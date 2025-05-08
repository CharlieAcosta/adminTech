/**
 * Realiza una solicitud AJAX al servidor para obtener datos de la base de datos aplicando filtros específicos.
 *
 * @function db_select_with_filters_V2
 * @param {string} url - La URL del script PHP que procesará la solicitud.
 * @param {string} table - Nombre de la tabla en la base de datos de la cual se desean obtener los datos.
 * @param {Array} [columns=[]] - Array de nombres de columnas para las condiciones WHERE.
 * @param {Array} [comparisons=[]] - Array de operadores de comparación correspondientes a cada columna (por ejemplo, '=', '>', '<', 'LIKE').
 * @param {Array} [values=[]] - Array de valores para comparar con cada columna.
 * @param {Array} [orderBy=[]] - Array de arrays que especifican las columnas y el orden para la cláusula ORDER BY (por ejemplo, [['columna1', 'ASC'], ['columna2', 'DESC']]).
 * @param {Array} [selectColumns=[]] - Array de nombres de columnas que se desean obtener en los resultados. Si se deja vacío, se obtendrán todas las columnas.
 * @returns {Promise} - Una promesa que se resuelve con los datos obtenidos o se rechaza con un error.
 *
 * @example
 * // Ejemplo de uso:
 * db_select_with_filters_V2(
 *   'ruta_a_tu_archivo/funciones.php', // URL del script PHP
 *   'usuarios',                        // Nombre de la tabla
 *   ['edad', 'estado'],                // Columnas para condiciones WHERE
 *   ['>', '='],                        // Operadores de comparación
 *   [25, 'activo'],                    // Valores para comparar
 *   [['edad', 'ASC']],                 // Ordenar por 'edad' ascendente
 *   ['nombre', 'edad', 'estado']       // Columnas a seleccionar
 * ).then(function(data) {
 *   console.log('Datos obtenidos:', data);
 *   // Procesar los datos recibidos
 * }).catch(function(error) {
 *   console.error('Error:', error);
 *   // Manejar el error
 * });
 */
function db_select_with_filters_V2(url, table, columns = [], comparisons = [], values = [], orderBy = [], selectColumns = []) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                funcionCall: 'db_select_with_filters_V2', // Nombre de la función PHP a llamar en el servidor
                table: table,
                columns: columns,
                comparisons: comparisons,
                values: values,
                orderBy: orderBy,
                selectColumns: selectColumns,
                callType: 'ajax'
            },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    reject("Error: " + response.error);
                } else {
                    resolve(response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                reject("Error en la llamada AJAX: " + textStatus + " " + errorThrown);
            }
        });
    });
}