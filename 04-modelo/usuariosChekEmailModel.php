<?php
session_start();
include '../06-funciones_php/cleanInput.php'; // limpia las variables input

if(isset($_POST['ajax']) && $_POST['ajax']=='on'){
    include '../04-modelo/conectDB.php'; //conecta a la base de datos
    if(isset($_POST['email']) && isset($_POST['accion']) == "check"){getUsuarioByEmail($_POST['email'], 'ajax', $_POST['idUsuario'] );}
}


// función para saber si el mail ya esta en uso | Parametros [$email] mail a buscar | [$metodo] metodo utilizado para la solicitud ajax
function getUsuarioByEmail($email, $metodo, $idUsuario){
        $_POST = cleanInput($_POST); //sanitiza las variables que vienen del formulario

        $db = conectDB();
        $query = "
        SELECT usu.*
        FROM usuarios AS usu
        WHERE usu.email = '".$email."'
        ;"; // trae el o los usuario que usan ese correo

        $resultado = $db->query($query);
        while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}

        // foreach ($rows as $key => $value) {
        //     $rows[$key]['calle'] = utf8_encode(ucwords(mb_strtolower($value['calle'])));
        // }

        if(isset($rows) && $idUsuario =! $rows[0]['id_usuario']){$mensaje="El agente <strong>".$rows[0]["apellidos"]." ".$rows[0]["nombres"]."</strong> tiene en uso el email ingresado";}else{$mensaje=true;}

        mysqli_close($db);                           // cierra la base de datos

        if($metodo!="ajax"){
            return $rows; // es php devuelve un array()
        }else{
            echo json_encode($mensaje); // es ajax devuelve un jason
        }
}
// end - funcion que devuelve la totalidad de calles correspondientes a un partido
 
// $fechaActual = new DateTime(date("Y-m-d H:i:s", time()));
?>