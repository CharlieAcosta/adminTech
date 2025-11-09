<?php
// 04-modelo/tareasArchivadasObtenerModel.php
require_once __DIR__ . '/conectDB.php';

/**
 * Devuelve una plantilla completa: cabecera + materiales + mano de obra.
 * @return array { ok, tarea: { nombre_plantilla, nombre_original, incluir_en_total, utilidad_* , otros_* , materiales:[], mano_obra:[] } }
 */
function obtenerTareaArchivada(int $idArchTarea): array
{
    $db = conectDB();
    $db->set_charset('utf8mb4');

    // Cabecera
    $sqlT = "SELECT id_arch_tarea, nombre_plantilla, nombre_original, descripcion,
                    incluir_en_total,
                    utilidad_materiales_pct, utilidad_mano_obra_pct,
                    otros_materiales_monto, otros_mano_obra_monto,
                    created_at
             FROM archivadas_tareas
             WHERE id_arch_tarea = ?";
    $stmtT = $db->prepare($sqlT);
    if (!$stmtT) throw new Exception('Prepare cabecera: ' . $db->error);
    $stmtT->bind_param('i', $idArchTarea);
    if (!$stmtT->execute()) throw new Exception('Exec cabecera: ' . $stmtT->error);
    $resT = $stmtT->get_result();
    $rowT = $resT->fetch_assoc();
    $stmtT->close();

    if (!$rowT) {
        $db->close();
        return ['ok' => false, 'msg' => 'Plantilla no encontrada'];
    }

    // Materiales
    $materiales = [];
    $sqlM = "SELECT id_atm, id_material, nombre_material, unidad_venta, unidad_medida,
                    cantidad, precio_unitario_usado, porcentaje_extra, subtotal_fila
             FROM archivadas_tarea_material
             WHERE id_arch_tarea = ?
             ORDER BY id_atm ASC";
    $stmtM = $db->prepare($sqlM);
    if (!$stmtM) throw new Exception('Prepare materiales: ' . $db->error);
    $stmtM->bind_param('i', $idArchTarea);
    if (!$stmtM->execute()) throw new Exception('Exec materiales: ' . $stmtM->error);
    $rM = $stmtM->get_result();
    while ($rm = $rM->fetch_assoc()) {
        $materiales[] = [
            'id_material'         => $rm['id_material'] !== null ? (int)$rm['id_material'] : null,
            'nombre'              => (string)$rm['nombre_material'],
            'cantidad'            => (float)$rm['cantidad'],
            'precio_unitario'     => (float)$rm['precio_unitario_usado'],
            'porcentaje_extra'    => (float)$rm['porcentaje_extra'],
        ];
    }
    $stmtM->close();

    // Mano de obra
    $manoObra = [];
    $sqlO = "SELECT id_atmo, id_jornal, nombre_jornal, cantidad, dias,
                    valor_jornal_usado, porcentaje_extra, subtotal_fila
             FROM archivadas_tarea_mano_obra
             WHERE id_arch_tarea = ?
             ORDER BY id_atmo ASC";
    $stmtO = $db->prepare($sqlO);
    if (!$stmtO) throw new Exception('Prepare mano_obra: ' . $db->error);
    $stmtO->bind_param('i', $idArchTarea);
    if (!$stmtO->execute()) throw new Exception('Exec mano_obra: ' . $stmtO->error);
    $rO = $stmtO->get_result();
    while ($ro = $rO->fetch_assoc()) {
        $manoObra[] = [
            'jornal_id'           => $ro['id_jornal'] !== null ? (int)$ro['id_jornal'] : null,
            'nombre'              => (string)$ro['nombre_jornal'],
            'cantidad'            => (float)$ro['cantidad'],
            'jornal_valor'        => (float)$ro['valor_jornal_usado'],
            'porcentaje_extra'    => (float)$ro['porcentaje_extra'],
        ];
    }
    $stmtO->close();

    $db->close();

    return [
        'ok' => true,
        'tarea' => [
            'id_arch_tarea'          => (int)$rowT['id_arch_tarea'],
            'nombre_plantilla'       => (string)$rowT['nombre_plantilla'],
            'nombre_original'        => (string)$rowT['nombre_original'],
            'descripcion'            => (string)$rowT['descripcion'],
            'incluir_en_total'       => (int)$rowT['incluir_en_total'],
            'utilidad_materiales'    => $rowT['utilidad_materiales_pct'] !== null ? (float)$rowT['utilidad_materiales_pct'] : null,
            'utilidad_mano_obra'     => $rowT['utilidad_mano_obra_pct']  !== null ? (float)$rowT['utilidad_mano_obra_pct']  : null,
            'otros_materiales'       => (float)$rowT['otros_materiales_monto'],
            'otros_mano_obra'        => (float)$rowT['otros_mano_obra_monto'],
            'materiales'             => $materiales,
            'mano_obra'              => $manoObra
        ]
    ];
}
