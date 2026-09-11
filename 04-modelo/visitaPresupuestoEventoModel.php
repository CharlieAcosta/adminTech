<?php

require_once __DIR__ . '/conectDB.php';

if (!function_exists('tipoEventoCongelamientoVisitaPresupuesto')) {
    function tipoEventoCongelamientoVisitaPresupuesto(): string
    {
        return 'VISITA_CONGELADA_POR_GENERACION_PRESUPUESTO';
    }
}

if (!function_exists('obtenerEventoCongelamientoVisitaPorPrevisitaEnConexion')) {
    function obtenerEventoCongelamientoVisitaPorPrevisitaEnConexion(mysqli $db, int $idPrevisita): ?array
    {
        if ($idPrevisita <= 0) {
            return null;
        }

        $sql = "
            SELECT
                id_evento,
                id_previsita,
                tipo_evento,
                id_presupuesto,
                id_usuario,
                origen,
                created_at
            FROM visita_presupuesto_eventos
            WHERE id_previsita = ?
              AND tipo_evento = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            throw new RuntimeException(
                'No se pudo preparar la consulta del congelamiento de Visita: ' . mysqli_error($db)
            );
        }

        $tipoEvento = tipoEventoCongelamientoVisitaPresupuesto();
        mysqli_stmt_bind_param($stmt, 'is', $idPrevisita, $tipoEvento);

        if (!mysqli_stmt_execute($stmt)) {
            $mensaje = mysqli_stmt_error($stmt) ?: mysqli_error($db);
            mysqli_stmt_close($stmt);
            throw new RuntimeException(
                'No se pudo consultar el congelamiento de Visita: ' . $mensaje
            );
        }

        $resultado = mysqli_stmt_get_result($stmt);
        $evento = $resultado ? mysqli_fetch_assoc($resultado) : null;
        mysqli_stmt_close($stmt);

        if (!$evento) {
            return null;
        }

        return [
            'id_evento' => (int)$evento['id_evento'],
            'id_previsita' => (int)$evento['id_previsita'],
            'tipo_evento' => (string)$evento['tipo_evento'],
            'id_presupuesto' => (int)$evento['id_presupuesto'],
            'id_usuario' => $evento['id_usuario'] !== null ? (int)$evento['id_usuario'] : null,
            'origen' => (string)$evento['origen'],
            'created_at' => (string)$evento['created_at'],
        ];
    }
}

if (!function_exists('obtenerEventoCongelamientoVisitaPorPrevisita')) {
    function obtenerEventoCongelamientoVisitaPorPrevisita(int $idPrevisita): ?array
    {
        if ($idPrevisita <= 0) {
            return null;
        }

        $db = conectDB();
        if (!$db) {
            throw new RuntimeException('No se pudo abrir la conexion para consultar el congelamiento de Visita.');
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            return obtenerEventoCongelamientoVisitaPorPrevisitaEnConexion($db, $idPrevisita);
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('visitaEstaCongeladaPorPresupuestoEnConexion')) {
    function visitaEstaCongeladaPorPresupuestoEnConexion(mysqli $db, int $idPrevisita): bool
    {
        return obtenerEventoCongelamientoVisitaPorPrevisitaEnConexion($db, $idPrevisita) !== null;
    }
}

if (!function_exists('visitaEstaCongeladaPorPresupuesto')) {
    function visitaEstaCongeladaPorPresupuesto(int $idPrevisita): bool
    {
        return obtenerEventoCongelamientoVisitaPorPrevisita($idPrevisita) !== null;
    }
}
