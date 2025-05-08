<?php
// file: procesoAEONovedades.php
/**
 * Procesa actualizaciones en la tabla obras_asistencia y registra novedades en novedades_personal.
 *
 * @param array $arrayToProcess Array de registros con 'usuario_id' y 'fecha'.
 * @param int $log_usuario_id ID del usuario que realiza la actualización.
 * @return int Número de inserciones realizadas en novedades_personal.
 * @throws Exception Si falla la actualización después de tres intentos.
 */
function procesoAEONovedades($arrayToProcess, $log_usuario_id) {
    // Inicializar el contador de inserciones
    $insertsCount = 0;

    foreach ($arrayToProcess as $item) {
        // Validar que los campos necesarios existan
        if (!isset($item['usuario_id']) || !isset($item['fecha'])) {
            throw new Exception("El registro no contiene 'usuario_id' o 'fecha'.");
        }

        $usuario_id = $item['usuario_id'];
        $fecha = $item['fecha'];
        $novedad_codigo = $item['codigo_novedad'];

        // Preparar los datos para el UPDATE en obras_asistencia
        $arraySet = array(
            'obas_procesado' => 'P',
            'obas_log_accion' => 'edit',
            'obas_log_usuario_id' => $log_usuario_id
        );

        $arrayWhere = array(
            array(
                'columna' => 'obas_id_usuario',
                'condicion' => '=',
                'valorCompara' => $usuario_id
            ),
            array(
                'columna' => 'obas_fecha',
                'condicion' => '=',
                'valorCompara' => $fecha
            )
        );

        $maxAttempts = 3;
        $attempt = 0;
        $success = false;

        // Intentar actualizar hasta tres veces
        while ($attempt < $maxAttempts && !$success) {
            try {
                simpleUpdateInDB('obras_asistencia', $arraySet, $arrayWhere, 'cli');
                $success = true; // Si la actualización es exitosa, salir del bucle
            } catch (Exception $e) {
                $attempt++;
                if ($attempt == $maxAttempts) {
                    // Registrar el error después de tres intentos fallidos
                    error_log("Error al actualizar obras_asistencia para usuario_id $usuario_id y fecha $fecha: " . $e->getMessage());
                    throw new Exception("No se pudo actualizar obras_asistencia para usuario_id $usuario_id y fecha $fecha después de $maxAttempts intentos.");
                }
                // Opcional: esperar un breve período antes de reintentar
                // sleep(1);
            }
        }

        if ($success) {
            // Registrar la novedad en novedades_personal usando simpleInsertInDB_v2
            try {
                // Preparar los datos para la inserción
                $arrayColumnas = array('id_usuario', 'novedad_codigo', 'fecha');
                $arrayValues = array(
                    $usuario_id,    // id_usuario
                    $novedad_codigo, // novedad_codigo
                    $fecha          // fecha
                );

                // Llamar a la función simpleInsertInDB_v2
                $insertResult = simpleInsertInDB_v2('novedades_personal_2', $arrayColumnas, $arrayValues, 'cli');

                if ($insertResult) {
                    // Incrementar el contador si la inserción fue exitosa
                    $insertsCount++;
                } else {
                    // Registrar el error si la inserción falla
                    error_log("Error al insertar en novedades_personal para usuario_id $usuario_id.");
                    // Opcional: decidir si lanzar una excepción o continuar
                    // throw new Exception("No se pudo insertar en novedades_personal para usuario_id $usuario_id.");
                }
            } catch (Exception $e) {
                // Registrar el error pero no interrumpir el procesamiento de otros registros
                error_log("Excepción al insertar en novedades_personal para usuario_id $usuario_id: " . $e->getMessage());
                // Opcional: decidir si lanzar una excepción o continuar
                // throw new Exception("Excepción al insertar en novedades_personal para usuario_id $usuario_id.");
            }
        }
    }

    // Retornar el número de inserciones realizadas
    return $insertsCount;
}
?>
