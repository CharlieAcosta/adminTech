(function () {
  'use strict';

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function extFromName(name) {
    var clean = String(name || '').trim();
    var parts = clean.split('.');
    return parts.length > 1 ? String(parts.pop() || '').toLowerCase() : '';
  }

  function iconClassForExt(ext) {
    switch (String(ext || '').toLowerCase()) {
      case 'pdf':
        return 'fas fa-file-pdf text-danger';
      case 'doc':
      case 'docx':
        return 'fas fa-file-word text-primary';
      case 'xls':
      case 'xlsx':
      case 'csv':
        return 'fas fa-file-excel text-success';
      case 'jpg':
      case 'jpeg':
      case 'png':
        return 'fas fa-file-image text-info';
      case 'txt':
        return 'fas fa-file-alt text-secondary';
      default:
        return 'fas fa-file text-muted';
    }
  }

  function humanSize(bytes) {
    var value = Number(bytes || 0);
    if (!value || value < 0) {
      return '';
    }

    var units = ['B', 'KB', 'MB', 'GB'];
    var idx = 0;
    while (value >= 1024 && idx < units.length - 1) {
      value = value / 1024;
      idx += 1;
    }

    var decimals = idx === 0 ? 0 : (value >= 10 ? 1 : 2);
    return value.toLocaleString('es-AR', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    }) + ' ' + units[idx];
  }

  function makeSavedKey(doc) {
    if (Number(doc.id_documento_previsita || 0) > 0) {
      return 'saved:' + Number(doc.id_documento_previsita || 0);
    }
    return 'saved-route:' + String(doc.ruta_archivo || '');
  }

  function makeTempKey(item) {
    return 'temp:' + String(item.tempId || '');
  }

  function normalizeSavedDoc(raw) {
    var doc = raw && typeof raw === 'object' ? raw : {};
    var nombreVisual = String(doc.nombre_visual || doc.nombre_archivo_original || doc.nombre_archivo || '').trim();
    var extension = String(doc.extension || extFromName(nombreVisual || doc.ruta_archivo || '')).toLowerCase();

    return {
      id_documento_previsita: Number(doc.id_documento_previsita || 0) || 0,
      ruta_archivo: String(doc.ruta_archivo || '').trim(),
      url_publica: String(doc.url_publica || '').trim(),
      nombre_visual: nombreVisual,
      extension: extension,
      tamano_bytes: Number(doc.tamano_bytes || 0) || 0,
      tamano_texto: String(doc.tamano_texto || '').trim(),
      archivo_disponible: doc.archivo_disponible === true,
      es_legacy: doc.es_legacy === true
    };
  }

  function createObjectUrl(item) {
    if (!item || !(item.file instanceof File)) {
      return '';
    }
    if (!item.objectUrl) {
      item.objectUrl = URL.createObjectURL(item.file);
    }
    return item.objectUrl;
  }

  function revokeObjectUrl(item) {
    if (item && item.objectUrl) {
      URL.revokeObjectURL(item.objectUrl);
      item.objectUrl = '';
    }
  }

  function showError(message) {
    if (window.Swal && typeof window.Swal.fire === 'function') {
      window.Swal.fire({
        icon: 'error',
        title: 'Archivo no permitido',
        html: message,
        confirmButtonText: 'OK'
      });
      return;
    }

    window.alert(String(message || '').replace(/<br>/g, '\n'));
  }

  function triggerDownload(url, filename) {
    var link = document.createElement('a');
    link.href = url;
    link.download = filename || 'documento';
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    link.remove();
  }

  function initPrevisitaDocumentos() {
    var panel = document.getElementById('previsitaDocumentosPanel');
    var input = document.getElementById('doc_previsita');
    var dropzone = document.getElementById('previsitaDocumentosDropzone');
    var grid = document.getElementById('previsitaDocumentosGrid');
    var summary = document.getElementById('previsitaDocumentosResumen');
    var hiddenDeleted = document.getElementById('previsita_documentos_eliminados');

    if (!panel || !input || !dropzone || !grid || !summary || !hiddenDeleted) {
      return null;
    }

    var readonly = panel.getAttribute('data-readonly') === '1';
    var initialDocs = [];
    try {
      initialDocs = JSON.parse(panel.getAttribute('data-documentos-iniciales') || '[]');
    } catch (e) {
      initialDocs = [];
    }

    var state = {
      readonly: readonly,
      saved: Array.isArray(initialDocs) ? initialDocs.map(normalizeSavedDoc) : [],
      queued: [],
      deleted: [],
      tempSeq: 0
    };

    function syncDeletedInput() {
      hiddenDeleted.value = JSON.stringify(state.deleted.map(function (doc) {
        return {
          id_documento_previsita: Number(doc.id_documento_previsita || 0) || 0,
          ruta_archivo: String(doc.ruta_archivo || ''),
          es_legacy: doc.es_legacy === true
        };
      }));
    }

    function hasDeletedDoc(doc) {
      var key = makeSavedKey(doc);
      return state.deleted.some(function (item) {
        return makeSavedKey(item) === key;
      });
    }

    function currentSavedDocs() {
      return state.saved.filter(function (doc) {
        return !hasDeletedDoc(doc);
      });
    }

    function buildCardHtml(opts) {
      var doc = opts.doc;
      var saved = opts.saved === true;
      var removable = opts.removable === true;
      var ext = String(doc.extension || extFromName(doc.nombre_visual || doc.fileName || '')).toUpperCase();
      var sizeText = String(doc.tamano_texto || '');
      var metaParts = [];

      if (ext) {
        metaParts.push('<span class="previsita-documento-badge">' + escapeHtml(ext) + '</span>');
      }
      if (sizeText) {
        metaParts.push('<span>' + escapeHtml(sizeText) + '</span>');
      }
      metaParts.push('<span>' + (saved ? 'Guardado' : 'Pendiente') + '</span>');
      if (saved && doc.archivo_disponible === false) {
        metaParts.push('<span class="text-danger">No disponible</span>');
      }

      return '' +
        '<div class="previsita-documento-slot" data-doc-key="' + escapeHtml(opts.key) + '" data-doc-type="' + (saved ? 'saved' : 'queued') + '">' +
          '<div class="previsita-documento-card">' +
            (removable ? '<button type="button" class="previsita-documento-remove" data-action="remove" title="Quitar documento"><i class="fas fa-times"></i></button>' : '') +
            '<button type="button" class="previsita-documento-open" data-action="open">' +
              '<span class="previsita-documento-icon ' + iconClassForExt(doc.extension) + '"></span>' +
              '<span class="previsita-documento-body">' +
                '<span class="previsita-documento-nombre">' + escapeHtml(doc.nombre_visual || doc.fileName || 'Documento') + '</span>' +
                '<span class="previsita-documento-meta">' + metaParts.join(' · ') + '</span>' +
              '</span>' +
            '</button>' +
            '<div class="previsita-documento-actions">' +
              '<button type="button" class="btn btn-sm btn-outline-secondary" data-action="download">Descargar</button>' +
              '<span class="small text-muted previsita-documento-estado">' + (saved ? 'Adjunto' : 'Pendiente') + '</span>' +
            '</div>' +
          '</div>' +
        '</div>';
    }

    function render() {
      var visibleSaved = currentSavedDocs();
      var html = '';

      visibleSaved.forEach(function (doc) {
        html += buildCardHtml({
          key: makeSavedKey(doc),
          doc: doc,
          saved: true,
          removable: !state.readonly
        });
      });

      state.queued.forEach(function (item) {
        var file = item.file;
        html += buildCardHtml({
          key: makeTempKey(item),
          doc: {
            nombre_visual: String(file.name || '').trim(),
            fileName: String(file.name || '').trim(),
            extension: extFromName(file.name),
            tamano_texto: humanSize(file.size),
            archivo_disponible: true
          },
          saved: false,
          removable: !state.readonly
        });
      });

      if (!html) {
        html = '<div class="col-12"><div class="previsita-documentos-empty">No hay archivos adjuntos en esta pre-visita.</div></div>';
      }

      grid.innerHTML = html;

      var total = visibleSaved.length + state.queued.length;
      if (state.readonly) {
        summary.textContent = total === 0 ? 'Sin adjuntos.' : (total === 1 ? '1 adjunto.' : total + ' adjuntos.');
        dropzone.classList.add('d-none');
      } else if (total === 0) {
        summary.textContent = 'Todavia no hay archivos seleccionados.';
      } else if (state.queued.length > 0) {
        summary.textContent = total + ' archivo(s) listos. ' + state.queued.length + ' pendiente(s) de guardar.';
      } else {
        summary.textContent = total + ' archivo(s) guardados.';
      }

      syncDeletedInput();
    }

    function addFiles(fileList) {
      if (state.readonly || !fileList || !fileList.length) {
        return;
      }

      var allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png'];
      var errors = [];

      Array.from(fileList).forEach(function (file) {
        var ext = extFromName(file.name);
        if (!ext || allowedExt.indexOf(ext) === -1) {
          errors.push('"' + escapeHtml(file.name) + '" no tiene un formato permitido.');
          return;
        }

        if (Number(file.size || 0) > 5 * 1024 * 1024) {
          errors.push('"' + escapeHtml(file.name) + '" supera el maximo de 5 MB.');
          return;
        }

        var duplicate = state.queued.some(function (item) {
          return item.file && item.file.name === file.name && item.file.size === file.size && item.file.lastModified === file.lastModified;
        });
        if (duplicate) {
          return;
        }

        state.tempSeq += 1;
        state.queued.push({
          tempId: 'doc_' + Date.now() + '_' + state.tempSeq,
          file: file,
          objectUrl: ''
        });
      });

      input.value = '';
      render();

      if (errors.length) {
        showError(errors.join('<br>'));
      }
    }

    function openSavedDoc(doc) {
      if (!doc || !doc.url_publica || doc.archivo_disponible === false) {
        showError('El archivo seleccionado no esta disponible para abrir.');
        return;
      }

      window.open(doc.url_publica, '_blank', 'noopener');
    }

    function openQueuedDoc(item) {
      var url = createObjectUrl(item);
      if (!url) {
        showError('No se pudo abrir el archivo seleccionado.');
        return;
      }

      window.open(url, '_blank', 'noopener');
    }

    function downloadSavedDoc(doc) {
      if (!doc || !doc.url_publica || doc.archivo_disponible === false) {
        showError('El archivo seleccionado no esta disponible para descargar.');
        return;
      }

      triggerDownload(doc.url_publica, doc.nombre_visual || 'documento');
    }

    function downloadQueuedDoc(item) {
      var url = createObjectUrl(item);
      if (!url) {
        showError('No se pudo descargar el archivo seleccionado.');
        return;
      }

      triggerDownload(url, item.file && item.file.name ? item.file.name : 'documento');
    }

    function removeSavedDoc(key) {
      var doc = state.saved.find(function (item) {
        return makeSavedKey(item) === key;
      });
      if (!doc || hasDeletedDoc(doc)) {
        return;
      }

      state.deleted.push(doc);
      render();
    }

    function removeQueuedDoc(key) {
      state.queued = state.queued.filter(function (item) {
        if (makeTempKey(item) === key) {
          revokeObjectUrl(item);
          return false;
        }
        return true;
      });
      render();
    }

    if (!state.readonly) {
      dropzone.addEventListener('click', function (event) {
        if (event.target.closest('[data-action]')) {
          return;
        }
        input.click();
      });

      dropzone.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          input.click();
        }
      });

      ['dragenter', 'dragover'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (event) {
          event.preventDefault();
          event.stopPropagation();
          dropzone.classList.add('is-dragover');
        });
      });

      ['dragleave', 'dragend'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (event) {
          event.preventDefault();
          event.stopPropagation();
          dropzone.classList.remove('is-dragover');
        });
      });

      dropzone.addEventListener('drop', function (event) {
        event.preventDefault();
        event.stopPropagation();
        dropzone.classList.remove('is-dragover');
        addFiles(event.dataTransfer ? event.dataTransfer.files : []);
      });

      input.addEventListener('change', function () {
        addFiles(input.files || []);
      });
    }

    grid.addEventListener('click', function (event) {
      var actionTarget = event.target.closest('[data-action]');
      if (!actionTarget) {
        return;
      }

      var slot = actionTarget.closest('.previsita-documento-slot');
      if (!slot) {
        return;
      }

      var key = slot.getAttribute('data-doc-key') || '';
      var type = slot.getAttribute('data-doc-type') || '';
      var action = actionTarget.getAttribute('data-action') || '';

      if (type === 'saved') {
        var savedDoc = state.saved.find(function (item) {
          return makeSavedKey(item) === key;
        });
        if (!savedDoc) {
          return;
        }

        if (action === 'open') {
          openSavedDoc(savedDoc);
        } else if (action === 'download') {
          downloadSavedDoc(savedDoc);
        } else if (action === 'remove' && !state.readonly) {
          removeSavedDoc(key);
        }
        return;
      }

      var queuedDoc = state.queued.find(function (item) {
        return makeTempKey(item) === key;
      });
      if (!queuedDoc) {
        return;
      }

      if (action === 'open') {
        openQueuedDoc(queuedDoc);
      } else if (action === 'download') {
        downloadQueuedDoc(queuedDoc);
      } else if (action === 'remove' && !state.readonly) {
        removeQueuedDoc(key);
      }
    });

    render();

    return {
      hasPendingChanges: function () {
        return state.queued.length > 0 || state.deleted.length > 0;
      },

      appendToFormData: function (formData) {
        if (!(formData instanceof FormData)) {
          return;
        }

        state.queued.forEach(function (item) {
          if (item.file instanceof File) {
            formData.append('doc_previsita[]', item.file, item.file.name);
          }
        });

        formData.set('previsita_documentos_eliminados', hiddenDeleted.value || '[]');
      },

      setPersistedDocuments: function (docs) {
        state.saved = Array.isArray(docs) ? docs.map(normalizeSavedDoc) : [];
        state.deleted = [];
        state.queued.forEach(revokeObjectUrl);
        state.queued = [];
        input.value = '';
        render();
      },

      clearAll: function () {
        state.saved = [];
        state.deleted = [];
        state.queued.forEach(revokeObjectUrl);
        state.queued = [];
        input.value = '';
        render();
      }
    };
  }

  document.addEventListener('DOMContentLoaded', function () {
    window.previsitaDocumentosManager = initPrevisitaDocumentos();
  });
})();
