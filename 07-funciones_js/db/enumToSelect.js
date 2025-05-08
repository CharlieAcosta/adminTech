/**
 * enumToSelectJS
 * 
 * Esta función envía una solicitud AJAX al servidor para obtener los valores
 * del campo ENUM y genera los <option> dentro de un select en la página.
 * 
 * @param {string} url - La URL completa a la que se hará la solicitud AJAX.
 * @param {string} tabla - Nombre de la tabla en la base de datos que contiene el campo ENUM.
 * @param {string} campo - Nombre del campo ENUM.
 * @param {string} leyendaOptionUno - Texto que aparecerá en el primer <option>, deshabilitado y seleccionado.
 * @param {string} orden - Define el orden de los valores ENUM. Puede ser 'asc' o 'desc'.
 * @param {string} selectId - El ID del select en el cual se generarán los options.
 * ejemplo de uso:
 *   <script>
 *       $(document).ready(function() {
 *           // Llamar a la función enumToSelectJS para llenar el select
 *           enumToSelectJS(
 *               '../06-funciones_php/db/enumToSelect.php', // URL donde se encuentra la API que devuelve los valores
 *               'mi_tabla',                                 // Nombre de la tabla (que será enviada al servidor)
 *               'mi_campo',                                 // Nombre del campo ENUM en la tabla
 *               'Selecciona una opción',                    // Texto que aparecerá en el primer <option>
 *               'asc',                                      // Orden de los valores (ascendente o descendente)
 *               'miSelect'                                  // ID del <select> que se va a llenar con las opciones
 *           );
 *       });
 *   </script>
 */


function enumToSelect(url, tabla, campo, leyendaOptionUno, orden = 'asc', selectId) {
    // Enviar solicitud AJAX al servidor para obtener los valores ENUM en formato JSON
    $.ajax({
        url: url, // Usar la URL directamente proporcionada en el parámetro
        type: 'POST',
        data: {
            tabla: tabla,
            campo: campo,
            leyenda: leyendaOptionUno,
            orden: orden,
            tipoSalida: 'ajax' // Solicitamos la salida en formato JSON
        },
        dataType: 'json',
        success: function(data) {
            // Obtener el select donde vamos a insertar los options
            const selectElement = $('#' + selectId);

            // Limpiar el select existente
            selectElement.empty();

            // Crear el primer option deshabilitado
            selectElement.append($('<option>', {
                value: '',
                text: leyendaOptionUno,
                disabled: true,
                selected: true
            }));

            // Iterar sobre los valores obtenidos y agregar cada <option>
            $.each(data, function(value, text) {
                selectElement.append($('<option>', {
                    value: value,
                    text: text
                }));
            });
        },
        error: function(xhr, status, error) {
            alert('Error al obtener los valores ENUM: ' + error);
        }
    });
}
