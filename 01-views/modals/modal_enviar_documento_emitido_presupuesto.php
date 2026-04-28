<?php
// ../01-views/modals/modal_enviar_documento_emitido_presupuesto.php
?>
<div class="modal fade" id="modalEnviarDocumentoEmitidoPresupuesto" tabindex="-1" role="dialog" aria-labelledby="modalEnviarDocumentoEmitidoPresupuestoLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <div>
          <h3 class="modal-title font-weight-bold mb-1" id="modalEnviarDocumentoEmitidoPresupuestoLabel">
            Enviar documento por mail
          </h3>
          <div id="modalEnviarDocumentoEmitidoContexto" class="text-dark font-weight-bold" style="font-size: 1rem;">
            <!-- Documento seleccionado -->
          </div>
        </div>

        <button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form id="formEnviarDocumentoEmitidoPresupuesto">
        <input type="hidden" name="id_documento_emitido" id="mail_id_documento_emitido" value="">
        <input type="hidden" name="id_presupuesto" id="mail_id_presupuesto" value="">
        <input type="hidden" name="id_previsita" id="mail_id_previsita" value="">

        <div class="modal-body py-3">
          <div id="modalEnviarDocumentoEmitidoAlertas"></div>

          <div class="form-group mb-2">
            <label class="mb-1" for="mail_documento_para">Para</label>
            <input type="text" class="form-control form-control-sm" id="mail_documento_para" name="para_email" placeholder="correo@cliente.com">
            <small class="form-text text-muted">Podés ingresar uno o más correos separados por coma.</small>
          </div>

          <div class="form-group mb-2">
            <label class="mb-1" for="mail_documento_sugerencias">Correos sugeridos</label>
            <select class="form-control form-control-sm" id="mail_documento_sugerencias">
              <option value="">Seleccionar sugerencia</option>
            </select>
          </div>

          <div class="form-group mb-2">
            <label class="mb-1">Copias internas por defecto (CC / CCO)</label>
            <div id="mail_documento_copias" class="border rounded p-2 bg-light small">
              <div class="text-muted">Cargando copias configuradas...</div>
            </div>
          </div>

          <div class="form-group mb-2">
            <label class="mb-1" for="mail_documento_cco_manual">Otra copia oculta</label>
            <input type="text" class="form-control form-control-sm" id="mail_documento_cco_manual" name="cco_manual" placeholder="otro@dominio.com">
            <small class="form-text text-muted">Si querés agregar más destinatarios internos, separalos por coma.</small>
          </div>

          <div class="form-group mb-2">
            <label class="mb-1" for="mail_documento_asunto">Asunto</label>
            <input type="text" class="form-control form-control-sm" id="mail_documento_asunto" name="asunto">
          </div>

          <div class="form-group mb-0">
            <label class="mb-1" for="mail_documento_cuerpo">Mensaje</label>
            <textarea class="form-control form-control-sm" id="mail_documento_cuerpo" name="cuerpo" rows="5" style="line-height:1.35; resize:vertical;"></textarea>
          </div>
        </div>

        <div class="modal-footer py-2 d-flex justify-content-between">
          <div id="mail_documento_modo" class="text-muted small"></div>
          <div class="d-flex align-items-center" style="gap: 10px;">
            <button type="button" class="btn btn-secondary d-inline-flex align-items-center justify-content-center" style="flex: 0 0 190px; height: 38px; white-space: nowrap;" data-dismiss="modal">
              <i class="fas fa-times fa-fw mr-1"></i> Cancelar
            </button>
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center justify-content-center" style="flex: 0 0 190px; height: 38px; white-space: nowrap;" id="btnEnviarDocumentoEmitido">
              <i class="fas fa-paper-plane fa-fw mr-1"></i> Enviar documento
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
