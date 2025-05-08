function registrosToFilas(parametros = {}, metodo = 'POST', callType = 'ajax', urlDestino = "../06-funciones_php/funciones.php") {
    // Añadimos el nombre de la función a los parámetros.
    parametros['funcionCall'] = 'registrosToFilas';
    parametros['callType'] = callType;

    // Hacemos la solicitud utilizando jQuery AJAX.
    return $.ajax({
        url: urlDestino,
        method: metodo,
        data: parametros,
        dataType: 'json', // Suponiendo que la respuesta sea en JSON.
        success: function (respuesta) {
            //console.log('Solicitud exitosa:', respuesta);

        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('Error al llamar a la función PHP:', textStatus, errorThrown);
            console.error('Detalles del error:', jqXHR.responseText);

            const alertParams = [
            'error',             
            'HA OCURRIDO UN ERROR', 
            'Depurar en consola.',
            3000,                              
            ];
            sAlertAutoCloseV2(alertParams);

        } 
    });
} 


