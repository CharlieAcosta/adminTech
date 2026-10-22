<?php

// Historial de intervinientes de Pedido de materiales (solo lectura).
//
// Reutiliza el formato de resumen de los demas acordeones
// (construirResumenIntervencionesPresupuesto: ultimo_texto + popover_html) y se alimenta
// exclusivamente de datos que el circuito ya persiste:
//   - pedido_materiales_pedidos:          Realizar pedido #N (usuario y fecha de confirmacion).
//   - pedido_materiales_autorizaciones:   Autorizar / Rechazar pedido #N (usuario y fecha de resolucion).
//   - pedido_materiales_snapshots:        ultimo guardado vigente (usuario y fecha).
// No registra eventos nuevos ni escribe en la base. Las intervenciones intermedias que el
// circuito no persiste (guardados anteriores, solicitudes de autorizacion) no se reconstruyen.

require_once __DIR__ . '/schemaIntrospectionModel.php';
require_once __DIR__ . '/presupuestoIntervencionesModel.php';

if (!function_exists('nombreUsuarioIntervencionPedidoMaterialesEnConexion')) {
    function nombreUsuarioIntervencionPedidoMaterialesEnConexion(mysqli $db, int $idUsuario, array &$cache): string
    {
        if ($idUsuario <= 0) {
            return 'Usuario no informado';
        }
        if (isset($cache[$idUsuario])) {
            return $cache[$idUsuario];
        }

        $nombre = '';
        $stmt = mysqli_prepare($db, 'SELECT apellidos, nombres FROM usuarios WHERE id_usuario = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $idUsuario);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);
            if ($row) {
                $nombre = trim((string)($row['apellidos'] ?? '') . ' ' . (string)($row['nombres'] ?? ''));
            }
        }

        return $cache[$idUsuario] = ($nombre !== '' ? $nombre : 'Usuario #' . $idUsuario);
    }
}

if (!function_exists('obtenerIntervencionesPedidoMaterialesEnConexion')) {
    /**
     * @return array[] items ordenados del mas reciente al mas antiguo, con las claves
     *                 usuario_nombre, accion_label, fecha_texto, created_at (formato del resumen compartido).
     */
    function obtenerIntervencionesPedidoMaterialesEnConexion(mysqli $db, int $idPrevisita): array
    {
        if ($idPrevisita <= 0) {
            return [];
        }

        $eventos = [];
        $cacheUsuarios = [];

        // 1) Pedidos confirmados (uno por ciclo).
        $confirmaciones = [];
        if (tabla_existe($db, 'pedido_materiales_pedidos')) {
            $stmt = mysqli_prepare(
                $db,
                'SELECT numero_pedido, id_usuario_confirmacion, fecha_confirmacion
                 FROM pedido_materiales_pedidos
                 WHERE id_previsita = ?'
            );
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                while ($res && ($row = mysqli_fetch_assoc($res))) {
                    $fecha = (string)($row['fecha_confirmacion'] ?? '');
                    if ($fecha === '') {
                        continue;
                    }
                    $idUsuario = (int)$row['id_usuario_confirmacion'];
                    $confirmaciones[] = ['usuario' => $idUsuario, 'ts' => strtotime($fecha)];
                    $eventos[] = [
                        'ts' => strtotime($fecha),
                        'orden' => 2,
                        'created_at' => $fecha,
                        'usuario_nombre' => nombreUsuarioIntervencionPedidoMaterialesEnConexion($db, $idUsuario, $cacheUsuarios),
                        'accion_label' => 'Realizar pedido #' . (int)$row['numero_pedido'],
                    ];
                }
                mysqli_stmt_close($stmt);
            }
        }

        // 2) Resoluciones de autorizacion (una por material; se agrupan las del mismo acto).
        if (tabla_existe($db, 'pedido_materiales_autorizaciones')) {
            $stmt = mysqli_prepare(
                $db,
                'SELECT numero_pedido, estado_autorizacion, id_usuario_autorizacion, fecha_autorizacion, COUNT(*) AS total
                 FROM pedido_materiales_autorizaciones
                 WHERE id_previsita = ?
                 GROUP BY numero_pedido, estado_autorizacion, id_usuario_autorizacion, fecha_autorizacion'
            );
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                while ($res && ($row = mysqli_fetch_assoc($res))) {
                    $fecha = (string)($row['fecha_autorizacion'] ?? '');
                    if ($fecha === '') {
                        continue;
                    }
                    $verbo = (string)$row['estado_autorizacion'] === 'rechazada' ? 'Rechazar' : 'Autorizar';
                    $total = (int)$row['total'];
                    $eventos[] = [
                        'ts' => strtotime($fecha),
                        'orden' => 1,
                        'created_at' => $fecha,
                        'usuario_nombre' => nombreUsuarioIntervencionPedidoMaterialesEnConexion(
                            $db,
                            (int)$row['id_usuario_autorizacion'],
                            $cacheUsuarios
                        ),
                        'accion_label' => $verbo . ' pedido #' . (int)$row['numero_pedido']
                            . ($total > 1 ? ' (' . $total . ' materiales)' : ''),
                    ];
                }
                mysqli_stmt_close($stmt);
            }
        }

        // 3) Ultimo guardado vigente del snapshot (el circuito solo persiste el ultimo).
        if (tabla_existe($db, 'pedido_materiales_snapshots')) {
            $stmt = mysqli_prepare(
                $db,
                'SELECT accion_guardado, id_usuario_guardado, updated_at
                 FROM pedido_materiales_snapshots
                 WHERE id_previsita = ?
                 LIMIT 1'
            );
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = $res ? mysqli_fetch_assoc($res) : null;
                mysqli_stmt_close($stmt);

                $fecha = (string)($row['updated_at'] ?? '');
                if ($row && $fecha !== '') {
                    $ts = strtotime($fecha);
                    $idUsuario = (int)$row['id_usuario_guardado'];
                    $esRealizar = (string)$row['accion_guardado'] === 'realizar';

                    // Un "realizar" ya queda representado por el pedido confirmado correspondiente.
                    $yaRepresentado = false;
                    if ($esRealizar) {
                        foreach ($confirmaciones as $c) {
                            if ($c['usuario'] === $idUsuario && abs($c['ts'] - $ts) <= 5) {
                                $yaRepresentado = true;
                                break;
                            }
                        }
                    }

                    if (!$yaRepresentado) {
                        $eventos[] = [
                            'ts' => $ts,
                            'orden' => 0,
                            'created_at' => $fecha,
                            'usuario_nombre' => nombreUsuarioIntervencionPedidoMaterialesEnConexion($db, $idUsuario, $cacheUsuarios),
                            'accion_label' => $esRealizar ? 'Realizar pedido' : 'Guardar pedido',
                        ];
                    }
                }
            }
        }

        usort($eventos, function ($a, $b) {
            return [$b['ts'], $b['orden']] <=> [$a['ts'], $a['orden']];
        });

        $items = [];
        foreach ($eventos as $evento) {
            $items[] = [
                'usuario_nombre' => $evento['usuario_nombre'],
                'accion_label' => $evento['accion_label'],
                'created_at' => $evento['created_at'],
                'fecha_texto' => formatearFechaIntervencionPresupuesto($evento['created_at']),
            ];
        }

        return $items;
    }
}

if (!function_exists('obtenerResumenIntervencionesPedidoMaterialesEnConexion')) {
    function obtenerResumenIntervencionesPedidoMaterialesEnConexion(mysqli $db, int $idPrevisita): array
    {
        return construirResumenIntervencionesPresupuesto(
            obtenerIntervencionesPedidoMaterialesEnConexion($db, $idPrevisita)
        );
    }
}
