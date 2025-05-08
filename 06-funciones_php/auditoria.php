<?php
// filename: auditoria.php
include_once '../10-clases/Auditoria.php'; 

// Conexión común para auditoría
function obtenerConexionAuditoria() {
    return conectaDB();
}

// Función para registrar navegación
function registrarNavegacion($modulo = 'Módulo desconocido') {
    if (isset($_SESSION['usuario'])) {
        $usuario_email = $_SESSION['usuario']['email'];
        $id_usuario = $_SESSION['usuario']['id_usuario'];
        $perfil_usuario = $_SESSION['usuario']['perfil'];

        $conexionAuditoria = obtenerConexionAuditoria();
        $auditoria = new Auditoria($conexionAuditoria);
        $auditoria->registrarAcceso($usuario_email, 'NAVIGATE', $modulo, $_SERVER['REQUEST_URI'], $id_usuario, $perfil_usuario);
        mysqli_close($conexionAuditoria);
    }
}

// Función para registrar una visualización
function registrarVisualizacion($modulo = 'Módulo desconocido') {
    if (isset($_SESSION['usuario'])) {
        $usuario_email = $_SESSION['usuario']['email'];
        $id_usuario = $_SESSION['usuario']['id_usuario'];
        $perfil_usuario = $_SESSION['usuario']['perfil'];

        $conexionAuditoria = obtenerConexionAuditoria();
        $auditoria = new Auditoria($conexionAuditoria);
        $auditoria->registrarVisualizacion($id_usuario, $usuario_email, $perfil_usuario, $modulo, $_SERVER['REQUEST_URI'], 'Visualización de obra');
        mysqli_close($conexionAuditoria);
    }
}

// Función para registrar un alta
function registrarAltaObra($modulo = 'Obras', $descripcion = 'Alta de obra') {
    if (isset($_SESSION['usuario'])) {
        $usuario_email = $_SESSION['usuario']['email'];
        $id_usuario = $_SESSION['usuario']['id_usuario'];
        $perfil_usuario = $_SESSION['usuario']['perfil'];

        $conexionAuditoria = obtenerConexionAuditoria();
        $auditoria = new Auditoria($conexionAuditoria);
        $auditoria->registrarAlta($id_usuario, $usuario_email, $perfil_usuario, $modulo, $_SERVER['REQUEST_URI'], $descripcion);
        mysqli_close($conexionAuditoria);
    }
}

// Función para registrar una modificación
function registrarModificacionObra($modulo = 'Obras', $descripcion = 'Modificación de obra', $datosPrevios = 'Datos previos') {
    if (isset($_SESSION['usuario'])) {
        $usuario_email = $_SESSION['usuario']['email'];
        $id_usuario = $_SESSION['usuario']['id_usuario'];
        $perfil_usuario = $_SESSION['usuario']['perfil'];

        $conexionAuditoria = obtenerConexionAuditoria();
        $auditoria = new Auditoria($conexionAuditoria);
        $auditoria->registrarModificacion($id_usuario, $usuario_email, $perfil_usuario, $modulo, $_SERVER['REQUEST_URI'], $descripcion, $datosPrevios);
        mysqli_close($conexionAuditoria);
    }
}
