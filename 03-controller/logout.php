<?php
session_start(); // Iniciar la sesión para poder destruirla

include_once '../00-config/db.php';  // Incluir conexión a la base de datos
include_once '../06-funciones_php/funciones.php';  // Incluir conexión a la base de datos
include_once '../10-clases/Auditoria.php';  // Incluir la clase Auditoria

// Verificar si el usuario está en la sesión antes de destruirla
if (isset($_SESSION['usuario'])) {
    // Recopilar información del usuario desde la sesión
    $usuario_email = $_SESSION['usuario']['email'];    // Correo electrónico del usuario
    $id_usuario = $_SESSION['usuario']['id_usuario'];  // ID del usuario
    $perfil_usuario = $_SESSION['usuario']['perfil'];  // Perfil del usuario

    // Conectar a la base de datos para registrar el logout en la auditoría
    $conexionAuditoria = conectaDB();
    $auditoria = new Auditoria($conexionAuditoria);

    // Registrar el logout en la tabla de auditoría
    $auditoria->registrarAcceso($usuario_email, 'LOGOUT', 'Logout', $_SERVER['REQUEST_URI'], $id_usuario, $perfil_usuario);

    // Cerrar la conexión de auditoría
    mysqli_close($conexionAuditoria);
}

// Destruir la sesión
session_unset();
session_destroy();

// Redirigir al formulario de login después del logout
header('Location: ../01-views/login.php');
exit();
