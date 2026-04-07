<?php
// ../01-views/modals/modal_documentos_emitidos_presupuesto.php
?>
<div class="modal fade" id="modalDocumentosEmitidosPresupuesto" tabindex="-1" role="dialog" aria-labelledby="modalDocumentosEmitidosPresupuestoLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">

  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">

      <div class="modal-header py-3">

        <div>
          <h3 class="modal-title font-weight-bold mb-1" id="modalDocumentosEmitidosPresupuestoLabel">
            Documentos emitidos
          </h3>

          <div id="modalDocumentosEmitidosContexto" class="text-dark font-weight-bold" style="font-size: 1.1rem;">
            <!-- Contexto: ID | Razón social -->
          </div>
        </div>

        <button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>

      </div>

      <div class="modal-body" id="modalDocumentosEmitidosPresupuestoBody">
        <div class="text-muted">Cargando documentos...</div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>
