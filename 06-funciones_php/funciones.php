<?php
// filename: funciones.php
//if(!$_REQUEST['funcionCall']){ 
    //dd($_SERVER);
    //dd($_REQUEST); 
    //dd($_POST);  
    //dd($_GET); 
//}

include_once '../00-config/configIni.php';

//si existe una llamada a una funcion y no esta vacia o no es nula
if(isset($_REQUEST['funcionCall']) && ($_REQUEST['funcionCall'] !== '' || $_REQUEST['funcionCall'] !== null)){ 

  $callMethod = $_SERVER['REQUEST_METHOD'];

  // solo si vino por get o por post
  if($callMethod === 'GET' || $callMethod === 'POST'){

    $requestData = ($callMethod === 'GET') ? $_GET : $_POST;
    //if($_POST['funcionCall'] == "arrayJoin"){dd($requestData);}
        $parametros = array();
        foreach ($requestData as $key => $value) {    
           if($key !== 'funcionCall'){ 
             array_push($parametros, $value); // prepara los parametros para pasarselos a la funcion que se va a invocar
           }
        }

        //if($_POST['funcionCall'] == "arrayJoin"){dd($parametros);}
        //dd($_REQUEST['funcionCall']);
        call_user_func_array($_REQUEST['funcionCall'], $parametros); // llama a la funcion y le pasa los parametros

  }  

}


// funciones varias que se usan casi siempre ==================================================================================================

// 1) Convierte las palabras de una cadena de texto a minusculas y solo deja Mayusculas en la primer letra de cada palabra ideal para ser usado en campos nombre
    //echo strToBold('Función: strMayusMinus($string)').'<br>'.'$string = CARLOS alberto aCOsta'.'<br>'.strToBold('Respuesta: ').strMayusMinus('CARLOS alberto aCOsta').'<br><br>';

function strMayusMinus($string){

	$string = ucwords(strtolower($string));
	return $string;

}

// 2) Convierte las palabras de una cadena de texto a minusculas
    // echo strToBold('Función: strToMinus($string)').'<br>'.'$string = CARLOS alberto aCOsta'.'<br>'.strToBold('Respuesta: ').strToMinus('CARLOS alberto aCOsta').'<br><br>'; 

function strToMinus($string){

	$string = strtolower($string);
	return $string;

}

// 3) valida si una cadena de texto tiene formato valido de correo electronico
    //echo strToBold('Función: emailValid($correo)').'<br>'.'$correo = charlie@acosta'.'<br>'.strToBold('Respuesta: '); var_dump(emailValid('charlie@acosta')); 
    //echo strToBold('Función: emailValid($correo)').'<br>'.'$correo = charlieacosta@outlook.com'.'<br>'.strToBold('Respuesta: '); var_dump(emailValid('charlieacosta@outlook.com'));

function emailValid($correo) {
    // Filtrar el correo electrónico para eliminar caracteres no válidos
    $correoFiltrado = filter_var($correo, FILTER_SANITIZE_EMAIL);

    // Verificar si el correo electrónico filtrado coincide con el original
    if (filter_var($correoFiltrado, FILTER_VALIDATE_EMAIL) === $correoFiltrado) {
        return true; // El correo electrónico es válido
    } else {
        return false; // El correo electrónico no es válido
    }
}

// 4) Convierte una cadena de texto bold
    //echo strToBold('Función: strToBold($string)').'<br>'.'$string = Charlie'.'<br>'.strToBold('Respuesta: ').strToBold('Charlie').'<br><br>'; 

function strToBold($string) {
    return "<strong>" . $string . "</strong>";
}

/**
 * Ordena un array de arrays asociativos por un índice específico en orden ascendente o descendente.
 *
 * @param array $array El array de arrays asociativos a ordenar.
 * @param string $indice El índice (clave) del array asociativo por el cual se ordenará.
 * @param string $orden Especifica si el orden será 'asc' para ascendente o 'desc' para descendente.
 * @return array El array ordenado.
 *
 * Ejemplo de uso:
 * $array = array(
 *     array("nombre" => "Juan", "edad" => 25, "carrera" => "Ingeniería"),
 *     array("nombre" => "María", "edad" => 24, "carrera" => "Medicina"),
 *     array("nombre" => "Carlos", "edad" => 23, "carrera" => "Arquitectura")
 * );
 * $arrayOrdenado = sortArray($array, 'edad', 'asc');
 *
 * Llamada a la función:
 * sortArray($array, 'indice', 'orden');
 */

function sortArray($array, $indice, $orden) {
    // Verificar si el array no está vacío
    if (empty($array)) {
        return $array;
    }

    // Verificar si el índice existe en el primer elemento del array
    if (!isset($array[0][$indice])) {
        throw new InvalidArgumentException("El índice '{$indice}' no existe en los elementos del array.");
    }

    // Obtener los valores correspondientes al índice dado en un nuevo array
    $values = array_column($array, $indice);

    // Determinar el tipo de ordenamiento
    $sortOrder = ($orden === 'asc') ? SORT_ASC : SORT_DESC;

    // Ordenar el array original utilizando los valores y el tipo de ordenamiento
    array_multisort($values, $sortOrder, $array);

    return $array;
}


/**
 * Ordena un array de arrays asociativos por múltiples índices en orden ascendente o descendente.
 *
 * @param array $array El array de arrays asociativos a ordenar.
 * @param array $indicesOrden Un array asociativo donde las claves son los índices por los cuales se ordenará y los valores son 'asc' para ascendente o 'desc' para descendente.
 * @return array El array ordenado.
 *
 * Ejemplo de uso:
 * $array = array(
 *     array("nombre" => "Juan", "edad" => 25, "carrera" => "Ingeniería"),
 *     array("nombre" => "María", "edad" => 24, "carrera" => "Medicina"),
 *     array("nombre" => "Carlos", "edad" => 23, "carrera" => "Arquitectura"),
 *     array("nombre" => "Carlos", "edad" => 22, "carrera" => "Derecho")
 * );
 * $arrayOrdenado = sortArrayMultiple($array, ['nombre' => 'asc', 'edad' => 'desc']);
 *
 * Llamada a la función:
 * sortArrayMultiple($array, ['indice1' => 'orden1', 'indice2' => 'orden2']);
 */

function sortArrayMultiple(array $array, array $indicesOrden): array {
    // Verificar si el array no está vacío
    if (empty($array)) {
        return $array;
    }

    // Array que contendrá los parámetros para array_multisort
    $multisortParams = [];

    // Iterar sobre cada índice y orden
    foreach ($indicesOrden as $indice => $orden) {
        // Verificar si el índice existe en el primer elemento del array
        if (!isset($array[0][$indice])) {
            throw new InvalidArgumentException("El índice '{$indice}' no existe en los elementos del array.");
        }

        // Obtener los valores correspondientes al índice dado en un nuevo array
        $values = array_column($array, $indice);
        $multisortParams[] = $values;

        // Determinar el tipo de ordenamiento
        $sortOrder = ($orden === 'asc') ? SORT_ASC : SORT_DESC;
        $multisortParams[] = $sortOrder;
    }

    // Añadir el array original al final de los parámetros
    $multisortParams[] = &$array;

    // Llamar a array_multisort con los parámetros dinámicos
    call_user_func_array('array_multisort', $multisortParams);

    return $array;
}




// 5) Convierte las palabras de una cadena de texto a mayusculas
    //echo strToBold('Función: strToMayus($string)').'<br>'.'$string = CARLOS alberto aCOsta'.'<br>'.strToBold('Respuesta: ').strToMayus('CARLOS alberto aCOsta').'<br><br>';

function strToMayus($string){

    $string = strtoupper($string);
    return $string;

}

// 6) Subraya una cadena de texto
    //echo strToBold('Función: strToUnderLine($string)').'<br>'.'$string = Buen día'.'<br>'.strToBold('Respuesta: ').strToUnderLine('Buen día').'<br><br>';

function strToUnderLine($string) {
    return '<u>' . $string . '</u>';
}


// 7) Pone negritas a una palabra dentro de texto
    //echo strToBold('Función: wordToBold($texto, $palabra)').'<br>'.'$string = Buen día, mister Charlie'.'<br>'.strToBold('Respuesta: ').wordToBold('Buen día, mister Charlie', 'mister').'<br><br>';

function wordToBold($texto, $palabra) {
    $texto = str_ireplace($palabra, '<strong>' . $palabra . '</strong>', $texto);
    return $texto;
}

// 8) Crea las options para un campo select dado un array
// $array_ejemplo = array(array("nombre" => "Juan", "edad" => 25, "carrera" => "Ingeniería"), array("nombre" => "María", "edad" => 24, "carrera" => "Medicina"), array("nombre" => "Carlos", "edad" => 23, "carrera" => "Arquitectura"));

//arrayToOptions(
    //$array,       NOMBRE DEL ARRAY | 'agentes'
    //$leyenda,     LEYENDA DE INICIO DEL SELECT | Eje: 'Seleccione un opción' 
    //$valor,       EL VALUE DEL OPTION EQUIVALE A UN SUBINIDICE DEL ARRAY | Eje: 'edad' 
    //$texto,       EL TEXTO DEL OPTION EQUIVALE A UN SUBINIDICE DEL ARRAY | Eje: 'nombre'
    //$separador,   SEPARADOR DEL CONCAT | Eje: '-', ' '. '_', '|'
    //$$concat_text POR SI SE QUIERE CON CONCATENAR OTRO SUBINIDICE DEL ARRAY SIRVE POR EJEMPLO PARA PONER CODIGOS | Eje 'Carrera'
    //$selectValue, VALOR PARA MOSTRAR UN OPTION SELECCIONADO POR SU VALUE | Eje: '25'
    //$selectText,  VALOR PARA MOSTRAR UN OPTION SELECCIONADO POR TEXTO | Eje: 'María' (Tener en cuenta que debe ser un valor 'único)
//)

function arrayToOptions($array, $leyenda, $valor, $texto, $separador, $concat_text, $concat_text_2, $concat_text_3, $concat_text_4, $selectValue, $selectText){
    $options = '';

    foreach ($array as $key => $value) {

       if($selectValue == $value[$valor] || $selectText == $value[$texto]){ 
            $options .= '<option selected value="'.$value[$valor].'" >'.$value[$texto].' '.$separador.' '.$value[$concat_text].' '.$separador.' '.$value[$concat_text_2].' '.$separador.' '.$value[$concat_text_3].' '.$value[$concat_text_4].'</option>';  
            $leyenda = null;
       }else{
            $options .= '<option value="'.$value[$valor].'" >'.$value[$texto].' '.$separador.' '.$value[$concat_text].' '.$separador.' '.$value[$concat_text_2].' '.$separador.' '.$value[$concat_text_3].' '.$value[$concat_text_4].'</option>'; 
       }

    }

    if($leyenda !== null){$options ='<option selected disabled="true">'.$leyenda.'</option>'.$options;} 

    return $options;
}



/**
 * Genera un conjunto de etiquetas <option> a partir de un array asociativo.
 *  
 * @param array $array Array que contiene los datos para generar las opciones.
 * @param string $valor Clave dentro del array que se usará como atributo value en la opción (obligatorio).
 * @param string $texto Clave dentro del array que se usará como el texto visible de la opción (obligatorio).
 * @param string|null $leyenda Texto que aparecerá como la primera opción deshabilitada (opcional).
 * @param string $separador Carácter o texto que separará los valores concatenados (por defecto es un espacio).
 * @param array $concat_values Array de claves para obtener los valores adicionales a concatenar.
 * @param string|null $selectedValue Valor que, si coincide con el valor del array, seleccionará la opción (opcional).
 * @param string|null $selectedText Texto que, si coincide con el texto del array, seleccionará la opción (opcional).
 * @return string Cadena de texto que contiene todas las etiquetas <option> generadas.
 *  
 * Ejemplo de uso de la función
 * $array = [
 *     ['id' => 1, 'nombre' => 'Producto A', 'categoria' => 'Categoria 1', 'marca' => 'Marca A', 'precio' => '100'],
 *     ['id' => 2, 'nombre' => 'Producto B', 'categoria' => 'Categoria 2', 'marca' => 'Marca B', 'precio' => '200'],
 *     ['id' => 3, 'nombre' => 'Producto C', 'categoria' => 'Categoria 1', 'marca' => 'Marca C', 'precio' => '150']
 * ];
 * 
 * Parámetros para generar las opciones
 * $leyenda = 'Seleccione un producto'; // Texto deshabilitado en la primera opción
 * $valor = 'id';                       // Clave que será el value de las opciones
 * $texto = 'nombre';                   // Clave que será el texto visible en las opciones
 * $separador = ' - ';                  // Separador entre los valores concatenados
 * $concat_values = ['categoria', 'marca', 'precio']; // Array con los campos a concatenar
 * $selectedValue = 2;                  // Valor seleccionado por defecto
 * $selectedText = '';                  // No se selecciona por texto en este ejemplo
 * 
 * // Genera las opciones
 * $options = arrayToOptionsV2($array, $valor, $texto, $leyenda, $separador, $concat_values, $selectedValue, $selectedText);
 * 
 * // Muestra las opciones dentro de un select
 * echo '<select>'.$options.'</select>';
 */

function arrayToOptionsV2($array, $valor, $texto, $leyenda = null, $separador = ' ', $concat_values = [], $selectedValue = null, $selectedText = null) {
    $options = '';

    // Añade la leyenda como primera opción si está configurada
    if ($leyenda !== null) {
        $options .= sprintf('<option value="" disabled selected>%s</option>', $leyenda);
    }

    // Recorre cada elemento del array
    foreach ($array as $value) {
        // Determina si la opción debe ser seleccionada
        $isSelected = ($selectedValue == $value[$valor] || $selectedText == $value[$texto]) ? ' selected' : '';

        // Construye el texto concatenado de la opción
        $concatTexts = array_filter(array_map(function($key) use ($value) {
            return $value[$key] ?? null;
        }, $concat_values));

        // Genera la opción con los valores concatenados y separados
        $options .= sprintf('<option value="%s"%s>%s</option>', 
                            $value[$valor], 
                            $isSelected, 
                            implode($separador, array_merge([$value[$texto]], $concatTexts)));
    }

    return $options;
}

// 8) arrayPrintValue(): Esta funcion devuelve el valor de un indice de un array envuelto en etiquetas. Se recomienda que las etiquetas sean palabras, pueden ser etiquetas html pero no se deberia usar embebida con otra funcion entonces. El array debe tener un formato indice => valor. Si el indice no esta seteado devuelve por defecto null, pero puede devolver otro valor si se indica.

// referencia: arrayPrintValue([etiqueta de comienzo], [nombre del array], [indice del array], [etiqueta de fin], [salidad forzada por defecto null])
// ejemplo: arrayPrintValue('Nombre:', $usuarios, 'nombre_usuario', 'SOCIO', 'NO EXISTE')

function arrayPrintValue($labelStart, $array, $indice, $labelEnd, $forceSalida = null){
       return isset($array[$indice]) ? $labelStart.$array[$indice].$labelEnd : $forceSalida;
}


// 9) formatea una string a fecha con el formato indicado | formato d/m/Y
function strToDateFormat($string, $formato){
    return empty($string) ? null : date($formato, strtotime($string));
}


// 9)
// Inicia o reanuda la sesión | URL_DESTINO se define en el archivo configuracion.php | $expira por defecto una hora 

function sesion($url_destino = URL_LOGIN, $expira = SESION_TIME) {
    // Iniciar sesión si no está ya iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Verificar si la sesión está activa
    if (!isset($_SESSION['usuario']['id_usuario']) || $_SESSION['usuario']['id_usuario'] == '') {
        // Si la sesión no está activa, redirigir al login
        header('Location: ' . $url_destino);
        exit();
    }

    // Verificar si la sesión ha expirado por inactividad
    if (isset($_SESSION['ultima_actividad'])) {
        $inactividad = time() - $_SESSION['ultima_actividad']; // tiempo transcurrido desde la última actividad
        if ($inactividad > $expira) {
            // Si ha pasado más de SESION_TIME segundos desde la última actividad, destruir la sesión
            session_unset(); // Destruir todas las variables de sesión
            session_destroy(); // Destruir la sesión actual
            header('Location: ' . $url_destino); // Redirigir al login
            exit();
        }
    }

    // Actualizar el timestamp de la última actividad para mantener la sesión activa
    $_SESSION['ultima_actividad'] = time();
}


// 10)
// Conecta con una base de datos MySQL

function conectaDB($server = DB_SERVER, $user = DB_USER, $pass = DB_PASS, $base = DB_NAME){
    date_default_timezone_set('America/Argentina/Buenos_Aires');    
    $db = mysqli_connect($server, $user, $pass, $base);

    if (!$db) {
        echo "Error: No se pudo conectar a MySQL." . PHP_EOL;
        echo "error de depuración: " . mysqli_connect_errno() . PHP_EOL;
        echo "error de depuración: " . mysqli_connect_error() . PHP_EOL;
        exit;
    } 
    return $db;
}


// 11) funcion tipo DB:
// esta función verifica si existe un registro con un valor en una columna parametrizada se puede usar en combinación con la funcion  existInDB de JS
// si el dato no existe al menos una vez devuelve false
// $tabla       = Nombre de la tabla
// $columnaDB   = columna de la tabla donde se va a buscar el valor
// $valueSearch = dato que se va a buscar

function existInDB($tabla, $columnaDB, $valueSearch, $callType, $valueStatus) {
    $db = conectaDB(); 
    $db->set_charset('utf8mb4');

    // Construir la consulta
    $query = "SELECT t.* FROM " . $tabla . " AS t WHERE t." . $columnaDB . " = '" . $valueSearch; 
    
    if ($valueStatus !== '') {
        $query .= "' AND t.estado = '" . $valueStatus . "';";
    } else {
        $query .= "';";
    }

    $rows = array();
    $resultado = $db->query($query);

    if ($resultado !== false && $resultado->num_rows > 0) {
        while ($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)) {
            $rows[] = $row;
        }
        // Siempre devolver solo la primera fila encontrada
        $rows = $rows[0];
    } else {
        $rows = []; // Aseguramos que $rows sea un array vacío si no hay resultados
    }
    // Cierra la conexión a la base de datos
    mysqli_close($db);

    // Manejo del retorno según el tipo de llamada
    if ($callType !== 'ajax') {    
        if (empty($rows)) {
            return false;
        } else {
            return $rows; // es php, devuelve un array asociativo de una fila
        }
    } else {
        if (empty($rows)) {
            echo json_encode(array('status' => false));
        } else {
            echo json_encode($rows); // es ajax, devuelve un json
        }
    }
}




// 12) funcion tipo DB:
// esta función agrega agrega un registro en una tabla de la base de datos
// devuelve false si no se pudo concretar la operacion 
// $tabla       = Nombre de la tabla
// $columnaDB   = columna de la tabla donde se va a buscar el valor
// $valueSearch = dato que se va a buscar

//simpleInsertInDB($tabla, $arrayColumnas, $arrayValues, $callType, $valueStatus = 'Activo'){


function simpleInsertInDB($tabla, $arrayColumnas, $arrayValues, $callType, $valueStatus = 'Activo'){

    $db = conectaDB(); 

    if (!is_array($arrayColumnas)){$arrayColumnas = json_decode($arrayColumnas, true);}
    //var_dump($arrayColumnas); //[DEBUG PERMANENTE]

    if (!is_array($arrayValues)){$arrayValues = json_decode($arrayValues, true);}
    //var_dump($arrayValues); //die(); //[DEBUG PERMANENTE]


    $columnas = " (";
    $values = "";
    $ultimoValor = end($arrayColumnas);
    $ultimaClave = key($arrayColumnas);

    foreach ($arrayColumnas as $key => $value){

         if($arrayValues[$key] !== ''){    
            $columnas .= $value;  
            $values   .= "'".$arrayValues[$key]."'";
            if($value !== $ultimoValor){$columnas .= ', '; $values .= ', ';}
         } 
    } 
    $columnas .= ")";

    $query  = "INSERT INTO ".$tabla.$columnas." ";
    $query .= "VALUES (".$values.");";
    //var_dump($query); die(); //[DEBUG PERMANENTE]          

    $resultado = $db->query($query);
    //dump($query); //[DEBUG PERMANENTE] 
    //dump('Error en la consulta: ' . $db->error); //[DEBUG PERMANENTE] 
    //dd($resultado); //[DEBUG PERMANENTE]

    mysqli_close($db); // cierra la base de datos


   if($callType !== 'ajax'){    
       
       return $resultado; // es php devuelve un array() 
        
   }else{

       echo $resultado; // es ajax devuelve un json
   }
}


function simpleInsertInDB_v2($tabla, $arrayColumnas, $arrayValues, $callType, $valueStatus = 'Activo') {
    $db = conectaDB(); 

    // Convertir JSON a array si es necesario
    if (!is_array($arrayColumnas)) {
        $arrayColumnas = json_decode($arrayColumnas, true);
    }

    if (!is_array($arrayValues)) {
        $arrayValues = json_decode($arrayValues, true);
    }

    $columnas = " (";
    $values = "";
    $ultimoValor = end($arrayColumnas);
    $ultimaClave = key($arrayColumnas);

    foreach ($arrayColumnas as $key => $value) {
        if ($arrayValues[$key] !== '') {
            $columnas .= $value;  
            $values   .= "'" . mysqli_real_escape_string($db, $arrayValues[$key]) . "'"; // Sanitizar los valores
            if ($value !== $ultimoValor) {
                $columnas .= ', ';
                $values .= ', ';
            }
        }
    } 
    $columnas .= ")";

    $query = "INSERT INTO ".$tabla.$columnas." VALUES (".$values.");";

    // Ejecutar la consulta
    $resultado = $db->query($query);

    // Manejar errores en la consulta
    if (!$resultado) {
        $error = "Error en la consulta: " . $db->error;
        mysqli_close($db);  // Cerrar la base de datos
        if ($callType !== 'ajax') {
            return false;  // En PHP devolvemos false en caso de error
        } else {
            echo json_encode(['success' => false, 'error' => $error]); // En AJAX devolvemos un JSON con el error
            return;
        }
    }

    mysqli_close($db); // Cierra la base de datos

    // Respuesta exitosa
    if ($callType !== 'ajax') {    
        return true; // En PHP, devuelve true si la consulta fue exitosa
    } else {
        echo json_encode(['success' => true]); // En AJAX, devolvemos un JSON de éxito
    }
}



function simpleUpdateInDB($tabla, $arraySet, $arrayWhere, $callType, $valueStatus = 'Activo') {
    //dump($tabla); dump($arraySet); dump($arrayWhere); dump($callType); dd($valueStatus); 

    $db = conectaDB();

    if (!is_array($arraySet)) {
        $arraySet = json_decode($arraySet, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error decodificando JSON para arraySet: " . json_last_error_msg());
        }
    }

    if (!is_array($arrayWhere)) {
        $arrayWhere = json_decode($arrayWhere, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error decodificando JSON para arrayWhere: " . json_last_error_msg());
        }
    }

    // Validar que arraySet y arrayWhere sean válidos
    if (empty($arraySet) || empty($arrayWhere)) {
        throw new Exception("Parámetros inválidos para la consulta.");
    }

    // Obtener el esquema de la tabla para verificar tipos
    $querySchema = "DESCRIBE $tabla";
    $resultSchema = $db->query($querySchema);
    if (!$resultSchema) {
        throw new Exception("Error al obtener el esquema de la tabla: " . $db->error);
    }

    $schema = [];
    while ($row = $resultSchema->fetch_assoc()) {
        $schema[$row['Field']] = $row['Type'];
    }

    // Preparar la parte SET de la consulta
    $setParts = [];
    foreach ($arraySet as $key => $value) {
        if (!isset($schema[$key])) {
            throw new Exception("La columna $key no existe en la tabla $tabla.");
        }

        // Verificar y convertir el tipo de dato si es necesario
        $fieldType = strtolower($schema[$key]);

        if (strpos($fieldType, 'int') !== false) {
            $value = (int)$value;
            $setParts[] = "$key = $value";  // Sin comillas para enteros
        } elseif (strpos($fieldType, 'float') !== false || strpos($fieldType, 'double') !== false || strpos($fieldType, 'decimal') !== false) {
            $value = (float)$value;
            $setParts[] = "$key = $value";  // Sin comillas para floats
        } elseif (strpos($fieldType, 'varchar') !== false || strpos($fieldType, 'text') !== false) {
            $value = (string)$value;
            $value = mysqli_real_escape_string($db, $value);
            $setParts[] = "$key = '$value'";  // Con comillas para cadenas
        } elseif (strpos($fieldType, 'date') !== false || strpos($fieldType, 'time') !== false || strpos($fieldType, 'datetime') !== false || strpos($fieldType, 'timestamp') !== false) {
            $date = date_create($value);
            if ($date) {
                $value = date_format($date, 'Y-m-d H:i:s');
                $setParts[] = "$key = '$value'";  // Con comillas para fechas
            } else {
                throw new Exception("El valor para la columna $key no es una fecha válida.");
            }
        } else {
            // Por defecto, si no se reconoce el tipo, tratar como string
            $value = mysqli_real_escape_string($db, $value);
            $setParts[] = "$key = '$value'";
        }
    }
    $set = "SET " . implode(', ', $setParts);

    // Preparar la parte WHERE de la consulta con múltiples condiciones
    $whereParts = [];
    foreach ($arrayWhere as $condition) {
        if (!isset($condition['columna']) || !isset($condition['condicion']) || !isset($condition['valorCompara'])) {
            throw new Exception("Falta información en una de las condiciones WHERE.");
        }
        $columna = mysqli_real_escape_string($db, $condition['columna']);
        $valorCompara = mysqli_real_escape_string($db, $condition['valorCompara']);
        $whereParts[] = "$columna " . $condition['condicion'] . " '$valorCompara'";
    }
    $where = "WHERE " . implode(' AND ', $whereParts);  // Se concatenan con AND

    // Construir la consulta final
    $query = "UPDATE $tabla $set $where;";
    //dump($query); // FOR DEBUGG

    // Ejecutar la consulta y manejar errores
    if (!$resultado = $db->query($query)) {
        throw new Exception("Error en la consulta: " . $db->error);
    }

    // Cerrar la conexión a la base de datos
    mysqli_close($db);

    // Devolver resultado según el tipo de llamada
    if ($callType !== 'ajax') {
        //dump('php'); //dd($resultado);        
        return $resultado;  // Si no es AJAX, devolver el resultado
    } else {
    //dump('ajax'); //dd($resultado);  
        echo json_encode($resultado);  // Si es AJAX, devolver JSON
    }
}

function deleteInDB($tabla, $camposCondicionesValores, $callType = 'ajax'){
//var_dump($tabla, $camposCondicionesValores, $callType); die(); //[DEBUG PERMANENTE]

    $db = conectaDB(); 

    if (!is_array($camposCondicionesValores)){$camposCondicionesValores = json_decode($camposCondicionesValores, true);}
    //var_dump($camposCondicionesValores); die(); //[DEBUG PERMANENTE]


    $query  = "DELETE FROM "; 
    $query .= $tabla." ";
    $query .= "WHERE ";

    foreach ($camposCondicionesValores as $key => $value){
         $query .= $value." ";
    } 

    $query .= ";";
    //var_dump($query); die(); //[DEBUG PERMANENTE]

    $resultado = $db->query($query);
    //var_dump($resultado); die(); //[DEBUG PERMANENTE]

    mysqli_close($db); // cierra la base de datos


   if($callType !== 'ajax'){    
       
       return $resultado; // es php devuelve un array() 
        
   }else{

       echo $resultado; // es ajax devuelve un json
   }

}



// Esta funcion hace un select en un tabla de datos
// simpleSelectAllDB($tabla, $arrayColumnas, $arrayValues, $callType, $valueStatus = 'Activo')
// $table_name, NOMBRE DE LA TABLA | Eje: 'materiales'
// $column_condition, NOMBRE DE LA COLUMNA A LA QUE SE LE APLICARA UNA CONDICION | Eje: 'estado'
// $condition, CONDICION A APLICAR | Eje '=', '<>', 'LIKE', '>', '<' ...etc
// $value_condition, VALOR DE LA CONDICION | Eje "'Activo'", 10, "'Perez'" ...etc

function SelectAllDB($table_name, $column_condition, $condition, $value_condition, $callType = 'php'){
    
    $db = conectaDB(); 

    $query  = "SELECT * ";
    $query .= "FROM ".$table_name." ";
    $query .= "WHERE ".$column_condition." ".$condition." ".$value_condition;
    $query .= ";";

    $resultado = $db->query($query);

    if ($resultado !== false && $resultado->num_rows > 0) {
        $rows = array(); // Crear un arreglo vacío para almacenar los registros

        while ($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)) {
            $rows[] = $row; // Agregar cada fila al arreglo
        }
    } 
  
    mysqli_close($db); // cierra la base de datos

    if ($callType !== 'ajax') {    
        return empty($rows) ? false : $rows;
    } else {
        echo json_encode(empty($rows) ? ['status' => false] : $rows);
    }

}

function comparaFechaHora($stringFechaHora, $flag) {
    // Fecha y hora actual
    $fechaHoraActual = new DateTime();

    // Convertir el string a un objeto DateTime según el formato correspondiente
    switch ($flag) {
        case 'fh':
            $fechaHoraObj = DateTime::createFromFormat('Y-m-d H:i:s', $stringFechaHora);
            $interval = $fechaHoraActual->diff($fechaHoraObj);
            break;
        case 'f':
            $fechaHoraObj = DateTime::createFromFormat('Y-m-d', $stringFechaHora);
            $interval = $fechaHoraActual->diff($fechaHoraObj);
            break;
        case 'h':
            $fechaHoraObj = DateTime::createFromFormat('H:i:s', $stringFechaHora);
            $interval = $fechaHoraActual->diff($fechaHoraObj);
            break;
        default:
            return array('error' => 'Flag no válido. Los valores permitidos son "fh", "f" o "h".');
    }

    // Compara la fecha y hora actuales con la proporcionada
    if ($fechaHoraObj === false) {
        return array('error' => 'El formato del string proporcionado no coincide con el flag especificado.');
    }

    if ($fechaHoraObj > $fechaHoraActual) {
        $resultado = 'posterior';
    } elseif ($fechaHoraObj < $fechaHoraActual) {
        $resultado = 'anterior';
        // Cambiamos los valores a negativos en caso de ser anterior
        $interval->invert = 1;
    } else {
        $resultado = 'igual';
    }

    // Construir el array asociativo con el resultado
    $resultadoArray = array(
        'statusFechaHora' => $resultado,
        'días' => (int) $interval->format('%r%a'),
        'horas' => (int) $interval->format('%r%H')
    );

    return $resultadoArray;
}


function countColWhere($tabla, $arrayWhere, $callType){

    $db = conectaDB();
  
    $query  = "SELECT count(*) AS total";  
    $query .= " FROM ".$tabla;
    $query .= " WHERE ".$arrayWhere['columna']." ".$arrayWhere['condicion']." '".$arrayWhere['valor']."';";
    //var_dump($query); die(); //[DEBUG PERMANENTE]    

    $rows = array();
    $resultado = $db->query($query);
    //var_dump($resultado); die(); //[DEBUG PERMANENTE]

    if($resultado !== false && $resultado->num_rows > 0){
        while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}
        $rows = $rows[0];
    } 
    //  var_dump($rows); die();

    mysqli_close($db); // cierra la base de datos

   if($callType !== 'ajax'){    
       
       if(empty($rows)){ return false; } else { return $rows; } // es php devuelve un array() 
        
   }else{

       if(empty($rows)){ echo json_encode( array('status' => false)); } else { echo json_encode($rows); } // es ajax devuelve un json
   }

}

// funcion para limpiar datos enviados por post o get
function sanitizeInput($input) {
    // Eliminar espacios en blanco al principio y al final
    $input = trim($input);

    // Eliminar barras invertidas escapadas
    $input = stripslashes($input);

    // Convertir caracteres especiales en entidades HTML
    $input = htmlspecialchars($input);

    return $input;
}

//////////////////////////////// funciones de este sistema

//12 funcion tipo DB: trae los datos de la calle, localidad, partido y provincia a partir de la calle y la localidad
function dataByIdCallelocalidad($idCalle, $idLocalidad, $callType){

    $db = conectaDB(); 

    $query =
    "SELECT c.*, par.*, loc.*, pro.*
    FROM calles AS c

    LEFT JOIN partidos AS par
    ON  par.id_partido = c.id_partido

    LEFT JOIN localidades AS loc
    ON loc.id_partido = par.id_partido

    LEFT JOIN provincias AS pro
    ON pro.id_provincia = par.id_provincia

    WHERE c.id_calle = '".$idCalle."' AND loc.id_localidad = '".$idLocalidad."';";

    $rows = array();
    $resultado = $db->query($query);

    if($resultado !== false && $resultado->num_rows > 0){
        while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}
        $rows = $rows[0];
    } 

    mysqli_close($db); // cierra la base de datos


   if($callType !== 'ajax'){    
       
       if(empty($rows)){ return false; } else { return $rows; } // es php devuelve un array() 
        
   }else{

       if(empty($rows)){ echo json_encode( array('status' => false)); } else { echo json_encode($rows); } // es ajax devuelve un json
   }

}


function normaliza_string($originalString){

    $remplazar = array(
        ' ' => '_', '-' => '_',
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
        'ñ' => 'n', 'Ñ' => 'N'
    );

    $string_normalizado = strtr($originalString, $remplazar);
    return $string_normalizado;

}

function error_file($error_code){

            switch ($error_code) {
                case UPLOAD_ERR_INI_SIZE:
                    $mensaje_error = "El archivo excede el tamaño máximo permitido.";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $mensaje_error = "El archivo excede el tamaño máximo especificado en el formulario.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $mensaje_error = "El archivo se cargó parcialmente.";
                    break;
                // ... Otros casos para manejar los diferentes códigos de error.
                default:
                    $mensaje_error = "Error de carga de archivo: $error_code";
            }

            return $mensaje_error;
}


function optionsGetEnum($Dbase_name, $tabla_name, $columna_name, $leyenda, $selected){
//var_dump($Dbase_name, $tabla_name, $columna_name, $leyenda, $selected) ; die(); //[DEBUG PERMANENTE]

    $db = conectaDB(); 

    $query  = "SELECT column_type ";
    $query .= "FROM information_schema.COLUMNS ";
    $query .= "WHERE table_schema = '".$Dbase_name."' ";
    $query .= "AND TABLE_NAME = '".$tabla_name."' ";
    $query .= "AND column_name = '".$columna_name."';";
    //var_dump($query); die();

    $resultado = $db->query($query);
    //var_dump($resultado); die(); //[DEBUG PERMANENTE]

    while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}
    //var_dump($rows[0]; die(); //[DEBUG PERMANENTE] 

    mysqli_close($db); // cierra la base de datos

    $is_enum = substr($rows[0][COLUMN_TYPE], 0, 4);
    //var_dump($is_enum); die(); //[DEBUG PERMANENTE]

    if($is_enum == 'enum'){
        $items = str_replace("enum(", "", $rows[0][COLUMN_TYPE]);
        $items = substr($items, 0, -1);
        $items = explode(',', str_replace("'", '', $items));
        //var_dump($items); die(); //[DEBUG PERMANENTE]

        if($selected == null){$seleccionado = "selected";} else {$seleccionado = "";}
        $options = '<option class="v-disabled" value="" disabled="disabled" '.$seleccionado.'>'.$leyenda.'</option>';

        sort($items);

        foreach ($items as $key => $value) {
           if($value == $selected){$seleccionado = "selected";} else {$seleccionado = "";}
           $options .= '<option value="'.utf8_encode($value).'" '.$seleccionado.'>'.utf8_encode($value).'</option>';

        }
        //var_dump($options); die(); //[DEBUG PERMANENTE]

        return $options;

    }else{
        $options = '<option class="v-disabled" value="ERROR">'."ERROR".'</option>';
        //var_dump($options); die(); //[DEBUG PERMANENTE]

        return $options;
    } 
}

// var_dump con die()
function dd($variable){var_dump($variable); die();}


// var_dump
function dump($variable){var_dump($variable);}

/**
 * Función para verificar la existencia de un registro en la base de datos basado en múltiples columnas y valores.
 *
 * @param string $tabla Nombre de la tabla en la base de datos.
 * @param array $columnasDB Array de nombres de las columnas a buscar.
 * @param array $valoresSearch Array de valores correspondientes a buscar en las columnas.
 * @param string $callType Tipo de llamada ('ajax' para retornar JSON o cualquier otro valor para retornar array).
 * @return mixed Devuelve un array con el registro encontrado o false si no se encuentra.
 */

/**
 * Ejemplo de uso:
 * 
 * // Datos de entrada
 * $tabla = 'nombreDeLaTabla';
 * $columnasDB = ['columna1', 'columna2']; // Nombres de las columnas a buscar
 * $valoresSearch = ['valor1', 'valor2']; // Valores correspondientes a las columnas
 * $callType = 'ajax'; // Tipo de llamada, puede ser 'ajax' o cualquier otro valor
 * 
 * // Llamada a la función
 * $resultado = existInDBByMultipleValues($tabla, $columnasDB, $valoresSearch, $callType);
 * 
 * // Manejo del resultado
 * if ($callType === 'ajax') {
 *     echo $resultado;
 * } else {
 *     var_dump($resultado);
 * }
 */


function existInDBByMultipleValues($tabla, $callType, $columnasDB, $valoresSearch) {
    // Función interna para convertir fechas a formato YYYY-MM-DD o YYYY-MM-DD HH:MM:SS
    function convertToDateFormat($valor) {
        $dateTimeParts = explode(' ', $valor);  // Separar fecha y hora si existe
        $datePart = $dateTimeParts[0];  // Parte de la fecha
        $timePart = isset($dateTimeParts[1]) ? $dateTimeParts[1] : null;  // Parte de la hora, si existe

        // Intenta detectar el formato de la fecha
        if (preg_match('/^\d{2}\/\d{2}\/\d{2,4}$/', $datePart)) {  // Si está en formato DD/MM/YY o DD/MM/YYYY
            $dateParts = explode('/', $datePart);
            if (count($dateParts) == 3) {
                $day = $dateParts[0];
                $month = $dateParts[1];
                $year = $dateParts[2];

                // Manejar año de dos dígitos
                if (strlen($year) == 2) {
                    // Decidir el siglo basado en un umbral, por ejemplo, 50
                    $year = (int) $year;
                    if ($year >= 50) {
                        $year = '19' . $year;  // Siglo 20
                    } else {
                        $year = '20' . $year;  // Siglo 21
                    }
                }

                // Valida que la fecha sea válida
                if (checkdate($month, $day, $year)) {
                    $formattedDate = $year . '-' . $month . '-' . $day;
                } else {
                    return false; // Fecha inválida
                }
            }
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePart)) {  // Si está en formato YYYY-MM-DD
            $formattedDate = $datePart;
        } else {
            return false; // Formato desconocido
        }

        // Si tiene parte de tiempo, añadirla
        if ($timePart) {
            $formattedDate .= ' ' . $timePart;
        }

        return $formattedDate;
    }

    // Conexión a la base de datos
    $db = conectaDB();

    // Obtener la información del esquema de la tabla
    $schemaQuery = "DESCRIBE " . $tabla . ";";
    $schemaResult = $db->query($schemaQuery);
    $columnTypes = [];

    while ($row = mysqli_fetch_array($schemaResult, MYSQLI_ASSOC)) {
        $columnTypes[$row['Field']] = $row['Type'];
    }

    // Construir la consulta SQL con múltiples columnas y valores
    $query = "SELECT t.* FROM " . $tabla . " AS t WHERE ";
    $conditions = [];

    foreach ($columnasDB as $index => $columna) {
        // Verificar el tipo de dato de la columna y formatear el valor de búsqueda
        $type = $columnTypes[$columna];
        $valor = $valoresSearch[$index];

        if (strpos($type, 'int') !== false || strpos($type, 'decimal') !== false || strpos($type, 'float') !== false) {
            $valor = (float) $valor;
        } elseif (strpos($type, 'date') !== false || strpos($type, 'datetime') !== false) {
            $valor = convertToDateFormat($valor);
            if (!$valor) {
                // Manejo de error en caso de que la fecha sea inválida
                return false;
            }
            $valor = "'" . $db->real_escape_string($valor) . "'";
        } elseif (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
            $valor = "'" . $db->real_escape_string($valor) . "'";
        } else {
            $valor = "'" . $db->real_escape_string($valor) . "'";
        }

        $conditions[] = "t." . $columna . " = " . $valor;
    }

    $query .= implode(' AND ', $conditions) . ";";
    $rows = array();
    $resultado = $db->query($query);

    if ($resultado !== false && $resultado->num_rows > 0) {
        while ($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)) {
            $rows[] = $row;
        }
        $rows = $rows[0];
    }

    mysqli_close($db); // Cierra la base de datos

    if ($callType !== 'ajax') {
        if (empty($rows)) {
            return false;
        } else {
            return $rows; // Es PHP, devuelve un array()
        }
    } else {
        if (empty($rows)) {
            echo json_encode(array('status' => false));
        } else {
            echo json_encode($rows); // Es AJAX, devuelve un JSON
        }
    }
}


/**
 * Realiza una consulta a la base de datos con filtros especificados en los arrays proporcionados, permite ordenar los resultados,
 * y permite seleccionar columnas específicas.
 *
 * @param string $table Nombre de la tabla en la base de datos desde la cual se seleccionan los datos.
 * @param array $columns Array de nombres de columnas a las cuales se les aplicarán las condiciones.
 * @param array $comparisons Array de operadores de comparación para cada columna (por ejemplo, '=', '>', '<').
 * @param array $values Array de valores para comparar con cada columna.
 * @param array $orderBy Array de pares columna/orden para ordenar los resultados (por ejemplo, [['columna1', 'ASC'], ['columna3', 'DESC']]).
 * @param string $callType Tipo de llamada: 'ajax' para llamadas AJAX que devuelven datos en formato JSON, 
 *                         vacío para llamadas normales que devuelven un array PHP.
 * @param array $selectColumns Array de nombres de columnas que se desean obtener. Si no se pasa, se seleccionarán todas las columnas.
 * 
 * @return array|false Devuelve un array de resultados en formato asociativo si la llamada no es AJAX, 
 *                     o JSON en caso de llamada AJAX. Devuelve false en caso de error.
 *
 * $columns = ['edad', 'estado'];
 * $comparisons = ['>', '='];
 * $values = [25, 'activo'];
 * $orderBy = [['edad', 'ASC'], ['nombre', 'DESC']];
 * $selectColumns = ['nombre', 'edad'];
 * $data = db_select_with_filters('usuarios', $columns, $comparisons, $values, $orderBy, '', $selectColumns);
 *
 */

function db_select_with_filters($table, $columns = array(), $comparisons = array(), $values = array(), $orderBy = array(), $callType = '', $selectColumns = array()) {
    // Si el quinto parámetro es un array, asumimos que es $selectColumns y no $callType
    if (is_array($callType)) {
        $selectColumns = $callType;
        $callType = '';
    }

    // Verificar que los tres arrays tengan la misma cantidad de elementos
    if (count($columns) !== count($comparisons) || count($columns) !== count($values)) {
        $error_message = "Error: Los arrays 'columns', 'comparisons' y 'values' deben tener la misma cantidad de elementos.";
        if ($callType === 'ajax') {
            echo json_encode(array('error' => $error_message));
        } else {
            echo $error_message . PHP_EOL;
        }
        return false;
    }

    // Conectar a la base de datos
    $db = conectaDB();

    // Obtener el esquema de la tabla
    $schema = array();
    $result = mysqli_query($db, "SHOW COLUMNS FROM $table");
    while ($row = mysqli_fetch_assoc($result)) {
        $schema[$row['Field']] = $row['Type'];
    }
    mysqli_free_result($result);

    // Verificar y formatear los valores en $values según el tipo de datos de las columnas
    for ($i = 0; $i < count($columns); $i++) {
        $column = $columns[$i];
        $type = $schema[$column];
        $value = $values[$i];

        if (strpos($type, 'int') !== false || strpos($type, 'float') !== false || strpos($type, 'double') !== false) {
            if (!is_numeric($value)) {
                $error_message = "Error: El valor para la columna '$column' debe ser numérico.";
                if ($callType === 'ajax') {
                    echo json_encode(array('error' => $error_message));
                } else {
                    echo $error_message . PHP_EOL;
                }
                mysqli_close($db);
                return false;
            }
        } else {
            $value = mysqli_real_escape_string($db, $value);
            $values[$i] = "'$value'";
        }
    }

    // Construir la lista de columnas a seleccionar
    $selectClause = '*'; // Por defecto selecciona todas las columnas
    if (!empty($selectColumns)) {
        $selectClause = implode(', ', $selectColumns);
    }

    // Construir la consulta SQL básica
    $sql = "SELECT $selectClause FROM $table";

    // Añadir las condiciones a la consulta si hay elementos en los arrays
    if (!empty($columns)) {
        $sql .= " WHERE ";
        $conditions = array();
        for ($i = 0; $i < count($columns); $i++) {
            $column = $columns[$i];
            $comparison = $comparisons[$i];
            $value = $values[$i];
            $conditions[] = "$column $comparison $value";
        }
        $sql .= implode(' AND ', $conditions);
    }

    // Añadir la cláusula ORDER BY si hay elementos en $orderBy
    if (!empty($orderBy)) {
        $orderConditions = array();
        foreach ($orderBy as $order) {
            $orderColumn = $order[0];
            $orderDirection = strtoupper($order[1]) === 'DESC' ? 'DESC' : 'ASC';
            $orderConditions[] = "$orderColumn $orderDirection";
        }
        $sql .= " ORDER BY " . implode(', ', $orderConditions);
    }

    // Ejecutar la consulta
    $result = mysqli_query($db, $sql);

    // Verificar si la consulta tiene errores
    if (!$result) {
        $error_message = "Error en la consulta: " . mysqli_error($db);
        if ($callType === 'ajax') {
            echo json_encode(array('error' => $error_message));
        } else {
            echo $error_message . PHP_EOL;
        }
        mysqli_close($db);
        return false;
    }

    // Almacenar los resultados en un array
    $data = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    // Liberar el resultado y cerrar la conexión
    mysqli_free_result($result);
    mysqli_close($db);

    // Devolver los datos
    if ($callType === 'ajax') {
        echo json_encode($data);
    } else {
        return $data;
    }
}


function registrosToFilas($registros, $claves, $acciones = [], $clases = [], $trData = [], $callType = 'servidor') {
    // Si la llamada es de tipo AJAX, convierte los parámetros desde JSON a arrays PHP.
    if ($callType === 'ajax') {
        if (is_string($registros)) $registros = json_decode($registros, true);
        if (is_string($claves))   $claves = json_decode($claves, true);
        if (is_string($acciones)) $acciones = json_decode($acciones, true);
        if (is_string($clases))   $clases = json_decode($clases, true);
        if (is_string($trData))   $trData = json_decode($trData, true);
    }

    // Verificar que $registros sea realmente un array
    if (!is_array($registros)) {
        die(json_encode(['error' => 'El parámetro "registros" no es un array válido']));
    }

    // Verificamos si hay registros. Si no, devolvemos un mensaje.
    if (empty($registros)) {
        return ($callType === 'ajax') ? json_encode(['html' => "<p>No hay registros para mostrar.</p>"]) : "<p>No hay registros para mostrar.</p>";
    }

    // Si $claves está vacío, usamos las claves del primer registro como $claves.
    if (empty($claves)) {
        $claves = array_keys($registros[0]);
    }

    $filas = ''; // Inicializamos la variable que contendrá todas las filas generadas.
    $rowIndex = 0; // Inicializamos el índice de fila.

    foreach ($registros as $registro) {
        $filas .= '<tr'; // Comenzamos la fila de la tabla.

        // Procesamos los atributos `data-*` para la fila, basados en $trData.
        if (!empty($trData)) {
            foreach ($trData as $data) {
                if (isset($data[0]) && isset($data[1])) {
                    $dataKey = htmlspecialchars($data[1]);

                    // Si la clave existe en el registro, usamos su valor, de lo contrario, usamos la clave tal cual.
                    if (array_key_exists($dataKey, $registro)) {
                        $filas .= ' data-' . htmlspecialchars($data[0]) . '="' . htmlspecialchars($registro[$dataKey]) . '"';
                    } else {
                        $filas .= ' data-' . htmlspecialchars($data[0]) . '="' . $dataKey . '"';
                    }
                }
            }
        }

        $filas .= '>'; // Cerramos la etiqueta <tr>.

        // Procesamos cada clave y su correspondiente valor en el registro.
        foreach ($claves as $index => $clave) {
            $valor = '';

            // Si el elemento es un array, concatenamos los valores de las claves con el separador dado.
            if (is_array($clave)) {
                $separador = array_pop($clave); // El último elemento es el separador.
                $valoresConcatenados = [];

                foreach ($clave as $subClave) {
                    if (isset($registro[$subClave])) {
                        $valoresConcatenados[] = htmlspecialchars($registro[$subClave]);
                    }
                }

                // Concatenamos los valores con el separador.
                $valor = implode($separador, $valoresConcatenados);
            } else {
                // Si es un string, lo procesamos como antes.
                $valor = isset($registro[$clave]) ? htmlspecialchars($registro[$clave]) : '';
            }

            // Si el valor parece una fecha (YYYY-MM-DD), lo formateamos.
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
                $fecha = DateTime::createFromFormat('Y-m-d', $valor);
                if ($fecha) {
                    $valor = $fecha->format('d-m-Y');
                }
            }

            // Obtenemos las clases correspondientes a la clave.
            $clase = isset($clases[$index]) ? implode(' ', $clases[$index]) : '';
            $filas .= "<td class='" . htmlspecialchars($clase) . "'>" . $valor . "</td>";
        }

        // Procesamos las acciones si están definidas.
        if (!empty($acciones[0])) {
            $iconos = ''; // Inicializamos la variable para los iconos.
            foreach ($acciones[0] as $i => $icono) {
                $claseIcono = isset($acciones[1][$i]) ? implode(' ', $acciones[1][$i]) : ''; // Clases adicionales para el icono.
                $iconoHtml = "<i class='fa $icono " . htmlspecialchars($claseIcono) . "'";

                // Procesamos los pares de atributos `data-*` para cada icono.
                if (isset($acciones[2][$i]) && is_array($acciones[2][$i])) {
                    foreach ($acciones[2][$i] as $dataAttr) {
                        if (is_array($dataAttr) && count($dataAttr) >= 2) {
                            $dataAttrName = 'data-' . htmlspecialchars($dataAttr[0]);
                            $valorAttr = '';

                            for ($j = 1; $j < count($dataAttr); $j++) {
                                $parte = htmlspecialchars($dataAttr[$j]);

                                // Si es una clave del registro, obtenemos el valor correspondiente.
                                if (array_key_exists($parte, $registro)) {
                                    $parte = htmlspecialchars($registro[$parte]);
                                }
                                $valorAttr .= $parte;
                            }

                            // Agregamos el atributo `data-*` al HTML del icono.
                            $iconoHtml .= ' ' . $dataAttrName . '="' . $valorAttr . '"';
                        }
                    }
                }

                $iconoHtml .= "></i> "; // Cerramos la etiqueta del icono.
                $iconos .= $iconoHtml;
            }

            // Obtenemos el índice de la clase correspondiente al td de acciones.
            $indiceAcciones = count($claves);
            $claseAcciones = isset($clases[$indiceAcciones]) ? implode(' ', $clases[$indiceAcciones]) : '';

            // Aplicamos la clase al td de acciones solo si existe.
            $filas .= "<td class='" . htmlspecialchars($claseAcciones) . "'>" . trim($iconos) . "</td>";
        }

        $filas .= '</tr>'; // Cerramos la fila.
        $rowIndex++;
    }

    // Si la llamada es AJAX, devolver la respuesta como JSON.
    if ($callType === 'ajax') {
        // Establecer la cabecera para especificar que la respuesta es JSON
        header('Content-Type: application/json');

        // Generar la respuesta en JSON
        $response = json_encode(['html' => $filas]);

        // Asegurarse de que la respuesta sea exitosa y esté limpia
        if (json_last_error() !== JSON_ERROR_NONE) {
            die(json_encode(['error' => 'Error al codificar el JSON: ' . json_last_error_msg()]));
        }

        // Imprimir la respuesta y salir
        echo $response;
        exit;  // Asegurarse de que PHP termine la ejecución aquí para que no se añada más contenido inesperado
    }

    // Devolver las filas HTML generadas.
    return $filas;
}


/**
 * Enriquece un array de registros uniendo datos de varias tablas basadas en claves específicas.
 *
 * Esta función permite enriquecer un array de registros (`$arrayRegistros`) agregando columnas adicionales 
 * obtenidas desde otras tablas. Las claves de unión, tablas de destino y columnas a recuperar se especifican 
 * en un array (`$keysArray`). Además, permite manejar el tipo de respuesta en caso de ser invocada desde AJAX.
 * 
 * Los datos que se pueden unir incluyen valores únicos por clave y columnas especificadas, que se 
 * recuperan de las tablas correspondientes en la base de datos.
 * 
 * Parámetros:
 * - `$arrayRegistros`: Puede ser un array o una cadena JSON que contiene los registros a procesar.
 * - `$keysArray`: Puede ser un array o una cadena JSON que contiene la información de unión:
 *   - Clave de comparación (por ejemplo, `id`).
 *   - Tabla de origen de los datos.
 *   - Columna de comparación (columna que coincide con la clave).
 *   - Columnas adicionales que se deben obtener y añadir a `$arrayRegistros`.
 * - `$callType`: Tipo de llamada (opcional). Si se especifica como 'ajax', la respuesta será en formato JSON.
 *
 * Estructura del `$keysArray`:
 * ```php
 * [
 *   ['key', 'table_name', 'comparison_column', ['column1', 'column2', ...]],
 *   ['key2', 'table_name2', 'comparison_column2', ['column1', 'column2', ...]],
 *   ...
 * ]
 * ```
 * 
 * Ejemplo de uso:
 * ```php
 * $arrayRegistros = [
 *   ['id' => 1, 'nombre' => 'John'],
 *   ['id' => 2, 'nombre' => 'Jane']
 * ];
 * $keysArray = [
 *   ['id', 'users_details', 'user_id', ['age', 'city']]
 * ];
 * $result = arrayJoin($arrayRegistros, $keysArray);
 * // Resultado:
 * // [
 * //   ['id' => 1, 'nombre' => 'John', 'age' => 25, 'city' => 'New York'],
 * //   ['id' => 2, 'nombre' => 'Jane', 'age' => 30, 'city' => 'Los Angeles']
 * // ]
 * ```
 * 
 * @param array|string $arrayRegistros Array de registros o cadena JSON que contiene los registros a procesar.
 * @param array|string $keysArray Array o cadena JSON con la información de unión para las tablas y columnas.
 * @param string $callType (opcional) Tipo de llamada: si es 'ajax', devuelve el resultado en formato JSON.
 * @return array El array enriquecido con los datos adicionales obtenidos de las tablas especificadas.
 * @throws Exception Si ocurre un error al decodificar JSON o al ejecutar una consulta.
 */


function arrayJoin($arrayRegistros, $keysArray, $callType = '') {
        // Verificar si $arrayRegistros es una cadena JSON y decodificarla
    if (is_string($arrayRegistros)) {
        $decoded = json_decode($arrayRegistros, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $arrayRegistros = $decoded;
        } else {
            throw new Exception('Error al decodificar JSON en $arrayRegistros: ' . json_last_error_msg());
        }
    }

    // Verificar si $keysArray es una cadena JSON y decodificarla
    if (is_string($keysArray)) {
        $decoded = json_decode($keysArray, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $keysArray = $decoded;
        } else {
            throw new Exception('Error al decodificar JSON en $keysArray: ' . json_last_error_msg());
        }
    }

    // Establecer conexión a la base de datos
    $db = conectaDB();
    if (!$db) {
        throw new Exception('Error al conectar a la base de datos.');
    }

    // Recorrer $keysArray para realizar las uniones correspondientes
    foreach ($keysArray as $keyInfo) {
        list($key, $table, $comparisonColumn, $columns) = $keyInfo;

        // Obtener todos los valores únicos de la clave
        $ids = array_unique(array_column($arrayRegistros, $key));

        // Construir la consulta para obtener todos los registros necesarios
        $columnList = implode(', ', array_map([$db, 'real_escape_string'], $columns));
        $idsList = implode(', ', array_map([$db, 'real_escape_string'], $ids));

        $query = "SELECT $comparisonColumn, $columnList FROM $table WHERE $comparisonColumn IN ($idsList)";
        $result = $db->query($query);
        if ($result) {
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[$row[$comparisonColumn]] = $row;
            }

            // Enriquecer los registros
            foreach ($arrayRegistros as &$registro) {
                $id = $registro[$key];
                if (isset($data[$id])) {
                    foreach ($columns as $column) {
                        $registro[$column] = $data[$id][$column];
                    }
                }
            }
        } else {
            throw new Exception('Error en la consulta: ' . $db->error);
        }
    }

    // Cerrar la conexión a la base de datos
    $db->close();
    // Evaluar el valor de $callType
        if ($callType === 'ajax') {
            header('Content-Type: application/json');
            
            $jsonResponse = json_encode([
                'status' => 'success',
                'data' => $arrayRegistros
            ]);
            
            if ($jsonResponse === false) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al codificar JSON: ' . json_last_error_msg()
                ]);
            } else {
                echo $jsonResponse;
            }
            exit;
        }
    // Si $callType está vacío, devolver el array directamente
    return $arrayRegistros;
}

/**
 * Obtiene la fecha actual del sistema en el formato especificado.
 * 
 * Formatos soportados:
 * "AAAA-MM-DD", "AA-MM-DD", "DD/MM/AAAA", "DD/MM/AA", "DD-MM-AAAA", 
 * "DD-MM-AA", "AAAA/MM", "AAAA-MM", "MM/AAAA", "MM-AAAA", "MM/DD", 
 * "MM-DD", "DD/MM", "DD-MM", "DD"
 * 
 * @param string $formato - El formato deseado para la fecha.
 * @return string La fecha formateada según el formato especificado.
 * @throws Exception Si el formato no está soportado o está vacío.
 */
function fechaActual($formato) {
    // Validar que el formato no esté vacío
    if (empty($formato)) {
        throw new Exception("El formato no puede estar vacío");
    }

    $fecha = new DateTime();
    $dia = $fecha->format('d');
    $mes = $fecha->format('m');
    $anio = $fecha->format('Y');  // Cambio de $año a $anio
    $anioCorto = substr($anio, -2);

    switch ($formato) {
        case "AAAA-MM-DD":
            // Formato: 2024-09-01
            return "{$anio}-{$mes}-{$dia}";
        case "AA-MM-DD":
            // Formato: 24-09-01
            return "{$anioCorto}-{$mes}-{$dia}";
        case "DD/MM/AAAA":
            // Formato: 01/09/2024
            return "{$dia}/{$mes}/{$anio}";
        case "DD/MM/AA":
            // Formato: 01/09/24
            return "{$dia}/{$mes}/{$anioCorto}";
        case "DD-MM-AAAA":
            // Formato: 01-09-2024
            return "{$dia}-{$mes}-{$anio}";
        case "DD-MM-AA":
            // Formato: 01-09-24
            return "{$dia}-{$mes}-{$anioCorto}";
        case "AAAA/MM":
            // Formato: 2024/09
            return "{$anio}/{$mes}";
        case "AAAA-MM":
            // Formato: 2024-09
            return "{$anio}-{$mes}";
        case "MM/AAAA":
            // Formato: 09/2024
            return "{$mes}/{$anio}";
        case "MM-AAAA":
            // Formato: 09-2024
            return "{$mes}-{$anio}";
        case "MM/DD":
            // Formato: 09/01
            return "{$mes}/{$dia}";
        case "MM-DD":
            // Formato: 09-01
            return "{$mes}-{$dia}";
        case "DD/MM":
            // Formato: 01/09
            return "{$dia}/{$mes}";
        case "DD-MM":
            // Formato: 01-09
            return "{$dia}-{$mes}";
        case "DD":
            // Formato: 01
            return "{$dia}";
        default:
            throw new Exception("Formato no soportado");
    }
}

/**
 * Obtiene la fecha actual del sistema ajustada por un lapso de tiempo
 * en días, semanas, meses o años, y la devuelve en el formato especificado.
 * 
 * Formatos soportados:
 * "AAAA-MM-DD", "AA-MM-DD", "DD/MM/AAAA", "DD/MM/AA", "DD-MM-AAAA", 
 * "DD-MM-AA", "AAAA/MM", "AAAA-MM", "MM/AAAA", "MM-AAAA", "MM/DD", 
 * "MM-DD", "DD/MM", "DD-MM", "DD"
 * 
 * Unidades de tiempo soportadas:
 * "dias", "semanas", "meses", "años"
 * 
 * @param string $formato - El formato deseado para la fecha.
 * @param int $lapso - El número de unidades de tiempo a ajustar. Puede ser positivo o negativo.
 * @param string $unidad - La unidad de tiempo para ajustar la fecha. Puede ser "dias", "semanas", "meses", "años".
 * @return string La fecha ajustada y formateada según el formato especificado.
 * @throws Exception Si el formato o la unidad de tiempo no están soportados.
 *
 * fechaAjustada("AAAA-MM-DD", 10, "dias"); // Supongamos que hoy es 2024-09-01, esto devolverá "2024-09-11".
 */
function fechaAjustada($formato, $lapso, $unidad) {
    // Validar que el formato no esté vacío
    if (empty($formato)) {
        throw new Exception("El formato no puede estar vacío");
    }

    // Crear un objeto DateTime con la fecha actual
    $fecha = new DateTime();

    // Ajustar la fecha según el lapso y la unidad de tiempo
    switch ($unidad) {
        case "dias":
            $fecha->modify("{$lapso} days");
            break;
        case "semanas":
            $fecha->modify("{$lapso} weeks");
            break;
        case "meses":
            $fecha->modify("{$lapso} months");
            break;
        case "años":
            $fecha->modify("{$lapso} years");
            break;
        default:
            throw new Exception("Unidad de tiempo no soportada");
    }

    // Formatear la fecha ajustada según el formato especificado
    $dia = $fecha->format('d');
    $mes = $fecha->format('m');
    $anio = $fecha->format('Y');
    $anioCorto = substr($anio, -2);

    switch ($formato) {
        case "AAAA-MM-DD":
            return "{$anio}-{$mes}-{$dia}";
        case "AA-MM-DD":
            return "{$anioCorto}-{$mes}-{$dia}";
        case "DD/MM/AAAA":
            return "{$dia}/{$mes}/{$anio}";
        case "DD/MM/AA":
            return "{$dia}/{$mes}/{$anioCorto}";
        case "DD-MM-AAAA":
            return "{$dia}-{$mes}-{$anio}";
        case "DD-MM-AA":
            return "{$dia}-{$mes}-{$anioCorto}";
        case "AAAA/MM":
            return "{$anio}/{$mes}";
        case "AAAA-MM":
            return "{$anio}-{$mes}";
        case "MM/AAAA":
            return "{$mes}/{$anio}";
        case "MM-AAAA":
            return "{$mes}-{$anio}";
        case "MM/DD":
            return "{$mes}/{$dia}";
        case "MM-DD":
            return "{$mes}-{$dia}";
        case "DD/MM":
            return "{$dia}/{$mes}";
        case "DD-MM":
            return "{$dia}-{$mes}";
        case "DD":
            return "{$dia}";
        case "timestamp":
            return $fecha->format('Y-m-d H:i:s'); // Devuelve en formato "YYYY-MM-DD HH:MM:SS"
        default:
            throw new Exception("Formato no soportado");
    }
}

/**
 * Obtiene la hora actual del sistema en el formato especificado.
 * 
 * Formatos soportados:
 * "HH:MM:SS", "HH:MM", "HH/MM/SS", "HH-MM-SS", "HH-MM", "HH/MM", "H:MM", "H/MM"
 * 
 * @param string $formato - El formato deseado para la hora.
 * @return string La hora formateada según el formato especificado.
 * @throws Exception Si el formato no está soportado.
 */
function horaActual($formato) {
    $hora = new DateTime();
    $horas = $hora->format('H');
    $minutos = $hora->format('i');
    $segundos = $hora->format('s');
    $horaCorta = $hora->format('G'); // Horas sin relleno de 0

    switch ($formato) {
        case "HH:MM:SS":
            return "{$horas}:{$minutos}:{$segundos}";
        case "HH:MM":
            return "{$horas}:{$minutos}";
        case "HH/MM/SS":
            return "{$horas}/{$minutos}/{$segundos}";
        case "HH-MM-SS":
            return "{$horas}-{$minutos}-{$segundos}";
        case "HH-MM":
            return "{$horas}-{$minutos}";
        case "HH/MM":
            return "{$horas}/{$minutos}";
        case "H:MM":
            return "{$horaCorta}:{$minutos}";
        case "H/MM":
            return "{$horaCorta}/{$minutos}";
        default:
            throw new Exception("Formato no soportado");
    }
}


/**
 * Función db_select_with_filters_V2
 *
 * Esta función realiza una consulta SELECT a una tabla de base de datos MySQL con filtros personalizados,
 * seleccionando las columnas deseadas, aplicando condiciones y permitiendo un ordenamiento opcional.
 * También admite llamadas AJAX para devolver los resultados en formato JSON.
 *
 * @param string $table El nombre de la tabla de la base de datos de la cual se seleccionarán los registros.
 * @param array $columns Array que contiene los nombres de las columnas a usar como filtro en la consulta. (Opcional)
 * @param array $comparisons Array que define las comparaciones a utilizar (por ejemplo, '=', '>', '<'). (Opcional)
 * @param array $values Array con los valores que se compararán contra las columnas definidas en $columns. (Opcional)
 * @param array $orderBy Array de arrays que define las columnas y la dirección de ordenamiento ('ASC', 'DESC'). (Opcional)
 * @param string $callType Define si la llamada es desde AJAX. Si es 'ajax', devuelve los resultados en JSON. (Opcional)
 * @param array $selectColumns Array opcional con los nombres de las columnas que se desean obtener. Si está vacío, selecciona todas las columnas. (Opcional)
 *
 * @return array|bool Devuelve los resultados en un array asociativo si no es una llamada AJAX, o JSON si se especifica 'ajax'.
 *                    Devuelve false si ocurre un error en la ejecución de la consulta.
 *
 * Ejemplo de uso 1: Sin seleccionar columnas específicas ni ordenamiento.
 * $resultados = db_select_with_filters_V2('usuarios', ['nombre', 'edad'], ['=', '>'], ['John', 25]);
 *
 * Ejemplo de uso 2: Seleccionando columnas específicas y aplicando un orden.
 * $resultados = db_select_with_filters_V2('usuarios', ['nombre', 'edad'], ['=', '>'], ['John', 25], [['edad', 'ASC']], '', ['nombre', 'edad']);
 *
 * Ejemplo de uso 3: Llamada AJAX.
 * db_select_with_filters_V2('usuarios', ['nombre', 'edad'], ['=', '>'], ['John', 25], [['edad', 'ASC']], 'ajax');
 *
 * Ejemplo de uso 4: Sin filtros ni columnas seleccionadas.
 * db_select_with_filters_V2('usuarios');
 * 
 * - En este caso, la función seleccionará todas las columnas de la tabla `usuarios` y devolverá todos los registros.
 * - No se aplicarán filtros ya que los arrays `$columns`, `$comparisons` y `$values` están vacíos.
 * - No se aplicará ningún ordenamiento ya que `$orderBy` está vacío.
 * - La consulta resultante será: "SELECT * FROM `usuarios`".
 * - Si no se especifica 'ajax' en `$callType`, el resultado será devuelto como un array asociativo en PHP.
 */

function db_select_with_filters_V2($table, $columns = [], $comparisons = [], $values = [], $orderBy = [], $callType = '', $selectColumns = []) {
    // Verificar que los arrays $columns, $comparisons y $values tengan la misma cantidad de elementos
    if (count($columns) !== count($comparisons) || count($columns) !== count($values)) {
        $error_message = "Error: Los arrays 'columns', 'comparisons' y 'values' deben tener la misma cantidad de elementos.";
        if ($callType === 'ajax') {
            echo json_encode(['error' => $error_message]);
        } else {
            echo $error_message . PHP_EOL;
        }
        return false;
    }

    // Conectar a la base de datos
    $db = conectaDB();

    // Establecer el conjunto de caracteres a utf8mb4
    $db->set_charset('utf8mb4');

    // Construir la lista de columnas a seleccionar
    $selectClause = '*'; // Por defecto selecciona todas las columnas
    if (!empty($selectColumns)) {
        $escapedColumns = array_map(function($col) use ($db) {
            return "`" . $db->real_escape_string($col) . "`";
        }, $selectColumns);
        $selectClause = implode(', ', $escapedColumns);
    }

    // Construir la consulta SQL básica con placeholders
    $sql = "SELECT $selectClause FROM `" . $db->real_escape_string($table) . "`";
    $conditions = [];
    $param_types = '';  // Cadena para los tipos de parámetros de bind_param
    $param_values = [];  // Array para almacenar los valores de los parámetros

    // Añadir las condiciones a la consulta si hay elementos en los arrays
    if (!empty($columns)) {
        $sql .= " WHERE ";
        for ($i = 0; $i < count($columns); $i++) {
            $column = $db->real_escape_string($columns[$i]);
            $comparison = $comparisons[$i];
            $conditions[] = "`$column` $comparison ?";

            // Determinar el tipo de parámetro y agregar el valor
            if (is_int($values[$i])) {
                $param_types .= 'i';
                $param_values[] = $values[$i];
            } elseif (is_float($values[$i])) {
                $param_types .= 'd';
                $param_values[] = $values[$i];
            } else {
                $param_types .= 's';
                $param_values[] = $values[$i];
            }
        }
        $sql .= implode(' AND ', $conditions);
    }

    // Añadir la cláusula ORDER BY si hay elementos en $orderBy
    if (!empty($orderBy)) {
        $orderConditions = [];
        foreach ($orderBy as $order) {
            $orderColumn = $db->real_escape_string($order[0]);
            $orderDirection = strtoupper($order[1]) === 'DESC' ? 'DESC' : 'ASC';
            $orderConditions[] = "`$orderColumn` $orderDirection";
        }
        $sql .= " ORDER BY " . implode(', ', $orderConditions);
    }

    // Preparar la consulta
    $stmt = $db->prepare($sql);

    // Verificar si la consulta se preparó correctamente
    if (!$stmt) {
        $error_message = "Error en la preparación de la consulta: " . $db->error;
        if ($callType === 'ajax') {
            echo json_encode(['error' => $error_message]);
        } else {
            echo $error_message . PHP_EOL;
        }
        $db->close();
        return false;
    }

    // Vincular los parámetros si es necesario
    if (!empty($columns)) {
        $stmt->bind_param($param_types, ...$param_values);
    }

        // Mostrar consultas
        //dump($sql);

        // Mostrar los tipos de parámetros
        //dump($param_types);

        // Mostrar los valores de los parámetros
        //dd($param_values);

    // Ejecutar la consulta
    if (!$stmt->execute()) {
        $error_message = "Error en la ejecución de la consulta: " . $stmt->error;
        if ($callType === 'ajax') {
            echo json_encode(['error' => $error_message]);
        } else {
            echo $error_message . PHP_EOL;
        }
        $stmt->close();
        $db->close();
        return false;
    }

    // Obtener los resultados
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    // Cerrar la conexión y la sentencia
    $stmt->close();
    $db->close();

    // Devolver los datos en el formato correcto
    if ($callType === 'ajax') {
        header('Content-Type: application/json');
        echo json_encode($data);
    } else {
        return $data;
    }
}

function controlarDepuracion() {
    $logFilePath = '../log/depuracion_log.txt';  // Archivo de log
    $db = conectaDB();  // Conexión a la BD

    try {
        // Obtener la última fecha de depuración
        $query = "SELECT ultima_depuracion FROM control_depuracion LIMIT 1;";
        $result = $db->query($query);
        $lastDepuration = $result->fetch_assoc();
        $ultimaDepuracion = $lastDepuration ? $lastDepuration['ultima_depuracion'] : '1970-01-01 00:00:00';
        $horaActual = date('Y-m-d H:i:s');

        // Eliminar registros duplicados desde la última depuración
        $queryDepuracion = "
            DELETE np1 FROM novedades_personal_2 np1
            INNER JOIN (
                SELECT id_usuario, fecha, MAX(id_novedad_per) as id_max
                FROM novedades_personal_2
                WHERE fecha >= '$ultimaDepuracion'  -- Solo desde la última depuración
                GROUP BY id_usuario, fecha
            ) np2
            ON np1.id_usuario = np2.id_usuario
            AND np1.fecha = np2.fecha
            AND np1.id_novedad_per < np2.id_max;
        ";

        $result = $db->query($queryDepuracion);
        $registrosEliminados = $db->affected_rows;

        // Actualizar la fecha de última depuración
        $queryUpdate = "UPDATE control_depuracion SET ultima_depuracion = '$horaActual';";
        $db->query($queryUpdate);

        // Registrar en el log
        if ($registrosEliminados > 0) {
            registrarLog($logFilePath, "🔥 Depuración realizada. Eliminados: $registrosEliminados duplicados.");
        } else {
            registrarLog($logFilePath, "✅ Depuración realizada. No se encontraron duplicados.");
        }

    } catch (Exception $e) {
        registrarLog($logFilePath, "❌ ERROR en la depuración: " . $e->getMessage());
    } finally {
        $db->close();
    }
}


// Función auxiliar para registrar en el log
function registrarLog($archivo, $mensaje) {
    $hora = date('[Y-m-d H:i:s]');
    file_put_contents($archivo, "$hora $mensaje" . PHP_EOL, FILE_APPEND | LOCK_EX);
}
