<?php

require_once __DIR__ . '/schemaIntrospectionModel.php';
require_once __DIR__ . '/presupuestoIntervencionesModel.php';

if (!function_exists('normalizarPerfilOrdenCompraSeguimiento')) {
    function normalizarPerfilOrdenCompraSeguimiento(?string $perfil): string
    {
        return trim((string)$perfil);
    }
}

if (!function_exists('perfilesSeguimientoCompletoOrdenCompra')) {
    function perfilesSeguimientoCompletoOrdenCompra(): array
    {
        return [
            'Super Administrador',
            'Administrador',
            'Técnico',
            'Tecnico',
            'Tecnico Administrativo',
        ];
    }
}

if (!function_exists('perfilesAdministrativosOrdenCompra')) {
    function perfilesAdministrativosOrdenCompra(): array
    {
        return [
            'Super Administrador',
            'Administrador',
            'Administrativo',
        ];
    }
}

if (!function_exists('perfilPuedeVerSeguimientoCompletoOrdenCompra')) {
    function perfilPuedeVerSeguimientoCompletoOrdenCompra(?string $perfil): bool
    {
        return in_array(
            normalizarPerfilOrdenCompraSeguimiento($perfil),
            perfilesSeguimientoCompletoOrdenCompra(),
            true
        );
    }
}

if (!function_exists('perfilPuedeAccederSoloOrdenCompra')) {
    function perfilPuedeAccederSoloOrdenCompra(?string $perfil): bool
    {
        return normalizarPerfilOrdenCompraSeguimiento($perfil) === 'Administrativo';
    }
}

if (!function_exists('perfilPuedeEditarOrdenCompra')) {
    function perfilPuedeEditarOrdenCompra(?string $perfil): bool
    {
        return in_array(
            normalizarPerfilOrdenCompraSeguimiento($perfil),
            perfilesAdministrativosOrdenCompra(),
            true
        );
    }
}

if (!function_exists('perfilSoloPuedeVerOrdenCompra')) {
    function perfilSoloPuedeVerOrdenCompra(?string $perfil): bool
    {
        $perfil = normalizarPerfilOrdenCompraSeguimiento($perfil);

        return in_array($perfil, ['Técnico', 'Tecnico', 'Tecnico Administrativo'], true);
    }
}

if (!function_exists('estadoComercialHabilitaOrdenCompra')) {
    function estadoComercialHabilitaOrdenCompra(?string $estadoComercial): bool
    {
        return strtoupper(trim((string)$estadoComercial)) === 'APROBADO';
    }
}

if (!function_exists('normalizarEstadoOrdenCompra')) {
    function normalizarEstadoOrdenCompra(?string $estado): string
    {
        $estado = strtolower(trim((string)$estado));
        $permitidos = ['pendiente', 'cargada', 'observada', 'anulada', 'no_habilitada'];

        return in_array($estado, $permitidos, true) ? $estado : 'no_habilitada';
    }
}

if (!function_exists('normalizarFiltroEstadoOrdenCompraAdministrativo')) {
    function normalizarFiltroEstadoOrdenCompraAdministrativo(?string $filtro): string
    {
        $filtro = strtolower(trim((string)$filtro));
        $filtro = str_replace(['-', ' '], '_', $filtro);
        $permitidos = ['pendientes', 'cargadas', 'todas_oc'];

        return in_array($filtro, $permitidos, true) ? $filtro : 'pendientes';
    }
}

if (!function_exists('estadoOrdenCompraCoincideConFiltroAdministrativo')) {
    function estadoOrdenCompraCoincideConFiltroAdministrativo(array $estadoOrdenCompra, ?string $filtro): bool
    {
        $estado = normalizarEstadoOrdenCompra((string)($estadoOrdenCompra['estado'] ?? 'no_habilitada'));
        $filtro = normalizarFiltroEstadoOrdenCompraAdministrativo($filtro);

        if ($filtro === 'pendientes') {
            return $estado === 'pendiente';
        }

        if ($filtro === 'cargadas') {
            return $estado === 'cargada';
        }

        return $estado !== 'no_habilitada';
    }
}

if (!function_exists('etiquetaEstadoOrdenCompra')) {
    function etiquetaEstadoOrdenCompra(string $estado): string
    {
        $map = [
            'no_habilitada' => 'No habilitada',
            'pendiente' => 'Pendiente',
            'cargada' => 'Cargada',
            'observada' => 'Observada',
            'anulada' => 'Anulada',
        ];

        return $map[normalizarEstadoOrdenCompra($estado)] ?? 'No habilitada';
    }
}

if (!function_exists('badgeClassEstadoOrdenCompra')) {
    function badgeClassEstadoOrdenCompra(string $estado): string
    {
        $map = [
            'no_habilitada' => 'badge-secondary',
            'pendiente' => 'badge-warning',
            'cargada' => 'badge-success',
            'observada' => 'badge-danger',
            'anulada' => 'badge-dark',
        ];

        return $map[normalizarEstadoOrdenCompra($estado)] ?? 'badge-secondary';
    }
}

if (!function_exists('tablaOrdenesCompraExiste')) {
    function tablaOrdenesCompraExiste(mysqli $db): bool
    {
        return function_exists('tabla_existe') && tabla_existe($db, 'ordenes_compra');
    }
}

if (!function_exists('obtenerOrdenCompraActivaPorPresupuestoEnConexion')) {
    function obtenerOrdenCompraActivaPorPresupuestoEnConexion(mysqli $db, int $idPresupuesto): ?array
    {
        if ($idPresupuesto <= 0 || !tablaOrdenesCompraExiste($db)) {
            return null;
        }

        $sql = "
            SELECT *
            FROM ordenes_compra
            WHERE id_presupuesto = ?
            ORDER BY
                CASE WHEN estado <> 'anulada' THEN 0 ELSE 1 END,
                updated_at DESC,
                created_at DESC,
                id_orden_compra DESC
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

        return $row ?: null;
    }
}

if (!function_exists('resolverEstadoOrdenCompraCalculado')) {
    function resolverEstadoOrdenCompraCalculado(?string $estadoComercialPresupuesto, ?array $ordenCompra = null): array
    {
        $numeroOc = trim((string)($ordenCompra['numero_oc'] ?? ''));
        $tieneOrdenCompra = is_array($ordenCompra) && !empty($ordenCompra);

        if ($tieneOrdenCompra) {
            $estado = normalizarEstadoOrdenCompra((string)($ordenCompra['estado'] ?? 'cargada'));
        } elseif (estadoComercialHabilitaOrdenCompra($estadoComercialPresupuesto)) {
            $estado = 'pendiente';
        } else {
            $estado = 'no_habilitada';
        }

        return [
            'estado' => $estado,
            'estado_label' => etiquetaEstadoOrdenCompra($estado),
            'badge_class' => badgeClassEstadoOrdenCompra($estado),
            'numero_oc' => $numeroOc,
            'tiene_oc' => $tieneOrdenCompra,
            'habilitada' => $estado !== 'no_habilitada',
            'puede_cargar' => $estado === 'pendiente',
        ];
    }
}

if (!function_exists('resolverEstadoOrdenCompraPorPresupuestoEnConexion')) {
    function resolverEstadoOrdenCompraPorPresupuestoEnConexion(
        mysqli $db,
        int $idPresupuesto,
        ?string $estadoComercialPresupuesto
    ): array {
        $ordenCompra = obtenerOrdenCompraActivaPorPresupuestoEnConexion($db, $idPresupuesto);

        return resolverEstadoOrdenCompraCalculado($estadoComercialPresupuesto, $ordenCompra);
    }
}

if (!function_exists('perfilPuedeAccederSeguimientoOrdenCompra')) {
    function perfilPuedeAccederSeguimientoOrdenCompra(?string $perfil, array $estadoOrdenCompra): bool
    {
        if (perfilPuedeVerSeguimientoCompletoOrdenCompra($perfil)) {
            return true;
        }

        return perfilPuedeAccederSoloOrdenCompra($perfil) && !empty($estadoOrdenCompra['habilitada']);
    }
}

if (!function_exists('contarOrdenesCompraPendientesEnConexion')) {
    function contarOrdenesCompraPendientesEnConexion(mysqli $db, ?string $modoCircuito = null): int
    {
        if (!tabla_existe($db, 'presupuestos') || !tabla_existe($db, 'previsitas')) {
            return 0;
        }

        $modoCircuito = normalizarModoEnvioMailPresupuestos(
            $modoCircuito ?: obtenerModoActivoCircuitoComercialPresupuestos()
        );
        $columnaEstadoComercial = columnaEstadoComercialPresupuestoPorModo($modoCircuito);
        $tieneColumnaEstadoComercial = columna_existe($db, 'presupuestos', $columnaEstadoComercial);
        $tieneHistorialComercial = tabla_existe($db, 'presupuesto_historial_comercial');

        if (!$tieneColumnaEstadoComercial && !$tieneHistorialComercial) {
            return 0;
        }

        $estadoComercialSql = $tieneColumnaEstadoComercial
            ? "COALESCE(NULLIF(UPPER(TRIM(p.{$columnaEstadoComercial})), ''), UPPER(TRIM(hc.estado_resultante)))"
            : "UPPER(TRIM(hc.estado_resultante))";

        $modoEscapado = mysqli_real_escape_string($db, $modoCircuito);
        $joinHistorial = $tieneHistorialComercial
            ? "
                LEFT JOIN (
                    SELECT h1.id_presupuesto, h1.estado_resultante
                    FROM presupuesto_historial_comercial h1
                    INNER JOIN (
                        SELECT id_presupuesto, MAX(id_historial_comercial) AS id_historial_comercial
                        FROM presupuesto_historial_comercial
                        WHERE modo_circuito = '{$modoEscapado}'
                        GROUP BY id_presupuesto
                    ) uh ON uh.id_historial_comercial = h1.id_historial_comercial
                ) hc ON hc.id_presupuesto = p.id_presupuesto
            "
            : "LEFT JOIN (SELECT NULL AS id_presupuesto, NULL AS estado_resultante) hc ON 1 = 0";

        $joinOrdenCompra = '';
        $whereSinOrdenCompraActiva = '';
        if (
            tablaOrdenesCompraExiste($db)
            && columna_existe($db, 'ordenes_compra', 'id_presupuesto')
            && columna_existe($db, 'ordenes_compra', 'estado')
        ) {
            $joinOrdenCompra = "
                LEFT JOIN ordenes_compra oc
                    ON oc.id_presupuesto = p.id_presupuesto
                   AND COALESCE(LOWER(TRIM(oc.estado)), '') <> 'anulada'
            ";
            $whereSinOrdenCompraActiva = "AND oc.id_presupuesto IS NULL";
        }

        $sql = "
            SELECT COUNT(*) AS total
            FROM presupuestos p
            INNER JOIN (
                SELECT id_previsita, MAX(id_presupuesto) AS id_presupuesto
                FROM presupuestos
                GROUP BY id_previsita
            ) up ON up.id_presupuesto = p.id_presupuesto
            INNER JOIN previsitas v ON v.id_previsita = p.id_previsita
            {$joinHistorial}
            {$joinOrdenCompra}
            WHERE {$estadoComercialSql} = 'APROBADO'
              AND v.estado_visita <> 'Eliminada'
              {$whereSinOrdenCompraActiva}
        ";

        $resultado = mysqli_query($db, $sql);
        if (!$resultado) {
            return 0;
        }

        $row = mysqli_fetch_assoc($resultado);
        mysqli_free_result($resultado);

        return (int)($row['total'] ?? 0);
    }
}

if (!function_exists('contarOrdenesCompraPendientes')) {
    function contarOrdenesCompraPendientes(?string $modoCircuito = null): int
    {
        $db = conectDB();
        if (!$db) {
            return 0;
        }

        mysqli_set_charset($db, 'utf8mb4');
        $total = contarOrdenesCompraPendientesEnConexion($db, $modoCircuito);
        mysqli_close($db);

        return $total;
    }
}

if (!function_exists('resolverRangoTiempoDesdeAntiguedadOrdenCompra')) {
    function resolverRangoTiempoDesdeAntiguedadOrdenCompra(?int $dias): string
    {
        if ($dias === null || $dias <= 15) {
            return '15_dias';
        }

        if ($dias <= 30) {
            return '30_dias';
        }

        if ($dias <= 90) {
            return 'trimestre';
        }

        if ($dias <= 180) {
            return 'semestre';
        }

        if ($dias <= 365) {
            return 'anio';
        }

        return '';
    }
}

if (!function_exists('resolverRangoInicialOrdenCompraPendienteEnConexion')) {
    function resolverRangoInicialOrdenCompraPendienteEnConexion(mysqli $db, ?string $modoCircuito = null): string
    {
        if (!tabla_existe($db, 'presupuestos') || !tabla_existe($db, 'previsitas')) {
            return '30_dias';
        }

        $modoCircuito = normalizarModoEnvioMailPresupuestos(
            $modoCircuito ?: obtenerModoActivoCircuitoComercialPresupuestos()
        );
        $columnaEstadoComercial = columnaEstadoComercialPresupuestoPorModo($modoCircuito);
        $tieneColumnaEstadoComercial = columna_existe($db, 'presupuestos', $columnaEstadoComercial);
        $tieneHistorialComercial = tabla_existe($db, 'presupuesto_historial_comercial');
        $tieneCreatedAtHistorial = $tieneHistorialComercial
            && columna_existe($db, 'presupuesto_historial_comercial', 'created_at');

        if (!$tieneColumnaEstadoComercial && !$tieneHistorialComercial) {
            return '30_dias';
        }

        $estadoComercialSql = $tieneColumnaEstadoComercial
            ? "COALESCE(NULLIF(UPPER(TRIM(p.{$columnaEstadoComercial})), ''), UPPER(TRIM(hc.estado_resultante)))"
            : "UPPER(TRIM(hc.estado_resultante))";

        $fechaHistorialSelect = $tieneCreatedAtHistorial
            ? 'h1.created_at AS fecha_estado'
            : 'NULL AS fecha_estado';
        $fechaReferenciaSql = $tieneCreatedAtHistorial
            ? "CASE WHEN UPPER(TRIM(hc.estado_resultante)) = 'APROBADO' THEN hc.fecha_estado ELSE v.log_alta END"
            : 'v.log_alta';

        $modoEscapado = mysqli_real_escape_string($db, $modoCircuito);
        $joinHistorial = $tieneHistorialComercial
            ? "
                LEFT JOIN (
                    SELECT h1.id_presupuesto, h1.estado_resultante, {$fechaHistorialSelect}
                    FROM presupuesto_historial_comercial h1
                    INNER JOIN (
                        SELECT id_presupuesto, MAX(id_historial_comercial) AS id_historial_comercial
                        FROM presupuesto_historial_comercial
                        WHERE modo_circuito = '{$modoEscapado}'
                        GROUP BY id_presupuesto
                    ) uh ON uh.id_historial_comercial = h1.id_historial_comercial
                ) hc ON hc.id_presupuesto = p.id_presupuesto
            "
            : "LEFT JOIN (SELECT NULL AS id_presupuesto, NULL AS estado_resultante, NULL AS fecha_estado) hc ON 1 = 0";

        $joinOrdenCompra = '';
        $whereSinOrdenCompraActiva = '';
        if (
            tablaOrdenesCompraExiste($db)
            && columna_existe($db, 'ordenes_compra', 'id_presupuesto')
            && columna_existe($db, 'ordenes_compra', 'estado')
        ) {
            $joinOrdenCompra = "
                LEFT JOIN ordenes_compra oc
                    ON oc.id_presupuesto = p.id_presupuesto
                   AND COALESCE(LOWER(TRIM(oc.estado)), '') <> 'anulada'
            ";
            $whereSinOrdenCompraActiva = "AND oc.id_presupuesto IS NULL";
        }

        $sql = "
            SELECT MAX(DATEDIFF(CURDATE(), DATE(COALESCE({$fechaReferenciaSql}, v.log_alta)))) AS max_dias
            FROM presupuestos p
            INNER JOIN (
                SELECT id_previsita, MAX(id_presupuesto) AS id_presupuesto
                FROM presupuestos
                GROUP BY id_previsita
            ) up ON up.id_presupuesto = p.id_presupuesto
            INNER JOIN previsitas v ON v.id_previsita = p.id_previsita
            {$joinHistorial}
            {$joinOrdenCompra}
            WHERE {$estadoComercialSql} = 'APROBADO'
              AND v.estado_visita <> 'Eliminada'
              {$whereSinOrdenCompraActiva}
        ";

        $resultado = mysqli_query($db, $sql);
        if (!$resultado) {
            return '30_dias';
        }

        $row = mysqli_fetch_assoc($resultado);
        mysqli_free_result($resultado);
        $maxDias = isset($row['max_dias']) && $row['max_dias'] !== null
            ? max(0, (int)$row['max_dias'])
            : null;

        return $maxDias === null ? '30_dias' : resolverRangoTiempoDesdeAntiguedadOrdenCompra($maxDias);
    }
}

if (!function_exists('resolverRangoInicialOrdenCompraPendiente')) {
    function resolverRangoInicialOrdenCompraPendiente(?string $modoCircuito = null): string
    {
        $db = conectDB();
        if (!$db) {
            return '30_dias';
        }

        mysqli_set_charset($db, 'utf8mb4');
        $rango = resolverRangoInicialOrdenCompraPendienteEnConexion($db, $modoCircuito);
        mysqli_close($db);

        return $rango;
    }
}
