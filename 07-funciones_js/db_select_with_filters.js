/**
 * Realiza una llamada AJAX a la función PHP 'db_select_with_filters_V2' para obtener resultados de la base de datos
 * basados en los filtros especificados. Devuelve una promesa que se resuelve con los datos.
 *
 * @param {string} table Nombre de la tabla en la base de datos desde la cual se seleccionan los datos.
 * @param {Array} columns Array de nombres de columnas a las cuales se les aplicarán las condiciones.
 * @param {Array} comparisons Array de operadores de comparación para cada columna (por ejemplo, '=', '>', '<').
 * @param {Array} values Array de valores para comparar con cada columna.
 * @param {Array} orderBy Array de pares columna/orden para ordenar los resultados (por ejemplo, [['columna1', 'ASC'], ['columna3', 'DESC']]).
 * @return {Promise} Promesa que se resuelve con los datos obtenidos del servidor o se rechaza con un error.
 */
function db_select_with_filters_V2(table, columns, comparisons, values, orderBy) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'ruta/a/tu/archivo_php.php',  // Cambia esta ruta por la ruta de tu archivo PHP
            method: 'POST',
            data: {
                table: table,
                columns: columns,
                comparisons: comparisons,
                values: values,
                orderBy: orderBy,
                callType: 'ajax'  // Esto indica que se espera la respuesta en formato JSON
            },
            dataType: 'json',
            success: function(response) {
                // Verificar si la respuesta tiene un error
                if (response.error) {
                    reject("Error: " + response.error);
                } else {
                    // Resolver la promesa con los datos obtenidos
                    resolve(response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                reject("Error en la llamada AJAX: " + textStatus + " " + errorThrown);
            }
        });
    });
}

