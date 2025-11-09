<?php
// 04-modelo/tareasArchivadasGuardarModel.php
// Inserta una tarea (y sus hijos) en archivadas_tareas / archivadas_tarea_material / archivadas_tarea_mano_obra

require_once __DIR__ . '/conectDB.php'; // debe exponer conectDB(): mysqli

/**
 * Estructura esperada ($payload), proveniente de __tareaArchivadaPreview en JS:
 * [
 *   'nombre_plantilla'       => string,
 *   'source_id_presupuesto'  => int|null,
 *   'source_id_presu_tarea'  => int|null,
 *   'tarea' => [
 *      'nro'                 => int,
 *      'descripcion'         => string,
 *      'incluir_en_total'    => 0|1,
 *      'utilidad_materiales' => float|null,
 *      'utilidad_mano_obra'  => float|null,
 *      'otros_materiales'    => float,
 *      'otros_mano_obra'     => float,
 *      'materiales' => [
 *          { id_material, nombre, cantidad, precio_unitario, porcentaje_extra }, ...
 *      ],
 *      'mano_obra' => [
 *          { jornal_id, nombre, cantidad, jornal_valor, porcentaje_extra }, ...
 *      ]
 *   ]
 * ]
 *
 * @return array { ok: bool, id_arch_tarea?: int, msg?: string, error?: string }
 */
function guardarTareaArchivada(array $payload)
{
    $db = conectDB();
    $db->set_charset('utf8mb4');

    // -------- Validación mínima --------
    $nombrePlantilla = trim((string)($payload['nombre_plantilla'] ?? ''));
    if ($nombrePlantilla === '') {
        return ['ok' => false, 'msg' => 'nombre_plantilla vacío'];
    }
    $tarea = $payload['tarea'] ?? null;
    if (!is_array($tarea)) {
        return ['ok' => false, 'msg' => 'tarea inválida'];
    }

    // Cabecera (campos básicos)
    $nombreOriginal = trim((string)($tarea['descripcion'] ?? ''));
    $incluirEnTotal = (int)($tarea['incluir_en_total'] ?? 1);

    // Números (permitimos null en utilidades usando NULLIF en SQL)
    $utilMat = isset($tarea['utilidad_materiales']) && $tarea['utilidad_materiales'] !== '' ? (string)(+($tarea['utilidad_materiales'])) : '';
    $utilMo  = isset($tarea['utilidad_mano_obra'])  && $tarea['utilidad_mano_obra']  !== '' ? (string)(+($tarea['utilidad_mano_obra']))  : '';
    $otrosMat = (float)($tarea['otros_materiales'] ?? 0);
    $otrosMo  = (float)($tarea['otros_mano_obra']  ?? 0);

    // Trazabilidad (pueden ser null)
    $sourcePresupuestoId = array_key_exists('source_id_presupuesto', $payload) && $payload['source_id_presupuesto'] !== null
        ? (string)(int)$payload['source_id_presupuesto'] : '';
    $sourcePresuTareaId  = array_key_exists('source_id_presu_tarea',  $payload) && $payload['source_id_presu_tarea']  !== null
        ? (string)(int)$payload['source_id_presu_tarea']  : '';

    // Hijos
    $materiales = is_array($tarea['materiales'] ?? null) ? $tarea['materiales'] : [];
    $manoObra   = is_array($tarea['mano_obra']   ?? null) ? $tarea['mano_obra']   : [];

    // -------- Transacción --------
    $db->begin_transaction();
    try {
        // =======================
        // 1) Insert cabecera
        // Usamos NULLIF(?, '') para permitir NULL reales en DECIMAL/INT
        // =======================
        $sqlCab = "
            INSERT INTO archivadas_tareas
                (nombre_original, nombre_plantilla, descripcion, incluir_en_total,
                 utilidad_materiales_pct, utilidad_mano_obra_pct,
                 otros_materiales_monto, otros_mano_obra_monto,
                 source_id_presupuesto,  source_id_presu_tarea,
                 creado_por, created_at, updated_at)
            VALUES
                (?,?,?,?,
                 NULLIF(?, ''), NULLIF(?, ''),
                 ?, ?,
                 NULLIF(?, ''), NULLIF(?, ''),
                 NULL, NOW(), NOW())";

        $stmt = $db->prepare($sqlCab);
        if (!$stmt) {
            throw new Exception('Prepare cabecera: ' . $db->error);
        }
        // tipos: s s s i s s d d s s
        $stmt->bind_param(
            'sssissddss',
            $nombreOriginal,
            $nombrePlantilla,
            $nombreOriginal,  // usamos también en descripcion (igual a presupuesto_tareas.descripcion)
            $incluirEnTotal,
            $utilMat,         // string o '' -> NULLIF
            $utilMo,          // string o '' -> NULLIF
            $otrosMat,
            $otrosMo,
            $sourcePresupuestoId, // string o '' -> NULLIF
            $sourcePresuTareaId   // string o '' -> NULLIF
        );
        if (!$stmt->execute()) {
            throw new Exception('Exec cabecera: ' . $stmt->error);
        }
        $idArchTarea = (int)$db->insert_id;
        $stmt->close();

        // =======================
        // 2) Insert materiales
        // =======================
        if (!empty($materiales)) {
            $sqlMat = "
                INSERT INTO archivadas_tarea_material
                    (id_arch_tarea, id_material, nombre_material, unidad_venta, unidad_medida,
                     cantidad, precio_unitario_usado, porcentaje_extra, subtotal_fila,
                     log_alta, log_edicion, created_at, updated_at)
                VALUES
                    (?,?,?,?,?,
                     ?,?,?,?,
                     NULL, NULL, NOW(), NOW())";
            $stmtM = $db->prepare($sqlMat);
            if (!$stmtM) {
                throw new Exception('Prepare materiales: ' . $db->error);
            }

            foreach ($materiales as $m) {
                $idMat   = array_key_exists('id_material', $m) && $m['id_material'] !== null ? (int)$m['id_material'] : null;
                $nombre  = trim((string)($m['nombre'] ?? ''));
                $cant    = (float)($m['cantidad'] ?? 0);
                $precio  = (float)($m['precio_unitario'] ?? 0);
                $extra   = (float)($m['porcentaje_extra'] ?? 0);

                $subtotal = round($cant * $precio * (1 + $extra / 100), 2);

                // En el payload no bajamos unidad_venta / unidad_medida -> NULL
                $unidadVenta  = null;
                $unidadMedida = null;

                // tipos: i i s s s d d d d
                $stmtM->bind_param(
                    'iisssdddd',
                    $idArchTarea,
                    $idMat,
                    $nombre,
                    $unidadVenta,
                    $unidadMedida,
                    $cant,
                    $precio,
                    $extra,
                    $subtotal
                );
                if (!$stmtM->execute()) {
                    throw new Exception('Exec material: ' . $stmtM->error);
                }
            }
            $stmtM->close();
        }

        // =======================
        // 3) Insert mano de obra
        // =======================
        if (!empty($manoObra)) {
            $sqlMo = "
                INSERT INTO archivadas_tarea_mano_obra
                    (id_arch_tarea, id_jornal, nombre_jornal,
                     cantidad, dias, valor_jornal_usado,
                     porcentaje_extra, observacion, subtotal_fila,
                     updated_at_origen, created_at, updated_at)
                VALUES
                    (?,?,?,
                     ?,?,?,
                     ?,NULL,?,
                     NULL, NOW(), NOW())";
            $stmtO = $db->prepare($sqlMo);
            if (!$stmtO) {
                throw new Exception('Prepare mano_obra: ' . $db->error);
            }

            foreach ($manoObra as $o) {
                $idJ     = array_key_exists('jornal_id', $o) && $o['jornal_id'] !== null ? (int)$o['jornal_id'] : null;
                $nombre  = trim((string)($o['nombre'] ?? ''));
                $cant    = (float)($o['cantidad'] ?? 0);
                $dias    = 1; // no lo traemos desde UI; default 1
                $valor   = (float)($o['jornal_valor'] ?? 0);
                $extra   = (float)($o['porcentaje_extra'] ?? 0);

                $subtotal = round($cant * $valor * (1 + $extra / 100), 2);

                // tipos: i i s d i d d d
                $stmtO->bind_param(
                    'iisdiddd',
                    $idArchTarea,
                    $idJ,
                    $nombre,
                    $cant,
                    $dias,
                    $valor,
                    $extra,
                    $subtotal
                );
                if (!$stmtO->execute()) {
                    throw new Exception('Exec mano_obra: ' . $stmtO->error);
                }
            }
            $stmtO->close();
        }

        $db->commit();
        return ['ok' => true, 'id_arch_tarea' => $idArchTarea];

    } catch (Throwable $e) {
        $db->rollback();
        return ['ok' => false, 'msg' => 'Error al guardar tarea archivada', 'error' => $e->getMessage()];
    } finally {
        if ($db && $db->ping()) {
            $db->close();
        }
    }
}
