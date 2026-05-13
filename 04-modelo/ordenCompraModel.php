<?php

require_once __DIR__ . '/schemaIntrospectionModel.php';
require_once __DIR__ . '/presupuestoIntervencionesModel.php';

if (!function_exists('tablaOrdenesCompraExiste')) {
    function tablaOrdenesCompraExiste(mysqli $db): bool
    {
        return tabla_existe($db, 'ordenes_compra');
    }
}

if (!function_exists('normalizarEstadoOrdenCompraPersistida')) {
    function normalizarEstadoOrdenCompraPersistida(?string $estado): string
    {
        $estado = strtolower(trim((string)$estado));
        $permitidos = ['cargada', 'observada', 'anulada'];

        return in_array($estado, $permitidos, true) ? $estado : 'cargada';
    }
}

if (!function_exists('esEstadoOrdenCompraActiva')) {
    function esEstadoOrdenCompraActiva(?string $estado): bool
    {
        return in_array(normalizarEstadoOrdenCompraPersistida($estado), ['cargada', 'observada'], true);
    }
}

if (!function_exists('ordenCompraTablaTieneColumnasMinimas')) {
    function ordenCompraTablaTieneColumnasMinimas(mysqli $db): bool
    {
        return tablaOrdenesCompraExiste($db)
            && columna_existe($db, 'ordenes_compra', 'id_orden_compra')
            && columna_existe($db, 'ordenes_compra', 'id_presupuesto')
            && columna_existe($db, 'ordenes_compra', 'estado')
            && columna_existe($db, 'ordenes_compra', 'pdf_nombre_archivo')
            && columna_existe($db, 'ordenes_compra', 'pdf_ruta_archivo')
            && columna_existe($db, 'ordenes_compra', 'pdf_mime_type')
            && columna_existe($db, 'ordenes_compra', 'pdf_tamano_bytes')
            && columna_existe($db, 'ordenes_compra', 'created_at')
            && columna_existe($db, 'ordenes_compra', 'updated_at');
    }
}

if (!function_exists('obtenerOrdenCompraActivaPorPresupuestoEnConexion')) {
    function obtenerOrdenCompraActivaPorPresupuestoEnConexion(mysqli $db, int $idPresupuesto): ?array
    {
        if ($idPresupuesto <= 0 || !ordenCompraTablaTieneColumnasMinimas($db)) {
            return null;
        }

        $sql = "
            SELECT *
            FROM ordenes_compra
            WHERE id_presupuesto = ?
              AND LOWER(TRIM(COALESCE(estado, ''))) IN ('cargada', 'observada')
            ORDER BY updated_at DESC, created_at DESC, id_orden_compra DESC
            LIMIT 1
        ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, 'i', $idPresupuesto);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            return null;
        }

        $row['estado'] = normalizarEstadoOrdenCompraPersistida($row['estado'] ?? 'cargada');

        return $row;
    }
}

if (!function_exists('existeOrdenCompraActivaPorPresupuestoEnConexion')) {
    function existeOrdenCompraActivaPorPresupuestoEnConexion(mysqli $db, int $idPresupuesto): bool
    {
        return obtenerOrdenCompraActivaPorPresupuestoEnConexion($db, $idPresupuesto) !== null;
    }
}

if (!function_exists('obtenerOrdenCompraPorIdEnConexion')) {
    function obtenerOrdenCompraPorIdEnConexion(mysqli $db, int $idOrdenCompra): ?array
    {
        if ($idOrdenCompra <= 0 || !ordenCompraTablaTieneColumnasMinimas($db)) {
            return null;
        }

        $sql = "
            SELECT *
            FROM ordenes_compra
            WHERE id_orden_compra = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, 'i', $idOrdenCompra);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            return null;
        }

        $row['estado'] = normalizarEstadoOrdenCompraPersistida($row['estado'] ?? 'cargada');

        return $row;
    }
}

if (!function_exists('listarOrdenesCompraPorPresupuestoEnConexion')) {
    function listarOrdenesCompraPorPresupuestoEnConexion(mysqli $db, int $idPresupuesto): array
    {
        if ($idPresupuesto <= 0 || !ordenCompraTablaTieneColumnasMinimas($db)) {
            return [];
        }

        $sql = "
            SELECT *
            FROM ordenes_compra
            WHERE id_presupuesto = ?
            ORDER BY created_at DESC, id_orden_compra DESC
        ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param($stmt, 'i', $idPresupuesto);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $row['estado'] = normalizarEstadoOrdenCompraPersistida($row['estado'] ?? 'cargada');
                $rows[] = $row;
            }
        }
        mysqli_stmt_close($stmt);

        return $rows;
    }
}

if (!function_exists('obtenerOrdenCompraActivaPorPresupuesto')) {
    function obtenerOrdenCompraActivaPorPresupuesto(mysqli $db, int $idPresupuesto): ?array
    {
        return obtenerOrdenCompraActivaPorPresupuestoEnConexion($db, $idPresupuesto);
    }
}

if (!function_exists('existeOrdenCompraActivaPorPresupuesto')) {
    function existeOrdenCompraActivaPorPresupuesto(mysqli $db, int $idPresupuesto): bool
    {
        return existeOrdenCompraActivaPorPresupuestoEnConexion($db, $idPresupuesto);
    }
}

if (!function_exists('obtenerOrdenCompraPorId')) {
    function obtenerOrdenCompraPorId(mysqli $db, int $idOrdenCompra): ?array
    {
        return obtenerOrdenCompraPorIdEnConexion($db, $idOrdenCompra);
    }
}

if (!function_exists('listarOrdenesCompraPorPresupuesto')) {
    function listarOrdenesCompraPorPresupuesto(mysqli $db, int $idPresupuesto): array
    {
        return listarOrdenesCompraPorPresupuestoEnConexion($db, $idPresupuesto);
    }
}

if (!function_exists('validarEstadoOrdenCompraPersistible')) {
    function validarEstadoOrdenCompraPersistible(?string $estado): bool
    {
        $estado = strtolower(trim((string)$estado));

        return in_array($estado, ['cargada', 'observada', 'anulada'], true);
    }
}

if (!function_exists('normalizarTextoOrdenCompra')) {
    function normalizarTextoOrdenCompra($valor, ?int $maxLength = null): ?string
    {
        if (is_array($valor)) {
            return null;
        }

        $valor = trim((string)$valor);
        if ($valor === '') {
            return null;
        }

        return $maxLength !== null ? substr($valor, 0, $maxLength) : $valor;
    }
}

if (!function_exists('normalizarBooleanoOrdenCompra')) {
    function normalizarBooleanoOrdenCompra($valor): int
    {
        if (is_bool($valor)) {
            return $valor ? 1 : 0;
        }

        $valor = strtolower(trim((string)$valor));

        return in_array($valor, ['1', 'true', 'on', 'si', 'yes'], true) ? 1 : 0;
    }
}

if (!function_exists('normalizarDecimalOrdenCompra')) {
    function normalizarDecimalOrdenCompra($valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_string($valor)) {
            $valor = str_replace(',', '.', trim($valor));
        }

        return is_numeric($valor) ? (float)$valor : null;
    }
}

if (!function_exists('normalizarFechaOrdenCompra')) {
    function normalizarFechaOrdenCompra($valor): ?string
    {
        $valor = normalizarTextoOrdenCompra($valor, 10);

        return $valor;
    }
}

if (!function_exists('rutaBaseOrdenCompraPdf')) {
    function rutaBaseOrdenCompraPdf(int $idPresupuesto): string
    {
        $root = realpath(__DIR__ . '/..');
        if ($root === false) {
            $root = __DIR__ . '/..';
        }

        return rtrim($root, '/\\') . '/uploads/presupuestos/' . max(0, $idPresupuesto) . '/ordenes_compra/';
    }
}

if (!function_exists('rutaRelativaOrdenCompraPdfDesdeAbsoluta')) {
    function rutaRelativaOrdenCompraPdfDesdeAbsoluta(string $rutaAbsoluta): string
    {
        $rutaAbsoluta = str_replace('\\', '/', $rutaAbsoluta);
        $pos = strpos($rutaAbsoluta, '/uploads/');
        if ($pos !== false) {
            return ltrim(substr($rutaAbsoluta, $pos), '/');
        }

        return basename($rutaAbsoluta);
    }
}

if (!function_exists('rutaAbsolutaOrdenCompraPdfDesdeRelativa')) {
    function rutaAbsolutaOrdenCompraPdfDesdeRelativa(string $rutaRelativa): string
    {
        $rutaRelativa = str_replace('\\', '/', trim($rutaRelativa));
        if ($rutaRelativa === '') {
            return '';
        }

        $root = realpath(__DIR__ . '/..');
        if ($root === false) {
            $root = __DIR__ . '/..';
        }

        if (preg_match('~^/?uploads/~', $rutaRelativa)) {
            return rtrim($root, '/\\') . '/' . ltrim($rutaRelativa, '/');
        }

        return $rutaRelativa;
    }
}

if (!function_exists('rutaOrdenCompraPdfDentroDeBase')) {
    function rutaOrdenCompraPdfDentroDeBase(string $rutaAbsoluta, int $idPresupuesto): bool
    {
        $rutaAbsoluta = str_replace('\\', '/', $rutaAbsoluta);
        $base = str_replace('\\', '/', rtrim(rutaBaseOrdenCompraPdf($idPresupuesto), '/\\')) . '/';

        return $rutaAbsoluta !== '' && strpos($rutaAbsoluta, $base) === 0;
    }
}

if (!function_exists('sanitizarNombreArchivoOrdenCompraPdf')) {
    function sanitizarNombreArchivoOrdenCompraPdf(string $nombre): string
    {
        $base = trim((string)pathinfo($nombre, PATHINFO_FILENAME));
        $base = preg_replace('/[\\\\\/:*?"<>|]+/', '', $base);
        $base = preg_replace('/[\r\n\t]+/', ' ', $base);
        $base = preg_replace('/\s+/', ' ', $base);
        $base = trim($base, " .");
        if ($base === '') {
            $base = 'orden-compra';
        }

        if (function_exists('slugify')) {
            $base = slugify($base);
        } else {
            $base = preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower($base));
            $base = trim((string)$base, '-_');
        }

        return $base !== '' ? $base : 'orden-compra';
    }
}

if (!function_exists('resolverRutaOrdenCompraPdfDisponible')) {
    function resolverRutaOrdenCompraPdfDisponible(string $directorio, string $nombreOriginal): array
    {
        $base = sanitizarNombreArchivoOrdenCompraPdf($nombreOriginal);
        $nombreArchivo = $base . '.pdf';
        $rutaAbsoluta = rtrim($directorio, '/\\') . '/' . $nombreArchivo;
        $contador = 1;

        while (file_exists($rutaAbsoluta)) {
            $contador++;
            $nombreArchivo = sprintf('%s_%02d.pdf', $base, $contador);
            $rutaAbsoluta = rtrim($directorio, '/\\') . '/' . $nombreArchivo;
        }

        return [
            'nombre_archivo' => $nombreArchivo,
            'ruta_absoluta' => $rutaAbsoluta,
        ];
    }
}

if (!function_exists('formatearTamanoOrdenCompraPdf')) {
    function formatearTamanoOrdenCompraPdf(?int $bytes): string
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

if (!function_exists('ordenCompraTienePdf')) {
    function ordenCompraTienePdf(?array $ordenCompra): bool
    {
        if (!$ordenCompra) {
            return false;
        }

        return trim((string)($ordenCompra['pdf_ruta_archivo'] ?? '')) !== ''
            && trim((string)($ordenCompra['pdf_nombre_archivo'] ?? '')) !== '';
    }
}

if (!function_exists('validarArchivoPdfOrdenCompra')) {
    function validarArchivoPdfOrdenCompra(array $archivo): array
    {
        if (empty($archivo) || !isset($archivo['tmp_name'])) {
            throw new RuntimeException('El PDF de la OC es obligatorio.');
        }

        $error = (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('El PDF de la OC es obligatorio.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo recibir el PDF de la OC.');
        }

        $tmp = (string)($archivo['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('El PDF de la OC no es valido.');
        }

        $size = (int)($archivo['size'] ?? 0);
        if ($size <= 0) {
            throw new RuntimeException('El PDF de la OC esta vacio.');
        }

        if ($size > 5 * 1024 * 1024) {
            throw new RuntimeException('El PDF de la OC debe pesar como maximo 5 MB.');
        }

        $nombreOriginal = trim((string)($archivo['name'] ?? ''));
        if (strtolower((string)pathinfo($nombreOriginal, PATHINFO_EXTENSION)) !== 'pdf') {
            throw new RuntimeException('El archivo de OC debe tener extension .pdf.');
        }

        $firma = '';
        $fh = @fopen($tmp, 'rb');
        if ($fh !== false) {
            $firma = (string)fread($fh, 4);
            fclose($fh);
        }

        if ($firma !== '%PDF') {
            throw new RuntimeException('El archivo recibido no tiene formato PDF valido.');
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

        if ($mime !== 'application/pdf') {
            throw new RuntimeException('El MIME real del archivo de OC no corresponde a PDF.');
        }

        return [
            'tmp_name' => $tmp,
            'size' => $size,
            'mime_type' => $mime,
            'original_name' => $nombreOriginal,
        ];
    }
}

if (!function_exists('guardarArchivoPdfOrdenCompra')) {
    function guardarArchivoPdfOrdenCompra(int $idPresupuesto, array $archivoPdf, string $numeroOc = ''): array
    {
        if ($idPresupuesto <= 0) {
            throw new RuntimeException('Presupuesto invalido para guardar PDF de OC.');
        }

        $archivoValidado = validarArchivoPdfOrdenCompra($archivoPdf);
        $directorio = rutaBaseOrdenCompraPdf($idPresupuesto);
        if (!function_exists('asegurarDir')) {
            throw new RuntimeException('No esta disponible el helper para crear directorios.');
        }

        asegurarDir($directorio);
        $nombrePreferido = trim($numeroOc) !== ''
            ? 'OC_' . $numeroOc
            : ($archivoValidado['original_name'] ?: 'orden-compra.pdf');
        $destino = resolverRutaOrdenCompraPdfDisponible($directorio, $nombrePreferido);

        if (!@move_uploaded_file($archivoValidado['tmp_name'], $destino['ruta_absoluta'])) {
            throw new RuntimeException('No se pudo guardar el PDF de la OC en el servidor.');
        }

        $tamanoBytes = filesize($destino['ruta_absoluta']);
        if ($tamanoBytes === false) {
            $tamanoBytes = (int)$archivoValidado['size'];
        }

        return [
            'pdf_nombre_archivo' => $destino['nombre_archivo'],
            'pdf_ruta_archivo' => rutaRelativaOrdenCompraPdfDesdeAbsoluta($destino['ruta_absoluta']),
            'pdf_mime_type' => $archivoValidado['mime_type'],
            'pdf_tamano_bytes' => (int)$tamanoBytes,
            'ruta_absoluta' => $destino['ruta_absoluta'],
        ];
    }
}

if (!function_exists('eliminarArchivoPdfOrdenCompraSiCorresponde')) {
    function eliminarArchivoPdfOrdenCompraSiCorresponde(?array $ordenCompra): void
    {
        if (!$ordenCompra || empty($ordenCompra['pdf_ruta_archivo']) || empty($ordenCompra['id_presupuesto'])) {
            return;
        }

        $rutaAbsoluta = rutaAbsolutaOrdenCompraPdfDesdeRelativa((string)$ordenCompra['pdf_ruta_archivo']);
        if (
            $rutaAbsoluta !== ''
            && file_exists($rutaAbsoluta)
            && rutaOrdenCompraPdfDentroDeBase($rutaAbsoluta, (int)$ordenCompra['id_presupuesto'])
        ) {
            @unlink($rutaAbsoluta);
        }
    }
}

if (!function_exists('normalizarDatosOrdenCompra')) {
    function normalizarDatosOrdenCompra(array $input): array
    {
        $estado = strtolower(trim((string)($input['estado'] ?? 'cargada')));

        return [
            'id_presupuesto' => isset($input['id_presupuesto']) ? (int)$input['id_presupuesto'] : 0,
            'id_previsita' => isset($input['id_previsita']) ? (int)$input['id_previsita'] : 0,
            'cliente_snapshot' => normalizarTextoOrdenCompra($input['cliente_snapshot'] ?? null),
            'numero_oc' => normalizarTextoOrdenCompra($input['numero_oc'] ?? null, 100),
            'fecha_emision' => normalizarFechaOrdenCompra($input['fecha_emision'] ?? null),
            'proveedor' => normalizarTextoOrdenCompra($input['proveedor'] ?? null, 150),
            'moneda' => strtoupper((string)(normalizarTextoOrdenCompra($input['moneda'] ?? 'ARS', 10) ?? 'ARS')),
            'monto_neto' => normalizarDecimalOrdenCompra($input['monto_neto'] ?? null),
            'iva_incluido' => normalizarBooleanoOrdenCompra($input['iva_incluido'] ?? 0),
            'total' => normalizarDecimalOrdenCompra($input['total'] ?? null),
            'estado' => $estado,
            'condicion_pago' => normalizarTextoOrdenCompra($input['condicion_pago'] ?? null),
            'requiere_anticipo' => normalizarBooleanoOrdenCompra($input['requiere_anticipo'] ?? 0),
            'anticipo_tipo' => ($anticipoTipo = normalizarTextoOrdenCompra($input['anticipo_tipo'] ?? null, 20)) !== null
                ? strtolower($anticipoTipo)
                : null,
            'anticipo_valor' => normalizarDecimalOrdenCompra($input['anticipo_valor'] ?? null),
            'condicion_saldo' => normalizarTextoOrdenCompra($input['condicion_saldo'] ?? null),
            'observaciones_comerciales' => normalizarTextoOrdenCompra($input['observaciones_comerciales'] ?? null),
            'direccion_entrega' => normalizarTextoOrdenCompra($input['direccion_entrega'] ?? null),
            'sucursal_planta_sede' => normalizarTextoOrdenCompra($input['sucursal_planta_sede'] ?? null, 255),
            'fecha_entrega_prevista' => normalizarFechaOrdenCompra($input['fecha_entrega_prevista'] ?? null),
            'contacto_sitio' => normalizarTextoOrdenCompra($input['contacto_sitio'] ?? null, 255),
            'contacto_sitio_email' => normalizarTextoOrdenCompra($input['contacto_sitio_email'] ?? null, 255),
            'contacto_sitio_telefono' => normalizarTextoOrdenCompra($input['contacto_sitio_telefono'] ?? null, 100),
            'email_facturacion' => normalizarTextoOrdenCompra($input['email_facturacion'] ?? null, 255),
            'area_facturacion' => normalizarTextoOrdenCompra($input['area_facturacion'] ?? null, 255),
            'factura_menciona_oc' => normalizarBooleanoOrdenCompra($input['factura_menciona_oc'] ?? 1),
            'factura_menciona_destino' => normalizarBooleanoOrdenCompra($input['factura_menciona_destino'] ?? 0),
            'instrucciones_facturacion' => normalizarTextoOrdenCompra($input['instrucciones_facturacion'] ?? null),
            'requiere_documentacion_seguridad' => normalizarBooleanoOrdenCompra($input['requiere_documentacion_seguridad'] ?? 0),
            'contactos_ingreso' => normalizarTextoOrdenCompra($input['contactos_ingreso'] ?? null),
            'estado_documentacion_seguridad' => normalizarTextoOrdenCompra($input['estado_documentacion_seguridad'] ?? null, 50),
            'observaciones_seguridad' => normalizarTextoOrdenCompra($input['observaciones_seguridad'] ?? null),
            'pdf_nombre_archivo' => normalizarTextoOrdenCompra($input['pdf_nombre_archivo'] ?? null, 255),
            'pdf_ruta_archivo' => normalizarTextoOrdenCompra($input['pdf_ruta_archivo'] ?? null),
            'pdf_mime_type' => normalizarTextoOrdenCompra($input['pdf_mime_type'] ?? null, 100),
            'pdf_tamano_bytes' => isset($input['pdf_tamano_bytes']) && $input['pdf_tamano_bytes'] !== ''
                ? max(0, (int)$input['pdf_tamano_bytes'])
                : null,
            'id_usuario_alta' => isset($input['id_usuario_alta']) ? (int)$input['id_usuario_alta'] : null,
            'id_usuario_modificacion' => isset($input['id_usuario_modificacion']) ? (int)$input['id_usuario_modificacion'] : null,
        ];
    }
}

if (!function_exists('fechaOrdenCompraValida')) {
    function fechaOrdenCompraValida(?string $fecha): bool
    {
        if ($fecha === null || $fecha === '') {
            return true;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }

        [$anio, $mes, $dia] = array_map('intval', explode('-', $fecha));

        return checkdate($mes, $dia, $anio);
    }
}

if (!function_exists('validarDatosOrdenCompra')) {
    function validarDatosOrdenCompra(array $datos, bool $esCreacion): array
    {
        $errores = [];

        if (($datos['id_presupuesto'] ?? 0) <= 0) {
            $errores['id_presupuesto'] = 'El presupuesto es obligatorio.';
        }

        if (($datos['id_previsita'] ?? 0) <= 0) {
            $errores['id_previsita'] = 'La previsita es obligatoria.';
        }

        if ($esCreacion || array_key_exists('numero_oc', $datos)) {
            if (($datos['numero_oc'] ?? null) === null) {
                $errores['numero_oc'] = 'El numero de OC es obligatorio.';
            }
        }

        if ($esCreacion || array_key_exists('fecha_emision', $datos)) {
            if (($datos['fecha_emision'] ?? null) === null) {
                $errores['fecha_emision'] = 'La fecha de emision es obligatoria.';
            }
        }

        if ($esCreacion || array_key_exists('moneda', $datos)) {
            if (trim((string)($datos['moneda'] ?? '')) === '') {
                $errores['moneda'] = 'La moneda es obligatoria.';
            }
        }

        if (!validarEstadoOrdenCompraPersistible($datos['estado'] ?? null)) {
            $errores['estado'] = 'El estado de la OC no es valido.';
        }

        foreach (['fecha_emision', 'fecha_entrega_prevista'] as $campoFecha) {
            if (!fechaOrdenCompraValida($datos[$campoFecha] ?? null)) {
                $errores[$campoFecha] = 'La fecha debe tener formato AAAA-MM-DD.';
            }
        }

        foreach (['monto_neto', 'total', 'anticipo_valor'] as $campoDecimal) {
            if (($datos[$campoDecimal] ?? null) !== null && (!is_numeric($datos[$campoDecimal]) || (float)$datos[$campoDecimal] < 0)) {
                $errores[$campoDecimal] = 'El valor debe ser numerico y mayor o igual a cero.';
            }
        }

        foreach (['contacto_sitio_email', 'email_facturacion'] as $campoEmail) {
            if (($datos[$campoEmail] ?? null) !== null && !filter_var($datos[$campoEmail], FILTER_VALIDATE_EMAIL)) {
                $errores[$campoEmail] = 'El email informado no es valido.';
            }
        }

        if (($datos['anticipo_tipo'] ?? null) !== null && !in_array($datos['anticipo_tipo'], ['porcentaje', 'monto'], true)) {
            $errores['anticipo_tipo'] = 'El tipo de anticipo no es valido.';
        }

        return $errores;
    }
}

if (!function_exists('camposInsertOrdenCompra')) {
    function camposInsertOrdenCompra(): array
    {
        return [
            'id_presupuesto' => 'i',
            'id_previsita' => 'i',
            'cliente_snapshot' => 's',
            'numero_oc' => 's',
            'fecha_emision' => 's',
            'proveedor' => 's',
            'moneda' => 's',
            'monto_neto' => 'd',
            'iva_incluido' => 'i',
            'total' => 'd',
            'estado' => 's',
            'condicion_pago' => 's',
            'requiere_anticipo' => 'i',
            'anticipo_tipo' => 's',
            'anticipo_valor' => 'd',
            'condicion_saldo' => 's',
            'observaciones_comerciales' => 's',
            'direccion_entrega' => 's',
            'sucursal_planta_sede' => 's',
            'fecha_entrega_prevista' => 's',
            'contacto_sitio' => 's',
            'contacto_sitio_email' => 's',
            'contacto_sitio_telefono' => 's',
            'email_facturacion' => 's',
            'area_facturacion' => 's',
            'factura_menciona_oc' => 'i',
            'factura_menciona_destino' => 'i',
            'instrucciones_facturacion' => 's',
            'requiere_documentacion_seguridad' => 'i',
            'contactos_ingreso' => 's',
            'estado_documentacion_seguridad' => 's',
            'observaciones_seguridad' => 's',
            'pdf_nombre_archivo' => 's',
            'pdf_ruta_archivo' => 's',
            'pdf_mime_type' => 's',
            'pdf_tamano_bytes' => 'i',
            'id_usuario_alta' => 'i',
            'id_usuario_modificacion' => 'i',
        ];
    }
}

if (!function_exists('camposUpdateOrdenCompra')) {
    function camposUpdateOrdenCompra(): array
    {
        $campos = camposInsertOrdenCompra();
        unset($campos['id_presupuesto'], $campos['id_previsita'], $campos['estado'], $campos['id_usuario_alta']);

        return $campos;
    }
}

if (!function_exists('bindParametrosOrdenCompra')) {
    function bindParametrosOrdenCompra(mysqli_stmt $stmt, string $types, array &$values): bool
    {
        $refs = [&$types];
        foreach ($values as $key => &$value) {
            $refs[] = &$value;
        }

        return (bool)call_user_func_array([$stmt, 'bind_param'], $refs);
    }
}

if (!function_exists('crearOrdenCompraEnConexion')) {
    function crearOrdenCompraEnConexion(mysqli $db, array $datos)
    {
        if (!ordenCompraTablaTieneColumnasMinimas($db)) {
            return false;
        }

        $campos = camposInsertOrdenCompra();
        $columnas = array_keys($campos);
        $placeholders = implode(', ', array_fill(0, count($columnas), '?'));
        $sql = 'INSERT INTO ordenes_compra (' . implode(', ', $columnas) . ') VALUES (' . $placeholders . ')';
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return false;
        }

        $types = implode('', array_values($campos));
        $values = [];
        foreach ($columnas as $columna) {
            $values[] = $datos[$columna] ?? null;
        }

        if (!bindParametrosOrdenCompra($stmt, $types, $values)) {
            mysqli_stmt_close($stmt);
            return false;
        }

        $ok = mysqli_stmt_execute($stmt);
        $id = $ok ? (int)mysqli_insert_id($db) : false;
        mysqli_stmt_close($stmt);

        return $id > 0 ? $id : false;
    }
}

if (!function_exists('actualizarOrdenCompraEnConexion')) {
    function actualizarOrdenCompraEnConexion(mysqli $db, int $idOrdenCompra, array $datos): bool
    {
        if ($idOrdenCompra <= 0 || !ordenCompraTablaTieneColumnasMinimas($db)) {
            return false;
        }

        $campos = camposUpdateOrdenCompra();
        $asignaciones = [];
        foreach (array_keys($campos) as $columna) {
            $asignaciones[] = "{$columna} = ?";
        }

        $sql = 'UPDATE ordenes_compra SET ' . implode(', ', $asignaciones) . ' WHERE id_orden_compra = ? LIMIT 1';
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return false;
        }

        $types = implode('', array_values($campos)) . 'i';
        $values = [];
        foreach (array_keys($campos) as $columna) {
            $values[] = $datos[$columna] ?? null;
        }
        $values[] = $idOrdenCompra;

        if (!bindParametrosOrdenCompra($stmt, $types, $values)) {
            mysqli_stmt_close($stmt);
            return false;
        }

        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return (bool)$ok;
    }
}

if (!function_exists('cambiarEstadoOrdenCompraEnConexion')) {
    function cambiarEstadoOrdenCompraEnConexion(mysqli $db, int $idOrdenCompra, string $estado, ?int $idUsuario): bool
    {
        if ($idOrdenCompra <= 0 || !validarEstadoOrdenCompraPersistible($estado) || !ordenCompraTablaTieneColumnasMinimas($db)) {
            return false;
        }

        $estado = strtolower(trim($estado));
        $stmt = mysqli_prepare($db, "
            UPDATE ordenes_compra
            SET estado = ?, id_usuario_modificacion = ?
            WHERE id_orden_compra = ?
            LIMIT 1
        ");
        if (!$stmt) {
            return false;
        }

        $idUsuario = $idUsuario ?: null;
        mysqli_stmt_bind_param($stmt, 'sii', $estado, $idUsuario, $idOrdenCompra);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return (bool)$ok;
    }
}

if (!function_exists('existeOtraOrdenCompraActivaPorPresupuestoEnConexion')) {
    function existeOtraOrdenCompraActivaPorPresupuestoEnConexion(mysqli $db, int $idPresupuesto, int $idOrdenCompraExcluir): bool
    {
        if ($idPresupuesto <= 0 || !ordenCompraTablaTieneColumnasMinimas($db)) {
            return false;
        }

        $stmt = mysqli_prepare($db, "
            SELECT 1
            FROM ordenes_compra
            WHERE id_presupuesto = ?
              AND id_orden_compra <> ?
              AND LOWER(TRIM(COALESCE(estado, ''))) IN ('cargada', 'observada')
            LIMIT 1
        ");
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'ii', $idPresupuesto, $idOrdenCompraExcluir);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $existe = (bool)($res && mysqli_fetch_row($res));
        mysqli_stmt_close($stmt);

        return $existe;
    }
}

if (!function_exists('obtenerPresupuestoBasicoParaOrdenCompra')) {
    function obtenerPresupuestoBasicoParaOrdenCompra(mysqli $db, int $idPresupuesto): ?array
    {
        if ($idPresupuesto <= 0 || !tabla_existe($db, 'presupuestos')) {
            return null;
        }

        $tienePrevisitas = tabla_existe($db, 'previsitas');
        $tieneHistorialComercial = tabla_existe($db, 'presupuesto_historial_comercial');
        $tieneEstadoComercialSimulacion = columna_existe($db, 'presupuestos', 'estado_comercial_simulacion');
        $tieneEstadoComercialSmtp = columna_existe($db, 'presupuestos', 'estado_comercial_smtp');

        $selectEstadoComercialSimulacion = $tieneEstadoComercialSimulacion
            ? 'p.estado_comercial_simulacion'
            : "'' AS estado_comercial_simulacion";
        $selectEstadoComercialSmtp = $tieneEstadoComercialSmtp
            ? 'p.estado_comercial_smtp'
            : "'' AS estado_comercial_smtp";

        $selectPrevisita = $tienePrevisitas
            ? "v.razon_social, v.cuit, v.estado_visita"
            : "'' AS razon_social, '' AS cuit, '' AS estado_visita";
        $joinPrevisita = $tienePrevisitas
            ? 'LEFT JOIN previsitas v ON v.id_previsita = p.id_previsita'
            : '';

        $joinHistorialComercial = $tieneHistorialComercial
            ? "
                LEFT JOIN (
                    SELECT
                        id_presupuesto,
                        MAX(CASE WHEN modo_circuito = 'simulacion' THEN id_historial_comercial ELSE 0 END) AS ultimo_historial_comercial_simulacion,
                        MAX(CASE WHEN modo_circuito = 'smtp' THEN id_historial_comercial ELSE 0 END) AS ultimo_historial_comercial_smtp
                    FROM presupuesto_historial_comercial
                    GROUP BY id_presupuesto
                ) hc ON hc.id_presupuesto = p.id_presupuesto
                LEFT JOIN presupuesto_historial_comercial hcs ON hcs.id_historial_comercial = hc.ultimo_historial_comercial_simulacion
                LEFT JOIN presupuesto_historial_comercial hcm ON hcm.id_historial_comercial = hc.ultimo_historial_comercial_smtp
            "
            : '';
        $selectHistorialComercial = $tieneHistorialComercial
            ? "
                COALESCE(hcs.estado_resultante, '') AS ultimo_estado_historial_comercial_simulacion,
                COALESCE(hcm.estado_resultante, '') AS ultimo_estado_historial_comercial_smtp
            "
            : "
                '' AS ultimo_estado_historial_comercial_simulacion,
                '' AS ultimo_estado_historial_comercial_smtp
            ";

        $sql = "
            SELECT
                p.id_presupuesto,
                p.id_previsita,
                p.estado,
                {$selectEstadoComercialSimulacion},
                {$selectEstadoComercialSmtp},
                {$selectPrevisita},
                {$selectHistorialComercial}
            FROM presupuestos p
            {$joinPrevisita}
            {$joinHistorialComercial}
            WHERE p.id_presupuesto = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, 'i', $idPresupuesto);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            return null;
        }

        $modo = function_exists('obtenerModoActivoCircuitoComercialPresupuestos')
            ? obtenerModoActivoCircuitoComercialPresupuestos()
            : 'simulacion';
        $estadoComercial = function_exists('obtenerEstadoComercialActivoDesdePresupuesto')
            ? obtenerEstadoComercialActivoDesdePresupuesto($row, $modo)
            : '';
        $razonSocial = trim((string)($row['razon_social'] ?? ''));
        $cuit = trim((string)($row['cuit'] ?? ''));
        $clienteSnapshot = trim($razonSocial . ($cuit !== '' ? ' - CUIT ' . $cuit : ''));

        return [
            'id_presupuesto' => (int)($row['id_presupuesto'] ?? 0),
            'id_previsita' => (int)($row['id_previsita'] ?? 0),
            'estado' => (string)($row['estado'] ?? ''),
            'estado_comercial_activo' => $estadoComercial,
            'cliente_snapshot' => $clienteSnapshot !== '' ? $clienteSnapshot : null,
            'razon_social' => $razonSocial,
            'cuit' => $cuit,
            'estado_visita' => (string)($row['estado_visita'] ?? ''),
        ];
    }
}
