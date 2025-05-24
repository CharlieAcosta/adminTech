function usuariosGuardar() {
    var accion = "";
    var leyenda = "";
    if ($(".v-id").val() != "") {
        accion = "editar"; // SOLO la palabra "editar"
        leyenda = "¿Quieres continuar editando este usuario?";
    } else {
        accion = "alta"; // SOLO la palabra "alta"
        leyenda = "¿Quieres ingresar otra alta?";
    }

    var formData = new FormData($('#currentForm')[0]);
    formData.append('ajax', 'on');
    formData.append('accion', accion);

    // Opcional: para logs, agregá otro campo
    if ($(".v-id").val() != "") {
        formData.append('log_accion', 'editar');
    } else {
        formData.append('log_accion', 'alta');
    }

    // Obtener los archivos seleccionados de cada campo de tipo file
    $('input[type="file"]').each(function() {
        var files = $(this)[0].files;
        for (var i = 0; i < files.length; i++) {
            formData.append($(this).attr('name'), files[i]);
        }
    });

    $.ajax({
        url: '../04-modelo/usuariosGuardarModel.php',
        type: 'POST',
        dataType: 'json',
        data: formData,
        processData: false,
        contentType: false,
        success: function (data) {
            Swal.fire({
                icon: 'success',
                title: 'Los datos se han registrado correctamente',
                text: leyenda,
                showDenyButton: true,
                confirmButtonText: 'Si',
                denyButtonText: 'No',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    if(accion=="alta"){ // <--- CAMBIADO!
                        $("#currentForm")[0].reset();
                        $(".v-select2").val('').trigger('change');
                        abm_detect();
                    }
                } else if (result.isDenied) {
                    window.location.href='listado_personal.php';
                }
            });
        },
        error: function (data) {
            Swal.fire({
                icon: 'error',
                title: 'Algó ha salido mal',
                text: "Intentalo más tarde o comunicate con el administrador",
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
               }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href='listado_personal.php';
                }
            });
        }
    });
}







