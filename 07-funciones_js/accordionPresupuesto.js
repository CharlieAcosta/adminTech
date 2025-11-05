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


// ===============================================
// FUNCIÓN ÚNICA DE GUARDADO (backend + visita)
// Pegar esto en accordionPresupuesto.js (antes del cierre })(jQuery); )
// ===============================================
window.presupuestoGuardar = async function (idPresuOverride = null) {
  const $root = $('#contenedorPresupuestoGenerado');
  console.log('83 | const $btn  = $(#btn-guardar-presupuesto)'); const $btn  = $('#btn-guardar-presupuesto');

  // IDs (si viene del backend, suele estar en data-id_presupuesto)
  const id_presupuesto = idPresuOverride ?? (Number($root.data('id_presupuesto')) || null);
  const id_previsita   = $('#id_previsita').val() || null;
  const id_visita      = $('#id_visita').val() || null;

  try {
    $btn.prop('disabled', true);

    // ===== 1) LEER TAREAS DEL DOM =====
    const tareas = [];
    $('#contenedorPresupuestoGenerado .tarea-card').each(function (i) {
      const $card  = $(this);
      const nro    = i + 1;

      // descripción priorizamos textarea; si no hubiera, tomamos del título
      const descTextarea = $card.find('textarea').first().val();
      const descTitulo   = ($card.find('.tarea-encabezado b').text() || '').replace(/^Tarea\s+\d+:\s*/i, '');
      const descripcion  = (descTextarea != null ? String(descTextarea) : String(descTitulo || '')).trim();

      const incluir_en_total = $card.find('.incluir-en-total').prop('checked') ? 1 : 0;

      // --- Materiales
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

      const otros_materiales = parseFloat($card.find('.input-otros-materiales').val()) || 0;
      const util_mat_txt     = $card.find('.utilidad-global-materiales').val();
      const utilidad_materiales = util_mat_txt === '' ? null : (parseFloat(util_mat_txt) || 0);

      // --- Mano de obra
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

      const otros_mano_obra = parseFloat($card.find('.input-otros-mano').val()) || 0;
      const util_mo_txt     = $card.find('.utilidad-global-mano-obra').val();
      const utilidad_mano_obra = util_mo_txt === '' ? null : (parseFloat(util_mo_txt) || 0);

      // --- Fotos (buffers globales)
      const nuevas    = (window.fotosNuevasPorTarea && window.fotosNuevasPorTarea[nro]) ? window.fotosNuevasPorTarea[nro] : [];
      const eliminadas= (window.fotosEliminadasPorTarea && window.fotosEliminadasPorTarea[nro]) ? window.fotosEliminadasPorTarea[nro] : [];

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

    // ===== 2) ARMAR PAYLOAD BASE =====
    const payload = {
      id_presupuesto,
      id_previsita,
      id_visita,
      tareas
    };

    // ===== 3) ARMAR FORMDATA (con fotos) =====
    const fd = new FormData();
    fd.append('via',     'ajax');
    fd.append('funcion', 'guardarPresupuesto');
    fd.append('payload', JSON.stringify(payload));

    // Fotos nuevas: fotos_tarea_1[], fotos_tarea_2[], ...
    if (window.fotosNuevasPorTarea) {
      Object.keys(window.fotosNuevasPorTarea).forEach((k) => {
        (window.fotosNuevasPorTarea[k] || []).forEach(item => {
          if (item && item.file instanceof File) {
            fd.append(`fotos_tarea_${k}[]`, item.file);
          }
        });
      });
    }

    // Fotos eliminadas: fotos_eliminadas_tarea_1[], ...
    if (window.fotosEliminadasPorTarea) {
      Object.keys(window.fotosEliminadasPorTarea).forEach((k) => {
        (window.fotosEliminadasPorTarea[k] || []).forEach(nombre => {
          fd.append(`fotos_eliminadas_tarea_${k}[]`, nombre);
        });
      });
    }

    // ===== 4) AJAX AL ENDPOINT QUE YA USABAS =====
    const resp = await $.ajax({
      url: '03-controller/presupuestos_guardar.php',
      method: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      dataType: 'json'
    });

    // ===== 5) FEEDBACK =====
    if (resp && resp.ok) {
      // si el backend devuelve el id, lo reflejamos en el DOM para siguientes guardados
      const nuevoId = resp.id_presupuesto || (resp.presupuesto && resp.presupuesto.id_presupuesto);
      if (nuevoId) {
        $('#contenedorPresupuestoGenerado').attr('data-id_presupuesto', String(nuevoId));
      }

      // Modal/Toast verde
      if (window.toastr) toastr.success('Presupuesto guardado correctamente.');
      if (window.AlertConfirmV2) {
        AlertConfirmV2('success', 'ACCIÓN COMPLETADA', 'Presupuesto guardado correctamente.');
      }
    } else {
      const msg = (resp && resp.msg) ? resp.msg : 'Error al guardar el presupuesto.';
      if (window.toastr) toastr.error(msg);
      if (window.AlertConfirmV2) {
        AlertConfirmV2('error', 'INCONSISTENCIA', msg);
      }
    }

  } catch (err) {
    console.error('Error al guardar presupuesto:', err);
    if (window.toastr) toastr.error('Error al guardar el presupuesto.');
    if (window.AlertConfirmV2) {
      AlertConfirmV2('error', 'INCONSISTENCIA', 'Error al guardar el presupuesto.');
    }
  } finally {
    $btn.prop('disabled', false);
  }
};

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
      const id_presupuesto = idPresuOpcional || Number($root.data('id_presupuesto')) || null;
      const id_previsita   = $('#id_previsita').val() || null;
      const id_visita      = $('#id_visita').val() || null;

      const tareas = [];
      $root.find('.tarea-card').each(function (index) {
        const $card = $(this);
        const nro = index + 1;

        const descripcion        = $card.find('textarea').first().val()?.trim() || '';
        const incluir_en_total   = $card.find('.incluir-en-total').is(':checked') ? 1 : 0;
        const utilidad_materiales= parseFloat($card.find('.tarea-materiales .utilidad-global-materiales').val()) || null;
        const utilidad_mano_obra = parseFloat($card.find('.tarea-mano-obra .utilidad-global-mano-obra').val()) || null;
        const otros_materiales   = parseFloat($card.find('.tarea-materiales .input-otros-materiales').val()) || 0;
        const otros_mano_obra    = parseFloat($card.find('.tarea-mano-obra .input-otros-mano').val()) || 0;

        const materiales = [];
        $card.find('.tarea-materiales tbody tr').not('.fila-subtotal,.fila-otros-materiales').each(function () {
          const $tr = $(this);
          const nombre           = $tr.find('td').eq(0).text().trim();
          const cantidad         = parseFloat($tr.find('.cantidad-material').val()) || 0;
          const precio_unitario  = parseFloat($tr.find('.precio-unitario').val()) || 0;
          const porcentaje_extra = parseFloat($tr.find('.porcentaje-extra').val()) || 0;
          const id_material      = $tr.data('material_id') || $tr.data('material-id') || null;
          if (nombre || cantidad || precio_unitario) {
            materiales.push({ id_material, nombre, cantidad, precio_unitario, porcentaje_extra });
          }
        });

        const mano_obra = [];
        $card.find('.tarea-mano-obra tbody tr').not('.fila-subtotal,.fila-otros-mano').each(function () {
          const $tr = $(this);
          const nombre           = $tr.find('td').eq(0).text().trim();
          const cantidad         = parseFloat($tr.find('.cantidad-mano-obra').val()) || 0;
          const jornal_valor     = parseFloat($tr.find('.valor-jornal').val()) || 0;
          const porcentaje_extra = parseFloat($tr.find('.porcentaje-extra').val()) || 0;
          const jornal_id        = $tr.data('jornal_id') || $tr.data('jornal-id') || null;
          if (nombre || cantidad || jornal_valor) {
            mano_obra.push({ jornal_id, nombre, cantidad, jornal_valor, porcentaje_extra });
          }
        });

        // Contadores de fotos (buffers globales del flujo backend)
        const fotos_nuevas_cant = Array.isArray(window.presuImagenesPorTarea?.[nro])
          ? window.presuImagenesPorTarea[nro].filter(f => f && f.file instanceof File).length
          : 0;

        const fotos_eliminadas_cant = Array.isArray(window.presuFotosEliminadas?.[nro])
          ? window.presuFotosEliminadas[nro].length
          : 0;

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
          fotos_nuevas_cant,
          fotos_eliminadas_cant
        });
      });

      // FormData: payload + archivos + eliminadas
      const fd = new FormData();
      fd.append('via', 'ajax');
      fd.append('funcion', 'guardarPresupuesto');
      fd.append('payload', JSON.stringify({ id_presupuesto, id_previsita, id_visita, tareas }));

      // Fotos nuevas
      Object.entries(window.presuImagenesPorTarea || {}).forEach(([nro, arr]) => {
        (arr || []).forEach(f => {
          if (f && f.file instanceof File) {
            fd.append(`fotos_tarea_${nro}[]`, f.file, f.nombre || f.file.name);
          }
        });
      });

      // Fotos eliminadas
      Object.entries(window.presuFotosEliminadas || {}).forEach(([nro, arr]) => {
        (arr || []).forEach(nombre => {
          fd.append(`fotos_eliminadas_tarea_${nro}[]`, nombre);
        });
      });

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
        if (resp.id_presupuesto) {
          $('#contenedorPresupuestoGenerado').data('id_presupuesto', resp.id_presupuesto);
        }
        // mismo feedback verde que ya usás
        if (typeof mostrarExito === 'function') {
          mostrarExito('Presupuesto guardado correctamente.');
        } else {
          Swal.fire({ icon: 'success', title: 'ACCIÓN COMPLETADA', text: 'Presupuesto guardado correctamente.' });
        }
        // limpiar buffers locales
        window.presuImagenesPorTarea = {};
        window.presuFotosEliminadas  = {};
      } else {
        const msg = resp?.msg || 'No se pudo guardar el presupuesto.';
        if (typeof mostrarError === 'function') mostrarError(msg); else Swal.fire({ icon: 'error', title: 'Error', text: msg });
      }
    } catch (err) {
      console.error('Error al guardar presupuesto (unificado):', err);
      if (typeof mostrarError === 'function') mostrarError('Error al guardar el presupuesto.');
      else Swal.fire({ icon: 'error', title: 'Error', text: 'Error al guardar el presupuesto.' });
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



})(jQuery);
