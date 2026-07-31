<?php

require_once __DIR__ . '/schemaIntrospectionModel.php';
require_once __DIR__ . '/ordenCompraWorkflowModel.php';
require_once __DIR__ . '/pedidoMaterialesSnapshotModel.php';
require_once __DIR__ . '/pedidoMaterialesAutorizacionesModel.php';

if (!function_exists('pedidoMaterialesPedidosTablasMinimasDisponibles')) {
    function pedidoMaterialesPedidosTablasMinimasDisponibles(mysqli $db): bool
    {
        return tabla_existe($db, 'pedido_materiales_pedidos')
            && tabla_existe($db, 'pedido_materiales_pedido_detalles');
    }
}

if (!function_exists('recortarTextoPedidoMaterialesConfirmado')) {
    function recortarTextoPedidoMaterialesConfirmado($valor, int $maximo): string
    {
        $texto = trim((string)$valor);

        return function_exists('mb_substr')
            ? mb_substr($texto, 0, $maximo, 'UTF-8')
            : substr($texto, 0, $maximo);
    }
}

if (!function_exists('obtenerDetallesConfirmacionPedidoMateriales')) {
    function obtenerDetallesConfirmacionPedidoMateriales(array $snapshot, int $numeroPedido): array
    {
        $detalles = [];

        foreach ([
            'materiales_presupuestados' => 'presupuestado',
            'materiales_agregados' => 'agregado',
        ] as $claveFilas => $tipoFila) {
            foreach ((array)($snapshot[$claveFilas] ?? []) as $indice => $fila) {
                $estadoAutorizacion = trim((string)($fila['estado_autorizacion'] ?? 'sin_solicitud'));
                if (!in_array($estadoAutorizacion, pedidoMaterialesSnapshotEstadosPermitidos(), true)) {
                    $estadoAutorizacion = 'sin_solicitud';
                }

                if ($estadoAutorizacion === 'pendiente') {
                    throw new RuntimeException(
                        'No se puede confirmar el pedido mientras existan autorizaciones pendientes.',
                        422
                    );
                }

                $pedidos = (array)($fila['pedidos'] ?? []);
                $cantidadPedido = normalizarDecimalPedidoMaterialesSnapshotEntrada(
                    $pedidos[$numeroPedido] ?? $pedidos[(string)$numeroPedido] ?? 0
                );
                if ($cantidadPedido <= 0) {
                    continue;
                }

                $idMaterial = (int)($fila['id_material'] ?? 0);
                $materialTexto = recortarTextoPedidoMaterialesConfirmado(
                    $fila['material_texto'] ?? '',
                    500
                );
                if ($idMaterial <= 0 || $materialTexto === '') {
                    throw new RuntimeException(
                        'El pedido contiene un material invalido para confirmar.',
                        422
                    );
                }

                $detalles[] = [
                    'tipo_fila' => $tipoFila,
                    'id_tarea' => isset($fila['id_tarea']) && (int)$fila['id_tarea'] > 0
                        ? (int)$fila['id_tarea']
                        : null,
                    'tarea_nro' => isset($fila['tarea_nro']) && (int)$fila['tarea_nro'] > 0
                        ? (int)$fila['tarea_nro']
                        : null,
                    'tarea_titulo' => recortarTextoPedidoMaterialesConfirmado(
                        $fila['tarea_titulo'] ?? '',
                        255
                    ),
                    'id_material' => $idMaterial,
                    'material_texto' => $materialTexto,
                    'cantidad_inicial' => normalizarDecimalPedidoMaterialesSnapshotEntrada(
                        $fila['cantidad_inicial'] ?? 0
                    ),
                    'cantidad_pedido' => $cantidadPedido,
                    'estado_autorizacion' => $estadoAutorizacion,
                    'orden_visual' => max(0, (int)($fila['orden_visual'] ?? ($indice + 1))),
                ];
            }
        }

        if (!$detalles) {
            throw new RuntimeException(
                'El pedido debe contener al menos un material con cantidad mayor que cero.',
                422
            );
        }

        return $detalles;
    }
}

if (!function_exists('calcularHashConfirmacionPedidoMateriales')) {
    function calcularHashConfirmacionPedidoMateriales(
        int $idPrevisita,
        int $numeroPedido,
        array $detalles
    ): string {
        $filasHash = [];

        foreach ($detalles as $detalle) {
            $filasHash[] = [
                'tipo_fila' => (string)$detalle['tipo_fila'],
                'id_tarea' => $detalle['id_tarea'],
                'tarea_nro' => $detalle['tarea_nro'],
                'tarea_titulo' => (string)$detalle['tarea_titulo'],
                'id_material' => (int)$detalle['id_material'],
                'material_texto' => (string)$detalle['material_texto'],
                'cantidad_inicial' => number_format((float)$detalle['cantidad_inicial'], 4, '.', ''),
                'cantidad_pedido' => number_format((float)$detalle['cantidad_pedido'], 4, '.', ''),
                'estado_autorizacion' => (string)$detalle['estado_autorizacion'],
                'orden_visual' => (int)$detalle['orden_visual'],
            ];
        }

        $json = json_encode([
            'id_previsita' => $idPrevisita,
            'numero_pedido' => $numeroPedido,
            'detalles' => $filasHash,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!is_string($json)) {
            throw new RuntimeException('No se pudo preparar la confirmacion del pedido.', 500);
        }

        return hash('sha256', $json);
    }
}

if (!function_exists('obtenerPedidoMaterialesConfirmadoEnConexion')) {
    function obtenerPedidoMaterialesConfirmadoEnConexion(
        mysqli $db,
        int $idPrevisita,
        int $numeroPedido,
        bool $bloquear = false
    ): ?array {
        $sql = "
            SELECT
                id_pedido_materiales_pedido,
                id_previsita,
                id_presupuesto,
                id_orden_compra,
                numero_pedido,
                estado,
                id_usuario_confirmacion,
                fecha_confirmacion,
                snapshot_hash,
                created_at,
                updated_at
            FROM pedido_materiales_pedidos
            WHERE id_previsita = ?
              AND numero_pedido = ?
            LIMIT 1
        ";
        if ($bloquear) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            throw new RuntimeException('No se pudo consultar el pedido confirmado.', 500);
        }

        mysqli_stmt_bind_param($stmt, 'ii', $idPrevisita, $numeroPedido);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('No se pudo consultar el pedido confirmado.', 500);
        }

        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $row ?: null;
    }
}

if (!function_exists('obtenerOrdenCompraHabilitantePedidoMaterialesEnConexion')) {
    function obtenerOrdenCompraHabilitantePedidoMaterialesEnConexion(
        mysqli $db,
        int $idPrevisita
    ): ?array {
        $sql = "
            SELECT id_presupuesto
            FROM presupuestos
            WHERE id_previsita = ?
            ORDER BY version DESC, created_at DESC, id_presupuesto DESC
            LIMIT 1
        ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            throw new RuntimeException('No se pudo validar el presupuesto de la previsita.', 500);
        }

        mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('No se pudo validar el presupuesto de la previsita.', 500);
        }

        $result = mysqli_stmt_get_result($stmt);
        $presupuesto = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if (!$presupuesto || (int)($presupuesto['id_presupuesto'] ?? 0) <= 0) {
            return null;
        }

        $ordenCompra = obtenerOrdenCompraActivaPorPresupuestoEnConexion(
            $db,
            (int)$presupuesto['id_presupuesto']
        );

        if (
            !$ordenCompra
            || (int)($ordenCompra['id_previsita'] ?? 0) !== $idPrevisita
        ) {
            return null;
        }

        return $ordenCompra;
    }
}

if (!function_exists('contarDetallesPedidoMaterialesConfirmadoEnConexion')) {
    function contarDetallesPedidoMaterialesConfirmadoEnConexion(
        mysqli $db,
        int $idPedidoMaterialesPedido
    ): int {
        $stmt = mysqli_prepare(
            $db,
            'SELECT COUNT(*) AS total FROM pedido_materiales_pedido_detalles WHERE id_pedido_materiales_pedido = ?'
        );
        if (!$stmt) {
            return 0;
        }

        mysqli_stmt_bind_param($stmt, 'i', $idPedidoMaterialesPedido);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return (int)($row['total'] ?? 0);
    }
}

if (!function_exists('confirmarPedidoMaterialesEnConexion')) {
    function confirmarPedidoMaterialesEnConexion(
        mysqli $db,
        array $snapshot,
        int $numeroPedido,
        int $idUsuario
    ): array {
        if (!pedidoMaterialesPedidosTablasMinimasDisponibles($db)) {
            throw new RuntimeException(
                'Las tablas de pedidos confirmados no estan disponibles. Debe aplicarse la migracion 2026-07-29-A_pedido_materiales_pedidos_confirmados.sql.',
                409
            );
        }

        $idPrevisita = (int)($snapshot['id_previsita'] ?? 0);
        if ($idPrevisita <= 0 || $numeroPedido < 1 || $numeroPedido > 5 || $idUsuario <= 0) {
            throw new RuntimeException('Los datos de confirmacion del pedido son invalidos.', 422);
        }

        $detalles = obtenerDetallesConfirmacionPedidoMateriales($snapshot, $numeroPedido);
        $snapshotHash = calcularHashConfirmacionPedidoMateriales(
            $idPrevisita,
            $numeroPedido,
            $detalles
        );

        mysqli_begin_transaction($db);

        try {
            $stmtPrevisita = mysqli_prepare(
                $db,
                'SELECT id_previsita FROM previsitas WHERE id_previsita = ? LIMIT 1 FOR UPDATE'
            );
            if (!$stmtPrevisita) {
                throw new RuntimeException('No se pudo bloquear la previsita para confirmar el pedido.', 500);
            }
            mysqli_stmt_bind_param($stmtPrevisita, 'i', $idPrevisita);
            if (!mysqli_stmt_execute($stmtPrevisita)) {
                mysqli_stmt_close($stmtPrevisita);
                throw new RuntimeException('No se pudo bloquear la previsita para confirmar el pedido.', 500);
            }
            $resultPrevisita = mysqli_stmt_get_result($stmtPrevisita);
            $previsita = $resultPrevisita ? mysqli_fetch_assoc($resultPrevisita) : null;
            mysqli_stmt_close($stmtPrevisita);

            if (!$previsita) {
                throw new RuntimeException('No se encontro la previsita informada.', 404);
            }

            $pedidoExistente = obtenerPedidoMaterialesConfirmadoEnConexion(
                $db,
                $idPrevisita,
                $numeroPedido,
                true
            );
            if ($pedidoExistente) {
                if (!hash_equals((string)$pedidoExistente['snapshot_hash'], $snapshotHash)) {
                    throw new RuntimeException(
                        'El pedido ya fue confirmado anteriormente con un detalle diferente.',
                        409
                    );
                }

                $idPedidoExistente = (int)$pedidoExistente['id_pedido_materiales_pedido'];
                $totalDetallesExistente = contarDetallesPedidoMaterialesConfirmadoEnConexion(
                    $db,
                    $idPedidoExistente
                );
                if ($totalDetallesExistente <= 0) {
                    throw new RuntimeException(
                        'El pedido confirmado existente no tiene un detalle valido.',
                        409
                    );
                }
                mysqli_commit($db);

                return [
                    'id_pedido_materiales_pedido' => $idPedidoExistente,
                    'numero_pedido' => (int)$pedidoExistente['numero_pedido'],
                    'ya_existia' => true,
                    'fecha_confirmacion' => (string)$pedidoExistente['fecha_confirmacion'],
                    'total_detalles' => $totalDetallesExistente,
                ];
            }

            $snapshot = protegerSnapshotConAutorizacionesFormalesPedidoMateriales($db, $snapshot);
            validarAutorizacionesFormalesConfirmacionPedidoMateriales(
                $db,
                $snapshot,
                $numeroPedido
            );
            $detalles = obtenerDetallesConfirmacionPedidoMateriales($snapshot, $numeroPedido);
            $snapshotHash = calcularHashConfirmacionPedidoMateriales(
                $idPrevisita,
                $numeroPedido,
                $detalles
            );

            $ordenCompra = obtenerOrdenCompraHabilitantePedidoMaterialesEnConexion(
                $db,
                $idPrevisita
            );
            if (!$ordenCompra || !esEstadoOrdenCompraActiva($ordenCompra['estado'] ?? null)) {
                throw new RuntimeException(
                    'Pedido de Materiales no esta habilitado: la previsita no tiene una Orden de Compra activa cargada u observada.',
                    409
                );
            }

            $idPresupuesto = (int)($ordenCompra['id_presupuesto'] ?? 0);
            $idOrdenCompra = (int)($ordenCompra['id_orden_compra'] ?? 0);
            if ($idPresupuesto <= 0 || $idOrdenCompra <= 0) {
                throw new RuntimeException(
                    'La Orden de Compra habilitante no tiene referencias validas.',
                    409
                );
            }
            $estado = 'confirmado';
            $sqlCabecera = "
                INSERT INTO pedido_materiales_pedidos (
                    id_previsita,
                    id_presupuesto,
                    id_orden_compra,
                    numero_pedido,
                    estado,
                    id_usuario_confirmacion,
                    fecha_confirmacion,
                    snapshot_hash,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, NOW(), NOW())
            ";
            $stmtCabecera = mysqli_prepare($db, $sqlCabecera);
            if (!$stmtCabecera) {
                throw new RuntimeException('No se pudo preparar la cabecera del pedido confirmado.', 500);
            }
            mysqli_stmt_bind_param(
                $stmtCabecera,
                'iiiisis',
                $idPrevisita,
                $idPresupuesto,
                $idOrdenCompra,
                $numeroPedido,
                $estado,
                $idUsuario,
                $snapshotHash
            );
            if (!mysqli_stmt_execute($stmtCabecera)) {
                mysqli_stmt_close($stmtCabecera);
                throw new RuntimeException('No se pudo guardar la cabecera del pedido confirmado.', 500);
            }
            $idPedidoMaterialesPedido = (int)mysqli_insert_id($db);
            mysqli_stmt_close($stmtCabecera);

            $sqlDetalle = "
                INSERT INTO pedido_materiales_pedido_detalles (
                    id_pedido_materiales_pedido,
                    tipo_fila,
                    id_tarea,
                    tarea_nro,
                    tarea_titulo,
                    id_material,
                    material_texto,
                    cantidad_inicial,
                    cantidad_pedido,
                    estado_autorizacion,
                    orden_visual,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";
            $stmtDetalle = mysqli_prepare($db, $sqlDetalle);
            if (!$stmtDetalle) {
                throw new RuntimeException('No se pudo preparar el detalle del pedido confirmado.', 500);
            }

            foreach ($detalles as $detalle) {
                $tipoFila = (string)$detalle['tipo_fila'];
                $idTarea = $detalle['id_tarea'];
                $tareaNro = $detalle['tarea_nro'];
                $tareaTitulo = (string)$detalle['tarea_titulo'];
                $idMaterial = (int)$detalle['id_material'];
                $materialTexto = (string)$detalle['material_texto'];
                $cantidadInicial = (float)$detalle['cantidad_inicial'];
                $cantidadPedido = (float)$detalle['cantidad_pedido'];
                $estadoAutorizacion = (string)$detalle['estado_autorizacion'];
                $ordenVisual = (int)$detalle['orden_visual'];

                mysqli_stmt_bind_param(
                    $stmtDetalle,
                    'isiisisddsi',
                    $idPedidoMaterialesPedido,
                    $tipoFila,
                    $idTarea,
                    $tareaNro,
                    $tareaTitulo,
                    $idMaterial,
                    $materialTexto,
                    $cantidadInicial,
                    $cantidadPedido,
                    $estadoAutorizacion,
                    $ordenVisual
                );
                if (!mysqli_stmt_execute($stmtDetalle)) {
                    mysqli_stmt_close($stmtDetalle);
                    throw new RuntimeException('No se pudo guardar el detalle del pedido confirmado.', 500);
                }
            }
            mysqli_stmt_close($stmtDetalle);

            $pedidoGuardado = obtenerPedidoMaterialesConfirmadoEnConexion(
                $db,
                $idPrevisita,
                $numeroPedido
            );
            if (!$pedidoGuardado) {
                throw new RuntimeException('No se pudo verificar el pedido confirmado.', 500);
            }

            mysqli_commit($db);

            return [
                'id_pedido_materiales_pedido' => $idPedidoMaterialesPedido,
                'numero_pedido' => $numeroPedido,
                'ya_existia' => false,
                'fecha_confirmacion' => (string)$pedidoGuardado['fecha_confirmacion'],
                'total_detalles' => count($detalles),
            ];
        } catch (Throwable $e) {
            mysqli_rollback($db);
            throw $e;
        }
    }
}
