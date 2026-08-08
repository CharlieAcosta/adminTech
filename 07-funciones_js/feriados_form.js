$(function () {
  $('.select2').select2({ language: "es" });
  $('.select2bs4').select2({ theme: 'bootstrap4', language: "es" });

  $('#currentForm').validate({
    rules: {
      fecha: { required: true, dateISO: true },
      descripcion: { required: true, maxlength: 255 }
    },
    messages: {
      fecha: {
        required: "Campo obligatorio",
        dateISO: "Ingrese una fecha valida"
      },
      descripcion: {
        required: "Campo obligatorio",
        maxlength: "La descripcion no puede superar 255 caracteres"
      }
    },
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group').append(error);
    },
    highlight: function (element) {
      $(element).addClass('is-invalid');
    },
    unhighlight: function (element) {
      $(element).removeClass('is-invalid');
    }
  });

  if ($('#feriado_log_usuario_id').data('visualiza') === 'on') {
    $('#currentForm input, #currentForm select, #currentForm textarea, #currentForm button[type="submit"], #currentForm button[data-accion="cancelar"]').prop('disabled', true);
  }
});
