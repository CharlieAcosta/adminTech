<?php

require_once __DIR__ . '/conectDB.php';
require_once __DIR__ . '/presupuestoGeneradoModel.php';
require_once __DIR__ . '/presupuestoMailConfigModel.php';

if (!function_exists('baseRutaDocumentosEmitidosPresupuesto')) {
    function baseRutaDocumentosEmitidosPresupuesto(int $idPresupuesto): string
    {
        return rtrim(rutaBaseFotosPresupuesto($idPresupuesto), '/\\') . '/emisiones';
    }
}

if (!function_exists('sanitizarNombreDocumentoEmitidoPresupuesto')) {
    function sanitizarNombreDocumentoEmitidoPresupuesto(string $nombre): string
    {
        $nombre = trim($nombre);
        $nombre = preg_replace('/\.pdf$/i', '', $nombre);
        $nombre = preg_replace('/[\\\\\/:*?"<>|]+/', '', $nombre);
        $nombre = preg_replace('/[\r\n\t]+/', ' ', $nombre);
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        $nombre = trim($nombre, " .");

        if ($nombre === '') {
            $nombre = 'PRESUPUESTO';
        }

        return $nombre;
    }
}

if (!function_exists('resolverRutaDocumentoEmitidoDisponible')) {
    function resolverRutaDocumentoEmitidoDisponible(string $directorio, string $nombreBase): array
    {
        $nombreBase = sanitizarNombreDocumentoEmitidoPresupuesto($nombreBase);
        $nombreArchivo = $nombreBase . '.pdf';
        $rutaAbsoluta = rtrim($directorio, '/\\') . '/' . $nombreArchivo;
        $contador = 1;

        while (file_exists($rutaAbsoluta)) {
            $contador++;
            $nombreArchivo = sprintf('%s_%02d.pdf', $nombreBase, $contador);
            $rutaAbsoluta = rtrim($directorio, '/\\') . '/' . $nombreArchivo;
        }

        return [
            'nombre_archivo' => $nombreArchivo,
            'ruta_absoluta' => $rutaAbsoluta,
        ];
    }
}

if (!function_exists('validarArchivoPdfEmitidoPresupuesto')) {
    function validarArchivoPdfEmitidoPresupuesto(array $archivo): array
    {
        if (empty($archivo) || !isset($archivo['tmp_name'])) {
            throw new RuntimeException('No se recibió el archivo PDF emitido.');
        }

        $error = (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Error al recibir el PDF emitido.');
        }

        $tmp = (string)$archivo['tmp_name'];
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('El archivo PDF subido no es válido.');
        }

        $size = (int)($archivo['size'] ?? 0);
        if ($size <= 0) {
            throw new RuntimeException('El archivo PDF emitido está vacío.');
        }

        $firma = '';
        $fh = @fopen($tmp, 'rb');
        if ($fh !== false) {
            $firma = (string)fread($fh, 4);
            fclose($fh);
        }

        if ($firma !== '%PDF') {
            throw new RuntimeException('El archivo recibido no tiene formato PDF válido.');
        }

        $mime = 'application/pdf';
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeDetectado = @finfo_file($finfo, $tmp);
                if (is_string($mimeDetectado) && $mimeDetectado !== '') {
                    $mime = $mimeDetectado;
                }
                @finfo_close($finfo);
            }
        }

        return [
            'tmp_name' => $tmp,
            'size' => $size,
            'mime_type' => $mime,
        ];
    }
}

if (!function_exists('emitirDocumentoPresupuesto')) {
    function emitirDocumentoPresupuesto(
        int $idPresupuesto,
        int $idPrevisita,
        int $idUsuario,
        array $archivoPdf,
        string $nombreArchivoPreferido = ''
    ): array {
        if ($idPresupuesto <= 0 || $idPrevisita <= 0 || $idUsuario <= 0) {
            return ['ok' => false, 'msg' => 'Datos incompletos para emitir el documento.'];
        }

        $db = conectDB();
        if (!$db) {
            return ['ok' => false, 'msg' => 'No se pudo abrir conexión a la base de datos.'];
        }

        mysqli_set_charset($db, 'utf8mb4');
        $rutaAbsolutaGuardada = null;
        $transaccionIniciada = false;

        try {
            if (!tabla_existe($db, 'presupuesto_documentos_emitidos')) {
                throw new RuntimeException('La tabla de documentos emitidos no existe en la base de datos.');
            }

            $archivoValidado = validarArchivoPdfEmitidoPresupuesto($archivoPdf);

            $sqlPresupuesto = "
                SELECT id_presupuesto, id_previsita, version
                FROM presupuestos
                WHERE id_presupuesto = ?
                LIMIT 1
            ";
            $stmtPresupuesto = stmt_or_throw($db, $sqlPresupuesto);
            mysqli_stmt_bind_param($stmtPresupuesto, 'i', $idPresupuesto);
            mysqli_stmt_execute($stmtPresupuesto);
            $resPresupuesto = mysqli_stmt_get_result($stmtPresupuesto);
            $presupuesto = $resPresupuesto ? mysqli_fetch_assoc($resPresupuesto) : null;
            mysqli_stmt_close($stmtPresupuesto);

            if (!$presupuesto) {
                throw new RuntimeException('No se encontró el presupuesto a emitir.');
            }

            if ((int)$presupuesto['id_previsita'] !== $idPrevisita) {
                throw new RuntimeException('La pre-visita no coincide con el presupuesto seleccionado.');
            }

            $versionPresupuesto = isset($presupuesto['version']) ? (int)$presupuesto['version'] : null;
            $nombreBase = $nombreArchivoPreferido !== ''
                ? $nombreArchivoPreferido
                : ('PRESUPUESTO_' . $idPrevisita . '_' . date('Ymd_His'));

            $directorioDestino = baseRutaDocumentosEmitidosPresupuesto($idPresupuesto);
            asegurarDir($directorioDestino);

            $destino = resolverRutaDocumentoEmitidoDisponible($directorioDestino, $nombreBase);
            $rutaAbsolutaGuardada = $destino['ruta_absoluta'];

            if (!@move_uploaded_file($archivoValidado['tmp_name'], $rutaAbsolutaGuardada)) {
                throw new RuntimeException('No se pudo guardar el PDF emitido en el servidor.');
            }

            $rutaRelativa = rutaPublicaDesdeAbsoluta($rutaAbsolutaGuardada);
            $tamanoBytes = filesize($rutaAbsolutaGuardada);
            if ($tamanoBytes === false) {
                $tamanoBytes = (int)$archivoValidado['size'];
            }

            mysqli_begin_transaction($db);
            $transaccionIniciada = true;

            $tieneEstadosComerciales = columna_existe($db, 'presupuestos', 'estado_comercial_simulacion')
                && columna_existe($db, 'presupuestos', 'estado_comercial_smtp');
            $sqlEstado = $tieneEstadosComerciales
                ? "
                    UPDATE presupuestos
                    SET estado = 'Emitido',
                        estado_comercial_simulacion = NULL,
                        estado_comercial_smtp = NULL,
                        updated_at = NOW()
                    WHERE id_presupuesto = ?
                "
                : "
                    UPDATE presupuestos
                    SET estado = 'Emitido', updated_at = NOW()
                    WHERE id_presupuesto = ?
                ";
            $stmtEstado = stmt_or_throw($db, $sqlEstado);
            mysqli_stmt_bind_param($stmtEstado, 'i', $idPresupuesto);
            mysqli_stmt_execute($stmtEstado);
            mysqli_stmt_close($stmtEstado);

            $sqlInsert = "
                INSERT INTO presupuesto_documentos_emitidos
                    (id_presupuesto, id_previsita, id_usuario, version_presupuesto, nombre_archivo, ruta_archivo, mime_type, tamano_bytes, created_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";
            $stmtInsert = stmt_or_throw($db, $sqlInsert);
            mysqli_stmt_bind_param(
                $stmtInsert,
                'iiiisssi',
                $idPresupuesto,
                $idPrevisita,
                $idUsuario,
                $versionPresupuesto,
                $destino['nombre_archivo'],
                $rutaRelativa,
                $archivoValidado['mime_type'],
                $tamanoBytes
            );
            mysqli_stmt_execute($stmtInsert);
            $idDocumentoEmitido = mysqli_insert_id($db);
            mysqli_stmt_close($stmtInsert);

            mysqli_commit($db);
            $transaccionIniciada = false;

            return [
                'ok' => true,
                'id_documento_emitido' => $idDocumentoEmitido,
                'id_presupuesto' => $idPresupuesto,
                'id_previsita' => $idPrevisita,
                'version_presupuesto' => $versionPresupuesto,
                'nombre_archivo' => $destino['nombre_archivo'],
                'ruta_archivo' => $rutaRelativa,
                'mime_type' => $archivoValidado['mime_type'],
                'tamano_bytes' => (int)$tamanoBytes,
                'estado' => 'Emitido',
            ];
        } catch (Throwable $e) {
            if ($transaccionIniciada) {
                @mysqli_rollback($db);
            }

            if ($rutaAbsolutaGuardada && is_file($rutaAbsolutaGuardada)) {
                @unlink($rutaAbsolutaGuardada);
            }

            return ['ok' => false, 'msg' => $e->getMessage()];
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('formatearFechaDocumentoEmitidoPresupuesto')) {
    function formatearFechaDocumentoEmitidoPresupuesto(?string $fecha): string
    {
        if (!$fecha) {
            return '-';
        }

        $dt = date_create($fecha);
        if (!$dt) {
            return (string)$fecha;
        }

        return $dt->format('d/m/Y H:i:s');
    }
}

if (!function_exists('extraerNumeroDocumentoEmitidoPresupuesto')) {
    function extraerNumeroDocumentoEmitidoPresupuesto(string $nombreArchivo): string
    {
        $base = preg_replace('/\.pdf$/i', '', trim($nombreArchivo));
        if ($base === '') {
            return '';
        }

        $partes = array_values(
            array_filter(
                explode('_', $base),
                static fn($valor) => $valor !== ''
            )
        );

        if (count($partes) < 3) {
            return str_replace('_', '-', $base);
        }

        $ultimaParte = (string)end($partes);
        $indices = [count($partes) - 3, count($partes) - 2, count($partes) - 1];

        if (preg_match('/^\d{2}$/', $ultimaParte) && count($partes) >= 4) {
            $indices = [count($partes) - 4, count($partes) - 3, count($partes) - 2];
        }

        $bloques = [
            $partes[$indices[0]] ?? '',
            $partes[$indices[1]] ?? '',
            $partes[$indices[2]] ?? '',
        ];

        if (
            preg_match('/^\d+$/', (string)$bloques[0]) &&
            preg_match('/^\d{8}$/', (string)$bloques[1]) &&
            preg_match('/^\d{6}$/', (string)$bloques[2])
        ) {
            return implode('-', $bloques);
        }

        return str_replace('_', '-', $base);
    }
}

if (!function_exists('resolverDisponibilidadDocumentoEmitidoPresupuesto')) {
    function resolverDisponibilidadDocumentoEmitidoPresupuesto(string $rutaArchivo): array
    {
        $rutaRelativa = ltrim(str_replace('\\', '/', trim($rutaArchivo)), '/');
        $rutaAbsoluta = normalizarRutaServidor($rutaRelativa);
        $disponible = $rutaRelativa !== '' && is_file($rutaAbsoluta);

        return [
            'ruta_archivo' => $rutaRelativa,
            'ruta_absoluta' => $rutaAbsoluta,
            'archivo_disponible' => $disponible,
        ];
    }
}

if (!function_exists('resolverRutaSeguraDocumentoEmitidoPresupuesto')) {
    function resolverRutaSeguraDocumentoEmitidoPresupuesto(
        int $idPresupuesto,
        string $rutaArchivo
    ): array {
        $directorioEsperado = realpath(baseRutaDocumentosEmitidosPresupuesto($idPresupuesto));
        $rutaNormalizada = trim(str_replace('\\', '/', $rutaArchivo));

        if (
            $idPresupuesto <= 0
            || $rutaNormalizada === ''
            || $directorioEsperado === false
            || !is_dir($directorioEsperado)
        ) {
            return [
                'disponible' => false,
                'ruta_absoluta' => '',
            ];
        }

        $rutaCandidata = normalizarRutaServidor($rutaNormalizada);
        $rutaReal = realpath($rutaCandidata);
        $baseReal = rtrim(str_replace('\\', '/', $directorioEsperado), '/');
        $archivoReal = $rutaReal !== false ? str_replace('\\', '/', $rutaReal) : '';
        $prefijoPermitido = $baseReal . '/';

        $dentroDelDirectorio = $archivoReal !== ''
            && strncmp($archivoReal, $prefijoPermitido, strlen($prefijoPermitido)) === 0;

        return [
            'disponible' => $dentroDelDirectorio && is_file($archivoReal) && is_readable($archivoReal),
            'ruta_absoluta' => $dentroDelDirectorio ? $archivoReal : '',
        ];
    }
}

if (!function_exists('resolverDocumentoAprobadoVigentePresupuestoEnConexion')) {
    function resolverDocumentoAprobadoVigentePresupuestoEnConexion(
        mysqli $db,
        int $idPresupuesto,
        int $idPrevisita
    ): array {
        $respuestaBase = [
            'disponible' => false,
            'id_documento_emitido' => null,
            'nombre_archivo' => '',
            'fecha_emision' => '',
            'fecha_envio' => '',
            'fecha_aprobacion' => '',
            'modo_circuito' => obtenerModoActivoCircuitoComercialPresupuestos(),
            'mensaje' => 'No se encontro un presupuesto aprobado disponible.',
            'ruta_absoluta' => '',
        ];

        if ($idPresupuesto <= 0 || $idPrevisita <= 0) {
            $respuestaBase['mensaje'] = 'Los identificadores del presupuesto y la previsita no son validos.';
            return $respuestaBase;
        }

        foreach ([
            'presupuestos',
            'presupuesto_documentos_emitidos',
            'presupuesto_documentos_emitidos_envios',
            'presupuesto_historial_comercial',
        ] as $tablaRequerida) {
            if (!tabla_existe($db, $tablaRequerida)) {
                $respuestaBase['mensaje'] = 'El circuito documental de presupuestos no esta disponible.';
                return $respuestaBase;
            }
        }

        $sqlPresupuesto = "
            SELECT id_presupuesto
            FROM presupuestos
            WHERE id_presupuesto = ?
              AND id_previsita = ?
            LIMIT 1
        ";
        $stmtPresupuesto = mysqli_prepare($db, $sqlPresupuesto);
        if (!$stmtPresupuesto) {
            $respuestaBase['mensaje'] = 'No se pudo validar el presupuesto solicitado.';
            return $respuestaBase;
        }
        mysqli_stmt_bind_param($stmtPresupuesto, 'ii', $idPresupuesto, $idPrevisita);
        mysqli_stmt_execute($stmtPresupuesto);
        $resPresupuesto = mysqli_stmt_get_result($stmtPresupuesto);
        $presupuestoExiste = $resPresupuesto && mysqli_fetch_assoc($resPresupuesto);
        mysqli_stmt_close($stmtPresupuesto);

        if (!$presupuestoExiste) {
            $respuestaBase['mensaje'] = 'El presupuesto no corresponde a la previsita informada.';
            return $respuestaBase;
        }

        $modoCircuito = $respuestaBase['modo_circuito'];
        $sqlEstadoVigente = "
            SELECT estado_resultante
            FROM presupuesto_historial_comercial
            WHERE id_presupuesto = ?
              AND id_previsita = ?
              AND modo_circuito = ?
            ORDER BY created_at DESC, id_historial_comercial DESC
            LIMIT 1
        ";
        $stmtEstado = mysqli_prepare($db, $sqlEstadoVigente);
        if (!$stmtEstado) {
            $respuestaBase['mensaje'] = 'No se pudo validar el estado comercial del presupuesto.';
            return $respuestaBase;
        }
        mysqli_stmt_bind_param($stmtEstado, 'iis', $idPresupuesto, $idPrevisita, $modoCircuito);
        mysqli_stmt_execute($stmtEstado);
        $resEstado = mysqli_stmt_get_result($stmtEstado);
        $estadoVigente = $resEstado ? mysqli_fetch_assoc($resEstado) : null;
        mysqli_stmt_close($stmtEstado);

        if (strtoupper(trim((string)($estadoVigente['estado_resultante'] ?? ''))) !== 'APROBADO') {
            $respuestaBase['mensaje'] = 'El presupuesto no se encuentra aprobado en el circuito comercial vigente.';
            return $respuestaBase;
        }

        $sqlAprobacion = "
            SELECT
                h.id_documento_emitido,
                h.created_at AS fecha_aprobacion
            FROM presupuesto_historial_comercial h
            WHERE h.id_presupuesto = ?
              AND h.id_previsita = ?
              AND h.modo_circuito = ?
              AND h.accion = 'aprobado'
              AND h.estado_resultante = 'APROBADO'
            ORDER BY h.created_at DESC, h.id_historial_comercial DESC
            LIMIT 1
        ";
        $stmtAprobacion = mysqli_prepare($db, $sqlAprobacion);
        if (!$stmtAprobacion) {
            $respuestaBase['mensaje'] = 'No se pudo consultar la aprobacion vigente.';
            return $respuestaBase;
        }
        mysqli_stmt_bind_param($stmtAprobacion, 'iis', $idPresupuesto, $idPrevisita, $modoCircuito);
        mysqli_stmt_execute($stmtAprobacion);
        $resAprobacion = mysqli_stmt_get_result($stmtAprobacion);
        $aprobacion = $resAprobacion ? mysqli_fetch_assoc($resAprobacion) : null;
        mysqli_stmt_close($stmtAprobacion);

        $idDocumentoEmitido = (int)($aprobacion['id_documento_emitido'] ?? 0);
        if (!$aprobacion || $idDocumentoEmitido <= 0) {
            $respuestaBase['fecha_aprobacion'] = (string)($aprobacion['fecha_aprobacion'] ?? '');
            $respuestaBase['mensaje'] = 'La aprobacion vigente no tiene un documento emitido asociado.';
            return $respuestaBase;
        }

        $sqlDocumento = "
            SELECT id_documento_emitido, nombre_archivo, ruta_archivo, created_at AS fecha_emision
            FROM presupuesto_documentos_emitidos
            WHERE id_documento_emitido = ?
              AND id_presupuesto = ?
              AND id_previsita = ?
            LIMIT 1
        ";
        $stmtDocumento = mysqli_prepare($db, $sqlDocumento);
        if (!$stmtDocumento) {
            $respuestaBase['mensaje'] = 'No se pudo consultar el documento aprobado.';
            return $respuestaBase;
        }
        mysqli_stmt_bind_param(
            $stmtDocumento,
            'iii',
            $idDocumentoEmitido,
            $idPresupuesto,
            $idPrevisita
        );
        mysqli_stmt_execute($stmtDocumento);
        $resDocumento = mysqli_stmt_get_result($stmtDocumento);
        $documento = $resDocumento ? mysqli_fetch_assoc($resDocumento) : null;
        mysqli_stmt_close($stmtDocumento);

        if (!$documento) {
            $respuestaBase['id_documento_emitido'] = $idDocumentoEmitido;
            $respuestaBase['fecha_aprobacion'] = (string)($aprobacion['fecha_aprobacion'] ?? '');
            $respuestaBase['mensaje'] = 'El documento asociado a la aprobacion vigente no existe.';
            return $respuestaBase;
        }

        $estadoEnvioExitoso = $modoCircuito === 'smtp' ? 'enviado' : 'simulado';
        $sqlEnvio = "
            SELECT MAX(created_at) AS fecha_envio
            FROM presupuesto_documentos_emitidos_envios
            WHERE id_documento_emitido = ?
              AND id_presupuesto = ?
              AND id_previsita = ?
              AND modo_envio = ?
              AND estado_envio = ?
        ";
        $stmtEnvio = mysqli_prepare($db, $sqlEnvio);
        if (!$stmtEnvio) {
            $respuestaBase['mensaje'] = 'No se pudo validar el envio del documento aprobado.';
            return $respuestaBase;
        }
        mysqli_stmt_bind_param(
            $stmtEnvio,
            'iiiss',
            $idDocumentoEmitido,
            $idPresupuesto,
            $idPrevisita,
            $modoCircuito,
            $estadoEnvioExitoso
        );
        mysqli_stmt_execute($stmtEnvio);
        $resEnvio = mysqli_stmt_get_result($stmtEnvio);
        $envio = $resEnvio ? mysqli_fetch_assoc($resEnvio) : null;
        mysqli_stmt_close($stmtEnvio);

        if (empty($envio['fecha_envio'])) {
            $respuestaBase['id_documento_emitido'] = $idDocumentoEmitido;
            $respuestaBase['nombre_archivo'] = repararTextoMojibakePresupuestoProfundo(
                (string)($documento['nombre_archivo'] ?? '')
            );
            $respuestaBase['fecha_emision'] = (string)($documento['fecha_emision'] ?? '');
            $respuestaBase['fecha_aprobacion'] = (string)($aprobacion['fecha_aprobacion'] ?? '');
            $respuestaBase['mensaje'] = 'El documento asociado a la aprobacion vigente no tiene un envio exitoso.';
            return $respuestaBase;
        }

        $rutaSegura = resolverRutaSeguraDocumentoEmitidoPresupuesto(
            $idPresupuesto,
            (string)($documento['ruta_archivo'] ?? '')
        );

        $respuesta = array_merge($respuestaBase, [
            'id_documento_emitido' => (int)$documento['id_documento_emitido'],
            'nombre_archivo' => repararTextoMojibakePresupuestoProfundo((string)$documento['nombre_archivo']),
            'fecha_emision' => (string)($documento['fecha_emision'] ?? ''),
            'fecha_envio' => (string)$envio['fecha_envio'],
            'fecha_aprobacion' => (string)($aprobacion['fecha_aprobacion'] ?? ''),
            'ruta_absoluta' => (string)$rutaSegura['ruta_absoluta'],
        ]);

        if (empty($rutaSegura['disponible'])) {
            $respuesta['mensaje'] = 'El documento aprobado esta registrado, pero el archivo no esta disponible en el servidor.';
            return $respuesta;
        }

        $respuesta['disponible'] = true;
        $respuesta['mensaje'] = 'Presupuesto aprobado disponible.';

        return $respuesta;
    }
}

if (!function_exists('resolverDocumentoAprobadoVigentePresupuesto')) {
    function resolverDocumentoAprobadoVigentePresupuesto(
        int $idPresupuesto,
        int $idPrevisita
    ): array {
        $db = conectDB();
        if (!$db) {
            return [
                'disponible' => false,
                'mensaje' => 'No se pudo abrir conexion a la base de datos.',
            ];
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            return resolverDocumentoAprobadoVigentePresupuestoEnConexion(
                $db,
                $idPresupuesto,
                $idPrevisita
            );
        } catch (Throwable $e) {
            return [
                'disponible' => false,
                'mensaje' => 'No se pudo consultar el presupuesto aprobado.',
            ];
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('listarDocumentosEmitidosPresupuesto')) {
    function listarDocumentosEmitidosPresupuesto(int $idPrevisita, ?int $idPresupuesto = null): array
    {
        if ($idPrevisita <= 0) {
            return [];
        }

        $db = conectDB();
        if (!$db) {
            return [];
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            if (!tabla_existe($db, 'presupuesto_documentos_emitidos')) {
                return [];
            }

            $modoActivo = obtenerModoActivoCircuitoComercialPresupuestos();
            $tablaEnviosExiste = tabla_existe($db, 'presupuesto_documentos_emitidos_envios');
            $selectEnvios = $tablaEnviosExiste
                ? "
                        COALESCE(ev.total_envios_activos, 0) AS total_envios_activos,
                        ev.ultimo_envio_activo"
                : "
                        0 AS total_envios_activos,
                        NULL AS ultimo_envio_activo";
            $joinEnvios = $tablaEnviosExiste
                ? "
                    LEFT JOIN (
                        SELECT
                            id_documento_emitido,
                            SUM(
                                CASE
                                    WHEN modo_envio = '" . mysqli_real_escape_string($db, $modoActivo) . "'
                                     AND (
                                        ('" . mysqli_real_escape_string($db, $modoActivo) . "' = 'smtp' AND estado_envio = 'enviado')
                                        OR
                                        ('" . mysqli_real_escape_string($db, $modoActivo) . "' = 'simulacion' AND estado_envio = 'simulado')
                                     )
                                    THEN 1 ELSE 0
                                END
                            ) AS total_envios_activos,
                            MAX(
                                CASE
                                    WHEN modo_envio = '" . mysqli_real_escape_string($db, $modoActivo) . "'
                                     AND (
                                        ('" . mysqli_real_escape_string($db, $modoActivo) . "' = 'smtp' AND estado_envio = 'enviado')
                                        OR
                                        ('" . mysqli_real_escape_string($db, $modoActivo) . "' = 'simulacion' AND estado_envio = 'simulado')
                                     )
                                    THEN created_at ELSE NULL
                                END
                            ) AS ultimo_envio_activo
                        FROM presupuesto_documentos_emitidos_envios
                        GROUP BY id_documento_emitido
                    ) ev
                        ON ev.id_documento_emitido = d.id_documento_emitido"
                : '';

            if ($idPresupuesto !== null && $idPresupuesto > 0) {
                $sql = "
                    SELECT
                        d.id_documento_emitido,
                        d.id_presupuesto,
                        d.id_previsita,
                        d.id_usuario,
                        d.version_presupuesto,
                        d.nombre_archivo,
                        d.ruta_archivo,
                        d.mime_type,
                        d.tamano_bytes,
                        d.created_at,
                        {$selectEnvios},
                        u.apellidos,
                        u.nombres
                    FROM presupuesto_documentos_emitidos d
                    LEFT JOIN usuarios u
                        ON u.id_usuario = d.id_usuario
                    {$joinEnvios}
                    WHERE d.id_previsita = ?
                      AND d.id_presupuesto = ?
                    ORDER BY d.created_at DESC, d.id_documento_emitido DESC
                ";
                $stmt = stmt_or_throw($db, $sql);
                mysqli_stmt_bind_param($stmt, 'ii', $idPrevisita, $idPresupuesto);
            } else {
                $sql = "
                    SELECT
                        d.id_documento_emitido,
                        d.id_presupuesto,
                        d.id_previsita,
                        d.id_usuario,
                        d.version_presupuesto,
                        d.nombre_archivo,
                        d.ruta_archivo,
                        d.mime_type,
                        d.tamano_bytes,
                        d.created_at,
                        {$selectEnvios},
                        u.apellidos,
                        u.nombres
                    FROM presupuesto_documentos_emitidos d
                    LEFT JOIN usuarios u
                        ON u.id_usuario = d.id_usuario
                    {$joinEnvios}
                    WHERE d.id_previsita = ?
                    ORDER BY d.created_at DESC, d.id_documento_emitido DESC
                ";
                $stmt = stmt_or_throw($db, $sql);
                mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
            }

            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $rows = [];

            while ($res && ($row = mysqli_fetch_assoc($res))) {
                $apellido = trim((string)($row['apellidos'] ?? ''));
                $nombre = trim((string)($row['nombres'] ?? ''));
                $usuarioNombre = trim($apellido . ' ' . $nombre);

                if ($usuarioNombre === '') {
                    $usuarioNombre = 'Usuario #' . (int)($row['id_usuario'] ?? 0);
                }

                $nombreArchivo = repararTextoMojibakePresupuestoProfundo((string)($row['nombre_archivo'] ?? ''));
                $nombreBase = preg_replace('/\.pdf$/i', '', $nombreArchivo);
                $rutaDisponibilidad = resolverDisponibilidadDocumentoEmitidoPresupuesto((string)($row['ruta_archivo'] ?? ''));
                $totalEnviados = (int)($row['total_envios_activos'] ?? 0);
                $ultimoEnvioReal = (string)($row['ultimo_envio_activo'] ?? '');

                $envioEstado = 'no_enviado';
                $envioCantidad = 0;
                $ultimoEnvio = '';
                $envioLabel = 'No enviado';

                if ($totalEnviados > 0) {
                    $envioEstado = 'enviado';
                    $envioCantidad = $totalEnviados;
                    $ultimoEnvio = $ultimoEnvioReal;
                    $envioLabel = 'Enviado (' . $totalEnviados . ')';
                }

                $rows[] = [
                    'id_documento_emitido' => (int)($row['id_documento_emitido'] ?? 0),
                    'id_presupuesto' => (int)($row['id_presupuesto'] ?? 0),
                    'id_previsita' => (int)($row['id_previsita'] ?? 0),
                    'id_usuario' => (int)($row['id_usuario'] ?? 0),
                    'version_presupuesto' => isset($row['version_presupuesto']) ? (int)$row['version_presupuesto'] : null,
                    'nombre_archivo' => $nombreArchivo,
                    'nombre_base' => $nombreBase,
                    'numero_documento' => extraerNumeroDocumentoEmitidoPresupuesto($nombreArchivo),
                    'ruta_archivo' => $rutaDisponibilidad['ruta_archivo'],
                    'archivo_disponible' => $rutaDisponibilidad['archivo_disponible'],
                    'mime_type' => (string)($row['mime_type'] ?? 'application/pdf'),
                    'tamano_bytes' => (int)($row['tamano_bytes'] ?? 0),
                    'created_at' => (string)($row['created_at'] ?? ''),
                    'fecha_texto' => formatearFechaDocumentoEmitidoPresupuesto((string)($row['created_at'] ?? '')),
                    'usuario_nombre' => repararTextoMojibakePresupuestoProfundo($usuarioNombre),
                    'envio_estado' => $envioEstado,
                    'envio_cantidad' => $envioCantidad,
                    'envio_label' => $envioLabel,
                    'ultimo_envio_at' => $ultimoEnvio,
                    'ultimo_envio_texto' => $ultimoEnvio !== '' ? formatearFechaDocumentoEmitidoPresupuesto($ultimoEnvio) : '',
                ];
            }

            mysqli_stmt_close($stmt);

            return $rows;
        } catch (Throwable $e) {
            return [];
        } finally {
            mysqli_close($db);
        }
    }
}
