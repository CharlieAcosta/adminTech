$(document).ready(function() {
  // Objeto global donde vamos guardando imágenes por tarea
  const imagenesPorTarea = {};
  let hayCambios = false;

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
    hayCambios = true;
  });

  // 2) Añadir o eliminar materiales / mano de obra también cuenta
  $(document).on('click', '.agregar-material, .eliminar-material, .agregar-mano-obra, .eliminar-mano-obra', function() {
    hayCambios = true;
  });

  // 3) Subir o eliminar fotos marca cambios
  $(document).on('change', '.tarea-fotos', function() {
    hayCambios = true;
  });
  $(document).on('click', '.eliminar-imagen', function() {
    hayCambios = true;
  });

  // 1) Delegado para eliminar miniatura
  $(document).on('click', '.eliminar-imagen', function() {
    const $thumb = $(this).closest('.preview-img-container');
    const idx    = parseInt($thumb.closest('.preview-fotos').attr('id').split('_').pop(), 10);
    const nombre = $thumb.data('nombre-archivo');

    // Quitar de la UI
    $thumb.remove();

    // Registrar eliminación
    window.fotosEliminadasPorTarea = window.fotosEliminadasPorTarea || {};
    window.fotosEliminadasPorTarea[idx] = window.fotosEliminadasPorTarea[idx] || [];
    window.fotosEliminadasPorTarea[idx].push(nombre);

    // Actualizar también imagenesPorTarea si quieres
    if (imagenesPorTarea[idx]) {
      imagenesPorTarea[idx] = imagenesPorTarea[idx].filter(img => img.nombre !== nombre);
    }
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

    // Validaciones
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
        return false; // salir del each
      }
    });

    if (existe) {
      mostrarError('Este material ya fue asociado a la tarea.', 4);
      return;
    }

    // Si estaba la fila vacía, la sacamos
    $materialesTable.find('.fila-vacia-materiales').remove();

    // Número de fila
    var rowCount = $materialesTable.find('tr').length + 1;

    // Agregar fila
    var nuevaFila = `
      <tr data-material-id="${materialId}">
        <td>${rowCount}</td>
        <td>${materialText}</td>
        <td>${cantidad}</td>
        <td class="text-center">
            <i class="fa fa-trash v-icon-pointer text-danger eliminar-material" title="Eliminar material" style="cursor: pointer; font-size: 1.2rem;"></i>
        </td>
      </tr>
    `;

    $materialesTable.append(nuevaFila);

    // Limpiar campos
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

      // Eliminar fila vacía si existe
      const filaVacia = tabla.find('.fila-vacia-mano-obra');
      if (filaVacia.length) filaVacia.remove();

      // Contador de filas
      const index = tabla.find('tr').length + 1;

      const fila = `
        <tr>
          <td>${index}</td>
          <td>
              <input type="hidden" name="mano_obra_id[]" value="${id}">
              <span>${texto}</span>
          </td>
          <td>
              <span>${cantidad}</span>
              <input type="hidden" name="mano_obra_cantidad[]" value="${cantidad}">
          </td>
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
      fila.remove();

      const tabla = $('.mano-obra-table tbody');
      const filasRestantes = tabla.find('tr');

      if (filasRestantes.length === 0) {
          tabla.append(`
              <tr class="fila-vacia-mano-obra">
                  <td colspan="4" class="text-center text-muted">Sin mano de obra asociada</td>
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
      if (!window.fotosEliminadasPorTarea) {
          window.fotosEliminadasPorTarea = {};
      }
      if (!window.fotosEliminadasPorTarea[index]) {
          window.fotosEliminadasPorTarea[index] = [];
      }

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
console.log(`🗑️ Imagen eliminada en tarea ${index}:`, nombreArchivo);
console.log(`🧮 Imágenes restantes en tarea ${index}:`, imagenesPorTarea[index]);
                  contenedor.remove();
              });

              previewContainer.append(contenedor);
          };
          reader.readAsDataURL(file);
      });
  });

  // ACCIONES Y EVENTOS
  $(document).on('click', '#btn-agregar-tarea', function () {
      //console.log('Agregar nueva tarea clickeado');
        
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

  $(document).on('click', '.btn-guardar-visita', function () {
      //console.log('Guardar visita clickeado');

      let faltaDescripcion = false;

      $('.tarea-descripcion').each(function () {
          if ($(this).val().trim() === '') {
              faltaDescripcion = true;
              $(this).addClass('is-invalid');
          } else {
              $(this).removeClass('is-invalid');
          }
      });

      if (faltaDescripcion) {
          mostrarAdvertencia('Debe completar la descripción de todas las tareas antes de guardar.', 4);
          return;
      }
  });

  $(document).on('input', '.tarea-descripcion', function () {
      if ($(this).val().trim() !== '') {
          $(this).removeClass('is-invalid');
      }
  });

  $(document).on('input', '.tarea-descripcion', function () {
      const texto = $(this).val().trim();

      // Desde el textarea, subimos hasta el collapse y obtenemos el id (ej. collapseTarea1)
      const collapseId = $(this).closest('.collapse').attr('id');

      // Luego buscamos el encabezado asociado (tiene el data-target a ese id)
      const encabezado = $(`button[data-target="#${collapseId}"]`);

      let preview = texto.length > 100 ? texto.substring(0, 100) + '...' : texto;

      if (preview !== '') {
          encabezado.html(`<strong>${encabezado.data('titulo-base')}:</strong> ${preview}`);
      } else {
          encabezado.html(`<strong>${encabezado.data('titulo-base')}:</strong> Breve descripción`);
      }
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
                                          <thead class="thead-light">
                                              <tr>
                                                  <th>#</th>
                                                  <th>Mano de obra</th>
                                                  <th>Cantidad</th>
                                                  <th>Observaciones</th>
                                                  <th>Acción</th>
                                              </tr>
                                          </thead>
                                          <tbody>
                                              <tr class="fila-vacia-mano-obra">
                                                  <td colspan="5" class="text-center text-muted">Sin mano de obra asociada</td>
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

      // Evitar eliminar si queda solo una
      if (totalTareas === 1) {
          mostrarAdvertencia('Debe quedar al menos una tarea.', 4);
          return;
      }

      // Eliminar la tarea actual
      $(this).closest('.card').remove();

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
          .removeAttr('name')  // ✅ ELIMINA ESE NAME SI EXISTE
          .attr('data-index', nuevoIndex);
          card.find('.custom-file-label').attr('for', `fotos_tarea_${nuevoIndex}`);
          card.find('.preview-fotos').attr('id', `preview_fotos_tarea_${nuevoIndex}`);
      });
  });

  // Guardar visita
  $(document).on('click', '.btn-guardar-visita', function () {
      let tareas = [];
      let hayError = false;

      // 🔄 Reconstruir imagenesPorTarea a partir de previews ya existentes (modo edición)
      $('.preview-fotos').each(function () {
          const previewContainer = $(this);
          const tareaIndex = parseInt(previewContainer.attr('id').replace('preview_fotos_tarea_', ''), 10);

          if (!imagenesPorTarea[tareaIndex]) {
              imagenesPorTarea[tareaIndex] = [];
          }

          previewContainer.find('.preview-img-container').each(function () {
              const nombreArchivo = $(this).data('nombre-archivo');

              // Creamos un objeto falso solo con el nombre (sin file real), para evitar duplicados
              if (!imagenesPorTarea[tareaIndex].some(img => img.nombre === nombreArchivo)) {
                  imagenesPorTarea[tareaIndex].push({ file: null, nombre: nombreArchivo });
              }
          });
      });

      $('#accordionTareas > .card').each(function (index) {
          const $tarea = $(this);
          const $descripcionElem = $tarea.find('.tarea-descripcion');
          if ($descripcionElem.length === 0) {
              hayError = true;
              console.warn(`No se encontró el campo de descripción en la tarea #${index + 1}`);
              return;
          }

          const idTareaVal = $tarea.find('input[name="id_tarea[]"]').val() || null;
          const descripcionVal = $descripcionElem.val() ?? '';
          const idTarea = $tarea.data('id-tarea') ?? null;

          if (descripcionVal.trim() === '') {
              $descripcionElem.addClass('is-invalid');
              hayError = true;
              return;
          } else {
              $descripcionElem.removeClass('is-invalid');
          }

          // Recolectar materiales
          let materiales = [];
          $tarea.find('.materiales-table tbody tr').each(function () {
              if (!$(this).hasClass('fila-vacia-materiales')) {
                  const id = $(this).data('material-id');
                  const cantidad = $(this).find('td:nth-child(3)').text();
                  materiales.push({ id, cantidad });
              }
          });

          // Recolectar mano de obra
          let manoDeObra = [];
          $tarea.find('.mano-obra-table tbody tr').each(function () {
              if (!$(this).hasClass('fila-vacia-mano-obra')) {
                  const id = $(this).find('input[name="mano_obra_id[]"]').val();
                  const cantidad = $(this).find('input[name="mano_obra_cantidad[]"]').val();
                  const observacion = $(this).find('input[name="mano_obra_observacion[]"]').val();
                  manoDeObra.push({ id, cantidad, observacion });
              }
          });

          // Recolectar fotos
          const fotosInput = $tarea.find('.tarea-fotos')[0];
          const fotos = imagenesPorTarea[index + 1] ?? [];

          // Recolectar fotos eliminadas
          let fotosEliminadas = [];
          const indexVisual = index + 1;
          if (window.fotosEliminadasPorTarea && window.fotosEliminadasPorTarea[indexVisual]) {
              fotosEliminadas = window.fotosEliminadasPorTarea[indexVisual];
          }

          tareas.push({
              id_tarea: idTareaVal,
              descripcion: descripcionVal.trim(),
              materiales,
              manoDeObra,
              fotos,
              fotosEliminadas
          });

      });

      if (hayError) {
          mostrarAdvertencia('Debe completar la descripción de todas las tareas.', 4);
          return;
      }

      const formData = new FormData();
      formData.append('id_visita', $('#id_previsita').val());

      tareas.forEach((tarea, i) => {
          if (tarea.id_tarea) {
              formData.append(`tareas[${i}][id_tarea]`, tarea.id_tarea);
          }

          formData.append(`tareas[${i}][descripcion]`, tarea.descripcion);

          tarea.materiales.forEach((mat, j) => {
              formData.append(`tareas[${i}][materiales][${j}][id]`, mat.id);
              formData.append(`tareas[${i}][materiales][${j}][cantidad]`, mat.cantidad);
          });

          tarea.manoDeObra.forEach((mo, j) => {
              formData.append(`tareas[${i}][mano_obra][${j}][id]`, mo.id);
              formData.append(`tareas[${i}][mano_obra][${j}][cantidad]`, mo.cantidad);
              formData.append(`tareas[${i}][mano_obra][${j}][observacion]`, mo.observacion);
          });

          // Adjuntar fotos eliminadas
          tarea.fotosEliminadas.forEach((nombreArchivo, j) => {
              formData.append(`tareas[${i}][fotos_eliminadas][${j}]`, nombreArchivo);
          });

          tarea.fotos.forEach((imgObj, j) => {
              const clave = `foto_tarea_${i}_${j}`;
              formData.append(clave, imgObj.file);
          });

      });

      // Enviar por AJAX
      for (var pair of formData.entries()) {
        console.log(pair[0]+ ':', pair[1]);
      }      
      $.ajax({
          url: '../06-funciones_php/guardar_visita.php',
          method: 'POST',
          data: formData,
          contentType: false,
          processData: false,
          success: function (resp) {
              console.log('Respuesta servidor:', resp);
              try {
                  const res = resp;
                  if (res.status === true) {
                      $('#accordionTareas > .card').each(function (index) {
                        $(this).attr('data-id-tarea', res.ids_tareas[index]);
                        // Insertar input oculto con id_tarea si no existe ya
                        let inputId = $(this).find('input[name="id_tarea[]"]');
                        if (inputId.length === 0) {
                            $(this).find('.tarea-descripcion')
                            .closest('.card-body')
                            .append(`<input type="hidden" class="tarea-id-oculto" name="id_tarea[]" value="${res.ids_tareas[index]}">`);
                        } else {
                            inputId.val(res.ids_tareas[index]);
                        }
                      });
                      hayCambios = false;        // <— RESET del flag
                      mostrarExito('Visita guardada correctamente', 4);
                  } else {
                      mostrarError(res.mensaje || 'Error al guardar.', 4);
                  }
              } catch (e) {
                  mostrarError('Respuesta inválida del servidor.', 4);
              }
          },
          error: function () {
              mostrarError('Error al comunicar con el servidor.', 4);
          }
      });
  });


  $(document).on('click', '.btn-cancelar-visita', function() {
    if (!hayCambios) {
      // No hay cambios, vamos directo
      window.location.href = 'seguimiento_de_obra_listado.php';
    } else {
      // Hay cambios, pedimos confirmación
      mostrarConfirmacion(
        'Tiene cambios sin guardar, <strong>¿continúas de todas maneras?</strong>',
        // onConfirm
        () => window.location.href = 'seguimiento_de_obra_listado.php',
        // onCancel (opcional, lo dejamos null para que cierre el modal)
        null
      );
    }
  });


  // ======= POBLAR DESDE EL BACKEND =======
  if (typeof tareasVisitadas !== 'undefined' && tareasVisitadas.length) {
    // Población de la primera tarea (ya existe en el HTML inicial)
    const primera = tareasVisitadas[0];
    $('#collapseTarea1 textarea.tarea-descripcion')
      .val(primera.descripcion)
      .trigger('input');

    // Materiales
    primera.materiales.forEach(mat => {
      const $btn = $('#collapseTarea1 .agregar-material');
      const $sel = $btn.closest('.card-body').find('.material-select');
      $sel.val(mat.id_material).trigger('change');
      $btn.closest('.card-body').find('.material-cantidad').val(mat.cantidad);
      $btn.click();
    });

    // Mano de obra
    primera.mano_obra.forEach(mo => {
      const $btn = $('#collapseTarea1 .agregar-mano-obra');
      const $sel = $btn.closest('.card-body').find('.mano-obra-select');
      $sel.val(mo.id_jornal).trigger('change');
      $btn.closest('.card-body').find('.mano-obra-cantidad').val(mo.cantidad);
      $btn.click();
      // poner la observación
      $('#collapseTarea1 .mano-obra-table tbody tr:last')
        .find('input[name="mano_obra_observacion[]"]')
        .val(mo.observaciones);
    });

    // Fotos
    primera.fotos.forEach(f => {
      const cont = $('#preview_fotos_tarea_1');
      const thumb = $(`
        <div class="preview-img-container position-relative d-inline-block m-1" data-nombre-archivo="${f.nombre_archivo}">
          <img src="${f.ruta_archivo}" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;">
          <i class="fa fa-times-circle text-white rounded-circle position-absolute eliminar-imagen"
             style="top:0;right:0;cursor:pointer;font-size:1rem;"></i>
        </div>
      `);
      cont.append(thumb);
      imagenesPorTarea[1] = imagenesPorTarea[1]||[];
      imagenesPorTarea[1].push({ file: null, nombre: f.nombre_archivo });
    });

    // Para las tareas adicionales
    for (let i = 1; i < tareasVisitadas.length; i++) {
      $('#btn-agregar-tarea').click();
      const tarea = tareasVisitadas[i];
      const num = i + 1;
      const $card = $(`#headingTarea${num}`).closest('.card');

      // Descripción
      $card.find('textarea.tarea-descripcion')
           .val(tarea.descripcion).trigger('input');

      // Materiales
      tarea.materiales.forEach(mat => {
        const $b = $card.find('.agregar-material');
        const $s = $b.closest('.card-body').find('.material-select');
        $s.val(mat.id_material).trigger('change');
        $b.closest('.card-body').find('.material-cantidad').val(mat.cantidad);
        $b.click();
      });

      // Mano de obra
      tarea.mano_obra.forEach(mo => {
        const $b = $card.find('.agregar-mano-obra');
        const $s = $b.closest('.card-body').find('.mano-obra-select');
        $s.val(mo.id_jornal).trigger('change');
        $b.closest('.card-body').find('.mano-obra-cantidad').val(mo.cantidad);
        $b.click();
        $card.find('.mano-obra-table tbody tr:last')
             .find('input[name="mano_obra_observacion[]"]')
             .val(mo.observaciones);
      });

      // Fotos
      tarea.fotos.forEach(f => {
        const cont = $(`#preview_fotos_tarea_${num}`);
        const thumb = $(`
          <div class="preview-img-container position-relative d-inline-block m-1" data-nombre-archivo="${f.nombre_archivo}">
            <img src="${f.ruta_archivo}" class="img-thumbnail" style="width:100px;height:100px;object-fit:cover;">
            <i class="fa fa-times-circle text-white rounded-circle position-absolute eliminar-imagen"
               style="top:0;right:0;cursor:pointer;font-size:1rem;"></i>
          </div>
        `);
        cont.append(thumb);
        imagenesPorTarea[num] = imagenesPorTarea[num]||[];
        imagenesPorTarea[num].push({ file: null, nombre: f.nombre_archivo });
      });
    }
  }
// ======= FIN POBLACIÓN BACKEND =======

});
