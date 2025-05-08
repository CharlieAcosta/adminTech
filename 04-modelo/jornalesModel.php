<?php
include_once '../06-funciones_php/funciones.php';
include_once '../04-modelo/conectDB.php'; 
function modGetAllRegistros($filtro = 'todos') {
    $conexion = conectDB(); // usa mysqli procedural
    $registros = [];

    $sql = "SELECT jornal_id, jornal_descripcion, jornal_codigo, jornal_valor, jornal_estado FROM tipo_jornales";

    if ($filtro === 'sinEliminados') {
        $sql .= " WHERE jornal_estado != 'eliminado'";
        $result = mysqli_query($conexion, $sql);
    } elseif ($filtro !== 'todos') {
        $sql .= " WHERE jornal_estado = ?";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, 's', $filtro);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conexion, $sql);
    }

    if ($result && mysqli_num_rows($result) > 0) {
        while ($fila = mysqli_fetch_assoc($result)) {
            $registros[] = $fila;
        }
    }

    return $registros;
}
?>

