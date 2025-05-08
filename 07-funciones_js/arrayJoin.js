function arrayJoin(arrayRegistros, keysArray, callType = 'ajax', urlDestino = "../06-funciones_php/funciones.php", funcionCall = 'arrayJoin') {
    // Verificar si arrayRegistros y keysArray son arrays
    if (!Array.isArray(arrayRegistros) || !Array.isArray(keysArray)) {
        console.error('arrayRegistros o keysArray no son arrays válidos');
        return;
    }

    // Preparar los datos para la petición AJAX
    const data = {
        funcionCall: funcionCall,
        arrayRegistros: JSON.stringify(arrayRegistros), // Convertir a JSON
        keysArray: JSON.stringify(keysArray), // Convertir a JSON
        callType: callType
    };

    // Realizar la petición AJAX usando jQuery
    return $.ajax({
        url: urlDestino,
        type: 'POST',
        dataType: 'json', // Esperamos que el servidor devuelva JSON
        data: data, 
        success: function(response) {
            if (response.status === 'success') {
                //console.log('Registros enriquecidos:', response.data);
                return response.data;  // Devolver los registros enriquecidos
            } else {
                console.error('Error en la respuesta del servidor');

                const alertParams = [
                'error',             
                'HA OCURRIDO UN ERROR', 
                'Depurar en consola.',
                3000,                              
                ];
                sAlertAutoCloseV2(alertParams);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error en la petición AJAX:', textStatus, errorThrown);
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
