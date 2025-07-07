<?php
// file: novedadAutomatica.php
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
 */

function novedadAutomatica($registros, $feriados_js) {
    $registrosParaNovedades = [];
    $registrosParaAEO = [];
    $fechaHoy = date('Y-m-d');
    $entradaTemprana = null;
    $salidaTardia = null;
    $fechaActual = '';
    $usuarioActual = '';

    // --- NORMALIZACIÓN DE FECHAS DE FERIADOS ---
    foreach ($feriados_js as &$feriado) {
         if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $feriado[0])) {
             $partes = explode('-', $feriado[0]);
             $feriado[0] = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
         }
    }
    unset($feriado); // buena práctica con referencias en PHP
    // --- FIN NORMALIZACIÓN ---
    

    foreach ($registros as $registro) {
        $fecha = date('Y-m-d', strtotime($registro['obas_fecha']));
        $usuario = $registro['obas_id_usuario'];
        $estado = $registro['obas_estado'];

        if ($fecha == $fechaHoy) {
            $registrosParaAEO[] = $registro;
            continue;
        }

        if ($usuario !== $usuarioActual || $fecha !== $fechaActual) {
            if ($entradaTemprana && $salidaTardia) {
                $diferenciaHoras = (strtotime($salidaTardia) - strtotime($entradaTemprana)) / 3600;

                if ($diferenciaHoras >= 6) {
                    // LÓGICA NUEVA: Determinar el tipo de día y condición de feriado
                    $fechaDate = new DateTime($fecha);
                    $diaSemana = $fechaDate->format('w'); // 0 = Domingo, 6 = Sábado
                    $esFeriado = in_array($fecha, array_column($feriados_js, 0)); // Usamos el array de feriados

                    // Asignar el código de novedad según el tipo de día y condición de feriado
                    if ($diaSemana >= 1 && $diaSemana <= 5) { // Lunes a Viernes
                        $codigoNovedad = $esFeriado ? 'PRESFE' : 'PRES';
                    } elseif ($diaSemana == 6) { // Sábado
                        $codigoNovedad = $esFeriado ? 'PRESAFE' : 'PRESSA';
                    } elseif ($diaSemana == 0) { // Domingo
                        $codigoNovedad = $esFeriado ? 'PREDOFE' : 'PRESDO';
                    }

                    // Guardar el registro en registrosParaNovedades con el código adecuado
                    $registrosParaNovedades[] = [
                        'usuario_id' => $usuarioActual,
                        'fecha' => $fechaActual,
                        'codigo_novedad' => $codigoNovedad
                    ];
               
                }
            }

            // Reiniciar variables para el nuevo usuario o fecha
            $entradaTemprana = null;
            $salidaTardia = null;
            $fechaActual = $fecha;
            $usuarioActual = $usuario;
        }

        if ($estado == 'Entrada') {
            if (!$entradaTemprana || strtotime($registro['obas_hora']) < strtotime($entradaTemprana)) {
                $entradaTemprana = $registro['obas_hora'];
            }
        } elseif ($estado == 'Salida') {
            if (!$salidaTardia || strtotime($registro['obas_hora']) > strtotime($salidaTardia)) {
                $salidaTardia = $registro['obas_hora'];
            }
        }

        $registrosParaAEO[] = $registro;
    }

    // Revisión final para el último usuario y día procesados
    if ($entradaTemprana && $salidaTardia) {
        $diferenciaHoras = (strtotime($salidaTardia) - strtotime($entradaTemprana)) / 3600;

        if ($diferenciaHoras >= 6) {
            // Determinar el tipo de día y condición de feriado para el último registro
            $fechaDate = new DateTime($fechaActual);
            $diaSemana = $fechaDate->format('w');
            $esFeriado = in_array($fechaActual, array_column($feriados_js, 0));

            if ($diaSemana >= 1 && $diaSemana <= 5) {
                $codigoNovedad = $esFeriado ? 'PRESFE' : 'PRES';
            } elseif ($diaSemana == 6) {
                $codigoNovedad = $esFeriado ? 'PRESAFE' : 'PRESSA';
            } elseif ($diaSemana == 0) {
                $codigoNovedad = $esFeriado ? 'PREDOFE' : 'PRESDO';
            }

            $registrosParaNovedades[] = [
                'usuario_id' => $usuarioActual,
                'fecha' => $fechaActual,
                'codigo_novedad' => $codigoNovedad
            ];
        }
    }

    foreach ($registrosParaNovedades as $cumple) {
        $registrosParaAEO = array_filter($registrosParaAEO, function ($registro) use ($cumple) {
            return !(
                $registro['obas_id_usuario'] == $cumple['usuario_id'] &&
                date('Y-m-d', strtotime($registro['obas_fecha'])) == $cumple['fecha']
            );
        });
    }
    $registrosParaAEO = array_values($registrosParaAEO);
//dd($registrosParaNovedades);
    return [
        'registrosParaNovedades' => $registrosParaNovedades,
        'registrosParaAEO' => $registrosParaAEO
    ];
}



?>
