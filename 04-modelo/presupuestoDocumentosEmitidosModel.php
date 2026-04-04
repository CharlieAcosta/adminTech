<?php

require_once __DIR__ . '/conectDB.php';
require_once __DIR__ . '/presupuestoGeneradoModel.php';

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

            $sqlEstado = "
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
                        u.apellidos,
                        u.nombres
                    FROM presupuesto_documentos_emitidos d
                    LEFT JOIN usuarios u
                        ON u.id_usuario = d.id_usuario
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
                        u.apellidos,
                        u.nombres
                    FROM presupuesto_documentos_emitidos d
                    LEFT JOIN usuarios u
                        ON u.id_usuario = d.id_usuario
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
