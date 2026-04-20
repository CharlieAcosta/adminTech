// presupuestosAcciones.js
$(document).on("click",".v-icon-accion, .v-accion-cancelar, .v-accion-eliminar, .v-accion-ver-todos, .v-accion-estado-comercial, .v-accion-contacto-comercial, .v-accion-orden-compra",function(){
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

function normalizarCuerpoMailDocumentoEmitido(valor) {
    return String(valor || '')
        .replace(/\r\n/g, '\n')
        .replace(/\n{2,}/g, '\n')
        .trim();
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
        const tooltipMail = archivoDisponible ? 'Enviar por mail' : 'Archivo no disponible';
        let envioHtml = '<div class="text-muted">No enviado</div>';

        if (String(item.envio_estado || '').toLowerCase() === 'enviado') {
            envioHtml = `
                <div class="text-primary font-weight-bold">${escapeHtmlDocumentoEmitido(item.envio_label || 'Enviado')}</div>
                <div class="text-muted small">${escapeHtmlDocumentoEmitido(item.ultimo_envio_texto || '')}</div>
            `;
        }

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
                <td class="align-middle">${envioHtml}</td>
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
                    <i class="v-icon-accion p-1 fas fa-envelope"
                        data-accion="mail_documento_emitido"
                        data-id-documento="${escapeHtmlDocumentoEmitido(item.id_documento_emitido || '')}"
                        data-toggle="tooltip"
                        title="${tooltipMail}" ${estiloAccion}></i>
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
                        <th style="width: 18%">Envío</th>
                        <th style="width: 14%">Acciones</th>
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

function renderAlertasModalEnviarDocumento(html, tipo = 'info') {
    const clase = {
        info: 'alert-info',
        danger: 'alert-danger',
        success: 'alert-success',
        warning: 'alert-warning'
    }[tipo] || 'alert-info';

    $('#modalEnviarDocumentoEmitidoAlertas').html(
        html ? `<div class="alert ${clase} mb-3">${html}</div>` : ''
    );
}

function mostrarSwalResultadoEnvioDocumento(response) {
    const estadoEnvio = String(response && response.estado_envio ? response.estado_envio : '').toLowerCase();
    const esSimulado = estadoEnvio === 'simulado';
    const mensaje = String(response && response.msg ? response.msg : 'Operación realizada correctamente.');

    if (typeof mostrarMensajeConfirmable === 'function') {
        mostrarMensajeConfirmable(
            escapeHtmlDocumentoEmitido(mensaje),
            esSimulado ? 'AD' : 'EX',
            esSimulado ? 'SIMULACION REGISTRADA' : 'DOCUMENTO ENVIADO',
            'OK'
        );
        return;
    }

    if (!window.Swal || typeof Swal.fire !== 'function') {
        if (estadoEnvio === 'simulado' && typeof mostrarAdvertencia === 'function') {
            mostrarAdvertencia(mensaje, 4);
            return;
        }
        if (typeof mostrarExito === 'function') {
            mostrarExito(mensaje, 4);
        }
        return;
    }

    Swal.fire({
        icon: esSimulado ? 'info' : 'success',
        title: esSimulado
            ? '<H2><STRONG style="color: #000000;">SIMULACIÓN REGISTRADA</STRONG></H2>'
            : '<H2><STRONG style="color: #ffffff;">DOCUMENTO ENVIADO</STRONG></H2>',
        html: `<H5 style="color: ${esSimulado ? '#000000' : '#ffffff'};">${escapeHtmlDocumentoEmitido(mensaje)}</H5>`,
        background: esSimulado ? '#ffc107' : '#28a745',
        iconColor: esSimulado ? '#000000' : '#ffffff',
        showConfirmButton: true,
        confirmButtonText: 'OK',
        confirmButtonColor: esSimulado ? '#d39e00' : '#1e7e34',
        allowOutsideClick: true,
        allowEscapeKey: true
    });
}

function construirHtmlCopiasMailDocumentoEmitido(copias) {
    if (!Array.isArray(copias) || !copias.length) {
        return '<div class="text-muted">No hay copias internas configuradas.</div>';
    }

    return copias.map((item) => `
        <div class="custom-control custom-checkbox mb-2">
            <input
                type="checkbox"
                class="custom-control-input mail-documento-copia"
                id="mail_copia_${escapeHtmlDocumentoEmitido(item.id_copia)}"
                value="${escapeHtmlDocumentoEmitido(item.email || '')}"
                data-tipo="${escapeHtmlDocumentoEmitido(item.tipo || 'cco')}"
                ${item.activo_por_defecto ? 'checked' : ''}
            >
            <label class="custom-control-label" for="mail_copia_${escapeHtmlDocumentoEmitido(item.id_copia)}">
                ${escapeHtmlDocumentoEmitido(item.etiqueta || 'Copia')} <span class="text-muted">(${escapeHtmlDocumentoEmitido((item.tipo || 'cco').toUpperCase())}: ${escapeHtmlDocumentoEmitido(item.email || '')})</span>
            </label>
        </div>
    `).join('');
}

function resetearModalEnviarDocumentoEmitido() {
    const form = $('#formEnviarDocumentoEmitidoPresupuesto')[0];
    if (form) {
        form.reset();
    }
    $('#mail_id_documento_emitido, #mail_id_presupuesto, #mail_id_previsita').val('');
    $('#modalEnviarDocumentoEmitidoContexto').html('');
    $('#mail_documento_sugerencias').html('<option value="">Seleccionar sugerencia</option>');
    $('#mail_documento_copias').html('<div class="text-muted">Cargando copias configuradas...</div>');
    $('#mail_documento_modo').html('');
    renderAlertasModalEnviarDocumento('');
    $('#btnEnviarDocumentoEmitido').prop('disabled', false);
}

function construirHtmlAccionesHistorialPresupuesto(acciones, idPrevisita, idPresupuesto) {
    if (!Array.isArray(acciones) || !acciones.length) {
        return '';
    }

    return acciones.map((accion, index) => `
        <span class="d-inline-flex align-items-center ${index > 0 ? 'ml-3' : ''}">
            ${index > 0 ? '<span class="text-muted mr-3">|</span>' : ''}
            <span
                class="v-accion-estado-comercial font-weight-bold ${escapeHtmlDocumentoEmitido(accion.text_class || 'text-dark')}"
                data-accion="${escapeHtmlDocumentoEmitido(accion.action_handler || 'estado_comercial_presupuesto')}"
                data-id="${escapeHtmlDocumentoEmitido(idPrevisita)}"
                data-id-presupuesto="${escapeHtmlDocumentoEmitido(idPresupuesto || '')}"
                data-estado-comercial="${escapeHtmlDocumentoEmitido(accion.accion || '')}"
                data-confirm-title="${escapeHtmlDocumentoEmitido(accion.confirm_title || 'Actualizar estado comercial')}"
                data-confirm-text="${escapeHtmlDocumentoEmitido(accion.confirm_text || 'Se va a registrar un nuevo evento comercial para este presupuesto.')}"
                style="cursor:pointer;"
            >
                <i class="v-icon-accion ${escapeHtmlDocumentoEmitido(accion.icon || 'fas fa-check')} mr-1"></i>${escapeHtmlDocumentoEmitido(accion.label || accion.accion || 'Accion')}
            </span>
        </span>
    `).join('');
}

function construirHtmlAccionesContactoHistorialPresupuesto(acciones, idPrevisita, idPresupuesto) {
    if (!Array.isArray(acciones) || !acciones.length) {
        return '';
    }

    return acciones.map((accion, index) => `
        <span class="d-inline-flex align-items-center ${index > 0 ? 'ml-3' : ''}">
            ${index > 0 ? '<span class="text-muted mr-3">|</span>' : ''}
            <span
                class="v-accion-contacto-comercial font-weight-bold text-dark"
                data-accion="contacto_comercial_presupuesto"
                data-id="${escapeHtmlDocumentoEmitido(idPrevisita)}"
                data-id-presupuesto="${escapeHtmlDocumentoEmitido(idPresupuesto || '')}"
                data-estado-comercial="${escapeHtmlDocumentoEmitido(accion.accion || '')}"
                data-confirm-title="${escapeHtmlDocumentoEmitido(accion.confirm_title || 'Registrar contacto comercial')}"
                data-confirm-text="${escapeHtmlDocumentoEmitido(accion.confirm_text || 'Se va a registrar un nuevo contacto comercial sobre este presupuesto.')}"
                style="cursor:pointer;"
            >
                <i class="v-icon-accion ${escapeHtmlDocumentoEmitido(accion.icon || 'fas fa-comment-alt')} mr-1"></i>${escapeHtmlDocumentoEmitido(accion.label || accion.accion || 'Accion')}
            </span>
        </span>
    `).join('');
}

function construirHtmlComentariosHistorialPresupuesto(comentarios) {
    const texto = String(comentarios || '').trim();

    if (!texto) {
        return '<span class="text-muted">-</span>';
    }

    return `<div class="text-break small" style="white-space: pre-wrap; min-width: 260px;">${escapeHtmlDocumentoEmitido(texto)}</div>`;
}

function habilitarAccionOrdenCompraHistorialPresupuesto(response) {
    const idPresupuesto = Number(response && response.id_presupuesto ? response.id_presupuesto : 0) || 0;
    const estadoActual = String(
        (response && (response.estado_comercial_activo || response.estado_actual || '')) || ''
    ).trim().toUpperCase();

    return idPresupuesto > 0 && estadoActual === 'APROBADO';
}

function construirHtmlAccionOrdenCompraHistorialPresupuesto(conSeparador = false) {
    return `
        <span class="d-inline-flex align-items-center ${conSeparador ? 'ml-3' : ''}">
            ${conSeparador ? '<span class="text-muted mr-3">|</span>' : ''}
            <span
                class="v-accion-orden-compra font-weight-bold text-success"
                data-accion="orden_compra_presupuesto"
                title="Orden de compra"
                style="cursor:pointer;"
            >
                <i class="fas fa-file-invoice mr-1"></i>OC
            </span>
        </span>
    `;
}

function construirHtmlHistorialPresupuesto(response) {
    const items = Array.isArray(response && response.items) ? response.items : [];
    const idPrevisita = Number(response && response.id_previsita ? response.id_previsita : 0) || '';
    const idPresupuesto = Number(response && response.id_presupuesto ? response.id_presupuesto : 0) || '';
    const accionesEstadoHtml = construirHtmlAccionesHistorialPresupuesto(
        response && response.acciones_disponibles ? response.acciones_disponibles : [],
        idPrevisita,
        idPresupuesto
    );
    const accionesContactoHtml = construirHtmlAccionesContactoHistorialPresupuesto(
        response && response.acciones_contacto_disponibles ? response.acciones_contacto_disponibles : [],
        idPrevisita,
        idPresupuesto
    );
    const accionOrdenCompraHtml = habilitarAccionOrdenCompraHistorialPresupuesto(response)
        ? construirHtmlAccionOrdenCompraHistorialPresupuesto(Boolean(accionesContactoHtml))
        : '';
    const bloqueAccionesHtml = (accionesEstadoHtml || accionesContactoHtml || accionOrdenCompraHtml) ? `
        <div class="mb-3 pb-2 border-bottom">
            ${accionesEstadoHtml ? `
                <div class="d-flex flex-wrap justify-content-center align-items-center text-center">
                    ${accionesEstadoHtml}
                </div>
            ` : ''}
            ${(accionesContactoHtml || accionOrdenCompraHtml) ? `
                <div class="${accionesEstadoHtml ? 'mt-2' : ''}">
                    <div class="d-flex flex-wrap justify-content-center align-items-center text-center">
                        ${accionesContactoHtml}${accionOrdenCompraHtml}
                    </div>
                </div>
            ` : ''}
        </div>
    ` : '';

    const historialHtml = items.length ? `
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Accion</th>
                        <th>Comentarios</th>
                    </tr>
                </thead>
                <tbody>
                    ${items.map((item) => `
                        <tr>
                            <td>${escapeHtmlDocumentoEmitido(item.fecha_texto || '-')}</td>
                            <td>${escapeHtmlDocumentoEmitido(item.usuario_nombre || '-')}</td>
                            <td>${escapeHtmlDocumentoEmitido(item.accion_label || item.accion || '-')}</td>
                            <td>${construirHtmlComentariosHistorialPresupuesto(item.comentarios || '')}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    ` : `
        <div class="alert alert-light border mb-0">
            <div class="font-weight-bold mb-1">Sin eventos comerciales</div>
            <div class="text-muted">El historial comercial empieza desde el envio por mail del presupuesto.</div>
        </div>
    `;

    return `
        ${bloqueAccionesHtml}
        ${historialHtml}
    `;
}

function resolverEstadoPresupuestoVisual(estado) {
    let normalizado = String(estado || '').trim().toUpperCase();
    if (normalizado === 'IMPRESO') {
        normalizado = 'EMITIDO';
    }

    let clase = 'text-secondary';
    let badgeClass = 'badge-secondary';
    let label = estado || '';

    if (normalizado === 'EMITIDO') {
        clase = 'text-info';
        badgeClass = 'badge-info';
        label = 'Emitido';
    } else if (normalizado === 'ENVIADO') {
        clase = 'text-primary';
        badgeClass = 'badge-primary';
        label = 'Enviado';
    } else if (normalizado === 'RECIBIDO') {
        clase = 'text-success';
        badgeClass = 'badge-success';
        label = 'Recibido';
    } else if (normalizado === 'RESOLICITADO') {
        clase = 'text-warning';
        badgeClass = 'badge-warning';
        label = 'Resolicitado';
    } else if (normalizado === 'APROBADO') {
        clase = 'text-success';
        badgeClass = 'badge-success';
        label = 'Aprobado';
    } else if (normalizado === 'RECHAZADO') {
        clase = 'text-danger';
        badgeClass = 'badge-danger';
        label = 'Rechazado';
    } else if (normalizado === 'CANCELADO') {
        clase = 'text-dark';
        badgeClass = 'badge-dark';
        label = 'Cancelado';
    } else if (normalizado === 'PENDIENTE') {
        clase = 'text-danger';
        badgeClass = 'badge-danger';
        label = 'Pendiente';
    } else if (normalizado === 'BORRADOR') {
        clase = 'text-secondary';
        badgeClass = 'badge-secondary';
        label = 'Borrador';
    }

    return { clase, badgeClass, label, normalizado };
}

function renderizarBadgeEstadoListado(label, badgeClass) {
    if (!label) {
        return '';
    }

    const clases = ['badge', 'badge-pill', 'estado-chip'];
    if (badgeClass) {
        clases.push(badgeClass);
    }

    return `<span class="${clases.join(' ')}">${escapeHtmlDocumentoEmitido(label)}</span>`;
}

function estadoBloqueaEdicionComercialPresupuestoListado(estado) {
    const normalizado = String(estado || '').trim().toUpperCase();
    return ['APROBADO', 'RECHAZADO', 'CANCELADO'].includes(normalizado);
}

function estadoVisitaBloqueaEdicionListado(estadoVisita) {
    const normalizado = String(estadoVisita || '').trim().toUpperCase();
    return normalizado === 'CANCELADA';
}

function actualizarAccionEditarListado($fila, estado) {
    if (!$fila || !$fila.length) {
        return;
    }

    const $iconoVisual = $fila.find('.fa-eye[data-accion="visual"]').first();
    const $iconoEditar = $fila.find('.fa-edit[data-accion="editar"]').first();
    if (!$iconoVisual.length) {
        return;
    }

    const visual = resolverEstadoPresupuestoVisual(estado);
    const estadoVisita = String($fila.attr('data-estado-visita') || '').trim().toUpperCase();
    const bloqueadoComercial = estadoBloqueaEdicionComercialPresupuestoListado(visual.normalizado);
    const bloqueadoVisita = estadoVisitaBloqueaEdicionListado(estadoVisita);
    const bloqueado = bloqueadoComercial || bloqueadoVisita;
    const mensajeBloqueo = bloqueadoComercial
        ? `La edicion de la visita y del presupuesto esta bloqueada porque el circuito comercial esta en ${visual.label || visual.normalizado}.`
        : (bloqueadoVisita
            ? 'La edicion no esta disponible para pre-visitas canceladas.'
            : 'Visualizar');

    $iconoVisual
        .attr('data-bloqueo-comercial', bloqueadoComercial ? '1' : '0')
        .attr('data-estado-bloqueo', bloqueadoComercial ? (visual.label || '') : '')
        .attr('title', mensajeBloqueo);

    if ($.fn.tooltip) {
        $iconoVisual.tooltip('dispose');
        $iconoVisual.tooltip({
            container: 'body',
            trigger: 'hover',
            boundary: 'window'
        });
    }

    if (bloqueado) {
        if ($iconoEditar.length) {
            if ($.fn.tooltip) {
                $iconoEditar.tooltip('dispose');
            }
            $iconoEditar.remove();
        }
        return;
    }

    if ($iconoEditar.length) {
        $iconoEditar
            .attr('data-bloqueo-comercial', '0')
            .attr('data-estado-bloqueo', '')
            .attr('title', 'Editar')
            .removeClass('text-muted');

        if ($.fn.tooltip) {
            $iconoEditar.tooltip('dispose');
            $iconoEditar.tooltip({
                container: 'body',
                trigger: 'hover',
                boundary: 'window'
            });
        }
        return;
    }

    const $nuevoEditar = $('<i class="v-icon-accion p-1 fas fa-edit" data-accion="editar" data-bloqueo-comercial="0" data-estado-bloqueo="" data-toggle="tooltip" title="Editar"></i>');
    $iconoVisual.after($nuevoEditar);

    if ($.fn.tooltip) {
        $nuevoEditar.tooltip({
            container: 'body',
            trigger: 'hover',
            boundary: 'window'
        });
    }
}

function actualizarEstadoPresupuestoListado(idPrevisita, estado) {
    const $fila = $('#current_table').find(`tbody tr[data-id="${idPrevisita}"]`).first();
    if (!$fila.length) {
        return;
    }

    const $celda = $fila.find('td').eq(7);
    const visual = resolverEstadoPresupuestoVisual(estado);

    $fila.attr('data-estado-presupuesto', visual.normalizado || '');
    $celda.html(renderizarBadgeEstadoListado(visual.label, visual.badgeClass));
    actualizarAccionEditarListado($fila, visual.normalizado);

    if ($.fn.dataTable && $.fn.dataTable.isDataTable('#current_table')) {
        const tabla = $('#current_table').DataTable();
        tabla.row($fila).invalidate('dom');
        tabla.draw(false);
    }
}

function obtenerFilaListadoPresupuesto(idPrevisita) {
    return $('#current_table').find(`tbody tr[data-id="${idPrevisita}"]`).first();
}

function asegurarIconoHistorialPresupuestoListado(idPrevisita) {
    const $fila = obtenerFilaListadoPresupuesto(idPrevisita);
    if (!$fila.length) {
        return;
    }

    const $celdaAcciones = $fila.find('td').last();
    if (!$celdaAcciones.length || $celdaAcciones.find('[data-accion="historial"]').length) {
        return;
    }

    const $icono = $(
        '<i class="v-icon-accion p-1 fas fa-history" data-accion="historial" data-toggle="tooltip" title="Historial de presupuesto"></i>'
    );
    const $iconoDelete = $celdaAcciones.find('[data-accion="delete"]').first();

    if ($iconoDelete.length) {
        $iconoDelete.before($icono);
    } else {
        $celdaAcciones.append($icono);
    }

    if ($.fn.tooltip) {
        $icono.tooltip({
            container: 'body',
            trigger: 'hover',
            boundary: 'window'
        });
    }
}

function abrirModalHistorialPresupuestoDesdeListado(idPrevisita) {
    const $fila = obtenerFilaListadoPresupuesto(idPrevisita);
    if (!$fila.length) {
        return;
    }

    const razonSocial = $fila.find('td').eq(3).text().trim();
    const contextoBase = 'ID: ' + idPrevisita + ' | ' + razonSocial;

    $('#modalHistorialContexto')
        .data('base-context', contextoBase)
        .html(contextoBase);

    $('#modalHistorialPresupuesto').data('id', idPrevisita);
    $('#modalHistorialPresupuestoBody').html(
        '<div class="text-muted">Cargando historial...</div>'
    );
    $('#modalHistorialPresupuesto').modal('show');
    cargarHistorialPresupuesto(idPrevisita);
}

function cerrarModalesYAbrirHistorialPresupuesto(idPrevisita, onComplete) {
    const abrirHistorial = () => {
        abrirModalHistorialPresupuestoDesdeListado(idPrevisita);
        if (typeof onComplete === 'function') {
            window.setTimeout(onComplete, 150);
        }
    };

    const $modalEnvio = $('#modalEnviarDocumentoEmitidoPresupuesto');
    const $modalDocumentos = $('#modalDocumentosEmitidosPresupuesto');

    const cerrarDocumentos = () => {
        if ($modalDocumentos.hasClass('show')) {
            $modalDocumentos
                .off('hidden.bs.modal.postEnvioHistorial')
                .one('hidden.bs.modal.postEnvioHistorial', abrirHistorial)
                .modal('hide');
            return;
        }

        abrirHistorial();
    };

    if ($modalEnvio.hasClass('show')) {
        $modalEnvio
            .off('hidden.bs.modal.postEnvioHistorial')
            .one('hidden.bs.modal.postEnvioHistorial', cerrarDocumentos)
            .modal('hide');
        return;
    }

    cerrarDocumentos();
}

function cerrarModalEnviarYRefrescarDocumentos(idPrevisita, onComplete) {
    const $modalEnviar = $('#modalEnviarDocumentoEmitidoPresupuesto');

    $modalEnviar.one('hidden.bs.modal', function () {
        if (idPrevisita > 0 && $('#modalDocumentosEmitidosPresupuesto').hasClass('show')) {
            cargarDocumentosEmitidosPresupuesto(idPrevisita);
        }

        if (typeof onComplete === 'function') {
            onComplete();
        }
    });

    $modalEnviar.modal('hide');
}

function actualizarEstadoActualModalHistorial(estado) {
    const visual = resolverEstadoPresupuestoVisual(estado);
    const $contexto = $('#modalHistorialContexto');
    const baseContexto = String($contexto.data('base-context') || '').trim();
    let html = escapeHtmlDocumentoEmitido(baseContexto);

    if (visual.label) {
        html += ` | <span class="${escapeHtmlDocumentoEmitido(visual.clase)}">${escapeHtmlDocumentoEmitido(visual.label)}</span>`;
    }

    $contexto.html(html);
}

function cargarHistorialPresupuesto(idPrevisita) {
    $.ajax({
        url: '../03-controller/presupuestos_guardar.php',
        type: 'POST',
        dataType: 'json',
        data: {
            via: 'ajax',
            funcion: 'obtenerHistorialComercialPresupuesto',
            id_previsita: idPrevisita
        },
        success: function (response) {
            if (!response || response.ok !== true) {
                const mensaje = response && response.msg
                    ? response.msg
                    : 'No se pudo cargar el historial.';
                $('#modalHistorialPresupuestoBody').html(
                    `<div class="alert alert-danger mb-0">${escapeHtmlDocumentoEmitido(mensaje)}</div>`
                );
                actualizarEstadoActualModalHistorial('');
                return;
            }

            $('#modalHistorialPresupuesto')
                .data('id', idPrevisita)
                .data('id-presupuesto', response.id_presupuesto || '');

            actualizarEstadoActualModalHistorial(response.estado_actual || '');

            $('#modalHistorialPresupuestoBody').html(
                construirHtmlHistorialPresupuesto(response)
            );
        },
        error: function () {
            $('#modalHistorialPresupuestoBody').html(
                '<div class="alert alert-danger mb-0">Ocurrio un error al consultar el historial.</div>'
            );
            actualizarEstadoActualModalHistorial('');
        }
    });
}

function liberarFocusTrapModalBootstrapTemporalmente($modal) {
    const $modalActivo = $modal && $modal.length ? $modal : $('#modalHistorialPresupuesto');

    if (!$modalActivo.length || !$modalActivo.hasClass('show')) {
        return null;
    }

    const instanciaModal = $modalActivo.data('bs.modal');

    $(document).off('focusin.bs.modal');

    return () => {
        if (!$modalActivo.hasClass('show')) {
            return;
        }

        const instanciaActual = $modalActivo.data('bs.modal') || instanciaModal;

        if (instanciaActual && typeof instanciaActual._enforceFocus === 'function') {
            instanciaActual._enforceFocus();
            return;
        }

        if (instanciaActual && typeof instanciaActual.enforceFocus === 'function') {
            instanciaActual.enforceFocus();
            return;
        }

        $modalActivo.trigger('focus');
    };
}

function solicitarComentariosAccionHistorialPresupuesto(titulo, texto, onConfirm) {
    const ejecutar = (comentarios) => {
        if (typeof onConfirm === 'function') {
            onConfirm(String(comentarios || '').trim());
        }
    };

    if (!window.Swal || typeof Swal.fire !== 'function') {
        const respuesta = window.prompt(`${texto}\n\nComentario (opcional):`, '');
        if (respuesta === null) {
            return;
        }

        ejecutar(respuesta);
        return;
    }

    const restaurarFocusModal = liberarFocusTrapModalBootstrapTemporalmente($('#modalHistorialPresupuesto'));

    Swal.fire({
        icon: 'question',
        title: titulo,
        text: texto,
        input: 'textarea',
        inputLabel: 'Comentarios',
        inputPlaceholder: 'Agregar comentario sobre esta acción (opcional)',
        inputAttributes: {
            maxlength: '2000',
            autocapitalize: 'off'
        },
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            const input = Swal.getInput();

            if (!input) {
                return;
            }

            input.readOnly = false;
            input.disabled = false;

            window.setTimeout(() => {
                input.focus();

                if (typeof input.setSelectionRange === 'function') {
                    const largo = input.value ? input.value.length : 0;
                    input.setSelectionRange(largo, largo);
                }
            }, 50);
        },
        willClose: () => {
            if (typeof restaurarFocusModal === 'function') {
                restaurarFocusModal();
            }
        }
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        ejecutar(result.value || '');
    });
}

function abrirModalEnvioDocumentoEmitido(idDocumentoEmitido) {
    resetearModalEnviarDocumentoEmitido();
    $('#modalEnviarDocumentoEmitidoPresupuesto').modal('show');
    renderAlertasModalEnviarDocumento('Cargando configuración de envío...', 'info');

    $.ajax({
        url: '../03-controller/presupuestos_guardar.php',
        type: 'POST',
        dataType: 'json',
        data: {
            via: 'ajax',
            funcion: 'obtenerContextoEnvioDocumentoEmitidoPresupuesto',
            id_documento_emitido: idDocumentoEmitido
        },
        success: function (response) {
            if (!response || response.ok !== true) {
                renderAlertasModalEnviarDocumento(
                    escapeHtmlDocumentoEmitido(response?.msg || 'No se pudo cargar el contexto de envío.'),
                    'danger'
                );
                $('#btnEnviarDocumentoEmitido').prop('disabled', true);
                return;
            }

            const documento = response.documento || {};
            const config = response.config || {};
            const sugerencias = Array.isArray(response.sugerencias_para) ? response.sugerencias_para : [];
            const copias = Array.isArray(response.copias) ? response.copias : [];

            $('#mail_id_documento_emitido').val(documento.id_documento_emitido || '');
            $('#mail_id_presupuesto').val(documento.id_presupuesto || '');
            $('#mail_id_previsita').val(documento.id_previsita || '');
            $('#mail_documento_para').val(response.para_default || '');
            $('#mail_documento_asunto').val(response.asunto_default || '');
            $('#mail_documento_cuerpo').val(
                normalizarCuerpoMailDocumentoEmitido(response.cuerpo_default || '')
            );
            $('#modalEnviarDocumentoEmitidoContexto').html(
                escapeHtmlDocumentoEmitido(documento.numero_documento || documento.nombre_archivo || 'Documento emitido')
                + (documento.razon_social ? ` | ${escapeHtmlDocumentoEmitido(documento.razon_social)}` : '')
            );

            const opciones = ['<option value="">Seleccionar sugerencia</option>'].concat(
                sugerencias.map((item) => `<option value="${escapeHtmlDocumentoEmitido(item.email || '')}">${escapeHtmlDocumentoEmitido(item.label || item.email || '')} | ${escapeHtmlDocumentoEmitido(item.email || '')}</option>`)
            );
            $('#mail_documento_sugerencias').html(opciones.join(''));
            $('#mail_documento_copias').html(construirHtmlCopiasMailDocumentoEmitido(copias));
            $('#mail_documento_modo').html(
                `Modo actual: <strong>${escapeHtmlDocumentoEmitido(config.modo_envio_label || config.modo_envio || 'Simulación')}</strong>`
            );

            if (String(config.modo_envio || '').toLowerCase() === 'simulacion') {
                renderAlertasModalEnviarDocumento('Modo simulación activo: el correo no se enviará realmente y el presupuesto permanecerá en Emitido.', 'warning');
            } else {
                renderAlertasModalEnviarDocumento('');
            }
        },
        error: function () {
            renderAlertasModalEnviarDocumento('Ocurrió un error al cargar el contexto de envío.', 'danger');
            $('#btnEnviarDocumentoEmitido').prop('disabled', true);
        }
    });
}

function presupuestoAcciones(elemento){
      elemento = $(elemento);
      const accionable = elemento.is('[data-accion]')
            ? elemento
            : elemento.closest('[data-accion]');

      if (accionable.length) {
            elemento = accionable;
      }

      switch($(elemento).data('accion')) {

            case 'visual': // ver el presupuesto por pantalla
                var id = $(elemento).closest('tr').data('id');
                window.location.href='seguimiento_form.php?acci=v&id='+id;
            break;

            case 'editar': // editar el presupuesto
                if (estadoVisitaBloqueaEdicionListado($(elemento).closest('tr').data('estado-visita'))) {
                    const mensajeBloqueoVisita = 'La edicion no esta disponible para pre-visitas canceladas.';

                    if (window.Swal && typeof Swal.fire === 'function') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Edicion bloqueada',
                            text: mensajeBloqueoVisita,
                            confirmButtonText: 'OK'
                        });
                    } else if (typeof mostrarAdvertencia === 'function') {
                        mostrarAdvertencia(mensajeBloqueoVisita, 4);
                    } else {
                        window.alert(mensajeBloqueoVisita);
                    }
                    break;
                }

                if (String($(elemento).data('bloqueo-comercial') || '0') === '1') {
                    const estadoBloqueo = String($(elemento).data('estado-bloqueo') || '').trim();
                    const mensajeBloqueo = estadoBloqueo
                        ? `La edicion esta bloqueada porque el circuito comercial esta en ${estadoBloqueo}.`
                        : 'La edicion esta bloqueada por el estado comercial actual.';

                    if (window.Swal && typeof Swal.fire === 'function') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Edicion bloqueada',
                            text: mensajeBloqueo,
                            confirmButtonText: 'OK'
                        });
                    } else if (typeof mostrarAdvertencia === 'function') {
                        mostrarAdvertencia(mensajeBloqueo, 4);
                    } else {
                        window.alert(mensajeBloqueo);
                    }
                    break;
                }

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

            case 'mail_documento_emitido':
                var idDocumentoEmitido = Number($(elemento).data('id-documento')) || 0;

                if (idDocumentoEmitido) {
                    abrirModalEnvioDocumentoEmitido(idDocumentoEmitido);
                    break;
                }

                var urlMail = $(elemento).data('url') || '';

                if (!urlMail) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Documento no disponible',
                        text: 'No se encontró la ruta del documento seleccionado.',
                        confirmButtonText: 'OK'
                    });
                    break;
                }

                Swal.fire({
                    icon: 'info',
                    title: 'Próxima etapa',
                    text: 'El envío por mail del documento emitido se va a implementar en la próxima etapa.',
                    confirmButtonText: 'OK'
                });
            break;

            case 'estado_comercial_presupuesto':
                var idPrevisitaEstado = Number($(elemento).data('id') || $('#modalHistorialPresupuesto').data('id') || 0);
                var idPresupuestoEstado = Number($(elemento).data('id-presupuesto') || $('#modalHistorialPresupuesto').data('id-presupuesto') || 0);
                var accionComercial = String($(elemento).data('estado-comercial') || '').trim();
                var confirmTitle = String($(elemento).data('confirm-title') || 'Actualizar estado comercial');
                var confirmText = String($(elemento).data('confirm-text') || 'Se va a registrar un nuevo evento comercial para este presupuesto.');

                if (!idPrevisitaEstado || !accionComercial) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Accion no disponible',
                        text: 'No se pudo identificar el presupuesto que queres actualizar.',
                        confirmButtonText: 'OK'
                    });
                    break;
                }

                const ejecutarActualizacionEstadoComercial = (comentarios) => {
                    Swal.fire({
                        title: 'Actualizando historial...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: '../03-controller/presupuestos_guardar.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            via: 'ajax',
                            funcion: 'registrarEstadoComercialPresupuesto',
                            id_previsita: idPrevisitaEstado,
                            id_presupuesto: idPresupuestoEstado,
                            accion_comercial: accionComercial,
                            comentarios: comentarios || '',
                            id_usuario: window.ACTIVE_USER_ID || 0
                        },
                        success: function (response) {
                            if (!response || response.ok !== true) {
                                const mensajeErrorHistorial = response && response.msg
                                    ? response.msg
                                    : 'Ocurrio un error al actualizar el estado comercial.';

                                if (typeof mostrarMensajeConfirmable === 'function') {
                                    mostrarMensajeConfirmable(
                                        mensajeErrorHistorial,
                                        'ER',
                                        'NO SE PUDO ACTUALIZAR EL HISTORIAL',
                                        'OK'
                                    );
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'No se pudo actualizar el historial',
                                        text: mensajeErrorHistorial,
                                        confirmButtonText: 'OK'
                                    });
                                }
                                return;
                            }

                            actualizarEstadoPresupuestoListado(idPrevisitaEstado, response.estado_actual || '');
                            cargarHistorialPresupuesto(idPrevisitaEstado);

                            if (typeof mostrarMensajeConfirmable === 'function') {
                                mostrarMensajeConfirmable(
                                    response.msg || 'El estado comercial del presupuesto se actualizo correctamente.',
                                    'EX',
                                    'HISTORIAL ACTUALIZADO',
                                    'OK'
                                );
                            } else {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Historial actualizado',
                                    text: response.msg || 'El estado comercial del presupuesto se actualizo correctamente.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function () {
                            if (typeof mostrarMensajeConfirmable === 'function') {
                                mostrarMensajeConfirmable(
                                    'Ocurrio un error al registrar el nuevo estado comercial.',
                                    'ER',
                                    'NO SE PUDO ACTUALIZAR EL HISTORIAL',
                                    'OK'
                                );
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'No se pudo actualizar el historial',
                                    text: 'Ocurrio un error al registrar el nuevo estado comercial.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        }
                    });
                };

                solicitarComentariosAccionHistorialPresupuesto(
                    confirmTitle,
                    confirmText,
                    ejecutarActualizacionEstadoComercial
                );
            break;

            case 'contacto_comercial_presupuesto':
                var idPrevisitaContacto = Number($(elemento).data('id') || $('#modalHistorialPresupuesto').data('id') || 0);
                var idPresupuestoContacto = Number($(elemento).data('id-presupuesto') || $('#modalHistorialPresupuesto').data('id-presupuesto') || 0);
                var accionContacto = String($(elemento).data('estado-comercial') || '').trim();
                var confirmTitleContacto = String($(elemento).data('confirm-title') || 'Registrar contacto comercial');
                var confirmTextContacto = String($(elemento).data('confirm-text') || 'Se va a registrar un nuevo contacto comercial para este presupuesto.');

                if (!idPrevisitaContacto || !accionContacto) {
                    if (typeof mostrarMensajeConfirmable === 'function') {
                        mostrarMensajeConfirmable(
                            'No se pudo identificar el presupuesto sobre el que queres registrar el contacto.',
                            'ER',
                            'ACCION NO DISPONIBLE',
                            'OK'
                        );
                    } else if (window.Swal && typeof Swal.fire === 'function') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Accion no disponible',
                            text: 'No se pudo identificar el presupuesto sobre el que queres registrar el contacto.',
                            confirmButtonText: 'OK'
                        });
                    }
                    break;
                }

                const ejecutarRegistroContactoComercial = (comentarios) => {
                    Swal.fire({
                        title: 'Registrando contacto...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: '../03-controller/presupuestos_guardar.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            via: 'ajax',
                            funcion: 'registrarContactoComercialPresupuesto',
                            id_previsita: idPrevisitaContacto,
                            id_presupuesto: idPresupuestoContacto,
                            accion_comercial: accionContacto,
                            comentarios: comentarios || '',
                            id_usuario: window.ACTIVE_USER_ID || 0
                        },
                        success: function (response) {
                            if (!response || response.ok !== true) {
                                const mensajeErrorContacto = response && response.msg
                                    ? response.msg
                                    : 'Ocurrio un error al registrar el contacto comercial.';

                                if (typeof mostrarMensajeConfirmable === 'function') {
                                    mostrarMensajeConfirmable(
                                        mensajeErrorContacto,
                                        'ER',
                                        'NO SE PUDO REGISTRAR EL CONTACTO',
                                        'OK'
                                    );
                                } else if (window.Swal && typeof Swal.fire === 'function') {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'No se pudo registrar el contacto',
                                        text: mensajeErrorContacto,
                                        confirmButtonText: 'OK'
                                    });
                                }
                                return;
                            }

                            actualizarEstadoPresupuestoListado(idPrevisitaContacto, response.estado_actual || '');
                            cargarHistorialPresupuesto(idPrevisitaContacto);

                            if (typeof mostrarMensajeConfirmable === 'function') {
                                mostrarMensajeConfirmable(
                                    response.msg || 'El contacto comercial se registro correctamente.',
                                    'EX',
                                    'CONTACTO REGISTRADO',
                                    'OK'
                                );
                            } else if (window.Swal && typeof Swal.fire === 'function') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Contacto registrado',
                                    text: response.msg || 'El contacto comercial se registro correctamente.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function () {
                            if (typeof mostrarMensajeConfirmable === 'function') {
                                mostrarMensajeConfirmable(
                                    'Ocurrio un error al registrar el contacto comercial.',
                                    'ER',
                                    'NO SE PUDO REGISTRAR EL CONTACTO',
                                    'OK'
                                );
                            } else if (window.Swal && typeof Swal.fire === 'function') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'No se pudo registrar el contacto',
                                    text: 'Ocurrio un error al registrar el contacto comercial.',
                                    confirmButtonText: 'OK'
                                });
                            }
                        }
                    });
                };

                solicitarComentariosAccionHistorialPresupuesto(
                    confirmTitleContacto,
                    confirmTextContacto,
                    ejecutarRegistroContactoComercial
                );
            break;

            case 'reabrir_comercial_presupuesto':
                var idPrevisitaReapertura = Number($(elemento).data('id') || $('#modalHistorialPresupuesto').data('id') || 0);
                var idPresupuestoReapertura = Number($(elemento).data('id-presupuesto') || $('#modalHistorialPresupuesto').data('id-presupuesto') || 0);
                var confirmTitleReapertura = String($(elemento).data('confirm-title') || 'Reabrir presupuesto');
                var confirmTextReapertura = String($(elemento).data('confirm-text') || 'Se va a reabrir el circuito comercial del presupuesto.');

                if (!idPrevisitaReapertura) {
                    if (window.Swal && typeof Swal.fire === 'function') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Accion no disponible',
                            text: 'No se pudo identificar el presupuesto que queres reabrir.',
                            confirmButtonText: 'OK'
                        });
                    }
                    break;
                }

                const ejecutarReaperturaComercial = (comentarios) => {
                    Swal.fire({
                        title: 'Reabriendo circuito...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: '../03-controller/presupuestos_guardar.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            via: 'ajax',
                            funcion: 'registrarReaperturaComercialPresupuesto',
                            id_previsita: idPrevisitaReapertura,
                            id_presupuesto: idPresupuestoReapertura,
                            comentarios: comentarios || '',
                            id_usuario: window.ACTIVE_USER_ID || 0
                        },
                        success: function (response) {
                            if (!response || response.ok !== true) {
                                const mensajeErrorReapertura = response && response.msg
                                    ? response.msg
                                    : 'Ocurrio un error al reabrir el circuito comercial.';

                                Swal.fire({
                                    icon: 'error',
                                    title: 'No se pudo reabrir el circuito',
                                    text: mensajeErrorReapertura,
                                    confirmButtonText: 'OK'
                                });
                                return;
                            }

                            actualizarEstadoPresupuestoListado(idPrevisitaReapertura, response.estado_actual || '');
                            cargarHistorialPresupuesto(idPrevisitaReapertura);

                            Swal.fire({
                                icon: 'success',
                                title: 'Circuito reabierto',
                                text: response.msg || 'El circuito comercial se reabrio correctamente.',
                                confirmButtonText: 'OK'
                            });
                        },
                        error: function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'No se pudo reabrir el circuito',
                                text: 'Ocurrio un error al reabrir el circuito comercial.',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                };

                solicitarComentariosAccionHistorialPresupuesto(
                    confirmTitleReapertura,
                    confirmTextReapertura,
                    ejecutarReaperturaComercial
                );
            break;

            case 'orden_compra_presupuesto':
                if (typeof mostrarMensajeConfirmable === 'function') {
                    mostrarMensajeConfirmable(
                        'El circuito de orden de compra todavia esta en desarrollo.',
                        'AD',
                        'ORDEN DE COMPRA',
                        'OK'
                    );
                } else if (window.Swal && typeof Swal.fire === 'function') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Orden de compra',
                        text: 'El circuito de orden de compra todavia esta en desarrollo.',
                        confirmButtonText: 'OK'
                    });
                } else {
                    window.alert('El circuito de orden de compra todavia esta en desarrollo.');
                }
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
                var id   = Number(fila.data('id')) || 0;

                // Obtener Razón Social (columna 3 del datatable)
                // Índices reales según tu tabla:
                // 0 ID
                // 1 Ingreso
                // 2 CUIT
                // 3 Razón Social  <-- esta
                abrirModalHistorialPresupuestoDesdeListado(id);

            break;
    }
}

$(document).on('hidden.bs.modal', '#modalDocumentosEmitidosPresupuesto', function(){
    $(this).find('[data-toggle="tooltip"]').tooltip('dispose');
});

$(document).on('hidden.bs.modal', '#modalHistorialPresupuesto', function(){
    $(this).removeData('id').removeData('id-presupuesto');
    $('#modalHistorialContexto').removeData('base-context').html('');
    $('#modalHistorialPresupuestoBody').html('<div class="text-muted">Cargando historial...</div>');
});

$(document).on('hidden.bs.modal', '#modalEnviarDocumentoEmitidoPresupuesto', function(){
    resetearModalEnviarDocumentoEmitido();
});

$(document).on('change', '#mail_documento_sugerencias', function () {
    const email = String($(this).val() || '').trim();
    if (!email) {
        return;
    }

    const actual = String($('#mail_documento_para').val() || '').trim();
    if (!actual) {
        $('#mail_documento_para').val(email);
        return;
    }

    const lista = actual.split(',').map((item) => item.trim()).filter(Boolean);
    if (!lista.includes(email)) {
        lista.push(email);
    }
    $('#mail_documento_para').val(lista.join(', '));
});

$(document).on('submit', '#formEnviarDocumentoEmitidoPresupuesto', function (e) {
    e.preventDefault();

    const ccEmails = [];
    const ccoEmails = [];

    $('.mail-documento-copia:checked').each(function () {
        const email = String($(this).val() || '').trim();
        const tipo = String($(this).data('tipo') || 'cco').trim().toLowerCase();
        if (!email) {
            return;
        }

        if (tipo === 'cc') {
            ccEmails.push(email);
        } else {
            ccoEmails.push(email);
        }
    });

    const payload = {
        via: 'ajax',
        funcion: 'enviarDocumentoEmitidoPresupuesto',
        id_documento_emitido: $('#mail_id_documento_emitido').val(),
        id_presupuesto: $('#mail_id_presupuesto').val(),
        id_previsita: $('#mail_id_previsita').val(),
        para_email: $('#mail_documento_para').val(),
        asunto: $('#mail_documento_asunto').val(),
        cuerpo: $('#mail_documento_cuerpo').val(),
        'cc_email[]': ccEmails,
        'cco_email[]': ccoEmails,
        cco_manual: $('#mail_documento_cco_manual').val(),
        id_usuario: window.ACTIVE_USER_ID || 0
    };

    $('#btnEnviarDocumentoEmitido').prop('disabled', true);
    renderAlertasModalEnviarDocumento('Procesando envío...', 'info');

    $.ajax({
        url: '../03-controller/presupuestos_guardar.php',
        type: 'POST',
        dataType: 'json',
        traditional: true,
        data: payload,
        success: function (response) {
            $('#btnEnviarDocumentoEmitido').prop('disabled', false);

            if (!response || response.ok !== true) {
                renderAlertasModalEnviarDocumento(
                    escapeHtmlDocumentoEmitido(response?.msg || 'No se pudo procesar el envío del documento.'),
                    'danger'
                );
                return;
            }

            const idPrevisita = Number(response?.documento?.id_previsita || $('#mail_id_previsita').val() || 0);
            const estadoPresupuesto = String(response?.estado_presupuesto_actual || '').trim();
            const impactaHistorialComercial = response?.impacta_historial_comercial === true;

            if (estadoPresupuesto) {
                actualizarEstadoPresupuestoListado(idPrevisita, estadoPresupuesto);
            }
            if (idPrevisita > 0 && impactaHistorialComercial) {
                asegurarIconoHistorialPresupuestoListado(idPrevisita);
            }

            if ($('#modalHistorialPresupuesto').hasClass('show') && idPrevisita > 0 && impactaHistorialComercial) {
                cargarHistorialPresupuesto(idPrevisita);
            }

            if (impactaHistorialComercial && idPrevisita > 0) {
                cerrarModalesYAbrirHistorialPresupuesto(idPrevisita, function () {
                    mostrarSwalResultadoEnvioDocumento(response);
                });
            } else {
                cerrarModalEnviarYRefrescarDocumentos(idPrevisita, function () {
                    mostrarSwalResultadoEnvioDocumento(response);
                });
            }
            return;

            Swal.fire({
                icon: response.estado_envio === 'simulado' ? 'info' : 'success',
                title: response.estado_envio === 'simulado' ? 'Simulación registrada' : 'Documento enviado',
                text: response.msg || 'Operación realizada correctamente.',
                confirmButtonText: 'OK'
            });
        },
        error: function () {
            $('#btnEnviarDocumentoEmitido').prop('disabled', false);
            renderAlertasModalEnviarDocumento('Ocurrió un error al procesar el envío del documento.', 'danger');
        }
    });
});
