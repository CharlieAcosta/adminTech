<?php
session_start();
define('BASE_URL', $_SESSION["base_url"] ?? '');
include_once '../06-funciones_php/funciones.php';
sesion();
include_once '../10-clases/Auditoria.php';
include_once '../04-modelo/migracionesModel.php';

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
asegurarTablaMigraciones($conn);

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

    registrarMigracionEjecutada($conn, $archivo, $estado, $idUsuario, $emailUsuario, $errorMsg);

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
    registrarMigracionEjecutada($conn, $archivo, $estado, $idUsuario, $emailUsuario);

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
