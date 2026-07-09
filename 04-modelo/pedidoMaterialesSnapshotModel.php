<?php

require_once __DIR__ . '/schemaIntrospectionModel.php';

if (!function_exists('pedidoMaterialesSnapshotTablasMinimasDisponibles')) {
    function pedidoMaterialesSnapshotTablasMinimasDisponibles(mysqli $db): bool
    {
        return tabla_existe($db, 'pedido_materiales_snapshots')
            && tabla_existe($db, 'pedido_materiales_snapshot_detalles');
    }
}

if (!function_exists('pedidoMaterialesSnapshotEstadosPermitidos')) {
    function pedidoMaterialesSnapshotEstadosPermitidos(): array
    {
        return ['sin_solicitud', 'pendiente', 'autorizada', 'rechazada'];
    }
}

if (!function_exists('normalizarNumeroPedidoMaterialesSnapshot')) {
    function normalizarNumeroPedidoMaterialesSnapshot($numero, int $fallback = 1): int
    {
        $numero = (int)$numero;
        if ($numero < 1) {
            return $fallback;
        }

        if ($numero > 5) {
            return 5;
        }

        return $numero;
    }
}

if (!function_exists('normalizarDecimalPedidoMaterialesSnapshotEntrada')) {
    function normalizarDecimalPedidoMaterialesSnapshotEntrada($valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        if (is_int($valor) || is_float($valor)) {
            $numero = (float)$valor;
            if (!is_finite($numero) || $numero < 0) {
                return 0.0;
            }

            return round($numero, 4);
        }

        $texto = trim((string)$valor);
        if ($texto === '') {
            return 0.0;
        }

        if (strpos($texto, ',') !== false) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        } elseif (preg_match('/^-?\d{1,3}(?:\.\d{3})+$/', $texto)) {
            $texto = str_replace('.', '', $texto);
        }

        $numero = (float)$texto;
        if (!is_finite($numero) || $numero < 0) {
            return 0.0;
        }

        return round($numero, 4);
    }
}

if (!function_exists('normalizarDecimalPedidoMaterialesSnapshotDesdeDb')) {
    function normalizarDecimalPedidoMaterialesSnapshotDesdeDb($valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        $numero = (float)$valor;
        if (!is_finite($numero) || $numero < 0) {
            return 0.0;
        }

        return round($numero, 4);
    }
}

if (!function_exists('pedidoMaterialesSnapshotDetalleDesdeFila')) {
    function pedidoMaterialesSnapshotDetalleDesdeFila(array $fila): array
    {
        $pedidos = $fila['pedidos'] ?? [];
        $estadoAutorizacion = trim((string)($fila['estado_autorizacion'] ?? 'sin_solicitud'));
        if (!in_array($estadoAutorizacion, pedidoMaterialesSnapshotEstadosPermitidos(), true)) {
            $estadoAutorizacion = 'sin_solicitud';
        }

        return [
            'tipo_fila' => ($fila['tipo_fila'] ?? '') === 'agregado' ? 'agregado' : 'presupuestado',
            'id_tarea' => isset($fila['id_tarea']) && (int)$fila['id_tarea'] > 0 ? (int)$fila['id_tarea'] : null,
            'tarea_nro' => isset($fila['tarea_nro']) && (int)$fila['tarea_nro'] > 0 ? (int)$fila['tarea_nro'] : null,
            'tarea_titulo' => trim((string)($fila['tarea_titulo'] ?? '')),
            'id_material' => (int)($fila['id_material'] ?? 0),
            'material_texto' => trim((string)($fila['material_texto'] ?? '')),
            'cantidad_inicial' => normalizarDecimalPedidoMaterialesSnapshotEntrada($fila['cantidad_inicial'] ?? 0),
            'cantidad_solicitada' => normalizarDecimalPedidoMaterialesSnapshotEntrada($fila['cantidad_solicitada'] ?? 0),
            'pedido_1' => normalizarDecimalPedidoMaterialesSnapshotEntrada($pedidos[1] ?? $pedidos['1'] ?? 0),
            'pedido_2' => normalizarDecimalPedidoMaterialesSnapshotEntrada($pedidos[2] ?? $pedidos['2'] ?? 0),
            'pedido_3' => normalizarDecimalPedidoMaterialesSnapshotEntrada($pedidos[3] ?? $pedidos['3'] ?? 0),
            'pedido_4' => normalizarDecimalPedidoMaterialesSnapshotEntrada($pedidos[4] ?? $pedidos['4'] ?? 0),
            'pedido_5' => normalizarDecimalPedidoMaterialesSnapshotEntrada($pedidos[5] ?? $pedidos['5'] ?? 0),
            'estado_autorizacion' => $estadoAutorizacion,
            'autorizacion_adicional' => array_key_exists('autorizacion_adicional', $fila)
                ? normalizarDecimalPedidoMaterialesSnapshotEntrada($fila['autorizacion_adicional'])
                : null,
            'pedido_autorizacion_previo' => array_key_exists('pedido_autorizacion_previo', $fila)
                ? normalizarDecimalPedidoMaterialesSnapshotEntrada($fila['pedido_autorizacion_previo'])
                : null,
            'orden_visual' => max(0, (int)($fila['orden_visual'] ?? 0)),
        ];
    }
}

if (!function_exists('obtenerPedidoMaterialesSnapshotPorPrevisitaEnConexion')) {
    function obtenerPedidoMaterialesSnapshotPorPrevisitaEnConexion(mysqli $db, int $idPrevisita): ?array
    {
        if ($idPrevisita <= 0 || !pedidoMaterialesSnapshotTablasMinimasDisponibles($db)) {
            return null;
        }

        $sql = "
            SELECT
                id_pedido_materiales_snapshot,
                id_previsita,
                pedido_activo,
                pedido_maximo_visible,
                finalizado,
                accion_guardado,
                id_usuario_guardado,
                created_at,
                updated_at
            FROM pedido_materiales_snapshots
            WHERE id_previsita = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $header = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if (!$header) {
            return null;
        }

        $snapshotId = (int)$header['id_pedido_materiales_snapshot'];
        $headerNormalizado = [
            'id_pedido_materiales_snapshot' => $snapshotId,
            'id_previsita' => (int)$header['id_previsita'],
            'pedido_activo' => normalizarNumeroPedidoMaterialesSnapshot($header['pedido_activo']),
            'pedido_maximo_visible' => normalizarNumeroPedidoMaterialesSnapshot($header['pedido_maximo_visible']),
            'finalizado' => (int)$header['finalizado'] === 1,
            'accion_guardado' => (string)$header['accion_guardado'],
            'id_usuario_guardado' => (int)$header['id_usuario_guardado'],
            'created_at' => (string)($header['created_at'] ?? ''),
            'updated_at' => (string)($header['updated_at'] ?? ''),
            'materiales_presupuestados' => [],
            'materiales_agregados' => [],
        ];

        $sqlDetalles = "
            SELECT
                tipo_fila,
                id_tarea,
                tarea_nro,
                tarea_titulo,
                id_material,
                material_texto,
                cantidad_inicial,
                cantidad_solicitada,
                pedido_1,
                pedido_2,
                pedido_3,
                pedido_4,
                pedido_5,
                estado_autorizacion,
                autorizacion_adicional,
                pedido_autorizacion_previo,
                orden_visual
            FROM pedido_materiales_snapshot_detalles
            WHERE id_pedido_materiales_snapshot = ?
            ORDER BY tipo_fila ASC, orden_visual ASC, id_pedido_materiales_snapshot_detalle ASC
        ";
        $stmtDetalles = mysqli_prepare($db, $sqlDetalles);
        if (!$stmtDetalles) {
            return $headerNormalizado;
        }

        mysqli_stmt_bind_param($stmtDetalles, 'i', $snapshotId);
        mysqli_stmt_execute($stmtDetalles);
        $resultDetalles = mysqli_stmt_get_result($stmtDetalles);

        while ($resultDetalles && ($row = mysqli_fetch_assoc($resultDetalles))) {
            $detalle = [
                'tipo_fila' => (string)$row['tipo_fila'],
                'id_tarea' => isset($row['id_tarea']) ? (int)$row['id_tarea'] : null,
                'tarea_nro' => isset($row['tarea_nro']) ? (int)$row['tarea_nro'] : null,
                'tarea_titulo' => (string)($row['tarea_titulo'] ?? ''),
                'id_material' => (int)($row['id_material'] ?? 0),
                'material_texto' => (string)($row['material_texto'] ?? ''),
                'cantidad_inicial' => normalizarDecimalPedidoMaterialesSnapshotDesdeDb($row['cantidad_inicial'] ?? 0),
                'cantidad_solicitada' => normalizarDecimalPedidoMaterialesSnapshotDesdeDb($row['cantidad_solicitada'] ?? 0),
                'pedidos' => [
                    1 => normalizarDecimalPedidoMaterialesSnapshotDesdeDb($row['pedido_1'] ?? 0),
                    2 => normalizarDecimalPedidoMaterialesSnapshotDesdeDb($row['pedido_2'] ?? 0),
                    3 => normalizarDecimalPedidoMaterialesSnapshotDesdeDb($row['pedido_3'] ?? 0),
                    4 => normalizarDecimalPedidoMaterialesSnapshotDesdeDb($row['pedido_4'] ?? 0),
                    5 => normalizarDecimalPedidoMaterialesSnapshotDesdeDb($row['pedido_5'] ?? 0),
                ],
                'estado_autorizacion' => (string)($row['estado_autorizacion'] ?? 'sin_solicitud'),
                'autorizacion_adicional' => $row['autorizacion_adicional'] !== null
                    ? normalizarDecimalPedidoMaterialesSnapshotDesdeDb($row['autorizacion_adicional'])
                    : null,
                'pedido_autorizacion_previo' => $row['pedido_autorizacion_previo'] !== null
                    ? normalizarDecimalPedidoMaterialesSnapshotDesdeDb($row['pedido_autorizacion_previo'])
                    : null,
                'orden_visual' => (int)($row['orden_visual'] ?? 0),
            ];

            if ($detalle['tipo_fila'] === 'agregado') {
                $headerNormalizado['materiales_agregados'][] = $detalle;
            } else {
                $headerNormalizado['materiales_presupuestados'][] = $detalle;
            }
        }

        mysqli_stmt_close($stmtDetalles);

        return $headerNormalizado;
    }
}

if (!function_exists('guardarPedidoMaterialesSnapshotEnConexion')) {
    function guardarPedidoMaterialesSnapshotEnConexion(mysqli $db, array $snapshot, int $idUsuario): array
    {
        if (!pedidoMaterialesSnapshotTablasMinimasDisponibles($db)) {
            throw new RuntimeException('Las tablas de snapshot de Pedido de Materiales no estan disponibles.');
        }

        $idPrevisita = (int)($snapshot['id_previsita'] ?? 0);
        if ($idPrevisita <= 0) {
            throw new RuntimeException('La previsita es obligatoria para guardar el snapshot.');
        }

        $pedidoActivo = normalizarNumeroPedidoMaterialesSnapshot($snapshot['pedido_activo'] ?? 1);
        $pedidoMaximoVisible = normalizarNumeroPedidoMaterialesSnapshot($snapshot['pedido_maximo_visible'] ?? $pedidoActivo, $pedidoActivo);
        if ($pedidoMaximoVisible < $pedidoActivo) {
            $pedidoMaximoVisible = $pedidoActivo;
        }

        $finalizado = !empty($snapshot['finalizado']) ? 1 : 0;
        $accionGuardado = (($snapshot['accion_guardado'] ?? '') === 'realizar') ? 'realizar' : 'guardar';
        $filas = [];

        foreach ((array)($snapshot['materiales_presupuestados'] ?? []) as $indice => $fila) {
            $fila['tipo_fila'] = 'presupuestado';
            $fila['orden_visual'] = $indice + 1;
            $filas[] = pedidoMaterialesSnapshotDetalleDesdeFila($fila);
        }
        foreach ((array)($snapshot['materiales_agregados'] ?? []) as $indice => $fila) {
            $fila['tipo_fila'] = 'agregado';
            $fila['orden_visual'] = $indice + 1;
            $filas[] = pedidoMaterialesSnapshotDetalleDesdeFila($fila);
        }

        mysqli_begin_transaction($db);

        try {
            $sqlCabecera = "
                INSERT INTO pedido_materiales_snapshots (
                    id_previsita,
                    pedido_activo,
                    pedido_maximo_visible,
                    finalizado,
                    accion_guardado,
                    id_usuario_guardado,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    id_pedido_materiales_snapshot = LAST_INSERT_ID(id_pedido_materiales_snapshot),
                    pedido_activo = VALUES(pedido_activo),
                    pedido_maximo_visible = VALUES(pedido_maximo_visible),
                    finalizado = VALUES(finalizado),
                    accion_guardado = VALUES(accion_guardado),
                    id_usuario_guardado = VALUES(id_usuario_guardado),
                    updated_at = NOW()
            ";
            $stmtCabecera = mysqli_prepare($db, $sqlCabecera);
            if (!$stmtCabecera) {
                throw new RuntimeException('No se pudo preparar la cabecera del snapshot.');
            }

            mysqli_stmt_bind_param(
                $stmtCabecera,
                'iiiisi',
                $idPrevisita,
                $pedidoActivo,
                $pedidoMaximoVisible,
                $finalizado,
                $accionGuardado,
                $idUsuario
            );

            if (!mysqli_stmt_execute($stmtCabecera)) {
                $error = mysqli_stmt_error($stmtCabecera);
                mysqli_stmt_close($stmtCabecera);
                throw new RuntimeException('No se pudo guardar la cabecera del snapshot: ' . $error);
            }

            $snapshotId = (int)mysqli_insert_id($db);
            mysqli_stmt_close($stmtCabecera);

            $stmtDelete = mysqli_prepare(
                $db,
                'DELETE FROM pedido_materiales_snapshot_detalles WHERE id_pedido_materiales_snapshot = ?'
            );
            if (!$stmtDelete) {
                throw new RuntimeException('No se pudo preparar la limpieza del detalle del snapshot.');
            }
            mysqli_stmt_bind_param($stmtDelete, 'i', $snapshotId);
            if (!mysqli_stmt_execute($stmtDelete)) {
                $error = mysqli_stmt_error($stmtDelete);
                mysqli_stmt_close($stmtDelete);
                throw new RuntimeException('No se pudo reemplazar el detalle del snapshot: ' . $error);
            }
            mysqli_stmt_close($stmtDelete);

            if (!empty($filas)) {
                $sqlDetalle = "
                    INSERT INTO pedido_materiales_snapshot_detalles (
                        id_pedido_materiales_snapshot,
                        tipo_fila,
                        id_tarea,
                        tarea_nro,
                        tarea_titulo,
                        id_material,
                        material_texto,
                        cantidad_inicial,
                        cantidad_solicitada,
                        pedido_1,
                        pedido_2,
                        pedido_3,
                        pedido_4,
                        pedido_5,
                        estado_autorizacion,
                        autorizacion_adicional,
                        pedido_autorizacion_previo,
                        orden_visual,
                        id_usuario_guardado,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ";
                $stmtDetalle = mysqli_prepare($db, $sqlDetalle);
                if (!$stmtDetalle) {
                    throw new RuntimeException('No se pudo preparar el detalle del snapshot.');
                }

                foreach ($filas as $fila) {
                    $tipoFila = $fila['tipo_fila'];
                    $idTarea = $fila['id_tarea'];
                    $tareaNro = $fila['tarea_nro'];
                    $tareaTitulo = $fila['tarea_titulo'];
                    $idMaterial = (int)$fila['id_material'];
                    $materialTexto = $fila['material_texto'];
                    $cantidadInicial = $fila['cantidad_inicial'];
                    $cantidadSolicitada = $fila['cantidad_solicitada'];
                    $pedido1 = $fila['pedido_1'];
                    $pedido2 = $fila['pedido_2'];
                    $pedido3 = $fila['pedido_3'];
                    $pedido4 = $fila['pedido_4'];
                    $pedido5 = $fila['pedido_5'];
                    $estadoAutorizacion = $fila['estado_autorizacion'];
                    $autorizacionAdicional = $fila['autorizacion_adicional'];
                    $pedidoAutorizacionPrevio = $fila['pedido_autorizacion_previo'];
                    $ordenVisual = (int)$fila['orden_visual'];

                    mysqli_stmt_bind_param(
                        $stmtDetalle,
                        'isiisisdddddddsddii',
                        $snapshotId,
                        $tipoFila,
                        $idTarea,
                        $tareaNro,
                        $tareaTitulo,
                        $idMaterial,
                        $materialTexto,
                        $cantidadInicial,
                        $cantidadSolicitada,
                        $pedido1,
                        $pedido2,
                        $pedido3,
                        $pedido4,
                        $pedido5,
                        $estadoAutorizacion,
                        $autorizacionAdicional,
                        $pedidoAutorizacionPrevio,
                        $ordenVisual,
                        $idUsuario
                    );

                    if (!mysqli_stmt_execute($stmtDetalle)) {
                        $error = mysqli_stmt_error($stmtDetalle);
                        mysqli_stmt_close($stmtDetalle);
                        throw new RuntimeException('No se pudo guardar una fila del snapshot: ' . $error);
                    }
                }

                mysqli_stmt_close($stmtDetalle);
            }

            mysqli_commit($db);

            return [
                'id_pedido_materiales_snapshot' => $snapshotId,
                'id_previsita' => $idPrevisita,
                'pedido_activo' => $pedidoActivo,
                'pedido_maximo_visible' => $pedidoMaximoVisible,
                'finalizado' => $finalizado === 1,
                'accion_guardado' => $accionGuardado,
                'total_filas' => count($filas),
            ];
        } catch (Throwable $e) {
            mysqli_rollback($db);
            throw $e;
        }
    }
}
