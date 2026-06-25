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

    if (presupuestoEdicionComercialBloqueada()) {
      $btnGuardar.prop('disabled', true).addClass('btn-secondary').removeClass('btn-success');
      $btnEmitir.prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
      return;
    }

    if (hayVencidos) {
      $btnGuardar.prop('disabled', true).addClass('btn-secondary').removeClass('btn-success');
      $btnEmitir.prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
      return;
    }

    if (presupuestoDirty) {
      $btnGuardar.prop('disabled', false).addClass('btn-success').removeClass('btn-secondary');
      $btnEmitir.prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
      return;
    }

    $btnGuardar.prop('disabled', true).addClass('btn-secondary').removeClass('btn-success');
    $btnEmitir
      .prop('disabled', !idPresupuesto)
      .toggleClass('btn-primary', !!idPresupuesto)
      .toggleClass('btn-secondary', !idPresupuesto);
  }

  function marcarPresupuestoComoModificadoSilencioso() {
    if (!$('#contenedorPresupuestoGenerado').length) return;

    window.presupuestoDirty = true;
    actualizarEstadoAccionesPresupuestoSilencioso();
  }

  // limpieza por namespace
  $(document)
    .off('click.presu',  '.presu-dropzone')
    .off('change.presu', '.presu-fotos')
    .off('click.presu',  '.presu-eliminar-imagen')
    .off('click.presu', '#btn-guardar-presupuesto')
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

            if (!esConfirmacionPrecioVencido && typeof window.marcarPresupuestoComoModificado === 'function') {
              window.marcarPresupuestoComoModificado();
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

            if (typeof window.marcarPresupuestoComoModificado === 'function') {
              window.marcarPresupuestoComoModificado();
            }

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

            if (typeof window.marcarPresupuestoComoModificado === 'function') {
              window.marcarPresupuestoComoModificado();
            }

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
    const precioSel = `${rootSel} .precio-unitario.bg-danger, ${rootSel} .valor-jornal.bg-danger`;
    const ENDPOINT_CONFIRMACION = '../03-controller/presupuestos_guardar.php';
    const MENSAJE_ERROR_CONFIRMACION = 'No se pudo confirmar la vigencia del precio.';

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

    function normalizarImporteTextoConfirmacionPHP(valor) {
      const texto = String(valor ?? '').trim();
      const partes = texto.match(/^(\d+)([\.,])(\d+)$/);

      if (!partes || partes[3].length <= 2) {
        return texto;
      }

      const decimalesSinCeros = partes[3].replace(/0+$/g, '');
      if (decimalesSinCeros.length > 2) {
        return texto;
      }

      const decimales = decimalesSinCeros.padEnd(2, '0');
      return `${partes[1]}${partes[2]}${decimales}`;
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

    function obtenerPayloadConfirmacion($input) {
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
        funcion: 'confirmarPrecioPresupuesto',
        tipo,
        id_presupuesto: idPresupuesto,
        id_linea: idLinea,
        id_catalogo: idCatalogo,
        importe: normalizarImporteTextoConfirmacionPHP($input.val())
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

    function confirmarPrecioPresupuestoPHP(input) {
      const $input = $(input);

      if (!$input.length || !$input.hasClass('bg-danger')) return;
      if ($input.data('confirmacionPrecioPresupuestoPendiente')) return;
      if (!$input.data('confirmacionPrecioPresupuestoIntervenido')) return;

      if (presupuestoEdicionBloqueadaParaConfirmar()) {
        mostrarBloqueoEdicionComercialPresupuesto();
        return;
      }

      const payload = obtenerPayloadConfirmacion($input);
      if (!payloadConfirmacionCompleto(payload)) {
        mostrarErrorConfirmacionPrecio(MENSAJE_ERROR_CONFIRMACION);
        return;
      }

      const dirtyAntes = $input.data('presupuestoDirtyAntesConfirmacion') === undefined
        ? !!window.presupuestoDirty
        : !!$input.data('presupuestoDirtyAntesConfirmacion');
      $input
        .data('confirmacionPrecioPresupuestoPendiente', true)
        .data('confirmacionPrecioPresupuestoIntervenido', false)
        .data('presupuestoDirtyAntesConfirmacion', dirtyAntes)
        .prop('readonly', true);

      $.ajax({
        url: ENDPOINT_CONFIRMACION,
        method: 'POST',
        dataType: 'json',
        data: payload
      })
      .done(function (respuesta) {
        if (!respuesta || respuesta.ok !== true) {
          $input.prop('readonly', false);
          mostrarErrorConfirmacionPrecio(respuesta && respuesta.mensaje);
          revalidarDatosVencidosPresupuestoPHP();
          return;
        }

        $input
          .val(String(respuesta.importe_persistido ?? payload.importe))
          .attr('data-fecha-actualizacion', String(respuesta.fecha_actualizacion ?? ''))
          .data('fecha-actualizacion', String(respuesta.fecha_actualizacion ?? ''))
          .removeClass('bg-danger')
          .addClass('bg-success')
          .prop('readonly', true)
          .removeData('confirmacionPrecioPresupuestoPendiente')
          .removeData('confirmacionPrecioPresupuestoIntervenido');

        recalcularDespuesDeConfirmacion($input, respuesta);

        if ($input.data('presupuestoDirtyAntesConfirmacion') === false) {
          window.presupuestoDirty = false;
        }

        revalidarDatosVencidosPresupuestoPHP();
      })
      .fail(function (xhr) {
        let mensaje = MENSAJE_ERROR_CONFIRMACION;
        const json = xhr && xhr.responseJSON ? xhr.responseJSON : null;

        if (json && typeof json.mensaje === 'string' && json.mensaje.trim()) {
          mensaje = json.mensaje.trim();
        } else if (xhr && typeof xhr.responseText === 'string' && xhr.responseText.trim()) {
          try {
            const parsed = JSON.parse(xhr.responseText);
            if (parsed && typeof parsed.mensaje === 'string' && parsed.mensaje.trim()) {
              mensaje = parsed.mensaje.trim();
            }
          } catch (err) {
            mensaje = MENSAJE_ERROR_CONFIRMACION;
          }
        }

        $input.prop('readonly', false);
        mostrarErrorConfirmacionPrecio(mensaje);
        revalidarDatosVencidosPresupuestoPHP();
      })
      .always(function () {
        $input.removeData('confirmacionPrecioPresupuestoPendiente');
      });
    }

    $(document)
      .off('focusin.presu-confirmar-precio input.presu-confirmar-precio blur.presu-confirmar-precio keydown.presu-confirmar-precio', precioSel)
      .on('focusin.presu-confirmar-precio', precioSel, function () {
        $(this).data('presupuestoDirtyAntesConfirmacion', !!window.presupuestoDirty);
      })
      .on('input.presu-confirmar-precio', precioSel, function () {
        if ($(this).data('confirmacionPrecioPresupuestoPendiente')) return;
        $(this).data('confirmacionPrecioPresupuestoIntervenido', true);
      })
      .on('blur.presu-confirmar-precio', precioSel, function () {
        confirmarPrecioPresupuestoPHP(this);
      })
      .on('keydown.presu-confirmar-precio', precioSel, function (e) {
        if (e.key !== 'Enter') return;

        e.preventDefault();
        e.stopPropagation();
        confirmarPrecioPresupuestoPHP(this);
      });

    $(function () {
      if ($(rootSel).length) {
        revalidarDatosVencidosPresupuestoPHP();
      }
    });
  })();

  (function bindAlertaPreciosVencidosAperturaPresupuestoPHP() {
    const collapseSel = '#collapsePresupuesto';
    const preciosVencidosSel = '#contenedorPresupuestoGenerado .precio-unitario.bg-danger, #contenedorPresupuestoGenerado .valor-jornal.bg-danger';

    function hayPreciosVencidosPresupuesto() {
      return $(preciosVencidosSel).length > 0;
    }

    function mostrarAlertaPreciosVencidosPresupuesto() {
      if (!hayPreciosVencidosPresupuesto()) return;

      const alertDanger = [
        false,
        '<H3><strong>VALORES DESACTUALIZADOS</H3>',
        'Los campos en color rojo presentan precios desactualizados, para poder guardar el presupuesto deberá actualizar los valores.',
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
        return;
      }

      if (window.Swal && typeof Swal.fire === 'function') {
        Swal.fire({
          icon: 'warning',
          title: 'VALORES DESACTUALIZADOS',
          text: 'Los campos en color rojo presentan precios desactualizados, para poder guardar el presupuesto deberá actualizar los valores.',
          confirmButtonText: 'OK'
        });
      }
    }

    $(document)
      .off('shown.bs.collapse.presu-alerta-vencidos hidden.bs.collapse.presu-alerta-vencidos', collapseSel)
      .on('shown.bs.collapse.presu-alerta-vencidos', collapseSel, function () {
        const $collapse = $(this);
        if ($collapse.data('alertaPreciosVencidosMostradaEnApertura')) return;

        $collapse.data('alertaPreciosVencidosMostradaEnApertura', true);
        mostrarAlertaPreciosVencidosPresupuesto();
      })
      .on('hidden.bs.collapse.presu-alerta-vencidos', collapseSel, function () {
        $(this).removeData('alertaPreciosVencidosMostradaEnApertura');
      });

    $(function () {
      const $collapse = $(collapseSel);
      if ($collapse.length && $collapse.hasClass('show')) {
        window.setTimeout(function () {
          if ($collapse.data('alertaPreciosVencidosMostradaEnApertura')) return;

          $collapse.data('alertaPreciosVencidosMostradaEnApertura', true);
          mostrarAlertaPreciosVencidosPresupuesto();
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
    if (typeof window.marcarPresupuestoComoModificado === 'function') {
      window.marcarPresupuestoComoModificado();
    }

    this.value = '';
  });

  // 3) eliminar
  $(document).on('click.presu', '.presu-eliminar-imagen', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const $wrap = $(this).closest('.preview-img-container');
    const idx   = $wrap.closest('.presu-dropzone').data('index');
    const nombre = $wrap.data('nombre-archivo'); // existente

    if (nombre) {
      if (!window.fotosEliminadasPorTarea[idx]) window.fotosEliminadasPorTarea[idx] = [];
      window.fotosEliminadasPorTarea[idx].push(nombre);
      $wrap.remove();

      if (typeof window.marcarPresupuestoComoModificado === 'function') {
        window.marcarPresupuestoComoModificado();
      }
      return;
    }

    const tempId = $wrap.data('temp-id'); // nueva
    if (tempId && window.fotosNuevasPorTarea[idx]) {
      window.fotosNuevasPorTarea[idx] = window.fotosNuevasPorTarea[idx].filter(x => x.tempId !== tempId);
      $wrap.remove();

      if (typeof window.marcarPresupuestoComoModificado === 'function') {
        window.marcarPresupuestoComoModificado();
      }
    }
  });

// === Guardar tarea (VISTA) — SweetAlert con input, único para backend + visita ===
// === Helper: serializa UNA sola tarea-card (mismos selectores que presupuestoGuardar) ===
window._presuSerializarCard = function ($card, nro) {
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

    (mapeo.materiales || []).forEach((item) => {
      const idAnterior = item && item.id_ptm_anterior ? String(item.id_ptm_anterior) : '';
      const idNuevo = item && item.id_ptm ? String(item.id_ptm) : '';
      if (!idAnterior || !idNuevo) return;

      const $input = $('#contenedorPresupuestoGenerado .precio-unitario').filter(function () {
        return String($(this).data('id-ptm') || '') === idAnterior;
      }).first();

      if ($input.length) {
        $input.attr('data-id-ptm', idNuevo).data('id-ptm', idNuevo);
      }
    });

    (mapeo.mano_obra || []).forEach((item) => {
      const idAnterior = item && item.id_ptmo_anterior ? String(item.id_ptmo_anterior) : '';
      const idNuevo = item && item.id_ptmo ? String(item.id_ptmo) : '';
      if (!idAnterior || !idNuevo) return;

      const $input = $('#contenedorPresupuestoGenerado .valor-jornal').filter(function () {
        return String($(this).data('id-ptmo') || '') === idAnterior;
      }).first();

      if ($input.length) {
        $input.attr('data-id-ptmo', idNuevo).data('id-ptmo', idNuevo);
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
  
          const id_ptm          = $tr.find('.precio-unitario').data('id-ptm') || null;
          const nombre           = ($tr.find('td').eq(0).text() || '').trim();
          const cantidad         = parseFloat($tr.find('.cantidad-material').val()) || 0;
          const precio_unitario  = parseFloat($tr.find('.precio-unitario').val()) || 0;
          const porcentaje_extra = parseFloat($tr.find('.porcentaje-extra').val()) || 0;
  
          materiales.push({
            id_ptm: id_ptm ? String(id_ptm) : null,
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
        
          const id_ptmo          = $tr.find('.valor-jornal').data('id-ptmo') || null;
          const nombre           = ($tr.find('td').eq(0).text() || '').trim();
          const cantidad         = parseFloat($tr.find('.cantidad-mano-obra').val()) || 0; // operarios
          const dias             = parseFloat($tr.find('.dias-mano-obra').val()) || 0;
          const jornales         = parseFloat($tr.find('.jornales-mano-obra').val()) || 0;
          const jornal_valor     = parseFloat($tr.find('.valor-jornal').val()) || 0;
          const porcentaje_extra = parseFloat($tr.find('.porcentaje-extra').val()) || 0;
        
          mano_obra.push({
            id_ptmo: id_ptmo ? String(id_ptmo) : null,
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

        if (typeof window.marcarPresupuestoComoGuardado === 'function') {
          window.marcarPresupuestoComoGuardado();
        }

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
      if (!presupuestoEdicionComercialBloqueada()) {
        $('#btn-guardar-presupuesto').prop('disabled', false);
      }
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
  if (typeof _safeActualizarSubtotalesBloque === 'function') {
    _safeActualizarSubtotalesBloque($card, $card[0]);
  }
  if (typeof _safeActualizarTotalesPorTarea === 'function') {
    _safeActualizarTotalesPorTarea($card, $card[0]);
  }
  if (typeof _safeActualizarTotalGeneral === 'function') {
    _safeActualizarTotalGeneral();
  }

  if (typeof window.marcarPresupuestoComoModificado === 'function') {
    window.marcarPresupuestoComoModificado();
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
