<?php

require_once __DIR__ . '/schemaIntrospectionModel.php';

if (!function_exists('pedidoMaterialesAutorizacionesTablaDisponible')) {
    function pedidoMaterialesAutorizacionesTablaDisponible(mysqli $db): bool
    {
        return tabla_existe($db, 'pedido_materiales_autorizaciones');
    }
}

if (!function_exists('normalizarDecimalAutorizacionPedidoMateriales')) {
    function normalizarDecimalAutorizacionPedidoMateriales($valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        if (is_string($valor)) {
            $valor = trim($valor);
            if (strpos($valor, ',') !== false) {
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
            } elseif (preg_match('/^-?\d{1,3}(?:\.\d{3})+$/', $valor)) {
                $valor = str_replace('.', '', $valor);
            }
        }

        $numero = (float)$valor;
        if (!is_finite($numero) || $numero < 0) {
            return 0.0;
        }

        return round($numero, 4);
    }
}

if (!function_exists('normalizarTipoFilaAutorizacionPedidoMateriales')) {
    function normalizarTipoFilaAutorizacionPedidoMateriales($tipo): string
    {
        return trim((string)$tipo) === 'agregado' ? 'agregado' : 'presupuestado';
    }
}

if (!function_exists('construirClaveFilaAutorizacionPedidoMateriales')) {
    function construirClaveFilaAutorizacionPedidoMateriales(array $fila): string
    {
        $tipoFila = normalizarTipoFilaAutorizacionPedidoMateriales($fila['tipo_fila'] ?? '');
        $identidad = [
            'tipo_fila' => $tipoFila,
            'id_material' => (int)($fila['id_material'] ?? 0),
            'tarea_nro' => isset($fila['tarea_nro']) && (int)$fila['tarea_nro'] > 0
                ? (int)$fila['tarea_nro']
                : null,
            // Los agregados son unicos por material en la UI y pueden reordenarse al eliminar otra fila.
            // Las filas presupuestadas no se eliminan, por lo que su orden desambigua duplicados de una tarea.
            'orden_visual' => $tipoFila === 'presupuestado'
                ? max(1, (int)($fila['orden_visual'] ?? 1))
                : null,
        ];

        return hash('sha256', json_encode($identidad, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

if (!function_exists('obtenerContextoFilaAutorizacionPedidoMateriales')) {
    function obtenerContextoFilaAutorizacionPedidoMateriales(array $fila, int $numeroPedido): array
    {
        $tipoFila = normalizarTipoFilaAutorizacionPedidoMateriales($fila['tipo_fila'] ?? '');
        $pedidos = (array)($fila['pedidos'] ?? []);
        $cantidadInicial = normalizarDecimalAutorizacionPedidoMateriales($fila['cantidad_inicial'] ?? 0);
        $cantidadPedido = 0.0;
        $cantidadAcumulada = 0.0;

        for ($numero = 1; $numero <= 5; $numero += 1) {
            $valor = $pedidos[$numero]
                ?? $pedidos[(string)$numero]
                ?? $fila['pedido_' . $numero]
                ?? 0;
            $cantidad = normalizarDecimalAutorizacionPedidoMateriales($valor);
            if ($numero <= $numeroPedido) {
                $cantidadAcumulada = round($cantidadAcumulada + $cantidad, 4);
            }
            if ($numero === $numeroPedido) {
                $cantidadPedido = $cantidad;
            }
        }

        $cantidadPrevia = normalizarDecimalAutorizacionPedidoMateriales(
            $fila['pedido_autorizacion_previo'] ?? 0
        );
        $requiereAutorizacion = $tipoFila === 'agregado'
            ? $cantidadPedido > 0.00005
            : $cantidadAcumulada > ($cantidadInicial + 0.00005);
        $claveFila = construirClaveFilaAutorizacionPedidoMateriales($fila);
        $datosHash = [
            'clave_fila' => $claveFila,
            'numero_pedido' => $numeroPedido,
            'cantidad_inicial' => number_format($cantidadInicial, 4, '.', ''),
            'cantidad_pedido' => number_format($cantidadPedido, 4, '.', ''),
            'cantidad_acumulada' => number_format($cantidadAcumulada, 4, '.', ''),
        ];

        return [
            'clave_fila' => $claveFila,
            'tipo_fila' => $tipoFila,
            'id_material' => (int)($fila['id_material'] ?? 0),
            'tarea_nro' => isset($fila['tarea_nro']) && (int)$fila['tarea_nro'] > 0
                ? (int)$fila['tarea_nro']
                : null,
            'orden_visual' => max(1, (int)($fila['orden_visual'] ?? 1)),
            'cantidad_inicial' => $cantidadInicial,
            'cantidad_pedido' => $cantidadPedido,
            'cantidad_acumulada' => $cantidadAcumulada,
            'cantidad_pedido_previa' => $cantidadPrevia,
            'requiere_autorizacion' => $requiereAutorizacion,
            'contexto_hash' => hash(
                'sha256',
                json_encode($datosHash, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ),
        ];
    }
}

if (!function_exists('obtenerAutorizacionFormalPedidoMateriales')) {
    function obtenerAutorizacionFormalPedidoMateriales(
        mysqli $db,
        int $idPrevisita,
        int $numeroPedido,
        string $claveFila,
        bool $bloquear = false
    ): ?array {
        $sql = "
            SELECT *
            FROM pedido_materiales_autorizaciones
            WHERE id_previsita = ?
              AND numero_pedido = ?
              AND clave_fila = ?
            LIMIT 1
        ";
        if ($bloquear) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            throw new RuntimeException('No se pudo consultar la autorizacion formal.', 500);
        }
        mysqli_stmt_bind_param($stmt, 'iis', $idPrevisita, $numeroPedido, $claveFila);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('No se pudo consultar la autorizacion formal.', 500);
        }
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $row ?: null;
    }
}

if (!function_exists('recalcularCantidadSolicitadaAutorizacionPedidoMateriales')) {
    function recalcularCantidadSolicitadaAutorizacionPedidoMateriales(array $fila): float
    {
        $pedidos = (array)($fila['pedidos'] ?? []);
        $total = 0.0;
        for ($numero = 1; $numero <= 5; $numero += 1) {
            $total += normalizarDecimalAutorizacionPedidoMateriales(
                $pedidos[$numero] ?? $pedidos[(string)$numero] ?? $fila['pedido_' . $numero] ?? 0
            );
        }

        return round($total, 4);
    }
}

if (!function_exists('protegerSnapshotConAutorizacionesFormalesPedidoMateriales')) {
    function protegerSnapshotConAutorizacionesFormalesPedidoMateriales(
        mysqli $db,
        array $snapshot,
        bool $bloquear = false
    ): array {
        if (!pedidoMaterialesAutorizacionesTablaDisponible($db)) {
            throw new RuntimeException(
                'La persistencia formal de autorizaciones no esta disponible. Debe aplicarse la migracion 2026-07-31-A_pedido_materiales_autorizaciones_backend.sql.',
                409
            );
        }

        $idPrevisita = (int)($snapshot['id_previsita'] ?? 0);
        $numeroPedido = (int)($snapshot['pedido_activo'] ?? 0);
        if ($idPrevisita <= 0 || $numeroPedido < 1 || $numeroPedido > 5) {
            throw new RuntimeException('El snapshot no identifica un pedido activo valido.', 422);
        }

        foreach (['materiales_presupuestados', 'materiales_agregados'] as $claveFilas) {
            $tipoFila = $claveFilas === 'materiales_agregados' ? 'agregado' : 'presupuestado';
            foreach ((array)($snapshot[$claveFilas] ?? []) as $indice => $fila) {
                $fila['tipo_fila'] = $tipoFila;
                $fila['orden_visual'] = $indice + 1;
                $contexto = obtenerContextoFilaAutorizacionPedidoMateriales($fila, $numeroPedido);
                $estadoCliente = trim((string)($fila['estado_autorizacion'] ?? 'sin_solicitud'));
                $autorizacion = obtenerAutorizacionFormalPedidoMateriales(
                    $db,
                    $idPrevisita,
                    $numeroPedido,
                    $contexto['clave_fila'],
                    $bloquear
                );

                if (!$autorizacion) {
                    if (in_array($estadoCliente, ['autorizada', 'rechazada'], true)) {
                        throw new RuntimeException(
                            'El snapshot intenta guardar una autorizacion sin decision backend formal.',
                            422
                        );
                    }
                    if ($contexto['requiere_autorizacion'] && $estadoCliente !== 'pendiente') {
                        throw new RuntimeException(
                            'La cantidad informada requiere una solicitud de autorizacion pendiente.',
                            422
                        );
                    }
                    if (!$contexto['requiere_autorizacion'] && $estadoCliente === 'pendiente') {
                        throw new RuntimeException(
                            'La fila no requiere una solicitud de autorizacion.',
                            422
                        );
                    }
                    $snapshot[$claveFilas][$indice] = $fila;
                    continue;
                }

                $estadoFormal = (string)$autorizacion['estado_autorizacion'];
                if ($estadoFormal === 'rechazada') {
                    $pedidos = (array)($fila['pedidos'] ?? []);
                    $pedidos[$numeroPedido] = normalizarDecimalAutorizacionPedidoMateriales(
                        $autorizacion['cantidad_pedido_previa'] ?? 0
                    );
                    $fila['pedidos'] = $pedidos;
                    $fila['cantidad_solicitada'] = recalcularCantidadSolicitadaAutorizacionPedidoMateriales($fila);
                } elseif ($contexto['requiere_autorizacion']
                    && !hash_equals((string)$autorizacion['contexto_hash'], $contexto['contexto_hash'])) {
                    throw new RuntimeException(
                        'La cantidad autorizada fue modificada. Debe generar una nueva solicitud.',
                        409
                    );
                }

                $fila['estado_autorizacion'] = $estadoFormal;
                $fila['autorizacion_adicional'] = null;
                $fila['pedido_autorizacion_previo'] = null;
                $fila['id_usuario_autorizacion'] = (int)$autorizacion['id_usuario_autorizacion'];
                $fila['fecha_autorizacion'] = (string)$autorizacion['fecha_autorizacion'];
                $fila['motivo_autorizacion'] = $autorizacion['motivo_autorizacion'];
                $snapshot[$claveFilas][$indice] = $fila;
            }
        }

        return $snapshot;
    }
}

if (!function_exists('validarAutorizacionesFormalesConfirmacionPedidoMateriales')) {
    function validarAutorizacionesFormalesConfirmacionPedidoMateriales(
        mysqli $db,
        array $snapshot,
        int $numeroPedido
    ): void {
        $idPrevisita = (int)($snapshot['id_previsita'] ?? 0);
        foreach (['materiales_presupuestados', 'materiales_agregados'] as $claveFilas) {
            $tipoFila = $claveFilas === 'materiales_agregados' ? 'agregado' : 'presupuestado';
            foreach ((array)($snapshot[$claveFilas] ?? []) as $indice => $fila) {
                $fila['tipo_fila'] = $tipoFila;
                $fila['orden_visual'] = $indice + 1;
                $contexto = obtenerContextoFilaAutorizacionPedidoMateriales($fila, $numeroPedido);
                if (!$contexto['requiere_autorizacion']) {
                    continue;
                }

                $autorizacion = obtenerAutorizacionFormalPedidoMateriales(
                    $db,
                    $idPrevisita,
                    $numeroPedido,
                    $contexto['clave_fila']
                );
                if (!$autorizacion
                    || (string)$autorizacion['estado_autorizacion'] !== 'autorizada'
                    || !hash_equals((string)$autorizacion['contexto_hash'], $contexto['contexto_hash'])) {
                    throw new RuntimeException(
                        'El pedido contiene cantidades que requieren una autorizacion backend vigente.',
                        422
                    );
                }
            }
        }
    }
}

if (!function_exists('filaSnapshotAutorizacionPedidoMaterialesDesdeDb')) {
    function filaSnapshotAutorizacionPedidoMaterialesDesdeDb(array $fila): array
    {
        return [
            'tipo_fila' => (string)$fila['tipo_fila'],
            'id_material' => (int)$fila['id_material'],
            'tarea_nro' => isset($fila['tarea_nro']) ? (int)$fila['tarea_nro'] : null,
            'orden_visual' => (int)$fila['orden_visual'],
            'cantidad_inicial' => normalizarDecimalAutorizacionPedidoMateriales($fila['cantidad_inicial'] ?? 0),
            'pedidos' => [
                1 => normalizarDecimalAutorizacionPedidoMateriales($fila['pedido_1'] ?? 0),
                2 => normalizarDecimalAutorizacionPedidoMateriales($fila['pedido_2'] ?? 0),
                3 => normalizarDecimalAutorizacionPedidoMateriales($fila['pedido_3'] ?? 0),
                4 => normalizarDecimalAutorizacionPedidoMateriales($fila['pedido_4'] ?? 0),
                5 => normalizarDecimalAutorizacionPedidoMateriales($fila['pedido_5'] ?? 0),
            ],
            'pedido_autorizacion_previo' => $fila['pedido_autorizacion_previo'],
        ];
    }
}

if (!function_exists('resolverAutorizacionPedidoMaterialesEnConexion')) {
    function resolverAutorizacionPedidoMaterialesEnConexion(
        mysqli $db,
        int $idPrevisita,
        int $numeroPedido,
        string $tipoFila,
        int $idMaterial,
        ?int $tareaNro,
        int $ordenVisual,
        string $decision,
        ?string $motivo,
        int $idUsuarioAutorizacion
    ): array {
        if (!pedidoMaterialesAutorizacionesTablaDisponible($db)) {
            throw new RuntimeException(
                'La persistencia formal de autorizaciones no esta disponible. Debe aplicarse la migracion 2026-07-31-A_pedido_materiales_autorizaciones_backend.sql.',
                409
            );
        }
        if (!in_array($decision, ['autorizada', 'rechazada'], true)) {
            throw new RuntimeException('La decision de autorizacion no es valida.', 422);
        }
        if ($idPrevisita <= 0
            || $numeroPedido < 1
            || $numeroPedido > 5
            || !in_array($tipoFila, ['presupuestado', 'agregado'], true)
            || $idMaterial <= 0
            || $ordenVisual <= 0
            || $idUsuarioAutorizacion <= 0) {
            throw new RuntimeException('Los datos de la autorizacion son invalidos.', 422);
        }

        mysqli_begin_transaction($db);
        try {
            $sql = "
                SELECT s.pedido_activo, d.*
                FROM pedido_materiales_snapshots s
                INNER JOIN pedido_materiales_snapshot_detalles d
                    ON d.id_pedido_materiales_snapshot = s.id_pedido_materiales_snapshot
                WHERE s.id_previsita = ?
                  AND d.tipo_fila = ?
                  AND d.id_material = ?
                  AND COALESCE(d.tarea_nro, 0) = ?
                  AND d.orden_visual = ?
                LIMIT 1
                FOR UPDATE
            ";
            $stmt = mysqli_prepare($db, $sql);
            if (!$stmt) {
                throw new RuntimeException('No se pudo consultar la solicitud de autorizacion.', 500);
            }
            $tareaNroConsulta = $tareaNro ?? 0;
            mysqli_stmt_bind_param(
                $stmt,
                'isiii',
                $idPrevisita,
                $tipoFila,
                $idMaterial,
                $tareaNroConsulta,
                $ordenVisual
            );
            if (!mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                throw new RuntimeException('No se pudo consultar la solicitud de autorizacion.', 500);
            }
            $result = mysqli_stmt_get_result($stmt);
            $filaDb = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            if (!$filaDb) {
                throw new RuntimeException('No se encontro la fila solicitada en el snapshot actual.', 404);
            }
            if ((int)$filaDb['pedido_activo'] !== $numeroPedido) {
                throw new RuntimeException('La solicitud ya no pertenece al pedido activo.', 409);
            }
            if ((string)$filaDb['estado_autorizacion'] !== 'pendiente') {
                throw new RuntimeException('La solicitud ya fue resuelta o cambio de estado.', 409);
            }

            $fila = filaSnapshotAutorizacionPedidoMaterialesDesdeDb($filaDb);
            $contexto = obtenerContextoFilaAutorizacionPedidoMateriales($fila, $numeroPedido);
            if (!$contexto['requiere_autorizacion']) {
                throw new RuntimeException('La fila ya no requiere autorizacion.', 409);
            }

            $existente = obtenerAutorizacionFormalPedidoMateriales(
                $db,
                $idPrevisita,
                $numeroPedido,
                $contexto['clave_fila'],
                true
            );
            if ($existente) {
                throw new RuntimeException('La solicitud ya fue resuelta o cambio de estado.', 409);
            }

            $motivo = $motivo !== null && trim($motivo) !== ''
                ? (function_exists('mb_substr')
                    ? mb_substr(trim($motivo), 0, 255, 'UTF-8')
                    : substr(trim($motivo), 0, 255))
                : null;
            $tipoFilaInsert = $contexto['tipo_fila'];
            $tareaNroInsert = $contexto['tarea_nro'];
            $claveFila = $contexto['clave_fila'];
            $cantidadInicial = $contexto['cantidad_inicial'];
            $cantidadPedido = $contexto['cantidad_pedido'];
            $cantidadAcumulada = $contexto['cantidad_acumulada'];
            $cantidadPedidoPrevia = $contexto['cantidad_pedido_previa'];
            $contextoHash = $contexto['contexto_hash'];
            $sqlInsert = "
                INSERT INTO pedido_materiales_autorizaciones (
                    id_previsita, numero_pedido, tipo_fila, id_material, tarea_nro,
                    orden_visual, clave_fila, estado_autorizacion, cantidad_inicial,
                    cantidad_pedido, cantidad_acumulada, cantidad_pedido_previa,
                    contexto_hash, id_usuario_autorizacion, fecha_autorizacion,
                    motivo_autorizacion,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW(), NOW())
            ";
            $stmtInsert = mysqli_prepare($db, $sqlInsert);
            if (!$stmtInsert) {
                throw new RuntimeException('No se pudo registrar la autorizacion formal.', 500);
            }
            mysqli_stmt_bind_param(
                $stmtInsert,
                'iisiiissddddsis',
                $idPrevisita,
                $numeroPedido,
                $tipoFilaInsert,
                $idMaterial,
                $tareaNroInsert,
                $ordenVisual,
                $claveFila,
                $decision,
                $cantidadInicial,
                $cantidadPedido,
                $cantidadAcumulada,
                $cantidadPedidoPrevia,
                $contextoHash,
                $idUsuarioAutorizacion,
                $motivo
            );
            if (!mysqli_stmt_execute($stmtInsert)) {
                $errno = mysqli_stmt_errno($stmtInsert);
                mysqli_stmt_close($stmtInsert);
                if ($errno === 1062) {
                    throw new RuntimeException('La solicitud ya fue resuelta o cambio de estado.', 409);
                }
                throw new RuntimeException('No se pudo registrar la autorizacion formal.', 500);
            }
            $idAutorizacion = (int)mysqli_insert_id($db);
            mysqli_stmt_close($stmtInsert);

            $idDetalle = (int)$filaDb['id_pedido_materiales_snapshot_detalle'];
            if ($decision === 'rechazada') {
                $pedidos = $fila['pedidos'];
                $pedidos[$numeroPedido] = $cantidadPedidoPrevia;
                $fila['pedidos'] = $pedidos;
                $cantidadSolicitada = recalcularCantidadSolicitadaAutorizacionPedidoMateriales($fila);
                $columnaPedido = 'pedido_' . $numeroPedido;
                $sqlUpdate = "
                    UPDATE pedido_materiales_snapshot_detalles
                    SET {$columnaPedido} = ?, cantidad_solicitada = ?,
                        estado_autorizacion = 'rechazada', autorizacion_adicional = NULL,
                        pedido_autorizacion_previo = NULL, updated_at = NOW()
                    WHERE id_pedido_materiales_snapshot_detalle = ?
                      AND estado_autorizacion = 'pendiente'
                ";
                $stmtUpdate = mysqli_prepare($db, $sqlUpdate);
                if (!$stmtUpdate) {
                    throw new RuntimeException('No se pudo actualizar el snapshot autorizado.', 500);
                }
                mysqli_stmt_bind_param(
                    $stmtUpdate,
                    'ddi',
                    $cantidadPedidoPrevia,
                    $cantidadSolicitada,
                    $idDetalle
                );
            } else {
                $stmtUpdate = mysqli_prepare(
                    $db,
                    "UPDATE pedido_materiales_snapshot_detalles
                     SET estado_autorizacion = 'autorizada', autorizacion_adicional = NULL,
                         pedido_autorizacion_previo = NULL, updated_at = NOW()
                     WHERE id_pedido_materiales_snapshot_detalle = ?
                       AND estado_autorizacion = 'pendiente'"
                );
                if (!$stmtUpdate) {
                    throw new RuntimeException('No se pudo actualizar el snapshot autorizado.', 500);
                }
                mysqli_stmt_bind_param($stmtUpdate, 'i', $idDetalle);
            }
            if (!$stmtUpdate || !mysqli_stmt_execute($stmtUpdate) || mysqli_stmt_affected_rows($stmtUpdate) !== 1) {
                if ($stmtUpdate) {
                    mysqli_stmt_close($stmtUpdate);
                }
                throw new RuntimeException('La solicitud ya fue resuelta o cambio de estado.', 409);
            }
            mysqli_stmt_close($stmtUpdate);

            $stmtResultado = mysqli_prepare(
                $db,
                'SELECT * FROM pedido_materiales_autorizaciones WHERE id_pedido_materiales_autorizacion = ?'
            );
            if (!$stmtResultado) {
                throw new RuntimeException('No se pudo recuperar la autorizacion registrada.', 500);
            }
            mysqli_stmt_bind_param($stmtResultado, 'i', $idAutorizacion);
            if (!mysqli_stmt_execute($stmtResultado)) {
                mysqli_stmt_close($stmtResultado);
                throw new RuntimeException('No se pudo recuperar la autorizacion registrada.', 500);
            }
            $resultResultado = mysqli_stmt_get_result($stmtResultado);
            $resultado = $resultResultado ? mysqli_fetch_assoc($resultResultado) : null;
            mysqli_stmt_close($stmtResultado);
            if (!$resultado) {
                throw new RuntimeException('No se pudo recuperar la autorizacion registrada.', 500);
            }

            mysqli_commit($db);

            return $resultado;
        } catch (Throwable $e) {
            mysqli_rollback($db);
            throw $e;
        }
    }
}
