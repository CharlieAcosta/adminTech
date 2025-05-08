
<?php
// file name: novedadesModel.php
include_once '../04-modelo/conectDB.php'; // conecta a la base de datos
include_once '../06-funciones_php/funciones.php'; //funciones últiles

if( isset($_POST['via']) && $_POST['via']=='ajax'){
   switch ($_POST['funcion']) {
        case 'modGetUsuariosById':
             modGetUsuariosById($_POST['id'], 'ajax');
             break;
        case 'modGuardarEventos':
             // guarda eventos en la base  
             modGuardarEventos($_POST['eventos'], 'ajax');
             break;
        case 'modGetNovedadesByIdyMes':
            // guarda eventos en la base  
            modGetNovedadesByIdyMes($_POST['idAgente'], $_POST['viewYearMonth'],  $_POST['via']);
        break;
         }
}


// start - funcion guardar las novedades de las vista del calendarios
function modGuardarEventos($eventos, $metodo){     
   //var_dump($eventos);die();
   $db = conectDB();
   $resultados = [];
   $eventos_salida = $eventos;
   //var_dump($eventos); die();  
   foreach ($eventos as $key_eventos => $value_eventos) {
      switch ($value_eventos['accion']) {
         case 'delete':
            //var_dump($value_eventos['accion']);
            $query = "DELETE FROM novedades_personal_2 WHERE novedades_personal_2.id_novedad_per = '".$value_eventos["idNovedadPer"]."';"; // borra las novedades
            //var_dump($query);die();
            $resultado = $db->query($query);
            //var_dump($query);die();
            unset($eventos_salida[$key_eventos]);
            break;
         case 'update':
            //var_dump($value_eventos['accion']);
            $query = "UPDATE novedades_personal_2 SET novedades_personal_2.novedad_codigo = '".$value_eventos['NovedadCodigo']."'
            WHERE novedades_personal_2.id_novedad_per = '".$value_eventos['idNovedadPer']."'
            ;"; // modifica las novedades
            $resultado = $db->query($query);
            $eventos_salida[$key_eventos]['NovedadCodigo'] = $value_eventos['NovedadCodigo'];
            $eventos_salida[$key_eventos]['accion'] = 'insert';
         break;         
         case 'insert':
            //var_dump($value_eventos['accion']);
            $query = "INSERT INTO novedades_personal_2 (id_usuario, novedad_codigo, fecha)
            VALUES ('".$value_eventos['idUsuario']."', '".$value_eventos['NovedadCodigo']."', '".$key_eventos."');
            ;"; // agrega novedades
            $resultado = $db->query($query);
            $insertId = $db->insert_id;
            $eventos_salida[$key_eventos]['idNovedadPer'] = strval($insertId);
            $eventos_salida[$key_eventos]['accion'] = 'insert';
            break;  
      }



   }
   
   //while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}

   mysqli_close($db); // cierra la base de datos

   echo json_encode($eventos_salida); // es ajax devuelve un jason

   controlarDepuracion();

}
// end - funcion guardar las novedades de las vista del calendarios


// start - funcion para traer las novedades de un usuario (id) y de un mes
function modGetNovedadesByIdyMes($id_agente, $yearMonth, $metodo){     

   $db = conectDB();
   $query = "
   SELECT nov.id_novedad_per as id, noc.descripcion AS title ,nov.fecha AS start, noc.css AS className, noc.codigo
   FROM novedades_personal_2 AS nov
  
   LEFT JOIN novedad_codigos_2 AS noc
   ON noc.codigo = nov.novedad_codigo

   WHERE nov.id_usuario = '".$id_agente."' AND SUBSTR(nov.fecha,1,7) = '".$yearMonth."'
   ORDER BY start ASC
   ;"; // end - funcion para traer las novedades de un usuario (id) y de un mes

   $resultado = $db->query($query);
   while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}

   mysqli_close($db);                           // cierra la base de datos
   if(!isset($metodo) || $metodo !== 'ajax'){    
      // es php devuelve un array()
      if(isset($rows)){ 
         return $rows;  
      }else{
         return $rows = array();
      }
   }else{
      // es ajax devuelve un jason
      echo json_encode($rows); 
   }
}
// end - funcion para traer las novedades de un usuario (id) y de un mes

function modGuardaEventos($id_agente, $yearMonth){     
   $db = conectDB();
   $query = "
   SELECT nov.id_novedad_per as id, noc.descripcion AS title ,nov.fecha AS start, noc.css AS className, noc.codigo
   FROM novedades_personal AS nov
  
   LEFT JOIN novedad_codigos_2 AS noc
   ON noc.codigo = nov.novedad_codigo

   WHERE nov.id_usuario = '".$id_agente."' AND SUBSTR(nov.fecha,1,7) = '".$yearMonth."'
   ORDER BY start ASC
   ;"; // end - funcion para traer las novedades de un usuario (id) y de un mes

   $resultado = $db->query($query);
   while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}

   mysqli_close($db);                           // cierra la base de datos

   if(!isset($metodo)){    
      // es php devuelve un array()
      if(isset($rows)){ 
         return $rows;  
      }else{
         return $rows = array();
      }
   }else{
      // es ajax devuelve un jason
      echo json_encode($rows); 
   }
}
// end - funcion para traer las novedades de un usuario (id) y de un mes

// funcion para traer todos los usuarios activos con las novedades del mes
function modGetAllUsuariosActivos($yearMonth, $metodo = null){     
   $db = conectDB();

   // Inicializamos el array para evitar problemas si no hay resultados
   $rows = array();

   // Usar LEFT JOIN para incluir todos los usuarios, incluso si no tienen novedades
   $query = "
   SELECT  usu.id_usuario AS usuario_id, 
           CONCAT(usu.apellidos, ' ', usu.nombres) AS agente, 
           usu.nro_documento, 
           noc.*, 
           nov.* 
   
   FROM usuarios AS usu
   
   LEFT JOIN novedades_personal_2 AS nov
   ON nov.id_usuario = usu.id_usuario 
   AND SUBSTR(nov.fecha, 1, 7) = '".$yearMonth."'  -- Filtrar novedades por mes seleccionado
   
   LEFT JOIN novedad_codigos_2 AS noc
   ON noc.codigo = nov.novedad_codigo
   
   WHERE usu.perfil <> 'Super Administrador' 
   AND usu.estado = 'Activo'  -- Filtrar solo los usuarios que están activos
   
   ORDER BY usu.apellidos, noc.id_novedad_cod
   ;"; // Solo trae los usuarios activos
   // Ejecutar la consulta


   $resultado = $db->query($query);
   
   // Guardar los resultados en el array $rows
   while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){
       $rows[] = $row;
   }
// var_dump($yearMonth);
// var_dump($rows);
   // Cerrar la conexión
   mysqli_close($db);
   // Si no es una llamada AJAX, devuelve el resultado como array
   if(!isset($metodo) || $metodo !== 'ajax'){    
      // Si hay resultados, devolver el array. Si no, devolver array vacío.
      return !empty($rows) ? $rows : array();
   } else {
      // Si es una llamada AJAX, devolver los resultados como JSON
      echo json_encode($rows); 
   }
}


// funcion para traer todos los usuarios activos con las novedades del mes




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