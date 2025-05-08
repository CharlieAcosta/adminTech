<?php  
session_start();
$usuarioLogueado = json_encode($_SESSION['usuario']);
$usuario_logueado = $_SESSION['usuario'];
define('BASE_URL', $_SESSION["base_url"]);
include_once '../06-funciones_php/funciones.php';
sesion(); // Verifica si hay usuario sesionado

include_once '../06-funciones_php/auditoria.php';
registrarNavegacion('AUDITORIA');

// traemos los datos
$table = "auditoria";
$columns = ['fecha_hora', 'perfil_usuario'];
$comparisons = ['>', '<>'];
$values = [fechaAjustada('timestamp', -1, 'dias'), 'Super Administrador'];
$orderBy = [['id_usuario', 'ASC']];
$selectColumns = [];
$eventos = db_select_with_filters_V2($table, $columns, $comparisons, $values, $orderBy, $selectColumns);

$keysArray = [
    ['id_usuario', 'usuarios', 'id_usuario', ['apellidos', 'nombres']],
];

// enriquecemos el array $eventos con datos faltantes
$eventosJoineado = arrayJoin($eventos, $keysArray);

// ordenamos el array por apellido
$eventosJoineado = sortArrayMultiple($eventosJoineado, ['fecha_hora'=>'asc','apellidos' => 'asc']);
//dd($eventosJoineado);

//armamos la filas de la tabla
$claves = [
'fecha_hora',
['apellidos','nombres',' '],
'perfil_usuario',
'accion_realizada',  
'modulo_afectado', 
'ip_origen',
'navegador',
'metodo_acceso',
'url_acceso'
];

$acciones = [];

$clases = [
    ['aud_fecha_hora'],
    ['aud_agente'], 
    ['aud_perfil_usuario'], 
    ['aud_accion_realizada'],  
    ['aud_modulo_afectado'],    
    ['aud_ip_origen'],
    ['aud_dispositivo'],
    ['aud_navegador'],
    ['aud_metodo_acceso'],
    ['aud_url_acceso']
];

$trData = [];

$filas = registrosToFilas($eventosJoineado, $claves, $acciones, $clases, $trData);


//dd($filas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ADMINTECH | Auditoría</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="../05-plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="../05-plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../dist/css/custom.css">
  <script src='../05-plugins/pdfmake/pdfmake.min.js'></script>
  <script src='../05-plugins/pdfmake/vfs_fonts.js'></script>
  <!-- Select2 -->
  <link rel="stylesheet" href="../05-plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../05-plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
</head>
<body class="hold-transition sidebar-collapse layout-navbar-fixed bg-yellow-soft">
<div class="wrapper">
  <!-- Navbar -->
  <?php include '../01-views/layout/navbar_layout.php'; ?> 
  <!-- Main Sidebar Container -->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-0">
          <div class="col-sm-6">
            <h1><strong>Auditoría</strong></h1>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="mb-2">
              <div class="d-flex align-items-end justify-content-between flex-wrap">
                <!-- Campo Fecha -->
                <div class="flex-grow-1 mr-3">
                  <label for="fecha" class="mb-0">Fecha</label>
                  <div class="input-group">
                    <input type="date" class="form-control" id="fecha_filter" placeholder="dd/mm/aaaa" max='<?php echo fechaAjustada("AAAA-MM-DD", -1, "dias"); ?>' onkeydown="return false;">

                  </div>
                </div>

                <!-- Campo Usuario -->
                <div class="flex-grow-1 mr-3">
                  <label for="usuario" class="mb-0">Usuario</label>
                  <select id="usuario_filter" class="form-control select2bs4">
                    <!-- Opciones cargadas dinámicamente -->
                  </select>
                </div>

                <!-- Botones -->
                <div class="d-flex">
                  <button type="button" class="btn btn-primary mr-2" style="width: 120px;" onclick="filtrarAuditoria()">Filtrar</button>
                  <button type="button" class="btn btn-secondary" style="width: 120px;" onclick="limpiarFiltros()">Limpiar</button>
                </div>
              </div>
            </div>

            <div class="card">
              <!-- /.card-header -->
              <div class="card-body">
                <table id="auditoria_table" class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>Fecha</th>
                      <th>Agente</th>
                      <th>Perfil</th>
                      <th>Acción</th>
                      <th>Módulo</th>
                      <th>IP</th>
                      <th>Browser</th>
                      <th>Acceso</th>
                      <th>Url</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php echo $filas; ?>
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
  <?php include '../01-views/layout/footer_layout.php'; ?>
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
<!-- Select2 -->
<script src="../05-plugins/select2/js/select2.full.min.js"></script>
<script src="../05-plugins/select2/js/i18n/es.js"></script>

<!-- Page specific script -->
<script>
$(function () {
  // Inicializar DataTable con botones de exportación
  $("#auditoria_table").DataTable({
    "dom": '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
    "responsive": true,
    "lengthChange": true,
    "autoWidth": false,
    "pageLength": 100,
    "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
    "language": { "url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json" },
    "order": [
      [0, 'desc'],
      [1, 'desc']
    ]
  }).buttons().container().appendTo('#auditoria_table_wrapper .col-md-6:eq(0)');

  // Inicializar Select2
  $('#usuario_filter').select2({
    theme: 'bootstrap4',
    language: "es",
    width: '100%',
    placeholder: 'Seleccione un usuario',
    allowClear: true
  });
});

// Funciones de filtro y limpiar
function filtrarAuditoria() {
  // Aquí irá la lógica para aplicar filtros
}

function limpiarFiltros() {
  // Aquí irá la lógica para limpiar filtros
  $('#usuario_filter').val(null).trigger('change');
  $('#fecha_filter').val('');
}
</script>
</body>
</html>

