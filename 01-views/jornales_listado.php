<?php  
session_start();
header('Content-Type: text/html; charset=utf-8');
define('BASE_URL', $_SESSION["base_url"]);
include_once '../06-funciones_php/funciones.php';
include_once '../03-controller/jornalesController.php'; // conecta a la base de datos

include_once '../06-funciones_php/auditoria.php';
registrarNavegacion('TIPO JORNALES - Listado');

$filas = poblarDatableAll(array('jornal_id', 'jornal_descripcion', 'jornal_codigo', 'jornal_valor', 'updated_at', 'jornal_estado'), 'php', 'sinEliminados');

$vigenciaValoresPermitidos = ['desactualizada', 'proxima_vencer', 'vigente'];
$vigenciaParam = (isset($_GET['vigencia']) && in_array($_GET['vigencia'], $vigenciaValoresPermitidos, true))
    ? $_GET['vigencia']
    : '';
$vigenciaTextoMap = [
    'desactualizada' => 'Desactualizada',
    'proxima_vencer' => 'Próxima a vencer',
    'vigente'        => 'Vigente',
];
$vigenciaTexto = $vigenciaParam !== '' ? $vigenciaTextoMap[$vigenciaParam] : '';
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

  <style>
    #current_table tbody td {
      vertical-align: middle !important;
    }

    #current_table thead th.jornales-col-actualizacion,
    #current_table tbody td.jornales-col-actualizacion {
      text-align: left !important;
    }

    #current_table thead th.jornales-col-vigencia,
    #current_table tbody td.jornales-col-vigencia {
      text-align: center !important;
    }

    #current_table tbody td.jornales-col-vigencia {
      padding-top: 0 !important;
      padding-bottom: 0 !important;
      vertical-align: middle !important;
    }

    #current_table .jornales-actualizacion-fecha {
      white-space: nowrap;
    }

    #current_table .jornales-vigencia-minicard {
      display: inline-flex;
      flex: 0 0 auto;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      box-sizing: border-box;
      width: 120px;
      height: 34px;
      padding: .15rem .65rem;
      border-radius: .32rem;
      gap: .14rem;
      line-height: 1;
      text-align: center;
      white-space: nowrap;
    }

    #current_table .jornales-vigencia-minicard-titulo {
      font-size: .64rem;
    }

    #current_table .jornales-vigencia-minicard-dias {
      font-size: .78rem;
      font-weight: 600;
    }

    #current_table .jornales-vigencia-export-separador {
      display: none;
    }

    #current_table tbody td.jornales-col-acciones {
      padding-left: .65rem !important;
      padding-right: .65rem !important;
      font-size: inherit;
      white-space: normal;
    }

    #current_table_wrapper .dataTables_filter {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      flex-wrap: wrap;
      gap: .5rem;
    }

    #current_table_wrapper .dataTables_filter label {
      margin-bottom: 0;
    }

    #current_table_wrapper .quitar-filtro-vigencia {
      flex: 0 0 auto;
      white-space: nowrap;
    }
  </style>

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
                      <th class="jornales-col-actualizacion">Última actualización</th>
                      <th class="jornales-col-vigencia">Vigencia</th>
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
  var vigenciaFiltroParam = <?php echo json_encode($vigenciaParam); ?>;
  var vigenciaTexto = <?php echo json_encode($vigenciaTexto); ?>;
  var vigenciaIdx = 6;

  if (vigenciaFiltroParam) {
    $.fn.dataTable.ext.search.push(function (settings, searchData) {
      if (settings.nTable.id !== 'current_table') return true;
      if (!vigenciaFiltroParam) return true;
      return searchData[vigenciaIdx] === vigenciaFiltroParam;
    });
  }

  function configurarFiltroInicialVigencia(api) {
    var $filterContainer = $('#current_table_wrapper .dataTables_filter');
    var $searchInput = $filterContainer.find('input[type="search"]');
    if (!$searchInput.length) return;
    if ($filterContainer.find('.quitar-filtro-vigencia').length) return;

    $searchInput.val(vigenciaTexto);

    var $btnQuitarFiltro = $('<button>', {
      type: 'button',
      'class': 'btn btn-sm btn-outline-secondary quitar-filtro-vigencia',
      title: 'Quitar filtro de vigencia',
      'aria-label': 'Quitar filtro de vigencia',
      html: '<i class="fas fa-times mr-1" aria-hidden="true"></i>Quitar filtro'
    });
    $filterContainer.append($btnQuitarFiltro);

    var searchEl = $searchInput[0];

    var cleanupFiltroVigencia = function () {
      vigenciaFiltroParam = null;
      searchEl.removeEventListener('input', cleanupFiltroVigencia, true);
      var url = new URL(window.location.href);
      url.searchParams.delete('vigencia');
      window.history.replaceState({}, '', url.pathname + url.search + url.hash);
      $btnQuitarFiltro.remove();
    };

    searchEl.addEventListener('input', cleanupFiltroVigencia, true);

    $btnQuitarFiltro.on('click', function () {
      cleanupFiltroVigencia();
      $searchInput.val('');
      api.search('').page('first').draw();
    });
  }

  $(function () {
    $("#current_table").DataTable({
      "dom": '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
      "responsive": true, "lengthChange": true, "autoWidth": false,
      "pageLength": 100, "lengthMenu": [10, 25, 50, 100],
      "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
      "language": {"url": "../05-plugins/datatables/es-ES.json"},
      "columns": [
        { "width": "5%" },   // ID
        { "width": "29%" },  // Descripción
        { "width": "14%" },  // Código
        { "width": "10%" },  // Valor
        { "width": "10%" },  // Estado
        { "width": "14%" },  // Última actualización
        { "width": "8%" },   // Vigencia
        { "width": "10%" }   // Acciones
      ],
      "order": [[5, "asc"]],
      "initComplete": function () {
        var api = this.api();
        api.buttons().container().appendTo('#current_table_wrapper .col-md-6:eq(0)');
        if (vigenciaFiltroParam) {
          configurarFiltroInicialVigencia(api);
        }
      }
    });
  });
</script>
<script src="../07-funciones_js/jornalesAcciones.js"></script>
</body>
</html>
