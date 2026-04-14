<?php

require_once __DIR__ . '/../06-funciones_php/funciones.php';
require_once __DIR__ . '/conectDB.php';

if (!function_exists('normalizarModoCircuitoComercialPresupuestoLock')) {
    function normalizarModoCircuitoComercialPresupuestoLock(?string $modo): string
    {
        $modo = strtolower(trim((string)$modo));
        return in_array($modo, ['simulacion', 'smtp'], true) ? $modo : 'simulacion';
    }
}

if (!function_exists('normalizarEstadoComercialPresupuestoLock')) {
    function normalizarEstadoComercialPresupuestoLock(?string $estado): string
    {
        $estado = strtoupper(trim((string)$estado));
        return in_array($estado, ['ENVIADO', 'RECIBIDO', 'RESOLICITADO', 'APROBADO', 'RECHAZADO', 'CANCELADO'], true)
            ? $estado
            : '';
    }
}

if (!function_exists('etiquetaEstadoComercialPresupuestoLock')) {
    function etiquetaEstadoComercialPresupuestoLock(?string $estado): string
    {
        $map = [
            'ENVIADO' => 'Enviado',
            'RECIBIDO' => 'Recibido',
            'RESOLICITADO' => 'Resolicitado',
            'APROBADO' => 'Aprobado',
            'RECHAZADO' => 'Rechazado',
            'CANCELADO' => 'Cancelado',
        ];

        $estado = normalizarEstadoComercialPresupuestoLock($estado);
        return $map[$estado] ?? '';
    }
}

if (!function_exists('estadosBloqueadosEdicionComercialPresupuesto')) {
    function estadosBloqueadosEdicionComercialPresupuesto(): array
    {
        return ['APROBADO', 'RECHAZADO', 'CANCELADO'];
    }
}

if (!function_exists('estadoBloqueaEdicionComercialPresupuesto')) {
    function estadoBloqueaEdicionComercialPresupuesto(?string $estado): bool
    {
        return in_array(
            normalizarEstadoComercialPresupuestoLock($estado),
            estadosBloqueadosEdicionComercialPresupuesto(),
            true
        );
    }
}

if (!function_exists('mensajeBloqueoEdicionComercialPresupuesto')) {
    function mensajeBloqueoEdicionComercialPresupuesto(?string $estado = null): string
    {
        $label = etiquetaEstadoComercialPresupuestoLock($estado);
        if ($label !== '') {
            return 'La edicion de la visita y del presupuesto esta bloqueada porque el circuito comercial esta en ' . $label . '.';
        }

        return 'La edicion de la visita y del presupuesto esta bloqueada por el estado comercial actual.';
    }
}

if (!function_exists('obtenerModoActivoCircuitoComercialPresupuestosLock')) {
    function obtenerModoActivoCircuitoComercialPresupuestosLock(mysqli $db): string
    {
        if (!tabla_existe($db, 'configuracion_mail_presupuestos')) {
            return 'simulacion';
        }

        $sql = "
            SELECT modo_envio
            FROM configuracion_mail_presupuestos
            WHERE id_configuracion = 1
            LIMIT 1
        ";
        $res = mysqli_query($db, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;

        return normalizarModoCircuitoComercialPresupuestoLock($row['modo_envio'] ?? null);
    }
}

if (!function_exists('columnaEstadoComercialPresupuestoLockPorModo')) {
    function columnaEstadoComercialPresupuestoLockPorModo(?string $modo): string
    {
        return normalizarModoCircuitoComercialPresupuestoLock($modo) === 'smtp'
            ? 'estado_comercial_smtp'
            : 'estado_comercial_simulacion';
    }
}

if (!function_exists('obtenerPresupuestoActualEdicionComercialPresupuestoEnConexion')) {
    function obtenerPresupuestoActualEdicionComercialPresupuestoEnConexion(mysqli $db, int $idPrevisita, ?int $idPresupuesto = null): ?array
    {
        if ($idPrevisita <= 0) {
            return null;
        }

        $tieneEstadoComercialSimulacion = columna_existe($db, 'presupuestos', 'estado_comercial_simulacion');
        $tieneEstadoComercialSmtp = columna_existe($db, 'presupuestos', 'estado_comercial_smtp');

        $selectEstadoComercialSimulacion = $tieneEstadoComercialSimulacion
            ? 'estado_comercial_simulacion'
            : "'' AS estado_comercial_simulacion";
        $selectEstadoComercialSmtp = $tieneEstadoComercialSmtp
            ? 'estado_comercial_smtp'
            : "'' AS estado_comercial_smtp";

        if ($idPresupuesto !== null && $idPresupuesto > 0) {
            $sql = "
                SELECT
                    id_presupuesto,
                    id_previsita,
                    id_visita,
                    estado,
                    {$selectEstadoComercialSimulacion},
                    {$selectEstadoComercialSmtp},
                    updated_at,
                    created_at
                FROM presupuestos
                WHERE id_previsita = ?
                  AND id_presupuesto = ?
                LIMIT 1
            ";
            $stmt = mysqli_prepare($db, $sql);
            if (!$stmt) {
                return null;
            }
            mysqli_stmt_bind_param($stmt, 'ii', $idPrevisita, $idPresupuesto);
        } else {
            $sql = "
                SELECT
                    id_presupuesto,
                    id_previsita,
                    id_visita,
                    estado,
                    {$selectEstadoComercialSimulacion},
                    {$selectEstadoComercialSmtp},
                    updated_at,
                    created_at
                FROM presupuestos
                WHERE id_previsita = ?
                ORDER BY updated_at DESC, created_at DESC, id_presupuesto DESC
                LIMIT 1
            ";
            $stmt = mysqli_prepare($db, $sql);
            if (!$stmt) {
                return null;
            }
            mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
        }

        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return $row ?: null;
    }
}

if (!function_exists('obtenerUltimoEstadoHistorialEdicionComercialPresupuestoEnConexion')) {
    function obtenerUltimoEstadoHistorialEdicionComercialPresupuestoEnConexion(mysqli $db, int $idPrevisita, int $idPresupuesto, string $modoCircuito): string
    {
        if ($idPrevisita <= 0 || $idPresupuesto <= 0 || !tabla_existe($db, 'presupuesto_historial_comercial')) {
            return '';
        }

        $modoCircuito = normalizarModoCircuitoComercialPresupuestoLock($modoCircuito);
        $sql = "
            SELECT estado_resultante
            FROM presupuesto_historial_comercial
            WHERE id_previsita = ?
              AND id_presupuesto = ?
              AND modo_circuito = ?
            ORDER BY created_at DESC, id_historial_comercial DESC
            LIMIT 1
        ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return '';
        }

        mysqli_stmt_bind_param($stmt, 'iis', $idPrevisita, $idPresupuesto, $modoCircuito);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return normalizarEstadoComercialPresupuestoLock($row['estado_resultante'] ?? null);
    }
}

if (!function_exists('resolverEstadoBloqueoEdicionComercialPresupuestoEnConexion')) {
    function resolverEstadoBloqueoEdicionComercialPresupuestoEnConexion(mysqli $db, array $presupuesto, ?string $modoCircuito = null): string
    {
        $modoCircuito = $modoCircuito ?: obtenerModoActivoCircuitoComercialPresupuestosLock($db);
        $columnaEstado = columnaEstadoComercialPresupuestoLockPorModo($modoCircuito);

        $estadoComercialActivo = normalizarEstadoComercialPresupuestoLock($presupuesto[$columnaEstado] ?? null);
        if ($estadoComercialActivo !== '') {
            return $estadoComercialActivo;
        }

        $estadoInterno = normalizarEstadoComercialPresupuestoLock($presupuesto['estado'] ?? null);
        if ($estadoInterno !== '') {
            return $estadoInterno;
        }

        return obtenerUltimoEstadoHistorialEdicionComercialPresupuestoEnConexion(
            $db,
            (int)($presupuesto['id_previsita'] ?? 0),
            (int)($presupuesto['id_presupuesto'] ?? 0),
            $modoCircuito
        );
    }
}

if (!function_exists('obtenerBloqueoEdicionComercialPresupuestoPorPrevisita')) {
    function obtenerBloqueoEdicionComercialPresupuestoPorPrevisita(int $idPrevisita, ?int $idPresupuesto = null): array
    {
        $snapshot = [
            'ok' => true,
            'bloqueado' => false,
            'estado' => '',
            'estado_label' => '',
            'modo_circuito' => 'simulacion',
            'id_previsita' => $idPrevisita,
            'id_presupuesto' => $idPresupuesto,
            'mensaje' => '',
        ];

        if ($idPrevisita <= 0) {
            return $snapshot;
        }

        $db = conectDB();
        if (!$db) {
            return $snapshot;
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            $modoCircuito = obtenerModoActivoCircuitoComercialPresupuestosLock($db);
            $snapshot['modo_circuito'] = $modoCircuito;

            $presupuesto = obtenerPresupuestoActualEdicionComercialPresupuestoEnConexion($db, $idPrevisita, $idPresupuesto);
            if (!$presupuesto) {
                return $snapshot;
            }

            $snapshot['id_presupuesto'] = isset($presupuesto['id_presupuesto']) ? (int)$presupuesto['id_presupuesto'] : $idPresupuesto;

            $estado = resolverEstadoBloqueoEdicionComercialPresupuestoEnConexion($db, $presupuesto, $modoCircuito);
            $snapshot['estado'] = $estado;
            $snapshot['estado_label'] = etiquetaEstadoComercialPresupuestoLock($estado);
            $snapshot['bloqueado'] = estadoBloqueaEdicionComercialPresupuesto($estado);
            $snapshot['mensaje'] = $snapshot['bloqueado']
                ? mensajeBloqueoEdicionComercialPresupuesto($estado)
                : '';

            return $snapshot;
        } finally {
            mysqli_close($db);
        }
    }
}
