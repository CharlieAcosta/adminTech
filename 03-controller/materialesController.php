<?php
include_once '../06-funciones_php/funciones.php'; //conecta a la base de datos
include_once '../04-modelo/materialesModel.php'; //conecta a la base de datos

if( isset($_POST['ajax']) && $_POST['ajax']=='on'){
	header('Content-Type: application/json; charset=utf-8');
	poblarDatableAll($_POST['tds'], 'ajax', $_POST['filtro']);
}

function construirCeldaActualizacionMaterial($logEdicion, $logAlta, DateTimeImmutable $ahoraServidor): array {
	$fechaFormateada = '-';
	$fechaOrden = '';
	$minicardVigencia = '';
	$dataSearchVigencia = '';

	// Prioridad: log_edicion → log_alta → sin fecha
	$fechaRaw = null;
	if (is_string($logEdicion) && trim($logEdicion) !== '') {
		$fechaRaw = trim($logEdicion);
	} elseif (is_string($logAlta) && trim($logAlta) !== '') {
		$fechaRaw = trim($logAlta);
	}

	if ($fechaRaw !== null) {
		$fechaObjeto = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $fechaRaw);
		$errores = DateTimeImmutable::getLastErrors();
		$fechaValida = $fechaObjeto !== false
			&& ($errores === false
				|| ($errores['warning_count'] === 0 && $errores['error_count'] === 0));

		if ($fechaValida) {
			$fechaFormateada = $fechaObjeto->format('d/m/Y H:i:s');
			$fechaOrden = $fechaObjeto->format('Y-m-d H:i:s');
			$segundosTranscurridos = $ahoraServidor->getTimestamp() - $fechaObjeto->getTimestamp();
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

				$minicardVigencia = '<span class="materiales-vigencia-minicard ' . $clasesColorVigencia . '">'
					. '<span class="materiales-vigencia-minicard-titulo">' . $tituloVigencia . '</span>'
					. '<span class="materiales-vigencia-minicard-dias"><span class="materiales-vigencia-export-separador"> - </span>'
					. $diasTranscurridos . ' días</span>'
					. '</span>';
			}
		}
	}

	return [
		'celda_actualizacion' => '<td class="materiales-col-actualizacion" data-order="'
			. htmlspecialchars($fechaOrden, ENT_QUOTES, 'UTF-8') . '">'
			. '<span class="materiales-actualizacion-fecha">'
			. htmlspecialchars($fechaFormateada, ENT_QUOTES, 'UTF-8')
			. '</span></td>',
		'celda_vigencia' => '<td class="materiales-col-vigencia" data-search="' . $dataSearchVigencia . '">' . $minicardVigencia . '</td>',
	];
}

function poblarDatableAll($tds, $via, $filtro) {
	$all_registros = modGetAllRegistros($filtro);
	$ahoraServidor = new DateTimeImmutable('now');
	$filas = "";
	foreach ($all_registros as $value_all_registros) {

		$filas .= '<tr data-id="' . $value_all_registros['id_material'] . '">';

		$filas .= '<td>' . $value_all_registros['id_material'] . '</td>';
		$filas .= '<td>' . htmlspecialchars($value_all_registros['producto'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
		$filas .= '<td class="text-right">' . $value_all_registros['contenido'] . '</td>';
		$filas .= '<td class="text-left">' . htmlspecialchars($value_all_registros['unidad_rendimiento'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
		$filas .= '<td class="text-right">' . $value_all_registros['precio_unidad_venta'] . '</td>';
		$filas .= '<td class="text-center">' . htmlspecialchars($value_all_registros['estado_material'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';

		$celdasActualizacion = construirCeldaActualizacionMaterial($value_all_registros['log_edicion'] ?? null, $value_all_registros['log_alta'] ?? null, $ahoraServidor);
		$filas .= $celdasActualizacion['celda_actualizacion'];
		$filas .= $celdasActualizacion['celda_vigencia'];

		$filas .= '<td class="text-center">';
		$filas .= '<i class="v-icon-accion p-1 fas fa-solid fa-eye" data-accion="visual"></i>';
		$filas .= '<i class="v-icon-accion p-1 fas fa-edit" data-accion="editar"></i>';
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
