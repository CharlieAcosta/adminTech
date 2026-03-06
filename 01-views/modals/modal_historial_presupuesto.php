<?php
// ../01-views/modals/modal_historial_presupuesto.php
?>
<!-- Modal: Historial / Seguimiento de presupuesto -->
<div class="modal fade" id="modalHistorialPresupuesto" tabindex="-1" role="dialog" aria-labelledby="modalHistorialPresupuestoLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">

  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">

      <div class="modal-header py-3">

          <div>
              <h3 class="modal-title font-weight-bold mb-1" id="modalHistorialPresupuestoLabel">
                  Historial de presupuesto
              </h3>

              <div id="modalHistorialContexto" class="text-dark font-weight-bold" style="font-size: 1.1rem;">
                  <!-- Acá vamos a inyectar: ID — Razón Social -->
              </div>
          </div>

          <button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
          </button>

      </div>

      <div class="modal-body" id="modalHistorialPresupuestoBody">
        <div class="text-muted">Cargando historial...</div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>