$(document).ready(function() {
  // Objeto global donde vamos guardando imágenes por tarea
  const imagenesPorTarea = {};

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

      // Si aún no hay array para esta tarea, lo inicializamos
      if (!imagenesPorTarea[index]) {
          imagenesPorTarea[index] = [];
      }

      archivos.forEach((file) => {
          const reader = new FileReader();
          reader.onload = function (event) {
              const imgSrc = event.target.result;

              // Guardamos la imagen en base64
              imagenesPorTarea[index].push(imgSrc);

              const contenedor = $(`
                  <div class="preview-img-container position-relative d-inline-block m-1">
                      <img src="${imgSrc}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover; cursor: pointer;">
                      <i class="fa fa-times-circle text-white rounded-circle position-absolute eliminar-imagen" 
                         style="top: 0px; right: 0px; cursor: pointer; font-size: 1rem;"></i>
                  </div>
              `);

              // Ver imagen ampliada
              contenedor.find('img').on('click', () => {
                Swal.fire({
                    imageUrl: imgSrc,
                    imageAlt: 'Foto de la tarea',
                    showConfirmButton: false,
                    background: '#ffffff',
                    backdrop: 'rgba(0,0,0,0.7)',   // ← bloquea e impide clics fuera
                    allowOutsideClick: false,      // ← no permite cerrar haciendo clic fuera
                    allowEscapeKey: true,          // ← sí permite cerrar con Esc
                    width: 'auto',
                    padding: '1rem',
                    showCloseButton: true,
                    customClass: {
                        popup: 'shadow-lg rounded'
                    }
                });
              });

              // Eliminar preview visual + del array
              contenedor.find('.eliminar-imagen').on('click', () => {
                  contenedor.remove();

                  // Quitamos también del array
                  imagenesPorTarea[index] = imagenesPorTarea[index].filter(img => img !== imgSrc);
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
                                        name="tareas[${tareaIndex - 1}][fotos][]" 
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
            .attr('name', `tareas[${nuevoIndex - 1}][fotos][]`)
            .attr('data-index', nuevoIndex);
          card.find('.custom-file-label').attr('for', `fotos_tarea_${nuevoIndex}`);
          card.find('.preview-fotos').attr('id', `preview_fotos_tarea_${nuevoIndex}`);
      });
  });

  // Guardar visita
  $(document).on('click', '.btn-guardar-visita', function () {
      let tareas = [];
      let hayError = false;

      $('#accordionTareas > .card').each(function (index) {
          const $tarea = $(this);
          const $descripcionElem = $tarea.find('.tarea-descripcion');
          if ($descripcionElem.length === 0) {
              hayError = true;
              console.warn(`No se encontró el campo de descripción en la tarea #${index + 1}`);
              return;
          }

          const descripcionVal = $descripcionElem.val() ?? '';

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
          const fotos = fotosInput?.files ? Array.from(fotosInput.files) : [];

          tareas.push({ descripcion: descripcionVal.trim(), materiales, manoDeObra, fotos });
      });

      if (hayError) {
          mostrarAdvertencia('Debe completar la descripción de todas las tareas.', 4);
          return;
      }

      const formData = new FormData();
      formData.append('id_visita', $('#id_previsita').val());

      tareas.forEach((tarea, i) => {
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

          tarea.fotos.forEach((foto, j) => {
              const clave = `foto_tarea_${i}_${j}`;
              formData.append(clave, foto);
          });

      });

      // Enviar por AJAX
      $.ajax({
          url: '../06-funciones_php/guardar_visita.php',
          method: 'POST',
          data: formData,
          contentType: false,
          processData: false,
          beforeSend: () => {
              mostrarAdvertencia('Guardando datos...', 3);
          },
          success: function (resp) {
              console.log('Respuesta servidor:', resp);
              try {
                  const res = resp;
                  if (res.status === true) {
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


});
