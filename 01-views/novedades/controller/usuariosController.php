<?php  
include_once 'modelo/usuariosModel.php'; //conecta a la base de dato

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
   $all_usuarios = modGetAllUsuariosActivos(); 		
   $filas = "";

   		foreach ($all_usuarios as $key_all_usuarios => $value_all_usuarios) {
   		  $filas .= '<tr data-id="'.$value_all_usuarios['id_usuario'].'">';		
	   		  foreach ($tds as $key_tds => $value_tds) {
	   		  	$filas .= '<td>'.$value_all_usuarios[$value_tds].'</td>';
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
   $all_usuarios = modGetAllUsuarios(); 		
   $filas = "";

   		foreach ($all_usuarios as $key_all_usuarios => $value_all_usuarios) {
   		  if($value_all_usuarios['estado']=='Desactivado'){$clase=' class="text-warning"';}else{$clase=' class=""';}	
   		  $filas .= '<tr '.$clase.' data-id="'.$value_all_usuarios['id_usuario'].'">';		
	   		  foreach ($tds as $key_tds => $value_tds) {
	   		  	$filas .= '<td>'.$value_all_usuarios[$value_tds].'</td>';
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


