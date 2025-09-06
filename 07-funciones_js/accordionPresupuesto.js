/* =======================================================
 * archivo: accordionPresupuesto.js
 * propósito: manejo de fotos por TAREA de PRESUPUESTO
 * estado: UI + memoria (sin persistencia aún)
 * ======================================================= */

(function ($) {
    'use strict';
  
    // ---- Estado en memoria ----
    const imagenesPorTareaPresu = {};   // { [numTarea]: Array<{file:File|null, nombre:string, src?:string}> }
    const fotosEliminadasPresu  = {};   // { [numTarea]: Array<string> }
  
    // ---- Utils ----
    function limpiarPlaceholderFotos($zone) {
        $zone.find('em').remove();
        $zone.removeClass('text-muted');
      }     

    function numeroTareaDesdeCard($card) {
      // 1) Buscar un botón/elemento con id "subt-tarea-N"
      const $btn = $card.find('[id^="subt-tarea-"]').first();
      if ($btn.length) {
        const n = parseInt(String($btn.attr('id')).replace('subt-tarea-', ''), 10);
        if (!Number.isNaN(n)) return n;
      }
      // 2) Fallback: índice visual dentro del contenedor
      const idx = $('#contenedorPresupuestoGenerado .tarea-card').index($card) + 1;
      return idx > 0 ? idx : null;
    }
  
    function ensureInputArchivo($zone, numeroTarea) {
      let $input = $zone.siblings('input.presu-fotos-input[data-numero]').filter(`[data-numero="${numeroTarea}"]`);
      if (!$input.length) {
        $input = $(`
          <input type="file"
                 class="d-none presu-fotos-input"
                 data-numero="${numeroTarea}"
                 accept="image/*"
                 multiple />
        `);
        // Lo insertamos justo después del contenedor de previews
        $zone.after($input);
      }
      return $input;
    }
  
    function renderThumb($zone, numeroTarea, imgSrc, nombre) {
        const $thumb = $(`
        <div class="preview-img-container position-relative d-inline-block m-1" data-nombre-archivo="${nombre}">
            <img src="${imgSrc}" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;cursor:pointer;" alt="${nombre}">
            <i class="fa fa-times-circle text-white rounded-circle position-absolute eliminar-imagen"
            title="Eliminar"
            style="top:0;right:0;cursor:pointer;font-size:1rem;"></i>
        </div>
        `);
    
        // ampliar
        $thumb.find('img').on('click', () => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
            imageUrl: imgSrc,
            imageAlt: nombre,
            showConfirmButton: false,
            background: '#fff',
            backdrop: 'rgba(0,0,0,0.7)',
            width: 'auto',
            padding: '1rem',
            showCloseButton: true,
            customClass: { popup: 'shadow-lg rounded' }
            });
        }
        });
    
        // eliminar
        $thumb.find('.eliminar-imagen').on('click', () => {
        // inicializar arreglos
        imagenesPorTareaPresu[numeroTarea] = imagenesPorTareaPresu[numeroTarea] || [];
        fotosEliminadasPresu[numeroTarea]  = fotosEliminadasPresu[numeroTarea]  || [];
    
        // si era una imagen "existente" (sin File), marcamos nombre para eliminar
        const item = imagenesPorTareaPresu[numeroTarea].find(x => x.nombre === nombre);
        if (item && item.file === null && !fotosEliminadasPresu[numeroTarea].includes(nombre)) {
            fotosEliminadasPresu[numeroTarea].push(nombre);
        }
        // sacamos del arreglo principal
        imagenesPorTareaPresu[numeroTarea] = imagenesPorTareaPresu[numeroTarea].filter(x => x.nombre !== nombre);
    
        // quitamos de la UI
        $thumb.remove();
        });
    
        // ——————————————————————————————
        // FIX: no vaciar el contenedor. Solo:
        //   - sacar el placeholder <em> si existe
        //   - quitar la clase text-muted
        //   - APPEND (no replace)
        // ——————————————————————————————
        limpiarPlaceholderFotos($zone);
        $zone.append($thumb);          // <<— antes estaba .empty().append(...)
    }
  
    function agregarArchivos($zone, numeroTarea, files) {
      if (!files || !files.length) return;
      imagenesPorTareaPresu[numeroTarea] = imagenesPorTareaPresu[numeroTarea] || [];
      fotosEliminadasPresu[numeroTarea]  = fotosEliminadasPresu[numeroTarea]  || [];
  
      Array.from(files).forEach(file => {
        const reader = new FileReader();
        reader.onload = (ev) => {
          const src = ev.target.result;
          imagenesPorTareaPresu[numeroTarea].push({ file, nombre: file.name, src });
          renderThumb($zone, numeroTarea, src, file.name);
        };
        reader.readAsDataURL(file);
      });
    }
  
    // ---- Delegados de UI ----
    $(document).on('click', '.tarea-card .preview-fotos', function (e) {
      // si clickea sobre una miniatura, dejamos que el otro handler actúe
      if ($(e.target).is('img, .eliminar-imagen')) return;
      const $zone = $(this);
      const $card = $zone.closest('.tarea-card');
      const numero = numeroTareaDesdeCard($card);
      if (!numero) return;
  
      const $input = ensureInputArchivo($zone, numero);
      $input.trigger('click');
    });
  
    $(document).on('change', 'input.presu-fotos-input', function () {
      const numero = parseInt($(this).data('numero'), 10);
      if (Number.isNaN(numero)) return;
      const $zone = $(this).prev('.preview-fotos');
      agregarArchivos($zone, numero, this.files);
      // limpia el input para permitir volver a elegir el mismo archivo luego
      this.value = '';
    });
  
    // drag & drop
    function isFileDrag(evt) {
      const dt = evt.originalEvent && evt.originalEvent.dataTransfer;
      return dt && (dt.types?.includes('Files') || dt.files?.length);
    }
  
    $(document).on('dragenter dragover', '.tarea-card .preview-fotos', function (e) {
      if (!isFileDrag(e)) return;
      e.preventDefault(); e.stopPropagation();
      $(this).addClass('border-primary');
    });
  
    $(document).on('dragleave drop', '.tarea-card .preview-fotos', function (e) {
      if (!isFileDrag(e)) return;
      e.preventDefault(); e.stopPropagation();
      $(this).removeClass('border-primary');
  
      if (e.type === 'drop') {
        const $zone = $(this);
        const $card = $zone.closest('.tarea-card');
        const numero = numeroTareaDesdeCard($card);
        if (!numero) return;
  
        const files = e.originalEvent.dataTransfer.files;
        agregarArchivos($zone, numero, files);
      }
    });
  
    // ---- Inicialización / repintado tras renderizar el presupuesto ----
    function prepararBloqueFotosPresupuesto($scope) {
      const $cards = ($scope && $scope.length)
        ? $scope.find('.tarea-card')
        : $('#contenedorPresupuestoGenerado .tarea-card');
  
      $cards.each(function () {
        const $card = $(this);
        const numero = numeroTareaDesdeCard($card);
        if (!numero) return;
  
        const $zone = $card.find('.preview-fotos').first();
  
        // Inyecta el input si no existe
        ensureInputArchivo($zone, numero);
  
        // Si hay fotos ya existentes en memoria (p.ej. repobladas), pintarlas
        if (Array.isArray(imagenesPorTareaPresu[numero])) {
          // limpiar UI antes de repintar
          $zone.empty();
          imagenesPorTareaPresu[numero].forEach(it => {
            if (it.src) {
              renderThumb($zone, numero, it.src, it.nombre);
            } else if (it.file === null) {
              // existente del servidor sin src; dejamos el “placeholder” hasta tener URL real
            }
          });
        }
      });
    }
  
    // Llamalo manualmente tras tu `renderizarPresupuestoDesdeDatos(...)`
    // o escuchamos el DOM si ese render agrega #contenedorPresupuestoGenerado.
    const obs = new MutationObserver((mutList) => {
      let hayCambios = false;
      mutList.forEach(m => {
        if (m.addedNodes && m.addedNodes.length) {
          hayCambios = true;
        }
      });
      if (hayCambios) prepararBloqueFotosPresupuesto();
    });
    $(function () {
      const $cont = $('#contenedorPresupuestoGenerado');
      if ($cont.length) {
        obs.observe($cont.get(0), { childList: true, subtree: true });
      }
    });
  
    // ---- API pública (para cuando definas el guardado) ----
    window.PresupuestoFotos = {
      /** Devuelve un snapshot shallow de las imágenes por tarea */
      getImagenes() {
        // Filtramos solo los File reales; las "existentes" (file:null) las podrás cruzar con el backend cuando toque
        const out = {};
        Object.keys(imagenesPorTareaPresu).forEach(k => {
          out[k] = (imagenesPorTareaPresu[k] || []).filter(x => x.file instanceof File);
        });
        return out;
      },
      /** Devuelve nombres marcados para eliminar (cuando ya tengas persistencia) */
      getEliminadas() {
        return JSON.parse(JSON.stringify(fotosEliminadasPresu));
      },
      /** Permite inyectar fotos existentes (cuando el backend traiga URLs) */
      setExistentes(numeroTarea, arreglo) {
        // arreglo: [{ nombre, url }]
        imagenesPorTareaPresu[numeroTarea] = imagenesPorTareaPresu[numeroTarea] || [];
        arreglo.forEach(f => {
          imagenesPorTareaPresu[numeroTarea].push({ file: null, nombre: f.nombre, src: f.url });
        });
        prepararBloqueFotosPresupuesto($('#contenedorPresupuestoGenerado .tarea-card').eq(numeroTarea - 1));
      },
      /** Forzar re-escaneo de cards por si cambió el DOM */
      refresh() {
        prepararBloqueFotosPresupuesto();
      }
    };
  
  })(jQuery);
  