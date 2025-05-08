<?php  
include_once '../04-modelo/aeoModel.php'; //conecta a la base de datos

if( isset($_POST['ajax']) && $_POST['ajax']=='on'){
    switch ($_POST['funcion']) {
        case 'poblarDatableAll':
             poblarDatableAll($_POST['tds'], 'ajax');
             break;
        case 'poblarDatableActivos':
             poblarDatableActivos($_POST['tds'], 'ajax');
             break;
    }
}

function poblarDatableActivos($tds, $via){     
   $all_aeo = modGetAllAeoActivos(); 		
   $filas = "";

   		foreach ($all_aeo as $key_all_aeo => $value_all_aeo) {
   		  $filas .= '<tr data-id="'.$value_all_aeo['id_usuario'].'">';		
	   		  foreach ($tds as $key_tds => $value_tds) {
	   		  	$filas .= '<td>'.$value_all_aeo[$value_tds].'</td>';
	   		  }
   		  $filas .= '<td class="text-center"><i class="v-icon-accion p-1 fas fa-solid fa-eye" data-accion="visual"></i><i class="v-icon-accion p-1 fas fa-edit" data-accion="editar"></i><i class="v-icon-accion p-1 fas fa-print" data-accion="pdf"></i></td>'; // acciones
		  $filas .='</tr>';
   		}	

	if($via != 'ajax'){
		return $filas;
	}else{
		echo json_encode($filas);
	}

}


function poblarDatableAll($tds, $via){     
   $all_aeo = modGetAllaeo(); 		
   $filas = "";

   		foreach ($all_aeo as $key_all_aeo => $value_all_aeo) {
   		  if($value_all_aeo['estado']=='Desactivado'){$clase=' class="text-warning"';}else{$clase=' class=""';}	
   		  $filas .= '<tr '.$clase.' data-id="'.$value_all_aeo['id_usuario'].'">';		
	   		  foreach ($tds as $key_tds => $value_tds) {
	   		  	$filas .= '<td>'.$value_all_aeo[$value_tds].'</td>';
	   		  }
   		  $filas .= '<td class="text-center"><i class="v-icon-accion p-1 fas fa-solid fa-eye" data-accion="visual"></i><i class="v-icon-accion p-1 fas fa-edit" data-accion="editar"></i><i class="v-icon-accion p-1 fas fa-print" data-accion="pdf"></i></td>'; // acciones
		  	  $filas .='</tr>';
   		}	

	if($via != 'ajax'){
		return $filas;
	}else{
		echo json_encode($filas);
	}


}


?>


