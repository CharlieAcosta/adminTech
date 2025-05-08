<?php  
session_start();
define('BASE_URL', $_SESSION["base_url"]);
include_once '../04-modelo/conectDB.php';
include_once '../06-funciones_php/funciones.php';
include_once '../03-controller/jornalesController.php';

include_once '../06-funciones_php/auditoria.php';
registrarNavegacion('TIPO JORNALES - Formulario');

$id=""; $visualiza=""; $datos=array();
$usuario_sesionado = $_SESSION["usuario"];

if(isset($_GET['id']) && isset($_GET['acci'])){
  $id = $_GET['id'];
  if($_GET['acci'] == "v"){
    $visualiza = "on";
    registrarVisualizacion('TIPO JORNALES | Form - Visualización');
  }

  $datos = db_select_with_filters(
    'tipo_jornales',        // tabla
    ['jornal_id'],          // columna para el WHERE
    ['='],                  // condición
    [$id],                  // valor del ID pasado al formulario
    [],                     // sin orden
    'php'                   // tipo de llamada
);

  // Siempre devolvés un array de arrays, así que accedemos al primero
  $datos = $datos[0];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>ADMINTECH | Tipo de Jornal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

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
</head>
<body class="hold-transition sidebar-collapse layout-navbar-fixed">
<div class="wrapper">

  <?php include '../01-views/layout/navbar_layout.php';?> 

  <div class="content-wrapper pb-5">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><strong class="v-alta d-none">Alta de Jornal</strong><strong class="v-visual d-none">Visualización de Jornal</strong><strong class="v-edit d-none">Edición de Jornal</strong></h1>
          </div>
        </div>
      </div>
    </section>

    <form id="currentForm" class="form">
      <input type="hidden" id="jornal_log_usuario_id" name="jornal_log_usuario_id" data-visualiza="<?php echo $visualiza; ?>" value="<?php echo arrayPrintValue(null, $usuario_sesionado, 'id_usuario', null, null); ?>">
      <input type="hidden" id="jornal_log_accion" name="jornal_log_accion" value="<?php echo isset($datos['jornal_id']) ? 'edit' : 'alta'; ?>">
      <input type="hidden" class="v-id" id="jornal_id" name="jornal_id" data-visualiza="<?php echo $visualiza; ?>" value="<?php echo arrayPrintValue(null, $datos, 'jornal_id', null, null); ?>">

      <section class="content">
        <div class="container-fluid">
          <div class="card card-info">
            <div class="card-header">
              <h3 class="card-title">Datos del Jornal <?php echo arrayPrintValue('ID: ', $datos, 'jornal_id', null, null); ?></h3>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-6 form-group mb-0 mt-1">
                  <label class="mb-0">Descripción</label>
                  <div class="input-group mb-0">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-align-left v-requerido-icon text-danger"></i></span>
                    </div>
                    <input type="text" class="form-control" placeholder="Descripción del Jornal" id="jornal_descripcion" name="jornal_descripcion" value="<?php echo arrayPrintValue(null, $datos, 'jornal_descripcion', null, null); ?>">
                  </div>
                </div>

                <div class="col-3 form-group mb-0 mt-1">
                  <label class="mb-0">Código</label>
                  <div class="input-group mb-0">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-barcode v-requerido-icon text-danger"></i></span>
                    </div>
                    <input type="text" class="form-control" placeholder="Código" id="jornal_codigo" name="jornal_codigo" value="<?php echo arrayPrintValue(null, $datos, 'jornal_codigo', null, null); ?>">
                  </div>
                </div>

                <div class="col-3 form-group mb-0 mt-1">
                  <label class="mb-0">Valor</label>
                  <div class="input-group mb-0">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-dollar-sign v-requerido-icon text-danger"></i></span>
                    </div>
                    <input type="number" step="0.01" min="0" class="form-control" placeholder="Valor" id="jornal_valor" name="jornal_valor" value="<?php echo arrayPrintValue(null, $datos, 'jornal_valor', null, null); ?>">
                  </div>
                </div>

                <div class="col-3 form-group mb-0 mt-1">
                  <label class="mb-0">Estado</label>
                  <div class="input-group mb-0">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-toggle-on v-requerido-icon text-danger"></i></span>
                    </div>
                    <select class="form-control" name="jornal_estado" id="jornal_estado">
                      <?php echo optionsGetEnum(DB_NAME, 'tipo_jornales', 'jornal_estado', 'Estado', arrayPrintValue(null, $datos, 'jornal_estado', null, null));?>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row d-flex text-center justify-content-center pr-1">
            <?php if ($visualiza === 'on'): ?>
              <!-- Solo Visualiza -->
              <button onclick="window.location.href='jornales_listado.php'" type="button" class="col-1 btn btn-success btn-block m-2">
                <i class="fa fa-arrow-circle-left"></i> Volver
              </button>
            <?php elseif (!isset($datos['jornal_id'])): ?>
              <!-- Alta -->
              <button type="button" class="col-1 btn btn-primary btn-block m-2 v-btn-accion" data-accion="guardar">
                <i class="fa fa-save"></i> Guardar
              </button>
              <button type="button" class="col-1 btn btn-warning btn-block m-2 v-accion-cancelar" data-accion="cancelar">
                <i class="fa fa-ban"></i> Cancelar
              </button>
            <?php else: ?>
              <!-- Edición -->
              <button type="button" class="col-1 btn btn-primary btn-block m-2 v-btn-accion" data-accion="guardar">
                <i class="fa fa-save"></i> Guardar
              </button>
              <button type="button" class="col-1 btn btn-warning btn-block m-2 v-accion-cancelar" data-accion="cancelar">
                <i class="fa fa-ban"></i> Cancelar
              </button>
              <button onclick="window.location.href='jornales_listado.php'" type="button" class="col-1 btn btn-success btn-block m-2">
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

<!-- Scripts -->
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
<script src="../07-funciones_js/jornales_form.js"></script> <!-- custom -->
<script src="../07-funciones_js/jornalesAcciones.js"></script> <!-- custom -->
<script src="../07-funciones_js/sAlertAutoCloseV2.js"></script> <!-- custom -->
</body>
</html>
