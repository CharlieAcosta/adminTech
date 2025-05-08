function historicoAEO(action) {
    if (action === 'filter') {
        // Obtener los valores de los filtros
        let fecha = document.getElementById('fecha_filter').value;
        let agente = document.getElementById('agente_filter').value;
        let obra = document.getElementById('obra_filter').value;

        // Validación de los campos
        if (!fecha) {
            // Mostrar alerta si no se ha completado la fecha
            sAlertAutoClose('warning', 'FECHA REQUERIDA', '<p>El campo de fecha es obligatorio.</p>', 3000, true, false, true, true);
            return; // No continuar si la validación falla
        }

        if (!agente && !obra) {
            // Mostrar alerta si ninguno de los campos 'agente' o 'obra' está completo
            sAlertAutoClose('warning', 'FILTROS INCOMPLETOS', '<p>Debe seleccionar al menos un Agente o una Obra.</p>', 3000, true, false, true, true);
            return; // No continuar si la validación falla
        }

        // Definir variables para los parámetros dinámicos
        let columnas = ['obas_fecha', 'obas_log_accion']; // Columnas fijas (fecha y acción)
        let operadores = ['=', '<>'];                   // Operadores fijos para esas columnas
        let valores = [fecha, 'delete'];                // Valores fijos para esas columnas

        // Agregar dinámicamente agente y/o obra según lo que esté completo
        if (agente) {
            columnas.push('obas_id_usuario');
            operadores.push('='); // operador para agente
            valores.push(agente);
        }

        if (obra) {
            columnas.push('obas_obra_id');
            operadores.push('='); // operador para obra
            valores.push(+obra);
        }

        // Llamar a db_select_with_filters_V2 con los parámetros dinámicos
          db_select_with_filters_V2(
            '../06-funciones_php/funciones.php', // URL del script PHP
            'obras_asistencia',                  // Nombre de la tabla
             columnas,                            // Columnas para condiciones WHERE
             operadores,                          // Operadores de comparación
             valores,                             // Valores para comparar
             [['obas_fecha', 'ASC'],['obas_id_usuario','ASC'],['obas_obra_id', 'ASC'],['obas_estado','ASC']],        // Ordenar por 'edad' ascendente
             []       // Columnas a seleccionar
          ).then(function(data) {
            //console.log('Datos obtenidos:', data);
            // Este es el keysArray equivalente en JavaScript
                const keysArray = [
                    ['obas_id_usuario', 'usuarios', 'id_usuario', ['apellidos', 'nombres', 'nro_documento']],
                    ['obas_obra_id', 'obras', 'obra_id', ['obra_nombre']]
                ];

                // Datos obtenidos (por ejemplo, la variable 'data' que ya tienes)
                const arrayRegistros = data;  // Aquí 'data' sería el array con los registros que viste en tu consola

                // Llamada a la función arrayJoin con fetch
                arrayJoin(arrayRegistros, keysArray)
                    .then(resultado => {
                        //console.log('Datos procesados:', resultado.data);
                        // Datos del array joineado (ejemplo de cómo se verían los registros).
                        const registros = resultado.data

                        // Claves que se van a usar.
                        const claves = [
                            'obas_fecha',
                            'nro_documento',
                            'apellidos',
                            'nombres',
                            'obas_estado',
                            'obas_obra_id',
                            'obra_nombre',
                            'obas_hora',
                            'horas',
                            'horas_total',
                            'novedad',
                            'obas_dispositivo'
                        ];

                        // Acciones que se aplicarán a cada fila.
                        const acciones = [
                            ['fa-trash'],
                            [['v-accion-hidden']],
                            []
                        ];

                        // Clases que se aplicarán a las celdas de la tabla.
                        const clases = [
                            ['aeo_fecha_class', 'text-center'],
                            ['nro_documento_class', 'text-center'],
                            ['aeo_apellido_class'],
                            ['aeo_nombre_class'],
                            ['aeo_estado_class', 'font-weight-bold'],
                            ['aeo_obra_id_class', 'text-center'],
                            ['aeo_obra_nombre_class'],
                            ['aeo_hora_class', 'text-right'],
                            ['horas_class', 'text-right', 'font-weight-bold'],
                            ['horas_total_class', 'text-right', 'font-weight-bold'],
                            ['novedad_class', 'text-center'],
                            ['dispositivo_class', 'text-center'],
                            ['aeo_acciones_class', 'text-left']
                        ];

                        // Atributos `data-*` para las filas.
                        const trData = [
                            ['id_usuario', 'obas_id_usuario'],
                            ['estado', 'obas_estado'],
                            ['id_obra', 'obas_obra_id'],
                            ['fecha', 'obas_fecha'],
                            ['apellido', 'apellidos']
                        ];

                        // Llamada a la función `registrosToFilas` mediante AJAX.
                        registrosToFilas({
                            registros: JSON.stringify(resultado.data),
                            claves: JSON.stringify(claves),
                            acciones: JSON.stringify(acciones),
                            clases: JSON.stringify(clases),
                            trData: JSON.stringify(trData)
                        }, 'POST', 'ajax', "../06-funciones_php/funciones.php", 'application/json')
                            .done(function (respuesta) {
                                //console.log('Respuesta del servidor:', respuesta); // AQUI YA TENEMOS EL HTML PARA ARMAR EL DATATABLE
                                actualizarDataTableConFiltrados(respuesta);
                            })
                            .fail(function (jqXHR, textStatus, errorThrown) {
                                console.error('Error al hacer la solicitud:', textStatus, errorThrown);

                                const alertParams = [
                                  'error',             
                                  'HA OCURRIDO UN ERROR', 
                                  'Depurar en consola.',
                                  3000,                            
                                ];
                                sAlertAutoCloseV2(alertParams);
                            });                        
                     
                    })
                    .catch(error => {
                        console.error('Error al procesar los datos:', error);

                        const alertParams = [
                        'error',             
                        'HA OCURRIDO UN ERROR', 
                        'Depurar en consola.',
                        3000,                               
                        ];
                        sAlertAutoCloseV2(alertParams);
                    });

          }).catch(function(error) {
            console.error('Error:', error);

           const alertParams = [
           'error',             
           'HA OCURRIDO UN ERROR', 
           'Depurar en consola.',
            3000,                                
            ];
            sAlertAutoCloseV2(alertParams);
          });

    }

    if (action === 'clear') {
        // Limpiar los campos de filtro
        document.getElementById('fecha_filter').value = '';
        
        // Resetear campos select2
        $('#agente_filter').val(null).trigger('change'); // Reiniciar select2 para agente
        $('#obra_filter').val(null).trigger('change');   // Reiniciar select2 para obra

        // Llamar a la función para resetear el DataTable
        resetearDataTable();
        
        //console.log('Filtros limpiados');
    }
}
