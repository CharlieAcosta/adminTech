<?php  
session_start();
define('BASE_URL', $_SESSION["base_url"]);
include_once '../06-funciones_php/funciones.php';
include_once '../03-controller/jornalesController.php'; // conecta a la base de datos

include_once '../06-funciones_php/auditoria.php';
registrarNavegacion('TIPO JORNALES - Listado');

$filas = poblarDatableAll(array('jornal_id', 'jornal_descripcion', 'jornal_codigo', 'jornal_valor', 'jornal_estado'), 'php', 'sinEliminados');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ADMINTECH | Tipos de Jornales</title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../dist/css/custom.css">
  <link rel="stylesheet" href="../05-plugins/sweetalert2/sweetalert2.min.css">

  <script src='../05-plugins/pdfmake/pdfmake.min.js'></script>
  <script src='../05-plugins/pdfmake/vfs_fonts.js'></script>
</head>
<body class="hold-transition sidebar-collapse layout-navbar-fixed">
<div class="wrapper">

  <?php include '../01-views/layout/navbar_layout.php';?> 

  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><strong>Tipos de Jornales | Listado</strong></h1>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row d-flex text-center justify-content-end p-2">
        <button type="button" class="col-1 btn btn-primary btn-block mb-0 mt-0 mr-1 v-btn-accion" data-accion="volver"><i class="fa fa-arrow-left mr-1"></i>Volver</button>  
        <button onclick="window.location.href='jornales_form.php'" type="button" class="col-1 btn btn-success btn-block mb-0 mt-0 mr-1"><i class="fa fa-plus-circle"></i> Agregar</button>       
        </div>
        <div class="row">
          <div class="col-12">
            <div class="card mb-2">
              <div class="card-body">
                <table id="current_table" class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Descripción</th>
                      <th>Código</th>
                      <th>Valor</th>
                      <th>Estado</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php echo $filas ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
  
  <?php include '../01-views/layout/footer_layout.php';?>

  <aside class="control-sidebar control-sidebar-dark"></aside>
</div>

<script src="../05-plugins/jquery/jquery.min.js"></script>
<script src="../05-plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
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
<script src="../05-plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="../dist/js/adminlte.min.js"></script>
<script src="../07-funciones_js/funciones.js"></script>

<script>
  $(function () {
    $("#current_table").DataTable({
      "dom": '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
      "responsive": true, "lengthChange": true, "autoWidth": false,
      "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
      "language": {"url": "../05-plugins/datatables/es-ES.json"},
      "columns": [
        { "width": "5%" },   // ID
        { "width": "30%" },  // Descripción
        { "width": "15%" },  // Código
        { "width": "15%" },  // Valor
        { "width": "15%" },  // Estado
        { "width": "10%" }   // Acciones
      ],
      "order": [[1, "asc"]]
    }).buttons().container().appendTo('#current_table_wrapper .col-md-6:eq(0)');
  });
</script>
<script src="../07-funciones_js/jornalesAcciones.js"></script>
</body>
</html>

