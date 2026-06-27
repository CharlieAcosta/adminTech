<?php
include_once '../04-modelo/conectDB.php';

function modGetTotalesVigenciaJornales(): array {
    $conexion = conectDB();
    $sql = "SELECT
        COALESCE(SUM(CASE
            WHEN updated_at IS NOT NULL
                 AND UNIX_TIMESTAMP(NOW()) >= UNIX_TIMESTAMP(updated_at)
                 AND FLOOR((UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(updated_at)) / 86400) >= 31
            THEN 1 ELSE 0 END), 0) AS total_desactualizadas,
        COALESCE(SUM(CASE
            WHEN updated_at IS NOT NULL
                 AND UNIX_TIMESTAMP(NOW()) >= UNIX_TIMESTAMP(updated_at)
                 AND FLOOR((UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(updated_at)) / 86400) BETWEEN 23 AND 30
            THEN 1 ELSE 0 END), 0) AS total_proximas_vencer,
        COALESCE(SUM(CASE
            WHEN updated_at IS NOT NULL
                 AND UNIX_TIMESTAMP(NOW()) >= UNIX_TIMESTAMP(updated_at)
                 AND FLOOR((UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(updated_at)) / 86400) BETWEEN 0 AND 22
            THEN 1 ELSE 0 END), 0) AS total_vigentes
        FROM tipo_jornales
        WHERE jornal_estado != 'eliminado'";
    $result = mysqli_query($conexion, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $fila = mysqli_fetch_assoc($result);
        return [
            'desactualizadas' => (int)$fila['total_desactualizadas'],
            'proximas_vencer' => (int)$fila['total_proximas_vencer'],
            'vigentes'        => (int)$fila['total_vigentes'],
        ];
    }
    return ['desactualizadas' => 0, 'proximas_vencer' => 0, 'vigentes' => 0];
}

function modGetTotalesVigenciaMateriales(): array {
    $conexion = conectDB();
    $sql = "SELECT
        COALESCE(SUM(CASE WHEN dias >= 31            THEN 1 ELSE 0 END), 0) AS total_desactualizados,
        COALESCE(SUM(CASE WHEN dias BETWEEN 23 AND 30 THEN 1 ELSE 0 END), 0) AS total_proximos_vencer,
        COALESCE(SUM(CASE WHEN dias BETWEEN 0  AND 22 THEN 1 ELSE 0 END), 0) AS total_vigentes
        FROM (
            SELECT FLOOR((UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(COALESCE(log_edicion, log_alta))) / 86400) AS dias
            FROM materiales
            WHERE estado_material <> 'Eliminado'
              AND COALESCE(log_edicion, log_alta) IS NOT NULL
              AND UNIX_TIMESTAMP(NOW()) >= UNIX_TIMESTAMP(COALESCE(log_edicion, log_alta))
        ) AS sub_ref";
    $result = mysqli_query($conexion, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $fila = mysqli_fetch_assoc($result);
        return [
            'desactualizados' => (int)$fila['total_desactualizados'],
            'proximos_vencer' => (int)$fila['total_proximos_vencer'],
            'vigentes'        => (int)$fila['total_vigentes'],
        ];
    }
    return ['desactualizados' => 0, 'proximos_vencer' => 0, 'vigentes' => 0];
}
?>
