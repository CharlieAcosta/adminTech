<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
define('BASE_URL', $_SESSION["base_url"]);
include_once '../04-modelo/conectDB.php';
include_once '../06-funciones_php/funciones.php';
include_once '../03-controller/feriadosController.php';

include_once '../06-funciones_php/auditoria.php';
registrarNavegacion('FERIADOS - Formulario');

$id = '';
$visualiza = '';
$datos = array();
$usuario_sesionado = $_SESSION["usuario"];

if (isset($_GET['id']) && isset($_GET['acci'])) {
  $id = $_GET['id'];
  if ($_GET['acci'] == 'v') {
    $visualiza = 'on';
    registrarVisualizacion('FERIADOS | Form - Visualizacion');
  }

  $datos = modGetFeriadoById($id) ?? array();
}

$estadoActual = arrayPrintValue(null, $datos, 'estado', null, null);
$estadoTexto = $estadoActual === 'disabled' ? 'Deshabilitado' : 'Habilitado';
$idValue = htmlspecialchars((string)arrayPrintValue(null, $datos, 'id_feriado', null, null), ENT_QUOTES, 'UTF-8');
$fechaValue = htmlspecialchars((string)arrayPrintValue(null, $datos, 'fecha', null, null), ENT_QUOTES, 'UTF-8');
$descripcionValue = htmlspecialchars((string)arrayPrintValue(null, $datos, 'descripcion', null, null), ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>ADMINTECH | Feriado</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../05-plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../05-plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
  <link rel="stylesheet" href="../05-plugins/toastr/toastr.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../dist/css/custom.css">
</head>
<body class="hold-transition sidebar-collapse layout-navbar-fixed">
<div class="wrapper">

  <?php include '../01-views/layout/navbar_layout.php';?>

  <div class="content-wrapper pb-5">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><strong class="v-alta d-none">Alta de Feriado</strong><strong class="v-visual d-none">Visualizaci&oacute;n de Feriado</strong><strong class="v-edit d-none">Edici&oacute;n de Feriado</strong></h1>
          </div>
        </div>
      </div>
    </section>

    <form id="currentForm" class="form">
      <input type="hidden" id="feriado_log_usuario_id" name="feriado_log_usuario_id" data-visualiza="<?php echo $visualiza; ?>" value="<?php echo arrayPrintValue(null, $usuario_sesionado, 'id_usuario', null, null); ?>">
      <input type="hidden" id="feriado_log_accion" name="feriado_log_accion" value="<?php echo isset($datos['id_feriado']) ? 'edit' : 'alta'; ?>">
      <input type="hidden" class="v-id" id="id_feriado" name="id_feriado" data-fecha-original="<?php echo $fechaValue; ?>" value="<?php echo $idValue; ?>">

      <section class="content">
        <div class="container-fluid">
          <div class="card card-info">
            <div class="card-header">
              <h3 class="card-title">Datos del Feriado <?php echo arrayPrintValue('ID: ', $datos, 'id_feriado', null, null); ?></h3>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-3 form-group mb-0 mt-1">
                  <label class="mb-0">Fecha</label>
                  <div class="input-group mb-0">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-calendar-day v-requerido-icon text-danger"></i></span>
                    </div>
                    <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo $fechaValue; ?>">
                  </div>
                </div>

                <div class="col-6 form-group mb-0 mt-1">
                  <label class="mb-0">Descripci&oacute;n</label>
                  <div class="input-group mb-0">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-align-left v-requerido-icon text-danger"></i></span>
                    </div>
                    <input type="text" maxlength="255" class="form-control" placeholder="Descripci&oacute;n del feriado" id="descripcion" name="descripcion" value="<?php echo $descripcionValue; ?>">
                  </div>
                </div>

                <div class="col-3 form-group mb-0 mt-1">
                  <label class="mb-0">Estado</label>
                  <div class="input-group mb-0">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                    </div>
                    <input type="text" class="form-control" id="estado_texto" name="estado_texto" value="<?php echo $estadoTexto; ?>" disabled>
                    <input type="hidden" id="estado" name="estado" value="<?php echo $estadoActual !== '' ? $estadoActual : 'enabled'; ?>">
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row d-flex text-center justify-content-center pr-1">
            <?php if ($visualiza === 'on'): ?>
              <button onclick="window.location.href='feriados_listado.php'" type="button" class="col-1 btn btn-success btn-block m-2">
                <i class="fa fa-arrow-circle-left"></i> Volver
              </button>
            <?php elseif (!isset($datos['id_feriado'])): ?>
              <button type="button" class="col-1 btn btn-primary btn-block m-2 v-btn-accion" data-accion="guardar">
                <i class="fa fa-save"></i> Guardar
              </button>
              <button type="button" class="col-1 btn btn-warning btn-block m-2 v-accion-cancelar" data-accion="cancelar">
                <i class="fa fa-ban"></i> Cancelar
              </button>
            <?php else: ?>
              <button type="button" class="col-1 btn btn-primary btn-block m-2 v-btn-accion" data-accion="guardar">
                <i class="fa fa-save"></i> Guardar
              </button>
              <button type="button" class="col-1 btn btn-warning btn-block m-2 v-accion-cancelar" data-accion="cancelar">
                <i class="fa fa-ban"></i> Cancelar
              </button>
              <button onclick="window.location.href='feriados_listado.php'" type="button" class="col-1 btn btn-success btn-block m-2">
                <i class="fa fa-arrow-circle-left"></i> Volver
              </button>
            <?php endif; ?>
          </div>
        </div>
      </section>
    </form>
  </div>

  <?php include '../01-views/layout/footer_layout.php';?>
  <aside class="control-sidebar control-sidebar-dark"></aside>
</div>

<script src="../05-plugins/jquery/jquery.min.js"></script>
<script src="../05-plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../05-plugins/select2/js/select2.full.min.js"></script>
<script src="../05-plugins/select2/js/i18n/es.js"></script>
<script src="../05-plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="../05-plugins/jquery-validation/additional-methods.min.js"></script>
<script src="../05-plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="../05-plugins/toastr/toastr.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script src="../07-funciones_js/abm_detect.js"></script>
<script src="../07-funciones_js/inputEmptyDetect.js"></script>
<script src="../07-funciones_js/funciones.js"></script>
<script src="../07-funciones_js/feriados_form.js"></script>
<script src="../07-funciones_js/feriadosAcciones.js"></script>
<script src="../07-funciones_js/sAlertAutoCloseV2.js"></script>
</body>
</html>
