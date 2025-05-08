<?php  
// file: aeo_listado.php
session_start();
$usuarioLogueado = json_encode($_SESSION['usuario']);
$usuario_logueado = $_SESSION['usuario'];
define('BASE_URL', $_SESSION["base_url"]);
include_once '../06-funciones_php/funciones.php';
include_once '../06-funciones_php/convertirFecha.php';
include_once '../06-funciones_php/novedadAutomatica.php';
include_once '../06-funciones_php/procesoAEONovedades.php';
sesion(); //Verifica si hay usuario sesionado
//echo "<pre"; // FOR DEBUGG
//dd($usuarioLogueado); // FOR DEBUGG
//echo "</pre>"; // FOR DEBUGG

include_once '../06-funciones_php/auditoria.php';
registrarNavegacion('AEO - listado');


//*******************************************************************************************************

$feriados = db_select_with_filters_V2('feriados');

$feriados_js = [];

foreach ($feriados as $feriado) {
    $feriados_js[] = [$feriado['fecha'], $feriado['descripcion']];
}

$table = "usuarios";
$columns = ['estado','log_accion','perfil'];
$comparisons = ['<>','<>','<>'];
$values = ['Desactivado','eliminar','Super Administrador'];
$orderBy = [['apellidos', 'ASC'],['nombres','ASC']];
$selectColumns = ['id_usuario','apellidos', 'nombres'];
$agentes = db_select_with_filters($table, $columns, $comparisons, $values, $orderBy, $selectColumns);
//dd($agentes);

$leyenda = ""; // Texto deshabilitado en la primera opción
$valor = 'id_usuario';                       // Clave que será el value de las opciones
$texto = 'apellidos';                   // Clave que será el texto visible en las opciones
$separador = ' ';                  // Separador entre los valores concatenados
$concat_values = ['nombres']; // Array con los campos a concatenar
$optionForAgentesFilter = arrayToOptionsV2($agentes, $valor, $texto, $leyenda, $separador, $concat_values);
//dd($optionForAgentesFilter);

$table = "obras";
$columns = ['obra_estado','obra_log_accion'];
$comparisons = ['=','<>'];
$values = ['En curso','delete'];
$orderBy = [['obra_nombre', 'ASC']];
$selectColumns = ['obra_id','obra_nombre'];
$obras = db_select_with_filters($table, $columns, $comparisons, $values, $orderBy, $selectColumns);
//dd($obras);

$leyenda = ""; // Texto deshabilitado en la primera opción
$valor = 'obra_id';                       // Clave que será el value de las opciones
$texto = 'obra_nombre';                   // Clave que será el texto visible en las opciones
$separador = ' ';                  // Separador entre los valores concatenados
$concat_values = []; // Array con los campos a concatenar
$optionForObrasFilter = arrayToOptionsV2($obras, $valor, $texto, $leyenda, $separador, $concat_values);
//dd($optionForObrasFilter);

$table = "obras_asistencia";
$columns = ['obas_log_accion','obas_procesado'];
$comparisons = ['<>','='];
$values = ['delete',''];
$orderBy = [['obas_fecha', 'ASC'],['obas_id_usuario','ASC'],['obas_obra_id', 'ASC'],['obas_estado','ASC']];
$registros = db_select_with_filters($table, $columns, $comparisons, $values, $orderBy);
//dd($registros);

$registrosProcesados = novedadAutomatica($registros, $feriados_js);
//dd($registrosProcesados);

$registrosNovedades = $registrosProcesados['registrosParaNovedades'];
//dd($registrosNovedades);
$registros = $registrosProcesados['registrosParaAEO'];

try {
    // Forzar una excepción lanzando un error manualmente para probar el bloque catch
    //throw new Exception("Error simulado para probar el bloque catch");

    // Llamar a la función y capturar el número de inserciones
    $numeroInserciones = procesoAEONovedades($registrosNovedades, $usuario_logueado['id_usuario']);
    //$numeroInserciones = 25;

    // Solo mostrar la alerta si hay inserciones
    if ($numeroInserciones > 0) {
        // Preparar el mensaje de éxito con el número de inserciones
        $titulo = "<span class='text-success'>REGISTROS PROCESADOS<span>";
        $message = "<h4>Presentes registrados en novedades:</h4><h2><strong class='text-success'> $numeroInserciones </strong></h2></strong>";
        $alertType = "success"; // Tipo de alerta para SweetAlert
    } else {
        // Si no hay inserciones, evitar que el alertType y demás variables se definan
        $alertType = null; // No asignar el tipo de alerta
        $titulo = "";
        $message = ""; 
    }
} catch (Exception $e) {
    // Preparar el mensaje de error en caso de excepción
    $titulo = "<span class='text-danger'>OCURRIO UN ERROR<span>";
    $message = "Al intentar procesar los registros para novedades<br> ocurrió el siguiente error:<br><br><h4 class='text-danger'>".$e->getMessage()."</h4>Realice una captura de pantalla y envíesela al administrador.";
    $alertType = "error"; // Tipo de alerta para SweetAlert
}

$keysArray = [
    ['obas_id_usuario', 'usuarios', 'id_usuario', ['apellidos', 'nombres', 'nro_documento']],
    ['obas_obra_id', 'obras', 'obra_id', ['obra_nombre']]
];
$arrayJoineado = arrayJoin($registros, $keysArray);
//dd($arrayJoineado);

$arrayJoineado = sortArrayMultiple($arrayJoineado, ['obas_fecha' => 'asc','apellidos' => 'asc','obas_id_usuario' => 'asc','obas_obra_id' => 'asc','obas_estado' => 'asc']);
//dd($arrayJoineado);

$claves = [
'obas_fecha',
'nro_documento',  
'apellidos',
'nombres',  
'obas_estado',
'obas_obra_id',
'obra_nombre',
'obas_hora',
'horas',
'horas_total',
'novedad',
'obas_dispositivo'
];

$acciones = [
['fa-trash','fa-p','fa-a','fa-m'],
[
  ['v-icon-accion','v-accion-delete'],
  ['v-icon-accion','v-accion-presente','v-accion-hidden'],
  ['v-icon-accion','v-accion-ausente','v-accion-hidden'],
  ['v-icon-accion','v-accion-media-jor','v-accion-hidden']
],    
[
  [['id_registro', 'obas_id']],
  
  [['id_agente', 'obas_id_usuario'],['id_registro', 'obas_id'],['novedad', 'P'],['html', '<span class="text-success"><strong>PRESENTE</strong></span>'],['agente', 'apellidos',' ','nombres'],['fecha', 'obas_fecha']],
  
  [['id_agente', 'obas_id_usuario'],['id_registro', 'obas_id'],['novedad', 'A'],['html', '<span class="text-danger"><strong>AUSENTE</strong></span>'],['agente', 'apellidos',' ','nombres'],['fecha', 'obas_fecha']],
  
  [['id_agente', 'obas_id_usuario'],['id_registro', 'obas_id'],['novedad', 'M'],['html', '<span class="text-warning"><strong>MEDIA JORNADA</strong></span>'],['agente', 'apellidos',' ','nombres'],['fecha', 'obas_fecha']]
]
];

$clases = [
['aeo_fecha_class','text-center'],
['nro_documento_class','text-center'],
['aeo_apellido_class'],
['aeo_nombre_class'],
['aeo_estado_class', 'font-weight-bold'],
['aeo_obra_id_class','text-center'],
['aeo_obra_nombre_class'],
['aeo_hora_class','text-right'],
['horas_class','text-right','font-weight-bold'],
['horas_total_class','text-right', 'font-weight-bold'],
['novedad_class','text-center'],
['dispositivo_class','text-center'],
['aeo_acciones_class', 'text-left']
];

$trData = [['id_usuario','obas_id_usuario'],['estado','obas_estado'],['id_obra','obas_obra_id'],['fecha','obas_fecha'],['apellido','apellidos']];

$filas = registrosToFilas($arrayJoineado, $claves, $acciones, $clases, $trData);
//dd($filas);

//*********************************************************************************************************

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="robots" content="noindex">
  <meta name="googlebot" content="noindex">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ADMINTECH | AEO | Asistencia en obra</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../05-plugins/fontawesome-free/css/all.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="../05-plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../05-plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../dist/css/adminlte.min.css">
  <link rel="stylesheet" href="../dist/css/custom.css">
  <script src='../05-plugins/pdfmake/pdfmake.min.js'></script>
  <script src='../05-plugins/pdfmake/vfs_fonts.js'></script>
  <!-- Select2 -->
  <link rel="stylesheet" href="../05-plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../05-plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
</head>
<body class="hold-transition sidebar-collapse layout-navbar-fixed bg-yellow-soft">
<div class="wrapper">
  <!-- Navbar -->
  <!-- /.navbar -->
  <?php include '../01-views/layout/navbar_layout.php';?> 
  <!-- Main Sidebar Container -->
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-0">
          <div class="col-sm-6">
            <h1><strong>AEO | Asistencia en obra</strong></h1>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="mb-2">
                <div class="d-flex align-items-end justify-content-between flex-wrap">
                    <!-- Campo Fecha -->
                    <div class="flex-grow-1 mr-3">
                        <label for="fecha" class="mb-0">Fecha</label> <!-- Reducimos el margin-bottom -->
                        <div class="input-group">
                        <input type="date" class="form-control" id="fecha_filter" placeholder="dd/mm/aaaa" max='<?php echo fechaAjustada("AAAA-MM-DD", -1, "dias"); ?>' onkeydown="return false;">

                        </div>
                    </div>

                    <!-- Campo Agente -->
                    <div class="flex-grow-1 mr-3">
                        <label for="agente" class="mb-0">Agente</label> <!-- Reducimos el margin-bottom -->
                        <select id="agente_filter" class="form-control select2bs4">
                          <?php echo $optionForAgentesFilter; ?>
                        </select>
                    </div>

                    <!-- Campo Obra -->
                    <div class="flex-grow-1 mr-3">
                        <label for="obra" class="mb-0">Obra</label> <!-- Reducimos el margin-bottom -->
                        <select id="obra_filter" class="form-control select2bs4">
                            <option value="">Seleccionar Obra</option>
                             <?php echo $optionForObrasFilter; ?>
                        </select>
                    </div>

                    <!-- Botones -->
                    <div class="d-flex">
                        <button type="button" class="btn btn-primary mr-2" style="width: 120px;" onclick="historicoAEO('filter')">Filtrar</button>
                        <button type="button" class="btn btn-secondary" style="width: 120px;" onclick="historicoAEO('clear')">Limpiar</button>
                    </div>
                </div>
            </div>

            <div class="card">
              <!-- /.card-header -->
              <div class="card-body">
                <table id="current_table" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Nro. Documento</th>
                    <th>Apellido(s)</th>
                    <th>Nombre(s)</th>
                    <th>Estado</th>
                    <th>Cód.</th>
                    <th>Obra Nombre</th>
                    <th>Hora</th>
                    <th>Horas par.</th>
                    <th>Horas tot.</th>
                    <th>Novedad</th>
                    <th>Dispositivo</th>
                    <th>Acciones</th>
                  </tr>
                  </thead>
                  <tbody>
                    <!-- aquí datos dinamicos -->
                      <?php echo $filas; ?>
                    <!-- aquí datos dinamicos -->
                  </tbody>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
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
<!-- Bootstrap 4 -->
<script src="../05-plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="../05-plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../05-plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../05-plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../05-plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../05-plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../05-plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="../05-plugins/jszip/jszip.min.js"></script>
<script src="../05-plugins/pdfmake/pdfmake.min.js"></script>
<script src="../05-plugins/pdfmake/vfs_fonts.js"></script>
<script src="../05-plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../05-plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="../05-plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>
<!-- Sweet Alert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11?v=<?php echo time(); ?>"></script>
<!-- Select2 -->
<script src="../05-plugins/select2/js/select2.full.min.js"></script>
<script src="../05-plugins/select2/js/i18n/es.js"></script>
<!-- customs -->
<script src="../07-funciones_js/funciones.js"></script>
<script src="../07-funciones_js/actionByEvent.js"></script>
<script src="../07-funciones_js/getRowIndex.js"></script>
<script src="../07-funciones_js/actionDeleteRegistro.js"></script>
<script src="../07-funciones_js/convertirFecha.js"></script>
<script src="../07-funciones_js/actionNovedadProcesada.js"></script>
<script src="../07-funciones_js/db/db_select_with_filters_V2.js"></script>
<script src="../07-funciones_js/arrayJoin.js"></script>
<script src="../07-funciones_js/registroToFilas.js"></script>
<script src="../07-funciones_js/sAlertAutoCloseV2.js"></script>
<script src="../07-funciones_js/actualizarDataTableConFiltrados.js"></script>
<script src="../07-funciones_js/resetearDataTable.js"></script>
<script src="../07-funciones_js/historicoAEO.js"></script>
<script src="../07-funciones_js/ayudaAEO.js"></script>
<!-- Page specific script -->



<script>
var usuarioLogueado = <?php echo $usuarioLogueado; ?>;
var currentTable;
let registrosPresente = []; // Aquí almacenaremos los datos de fecha e 

// Convertimos el array PHP a formato JSON y lo imprimimos para incluirlo en JavaScript
<?php echo "var feriados = " . json_encode($feriados_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";";?>

$(function () {
ayuda();

// Definir la función de ordenación personalizada para fechas en formato DD-MM-YYYY
$.fn.dataTable.ext.type.order['date-dd-mm-yyyy-pre'] = function(d) {
    var parts = d.split('-');
    return new Date(parts[2], parts[1] - 1, parts[0]);
};


$("#current_table").DataTable({
    "dom": '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
    "responsive": true,
    "lengthChange": true,
    "autoWidth": false,
    "pageLength": 100,  // Mostrar 50 registros inicialmente
    "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
    "language": {"url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"},
    "columns": [
      { "width": "5%", "type": "date-dd-mm-yyyy" },  // Ajustar esta columna como fecha
      { "width": "7%" },
      { "width": "8%" }, 
      { "width": "9%" }, 
      { "width": "5%" }, 
      { "width": "3%" }, 
      { "width": "10%" }, 
      { "width": "5%" }, 
      { "width": "5%" },
      { "width": "5%" },
      { "width": "5%" },
      { "width": "5%" },
      { "width": "4%" }
    ],
    "order": [
        [0, 'asc'],
        [2, 'asc']
    ],
    "initComplete": function(settings, json) {
            // Guardar el HTML inicial del DataTable una vez que se haya completado la inicialización
            let initialTableHTML = $('#current_table tbody').html();
            // Asignarlo a una variable global para su uso posterior
            window.initialTableHTML = initialTableHTML;
    }
    }).buttons().container().appendTo('#current_table_wrapper .col-md-6:eq(0)');


    // Initialize Select2 Elements
    $('#agente_filter').select2({
        theme: 'bootstrap4',
        language: "es",
        width: '100%',
        placeholder: 'Seleccione un agente', // Placeholder para Select2
        allowClear: true // Permite borrar la selección
    });
    // Initialize Select2 Elements
    $('#obra_filter').select2({
        theme: 'bootstrap4',
        language: "es",
        width: '100%',
        placeholder: 'Seleccione una obra', // Placeholder para Select2
        allowClear: true // Permite borrar la selección
    });
});

document.addEventListener("DOMContentLoaded", function() {

processTableRows();

  actionByEvent(
  ['.v-accion-delete','.v-accion-presente','.v-accion-ausente','.v-accion-media-jor'],                      
  [['click'],['click'],['click'],['click']],                
  [
    [function(event, data){actionDeleteRegistro(data.id_registro, getRowIndex(event))}],
    [function(event, data){actionNovedadProcesada(getRowIndex(event), data.agente, data.html, data.fecha, data.novedad, data.id_agente)}], 
    [function(event, data){actionNovedadProcesada(getRowIndex(event), data.agente, data.html, data.fecha, data.novedad, data.id_agente)}],
    [function(event, data){actionNovedadProcesada(getRowIndex(event), data.agente, data.html, data.fecha, data.novedad, data.id_agente)}],
  ]        
  );

});


document.addEventListener("DOMContentLoaded", function() {
    // Solo mostrar la alerta si el tipo de alerta está definido (es decir, si no es null o undefined)
    if ("<?php echo $alertType; ?>" !== "") {
        sAlertConfirm(
          "<?php echo $alertType; ?>",
          "<?php echo $titulo; ?>",
          "<?php echo $message; ?>", 
          "OK",
          "#3085d6"
        );
    }
});

function processTableRows() {
    const rows = document.querySelectorAll("#current_table tbody tr");

    // Primera iteración: cálculo de horas parciales y aplicación de estilos según criterios.
    for (let i = 0; i < rows.length; i++) {
        const currentRow = rows[i];
        const userId = currentRow.getAttribute("data-id_usuario");
        const estado = currentRow.getAttribute("data-estado");

        if (i < rows.length - 1) {
            const nextRow = rows[i + 1];
            const nextUserId = nextRow.getAttribute("data-id_usuario");
            const nextEstado = nextRow.getAttribute("data-estado");

            if (userId === nextUserId) {
                if (estado === "Entrada" && nextEstado === "Salida") {
                    marcarComoExitoso(currentRow, nextRow);
                    calcularDiferenciaTiempo(currentRow, nextRow, true); // Formato unificado hh:mm
                    limpiarUltimoTd(currentRow);

                    i++; // Saltar al siguiente del siguiente              
                    continue;
                } else {
                    marcarComoError(currentRow, nextRow);
                }
            } else {
                marcarComoError(currentRow, nextRow);
            }
        } else {
            marcarComoError(currentRow);
        }
    }

    // Segunda iteración: cálculo de horas totales y aplicación de clases según criterio.
    const userEntries = {};

    for (let i = 0; i < rows.length; i++) {
        const currentRow = rows[i];
        const userId = currentRow.getAttribute("data-id_usuario");
        const fecha = currentRow.getAttribute("data-fecha");
        const estado = currentRow.getAttribute("data-estado");
        const horaTd = currentRow.querySelector(".aeo_hora_class"); // Referencia al <td> contenedor de la hora
        const hora = horaTd.innerText.trim();

        // Inicializar la entrada del usuario si no existe
        if (!userEntries[userId]) {
            userEntries[userId] = {};
        }

        // Si el usuario ya tiene una entrada para esa fecha
        if (!userEntries[userId][fecha]) {
            userEntries[userId][fecha] = {
                earliestEntry: null,
                latestExit: null,
                lastExitRow: null,
                lastRow: currentRow, // Guardar la última fila del usuario
                earliestEntryTd: null, // Guardar la referencia al <td> de la entrada más temprana
                latestExitTd: null, // Guardar la referencia al <td> de la salida más tardía
            };
        }

        const userEntry = userEntries[userId][fecha];

        // Actualizar la entrada más temprana y guardar la referencia al <td>
        if (estado === "Entrada") {
            if (!userEntry.earliestEntry || hora < userEntry.earliestEntry) {
                userEntry.earliestEntry = hora;
                userEntry.earliestEntryTd = horaTd; // Guardar la referencia al <td>
            }
        } 
        // Actualizar la salida más tardía y guardar la referencia al <td>
        else if (estado === "Salida") {
            if (!userEntry.latestExit || hora > userEntry.latestExit) {
                userEntry.latestExit = hora;
                userEntry.latestExitTd = horaTd; // Guardar la referencia al <td>
                userEntry.lastExitRow = currentRow; // Guardar la fila de la última salida
            }
        }

        // Actualizar la última fila del usuario
        userEntry.lastRow = currentRow;
    }

    // Aplicar la clase `font-weight-bold` a los <td> que contienen la entrada más temprana y la salida más tardía
    for (const userId in userEntries) {
        for (const fecha in userEntries[userId]) {
            const entry = userEntries[userId][fecha];
            
            if (entry.earliestEntryTd) {
                entry.earliestEntryTd.classList.add("font-weight-bold");
            }
            if (entry.latestExitTd) {
                entry.latestExitTd.classList.add("font-weight-bold");
            }

            // Si no existe una combinación válida de entrada y salida, asignar "Indeterminado"
            if (!entry.earliestEntry || !entry.latestExit) {
                const novedadCell = entry.lastRow.querySelector(".novedad_class"); // Última fila del usuario ese día
                novedadCell.innerText = "Indeterminado";
                novedadCell.classList.add("text-muted", "font-weight-bold"); // Clase de estilo para indicar indeterminado

                // Aplicar el mismo criterio de acciones que en "1/2 Jornada" y "Ausente"
                const accionesCell = entry.lastRow.querySelector(".aeo_acciones_class");
                if (accionesCell) {
                    const acciones = accionesCell.querySelectorAll(".v-accion-presente, .v-accion-ausente, .v-accion-media-jor");
                    acciones.forEach(accion => {
                        accion.classList.remove("v-accion-hidden");
                    });
                }

                // Aquí muevo el chequeo de apellido y dispositivo ANTES del continue
                const apellidoCell = entry.lastRow.querySelector(".aeo_apellido_class");
                const dispositivoCell = entry.lastRow.querySelector(".dispositivo_class");

                if (apellidoCell && dispositivoCell) {
                    const apellidoContent = apellidoCell.textContent.trim();
                    const dispositivoContent = dispositivoCell.textContent.trim();

                    if (apellidoContent !== dispositivoContent) {
                        dispositivoCell.classList.add("text-white", "bg-danger"); // Aplicar clases de advertencia
                    }
                }

                continue; // Saltar el cálculo de horas
            }

            // Calcular y aplicar la diferencia de horas totales
            const [h1, m1, s1] = entry.earliestEntry.split(":").map(Number);
            const [h2, m2, s2] = entry.latestExit.split(":").map(Number);

            const firstEntryDate = new Date(0, 0, 0, h1, m1, s1);
            const lastExitDate = new Date(0, 0, 0, h2, m2, s2);

            let diffMs = lastExitDate - firstEntryDate;
            let diffMinutes = Math.round(diffMs / 60000); // Convertir a minutos y redondear

            const diffHours = Math.floor(diffMinutes / 60);
            diffMinutes = diffMinutes % 60;

            const diffText = `${diffHours.toString().padStart(2, '0')}:${diffMinutes.toString().padStart(2, '0')}`;

            // Ahora se aplica el resultado de horas totales en la última fila del usuario
            const horasTotalCell = entry.lastRow.querySelector(".horas_total_class");
            const novedadCell = entry.lastRow.querySelector(".novedad_class"); // Seleccionar la celda de novedad
            horasTotalCell.innerText = diffText;

            // Aquí se aplican las clases de color según el número de horas y se agrega la novedad
            if ((diffHours === 0 && diffMinutes > 0) || (diffHours > 0 && diffHours < 4)) {
                horasTotalCell.classList.add("text-danger");  // Menos de 4 horas: rojo
                novedadCell.innerText = "Ausente";  // Novedad: Ausente
                novedadCell.classList.add("text-danger", "font-weight-bold"); // Aplicar clases a la novedad

                const accionesCell = entry.lastRow.querySelector(".aeo_acciones_class");
                if (accionesCell) {
                    const acciones = accionesCell.querySelectorAll(".v-accion-presente, .v-accion-ausente, .v-accion-media-jor");
                    acciones.forEach(accion => {
                        accion.classList.remove("v-accion-hidden");
                    });
                }

            } else if (diffHours >= 4 && diffHours < 6) {
                horasTotalCell.classList.add("text-warning");  // Entre 4 y 6 horas: amarillo
                novedadCell.innerText = "1/2 Jornada";  // Novedad: 1/2 Jornada
                novedadCell.classList.add("text-warning", "font-weight-bold");

                const accionesCell = entry.lastRow.querySelector(".aeo_acciones_class");
                if (accionesCell) {
                    const acciones = accionesCell.querySelectorAll(".v-accion-presente, .v-accion-ausente, .v-accion-media-jor");
                    acciones.forEach(accion => {
                        accion.classList.remove("v-accion-hidden");
                    });
                }
            } else if (diffHours >= 6) {
                horasTotalCell.classList.add("text-success");  // 6 horas o más: verde
                novedadCell.innerText = "Presente";  // Novedad: Jornada completa
                novedadCell.classList.add("text-success", "font-weight-bold");
            }

            // Verificar si el contenido de aeo_apellido_class y dispositivo_class es diferente
            const apellidoCell = entry.lastRow.querySelector(".aeo_apellido_class");
            const dispositivoCell = entry.lastRow.querySelector(".dispositivo_class");

            if (apellidoCell && dispositivoCell) {
                const apellidoContent = apellidoCell.textContent.trim();
                const dispositivoContent = dispositivoCell.textContent.trim();

                if (apellidoContent !== dispositivoContent) {
                    dispositivoCell.classList.add("text-white", "bg-danger"); // Aplicar clases de advertencia
                }
            }
        }
    }

    // Segunda iteración para aplicar bordes
    for (let i = 0; i < rows.length; i++) {
        const currentRow = rows[i];
        const userId = currentRow.getAttribute("data-id_usuario");

        if (i < rows.length - 1) {
            const nextRow = rows[i + 1];
            const nextUserId = nextRow.getAttribute("data-id_usuario");

          if (userId !== nextUserId) {
              const tds = currentRow.querySelectorAll("td");
              
              // Detectar si el valor de novedad es "Presente"
              const novedadCell = currentRow.querySelector(".novedad_class"); // Selecciona la celda de novedad
              const novedadText = novedadCell ? novedadCell.innerText.trim() : ""; // Obtiene el texto de la novedad

              // Aplicar siempre el borde independientemente del valor de novedad
              tds.forEach(td => {
                  td.classList.add("border-bottom-apellido");
              });

          }

        }
    }
}


// Función de cálculo y formateo del tiempo en formato hh:mm
function calcularDiferenciaTiempo(row1, row2, isPartial = false) {
    const time1 = row1.querySelector(".aeo_hora_class").innerText.trim();
    const time2 = row2.querySelector(".aeo_hora_class").innerText.trim();

    const [h1, m1, s1] = time1.split(":").map(Number);
    const [h2, m2, s2] = time2.split(":").map(Number);

    const date1 = new Date(0, 0, 0, h1, m1, s1);
    const date2 = new Date(0, 0, 0, h2, m2, s2);

    let diffMs = date2 - date1;
    let diffMinutes = Math.round(diffMs / 60000); // Convertir a minutos y redondear

    const diffHours = Math.floor(diffMinutes / 60);
    diffMinutes = diffMinutes % 60;

    const diffText = `${diffHours.toString().padStart(2, '0')}:${diffMinutes.toString().padStart(2, '0')}`;

    const targetCell = isPartial ? row1.querySelector(".horas__parciales_class") : row2.querySelector(".horas_total_class");
    targetCell.innerText = diffText;
}

function marcarComoExitoso(currentRow, nextRow) {
    currentRow.classList.add("text-success");
    nextRow.classList.add("text-success");
}

function calcularDiferenciaTiempo(currentRow, nextRow) {
    const entradaHora = currentRow.querySelector('td:nth-child(8)').textContent.trim();
    const salidaHora = nextRow.querySelector('td:nth-child(8)').textContent.trim();

    if (!entradaHora || !salidaHora) {
        return; // Si no hay horas, no hace nada
    }

    const [entradaHoras, entradaMinutos] = entradaHora.split(":").map(Number);
    const [salidaHoras, salidaMinutos] = salidaHora.split(":").map(Number);

    const entradaTotalMinutos = entradaHoras * 60 + entradaMinutos;
    const salidaTotalMinutos = salidaHoras * 60 + salidaMinutos;

    const diferenciaMinutos = salidaTotalMinutos - entradaTotalMinutos;

    const diferenciaHoras = Math.floor(Math.abs(diferenciaMinutos) / 60);
    const diferenciaMin = Math.abs(diferenciaMinutos) % 60;
    const resultadoTiempo = `${diferenciaHoras.toString().padStart(2, '0')}:${diferenciaMin.toString().padStart(2, '0')}`;

    const resultadoTd = nextRow.querySelector('td:nth-child(9)');
    if (resultadoTd) {
        resultadoTd.textContent = resultadoTiempo;

    }
}

function limpiarUltimoTd(row) {
    const entradaActionTd = row.querySelector('td:last-child');
    if (entradaActionTd) {
        const accionDelete = entradaActionTd.querySelector('.v-accion-delete');
        if (accionDelete) {
            accionDelete.classList.add('v-accion-hidden');
        }
    }
}

function marcarComoError(row, nextRow = null) {
    row.classList.add("text-danger");
}

////// processTableRows
</script>
<script src="../07-funciones_js/aeoAcciones.js"></script>
</body>
</html>


