<?php

// Composicion READ-ONLY de los datos reales del Panel de analitica de Seguimiento.
// No escribe en la base ni genera HTML: devuelve solo datos.
// Cantidad de consultas constante (sin N+1): previsita, presupuesto vigente, lineas de
// materiales, lineas de mano de obra, pedidos confirmados, snapshot, autorizaciones y
// catalogo de unidades de materiales adicionales.

require_once __DIR__ . '/conectDB.php';
require_once __DIR__ . '/schemaIntrospectionModel.php';
require_once __DIR__ . '/presupuestoGeneradoModel.php';

if (!function_exists('dashboardSeguimientoRedondearCantidad')) {
    function dashboardSeguimientoRedondearCantidad($valor): float
    {
        return round((float)$valor, 4);
    }
}

if (!function_exists('dashboardSeguimientoEjecutarFilas')) {
    // Ejecuta una consulta preparada y devuelve todas las filas asociativas.
    function dashboardSeguimientoEjecutarFilas(mysqli $db, string $sql, string $tipos = '', array $params = []): array
    {
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            throw new RuntimeException('No se pudo preparar la consulta del Panel.');
        }

        if ($tipos !== '') {
            mysqli_stmt_bind_param($stmt, $tipos, ...$params);
        }

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('No se pudo ejecutar la consulta del Panel.');
        }

        $res = mysqli_stmt_get_result($stmt);
        $filas = [];
        while ($res && ($fila = mysqli_fetch_assoc($res))) {
            $filas[] = $fila;
        }
        mysqli_stmt_close($stmt);

        return $filas;
    }
}

if (!function_exists('dashboardSeguimientoDiasTranscurridos')) {
    // Dias calendario entre DATE(log_alta) y hoy. null si la fecha es invalida o futura.
    function dashboardSeguimientoDiasTranscurridos(?string $logAlta): ?int
    {
        $logAlta = trim((string)$logAlta);
        if ($logAlta === '' || strpos($logAlta, '0000-00-00') === 0) {
            return null;
        }

        $timestamp = strtotime($logAlta);
        if ($timestamp === false) {
            return null;
        }

        $inicio = new DateTime(date('Y-m-d', $timestamp));
        $hoy = new DateTime(date('Y-m-d'));
        if ($inicio > $hoy) {
            return null;
        }

        return (int)$inicio->diff($hoy)->days;
    }
}

if (!function_exists('dashboardSeguimientoEstadoMaterial')) {
    // Estados defendibles hoy: sin_pedido / dentro / excedido / adicional (sin umbrales).
    function dashboardSeguimientoEstadoMaterial(float $presupuestado, ?float $solicitado, bool $esAdicional): string
    {
        if ($esAdicional) {
            return 'adicional';
        }
        if ($solicitado === null) {
            return 'nd';
        }

        $tolerancia = 0.00005;
        if ($solicitado <= $tolerancia) {
            return 'sin_pedido';
        }
        if ($presupuestado <= $tolerancia) {
            return 'adicional';
        }

        return $solicitado > $presupuestado + $tolerancia ? 'excedido' : 'dentro';
    }
}

if (!function_exists('dashboardSeguimientoUnidadesCatalogo')) {
    // Unidad vigente del catalogo para una lista de id_material (una sola consulta).
    function dashboardSeguimientoUnidadesCatalogo(mysqli $db, array $idsMaterial): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $idsMaterial), static fn($id) => $id > 0)));
        if (!$ids) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $filas = dashboardSeguimientoEjecutarFilas(
            $db,
            "SELECT id_material, unidad_venta FROM materiales WHERE id_material IN ($placeholders)",
            str_repeat('i', count($ids)),
            $ids
        );

        $mapa = [];
        foreach ($filas as $fila) {
            $unidad = trim((string)($fila['unidad_venta'] ?? ''));
            $mapa[(int)$fila['id_material']] = $unidad !== '' ? $unidad : '';
        }

        return $mapa;
    }
}

if (!function_exists('obtenerDatosDashboardSeguimientoEnConexion')) {
    /**
     * @return array|null null si la previsita no existe (o esta eliminada, igual que el listado).
     */
    function obtenerDatosDashboardSeguimientoEnConexion(mysqli $db, int $idPrevisita): ?array
    {
        if ($idPrevisita <= 0) {
            return null;
        }

        $previsitas = dashboardSeguimientoEjecutarFilas(
            $db,
            'SELECT id_previsita, razon_social, cuit, log_alta, estado_visita FROM previsitas WHERE id_previsita = ? LIMIT 1',
            'i',
            [$idPrevisita]
        );
        $previsita = $previsitas[0] ?? null;
        if (!$previsita || strtoupper(trim((string)($previsita['estado_visita'] ?? ''))) === 'ELIMINADA') {
            return null;
        }

        // Presupuesto vigente: misma funcion que usa el circuito de Pedido de Materiales
        // (ORDER BY version DESC, created_at DESC, id_presupuesto DESC).
        $vigente = obtenerPresupuestoVigentePorPrevisitaEnConexion($db, $idPrevisita);
        $idPresupuesto = (int)($vigente['id_presupuesto'] ?? 0);

        $presupuestoVisible = 'Sin presupuesto';
        $monto = ['valor' => null, 'moneda' => ''];
        if ($idPresupuesto > 0) {
            $cabecera = dashboardSeguimientoEjecutarFilas(
                $db,
                'SELECT codigo, total_mostrado, moneda FROM presupuestos WHERE id_presupuesto = ? LIMIT 1',
                'i',
                [$idPresupuesto]
            )[0] ?? [];

            $codigo = trim((string)($cabecera['codigo'] ?? ''));
            $presupuestoVisible = $codigo !== '' ? $codigo : '#' . $idPresupuesto;
            $monto = [
                'valor' => isset($cabecera['total_mostrado']) && $cabecera['total_mostrado'] !== null
                    ? round((float)$cabecera['total_mostrado'], 2)
                    : null,
                'moneda' => strtoupper(trim((string)($cabecera['moneda'] ?? ''))),
            ];
        }

        $logAlta = trim((string)($previsita['log_alta'] ?? ''));
        $timestampAlta = $logAlta !== '' ? strtotime($logAlta) : false;
        $inicio = ($timestampAlta !== false && strpos($logAlta, '0000-00-00') !== 0)
            ? date('d/m/Y', $timestampAlta)
            : null;

        $materiales = [];
        $jornales = [];
        $pedidosInfo = [
            'confirmados' => 0,
            'otro_presupuesto' => 0,
            'sin_historial_formal' => [],
        ];

        if ($idPresupuesto > 0) {
            $ordenMaterial = columna_existe($db, 'presupuesto_tarea_material', 'orden') ? 'ptm.orden ASC, ' : '';
            $lineasMaterial = dashboardSeguimientoEjecutarFilas(
                $db,
                "
                SELECT ptm.id_ptm, ptm.id_material, ptm.nombre_material, ptm.cantidad,
                       pt.nro AS tarea_nro, pt.descripcion AS tarea_titulo,
                       COALESCE(NULLIF(TRIM(ptm.unidad_venta), ''), NULLIF(TRIM(m.unidad_venta), '')) AS unidad
                FROM presupuesto_tarea_material ptm
                INNER JOIN presupuesto_tareas pt ON pt.id_presu_tarea = ptm.id_presu_tarea
                LEFT JOIN materiales m ON m.id_material = ptm.id_material
                WHERE pt.id_presupuesto = ?
                ORDER BY pt.nro ASC, {$ordenMaterial}ptm.id_ptm ASC
                ",
                'i',
                [$idPresupuesto]
            );

            $ordenManoObra = columna_existe($db, 'presupuesto_tarea_mano_obra', 'orden') ? 'mo.orden ASC, ' : '';
            $lineasManoObra = dashboardSeguimientoEjecutarFilas(
                $db,
                "
                SELECT mo.id_ptmo, mo.id_jornal, mo.nombre_jornal, mo.cantidad, mo.dias,
                       pt.nro AS tarea_nro, pt.descripcion AS tarea_titulo
                FROM presupuesto_tarea_mano_obra mo
                INNER JOIN presupuesto_tareas pt ON pt.id_presu_tarea = mo.id_presu_tarea
                WHERE pt.id_presupuesto = ?
                ORDER BY pt.nro ASC, {$ordenManoObra}mo.id_ptmo ASC
                ",
                'i',
                [$idPresupuesto]
            );

            foreach ($lineasManoObra as $linea) {
                $personas = dashboardSeguimientoRedondearCantidad($linea['cantidad'] ?? 0);
                $dias = (int)($linea['dias'] ?? 0);
                $jornales[] = [
                    'jornal' => trim((string)($linea['nombre_jornal'] ?? '')),
                    'tarea' => trim((string)($linea['tarea_titulo'] ?? '')),
                    'personas' => $personas,
                    'dias' => $dias,
                    'jornales' => dashboardSeguimientoRedondearCantidad($personas * $dias),
                ];
            }

            // Pedidos confirmados (persistencia formal). El snapshot editable NO se cuenta como solicitado.
            $solicitadoPorClave = [];
            $adicionalesConfirmados = [];
            $numerosConfirmados = [];
            if (tabla_existe($db, 'pedido_materiales_pedidos') && tabla_existe($db, 'pedido_materiales_pedido_detalles')) {
                $cabeceras = dashboardSeguimientoEjecutarFilas(
                    $db,
                    "SELECT numero_pedido, id_presupuesto FROM pedido_materiales_pedidos WHERE id_previsita = ? AND estado = 'confirmado'",
                    'i',
                    [$idPrevisita]
                );
                foreach ($cabeceras as $cabecera) {
                    if ((int)$cabecera['id_presupuesto'] === $idPresupuesto) {
                        $numerosConfirmados[(int)$cabecera['numero_pedido']] = true;
                    } else {
                        $pedidosInfo['otro_presupuesto']++;
                    }
                }
                $pedidosInfo['confirmados'] = count($numerosConfirmados);

                $detalles = dashboardSeguimientoEjecutarFilas(
                    $db,
                    "
                    SELECT d.tipo_fila, d.id_material, d.tarea_nro,
                           MAX(d.material_texto) AS material_texto,
                           SUM(d.cantidad_pedido) AS cantidad
                    FROM pedido_materiales_pedido_detalles d
                    INNER JOIN pedido_materiales_pedidos p
                            ON p.id_pedido_materiales_pedido = d.id_pedido_materiales_pedido
                    WHERE p.id_previsita = ?
                      AND p.id_presupuesto = ?
                      AND p.estado = 'confirmado'
                    GROUP BY d.tipo_fila, d.id_material, d.tarea_nro
                    ",
                    'ii',
                    [$idPrevisita, $idPresupuesto]
                );
                foreach ($detalles as $detalle) {
                    $tareaNro = $detalle['tarea_nro'] !== null ? (int)$detalle['tarea_nro'] : 0;
                    $clave = (int)$detalle['id_material'] . '|' . $tareaNro;
                    if ((string)$detalle['tipo_fila'] === 'presupuestado') {
                        $solicitadoPorClave[$clave] = [
                            'cantidad' => dashboardSeguimientoRedondearCantidad($detalle['cantidad']),
                            'material' => trim((string)$detalle['material_texto']),
                            'id_material' => (int)$detalle['id_material'],
                            'usada' => false,
                        ];
                    } else {
                        $adicionalesConfirmados[] = $detalle;
                    }
                }
            }

            // Deteccion generica de pedidos realizados sin persistencia formal (legacy):
            // el snapshot avanzo el pedido activo (N) => los pedidos 1..N-1 ya fueron realizados.
            // Si alguno no figura como pedido confirmado, el historial formal es incompleto.
            if (tabla_existe($db, 'pedido_materiales_snapshots')) {
                $snapshot = dashboardSeguimientoEjecutarFilas(
                    $db,
                    'SELECT pedido_activo FROM pedido_materiales_snapshots WHERE id_previsita = ? LIMIT 1',
                    'i',
                    [$idPrevisita]
                )[0] ?? null;
                $pedidoActivo = $snapshot ? (int)$snapshot['pedido_activo'] : 1;
                for ($numero = 1; $numero < $pedidoActivo; $numero++) {
                    if (!isset($numerosConfirmados[$numero])) {
                        $pedidosInfo['sin_historial_formal'][] = $numero;
                    }
                }
            }
            $historialIncompleto = !empty($pedidosInfo['sin_historial_formal']);

            foreach ($lineasMaterial as $linea) {
                $idMaterial = (int)$linea['id_material'];
                $tareaNro = $linea['tarea_nro'] !== null ? (int)$linea['tarea_nro'] : 0;
                $clave = $idMaterial . '|' . $tareaNro;
                $presupuestado = dashboardSeguimientoRedondearCantidad($linea['cantidad'] ?? 0);

                if ($historialIncompleto) {
                    $solicitado = null;
                } else {
                    $solicitado = isset($solicitadoPorClave[$clave]) ? $solicitadoPorClave[$clave]['cantidad'] : 0.0;
                    if (isset($solicitadoPorClave[$clave])) {
                        $solicitadoPorClave[$clave]['usada'] = true;
                    }
                }

                $estado = dashboardSeguimientoEstadoMaterial($presupuestado, $solicitado, false);
                $materiales[] = [
                    'material' => trim((string)($linea['nombre_material'] ?? '')),
                    'tarea' => trim((string)($linea['tarea_titulo'] ?? '')),
                    'unidad' => trim((string)($linea['unidad'] ?? '')),
                    'presupuestado' => $presupuestado,
                    'solicitado' => $solicitado,
                    'diferencia' => $solicitado === null ? null : dashboardSeguimientoRedondearCantidad($presupuestado - $solicitado),
                    'porcentaje' => ($solicitado !== null && $presupuestado > 0)
                        ? round($solicitado / $presupuestado * 100, 1)
                        : null,
                    'estado' => $estado,
                    'adicional' => $estado === 'adicional',
                ];
            }

            // Lo confirmado que no tiene linea vigente en el presupuesto (agregados, o lineas
            // presupuestadas que ya no existen) se informa como adicional, sin descartarlo.
            if (!$historialIncompleto) {
                $extras = [];
                foreach ($adicionalesConfirmados as $detalle) {
                    $extras[] = [
                        'id_material' => (int)$detalle['id_material'],
                        'material' => trim((string)$detalle['material_texto']),
                        'tarea_nro' => $detalle['tarea_nro'],
                        'cantidad' => dashboardSeguimientoRedondearCantidad($detalle['cantidad']),
                    ];
                }
                foreach ($solicitadoPorClave as $item) {
                    if (!$item['usada']) {
                        $extras[] = [
                            'id_material' => $item['id_material'],
                            'material' => $item['material'],
                            'tarea_nro' => null,
                            'cantidad' => $item['cantidad'],
                        ];
                    }
                }

                $unidades = dashboardSeguimientoUnidadesCatalogo($db, array_column($extras, 'id_material'));
                foreach ($extras as $extra) {
                    $materiales[] = [
                        'material' => $extra['material'],
                        'tarea' => '',
                        'unidad' => $unidades[$extra['id_material']] ?? '',
                        'presupuestado' => 0.0,
                        'solicitado' => $extra['cantidad'],
                        'diferencia' => null,
                        'porcentaje' => null,
                        'estado' => 'adicional',
                        'adicional' => true,
                    ];
                }
            }
        }

        // Autorizaciones: pendientes = estado ACTUAL del snapshot; resueltas = tabla formal.
        $pendientes = 0;
        if (tabla_existe($db, 'pedido_materiales_snapshots') && tabla_existe($db, 'pedido_materiales_snapshot_detalles')) {
            $fila = dashboardSeguimientoEjecutarFilas(
                $db,
                "
                SELECT COUNT(*) AS total
                FROM pedido_materiales_snapshots s
                INNER JOIN pedido_materiales_snapshot_detalles d
                        ON d.id_pedido_materiales_snapshot = s.id_pedido_materiales_snapshot
                WHERE s.id_previsita = ?
                  AND d.estado_autorizacion = 'pendiente'
                ",
                'i',
                [$idPrevisita]
            )[0] ?? [];
            $pendientes = (int)($fila['total'] ?? 0);
        }

        $autorizadas = 0;
        $rechazadas = 0;
        if (tabla_existe($db, 'pedido_materiales_autorizaciones')) {
            $filas = dashboardSeguimientoEjecutarFilas(
                $db,
                'SELECT estado_autorizacion, COUNT(*) AS total FROM pedido_materiales_autorizaciones WHERE id_previsita = ? GROUP BY estado_autorizacion',
                'i',
                [$idPrevisita]
            );
            foreach ($filas as $fila) {
                if ((string)$fila['estado_autorizacion'] === 'autorizada') {
                    $autorizadas = (int)$fila['total'];
                } elseif ((string)$fila['estado_autorizacion'] === 'rechazada') {
                    $rechazadas = (int)$fila['total'];
                }
            }
        }

        return [
            'identificacion' => [
                'id_previsita' => (int)$previsita['id_previsita'],
                'cliente' => trim((string)($previsita['razon_social'] ?? '')),
                'cuit' => trim((string)($previsita['cuit'] ?? '')),
                'presupuesto' => $presupuestoVisible,
                'tiene_presupuesto' => $idPresupuesto > 0,
            ],
            'kpis' => [
                'inicio' => $inicio,
                'dias_transcurridos' => dashboardSeguimientoDiasTranscurridos($logAlta),
                'monto_presupuestado' => $monto,
            ],
            'materiales' => $materiales,
            'materiales_meta' => [
                'pedidos_confirmados' => $pedidosInfo['confirmados'],
                'pedidos_otro_presupuesto' => $pedidosInfo['otro_presupuesto'],
                'sin_historial_formal' => $pedidosInfo['sin_historial_formal'],
            ],
            'jornales' => $jornales,
            'autorizaciones' => [
                'pendientes' => $pendientes,
                'autorizadas' => $autorizadas,
                'rechazadas' => $rechazadas,
            ],
        ];
    }
}

if (!function_exists('obtenerDatosDashboardSeguimiento')) {
    function obtenerDatosDashboardSeguimiento(int $idPrevisita): ?array
    {
        $db = conectDB();
        if (!$db) {
            throw new RuntimeException('No se pudo abrir la conexion del Panel.');
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            return obtenerDatosDashboardSeguimientoEnConexion($db, $idPrevisita);
        } finally {
            mysqli_close($db);
        }
    }
}
