<?php
/**
 * enumToSelect
 * 
 * Esta función genera los <option> de un campo ENUM en una tabla de base de datos,
 * permitiendo diferentes tipos de salida: HTML (para PHP) o JSON (para AJAX).
 * 
 * @param string $tabla Nombre de la tabla en la base de datos que contiene el campo ENUM.
 * @param string $campo Nombre del campo ENUM dentro de la tabla.
 * @param string $leyendaOptionUno Texto que aparecerá en el primer <option>, 
 *                                 deshabilitado y seleccionado por defecto.
 * @param string $orden Define el orden de los valores ENUM. Puede ser 'asc' (ascendente) o 'desc' (descendente).
 *                     Por defecto, el valor es 'asc'.
 * @param string $tipoSalida Define el tipo de salida que devolverá la función.
 *                           'php' para generar el HTML de las opciones (por defecto).
 *                           'ajax' para devolver un array JSON con los valores ENUM.
 * 
 * @return string|JSON Devuelve el HTML de los <option> o un array en formato JSON, 
 *                     dependiendo del valor de $tipoSalida.
 * 
 * @throws Exception Si la tabla o el campo no existen o si el campo no es de tipo ENUM.
 */
function enumToSelect($tabla, $campo, $leyendaOptionUno, $orden = 'asc', $tipoSalida = 'php') {
    // Conectar a la base de datos
    $conexion = conectaDB(); // Asumimos que conectaDB() es una función que ya existe en tu proyecto

    // Verificar si la tabla existe
    $verificaTabla = "SHOW TABLES LIKE '$tabla'";
    $resultadoTabla = $conexion->query($verificaTabla);

    if ($resultadoTabla->num_rows == 0) {
        throw new Exception("La tabla '$tabla' no existe en la base de datos.");
    }

    // Verificar si el campo existe y es de tipo ENUM
    $query = "SHOW COLUMNS FROM `$tabla` LIKE '$campo'";
    $resultadoCampo = $conexion->query($query);

    if ($resultadoCampo->num_rows == 0) {
        throw new Exception("El campo '$campo' no existe en la tabla '$tabla'.");
    }

    $fila = $resultadoCampo->fetch_assoc();

    // Verificar si el campo es de tipo ENUM
    if (strpos($fila['Type'], 'enum') === false) {
        throw new Exception("El campo '$campo' en la tabla '$tabla' no es de tipo ENUM.");
    }

    // Extraer los valores del ENUM y quitar las comillas innecesarias
    preg_match("/^enum\(\'(.*)\'\)$/", $fila['Type'], $matches);
    $valoresEnum = explode("','", $matches[1]);

    // Ordenar según el parámetro
    if ($orden == 'desc') {
        rsort($valoresEnum);
    } else {
        sort($valoresEnum);
    }

    // Si el tipo de salida es 'ajax', devolver en formato JSON
    if ($tipoSalida == 'ajax') {
        // Incluir la opción deshabilitada como primer elemento
        $valoresEnum = array_merge(['' => $leyendaOptionUno], array_combine($valoresEnum, $valoresEnum));
        return json_encode($valoresEnum);
    }

    // Iniciar el string con el primer option deshabilitado y seleccionado (para 'php')
    $html = "<option value='' disabled selected>$leyendaOptionUno</option>\n";

    // Generar los demás options
    foreach ($valoresEnum as $valor) {
        $html .= "<option value='$valor'>$valor</option>\n";
    }

    // Retornar el HTML generado si es para 'php'
    return $html;
}
