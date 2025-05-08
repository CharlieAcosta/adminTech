<?php  
session_start();
define('BASE_URL', $_SESSION["base_url"]);
include_once '../04-modelo/conectDB.php'; //conecta a la base de datos
include_once '../06-funciones_php/funciones.php'; //conecta a la base de datos
include_once '../03-controller/materialesController.php'; //conecta a la base de datos
//var_dump($_SESSION); die(); // [DEBUG PERMANENTE]

$id=""; $visualiza=""; $pdf="";
$usuario_sesionado = $_SESSION["usuario"];

if(isset($_GET['id']) && isset($_GET['acci'])){
  $id = $_GET['id'];
  if($_GET['acci'] == "v"){$visualiza="on";}
  if($_GET['acci'] == "pdf"){$pdf="on";}

  $datos = modGetMaterialById($id, 'php');
  $datos = $datos[0];
  //var_dump($datos); die(); // [DEBUG PERMANENTE]
//echo utf8_encode( $usuario_datos['0']['provincia'] ); die();
}else{$datos = array();}

// solo si es edición

?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title class="v-alta d-none">ADMINTECH | Alta de material</title>
  <title class="v-visual d-none">ADMINTECH | Visualización de material</title>
  <title class="v-edit d-none">ADMINTECH | Edición de material</title>

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
            <h1><strong class="v-alta d-none">Alta de material</strong><strong class="v-visual d-none">Visualización de material</strong><strong class="v-edit d-none">Edición de material</strong></h1>
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
    <input type="hidden" id="log_usuario_id" name="log_usuario_id" data-visualiza="<?php echo $visualiza; ?>" 
    value="<?php echo arrayPrintValue(null, $usuario_sesionado, 'id_usuario', null, null); ?>">

    <input type="hidden" id="log_accion" name="log_accion" data-visualiza="" 
    value="<?php echo isset($datos['id_material']) ? 'editar' : 'alta'; ?>">


    <section class="content">
      <div class="container-fluid">

<!-- /.card start -------------------------------------------------------------------------------------------------------------------------------------------------- -->
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">Datos del Material <?php echo arrayPrintValue(null, $datos, 'material', null, null); ?></h3>
              </div>
              <div class="card-body">
                <input type="hidden" class="v-id" id="id_material" name="id_material" data-visualiza="<?php echo $visualiza; ?>" 
                value="<?php echo arrayPrintValue(null, $datos, 'id_material', null, null); ?>">

                <!-- start row -->
                <div class="row">

                  <div class="col-3 form-group mb-0 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Producto</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-flask-vial v-requerido-icon"></i></span>
                        </div>
                          <input type="text" class="form-control v-input-requerido texto-capital" data-inputmask='' data-mask="" inputmode="" data-cuit="" placeholder="Producto" id="producto" name="producto" 
                          value="<?php echo utf8_encode(arrayPrintValue(null, $datos, 'producto', null, null)); ?>">
                      </div>
                  </div>

                  <div class="col-3 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Marca</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-registered v-requerido-icon"></i>
                        </div>
                        <input type="text" class="form-control v-input-requerido" placeholder="Marca" id="marca" name="marca" 
                        value="<?php echo utf8_encode(arrayPrintValue(null, $datos, 'marca', null, null)); ?>">
                      </div>
                  </div>          

                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Unidad de venta</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-box v-requerido-icon"></i>
                        </div>            
                        <select class="form-control v-input-requerido" name="unidad_venta" id="unidad_venta">
                          <?php echo optionsGetEnum(DB_NAME, 'materiales', 'unidad_venta', 'U.Venta', arrayPrintValue(null, $datos, 'unidad_venta', null, null));?>
                        </select>  
                      </div>
                  </div>   

                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Contenido</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-scale-unbalanced-flip v-requerido-icon"></i>
                        </div>
                        <input type="text" class="form-control v-input-requerido v-calcular" placeholder="Contenido" id="contenido" name="contenido" 
                        value="<?php echo arrayPrintValue(null, $datos, 'contenido', null, null); ?>" data-inputmask='"mask": "9{1,10}[.99]"' data-mask="" inputmode="decimal">
                      </div>
                  </div>

                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Unidad de medida</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-prescription-bottle v-requerido-icon"></i>
                        </div>
                        <select class="form-control v-input-requerido" name="unidad_medida" id="unidad_medida">
                          <?php echo optionsGetEnum(DB_NAME, 'materiales', 'unidad_medida', 'U.Medida', arrayPrintValue(null, $datos, 'unidad_medida', null, null));?>
                        </select> 
                      </div>
                  </div>

                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Rendimiento</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-arrow-up-wide-short v-requerido-icon"></i>
                        </div>
                        <input type="text" class="form-control v-input-requerido v-calcular" placeholder="Rendimiento" id="rendimiento" name="rendimiento" 
                        value="<?php echo arrayPrintValue(null, $datos, 'rendimiento', null, null); ?>" data-inputmask='"mask": "9{1,10}[.99]"' data-mask="" inputmode="decimal">
                      </div>
                  </div>

                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">U. Rendimiento</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-prescription-bottle v-requerido-icon"></i>
                        </div>
                        <select class="form-control v-input-requerido" name="unidad_rendimiento" id="unidad_rendimiento">
                          <?php echo optionsGetEnum(DB_NAME, 'materiales', 'unidad_rendimiento', 'U.Rendimiento', arrayPrintValue(null, $datos, 'unidad_rendimiento', null, null));?>
                        </select> 
                      </div>
                  </div>

                  <div class="col-1 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Estado</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-sliders-h v-requerido-icon"></i>
                        </div>
                        <select class="form-control v-input-requerido" name="estado_material" id="estado_material">
                          <?php echo optionsGetEnum(DB_NAME, 'materiales', 'estado_material', 'Estado', arrayPrintValue(null, $datos, 'estado_material', null, null));?>
                        </select> 

                      </div>
                  </div>

                  <div class="col-12 form-group mb-1 mt-1">
                      <small class="v-visual-edit d-none"><label class="mb-0">Descripción corta</label></small>
                      <div class="input-group mb-0">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-pen-to-square v-requerido-icon"></i>
                        </div>
                        <input type="text" class="form-control v-input-requerido" placeholder="Descripción corta" id="descripcion_corta" name="descripcion_corta" value="<?php echo utf8_encode(arrayPrintValue(null, $datos, 'descripcion_corta', null, null)); ?>">
                      </div>
                  </div>

                  <div class="col-12 form-group mb-1 mt-1">
                        <small class="v-visual-edit d-none"><label class="mb-0">Descripción técnica</label></small>
                        <div class="input-group mb-0">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-pen-to-square v-requerido-icon"></i></span>
                          </div>
                          <textarea type="text" rows="5" class="form-control v-input-requerido" placeholder="Descripción técnica" id="descripcion_tecnica" name="descripcion_tecnica"><?php echo utf8_encode(arrayPrintValue(null, $datos, 'descripcion_tecnica', null, null)); ?></textarea>
                        </div>
                  </div>

                  <div class="col-12 form-group mb-1 mt-1">
                        <small class="v-visual-edit d-none"><label class="mb-0">Nota</label></small>
                        <div class="input-group mb-0">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                          </div>
                          <textarea type="text" rows="5" class="form-control" placeholder="Nota" id="nota_material" name="nota_material"><?php echo utf8_encode(arrayPrintValue(null, $datos, 'nota_material', null, null)); ?></textarea>
                        </div>
                  </div>

                </div>
                 <!-- end row -->

                <!-- start row -->
                <div class="row">

                    <div class="col-4 form-group mb-1 mt-1">
                        <small class="v-visual-edit d-none"><label class="mb-0">Precio unidad de venta</label></small>
                        <div class="input-group mb-0">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-dollar-sign v-requerido-icon"></i>
                          </div>
                          <input type="text" class="form-control v-input-requerido form-control-lg v-calcular" placeholder="Precio de unidad de venta" id="precio_unidad_venta" name="precio_unidad_venta" 
                          value="<?php echo arrayPrintValue(null, $datos, 'precio_unidad_venta', null, null); ?>" data-inputmask='"mask": "9{1,10}[.99]"' data-mask="" inputmode="decimal">
                        </div>
                    </div>

                    <div class="col-4 form-group mb-1 mt-1">
                        <small class="v-visual-edit d-none"><label class="mb-0">Precio unitario</label></small>
                        <div class="input-group mb-0">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-dollar-sign v-requerido-icon"></i>
                          </div>
                          <input type="text" class="form-control v-input-requerido form-control-lg v-disabled" placeholder="Precio unitario" id="precio_unitario" name="precio_unitario" 
                          value="<?php echo arrayPrintValue(null, $datos, 'precio_unitario', null, null); ?>" data-inputmask='' data-mask="" inputmode="decimal">
                        </div>
                    </div>

                    <div class="col-4 form-group mb-1 mt-1">
                        <small class="v-visual-edit d-none"><label class="mb-0">Precio por rendimiento</label></small>
                        <div class="input-group mb-0">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-dollar-sign v-requerido-icon"></i>
                          </div>
                          <input type="text" class="form-control v-input-requerido form-control-lg v-disabled" placeholder="Precio por rendimiento" id="precio_rendimiento" name="precio_rendimiento" 
                          value="<?php echo arrayPrintValue(null, $datos, 'precio_rendimiento', null, null); ?>" data-inputmask='' data-mask="" inputmode="decimal">
                        </div>
                    </div>

                 </div>
                 <!-- end row -->

              </div>
              <!-- en card-body -->
            </div>
<!-- end card -------------------------------------------------------------------------------------------------------------------------------------- -->

            <div class="row d-flex text-center justify-content-center pr-1">
              <button type="submit" class="col-1 btn btn-primary btn-block m-2 v-alta-edit d-none" data-accion="guardar"><i class="fa fa-plus-circle"></i> Guardar</button>
              <button onclick="window.location.href='materiales_listado.php'" type="button" class="col-1 btn btn-success btn-block m-2 v-edit"><i class="fa fa-arrow-circle-left"></i> Volver</button>
              <button type="button" class="col-1 btn btn-warning btn-block m-2 v-alta-edit d-none v-accion-cancelar" data-accion="cancelar"><i class="fa fa-ban"></i> Cancelar</button>
              <!-- <button type="button" class="col-1 btn btn-danger btn-block m-2 v-edit v-accion-eliminar d-none" data-accion="eliminar"><i class="fa fa-trash"></i> Eliminar</button> -->
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
  });

  $(function () {
  $.validator.setDefaults({
    submitHandler: function () {
      if($(".v-id").val() == '' ||  $(".v-id").val() == null){
            //alert('Alta');
            simpleInsertInDB('../06-funciones_php/funciones.php', 'materiales', NombresCampos('currentForm'), ValoresCampos('currentForm'))
             .then(data => {
             // Manejar los datos obtenidos cuando la promesa se resuelva con éxito
             //   //console.log(data);
               if(data == 1)
                 {
                   sAlertDialog(
                       'success',   
                       '<h3 class="text-success"><b>EL MATERIAL SE REGISTRO CORRECTAMENTE<b></h3>',
                       '<h5>¿Desea seguir ingresando materiales?</h5>', 
                       'SI',            
                       'success',         
                       'NO',
                       'dark',
                       function(){ window.location.href = "../01-views/materiales_form.php"; },
                       function(){ window.location.href = "../01-views/materiales_listado.php"; }
                   );
                 }
               else
                 {
                   sAlertAutoClose(
                    'error',                  
                    '<h3 class="text-danger"><b>NO SE PUDO REGISTRAR EL MATERIAL<b></h3>',
                    '<h5>Vuelva a intentarlo en unos minutos.<br> Si persiste este mensaje oprima <b>cancelar</b> y comuniquese con el administrador.</h5>',
                    6000  
                   );
                 }
             })
             .catch(error => {
               // Manejar errores si la promesa se rechaza
               //console.error(error);
                   sAlertAutoClose(
                    'error',                  
                    '<h3 class="text-danger"><b>NO SE PUDO REGISTRAR EL MATERIAL<b></h3>',
                    '<h5>Vuelva a intentarlo en unos minutos.<br> Si persiste este mensaje oprima <b>cancelar</b> y comuniquese con el administrador.</h5>',
                    6000  
                   );
             });

      }else{
              simpleUpdateInDB(
                '../06-funciones_php/funciones.php',
                'materiales',
                serializeForm('currentForm'),
                {columna: 'id_material', condicion: '=', valorCompara: $(".v-id").val()}
             ).then(data => {
             // Manejar los datos obtenidos cuando la promesa se resuelva con éxito
             //   //console.log(data);
               if(data == true)
                 {
                   sAlertDialog(
                       'success',   
                       '<h3 class="text-success"><b>EL MATERIAL SE MODIFICO CORRECTAMENTE<b></h3>',
                       '<h5>¿Desea seguir modificando este material?</h5>', 
                       'SI',            
                       'success',         
                       'NO',
                       'dark',
                       function(){},
                       function(){ window.location.href = "../01-views/materiales_listado.php"; }
                   );
                 }
               else
                 {
                   sAlertAutoClose(
                    'error',                  
                    '<h3 class="text-danger"><b>NO SE PUDO MODIFICAR EL MATERIAL<b></h3>',
                    '<h5>Vuelva a intentarlo en unos minutos.<br> Si persiste este mensaje oprima <b>cancelar</b> y comuniquese con el administrador.</h5>',
                    6000  
                   );
                 }
             })
             .catch(error => {
               // Manejar errores si la promesa se rechaza
               //console.error(error);
                   sAlertAutoClose(
                    'error',                  
                    '<h3 class="text-danger"><b>NO SE PUDO REGISTRAR EL MATERIAL<b></h3>',
                    '<h5>Vuelva a intentarlo en unos minutos.<br> Si persiste este mensaje oprima <b>cancelar</b> y comuniquese con el administrador.</h5>',
                    6000  
                   );
             });

      }

    } // END - submitHandler: function ()
  
  }); // END - $.validator.setDefaults

  $('#currentForm').validate({
    rules: {
      producto: {
        required: true
      },
      marca: {
        required: true
      },
      descripcion_corta: {
        required: true
      },
      descripcion_tecnica: {
        required: true
      },
      unidad_venta: {
        required: true
      },
      unidad_medida: {
        required: true
      },
      contenido: {
        required: true,
        min: 0.01
      },
      rendimiento: {
        required: true,
        min: 0.01
      },
      unidad_rendimiento: {
        required: true
      },
      estado_material: {
        required: true
      },
      precio_unidad_venta: {
        required: true,
        min: 0.01
      }
    },

    messages: {
      producto: {
        required: "Debe completar este campo"
      },
      marca: {
        required: "Debe completar este campo"
      },
      descripcion_corta: {
        required: "Debe completar este campo"
      },
      descripcion_tecnica: {
        required: "Debe completar este campo"
      },
      unidad_venta: {
        required: "Debe completar este campo"
      },
      unidad_medida: {
        required: "Debe completar este campo"
      },
      contenido: {
        required: "Debe completar este campo",
        min: "El valor mínimo permitido es 0.01"
      },
      rendimiento: {
        required: "Debe completar este campo",
        min: "El valor mínimo permitido es 0.01"
      },
      unidad_rendimiento: {
        required: "Debe completar este campo"
      },
      estado_material: {
        required: "Debe completar este campo"
      },
      precio_unidad_venta: {
        required: "Debe completar este campo",
        min: "El valor mínimo permitido es 0.01"
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
 

  $(document).on('change',".v-calcular",function(){
  
      if( $("#precio_unidad_venta").val() !== '' && $("#precio_unidad_venta").val() !== "0" && $("#contenido").val() !== '' && $("#contenido").val() !== '0'){
        
        var preunit = parseFloat($("#precio_unidad_venta").val()) / parseFloat($("#contenido").val()); 
        preunit = preunit.toFixed(2);
        $('#precio_unitario').val(preunit).text(preunit);

      }

      if( $("#precio_unitario").val() !== '' && $("#precio_unitario").val() !== "0" && $("#rendimiento").val() !== '' && $("#rendimiento").val() !== '0'){
        
        var preunirend = parseFloat($("#precio_unitario").val()) / parseFloat($("#rendimiento").val()); 
        preunirend = preunirend.toFixed(2);
        $('#precio_rendimiento').val(preunirend).text(preunirend);

      }
  
  });


</script>

<!-- custom functions -->
<!-- funcion para saber si es un alta, visualización, edición // formatea la vista -->
<script src="../07-funciones_js/abm_detect.js"></script>
<!-- funcion para traer las calles de una provincia -->
<script src="../07-funciones_js/inputEmptyDetect.js"></script>
<!-- funcion rellenas select con valores traidos por ajax -->
<script src="../07-funciones_js/optionSelect.js"></script>
<!-- funcion para mostrar documentios -->
<script src="../07-funciones_js/muestraDocumento.js"></script>

<!-- Guarda usuarios en la base -->
<script src="../07-funciones_js/materialesAcciones.js"></script>
<!-- Guarda usuarios en la base -->
<script src="../07-funciones_js/materialesGuardar.js"></script>
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
