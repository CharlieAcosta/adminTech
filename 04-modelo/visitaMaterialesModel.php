<?php

// P111 punto 7/8: operaciones especificas y validadas para los DOS unicos
// consumidores legitimos de materiales_visita que siguen necesitando escribir
// esa tabla (alta y borrado logico de "Materiales de la Visita"), para poder
// cerrar la ruta generica (funciones.php) sobre esta tabla sin romper Visita.
// Preserva EXACTAMENTE la semantica ya existente (fila independiente y
// absoluta por alta, borrado logico por estado) — no le aplica la
// clasificacion entero/decimal de Pedido de materiales, que es una regla
// distinta y no se extiende aqui.

require_once __DIR__ . '/schemaIntrospectionModel.php';

if (!function_exists('validarPrevisitaExisteEnConexionVisitaMateriales')) {
    function validarPrevisitaExisteEnConexionVisitaMateriales(mysqli $db, int $idPrevisita): bool
    {
        if ($idPrevisita <= 0) {
            return false;
        }
        $stmt = mysqli_prepare($db, 'SELECT id_previsita FROM previsitas WHERE id_previsita = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return (bool)$row;
    }
}

if (!function_exists('agregarMaterialVisitaEnConexion')) {
    /**
     * Reemplaza, para el alta de "Materiales de la Visita", la llamada directa
     * a simpleInsertInDB() sobre materiales_visita. Misma semantica: fila
     * nueva, independiente, con la cantidad tal como la ingreso el usuario (no
     * se le aplica la clasificacion entero/decimal de Pedido, que es una regla
     * distinta de otro modulo).
     */
    function agregarMaterialVisitaEnConexion(mysqli $db, int $idVisita, int $idMaterial, $cantidadCruda, int $idUsuario): array
    {
        if ($idVisita <= 0 || $idMaterial <= 0 || $idUsuario <= 0) {
            throw new RuntimeException('Los datos del material son invalidos.', 422);
        }
        if (!validarPrevisitaExisteEnConexionVisitaMateriales($db, $idVisita)) {
            throw new RuntimeException('No se encontro la previsita informada.', 404);
        }

        $stmtMaterial = mysqli_prepare($db, 'SELECT id_material FROM materiales WHERE id_material = ? LIMIT 1');
        if (!$stmtMaterial) {
            throw new RuntimeException('No se pudo validar el material.', 500);
        }
        mysqli_stmt_bind_param($stmtMaterial, 'i', $idMaterial);
        mysqli_stmt_execute($stmtMaterial);
        $resMaterial = mysqli_stmt_get_result($stmtMaterial);
        $materialRow = $resMaterial ? mysqli_fetch_assoc($resMaterial) : null;
        mysqli_stmt_close($stmtMaterial);
        if (!$materialRow) {
            throw new RuntimeException('El material informado no existe.', 404);
        }

        $texto = trim((string)$cantidadCruda);
        $textoNormalizado = str_replace(',', '.', $texto);
        if ($texto === '' || !is_numeric($textoNormalizado)) {
            throw new RuntimeException('La cantidad ingresada no es un numero valido.', 422);
        }
        $cantidad = (float)$textoNormalizado;
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad ingresada debe ser mayor que cero.', 422);
        }

        $stmtInsert = mysqli_prepare(
            $db,
            "INSERT INTO materiales_visita (id_visita, id_material, material_cantidad, estado) VALUES (?, ?, ?, 'activo')"
        );
        if (!$stmtInsert) {
            throw new RuntimeException('No se pudo registrar el material.', 500);
        }
        mysqli_stmt_bind_param($stmtInsert, 'iid', $idVisita, $idMaterial, $cantidad);
        $ejecutoOk = mysqli_stmt_execute($stmtInsert);
        $idInsertado = $ejecutoOk ? mysqli_stmt_insert_id($stmtInsert) : 0;
        $filasAfectadas = $ejecutoOk ? mysqli_stmt_affected_rows($stmtInsert) : 0;
        mysqli_stmt_close($stmtInsert);

        if (!$ejecutoOk || $idInsertado <= 0 || $filasAfectadas !== 1) {
            throw new RuntimeException('El material no pudo registrarse correctamente.', 500);
        }

        return [
            'ok' => true,
            'id_materiales_visita' => $idInsertado,
            'id_material' => $idMaterial,
            'material_cantidad' => $cantidad,
        ];
    }
}

if (!function_exists('eliminarMaterialVisitaEnConexion')) {
    /**
     * Reemplaza, para el borrado logico de "Materiales de la Visita", la
     * llamada directa a simpleUpdateInDB() sobre materiales_visita. Misma
     * semantica: UPDATE estado='eliminado' de una fila propia, acotado ademas
     * a la previsita informada (una fila de otra visita nunca puede borrarse
     * por este camino, aunque se adivine su id).
     */
    function eliminarMaterialVisitaEnConexion(mysqli $db, int $idVisita, int $idMaterialesVisita, int $idUsuario): array
    {
        if ($idVisita <= 0 || $idMaterialesVisita <= 0 || $idUsuario <= 0) {
            throw new RuntimeException('Los datos de la eliminacion son invalidos.', 422);
        }
        if (!validarPrevisitaExisteEnConexionVisitaMateriales($db, $idVisita)) {
            throw new RuntimeException('No se encontro la previsita informada.', 404);
        }

        $stmt = mysqli_prepare(
            $db,
            "UPDATE materiales_visita SET estado = 'eliminado' WHERE id_materiales_visita = ? AND id_visita = ?"
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo eliminar el material.', 500);
        }
        mysqli_stmt_bind_param($stmt, 'ii', $idMaterialesVisita, $idVisita);
        $ejecutoOk = mysqli_stmt_execute($stmt);
        $filasAfectadas = $ejecutoOk ? mysqli_stmt_affected_rows($stmt) : 0;
        mysqli_stmt_close($stmt);

        if (!$ejecutoOk) {
            throw new RuntimeException('El material no pudo eliminarse correctamente.', 500);
        }

        // 0 filas afectadas: o no existia, o ya estaba eliminado, o pertenece a
        // otra visita — idempotente en los dos primeros casos, no se informa
        // como error para permitir reintentos seguros.
        return ['ok' => true, 'id_materiales_visita' => $idMaterialesVisita, 'modificado' => $filasAfectadas === 1];
    }
}
