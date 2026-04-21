function previsitaGuardar(options) {
    var config = options && typeof options === 'object' ? options : {};
    var soloDocumentos = config.soloDocumentos === true;

    if (!soloDocumentos && typeof window.obtenerBloqueoEdicionComercialSeguimiento === 'function') {
        var bloqueoEdicion = window.obtenerBloqueoEdicionComercialSeguimiento();
        if (bloqueoEdicion && bloqueoEdicion.bloqueado) {
            Swal.fire({
                icon: 'warning',
                title: 'Edicion bloqueada',
                text: typeof window.mensajeBloqueoEdicionComercialSeguimiento === 'function'
                    ? window.mensajeBloqueoEdicionComercialSeguimiento()
                    : 'La edicion del seguimiento esta bloqueada por el estado comercial actual.',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
            });
            return;
        }
    }

    var esEdicion = $(".v-id").val() !== "";
    var accionPost = esEdicion ? 'edicion' : 'alta';
    var leyenda = esEdicion
        ? 'Quieres continuar editando esta previsita?'
        : 'Quieres ingresar otra previsita?';

    if (soloDocumentos) {
        if (!esEdicion) {
            Swal.fire({
                icon: 'warning',
                title: 'Pre-visita invalida',
                text: 'Primero debe guardar la pre-visita antes de administrar documentos.',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
            });
            return;
        }

        if (
            window.previsitaDocumentosManager &&
            typeof window.previsitaDocumentosManager.hasPendingChanges === 'function' &&
            !window.previsitaDocumentosManager.hasPendingChanges()
        ) {
            Swal.fire({
                icon: 'info',
                title: 'Sin cambios',
                text: 'No hay cambios pendientes en los documentos.',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
            });
            return;
        }
    }

    var formData = new FormData($('#currentForm')[0]);
    formData.append('ajax', 'on');
    formData.append('accion', accionPost);
    if (soloDocumentos) {
        formData.append('solo_documentos', '1');
    }

    if (window.previsitaDocumentosManager && typeof window.previsitaDocumentosManager.appendToFormData === 'function') {
        window.previsitaDocumentosManager.appendToFormData(formData);
    }

    $.ajax({
        url: '../04-modelo/presupuestosGuardarModel.php',
        type: 'POST',
        dataType: 'json',
        data: formData,
        processData: false,
        contentType: false,
        success: function (data) {
            if (!data || data.resultado !== true) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No se pudo guardar',
                    text: (data && data.msg) ? data.msg : 'No se pudo guardar la pre-visita.',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });
                return;
            }

            if (window.previsitaDocumentosManager && typeof window.previsitaDocumentosManager.setPersistedDocuments === 'function') {
                window.previsitaDocumentosManager.setPersistedDocuments(data.documentos_previsita || []);
            }

            if (soloDocumentos) {
                Swal.fire({
                    icon: 'success',
                    title: 'Documentos actualizados',
                    text: 'Los documentos de la pre-visita se actualizaron correctamente.',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });
                return;
            }

            Swal.fire({
                icon: 'success',
                title: 'Los datos se han registrado correctamente',
                html: leyenda,
                showDenyButton: true,
                confirmButtonText: 'Si',
                denyButtonText: 'No',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(function (result) {
                if (result.isConfirmed) {
                    if (!esEdicion) {
                        $("#currentForm")[0].reset();
                        $(".v-select2").val('').trigger('change');
                        if (window.previsitaDocumentosManager && typeof window.previsitaDocumentosManager.clearAll === 'function') {
                            window.previsitaDocumentosManager.clearAll();
                        }
                        abm_detect();
                    }
                } else if (result.isDenied) {
                    window.location.href = 'seguimiento_de_obra_listado.php';
                }
            });
        },
        error: function () {
            Swal.fire({
                icon: 'error',
                title: 'Algo ha salido mal',
                text: 'Intentalo mas tarde o comunicate con el administrador',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.location.href = 'seguimiento_de_obra_listado.php';
                }
            });
        }
    });
}

$(document).on('click', '.btn-guardar-documentos-previsita', function (event) {
    event.preventDefault();
    previsitaGuardar({ soloDocumentos: true });
});
