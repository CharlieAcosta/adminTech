<?php
// ../01-views/modals/modal_dashboard_seguimiento.php
?>
<!-- Modal: Panel de analitica -->
<div class="modal fade" id="modalDashboardSeguimiento" tabindex="-1" role="dialog" aria-labelledby="modalDashboardSeguimientoLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">

  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">

      <div class="modal-header py-3">

          <div>
              <h3 class="modal-title font-weight-bold mb-1" id="modalDashboardSeguimientoLabel">
                  <i class="fas fa-chart-line mr-2"></i>Panel de anal&iacute;tica
              </h3>

              <div id="modalDashboardSeguimientoContexto" class="text-dark font-weight-bold">
                  ID: <span data-dashboard-field="id_previsita">-</span>
              </div>
          </div>

          <button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
          </button>

      </div>

      <div class="modal-body" id="modalDashboardSeguimientoBody">
        <!--
          PANEL DE ANALITICA - V1 DE INTEGRACION REAL
          DATO REAL: se completa por AJAX (obtenerDashboardSeguimiento).
          DATO DEMO: Estado actual, KPI Materiales solicitados y Duracion del circuito.
        -->
        <div id="dashboardSeguimientoEstadoCarga" class="text-muted small mb-2 d-none"></div>
        <div class="card card-outline card-secondary mb-3">
          <div class="card-body py-2">
            <div class="row">
              <div class="col-12 col-md-3 mb-2 mb-md-0">
                <span class="text-muted d-block small text-uppercase">Cliente <span class="badge badge-success ml-1">DATO REAL</span></span>
                <span class="font-weight-bold" data-dashboard-field="cliente">-</span>
              </div>
              <div class="col-12 col-md-3 mb-2 mb-md-0">
                <span class="text-muted d-block small text-uppercase">CUIT <span class="badge badge-success ml-1">DATO REAL</span></span>
                <span class="font-weight-bold" data-dashboard-field="cuit">-</span>
              </div>
              <div class="col-12 col-md-3 mb-2 mb-md-0">
                <span class="text-muted d-block small text-uppercase">Presupuesto <span class="badge badge-success ml-1">DATO REAL</span></span>
                <span class="font-weight-bold" data-dashboard-field="presupuesto">-</span>
              </div>
              <div class="col-12 col-md-3">
                <span class="text-muted d-block small text-uppercase">Estado actual <span class="badge badge-warning ml-1">DATO DEMO</span></span>
                <span class="badge badge-info" data-dashboard-field="estado_actual">En ejecuci&oacute;n</span>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="info-box">
              <span class="info-box-icon bg-info elevation-1"><i class="fas fa-calendar-alt"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Inicio del circuito <span class="badge badge-success ml-1">DATO REAL</span></span>
                <span class="info-box-number" data-dashboard-kpi="inicio">-</span>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <div class="info-box">
              <span class="info-box-icon bg-success elevation-1"><i class="fas fa-clock"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">D&iacute;as transcurridos <span class="badge badge-success ml-1">DATO REAL</span></span>
                <span class="info-box-number" data-dashboard-kpi="dias_transcurridos">-</span>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <div class="info-box">
              <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-dollar-sign"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Monto presupuestado <span class="badge badge-success ml-1">DATO REAL</span></span>
                <span class="info-box-number" data-dashboard-kpi="monto_presupuestado">-</span>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <div class="info-box">
              <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-dolly-flatbed"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Materiales solicitados <span class="badge badge-warning ml-1">DATO DEMO</span></span>
                <span class="info-box-number" data-dashboard-kpi="materiales_solicitados">82%</span>
              </div>
            </div>
          </div>
        </div>

        <div class="card card-outline card-primary mb-3 collapsed-card dashboard-seguimiento-card">
          <div class="card-header py-2">
            <h5 class="card-title mb-0">
              <i class="fas fa-tasks mr-2"></i>Duraci&oacute;n del circuito <span class="badge badge-warning ml-1">DATO DEMO</span>
            </h5>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Expandir o contraer">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
          <div class="card-body p-2">
            <div class="table-responsive">
              <table class="table table-bordered table-sm table-hover mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>Etapa</th>
                    <th class="text-center">Inicio</th>
                    <th class="text-center">Fin</th>
                    <th class="text-center">D&iacute;as</th>
                    <th class="text-center">Estado</th>
                  </tr>
                </thead>
                <tbody id="dashboardSeguimientoEtapasBody">
                  <tr>
                    <td>Previsita / solicitud</td>
                    <td class="text-center">12/06/2026</td>
                    <td class="text-center">14/06/2026</td>
                    <td class="text-center">2</td>
                    <td class="text-center"><span class="badge badge-success">Finalizada</span></td>
                  </tr>
                  <tr>
                    <td>Visita</td>
                    <td class="text-center">15/06/2026</td>
                    <td class="text-center">18/06/2026</td>
                    <td class="text-center">3</td>
                    <td class="text-center"><span class="badge badge-success">Finalizada</span></td>
                  </tr>
                  <tr>
                    <td>Presupuesto</td>
                    <td class="text-center">19/06/2026</td>
                    <td class="text-center">25/06/2026</td>
                    <td class="text-center">6</td>
                    <td class="text-center"><span class="badge badge-success">Finalizada</span></td>
                  </tr>
                  <tr>
                    <td>Circuito comercial</td>
                    <td class="text-center">26/06/2026</td>
                    <td class="text-center">04/07/2026</td>
                    <td class="text-center">8</td>
                    <td class="text-center"><span class="badge badge-success">Finalizada</span></td>
                  </tr>
                  <tr>
                    <td>Orden de compra</td>
                    <td class="text-center">05/07/2026</td>
                    <td class="text-center">09/07/2026</td>
                    <td class="text-center">4</td>
                    <td class="text-center"><span class="badge badge-success">Finalizada</span></td>
                  </tr>
                  <tr>
                    <td>Ejecuci&oacute;n / materiales</td>
                    <td class="text-center">10/07/2026</td>
                    <td class="text-center">-</td>
                    <td class="text-center">47</td>
                    <td class="text-center"><span class="badge badge-info">En curso</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card card-outline card-info mb-3 collapsed-card dashboard-seguimiento-card">
          <div class="card-header py-2">
            <h5 class="card-title mb-0">
              <i class="fas fa-dolly-flatbed mr-2"></i>Materiales <span class="badge badge-success ml-1">DATO REAL</span>
            </h5>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Expandir o contraer">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
          <div class="card-body p-2">
            <div class="table-responsive">
              <table class="table table-bordered table-sm table-hover mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>Material</th>
                    <th class="text-center">Presupuestado</th>
                    <th class="text-center">Solicitado</th>
                    <th class="text-center">Diferencia</th>
                    <th class="text-center">%</th>
                    <th class="text-center">Estado</th>
                  </tr>
                </thead>
                <tbody id="dashboardSeguimientoMaterialesBody">
                  <tr><td colspan="6" class="text-center text-muted">Cargando datos...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card card-outline card-info mb-3 collapsed-card dashboard-seguimiento-card">
          <div class="card-header py-2">
            <h5 class="card-title mb-0">
              <i class="fas fa-users mr-2"></i>Jornales <span class="badge badge-success ml-1">DATO REAL</span>
            </h5>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Expandir o contraer">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
          <div class="card-body p-2">
            <div class="table-responsive">
              <table class="table table-bordered table-sm table-hover mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>Jornal</th>
                    <th class="text-center">Personas</th>
                    <th class="text-center">D&iacute;as</th>
                    <th class="text-center">Jornales presupuestados</th>
                  </tr>
                </thead>
                <tbody id="dashboardSeguimientoJornalesBody">
                  <tr><td colspan="4" class="text-center text-muted">Cargando datos...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card card-outline card-warning mb-0 collapsed-card dashboard-seguimiento-card">
          <div class="card-header py-2">
            <h5 class="card-title mb-0">
              <i class="fas fa-clipboard-check mr-2"></i>Adicionales / autorizaciones <span class="badge badge-success ml-1">DATO REAL</span>
            </h5>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Expandir o contraer">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
          <div class="card-body py-2">
            <div id="dashboardSeguimientoAutorizacionesBody" class="d-flex flex-wrap align-items-center">
              <span class="text-muted">Cargando datos...</span>
            </div>
          </div>
        </div>
        <!-- FIN PANEL DE ANALITICA -->
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>
