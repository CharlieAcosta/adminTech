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
        $db->set_charset("utf8mb4");

        $query_id = "
        SELECT MAX(id_previsita) 
        FROM previsitas
        ";

        $id_previsita = $db->query($query_id);
        while($row = mysqli_fetch_array($id_previsita, MYSQLI_ASSOC)){$rows[] = $row;}
        $proximo_id = intval($rows[0]['MAX(id_previsita)']) + 1;
        $proximo_id = strval($proximo_id);


        $error_file = '';
        if (isset($_FILES["doc_previsita"]) && $_FILES["doc_previsita"]["error"] === UPLOAD_ERR_OK) {
            $nombre_temporal = $_FILES["doc_previsita"]["tmp_name"];
            $nombre_archivo = $proximo_id."-".normaliza_string($_FILES["doc_previsita"]["name"]);
            $ruta_destino = "../09-adjuntos/previsita/".$nombre_archivo;
        
            if (move_uploaded_file($nombre_temporal, $ruta_destino)) {
                $campos  .= ", doc_previsita";
                $valores .= ", '".$nombre_archivo."'";
            } else {
                $error_file = "Ocurrió un error al intentar guardar el archivo adjunto.";
            }
        } elseif (isset($_FILES["doc_previsita"]) && $_FILES["doc_previsita"]["error"] !== UPLOAD_ERR_NO_FILE) {
            $error_file = error_file($_FILES["doc_previsita"]["error"]);
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
            echo json_encode(array('resultado' => $resultado, 'error_file' => $error_file));
        }
}
// end - funcion para dar de alta una previsitaa

// funcion modifica o eliminar una previsita
function savePrevisita($metodo){
    $error_file = '';
    $_POST = cleanInput($_POST); // sanitiza las variables

    $camposValues = "";

    if(isset($_POST['provincia_visita'])){
        if(!isset($_POST['partido_visita'])){$_POST['partido_visita']=null;} 
        if(!isset($_POST['localidad_visita'])){$_POST['localidad_visita']=null;}
        if(!isset($_POST['calle_visita'])){$_POST['calle_visita']=null;}
    }

    // Campos checkbox
    $checks = ['induccion_visita','chaleco_visita','casco_visita','escalera_visita','arnes_visita','soga_visita','gafas_visita'];
    foreach($checks as $check){
        if (!array_key_exists($check, $_POST)){$_POST[$check] = 'n';}
    }

    foreach ($_POST as $key => $value) {
        if($key != "ajax" && $key != "accion" && $key != "id_previsita"){
            if($key=='razon_social'){$value = strToMayus($value);}
            if($key=='contacto_obra'){$value = strMayusMinus($value);}
            if($key=='email_contacto_obra'){$value = strToMinus($value);}

            $camposValues .= $key." = '".$value."', ";
        } 
    }

    // Archivo adjunto
    if (isset($_FILES["doc_previsita"]) && $_FILES["doc_previsita"]["error"] === UPLOAD_ERR_OK) {
        $nombre_temporal = $_FILES["doc_previsita"]["tmp_name"];
        $nombre_archivo = $_POST['id_previsita']."-".normaliza_string($_FILES["doc_previsita"]["name"]);
        $ruta_destino = "../09-adjuntos/previsita/".$nombre_archivo;

        if (move_uploaded_file($nombre_temporal, $ruta_destino)) {
            $camposValues .= "doc_previsita = '".$nombre_archivo."', ";
        } else {
            $error_file = "Ocurrió un error al intentar guardar el archivo adjunto.";
        }
    } elseif (isset($_FILES["doc_previsita"]) && $_FILES["doc_previsita"]["error"] !== UPLOAD_ERR_NO_FILE) {
        $error_file = error_file($_FILES["doc_previsita"]["error"]);
    }    

    $camposValues = rtrim($camposValues, ", ");
    $camposValues .= ", log_usuario_id = '".$_SESSION['usuario']['id_usuario']."'";
    if($_POST['accion'] == 'eliminar'){$camposValues .= ", log_accion = 'eliminar'";}

    $db = conectDB();
    $db->set_charset("utf8mb4");
    
    $query = "UPDATE previsitas AS p SET ".$camposValues." WHERE p.id_previsita = ".$_POST['id_previsita'].";";
    $resultado = $db->query($query);

    mysqli_close($db);

    if($metodo != "ajax"){
        return true;
    } else {
        echo json_encode(array('resultado' => $resultado, 'error_file' => $error_file));
    }
}

// end - funcion para dar de alta un agente
?>