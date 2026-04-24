<?php  
// segumiento_de_obra_listado.php

session_start();
define('BASE_URL', $_SESSION["base_url"]);  
include_once '../06-funciones_php/funciones.php';
sesion(); //Verifica si hay usuario sesionado
include_once '../06-funciones_php/auditoria.php';
registrarNavegacion('SEGUIMIENTO DE OBRA | Listado');

$perfil = $_SESSION['usuario']['perfil'];
$deleteIcon = array('Super Administrador','Administrador');

include_once '../03-controller/presupuestosController.php'; //conecta a la base de datos

// poblarDatableAll(columnas de la tablas, php o ajax, filtro); [reference] 
$filas = poblarDatableAll(
    array('id_previsita', 'log_alta', 'cuit', 'razon_social', 'requerimiento_tecnico', 'estado_visita', 'fecha_visita', 'hora_visita'),
    'php',
    'todos',
    $perfil,
    $deleteIcon
);

$estadosVisitaRapidos = array(
    'PROGRAMADA' => 'Programada',
    'REPROGRAMADA' => 'Reprogramada',
    'EJECUTADA' => 'Ejecutada',
    'CANCELADA' => 'Cancelada',
    'VENCIDA' => 'Vencida'
);

$estadosPresupuestoRapidos = array(
    'PENDIENTE' => 'Pendiente',
    'BORRADOR' => 'Borrador',
    'EMITIDO' => 'Emitido',
    'ENVIADO' => 'Enviado',
    'RECIBIDO' => 'Recibido',
    'RESOLICITADO' => 'Resolicitado',
    'APROBADO' => 'Aprobado',
    'RECHAZADO' => 'Rechazado',
    'CANCELADO' => 'Cancelado'
);
?> 

<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ADMINTECH | Seguimiento de obra | Listado</title>

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
  <!-- Agregar SweetAlert2 CSS -->
  <link rel="stylesheet" href="../05-plugins/sweetalert2/sweetalert2.min.css">


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
            <h1><strong>Seguimiento de obra | Listado</strong></h1>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
            <div class="row px-2 pt-2 pb-1">
              <div class="col-12">
                <div class="seguimiento-toolbar">
                  <div class="seguimiento-toolbar-filtros">
                    <div class="seguimiento-filtro-columna">
                      <div class="seguimiento-filtro-label">Filtros de visitas</div>
                      <div class="seguimiento-filtro-linea">
                        <?php foreach ($estadosVisitaRapidos as $valorFiltro => $labelFiltro): ?>
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-info seguimiento-filtro-btn btn-filtro-rapido"
                            data-filter-target="visita"
                            data-filter-value="<?php echo htmlspecialchars($valorFiltro, ENT_QUOTES, 'UTF-8'); ?>"
                            data-variant="info">
                            <?php echo htmlspecialchars($labelFiltro, ENT_QUOTES, 'UTF-8'); ?>
                          </button>
                        <?php endforeach; ?>
                      </div>
                    </div>

                    <span class="seguimiento-filtro-separador">|</span>

                    <div class="seguimiento-filtro-columna">
                      <div class="seguimiento-filtro-label">Filtros de presupuesto</div>
                      <div class="seguimiento-filtro-linea">
                        <?php foreach ($estadosPresupuestoRapidos as $valorFiltro => $labelFiltro): ?>
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-primary seguimiento-filtro-btn btn-filtro-rapido"
                            data-filter-target="presupuesto"
                            data-filter-value="<?php echo htmlspecialchars($valorFiltro, ENT_QUOTES, 'UTF-8'); ?>"
                            data-variant="primary">
                            <?php echo htmlspecialchars($labelFiltro, ENT_QUOTES, 'UTF-8'); ?>
                          </button>
                        <?php endforeach; ?>
                      </div>
                    </div>

                    <span class="seguimiento-filtro-separador">|</span>

                    <div class="seguimiento-filtro-columna seguimiento-filtro-columna-todos">
                      <div class="seguimiento-filtro-label seguimiento-filtro-label-placeholder">&nbsp;</div>
                      <div class="seguimiento-filtro-linea">
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-success seguimiento-filtro-btn btn-filtro-rapido is-active"
                          data-filter-reset="all"
                          data-variant="success">
                          Todos
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="seguimiento-toolbar-accion">
                    <button onclick="window.location.href='seguimiento_form.php'" type="button" class="btn btn-success">
                      <i class="fa fa-plus-circle"></i> Agregar
                    </button>
                  </div>
                </div>
              </div>
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
                    <th>Ingreso</th>
                    <th>CUIT</th>
                    <th>Razón Social</th>
                    <th>Descripci&oacute;n</th>
                    <th>Visita</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Presupuesto</th>
                    <th>Orden de compra</th>
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
  <?php include '../01-views/modals/modal_historial_presupuesto.php'; ?>
  <?php include '../01-views/modals/modal_documentos_emitidos_presupuesto.php'; ?>
  <?php include '../01-views/modals/modal_enviar_documento_emitido_presupuesto.php'; ?>

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
<!-- SweetAlert2 -->
<script src="../05-plugins/sweetalert2/sweetalert2.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>
<!-- Page specific script -->

<!-- funciones customizadas -->
<script src="../07-funciones_js/funciones.js"></script>

<script>
  const dataTableSeguimientoLanguage = {
    processing: "Procesando...",
    lengthMenu: "Mostrar _MENU_ registros",
    zeroRecords: "No se encontraron resultados",
    emptyTable: "No hay datos disponibles en esta tabla",
    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
    infoEmpty: "Mostrando 0 a 0 de 0 registros",
    infoFiltered: "(filtrado de _MAX_ registros totales)",
    loadingRecords: "Cargando...",
    search: "Buscar:",
    paginate: {
      first: "Primero",
      last: "Ultimo",
      next: "Siguiente",
      previous: "Anterior"
    },
    aria: {
      sortAscending: ": Activar para ordenar la columna de manera ascendente",
      sortDescending: ": Activar para ordenar la columna de manera descendente"
    },
    buttons: {
      copy: "Copiar",
      csv: "CSV",
      excel: "Excel",
      pdf: "PDF",
      print: "Imprimir",
      colvis: "Columnas"
    }
  };

  // --- Orden correcto para fechas en formato DD-MM-YYYY (DataTables) ---
  // Convierte "DD-MM-YYYY" -> número YYYYMMDD para ordenar real, no por texto.
  jQuery.fn.dataTable.ext.type.order['date-eu-pre'] = function (d) {
    if (!d) return 0;

    // Si viene con espacios, o HTML (por ejemplo <span>), lo limpiamos:
    d = ('' + d).replace(/<[^>]*>/g, '').trim();

    // Esperado: DD-MM-YYYY
    const parts = d.split('-');
    if (parts.length !== 3) return 0;

    const dd = parts[0].padStart(2, '0');
    const mm = parts[1].padStart(2, '0');
    const yyyy = parts[2];

    // Si no parece fecha válida, devolvemos 0 para no romper el sort
    if (yyyy.length !== 4) return 0;

    return parseInt(yyyy + mm + dd, 10);
  };

  const filtroRapidoSeguimiento = {
    target: '',
    value: ''
  };

  function normalizarEstadoFiltroSeguimiento(estado) {
    return String(estado || '').trim().toUpperCase();
  }

  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (!settings || !settings.nTable || settings.nTable.id !== 'current_table') {
      return true;
    }

    const rowData = settings.aoData[dataIndex];
    const row = rowData ? rowData.nTr : null;
    if (!row) {
      return true;
    }

    if (!filtroRapidoSeguimiento.target || !filtroRapidoSeguimiento.value) {
      return true;
    }

    const atributoEstado = filtroRapidoSeguimiento.target === 'presupuesto'
      ? 'data-estado-presupuesto'
      : 'data-estado-visita';
    const estadoFila = normalizarEstadoFiltroSeguimiento(row.getAttribute(atributoEstado));

    return estadoFila === filtroRapidoSeguimiento.value;
  });

  function aplicarEstadoVisualBotonFiltro($boton, activo) {
    $boton.toggleClass('is-active', activo);
    $boton.attr('aria-pressed', activo ? 'true' : 'false');
  }

  function actualizarBotonesFiltrosRapidosSeguimiento() {
    const hayBusquedaTexto = String($('#current_table_filter input[type="search"]').val() || '').trim() !== '';

    $('.seguimiento-filtro-btn[data-filter-target]').each(function () {
      const $boton = $(this);
      const target = String($boton.data('filter-target') || '').trim();
      const valor = normalizarEstadoFiltroSeguimiento($boton.data('filter-value'));
      const activo = target === filtroRapidoSeguimiento.target && valor === filtroRapidoSeguimiento.value;
      aplicarEstadoVisualBotonFiltro($boton, activo);
    });

    const sinFiltroActivo = !filtroRapidoSeguimiento.target || !filtroRapidoSeguimiento.value;
    $('[data-filter-reset="all"]').each(function () {
      aplicarEstadoVisualBotonFiltro($(this), sinFiltroActivo && !hayBusquedaTexto);
    });
  }

  function aplicarFiltrosRapidosSeguimiento(tabla) {
    actualizarBotonesFiltrosRapidosSeguimiento();
    tabla.draw(false);
  }

  function limpiarBusquedaSeguimiento(tabla) {
    tabla.search('');
    $('#current_table_filter input[type="search"]').val('');
  }

  function inicializarDataTableSeguimiento() {
    const tabla = $("#current_table").DataTable({
      "dom": '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
      "responsive": true,
      "lengthChange": true,
      "autoWidth": false,
      "pageLength": 100,
      "buttons": [
        { "extend": "copy", "text": "Copiar" },
        { "extend": "csv", "text": "CSV" },
        { "extend": "excel", "text": "Excel" },
        { "extend": "pdf", "text": "PDF" },
        { "extend": "print", "text": "Imprimir" },
        { "extend": "colvis", "text": "Columnas" }
      ],
      "language": dataTableSeguimientoLanguage,
      "columns": [
        { "width": "0.8%" },
        { "width": "5%" },  // Ingreso
        { "width": "6%" },
        { "width": "13%" },
        { "width": "18%" },
        { "width": "7%" },
        { "width": "5%" },
        { "width": "4%" },
        { "width": "9%" },
        { "width": "8%" },
        { "width": "9%" }
      ],

      "columnDefs": [
        { "targets": 1, "type": "date-eu" }, // Ingreso
        { "targets": 6, "type": "date-eu" }  // Fecha
      ],

      "order": [[5, "desc"], [6, "asc"], [7, "asc"]]
    });

    tabla.buttons().container().appendTo('#current_table_wrapper .col-md-6:eq(0)');
    return tabla;
  }

  $(function () {
    const tablaSeguimiento = inicializarDataTableSeguimiento();
    window.tablaSeguimientoObra = tablaSeguimiento;
    actualizarBotonesFiltrosRapidosSeguimiento();

    tablaSeguimiento.on('search.dt', function () {
      actualizarBotonesFiltrosRapidosSeguimiento();
    });

    $(document).on('click', '.seguimiento-filtro-btn[data-filter-target]', function () {
      const $boton = $(this);
      const target = String($boton.data('filter-target') || '').trim();
      const valor = normalizarEstadoFiltroSeguimiento($boton.data('filter-value'));

      if (!target || !valor) {
        return;
      }

      const mismoFiltroActivo = filtroRapidoSeguimiento.target === target && filtroRapidoSeguimiento.value === valor;

      if (mismoFiltroActivo) {
        filtroRapidoSeguimiento.target = '';
        filtroRapidoSeguimiento.value = '';
      } else {
        filtroRapidoSeguimiento.target = target;
        filtroRapidoSeguimiento.value = valor;
      }

      aplicarFiltrosRapidosSeguimiento(tablaSeguimiento);
    });

    $(document).on('click', '[data-filter-reset="all"]', function () {
      filtroRapidoSeguimiento.target = '';
      filtroRapidoSeguimiento.value = '';
      limpiarBusquedaSeguimiento(tablaSeguimiento);
      aplicarFiltrosRapidosSeguimiento(tablaSeguimiento);
    });
  });
</script>

</body>
</html>
<script src="../07-funciones_js/presupuestosAcciones.js"></script>
