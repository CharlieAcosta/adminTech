function ayuda() {
    let html = '<small class="text-dark">Instructivo de Uso</small><br><strong>AEO | Asistencia en Obra</strong>';
    html += '<p style="line-height: 1; text-align: justify;" class="text-dark"><small>Este módulo permite gestionar y visualizar la asistencia en obra para el personal de manera efectiva. Aquí te explicamos los elementos principales para que puedas utilizar la plataforma sin problemas:</small></p>';

    // Aplicando estilos para reducir margen y padding a la lista y bullets con mayor interlineado
    html += '<ol class="text-dark" style="line-height: 1.5; text-align: left; padding-left: 10px;">';
    
    // Filtros de Búsqueda
    html += '  <li><span style="font-weight: bold;">Filtros de Búsqueda:</span><br><br><small>';
    html += '    <ul style="list-style-position: inside; padding-left: 10px; margin-left: 0; line-height: 1.5;">'; // Ajuste de interlineado aquí
    html += '      <li style="margin-left: -10px;"><strong style="margin-left: -10px;">Fecha:</strong> Puedes seleccionar una fecha específica para consultar los registros de asistencia. Usa el campo de fecha y selecciona la que te interesa.</li><br>';
    html += '      <li style="margin-left: -10px;"><strong style="margin-left: -10px;">Agente:</strong> Selecciona un agente específico del personal utilizando el desplegable con la lista de agentes. Si deseas ver todos los agentes, deja este campo en blanco.</li><br>';
    html += '      <li style="margin-left: -10px;"><strong style="margin-left: -10px;">Obra:</strong> Selecciona una obra específica para ver la asistencia en dicha obra. Puedes limpiar el filtro para ver todas las obras.</li><br>';
    html += '      <li style="margin-left: -10px;"><strong style="margin-left: -10px;">Botón Filtrar:</strong> Utiliza este botón para aplicar los filtros seleccionados y obtener los registros correspondientes.</li><br>';
    html += '      <li style="margin-left: -10px;"><strong style="margin-left: -10px;">Botón Limpiar:</strong> Restablece todos los filtros a sus valores iniciales para comenzar una nueva búsqueda.</li><br>';
    html += '    </ul>';
    html += '  </small></li><br>';

    // Tabla de Asistencia
    html += '  <li><span style="font-weight: bold;">Tabla de Asistencia:</span><br><br><small>';
    html += '    <ul style="list-style-position: inside; padding-left: 10px; margin-left: 0; line-height: 1.5;">'; // Ajuste de interlineado aquí
    html += '      <li style="margin-left: -10px;"><span style="margin-left: -10px;">La tabla muestra la información relacionada con la asistencia del personal, como la fecha, número de documento, apellidos, nombres, estado (entrada/salida), obra, horas parciales, horas totales y novedades.</span></li><br>';
    html += '      <li style="margin-left: -10px;"><span style="margin-left: -10px;">Cada columna tiene un propósito específico:</span></li><br>';
    html += '      <ul style="list-style-position: inside; padding-left: 20px; margin-left: 0; line-height: 1.5;">'; // Ajuste de interlineado aquí
    html += '        <li style="margin-left: -15px;"><strong style="margin-left: -10px;">Fecha:</strong> Fecha del registro de asistencia.</li><br>';
    html += '        <li style="margin-left: -15px;"><strong style="margin-left: -10px;">Nro. Documento:</strong> Número de documento del agente.</li><br>';
    html += '        <li style="margin-left: -15px;"><strong style="margin-left: -10px;">Estado:</strong> Indica si es una entrada o salida.</li><br>';
    html += '        <li style="margin-left: -15px;"><strong style="margin-left: -10px;">Horas Totales:</strong> Calcula el total de horas trabajadas durante el día.</li><br>';
    html += '        <li style="margin-left: -15px;"><strong style="margin-left: -10px;">Novedad:</strong> Marca si la asistencia fue completa, media jornada o si hubo ausencias.</li><br>';
    html += '      </ul><br>';
    html += '      <li style="margin-left: -15px;"><strong style="margin-left: -10px;">Botones de Exportación:</strong> Encima de la tabla, encontrarás varios botones para exportar la información en diferentes formatos: <strong>Copiar, CSV, Excel, PDF, Imprimir</strong> y <strong>Visibilidad</strong>. Estos botones permiten exportar los datos o ajustar qué columnas se muestran en la tabla.</li><br>';
    html += '      <li style="margin-left: -15px;"><strong style="margin-left: -10px;">Acciones:</strong> Puedes gestionar los registros desde la columna de acciones, que muestra diferentes opciones como <strong>eliminar</strong>, <strong>marcar como presente (P)</strong>, <strong>marcar como ausente (A)</strong>, o <strong>marcar media jornada (M)</strong>. Estos íconos te permiten realizar cambios rápidamente en el estado de asistencia del agente.</li><br>';
    html += '    </ul>';
    html += '  </small></li><br>';

    // Manejo de Registros
    html += '  <li><span style="font-weight: bold;">Manejo de Registros:</span><br><br><small>';
    html += '    <ul style="list-style-position: inside; padding-left: 10px; margin-left: 0; line-height: 1.5;">'; // Ajuste de interlineado aquí
    html += '      <li style="margin-left: -10px;"><span style="margin-left: -10px;">La aplicación calcula automáticamente las horas trabajadas y clasifica los registros de acuerdo a criterios predefinidos (completa, media jornada, ausente, etc.).</span></li><br>';
    html += '      <li style="margin-left: -10px;"><span style="margin-left: -10px;">Al procesar los registros, se muestra un mensaje de confirmación para indicarte si los registros se han procesado con éxito o si hubo un error.</span></li><br>';
    html += '      <li style="margin-left: -10px;"><span style="margin-left: -10px;">Se muestran alertas cuando ocurre un error o para confirmar la cantidad de registros procesados.</span></li><br>';
    html += '    </ul>';
    html += '  </small></li><br>';

    // Uso de la Herramienta
    html += '  <li><span style="font-weight: bold;">Uso de la Herramienta:</span><br><br><small>';
    html += '    <ul style="list-style-position: inside; padding-left: 10px; margin-left: 0; line-height: 1.5;">';
    html += '      <li style="margin-left: -10px;"><span style="margin-left: -10px;"><strong>Iconos de Acción:</strong> Los iconos en la columna de acciones te permiten realizar gestiones rápidas sobre cada registro, como eliminar (icono de papelera), marcar como presente (P), marcar como ausente (A), o marcar media jornada (M). Estos iconos están diseñados para facilitar el control de la asistencia de manera directa.</span></li><br>';
    html += '      <li style="margin-left: -10px;"><span style="margin-left: -10px;"><strong>Alertas y Notificaciones:</strong> El sistema te notificará sobre los resultados de las acciones realizadas (novedades registradas, errores en el procesamiento, etc.) mediante mensajes emergentes.</span></li><br>';
    html += '    </ul>';
    html += '  </small></li><br>';

    // Consideraciones
    html += '  <li><span style="font-weight: bold;">Consideraciones:</span><br><br><small>';
    html += '    <ul style="list-style-position: inside; padding-left: 10px; margin-left: 0; line-height: 1.5;">';
    html += '      <li style="margin-left: -10px;"><span style="margin-left: -10px;">Solo puedes realizar filtros hasta la fecha del día anterior.</span></li><br>';
    html += '      <li style="margin-left: -10px;"><span style="margin-left: -10px;">Las horas totales se marcan automáticamente según los registros de entrada y salida. En caso de no haber registros completos (entrada/salida), se marcará como "Indeterminado".</span></li><br>';
    html += '      <li style="margin-left: -10px;"><span style="margin-left: -10px;">Los cambios en la información serán reflejados de inmediato en la tabla una vez que realices alguna acción.</span></li><br>';
    html += '    </ul>';
    html += '  </small></li>';

    html += '</ol>';

    $('.ayuda').html(html);
}
