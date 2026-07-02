<?php  
include_once '../06-funciones_php/funciones.php'; // conecta a la base de datos
include_once '../04-modelo/jornalesModel.php'; // modelo de tipo_jornales

if (isset($_POST['ajax']) && $_POST['ajax'] == 'on') {
	header('Content-Type: application/json; charset=utf-8');
	poblarDatableAll($_POST['tds'], 'ajax', $_POST['filtro']);
}

function construirCeldaActualizacionJornal($fechaActualizacion, DateTimeImmutable $ahoraServidor): array {
	$fechaActualizacionFormateada = '-';
	$fechaActualizacionOrden = '';
	$minicardVigencia = '';
	$dataSearchVigencia = '';

	if (is_string($fechaActualizacion) && trim($fechaActualizacion) !== '') {
		$fechaActualizacionObjeto = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', trim($fechaActualizacion));
		$erroresFechaActualizacion = DateTimeImmutable::getLastErrors();
		$fechaActualizacionValida = $fechaActualizacionObjeto !== false
			&& ($erroresFechaActualizacion === false
				|| ($erroresFechaActualizacion['warning_count'] === 0 && $erroresFechaActualizacion['error_count'] === 0));

		if ($fechaActualizacionValida) {
			$fechaActualizacionFormateada = $fechaActualizacionObjeto->format('d/m/Y H:i:s');
			$fechaActualizacionOrden = $fechaActualizacionObjeto->format('Y-m-d H:i:s');
			$segundosTranscurridos = $ahoraServidor->getTimestamp() - $fechaActualizacionObjeto->getTimestamp();
			if ($segundosTranscurridos >= 0) {
				$diasTranscurridos = intdiv($segundosTranscurridos, 86400);
				if ($diasTranscurridos <= 22) {
					$tituloVigencia = 'Vigente';
					$clasesColorVigencia = 'bg-success text-white';
					$dataSearchVigencia = 'vigente';
				} elseif ($diasTranscurridos <= 30) {
					$tituloVigencia = 'Próxima a vencer';
					$clasesColorVigencia = 'bg-warning text-dark';
					$dataSearchVigencia = 'proxima_vencer';
				} else {
					$tituloVigencia = 'Desactualizada';
					$clasesColorVigencia = 'bg-danger text-white';
					$dataSearchVigencia = 'desactualizada';
				}

				$minicardVigencia = '<span class="jornales-vigencia-minicard ' . $clasesColorVigencia . '">'
					. '<span class="jornales-vigencia-minicard-titulo">' . $tituloVigencia . '</span>'
					. '<span class="jornales-vigencia-minicard-dias"><span class="jornales-vigencia-export-separador"> - </span>'
					. $diasTranscurridos . ' días</span>'
					. '</span>';
			}
		}
	}

	return [
		'celda_actualizacion' => '<td class="jornales-col-actualizacion" data-order="'
			. htmlspecialchars($fechaActualizacionOrden, ENT_QUOTES, 'UTF-8') . '">'
			. '<span class="jornales-actualizacion-fecha">'
			. htmlspecialchars($fechaActualizacionFormateada, ENT_QUOTES, 'UTF-8')
			. '</span></td>',
		'celda_vigencia' => '<td class="jornales-col-vigencia" data-search="' . $dataSearchVigencia . '">' . $minicardVigencia . '</td>',
	];
}

// función para poblar la DataTable con registros de tipo_jornales
function poblarDatableAll($tds, $via, $filtro) {
	$all_registros = modGetAllRegistros($filtro); 		
	$ahoraServidor = new DateTimeImmutable('now');
	$filas = "";
	foreach ($all_registros as $key_all_registros => $value_all_registros) {

		$filas .= '<tr data-id="' . $value_all_registros['jornal_id'] . '">';

		$filas .= '<td>' . $value_all_registros['jornal_id'] . '</td>';
		$filas .= '<td>' . htmlspecialchars($value_all_registros['jornal_descripcion'], ENT_QUOTES, 'UTF-8') . '</td>';
		$filas .= '<td>' . htmlspecialchars($value_all_registros['jornal_codigo'], ENT_QUOTES, 'UTF-8') . '</td>';
		$filas .= '<td class="text-right">$ ' . number_format($value_all_registros['jornal_valor'], 2, ',', '.') . '</td>';

		$filas .= '<td class="text-center">' . htmlspecialchars($value_all_registros['jornal_estado'], ENT_QUOTES, 'UTF-8') . '</td>';
		$celdasActualizacion = construirCeldaActualizacionJornal($value_all_registros['updated_at'] ?? null, $ahoraServidor);
		$filas .= $celdasActualizacion['celda_actualizacion'];
		$filas .= $celdasActualizacion['celda_vigencia'];

		$filas .= '<td class="text-center jornales-col-acciones">';
		$filas .= '<i class="v-icon-accion p-1 fas fa-solid fa-eye" data-accion="visual"></i>';
		$filas .= '<i class="v-icon-accion p-1 fas fa-edit" data-accion="editar"></i>';
		$filas .= '<i class="v-icon-accion text-danger p-1 fas fa-trash-alt" data-accion="delete" data-id="' . $value_all_registros['jornal_id'] . '"></i>';
		$filas .= '</td>';

		$filas .= '</tr>';
	}

	if ($via != 'ajax') {
		return $filas;
	} else {
		echo json_encode($filas, JSON_UNESCAPED_UNICODE);
	}
}
?>
