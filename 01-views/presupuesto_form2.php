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
include_once '../06-funciones_php/ordenar_array.php'; //ordena array por el indice indicado
include_once '../06-funciones_php/optionsByIndex.php'; //ordena array por el indice indicado
include_once '../06-funciones_php/funciones.php'; //funciones últiles


$id=""; $visualiza=""; $pdf="";

if(isset($_GET['id']) && isset($_GET['acci'])){
  $id = $_GET['id'];
  if($_GET['acci'] == "v"){$visualiza="on";}
  if($_GET['acci'] == "pdf"){$pdf="on";}

  $datos = modGetPresupuestoById($id, 'php');
  $datos = $datos[0];

//var_dump($datos); die();
//echo utf8_encode( $usuario_datos['0']['provincia'] ); die();

    $previsita_card = 'card-success'; $previsita_buttons = 'd-flex'; 
    $visita_card = 'card-danger'; $items_options = "";
    $presupuesto_card = 'card-danger'; 
    $orden_compra_card = 'card-danger';

    if($datos['estado_visita'] !== 'Ejecutada'){$previsita_show = "show";} else {$previsita_show = "";}
    
    if($datos['estado_visita'] == 'Ejecutada')
      {
        $visita_show = "show"; //muestra el accordion de visita abierto
        $previsita_buttons = 'd-none';
        $items_db = SelectAllDB('materiales', 'estado_material', '=', "'Activo'", $callType = 'php');
        $items_options = arrayToOptions($items_db, 'Seleccione un ítem', 'id_material', 'id_material', '|', 'producto', 'unidad_venta', 'rendimiento', 'unidad_rendimiento', null, null);
      } 
    else
      {$visita_show = ""; //muestra el accordion de visita cerrado
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

?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title class="v-alta d-none">ADMINTECH | Alta de solicitud de presupuesto</title>
  <title class="v-visual d-none">ADMINTECH | Visualización de solicitud de presupuesto</title>
  <title class="v-edit d-none">ADMINTECH | Edición de solicitud de presupuesto</title>

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
            <h1><strong class="v-alta d-none">Alta de solicitud de presupuesto</strong><strong class="v-visual d-none">Visualización de solicitud de presupuesto</strong><strong class="v-edit d-none">Edición de solicitud de presupuesto</strong></h1>
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
        <button class="btn btn-link btn-block text-left text-white p-0 card-title " type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
          Pre-visita <?php echo arrayPrintValue('Nro: <strong>', $datos, 'id_previsita', '</strong>'); ?>
        </button>
        <small><span><i class="fas fa-print fa-xs v-icon-pointer v-icon-accion" data-accion="pdf-previsita" data-toggle="tooltip" data-placement="top" title="Imprimir" style="color: #ffffff;"></i></span></small>
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
                          <input type="date" class="form-control v-input-requerido" inputmode="decimal" placeholder="Fecha de visita" id="fecha_visita" name="fecha_visita" value="<?php echo arrayPrintValue(null, $datos, 'fecha_visita', null); ?>" min=""> 
                      </div>
                  </div>

                  <div class="col-1 form-group mb-0 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Hora Visita</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-clock v-requerido-icon"></i></span>
                        </div>
                          <input type="time" class="form-control" inputmode="decimal" placeholder="Hora" id="hora_visita" name="hora_visita" 
                          value="<?php echo arrayPrintValue(null, $datos, 'hora_visita', null); ?>">
                      </div>
                  </div>

                  <div class="col-2 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Estado</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-sliders-h v-requerido-icon"></i></span>
                        </div>
                        <select class="form-control v-input-requerido" id="estado_visita" name="estado_visita">
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
                          <input type="text" class="form-control v-input-requerido" data-inputmask='"mask": "99-99999999-9", "clearIncomplete": "true"' data-mask="" inputmode="decimal" data-cuit="<?php echo arrayPrintValue(null, $datos, 'cuit', null); ?>" placeholder="CUIT" id="cuit" name="cuit" value="<?php echo arrayPrintValue(null, $datos, 'cuit', null); ?>">

                      </div>
                  </div>

                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Razón Social</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-city v-requerido-icon"></i></span>
                        </div>
                        <input type="text" class="form-control v-input-requerido" placeholder="Razón Social" id="razon_social" name="razon_social" value="<?php echo arrayPrintValue(null, $datos, 'razon_social', null); ?>">
                      </div>
                  </div>

                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Contacto en obra</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-user-tie v-requerido-icon"></i></span>
                        </div>
                        <input type="text" class="form-control v-input-requerido" placeholder="Contacto en obra" id="contacto_obra" name="contacto_obra" value="<?php echo arrayPrintValue(null, $datos, 'contacto_obra', null); ?>">
                      </div>
                  </div>
                  <div class="col-2 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Teléfono</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-phone v-requerido-icon"></i></span>
                        </div>
                          <input type="text" class="form-control v-input-requerido" data-inputmask='"mask": "(9{1,5})(99999999)", "clearIncomplete": "true"' data-mask="" inputmode="decimal" placeholder="Teléfono" id="tel_contacto_obra" name="tel_contacto_obra" value="<?php echo arrayPrintValue(null, $datos, 'tel_contacto_obra', null); ?>">
                      </div>
                  </div>

                  <div class="col-2 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Email</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        </div>
                        <input type="text" class="form-control v-input-requerido" placeholder="Email" data-inputmask='"mask": "[{1,20}|_|.]@*{1,20}.*{1,3}[.*{1,3}]", "clearIncomplete": "true"' autocomplete="rutjfkde" id="email_contacto_obra" name="email_contacto_obra" value="<?php echo arrayPrintValue(null, $datos, 'email_contacto_obra', null); ?>">
                      </div>
                  </div>

                  <div class="col-3 form-group mb-1 mt-1">
                        <small class="v-visual-edit d-none"><label class="mb-0">Provincia</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                            </div>
                            <select class="form-control select2bs4 v-select2 provincia" id="provincia_visita" name="provincia_visita">
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
                            <select class="form-control select2bs4 v-select2 partido" id="partido_visita" name="partido_visita">
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
                            <select class="form-control select2bs4 v-select2 localidad" id="localidad_visita" name="localidad_visita">
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
                            <select class="form-control select2bs4 v-select2 calle" id="calle_visita" name="calle_visita" data-tags="true">
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
                              <input type="text" class="form-control " data-inputmask='"mask": "9{1,5}", "clearIncomplete": "true" ' data-mask="" inputmode="decimal" placeholder="Altura / Km" id="altura_visita" name="altura_visita" value="<?php echo arrayPrintValue(null, $datos, 'altura_visita', null); ?>">
                          </div>
                  </div>

                  <div class="col-1 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">CP</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-mail-bulk"></i></span>
                            </div>
                              <input type="text" class="form-control " data-inputmask='"mask": "99999", "clearIncomplete": "true" ' data-mask="" inputmode="decimal" placeholder="CP" id="cp_visita" name="cp_visita" value="<?php echo arrayPrintValue(null, $datos, 'cp_visita', null); ?>">
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
                                                            <input type="checkbox" id="induccion_visita" name="induccion_visita" value="s" <?php echo arrayPrintValue(null, $datos, 'induccion_visita', null) == 's' ? 'checked' : '' ; ?>>
                                                            <label for="induccion_visita">Inducción</label>
                                                          </div>

                                                          <div class="icheck-success d-inline mr-4">
                                                            <input type="checkbox" id="chaleco_visita" name="chaleco_visita" value="s" <?php echo arrayPrintValue(null, $datos, 'chaleco_visita', null) == 's' ? 'checked' : '' ; ?>>
                                                            <label for="chaleco_visita">Chaleco</label>
                                                          </div>

                                                          <div class="icheck-success d-inline mr-4">
                                                            <input type="checkbox" id="casco_visita" name="casco_visita" value="s" <?php echo arrayPrintValue(null, $datos, 'casco_visita', null) == 's' ? 'checked' : '' ; ?>>
                                                            <label for="casco_visita">Casco</label>
                                                          </div>

                                                          <div class="icheck-success d-inline mr-4">
                                                            <input type="checkbox" id="escalera_visita" name="escalera_visita" value="s" <?php echo arrayPrintValue(null, $datos, 'escalera_visita', null) == 's' ? 'checked' : '' ; ?>>
                                                            <label for="escalera_visita">Escalera</label>
                                                          </div>

                                                          <div class="icheck-success d-inline mr-4">
                                                            <input type="checkbox" id="arnes_visita" name="arnes_visita" value="s" <?php echo arrayPrintValue(null, $datos, 'arnes_visita', null) == 's' ? 'checked' : '' ; ?>>
                                                            <label for="arnes_visita">Arnes</label>
                                                          </div>
                                                          <div class="icheck-success d-inline mr-4">
                                                            <input type="checkbox" id="soga_visita" name="soga_visita" value="s" <?php echo arrayPrintValue(null, $datos, 'soga_visita', null) == 's' ? 'checked' : '' ; ?>>
                                                            <label for="soga_visita">Soga</label>
                                                          </div>

                                                          <div class="icheck-success d-inline mr-4">
                                                            <input type="checkbox" id="gafas_visita" name="gafas_visita" value="s" <?php echo arrayPrintValue(null, $datos, 'gafas_visita', null) == 's' ? 'checked' : '' ; ?>>
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
                                                          <input type="text" class="form-control v-input-requerido" placeholder="Otros" id="otros_visita" name="otros_visita" value="<?php echo arrayPrintValue(null, $datos, 'otros_visita', null); ?>">
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
                        <small class="v-visual-edit d-none"><label class="mb-0">Nota</label></small>
                        <div class="input-group mb-0">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                          </div>
                          <textarea type="text" rows="5" class="form-control" placeholder="Nota" id="nota_visita" name="nota_visita"><?php echo arrayPrintValue(null, $datos, 'nota_visita', null); ?></textarea>
                        </div>
                      </div>
                </div>                

                <div class="input-group col-6 form-group mb-1 mt-2">
                    <small class="input-group v-visual-edit d-none"><label class="mb-0">Documento<strong><small class="text-primary"></small></strong></label></small>
                    <div class="input-group-prepend">
                         <span class="input-group-text"><i class="fas fa-file-invoice v-documento-link" data-tipo="previsita"></i></span>
                    </div>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input v-archivos-admitidos-pdf" name="doc_visita" id="doc_visita" data-browse="Buscar" value="">
                        <label class="custom-file-label" for="exampleInputFile"><?php echo arrayPrintValue(null, $datos, 'doc_visita', null, 'Seleccionar documento'); ?></label>
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




<!-- start accordion visita -->
<div class="accordion" id="accordionExample2">
  <div class="card <?php echo $visita_card; ?> accordion 2">
    <div class="card-header" id="heading2">
      <h2 class="mb-0">
        <button class="btn btn-link btn-block text-left text-white p-0 card-title" type="button" data-toggle="collapse" data-target="#collapse2" aria-expanded="true" aria-controls="collapse2">
          Visita<?php echo isset($datos['0']['id_previsita']) ? ' N°:<strong class="text-lg"> ' . $datos['0']['id_previsita'].'</strong>' : ''; ?>
        </button>
      </h2>
    </div>

    <!-- start collapse accordion 2 -->
    <div id="collapse2" class="collapse <?php echo $visita_show; ?>" aria-labelledby="heading2" data-parent="#accordionExample2">

          <!-- start card body accordion 3-->
          <div id="visita_tareas" class="card-body">
              <div id="tarea_1" class="mt-3 v-tarea">
                <!-- tarea  -->
                <div class="d-flex justify-content-between align-items-center">
                    <small class="v-visual-edit d-none v-label-tarea"><label class="mb-0">Tarea Nro: 1</label></small>
                    <small class="text-danger"><b>Quitar tarea </b><i class="fa-solid fa-circle-xmark text-danger v-icon-delete-tarea v-icon-pointer"  data-tarea="tarea_1"></i></small>
                </div>
                <div class="border p-3 rounded shadow-sm">
                    <div class="row">
                          <div class="col-12 form-group mb-1 mt-1">
                            <div class="input-group mb-0">
                              <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                              </div>
                              <textarea type="text" rows="2" class="form-control" placeholder="Tareas a realizar" id="nota_visita" name="nota_visita"></textarea>
                            </div>
                          </div>
                    </div>

                    <div class="row d-none" id="items_table_1">
                          <div class="col-12 mb-1 mt-1">
                              <!-- /.card -->
                              <div class="card">
                                <!-- /.card-header -->
                                <div class="card-body p-0">
                                  <table class="table table-striped">
                                    <thead style="height: 10px;">
                                      <tr>
                                        <th style="width: 10px">#</th>
                                        <th>Items</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-center" style="width: 5%">Acción</th>
                                      </tr>
                                    </thead>
                                    <tbody id="items_table_body_1"></tbody>
                                  </table>
                                </div>
                                <!-- /.card-body -->
                              </div>
                              <!-- /.card -->
                          </div>
                    </div>

                    <div class="row justify-content-center align-items-center">
                          <div class="col-6 form-group mb-1 mt-1">
                              <small class="v-visual-edit d-none"><label class="mb-0">Items sugeridos</label></small>
                              <div class="input-group mb-0">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-list-check v-requerido-icon"></i></span>
                                </div>
                                <select class="form-control v-input-requerido select2bs4 v-select2" id="item_select_1">
                                <?php echo $items_options;?>  
                                </select>
                              </div>
                          </div>

                          <div class="col-1 form-group mb-1 mt-1">
                                  <small class="v-visual-edit d-none"><label class="mb-0">Cantidad</label></small>
                                  <div class="input-group mb-0">
                                    <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="fas fa-list-ol"></i></span>
                                    </div>
                                      <input type="text" class="form-control v-input-requerido" data-inputmask='"mask": "99999"' data-mask="" inputmode="decimal" placeholder="Cantidad" id="item_cantidad" value="<?php echo arrayPrintValue(null, $datos, 'cp_visita', null); ?>">
                                  </div>
                          </div>

                          <div class="col-1 form-group mb-1 mt-4 pt-1">
                             <button class="btn btn-success v-btn-add" data-id="item_select_1" data-items_table_body="items_table_body_1"><i class="fa-solid fa-circle-plus"></i></button> 
                          </div>
                    </div>
                </div>    
                   <div id="visita_buttons" class="row text-center justify-content-center pr-1 mt-2 d-none">
                    <button type="button" class="col-2 btn btn-success btn-block m-2 v-alta-edit d-none"><i class="fa-solid fa-cloud-arrow-up"></i> Guardar</button>
                    <button type="button" class="col-2 btn btn-primary btn-block m-2 v-alta-edit d-none v-add-tarea"><i class="fa-solid fa-circle-plus"></i> Agregar Tarea</button>
                    <button type="button" class="col-2 btn btn-warning btn-block m-2 v-alta-edit d-none v-accion-cancelar"><i class="fa-solid fa-calculator"></i> Generar Presupuesto</button>
                    <button type="button" class="col-2 btn btn-danger btn-block m-2 v-alta-edit d-none v-accion-cancelar"><i class="fa-solid fa-circle-xmark v-icon-pointer" ></i> Cancelar</button> 
                   </div>
                <!-- tarea  -->
              <div>  
          </div>

    </div>
          <!-- end card accordion 3 -->

    </div>
    <!-- end collapse accordion 2-->

  </div>
  <!-- end card accordion 2 -->
</div>
<!-- end accordion visita -->


<!-- start accordion 3 -->
<div class="accordion" id="accordionExample3">
  <div class="card <?php echo $presupuesto_card; ?> accordion 3">
    <div class="card-header" id="heading3">
      <h2 class="mb-0">
        <button class="btn btn-link btn-block text-left text-white p-0 card-title" type="button" data-toggle="collapse" data-target="#collapse3" aria-expanded="true" aria-controls="collapse3">
          Presupuesto<?php echo isset($datos['0']['id_previsita']) ? ' N°:<strong class="text-lg"> ' . $datos['0']['id_previsita'].'</strong>' : ''; ?>
        </button>
      </h2>
    </div>

    <!-- start collapse accordion 3 -->
    <div id="collapse3" class="collapse <?php echo !isset($cliente_datos['0']['id_cliente']) ? '' : ''; ?>" aria-labelledby="headingOne" data-parent="#accordionExample3">

          <!-- start card body accordion 3-->
          <div class="card-body">
            EN DESARROLLO
          </div>
          <!-- end card accordion 3 -->

    </div>
    <!-- end collapse accordion 3-->

  </div>
  <!-- end card accordion 3 -->
</div>
<!-- end accordion 3 -->

<!-- start accordion 4 -->
<div class="accordion" id="accordionExample4">
  <div class="card <?php echo $orden_compra_card; ?> accordion 4">
    <div class="card-header" id="heading2">
      <h2 class="mb-0">
        <button class="btn btn-link btn-block text-left text-white p-0 card-title" type="button" data-toggle="collapse" data-target="#collapse4" aria-expanded="true" aria-controls="collapse4">
          Orden de compra<?php echo isset($datos['0']['id_previsita']) ? ' N°:<strong class="text-lg"> ' . $datos['0']['id_previsita'].'</strong>' : ''; ?>
        </button>
      </h2>
    </div>

    <!-- start collapse accordion 2 -->
    <div id="collapse4" class="collapse <?php echo !isset($cliente_datos['0']['id_cliente']) ? '' : ''; ?>" aria-labelledby="heading4" data-parent="#accordionExample4">

          <!-- start card body accordion 1-->
          <div class="card-body">
            EN DESARROLLO
          </div>
          <!-- end card accordion 1 -->

    </div>
    <!-- end collapse accordion 2-->

  </div>
  <!-- end card accordion 2 -->
</div>
<!-- end accordion 4 -->



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
<script src="../05-plugins/popper/popper.min.js"></script>

<!-- funciones customizadas -->
<script src="../07-funciones_js/funciones.js"></script>


<!-- Page specific script -->
<script>
  var camposLimpios = "";

$(document).ready(function() {
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

    var tareaLabelNumero = 1;

    var tareaNumero = 1;

    // Obtener el elemento con el id tarea_1
    var tarea1 = document.getElementById('tarea_1');

    // Clonar el elemento
    var tarea = tarea1.cloneNode(true);

///////////////////////////////////////////////////////////////////////////////
  var itemTable = 'off';
  var itemNumber = 0;

  $(document).on('click',".v-btn-add",function(){  
    var itemSelectID = "#"+$(this).data('id');
  
    var itemsTable = '#items_table_'+tareaNumero.toString();

    var itemsTableBody = '#'+$(this).data('items_table_body');

    if(($(itemSelectID).val() == 'Seleccione un ítem' || $(itemSelectID).val() == null) || $(itemSelectID).val() == ''){

 sAlertAutoClose('error',
  'COMPLETA LOS CAMPOS',
  'Debes completar los campos <b>Items sugeridos</b> y <b>cantidad</b> para agregar un ítem', 
  4000,
 );

    }else{

        if(itemTable == 'off'){

          $(itemsTable).removeClass('d-none');
          $('#visita_buttons').removeClass('d-none');
          itemTable = 'on';  
        }
     
        itemNumber = itemNumber + 1;
          var html  = '<tr class="v-tarea-item" id="item_'+String(itemNumber)+'">';
              html += '<td>'+String(itemNumber)+'.</td>';
              html += '<td>'+$(itemSelectID+' option:selected').text()+'</td>;'
              html += '<td class="text-center">'+$('#item_cantidad').inputmask('unmaskedvalue')+'</td>';
              html += '<td class="text-center"><i class="fa-solid fa-circle-xmark text-danger v-icon-delete-item v-icon-pointer" data-item_id="item_'+String(itemNumber)+'"></i></td></tr>';
              html += '</tr>';

        $(itemsTableBody).append(html);  
        if($(itemsTable+" tbody tr").length > 0){$(itemsTable+', #visita_buttons').fadeIn(300);}

        $(itemSelectID).val(null).trigger('change.select2');

        var firstOption = $(itemSelectID+" option:first");
        firstOption.prop("selected", true);
        $(itemSelectID).trigger('change.select2');

        $("#item_cantidad").val('');
    }

  });    

  $(document).on('click',".v-icon-delete-item",function(){  
    var itemTableId = $(this).data("item_id");
    $('#'+itemTableId).remove();
    if($(itemsTable+" tbody tr").length == 0){$(itemsTable+', #visita_buttons').fadeOut(300);}

  });

  $(document).on('click',".v-add-tarea",function(){  
    alert('v-add-tarea');
        tareaNumero = tareaNumero + 1; 

        tareaLabelNumero = tareaLabelNumero + 1;

        //clonar la botonera
        var botoneraVisita       = document.getElementById('visita_buttons');
        var botoneraVisitaClon  = botoneraVisita.cloneNode(true);
        botoneraVisita.remove();

        var tareaClon = tarea.cloneNode(true);

        tareaClon.id = 'tarea_'+tareaNumero.toString();

        // Agregar el nuevo elemento clonado al elemento con id visita_tareas
        var visitaTareas = document.getElementById('visita_tareas');
        visitaTareas.appendChild(tareaClon);

        // cambia el título de la tarea - OK
        $(".v-tarea:last .v-label-tarea").html("<b>Tarea Nro: "+tareaLabelNumero.toString()+"</b>");

        //cambia el data del boton quitar tarea del clon - OK
        $(".v-tarea:last .v-icon-delete-tarea").data('tarea', 'tarea_'+tareaNumero.toString());

        //cambia el id items_table_1 por el al nro que tiene la tarea - 
        $(".v-tarea:last #items_table_1").prop("id", "items_table_"+tareaNumero.toString());

        //cambia el id items_table_body por el al nro que tiene la tarea - 
        $(".v-tarea:last #items_table_body_1").prop("id", "items_table_body_"+tareaNumero.toString());

        //cambia el id item_select_1 por el al nro que tiene la tarea - 
        $(".v-tarea:last #item_select_1").prop("id", "item_select_"+tareaNumero.toString());

        $(".v-tarea:last").find(".v-btn-add").data('id', 'item_select_'+tareaNumero.toString());
        $(".v-tarea:last").find(".v-btn-add").data('items_table_body', "items_table_body_"+tareaNumero.toString());    

        itemTable = 'off';




        //visitaTareas.appendChild(botoneraVisitaClon); 

  });


  $(document).on('click',".v-icon-delete-tarea",function(){

      tareaLabelNumero = 0;

      $('#'+$(this).data('tarea')).remove(); 

      $('.v-label-tarea').each(function(index, value){

          tareaLabelNumero = tareaLabelNumero + 1;
          $(this).html("<b>Tarea Nro: "+tareaLabelNumero.toString()+"</b>")

      });


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
                  () => inputPushValue({"#razon_social": {"valor": razon_social, "texto": false}, "#altura_visita": {"valor": altura, "texto": false}, "#cp_visita": {"valor": cp, "texto": false}}),
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

});

$(function () {

  $('[data-toggle="tooltip"]').tooltip();

  bsCustomFileInput.init();


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

    //Initialize Select2 Elements
    $('.select2').select2({
      language: "es"
    })

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4',
      language: "es"
    })

    //Datemask dd/mm/yyyy
    $('#datemask').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' })
    //Datemask2 mm/dd/yyyy
    $('#datemask2').inputmask('mm/dd/yyyy', { 'placeholder': 'mm/dd/yyyy' })
    //Money Euro
    $('[data-mask]').inputmask()

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

<!-- archivo funciones -->
<script src="../07-funciones_js/funciones.js"></script>
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



</body>
</html>
