// funcion ajax guardar los usuarios
function presupuestoGuardar() {
console.log('3 | presupuestoGuardar()');    
    if ($(".v-id").val() != "") {
        var accion = "edicion&log_accion=editar";
        var leyenda = "¿Quieres continuar editando esta previsita+++";
        var accionPost = 'edicion';
    } else {
        var accion = "alta&log_accion=alta";
        var accionPost = 'alta';
        var leyenda = "¿Quieres ingresar otra previsita?++++";
    }

    var formData = new FormData($('#currentForm')[0]);
    formData.append('ajax', 'on');
    formData.append('accion', accionPost);

    // Obtener los archivos seleccionados de cada campo de tipo file
    // $('input[type="file"]').each(function() {
    //     var files = $(this)[0].files;
    //     for (var i = 0; i < files.length; i++) {
    //         formData.append($(this).attr('name'), files[i]);
    //     }
    // });

    $.ajax({
        url: '../04-modelo/presupuestosGuardarModel.php',
        type: 'POST',
        dataType: 'json',
        data: formData,
        processData: false,
        contentType: false,
        success: function (data) {
            //console.log('success: '+(data));
            Swal.fire({
                icon: 'success',
                title: 'Los datos se han registrado correctamente',
                html: (data.error_file ? data.error_file + '<br>' : '') + leyenda,
                showDenyButton: true,
                confirmButtonText: 'Si',
                denyButtonText: 'No',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    if(accion=="alta&log_accion=alta"){
                        $("#currentForm")[0].reset();
                        $(".v-select2").val('').trigger('change');
                         abm_detect();
                    }
                } else if (result.isDenied) {
                    window.location.href='seguimiento_de_obra_listado.php';
                }
            })


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
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                     window.location.href='seguimiento_de_obra_listado.php';
                }
            })
        }
    });   
}






