<?php
include_once '../06-funciones_php/funciones.php'; //conecta a la base de datos
include_once '../04-modelo/conectDB.php'; // conecta a la base de datos
include_once '../04-modelo/presupuestoGeneradoModel.php';
include_once '../04-modelo/presupuestoMailConfigModel.php';



if( isset($_POST['via']) && $_POST['via']=='ajax'){
    header('Content-Type: application/json; charset=utf-8');
    
    switch ($_POST['funcion']) {
        case 'modGetClientesById':
             modGetClientesById($_POST['id'], 'ajax');
             break;
    }
}


// funcion para traer todos los usuarios activos
function modGetAllRegistros($filtro, $rangoTiempo = '30_dias', $busqueda = ''){     
   $db = conectDB();
   mysqli_set_charset($db, 'utf8mb4');

   $rangoTiempoNormalizado = strtoupper(trim((string)$rangoTiempo));
   $busqueda = trim((string)$busqueda);
   $filtroTiempoSql = '';
   switch ($rangoTiempoNormalizado) {
      case '15_DIAS':
         $filtroTiempoSql = " AND DATE(v.log_alta) >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)";
         break;
      case '30_DIAS':
         $filtroTiempoSql = " AND DATE(v.log_alta) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
         break;
      case 'TRIMESTRE':
         $filtroTiempoSql = " AND DATE(v.log_alta) >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
         break;
      case 'SEMESTRE':
         $filtroTiempoSql = " AND DATE(v.log_alta) >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
         break;
      case 'ANIO':
         $filtroTiempoSql = " AND DATE(v.log_alta) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
         break;
      default:
         $filtroTiempoSql = '';
         break;
   }

   $tieneTablaDocumentosEmitidos = tabla_existe($db, 'presupuesto_documentos_emitidos');
   $tieneTablaHistorialComercial = tabla_existe($db, 'presupuesto_historial_comercial');
   $tieneEstadoComercialSimulacion = columna_existe($db, 'presupuestos', 'estado_comercial_simulacion');
   $tieneEstadoComercialSmtp = columna_existe($db, 'presupuestos', 'estado_comercial_smtp');

   $selectEstadoComercialSimulacion = $tieneEstadoComercialSimulacion
      ? "p.estado_comercial_simulacion"
      : "'' AS estado_comercial_simulacion";
   $selectEstadoComercialSmtp = $tieneEstadoComercialSmtp
      ? "p.estado_comercial_smtp"
      : "'' AS estado_comercial_smtp";

   $joinDocumentosEmitidos = $tieneTablaDocumentosEmitidos
      ? "
   LEFT JOIN (
      SELECT
         id_presupuesto,
         COUNT(*) AS total_documentos_emitidos
      FROM presupuesto_documentos_emitidos
      GROUP BY id_presupuesto
   ) AS pd ON pd.id_presupuesto = p.id_presupuesto"
      : '';

   $selectDocumentosEmitidos = $tieneTablaDocumentosEmitidos
      ? 'COALESCE(pd.total_documentos_emitidos, 0) AS total_documentos_emitidos'
      : '0 AS total_documentos_emitidos';

   $joinHistorialComercial = $tieneTablaHistorialComercial
      ? "
   LEFT JOIN (
      SELECT
         id_previsita,
         id_presupuesto,
         SUM(CASE WHEN modo_circuito = 'simulacion' THEN 1 ELSE 0 END) AS total_historial_comercial_simulacion,
         SUM(CASE WHEN modo_circuito = 'smtp' THEN 1 ELSE 0 END) AS total_historial_comercial_smtp,
         MAX(CASE WHEN modo_circuito = 'simulacion' THEN id_historial_comercial ELSE 0 END) AS ultimo_historial_comercial_simulacion,
         MAX(CASE WHEN modo_circuito = 'smtp' THEN id_historial_comercial ELSE 0 END) AS ultimo_historial_comercial_smtp
      FROM presupuesto_historial_comercial
      GROUP BY id_previsita, id_presupuesto
   ) AS hc ON hc.id_previsita = v.id_previsita
          AND hc.id_presupuesto = p.id_presupuesto
   LEFT JOIN presupuesto_historial_comercial AS hcs ON hcs.id_historial_comercial = hc.ultimo_historial_comercial_simulacion
   LEFT JOIN presupuesto_historial_comercial AS hcm ON hcm.id_historial_comercial = hc.ultimo_historial_comercial_smtp"
      : '';

   $selectHistorialComercial = $tieneTablaHistorialComercial
      ? "
      COALESCE(hc.total_historial_comercial_simulacion, 0) AS total_historial_comercial_simulacion,
      COALESCE(hc.total_historial_comercial_smtp, 0) AS total_historial_comercial_smtp,
      COALESCE(hcs.estado_resultante, '') AS ultimo_estado_historial_comercial_simulacion,
      COALESCE(hcm.estado_resultante, '') AS ultimo_estado_historial_comercial_smtp"
      : "
      0 AS total_historial_comercial_simulacion,
      0 AS total_historial_comercial_smtp,
      '' AS ultimo_estado_historial_comercial_simulacion,
      '' AS ultimo_estado_historial_comercial_smtp";

   $exprBusquedaEstadoComercialSimulacion = $tieneEstadoComercialSimulacion
      ? "COALESCE(p.estado_comercial_simulacion, '')"
      : "''";
   $exprBusquedaEstadoComercialSmtp = $tieneEstadoComercialSmtp
      ? "COALESCE(p.estado_comercial_smtp, '')"
      : "''";
   $exprBusquedaHistorialSimulacion = $tieneTablaHistorialComercial
      ? "COALESCE(hcs.estado_resultante, '')"
      : "''";
   $exprBusquedaHistorialSmtp = $tieneTablaHistorialComercial
      ? "COALESCE(hcm.estado_resultante, '')"
      : "''";

   $filtroBusquedaSql = '';
   if ($busqueda !== '') {
      $busquedaEsc = mysqli_real_escape_string($db, $busqueda);
      $likeBusqueda = "'%" . $busquedaEsc . "%'";
      $filtroBusquedaSql = "
        AND (
            CAST(v.id_previsita AS CHAR) LIKE {$likeBusqueda}
            OR DATE_FORMAT(v.log_alta, '%d-%m-%Y') LIKE {$likeBusqueda}
            OR COALESCE(v.cuit, '') LIKE {$likeBusqueda}
            OR COALESCE(v.razon_social, '') LIKE {$likeBusqueda}
            OR COALESCE(v.requerimiento_tecnico, '') LIKE {$likeBusqueda}
            OR COALESCE(v.estado_visita, '') LIKE {$likeBusqueda}
            OR DATE_FORMAT(v.fecha_visita, '%d-%m-%Y') LIKE {$likeBusqueda}
            OR COALESCE(v.hora_visita, '') LIKE {$likeBusqueda}
            OR COALESCE(p.estado, '') LIKE {$likeBusqueda}
            OR {$exprBusquedaEstadoComercialSimulacion} LIKE {$likeBusqueda}
            OR {$exprBusquedaEstadoComercialSmtp} LIKE {$likeBusqueda}
            OR {$exprBusquedaHistorialSimulacion} LIKE {$likeBusqueda}
            OR {$exprBusquedaHistorialSmtp} LIKE {$likeBusqueda}
        )";
   }

   switch ($filtro) {
      case 'todos':
         $were    = "WHERE v.estado_visita <> 'Eliminada'{$filtroTiempoSql}{$filtroBusquedaSql}";
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
   SELECT 
      v.*,
      p.estado AS estado_presupuesto,
      {$selectEstadoComercialSimulacion},
      {$selectEstadoComercialSmtp},
      {$selectDocumentosEmitidos},
      {$selectHistorialComercial}
   FROM previsitas AS v
   LEFT JOIN (
      SELECT 
         id_previsita,
         MAX(id_presupuesto) AS max_id_presupuesto
      FROM presupuestos
      GROUP BY id_previsita
   ) AS up ON up.id_previsita = v.id_previsita
   LEFT JOIN presupuestos AS p ON p.id_presupuesto = up.max_id_presupuesto
   {$joinDocumentosEmitidos}
   {$joinHistorialComercial}
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
      echo json_encode($rows, JSON_UNESCAPED_UNICODE); // es ajax devuelve un jason
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
   LEFT JOIN     provincias  AS pro ON pro.id_provincia  = CAST(pv.provincia_visita  AS UNSIGNED)
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
           $rows[$key]['provincianom'] = ucwords(mb_strtolower((string)($value['provincianom'] ?? '')));
           $rows[$key]['partidonom']   = ucwords(mb_strtolower((string)($value['partidonom']   ?? '')));
           $rows[$key]['localidadnom'] = ucwords(mb_strtolower((string)($value['localidadnom'] ?? '')));
           $rows[$key]['callenom']     = ucwords(mb_strtolower((string)($value['callenom']     ?? '')));
       }
   }

   mysqli_close($db);

   if ($via !== 'ajax'){
       return $rows ?? [];
   } else {
       echo json_encode($rows ?? [], JSON_UNESCAPED_UNICODE);
   }
}
// end - funcion para dar de alta un agente




// $fechaActual = new DateTime(date("Y-m-d H:i:s", time()));
?>
