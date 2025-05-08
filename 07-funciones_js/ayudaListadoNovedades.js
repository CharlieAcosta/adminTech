function ayuda() {
    let html = '<small class="text-dark">Instructivo de Uso</small><br><strong>Listado de Novedades de Personal</strong>';
    html += '<p style="line-height: 1; text-align: justify;" class="text-dark"><small>Este sección permite visualizar, gestionar y exportar las novedades del personal para cada quincena del mes seleccionado. A continuación, se explican los elementos y funciones del módulo:</small></p>';

    // Lista principal
    html += '<ol class="text-dark" style="line-height: 1.5; text-align: left; padding-left: 10px;">';

    // Visualización y Filtrado de Novedades
    html += '<li><span style="font-weight: bold;">Visualización y Filtrado de Novedades:</span><br><br><small>';
    html += '<ul style="list-style-position: inside; padding-left: 10px; margin-left: 0; line-height: 1.5;">';
    html += '<li><strong>Columnas de Información:</strong> En la tabla, cada columna representa un tipo de novedad (como 0%, 100%, 150%, etc.) por quincena. También se muestran subtotales y un total general de novedades por agente.</li><br>';
    html += '<li><strong>Opciones de Filtrado:</strong> Puedes filtrar las novedades por nombre de agente, porcentaje de pago, y otros campos específicos usando el campo de búsqueda en la parte superior derecha de la tabla.</li>';
    html += '</ul></small></li><br>';

    // Botones Principales
    html += '<li><span style="font-weight: bold;">Botones Principales:</span><br><br><small>';
    html += '<ul style="list-style-position: inside; padding-left: 10px; margin-left: 0; line-height: 1.5;">';
    html += '<li><strong>Exportar:</strong> Este conjunto de botones permite exportar la información de novedades en varios formatos (CSV, Excel, PDF) para realizar análisis o reportes externos.</li><br>';
    html += '<li><strong>Copiar y Imprimir:</strong> Puedes copiar los datos al portapapeles o imprimir la tabla directamente desde el módulo.</li><br>';
    html += '<li><strong>Visibilidad de Columnas:</strong> Mediante el botón "Colvis", puedes seleccionar qué columnas quieres mostrar u ocultar para ajustar la visualización de la información según tus necesidades.</li>';
    html += '</ul></small></li><br>';

    // Navegación entre Meses
    html += '<li><span style="font-weight: bold;">Navegación entre Meses:</span><br><br><small>';
    html += '<ul style="list-style-position: inside; padding-left: 10px; margin-left: 0; line-height: 1.5;">';
    html += '<li><strong>Mes Anterior y Mes Siguiente:</strong> Usa los botones "Mes anterior" y "Mes siguiente" para navegar entre meses y ver las novedades de cada período. El sistema solo permite navegar a meses anteriores y al mes actual.</li><br>';
    html += '<li><strong>Actualización Automática:</strong> Al cambiar de mes, la tabla se actualiza automáticamente para mostrar las novedades correspondientes al período seleccionado.</li>';
    html += '</ul></small></li><br>';

    // Cálculos y Consideraciones
    html += '<li><span style="font-weight: bold;">Cálculos y Consideraciones:</span><br><br><small>';
    html += '<ul style="list-style-position: inside; padding-left: 10px; margin-left: 0; line-height: 1.5;">';
    html += '<li><strong>Subtotal y Total:</strong> Los subtotales se calculan para cada quincena y el total general muestra la suma de ambas. Los valores de 0% no se incluyen en el subtotal ni en el total.</li><br>';
    html += '<li><strong>Interpretación de Colores:</strong> Los valores mayores a 0% se muestran en color (rojo o verde), indicando el tipo de porcentaje, mientras que los valores de 0% permanecen en negro.</li><br>';
    html += '<li><strong>Visualización en Tiempo Real:</strong> Cualquier cambio o filtro aplicado en la tabla se refleja en tiempo real sin necesidad de recargar la página.</li>';
    html += '</ul></small></li>';

    // Cierre de la lista principal
    html += '</ol>';

    $('.ayuda').html(html);
}
