$(document).on("click",".v-boton-accion, .v-accion, .v-icon-accion, .v-accion-cancelar, .v-accion-eliminar, .v-accion-ver-todos, .v-accion-link, .v-nav-modulos, .fc-prev-button, .fc-next-button",function(event){
    event.preventDefault();
    usuariosAcciones($(this, event));
});


function usuariosAcciones(elemento, event){

    switch($(elemento).data('accion')) {
        case 'prevNextButton': //carga el mes para realizar el render

         if(window.meses.includes(viewYearMonth) == false){
                window.meses.push(viewYearMonth);
                //console.log(window.meses);
                
                // ajax para ir a buscar el mes
                $.ajax({
                    url: '../03-controller/novedadesController.php', // URL del archivo que procesará la petición 
                    type: "POST", // Método HTTP a utilizar (GET o POST)
                    dataType: 'json',
                    data: { // Datos a enviar al servidor en formato de objeto JavaScript
                      ajax: 'on',
                      funcion: 'poblarCalendarByIdyMes',
                      idAgente: $(elemento).data('id-agente'),
                      viewYearMonth: viewYearMonth,
                    },
                    success: function(respuesta) { // Función a ejecutar cuando la petición es exitosa
                      //console.log(respuesta); // Se muestra la respuesta del servidor en la consola del navegador
                      calendar.addEventSource(respuesta);
                      flagEventosMod = false; //inicializa la variable para detectar cambios en el calendario
                      eventosNormIni = {};
                      //eventos(eventosNormIni, viewYearMonth); //iniciliaza el array para guadar cambios posteriores
                    },
                    error: function(respuesta) { // Función a ejecutar cuando hay un error en la petición
                      console.log('Error: '+respuesta);
                    }
                });
         }
        break;

        case 'linkPanel': //vuelve a novedades   
         if(typeof flagEventosMod !== "undefined" && flagEventosMod == true){
            Swal.fire({
                  icon: 'warning',
                  title: 'Se han ingresado o modificado datos y no fueron guardados',
                  text: "¿Quieres guardar los cambios antes de salir?",
                  showDenyButton: true,
                  confirmButtonText: 'Si',
                  denyButtonText: 'No',
                  allowOutsideClick: false,
                  allowEscapeKey: false
              }).then((result) => {
                  /* Read more about isConfirmed, isDenied below */
                  if (result.isConfirmed) {
                      eventos(eventosNormFin, viewYearMonth);
                      guardarEventos();
                      Swal.fire({
                          title: 'NOVEDADES GUARDADAS',
                          icon: 'success',
                          allowOutsideClick: false, // Desactiva el cierre por click afuera y por el botón de escape
                          timer: 1500,
                          timerProgressBar: true,
                          showConfirmButton: false,
                        });
                        setTimeout(function() {
                            window.location.href='../01-views/panel/panel.php';
                          }, 1800);
                       
                  } else if (result.isDenied) {
                      window.location.href='../01-views/panel/panel.php';
                  }
              });  
          }else{
            window.location.href='../01-views/panel/panel.php';
          }
        break;

        case 'linkNovedades': // Vuelve al listado de novedades
            if (flagEventosMod == true) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Se han ingresado o modificado datos y no fueron guardados',
                    text: "¿Quieres guardar los cambios antes de salir?",
                    showDenyButton: true,
                    confirmButtonText: 'Si',
                    denyButtonText: 'No',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        eventos(eventosNormFin, viewYearMonth);
                        guardarEventos();
                        Swal.fire({
                            title: 'NOVEDADES GUARDADAS',
                            icon: 'success',
                            allowOutsideClick: false,
                            timer: 1400,
                            timerProgressBar: true,
                            showConfirmButton: false,
                        });
                        setTimeout(function() {
                            window.location.href = 'novedades_listado.php?month=' + viewCurrentMonth + '&year=' + viewCurrentYear;
                        }, 1900);
                    } else if (result.isDenied) {
                        window.location.href = 'novedades_listado.php?month=' + viewCurrentMonth + '&year=' + viewCurrentYear;
                    }
                });
            } else {
                window.location.href = 'novedades_listado.php?month=' + viewCurrentMonth + '&year=' + viewCurrentYear;
            }
        break;

        case 'cancelar':  
               if(flagEventosMod == true){
                    Swal.fire({
                        icon: 'warning',
                        title: 'Has modificado o ingresado datos, si cancelas no quedaran registrados',
                        text: "¿Deseas cancelar de todas maneras?",
                        showDenyButton: true,
                        confirmButtonText: 'Si',
                        denyButtonText: 'No',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then((result) => {
                        /* Read more about isConfirmed, isDenied below */
                        if (result.isConfirmed) {
                            window.location.href='novedades_listado.php';
                        } else if (result.isDenied) {

                        }
                    })  
                }else{
                    window.location.href='novedades_listado.php';
                }
        break;

        case 'novedad': // ver el agente por pantalla
            var id = $(elemento).closest('tr').data('id');
            var agente = $(elemento).data('agente');
            var presentes = $(elemento).data('presentes');
            var justificadas = $(elemento).data('justificadas');
            var injustificadas = $(elemento).data('injustificadas');
            window.location.href='agente_novedad_2.php?id_agente='+id+'&agente='+agente+'&presentes='+presentes+'&justificadas='+justificadas+'&injustificadas='+injustificadas+'&year='+currentYear+'&month='+currentMonth;
        break;

        case 'imprimir':
                // Selecciona el modal visible
                var element = document.querySelector('#modalPago .modal-content'); 

                // Oculta los botones antes de capturar
                document.querySelectorAll('.no-print').forEach(el => el.style.display = 'none');

                // Usamos html2canvas para capturar la vista como imagen
                html2canvas(element, {
                    scale: 2, // Mejora la resolución
                    useCORS: true, // Maneja contenido externo si lo hay
                }).then(function (canvas) {
                    // Restaurar los botones después de capturar
                    document.querySelectorAll('.no-print').forEach(el => el.style.display = '');

                    var imgData = canvas.toDataURL('image/png'); // Convierte la captura a imagen
                    var doc = new window.jspdf.jsPDF('p', 'mm', 'a4'); // Configura jsPDF correctamente

                    // Calcula el tamaño de la imagen para adaptarse al PDF
                    var pageWidth = doc.internal.pageSize.getWidth(); // Ancho total de la página
                    var pageHeight = doc.internal.pageSize.getHeight(); // Altura total de la página
                    var imgWidth = pageWidth - 20; // Margen de 10mm a cada lado
                    var imgHeight = (canvas.height * imgWidth) / canvas.width; // Proporción de altura

                    if (imgHeight > pageHeight) {
                        // Si la altura excede la página, agregar páginas adicionales
                        var heightLeft = imgHeight;
                        var position = 10; // Inicia con un margen de 10mm

                        while (heightLeft > 0) {
                            doc.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight); // Agrega imagen
                            heightLeft -= pageHeight - 20; // Ajusta la altura restante
                            position = 10; // Resetea la posición
                            if (heightLeft > 0) doc.addPage(); // Agrega una nueva página si queda contenido
                        }
                    } else {
                        // Si la altura cabe en una sola página
                        doc.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
                    }

                    // Abrir en una nueva pestaña
                    window.open(doc.output('bloburl'), '_blank'); 
                });
        break;

        case 'eliminar': // editar el agente
                $('#estado').html('<option selected value="Eliminado">Eliminado</option>');
                    $.ajax({
                        url: '../04-modelo/usuariosGuardarModel.php',
                        type: 'POST',
                        dataType: 'json',
                        data: $('#currentForm').serialize()+"&ajax=on&accion=eliminar",
                        // data: {
                        //    'ajax'    : 'on', 
                        //    'accion' : "alta",
                        // },
                        success: function (data) {
                            //console.log('success: '+(data));

                            Swal.fire({
                                icon: 'success',
                                title: 'El agente se elimino correctamente',
                                confirmButtonText: 'OK',
                                allowOutsideClick: false,
                                allowEscapeKey: false
                               }).then((result) => {
                                /* Read more about isConfirmed, isDenied below */
                                if (result.isConfirmed) {
                                     window.location.href='listado_personal.php';
                                }
                            })


                        },
                        error: function (data) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Algó ha salido mal',
                                text: "Intentalo más tarde o comunicate con el administrador",
                                confirmButtonText: 'OK',
                                allowOutsideClick: false,
                                allowEscapeKey: false
                               }).then((result) => {
                                /* Read more about isConfirmed, isDenied below */
                                if (result.isConfirmed) {
                                     window.location.href='listado_personal.php';
                                }
                            })
                        }
                    });   
        break;

        case 'vertodos':
                $('.v-accion-ver-todos').html('<i class="fa fa-eye"></i> Activos').data('accion', 'veractivos');
                $.ajax({
                    url: '../03-controller/usuariosController.php',
                    type: 'POST',
                    dataType: 'json',
                    //data: $('#currentForm').serialize()+"&ajax=on&accion=eliminar",
                      data: {
                            'ajax'    : 'on', 
                            'funcion' : 'poblarDatableAll',
                            'tds'     : new Array('id_usuario', 'estado', 'apellidos', 'nombres', 'tipo_documento', 'nro_documento', 'cuil', 'nacimiento', 'ingreso'), 
                      },
                    success: function (data) {
                       //console.log('success: '+(data));
                       $('#current_table').DataTable().destroy();
                       $('#current_table tbody').html(data);
                          $(function () {
                            $("#current_table").DataTable({
                              "dom": '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
                              "responsive": true, "lengthChange": true, "autoWidth": false,
                              "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
                              "language": {"url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"},
                              "columns": [{ "width": "3%" }, { "width": "5%" }, null, null, { "width": "10%" }, { "width": "10%" }, { "width": "10%" }, { "width": "11%" }, { "width": "10%" }, { "width": "10%" }]
                            }).buttons().container().appendTo('#current_table_wrapper .col-md-6:eq(0)');
                          });
                    },
                    error: function (data) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Algó ha salido mal',
                            text: "Intentalo más tarde o comunicate con el administrador",
                            confirmButtonText: 'OK',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                           }).then((result) => {
                            /* Read more about isConfirmed, isDenied below */
                            if (result.isConfirmed) {
                                 window.location.href='listado_personal.php';
                            }
                        })
                    }
                });   
        break;

        case 'veractivos':
                $('.v-accion-ver-todos').html('<i class="fa fa-eye"></i> Todos').data('accion', 'vertodos');
                $.ajax({
                    url: '../03-controller/usuariosController.php',
                    type: 'POST',
                    dataType: 'json',
                    //data: $('#currentForm').serialize()+"&ajax=on&accion=eliminar",
                      data: {
                            'ajax'    : 'on', 
                            'funcion' : 'poblarDatableActivos',
                            'tds'     : new Array('id_usuario', 'estado', 'apellidos', 'nombres', 'tipo_documento', 'nro_documento', 'cuil', 'nacimiento', 'ingreso'), 
                      },
                    success: function (data) {
                       //console.log('success: '+(data));
                       $('#current_table').DataTable().destroy();
                       $('#current_table tbody').html(data);
                          $(function () {
                            $("#current_table").DataTable({
                              "dom": '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
                              "responsive": true, "lengthChange": true, "autoWidth": false,
                              "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
                              "language": {"url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"},
                              "columns": [{ "width": "3%" }, { "width": "5%" }, null, null, { "width": "10%" }, { "width": "10%" }, { "width": "10%" }, { "width": "11%" }, { "width": "10%" }, { "width": "10%" }]
                            }).buttons().container().appendTo('#current_table_wrapper .col-md-6:eq(0)');
                          });
                    },
                    error: function (data) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Algó ha salido mal',
                            text: "Intentalo más tarde o comunicate con el administrador",
                            confirmButtonText: 'OK',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                           }).then((result) => {
                            /* Read more about isConfirmed, isDenied below */
                            if (result.isConfirmed) {
                                 window.location.href='novedades_listado.php';
                            }
                        })
                    }
                });
        break;

        case 'passOnOff':
                $('.v-ver-pass').toggleClass("fa-eye-slash");            
                $('.v-ver-pass').toggleClass("fa-eye");  
                
                if($('.v-ver-pass').data('estado')!='on'){
                    $('.v-ver-pass').data('estado','on');
                    $("#password").attr('type','text');
                }else{
                    $('.v-ver-pass').data('estado','off');
                    $("#password").attr('type','password');
                }
        break;

        case 'guardar':
                if(typeof flagEventosMod !== "undefined" && flagEventosMod == true){
                    eventos(eventosNormFin, viewYearMonth);
                    guardarEventos();
                    var nextButton = document.querySelector('.fc-next-button');
                    nextButton.disabled = false; // deshabilitar botón "Next"
                    var prevtButton = document.querySelector('.fc-prev-button');
                    prevtButton.disabled = false; // deshabilitar botón "Next"
                    flagEventosMod = false
                    Swal.fire({
                        title: 'NOVEDADES GUARDADAS',
                        icon: 'success',
                        allowOutsideClick: false, // Desactiva el cierre por click afuera y por el botón de escape
                        timer: 1100,
                        timerProgressBar: true,
                        showConfirmButton: false,
                    });
                }else{
                    Swal.fire({
                        title: 'NO TIENES CAMBIOS PARA GUARDAR',
                        icon: 'success',
                        allowOutsideClick: false, // Desactiva el cierre por click afuera y por el botón de escape
                        timer: 1400,
                        timerProgressBar: true,
                        showConfirmButton: false,
                      });
                }    
        break;

        case 'pago':

              var datatipoLiquidacion = ($(elemento).data('tipo'));

              // 1. Capturar información del agente
              var fila = $(elemento).closest('tr'); // Fila seleccionada
              var idAgente = fila.data('id');       // ID del agente
              var nombreAgente = $(elemento).data('agente'); // Nombre del agente

              // 2. Factores de conversión para los tipos de jornales
              var factores = {
                '0%': 0,
                '50%': 0.5,
                '100%': 1,
                '150%': 1.5,
                '200%': 2,
                '300%': 3,
                '400%': 4
              };

              // 3. Tipos de jornales
              var tipos = ['0%', '50%', '100%', '150%', '200%', '300%', '400%'];

              // 4. Función para llenar tabla de quincena
              function llenarTablaQuincena(fila, tipoIndices, tablaId) {
                  var tbody = $(tablaId).find('tbody');
                  tbody.empty(); // Limpiar tabla

                  var totalJornales = 0;

                  tipos.forEach(function (tipo) {
                      var cantidad = fila.find('td').eq(tipoIndices[tipo]).text().trim() || 0; 
                      var cantidadNum = parseFloat(cantidad) || 0; 
                      var total = cantidadNum * factores[tipo]; 
                      totalJornales += total;

                      tbody.append(`
                          <tr>
                              <td>${tipo}</td>
                              <td class="text-right">${cantidadNum}</td>
                              <td class="text-right">${total.toFixed(2)}</td>
                              <td class="text-right">0.00</td> <!-- Inicialmente el importe está en 0 -->
                          </tr>
                      `);
                  });

                  // Fila de subtotales
                  var totalJornalesId = (tablaId === "#tablaPrimeraQuincena") ? "totalJornalesPrimera" : "totalJornalesSegunda";
                  var totalImporteId  = (tablaId === "#tablaPrimeraQuincena") ? "totalImportePrimera"  : "totalImporteSegunda";

                  tbody.append(`
                      <tr class="font-weight-bold text-primary">
                          <td colspan="2" class="text-right">Subtotales:</td>
                          <td id="${totalJornalesId}" class="text-right">${totalJornales.toFixed(2)}</td>
                          <td id="${totalImporteId}"  class="text-right">0.00</td>
                      </tr>
                  `);
              }

              // 5. Función para llenar tabla de liquidación MENSAUAL sumando primera + segunda
              function llenarTablaMensual(fila, tipoIndicesPri, tipoIndicesSeg, tablaId) {
                  var tbody = $(tablaId).find('tbody');
                  tbody.empty();

                  // Factores para convertir a jornales (ej.: 50% => 0.5)
                  var factores = {
                    '0%': 0,
                    '50%': 0.5,
                    '100%': 1,
                    '150%': 1.5,
                    '200%': 2,
                    '300%': 3,
                    '400%': 4
                  };
                  var tipos = ['0%', '50%', '100%', '150%', '200%', '300%', '400%'];

                  var totalJornales = 0;

                  tipos.forEach(function (tipo) {
                    // Suma de ambas quincenas
                    var cantidadPri = parseFloat(fila.find('td').eq(tipoIndicesPri[tipo]).text()) || 0;
                    var cantidadSeg = parseFloat(fila.find('td').eq(tipoIndicesSeg[tipo]).text()) || 0;
                    var cantidadTotal = cantidadPri + cantidadSeg;

                    // Convertir a jornales
                    var jornales = cantidadTotal * factores[tipo];
                    totalJornales += jornales;

                    // Dejar la columna "Importe" en 0.00 (o elimínala del HTML si no la quieres)
                    tbody.append(`
                      <tr>
                        <td>${tipo}</td>
                        <td class="text-right">${cantidadTotal}</td>
                        <td class="text-right">${jornales.toFixed(2)}</td>
                        <td class="text-right">0.00</td>
                      </tr>
                    `);
                  });

                  // Fila final de subtotales
                  tbody.append(`
                    <tr class="font-weight-bold text-primary">
                      <td colspan="2" class="text-right">Subtotales:</td>
                      <td id="totalJornalesMensual" class="text-right">${totalJornales.toFixed(2)}</td>
                      <td id="totalImporteMensual" class="text-right">0.00</td>
                    </tr>
                  `);
              }


              // 6. Índices de columnas para cada quincena
              var tipoIndicesPrimera = {
                  '0%':  2,
                  '50%': 3,
                  '100%': 4,
                  '150%': 5,
                  '200%': 6,
                  '300%': 7,
                  '400%': 8
              };
              var tipoIndicesSegunda = {
                  '0%':  10,
                  '50%': 11,
                  '100%': 12,
                  '150%': 13,
                  '200%': 14,
                  '300%': 15,
                  '400%': 16
              };

              // 7. Determinar qué tipo de liquidación se quiere (primera, segunda o mensual)
              var tipoQuincena = $(elemento).data('tipo');
              //console.log("Tipo seleccionado:", tipoQuincena);

              // 8. Mostramos/ocultamos contenedores
              //    Primero ocultamos todo y luego mostramos el que corresponda
              $('#contenedorPrimeraQuincena').hide();
              $('#segundaQuincena').hide();
              $('#contenedorMensual').hide();

              if (tipoQuincena === 'primera') {
                  // Título
                  $('#modalPagoLabel').text('Liquidación');
                  // Mostrar contenedor de PRIMERA
                  $('#contenedorPrimeraQuincena').show();
                  // Llenar su tabla
                  llenarTablaQuincena(fila, tipoIndicesPrimera, '#tablaPrimeraQuincena');
                  $("#title-1q").html('<strong>Primera Quincena</strong> | '+$("#currentMonthYear").text());
              }
              else if (tipoQuincena === 'segunda') {
                  // Título
                  $('#modalPagoLabel').text('Liquidación');
                  // Mostrar contenedor de SEGUNDA
                  $('#segundaQuincena').show();
                  // Llenar su tabla
                  llenarTablaQuincena(fila, tipoIndicesSegunda, '#tablaSegundaQuincena');
                  $("#title-2q").html('<strong>Segunda Quincena</strong> | '+$("#currentMonthYear").text());
              }
              else if (tipoQuincena === 'mensual') {
                  // NUEVO: Liquidación Mensual
                  $('#modalPagoLabel').text('Liquidación');
                  // Mostrar contenedor MENSUAL
                  $('#contenedorMensual').show();
                  // Llenar su tabla con sumatoria (Primera + Segunda)
                  llenarTablaMensual(fila, tipoIndicesPrimera, tipoIndicesSegunda, '#tablaMensual');
                  $("#title-me").html('<strong>Mensual</strong> | '+$("#currentMonthYear").text());                  
              }
              else {
                  console.error('Tipo de quincena no detectado');
              }

              // 9. Pasar el nombre del agente al modal
              $('#nombreAgente').text(nombreAgente);

              // Agregar el ID del usuario al botón guardar

              $('#guardaLiquidacion').data('id', idAgente);
              $('#guardaLiquidacion')
              .data('id', idAgente)
              .data('year', obtenerYear())
              .data('month', obtenerMonth());

              // 10. Finalmente, mostrar el modal
              $('#modalPago').modal('show');


              var tipoLiquidacion = datatipoLiquidacion == 'primera' ? 'Q1' :
                                        datatipoLiquidacion == 'segunda' ? 'Q2' : 'ME';

              var idUsuario = $('#guardaLiquidacion').data('id'); 

              var anio = $('#guardaLiquidacion').data('year');
              var mes = $('#guardaLiquidacion').data('month');

              codigoLiquidacion = `${idUsuario}-${anio}-${mes}-${tipoLiquidacion}`;
        
            // Verificar si la liquidación ya existe en la base de datos
            recuperarLiquidacion(codigoLiquidacion);
        break;

        case 'guardaliquidacion':
            // Capturar el tipo de liquidación actual
            var tipoLiquidacion = $('#contenedorPrimeraQuincena').is(':visible') ? 'Q1' :
                                  $('#segundaQuincena').is(':visible') ? 'Q2' : 'ME';
            var codigoLiquidacion = obtenerCodigo();                      
            //console.log("codigoLiquidacion en guardaliquidacion" + codigoLiquidacion);


            // Obtener valores de "Varios (+)" según el tipo de liquidación
            var variosMas = tipoLiquidacion === 'Q1' ? [
                parseFloat($('#otrosMas1PrimeraQuincena').val()) || 0, 
                parseFloat($('#otrosMas2PrimeraQuincena').val()) || 0, 
                parseFloat($('#otrosMas3PrimeraQuincena').val()) || 0, 
                parseFloat($('#otrosMas4PrimeraQuincena').val()) || 0
            ] : tipoLiquidacion === 'Q2' ? [
                parseFloat($('#variosMas1SegundaQuincena').val()) || 0, 
                parseFloat($('#variosMas2SegundaQuincena').val()) || 0, 
                parseFloat($('#variosMas3SegundaQuincena').val()) || 0, 
                parseFloat($('#variosMas4SegundaQuincena').val()) || 0
            ] : [
                parseFloat($('#variosMas1Mensual').val()) || 0, 
                parseFloat($('#variosMas2Mensual').val()) || 0, 
                parseFloat($('#variosMas3Mensual').val()) || 0, 
                parseFloat($('#variosMas4Mensual').val()) || 0
            ];

            // Obtener valores de "Varios (-)" según el tipo de liquidación
            var variosMenos = tipoLiquidacion === 'Q1' ? [
                parseFloat($('#otrosMenos1PrimeraQuincena').val()) || 0, 
                parseFloat($('#otrosMenos2PrimeraQuincena').val()) || 0, 
                parseFloat($('#otrosMenos3PrimeraQuincena').val()) || 0, 
                parseFloat($('#otrosMenos4PrimeraQuincena').val()) || 0
            ] : tipoLiquidacion === 'Q2' ? [
                parseFloat($('#variosMenos1SegundaQuincena').val()) || 0, 
                parseFloat($('#variosMenos2SegundaQuincena').val()) || 0, 
                parseFloat($('#variosMenos3SegundaQuincena').val()) || 0, 
                parseFloat($('#variosMenos4SegundaQuincena').val()) || 0
            ] : [
                parseFloat($('#variosMenos1Mensual').val()) || 0, 
                parseFloat($('#variosMenos2Mensual').val()) || 0, 
                parseFloat($('#variosMenos3Mensual').val()) || 0, 
                parseFloat($('#variosMenos4Mensual').val()) || 0
            ];

            // Crear objeto con los datos de la liquidación
            var datosLiquidacion = {
                liq_codigo: codigoLiquidacion,
                liq_valor_jm: parseFloat(tipoLiquidacion === 'Q1' ? $('#valorPrimeraQuincena').val() :
                                         tipoLiquidacion === 'Q2' ? $('#valorSegundaQuincena').val() :
                                         $('#valorMensual').val()) || 0,
                liq_liquidado: parseFloat(tipoLiquidacion === 'Q1' ? $('#liquidadoPrimeraQuincena').val() :
                                          tipoLiquidacion === 'Q2' ? $('#liquidadoSegundaQuincena').val() :
                                          $('#liquidadoMensual').val()) || 0,
                liq_viaticos: parseFloat(tipoLiquidacion === 'Q1' ? $('#viaticosPrimeraQuincena').val() :
                                         tipoLiquidacion === 'Q2' ? $('#viaticosSegundaQuincena').val() :
                                         $('#viaticosMensual').val()) || 0,
                liq_masvarios1: variosMas[0],
                liq_masvarios2: variosMas[1],
                liq_masvarios3: variosMas[2],
                liq_masvarios4: variosMas[3],
                liq_menovarios1: variosMenos[0],
                liq_menovarios2: variosMenos[1],
                liq_menovarios3: variosMenos[2],
                liq_menovarios4: variosMenos[3]
            };

            //console.log("Datos a enviar:", datosLiquidacion); // 🔍 Log para verificar valores antes de enviar


            if($('#guardaLiquidacion').data('tipoGuardado') === 'insert'){
                // Enviar los datos a la base de datos usando la función `simpleInsertInDB`
                simpleInsertInDB(
                    '../06-funciones_php/funciones.php',  // URL del servidor
                    'liquidaciones',                      // Tabla destino
                    Object.keys(datosLiquidacion),        // Nombres de columna
                    Object.values(datosLiquidacion)       // Valores correspondientes
                ).then(response => {
                    //console.log("Respuesta del servidor:", response);
                    sAlertAutoClose("success", "LIQUIDACIÓN GUARDADA", "</h5>Se ha guardado la liquidación con éxito.</h5>", 1800);
                }).catch(error => {
                    //console.error("Error al guardar la liquidación:", error);
                    sAlertAutoClose("error", "ERROR", "No se pudo guardar la liquidación. Intenta nuevamente.", 1800);
                }); 
            }else{
                // Si la liquidación ya existe, actualizar en la base de datos
                var arraySet = {
                    liq_valor_jm: datosLiquidacion.liq_valor_jm,
                    liq_liquidado: datosLiquidacion.liq_liquidado,
                    liq_viaticos: datosLiquidacion.liq_viaticos,
                    liq_masvarios1: datosLiquidacion.liq_masvarios1,
                    liq_masvarios2: datosLiquidacion.liq_masvarios2,
                    liq_masvarios3: datosLiquidacion.liq_masvarios3,
                    liq_masvarios4: datosLiquidacion.liq_masvarios4,
                    liq_menovarios1: datosLiquidacion.liq_menovarios1,
                    liq_menovarios2: datosLiquidacion.liq_menovarios2,
                    liq_menovarios3: datosLiquidacion.liq_menovarios3,
                    liq_menovarios4: datosLiquidacion.liq_menovarios4
                };

                var arrayWhere = [{
                    columna: "liq_codigo",
                    condicion: "=",
                    valorCompara: codigoLiquidacion
                }];

                simpleUpdateInDB(
                    '../06-funciones_php/funciones.php', // URL del servidor
                    'liquidaciones',                    // Tabla a actualizar
                    arraySet,                            // Datos a actualizar
                    arrayWhere                           // Condición de actualización
                ).then(response => {
                    //console.log("Respuesta del servidor:", response);
                    sAlertAutoClose("success", "LIQUIDACIÓN ACTUALIZADA", "<H5>La liquidación se actualizo con éxito.</H5>", 1800);
                }).catch(error => {
                    //console.error("Error al actualizar la liquidación:", error);
                    sAlertAutoClose("error", "OCURRIO UN ERROR", "<H5>No se pudo actualizar la liquidación. Intenta nuevamente.</H5>", 1800);
                });
            }
        break;

        case 'cerrarModalLiquidacion':
            // Limpiar los campos de la Primera Quincena (Q1)
            $('#valorPrimeraQuincena').val('');
            $('#liquidadoPrimeraQuincena').val('');
            $('#viaticosPrimeraQuincena').val('');
            $('#otrosMas1PrimeraQuincena, #otrosMas2PrimeraQuincena, #otrosMas3PrimeraQuincena, #otrosMas4PrimeraQuincena').val('');
            $('#otrosMenos1PrimeraQuincena, #otrosMenos2PrimeraQuincena, #otrosMenos3PrimeraQuincena, #otrosMenos4PrimeraQuincena').val('');

            // Limpiar los campos de la Segunda Quincena (Q2)
            $('#valorSegundaQuincena').val('');
            $('#liquidadoSegundaQuincena').val('');
            $('#viaticosSegundaQuincena').val('');
            $('#variosMas1SegundaQuincena, #variosMas2SegundaQuincena, #variosMas3SegundaQuincena, #variosMas4SegundaQuincena').val('');
            $('#variosMenos1SegundaQuincena, #variosMenos2SegundaQuincena, #variosMenos3SegundaQuincena, #variosMenos4SegundaQuincena').val('');

            // Limpiar los campos de Liquidación Mensual (ME)
            $('#valorMensual').val('');
            $('#liquidadoMensual').val('');
            $('#viaticosMensual').val('');
            $('#variosMas1Mensual, #variosMas2Mensual, #variosMas3Mensual, #variosMas4Mensual').val('');
            $('#variosMenos1Mensual, #variosMenos2Mensual, #variosMenos3Mensual, #variosMenos4Mensual').val('');

            // Cerrar el modal
            $('#modalPago').modal('hide');
        break;
  





    } // cierre del switch

} // cierre de la funcion

// Al abrir el modal
$('#modalPago').on('show.bs.modal', function () {
    $('#valorPrimeraQuincena').val('');
    $('#valorSegundaQuincena').val('');
    $('#valorMensual').val(''); // <-- Agregado
});

// Al cerrar el modal
$('#modalPago').on('hide.bs.modal', function () {
    $('#valorPrimeraQuincena').val('');
    $('#valorSegundaQuincena').val('');
    $('#valorMensual').val(''); // <-- Agregado
});

// Declarar funciones fuera del case
function obtenerYear() {
    const textoFecha = $('#currentMonthYear').text().trim(); // Ej: "Octubre 2024"
    const partes = textoFecha.split(' '); // ["Octubre", "2024"]
    return partes[1]; // Año
}

function obtenerMonth() {
    const textoFecha = $('#currentMonthYear').text().trim();
    const partes = textoFecha.split(' ');
    const nombreMes = partes[0].toLowerCase(); // "octubre"

    // Conversión de nombre de mes a número
    const meses = {
        enero: '01',
        febrero: '02',
        marzo: '03',
        abril: '04',
        mayo: '05',
        junio: '06',
        julio: '07',
        agosto: '08',
        septiembre: '09',
        octubre: '10',
        noviembre: '11',
        diciembre: '12'
    };

    return meses[nombreMes] || '00'; // Retorna "00" si no encuentra coincidencia
}


function obtenerCodigo(){
  var tipoLiquidacion = $('#contenedorPrimeraQuincena').is(':visible') ? 'Q1' :
                        $('#segundaQuincena').is(':visible') ? 'Q2' : 'ME';

  // Obtener el ID del usuario
  var idUsuario = $('#guardaLiquidacion').data('id'); 

  // Obtener año y mes
  var anio = $('#guardaLiquidacion').data('year');
  var mes = $('#guardaLiquidacion').data('month');

  // Generar el código
  return codigoLiquidacion = `${idUsuario}-${anio}-${mes}-${tipoLiquidacion}`;
}

function recuperarLiquidacion(codigoLiquidacion) {
    existInDB(
        '../06-funciones_php/funciones.php',
        'existInDB',
        'liquidaciones',
        'liq_codigo',
        codigoLiquidacion,
        ''
    ).then(response => {
        if (response.status !== false) {
            //console.log('Datos encontrados:', response);

            let tipo = '';
            if (codigoLiquidacion.includes('-Q1')) tipo = "PrimeraQuincena";
            else if (codigoLiquidacion.includes('-Q2')) tipo = "SegundaQuincena";
            else if (codigoLiquidacion.includes('-ME')) tipo = "Mensual";

            if (tipo) {
                // Llenar los campos con los valores de la BD
                llenarCamposLiquidacion(response, tipo);

            // Solo recalcular importes en Q1 y Q2 (NO en mensual)
            if (tipo === "PrimeraQuincena") {
                actualizarImportes(tipo);
                recalcularTotalPrimeraQuincena(); // Asegurar cálculo correcto
            } else if (tipo === "SegundaQuincena") {
                actualizarImportes(tipo);
                recalcularTotalSegundaQuincena(); // Asegurar cálculo correcto
            } else if (tipo === "Mensual") {
                recalcularTotalMensual(); // Para mensual, solo se recalcula el total
            }

                sAlertAutoClose("success", "LIQUIDACIÓN CARGADA", "<H5>Se obtuvieron datos registrados para esta liquidación.</H5>", 1900);
            }
            $('#guardaLiquidacion').data('tipoGuardado','update');
        } else {
            $('#guardaLiquidacion').data('tipoGuardado','insert');
        }
    }).catch(error => {
        sAlertAutoClose("error", "ERROR AL RECUPERAR LIQUIDACIÓN", "<H5>Intentelo nuevamente.</H5>", 1800);
    });
}

/**
 * Recalcula la columna "Importe" en la tabla de liquidación (Q1 o Q2) después de recuperar datos.
 * @param {String} tipo - Puede ser 'PrimeraQuincena' o 'SegundaQuincena'
 */
function actualizarImportes(tipo) {
    let tablaId = tipo === "PrimeraQuincena" ? "#tablaPrimeraQuincena" : "#tablaSegundaQuincena";
    let valorJornal = parseFloat($(`#valor${tipo}`).val()) || 0;

    let totalImporte = 0;

    $(tablaId).find("tbody tr").each(function () {
        let cantidad = parseFloat($(this).find("td:eq(1)").text()) || 0; // Columna Cantidad
        let jornales = parseFloat($(this).find("td:eq(2)").text()) || 0; // Columna Jornales

        // Multiplicar jornales por cantidad en lugar de factor
        let importe = jornales * valorJornal;

        $(this).find("td:eq(3)").text(importe.toFixed(2)); // Columna Importe
        totalImporte += importe;
    });

    // Actualizar el subtotal de importes
    let totalImporteId = tipo === "PrimeraQuincena" ? "totalImportePrimera" : "totalImporteSegunda";
    $(`#${totalImporteId}`).text(totalImporte.toFixed(2));
}

function llenarCamposLiquidacion(data, tipo) {
    let prefijo = tipo === "PrimeraQuincena" ? "PrimeraQuincena" :
                  tipo === "SegundaQuincena" ? "SegundaQuincena" : 
                  "Mensual";

    // Asignar valores a los campos correspondientes
    $(`#valor${prefijo}`).val(parseFloat(data.liq_valor_jm).toFixed(2));
    $(`#liquidado${prefijo}`).val(parseFloat(data.liq_liquidado).toFixed(2));
    $(`#viaticos${prefijo}`).val(parseFloat(data.liq_viaticos).toFixed(2));

    // Ajustar nombres de ID para "Varios (+)" y "Varios (-)" en la Primera Quincena
    let prefixMas = (tipo === "PrimeraQuincena") ? "otrosMas" : "variosMas";
    let prefixMenos = (tipo === "PrimeraQuincena") ? "otrosMenos" : "variosMenos";

    // Llenar los campos de "Varios (+)" y "Varios (-)"
    for (let i = 1; i <= 4; i++) {
        let variosMas = parseFloat(data[`liq_masvarios${i}`]) || 0.00;
        let variosMenos = parseFloat(data[`liq_menovarios${i}`]) || 0.00;

        $(`#${prefixMas}${i}${prefijo}`).val(variosMas.toFixed(2));
        $(`#${prefixMenos}${i}${prefijo}`).val(variosMenos.toFixed(2));
    }

    // Actualizar el total del modal
    actualizarTotalLiquidacion(prefijo);
}


function actualizarTotalLiquidacion(tipo) {
    let total = 0;

    // Convertir valores numéricos de Liquidado y Viáticos
    let liquidado = parseFloat($(`#liquidado${tipo}`).val()) || 0;
    let viaticos = parseFloat($(`#viaticos${tipo}`).val()) || 0;

    // Inicializar el total con liquidado y viáticos
    total += liquidado + viaticos;

    // Sumar "Varios (+)"
    for (let i = 1; i <= 4; i++) {
        let valor = parseFloat($(`#otrosMas${i}${tipo}`).val()) || 0;
        total += valor;
    }

    // Restar "Varios (-)"
    for (let i = 1; i <= 4; i++) {
        let valor = parseFloat($(`#otrosMenos${i}${tipo}`).val()) || 0;
        total -= valor;
    }

    console.log(`Total actualizado para ${tipo}:`, total);

    // Asignar el total calculado
    $(`#total${tipo}`).val(total.toFixed(2));
}
