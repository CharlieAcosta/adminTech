<?php
include_once '../04-modelo/conectDB.php'; // conecta a la base de datos

if( isset($_POST['via']) && $_POST['via']=='ajax'){
    
    switch ($_POST['funcion']) {
        case 'modGetClientesById':
             modGetClientesById($_POST['id'], 'ajax');
             break;
    }
}


// funcion para traer todos los usuarios activos
function modGetAllClientes($filtro){     
   $db = conectDB();

   switch ($filtro) {
      case 'todos':
         $were = "WHERE c.estado <> 'Eliminado'";
         break;

      case 'activos':
         $were = "WHERE c.estado = 'Activo'";
         break;
      
      case 'potenciales':
         $were = "WHERE c.estado = 'Potencial'";
         break;

      case 'desactivados':
         $were = "WHERE c.estado = 'Desactivado'";
         break;
      // default:
      //    // code...
      //    break;
   }

   $query = "
   SELECT c.*
   FROM clientes AS c
   ".$were."
   ;";

   $resultado = $db->query($query);
   while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}

   mysqli_close($db);                           // cierra la base de datos

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
// end - funcion para traer todos los usuarios activos


// funcion para traer todos los usuarios menos los eliminados
// function modGetAllUsuarios(){     
//    $db = conectDB();
//    $query = "
//    SELECT u.*
//    FROM usuarios AS u
//    WHERE u.estado <> 'Eliminado' AND u.perfil <> 'Super Administrador'
//    ;"; // trae todos los usuarios

//    $resultado = $db->query($query);
//    while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}

//    mysqli_close($db);                           // cierra la base de datos

//    if(!isset($metodo)){    
//       if(isset($rows)){ 
//          return $rows; // es php devuelve un array() 
//       }else{
//          return $rows = array();
//       }
//    }else{
//       echo json_encode($rows); // es ajax devuelve un jason
//    }
// }
// end - funcion para traer todos los usuarios menos los eliminados

function modGetClientesById($id, $via){     
   $db = conectDB();
   $query = "
   SELECT c.*, pro.provincia AS provincianom, par.partido AS partidonom, loc.localidad AS localidadnom, cal.calle AS callenom
   FROM clientes AS c
   LEFT JOIN provincias pro
   ON c.dirfis_provincia = pro.id_provincia
   LEFT JOIN partidos par
   ON c.dirfis_partido = par.id_partido
   LEFT JOIN localidades loc
   ON c.dirfis_localidad = loc.id_localidad
   LEFT JOIN calles cal
   ON c.dirfis_calle = cal.id_calle
   WHERE c.id_cliente = '".$id."'
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