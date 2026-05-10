<?php

require_once __DIR__ . '/schemaIntrospectionModel.php';

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
