<?php  
session_start();
define('BASE_URL', $_SESSION["base_url"]);
include_once '../04-modelo/conectDB.php'; //conecta a la base de datos
include_once '../04-modelo/paisesModel.php'; //conecta a la tabla de paises
include_once '../04-modelo/provinciasModel.php'; //conecta a la tabla de provincias
include_once '../04-modelo/partidosModel.php'; //conecta a la tabla de partido
include_once '../04-modelo/localidadesModel.php'; //conecta a la tabla de localidades
include_once '../04-modelo/presupuestosModel.php'; //conecta a la tabla de clientes
include_once '../04-modelo/callesModel.php'; //conecta a la tabla de usuarios
include_once '../04-modelo/clientesModel.php'; //conecta a la tabla de clientes
include_once '../04-modelo/visitaModel.php';
include_once '../04-modelo/presupuestoGeneradoModel.php';
include_once '../04-modelo/presupuestoIntervencionesModel.php';
include_once '../04-modelo/presupuestoComercialLockModel.php';
include_once '../04-modelo/previsitaWorkflowModel.php';
include_once '../04-modelo/previsitaDocumentosModel.php';
include_once '../06-funciones_php/ordenar_array.php'; //ordena array por el indice indicado
include_once '../06-funciones_php/optionsByIndex.php'; //ordena array por el indice indicado
include_once '../06-funciones_php/funciones.php'; //funciones últiles

// =========================
// Permisos por perfil (UI)
// =========================
$usuarioSesion = $_SESSION["usuario"] ?? null;
$perfilSesion  = is_array($usuarioSesion) ? ($usuarioSesion['perfil'] ?? '') : '';

// Por defecto, NO mostramos vista detallada (más seguro)
$mostrarVistaDetallada = false;

// Ajustar perfiles permitidos a ver impuestos/utilidades:
$perfilesDetallado = ['Super Administrador', 'Administrador']; // <-- editá según tu sistema

if (in_array($perfilSesion, $perfilesDetallado, true)) {
  $mostrarVistaDetallada = true;
}


$id=""; $visualiza=""; $pdf=""; $visualiza_prevista ="";
$visualizacionSolicitada = false;
$bloqueoEdicionComercial = [
  'bloqueado' => false,
  'estado' => '',
  'estado_label' => '',
  'mensaje' => '',
];
$documentosPrevisita = [];
$documentosPrevisitaJson = '[]';
$permiteEditarDocumentosPrevisita = true;
$workflowPrevisita = snapshotWorkflowPrevisitaEstado(null);
$presupuestoIntervinoResumen = construirResumenIntervencionesPresupuesto([]);
$ultimoIntervinoPresupuesto = $presupuestoIntervinoResumen['ultimo_texto'] ?? 'Sin intervenciones';
$popoverIntervinientesPresupuesto = $presupuestoIntervinoResumen['popover_html'] ?? '';

if(isset($_GET['id']) && isset($_GET['acci'])){
  $id = $_GET['id'];
  if($_GET['acci'] == "v"){$visualiza="on"; $visualizacionSolicitada = true;} // pone los campos en modo visualización
  if($_GET['acci'] == "pdf"){$pdf="on";}

  $datos = modGetPresupuestoById($id, 'php');
  $datos = $datos[0] ?? [];

  //dd($datos);
  //dd($datos['log_usuario_id']);
  $intervino_previsita_agente = !empty($datos['log_usuario_id']) ? db_select_with_filters_V2(
    'usuarios',                // tabla
    ['id_usuario'],            // columnas a filtrar
    ['='],                     // comparaciones
    [$datos['log_usuario_id'] ?? null],// valores
    [],                        // ordenamiento (vacío)
    'php'                      // devuelve array en PHP
  ) : [];

  $intervino_previsita_apenom = ($intervino_previsita_agente[0]['apellidos'] ?? '')." ".($intervino_previsita_agente[0]['nombres'] ?? '')." | ".strToDateFormat($datos['log_edicion'] ?? '', 'd/m/Y H:i:s');
  $workflowPrevisita = snapshotWorkflowPrevisitaEstado($datos['estado_visita'] ?? null);

  $tareas_visitadas = [];

  if (!empty($workflowPrevisita['habilita_visita']) && isset($datos['id_previsita'])) {
      $id_visita = $datos['id_previsita'];

        $tareas_visitadas = modGetTareasByVisitaId($id_visita, 'php');
  }

  
//echo utf8_encode( $usuario_datos['0']['provincia'] ); die();

    $previsita_card = 'card-success'; $previsita_buttons = 'd-flex'; 
    $visita_card = 'card-danger'; $items_options = "";
    $presupuesto_card = 'card-danger'; 
    $orden_compra_card = 'card-danger';
    $presupuesto_display = 'd-none';

    if (!empty($workflowPrevisita['habilita_visita'])) {$previsita_show = "";} else {$previsita_show = "show";}
    
    if (!empty($workflowPrevisita['habilita_visita'])) {
        $itemNumber = 0;

        //$itemNota = SelectAllDB('visita_notas', 'id_visita', '=', arrayPrintValue('', $datos, 'id_previsita', ''), $callType = 'php');
        $visita_show = "show"; //muestra el accordion de visita abierto
        $previsita_buttons = 'd-none'; // quita los botones de previsita para evitar guardado o modificación de datos
        $items_db = SelectAllDB('materiales', 'estado_material', '=', "'Activo'", $callType = 'php');
        $items_options = arrayToOptions($items_db, 'Seleccione un ítem', 'id_material', 'id_material', '|', 'producto', 'unidad_venta', 'rendimiento', 'unidad_rendimiento', null, null);

        $materiales_cargados = SelectAllDB('materiales_visita', 'id_visita', '=', arrayPrintValue('', $datos, 'id_previsita', ''), $callType = 'php'); 
      
        $html = "";
        if($materiales_cargados !== false){
            foreach ($materiales_cargados as $mc_key => $mc_value) {

                foreach ($items_db as $idb_key => $idb_value) {

                  if($mc_value['id_material'] == $idb_value['id_material'] && $mc_value['estado'] !== 'eliminado'){
                      $itemNumber  = $itemNumber + 1;
                        $html .= '<tr class="v-materiales-visita" id="item_'.$itemNumber.'" data-id_mate_visi="'.$mc_value['id_materiales_visita'].'" data-material_id="'.$mc_value['id_material'].'" data-material_cantidad="'.$mc_value['material_cantidad'].'">';
                        $html .= '<td>'.$itemNumber.'.</td>';
                        $html .= '<td>'.$mc_value['id_material'].' | '.$idb_value['producto'].' | '.$idb_value['unidad_venta'].' | '.$idb_value['rendimiento'].' </td>';
                        $html .= '<td class="text-center">'.$mc_value['material_cantidad'].'</td>';
                        $html .= '<td></td>';
                        $html .= '<td class="text-center"><i class="fa-solid fa-circle-xmark text-danger v-icon-delete-item v-icon-pointer" data-item_id="item_'.$itemNumber.'"></i></td>';
                        $html .= '</tr>';
                  } 

                }

            }  
        }

        $visualiza_prevista = "on";

    } elseif (!empty($workflowPrevisita['bloqueado'])) {
        $previsita_buttons = 'd-none';
        $visita_show = "";
        $visualiza_prevista = "on";

        if (!empty($workflowPrevisita['bloquea_avance'])) {
          $visualiza = "on";
        }
    } else {
        $visita_show = ""; //muestra el accordion de visita cerrado
    } 

}else{$datos = array();

  $previsita_card = 'card-danger'; $previsita_show = "show"; $previsita_buttons = 'd-flex';
  $visita_card = 'card-danger'; $visita_show = ""; 
  $presupuesto_card = 'card-danger'; $presupuesto_show = "";
  $orden_compra_card = 'card-danger'; $orden_compra_show = "";

}

$clientesSugeridosDisponibles = modGetAllClientes('todos');
$clientesSugeridosMap = [];

if (!is_array($clientesSugeridosDisponibles)) {
  $clientesSugeridosDisponibles = [];
}

foreach ($clientesSugeridosDisponibles as $clienteSugerido) {
  $razonSocialSugerida = trim((string)($clienteSugerido['razon_social'] ?? ''));
  $cuitSugerido = trim((string)($clienteSugerido['cuit'] ?? ''));

  if ($razonSocialSugerida === '') {
    continue;
  }

  $sugerenciaVisible = $razonSocialSugerida . ($cuitSugerido !== '' ? ' | ' . $cuitSugerido : '');
  $claveSugerencia = mb_strtoupper(preg_replace('/\s+/', ' ', trim($sugerenciaVisible)), 'UTF-8');

  $clientesSugeridosMap[$claveSugerencia] = [
    'id_cliente' => (int)($clienteSugerido['id_cliente'] ?? 0),
    'label' => $sugerenciaVisible,
    'razon_social' => $razonSocialSugerida,
    'cuit' => $cuitSugerido,
    'dirfis_provincia' => $clienteSugerido['dirfis_provincia'] ?? '',
    'dirfis_partido' => $clienteSugerido['dirfis_partido'] ?? '',
    'dirfis_localidad' => $clienteSugerido['dirfis_localidad'] ?? '',
    'dirfis_calle' => $clienteSugerido['dirfis_calle'] ?? '',
    'dirfis_altura' => $clienteSugerido['dirfis_altura'] ?? '',
    'dirfis_cp' => $clienteSugerido['dirfis_cp'] ?? '',
  ];
}

$provincias = getAllProvincias();
$provinciasSelect = ""; //para el select de provincias
foreach ($provincias as $key => $value) {
   if(!isset($cliente_datos)){ 
      $provinciasSelect .= '<option value="'.$value['id_provincia'].'">'.$value['provincia'].'</option>';
   }else{
    if($cliente_datos['0']['dirfis_provincia'] != $value['id_provincia']){
      $provinciasSelect .= '<option value="'.$value['id_provincia'].'">'.$value['provincia'].'</option>';
    }
   }
}

// solo si es edición

if(isset($cliente_datos['0']['id_cliente']) && $visualiza == "" && !is_null($cliente_datos['0']['dirfis_provincia']) && $cliente_datos['0']['dirfis_provincia'] !== ''){ 
  //var_dump($usuario_datos['0']['provincia']); die();
    $partidos = getPartidosByProvincia($cliente_datos['0']['dirfis_provincia'], 'php');
    $partidosSelect = ""; //para el select de partidos
    foreach ($partidos as $key => $value) {
      if($value['id_partido'] != $cliente_datos['0']['dirfis_partido']){
        $partidosSelect .= '<option value="'.$value['id_partido'].'">'.$value['partido'].'</option>';
      }
    }
}

if(isset($cliente_datos['0']['id_cliente']) && $visualiza == "" && !is_null($cliente_datos['0']['dirfis_partido']) && $cliente_datos['0']['dirfis_partido'] !== ''){
  $localidades = getLocalidadesByPartido($cliente_datos['0']['dirfis_partido'], 'php');
  $localidadesSelect = ""; //para el select de localidades
  foreach ($localidades as $key => $value) {
    if($value['id_localidad'] != $cliente_datos['0']['dirfis_localidad']){
      $localidadesSelect .= '<option value="'.$value['id_localidad'].'">'.$value['localidad'].'</option>';
    }
  }
}

if(isset($cliente_datos['0']['id_cliente']) && $visualiza == "" && !is_null($cliente_datos['0']['dirfis_partido']) && $cliente_datos['0']['dirfis_partido'] !== ''){
  $calles = getCallesByPartido($cliente_datos['0']['dirfis_partido'], 'php');
  $callesSelect = ""; //para el select de calles
  foreach ($calles as $key => $value) {
    if($value['id_calle'] != $cliente_datos['0']['dirfis_calle']){
      $callesSelect .= '<option value="'.$value['id_calle'].'">'.$value['calle'].'</option>';
    }
  }
}

// START PHP - Visita

      // Traemos materiales activos
      $materiales = SelectAllDB('materiales', 'estado_material', '=', "'Activo'", 'php');

      // Preparamos las opciones
      $opcionesMateriales = arrayToOptionsWithData(
        $materiales,
        'id_material',
        'descripcion_corta',
        'Seleccione un material',
        ' | ',
        ['unidad_venta', 'contenido', 'unidad_medida'],
        [
          'precio_unitario' => 'precio_unitario',
          'unidad_medida'   => 'unidad_medida',
          'unidad_venta'    => 'unidad_venta',
          'contenido'       => 'contenido',
          'log_alta'        => 'log_alta',
          'log_edicion'     => 'log_edicion'
        ]
      );

      $manoDeObra = SelectAllDB('tipo_jornales', 'jornal_estado', '=', "'activo'", 'php');

      $opcionesManoDeObra = arrayToOptionsWithData(
        $manoDeObra,
        'jornal_id',
        'jornal_codigo',
        'Seleccione mano de obra',
        ' | ',
        ['jornal_descripcion'],
        [
          'jornal_id' => 'jornal_id',
          'jornal_valor' => 'jornal_valor',
          'updated_at'   => 'updated_at',
        ]
      );

      if(isset($datos['id_previsita'])) {
          // intervinientes visita
          $intervinientes_visita_ids = db_select_with_filters_V2(
            'seguimiento_guardados',              // tabla
            ['id_previsita', 'modulo'],           // columnas a filtrar
            ['=','='],                            // comparaciones
            [$datos['id_previsita'], '2'],        // valores
            [['created_at', 'DESC']],                                   // ordenamiento (vacío)
            'php'                                 // devuelve array en PHP
          );

          $intervinientes_visita_nombres = intervinientes_names($intervinientes_visita_ids);

          // --- Nuevo: construyo aquí el HTML del popover sin incluir al primero ---
          $otros = array_slice($intervinientes_visita_nombres, 1);

          $popoverIntervinientes = '<table class="table table-sm mb-0">'
                                . '<thead><tr><th>Agente</th><th>Fecha</th></tr></thead>'
                                . '<tbody>';

          if (count($otros) > 0) {
              foreach ($otros as $item) {
                  list($agente, $fecha) = explode(' | ', $item);
                  $popoverIntervinientes .= "<tr><td>{$agente}</td><td>{$fecha}</td></tr>";
              }
          } else {
              // opcional: mensaje si no queda ninguno
              $popoverIntervinientes .= '<tr><td colspan="2" class="text-center text-muted">'
                                    . 'Sin otros intervinientes'
                                    . '</td></tr>';
          }

          $popoverIntervinientes .= '</tbody></table>';


          // El primer elemento (más reciente) para mostrar “en reposo”
          $ultimo = $intervinientes_visita_nombres[0] ?? '';
          // intervinientes visita

          if (empty($tareas_visitadas)){$visita_card = 'card-danger';}else{$visita_card = 'card-success';}
           

          // END PHP - Visita


        // START PHP PRESUPUESTO GENERADO 
              $presupuesto_generado = obtenerPresupuestoPorPrevisita($datos["id_previsita"], true);            
              $presupuestoGenerado = $presupuesto_generado['presupuesto']; // Cambia a true para probar el otro caso
              $bloqueoEdicionComercial = obtenerBloqueoEdicionComercialPresupuestoPorPrevisita((int)$datos["id_previsita"]);
              if (!empty($bloqueoEdicionComercial['bloqueado']) || !empty($workflowPrevisita['bloquea_avance'])) {
                $visualiza = "on";
              }
              if ($presupuesto_generado['presupuesto']) {
                //muestra el accordión de presupuesto generado abierto
                $presupuestoIntervinoResumen = obtenerResumenIntervencionesPresupuesto(
                  (int)$datos['id_previsita'],
                  isset($presupuestoGenerado['id_presupuesto']) ? (int)$presupuestoGenerado['id_presupuesto'] : null
                );
                $ultimoIntervinoPresupuesto = $presupuestoIntervinoResumen['ultimo_texto'] ?? 'Sin intervenciones';
                $popoverIntervinientesPresupuesto = $presupuestoIntervinoResumen['popover_html'] ?? '';
                $presupuesto_card = 'card-success';
                $presupuesto_show = 'show';
                $visita_show = '';
                $presupuesto_display = '';
              } else {
                $visita_show = 'show';
                $presupuesto_show = '';
              }
        // END PRESUPUESTO GENERADO 

      }

if (isset($datos['id_previsita']) && (int)$datos['id_previsita'] > 0) {
  $documentosPrevisita = listarDocumentosPrevisita((int)$datos['id_previsita'], $datos);
}

$permiteEditarPrevisitaCompleta = ($visualizacionSolicitada === false && $visualiza === '' && $visualiza_prevista === '' && empty($bloqueoEdicionComercial['bloqueado']) && empty($workflowPrevisita['bloqueado']));
$permiteEditarDocumentosPrevisita = ($visualizacionSolicitada === false && $pdf === '');
$mostrarGuardarSoloDocumentosPrevisita = ($permiteEditarDocumentosPrevisita && !$permiteEditarPrevisitaCompleta && !empty($datos['id_previsita']));
$previsita_buttons = $permiteEditarPrevisitaCompleta ? 'd-flex' : 'd-none';
$documentosPrevisitaJson = json_encode($documentosPrevisita, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($documentosPrevisitaJson === false) {
  $documentosPrevisitaJson = '[]';
}

function intervinientes_names($b_array){
  $intervinieron_agentes = [];
  foreach ($b_array as $key => $value) {
               
        $intervino_agente = db_select_with_filters_V2(
          'usuarios',                // tabla
          ['id_usuario'],            // columnas a filtrar
          ['='],                     // comparaciones
          [$value['id_usuario']],    // valores
          [],                        // ordenamiento (vacío)
          'php'                      // devuelve array en PHP
        );     

        array_push($intervinieron_agentes, $intervino_agente[0]['apellidos']." ".$intervino_agente[0]['nombres']." | ".strToDateFormat($value['created_at'], 'd/m/Y H:i:s'));
    }
     return $intervinieron_agentes;
}

function resumir_titulo_tarea_presupuesto(string $descripcion, int $maxPalabras = 12): string
{
    if (function_exists('textoPlanoDetalleTareaPresupuesto')) {
        $descripcion = textoPlanoDetalleTareaPresupuesto($descripcion);
    }

    $texto = trim(preg_replace('/\r\n?/', "\n", $descripcion));
    if ($texto === '') {
        return '';
    }

    $lineas = preg_split('/\n+/', $texto) ?: [];
    $lineas = array_values(array_filter(array_map('trim', $lineas), static function ($linea) {
        return $linea !== '';
    }));

    if (count($lineas) > 1) {
        return $lineas[0];
    }

    $textoPlano = $lineas[0] ?? $texto;

    $posPunto = strpos($textoPlano, '.');
    if ($posPunto !== false) {
        return trim(substr($textoPlano, 0, $posPunto + 1));
    }

    $posComa = strpos($textoPlano, ',');
    if ($posComa !== false) {
        return trim(substr($textoPlano, 0, $posComa + 1));
    }

    $palabras = preg_split('/\s+/u', $textoPlano, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($palabras) <= $maxPalabras) {
        return $textoPlano;
    }

    return implode(' ', array_slice($palabras, 0, $maxPalabras)) . '...';
}

function renderizar_editor_detalle_tarea_presupuesto(string $descripcion, bool $soloLectura = false): string
{
    $htmlSeguro = function_exists('sanitizarHtmlDetalleTareaPresupuesto')
        ? sanitizarHtmlDetalleTareaPresupuesto($descripcion)
        : htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8');

    $valorTextarea = htmlspecialchars($htmlSeguro, ENT_QUOTES, 'UTF-8');
    $disabledAttr = $soloLectura ? 'disabled' : '';
    $contenteditableAttr = $soloLectura ? 'false' : 'true';
    $toolbarClass = $soloLectura ? ' d-none' : '';

    return '
      <div class="tarea-detalle-editor">
        <div class="btn-toolbar btn-group-sm tarea-detalle-editor-toolbar mb-2'. $toolbarClass .'" role="toolbar" aria-label="Formato del detalle de la tarea">
          <div class="btn-group mr-2" role="group" aria-label="Formato basico">
            <button type="button" class="btn btn-light rich-editor-action" data-command="bold" title="Negrita" '. $disabledAttr .'><i class="fas fa-bold"></i></button>
            <button type="button" class="btn btn-light rich-editor-action" data-command="italic" title="Cursiva" '. $disabledAttr .'><i class="fas fa-italic"></i></button>
            <button type="button" class="btn btn-light rich-editor-action" data-command="underline" title="Subrayado" '. $disabledAttr .'><i class="fas fa-underline"></i></button>
          </div>
          <div class="btn-group mr-2" role="group" aria-label="Listas">
            <button type="button" class="btn btn-light rich-editor-action" data-command="insertUnorderedList" title="Lista" '. $disabledAttr .'><i class="fas fa-list-ul"></i></button>
          </div>
          <div class="btn-group" role="group" aria-label="Limpiar formato">
            <button type="button" class="btn btn-light rich-editor-action" data-command="removeFormat" title="Limpiar formato" '. $disabledAttr .'><i class="fas fa-eraser"></i></button>
          </div>
        </div>
        <div class="form-control form-control-sm tarea-descripcion-editor'. ($soloLectura ? ' input-sololectura' : '') .'" contenteditable="'. $contenteditableAttr .'" data-placeholder="Describa la tarea..." aria-label="Editor de detalle de la tarea">' . $htmlSeguro . '</div>
        <textarea class="form-control form-control-sm tarea-descripcion d-none" rows="5">' . $valorTextarea . '</textarea>
      </div>
    ';
}

function renderizar_presupuesto_html(array $presupuesto_generado, bool $mostrarVistaDetallada = true, bool $soloLectura = false): string
{
    $hoy = new DateTimeImmutable('now');

    $e = function ($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    };

    $parseFecha = function (?string $s): ?DateTimeImmutable {
        if (!$s) return null;
        // Acepta "YYYY-mm-dd HH:ii:ss" o lo que parsee strtotime
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $s);
        return $dt ?: (strtotime($s) ? (new DateTimeImmutable('@'.strtotime($s)))->setTimezone(new DateTimeZone(date_default_timezone_get())) : null);
    };

    $vigencia = function (?string $fechaStr) use ($parseFecha, $hoy): array {
        $dt = $parseFecha($fechaStr);
        if (!$dt) {
            // Sin fecha → lo consideramos vigente (verde y readonly), igual que tu JS para MO sin updated_at
            return ['bg-success', 'readonly'];
        }
        $diffDias = (int)$hoy->diff($dt)->format('%a');
        if ($dt < $hoy && $diffDias > 30) {
            return ['bg-danger', '']; // vencido, editable
        }
        return ['bg-success', 'readonly']; // vigente
    };

    $html = [];
    $idPresupuesto = $presupuesto_generado['presupuesto']['id_presupuesto'] ?? null;
    $disabledAttr = $soloLectura ? 'disabled' : '';
    $readonlyUtilClass = $soloLectura ? 'readonly input-sololectura' : '';

    // Contenedor raíz (data-id_presupuesto para que tu JS lo tome)
    $html[] = '<div id="contenedorPresupuestoGenerado" data-id_presupuesto="'. $e($idPresupuesto) .'">';

    // TAREAS (respetamos el orden por "nro" si viene)
    $tareas = $presupuesto_generado['tareas'] ?? [];
    usort($tareas, function($a,$b){ return ($a['nro'] ?? 0) <=> ($b['nro'] ?? 0); });

    foreach ($tareas as $t) {
        $nro          = (int)($t['nro'] ?? 0);
        $descripcion  = $t['descripcion'] ?? '';
        $descripcionHtml = function_exists('sanitizarHtmlDetalleTareaPresupuesto')
            ? sanitizarHtmlDetalleTareaPresupuesto((string)$descripcion)
            : (string)$descripcion;
        $tituloTarea  = resumir_titulo_tarea_presupuesto((string)$descripcionHtml);
        $incluido     = (int)($t['incluir_en_total'] ?? 1) === 1 ? 'checked' : '';
        $utilMatPct   = $t['utilidad_materiales_pct'] ?? null;
        $utilMoPct    = $t['utilidad_mano_obra_pct'] ?? null;
        $otrosMat     = $t['otros_materiales_monto'] ?? '0.00';
        $otrosMo      = $t['otros_mano_obra_monto'] ?? '0.00';

        // --- Materiales
        $rowsMat = [];
        foreach (($t['materiales'] ?? []) as $m) {
            $nombre   = $m['nombre_material'] ?? '';
            $cant     = $m['cantidad'] ?? '0';
            $pu       = $m['precio_unitario_usado'] ?? '0';
            $pctExtra = $m['porcentaje_extra'] ?? '0';
            $subfila  = $m['subtotal_fila'] ?? '0.00';
            $idMat    = $m['id_material'] ?? null;
            $ordenMat = $m['orden'] ?? null;

            // vigencia: log_edicion o log_alta
            $fechaRef = $m['log_edicion'] ?? $m['log_alta'] ?? null;
            [$clase, $ro] = $vigencia($fechaRef);

            $rowsMat[] = '
            <tr data-material-id="'. $e($idMat) .'" data-orden="'. $e($ordenMat) .'">
              <td>'. $e($nombre) .'</td>
              <td>
                <input type="number" class="form-control form-control-sm cantidad-material"
                       value="'. $e($cant) .'" min="0" step="any" '. $disabledAttr .'>
              </td>
              <td>
                <input type="number" class="form-control form-control-sm precio-unitario '. $e($clase) .'"
                       value="'. $e($pu) .'" min="0" step="any" '. $e($ro) .'>
              </td>
              <td>
                <input type="number" class="form-control form-control-sm porcentaje-extra"
                       value="'. $e($pctExtra) .'" min="0" step="any" '. $disabledAttr .'>
              </td>
              <td class="text-right subtotal-material">$'. $e(number_format((float)$subfila, 2, '.', '')) .'</td>
            </tr>';

        }

        // --- Mano de obra (DB -> igual a visita: Operarios + Días + Jornales)
        $rowsMo = [];
        foreach (($t['mano_obra'] ?? []) as $mo) {
            $nombre   = $mo['nombre_jornal'] ?? '';
            $operarios= $mo['operarios'] ?? $mo['cantidad'] ?? '0';          // en DB hoy viene como "cantidad"
            $valorJ   = $mo['valor_jornal_usado'] ?? '0';
            $pctExtra = $mo['porcentaje_extra'] ?? '0';
            $subfila  = $mo['subtotal_fila'] ?? '0.00';
            $jId      = $mo['id_jornal'] ?? $mo['jornal_id'] ?? null;
            $ordenMo  = $mo['orden'] ?? null;

            // vigencia: updated_at_origen preferente, si no updated_at
            $fechaRef = $mo['updated_at_origen'] ?? $mo['updated_at'] ?? null;
            [$clase, $ro] = $vigencia($fechaRef);

            // Normalizamos numéricos para cálculo
            $opNum   = (float)$operarios;
            $jValNum = (float)$valorJ;
            $pctNum  = (float)$pctExtra;
            $subNum  = (float)$subfila;

            // Días y jornales: si existen en DB los usamos; si no, deducimos desde subtotal
            $diasDb     = $mo['dias'] ?? null;
            $jornalesDb = $mo['jornales'] ?? null;

            if ($diasDb !== null) {
                $diasNum = (float)$diasDb;
            } else {
                // Deducción: base = subtotal / (1 + pctExtra/100)
                // jornales = base / valorJ
                // dias = jornales / operarios
                if ($opNum > 0 && $jValNum > 0) {
                    $factor = 1 + ($pctNum / 100);
                    $base   = ($factor != 0) ? ($subNum / $factor) : 0;
                    $jornalesCalc = ($jValNum != 0) ? ($base / $jValNum) : 0;
                    $diasNum = ($opNum != 0) ? ($jornalesCalc / $opNum) : 0;
                } else {
                    $diasNum = 0;
                }
            }

            if ($jornalesDb !== null) {
                $jornalesNum = (float)$jornalesDb;
            } else {
                $jornalesNum = $opNum * $diasNum;
            }

            $rowsMo[] = '
            <tr data-jornal_id="'. $e($jId) .'" data-orden="'. $e($ordenMo) .'">
              <td>'. $e($nombre) .'</td>

              <!-- Operarios -->
              <td>
                <input type="number" class="form-control form-control-sm cantidad-mano-obra"
                      value="'. $e($opNum) .'" min="0" step="any" '. $disabledAttr .'>
              </td>

              <!-- Días -->
              <td>
                <input type="number" class="form-control form-control-sm dias-mano-obra"
                      value="'. $e($diasNum) .'" min="0" step="any" '. $disabledAttr .'>
              </td>

              <!-- Jornales (Operarios × Días) -->
              <td>
                <input type="number" class="form-control form-control-sm jornales-mano-obra"
                      value="'. $e($jornalesNum) .'" min="0" step="any" readonly>
              </td>

              <td>
                <input type="number" class="form-control form-control-sm valor-jornal '. $e($clase) .'"
                      value="'. $e($jValNum) .'" min="0" step="any" '. $e($ro) .'>
              </td>

              <td>
                <input type="number" class="form-control form-control-sm porcentaje-extra"
                      value="'. $e($pctNum) .'" min="0" step="any" '. $disabledAttr .'>
              </td>

              <td class="text-right subtotal-mano">$'. $e(number_format((float)$subNum, 2, '.', '')) .'</td>
            </tr>';
        }

        $claseUtil = $mostrarVistaDetallada ? '' : 'd-none';
        $claseImp  = $mostrarVistaDetallada ? '' : 'd-none';
        $roUtil    = $mostrarVistaDetallada ? $readonlyUtilClass : 'readonly input-sololectura';

        $html[] = '
        <div class="tarea-card">
          <div class="tarea-encabezado">
            <span><i class="fas fa-tasks"></i> <b>Tarea '. $e($nro) .': '. $e($tituloTarea) .'</b></span>
            <label class="incluir-presupuesto-label">
              <input type="checkbox" class="incluir-en-total" '. $incluido .' '. $disabledAttr .'>
              <span>Incluído en el presupuesto</span>
            </label>
          </div>

          <div class="container-fluid px-3 pt-3">
            <div class="row tarea-card-cuerpo">
              <!-- Izquierda -->
              <div class="col-md-4 tarea-columna-izquierda">
                <div class="mb-2 tarea-columna-panel tarea-columna-panel-detalle">
                  <label class="mb-0"><b>Detalle de la tarea</b></label>
                  '. renderizar_editor_detalle_tarea_presupuesto((string)$descripcionHtml, $soloLectura) .'
                </div>

                <div class="mb-2 tarea-columna-panel tarea-columna-panel-imagenes">
                  <label class="mb-0"><b>Imágenes</b></label>

                  <input type="file" class="presu-fotos d-none" id="presu_fotos_tarea_'. $e($nro) .'"
                         multiple accept="image/*" data-index="'. $e($nro) .'" '. $disabledAttr .'/>

                  <div class="presu-dropzone border rounded bg-light p-3 text-muted mb-2"
                       data-index="'. $e($nro) .'" style="min-height:100px;">
                    <div class="w-100 d-flex align-items-center justify-content-center text-center">
                      <em>Arrastre aquí las imágenes o haga click.</em>
                    </div>
                    <div class="row presu-preview-fotos m-0 mt-2" id="presu_preview_'. $e($nro) .'">';

        // Thumbs de fotos existentes (si hay)
        foreach (($t['fotos'] ?? []) as $f) {
            $src   = $f['ruta_archivo'] ?? '';
            $nom   = $f['nombre_archivo'] ?? basename($src);
            $html[] = '
              <div class="preview-img-container position-relative d-inline-block m-1" data-nombre-archivo="'. $e($nom) .'">
                <img src="../'. $e($src) .'" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;cursor:pointer;">
                '. ($soloLectura ? '' : '<i class="fa fa-times-circle text-white rounded-circle position-absolute presu-eliminar-imagen"
                   style="top:0;right:0;cursor:pointer;font-size:1rem;"></i>') .'
              </div>';
        }

        $html[] = '
                    </div>
                  </div>
                </div>
              </div>

              <!-- Derecha -->
              <div class="col-md-8 tarea-columna-derecha d-flex flex-column justify-content-start">
              <!-- Materiales -->
              <div class="tarea-materiales mb-0 mt-0 pt-0">
                <div class="bloque-titulo mt-0 pt-0 mb-0">Materiales</div>
                <table class="tabla-presupuesto tabla-presupuesto-sm">
                  <thead>
                    <tr>
                      <th>Material</th>
                      <th>Cantidad</th>
                      <th>Precio Unitario</th>
                      <th>% Extra</th>
                      <th>Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>'
                  . implode('', $rowsMat) .
                  '
                    <tr class="fila-otros-materiales">
                      <td><b>Otros</b></td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td class="text-right">
                        <input type="number" min="0" step="0.01"
                               class="form-control form-control-sm input-otros-materiales"
                               id="otros-mat-'. $e($nro) .'" value="'. $e($otrosMat) .'" '. $disabledAttr .'>
                      </td>
                    </tr>
                    <tr class="fila-subtotal">
                      <td colspan="3" class="text-right"><b>Subtotal Materiales</b></td>
                      <td>
                        <input type="number" class="form-control form-control-sm utilidad-global-materiales"
                               min="0" '. $roUtil .' value="'. $e($utilMatPct ?? '') .'" placeholder="%">
                      </td>
                      <td class="text-right"><b>$0.00</b></td>
                    </tr>
                  </tbody>
                </table>
              </div>

                <!-- Mano de Obra -->
                <div class="tarea-mano-obra">
                  <div class="bloque-titulo mt-0">Mano de Obra</div>
                  <table class="tabla-presupuesto tabla-presupuesto-sm">
                    <thead>
                      <tr>
                        <th>Tipo</th>
                        <th>Operarios</th>
                        <th>Días</th>
                        <th>Jornales</th>
                        <th>Valor Jornal</th>
                        <th>% Extra</th>
                        <th>Subtotal</th>
                      </tr>
                    </thead>                
                    <tbody>'
                    . implode('', $rowsMo) .
                    '
                    <tr class="fila-otros-mano">
                      <td><b>Otros</b></td>
                      <td></td> <!-- Operarios -->
                      <td></td> <!-- Días -->
                      <td></td> <!-- Jornales -->
                      <td></td> <!-- Valor Jornal -->
                      <td></td> <!-- % Extra -->
                      <td class="text-right">
                        <input type="number" min="0" step="0.01"
                              class="form-control form-control-sm input-otros-mano"
                              id="otros-mo-'. $e($nro) .'" value="'. $e($otrosMo) .'" '. $disabledAttr .'>
                      </td>
                    </tr>
                    <tr class="fila-subtotal">
                      <td colspan="5" class="text-right"><b>Subtotal Mano de Obra</b></td>
                      <td>
                        <input type="number" class="form-control form-control-sm utilidad-global-mano-obra"
                              min="0" '. $roUtil .' value="'. $e($utilMoPct ?? '') .'" placeholder="%">
                      </td>
                      <td class="text-right"><b>$0.00</b></td>
                    </tr>                  
                    </tbody>
                  </table>
                </div>

                <div class="tarea-total d-flex flex-column align-items-end px-3">
                  <div class="utilidades-extra w-100">
                    <button class="col-2 btn-total-tarea subt-util-materiales w-100 '. $claseUtil .'" id="subt-util-materiales-'. $e($nro) .'">Subtotal Util. Mat.: $0,00</button>
                  </div>
                  <div class="utilidades-extra w-100">
                    <button class="col-2 btn-total-tarea subt-util-manoobra w-100 '. $claseUtil .'" id="subt-util-manoobra-'. $e($nro) .'">Subtotal Util. MO.: $0,00</button>
                  </div>
                  <div class="utilidades-extra w-100">
                    <button class="col-2 btn-total-tarea subt-util-total w-100 '. $claseUtil .'" id="subt-util-total-'. $e($nro) .'">Sub Util. Mat.+MO.: $0,00</button>
                  </div>
                  <div class="d-flex justify-content-end w-100">
                    <button class="col-2 btn-total-tarea w-100 subt-util-final '. $claseUtil .'" id="utilfinal-'. $e($nro) .'">Util real final: $0,00</button>
                  </div>
                  <div class="d-flex justify-content-end w-100">
                    <button class="col-2 btn-total-tarea porcentaje-tarea w-100 porcentajetarea '. $claseUtil .'" id="porcentajetarea-'. $e($nro) .'">% : <strong>$0,00</strong></button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="tarea-barra-inferior d-flex align-items-end px-3 pb-2">
            <div class="tarea-inline-actions d-flex align-items-center">
              <button
                type="button"
                id="btnGuardarTarea_'. $e($nro) .'"
                class="btn btn-warning mr-2 btn-guardar-tarea btn-tarea"
                data-nro="'. $e($nro) .'"
                data-id-presu-tarea="'. (int)$t['id_presu_tarea'] .'" '. $disabledAttr .'>
                <i class="fas fa-save"></i> Guardar tarea
              </button>
              <button
                type="button"
                id="btnTraerTarea_'. $e($nro) .'"
                class="btn btn-warning btn-traer-tarea btn-tarea"
                data-nro="'. $e($nro) .'"
                data-id-presu-tarea="'. (int)$t['id_presu_tarea'] .'" '. $disabledAttr .'>
                <i class="fas fa-download"></i> Traer tarea
              </button>
            </div>

            <div class="fila-impuestos flex-grow-1" id="fila-impuestos-'. $e($nro) .'">
              <div class="tarea-impuestos-lista">
                <div class="col-auto pr-1 pl-0 '. $claseImp .'"><button type="button" class="btn bg-secondary w-100" id="iibb-'. $e($nro) .'">IIBB: $0,00</button></div>
                <div class="col-auto pr-1 pl-0 '. $claseImp .'"><button type="button" class="btn bg-secondary w-100" id="ganancias-'. $e($nro) .'">Ganancias 35%: $0,00</button></div>
                <div class="col-auto pr-1 pl-0 '. $claseImp .'"><button type="button" class="btn bg-secondary w-100" id="cheque-'. $e($nro) .'">Imp. cheque: $0,00</button></div>
                <div class="col-auto pr-1 pl-0 '. $claseImp .'"><button type="button" class="btn bg-secondary w-100" id="inversion-'. $e($nro) .'">Costo inv. 3%: $0,00</button></div>
                <div class="col-auto pr-1 pl-0 '. $claseImp .'"><button type="button" class="btn bg-secondary w-100" id="retiva-'. $e($nro) .'">Ret. IVA mat: <strong>$0,00</strong></button></div>
              </div>

              <div class="tarea-subtotal-col">
                <button type="button" class="btn-total-tarea w-100 util-muy mt-0" id="subt-tarea-'. $e($nro) .'">Subtotal Tarea '. $e($nro) .': $0,00</button>
              </div>
            </div>
          </div>
        </div>';
    }

    // Bloque TOTAL
    $html[] = '
      <div class="presupuesto-total-card">
        <div class="presupuesto-total-row">
          <div class="presupuesto-total-actions">
            <button id="btn-guardar-presupuesto" type="button" class="btn btn-success mr-2" '. $disabledAttr .'>
              <i class="fas fa-save"></i> Guardar
            </button>

            <button type="button" class="btn btn-primary mr-2 btn-emitir-presupuesto" '. $disabledAttr .'>
              <i class="fas fa-file-pdf"></i> Generar documento
            </button>
          </div>

          <div class="presupuesto-total-label">
            <span class="presupuesto-total-title">TOTAL PRESUPUESTO:</span>
            <span class="presupuesto-total-valor">$0.00</span>
          </div>
        </div>
      </div>';

    $html[] = '</div>'; // cierre contenedor

    return implode("\n", $html);
}

?>
<script>
  const presupuestoGenerado = <?php echo json_encode((bool)($presupuestoGenerado ?? false)); ?>;
</script>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title class="v-alta d-none">ADMINTECH | Alta de solicitud de presupuesto</title>
  <title class="v-visual d-none">ADMINTECH | Visualización de solicitud de presupuesto</title>
  <title class="v-edit d-none">ADMINTECH | Edición de seguimiento de obra</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <!-- Select2 -->
  <link rel="stylesheet" href="../05-plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../05-plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- SweetAlert2 -->
  <link rel="stylesheet" href="../05-plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
  <!-- Toastr -->
  <link rel="stylesheet" href="../05-plugins/toastr/toastr.min.css">
  <!-- iCheck for checkboxes and radio inputs -->
  <link rel="stylesheet" href="../05-plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <!-- Custom -->
  <link rel="stylesheet" href="../dist/css/custom.css">
  <style>
    #toast-container > .toast-info {
      background-color: #0f6d85 !important;
      color: #ffffff !important;
      opacity: 0.98 !important;
      box-shadow: 0 10px 28px rgba(15, 109, 133, 0.35) !important;
      border-left: 4px solid #083b48;
    }

    #toast-container > .toast-info .toast-message,
    #toast-container > .toast-info .toast-title,
    #toast-container > .toast-info .toast-close-button {
      color: #ffffff !important;
      text-shadow: none !important;
      opacity: 1 !important;
    }

    #toast-container > .toast-info .toast-progress {
      background-color: rgba(255, 255, 255, 0.7) !important;
      opacity: 1 !important;
    }

    .cliente-sugerencias-group {
      position: relative;
    }

    .cliente-sugerencias-menu {
      position: absolute;
      top: calc(100% - 1px);
      left: 0;
      right: 0;
      z-index: 1060;
      display: none;
      background: #ffffff;
      border: 1px solid #ced4da;
      border-top: none;
      border-radius: 0 0 0.25rem 0.25rem;
      box-shadow: 0 14px 28px rgba(31, 45, 61, 0.16);
      max-height: 260px;
      overflow-y: auto;
    }

    .cliente-sugerencia-item {
      display: block;
      width: 100%;
      padding: 0.65rem 0.8rem;
      border: none;
      border-top: 1px solid #eef2f6;
      background: #ffffff;
      color: #1f2d3d;
      text-align: left;
      cursor: pointer;
    }

    .cliente-sugerencia-item:first-child {
      border-top: none;
    }

    .cliente-sugerencia-item:hover,
    .cliente-sugerencia-item:focus {
      background: #edf7fa;
      outline: none;
    }

    .cliente-sugerencia-item.is-active {
      background: #dff1f7;
    }

    .cliente-sugerencia-titulo {
      display: block;
      font-weight: 600;
      color: #1f2d3d;
    }

    .cliente-sugerencia-meta {
      display: block;
      margin-top: 0.15rem;
      font-size: 0.82rem;
      color: #5c6b77;
    }
  </style>
  <script src='../05-plugins/pdfmake/pdfmake.min.js'></script>
  <script src='../05-plugins/pdfmake/vfs_fonts.js'></script>
  <script src="../05-plugins/html2canvas/html2canvas.min.js"></script>
  <script src="../05-plugins/jspdf/jspdf.umd.min.js"></script>
</head>
<body class="hold-transition sidebar-collapse layout-navbar-fixed">
<div class="wrapper">
  
  <!-- Navbar -->
  <?php include '../01-views/layout/navbar_layout.php';?> 
  <!-- /.navbar -->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper pb-5">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><strong class="v-alta d-none">Alta de solicitud de presupuesto</strong><strong class="v-visual d-none">Visualización de solicitud de presupuesto</strong><strong class="v-edit d-none">Edición de seguimiento de obra</strong></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <span class="v-alta-edit"><small class="text-danger"><strong>(*)Los iconos rojos indican campos obligatorios</strong></small></span>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->

<section class="content">
<div class="container-fluid">

<form id="currentForm"  class="form" enctype="multipart/form-data">
<?php if (!empty($bloqueoEdicionComercial['bloqueado'])): ?>
  <div class="alert alert-warning">
    <strong>Edicion bloqueada.</strong>
    <?php echo htmlspecialchars($bloqueoEdicionComercial['mensaje'] ?: mensajeBloqueoEdicionComercialPresupuesto($bloqueoEdicionComercial['estado'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
  </div>
<?php endif; ?>
<!-- start accordion -->
<div class="accordion" id="accordionExample">
  <div class="card <?php echo $previsita_card; ?> accordion 1">
    <div class="card-header" id="headingOne">
      <h2 class="mb-0 d-flex justify-content-between align-items-center">
        <button class="col-9 btn btn-link btn-block text-left text-white p-0 card-title " type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
        <span class="previsita-titulo-base">Pre-visita <?php echo arrayPrintValue('Nro: <strong>', $datos, 'id_previsita', '</strong>'); ?></span><span id="previsita_cliente_titulo"><?php if (!empty($datos['razon_social'])) { echo ' | ' . strtoupper($datos['razon_social']); } ?></span>
        </button>
        <span class="col-3 card-title text-right"><?php imprimirValido("Intervino", "intervino_previsita_apenom", true, 'strong', ': ') ?></span>
      </h2>
    </div>

    <!-- start collapse accordion 1 -->
    <div id="collapseOne" class="collapse <?php echo $previsita_show; ?>" aria-labelledby="headingOne" data-parent="#accordionExample">
          
          <!-- start card body accordion 1-->
          <div class="card-body">
                <input type="hidden" class="v-id" id="id_previsita" name="id_previsita" data-visualiza="<?php echo $visualiza; ?>" data-bloqueo-comercial="<?php echo !empty($bloqueoEdicionComercial['bloqueado']) ? '1' : '0'; ?>" data-estado-bloqueo="<?php echo htmlspecialchars((string)($bloqueoEdicionComercial['estado_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo arrayPrintValue(null, $datos, 'id_previsita', null); ?>">
                <!-- /.row start-->
                <div class="row pb-1">

                    <div class="col-1 form-group mb-0 mt-1">
                        <small class="v-visual-edit d-none"><label class="mb-0">Fecha de alta</label></small>
                        <div class="input-group mb-0">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
                          </div>
                            <input type="text" class="form-control v-disabled" inputmode="decimal" placeholder="" id="" name="" 
                            value="<?php echo strToDateFormat(arrayPrintValue(null, $datos, 'log_alta', null), 'd-m-Y'); ?>">
                        </div>
                    </div>

                  <div class="col-2 form-group mb-0 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Fecha de visita</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-calendar-check v-requerido-icon"></i></span>
                        </div>
                          <input type="date" class="form-control v-input-requerido <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" inputmode="decimal" placeholder="Fecha de visita" id="fecha_visita" name="fecha_visita" value="<?php echo arrayPrintValue(null, $datos, 'fecha_visita', null); ?>" min=""> 
                      </div>
                  </div>

                  <div class="col-1 form-group mb-0 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Hora Visita</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-clock v-requerido-icon"></i></span>
                        </div>
                          <input type="time" class="form-control <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" inputmode="decimal" placeholder="Hora" id="hora_visita" name="hora_visita" 
                          value="<?php echo arrayPrintValue(null, $datos, 'hora_visita', null); ?>">
                      </div>
                  </div>

                  <div class="col-2 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Estado</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-sliders-h v-requerido-icon"></i></span>
                        </div>
                        <select class="form-control v-input-requerido <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" id="estado_visita" name="estado_visita">
                          <?php echo !isset($datos['estado_visita']) ? '<option value="" disabled selected class="bg-secondary">Estado</option>' : ''; ?>
                          <?php echo isset($datos['estado_visita']) && $datos['estado_visita'] == "Vencida" ? '<option value="" disabled selected class="bg-secondary">Vencida</option>' : ''; ?>

                            <?php 
                            if (!isset($datos['estado_visita']) || $datos['estado_visita'] !== "Vencida") {
                                echo '<option value="Programada" ' . (isset($datos['estado_visita']) && $datos['estado_visita'] == "Programada" ? "selected" : '') . '>Programada</option>';
                            }
                            ?>

                          <option value="Reprogramada" <?php echo isset($datos['estado_visita']) && $datos['estado_visita'] == "Reprogramada" ? "selected" : ''; ?>>Reprogramada </option>
                          <option value="Ejecutada" <?php echo isset($datos['estado_visita']) && $datos['estado_visita'] == "Ejecutada" ? "selected" : ''; ?>>Ejecutada </option>
                          <option value="Cancelada" <?php echo isset($datos['estado_visita']) && $datos['estado_visita'] == "Cancelada" ? "selected" : ''; ?>>Cancelada </option>
                        </select>
                      </div>
                  </div>

                </div>
                <!-- row end -->

                <!-- /.row start-->
                <div class="row">

                  <div class="col-2 form-group mb-0 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">CUIT</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-file-invoice"></i></span>
                        </div>
                          <input type="text" class="form-control v-input-requerido <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" data-inputmask='"mask": "99-99999999-9", "clearIncomplete": "true"' data-mask="" inputmode="decimal" data-cuit="<?php echo arrayPrintValue(null, $datos, 'cuit', null); ?>" placeholder="CUIT" id="cuit" name="cuit" value="<?php echo arrayPrintValue(null, $datos, 'cuit', null); ?>">

                      </div>
                  </div>

                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Razón Social</label></small>
                      <div class="input-group mb-0 cliente-sugerencias-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-city v-requerido-icon"></i></span>
                        </div>
                        <input type="text" class="form-control v-input-requerido <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" placeholder="Razón Social" id="razon_social" name="razon_social" list="clientes_razon_social_list" autocomplete="off" value="<?php echo arrayPrintValue(null, $datos, 'razon_social', null); ?>">
                        <div id="razon_social_sugerencias" class="cliente-sugerencias-menu" role="listbox" aria-label="Clientes sugeridos"></div>
                      </div>
                  </div>

                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Contacto en obra</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-user-tie v-requerido-icon"></i></span>
                        </div>
                        <input type="text" class="form-control v-input-requerido <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" placeholder="Contacto en obra" id="contacto_obra" name="contacto_obra" value="<?php echo arrayPrintValue(null, $datos, 'contacto_obra', null); ?>">
                      </div>
                  </div>
                  <div class="col-2 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Teléfono</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-phone v-requerido-icon"></i></span>
                        </div>
                          <input type="text" class="form-control v-input-requerido <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" data-inputmask='"mask": "(9{1,5})(99999999)", "clearIncomplete": "false"' data-mask="" inputmode="decimal" placeholder="Teléfono" id="tel_contacto_obra" name="tel_contacto_obra" value="<?php echo arrayPrintValue(null, $datos, 'tel_contacto_obra', null); ?>">
                      </div>
                  </div>

                  <div class="col-2 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Email</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        </div>
                        <input type="text" class="form-control v-input-requerido <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" placeholder="Email" data-inputmask='"mask": "[{1,20}|_|.]@*{1,20}.*{1,3}[.*{1,3}]", "clearIncomplete": "true"' autocomplete="rutjfkde" id="email_contacto_obra" name="email_contacto_obra" value="<?php echo arrayPrintValue(null, $datos, 'email_contacto_obra', null); ?>">
                      </div>
                  </div>

                  <div class="col-3 form-group mb-1 mt-1">
                        <small class="v-visual-edit d-none"><label class="mb-0">Provincia</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                            </div>
                            <select class="form-control select2bs4 v-select2 provincia <?php echo $visualiza_prevista !== "" ? 'v-disabled-select2' : ''; ?>" id="provincia_visita" name="provincia_visita">
                              <option value="<?php if(isset($datos['provincia_visita'])){echo $datos['provincia_visita'];}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($datos['provincianom'])){echo $datos['provincianom'];}else{echo "Provincia";} ?></option>
                              <?php echo $provinciasSelect;?>
                            </select>
                          </div>
                  </div>

                  <div class="col-2 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">Partido</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                            </div>
                            <select class="form-control select2bs4 v-select2 partido <?php echo $visualiza_prevista !== "" ? 'v-disabled-select2' : ''; ?>" id="partido_visita" name="partido_visita">
                              <option value="<?php if(isset($datos['partido_visita'])){echo $datos['partido_visita'];}else{echo "";} ?>" disabled selected class="bg-secondary partidosOpt1"><?php if(isset($datos['partidonom'])){echo $datos['partidonom'];}else{echo "Partido";} ?></option>
                            <?php if(isset($partidosSelect)){echo $partidosSelect;} ?>
                            </select>
                          </div>
                  </div>

                  <div class="col-3 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">Localidad</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                            </div>
                            <select class="form-control select2bs4 v-select2 localidad <?php echo $visualiza_prevista !== "" ? 'v-disabled-select2' : ''; ?>" id="localidad_visita" name="localidad_visita">
                              <option value="<?php if(isset($datos['localidad_visita'])){echo $datos['localidad_visita'];}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($datos['localidadnom'])){echo $datos['localidadnom'];}else{echo "Localidad";} ?></option>
                                <?php if(isset($localidadesSelect)){echo $localidadesSelect;} ?>
                            </select>
                          </div>
                  </div>

                  <div class="col-3 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">Calle</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-road"></i></span>
                            </div>
                            <select class="form-control select2bs4 v-select2 calle <?php echo $visualiza_prevista !== "" ? 'v-disabled-select2' : ''; ?>" id="calle_visita" name="calle_visita" data-tags="true">
                              <option value="<?php if(isset($datos['calle_visita'])){echo $datos['calle_visita'];}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($datos['callenom'])){echo $datos['callenom'];}else{echo "Calle";} ?></option>
                            <?php if(isset($callesSelect)){echo $callesSelect;} ?>
                            </select>
                          </div>
                  </div>

                  <div class="col-1 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">Altura / Km</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-sort-numeric-up"></i></span>
                            </div>
                              <input type="text" class="form-control <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" data-inputmask='"mask": "9{1,5}", "clearIncomplete": "true" ' data-mask="" inputmode="decimal" placeholder="Altura / Km" id="altura_visita" name="altura_visita" value="<?php echo arrayPrintValue(null, $datos, 'altura_visita', null); ?>">
                          </div>
                  </div>

                  <div class="col-1 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">CP</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-mail-bulk"></i></span>
                            </div>
                            <input type="text" 
                              class="form-control <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" 
                              data-inputmask='"mask": "9{4,5}", "greedy": false, "clearIncomplete": false, "autoUnmask": true, "removeMaskOnSubmit": true' 
                              data-mask 
                              inputmode="decimal" 
                              placeholder="CP" 
                              id="cp_visita" 
                              name="cp_visita" 
                              value="<?php echo arrayPrintValue(null, $datos, 'cp_visita', null); ?>">
                          </div>
                  </div>

                  <div class="col-2 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Medio Contacto</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fa-solid fa-headset v-requerido-icon"></i></span>
                        </div>
                        <select class="form-control v-input-requerido <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" id="medio_contacto" name="medio_contacto">
                          <?php echo !isset($datos['medio_contacto']) ? '<option value="" disabled selected class="bg-secondary">Medio Contacto</option>' : ''; ?>
                          <option value="Gestion anterior" <?php echo isset($datos['medio_contacto']) && $datos['medio_contacto'] == "Gestion anterior" ? "selected" : ''; ?>>Gestión anterior</option>
                          <option value="Gestion comercial" <?php echo isset($datos['medio_contacto']) && $datos['medio_contacto'] == "Gestion comercial" ? "selected" : ''; ?>>Gestión comercial</option>
                          <option value="Pagina Web" <?php echo isset($datos['medio_contacto']) && $datos['medio_contacto'] == "Pagina Web" ? "selected" : ''; ?>>Página Web</option>
                          <option value="Google" <?php echo isset($datos['medio_contacto']) && $datos['medio_contacto'] == "Google" ? "selected" : ''; ?>>Google</option>
                          <option value="Nueva gestion comercial" <?php echo isset($datos['medio_contacto']) && $datos['medio_contacto'] == "Nueva gestion comercial" ? "selected" : ''; ?>>Nueva gestión comercial</option>
                          <option value="Instagram" <?php echo isset($datos['medio_contacto']) && $datos['medio_contacto'] == "Instagram" ? "selected" : ''; ?>>Instagram</option>
                          <option value="WhatsApp" <?php echo isset($datos['medio_contacto']) && $datos['medio_contacto'] == "WhatsApp" ? "selected" : ''; ?>>WhatsApp</option>
                        </select>
                      </div>
                  </div>

                  <div class="col-2 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Empresa status</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fa-solid fa-award v-requerido-icon"></i></span>
                        </div>
                        <select class="form-control v-input-requerido <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" id="empresa_status" name="empresa_status">
                          <?php echo !isset($datos['empresa_status']) ? '<option value="" disabled selected class="bg-secondary">Empresa status</option>' : ''; ?>
                          <option value="Cliente" <?php echo isset($datos['empresa_status']) && $datos['empresa_status'] == "Cliente" ? "selected" : ''; ?>>Cliente</option>
                          <option value="Con cotizacion previa" <?php echo isset($datos['empresa_status']) && $datos['empresa_status'] == "Con cotizacion previa" ? "selected" : ''; ?>>Con cotización previa</option>
                          <option value="Prospecto" <?php echo isset($datos['empresa_status']) && $datos['empresa_status'] == "Prospecto" ? "selected" : ''; ?>>Prospecto</option>
                        </select>
                      </div>
                  </div>

                </div>
                <!-- row end -->

                <!-- /.card start ---------------------------------------------------------------------------------- -->
                <div class="card card-secondary mt-2">
                          <div class="card-header p-2" style="background-color: #708bd3 !important;">
                             <h3 class="card-title"><i class="fas fa-hammer mr-2"></i>Elementos</h3>
                          </div>

                          <!-- Start card-body -->
                          <div class="card-body pb-0">

                                              <!-- start row -->                                              
                                              <div class="row d-flex align-items-center">

                                                  <div class="col-sm-5 pr-0">
                                                    <!-- checkbox -->
                                                    <div class="form-group clearfix mb-1">
                                                          <div class="icheck-success d-inline mr-4">
                                                            <input type="checkbox" id="induccion_visita" name="induccion_visita" value="s" <?php echo arrayPrintValue(null, $datos, 'induccion_visita', null) == 's' ? 'checked' : '' ; ?> <?php echo $visualiza_prevista !== "" ? 'disabled' : ''; ?>>
                                                            <label for="induccion_visita">Inducción</label>
                                                          </div>

                                                          <div class="icheck-success d-inline mr-4">
                                                            <input type="checkbox" id="chaleco_visita" name="chaleco_visita" value="s" <?php echo arrayPrintValue(null, $datos, 'chaleco_visita', null) == 's' ? 'checked' : '' ; ?> <?php echo $visualiza_prevista !== "" ? 'disabled' : ''; ?>>
                                                            <label for="chaleco_visita">Chaleco</label>
                                                          </div>

                                                          <div class="icheck-success d-inline mr-4">
                                                            <input type="checkbox" id="casco_visita" name="casco_visita" value="s" <?php echo arrayPrintValue(null, $datos, 'casco_visita', null) == 's' ? 'checked' : '' ; ?> <?php echo $visualiza_prevista !== "" ? 'disabled' : ''; ?>>
                                                            <label for="casco_visita">Casco</label>
                                                          </div>

                                                          <div class="icheck-success d-inline mr-4">
                                                            <input type="checkbox" id="escalera_visita" name="escalera_visita" value="s" <?php echo arrayPrintValue(null, $datos, 'escalera_visita', null) == 's' ? 'checked' : '' ; ?> <?php echo $visualiza_prevista !== "" ? 'disabled' : ''; ?>>
                                                            <label for="escalera_visita">Escalera</label>
                                                          </div>

                                                          <div class="icheck-success d-inline mr-4">
                                                            <input type="checkbox" id="arnes_visita" name="arnes_visita" value="s" <?php echo arrayPrintValue(null, $datos, 'arnes_visita', null) == 's' ? 'checked' : '' ; ?> <?php echo $visualiza_prevista !== "" ? 'disabled' : ''; ?>>
                                                            <label for="arnes_visita">Arnes</label>
                                                          </div>
                                                          <div class="icheck-success d-inline mr-4">
                                                            <input type="checkbox" id="soga_visita" name="soga_visita" value="s" <?php echo arrayPrintValue(null, $datos, 'soga_visita', null) == 's' ? 'checked' : '' ; ?> <?php echo $visualiza_prevista !== "" ? 'disabled' : ''; ?>>
                                                            <label for="soga_visita">Soga</label>
                                                          </div>

                                                          <div class="icheck-success d-inline mr-4">
                                                            <input type="checkbox" id="gafas_visita" name="gafas_visita" value="s" <?php echo arrayPrintValue(null, $datos, 'gafas_visita', null) == 's' ? 'checked' : '' ; ?> <?php echo $visualiza_prevista !== "" ? 'disabled' : ''; ?>>
                                                            <label for="gafas_visita">Gafas</label>
                                                          </div>
                                                    </div>
                                                    <!-- checkbox -->
                                                </div>

                                                <div class="col-sm-7 pl-0">
                                                    <div class="col-12 form-group">
                                                        <small class="v-visual-edit d-none"><label class="mb-0">Otros</label></small>
                                                        <div class="input-group mb-0">
                                                          <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-toolbox"></i></span>
                                                          </div>
                                                          <input type="text" class="form-control v-input-requerido  <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" placeholder="Otros" id="otros_visita" name="otros_visita" value="<?php echo arrayPrintValue(null, $datos, 'otros_visita', null); ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                              </div>                                        
                                              <!-- end row--> 

                          </div>
                          <!-- End card-body -->

                </div>
                <!-- /.card end ------------------------------------------------------------------------------------ -->

                <div class="row">
                      <div class="col-12 form-group mb-1 mt-1">
                        <small class="v-visual-edit d-none"><label class="mb-0">Requerimiento técnico</label></small>
                        <div class="input-group mb-0">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa-solid fa-gears"></i></span>
                          </div>
                          <textarea type="text" rows="3" class="v-visita form-control  <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" placeholder="Requerimiento técnico" id="requerimiento_tecnico" name="requerimiento_tecnico"><?php echo arrayPrintValue(null, $datos, 'requerimiento_tecnico', null); ?></textarea>
                        </div>
                      </div>
                </div>

                <div class="row">
                      <div class="col-12 form-group mb-1 mt-1">
                        <small class="v-visual-edit d-none"><label class="mb-0">Nota</label></small>
                        <div class="input-group mb-0">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                          </div>
                          <textarea type="text" rows="3" class="v-visita form-control  <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" placeholder="Nota" id="nota_visita" name="nota_visita"><?php echo arrayPrintValue(null, $datos, 'nota_visita', null); ?></textarea>
                        </div>
                      </div>
                </div>                

                <div class="row">
                  <div class="col-12 form-group mb-1 mt-2">
                      <small class="v-visual-edit d-none"><label class="mb-1">Documentos</label></small>
                      <div class="previsita-documentos-panel" id="previsitaDocumentosPanel" data-readonly="<?php echo $permiteEditarDocumentosPrevisita ? '0' : '1'; ?>" data-documentos-iniciales="<?php echo htmlspecialchars($documentosPrevisitaJson, ENT_QUOTES, 'UTF-8'); ?>">
                        <input
                          type="file"
                          class="d-none"
                          id="doc_previsita"
                          multiple
                          accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.jpg,.jpeg,.png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,image/jpeg,image/png"
                        >
                        <input type="hidden" id="previsita_documentos_eliminados" name="previsita_documentos_eliminados" value="">

                        <div class="previsita-documentos-dropzone border rounded bg-light p-3 text-muted mb-2" id="previsitaDocumentosDropzone" role="button" tabindex="0" aria-label="Adjuntar documentos de la pre-visita">
                          <div class="previsita-documentos-dropzone-copy text-center">
                            <i class="fas fa-file-upload mb-2"></i>
                            <div><strong>Arrastre aqui los archivos</strong> o haga click.</div>
                            <small class="d-block mt-1">PDF, Word, Excel, JPG, PNG o TXT. Maximo 5 MB por archivo.</small>
                          </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2 previsita-documentos-encabezado">
                          <div class="small text-muted" id="previsitaDocumentosResumen"></div>
                        </div>

                        <div class="row previsita-documentos-grid" id="previsitaDocumentosGrid"></div>
                      </div>
                  </div>
                </div>

                <div class="row <?php echo $previsita_buttons;?> text-center justify-content-center pr-1 mt-2">
                  <button type="submit" class="col-1 btn btn-primary btn-block m-2 v-alta-edit d-none" data-accion="guardar"><i class="fa fa-plus-circle"></i> Guardar</button>
                  <button type="button" class="col-1 btn btn-warning btn-block m-2 v-alta-edit d-none v-accion-cancelar" data-accion="cancelar"><i class="fa fa-ban"></i> Cancelar</button>
                </div>

                <?php if ($mostrarGuardarSoloDocumentosPrevisita): ?>
                <div class="row text-center justify-content-center pr-1 mt-2">
                  <button type="button" class="btn btn-primary btn-uniform m-2 btn-guardar-documentos-previsita">
                    <i class="fa fa-save"></i> Guardar documentos
                  </button>
                </div>
                <?php endif; ?>

          </div>
          <!-- end card accordion 1 -->

    </div>
    <!-- end collapse accordion 1-->

  </div>
  <!-- end card accordion 1 -->
</div>
<!-- end accordion -->
</form>

<?php if (isset($datos['estado_visita']) && $datos['estado_visita'] === 'Ejecutada'): ?>
  <!-- start accordion visita -->
  <div class="accordion" id="accordionVisita">
    <div class="card <?php echo $visita_card; ?> accordion_2">
    <div class="card-header" id="headingVisita">
      <h2 class="mb-0 d-flex align-items-center">
        <!-- Botón collapse -->
        <button
          class="col-9 btn btn-link btn-block text-left text-white p-0 card-title"
          type="button"
          data-toggle="collapse"
          data-target="#collapseVisita"
          aria-expanded="<?php echo $visita_show === 'show' ? 'true' : 'false'; ?>"
          aria-controls="collapseVisita"
        >
          Visita
          <?php echo isset($datos['0']['id_previsita'])
            ? ' N°:<strong class="text-lg"> ' . $datos['0']['id_previsita'].'</strong>'
            : '';
          ?>
        </button>

        <!-- Span con el popover -->
        <span class="col-3 card-title text-right">
          <strong>Intervino: </strong>
          <span
            tabindex="0"
            role="button"
            data-toggle="popover"
            data-html="true"
            data-placement="bottom"
            data-content='<?php echo htmlspecialchars($popoverIntervinientes, ENT_QUOTES); ?>'
          >
            <?php echo htmlspecialchars($ultimo, ENT_QUOTES); ?>
          </span>
        </span>
      </h2>
    </div>

    <div id="collapseVisita" class="collapse <?php echo $visita_show; ?>" aria-labelledby="headingVisita" data-parent="#accordionVisita">
        <div class="card-body">

          <!-- start accordion tareas -->
          <div class="accordion" id="accordionTareas">

            <!-- Ejemplo de Tarea 1 -->
            <div class="card tarea-card">
              <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center" id="headingTarea1">
                <button class="btn btn-link text-white p-0 m-0 flex-grow-1 text-left"
                        type="button"
                        data-toggle="collapse"
                        data-target="#collapseTarea1"
                        aria-expanded="true"
                        aria-controls="collapseTarea1"
                        data-titulo-base="Tarea 1">
                  <strong>Tarea 1:</strong> 
                </button>
                <i class="fa fa-trash eliminar-tarea v-icon-pointer" title="Eliminar tarea" style="cursor: pointer; font-size: 1.3rem;"></i>
              </div>
              <div id="collapseTarea1" class="collapse show" aria-labelledby="headingTarea1" data-parent="#accordionTareas">
                <div class="card-body">
                  <div class="row d-flex align-items-stretch">

                    <!-- Columna 1: Descripción -->
                    <div class="col-md-3">
                      <div class="card h-100 mb-2">
                        <div class="card-header v-bg-violeta text-white">
                          <h5 class="card-title mb-0">Descripción de la Tarea</h5>
                        </div>
                        <div class="card-body p-2 d-flex flex-column">
                          <div class="form-group flex-grow-1">
                            <textarea class="form-control tarea-descripcion h-100" placeholder="Describa la tarea..."></textarea>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Columna 2: Materiales y Mano de Obra -->
                    <div class="col-md-6 d-flex flex-column">
                      <!-- Materiales Asociados -->
                      <div class="card mb-2 flex-fill">
                        <div class="card-header v-bg-violeta text-white">
                          <h5 class="card-title mb-0">Materiales Asociados</h5>
                        </div>
                        <div class="card-body p-2">
                          <div class="form-row align-items-center mb-2">
                            <div class="col-8">
                              <select class="form-control form-control-sm material-select">
                                <?= $opcionesMateriales ?>
                              </select>
                            </div>
                            <div class="col-3">
                              <input type="number" class="form-control form-control-sm material-cantidad" placeholder="Cantidad" min="1">
                            </div>
                            <div class="col-1">
                              <button type="button" class="btn btn-success btn-sm agregar-material w-100"><i class="fa fa-plus"></i></button>
                            </div>
                          </div>

                          <div class="table-responsive">
                            <table class="table table-bordered materiales-table mb-2">
                              <thead class="thead-light">
                                <tr>
                                  <th>#</th>
                                  <th>Material</th>
                                  <th>Cantidad</th>
                                  <th>Acción</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr class="fila-vacia-materiales">
                                  <td colspan="4" class="text-center text-muted">Sin materiales asociados</td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>

                      <!-- Mano de Obra Asociada -->
                      <div class="card flex-fill">
                        <div class="card-header v-bg-violeta text-white">
                          <h5 class="card-title mb-0">Mano de Obra Asociada</h5>
                        </div>
                        <div class="card-body p-2">
                          <div class="form-row align-items-center mb-2">
                            <div class="col-8">
                              <select class="form-control form-control-sm mano-obra-select">
                                <?= $opcionesManoDeObra ?>
                              </select>
                            </div>
                            <div class="col-3">
                              <input type="number" class="form-control form-control-sm mano-obra-cantidad" placeholder="Cantidad" min="1">
                            </div>
                            <div class="col-1">
                              <button type="button" class="btn btn-success btn-sm agregar-mano-obra w-100"><i class="fa fa-plus"></i></button>
                            </div>
                          </div>

                          <div class="table-responsive">
                            <table class="table table-bordered mano-obra-table mb-2">
                              <thead class="thead-light text-center">
                                <tr>
                                  <th>#</th>
                                  <th>Mano de obra</th>
                                  <th>Cantidad</th>
                                  <th>Días</th>
                                  <th>Jornales</th>
                                  <th>Observaciones</th>
                                  <th>Acción</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr class="fila-vacia-mano-obra">
                                  <td colspan="7" class="text-center text-muted">Sin mano de obra asociada</td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Columna 3: Fotos -->
                    <div class="col-md-3">
                      <div class="card h-100 mb-2">
                        <div class="card-header v-bg-violeta text-white">
                          <h5 class="card-title mb-0">Fotos de la Tarea</h5>
                        </div>
                        <div class="card-body p-2">
                          <div class="custom-file mb-2">
                            <input 
                              type="file" 
                              class="custom-file-input tarea-fotos" 
                              id="fotos_tarea_1" 
                              name="fotos_tarea_1[]" 
                              multiple 
                              accept="image/*" 
                              capture="environment"
                              data-index="1"
                            />
                            <label class="custom-file-label" for="fotos_tarea_1">Seleccionar fotos</label>
                          </div>
                          <div class="row preview-fotos" id="preview_fotos_tarea_1"></div>
                        </div>
                      </div>
                    </div>

                  </div> <!-- end row -->
                </div> <!-- end card-body -->
              </div> <!-- end collapse -->
            </div>
            <!-- end card tarea1 -->
          </div>
          <!-- end accordion tareas -->

          <!-- Botón agregar nueva tarea -->
          <div class="text-center my-3">
            <button type="button" class="btn btn-primary" id="btn-agregar-tarea"><i class="fa fa-plus-circle"></i> Agregar nueva tarea</button>
          </div>

          <!-- Botones generales -->
          <div class="text-center">
            <button type="button" class="btn bg-success mr-2 btn-uniform btn-guardar-visita">Guardar Visita</button>
            <button type="button" class="btn btn-secondary mr-2 btn-uniform btn-generar-presupuesto" id="btn-generar-presupuesto" 
            <?php echo $presupuestoGenerado ? 'disabled' : ''; ?>> Generar Presupuesto</button>
            <button type="button" class="btn btn-secondary btn-uniform btn-cancelar-visita">Volver</button>
          </div>

        </div> <!-- end card-body visita -->
      </div> <!-- end collapse visita -->
    </div> <!-- end card visita -->
  </div>
  <!-- end accordion visita -->
<?php endif; ?>

<!-- Fuera del accordion, al fondo de la página -->
<select id="opcionesMaterialBase" class="d-none">
  <?= $opcionesMateriales ?>
</select>

<select id="opcionesManoObraBase" class="d-none">
  <?= $opcionesManoDeObra ?>
</select>

<!-- /accordion presupuesto -->
<?php if (!empty($presupuestoGenerado)): ?>
  <div class="accordion <?php echo $presupuesto_display; ?>" id="accordionPresupuesto">
    <div class="card <?php echo $presupuesto_card; ?> accordion_3">
      <div class="card-header" id="headingPresupuesto">
        <h2 class="mb-0 d-flex align-items-center presupuesto-accordion-header">
          <button class="btn btn-link text-left text-white p-0 card-title presupuesto-accordion-toggle" 
                  type="button" 
                  data-toggle="collapse" 
                  data-target="#collapsePresupuesto" 
                  aria-expanded="<?php echo $presupuesto_show === 'show' ? 'true' : 'false'; ?>" 
                  aria-controls="collapsePresupuesto">
            Presupuesto
          </button>
          <span class="card-title text-right presupuesto-accordion-intervino">
            <strong>Intervino: </strong>
            <span
              class="intervino-presupuesto-ultimo"
              tabindex="0"
              role="button"
              data-toggle="popover"
              data-html="true"
              data-placement="bottom"
            >
              <?php echo htmlspecialchars($ultimoIntervinoPresupuesto, ENT_QUOTES); ?>
            </span>
          </span>
        </h2>
      </div>
      <div id="collapsePresupuesto" class="collapse <?php echo $presupuesto_show; ?>" aria-labelledby="headingPresupuesto" data-parent="#accordionPresupuesto">
        <div class="card-body" id="presupuesto-card-body">
          <!-- Aquí se insertará el contenido dinámico generado -->
            <?php 
              if($presupuestoGenerado): 
                echo renderizar_presupuesto_html($presupuesto_generado, $mostrarVistaDetallada, !empty($bloqueoEdicionComercial['bloqueado']) || !empty($workflowPrevisita['bloquea_avance']));
              endif;                
            ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
<!-- /accordion presupuesto -->


<!-- start accordion 4 - Orden de compra -->
<div class="accordion d-none" id="accordionExample4">
  <div class="card <?php echo $orden_compra_card; ?> accordion 4">
    <div class="card-header" id="heading4">
      <h2 class="mb-0">
        <button class="btn btn-link btn-block text-left text-white p-0 card-title" 
                type="button" 
                data-toggle="collapse" 
                data-target="#collapse4_OC" 
                aria-expanded="true" 
                aria-controls="collapse4_OC">
          Orden de compra<?php echo isset($datos['0']['id_previsita']) ? ' N°:<strong class="text-lg"> ' . $datos['0']['id_previsita'].'</strong>' : ''; ?>
        </button>
      </h2>
    </div>

    <div id="collapse4_OC" class="collapse <?php echo !isset($cliente_datos['0']['id_cliente']) ? '' : ''; ?>" 
         aria-labelledby="heading4" 
         data-parent="#accordionExample4">
      <div class="card-body">
        EN DESARROLLO
      </div>
    </div>
  </div>
</div>
<!-- end accordion 4 -->

<!-- start accordion 5 - Pedido de materiales -->
<div class="accordion d-none" id="accordionExample5">
  <div class="card <?php echo $orden_compra_card; ?> accordion 5">
    <div class="card-header" id="heading5">
      <h2 class="mb-0">
        <button class="btn btn-link btn-block text-left text-white p-0 card-title" 
                type="button" 
                data-toggle="collapse" 
                data-target="#collapse5_PM" 
                aria-expanded="true" 
                aria-controls="collapse5_PM">
          Pedido de materiales<?php echo isset($datos['0']['id_previsita']) ? ' N°:<strong class="text-lg"> ' . $datos['0']['id_previsita'].'</strong>' : ''; ?>
        </button>
      </h2>
    </div>

    <div id="collapse5_PM" class="collapse <?php echo !isset($cliente_datos['0']['id_cliente']) ? '' : ''; ?>" 
         aria-labelledby="heading5" 
         data-parent="#accordionExample5">
      <div class="card-body">
        EN DESARROLLO
      </div>
    </div>
  </div>
</div>
<!-- end accordion 5 -->

<!-- start accordion 6 - Facturación -->
<div class="accordion d-none" id="accordionExample6">
  <div class="card <?php echo $orden_compra_card; ?> accordion 6">
    <div class="card-header" id="heading6">
      <h2 class="mb-0">
        <button class="btn btn-link btn-block text-left text-white p-0 card-title" 
                type="button" 
                data-toggle="collapse" 
                data-target="#collapse6_FACT" 
                aria-expanded="true" 
                aria-controls="collapse6_FACT">
          Facturación<?php echo isset($datos['0']['id_previsita']) ? ' N°:<strong class="text-lg"> ' . $datos['0']['id_previsita'].'</strong>' : ''; ?>
        </button>
      </h2>
    </div>

    <div id="collapse6_FACT" class="collapse <?php echo !isset($cliente_datos['0']['id_cliente']) ? '' : ''; ?>" 
         aria-labelledby="heading6" 
         data-parent="#accordionExample6">
      <div class="card-body">
        EN DESARROLLO
      </div>
    </div>
  </div>
</div>
<!-- end accordion 6 -->


</div>

 <div class="row d-flex text-center justify-content-center pr-1">
      <button onclick="window.location.href='seguimiento_de_obra_listado.php'" type="button" class="col-1 btn btn-success btn-block m-2"><i class="fa fa-arrow-circle-left"></i> Volver</button>
</div>


      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->

</div>
  <!-- /.content-wrapper -->
  <?php include '../01-views/layout/footer_layout.php';?>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="../05-plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../05-plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- contenedor oculto -->
<div id="popover-content-visita" style="display:none">
  <?= $popoverIntervinientes ?>
</div>

<div id="popover-content-presupuesto" style="display:none">
  <?= $popoverIntervinientesPresupuesto ?>
</div>

<style>
  .previsita-documentos-panel {
    padding: 0.6rem 0.7rem;
    border: 1px solid #d9e0e7;
    border-radius: 0.6rem;
    background: #ffffff;
  }

  .previsita-documentos-dropzone {
    border: 2px dashed #c7d2dc !important;
    transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
    cursor: pointer;
    padding: 0.55rem 0.75rem !important;
  }

  .previsita-documentos-dropzone-copy i {
    font-size: 1rem;
    margin-bottom: 0.2rem !important;
  }

  .previsita-documentos-dropzone-copy {
    font-size: 0.92rem;
    line-height: 1.2;
  }

  .previsita-documentos-dropzone.is-dragover {
    background: #eef6ff !important;
    border-color: #2f6fad !important;
    color: #2f6fad !important;
  }

  .previsita-documentos-panel[data-readonly="1"] .previsita-documentos-dropzone {
    cursor: default;
    background: #f8f9fa !important;
    border-style: solid !important;
    color: #6c757d !important;
  }

  .previsita-documentos-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
    margin: 0;
    min-height: 0.5rem;
  }

  .previsita-documento-slot {
    display: flex;
    flex: 0 0 290px;
    max-width: 290px;
    padding: 0;
    margin: 0 !important;
  }

  .previsita-documento-card {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    width: 100%;
    min-height: 104px;
    padding: 0.55rem 0.65rem 0.5rem;
    border: 1px solid #dde4ea;
    border-radius: 0.55rem;
    background: linear-gradient(180deg, #ffffff 0%, #f6f8fb 100%);
    box-shadow: 0 1px 2px rgba(34, 53, 74, 0.06);
  }

  @media (max-width: 575.98px) {
    .previsita-documento-slot {
      flex-basis: 100%;
      max-width: 100%;
    }
  }

  .previsita-documento-open {
    display: flex;
    align-items: flex-start;
    gap: 0.55rem;
    width: 100%;
    border: 0;
    padding: 0;
    background: transparent;
    text-align: left;
    cursor: pointer;
  }

  .previsita-documento-open:focus {
    outline: 0;
  }

  .previsita-documento-icon {
    flex: 0 0 auto;
    font-size: 1.55rem;
    line-height: 1;
  }

  .previsita-documento-body {
    display: flex;
    flex: 1 1 auto;
    min-width: 0;
    flex-direction: column;
    gap: 0.15rem;
  }

  .previsita-documento-nombre {
    display: -webkit-box;
    overflow: hidden;
    font-weight: 600;
    font-size: 0.92rem;
    line-height: 1.15;
    color: #243444;
    word-break: break-word;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }

  .previsita-documento-meta {
    font-size: 0.72rem;
    line-height: 1.2;
    color: #6c757d;
  }

  .previsita-documento-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.4rem;
    margin-top: auto;
  }

  .previsita-documento-remove {
    position: absolute;
    top: 0.3rem;
    right: 0.3rem;
    width: 1.45rem;
    height: 1.45rem;
    border: 0;
    border-radius: 999px;
    background: rgba(220, 53, 69, 0.12);
    color: #c82333;
    cursor: pointer;
    font-size: 0.78rem;
  }

  .previsita-documento-remove:hover {
    background: rgba(220, 53, 69, 0.2);
  }

  .previsita-documentos-empty {
    width: 100%;
    padding: 0.8rem;
    border: 1px dashed #d7dde4;
    border-radius: 0.55rem;
    background: #f8f9fb;
    text-align: center;
    color: #6c757d;
    font-size: 0.86rem;
  }

  .previsita-documento-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.08rem 0.34rem;
    border-radius: 999px;
    background: #e9eef5;
    font-size: 0.64rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: #516170;
    text-transform: uppercase;
  }

  .previsita-documento-actions .btn {
    padding: 0.18rem 0.48rem;
    font-size: 0.78rem;
    line-height: 1.2;
  }

  .previsita-documento-estado {
    white-space: nowrap;
    font-size: 0.72rem;
  }

  .tarea-detalle-editor {
    display: flex;
    flex-direction: column;
  }

  .tarea-detalle-editor-toolbar {
    gap: 0.2rem;
    margin-bottom: 0.35rem !important;
    width: fit-content;
    max-width: 100%;
    padding: 0.2rem 0.25rem;
    background: #eef1f4;
    border: 1px solid #d7dde4;
    border-radius: 0.4rem;
  }

  .tarea-detalle-editor-toolbar .btn {
    min-width: 1.85rem;
    padding: 0.12rem 0.35rem;
    font-size: 0.9rem;
    line-height: 1.1;
    border-color: #d7dde4;
    background: #f8f9fb;
  }

  #accordionTareas .tarea-descripcion,
  .tarea-descripcion-editor {
    min-height: 8.6rem;
    height: 8.6rem;
    max-height: 8.6rem;
    overflow-y: auto;
    overflow-x: hidden;
    white-space: pre-wrap;
    font-size: 1rem;
    line-height: 1.6;
  }

  .tarea-descripcion-editor {
    padding: 0.7rem 0.8rem;
  }

  .tarea-descripcion-editor:empty::before {
    content: attr(data-placeholder);
    color: #6c757d;
  }

  .tarea-descripcion-editor:focus {
    outline: 0;
    box-shadow: none;
  }

  .tarea-descripcion-editor p,
  .tarea-descripcion-editor div,
  .tarea-descripcion-editor ul,
  .tarea-descripcion-editor ol {
    margin-bottom: 0.45rem;
  }

  .tarea-descripcion-editor p:last-child,
  .tarea-descripcion-editor div:last-child,
  .tarea-descripcion-editor ul:last-child,
  .tarea-descripcion-editor ol:last-child {
    margin-bottom: 0;
  }

  .tarea-descripcion-editor ul,
  .tarea-descripcion-editor ol {
    padding-left: 1.25rem;
  }

  #contenedorPresupuestoGenerado .tarea-card {
    --tarea-totales-width: 300px;
    --tarea-barra-btn-height: 2.62rem;
    --tarea-util-gap: 0.45rem;
    --tarea-editor-line-height: 1.18rem;
    --tarea-editor-block-gap: 0.04rem;
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-descripcion-editor {
    line-height: var(--tarea-editor-line-height);
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-descripcion-editor p,
  #contenedorPresupuestoGenerado .tarea-card .tarea-descripcion-editor div,
  #contenedorPresupuestoGenerado .tarea-card .tarea-descripcion-editor ul,
  #contenedorPresupuestoGenerado .tarea-card .tarea-descripcion-editor ol {
    margin-bottom: var(--tarea-editor-block-gap);
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-total {
    row-gap: var(--tarea-util-gap);
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-acciones-izquierda {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-top: 0.25rem;
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-acciones-izquierda .btn {
    margin-right: 0 !important;
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-barra-inferior {
    display: flex;
    align-items: flex-end;
    gap: 1rem;
    width: 100%;
    margin-top: var(--tarea-util-gap);
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-barra-inferior .tarea-inline-actions {
    flex: 0 0 auto;
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-barra-inferior .fila-impuestos {
    display: grid;
    grid-template-columns: minmax(0, 1fr) var(--tarea-totales-width);
    column-gap: 0.75rem;
    row-gap: 0.65rem;
    flex: 1 1 auto;
    align-items: flex-end;
    min-width: 0;
    margin-left: auto;
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-barra-inferior .fila-impuestos .tarea-impuestos-lista {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    align-items: flex-end;
    align-content: flex-end;
    gap: 0.35rem;
    min-width: 0;
    width: fit-content;
    max-width: 100%;
    justify-self: end;
    margin-left: auto;
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-barra-inferior .fila-impuestos .tarea-impuestos-lista > .col-auto {
    flex: 0 0 auto;
    max-width: none;
    padding-right: 0 !important;
    padding-left: 0 !important;
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-total .btn-total-tarea,
  #contenedorPresupuestoGenerado .tarea-card .tarea-total .btn-porcentaje-tarea {
    margin-top: 0;
    padding: 0.55rem 0.9rem;
    font-size: 0.98rem;
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-barra-inferior .fila-impuestos .tarea-impuestos-lista .btn {
    white-space: nowrap;
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-barra-inferior .fila-impuestos .tarea-impuestos-lista .btn.bg-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: var(--tarea-barra-btn-height);
    font-size: 0.92rem;
    padding: 0.42rem 0.72rem;
    line-height: 1.5;
    box-sizing: border-box;
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-total .utilidades-extra,
  #contenedorPresupuestoGenerado .tarea-card .tarea-total > .d-flex.justify-content-end.w-100:not(.fila-impuestos) {
    width: min(100%, var(--tarea-totales-width));
    max-width: var(--tarea-totales-width);
    margin-left: auto;
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-total .utilidades-extra .btn-total-tarea,
  #contenedorPresupuestoGenerado .tarea-card .tarea-total > .d-flex.justify-content-end.w-100:not(.fila-impuestos) .btn-total-tarea,
  #contenedorPresupuestoGenerado .tarea-card .tarea-total > .d-flex.justify-content-end.w-100:not(.fila-impuestos) .btn-porcentaje-tarea {
    flex: 0 0 100%;
    width: 100% !important;
    max-width: 100% !important;
    white-space: nowrap;
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-barra-inferior .fila-impuestos .tarea-subtotal-col {
    width: 100%;
    max-width: var(--tarea-totales-width);
    justify-self: end;
  }

  #contenedorPresupuestoGenerado .tarea-card .tarea-barra-inferior .fila-impuestos .tarea-subtotal-col .btn-total-tarea {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100% !important;
    max-width: 100% !important;
    height: var(--tarea-barra-btn-height);
    margin-top: 0;
    padding: 0.42rem 0.72rem;
    font-size: 0.92rem;
    line-height: 1.5;
    white-space: nowrap;
    box-sizing: border-box;
  }

  @media (min-width: 768px) {
    #contenedorPresupuestoGenerado .tarea-card .tarea-card-cuerpo {
      align-items: stretch;
    }

    #contenedorPresupuestoGenerado .tarea-card .tarea-columna-izquierda {
      display: flex;
      flex-direction: column;
      min-height: 100%;
      gap: 0.75rem;
    }

    #contenedorPresupuestoGenerado .tarea-card .tarea-columna-izquierda .tarea-columna-panel {
      display: flex;
      flex: 1 1 0;
      flex-direction: column;
      min-height: 0;
      margin-bottom: 0 !important;
    }

    #contenedorPresupuestoGenerado .tarea-card .tarea-columna-derecha {
      display: flex;
      flex-direction: column;
      min-height: 100%;
    }

    #contenedorPresupuestoGenerado .tarea-card .tarea-columna-panel-detalle .tarea-detalle-editor,
    #contenedorPresupuestoGenerado .tarea-card .tarea-columna-panel-imagenes .presu-dropzone {
      flex: 1 1 auto;
      min-height: 0;
    }

    #contenedorPresupuestoGenerado .tarea-card .tarea-columna-panel-detalle .tarea-descripcion,
    #contenedorPresupuestoGenerado .tarea-card .tarea-columna-panel-detalle .tarea-descripcion-editor {
      flex: 1 1 auto;
      min-height: 0;
      height: auto;
      max-height: none;
    }

    #contenedorPresupuestoGenerado .tarea-card .tarea-columna-panel-imagenes .presu-dropzone {
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    #contenedorPresupuestoGenerado .tarea-card .tarea-columna-panel-imagenes .presu-preview-fotos {
      flex: 1 1 auto;
      min-height: 0;
      overflow-y: auto;
      align-content: flex-start;
    }

    #contenedorPresupuestoGenerado .tarea-card .tarea-columna-izquierda .tarea-acciones-izquierda {
      flex: 0 0 auto;
      margin-top: auto;
    }

    #contenedorPresupuestoGenerado .tarea-card .tarea-columna-derecha .tarea-total {
      width: 100%;
      margin-top: auto;
      padding-left: 0 !important;
      padding-right: 0 !important;
    }
  }

  @media (max-width: 767.98px) {
    #contenedorPresupuestoGenerado .tarea-card .tarea-acciones-izquierda {
      flex-wrap: wrap;
    }

    #contenedorPresupuestoGenerado .tarea-card .tarea-barra-inferior {
      flex-wrap: wrap;
    }

    #contenedorPresupuestoGenerado .tarea-card .tarea-barra-inferior .tarea-inline-actions,
    #contenedorPresupuestoGenerado .tarea-card .tarea-total .utilidades-extra,
    #contenedorPresupuestoGenerado .tarea-card .tarea-total > .d-flex.justify-content-end.w-100:not(.fila-impuestos) {
      max-width: 100%;
      flex-basis: 100%;
      width: 100%;
    }

    #contenedorPresupuestoGenerado .tarea-card .tarea-barra-inferior .fila-impuestos {
      grid-template-columns: 1fr;
      width: 100%;
      max-width: 100%;
    }

    #contenedorPresupuestoGenerado .tarea-card .tarea-barra-inferior .fila-impuestos .tarea-impuestos-lista,
    #contenedorPresupuestoGenerado .tarea-card .tarea-barra-inferior .fila-impuestos .tarea-subtotal-col {
      width: 100%;
      max-width: 100%;
      justify-self: stretch;
    }
  }

  .presupuesto-accordion-header {
    gap: 1rem;
  }

  .presupuesto-accordion-toggle {
    flex: 1 1 auto;
    min-width: 0;
  }

  .presupuesto-accordion-intervino {
    flex: 0 0 auto;
    margin-left: auto;
    white-space: nowrap;
  }

  .popover-wide {
    max-width: min(400px, calc(100vw - 2rem)) !important;
    width: min(400px, calc(100vw - 2rem)) !important;
    max-height: 60vh;
  }

  .popover-wide .popover-header:empty {
    display: none;
  }

  .popover-wide .popover-body {
    max-height: calc(60vh - 1rem);
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: auto;
    scrollbar-color: #8a8f98 #e9ecef;
  }

  .popover-wide .table {
    margin-bottom: 0;
  }

  .popover-wide .popover-body::-webkit-scrollbar {
    width: 14px;
  }

  .popover-wide .popover-body::-webkit-scrollbar-track {
    background: #e9ecef;
    border-radius: 10px;
  }

  .popover-wide .popover-body::-webkit-scrollbar-thumb {
    background: #8a8f98;
    border-radius: 10px;
    border: 3px solid #e9ecef;
  }

  .popover-wide .popover-body::-webkit-scrollbar-thumb:hover {
    background: #6c757d;
  }

  @media (max-width: 991.98px) {
    .presupuesto-accordion-header {
      flex-wrap: wrap;
    }

    .presupuesto-accordion-intervino {
      width: 100%;
      text-align: left !important;
    }
  }
</style>

<script>
window.bindIntervinoPopoverPersistente = function ($target, getContent) {
  if (!$target || !$target.length) return;

  const HIDE_DELAY_MS = 180;

  const limpiarTimer = function ($el) {
    const timer = $el.data('intervinoPopoverHideTimer');
    if (timer) {
      clearTimeout(timer);
      $el.removeData('intervinoPopoverHideTimer');
    }
  };

  const programarOcultar = function ($el) {
    limpiarTimer($el);
    const timer = setTimeout(function () {
      const tipId = $el.attr('aria-describedby');
      const $tip = tipId ? $('#' + tipId) : $();
      const triggerActivo = $el.is(':hover') || $el.is(':focus');
      const popoverActivo = $tip.length && $tip.is(':hover');

      if (!triggerActivo && !popoverActivo) {
        $el.popover('hide');
      }
    }, HIDE_DELAY_MS);

    $el.data('intervinoPopoverHideTimer', timer);
  };

  const vincularHoverPopover = function ($el) {
    const tipId = $el.attr('aria-describedby');
    if (!tipId) return;

    const $tip = $('#' + tipId);
    if (!$tip.length) return;

    $tip.off('.intervinoPopoverPersistente');
    $tip.on('mouseenter.intervinoPopoverPersistente', function () {
      limpiarTimer($el);
    });
    $tip.on('mouseleave.intervinoPopoverPersistente', function () {
      programarOcultar($el);
    });
  };

  $target.off('.intervinoPopoverPersistente');
  $target.popover('dispose');
  $target.popover({
    trigger: 'manual',
    container: 'body',
    boundary: 'viewport',
    html: true,
    sanitize: false,
    placement: function () {
      return $(this).data('placement') || 'bottom';
    },
    template: `
      <div class="popover popover-wide" role="tooltip">
        <div class="arrow"></div>
        <h3 class="popover-header"></h3>
        <div class="popover-body"></div>
      </div>
    `,
    content: function () {
      return getContent.call(this);
    }
  });

  $target.on('mouseenter.intervinoPopoverPersistente focusin.intervinoPopoverPersistente', function () {
    const $el = $(this);
    limpiarTimer($el);
    $el.popover('show');
    vincularHoverPopover($el);
  });

  $target.on('mouseleave.intervinoPopoverPersistente focusout.intervinoPopoverPersistente', function () {
    programarOcultar($(this));
  });

  $target.on('keydown.intervinoPopoverPersistente', function (e) {
    if (e.key === 'Escape') {
      limpiarTimer($(this));
      $(this).popover('hide');
    }
  });
};

$(function(){
  window.bindIntervinoPopoverPersistente(
    $('#headingVisita [data-toggle="popover"]'),
    function () {
      return $('#popover-content-visita').html();
    }
  );
});

window.initPopoverIntervinoPresupuesto = function () {
  const $target = $('#headingPresupuesto .intervino-presupuesto-ultimo[data-toggle="popover"]');
  if (!$target.length) return;

  window.bindIntervinoPopoverPersistente($target, function () {
    return $('#popover-content-presupuesto').html();
  });
};

window.actualizarIntervinoPresupuestoUI = function (intervino) {
  const ultimo = String(intervino?.ultimo_texto || 'Sin intervenciones');
  const popoverHtml = String(intervino?.popover_html || '');

  $('#popover-content-presupuesto').html(popoverHtml);
  $('.intervino-presupuesto-ultimo').text(ultimo);

  window.initPopoverIntervinoPresupuesto();
};

$(function () {
  window.initPopoverIntervinoPresupuesto();
});
</script>

<!-- Select2 -->
<script src="../05-plugins/select2/js/select2.full.min.js"></script>
<script src="../05-plugins/select2/js/i18n/es.js"></script>
<!-- Bootstrap4 Duallistbox -->
<script src="../05-plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
<!-- moment -->
<script src="../05-plugins/moment/moment.min.js"></script>
<!-- InputMask -->
<script src="../05-plugins/inputmask/jquery.inputmask.min.js"></script>
<!-- date-range-picker -->
<script src="../05-plugins/daterangepicker/daterangepicker.js"></script>
<!-- bootstrap color picker -->
<script src="../05-plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="../05-plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- BS-Stepper -->
<script src="../05-plugins/bs-stepper/js/bs-stepper.min.js"></script>
<!-- dropzonejs -->
<script src="../05-plugins/dropzone/min/dropzone.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>
<!-- jquery-validation -->
<script src="../05-plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="../05-plugins/jquery-validation/additional-methods.min.js"></script>
<!-- SweetAlert2 -->
<script src="../05-plugins/sweetalert2/sweetalert2.min.js"></script>
<!-- Toastr -->
<script src="../05-plugins/toastr/toastr.min.js"></script>
<!-- bs-custom-file-input -->
<script src="../05-plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>

<!-- popper -->
<!-- <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script> -->

<!-- funciones customizadas -->
<script src="../07-funciones_js/funciones.js"></script>
<script src="../07-funciones_js/sAlertConfirmV2.js"></script>

<!-- Page specific script -->
<script>
  var camposLimpios = "";

$(document).ready(function() {
  toastr.options = {
    closeButton: true,
    progressBar: true,
    newestOnTop: true,
    timeOut: 2600,
    extendedTimeOut: 350,
    showDuration: 150,
    hideDuration: 150,
    preventDuplicates: false
  };

  $('[data-mask]').inputmask({ clearIncomplete: false });

  // Aplicar la máscara con opciones correctas
  $('#cp_visita').inputmask({
    mask: "9{4,5}",
    clearIncomplete: false,         // No borra por blur
    greedy: false,                  // Acepta desde 4
    autoUnmask: true,               // Devuelve valor limpio
    removeMaskOnSubmit: true,       // Enviar sin máscara
    showMaskOnHover: false,
    showMaskOnFocus: false
  });


  // Desactiva el evento 'blur' automático que borra el valor si lo considera incompleto
  $('#cp_visita').off('blur');

  // (Opcional) Reaplicar el valor manualmente si querés ver qué pasa
  $('#cp_visita').on('blur', function () {
    console.log('Valor desenfocado:', $(this).val());
  });

    navigator.clipboard.writeText('');
    // current funtions
    abm_detect();
    if( $(".v-id").val() != "" ){ 
     inputEmptyDetect("input, select, textarea");
    }

    function obtenerFechaLocalInput(fecha) {
      const fechaBase = fecha instanceof Date ? fecha : new Date();
      const year = fechaBase.getFullYear();
      const month = String(fechaBase.getMonth() + 1).padStart(2, '0');
      const day = String(fechaBase.getDate()).padStart(2, '0');
      return year + '-' + month + '-' + day;
    }

    function sincronizarFechaVisitaMinima() {
      var campoFecha = document.getElementById('fecha_visita');
      if (!campoFecha) {
        return '';
      }

      var fechaActualInput = obtenerFechaLocalInput(new Date());
      campoFecha.min = fechaActualInput;
      return fechaActualInput;
    }

    function sincronizarTituloClientePrevisita() {
      var $tituloCliente = $('#previsita_cliente_titulo');
      var $campoRazonSocial = $('#razon_social');

      if (!$tituloCliente.length || !$campoRazonSocial.length) {
        return;
      }

      var razonSocialActual = String($campoRazonSocial.val() || '').trim();
      if (razonSocialActual.indexOf(' | ') !== -1) {
        razonSocialActual = razonSocialActual.split(' | ')[0].trim();
      }

      $tituloCliente.text(razonSocialActual !== '' ? ' | ' + razonSocialActual.toUpperCase() : '');
    }

    function normalizarClaveClienteSugerido(texto) {
      return String(texto || '')
        .trim()
        .replace(/\s+/g, ' ')
        .toUpperCase();
    }

    function escaparHtmlClienteSugerencia(texto) {
      return String(texto || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function obtenerListadoClientesSugeridos() {
      var clientesSugeridos = window.SEGUIMIENTO_CLIENTES_SUGERIDOS || {};
      var clientesUnicos = [];
      var clientesIndexados = {};

      Object.keys(clientesSugeridos).forEach(function (claveCliente) {
        var clienteSugerido = clientesSugeridos[claveCliente];

        if (!clienteSugerido) {
          return;
        }

        var claveUnica = [
          String(clienteSugerido['id_cliente'] || ''),
          String(clienteSugerido['cuit'] || ''),
          String(clienteSugerido['razon_social'] || '')
        ].join('|');

        if (clientesIndexados[claveUnica]) {
          return;
        }

        clientesIndexados[claveUnica] = true;
        clientesUnicos.push(clienteSugerido);
      });

      return clientesUnicos;
    }

    function obtenerClienteSugeridoPorRazonSocial(valorIngresado) {
      var claveClienteBuscada = normalizarClaveClienteSugerido(valorIngresado);
      var clientesSugeridos = obtenerListadoClientesSugeridos();

      if (claveClienteBuscada === '') {
        return null;
      }

      for (var i = 0; i < clientesSugeridos.length; i++) {
        var clienteSugerido = clientesSugeridos[i];
        var razonSocial = normalizarClaveClienteSugerido(clienteSugerido['razon_social'] || '');
        var cuit = normalizarClaveClienteSugerido(clienteSugerido['cuit'] || '');
        var label = normalizarClaveClienteSugerido(clienteSugerido['label'] || '');

        if (
          claveClienteBuscada === razonSocial ||
          (cuit !== '' && claveClienteBuscada === cuit) ||
          (label !== '' && claveClienteBuscada === label)
        ) {
          return clienteSugerido;
        }
      }

      return null;
    }

    function filtrarClientesSugeridosPorRazonSocial(valorIngresado) {
      var terminoBuscado = normalizarClaveClienteSugerido(valorIngresado);

      if (terminoBuscado.length < 2) {
        return [];
      }

      return obtenerListadoClientesSugeridos()
        .map(function (clienteSugerido) {
          var razonSocial = normalizarClaveClienteSugerido(clienteSugerido['razon_social'] || '');
          var cuit = normalizarClaveClienteSugerido(clienteSugerido['cuit'] || '');
          var label = normalizarClaveClienteSugerido(clienteSugerido['label'] || '');
          var prioridad = 99;

          if (razonSocial.indexOf(terminoBuscado) === 0) {
            prioridad = 0;
          } else if (label.indexOf(terminoBuscado) === 0) {
            prioridad = 1;
          } else if (cuit.indexOf(terminoBuscado) === 0) {
            prioridad = 2;
          } else if (razonSocial.indexOf(terminoBuscado) !== -1) {
            prioridad = 3;
          } else if (label.indexOf(terminoBuscado) !== -1 || cuit.indexOf(terminoBuscado) !== -1) {
            prioridad = 4;
          }

          if (prioridad === 99) {
            return null;
          }

          return {
            prioridad: prioridad,
            cliente: clienteSugerido
          };
        })
        .filter(function (item) {
          return item !== null;
        })
        .sort(function (a, b) {
          if (a.prioridad !== b.prioridad) {
            return a.prioridad - b.prioridad;
          }

          return String(a.cliente['razon_social'] || '').localeCompare(
            String(b.cliente['razon_social'] || ''),
            'es',
            { sensitivity: 'base' }
          );
        })
        .slice(0, 8)
        .map(function (item) {
          return item.cliente;
        });
    }

    function ocultarSugerenciasRazonSocial() {
      window.SEGUIMIENTO_CLIENTES_SUGERENCIAS_ACTIVAS = [];
      window.SEGUIMIENTO_CLIENTE_SUGERENCIA_ACTIVA = -1;
      $('#razon_social_sugerencias').stop(true, true).hide().empty();
    }

    function actualizarIndiceSugerenciaActiva(indiceSugerido) {
      var sugerenciasActivas = window.SEGUIMIENTO_CLIENTES_SUGERENCIAS_ACTIVAS || [];
      var $menuSugerencias = $('#razon_social_sugerencias');

      if (!$menuSugerencias.length || !sugerenciasActivas.length) {
        window.SEGUIMIENTO_CLIENTE_SUGERENCIA_ACTIVA = -1;
        return;
      }

      var indiceNormalizado = parseInt(indiceSugerido, 10);

      if (isNaN(indiceNormalizado) || indiceNormalizado < 0) {
        indiceNormalizado = -1;
      } else if (indiceNormalizado >= sugerenciasActivas.length) {
        indiceNormalizado = sugerenciasActivas.length - 1;
      }

      window.SEGUIMIENTO_CLIENTE_SUGERENCIA_ACTIVA = indiceNormalizado;

      var $itemsSugeridos = $menuSugerencias.find('.cliente-sugerencia-item');
      $itemsSugeridos.removeClass('is-active').attr('aria-selected', 'false');

      if (indiceNormalizado < 0) {
        return;
      }

      var $itemActivo = $itemsSugeridos.filter('[data-index="' + indiceNormalizado + '"]');
      $itemActivo.addClass('is-active').attr('aria-selected', 'true');

      if ($itemActivo.length && typeof $itemActivo.get(0).scrollIntoView === 'function') {
        $itemActivo.get(0).scrollIntoView({ block: 'nearest' });
      }
    }

    function renderizarSugerenciasRazonSocial(clientesSugeridos) {
      var $menuSugerencias = $('#razon_social_sugerencias');

      if (!$menuSugerencias.length) {
        return;
      }

      if (!Array.isArray(clientesSugeridos) || !clientesSugeridos.length) {
        ocultarSugerenciasRazonSocial();
        return;
      }

      window.SEGUIMIENTO_CLIENTES_SUGERENCIAS_ACTIVAS = clientesSugeridos;
      window.SEGUIMIENTO_CLIENTE_SUGERENCIA_ACTIVA = -1;

      var htmlSugerencias = '';

      clientesSugeridos.forEach(function (clienteSugerido, indiceSugerencia) {
        var razonSocial = escaparHtmlClienteSugerencia(clienteSugerido['razon_social'] || '');
        var cuit = escaparHtmlClienteSugerencia(clienteSugerido['cuit'] || '');

        htmlSugerencias += '<button type="button" class="cliente-sugerencia-item" data-index="' + indiceSugerencia + '" role="option" aria-selected="false">';
        htmlSugerencias += '<span class="cliente-sugerencia-titulo">' + (razonSocial !== '' ? razonSocial : 'Cliente sin razon social') + '</span>';

        if (cuit !== '') {
          htmlSugerencias += '<span class="cliente-sugerencia-meta">CUIT: ' + cuit + '</span>';
        }

        htmlSugerencias += '</button>';
      });

      $menuSugerencias.html(htmlSugerencias).show();
    }

    function seleccionarClienteSugerido(clienteSugerido, valorReferencia) {
      if (!clienteSugerido) {
        return;
      }

      var razonSocialActual = String(clienteSugerido['razon_social'] || '').trim();
      var cuitActual = String(clienteSugerido['cuit'] || '').trim();

      if (razonSocialActual === '') {
        ocultarSugerenciasRazonSocial();
        return;
      }

      $('#razon_social').val(razonSocialActual);
      ocultarSugerenciasRazonSocial();
      sincronizarTituloClientePrevisita();

      if (
        String($('#razon_social').val() || '').trim() === razonSocialActual &&
        String($('#cuit').val() || '').trim() === cuitActual
      ) {
        return;
      }

      mostrarSugerenciaClienteRegistrado(
        clienteSugerido,
        String(valorReferencia || clienteSugerido['label'] || razonSocialActual).trim(),
        'razon_social'
      );
    }

    function seleccionarClienteSugeridoPorIndice(indiceSugerencia) {
      var sugerenciasActivas = window.SEGUIMIENTO_CLIENTES_SUGERENCIAS_ACTIVAS || [];
      var indiceNormalizado = parseInt(indiceSugerencia, 10);

      if (
        isNaN(indiceNormalizado) ||
        indiceNormalizado < 0 ||
        indiceNormalizado >= sugerenciasActivas.length
      ) {
        return;
      }

      var clienteSugerido = sugerenciasActivas[indiceNormalizado];
      $('#razon_social').data('omitir-autosugerencia-change', true);
      seleccionarClienteSugerido(clienteSugerido, clienteSugerido['label'] || clienteSugerido['razon_social'] || '');
    }

    var fechaActualInput = sincronizarFechaVisitaMinima();
    sincronizarTituloClientePrevisita();
    ocultarSugerenciasRazonSocial();

    $(document).on('input blur', '#razon_social', function () {
      sincronizarTituloClientePrevisita();
    });

    $(document).on('input', '#razon_social', function () {
      if ($(this).is(':disabled')) {
        ocultarSugerenciasRazonSocial();
        return;
      }

      if (sugerenciasRazonSocialTemporalmenteBloqueadas()) {
        ocultarSugerenciasRazonSocial();
        return;
      }

      var valorRazonSocial = String($(this).val() || '').trim();

      if (valorRazonSocial.length < 2) {
        ocultarSugerenciasRazonSocial();
        return;
      }

      renderizarSugerenciasRazonSocial(filtrarClientesSugeridosPorRazonSocial(valorRazonSocial));
    });

    $(document).on('focus', '#razon_social', function () {
      if ($(this).is(':disabled')) {
        return;
      }

      if (sugerenciasRazonSocialTemporalmenteBloqueadas()) {
        ocultarSugerenciasRazonSocial();
        return;
      }

      var valorRazonSocial = String($(this).val() || '').trim();

      if (valorRazonSocial.length < 2) {
        ocultarSugerenciasRazonSocial();
        return;
      }

      renderizarSugerenciasRazonSocial(filtrarClientesSugeridosPorRazonSocial(valorRazonSocial));
    });

    $(document).on('keydown', '#razon_social', function (event) {
      var valorRazonSocial = String($(this).val() || '').trim();
      var sugerenciasActivas = window.SEGUIMIENTO_CLIENTES_SUGERENCIAS_ACTIVAS || [];
      var indiceActivo = parseInt(window.SEGUIMIENTO_CLIENTE_SUGERENCIA_ACTIVA, 10);

      if (event.key === 'Escape') {
        ocultarSugerenciasRazonSocial();
        return;
      }

      if (!sugerenciasActivas.length && valorRazonSocial.length >= 2) {
        renderizarSugerenciasRazonSocial(filtrarClientesSugeridosPorRazonSocial(valorRazonSocial));
        sugerenciasActivas = window.SEGUIMIENTO_CLIENTES_SUGERENCIAS_ACTIVAS || [];
      }

      if (!sugerenciasActivas.length) {
        return;
      }

      if (isNaN(indiceActivo)) {
        indiceActivo = -1;
      }

      if (event.key === 'ArrowDown') {
        event.preventDefault();
        actualizarIndiceSugerenciaActiva(indiceActivo + 1);
        return;
      }

      if (event.key === 'ArrowUp') {
        event.preventDefault();
        actualizarIndiceSugerenciaActiva(indiceActivo <= 0 ? 0 : indiceActivo - 1);
        return;
      }

      if (event.key === 'Enter' && indiceActivo >= 0) {
        event.preventDefault();
        seleccionarClienteSugeridoPorIndice(indiceActivo);
      }
    });

    $(document).on('mouseenter', '.cliente-sugerencia-item', function () {
      actualizarIndiceSugerenciaActiva($(this).data('index'));
    });

    $(document).on('mousedown', '.cliente-sugerencia-item', function (event) {
      event.preventDefault();
      seleccionarClienteSugeridoPorIndice($(this).data('index'));
    });

    $(document).on('mousedown', function (event) {
      if ($(event.target).closest('.cliente-sugerencias-group').length) {
        return;
      }

      ocultarSugerenciasRazonSocial();
    });

    $(document).on('change', '#razon_social', function () {
      if ($(this).data('omitir-autosugerencia-change')) {
        $(this).removeData('omitir-autosugerencia-change');
        ocultarSugerenciasRazonSocial();
        return;
      }

      var valorRazonSocial = String($(this).val() || '').trim();
      var clienteSugerido = obtenerClienteSugeridoPorRazonSocial(valorRazonSocial);

      if (!clienteSugerido) {
        ocultarSugerenciasRazonSocial();
        return;
      }

      seleccionarClienteSugerido(clienteSugerido, valorRazonSocial);
    });

    $(document).on('change', '#estado_visita', function () {
      var $campoFechaVisita = $('#fecha_visita');

      if (!$campoFechaVisita.length || $campoFechaVisita.is(':disabled')) {
        return;
      }

      var fechaMinima = sincronizarFechaVisitaMinima();
      var fechaActualCampo = String($campoFechaVisita.val() || '').trim();

      if (fechaActualCampo === '' || fechaActualCampo < fechaMinima) {
        $campoFechaVisita.val(fechaMinima).trigger('change');
        toastr.info('La fecha de visita se actualizo a hoy al cambiar el estado.');
      }
    });


///////////////////////////////////////////////////////////////////////////////
  var itemTable = 'off';
  var itemNumber = <?php echo isset($itemNumber) ? (int)$itemNumber : 0 ?>;

  $(document).on('click',"#v-btn-add",function(){  
    if(($("#item_select").val() == 'Seleccione un ítem' || $("#item_select").val() == null) || $("#item_cantidad").val() == ''){

 sAlertAutoClose('error',
  'COMPLETA LOS CAMPOS',
  'Debes completar los campos <b>Items sugeridos</b> y <b>cantidad</b> para agregar un ítem', 
  6000,
 );

    }else{

        if(itemTable == 'off'){
          $('#items_table').removeClass('d-none');
          $('#visita_buttons').removeClass('d-none');
          itemTable = 'on';  
        }
     

        itemNumber = itemNumber + 1;
          var html  = '<tr class="v-materiales-visita" id="item_'+String(itemNumber)+'" data-id_mate_visi="add" data-material_id="'+$('#item_select option:selected').val()+'" data-material_cantidad="'+$('#item_cantidad').inputmask('unmaskedvalue')+'">';
              html += '<td>'+String(itemNumber)+'.</td>';
              html += '<td>'+$('#item_select option:selected').text()+'</td>;'
              html += '<td class="text-center">'+$('#item_cantidad').inputmask('unmaskedvalue')+'</td>';
              html += '<td></td>';
              html += '<td class="text-center"><i class="fa-solid fa-circle-xmark text-danger v-icon-delete-item v-icon-pointer" data-item_id="item_'+String(itemNumber)+'"></i></td></tr>';
              html += '</tr>';

        $("#items_table_body").append(html);  
        if($("#items_table tbody tr").length > 0){$('#items_table, #visita_buttons').fadeIn(300);}

        $("#item_select").val(null).trigger('change.select2');

        var firstOption = $("#item_select option:first");
        firstOption.prop("selected", true);
        $("#item_select").trigger('change.select2');

        $("#item_cantidad").val('');
    }

  });    

  $(document).on('click',".v-icon-delete-item",function(){  
    var itemTableId = $(this).data("item_id");

    simpleUpdateInDB(
      '../06-funciones_php/funciones.php',
      'materiales_visita',
      {estado: 'eliminado'},
      {columna: 'id_materiales_visita', condicion: '=', valorCompara: $('#'+itemTableId).data("id_mate_visi")},
      undefined
    )

    $('#'+itemTableId).remove();

    if($("#items_table tbody tr").length == 0){$('#items_table, #visita_buttons').fadeOut(300);}

  });



//////////////////////////////////////////////////////////////////////////////////


  function obtenerClienteRegistradoExpandido(idCliente) {
      return new Promise(function(resolve, reject) {
        if (idCliente === undefined || idCliente === null || idCliente === '') {
          resolve(null);
          return;
        }

        $.ajax({
          type: 'POST',
          url: '../04-modelo/clientesModel.php',
          data: {
            via: 'ajax',
            funcion: 'modGetClientesById',
            id: idCliente
          },
          dataType: 'json',
          success: function(data) {
            if (Array.isArray(data) && data.length > 0) {
              resolve(data[0]);
              return;
            }

            resolve(null);
          },
          error: function(xhr, status, error) {
            reject(error);
          }
        });
      });
  }

  function normalizarDetalleDomicilioCliente(detalleDomicilio) {
      if (!detalleDomicilio || detalleDomicilio['status'] === false) {
        return null;
      }

      return {
        calle: String(detalleDomicilio['calle'] || detalleDomicilio['callenom'] || '').trim(),
        localidad: String(detalleDomicilio['localidad'] || detalleDomicilio['localidadnom'] || '').trim(),
        partido: String(detalleDomicilio['partido'] || detalleDomicilio['partidonom'] || '').trim(),
        provincia: String(detalleDomicilio['provincia'] || detalleDomicilio['provincianom'] || '').trim()
      };
  }

  function detalleDomicilioClienteValido(detalleDomicilio) {
      return !!(
        detalleDomicilio &&
        detalleDomicilio['calle'] &&
        detalleDomicilio['localidad'] &&
        detalleDomicilio['provincia']
      );
  }

  function obtenerDetalleDomicilioCliente(clienteEncontrado) {
      if (!clienteEncontrado) {
        return Promise.resolve(null);
      }

      var detalleDesdeCliente = normalizarDetalleDomicilioCliente(clienteEncontrado);
      if (detalleDomicilioClienteValido(detalleDesdeCliente)) {
        return Promise.resolve(detalleDesdeCliente);
      }

      var intentarPorClienteId = Promise.resolve(null);
      if (clienteEncontrado['id_cliente']) {
        intentarPorClienteId = obtenerClienteRegistradoExpandido(clienteEncontrado['id_cliente'])
        .then(function(clienteExpandido) {
          return normalizarDetalleDomicilioCliente(clienteExpandido);
        })
        .catch(function() {
          return null;
        });
      }

      return intentarPorClienteId.then(function(detalleExpandido) {
        if (detalleDomicilioClienteValido(detalleExpandido)) {
          return detalleExpandido;
        }

        if (
          !clienteEncontrado['dirfis_calle'] ||
          !clienteEncontrado['dirfis_localidad']
        ) {
          return null;
        }

        return dataByIdCalleLocalidad(
          '../06-funciones_php/funciones.php',
          'dataByIdCalleLocalidad',
          clienteEncontrado['dirfis_calle'],
          clienteEncontrado['dirfis_localidad']
        )
        .then(function(detalleDomicilio) {
          return normalizarDetalleDomicilioCliente(detalleDomicilio);
        })
        .catch(function() {
          return null;
        });
      });
  }

  function esperarOpcionSelect($select, value, descripcion) {
      return new Promise(function(resolve, reject) {
        if (value === undefined || value === null || value === '') {
          resolve();
          return;
        }

        const valueString = String(value);
        const inicio = Date.now();
        const timeoutMs = 6000;

        function verificarOpcion() {
          const existeOpcion = $select.find('option').filter(function() {
            return String($(this).val()) === valueString;
          }).length > 0;

          if (existeOpcion) {
            resolve();
            return;
          }

          if ((Date.now() - inicio) >= timeoutMs) {
            reject('No se pudo cargar ' + descripcion + ' del domicilio del cliente.');
            return;
          }

          setTimeout(verificarOpcion, 120);
        }

        verificarOpcion();
      });
  }

  function seleccionarValorSelect($select, value) {
      if (value === undefined || value === null || value === '') {
        return;
      }

      $select.val(String(value)).trigger('change.select2').trigger('change');
  }

  function aplicarDomicilioClienteRegistrado(clienteEncontrado) {
      if (!clienteEncontrado || !clienteEncontrado['dirfis_provincia']) {
        return Promise.resolve();
      }

      const $provincia = $("#provincia_visita");
      const $partido = $("#partido_visita");
      const $localidad = $("#localidad_visita");
      const $calle = $("#calle_visita");

      seleccionarValorSelect($provincia, clienteEncontrado['dirfis_provincia']);

      if (!clienteEncontrado['dirfis_partido']) {
        return Promise.resolve();
      }

      return esperarOpcionSelect($partido, clienteEncontrado['dirfis_partido'], 'el partido')
      .then(function() {
        seleccionarValorSelect($partido, clienteEncontrado['dirfis_partido']);

        const esperas = [];

        if (clienteEncontrado['dirfis_localidad']) {
          esperas.push(esperarOpcionSelect($localidad, clienteEncontrado['dirfis_localidad'], 'la localidad'));
        }

        if (clienteEncontrado['dirfis_calle']) {
          esperas.push(esperarOpcionSelect($calle, clienteEncontrado['dirfis_calle'], 'la calle'));
        }

        return Promise.all(esperas);
      })
      .then(function() {
        seleccionarValorSelect($localidad, clienteEncontrado['dirfis_localidad']);
        seleccionarValorSelect($calle, clienteEncontrado['dirfis_calle']);
      });
  }

  function aplicarDatosClienteRegistrado(clienteEncontrado) {
      $('#cuit').val(clienteEncontrado['cuit'] || '');

      inputPushValue({
        "#razon_social": {
          valor: clienteEncontrado['razon_social'] || '',
          texto: false
        },
        "#altura_visita": {
          valor: clienteEncontrado['dirfis_altura'] || '',
          texto: false
        },
        "#cp_visita": {
          valor: clienteEncontrado['dirfis_cp'] || '',
          texto: false
        }
      });

      return aplicarDomicilioClienteRegistrado(clienteEncontrado);
  }

  function bloquearSugerenciasRazonSocialTemporalmente(duracionMs) {
      window.SEGUIMIENTO_RAZON_SOCIAL_SUGERENCIAS_BLOQUEADAS_HASTA = Date.now() + Math.max(0, parseInt(duracionMs, 10) || 0);
  }

  function sugerenciasRazonSocialTemporalmenteBloqueadas() {
      return Number(window.SEGUIMIENTO_RAZON_SOCIAL_SUGERENCIAS_BLOQUEADAS_HASTA || 0) > Date.now();
  }

  function finalizarAutocompletadoClienteRegistrado(demoraMs) {
      var $campoRazonSocial = $('#razon_social');
      var $campoSiguiente = $('#contacto_obra');
      var demoraBaseMs = Math.max(0, parseInt(demoraMs, 10) || 0);
      var demoraEnfoqueMs = demoraBaseMs + 220;

      bloquearSugerenciasRazonSocialTemporalmente(demoraEnfoqueMs + 900);
      ocultarSugerenciasRazonSocial();

      if ($campoRazonSocial.length) {
        $campoRazonSocial.trigger('blur');
      }

      if (window.SEGUIMIENTO_FINALIZAR_CLIENTE_TIMEOUT) {
        window.clearTimeout(window.SEGUIMIENTO_FINALIZAR_CLIENTE_TIMEOUT);
      }

      window.SEGUIMIENTO_FINALIZAR_CLIENTE_TIMEOUT = window.setTimeout(function() {
        ocultarSugerenciasRazonSocial();

        if (document.activeElement && typeof document.activeElement.blur === 'function') {
          document.activeElement.blur();
        }

        if (
          $campoSiguiente.length &&
          !$campoSiguiente.is(':disabled') &&
          $campoSiguiente.is(':visible')
        ) {
          $campoSiguiente.trigger('focus');
          return;
        }

        if ($campoRazonSocial.length) {
          $campoRazonSocial.trigger('blur');
        }
      }, demoraEnfoqueMs);
  }

  function obtenerTituloAvisoClienteRegistrado(origenAviso) {
      return origenAviso === 'razon_social' ? 'DATOS DEL CLIENTE' : 'CLIENTE YA REGISTRADO';
  }

  function mostrarSugerenciaClienteRegistrado(clienteEncontrado, valorReferencia, origenAviso) {
      if (!clienteEncontrado || clienteEncontrado['status'] === false) {
        return;
      }

      var tituloAviso = obtenerTituloAvisoClienteRegistrado(origenAviso);

      obtenerDetalleDomicilioCliente(clienteEncontrado)
      .then(function(detalleDomicilio) {
        var mensajeCuit = 'CUIT: ' + (clienteEncontrado['cuit'] || valorReferencia || '') + '<br>';
        mensajeCuit += '<strong>' + (clienteEncontrado['razon_social'] || '') + '</strong><br><br>';

        const calleDomicilio = detalleDomicilio && detalleDomicilio['calle'] ? detalleDomicilio['calle'] : '';
        const localidadDomicilio = detalleDomicilio && detalleDomicilio['localidad'] ? detalleDomicilio['localidad'] : '';
        const provinciaDomicilio = detalleDomicilio && detalleDomicilio['provincia'] ? detalleDomicilio['provincia'] : '';

        if (calleDomicilio && localidadDomicilio && provinciaDomicilio) {
          mensajeCuit += 'Domicilio<br>';
          mensajeCuit += '<strong>' + calleDomicilio + ' Nro: ' + (clienteEncontrado['dirfis_altura'] || '') + ' - ' + localidadDomicilio + '<br>';
          mensajeCuit += provinciaDomicilio + ' - CP: ' + (clienteEncontrado['dirfis_cp'] || '') + '</strong><br><br>';
        } else {
          mensajeCuit += 'Se encontro el cliente, pero no se pudo reconstruir el domicilio fiscal completo para mostrarlo en el aviso.<br><br>';
        }

        mensajeCuit += '¿Desea utilizar estos datos del cliente?';

        sAlertDialog(
          'info',
          '<h3><strong>' + tituloAviso + '</strong></h3>',
          mensajeCuit,
          'SI',
          'success',
          'NO',
          'warning',
          function() {
            var demoraActualizacionCamposMs = 1200;

            sAlertAutoClose("info", "ACTUALIZANDO CAMPOS", "", demoraActualizacionCamposMs);
            finalizarAutocompletadoClienteRegistrado(demoraActualizacionCamposMs);

            aplicarDatosClienteRegistrado(clienteEncontrado)
            .catch(function(error) {
              sAlertConfirm('warning', 'NO SE PUDO COMPLETAR TODO EL DOMICILIO', error, 'OK', '#ffc107');
            });
          },
          undefined,
          undefined,
          undefined,
          undefined,
          undefined
        );
      });
  }

  $(document).on('change',"#cuit",function(){

      var valueSearchNormalizado = String($(this).val() || '').trim();

      if (valueSearchNormalizado === '') {
        return;
      }

      existInDB('../06-funciones_php/funciones.php', 'existInDB', 'clientes', 'cuit', valueSearchNormalizado)
      .then(function(clienteEncontrado) {

            if (!clienteEncontrado || clienteEncontrado['status'] === false) {
              return;
            }

            obtenerDetalleDomicilioCliente(clienteEncontrado)
            .then(function(detalleDomicilio) {
              var mensajeCuit = 'CUIT: ' + (clienteEncontrado['cuit'] || valueSearchNormalizado) + '<br>';
              mensajeCuit += '<strong>' + (clienteEncontrado['razon_social'] || '') + '</strong><br><br>';

              const calleDomicilio = detalleDomicilio && detalleDomicilio['calle'] ? detalleDomicilio['calle'] : '';
              const localidadDomicilio = detalleDomicilio && detalleDomicilio['localidad'] ? detalleDomicilio['localidad'] : '';
              const provinciaDomicilio = detalleDomicilio && detalleDomicilio['provincia'] ? detalleDomicilio['provincia'] : '';

              if (calleDomicilio && localidadDomicilio && provinciaDomicilio) {
                mensajeCuit += 'Domicilio<br>';
                mensajeCuit += '<strong>' + calleDomicilio + ' Nro: ' + (clienteEncontrado['dirfis_altura'] || '') + ' - ' + localidadDomicilio + '<br>';
                mensajeCuit += provinciaDomicilio + ' - CP: ' + (clienteEncontrado['dirfis_cp'] || '') + '</strong><br><br>';
              } else {
                mensajeCuit += 'Se encontro el cliente, pero no se pudo reconstruir el domicilio fiscal completo para mostrarlo en el aviso.<br><br>';
              }

              mensajeCuit += '¿Desea utilizar estos datos del cliente?';

              sAlertDialog(
                'info',
                '<h3><strong>CLIENTE YA REGISTRADO</strong></h3>',
                mensajeCuit,
                'SI',
                'success',
                'NO',
                'warning',
                function() {
                  sAlertAutoClose("info", "ACTUALIZANDO CAMPOS", "", 1200);

                  aplicarDatosClienteRegistrado(clienteEncontrado)
                  .catch(function(error) {
                    sAlertConfirm('warning', 'NO SE PUDO COMPLETAR TODO EL DOMICILIO', error, 'OK', '#ffc107');
                  });
                },
                undefined,
                undefined,
                undefined,
                undefined,
                undefined
              );
            });

      })
      .catch(function(error){ sAlertConfirm('error', 'SE HA PRODUCIDO EL SIGUIENTE ERROR (636)', error, 'OK', '#dc3545'); });

      return;

      var valueSearch = $(this).val();
      existInDB('../06-funciones_php/funciones.php', 'existInDB', 'clientes', 'cuit', valueSearch)
      .then(function(resultado){  

            if ((resultado['status'] !== false)){        
                   var mensajeCuit = 'CUIT: '+ resultado['cuit']+'<br>';
                   var cp = resultado['dirfis_cp']; 
                   var altura = resultado['dirfis_altura'];                  
                   var razon_social = resultado['razon_social'];
                   mensajeCuit += '<strong>'+resultado['razon_social']+'</strong><br><br>';

                dataByIdCalleLocalidad(
                  '../06-funciones_php/funciones.php', 'dataByIdCalleLocalidad', resultado['dirfis_calle'], resultado['dirfis_localidad']
                ).then(function(resultado){
                   console.log(resultado);    
                   mensajeCuit += 'Domicilio<br>';
                   mensajeCuit += '<strong>'+resultado['calle']+' Nro: '+altura+' - '+resultado['localidad']+'<br>';
                   mensajeCuit += resultado['provincia']+' - CP: '+cp+'</strong><br><br>';   
                   mensajeCuit += '¿Desea utilizar este domicilio?'

                  const functionsArray = [
                  () => sAlertAutoClose("info", "ACTUALIZANDO CAMPOS", html = "", 5000), 
                  () => inputPushValue({"#razon_social": { valor: razon_social, texto: false }, "#altura_visita": { valor: altura, texto: false }, "#cp_visita": { valor: $("#cp_visita").inputmask('unmaskedvalue'), texto: false }}),
                  () => $("#provincia_visita").val(resultado['id_provincia']).trigger("change.select2").trigger("change"), 
                  () => $("#partido_visita").val(resultado['id_partido']).trigger("change.select2").trigger("change"), 
                  () => $("#localidad_visita").val(resultado['id_localidad']).trigger("change.select2").trigger("change"), 
                  () => $("#calle_visita").val(resultado['id_calle']).trigger("change.select2").trigger("change")
                  ];

                  sAlertDialog(
                    'info', 
                    '<h3><strong>CLIENTE YA REGISTRADO</strong></h3>', 
                    mensajeCuit, 
                    'SI', 
                    'success', 
                    'NO', 
                    'warning', 
                    () => someFunctions(functionsArray, 1000), 
                    undefined, undefined, undefined, undefined, undefined);

                }).catch(function(error){sAlertConfirm('error', 'SE HA PRODUCIDO EL SIGUIENTE ERROR (621)', error, 'OK', '#dc3545');});

            }

      }) 
      .catch(function(error){ sAlertConfirm('error', 'SE HA PRODUCIDO EL SIGUIENTE ERROR (636)', error, 'OK', '#dc3545'); });

  
  });

  setTimeout(() => {
  $("#cp_visita").inputmask("remove"); // 🔄 fuerza reset
    $("#cp_visita").inputmask({
      mask: "99999",
      clearIncomplete: false,
      autoUnmask: false,
      removeMaskOnSubmit: false,
      showMaskOnHover: false,
      showMaskOnFocus: true,
      greedy: false
    });
  }, 200);

});


  
$("#v-visita-guardar").click(function(){

      if($('#note_visita').val() !== ''){

            $('.v-materiales-visita').each(function(index, elemento) {
                  // Haz algo con cada elemento

                  if($(elemento).data('id_mate_visi') == 'add'){

                      simpleInsertInDB(
                        '../06-funciones_php/funciones.php',
                        'materiales_visita',
                         ['id_visita', 'id_material', 'material_cantidad'],
                         ['<?php echo arrayPrintValue('', $datos, 'id_previsita', ''); ?>', $(elemento).data('material_id'), $(elemento).data('material_cantidad')],
                         undefined
                      )

                  }

            });


            if($('#note_visita').data('existe') == 'si'){  

                simpleUpdateInDB(
                  '../06-funciones_php/funciones.php',
                  'visita_notas',
                  {nota: $('#note_visita').val()},
                  {columna: 'id_visita', condicion: '=', valorCompara: '<?php echo arrayPrintValue('', $datos, 'id_previsita', ''); ?>'},
                  undefined
                )

            }else{
                 simpleInsertInDB(
                     '../06-funciones_php/funciones.php',
                     'visita_notas',
                     ['id_visita', 'nota'],
                     ['<?php echo arrayPrintValue('', $datos, 'id_previsita', ''); ?>', $('#note_visita').val()],
                     undefined
                 )              
            }


             sAlertAutoClose(
                'success',
                'LOS DATOS DE LA VISITA SE GUARDARON CORRECTAMENTE',
                '<h1></h1>',
                2500, 
                undefined,
                undefined,
                undefined,
                undefined
            );

      }else{
             sAlertAutoClose(
                'error',
                'DEBES COMPLETAR EL ITEM NOTA',
                '<h1></h1>',
                2500, 
                undefined,
                undefined,
                undefined,
                undefined
            );
      }
});



// Generar presupuesto
$("#v-visita-generar-presupuesto").click(function(){

        var htmlPresupuestoBody = <?php echo json_encode($html ?? ''); ?>;
        console.log(htmlPresupuestoBody);
        htmlPresupuestoBody = htmlPresupuestoBody.replace(/v-materiales-visita/g, 'v-materiales-presupuesto');

        var itemsSugeridoPresupuesto = <?php echo json_encode($items_options ?? ''); ?>;


        var htmlPresupuesto  = '<div class="row" id="items_table_presupuesto">';
            htmlPresupuesto += '<div class="col-12 mb-1 mt-1">';
            htmlPresupuesto += '<!-- /.card -->';                      
            htmlPresupuesto += '<div class="card">';
            htmlPresupuesto += '<!-- /.card-header -->';
            htmlPresupuesto += '<div class="card-body p-0">';
            htmlPresupuesto += '<table class="table table-striped">';
            htmlPresupuesto += '<thead>';
            htmlPresupuesto += '<tr>';                         
            htmlPresupuesto += '<th style="width: 10px">#</th>';   
            htmlPresupuesto += '<th >Items</th>';                             
            htmlPresupuesto += '<th class="text-center">Cantidad</th>';                             
            htmlPresupuesto += '<th class="text-center">precio unitario</th>';            
            htmlPresupuesto += '<th class="text-center">% agregado 1 </th>';         
            htmlPresupuesto += '<th class="text-center">% agregado 2 </th>'; 
            htmlPresupuesto += '<th class="text-center">% agregado 3</th>'; 
            htmlPresupuesto += '<th style="width: 10%">Sub Total</th>'; 
            htmlPresupuesto += '<th class="text-center" style="width: 5%">Acción</th>';                                
            htmlPresupuesto += '</tr>';
            htmlPresupuesto += '</thead>'; 
            htmlPresupuesto += '<tbody id="items_table_body">'+htmlPresupuestoBody+'</tbody>'; 
            htmlPresupuesto += '</table>';                                  
            htmlPresupuesto += '</div>';                                    
            htmlPresupuesto += '<!-- /.card-body -->';
            htmlPresupuesto += '</div>';  
            htmlPresupuesto += '<!-- /.card -->';                                                        
            htmlPresupuesto += '</div>'; 
            htmlPresupuesto += '</div>';                                     



    $('.accordion_2').removeClass('card-danger').addClass('card-success');
    $('#visita_items_sugeridos, #visita_buttons').addClass('d-none');
    $('#collapse2').removeClass('show');
    $('#collapse3').addClass('show');
    $('#presupuesto-card-body').append(htmlPresupuesto);



var totalTable = `
<div class="row" id="total_table">
    <div class="col-12 mb-1 mt-1">
        <!-- /.card -->
        <div class="card">
            <!-- /.card-header -->
            <div class="card-body p-0">
                <table class="table table-striped">
                    <tbody id="items_table_body"><tr>
                      <td style="width: 20%"></td>
                      <td style="width: 20%"></td>
                      <td style="width: 10%"></td>
                      <td style="width: 10%"></td>
                      <td style="width: 10%"></td>
                      <td></td>
                      <td><h4 class="text-right">TOTAL:</h4></td>
                      <td><h4 class="text-center"><b>$ XXXXX</b></h4></td>
                      <td></td>
                      <tr>
                    </tbody>
                </table>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
</div>`;

    $('#presupuesto-card-body').append(totalTable);



    var cloneItems = <?php echo json_encode('<div id="visita_items_sugeridos" class="row justify-content-center align-items-center">
    <div class="col-6 form-group mb-1 mt-1">
        <small class="v-visual-edit d-none"><label class="mb-0">Items sugeridos</label></small>
        <div class="input-group mb-0">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-list-check v-requerido-icon"></i></span>
            </div>
            <select class="form-control v-input-requerido select2bs4 v-select2" id="item_select">
                ' . ($items_options ?? '') . '
            </select>
        </div>
    </div>

    <div class="col-1 form-group mb-1 mt-1">
        <small class="v-visual-edit d-none"><label class="mb-0">Cantidad</label></small>
        <div class="input-group mb-0">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-list-ol"></i></span>
            </div>
            <input type="text" class="form-control v-input-requerido" data-inputmask=\'"mask": "99999"\' data-mask="" inputmode="decimal" placeholder="Cantidad" id="item_cantidad" value="' . arrayPrintValue(null, $datos, 'cp_visita', null) . '">
        </div>
    </div>

    <div class="col-1 form-group mb-1 mt-1 pt-1">
        <button id="v-btn-add" class="btn btn-success"><i class="fa-solid fa-circle-plus"></i></button> 
    </div>
</div>'); ?>;


    $('#presupuesto-card-body').append(cloneItems);

    // Itera sobre cada fila con la clase 'v-materiales-presupuesto'
    $('.v-materiales-presupuesto').each(function(index) {
         // Encuentra el cuarto td dentro de la fila actual
         var fourthTd = $(this).find('td:eq(3)');

         // Modifica el contenido del cuarto td
         fourthTd.addClass('text-center').text('$0.00');

        // Encuentra el último td dentro de la fila actual
        var lastTd = $(this).find('td:last');

        // Inserta los nuevos td antes del último td encontrado
        lastTd.before(
            '<td><input type="text" class="form-control" placeholder="0.00"></td>' +
            '<td><input type="text" class="form-control" placeholder="0.00"></td>' +
            '<td><input type="text" class="form-control" placeholder="0.00"></td>' +
            '<td><input type="text" class="form-control" placeholder="0.00"></td>'
        );
    });




});



$(function () {

  $('[data-toggle="tooltip"]').tooltip();

  bsCustomFileInput.init();

  $('[data-mask]').inputmask({ clearIncomplete: false });

  $('#cp_visita').inputmask({
  mask: "99999",
  clearIncomplete: false,      // 🔒 no borrar en blur
  showMaskOnHover: false,
  showMaskOnFocus: true,
  greedy: false,
  autoUnmask: false,           // 🔒 evita que borre valor al salir
  removeMaskOnSubmit: false    // 🔒 asegura que se mantenga el valor
});


  $.validator.setDefaults({
    submitHandler: function () {
console.log('1865 | previsitaGuardar()');      
      previsitaGuardar();
    }
  });

  // $('#currentForm').validate({
  //   ignore: "#hora_visita"
  // });

  $('#currentForm').validate({

    rules: {
      fecha_visita: {required: true}, 
      razon_social: {required: true}, 
      hora_visita: {required: true},
      tel_contacto_obra: {required: true},     
      contacto_obra: {required: true}
    },
    messages: {
      fecha_visita: {required: "Debe completar este campo"},
      razon_social: {required: "Debe completar este campo"},
      hora_visita:  {required: "Debe completar este campo"},             
      tel_contacto_obra: {required: "Debe completar este campo"},
      contacto_obra: {required: "Debe completar este campo"}
    },
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group').append(error);
    },
    highlight: function (element, errorClass, validClass) {
      $(element).addClass('is-invalid');
    },
    unhighlight: function (element, errorClass, validClass) {
      $(element).removeClass('is-invalid');
    }
  });
  // end validation

  $("#currentForm").on("submit", function(e) {
    const cp = $("#cp_visita").inputmask("unmaskedvalue");

    if (!cp || cp.length < 4 || cp.length > 5) {
      e.preventDefault();
      $("#cp_visita").addClass("is-invalid");
      toastr.error("El código postal debe tener 4 o 5 dígitos");
    } else {
      $("#cp_visita").removeClass("is-invalid");
    }
  });


  
    //Initialize Select2 Elements
    $('.select2').select2({
      language: "es"
    })

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4',
      language: "es"
    })

    $(".v-disabled-select2").next(".select2").find(".select2-selection").addClass("v-disabled");



    //Datemask dd/mm/yyyy
    $('#datemask').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' })
    //Datemask2 mm/dd/yyyy
    $('#datemask2').inputmask('mm/dd/yyyy', { 'placeholder': 'mm/dd/yyyy' })
    //Money Euro
    $('[data-mask]').inputmask({ clearIncomplete: false });

    //Date picker
    $('#reservationdate').datetimepicker({
        format: 'L'
    });

    //Date and time picker
    $('#reservationdatetime').datetimepicker({ icons: { time: 'far fa-clock' } });

    //Date range picker
    $('#reservation').daterangepicker()
    //Date range picker with time picker
    $('#reservationtime').daterangepicker({
      timePicker: true,
      timePickerIncrement: 30,
      locale: {
        format: 'MM/DD/YYYY hh:mm A'
      }
    })
    //Date range as a button
    $('#daterange-btn').daterangepicker(
      {
        ranges   : {
          'Today'       : [moment(), moment()],
          'Yesterday'   : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
          'Last 30 Days': [moment().subtract(29, 'days'), moment()],
          'This Month'  : [moment().startOf('month'), moment().endOf('month')],
          'Last Month'  : [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        startDate: moment().subtract(29, 'days'),
        endDate  : moment()
      },
      function (start, end) {
        $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'))
      }
    )

    //Timepicker
    $('#timepicker').datetimepicker({
      format: 'LT'
    })

    //Bootstrap Duallistbox
    $('.duallistbox').bootstrapDualListbox()

    //Colorpicker
    $('.my-colorpicker1').colorpicker()
    //color picker with addon
    $('.my-colorpicker2').colorpicker()

    $('.my-colorpicker2').on('colorpickerChange', function(event) {
      $('.my-colorpicker2 .fa-square').css('color', event.color.toString());
    })
}) // end functions
 
</script>

<?php
// Garantizá que sea array; si no existe o viene null, dejalo en []
$tareas_js = is_array($tareas_visitadas ?? null) ? array_values($tareas_visitadas) : [];
?>
<script>
  // JSON limpio y seguro para JS
  window.tareasVisitadas = <?= json_encode(
    $tareas_js,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES |
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
  ); ?>;

  // Copia segura para usar en tu código
  window.SEGUIMIENTO_BLOQUEO_COMERCIAL = <?= json_encode([
    'bloqueado' => !empty($bloqueoEdicionComercial['bloqueado']),
    'estado' => (string)($bloqueoEdicionComercial['estado'] ?? ''),
    'estado_label' => (string)($bloqueoEdicionComercial['estado_label'] ?? ''),
    'mensaje' => (string)($bloqueoEdicionComercial['mensaje'] ?? ''),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

  window.SEGUIMIENTO_CLIENTES_SUGERIDOS = <?= json_encode(
    $clientesSugeridosMap,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES |
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
  ); ?>;

  window.obtenerBloqueoEdicionComercialSeguimiento = function () {
    return window.SEGUIMIENTO_BLOQUEO_COMERCIAL || {};
  };

  window.mensajeBloqueoEdicionComercialSeguimiento = function () {
    const bloqueo = window.obtenerBloqueoEdicionComercialSeguimiento();
    return String(bloqueo && bloqueo.mensaje ? bloqueo.mensaje : 'La edicion del seguimiento esta bloqueada por el estado comercial actual.');
  };

  const tareas = Array.isArray(window.tareasVisitadas) ? window.tareasVisitadas : [];
</script>



</script>
<script src="../07-funciones_js/accordionPresupuesto.js"></script>
<script src="../07-funciones_js/accordionVisita.js"></script>
<!-- funcion para saber si es un alta, visualización, edición // formatea la vista -->
<script src="../07-funciones_js/abm_detect.js"></script>
<!-- funcion para traer los partidos de una provincia -->
<script src="../07-funciones_js/partidosByProvincia.js"></script>
<!-- funcion para traer las calles de una provincia -->
<script src="../07-funciones_js/localidadesyCallesByPartido.js"></script>
<!-- funcion para detectar input completos o incompletos -->
<script src="../07-funciones_js/inputEmptyDetect.js"></script>
<!-- funcion rellenas select con valores traidos por ajax -->
<script src="../07-funciones_js/optionSelect.js"></script>
<!-- funcion para mostrar documentios -->
<script src="../07-funciones_js/muestraDocumento.js"></script>
<!-- funcion calcular edad -->
<script src="../07-funciones_js/calculaEdad.js"></script>
<!-- Guarda usuarios en la base -->
<script src="../07-funciones_js/presupuestosAcciones.js"></script>
<script src="../07-funciones_js/previsitaDocumentos.js"></script>
<!-- Guarda usuarios en la base -->
<script src="../07-funciones_js/previsitaGuardar.js"></script>

<!-- funciones js -->
<script src="../07-funciones_js/scripts_list.js"></script>

<template id="tpl-accordion-presupuesto">
  <div class="accordion" id="accordionPresupuesto">
    <div class="card card-success accordion_3">
      <div class="card-header" id="headingPresupuesto">
        <h2 class="mb-0 d-flex align-items-center presupuesto-accordion-header">
          <button class="btn btn-link text-left text-white p-0 card-title presupuesto-accordion-toggle" 
                  type="button" 
                  data-toggle="collapse" 
                  data-target="#collapsePresupuesto" 
                  aria-expanded="true" 
                  aria-controls="collapsePresupuesto">
            Presupuesto
          </button>
          <span class="card-title text-right presupuesto-accordion-intervino">
            <strong>Intervino: </strong>
            <span
              class="intervino-presupuesto-ultimo"
              tabindex="0"
              role="button"
              data-toggle="popover"
              data-html="true"
              data-placement="bottom"
            >
              Sin intervenciones
            </span>
          </span>
        </h2>
      </div>
      <div id="collapsePresupuesto" class="collapse show" aria-labelledby="headingPresupuesto" data-parent="#accordionPresupuesto">
        <div class="card-body" id="presupuesto-card-body">
          <div id="contenedorPresupuestoGenerado" class="mt-3">
            <div class="alert alert-success text-center mb-3">
                <i class="fa fa-check-circle mr-2"></i> ¡Presupuesto generado exitosamente!<br>
                Aquí aparecerá el detalle una vez implementada la lógica completa.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<!-- Aquí, justo antes de tus <script> que inicializan el popover: -->
<div id="popover-content-visita" style="display:none">
  <?= $popoverIntervinientes /* contiene la tabla completa */ ?>
</div>
<script>window.URL_GUARDAR_PRESUPUESTO = '../03-controller/presupuestos_guardar.php';</script>

<!-- =========================
  MODAL: Traer tarea archivada
  Colocar antes de </body>
========================== -->
<div class="modal fade" id="modalTraerTareaArchivada" tabindex="-1" role="dialog"
     aria-labelledby="modalTraerTareaArchivadaLabel" aria-hidden="true"
     data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title" id="modalTraerTareaArchivadaLabel">
          <i class="fas fa-archive mr-2"></i> Traer tarea archivada
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">

        <!-- Filtro simple (opcional; la lógica se agrega luego) -->
        <div class="form-row mb-3">
          <div class="col-md-6">
            <label for="filtroTareasArchivadas" class="mb-1">Buscar</label>
            <input type="text" class="form-control" id="filtroTareasArchivadas"
                   placeholder="Escribe para filtrar por nombre de plantilla…">
          </div>
        </div>

        <!-- Tabla de plantillas -->
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0" id="tablaTareasArchivadas">
              <thead class="thead-light">
                <tr>
                  <th style="min-width: 280px;">Nombre de plantilla</th>
                  <th style="min-width: 280px;">Nombre original</th>
                  <th class="text-nowrap" style="width: 180px;">Creada</th>
                  <th class="text-center" style="width: 110px;">Acción</th>
                </tr>
              </thead>
              <tbody>
                <!-- Se poblará por JS -->
              </tbody>
            </table>
          </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

</body>
</html>
