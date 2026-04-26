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
    $deleteIcon,
    '30_dias'
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

$rangosTiempoRapidos = array(
    '15_dias' => '15 dias',
    '30_dias' => '30 dias',
    'trimestre' => 'Trimestre',
    'semestre' => 'Semestre',
    'anio' => 'Año'
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

                    <div class="seguimiento-filtro-columna">
                      <div class="seguimiento-filtro-label">Filtros de tiempo</div>
                      <div class="seguimiento-filtro-linea">
                        <?php foreach ($rangosTiempoRapidos as $valorTiempo => $labelTiempo): ?>
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary seguimiento-filtro-btn seguimiento-filtro-tiempo-btn btn-filtro-rapido<?php echo $valorTiempo === '30_dias' ? ' is-active' : ''; ?>"
                            data-time-range="<?php echo htmlspecialchars($valorTiempo, ENT_QUOTES, 'UTF-8'); ?>"
                            data-variant="success"
                            aria-pressed="<?php echo $valorTiempo === '30_dias' ? 'true' : 'false'; ?>">
                            <?php echo htmlspecialchars($labelTiempo, ENT_QUOTES, 'UTF-8'); ?>
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
                          class="btn btn-sm btn-outline-secondary seguimiento-filtro-btn seguimiento-filtro-reset-all-btn btn-filtro-rapido"
                          data-filter-reset="all"
                          data-variant="success"
                          aria-pressed="false">
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
                <div id="seguimientoErrorCarga" class="alert alert-danger d-none" role="alert">
                  No se pudo actualizar el listado. Revisa la respuesta del servidor e intenta nuevamente.
                </div>
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
    visita: '',
    presupuesto: ''
  };

  const filtroTiempoSeguimiento = {
    value: '30_DIAS'
  };

  const MINIMO_BUSQUEDA_GLOBAL_TODOS = 3;
  let resetTodosSeguimientoActivo = false;
  let ultimoRangoTiempoSeguimiento = '30_DIAS';
  let tablaSeguimiento = null;
  let debounceBusquedaSeguimiento = null;
  let xhrRecargaSeguimiento = null;
  let secuenciaRecargaSeguimiento = 0;
  const columnasSeguimientoAjax = ['id_previsita', 'log_alta', 'cuit', 'razon_social', 'requerimiento_tecnico', 'estado_visita', 'fecha_visita', 'hora_visita'];
  const ayudaBusquedaGlobalSeguimientoHtml = `
    <div id="seguimientoAyudaBusquedaGlobalWrap" class="seguimiento-ayuda-busqueda-global-wrap d-none">
      <div id="seguimientoAyudaBusquedaGlobal" class="seguimiento-ayuda-busqueda-global small text-muted">
        En <strong>Todos</strong>, escribi al menos 3 caracteres en <strong>Buscar</strong> para consultar toda la base.
      </div>
    </div>
  `;

  function normalizarEstadoFiltroSeguimiento(estado) {
    return String(estado || '').trim().toUpperCase();
  }

  function normalizarRangoTiempoSeguimiento(rango) {
    return String(rango || '').trim().toUpperCase();
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

    let coincideVisita = true;
    if (filtroRapidoSeguimiento.visita) {
      const estadoVisitaFila = normalizarEstadoFiltroSeguimiento(row.getAttribute('data-estado-visita'));
      coincideVisita = estadoVisitaFila === filtroRapidoSeguimiento.visita;
    }

    let coincidePresupuesto = true;
    if (filtroRapidoSeguimiento.presupuesto) {
      const estadoPresupuestoFila = normalizarEstadoFiltroSeguimiento(row.getAttribute('data-estado-presupuesto'));
      coincidePresupuesto = estadoPresupuestoFila === filtroRapidoSeguimiento.presupuesto;
    }

    return coincideVisita && coincidePresupuesto;
  });

  function aplicarEstadoVisualBotonFiltro($boton, activo) {
    $boton.toggleClass('is-active', activo);
    $boton.attr('aria-pressed', activo ? 'true' : 'false');
  }

  function actualizarBotonesFiltrosRapidosSeguimiento() {
    $('.seguimiento-filtro-btn[data-filter-target]').each(function () {
      const $boton = $(this);
      const target = String($boton.data('filter-target') || '').trim();
      const valor = normalizarEstadoFiltroSeguimiento($boton.data('filter-value'));
      const activo = target !== '' && Object.prototype.hasOwnProperty.call(filtroRapidoSeguimiento, target)
        && filtroRapidoSeguimiento[target] === valor;
      aplicarEstadoVisualBotonFiltro($boton, activo);
    });

    $('.seguimiento-filtro-btn[data-time-range]').each(function () {
      const $boton = $(this);
      const valor = normalizarRangoTiempoSeguimiento($boton.data('time-range'));
      const activo = valor !== '' && valor === filtroTiempoSeguimiento.value;
      aplicarEstadoVisualBotonFiltro($boton, activo);
    });

    $('[data-filter-reset="all"]').each(function () {
      aplicarEstadoVisualBotonFiltro($(this), resetTodosSeguimientoActivo);
    });
  }

  function aplicarFiltrosRapidosSeguimiento(tabla) {
    actualizarBotonesFiltrosRapidosSeguimiento();
    tabla.draw(false);
  }

  function todosActivoSeguimiento() {
    return resetTodosSeguimientoActivo === true && filtroTiempoSeguimiento.value === '';
  }

  function hayFiltrosRapidosActivosSeguimiento() {
    return filtroRapidoSeguimiento.visita !== '' || filtroRapidoSeguimiento.presupuesto !== '';
  }

  function todosUsaBusquedaGlobalSeguimiento() {
    return todosActivoSeguimiento() && !hayFiltrosRapidosActivosSeguimiento();
  }

  function obtenerBusquedaActualSeguimiento() {
    return String($('#current_table_filter input[type="search"]').val() || '').trim();
  }

  function sincronizarAyudaBusquedaGlobalSeguimiento() {
    const $contenedorCentral = $('#current_table_wrapper .dt-center-in-div');
    if (!$contenedorCentral.length) {
      return;
    }

    if (!$contenedorCentral.find('#seguimientoAyudaBusquedaGlobalWrap').length) {
      $contenedorCentral.append(ayudaBusquedaGlobalSeguimientoHtml);
    }
  }

  function mostrarAyudaBusquedaGlobalSeguimiento(mostrar) {
    sincronizarAyudaBusquedaGlobalSeguimiento();
    $('#current_table_wrapper #seguimientoAyudaBusquedaGlobalWrap').toggleClass('d-none', !mostrar);
  }

  function mostrarErrorCargaSeguimiento(mostrar) {
    $('#seguimientoErrorCarga').toggleClass('d-none', !mostrar);
  }

  function limpiarBusquedaSeguimiento(tabla) {
    if (tabla && typeof tabla.search === 'function') {
      tabla.search('');
    }
    $('#current_table_filter input[type="search"]').val('');
  }

  function cancelarRecargaTablaSeguimiento() {
    if (xhrRecargaSeguimiento && typeof xhrRecargaSeguimiento.abort === 'function' && xhrRecargaSeguimiento.readyState !== 4) {
      xhrRecargaSeguimiento.abort();
    }

    xhrRecargaSeguimiento = null;
  }

  function invalidarRecargaTablaSeguimiento() {
    secuenciaRecargaSeguimiento += 1;
    cancelarRecargaTablaSeguimiento();
  }

  function obtenerTiempoAjaxSeguimiento() {
    switch (filtroTiempoSeguimiento.value) {
      case '15_DIAS':
        return '15_dias';
      case '30_DIAS':
        return '30_dias';
      case 'TRIMESTRE':
        return 'trimestre';
      case 'SEMESTRE':
        return 'semestre';
      case 'ANIO':
        return 'anio';
      default:
        return '';
    }
  }

  function obtenerRangoTiempoRestauracionSeguimiento() {
    return ultimoRangoTiempoSeguimiento || '30_DIAS';
  }

  function restaurarInputBusquedaSeguimiento(busquedaVisible, opciones) {
    const opts = opciones && typeof opciones === 'object' ? opciones : {};
    const $input = $('#current_table_filter input[type="search"]');
    if (!$input.length) {
      return;
    }

    const valor = typeof busquedaVisible === 'string' ? busquedaVisible : '';
    $input.val(valor);

    if (opts.restaurarFoco === true) {
      const input = $input.get(0);
      if (input && typeof input.focus === 'function') {
        input.focus();
      }

      const posicion = typeof opts.cursorPosicion === 'number'
        ? Math.max(0, Math.min(opts.cursorPosicion, valor.length))
        : valor.length;

      if (input && typeof input.setSelectionRange === 'function') {
        input.setSelectionRange(posicion, posicion);
      }
    }
  }

  function obtenerEstadoInputBusquedaSeguimiento() {
    const $input = $('#current_table_filter input[type="search"]');
    const input = $input.get(0);
    if (!input) {
      return {
        restaurarFoco: false,
        cursorPosicion: null
      };
    }

    const activo = document.activeElement === input;
    return {
      restaurarFoco: activo,
      cursorPosicion: activo && typeof input.selectionStart === 'number'
        ? input.selectionStart
        : null
    };
  }

  function renderizarTablaSeguimientoDesdeFilas(filasHtml, opciones) {
    const opts = opciones && typeof opciones === 'object' ? opciones : {};
    const busquedaVisible = typeof opts.busquedaVisible === 'string' ? opts.busquedaVisible : '';
    const usarBusquedaCliente = opts.usarBusquedaCliente === true;
    const restaurarFocoBusqueda = opts.restaurarFocoBusqueda === true;
    const cursorBusqueda = typeof opts.cursorBusqueda === 'number' ? opts.cursorBusqueda : null;

    if ($.fn.dataTable && $.fn.dataTable.isDataTable('#current_table')) {
      $('#current_table').DataTable().destroy();
    }

    $('#current_table tbody').html(filasHtml || '');

    const nuevaTabla = inicializarDataTableSeguimiento();
    tablaSeguimiento = nuevaTabla;
    window.tablaSeguimientoObra = nuevaTabla;
    vincularEventosTablaSeguimiento(nuevaTabla);
    vincularBusquedaInputSeguimiento(nuevaTabla);
    mostrarAyudaBusquedaGlobalSeguimiento(todosUsaBusquedaGlobalSeguimiento());

    nuevaTabla.search(usarBusquedaCliente ? busquedaVisible : '');
    aplicarFiltrosRapidosSeguimiento(nuevaTabla);
    restaurarInputBusquedaSeguimiento(busquedaVisible, {
      restaurarFoco: restaurarFocoBusqueda,
      cursorPosicion: cursorBusqueda
    });
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

    $('#current_table_wrapper .dt-buttons').remove();
    tabla.buttons().container().appendTo('#current_table_wrapper .col-md-6:eq(0)');
    return tabla;
  }

  function vincularEventosTablaSeguimiento(tabla) {
    if (!tabla || typeof tabla.on !== 'function') {
      return;
    }

    tabla.on('search.dt', function () {
      actualizarBotonesFiltrosRapidosSeguimiento();
    });
  }

  function ejecutarBusquedaGlobalTodosSeguimiento(busqueda) {
    const termino = String(busqueda || '').trim();
    const estadoInputBusqueda = obtenerEstadoInputBusquedaSeguimiento();

    if (!todosUsaBusquedaGlobalSeguimiento()) {
      mostrarAyudaBusquedaGlobalSeguimiento(false);
      return;
    }

    if (termino.length < MINIMO_BUSQUEDA_GLOBAL_TODOS) {
      invalidarRecargaTablaSeguimiento();
      mostrarErrorCargaSeguimiento(false);
      mostrarAyudaBusquedaGlobalSeguimiento(true);
      renderizarTablaSeguimientoDesdeFilas('', {
        busquedaVisible: termino,
        usarBusquedaCliente: false,
        restaurarFocoBusqueda: estadoInputBusqueda.restaurarFoco,
        cursorBusqueda: estadoInputBusqueda.cursorPosicion
      });
      return;
    }

    mostrarErrorCargaSeguimiento(false);
    mostrarAyudaBusquedaGlobalSeguimiento(true);
    recargarTablaSeguimientoPorTiempo({
      busqueda: termino,
      usarBusquedaCliente: false,
      restaurarFocoBusqueda: estadoInputBusqueda.restaurarFoco,
      cursorBusqueda: estadoInputBusqueda.cursorPosicion
    });
  }

  function vincularBusquedaInputSeguimiento(tabla) {
    const $input = $('#current_table_filter input[type="search"]');
    if (!$input.length) {
      return;
    }

    $input.off('.DT');
    $input.off('.seguimientoSearch');

    $input.on('input.seguimientoSearch', function () {
      const valor = String($(this).val() || '');

      if (debounceBusquedaSeguimiento) {
        clearTimeout(debounceBusquedaSeguimiento);
      }

      if (todosUsaBusquedaGlobalSeguimiento()) {
        debounceBusquedaSeguimiento = setTimeout(function () {
          ejecutarBusquedaGlobalTodosSeguimiento(valor);
        }, 300);
        return;
      }

      mostrarErrorCargaSeguimiento(false);
      mostrarAyudaBusquedaGlobalSeguimiento(false);
      tabla.search(valor).draw();
    });
  }

  function recargarTablaSeguimientoPorTiempo(opts) {
    const opciones = opts && typeof opts === 'object' ? opts : {};
    const limpiarBusqueda = opciones.limpiarBusqueda === true;
    const usarBusquedaCliente = opciones.usarBusquedaCliente !== false;
    const aplicarFiltrosServidor = opciones.aplicarFiltrosServidor === true;
    const secuenciaSolicitud = secuenciaRecargaSeguimiento + 1;
    const estadoInputBusqueda = obtenerEstadoInputBusquedaSeguimiento();
    const restaurarFocoBusqueda = opciones.restaurarFocoBusqueda === true || estadoInputBusqueda.restaurarFoco === true;
    const cursorBusqueda = typeof opciones.cursorBusqueda === 'number'
      ? opciones.cursorBusqueda
      : estadoInputBusqueda.cursorPosicion;
    const estadoVisitaFiltro = aplicarFiltrosServidor ? filtroRapidoSeguimiento.visita : '';
    const estadoPresupuestoFiltro = aplicarFiltrosServidor ? filtroRapidoSeguimiento.presupuesto : '';
    const busquedaActual = typeof opciones.busqueda === 'string'
      ? opciones.busqueda
      : (limpiarBusqueda
      ? ''
      : obtenerBusquedaActualSeguimiento());

    if (debounceBusquedaSeguimiento) {
      clearTimeout(debounceBusquedaSeguimiento);
    }

    secuenciaRecargaSeguimiento = secuenciaSolicitud;
    cancelarRecargaTablaSeguimiento();

    xhrRecargaSeguimiento = $.ajax({
      url: '../03-controller/presupuestosController.php',
      type: 'POST',
      dataType: 'json',
      data: {
        ajax: 'on',
        funcion: 'poblarDatableAll',
        tds: columnasSeguimientoAjax,
        filtro: 'todos',
        tiempo: obtenerTiempoAjaxSeguimiento(),
        busqueda: usarBusquedaCliente ? '' : busquedaActual,
        estado_visita: estadoVisitaFiltro,
        estado_presupuesto: estadoPresupuestoFiltro
      },
      success: function (filasHtml) {
        if (secuenciaSolicitud !== secuenciaRecargaSeguimiento) {
          return;
        }

        xhrRecargaSeguimiento = null;
        mostrarErrorCargaSeguimiento(false);
        renderizarTablaSeguimientoDesdeFilas(filasHtml || '', {
          busquedaVisible: busquedaActual,
          usarBusquedaCliente: usarBusquedaCliente,
          restaurarFocoBusqueda: restaurarFocoBusqueda,
          cursorBusqueda: cursorBusqueda
        });
      },
      error: function (jqXHR, textStatus) {
        if (secuenciaSolicitud !== secuenciaRecargaSeguimiento) {
          return;
        }

        xhrRecargaSeguimiento = null;
        if (textStatus === 'abort') {
          return;
        }

        mostrarErrorCargaSeguimiento(true);
      }
    });
  }

  function sincronizarModoTodosSeguimiento(opciones) {
    const opts = opciones && typeof opciones === 'object' ? opciones : {};
    const estadoInputBusqueda = obtenerEstadoInputBusquedaSeguimiento();
    const restaurarFocoBusqueda = opts.restaurarFocoBusqueda === true || estadoInputBusqueda.restaurarFoco === true;
    const cursorBusqueda = typeof opts.cursorBusqueda === 'number'
      ? opts.cursorBusqueda
      : estadoInputBusqueda.cursorPosicion;
    const busquedaActual = typeof opts.busqueda === 'string'
      ? opts.busqueda
      : obtenerBusquedaActualSeguimiento();

    mostrarErrorCargaSeguimiento(false);
    actualizarBotonesFiltrosRapidosSeguimiento();

    if (!todosActivoSeguimiento()) {
      mostrarAyudaBusquedaGlobalSeguimiento(false);
      return;
    }

    if (hayFiltrosRapidosActivosSeguimiento()) {
      mostrarAyudaBusquedaGlobalSeguimiento(false);
      recargarTablaSeguimientoPorTiempo({
        busqueda: busquedaActual,
        usarBusquedaCliente: true,
        aplicarFiltrosServidor: true,
        restaurarFocoBusqueda: restaurarFocoBusqueda,
        cursorBusqueda: cursorBusqueda
      });
      return;
    }

    if (busquedaActual.length >= MINIMO_BUSQUEDA_GLOBAL_TODOS) {
      mostrarAyudaBusquedaGlobalSeguimiento(true);
      recargarTablaSeguimientoPorTiempo({
        busqueda: busquedaActual,
        usarBusquedaCliente: false,
        restaurarFocoBusqueda: restaurarFocoBusqueda,
        cursorBusqueda: cursorBusqueda
      });
      return;
    }

    invalidarRecargaTablaSeguimiento();
    mostrarAyudaBusquedaGlobalSeguimiento(true);
    renderizarTablaSeguimientoDesdeFilas('', {
      busquedaVisible: busquedaActual,
      usarBusquedaCliente: false,
      restaurarFocoBusqueda: restaurarFocoBusqueda,
      cursorBusqueda: cursorBusqueda
    });
  }

  function activarModoTodosSeguimiento() {
    filtroRapidoSeguimiento.visita = '';
    filtroRapidoSeguimiento.presupuesto = '';

    if (filtroTiempoSeguimiento.value) {
      ultimoRangoTiempoSeguimiento = filtroTiempoSeguimiento.value;
    }

    filtroTiempoSeguimiento.value = '';
    resetTodosSeguimientoActivo = true;
    sincronizarModoTodosSeguimiento();
  }

  $(function () {
    tablaSeguimiento = inicializarDataTableSeguimiento();
    window.tablaSeguimientoObra = tablaSeguimiento;
    vincularEventosTablaSeguimiento(tablaSeguimiento);
    vincularBusquedaInputSeguimiento(tablaSeguimiento);
    mostrarErrorCargaSeguimiento(false);
    mostrarAyudaBusquedaGlobalSeguimiento(false);
    actualizarBotonesFiltrosRapidosSeguimiento();

    $(document).on('click', '.seguimiento-filtro-btn[data-filter-target]', function () {
      const $boton = $(this);
      const target = String($boton.data('filter-target') || '').trim();
      const valor = normalizarEstadoFiltroSeguimiento($boton.data('filter-value'));
      const estabaTodosActivo = todosActivoSeguimiento();

      if (!target || !valor || !Object.prototype.hasOwnProperty.call(filtroRapidoSeguimiento, target)) {
        return;
      }

      const mismoFiltroActivo = filtroRapidoSeguimiento[target] === valor;

      if (mismoFiltroActivo) {
        filtroRapidoSeguimiento[target] = '';
      } else {
        filtroRapidoSeguimiento[target] = valor;
      }

      if (estabaTodosActivo) {
        resetTodosSeguimientoActivo = true;
        filtroTiempoSeguimiento.value = '';
        sincronizarModoTodosSeguimiento();
        return;
      }

      resetTodosSeguimientoActivo = false;
      mostrarErrorCargaSeguimiento(false);
      mostrarAyudaBusquedaGlobalSeguimiento(false);
      aplicarFiltrosRapidosSeguimiento(tablaSeguimiento);
    });

    $(document).on('click', '.seguimiento-filtro-btn[data-time-range]', function () {
      const $boton = $(this);
      const valor = normalizarRangoTiempoSeguimiento($boton.data('time-range'));
      const estabaTodosActivo = todosActivoSeguimiento();

      if (!valor || filtroTiempoSeguimiento.value === valor) {
        return;
      }

      filtroTiempoSeguimiento.value = valor;
      ultimoRangoTiempoSeguimiento = valor;
      resetTodosSeguimientoActivo = false;
      mostrarErrorCargaSeguimiento(false);
      mostrarAyudaBusquedaGlobalSeguimiento(false);
      actualizarBotonesFiltrosRapidosSeguimiento();

      if (estabaTodosActivo) {
        limpiarBusquedaSeguimiento(tablaSeguimiento);
        recargarTablaSeguimientoPorTiempo({
          limpiarBusqueda: true
        });
        return;
      }

      recargarTablaSeguimientoPorTiempo();
    });

    $(document).on('click', '[data-filter-reset="all"]', function () {
      activarModoTodosSeguimiento();
    });
  });
</script>

</body>
</html>
<script src="../07-funciones_js/presupuestosAcciones.js"></script>
