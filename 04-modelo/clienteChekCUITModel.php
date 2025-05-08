<?php
session_start();
include '../06-funciones_php/cleanInput.php'; // limpia las variables input

if(isset($_POST['ajax']) && $_POST['ajax']=='on'){ 
    include '../04-modelo/conectDB.php'; //conecta a la base de datos
    if(isset($_POST['cuit']) && isset($_POST['accion']) == "check"){getClienteByCuit($_POST['cuit'], 'ajax');}
}



// función para saber si el mail ya esta en uso | Parametros [$email] mail a buscar | [$metodo] metodo utilizado para la solicitud ajax
function getClienteByCuit($cuit, $metodo){
        $_POST = cleanInput($_POST); //sanitiza las variables que vienen del formulario
        $db = conectDB();
        $query = "
        SELECT cli.*
        FROM clientes AS cli
        WHERE cli.cuit = '".$cuit."'
        ;"; // trae el o los clientes que usan ese cuit

        $resultado = $db->query($query);

        while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}

        if(isset($rows)){
          $mensaje='El cliente <strong>'.$rows[0]['razon_social'].'</strong> ya tiene en uso el CUIT ingresado';
        }else{
          $mensaje=false;
        }



        mysqli_close($db); // cierra la base de datos

        if($metodo!="ajax"){
            return $rows; // es php devuelve un array()
        }else{
            echo json_encode($mensaje); // es ajax devuelve un jason
        }
}
// end - funcion que devuelve la totalidad de calles correspondientes a un partido
 
// $fechaActual = new DateTime(date("Y-m-d H:i:s", time()));
?>