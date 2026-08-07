<?php
require_once __DIR__ . '/../06-funciones_php/sesionSegura.php';

if (!iniciarSesionAdmintech()) {
    header('Location: ../01-views/login.php');
    exit;
}

require_once __DIR__ . '/../06-funciones_php/funciones.php';
require_once __DIR__ . '/../04-modelo/feriadosModel.php';

sesion();

if (!usuarioAutenticadoPuedeAdministrarFeriados()) {
    header('Location: ../01-views/panel.php');
    exit;
}

define('BASE_URL', $_SESSION['base_url']);
header('Content-Type: text/html; charset=utf-8');

$zonaHorariaFeriados = new DateTimeZone('America/Argentina/Buenos_Aires');
$hoyFeriados = new DateTimeImmutable('today', $zonaHorariaFeriados);
$mananaFeriados = $hoyFeriados->modify('+1 day');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ADMINTECH | Feriados</title>

  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/sweetalert2/sweetalert2.min.css">
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../dist/css/custom.css">
  <link rel="stylesheet" href="../dist/css/feriados.css">
</head>
<body class="hold-transition sidebar-collapse layout-navbar-fixed">
<div class="wrapper">
  <?php include __DIR__ . '/layout/navbar_layout.php'; ?>

  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row align-items-center mb-2">
          <div class="col-sm-6">
            <h1><strong>Feriados</strong></h1>
          </div>
          <div class="col-sm-6 feriados-cabecera-acciones">
            <a href="../01-views/panel.php" class="btn btn-primary">
              <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i>Volver a Módulos
            </a>
            <button type="button" id="feriadosCrear" class="btn btn-success">
              <i class="fas fa-plus-circle mr-1" aria-hidden="true"></i>Nuevo feriado
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div id="feriadosEstadoCarga" class="alert alert-info" role="status" aria-live="polite">
          <span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>
          Cargando feriados…
        </div>

        <div id="feriadosErrorCarga" class="alert alert-danger d-none" role="alert">
          <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <span id="feriadosErrorCargaTexto">No se pudo cargar el listado.</span>
            <button type="button" id="feriadosReintentarCarga" class="btn btn-outline-danger btn-sm mt-2 mt-md-0">
              <i class="fas fa-redo mr-1" aria-hidden="true"></i>Reintentar
            </button>
          </div>
        </div>

        <div class="card">
          <div class="card-header feriados-filtros">
            <div class="form-group mb-0">
              <label for="feriadosFiltroEstado">Estado</label>
              <select id="feriadosFiltroEstado" class="form-control form-control-sm">
                <option value="">Todos</option>
                <option value="Habilitado">Habilitados</option>
                <option value="Deshabilitado">Deshabilitados</option>
              </select>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="feriadosTabla" class="table table-bordered table-striped" width="100%">
                <caption class="sr-only">Listado administrativo de feriados</caption>
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <?php include __DIR__ . '/layout/footer_layout.php'; ?>
  <aside class="control-sidebar control-sidebar-dark"></aside>
</div>

<div class="modal fade" id="feriadosModal" tabindex="-1" role="dialog" aria-labelledby="feriadosModalTitulo" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="feriadosFormulario" novalidate>
        <div class="modal-header bg-navy">
          <h2 class="modal-title h5" id="feriadosModalTitulo">Nuevo feriado</h2>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div id="feriadosFormularioContexto" class="alert alert-light border d-none" role="status"></div>

          <div class="form-group">
            <label for="feriadosFecha">Fecha <span aria-hidden="true">*</span></label>
            <input
              type="date"
              class="form-control"
              id="feriadosFecha"
              name="fecha"
              min="<?php echo htmlspecialchars($mananaFeriados->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
              aria-describedby="feriadosFechaAyuda feriadosFechaError"
              required
            >
            <small id="feriadosFechaAyuda" class="form-text text-muted">Debe ser posterior a hoy.</small>
            <div id="feriadosFechaError" class="invalid-feedback"></div>
          </div>

          <div class="form-group">
            <label for="feriadosDescripcion">Descripción <span aria-hidden="true">*</span></label>
            <input
              type="text"
              class="form-control"
              id="feriadosDescripcion"
              name="descripcion"
              maxlength="255"
              autocomplete="off"
              aria-describedby="feriadosDescripcionContador feriadosDescripcionError"
              required
            >
            <div class="d-flex justify-content-between">
              <small class="form-text text-muted">Máximo 255 caracteres.</small>
              <small id="feriadosDescripcionContador" class="form-text text-muted" aria-live="polite">0/255</small>
            </div>
            <div id="feriadosDescripcionError" class="invalid-feedback"></div>
          </div>

          <p class="text-muted mb-0"><small>Los campos marcados con * son obligatorios.</small></p>
        </div>
        <div class="modal-footer">
          <button type="button" id="feriadosCancelar" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" id="feriadosGuardar" class="btn btn-success">
            <span id="feriadosGuardarSpinner" class="spinner-border spinner-border-sm mr-1 d-none" role="status" aria-hidden="true"></span>
            <i id="feriadosGuardarIcono" class="fas fa-save mr-1" aria-hidden="true"></i>
            <span id="feriadosGuardarTexto">Guardar</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div
  id="feriadosConfiguracion"
  data-endpoint="../03-controller/feriadosController.php"
  data-hoy="<?php echo htmlspecialchars($hoyFeriados->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
  data-manana="<?php echo htmlspecialchars($mananaFeriados->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
  hidden
></div>

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
<script src="../07-funciones_js/feriadosAcciones.js"></script>
</body>
</html>
