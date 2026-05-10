<?php

if (!function_exists('sqlCrearTablaMigracionesControl')) {
    function sqlCrearTablaMigracionesControl(): string
    {
        return "CREATE TABLE IF NOT EXISTS migraciones (
            id_migracion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            archivo VARCHAR(255) NOT NULL UNIQUE,
            estado ENUM('OK','ERROR') DEFAULT 'OK',
            ejecutada_por_id INT,
            ejecutada_por_email VARCHAR(255),
            fecha_ejecucion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            error_mensaje TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }
}

if (!function_exists('asegurarTablaMigraciones')) {
    function asegurarTablaMigraciones(mysqli $conn): bool
    {
        return (bool)$conn->query(sqlCrearTablaMigracionesControl());
    }
}

if (!function_exists('obtenerMigracionesRegistradas')) {
    function obtenerMigracionesRegistradas(mysqli $conn): array
    {
        asegurarTablaMigraciones($conn);

        $ejecutadas = [];
        $res = $conn->query("
            SELECT archivo, estado, ejecutada_por_email, fecha_ejecucion, error_mensaje
            FROM migraciones
            ORDER BY fecha_ejecucion DESC
        ");

        if (!$res) {
            return [];
        }

        while ($fila = $res->fetch_assoc()) {
            $ejecutadas[$fila['archivo']] = $fila;
        }

        return $ejecutadas;
    }
}

if (!function_exists('registrarMigracionEjecutada')) {
    function registrarMigracionEjecutada(
        mysqli $conn,
        string $archivo,
        string $estado,
        ?int $idUsuario,
        ?string $emailUsuario,
        ?string $errorMensaje = null
    ): bool {
        asegurarTablaMigraciones($conn);

        $estado = strtoupper(trim($estado)) === 'ERROR' ? 'ERROR' : 'OK';
        $idUsuario = $idUsuario ?: null;
        $emailUsuario = trim((string)$emailUsuario);

        $stmt = $conn->prepare("
            INSERT INTO migraciones (archivo, estado, ejecutada_por_id, ejecutada_por_email, error_mensaje)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                estado = VALUES(estado),
                ejecutada_por_id = VALUES(ejecutada_por_id),
                ejecutada_por_email = VALUES(ejecutada_por_email),
                fecha_ejecucion = CURRENT_TIMESTAMP,
                error_mensaje = VALUES(error_mensaje)
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ssiss', $archivo, $estado, $idUsuario, $emailUsuario, $errorMensaje);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('registrarMigracionAutoDetectada')) {
    function registrarMigracionAutoDetectada(mysqli $conn, string $archivo): bool
    {
        asegurarTablaMigraciones($conn);

        $emailAuto = 'auto-detectada';
        $stmt = $conn->prepare("
            INSERT IGNORE INTO migraciones (archivo, estado, ejecutada_por_id, ejecutada_por_email)
            VALUES (?, 'OK', NULL, ?)
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ss', $archivo, $emailAuto);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }
}
