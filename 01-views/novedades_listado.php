<?php  
// filename: novedades_listado.php | path: 01-views/novedades/novedades_listado.php

session_start();
include_once '../06-funciones_php/funciones.php'; //funciones últiles
sesion(); //Verifica si hay usuario sesionado

include_once '../06-funciones_php/auditoria.php';
registrarNavegacion('NOVEDADES - Listado');

$yearMonth = isset($_GET['year']) && isset($_GET['month']) 
    ? $_GET['year'] . '-' . str_pad($_GET['month'], 2, '0', STR_PAD_LEFT)
    : date('Y-m');

define('BASE_URL', $_SESSION["base_url"]);
include_once '../03-controller/novedadesController.php'; //conecta a la base de datos
$filas = poblarDatableActivos(array(
    'id_agente', 
    'agente', 
    '0%-pri-qui', '50%-pri-qui', '100%-pri-qui', '150%-pri-qui', '200%-pri-qui', '300%-pri-qui', '400%-pri-qui',
    'subtotal-pri-qui',  // Subtotal de la primera quincena
    '0%-seg-qui', '50%-seg-qui', '100%-seg-qui', '150%-seg-qui', '200%-seg-qui', '300%-seg-qui', '400%-seg-qui',
    'subtotal-seg-qui',  // Subtotal de la segunda quincena
    'total'              // Total general
), '', $yearMonth);

list($initialYear, $initialMonth) = explode('-', $yearMonth);

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ADMINTECH | Listado de Novedades de personal</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="../05-plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../dist/css/custom.css">
  <link rel="stylesheet" href="../05-plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">

  <script src='../05-plugins/pdfmake/pdfmake.min.js'></script>
  <script src='../05-plugins/pdfmake/vfs_fonts.js'></script>
  <script src="../05-plugins/html2canvas/html2canvas.min.js"></script>
  <script src="../05-plugins/jspdf/jspdf.umd.min.js"></script>

  <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11?v=<?php echo time(); ?>"></script> -->
  <script src="../05-plugins/sweetalert2/sweetalert2.min.js"></script>

</head>
<body class="hold-transition sidebar-collapse layout-navbar-fixed bg-yellow-soft">
<div class="wrapper">

  <!-- Navbar -->

  <!-- /.navbar -->
  <?php include '../01-views/layout/navbar_layout.php';?> 
  <!-- Main Sidebar Container -->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><strong>Listado de Novedades de personal</strong></h1>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
            <div class="row d-flex text-center justify-content-end p-2">
              <button type="button" class="col-1 btn btn-warning btn-block mb-0 mt-0 mr-1 v-accion-ver-todos d-none" data-accion="vertodos"><i class="fa fa-eye"></i> Todos</button><button onclick="window.location.href='agente_form.php'" type="button" class="col-1 btn btn-success btn-block mb-0 mt-0 mr-0 d-none"><i class="fa fa-plus-circle"></i> Agregar</button>
            </div>
        <div class="row">
          <div class="col-12">
            <div class="row d-flex justify-content-between p-2">
              <button id="prevMonth" type="button" class="col-1 btn btn-info btn-block mb-0 mt-0 mr-1" data-accion="prev">
                <i class="fa fa-arrow-left"></i> Mes Anterior
              </button>
              
              <!-- Mostrar el mes y año actual en negritas usando strong -->
              <span class="align-self-center"><strong id="currentMonthYear" style="font-size: 1.5rem;">Mes Año</strong></span>

              <button id="nextMonth" type="button" class="col-1 btn btn-info btn-block mb-0 mt-0 mr-1" data-accion="next" disabled>
                Mes Siguiente <i class="fa fa-arrow-right"></i>
              </button>
            </div>
            <div class="card">
              <!-- /.card-header -->
              <div class="card-body">
                <table id="novedades_tabla" class="table table-bordered table-striped">
                  <thead>
                        <tr>
                            <th></th> <!-- Columna ID vacía en la primera fila -->
                            <th></th> <!-- Columna Agente vacía en la primera fila -->
                            <th class="text-center primera-quincena segunda-quincena" colspan="8">Primera quincena</th>
                            <th class="text-center segunda-quincena" colspan="8">Segunda quincena</th>
                            <th></th> <!-- Columna Total vacía en la primera fila -->
                            <th></th> <!-- Columna Acciones vacía en la primera fila -->
                        </tr>
                        <tr>
                            <th>ID</th>
                            <th>Agente</th>
                            <th class="text-center primera-quincena">0%</th>
                            <th class="text-center primera-quincena">50%</th>
                            <th class="text-center primera-quincena">100%</th>
                            <th class="text-center primera-quincena">150%</th>
                            <th class="text-center primera-quincena">200%</th>
                            <th class="text-center primera-quincena">300%</th>
                            <th class="text-center primera-quincena">400%</th>
                            <th class="text-center primera-quincena">Jornales</th>
                            <th class="text-center segunda-quincena">0%</th>
                            <th class="text-center segunda-quincena">50%</th>
                            <th class="text-center segunda-quincena">100%</th>
                            <th class="text-center segunda-quincena">150%</th>
                            <th class="text-center segunda-quincena">200%</th>
                            <th class="text-center segunda-quincena">300%</th>
                            <th class="text-center">400%</th>
                            <th class="text-center segunda-quincena">Jornales</th>
                            <th class="text-center">Jornales del Mes</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                      <?php echo $filas;?>  
                    </tbody>

                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
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
<!-- DataTables  & Plugins -->
<script src="../05-plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../05-plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../05-plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../05-plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../05-plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../05-plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="../05-plugins/jszip/jszip.min.js"></script>
<script src="../05-plugins/pdfmake/pdfmake.min.js"></script>
<script src="../05-plugins/pdfmake/vfs_fonts.js"></script>
<script src="../05-plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../05-plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="../05-plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>
<!-- Page specific script -->
<script src="../07-funciones_js/funciones.js"></script>
<script src="../07-funciones_js/ayudaListadoNovedades.js"></script>

<script>
let currentYear = <?php echo $initialYear; ?>;
let currentMonth = <?php echo $initialMonth; ?>;

$(function () {
    ayuda();

    $("#novedades_tabla").DataTable({
        "dom": '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "pageLength": 100,
        "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
        "language": {"url": "../05-plugins/datatables/es-ES.json"},
        "columns": [
            { "width": "2%", "orderable": false },    // ID
            { "width": "12%" },                       // Agente (única columna ordenable)
            { "width": "4%", "className": "text-center primera-quincena", "orderable": false },  // Primera Quincena - 0%
            { "width": "4%", "className": "text-center", "orderable": false },  // Primera Quincena - 50%
            { "width": "4%", "className": "text-center", "orderable": false },  // Primera Quincena - 100%
            { "width": "4%", "className": "text-center", "orderable": false },  // Primera Quincena - 150%
            { "width": "4%", "className": "text-center", "orderable": false },  // Primera Quincena - 200%
            { "width": "4%", "className": "text-center", "orderable": false },  // Primera Quincena - 300%
            { "width": "4%", "className": "text-center", "orderable": false },  // Primera Quincena - 400%
            { "width": "6%", "className": "text-center primera-quincena segunda-quincena", "orderable": false }, // Primera Quincena - Subtotal
            { "width": "4%", "className": "text-center", "orderable": false },  // Segunda Quincena - 0%
            { "width": "4%", "className": "text-center", "orderable": false },  // Segunda Quincena - 50%
            { "width": "4%", "className": "text-center", "orderable": false },  // Segunda Quincena - 100%
            { "width": "4%", "className": "text-center", "orderable": false },  // Segunda Quincena - 150%
            { "width": "4%", "className": "text-center", "orderable": false },  // Segunda Quincena - 200%
            { "width": "4%", "className": "text-center", "orderable": false },  // Segunda Quincena - 300%
            { "width": "4%", "className": "text-center", "orderable": false },  // Segunda Quincena - 400%
            { "width": "6%", "className": "text-center primera-quincena segunda-quincena", "orderable": false }, // Segunda Quincena - Subtotal
            { "width": "6%", "className": "text-center  segunda-quincena", "orderable": false },                 // Total
            { "width": "6%", "className": "text-center", "orderable": false }                                               // Acciones
        ],
        "order": [[1, 'asc']]
    }).buttons().container().appendTo('#novedades_tabla_wrapper .col-md-6:eq(0)');
});

$(document).ready(function () {
    let dataTable = null; // Variable para controlar la instancia del DataTable
    updateMonthYearDisplay(currentMonth, currentYear);  // Muestra el mes/año inicial en la interfaz
    updateTable(currentMonth, currentYear);  // Carga la tabla para el mes/año inicial
    // Función para actualizar el contenido del mes y año
    function updateMonthYearDisplay(month, year) {
        const monthNames = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
        $('#currentMonthYear').text(monthNames[month - 1] + ' ' + year);
    }

// Función para actualizar los datos del DataTable solo si se navega entre meses
    function updateTable(month, year) {
        const tds = [
            'id_agente', 'agente', 
            '0%-pri-qui', '50%-pri-qui', '100%-pri-qui', '150%-pri-qui', '200%-pri-qui', '300%-pri-qui', '400%-pri-qui',
            'subtotal-pri-qui', '0%-seg-qui', '50%-seg-qui', '100%-seg-qui', '150%-seg-qui', '200%-seg-qui', '300%-seg-qui', '400%-seg-qui',
            'subtotal-seg-qui', 'total'
        ];

        $.ajax({
            url: '../03-controller/novedadesController.php',
            method: 'POST',
            data: { 
                mes: month, 
                anio: year,
                tds: tds,
                funcion: 'poblarDatableActivos',
                ajax: 'on'
            },
            success: function (data) {

                // Destruye y limpia el DataTable antes de actualizar el contenido
                $('#novedades_tabla').DataTable().clear().destroy();

                // Actualiza el tbody con los nuevos datos interpretando el HTML
                $('#novedades_tabla tbody').html(data);

                // Reinicializa el DataTable con la configuración y botones de exportación
                $('#novedades_tabla').DataTable({
                    "dom": '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
                    "responsive": true,
                    "lengthChange": true,
                    "autoWidth": false,
                    "pageLength": 100,
                    "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
                    "language": { "url": "../05-plugins/datatables/es-ES.json"},
                    "columns": [
                        { "width": "2%", "orderable": false }, // ID
                        { "width": "12%" },                    // Agente
                        { "width": "4%", "className": "text-center primera-quincena", "orderable": false }, // 0%-pri-qui
                        { "width": "4%", "className": "text-center", "orderable": false }, // 50%-pri-qui
                        { "width": "4%", "className": "text-center", "orderable": false }, // 100%-pri-qui
                        { "width": "4%", "className": "text-center", "orderable": false }, // 150%-pri-qui
                        { "width": "4%", "className": "text-center", "orderable": false }, // 200%-pri-qui
                        { "width": "4%", "className": "text-center", "orderable": false }, // 300%-pri-qui
                        { "width": "4%", "className": "text-center", "orderable": false }, // 400%-pri-qui
                        { "width": "6%", "className": "text-center primera-quincena segunda-quincena", "orderable": false }, // Subtotal primera quincena
                        { "width": "4%", "className": "text-center", "orderable": false }, // 0%-seg-qui
                        { "width": "4%", "className": "text-center", "orderable": false }, // 50%-seg-qui
                        { "width": "4%", "className": "text-center", "orderable": false }, // 100%-seg-qui
                        { "width": "4%", "className": "text-center", "orderable": false }, // 150%-seg-qui
                        { "width": "4%", "className": "text-center", "orderable": false }, // 200%-seg-qui
                        { "width": "4%", "className": "text-center", "orderable": false }, // 300%-seg-qui
                        { "width": "4%", "className": "text-center", "orderable": false }, // 400%-seg-qui
                        { "width": "6%", "className": "text-center primera-quincena segunda-quincena", "orderable": false }, // Subtotal segunda quincena
                        { "width": "6%", "className": "text-center segunda-quincena", "orderable": false }, // Total
                        { "width": "6%", "className": "text-center", "orderable": false }  // Acciones
                    ],
                    "order": [[1, 'asc']]
                }).buttons().container().appendTo('#novedades_tabla_wrapper .col-md-6:eq(0)'); // Restaurar botones

                // Deshabilitar el botón "Mes Siguiente" solo si estamos en el mes actual
                const today = new Date();
                const currentYear = today.getFullYear();
                const currentMonth = today.getMonth() + 1; // Mes actual en formato 1-12

                const maxAllowedYear = currentYear;
                const maxAllowedMonth = currentMonth + 1; // Solo permite avanzar hasta el mes siguiente

                const isMaxReached = (year > maxAllowedYear) || 
                                     (year === maxAllowedYear && month >= maxAllowedMonth);

                $('#nextMonth').prop('disabled', isMaxReached);



                // Actualiza la visualización del mes y año en la interfaz
                updateMonthYearDisplay(month, year);
                $('[data-toggle="tooltip"]').tooltip();
            },
            error: function () {
                alert('Error al actualizar la tabla');
            }
        });
    }

    // Cargar los datos del mes actual sin hacer la llamada AJAX
    updateMonthYearDisplay(currentMonth, currentYear);

    // Evento para botón de mes anterior
    $('#prevMonth').click(function () {
        if (currentMonth === 1) {
            currentMonth = 12;
            currentYear--;  // Cambiar a diciembre del año anterior
        } else {
            currentMonth--;
        }
        updateTable(currentMonth, currentYear);
    });

    // Evento para botón de mes siguiente
    $('#nextMonth').click(function () {
        if (currentMonth === 12) {
            currentMonth = 1;
            currentYear++;  // Cambiar a enero del siguiente año
        } else {
            currentMonth++;
        }
        updateTable(currentMonth, currentYear);
    });
});

// Función para actualizar el contenido de mes y año al cargar la página
$(document).ready(function() {

});


</script>

</script>
<!-- Modal para registrar pagos -->
<div class="modal fade" id="modalPago" tabindex="-1" aria-labelledby="modalPagoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header d-flex justify-content-between align-items-center pb-1 pt-1 bg-gray">
        <h4 class="modal-title fw-bold"><strong id="modalPagoLabel">Liquidación</strong></h4>
        <h4 class="modal-title fw-bold text-end"><strong id="nombreAgente">Sin nombre</strong></h4>
      </div>
      <div class="modal-body pb-1 pt-1">

        <!-- primera Quincena -->
        <div id="contenedorPrimeraQuincena" class="mb-1">
          <!-- Primera Quincena -->
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="text-left mt-3" id="title-1q">Primera Quincena</h5>
            <div>
              <label for="valorPrimeraQuincena" class="form-label mb-0">Valor jornal:</label>
              <input type="number" class="form-control form-control-sm d-inline-block w-auto text-right" id="valorPrimeraQuincena" placeholder="0.00" step="0.01">
            </div>
          </div>
          <table class="table table-striped table-bordered table-sm" id="tablaPrimeraQuincena">
            <thead>
              <tr>
                <th class="text-center">Tipo</th>
                <th class="text-center">Cantidad</th>
                <th class="text-center">Jornales</th>
                <th class="text-center">Importe</th>
              </tr>
            </thead>
            <tbody>
              <!-- Datos dinámicos -->
            </tbody>
          </table>

          <!-- Nuevos campos debajo de la tabla -->
          <div class="mt-3">
            <div class="d-flex justify-content-end align-items-center mb-2">
              <label for="liquidadoPrimeraQuincena" class="form-label mb-0 mr-2 text-danger">Liquidado:</label>
              <input type="number" class="form-control form-control-sm text-right text-danger uniform-width" id="liquidadoPrimeraQuincena" placeholder="0.00" step="0.01">
            </div>
            <div class="d-flex justify-content-end align-items-center mb-2">
              <label for="viaticosPrimeraQuincena" class="form-label mb-0 mr-2 text-success">Viáticos:</label>
              <input type="number" class="form-control form-control-sm text-right text-success uniform-width" id="viaticosPrimeraQuincena" placeholder="0.00" step="0.01">
            </div>

            <!-- Agrupación de Varios (+) -->
            <div class="d-flex flex-column align-items-end">
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="otrosMas1PrimeraQuincena" class="form-label mb-0 mr-2 text-success">Varios (+):</label>
                <input type="number" class="form-control form-control-sm text-right text-success uniform-width" id="otrosMas1PrimeraQuincena" placeholder="0.00" step="0.01">
              </div>
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="otrosMas2PrimeraQuincena" class="form-label mb-0 mr-2 text-success">Varios (+):</label>
                <input type="number" class="form-control form-control-sm text-right text-success uniform-width" id="otrosMas2PrimeraQuincena" placeholder="0.00" step="0.01">
              </div>
              <!-- Nuevos campos -->
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="otrosMas3PrimeraQuincena" class="form-label mb-0 mr-2 text-success">Varios (+):</label>
                <input type="number" class="form-control form-control-sm text-right text-success uniform-width" id="otrosMas3PrimeraQuincena" placeholder="0.00" step="0.01">
              </div>
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="otrosMas4PrimeraQuincena" class="form-label mb-0 mr-2 text-success">Varios (+):</label>
                <input type="number" class="form-control form-control-sm text-right text-success uniform-width" id="otrosMas4PrimeraQuincena" placeholder="0.00" step="0.01">
              </div>
            </div>

            <!-- Agrupación de Varios (-) -->
            <div class="d-flex flex-column align-items-end">
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="otrosMenos1PrimeraQuincena" class="form-label mb-0 mr-2 text-danger">Varios (-):</label>
                <input type="number" class="form-control form-control-sm text-right text-danger uniform-width" id="otrosMenos1PrimeraQuincena" placeholder="0.00" step="0.01">
              </div>
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="otrosMenos2PrimeraQuincena" class="form-label mb-0 mr-2 text-danger">Varios (-):</label>
                <input type="number" class="form-control form-control-sm text-right text-danger uniform-width" id="otrosMenos2PrimeraQuincena" placeholder="0.00" step="0.01">
              </div>
              <!-- Nuevos campos -->
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="otrosMenos3PrimeraQuincena" class="form-label mb-0 mr-2 text-danger">Varios (-):</label>
                <input type="number" class="form-control form-control-sm text-right text-danger uniform-width" id="otrosMenos3PrimeraQuincena" placeholder="0.00" step="0.01">
              </div>
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="otrosMenos4PrimeraQuincena" class="form-label mb-0 mr-2 text-danger">Varios (-):</label>
                <input type="number" class="form-control form-control-sm text-right text-danger uniform-width" id="otrosMenos4PrimeraQuincena" placeholder="0.00" step="0.01">
              </div>
            </div>

            <div class="d-flex justify-content-end align-items-center">
              <label for="totalPrimeraQuincena" class="form-label mb-0 mr-2 font-weight-bold">Total:</label>
              <input type="number" class="form-control form-control-sm text-right font-weight-bold uniform-width" style="font-size: 1.2rem;" id="totalPrimeraQuincena" placeholder="0.00" step="0.01" readonly>
            </div>
          </div>
        </div>

        <!-- Segunda Quincena -->
        <div id="segundaQuincena" class="mb-1">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="text-left mt-3" id="title-2q">Segunda Quincena</h5>
            <div>
              <label for="valorSegundaQuincena" class="form-label mb-0">Valor jornal:</label>
              <input type="number" class="form-control form-control-sm d-inline-block w-auto text-right" id="valorSegundaQuincena" placeholder="0.00" step="0.01">
            </div>
          </div>
          <table class="table table-striped table-bordered table-sm" id="tablaSegundaQuincena">
            <thead>
              <tr>
                <th class="text-center">Tipo</th>
                <th class="text-center">Cantidad</th>
                <th class="text-center">Jornales</th>
                <th class="text-center">Importe</th>
              </tr>
            </thead>
            <tbody>
              <!-- Datos dinámicos -->
            </tbody>
          </table>

          <!-- Campos adicionales debajo de la tabla -->
          <div class="mt-3">
            <div class="d-flex justify-content-end align-items-center mb-2">
              <label for="liquidadoSegundaQuincena" class="form-label mb-0 text-danger mr-2">Liquidado:</label>
              <input type="number" class="form-control form-control-sm w-auto text-danger text-right" id="liquidadoSegundaQuincena" placeholder="0.00" step="0.01">
            </div>
            <div class="d-flex justify-content-end align-items-center mb-2">
              <label for="viaticosSegundaQuincena" class="form-label mb-0 text-success mr-2">Viáticos:</label>
              <input type="number" class="form-control form-control-sm w-auto text-success text-right" id="viaticosSegundaQuincena" placeholder="0.00" step="0.01">
            </div>
            <!-- Agrupación de Varios (+) -->
            <div class="d-flex flex-column align-items-end">
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="variosMas1SegundaQuincena" class="form-label mb-0 mr-2 text-success">Varios (+):</label>
                <input type="number" class="form-control form-control-sm text-right text-success uniform-width" id="variosMas1SegundaQuincena" placeholder="0.00" step="0.01">
              </div>
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="variosMas2SegundaQuincena" class="form-label mb-0 mr-2 text-success">Varios (+):</label>
                <input type="number" class="form-control form-control-sm text-right text-success uniform-width" id="variosMas2SegundaQuincena" placeholder="0.00" step="0.01">
              </div>
              <!-- Nuevos campos -->
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="variosMas3SegundaQuincena" class="form-label mb-0 mr-2 text-success">Varios (+):</label>
                <input type="number" class="form-control form-control-sm text-right text-success uniform-width" id="variosMas3SegundaQuincena" placeholder="0.00" step="0.01">
              </div>
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="variosMas4SegundaQuincena" class="form-label mb-0 mr-2 text-success">Varios (+):</label>
                <input type="number" class="form-control form-control-sm text-right text-success uniform-width" id="variosMas4SegundaQuincena" placeholder="0.00" step="0.01">
              </div>
            </div>

            <!-- Agrupación de Varios (-) -->
            <div class="d-flex flex-column align-items-end">
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="variosMenos1SegundaQuincena" class="form-label mb-0 mr-2 text-danger">Varios (-):</label>
                <input type="number" class="form-control form-control-sm text-right text-danger uniform-width" id="variosMenos1SegundaQuincena" placeholder="0.00" step="0.01">
              </div>
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="variosMenos2SegundaQuincena" class="form-label mb-0 mr-2 text-danger">Varios (-):</label>
                <input type="number" class="form-control form-control-sm text-right text-danger uniform-width" id="variosMenos2SegundaQuincena" placeholder="0.00" step="0.01">
              </div>
              <!-- Nuevos campos -->
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="variosMenos3SegundaQuincena" class="form-label mb-0 mr-2 text-danger">Varios (-):</label>
                <input type="number" class="form-control form-control-sm text-right text-danger uniform-width" id="variosMenos3SegundaQuincena" placeholder="0.00" step="0.01">
              </div>
              <div class="d-flex justify-content-end align-items-center mb-2">
                <label for="variosMenos4SegundaQuincena" class="form-label mb-0 mr-2 text-danger">Varios (-):</label>
                <input type="number" class="form-control form-control-sm text-right text-danger uniform-width" id="variosMenos4SegundaQuincena" placeholder="0.00" step="0.01">
              </div>
            </div>

            <div class="d-flex justify-content-end align-items-center">
              <label for="totalSegundaQuincena" class="form-label mb-0 font-weight-bold text-dark mr-2">Total:</label>
              <input type="number" class="form-control form-control-sm w-auto font-weight-bold text-right uniform-width" id="totalSegundaQuincena" placeholder="0.00" step="0.01" readonly>
            </div>
          </div>
        </div>

        <!-- =====================-->
        <!--  LIQUIDACIÓN MENSUAL -->
        <!-- =====================-->
        <!-- NUEVA SECCIÓN PARA “3” -->
        <div id="contenedorMensual" class="mb-1" style="display: none;">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="text-left mt-3" id="title-me">Liquidación Mensual</h5>
            <div>
              <label for="valorMensual" class="form-label mb-0">Valor Mensual:</label>
              <input
                type="number"
                class="form-control form-control-sm d-inline-block w-auto text-right"
                id="valorMensual"
                placeholder="0.00"
                step="0.01"
              >
            </div>
          </div>
          <table class="table table-striped table-bordered table-sm" id="tablaMensual">
            <thead>
              <tr>
                <th class="text-center">Tipo</th>
                <th class="text-center">Cantidad</th>
                <th class="text-center">Jornales</th>
                <th class="text-center">Importe</th>
              </tr>
            </thead>
            <tbody>
              <!-- Se llenará dinámicamente con la SUMA de ambas quincenas -->
            </tbody>
          </table>

          <!-- Campos de totales y ajustes (Mensual) -->
          <div class="mt-3">
            <div class="d-flex justify-content-end align-items-center mb-2">
              <label for="liquidadoMensual" class="form-label mb-0 mr-2 text-danger">Liquidado:</label>
              <input
                type="number"
                class="form-control form-control-sm text-right text-danger uniform-width"
                id="liquidadoMensual"
                placeholder="0.00"
                step="0.01"
              >
            </div>
            <div class="d-flex justify-content-end align-items-center mb-2">
              <label for="viaticosMensual" class="form-label mb-0 mr-2 text-success">Viáticos:</label>
              <input
                type="number"
                class="form-control form-control-sm text-right text-success uniform-width"
                id="viaticosMensual"
                placeholder="0.00"
                step="0.01"
              >
            </div>
            <!-- Varios (+) -->
            <div class="d-flex justify-content-end align-items-center mb-2">
              <label for="variosMas1Mensual" class="form-label mb-0 mr-2 text-success">Varios (+):</label>
              <input type="number" class="form-control form-control-sm text-right text-success uniform-width" id="variosMas1Mensual" placeholder="0.00" step="0.01">
            </div>
            <div class="d-flex justify-content-end align-items-center mb-2">
              <label for="variosMas2Mensual" class="form-label mb-0 mr-2 text-success">Varios (+):</label>
              <input type="number" class="form-control form-control-sm text-right text-success uniform-width" id="variosMas2Mensual" placeholder="0.00" step="0.01">
            </div>
            <div class="d-flex justify-content-end align-items-center mb-2">
              <label for="variosMas3Mensual" class="form-label mb-0 mr-2 text-success">Varios (+):</label>
              <input type="number" class="form-control form-control-sm text-right text-success uniform-width" id="variosMas3Mensual" placeholder="0.00" step="0.01">
            </div>
            <div class="d-flex justify-content-end align-items-center mb-2">
              <label for="variosMas4Mensual" class="form-label mb-0 mr-2 text-success">Varios (+):</label>
              <input type="number" class="form-control form-control-sm text-right text-success uniform-width" id="variosMas4Mensual" placeholder="0.00" step="0.01">
            </div>

            <!-- Varios (-) -->
            <div class="d-flex justify-content-end align-items-center mb-2">
              <label for="variosMenos1Mensual" class="form-label mb-0 mr-2 text-danger">Varios (-):</label>
              <input type="number" class="form-control form-control-sm text-right text-danger uniform-width" id="variosMenos1Mensual" placeholder="0.00" step="0.01">
            </div>
            <div class="d-flex justify-content-end align-items-center mb-2">
              <label for="variosMenos2Mensual" class="form-label mb-0 mr-2 text-danger">Varios (-):</label>
              <input type="number" class="form-control form-control-sm text-right text-danger uniform-width" id="variosMenos2Mensual" placeholder="0.00" step="0.01">
            </div>
            <div class="d-flex justify-content-end align-items-center mb-2">
              <label for="variosMenos3Mensual" class="form-label mb-0 mr-2 text-danger">Varios (-):</label>
              <input type="number" class="form-control form-control-sm text-right text-danger uniform-width" id="variosMenos3Mensual" placeholder="0.00" step="0.01">
            </div>
            <div class="d-flex justify-content-end align-items-center mb-2">
              <label for="variosMenos4Mensual" class="form-label mb-0 mr-2 text-danger">Varios (-):</label>
              <input type="number" class="form-control form-control-sm text-right text-danger uniform-width" id="variosMenos4Mensual" placeholder="0.00" step="0.01">
            </div>

            <div class="d-flex justify-content-end align-items-center">
              <label for="totalMensual" class="form-label mb-0 mr-2 font-weight-bold">Total:</label>
              <input
                type="number"
                class="form-control form-control-sm text-right font-weight-bold uniform-width"
                style="font-size: 1.2rem;"
                id="totalMensual"
                placeholder="0.00"
                step="0.01"
                readonly
              >
            </div>
          </div>
        </div>
        <!-- FIN Liquidación Mensual -->

        <div class="modal-footer">
            <!-- Botón Guardar -->
            <button type="button" id="guardaLiquidacion" class="btn btn-success v-boton-accion no-print" data-accion="guardaliquidacion">Guardar</button>
            <!-- Botón Imprimir -->
            <button type="button" class="btn btn-primary v-boton-accion no-print" data-accion="imprimir">Imprimir</button>
            <!-- Botón Cerrar -->
            <button type="button" class="btn btn-secondary no-print v-boton-accion" data-accion="cerrarModalLiquidacion">Cerrar</button>
        </div>
    </div>
  </div>
</div>


<style>
  /* Reducir la altura de las filas de las tablas */
  #tablaPrimeraQuincena tbody tr,
  #tablaSegundaQuincena tbody tr,
  #tablaMensual tbody tr {
    line-height: 1; /* Reducir la altura de la línea */
    padding: 0.25rem; /* Reducir el relleno de las celdas */
  }

  /* Reducir la altura del encabezado */
  #tablaPrimeraQuincena thead tr th,
  #tablaSegundaQuincena thead tr th,
  #tablaMensual thead tr th {
    line-height: 1.2; /* Ajustar la altura de la línea en el encabezado */
    padding: 0.5rem; /* Reducir el relleno en el encabezado */
  }
</style>





</body>
</html>
<script src="../07-funciones_js/novedadesAcciones.js"></script>

<script>


$(document).ready(function () {
    // Configuración del modal
    $('#modalPago').modal({
        backdrop: 'static',
        keyboard: false,
        show: false,
    });
});

// Función para recalcular el total
function recalcularTotalPrimeraQuincena() {
  const liquidado = parseFloat($('#liquidadoPrimeraQuincena').val()) || 0;
  const viaticos = parseFloat($('#viaticosPrimeraQuincena').val()) || 0;
  const otrosMas1 = parseFloat($('#otrosMas1PrimeraQuincena').val()) || 0;
  const otrosMas2 = parseFloat($('#otrosMas2PrimeraQuincena').val()) || 0;
  const otrosMas3 = parseFloat($('#otrosMas3PrimeraQuincena').val()) || 0;
  const otrosMas4 = parseFloat($('#otrosMas4PrimeraQuincena').val()) || 0;
  const otrosMenos1 = parseFloat($('#otrosMenos1PrimeraQuincena').val()) || 0;
  const otrosMenos2 = parseFloat($('#otrosMenos2PrimeraQuincena').val()) || 0;
  const otrosMenos3 = parseFloat($('#otrosMenos3PrimeraQuincena').val()) || 0;
  const otrosMenos4 = parseFloat($('#otrosMenos4PrimeraQuincena').val()) || 0;

  const subtotalImporte = parseFloat($('#tablaPrimeraQuincena tbody tr:last-child td:last-child').text()) || 0;

  const total =
    subtotalImporte +
    viaticos +
    otrosMas1 +
    otrosMas2 +
    otrosMas3 +
    otrosMas4 -
    liquidado -
    otrosMenos1 -
    otrosMenos2 -
    otrosMenos3 -
    otrosMenos4;

  $('#totalPrimeraQuincena').val(total.toFixed(2));
}


// Escuchar cambios en los campos relacionados y recalcular automáticamente
$('#liquidadoPrimeraQuincena, #viaticosPrimeraQuincena, #otrosMas1PrimeraQuincena, #otrosMas2PrimeraQuincena, #otrosMas3PrimeraQuincena, #otrosMas4PrimeraQuincena, #otrosMenos1PrimeraQuincena, #otrosMenos2PrimeraQuincena, #otrosMenos3PrimeraQuincena, #otrosMenos4PrimeraQuincena').on('input', function () {
  recalcularTotalPrimeraQuincena();
});

// Escuchar cambios en la tabla (última celda de "Importe") y recalcular automáticamente
$('#liquidadoSegundaQuincena, #viaticosSegundaQuincena, #variosMas1SegundaQuincena, #variosMas2SegundaQuincena, #variosMas3SegundaQuincena, #variosMas4SegundaQuincena, #variosMenos1SegundaQuincena, #variosMenos2SegundaQuincena, #variosMenos3SegundaQuincena, #variosMenos4SegundaQuincena').on('input', function () {
  recalcularTotalSegundaQuincena();
});

// Función para recalcular el total de la Segunda Quincena
function recalcularTotalSegundaQuincena() {
  const liquidado = parseFloat($('#liquidadoSegundaQuincena').val()) || 0;
  const viaticos = parseFloat($('#viaticosSegundaQuincena').val()) || 0;
  const variosMas1 = parseFloat($('#variosMas1SegundaQuincena').val()) || 0;
  const variosMas2 = parseFloat($('#variosMas2SegundaQuincena').val()) || 0;
  const variosMas3 = parseFloat($('#variosMas3SegundaQuincena').val()) || 0;
  const variosMas4 = parseFloat($('#variosMas4SegundaQuincena').val()) || 0;
  const variosMenos1 = parseFloat($('#variosMenos1SegundaQuincena').val()) || 0;
  const variosMenos2 = parseFloat($('#variosMenos2SegundaQuincena').val()) || 0;
  const variosMenos3 = parseFloat($('#variosMenos3SegundaQuincena').val()) || 0;
  const variosMenos4 = parseFloat($('#variosMenos4SegundaQuincena').val()) || 0;

  const subtotalImporte = parseFloat($('#tablaSegundaQuincena tbody tr:last-child td:last-child').text()) || 0;

  const total =
    subtotalImporte +
    viaticos +
    variosMas1 +
    variosMas2 +
    variosMas3 +
    variosMas4 -
    liquidado -
    variosMenos1 -
    variosMenos2 -
    variosMenos3 -
    variosMenos4;

  $('#totalSegundaQuincena').val(total.toFixed(2));
}

// Escuchar cambios en los campos de la Segunda Quincena
$('#liquidadoSegundaQuincena, #viaticosSegundaQuincena, #variosMas1SegundaQuincena, #variosMas2SegundaQuincena, #variosMenos1SegundaQuincena, #variosMenos2SegundaQuincena').on('input', function () {
  recalcularTotalSegundaQuincena();
});

// Escuchar cambios en la tabla de la Segunda Quincena
$('#tablaSegundaQuincena').on('input', 'td:last-child', function () {
  recalcularTotalSegundaQuincena();
});


function recalcularTotalMensual() {
  const liquidado = parseFloat($('#liquidadoMensual').val()) || 0;
  const viaticos  = parseFloat($('#viaticosMensual').val()) || 0;

  const otrosMas1 = parseFloat($('#variosMas1Mensual').val()) || 0;
  const otrosMas2 = parseFloat($('#variosMas2Mensual').val()) || 0;
  const otrosMas3 = parseFloat($('#variosMas3Mensual').val()) || 0;
  const otrosMas4 = parseFloat($('#variosMas4Mensual').val()) || 0;

  const otrosMenos1 = parseFloat($('#variosMenos1Mensual').val()) || 0;
  const otrosMenos2 = parseFloat($('#variosMenos2Mensual').val()) || 0;
  const otrosMenos3 = parseFloat($('#variosMenos3Mensual').val()) || 0;
  const otrosMenos4 = parseFloat($('#variosMenos4Mensual').val()) || 0;

  // Valor jornal: ahora se suma directo, sin multiplicar
  const valorJornal = parseFloat($('#valorMensual').val()) || 0;

  // Fórmula: un ejemplo donde valorJornal se suma tal cual
  const total = valorJornal
              + viaticos
              + otrosMas1 + otrosMas2 + otrosMas3 + otrosMas4
              - liquidado
              - otrosMenos1 - otrosMenos2 - otrosMenos3 - otrosMenos4;

  $('#totalMensual').val(total.toFixed(2));
}


$('#valorMensual, #liquidadoMensual, #viaticosMensual, #variosMas1Mensual, #variosMas2Mensual, #variosMas3Mensual, #variosMas4Mensual, #variosMenos1Mensual, #variosMenos2Mensual, #variosMenos3Mensual, #variosMenos4Mensual').on('input', function () {
  recalcularTotalMensual();
});


$(document).ready(function () {
    // Cuando cambia el valor del jornal en la Primera Quincena
    $('#valorPrimeraQuincena').on('input', function () {
        actualizarImportes('#tablaPrimeraQuincena', $(this).val());
    });

    // Cuando cambia el valor del jornal en la Segunda Quincena
    $('#valorSegundaQuincena').on('input', function () {
        actualizarImportes('#tablaSegundaQuincena', $(this).val());
    });

    // Función para actualizar los importes en una tabla específica
    function actualizarImportes(tablaId, valorJornal) {
        valorJornal = parseFloat(valorJornal) || 0; // Asegurar número válido

        $(tablaId + ' tbody tr').each(function () {
            var jornales = parseFloat($(this).find('td:nth-child(3)').text()) || 0; // Leer jornales
            var importe = jornales * valorJornal; // Calcular importe
            $(this).find('td:nth-child(4)').text(importe.toFixed(2)); // Actualizar en la tabla
        });

        // También recalcular el total de la quincena
        recalcularTotalQuincena(tablaId);
    }

    // Función para recalcular el total de una quincena
    function recalcularTotalQuincena(tablaId) {
        var total = 0;

        // Sumar todos los valores de la columna "Importe"
        $(tablaId + ' tbody tr').each(function () {
            var importe = parseFloat($(this).find('td:nth-child(4)').text()) || 0;
            total += importe;
        });

        // Actualizar el subtotal en la última fila de la tabla
        $(tablaId + ' tbody tr:last-child td:last-child').text(total.toFixed(2));

        // También actualizar el campo de Total debajo de la tabla
        if (tablaId === "#tablaPrimeraQuincena") {
            recalcularTotalPrimeraQuincena();
        } else if (tablaId === "#tablaSegundaQuincena") {
            recalcularTotalSegundaQuincena();
        }
    }

});

</script>
