(function ($) {
  'use strict';

  if (window.__presuHandlersBound) return;
  window.__presuHandlersBound = true;

  // buffers globales
  window.fotosNuevasPorTarea     = window.fotosNuevasPorTarea     || {};
  window.fotosEliminadasPorTarea = window.fotosEliminadasPorTarea || {};

  // limpieza por namespace
  $(document)
    .off('click.presu',  '.presu-dropzone')
    .off('change.presu', '.presu-fotos')
    .off('click.presu',  '.presu-eliminar-imagen')
    .off('click.presu', '#btn-guardar-presupuesto')
    .on('click.presu', '#btn-guardar-presupuesto', function (e) {
      e.preventDefault();
      e.stopPropagation();
      window.presupuestoGuardar(); // usa el data-id_presupuesto si existe
    });

    // === Recalculo en vivo para presupuesto cargado del backend ===
    (function bindRecalcForBackend() {
      const rootSel = '#contenedorPresupuestoGenerado';

      // 1) Fila Materiales / Mano de Obra (cantidad, precio/jornal, % extra)
      const filaInputs =
        [
          // Materiales
          `${rootSel} .cantidad-material`,
          `${rootSel} .precio-unitario`,
          `${rootSel} .tarea-materiales .porcentaje-extra`,
          // Mano de obra
          `${rootSel} .cantidad-mano-obra`,
          `${rootSel} .dias-mano-obra`,
          `${rootSel} .valor-jornal`,
          `${rootSel} .tarea-mano-obra .porcentaje-extra`,
        ].join(',');

      $(document)
        .off('input.presu change.presu', filaInputs)
        .on('input.presu change.presu', filaInputs, function () {
          const $tr   = $(this).closest('tr');
          const $card = $(this).closest('.tarea-card');

          // Recalcular la fila que cambió
          if ($tr.find('.cantidad-material').length && typeof window.calcularFilaMaterial === 'function') {
            window.calcularFilaMaterial($tr);
          }
          if ($tr.find('.cantidad-mano-obra').length && typeof window.calcularFilaManoObra === 'function') {
            window.calcularFilaManoObra($tr);
          }

          // Subtotales del bloque y total de tarea
          if (typeof window.actualizarSubtotalesBloque === 'function') {
            window.actualizarSubtotalesBloque($card);
          }
          if (typeof window.actualizarTotalesPorTarea === 'function') {
            // el wrapper ya resuelve firma (numeroTarea, $card) o ($card)
            _safeActualizarTotalesPorTarea($card, this);
          }

          // Total general
          if (typeof window.actualizarTotalGeneral === 'function') {
            window.actualizarTotalGeneral();
          }
        });

      // 2) “Otros” y utilidades globales por bloque
      const bloqueInputs =
        [
          `${rootSel} .input-otros-materiales`,
          `${rootSel} .input-otros-mano`,
          `${rootSel} .utilidad-global-materiales`,
          `${rootSel} .utilidad-global-mano-obra`,
        ].join(',');

      $(document)
        .off('input.presu change.presu', bloqueInputs)
        .on('input.presu change.presu', bloqueInputs, function () {
          const $card = $(this).closest('.tarea-card');

          if (typeof window.actualizarSubtotalesBloque === 'function') {
            window.actualizarSubtotalesBloque($card);
          }
          if (typeof window.actualizarTotalesPorTarea === 'function') {
            _safeActualizarTotalesPorTarea($card, this);
          }
          if (typeof window.actualizarTotalGeneral === 'function') {
            window.actualizarTotalGeneral();
          }
        });

      // 3) Incluir en total (checkbox de la cabecera de tarea)
      $(document)
        .off('change.presu', `${rootSel} .incluir-en-total`)
        .on('change.presu', `${rootSel} .incluir-en-total`, function () {
          const $card = $(this).closest('.tarea-card');

          if (typeof window.actualizarTotalesPorTarea === 'function') {
            _safeActualizarTotalesPorTarea($card, this);
          }
          if (typeof window.actualizarTotalGeneral === 'function') {
            window.actualizarTotalGeneral();
          }
        });
    })();


  // 1) dropzone -> input
  $(document).on('click.presu', '.presu-dropzone', function (e) {
    if ($(e.target).closest('.presu-eliminar-imagen').length) return;
    e.preventDefault(); e.stopPropagation();
    const idx = $(this).data('index');
    const $inp = $('#presu_fotos_tarea_' + idx);
    if ($inp.length) $inp.trigger('click');
  });

  // 2) input -> previews
  $(document).on('change.presu', '.presu-fotos', function (e) {
    e.stopPropagation();
    const idx = $(this).data('index');
    const files = Array.from(this.files || []);
    if (!idx || !files.length) return;
    const $preview = $('#presu_preview_' + idx);
    if (!$preview.length) return;

    if (!window.fotosNuevasPorTarea[idx]) window.fotosNuevasPorTarea[idx] = [];
    files.forEach((file, i) => {
      const tempId = 'tmp_' + Date.now() + '_' + i;
      window.fotosNuevasPorTarea[idx].push({ tempId, file });
      const url = URL.createObjectURL(file);
      $preview.append(
        `<div class="preview-img-container position-relative d-inline-block m-1" data-temp-id="${tempId}">
           <img src="${url}" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;cursor:pointer;">
           <i class="fa fa-times-circle text-white rounded-circle position-absolute presu-eliminar-imagen"
              style="top:0;right:0;cursor:pointer;font-size:1rem;"></i>
         </div>`
      );
    });
    this.value = '';
  });

  // 3) eliminar
  $(document).on('click.presu', '.presu-eliminar-imagen', function (e) {
    e.preventDefault(); e.stopPropagation();
    const $wrap = $(this).closest('.preview-img-container');
    const idx   = $wrap.closest('.presu-dropzone').data('index');
    const nombre = $wrap.data('nombre-archivo');     // existente
    if (nombre) {
      if (!window.fotosEliminadasPorTarea[idx]) window.fotosEliminadasPorTarea[idx] = [];
      window.fotosEliminadasPorTarea[idx].push(nombre);
      $wrap.remove();
      return;
    }
    const tempId = $wrap.data('temp-id');            // nueva
    if (tempId && window.fotosNuevasPorTarea[idx]) {
      window.fotosNuevasPorTarea[idx] = window.fotosNuevasPorTarea[idx].filter(x => x.tempId !== tempId);
      $wrap.remove();
    }
  });

// === Guardar tarea (VISTA) — SweetAlert con input, único para backend + visita ===
// === Helper: serializa UNA sola tarea-card (mismos selectores que presupuestoGuardar) ===
window._presuSerializarCard = function ($card, nro) {
  // Descripción (prioriza textarea; si no, el título)
  const descTextarea = $card.find('textarea').first().val();
  const descTitulo   = ($card.find('.tarea-encabezado b').text() || '').replace(/^Tarea\s+\d+:\s*/i, '');
  const descripcion  = (descTextarea != null ? String(descTextarea) : String(descTitulo || '')).trim();

  const incluir_en_total = $card.find('.incluir-en-total').prop('checked') ? 1 : 0;

  // --- Materiales (idéntico a guardado unificado)
  const materiales = [];
  $card.find('.tarea-materiales tbody tr').each(function () {
    const $tr = $(this);
    if ($tr.hasClass('fila-otros-materiales') || $tr.hasClass('fila-subtotal')) return;

    const id_material = $tr.data('material-id');
    if (!id_material) return;

    const nombre           = ($tr.find('td').eq(0).text() || '').trim();
    const cantidad         = parseFloat($tr.find('.cantidad-material').val()) || 0;
    const precio_unitario  = parseFloat($tr.find('.precio-unitario').val()) || 0;
    const porcentaje_extra = parseFloat($tr.find('.porcentaje-extra').val()) || 0;

    materiales.push({
      id_material: String(id_material),
      nombre,
      cantidad,
      precio_unitario,
      porcentaje_extra
    });
  });

  // Otros + utilidades del bloque Materiales
  const otros_materiales = parseFloat($card.find('.input-otros-materiales').val()) || 0;
  const util_mat_txt     = $card.find('.utilidad-global-materiales').val();
  const utilidad_materiales = util_mat_txt === '' ? null : (parseFloat(util_mat_txt) || 0);

  // --- Mano de Obra (idéntico a guardado unificado)
  const mano_obra = [];
  $card.find('.tarea-mano-obra tbody tr').each(function () {
    const $tr = $(this);
    if ($tr.hasClass('fila-otros-mano') || $tr.hasClass('fila-subtotal')) return;

    const jornal_id = $tr.data('jornal_id');
    if (!jornal_id) return;

    const nombre           = ($tr.find('td').eq(0).text() || '').trim();
    const cantidad         = parseFloat($tr.find('.cantidad-mano-obra').val()) || 0;
    const jornal_valor     = parseFloat($tr.find('.valor-jornal').val()) || 0;
    const porcentaje_extra = parseFloat($tr.find('.porcentaje-extra').val()) || 0;

    mano_obra.push({
      jornal_id: String(jornal_id),
      nombre,
      cantidad,
      jornal_valor,
      porcentaje_extra
    });
  });

  // Otros + utilidades del bloque Mano de Obra
  const otros_mano_obra = parseFloat($card.find('.input-otros-mano').val()) || 0;
  const util_mo_txt     = $card.find('.utilidad-global-mano-obra').val();
  const utilidad_mano_obra = util_mo_txt === '' ? null : (parseFloat(util_mo_txt) || 0);

  // Nota: fotos quedan fuera para tareas archivadas
  return {
    nro,
    descripcion,
    incluir_en_total,
    utilidad_materiales,
    utilidad_mano_obra,
    otros_materiales,
    otros_mano_obra,
    materiales,
    mano_obra
  };
};

// === Guardar tarea (VISTA) — SweetAlert + serialización de la card (único handler) ===
$(document)
  .off('click.presu-guardar-tarea', '#contenedorPresupuestoGenerado .btn-guardar-tarea')
  .on('click.presu-guardar-tarea', '#contenedorPresupuestoGenerado .btn-guardar-tarea', function (e) {
    e.preventDefault(); e.stopPropagation();
    const $btn  = $(this);
    const nro   = $btn.data('nro');
    const $card = $btn.closest('.tarea-card');

    const lanzarPrompt = (onOk) => {
      // nombre por defecto: textarea > título
      const descTextarea = $card.find('textarea').first().val();
      const descTitulo   = ($card.find('.tarea-encabezado b').text() || '').replace(/^Tarea\s+\d+:\s*/i, '');
      const defaultName  = (descTextarea != null ? String(descTextarea) : String(descTitulo || '')).trim();
    
      if (window.Swal && typeof Swal.fire === 'function') {
        Swal.fire({
          title: '¿Con que nombre vas a guardar este tarea?',
          input: 'text',
          inputValue: defaultName,
          showCancelButton: true,
          confirmButtonText: 'Aceptar',
          cancelButtonText: 'Cancelar',
          allowOutsideClick: false, // no se cierra clickeando afuera
          allowEscapeKey: false     // no se cierra con ESC
        }).then((result) => {
          if (!result.isConfirmed) return;
          onOk(String(result.value || '').trim());
        });
      } else {
        const val = window.prompt('¿Con que nombre vas a guardar este tarea?', defaultName);
        if (val != null) onOk(String(val).trim());
      }
    };

    lanzarPrompt(function (nombre_plantilla) {
      const id_presupuesto  = Number($('#contenedorPresupuestoGenerado').data('id_presupuesto')) || null;
      const source_tarea_id = $card.data('id_presu_tarea') || null;
      const tarea = window._presuSerializarCard($card, nro);

      // Preview en memoria
      window.__tareaArchivadaPreview = {
        nombre_plantilla,
        source_id_presupuesto: id_presupuesto,
        source_id_presu_tarea: source_tarea_id,
        tarea
      };
      console.log('Tarea archivable (preview):', window.__tareaArchivadaPreview);
      // === AJAX: enviar al controlador unificado ===
      $.ajax({
        url: '../03-controller/presupuestos_guardar.php',
        method: 'POST',
        dataType: 'json',
        data: {
          via: 'ajax',
          funcion: 'guardar_tarea_archivada',
          data_json: JSON.stringify(window.__tareaArchivadaPreview)
        }
      })
      .done(function (resp) {
        // Minimal feedback (no tocamos tus alertas existentes)
        if (resp && resp.ok) {
          if (window.mostrarExito) {
            mostrarExito('La tarea ha sido archivada');
          }
        } else {
          console.error('Respuesta no OK:'+resp);
          if (window.mostrarError) {
            mostrarError((resp && resp.msg) ? resp.msg : 'Error inesperado.');
          }
        }
      })
      .fail(function (xhr) {
        console.error('Error AJAX (guardar_tarea_archivada):', xhr.responseText || xhr.statusText);
        if (window.mostrarError) {
          mostrarError('No se pudo archivar la tarea, error de red o servidor.');
        }
      });
    });
  });

  // === Unificador de guardado para presupuestos cargados del BACKEND o generados en la visita ===
(function () {
  // Usa el mismo endpoint que ya usa tu flujo "OK"
  const ENDPOINT = '../03-controller/presupuestos_guardar.php';

  // Reemplazo global: ignora la versión vieja de presupuestoGuardar.js
  window.presupuestoGuardar = async function (idPresuOpcional) {
    try {
      const $btn = $('#btn-guardar-presupuesto');
      $btn.prop('disabled', true);
  
      const $root = $('#contenedorPresupuestoGenerado');
  
      // IDs
      const id_presupuesto = idPresuOpcional || Number($root.data('id_presupuesto')) || null;
      const id_previsita   = $('#id_previsita').val() || null;
      const id_visita      = $('#id_visita').val() || null;
  
      const tareas = [];
      $root.find('.tarea-card').each(function (index) {
        const $card = $(this);
        const nro = index + 1;
  
        // descripción igual que tu versión “buena” (textarea o título)
        const descTextarea = $card.find('textarea').first().val();
        const descTitulo   = ($card.find('.tarea-encabezado b').text() || '').replace(/^Tarea\s+\d+:\s*/i, '');
        const descripcion  = (descTextarea != null ? String(descTextarea) : String(descTitulo || '')).trim();
  
        const incluir_en_total = $card.find('.incluir-en-total').is(':checked') ? 1 : 0;
  
        // Utilidades globales + Otros
        const util_mat_txt = $card.find('.utilidad-global-materiales').val();
        const utilidad_materiales = util_mat_txt === '' ? null : (parseFloat(util_mat_txt) || 0);
  
        const util_mo_txt = $card.find('.utilidad-global-mano-obra').val();
        const utilidad_mano_obra = util_mo_txt === '' ? null : (parseFloat(util_mo_txt) || 0);
  
        const otros_materiales = parseFloat($card.find('.input-otros-materiales').val()) || 0;
        const otros_mano_obra  = parseFloat($card.find('.input-otros-mano').val()) || 0;
  
        // Materiales
        const materiales = [];
        $card.find('.tarea-materiales tbody tr').each(function () {
          const $tr = $(this);
          if ($tr.hasClass('fila-otros-materiales') || $tr.hasClass('fila-subtotal')) return;
  
          const id_material = $tr.data('material-id');
          if (!id_material) return;
  
          const nombre           = ($tr.find('td').eq(0).text() || '').trim();
          const cantidad         = parseFloat($tr.find('.cantidad-material').val()) || 0;
          const precio_unitario  = parseFloat($tr.find('.precio-unitario').val()) || 0;
          const porcentaje_extra = parseFloat($tr.find('.porcentaje-extra').val()) || 0;
  
          materiales.push({
            id_material: String(id_material),
            nombre,
            cantidad,
            precio_unitario,
            porcentaje_extra
          });
        });
  
        // Mano de obra (guardamos como “cantidad” = operarios; si tu backend soporta días/jornales ya lo vemos después)
        const mano_obra = [];
        $card.find('.tarea-mano-obra tbody tr').each(function () {
          const $tr = $(this);
          if ($tr.hasClass('fila-otros-mano') || $tr.hasClass('fila-subtotal')) return;
        
          const jornal_id = $tr.data('jornal_id');
          if (!jornal_id) return;
        
          const nombre           = ($tr.find('td').eq(0).text() || '').trim();
          const cantidad         = parseFloat($tr.find('.cantidad-mano-obra').val()) || 0; // operarios
          const dias             = parseFloat($tr.find('.dias-mano-obra').val()) || 0;
          const jornales         = parseFloat($tr.find('.jornales-mano-obra').val()) || 0;
          const jornal_valor     = parseFloat($tr.find('.valor-jornal').val()) || 0;
          const porcentaje_extra = parseFloat($tr.find('.porcentaje-extra').val()) || 0;
        
          mano_obra.push({
            jornal_id: String(jornal_id),
            nombre,
            cantidad,        // operarios
            dias,
            jornales,
            jornal_valor,
            porcentaje_extra
          });
        });
         
        // Contadores (buffers reales del dropzone actual)
        const nuevas     = (window.fotosNuevasPorTarea && window.fotosNuevasPorTarea[nro]) ? window.fotosNuevasPorTarea[nro] : [];
        const eliminadas = (window.fotosEliminadasPorTarea && window.fotosEliminadasPorTarea[nro]) ? window.fotosEliminadasPorTarea[nro] : [];
  
        tareas.push({
          nro,
          descripcion,
          incluir_en_total,
          utilidad_materiales,
          utilidad_mano_obra,
          otros_materiales,
          otros_mano_obra,
          materiales,
          mano_obra,
          fotos_nuevas_cnt: nuevas.length,
          fotos_eliminadas_cnt: eliminadas.length
        });
      });
  
      // FormData
      const fd = new FormData();
      fd.append('via', 'ajax');
      fd.append('funcion', 'guardarPresupuesto');
      fd.append('payload', JSON.stringify({ id_presupuesto, id_previsita, id_visita, tareas }));
  
      // Adjuntar fotos nuevas
      if (window.fotosNuevasPorTarea) {
        Object.keys(window.fotosNuevasPorTarea).forEach((k) => {
          (window.fotosNuevasPorTarea[k] || []).forEach(item => {
            if (item && item.file instanceof File) {
              fd.append(`fotos_tarea_${k}[]`, item.file);
            }
          });
        });
      }
  
      // Adjuntar fotos eliminadas
      if (window.fotosEliminadasPorTarea) {
        Object.keys(window.fotosEliminadasPorTarea).forEach((k) => {
          (window.fotosEliminadasPorTarea[k] || []).forEach(nombre => {
            fd.append(`fotos_eliminadas_tarea_${k}[]`, nombre);
          });
        });
      }
  
      const resp = await $.ajax({
        url: ENDPOINT,
        method: 'POST',
        data: fd,
        contentType: false,
        processData: false,
        cache: false,
        dataType: 'json'
      });
  
      if (resp?.ok) {
        // reflejar id_presupuesto en el DOM si llega
        const nuevoId = resp.id_presupuesto || (resp.presupuesto && resp.presupuesto.id_presupuesto);
        if (nuevoId) {
          $('#contenedorPresupuestoGenerado').attr('data-id_presupuesto', String(nuevoId));
        }
  
        // feedback (mantengo tu esquema “verde” existente)
        if (typeof mostrarExito === 'function') {
          mostrarExito('Presupuesto guardado correctamente.');
        } else if (window.Swal && typeof Swal.fire === 'function') {
          Swal.fire({ icon: 'success', title: 'ACCIÓN COMPLETADA', text: 'Presupuesto guardado correctamente.' });
        }
  
        // limpiar buffers correctos
        window.fotosNuevasPorTarea     = {};
        window.fotosEliminadasPorTarea = {};
      } else {
        const msg = resp?.msg || 'No se pudo guardar el presupuesto.';
        if (typeof mostrarError === 'function') mostrarError(msg);
        else if (window.Swal && typeof Swal.fire === 'function') Swal.fire({ icon: 'error', title: 'Error', text: msg });
      }
  
    } catch (err) {
      console.error('Error al guardar presupuesto (unificado):', err);
      if (typeof mostrarError === 'function') mostrarError('Error al guardar el presupuesto.');
      else if (window.Swal && typeof Swal.fire === 'function') Swal.fire({ icon: 'error', title: 'Error', text: 'Error al guardar el presupuesto.' });
    } finally {
      $('#btn-guardar-presupuesto').prop('disabled', false);
    }
  };
  

})();


// ==== Adaptadores seguros para convivir con ambas firmas ====
// Si la función espera 1 parámetro ($card), se lo pasamos.
// Si espera 0 parámetros (usa `this` internamente), la invocamos con `call(nodeDelInput)`.

function _safeActualizarSubtotalesBloque($card, nodeCtx) {
  const fn = window.actualizarSubtotalesBloque;
  if (typeof fn !== 'function') return;
  try {
    if (fn.length >= 1) {
      fn($card);                   // firma: fn($card)
    } else {
      fn.call(nodeCtx || null);    // firma: fn() usando this
    }
  } catch (e) {
    console.error('Error en actualizarSubtotalesBloque:', e);
  }
}

function _safeActualizarTotalesPorTarea($card, nodeCtx) {
  const fn = window.actualizarTotalesPorTarea;
  if (typeof fn !== 'function') return;

  try {
    if (fn.length >= 2) {
      // Tu firma: (numeroTarea, $card)
      let numero = NaN;

      if ($card && $card.length) {
        const $btn = $card.find('[id^="subt-tarea-"]').last();
        if ($btn.length) {
          const m = ($btn.attr('id') || '').match(/subt-tarea-(\d+)/);
          if (m) numero = parseInt(m[1], 10);
        }
      }

      if (!numero || isNaN(numero)) {
        // Fallback por posición en el DOM (1-based)
        numero = ($card && $card.length) ? ($card.index() + 1) : 1;
      }

      fn(numero, $card);
    } else if (fn.length === 1) {
      // Firma: ($card)
      fn($card);
    } else {
      // Firma: () usando this interno
      fn.call(nodeCtx || null);
    }
  } catch (e) {
    console.error('Error en actualizarTotalesPorTarea:', e);
  }
}


function _safeActualizarTotalGeneral() {
  const fn = window.actualizarTotalGeneral;
  if (typeof fn !== 'function') return;
  try { fn(); } catch (e) { console.error('Error en actualizarTotalGeneral:', e); }
}


// ==== Recalculo y listeners universales para presupuesto (backend o visita) ====
window.initRecalculoPresupuestoCargado = function () {
  const $root = $('#contenedorPresupuestoGenerado');
  if ($root.length === 0) return;

  // 1) Enlazar (una sola vez) los listeners delegados que disparan los cálculos
  if (!window.__presuCalcBound) {
    window.__presuCalcBound = true;

    // Limpio namespace y vuelvo a enlazar delegados sobre el contenedor
    $(document)
      .off('.presucalc')
      .on(
        'input.presucalc change.presucalc',
        '#contenedorPresupuestoGenerado .cantidad-material, \
         #contenedorPresupuestoGenerado .precio-unitario, \
         #contenedorPresupuestoGenerado .porcentaje-extra, \
         #contenedorPresupuestoGenerado .cantidad-mano-obra, \
         #contenedorPresupuestoGenerado .dias-mano-obra, \
         #contenedorPresupuestoGenerado .valor-jornal, \
         #contenedorPresupuestoGenerado .input-otros-materiales, \
         #contenedorPresupuestoGenerado .input-otros-mano, \
         #contenedorPresupuestoGenerado .utilidad-global-materiales, \
         #contenedorPresupuestoGenerado .utilidad-global-mano-obra',
         function () {
          const $card = $(this).closest('.tarea-card');
      
          // ANTES:
          // window.actualizarSubtotalesBloque($card);
          // window.actualizarTotalesPorTarea($card);
          // window.actualizarTotalGeneral();
      
          // AHORA (compatibles con ambas firmas):
          _safeActualizarSubtotalesBloque($card, this);
          _safeActualizarTotalesPorTarea($card, this);
          _safeActualizarTotalGeneral();
        }
      );
  }

        // 2) Barrido inicial: recalcula TODO lo ya pintado
        $root.find('.tarea-card').each(function () {
          const $card = $(this);

          // Recalcular filas
          $card.find('tbody tr').each(function () {
            const $tr = $(this);
            if ($tr.find('.cantidad-material').length && typeof window.calcularFilaMaterial === 'function') {
              window.calcularFilaMaterial($tr);
            }
            if ($tr.find('.cantidad-mano-obra').length && typeof window.calcularFilaManoObra === 'function') {
              window.calcularFilaManoObra($tr);
            }
          });

          // Usar ADAPTADORES (no llames directo) 👇
          _safeActualizarSubtotalesBloque($card, $card[0]);
          _safeActualizarTotalesPorTarea($card, $card[0]);
        });

        // Cerrar con el total general
        _safeActualizarTotalGeneral();

};

// Llamada automática si el contenedor ya está en el DOM (por backend)
$(function () {
  if ($('#contenedorPresupuestoGenerado').length) {
    window.initRecalculoPresupuestoCargado();
  }
});


// === Traer tarea — abrir modal y poblar listado real ===
$(document)
  .off('click.presu-traer-tarea', '#contenedorPresupuestoGenerado .btn-traer-tarea')
  .on('click.presu-traer-tarea', '#contenedorPresupuestoGenerado .btn-traer-tarea', function (e) {
    e.preventDefault(); e.stopPropagation();

    // Guardamos referencia de la card activa (la que recibirá la plantilla)
    const $card = $(this).closest('.tarea-card');
    const nro   = $(this).data('nro');
    window.__tareaDestinoTraer = { nro, $card };

    // Abrimos modal
    const $modal = $('#modalTraerTareaArchivada');
    const $tbody = $('#tablaTareasArchivadas tbody');
    $tbody.empty().append(
      `<tr><td colspan="4" class="text-muted">Cargando templados…</td></tr>`
    );
    $modal.modal('show');

    // Disparo inicial sin filtro
    cargarListadoTareasArchivadas('');
  });
  
// === Helper: renderizar filas en la tabla del modal ===
window.renderTablaTareasArchivadas = function (items) {
  const $tbody = $('#tablaTareasArchivadas tbody');
  $tbody.empty();

  if (!Array.isArray(items) || items.length === 0) {
    $tbody.append(`<tr><td colspan="4" class="text-muted">No hay tareas archivadas.</td></tr>`);
    return;
  }

  items.forEach(it => {
    const tr = `
      <tr>
        <td>${it.nombre_plantilla || ''}</td>
        <td>${it.nombre_original || ''}</td>
        <td>${it.created_at || ''}</td>
        <td class="text-center">
          <button type="button" class="btn btn-warning btn-sm usar-plantilla" data-id="${it.id_arch_tarea}">
            Usar
          </button>
        </td>
      </tr>`;
    $('#tablaTareasArchivadas tbody').append(tr);
  });
};

// === Helper: llamada AJAX a listar ===
window.cargarListadoTareasArchivadas = function (q, page = 1) {
  $.ajax({
    url: '../03-controller/presupuestos_guardar.php',
    method: 'POST',
    dataType: 'json',
    data: {
      via: 'ajax',
      funcion: 'listar_tareas_archivadas',
      q: q || '',
      page: page,
      per_page: 20
    }
  })
  .done(function (resp) {
    if (resp && resp.ok) {
      window.renderTablaTareasArchivadas(resp.items || []);
    } else {
      $('#tablaTareasArchivadas tbody').html(
        `<tr><td colspan="4" class="text-danger">Error al cargar listado.</td></tr>`
      );
      console.error('listar_tareas_archivadas → respuesta no OK:', resp);
    }
  })
  .fail(function (xhr) {
    $('#tablaTareasArchivadas tbody').html(
      `<tr><td colspan="4" class="text-danger">Fallo de red/servidor.</td></tr>`
    );
    console.error('listar_tareas_archivadas → error:', xhr.responseText || xhr.statusText);
  });
};

// === Traer tarea — usar plantilla seleccionada ===
$(document)
  .off('click.presu-traer-usar', '#tablaTareasArchivadas .usar-plantilla')
  .on('click.presu-traer-usar', '#tablaTareasArchivadas .usar-plantilla', function (e) {
    e.preventDefault(); e.stopPropagation();
    const id = parseInt($(this).data('id'), 10) || 0;
    if (!id) return;

    const destino = window.__tareaDestinoTraer;
    if (!destino || !destino.$card || !Number.isInteger(destino.nro)) {
      console.error('Destino no definido');
      return;
    }

    // Confirmación simple: reemplazar contenido de la tarea actual
    if (window.Swal && typeof Swal.fire === 'function') {
      Swal.fire({
        title: 'Reemplazar contenido de la tarea actual?',
        html: `<small>Se borrarán filas existentes de Materiales y Mano de Obra (excepto "Otros" y "Subtotal").</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, reemplazar',
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false,
        allowEscapeKey: false
      }).then((res) => {
        if (!res.isConfirmed) return;
        obtenerYAplicarPlantilla(id, destino.$card);
      });
    } else {
      if (window.confirm('Reemplazar contenido de la tarea actual?')) {
        obtenerYAplicarPlantilla(id, destino.$card);
      }
    }
  });

// === Helper: pedir al servidor y aplicar en la card ===
function obtenerYAplicarPlantilla(id_arch_tarea, $card) {
  // cerrar modal de selección
  $('#modalTraerTareaArchivada').modal('hide');

  $.ajax({
    url: '../03-controller/presupuestos_guardar.php',
    method: 'POST',
    dataType: 'json',
    data: {
      via: 'ajax',
      funcion: 'obtener_tarea_archivada',
      id_arch_tarea
    }
  })
  .done(function (resp) {
    if (!resp || !resp.ok || !resp.tarea) {
      console.error('obtener_tarea_archivada → respuesta no OK:', resp);
      if (window.mostrarError) mostrarError('No se pudo obtener la plantilla.');
      return;
    }
    aplicarPlantillaEnCard(resp.tarea, $card);
  })
  .fail(function (xhr) {
    console.error('obtener_tarea_archivada → error:', xhr.responseText || xhr.statusText);
    if (window.mostrarError) mostrarError('Fallo de red/servidor al obtener plantilla.');
  });
}

// === Helper: aplica materiales + MO + utilidades/otros en la card destino ===
function aplicarPlantillaEnCard(tareaPlantilla, $card) {
  if (!$card || !$card.length) return;

  // 0) Título de la tarea = nombre de la plantilla
  const $titulo = $card.find('.tarea-encabezado b');
  if ($titulo.length) {
    const txtActual = ($titulo.text() || '');
    const m = txtActual.match(/Tarea\s+(\d+)/i);
    const nroTxt = m ? m[1] : '';
    $titulo.text(`Tarea ${nroTxt}: ${tareaPlantilla.nombre_plantilla || ''}`);
  }

  // 0.b) Descripción (textarea)
  let nuevaDescripcion = '';
  if (typeof tareaPlantilla.descripcion === 'string' && tareaPlantilla.descripcion.trim() !== '') {
    nuevaDescripcion = tareaPlantilla.descripcion.trim();
  } else if (typeof tareaPlantilla.nombre_original === 'string') {
    nuevaDescripcion = tareaPlantilla.nombre_original.trim();
  }

  let $txt = $card.find('textarea.tarea-descripcion').first();
  if (!$txt.length) $txt = $card.find('textarea').first();
  if ($txt.length) $txt.val(nuevaDescripcion).trigger('input');

  // 1) Incluir en total
  $card.find('.incluir-en-total').prop('checked', !!tareaPlantilla.incluir_en_total);

  // 2) Utilidades y "otros"
  const uMat = tareaPlantilla.utilidad_materiales;
  const uMo  = tareaPlantilla.utilidad_mano_obra;
  $card.find('.utilidad-global-materiales').val(uMat === null ? '' : uMat);
  $card.find('.utilidad-global-mano-obra').val(uMo === null ? '' : uMo);

  $card.find('.input-otros-materiales').val(tareaPlantilla.otros_materiales || 0);
  $card.find('.input-otros-mano').val(tareaPlantilla.otros_mano_obra || 0);

  // 3) TBODYs
  const $tbMat = $card.find('.tarea-materiales tbody');
  const $tbMo  = $card.find('.tarea-mano-obra tbody');

  // =========================
  // MATERIALS (modo visita)
  // Header: Material | Cantidad | Precio Unitario | % Extra | Subtotal (5 cols)
  // =========================
  const $matOtros    = $tbMat.find('tr.fila-otros-materiales').first().detach();
  const $matSubtotal = $tbMat.find('tr.fila-subtotal').first().detach();
  $tbMat.empty();

  (tareaPlantilla.materiales || []).forEach(m => {
    const idMat = (m.id_material != null ? m.id_material : '');
    const nombre = (m.nombre || '');
    const cantidad = (m.cantidad != null ? m.cantidad : 0);
    const precio = (m.precio_unitario != null ? m.precio_unitario : 0);
    const extra = (m.porcentaje_extra != null ? m.porcentaje_extra : 0);

    const row = `
      <tr data-material-id="${idMat}">
        <td>${nombre}</td>
        <td>
          <input type="number" class="form-control form-control-sm cantidad-material"
                 value="${cantidad}" min="0" step="any">
        </td>
        <td>
          <input type="number" class="form-control form-control-sm precio-unitario bg-success"
                 value="${precio}" min="0" step="any" readonly>
        </td>
        <td>
          <input type="number" class="form-control form-control-sm porcentaje-extra"
                 value="${extra}" min="0" step="any">
        </td>
        <td class="text-right subtotal-material"></td>
      </tr>`;
    $tbMat.append(row);
  });

  if ($matOtros && $matOtros.length) $tbMat.append($matOtros);
  if ($matSubtotal && $matSubtotal.length) $tbMat.append($matSubtotal);

  // =========================
  // MANO DE OBRA (modo visita)
  // Header: Tipo | Operarios | Días | Jornales | Valor Jornal | % Extra | Subtotal (7 cols)
  // =========================
  const $moOtros    = $tbMo.find('tr.fila-otros-mano').first().detach();
  const $moSubtotal = $tbMo.find('tr.fila-subtotal').first().detach();
  $tbMo.empty();

  (tareaPlantilla.mano_obra || []).forEach(o => {
    const jornalId = (o.jornal_id != null ? o.jornal_id : '');
    const nombre = (o.nombre || '');

    // En plantillas puede venir "cantidad" (operarios) y "dias"
    const operarios = (o.cantidad != null ? o.cantidad : 0);
    const dias = (o.dias != null ? o.dias : 1);
    const valor = (o.jornal_valor != null ? o.jornal_valor : 0);
    const extra = (o.porcentaje_extra != null ? o.porcentaje_extra : 0);

    // Jornales = operarios * dias (readonly, como visita)
    const jornales = (parseFloat(operarios) || 0) * (parseFloat(dias) || 0);

    const row = `
      <tr data-jornal_id="${jornalId}">
        <td>${nombre}</td>

        <!-- Operarios -->
        <td>
          <input type="number" class="form-control form-control-sm cantidad-mano-obra"
                 value="${operarios}" min="0" step="any">
        </td>

        <!-- Días -->
        <td>
          <input type="number" class="form-control form-control-sm dias-mano-obra"
                 value="${dias}" min="0" step="any">
        </td>

        <!-- Jornales (Operarios × Días) -->
        <td>
          <input type="number" class="form-control form-control-sm jornales-mano-obra"
                 value="${jornales}" min="0" step="any" readonly>
        </td>

        <!-- Valor Jornal -->
        <td>
          <input type="number" class="form-control form-control-sm valor-jornal bg-success"
                 value="${valor}" min="0" step="any" readonly>
        </td>

        <!-- % Extra -->
        <td>
          <input type="number" class="form-control form-control-sm porcentaje-extra"
                 value="${extra}" min="0" step="any">
        </td>

        <!-- Subtotal -->
        <td class="text-right subtotal-mano"></td>
      </tr>`;
    $tbMo.append(row);
  });

  if ($moOtros && $moOtros.length) $tbMo.append($moOtros);
  if ($moSubtotal && $moSubtotal.length) $tbMo.append($moSubtotal);

  // 6) Recalcular con tus funciones existentes
  if (typeof window.initRecalculoPresupuestoCargado === 'function') {
    window.initRecalculoPresupuestoCargado();
  }

  // Si tus helpers existen, mejor usar esos (como ya venías haciendo)
  if (typeof window._safeActualizarSubtotalesBloque === 'function') {
    window._safeActualizarSubtotalesBloque($card, $card[0]);
  }
  if (typeof window._safeActualizarTotalesPorTarea === 'function') {
    window._safeActualizarTotalesPorTarea($card, $card[0]);
  }
  if (typeof window._safeActualizarTotalGeneral === 'function') {
    window._safeActualizarTotalGeneral();
  }

  if (window.mostrarExito) {
    mostrarExito('Plantilla aplicada a la tarea.');
  }
}



// === Filtro por texto (con debounce simple) ===
(function () {
  let t = null;
  $(document)
    .off('input.presu-traer-filter', '#filtroTareasArchivadas')
    .on('input.presu-traer-filter', '#filtroTareasArchivadas', function () {
      const q = $(this).val();
      clearTimeout(t);
      t = setTimeout(() => cargarListadoTareasArchivadas(q, 1), 300);
    });
})();





})(jQuery);
