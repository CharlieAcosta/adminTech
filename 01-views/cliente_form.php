<?php  
session_start();
define('BASE_URL', $_SESSION["base_url"]);
include_once '../04-modelo/conectDB.php'; //conecta a la base de datos
include_once '../04-modelo/paisesModel.php'; //conecta a la tabla de paises
include_once '../04-modelo/provinciasModel.php'; //conecta a la tabla de provincias
include_once '../04-modelo/partidosModel.php'; //conecta a la tabla de partido
include_once '../04-modelo/localidadesModel.php'; //conecta a la tabla de localidades
include_once '../04-modelo/clientesModel.php'; //conecta a la tabla de clientes
include_once '../04-modelo/callesModel.php'; //conecta a la tabla de usuarios

$id=""; $visualiza=""; $pdf="";
if(isset($_GET['id']) && isset($_GET['acci'])){
  $id = $_GET['id'];
  if($_GET['acci'] == "v"){$visualiza="on";}
  if($_GET['acci'] == "pdf"){$pdf="on";}
  $cliente_datos = modGetClientesById($id, 'php');
//var_dump($cliente_datos); die();
//echo utf8_encode( $usuario_datos['0']['provincia'] ); die();
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
  <title class="v-alta d-none">ADMINTECH | Alta de cliente</title>
  <title class="v-visual d-none">ADMINTECH | Visualización de cliente</title>
  <title class="v-edit d-none">ADMINTECH | Edición de cliente</title>

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
            <h1><strong class="v-alta d-none">Alta de cliente</strong><strong class="v-visual d-none">Visualización de cliente</strong><strong class="v-edit d-none">Edición de cliente</strong></h1>
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
<form id="currentForm"  class="form" enctype="multipart/form-data">
    <section class="content">
      <div class="container-fluid">

<!-- /.card start -------------------------------------------------------------------------------------------------------------------------------------------------- -->
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">Datos del cliente<?php echo isset($cliente_datos['0']['id_cliente']) ? ' N°:<strong class="text-lg"> ' . $cliente_datos['0']['id_cliente'].'</strong>' : ''; ?></h3>
              </div>
              <div class="card-body">
                <input type="hidden" class="v-id" id="id_cliente" name="id_cliente" data-visualiza="<?php echo $visualiza; ?>" value="<?php if(isset($cliente_datos['0']['id_cliente'])){echo $cliente_datos['0']['id_cliente'];}?>">
<!--                 <div class="row">
                <?php
                    if(isset($usuario_datos['0']['doc_foto']) && $usuario_datos['0']['doc_foto'] != '')
                    { $url = $usuario_datos['0']['doc_foto'];
                      $downloadUrl = str_replace('www.dropbox.com', 'dl.dropboxusercontent.com', $url);
                      $downloadUrl = str_replace('?dl=0', '', $downloadUrl);
                      echo '<img class="foto4x4 mb-3 ml-2" src="'.$downloadUrl.'">';
                    }
                    else
                    {
                     echo '<div class="foto4x4 mb-3 ml-2 p-5"><small>Sin foto</small></div>'; 
                    }
                  ?>
                 
                </div>
--> 
<!--  codigo de arriba comentado por si quieren por el logo de la empresa -->

                <!-- /.row -->
                <div class="row">

                  <div class="col-1 form-group mb-0 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Fecha de alta</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-calendar-check "></i></span>
                        </div>
                          <input type="text" class="form-control v-input-requerido v-disabled" inputmode="decimal" placeholder="" id="" name="" 
                          value="<?php echo isset($cliente_datos['0']['log_alta']) ? date('d-m-Y', strtotime($cliente_datos['0']['log_alta'])) : '' ?>">
                      </div>
                  </div>

                  <div class="col-2 form-group mb-0 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">CUIT</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-file-invoice v-requerido-icon"></i></span>
                        </div>
                          <input type="text" class="form-control v-input-requerido" data-inputmask='"mask": "99-99999999-9", "clearIncomplete": "true"' data-mask="" inputmode="decimal" data-cuit="<?php echo isset($cliente_datos['0']['cuit']) ? $cliente_datos['0']['cuit'] : ''; ?>" placeholder="CUIT" id="cuit" name="cuit" value="<?php if(isset($cliente_datos['0']['cuit'])){echo $cliente_datos['0']['cuit'];}?>">
                      </div>
                  </div>

                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Razón Social</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-city v-requerido-icon"></i></span>
                        </div>
                        <input type="text" class="form-control v-input-requerido" placeholder="Razón Social" id="razon_social" name="razon_social" value="<?php if(isset($cliente_datos['0']['razon_social'])){echo $cliente_datos['0']['razon_social'];}?>">
                      </div>
                  </div>
              
                  <div class="col-2 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Teléfono</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-phone v-requerido-icon"></i></span>
                        </div>
                          <input type="text" class="form-control v-input-requerido" data-inputmask='"mask": "(9{1,5})(99999999)", "clearIncomplete": "true"' data-mask="" inputmode="decimal" placeholder="Teléfono" id="telefono" name="telefono" value="<?php if(isset($cliente_datos['0']['telefono'])){echo $cliente_datos['0']['telefono'];}?>">
                      </div>
                  </div>

                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Email</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-envelope v-requerido-icon"></i></span>
                        </div>
                        <input type="text" class="form-control v-input-requerido" placeholder="Email" data-inputmask='"mask": "[{1,20}|_|.]@*{1,20}.*{1,3}[.*{1,3}]", "clearIncomplete": "true"' autocomplete="rutjfkde" id="email" name="email" value="<?php if(isset($cliente_datos['0']['email'])){echo $cliente_datos['0']['email'];}?>">
                      </div>
                  </div>

                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Estado</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-sliders-h v-requerido-icon"></i></span>
                        </div>
                        <select class="form-control v-input-requerido" id="estado" name="estado">
                          <!-- <option value="<?php if(isset($cliente_datos['0']['estado'])){echo $cliente_datos['0']['estado'];}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($cliente_datos['0']['estado'])){echo $cliente_datos['0']['estado'];}else{echo "Estado";} ?></option> -->

                          <?php echo !isset($cliente_datos['0']['estado']) ? '<option value="" disabled selected class="bg-secondary">Estado</option>' : ''; ?>                        
                          <option value="Potencial" <?php echo isset($cliente_datos['0']['estado']) && $cliente_datos['0']['estado'] == "Potencial" ? "selected" : ''; ?>>Potencial</option>
                          <option value="Activo" <?php echo isset($cliente_datos['0']['estado']) && $cliente_datos['0']['estado'] == "Activo" ? "selected" : ''; ?>>Activo</option>
                          <option value="Desactivado" <?php echo isset($cliente_datos['0']['estado']) && $cliente_datos['0']['estado'] == "Desactivado" ? "selected" : ''; ?>>Desactivado</option>
                        </select>
                      </div>
                  </div>

                </div>
              </div>
              <!-- /.card-body -->
            </div>
<!-- /.card end -------------------------------------------------------------------------------------------------------------------------------------------------- -->

<!-- /.card start ------------------------------------------------------------------------------------------------------------------------------------------------ -->
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">Domicilio Fiscal</h3>
              </div>
              <div class="card-body">    
                <div class="row">
                  <div class="col-4 form-group mb-1 mt-1">
                    <small class="v-visual-edit d-none"><label class="mb-0">Provincia</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                        </div>
                        <select class="form-control select2bs4 v-select2 provincia" id="dirfis_provincia" name="dirfis_provincia">
                          <option value="<?php if(isset($cliente_datos['0']['dirfis_provincia'])){echo utf8_encode($cliente_datos['0']['dirfis_provincia']);}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($cliente_datos['0']['provincianom'])){echo utf8_encode($cliente_datos['0']['provincianom']);}else{echo "Provincia";} ?></option>
                          <?php echo $provinciasSelect;?>
                        </select>
                      </div>
                  </div>
                  <div class="col-4 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Partido</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-map-marked-alt v-requerido-icon-off"></i></span>
                        </div>
                        <select class="form-control select2bs4 v-select2 partido" id="dirfis_partido" name="dirfis_partido">
                          <option value="<?php if(isset($cliente_datos['0']['dirfis_partido'])){echo $cliente_datos['0']['dirfis_partido'];}else{echo "";} ?>" disabled selected class="bg-secondary partidosOpt1"><?php if(isset($cliente_datos['0']['partidonom'])){echo $cliente_datos['0']['partidonom'];}else{echo "Partido";} ?></option>
                        <?php if(isset($partidosSelect)){echo $partidosSelect;} ?>
                        </select>
                      </div>
                  </div>
                  <div class="col-4 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Localidad</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-map-marked-alt v-requerido-icon-off"></i></span>
                        </div>
                        <select class="form-control select2bs4 v-select2 localidad" id="dirfis_localidad" name="dirfis_localidad">
                          <option value="<?php if(isset($cliente_datos['0']['dirfis_localidad'])){echo $cliente_datos['0']['dirfis_localidad'];}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($cliente_datos['0']['localidadnom'])){echo $cliente_datos['0']['localidadnom'];}else{echo "Localidad";} ?></option>
                            <?php if(isset($localidadesSelect)){echo $localidadesSelect;} ?>
                        </select>
                      </div>
                  </div>
                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Calle</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-road v-requerido-icon-off"></i></span>
                        </div>
                        <select class="form-control select2bs4 v-select2 calle" id="dirfis_calle" name="dirfis_calle">
                          <option value="<?php if(isset($cliente_datos['0']['dirfis_calle'])){echo $cliente_datos['0']['dirfis_calle'];}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($cliente_datos['0']['callenom'])){echo $cliente_datos['0']['callenom'];}else{echo "Calle";} ?></option>
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
                          <input type="text" class="form-control " data-inputmask='"mask": "9{1,5}", "clearIncomplete": "true" ' data-mask="" inputmode="decimal" placeholder="Altura / Km" id="dirfis_altura" name="dirfis_altura" value="<?php if(isset($cliente_datos['0']['dirfis_altura'])){echo $cliente_datos['0']['dirfis_altura'];}?>">
                      </div>
                  </div>
                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Piso</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-building"></i></span>
                        </div>
                          <input type="text" class="form-control" data-inputmask='"mask": "*{0,3}"' data-mask="" placeholder="Piso" id="dirfis_piso" name="dirfis_piso" value="<?php if(isset($cliente_datos['0']['dirfis_piso'])){echo $cliente_datos['0']['dirfis_piso'];}?>">
                      </div>
                  </div>
                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Depto</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-door-closed"></i></span>
                        </div>
                          <input type="text" class="form-control" data-inputmask='"mask": "*{0,3}"' data-mask="" placeholder="Depto" id="dirfis_depto" name="dirfis_depto" value="<?php if(isset($cliente_datos['0']['dirfis_depto'])){echo $cliente_datos['0']['dirfis_depto'];}?>">
                      </div>
                  </div>
                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">CP</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-mail-bulk"></i></span>
                        </div>
                          <input type="text" class="form-control " data-inputmask='"mask": "9{1,5}", "clearIncomplete": "true" ' data-mask="" inputmode="decimal" placeholder="CP" id="dirfis_cp" name="dirfis_cp" value="<?php if(isset($cliente_datos['0']['dirfis_cp'])){echo $cliente_datos['0']['dirfis_cp'];}?>">
                      </div>
                  </div>
                </div>
                <!-- /.row -->

              </div>
              <!-- /.card-body -->
            </div>
<!-- /.card end -------------------------------------------------------------------------------------------------------------------------------------------------- -->

<div class="row pl-2 pr-2">
<!-- /.card start ------------------------------------------------------------------------------------------------------------------------------------------------ -->
<div class="col-6 pl-0">          
            <div class="col-12 card card-info pl-0 pr-0">
              <div class="card-header">
                <h3 class="card-title">Contacto principal</h3>
              </div>
              <div class="card-body">
                <div class="row">
              
                  <div class="col-4 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Apellido y Nombre(s)</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-user-tie v-requerido-icon"></i></span>
                        </div>
                        <input type="text" class="form-control v-input-requerido" placeholder="Apellido y Nombre(s)" id="contacto_pri" name="contacto_pri" value="<?php if(isset($cliente_datos['0']['contacto_pri'])){echo $cliente_datos['0']['contacto_pri'];}?>">
                      </div>
                  </div>

                  <div class="col-4 form-group mb-1 mt-1">
                    <small class="v-visual-edit d-none"><label class="mb-0">Celular</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-mobile-alt v-requerido-icon"></i></span>
                        </div>
                          <input type="text" class="form-control v-input-requerido" data-inputmask='"mask": "(9{1,5})(99999999)", "clearIncomplete": "true"' data-mask="" inputmode="decimal" placeholder="Celular" id="contacto_pri_celular" name="contacto_pri_celular" value="<?php if(isset($cliente_datos['0']['contacto_pri_celular'])){echo $cliente_datos['0']['contacto_pri_celular'];}?>">
                      </div>
                  </div>

                  <div class="col-4 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Email</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-envelope v-requerido-icon"></i></span>
                        </div>
                        <input type="text" class="form-control v-input-requerido" placeholder="Email" data-inputmask='"mask": "[{1,20}|_|.]@*{1,20}.*{1,3}[.*{1,3}]", "clearIncomplete": "true"'  autocomplete="rutjfkde" id="contacto_pri_email" name="contacto_pri_email" value="<?php if(isset($cliente_datos['0']['contacto_pri_email'])){echo $cliente_datos['0']['contacto_pri_email'];}?>">
                      </div>
                  </div>

                </div>
                <!-- /.row -->
              </div>
              <!-- /.card-body -->
            </div>
</div> 
<!-- /.card end -------------------------------------------------------------------------------------------------------------------------------------------------- -->


<!-- /.card start ------------------------------------------------------------------------------------------------------------------------------------------------ -->
<div class="col-6 pr-0">  
            <div class="col-12 card card-info pl-0 pr-0">
              <div class="card-header">
                <h3 class="card-title">Contacto pago a proveedores</h3>
              </div>
              <div class="card-body">
                <div class="row">
              
                  <div class="col-4 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Apellido y Nombre(s)</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-user-tie"></i></span>
                        </div>
                        <input type="text" class="form-control" placeholder="Apellido y Nombre(s)" id="contacto_papro" name="contacto_papro" value="<?php if(isset($cliente_datos['0']['contacto_papro'])){echo $cliente_datos['0']['contacto_papro'];}?>">
                      </div>
                  </div>

                  <div class="col-4 form-group mb-1 mt-1">
                    <small class="v-visual-edit d-none"><label class="mb-0">Celular</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-mobile-alt"></i></span>
                        </div>
                          <input type="text" class="form-control" data-inputmask='"mask": "(9{1,5})(99999999)", "clearIncomplete": "true"' data-mask="" inputmode="decimal" placeholder="Celular" id="contacto_papro_celular" name="contacto_papro_celular" value="<?php if(isset($cliente_datos['0']['contacto_papro_celular'])){echo $cliente_datos['0']['contacto_papro_celular'];}?>">
                      </div>
                  </div>

                  <div class="col-4 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Email</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        </div>
                        <input type="text" class="form-control" placeholder="Email" data-inputmask='"mask": "[{1,20}|_|.]@*{1,20}.*{1,3}[.*{1,3}]", "clearIncomplete": "true"'  autocomplete="rutjfkde" id="contacto_papro_email" name="contacto_papro_email" value="<?php if(isset($cliente_datos['0']['contacto_papro_email'])){echo $cliente_datos['0']['contacto_papro_email'];}?>">
                      </div>
                  </div>

                </div>
                <!-- /.row -->
              </div>
              <!-- /.card-body -->
            </div>
</div>
<!-- /.card end -------------------------------------------------------------------------------------------------------------------------------------------------- -->
</div>

<!-- /.card start ------------------------------------------------------------------------------------------------------------------------------------------------ -->
            <!-- /.card -->
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">Plataformas</h3> 
              </div>
              <div class="card-body">              

                <!-- start row -->    
                <div class="row">

                      <div class="col-3 form-group mb-1 mt-1">
                        <small class="v-visual-edit d-none"><label class="mb-0">Plataforma de licitaciones</label></small>
                        <div class="input-group mb-0">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-file-invoice-dollar v-documento-link" data-tipo="site"></i></span>
                          </div>
                          <input type="text" class="form-control" placeholder="Plataforma de licitaciones" id="plat_licitacion" name="plat_licitacion" value="<?php if(isset($cliente_datos['0']['plat_licitacion'])){echo $cliente_datos['0']['plat_licitacion'];}?>">
                        </div>
                      </div>
 
                      <div class="col-3 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">Usuario</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input type="text" class="form-control v-input-requerido" placeholder="Usuario" id="usuario_licitacion" name="usuario_licitacion" value="<?php if(isset($cliente_datos['0']['usuario_licitacion'])){echo $cliente_datos['0']['usuario_licitacion'];}?>">
                          </div>
                      </div>

                      <div class="col-3 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">Contraseña</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-key"></i></span>
                            </div>
                            <input type="text" class="form-control v-input-requerido" placeholder="Contraseña" id="pass_licitacion" name="pass_licitacion" value="<?php if(isset($cliente_datos['0']['pass_licitacion'])){echo $cliente_datos['0']['pass_licitacion'];}?>">
                          </div>
                      </div>

                      <div class="col-3 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">Email</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            </div>
                            <input type="text" class="form-control" placeholder="Email" data-inputmask='"mask": "[{1,20}|_|.]@*{1,20}.*{1,3}[.*{1,3}]", "clearIncomplete": "true"'  autocomplete="rutjfkde" id="email_licitacion" name="email_licitacion" value="<?php if(isset($cliente_datos['0']['email_licitacion'])){echo $cliente_datos['0']['email_licitacion'];}?>">
                          </div>
                      </div>

                </div>
                <!-- /end row -->              

                <!-- start row -->    
                <div class="row">

                      <div class="col-3 form-group mb-1 mt-1">
                        <small class="v-visual-edit d-none"><label class="mb-0">Plataforma de pagos</label></small>
                        <div class="input-group mb-0">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-hand-holding-usd v-documento-link" data-tipo="site"></i></span>
                          </div>
                          <input type="text" class="form-control" placeholder="Plataforma de pagos" id="plat_pagos" name="plat_pagos" value="<?php if(isset($cliente_datos['0']['plat_pagos'])){echo $cliente_datos['0']['plat_pagos'];}?>">
                        </div>
                      </div>
 
                      <div class="col-3 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">Usuario</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input type="text" class="form-control v-input-requerido" placeholder="Usuario" id="usuario_pagos" name="usuario_pagos" value="<?php if(isset($cliente_datos['0']['usuario_pagos'])){echo $cliente_datos['0']['usuario_pagos'];}?>">
                          </div>
                      </div>

                      <div class="col-3 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">Contraseña</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-key"></i></span>
                            </div>
                            <input type="text" class="form-control v-input-requerido" placeholder="Contraseña" id="pass_pagos" name="pass_pagos" value="<?php if(isset($cliente_datos['0']['pass_pagos'])){echo $cliente_datos['0']['pass_pagos'];}?>">
                          </div>
                      </div>

                      <div class="col-3 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">Email</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            </div>
                            <input type="text" class="form-control" placeholder="Email" data-inputmask='"mask": "[{1,20}|_|.]@*{1,20}.*{1,3}[.*{1,3}]", "clearIncomplete": "true"'  autocomplete="rutjfkde" id="email_pagos" name="email_pagos" value="<?php if(isset($cliente_datos['0']['email_pagos'])){echo $cliente_datos['0']['email_pagos'];}?>">
                          </div>
                      </div>

                </div>
                <!-- /end row -->  

                <!-- start row -->    
                <div class="row">

                      <div class="col-3 form-group mb-1 mt-1">
                        <small class="v-visual-edit d-none"><label class="mb-0">Plataforma de documentación</label></small>
                        <div class="input-group mb-0">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-file-invoice v-documento-link" data-tipo="site"></i></span>
                          </div>
                          <input type="text" class="form-control" placeholder="Plataforma de documentación" id="plat_documentacion" name="plat_documentacion" value="<?php if(isset($cliente_datos['0']['plat_documentacion'])){echo $cliente_datos['0']['plat_documentacion'];}?>">
                        </div>
                      </div>
 
                      <div class="col-3 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">Usuario</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input type="text" class="form-control v-input-requerido" placeholder="Usuario" id="usuario_documentacion" name="usuario_documentacion" value="<?php if(isset($cliente_datos['0']['usuario_documentacion'])){echo $cliente_datos['0']['usuario_documentacion'];}?>">
                          </div>
                      </div>

                      <div class="col-3 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">Contraseña</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-key"></i></span>
                            </div>
                            <input type="text" class="form-control v-input-requerido" placeholder="Contraseña" id="pass_documentacion" name="pass_documentacion" value="<?php if(isset($cliente_datos['0']['pass_documentacion'])){echo $cliente_datos['0']['pass_documentacion'];}?>">
                          </div>
                      </div>

                      <div class="col-3 form-group mb-1 mt-1">
                          <small class="v-visual-edit d-none"><label class="mb-0">Email</label></small>
                          <div class="input-group mb-0">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            </div>
                            <input type="text" class="form-control" placeholder="Email" data-inputmask='"mask": "[{1,20}|_|.]@*{1,20}.*{1,3}[.*{1,3}]", "clearIncomplete": "true"'  autocomplete="rutjfkde" id="email_documentacion" name="email_documentacion" value="<?php if(isset($cliente_datos['0']['email_documentacion'])){echo $cliente_datos['0']['email_documentacion'];}?>">
                          </div>
                      </div>

                </div>
                <!-- /end row -->  

              </div>
              <!-- /.card-body -->
            </div>
<!-- /.card end -------------------------------------------------------------------------------------------------------------------------------------------------- -->

<!-- /.card start ------------------------------------------------------------------------------------------------------------------------------------------------ -->
            <!-- /.card -->
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">Notas</h3> 
              </div>
              <div class="card-body">              
                <div class="row">
                      <div class="col-12 form-group mb-1 mt-1">
                        <small class="v-visual-edit d-none"><label class="mb-0">Nota</label></small>
                        <div class="input-group mb-0">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                          </div>
                          <textarea type="text" rows="5" class="form-control" placeholder="Nota" id="cliente_nota" name="cliente_nota"><?php if(isset($cliente_datos['0']['cliente_nota'])){echo $cliente_datos['0']['cliente_nota'];}?></textarea>
                        </div>
                      </div>
                </div>
                <!-- /.row -->              
              </div>
              <!-- /.card-body -->
            </div>
<!-- /.card end -------------------------------------------------------------------------------------------------------------------------------------------------- -->
            <div class="row d-flex text-center justify-content-center pr-1">
              <button type="submit" class="col-1 btn btn-primary btn-block m-2 v-alta-edit d-none" data-accion="guardar"><i class="fa fa-plus-circle"></i> Guardar</button>
              <button onclick="window.location.href='clientes_listado.php'" type="button" class="col-1 btn btn-success btn-block m-2 v-edit"><i class="fa fa-arrow-circle-left"></i> Volver</button>
              <button type="button" class="col-1 btn btn-warning btn-block m-2 v-alta-edit d-none v-accion-cancelar" data-accion="cancelar"><i class="fa fa-ban"></i> Cancelar</button>
              <button type="button" class="col-1 btn btn-danger btn-block m-2 v-edit v-accion-eliminar d-none" data-accion="eliminar"><i class="fa fa-trash"></i> Eliminar</button>
            </div>


      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</form>
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
  });

  $(function () {
  $.validator.setDefaults({
    submitHandler: function () {
      clientesGuardar();
    }
  });

  $('#currentForm').validate({
    rules: {
      cuit: {
        required: true,
        // remote: {
        //     url:  "../04-modelo/clienteChekCUITmodel.php",
        //     type: "post",
        //     data: {
        //      ajax: "on",
        //      accion: "check",
        //      idCliente: '<?php if(isset($cliente_datos['0']['id_cliente'])){echo $cliente_datos['0']['id_cliente'];}?>"',
        //      cuitInicial: $("#cuit").data("cuit")
        //     }
        // }
      },
      razon_social: {
        required: true,
      },
      telefono: {
        required: true,
      },
      email: {
        required: true,
        email: true,
        // remote: {
        //     url:  "../04-modelo/usuariosChekEmailModel.php",
        //     type: "post",
        //     data: {
        //      ajax: "on",
        //      accion: "check",
        //      idUsuario: '<?php if(isset($usuario_datos['0']['id_usuario'])){echo $usuario_datos['0']['id_usuario'];}?>"',
        //     }
        // }
      },
      estado: {
        required: true,
      },
      contacto_pri: {
        required: true,
      },
      contacto_pri_celular: {
        required: true,
      },
      contacto_pri_email: {
        required: true,
        email: true,
      },
      contacto_papro_email: {
        email: true,   
      }, 
      email_licitacion: {
        email: true,   
      }, 
      email_documentacion: {
        email: true,   
      }, 
      email_pagos: {
        email: true,   
      },
    },

    messages: {
      cuit: {
        required: "Debe completar este campo",
      },
      razon_social: {
        required: "Debe completar este campo",
      },
      telefono: {
        required: "Debe completar este campo",
      },
      email: {
        required: "Debe completar este campo",
        email: "El formato del email es invalido",      
      },
      estado: {
        required: "Debe completar este campo",
      },
      contacto_pri: {
        required: "Debe completar este campo",
      },
      contacto_pri_celular: {
        required: "Debe completar este campo",
      },
      contacto_pri_email: {
        required: "Debe completar este campo",
        email: "El formato del email es invalido",   
      },           
      contacto_papro_email: {
        email: "El formato del email es invalido",   
      }, 
      email_licitacion: {
        email: "El formato del email es invalido",   
      }, 
      email_documentacion: {
        email: "El formato del email es invalido",   
      }, 
      email_pagos: {
        email: "El formato del email es invalido",   
      } 

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
  })
 
</script>

<!-- custom functions -->
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
<script src="../07-funciones_js/clientesAcciones.js"></script>
<!-- Guarda usuarios en la base -->
<script src="../07-funciones_js/clienteGuardar.js"></script>
<!-- Inicializa bsCustomFileInput después de que el documento se haya cargado -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    bsCustomFileInput.init();

    $('.v-archivos-admitidos-pdf, .v-archivos-admitidos-jpg').each(function() {

        if($(this).hasClass('v-archivos-admitidos-pdf')){this.accept = "application/pdf";}
        if($(this).hasClass('v-archivos-admitidos-jpg')){this.accept = "image/jpeg, image/jpg, image/png";}
            
      this.addEventListener('change', function() {
          var file = this.files[0];
          if (file && file.size > 5 * 1024 * 1024) {

            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'El archivo no puede ser mayor a 5 Mb',
              timer: 3500, // Duración de 10 segundos
              timerProgressBar: true,
              position: 'center',
              showConfirmButton: false
            });

            this.value = '';
            $(this).next('.custom-file-label').html('Seleccionar archivo');
          }
      });
    });
});

$('input[type="file"]').change(function() {
      // Almacenar $(this) en una variable para mantener el contexto
      var campoV = $(this);
      var campo = $(this).nextAll(":input:hidden:first");     
      // Leer el contenido del portapapeles
      navigator.clipboard.readText()
        .then(function(clipboardText) {
        if (clipboardText.startsWith('https://www.dropbox'))
          {// Asignar el contenido del portapapeles al campo oculto         
           campo.val(clipboardText);        
           campoV.next('.custom-file-label').html(clipboardText);             
           navigator.clipboard.writeText('');                  
          }
        else
          { Swal.fire({
              icon: 'error',
              title: 'El link no fue copiado',
              text: 'Seguramente olvidaste copiar el link con el botón derecho, intentalo de nuevo.',
              timer: 5000, // Duración de 10 segundos
              timerProgressBar: true,
              position: 'center',
              showConfirmButton: false
            });
            campoV.next('.custom-file-label').html('Seleccionar archivo');
            campoV.val('');          
          }  

        })
        .catch(function(error) {
          Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Al copiar el link del archivo, intentalo de nuevo',
              timer: 4000, // Duración de 10 segundos
              timerProgressBar: true,
              position: 'center',
              showConfirmButton: false
            });        
        });

});

</script>
</body>
</html>
