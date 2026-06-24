<?php
// ../04-modelo/presupuestoGeneradoModel.php

require_once __DIR__ . '/conectDB.php';
require_once __DIR__ . '/schemaIntrospectionModel.php';
require_once __DIR__ . '/presupuestoComercialLockModel.php';
require_once __DIR__ . '/previsitaWorkflowModel.php';

if (!function_exists('repararTextoMojibakePresupuesto')) {
    function repararTextoMojibakePresupuesto(?string $texto): string
    {
        $texto = (string)($texto ?? '');
        if ($texto === '') {
            return '';
        }

        $marcas = ['Ã', 'Â', 'â', 'ðŸ', '�'];
        $tieneMojibake = false;
        foreach ($marcas as $marca) {
            if (strpos($texto, $marca) !== false) {
                $tieneMojibake = true;
                break;
            }
        }

        if (!$tieneMojibake) {
            return $texto;
        }

        $mejor = $texto;
        $scoreMejor = 0;
        foreach ($marcas as $marca) {
            $scoreMejor += substr_count($texto, $marca);
        }

        foreach (['Windows-1252', 'ISO-8859-1'] as $origen) {
            $candidato = @mb_convert_encoding($texto, 'UTF-8', $origen);
            if (!is_string($candidato) || $candidato === '') {
                continue;
            }

            $score = 0;
            foreach ($marcas as $marca) {
                $score += substr_count($candidato, $marca);
            }

            if ($score < $scoreMejor) {
                $mejor = $candidato;
                $scoreMejor = $score;
            }
        }

        return $mejor;
    }
}

if (!function_exists('repararTextoMojibakePresupuestoProfundo')) {
    function repararTextoMojibakePresupuestoProfundo(?string $texto): string
    {
        $texto = (string)($texto ?? '');
        if ($texto === '') {
            return '';
        }

        $marcas = ['Ã', 'Â', 'â', 'ð', 'ï¿½', '�'];
        $scoreTexto = static function (string $valor) use ($marcas): int {
            $score = 0;
            foreach ($marcas as $marca) {
                $score += substr_count($valor, $marca);
            }
            return $score;
        };

        $mejor = $texto;
        $scoreMejor = $scoreTexto($texto);
        if ($scoreMejor === 0) {
            return $texto;
        }

        for ($paso = 0; $paso < 4; $paso++) {
            $huboMejora = false;

            foreach (['Windows-1252', 'ISO-8859-1'] as $origen) {
                $candidato = @mb_convert_encoding($mejor, 'UTF-8', $origen);
                if (!is_string($candidato) || $candidato === '') {
                    continue;
                }

                $score = $scoreTexto($candidato);
                if ($score < $scoreMejor) {
                    $mejor = $candidato;
                    $scoreMejor = $score;
                    $huboMejora = true;
                }
            }

            if (!$huboMejora) {
                break;
            }
        }

        return $mejor;
    }
}

if (!function_exists('textoPlanoDetalleTareaPresupuesto')) {
    function textoPlanoDetalleTareaPresupuesto(?string $html): string
    {
        $html = (string)($html ?? '');
        if ($html === '') {
            return '';
        }

        $normalizado = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $normalizado = preg_replace('/<li\b[^>]*>/i', '- ', (string)$normalizado);
        $normalizado = preg_replace('/<\/(li|p|div|ul|ol)>/i', "\n", (string)$normalizado);

        $texto = strip_tags((string)$normalizado);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = str_replace("\xc2\xa0", ' ', $texto);
        $texto = preg_replace('/\r\n?/', "\n", $texto);
        $texto = preg_replace('/[ \t]+\n/u', "\n", (string)$texto);
        $texto = preg_replace('/\n{3,}/u', "\n\n", (string)$texto);

        return trim((string)$texto);
    }
}

if (!function_exists('resumirTextoSegunReglaPresupuesto')) {
    function resumirTextoSegunReglaPresupuesto(?string $texto, int $maxPalabras = 12): string
    {
        $textoPlano = textoPlanoDetalleTareaPresupuesto($texto);
        if ($textoPlano === '') {
            return '';
        }

        $textoPlano = preg_replace('/^[\s\.\:\-\*,]+/u', '', (string)$textoPlano);
        $textoPlano = preg_replace('/\s+/u', ' ', (string)$textoPlano);
        $textoPlano = trim((string)$textoPlano);

        if ($textoPlano === '') {
            return '';
        }

        if (preg_match('/^(.+?)[\.\:\*,-]/u', $textoPlano, $coincidencia)) {
            $resumen = trim((string)($coincidencia[1] ?? ''));
            if ($resumen !== '') {
                return $resumen . '.';
            }
        }

        $palabras = preg_split('/\s+/u', $textoPlano, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($palabras) <= $maxPalabras) {
            return $textoPlano;
        }

        return implode(' ', array_slice($palabras, 0, $maxPalabras)) . '...';
    }
}

if (!function_exists('sanitizarHtmlDetalleTareaPresupuesto')) {
    function sanitizarHtmlDetalleTareaPresupuesto(?string $html): string
    {
        $html = repararTextoMojibakePresupuestoProfundo((string)($html ?? ''));
        if ($html === '') {
            return '';
        }

        $permitidos = '<b><strong><i><em><u><br><ul><ol><li><p><div>';
        $html = strip_tags($html, $permitidos);
        $html = preg_replace_callback(
            '/<(\/?)([a-z0-9]+)(?:\s+[^>]*)?>/i',
            static function (array $m): string {
                $tag = strtolower((string)($m[2] ?? ''));
                $permitidos = ['b', 'strong', 'i', 'em', 'u', 'br', 'ul', 'ol', 'li', 'p', 'div'];
                if (!in_array($tag, $permitidos, true)) {
                    return '';
                }

                return '<' . ($m[1] ?? '') . $tag . '>';
            },
            $html
        );

        $html = preg_replace('/(?:<br>\s*){3,}/i', '<br><br>', (string)$html);
        $html = trim((string)$html);

        if (textoPlanoDetalleTareaPresupuesto($html) === '') {
            return '';
        }

        return $html;
    }
}

if (!function_exists('normalizarImporteConfirmacionPrecioPresupuesto')) {
    function normalizarImporteConfirmacionPrecioPresupuesto($importe): string
    {
        if (!is_string($importe) && !is_int($importe)) {
            throw new RuntimeException('El importe tiene un formato invalido.', 400);
        }

        $importe = trim((string)$importe);
        if (!preg_match('/^\d+(?:[\.,]\d{1,2})?$/D', $importe)) {
            throw new RuntimeException('El importe debe ser un numero positivo con hasta dos decimales.', 400);
        }

        $importe = str_replace(',', '.', $importe);
        [$parteEntera, $parteDecimal] = array_pad(explode('.', $importe, 2), 2, '');
        $parteEntera = ltrim($parteEntera, '0');
        $parteEntera = $parteEntera === '' ? '0' : $parteEntera;

        if (strlen($parteEntera) > 8) {
            throw new RuntimeException('El importe supera el maximo permitido.', 400);
        }

        $parteDecimal = str_pad($parteDecimal, 2, '0');
        if ($parteEntera === '0' && $parteDecimal === '00') {
            throw new RuntimeException('El importe debe ser mayor que cero.', 400);
        }

        return $parteEntera . '.' . $parteDecimal;
    }
}

if (!function_exists('decimalPersistidoComparableConfirmacionPrecioPresupuesto')) {
    function decimalPersistidoComparableConfirmacionPrecioPresupuesto(?string $importe): string
    {
        $importe = trim((string)$importe);
        if (!preg_match('/^(\d+)(?:\.(\d+))?$/D', $importe, $coincidencias)) {
            return '';
        }

        $entero = ltrim((string)$coincidencias[1], '0');
        $entero = $entero === '' ? '0' : $entero;
        $decimal = rtrim((string)($coincidencias[2] ?? ''), '0');

        return $decimal === '' ? $entero : ($entero . '.' . $decimal);
    }
}

if (!function_exists('confirmarPrecioPresupuesto')) {
    function confirmarPrecioPresupuesto(
        string $tipo,
        int $idPresupuesto,
        int $idLinea,
        int $idCatalogo,
        $importe
    ): array {
        $db = null;
        $transaccionActiva = false;
        $catalogoMaterialOriginal = null;
        $catalogoMaterialEscrito = null;
        $catalogoMaterialModificado = false;
        $lockMaterialAdquirido = false;
        $resultadoOperacion = null;

        try {
            $tipo = strtolower(trim($tipo));
            if (!in_array($tipo, ['material', 'jornal'], true)) {
                throw new RuntimeException('El tipo de precio es invalido.', 400);
            }
            if ($idPresupuesto <= 0 || $idLinea <= 0 || $idCatalogo <= 0) {
                throw new RuntimeException('Los identificadores deben ser enteros positivos.', 400);
            }
            $importeNormalizado = normalizarImporteConfirmacionPrecioPresupuesto($importe);

            $db = conectDB();
            if (!mysqli_set_charset($db, 'utf8mb4')) {
                throw new RuntimeException('No se pudo configurar la conexion de base de datos.');
            }
            if (!mysqli_begin_transaction($db)) {
                throw new RuntimeException('No se pudo iniciar la transaccion.');
            }
            $transaccionActiva = true;

            $stmt = mysqli_prepare($db, "
                SELECT id_presupuesto, id_previsita, id_visita, estado,
                       estado_comercial_simulacion, estado_comercial_smtp,
                       created_at, updated_at
                FROM presupuestos
                WHERE id_presupuesto = ?
                LIMIT 1
                FOR UPDATE
            ");
            if (!$stmt) {
                throw new RuntimeException('No se pudo validar el presupuesto.');
            }
            mysqli_stmt_bind_param($stmt, 'i', $idPresupuesto);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('No se pudo ejecutar la validacion del presupuesto.');
            }
            $res = mysqli_stmt_get_result($stmt);
            if ($res === false) {
                throw new RuntimeException('No se pudo obtener el resultado de la validacion del presupuesto.');
            }
            $presupuesto = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (!$presupuesto) {
                throw new RuntimeException('El presupuesto no existe.', 404);
            }

            $modoCircuito = obtenerModoActivoCircuitoComercialPresupuestosLock($db);
            $estadoComercial = resolverEstadoBloqueoEdicionComercialPresupuestoEnConexion(
                $db,
                $presupuesto,
                $modoCircuito
            );
            $estadosAValidar = [
                $estadoComercial,
                (string)($presupuesto['estado'] ?? ''),
                (string)($presupuesto['estado_comercial_simulacion'] ?? ''),
                (string)($presupuesto['estado_comercial_smtp'] ?? ''),
            ];
            $presupuestoBloqueado = false;
            foreach ($estadosAValidar as $estadoAValidar) {
                if (estadoBloqueaEdicionComercialPresupuesto($estadoAValidar)) {
                    $presupuestoBloqueado = true;
                    break;
                }
            }
            if ($presupuestoBloqueado) {
                throw new RuntimeException('El presupuesto esta bloqueado por su estado comercial.', 409);
            }

            $idPrevisita = (int)$presupuesto['id_previsita'];
            $estadoWorkflow = obtenerEstadoWorkflowPrevisitaPorIdEnConexion($db, $idPrevisita);
            if (estadoBloqueaAvanceWorkflowPrevisita($estadoWorkflow)) {
                throw new RuntimeException('El workflow de la pre-visita no permite confirmar precios.', 409);
            }

            if ($tipo === 'material') {
                $tablaLinea = 'presupuesto_tarea_material';
                $columnaIdLinea = 'id_ptm';
                $columnaIdCatalogo = 'id_material';
            } else {
                $tablaLinea = 'presupuesto_tarea_mano_obra';
                $columnaIdLinea = 'id_ptmo';
                $columnaIdCatalogo = 'id_jornal';
            }

            $stmt = mysqli_prepare($db, "
                SELECT linea.{$columnaIdLinea} AS id_linea,
                       linea.{$columnaIdCatalogo} AS id_catalogo,
                       tarea.id_presupuesto
                FROM {$tablaLinea} AS linea
                INNER JOIN presupuesto_tareas AS tarea
                    ON tarea.id_presu_tarea = linea.id_presu_tarea
                WHERE linea.{$columnaIdLinea} = ?
                LIMIT 1
                FOR UPDATE
            ");
            if (!$stmt) {
                throw new RuntimeException('No se pudo validar la linea del presupuesto.');
            }
            mysqli_stmt_bind_param($stmt, 'i', $idLinea);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('No se pudo ejecutar la validacion de la linea del presupuesto.');
            }
            $res = mysqli_stmt_get_result($stmt);
            if ($res === false) {
                throw new RuntimeException('No se pudo obtener el resultado de la linea del presupuesto.');
            }
            $linea = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (!$linea) {
                throw new RuntimeException('La linea del presupuesto no existe.', 404);
            }
            if ((int)$linea['id_presupuesto'] !== $idPresupuesto) {
                throw new RuntimeException('La linea no pertenece al presupuesto indicado.', 409);
            }
            if ((int)$linea['id_catalogo'] !== $idCatalogo) {
                throw new RuntimeException('El registro de catalogo no coincide con la linea.', 409);
            }

            if ($tipo === 'material') {
                $nombreLockMaterial = 'confirmar_precio_material_' . $idCatalogo;
                $stmt = mysqli_prepare($db, 'SELECT GET_LOCK(?, 5) AS adquirido');
                if (!$stmt) {
                    throw new RuntimeException('No se pudo bloquear el precio del material.');
                }
                mysqli_stmt_bind_param($stmt, 's', $nombreLockMaterial);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException('No se pudo ejecutar el bloqueo del precio del material.');
                }
                $res = mysqli_stmt_get_result($stmt);
                if ($res === false) {
                    throw new RuntimeException('No se pudo obtener el resultado del bloqueo del material.');
                }
                $lockRow = $res ? mysqli_fetch_assoc($res) : null;
                mysqli_stmt_close($stmt);
                if ((int)($lockRow['adquirido'] ?? 0) !== 1) {
                    throw new RuntimeException('El precio del material esta siendo actualizado.', 409);
                }
                $lockMaterialAdquirido = true;
            }

            $sqlCatalogoLock = $tipo === 'material'
                ? 'SELECT id_material, precio_unitario, log_edicion FROM materiales WHERE id_material = ? LIMIT 1'
                : 'SELECT jornal_id FROM tipo_jornales WHERE jornal_id = ? LIMIT 1 FOR UPDATE';
            $stmt = mysqli_prepare($db, $sqlCatalogoLock);
            if (!$stmt) {
                throw new RuntimeException('No se pudo validar el registro de catalogo.');
            }
            mysqli_stmt_bind_param($stmt, 'i', $idCatalogo);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('No se pudo ejecutar la validacion del registro de catalogo.');
            }
            $res = mysqli_stmt_get_result($stmt);
            if ($res === false) {
                throw new RuntimeException('No se pudo obtener el resultado del registro de catalogo.');
            }
            $catalogoExiste = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (!$catalogoExiste) {
                throw new RuntimeException('El registro de catalogo no existe.', 404);
            }
            if ($tipo === 'material') {
                $catalogoMaterialOriginal = [
                    'precio_unitario' => (string)$catalogoExiste['precio_unitario'],
                    'log_edicion' => $catalogoExiste['log_edicion'],
                ];
            }
            $sqlActualizarCatalogo = $tipo === 'material'
                ? 'UPDATE materiales SET precio_unitario = ?, log_edicion = NOW() WHERE id_material = ?'
                : 'UPDATE tipo_jornales SET jornal_valor = ?, updated_at = NOW() WHERE jornal_id = ?';
            $stmt = mysqli_prepare($db, $sqlActualizarCatalogo);
            if (!$stmt) {
                throw new RuntimeException('No se pudo preparar la actualizacion del catalogo.');
            }
            mysqli_stmt_bind_param($stmt, 'si', $importeNormalizado, $idCatalogo);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('No se pudo actualizar el precio del catalogo.');
            }
            mysqli_stmt_close($stmt);
            if ($tipo === 'material') {
                $catalogoMaterialModificado = true;
            }

            $sqlLeerCatalogo = $tipo === 'material'
                ? 'SELECT precio_unitario AS importe, log_edicion AS fecha_actualizacion FROM materiales WHERE id_material = ? LIMIT 1'
                : 'SELECT jornal_valor AS importe, updated_at AS fecha_actualizacion FROM tipo_jornales WHERE jornal_id = ? LIMIT 1';
            $stmt = mysqli_prepare($db, $sqlLeerCatalogo);
            if (!$stmt) {
                throw new RuntimeException('No se pudo verificar el precio actualizado del catalogo.');
            }
            mysqli_stmt_bind_param($stmt, 'i', $idCatalogo);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('No se pudo ejecutar la verificacion del catalogo actualizado.');
            }
            $res = mysqli_stmt_get_result($stmt);
            if ($res === false) {
                throw new RuntimeException('No se pudo obtener el precio actualizado del catalogo.');
            }
            $catalogo = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (!$catalogo || empty($catalogo['fecha_actualizacion'])) {
                throw new RuntimeException('No se pudo verificar la actualizacion del catalogo.');
            }

            $importeCatalogo = (string)$catalogo['importe'];
            $fechaCatalogo = (string)$catalogo['fecha_actualizacion'];
            if ($tipo === 'material') {
                $catalogoMaterialEscrito = [
                    'precio_unitario' => $importeCatalogo,
                    'log_edicion' => $fechaCatalogo,
                ];
            }
            if (
                decimalPersistidoComparableConfirmacionPrecioPresupuesto($importeCatalogo)
                !== decimalPersistidoComparableConfirmacionPrecioPresupuesto($importeNormalizado)
            ) {
                throw new RuntimeException('El importe persistido en el catalogo no coincide con el solicitado.');
            }

            if ($tipo === 'material') {
                $sqlActualizarSnapshot = '
                    UPDATE presupuesto_tarea_material AS linea
                    INNER JOIN presupuesto_tareas AS tarea
                        ON tarea.id_presu_tarea = linea.id_presu_tarea
                    SET linea.precio_unitario_usado = ?,
                        linea.log_edicion = ?,
                        linea.subtotal_fila = ROUND(
                            linea.cantidad * ? * (1 + (linea.porcentaje_extra / 100)),
                            2
                        )
                    WHERE linea.id_ptm = ? AND linea.id_material = ?
                      AND tarea.id_presupuesto = ?
                ';
            } else {
                $sqlActualizarSnapshot = '
                    UPDATE presupuesto_tarea_mano_obra AS linea
                    INNER JOIN presupuesto_tareas AS tarea
                        ON tarea.id_presu_tarea = linea.id_presu_tarea
                    SET linea.valor_jornal_usado = ?,
                        linea.updated_at_origen = ?,
                        linea.subtotal_fila = ROUND(
                            linea.cantidad * linea.dias * ? * (1 + (linea.porcentaje_extra / 100)),
                            2
                        )
                    WHERE linea.id_ptmo = ? AND linea.id_jornal = ?
                      AND tarea.id_presupuesto = ?
                ';
            }
            $stmt = mysqli_prepare($db, $sqlActualizarSnapshot);
            if (!$stmt) {
                throw new RuntimeException('No se pudo preparar la actualizacion del snapshot.');
            }
            mysqli_stmt_bind_param(
                $stmt,
                'sssiii',
                $importeCatalogo,
                $fechaCatalogo,
                $importeCatalogo,
                $idLinea,
                $idCatalogo,
                $idPresupuesto
            );
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('No se pudo actualizar el snapshot del presupuesto.');
            }
            mysqli_stmt_close($stmt);
            if ($tipo === 'material') {
                $sqlLeerSnapshot = '
                    SELECT linea.precio_unitario_usado AS importe_snapshot,
                           linea.log_edicion AS fecha_origen_snapshot,
                           linea.subtotal_fila AS subtotal_snapshot,
                           ROUND(
                               linea.cantidad * linea.precio_unitario_usado
                               * (1 + (linea.porcentaje_extra / 100)),
                               2
                           ) AS subtotal_esperado,
                           linea.id_material AS id_catalogo,
                           tarea.id_presupuesto
                    FROM presupuesto_tarea_material AS linea
                    INNER JOIN presupuesto_tareas AS tarea
                        ON tarea.id_presu_tarea = linea.id_presu_tarea
                    WHERE linea.id_ptm = ? LIMIT 1
                ';
            } else {
                $sqlLeerSnapshot = '
                    SELECT linea.valor_jornal_usado AS importe_snapshot,
                           linea.updated_at_origen AS fecha_origen_snapshot,
                           linea.subtotal_fila AS subtotal_snapshot,
                           ROUND(
                               linea.cantidad * linea.dias * linea.valor_jornal_usado
                               * (1 + (linea.porcentaje_extra / 100)),
                               2
                           ) AS subtotal_esperado,
                           linea.id_jornal AS id_catalogo,
                           tarea.id_presupuesto
                    FROM presupuesto_tarea_mano_obra AS linea
                    INNER JOIN presupuesto_tareas AS tarea
                        ON tarea.id_presu_tarea = linea.id_presu_tarea
                    WHERE linea.id_ptmo = ? LIMIT 1
                ';
            }
            $stmt = mysqli_prepare($db, $sqlLeerSnapshot);
            if (!$stmt) {
                throw new RuntimeException('No se pudo verificar el snapshot actualizado.');
            }
            mysqli_stmt_bind_param($stmt, 'i', $idLinea);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('No se pudo ejecutar la verificacion del snapshot actualizado.');
            }
            $res = mysqli_stmt_get_result($stmt);
            if ($res === false) {
                throw new RuntimeException('No se pudo obtener el snapshot actualizado.');
            }
            $snapshot = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (
                !$snapshot
                || (int)$snapshot['id_presupuesto'] !== $idPresupuesto
                || (int)$snapshot['id_catalogo'] !== $idCatalogo
                || decimalPersistidoComparableConfirmacionPrecioPresupuesto((string)$snapshot['importe_snapshot'])
                    !== decimalPersistidoComparableConfirmacionPrecioPresupuesto($importeCatalogo)
                || (string)$snapshot['fecha_origen_snapshot'] !== $fechaCatalogo
                || decimalPersistidoComparableConfirmacionPrecioPresupuesto((string)$snapshot['subtotal_snapshot'])
                    !== decimalPersistidoComparableConfirmacionPrecioPresupuesto((string)$snapshot['subtotal_esperado'])
            ) {
                throw new RuntimeException('No se pudo verificar la consistencia entre catalogo y snapshot.');
            }

            $stmtWorkflowFinal = mysqli_prepare(
                $db,
                'SELECT estado_visita FROM previsitas WHERE id_previsita = ? LIMIT 1'
            );
            if (!$stmtWorkflowFinal) {
                throw new RuntimeException('No se pudo preparar la revalidacion del workflow.');
            }
            mysqli_stmt_bind_param($stmtWorkflowFinal, 'i', $idPrevisita);
            if (!mysqli_stmt_execute($stmtWorkflowFinal)) {
                throw new RuntimeException('No se pudo ejecutar la revalidacion del workflow.');
            }
            $workflowFinalResult = mysqli_stmt_get_result($stmtWorkflowFinal);
            if ($workflowFinalResult === false) {
                throw new RuntimeException('No se pudo obtener la revalidacion del workflow.');
            }
            $workflowFinalRow = mysqli_fetch_assoc($workflowFinalResult);
            mysqli_stmt_close($stmtWorkflowFinal);
            if (!$workflowFinalRow) {
                throw new RuntimeException('La pre-visita asociada no existe.', 409);
            }
            $estadoWorkflowFinal = trim((string)$workflowFinalRow['estado_visita']);
            if (estadoBloqueaAvanceWorkflowPrevisita($estadoWorkflowFinal)) {
                throw new RuntimeException('El workflow de la pre-visita no permite confirmar precios.', 409);
            }

            if (!mysqli_commit($db)) {
                throw new RuntimeException('No se pudo confirmar la transaccion.');
            }
            $transaccionActiva = false;

            $resultadoOperacion = [
                'ok' => true,
                'tipo' => $tipo,
                'id_presupuesto' => $idPresupuesto,
                'id_linea' => $idLinea,
                'id_catalogo' => $idCatalogo,
                'importe_persistido' => $importeCatalogo,
                'fecha_actualizacion' => $fechaCatalogo,
                'importe_snapshot' => (string)$snapshot['importe_snapshot'],
                'fecha_origen_snapshot' => (string)$snapshot['fecha_origen_snapshot'],
                'subtotal_snapshot' => (string)$snapshot['subtotal_snapshot'],
            ];
        } catch (Throwable $e) {
            $falloRollback = false;
            $falloCompensacionMaterial = false;

            if ($db instanceof mysqli) {
                if ($transaccionActiva) {
                    try {
                        if (!mysqli_rollback($db)) {
                            $falloRollback = true;
                            error_log('confirmarPrecioPresupuesto: fallo critico al ejecutar rollback.');
                        }
                    } catch (Throwable $rollbackError) {
                        $falloRollback = true;
                        error_log('confirmarPrecioPresupuesto rollback: ' . $rollbackError->getMessage());
                    }
                    $transaccionActiva = false;
                }

                if ($catalogoMaterialModificado) {
                    if (!is_array($catalogoMaterialOriginal) || !is_array($catalogoMaterialEscrito)) {
                        $falloCompensacionMaterial = true;
                        error_log('confirmarPrecioPresupuesto: no hay datos suficientes para compensar el catalogo MyISAM.');
                    } else {
                        try {
                            $stmtRestaurar = mysqli_prepare($db, '
                                UPDATE materiales
                                SET precio_unitario = ?, log_edicion = ?
                                WHERE id_material = ?
                                  AND precio_unitario = ?
                                  AND log_edicion <=> ?
                            ');
                            if (!$stmtRestaurar) {
                                throw new RuntimeException('No se pudo preparar la compensacion del catalogo MyISAM.');
                            }

                            $precioOriginal = (string)$catalogoMaterialOriginal['precio_unitario'];
                            $fechaOriginal = $catalogoMaterialOriginal['log_edicion'];
                            $precioEscrito = (string)$catalogoMaterialEscrito['precio_unitario'];
                            $fechaEscrita = $catalogoMaterialEscrito['log_edicion'];
                            mysqli_stmt_bind_param(
                                $stmtRestaurar,
                                'ssiss',
                                $precioOriginal,
                                $fechaOriginal,
                                $idCatalogo,
                                $precioEscrito,
                                $fechaEscrita
                            );
                            if (!mysqli_stmt_execute($stmtRestaurar)) {
                                throw new RuntimeException('No se pudo ejecutar la compensacion del catalogo MyISAM.');
                            }
                            $filasCompensadas = mysqli_stmt_affected_rows($stmtRestaurar);
                            mysqli_stmt_close($stmtRestaurar);

                            if ($filasCompensadas !== 1) {
                                $falloCompensacionMaterial = true;
                                error_log(
                                    'confirmarPrecioPresupuesto: compensacion critica omitida; '
                                    . 'el material cambio concurrentemente o no conserva los valores escritos.'
                                );
                            }
                        } catch (Throwable $compensacionError) {
                            $falloCompensacionMaterial = true;
                            error_log('confirmarPrecioPresupuesto compensacion MyISAM: ' . $compensacionError->getMessage());
                        }
                    }
                }
            }

            $codigo = (int)$e->getCode();
            $esControlado = in_array($codigo, [400, 404, 409], true);
            if ($falloRollback || $falloCompensacionMaterial) {
                $codigo = 500;
                $esControlado = false;
            }
            if (!$esControlado) {
                error_log('confirmarPrecioPresupuesto: ' . $e->getMessage());
            }

            $resultadoOperacion = [
                'ok' => false,
                'mensaje' => $esControlado
                    ? $e->getMessage()
                    : 'No se pudo confirmar la vigencia del precio.',
                'http_status' => $esControlado ? $codigo : 500,
            ];
        } finally {
            if ($db instanceof mysqli) {
                if ($lockMaterialAdquirido) {
                    try {
                        $stmtRelease = mysqli_prepare($db, 'SELECT RELEASE_LOCK(?) AS liberado');
                        if (!$stmtRelease) {
                            throw new RuntimeException('No se pudo preparar RELEASE_LOCK.');
                        }
                        mysqli_stmt_bind_param($stmtRelease, 's', $nombreLockMaterial);
                        if (!mysqli_stmt_execute($stmtRelease)) {
                            throw new RuntimeException('No se pudo ejecutar RELEASE_LOCK.');
                        }
                        $releaseResult = mysqli_stmt_get_result($stmtRelease);
                        if ($releaseResult === false) {
                            throw new RuntimeException('No se pudo obtener el resultado de RELEASE_LOCK.');
                        }
                        $releaseRow = mysqli_fetch_assoc($releaseResult);
                        mysqli_stmt_close($stmtRelease);
                        if ((int)($releaseRow['liberado'] ?? 0) !== 1) {
                            error_log('confirmarPrecioPresupuesto: RELEASE_LOCK no devolvio 1.');
                        }
                    } catch (Throwable $releaseError) {
                        error_log('confirmarPrecioPresupuesto cleanup lock: ' . $releaseError->getMessage());
                    }
                }

                try {
                    mysqli_close($db);
                } catch (Throwable $closeError) {
                    error_log('confirmarPrecioPresupuesto cleanup conexion: ' . $closeError->getMessage());
                }
            }
        }

        return is_array($resultadoOperacion)
            ? $resultadoOperacion
            : [
                'ok' => false,
                'mensaje' => 'No se pudo confirmar la vigencia del precio.',
                'http_status' => 500,
            ];
    }
}

if (!function_exists('confirmarPrecioCatalogoPresupuestoDinamico')) {
    function confirmarPrecioCatalogoPresupuestoDinamico(
        string $tipo,
        int $idCatalogo,
        $importe
    ): array {
        $db = null;
        $lockMaterialAdquirido = false;
        $nombreLockMaterial = '';

        try {
            $tipo = strtolower(trim($tipo));
            if (!in_array($tipo, ['material', 'jornal'], true)) {
                throw new RuntimeException('El tipo de precio es invalido.', 400);
            }
            if ($idCatalogo <= 0) {
                throw new RuntimeException('El identificador de catalogo debe ser un entero positivo.', 400);
            }

            $importeNormalizado = normalizarImporteConfirmacionPrecioPresupuesto($importe);

            $db = conectDB();
            if (!mysqli_set_charset($db, 'utf8mb4')) {
                throw new RuntimeException('No se pudo configurar la conexion de base de datos.');
            }

            if ($tipo === 'material') {
                $nombreLockMaterial = 'confirmar_precio_material_' . $idCatalogo;
                $stmt = mysqli_prepare($db, 'SELECT GET_LOCK(?, 5) AS adquirido');
                if (!$stmt) {
                    throw new RuntimeException('No se pudo bloquear el precio del material.');
                }
                mysqli_stmt_bind_param($stmt, 's', $nombreLockMaterial);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException('No se pudo ejecutar el bloqueo del precio del material.');
                }
                $res = mysqli_stmt_get_result($stmt);
                if ($res === false) {
                    throw new RuntimeException('No se pudo obtener el resultado del bloqueo del material.');
                }
                $lockRow = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);

                if ((int)($lockRow['adquirido'] ?? 0) !== 1) {
                    throw new RuntimeException('El precio del material esta siendo actualizado.', 409);
                }
                $lockMaterialAdquirido = true;
            }

            $sqlExiste = $tipo === 'material'
                ? 'SELECT id_material FROM materiales WHERE id_material = ? LIMIT 1'
                : 'SELECT jornal_id FROM tipo_jornales WHERE jornal_id = ? LIMIT 1';
            $stmt = mysqli_prepare($db, $sqlExiste);
            if (!$stmt) {
                throw new RuntimeException('No se pudo validar el registro de catalogo.');
            }
            mysqli_stmt_bind_param($stmt, 'i', $idCatalogo);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('No se pudo ejecutar la validacion del registro de catalogo.');
            }
            $res = mysqli_stmt_get_result($stmt);
            if ($res === false) {
                throw new RuntimeException('No se pudo obtener el resultado del registro de catalogo.');
            }
            $catalogoExiste = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if (!$catalogoExiste) {
                throw new RuntimeException('El registro de catalogo no existe.', 404);
            }

            // GET_LOCK coordina solamente consumidores cooperativos; esta operacion modifica un unico catalogo.
            $sqlUpdate = $tipo === 'material'
                ? 'UPDATE materiales SET precio_unitario = ?, log_edicion = NOW() WHERE id_material = ?'
                : 'UPDATE tipo_jornales SET jornal_valor = ?, updated_at = NOW() WHERE jornal_id = ?';
            $stmt = mysqli_prepare($db, $sqlUpdate);
            if (!$stmt) {
                throw new RuntimeException('No se pudo preparar la actualizacion del catalogo.');
            }
            mysqli_stmt_bind_param($stmt, 'si', $importeNormalizado, $idCatalogo);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('No se pudo actualizar el precio del catalogo.');
            }
            mysqli_stmt_close($stmt);

            $sqlLeer = $tipo === 'material'
                ? 'SELECT precio_unitario AS importe, log_edicion AS fecha_actualizacion FROM materiales WHERE id_material = ? LIMIT 1'
                : 'SELECT jornal_valor AS importe, updated_at AS fecha_actualizacion FROM tipo_jornales WHERE jornal_id = ? LIMIT 1';
            $stmt = mysqli_prepare($db, $sqlLeer);
            if (!$stmt) {
                throw new RuntimeException('No se pudo verificar el precio actualizado del catalogo.');
            }
            mysqli_stmt_bind_param($stmt, 'i', $idCatalogo);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('No se pudo ejecutar la verificacion del catalogo actualizado.');
            }
            $res = mysqli_stmt_get_result($stmt);
            if ($res === false) {
                throw new RuntimeException('No se pudo obtener el precio actualizado del catalogo.');
            }
            $catalogo = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if (!$catalogo || empty($catalogo['fecha_actualizacion'])) {
                throw new RuntimeException('No se pudo verificar la actualizacion del catalogo.');
            }

            $importeCatalogo = (string)$catalogo['importe'];
            if (
                decimalPersistidoComparableConfirmacionPrecioPresupuesto($importeCatalogo)
                !== decimalPersistidoComparableConfirmacionPrecioPresupuesto($importeNormalizado)
            ) {
                throw new RuntimeException('El importe persistido en el catalogo no coincide con el solicitado.');
            }

            return [
                'ok' => true,
                'tipo' => $tipo,
                'id_catalogo' => $idCatalogo,
                'importe_persistido' => $importeCatalogo,
                'fecha_actualizacion' => (string)$catalogo['fecha_actualizacion'],
            ];
        } catch (Throwable $e) {
            $codigo = (int)$e->getCode();
            $esControlado = in_array($codigo, [400, 404, 409], true);
            if (!$esControlado) {
                error_log('confirmarPrecioCatalogoPresupuestoDinamico: ' . $e->getMessage());
            }

            return [
                'ok' => false,
                'mensaje' => $esControlado
                    ? $e->getMessage()
                    : 'No se pudo confirmar la vigencia del precio.',
                'http_status' => $esControlado ? $codigo : 500,
            ];
        } finally {
            if ($db instanceof mysqli) {
                if ($lockMaterialAdquirido) {
                    try {
                        $stmtRelease = mysqli_prepare($db, 'SELECT RELEASE_LOCK(?) AS liberado');
                        if (!$stmtRelease) {
                            throw new RuntimeException('No se pudo preparar RELEASE_LOCK.');
                        }
                        mysqli_stmt_bind_param($stmtRelease, 's', $nombreLockMaterial);
                        if (!mysqli_stmt_execute($stmtRelease)) {
                            throw new RuntimeException('No se pudo ejecutar RELEASE_LOCK.');
                        }
                        $releaseResult = mysqli_stmt_get_result($stmtRelease);
                        if ($releaseResult === false) {
                            throw new RuntimeException('No se pudo obtener el resultado de RELEASE_LOCK.');
                        }
                        $releaseRow = mysqli_fetch_assoc($releaseResult);
                        mysqli_stmt_close($stmtRelease);
                        if ((int)($releaseRow['liberado'] ?? 0) !== 1) {
                            error_log('confirmarPrecioCatalogoPresupuestoDinamico: RELEASE_LOCK no devolvio 1.');
                        }
                    } catch (Throwable $releaseError) {
                        error_log('confirmarPrecioCatalogoPresupuestoDinamico cleanup lock: ' . $releaseError->getMessage());
                    }
                }

                try {
                    mysqli_close($db);
                } catch (Throwable $closeError) {
                    error_log('confirmarPrecioCatalogoPresupuestoDinamico cleanup conexion: ' . $closeError->getMessage());
                }
            }
        }
    }
}

/**
 * Guarda un presupuesto + materiales + MO + fotos.
 *
 * @param array $payload             Cabecera + tareas (como lo armás en JS).
 * @param array $archivosPorTarea    [nroTarea => [ [name,type,tmp_name,error,size], ... ]]
 * @param array $eliminadasPorTarea  [nroTarea => ['nombre1.jpg','nombre2.png',...]]
 *
 * @return array ['ok'=>bool, 'id_presupuesto'=>int, 'version'=>int, 'estado'=>string] | ['ok'=>false,'msg'=>...]
 */
if (!function_exists('decimalComparableGuardarPresupuesto')) {
    function decimalComparableGuardarPresupuesto($importe): string
    {
        if (!is_string($importe) && !is_int($importe) && !is_float($importe)) {
            return '';
        }

        $importe = trim((string)$importe);
        if ($importe === '') {
            return '';
        }
        $importe = str_replace(',', '.', $importe);

        if (!preg_match('/^(\d+)(?:\.(\d+))?$/D', $importe, $coincidencias)) {
            return '';
        }

        $entero = ltrim((string)$coincidencias[1], '0');
        $entero = $entero === '' ? '0' : $entero;
        $decimal = rtrim((string)($coincidencias[2] ?? ''), '0');

        return $decimal === '' ? $entero : ($entero . '.' . $decimal);
    }
}

if (!function_exists('validarCatalogosPayloadGuardarPresupuesto')) {
    function validarCatalogosPayloadGuardarPresupuesto(mysqli $db, array $tareasPayload, ?int $idPresupuesto): array
    {
        if (!is_array($tareasPayload)) {
            throw new RuntimeException('La estructura de tareas del presupuesto es invalida.', 400);
        }

        $mensajeCambioPrecio = 'El precio de un material o jornal cambio. Confirma nuevamente los precios antes de guardar.';
        $validados = [
            'materiales' => [],
            'mano_obra' => [],
        ];

        foreach ($tareasPayload as $indiceTarea => $t) {
            if (!is_array($t)) {
                throw new RuntimeException('La estructura de una tarea del presupuesto es invalida.', 400);
            }

            foreach (($t['materiales'] ?? []) as $indiceMaterial => $m) {
                if (!is_array($m)) {
                    throw new RuntimeException('La estructura de un material del presupuesto es invalida.', 400);
                }

                $idMaterialRaw = $m['id_material'] ?? null;
                if ($idMaterialRaw === null || $idMaterialRaw === '') {
                    continue;
                }

                $idMaterial = filter_var($idMaterialRaw, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                if ($idMaterial === false) {
                    throw new RuntimeException('El identificador de un material es invalido.', 400);
                }

                $precioPayload = $m['precio_unitario'] ?? null;
                $idPtmRaw = $m['id_ptm'] ?? null;
                if ($idPtmRaw !== null && $idPtmRaw !== '') {
                    $idPtm = filter_var($idPtmRaw, FILTER_VALIDATE_INT, [
                        'options' => ['min_range' => 1],
                    ]);
                    if ($idPtm === false || $idPresupuesto === null || $idPresupuesto <= 0) {
                        throw new RuntimeException($mensajeCambioPrecio, 409);
                    }

                    $stmt = mysqli_prepare(
                        $db,
                        'SELECT ptm.id_ptm, ptm.id_material, ptm.precio_unitario_usado,
                                ptm.log_alta, ptm.log_edicion, pt.id_presupuesto
                         FROM presupuesto_tarea_material AS ptm
                         INNER JOIN presupuesto_tareas AS pt
                            ON pt.id_presu_tarea = ptm.id_presu_tarea
                         WHERE ptm.id_ptm = ?
                           AND pt.id_presupuesto = ?
                         LIMIT 1'
                    );
                    if (!$stmt) {
                        throw new RuntimeException('No se pudo validar la linea historica de material.');
                    }
                    mysqli_stmt_bind_param($stmt, 'ii', $idPtm, $idPresupuesto);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new RuntimeException('No se pudo ejecutar la validacion de la linea historica de material.');
                    }
                    $res = mysqli_stmt_get_result($stmt);
                    if ($res === false) {
                        throw new RuntimeException('No se pudo obtener la linea historica de material.');
                    }
                    $lineaHistorica = mysqli_fetch_assoc($res);
                    mysqli_stmt_close($stmt);

                    if (!$lineaHistorica || (int)$lineaHistorica['id_material'] !== $idMaterial) {
                        throw new RuntimeException($mensajeCambioPrecio, 409);
                    }

                    if (
                        decimalComparableGuardarPresupuesto($precioPayload)
                        === decimalComparableGuardarPresupuesto((string)$lineaHistorica['precio_unitario_usado'])
                    ) {
                        $validados['materiales'][$indiceTarea][$indiceMaterial] = [
                            'precio_unitario' => (string)$lineaHistorica['precio_unitario_usado'],
                            'log_alta' => $lineaHistorica['log_alta'],
                            'log_edicion' => $lineaHistorica['log_edicion'],
                        ];
                        continue;
                    }
                }

                $stmt = mysqli_prepare(
                    $db,
                    'SELECT id_material, precio_unitario, log_alta, log_edicion
                     FROM materiales
                     WHERE id_material = ?
                     LIMIT 1'
                );
                if (!$stmt) {
                    throw new RuntimeException('No se pudo validar el catalogo de materiales.');
                }
                mysqli_stmt_bind_param($stmt, 'i', $idMaterial);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException('No se pudo ejecutar la validacion del catalogo de materiales.');
                }
                $res = mysqli_stmt_get_result($stmt);
                if ($res === false) {
                    throw new RuntimeException('No se pudo obtener el catalogo de materiales.');
                }
                $catalogo = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);

                if (!$catalogo) {
                    throw new RuntimeException($mensajeCambioPrecio, 409);
                }

                if (
                    decimalComparableGuardarPresupuesto($precioPayload)
                    !== decimalComparableGuardarPresupuesto((string)$catalogo['precio_unitario'])
                ) {
                    throw new RuntimeException($mensajeCambioPrecio, 409);
                }

                $validados['materiales'][$indiceTarea][$indiceMaterial] = [
                    'precio_unitario' => (string)$catalogo['precio_unitario'],
                    'log_alta' => $catalogo['log_alta'],
                    'log_edicion' => $catalogo['log_edicion'],
                ];
            }

            foreach (($t['mano_obra'] ?? []) as $indiceManoObra => $mo) {
                if (!is_array($mo)) {
                    throw new RuntimeException('La estructura de un jornal del presupuesto es invalida.', 400);
                }

                $idJornalRaw = $mo['jornal_id'] ?? null;
                if ($idJornalRaw === null || $idJornalRaw === '') {
                    continue;
                }

                $idJornal = filter_var($idJornalRaw, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                if ($idJornal === false) {
                    throw new RuntimeException('El identificador de un jornal es invalido.', 400);
                }

                $precioPayload = $mo['jornal_valor'] ?? null;
                $idPtmoRaw = $mo['id_ptmo'] ?? null;
                if ($idPtmoRaw !== null && $idPtmoRaw !== '') {
                    $idPtmo = filter_var($idPtmoRaw, FILTER_VALIDATE_INT, [
                        'options' => ['min_range' => 1],
                    ]);
                    if ($idPtmo === false || $idPresupuesto === null || $idPresupuesto <= 0) {
                        throw new RuntimeException($mensajeCambioPrecio, 409);
                    }

                    $stmt = mysqli_prepare(
                        $db,
                        'SELECT ptmo.id_ptmo, ptmo.id_jornal, ptmo.valor_jornal_usado,
                                ptmo.updated_at_origen, pt.id_presupuesto
                         FROM presupuesto_tarea_mano_obra AS ptmo
                         INNER JOIN presupuesto_tareas AS pt
                            ON pt.id_presu_tarea = ptmo.id_presu_tarea
                         WHERE ptmo.id_ptmo = ?
                           AND pt.id_presupuesto = ?
                         LIMIT 1'
                    );
                    if (!$stmt) {
                        throw new RuntimeException('No se pudo validar la linea historica de jornal.');
                    }
                    mysqli_stmt_bind_param($stmt, 'ii', $idPtmo, $idPresupuesto);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new RuntimeException('No se pudo ejecutar la validacion de la linea historica de jornal.');
                    }
                    $res = mysqli_stmt_get_result($stmt);
                    if ($res === false) {
                        throw new RuntimeException('No se pudo obtener la linea historica de jornal.');
                    }
                    $lineaHistorica = mysqli_fetch_assoc($res);
                    mysqli_stmt_close($stmt);

                    if (!$lineaHistorica || (int)$lineaHistorica['id_jornal'] !== $idJornal) {
                        throw new RuntimeException($mensajeCambioPrecio, 409);
                    }

                    if (
                        decimalComparableGuardarPresupuesto($precioPayload)
                        === decimalComparableGuardarPresupuesto((string)$lineaHistorica['valor_jornal_usado'])
                    ) {
                        $validados['mano_obra'][$indiceTarea][$indiceManoObra] = [
                            'jornal_valor' => (string)$lineaHistorica['valor_jornal_usado'],
                            'updated_at_origen' => $lineaHistorica['updated_at_origen'],
                        ];
                        continue;
                    }
                }

                $stmt = mysqli_prepare(
                    $db,
                    'SELECT jornal_id, jornal_valor, created_at, updated_at,
                            COALESCE(updated_at, created_at) AS fecha_origen
                     FROM tipo_jornales
                     WHERE jornal_id = ?
                     LIMIT 1'
                );
                if (!$stmt) {
                    throw new RuntimeException('No se pudo validar el catalogo de jornales.');
                }
                mysqli_stmt_bind_param($stmt, 'i', $idJornal);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException('No se pudo ejecutar la validacion del catalogo de jornales.');
                }
                $res = mysqli_stmt_get_result($stmt);
                if ($res === false) {
                    throw new RuntimeException('No se pudo obtener el catalogo de jornales.');
                }
                $catalogo = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);

                if (!$catalogo) {
                    throw new RuntimeException($mensajeCambioPrecio, 409);
                }

                if (
                    decimalComparableGuardarPresupuesto($precioPayload)
                    !== decimalComparableGuardarPresupuesto((string)$catalogo['jornal_valor'])
                ) {
                    throw new RuntimeException($mensajeCambioPrecio, 409);
                }

                $validados['mano_obra'][$indiceTarea][$indiceManoObra] = [
                    'jornal_valor' => (string)$catalogo['jornal_valor'],
                    'updated_at_origen' => $catalogo['fecha_origen'],
                ];
            }
        }

        return $validados;
    }
}

function guardarPresupuesto(array $payload, array $archivosPorTarea = [], array $eliminadasPorTarea = []): array
{
    // Config de uploads (ajustá si querés)
    $ALLOWED_EXT = ['jpg','jpeg','png','webp','gif'];
    $MAX_SIZE    = 15 * 1024 * 1024; // 15 MB por archivo

    $db = conectDB();
    mysqli_begin_transaction($db);

    try {
        $id_previsita   = isset($payload['id_previsita']) ? (int)$payload['id_previsita'] : null;
        $id_visita      = isset($payload['id_visita']) ? (int)$payload['id_visita'] : null;
        $id_presupuesto = !empty($payload['id_presupuesto']) ? (int)$payload['id_presupuesto'] : null;
        // Cada nuevo guardado vuelve el presupuesto a BORRADOR.
        // Si ya fue impreso/enviado/aprobado, una modificación invalida ese estado previo.
        $estado         = 'BORRADOR'; // unificamos a mayúsculas
        $moneda         = 'ARS';
        $version        = 1;
        $tieneEstadosComerciales = columna_existe($db, 'presupuestos', 'estado_comercial_simulacion')
            && columna_existe($db, 'presupuestos', 'estado_comercial_smtp');
        $tieneOrdenMaterialesPresupuesto = columna_existe($db, 'presupuesto_tarea_material', 'orden');
        $tieneOrdenManoObraPresupuesto = columna_existe($db, 'presupuesto_tarea_mano_obra', 'orden');

        if (!$id_previsita) {
            throw new RuntimeException('id_previsita es requerido');
        }

        $bloqueoWorkflowPrevisita = obtenerBloqueoWorkflowPrevisitaPorId($id_previsita);
        if (!empty($bloqueoWorkflowPrevisita['bloquea_avance'])) {
            throw new RuntimeException(
                $bloqueoWorkflowPrevisita['mensaje'] ?: mensajeBloqueoWorkflowPrevisita($bloqueoWorkflowPrevisita['estado'] ?? '')
            );
        }

        $bloqueoEdicion = obtenerBloqueoEdicionComercialPresupuestoPorPrevisita($id_previsita, $id_presupuesto);
        if (!empty($bloqueoEdicion['bloqueado'])) {
            throw new RuntimeException(
                $bloqueoEdicion['mensaje'] ?: mensajeBloqueoEdicionComercialPresupuesto($bloqueoEdicion['estado'] ?? '')
            );
        }

        // === INSERT o UPDATE cabecera (idempotente por (id_previsita, id_visita)) ===

            // Si no vino id_presupuesto, intentamos reutilizar uno existente para esta visita
            $stmt = mysqli_prepare($db, "
                SELECT id_presupuesto, estado
                FROM presupuestos
                WHERE id_previsita = ?
                AND ( ? IS NULL OR id_visita = ? )
                AND UPPER(estado) IN ('BORRADOR','GENERADO','EMITIDO','IMPRESO','ENVIADO','RECIBIDO','RESOLICITADO','APROBADO','RECHAZADO','CANCELADO')
                ORDER BY updated_at DESC, id_presupuesto DESC
                LIMIT 1
            ");

            // si id_visita es null, el predicado ( ? IS NULL OR id_visita = ? ) se cumple por el primer término
            mysqli_stmt_bind_param($stmt, "iii", $id_previsita, $id_visita, $id_visita);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('Error al buscar presupuesto previo: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
            }
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if ($row && !empty($row['id_presupuesto'])) {
                $id_presupuesto = (int)$row['id_presupuesto'];
            }

            $tareasPayload = $payload['tareas'] ?? [];
            $catalogosValidados = validarCatalogosPayloadGuardarPresupuesto($db, $tareasPayload, $id_presupuesto);

            if ($id_presupuesto === null) {
                // No había uno previo: insertamos cabecera nueva
                $stmt = mysqli_prepare($db, $tieneEstadosComerciales
                    ? "
                        INSERT INTO presupuestos (
                            id_previsita,
                            id_visita,
                            estado,
                            estado_comercial_simulacion,
                            estado_comercial_smtp,
                            moneda,
                            version,
                            created_at,
                            updated_at
                        )
                        VALUES (?, ?, ?, NULL, NULL, ?, 1, NOW(), NOW())
                    "
                    : "
                        INSERT INTO presupuestos (id_previsita, id_visita, estado, moneda, version, created_at, updated_at)
                        VALUES (?, ?, ?, ?, 1, NOW(), NOW())
                    "
                );
                mysqli_stmt_bind_param($stmt, "iiss", $id_previsita, $id_visita, $estado, $moneda);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException('Error al insertar cabecera: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
                }
                $id_presupuesto = mysqli_insert_id($db);
                mysqli_stmt_close($stmt);
            } else {
                // Reutilizamos el existente: actualizamos cabecera (NO borrar hijos / NO borrar carpeta)
                // y reseteamos el estado a BORRADOR en cada guardado.
                $stmt = mysqli_prepare($db, $tieneEstadosComerciales
                    ? "
                        UPDATE presupuestos
                        SET id_previsita = ?,
                            id_visita = ?,
                            estado = ?,
                            estado_comercial_simulacion = NULL,
                            estado_comercial_smtp = NULL,
                            updated_at = NOW()
                        WHERE id_presupuesto = ?
                    "
                    : "
                        UPDATE presupuestos
                        SET id_previsita = ?, id_visita = ?, estado = ?, updated_at = NOW()
                        WHERE id_presupuesto = ?
                    "
                );
                mysqli_stmt_bind_param($stmt, "iisi", $id_previsita, $id_visita, $estado, $id_presupuesto);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException('Error al actualizar cabecera: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
                }
                mysqli_stmt_close($stmt);
            }
          
        // === Insertar tareas e hijos desde payload ===
        $total_mostrado_cab = 0.0;
        $total_base_cab     = 0.0;
        $impuestos_totales  = 0.0;
        $util_real_total    = 0.0;
        $porc_util_total    = null;
        $lineasInsertadas = [
            'materiales' => [],
            'mano_obra' => [],
        ];

        foreach ($tareasPayload as $indiceTarea => $t) {
            $nro                = isset($t['nro']) ? (int)$t['nro'] : 0;
            $descripcion        = sanitizarHtmlDetalleTareaPresupuesto((string)($t['descripcion'] ?? ''));
            $incluir_en_total   = !empty($t['incluir_en_total']) ? 1 : 0;
            $util_mat_pct       = isset($t['utilidad_materiales']) ? (float)$t['utilidad_materiales'] : null;
            $util_mo_pct        = isset($t['utilidad_mano_obra']) ? (float)$t['utilidad_mano_obra'] : null;
            $otros_materiales   = isset($t['otros_materiales']) ? (float)$t['otros_materiales'] : 0.0;
            $otros_mano_obra    = isset($t['otros_mano_obra']) ? (float)$t['otros_mano_obra'] : 0.0;


        // Tarea (UPSERT por nro para mantener id_presu_tarea estable y preservar fotos)
        $id_presu_tarea = obtenerIdPresuTareaPorNro($db, $id_presupuesto, $nro);

        if ($id_presu_tarea) {
            // Update tarea existente
            $stmt = mysqli_prepare($db, "
                UPDATE presupuesto_tareas
                SET descripcion = ?,
                    incluir_en_total = ?,
                    utilidad_materiales_pct = ?,
                    utilidad_mano_obra_pct = ?,
                    otros_materiales_monto = ?,
                    otros_mano_obra_monto = ?,
                    updated_at = NOW()
                WHERE id_presu_tarea = ?
            ");
            mysqli_stmt_bind_param(
                $stmt,
                "siddddi",
                $descripcion,
                $incluir_en_total,
                $util_mat_pct,
                $util_mo_pct,
                $otros_materiales,
                $otros_mano_obra,
                $id_presu_tarea
            );
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('Error al actualizar tarea: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
            }
            mysqli_stmt_close($stmt);

            // Borramos SOLO detalle recalculable (materiales/MO). Fotos NO.
            borrarDetalleRecalculableDeTarea($db, $id_presu_tarea);

        } else {
            // Insert tarea nueva
            $stmt = mysqli_prepare($db, "
                INSERT INTO presupuesto_tareas
                (id_presupuesto, nro, descripcion, incluir_en_total,
                utilidad_materiales_pct, utilidad_mano_obra_pct,
                otros_materiales_monto, otros_mano_obra_monto,
                created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            mysqli_stmt_bind_param(
                $stmt,
                "iisidddd",
                $id_presupuesto, $nro, $descripcion, $incluir_en_total,
                $util_mat_pct, $util_mo_pct, $otros_materiales, $otros_mano_obra
            );
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('Error al insertar tarea: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
            }
            $id_presu_tarea = mysqli_insert_id($db);
            mysqli_stmt_close($stmt);
        }
        

            // === Materiales
            $suma_mat_filas = 0.0;
            $materiales = $t['materiales'] ?? [];
            foreach ($materiales as $indiceMaterial => $m) {
                $id_material       = !empty($m['id_material']) ? (int)$m['id_material'] : null;
                $nombre_material   = trim((string)($m['nombre'] ?? ''));
                $cantidad          = isset($m['cantidad']) ? (float)$m['cantidad'] : 0.0;
                $catalogoMaterialValidado = $catalogosValidados['materiales'][$indiceTarea][$indiceMaterial] ?? null;
                $precio_unitario   = $catalogoMaterialValidado
                    ? (float)$catalogoMaterialValidado['precio_unitario']
                    : (isset($m['precio_unitario']) ? (float)$m['precio_unitario'] : 0.0);
                $log_alta_material = $catalogoMaterialValidado['log_alta'] ?? null;
                $log_edicion_material = $catalogoMaterialValidado['log_edicion'] ?? null;
                $porcentaje_extra  = isset($m['porcentaje_extra']) ? (float)$m['porcentaje_extra'] : 0.0;
                $orden             = isset($m['orden']) ? (int)$m['orden'] : ($indiceMaterial + 1);
                if ($orden <= 0) {
                    $orden = $indiceMaterial + 1;
                }

                $subtotal_fila = ($cantidad * $precio_unitario);
                if ($porcentaje_extra != 0) {
                    $subtotal_fila *= (1 + ($porcentaje_extra / 100.0));
                }
                $suma_mat_filas += $subtotal_fila;

                if ($tieneOrdenMaterialesPresupuesto) {
                    $stmt = mysqli_prepare($db, "
                        INSERT INTO presupuesto_tarea_material
                        (id_presu_tarea, id_material, orden, nombre_material, unidad_venta, unidad_medida,
                         cantidad, precio_unitario_usado, porcentaje_extra, subtotal_fila, log_alta, log_edicion,
                         created_at, updated_at)
                        VALUES (?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    mysqli_stmt_bind_param(
                        $stmt,
                        "iiisddddss",
                        $id_presu_tarea, $id_material, $orden, $nombre_material,
                        $cantidad, $precio_unitario, $porcentaje_extra, $subtotal_fila,
                        $log_alta_material, $log_edicion_material
                    );
                } else {
                    $stmt = mysqli_prepare($db, "
                        INSERT INTO presupuesto_tarea_material
                        (id_presu_tarea, id_material, nombre_material, unidad_venta, unidad_medida,
                         cantidad, precio_unitario_usado, porcentaje_extra, subtotal_fila, log_alta, log_edicion,
                         created_at, updated_at)
                        VALUES (?, ?, ?, NULL, NULL, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    mysqli_stmt_bind_param(
                        $stmt,
                        "iisddddss",
                        $id_presu_tarea, $id_material, $nombre_material,
                        $cantidad, $precio_unitario, $porcentaje_extra, $subtotal_fila,
                        $log_alta_material, $log_edicion_material
                    );
                }
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException('Error al insertar material: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
                }
                $idPtmNuevo = mysqli_insert_id($db);
                $idPtmAnterior = !empty($m['id_ptm']) ? (int)$m['id_ptm'] : null;
                if ($idPtmNuevo > 0) {
                    $lineasInsertadas['materiales'][] = [
                        'id_ptm_anterior' => $idPtmAnterior,
                        'id_ptm' => $idPtmNuevo,
                        'id_material' => $id_material,
                    ];
                }
                mysqli_stmt_close($stmt);
            }

            // === Mano de Obra
            $suma_mo_filas = 0.0;
            $mano_obra = $t['mano_obra'] ?? [];
            foreach ($mano_obra as $indiceManoObra => $mo) {
                $jornal_id         = !empty($mo['jornal_id']) ? (int)$mo['jornal_id'] : null;
                $nombre_jornal     = trim((string)($mo['nombre'] ?? ''));
                $cantidad          = isset($mo['cantidad']) ? (float)$mo['cantidad'] : 0.0;
                $catalogoJornalValidado = $catalogosValidados['mano_obra'][$indiceTarea][$indiceManoObra] ?? null;
                $valor_jornal      = $catalogoJornalValidado
                    ? (float)$catalogoJornalValidado['jornal_valor']
                    : (isset($mo['jornal_valor']) ? (float)$mo['jornal_valor'] : 0.0);
                $updated_at_origen_jornal = $catalogoJornalValidado['updated_at_origen'] ?? null;
                $porcentaje_extra  = isset($mo['porcentaje_extra']) ? (float)$mo['porcentaje_extra'] : 0.0;
                $dias              = isset($mo['dias']) ? (int)$mo['dias'] : 1;
                $observacion       = isset($mo['observacion']) ? trim((string)$mo['observacion']) : null;
                $orden             = isset($mo['orden']) ? (int)$mo['orden'] : ($indiceManoObra + 1);
                if ($dias <= 0) {
                    $dias = 1;
                }
                $jornales          = isset($mo['jornales']) ? (float)$mo['jornales'] : ($cantidad * $dias);
                if ($jornales < 0) {
                    $jornales = 0;
                }
                if ($orden <= 0) {
                    $orden = $indiceManoObra + 1;
                }

                $subtotal_fila = ($jornales * $valor_jornal);
                if ($porcentaje_extra != 0) {
                    $subtotal_fila *= (1 + ($porcentaje_extra / 100.0));
                }
                $suma_mo_filas += $subtotal_fila;

                if ($tieneOrdenManoObraPresupuesto) {
                    $stmt = mysqli_prepare($db, "
                        INSERT INTO presupuesto_tarea_mano_obra
                        (id_presu_tarea, id_jornal, orden, nombre_jornal,
                         cantidad, dias, valor_jornal_usado, porcentaje_extra, observacion, subtotal_fila,
                         updated_at_origen, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    mysqli_stmt_bind_param(
                        $stmt,
                        "iiisdiddsds",
                        $id_presu_tarea, $jornal_id, $orden, $nombre_jornal,
                        $cantidad, $dias, $valor_jornal, $porcentaje_extra, $observacion, $subtotal_fila,
                        $updated_at_origen_jornal
                    );
                } else {
                    $stmt = mysqli_prepare($db, "
                        INSERT INTO presupuesto_tarea_mano_obra
                        (id_presu_tarea, id_jornal, nombre_jornal,
                         cantidad, dias, valor_jornal_usado, porcentaje_extra, observacion, subtotal_fila,
                         updated_at_origen, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    mysqli_stmt_bind_param(
                        $stmt,
                        "iisdiddsds",
                        $id_presu_tarea, $jornal_id, $nombre_jornal,
                        $cantidad, $dias, $valor_jornal, $porcentaje_extra, $observacion, $subtotal_fila,
                        $updated_at_origen_jornal
                    );
                }
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException('Error al insertar mano de obra: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
                }
                $idPtmoNuevo = mysqli_insert_id($db);
                $idPtmoAnterior = !empty($mo['id_ptmo']) ? (int)$mo['id_ptmo'] : null;
                if ($idPtmoNuevo > 0) {
                    $lineasInsertadas['mano_obra'][] = [
                        'id_ptmo_anterior' => $idPtmoAnterior,
                        'id_ptmo' => $idPtmoNuevo,
                        'id_jornal' => $jornal_id,
                    ];
                }
                mysqli_stmt_close($stmt);
            }

            // === Totales contables por tarea
            $util_mat_contable = ($util_mat_pct !== null) ? ($suma_mat_filas * ($util_mat_pct / 100.0)) : 0.0;
            $util_mo_contable  = ($util_mo_pct  !== null) ? ($suma_mo_filas  * ($util_mo_pct  / 100.0)) : 0.0;

            $total_base_tarea   = ($suma_mat_filas + $suma_mo_filas + $util_mat_contable + $util_mo_contable);
            $total_mostrado_t   = $total_base_tarea + $otros_materiales + $otros_mano_obra;

            $stmt = mysqli_prepare($db, "
                UPDATE presupuesto_tareas
                SET suma_mat_filas = ?, suma_mo_filas = ?,
                    util_mat_contable = ?, util_mo_contable = ?,
                    total_base = ?, total_mostrado = ?,
                    porcentaje_utilidad = NULL,
                    iibb = NULL, ganancia35 = NULL, costo_inversion_3 = NULL, imp_cheque = NULL
                WHERE id_presu_tarea = ?
            ");
            mysqli_stmt_bind_param(
                $stmt,
                "ddddddi",
                $suma_mat_filas, $suma_mo_filas,
                $util_mat_contable, $util_mo_contable,
                $total_base_tarea, $total_mostrado_t,
                $id_presu_tarea
            );
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('Error al actualizar totales de tarea: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
            }
            mysqli_stmt_close($stmt);

            // =======================
            // === FOTOS por tarea ===
            // =======================

            // 1) Eliminar fotos marcadas (si las hay)
            $nombresAEliminar = $eliminadasPorTarea[$nro] ?? [];
            if ($nombresAEliminar) {
                foreach ($nombresAEliminar as $nombre) {
                    $nombre = (string)$nombre;
                    if ($nombre === '') { continue; }

                    // Buscar ruta actual para intentar borrar archivo físico
                    $ruta = null;
                    $q = mysqli_prepare($db, "
                        SELECT ruta_archivo FROM presupuesto_tarea_foto
                        WHERE id_presu_tarea = ? AND nombre_archivo = ?
                        LIMIT 1
                    ");
                    mysqli_stmt_bind_param($q, "is", $id_presu_tarea, $nombre);
                    mysqli_stmt_execute($q);
                    mysqli_stmt_bind_result($q, $ruta);
                    mysqli_stmt_fetch($q);
                    mysqli_stmt_close($q);

                    // Borrar registro
                    $del = mysqli_prepare($db, "
                        DELETE FROM presupuesto_tarea_foto
                        WHERE id_presu_tarea = ? AND nombre_archivo = ?
                    ");
                    mysqli_stmt_bind_param($del, "is", $id_presu_tarea, $nombre);
                    mysqli_stmt_execute($del);
                    mysqli_stmt_close($del);

                    // Intentar borrar físico
                    if ($ruta && is_file(normalizarRutaServidor($ruta))) {
                        @unlink(normalizarRutaServidor($ruta));
                    }
                }
            }

            // 2) Guardar nuevas subidas reales
            $bagArchivos = $archivosPorTarea[$nro] ?? [];
            if ($bagArchivos) {
                $dirBase  = rutaBaseFotosPresupuesto($id_presupuesto);
                $dirTarea = $dirBase . "t{$nro}/";
                asegurarDir($dirTarea);

                // Verificador MIME
                $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;

                foreach ($bagArchivos as $idx => $f) {
                    if (!isset($f['tmp_name']) || (int)$f['error'] !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    if ((int)($f['size'] ?? 0) > $MAX_SIZE) {
                        // descartamos silenciosamente archivos demasiado grandes
                        continue;
                    }

                    $ext   = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
                    if (!in_array($ext, $ALLOWED_EXT, true)) {
                        continue;
                    }

                    // Verificación MIME (best effort)
                    $okMime = true;
                    if ($finfo) {
                        $mime = @finfo_file($finfo, $f['tmp_name']);
                        if (!is_string($mime) || strpos($mime, 'image/') !== 0) {
                            $okMime = false;
                        }
                    }
                    if (!$okMime) {
                        continue;
                    }

                    $base  = pathinfo($f['name'] ?? '', PATHINFO_FILENAME);
                    $slug  = slugify($base ?: "foto");
                    $rand  = bin2hex(random_bytes(3));
                    $fname = "{$slug}_" . date('Ymd_His') . "_{$rand}." . $ext;

                    $destAbs = $dirTarea . $fname; // absoluta en servidor
                    if (!move_uploaded_file($f['tmp_name'], $destAbs)) {
                        // si falla, seguimos con el resto sin abortar toda la transacción
                        continue;
                    }

                    // Ruta “web/relativa” para servir en front
                    $rutaRel = rutaPublicaDesdeAbsoluta($destAbs);

                    // Evitar duplicados por nombre en la misma tarea
                    $del = mysqli_prepare($db, "
                        DELETE FROM presupuesto_tarea_foto
                        WHERE id_presu_tarea = ? AND nombre_archivo = ?
                    ");
                    mysqli_stmt_bind_param($del, "is", $id_presu_tarea, $fname);
                    mysqli_stmt_execute($del);
                    mysqli_stmt_close($del);

                    $stmt = mysqli_prepare($db, "
                        INSERT INTO presupuesto_tarea_foto
                        (id_presu_tarea, nombre_archivo, ruta_archivo, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    mysqli_stmt_bind_param($stmt, "iss", $id_presu_tarea, $fname, $rutaRel);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new RuntimeException('Error al insertar foto: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
                    }
                    mysqli_stmt_close($stmt);
                }

                if ($finfo) { finfo_close($finfo); }
            }

            // 3) Compat: si vinieron nombres “fotos” en payload (sin archivo real), registrar sin ruta
            if (empty($bagArchivos) && !empty($t['fotos']) && is_array($t['fotos'])) {
                $idxFoto = 0;
                foreach ($t['fotos'] as $f) {
                    $idxFoto++;
                    $nombre_archivo = '';
                    if (is_array($f)) {
                        $nombre_archivo = trim((string)($f['nombre'] ?? $f['name'] ?? $f['filename'] ?? ''));
                    } elseif (is_string($f)) {
                        $nombre_archivo = trim($f);
                    }
                    if ($nombre_archivo === '') {
                        $nombre_archivo = sprintf('t%d_img%d_%d.jpg', (int)$nro, (int)$idxFoto, time());
                    }

                    $stmt = mysqli_prepare($db, "
                        INSERT INTO presupuesto_tarea_foto
                        (id_presu_tarea, nombre_archivo, ruta_archivo, created_at)
                        VALUES (?, ?, '', NOW())
                    ");
                    mysqli_stmt_bind_param($stmt, "is", $id_presu_tarea, $nombre_archivo);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new RuntimeException('Error al insertar foto (compat): ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
                    }
                    mysqli_stmt_close($stmt);
                }
            }

            // Acumuladores cabecera
            if ($incluir_en_total) {
                $total_base_cab     += $total_base_tarea;
                $total_mostrado_cab += $total_mostrado_t;
            }
        }

        // === Actualizar cabecera con totales
        $stmt = mysqli_prepare($db, "
            UPDATE presupuestos
            SET total_base = ?, total_mostrado = ?, impuestos_totales = ?, util_real_total = ?, porcentaje_utilidad_total = ?
            WHERE id_presupuesto = ?
        ");
        mysqli_stmt_bind_param(
            $stmt,
            "dddddi",
            $total_base_cab, $total_mostrado_cab, $impuestos_totales, $util_real_total, $porc_util_total, $id_presupuesto
        );
        if (!mysqli_stmt_execute($stmt)) {
            throw new RuntimeException('Error al actualizar cabecera: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
        }
        mysqli_stmt_close($stmt);

        mysqli_commit($db);

        return [
            'ok'             => true,
            'id_presupuesto' => $id_presupuesto,
            'version'        => $version,
            'estado'         => $estado,
            'lineas'         => $lineasInsertadas
        ];
    } catch (Throwable $e) {
        mysqli_rollback($db);
        $codigo = (int)$e->getCode();
        $respuesta = ['ok' => false, 'msg' => $e->getMessage()];
        if (in_array($codigo, [400, 404, 409], true)) {
            $respuesta['http_status'] = $codigo;
        }
        return $respuesta;
    } finally {
        if ($db) { mysqli_close($db); }
    }
}

// === Helper: preparar statement o lanzar con el detalle real de MySQL ===
if (!function_exists('stmt_or_throw')) {
    function stmt_or_throw(mysqli $db, string $sql): mysqli_stmt {
        $stmt = mysqli_prepare($db, $sql);
        if ($stmt === false) {
            throw new RuntimeException('Fallo prepare(): ' . mysqli_error($db) . ' | SQL: ' . $sql);
        }
        return $stmt;
    }
}

// === Helper: bind_param dinámico (IN (...)) con referencias ===
if (!function_exists('bind_params_dynamic')) {
    function bind_params_dynamic(mysqli_stmt $stmt, string $types, array $params): bool {
        // armar array: [$stmt, $types, &p1, &p2, ...] con referencias
        $bind = [$stmt, $types];
        foreach ($params as $k => $v) { $bind[] = &$params[$k]; }
        return call_user_func_array('mysqli_stmt_bind_param', $bind);
    }
}

// === Helper: check si existe tabla (para fotos opcionales) ===
if (!function_exists('tabla_existe')) {
    function tabla_existe(mysqli $db, string $table): bool {
        $sql = "
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = ?
            LIMIT 1
        ";
        $st  = mysqli_prepare($db, $sql);
        if (!$st) return false;
        mysqli_stmt_bind_param($st, "s", $table);
        if (!mysqli_stmt_execute($st)) {
            mysqli_stmt_close($st);
            return false;
        }
        $rs = mysqli_stmt_get_result($st);
        $ok = ($rs && mysqli_fetch_row($rs));
        mysqli_stmt_close($st);
        return (bool)$ok;
    }
}

if (!function_exists('columna_existe')) {
    function columna_existe(mysqli $db, string $table, string $column): bool {
        $sql = "
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND column_name = ?
            LIMIT 1
        ";
        $st = mysqli_prepare($db, $sql);
        if (!$st) return false;
        mysqli_stmt_bind_param($st, "ss", $table, $column);
        if (!mysqli_stmt_execute($st)) {
            mysqli_stmt_close($st);
            return false;
        }
        $rs = mysqli_stmt_get_result($st);
        $ok = ($rs && mysqli_fetch_row($rs));
        mysqli_stmt_close($st);
        return (bool)$ok;
    }
}

/**
 * Obtiene el presupuesto más reciente de una previsita.
 *
 * @param int  $id_previsita
 * @param bool $incluirDetalle  Cuando true, agrega tareas + materiales + mano de obra + fotos
 * @return array ['ok'=>true, 'presupuesto'=>array|null, 'tareas'=>array] | ['ok'=>false,'msg'=>string]
 */
function obtenerPresupuestoPorPrevisita(int $id_previsita, bool $incluirDetalle = true): array
{
    $db = conectDB();
    if (!$db) {
        return ['ok' => false, 'msg' => 'No se pudo abrir conexión a la base de datos'];
    }
    mysqli_set_charset($db, 'utf8mb4');

    try {
        $sql = "
            SELECT *
            FROM `presupuestos`
            WHERE `id_previsita` = ?
            ORDER BY `version` DESC, `created_at` DESC, `id_presupuesto` DESC
            LIMIT 1
        ";
        $stmt = stmt_or_throw($db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_previsita);
        mysqli_stmt_execute($stmt);
        $res   = mysqli_stmt_get_result($stmt);
        $presu = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$presu) {
            mysqli_close($db);
            return ['ok' => true, 'presupuesto' => null, 'tareas' => []];
        }

        $tareas = $incluirDetalle ? _obtenerTareasConDetalle($db, (int)$presu['id_presupuesto']) : [];
        mysqli_close($db);

        return ['ok' => true, 'presupuesto' => $presu, 'tareas' => $tareas];

    } catch (Throwable $e) {
        mysqli_close($db);
        return ['ok' => false, 'msg' => $e->getMessage()];
    }
}



/**
 * Obtiene un presupuesto por id_presupuesto.
 *
 * @param int  $id_presupuesto
 * @param bool $incluirDetalle  Cuando true, agrega tareas + materiales + mano de obra + fotos
 * @return array ['ok'=>true, 'presupuesto'=>array|null, 'tareas'=>array] | ['ok'=>false,'msg'=>string]
 */
function obtenerPresupuestoPorId(int $id_presupuesto, bool $incluirDetalle = true): array
{
    $db = conectDB();
    if (!$db) {
        return ['ok' => false, 'msg' => 'No se pudo abrir conexión a la base de datos'];
    }
    mysqli_set_charset($db, 'utf8mb4');

    try {
        $sql = "SELECT * FROM `presupuestos` WHERE `id_presupuesto` = ? LIMIT 1";
        $stmt = stmt_or_throw($db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_presupuesto);
        mysqli_stmt_execute($stmt);
        $res   = mysqli_stmt_get_result($stmt);
        $presu = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$presu) {
            mysqli_close($db);
            return ['ok' => true, 'presupuesto' => null, 'tareas' => []];
        }

        $tareas = $incluirDetalle ? _obtenerTareasConDetalle($db, (int)$presu['id_presupuesto']) : [];
        mysqli_close($db);

        return ['ok' => true, 'presupuesto' => $presu, 'tareas' => $tareas];

    } catch (Throwable $e) {
        mysqli_close($db);
        return ['ok' => false, 'msg' => $e->getMessage()];
    }
}



/**
 * PRIVADA: trae tareas con sus materiales, mano de obra y fotos (si existen).
 * - Menos roundtrips: materiales/MO/fotos se traen en batch usando IN (...)
 * - Devuelve array de tareas, cada una con keys: materiales[], mano_obra[], fotos[]
 *
 * @param mysqli $db
 * @param int    $id_presupuesto
 * @return array
 */
function _obtenerTareasConDetalle(mysqli $db, int $id_presupuesto): array
{
    // === TAREAS
    $stmtT = stmt_or_throw($db, "
        SELECT *
        FROM `presupuesto_tareas`
        WHERE `id_presupuesto` = ?
        ORDER BY `nro` ASC, `id_presu_tarea` ASC
    ");
    mysqli_stmt_bind_param($stmtT, "i", $id_presupuesto);
    mysqli_stmt_execute($stmtT);
    $resT = mysqli_stmt_get_result($stmtT);

    $tareas = [];
    while ($row = mysqli_fetch_assoc($resT)) {
        $tareas[] = $row;
    }
    mysqli_stmt_close($stmtT);

    if (!$tareas) {
        return [];
    }

    // IDs para IN(...)
    $ids = array_map(static fn($t) => (int)$t['id_presu_tarea'], $tareas);
    if (count($ids) === 0) {
        return $tareas;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types        = str_repeat('i', count($ids));

    // === MATERIALES
    $materialesPorTarea = [];
    $ordenMateriales = columna_existe($db, 'presupuesto_tarea_material', 'orden')
        ? '`id_presu_tarea` ASC, `orden` ASC, `id_ptm` ASC'
        : '`id_presu_tarea` ASC, `id_ptm` ASC';
    $sqlMat = "SELECT * FROM `presupuesto_tarea_material` WHERE `id_presu_tarea` IN ($placeholders) ORDER BY $ordenMateriales";
    $stmtM  = stmt_or_throw($db, $sqlMat);
    bind_params_dynamic($stmtM, $types, $ids);
    mysqli_stmt_execute($stmtM);
    $resM = mysqli_stmt_get_result($stmtM);
    while ($row = mysqli_fetch_assoc($resM)) {
        $materialesPorTarea[(int)$row['id_presu_tarea']][] = $row;
    }
    mysqli_stmt_close($stmtM);

    // === MANO DE OBRA
    $moPorTarea = [];
    $ordenManoObra = columna_existe($db, 'presupuesto_tarea_mano_obra', 'orden')
        ? '`id_presu_tarea` ASC, `orden` ASC, `id_ptmo` ASC'
        : '`id_presu_tarea` ASC, `id_ptmo` ASC';
    $sqlMO = "SELECT * FROM `presupuesto_tarea_mano_obra` WHERE `id_presu_tarea` IN ($placeholders) ORDER BY $ordenManoObra";
    $stmtMO = stmt_or_throw($db, $sqlMO);
    bind_params_dynamic($stmtMO, $types, $ids);
    mysqli_stmt_execute($stmtMO);
    $resMO = mysqli_stmt_get_result($stmtMO);
    while ($row = mysqli_fetch_assoc($resMO)) {
        $moPorTarea[(int)$row['id_presu_tarea']][] = $row;
    }
    mysqli_stmt_close($stmtMO);

    // === FOTOS (mismo patrón que materiales/MO: IN(...) sobre los mismos $ids) ===
    $fotosPorTarea = [];
    // Si tu tabla se llama EXACTAMENTE 'presupuesto_tarea_foto', esto va a funcionar.
    // Si usás otro nombre, cambialo aquí.
    $sqlF = "SELECT * 
             FROM `presupuesto_tarea_foto` 
             WHERE `id_presu_tarea` IN ($placeholders)
             ORDER BY `id_presu_tarea` ASC";
    $stmtF = stmt_or_throw($db, $sqlF);
    bind_params_dynamic($stmtF, $types, $ids);
    mysqli_stmt_execute($stmtF);
    $resF = mysqli_stmt_get_result($stmtF);
    while ($row = mysqli_fetch_assoc($resF)) {
        $fotosPorTarea[(int)$row['id_presu_tarea']][] = $row;
    }
    mysqli_stmt_close($stmtF);



    // === MERGE
    foreach ($tareas as &$t) {
        $tid = (int)$t['id_presu_tarea'];
        $t['materiales'] = $materialesPorTarea[$tid] ?? [];
        $t['mano_obra']  = $moPorTarea[$tid] ?? [];
        $t['fotos']      = $fotosPorTarea[$tid] ?? [];

        if (isset($t['descripcion'])) {
            $t['descripcion'] = repararTextoMojibakePresupuestoProfundo((string)$t['descripcion']);
        }

        foreach ($t['materiales'] as &$material) {
            if (isset($material['nombre_material'])) {
                $material['nombre_material'] = repararTextoMojibakePresupuestoProfundo((string)$material['nombre_material']);
            }
        }
        unset($material);

        foreach ($t['mano_obra'] as &$mano) {
            if (isset($mano['nombre_jornal'])) {
                $mano['nombre_jornal'] = repararTextoMojibakePresupuestoProfundo((string)$mano['nombre_jornal']);
            }
            if (isset($mano['observacion'])) {
                $mano['observacion'] = repararTextoMojibakePresupuestoProfundo((string)$mano['observacion']);
            }
        }
        unset($mano);
    }
    unset($t);

    return $tareas;
}



/**
 * Borra hijos (tareas + materiales + mano_obra + fotos) de un presupuesto.
 */
function borrarHijosPresupuesto(mysqli $db, int $id_presupuesto): void
{
    // 1) Obtener ids de tareas
    $ids = [];
    $rs = mysqli_query($db, "SELECT id_presu_tarea FROM presupuesto_tareas WHERE id_presupuesto = " . (int)$id_presupuesto);
    if ($rs) {
        while ($row = mysqli_fetch_assoc($rs)) {
            $ids[] = (int)$row['id_presu_tarea'];
        }
        mysqli_free_result($rs);
    }

    if (!$ids) return;

    $in = implode(',', $ids);

    // Borrar primero fotos (si quisieras, podrías también recorrer y unlink físico aquí)
    mysqli_query($db, "DELETE FROM presupuesto_tarea_foto     WHERE id_presu_tarea IN ($in)");
    mysqli_query($db, "DELETE FROM presupuesto_tarea_mano_obra WHERE id_presu_tarea IN ($in)");
    mysqli_query($db, "DELETE FROM presupuesto_tarea_material  WHERE id_presu_tarea IN ($in)");
    mysqli_query($db, "DELETE FROM presupuesto_tareas          WHERE id_presupuesto = " . (int)$id_presupuesto);
}

/**
 * Devuelve id_presu_tarea existente para (id_presupuesto, nro) o null si no existe.
 */
function obtenerIdPresuTareaPorNro(mysqli $db, int $id_presupuesto, int $nro): ?int
{
    $stmt = mysqli_prepare($db, "
        SELECT id_presu_tarea
        FROM presupuesto_tareas
        WHERE id_presupuesto = ? AND nro = ?
        LIMIT 1
    ");
    if (!$stmt) {
        throw new RuntimeException('Error prepare obtenerIdPresuTareaPorNro: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, "ii", $id_presupuesto, $nro);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    return ($row && !empty($row['id_presu_tarea'])) ? (int)$row['id_presu_tarea'] : null;
}

/**
 * Borra SOLO el detalle recalculable de una tarea (materiales y mano de obra).
 * NO borra fotos (para preservarlas entre guardados).
 */
function borrarDetalleRecalculableDeTarea(mysqli $db, int $id_presu_tarea): void
{
    mysqli_query($db, "DELETE FROM presupuesto_tarea_mano_obra WHERE id_presu_tarea = " . (int)$id_presu_tarea);
    mysqli_query($db, "DELETE FROM presupuesto_tarea_material  WHERE id_presu_tarea = " . (int)$id_presu_tarea);
}


/* ============================
 * Helpers de archivos/rutas
 * ============================ */

/** Ruta base absoluta para fotos de un presupuesto. */
function rutaBaseFotosPresupuesto(int $id_presupuesto): string
{
    // __DIR__ = ../04-modelo
    $root = realpath(__DIR__ . '/..');                // raíz del proyecto (un nivel arriba de 04-modelo)
    if ($root === false) { $root = __DIR__ . '/..'; }
    $base = rtrim($root, '/\\') . '/uploads/presupuestos/' . (int)$id_presupuesto . '/';
    return $base;
}

/** Crea un directorio si no existe (recursivo). */
function asegurarDir(string $path): void
{
    if (!is_dir($path)) {
        $old = umask(0);
        @mkdir($path, 0775, true);
        umask($old);
    }
}

/** Slug simple para nombres de archivo (con fallback si iconv no está). */
function slugify(string $txt): string
{
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $txt);
        if ($converted !== false) { $txt = $converted; }
    }
    $txt = preg_replace('~[^\\pL\\d]+~u', '-', $txt);
    $txt = trim($txt, '-');
    $txt = preg_replace('~[^-a-z0-9]+~i', '', $txt);
    $txt = strtolower($txt);
    return $txt ?: 'archivo';
}

/** Convierte ruta absoluta del server a ruta “pública” relativa (ajustá a tu estructura). */
function rutaPublicaDesdeAbsoluta(string $abs): string
{
    $abs = str_replace('\\', '/', $abs);
    $pos = strpos($abs, '/uploads/');
    if ($pos !== false) {
        return ltrim(substr($abs, $pos), '/'); // "uploads/..."
    }
    // Fallback
    return basename($abs);
}

/** Normaliza ruta relativa (tipo "uploads/...") a absoluta en disco. */
function normalizarRutaServidor(string $rutaRel): string
{
    $rutaRel = str_replace('\\', '/', $rutaRel);
    if (preg_match('~^/?uploads/~', $rutaRel)) {
        $root = realpath(__DIR__ . '/..');
        if ($root === false) { $root = __DIR__ . '/..'; }
        return rtrim($root, '/\\') . '/' . ltrim($rutaRel, '/');
    }
    // Si ya parece absoluta, devolver tal cual
    return $rutaRel;
}

/** (Opcional) Eliminar carpeta recursiva. */
function eliminarDirectorioRecursivo(string $dir): void
{
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') continue;
        $p = $dir . '/' . $it;
        if (is_dir($p)) eliminarDirectorioRecursivo($p);
        else @unlink($p);
    }
    @rmdir($dir);
}
