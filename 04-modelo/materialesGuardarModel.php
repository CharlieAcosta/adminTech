<?php
session_start();
//var_dump($_POST); die;
include_once '../06-funciones_php/funciones.php'; //funciones últiles
include_once '../06-funciones_php/cleanInput.php'; // limpia las variables input

if(isset($_POST['ajax']) && $_POST['ajax']=='on'){
    include_once '../04-modelo/conectDB.php'; //conecta a la base de datos
    if(isset($_POST['accion']) && $_POST['accion'] == "alta"){addPrevisita('ajax');} else {savePrevisita('ajax');}
}

// funcion para dar de alta una previsita
function addPrevisita($metodo){

        $_POST = cleanInput($_POST); //sanitiza las variables que vienen del formulario
        //var_dump($_POST); var_dump($_FILES);
        $campos  ="";
        $valores ="";

        foreach ($_POST as $key => $value) {
            if($key != "ajax" && $key != "accion" && $key != "edad" && $value != ""){

                if($key=='razon_social'){$value = strtoupper($value);}
                if($key=='contacto_obra'){$value = ucwords(strtolower($value));}


                $campos  .= $key.", ";
                $valores .= "'".$value."'".", ";

            } 
        }

        $campos = rtrim($campos, ", ");
        $valores = rtrim($valores, ", ");

        $campos = $campos.", log_usuario_id, log_accion";
        $valores = $valores.", ".$_SESSION['usuario']['id_usuario'].", '".$_POST['accion']."'";
        $db = conectDB();

        $query_id = "
        SELECT MAX(id_previsita) 
        FROM previsitas
        ";

        $id_previsita = $db->query($query_id);
        while($row = mysqli_fetch_array($id_previsita, MYSQLI_ASSOC)){$rows[] = $row;}
        $proximo_id = intval($rows[0]['MAX(id_previsita)']) + 1;
        $proximo_id = strval($proximo_id);


        $error_file = '';
        if ($_FILES["doc_visita"]["error"] === UPLOAD_ERR_OK) {
                $nombre_temporal = $_FILES["doc_visita"]["tmp_name"];
                $nombre_archivo = $proximo_id."-".normaliza_string($_FILES["doc_visita"]["name"]);
                $ruta_destino = "../09-adjuntos/previsita/".$nombre_archivo;

                if (move_uploaded_file($nombre_temporal, $ruta_destino)) {
                    $campos  .= ", doc_visita";
                    $valores .= ", '".$nombre_archivo."'";
                } else {
                    $error_file = "Ocurrio un error al intentar guardar el archivo adjunto.";
                }
        }else{
            $error_file = error_file($_FILES["doc_visita"]["error"]);
        }


        $query = "
        INSERT INTO previsitas (".$campos.")
        VALUES (".$valores.");"; // 
        $resultado = $db->query($query);


        // foreach ($rows as $key => $value) {
        //     $rows[$key]['calle'] = utf8_encode(ucwords(mb_strtolower($value['calle'])));
        // }
    
        mysqli_close($db);                           // cierra la base de datos

        if($metodo!="ajax"){
            return $rows; // es php devuelve un array()
        }else{
            echo json_encode(array('resultado' => $resultado, 'error_file' => $error_file)); // es ajax devuelve un json
        }
}
// end - funcion para dar de alta una previsitaa

// funcion modifica o eliminar una previsita
function savePrevisita($metodo){
        $_POST = cleanInput($_POST); //sanitiza las variables que vienen del formulario

        $campos  ="";
        $valores ="";

        $camposValues = "";

        // esta condición se hace porque en el caso de clientes algún campo hijo de provincia puede venir vacio. Si provincia viene vacio no hacemos nada, si viene con valor hay ver cuales de los campos siguientes tiene valor y cuales no
        if(isset($_POST['provincia_visita'])){
           if(!isset($_POST['partido_visita'])){$_POST['partido_visita']=null;} 
           if(!isset($_POST['localidad_visita'])){$_POST['localidad_visita']=null;}
           if(!isset($_POST['calle_visita'])){$_POST['calle_visita']=null;}
        }

        // campos check
        if (!array_key_exists('induccion_visita', $_POST)){$_POST['induccion_visita'] = 'n';}
        if (!array_key_exists('chaleco_visita', $_POST)){$_POST['chaleco_visita'] = 'n';}
        if (!array_key_exists('casco_visita', $_POST)){$_POST['casco_visita'] = 'n';}
        if (!array_key_exists('escalera_visita', $_POST)){$_POST['escalera_visita'] = 'n';}
        if (!array_key_exists('arnes_visita', $_POST)){$_POST['arnes_visita'] = 'n';}
        if (!array_key_exists('soga_visita', $_POST)){$_POST['soga_visita'] = 'n';}
        if (!array_key_exists('gafas_visita', $_POST)){$_POST['gafas_visita'] = 'n';}


        foreach ($_POST as $key => $value) {
            
            if($key != "ajax" && $key != "accion" && $key != "id_previsita"){
              if($key=='razon_social'){$value = strToMayus($value);}
              if($key=='contacto_obra'){$value = strMayusMinus($value);}
              if($key=='email_contacto_obra'){$value = strToMinus($value);
            }

              $camposValues  .= $key." = '".$value."', ";
            } 
        }



        $camposValues = rtrim($camposValues, ", ");
        $camposValues .= ", log_usuario_id = '".$_SESSION['usuario']['id_usuario']."'";
        if($_POST['accion'] == 'eliminar'){$camposValues .= ", log_accion = 'eliminar'";}
        $db = conectDB();
        $query = "
        UPDATE previsitas AS p
        SET ".$camposValues."
        WHERE p.id_previsita = ".$_POST['id_previsita'].";"; // 
  

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