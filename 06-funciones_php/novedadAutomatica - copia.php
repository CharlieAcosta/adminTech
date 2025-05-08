<?php

/**
 * Función novedadAutomatica
 * 
 * Esta función procesa un array de registros de usuarios para determinar si han cumplido con las horas mínimas
 * de trabajo en días anteriores a la fecha actual. Si el usuario cumple con las 6 horas o más en un día, 
 * sus registros se almacenan en el array `$registrosParaNovedades`. Los registros correspondientes a la fecha 
 * actual se mueven directamente al array `$registrosParaAEO` sin ser procesados.
 * 
 * Parámetros:
 * 
 * @param array $registros Array de registros, donde cada registro debe contener al menos las siguientes claves:
 *     - 'obas_id' (string): El ID de la obra.
 *     - 'obas_id_usuario' (string): El ID del usuario.
 *     - 'obas_obra_id' (string): El ID de la obra.
 *     - 'obas_fecha' (string): La fecha del registro en formato 'Y-m-d H:i:s'.
 *     - 'obas_hora' (string): La hora del registro en formato 'H:i:s'.
 *     - 'obas_estado' (string): Estado del registro ('Entrada' o 'Salida').
 * 
 * Retorno:
 * 
 * @return array Un array asociativo con dos elementos:
 *     - 'registrosParaNovedades' (array): Contiene los usuarios y fechas donde se cumplieron las 6 o más horas de trabajo.
 *     - 'registrosParaAEO' (array): Contiene todos los registros (entradas y salidas) correspondientes a la fecha actual,
 *       y los registros de días anteriores donde el usuario no cumplió con las 6 horas.
 * 
 * Ejemplo de uso:
 * 
 * $registros = [
 *     [
 *         'obas_id' => '247',
 *         'obas_id_usuario' => '3',
 *         'obas_obra_id' => '6',
 *         'obas_fecha' => '2024-09-04 00:01:22',
 *         'obas_hora' => '00:01:22',
 *         'obas_estado' => 'Entrada'
 *     ],
 *     // Otros registros...
 * ];
 * 
 * $resultado = novedadAutomatica($registros);
 * 
 * // Acceder a los usuarios y fechas que cumplieron con las horas mínimas
 * $registrosParaNovedades = $resultado['registrosParaNovedades'];
 * 
 * // Acceder a los registros que no cumplieron (incluyendo la fecha actual)
 * $registrosParaAEO = $resultado['registrosParaAEO'];
 * 
 */


function novedadAutomatica($registros) {
    // Arrays para almacenar los resultados con los nuevos nombres
    $registrosParaNovedades = [];
    $registrosParaAEO = [];

    // Fecha actual
    $fechaHoy = date('Y-m-d');

    // Variables para guardar la primera entrada y la última salida del día
    $entradaTemprana = null;
    $salidaTardia = null;
    $fechaActual = '';
    $usuarioActual = '';

    // Recorremos los registros para ir acumulando los que no cumplen
    foreach ($registros as $registro) {
        $fecha = date('Y-m-d', strtotime($registro['obas_fecha']));
        $usuario = $registro['obas_id_usuario'];
        $estado = $registro['obas_estado'];

        // Si es la fecha actual, pasa directamente a registrosParaAEO
        if ($fecha == $fechaHoy) {
            $registrosParaAEO[] = $registro;
            continue; // Saltamos el procesamiento de este registro
        }

        // Cuando el usuario o la fecha cambian, revisamos si cumplió las 6 horas
        if ($usuario !== $usuarioActual || $fecha !== $fechaActual) {
            if ($entradaTemprana && $salidaTardia) {
                $diferenciaHoras = (strtotime($salidaTardia) - strtotime($entradaTemprana)) / 3600;

                // Si cumple con las 6 o más horas, lo guardamos en registrosParaNovedades
                if ($diferenciaHoras >= 6) {
                    $registrosParaNovedades[] = [
                        'usuario_id' => $usuarioActual,
                        'fecha' => $fechaActual
                    ];
                }
            }

            // Actualizamos las variables con los valores actuales
            $entradaTemprana = null;
            $salidaTardia = null;
            $fechaActual = $fecha;
            $usuarioActual = $usuario;
        }

        // Registramos la entrada y salida
        if ($estado == 'Entrada') {
            if (!$entradaTemprana || strtotime($registro['obas_hora']) < strtotime($entradaTemprana)) {
                $entradaTemprana = $registro['obas_hora'];
            }
        } elseif ($estado == 'Salida') {
            if (!$salidaTardia || strtotime($registro['obas_hora']) > strtotime($salidaTardia)) {
                $salidaTardia = $registro['obas_hora'];
            }
        }

        // Mientras procesamos, guardamos temporalmente los registros en registrosParaAEO
        $registrosParaAEO[] = $registro;
    }

    // Revisión final para el último usuario y día procesados
    if ($entradaTemprana && $salidaTardia) {
        $diferenciaHoras = (strtotime($salidaTardia) - strtotime($entradaTemprana)) / 3600;

        if ($diferenciaHoras >= 6) {
            $registrosParaNovedades[] = [
                'usuario_id' => $usuarioActual,
                'fecha' => $fechaActual
            ];
        }
    }

    // Filtramos los registros de registrosParaAEO
    foreach ($registrosParaNovedades as $cumple) {
        // Eliminamos todos los registros del usuario para esa fecha en registrosParaAEO
        $registrosParaAEO = array_filter($registrosParaAEO, function ($registro) use ($cumple) {
            return !(
                $registro['obas_id_usuario'] == $cumple['usuario_id'] &&
                date('Y-m-d', strtotime($registro['obas_fecha'])) == $cumple['fecha']
            );
        });
    }

    // Reindexamos los arrays después del filtrado
    $registrosParaAEO = array_values($registrosParaAEO);

    return [
        'registrosParaNovedades' => $registrosParaNovedades,
        'registrosParaAEO' => $registrosParaAEO
    ];
}


?>
