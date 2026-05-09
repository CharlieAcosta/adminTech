<?php
session_start();
define('BASE_URL', $_SESSION["base_url"] ?? '');
include_once '../06-funciones_php/funciones.php';
sesion();
include_once '../10-clases/Auditoria.php';

$accion = $_POST['accion'] ?? '';

if ($accion === 'activar') {
    // Validar que el perfil REAL (no el disfraz) sea Super Administrador
    $perfilReal = $_SESSION['disfraz']['perfil_original'] ?? ($_SESSION['usuario']['perfil'] ?? '');
    if ($perfilReal !== 'Super Administrador') {
        header('Location: ../01-views/panel.php');
        exit;
    }

    $perfilesValidos = ['Administrador', 'Administrativo', 'Técnico', 'Tecnico Administrativo', 'Operario'];
    $perfilDisfraz = $_POST['perfil_disfraz'] ?? '';
    if (!in_array($perfilDisfraz, $perfilesValidos, true)) {
        header('Location: ../01-views/perfiles_panel.php');
        exit;
    }

    $perfilOriginal = $_SESSION['disfraz']['perfil_original'] ?? $_SESSION['usuario']['perfil'];
    $_SESSION['disfraz'] = [
        'activo'          => true,
        'perfil_original' => $perfilOriginal,
        'perfil_disfraz'  => $perfilDisfraz,
    ];
    $_SESSION['usuario']['perfil'] = $perfilDisfraz;

    $conn = conectaDB();
    $auditoria = new Auditoria($conn);
    $auditoria->registrarModificacion(
        $_SESSION['usuario']['id_usuario'],
        $_SESSION['usuario']['email'],
        'Super Administrador',
        'PERFILES - DISFRAZ',
        $_SERVER['REQUEST_URI'],
        "Super Administrador activó disfraz como: {$perfilDisfraz}",
        "Perfil original: Super Administrador"
    );
    mysqli_close($conn);

    header('Location: ../01-views/panel.php');
    exit;
}

if ($accion === 'quitar') {
    if (isset($_SESSION['disfraz']['activo']) && $_SESSION['disfraz']['activo']) {
        $perfilOriginal = $_SESSION['disfraz']['perfil_original'];
        $perfilDisfraz  = $_SESSION['disfraz']['perfil_disfraz'];
        $_SESSION['usuario']['perfil'] = $perfilOriginal;
        unset($_SESSION['disfraz']);

        $conn = conectaDB();
        $auditoria = new Auditoria($conn);
        $auditoria->registrarModificacion(
            $_SESSION['usuario']['id_usuario'],
            $_SESSION['usuario']['email'],
            $perfilOriginal,
            'PERFILES - DISFRAZ',
            $_SERVER['REQUEST_URI'],
            "Super Administrador quitó el disfraz de: {$perfilDisfraz}",
            "Perfil disfraz anterior: {$perfilDisfraz}"
        );
        mysqli_close($conn);
    }

    header('Location: ../01-views/panel.php');
    exit;
}

header('Location: ../01-views/panel.php');
exit;
