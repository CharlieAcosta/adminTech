// presupuestosAcciones.js
$(document).on("click",".v-icon-accion, .v-accion-cancelar, .v-accion-eliminar, .v-accion-ver-todos",function(){
    presupuestoAcciones($(this));
});

function escapeHtmlDocumentoEmitido(valor) {
    return String(valor ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function resolverUrlDocumentoEmitido(item) {
    const ruta = String(item && item.ruta_archivo ? item.ruta_archivo : '').replace(/^\/+/, '');
    if (ruta) {
        return '../' + ruta;
    }

    return String(item && item.url_publica ? item.url_publica : '');
}

function descargarArchivoDocumentoEmitido(url, nombreArchivo) {
    const link = document.createElement('a');
    link.href = url;
    if (nombreArchivo) {
        link.download = nombreArchivo;
    }
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function inicializarTooltipsDocumentosEmitidos() {
    const $modal = $('#modalDocumentosEmitidosPresupuesto');
    const $items = $modal.find('[data-toggle="tooltip"]');

    if (!$items.length) {
        return;
    }

    $items.tooltip('dispose');
    $items.tooltip({
        container: 'body',
        trigger: 'hover',
        boundary: 'window',
        animation: false,
        template: '<div class="tooltip" role="tooltip" style="pointer-events:none;"><div class="arrow"></div><div class="tooltip-inner"></div></div>'
    });
}

function construirHtmlDocumentosEmitidos(items) {
    if (!Array.isArray(items) || !items.length) {
        return `
            <div class="alert alert-light border mb-0">
                <div class="font-weight-bold mb-1">Todavía no hay documentos emitidos</div>
                <div class="text-muted">Cuando generes un documento desde el presupuesto, va a aparecer listado acá.</div>
            </div>
        `;
    }

    const filas = items.map((item) => {
        const documentoNumero = item.numero_documento || item.nombre_base || item.nombre_archivo || ('Documento #' + (item.id_documento_emitido || ''));
        const nombreArchivo = item.nombre_archivo || '';
        const url = resolverUrlDocumentoEmitido(item);
        const archivoDisponible = item.archivo_disponible !== false;
        const claseFila = archivoDisponible ? '' : 'table-warning';
        const leyendaDisponibilidad = archivoDisponible
            ? ''
            : '<div class="text-warning small font-weight-bold">Archivo no disponible en el servidor</div>';
        const estiloAccion = archivoDisponible ? '' : 'style="pointer-events:none;opacity:.35;"';
        const tooltipVer = archivoDisponible ? 'Ver documento' : 'Archivo no disponible';
        const tooltipDescargar = archivoDisponible ? 'Descargar documento' : 'Archivo no disponible';

        return `
            <tr class="${claseFila}">
                <td class="text-center align-middle text-danger">
                    <i class="fas fa-file-pdf fa-lg"></i>
                </td>
                <td class="align-middle">
                    <div class="font-weight-bold">${escapeHtmlDocumentoEmitido(documentoNumero)}</div>
                    <div class="text-muted small">${escapeHtmlDocumentoEmitido(nombreArchivo)}</div>
                    ${leyendaDisponibilidad}
                </td>
                <td class="align-middle">${escapeHtmlDocumentoEmitido(item.fecha_texto || '-')}</td>
                <td class="align-middle">${escapeHtmlDocumentoEmitido(item.usuario_nombre || '-')}</td>
                <td class="text-center align-middle">
                    <i class="v-icon-accion p-1 fas fa-eye"
                        data-accion="ver_documento_emitido"
                        data-url="${escapeHtmlDocumentoEmitido(url)}"
                        data-toggle="tooltip"
                        title="${tooltipVer}" ${estiloAccion}></i>
                    <i class="v-icon-accion p-1 fas fa-download"
                        data-accion="descargar_documento_emitido"
                        data-url="${escapeHtmlDocumentoEmitido(url)}"
                        data-nombre="${escapeHtmlDocumentoEmitido(nombreArchivo)}"
                        data-toggle="tooltip"
                        title="${tooltipDescargar}" ${estiloAccion}></i>
                </td>
            </tr>
        `;
    }).join('');

    return `
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 6%">PDF</th>
                        <th>Documento</th>
                        <th style="width: 18%">Fecha emisión</th>
                        <th style="width: 22%">Usuario</th>
                        <th style="width: 12%">Acciones</th>
                    </tr>
                </thead>
                <tbody>${filas}</tbody>
            </table>
        </div>
    `;
}

function cargarDocumentosEmitidosPresupuesto(idPrevisita) {
    $.ajax({
        url: '../03-controller/presupuestos_guardar.php',
        type: 'POST',
        dataType: 'json',
        data: {
            via: 'ajax',
            funcion: 'listarDocumentosEmitidosPresupuesto',
            id_previsita: idPrevisita
        },
        success: function (response) {
            if (!response || response.ok !== true) {
                const mensaje = response && response.msg
                    ? response.msg
                    : 'No se pudieron cargar los documentos emitidos.';

                $('#modalDocumentosEmitidosPresupuestoBody').html(
                    `<div class="alert alert-danger mb-0">${escapeHtmlDocumentoEmitido(mensaje)}</div>`
                );
                return;
            }

            $('#modalDocumentosEmitidosPresupuestoBody').html(
                construirHtmlDocumentosEmitidos(response.items || [])
            );

            inicializarTooltipsDocumentosEmitidos();
        },
        error: function () {
            $('#modalDocumentosEmitidosPresupuestoBody').html(
                '<div class="alert alert-danger mb-0">Ocurrió un error al consultar los documentos emitidos.</div>'
            );
        }
    });
}

function presupuestoAcciones(elemento){
      switch($(elemento).data('accion')) {

            case 'visual': // ver el presupuesto por pantalla
                var id = $(elemento).closest('tr').data('id');
                window.location.href='seguimiento_form.php?acci=v&id='+id;
            break;

            case 'editar': // editar el presupuesto
                var id = $(elemento).closest('tr').data('id');
                window.location.href='seguimiento_form.php?acci=e&id='+id;
            break;

            case 'documentos_emitidos':
                var fila = $(elemento).closest('tr');
                var idPrevisita = Number(fila.data('id')) || 0;
                var razonSocial = fila.find('td').eq(3).text().trim();

                $('#modalDocumentosEmitidosContexto').html(
                    'ID: ' + idPrevisita + ' | ' + escapeHtmlDocumentoEmitido(razonSocial)
                );

                $('#modalDocumentosEmitidosPresupuesto')
                    .data('id', idPrevisita)
                    .modal('show');

                $('#modalDocumentosEmitidosPresupuestoBody').html(
                    '<div class="text-muted">Cargando documentos...</div>'
                );

                cargarDocumentosEmitidosPresupuesto(idPrevisita);
            break;

            case 'ver_documento_emitido':
                var urlDocumento = $(elemento).data('url') || '';

                if (!urlDocumento) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Documento no disponible',
                        text: 'No se encontró la ruta del documento seleccionado.',
                        confirmButtonText: 'OK'
                    });
                    break;
                }

                window.open(urlDocumento, '_blank', 'noopener');
            break;

            case 'descargar_documento_emitido':
                var urlDescarga = $(elemento).data('url') || '';
                var nombreDescarga = $(elemento).data('nombre') || 'documento.pdf';

                if (!urlDescarga) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Documento no disponible',
                        text: 'No se encontró la ruta del documento seleccionado.',
                        confirmButtonText: 'OK'
                    });
                    break;
                }

                descargarArchivoDocumentoEmitido(urlDescarga, nombreDescarga);
            break;

            case 'pdf': // generar pdf del usuario
                var id = $(elemento).closest('tr').data('id');

                    $.ajax({
                        url: '../04-modelo/presupuestosModel.php',
                        type: 'POST',
                        dataType: 'json',
                        //data: $('#inversion').serialize(), [example]
                        data: {
                        'id'      : id,
                        'via'     : 'ajax',   
                        'funcion' : 'modGetClientesById',      
                        },
                        success: function (data) {
                        //console.log('success: '+(data));
                            agentePdf = Object.values(data);                               

                    var dd = {
                        content: [
                            {
                                style: 'tableExample',
                                table: {
                                    widths: ['85%', '15%'],
                                    headerRows: 2,
                                    body: [
                                        [{border: [true, true, false, false], text: 'Razón Social'}, {border: [false, true, true, false], text: 'Nro. cliente', alignment: 'center'}],
                                        [{border: [true, false, false, true], text: agentePdf['0']['razon_social'], style: 'header'}, {border: [false, false, true, true], text: agentePdf['0']['id_cliente'], style: 'header', alignment: 'center'}],
                                    ]
                                }
                            },

                            {
                                style: 'tableExample',
                                table: {
                                    widths: ['35%', '65%'],
                                    headerRows: 1,
                                    body: [
                                        [{text: 'Datos de la empresa', style: 'tableHeader'}, {}],
                                        ['CUIT:', agentePdf['0']['cuit']],
                                        ['Email:', agentePdf['0']['email']],
                                        ['Estado:', agentePdf['0']['estado']],
                                    ]
                                },
                                layout: 'lightHorizontalLines'
                            },
                            {
                                style: 'tableExample',
                                table: {
                                    widths: ['35%', '65%'],
                                    headerRows: 1,
                                    body: [
                                        [{text: 'Domicilio fiscal', style: 'tableHeader'}, {}],
                                        ['Provincia:', agentePdf['0']['provincianom']],
                                        ['Partido:', agentePdf['0']['partidonom']],
                                        ['Localidad:', agentePdf['0']['localidadnom']],
                                        ['Calle:', agentePdf['0']['callenom']],
                                        ['Altura:', agentePdf['0']['dirfis_altura']],
                                        ['Piso:', agentePdf['0']['dirfis_piso']],
                                        ['Depto:', agentePdf['0']['dirfis_depto']],
                                        ['CP:', agentePdf['0']['dirfis_cp']],
                                        ['Teléfono:', agentePdf['0']['telefono']],
                                    ]
                                },
                                layout: 'lightHorizontalLines'
                            },
                            {
                                style: 'tableExample',
                                table: {
                                    widths: ['35%', '65%'],
                                    headerRows: 1,
                                    body: [
                                        [{text: 'Contacto principal', style: 'tableHeader'}, {}],
                                        ['Apellido y Nombre(s):', agentePdf['0']['contacto_pri']],
                                        ['Celular:', agentePdf['0']['contacto_pri_celular']],
                                        ['Email:', agentePdf['0']['contacto_pri_email']],
                                    ]
                                },
                                layout: 'lightHorizontalLines'
                            },
                            {
                                style: 'tableExample',
                                table: {
                                    widths: ['35%', '65%'],
                                    headerRows: 1,
                                    body: [
                                        [{text: 'Contacto pago a proveedores', style: 'tableHeader'}, {}],
                                        ['Apellido y Nombre(s):', agentePdf['0']['contacto_papro']],
                                        ['Celular:', agentePdf['0']['contacto_papro_celular']],
                                        ['Email:', agentePdf['0']['contacto_papro_email']],
                                    ]
                                },
                                layout: 'lightHorizontalLines'
                            },        
                            {
                                style: 'tableExample',
                                table: {
                                    widths: ['50%', '50%'],
                                    headerRows: 1,
                                    body: [
                                        [{text: 'Plataformas', style: 'tableHeader'}, {}],
                                        ['Plataforma de licitaciones:', agentePdf['0']['plat_licitacion']],
                                        ['Plataforma de pagos:', agentePdf['0']['plat_pagos']],
                                        ['Plataforma de documentación:', agentePdf['0']['plat_documentacion']]
                                    ]
                                },
                                layout: 'lightHorizontalLines'
                            }
                        ],
                        styles: {
                            header: {
                                fontSize: 18,
                                bold: true,
                                margin: [0, 0, 0, 10]
                            },
                            subheader: {
                                fontSize: 16,
                                bold: true,
                                margin: [0, 10, 0, 5]
                            },
                            tableExample: {
                                margin: [0, 5, 0, 15]
                            },
                            tableHeader: {
                                bold: true,
                                fontSize: 13,
                                color: 'black'
                            }
                        },
                        defaultStyle: {
                            // alignment: 'justify'
                        }
                                            
                                        }
                                        pdfMake.createPdf(dd).open();
                                    },
                                    error: function (data) {
                                        console.log('error: '+Object.values(data));
                                    }
                                });


            break;

            case 'cancelar':
                    Swal.fire({
                        icon: 'warning',
                        title: 'Los datos ingresados o modificados no seran registrados',
                        text: "¿Deseas cancelar de todas maneras?",
                        showDenyButton: true,
                        confirmButtonText: 'Si',
                        denyButtonText: 'No',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then((result) => {
                        /* Read more about isConfirmed, isDenied below */
                        if (result.isConfirmed) {
                            window.location.href='seguimiento_de_obra_listado.php';
                        } else if (result.isDenied) {

                        }
                    })  
            break;

            case 'delete':
                    var id = $(elemento).closest('tr').data('id');

                    mostrarConfirmacion(
                        'Estás a punto de eliminar un seguimiento completo, ¿confirmás?',
                        () => {
                            // 1) Primero: borrado lógico en DB
                            simpleUpdateInDB(
                                '../06-funciones_php/funciones.php',
                                'previsitas',
                                { estado_visita: 'Eliminada' },
                                [{ columna: 'id_previsita', condicion: '=', valorCompara: id }]
                            )
                            .then((ok) => {
                                // 2) Si OK: eliminar la fila del datatable y avisar éxito
                                if (ok === true) {
                                    dtableRowDelete("current_table", id);
                                    mostrarExito('Seguimiento eliminado correctamente.');
                                } else {
                                    // Si no vino true (vino false u otra cosa): avisar error
                                    mostrarError('No se pudo eliminar el seguimiento. No se aplicaron cambios.');
                                }
                            })
                            .catch((err) => {
                                // Error AJAX / parse / server
                                console.error(err);
                                mostrarError('Ocurrió un error al intentar eliminar el seguimiento.');
                            });
                        },
                        () => {
                            // Cancelado: no hacer nada
                        },
                        'AD',
                        'Confirmar',
                        'Cancelar'
                    );

            break;    

            break;

            case 'vertodos':
                $('.v-accion-ver-todos i').removeClass("fa-eye").addClass("fa-eye-slash");
                $('.v-accion-ver-todos').removeClass("btn-success").addClass("btn-warning");
                elemento.removeClass("btn-warning").addClass('btn-success').find('i').removeClass("fa-eye-slash").addClass('fa-eye');

                $.ajax({
                    url: '../03-controller/presupuestosController.php',
                    type: 'POST',
                    dataType: 'json',
                    //data: $('#currentForm').serialize()+"&ajax=on&accion=eliminar",
                    data: {
                            'ajax'    : 'on', 
                            'funcion' : 'poblarDatableAll',
                            'tds'     : new Array('id_cliente', 'log_alta','estado', 'razon_social', 'cuit', 'telefono', 'email', 'contacto_pri', 'contacto_pri_celular', 'contacto_pri_email'), 
                            'filtro'  : elemento.data('filtro')
                    },
                    success: function (data) {
                    //console.log('success: '+(data));
                    $('#current_table').DataTable().destroy();
                    $('#current_table tbody').html(data);
                        $(function () {
                            $("#current_table").DataTable({
                            "dom": '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
                            "responsive": true, "lengthChange": true, "autoWidth": false,
                            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
                            "language": {"url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"},
                            "columns": [{ "width": "1%" }, { "width": "6%" }, { "width": "5%" }, null, { "width": "8%" }, { "width": "10%" }, { "width": "10%" }, { "width": "10%" }, { "width": "11%" }, { "width": "10%" }, { "width": "6%" }]
                            }).buttons().container().appendTo('#current_table_wrapper .col-md-6:eq(0)');
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
                            /* Read more about isConfirmed, isDenied below */
                            if (result.isConfirmed) {
                                window.location.href='listado_personal.php';
                            }
                        })
                    }
                });   
            break;

            case 'passOnOff':
                $('.v-ver-pass').toggleClass("fa-eye-slash");            
                $('.v-ver-pass').toggleClass("fa-eye");  
                
                if($('.v-ver-pass').data('estado')!='on'){
                    $('.v-ver-pass').data('estado','on');
                    $("#password").attr('type','text');
                }else{
                    $('.v-ver-pass').data('estado','off');
                    $("#password").attr('type','password');
                }

            break;

            case 'guardar':
                alert("guardar");
            break;
    
            case 'historial':

                var fila = $(elemento).closest('tr');
                var id   = fila.data('id');

                // Obtener Razón Social (columna 3 del datatable)
                // Índices reales según tu tabla:
                // 0 ID
                // 1 Ingreso
                // 2 CUIT
                // 3 Razón Social  <-- esta
                var razonSocial = fila.find('td').eq(3).text().trim();

                // Inyectar contexto en el header
                $('#modalHistorialContexto').html(
                    'ID: ' + id + ' | ' + razonSocial
                );

                // Guardar id por si luego pedimos historial por AJAX
                $('#modalHistorialPresupuesto').data('id', id);

                // Placeholder cuerpo
                $('#modalHistorialPresupuestoBody').html(
                    '<div class="text-muted">Cargando historial...</div>'
                );

                // Mostrar modal
                $('#modalHistorialPresupuesto').modal('show');

            break;
    }
}

$(document).on('hidden.bs.modal', '#modalDocumentosEmitidosPresupuesto', function(){
    $(this).find('[data-toggle="tooltip"]').tooltip('dispose');
});
