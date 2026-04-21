<?php

require_once __DIR__ . '/conectDB.php';
require_once __DIR__ . '/schemaIntrospectionModel.php';
require_once __DIR__ . '/presupuestoGeneradoModel.php';

if (!function_exists('tablaDocumentosPrevisita')) {
    function tablaDocumentosPrevisita(): string
    {
        return 'previsita_documentos';
    }
}

if (!function_exists('rutaBaseRaizDocumentosPrevisita')) {
    function rutaBaseRaizDocumentosPrevisita(): string
    {
        $root = realpath(__DIR__ . '/..');
        if ($root === false) {
            $root = __DIR__ . '/..';
        }

        return rtrim($root, '/\\') . '/09-adjuntos/previsita/';
    }
}

if (!function_exists('rutaBaseDocumentosPrevisita')) {
    function rutaBaseDocumentosPrevisita(int $idPrevisita): string
    {
        return rtrim(rutaBaseRaizDocumentosPrevisita(), '/\\') . '/' . max(0, $idPrevisita) . '/';
    }
}

if (!function_exists('rutaLegacyDocumentoPrevisita')) {
    function rutaLegacyDocumentoPrevisita(string $nombreArchivo): string
    {
        return '09-adjuntos/previsita/' . ltrim(str_replace('\\', '/', trim($nombreArchivo)), '/');
    }
}

if (!function_exists('rutaPublicaDocumentoPrevisitaDesdeAbsoluta')) {
    function rutaPublicaDocumentoPrevisitaDesdeAbsoluta(string $rutaAbsoluta): string
    {
        $rutaAbsoluta = str_replace('\\', '/', $rutaAbsoluta);
        $pos = strpos($rutaAbsoluta, '/09-adjuntos/');
        if ($pos !== false) {
            return ltrim(substr($rutaAbsoluta, $pos), '/');
        }

        return basename($rutaAbsoluta);
    }
}

if (!function_exists('normalizarRutaDocumentoPrevisitaServidor')) {
    function normalizarRutaDocumentoPrevisitaServidor(string $rutaRelativa): string
    {
        $rutaRelativa = str_replace('\\', '/', trim($rutaRelativa));
        if ($rutaRelativa === '') {
            return '';
        }

        if (preg_match('~^/?09-adjuntos/~', $rutaRelativa)) {
            $root = realpath(__DIR__ . '/..');
            if ($root === false) {
                $root = __DIR__ . '/..';
            }

            return rtrim($root, '/\\') . '/' . ltrim($rutaRelativa, '/');
        }

        return $rutaRelativa;
    }
}

if (!function_exists('rutaDocumentoPrevisitaDentroDeBase')) {
    function rutaDocumentoPrevisitaDentroDeBase(string $rutaAbsoluta): bool
    {
        $rutaAbsoluta = str_replace('\\', '/', $rutaAbsoluta);
        $base = str_replace('\\', '/', rtrim(rutaBaseRaizDocumentosPrevisita(), '/\\')) . '/';

        return $rutaAbsoluta !== '' && strpos($rutaAbsoluta, $base) === 0;
    }
}

if (!function_exists('extensionesPermitidasDocumentosPrevisita')) {
    function extensionesPermitidasDocumentosPrevisita(): array
    {
        return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png'];
    }
}

if (!function_exists('mimePermitidosDocumentosPrevisita')) {
    function mimePermitidosDocumentosPrevisita(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'image/jpeg',
            'image/png',
            'application/octet-stream',
            'application/zip',
        ];
    }
}

if (!function_exists('formatearTamanoDocumentoPrevisita')) {
    function formatearTamanoDocumentoPrevisita(?int $bytes): string
    {
        $bytes = (int)($bytes ?? 0);
        if ($bytes <= 0) {
            return '';
        }

        $unidades = ['B', 'KB', 'MB', 'GB'];
        $valor = (float)$bytes;
        $indice = 0;

        while ($valor >= 1024 && $indice < count($unidades) - 1) {
            $valor /= 1024;
            $indice++;
        }

        $decimales = $indice === 0 ? 0 : ($valor >= 10 ? 1 : 2);

        return number_format($valor, $decimales, ',', '.') . ' ' . $unidades[$indice];
    }
}

if (!function_exists('normalizarArchivosSubidosPrevisita')) {
    function normalizarArchivosSubidosPrevisita(array $files): array
    {
        if (empty($files) || !isset($files['name'])) {
            return [];
        }

        $normalizados = [];

        if (is_array($files['name'])) {
            $total = count($files['name']);
            for ($i = 0; $i < $total; $i++) {
                $normalizados[] = [
                    'name' => $files['name'][$i] ?? '',
                    'type' => $files['type'][$i] ?? '',
                    'tmp_name' => $files['tmp_name'][$i] ?? '',
                    'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $files['size'][$i] ?? 0,
                ];
            }

            return $normalizados;
        }

        return [$files];
    }
}

if (!function_exists('validarArchivoDocumentoPrevisita')) {
    function validarArchivoDocumentoPrevisita(array $archivo): array
    {
        if (empty($archivo) || !isset($archivo['tmp_name'])) {
            throw new RuntimeException('No se recibió el archivo adjunto de la pre-visita.');
        }

        $error = (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Error al recibir uno de los documentos adjuntos.');
        }

        $tmp = (string)($archivo['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Uno de los documentos adjuntos no es válido.');
        }

        $size = (int)($archivo['size'] ?? 0);
        if ($size <= 0) {
            throw new RuntimeException('Uno de los documentos adjuntos está vacío.');
        }

        if ($size > 5 * 1024 * 1024) {
            throw new RuntimeException('Cada documento adjunto debe pesar como máximo 5 MB.');
        }

        $nombreOriginal = trim((string)($archivo['name'] ?? ''));
        if ($nombreOriginal === '') {
            throw new RuntimeException('Uno de los documentos adjuntos no tiene nombre de archivo.');
        }

        $extension = strtolower((string)pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, extensionesPermitidasDocumentosPrevisita(), true)) {
            throw new RuntimeException('Solo se permiten archivos PDF, Word, Excel, JPG, PNG o texto en la pre-visita.');
        }

        $mimeType = trim((string)($archivo['type'] ?? ''));
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeDetectado = @finfo_file($finfo, $tmp);
                if (is_string($mimeDetectado) && $mimeDetectado !== '') {
                    $mimeType = $mimeDetectado;
                }
                @finfo_close($finfo);
            }
        }

        if ($mimeType !== '' && !in_array($mimeType, mimePermitidosDocumentosPrevisita(), true)) {
            if (!in_array($extension, ['docx', 'xlsx'], true)) {
                throw new RuntimeException('Uno de los documentos adjuntos no tiene un formato permitido.');
            }
        }

        return [
            'tmp_name' => $tmp,
            'size' => $size,
            'mime_type' => $mimeType !== '' ? $mimeType : 'application/octet-stream',
            'extension' => $extension,
            'original_name' => $nombreOriginal,
        ];
    }
}

if (!function_exists('resolverRutaDocumentoPrevisitaDisponible')) {
    function resolverRutaDocumentoPrevisitaDisponible(string $directorioDestino, string $nombreOriginal): array
    {
        $extension = strtolower((string)pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $base = trim((string)pathinfo($nombreOriginal, PATHINFO_FILENAME));
        $base = slugify($base !== '' ? $base : 'documento');
        $sufijo = $extension !== '' ? '.' . $extension : '';

        $nombreArchivo = $base . $sufijo;
        $rutaAbsoluta = rtrim($directorioDestino, '/\\') . '/' . $nombreArchivo;
        $contador = 1;

        while (file_exists($rutaAbsoluta)) {
            $contador++;
            $nombreArchivo = sprintf('%s_%02d%s', $base, $contador, $sufijo);
            $rutaAbsoluta = rtrim($directorioDestino, '/\\') . '/' . $nombreArchivo;
        }

        return [
            'nombre_archivo' => $nombreArchivo,
            'ruta_absoluta' => $rutaAbsoluta,
        ];
    }
}

if (!function_exists('armarDocumentoPrevisitaParaVista')) {
    function armarDocumentoPrevisitaParaVista(array $row): array
    {
        $rutaArchivo = trim((string)($row['ruta_archivo'] ?? ''));
        $rutaAbsoluta = normalizarRutaDocumentoPrevisitaServidor($rutaArchivo);
        $archivoDisponible = $rutaArchivo !== '' && $rutaAbsoluta !== '' && is_file($rutaAbsoluta);

        $nombreArchivo = trim((string)($row['nombre_archivo'] ?? ''));
        $nombreOriginal = trim((string)($row['nombre_archivo_original'] ?? ''));
        $nombreVisual = $nombreOriginal !== '' ? $nombreOriginal : ($nombreArchivo !== '' ? $nombreArchivo : basename($rutaArchivo));
        $extension = strtolower((string)pathinfo($nombreVisual !== '' ? $nombreVisual : $rutaArchivo, PATHINFO_EXTENSION));
        $tamano = isset($row['tamano_bytes']) ? (int)$row['tamano_bytes'] : 0;

        return [
            'id_documento_previsita' => (int)($row['id_documento_previsita'] ?? 0),
            'id_previsita' => (int)($row['id_previsita'] ?? 0),
            'nombre_archivo' => $nombreArchivo,
            'nombre_archivo_original' => $nombreOriginal,
            'nombre_visual' => $nombreVisual,
            'ruta_archivo' => $rutaArchivo,
            'url_publica' => $archivoDisponible && $rutaArchivo !== '' ? '../' . ltrim($rutaArchivo, '/') : '',
            'mime_type' => (string)($row['mime_type'] ?? ''),
            'tamano_bytes' => $tamano,
            'tamano_texto' => formatearTamanoDocumentoPrevisita($tamano),
            'extension' => $extension,
            'archivo_disponible' => $archivoDisponible,
            'created_at' => (string)($row['created_at'] ?? ''),
            'es_legacy' => !empty($row['es_legacy']),
        ];
    }
}

if (!function_exists('listarDocumentosPrevisitaEnConexion')) {
    function listarDocumentosPrevisitaEnConexion(mysqli $db, int $idPrevisita, array $legacyData = []): array
    {
        if ($idPrevisita <= 0) {
            return [];
        }

        $items = [];
        $rutasVistas = [];
        $nombresVistos = [];
        $tabla = tablaDocumentosPrevisita();

        if (tabla_existe($db, $tabla)) {
            $sql = "
                SELECT
                    id_documento_previsita,
                    id_previsita,
                    nombre_archivo,
                    nombre_archivo_original,
                    ruta_archivo,
                    mime_type,
                    tamano_bytes,
                    created_at
                FROM {$tabla}
                WHERE id_previsita = ?
                ORDER BY created_at DESC, id_documento_previsita DESC
            ";
            $stmt = stmt_or_throw($db, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            while ($result && ($row = mysqli_fetch_assoc($result))) {
                $doc = armarDocumentoPrevisitaParaVista($row);
                $items[] = $doc;
                if ($doc['ruta_archivo'] !== '') {
                    $rutasVistas[$doc['ruta_archivo']] = true;
                }
                foreach ([
                    trim((string)($doc['nombre_archivo'] ?? '')),
                    trim((string)($doc['nombre_archivo_original'] ?? '')),
                    basename((string)($doc['ruta_archivo'] ?? '')),
                ] as $nombreVisto) {
                    $nombreNormalizado = mb_strtolower($nombreVisto, 'UTF-8');
                    if ($nombreNormalizado !== '') {
                        $nombresVistos[$nombreNormalizado] = true;
                    }
                }
            }

            mysqli_stmt_close($stmt);
        }

        $docLegacy = trim((string)($legacyData['doc_previsita'] ?? ''));
        if ($docLegacy !== '') {
            $rutaLegacy = rutaLegacyDocumentoPrevisita($docLegacy);
            $docLegacyNormalizado = mb_strtolower($docLegacy, 'UTF-8');
            if (!isset($rutasVistas[$rutaLegacy]) && !isset($nombresVistos[$docLegacyNormalizado])) {
                $items[] = armarDocumentoPrevisitaParaVista([
                    'id_documento_previsita' => 0,
                    'id_previsita' => $idPrevisita,
                    'nombre_archivo' => $docLegacy,
                    'nombre_archivo_original' => $docLegacy,
                    'ruta_archivo' => $rutaLegacy,
                    'mime_type' => '',
                    'tamano_bytes' => 0,
                    'created_at' => '',
                    'es_legacy' => true,
                ]);
            }
        }

        return $items;
    }
}

if (!function_exists('listarDocumentosPrevisita')) {
    function listarDocumentosPrevisita(int $idPrevisita, array $legacyData = []): array
    {
        if ($idPrevisita <= 0) {
            return [];
        }

        $db = conectDB();
        mysqli_set_charset($db, 'utf8mb4');

        try {
            return listarDocumentosPrevisitaEnConexion($db, $idPrevisita, $legacyData);
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('decodificarDocumentosPrevisitaEliminados')) {
    function decodificarDocumentosPrevisitaEliminados($raw): array
    {
        if (is_array($raw)) {
            $decoded = $raw;
        } else {
            $raw = trim((string)$raw);
            if ($raw === '') {
                return [];
            }

            $decoded = json_decode($raw, true);
        }

        if (!is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $rutaArchivo = trim((string)($row['ruta_archivo'] ?? ''));
            $idDocumento = (int)($row['id_documento_previsita'] ?? 0);
            if ($idDocumento <= 0 && $rutaArchivo === '') {
                continue;
            }

            $items[] = [
                'id_documento_previsita' => $idDocumento,
                'ruta_archivo' => $rutaArchivo,
                'es_legacy' => !empty($row['es_legacy']),
            ];
        }

        return $items;
    }
}

if (!function_exists('sincronizarColumnaLegacyDocumentoPrevisitaEnConexion')) {
    function sincronizarColumnaLegacyDocumentoPrevisitaEnConexion(mysqli $db, int $idPrevisita): void
    {
        if ($idPrevisita <= 0 || !columna_existe($db, 'previsitas', 'doc_previsita')) {
            return;
        }

        $valorLegacy = '';
        $tabla = tablaDocumentosPrevisita();

        if (tabla_existe($db, $tabla)) {
            $sql = "
                SELECT nombre_archivo
                FROM {$tabla}
                WHERE id_previsita = ?
                ORDER BY created_at DESC, id_documento_previsita DESC
                LIMIT 1
            ";
            $stmt = stmt_or_throw($db, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            if ($row && !empty($row['nombre_archivo'])) {
                $valorLegacy = (string)$row['nombre_archivo'];
            }
        }

        $sqlUpdate = "UPDATE previsitas SET doc_previsita = ? WHERE id_previsita = ?";
        $stmtUpdate = stmt_or_throw($db, $sqlUpdate);
        mysqli_stmt_bind_param($stmtUpdate, 'si', $valorLegacy, $idPrevisita);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }
}

if (!function_exists('eliminarDocumentoPrevisitaPersistidoEnConexion')) {
    function eliminarDocumentoPrevisitaPersistidoEnConexion(mysqli $db, int $idPrevisita, array $documento): void
    {
        if ($idPrevisita <= 0) {
            return;
        }

        $tabla = tablaDocumentosPrevisita();
        $idDocumento = (int)($documento['id_documento_previsita'] ?? 0);
        $rutaArchivo = trim((string)($documento['ruta_archivo'] ?? ''));
        $rutaPersistida = '';

        if ($idDocumento > 0 && tabla_existe($db, $tabla)) {
            $sql = "
                SELECT ruta_archivo
                FROM {$tabla}
                WHERE id_documento_previsita = ? AND id_previsita = ?
                LIMIT 1
            ";
            $stmt = stmt_or_throw($db, $sql);
            mysqli_stmt_bind_param($stmt, 'ii', $idDocumento, $idPrevisita);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            if ($row && !empty($row['ruta_archivo'])) {
                $rutaPersistida = (string)$row['ruta_archivo'];
            }

            $sqlDelete = "DELETE FROM {$tabla} WHERE id_documento_previsita = ? AND id_previsita = ?";
            $stmtDelete = stmt_or_throw($db, $sqlDelete);
            mysqli_stmt_bind_param($stmtDelete, 'ii', $idDocumento, $idPrevisita);
            mysqli_stmt_execute($stmtDelete);
            mysqli_stmt_close($stmtDelete);
        }

        if ($rutaPersistida === '' && $rutaArchivo !== '') {
            $rutaPersistida = $rutaArchivo;

            if (tabla_existe($db, $tabla)) {
                $sqlDeleteRuta = "DELETE FROM {$tabla} WHERE id_previsita = ? AND ruta_archivo = ?";
                $stmtDeleteRuta = stmt_or_throw($db, $sqlDeleteRuta);
                mysqli_stmt_bind_param($stmtDeleteRuta, 'is', $idPrevisita, $rutaArchivo);
                mysqli_stmt_execute($stmtDeleteRuta);
                mysqli_stmt_close($stmtDeleteRuta);
            }
        }

        $rutaAbsoluta = normalizarRutaDocumentoPrevisitaServidor($rutaPersistida);
        if ($rutaAbsoluta !== '' && rutaDocumentoPrevisitaDentroDeBase($rutaAbsoluta) && is_file($rutaAbsoluta)) {
            @unlink($rutaAbsoluta);
        }
    }
}

if (!function_exists('guardarDocumentoPrevisitaEnConexion')) {
    function guardarDocumentoPrevisitaEnConexion(mysqli $db, int $idPrevisita, int $idUsuario, array $archivo): void
    {
        if ($idPrevisita <= 0) {
            throw new RuntimeException('Pre-visita inválida para adjuntar documentos.');
        }

        $archivoValidado = validarArchivoDocumentoPrevisita($archivo);
        $directorioDestino = rutaBaseDocumentosPrevisita($idPrevisita);
        asegurarDir($directorioDestino);

        $destino = resolverRutaDocumentoPrevisitaDisponible($directorioDestino, $archivoValidado['original_name']);
        if (!@move_uploaded_file($archivoValidado['tmp_name'], $destino['ruta_absoluta'])) {
            throw new RuntimeException('No se pudo guardar uno de los documentos adjuntos en el servidor.');
        }

        $rutaRelativa = rutaPublicaDocumentoPrevisitaDesdeAbsoluta($destino['ruta_absoluta']);
        $tamanoBytes = @filesize($destino['ruta_absoluta']);
        if ($tamanoBytes === false) {
            $tamanoBytes = (int)$archivoValidado['size'];
        }

        $sql = "
            INSERT INTO " . tablaDocumentosPrevisita() . "
                (id_previsita, nombre_archivo, nombre_archivo_original, ruta_archivo, mime_type, tamano_bytes, id_usuario_alta, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        $stmt = stmt_or_throw($db, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            'issssii',
            $idPrevisita,
            $destino['nombre_archivo'],
            $archivoValidado['original_name'],
            $rutaRelativa,
            $archivoValidado['mime_type'],
            $tamanoBytes,
            $idUsuario
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('sincronizarDocumentosPrevisitaEnConexion')) {
    function sincronizarDocumentosPrevisitaEnConexion(
        mysqli $db,
        int $idPrevisita,
        int $idUsuario,
        array $archivosNuevos = [],
        array $documentosEliminados = [],
        array $legacyData = []
    ): array {
        if ($idPrevisita <= 0) {
            return [];
        }

        $archivosNuevos = array_values(array_filter($archivosNuevos, static function ($archivo) {
            return is_array($archivo) && !empty($archivo['tmp_name']);
        }));
        $documentosEliminados = decodificarDocumentosPrevisitaEliminados($documentosEliminados);

        if ((count($archivosNuevos) > 0 || count($documentosEliminados) > 0) && !tabla_existe($db, tablaDocumentosPrevisita())) {
            throw new RuntimeException(
                'La base no tiene la tabla previsita_documentos. Ejecutá la migración 11-migraciones_sql/2026-04-19-A_previsita_documentos.sql antes de usar múltiples documentos.'
            );
        }

        foreach ($documentosEliminados as $documento) {
            eliminarDocumentoPrevisitaPersistidoEnConexion($db, $idPrevisita, $documento);
        }

        foreach ($archivosNuevos as $archivo) {
            guardarDocumentoPrevisitaEnConexion($db, $idPrevisita, $idUsuario, $archivo);
        }

        if (tabla_existe($db, tablaDocumentosPrevisita())) {
            sincronizarColumnaLegacyDocumentoPrevisitaEnConexion($db, $idPrevisita);

            if (columna_existe($db, 'previsitas', 'doc_previsita')) {
                $sqlLegacy = "SELECT doc_previsita FROM previsitas WHERE id_previsita = ? LIMIT 1";
                $stmtLegacy = stmt_or_throw($db, $sqlLegacy);
                mysqli_stmt_bind_param($stmtLegacy, 'i', $idPrevisita);
                mysqli_stmt_execute($stmtLegacy);
                $resultLegacy = mysqli_stmt_get_result($stmtLegacy);
                $legacyData = $resultLegacy ? (mysqli_fetch_assoc($resultLegacy) ?: []) : [];
                mysqli_stmt_close($stmtLegacy);
            }
        }

        return listarDocumentosPrevisitaEnConexion($db, $idPrevisita, $legacyData);
    }
}
