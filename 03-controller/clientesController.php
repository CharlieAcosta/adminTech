<?php  
include_once '../04-modelo/clientesModel.php'; //conecta a la base de datos

if( isset($_POST['ajax']) && $_POST['ajax']=='on'){
	poblarDatableAll($_POST['tds'], 'ajax', $_POST['filtro']);
}

function poblarDatableAll($tds, $via, $filtro){     
   $all_clientes = modGetAllClientes($filtro); 		
   $filas = "";
//var_dump($tds); die();
   		foreach ($all_clientes as $key_all_clientes => $value_all_clientes) {
   		  $filas .= '<tr data-id="'.$value_all_clientes['id_cliente'].'">';		
	   		  foreach ($tds as $key_tds => $value_tds) {

	   		  	if($value_tds=='log_alta'){$filas .= '<td>'.date('d-m-Y', strtotime($value_all_clientes[$value_tds])).'</td>';}else{$filas .= '<td>'.$value_all_clientes[$value_tds].'</td>';}

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


