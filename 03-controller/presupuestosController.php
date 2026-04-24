<?php  
include_once '../06-funciones_php/funciones.php'; //conecta a la base de datos
include_once '../04-modelo/presupuestosModel.php'; //conecta a la base de datos
include_once '../04-modelo/presupuestoIntervencionesModel.php';
include_once '../04-modelo/presupuestoComercialLockModel.php';

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

if (!function_exists('escapeHtmlSeguimientoListado')) {
    function escapeHtmlSeguimientoListado($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('construirBadgeEstadoSeguimientoListado')) {
    function construirBadgeEstadoSeguimientoListado(string $label, string $badgeClass): string
    {
        return '<span class="badge badge-pill estado-chip ' . escapeHtmlSeguimientoListado($badgeClass) . '">'
            . escapeHtmlSeguimientoListado($label)
            . '</span>';
    }
}

if (!function_exists('resolverBadgeEstadoVisitaSeguimientoListado')) {
    function resolverBadgeEstadoVisitaSeguimientoListado(?string $estado): array
    {
        $normalizado = strtoupper(trim((string)$estado));
        $label = trim((string)$estado);
        $textClass = 'text-secondary';
        $badgeClass = 'badge-secondary';

        switch ($normalizado) {
            case 'PROGRAMADA':
                $label = 'Programada';
                $textClass = 'text-info';
                $badgeClass = 'badge-info';
                break;
            case 'EJECUTADA':
                $label = 'Ejecutada';
                $textClass = 'text-success';
                $badgeClass = 'badge-success';
                break;
            case 'CANCELADA':
                $label = 'Cancelada';
                $textClass = 'text-secondary';
                $badgeClass = 'badge-secondary';
                break;
            case 'REPROGRAMADA':
                $label = 'Reprogramada';
                $textClass = 'text-primary';
                $badgeClass = 'badge-primary';
                break;
            case 'VENCIDA':
                $label = 'Vencida';
                $textClass = 'text-danger';
                $badgeClass = 'badge-danger';
                break;
        }

        return [
            'normalizado' => $normalizado,
            'label' => $label,
            'text_class' => $textClass,
            'badge_class' => $badgeClass,
            'html' => $label !== '' ? construirBadgeEstadoSeguimientoListado($label, $badgeClass) : ''
        ];
    }
}

if (!function_exists('resolverBadgeEstadoPresupuestoSeguimientoListado')) {
    function resolverBadgeEstadoPresupuestoSeguimientoListado(?string $estado): array
    {
        $normalizado = strtoupper(trim((string)$estado));
        if ($normalizado === 'IMPRESO') {
            $normalizado = 'EMITIDO';
        }

        if ($normalizado === '') {
            return [
                'normalizado' => '',
                'label' => '',
                'badge_class' => 'badge-secondary',
                'html' => ''
            ];
        }

        $label = trim((string)$estado);
        $badgeClass = 'badge-secondary';

        switch ($normalizado) {
            case 'BORRADOR':
                $label = 'Borrador';
                $badgeClass = 'badge-secondary';
                break;
            case 'EMITIDO':
                $label = 'Emitido';
                $badgeClass = 'badge-info';
                break;
            case 'ENVIADO':
                $label = 'Enviado';
                $badgeClass = 'badge-primary';
                break;
            case 'RECIBIDO':
                $label = 'Recibido';
                $badgeClass = 'badge-success';
                break;
            case 'RESOLICITADO':
                $label = 'Resolicitado';
                $badgeClass = 'badge-warning';
                break;
            case 'APROBADO':
                $label = 'Aprobado';
                $badgeClass = 'badge-success';
                break;
            case 'RECHAZADO':
                $label = 'Rechazado';
                $badgeClass = 'badge-danger';
                break;
            case 'CANCELADO':
                $label = 'Cancelado';
                $badgeClass = 'badge-dark';
                break;
            case 'PENDIENTE':
                $label = 'Pendiente';
                $badgeClass = 'badge-danger';
                break;
        }

        return [
            'normalizado' => $normalizado,
            'label' => $label,
            'badge_class' => $badgeClass,
            'html' => construirBadgeEstadoSeguimientoListado($label, $badgeClass)
        ];
    }
}

if (!function_exists('formatearDescripcionSentenceCaseSeguimientoListado')) {
    function formatearDescripcionSentenceCaseSeguimientoListado(?string $texto): string
    {
        $texto = trim((string)($texto ?? ''));
        if ($texto === '') {
            return '';
        }

        $texto = mb_strtolower($texto, 'UTF-8');
        $primeraLetra = mb_substr($texto, 0, 1, 'UTF-8');
        $resto = mb_substr($texto, 1, null, 'UTF-8');

        return mb_strtoupper($primeraLetra, 'UTF-8') . $resto;
    }
}

if (!function_exists('resolverDescripcionSeguimientoListado')) {
    function resolverDescripcionSeguimientoListado(?string $texto): array
    {
        $textoPlano = function_exists('textoPlanoDetalleTareaPresupuesto')
            ? textoPlanoDetalleTareaPresupuesto($texto)
            : trim((string)($texto ?? ''));

        $textoPlano = preg_replace('/\s+/u', ' ', (string)$textoPlano);
        $textoPlano = trim((string)$textoPlano);

        $resumen = $textoPlano;
        if ($textoPlano !== '' && function_exists('resumirTextoSegunReglaPresupuesto')) {
            $resumen = resumirTextoSegunReglaPresupuesto($textoPlano);
        }

        $textoPlano = formatearDescripcionSentenceCaseSeguimientoListado($textoPlano);
        $resumen = formatearDescripcionSentenceCaseSeguimientoListado($resumen);

        return [
            'texto_completo' => $textoPlano,
            'resumen' => $resumen,
        ];
    }
}

function poblarDatableAll($tds, $via, $filtro, $perfil, $deleteIcon){     

   $all_registros = modGetAllRegistros($filtro); 		
   $modoCircuitoActivo = obtenerModoActivoCircuitoComercialPresupuestos();
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

		  $estadoVisitaVisual = resolverBadgeEstadoVisitaSeguimientoListado($value_all_registros['estado_visita'] ?? '');

		  $presupuestoHtml = '';
		  $estadoVisiblePresupuestoNormalizado = '';
		  $mostrarIconoDocumentosEmitidos = false;
		  $mostrarIconoHistorialPresupuesto = false;
		  $editarBloqueadoComercial = false;
		  $editarBloqueadoVisita = false;
		  $editarBloqueadoEstado = '';
		  $editarBloqueadoTooltip = 'Editar';

		  if ($estadoVisitaVisual['normalizado'] === 'CANCELADA') {
		  	$editarBloqueadoVisita = true;
		  	$editarBloqueadoTooltip = 'La edicion no esta disponible para pre-visitas canceladas.';
		  }

		  if ($estadoVisitaVisual['normalizado'] === 'EJECUTADA') {
            $estadoPresupuesto = isset($value_all_registros['estado_presupuesto']) ? $value_all_registros['estado_presupuesto'] : null;
            $estadoComercialSimulacion = isset($value_all_registros['estado_comercial_simulacion']) ? $value_all_registros['estado_comercial_simulacion'] : '';
            $estadoComercialSmtp = isset($value_all_registros['estado_comercial_smtp']) ? $value_all_registros['estado_comercial_smtp'] : '';
            $totalDocumentosEmitidos = (int)($value_all_registros['total_documentos_emitidos'] ?? 0);
            $totalHistorialComercialActivo = $modoCircuitoActivo === 'smtp'
                ? (int)($value_all_registros['total_historial_comercial_smtp'] ?? 0)
                : (int)($value_all_registros['total_historial_comercial_simulacion'] ?? 0);

            $presupuestoActual = [
                'estado' => $estadoPresupuesto,
                'estado_comercial_simulacion' => $estadoComercialSimulacion,
                'estado_comercial_smtp' => $estadoComercialSmtp,
            ];

            $estadoVisiblePresupuesto = resolverEstadoVisiblePresupuestoDesdePresupuesto(
                $presupuestoActual,
                $modoCircuitoActivo
            );
            $estadoComercialActivo = obtenerEstadoComercialActivoDesdePresupuesto(
                $presupuestoActual,
                $modoCircuitoActivo
            );

            if ($estadoPresupuesto === null || $estadoPresupuesto === '') {
                $presupuestoVisual = resolverBadgeEstadoPresupuestoSeguimientoListado('PENDIENTE');
            } else {
                $presupuestoVisual = resolverBadgeEstadoPresupuestoSeguimientoListado($estadoVisiblePresupuesto);
                $mostrarIconoDocumentosEmitidos = $totalDocumentosEmitidos > 0;
                $mostrarIconoHistorialPresupuesto = $estadoComercialActivo !== '' || $totalHistorialComercialActivo > 0;

                    $editarBloqueadoEstado = $estadoComercialActivo !== ''
                        ? $estadoComercialActivo
                        : $presupuestoVisual['normalizado'];
                    $editarBloqueadoComercial = estadoBloqueaEdicionComercialPresupuesto($editarBloqueadoEstado);
                    if ($editarBloqueadoComercial) {
                        $editarBloqueadoTooltip = mensajeBloqueoEdicionComercialPresupuesto($editarBloqueadoEstado);
                    }
            }

            $presupuestoHtml = $presupuestoVisual['html'];
            $estadoVisiblePresupuestoNormalizado = $presupuestoVisual['normalizado'];
		  }

          $filas .= '<tr data-id="'.$value_all_registros['id_previsita'].'" data-estado-visita="'.escapeHtmlSeguimientoListado($estadoVisitaVisual['normalizado']).'" data-estado-presupuesto="'.escapeHtmlSeguimientoListado($estadoVisiblePresupuestoNormalizado).'">';

			  $clases = '';
			  foreach ($tds as $key_tds => $value_tds) {   		  	
	   		  switch ($value_tds) {
		   		  	case 'log_alta':
		   		  		$filas .= '<td>'.date('d-m-Y', strtotime($value_all_registros[$value_tds])).'</td>';
		   		  	break;
		   		  
		   		   case 'estado_visita':
                    $clases = $estadoVisitaVisual['text_class'];
                    $filas .= '<td class="text-center align-middle">'.$estadoVisitaVisual['html'].'</td>';
		   		   break;

		   		  case 'fecha_visita':
		   		  		$filas .= '<td class="'.$clases.'"><strong>'.date('d-m-Y', strtotime($value_all_registros[$value_tds])).'</strong></td>';
		   		  break;	

		   		  case 'hora_visita':
		   		  		$filas .= '<td class="'.$clases.'"><strong>'.$value_all_registros[$value_tds].'</strong></td>';
		   		  break;	

		   		  case 'razon_social':
		   		  		$filas .= '<td class="seguimiento-col-razon-social"><strong>'.escapeHtmlSeguimientoListado($value_all_registros[$value_tds]).'</strong></td>';
		   		  break;

		   		  case 'requerimiento_tecnico':
                        $descripcionSeguimiento = resolverDescripcionSeguimientoListado($value_all_registros[$value_tds] ?? '');
                        $descripcionCompleta = $descripcionSeguimiento['texto_completo'];
                        $descripcionResumen = $descripcionSeguimiento['resumen'];
                        $descripcionHtml = $descripcionResumen !== ''
                            ? '<span class="seguimiento-descripcion-corta" title="'.escapeHtmlSeguimientoListado($descripcionCompleta).'">'.escapeHtmlSeguimientoListado($descripcionResumen).'</span>'
                            : '<span class="text-muted">-</span>';
		   		  		$filas .= '<td class="align-middle seguimiento-col-descripcion" data-search="'.escapeHtmlSeguimientoListado($descripcionCompleta).'">'.$descripcionHtml.'</td>';
		   		  break;

		   		  default:
		   		  		$filas .= '<td>'.$value_all_registros[$value_tds].'</td>';
		   		  break;
		   		  }

	   		  }

		  // Columna Presupuesto (solo si la visita está Ejecutada)
		  $presupuestoHtml = $presupuestoHtml; // legado: el estado ya fue calculado arriba
		  $mostrarIconoDocumentosEmitidos = $mostrarIconoDocumentosEmitidos;
		  $mostrarIconoHistorialPresupuesto = $mostrarIconoHistorialPresupuesto;
		  $editarBloqueadoComercial = $editarBloqueadoComercial;
		  $editarBloqueadoEstado = $editarBloqueadoEstado;
		  $editarBloqueadoTooltip = $editarBloqueadoTooltip;

		  if (false && isset($value_all_registros['estado_visita']) && $value_all_registros['estado_visita'] === 'Ejecutada') { // legado

		  	$estadoPresupuesto = isset($value_all_registros['estado_presupuesto']) ? $value_all_registros['estado_presupuesto'] : null;
		  	$estadoComercialSimulacion = isset($value_all_registros['estado_comercial_simulacion']) ? $value_all_registros['estado_comercial_simulacion'] : '';
		  	$estadoComercialSmtp = isset($value_all_registros['estado_comercial_smtp']) ? $value_all_registros['estado_comercial_smtp'] : '';
		  	$totalDocumentosEmitidos = (int)($value_all_registros['total_documentos_emitidos'] ?? 0);
		  	$totalHistorialComercialActivo = $modoCircuitoActivo === 'smtp'
		  		? (int)($value_all_registros['total_historial_comercial_smtp'] ?? 0)
		  		: (int)($value_all_registros['total_historial_comercial_simulacion'] ?? 0);

		  	$presupuestoActual = [
		  		'estado' => $estadoPresupuesto,
		  		'estado_comercial_simulacion' => $estadoComercialSimulacion,
		  		'estado_comercial_smtp' => $estadoComercialSmtp,
		  	];

		  	$estadoVisiblePresupuesto = resolverEstadoVisiblePresupuestoDesdePresupuesto(
		  		$presupuestoActual,
		  		$modoCircuitoActivo
		  	);
		  	$estadoComercialActivo = obtenerEstadoComercialActivoDesdePresupuesto(
		  		$presupuestoActual,
		  		$modoCircuitoActivo
		  	);

		  	// Si no existe presupuesto asociado, la leyenda es Pendiente (en rojo)
		  	if ($estadoPresupuesto === null || $estadoPresupuesto === '') {
		  		$presupuestoHtml = '<span class="text-danger"><strong>Pendiente</strong></span>';
		  	} else {

		  		// Mapeo de colores por estado
		  		$claseEstado = 'text-secondary'; // default conservador

		  		$estadoPresupuestoKey = strtoupper(trim((string)$estadoVisiblePresupuesto));
		  		$estadoPresupuestoLabel = trim((string)$estadoVisiblePresupuesto);
		  		$mostrarIconoDocumentosEmitidos = $totalDocumentosEmitidos > 0;
		  		$mostrarIconoHistorialPresupuesto = $estadoComercialActivo !== '' || $totalHistorialComercialActivo > 0;

		  		switch ($estadoPresupuestoKey) {
		  			case 'BORRADOR':
		  				$claseEstado = 'text-secondary';
		  				$estadoPresupuestoLabel = 'Borrador';
		  				break;
		  			case 'IMPRESO':
		  			case 'EMITIDO':
		  				$claseEstado = 'text-info';
		  				$estadoPresupuestoLabel = 'Emitido';
		  				break;
		  			case 'ENVIADO':
		  				$claseEstado = 'text-primary';
		  				$estadoPresupuestoLabel = 'Enviado';
		  				break;
		  			case 'RECIBIDO':
		  				$claseEstado = 'text-success';
		  				$estadoPresupuestoLabel = 'Recibido';
		  				break;
		  			case 'RESOLICITADO':
		  				$claseEstado = 'text-warning';
		  				$estadoPresupuestoLabel = 'Resolicitado';
		  				break;
		  			case 'APROBADO':
		  				$claseEstado = 'text-success';
		  				$estadoPresupuestoLabel = 'Aprobado';
		  				break;
		  			case 'RECHAZADO':
		  				$claseEstado = 'text-danger';
		  				$estadoPresupuestoLabel = 'Rechazado';
		  				break;
		  			case 'CANCELADO':
		  				$claseEstado = 'text-dark';
		  				$estadoPresupuestoLabel = 'Cancelado';
		  				break;
		  			case 'PENDIENTE':
		  				// Si el registro existe pero está en Pendiente, lo mantenemos en rojo también
		  				$claseEstado = 'text-danger';
		  				$estadoPresupuestoLabel = 'Pendiente';
		  				break;
		  		}

                    $editarBloqueadoEstado = $estadoComercialActivo !== ''
                        ? $estadoComercialActivo
                        : $estadoPresupuestoKey;
                    $editarBloqueadoComercial = estadoBloqueaEdicionComercialPresupuesto($editarBloqueadoEstado);
                    if ($editarBloqueadoComercial) {
                        $editarBloqueadoTooltip = mensajeBloqueoEdicionComercialPresupuesto($editarBloqueadoEstado);
                    }

		  		$presupuestoHtml = '<span class="'.$claseEstado.'"><strong>'.$estadoPresupuestoLabel.'</strong></span>';
		  	}
		  }

		  // Presupuesto + Orden de compra (por ahora OC sigue vacía)
          $filas .= '<td class="text-center align-middle">'.$presupuestoHtml.'</td><td class="text-center align-middle"></td>'; //para relleno en el caso de datos futuros

			// acciones				
			$filas .= '<td class="text-left pl-3" style="white-space: nowrap;">';

			$filas .= '<i class="v-icon-accion p-1 fas fa-solid fa-eye"
						data-accion="visual"
						data-bloqueo-comercial="'.($editarBloqueadoComercial ? '1' : '0').'"
						data-estado-bloqueo="'.htmlspecialchars(etiquetaEstadoComercialPresupuestoLock($editarBloqueadoEstado), ENT_QUOTES, 'UTF-8').'"
						data-toggle="tooltip"
						title="'.htmlspecialchars($editarBloqueadoComercial ? $editarBloqueadoTooltip : 'Visualizar', ENT_QUOTES, 'UTF-8').'"></i>';

			if (!$editarBloqueadoComercial && !$editarBloqueadoVisita) {
				$filas .= '<i class="v-icon-accion p-1 fas fa-edit"
							data-accion="editar"
							data-bloqueo-comercial="0"
							data-estado-bloqueo=""
							data-toggle="tooltip"
							title="Editar"></i>';
			}

			$filas .= '<i class="v-icon-accion p-1 fas fa-paperclip" 
						style="pointer-events:none;opacity:.4;" 
						data-accion="adjunto" 
						data-toggle="tooltip" 
						title="Ver adjuntos"></i>';

			if ($mostrarIconoDocumentosEmitidos) {
				$filas .= '<i class="v-icon-accion p-1 fas fa-file-pdf" 
							data-accion="documentos_emitidos" 
							data-toggle="tooltip" 
							title="Documentos emitidos"></i>';
			}

			if ($mostrarIconoHistorialPresupuesto) {
				$filas .= '<i class="v-icon-accion p-1 fas fa-history" 
							data-accion="historial" 
							data-toggle="tooltip" 
							title="Historial de presupuesto"></i>';
			}

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
