<?php  
// file name: novedadesController.php
include_once '../04-modelo/novedadesModel.php'; //conecta a la base de datos

if(isset($_POST['ajax']) && $_POST['ajax'] == 'on'){
    switch ($_POST['funcion']) {
        case 'poblarDatableAll':
            poblarDatableAll($_POST['tds'], 'ajax');
            break;
        case 'poblarDatableActivos':
            // Asegúrate de que $_POST['tds'] esté presente y sea un array
            if (isset($_POST['tds']) && is_array($_POST['tds'])) {
                poblarDatableActivos($_POST['tds'], 'ajax');
            } else {
                // Puedes agregar un manejo de error en caso de que falte el parámetro
                echo json_encode(['error' => 'Las columnas tds no se recibieron correctamente.']);
            }
            break;
        case 'poblarCalendarByIdyMes':
            poblarCalendarByIdyMes($_POST['idAgente'], $_POST['viewYearMonth'], 'ajax');
            break;
    }
}


function poblarCalendarByIdyMes($id_agente, $yearMonth, $metodo){
    if ($yearMonth == '') {
        $yearMonth = date("Y-m"); // Obtiene el año y el mes actual en formato "yyyy-mm"
        $metodo = 'PHP';
    }

    // Normalizamos el formato a "YYYY-MM", asegurando que el mes tenga dos dígitos
    $yearMonthParts = explode('-', $yearMonth); // Dividimos el año y el mes
    $normalizedYearMonth = sprintf('%04d-%02d', $yearMonthParts[0], $yearMonthParts[1]); // Aseguramos formato "YYYY-MM"

    // Llamamos a la función con el formato corregido
    $novedades_resultado = modGetNovedadesByIdyMes($id_agente, $normalizedYearMonth, 'php');

    foreach ($novedades_resultado as $key_novedades_resultado => $value_novedades_resultado) {
        $novedades_resultado[$key_novedades_resultado]['allDay'] = 'true';
        $novedades_resultado[$key_novedades_resultado]['display'] = 'background';
    }

    if ($metodo != 'ajax') {
        return $novedades_resultado;
    } else {
        echo json_encode($novedades_resultado);
    }
}

function poblarDatableActivos($tds, $via, $yearMonth = null) {
    // Si $yearMonth no es null, se usa ese valor; de lo contrario, se verifica en GET, POST o se usa el mes actual.
    $yearMonth = $yearMonth 
        ?? (isset($_GET['year']) && isset($_GET['month']) 
            ? $_GET['year'] . '-' . str_pad($_GET['month'], 2, '0', STR_PAD_LEFT)
            : (isset($_POST['mes']) && isset($_POST['anio']) 
                ? $_POST['anio'] . '-' . str_pad($_POST['mes'], 2, '0', STR_PAD_LEFT)
                : date("Y-m")));

    // Obtener los usuarios activos del mes y año seleccionados
    $all_usuarios = modGetAllUsuariosActivos($yearMonth); 
    //dd($all_usuarios);

    $agente_novedades_todos = array();
    
    foreach ($all_usuarios as $value_all_usuarios) {
        $usuario_id = $value_all_usuarios['usuario_id'];

        // Inicializar la información para cada agente si aún no existe
        if (!isset($agente_novedades_todos[$usuario_id])) {
            $agente_novedades_todos[$usuario_id] = array(
                'id_agente' => $value_all_usuarios['usuario_id'],
                'agente' => $value_all_usuarios['agente'],
                '0%-pri-qui' => 0, '50%-pri-qui' => 0,'100%-pri-qui' => 0, '150%-pri-qui' => 0, '200%-pri-qui' => 0,
                '300%-pri-qui' => 0, '400%-pri-qui' => 0,
                '0%-seg-qui' => 0, '50%-seg-qui' => 0,'100%-seg-qui' => 0, '150%-seg-qui' => 0, '200%-seg-qui' => 0,
                '300%-seg-qui' => 0, '400%-seg-qui' => 0,
                'subtotal-pri-qui' => 0,  // Subtotal de la primera quincena
                'subtotal-seg-qui' => 0,  // Subtotal de la segunda quincena
                'total' => 0              // Total general
            );
        }

        // Determinar la quincena basada en la fecha
        $dia = intval(substr($value_all_usuarios['fecha'], 8, 2));  // Extraer el día de la fecha
        $quincena = ($dia <= 15) ? 'pri-qui' : 'seg-qui';  // Determinar si es primera o segunda quincena

        // Asignar un valor temporal a $paga para evitar que los null afecten el conteo
        $paga = $value_all_usuarios['paga'] ?? 999; // el número debe ser un número que no contemple ningun case

        switch ($paga) {
            case 0.00:
                $agente_novedades_todos[$usuario_id]['0%-' . $quincena] += 1;
                break;
            case 0.50:
                $agente_novedades_todos[$usuario_id]['50%-' . $quincena] += 1;
                $agente_novedades_todos[$usuario_id]['subtotal-' . $quincena] += 0.5;
                break;
            case 1.00:
                $agente_novedades_todos[$usuario_id]['100%-' . $quincena] += 1;
                $agente_novedades_todos[$usuario_id]['subtotal-' . $quincena] += 1;
                break;
            case 1.50:
                $agente_novedades_todos[$usuario_id]['150%-' . $quincena] += 1;
                $agente_novedades_todos[$usuario_id]['subtotal-' . $quincena] += 1.5;
                break;
            case 2.00:
                $agente_novedades_todos[$usuario_id]['200%-' . $quincena] += 1;
                $agente_novedades_todos[$usuario_id]['subtotal-' . $quincena] += 2;
                break;
            case 3.00:
                $agente_novedades_todos[$usuario_id]['300%-' . $quincena] += 1;
                $agente_novedades_todos[$usuario_id]['subtotal-' . $quincena] += 3;
                break;
            case 4.00:
                $agente_novedades_todos[$usuario_id]['400%-' . $quincena] += 1;
                $agente_novedades_todos[$usuario_id]['subtotal-' . $quincena] += 4;
                break;
        }
    }

    // Calcular el total general por cada agente (sumando los subtotales de ambas quincenas)
    foreach ($agente_novedades_todos as &$agente) {
        $agente['total'] = (isset($agente['subtotal-pri-qui']) ? $agente['subtotal-pri-qui'] : 0) 
                         + (isset($agente['subtotal-seg-qui']) ? $agente['subtotal-seg-qui'] : 0);
    }

    // Generar las filas de la tabla
    $filas = "";
    foreach ($agente_novedades_todos as $value_agente_novedades_todos) {
        $filas .= '<tr data-id="' . $value_agente_novedades_todos['id_agente'] . '">';

        foreach ($tds as $value_tds) {
            if (isset($value_agente_novedades_todos[$value_tds])) {
                $value = $value_agente_novedades_todos[$value_tds];

                // Determinar la clase de estilo según el tipo de columna
                $class = "";
                if ($value_tds === '0%-pri-qui' || $value_tds === '0%-seg-qui') {  // Columnas de 0%
                    $class = $value > 0 ? "text-danger font-weight-bold" : "";  // text-danger y negrita si es mayor que 0, en negro normal si es 0
                } elseif (
                    $value_tds === '50%-pri-qui' || $value_tds === '50%-seg-qui' ||
                    $value_tds === '100%-pri-qui' || $value_tds === '100%-seg-qui' ||
                    $value_tds === '150%-pri-qui' || $value_tds === '150%-seg-qui' ||
                    $value_tds === '200%-pri-qui' || $value_tds === '200%-seg-qui' ||
                    $value_tds === '300%-pri-qui' || $value_tds === '300%-seg-qui' ||
                    $value_tds === '400%-pri-qui' || $value_tds === '400%-seg-qui'
                ) {  // Columnas de porcentajes mayores a 0
                    $class = $value > 0 ? "text-success font-weight-bold" : "";  // text-success y negrita si es mayor que 0, en negro normal si es 0
                } elseif (strpos($value_tds, 'subtotal') !== false || $value_tds === 'total') {  // Subtotales y total
                    $class = "font-weight-bold text-primary lead";  // Negrita y negro
                }

                // Generar la celda con el valor y la clase de estilo
                $filas .= '<td class="text-center ' . $class . '">' . $value . '</td>';
            } else {
                // En caso de que el campo no esté definido en el array, muestra un "-"
                $filas .= '<td class="text-center"> - </td>';
            }
        }

        // Columna de acciones
        $filas .= '<td class="text-center">
            <i class="v-icon-accion p-0 fas fa-calendar-plus" data-accion="novedad" data-agente="' . $value_agente_novedades_todos['agente'] . '" data-toggle="tooltip" title="" data-original-title="Calendario novedades"></i>
            <!-- Primer icono: Número 1 -->
            <i class="v-icon-accion p-0 fas fa-1" data-accion="pago" data-tipo="primera" data-agente="' . $value_agente_novedades_todos['agente'] . '" title="Liquidación Primera Quincena" data-toggle="tooltip" data-original-title="Liquidación Primera Quincena"></i>
            <!-- Segundo icono: Número 2 -->
            <i class="v-icon-accion p-0 fas fa-2" data-accion="pago" data-tipo="segunda" data-agente="' . $value_agente_novedades_todos['agente'] . '" title="Liquidación Segunda Quincena" data-toggle="tooltip" data-original-title="Liquidación Segunda Quincena"></i>
            <!-- Tercer icono: Número 3 -->
            <i class="v-icon-accion p-0 fas fa-m" data-accion="pago" data-tipo="mensual" data-agente="' . $value_agente_novedades_todos['agente'] . '" title="Liquidación Mensual" data-toggle="tooltip" data-original-title="Liquidación Mensual"></i>
        </td>';

        $filas .= '</tr>';
    }
    if ($via != 'ajax') {
        return $filas;
    } else {
        echo $filas;
    }
}



function poblarDatableAll($tds, $via){     
   $all_usuarios = modGetAllUsuarios(); 		
   $filas = "";

   		foreach ($all_usuarios as $key_all_usuarios => $value_all_usuarios) {
   		  if($value_all_usuarios['estado']=='Desactivado'){$clase=' class="text-warning"';}else{$clase=' class=""';}	
   		  $filas .= '<tr '.$clase.' data-id="'.$value_all_usuarios['id_usuario'].'">';		
	   		  foreach ($tds as $key_tds => $value_tds) {
	   		  	$filas .= '<td>'.$value_all_usuarios[$value_tds].'</td>';
	   		  }
            // Columna de acciones
            $filas .= '<td class="text-center">
                <i class="v-icon-accion p-0 fas fa-calendar-plus" data-accion="novedad" data-agente="' . $value_agente_novedades_todos['agente'] . '" data-toggle="tooltip" title="" data-original-title="Calendario novedades"></i>
                <!-- Primer icono: Número 1 -->
                <i class="v-icon-accion p-0 fas fa-1" data-accion="pago" data-tipo="primera" data-agente="' . $value_agente_novedades_todos['agente'] . '" title="Liquidación Primera Quincena" data-toggle="tooltip" data-original-title="Liquidación Primera Quincena"></i>
                <!-- Segundo icono: Número 2 -->
                <i class="v-icon-accion p-0 fas fa-2" data-accion="pago" data-tipo="segunda" data-agente="' . $value_agente_novedades_todos['agente'] . '" title="Liquidación Segunda Quincena" data-toggle="tooltip" data-original-title="Liquidación Segunda Quincena"></i>
                <!-- Tercer icono: Número 3 -->
                <i class="v-icon-accion p-0 fas fa-m" data-accion="pago" data-tipo="mensual" data-agente="' . $value_agente_novedades_todos['agente'] . '" title="Liquidación Mensual" data-toggle="tooltip" data-original-title="Liquidación Mensual"></i>
            </td>';

		  	  $filas .='</tr>';
   		}	

	if($via != 'ajax'){
		return $filas;
	}else{
		echo json_encode($filas);
	}
}


?>


