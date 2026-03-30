<?php  
include_once '../06-funciones_php/funciones.php'; //conecta a la base de datos
include_once '../04-modelo/presupuestosModel.php'; //conecta a la base de datos

if (isset($_POST['ajax']) && $_POST['ajax'] == 'on') {

    $perfil = $_SESSION['usuario']['perfil'] ?? null;
    $deleteIcon = array('Super Administrador', 'Administrador');

    poblarDatableAll(
        $_POST['tds'],
        'ajax',
        $_POST['filtro'],
        $perfil,
        $deleteIcon
    );
}
//////// function poblarDatableAll(columnas de la base, php o ajax, filtro){  

function poblarDatableAll($tds, $via, $filtro, $perfil, $deleteIcon){     

   $all_registros = modGetAllRegistros($filtro); 		
	//var_dump($all_registros); die(); //[DEBUG PERMANENTE]

   $filas = "";
   		foreach ($all_registros as $key_all_registros => $value_all_registros) {

   		$stringFechaHora = $value_all_registros['fecha_visita']." ".$value_all_registros['hora_visita'];
   		$resultadoFechaHora = comparaFechaHora($stringFechaHora, 'fh');

   		if($resultadoFechaHora['statusFechaHora'] == 'anterior' && ($value_all_registros['estado_visita'] == 'Programada' || $value_all_registros['estado_visita'] == 'Reprogramada')){  			
				$tabla = 'previsitas'; 
				$arraySet = ['estado_visita' => 'Vencida']; 
				$arrayWhere = [
					['columna' => 'id_previsita', 'condicion' => '=', 'valorCompara' => $value_all_registros['id_previsita']]
				];
				$callType = false;
				$valueStatus = false;
   			$resultadoUpdate = simpleUpdateInDB($tabla, $arraySet, $arrayWhere, $callType, $valueStatus);
   			if($resultadoUpdate == true){$value_all_registros['estado_visita'] = 'Vencida';}
   		}


   		  $filas .= '<tr data-id="'.$value_all_registros['id_previsita'].'">';		

			  $clases = '';
			  foreach ($tds as $key_tds => $value_tds) {   		  	
	   		  switch ($value_tds) {
		   		  	case 'log_alta':
		   		  		$filas .= '<td>'.date('d-m-Y', strtotime($value_all_registros[$value_tds])).'</td>';
		   		  	break;
		   		  
		   		   case 'estado_visita':
		   		   	if($value_all_registros[$value_tds] == 'Programada'){$clases = 'text-info'; $filas .= '<td class="text-info"><strong>'.$value_all_registros[$value_tds].'</strong></td>';} 
		   		   	if($value_all_registros[$value_tds] == 'Ejecutada'){$clases = 'text-success'; $filas .= '<td class="text-success"><strong>'.$value_all_registros[$value_tds].'</strong></td>';} 
		   		   	if($value_all_registros[$value_tds] == 'Cancelada'){$clases = 'text-secondary'; $filas .= '<td class="text-secondary"><strong>'.$value_all_registros[$value_tds].'</strong></td>';}
		   		   	if($value_all_registros[$value_tds] == 'Reprogramada'){$clases = 'text-primary'; $filas .= '<td class="text-primary"><strong>'.$value_all_registros[$value_tds].'</strong></td>';}
		   		   	if($value_all_registros[$value_tds] == 'Vencida'){$clases = 'text-danger'; $filas .= '<td class="text-danger"><strong>'.$value_all_registros[$value_tds].'</strong></td>';}
		   		   break;

		   		  case 'fecha_visita':
		   		  		$filas .= '<td class="'.$clases.'"><strong>'.date('d-m-Y', strtotime($value_all_registros[$value_tds])).'</strong></td>';
		   		  break;	

		   		  case 'hora_visita':
		   		  		$filas .= '<td class="'.$clases.'"><strong>'.$value_all_registros[$value_tds].'</strong></td>';
		   		  break;	

		   		  case 'razon_social':
		   		  		$filas .= '<td class=""><strong>'.$value_all_registros[$value_tds].'</strong></td>';
		   		  break;

		   		  default:
		   		  		$filas .= '<td>'.$value_all_registros[$value_tds].'</td>';
		   		  break;
		   		  }

	   		  }

		  // Columna Presupuesto (solo si la visita está Ejecutada)
		  $presupuestoHtml = '';

		  if (isset($value_all_registros['estado_visita']) && $value_all_registros['estado_visita'] === 'Ejecutada') {

		  	$estadoPresupuesto = isset($value_all_registros['estado_presupuesto']) ? $value_all_registros['estado_presupuesto'] : null;

		  	// Si no existe presupuesto asociado, la leyenda es Pendiente (en rojo)
		  	if ($estadoPresupuesto === null || $estadoPresupuesto === '') {
		  		$presupuestoHtml = '<span class="text-danger"><strong>Pendiente</strong></span>';
		  	} else {

		  		// Mapeo de colores por estado
		  		$claseEstado = 'text-secondary'; // default conservador

		  		switch ($estadoPresupuesto) {
		  			case 'Borrador':
		  				$claseEstado = 'text-secondary';
		  				break;
		  			case 'Impreso':
		  				$claseEstado = 'text-info';
		  				break;
		  			case 'Enviado':
		  				$claseEstado = 'text-primary';
		  				break;
		  			case 'Aprobado':
		  				$claseEstado = 'text-success';
		  				break;
		  			case 'Rechazado':
		  				$claseEstado = 'text-danger';
		  				break;
		  			case 'Pendiente':
		  				// Si el registro existe pero está en Pendiente, lo mantenemos en rojo también
		  				$claseEstado = 'text-danger';
		  				break;
		  		}

		  		$presupuestoHtml = '<span class="'.$claseEstado.'"><strong>'.$estadoPresupuesto.'</strong></span>';
		  	}
		  }

		  // Presupuesto + Orden de compra (por ahora OC sigue vacía)
	   	  $filas .= '<td>'.$presupuestoHtml.'</td><td></td>';	  //para relleno en el caso de datos futuros

			// acciones				
			$filas .= '<td class="text-center">';

			$filas .= '<i class="v-icon-accion p-1 fas fa-solid fa-eye" 
						data-accion="visual" 
						data-toggle="tooltip" 
						title="Visualizar"></i>';

			$filas .= '<i class="v-icon-accion p-1 fas fa-edit" 
						data-accion="editar" 
						data-toggle="tooltip" 
						title="Editar"></i>';

			$filas .= '<i class="v-icon-accion p-1 fas fa-print" 
						style="pointer-events:none;opacity:.4;" 
						data-accion="pdf" 
						data-toggle="tooltip" 
						title="Imprimir PDF"></i>';

			$filas .= '<i class="v-icon-accion p-1 fas fa-paperclip" 
						style="pointer-events:none;opacity:.4;" 
						data-accion="adjunto" 
						data-toggle="tooltip" 
						title="Ver adjuntos"></i>';

			$filas .= '<i class="v-icon-accion p-1 fas fa-history" 
						data-accion="historial" 
						data-toggle="tooltip" 
						title="Historial de presupuesto"></i>';

			if (in_array($perfil, $deleteIcon)){ 				
				$filas .= '<i class="v-icon-accion text-danger p-1 fas fa-trash-alt" 
							data-accion="delete" 
							data-id="'.$value_all_registros['id_previsita'].'" 
							data-toggle="tooltip" 
							title="Eliminar"></i>';
			}				

			$filas .= '</td>';
			$filas .='</tr>';
   		}	

	//var_dump($filas); die();
	if($via != 'ajax'){
		return $filas;
	}else{
		echo json_encode($filas);
	}

}

?>
