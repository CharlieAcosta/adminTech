<?php  
include_once '../06-funciones_php/funciones.php'; //conecta a la base de datos
include_once '../04-modelo/obrasModel.php'; //conecta a la base de datos

if( isset($_POST['ajax']) && $_POST['ajax']=='on'){
	poblarDatableAll($_POST['tds'], 'ajax', $_POST['filtro']);
}

//////// function poblarDatableAll(columnas de la base, php o ajax, filtro){  

function poblarDatableAll($tds, $via, $filtro){     

   $all_registros = modGetAllRegistros($filtro); 		
	//var_dump($all_registros); die(); //[DEBUG PERMANENTE]

   $filas = "";
   		foreach ($all_registros as $key_all_registros => $value_all_registros) {

   		  // en el row el id de la entidad 
   		  $filas .= '<tr data-id="'.$value_all_registros['obra_id'].'">';		

   		  // columnas
     		  $filas .= '<td>'.$value_all_registros['obra_id'].'</td>';
   		  $filas .= '<td>'.$value_all_registros['obra_nombre'].'</td>';
   		  $filas .= '<td>'.strToDateFormat($value_all_registros['obra_fecha_inicio'], 'd/m/Y').'</td>';
   		  $filas .= '<td>'.strToDateFormat($value_all_registros['obra_fecha_fin'], 'd/m/Y').'</td>';
   		  $filas .= '<td class="text-center">'.$value_all_registros['obra_estado'].'</td>';

   		  // columnas de relleno
	   	  //$filas .='<td></td>';	  //para relleno en el caso de datos futuros

	   	  // columna de acciones
   		  $filas .= '<td class="text-center">';
   		  $filas .= '<i class="v-icon-accion p-1 fas fa-solid fa-eye" data-accion="visual"></i>';
   		  $filas .= '<i class="v-icon-accion p-1 fas fa-edit" data-accion="editar"></i>';
   		  //$filas .= '<i class="v-icon-accion p-1 fas fa-print" data-accion="pdf"></i>';
   		  $filas .= '<i class="v-icon-accion text-danger p-1 fas fa-trash-alt" data-accion="delete" data-id="'.$value_all_registros['obra_id'].'"></i>';
   		  $filas .= '</td>'; 

   		  // cierre de la fila (row)
			  $filas .='</tr>'; 
   		}	
   		//var_dump($filas); die(); //[DEBUG PERMANENTE]

	if($via != 'ajax'){
		return $filas;
	}else{
		echo json_encode($filas);
	}

}

?>


