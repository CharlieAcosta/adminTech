<?php  
session_start();
define('BASE_URL', $_SESSION["base_url"]);
include_once '../03-controller/clientesController.php'; //conecta a la base de datos
$filas = poblarDatableAll(array('id_cliente', 'log_alta','estado', 'razon_social', 'cuit', 'telefono', 'email', 'contacto_pri', 'contacto_pri_celular', 'contacto_pri_email'), 'php', 'todos');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ADMINTECH | Listado de Clientes</title>

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
  <script src='../05-plugins/pdfmake/pdfmake.min.js'></script>
  <script src='../05-plugins/pdfmake/vfs_fonts.js'></script>
</head>
<body class="hold-transition sidebar-collapse layout-navbar-fixed">
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
            <h1><strong>Listado de clientes</strong></h1>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
            <div class="row d-flex text-center justify-content-end p-2">
              <button type="button" class="col-1 btn btn-success btn-block mb-0 mt-0 mr-1 v-accion-ver-todos" data-accion="vertodos" data-filtro="todos"><i class="fa fa-eye"></i> Todos</button>
              <button type="button" class="col-1 btn btn-warning btn-block mb-0 mt-0 mr-1 v-accion-ver-todos" data-accion="vertodos" data-filtro="activos"><i class="fa fa-eye-slash"></i> Activos</button>
              <button type="button" class="col-1 btn btn-warning btn-block mb-0 mt-0 mr-1 v-accion-ver-todos" data-accion="vertodos" data-filtro="potenciales"><i class="fa fa-eye-slash"></i> Potenciales</button>
              <button type="button" class="col-1 btn btn-warning btn-block mb-0 mt-0 mr-1 v-accion-ver-todos" data-accion="vertodos" data-filtro="desactivados"><i class="fa fa-eye-slash"></i> Desactivados</button>
              <button onclick="window.location.href='cliente_form.php'" type="button" class="col-1 btn btn-success btn-block mb-0 mt-0 mr-0"><i class="fa fa-plus-circle"></i> Agregar</button>
            </div>
        <div class="row">
          <div class="col-12">
            <div class="card">
              <!-- /.card-header -->
              <div class="card-body">
                <table id="current_table" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Alta</th>
                    <th>Estado</th>
                    <th>Razón Social</th>
                    <th>CUIT</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Contacto</th>
                    <th>Cel. contactco</th>
                    <th>Email contacto</th>
                    <th>Acciones</th>
                  </tr>
                  </thead>
                  <tbody>
                   <?php echo $filas?> 
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

<script>
  $(function () {
    $("#current_table").DataTable({
      "dom": '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
      "responsive": true, "lengthChange": true, "autoWidth": false,
      "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
      "language": {"url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"},
      "columns": [{ "width": "1%" }, { "width": "6%" }, { "width": "5%" }, null, { "width": "7%" }, { "width": "9%" }, { "width": "7%" }, { "width": "10%" }, { "width": "9%" }, { "width": "10%" }, { "width": "6%" }]
    }).buttons().container().appendTo('#current_table_wrapper .col-md-6:eq(0)');
  });
</script>
</body>
</html>
<script src="../07-funciones_js/clientesAcciones.js"></script>


