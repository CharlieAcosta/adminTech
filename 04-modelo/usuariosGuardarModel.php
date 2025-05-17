<?php
session_start();

include_once '../06-funciones_php/cleanInput.php'; // limpia las variables input

if (isset($_POST['ajax']) && $_POST['ajax'] == 'on') {
    include_once '../04-modelo/conectDB.php'; //conecta a la base de datos
    if (isset($_POST['accion']) && $_POST['accion'] == "alta") {
        addUser('ajax');
    } else {
        saveUser('ajax');
    }
}

// función para dar de alta un agente
function addUser($metodo)
{
    $_POST = cleanInput($_POST); // sanitiza las variables que vienen del formulario

    $passwordFinal = null;

    if (!empty($_POST['password'])) {
        $passwordFinal = md5($_POST['password']);
    }

    $campos = "";
    $valores = "";

    foreach ($_POST as $key => $value) {
        if ($key != "ajax" && $key != "accion" && $key != "edad" && $value != "") {
            if ($key == 'nombres' || $key == 'apellidos') {
                $value = ucwords(strtolower($value));
            }

            if ($key == 'password') {
                if (!is_null($passwordFinal)) {
                    $campos .= "password, ";
                    $valores .= "'$passwordFinal', ";
                }
                continue;
            }

            $campos .= $key . ", ";
            $valores .= "'" . $value . "', ";
        }
    }

    $campos = rtrim($campos, ", ");
    $valores = rtrim($valores, ", ");

    $campos .= ", log_usuario_id";
    $valores .= ", " . $_SESSION['usuario']['id_usuario'];

    $db = conectDB();
    $query = "
        INSERT INTO usuarios ($campos)
        VALUES ($valores);
    ";

    $resultado = $db->query($query);

    mysqli_close($db); // cierra la base de datos

    if ($metodo != "ajax") {
        return $resultado;
    } else {
        echo json_encode($resultado);
    }
}
// end - función para dar de alta un agente

// función modifica o elimina un agente
function saveUser($metodo)
{
    $_POST = cleanInput($_POST); // sanitiza las variables que vienen del formulario

    $passwordFinal = null;
    $db = conectDB(); // una sola conexión para toda la función

    if (!empty($_POST['password'])) {
        $passwordForm = $_POST['password'];
        $idUsuario = intval($_POST['id_usuario']);

        $queryPass = "SELECT password FROM usuarios WHERE id_usuario = $idUsuario";
        $res = mysqli_query($db, $queryPass);

        if ($res && $row = mysqli_fetch_assoc($res)) {
            $passwordBD = $row['password'];

            if ($passwordForm !== $passwordBD) {
                // Solo si es distinta, la encriptamos
                $passwordFinal = md5($passwordForm);
            }
        }

        mysqli_free_result($res);
    }

    $camposValues = "";

    foreach ($_POST as $key => $value) {
        if ($key != "ajax" && $key != "accion" && $key != "edad" && $key != "id_usuario") {
            if ($key == 'password') {
                if (!is_null($passwordFinal)) {
                    $camposValues .= "password = '" . $passwordFinal . "', ";
                }
                continue;
            }

            if ($key == 'nombres' || $key == 'apellidos') {
                $value = ucwords(strtolower($value));
            }

            $camposValues .= $key . " = '" . $value . "', ";
        }
    }

    $camposValues = rtrim($camposValues, ", ");
    $camposValues .= ", log_usuario_id = '" . $_SESSION['usuario']['id_usuario'] . "'";

    if ($_POST['accion'] == 'eliminar') {
        $camposValues .= ", log_accion = 'eliminar'";
    }

    $query = "
        UPDATE usuarios AS usu
        SET $camposValues
        WHERE usu.id_usuario = " . $_POST['id_usuario'] . ";
    ";

    $resultado = $db->query($query);

    mysqli_close($db); // cierra la base de datos

    if ($metodo != "ajax") {
        return $resultado;
    } else {
        echo json_encode($resultado);
    }
}
// end - función para modificar un agente
?>
