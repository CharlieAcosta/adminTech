<?php
include_once '../04-modelo/conectDB.php'; // conecta a la base de datos

if( isset($_POST['via']) && $_POST['via']=='ajax'){
    
    switch ($_POST['funcion']) {
        case 'modGetUsuariosById':
             modGetUsuariosById($_POST['id'], 'ajax');
             break;
    }
}


// funcion para traer todos los usuarios activos
function modGetAllUsuariosActivos(){     
   $db = conectDB();
   $query = "
   SELECT u.*
   FROM usuarios AS u
   WHERE u.estado <> 'Desactivado' AND u.estado <> 'Eliminado' AND u.perfil <> 'Super Administrador'
   ;"; // trae todos los usuarios

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
function modGetAllUsuarios(){     
   $db = conectDB();
   $query = "
   SELECT u.*
   FROM usuarios AS u
   WHERE u.estado <> 'Eliminado' AND u.perfil <> 'Super Administrador'
   ;"; // trae todos los usuarios

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
// end - funcion para traer todos los usuarios menos los eliminados

function modGetUsuariosById($id, $via){     
   $db = conectDB();
   $query = "
   SELECT u.*, pro.provincia AS provincianom, par.partido AS partidonom, loc.localidad AS localidadnom, cal.calle AS callenom
   FROM usuarios AS u
   LEFT JOIN provincias pro
   ON u.provincia = pro.id_provincia
   LEFT JOIN partidos par
   ON u.partido = par.id_partido
   LEFT JOIN localidades loc
   ON u.localidad = loc.id_localidad
   LEFT JOIN calles cal
   ON u.calle = cal.id_calle
   WHERE u.id_usuario = '".$id."'
   ;"; // trae todos los usuarios

        $resultado = $db->query($query);
        while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}

        foreach ($rows as $key => $value) {
            $rows[$key]['provincianom'] = utf8_encode(ucwords(mb_strtolower($value['provincianom'])));
            $rows[$key]['partidonom'] = utf8_encode(ucwords(mb_strtolower($value['partidonom'])));
            $rows[$key]['localidadnom'] = utf8_encode(ucwords(mb_strtolower($value['localidadnom'])));
            $rows[$key]['callenom'] = utf8_encode(ucwords(mb_strtolower($value['callenom'])));
        }


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