<?php

require_once __DIR__ . '/conectDB.php';

if (!function_exists('normalizarEstadoWorkflowPrevisita')) {
    function normalizarEstadoWorkflowPrevisita(?string $estado): string
    {
        $estado = strtoupper(trim((string)($estado ?? '')));
        $permitidos = ['PROGRAMADA', 'REPROGRAMADA', 'VENCIDA', 'EJECUTADA', 'CANCELADA'];

        return in_array($estado, $permitidos, true) ? $estado : '';
    }
}

if (!function_exists('etiquetaEstadoWorkflowPrevisita')) {
    function etiquetaEstadoWorkflowPrevisita(?string $estado): string
    {
        $map = [
            'PROGRAMADA' => 'Programada',
            'REPROGRAMADA' => 'Reprogramada',
            'VENCIDA' => 'Vencida',
            'EJECUTADA' => 'Ejecutada',
            'CANCELADA' => 'Cancelada',
        ];

        $estado = normalizarEstadoWorkflowPrevisita($estado);
        return $map[$estado] ?? '';
    }
}

if (!function_exists('estadosEditablesWorkflowPrevisita')) {
    function estadosEditablesWorkflowPrevisita(): array
    {
        return ['PROGRAMADA', 'REPROGRAMADA', 'VENCIDA'];
    }
}

if (!function_exists('estadoPermiteEdicionWorkflowPrevisita')) {
    function estadoPermiteEdicionWorkflowPrevisita(?string $estado): bool
    {
        $estadoOriginal = trim((string)($estado ?? ''));
        if ($estadoOriginal === '') {
            return true;
        }

        $estado = normalizarEstadoWorkflowPrevisita($estadoOriginal);
        if ($estado === '') {
            return false;
        }

        return in_array($estado, estadosEditablesWorkflowPrevisita(), true);
    }
}

if (!function_exists('estadoHabilitaVisitaWorkflowPrevisita')) {
    function estadoHabilitaVisitaWorkflowPrevisita(?string $estado): bool
    {
        return normalizarEstadoWorkflowPrevisita($estado) === 'EJECUTADA';
    }
}

if (!function_exists('estadoBloqueaAvanceWorkflowPrevisita')) {
    function estadoBloqueaAvanceWorkflowPrevisita(?string $estado): bool
    {
        return normalizarEstadoWorkflowPrevisita($estado) === 'CANCELADA';
    }
}

if (!function_exists('mensajeBloqueoWorkflowPrevisita')) {
    function mensajeBloqueoWorkflowPrevisita(?string $estado = null): string
    {
        switch (normalizarEstadoWorkflowPrevisita($estado)) {
            case 'EJECUTADA':
                return 'La pre-visita ya fue ejecutada y su edicion quedo bloqueada. Continue desde la visita.';
            case 'CANCELADA':
                return 'La pre-visita esta cancelada y no permite mas avances.';
            default:
                return 'La pre-visita no permite edicion con su estado actual.';
        }
    }
}

if (!function_exists('snapshotWorkflowPrevisitaEstado')) {
    function snapshotWorkflowPrevisitaEstado(?string $estado): array
    {
        $estadoOriginal = trim((string)($estado ?? ''));
        $estado = normalizarEstadoWorkflowPrevisita($estadoOriginal);
        $permiteEdicion = estadoPermiteEdicionWorkflowPrevisita($estadoOriginal);
        $habilitaVisita = estadoHabilitaVisitaWorkflowPrevisita($estadoOriginal);
        $bloqueaAvance = estadoBloqueaAvanceWorkflowPrevisita($estadoOriginal);
        $bloqueado = ($estadoOriginal !== '' && !$permiteEdicion);

        return [
            'estado' => $estado,
            'estado_label' => etiquetaEstadoWorkflowPrevisita($estado) ?: $estadoOriginal,
            'permite_edicion' => $permiteEdicion,
            'habilita_visita' => $habilitaVisita,
            'bloquea_avance' => $bloqueaAvance,
            'bloqueado' => $bloqueado,
            'mensaje' => $bloqueado ? mensajeBloqueoWorkflowPrevisita($estadoOriginal) : '',
        ];
    }
}

if (!function_exists('obtenerEstadoWorkflowPrevisitaPorIdEnConexion')) {
    function obtenerEstadoWorkflowPrevisitaPorIdEnConexion(mysqli $db, int $idPrevisita): string
    {
        if ($idPrevisita <= 0) {
            return '';
        }

        $sql = 'SELECT estado_visita FROM previsitas WHERE id_previsita = ? LIMIT 1';
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return '';
        }

        mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return trim((string)($row['estado_visita'] ?? ''));
    }
}

if (!function_exists('obtenerBloqueoWorkflowPrevisitaPorId')) {
    function obtenerBloqueoWorkflowPrevisitaPorId(int $idPrevisita): array
    {
        $snapshot = snapshotWorkflowPrevisitaEstado(null);
        $snapshot['id_previsita'] = $idPrevisita;

        if ($idPrevisita <= 0) {
            return $snapshot;
        }

        $db = conectDB();
        if (!$db) {
            return $snapshot;
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            $estado = obtenerEstadoWorkflowPrevisitaPorIdEnConexion($db, $idPrevisita);
            $snapshot = snapshotWorkflowPrevisitaEstado($estado);
            $snapshot['id_previsita'] = $idPrevisita;

            return $snapshot;
        } finally {
            mysqli_close($db);
        }
    }
}
