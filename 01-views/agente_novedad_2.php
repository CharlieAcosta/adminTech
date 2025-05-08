<?php  
// file agente_novedades_2.php

session_start();//
include_once '../06-funciones_php/funciones.php'; //funciones últiles
sesion(); //Verifica si hay usuario sesionado

include_once '../06-funciones_php/auditoria.php';
registrarNavegacion('NOVEDADES - Agente');

if ($_SERVER['SERVER_NAME'] == 'admintech.ar') {
    $_SESSION["base_url"] = 'https://admintech.ar/';
} else {
    $_SESSION["base_url"] = 'http://127.0.0.1/adminTech/';
}
define('BASE_URL', $_SESSION["base_url"]);

$novedadCodigos = db_select_with_filters_V2('novedad_codigos_2', [], [], [], $orderBy = [['orden', 'ASC']]);

include_once '../03-controller/novedadesController.php'; //conecta a la base de datos
$id_agente  = $_GET['id_agente'];
$year = $_GET['year'];
$month = $_GET['month'];
$yearMonth = $year.'-'.$month; 
//dd($yearMonth);
$eventos = json_encode(poblarCalendarByIdyMes($id_agente, $yearMonth,'php'));
//dd($eventos);

$feriados = db_select_with_filters_V2('feriados');

$feriados_js = [];

foreach ($feriados as $feriado) {
    $feriados_js[] = [$feriado['fecha'], $feriado['descripcion']];
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
      <meta name="robots" content="noindex">
      <meta name="googlebot" content="noindex">
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>ADMINTECH | Novedades de personal</title>

<!-- start - librerias comunes -->

      <!-- Google Font: Source Sans Pro -->
      <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
      <!-- Font Awesome -->
      <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
      <!-- SweetAlert2 -->
      <link rel="stylesheet" href="../05-plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
      <!-- Theme style -->
      <link rel="stylesheet" href="../dist/css/adminlte.min.css">
      <link rel="stylesheet" href="../dist/css/custom.css">


<!-- start - librerias especificas -->



</head>

<body class="hold-transition sidebar-collapse layout-navbar-fixed bg-yellow-soft">
<div class="wrapper">

  <!-- Navbar -->

  <!-- /.navbar -->
  <?php include '../01-views/layout/navbar_layout.php';?> 
  <!-- Main Sidebar Container -->

<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper" style="display: grid;">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-12 d-flex justify-content-between">
            <div><h1><strong>Novedades del agente</strong></h1></div>
            <div><h1 class="text-primary"><strong><?= " ".$_GET['agente']; ?></strong></h1><div>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-3">
            <div class="sticky-top mb-3">
                <div class="card novedades-card">
                  <div class="card-body p-2">
                    <!-- the events -->
                    <div id="external-events" class="overflow-auto">
                      <?php 
                        foreach ($novedadCodigos as $keyCodigos => $valueCodigos) {
                          echo '<div class="external-event '.$valueCodigos["css"].' ui-draggable ui-draggable-handle" data-codigo_novedad="'.$valueCodigos["codigo"].'">'.$valueCodigos["descripcion"].'</div>';
                        }
                      ?>
                  </div>
                </div>
                <!-- /.card-body -->
              </div>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-md-9">
            <div class="card card-primary">
              <div class="card-body p-0">
                <!-- THE CALENDAR -->
                <div class="p-1" id="calendar"></div>
              </div>
              <!-- /.card-body -->
              <!-- Aquí añades los botones centrados -->
              <div class="row d-flex justify-content-center mt-1">
                  <button type="button" class="col-2 btn btn-danger m-2" id="btnLimpiar"><i class="fa fa-times-circle"></i> Limpiar</button>
                  <button type="button" class="col-2 btn btn-light-green m-2" id="btnMensualizar"><i class="fa fa-calendar-alt"></i> Mensualizar</button>
              </div>
            </div>
            <!-- /.card -->
                <div class="row d-flex text-center justify-content-end pr-1">
                  <button type="submit" class="col-1 btn btn-primary btn-block m-2 v-accion" data-accion="guardar"><i class="fa fa-plus-circle"></i> Guardar</button>
                  <button type="button" class="col-1 btn btn-warning btn-block m-2 v-accion-cancelar" data-accion="cancelar"><i class="fa fa-ban"></i> Cancelar</button>
                  <button type="button" class="col-1 btn btn-success btn-block m-2 v-visual v-accion-link" data-accion="linkNovedades"><i class="fa fa-arrow-circle-left"></i> Volver</button>
                  <button type="button" class="col-1 btn btn-danger btn-block m-2 v-edit v-accion-eliminar d-none" data-accion="eliminar"><i class="fa fa-trash"></i> Eliminar</button>
                </div>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
<!-- /.content-wrapper -->
  <?php include '../01-views/layout/footer_layout.php';?>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="../05-plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap -->
<script src="<?php echo BASE_URL.'05-plugins/bootstrap/js/bootstrap.bundle.min.js'?>"></script>
<!-- jQuery UI -->
<script src="<?php echo BASE_URL.'05-plugins/jquery-ui/jquery-ui.min.js'?>"></script>
<!-- AdminLTE App -->
<script src="<?php echo BASE_URL.'dist/js/adminlte.min.js'?>"></script>
<!-- fullCalendar -->
<script src="<?php echo BASE_URL.'05-plugins/moment/moment.min.js'?>"></script>
<script src="<?php echo BASE_URL.'05-plugins/fullcalendar/index.global.min.js'?>"></script>
<!-- Page specific script -->
<script src="../05-plugins/sweetalert2/sweetalert2.min.js"></script>
<!-- Customs -->
<script src="../07-funciones_js/ayudaCalendarioNovedades.js"></script>
<script>
// variables globales para el calendario
var eventosNormIni = {}; // array que normaliza los eventos al renderizar o cargar por primera vez la vista
var eventosNormFin = {}; // array que normaliza los eventos que se van a guardar en la base de datos
window.meses = []; // array que gurda los años para la navegacion
var eventoCapturado = ''; // guarda el evento para un posterior dispacht
var flagEventosMod = false; // bandera que registra modificaciones de evento
var flagBotonNext = false;
var viewCurrentMonth = ''; // el mes de la vista actual del calendario
var viewCurrentYear = ''; // el año de la vista actual del calendario
var viewYearMonth = ''  // la union de las dos variables anteriores
var idAgente = "<?php echo $id_agente; ?>"; // id del agente que se esta visualizando
// Convertimos el array PHP a formato JSON y lo imprimimos para incluirlo en JavaScript
<?php echo "var feriados = " . json_encode($feriados_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";";?>

// Obtén el año y el mes desde PHP
var year = "<?php echo $year; ?>";  // Obtiene el año desde el PHP
var month = "<?php echo $month; ?>";  // Obtiene el mes desde el PHP (puede ser 1-12)

// Ajustar el mes para que FullCalendar lo entienda correctamente (de 0 a 11)
var initialMonth = month - 1;  // Restamos 1 al mes, ya que FullCalendar trabaja con meses de 0-11

    /* initialize the external events
     -----------------------------------------------------------------*/
    function ini_events(ele) {
      ele.each(function () {

        // create an Event Object (https://fullcalendar.io/docs/event-object)
        // it doesn't need to have a start or end
        var eventObject = {
          title: $.trim($(this).text()) // use the element's text as the event title
        }

        // store the Event Object in the DOM element so we can get to it later
        $(this).data('eventObject', eventObject)

        // make the event draggable using jQuery UI
        $(this).draggable({
          zIndex        : 1070,
          revert        : true, // will cause the event to go back to its
          revertDuration: 0,  //  original position after the drag
          scroll: false  // Desactiva el scroll automático cuando arrastras un elemento fuera del contenedor    
        })

      })
    }

    ini_events($('#external-events div.external-event'))

    /* initialize the calendar
     -----------------------------------------------------------------*/
    //Date for the calendar events (dummy data)
    var date = new Date()
    var d    = date.getDate(),
        m    = date.getMonth(),
        y    = date.getFullYear()

    var Calendar = FullCalendar.Calendar;
    var Draggable = FullCalendar.Draggable;

    var containerEl = document.getElementById('external-events');
    var checkbox = document.getElementById('drop-remove');
    var calendarEl = document.getElementById('calendar');
    // initialize the external events
    // -----------------------------------------------------------------

    new Draggable(containerEl, {
      itemSelector: '.external-event',
      eventData: function(eventEl) {
        return {
          title: eventEl.innerText,
          backgroundColor: window.getComputedStyle( eventEl ,null).getPropertyValue('background-color'),
          borderColor: window.getComputedStyle( eventEl ,null).getPropertyValue('background-color'),
          textColor: window.getComputedStyle( eventEl ,null).getPropertyValue('color'),
          allDay: true,
          className: 'text-center h6',
          display: 'background',
          codigo: $(eventEl).data('codigo_novedad'),
        };
      }
    });

    var today = new Date();
    var firstDayOfCurrentMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    // declaración del calendario ////////////////////////////////////////////////////////////////////
    var calendar = new FullCalendar.Calendar(calendarEl, {
      droppable: true, // permite que se puedan soltar eventos
      headerToolbar: {
        left  : 'prev',
        center: 'title',
        right : 'next'
      },
      buttonText: {
        prev: "Mes anterior",
        next: "Mes siguiente",
      },
      initialDate: new Date(year, initialMonth, 1),  // Define la fecha inicial del calendario
      eventConstraint: {
        start: '00:00', // restrict events to be all day
        end: '24:00',
        //daysOfWeek: [1,2,3,4,5,6,7]
      },
      themeSystem: 'bootstrap',
      datesSet: function(info) {
          var currentDate = new Date();
          var nextButton = document.querySelector('.fc-next-button');
          var view = info.view; // obtenemos el objeto view

          // Calculamos el primer día del mes siguiente al actual
          var nextMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 2, 1);

          // Depuración en consola
          //console.log("Datos");
          //console.log("info.end:", info.end, "| Fecha límite (primer día del mes siguiente):", nextMonth);

          // Permitir avanzar hasta el mes siguiente, pero no más allá
          if (info.end < nextMonth) {
              nextButton.disabled = false; // habilitar botón "Next"
          } else {
              nextButton.disabled = true; // deshabilitar botón "Next"
          }

          // Actualización de las variables de mes y año
          viewCurrentMonth = view.currentStart.getMonth() + 1; // Los meses en JavaScript van de 0 a 11
          viewCurrentYear = view.currentStart.getFullYear();

          // Formateo del mes para tener siempre dos dígitos
          if (viewCurrentMonth < 10) {
              viewCurrentMonth = '0' + viewCurrentMonth;
          }

          viewYearMonth = viewCurrentYear + '-' + viewCurrentMonth;

          // Actualización de la URL sin recargar la página
          history.replaceState(null, '', '?id_agente=' + idAgente + '&agente=' + encodeURIComponent("<?php echo $_GET['agente']; ?>") + '&year=' + viewCurrentYear + '&month=' + viewCurrentMonth);

          // Llenamos eventosNormIni con el estado actual del mes visible
          eventos(eventosNormIni, mes='');
      },
      events: <?php echo $eventos; ?>,
      eventReceive: function(event) {
          var fechaDrop = event.event.start;
          var diaSemana = fechaDrop.getDay(); // 0 = Domingo, 1 = Lunes, ..., 6 = Sábado
          var codigoNovedad = event.event.extendedProps.codigo.toLowerCase(); // Convertir el código a minúsculas
          var mensajeError = ''; // Variable para almacenar el mensaje de error
          var esFeriado = feriados.some(function(feriado) {
              return feriado[0] === fechaDrop.toISOString().split('T')[0]; // Verifica si la fecha coincide con algún feriado
          });

          // Verificar restricciones según el código de la novedad
          if (codigoNovedad === 'pressa' || codigoNovedad === 'mejosa') {
              if (diaSemana !== 6 || esFeriado) {
                  mensajeError = `La novedad (${codigoNovedad.toUpperCase()}) solo puede ser ubicada en sábados que no sean feriados.`;
              }
          } else if (codigoNovedad === 'presdo' || codigoNovedad === 'mejodo') {
              if (diaSemana !== 0) {
                  mensajeError = `La novedad (${codigoNovedad}) solo puede ser ubicada en días domingos.`;
              }
          } else if (codigoNovedad === 'ausesd') {
              if (!(diaSemana === 6 || diaSemana === 0) || esFeriado) {
                  mensajeError = `La novedad (${codigoNovedad.toUpperCase()}) solo puede ser ubicada en sábados o domingos que no sean feriados.`;
              }
          } else if (codigoNovedad === 'presfe' || codigoNovedad === 'feri') {
              if (!(diaSemana >= 1 && diaSemana <= 5 && esFeriado)) {
                  mensajeError = `La novedad (${codigoNovedad.toUpperCase()}) solo puede ser ubicada en días de semana que sean feriados.`;
              }
          } else if (codigoNovedad === 'presafe' || codigoNovedad === 'mejosafe') {
              if (!(diaSemana === 6 && esFeriado)) {
                  mensajeError = `La novedad (${codigoNovedad.toUpperCase()}) solo puede ser ubicada en sábados que sean feriados.`;
              }
          } else if (codigoNovedad === 'predofe' || codigoNovedad === 'mejosafe') {
              if (!(diaSemana === 0 && esFeriado)) {
                  mensajeError = `La novedad (${codigoNovedad.toUpperCase()}) solo puede ser ubicada en domingos que sean feriados.`;
              }
          } else if (codigoNovedad === 'ausefe') {
              if (!esFeriado) {
                  mensajeError = `La novedad (${codigoNovedad.toUpperCase()}) solo puede ser ubicada en días feriados.`;
              }
          } else if (codigoNovedad === 'mejo' || codigoNovedad === 'pres' || codigoNovedad === 'dill') {
              if (diaSemana === 0 || diaSemana === 6 || esFeriado) {
                  mensajeError = `La novedad (${codigoNovedad.toUpperCase()}) solo puede ser ubicada en días de semana (lunes a viernes) que no sean feriados.`;
              }
          } else if (codigoNovedad === 'bade' || codigoNovedad === 'bare') {
              // Restricción para BADE y BARE: Solo en días de semana (lunes a viernes), incluso si son feriados
              if (diaSemana === 0 || diaSemana === 6) {
                  mensajeError = `La novedad (${codigoNovedad.toUpperCase()}) solo puede ser ubicada en días de semana (lunes a viernes).`;
              }
          } else if (['dies', 'cile', 'emea', 'dona', 'muda', 'casa', 'exam', 'nahi'].includes(codigoNovedad)) {
              // Restricción para DIES, CILE, EMEA, DONA, MUDA, CASA, EXAM, NAHI: Solo en días de semana (lunes a viernes) que no sean feriados
              if (diaSemana === 0 || diaSemana === 6 || esFeriado) {
                  mensajeError = `La novedad (${codigoNovedad.toUpperCase()}) solo puede ser ubicada en días de semana (lunes a viernes) que no sean feriados.`;
              }
          } else if (codigoNovedad === 'fafa') {
          // Solo se puede agregar de lunes a viernes que no sean feriados
            if (diaSemana === 0 || diaSemana === 6 || esFeriado) {
                mensajeError = `La novedad (${codigoNovedad.toUpperCase()}) solo puede ser ubicada en días de semana (lunes a viernes) que no sean feriados.`;
            }
          } else if (codigoNovedad === 'auca' || codigoNovedad === 'ausa') {
            // Solo permitir de lunes a viernes y que no sean feriados
            if (diaSemana === 0 || diaSemana === 6 || esFeriado) {
                mensajeError = `La novedad (${codigoNovedad.toUpperCase()}) solo puede ser ubicada en días de semana (lunes a viernes) que no sean feriados.`;
            }
          } else if (codigoNovedad === 'mejofe') {
              // Solo permitir de lunes a viernes y que sean feriados
              if (diaSemana < 1 || diaSemana > 5 || !esFeriado) {
                  mensajeError = `La novedad (${codigoNovedad.toUpperCase()}) solo puede ser ubicada en días de semana (lunes a viernes) que sean feriados.`;
              }
          } else if (codigoNovedad === 'mejodofe') {
              // Solo permitir en domingos que sean feriados
              if (diaSemana !== 0 || !esFeriado) {
                  mensajeError = `La novedad (${codigoNovedad.toUpperCase()}) solo puede ser ubicada en domingos que sean feriados.`;
              }
          } else if (codigoNovedad === 'auen' || codigoNovedad === 'auar') {
              // Solo permitir de lunes a viernes y que no sean feriados
              if (diaSemana === 0 || diaSemana === 6 || esFeriado) {
                  mensajeError = `La novedad (${codigoNovedad.toUpperCase()}) solo puede ser ubicada en días de semana (lunes a viernes) que no sean feriados.`;
              }
          }

          // Si hay un mensaje de error, eliminar el evento y mostrar la alerta
          if (mensajeError !== '') {
              event.event.remove(); // Eliminar el evento del calendario
              Swal.fire({
                  title: mensajeError,
                  icon: 'warning',
                  confirmButtonText: 'Entendido'
              });
              return; // Detener el procesamiento si no cumple con las restricciones
          }

          // Si el evento cumple con las restricciones, continuar con la lógica existente

          var nextButton = document.querySelector('.fc-next-button');
          nextButton.disabled = true; // deshabilitar botón "Next"
          var prevButton = document.querySelector('.fc-prev-button');
          prevButton.disabled = true; // deshabilitar botón "Prev"
          flagEventosMod = true;

          // Obtener todos los eventos de la fecha
          var eventosFecha = calendar.getEvents().filter(function(evento) {
              return evento.start.toISOString().substring(0, 10) === fechaDrop.toISOString().substring(0, 10);
          });

          // Filtrar los eventos que no son el último evento que se ha soltado
          var eventosAEliminar = eventosFecha.filter(function(evento) {
              return evento._instance.defId !== event.event._instance.defId;
          });

          // Eliminar los eventos duplicados de la misma fecha
          eventosAEliminar.forEach(function(evento) {
              evento.remove();
          });
      },

      eventClick: function(evento) {
        Swal.fire({
            title: 'ESTAS A PUNTO DE ELIMINAR UNA NOVEDAD',
            text: "",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Si, eliminar',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false // Desactiva el cierre por click afuera y por el botón de escape
          }).then((result) => {
            if (result.isConfirmed) {
              evento.event.remove();  
              var nextButton = document.querySelector('.fc-next-button');
              nextButton.disabled = true; // deshabilitar botón "Next"
              var prevtButton = document.querySelector('.fc-prev-button');
              prevtButton.disabled = true; // deshabilitar botón "Next"
              flagEventosMod = true;
                Swal.fire({
                  title: 'NOVEDAD ELIMINADA',
                  icon: 'success',
                  allowOutsideClick: false, // Desactiva el cierre por click afuera y por el botón de escape
                  timer: 1200,
                  timerProgressBar: true,
                  showConfirmButton: false,
                });
            }
          })
      },
      locale: 'es',
      editable  : true,
      droppable : true, // this allows things to be dropped onto the calendar !!!
      contentHeight: 600,
      fixedWeekCount: false,
      selectOverlap: false,
      showNonCurrentDates: false,

    // Aseguramos que la celda tenga la posición relativa
    dayCellDidMount: function(arg) {
        // Establecer la posición relativa en todas las celdas
        arg.el.style.position = 'relative';

        // Buscar si hay un feriado en esta fecha
        var feriado = feriados.find(f => f[0] === arg.date.toISOString().split('T')[0]);

        if (feriado) {
            // Dejar el color del número de la fecha en gris
            arg.el.querySelector('.fc-daygrid-day-number').style.color = '';

            // Crear el elemento "small" para mostrar el texto del feriado en la parte superior izquierda
            var span = document.createElement('small');
            span.innerText = `FERIADO: ${feriado[1].toUpperCase()}`;
            span.style.position = 'absolute';
            span.style.top = '2px';  // Parte superior
            span.style.left = '2px'; // Esquina izquierda
            span.style.fontSize = '10px';
            span.style.color = 'orange';  // Texto en negro
            span.style.fontWeight = 'bold';  // Texto en negritas
            span.style.backgroundColor = 'rgba(255,255,255,0.8)';
            span.style.padding = '1px 3px';

            // Añadir el span a la celda
            arg.el.appendChild(span);
        }
    },

    eventDidMount: function(info) {
      if (info.event.extendedProps.codigo && info.event.extendedProps.codigo.toLowerCase() === 'mejo') {
        // Añadir clase especial solo para los eventos con código "mejo"
        info.el.classList.add('mejo-event');
      }

      if (info.event.extendedProps.codigo && info.event.extendedProps.codigo.toLowerCase() === 'pres') {
        // Añadir clase especial solo para los eventos con código "pres"
        info.el.classList.add('pres-event');
      }
      
    }

    });

    calendar.render();
    window.meses.push(viewYearMonth);
    agregaData('.fc-prev-button', 'data-accion', 'prevNextButton');
    agregaData('.fc-prev-button', 'data-id-agente', idAgente);  
    agregaData('.fc-next-button', 'data-accion', 'prevNextButton');
    agregaData('.fc-next-button', 'data-id-agente', idAgente);

    //eventos(eventosNormIni, mes=''); // inicializa el array de eventos con los eventos traidos de la base


    /* ADDING EVENTS */
    var currColor = '#3c8dbc' //Red by default
    // Color chooser button
    $('#color-chooser > li > a').click(function (e) {
      e.preventDefault()
      // Save color
      currColor = $(this).css('color')
      // Add color effect to button
      $('#add-new-event').css({
        'background-color': currColor,
        'border-color'    : currColor
      })
    })
    $('#add-new-event').click(function (e) {
      e.preventDefault()
      // Get value and make sure it is not null
      var val = $('#new-event').val()
      if (val.length == 0) {
        return
      }

      // Create events
      var event = $('<div />')
      event.css({
        'background-color': currColor,
        'border-color'    : currColor,
        'color'           : '#fff'
      }).addClass('external-event')
      event.text(val)
      $('#external-events').prepend(event)

      // Add draggable funtionality
      ini_events(event)

      // Remove event from text input
      $('#new-event').val('')
    })
      
  // start - funcion para obtener los eventos del mes parametrizado segun acciones: iniciar, guardar
function eventos(objeto, mes) {
    // Limpiar el objeto
    for (var prop in objeto) {
        if (objeto.hasOwnProperty(prop)) {
            delete objeto[prop];
        }
    }

    // Obtener la fecha de inicio y fin del mes visualizado
    var viewDate = calendar.getDate();
    var firstDay = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);
    var lastDay = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 0);

    // Obtener los eventos del mes visualizado
    var events = calendar.getEvents().filter(function(event) {
        return (event.start >= firstDay && event.start <= lastDay);
    });

    // Llenar el objeto con los eventos actuales
    $.each(events, function(index, value) {
        var fecha = value['startStr'];
        objeto[fecha] = {
            'idNovedadPer': value['id'],
            'idUsuario': idAgente,
            'NovedadCodigo': value['extendedProps']['codigo'],
            'accion': 'insert'
        };
    });
}


// end - funcion para obtener los eventos del mes parametrizado segun acciones: iniciar, guardar

// start - guarda todos los eventos de la vista actual del calendario
function guardarEventos(){
    // Actualizar eventosNormFin con los eventos actuales del calendario
    eventos(eventosNormFin, mes='');

    $.each(eventosNormIni, function(index, value) {   
        if(eventosNormFin[index] === undefined){
            eventosNormFin[index] = value; // Copiar el evento eliminado
            eventosNormFin[index].accion = 'delete'; // Marcarlo para eliminación
        } else {
            if(eventosNormFin[index].idNovedadPer == ''){
                eventosNormFin[index].accion = 'update';
                eventosNormFin[index].idNovedadPer  =  value.idNovedadPer;        
            } else {
                eventosNormFin[index].accion = '-';
            }
        } 
    });

    // Enviar eventosNormFin al servidor para guardar
    $.ajax({
        url: '../04-modelo/novedadesModel.php',
        type: "POST",
        dataType: 'json',
        data: {
            via: 'ajax',
            funcion: 'modGuardarEventos',
            eventos: eventosNormFin,
        },
        success: function(respuesta) {
            eventosNormIni = respuesta;
        },
        error: function(respuesta) {
            console.log(respuesta);
        }
    });

    eventosNormFin = {}; // Limpiar eventosNormFin para un próximo guardado
}

// end - guarda todos los eventos de la vista actual del calendario

// start - funcion agregar data a elemento del dom
function agregaData(elemento, dataNom, dataValor){
  $(elemento).attr(dataNom, dataValor);
}
// volvio - funcion agregar data a elemento del dom

//

document.addEventListener("DOMContentLoaded", function() {
    var calendarCard = document.querySelector('.calendar-card .card-body');
    var novedadesCard = document.querySelector('.novedades-card .card-body');

    if (calendarCard && novedadesCard) {
        var calendarHeight = calendarCard.offsetHeight;
        novedadesCard.style.minHeight = calendarHeight + 'px'; // Ajustar la altura mínima al card del calendario
        novedadesCard.style.overflowY = 'auto'; // Añadir scroll si el contenido excede la altura
    }

    var toolbarDiv = document.querySelector('.fc-header-toolbar.fc-toolbar.fc-toolbar-ltr');
    if (toolbarDiv) {
        toolbarDiv.classList.add('pl-2', 'pr-2', 'pt-1', 'pb-2', 'm-0');
    }
});


document.getElementById('btnLimpiar').addEventListener('click', function() {
    Swal.fire({
        title: '¿Estás seguro de que deseas limpiar el calendario?',
        text: "Esto eliminará todas las novedades actuales del mes",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            var events = calendar.getEvents();
            events.forEach(function(event) {
                event.remove(); // Eliminar cada evento del calendario
            });

            // Establecer la bandera de modificaciones a true
            flagEventosMod = true;

            // Swal.fire(
            //     '¡Eliminadas!',
            //     'Sequitar.',
            //     'success'
            // );
        }
    });
});


document.getElementById('btnMensualizar').addEventListener('click', function() {
    Swal.fire({
        title: '¿Estás seguro de que deseas mensualizar?',
        text: "Esto limpiará todas las novedades actuales y agregará presentes de lunes a viernes al mes que estás visualizando.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, mensualizar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Primero, limpiar todas las novedades actuales
            var events = calendar.getEvents();
            events.forEach(function(event) {
                event.remove(); // Eliminar cada evento del calendario
            });

            // Luego, mensualizar
            var viewDate = calendar.getDate(); // Obtener la fecha actual de la vista del calendario
            var firstDay = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1); // Primer día del mes de la vista
            var lastDay = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 0); // Último día del mes de la vista

            // Clonar la fecha para iterar sin modificar 'firstDay'
            var day = new Date(firstDay);

            while (day <= lastDay) {
                var diaSemana = day.getDay(); // 0 = Domingo, 1 = Lunes, ..., 6 = Sábado
                var fechaFormateada = day.toISOString().split('T')[0];

                // Si es lunes a viernes (1 a 5)
                if (diaSemana >= 1 && diaSemana <= 5) {
                    var esFeriado = feriados.some(function(feriado) {
                        return feriado[0] === fechaFormateada; // Verifica si la fecha coincide con algún feriado
                    });

                    if (esFeriado) {
                        // Si es feriado, agregar la novedad 'FERI' como evento
                        calendar.addEvent({
                            title: 'FERIADO (feri) (100%)',
                            start: fechaFormateada,
                            allDay: true,
                            backgroundColor: '#DDA0DD',
                            borderColor: '#DDA0DD',
                            textColor: '#ffffff',
                            className: 'feri-event',
                            extendedProps: {
                                codigo: 'feri' // Agregamos el código de la novedad
                            },
                            display: 'background' // Usar 'display' en lugar de 'rendering'
                        });
                    } else {
                        // Si no es feriado, agregar la novedad 'PRESENTE' como evento
                        calendar.addEvent({
                            title: 'PRESENTE (pres) (100%)',
                            start: fechaFormateada,
                            allDay: true,
                            backgroundColor: '#a8d5ba',
                            borderColor: '#a8d5ba',
                            textColor: '#000000',
                            className: 'pres-event',
                            extendedProps: {
                                codigo: 'pres' // Agregamos el código de la novedad
                            },
                            display: 'background' // Usar 'display' en lugar de 'rendering'
                        });
                    }
                }

                // Avanzar al siguiente día
                day.setDate(day.getDate() + 1);
            }

            // Indicar que hay modificaciones pendientes de guardar
            flagEventosMod = true;

            // Swal.fire(
            //     '¡Mensualizado!',
            //     'Las novedades han sido agregadas para todos los días de lunes a viernes del mes visualizado.',
            //     'success'
            // );
        }
    });
});


ayuda();


</script>
</body>
</html>
<script src="../07-funciones_js/novedadesAcciones.js"></script>