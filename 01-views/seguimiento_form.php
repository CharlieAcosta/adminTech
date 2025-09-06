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
include_once '../06-funciones_php/ordenar_array.php'; //ordena array por el indice indicado
include_once '../06-funciones_php/optionsByIndex.php'; //ordena array por el indice indicado
include_once '../06-funciones_php/funciones.php'; //funciones últiles


$id=""; $visualiza=""; $pdf=""; $visualiza_prevista ="";

if(isset($_GET['id']) && isset($_GET['acci'])){
  $id = $_GET['id'];
  if($_GET['acci'] == "v"){$visualiza="on";} // pone los campos en modo visualización
  if($_GET['acci'] == "pdf"){$pdf="on";}

  $datos = modGetPresupuestoById($id, 'php');
  $datos = $datos[0];

  //dd($datos);

  $intervino_previsita_agente = db_select_with_filters_V2(
    'usuarios',                // tabla
    ['id_usuario'],            // columnas a filtrar
    ['='],                     // comparaciones
    [$datos['log_usuario_id']],              // valores
    [],                        // ordenamiento (vacío)
    'php'                      // devuelve array en PHP
  );

  $intervino_previsita_apenom = $intervino_previsita_agente[0]['apellidos']." ".$intervino_previsita_agente[0]['nombres']." | ".strToDateFormat($datos['log_edicion'], 'd/m/Y H:i:s');

  $tareas_visitadas = [];

  if ($datos['estado_visita'] == 'Ejecutada' && isset($datos['id_previsita'])) {
      $id_visita = $datos['id_previsita'];

        $tareas_visitadas = modGetTareasByVisitaId($id_visita, 'php');
  }

//echo utf8_encode( $usuario_datos['0']['provincia'] ); die();

    $previsita_card = 'card-success'; $previsita_buttons = 'd-flex'; 
    $visita_card = 'card-danger'; $items_options = "";
    $presupuesto_card = 'card-danger'; 
    $orden_compra_card = 'card-danger';

    if($datos['estado_visita'] !== 'Ejecutada'){$previsita_show = "show";} else {$previsita_show = "";}
    
    if($datos['estado_visita'] == 'Ejecutada'){
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

    }else{
        $visita_show = ""; //muestra el accordion de visita cerrado
    } 

}else{$datos = array();

  $previsita_card = 'card-danger'; $previsita_show = "show"; $previsita_buttons = 'd-flex';
  $visita_card = 'card-danger'; $visita_show = ""; 
  $presupuesto_card = 'card-danger'; $presupuesto_show = "";
  $orden_compra_card = 'card-danger'; $orden_compra_show = "";

}



$provincias = getAllProvincias();
$provinciasSelect = ""; //para el select de provincias
foreach ($provincias as $key => $value) {
   if(!isset($cliente_datos)){ 
      $provinciasSelect .= '<option value="'.utf8_encode($value['id_provincia']).'">'.utf8_encode($value['provincia']).'</option>';
   }else{
    if($cliente_datos['0']['dirfis_provincia'] != $value['id_provincia']){
      $provinciasSelect .= '<option value="'.utf8_encode($value['id_provincia']).'">'.utf8_encode($value['provincia']).'</option>';
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
        $partidosSelect .= '<option value="'.utf8_encode($value['id_partido']).'">'.utf8_encode($value['partido']).'</option>';
      }
    }
}

if(isset($cliente_datos['0']['id_cliente']) && $visualiza == "" && !is_null($cliente_datos['0']['dirfis_partido']) && $cliente_datos['0']['dirfis_partido'] !== ''){
  $localidades = getLocalidadesByPartido($cliente_datos['0']['dirfis_partido'], 'php');
  $localidadesSelect = ""; //para el select de localidades
  foreach ($localidades as $key => $value) {
    if($value['id_localidad'] != $cliente_datos['0']['dirfis_localidad']){
      $localidadesSelect .= '<option value="'.utf8_encode($value['id_localidad']).'">'.utf8_encode($value['localidad']).'</option>';
    }
  }
}

if(isset($cliente_datos['0']['id_cliente']) && $visualiza == "" && !is_null($cliente_datos['0']['dirfis_partido']) && $cliente_datos['0']['dirfis_partido'] !== ''){
  $calles = getCallesByPartido($cliente_datos['0']['dirfis_partido'], 'php');
  $callesSelect = ""; //para el select de calles
  foreach ($calles as $key => $value) {
    if($value['id_calle'] != $cliente_datos['0']['dirfis_calle']){
      $callesSelect .= '<option value="'.utf8_encode($value['id_calle']).'">'.utf8_encode($value['calle']).'</option>';
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

// START PRESUPUESTO
$presupuestoGenerado = false; // Cambia a true para probar el otro caso
if ($presupuestoGenerado) {
  $visita_show = '';
  $presupuesto_show = 'show';
} else {
  $visita_show = 'show';
  $presupuesto_show = '';
}

// END PRESUPUESTO
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

?>
<script>
  let presupuestoGenerado = <?php echo $presupuestoGenerado ? 'true' : 'false'; ?>;
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
  <script src='../05-plugins/pdfmake/pdfmake.min.js'></script>
  <script src='../05-plugins/pdfmake/vfs_fonts.js'></script>
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
<!-- start accordion -->
<div class="accordion" id="accordionExample">
  <div class="card <?php echo $previsita_card; ?> accordion 1">
    <div class="card-header" id="headingOne">
      <h2 class="mb-0 d-flex justify-content-between align-items-center">
        <button class="col-9 btn btn-link btn-block text-left text-white p-0 card-title " type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
        Pre-visita <?php 
          echo arrayPrintValue('Nro: <strong>', $datos, 'id_previsita', '</strong>');
          if (!empty($datos['razon_social'])) {
            echo ' | ' . strtoupper($datos['razon_social']);
          }
        ?>
        </button>
        <span class="col-3 card-title text-right"><?php echo "<strong>Intervino: </strong>".$intervino_previsita_apenom; ?></span>
      </h2>
    </div>

    <!-- start collapse accordion 1 -->
    <div id="collapseOne" class="collapse <?php echo $previsita_show; ?>" aria-labelledby="headingOne" data-parent="#accordionExample">
          
          <!-- start card body accordion 1-->
          <div class="card-body">
                <input type="hidden" class="v-id" id="id_previsita" name="id_previsita" data-visualiza="<?php echo $visualiza; ?>" value="<?php echo arrayPrintValue(null, $datos, 'id_previsita', null); ?>">
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
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-city v-requerido-icon"></i></span>
                        </div>
                        <input type="text" class="form-control v-input-requerido <?php echo $visualiza_prevista !== "" ? 'v-disabled' : ''; ?>" placeholder="Razón Social" id="razon_social" name="razon_social" value="<?php echo arrayPrintValue(null, $datos, 'razon_social', null); ?>">
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
                              <option value="<?php if(isset($datos['provincia_visita'])){echo utf8_encode($datos['provincia_visita']);}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($datos['provincianom'])){echo utf8_encode($datos['provincianom']);}else{echo "Provincia";} ?></option>
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

                <div class="input-group col-6 form-group mb-1 mt-2">
                    <small class="input-group v-visual-edit d-none"><label class="mb-0">Documento<strong><small class="text-primary"></small></strong></label></small>
                    <div class="input-group-prepend">
                         <span class="input-group-text"><i class="fas fa-file-invoice v-documento-link" data-tipo="previsita"></i></span>
                    </div>
                    <div class="custom-file <?php echo '';//$previsita_v_disabled; ?>">
                        <input type="file" class="custom-file-input v-archivos-admitidos-pdf" name="doc_previsita" id="doc_previsita" data-browse="Buscar" value="">
                        <label class="custom-file-label <?php '';//$previsita_v_disabled; ?>" for="exampleInputFile"><?php echo arrayPrintValue(null, $datos, 'doc_previsita', null, 'Seleccionar documento'); ?></label>
                    </div>
                </div>

                <div class="row <?php echo $previsita_buttons;?> text-center justify-content-center pr-1 mt-2">
                  <button type="submit" class="col-1 btn btn-primary btn-block m-2 v-alta-edit d-none" data-accion="guardar"><i class="fa fa-plus-circle"></i> Guardar</button>
                  <button type="button" class="col-1 btn btn-warning btn-block m-2 v-alta-edit d-none v-accion-cancelar" data-accion="cancelar"><i class="fa fa-ban"></i> Cancelar</button>
                </div>

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
<?php if ($presupuestoGenerado): ?>
  <div class="accordion" id="accordionPresupuesto">
    <div class="card <?php echo $presupuesto_card; ?> accordion_3">
      <div class="card-header" id="headingPresupuesto">
        <h2 class="mb-0 d-flex justify-content-between align-items-center">
          <button class="btn btn-link btn-block text-left text-white p-0 card-title" 
                  type="button" 
                  data-toggle="collapse" 
                  data-target="#collapsePresupuesto" 
                  aria-expanded="<?php echo $presupuesto_show === 'show' ? 'true' : 'false'; ?>" 
                  aria-controls="collapsePresupuesto">
            Presupuesto
          </button>
          <small>
            <span>
              <i class="fas fa-edit fa-xs v-icon-pointer v-icon-accion" 
                data-accion="editar-presupuesto" 
                data-toggle="tooltip" 
                data-placement="top" 
                title="Editar presupuesto"
                style="color: #ffffff;">
              </i>
            </span>
          </small>
        </h2>
      </div>
      <div id="collapsePresupuesto" class="collapse <?php echo $presupuesto_show; ?>" aria-labelledby="headingPresupuesto" data-parent="#accordionPresupuesto">
        <div class="card-body" id="presupuesto-card-body">
          <!-- Aquí se insertará el contenido dinámico generado -->
          <div id="contenedorPresupuestoGenerado" class="mt-3">
            <?php if($presupuestoGenerado): ?>
              <div class="alert alert-info text-center mb-3">
                  Aquí aparecerá el presupuesto generado con los datos traídos del backend.
              </div>
            <?php endif; ?>
            </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
<!-- /accordion presupuesto -->


<!-- start accordion 4 - Orden de compra -->
<div class="accordion" id="accordionExample4">
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
<div class="accordion" id="accordionExample5">
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
<div class="accordion" id="accordionExample6">
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

<script>
$(function(){
  // inicializa sobre el mismo selector que ya usas
  $('#headingVisita [data-toggle="popover"]').popover({
    trigger: 'hover focus',
    container: 'body',
    html:     true,
    sanitize: false,  // desactiva saneamiento para que no borre <table>
    // inyecta un template con clase propia
    template: `
      <div class="popover popover-wide" role="tooltip">
        <div class="arrow"></div>
        <h3 class="popover-header"></h3>
        <div class="popover-body"></div>
      </div>
    `,
    content: function(){
      return $('#popover-content-visita').html();
    }
  });
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

    // Obtener la fecha actual
    var fechaActual = new Date();

    // Obtener la referencia al campo de fecha
    var campoFecha = document.getElementById('fecha_visita');

    // Establecer el atributo 'min' del campo de fecha a la fecha actual
    campoFecha.min = fechaActual.toISOString().split('T')[0];


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


  $(document).on('change',"#cuit",function(){

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
      presupuestoGuardar();
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

<!-- custom functions -->
<script>
// Paso 1: Serializamos el arreglo PHP a JSON seguro para JS
const tareasVisitadas = <?php 
    echo json_encode(
        $tareas_visitadas, 
        JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT
    ); 
?>;
</script>
<script src="../07-funciones_js/accordionVisita.js"></script>
<script src="../07-funciones_js/accordionPresupuesto.js"></script>
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
<!-- Guarda usuarios en la base -->
<script src="../07-funciones_js/presupuestoGuardar.js"></script>

<!-- funciones js -->
<script src="../07-funciones_js/scripts_list.js"></script>

<template id="tpl-accordion-presupuesto">
  <div class="accordion" id="accordionPresupuesto">
    <div class="card card-success accordion_3">
      <div class="card-header" id="headingPresupuesto">
        <h2 class="mb-0 d-flex justify-content-between align-items-center">
          <button class="btn btn-link btn-block text-left text-white p-0 card-title" 
                  type="button" 
                  data-toggle="collapse" 
                  data-target="#collapsePresupuesto" 
                  aria-expanded="true" 
                  aria-controls="collapsePresupuesto">
            Presupuesto
          </button>
          </small>
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

</body>
</html>
