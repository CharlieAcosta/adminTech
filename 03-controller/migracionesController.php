<?php
session_start();
define('BASE_URL', $_SESSION["base_url"] ?? '');
include_once '../06-funciones_php/funciones.php';
sesion();
include_once '../10-clases/Auditoria.php';

if (($_SESSION['usuario']['perfil'] ?? '') !== 'Super Administrador') {
    header('Location: ../01-views/panel.php');
    exit;
}

$accion  = $_POST['accion'] ?? '';
$archivo = basename($_POST['archivo'] ?? '');

if (!$archivo || pathinfo($archivo, PATHINFO_EXTENSION) !== 'sql') {
    header('Location: ../01-views/migraciones.php');
    exit;
}

$rutaArchivo = '../11-migraciones_sql/' . $archivo;
if (!file_exists($rutaArchivo)) {
    $_SESSION['mig_flash'] = ['tipo' => 'danger', 'mensaje' => "Archivo no encontrado: {$archivo}"];
    header('Location: ../01-views/migraciones.php');
    exit;
}

$conn = conectaDB();

// Crear tabla de migraciones si no existe
$conn->query("CREATE TABLE IF NOT EXISTS migraciones (
    id_migracion      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    archivo           VARCHAR(255) NOT NULL UNIQUE,
    estado            ENUM('OK','ERROR') DEFAULT 'OK',
    ejecutada_por_id  INT,
    ejecutada_por_email VARCHAR(255),
    fecha_ejecucion   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    error_mensaje     TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$idUsuario    = (int) ($_SESSION['usuario']['id_usuario'] ?? 0);
$emailUsuario = $_SESSION['usuario']['email'] ?? '';

if ($accion === 'ejecutar') {
    $sql = file_get_contents($rutaArchivo);
    $estado = 'OK';
    $errorMsg = null;

    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    }

    if ($conn->errno) {
        $estado   = 'ERROR';
        $errorMsg = $conn->error;
    }

    $stmt = $conn->prepare("INSERT INTO migraciones (archivo, estado, ejecutada_por_id, ejecutada_por_email, error_mensaje)
                            VALUES (?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE estado = VALUES(estado), ejecutada_por_id = VALUES(ejecutada_por_id),
                            ejecutada_por_email = VALUES(ejecutada_por_email), fecha_ejecucion = CURRENT_TIMESTAMP,
                            error_mensaje = VALUES(error_mensaje)");
    $stmt->bind_param('ssiss', $archivo, $estado, $idUsuario, $emailUsuario, $errorMsg);
    $stmt->execute();
    $stmt->close();

    $auditoria = new Auditoria($conn);
    $auditoria->registrarModificacion(
        $idUsuario,
        $emailUsuario,
        'Super Administrador',
        'MIGRACIONES',
        $_SERVER['REQUEST_URI'],
        "Ejecutó migración: {$archivo} — Resultado: {$estado}",
        "Archivo: {$archivo}"
    );

    if ($estado === 'OK') {
        $_SESSION['mig_flash'] = ['tipo' => 'success', 'mensaje' => "Migración ejecutada correctamente: {$archivo}"];
    } else {
        $_SESSION['mig_flash'] = ['tipo' => 'danger', 'mensaje' => "Error al ejecutar {$archivo}: {$errorMsg}"];
    }
}

if ($accion === 'marcar') {
    $estado = 'OK';
    $stmt = $conn->prepare("INSERT INTO migraciones (archivo, estado, ejecutada_por_id, ejecutada_por_email)
                            VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE estado = VALUES(estado), ejecutada_por_id = VALUES(ejecutada_por_id),
                            ejecutada_por_email = VALUES(ejecutada_por_email), fecha_ejecucion = CURRENT_TIMESTAMP");
    $stmt->bind_param('ssis', $archivo, $estado, $idUsuario, $emailUsuario);
    $stmt->execute();
    $stmt->close();

    $auditoria = new Auditoria($conn);
    $auditoria->registrarModificacion(
        $idUsuario,
        $emailUsuario,
        'Super Administrador',
        'MIGRACIONES',
        $_SERVER['REQUEST_URI'],
        "Marcó como ejecutada (sin correr): {$archivo}",
        "Archivo: {$archivo}"
    );

    $_SESSION['mig_flash'] = ['tipo' => 'warning', 'mensaje' => "Migración marcada como ejecutada (sin correr el SQL): {$archivo}"];
}

mysqli_close($conn);
header('Location: ../01-views/migraciones.php');
exit;
