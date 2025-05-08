  $(function () {
    $('.select2').select2({ language: "es" });
    $('.select2bs4').select2({ theme: 'bootstrap4', language: "es" });

    // VALIDA LOS CAMPOS DEL FORMULARIO
    $('#currentForm').validate({
      rules: {
        jornal_descripcion: { required: true },
        jornal_codigo: { required: true },
        jornal_valor: { required: true },
        jornal_estado: { required: true }
      },
      messages: {
        jornal_descripcion: { required: "Campo obligatorio" },
        jornal_codigo: { required: "Campo obligatorio" },
        jornal_valor: { required: "Campo obligatorio" },
        jornal_estado: { required: "Campo obligatorio" }
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
   
    // DESACTIVA TODOS LOS CAMPOS SI ESTAMOS EN MODO VISUALIZACIÓN
    if ($('#jornal_log_usuario_id').data('visualiza') === 'on') {
      $('#currentForm input, #currentForm select, #currentForm textarea, #currentForm button[type="submit"], #currentForm button[data-accion="cancelar"]').prop('disabled', true);
    }
  
});
