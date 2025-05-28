function normalizarFecha(fecha) {
    // Si ya está en formato ISO (YYYY-MM-DD), devolvés directo
    if (/^\d{4}-\d{2}-\d{2}$/.test(fecha)) return fecha;

    // Si está en formato DD-MM-YYYY, lo convertís a YYYY-MM-DD
    const partes = fecha.split('-');
    if (partes.length === 3) {
        return `${partes[2]}-${partes[1]}-${partes[0]}`;
    }

    return fecha; // fallback sin modificación
}

// Ejemplo de uso en la función actionNovedadProcesada:
function actionNovedadProcesada(rowNumber, agente, html, fecha, novedad, idAgente) {

    // Comprobación de la variable usuarioLogueado
    if (typeof usuarioLogueado === 'undefined' || typeof usuarioLogueado.id_usuario === 'undefined') {
        sAlertAutoClose(
            'error', 
            "ERROR INESPERADO", 
            "El usuario logueado no está definido", 
            2000, 
            true, 
            false, 
            false, 
            false
        );
        return;
    }

    // Obtener el código de novedad basado en el tipo de acción y la fecha
    const codigoNovedad = obtenerCodigoNovedad(fecha, novedad);

    // Mostrar un cuadro de diálogo para confirmar la acción
    sAlertDialog(
        false,   
        '<h3 class=""><b>' + agente + '</b></h3>',
        '<h4>¿Confirma ' + html + ' para el día <strong><br>' + convertirFecha(fecha, 'DD-MM-YYYY') + '</strong>?</h4>', 
        'SI',            
        'success',         
        'NO',
        'secondary',
        function() {
            // Si el usuario confirma, realizamos la actualización en la base de datos
            simpleUpdateInDB(
                '../06-funciones_php/funciones.php',
                'obras_asistencia',
                {
                    obas_log_accion: 'edit', 
                    obas_procesado: novedad, 
                    obas_log_usuario_id: usuarioLogueado.id_usuario
                },
                [
                    { columna: 'obas_fecha', condicion: '=', valorCompara: fecha },     
                    { columna: 'obas_id_usuario', condicion: '=', valorCompara: idAgente }
                ]
            )
            .then(function(response) {
                if (response === true) {
                    // Eliminación de filas y actualización de la tabla, como en el código existente

                var table = $('#current_table').DataTable();
                var rowNode = table.row(rowNumber).node();  // Nodo de la fila original
                var fechaOriginal = $(rowNode).find('td:eq(0)').text().trim(); // Primera columna: fecha
                var nroDocumento = $(rowNode).find('td:eq(1)').text().trim(); // Segunda columna: número de documento

                // Eliminar todas las filas que coincidan con la misma fecha y nro de documento
                table.rows(function(idx, data, node) {
                    var fechaRow = $(node).find('td:eq(0)').text().trim(); // Primera columna: fecha
                    var documentoRow = $(node).find('td:eq(1)').text().trim(); // Segunda columna: nro documento

                    // Comparar si la fila tiene la misma fecha y nro de documento
                    return fechaRow === fechaOriginal && documentoRow === nroDocumento;
                }).remove(); // Eliminar las filas seleccionadas

                // Redibujar la tabla después de eliminar las filas, manteniendo la paginación con 'full-hold'
                table.draw('full-hold');

                    // Intentamos insertar en novedades_personal
                    function intentarInsertarEnNovedades(intentosRestantes) {
                        simpleInsertInDB_v2(
                            '../06-funciones_php/funciones.php',
                            'novedades_personal_2',                
                            ['id_usuario', 'novedad_codigo', 'fecha'],
                            [idAgente, codigoNovedad, fecha]
                        ).then(function(insertResponse) {
                            if (insertResponse.success === true) {
                                sAlertAutoClose('success', "NOVEDAD REGISTRADA", undefined, 2000, true, false, false, false);
                            } else {
                                if (intentosRestantes > 0) {
                                    intentarInsertarEnNovedades(intentosRestantes - 1);
                                } else {
                                    sAlertAutoClose('error', "ERROR EN LA TABLA NOVEDADES", "Problema al registrar en novedades_personal. Contacta al administrador.", 3000, true, false, false, false);
                                }
                            }
                        }).catch(function(error) {
                            console.error("Error capturado en novedades_personal:", error);
                            sAlertAutoClose('error', "ERROR EN EL SISTEMA", "Problema inesperado al insertar en novedades_personal. Contacta al administrador.", 3000, true, false, false, false);
                        });
                    }

                    intentarInsertarEnNovedades(3);

                } else {
                    sAlertAutoClose('error', "NO HAY CONEXIÓN CON LA BASE DE DATOS", "Inténtelo nuevamente en unos minutos", 2000, true, false, false, false);
                }
            })
            .catch(function(error) {
                console.error("Error capturado:", error);
                sAlertAutoClose('error', "ERROR EN LA TABLA ASISTENCIA", "Inténtelo nuevamente en unos minutos. Si persiste, notificar al administrador.", 2000, true, false, false, false);
            });
        },
        function() {
            // Acción si el usuario cancela la confirmación
        }
    );
}

// Obtener el día de la semana y verificar si es feriado
function obtenerCodigoNovedad(fecha, tipoNovedad) {
    const diaSemana = new Date(normalizarFecha(fecha) + 'T00:00:00').getDay(); // 0 = Domingo, 1 = Lunes, ..., 6 = Sábado
    const esFeriado = window.feriados.some(feriado => feriado[0] === normalizarFecha(fecha)); // Compara con los feriados cargados

    // Mapeo de códigos de novedad según el tipo y criterios definidos
    let codigoNovedad = null;
    if (tipoNovedad === 'P') { // Presente
        if (diaSemana >= 1 && diaSemana <= 5) {
            codigoNovedad = esFeriado ? 'PRESFE' : 'PRES';
        } else if (diaSemana === 6) {
            codigoNovedad = esFeriado ? 'PRESAFE' : 'PRESSA';
        } else if (diaSemana === 0) {
            codigoNovedad = esFeriado ? 'PREDOFE' : 'PRESDO';
        }
    } else if (tipoNovedad === 'M') { // Media Jornada
        if (diaSemana >= 1 && diaSemana <= 5) {
            codigoNovedad = esFeriado ? 'MEJOFE' : 'MEJO';
        } else if (diaSemana === 6) {
            codigoNovedad = esFeriado ? 'MEJOSAFE' : 'MEJOSA';
        } else if (diaSemana === 0) {
            codigoNovedad = esFeriado ? 'MEJODOFE' : 'MEJODO';
        }
    } else if (tipoNovedad === 'A') { // Ausente
        if (diaSemana >= 1 && diaSemana <= 5 && !esFeriado) {
            codigoNovedad = 'AUCA';
        } else if (esFeriado) {
            codigoNovedad = 'AUSEFE';
        } else if ((diaSemana === 6 || diaSemana === 0) && !esFeriado) {
            codigoNovedad = 'AUSESD';
        }
    }

    return codigoNovedad;
}