<?php
include_once '../06-funciones_php/funciones.php'; //conecta a la base de datos
include_once '../04-modelo/conectDB.php'; // conecta a la base de datos



if( isset($_POST['via']) && $_POST['via']=='ajax'){
    
    switch ($_POST['funcion']) {
        case 'modGetClientesById':
             modGetClientesById($_POST['id'], 'ajax');
             break;
    }
}


// funcion para traer todos los usuarios activos
function modGetAllRegistros($filtro){     
   $db = conectDB();
   switch ($filtro) {
      case 'todos':
         $were    = "WHERE t.estado_material <> 'Eliminado'";
         $orderBy = "ORDER BY t.descripcion_corta ASC";
         break;

      // case 'activos':
      //    $were = "WHERE v.estado = 'Activo'";
      //    break;
      
      // case 'potenciales':
      //    $were = "WHERE v.estado = 'Potencial'";
      //    break;

      // case 'desactivados':
      //    $were = "WHERE v.estado = 'Desactivado'";
      //    break;
      // default:
      //    // code...
      //    break;
   }

   $query = "
   SELECT t.*
   FROM materiales AS t
   ".$were."
   ".$orderBy."
   ;";

   $resultado = $db->query($query);
   while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}
   //var_dump($rows); die(); // [DEBUG PERMANENTE]

   mysqli_close($db); // cierra la base de datos

   
   if(!isset($metodo)){    
      if(isset($rows)){ 
         return $rows; // es php devuelve un array() 
      }else{
         return $rows = array();
      }
   }else{
      echo json_encode($rows); // es ajax devuelve un jason
   }
}

function modGetMaterialById($id, $via){     
   $db = conectDB();
   $query = "
   SELECT m.*
   FROM materiales AS m
   WHERE m.id_material = '".$id."'
   ;"; // trae el material
        $resultado = $db->query($query);

        while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}

//var_dump($rows); die();

        mysqli_close($db);                           // cierra la base de datos

   if($via != 'ajax'){    
      return $rows; // es php devuelve un array()
   }else{
      echo json_encode($rows); // es ajax devuelve un jason
   }
}
// end - funcion para dar de alta un agente




// $fechaActual = new DateTime(date("Y-m-d H:i:s", time()));
?>