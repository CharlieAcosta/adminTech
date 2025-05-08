function actionDeleteRegistro(id, rowNumber) {
    //alert(id); alert(row);
    sAlertDialog(
        false,   
        '<h3 class=""><b>BORRAR REGISTRO</b></h3>',
        '<h5>¿Confirma el borrado del registro?</h5>', 
        'SI',            
        'success',         
        'NO',
        'secondary',
        function(){
                    //console log(obas_log_accion);

                    simpleUpdateInDB(
                        '../06-funciones_php/funciones.php',
                        'obras_asistencia',
                        {obas_log_accion: 'delete', obas_log_usuario_id: usuarioLogueado.id_usuario},
                        [{columna: 'obas_id', condicion: '=', valorCompara: Number(id)}]
                    )
                    .then(function(response) {
                      if (response === true) {
                          // La actualización fue exitosa
                          var titulo = "REGISTRO ELIMINADO";
                          sAlertAutoClose(icono = 'success', titulo, undefined, duracion = 2000, barraProgreso = true, pie = false, clickFuera = false, escape = false);

                          // Obtener referencia a la fila eliminada
                          var table = $('#current_table').DataTable();
                          var deletedRow = table.row(rowNumber).node();

                          // Verificar si la fila eliminada es una salida
                          var estado = $(deletedRow).attr('data-estado');
                          if (estado === 'Salida') {
                              // Obtener la fila anterior
                              var previousRow = $(deletedRow).prev('tr');

                              if (previousRow.length) {
                                  // Buscar el td con la clase .v-accion-delete dentro del último td de la fila anterior
                                  var accionDelete = previousRow.find('td:last-child .v-accion-delete');
                                  if (accionDelete.length) {
                                      // Quitar la clase v-accion-hidden
                                      accionDelete.removeClass('v-accion-hidden');
                                  }
                              }
                          }

                          // Eliminar la fila
                          table.row(rowNumber).remove().draw('full-hold');
                          processTableRows();
                        } else {
                          // La actualización falló, pero la consulta fue ejecutada
                          var titulo = "NO SE PUDO ELIMINAR EL REGISTRO";
                          var html = "Intentelo nuevamente en unos minutos";
                          sAlertAutoClose(icono = 'error', titulo, html, duracion = 2000, barraProgreso = true, pie = false, clickFuera = false, escape = false);
                          // Aquí puedes manejar el caso de error en la base de datos
                        }
                    })
                    .catch(function(error) {
                        //alert('Error al eliminar el registro:', error);
                        // Ocurrió un error en la llamada AJAX
                          var titulo = "NO SE PUDO ELIMINAR EL REGISTRO";
                          var html = "Informar al administrador del sistema";
                          sAlertAutoClose(icono = 'error', titulo, html, duracion = 2000, barraProgreso = true, pie = false, clickFuera = false, escape = false);
                        // Aquí puedes manejar el error de red o comunicación
                    });
        },
        function() { 
            
        }
    );
}