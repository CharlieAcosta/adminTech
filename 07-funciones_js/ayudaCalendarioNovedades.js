function ayuda() {
    let html = '<small class="text-dark">Instructivo de Uso</small><br><strong>Novedades del Agente</strong>';
    html += '<p style="line-height: 1; text-align: justify;" class="text-dark"><small>Este módulo permite gestionar las novedades de un agente en un calendario de manera interactiva. A continuación te explicamos cómo utilizar cada una de las funciones y elementos presentes:</small></p>';

    // Lista principal
    html += '<ol class="text-dark" style="line-height: 1.5; text-align: left; padding-left: 10px;">';

    // Agregar Novedades al Calendario
    html += '<li><span style="font-weight: bold;">Agregar Novedades al Calendario:</span><br><br><small>';
    html += '<ul style="list-style-position: inside; padding-left: 10px; margin-left: 0; line-height: 1.5;">';
    html += '<li><strong>Novedades Disponibles:</strong> En la barra lateral izquierda, puedes ver las diferentes novedades disponibles para agregar al calendario. Estas se arrastran directamente al día correspondiente en el calendario.</li><br>';
    html += '<li><strong>Drag and Drop:</strong> Simplemente arrastra la novedad deseada hacia el día que quieras asignarla. El sistema validará si es un día permitido o si es un feriado, según las restricciones de cada tipo de novedad.</li><br>';
    html += '<li><strong>Restricciones:</strong> Cada tipo de novedad tiene restricciones (por ejemplo, algunas solo pueden asignarse en días hábiles o fines de semana, o solo en feriados). Si intentas colocar una novedad en un día no permitido, recibirás una advertencia y la acción no será permitida.</li>';
    html += '</ul></small></li><br>';

    // Botones Principales
    html += '<li><span style="font-weight: bold;">Botones Principales:</span><br><br><small>';
    html += '<ul style="list-style-position: inside; padding-left: 10px; margin-left: 0; line-height: 1.5;">';
    html += '<li><strong>Guardar:</strong> Este botón guarda todas las modificaciones realizadas en el calendario, registrando las novedades asignadas o eliminadas para el agente en el mes actual.</li><br>';
    html += '<li><strong>Cancelar:</strong> Este botón cancela cualquier cambio no guardado en el calendario, restaurando la vista al estado anterior sin registrar los cambios.</li><br>';
    html += '<li><strong>Limpiar:</strong> Al presionar este botón, se eliminan todas las novedades del calendario para el mes en curso. Esto es útil si deseas reiniciar completamente la asignación de novedades.</li><br>';
    html += '<li><strong>Mensualizar:</strong> Este botón te permite agregar la novedad "Presente" de manera automática para todos los días hábiles del mes actual, excluyendo los feriados, donde se marcará automáticamente como "Feriado".</li>';
    html += '</ul></small></li><br>';

    // Calendario y Eventos
    html += '<li><span style="font-weight: bold;">Calendario y Eventos:</span><br><br><small>';
    html += '<ul style="list-style-position: inside; padding-left: 10px; margin-left: 0; line-height: 1.5;">';
    html += '<li><strong>Navegación por Meses:</strong> Puedes moverte entre meses utilizando los botones "Mes anterior" y "Mes siguiente". El calendario solo permite añadir novedades en los meses actuales y anteriores, según lo configurado en el sistema.</li><br>';
    html += '<li><strong>Eliminar Eventos:</strong> Puedes eliminar una novedad haciendo clic sobre ella y confirmando su eliminación. Alternativamente, puedes eliminar una novedad arrastrando otra novedad sobre la existente para reemplazarla.</li><br>';
    html += '<li><strong>Visualización de Feriados:</strong> Los días que coinciden con feriados serán marcados automáticamente en el calendario, y verás una etiqueta indicando el motivo del feriado. No podrás agregar novedades regulares en esos días a menos que sea una novedad especial.</li>';
    html += '</ul></small></li><br>';

    // Consideraciones
    html += '<li><span style="font-weight: bold;">Consideraciones:</span><br><br><small>';
    html += '<ul style="list-style-position: inside; padding-left: 10px; margin-left: 0; line-height: 1.5;">';
    html += '<li><strong>Guardar los Cambios:</strong> Asegúrate de guardar los cambios antes de salir de la página o cambiar de vista, ya que cualquier modificación no guardada será descartada.</li><br>';
    html += '<li><strong>Restricciones de Novedades:</strong> Las restricciones de cada novedad están basadas en los días de la semana y feriados. Si intentas agregar una novedad en un día no permitido, el sistema te notificará y no permitirá realizar el cambio.</li><br>';
    html += '<li><strong>Navegación:</strong> La navegación entre meses te permite visualizar y gestionar las novedades de meses anteriores. No puedes agregar novedades en meses futuros.</li>';
    html += '</ul></small></li>';

    // Cierre de la lista principal
    html += '</ol>';

    $('.ayuda').html(html);
}
