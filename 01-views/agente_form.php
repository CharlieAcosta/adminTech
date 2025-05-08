<?php  
session_start();
define('BASE_URL', $_SESSION["base_url"]);
include_once '../04-modelo/conectDB.php'; //conecta a la base de datos
include_once '../04-modelo/paisesModel.php'; //conecta a la tabla de paises
include_once '../04-modelo/provinciasModel.php'; //conecta a la tabla de provincias
include_once '../04-modelo/partidosModel.php'; //conecta a la tabla de partido
include_once '../04-modelo/localidadesModel.php'; //conecta a la tabla de localidades
include_once '../04-modelo/usuariosModel.php'; //conecta a la tabla de usuarios
include_once '../04-modelo/callesModel.php'; //conecta a la tabla de usuarios

include_once '../06-funciones_php/funciones.php'; 
include_once '../06-funciones_php/auditoria.php';
registrarNavegacion('AGENTES - Formulario');


$id=""; $visualiza=""; $pdf="";
if(isset($_GET['id']) && isset($_GET['acci'])){
  $id = $_GET['id'];
  if($_GET['acci'] == "v"){$visualiza="on";}
  if($_GET['acci'] == "pdf"){$pdf="on";}
  $usuario_datos = modGetUsuariosById($id, 'php');
//var_dump($usuario_datos); die();
//echo utf8_encode( $usuario_datos['0']['provincia'] ); die();
}

$paises = getAllPaises();
$paisesSelect = ""; //para el select de nacionalidad
foreach ($paises as $key => $value) {
  $paisesSelect .= '<option value="'.utf8_encode($value['nombre']).'">'.utf8_encode($value['nombre']).'</option>';
}

$provincias = getAllProvincias();
$provinciasSelect = ""; //para el select de provincias
foreach ($provincias as $key => $value) {
   if(!isset($usuario_datos)){ 
      $provinciasSelect .= '<option value="'.utf8_encode($value['id_provincia']).'">'.utf8_encode($value['provincia']).'</option>';
   }else{
    if($usuario_datos['0']['provincia'] != $value['id_provincia']){
      $provinciasSelect .= '<option value="'.utf8_encode($value['id_provincia']).'">'.utf8_encode($value['provincia']).'</option>';
    }
   }
}

// solo si es edición
if(isset($usuario_datos['0']['id_usuario']) && $visualiza == ""){ 
//var_dump($usuario_datos['0']['provincia']); die();
  $partidos = getPartidosByProvincia($usuario_datos['0']['provincia'], 'php');
  $partidosSelect = ""; //para el select de partidos
  foreach ($partidos as $key => $value) {
    if($value['id_partido'] != $usuario_datos['0']['partido']){
      $partidosSelect .= '<option value="'.utf8_encode($value['id_partido']).'">'.utf8_encode($value['partido']).'</option>';
    }
  }

  $localidades = getLocalidadesByPartido($usuario_datos['0']['partido'], 'php');
  $localidadesSelect = ""; //para el select de localidades
  foreach ($localidades as $key => $value) {
    if($value['id_localidad'] != $usuario_datos['0']['localidad']){
      $localidadesSelect .= '<option value="'.utf8_encode($value['id_localidad']).'">'.utf8_encode($value['localidad']).'</option>';
    }
  }

  $calles = getCallesByPartido($usuario_datos['0']['partido'], 'php');
  $callesSelect = ""; //para el select de calles
  foreach ($calles as $key => $value) {
    if($value['id_calle'] != $usuario_datos['0']['calle']){
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
  <title class="v-alta d-none">ADMINTECH | Alta de agente</title>
  <title class="v-visual d-none">Visualización de agente</title>
  <title class="v-edit d-none">Edición de agente</title>

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
            <h1><strong class="v-alta d-none">Alta de agente</strong><strong class="v-visual d-none">Visualización de agente</strong><strong class="v-edit d-none">Edición de agente</strong></h1>
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
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">Datos personales</h3>
              </div>
              <div class="card-body">
                <input type="hidden" class="v-id" id="id_usuario" name="id_usuario" data-visualiza="<?php echo $visualiza; ?>" value="<?php if(isset($usuario_datos['0']['id_usuario'])){echo $usuario_datos['0']['id_usuario'];}?>">
                <div class="row">
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
                <!-- /.row -->
                <div class="row">
                <div class="col-1 form-group mb-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Legajo</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-file"></i></span>
                        </div>
                        <input type="text" class="form-control" placeholder="Legajo" id="legajo" name="legajo" data-inputmask='"mask": "9{0,4}"' data-mask="" inputmode="decimal" value="<?php if(isset($usuario_datos['0']['legajo'])){echo $usuario_datos['0']['legajo'];}?>">
                      </div>
                  </div>
                
                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Apellido(s)</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-user v-requerido-icon"></i></span>
                        </div>
                        <input type="text" class="form-control v-input-requerido" placeholder="Apellido(s)" id="apellidos" name="apellidos" value="<?php if(isset($usuario_datos['0']['apellidos'])){echo $usuario_datos['0']['apellidos'];}?>">
                      </div>
                  </div>

                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Nombres(s)</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-user v-requerido-icon"></i></span>
                        </div>
                        <input type="text" class="form-control v-input-requerido" placeholder="Nombre(s)" id="nombres" name="nombres" value="<?php if(isset($usuario_datos['0']['nombres'])){echo $usuario_datos['0']['nombres'];}?>">
                      </div>
                  </div>

                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Tipo de documento</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-id-card v-requerido-icon"></i></span>
                        </div>
                        <select class="form-control v-input-requerido" id="tipo_documento" name="tipo_documento">
                          <option value="<?php if(isset($usuario_datos['0']['tipo_documento'])){echo $usuario_datos['0']['tipo_documento'];}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($usuario_datos['0']['tipo_documento'])){echo $usuario_datos['0']['tipo_documento'];}else{echo "Tipo de documento";} ?></option>
                          <option value="DNI">DNI</option>
                          <option value="LE">LE</option>
                          <option value="Cédula">Cédula</option>
                          <option value="Pasaporte">Pasaporte</option>
                          <option value="DNI Extranjero">DNI Extranjero</option>
                        </select>
                      </div>
                  </div>
                  <div class="col-2 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Nro. de documento</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-id-card v-requerido-icon"></i></span>
                        </div>
                          <input type="text" class="form-control v-input-requerido" data-inputmask='"mask": "99.999.999", "clearIncomplete": "true"' data-mask="" inputmode="decimal" placeholder="Nro. de documento" id="nro_documento" name="nro_documento" value="<?php if(isset($usuario_datos['0']['nro_documento'])){echo $usuario_datos['0']['nro_documento'];}?>">
                      </div>
                  </div>
                  <div class="col-2 form-group mb-0 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">CUIL</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-file-invoice v-requerido-icon"></i></span>
                        </div>
                          <input type="text" class="form-control v-input-requerido" data-inputmask='"mask": "99-99999999-9", "clearIncomplete": "true"' data-mask="" inputmode="decimal" placeholder="CUIL" id="cuil" name="cuil" value="<?php if(isset($usuario_datos['0']['cuil'])){echo $usuario_datos['0']['cuil'];}?>">
                      </div>
                  </div>
                </div>
                <!-- /.row -->
                <div class="row">
                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Nacionalidad</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-globe v-requerido-icon"></i></span>
                        </div>
                        <select class="form-control select2bs4 v-input-requerido v-select2" id="nacionalidad" name="nacionalidad">
                          <option value="<?php if(isset($usuario_datos['0']['nacionalidad'])){echo $usuario_datos['0']['nacionalidad'];}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($usuario_datos['0']['nacionalidad'])){echo $usuario_datos['0']['nacionalidad'];}else{echo "Nacionalidad";} ?></option>
                          <?php echo $paisesSelect;?>
                        </select>
                      </div>
                  </div>
                  <div class="form-group col-2 mb-1 mt-1">
                    <small class="v-visual-edit d-none"><label class="mb-0">Fecha de nacimiento</label></small>
                    <div class="input-group mb-0">
                     <div class="input-group-prepend">
                       <span class="input-group-text"><i class="far fa-calendar-alt v-requerido-icon"></i></span>
                     </div>
                       <input type="text" class="form-control v-input-requerido" data-inputmask='"clearIncomplete": "true"' data-inputmask-alias="datetime" data-inputmask-inputformat="dd/mm/yyyy" data-mask="" inputmode="numeric" placeholder="Fecha de nacimiento" id="nacimiento" name="nacimiento" value="<?php if(isset($usuario_datos['0']['nacimiento'])){echo $usuario_datos['0']['nacimiento'];}?>">
                    </div>
                  </div>
                  <div class="col-1 form-group mb-1 mt-1">
                    <small class="v-visual-edit d-none"><label class="mb-0">Edad</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-birthday-cake"></i></span>
                        </div>
                        <input type="text" class="v-disabled form-control" placeholder="Edad" id="edad" name="edad">
                      </div>
                  </div>
                  <div class="col-2 form-group mb-1 mt-1">
                    <small class="v-visual-edit d-none"><label class="mb-0">Estado Civil</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-ring v-requerido-icon"></i></span>
                        </div>
                        <select class="form-control v-input-requerido" id="estado_civil" name="estado_civil">
                          <option value="<?php if(isset($usuario_datos['0']['estado_civil'])){echo $usuario_datos['0']['estado_civil'];}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($usuario_datos['0']['estado_civil'])){echo $usuario_datos['0']['estado_civil'];}else{echo "estado_civil";} ?></option>
                          <option value="Casado">Casado</option>
                          <option value="Soltero">Soltero</option>
                          <option value="Unión de hecho">Unión de hecho</option>
                          <option value="Viudo">Viudo</option>
                        </select>
                      </div>
                  </div>
                  <div class="col-2 form-group mb-1 mt-1">
                    <small class="v-visual-edit d-none"><label class="mb-0">Celular</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-mobile-alt v-requerido-icon"></i></span>
                        </div>
                          <input type="text" class="form-control v-input-requerido" data-inputmask='"mask": "(9{1,5})(99999999)", "clearIncomplete": "true"' data-mask="" inputmode="decimal" placeholder="Celular" id="celular" name="celular" value="<?php if(isset($usuario_datos['0']['celular'])){echo $usuario_datos['0']['celular'];}?>">
                      </div>
                  </div>
                </div>
                <!-- /.row -->
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">Domicilio</h3>
              </div>
              <div class="card-body">    
                <div class="row">
                  <div class="col-4 form-group mb-1 mt-1">
                    <small class="v-visual-edit d-none"><label class="mb-0">Provincia</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-map-marked-alt v-requerido-icon"></i></span>
                        </div>
                        <select class="form-control select2bs4 v-input-requerido v-select2" id="provincia" name="provincia">
                          <option value="<?php if(isset($usuario_datos['0']['provincia'])){echo utf8_encode($usuario_datos['0']['provincia']);}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($usuario_datos['0']['provincianom'])){echo utf8_encode($usuario_datos['0']['provincianom']);}else{echo "Provincia";} ?></option>
                          <?php echo $provinciasSelect;?>
                        </select>
                      </div>
                  </div>
                  <div class="col-4 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Partido</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-map-marked-alt v-requerido-icon"></i></span>
                        </div>
                        <select class="form-control select2bs4 v-input-requerido v-select2" id="partido" name="partido">
                          <option value="<?php if(isset($usuario_datos['0']['partido'])){echo $usuario_datos['0']['partido'];}else{echo "";} ?>" disabled selected class="bg-secondary partidosOpt1"><?php if(isset($usuario_datos['0']['partidonom'])){echo $usuario_datos['0']['partidonom'];}else{echo "Partido";} ?></option>
                        <?php if(isset($partidosSelect)){echo $partidosSelect;} ?>
                        </select>
                      </div>
                  </div>
                  <div class="col-4 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Localidad</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-map-marked-alt v-requerido-icon"></i></span>
                        </div>
                        <select class="form-control select2bs4 v-input-requerido v-select2" id="localidad" name="localidad">
                          <option value="<?php if(isset($usuario_datos['0']['localidad'])){echo $usuario_datos['0']['localidad'];}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($usuario_datos['0']['localidadnom'])){echo $usuario_datos['0']['localidadnom'];}else{echo "Localidad";} ?></option>
                            <?php if(isset($localidadesSelect)){echo $localidadesSelect;} ?>
                        </select>
                      </div>
                  </div>
                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Calle</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-road v-requerido-icon"></i></span>
                        </div>
                        <select class="form-control select2bs4 v-input-requerido v-select2" id="calle" name="calle">
                          <option value="<?php if(isset($usuario_datos['0']['calle'])){echo $usuario_datos['0']['calle'];}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($usuario_datos['0']['callenom'])){echo $usuario_datos['0']['callenom'];}else{echo "Calle";} ?></option>
                        <?php if(isset($callesSelect)){echo $callesSelect;} ?>
                        </select>
                      </div>
                  </div>
                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Altura</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-sort-numeric-up v-requerido-icon"></i></span>
                        </div>
                          <input type="text" class="form-control v-input-requerido" data-inputmask='"mask": "9{1,5}", "clearIncomplete": "true" ' data-mask="" inputmode="decimal" placeholder="Altura" id="altura" name="altura" value="<?php if(isset($usuario_datos['0']['altura'])){echo $usuario_datos['0']['altura'];}?>">
                      </div>
                  </div>
                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Piso</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-building"></i></span>
                        </div>
                          <input type="text" class="form-control" data-inputmask='"mask": "*{0,3}"' data-mask="" placeholder="Piso" id="piso" name="piso" value="<?php if(isset($usuario_datos['0']['piso'])){echo $usuario_datos['0']['piso'];}?>">
                      </div>
                  </div>
                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Depto</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-door-closed"></i></span>
                        </div>
                          <input type="text" class="form-control" data-inputmask='"mask": "*{0,3}"' data-mask="" placeholder="Depto" id="depto" name="depto" value="<?php if(isset($usuario_datos['0']['depto'])){echo $usuario_datos['0']['depto'];}?>">
                      </div>
                  </div>
                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">CP</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-mail-bulk v-requerido-icon"></i></span>
                        </div>
                          <input type="text" class="form-control v-input-requerido" data-inputmask='"mask": "9999", "clearIncomplete": "true" ' data-mask="" inputmode="decimal" placeholder="CP" id="cp" name="cp" value="<?php if(isset($usuario_datos['0']['cp'])){echo $usuario_datos['0']['cp'];}?>">
                      </div>
                  </div>
                  <div class="col-2 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Teléfono</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        </div>
                          <input type="text" class="form-control" data-inputmask='"mask": "(9{1,5})(99999999)", "clearIncomplete": "true"' data-mask="" inputmode="decimal" placeholder="Teléfono" id="telefono" name="telefono" value="<?php if(isset($usuario_datos['0']['telefono'])){echo $usuario_datos['0']['telefono'];}?>">
                      </div>
                  </div>
                </div>
                <!-- /.row -->

              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">Datos del sistema</h3>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Estado</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-sliders-h v-requerido-icon"></i></span>
                        </div>
                        <select class="form-control v-input-requerido" id="estado" name="estado">
                          <option value="<?php if(isset($usuario_datos['0']['estado'])){echo $usuario_datos['0']['estado'];}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($usuario_datos['0']['estado'])){echo $usuario_datos['0']['estado'];}else{echo "Estado";} ?></option>
                          <option value="Activo">Activo</option>
                          <option value="Desactivado">Desactivado</option>
                        </select>
                      </div>
                  </div>
                  <div class="col-1 form-group mb-1 mt-1">
                    <small class="v-visual-edit d-none"><label class="mb-0">Ingreso</label></small>
                    <div class="input-group mb-0">
                     <div class="input-group-prepend">
                       <span class="input-group-text"><i class="far fa-calendar-alt v-requerido-icon"></i></span>
                     </div>
                       <input type="text" class="form-control v-input-requerido" data-inputmask='"clearIncomplete": "true"' data-inputmask-alias="datetime" data-inputmask-inputformat="dd/mm/yyyy" data-mask="" inputmode="numeric" placeholder="Ingreso" id="ingreso" name="ingreso" value="<?php if(isset($usuario_datos['0']['ingreso'])){echo $usuario_datos['0']['ingreso'];}?>">
                    </div>
                  </div>

                  <div class="col-1 form-group mb-1 mt-1">
                    <small class="v-visual-edit d-none"><label class="mb-0">Egreso</label></small>
                    <div class="input-group mb-0">
                     <div class="input-group-prepend">
                       <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                     </div>
                       <input type="text" class="form-control" data-inputmask='"clearIncomplete": "true"' data-inputmask-alias="datetime" data-inputmask-inputformat="dd/mm/yyyy" data-mask="" inputmode="numeric" placeholder="Egreso" id="egreso" name="egreso" value="<?php if(isset($usuario_datos['0']['egreso'])){echo $usuario_datos['0']['egreso'];}?>">
                    </div>
                  </div>

                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Email</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-envelope v-requerido-icon"></i></span>
                        </div>
                        <input type="text" class="form-control v-input-requerido" placeholder="Email" data-inputmask='"mask": "[{1,20}|_|.]@*{1,20}.*{1,3}[.*{1,3}]", "clearIncomplete": "true"' data-mask="" autocomplete="rutjfkde" id="email" name="email" value="<?php if(isset($usuario_datos['0']['email'])){echo $usuario_datos['0']['email'];}?>">
                      </div>
                  </div>
                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Contraseña</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-key"></i></span>
                        </div>
                        <input type="password" class="form-control" placeholder="Contraseña" autocomplete="new-password" id="password" name="password" value="<?php if(isset($usuario_datos['0']['password'])){echo $usuario_datos['0']['password'];}?>">
                          <div class="input-group-append">
                               <span class="input-group-text"><i class="fas fa-eye-slash v-icon-accion v-ver-pass" data-accion="passOnOff" data-estado="off"></i></span>
                          </div>
                      </div>
                  </div>
                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Perfil</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-user-circle v-requerido-icon"></i></span>
                        </div>
                        <select class="form-control v-input-requerido" id="perfil" name="perfil">
                          <option value="<?php if(isset($usuario_datos['0']['perfil'])){echo $usuario_datos['0']['perfil'];}else{echo "";} ?>" disabled selected class="bg-secondary"><?php if(isset($usuario_datos['0']['perfil'])){echo $usuario_datos['0']['perfil'];}else{echo "Perfil";} ?></option>
                          <option value="Administrador">Administrador</option>
                          <option value="Administrativo">Administrativo</option>
                          <option value="Técnico">Técnico</option>
                          <option value="Tecnico Administrativo">Técnico Administrativo</option>
                          <option value="Operario">Operario</option>
                        </select>
                      </div>
                  </div>
                </div>
                <!-- /.row -->              
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">Documentos PDF <small>( Para visualizar el documento haga clic en el icono de color verde. )</small></h3>
              </div>
              <div class="card-body">              
                <div class="row">
                      <div class="input-group col-3 form-group mb-1 mt-1">
                                <small class="input-group v-visual-edit d-none"><label class="mb-0">DNI <strong><small class="text-primary">(PDF)</small></strong></label></small>
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-id-card v-documento-link <?php if(isset($usuario_datos['0']['doc_dni']) && $usuario_datos['0']['doc_dni'] != ''){echo 'text-success';}?>"></i></span>
                                </div>
                                <div class="custom-file">
                                      <input type="file" class="custom-file-input v-archivos-admitidos-pdf" name="doc_dni_v" id="doc_dni_v" value="">
                                      <label class="custom-file-label" for="exampleInputFile"><?php if(isset($usuario_datos['0']['doc_dni']) && $usuario_datos['0']['doc_dni'] != ''){echo $usuario_datos['0']['doc_dni'];}else{echo 'Seleccionar archivo';}?></label>
                                      <input type="hidden" id="doc_dni" name="doc_dni" value="<?php if(isset($usuario_datos['0']['doc_dni']) && $usuario_datos['0']['doc_dni'] != ''){echo $usuario_datos['0']['doc_dni'];}?>">
                                </div><button type="button" class="ml-1 p-0 border rounded v-clear-doc"><i class="p-2 fa-solid fa-eraser"></i></button>
                      </div>  
                      <div class="input-group col-3 form-group mb-1 mt-1">
                                <small class="input-group v-visual-edit d-none"><label class="mb-0">CUIL <strong><small class="text-primary">(PDF)</small></strong></label></small>
                                <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-file-invoice v-documento-link <?php if(isset($usuario_datos['0']['doc_cuil']) && $usuario_datos['0']['doc_cuil'] != ''){echo 'text-success';}?>"></i></span>
                                </div>
                                <div class="custom-file">
                                      <input type="file" class="custom-file-input v-archivos-admitidos-pdf" name="doc_cuil_v" id="doc_cuil_v" data-browse="Buscar" value="">
                                      <label class="custom-file-label" for="exampleInputFile"><?php if(isset($usuario_datos['0']['doc_cuil']) && $usuario_datos['0']['doc_cuil'] != ''){echo $usuario_datos['0']['doc_cuil'];}else{echo 'Seleccionar archivo';}?></label>
                                      <input type="hidden" id="doc_cuil" name="doc_cuil" value="<?php if(isset($usuario_datos['0']['doc_cuil']) && $usuario_datos['0']['doc_cuil'] != ''){echo $usuario_datos['0']['doc_cuil'];}?>">
                                </div><button type="button" class="ml-1 p-0 border rounded v-clear-doc"><i class="p-2 fa-solid fa-eraser"></i></button>
                      </div> 
                      <div class="input-group col-3 form-group mb-1 mt-1">
                                <small class="input-group v-visual-edit d-none"><label class="mb-0">AFIP (alta/baja) <strong><small class="text-primary">(PDF)</small></strong></label></small>
                                <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-file-invoice v-documento-link <?php if(isset($usuario_datos['0']['doc_afip']) && $usuario_datos['0']['doc_afip'] != ''){echo 'text-success';}?>"></i></span>
                                </div>
                                <div class="custom-file">
                                      <input type="file" class="custom-file-input v-archivos-admitidos-pdf" name="doc_afip-v" id="doc_afip-v" data-browse="Buscar" value="">
                                      <label class="custom-file-label" for="exampleInputFile"><?php if(isset($usuario_datos['0']['doc_afip']) && $usuario_datos['0']['doc_afip'] != ''){echo $usuario_datos['0']['doc_afip'];}else{echo 'Seleccionar archivo';}?></label>
                                      <input type="hidden" id="doc_afip" name="doc_afip" value="<?php if(isset($usuario_datos['0']['doc_afip']) && $usuario_datos['0']['doc_afip'] != ''){echo $usuario_datos['0']['doc_afip'];}?>">
                                </div><button type="button" class="ml-1 p-0 border rounded v-clear-doc"><i class="p-2 fa-solid fa-eraser"></i></button>
                      </div> 
                      <div class="input-group col-3 form-group mb-1 mt-1">
                                <small class="input-group v-visual-edit d-none"><label class="mb-0">Foto 4x4 <strong><small class="text-primary">(.jpg, .jpeg, .png)</small></strong></label></small>
                                <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user v-documento-link <?php if(isset($usuario_datos['0']['doc_foto']) && $usuario_datos['0']['doc_foto'] != ''){echo 'text-success';}?>"></i></span>
                                </div>
                                <div class="custom-file">
                                      <input type="file" class="custom-file-input v-archivos-admitidos-jpg" name="doc_foto_v" id="doc_foto_v" data-browse="Buscar" value="">
                                      <label class="custom-file-label" for="exampleInputFile"><?php if(isset($usuario_datos['0']['doc_foto']) && $usuario_datos['0']['doc_foto'] != ''){echo $usuario_datos['0']['doc_foto'];}else{echo 'Seleccionar archivo';}?></label>
                                      <input type="hidden" id="doc_foto" name="doc_foto" value="<?php if(isset($usuario_datos['0']['doc_foto']) && $usuario_datos['0']['doc_foto'] != ''){echo $usuario_datos['0']['doc_foto'];}?>">
                                </div><button type="button" class="ml-1 p-0 border rounded v-clear-doc"><i class="p-2 fa-solid fa-eraser"></i></button>
                      </div>
                      <div class="input-group col-3 form-group mb-1 mt-1">
                                <small class="input-group v-visual-edit d-none"><label class="mb-0">Recibos <strong><small class="text-primary">(PDF)</small></strong></label></small>
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-file-invoice-dollar v-documento-link <?php if(isset($usuario_datos['0']['doc_recibos']) && $usuario_datos['0']['doc_recibos'] != ''){echo 'text-success';}?>"></i></span>
                                </div>
                                <div class="custom-file">
                                      <input type="file" class="custom-file-input v-archivos-admitidos-pdf" name="doc_recibos-v" id="doc_recibos-v" data-browse="Buscar" value="">
                                      <label class="custom-file-label" for="exampleInputFile"><?php if(isset($usuario_datos['0']['doc_recibos']) && $usuario_datos['0']['doc_recibos'] != ''){echo $usuario_datos['0']['doc_recibos'];}else{echo 'Seleccionar archivo';}?></label>
                                      <input type="hidden" id="doc_recibos" name="doc_recibos" value="<?php if(isset($usuario_datos['0']['doc_recibos']) && $usuario_datos['0']['doc_recibos'] != ''){echo $usuario_datos['0']['doc_recibos'];}?>">
                                </div><button type="button" class="ml-1 p-0 border rounded v-clear-doc"><i class="p-2 fa-solid fa-eraser"></i></button>
                      </div>  
                      <div class="input-group col-3 form-group mb-1 mt-1">
                                <small class="input-group v-visual-edit d-none"><label class="mb-0">Apto médico <strong><small class="text-primary">(PDF)</small></strong></label></small>
                                <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-file-medical v-documento-link <?php if(isset($usuario_datos['0']['doc_apto_medico']) && $usuario_datos['0']['doc_apto_medico'] != ''){echo 'text-success';}?>"></i></span>
                                </div>
                                <div class="custom-file">
                                      <input type="file" class="custom-file-input v-archivos-admitidos-pdf" name="doc_apto_medico-v" id="doc_apto_medico-v" data-browse="Buscar" value="">
                                      <label class="custom-file-label" for="exampleInputFile"><?php if(isset($usuario_datos['0']['doc_apto_medico']) && $usuario_datos['0']['doc_apto_medico'] != ''){echo $usuario_datos['0']['doc_apto_medico'];}else{echo 'Seleccionar archivo';}?></label>
                                      <input type="hidden" id="doc_apto_medico" name="doc_apto_medico" value="<?php if(isset($usuario_datos['0']['doc_apto_medico']) && $usuario_datos['0']['doc_apto_medico'] != ''){echo $usuario_datos['0']['doc_apto_medico'];}?>">
                                </div><button type="button" class="ml-1 p-0 border rounded v-clear-doc"><i class="p-2 fa-solid fa-eraser"></i></button>
                      </div> 
                      <div class="input-group col-3 form-group mb-1 mt-1">
                                <small class="input-group v-visual-edit d-none"><label class="mb-0">Título(s) <strong><small class="text-primary">(PDF)</small></strong></label></small>
                                <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-certificate v-documento-link <?php if(isset($usuario_datos['0']['doc_titulos']) && $usuario_datos['0']['doc_titulos'] != ''){echo 'text-success';}?>"></i></span>
                                </div>
                                <div class="custom-file">
                                      <input type="file" class="custom-file-input v-archivos-admitidos-pdf" name="doc_titulos-v" id="doc_titulos-v" data-browse="Buscar" value="">
                                      <label class="custom-file-label" for="exampleInputFile"><?php if(isset($usuario_datos['0']['doc_titulos']) && $usuario_datos['0']['doc_titulos'] != ''){echo $usuario_datos['0']['doc_titulos'];}else{echo 'Seleccionar archivo';}?></label>
                                      <input type="hidden" id="doc_titulos" name="doc_titulos" value="<?php if(isset($usuario_datos['0']['doc_titulos']) && $usuario_datos['0']['doc_titulos'] != ''){echo $usuario_datos['0']['doc_titulos'];}?>">
                                </div><button type="button" class="ml-1 p-0 border rounded v-clear-doc"><i class="p-2 fa-solid fa-eraser"></i></button>
                      </div> 
                      <div class="input-group col-3 form-group mb-1 mt-1">
                                <small class="input-group v-visual-edit d-none"><label class="mb-0">Vacunas <strong><small class="text-primary">(PDF)</small></strong></label></small>
                                <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-syringe v-documento-link <?php if(isset($usuario_datos['0']['doc_vacunas']) && $usuario_datos['0']['doc_vacunas'] != ''){echo 'text-success';}?>"></i></span>
                                </div>
                                <div class="custom-file">
                                      <input type="file" class="custom-file-input v-archivos-admitidos-pdf" name="doc_vacunas-v" id="doc_vacunas-v" data-browse="Buscar" value="">
                                      <label class="custom-file-label" for="exampleInputFile"><?php if(isset($usuario_datos['0']['doc_vacunas']) && $usuario_datos['0']['doc_vacunas'] != ''){echo $usuario_datos['0']['doc_vacunas'];}else{echo 'Seleccionar archivo';}?></label>
                                      <input type="hidden" id="doc_vacunas" name="doc_vacunas" value="<?php if(isset($usuario_datos['0']['doc_vacunas']) && $usuario_datos['0']['doc_vacunas'] != ''){echo $usuario_datos['0']['doc_vacunas'];}?>">
                                </div><button type="button" class="ml-1 p-0 border rounded v-clear-doc"><i class="p-2 fa-solid fa-eraser"></i></button>
                      </div>
                      <div class="input-group col-3 form-group mb-1 mt-1">
                                <small class="input-group v-visual-edit d-none"><label class="mb-0">Cuenta banco <strong><small class="text-primary">(PDF)</small></strong></label></small>
                                <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-money-check-alt v-documento-link <?php if(isset($usuario_datos['0']['doc_cuenta_banco']) && $usuario_datos['0']['doc_cuenta_banco'] != ''){echo 'text-success';}?>"></i></span>
                                </div>
                                <div class="custom-file">
                                      <input type="file" class="custom-file-input v-archivos-admitidos-pdf" name="doc_cuenta_banco_v" id="doc_cuenta_banco_v" data-browse="Buscar" value="">
                                      <label class="custom-file-label" for="exampleInputFile"><?php if(isset($usuario_datos['0']['doc_cuenta_banco']) && $usuario_datos['0']['doc_cuenta_banco'] != ''){echo $usuario_datos['0']['doc_cuenta_banco'];}else{echo 'Seleccionar archivo';}?></label>
                                      <input type="hidden" id="doc_cuenta_banco" name="doc_cuenta_banco" value="<?php if(isset($usuario_datos['0']['doc_cuenta_banco']) && $usuario_datos['0']['doc_cuenta_banco'] != ''){echo $usuario_datos['0']['doc_cuenta_banco'];}?>">
                                </div><button type="button" class="ml-1 p-0 border rounded v-clear-doc"><i class="p-2 fa-solid fa-eraser"></i></button>
                      </div>
                      <div class="input-group col-3 form-group mb-1 mt-1">
                                <small class="input-group v-visual-edit d-none"><label class="mb-0">Licencia de conducir <strong><small class="text-primary">(PDF)</small></strong></label></small>
                                <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-id-card v-documento-link <?php if(isset($usuario_datos['0']['doc_licencia_conducir']) && $usuario_datos['0']['doc_licencia_conducir'] != ''){echo 'text-success';}?>"></i></span>
                                </div>
                                <div class="custom-file">
                                      <input type="file" class="custom-file-input v-archivos-admitidos-pdf" name="doc_licencia_conducir_v" id="doc_licencia_conducir_v" data-browse="Buscar" value="" >
                                      <label class="custom-file-label" for="exampleInputFile"><?php if(isset($usuario_datos['0']['doc_licencia_conducir']) && $usuario_datos['0']['doc_licencia_conducir'] != ''){echo $usuario_datos['0']['doc_licencia_conducir'];}else{echo 'Seleccionar archivo';}?></label>
                                      <input type="hidden" id="doc_licencia_conducir" name="doc_licencia_conducir" value="<?php if(isset($usuario_datos['0']['doc_licencia_conducir']) && $usuario_datos['0']['doc_licencia_conducir'] != ''){echo $usuario_datos['0']['doc_licencia_conducir'];}?>">
                                </div><button type="button" class="ml-1 p-0 border rounded v-clear-doc"><i class="p-2 fa-solid fa-eraser"></i></button>
                      </div>                                                                                              
                      <div class="input-group col-3 form-group mb-1 mt-1">
                                <small class="input-group v-visual-edit d-none"><label class="mb-0">Capacitación <strong><small class="text-primary">(PDF)</small></strong></label></small>
                                <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-file-invoice v-documento-link <?php if(isset($usuario_datos['0']['doc_capacitacion']) && $usuario_datos['0']['doc_capacitacion'] != ''){echo 'text-success';}?>"></i></span>
                                </div>
                                <div class="custom-file">
                                      <input type="file" class="custom-file-input v-archivos-admitidos-pdf" name="doc_capacitacion_v" id="doc_capacitacion_v" data-browse="Buscar" value="" >
                                      <label class="custom-file-label" for="exampleInputFile"><?php if(isset($usuario_datos['0']['doc_capacitacion']) && $usuario_datos['0']['doc_capacitacion'] != ''){echo $usuario_datos['0']['doc_capacitacion'];}else{echo 'Seleccionar archivo';}?></label>
                                      <input type="hidden" id="doc_capacitacion" name="doc_capacitacion" value="<?php if(isset($usuario_datos['0']['doc_capacitacion']) && $usuario_datos['0']['doc_capacitacion'] != ''){echo $usuario_datos['0']['doc_capacitacion'];}?>">
                                </div><button type="button" class="ml-1 p-0 border rounded v-clear-doc"><i class="p-2 fa-solid fa-eraser"></i></button>
                      </div> 
                      <div class="input-group col-3 form-group mb-1 mt-1">
                                <small class="input-group v-visual-edit d-none"><label class="mb-0">E.P.P. <strong><small class="text-primary">(PDF)</small></strong></label></small>
                                <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-file-invoice v-documento-link <?php if(isset($usuario_datos['0']['doc_erp']) && $usuario_datos['0']['doc_erp'] != ''){echo 'text-success';}?>"></i></span>
                                </div>
                                <div class="custom-file">
                                      <input type="file" class="custom-file-input v-archivos-admitidos-pdf" name="doc_erp_v" id="doc_erp_v" data-browse="Buscar" value="" >
                                      <label class="custom-file-label" for="exampleInputFile"><?php if(isset($usuario_datos['0']['doc_erp']) && $usuario_datos['0']['doc_erp'] != ''){echo $usuario_datos['0']['doc_erp'];}else{echo 'Seleccionar archivo';}?></label>
                                      <input type="hidden" id="doc_erp" name="doc_erp" value="<?php if(isset($usuario_datos['0']['doc_erp']) && $usuario_datos['0']['doc_erp'] != ''){echo $usuario_datos['0']['doc_erp'];}?>">
                                </div><button type="button" class="ml-1 p-0 border rounded v-clear-doc"><i class="p-2 fa-solid fa-eraser"></i></button>
                      </div>                       
                </div>
                <!-- /.row -->              
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
            <div class="row d-flex text-center justify-content-center pr-1">
              <button type="submit" class="col-1 btn btn-primary btn-block m-2 v-alta-edit d-none" data-accion="guardar"><i class="fa fa-plus-circle"></i> Guardar</button>
              <button onclick="window.location.href='listado_personal.php'" type="button" class="col-1 btn btn-success btn-block m-2 v-edit"><i class="fa fa-arrow-circle-left"></i> Volver</button>
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
<!-- InputMask -->
<script src="../05-plugins/moment/moment.min.js"></script>
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
     inputEmptyDetect("input, select");
    }
  });

  $(function () {
  $.validator.setDefaults({
    submitHandler: function () {
      usuariosGuardar();
    }
  });

  $('#currentForm').validate({
    rules: {
      apellidos: {
        required: true,
      },
      nombres: {
        required: true,
      },
      tipo_documento: {
        required: true,
      },
      nro_documento: {
        required: true,
        minlength: 8,
      },
      cuil: {
        required: true,
      },
      nacionalidad: {
        required: true,
      },
      nacimiento: {
        required: true,
      },
      estado_civil: {
        required: true,
      },
      celular: {
        required: true,
      },
      provincia: {
        required: true,
      },
      partido: {
        required: true,
      },      
      localidad: {
        required: true,
      },
      calle: {
        required: true,
      },
      altura: {
        required: true,
      },
      cp: {
        required: true,
      },
      estado: {
        required: true,
      },
      ingreso: {
        required: true,
      },
      email: {
        required: true,
        email: true,
        remote: {
            url:  "../04-modelo/usuariosChekEmailModel.php",
            type: "post",
            data: {
             ajax: "on",
             accion: "check",
             idUsuario: '<?php if(isset($usuario_datos['0']['id_usuario'])){echo $usuario_datos['0']['id_usuario'];}?>"',
            }
        }
      },
      password: {
        minlength: 8,
      },
      perfil: {
        required: true,
      },
      // doc_dni: {
      //   url: true,
      // },
      // doc_cuil: {
      //   url: true,
      // },
      // doc_afip: {
      //   url: true,
      // },
      // doc_foto: {
      //   url: true,
      // },
      // doc_recibos: {
      //   url: true,
      // },
      // doc_apto_medico: {
      //   url: true,
      // },
      // doc_titulos: {
      //   url: true,
      // },
      // doc_vacunas: {
      //   url: true,
      // },
      // doc_cuenta_banco: {
      //   url: true,
      // },
      // doc_licencia_conducir: {
      //   url: true,
      // },
    },
    messages: {
      apellidos: {
        required: "Por favor complete el campo apellido(s)",
      },
      nombres: {
        required: "Por favor complete el campo nombres(s)",
      },
      tipo_documento: {
        required: "Por favor complete el campo tipo de documento",
      },
      nro_documento: {
        required: "Por favor complete el campo nro. de documento",
      },
      cuil: {
        required: "Por favor complete el campo CUIL",
      },
      nacionalidad: {
        required: "Por favor complete el campo nacionalidad",
      },
      nacimiento: {
        required: "Por favor complete el campo fecha de nacimiento",
      },
      estado_civil: {
        required: "Por favor complete el campo estado civil",
      },
      celular: {
        required: "Por favor complete el campo celular",
      },
      provincia: {
        required: "Por favor complete el campo provincia",
      },
      partido: {
        required: "Por favor complete el campo partido",
      },      
      localidad: {
        required: "Por favor complete el campo localidad",
      },
      calle: {
        required: "Por favor complete el campo calle",
      },
      altura: {
        required: "Completar este campo",
      },
      cp: {
        required: "Completar este campo",
      },
      estado: {
        required: "Completar este campo",
      },
      ingreso: {
        required: "Completar este campo",
      },
      email: {
        required: "Por favor complete el campo email",
        email: "El formato del email no es invalido",      
      },
      password: {
        maxlength: "La contraseña admite como máximo hasta 15 caracteres",
        minlength: "La contraseña admite como mínimo hasta 8 caracteres",
      },
      perfil: {
        required: "Por favor complete el campo perfil",
      },
      // doc_dni: {
      //   url: "Ingrese una url con el formato https://...",
      // },
      // doc_cuil: {
      //   url: "Ingrese una url con el formato https://...",
      // },
      // doc_afip: {
      //   url: "Ingrese una url con el formato https://...",
      // },
      // doc_foto: {
      //   url: "Ingrese una url con el formato https://...",
      // },
      // doc_recibos: {
      //   url: "Ingrese una url con el formato https://...",
      // },
      // doc_apto_medico: {
      //   url: "Ingrese una url con el formato https://...",
      // },
      // doc_titulos: {
      //   url: "Ingrese una url con el formato https://...",
      // },
      // doc_vacunas: {
      //   url: "Ingrese una url con el formato https://...",
      // },
      // doc_cuenta_banco: {
      //   url: "Ingrese una url con el formato https://...",
      // },
      // doc_licencia_conducir: {
      //   url: "Ingrese una url con el formato https://...",
      // },
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
<script src="../07-funciones_js/usuariosAcciones.js"></script>
<!-- Guarda usuarios en la base -->
<script src="../07-funciones_js/usuariosGuardar.js"></script>
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


$(".v-clear-doc").click(function() {

        // Encontrar el div padre del botón
        var divPadre = $(this).closest('.input-group');

        // Encontrar los campos input dentro del div padre y limpiar sus valores
        divPadre.find('input[type=hidden]').val('');
        divPadre.find('input[type=file]').val('');
        divPadre.find('.custom-file-label').text('Seleccionar archivo');
});



</script>
</body>
</html>
