<?php
// ../01-views/modals/modal_enviar_documento_emitido_presupuesto.php
?>
<style>
  #modalEnviarDocumentoEmitidoPresupuesto .modal-dialog {
    max-height: calc(100vh - 1rem);
  }

  #modalEnviarDocumentoEmitidoPresupuesto .modal-content {
    max-height: calc(100vh - 1rem);
    overflow: hidden;
  }

  #modalEnviarDocumentoEmitidoPresupuesto #formEnviarDocumentoEmitidoPresupuesto {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    min-height: 0;
  }

  #modalEnviarDocumentoEmitidoPresupuesto .modal-header,
  #modalEnviarDocumentoEmitidoPresupuesto .modal-footer {
    flex: 0 0 auto;
  }

  #modalEnviarDocumentoEmitidoPresupuesto .modal-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-x: hidden;
    overflow-y: auto;
  }

  @media (min-width: 576px) {
    #modalEnviarDocumentoEmitidoPresupuesto .modal-dialog,
    #modalEnviarDocumentoEmitidoPresupuesto .modal-content {
      max-height: calc(100vh - 3.5rem);
    }
  }

  .documento-emitido-adjuntos-dropzone {
    border: 2px dashed #c7d2dc !important;
    cursor: pointer;
    transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
  }

  .documento-emitido-adjuntos-dropzone-copy {
    font-size: 0.92rem;
    line-height: 1.25;
  }

  .documento-emitido-adjuntos-dropzone-copy i {
    font-size: 1.1rem;
  }

  .documento-emitido-adjuntos-dropzone:focus-within {
    border-color: #2f6fad !important;
    box-shadow: 0 0 0 0.15rem rgba(47, 111, 173, 0.18);
    color: #2f6fad !important;
  }
</style>
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

          <div class="form-group mb-0 mt-3">
            <div class="mb-1 font-weight-bold">Adjuntos adicionales</div>
            <label
              class="documento-emitido-adjuntos-dropzone border rounded bg-light p-3 text-muted mb-2 d-block"
              id="documentoEmitidoAdjuntosDropzone"
            >
              <input
                type="file"
                class="sr-only"
                id="documentoEmitidoAdjuntosInput"
                name="adjuntos_adicionales[]"
                multiple
                accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.jpg,.jpeg,.png"
              >
              <span class="documento-emitido-adjuntos-dropzone-copy text-center d-block">
                <i class="fas fa-file-upload mb-2" aria-hidden="true"></i>
                <span class="d-block"><strong>Arrastrá los archivos aquí</strong> o hacé clic para seleccionarlos</span>
                <small class="d-block mt-1">Formatos permitidos: PDF, Word, Excel, TXT, JPG y PNG. Máximo 5 MB por archivo.</small>
              </span>
            </label>
            <small class="form-text text-muted mb-2">Los archivos se utilizarán únicamente en este envío y no quedarán almacenados.</small>
            <div class="row documento-emitido-adjuntos-lista" id="documentoEmitidoAdjuntosLista"></div>
            <div class="text-danger small d-none mt-1" id="documentoEmitidoAdjuntosError" role="alert"></div>
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
