<?php
session_start();
//var_dump($_POST); die('3');
include_once '../06-funciones_php/cleanInput.php'; // limpia las variables input
if(isset($_POST['ajax']) && $_POST['ajax']=='on'){
    include_once '../04-modelo/conectDB.php'; //conecta a la base de datos
    if(isset($_POST['accion']) && $_POST['accion'] == "alta"){addUser('ajax');}else{saveUser('ajax');}
}

// funcion para dar de alta un agente 
function addUser($metodo){
        $_POST = cleanInput($_POST); //sanitiza las variables que vienen del formulario

        $campos  ="";
        $valores ="";

        // esta condición se hace porque en el caso de clientes algún campo hijo de provincia puede venir vacio. Si provincia viene vacio no hacemos nada, si viene con valor hay ver cuales de los campos siguientes tiene valor y cuales no
        if(isset($_POST['dirfis_provincia'])){
           if(!isset($_POST['dirfis_partido'])){$_POST['dirfis_partido']=null;} 
           if(!isset($_POST['dirfis_localidad'])){$_POST['dirfis_localidad']=null;}
           if(!isset($_POST['dirfis_calle'])){$_POST['dirfis_calle']=null;}
        }

        foreach ($_POST as $key => $value) {
            if($key != "ajax" && $key != "accion" && $key != "edad" && $value != ""){
                if($key=='razon_social'){$value = strtoupper($value);}
                if($key=='contacto_pri' || $key=='contacto_papro'){$value = ucwords(strtolower($value));}
              if($key=='email' || $key=='contacto_pri_email' || $key=='contacto_papro_email' || $key=='email_documentacion' || $key=='email_licitacion' || $key=='email_pagos'){$value = strtolower($value);}
                $campos  .= $key.", ";
                $valores .= "'".$value."'".", ";

            } 
        }

        $campos = rtrim($campos, ", ");
        $valores = rtrim($valores, ", ");

        $campos = $campos.", log_usuario_id, log_accion";
        $valores = $valores.", ".$_SESSION['usuario']['id_usuario'].", '".$_POST['accion']."'";
        $db = conectDB();

        $query = "
        INSERT INTO clientes (".$campos.")
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

// funcion modifica o eliminar un cliente
function saveUser($metodo){
//var_dump($_POST); die();
        $_POST = cleanInput($_POST); //sanitiza las variables que vienen del formulario

        $campos  ="";
        $valores ="";

        $camposValues = "";

        // esta condición se hace porque en el caso de clientes algún campo hijo de provincia puede venir vacio. Si provincia viene vacio no hacemos nada, si viene con valor hay ver cuales de los campos siguientes tiene valor y cuales no
        if(isset($_POST['dirfis_provincia'])){
           if(!isset($_POST['dirfis_partido'])){$_POST['dirfis_partido']=null;} 
           if(!isset($_POST['dirfis_localidad'])){$_POST['dirfis_localidad']=null;}
           if(!isset($_POST['dirfis_calle'])){$_POST['dirfis_calle']=null;}
        }

        foreach ($_POST as $key => $value) {
             if($key != "ajax" && $key != "accion" && $key != "id_cliente"){
              if($key=='razon_social'){$value = strtoupper($value);}
              if($key=='contacto_pri' || $key=='contacto_papro'){$value = ucwords(strtolower($value));}
              if($key=='email' || $key=='contacto_pri_email' || $key=='contacto_papro_email' || $key=='email_documentacion' || $key=='email_licitacion' || $key=='email_pagos'){$value = strtolower($value);}

              $camposValues  .= $key." = '".$value."', ";
            } 
        }

        $camposValues = rtrim($camposValues, ", ");
        $camposValues .= ", log_usuario_id = '".$_SESSION['usuario']['id_usuario']."'";
        if($_POST['accion'] == 'eliminar'){$camposValues .= ", log_accion = 'eliminar'";}
        $db = conectDB();
        $query = "
        UPDATE clientes AS c
        SET ".$camposValues."
        WHERE c.id_cliente = ".$_POST['id_cliente'].";"; // 
  
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