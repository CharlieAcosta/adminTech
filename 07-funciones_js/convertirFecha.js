/**
 * Convierte una cadena de texto que representa una fecha a un formato específico.
 * 
 * @param {string} fechaStr - La fecha en formato string. Puede aceptar varios formatos de entrada dependiendo de la capacidad del objeto Date.
 *                            Ejemplos de entrada: 
 *                            '2023-09-12' (Año-Mes-Día),
 *                            '12/09/2023' (Día/Mes/Año),
 *                            '09-12-2023' (Mes-Día-Año).
 * 
 * @param {string} formatoDestino - El formato en el que se desea la fecha de salida. Los formatos deben utilizar:
 *                                  - 'DD' para el día (dos dígitos).
 *                                  - 'MM' para el mes (dos dígitos).
 *                                  - 'YYYY' para el año completo.
 *                                  - 'YY' para los dos últimos dígitos del año.
 * 
 *                                  Ejemplos de formatos de salida:
 *                                  - 'DD/MM/YYYY' -> Resultado: 12/09/2023
 *                                  - 'YYYY-MM-DD' -> Resultado: 2023-09-12
 *                                  - 'MM-DD-YY'   -> Resultado: 09-12-23
 *                                  - 'DD-MM-YYYY' -> Resultado: 12-09-2023
 *                                  - 'MM/DD/YYYY' -> Resultado: 09/12/2023
 * 
 * @return {string} La fecha en el nuevo formato, o 'Fecha inválida' si la fecha proporcionada no es válida.
 * 
 * Ejemplos de uso:
 * console.log(convertirFecha('2023-09-12', 'DD/MM/YYYY')); // Resultado: 12/09/2023
 * console.log(convertirFecha('12/09/2023', 'YYYY-MM-DD')); // Resultado: 2023-09-12
 * console.log(convertirFecha('2023-09-12', 'MM-DD-YY'));   // Resultado: 09-12-23
 * console.log(convertirFecha('09/12/2023', 'DD-MM-YYYY')); // Resultado: 12-09-2023
 * console.log(convertirFecha('2023-09-12', 'MM/DD/YYYY')); // Resultado: 09/12/2023
 */

function convertirFecha(fechaStr, formatoDestino) {
    // Primero intentamos convertir el string a un objeto Date usando la zona horaria local
    let partes = fechaStr.split(/[-/]/); // Dividimos por '-' o '/'
    let fecha;
    
    // Comprobamos el formato de la fecha de entrada para manejar correctamente diferentes casos
    if (fechaStr.includes('-')) {
        // Formato Año-Mes-Día (YYYY-MM-DD)
        fecha = new Date(partes[0], partes[1] - 1, partes[2]);
    } else if (fechaStr.includes('/')) {
        // Formato Día/Mes/Año (DD/MM/YYYY)
        fecha = new Date(partes[2], partes[1] - 1, partes[0]);
    } else {
        return "Formato de fecha no reconocido";
    }

    // Verificamos si la fecha es válida
    if (isNaN(fecha)) {
        return "Fecha inválida";
    }

    // Función auxiliar para rellenar con ceros si es necesario (para días y meses)
    function agregarCero(numero) {
        return numero < 10 ? '0' + numero : numero;
    }

    // Extraemos los componentes de la fecha
    let dia = agregarCero(fecha.getDate());
    let mes = agregarCero(fecha.getMonth() + 1); // getMonth() devuelve 0-11
    let año = fecha.getFullYear();

    // Reemplazamos el formato por el formato destino
    let fechaFormateada = formatoDestino
        .replace('DD', dia)
        .replace('MM', mes)
        .replace('YYYY', año)
        .replace('YY', año.toString().slice(-2));

    return fechaFormateada;
}

