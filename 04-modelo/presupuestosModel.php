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

   // 1) Habilitamos big selects SOLO en esta conexión (sesión actual)
   //    No toca config global ni .htaccess
   $db->query("SET SESSION SQL_BIG_SELECTS=1");

   // 2) Sanitizar id
   $id_safe = mysqli_real_escape_string($db, $id);

   // 3) STRAIGHT_JOIN fuerza a resolver pv (const por PK) primero
   //    y recién después los catálogos. CAST en el lado pv para evitar
   //    conversiones sobre índices de catálogos.
   $query = "
   SELECT
       pv.*,
       pro.provincia  AS provincianom,
       par.partido    AS partidonom,
       loc.localidad  AS localidadnom,
       cal.calle      AS callenom
   FROM previsitas AS pv
   STRAIGHT_JOIN provincias  AS pro ON pro.id_provincia  = CAST(pv.provincia_visita  AS UNSIGNED)
   LEFT JOIN     partidos    AS par ON par.id_partido    = CAST(pv.partido_visita    AS UNSIGNED)
   LEFT JOIN     localidades AS loc ON loc.id_localidad  = CAST(pv.localidad_visita  AS UNSIGNED)
   LEFT JOIN     calles      AS cal ON cal.id_calle      = CAST(pv.calle_visita      AS UNSIGNED)
   WHERE pv.id_previsita = '{$id_safe}'
   LIMIT 1
   ;";

   $resultado = $db->query($query);

   $rows = [];
   while ($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)) {
       $rows[] = $row;
   }

   if (!empty($rows)) {
       foreach ($rows as $key => $value) {
           $rows[$key]['provincianom'] = utf8_encode(ucwords(mb_strtolower($value['provincianom'] ?? '')));
           $rows[$key]['partidonom']   = utf8_encode(ucwords(mb_strtolower($value['partidonom']   ?? '')));
           $rows[$key]['localidadnom'] = utf8_encode(ucwords(mb_strtolower($value['localidadnom'] ?? '')));
           $rows[$key]['callenom']     = utf8_encode(ucwords(mb_strtolower($value['callenom']     ?? '')));
       }
   }

   mysqli_close($db);

   if ($via !== 'ajax'){
       return $rows ?? [];
   } else {
       echo json_encode($rows ?? []);
   }
}
// end - funcion para dar de alta un agente




// $fechaActual = new DateTime(date("Y-m-d H:i:s", time()));
?>