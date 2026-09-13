// file accordionPresupuesto.js
(function ($) {
  'use strict';

  if (window.__presuHandlersBound) return;
  window.__presuHandlersBound = true;

  // buffers globales
  window.fotosNuevasPorTarea     = window.fotosNuevasPorTarea     || {};
  window.fotosEliminadasPorTarea = window.fotosEliminadasPorTarea || {};
  const DETALLE_TAREA_ALLOWED_TAGS = new Set(['b', 'strong', 'i', 'em', 'u', 'br', 'ul', 'ol', 'li', 'p', 'div']);
  const DETALLE_TAREA_BLOCK_TAGS = new Set(['p', 'div', 'ul', 'ol']);
  const DETALLE_TAREA_DROP_TAGS = new Set([
    'style',
    'script',
    'meta',
    'link',
    'title',
    'noscript',
    'template',
    'iframe',
    'object',
    'embed',
    'svg',
    'xml',
    'o:p'
  ]);
  const DETALLE_TAREA_INLINE_TAG_MAP = {
    b: 'strong',
    strong: 'strong',
    i: 'em',
    em: 'em',
    u: 'u'
  };

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function parseDetalleTareaInlineStyles(styleText) {
    const styles = {};
    String(styleText || '')
      .split(';')
      .forEach((declaration) => {
        const [rawKey, rawValue] = declaration.split(':');
        const key = String(rawKey || '').trim().toLowerCase();
        const value = String(rawValue || '').trim().toLowerCase();
        if (key && value) {
          styles[key] = value;
        }
      });
    return styles;
  }

  function detectDetalleTareaInlineWrappers(node, normalizedTag) {
    const wrappers = [];
    const tag = String(node?.tagName || '').toLowerCase();

    if (DETALLE_TAREA_INLINE_TAG_MAP[tag]) {
      wrappers.push(DETALLE_TAREA_INLINE_TAG_MAP[tag]);
    }

    const styles = parseDetalleTareaInlineStyles(node?.getAttribute?.('style') || '');
    const fontWeight = styles['font-weight'] || '';
    const fontStyle = styles['font-style'] || '';
    const textDecoration = `${styles['text-decoration'] || ''} ${styles['text-decoration-line'] || ''}`.trim();

    if (fontWeight === 'bold' || fontWeight === 'bolder') {
      wrappers.push('strong');
    } else if (/^\d+$/.test(fontWeight) && Number(fontWeight) >= 600) {
      wrappers.push('strong');
    }

    if (fontStyle.includes('italic') || fontStyle.includes('oblique')) {
      wrappers.push('em');
    }

    if (textDecoration.includes('underline')) {
      wrappers.push('u');
    }

    return Array.from(new Set(wrappers.filter((wrapper) => wrapper && wrapper !== normalizedTag)));
  }

  function wrapDetalleTareaNode(doc, node, wrappers) {
    return wrappers.reduceRight((acc, wrapperTag) => {
      const wrapper = doc.createElement(wrapperTag);
      wrapper.appendChild(acc);
      return wrapper;
    }, node);
  }

  function normalizarTextoPlanoDetalleTarea(rawText) {
    const normalized = String(rawText ?? '').replace(/\r\n?/g, '\n');
    if (!normalized.trim()) return '';

    return normalized
      .split(/\n{2,}/)
      .map((block) => block.trim())
      .filter(Boolean)
      .map((block) => `<div>${escapeHtml(block).replace(/\n/g, '<br>')}</div>`)
      .join('');
  }

  function editorContainsNode(editor, node) {
    if (!editor || !node) return false;

    const candidate = node.nodeType === window.Node.TEXT_NODE ? node.parentNode : node;
    return candidate === editor || !!(candidate && editor.contains(candidate));
  }

  function guardarSeleccionDetalleTarea(target) {
    const $wrapper = resolveDetalleEditorWrapper(target);
    if (!$wrapper.length) return;

    const editor = $wrapper.find('.tarea-descripcion-editor').get(0);
    const selection = window.getSelection ? window.getSelection() : null;
    if (!editor || !selection || !selection.rangeCount) return;

    const range = selection.getRangeAt(0);
    if (!editorContainsNode(editor, range.commonAncestorContainer)) return;

    $wrapper.data('richEditorRange', range.cloneRange());
  }

  function restaurarSeleccionDetalleTarea(target) {
    const $wrapper = resolveDetalleEditorWrapper(target);
    if (!$wrapper.length) return false;

    const editor = $wrapper.find('.tarea-descripcion-editor').get(0);
    const savedRange = $wrapper.data('richEditorRange');
    const selection = window.getSelection ? window.getSelection() : null;
    if (!editor || !selection) return false;

    editor.focus({ preventScroll: true });

    if (!(savedRange instanceof window.Range)) {
      return false;
    }

    try {
      selection.removeAllRanges();
      selection.addRange(savedRange);
      return true;
    } catch (err) {
      return false;
    }
  }

  function insertarHtmlEnDetalleTarea(target, html) {
    const $wrapper = resolveDetalleEditorWrapper(target);
    if (!$wrapper.length) return;

    const editor = $wrapper.find('.tarea-descripcion-editor').get(0);
    if (!editor) return;

    restaurarSeleccionDetalleTarea($wrapper);

    const selection = window.getSelection ? window.getSelection() : null;
    if (!selection) return;

    let range = selection.rangeCount ? selection.getRangeAt(0) : null;
    if (!range || !editorContainsNode(editor, range.commonAncestorContainer)) {
      range = document.createRange();
      range.selectNodeContents(editor);
      range.collapse(false);
      selection.removeAllRanges();
      selection.addRange(range);
    }

    range.deleteContents();
    const fragment = range.createContextualFragment(String(html || ''));
    const lastNode = fragment.lastChild;
    range.insertNode(fragment);

    if (lastNode) {
      const nextRange = document.createRange();
      nextRange.setStartAfter(lastNode);
      nextRange.collapse(true);
      selection.removeAllRanges();
      selection.addRange(nextRange);
    }

    guardarSeleccionDetalleTarea($wrapper);
  }

  function sanitizeDetalleTareaHtml(rawHtml) {
    const source = String(rawHtml ?? '').trim();
    if (!source) return '';

    const parser = new window.DOMParser();
    const doc = parser.parseFromString(`<div>${source}</div>`, 'text/html');
    const root = doc.body.firstElementChild || doc.body;

    const sanitizeNode = (node) => {
      if (node.nodeType === window.Node.TEXT_NODE) {
        return doc.createTextNode(node.textContent || '');
      }

      if (node.nodeType !== window.Node.ELEMENT_NODE) {
        return null;
      }

      const tag = (node.tagName || '').toLowerCase();
      const childNodes = Array.from(node.childNodes || []);

      if (DETALLE_TAREA_DROP_TAGS.has(tag)) {
        return null;
      }

      const normalizedTag = DETALLE_TAREA_ALLOWED_TAGS.has(tag)
        ? (DETALLE_TAREA_INLINE_TAG_MAP[tag] || tag)
        : null;
      const inlineWrappers = detectDetalleTareaInlineWrappers(node, normalizedTag);

      if (!normalizedTag) {
        const fragment = doc.createDocumentFragment();
        childNodes.forEach((child) => {
          const sanitizedChild = sanitizeNode(child);
          if (sanitizedChild) fragment.appendChild(sanitizedChild);
        });
        if (!fragment.childNodes.length) {
          return null;
        }

        if (!inlineWrappers.length) {
          return fragment;
        }

        const wrappedFragment = doc.createDocumentFragment();
        Array.from(fragment.childNodes).forEach((child) => {
          wrappedFragment.appendChild(wrapDetalleTareaNode(doc, child, inlineWrappers));
        });
        return wrappedFragment;
      }

      const element = doc.createElement(normalizedTag);
      childNodes.forEach((child) => {
        const sanitizedChild = sanitizeNode(child);
        if (sanitizedChild) element.appendChild(sanitizedChild);
      });

      if (inlineWrappers.length) {
        const originalChildren = Array.from(element.childNodes);
        element.textContent = '';
        originalChildren.forEach((child) => {
          element.appendChild(wrapDetalleTareaNode(doc, child, inlineWrappers));
        });
      }

      return element;
    };

    const output = doc.createElement('div');
    Array.from(root.childNodes || []).forEach((child) => {
      const sanitizedChild = sanitizeNode(child);
      if (sanitizedChild) output.appendChild(sanitizedChild);
    });

    const html = output.innerHTML
      .replace(/&nbsp;/gi, ' ')
      .replace(/<div><br><\/div>/gi, '<div></div>')
      .trim();

    return detalleTareaHtmlToPlainText(html) ? html : '';
  }

  function detalleTareaHtmlToPlainText(rawHtml) {
    const safeHtml = String(rawHtml ?? '').trim();
    if (!safeHtml) return '';

    const container = document.createElement('div');
    container.innerHTML = safeHtml;

    const walk = (node) => {
      let text = '';

      Array.from(node.childNodes || []).forEach((child) => {
        if (child.nodeType === window.Node.TEXT_NODE) {
          text += child.textContent || '';
          return;
        }

        if (child.nodeType !== window.Node.ELEMENT_NODE) {
          return;
        }

        const tag = (child.tagName || '').toLowerCase();

        if (tag === 'br') {
          text += '\n';
          return;
        }

        if (tag === 'li') {
          const liText = walk(child).trim();
          if (liText) {
            text += `${text && !text.endsWith('\n') ? '\n' : ''}- ${liText}\n`;
          }
          return;
        }

        const childText = walk(child);
        if (DETALLE_TAREA_BLOCK_TAGS.has(tag)) {
          if (childText.trim()) {
            text += childText.trimEnd();
            if (!text.endsWith('\n')) text += '\n';
            text += '\n';
          }
          return;
        }

        text += childText;
      });

      return text;
    };

    return walk(container)
      .replace(/\u00a0/g, ' ')
      .replace(/\r/g, '')
      .replace(/[ \t]+\n/g, '\n')
      .replace(/\n{3,}/g, '\n\n')
      .trim();
  }

  function resolveDetalleEditorWrapper(target) {
    const $target = $(target);
    if ($target.hasClass('tarea-detalle-editor')) {
      return $target;
    }
    return $target.find('.tarea-detalle-editor').first();
  }

  function obtenerDetalleTareaHtml($card) {
    const $textarea = $card.find('textarea.tarea-descripcion').first();
    return sanitizeDetalleTareaHtml($textarea.val() || '');
  }

  function obtenerDetalleTareaTexto($card) {
    return detalleTareaHtmlToPlainText(obtenerDetalleTareaHtml($card));
  }

  function setDetalleTareaEditorValue(target, htmlValue, opts = {}) {
    const options = {
      triggerInput: false,
      normalizeEditor: true,
      ...opts
    };

    const $wrapper = resolveDetalleEditorWrapper(target);
    if (!$wrapper.length) return;

    const safeHtml = sanitizeDetalleTareaHtml(htmlValue);
    const $editor = $wrapper.find('.tarea-descripcion-editor').first();
    const $textarea = $wrapper.find('textarea.tarea-descripcion').first();

    if (options.normalizeEditor && $editor.length && $editor.html() !== safeHtml) {
      $editor.html(safeHtml);
    }

    if ($textarea.length && $textarea.val() !== safeHtml) {
      $textarea.val(safeHtml);
      if (options.triggerInput) {
        $textarea.trigger('input');
      }
    }
  }

  function renderDetalleTareaEditorHtml(htmlValue = '') {
    const safeHtml = sanitizeDetalleTareaHtml(htmlValue);
    const safeTextareaValue = escapeHtml(safeHtml);

    return `
      <div class="tarea-detalle-editor">
        <div class="btn-toolbar btn-group-sm tarea-detalle-editor-toolbar mb-2" role="toolbar" aria-label="Formato del detalle de la tarea">
          <div class="btn-group mr-2" role="group" aria-label="Formato basico">
            <button type="button" class="btn btn-light rich-editor-action" data-command="bold" title="Negrita"><i class="fas fa-bold"></i></button>
            <button type="button" class="btn btn-light rich-editor-action" data-command="italic" title="Cursiva"><i class="fas fa-italic"></i></button>
            <button type="button" class="btn btn-light rich-editor-action" data-command="underline" title="Subrayado"><i class="fas fa-underline"></i></button>
          </div>
          <div class="btn-group mr-2" role="group" aria-label="Listas">
            <button type="button" class="btn btn-light rich-editor-action" data-command="insertUnorderedList" title="Lista"><i class="fas fa-list-ul"></i></button>
          </div>
          <div class="btn-group" role="group" aria-label="Limpiar formato">
            <button type="button" class="btn btn-light rich-editor-action" data-command="removeFormat" title="Limpiar formato"><i class="fas fa-eraser"></i></button>
          </div>
        </div>
        <div class="form-control form-control-sm tarea-descripcion-editor" contenteditable="true" data-placeholder="Describa la tarea..." aria-label="Editor de detalle de la tarea">${safeHtml}</div>
        <textarea class="form-control form-control-sm tarea-descripcion d-none" rows="5">${safeTextareaValue}</textarea>
      </div>
    `;
  }

  function syncDetalleTareaEditor(target, opts = {}) {
    const options = {
      triggerInput: true,
      normalizeEditor: true,
      ...opts
    };

    const $wrapper = resolveDetalleEditorWrapper(target);
    if (!$wrapper.length) return;

    const $editor = $wrapper.find('.tarea-descripcion-editor').first();
    if (!$editor.length) return;

    setDetalleTareaEditorValue($wrapper, $editor.html() || '', options);
    if (options.normalizeEditor) {
      guardarSeleccionDetalleTarea($wrapper);
    }
  }

  function initDetalleTareaRichEditors(root, opts = {}) {
    const options = {
      triggerInput: false,
      ...opts
    };

    $(root || document).find('.tarea-detalle-editor').each(function () {
      setDetalleTareaEditorValue(this, $(this).find('.tarea-descripcion').first().val() || '', options);
    });
  }

  window.detalleTareaHtmlToPlainText = detalleTareaHtmlToPlainText;
  window.renderDetalleTareaEditorHtml = renderDetalleTareaEditorHtml;
  window.initDetalleTareaRichEditors = initDetalleTareaRichEditors;
  window.setDetalleTareaEditorValue = function (target, htmlValue, opts) {
    setDetalleTareaEditorValue(target, htmlValue, opts);
  };
  window.normalizarTextoPlanoDetalleTarea = normalizarTextoPlanoDetalleTarea;
  window.obtenerDetalleTareaHtmlDesdeCard = function (cardOrJq) {
    return obtenerDetalleTareaHtml($(cardOrJq));
  };
  window.obtenerDetalleTareaTextoDesdeCard = function (cardOrJq) {
    return obtenerDetalleTareaTexto($(cardOrJq));
  };

  function resumirTituloTareaPresupuesto(texto) {
    const limpio = detalleTareaHtmlToPlainText(texto)
      .replace(/\r/g, '\n')
      .trim();

    if (!limpio) return '';

    const textoPlano = limpio
      .replace(/^[\s.,:\-*]+/, '')
      .replace(/\s+/g, ' ')
      .trim();

    if (!textoPlano) return '';

    const matchDelimitador = textoPlano.match(/^(.+?)[\.,:\*,-]/);
    if (matchDelimitador && matchDelimitador[1]) {
      return `${matchDelimitador[1].trim()}.`;
    }

    const palabras = textoPlano.split(/\s+/).filter(Boolean);
    if (palabras.length <= 12) {
      return textoPlano;
    }

    return `${palabras.slice(0, 12).join(' ')}...`;
  }

  function syncTituloCardPresupuesto($card) {
    if (!$card || !$card.length) return;

    const $titulo = $card.find('.tarea-encabezado b').first();
    if (!$titulo.length) return;

    const tituloActual = ($titulo.text() || '').trim();
    const matchNumero = tituloActual.match(/^Tarea\s+(\d+)/i);
    const numero = matchNumero ? matchNumero[1] : '';

    const descripcion = obtenerDetalleTareaTexto($card);

    const resumen = resumirTituloTareaPresupuesto(descripcion) || 'Detalle de la tarea';
    $titulo.text(numero ? `Tarea ${numero}: ${resumen}` : resumen);
  }

  window.resumirTituloTareaPresupuesto = resumirTituloTareaPresupuesto;
  window.syncTituloCardPresupuesto = function (cardOrJq) {
    syncTituloCardPresupuesto($(cardOrJq));
  };

  function presupuestoEdicionComercialBloqueada() {
    if (typeof window.obtenerBloqueoEdicionComercialSeguimiento !== 'function') {
      return false;
    }

    const bloqueo = window.obtenerBloqueoEdicionComercialSeguimiento();
    return !!(bloqueo && bloqueo.bloqueado);
  }

  function mostrarBloqueoEdicionComercialPresupuesto() {
    const mensaje = typeof window.mensajeBloqueoEdicionComercialSeguimiento === 'function'
      ? window.mensajeBloqueoEdicionComercialSeguimiento()
      : 'La edicion del seguimiento esta bloqueada por el estado comercial actual.';

    if (window.Swal && typeof Swal.fire === 'function') {
      Swal.fire({
        icon: 'warning',
        title: 'Edicion bloqueada',
        text: mensaje,
        confirmButtonText: 'OK'
      });
      return;
    }

    if (typeof mostrarAdvertencia === 'function') {
      mostrarAdvertencia(mensaje, 4);
      return;
    }

    window.alert(mensaje);
  }

  function actualizarEstadoAccionesPresupuestoSilencioso() {
    const rootSel = '#contenedorPresupuestoGenerado';
    const hayVencidos = $(`${rootSel} .precio-unitario.bg-danger, ${rootSel} .valor-jornal.bg-danger`).length > 0;
    const idPresupuesto = Number($(rootSel).data('id_presupuesto')) || null;
    const presupuestoDirty = !!window.presupuestoDirty;
    const $btnGuardar = $('.presupuesto-total-actions #btn-guardar-presupuesto');
    const $btnEmitir = $('.btn-emitir-presupuesto');

    const $btnAgregarTarea = $('#btn-agregar-tarea-presupuesto');
    const $accionesMaterial = $(`${rootSel} .presu-agregar-material, ${rootSel} .btn-eliminar-material-presupuesto`);
    const $inputsAltaMaterial = $(`${rootSel} .presu-material-select, ${rootSel} .presu-material-cantidad`);
    const $accionesManoObra = $(`${rootSel} .presu-agregar-mano-obra, ${rootSel} .btn-eliminar-mano-obra-presupuesto`);
    const $inputsAltaManoObra = $(`${rootSel} .presu-mano-obra-select, ${rootSel} .presu-mano-obra-operarios, ${rootSel} .presu-mano-obra-dias, ${rootSel} .presu-mano-obra-observacion`);

    if (presupuestoEdicionComercialBloqueada()) {
      $btnGuardar.prop('disabled', true).addClass('btn-secondary').removeClass('btn-success');
      $btnEmitir.prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
      $btnAgregarTarea.prop('disabled', true).addClass('disabled');
      $accionesMaterial.prop('disabled', true).addClass('disabled');
      $inputsAltaMaterial.prop('disabled', true);
      $accionesManoObra.prop('disabled', true).addClass('disabled');
      $inputsAltaManoObra.prop('disabled', true);
      return;
    }

    $btnAgregarTarea.prop('disabled', false).removeClass('disabled');
    $accionesMaterial.prop('disabled', false).removeClass('disabled');
    $inputsAltaMaterial.prop('disabled', false);
    $accionesManoObra.prop('disabled', false).removeClass('disabled');
    $inputsAltaManoObra.prop('disabled', false);

    $btnGuardar
      .prop('disabled', !presupuestoDirty)
      .toggleClass('btn-success', presupuestoDirty)
      .toggleClass('btn-secondary', !presupuestoDirty);

    const puedeEmitir = !!idPresupuesto && !presupuestoDirty && !hayVencidos;
    $btnEmitir
      .prop('disabled', !puedeEmitir)
      .toggleClass('btn-primary', puedeEmitir)
      .toggleClass('btn-secondary', !puedeEmitir);
  }

  function marcarPresupuestoComoModificadoSilencioso() {
    if (!$('#contenedorPresupuestoGenerado').length) return;

    window.presupuestoDirty = true;
    actualizarEstadoAccionesPresupuestoSilencioso();
  }
  window.marcarPresupuestoComoModificadoSilencioso = marcarPresupuestoComoModificadoSilencioso;


  let contadorClientKeyTareaPresupuesto = 0;

  function generarClientKeyTareaPresupuesto() {
    contadorClientKeyTareaPresupuesto += 1;
    return 'tmp_' + Date.now() + '_' + contadorClientKeyTareaPresupuesto;
  }

  function obtenerClientKeyTareaPresupuesto($card) {
    let key = String($card.attr('data-presu-client-key') || $card.data('presu-client-key') || '').trim();
    if (!key) {
      key = generarClientKeyTareaPresupuesto();
      $card.attr('data-presu-client-key', key).data('presu-client-key', key);
    }
    return key;
  }

  function obtenerIdPresuTareaCard($card) {
    const raw = String($card.attr('data-id-presu-tarea') || $card.data('id-presu-tarea') || '').trim();
    const id = parseInt(raw, 10);
    return Number.isFinite(id) && id > 0 ? id : null;
  }

  function setIdentidadPresuTareaCard($card, idPresuTarea, clientKey) {
    const id = parseInt(idPresuTarea, 10) || 0;
    const key = clientKey || (id > 0 ? 'pt_' + id : obtenerClientKeyTareaPresupuesto($card));
    $card.attr('data-id-presu-tarea', id > 0 ? String(id) : '').data('id-presu-tarea', id > 0 ? id : '');
    $card.attr('data-presu-client-key', key).data('presu-client-key', key);
    $card.find('.btn-tarea')
      .attr('data-id-presu-tarea', id > 0 ? String(id) : '')
      .data('id-presu-tarea', id > 0 ? id : '');
    return key;
  }

  function sincronizarDatosTareaPresupuesto($card, nro) {
    const key = obtenerClientKeyTareaPresupuesto($card);
    const $titulo = $card.find('.tarea-encabezado b').first();
    if ($titulo.length) {
      const tituloActual = ($titulo.text() || '').replace(/^Tarea\s+\d+:\s*/i, '').trim();
      $titulo.text('Tarea ' + nro + (tituloActual ? ': ' + tituloActual : ':'));
    }
    $card.find('.btn-tarea').attr('data-nro', String(nro)).data('nro', nro);
    $card.find('.presu-fotos')
      .attr('id', 'presu_fotos_tarea_' + nro)
      .attr('data-index', String(nro))
      .attr('data-client-key', key)
      .data('index', nro)
      .data('client-key', key);
    $card.find('.presu-dropzone')
      .attr('data-index', String(nro))
      .attr('data-client-key', key)
      .data('index', nro)
      .data('client-key', key);
    $card.find('.presu-preview-fotos').attr('id', 'presu_preview_' + nro);
  }

  function renumerarTareasPresupuesto() {
    $('#contenedorPresupuestoGenerado .tarea-card').each(function (index) {
      sincronizarDatosTareaPresupuesto($(this), index + 1);
    });
  }

  const ENDPOINT_PRESUPUESTO_GUARDAR = '../03-controller/presupuestos_guardar.php';

  function mostrarMensajeMaterialPresupuesto(tipo, mensaje) {
    if (tipo === 'error' && typeof mostrarError === 'function') {
      mostrarError(mensaje, 4);
      return;
    }
    if (tipo === 'warning' && typeof mostrarAdvertencia === 'function') {
      mostrarAdvertencia(mensaje, 4);
      return;
    }
    if (window.toastr && typeof toastr[tipo] === 'function') {
      toastr[tipo](mensaje);
      return;
    }
    window.alert(mensaje);
  }

  function parseNumeroPresupuesto(valor) {
    const n = parseFloat(String(valor == null ? '' : valor).replace(',', '.'));
    return Number.isFinite(n) ? n : 0;
  }

  function escaparHtmlPresupuesto(valor) {
    return $('<div>').text(String(valor == null ? '' : valor)).html();
  }

  function fechaCatalogoVencidaPresupuesto(fecha) {
    if (!fecha) return true;
    const base = new Date(String(fecha).replace(' ', 'T'));
    if (Number.isNaN(base.getTime())) return true;
    const diffDias = (Date.now() - base.getTime()) / 86400000;
    return diffDias > 30;
  }

  function obtenerDatoOptionPresupuesto($option, nombre) {
    if (!$option || !$option.length) return '';
    const snake = 'data-' + String(nombre);
    const kebab = 'data-' + String(nombre).replace(/_/g, '-');
    const directo = $option.attr(snake);
    if (directo !== undefined) return directo;
    const kebabAttr = $option.attr(kebab);
    if (kebabAttr !== undefined) return kebabAttr;
    const data = $option.data(nombre);
    return data == null ? '' : data;
  }

  function obtenerDatoOptionMaterial($option, nombre) {
    return obtenerDatoOptionPresupuesto($option, nombre);
  }

  function solicitarContextoMaterialPresupuesto(idMaterial) {
    return $.ajax({
      url: ENDPOINT_PRESUPUESTO_GUARDAR,
      method: 'POST',
      dataType: 'json',
      data: {
        via: 'ajax',
        funcion: 'obtenerContextoPrecioCatalogoPresupuestoDinamico',
        tipo: 'material',
        id_catalogo: idMaterial
      }
    });
  }

  function confirmarContextoMaterialPresupuesto(contexto, importe, accionResolucion) {
    return $.ajax({
      url: ENDPOINT_PRESUPUESTO_GUARDAR,
      method: 'POST',
      dataType: 'json',
      data: {
        via: 'ajax',
        funcion: 'confirmarPrecioCatalogoPresupuestoDinamico',
        tipo: 'material',
        id_catalogo: contexto.id_catalogo || contexto.id_material,
        importe: importe,
        accion_resolucion: accionResolucion || 'CONFIRMAR_VIGENCIA_CATALOGO',
        precio_catalogo_esperado: contexto.precio_catalogo || contexto.precio_unitario || contexto.precio || '',
        fecha_catalogo_esperada: contexto.fecha_catalogo || contexto.fecha_actualizacion || contexto.log_edicion || contexto.log_alta || ''
      }
    });
  }

  function solicitarContextoJornalPresupuesto(idJornal) {
    return $.ajax({
      url: ENDPOINT_PRESUPUESTO_GUARDAR,
      method: 'POST',
      dataType: 'json',
      data: {
        via: 'ajax',
        funcion: 'obtenerContextoPrecioCatalogoPresupuestoDinamico',
        tipo: 'jornal',
        id_catalogo: idJornal
      }
    });
  }

  function confirmarContextoJornalPresupuesto(contexto, importe, accionResolucion) {
    return $.ajax({
      url: ENDPOINT_PRESUPUESTO_GUARDAR,
      method: 'POST',
      dataType: 'json',
      data: {
        via: 'ajax',
        funcion: 'confirmarPrecioCatalogoPresupuestoDinamico',
        tipo: 'jornal',
        id_catalogo: contexto.id_catalogo || contexto.jornal_id,
        importe: importe,
        accion_resolucion: accionResolucion || 'CONFIRMAR_VIGENCIA_CATALOGO',
        precio_catalogo_esperado: contexto.precio_catalogo || contexto.jornal_valor || contexto.precio || '',
        fecha_catalogo_esperada: contexto.fecha_catalogo || contexto.fecha_actualizacion || contexto.updated_at || contexto.created_at || ''
      }
    });
  }

  async function pedirNuevoPrecioMaterialPresupuesto(contexto) {
    const actual = parseNumeroPresupuesto(contexto.precio_unitario || contexto.precio);
    const resp = await Swal.fire({
      icon: 'question',
      title: 'Actualizar precio',
      text: 'Ingrese el precio vigente para este material.',
      input: 'number',
      inputValue: actual,
      inputAttributes: { min: '0', step: 'any' },
      showCancelButton: true,
      confirmButtonText: 'Confirmar',
      cancelButtonText: 'Cancelar',
      preConfirm: (value) => {
        const n = parseNumeroPresupuesto(value);
        if (!(n > 0)) {
          Swal.showValidationMessage('Ingrese un precio mayor a cero.');
          return false;
        }
        return n;
      }
    });
    return resp.isConfirmed ? resp.value : null;
  }

  async function pedirNuevoPrecioJornalPresupuesto(contexto) {
    const actual = parseNumeroPresupuesto(contexto.jornal_valor || contexto.precio_catalogo || contexto.precio);
    const resp = await Swal.fire({
      icon: 'question',
      title: 'Actualizar valor jornal',
      text: 'Ingrese el valor vigente para este jornal.',
      input: 'number',
      inputValue: actual,
      inputAttributes: { min: '0', step: 'any' },
      showCancelButton: true,
      confirmButtonText: 'Confirmar',
      cancelButtonText: 'Cancelar',
      preConfirm: (value) => {
        const n = parseNumeroPresupuesto(value);
        if (!(n > 0)) {
          Swal.showValidationMessage('Ingrese un valor mayor a cero.');
          return false;
        }
        return n;
      }
    });
    return resp.isConfirmed ? resp.value : null;
  }

  async function resolverPrecioMaterialNuevoPresupuesto(idMaterial) {
    const contextoResp = await solicitarContextoMaterialPresupuesto(idMaterial);
    if (!contextoResp || contextoResp.ok === false || contextoResp.status === false) {
      throw new Error((contextoResp && (contextoResp.mensaje || contextoResp.error)) || 'No se pudo obtener el precio del material.');
    }

    const contexto = contextoResp.contexto || contextoResp.data || contextoResp;
    const precio = parseNumeroPresupuesto(contexto.precio_catalogo || contexto.precio_unitario || contexto.precio);
    const fecha = contexto.fecha_catalogo || contexto.fecha_actualizacion || contexto.log_edicion || contexto.log_alta || '';

    if (!fechaCatalogoVencidaPresupuesto(fecha)) {
      return { precio, fecha, contexto };
    }

    if (!window.Swal || typeof Swal.fire !== 'function') {
      throw new Error('El precio del material esta vencido y debe confirmarse antes de agregarlo.');
    }

    const decision = await Swal.fire({
      icon: 'warning',
      title: 'Precio vencido',
      text: 'El precio del material tiene mas de 30 dias. Confirme el valor actual o cargue uno nuevo antes de agregarlo.',
      showDenyButton: true,
      showCancelButton: true,
      confirmButtonText: 'Usar precio actual',
      denyButtonText: 'Ingresar nuevo',
      cancelButtonText: 'Cancelar'
    });

    if (decision.isDismissed) return null;

    let precioConfirmado = precio;
    let accion = 'CONFIRMAR_VIGENCIA_CATALOGO';
    if (decision.isDenied) {
      const nuevo = await pedirNuevoPrecioMaterialPresupuesto(contexto);
      if (nuevo == null) return null;
      precioConfirmado = nuevo;
      accion = 'ACTUALIZAR_PRECIO_CATALOGO';
    }

    const confirmacion = await confirmarContextoMaterialPresupuesto(contexto, precioConfirmado, accion);
    if (!confirmacion || confirmacion.ok === false || confirmacion.status === false) {
      throw new Error((confirmacion && (confirmacion.mensaje || confirmacion.error)) || 'No se pudo confirmar el precio del material.');
    }

    const confirmado = confirmacion.contexto || confirmacion.data || confirmacion;
    return {
      precio: parseNumeroPresupuesto(confirmado.importe_persistido || confirmado.precio_catalogo || confirmado.precio_unitario || confirmado.precio || precioConfirmado),
      fecha: confirmado.fecha_actualizacion || confirmado.fecha_catalogo || confirmado.log_edicion || confirmado.log_alta || '',
      contexto: confirmado
    };
  }

  async function resolverPrecioJornalNuevoPresupuesto(idJornal) {
    const contextoResp = await solicitarContextoJornalPresupuesto(idJornal);
    if (!contextoResp || contextoResp.ok === false || contextoResp.status === false) {
      throw new Error((contextoResp && (contextoResp.mensaje || contextoResp.error)) || 'No se pudo obtener el valor del jornal.');
    }

    const contexto = contextoResp.contexto || contextoResp.data || contextoResp;
    const precio = parseNumeroPresupuesto(contexto.precio_catalogo || contexto.jornal_valor || contexto.precio);
    const fecha = contexto.fecha_catalogo || contexto.fecha_actualizacion || contexto.updated_at || contexto.created_at || '';

    if (!fechaCatalogoVencidaPresupuesto(fecha)) {
      return { precio, fecha, contexto };
    }

    if (!window.Swal || typeof Swal.fire !== 'function') {
      throw new Error('El valor del jornal esta vencido y debe confirmarse antes de agregarlo.');
    }

    const decision = await Swal.fire({
      icon: 'warning',
      title: 'Valor jornal vencido',
      text: 'El valor del jornal tiene mas de 30 dias. Confirme el valor actual o cargue uno nuevo antes de agregarlo.',
      showDenyButton: true,
      showCancelButton: true,
      confirmButtonText: 'Usar valor actual',
      denyButtonText: 'Ingresar nuevo',
      cancelButtonText: 'Cancelar'
    });

    if (decision.isDismissed) return null;

    let precioConfirmado = precio;
    let accion = 'CONFIRMAR_VIGENCIA_CATALOGO';
    if (decision.isDenied) {
      const nuevo = await pedirNuevoPrecioJornalPresupuesto(contexto);
      if (nuevo == null) return null;
      precioConfirmado = nuevo;
      accion = 'ACTUALIZAR_PRECIO_CATALOGO';
    }

    const confirmacion = await confirmarContextoJornalPresupuesto(contexto, precioConfirmado, accion);
    if (!confirmacion || confirmacion.ok === false || confirmacion.status === false) {
      throw new Error((confirmacion && (confirmacion.mensaje || confirmacion.error)) || 'No se pudo confirmar el valor del jornal.');
    }

    const confirmado = confirmacion.contexto || confirmacion.data || confirmacion;
    return {
      precio: parseNumeroPresupuesto(confirmado.importe_persistido || confirmado.precio_catalogo || confirmado.jornal_valor || confirmado.precio || precioConfirmado),
      fecha: confirmado.fecha_actualizacion || confirmado.fecha_catalogo || confirmado.updated_at || confirmado.created_at || '',
      contexto: confirmado
    };
  }

  function materialYaExisteEnTarea($card, idMaterial) {
    const idBuscado = String(idMaterial);
    let existe = false;
    $card.find('.tarea-materiales tbody tr[data-material-id]').each(function () {
      if (String($(this).attr('data-material-id') || $(this).data('material-id') || '') === idBuscado) {
        existe = true;
        return false;
      }
      return true;
    });
    return existe;
  }

  function renumerarMaterialesPresupuesto($card) {
    $card.find('.tarea-materiales tbody tr[data-material-id]').each(function (index) {
      $(this).attr('data-orden', String(index + 1)).data('orden', index + 1);
    });
  }

  function crearFilaMaterialPresupuesto(datos) {
    const idPtm = datos.id_ptm ? String(datos.id_ptm) : '';
    const idPresupuesto = Number($('#contenedorPresupuestoGenerado').data('id_presupuesto')) || '';
    const fecha = datos.fecha_actualizacion || datos.log_edicion || datos.log_alta || '';
    const precioVencido = fechaCatalogoVencidaPresupuesto(fecha);
    const clasePrecio = precioVencido ? 'bg-danger' : 'bg-success';
    const nombre = escaparHtmlPresupuesto(datos.nombre || datos.descripcion || 'Material');
    const cantidad = parseNumeroPresupuesto(datos.cantidad);
    const precio = parseNumeroPresupuesto(datos.precio_unitario || datos.precio_unitario_usado);
    const extra = parseNumeroPresupuesto(datos.porcentaje_extra);

    const $fila = $(
      '<tr data-material-id="' + escaparHtmlPresupuesto(datos.id_material) + '" data-id-ptm="' + escaparHtmlPresupuesto(idPtm) + '" data-orden="' + escaparHtmlPresupuesto(datos.orden || '') + '">' +
        '<td><span class="material-nombre-presupuesto">' + nombre + '</span></td>' +
        '<td><input type="number" class="form-control form-control-sm cantidad-material" min="0" step="any"></td>' +
        '<td><input type="number" class="form-control form-control-sm precio-unitario ' + clasePrecio + '" min="0" step="any" readonly></td>' +
        '<td><input type="number" class="form-control form-control-sm porcentaje-extra" min="0" step="any"></td>' +
        '<td class="text-right subtotal-material"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-material-presupuesto" title="Eliminar material"><i class="fas fa-trash"></i></button></td>' +
      '</tr>'
    );

    $fila.attr('data-log_alta', datos.log_alta || '').attr('data-log_edicion', datos.log_edicion || fecha || '');
    $fila.find('.cantidad-material').val(cantidad);
    $fila.find('.precio-unitario')
      .val(precio)
      .attr('data-id-presupuesto', idPresupuesto)
      .attr('data-id-ptm', idPtm)
      .attr('data-id-material', datos.id_material)
      .attr('data-fecha-actualizacion', fecha)
      .attr('data-confirmar-precio-tipo', 'material')
      .data('id-presupuesto', idPresupuesto)
      .data('id-ptm', idPtm)
      .data('id-material', datos.id_material)
      .data('fecha-actualizacion', fecha)
      .data('confirmar-precio-tipo', 'material');
    $fila.find('.porcentaje-extra').val(extra);
    return $fila;
  }

  function insertarFilaMaterialPresupuesto($card, datos) {
    const $tbody = $card.find('.tarea-materiales tbody').first();
    const $fila = crearFilaMaterialPresupuesto(datos);
    const $referencia = $tbody.find('tr.fila-otros-materiales, tr.fila-subtotal').first();
    if ($referencia.length) $referencia.before($fila); else $tbody.append($fila);
    renumerarMaterialesPresupuesto($card);
    if (typeof window.calcularFilaMaterial === 'function') window.calcularFilaMaterial($fila);
    _safeActualizarSubtotalesBloque($card, $card[0]);
    _safeActualizarTotalesPorTarea($card, $card[0]);
    _safeActualizarTotalGeneral();
    marcarPresupuestoComoModificadoSilencioso();
    actualizarEstadoAccionesPresupuestoSilencioso();
    return $fila;
  }

  function inicializarSelectMaterialesPresupuesto($scope) {
    const $root = $scope && $scope.length ? $scope : $('#contenedorPresupuestoGenerado');
    const opciones = $('#opcionesMaterialBase').html() || '';
    $root.find('.presu-material-select').each(function () {
      const $select = $(this);
      if (!$select.find('option[value!=""]').length && opciones) {
        $select.append(opciones);
      }
      if ($.fn.select2 && !$select.data('select2')) {
        $select.select2({ width: '100%', placeholder: 'Material', allowClear: true });
      }
    });
  }

  function jornalYaExisteEnTarea($card, idJornal) {
    const idBuscado = String(idJornal);
    let existe = false;
    $card.find('.tarea-mano-obra tbody tr[data-jornal_id]').each(function () {
      if (String($(this).attr('data-jornal_id') || $(this).data('jornal_id') || '') === idBuscado) {
        existe = true;
        return false;
      }
      return true;
    });
    return existe;
  }

  function renumerarManoObraPresupuesto($card) {
    $card.find('.tarea-mano-obra tbody tr[data-jornal_id]').each(function (index) {
      $(this).attr('data-orden', String(index + 1)).data('orden', index + 1);
    });
  }

  function crearFilaManoObraPresupuesto(datos) {
    const idPtmo = datos.id_ptmo ? String(datos.id_ptmo) : '';
    const idPresupuesto = Number($('#contenedorPresupuestoGenerado').data('id_presupuesto')) || '';
    const fecha = datos.fecha_actualizacion || datos.updated_at || datos.created_at || '';
    const precioVencido = fechaCatalogoVencidaPresupuesto(fecha);
    const clasePrecio = precioVencido ? 'bg-danger' : 'bg-success';
    const nombre = escaparHtmlPresupuesto(datos.nombre || datos.descripcion || 'Jornal');
    const operarios = parseNumeroPresupuesto(datos.cantidad || datos.operarios);
    const dias = parseNumeroPresupuesto(datos.dias || 1);
    const jornales = operarios * dias;
    const valor = parseNumeroPresupuesto(datos.jornal_valor || datos.valor_jornal_usado);
    const extra = parseNumeroPresupuesto(datos.porcentaje_extra);

    const $fila = $(
      '<tr data-jornal_id="' + escaparHtmlPresupuesto(datos.jornal_id || datos.id_jornal) + '" data-id-ptmo="' + escaparHtmlPresupuesto(idPtmo) + '" data-orden="' + escaparHtmlPresupuesto(datos.orden || '') + '">' +
        '<td><span class="mano-obra-nombre-presupuesto">' + nombre + '</span></td>' +
        '<td><input type="number" class="form-control form-control-sm cantidad-mano-obra" min="0" step="any"></td>' +
        '<td><input type="number" class="form-control form-control-sm dias-mano-obra" min="0" step="any"></td>' +
        '<td><input type="number" class="form-control form-control-sm jornales-mano-obra" min="0" step="any" readonly></td>' +
        '<td><input type="number" class="form-control form-control-sm valor-jornal ' + clasePrecio + '" min="0" step="any" readonly></td>' +
        '<td><input type="number" class="form-control form-control-sm porcentaje-extra" min="0" step="any"></td>' +
        '<td class="text-right subtotal-mano"></td>' +
        '<td><input type="text" class="form-control form-control-sm observacion-mano-obra" maxlength="255"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-mano-obra-presupuesto" title="Eliminar mano de obra"><i class="fas fa-trash"></i></button></td>' +
      '</tr>'
    );

    $fila.find('.cantidad-mano-obra').val(operarios);
    $fila.find('.dias-mano-obra').val(dias);
    $fila.find('.jornales-mano-obra').val(jornales);
    $fila.find('.valor-jornal')
      .val(valor)
      .attr('data-id-presupuesto', idPresupuesto)
      .attr('data-id-ptmo', idPtmo)
      .attr('data-id-jornal', datos.jornal_id || datos.id_jornal)
      .attr('data-fecha-actualizacion', fecha)
      .attr('data-confirmar-precio-tipo', 'jornal')
      .data('id-presupuesto', idPresupuesto)
      .data('id-ptmo', idPtmo)
      .data('id-jornal', datos.jornal_id || datos.id_jornal)
      .data('fecha-actualizacion', fecha)
      .data('confirmar-precio-tipo', 'jornal');
    $fila.find('.porcentaje-extra').val(extra);
    $fila.find('.observacion-mano-obra').val(datos.observacion || '');
    return $fila;
  }

  function insertarFilaManoObraPresupuesto($card, datos) {
    const $tbody = $card.find('.tarea-mano-obra tbody').first();
    const $fila = crearFilaManoObraPresupuesto(datos);
    const $referencia = $tbody.find('tr.fila-otros-mano, tr.fila-subtotal').first();
    if ($referencia.length) $referencia.before($fila); else $tbody.append($fila);
    renumerarManoObraPresupuesto($card);
    if (typeof window.calcularFilaManoObra === 'function') window.calcularFilaManoObra($fila);
    _safeActualizarSubtotalesBloque($card, $card[0]);
    _safeActualizarTotalesPorTarea($card, $card[0]);
    _safeActualizarTotalGeneral();
    marcarPresupuestoComoModificadoSilencioso();
    actualizarEstadoAccionesPresupuestoSilencioso();
    return $fila;
  }

  function inicializarSelectManoObraPresupuesto($scope) {
    const $root = $scope && $scope.length ? $scope : $('#contenedorPresupuestoGenerado');
    const opciones = $('#opcionesManoObraBase').html() || '';
    $root.find('.presu-mano-obra-select').each(function () {
      const $select = $(this);
      if (!$select.find('option[value!=""]').length && opciones) {
        $select.append(opciones);
      }
      if ($.fn.select2 && !$select.data('select2')) {
        $select.select2({ width: '100%', placeholder: 'Tipo de jornal', allowClear: true });
      }
    });
  }


  function obtenerResumenPreciosVencidosPresupuesto() {
    const $root = $('#contenedorPresupuestoGenerado');
    const contarLineas = function (selector) {
      const lineas = new Set();
      $root.find(selector).each(function () {
        if (String(this.type || '').toLowerCase() === 'hidden') return;
        const fila = $(this).closest('tr').get(0);
        lineas.add(fila || this);
      });
      return lineas.size;
    };

    const cantidadMaterialesVencidos = contarLineas('.precio-unitario.bg-danger');
    const cantidadJornalesVencidos = contarLineas('.valor-jornal.bg-danger');
    const cantidadTotalVencidos = cantidadMaterialesVencidos + cantidadJornalesVencidos;
    const categorias = [];

    if (cantidadMaterialesVencidos > 0) {
      categorias.push(`${cantidadMaterialesVencidos} ${cantidadMaterialesVencidos === 1 ? 'material' : 'materiales'}`);
    }
    if (cantidadJornalesVencidos > 0) {
      categorias.push(`${cantidadJornalesVencidos} ${cantidadJornalesVencidos === 1 ? 'jornal' : 'jornales'}`);
    }

    const introduccion = cantidadTotalVencidos === 1
      ? 'Se detectó 1 valor desactualizado:'
      : `Se detectaron ${cantidadTotalVencidos} valores desactualizados:`;
    const html = cantidadTotalVencidos > 0
      ? `<p>${introduccion}<br><strong>${categorias.join(' y ')}</strong>.</p>`
        + '<p>Puede guardar el borrador, pero deberá actualizar todos los valores antes de generar el documento.</p>'
      : '';

    return {
      cantidadMaterialesVencidos,
      cantidadJornalesVencidos,
      cantidadTotalVencidos,
      html
    };
  }
  window.obtenerResumenPreciosVencidosPresupuesto = obtenerResumenPreciosVencidosPresupuesto;

  // limpieza por namespace
  $(document)
    .off('click.presu',  '.presu-dropzone')
    .off('change.presu', '.presu-fotos')
    .off('click.presu',  '.presu-eliminar-imagen')
    .off('click.presu', '#btn-guardar-presupuesto')
    .off('click.presu-agregar-tarea', '#btn-agregar-tarea-presupuesto')
    .off('click.presu-agregar-material', '#contenedorPresupuestoGenerado .presu-agregar-material')
    .off('click.presu-eliminar-material', '#contenedorPresupuestoGenerado .btn-eliminar-material-presupuesto')
    .off('click.presu-agregar-mano-obra', '#contenedorPresupuestoGenerado .presu-agregar-mano-obra')
    .off('click.presu-eliminar-mano-obra', '#contenedorPresupuestoGenerado .btn-eliminar-mano-obra-presupuesto')
    .off('click.presu-eliminar-tarea', '#contenedorPresupuestoGenerado .btn-eliminar-tarea-presupuesto')
    .on('click.presu-agregar-tarea', '#btn-agregar-tarea-presupuesto', function (e) {
      e.preventDefault();
      e.stopPropagation();

      if (presupuestoEdicionComercialBloqueada()) {
        mostrarBloqueoEdicionComercialPresupuesto();
        return;
      }

      const $root = $('#contenedorPresupuestoGenerado');
      const $base = $root.find('.tarea-card').last();
      if (!$base.length) return;

      const $nueva = $base.clone(false, false);
      $nueva.find('.select2-container').remove();
      $nueva.find('.presu-material-select, .presu-mano-obra-select')
        .removeClass('select2-hidden-accessible')
        .removeAttr('data-select2-id tabindex aria-hidden')
        .val('');

      const key = generarClientKeyTareaPresupuesto();
      setIdentidadPresuTareaCard($nueva, null, key);
      window.fotosNuevasPorTarea[key] = [];
      window.fotosEliminadasPorTarea[key] = [];

      if (typeof window.setDetalleTareaEditorValue === 'function') {
        window.setDetalleTareaEditorValue($nueva, '', { triggerInput: false });
      } else {
        $nueva.find('textarea.tarea-descripcion').val('');
        $nueva.find('.tarea-descripcion-editor').empty();
      }
      $nueva.find('.incluir-en-total').prop('checked', true);
      $nueva.find('.utilidad-global-materiales, .utilidad-global-mano-obra').val('');
      $nueva.find('.input-otros-materiales, .input-otros-mano').val('0');
      $nueva.find('.tarea-materiales tbody tr').not('.fila-otros-materiales,.fila-subtotal').remove();
      $nueva.find('.tarea-mano-obra tbody tr').not('.fila-otros-mano,.fila-subtotal').remove();
      $nueva.find('.presu-preview-fotos').empty();
      $nueva.find('.presu-fotos').val('');
      $nueva.find('.presu-material-select').val('');
      $nueva.find('.presu-material-cantidad').val('1');
      $nueva.find('.presu-mano-obra-select').val('');
      $nueva.find('.presu-mano-obra-operarios').val('1');
      $nueva.find('.presu-mano-obra-dias').val('1');
      $nueva.find('.presu-mano-obra-observacion').val('');

      $root.find('.presupuesto-total-card').before($nueva);
      renumerarTareasPresupuesto();
      initDetalleTareaRichEditors($nueva, { triggerInput: false });
      inicializarSelectMaterialesPresupuesto($nueva);
      inicializarSelectManoObraPresupuesto($nueva);
      syncTituloCardPresupuesto($nueva);
      _safeActualizarSubtotalesBloque($nueva, $nueva[0]);
      _safeActualizarTotalesPorTarea($nueva, $nueva[0]);
      _safeActualizarTotalGeneral();
      marcarPresupuestoComoModificadoSilencioso();
    })
    .on('click.presu-agregar-material', '#contenedorPresupuestoGenerado .presu-agregar-material', async function (e) {
      e.preventDefault();
      e.stopPropagation();

      if (presupuestoEdicionComercialBloqueada()) {
        mostrarBloqueoEdicionComercialPresupuesto();
        return;
      }

      const $btn = $(this);
      const $card = $btn.closest('.tarea-card');
      const $filaAlta = $btn.closest('.presu-material-add-row');
      const $select = $filaAlta.find('.presu-material-select').first();
      const $cantidad = $filaAlta.find('.presu-material-cantidad').first();
      const idMaterial = parseInt($select.val(), 10) || 0;
      const cantidad = parseNumeroPresupuesto($cantidad.val());

      if (!(idMaterial > 0)) {
        mostrarMensajeMaterialPresupuesto('warning', 'Seleccione un material.');
        return;
      }
      if (!(cantidad > 0)) {
        mostrarMensajeMaterialPresupuesto('warning', 'Ingrese una cantidad mayor a cero.');
        return;
      }
      if (materialYaExisteEnTarea($card, idMaterial)) {
        mostrarMensajeMaterialPresupuesto('warning', 'Ese material ya existe en la tarea. Para reemplazarlo, elimine la linea anterior y agregue el nuevo material.');
        return;
      }

      const $option = $select.find('option:selected');
      $btn.prop('disabled', true).addClass('disabled');
      try {
        const precioResuelto = await resolverPrecioMaterialNuevoPresupuesto(idMaterial);
        if (!precioResuelto) return;

        insertarFilaMaterialPresupuesto($card, {
          id_ptm: null,
          id_material: idMaterial,
          nombre: ($option.text() || '').trim(),
          cantidad,
          precio_unitario: precioResuelto.precio,
          porcentaje_extra: 0,
          fecha_actualizacion: precioResuelto.fecha,
          log_alta: obtenerDatoOptionMaterial($option, 'log_alta'),
          log_edicion: precioResuelto.fecha || obtenerDatoOptionMaterial($option, 'log_edicion')
        });

        $select.val('').trigger('change');
        $cantidad.val('1');
      } catch (err) {
        mostrarMensajeMaterialPresupuesto('error', err && err.message ? err.message : 'No se pudo agregar el material.');
      } finally {
        $btn.prop('disabled', false).removeClass('disabled');
        actualizarEstadoAccionesPresupuestoSilencioso();
      }
    })
    .on('click.presu-eliminar-material', '#contenedorPresupuestoGenerado .btn-eliminar-material-presupuesto', function (e) {
      e.preventDefault();
      e.stopPropagation();

      if (presupuestoEdicionComercialBloqueada()) {
        mostrarBloqueoEdicionComercialPresupuesto();
        return;
      }

      const $fila = $(this).closest('tr');
      const $card = $fila.closest('.tarea-card');
      const eliminar = () => {
        $fila.remove();
        renumerarMaterialesPresupuesto($card);
        _safeActualizarSubtotalesBloque($card, $card[0]);
        _safeActualizarTotalesPorTarea($card, $card[0]);
        _safeActualizarTotalGeneral();
        marcarPresupuestoComoModificadoSilencioso();
      };

      if (window.Swal && typeof Swal.fire === 'function') {
        Swal.fire({
          icon: 'warning',
          title: 'Eliminar material?',
          text: 'La baja se aplicara al guardar el presupuesto.',
          showCancelButton: true,
          confirmButtonText: 'Eliminar',
          cancelButtonText: 'Cancelar'
        }).then((res) => { if (res.isConfirmed) eliminar(); });
      } else if (window.confirm('Eliminar material?')) {
        eliminar();
      }
    })
    .on('click.presu-agregar-mano-obra', '#contenedorPresupuestoGenerado .presu-agregar-mano-obra', async function (e) {
      e.preventDefault();
      e.stopPropagation();

      if (presupuestoEdicionComercialBloqueada()) {
        mostrarBloqueoEdicionComercialPresupuesto();
        return;
      }

      const $btn = $(this);
      const $card = $btn.closest('.tarea-card');
      const $filaAlta = $btn.closest('.presu-mano-obra-add-row');
      const $select = $filaAlta.find('.presu-mano-obra-select').first();
      const $operarios = $filaAlta.find('.presu-mano-obra-operarios').first();
      const $dias = $filaAlta.find('.presu-mano-obra-dias').first();
      const $observacion = $filaAlta.find('.presu-mano-obra-observacion').first();
      const idJornal = parseInt($select.val(), 10) || 0;
      const operarios = parseNumeroPresupuesto($operarios.val());
      const dias = parseNumeroPresupuesto($dias.val());

      if (!(idJornal > 0)) {
        mostrarMensajeMaterialPresupuesto('warning', 'Seleccione un tipo de jornal.');
        return;
      }
      if (!(operarios > 0)) {
        mostrarMensajeMaterialPresupuesto('warning', 'Ingrese operarios mayor a cero.');
        return;
      }
      if (!(dias > 0)) {
        mostrarMensajeMaterialPresupuesto('warning', 'Ingrese dias mayor a cero.');
        return;
      }
      if (jornalYaExisteEnTarea($card, idJornal)) {
        mostrarMensajeMaterialPresupuesto('warning', 'Ese tipo de jornal ya existe en la tarea. Para reemplazarlo, elimine la linea anterior y agregue el nuevo jornal.');
        return;
      }

      const $option = $select.find('option:selected');
      $btn.prop('disabled', true).addClass('disabled');
      try {
        const precioResuelto = await resolverPrecioJornalNuevoPresupuesto(idJornal);
        if (!precioResuelto) return;

        insertarFilaManoObraPresupuesto($card, {
          id_ptmo: null,
          jornal_id: idJornal,
          nombre: ($option.text() || '').trim(),
          cantidad: operarios,
          dias,
          jornal_valor: precioResuelto.precio,
          porcentaje_extra: 0,
          observacion: ($observacion.val() || '').trim(),
          fecha_actualizacion: precioResuelto.fecha
        });

        $select.val('').trigger('change');
        $operarios.val('1');
        $dias.val('1');
        $observacion.val('');
      } catch (err) {
        mostrarMensajeMaterialPresupuesto('error', err && err.message ? err.message : 'No se pudo agregar mano de obra.');
      } finally {
        $btn.prop('disabled', false).removeClass('disabled');
        actualizarEstadoAccionesPresupuestoSilencioso();
      }
    })
    .on('click.presu-eliminar-mano-obra', '#contenedorPresupuestoGenerado .btn-eliminar-mano-obra-presupuesto', function (e) {
      e.preventDefault();
      e.stopPropagation();

      if (presupuestoEdicionComercialBloqueada()) {
        mostrarBloqueoEdicionComercialPresupuesto();
        return;
      }

      const $fila = $(this).closest('tr');
      const $card = $fila.closest('.tarea-card');
      const eliminar = () => {
        $fila.remove();
        renumerarManoObraPresupuesto($card);
        _safeActualizarSubtotalesBloque($card, $card[0]);
        _safeActualizarTotalesPorTarea($card, $card[0]);
        _safeActualizarTotalGeneral();
        marcarPresupuestoComoModificadoSilencioso();
      };

      if (window.Swal && typeof Swal.fire === 'function') {
        Swal.fire({
          icon: 'warning',
          title: 'Eliminar mano de obra?',
          text: 'La baja se aplicara al guardar el presupuesto.',
          showCancelButton: true,
          confirmButtonText: 'Eliminar',
          cancelButtonText: 'Cancelar'
        }).then((res) => { if (res.isConfirmed) eliminar(); });
      } else if (window.confirm('Eliminar mano de obra?')) {
        eliminar();
      }
    })
    .on('click.presu-eliminar-tarea', '#contenedorPresupuestoGenerado .btn-eliminar-tarea-presupuesto', function (e) {
      e.preventDefault();
      e.stopPropagation();

      if (presupuestoEdicionComercialBloqueada()) {
        mostrarBloqueoEdicionComercialPresupuesto();
        return;
      }

      const $card = $(this).closest('.tarea-card');
      const confirmar = () => {
        const key = obtenerClientKeyTareaPresupuesto($card);
        delete window.fotosNuevasPorTarea[key];
        delete window.fotosEliminadasPorTarea[key];
        $card.remove();
        renumerarTareasPresupuesto();
        _safeActualizarTotalGeneral();
        marcarPresupuestoComoModificadoSilencioso();
      };

      if (window.Swal && typeof Swal.fire === 'function') {
        Swal.fire({
          icon: 'warning',
          title: 'Eliminar tarea?',
          text: 'La baja se aplicara al guardar el presupuesto.',
          showCancelButton: true,
          confirmButtonText: 'Eliminar',
          cancelButtonText: 'Cancelar'
        }).then((res) => { if (res.isConfirmed) confirmar(); });
      } else if (window.confirm('Eliminar tarea?')) {
        confirmar();
      }
    })
    .on('click.presu', '#btn-guardar-presupuesto', function (e) {
      e.preventDefault();
      e.stopPropagation();

      if (presupuestoEdicionComercialBloqueada()) {
        mostrarBloqueoEdicionComercialPresupuesto();
        return;
      }

      window.presupuestoGuardar(); // usa el data-id_presupuesto si existe
    });

  $(document)
    .off('mousedown.presu-editor', '.rich-editor-action')
    .on('mousedown.presu-editor', '.rich-editor-action', function (e) {
      guardarSeleccionDetalleTarea($(this).closest('.tarea-detalle-editor'));
      e.preventDefault();
    })
    .off('click.presu-editor', '.rich-editor-action')
    .on('click.presu-editor', '.rich-editor-action', function (e) {
      e.preventDefault();
      const $btn = $(this);
      const $wrapper = $btn.closest('.tarea-detalle-editor');
      const editor = $wrapper.find('.tarea-descripcion-editor').get(0);
      if (!editor) return;

      restaurarSeleccionDetalleTarea($wrapper);
      editor.focus({ preventScroll: true });
      const command = String($btn.data('command') || '');
      if (command === 'removeFormat') {
        document.execCommand('removeFormat', false, null);
      } else if (command) {
        document.execCommand(command, false, null);
      }

      guardarSeleccionDetalleTarea($wrapper);
      syncDetalleTareaEditor($wrapper, { triggerInput: true });
    })
    .off('mouseup.presu-editor keyup.presu-editor focusin.presu-editor input.presu-editor blur.presu-editor paste.presu-editor', '.tarea-descripcion-editor')
    .on('mouseup.presu-editor keyup.presu-editor focusin.presu-editor', '.tarea-descripcion-editor', function () {
      guardarSeleccionDetalleTarea($(this).closest('.tarea-detalle-editor'));
    })
    .on('input.presu-editor', '.tarea-descripcion-editor', function () {
      guardarSeleccionDetalleTarea($(this).closest('.tarea-detalle-editor'));
      syncDetalleTareaEditor($(this).closest('.tarea-detalle-editor'), {
        triggerInput: true,
        normalizeEditor: false
      });
      marcarPresupuestoComoModificadoSilencioso();
    })
    .on('blur.presu-editor', '.tarea-descripcion-editor', function () {
      syncDetalleTareaEditor($(this).closest('.tarea-detalle-editor'), {
        triggerInput: true,
        normalizeEditor: true
      });
    })
    .on('paste.presu-editor', '.tarea-descripcion-editor', function (e) {
      e.preventDefault();
      const $wrapper = $(this).closest('.tarea-detalle-editor');
      const clipboard = e.originalEvent && e.originalEvent.clipboardData ? e.originalEvent.clipboardData : null;
      const htmlClipboard = clipboard ? clipboard.getData('text/html') : '';
      const textClipboard = clipboard ? clipboard.getData('text/plain') : '';
      const htmlParaInsertar = htmlClipboard
        ? sanitizeDetalleTareaHtml(htmlClipboard)
        : normalizarTextoPlanoDetalleTarea(textClipboard);

      if (!htmlParaInsertar && textClipboard) {
        insertarHtmlEnDetalleTarea($wrapper, escapeHtml(textClipboard));
      } else if (htmlParaInsertar) {
        insertarHtmlEnDetalleTarea($wrapper, htmlParaInsertar);
      }

      syncDetalleTareaEditor($wrapper, {
        triggerInput: true,
        normalizeEditor: false
      });
    });

  $(function () {
    initDetalleTareaRichEditors(document, { triggerInput: false });
    inicializarSelectMaterialesPresupuesto($('#contenedorPresupuestoGenerado'));
    inicializarSelectManoObraPresupuesto($('#contenedorPresupuestoGenerado'));
    actualizarEstadoAccionesPresupuestoSilencioso();
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
            const $inputActual = $(this);
            const esConfirmacionPrecioVencido = (
              $inputActual.hasClass('bg-danger')
              && ($inputActual.hasClass('precio-unitario') || $inputActual.hasClass('valor-jornal'))
            );

            if (!esConfirmacionPrecioVencido) {
              marcarPresupuestoComoModificadoSilencioso();
            }

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

            marcarPresupuestoComoModificadoSilencioso();

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

            marcarPresupuestoComoModificadoSilencioso();

            if (typeof window.actualizarTotalesPorTarea === 'function') {
              _safeActualizarTotalesPorTarea($card, this);
            }
            if (typeof window.actualizarTotalGeneral === 'function') {
              window.actualizarTotalGeneral();
            }
          });

        $(document)
          .off('input.presu change.presu', `${rootSel} .tarea-card textarea`)
          .on('input.presu change.presu', `${rootSel} .tarea-card textarea`, function () {
            syncTituloCardPresupuesto($(this).closest('.tarea-card'));
            marcarPresupuestoComoModificadoSilencioso();
          });
    })();


  // === Confirmacion de vigencia para precios renderizados por PHP ===
  (function bindConfirmacionPrecioPresupuestoPHP() {
    const rootSel = '#contenedorPresupuestoGenerado';
    const precioSel = [
      `${rootSel} .precio-unitario.bg-danger[data-confirmar-precio-tipo="material"][data-id-presupuesto][data-id-ptm][data-id-material]`,
      `${rootSel} .valor-jornal.bg-danger[data-confirmar-precio-tipo="jornal"][data-id-presupuesto][data-id-ptmo][data-id-jornal]`
    ].join(',');
    const ENDPOINT_CONFIRMACION = '../03-controller/presupuestos_guardar.php';
    const MENSAJE_ERROR_CONFIRMACION = 'No se pudo confirmar la vigencia del precio.';
    let sweetContextualActivo = false;

    function mostrarErrorConfirmacionPrecio(mensaje) {
      const texto = String(mensaje || MENSAJE_ERROR_CONFIRMACION).trim() || MENSAJE_ERROR_CONFIRMACION;

      if (typeof mostrarError === 'function') {
        mostrarError(texto);
        return;
      }
      if (typeof toastr !== 'undefined' && toastr && typeof toastr.error === 'function') {
        toastr.error(texto);
        return;
      }
      if (window.Swal && typeof Swal.fire === 'function') {
        Swal.fire({ icon: 'error', title: 'Error', text: texto });
        return;
      }

      window.alert(texto);
    }

    function presupuestoEdicionBloqueadaParaConfirmar() {
      if (typeof window.obtenerBloqueoEdicionComercialSeguimiento !== 'function') {
        return false;
      }

      const bloqueo = window.obtenerBloqueoEdicionComercialSeguimiento();
      return !!(bloqueo && bloqueo.bloqueado);
    }

    function revalidarDatosVencidosPresupuestoPHP() {
      actualizarEstadoAccionesPresupuestoSilencioso();
    }

    function leerDatoConfirmacionPrecio($input, nombreAttr) {
      const nombreData = nombreAttr.replace(/^data-/, '');
      const valorAttr = $input.attr(nombreAttr);
      if (valorAttr !== undefined && valorAttr !== null && String(valorAttr).trim() !== '') {
        return String(valorAttr).trim();
      }

      const valorData = $input.data(nombreData);
      return String(valorData ?? '').trim();
    }

    function formatSubtotalConfirmado(valor) {
      const normalizado = String(valor ?? '').trim().replace(',', '.');
      const numero = Number(normalizado);

      if (!Number.isFinite(numero)) {
        return `$${String(valor ?? '').trim()}`;
      }

      const partes = numero.toFixed(2).split('.');
      const entero = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      return `$${entero},${partes[1]}`;
    }

    function obtenerIdentidadPrecio($input) {
      const tipo = leerDatoConfirmacionPrecio($input, 'data-confirmar-precio-tipo');
      const idPresupuesto = leerDatoConfirmacionPrecio($input, 'data-id-presupuesto')
        || String($(rootSel).data('id_presupuesto') || '').trim();
      const idLinea = tipo === 'material'
        ? leerDatoConfirmacionPrecio($input, 'data-id-ptm')
        : leerDatoConfirmacionPrecio($input, 'data-id-ptmo');
      const idCatalogo = tipo === 'material'
        ? leerDatoConfirmacionPrecio($input, 'data-id-material')
        : leerDatoConfirmacionPrecio($input, 'data-id-jornal');

      return {
        via: 'ajax',
        tipo,
        id_presupuesto: idPresupuesto,
        id_linea: idLinea,
        id_catalogo: idCatalogo
      };
    }

    function payloadConfirmacionCompleto(payload) {
      return !!(
        payload.tipo
        && payload.id_presupuesto
        && payload.id_linea
        && payload.id_catalogo
      );
    }

    function recalcularDespuesDeConfirmacion($input, respuesta) {
      const $tr = $input.closest('tr');
      const $card = $input.closest('.tarea-card');

      if (respuesta && Object.prototype.hasOwnProperty.call(respuesta, 'subtotal_snapshot')) {
        if ($input.hasClass('precio-unitario')) {
          $tr.find('.subtotal-material').text(formatSubtotalConfirmado(respuesta.subtotal_snapshot));
        } else if ($input.hasClass('valor-jornal')) {
          $tr.find('.subtotal-mano').text(formatSubtotalConfirmado(respuesta.subtotal_snapshot));
        }
      }

      if ($tr.find('.cantidad-material').length && typeof window.calcularFilaMaterial === 'function') {
        window.calcularFilaMaterial($tr);
      }
      if ($tr.find('.cantidad-mano-obra').length && typeof window.calcularFilaManoObra === 'function') {
        window.calcularFilaManoObra($tr);
      }

      if (respuesta && Object.prototype.hasOwnProperty.call(respuesta, 'subtotal_snapshot')) {
        if ($input.hasClass('precio-unitario')) {
          $tr.find('.subtotal-material').text(formatSubtotalConfirmado(respuesta.subtotal_snapshot));
        } else if ($input.hasClass('valor-jornal')) {
          $tr.find('.subtotal-mano').text(formatSubtotalConfirmado(respuesta.subtotal_snapshot));
        }
      }

      if (typeof window.actualizarSubtotalesBloque === 'function') {
        _safeActualizarSubtotalesBloque($card, $input[0]);
      }
      if (typeof window.actualizarTotalesPorTarea === 'function') {
        _safeActualizarTotalesPorTarea($card, $input[0]);
      }
      if (typeof window.actualizarTotalGeneral === 'function') {
        _safeActualizarTotalGeneral();
      }
    }

    function solicitarPrecioPresupuesto(data) {
      return new Promise((resolve, reject) => {
        $.ajax({ url: ENDPOINT_CONFIRMACION, method: 'POST', dataType: 'json', data })
          .done((respuesta) => respuesta && respuesta.ok === true
            ? resolve(respuesta)
            : reject(new Error((respuesta && respuesta.mensaje) || MENSAJE_ERROR_CONFIRMACION)))
          .fail((xhr) => reject(new Error(
            (xhr && xhr.responseJSON && xhr.responseJSON.mensaje) || MENSAJE_ERROR_CONFIRMACION
          )));
      });
    }

    function monedaPrecio(valor) {
      const numero = Number(String(valor ?? '').replace(',', '.'));
      return Number.isFinite(numero)
        ? numero.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        : String(valor ?? '');
    }

    function escaparHtmlPrecio(valor) {
      return $('<div>').text(String(valor ?? '')).html();
    }

    function activarSupresionFocoPrecio($input) {
      if (!$input || !$input.length) return;
      $input
        .data('precioSwalSuppressFocus', true)
        .attr('data-precio-swal-suppress-focus', 'true');
      $input[0].blur();
    }

    function liberarSupresionFocoPrecioCuandoCorresponda($input) {
      if (!$input || !$input.length) return;
      const input = $input[0];
      const siguienteFrame = window.requestAnimationFrame
        ? window.requestAnimationFrame.bind(window)
        : (callback) => window.setTimeout(callback, 0);

      const verificarSinFoco = function () {
        if (document.activeElement === input) {
          input.blur();
          window.setTimeout(verificarSinFoco, 0);
          return;
        }

        siguienteFrame(function () {
          if (document.activeElement === input) {
            input.blur();
            window.setTimeout(verificarSinFoco, 0);
            return;
          }

          $input
            .removeData('precioSwalSuppressFocus')
            .removeAttr('data-precio-swal-suppress-focus');
        });
      };

      window.setTimeout(verificarSinFoco, 0);
    }

    async function pedirNuevoPrecioCatalogo(contexto, $input) {
      const resultado = await Swal.fire({
        icon: 'warning',
        title: 'Modificar precio maestro',
        html: `<p>Precio actual del catálogo: <strong>$${monedaPrecio(contexto.precio_catalogo)}</strong></p>`
          + '<p>Esta acción modificará el catálogo utilizado por futuras visitas y presupuestos.</p>'
          + '<p>Los demás presupuestos existentes no serán modificados.</p>',
        input: 'text',
        inputLabel: 'Nuevo precio',
        inputAttributes: { inputmode: 'decimal', autocomplete: 'off' },
        showCancelButton: true,
        confirmButtonText: 'Actualizar catálogo y presupuesto',
        cancelButtonText: 'Volver',
        confirmButtonColor: '#198754',
        returnFocus: false,
        willClose: () => activarSupresionFocoPrecio($input),
        inputValidator: (valor) => {
          const texto = String(valor ?? '').trim();
          if (!/^\d+(?:[\.,]\d{1,2})?$/.test(texto)) return 'Ingresá un importe positivo con hasta dos decimales.';
          const normalizado = texto.replace(',', '.');
          const partes = normalizado.split('.');
          const entero = partes[0].replace(/^0+(?=\d)/, '');
          if (entero.length > 8 || Number(normalizado) <= 0) return 'El importe debe ser mayor que cero y no superar 99999999,99.';
          return undefined;
        }
      });
      return resultado.isConfirmed ? String(resultado.value).trim() : null;
    }

    async function decidirResolucionPrecio(contexto, $input) {
      const descripcion = escaparHtmlPrecio(contexto.descripcion);
      const snapshot = monedaPrecio(contexto.precio_snapshot);
      const catalogo = monedaPrecio(contexto.precio_catalogo);
      const comun = `<p><strong>${contexto.tipo === 'material' ? 'Material' : 'Jornal'}:</strong> ${descripcion}</p>`;
      let config;
      if (contexto.escenario === 'MISMO_PRECIO_CATALOGO_VIGENTE') {
        config = {
          icon: 'info', title: 'Actualizar vigencia del presupuesto',
          html: `${comun}<p>Precio del presupuesto: <strong>$${snapshot}</strong><br>Precio vigente del catálogo: <strong>$${catalogo}</strong></p><p>El importe coincide con el catálogo vigente.<br>Se actualizará únicamente esta línea del presupuesto.</p>`,
          confirmButtonText: 'Actualizar presupuesto', showCancelButton: true, cancelButtonText: 'Cancelar'
        };
      } else if (contexto.escenario === 'MISMO_PRECIO_CATALOGO_VENCIDO') {
        config = {
          icon: 'warning', title: 'Confirmar vigencia del precio',
          html: `${comun}<p>Precio del presupuesto: <strong>$${snapshot}</strong><br>Precio del catálogo: <strong>$${catalogo}</strong></p><p>El catálogo también está desactualizado.</p>`,
          confirmButtonText: 'Confirmar vigencia', showDenyButton: true, denyButtonText: 'Ingresar otro precio', showCancelButton: true, cancelButtonText: 'Cancelar'
        };
      } else if (contexto.escenario === 'DISTINTO_PRECIO_CATALOGO_VIGENTE') {
        config = {
          icon: 'warning', title: 'El catálogo tiene un precio más reciente',
          html: `${comun}<p>Precio del presupuesto: <strong>$${snapshot}</strong><br>Precio vigente del catálogo: <strong>$${catalogo}</strong></p>`,
          confirmButtonText: `Aplicar $${catalogo} al presupuesto`, showDenyButton: true, denyButtonText: 'Modificar catálogo y presupuesto', showCancelButton: true, cancelButtonText: 'Cancelar'
        };
      } else {
        config = {
          icon: 'warning', title: 'Presupuesto y catálogo desactualizados',
          html: `${comun}<p>Precio del presupuesto: <strong>$${snapshot}</strong><br>Precio del catálogo: <strong>$${catalogo}</strong></p><p>El catálogo también superó el período de vigencia.</p>`,
          confirmButtonText: `Confirmar $${catalogo} y aplicarlo`, showDenyButton: true, denyButtonText: 'Ingresar otro precio', showCancelButton: true, cancelButtonText: 'Cancelar'
        };
      }
      config.confirmButtonColor = '#198754';
      config.returnFocus = false;
      config.willClose = () => activarSupresionFocoPrecio($input);
      const eleccion = await Swal.fire(config);
      if (eleccion.isDismissed) return null;
      if (eleccion.isDenied) {
        const importe = await pedirNuevoPrecioCatalogo(contexto, $input);
        return importe === null ? null : { accion: 'ACTUALIZAR_CATALOGO_Y_SNAPSHOT', importe };
      }
      const acciones = {
        MISMO_PRECIO_CATALOGO_VIGENTE: 'SINCRONIZAR_SNAPSHOT_DESDE_CATALOGO',
        MISMO_PRECIO_CATALOGO_VENCIDO: 'CONFIRMAR_CATALOGO_Y_SINCRONIZAR',
        DISTINTO_PRECIO_CATALOGO_VIGENTE: 'APLICAR_CATALOGO_AL_SNAPSHOT',
        DISTINTO_PRECIO_CATALOGO_VENCIDO: 'CONFIRMAR_CATALOGO_Y_SINCRONIZAR'
      };
      return { accion: acciones[contexto.escenario], importe: contexto.precio_catalogo };
    }

    async function resolverPrecioPresupuestoPHP(input) {
      const $input = $(input);
      if (!$input.hasClass('bg-danger') || $input.data('confirmacionPrecioPresupuestoPendiente') || sweetContextualActivo) return;
      if (presupuestoEdicionBloqueadaParaConfirmar()) {
        $input.blur();
        mostrarBloqueoEdicionComercialPresupuesto();
        return;
      }
      const identidad = obtenerIdentidadPrecio($input);
      if (!payloadConfirmacionCompleto(identidad)) {
        $input.blur();
        mostrarErrorConfirmacionPrecio(MENSAJE_ERROR_CONFIRMACION);
        return;
      }
      const dirtyAntes = !!window.presupuestoDirty;
      sweetContextualActivo = true;
      $input.data('confirmacionPrecioPresupuestoPendiente', true).prop('readonly', true);
      try {
        const contexto = await solicitarPrecioPresupuesto({ ...identidad, funcion: 'obtenerContextoPrecioPresupuesto' });
        const precioSnapshotCoincideCatalogo = [
          'MISMO_PRECIO_CATALOGO_VIGENTE',
          'MISMO_PRECIO_CATALOGO_VENCIDO'
        ].includes(String(contexto.escenario || ''));
        if (contexto.snapshot_vencido !== true && precioSnapshotCoincideCatalogo) {
          $input
            .val(String(contexto.precio_snapshot))
            .attr('data-fecha-actualizacion', String(contexto.fecha_snapshot || ''))
            .data('fecha-actualizacion', String(contexto.fecha_snapshot || ''))
            .removeClass('bg-danger').addClass('bg-success').prop('readonly', true);
          recalcularDespuesDeConfirmacion($input, null);
          revalidarDatosVencidosPresupuestoPHP();
          return;
        }
        const decision = await decidirResolucionPrecio(contexto, $input);
        if (!decision) return;
        const respuesta = await solicitarPrecioPresupuesto({
          ...identidad,
          funcion: 'confirmarPrecioPresupuesto',
          accion_resolucion: decision.accion,
          importe: decision.importe,
          precio_snapshot_esperado: contexto.precio_snapshot,
          fecha_snapshot_esperada: contexto.fecha_snapshot,
          precio_catalogo_esperado: contexto.precio_catalogo,
          fecha_catalogo_esperada: contexto.fecha_catalogo
        });
        $input
          .val(String(respuesta.importe_snapshot))
          .attr('data-fecha-actualizacion', String(respuesta.fecha_origen_snapshot || ''))
          .data('fecha-actualizacion', String(respuesta.fecha_origen_snapshot || ''))
          .removeClass('bg-danger').addClass('bg-success').prop('readonly', true);
        recalcularDespuesDeConfirmacion($input, respuesta);
        if (!dirtyAntes) window.presupuestoDirty = false;
        revalidarDatosVencidosPresupuestoPHP();
      } catch (error) {
        mostrarErrorConfirmacionPrecio(error && error.message);
        revalidarDatosVencidosPresupuestoPHP();
      } finally {
        $input.removeData('confirmacionPrecioPresupuestoPendiente');
        activarSupresionFocoPrecio($input);
        sweetContextualActivo = false;
        liberarSupresionFocoPrecioCuandoCorresponda($input);
      }
    }

    $(document)
      .off('.presu-confirmar-precio')
      .on('focusin.presu-confirmar-precio', precioSel, function () {
        if ($(this).data('precioSwalSuppressFocus')) return;
        resolverPrecioPresupuestoPHP(this);
      });

    $(function () {
      if ($(rootSel).length) {
        revalidarDatosVencidosPresupuestoPHP();
      }
    });
  })();

  (function bindAlertaPreciosVencidosAperturaPresupuestoPHP() {
    const collapseSel = '#collapsePresupuesto';

    function mostrarAlertaPreciosVencidosPresupuesto() {
      const resumen = obtenerResumenPreciosVencidosPresupuesto();
      if (resumen.cantidadTotalVencidos === 0) return false;

      const alertDanger = [
        false,
        '<H3><strong>VALORES DESACTUALIZADOS</H3>',
        resumen.html,
        'OK',
        false,
        false,
        true,
        '#dc3545',
        '#fff',
        '#198754',
        '#fff'
      ];

      if (typeof sAlertConfirmV2 === 'function') {
        sAlertConfirmV2(alertDanger);
        return true;
      }

      if (window.Swal && typeof Swal.fire === 'function') {
        Swal.fire({
          icon: 'warning',
          title: 'VALORES DESACTUALIZADOS',
          html: resumen.html,
          confirmButtonText: 'OK'
        });
        return true;
      }

      return false;
    }
    window.mostrarAlertaPreciosVencidosPresupuesto = mostrarAlertaPreciosVencidosPresupuesto;

    $(document)
      .off('shown.bs.collapse.presu-alerta-vencidos hidden.bs.collapse.presu-alerta-vencidos', collapseSel)
      .on('shown.bs.collapse.presu-alerta-vencidos', collapseSel, function () {
        const $collapse = $(this);
        if ($collapse.data('alertaPreciosVencidosMostradaEnApertura')) return;

        if (mostrarAlertaPreciosVencidosPresupuesto()) {
          $collapse.data('alertaPreciosVencidosMostradaEnApertura', true);
        }
      })
      .on('hidden.bs.collapse.presu-alerta-vencidos', collapseSel, function () {
        $(this).removeData('alertaPreciosVencidosMostradaEnApertura');
      });

    $(function () {
      const $collapse = $(collapseSel);
      if ($collapse.length && $collapse.hasClass('show')) {
        window.setTimeout(function () {
          if ($collapse.data('alertaPreciosVencidosMostradaEnApertura')) return;

          if (mostrarAlertaPreciosVencidosPresupuesto()) {
            $collapse.data('alertaPreciosVencidosMostradaEnApertura', true);
          }
        }, 0);
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
    const $card = $(this).closest('.tarea-card');
    const key = obtenerClientKeyTareaPresupuesto($card);
    const files = Array.from(this.files || []);
    if (!idx || !files.length) return;
    const $preview = $('#presu_preview_' + idx);
    if (!$preview.length) return;

    if (!window.fotosNuevasPorTarea[key]) window.fotosNuevasPorTarea[key] = [];
    files.forEach((file, i) => {
      const tempId = 'tmp_' + Date.now() + '_' + i;
      window.fotosNuevasPorTarea[key].push({ tempId, file });
      const url = URL.createObjectURL(file);
      $preview.append(
        `<div class="preview-img-container position-relative d-inline-block m-1" data-temp-id="${tempId}">
           <img src="${url}" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;cursor:pointer;">
           <i class="fa fa-times-circle text-white rounded-circle position-absolute presu-eliminar-imagen"
              style="top:0;right:0;cursor:pointer;font-size:1rem;"></i>
         </div>`
      );
    });
    marcarPresupuestoComoModificadoSilencioso();

    this.value = '';
  });

  // 3) eliminar
  $(document).on('click.presu', '.presu-eliminar-imagen', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const $wrap = $(this).closest('.preview-img-container');
    const $card = $wrap.closest('.tarea-card');
    const key   = obtenerClientKeyTareaPresupuesto($card);
    const nombre = $wrap.data('nombre-archivo'); // existente

    if (nombre) {
      if (!window.fotosEliminadasPorTarea[key]) window.fotosEliminadasPorTarea[key] = [];
      window.fotosEliminadasPorTarea[key].push(nombre);
      $wrap.remove();

      marcarPresupuestoComoModificadoSilencioso();
      return;
    }

    const tempId = $wrap.data('temp-id'); // nueva
    if (tempId && window.fotosNuevasPorTarea[key]) {
      window.fotosNuevasPorTarea[key] = window.fotosNuevasPorTarea[key].filter(x => x.tempId !== tempId);
      $wrap.remove();

      marcarPresupuestoComoModificadoSilencioso();
    }
  });

// === Guardar tarea (VISTA) — SweetAlert con input, único para backend + visita ===
// === Helper: serializa UNA sola tarea-card (mismos selectores que presupuestoGuardar) ===
window._presuSerializarCard = function ($card, nro) {
  const id_presu_tarea = obtenerIdPresuTareaCard($card);
  const client_key = obtenerClientKeyTareaPresupuesto($card);

  // Descripción (prioriza textarea; si no, el título)
  const descTextarea = obtenerDetalleTareaHtml($card);
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

    const id_ptm = $tr.find('.precio-unitario').data('id-ptm') || $tr.attr('data-id-ptm') || null;
    const orden = parseInt($tr.attr('data-orden') || $tr.data('orden') || (materiales.length + 1), 10) || (materiales.length + 1);
    const nombre = ($tr.find('.material-nombre-presupuesto').first().text() || $tr.find('td').eq(0).text() || '').trim();
    const cantidad         = parseFloat($tr.find('.cantidad-material').val()) || 0;
    const precio_unitario  = parseFloat($tr.find('.precio-unitario').val()) || 0;
    const porcentaje_extra = parseFloat($tr.find('.porcentaje-extra').val()) || 0;

    materiales.push({
      id_ptm: id_ptm ? String(id_ptm) : null,
      id_material: String(id_material),
      orden,
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

    const id_ptmo          = $tr.find('.valor-jornal').data('id-ptmo') || $tr.attr('data-id-ptmo') || null;
    const orden            = parseInt($tr.attr('data-orden') || $tr.data('orden') || (mano_obra.length + 1), 10) || (mano_obra.length + 1);
    const nombre           = ($tr.find('.mano-obra-nombre-presupuesto').first().text() || $tr.find('td').eq(0).text() || '').trim();
    const cantidad         = parseFloat($tr.find('.cantidad-mano-obra').val()) || 0;
    const dias             = parseFloat($tr.find('.dias-mano-obra').val()) || 0;
    const jornales         = parseFloat($tr.find('.jornales-mano-obra').val()) || 0;
    const jornal_valor     = parseFloat($tr.find('.valor-jornal').val()) || 0;
    const porcentaje_extra = parseFloat($tr.find('.porcentaje-extra').val()) || 0;
    const observacion      = ($tr.find('.observacion-mano-obra').val() || '').trim();

    mano_obra.push({
      id_ptmo: id_ptmo ? String(id_ptmo) : null,
      jornal_id: String(jornal_id),
      orden,
      nombre,
      cantidad,
      dias,
      jornales,
      jornal_valor,
      porcentaje_extra,
      observacion
    });
  });

  // Otros + utilidades del bloque Mano de Obra
  const otros_mano_obra = parseFloat($card.find('.input-otros-mano').val()) || 0;
  const util_mo_txt     = $card.find('.utilidad-global-mano-obra').val();
  const utilidad_mano_obra = util_mo_txt === '' ? null : (parseFloat(util_mo_txt) || 0);

  // Nota: fotos quedan fuera para tareas archivadas
  return {
    id_presu_tarea,
    client_key,
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

    if (presupuestoEdicionComercialBloqueada()) {
      mostrarBloqueoEdicionComercialPresupuesto();
      return;
    }

    const $btn  = $(this);
    const nro   = $btn.data('nro');
    const $card = $btn.closest('.tarea-card');

    const lanzarPrompt = (onOk) => {
      // nombre por defecto: textarea > título
      const descTextarea = obtenerDetalleTareaTexto($card);
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

  window.registrarIntervencionPresupuestoAccion = async function (opts = {}) {
    const accion = String(opts.accion || '').trim();
    const idPresupuesto = Number(opts.id_presupuesto || $('#contenedorPresupuestoGenerado').data('id_presupuesto')) || 0;
    const idPrevisita = Number(opts.id_previsita || $('#id_previsita').val()) || 0;
    const idUsuario = Number(opts.id_usuario || window.ACTIVE_USER_ID || 0) || 0;

    if (!accion || !idPresupuesto || !idPrevisita) {
      return { ok: false, msg: 'Faltan datos para registrar la intervención del presupuesto.' };
    }

    const resp = await $.ajax({
      url: ENDPOINT,
      method: 'POST',
      dataType: 'json',
      data: {
        via: 'ajax',
        funcion: 'registrarIntervencionPresupuesto',
        id_presupuesto: idPresupuesto,
        id_previsita: idPrevisita,
        accion_intervencion: accion,
        id_usuario: idUsuario
      }
    });

    if (resp?.ok && resp?.intervino && typeof window.actualizarIntervinoPresupuestoUI === 'function') {
      window.actualizarIntervinoPresupuestoUI(resp.intervino);
    }

    return resp;
  };

  window.hookPresupuestoMailEnviado = function (opts = {}) {
    return window.registrarIntervencionPresupuestoAccion({
      ...opts,
      accion: 'enviar_mail'
    });
  };

  // Reemplazo global: ignora la versión vieja de presupuestoGuardar.js
  function aplicarMapeoLineasPresupuestoGuardado(mapeo) {
    if (!mapeo || typeof mapeo !== 'object') return;

    (mapeo.tareas || []).forEach((item, indice) => {
      const idNuevo = item && item.id_presu_tarea ? String(item.id_presu_tarea) : '';
      if (!idNuevo) return;

      const key = item && item.client_key ? String(item.client_key) : '';
      const $cards = $('#contenedorPresupuestoGenerado .tarea-card');
      const $card = key
        ? $cards.filter(function () { return String($(this).attr('data-presu-client-key') || '') === key; }).first()
        : $cards.eq(indice);

      if ($card.length) {
        setIdentidadPresuTareaCard($card, idNuevo, 'pt_' + idNuevo);
      }
    });

    const $materialesDom = $('#contenedorPresupuestoGenerado .precio-unitario');
    (mapeo.materiales || []).forEach((item, indice) => {
      const idAnterior = item && item.id_ptm_anterior ? String(item.id_ptm_anterior) : '';
      const idNuevo = item && item.id_ptm ? String(item.id_ptm) : '';
      if (!idNuevo) return;

      const key = item && item.client_key ? String(item.client_key) : '';
      const indiceLocal = item && item.indice !== undefined ? Number(item.indice) : indice;
      const $scopeMateriales = key
        ? $('#contenedorPresupuestoGenerado .tarea-card').filter(function () {
            return String($(this).attr('data-presu-client-key') || '') === key;
          }).first().find('.precio-unitario')
        : $materialesDom;

      const $input = idAnterior
        ? $scopeMateriales.filter(function () {
            return String($(this).data('id-ptm') || '') === idAnterior;
          }).first()
        : $scopeMateriales.eq(indiceLocal);

      if ($input.length) {
        $input.attr('data-id-ptm', idNuevo).data('id-ptm', idNuevo);
        $input.closest('tr').attr('data-id-ptm', idNuevo).data('id-ptm', idNuevo);
        $input.closest('tr').find('.btn-eliminar-material-presupuesto').attr('data-id-ptm', idNuevo).data('id-ptm', idNuevo);
      }
    });

    const $jornalesDom = $('#contenedorPresupuestoGenerado .valor-jornal');
    (mapeo.mano_obra || []).forEach((item, indice) => {
      const idAnterior = item && item.id_ptmo_anterior ? String(item.id_ptmo_anterior) : '';
      const idNuevo = item && item.id_ptmo ? String(item.id_ptmo) : '';
      if (!idNuevo) return;

      const key = item && item.client_key ? String(item.client_key) : '';
      const indiceLocal = item && item.indice !== undefined ? Number(item.indice) : indice;
      const $scopeJornales = key
        ? $('#contenedorPresupuestoGenerado .tarea-card').filter(function () {
            return String($(this).attr('data-presu-client-key') || '') === key;
          }).first().find('.valor-jornal')
        : $jornalesDom;

      const $input = idAnterior
        ? $scopeJornales.filter(function () {
            return String($(this).data('id-ptmo') || '') === idAnterior;
          }).first()
        : $scopeJornales.eq(indiceLocal);

      if ($input.length) {
        $input.attr('data-id-ptmo', idNuevo).data('id-ptmo', idNuevo);
        $input.closest('tr').attr('data-id-ptmo', idNuevo).data('id-ptmo', idNuevo);
        $input.closest('tr').find('.btn-eliminar-mano-obra-presupuesto').attr('data-id-ptmo', idNuevo).data('id-ptmo', idNuevo);
      }
    });
  }

  window.presupuestoGuardar = async function (idPresuOpcional) {
    try {
      if (presupuestoEdicionComercialBloqueada()) {
        mostrarBloqueoEdicionComercialPresupuesto();
        return;
      }

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
        sincronizarDatosTareaPresupuesto($card, nro);
        const id_presu_tarea = obtenerIdPresuTareaCard($card);
        const client_key = obtenerClientKeyTareaPresupuesto($card);
  
        // descripción igual que tu versión “buena” (textarea o título)
        const descTextarea = obtenerDetalleTareaHtml($card);
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
  
          const id_ptm = $tr.find('.precio-unitario').data('id-ptm') || $tr.attr('data-id-ptm') || null;
          const orden = parseInt($tr.attr('data-orden') || $tr.data('orden') || (materiales.length + 1), 10) || (materiales.length + 1);
          const nombre = ($tr.find('.material-nombre-presupuesto').first().text() || $tr.find('td').eq(0).text() || '').trim();
          const cantidad         = parseFloat($tr.find('.cantidad-material').val()) || 0;
          const precio_unitario  = parseFloat($tr.find('.precio-unitario').val()) || 0;
          const porcentaje_extra = parseFloat($tr.find('.porcentaje-extra').val()) || 0;
  
          materiales.push({
            id_ptm: id_ptm ? String(id_ptm) : null,
            id_material: String(id_material),
            orden,
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
        
          const id_ptmo          = $tr.find('.valor-jornal').data('id-ptmo') || $tr.attr('data-id-ptmo') || null;
          const orden            = parseInt($tr.attr('data-orden') || $tr.data('orden') || (mano_obra.length + 1), 10) || (mano_obra.length + 1);
          const nombre           = ($tr.find('.mano-obra-nombre-presupuesto').first().text() || $tr.find('td').eq(0).text() || '').trim();
          const cantidad         = parseFloat($tr.find('.cantidad-mano-obra').val()) || 0; // operarios
          const dias             = parseFloat($tr.find('.dias-mano-obra').val()) || 0;
          const jornales         = parseFloat($tr.find('.jornales-mano-obra').val()) || 0;
          const jornal_valor     = parseFloat($tr.find('.valor-jornal').val()) || 0;
          const porcentaje_extra = parseFloat($tr.find('.porcentaje-extra').val()) || 0;
          const observacion      = ($tr.find('.observacion-mano-obra').val() || '').trim();
        
          mano_obra.push({
            id_ptmo: id_ptmo ? String(id_ptmo) : null,
            jornal_id: String(jornal_id),
            orden,
            nombre,
            cantidad,        // operarios
            dias,
            jornales,
            jornal_valor,
            porcentaje_extra,
            observacion
          });
        });
         
        // Contadores (buffers reales del dropzone actual)
        const nuevas     = (window.fotosNuevasPorTarea && window.fotosNuevasPorTarea[client_key]) ? window.fotosNuevasPorTarea[client_key] : [];
        const eliminadas = (window.fotosEliminadasPorTarea && window.fotosEliminadasPorTarea[client_key]) ? window.fotosEliminadasPorTarea[client_key] : [];
  
        tareas.push({
          id_presu_tarea,
          client_key,
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
          $('#contenedorPresupuestoGenerado')
            .attr('data-id_presupuesto', String(nuevoId))
            .data('id_presupuesto', Number(nuevoId));
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

        aplicarMapeoLineasPresupuestoGuardado(resp.lineas || null);
        renumerarTareasPresupuesto();

        window.presupuestoDirty = false;
        actualizarEstadoAccionesPresupuestoSilencioso();

        if (resp?.intervino && typeof window.actualizarIntervinoPresupuestoUI === 'function') {
          window.actualizarIntervinoPresupuestoUI(resp.intervino);
        }
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
      actualizarEstadoAccionesPresupuestoSilencioso();
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

  if (!window.__presuTitleBound) {
    window.__presuTitleBound = true;

    $(document)
      .off('input.presutitle change.presutitle', '#contenedorPresupuestoGenerado .tarea-card textarea')
      .on('input.presutitle change.presutitle', '#contenedorPresupuestoGenerado .tarea-card textarea', function () {
        syncTituloCardPresupuesto($(this).closest('.tarea-card'));
      });
  }

        // 2) Barrido inicial: recalcula TODO lo ya pintado
        $root.find('.tarea-card').each(function () {
          const $card = $(this);

          syncTituloCardPresupuesto($card);

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

  if (typeof window.setDetalleTareaEditorValue === 'function') {
    window.setDetalleTareaEditorValue($card, nuevaDescripcion, { triggerInput: true });
  } else {
    let $txt = $card.find('textarea.tarea-descripcion').first();
    if (!$txt.length) $txt = $card.find('textarea').first();
    if ($txt.length) $txt.val(nuevaDescripcion).trigger('input');
  }

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

  (tareaPlantilla.materiales || []).forEach((m, indice) => {
    insertarFilaMaterialPresupuesto($card, {
      id_ptm: null,
      id_material: (m.id_material != null ? m.id_material : ''),
      orden: indice + 1,
      nombre: (m.nombre || ''),
      cantidad: (m.cantidad != null ? m.cantidad : 0),
      precio_unitario: (m.precio_unitario != null ? m.precio_unitario : 0),
      porcentaje_extra: (m.porcentaje_extra != null ? m.porcentaje_extra : 0),
      fecha_actualizacion: m.fecha_actualizacion || m.log_edicion || m.log_alta || '',
      log_alta: m.log_alta || '',
      log_edicion: m.log_edicion || ''
    });
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

  (tareaPlantilla.mano_obra || []).forEach((o, indice) => {
    insertarFilaManoObraPresupuesto($card, {
      id_ptmo: null,
      jornal_id: (o.jornal_id != null ? o.jornal_id : ''),
      orden: indice + 1,
      nombre: (o.nombre || o.nombre_jornal || ''),
      cantidad: (o.cantidad != null ? o.cantidad : 0),
      dias: (o.dias != null ? o.dias : 1),
      jornal_valor: (o.jornal_valor != null ? o.jornal_valor : (o.valor_jornal_usado != null ? o.valor_jornal_usado : 0)),
      porcentaje_extra: (o.porcentaje_extra != null ? o.porcentaje_extra : 0),
      observacion: (o.observacion != null ? o.observacion : ''),
      fecha_actualizacion: o.fecha_actualizacion || o.updated_at_origen || o.updated_at || ''
    });
  });

  if ($moOtros && $moOtros.length) $tbMo.append($moOtros);
  if ($moSubtotal && $moSubtotal.length) $tbMo.append($moSubtotal);

  // 6) Recalcular con tus funciones existentes
  if (typeof window.initRecalculoPresupuestoCargado === 'function') {
    window.initRecalculoPresupuestoCargado();
  }

  // Si tus helpers existen, mejor usar esos (como ya venías haciendo)
  if (typeof _safeActualizarSubtotalesBloque === 'function') {
    _safeActualizarSubtotalesBloque($card, $card[0]);
  }
  if (typeof _safeActualizarTotalesPorTarea === 'function') {
    _safeActualizarTotalesPorTarea($card, $card[0]);
  }
  if (typeof _safeActualizarTotalGeneral === 'function') {
    _safeActualizarTotalGeneral();
  }

  if (typeof window.marcarPresupuestoComoModificadoSilencioso === 'function') {
    window.marcarPresupuestoComoModificadoSilencioso();
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
