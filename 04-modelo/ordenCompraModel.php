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

if (!function_exists('columnasAdicionalesOrdenCompra')) {
    function columnasAdicionalesOrdenCompra(): array
    {
        return [
            'proveedor_nombre_fantasia',
            'proveedor_direccion_fiscal',
            'direccion_obra_alternativa',
            'contacto_compras',
            'contacto_compras_email',
            'contacto_compras_telefono',
            'contacto_obra_mantenimiento',
            'contacto_obra_mantenimiento_email',
            'contacto_obra_mantenimiento_telefono',
            'portal_facturacion_url',
            'portal_ingreso_obra_url',
            'requiere_caucion',
            'requiere_poliza_rc',
            'poliza_rc_detalle',
        ];
    }
}

if (!function_exists('ordenCompraTablaTieneColumnasAdicionales')) {
    function ordenCompraTablaTieneColumnasAdicionales(mysqli $db): bool
    {
        if (!tablaOrdenesCompraExiste($db)) {
            return false;
        }

        foreach (columnasAdicionalesOrdenCompra() as $columna) {
            if (!columna_existe($db, 'ordenes_compra', $columna)) {
                return false;
            }
        }

        return true;
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

if (!function_exists('formatearFechaIntervencionOrdenCompra')) {
    function formatearFechaIntervencionOrdenCompra(?string $fecha): string
    {
        if (function_exists('formatearFechaIntervencionPresupuesto')) {
            return formatearFechaIntervencionPresupuesto($fecha);
        }

        if (!$fecha) {
            return '-';
        }

        $dt = date_create($fecha);
        return $dt ? $dt->format('d/m/Y H:i:s') : $fecha;
    }
}

if (!function_exists('escIntervencionOrdenCompra')) {
    function escIntervencionOrdenCompra(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('obtenerNombreUsuarioOrdenCompraEnConexion')) {
    function obtenerNombreUsuarioOrdenCompraEnConexion(mysqli $db, ?int $idUsuario): string
    {
        $idUsuario = (int)$idUsuario;
        if ($idUsuario <= 0) {
            return 'Usuario no informado';
        }

        $stmt = mysqli_prepare($db, 'SELECT apellidos, nombres FROM usuarios WHERE id_usuario = ? LIMIT 1');
        if (!$stmt) {
            return 'Usuario #' . $idUsuario;
        }

        mysqli_stmt_bind_param($stmt, 'i', $idUsuario);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            return 'Usuario #' . $idUsuario;
        }

        $nombre = trim((string)($row['apellidos'] ?? '') . ' ' . (string)($row['nombres'] ?? ''));

        return $nombre !== '' ? $nombre : 'Usuario #' . $idUsuario;
    }
}

if (!function_exists('construirResumenIntervencionesOrdenCompraEnConexion')) {
    function construirResumenIntervencionesOrdenCompraEnConexion(mysqli $db, ?array $ordenCompra): array
    {
        $headerHtml = '<thead><tr><th>Usuario</th><th>Accion</th><th>Fecha</th></tr></thead>';
        $tablaVacia = '<table class="table table-sm mb-0">'
            . $headerHtml
            . '<tbody><tr><td colspan="3" class="text-center text-muted">Sin otras intervenciones</td></tr></tbody>'
            . '</table>';

        if (empty($ordenCompra)) {
            return [
                'ultimo_texto' => 'Sin intervenciones',
                'popover_html' => $tablaVacia,
                'total' => 0,
                'items' => [],
            ];
        }

        $items = [];
        $createdAt = (string)($ordenCompra['created_at'] ?? '');
        $updatedAt = (string)($ordenCompra['updated_at'] ?? '');
        $idAlta = isset($ordenCompra['id_usuario_alta']) ? (int)$ordenCompra['id_usuario_alta'] : 0;
        $idModificacion = isset($ordenCompra['id_usuario_modificacion']) ? (int)$ordenCompra['id_usuario_modificacion'] : 0;

        if ($updatedAt !== '' && ($updatedAt !== $createdAt || ($idModificacion > 0 && $idModificacion !== $idAlta))) {
            $items[] = [
                'usuario_nombre' => obtenerNombreUsuarioOrdenCompraEnConexion($db, $idModificacion),
                'accion_label' => 'Actualizar OC',
                'fecha_texto' => formatearFechaIntervencionOrdenCompra($updatedAt),
            ];
        }

        if ($createdAt !== '') {
            $items[] = [
                'usuario_nombre' => obtenerNombreUsuarioOrdenCompraEnConexion($db, $idAlta),
                'accion_label' => 'Cargar OC',
                'fecha_texto' => formatearFechaIntervencionOrdenCompra($createdAt),
            ];
        }

        if (empty($items)) {
            return [
                'ultimo_texto' => 'Sin intervenciones',
                'popover_html' => $tablaVacia,
                'total' => 0,
                'items' => [],
            ];
        }

        $ultimo = $items[0];
        $ultimoTexto = $ultimo['usuario_nombre']
            . ' | ' . $ultimo['accion_label']
            . ' | ' . $ultimo['fecha_texto'];

        $otros = array_slice($items, 1);
        $html = '<table class="table table-sm mb-0">' . $headerHtml . '<tbody>';
        if ($otros) {
            foreach ($otros as $item) {
                $html .= '<tr>'
                    . '<td>' . escIntervencionOrdenCompra((string)$item['usuario_nombre']) . '</td>'
                    . '<td>' . escIntervencionOrdenCompra((string)$item['accion_label']) . '</td>'
                    . '<td>' . escIntervencionOrdenCompra((string)$item['fecha_texto']) . '</td>'
                    . '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="3" class="text-center text-muted">Sin otras intervenciones</td></tr>';
        }
        $html .= '</tbody></table>';

        return [
            'ultimo_texto' => $ultimoTexto,
            'popover_html' => $html,
            'total' => count($items),
            'items' => $items,
        ];
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

if (!function_exists('urlOrdenCompraValida')) {
    function urlOrdenCompraValida(?string $url): bool
    {
        if ($url === null || $url === '') {
            return true;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
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
            'proveedor_nombre_fantasia' => normalizarTextoOrdenCompra($input['proveedor_nombre_fantasia'] ?? null, 150),
            'proveedor_direccion_fiscal' => normalizarTextoOrdenCompra($input['proveedor_direccion_fiscal'] ?? null),
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
            'direccion_obra_alternativa' => normalizarTextoOrdenCompra($input['direccion_obra_alternativa'] ?? null),
            'sucursal_planta_sede' => normalizarTextoOrdenCompra($input['sucursal_planta_sede'] ?? null, 255),
            'fecha_entrega_prevista' => normalizarFechaOrdenCompra($input['fecha_entrega_prevista'] ?? null),
            'contacto_sitio' => normalizarTextoOrdenCompra($input['contacto_sitio'] ?? null, 255),
            'contacto_sitio_email' => normalizarTextoOrdenCompra($input['contacto_sitio_email'] ?? null, 255),
            'contacto_sitio_telefono' => normalizarTextoOrdenCompra($input['contacto_sitio_telefono'] ?? null, 100),
            'contacto_obra_mantenimiento' => normalizarTextoOrdenCompra($input['contacto_obra_mantenimiento'] ?? null, 255),
            'contacto_obra_mantenimiento_email' => normalizarTextoOrdenCompra($input['contacto_obra_mantenimiento_email'] ?? null, 255),
            'contacto_obra_mantenimiento_telefono' => normalizarTextoOrdenCompra($input['contacto_obra_mantenimiento_telefono'] ?? null, 100),
            'email_facturacion' => normalizarTextoOrdenCompra($input['email_facturacion'] ?? null, 255),
            'area_facturacion' => normalizarTextoOrdenCompra($input['area_facturacion'] ?? null, 255),
            'contacto_compras' => normalizarTextoOrdenCompra($input['contacto_compras'] ?? null, 255),
            'contacto_compras_email' => normalizarTextoOrdenCompra($input['contacto_compras_email'] ?? null, 255),
            'contacto_compras_telefono' => normalizarTextoOrdenCompra($input['contacto_compras_telefono'] ?? null, 100),
            'factura_menciona_oc' => normalizarBooleanoOrdenCompra($input['factura_menciona_oc'] ?? 1),
            'factura_menciona_destino' => normalizarBooleanoOrdenCompra($input['factura_menciona_destino'] ?? 0),
            'instrucciones_facturacion' => normalizarTextoOrdenCompra($input['instrucciones_facturacion'] ?? null),
            'portal_facturacion_url' => normalizarTextoOrdenCompra($input['portal_facturacion_url'] ?? null, 255),
            'requiere_documentacion_seguridad' => normalizarBooleanoOrdenCompra($input['requiere_documentacion_seguridad'] ?? 0),
            'contactos_ingreso' => normalizarTextoOrdenCompra($input['contactos_ingreso'] ?? null),
            'estado_documentacion_seguridad' => normalizarTextoOrdenCompra($input['estado_documentacion_seguridad'] ?? null, 50),
            'observaciones_seguridad' => normalizarTextoOrdenCompra($input['observaciones_seguridad'] ?? null),
            'requiere_caucion' => normalizarBooleanoOrdenCompra($input['requiere_caucion'] ?? 0),
            'requiere_poliza_rc' => normalizarBooleanoOrdenCompra($input['requiere_poliza_rc'] ?? 0),
            'poliza_rc_detalle' => normalizarTextoOrdenCompra($input['poliza_rc_detalle'] ?? null),
            'portal_ingreso_obra_url' => normalizarTextoOrdenCompra($input['portal_ingreso_obra_url'] ?? null, 255),
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
    function validarDatosOrdenCompra(array $datos, bool $esCreacion, bool $validarCamposAdicionales = true): array
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

        $camposEmail = ['contacto_sitio_email', 'email_facturacion'];
        if ($validarCamposAdicionales) {
            $camposEmail[] = 'contacto_obra_mantenimiento_email';
            $camposEmail[] = 'contacto_compras_email';
        }

        foreach ($camposEmail as $campoEmail) {
            if (($datos[$campoEmail] ?? null) !== null && !filter_var($datos[$campoEmail], FILTER_VALIDATE_EMAIL)) {
                $errores[$campoEmail] = 'El email informado no es valido.';
            }
        }

        if ($validarCamposAdicionales) {
            foreach (['portal_facturacion_url', 'portal_ingreso_obra_url'] as $campoUrl) {
                if (!urlOrdenCompraValida($datos[$campoUrl] ?? null)) {
                    $errores[$campoUrl] = 'La URL informada debe ser valida y comenzar con http:// o https://.';
                }
            }
        }

        if (($datos['anticipo_tipo'] ?? null) !== null && !in_array($datos['anticipo_tipo'], ['porcentaje', 'monto'], true)) {
            $errores['anticipo_tipo'] = 'El tipo de anticipo no es valido.';
        }

        return $errores;
    }
}

if (!function_exists('camposAdicionalesOrdenCompra')) {
    function camposAdicionalesOrdenCompra(): array
    {
        return [
            'proveedor_nombre_fantasia' => 's',
            'proveedor_direccion_fiscal' => 's',
            'direccion_obra_alternativa' => 's',
            'contacto_obra_mantenimiento' => 's',
            'contacto_obra_mantenimiento_email' => 's',
            'contacto_obra_mantenimiento_telefono' => 's',
            'contacto_compras' => 's',
            'contacto_compras_email' => 's',
            'contacto_compras_telefono' => 's',
            'portal_facturacion_url' => 's',
            'requiere_caucion' => 'i',
            'requiere_poliza_rc' => 'i',
            'poliza_rc_detalle' => 's',
            'portal_ingreso_obra_url' => 's',
        ];
    }
}

if (!function_exists('camposBaseInsertOrdenCompra')) {
    function camposBaseInsertOrdenCompra(): array
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

if (!function_exists('camposInsertOrdenCompra')) {
    function camposInsertOrdenCompra(?mysqli $db = null): array
    {
        $campos = camposBaseInsertOrdenCompra();

        if ($db === null || ordenCompraTablaTieneColumnasAdicionales($db)) {
            $campos = array_merge($campos, camposAdicionalesOrdenCompra());
        }

        return $campos;
    }
}

if (!function_exists('camposUpdateOrdenCompra')) {
    function camposUpdateOrdenCompra(?mysqli $db = null): array
    {
        $campos = camposInsertOrdenCompra($db);
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

        $campos = camposInsertOrdenCompra($db);
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

        $campos = camposUpdateOrdenCompra($db);
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
            ? "
                v.razon_social,
                v.cuit,
                v.estado_visita,
                v.contacto_obra,
                v.tel_contacto_obra,
                v.email_contacto_obra,
                v.provincia_visita,
                v.partido_visita,
                v.localidad_visita,
                v.calle_visita,
                v.altura_visita,
                v.cp_visita
            "
            : "
                '' AS razon_social,
                '' AS cuit,
                '' AS estado_visita,
                '' AS contacto_obra,
                '' AS tel_contacto_obra,
                '' AS email_contacto_obra,
                '' AS provincia_visita,
                '' AS partido_visita,
                '' AS localidad_visita,
                '' AS calle_visita,
                '' AS altura_visita,
                '' AS cp_visita
            ";
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
            'contacto_obra' => trim((string)($row['contacto_obra'] ?? '')),
            'tel_contacto_obra' => trim((string)($row['tel_contacto_obra'] ?? '')),
            'email_contacto_obra' => trim((string)($row['email_contacto_obra'] ?? '')),
            'provincia_visita' => trim((string)($row['provincia_visita'] ?? '')),
            'partido_visita' => trim((string)($row['partido_visita'] ?? '')),
            'localidad_visita' => trim((string)($row['localidad_visita'] ?? '')),
            'calle_visita' => trim((string)($row['calle_visita'] ?? '')),
            'altura_visita' => trim((string)($row['altura_visita'] ?? '')),
            'cp_visita' => trim((string)($row['cp_visita'] ?? '')),
        ];
    }
}

if (!function_exists('normalizarCuitClienteOrdenCompra')) {
    function normalizarCuitClienteOrdenCompra($cuit): string
    {
        return preg_replace('/\D+/', '', (string)$cuit) ?: '';
    }
}

if (!function_exists('primerValorOrdenCompraCliente')) {
    function primerValorOrdenCompraCliente(...$valores): ?string
    {
        foreach ($valores as $valor) {
            $texto = trim((string)$valor);
            if ($texto !== '') {
                return $texto;
            }
        }

        return null;
    }
}

if (!function_exists('normalizarEmailClienteOrdenCompra')) {
    function normalizarEmailClienteOrdenCompra($email): ?string
    {
        $email = strtolower(trim((string)$email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return normalizarTextoOrdenCompra($email, 255);
    }
}

if (!function_exists('normalizarEnteroNullableClienteOrdenCompra')) {
    function normalizarEnteroNullableClienteOrdenCompra($valor): ?int
    {
        $valor = trim((string)$valor);
        if ($valor === '' || !ctype_digit($valor)) {
            return null;
        }

        return (int)$valor;
    }
}

if (!function_exists('obtenerClientePorCuitNormalizadoEnConexion')) {
    function obtenerClientePorCuitNormalizadoEnConexion(mysqli $db, ?string $cuit): ?array
    {
        $cuitNormalizado = normalizarCuitClienteOrdenCompra($cuit);
        if ($cuitNormalizado === '' || !tabla_existe($db, 'clientes')) {
            return null;
        }

        $sql = "
            SELECT id_cliente, cuit, razon_social, estado
            FROM clientes
            WHERE REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(cuit, ''), '-', ''), '.', ''), ' ', ''), '/', '') = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, 's', $cuitNormalizado);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return $row ?: null;
    }
}

if (!function_exists('prepararDatosClienteDesdeOrdenCompra')) {
    function prepararDatosClienteDesdeOrdenCompra(array $presupuesto, array $datosOrdenCompra, int $idUsuario): array
    {
        $cuit = normalizarCuitClienteOrdenCompra($presupuesto['cuit'] ?? '');
        $razonSocial = normalizarTextoOrdenCompra($presupuesto['razon_social'] ?? '', 255);
        $contactoPrincipal = primerValorOrdenCompraCliente(
            $datosOrdenCompra['contacto_compras'] ?? null,
            $datosOrdenCompra['area_facturacion'] ?? null,
            $datosOrdenCompra['contacto_sitio'] ?? null,
            $datosOrdenCompra['contacto_obra_mantenimiento'] ?? null,
            $presupuesto['contacto_obra'] ?? null
        );
        $telefono = primerValorOrdenCompraCliente(
            $datosOrdenCompra['contacto_compras_telefono'] ?? null,
            $datosOrdenCompra['contacto_sitio_telefono'] ?? null,
            $datosOrdenCompra['contacto_obra_mantenimiento_telefono'] ?? null,
            $presupuesto['tel_contacto_obra'] ?? null
        );
        $email = primerValorOrdenCompraCliente(
            $datosOrdenCompra['email_facturacion'] ?? null,
            $datosOrdenCompra['contacto_compras_email'] ?? null,
            $datosOrdenCompra['contacto_sitio_email'] ?? null,
            $datosOrdenCompra['contacto_obra_mantenimiento_email'] ?? null,
            $presupuesto['email_contacto_obra'] ?? null
        );
        $contactoPago = primerValorOrdenCompraCliente(
            $datosOrdenCompra['area_facturacion'] ?? null,
            $datosOrdenCompra['contacto_compras'] ?? null,
            $contactoPrincipal
        );
        $emailPago = primerValorOrdenCompraCliente(
            $datosOrdenCompra['email_facturacion'] ?? null,
            $datosOrdenCompra['contacto_compras_email'] ?? null,
            $email
        );
        $nota = sprintf(
            'Alta automatica desde OC %s. Presupuesto %d / Previsita %d.',
            trim((string)($datosOrdenCompra['numero_oc'] ?? '')) ?: 'sin numero',
            (int)($presupuesto['id_presupuesto'] ?? 0),
            (int)($presupuesto['id_previsita'] ?? 0)
        );

        return [
            'cuit' => $cuit,
            'razon_social' => $razonSocial ? strtoupper($razonSocial) : null,
            'telefono' => normalizarTextoOrdenCompra($telefono, 255),
            'email' => normalizarEmailClienteOrdenCompra($email),
            'estado' => 'Activo',
            'dirfis_provincia' => normalizarEnteroNullableClienteOrdenCompra($presupuesto['provincia_visita'] ?? null),
            'dirfis_partido' => normalizarEnteroNullableClienteOrdenCompra($presupuesto['partido_visita'] ?? null),
            'dirfis_localidad' => normalizarEnteroNullableClienteOrdenCompra($presupuesto['localidad_visita'] ?? null),
            'dirfis_calle' => normalizarEnteroNullableClienteOrdenCompra($presupuesto['calle_visita'] ?? null),
            'dirfis_altura' => normalizarEnteroNullableClienteOrdenCompra($presupuesto['altura_visita'] ?? null),
            'dirfis_piso' => normalizarTextoOrdenCompra($datosOrdenCompra['dirfis_piso'] ?? $presupuesto['dirfis_piso'] ?? null, 3),
            'dirfis_depto' => normalizarTextoOrdenCompra($datosOrdenCompra['dirfis_depto'] ?? $presupuesto['dirfis_depto'] ?? null, 3),
            'dirfis_cp' => normalizarTextoOrdenCompra($presupuesto['cp_visita'] ?? null, 10),
            'contacto_pri' => normalizarTextoOrdenCompra($contactoPrincipal, 255),
            'contacto_pri_celular' => normalizarTextoOrdenCompra($telefono, 255),
            'contacto_pri_email' => normalizarEmailClienteOrdenCompra($email),
            'contacto_papro' => normalizarTextoOrdenCompra($contactoPago, 255),
            'contacto_papro_celular' => normalizarTextoOrdenCompra($datosOrdenCompra['contacto_compras_telefono'] ?? $telefono, 255),
            'contacto_papro_email' => normalizarEmailClienteOrdenCompra($emailPago),
            'plat_licitacion' => normalizarTextoOrdenCompra($datosOrdenCompra['plat_licitacion'] ?? null, 255),
            'usuario_licitacion' => normalizarTextoOrdenCompra($datosOrdenCompra['usuario_licitacion'] ?? null, 255),
            'pass_licitacion' => normalizarTextoOrdenCompra($datosOrdenCompra['pass_licitacion'] ?? null, 255),
            'email_licitacion' => normalizarEmailClienteOrdenCompra($datosOrdenCompra['email_licitacion'] ?? null),
            'plat_pagos' => normalizarTextoOrdenCompra($datosOrdenCompra['plat_pagos'] ?? $datosOrdenCompra['portal_facturacion_url'] ?? null, 255),
            'usuario_pagos' => normalizarTextoOrdenCompra($datosOrdenCompra['usuario_pagos'] ?? null, 255),
            'pass_pagos' => normalizarTextoOrdenCompra($datosOrdenCompra['pass_pagos'] ?? null, 255),
            'email_pagos' => normalizarEmailClienteOrdenCompra($datosOrdenCompra['email_pagos'] ?? $datosOrdenCompra['email_facturacion'] ?? null),
            'plat_documentacion' => normalizarTextoOrdenCompra($datosOrdenCompra['plat_documentacion'] ?? $datosOrdenCompra['portal_ingreso_obra_url'] ?? null, 255),
            'usuario_documentacion' => normalizarTextoOrdenCompra($datosOrdenCompra['usuario_documentacion'] ?? null, 255),
            'pass_documentacion' => normalizarTextoOrdenCompra($datosOrdenCompra['pass_documentacion'] ?? null, 255),
            'email_documentacion' => normalizarEmailClienteOrdenCompra($datosOrdenCompra['email_documentacion'] ?? $datosOrdenCompra['contacto_sitio_email'] ?? null),
            'cliente_nota' => normalizarTextoOrdenCompra($nota, 255),
            'log_usuario_id' => $idUsuario,
            'log_accion' => 'alta',
        ];
    }
}

if (!function_exists('validarDatosClienteDesdeOrdenCompra')) {
    function validarDatosClienteDesdeOrdenCompra(array $datosCliente): array
    {
        $errores = [];
        if (normalizarCuitClienteOrdenCompra($datosCliente['cuit'] ?? '') === '') {
            $errores['cliente_cuit'] = 'No hay CUIT disponible para verificar o crear el cliente.';
        } elseif (strlen(normalizarCuitClienteOrdenCompra($datosCliente['cuit'])) !== 11) {
            $errores['cliente_cuit'] = 'El CUIT disponible no tiene el formato esperado para alta automatica.';
        }

        if (trim((string)($datosCliente['razon_social'] ?? '')) === '') {
            $errores['cliente_razon_social'] = 'No hay razon social suficiente para crear el cliente automaticamente.';
        }

        return $errores;
    }
}

if (!function_exists('insertarClienteDesdeOrdenCompraEnConexion')) {
    function insertarClienteDesdeOrdenCompraEnConexion(mysqli $db, array $datosCliente)
    {
        $campos = [
            'cuit' => 's',
            'razon_social' => 's',
            'telefono' => 's',
            'email' => 's',
            'estado' => 's',
            'dirfis_provincia' => 'i',
            'dirfis_partido' => 'i',
            'dirfis_localidad' => 'i',
            'dirfis_calle' => 'i',
            'dirfis_altura' => 'i',
            'dirfis_piso' => 's',
            'dirfis_depto' => 's',
            'dirfis_cp' => 's',
            'contacto_pri' => 's',
            'contacto_pri_celular' => 's',
            'contacto_pri_email' => 's',
            'contacto_papro' => 's',
            'contacto_papro_celular' => 's',
            'contacto_papro_email' => 's',
            'plat_licitacion' => 's',
            'usuario_licitacion' => 's',
            'pass_licitacion' => 's',
            'email_licitacion' => 's',
            'plat_pagos' => 's',
            'usuario_pagos' => 's',
            'pass_pagos' => 's',
            'email_pagos' => 's',
            'plat_documentacion' => 's',
            'usuario_documentacion' => 's',
            'pass_documentacion' => 's',
            'email_documentacion' => 's',
            'cliente_nota' => 's',
            'log_usuario_id' => 'i',
            'log_accion' => 's',
        ];

        $nombres = array_keys($campos);
        $placeholders = implode(', ', array_fill(0, count($nombres), '?'));
        $sql = 'INSERT INTO clientes (' . implode(', ', $nombres) . ') VALUES (' . $placeholders . ')';
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return false;
        }

        $types = implode('', array_values($campos));
        $values = [];
        foreach ($nombres as $nombre) {
            $values[] = $datosCliente[$nombre] ?? null;
        }

        bindParametrosOrdenCompra($stmt, $types, $values);
        $ok = mysqli_stmt_execute($stmt);
        $idCliente = $ok ? mysqli_insert_id($db) : false;
        mysqli_stmt_close($stmt);

        return $idCliente;
    }
}

if (!function_exists('crearClienteDesdeOrdenCompraEnConexion')) {
    function crearClienteDesdeOrdenCompraEnConexion(mysqli $db, array $datosCliente): array
    {
        $cuitNormalizado = normalizarCuitClienteOrdenCompra($datosCliente['cuit'] ?? '');
        $lockName = 'admintech_cliente_cuit_' . $cuitNormalizado;
        $lockTomado = false;

        $stmtLock = mysqli_prepare($db, 'SELECT GET_LOCK(?, 5) AS lock_status');
        if ($stmtLock) {
            mysqli_stmt_bind_param($stmtLock, 's', $lockName);
            mysqli_stmt_execute($stmtLock);
            $resLock = mysqli_stmt_get_result($stmtLock);
            $rowLock = $resLock ? mysqli_fetch_assoc($resLock) : null;
            $lockTomado = ((int)($rowLock['lock_status'] ?? 0) === 1);
            mysqli_stmt_close($stmtLock);
        }

        if (!$lockTomado) {
            return ['creado' => false, 'error' => 'No se pudo bloquear el CUIT para evitar duplicados.'];
        }

        try {
            $clienteExistente = obtenerClientePorCuitNormalizadoEnConexion($db, $cuitNormalizado);
            if ($clienteExistente) {
                return ['creado' => false, 'existente' => true, 'cliente' => $clienteExistente];
            }

            $idCliente = insertarClienteDesdeOrdenCompraEnConexion($db, $datosCliente);
            if (!$idCliente) {
                return ['creado' => false, 'error' => 'No se pudo crear el cliente.'];
            }

            return [
                'creado' => true,
                'cliente' => [
                    'id_cliente' => (int)$idCliente,
                    'cuit' => $datosCliente['cuit'] ?? '',
                    'razon_social' => $datosCliente['razon_social'] ?? '',
                    'estado' => $datosCliente['estado'] ?? 'Activo',
                ],
            ];
        } finally {
            $stmtUnlock = mysqli_prepare($db, 'SELECT RELEASE_LOCK(?)');
            if ($stmtUnlock) {
                mysqli_stmt_bind_param($stmtUnlock, 's', $lockName);
                mysqli_stmt_execute($stmtUnlock);
                mysqli_stmt_close($stmtUnlock);
            }
        }
    }
}
