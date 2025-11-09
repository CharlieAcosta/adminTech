<?php
// 04-modelo/tareasArchivadasListarModel.php
require_once __DIR__ . '/conectDB.php';

/**
 * Lista plantillas de tareas archivadas con paginación y búsqueda opcional.
 * @param string $q   texto libre (busca en nombre_plantilla y nombre_original)
 * @param int    $page
 * @param int    $perPage
 * @return array { ok, items:[], total, page, per_page }
 */
function listarTareasArchivadas(string $q = '', int $page = 1, int $perPage = 20): array
{
    $db = conectDB();
    $db->set_charset('utf8mb4');

    $offset = ($page - 1) * $perPage;
    $like = '%' . $db->real_escape_string($q) . '%';

    // Total
    if ($q !== '') {
        $sqlTotal = "SELECT COUNT(*) AS c
                     FROM archivadas_tareas
                     WHERE nombre_plantilla LIKE ? OR nombre_original LIKE ?";
        $stmtTot = $db->prepare($sqlTotal);
        $stmtTot->bind_param('ss', $like, $like);
    } else {
        $sqlTotal = "SELECT COUNT(*) AS c FROM archivadas_tareas";
        $stmtTot = $db->prepare($sqlTotal);
    }
    if (!$stmtTot->execute()) {
        throw new Exception('Error total: ' . $stmtTot->error);
    }
    $resTot = $stmtTot->get_result()->fetch_assoc();
    $total = (int)($resTot['c'] ?? 0);
    $stmtTot->close();

    // Items
    if ($q !== '') {
        $sql = "SELECT id_arch_tarea, nombre_plantilla, nombre_original, created_at
                FROM archivadas_tareas
                WHERE nombre_plantilla LIKE ? OR nombre_original LIKE ?
                ORDER BY id_arch_tarea DESC
                LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('ssii', $like, $like, $perPage, $offset);
    } else {
        $sql = "SELECT id_arch_tarea, nombre_plantilla, nombre_original, created_at
                FROM archivadas_tareas
                ORDER BY id_arch_tarea DESC
                LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('ii', $perPage, $offset);
    }

    if (!$stmt->execute()) {
        throw new Exception('Error listado: ' . $stmt->error);
    }

    $items = [];
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) {
        $items[] = [
            'id_arch_tarea'    => (int)$row['id_arch_tarea'],
            'nombre_plantilla' => (string)$row['nombre_plantilla'],
            'nombre_original'  => (string)$row['nombre_original'],
            'created_at'       => (string)$row['created_at'],
        ];
    }
    $stmt->close();
    $db->close();

    return [
        'ok'        => true,
        'items'     => $items,
        'total'     => $total,
        'page'      => $page,
        'per_page'  => $perPage,
    ];
}
