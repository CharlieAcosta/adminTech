// Esta función verifica si existe un dato en la base de datos trabaja con la funcion existInDB del lado del servidor, si el dato no existe al menos una vez devuelve false
// existInDB(
//     '../04-modelo/buscaID.php', // urlDestino = archivo del servidor que ejecuta Eje: '../04-modelo/buscaID.php', 'https:/sitio/archivo.php', string
//     'buscaregistro',            //funcionCall =  //funcion que se va llamar en el destino solo nombre sin parentesis Eje: 'buscaregistro', string
//     'clientes',                 //tabla = nombre de la tabla de la tabla donde se va a buscar Eje: 'clientes', string
//     'id_cliente',               //columnaDB = columna en la tabla contra la que se va a matchear Eje: 'id_cliente', string
//     '168',                      // valueSearch = valueSearch: valor a buscar en la columna debe coincidir con el tipo dato de la columna string, date, numeric, etc Eje; '168'
//     'Activo', = Estado de registros que se tomaran en cuenta segun valores de tabla: Eje: null, por defecto 'Activo' 'Eliminado' 'Desactivado' 
// );
function existInDB(urlDestino, funcionCall, tabla, columnaDB, valueSearch, valueStatus = 'Activo'){
//alert('existInDB:'+' | '+urlDestino+' | '+funcionCall+' | '+tabla+' | '+columnaDB+' | '+valueSearch); // Debug permanente
    return new Promise(function(resolve, reject) {
        //reject("¡Esto es un error forzado!"); // solo para debug

        $.ajax({
            type: "POST", // Método de la petición (POST)
            url: urlDestino, 
            data: {
              funcionCall	: funcionCall,
			  table         : tabla,
              columnaDB     : columnaDB,
              valueSearch	: valueSearch,
              callType      : 'ajax',
			  valueStatus   : valueStatus
            },
            dataType: "json", // Tipo de datos que esperamos recibir del servidor (JSON)
            success: function(data) {
					//console.log(data);
                    resolve(data);
            },
            error: function(xhr, status, error) {
                    //alert("Error en la llamada AJAX: " + error);
                    reject(error);
            }
        });

    });

}	

/**
 * Muestra una alerta emergente personalizada usando SweetAlert2 que se cierra automáticamente después de un tiempo especificado.
 *
 * @param {string|boolean} [icono=false] - El ícono que se mostrará en la alerta. Puede ser 'success', 'error', 'warning', 'info', 'question', o `false` para ocultar el ícono.
 * @param {string} titulo - El título principal que se mostrará en la alerta.
 * @param {string} [html=''] - Contenido HTML que se mostrará en el cuerpo de la alerta. Puede incluir texto, imágenes, enlaces, etc.
 * @param {number} [duracion=2000] - Tiempo en milisegundos durante el cual la alerta permanecerá visible antes de cerrarse automáticamente. El valor predeterminado es 2000 ms (2 segundos).
 * @param {boolean} [barraProgreso=true] - Indica si se debe mostrar una barra de progreso que visualiza el tiempo restante antes de que la alerta se cierre.
 * @param {string|boolean} [pie=false] - Texto o HTML que se mostrará en la parte inferior de la alerta como pie de página. Si es `false`, no se mostrará ningún pie de página.
 * @param {boolean} [clickFuera=false] - Determina si la alerta se puede cerrar haciendo clic fuera del popup. `false` no permite cerrar la alerta con clics fuera, `true` lo permite.
 * @param {boolean} [escape=false] - Determina si la alerta se puede cerrar presionando la tecla "Esc". `false` no permite cerrar la alerta con "Esc", `true` lo permite.
 *
 * @example
 * // Ejemplo de uso:
 * sAlertAutoClose(
 *   'success',                   // icono = 'success' (muestra un ícono de éxito)
 *   'Operación exitosa',         // titulo = 'Operación exitosa'
 *   '<p>Los datos se han guardado correctamente.</p>',  // html = un párrafo con un mensaje
 *   3000,                        // duracion = 3000 ms (3 segundos)
 *   true,                        // barraProgreso = true (muestra la barra de progreso)
 *   '<a href="#">Ver detalles</a>',  // pie = un enlace en el pie de la alerta
 *   true,                        // clickFuera = true (permite cerrar con clic fuera)
 *   true                         // escape = true (permite cerrar con la tecla "Esc")
 * );
 */
function sAlertAutoClose(icono = false, titulo, html = '', duracion = 2000, barraProgreso = true, pie = false, clickFuera = false, escape = false){

    let timerInterval
    Swal.fire({
      icon: icono,      
      title: titulo,
      html: html,
      timer: duracion,
      timerProgressBar: barraProgreso,
      showConfirmButton: false,
      footer: pie,
      allowOutsideClick: clickFuera,
      allowEscapeKey: escape,
      backdrop: true
    }).then((result) => {
      /* Read more about handling dismissals below */
      if (result.dismiss === Swal.DismissReason.timer) {
        console.log('I was closed by the timer')
      }
    });

}

/**
 * Muestra una alerta de confirmación personalizada usando SweetAlert2.
 * Permite personalizar el ícono, el título, el contenido HTML, el texto y color del botón de confirmación,
 * además de opciones para pie de página, permitir el cierre al hacer clic fuera de la alerta, y cerrar con la tecla "Esc".
 *
 * @param {string|boolean} icono - El ícono que se mostrará en la alerta. Puede ser 'success', 'error', 'warning', 'info', 'question', o `false` para ocultar el ícono.
 * @param {string} titulo - El título principal que se mostrará en la alerta.
 * @param {string} [html=''] - Contenido HTML que se mostrará en el cuerpo de la alerta. Puede incluir texto, imágenes, enlaces, etc.
 * @param {string} textoBoton - El texto que se mostrará en el botón de confirmación.
 * @param {string} colorBoton - El color del botón de confirmación. Debe ser un valor hexadecimal (ejemplo: '#28a745').
 * @param {string|boolean} [pie=false] - Texto o HTML que se mostrará en la parte inferior de la alerta como pie de página. Si es `false`, no se mostrará ningún pie de página.
 * @param {boolean} [clickFuera=false] - Determina si la alerta se puede cerrar haciendo clic fuera del popup. `false` no permite cerrar la alerta con clics fuera, `true` lo permite.
 * @param {boolean} [escape=false] - Determina si la alerta se puede cerrar presionando la tecla "Esc". `false` no permite cerrar la alerta con "Esc", `true` lo permite.
 *
 * @example
 * // Ejemplo de uso:
 * sAlertConfirm(
 *   'warning',                      // icono = 'warning' (muestra un ícono de advertencia)
 *   '¿Estás seguro?',               // titulo = '¿Estás seguro?'
 *   '<p>Esta acción no se puede deshacer.</p>',  // html = un párrafo con un mensaje
 *   'Confirmar',                    // textoBoton = 'Confirmar' (texto del botón de confirmación)
 *   '#dc3545',                      // colorBoton = '#dc3545' (rojo, para indicar una acción peligrosa)
 *   '<a href="#">Leer más</a>',     // pie = un enlace en el pie de la alerta
 *   true,                           // clickFuera = true (permite cerrar con clic fuera)
 *   true                            // escape = true (permite cerrar con la tecla "Esc")
 * );
 */
function sAlertConfirm(icono, titulo, html = '', textoBoton, colorBoton, pie = false, clickFuera = false, escape = false) {

    Swal.fire({
      icon: icono,
      title: titulo,
      html: html,
      showConfirmButton: true,
      confirmButtonText: textoBoton,
      confirmButtonColor: colorBoton,
      footer: pie,
      allowOutsideClick: clickFuera,
      allowEscapeKey: escape,
      backdrop: true
    }).then((result) => {
      if (result.dismiss === Swal.DismissReason.timer) {
        // La alerta se cerró automáticamente por el temporizador
      }
    });
}

             
/**
 * Muestra un diálogo de confirmación personalizado usando SweetAlert2.
 * Permite personalizar el ícono, el título, el contenido HTML, el texto y color de los botones de confirmación y cancelación,
 * además de opciones para pie de página, permitir el cierre al hacer clic fuera de la alerta, cerrar con la tecla "Esc" y
 * la posibilidad de invertir el orden de los botones.
 *
 * @param {string|boolean} icono - El ícono que se mostrará en la alerta. Puede ser 'success', 'error', 'warning', 'info', 'question', o `false` para ocultar el ícono.
 * @param {string} titulo - El título principal que se mostrará en la alerta.
 * @param {string} html - Contenido HTML que se mostrará en el cuerpo de la alerta. Puede incluir texto, imágenes, enlaces, etc.
 * @param {string} btnConfirmTexto - El texto que se mostrará en el botón de confirmación.
 * @param {string} btnConfirmColor - El color del botón de confirmación. Debe ser uno de los siguientes: 'primary', 'secondary', 'success', 'info', 'warning', 'danger', 'light', 'dark'.
 * @param {string} btnCancelTexto - El texto que se mostrará en el botón de cancelación.
 * @param {string} btnCancelColor - El color del botón de cancelación. Debe ser uno de los siguientes: 'primary', 'secondary', 'success', 'info', 'warning', 'danger', 'light', 'dark'.
 * @param {function} funConfirm - La función que se ejecutará si se confirma la acción.
 * @param {function} funCancel - La función que se ejecutará si se cancela la acción.
 * @param {string|boolean} [pie=false] - Texto o HTML que se mostrará en la parte inferior de la alerta como pie de página. Si es `false`, no se mostrará ningún pie de página.
 * @param {boolean} [clickFuera=false] - Determina si la alerta se puede cerrar haciendo clic fuera del popup. `false` no permite cerrar la alerta con clics fuera, `true` lo permite.
 * @param {boolean} [escape=false] - Determina si la alerta se puede cerrar presionando la tecla "Esc". `false` no permite cerrar la alerta con "Esc", `true` lo permite.
 * @param {boolean} [invertir=false] - Determina si se invierte el orden de los botones. `false` mantiene el orden predeterminado (confirmar primero), `true` invierte el orden (cancelar primero).
 *
 * @example
 * // Ejemplo de uso:
 * sAlertDialog(
 *   'warning',                      // icono = 'warning' (muestra un ícono de advertencia)
 *   '¿Eliminar registro?',          // titulo = '¿Eliminar registro?'
 *   '<p>¿Estás seguro de que deseas eliminar este registro?</p>',  // html = un párrafo con un mensaje
 *   'Eliminar',                     // btnConfirmTexto = 'Eliminar'
 *   'danger',                       // btnConfirmColor = 'danger' (rojo, para indicar una acción peligrosa)
 *   'Cancelar',                     // btnCancelTexto = 'Cancelar'
 *   'secondary',                    // btnCancelColor = 'secondary' (gris)
 *   function() {                    // funConfirm = función que se ejecuta al confirmar
 *       console.log('Registro eliminado');
 *   },
 *   function() {                    // funCancel = función que se ejecuta al cancelar
 *       console.log('Eliminación cancelada');
 *   },
 *   '<a href="#">Leer más</a>',     // pie = un enlace en el pie de la alerta
 *   true,                           // clickFuera = true (permite cerrar con clic fuera)
 *   true,                           // escape = true (permite cerrar con la tecla "Esc")
 *   true                            // invertir = true (invierte el orden de los botones)
 * );
 */
function sAlertDialog(icono, titulo, html, btnConfirmTexto, btnConfirmColor, btnCancelTexto, btnCancelColor, funConfirm, funCancel, pie = false, clickFuera = false, escape = false, invertir = false) {

    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: 'btn btn-' + btnConfirmColor + ' m-1',
            cancelButton: 'btn btn-' + btnCancelColor + ' m-1'
        },
        buttonsStyling: false
    });

    swalWithBootstrapButtons.fire({
        title: titulo,
        html: html,
        icon: icono,
        showCancelButton: true,
        confirmButtonText: btnConfirmTexto,
        cancelButtonText: btnCancelTexto,
        reverseButtons: invertir,
        footer: pie,
        allowOutsideClick: clickFuera,
        allowEscapeKey: escape,
        backdrop: true
    }).then((result) => {
        if (result.isConfirmed) {
            funConfirm(); // Ejecuta la función de confirmación si se confirma la acción
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            funCancel(); // Ejecuta la función de cancelación si se cancela la acción
        }
    });

}

function mostrarError(mensaje, segundos = 3) {
    Swal.fire({
        icon: 'error',
        title: '<H2><STRONG style="color: #ffffff;">INCONSISTENCIA</STRONG></H2>',
        html: `<H5 style="color: #ffffff;">${mensaje}</H5>`,
        background: '#dc3545',
        iconColor: '#ffffff',
        timer: segundos * 1000,
        timerProgressBar: true,
        showConfirmButton: false
    });
}

function mostrarAdvertencia(mensaje, segundos = 3) {
    Swal.fire({
        icon: 'warning',
        title: '<H2><STRONG style="color: #000000;">IMPORTANTE</STRONG></H2>',
        html: `<H5 style="color: #000000;">${mensaje}</H5>`,
        background: '#ffc107',
        iconColor: '#000000',
        timer: segundos * 1000,
        timerProgressBar: true,
        showConfirmButton: false
    });
}

function mostrarExito(mensaje, segundos = 3) {
    Swal.fire({
        icon: 'success',
        title: '<H2><STRONG style="color: #ffffff;">ACCION COMPLETADA</STRONG></H5>',
        html: `<H5 style="color: #ffffff;">${mensaje}</H4>`,
        background: '#28a745',
        iconColor: '#ffffff',
        timer: segundos * 1000,
        timerProgressBar: true,
        showConfirmButton: false
    });
}

/**
 * Muestra un diálogo de confirmación.
 * @param {string} mensaje        — Texto HTML que aparece bajo el título.
 * @param {function} onConfirm    — Callback si el usuario confirma.
 * @param {function} [onCancel]   — Callback si el usuario cancela (opcional).
 */
function mostrarConfirmacion(mensaje, onConfirm, onCancel) {
  Swal.fire({
    icon: 'warning',
    title: '<h2 class="mb-1"><STRONG>ADVERTENCIA<STRONG></h2>',
    html: '<h4 class="mt-0">'+mensaje+'</h4>',
    background: '#ffc107',
    iconColor: '#000000',
    showCancelButton: true,
    confirmButtonText: 'Sí',
    cancelButtonText: 'No',
    confirmButtonColor: '#28a745', // verde
    cancelButtonColor: '#6c757d'   // gris
  }).then(result => {
    if (result.isConfirmed) {
      onConfirm && onConfirm();
    } else {
      onCancel && onCancel();
    }
  });
}



// inputPushValue(
//  jason // jason =  {"#id": {"valor": valor1, "texto": valor2}, "#apellido": {"valor": valor1, "texto": false},}; // si el valor y el texto son iguale texto en false
// );
function inputPushValue(jason) {
  for (const indice in jason) {
    const value = jason[indice];
    const inputElement = $(indice);

    if (value['texto'] !== false) {
      inputElement.val(value['valor']).text(value['texto']).trigger('change');
      //console.log('Con texto: '+value['valor']+' '+value['texto']);
    } else {
      inputElement.val(value['valor']).text(value['valor']).trigger('change');
      //console.log('Sin texto: '+value['valor']+' '+value['valor']);
    }
  }
}

/**
* Esta funcion hace una insert simple en la base se la puede llamar por ajax o por php. Desde cliente trabaja con la funciona gemela
* simpleInsertInDB(
* '../06-funciones_php/funciones.php', urlDestino = 'url del destino que ejecuta' - string
* 'clientes',                          tabla = 'nombre_de_la_tabla_de_update' - string
* arrayColumnas,                      arrayColumnas = ['columna1', 'columna2', ...] - array
* arrayWhere,                         arrayValues = ['ValorDelCampo1', 'ValorDelCampo2', ...] - array
* undefined                           callType = 'ajax' por default - string
* )
*/

function simpleInsertInDB(urlDestino, tabla, arrayColumnas, arrayValues, callType = 'ajax') {
//alert(urlDestino+" "+tabla+" "+arrayColumnas+" "+arrayValues+" "+callType); //[DEBUG PERMANENTE]
    return new Promise(function(resolve, reject) {
        //reject("¡Esto es un error forzado!"); // debug permanente

        $.ajax({
            type: "POST", // Método de la petición (POST)
            url: urlDestino,
            data: {
                funcionCall: 'simpleInsertInDB',
                tabla: tabla,
                arrayColumnas: JSON.stringify(arrayColumnas),
                arrayValues: JSON.stringify(arrayValues),
                callType: callType
            },
            dataType: "json", // Tipo de datos que esperamos recibir del servidor (JSON)
            success: function(data) {
                //console.log(data);
                resolve(data);
            },
            error: function(xhr, status, error) {
                console.log("Error en la llamada AJAX: " + error);
                reject(error);
            }
        });
    });
}

function simpleInsertInDB_v2(urlDestino, tabla, arrayColumnas, arrayValues, callType = 'ajax') {
    return new Promise(function(resolve, reject) {
        // Realizar la llamada AJAX
        $.ajax({
            type: "POST", // Método de la petición (POST)
            url: urlDestino,
            data: {
                funcionCall: 'simpleInsertInDB_v2', // Especificamos la versión 2
                tabla: tabla,
                arrayColumnas: JSON.stringify(arrayColumnas),
                arrayValues: JSON.stringify(arrayValues),
                callType: callType
            },
            dataType: "json", // Tipo de datos que esperamos recibir del servidor (JSON)
            success: function(data) {
                // Resolver la promesa con los datos recibidos
                resolve(data);
            },
            error: function(xhr, status, error) {
                // Rechazar la promesa en caso de error
                console.log("Error en la llamada AJAX: " + error);
                reject(error);
            }
        });
    });
}


// Esta funcion hace una update simple en la base se la puede llamar por ajax o por php. Desde cliente trabaja con la funciona gemela
// simpleUpdateInDB(
//   '../06-funciones_php/funciones.php', urlDestino = 'url del destino que ejecuta' - string
//   'clientes',                          tabla = 'nombre_de_la_tabla_de_update' - string
//    arraySet,                           arraySet = {nombreDelcampo1: 'ValorDelCampo1', nombreDelcampo2: 'ValorDelCampo2'} - jason
//    arrayWhere,                         arrayWhere = {columna: 'id', condicion: '=', valorCompara: '1'} - jason
//    undefined                           callType = 'ajax' por default - string
// )
function simpleUpdateInDB(urlDestino, tabla, arraySet, arrayWhere, callType = 'ajax') {
     // console.log(urlDestino); //[DEBUG PERMANENTE]
     // console.log(tabla); //[DEBUG PERMANENTE]
     // console.log(arraySet); //[DEBUG PERMANENTE]
     // console.log(arrayWhere); //[DEBUG PERMANENTE]
     // console.log(callType); //[DEBUG PERMANENTE]

    return new Promise(function(resolve, reject) {
        //reject("¡Esto es un error forzado!"); // debug permanente

        $.ajax({
            type: "POST", // Método de la petición (POST)
            url: urlDestino,
            data: {
                funcionCall: 'simpleUpdateInDB',
                tabla: tabla,
                arraySet: JSON.stringify(arraySet),
                arrayWhere: JSON.stringify(arrayWhere),  // Puede contener múltiples condiciones
                callType: callType
            },
            dataType: "json", // Tipo de datos que esperamos recibir del servidor (JSON)
            success: function(data) {
                console.log(data);
                resolve(data);
            },
            error: function(xhr, status, error) {
                //alert("Error en la llamada AJAX: " + error);
                reject(error);
            }
        });
    });
}


function deleteInDB(urlDestino, tabla, camposCondicionesValores, callType = 'ajax') {
//console.log(urlDestino+" "+tabla+" "+camposCondicionesValores+" "+callType); //[DEBUG PERMANENTE]
    return new Promise(function(resolve, reject) {
        //reject("¡Esto es un error forzado!"); // debug permanente

        $.ajax({
            type: "POST", // Método de la petición (POST)
            url: urlDestino,
            data: {
                funcionCall: 'deleteInDB',
                tabla: tabla,
                camposCondicionesValores: JSON.stringify(camposCondicionesValores),
                callType: callType
            },
            dataType: "json", // Tipo de datos que esperamos recibir del servidor (JSON)
            success: function(data) {
                console.log(data);
                resolve(data);
            },
            error: function(xhr, status, error) {
                console.log("Error en la llamada AJAX: " + error);
                reject(error);
            }
        });
    });
}


// dTableRowDelete(
//   'current_table', // idTabla = 'currenTable' string id de la tabla sin numeral
//    3               //idRow = 3 Entero
// )

function dtableRowDelete(idTabla, idRow) {
  var tabla = $('#' + idTabla).DataTable();
  var filaEliminar = tabla.row('[data-id="' + idRow + '"]');

  if (filaEliminar.length === 0) {
    console.log('No se encontró ninguna fila con data-id: ' + idRow);
    return;
  }

  filaEliminar.remove().draw();
}

// funciones custom //////////////////////////////////////////////////////////////////////////////////////////

function dataByIdCalleLocalidad(urlDestino, funcionCall, idCalle, idLocalidad){
//alert('existInDB'+' '+urlDestino+' '+funcionCall+' '+tabla+' '+columnaDB+' '+valueSearch);
    return new Promise(function(resolve, reject) {
        //reject("¡Esto es un error forzado!"); // solo para debug

        $.ajax({
            type: "POST", // Método de la petición (POST)
            url: urlDestino, 
            data: {
              funcionCall    : funcionCall,
              idCalle        : idCalle,
              idLocalidad    : idLocalidad,
              callType       : 'ajax',
            },
            dataType: "json", // Tipo de datos que esperamos recibir del servidor (JSON)
            success: function(data) {
          //console.log(data);
                    resolve(data);
            },
            error: function(xhr, status, error) {
                    //console.log("Error en la llamada AJAX: " + error);
                    reject(error);
            }
        });

    });

} 


// Función someFunctions que acepta un array de funciones y un intervalo
function someFunctions(functionsArray, interval) {
  functionsArray.forEach((func, index) => {
    setTimeout(func, index * interval);
  });
}


// esta funcion obtiene todos los nombres de campo del formulario
function NombresCampos(formularioId) {
  // Seleccionar el formulario por su ID
  var formulario = document.getElementById(formularioId);

  // Obtener todos los elementos de entrada, textarea y select dentro del formulario
  var elementos = formulario.querySelectorAll('input, textarea, select');

  // Crear un array para almacenar los nombres de los campos
  var nombresCampos = [];

  // Iterar sobre los elementos y guardar los nombres de los campos en el array
  elementos.forEach(function(elemento) {
    if (elemento.name) {
      nombresCampos.push(elemento.name);
    }
  });

  // Devolver el array con los nombres de los campos
  return nombresCampos;
}

// esta funcion obtiene todos los nombres de campo del formulario
function ValoresCampos(formularioId) {
  // Seleccionar el formulario por su ID
  var formulario = document.getElementById(formularioId);

  // Obtener todos los elementos de entrada, textarea y select dentro del formulario
  var elementos = formulario.querySelectorAll('input, textarea, select');

  // Crear un array para almacenar los valores de los campos
  var valoresCampos = [];

  // Iterar sobre los elementos y guardar los valores de los campos en el array
  elementos.forEach(function(elemento) {
    if (elemento.name) {
      valoresCampos.push(elemento.value);
    }
  });

  // Devolver el array con los valores de los campos
  return valoresCampos;
}


// crea un json con los campos de un formulario
function serializeForm(formulario) {
    const datos = {};
    const elementos = $("#"+formulario).serializeArray();
    $.each(elementos, function(index, elemento) {
        datos[elemento.name] = elemento.value;
    });
    return datos;
}

/**
 * Ejemplo de uso:
 * 
 * const urlDestino = 'ruta/al/servidor';
 * const funcionCall = 'nombreDeLaFuncion';
 * const tabla = 'nombreDeLaTabla';
 * const columnasDB = ['columna1', 'columna2']; // Nombres de las columnas a buscar
 * const valoresSearch = ['valor1', 'valor2']; // Valores correspondientes a las columnas
 * 
 * existInDBByMultipleValues(urlDestino, funcionCall, tabla, columnasDB, valoresSearch)
 *     .then(data => {
 *         console.log('Datos recibidos:', data);
 *     })
 *     .catch(error => {
 *         console.error('Error:', error);
 *     });
 * 
 * Posibles valores permitidos:
 * - urlDestino: Una cadena con la URL del servidor al que se realizará la petición.
 * - funcionCall: Una cadena con el nombre de la función a llamar en el servidor.
 * - tabla: Una cadena con el nombre de la tabla en la base de datos.
 * - columnasDB: Un array de cadenas con los nombres de las columnas a buscar.
 * - valoresSearch: Un array de cadenas con los valores correspondientes a buscar en las columnas.
 */

function existInDBByMultipleValues(urlDestino, funcionCall, tabla, columnasDB, valoresSearch) {
    return new Promise(function(resolve, reject) {
        // Verificar que columnasDB y valoresSearch sean arrays de la misma longitud
        if (!Array.isArray(columnasDB) || !Array.isArray(valoresSearch) || columnasDB.length !== valoresSearch.length) {
            return reject(new Error("Los parámetros columnasDB y valoresSearch deben ser arrays de la misma longitud"));
        }

        // Crear un objeto con los pares de columnas y valores
        let data = {
            funcionCall: funcionCall,
            table: tabla,
            callType: 'ajax',
            columnasDB: columnasDB,
            valoresSearch: valoresSearch 
        };

        //DEBUG alert(JSON.stringify(data, null, 2));
        $.ajax({
            type: "POST", // Método de la petición (POST)
            url: urlDestino, // URL a la que se envía la petición
            data: data, // Datos a enviar
            dataType: "json", // Tipo de datos que esperamos recibir del servidor (JSON)
            success: function(data) {
                // Resolver la promesa con los datos recibidos
                resolve(data);
            },
            error: function(xhr, status, error) {
                // Rechazar la promesa con el error recibido
                reject(new Error(`Error en la llamada AJAX: ${error}`));
            }
        });
    });
}

/**
 * Obtiene la fecha actual del sistema en el formato especificado.
 * 
 * Formatos soportados:
 * "AAAA-MM-DD", "AA-MM-DD", "DD/MM/AAAA", "DD/MM/AA", "DD-MM-AAAA", 
 * "DD-MM-AA", "AAAA/MM", "AAAA-MM", "MM/AAAA", "MM-AAAA", "MM/DD", 
 * "MM-DD", "DD/MM", "DD-MM", "DD"
 * 
 * @param {string} formato - El formato deseado para la fecha.
 * @returns {string} La fecha formateada según el formato especificado.
 * @throws {Error} Si el formato no está soportado.
 */

function fechaActual(formato) {
    const fecha = new Date();
    const dia = String(fecha.getDate()).padStart(2, '0');
    const mes = String(fecha.getMonth() + 1).padStart(2, '0'); // Los meses van de 0-11
    const año = String(fecha.getFullYear());
    const añoCorto = año.slice(-2);

    let fechaFormateada;

    switch (formato) {
        case "AAAA-MM-DD":
            fechaFormateada = `${año}-${mes}-${dia}`;
            break;
        case "AA-MM-DD":
            fechaFormateada = `${añoCorto}-${mes}-${dia}`;
            break;
        case "DD/MM/AAAA":
            fechaFormateada = `${dia}/${mes}/${año}`;
            break;
        case "DD/MM/AA":
            fechaFormateada = `${dia}/${mes}/${añoCorto}`;
            break;
        case "DD-MM-AAAA":
            fechaFormateada = `${dia}-${mes}-${año}`;
            break;
        case "DD-MM-AA":
            fechaFormateada = `${dia}-${mes}-${añoCorto}`;
            break;
        case "AAAA/MM":
            fechaFormateada = `${año}/${mes}`;
            break;
        case "AAAA-MM":
            fechaFormateada = `${año}-${mes}`;
            break;
        case "MM/AAAA":
            fechaFormateada = `${mes}/${año}`;
            break;
        case "MM-AAAA":
            fechaFormateada = `${mes}-${año}`;
            break;
        case "MM/DD":
            fechaFormateada = `${mes}/${dia}`;
            break;
        case "MM-DD":
            fechaFormateada = `${mes}-${dia}`;
            break;
        case "DD/MM":
            fechaFormateada = `${dia}/${mes}`;
            break;
        case "DD-MM":
            fechaFormateada = `${dia}-${mes}`;
            break;
        case "DD":
            fechaFormateada = `${dia}`;
            break;
        default:
            throw new Error("Formato no soportado");
    }

    return fechaFormateada;
}

/**
 * Obtiene la hora actual del sistema en el formato especificado.
 * 
 * Formatos soportados:
 * "HH:MM:SS", "HH:MM", "HH/MM/SS", "HH-MM-SS", "HH-MM", "HH/MM", "H:MM", "H/MM"
 * 
 * @param {string} formato - El formato deseado para la hora.
 * @returns {string} La hora formateada según el formato especificado.
 * @throws {Error} Si el formato no está soportado.
 */
function horaActual(formato) {
    const fecha = new Date();
    const horas = String(fecha.getHours()).padStart(2, '0');
    const minutos = String(fecha.getMinutes()).padStart(2, '0');
    const segundos = String(fecha.getSeconds()).padStart(2, '0');
    const horaCorta = String(fecha.getHours());

    let horaFormateada;

    switch (formato) {
        case "HH:MM:SS":
            horaFormateada = `${horas}:${minutos}:${segundos}`;
            break;
        case "HH:MM":
            horaFormateada = `${horas}:${minutos}`;
            break;
        case "HH/MM/SS":
            horaFormateada = `${horas}/${minutos}/${segundos}`;
            break;
        case "HH-MM-SS":
            horaFormateada = `${horas}-${minutos}-${segundos}`;
            break;
        case "HH-MM":
            horaFormateada = `${horas}-${minutos}`;
            break;
        case "HH/MM":
            horaFormateada = `${horas}/${minutos}`;
            break;
        case "H:MM":
            horaFormateada = `${horaCorta}:${minutos}`;
            break;
        case "H/MM":
            horaFormateada = `${horaCorta}/${minutos}`;
            break;
        default:
            throw new Error("Formato no soportado");
    }

    return horaFormateada;
}
