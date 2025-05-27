<?php
ob_start(); // para que funcione el header
include_once '../00-config/db.php'; 
include_once '../06-funciones_php/cleanInput.php'; // limpia las variables input
include_once '../06-funciones_php/base_url.php';
include_once '../06-funciones_php/funciones.php'; // funciones útiles
include_once '../10-clases/Auditoria.php';  // Archivo con la clase Auditoria

// Conectar a la base de datos
$db = conectaDB();

// Verificar conexión
if (!$db) {
    echo "Error: No se pudo conectar a MySQL." . PHP_EOL;
    echo "error de depuración: " . mysqli_connect_errno() . PHP_EOL;
    echo "error de depuración: " . mysqli_connect_error() . PHP_EOL;
    exit;
} else {
    // Verificar si las credenciales fueron enviadas
    if (isset($_POST['usuario']) && isset($_POST['password'])) {
        // Sanitizar las variables que vienen del formulario
        $_POST = cleanInput($_POST);

        // Consultar si el usuario existe en la base de datos
        $query = "
        SELECT u.*
        FROM usuarios AS u
        WHERE u.email = '" . $_POST['usuario'] . "'
        ;";
        $resultado = $db->query($query);

        // Almacenar los resultados en un array
        $rows = [];
        while ($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)) {
            $rows[] = $row;
        }

        // Cifrar la contraseña proporcionada por el usuario
        $password_md5_ext = md5($_POST['password']);

        // Verificar si el usuario existe
        if (!empty($rows)) {
            // El usuario existe en la base de datos
            $password_md5_int = $rows[0]['password'];    // Contraseña almacenada
            $estado_int = $rows[0]['estado'];            // Estado del usuario
            $id_usuario = $rows[0]['id_usuario'];        // ID del usuario
            $perfil_usuario = $rows[0]['perfil'];        // Perfil del usuario

            // Cerrar la conexión a la base de datos
            mysqli_close($db);

            // Verificar contraseña y estado del usuario
            if ($password_md5_ext == $password_md5_int && $estado_int == "Activo") {

                // Establecer duración máxima de la sesión en segundos (3600 = 1 hora)
                ini_set('session.gc_maxlifetime', SESION_TIME);

                session_start(); // Inicia la sesión

                // Establecer variables de sesión
                $_SESSION["usuario"] = $rows[0];
                $_SESSION["base_url"] = base_url() . "/adminTech/";

                // Registro de auditoría - Login exitoso
                $conexionAuditoria = conectaDB(); // Conectar a la base de datos para auditoría
                $auditoria = new Auditoria($conexionAuditoria);
                // Registrar acceso exitoso con id_usuario y perfil
                $auditoria->registrarAcceso($rows[0]['email'], 'LOGIN', 'LOGIN CONTROLLER', $_SERVER['REQUEST_URI'], $id_usuario, $perfil_usuario);
                mysqli_close($conexionAuditoria); // Cerrar la conexión de auditoría

                // Redireccionar al panel
                header('Location: ../01-views/panel.php');

            } else {
                // Credenciales inválidas - Registrar intento fallido
                $conexionAuditoria = conectaDB(); // Conectar a la base de datos para auditoría
                $auditoria = new Auditoria($conexionAuditoria);
                // Registrar intento fallido
                $auditoria->registrarAcceso($_POST['usuario'], 'LOGIN_FAILED', 'Login', $_SERVER['REQUEST_URI'], null, null);
                mysqli_close($conexionAuditoria); // Cerrar la conexión de auditoría

                // Redireccionar al formulario de login con credenciales inválidas
                header('Location: ../01-views/login.php');
            }

        } else {
            // Usuario no encontrado - Registrar intento fallido
            mysqli_close($db); // Cerrar la conexión a la base de datos

            // Conectar para auditoría
            $conexionAuditoria = conectaDB();
            $auditoria = new Auditoria($conexionAuditoria);
            // Registrar intento fallido (usuario no existe)
            $auditoria->registrarAcceso($_POST['usuario'], 'LOGIN_FAILED', 'Login', $_SERVER['REQUEST_URI'], null, null);
            mysqli_close($conexionAuditoria);

            // Redireccionar al formulario de login con credenciales inválidas
            header('Location: ../01-views/login.php');
        }

    } else {
        // Intento de acceso directo a login.php sin enviar credenciales
        header('Location: ../01-views/login.php'); // Redirigir al formulario de login
    }
}

ob_end_flush();
