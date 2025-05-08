<?php
session_start();

include_once '../06-funciones_php/cleanInput.php'; // limpia las variables input
if(isset($_POST['ajax']) && $_POST['ajax']=='on'){
    include_once '../04-modelo/conectDB.php'; //conecta a la base de datos
    if(isset($_POST['accion']) && $_POST['accion'] == "alta"){addUser('ajax');}else{saveUser('ajax');}
}

// funcion para dar de alta un agente 
function addUser($metodo){

        $_POST = cleanInput($_POST); //sanitiza las variables que vienen del formulario

        if(!empty($_POST['password'])){$_POST['password'] = md5($_POST['password']);}
        $campos  ="";
        $valores ="";

        foreach ($_POST as $key => $value) {
            if($key != "ajax" && $key != "accion" && $key != "edad" && $value != ""){
                if($key=='nombres' || $key=='apellidos'){$value = ucwords(strtolower($value));}
                $campos  .= $key.", ";
                $valores .= "'".$value."'".", ";

            } 
        }
        $campos = rtrim($campos, ", ");
        $valores = rtrim($valores, ", ");

        $campos = $campos.", log_usuario_id";
        $valores = $valores.", ".$_SESSION['usuario']['id_usuario'];
        $db = conectDB();
        $query = "
        INSERT INTO usuarios (".$campos.")
        VALUES (".$valores.");"; // 
        $resultado = $db->query($query);


        // foreach ($rows as $key => $value) {
        //     $rows[$key]['calle'] = utf8_encode(ucwords(mb_strtolower($value['calle'])));
        // }
    
        mysqli_close($db);                           // cierra la base de datos

        if($metodo!="ajax"){
            return $rows; // es php devuelve un array()
        }else{
            echo json_encode($resultado); // es ajax devuelve un json
        }
}
// end - funcion para dar de alta un agente

// funcion modifica o eliminar un agente
function saveUser($metodo){

        $_POST = cleanInput($_POST); //sanitiza las variables que vienen del formulario

        if(!empty($_POST['password'])){$_POST['password'] = md5($_POST['password']);}
        $campos  ="";
        $valores ="";

        $camposValues = "";
        foreach ($_POST as $key => $value) {
            if($key != "ajax" && $key != "accion" && $key != "edad" && $key != "id_usuario"){
              if($key=='nombres' || $key=='apellidos'){$value = ucwords(strtolower($value));}
              $camposValues  .= $key." = '".$value."', ";
            } 
        }

        $camposValues = rtrim($camposValues, ", ");
        $camposValues .= ", log_usuario_id = '".$_SESSION['usuario']['id_usuario']."'";
        if($_POST['accion'] == 'eliminar'){$camposValues .= ", log_accion = 'eliminar'";}
        $db = conectDB();
        $query = "
        UPDATE usuarios AS usu
        SET ".$camposValues."
        WHERE usu.id_usuario = ".$_POST['id_usuario'].";"; // 

        $resultado = $db->query($query);

        // foreach ($rows as $key => $value) {
        //     $rows[$key]['calle'] = utf8_encode(ucwords(mb_strtolower($value['calle'])));
        // }
    
        mysqli_close($db);                           // cierra la base de datos

        if($metodo!="ajax"){
            return $rows; // es php devuelve un array()
        }else{
            echo json_encode($resultado); // es ajax devuelve un json
        }
}
// end - funcion para dar de alta un agente
?>