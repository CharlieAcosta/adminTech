// Porcentajes por defecto para el presupuesto
const porcentajesPorDefecto = {
  materiales: 30,      // Utilidad materiales (puedes cambiarlo fácil)
  mano_obra: 100         // Placeholder para futuro uso en mano de obra
};

$(document).ready(function() {
    let modoVisualizacion = false;
    let presupuestoGenerado = false;

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
      
        const habilitado = tieneTareas && todasTienenMaterial && todasTienenManoObra && !hayCambios && !modoVisualizacion && !presupuestoGenerado;
      
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


      // Agregar fila
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
              <input type="number" class="form-control form-control-sm mano-obra-dias" name="mano_obra_dias[]" value="1" min="1" style="min-width: 60px; max-width: 60px;">
            </div>
          </td>
          <td class="text-center mano-obra-jornales">${cantidad}</td>               
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

              $('.tarea-descripcion').each(function () {
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


    $(document).on('input', '.tarea-descripcion', function () {
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

          $('.tarea-descripcion').each(function () {
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
        $('.tarea-descripcion').each(function () {
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
      presupuestoGenerado = true; 
      // 1. Si no existe el accordion, lo crea usando el template
      if ($('#accordionPresupuesto').length === 0) {
          const tpl = document.getElementById('tpl-accordion-presupuesto');
          if (tpl) {
              const clone = tpl.content.cloneNode(true);
              // Insertarlo después del accordion de Visita
              $('#accordionVisita').after(clone);
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
    if (Array.isArray(tareasVisitadas) && tareasVisitadas.length) {
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
    
    // ======= Obtiene los datos para el presupuesto =======
    function recolectarDatosParaPresupuesto() {
      // === CLIENTE ===
      const razon_social = $('#razon_social').val() || null;
      const cuit = $('#cuit').val() || null;
      const contacto = $('#contacto_obra').val() || null;
      const email = $('#email_contacto_obra').val() || null;
      const telefono = $('#tel_contacto_obra').val() || null;

      const calle = $('#select2-calle_visita-container').text().trim();
      const altura = $('#altura_visita').val()?.trim() || '';
      const localidad = $('#select2-localidad_visita-container').text().trim();
      const partido = $('#select2-partido_visita-container').text().trim();
      const provincia = $('#select2-provincia_visita-container').text().trim();
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
    
    
    /**
     * Calcula y actualiza el subtotal de una fila de mano de obra.
     * subtotal = cantidad×valorJornal + extraFijo + %extra sobre el resultado.
     */
    function calcularFilaManoObra($tr) {
      const cantidad = parseFloat($tr.find('.cantidad-mano-obra').val()) || 0;
      const jornal   = parseFloat($tr.find('.valor-jornal').val())       || 0;
    
      let subtotal = cantidad * jornal;
    
      const porcentajeExtra = parseFloat($tr.find('.porcentaje-extra').val()) || 0;
      subtotal += subtotal * (porcentajeExtra / 100);
    
      $tr.find('.subtotal-mano').text(formatMoney(subtotal));
    }
   
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
      // === 1) Subtotal Materiales ===
      let sumaMat = 0;
      $card.find('.tarea-materiales tbody tr').not('.fila-subtotal').each(function() {
        const txt = $(this).find('.subtotal-material').text().replace('$','').replace(/\./g,'').replace(',','.') || 0;
        sumaMat += parseFloat(txt) || 0;
      });
    
      // Utilidad global de materiales (input en fila de subtotales)
      const utilidadMatInput = parseFloat($card.find('.tarea-materiales .fila-subtotal input').val()) || porcentajesPorDefecto.materiales;
      const sumaMatConUtil = sumaMat + (sumaMat * (utilidadMatInput / 100));
      $card.find('.tarea-materiales .fila-subtotal td:last b').text(formatMoney(sumaMatConUtil));
      $card.find('.subt-util-materiales').text('Subtotal Util. Mat.: ' + formatMoney(sumaMat * (utilidadMatInput / 100)));
      // === 2) Subtotal Mano de Obra ===
      let sumaMan = 0;
      $card.find('.tarea-mano-obra tbody tr').not('.fila-subtotal').each(function() {
        const txt = $(this).find('.subtotal-mano').text().replace('$','').replace(/\./g,'').replace(',','.') || 0;
        sumaMan += parseFloat(txt) || 0;
      });
    
      const utilidadManInput = parseFloat($card.find('.tarea-mano-obra .fila-subtotal input').val()) || porcentajesPorDefecto.mano_obra;
      const sumaManConUtil = sumaMan + (sumaMan * (utilidadManInput / 100));
    
      $card.find('.tarea-mano-obra .fila-subtotal td:last b').text(formatMoney(sumaManConUtil));
      $card.find('.subt-util-manoobra').text('Subtotal Util. MO.: ' + formatMoney(sumaMan * (utilidadManInput / 100)));
    
      // === 3) Subtotal combinados ===
      const sumaUtilTotal = (sumaMat * utilidadMatInput / 100) + (sumaMan * utilidadManInput / 100);
      $card.find('.subt-util-total').text('Sub Util. Mat.+MO.: ' + formatMoney(sumaUtilTotal));
    
      // === 4) Subtotal por tarea (botón principal) ===
      const totalTarea = sumaMatConUtil + sumaManConUtil;
      const num = $card.find('.tarea-encabezado b').text().match(/\d+/)[0] || '';
      $card
        .find(`#subt-tarea-${num}`)
        .data('monto', totalTarea)
        .text(`Subtotal Tarea ${num}: ${formatMoney(totalTarea)}`);

    }
    
    
    /**
     * Calcula y actualiza los botones de utilidades y subtotal de tarea para una tarjeta de tarea.
     * @param {number} numeroTarea - El índice de la tarea (1-based).
     * @param {jQuery} $card - El contenedor .tarea-card correspondiente.
     */
    function actualizarTotalesPorTarea(numeroTarea, $card) {
      // 1) SUMAS BASE (ya incluyen % Extra de cada fila)
      let sumaMat = 0;
      $card.find('.tarea-materiales tbody tr').not('.fila-subtotal').each(function () {
        const txt = $(this).find('.subtotal-material').text().replace('$','').replace(/\./g,'').replace(',','.') || 0;
        sumaMat += parseFloat(txt) || 0;
      });
      let sumaMan = 0;
      $card.find('.tarea-mano-obra tbody tr').not('.fila-subtotal').each(function () {
        const txt = $(this).find('.subtotal-mano').text().replace('$','').replace(/\./g,'').replace(',','.') || 0;
        sumaMan += parseFloat(txt) || 0;
      });
    
      // 2) % UTILIDAD GLOBAL (si está vacío usa defaults)
      const utilMatPct = parseFloat($card.find('.tarea-materiales .fila-subtotal input').val()) || porcentajesPorDefecto.materiales;
      const utilMoPct  = parseFloat($card.find('.tarea-mano-obra .fila-subtotal input').val())   || porcentajesPorDefecto.mano_obra;
    
      // 3) MONTOS DE UTILIDAD
      const utilMat = sumaMat * (utilMatPct / 100);
      const utilMO  = sumaMan * (utilMoPct  / 100);
      const utilTotal = utilMat + utilMO;
    
      // 4) SUBTOTAL DE TAREA (con utilidad incluida) ya lo setea actualizarSubtotalesBloque; lo leemos
      let totalTarea = parseFloat($card.find(`#subt-tarea-${numeroTarea}`).data('monto'));
      if (isNaN(totalTarea)) {
        const txt = $card.find(`#subt-tarea-${numeroTarea}`).text();
        const m = txt.match(/\$([\d\.]+),(\d{2})/);
        if (m) {
          const entero = m[1].replace(/\./g, '');
          const decimal = m[2];
          totalTarea = parseFloat(`${entero}.${decimal}`) || 0;
        } else {
          totalTarea = 0;
        }
      }
    
      // 5) REFRESCAR BOTONES DE UTILIDAD
      $(`#subt-util-materiales-${numeroTarea}`).html(`Subtotal Util. Mat.: <strong>${formatMoney(utilMat)}</strong>`);
      $(`#subt-util-manoobra-${numeroTarea}`).html(`Subtotal Util. MO.: <strong>${formatMoney(utilMO)}</strong>`);
      $(`#subt-util-total-${numeroTarea}`).html(`Sub Util. Mat.+MO.: <strong>${formatMoney(utilTotal)}</strong>`);
      $(`#subt-tarea-${numeroTarea}`).html(`Subtotal Tarea ${numeroTarea}: <strong>${formatMoney(totalTarea)}</strong>`);
    
      // 6) IMPUESTOS / COSTOS (igual que antes)
      const iibb       = totalTarea * 1.21 * 0.03;  // 3% sobre total con IVA
      const ganancia35 = utilTotal  * 0.35;         // 35% sobre utilidad
      const costoInv3  = totalTarea * 0.03;         // 3% sobre total
      const impCheque  = totalTarea * 0.012;        // 1.2% sobre total
    
      const utilRealFinal = utilTotal - (iibb + ganancia35 + impCheque + costoInv3);
      const porcentajeUtilidad = (totalTarea > 0) ? (utilRealFinal / totalTarea) * 100 : 0;
    
      // 7) REFRESCAR TAGS GRISES E INDICADORES
      $(`#iibb-${numeroTarea}`).html(`IIBB:<strong> ${formatMoney(iibb)}</strong>`);
      $(`#ganancias-${numeroTarea}`).html(`Ganancias 35%:<strong> ${formatMoney(ganancia35)}</strong>`);
      $(`#cheque-${numeroTarea}`).html(`Imp. cheque:<strong> ${formatMoney(impCheque)}</strong>`);
      $(`#inversion-${numeroTarea}`).html(`Costo inv. 3%:<strong> ${formatMoney(costoInv3)}</strong>`);
      $(`#utilfinal-${numeroTarea}`).html(`Util real final:<strong> ${formatMoney(utilRealFinal)}</strong>`);
      $(`#porcentajetarea-${numeroTarea}`).html(`% Utilidad:<strong> ${porcentajeUtilidad.toFixed(2)}%</strong>`);
    
      // Aplica color (limpia y setea)
      aplicarColorUtilidad($card, numeroTarea, porcentajeUtilidad);
    }
  
    /**
     * Recorre todos los botones de subtotal de tarea y suma sus valores,
     * luego actualiza el span .presupuesto-total-valor.
     */
    function actualizarTotalGeneral() { 
      let total = 0;
    
      $('.tarea-card').each(function() {
        const $card = $(this);
        if (!$card.find('input.incluir-en-total').is(':checked')) return;
    
        const $btn = $card.find('button[id^="subt-tarea-"]');
        if (!$btn.length) return;
    
        let monto = parseFloat($btn.data('monto'));
        if (isNaN(monto)) {
          const txt = $btn.text();
          const m = txt.match(/\$([\d\.]+),(\d{2})/);
          if (m) {
            const entero = m[1].replace(/\./g, '');
            const decimal = m[2];
            monto = parseFloat(`${entero}.${decimal}`) || 0;
          } else {
            monto = 0;
          }
        }
        total += monto;
      });
    
      $('.presupuesto-total-valor').text(formatMoney(total));
    }
    
  
    function renderizarPresupuestoDesdeDatos(datos) {
      const contenedor = $('#contenedorPresupuestoGenerado');
      contenedor.empty();
      const hoy = new Date();
      
      datos.tareas.forEach((tarea, index) => {
        const numeroTarea = index + 1;
        const descripcion = tarea.descripcion || '';
    
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
        const cantidadMo = mo.cantidad ?? 0;

        let claseAlerta = '';
        let readonly = '';

        if (mo.updated_at) {
          const fechaMO = new Date(mo.updated_at);
          // Si está vencido (>30 días) → bg-danger (editable)
          // Si NO está vencido → bg-success + readonly
          if ((hoy - fechaMO) > (30 * 24 * 60 * 60 * 1000)) {
            claseAlerta = 'bg-danger';
          } else {
            claseAlerta = 'bg-success';
            readonly = 'readonly';
          }
        } else {
          // Si no hay updated_at, lo consideramos vigente por defecto
          claseAlerta = 'bg-success';
          readonly = 'readonly';
        }

        htmlManoObra += `
          <tr data-jornal_id="${mo.jornal_id ?? ''}">
            <td>${mo.nombre || ''}</td>
            <td>
              <input
                type="number"
                class="form-control form-control-sm cantidad-mano-obra"
                value="${cantidadMo}"
                min="0"
                step="any"
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

      const htmlTarea = `
      <div class="tarea-card">
        <div class="tarea-encabezado">
          <span>
            <i class="fas fa-tasks"></i>
            <b>Tarea ${numeroTarea}: ${descripcion}</b>
          </span>
          <label class="incluir-presupuesto-label">
            <input type="checkbox" class="incluir-en-total" checked>
            <span>Incluído en el presupuesto</span>
          </label>
        </div>

        <div class="container-fluid px-3 pt-3">
          <div class="row">
            <!-- Columna izquierda -->
            <div class="col-md-4 d-flex flex-column justify-content-between" style="min-height: 100%;">
              <!-- Detalle -->
              <div class="mb-2">
                <label class="mb-0"><b>Detalle de la tarea</b></label>
                <textarea class="form-control form-control-sm" rows="5">${descripcion}</textarea>
              </div>
              <!-- Fotos -->
              <div class="mb-2 flex-grow-1">
                <label><b>Fotos (próximamente)</b></label>
                <div class="preview-fotos border rounded bg-light p-3 d-flex align-items-center justify-content-center text-muted" style="min-height: 100px;">
                  <em>Aquí se mostrarán las imágenes</em>
                </div>
              </div>
            </div>

            <!-- Columna derecha -->
            <div class="col-md-8 d-flex flex-column justify-content-start">
              <!-- Materiales -->
              <div class="tarea-materiales mb-0 mt-0 pt-0">
                <div class="bloque-titulo mt-0 pt-0 mb-0">Materiales</div>
                <table class="tabla-presupuesto tabla-presupuesto-sm">
                  <thead>
                    <tr>
                      <th>Material</th>
                      <th>Cantidad</th>
                      <th>Precio Unitario</th>
                      <th>% Utilidad Materiales</th>
                      <th>% Extra</th>
                      <th>Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${htmlMateriales}
                    <tr class="fila-subtotal">
                      <td colspan="4" class="text-right"><b>Subtotal Materiales</b></td>
                      <td>
                        <input
                          type="number"
                          class="form-control form-control-sm utilidad-global-materiales"
                          min="0"
                          value="${tarea.utilidad_materiales ?? ''}"
                        >
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
                      <th>Cantidad</th>
                      <th>Valor Jornal</th>
                      <th>% Utilidad Mano de Obra</th>
                      <th>% Extra</th>
                      <th>Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${htmlManoObra}
                    <tr class="fila-subtotal">
                      <td colspan="4" class="text-right"><b>Subtotal Mano de Obra</b></td>
                      <td>
                        <input
                          type="number"
                          class="form-control form-control-sm utilidad-global-mano-obra"
                          min="0"
                          value="${tarea.utilidad_mano_obra ?? ''}"
                        >
                      </td>
                      <td class="text-right"><b>$0.00</b></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
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
          <div class="d-flex justify-content-end flex-wrap fila-impuestos mt-2 w-100" id="fila-impuestos-${numeroTarea}">
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
        
          <!-- ESTE NO SE OCULTA -->
          <div class="col-2 pr-0 pl-0">
            <button type="button" class="btn-total-tarea w-100 px-4 util-muy pt-2 mt-0" id="subt-tarea-${numeroTarea}">
              Subtotal Tarea ${numeroTarea}: $0,00
            </button>
          </div>
        </div>        
        </div>
      </div>`;
        
        contenedor.append(htmlTarea);

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
              <button class="btn btn-success mr-2"><i class="fas fa-save"></i> Guardar</button>
              <button class="btn btn-primary mr-2"><i class="fas fa-print"></i> Imprimir</button>
              <button class="btn btn-primary"><i class="fas fa-envelope"></i> Enviar por mail</button>
            </div>
            <div class="presupuesto-total-label">
              <span class="presupuesto-total-title">TOTAL PRESUPUESTO:</span>
              <span class="presupuesto-total-valor">$0.00</span>
            </div>
          </div>
        </div>`;
      contenedor.append(htmlTotal);
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
      // ¿Hay inputs en rojo?
      const hayVencidos = $('.precio-unitario.bg-danger, .valor-jornal.bg-danger').length > 0;
    
      // Botones de acciones del bloque total
      const $botones = $('.presupuesto-total-actions button');
    
      if (hayVencidos) {
        // Deshabilitar e indicar visualmente estado bloqueado
        $botones
          .prop('disabled', true)
          .addClass('btn-secondary')
          .removeClass('btn-success btn-primary');
    
        // Mostrar alerta una sola vez hasta que se corrija la situación
        if (!alertaDesactualizadosMostrada) {
          alertaDesactualizadosMostrada = true;

          const alertDanger = [
            false, // icono
            '<H3><strong>VALORES DESACTUALIZADOS</H3>', // título
            'Los campos en color rojo presentan precios desactualizados, para poder guardar el presupuesto deberá actualizar los valores.', // html
            'OK', // texto del botón
            false, // pie
            false, // clic fuera
            true, // permitir Escape
            '#dc3545', // color de fondo
            '#fff',    // 8 - color del texto general
            '#198754', // 9 - color de fondo del botón (default azul SweetAlert)
            '#fff'// 10 - color del texto del botón
          ];

          sAlertConfirmV2(alertDanger);

        }
      } else {
        // No hay vencidos: habilitar botones y resetear el flag para futuros alerts
        $botones.prop('disabled', false).each(function () {
          const $btn = $(this);
          // Restaurar estilos coherentes
          if ($btn.text().includes('Guardar')) {
            $btn.addClass('btn-success').removeClass('btn-secondary');
          } else {
            $btn.addClass('btn-primary').removeClass('btn-secondary');
          }
        });
    
        alertaDesactualizadosMostrada = false;
      }
    }
  
});
