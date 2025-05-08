<?php  
include_once '../06-funciones_php/funciones.php'; // conecta a la base de datos
include_once '../04-modelo/jornalesModel.php'; // modelo de tipo_jornales

if (isset($_POST['ajax']) && $_POST['ajax'] == 'on') {
	poblarDatableAll($_POST['tds'], 'ajax', $_POST['filtro']);
}

// función para poblar la DataTable con registros de tipo_jornales
function poblarDatableAll($tds, $via, $filtro) {
	$all_registros = modGetAllRegistros($filtro); 		
	$filas = "";
	foreach ($all_registros as $key_all_registros => $value_all_registros) {

		$filas .= '<tr data-id="' . $value_all_registros['jornal_id'] . '">';

		$filas .= '<td>' . $value_all_registros['jornal_id'] . '</td>';
		$filas .= '<td>' . htmlspecialchars($value_all_registros['jornal_descripcion']) . '</td>';
		$filas .= '<td>' . htmlspecialchars($value_all_registros['jornal_codigo']) . '</td>';
		$filas .= '<td class="text-right">$ ' . number_format($value_all_registros['jornal_valor'], 2, ',', '.') . '</td>';
		$filas .= '<td class="text-center">' . $value_all_registros['jornal_estado'] . '</td>';

		$filas .= '<td class="text-center">';
		$filas .= '<i class="v-icon-accion p-1 fas fa-solid fa-eye" data-accion="visual"></i>';
		$filas .= '<i class="v-icon-accion p-1 fas fa-edit" data-accion="editar"></i>';
		$filas .= '<i class="v-icon-accion text-danger p-1 fas fa-trash-alt" data-accion="delete" data-id="' . $value_all_registros['jornal_id'] . '"></i>';
		$filas .= '</td>';

		$filas .= '</tr>';
	}

	if ($via != 'ajax') {
		return $filas;
	} else {
		echo json_encode($filas);
	}
}
?>
