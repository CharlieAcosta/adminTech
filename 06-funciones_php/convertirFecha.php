<?php
/**
 * Convierte una cadena de texto que representa una fecha a un formato específico.
 * 
 * @param string $fechaStr - La fecha en formato string. Puede aceptar varios formatos de entrada, como:
 *                           '2023-09-12' (Año-Mes-Día),
 *                           '12/09/2023' (Día/Mes/Año),
 *                           '09-12-2023' (Mes-Día-Año).
 * 
 * @param string $formatoDestino - El formato en el que se desea la fecha de salida. Los formatos deben utilizar:
 *                                 - 'DD' para el día (dos dígitos).
 *                                 - 'MM' para el mes (dos dígitos).
 *                                 - 'YYYY' para el año completo.
 *                                 - 'YY' para los dos últimos dígitos del año.
 * 
 *                                 Ejemplos de formatos de salida:
 *                                 - 'DD/MM/YYYY' -> Resultado: 12/09/2023
 *                                 - 'YYYY-MM-DD' -> Resultado: 2023-09-12
 *                                 - 'MM-DD-YY'   -> Resultado: 09-12-23
 *                                 - 'DD-MM-YYYY' -> Resultado: 12-09-2023
 *                                 - 'MM/DD/YYYY' -> Resultado: 09/12/2023
 * 
 * @return string La fecha en el nuevo formato, o 'Fecha inválida' si la fecha proporcionada no es válida.
 * 
 * Ejemplos de uso:
 * echo convertirFecha('2023-09-12', 'DD/MM/YYYY'); // Resultado: 12/09/2023
 * echo convertirFecha('12/09/2023', 'YYYY-MM-DD'); // Resultado: 2023-09-12
 * echo convertirFecha('2023-09-12', 'MM-DD-YY');   // Resultado: 09-12-23
 * echo convertirFecha('09/12/2023', 'DD-MM-YYYY'); // Resultado: 12-09-2023
 * echo convertirFecha('2023-09-12', 'MM/DD/YYYY'); // Resultado: 09/12/2023
 */

function convertirFecha($fechaStr, $formatoDestino) {
    // Intentamos convertir la cadena de texto en un objeto DateTime
    $fecha = DateTime::createFromFormat('Y-m-d', $fechaStr) ?: DateTime::createFromFormat('d/m/Y', $fechaStr) ?: DateTime::createFromFormat('m-d-Y', $fechaStr);
    
    // Verificamos si la fecha es válida
    if (!$fecha) {
        return "Fecha inválida";
    }
    
    // Reemplazamos el formato destino con los valores correspondientes de la fecha
    $dia = $fecha->format('d');  // Día con dos dígitos
    $mes = $fecha->format('m');  // Mes con dos dígitos
    $año = $fecha->format('Y');  // Año completo (4 dígitos)
    $añoCorto = $fecha->format('y');  // Año corto (2 dígitos)

    // Reemplazamos los placeholders en el formato destino
    $fechaFormateada = str_replace(
        ['DD', 'MM', 'YYYY', 'YY'], 
        [$dia, $mes, $año, $añoCorto], 
        $formatoDestino
    );

    return $fechaFormateada;
}
