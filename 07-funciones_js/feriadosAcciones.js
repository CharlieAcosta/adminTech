(function ($, window, document) {
  'use strict';

  var configuracion = document.getElementById('feriadosConfiguracion');
  if (!configuracion) {
    return;
  }

  var endpoint = configuracion.getAttribute('data-endpoint');
  var hoy = configuracion.getAttribute('data-hoy');
  var manana = configuracion.getAttribute('data-manana');
  var tabla = null;
  var csrfToken = null;
  var feriadoEdicion = null;
  var escrituraEnCurso = false;
  var lecturaEnCurso = false;
  var disparadorModal = null;

  function ErrorApiFeriados(message, status, code) {
    this.name = 'ErrorApiFeriados';
    this.message = message || 'No se pudo completar la solicitud.';
    this.status = status || 0;
    this.code = code || 'UNEXPECTED_RESPONSE';
  }
  ErrorApiFeriados.prototype = Object.create(Error.prototype);
  ErrorApiFeriados.prototype.constructor = ErrorApiFeriados;

  function escaparHtml(valor) {
    return String(valor == null ? '' : valor)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function fechaValida(fecha) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(String(fecha || ''))) {
      return false;
    }
    var partes = fecha.split('-');
    var fechaUtc = new Date(Date.UTC(Number(partes[0]), Number(partes[1]) - 1, Number(partes[2])));
    return fechaUtc.getUTCFullYear() === Number(partes[0])
      && fechaUtc.getUTCMonth() === Number(partes[1]) - 1
      && fechaUtc.getUTCDate() === Number(partes[2]);
  }

  function formatearFecha(fecha) {
    if (!fechaValida(fecha)) {
      return 'Fecha inválida';
    }
    var partes = fecha.split('-');
    return partes[2] + '/' + partes[1] + '/' + partes[0];
  }

  function esHistorico(feriado) {
    return fechaValida(feriado.fecha) && feriado.fecha <= hoy;
  }

  function normalizarFeriado(datos) {
    var id = Number(datos && datos.id_feriado);
    var estado = datos && datos.estado;
    if (!Number.isInteger(id) || id <= 0
      || !fechaValida(datos && datos.fecha)
      || typeof (datos && datos.descripcion) !== 'string'
      || (estado !== 'enabled' && estado !== 'disabled')) {
      throw new ErrorApiFeriados('La respuesta del servidor no tiene el formato esperado.', 0, 'UNEXPECTED_RESPONSE');
    }
    return {
      id_feriado: id,
      fecha: datos.fecha,
      descripcion: datos.descripcion,
      estado: estado
    };
  }

  function mensajeSeguro(error) {
    var mensajes = {
      INVALID_ACTION: 'La operación solicitada no es válida.',
      INVALID_DATA: 'Revisá los datos ingresados.',
      UNAUTHENTICATED: 'La sesión finalizó. Volvé a ingresar al sistema.',
      FORBIDDEN: 'Tu perfil no tiene permiso para administrar feriados.',
      METHOD_NOT_ALLOWED: 'La operación no admite el método utilizado.',
      NOT_FOUND: 'El feriado ya no se encuentra disponible.',
      DUPLICATE_DATE: 'Ya existe un feriado para la fecha indicada.',
      HISTORICAL_CONFLICT: 'La operación no está permitida para esa fecha histórica.',
      INVALID_STATE_TRANSITION: 'El cambio de estado solicitado no es válido.',
      CONCURRENCY_CONFLICT: 'El feriado cambió mientras lo editabas. Volvé a intentarlo.',
      AUDIT_FAILURE: 'No se pudo registrar la operación. No se aplicaron cambios.',
      DATABASE_ERROR: 'No se pudo completar la operación.',
      INTERNAL_ERROR: 'Ocurrió un error interno.',
      CSRF_VALIDATION_FAILED: 'No se pudo validar la solicitud.',
      NETWORK_ERROR: 'No se pudo conectar con el servidor.',
      INVALID_JSON: 'El servidor devolvió una respuesta inválida.',
      UNEXPECTED_RESPONSE: 'El servidor devolvió una respuesta inesperada.'
    };
    return mensajes[error && error.code] || 'No se pudo completar la operación.';
  }

  async function solicitar(accion, metodo, datos, token) {
    var opciones = {
      method: metodo,
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {
        Accept: 'application/json'
      }
    };
    var url = endpoint;

    if (metodo === 'GET') {
      var parametros = new URLSearchParams();
      parametros.set('accion', accion);
      Object.keys(datos || {}).forEach(function (clave) {
        parametros.set(clave, String(datos[clave]));
      });
      url += '?' + parametros.toString();
    } else {
      opciones.headers['Content-Type'] = 'application/json; charset=utf-8';
      opciones.headers['X-CSRF-Token'] = token;
      opciones.body = JSON.stringify(Object.assign({accion: accion}, datos || {}));
    }

    var respuesta;
    try {
      respuesta = await window.fetch(url, opciones);
    } catch (errorRed) {
      throw new ErrorApiFeriados('Error de red.', 0, 'NETWORK_ERROR');
    }

    var texto = await respuesta.text();
    var cuerpo;
    try {
      cuerpo = JSON.parse(texto);
    } catch (errorJson) {
      throw new ErrorApiFeriados('JSON inválido.', respuesta.status, 'INVALID_JSON');
    }

    if (!respuesta.ok || !cuerpo || cuerpo.ok !== true) {
      var errorServidor = cuerpo && cuerpo.error && typeof cuerpo.error === 'object'
        ? cuerpo.error
        : {};
      throw new ErrorApiFeriados(
        'Solicitud rechazada.',
        respuesta.status,
        typeof errorServidor.code === 'string' ? errorServidor.code : 'UNEXPECTED_RESPONSE'
      );
    }

    return cuerpo;
  }

  async function obtenerCsrf(forzar) {
    if (csrfToken && !forzar) {
      return csrfToken;
    }
    var respuesta = await solicitar('obtener_csrf', 'GET', {}, null);
    var token = respuesta && respuesta.data && respuesta.data.csrf_token;
    if (typeof token !== 'string' || !/^[a-f0-9]{64}$/.test(token)) {
      throw new ErrorApiFeriados('Token inválido.', 0, 'UNEXPECTED_RESPONSE');
    }
    csrfToken = token;
    return csrfToken;
  }

  async function escribir(accion, datos, permiteReintentoCsrf) {
    var token = await obtenerCsrf(false);
    try {
      return await solicitar(accion, 'POST', datos, token);
    } catch (error) {
      if (permiteReintentoCsrf
        && error.status === 403
        && error.code === 'CSRF_VALIDATION_FAILED') {
        csrfToken = null;
        await obtenerCsrf(true);
        return escribir(accion, datos, false);
      }
      throw error;
    }
  }

  function renderEstado(estado, tipo) {
    var texto = estado === 'enabled' ? 'Habilitado' : 'Deshabilitado';
    if (tipo !== 'display') {
      return texto;
    }
    var clase = estado === 'enabled' ? 'badge-success' : 'badge-secondary';
    var icono = estado === 'enabled' ? 'fa-check-circle' : 'fa-ban';
    return '<span class="badge ' + clase + '"><i class="fas ' + icono
      + ' mr-1" aria-hidden="true"></i>' + texto + '</span>';
  }

  function renderAcciones(feriado) {
    var id = feriado.id_feriado;
    var botones = '<div class="feriados-acciones">'
      + '<button type="button" class="btn btn-link p-0 feriados-accion feriados-accion-editar" data-id="' + id
      + '" data-toggle="tooltip" data-placement="top" title="Editar feriado" aria-label="Editar feriado del '
      + escaparHtml(formatearFecha(feriado.fecha)) + '">'
      + '<i class="v-icon-accion p-1 fas fa-edit" aria-hidden="true"></i><span class="sr-only">Editar</span></button>';

    if (!esHistorico(feriado)) {
      if (feriado.estado === 'enabled') {
        botones += '<button type="button" class="btn btn-link p-0 text-danger feriados-accion feriados-accion-estado feriados-accion-desactivar" data-id="' + id
          + '" data-accion="desactivar" data-toggle="tooltip" data-placement="top" title="Desactivar feriado" aria-label="Desactivar feriado del '
          + escaparHtml(formatearFecha(feriado.fecha)) + '"><i class="v-icon-accion p-1 fas fa-toggle-off" aria-hidden="true"></i>'
          + '<span class="sr-only">Desactivar</span></button>';
      } else {
        botones += '<button type="button" class="btn btn-link p-0 text-success feriados-accion feriados-accion-estado feriados-accion-reactivar" data-id="' + id
          + '" data-accion="reactivar" data-toggle="tooltip" data-placement="top" title="Reactivar feriado" aria-label="Reactivar feriado del '
          + escaparHtml(formatearFecha(feriado.fecha)) + '"><i class="v-icon-accion p-1 fas fa-toggle-on" aria-hidden="true"></i>'
          + '<span class="sr-only">Reactivar</span></button>';
      }
    }

    return botones + '</div>';
  }

  function inicializarTabla() {
    tabla = $('#feriadosTabla').DataTable({
      data: [],
      responsive: true,
      autoWidth: false,
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      order: [[0, 'desc']],
      dom: '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
      buttons: [
        {extend: 'copy', text: 'Copiar', exportOptions: {columns: [0, 1, 2]}},
        {extend: 'excel', text: 'Excel', exportOptions: {columns: [0, 1, 2]}},
        {extend: 'pdf', text: 'PDF', exportOptions: {columns: [0, 1, 2]}},
        {extend: 'print', text: 'Imprimir', exportOptions: {columns: [0, 1, 2]}},
        {extend: 'colvis', text: 'Columnas'}
      ],
      language: {
        url: '../05-plugins/datatables/es-ES.json',
        emptyTable: 'No hay feriados registrados.'
      },
      columns: [
        {
          data: 'fecha',
          className: 'feriados-fecha',
          responsivePriority: 1,
          render: function (dato, tipo) {
            return tipo === 'display' ? escaparHtml(formatearFecha(dato)) : dato;
          }
        },
        {
          data: 'descripcion',
          responsivePriority: 2,
          render: function (dato, tipo) {
            return tipo === 'display' ? escaparHtml(dato) : dato;
          }
        },
        {
          data: 'estado',
          className: 'text-center',
          responsivePriority: 3,
          render: renderEstado
        },
        {
          data: null,
          orderable: false,
          searchable: false,
          className: 'text-center',
          responsivePriority: 1,
          render: function (dato, tipo, fila) {
            return tipo === 'display' ? renderAcciones(fila) : '';
          }
        }
      ],
      createdRow: function (fila, datos) {
        if (datos.estado === 'disabled') {
          fila.classList.add('feriado-deshabilitado');
        }
      },
      preDrawCallback: function () {
        $('#feriadosTabla [data-toggle="tooltip"]').tooltip('dispose');
      },
      drawCallback: function () {
        $('#feriadosTabla [data-toggle="tooltip"]').tooltip({
          container: 'body',
          trigger: 'hover focus'
        });
      }
    });
  }

  function mostrarEstadoCarga(mostrar) {
    $('#feriadosEstadoCarga').toggleClass('d-none', !mostrar);
  }

  function mostrarErrorCarga(error) {
    $('#feriadosErrorCargaTexto').text(mensajeSeguro(error));
    $('#feriadosErrorCarga').removeClass('d-none');
  }

  async function cargarListado() {
    mostrarEstadoCarga(true);
    $('#feriadosErrorCarga').addClass('d-none');
    try {
      var respuesta = await solicitar('listar', 'GET', {}, null);
      var datos = respuesta && respuesta.data && respuesta.data.feriados;
      if (!Array.isArray(datos)) {
        throw new ErrorApiFeriados('Listado inválido.', 0, 'UNEXPECTED_RESPONSE');
      }
      var feriados = datos.map(normalizarFeriado);
      tabla.clear().rows.add(feriados).draw(false);
    } catch (error) {
      tabla.clear().draw();
      mostrarErrorCarga(error);
      if (error.code === 'UNAUTHENTICATED' || error.code === 'FORBIDDEN') {
        await mostrarError(error);
      }
    } finally {
      mostrarEstadoCarga(false);
    }
  }

  function limpiarErroresFormulario() {
    $('#feriadosFormulario').removeClass('was-validated');
    $('#feriadosFecha, #feriadosDescripcion').removeClass('is-invalid');
    $('#feriadosFechaError, #feriadosDescripcionError').text('');
  }

  function actualizarContadorDescripcion() {
    var longitud = Array.from($('#feriadosDescripcion').val() || '').length;
    $('#feriadosDescripcionContador').text(longitud + '/255');
  }

  function prepararModalCrear() {
    feriadoEdicion = null;
    limpiarErroresFormulario();
    $('#feriadosFormulario')[0].reset();
    $('#feriadosModalTitulo').text('Nuevo feriado');
    $('#feriadosGuardarTexto').text('Crear feriado');
    $('#feriadosFecha').prop('readonly', false).attr('min', manana);
    $('#feriadosFechaAyuda').text('Debe ser estrictamente posterior a hoy.');
    $('#feriadosFormularioContexto').addClass('d-none').text('');
    actualizarContadorDescripcion();
    $('#feriadosModal').modal('show');
  }

  async function obtenerFeriado(id) {
    var respuesta = await solicitar('obtener', 'GET', {id_feriado: id}, null);
    return normalizarFeriado(respuesta && respuesta.data && respuesta.data.feriado);
  }

  function prepararModalEditar(feriado) {
    feriadoEdicion = feriado;
    limpiarErroresFormulario();
    $('#feriadosModalTitulo').text('Editar feriado');
    $('#feriadosGuardarTexto').text('Guardar cambios');
    $('#feriadosFecha').val(feriado.fecha);
    $('#feriadosDescripcion').val(feriado.descripcion);

    if (esHistorico(feriado)) {
      $('#feriadosFecha').prop('readonly', true).removeAttr('min');
      $('#feriadosFechaAyuda').text('La fecha es histórica y no puede modificarse.');
      $('#feriadosFormularioContexto')
        .removeClass('d-none')
        .text('Para un feriado histórico solamente se puede modificar la descripción.');
    } else {
      $('#feriadosFecha').prop('readonly', false).attr('min', manana);
      $('#feriadosFechaAyuda').text('La nueva fecha debe continuar siendo posterior a hoy.');
      $('#feriadosFormularioContexto').addClass('d-none').text('');
    }
    actualizarContadorDescripcion();
    $('#feriadosModal').modal('show');
  }

  function asignarErrorCampo(selector, selectorError, mensaje) {
    $(selector).addClass('is-invalid');
    $(selectorError).text(mensaje);
  }

  function validarFormulario() {
    limpiarErroresFormulario();
    var fecha = $('#feriadosFecha').val();
    var descripcion = ($('#feriadosDescripcion').val() || '').trim();
    var valido = true;

    if (!fechaValida(fecha)) {
      asignarErrorCampo('#feriadosFecha', '#feriadosFechaError', 'Ingresá una fecha válida.');
      valido = false;
    } else if (!feriadoEdicion && fecha <= hoy) {
      asignarErrorCampo('#feriadosFecha', '#feriadosFechaError', 'La fecha debe ser posterior a hoy.');
      valido = false;
    } else if (feriadoEdicion && esHistorico(feriadoEdicion) && fecha !== feriadoEdicion.fecha) {
      asignarErrorCampo('#feriadosFecha', '#feriadosFechaError', 'La fecha histórica no puede modificarse.');
      valido = false;
    } else if (feriadoEdicion && !esHistorico(feriadoEdicion) && fecha <= hoy) {
      asignarErrorCampo('#feriadosFecha', '#feriadosFechaError', 'La fecha debe continuar siendo posterior a hoy.');
      valido = false;
    }

    var longitudDescripcion = Array.from(descripcion).length;
    if (!descripcion) {
      asignarErrorCampo('#feriadosDescripcion', '#feriadosDescripcionError', 'Ingresá una descripción.');
      valido = false;
    } else if (longitudDescripcion > 255) {
      asignarErrorCampo('#feriadosDescripcion', '#feriadosDescripcionError', 'La descripción no puede superar los 255 caracteres.');
      valido = false;
    }

    $('#feriadosFormulario').addClass('was-validated');
    if (!valido) {
      $('#feriadosFormulario .is-invalid').first().trigger('focus');
      return null;
    }
    return {fecha: fecha, descripcion: descripcion};
  }

  function bloquearFormulario(bloquear) {
    escrituraEnCurso = bloquear;
    $('#feriadosGuardar, #feriadosCancelar, #feriadosModal .close').prop('disabled', bloquear);
    $('#feriadosFecha, #feriadosDescripcion').prop('disabled', bloquear);
    $('#feriadosGuardarSpinner').toggleClass('d-none', !bloquear);
    $('#feriadosGuardarIcono').toggleClass('d-none', bloquear);
  }

  function mostrarExito(mensaje) {
    return Swal.fire({
      icon: 'success',
      title: 'Operación completada',
      text: mensaje,
      confirmButtonText: 'Aceptar',
      confirmButtonColor: '#28a745'
    });
  }

  async function mostrarError(error) {
    await Swal.fire({
      icon: 'error',
      title: 'No se pudo completar la operación',
      text: mensajeSeguro(error),
      confirmButtonText: 'Aceptar',
      confirmButtonColor: '#6c757d'
    });

    if (error && error.code === 'UNAUTHENTICATED') {
      window.location.assign('../01-views/login.php');
    } else if (error && error.code === 'FORBIDDEN') {
      window.location.assign('../01-views/panel.php');
    }
  }

  async function guardarFeriado(evento) {
    evento.preventDefault();
    if (escrituraEnCurso) {
      return;
    }
    var datos = validarFormulario();
    if (!datos) {
      return;
    }

    var accion = feriadoEdicion ? 'actualizar' : 'crear';
    if (feriadoEdicion) {
      datos.id_feriado = feriadoEdicion.id_feriado;
    }

    bloquearFormulario(true);
    try {
      var respuesta = await escribir(accion, datos, true);
      $('#feriadosModal').modal('hide');
      await mostrarExito(respuesta.message || 'La operación se completó correctamente.');
      await cargarListado();
    } catch (error) {
      await mostrarError(error);
    } finally {
      bloquearFormulario(false);
    }
  }

  async function editarDesdeBoton(boton) {
    var id = Number(boton.getAttribute('data-id'));
    if (!Number.isInteger(id) || id <= 0 || lecturaEnCurso) {
      return;
    }
    lecturaEnCurso = true;
    boton.disabled = true;
    try {
      disparadorModal = boton;
      prepararModalEditar(await obtenerFeriado(id));
    } catch (error) {
      await mostrarError(error);
    } finally {
      lecturaEnCurso = false;
      boton.disabled = false;
    }
  }

  function buscarFila(id) {
    var encontrada = null;
    tabla.rows().every(function () {
      var fila = this.data();
      if (fila && fila.id_feriado === id) {
        encontrada = fila;
      }
    });
    return encontrada;
  }

  async function cambiarEstadoDesdeBoton(boton) {
    var id = Number(boton.getAttribute('data-id'));
    var accion = boton.getAttribute('data-accion');
    var feriado = buscarFila(id);
    if (!feriado || esHistorico(feriado)
      || (accion !== 'desactivar' && accion !== 'reactivar')
      || escrituraEnCurso) {
      return;
    }

    var esDesactivacion = accion === 'desactivar';
    var confirmacion = await Swal.fire({
      icon: esDesactivacion ? 'warning' : 'question',
      title: esDesactivacion ? 'Desactivar feriado' : 'Reactivar feriado',
      text: (esDesactivacion ? 'Se desactivará ' : 'Se reactivará ')
        + formatearFecha(feriado.fecha) + ' — ' + feriado.descripcion + '.',
      showCancelButton: true,
      confirmButtonText: esDesactivacion ? 'Sí, desactivar' : 'Sí, reactivar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: esDesactivacion ? '#ffc107' : '#28a745',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    });
    if (!confirmacion.isConfirmed) {
      return;
    }

    escrituraEnCurso = true;
    boton.disabled = true;
    try {
      var respuesta = await escribir(accion, {id_feriado: id}, true);
      await mostrarExito(respuesta.message || 'El estado se actualizó correctamente.');
      await cargarListado();
    } catch (error) {
      await mostrarError(error);
    } finally {
      escrituraEnCurso = false;
      boton.disabled = false;
    }
  }

  $(function () {
    inicializarTabla();
    cargarListado();

    $('#feriadosCrear').on('click', function () {
      disparadorModal = this;
      prepararModalCrear();
    });
    $('#feriadosReintentarCarga').on('click', cargarListado);
    $('#feriadosFormulario').on('submit', guardarFeriado);
    $('#feriadosDescripcion').on('input', actualizarContadorDescripcion);
    $('#feriadosFiltroEstado').on('change', function () {
      var valor = $(this).val();
      tabla.column(2).search(valor ? '^' + valor + '$' : '', true, false).draw();
    });

    $('#feriadosTabla').on('click', '.feriados-accion-editar', function () {
      $(this).tooltip('hide');
      editarDesdeBoton(this);
    });
    $('#feriadosTabla').on('click', '.feriados-accion-estado', function () {
      $(this).tooltip('hide');
      cambiarEstadoDesdeBoton(this);
    });

    $('#feriadosModal').on('shown.bs.modal', function () {
      if (feriadoEdicion && esHistorico(feriadoEdicion)) {
        $('#feriadosDescripcion').trigger('focus');
      } else {
        $('#feriadosFecha').trigger('focus');
      }
    });
    $('#feriadosModal').on('hidden.bs.modal', function () {
      if (!escrituraEnCurso) {
        feriadoEdicion = null;
        limpiarErroresFormulario();
        $('#feriadosFormulario')[0].reset();
        actualizarContadorDescripcion();
      }
      if (disparadorModal && document.documentElement.contains(disparadorModal)) {
        disparadorModal.focus();
      }
      disparadorModal = null;
    });

  });
})(jQuery, window, document);
