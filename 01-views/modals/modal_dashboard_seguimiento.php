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
          DATOS DE DEMOSTRACIÓN - PANEL DE ANALÍTICA
          Eliminar/reemplazar cuando se implemente backend real.
        -->
        <div class="card card-outline card-secondary mb-3">
          <div class="card-body py-2">
            <div class="row">
              <div class="col-12 col-md-3 mb-2 mb-md-0">
                <span class="text-muted d-block small text-uppercase">Cliente</span>
                <span class="font-weight-bold" data-dashboard-field="cliente">Industrias del Plata S.A.</span>
              </div>
              <div class="col-12 col-md-3 mb-2 mb-md-0">
                <span class="text-muted d-block small text-uppercase">CUIT</span>
                <span class="font-weight-bold" data-dashboard-field="cuit">30-71234567-8</span>
              </div>
              <div class="col-12 col-md-3 mb-2 mb-md-0">
                <span class="text-muted d-block small text-uppercase">Presupuesto</span>
                <span class="font-weight-bold" data-dashboard-field="presupuesto">P-2026-0148</span>
              </div>
              <div class="col-12 col-md-3">
                <span class="text-muted d-block small text-uppercase">Estado actual</span>
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
                <span class="info-box-text">Inicio</span>
                <span class="info-box-number" data-dashboard-kpi="inicio">12/06/2026</span>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <div class="info-box">
              <span class="info-box-icon bg-success elevation-1"><i class="fas fa-clock"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">D&iacute;as transcurridos</span>
                <span class="info-box-number" data-dashboard-kpi="dias_transcurridos">76 d&iacute;as</span>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <div class="info-box">
              <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-dollar-sign"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Monto presupuestado</span>
                <span class="info-box-number" data-dashboard-kpi="monto_presupuestado">$ 18.450.000</span>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <div class="info-box">
              <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-dolly-flatbed"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Materiales solicitados</span>
                <span class="info-box-number" data-dashboard-kpi="materiales_solicitados">82%</span>
              </div>
            </div>
          </div>
        </div>

        <div class="card card-outline card-primary mb-3">
          <div class="card-header py-2">
            <h5 class="card-title mb-0">
              <i class="fas fa-tasks mr-2"></i>Duraci&oacute;n del circuito
            </h5>
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

        <div class="card card-outline card-info mb-3">
          <div class="card-header py-2">
            <h5 class="card-title mb-0">
              <i class="fas fa-dolly-flatbed mr-2"></i>Materiales
            </h5>
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
                  <tr>
                    <td>Chapa galvanizada</td>
                    <td class="text-right">1.200 m&sup2;</td>
                    <td class="text-right">980 m&sup2;</td>
                    <td class="text-right">220 m&sup2;</td>
                    <td class="text-center">82%</td>
                    <td class="text-center"><span class="badge badge-success">Dentro de presupuesto</span></td>
                  </tr>
                  <tr>
                    <td>Membrana aislante</td>
                    <td class="text-right">850 m&sup2;</td>
                    <td class="text-right">720 m&sup2;</td>
                    <td class="text-right">130 m&sup2;</td>
                    <td class="text-center">85%</td>
                    <td class="text-center"><span class="badge badge-success">Dentro de presupuesto</span></td>
                  </tr>
                  <tr>
                    <td>Tornillos autoperforantes</td>
                    <td class="text-right">4.500 un.</td>
                    <td class="text-right">4.200 un.</td>
                    <td class="text-right">300 un.</td>
                    <td class="text-center">93%</td>
                    <td class="text-center"><span class="badge badge-warning">Pr&oacute;ximo al l&iacute;mite</span></td>
                  </tr>
                  <tr>
                    <td>Perfil galvanizado</td>
                    <td class="text-right">600 ml</td>
                    <td class="text-right">630 ml</td>
                    <td class="text-right">-30 ml</td>
                    <td class="text-center">105%</td>
                    <td class="text-center"><span class="badge badge-danger">Excedido</span></td>
                  </tr>
                  <tr>
                    <td>Sellador poliuret&aacute;nico</td>
                    <td class="text-right">0</td>
                    <td class="text-right">35 un.</td>
                    <td class="text-right">-</td>
                    <td class="text-center">N/A</td>
                    <td class="text-center"><span class="badge badge-secondary">Adicional</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card card-outline card-info mb-3">
          <div class="card-header py-2">
            <h5 class="card-title mb-0">
              <i class="fas fa-users mr-2"></i>Jornales
            </h5>
          </div>
          <div class="card-body p-2">
            <div class="table-responsive">
              <table class="table table-bordered table-sm table-hover mb-0">
                <thead class="thead-light">
                  <tr>
                    <th>Jornal</th>
                    <th class="text-center">Presupuestado</th>
                    <th class="text-center">Utilizado</th>
                    <th class="text-center">Diferencia</th>
                    <th class="text-center">%</th>
                    <th class="text-center">Estado</th>
                  </tr>
                </thead>
                <tbody id="dashboardSeguimientoJornalesBody">
                  <tr>
                    <td>Oficial techista</td>
                    <td class="text-right">320 hs</td>
                    <td class="text-right">245 hs</td>
                    <td class="text-right">75 hs</td>
                    <td class="text-center">77%</td>
                    <td class="text-center"><span class="badge badge-success">Dentro de presupuesto</span></td>
                  </tr>
                  <tr>
                    <td>Medio oficial</td>
                    <td class="text-right">240 hs</td>
                    <td class="text-right">210 hs</td>
                    <td class="text-right">30 hs</td>
                    <td class="text-center">88%</td>
                    <td class="text-center"><span class="badge badge-success">Dentro de presupuesto</span></td>
                  </tr>
                  <tr>
                    <td>Ayudante</td>
                    <td class="text-right">360 hs</td>
                    <td class="text-right">345 hs</td>
                    <td class="text-right">15 hs</td>
                    <td class="text-center">96%</td>
                    <td class="text-center"><span class="badge badge-warning">Pr&oacute;ximo al l&iacute;mite</span></td>
                  </tr>
                  <tr>
                    <td>Supervisor</td>
                    <td class="text-right">80 hs</td>
                    <td class="text-right">86 hs</td>
                    <td class="text-right">-6 hs</td>
                    <td class="text-center">108%</td>
                    <td class="text-center"><span class="badge badge-danger">Excedido</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card card-outline card-warning mb-0">
          <div class="card-header py-2">
            <h5 class="card-title mb-0">
              <i class="fas fa-clipboard-check mr-2"></i>Adicionales / autorizaciones
            </h5>
          </div>
          <div class="card-body py-2">
            <div id="dashboardSeguimientoAutorizacionesBody" class="d-flex flex-wrap align-items-center">
              <span class="mr-4 my-1"><span class="badge badge-warning mr-1">2</span> solicitudes pendientes</span>
              <span class="mr-4 my-1"><span class="badge badge-success mr-1">1</span> material adicional autorizado</span>
              <span class="my-1"><span class="badge badge-danger mr-1">1</span> solicitud rechazada</span>
            </div>
          </div>
        </div>
        <!-- FIN DATOS DE DEMOSTRACION - PANEL DE ANALITICA -->
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>
