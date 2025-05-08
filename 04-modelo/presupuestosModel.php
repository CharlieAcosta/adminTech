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
         $were    = "WHERE v.estado_visita <> 'Eliminada'";
         $orderBy = "ORDER BY v.estado_visita ASC, v.fecha_visita ASC, v.hora_visita ASC";
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
   SELECT v.*
   FROM previsitas AS v
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

function modGetPresupuestoById($id, $via){     
   $db = conectDB();
   $query = "
   SELECT pv.*, pro.provincia AS provincianom, par.partido AS partidonom, loc.localidad AS localidadnom, cal.calle AS callenom
   FROM previsitas AS pv
   LEFT JOIN provincias pro
   ON pv.provincia_visita = pro.id_provincia
   LEFT JOIN partidos par
   ON pv.partido_visita = par.id_partido
   LEFT JOIN localidades loc
   ON pv.localidad_visita = loc.id_localidad
   LEFT JOIN calles cal
   ON pv.calle_visita = cal.id_calle
   WHERE pv.id_previsita = '".$id."'
   ;"; // trae todos los usuarios

        $resultado = $db->query($query);

        while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}

        foreach ($rows as $key => $value) {
            $rows[$key]['provincianom'] = utf8_encode(ucwords(mb_strtolower($value['provincianom'])));
            $rows[$key]['partidonom'] = utf8_encode(ucwords(mb_strtolower($value['partidonom'])));
            $rows[$key]['localidadnom'] = utf8_encode(ucwords(mb_strtolower($value['localidadnom'])));
            $rows[$key]['callenom'] = utf8_encode(ucwords(mb_strtolower($value['callenom'])));
        }
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