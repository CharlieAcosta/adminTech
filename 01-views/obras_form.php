<?php  
session_start();
define('BASE_URL', $_SESSION["base_url"]);
include_once '../04-modelo/conectDB.php'; //conecta a la base de datos
include_once '../06-funciones_php/funciones.php'; //conecta a la base de datos
include_once '../03-controller/obrasController.php'; //conecta a la base de datos
//var_dump($_SESSION); die(); // [DEBUG PERMANENTE]

include_once '../06-funciones_php/auditoria.php';
registrarNavegacion('OBRAS - Formulario ');

$id=""; $visualiza=""; $pdf="";
$usuario_sesionado = $_SESSION["usuario"];

if(isset($_GET['id']) && isset($_GET['acci'])){
  $id = $_GET['id'];
  if($_GET['acci'] == "v")
  {
    $visualiza="on";
    registrarVisualizacion('OBRAS | Form - Visualización'); // Registrar visualización en la auditoría
  }

  if($_GET['acci'] == "pdf"){$pdf="on";}

  $datos = modGetObrasById($id, 'php');
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
  <title class="v-alta d-none">ADMINTECH | Alta de obra</title>
  <title class="v-visual d-none">ADMINTECH | Visualización de obra</title>
  <title class="v-edit d-none">ADMINTECH | Edición de obra</title>

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
  
  <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC2iq03JRx9R17Yu6mzg6dD6hCflUdns7k&libraries=places&callback=initMap"></script>
  <!-- key=AIzaSyC2iq03JRx9R17Yu6mzg6dD6hCflUdns7k -->
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
            <h1><strong class="v-alta d-none">Alta de obra</strong><strong class="v-visual d-none">Visualización de obra</strong><strong class="v-edit d-none">Edición de obra</strong></h1>
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
<form id="currentForm" class="form" enctype="multipart/form-data">
    <input type="hidden" id="obra_log_usuario_id" name="obra_log_usuario_id" data-visualiza="<?php echo $visualiza; ?>"
    value="<?php echo arrayPrintValue(null, $usuario_sesionado, 'id_usuario', null, null); ?>">

    <input type="hidden" id="obra_log_accion" name="obra_log_accion" data-visualiza=""
    value="<?php echo isset($datos['obra_id']) ? 'edit' : 'alta'; ?>">

    <section class="content">
        <div class="container-fluid">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Datos de la Obra <?php echo arrayPrintValue('ID: ', $datos, 'obra_id', null, null); ?></h3>
                </div>
                <div class="card-body">
                    <input type="hidden" class="v-id" id="obra_id" name="obra_id" data-visualiza="<?php echo $visualiza; ?>"
                    value="<?php echo arrayPrintValue(null, $datos, 'obra_id', null, null); ?>">

                    <div class="row">
                        <div class="col-6 form-group mb-0 mt-1">
                            <label class="mb-0">Nombre de la Obra</label>
                            <div class="input-group mb-0">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-building v-requerido-icon"></i></span>
                                </div>
                                <input type="text" class="form-control" placeholder="Nombre de la Obra" id="obra_nombre" name="obra_nombre"
                                value="<?php echo utf8_encode(arrayPrintValue(null, $datos, 'obra_nombre', null, null)); ?>">
                            </div>
                        </div>

                        <div class="col-3 form-group mb-0 mt-1">
                            <label class="mb-0">Fecha de Inicio</label>
                            <div class="input-group mb-0">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt v-requerido-icon"></i></span>
                                </div>
                                <input type="date" class="form-control" id="obra_fecha_inicio" name="obra_fecha_inicio"
                                value="<?php echo arrayPrintValue(null, $datos, 'obra_fecha_inicio', null, null); ?>" 
                                min="<?php echo date('Y-m-d'); ?>" onkeydown="return false;">
                            </div>
                        </div>

                        <div class="col-3 form-group mb-0 mt-1">
                            <label class="mb-0">Fecha de Finalización</label>
                            <div class="input-group mb-0">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt v-requerido-icon"></i></span>
                                </div>
                                <input type="date" class="form-control" id="obra_fecha_fin" name="obra_fecha_fin"
                                value="<?php echo arrayPrintValue(null, $datos, 'obra_fecha_fin', null, null); ?>" 
                                min="" onkeydown="return false;">
                            </div>
                        </div>

                        <div class="col-3 form-group mb-0 mt-1">
                            <label class="mb-0">Estado de la Obra</label>
                            <div class="input-group mb-0">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-info-circle v-requerido-icon"></i></span>
                                </div>
                                <select class="form-control" name="obra_estado" id="obra_estado">
                                    <?php echo optionsGetEnum(DB_NAME, 'obras', 'obra_estado', 'Estado', arrayPrintValue(null, $datos, 'obra_estado', null, null));?>
                                </select>
                            </div>
                        </div>

                        <div class="col-3 form-group mb-0 mt-1">
                            <label class="mb-0">Latitud</label>
                            <div class="input-group mb-0">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt v-requerido-icon"></i></span>
                                </div>
                                <input type="text" class="form-control" placeholder="Latitud" id="obra_lat" name="obra_lat"
                                value="<?php echo arrayPrintValue(null, $datos, 'obra_lat', null, null); ?>" readonly>
                            </div>
                        </div>

                        <div class="col-3 form-group mb-0 mt-1">
                            <label class="mb-0">Longitud</label>
                            <div class="input-group mb-0">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt v-requerido-icon"></i></span>
                                </div>
                                <input type="text" class="form-control" placeholder="Longitud" id="obra_lon" name="obra_lon"
                                value="<?php echo arrayPrintValue(null, $datos, 'obra_lon', null, null); ?>" readonly>
                            </div>
                        </div>

                        <div class="col-12 form-group mb-0 mt-3">
                            <div id="map-container" style="position: relative;">
                                <div id="map" style="height: 400px;"></div>
                                <input id="pac-input" class="form-control" type="text" placeholder="Buscar ubicación" style="margin-top: 10px; position: absolute; top: 10px; left: 193px; width: 85%; z-index: 5;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row d-flex text-center justify-content-center pr-1">
                <button type="submit" class="col-1 btn btn-primary btn-block m-2 v-alta-edit d-none" data-accion="guardar"><i class="fa fa-plus-circle"></i> Guardar</button>
                <button onclick="window.location.href='obras_listado.php'" type="button" class="col-1 btn btn-success btn-block m-2 v-edit"><i class="fa fa-arrow-circle-left"></i> Volver</button>
                <button type="button" class="col-1 btn btn-warning btn-block m-2 v-alta-edit d-none v-accion-cancelar" data-accion="cancelar"><i class="fa fa-ban"></i> Cancelar</button>
            </div>
        </div>
    </section>
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
            simpleInsertInDB('../06-funciones_php/funciones.php', 'obras', NombresCampos('currentForm'), ValoresCampos('currentForm'))
             .then(data => {
             // Manejar los datos obtenidos cuando la promesa se resuelva con éxito
             //   //console.log(data);
               if(data == 1)
                 {
                   sAlertDialog(
                       'success',   
                       '<h3 class="text-success"><b>LA OBRA SE REGISTRO CORRECTAMENTE<b></h3>',
                       '<h5>¿Desea seguir ingresando obras?</h5>', 
                       'SI',            
                       'success',         
                       'NO',
                       'dark',
                       function(){ window.location.href = "../01-views/obras_form.php"; },
                       function(){ window.location.href = "../01-views/obras_listado.php"; }
                   );
                 }
               else
                 {
                   sAlertAutoClose(
                    'error',                  
                    '<h3 class="text-danger"><b>OCURRIO UN ERROR EN LA BASE DE DATOS</b></h3>',
                    '<h5>Vuelva a intentarlo en unos minutos.<br> Si persiste este mensaje oprima <b>cancelar</b> y comuniquese con el administrador.<br><b>código: (en.in)</b></h5>',
                    6000  
                   );
                 }
             })
             .catch(error => {
               // Manejar errores si la promesa se rechaza
               //console.error(error);
                   sAlertAutoClose(
                    'error',                  
                    '<h3 class="text-danger"><b>OCURRIO UN ERROR EN EL SERVIDOR</b></h3>',
                    '<h5>Vuelva a intentarlo en unos minutos.<br> Si persiste este mensaje oprima <b>cancelar</b> y comuniquese con el administrador.<br><b>código: (en.in)</b></h5>',
                    6000  
                   );
             });

      }else{
              //alert('edicion');  
              simpleUpdateInDB(
                '../06-funciones_php/funciones.php',
                'obras',
                serializeForm('currentForm'),
                [{columna: 'obra_id', condicion: '=', valorCompara: $(".v-id").val()}]
             ).then(data => {
             // Manejar los datos obtenidos cuando la promesa se resuelva con éxito
             //   //console.log(data);
               if(data == true)
                 {
                   sAlertDialog(
                       'success',   
                       '<h3 class="text-success"><b>LA OBRA SE MODIFICO CORRECTAMENTE<b></h3>',
                       '<h5>¿Desea seguir modificando esta obra?</h5>', 
                       'SI',            
                       'success',         
                       'NO',
                       'dark',
                       function(){},
                       function(){ window.location.href = "../01-views/obras_listado.php"; }
                   );
                 }
               else
                 {
                   sAlertAutoClose(
                    'error',                  
                    '<h3 class="text-danger"><b>OCURRIO UN ERROR EN LA BASE DE DATOS código: (eu)<b></h3>',
                    '<h5>Vuelva a intentarlo en unos minutos.<br> Si persiste este mensaje oprima <b>cancelar</b> y comuniquese con el administrador.<br><b>código: (en.up)</b></h5>',
                    6000  
                   );
                 }
             })
             .catch(error => {
               // Manejar errores si la promesa se rechaza
               //console.error(error);
                   sAlertAutoClose(
                    'error',                  
                    '<h3 class="text-danger"><b>OCURRIO UN ERROR EN EL SERVIDOR</b></h3>',
                    '<h5>Vuelva a intentarlo en unos minutos.<br> Si persiste este mensaje oprima <b>cancelar</b> y comuniquese con el administrador.<br><b>código: (en.up)</b></h5>',
                    6000  
                   );
             });

      }

    } // END - submitHandler: function ()
  
  }); // END - $.validator.setDefaults

  $('#currentForm').validate({
    rules: {
      obra_nombre: {
        required: true
      },
      obra_fecha_inicio: {
        required: true
      },
      obra_fecha_fin: {
        required: true
      },
      obra_estado: {
        required: true
      },
      obra_lat: {
        required: true
      },
      obra_lon: {
        required: true,
      }
    },

    messages: {
      obra_nombre: {
        required: "Debe completar este campo"
      },
      obra_fecha_inicio: {
        required: "Debe completar este campo"
      },
      obra_fecha_fin: {
        required: "Debe completar este campo"
      },
      obra_estado: {
        required: "Debe completar este campo"
      },
      obra_lat: {
        required: "Debe completar este campo"
      },
      obra_lon: {
        required: "Debe completar este campo"
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
<!-- funcion para traer las calles de una provincia -->
<script src="../07-funciones_js/inputEmptyDetect.js"></script>
<!-- funcion rellenas select con valores traidos por ajax -->
<script src="../07-funciones_js/optionSelect.js"></script>
<!-- funcion para mostrar documentios -->
<script src="../07-funciones_js/muestraDocumento.js"></script>

<!-- Guarda usuarios en la base -->
<script src="../07-funciones_js/obrasAcciones.js"></script>
<!-- Guarda usuarios en la base -->
<script src="../07-funciones_js/obrasGuardar.js"></script>
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

<script>
document.getElementById('obra_fecha_inicio').addEventListener('change', function() {
    var fechaInicio = new Date(this.value);
    var fechaFinInput = document.getElementById('obra_fecha_fin');
    fechaFinInput.min = this.value;
});


function initMap() {
    var defaultLocation = { lat: -34.6037, lng: -58.3816 }; // Buenos Aires

    var latInput = document.getElementById('obra_lat').value;
    var lngInput = document.getElementById('obra_lon').value;
    var initialLocation;
    var initialZoom;

    if (latInput && lngInput) {
        initialLocation = {
            lat: parseFloat(latInput),
            lng: parseFloat(lngInput)
        };
        initialZoom = 18; // Zoom mayor si hay valores en los campos
    } else {
        initialLocation = defaultLocation;
        initialZoom = 12; // Zoom por defecto
    }

    // Verificar si el campo con clase "v-id" tiene data-visualiza="on"
    var visualizaOn = false;
    var vIdElement = document.querySelector('.v-id');
    if (vIdElement && vIdElement.getAttribute('data-visualiza') === 'on') {
        visualizaOn = true;
    }

    var map = new google.maps.Map(document.getElementById('map'), {
        center: initialLocation,
        zoom: initialZoom
    });

    var marker = new google.maps.Marker({
        position: initialLocation,
        map: map,
        draggable: !visualizaOn // Deshabilitar arrastrar si visualizaOn es true
    });

    var input = document.getElementById('pac-input');
    var searchBox = new google.maps.places.SearchBox(input);
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

    map.addListener('bounds_changed', function() {
        searchBox.setBounds(map.getBounds());
    });

    searchBox.addListener('places_changed', function() {
        var places = searchBox.getPlaces();
        if (places.length == 0) {
            return;
        }

        var bounds = new google.maps.LatLngBounds();
        places.forEach(function(place) {
            if (!place.geometry) {
                console.log("Returned place contains no geometry");
                return;
            }

            if (place.geometry.viewport) {
                bounds.union(place.geometry.viewport);
            } else {
                bounds.extend(place.geometry.location);
            }

            marker.setPosition(place.geometry.location);
            document.getElementById('obra_lat').value = place.geometry.location.lat();
            document.getElementById('obra_lon').value = place.geometry.location.lng();
        });
        map.fitBounds(bounds);
    });

    marker.addListener('dragend', function(event) {
        if (!visualizaOn) {
            document.getElementById('obra_lat').value = event.latLng.lat();
            document.getElementById('obra_lon').value = event.latLng.lng();
        }
    });

    map.addListener('click', function(event) {
        if (!visualizaOn) {
            marker.setPosition(event.latLng);
            document.getElementById('obra_lat').value = event.latLng.lat();
            document.getElementById('obra_lon').value = event.latLng.lng();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initMap();
});
</script>



<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('currentForm').addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            return false;
        }
    });

    document.getElementById('guardar-btn').addEventListener('click', function() {
        document.getElementById('currentForm').submit();
    });
});
</script>