// file: accordionVisita.js 

const SELECTOR_TEXTO_TAREA_VISITA = '#accordionTareas .tarea-descripcion';

// Porcentajes por defecto para el presupuesto
const porcentajesPorDefecto = {
  materiales: 30,      // Utilidad materiales (puedes cambiarlo fácil)
  mano_obra: 100         // Placeholder para futuro uso en mano de obra
};

// === Fotos de PRESUPUESTO (independientes de las de VISITA) ===
window.presuImagenesPorTarea = {};     // { [nroTarea]: [ { file:File, nombre:string } ] }
window.presuFotosEliminadas  = {};     // { [nroTarea]: [ nombre:string ] }


$(document).ready(function() {
    let modoVisualizacion = false;
    let presupuestoGenerado = false;
    window.presupuestoGenerado = window.presupuestoGenerado || false;
    window.presupuestoDirty = window.presupuestoDirty || false;

    function marcarPresupuestoComoModificado() {
      if (!$('#contenedorPresupuestoGenerado').length) return;
      window.presupuestoDirty = true;
      verificarDatosVencidos();
    }

    function marcarPresupuestoComoGuardado() {
      if (!$('#contenedorPresupuestoGenerado').length) return;
      window.presupuestoDirty = false;
      verificarDatosVencidos();
    }

    window.marcarPresupuestoComoModificado = marcarPresupuestoComoModificado;
    window.marcarPresupuestoComoGuardado = marcarPresupuestoComoGuardado;

    function edicionComercialBloqueada() {
      if (typeof window.obtenerBloqueoEdicionComercialSeguimiento !== 'function') {
        return false;
      }
      const bloqueo = window.obtenerBloqueoEdicionComercialSeguimiento();
      return !!(bloqueo && bloqueo.bloqueado);
    }

    function mostrarAlertaBloqueoEdicionComercial() {
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


    // === Vista detallada por usuario (sin ojito) ===
    // IDs habilitados a ver utilidades/porcentajes/“vista completa”
    const vistaDetallada = [2, 38];

    // Helper robusto para obtener el id del usuario activo desde el DOM/ventana
    function obtenerUsuarioIdActivo() {
      // 1) Variable global inyectada desde navbar_layout.php (window.ACTIVE_USER_ID = <id>)
      if (typeof window.ACTIVE_USER_ID !== 'undefined') {
        const v = parseInt(window.ACTIVE_USER_ID, 10);
        if (!isNaN(v)) return v;
      }
      // 2) Hidden inputs habituales
      const $hid = $('#usuario_id, #id_usuario, input[name="usuario_id"]').first();
      if ($hid.length && $hid.val()) {
        const v = parseInt($hid.val(), 10);
        if (!isNaN(v)) return v;
      }
      // 3) Data-attribute en <body data-usuario-id="...">
      const dataId = parseInt($('body').data('usuarioId'), 10);
      if (!isNaN(dataId)) return dataId;

      return null; // no encontrado
    }

    const USUARIO_ID_ACTIVO = obtenerUsuarioIdActivo();
    const mostrarVistaDetallada = (
      USUARIO_ID_ACTIVO != null &&
      vistaDetallada.includes(USUARIO_ID_ACTIVO)
    );

    function controlarBotonGenerarPresupuesto() {
        // Chequea si hay tareas
        let tieneTareas = $('#accordionTareas > .card').length > 0;
        let todasTienenMaterial = true;
        let todasTienenManoObra = true;
      
        // Chequea que cada tarea tenga al menos un material y una mano de obra
        $('#accordionTareas > .card').each(function () {
          const materiales = $(this).find('.materiales-table tbody tr').not('.fila-vacia-materiales').length;
          if (materiales === 0) todasTienenMaterial = false;
      
          const manoObra = $(this).find('.mano-obra-table tbody tr').not('.fila-vacia-mano-obra').length;
          if (manoObra === 0) todasTienenManoObra = false;
        });
      
        // 💡 SOLO se habilita si:
        // - Hay tareas
        // - Todas tienen materiales y mano de obra
        // - No hay cambios pendientes (hayCambios === false)
        // - No estamos en modo visualización (modoVisualizacion === false)
        // - **No existe presupuesto generado** (!presupuestoGenerado)
      
        const habilitado = tieneTareas && todasTienenMaterial && todasTienenManoObra && !hayCambios && !modoVisualizacion && !presupuestoGenerado && !edicionComercialBloqueada();
      
        const $btn = $('#btn-generar-presupuesto');
        if ($btn.length) {
          if (habilitado) {
            $btn.prop('disabled', false).removeClass('btn-secondary').addClass('btn-info');
          } else {
            $btn.prop('disabled', true).removeClass('btn-info').addClass('btn-secondary');
          }
        }
    }
 
    // Objeto global donde vamos guardando imágenes por tarea
    const imagenesPorTarea = {};
    let hayCambios = false;
    controlarBotonGenerarPresupuesto();
    const fotosEliminadasPorTarea = {};
    const fotos = {};
    // Flag para no spamear el alerta de valores desactualizados
    let alertaDesactualizadosMostrada = false;

    // Inicializar select2 para Materiales
    $('.material-select').select2({
      placeholder: "Seleccione material",
      width: '100%',
      language: "es"
    });

    // Inicializar select2 para Mano de Obra
    $('.mano-obra-select').select2({
      placeholder: "Seleccione mano de obra",
      width: '100%',
      language: "es"
    });


    // 1) Cualquier input, select o textarea modificado dispara el flag
    $(document).on('change input', 'input, textarea, select', function() {
      if (!modoVisualizacion) hayCambios = true;
      controlarBotonGenerarPresupuesto();
    });

    // 2) Añadir o eliminar materiales / mano de obra también cuenta
    $(document).on('click', '.agregar-material, .eliminar-material, .agregar-mano-obra, .eliminar-mano-obra', function() {
      if (!modoVisualizacion) hayCambios = true;
      controlarBotonGenerarPresupuesto();
    });

    // 3) Subir o eliminar fotos marca cambios
    $(document).on('change', '.tarea-fotos', function() {
      if (!modoVisualizacion) hayCambios = true;
      controlarBotonGenerarPresupuesto();
    });

    $(document).on('click', '.eliminar-imagen', function() {
      if (!modoVisualizacion) hayCambios = true;
      controlarBotonGenerarPresupuesto();
      const $thumb = $(this).closest('.preview-img-container');
      const nombre = $thumb.data('nombre-archivo');
      const idx = parseInt($thumb.closest('.preview-fotos').attr('id').split('_').pop(), 10);
    
      // Quitar de la UI
      $thumb.remove();
    
      // Inicializar si hace falta
      fotosEliminadasPorTarea[idx] = fotosEliminadasPorTarea[idx] || [];
      imagenesPorTarea[idx]     = imagenesPorTarea[idx]     || [];
    
      // Registrar eliminación **sin duplicados**
      if (!fotosEliminadasPorTarea[idx].includes(nombre) && 
          imagenesPorTarea[idx].some(img => img.nombre === nombre && img.file === null)
      ) {
        fotosEliminadasPorTarea[idx].push(nombre);
      }
    
      // Eliminar de la lista de imágenes en memoria
      imagenesPorTarea[idx] = imagenesPorTarea[idx].filter(img => img.nombre !== nombre);
    });
  
    // 2) Delegado para ampliar miniatura
    $(document).on('click', '.preview-img-container img', function() {
      const src = $(this).attr('src');
      Swal.fire({
        imageUrl: src,
        imageAlt: 'Foto de la tarea',
        showConfirmButton: false,
        background: '#ffffff',
        backdrop: 'rgba(0,0,0,0.7)',
        width: 'auto',
        padding: '1rem',
        showCloseButton: true,
        customClass: { popup: 'shadow-lg rounded' }
      });
    });

    // Función para agregar material
    $(document).on('click', '.agregar-material', function() {
      var $cardBody = $(this).closest('.card-body');
      var $materialSelect = $cardBody.find('.material-select');
      var $cantidadInput = $cardBody.find('.material-cantidad');
      var $materialesTable = $cardBody.find('.materiales-table tbody');

      var materialId = $materialSelect.val();
      var materialText = $materialSelect.find('option:selected').text();
      var cantidad = $cantidadInput.val();

      if (!materialId) {
        mostrarAdvertencia('Debes seleccionar un material.', 4);
        return;
      }

      if (!cantidad || cantidad <= 0) {
        mostrarAdvertencia('Debes ingresar una cantidad válida.', 4);
        return;
      }

      // Verificar duplicados
      var existe = false;
      $materialesTable.find('tr').each(function() {
        if ($(this).data('material-id') == materialId) {
          existe = true;
          return false;
        }
      });

      if (existe) {
        mostrarError('Este material ya fue asociado a la tarea.', 4);
        return;
      }

      // Obtener data-* del option seleccionado
      var $selected = $materialSelect.find('option:selected');
      var precio = $selected.data('precio_unitario') || '';
      var unidadMedida = $selected.data('unidad_medida') || '';
      var unidadVenta = $selected.data('unidad_venta') || '';
      var contenido = $selected.data('contenido') || '';
      var logEdicion = $selected.data('log_edicion') || '';
      var logAlta = $selected.data('log_alta') || '';


      // Agregar fila (y quitar placeholder si existe)
      $materialesTable.find('.fila-vacia-materiales').remove();

      // calcular el # de fila luego de quitar el placeholder
      var rowCount = $materialesTable.find('tr').length + 1;

      var nuevaFila = `
        <tr 
          data-material-id="${materialId}"
          data-precio_unitario="${precio}"
          data-unidad_medida="${unidadMedida}"
          data-unidad_venta="${unidadVenta}"
          data-contenido="${contenido}"
          data-log_edicion="${logEdicion}"
          data-log_alta="${logAlta}">
          <td>${rowCount}</td>
          <td>${materialText}</td>
          <td>${cantidad}</td>
          <td class="text-center">
              <i class="fa fa-trash v-icon-pointer text-danger eliminar-material" title="Eliminar material" style="cursor: pointer; font-size: 1.2rem;"></i>
          </td>
        </tr>
      `;


      $materialesTable.append(nuevaFila);
      $materialSelect.val('').trigger('change');
      $cantidadInput.val('');
    });

    // Función para eliminar material
    $(document).on('click', '.eliminar-material', function() {
        var $fila = $(this).closest('tr');
        var $tabla = $fila.closest('tbody');

        $fila.remove();

        // Renumerar filas
        $tabla.find('tr').each(function(index) {
          $(this).find('td:first').text(index + 1);
        });

        // Si no hay más filas, mostrar fila vacía
        if ($tabla.find('tr').length == 0) {
          $tabla.html(`
            <tr class="fila-vacia-materiales">
              <td colspan="4" class="text-center text-muted">Sin materiales asociados</td>
            </tr>
          `);
        }
    });

    // Función para agregar mano de obra  
    $(document).on('click', '.agregar-mano-obra', function () {
      const $cardBody = $(this).closest('.card-body');
      const $select = $cardBody.find('.mano-obra-select');
      const $cantidad = $cardBody.find('.mano-obra-cantidad');
      const tabla = $cardBody.find('.mano-obra-table tbody');

      const id = $select.val();
      const texto = $select.find('option:selected').text();
      const cantidad = $cantidad.val();

      // Validar duplicado
      let yaExiste = false;
      tabla.find('tr').each(function () {
        const idExistente = $(this).find('input[name="mano_obra_id[]"]').val();
        if (idExistente == id) {
          yaExiste = true;
          return false;
        }
      });

      if (yaExiste) {
        mostrarError('Esa mano de obra ya fue asociada a la tarea.', 3);
        return;
      }

      if (!id || id === 'Seleccione mano de obra') {
        mostrarAdvertencia('Debe seleccionar una opción de mano de obra.', 3);
        return;
      }

      if (!cantidad || cantidad <= 0) {
        mostrarAdvertencia('Debe ingresar una cantidad válida.', 3);
        return;
      }

      // Obtener data-* del option
      const $opt = $select.find('option:selected'); 
      const idJornal = $opt.data('jornal_id') || '';
      const valor = $opt.data('jornal_valor') || '';
      const updatedAt = $opt.data('updated_at') || '';

      // Eliminar fila vacía si existe
      tabla.find('.fila-vacia-mano-obra').remove();

      const index = tabla.find('tr').length + 1;

      // ✅ Días por defecto según tu HTML actual (value="1")
      const dias = 1;

      // ✅ Jornales = cantidad × días
      const jornales = (parseFloat(cantidad) || 0) * (parseFloat(dias) || 1);

      const fila = `
        <tr data-jornal_id="${idJornal}" data-jornal_valor="${valor}" data-updated_at="${updatedAt}">
          <td>${index}</td>
          <td>
            <input type="hidden" name="mano_obra_id[]" value="${id}">
            <span>${texto}</span>
          </td>
          <td>
            <span>${cantidad}</span>
            <input type="hidden" name="mano_obra_cantidad[]" value="${cantidad}">
          </td>
          <td class="text-center align-middle p-0">
            <div style="width: 100%; display: flex; justify-content: center;">
              <input type="number" class="form-control form-control-sm mano-obra-dias" name="mano_obra_dias[]" value="${dias}" min="1" style="min-width: 60px; max-width: 60px;">
            </div>
          </td>
          <td class="text-center mano-obra-jornales">${jornales}</td>               
          <td>
            <input type="text" name="mano_obra_observacion[]" class="form-control form-control-sm" placeholder="Observaciones">
          </td>
          <td class="text-center">
            <i class="fa fa-trash eliminar-mano-obra text-danger" title="Eliminar" style="cursor: pointer;"></i>
          </td>
        </tr>
      `;

      tabla.append(fila);
      $select.val(null).trigger('change');
      $cantidad.val('');
    });

    // Función para eliminar mano de obra
    $(document).on('click', '.eliminar-mano-obra', function () {
        const fila = $(this).closest('tr');
        const tabla = fila.closest('tbody'); // 🔧 ahora es relativa
        fila.remove();

        const filasRestantes = tabla.find('tr');

        if (filasRestantes.length === 0) {
            tabla.append(`
                <tr class="fila-vacia-mano-obra">
                  <td colspan="7" class="text-center text-muted">Sin mano de obra asociada</td>
                </tr>
            `);
        }
    });

    // ✅ Recalcular jornales (Cantidad × Días) en mano de obra (VISITA)
    function recalcularJornalesFila($tr) {
      const cant = parseFloat($tr.find('input[name="mano_obra_cantidad[]"]').val()) || 0;
      const dias = parseFloat($tr.find('input[name="mano_obra_dias[]"]').val()) || 1;
      const jornales = cant * dias;
      $tr.find('.mano-obra-jornales').text(jornales);
    }

    // Cuando cambia días (input/change)
    $(document).on('input change', '.mano-obra-dias', function () {
      recalcularJornalesFila($(this).closest('tr'));
    });
    
    $(document).on('change', '.tarea-fotos', function (e) {
          const input = e.target;
          const index = $(input).data('index'); // índice de la tarea
          const previewContainer = $(`#preview_fotos_tarea_${index}`);
          const archivos = Array.from(this.files);

          if (!archivos || archivos.length === 0) return;

          // Inicializar array de previews si no existe
          if (!imagenesPorTarea[index]) {
              imagenesPorTarea[index] = [];
          }

          // Inicializar array de eliminadas si no existe
          if (!fotosEliminadasPorTarea[index]) fotosEliminadasPorTarea[index] = [];

          archivos.forEach((file) => {
              const reader = new FileReader();
              reader.onload = function (event) {
                  const imgSrc = event.target.result;
                  const nombreArchivo = file.name;

                  // Guardar imagen en base64 para preview
                  imagenesPorTarea[index].push({ file, nombre: file.name });

                  const contenedor = $(`
                      <div class="preview-img-container position-relative d-inline-block m-1" data-nombre-archivo="${nombreArchivo}">
                          <img src="${imgSrc}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover; cursor: pointer;">
                          <i class="fa fa-times-circle text-white rounded-circle position-absolute eliminar-imagen" 
                            style="top: 0px; right: 0px; cursor: pointer; font-size: 1rem;"></i>
                      </div>
                  `);

                  // // Ver imagen ampliada
                  // contenedor.find('img').on('click', () => {
                  //     Swal.fire({
                  //         imageUrl: imgSrc,
                  //         imageAlt: 'Foto de la tarea',
                  //         showConfirmButton: false,
                  //         background: '#ffffff',
                  //         backdrop: 'rgba(0,0,0,0.7)',
                  //         allowOutsideClick: false,
                  //         allowEscapeKey: true,
                  //         width: 'auto',
                  //         padding: '1rem',
                  //         showCloseButton: true,
                  //         customClass: {
                  //             popup: 'shadow-lg rounded'
                  //         }
                  //     });
                  // });

                  // Eliminar visual y registrar
                  contenedor.find('.eliminar-imagen').on('click', () => {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    imagenesPorTarea[index] = imagenesPorTarea[index].filter(img => img.nombre !== nombreArchivo);
                    fotosEliminadasPorTarea[index].push(nombreArchivo);
                    contenedor.remove();
                  });

                  previewContainer.append(contenedor);
              };
              reader.readAsDataURL(file);
          });
    });

    // acciones y eventos
    $(document).on('click', '#btn-agregar-tarea', function () {
          //console.log('Agregar nueva tarea clickeado');
            if (!modoVisualizacion) hayCambios = true;
            controlarBotonGenerarPresupuesto();
            let hayDescripcionIncompleta = false;

              $(SELECTOR_TEXTO_TAREA_VISITA).each(function () {
                  const valor = $(this).val();
                  
                  if (!valor || valor.trim() === '') {
                      $(this).addClass('is-invalid');
                      hayDescripcionIncompleta = true;
                  } else {
                      $(this).removeClass('is-invalid');
                  }
              });

            if (hayDescripcionIncompleta) {
                mostrarAdvertencia('Complete la descripción de la tarea antes de agregar otra.', 4);
                return;
            }
    });


    $(document).on('input', SELECTOR_TEXTO_TAREA_VISITA, function () {
        const texto = $(this).val().trim();

        // Validación visual
        if (texto !== '') {
            $(this).removeClass('is-invalid');
        }

        // Actualizar encabezado de la tarea
        const collapseId = $(this).closest('.collapse').attr('id');
        const encabezado = $(`button[data-target="#${collapseId}"]`);

        const preview = texto.length > 100 ? texto.substring(0, 100) + '...' : texto;

        encabezado.html(`<strong>${encabezado.data('titulo-base')}:</strong> ${preview || 'Breve descripción'}`);
    });


    // clonado de tarea
    $(document).on('click', '#btn-agregar-tarea', function () {
          let hayDescripcionIncompleta = false;

          $(SELECTOR_TEXTO_TAREA_VISITA).each(function () {
              if ($(this).val().trim() === '') {
                  $(this).addClass('is-invalid');
                  hayDescripcionIncompleta = true;
              } else {
                  $(this).removeClass('is-invalid');
              }
          });

          if (hayDescripcionIncompleta) {
              mostrarAdvertencia('Complete la descripción de la tarea antes de agregar otra.', 4);
              return;
          }

          const tareaIndex = $('#accordionTareas > .card').length + 1;

          // Colapsar todas las tareas existentes
          $('#accordionTareas .collapse').removeClass('show');
          $('#accordionTareas .btn-link').attr('aria-expanded', 'false');

          // Clonamos los options del primer select
          const opcionesMaterial = $('#opcionesMaterialBase').html();
          const opcionesManoObra = $('#opcionesManoObraBase').html();

          // HTML de nueva tarea (SIN recortar)
          const nuevaTareaHtml = `
          <div class="card">
              <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center" id="headingTarea${tareaIndex}">
                  <button class="btn btn-link text-white p-0 m-0 flex-grow-1 text-left"
                          type="button"
                          data-toggle="collapse"
                          data-target="#collapseTarea${tareaIndex}"
                          aria-expanded="true"
                          aria-controls="collapseTarea${tareaIndex}"
                          data-titulo-base="Tarea ${tareaIndex}">
                      <strong>Tarea ${tareaIndex}:</strong>  
                  </button>
                  <i class="fa fa-trash eliminar-tarea v-icon-pointer" title="Eliminar tarea" style="cursor: pointer; font-size: 1.3rem;"></i>
              </div>
              <div id="collapseTarea${tareaIndex}" class="collapse show" aria-labelledby="headingTarea${tareaIndex}" data-parent="#accordionTareas">
                  <div class="card-body">
                      <div class="row d-flex align-items-stretch">

                          <!-- Descripción -->
                          <div class="col-md-3">
                              <div class="card h-100 mb-2">
                                  <div class="card-header v-bg-violeta text-white">
                                      <h5 class="card-title mb-0">Descripción de la Tarea</h5>
                                  </div>
                                  <div class="card-body p-2 d-flex flex-column">
                                      <div class="form-group flex-grow-1">
                                          <textarea class="form-control tarea-descripcion h-100" placeholder="Describa la tarea..."></textarea>
                                      </div>
                                  </div>
                              </div>
                          </div>

                          <!-- Materiales + Mano de Obra -->
                          <div class="col-md-6 d-flex flex-column">

                              <!-- Materiales -->
                              <div class="card mb-2 flex-fill">
                                  <div class="card-header v-bg-violeta text-white">
                                      <h5 class="card-title mb-0">Materiales Asociados</h5>
                                  </div>
                                  <div class="card-body p-2">
                                      <div class="form-row align-items-center mb-2">
                                          <div class="col-8">
                                              <select class="form-control form-control-sm material-select">
                                                  ${opcionesMaterial}
                                              </select>
                                          </div>
                                          <div class="col-3">
                                              <input type="number" class="form-control form-control-sm material-cantidad" placeholder="Cantidad" min="1">
                                          </div>
                                          <div class="col-1">
                                              <button type="button" class="btn btn-success btn-sm agregar-material w-100"><i class="fa fa-plus"></i></button>
                                          </div>
                                      </div>
                                      <div class="table-responsive">
                                          <table class="table table-bordered materiales-table mb-2">
                                              <thead class="thead-light">
                                                  <tr>
                                                      <th>#</th>
                                                      <th>Material</th>
                                                      <th>Cantidad</th>
                                                      <th>Acción</th>
                                                  </tr>
                                              </thead>
                                              <tbody>
                                                  <tr class="fila-vacia-materiales">
                                                      <td colspan="4" class="text-center text-muted">Sin materiales asociados</td>
                                                  </tr>
                                              </tbody>
                                          </table>
                                      </div>
                                  </div>
                              </div>

                              <!-- Mano de Obra -->
                              <div class="card flex-fill">
                                  <div class="card-header v-bg-violeta text-white">
                                      <h5 class="card-title mb-0">Mano de Obra Asociada</h5>
                                  </div>
                                  <div class="card-body p-2">
                                      <div class="form-row align-items-center mb-2">
                                          <div class="col-8">
                                              <select class="form-control form-control-sm mano-obra-select">
                                                  ${opcionesManoObra}
                                              </select>
                                          </div>
                                          <div class="col-3">
                                              <input type="number" class="form-control form-control-sm mano-obra-cantidad" placeholder="Cantidad" min="1">
                                          </div>
                                          <div class="col-1">
                                              <button type="button" class="btn btn-success btn-sm agregar-mano-obra w-100"><i class="fa fa-plus"></i></button>
                                          </div>
                                      </div>
                                      <div class="table-responsive">
                                          <table class="table table-bordered mano-obra-table mb-2">
                                              <thead class="thead-light text-center">
                                                  <tr>
                                                  <th>#</th>
                                                  <th>Mano de obra</th>
                                                  <th>Cantidad</th>
                                                  <th>Días</th>
                                                  <th>Jornales</th>
                                                  <th>Observaciones</th>
                                                  <th>Acción</th>
                                                  </tr>
                                              </thead>
                                              <tbody>
                                                  <tr class="fila-vacia-mano-obra">
                                                      <td colspan="7" class="text-center text-muted">Sin mano de obra asociada</td>
                                                  </tr>
                                              </tbody>
                                          </table>
                                      </div>
                                  </div>
                              </div>

                          </div>

                          <!-- Fotos -->
                          <div class="col-md-3">
                              <div class="card h-100 mb-2">
                                  <div class="card-header v-bg-violeta text-white">
                                      <h5 class="card-title mb-0">Fotos de la Tarea</h5>
                                  </div>
                                  <div class="card-body p-2">
                                      <div class="custom-file mb-2">
                                              <input 
                                                  type="file" 
                                                  class="custom-file-input tarea-fotos" 
                                                  id="fotos_tarea_${tareaIndex}" 
                                                  multiple 
                                                  accept="image/*" 
                                                  capture="environment"
                                                  data-index="${tareaIndex}"
                                              />
                                          <label class="custom-file-label" for="fotos_tarea_${tareaIndex}">Seleccionar fotos</label>
                                      </div>
                                      <div class="row preview-fotos" id="preview_fotos_tarea_${tareaIndex}"></div>
                                  </div>
                              </div>
                          </div>

                      </div>
                  </div>
              </div>
          </div>`;

          // Agregar al DOM
          $('#accordionTareas').append(nuevaTareaHtml);

          const nuevaTarea = $('#accordionTareas > .card').last();

          // 🔧 Limpiar cualquier option seleccionado en los selects nuevos
          nuevaTarea.find('.material-select option:selected').prop('selected', false);
          nuevaTarea.find('.mano-obra-select option:selected').prop('selected', false);

          // 🔧 Inicializar select2 correctamente
          nuevaTarea.find('.material-select').select2({
              placeholder: "Seleccione material",
              width: '100%',
              language: "es"
          });

          nuevaTarea.find('.mano-obra-select').select2({
              placeholder: "Seleccione mano de obra",
              width: '100%',
              language: "es"
          });

          // 🔧 Reset visual del valor para que se vea el placeholder
          nuevaTarea.find('.material-select').val('').trigger('change');
          nuevaTarea.find('.mano-obra-select').val('').trigger('change');
    });

    // eliminar tarea
    $(document).on('click', '.eliminar-tarea', function () {
        const totalTareas = $('#accordionTareas > .card').length;
        const $boton = $(this); // capturamos el botón para usar en el callback

        if (totalTareas === 1) {
            mostrarAdvertencia('Debe quedar al menos una tarea.', 4);
            return;
        }

        mostrarConfirmacion('Se va a eliminar una tarea, confirma', () => {
            if (!modoVisualizacion) hayCambios = true;
            controlarBotonGenerarPresupuesto();
            // Eliminar la tarea actual
            $boton.closest('.card').remove();

            // Renumerar tareas
            $('#accordionTareas > .card').each(function (index) {
                const nuevoIndex = index + 1;
                const card = $(this);
                const header = card.find('.card-header button');
                const textarea = card.find('.tarea-descripcion').val().trim();

                // Actualizar texto del botón
                const preview = textarea.length > 50 ? textarea.substring(0, 50) + '...' : textarea;
                header.attr('data-titulo-base', `Tarea ${nuevoIndex}`);
                header.html(`<strong>Tarea ${nuevoIndex}:</strong> ${preview || 'Breve descripción'}`);

                // Actualizar IDs y targets del acordeón
                const nuevoHeadingId = `headingTarea${nuevoIndex}`;
                const nuevoCollapseId = `collapseTarea${nuevoIndex}`;

                card.find('.card-header').attr('id', nuevoHeadingId);
                header.attr('data-target', `#${nuevoCollapseId}`);
                header.attr('aria-controls', nuevoCollapseId);

                card.find('.collapse')
                    .attr('id', nuevoCollapseId)
                    .attr('aria-labelledby', nuevoHeadingId)
                    .attr('data-parent', '#accordionTareas');

                // Actualizar campos de fotos
                card.find('.tarea-fotos')
                    .attr('id', `fotos_tarea_${nuevoIndex}`)
                    .removeAttr('name')
                    .attr('data-index', nuevoIndex);
                card.find('.custom-file-label').attr('for', `fotos_tarea_${nuevoIndex}`);
                card.find('.preview-fotos').attr('id', `preview_fotos_tarea_${nuevoIndex}`);
            });
        });
    });


    // Guardar visita
    $(document).on('click', '.btn-guardar-visita', function () {
        $('#accordionTareas > .card').each(function() {
            const idx = parseInt($(this)
              .find('.preview-fotos')
              .attr('id')
              .replace('preview_fotos_tarea_', ''), 10);
          
            // FILTRAR: sólo dejamos los objetos con file real
            imagenesPorTarea[idx] = (imagenesPorTarea[idx] || [])
              .filter(img => img.file instanceof File);
          });

        // 1) Validar descripciones
        let falta = false;
        $(SELECTOR_TEXTO_TAREA_VISITA).each(function () {
          if (!$(this).val().trim()) {
            falta = true;
            $(this).addClass('is-invalid');
          } else {
            $(this).removeClass('is-invalid');
          }
        });
        if (falta) {
          mostrarAdvertencia('Debe completar la descripción de todas las tareas antes de guardar.', 4);
          return;
        }
      
        // 2) Preparar FormData
        const formData = new FormData();
        formData.append('id_visita', $('#id_previsita').val());
      
        // 3) Para cada card de tarea, usamos su data-index real
        $('#accordionTareas > .card').each(function () {
          const $card = $(this);
          // 🏷️ obtenemos el índice de tarea a partir del ID de la zona de fotos:
          const tareaIndex = parseInt(
            $card.find('.preview-fotos').attr('id').replace('preview_fotos_tarea_', ''),
            10
          );
      
          // 3.1) ID de la tarea (si existe)
          const idTarea = $card.find('input[name="id_tarea[]"]').val();
          if (idTarea) {
            formData.append(`tareas[${tareaIndex}][id_tarea]`, idTarea);
          }
      
          // 3.2) Descripción
          const desc = $card.find('textarea.tarea-descripcion').val().trim();
          formData.append(`tareas[${tareaIndex}][descripcion]`, desc);
      
          // 3.3) Materiales
          $card.find('.materiales-table tbody tr').each(function (j) {
            if (!$(this).hasClass('fila-vacia-materiales')) {
              const idMat = $(this).data('material-id');
              const cant  = $(this).find('td:nth-child(3)').text();
              formData.append(`tareas[${tareaIndex}][materiales][${j}][id]`, idMat);
              formData.append(`tareas[${tareaIndex}][materiales][${j}][cantidad]`, cant);
            }
          });
      
          // 3.4) Mano de obra
          $card.find('.mano-obra-table tbody tr').each(function (j) {
            if (!$(this).hasClass('fila-vacia-mano-obra')) {
                const idMo = $(this).find('input[name="mano_obra_id[]"]').val();
                const cant = $(this).find('input[name="mano_obra_cantidad[]"]').val();
                const dias = $(this).find('input[name="mano_obra_dias[]"]').val();
                const obs  = $(this).find('input[name="mano_obra_observacion[]"]').val();

                formData.append(`tareas[${tareaIndex}][mano_obra][${j}][id]`, idMo);
                formData.append(`tareas[${tareaIndex}][mano_obra][${j}][cantidad]`, cant);
                formData.append(`tareas[${tareaIndex}][mano_obra][${j}][dias]`, dias);
                formData.append(`tareas[${tareaIndex}][mano_obra][${j}][observacion]`, obs);
            }
          });
      
          // 3.5) Fotos eliminadas (solo los nombres que el usuario borró)
          (fotosEliminadasPorTarea[tareaIndex] || []).forEach((nombre, j) => {
            formData.append(
              `tareas[${tareaIndex}][fotos_eliminadas][${j}]`,
              nombre
            );
          });
      
          // 3.6) Fotos nuevas (solo los File)
          (imagenesPorTarea[tareaIndex] || []).forEach((imgObj, j) => {
            if (imgObj.file instanceof File) {
              // OBSERVA: uso tareaIndex, **no** la variable i del forEach de tareas
              formData.append(`foto_tarea_${tareaIndex}_${j}`, imgObj.file);
            }
          });
        });



        // 4) Envío AJAX
        $.ajax({
          url: '../06-funciones_php/guardar_visita.php',
          method: 'POST',
          data: formData,
          contentType: false,
          processData: false,
          success(resp) {
            if (resp.status) {
              // Actualizar los hidden id_tarea[] con los nuevos IDs
              $('#accordionTareas > .card').each(function () {
                const $c = $(this);
                const idx = parseInt(
                  $c.find('.preview-fotos').attr('id').replace('preview_fotos_tarea_', ''),
                  10
                );
                const newId = resp.ids_tareas[idx];
                let $hid = $c.find('input[name="id_tarea[]"]');
                if ($hid.length) {
                  $hid.val(newId);
                } else {
                  $c.find('.card-body')
                    .append(`<input type="hidden" name="id_tarea[]" value="${newId}">`);
                }
              });
              hayCambios = false;
              controlarBotonGenerarPresupuesto();
              mostrarExito('Visita guardada correctamente', 4);
            } else {
              mostrarError(resp.mensaje || 'Error al guardar.', 4);
            }
          },
          error() {
            mostrarError('Error al comunicar con el servidor.', 4);
          }
        });
    });
    
    $(document).on('click', '.btn-cancelar-visita', function () {
        if (modoVisualizacion) {
          // En modo visualización, salir directamente sin preguntar
          window.location.href = 'seguimiento_de_obra_listado.php';
          return;
        }
      
        if (!hayCambios) {
          // Si no hubo cambios, salir directo
          window.location.href = 'seguimiento_de_obra_listado.php';
        } else {
          // Si hubo cambios, mostrar confirmación
          mostrarConfirmacion(
            'Tiene cambios sin guardar, <strong>¿deseas salir de todas maneras?</strong>',
            () => window.location.href = 'seguimiento_de_obra_listado.php',
            null
          );
        }
    });
    
    // Botón: Generar Presupuesto
    $(document).on('click', '.btn-generar-presupuesto', function() {
      if (edicionComercialBloqueada()) {
        mostrarAlertaBloqueoEdicionComercial();
        return;
      }

      presupuestoGenerado = true; 
      // 1. Si no existe el accordion, lo crea usando el template
      if ($('#accordionPresupuesto').length === 0) {
          const tpl = document.getElementById('tpl-accordion-presupuesto');
          if (tpl) {
              const clone = tpl.content.cloneNode(true);
              // Insertarlo después del accordion de Visita
              $('#accordionVisita').after(clone);
              if (typeof window.initPopoverIntervinoPresupuesto === 'function') {
                window.initPopoverIntervinoPresupuesto();
              }
          }
      }

      // 2. Colapsar Visita y expandir Presupuesto
      $('#collapseVisita').collapse('hide');
      setTimeout(() => {
          $('#collapsePresupuesto').collapse('show');
      }, 150);

      // 3. Recolección de datos y render dinámico
      const datosExtraidos = recolectarDatosParaPresupuesto();
      console.log('📦 Datos extraídos para presupuesto:', datosExtraidos);
      renderizarPresupuestoDesdeDatos(datosExtraidos);
      marcarPresupuestoComoModificado();

      // 🔗 NUEVO: preparar bloque de fotos para drag&drop y selección
      if (window.PresupuestoFotos && typeof PresupuestoFotos.refresh === 'function') {
        PresupuestoFotos.refresh();
      }

      // 5. Deshabilitar el botón para evitar doble click
      $(this).prop('disabled', true);

      // 6. Scroll suave al Presupuesto
      setTimeout(() => {
          $('html, body').animate({
              scrollTop: $("#headingPresupuesto").offset().top - 80
          }, 600);
      }, 300);
    });

    // ======= POBLAR DESDE EL BACKEND =======
    
    if (typeof tareasVisitadas !== 'undefined' && tareasVisitadas.length) {
        modoVisualizacion = $('form').find('.v-id').data('visualiza') === 'on';

        // Reiniciamos trackers
        Object.keys(fotosEliminadasPorTarea).forEach(k => delete fotosEliminadasPorTarea[k]);


        // Para cada tarea que vino del servidor...
        tareasVisitadas.forEach((tarea, i) => {
          const num = i + 1;
      
            // 1) Si no es la primera, simulamos click en "Agregar nueva tarea"
            if (i > 0) {
                $('#btn-agregar-tarea').click();
            }
      
            // 2) Referencia al card recién creado o inicial
            const $card = $(`#headingTarea${num}`).closest('.card');
      
            // 3) Descripción
            $card.find('textarea.tarea-descripcion')
                .val(tarea.descripcion)
                .trigger('input');
      
            // 4) Hidden con el ID real de la tarea
            let $hid = $card.find('input[name="id_tarea[]"]');
            if ($hid.length) {
                $hid.val(tarea.id_tarea);
            } else {
                $card.find('.card-body')
                    .append(`<input type="hidden" name="id_tarea[]" value="${tarea.id_tarea}">`);
            }
      
            // 5) Materiales
            tarea.materiales.forEach(mat => {
                const $btnMat = $card.find('.agregar-material');
                const $selMat = $card.find('.material-select');
                $selMat.val(mat.id_material).trigger('change');
                $card.find('.material-cantidad').val(mat.cantidad);
                $btnMat.click();
            });
      
            // 6) Mano de obra
            tarea.mano_obra.forEach(mo => {
                const $btnMO = $card.find('.agregar-mano-obra');
                const $selMO = $card.find('.mano-obra-select');
                $selMO.val(mo.id_jornal).trigger('change');
                $card.find('.mano-obra-cantidad').val(mo.cantidad);
                $btnMO.click();
                // luego ponemos la observación
                $card.find('.mano-obra-table tbody tr:last')
                .find('input[name="mano_obra_dias[]"]')
                .val(mo.dias || 1)  // default a 1 si no viene definido
                .trigger('input');  // actualiza también los jornales si hace falta
              
            });
      
            // 7) Fotos
            imagenesPorTarea[num]       = [];
            fotosEliminadasPorTarea[num] = [];

            const $preview = $(`#preview_fotos_tarea_${num}`);
            tarea.fotos.forEach(f => {
                    const thumb = $(`
                        <div class="preview-img-container position-relative d-inline-block m-1"
                            data-nombre-archivo="${f.nombre_archivo}">
                        <img src="${f.ruta_archivo}"
                            class="img-thumbnail"
                            style="width:100px;height:100px;object-fit:cover;">
                        <i class="fa fa-times-circle text-white rounded-circle position-absolute eliminar-imagen"
                            style="top:0;right:0;cursor:pointer;font-size:1rem;"></i>
                        </div>
                    `);

                    // Guardar en array global como foto "existente"
                    imagenesPorTarea[num].push({ file: null, nombre: f.nombre_archivo });

                    // ✅ Evento para eliminar imagen repoblada
                    thumb.find('.eliminar-imagen').on('click', function () {
                        const $thumb = $(this).closest('.preview-img-container');
                        const nombre = $thumb.data('nombre-archivo');

                        // Inicializar arrays si hiciera falta
                        fotosEliminadasPorTarea[num] = fotosEliminadasPorTarea[num] || [];
                        imagenesPorTarea[num] = imagenesPorTarea[num] || [];

                        // Agregar a array de eliminadas (sin duplicados)
                        if (!fotosEliminadasPorTarea[num].includes(nombre)) {
                        fotosEliminadasPorTarea[num].push(nombre);
                        }

                        // Eliminar de imágenes en memoria
                        imagenesPorTarea[num] = imagenesPorTarea[num].filter(img => img.nombre !== nombre);

                        // Eliminar del DOM
                        $thumb.remove();
                    });

                // Agregar al contenedor
                $preview.append(thumb);
            });

            // Deshabilitar campos si estamos en modo visualización
            if (modoVisualizacion) {
                const contenedorTarea = $(`#collapseTarea${num}`);

                // 🔒 Deshabilitar todos los campos de la tarea
                contenedorTarea.find('input, textarea, select, button').prop('disabled', true);

                // ❌ Eliminar tachitos de encabezado
                contenedorTarea.closest('.card').find('.eliminar-tarea').remove();

                // ❌ Eliminar tachitos de tablas (materiales, mano de obra, fotos)
                contenedorTarea.find('.eliminar-material').closest('td').remove();
                contenedorTarea.find('.eliminar-mano-obra').closest('td').remove();
                contenedorTarea.find('.eliminar-imagen').remove();

                // ❌ Ocultar campo de selección de fotos
                contenedorTarea.find('.custom-file').hide();

                // ❌ Ocultar fila superior de materiales (select + input + botón)
                contenedorTarea.find('.material-select').closest('.form-row').hide();

                // ❌ Ocultar fila superior de mano de obra (select + input + botón)
                contenedorTarea.find('.mano-obra-select').closest('.form-row').hide();

                // ✅ Solo mostrar botón "Volver"
                $('#btn-agregar-tarea').hide();
                $('.btn-guardar-visita').hide();
                $('.btn-generar-presupuesto').hide();
                $('.btn-cancelar-visita').show().text('Volver');

                // 🎯 Expandir solo esta tarea
                $(`#accordionTareas .collapse`).removeClass('show');
                contenedorTarea.addClass('show');

                // Eliminar columna "Acción" de materiales
                contenedorTarea.find('.materiales-table thead tr th:last-child').remove();

                // Eliminar columna "Acción" de mano de obra
                contenedorTarea.find('.mano-obra-table thead tr th:last-child').remove();           

            }

        });

    }

    // ======= FIN POBLACIÓN BACKEND =======
    hayCambios = false;
    controlarBotonGenerarPresupuesto(); 
    
    function obtenerTextoVisibleSelect(selector, placeholders = []) {
      const $select = $(selector).first();
      if (!$select.length) return '';

      const invalidos = new Set(
        ['']
          .concat(placeholders || [])
          .map((item) => String(item ?? '').replace(/\s+/g, ' ').trim())
      );

      const candidatos = [];

      const textoSeleccionado = String($select.find('option:selected').text() || '')
        .replace(/\s+/g, ' ')
        .trim();
      if (textoSeleccionado) {
        candidatos.push(textoSeleccionado);
      }

      const selectId = String($select.attr('id') || '').trim();
      if (selectId !== '') {
        const textoSelect2 = String($(`#select2-${selectId}-container`).first().text() || '')
          .replace(/\s+/g, ' ')
          .trim();
        if (textoSelect2) {
          candidatos.push(textoSelect2);
        }
      }

      for (const texto of candidatos) {
        if (texto && !invalidos.has(texto)) {
          return texto;
        }
      }

      return '';
    }

    // ======= Obtiene los datos para el presupuesto =======
    function recolectarDatosParaPresupuesto() {
      // === CLIENTE ===
      const razon_social = $('#razon_social').val() || null;
      const cuit = $('#cuit').val() || null;
      const contacto = $('#contacto_obra').val() || null;
      const email = $('#email_contacto_obra').val() || null;
      const telefono = $('#tel_contacto_obra').val() || null;

      const calle = obtenerTextoVisibleSelect('#calle_visita', ['Calle']);
      const altura = $('#altura_visita').val()?.trim() || '';
      const localidad = obtenerTextoVisibleSelect('#localidad_visita', ['Localidad']);
      const partido = obtenerTextoVisibleSelect('#partido_visita', ['Partido']);
      const provincia = obtenerTextoVisibleSelect('#provincia_visita', ['Provincia']);
      const cp = $('#cp_visita').val()?.trim() || '';

      const direccion = [calle, altura, localidad, partido, provincia, cp].filter(Boolean).join(', ');

      const cliente = {
        razon_social,
        cuit,
        direccion: direccion || null,
        contacto,
        email,
        telefono
      };

      // === OBRA ===
      const obra = {
        titulo: $('#titulo_obra').val() || $('#nombre_obra').val() || null,
        direccion: direccion || null,
        fecha: $('#fecha_visita').val() || null,
        descripcion: $('#descripcion_obra').val()
                  || $('#detalle_visita').val()
                  || $('#descripcion').val()
                  || null
      };

      // === PRESUPUESTO ===
      const presupuesto = {
        id_previsita: $('#id_previsita').val() || null,
        id_visita: $('#id_visita').val() || null,
        estado_visita: $('#estado_visita').val() || null,
        agente: $('#agente_tecnico').val() || null
      };

      // === TAREAS ===
      const tareas = [];

      $('#accordionTareas > .card').each(function(index) {
        const $card = $(this);
        const idx = index + 1;

        const tarea = {
          id_tarea: $card.find('input[name="id_tarea[]"]').val() || null,
          descripcion: $card.find('textarea.tarea-descripcion').val().trim(),
          materiales: [],
          mano_obra: []
        };

        // --- Materiales
        $card.find('.materiales-table tbody tr').each(function() {
          const $fila = $(this);
          if ($fila.hasClass('fila-vacia-materiales')) return;

          const id = $fila.data('material-id');
          const nombre = $fila.find('td:nth-child(2)').text().trim();
          const cantidad = parseFloat($fila.find('td:nth-child(3)').text().replace(',', '.')) || 0;

          const precio_unitario = parseFloat($fila.data('precio_unitario')) || 0;
          const unidad_medida = $fila.data('unidad_medida') || '';
          const unidad_venta = $fila.data('unidad_venta') || '';
          const contenido = parseFloat($fila.data('contenido')) || 0;
          const log_edicion = $fila.data('log_edicion') || null;
          const log_alta = $fila.data('log_alta') || null;

          tarea.materiales.push({
            id_material: id,
            nombre: nombre,
            cantidad: cantidad,
            precio_unitario: precio_unitario,
            unidad_medida: unidad_medida,
            unidad_venta: unidad_venta,
            contenido: contenido,
            log_edicion: log_edicion,
            log_alta: log_alta
          });
        });

        // --- Mano de Obra
        $card.find('.mano-obra-table tbody tr').each(function() {
          const $fila = $(this);
          if ($fila.hasClass('fila-vacia-mano-obra')) return;

          const id = $fila.find('input[name="mano_obra_id[]"]').val();
          const cantidad = parseFloat($fila.find('input[name="mano_obra_cantidad[]"]').val()) || 0;
          const dias = parseFloat($fila.find('input[name="mano_obra_dias[]"]').val()) || 1;
          const observacion = $fila.find('input[name="mano_obra_observacion[]"]').val().trim();
          const nombre = $fila.find('td:nth-child(2) span').text().trim();

          const jornal_valor = parseFloat($fila.data('jornal_valor')) || 0;
          const updated_at = $fila.data('updated_at') || null;
          const jornal_id = $fila.data('jornal_id') || null;


          tarea.mano_obra.push({
            id_jornal: id,
            nombre: nombre,
            cantidad: cantidad,
            dias: dias,
            observacion: observacion,
            jornal_valor: jornal_valor,
            updated_at: updated_at,
            jornal_id: jornal_id
          });
        });

        tareas.push(tarea);
      });

      return {
        cliente,
        obra,
        presupuesto,
        tareas
      };
    }

    /* -------------------------------------------------
      CÁLCULO DE SUBTOTALES POR FILA (Material / Mano)
      ------------------------------------------------- */

    /**
     * Calcula y actualiza el subtotal de una fila de materiales.
     * subtotal = cantidad×precio + extraFijo + %extra sobre el resultado.
     */
    function calcularFilaMaterial($tr) {
      const cantidad = parseFloat($tr.find('.cantidad-material').val()) || 0;
      const precio   = parseFloat($tr.find('.precio-unitario').val())   || 0;
    
      let subtotal = cantidad * precio;
    
      const porcentajeExtra = parseFloat($tr.find('.porcentaje-extra').val()) || 0;
      subtotal += subtotal * (porcentajeExtra / 100);
    
      $tr.find('.subtotal-material').text(formatMoney(subtotal));
    }
    window.calcularFilaMaterial     = calcularFilaMaterial;
    
    /**
     * Calcula y actualiza el subtotal de una fila de mano de obra.
     * subtotal = cantidad×valorJornal + extraFijo + %extra sobre el resultado.
     */
    function calcularFilaManoObra($tr) {
      // Operarios (antes "cantidad")
      const operarios = parseFloat(String($tr.find('.cantidad-mano-obra').val()).replace(',', '.')) || 0;
    
      // Días (nuevo)
      const dias = parseFloat(String($tr.find('.dias-mano-obra').val()).replace(',', '.')) || 0;
    
      // Jornales = Operarios × Días (derivado SIEMPRE)
      const jornalesCalc = operarios * dias;
    
      // Escribimos el valor calculado en el input de jornales para que se vea reflejado
      const $jornalesInput = $tr.find('.jornales-mano-obra');
      if ($jornalesInput.length) {
        $jornalesInput.val(jornalesCalc);
      }
    
      // Valor jornal
      const jornal = parseFloat(String($tr.find('.valor-jornal').val()).replace(',', '.')) || 0;
    
      // Base = jornales × valor
      const base = jornalesCalc * jornal;
    
      // % extra
      const porcentajeExtra = parseFloat(String($tr.find('.porcentaje-extra').val()).replace(',', '.')) || 0;
      const subtotal = base + (base * (porcentajeExtra / 100));
    
      $tr.find('.subtotal-mano').text(formatMoney(subtotal));
    }
    window.calcularFilaManoObra = calcularFilaManoObra;
    
     
    /**
     * Formatea un número como moneda en formato español:
     * miles con punto, decimales con coma.
     * @param {number} valor - El valor numérico a formatear.
     * @returns {string} - Ejemplo: "$20.561.100,12"
     */
    function formatMoney(valor) {
      const [intPart, decPart] = valor.toFixed(2).split('.');
      const intWithSep = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
      return '$' + intWithSep + ',' + decPart;
    }

    // === nuevo codigo 24: helpers de color por % utilidad ===
    function clasePorUtilidad(p) {
      if (p <= 12) return 'util-inaceptable';   // rojo
      if (p <= 17) return 'util-aceptable';     // ámbar
      if (p <= 23) return 'util-muy';           // verde
      return 'util-margen';                     // verde intenso
    }

    function aplicarColorUtilidad($card, numeroTarea, p) {
      const $targets = $card.find(`#porcentajetarea-${numeroTarea}, #subt-tarea-${numeroTarea}`);

      // Limpieza agresiva: quitamos nuestras clases y posibles bg-* previas
      $targets.removeClass('util-inaceptable util-aceptable util-muy util-margen bg-danger bg-warning bg-success');

      // Aplica la clase correspondiente
      $targets.addClass(clasePorUtilidad(p));
    }

    /**
     * Actualiza los subtotales de materiales, mano de obra y total de una tarjeta de tarea.
     * @param {jQuery} $card El contenedor .tarea-card correspondiente.
     */
    function actualizarSubtotalesBloque($card) {
      // --- Materiales: suma de filas (excluye Subtotal y Otros) ---
      let sumaMat = 0;
      $card.find('.tarea-materiales tbody tr')
        .not('.fila-subtotal, .fila-otros-materiales')
        .each(function () {
          const raw = $(this).find('.subtotal-material').text().trim();
          if (!raw) return;
          const v = parseFloat(raw.replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.')) || 0;
          sumaMat += v;
        });
    
      const otrosMat = parseFloat(String($card.find('.input-otros-materiales').val()).replace(',', '.')) || 0;
    
      // % utilidad materiales (si está vacío usa default)
      const utilMatPct = parseFloat(
        $card.find('.tarea-materiales .fila-subtotal input.utilidad-global-materiales').val()
      ) || porcentajesPorDefecto.materiales;
    
      // Utilidad contable (base × %)
      const utilMatBase = sumaMat * (utilMatPct / 100);
    
      // Lo que muestra el botón "Subtotal Util. Mat." = (base × %) + otros
      const utilMatParaMostrar = utilMatBase + otrosMat;
    
      // Subtotal mostrado en la fila de la tabla: base + utilidad + otros
      const subtotalMatMostrado = sumaMat + utilMatBase + otrosMat;
    
      // Pintar
      $card.find('.tarea-materiales .fila-subtotal td:last-child b').text(formatMoney(subtotalMatMostrado));
      $card.find('.subt-util-materiales').text('Subtotal Util. Mat.: ' + formatMoney(utilMatParaMostrar));
    
      // --- Mano de Obra ---
      let sumaMan = 0;
      $card.find('.tarea-mano-obra tbody tr')
        .not('.fila-subtotal, .fila-otros-mano')
        .each(function () {
          const raw = $(this).find('.subtotal-mano').text().trim();
          if (!raw) return;
          const v = parseFloat(raw.replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.')) || 0;
          sumaMan += v;
        });
    
      const otrosMo = parseFloat(String($card.find('.input-otros-mano').val()).replace(',', '.')) || 0;
    
      const utilMoPct = parseFloat(
        $card.find('.tarea-mano-obra .fila-subtotal input.utilidad-global-mano-obra').val()
      ) || porcentajesPorDefecto.mano_obra;
    
      const utilMoBase = sumaMan * (utilMoPct / 100);
      const utilMoParaMostrar = utilMoBase + otrosMo;      // (base × %) + otros
      const subtotalMoMostrado = sumaMan + utilMoBase + otrosMo;
    
      $card.find('.tarea-mano-obra .fila-subtotal td:last-child b').text(formatMoney(subtotalMoMostrado));
      $card.find('.subt-util-manoobra').text('Subtotal Util. MO.: ' + formatMoney(utilMoParaMostrar));
    
      // 🔹 NUEVO: “Sub Util. Mat.+MO.” debe ser la suma visible (cada util + sus “otros”)
      const utilTotalParaMostrar = utilMatParaMostrar + utilMoParaMostrar;
      $card.find('.subt-util-total').text('Sub Util. Mat.+MO.: ' + formatMoney(utilTotalParaMostrar));
    }
    window.actualizarSubtotalesBloque = actualizarSubtotalesBloque; 
    
    /**
     * Calcula y actualiza los botones de utilidades y subtotal de tarea para una tarjeta de tarea.
     * @param {number} numeroTarea - El índice de la tarea (1-based).
     * @param {jQuery} $card - El contenedor .tarea-card correspondiente.
     */
    function actualizarTotalesPorTarea(numeroTarea, $card) {
      // 1) Sumas base de filas (sin "Otros")
      let sumaMatFilas = 0;
      $card.find('.tarea-materiales tbody tr')
        .not('.fila-subtotal,.fila-otros-materiales')
        .each(function () {
          const raw = $(this).find('.subtotal-material').text().trim();
          if (!raw) return;
          const v = parseFloat(raw.replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.'));
          if (!isNaN(v)) sumaMatFilas += v;
        });
    
      let sumaMoFilas = 0;
      $card.find('.tarea-mano-obra tbody tr')
        .not('.fila-subtotal,.fila-otros-mano')
        .each(function () {
          const raw = $(this).find('.subtotal-mano').text().trim();
          if (!raw) return;
          const v = parseFloat(raw.replace(/[^0-9,.-]/g, '').replace(/\./g, '').replace(',', '.'));
          if (!isNaN(v)) sumaMoFilas += v;
        });
    
      // 2) Otros (montos planos)
      const otrosMat = parseFloat(String($card.find('.input-otros-materiales').val()).replace(',', '.')) || 0;
      const otrosMo  = parseFloat(String($card.find('.input-otros-mano').val()).replace(',', '.')) || 0;
    
      // 3) % utilidad global
      const utilMatPct = parseFloat($card.find('.tarea-materiales .fila-subtotal input.utilidad-global-materiales').val()) || porcentajesPorDefecto.materiales;
      const utilMoPct  = parseFloat($card.find('.tarea-mano-obra .fila-subtotal input.utilidad-global-mano-obra').val())   || porcentajesPorDefecto.mano_obra;
    
      // 4) Utilidades contables (base × %) — se usan para impuestos y util real
      const utilMatContable = sumaMatFilas * (utilMatPct / 100);
      const utilMOContable  = sumaMoFilas  * (utilMoPct  / 100);
      const utilTotal       = utilMatContable + utilMOContable;
    
      // 🔹 Lo que muestran los botones de utilidades (visible)
      const matUtilParaMostrar = utilMatContable + otrosMat; // (base × %) + otros
      const moUtilParaMostrar  = utilMOContable  + otrosMo;  // (base × %) + otros
      const utilTotalParaMostrar = matUtilParaMostrar + moUtilParaMostrar;
    
      // 5) Totales de tarea (base para cálculos y UI)
      const totalBase     = (sumaMatFilas + utilMatContable) + (sumaMoFilas + utilMOContable); // cálculos/impuestos
      const totalMostrado = (sumaMatFilas + utilMatContable + otrosMat) + (sumaMoFilas + utilMOContable + otrosMo); // UI
    
      // 6) Refrescar botones
      $(`#subt-util-materiales-${numeroTarea}`).html(`Subtotal Util. Mat.: <strong>${formatMoney(matUtilParaMostrar)}</strong>`);
      $(`#subt-util-manoobra-${numeroTarea}`).html(`Subtotal Util. MO.: <strong>${formatMoney(moUtilParaMostrar)}</strong>`);
      $(`#subt-util-total-${numeroTarea}`).html(`Sub Util. Mat.+MO.: <strong>${formatMoney(utilTotalParaMostrar)}</strong>`);
    
      $(`#subt-tarea-${numeroTarea}`)
        .data('monto', totalBase)        // cálculos (impuestos, % util, total general)
        .data('mostrado', totalMostrado) // total “visible” con Otros
        .html(`Subtotal Tarea ${numeroTarea}: <strong>${formatMoney(totalMostrado)}</strong>`);
    
        // 7) Impuestos / costos
        // IIBB sobre el Subtotal Tarea mostrado (incluye “Otros”) con IVA 21%
        const iibb = totalMostrado * 1.21 * 0.03;

        // Ganancias 35%: 35% de la suma de utilidades visibles (Mat.+Otros + MO.+Otros)
        const ganancia35 = utilTotalParaMostrar * 0.35;

        // Costo inv. 3% sobre base contable
        const costoInv3  = totalMostrado * 0.03;

        // 🔹 Impuesto al cheque: Subtotal Tarea mostrado × 0,0012 (0,12%)
        const impCheque  = totalMostrado * 0.012;
    
      const utilRealFinal = utilTotalParaMostrar - (iibb + ganancia35 + impCheque + costoInv3);
    
      // % utilidad (reflejando “Otros” en el denominador)
      const porcentajeUtilidad = (totalMostrado > 0)
        ? (utilRealFinal / totalMostrado) * 100
        : 0;
    
      $(`#iibb-${numeroTarea}`).html(`IIBB:<strong> ${formatMoney(iibb)}</strong>`);
      $(`#ganancias-${numeroTarea}`).html(`Ganancias 35%:<strong> ${formatMoney(ganancia35)}</strong>`);
      $(`#cheque-${numeroTarea}`).html(`Imp. cheque:<strong> ${formatMoney(impCheque)}</strong>`);
      $(`#inversion-${numeroTarea}`).html(`Costo inv. 3%:<strong> ${formatMoney(costoInv3)}</strong>`);
      $(`#utilfinal-${numeroTarea}`).html(`Util real final:<strong> ${formatMoney(utilRealFinal)}</strong>`);
      $(`#porcentajetarea-${numeroTarea}`).html(`% Utilidad:<strong> ${porcentajeUtilidad.toFixed(2)}%</strong>`);
    
      // 8) Colores
      const $targets = $(`#porcentajetarea-${numeroTarea}, #subt-tarea-${numeroTarea}`);
      $targets.removeClass('util-inaceptable util-aceptable util-muy util-margen');
      if (porcentajeUtilidad <= 12)      $targets.addClass('util-inaceptable');
      else if (porcentajeUtilidad <= 17) $targets.addClass('util-aceptable');
      else if (porcentajeUtilidad <= 23) $targets.addClass('util-muy');
      else                               $targets.addClass('util-margen');
    }
    window.actualizarTotalesPorTarea  = actualizarTotalesPorTarea;
       
    /**
     * Recorre todos los botones de subtotal de tarea y suma sus valores,
     * luego actualiza el span .presupuesto-total-valor.
     */
    function actualizarTotalGeneral() {
      let totalMostrar = 0; // con "Otros"
      let totalBase    = 0; // sin "Otros" (por si lo necesitás en otro lado)
    
      $('#contenedorPresupuestoGenerado .tarea-card').each(function () {
        const $card = $(this);
        // solo tareas incluidas
        if (!$card.find('.incluir-en-total').prop('checked')) return;
    
        const $btn = $card.find('[id^="subt-tarea-"]');
    
        const base     = parseFloat($btn.data('monto'))     || 0; // sin "Otros"
        const mostrado = parseFloat($btn.data('mostrado'))  || base; // con "Otros" (fallback)
    
        totalBase    += base;
        totalMostrar += mostrado;
      });
    
      // Mostrar el total del presupuesto CON "Otros"
      $('.presupuesto-total-valor').text(formatMoney(totalMostrar));
    
      // (opcional) lo dejamos a mano si querés inspeccionarlo en consola
      window.__TOTAL_BASE__     = totalBase;
      window.__TOTAL_MOSTRADO__ = totalMostrar;
    }
    window.actualizarTotalGeneral     = actualizarTotalGeneral;
    
    function renderizarPresupuestoDesdeDatos(datos){   
      const contenedor = $('#contenedorPresupuestoGenerado');
      contenedor.empty();
      const hoy = new Date();
      
      datos.tareas.forEach((tarea, index) => {
        const numeroTarea = index + 1;
        const descripcion = tarea.descripcion || '';
        const tituloTarea = (typeof window.resumirTituloTareaPresupuesto === 'function')
          ? window.resumirTituloTareaPresupuesto(descripcion)
          : descripcion;
    
      // HTML dinámico de materiales
      let htmlMateriales = '';
      tarea.materiales.forEach((mat) => {
        const precioUnitario = mat.precio_unitario ?? 0;
        const cantidad = mat.cantidad ?? 0;

        let claseAlerta = '';
        let fechaRef = null;
        let readonly = '';

        if (mat.log_edicion) {
          fechaRef = new Date(mat.log_edicion);
        } else if (mat.log_alta) {
          fechaRef = new Date(mat.log_alta);
        }

        // Si está vencido (>30 días) → bg-danger (editable)
        // Si NO está vencido → bg-success + readonly
        if (fechaRef && (hoy - fechaRef) > (30 * 24 * 60 * 60 * 1000)) {
          claseAlerta = 'bg-danger';
        } else {
          claseAlerta = 'bg-success';
          readonly = 'readonly';
        }

        htmlMateriales += `
        <tr data-material-id="${mat.id_material ?? ''}">
          <td>${mat.nombre || ''}</td>
          <td>
            <input
              type="number"
              class="form-control form-control-sm cantidad-material"
              value="${cantidad}"
              min="0"
              step="any"
            >
          </td>
          <td>
            <input
              type="number"
              class="form-control form-control-sm precio-unitario ${claseAlerta}"
              value="${precioUnitario}"
              min="0"
              step="any"
              ${readonly}
            >
          </td>
          <td>
            <input
              type="number"
              class="form-control form-control-sm porcentaje-extra"
              value="0"
              min="0"
              step="any"
            >
          </td>
          <td class="text-right subtotal-material">$0.00</td>
        </tr>`;    

      });

      
      // HTML dinámico de mano de obra
      let htmlManoObra = '';
      tarea.mano_obra.forEach((mo) => {
        const valorJornal = mo.jornal_valor ?? 0;
        const cantidadMo  = mo.cantidad ?? 0;

        // Jornales iniciales: los trae la visita.
        // Priorizamos mo.jornales si viene; si no, intentamos cantidad×días si viene mo.dias; si no, fallback cantidad.
        const diasMo     = (mo.dias ?? null);
        const jornalesMo = (mo.jornales ?? (diasMo != null ? (cantidadMo * diasMo) : cantidadMo)) ?? 0;

        let claseAlerta = '';
        let readonly = '';

        if (mo.updated_at) {
          const fechaMO = new Date(mo.updated_at);
          if ((hoy - fechaMO) > (30 * 24 * 60 * 60 * 1000)) {
            claseAlerta = 'bg-danger';
          } else {
            claseAlerta = 'bg-success';
            readonly = 'readonly';
          }
        } else {
          claseAlerta = 'bg-success';
          readonly = 'readonly';
        }

        htmlManoObra += `
        <tr data-jornal_id="${mo.jornal_id ?? ''}">
          <td>${mo.nombre || ''}</td>
        
          <!-- Operarios (internamente cantidad) -->
          <td>
            <input
              type="number"
              class="form-control form-control-sm cantidad-mano-obra"
              value="${cantidadMo}"
              min="0"
              step="any"
            >
          </td>
        
          <!-- Días -->
          <td>
            <input
              type="number"
              class="form-control form-control-sm dias-mano-obra"
              value="${diasMo != null ? diasMo : 0}"
              min="0"
              step="any"
            >
          </td>
        
          <!-- Jornales (derivado: Operarios × Días) -->
          <td>
            <input
              type="number"
              class="form-control form-control-sm jornales-mano-obra"
              value="${jornalesMo}"
              min="0"
              step="any"
              readonly
            >
          </td>
        
          <td>
            <input
              type="number"
              class="form-control form-control-sm valor-jornal ${claseAlerta}"
              value="${valorJornal}"
              min="0"
              step="any"
              ${readonly}
            >
          </td>
        
          <td>
            <input
              type="number"
              class="form-control form-control-sm porcentaje-extra"
              value="0"
              min="0"
              step="any"
            >
          </td>
        
          <td class="text-right subtotal-mano">$0.00</td>
        </tr>`;
        

      
      });

       const claseUtilidades = mostrarVistaDetallada ? '' : 'd-none';
       const claseImpuestos  = mostrarVistaDetallada ? '' : 'd-none';
      const detalleEditorHtml = (typeof window.renderDetalleTareaEditorHtml === 'function')
        ? window.renderDetalleTareaEditorHtml(descripcion)
        : `<textarea class="form-control form-control-sm tarea-descripcion" rows="5">${descripcion}</textarea>`;

      const htmlTarea = `
        <div class="tarea-card">
        <div class="tarea-encabezado">
          <span>
            <i class="fas fa-tasks"></i>
            <b>Tarea ${numeroTarea}: ${tituloTarea}</b>
          </span>
          <label class="incluir-presupuesto-label">
            <input type="checkbox" class="incluir-en-total" checked>
            <span>Incluído en el presupuesto</span>
          </label>
        </div>

        <div class="container-fluid px-3 pt-3">
          <div class="row tarea-card-cuerpo">
            <!-- Columna izquierda -->
            <div class="col-md-4 tarea-columna-izquierda">
              <!-- Detalle -->
              <div class="mb-2 tarea-columna-panel tarea-columna-panel-detalle">
                <label class="mb-0"><b>Detalle de la tarea</b></label>
                ${detalleEditorHtml}
              </div>
              <!-- Fotos (PRESUPUESTO: dropzone + input oculto, sin “Seleccionar fotos”) -->
              <div class="mb-2 tarea-columna-panel tarea-columna-panel-imagenes">
                <label class="mb-0"><b>Imágenes</b></label>
              
                <!-- input oculto por tarea (queda igual) -->
                <input 
                  type="file" 
                  class="presu-fotos d-none"
                  id="presu_fotos_tarea_${numeroTarea}" 
                  multiple 
                  accept="image/*" 
                  data-index="${numeroTarea}"
                />
                
                <!-- dropzone gris + previews adentro -->
                <div class="presu-dropzone border rounded bg-light p-3 text-muted mb-2"
                     data-index="${numeroTarea}"
                     style="min-height: 100px;">
                  <div class="w-100 d-flex align-items-center justify-content-center text-center">
                    <em>Arrastre aquí las imágenes o haga click.</em>
                  </div>
                  <div class="row presu-preview-fotos m-0 mt-2" id="presu_preview_${numeroTarea}"></div>
                </div>

              </div>
                          
            </div>

            <!-- Columna derecha -->
            <div class="col-md-8 tarea-columna-derecha d-flex flex-column justify-content-start">
              <!-- Materiales -->
              <div class="tarea-materiales mb-0 mt-0 pt-0">
                <div class="bloque-titulo mt-0 pt-0 mb-0">Materiales</div>
                <table class="tabla-presupuesto tabla-presupuesto-sm">
                  <thead>
                    <tr>
                      <th>Material</th>
                      <th>Cantidad</th>
                      <th>Precio Unitario</th>
                      <th>% Extra</th>
                      <th>Subtotal</th>
                    </tr>
                  </thead>              
                  <tbody>
                    ${htmlMateriales}
                    <tr class="fila-otros-materiales">
                    <td><b>Otros</b></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-right">
                      <input
                        type="number"
                        min="0"
                        step="0.01"
                        class="form-control form-control-sm input-otros-materiales"
                        id="otros-mat-${numeroTarea}"
                        value="${tarea.otros_materiales ?? 0}"
                      >
                      </td>
                    </tr>                 
                    <tr class="fila-subtotal">
                      <td colspan="3" class="text-right"><b>Subtotal Materiales</b></td>
                      <td>
                        <input
                        type="number"
                        class="form-control form-control-sm utilidad-global-materiales"
                        min="0"
                        value="${tarea.utilidad_materiales ?? ''}"
                        placeholder="%"
                        />                   
                      </td>
                      <td class="text-right"><b>$0.00</b></td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Mano de Obra -->
              <div class="tarea-mano-obra">
                <div class="bloque-titulo mt-0">Mano de Obra</div>
                <table class="tabla-presupuesto tabla-presupuesto-sm">
                  <thead>
                    <tr>
                      <th>Tipo</th>
                      <th>Operarios</th>
                      <th>Días</th>
                      <th>Jornales</th>
                      <th>Valor Jornal</th>
                      <th>% Extra</th>
                      <th>Subtotal</th>
                    </tr>
                  </thead>                          
                  <tbody>
                    ${htmlManoObra}
                    <tr class="fila-otros-mano">
                    <td><b>Otros</b></td>
                    <td></td> <!-- Operarios -->
                    <td></td> <!-- Días -->
                    <td></td> <!-- Jornales -->
                    <td></td> <!-- Valor Jornal -->
                    <td></td> <!-- % Extra -->
                    <td class="text-right">
                      <input
                        type="number"
                        min="0"
                        step="0.01"
                        class="form-control form-control-sm input-otros-mano"
                        id="otros-mo-${numeroTarea}"
                        value="${tarea.otros_mano_obra ?? 0}"
                      >
                    </td>
                  </tr>                                                   
                    <tr class="fila-subtotal">
                    <td colspan="5" class="text-right"><b>Subtotal Mano de Obra</b></td>
                    <td>
                      <input
                        type="number"
                        class="form-control form-control-sm utilidad-global-mano-obra"
                        min="0"
                        value="${tarea.utilidad_mano_obra ?? ''}"
                        placeholder="%"
                      />
                    </td>
                    <td class="text-right"><b>$0.00</b></td>
                  </tr>               
                  </tbody>
                </table>
              </div>

              <div class="tarea-total d-flex flex-column align-items-end px-3">
          <!-- Botones de utilidades: visibilidad depende del ID habilitado -->
          <div class="utilidades-extra w-100">
            <button class="col-2 btn-total-tarea subt-util-materiales w-100 ${claseUtilidades}" id="subt-util-materiales-${numeroTarea}">
              Subtotal Util. Mat.: $0,00
            </button>
          </div>
          <div class="utilidades-extra w-100">
            <button class="col-2 btn-total-tarea subt-util-manoobra w-100 ${claseUtilidades}" id="subt-util-manoobra-${numeroTarea}">
              Subtotal Util. MO.: $0,00
            </button>
          </div>
          <div class="utilidades-extra w-100">
            <button class="col-2 btn-total-tarea subt-util-total w-100 ${claseUtilidades}" id="subt-util-total-${numeroTarea}">
              Sub Util. Mat.+MO.: $0,00
            </button>
          </div>
          <div class="d-flex justify-content-end w-100">
            <button class="col-2 btn-total-tarea w-100 subt-util-final ${claseUtilidades}" id="utilfinal-${numeroTarea}">Util real final: $0,00</button>
          </div>
          <div class="d-flex justify-content-end w-100">
            <button class="col-2 btn-total-tarea porcentaje-tarea w-100 porcentajetarea ${claseUtilidades}" id="porcentajetarea-${numeroTarea}">% : <strong>$0,00</strong></button>
          </div>
        </div>
            </div>
          </div>
        </div>

        <div class="tarea-barra-inferior d-flex align-items-end px-3 pb-2">
          <div class="tarea-inline-actions d-flex align-items-center">
            <button
              type="button"
              id="btnGuardarTarea_${numeroTarea}"
              class="btn btn-warning mr-2 btn-guardar-tarea btn-tarea"
              data-nro="${numeroTarea}">
              <i class="fas fa-save"></i> Guardar tarea
            </button>
            <button
              type="button"
              id="btnTraerTarea_${numeroTarea}"
              class="btn btn-warning btn-traer-tarea btn-tarea"
              data-nro="${numeroTarea}">
              <i class="fas fa-download"></i> Traer tarea
            </button>
          </div>

          <div class="fila-impuestos flex-grow-1" id="fila-impuestos-${numeroTarea}">
            <div class="tarea-impuestos-lista">
              <div class="col-auto pr-1 pl-0 ${claseImpuestos}">
                <button type="button" class="btn bg-secondary w-100" id="iibb-${numeroTarea}">IIBB: $0,00</button>
              </div>
              <div class="col-auto pr-1 pl-0 ${claseImpuestos}">
                <button type="button" class="btn bg-secondary w-100" id="ganancias-${numeroTarea}">Ganancias 35%: $0,00</button>
              </div>
              <div class="col-auto pr-1 pl-0 ${claseImpuestos}">
                <button type="button" class="btn bg-secondary w-100" id="cheque-${numeroTarea}">Imp. cheque: $0,00</button>
              </div>
              <div class="col-auto pr-1 pl-0 ${claseImpuestos}">
                <button type="button" class="btn bg-secondary w-100" id="inversion-${numeroTarea}">Costo inv. 3%: $0,00</button>
              </div>
              <div class="col-auto pr-1 pl-0 ${claseImpuestos}">
                <button type="button" class="btn bg-secondary w-100" id="retiva-${numeroTarea}">Ret. IVA mat: <strong>$0,00</strong></button>
              </div>
            </div>

            <div class="tarea-subtotal-col">
              <button type="button" class="btn-total-tarea w-100 util-muy mt-0" id="subt-tarea-${numeroTarea}">
                Subtotal Tarea ${numeroTarea}: $0,00
              </button>
            </div>
          </div>
        </div>
      </div>`;
        
        contenedor.append(htmlTarea);

        // === bloquear/permitir edición de % de utilidad global por vistaDetallada ===
        // referencia a la card recién inyectada
        const $cardRecienAgregada = contenedor.find('.tarea-card').last();

        if (typeof window.initDetalleTareaRichEditors === 'function') {
          window.initDetalleTareaRichEditors($cardRecienAgregada, { triggerInput: false });
        }

        // inputs a controlar
        const $utilMat = $cardRecienAgregada.find('.utilidad-global-materiales');
        const $utilMO  = $cardRecienAgregada.find('.utilidad-global-mano-obra');

        // si NO tiene vista detallada -> solo lectura + estilo gris
        if (!mostrarVistaDetallada) {
          $utilMat.prop('readonly', true).addClass('input-sololectura');
          $utilMO.prop('readonly', true).addClass('input-sololectura');
        } else {
          // si tiene vista detallada -> editable normal
          $utilMat.prop('readonly', false).removeClass('input-sololectura');
          $utilMO.prop('readonly', false).removeClass('input-sololectura');
        }
        
        if (!mostrarVistaDetallada) {
          // oculta solo los impuestos, deja visible "Subtotal Tarea"
          $(`#iibb-${numeroTarea}, #ganancias-${numeroTarea}, #cheque-${numeroTarea}, #inversion-${numeroTarea}, #retiva-${numeroTarea}`)
            .closest('.col-auto')
            .addClass('d-none');
        }

      });
    
    // Bloque total general con botón Guardar
    const htmlTotal = `
    <div class="presupuesto-total-card">
      <div class="presupuesto-total-row">
        <div class="presupuesto-total-actions">
          <button id="btn-guardar-presupuesto" type="button" class="btn btn-success mr-2">
            <i class="fas fa-save"></i> Guardar 
          </button>
  
          <button type="button" class="btn btn-primary mr-2 btn-emitir-presupuesto" disabled>
            <i class="fas fa-file-pdf"></i> Generar documento 
          </button>
        </div>
  
        <div class="presupuesto-total-label">
          <span class="presupuesto-total-title">TOTAL PRESUPUESTO:</span>
          <span class="presupuesto-total-valor">$0.00</span>
        </div>
      </div>
    </div>`;
  
      contenedor.append(htmlTotal);

      // === Dirty state del presupuesto actual
      contenedor
        .off('input.presuDirty change.presuDirty', 'textarea, input, .incluir-en-total')
        .on('input.presuDirty change.presuDirty', 'textarea, input, .incluir-en-total', function () {
          const $el = $(this);

          if ($el.attr('id') === 'btn-guardar-presupuesto') return;
          if ($el.hasClass('btn-emitir-presupuesto')) return;
          marcarPresupuestoComoModificado();
        });      

       // === Guardar presupuesto (delegado sobre el contenedor)
      contenedor.off('click', '#btn-guardar-presupuesto')
      .on('click', '#btn-guardar-presupuesto', async function (e) {
      e.preventDefault();

        if (edicionComercialBloqueada()) {
          mostrarAlertaBloqueoEdicionComercial();
          return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true);

        try {
          const $root = $('#contenedorPresupuestoGenerado');

          const id_presupuesto = Number($root.data('id_presupuesto')) || null;
          const id_previsita   = $('#id_previsita').val() || null;
          const id_visita      = $('#id_visita').val() || null;

          const tareas = [];

          // Armar payload JSON (sin archivos aún)
          $root.find('.tarea-card').each(function (index) {
            const $card = $(this);
            const nro = index + 1;

            const descripcion       = (typeof window.obtenerDetalleTareaHtmlDesdeCard === 'function'
              ? window.obtenerDetalleTareaHtmlDesdeCard($card)
              : ($card.find('textarea').first().val() || '')).trim();
            const incluir_en_total  = $card.find('.incluir-en-total').is(':checked') ? 1 : 0;
            const utilidad_materiales = parseFloat($card.find('.tarea-materiales .utilidad-global-materiales').val()) || null;
            const utilidad_mano_obra  = parseFloat($card.find('.tarea-mano-obra .utilidad-global-mano-obra').val()) || null;
            const otros_materiales    = parseFloat($card.find('.tarea-materiales .input-otros-materiales').val()) || 0;
            const otros_mano_obra     = parseFloat($card.find('.tarea-mano-obra .input-otros-mano').val()) || 0;

            const materiales = [];
            $card.find('.tarea-materiales tbody tr').not('.fila-subtotal,.fila-otros-materiales').each(function () {
              const $tr = $(this);
              const nombre            = $tr.find('td').eq(0).text().trim();
              const cantidad          = parseFloat($tr.find('.cantidad-material').val()) || 0;
              const precio_unitario   = parseFloat($tr.find('.precio-unitario').val()) || 0;
              const porcentaje_extra  = parseFloat($tr.find('.porcentaje-extra').val()) || 0;
              const id_material       = $tr.data('material_id') || $tr.data('material-id') || null;
              if (nombre || cantidad || precio_unitario) {
                materiales.push({ id_material, nombre, cantidad, precio_unitario, porcentaje_extra });
              }
            });

            const mano_obra = [];
            $card.find('.tarea-mano-obra tbody tr').not('.fila-subtotal,.fila-otros-mano').each(function () {
              const $tr = $(this);
              const nombre            = $tr.find('td').eq(0).text().trim();
              const cantidad          = parseFloat($tr.find('.cantidad-mano-obra').val()) || 0;
              const jornal_valor      = parseFloat($tr.find('.valor-jornal').val()) || 0;
              const porcentaje_extra  = parseFloat($tr.find('.porcentaje-extra').val()) || 0;
              const jornal_id         = $tr.data('jornal_id') || $tr.data('jornal-id') || null;
              if (nombre || cantidad || jornal_valor) {
                mano_obra.push({ jornal_id, nombre, cantidad, jornal_valor, porcentaje_extra });
              }
            });

            // En el payload solo indicamos que HAY fotos; los File viajan aparte en FormData
            const fotos_nuevas_cant = Array.isArray(presuImagenesPorTarea[nro])
              ? presuImagenesPorTarea[nro].filter(f => f && f.file instanceof File).length
              : 0;

            const fotos_eliminadas_cant = Array.isArray(presuFotosEliminadas[nro])
              ? presuFotosEliminadas[nro].length
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

          // Armamos FormData: payload + archivos + eliminadas
          const fd = new FormData();
          fd.append('via', 'ajax');
          fd.append('funcion', 'guardarPresupuesto');
          fd.append('payload', JSON.stringify({
            id_presupuesto,
            id_previsita,
            id_visita,
            tareas
          }));

          // Adjuntar archivos reales: fotos_tarea_{N}[]
          Object.entries(window.presuImagenesPorTarea || {}).forEach(([nro, arr]) => {
            (arr || []).forEach((f) => {
              if (f && f.file instanceof File) {
                // nombre estable: si tenés f.nombre lo usamos; si no, el del File
                fd.append(`fotos_tarea_${nro}[]`, f.file, f.nombre || f.file.name);
              }
            });
          });

          // Adjuntar eliminadas: fotos_eliminadas_tarea_{N}[]
          Object.entries(window.presuFotosEliminadas || {}).forEach(([nro, arr]) => {
            (arr || []).forEach((nombre) => {
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

          if (resp && resp.ok) {

            if (resp.id_presupuesto) {
              // Guardar el id en el contenedor (estado “guardado”)
              $('#contenedorPresupuestoGenerado').data('id_presupuesto', resp.id_presupuesto);
            }
          
            mostrarExito('Presupuesto guardado correctamente.');
          
            // Recalcular si aplica
            if (typeof window.initRecalculoPresupuestoCargado === 'function') {
              window.initRecalculoPresupuestoCargado();
            }
          
            // ✅ El presupuesto actual quedó sincronizado con BD
            marcarPresupuestoComoGuardado();
          
            // (Opcional) limpiar buffers si el back ya las guardó
            presuImagenesPorTarea = {};
            presuFotosEliminadas  = {};
          
          } else {
            mostrarError(resp?.msg || 'No se pudo guardar el presupuesto.');
          }        
        } catch (err) {
          console.log('Error al guardar presupuesto:', err);
          mostrarError('Error al guardar el presupuesto.');
        } finally {
          $btn.prop('disabled', false);
        }
      });

      // === PRESUPUESTO: cambiar input de fotos
      $(document).off('change', '.presu-fotos').on('change', '.presu-fotos', function (e) {
        const input = e.target;
        const idx   = parseInt($(input).data('index'), 10);
        const $prev = $(`#presu_preview_${idx}`);
        const files = Array.from(input.files || []);

        if (!idx || !files.length) return;

        if (!presuImagenesPorTarea[idx]) presuImagenesPorTarea[idx] = [];
        if (!presuFotosEliminadas[idx])  presuFotosEliminadas[idx]  = [];

        files.forEach(file => {
          const reader = new FileReader();
          reader.onload = ev => {
            const src = ev.target.result;
            const nombreArchivo = file.name;

            // buffer
            presuImagenesPorTarea[idx].push({ file, nombre: nombreArchivo });

            // UI
            const thumb = $(`
              <div class="preview-img-container position-relative d-inline-block m-1" data-nombre-archivo="${nombreArchivo}">
                <img src="${src}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover; cursor: pointer;">
                <i class="fa fa-times-circle text-white rounded-circle position-absolute presu-eliminar-imagen" 
                  style="top: 0px; right: 0px; cursor: pointer; font-size: 1rem;"></i>
              </div>
            `);
            $prev.append(thumb);
          };
          reader.readAsDataURL(file);
        });

        // limpiar el input para poder volver a elegir los mismos archivos si hace falta
        input.value = '';
        marcarPresupuestoComoModificado();
      });

      // PRESUPUESTO
      $(document).off('click', '.presu-eliminar-imagen').on('click', '.presu-eliminar-imagen', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const $thumb = $(this).closest('.preview-img-container');
        const nombre = $thumb.data('nombre-archivo');
        const idx = parseInt($(this).closest('.presu-preview-fotos').attr('id').replace('presu_preview_', ''), 10);

        presuImagenesPorTarea[idx] = (presuImagenesPorTarea[idx] || []).filter(img => img.nombre !== nombre);
        presuFotosEliminadas[idx]  = presuFotosEliminadas[idx]  || [];
        if (!presuFotosEliminadas[idx].includes(nombre)) presuFotosEliminadas[idx].push(nombre);

        $thumb.remove();
        marcarPresupuestoComoModificado();
      });

      // === PRESUPUESTO: drag & drop sobre la dropzone
      $(document).off('dragover drop dragleave', '.presu-dropzone')
      .on('dragover', '.presu-dropzone', function (e) {
        e.preventDefault(); e.stopPropagation();
        $(this).addClass('bg-white');
      })
      .on('dragleave', '.presu-dropzone', function (e) {
        e.preventDefault(); e.stopPropagation();
        $(this).removeClass('bg-white');
      })
      .on('drop', '.presu-dropzone', function (e) {
        e.preventDefault(); e.stopPropagation();
        $(this).removeClass('bg-white');

        const idx   = parseInt($(this).data('index'), 10);
        const $prev = $(`#presu_preview_${idx}`);
        const files = Array.from(e.originalEvent.dataTransfer.files || []).filter(f => f.type.startsWith('image/'));

        if (!idx || !files.length) return;

        if (!presuImagenesPorTarea[idx]) presuImagenesPorTarea[idx] = [];
        if (!presuFotosEliminadas[idx])  presuFotosEliminadas[idx]  = [];

        files.forEach(file => {
          const reader = new FileReader();
          reader.onload = ev => {
            const src = ev.target.result;
            const nombreArchivo = file.name;

            presuImagenesPorTarea[idx].push({ file, nombre: nombreArchivo });

            const thumb = $(`
              <div class="preview-img-container position-relative d-inline-block m-1" data-nombre-archivo="${nombreArchivo}">
                <img src="${src}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover; cursor: pointer;">
                <i class="fa fa-times-circle text-white rounded-circle position-absolute presu-eliminar-imagen" 
                  style="top: 0px; right: 0px; cursor: pointer; font-size: 1rem;"></i>
              </div>
            `);
            $prev.append(thumb);
          };
          reader.readAsDataURL(file);
        });

        marcarPresupuestoComoModificado();        
      });

      if (!mostrarVistaDetallada) {
        contenedor.find('.fila-impuestos').addClass('d-none');
      }
      verificarDatosVencidos();
    
      // === INICIALIZACIÓN DE SUBTOTALES Y EVENTOS ===
    
      // 1) Subtotales iniciales en cada fila de materiales y mano de obra
      contenedor.find('tr').each(function() {
        const $tr = $(this);
        if ($tr.find('.cantidad-material').length) {
          calcularFilaMaterial($tr);
        }
        if ($tr.find('.cantidad-mano-obra').length) {
          calcularFilaManoObra($tr);
        }
      });
    
      // 2) Subtotales de bloque (Materiales, Mano de Obra y Tarea)
      contenedor.find('.tarea-card').each(function() {
        actualizarSubtotalesBloque($(this));
      });
    
      // 3) Calcular totales por tarea (botones visuales)
      contenedor.find('.tarea-card').each(function(i) {
        const numeroTarea = i + 1;
        actualizarTotalesPorTarea(numeroTarea, $(this));
      });
    
      // 4) Calcular total general
      actualizarTotalGeneral();
      verificarDatosVencidos();
    
      // 5) Delegado de eventos (único y correcto)
      //    + Si cambia un precio/jornal que estaba en rojo, persistimos en BD y, si es exitoso,
      //      reemplazamos bg-danger → bg-success, seteamos readonly y revalidamos.
      contenedor.off('input change', 'input').on('input change', 'input', function (e) {
        const $el   = $(this);
        const $card = $el.closest('.tarea-card');
        const $tr   = $el.closest('tr');

        const esMaterial = $el.hasClass('precio-unitario');
        const esJornal   = $el.hasClass('valor-jornal');
        const estabaRojo = $el.hasClass('bg-danger');

        // --- A) Si se trata de precio/jornal y estaba en rojo, en 'change' persistimos en BD
        if ((esMaterial || esJornal) && estabaRojo && e.type === 'change') {
          const valorNuevo = parseFloat(String($el.val()).replace(',', '.')) || 0;

          if (esMaterial) {
            // La <tr> del presupuesto debe traer data-material-id="<id_material>"
            const idMaterial = $tr.data('material-id');
            if (idMaterial) {
              simpleUpdateInDB(
                '../06-funciones_php/funciones.php', // urlDestino
                'materiales',                         // tabla
                { precio_unitario: valorNuevo },      // SET
                [                                      // WHERE
                  { columna: 'id_material', condicion: '=', valorCompara: String(idMaterial) }
                ],
                'ajax'
              ).then(() => {
                // ✅ Éxito: pasar a vigente (verde) y bloquear edición
                $el.removeClass('bg-danger').addClass('bg-success').prop('readonly', true);
                verificarDatosVencidos();
                mostrarExito('PRECIO DE MATERIAL ACTUALIZADO');
              }).catch(() => {
                mostrarError('NO SE PUDO ACTUALIZAR EL PRECIO DEL MATERIAL');
              });
            }
          } else if (esJornal) {
            // La <tr> del presupuesto debe traer data-jornal_id="<jornal_id>"
            const idJornal = $tr.data('jornal_id');
            if (idJornal) {
              simpleUpdateInDB(
                '../06-funciones_php/funciones.php', // urlDestino
                'tipo_jornales',                      // tabla
                { jornal_valor: valorNuevo },         // SET
                [                                      // WHERE
                  { columna: 'jornal_id', condicion: '=', valorCompara: String(idJornal) }
                ],
                'ajax'
              ).then(() => {
                // ✅ Éxito: pasar a vigente (verde) y bloquear edición
                $el.removeClass('bg-danger').addClass('bg-success').prop('readonly', true);
                verificarDatosVencidos();
                mostrarExito('VALOR DE JORNAL ACTUALIZADO');
              }).catch(() => {
                mostrarError('NO SE PUDO ACTUALIZAR EL VALOR DEL JORNAL');
              });
            }
          }
        }

        // --- B) Recalcular (tu lógica existente)
        if ($tr.find('.cantidad-material').length) calcularFilaMaterial($tr);
        if ($tr.find('.cantidad-mano-obra').length) calcularFilaManoObra($tr);

        actualizarSubtotalesBloque($card);

        const idBtn = $card.find('.btn-total-tarea').last().attr('id');
        const numeroTarea = parseInt(idBtn?.split('-').pop(), 10);
        actualizarTotalesPorTarea(numeroTarea, $card);

        actualizarTotalGeneral();

        // --- C) Revalidar botones/alertas (si el usuario solo tipea, sin “change”, también se controla)
        verificarDatosVencidos();
      });

      
    }

   // START - Rederizado con datos backend ///////////////////////////////////////////////////////
   function renderizarPresupuestoDesdeBackend(datos) {
    
   }   
  // END - Rederizado con datos backend /////////////////////////////////////////////////////////

    // Recalcular al modificar los inputs de utilidad global por bloque
    $(document).on('input change', '.utilidad-global-materiales, .utilidad-global-mano-obra', function () {
      const $card = $(this).closest('.tarea-card');
      const idBtn = $card.find('.btn-total-tarea').last().attr('id');
      const numeroTarea = parseInt(idBtn?.split('-').pop(), 10);

      actualizarSubtotalesBloque($card);
      if (!isNaN(numeroTarea)) actualizarTotalesPorTarea(numeroTarea, $card);
      actualizarTotalGeneral();
    });

    // Escuchar cambios en los inputs de utilidad por bloque
    $(document).on('input change', '.tarea-materiales .monto-extra-fijo, .tarea-mano-obra .monto-extra-fijo', function () {
      const $card = $(this).closest('.tarea-card');
      actualizarSubtotalesBloque($card);        // Recalcula los subtotales generales
      actualizarTotalesPorTarea();              // Recalcula subtotales de utilidad (Mat + MO)
      actualizarTotalGeneral();                 // Recalcula el total general del presupuesto
    });

    // Al cambiar el % utilidad global (materiales o mano de obra), recalcular totales
    $(document).on('input change', '.monto-extra-fijo-global', function () {
      const $input = $(this);
      const $card = $input.closest('.tarea-card');
      const idBtn = $card.find('.btn-total-tarea').last().attr('id');
      const numeroTarea = parseInt(idBtn?.split('-').pop(), 10);

      if (!isNaN(numeroTarea)) {
        actualizarSubtotalesBloque($card);
        actualizarTotalesPorTarea(numeroTarea, $card);
        actualizarTotalGeneral();
      }
    });

    function verificarDatosVencidos() {
      const hayVencidos = $('.precio-unitario.bg-danger, .valor-jornal.bg-danger').length > 0;
      const idPresupuesto = Number($('#contenedorPresupuestoGenerado').data('id_presupuesto')) || null;
      const presupuestoDirty = !!window.presupuestoDirty;
    
      const $btnGuardar = $('.presupuesto-total-actions #btn-guardar-presupuesto');
      const $btnEmitir = $('.btn-emitir-presupuesto');

      if (edicionComercialBloqueada()) {
        $btnGuardar
          .prop('disabled', true)
          .addClass('btn-secondary')
          .removeClass('btn-success');

        $btnEmitir
          .prop('disabled', true)
          .addClass('btn-secondary')
          .removeClass('btn-primary');

        return;
      }

      if (hayVencidos) {
        $btnGuardar
          .prop('disabled', true)
          .addClass('btn-secondary')
          .removeClass('btn-success');
    
        $btnEmitir
          .prop('disabled', true)
          .addClass('btn-secondary')
          .removeClass('btn-primary');
    
        if (!alertaDesactualizadosMostrada) {
          alertaDesactualizadosMostrada = true;
    
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
    
          sAlertConfirmV2(alertDanger);
        }
    
        return;
      }
    
      alertaDesactualizadosMostrada = false;
    
      if (presupuestoDirty) {
        $btnGuardar
          .prop('disabled', false)
          .addClass('btn-success')
          .removeClass('btn-secondary');
    
        $btnEmitir
          .prop('disabled', true)
          .addClass('btn-secondary')
          .removeClass('btn-primary');
    
        return;
      }
    
      $btnGuardar
        .prop('disabled', true)
        .addClass('btn-secondary')
        .removeClass('btn-success');
    
      if (idPresupuesto) {
        $btnEmitir
          .prop('disabled', false)
          .addClass('btn-primary')
          .removeClass('btn-secondary');
      } else {
        $btnEmitir
          .prop('disabled', true)
          .addClass('btn-secondary')
          .removeClass('btn-primary');
      }
    }
  
    // al final del $(document).ready, antes de cerrar:
    if ($('#contenedorPresupuestoGenerado .tarea-card').length) {
      window.presupuestoGenerado = true;
    }

    // al final del $(document).ready, después del bloque anterior:
    window.initRecalculoPresupuestoCargado = function () {
      const $root = $('#contenedorPresupuestoGenerado');
      if (!$root.length) return;
    
      // Recalcular filas
      $root.find('.tarea-card').each(function () {
        const $card = $(this);

        if (typeof window.syncTituloCardPresupuesto === 'function') {
          window.syncTituloCardPresupuesto($card);
        }
    
        $card.find('tbody tr').each(function () {
          const $tr = $(this);
          if ($tr.find('.cantidad-material').length)  window.calcularFilaMaterial($tr);
          if ($tr.find('.cantidad-mano-obra').length) window.calcularFilaManoObra($tr);
        });
    
        window.actualizarSubtotalesBloque($card);
    
        // obtener número de tarea desde el botón final
        const idBtn = $card.find('[id^="subt-tarea-"]').attr('id');
        const numero = parseInt(idBtn?.split('-').pop(), 10) || 1;
        window.actualizarTotalesPorTarea(numero, $card);
      });
    
      window.actualizarTotalGeneral();
    };
    
    // Ejecutar una vez si ya hay presupuesto renderizado
    window.initRecalculoPresupuestoCargado();

// código de emisión de documento ////////////////////////////////////////////
(function () {
  // lock global anti doble emisión
  window.__PRESU_PRINT_LOCK__ = window.__PRESU_PRINT_LOCK__ || false;
  const PRINT_ROOT_ID = 'presu-print-root';
  const PRINT_STYLE_ID = 'presu-print-style';
  const A4_WIDTH_PX = 794;
  const A4_HEIGHT_PX = 1123;
  const A4_MARGIN_PX = 38;
  const A4_WIDTH_MM = 210;
  const A4_HEIGHT_MM = 297;

  const limpiarHostRender = () => {
    const $root = $('#' + PRINT_ROOT_ID);
    if ($root.length) {
      $root.empty();
    }
  };

  const asegurarHostRenderStyles = () => {
    if (document.getElementById(PRINT_STYLE_ID)) return;

    const style = document.createElement('style');
    style.id = PRINT_STYLE_ID;
    style.type = 'text/css';
    style.textContent = `
      #${PRINT_ROOT_ID} {
        position: absolute !important;
        left: -20000px !important;
        top: 0 !important;
        width: ${A4_WIDTH_PX}px !important;
        padding: 0 !important;
        margin: 0 !important;
        background: #fff !important;
        pointer-events: none !important;
      }

      #${PRINT_ROOT_ID} .page {
        width: ${A4_WIDTH_PX}px !important;
        max-width: ${A4_WIDTH_PX}px !important;
      }

      #${PRINT_ROOT_ID} .print-page-task,
      #${PRINT_ROOT_ID} .print-page-total {
        width: ${A4_WIDTH_PX}px !important;
        min-height: ${A4_HEIGHT_PX}px !important;
        padding: ${A4_MARGIN_PX}px !important;
        background: #fff !important;
        overflow: hidden !important;
      }

      #${PRINT_ROOT_ID} .page-break-before {
        break-before: auto !important;
        page-break-before: auto !important;
      }
    `;

    document.head.appendChild(style);
  };

  const obtenerHostRender = () => {
    let $root = $('#' + PRINT_ROOT_ID);
    if (!$root.length) {
      $root = $('<div/>', { id: PRINT_ROOT_ID }).appendTo('body');
    }
    return $root;
  };

  const esperarSiguienteFrame = () => new Promise((resolve) => {
    requestAnimationFrame(() => requestAnimationFrame(resolve));
  });

  const esperarImagenesRender = async ($scope) => {
    const imagenes = Array.from($scope.find('img'));
    if (!imagenes.length) return;

    await Promise.all(imagenes.map((img) => new Promise((resolve) => {
      if (img.complete && img.naturalWidth > 0) {
        resolve();
        return;
      }

      const finalizar = () => {
        img.removeEventListener('load', finalizar);
        img.removeEventListener('error', finalizar);
        resolve();
      };

      img.addEventListener('load', finalizar, { once: true });
      img.addEventListener('error', finalizar, { once: true });
    })));
  };

  const ajustarFotosDocumentoEmitido = ($scope) => {
    const paginas = Array.from($scope.find('.print-page-task'));
    if (!paginas.length) return;

    paginas.forEach((pagina) => {
      const contenedorFotos = pagina.querySelector('.fotos');
      if (!contenedorFotos) return;

      const slots = Array.from(contenedorFotos.querySelectorAll('.foto-slot'));
      if (!slots.length) return;

      const pageRect = pagina.getBoundingClientRect();
      const fotosRect = contenedorFotos.getBoundingClientRect();
      const topRelativo = fotosRect.top - pageRect.top;
      const altoDisponible = Math.floor(A4_HEIGHT_PX - A4_MARGIN_PX - topRelativo);

      if (altoDisponible <= 0) return;

      contenedorFotos.style.maxHeight = `${altoDisponible}px`;
      slots.forEach((slot) => {
        slot.style.maxHeight = `${altoDisponible}px`;
      });
    });
  };

  const mostrarSwalGenerandoDocumento = () => {
    if (!window.Swal || typeof Swal.fire !== 'function') return;

    Swal.fire({
      icon: 'info',
      title: '<H2><STRONG style="color: #000000;">GENERANDO DOCUMENTO</STRONG></H2>',
      html: '<H5 style="color: #000000;">Estamos armando el PDF oficial y guardándolo en el sistema.</H5>',
      background: '#ffc107',
      iconColor: '#000000',
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
        const loader = Swal.getLoader();
        if (loader) {
          loader.style.borderColor = '#000000';
          loader.style.borderRightColor = 'transparent';
        }
      }
    });
  };

  const mostrarSwalDocumentoExito = (mensajeHtml) => {
    if (!window.Swal || typeof Swal.fire !== 'function') {
      if (typeof mostrarExito === 'function') {
        mostrarExito('Documento generado, guardado y descargado correctamente.', 4);
      }
      return;
    }

    const config = {
      icon: 'success',
      title: '<H2><STRONG style="color: #ffffff;">DOCUMENTO DESCARGADO</STRONG></H2>',
      html: `<H5 style="color: #ffffff;">${mensajeHtml}</H5>`,
      background: '#28a745',
      iconColor: '#ffffff',
      showConfirmButton: true,
      confirmButtonText: 'OK',
      confirmButtonColor: '#1e7e34',
      allowOutsideClick: true,
      allowEscapeKey: true
    };

    if (Swal.isVisible()) {
      Swal.hideLoading();
      Swal.update(config);
      return;
    }

    Swal.fire(config);
  };

  const mostrarSwalDocumentoError = (mensajeHtml) => {
    if (!window.Swal || typeof Swal.fire !== 'function') {
      if (typeof mostrarError === 'function') {
        mostrarError(mensajeHtml.replace(/<[^>]*>/g, ' ').trim() || 'Error al generar el documento del presupuesto.');
      }
      return;
    }

    const config = {
      icon: 'error',
      title: '<H2><STRONG style="color: #ffffff;">NO SE PUDO GENERAR EL DOCUMENTO</STRONG></H2>',
      html: `<H5 style="color: #ffffff;">${mensajeHtml}</H5>`,
      background: '#dc3545',
      iconColor: '#ffffff',
      confirmButtonText: 'Cerrar',
      confirmButtonColor: '#b02a37',
      allowOutsideClick: true,
      allowEscapeKey: true
    };

    if (Swal.isVisible()) {
      Swal.hideLoading();
      Swal.update(config);
      return;
    }

    Swal.fire(config);
  };

  const descargarBlobDocumento = (blob, nombreArchivo) => {
    const nombreBase = String(nombreArchivo || 'documento.pdf').trim() || 'documento.pdf';
    const nombreFinal = /\.pdf$/i.test(nombreBase) ? nombreBase : `${nombreBase}.pdf`;
    const urlBlob = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = urlBlob;
    link.download = nombreFinal;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(urlBlob), 1000);
  };

  const renderizarDocumentoComoPdf = async ($printRoot) => {
    if (typeof window.html2canvas !== 'function' || !window.jspdf?.jsPDF) {
      throw new Error('No se pudieron cargar las librerías para generar el PDF.');
    }

    if (document.fonts?.ready) {
      try {
        await document.fonts.ready;
      } catch (err) {
        console.log('No se pudo esperar la carga completa de fuentes:', err);
      }
    }

    await esperarImagenesRender($printRoot);
    ajustarFotosDocumentoEmitido($printRoot);
    await esperarSiguienteFrame();

    const paginas = Array.from(
      $printRoot[0].querySelectorAll('.print-page-task, .print-page-total')
    );

    if (!paginas.length) {
      throw new Error('No se encontraron páginas para renderizar el documento.');
    }

    const pdf = new window.jspdf.jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4',
      compress: true
    });
    const altoCanvasPorPagina = Math.ceil((A4_HEIGHT_MM / A4_WIDTH_MM) * (A4_WIDTH_PX * 2));
    const toleranciaResiduoPx = 8;
    let paginaPdfAgregada = false;

    for (let index = 0; index < paginas.length; index += 1) {
      const pagina = paginas[index];
      const canvas = await window.html2canvas(pagina, {
        scale: 2,
        backgroundColor: '#ffffff',
        useCORS: true,
        allowTaint: false,
        logging: false,
        windowWidth: A4_WIDTH_PX,
        windowHeight: A4_HEIGHT_PX
      });

      for (let offsetY = 0; offsetY < canvas.height; offsetY += altoCanvasPorPagina) {
        const altoRestante = canvas.height - offsetY;
        if (paginaPdfAgregada && altoRestante <= toleranciaResiduoPx) {
          break;
        }

        const altoSlice = Math.min(altoCanvasPorPagina, altoRestante);
        const sliceCanvas = document.createElement('canvas');
        sliceCanvas.width = canvas.width;
        sliceCanvas.height = altoSlice;

        const ctx = sliceCanvas.getContext('2d');
        if (!ctx) {
          throw new Error('No se pudo preparar el recorte del PDF emitido.');
        }

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, sliceCanvas.width, sliceCanvas.height);
        ctx.drawImage(
          canvas,
          0,
          offsetY,
          canvas.width,
          altoSlice,
          0,
          0,
          sliceCanvas.width,
          sliceCanvas.height
        );

        if (paginaPdfAgregada) {
          pdf.addPage();
        }

        const altoSliceMm = (A4_WIDTH_MM * altoSlice) / canvas.width;
        const imgData = sliceCanvas.toDataURL('image/png');
        pdf.addImage(imgData, 'PNG', 0, 0, A4_WIDTH_MM, altoSliceMm, undefined, 'FAST');
        paginaPdfAgregada = true;
      }
    }

    return pdf.output('blob');
  };

  const parsearRespuestaJsonDocumento = (rawResponse) => {
    if (typeof rawResponse === 'object' && rawResponse !== null) {
      return rawResponse;
    }

    const texto = String(rawResponse ?? '').trim();
    if (texto === '') {
      throw new Error('El servidor devolvió una respuesta vacía al emitir el documento.');
    }

    try {
      return JSON.parse(texto);
    } catch (errJson) {
      const ini = texto.indexOf('{');
      const fin = texto.lastIndexOf('}');
      if (ini !== -1 && fin !== -1 && fin > ini) {
        const posibleJson = texto.slice(ini, fin + 1);
        try {
          return JSON.parse(posibleJson);
        } catch (errJsonRecortado) {
          // sigue al fallback de error legible
        }
      }

      const resumen = texto.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
      throw new Error(
        resumen
          ? `Respuesta inválida del servidor al emitir el documento: ${resumen.slice(0, 220)}`
          : 'Respuesta inválida del servidor al emitir el documento.'
      );
    }
  };

  const subirDocumentoEmitido = async ({ idPresupuesto, idPrevisita, nombreArchivoPdf, pdfBlob }) => {
    const formData = new FormData();
    const idUsuarioActivo = Number(window.ACTIVE_USER_ID || 0) || 0;
    formData.append('via', 'ajax');
    formData.append('funcion', 'emitirDocumentoPresupuesto');
    formData.append('id_presupuesto', String(idPresupuesto));
    formData.append('id_previsita', String(idPrevisita));
    formData.append('nombre_archivo', nombreArchivoPdf);
    if (idUsuarioActivo > 0) {
      formData.append('id_usuario', String(idUsuarioActivo));
    }
    formData.append('documento_pdf', pdfBlob, `${nombreArchivoPdf}.pdf`);

    const rawResponse = await $.ajax({
      url: ENDPOINT,
      method: 'POST',
      dataType: 'text',
      data: formData,
      processData: false,
      contentType: false
    });

    return parsearRespuestaJsonDocumento(rawResponse);
  };

  const extraerMensajeErrorDocumento = (err) => {
    if (typeof err === 'string' && err.trim() !== '') {
      return err.trim();
    }

    if (err?.message) {
      return String(err.message).trim();
    }

    const respuestaTexto = err?.responseText;
    if (typeof respuestaTexto === 'string' && respuestaTexto.trim() !== '') {
      try {
        const data = JSON.parse(respuestaTexto);
        if (data?.msg) {
          return String(data.msg).trim();
        }
      } catch (jsonErr) {
        const textoPlano = respuestaTexto.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        if (textoPlano !== '') {
          return textoPlano.slice(0, 220);
        }
      }
    }

    const status = err?.status ? `HTTP ${err.status}` : '';
    const statusText = err?.statusText ? String(err.statusText).trim() : '';
    const combinado = [status, statusText].filter(Boolean).join(' ');
    if (combinado) {
      return combinado;
    }

    return 'Ocurrió un error inesperado.';
  };

  $(document)
    .off('click', '.btn-emitir-presupuesto')
    .on('click', '.btn-emitir-presupuesto', async function (e) {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      if (edicionComercialBloqueada()) {
        mostrarAlertaBloqueoEdicionComercial();
        return false;
      }

      const $btn = $(this);

      // Respeta disabled real si el botón lo trae
      if ($btn.prop('disabled')) return false;

      // Anti doble disparo (doble click, listeners duplicados, comportamiento raro del navegador)
      if (window.__PRESU_PRINT_LOCK__) return false;
      window.__PRESU_PRINT_LOCK__ = true;
      $btn.prop('disabled', true);

      try {
        // Solo emitimos si existe id_presupuesto (guardado)
        const idPresupuesto = Number($('#contenedorPresupuestoGenerado').data('id_presupuesto')) || null;
        const idPrevisitaImpresion = String($('#id_previsita').val() || '').trim();
        if (!idPresupuesto) {
          if (typeof mostrarAdvertencia === 'function') {
            mostrarAdvertencia('Debe guardar el presupuesto antes de generar el documento.', 4);
          } else {
            alert('Debe guardar el presupuesto antes de generar el documento.');
          }
          window.__PRESU_PRINT_LOCK__ = false;
          return false;
        }
        if (!idPrevisitaImpresion) {
          if (typeof mostrarAdvertencia === 'function') {
            mostrarAdvertencia('No se pudo obtener el Nro de pre-visita para generar el documento.', 4);
          } else {
            alert('No se pudo obtener el Nro de pre-visita para generar el documento.');
          }
          window.__PRESU_PRINT_LOCK__ = false;
          return false;
        }

        mostrarSwalGenerandoDocumento();

        // Tomamos datos desde tu recolector (si existe)
        const datos = (typeof recolectarDatosParaPresupuesto === 'function')
          ? recolectarDatosParaPresupuesto()
          : null;

        const cliente = datos?.cliente || {};
        const obra    = datos?.obra || {};
        const presup  = datos?.presupuesto || {};

        // Total mostrado
        const totalTxt = ($('.presupuesto-total-valor').first().text() || '').trim() || 'FALTA COMPLETAR';

        // Helpers
        const esc = (s) => String(s ?? '')
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('"', '&quot;')
          .replaceAll("'", '&#039;');

        const valOrFalta = (v) => {
          const t = String(v ?? '').trim();
          return t ? esc(t) : '<span class="falta">FALTA COMPLETAR</span>';
        };
        const valOrDash = (v) => {
          const t = String(v ?? '').trim();
          return t ? esc(t) : '-';
        };
        const cleanSelectText = (selector, invalidos = []) => {
          const texto = obtenerTextoVisibleSelect(selector, invalidos);
          return texto || '';
        };
        const formatFechaCorta = (fecha) => {
          const t = String(fecha ?? '').trim();
          if (!t) return '-';
          const m = t.match(/^(\d{4})-(\d{2})-(\d{2})$/);
          if (m) return `${m[3]}-${m[2]}-${m[1]}`;
          return esc(t);
        };
        const formatTelefono = (telefono) => {
          const limpio = String(telefono ?? '')
            .replace(/[()_]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
          return limpio || '-';
        };
        const PDF_DETALLE_BLOCK_TAGS = new Set(['p', 'div', 'ul', 'ol', 'li']);
        const normalizarTextoPdfTarea = (texto) => String(texto ?? '')
          .replace(/\s+/g, ' ')
          .trim();
        const descomponerContenidoTarea = (texto, fallback) => {
          const limpio = String(texto ?? '').replace(/\s+/g, ' ').trim();
          const fallbackTitulo = String(fallback ?? '').toUpperCase();
          const limpiarInicioDetalle = (valor) => String(valor ?? '')
            .replace(/^[\s\.,:\-*]+/, '')
            .trim();
          if (!limpio) {
            return {
              titulo: fallbackTitulo,
              detalle: '',
              detalleDesde: 0
            };
          }

          const delimitadoresConPunto = ['.', ',', '-', '*', ':'];
          let idxCorte = -1;

          delimitadoresConPunto.forEach((delimitador) => {
            const idx = limpio.indexOf(delimitador);
            if (idx > -1 && (idxCorte === -1 || idx < idxCorte)) {
              idxCorte = idx;
            }
          });

          if (idxCorte > -1) {
            const baseTitulo = limpio.slice(0, idxCorte).trim();
            let detalleDesde = idxCorte + 1;
            while (detalleDesde < limpio.length && /[\s\.,:\-*]/.test(limpio.charAt(detalleDesde))) {
              detalleDesde += 1;
            }
            const detalle = limpiarInicioDetalle(limpio.slice(detalleDesde));
            if (baseTitulo) {
              return {
                titulo: `${baseTitulo}.`.toUpperCase(),
                detalle,
                detalleDesde
              };
            }
            return {
              titulo: fallbackTitulo,
              detalle,
              detalleDesde
            };
          }

          const palabras = limpio.split(' ').filter(Boolean);
          if (!palabras.length) {
            return {
              titulo: fallbackTitulo,
              detalle: '',
              detalleDesde: 0
            };
          }

          let detalleDesde = limpio.length;
          if (palabras.length > 12) {
            const matcher = /\S+/g;
            let contador = 0;
            let matchPalabra = null;

            while ((matchPalabra = matcher.exec(limpio)) && contador < 12) {
              contador += 1;
              detalleDesde = matcher.lastIndex;
            }

            while (detalleDesde < limpio.length && /\s/.test(limpio.charAt(detalleDesde))) {
              detalleDesde += 1;
            }
          }

          return {
            titulo: `${palabras.slice(0, 12).join(' ').toUpperCase()}...`,
            detalle: palabras.slice(12).join(' ').trim(),
            detalleDesde
          };
        };
        const limpiarPrefijoTarea = (texto) => String(texto ?? '')
          .replace(/^\s*tarea\s*\d+\s*[:.-]?\s*/i, '')
          .replace(/\s+/g, ' ')
          .trim();
        const normalizarTextoOrtografico = (texto) => String(texto ?? '')
          .replace(/\s+/g, ' ')
          .replace(/\s+([,.;:!?])/g, '$1')
          .replace(/([,.;:!?])(?=\S)/g, '$1 ')
          .replace(/\(\s+/g, '(')
          .replace(/\s+\)/g, ')')
          .trim();
        const convertirDetalleTextoAHtml = (texto) => {
          const limpio = String(texto ?? '').trim();
          if (!limpio) return '';

          if (typeof window.normalizarTextoPlanoDetalleTarea === 'function') {
            return window.normalizarTextoPlanoDetalleTarea(limpio);
          }

          return esc(limpio).replace(/\n/g, '<br>');
        };
        const limpiarInicioHtmlDetalle = (html) => {
          const parser = new window.DOMParser();
          const doc = parser.parseFromString(`<div>${String(html ?? '')}</div>`, 'text/html');
          const root = doc.body.firstElementChild || doc.body;

          const nodoSinContenido = (node) => {
            if (!node) return true;
            if (node.nodeType === window.Node.TEXT_NODE) {
              return !String(node.textContent || '').trim();
            }
            if (node.nodeType !== window.Node.ELEMENT_NODE) {
              return true;
            }
            const tag = String(node.tagName || '').toLowerCase();
            if (tag === 'br') {
              return true;
            }
            return !String(node.textContent || '').trim();
          };

          const recortarInicio = (container) => {
            while (container.firstChild) {
              const firstChild = container.firstChild;

              if (firstChild.nodeType === window.Node.TEXT_NODE) {
                const textoRecortado = String(firstChild.textContent || '').replace(/^[\s\.,:\-*]+/u, '');
                if (textoRecortado) {
                  firstChild.textContent = textoRecortado;
                  return;
                }
                firstChild.remove();
                continue;
              }

              if (firstChild.nodeType !== window.Node.ELEMENT_NODE) {
                firstChild.remove();
                continue;
              }

              const tag = String(firstChild.tagName || '').toLowerCase();
              if (tag === 'br') {
                firstChild.remove();
                continue;
              }

              recortarInicio(firstChild);

              if (nodoSinContenido(firstChild)) {
                firstChild.remove();
                continue;
              }

              return;
            }
          };

          recortarInicio(root);
          return root.innerHTML.trim();
        };
        const extraerDetalleHtmlPreservandoFormato = (html, detalleEsperado, detalleDesde = 0) => {
          const htmlFuente = /<[^>]+>/.test(String(html ?? ''))
            ? String(html ?? '')
            : convertirDetalleTextoAHtml(html);
          const detalleNormalizado = normalizarTextoPdfTarea(detalleEsperado);

          if (!htmlFuente || !detalleNormalizado) {
            return '';
          }

          const parser = new window.DOMParser();
          const doc = parser.parseFromString(`<div>${htmlFuente}</div>`, 'text/html');
          const root = doc.body.firstElementChild || doc.body;
          const posiciones = [];
          let textoNormalizado = '';
          let ultimoFueEspacio = true;

          const emitirCaracter = (caracter, posicion) => {
            const esEspacio = /\s/.test(caracter);

            if (esEspacio) {
              if (ultimoFueEspacio || !textoNormalizado.length) {
                return;
              }
              textoNormalizado += ' ';
              posiciones.push({ ...posicion, esEspacio: true });
              ultimoFueEspacio = true;
              return;
            }

            textoNormalizado += caracter;
            posiciones.push({ ...posicion, esEspacio: false });
            ultimoFueEspacio = false;
          };

          const recorrerNodo = (node) => {
            if (!node) return;

            if (node.nodeType === window.Node.TEXT_NODE) {
              const contenido = String(node.textContent || '');
              for (let idx = 0; idx < contenido.length; idx += 1) {
                emitirCaracter(contenido.charAt(idx), {
                  tipo: 'texto',
                  node,
                  offset: idx + 1
                });
              }
              return;
            }

            if (node.nodeType !== window.Node.ELEMENT_NODE) {
              return;
            }

            const tag = String(node.tagName || '').toLowerCase();

            if (tag === 'br') {
              emitirCaracter(' ', { tipo: 'despuesNodo', node });
              return;
            }

            if (tag === 'li') {
              emitirCaracter('-', { tipo: 'inicioElemento', node });
              emitirCaracter(' ', { tipo: 'inicioElemento', node });
            }

            Array.from(node.childNodes || []).forEach(recorrerNodo);

            if (PDF_DETALLE_BLOCK_TAGS.has(tag)) {
              emitirCaracter(' ', { tipo: 'despuesNodo', node });
            }
          };

          Array.from(root.childNodes || []).forEach(recorrerNodo);

          while (posiciones.length && posiciones[posiciones.length - 1].esEspacio) {
            posiciones.pop();
            textoNormalizado = textoNormalizado.slice(0, -1);
          }

          const coincidenciaPrefijo = textoNormalizado.match(/^tarea\s*\d+\s*[:.-]?\s*/i);
          const prefijoConsumido = coincidenciaPrefijo ? coincidenciaPrefijo[0].length : 0;
          const detalleTextoEnHtml = textoNormalizado.slice(prefijoConsumido);
          const detalleDesdeBase = Math.max(0, (Number(detalleDesde) || 0) - prefijoConsumido);
          const indiceDetalleReal = detalleTextoEnHtml.indexOf(detalleNormalizado, detalleDesdeBase);
          const totalConsumido = indiceDetalleReal > -1
            ? prefijoConsumido + indiceDetalleReal
            : Math.max(0, Number(detalleDesde) || 0);

          if (totalConsumido <= 0) {
            return limpiarInicioHtmlDetalle(root.innerHTML);
          }

          if (totalConsumido >= posiciones.length) {
            return '';
          }

          const posicionInicio = posiciones[totalConsumido - 1];
          const range = doc.createRange();

          if (!posicionInicio) {
            range.setStart(root, 0);
          } else if (posicionInicio.tipo === 'texto') {
            range.setStart(posicionInicio.node, posicionInicio.offset);
          } else if (posicionInicio.tipo === 'inicioElemento') {
            range.setStart(posicionInicio.node, 0);
          } else {
            range.setStartAfter(posicionInicio.node);
          }

          range.setEnd(root, root.childNodes.length);

          const fragment = range.cloneContents();
          const container = doc.createElement('div');
          container.appendChild(fragment);

          return limpiarInicioHtmlDetalle(container.innerHTML);
        };
        const descomponerContenidoTareaHtml = (html, fallback) => {
          const htmlSeguro = String(html ?? '').trim();
          const textoPlano = (typeof window.detalleTareaHtmlToPlainText === 'function')
            ? window.detalleTareaHtmlToPlainText(htmlSeguro)
            : String(htmlSeguro).replace(/<[^>]+>/g, ' ');
          const textoLimpio = limpiarPrefijoTarea(textoPlano || fallback);
          const resultadoPlano = descomponerContenidoTarea(textoLimpio, fallback);

          if (!htmlSeguro) {
            return {
              titulo: resultadoPlano.titulo,
              detalleHtml: resultadoPlano.detalle
                ? convertirDetalleTextoAHtml(resultadoPlano.detalle)
                : ''
            };
          }

          return {
            titulo: resultadoPlano.titulo,
            detalleHtml: extraerDetalleHtmlPreservandoFormato(
              htmlSeguro,
              resultadoPlano.detalle,
              resultadoPlano.detalleDesde
            ) || (resultadoPlano.detalle
              ? convertirDetalleTextoAHtml(resultadoPlano.detalle)
              : '')
          };
        };
        const agruparItems = (items, tamanoGrupo) => {
          const resultado = [];
          for (let idx = 0; idx < items.length; idx += tamanoGrupo) {
            resultado.push(items.slice(idx, idx + tamanoGrupo));
          }
          return resultado;
        };

        const fechaImpresion = new Date();
        const pad2 = (n) => String(n).padStart(2, '0');
        const numeroPresupuestoImpresion = `${idPrevisitaImpresion}_${fechaImpresion.getFullYear()}${pad2(fechaImpresion.getMonth() + 1)}${pad2(fechaImpresion.getDate())}_${pad2(fechaImpresion.getHours())}${pad2(fechaImpresion.getMinutes())}${pad2(fechaImpresion.getSeconds())}`;
        const numeroPresupuestoVisual = numeroPresupuestoImpresion.replaceAll('_', '-');
        const razonSocialArchivo = String(cliente?.razon_social ?? '')
          .trim()
          .replace(/\s+/g, ' ')
          .replace(/[\\/:*?"<>|]/g, '')
          .replace(/\s+/g, '-');
        const nombreArchivoPdf = `${razonSocialArchivo || 'PRESUPUESTO'}_${numeroPresupuestoImpresion}`;
        const responsableImpresion = ($('.nombre-completo').first().text() || '').trim() || '-';
        const calleObra = cleanSelectText('#calle_visita', ['Calle']);
        const alturaObra = ($('#altura_visita').val() || '').toString().trim();
        const partidoObra = cleanSelectText('#partido_visita', ['Partido']);
        const localidadObra = cleanSelectText('#localidad_visita', ['Localidad']);
        const calleYAlturaObra = [calleObra, alturaObra].filter((v) => String(v).trim()).join(' ');
        const resumenObra = [
          cliente?.razon_social || '',
          calleYAlturaObra,
          localidadObra,
          partidoObra
        ].filter((v) => String(v).trim()).join(' | ');
        const requerimientoTecnico = ($('#requerimiento_tecnico').val() || '').trim();
        const descripcionObra = normalizarTextoOrtografico(requerimientoTecnico || 'Requerimiento técnico');
        const encabezadoDatosHtml = `
    <div class="grid">
      <div class="box">
        <h4><b>CLIENTE</b></h4>
        <div class="linea"><b>Razón social:</b> ${valOrDash(cliente.razon_social)}</div>
        <div class="linea"><b>CUIT:</b> ${valOrDash(cliente.cuit)}</div>
        <div class="linea"><b>Dirección:</b> ${valOrDash(cliente.direccion)}</div>
        <div class="linea"><b>Contacto:</b> ${valOrDash(cliente.contacto)}</div>
        <div class="linea"><b>Email:</b> ${valOrDash(cliente.email)}</div>
        <div class="linea"><b>Teléfono:</b> ${esc(formatTelefono(cliente.telefono))}</div>
      </div>

      <div class="box">
        <h4><b>OBRA</b></h4>
        <div class="linea">${valOrDash(resumenObra)}</div>
        <div class="linea"><b>Fecha de visita:</b> ${formatFechaCorta(obra.fecha)}</div>
        <div class="linea"><b>Responsable:</b> ${valOrDash(responsableImpresion)}</div>
        <div class="linea"><b>Descripción:</b> ${esc(descripcionObra)}</div>
      </div>
    </div>
  `;

        // Logo (ruta relativa)
        // Logo
        const logoSrc = `../dist/img/logos_propios/ecotechos-logo2.png`;

        // Construir HTML de tareas desde el DOM del presupuesto
let htmlPrimeraPagina = '';
let htmlPaginasTareas = '';

$('#contenedorPresupuestoGenerado .tarea-card').each(function (idx) {
  const nro = idx + 1;
  const $card = $(this);

  let $detalle = $card.find('textarea.tarea-descripcion').first();
  if (!$detalle.length) $detalle = $card.find('textarea').first();
  const detalleOriginalHtml = (typeof window.obtenerDetalleTareaHtmlDesdeCard === 'function')
    ? window.obtenerDetalleTareaHtmlDesdeCard($card)
    : (($detalle.val() || '').trim());
  const detalleOriginal = (typeof window.detalleTareaHtmlToPlainText === 'function')
    ? window.detalleTareaHtmlToPlainText(detalleOriginalHtml)
    : detalleOriginalHtml;
  const tituloOriginal = detalleOriginal
    || ($card.find('.tarea-encabezado b').text() || '').trim()
    || `Tarea ${nro}`;
  const detalleTarea = limpiarPrefijoTarea(detalleOriginal || tituloOriginal);
  const contenidoTareaResuelto = descomponerContenidoTareaHtml(detalleOriginalHtml || detalleTarea, `Tarea ${nro}`);
  const tituloTarea = contenidoTareaResuelto.titulo;
  const detalleTareaHtml = contenidoTareaResuelto.detalleHtml
    ? `<div class="detalle-tarea">${contenidoTareaResuelto.detalleHtml}</div>`
    : '';

  // Materiales
  let filasMat = '';
  $card.find('.tarea-materiales tbody tr')
    .not('.fila-subtotal,.fila-otros-materiales')
    .each(function () {
      const $tr = $(this);
      const material = ($tr.find('td').eq(0).text() || '').trim();
      const cant = ($tr.find('.cantidad-material').val() ?? '').toString();
      const pu   = ($tr.find('.precio-unitario').val() ?? '').toString();
      const pex  = ($tr.find('.porcentaje-extra').val() ?? '').toString();
      const sub  = ($tr.find('.subtotal-material').text() || '').trim();

      filasMat += `
        <tr>
          <td>${esc(material)}</td>
          <td class="num">${esc(cant)}</td>
          <td class="num">${esc(pu)}</td>
          <td class="num">${esc(pex)}</td>
          <td class="num">${esc(sub)}</td>
        </tr>`;
    });

  const otrosMat = ($card.find('.input-otros-materiales').val() ?? '').toString();
  const subtotalMat = ($card.find('.tarea-materiales .fila-subtotal td:last-child b').text() || '').trim();

  // Mano de obra
  let filasMO = '';
  $card.find('.tarea-mano-obra tbody tr')
    .not('.fila-subtotal,.fila-otros-mano')
    .each(function () {
      const $tr = $(this);
      const tipo = ($tr.find('td').eq(0).text() || '').trim();
      const oper = ($tr.find('.cantidad-mano-obra').val() ?? '').toString();
      const dias = ($tr.find('.dias-mano-obra').val() ?? '').toString();
      const jor  = ($tr.find('.jornales-mano-obra').val() ?? '').toString();
      const valJ = ($tr.find('.valor-jornal').val() ?? '').toString();
      const pex  = ($tr.find('.porcentaje-extra').val() ?? '').toString();
      const sub  = ($tr.find('.subtotal-mano').text() || '').trim();

      filasMO += `
        <tr>
          <td>${esc(tipo)}</td>
          <td class="num">${esc(oper)}</td>
          <td class="num">${esc(dias)}</td>
          <td class="num">${esc(jor)}</td>
          <td class="num">${esc(valJ)}</td>
          <td class="num">${esc(pex)}</td>
          <td class="num">${esc(sub)}</td>
        </tr>`;
    });

  const subtotalMO = ($card.find('.tarea-mano-obra .fila-subtotal td:last-child b').text() || '').trim();
  const subtTareaTxt = ($card.find(`[id^="subt-tarea-"]`).text() || '').trim() || 'FALTA COMPLETAR';

  // Fotos
  const fotos = [];
  const $prev = $(`#presu_preview_${nro}`);
  if ($prev.length) {
    $prev.find('img').each(function () {
      const src = $(this).attr('src');
      if (!src) return;
      fotos.push({
        src,
        alt: `Foto tarea ${nro}`
      });
    });
  }
  const fotosInline = fotos.slice(0, 2);
  const fotosExtra = fotos.slice(2);
  let fotosHtml = '';
  if (fotosInline.length) {
    fotosInline.forEach((foto) => {
      fotosHtml += `
        <div class="foto-slot">
          <img class="foto" src="${esc(foto.src)}" alt="${esc(foto.alt)}">
        </div>`;
    });
  } else {
    fotosHtml = `<div class="falta">FALTA COMPLETAR</div>`;
  }
  const paginasImagenesExtra = agruparItems(fotosExtra, 4).map((grupo, grupoIdx) => `
      <section class="print-page-task print-page-task-imagenes-extra page-break-before" data-tarea="${esc(String(nro))}" data-grupo-imagenes="${esc(String(grupoIdx + 1))}">
        <div class="imagenes-extra-grid">
          ${grupo.map((foto, fotoIdx) => `
            <div class="imagen-extra-slot">
              <img
                class="imagen-extra"
                src="${esc(foto.src)}"
                alt="${esc(foto.alt || `Foto tarea ${nro} ${grupoIdx * 4 + fotoIdx + 3}`)}">
            </div>
          `).join('')}
        </div>
      </section>
    `).join('');

  const encabezadoPaginaSecundaria = `
    <div class="top">
      <div class="logo">
        <img src="${esc(logoSrc)}" alt="Logo">
      </div>
      <div class="doc">
        <div class="doc-label">Presupuesto Nro:</div>
        <div class="muted">N°: ${esc(idPresupuesto)} | Fecha: ${esc(new Date().toLocaleDateString())}</div>
      </div>
    </div>

    <hr>

    <div class="grid">
      <div class="box">
        <h4>Cliente</h4>
        <div class="linea"><b>Razón social:</b> ${valOrFalta(cliente.razon_social)}</div>
        <div class="linea"><b>CUIT:</b> ${valOrFalta(cliente.cuit)}</div>
        <div class="linea"><b>Dirección:</b> ${valOrFalta(cliente.direccion)}</div>
        <div class="linea"><b>Contacto:</b> ${valOrFalta(cliente.contacto)}</div>
        <div class="linea"><b>Email:</b> ${valOrFalta(cliente.email)}</div>
        <div class="linea"><b>Teléfono:</b> ${valOrFalta(cliente.telefono)}</div>
      </div>

      <div class="box">
        <h4>Obra / Visita</h4>
        <div class="linea"><b>Título:</b> ${valOrFalta(obra.titulo)}</div>
        <div class="linea"><b>Dirección:</b> ${valOrFalta(obra.direccion)}</div>
        <div class="linea"><b>Fecha visita:</b> ${valOrFalta(obra.fecha)}</div>
        <div class="linea"><b>Agente:</b> ${valOrFalta(presup.agente)}</div>
        <div class="linea"><b>Descripción:</b> ${valOrFalta(obra.descripcion)}</div>
      </div>
    </div>
  `;
  // Desde la tarea 2 en adelante: nueva página + encabezado repetido
  const encabezadoInterno = idx === 0 ? '' : encabezadoPaginaSecundaria;

  const contenidoTarea = `
      ${encabezadoInterno}

      <section class="bloque">
        <h3>${esc(tituloTarea)}</h3>
        ${detalleTareaHtml}

        <h4>Materiales</h4>
        <table class="tabla-resumen">
          <tbody>
              <td class="label"><b>Subtotal Materiales</b></td>
              <td class="value"><b>${esc(subtotalMat || 'FALTA COMPLETAR')}</b></td>
            </tr>
          </tbody>
        </table>

        <h4 style="margin-top:14px;">Mano de obra</h4>
        <table class="tabla-resumen">
            <tr>
              <th class="num">Días</th>
          <tbody>
            <tr>
              <td class="label"><b>Subtotal Mano de Obra</b></td>
              <td class="value"><b>${esc(subtotalMO || 'FALTA COMPLETAR')}</b></td>
            </tr>
          </tbody>
        </table>

        <div class="subtarea"><b>${esc(subtTareaTxt.replace(/^Subtotal\s+Tarea\s+\d+\s*:\s*/i, 'Subtotal: '))}</b></div>

        <h4 style="margin-top:16px;">Imágenes</h4>
        <div class="fotos">${fotosHtml}</div>
      </section>
  `;

  if (idx === 0) {
    htmlPrimeraPagina += contenidoTarea;
    htmlPaginasTareas += paginasImagenesExtra;
  } else {
    htmlPaginasTareas += `
      <section class="print-page-task page-break-before">
        ${contenidoTarea}
      </section>
      ${paginasImagenesExtra}
    `;
  }
});

const condicionesPresupuestoHtml = `
    <div class="condiciones-presupuesto">
      <div class="condiciones-titulo">Condiciones Generales</div>

      <p><strong>Forma de pago:</strong><br>
      Anticipo 30%<br>
      Saldo al finalizar las tareas 70%</p>

      <p><strong>No se incluye:</strong></p>
      <ul class="condiciones-lista">
        <li>IVA.</li>
        <li>Seguros especiales.</li>
        <li>Certificado de disposición final del material.</li>
      </ul>

      <p><strong>Se incluye:</strong></p>
      <ul class="condiciones-lista">
        <li>Programa de seguridad para las tareas detalladas.</li>
        <li>Todos los requisitos de seg. e higiene (EPP, capacitaciones, ATS).</li>
        <li>Retiro de obra de materiales sobrantes.</li>
      </ul>

      <p><strong>Alcance del presupuesto:</strong> El presente presupuesto se ha efectuado en base a un examen in situ. El mismo se extiende por los trabajos cotizados y no es comprensivo de tareas o materiales adicionales que surjan en el hecho y ocasión de la ejecución de los trabajos y que no pudieran advertirse sin desmonte o ejecución previa. Dichos trabajos o materiales serán presupuestados y facturados en forma complementaria.</p>

      <p><strong>Comunicaciones:</strong> Toda comunicación entre las partes se efectuará por medios electrónicos. A tales efectos, constituyen domicilios en el email info@ecotechos.com.ar. La misma tendrá plenos efectos a los fines de la conformación de la voluntad contractual y los alcances del contrato.</p>

      <p><strong>Pólizas:</strong> En caso de que el cliente así lo solicitara y a fin de afianzar el cumplimiento de las obligaciones, se constituirán póliza por adelanto, póliza por fondo de reparo y póliza de responsabilidad civil. En caso de no reintegrarse la póliza en los plazos convenidos, quedará a cargo del cliente el pago del premio de la misma, el que podrá ser facturado como adicional.</p>

      <p><strong>Caución adelanto:</strong> Si así correspondiere, sobre el monto de adelanto se constituirá una póliza de caución a favor del cliente. La misma será reintegrada dentro de los 30 días de haberse cumplimentado con la certificación de avances por un importe igual o mayor al del anticipo.</p>

      <p><strong>Caución sobre fondo de reparo:</strong> Sobre el 5% del monto contratado se constituirá una póliza de caución. La misma será reintegrada dentro de los 30 días de haberse cumplimentado con la entrega de la última certificación de avance, en igual plazo.</p>

      <p><strong>Certificaciones y pagos:</strong> En caso de que corresponda, el proveedor / constructor emitirá las certificaciones de avance, que serán remitidas al domicilio electrónico constituido en el presente, quedando aprobadas a los 10 días de su recepción y en condiciones de facturación. Las observaciones no suspenderán los plazos. Los pagos deberán efectuarse dentro de los 7 días corridos de la emisión de la factura como plazo general, salvo concesión en particular. La mora será de pleno derecho sin necesidad de interpelación y se aplicará ajuste por Índice CAC sobre dichos valores.</p>

      <p><strong>Seguros:</strong> ECOTECHOS SRL cuenta con las coberturas de seguros (ART y vida) que las normas vigentes marcan según lo dispuesto en la Ley 24557 y los decretos reglamentarios 911/96 y 231/96, dejando al cliente libre de reclamos por accidentes personales. Cabe destacar que nuestra empresa posee un departamento de Seguridad e Higiene que inspecciona los elementos de seguridad de nuestro personal, además de capacitarlo permanentemente con respecto a las normas de trabajo seguro.</p>
    </div>
`;

const htmlPaginaTotal = `
  <section class="print-page-total page-break-before">
    <div class="top">
      <div class="logo">
        <img src="${esc(logoSrc)}" alt="Logo">
      </div>
      <div class="doc">
        <div class="doc-label">Presupuesto Nro:</div>
        <div class="muted">N°: ${esc(idPresupuesto)} | Fecha: ${esc(new Date().toLocaleDateString())}</div>
      </div>
    </div>

    <hr>

    <div class="grid">
      <div class="box">
        <h4>Cliente</h4>
        <div class="linea"><b>Razón social:</b> ${valOrFalta(cliente.razon_social)}</div>
        <div class="linea"><b>CUIT:</b> ${valOrFalta(cliente.cuit)}</div>
        <div class="linea"><b>Dirección:</b> ${valOrFalta(cliente.direccion)}</div>
        <div class="linea"><b>Contacto:</b> ${valOrFalta(cliente.contacto)}</div>
        <div class="linea"><b>Email:</b> ${valOrFalta(cliente.email)}</div>
        <div class="linea"><b>Teléfono:</b> ${valOrFalta(cliente.telefono)}</div>
      </div>

      <div class="box">
        <h4>Obra / Visita</h4>
        <div class="linea"><b>Título:</b> ${valOrFalta(obra.titulo)}</div>
        <div class="linea"><b>Dirección:</b> ${valOrFalta(obra.direccion)}</div>
        <div class="linea"><b>Fecha visita:</b> ${valOrFalta(obra.fecha)}</div>
        <div class="linea"><b>Agente:</b> ${valOrFalta(presup.agente)}</div>
        <div class="linea"><b>Descripción:</b> ${valOrFalta(obra.descripcion)}</div>
      </div>
    </div>

    <div class="total">
      <div class="valor">TOTAL: ${esc(totalTxt)}</div>
    </div>

    ${condicionesPresupuestoHtml}
  </section>
`;

        // HTML completo (lo usamos para extraer el <body>)
        const baseHref = location.href.replace(/[#?].*$/, '').replace(/[^/]*$/, '');
        const html = `
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <base href="${esc(baseHref)}">
  <title>${esc(nombreArchivoPdf)}</title>

  <style>
  * { box-sizing: border-box; }
  html, body { padding: 0; margin: 0; width: 100%; overflow-x: hidden; }
  body { font-family: Arial, Helvetica, sans-serif; color:#111; margin: 0; }

  @page { size: A4; margin: 10mm; }

  .page { width: 100%; max-width: 100%; padding: 0; overflow-x: hidden; }
  .print-page-total { display:flex; flex-direction:column; }
  .print-page-task-imagenes-extra { display:flex; align-items:stretch; }

  .top { display:flex; align-items:center; justify-content:space-between; gap:14px; }
  .logo img { max-width: 220px; height:auto; }
  .doc { text-align:right; }
  .doc h1 { margin:0; font-size: 18px; letter-spacing: .2px; }
  .doc-label { margin:0; font-size: 18px; font-weight: 400; letter-spacing: .2px; }
  .doc-code { margin-top: 2px; font-size: 15px; font-weight: 700; line-height: 1.2; }
  .doc-page { margin-top: 2px; font-size: 10px; font-weight: 700; line-height: 1.2; color:#555; }
  .muted { color:#666; font-size: 11px; margin-top: 2px; }
  hr { border:0; border-top:1px solid #ddd; margin: 10px 0 12px; }

  .box { border:1px solid #ddd; border-radius: 8px; padding: 10px 12px; margin-bottom: 8px; }
  .grid { display:grid; grid-template-columns: 1fr 1fr; gap:8px; }
  .linea { margin: 3px 0; font-size: 12px; line-height: 1.25; }
  .detalle-tarea { font-size: 12px; line-height: 1.35; overflow-wrap: anywhere; }
  .detalle-tarea p,
  .detalle-tarea div,
  .detalle-tarea ul,
  .detalle-tarea ol { margin: 0 0 6px; }
  .detalle-tarea p:last-child,
  .detalle-tarea div:last-child,
  .detalle-tarea ul:last-child,
  .detalle-tarea ol:last-child { margin-bottom: 0; }
  .detalle-tarea ul,
  .detalle-tarea ol { padding-left: 18px; }
  .detalle-tarea strong,
  .detalle-tarea b { font-weight: 700; }
  .detalle-tarea em,
  .detalle-tarea i { font-style: italic; }
  .detalle-tarea u { text-decoration: underline; }

  .falta { color:#b00020; font-weight:700; }

  .bloque { margin-top: 10px; break-inside: avoid; page-break-inside: avoid; }
  h3 { margin: 0 0 8px; font-size: 16px; font-weight: 700; border-left: 4px solid #111; padding-left: 8px; text-transform: uppercase; }
  h4 { margin: 8px 0 6px; font-size: 12px; text-transform: uppercase; letter-spacing: .02em; color:#333; }

  .grid2 { display:grid; grid-template-columns: 2fr 1fr; gap:10px; }

  table { width:100%; max-width:100%; border-collapse: collapse; font-size: 11px; table-layout: fixed; }
  th, td { border:1px solid #ddd; padding: 5px 6px; vertical-align: top; }
  th { background:#f5f5f5; text-align:left; }
  .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
  .tabla-resumen td { padding: 6px 10px; }
  .tabla-resumen .label { width: 86%; text-align: right; }
  .tabla-resumen .value { width: 14%; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }

  table, tr, td, th { page-break-inside: avoid; break-inside: avoid; }

  .subtarea { margin-top: 8px; padding: 7px 9px; background:#f7f7f7; border:1px solid #ddd; border-radius: 8px; text-align:right; font-weight: 700; }

  .fotos {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: flex-start;
    align-content: flex-start;
    overflow: hidden;
  }

  .foto-slot {
    flex: 0 0 calc(50% - 5px);
    width: calc(50% - 5px);
    max-width: calc(50% - 5px);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fafafa;
    padding: 6px;
  }

  .foto {
    display: block;
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    break-inside: avoid;
    page-break-inside: avoid;
  }

  .imagenes-extra-grid {
    width: 100%;
    min-height: ${A4_HEIGHT_PX - (A4_MARGIN_PX * 2)}px;
    height: ${A4_HEIGHT_PX - (A4_MARGIN_PX * 2)}px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-template-rows: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }

  .imagen-extra-slot {
    min-width: 0;
    min-height: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fafafa;
    padding: 6px;
  }

  .imagen-extra {
    display: block;
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
  }

  .total { display:flex; justify-content:flex-end; margin-top: 10px; break-inside: avoid; }
  .total .valor { font-size: 16px; font-weight: 800; padding: 8px 12px; border: 2px solid #111; border-radius: 10px; }

  .condiciones-presupuesto {
    margin-top: auto;
    padding-top: 14px;
    border-top: 1px solid #ddd;
    font-size: 9px;
    line-height: 1.32;
    color:#333;
  }
  .condiciones-titulo {
    margin-bottom: 6px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color:#222;
  }
  .condiciones-presupuesto p {
    margin: 0 0 6px;
    text-align: justify;
  }
  .condiciones-lista {
    margin: 0 0 6px 0;
    padding-left: 14px;
  }
  .condiciones-lista li {
    margin-bottom: 1px;
  }

  @media print {
    body { margin: 0; }
  
    html, body, .page, .print-page-task, .print-page-total {
      width: 100% !important;
      max-width: 100% !important;
      overflow-x: hidden !important;
    }

    .box, .bloque, .fotos, .foto, table, tr, td, th {
      break-inside: avoid;
      page-break-inside: avoid;
    }
  
    .print-page-task,
    .print-page-total {
      min-height: 100vh;
      break-inside: avoid;
      page-break-inside: avoid;
    }
  
    .page-break-before {
      break-before: page;
      page-break-before: always;
    }
  }
  </style>
</head>
<body>
<div class="page">
  <section class="print-page-task">
    <div class="top">
      <div class="logo">
        <img src="${esc(logoSrc)}" alt="Logo">
      </div>
      <div class="doc">
        <div class="doc-label">Presupuesto Nro:</div>
        <div class="muted">N°: ${esc(idPresupuesto)} | Fecha: ${esc(new Date().toLocaleDateString())}</div>
      </div>
    </div>

    <hr>

    <div class="grid">
      <div class="box">
        <h4>Cliente</h4>
        <div class="linea"><b>Razón social:</b> ${valOrFalta(cliente.razon_social)}</div>
        <div class="linea"><b>CUIT:</b> ${valOrFalta(cliente.cuit)}</div>
        <div class="linea"><b>Dirección:</b> ${valOrFalta(cliente.direccion)}</div>
        <div class="linea"><b>Contacto:</b> ${valOrFalta(cliente.contacto)}</div>
        <div class="linea"><b>Email:</b> ${valOrFalta(cliente.email)}</div>
        <div class="linea"><b>Teléfono:</b> ${valOrFalta(cliente.telefono)}</div>
      </div>

      <div class="box">
        <h4>Obra / Visita</h4>
        <div class="linea"><b>Título:</b> ${valOrFalta(obra.titulo)}</div>
        <div class="linea"><b>Dirección:</b> ${valOrFalta(obra.direccion)}</div>
        <div class="linea"><b>Fecha visita:</b> ${valOrFalta(obra.fecha)}</div>
        <div class="linea"><b>Agente:</b> ${valOrFalta(presup.agente)}</div>
        <div class="linea"><b>Descripción:</b> ${valOrFalta(obra.descripcion)}</div>
      </div>
    </div>

    ${htmlPrimeraPagina || `<div class="box"><span class="falta">FALTA COMPLETAR</span></div>`}
  </section>
  ${htmlPaginasTareas}
  ${htmlPaginaTotal}
</div>
        </body>
</html>`;

        // 0) Asegurar URL absoluta del logo
        const logoAbs = (() => {
          try { return new URL(logoSrc, window.location.href).href; }
          catch (e) { return logoSrc; }
        })();

        asegurarHostRenderStyles();
        const $printRoot = obtenerHostRender();

        // Extraer CSS y body
        const cssMatch = html.match(/<style[^>]*>([\s\S]*?)<\/style>/i);
        let cssInterno = cssMatch ? cssMatch[1] : '';

        const bodyMatch = html.match(/<body[^>]*>([\s\S]*)<\/body>/i);
        let soloBody = bodyMatch ? bodyMatch[1] : html;

        soloBody = soloBody.replace(
          /<div class="grid">[\s\S]*?<h4>Cliente<\/h4>[\s\S]*?<h4>Obra \/ Visita<\/h4>[\s\S]*?<\/div>\s*<\/div>\s*<\/div>/g,
          encabezadoDatosHtml
        );

        soloBody = soloBody.replace(
          /<table class="tabla-resumen">\s*<tr>[\s\S]*?<tbody>/g,
          '<table class="tabla-resumen"><tbody>'
        );

        let numeroPaginaActual = 0;
        soloBody = soloBody.replace(
          /<div class="muted">[\s\S]*?<\/div>/g,
          () => {
            numeroPaginaActual += 1;
            return `<div class="doc-code">${esc(numeroPresupuestoVisual)}</div><div class="doc-page">Página Nro: ${esc(numeroPaginaActual)}</div>`;
          }
        );

        // 4) Corregir logo a absoluto
        soloBody = soloBody.replace(
          /<img\s+src="[^"]*"\s+alt="Logo">/g,
          `<img src="${logoAbs}" alt="Logo">`
        );

        // Inyectar en el host offscreen para renderizar el PDF
        $printRoot.html(`<style>${cssInterno}</style>${soloBody}`);

        const pdfBlob = await renderizarDocumentoComoPdf($printRoot);
        const respuestaEmision = await subirDocumentoEmitido({
          idPresupuesto,
          idPrevisita: idPrevisitaImpresion,
          nombreArchivoPdf,
          pdfBlob
        });

        if (!respuestaEmision?.ok) {
          throw new Error(respuestaEmision?.msg || 'No se pudo guardar el documento emitido.');
        }

        if (respuestaEmision?.intervino && typeof window.actualizarIntervinoPresupuestoUI === 'function') {
          window.actualizarIntervinoPresupuestoUI(respuestaEmision.intervino);
        }

        descargarBlobDocumento(
          pdfBlob,
          String(respuestaEmision?.nombre_archivo || `${nombreArchivoPdf}.pdf`)
        );
        mostrarSwalDocumentoExito('El documento fue generado, guardado en el sistema y descargado correctamente.');

        return false;

      } catch (err) {
        console.log('Error al generar documento del presupuesto:', err);
        const mensajeErrorDocumento = extraerMensajeErrorDocumento(err);
        const mensajeErrorDocumentoHtml = mensajeErrorDocumento
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;');
        mostrarSwalDocumentoError(mensajeErrorDocumentoHtml);
        return false;
      } finally {
        limpiarHostRender();
        window.__PRESU_PRINT_LOCK__ = false;
        $btn.prop('disabled', false);
      }
    });
})();
// código de emisión de documento ////////////////////////////////////////////

});

// Endpoint (ajustá si cambió)
const ENDPOINT = window.URL_GUARDAR_PRESUPUESTO || '../03-controller/presupuestos_guardar.php';
