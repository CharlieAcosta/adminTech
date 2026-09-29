<?php

// P111 punto 7/8: endpoint especifico y autenticado para las dos unicas
// operaciones legitimas de "Materiales de la Visita" sobre materiales_visita
// (alta y borrado logico), que hasta ahora escribian via la ruta generica
// funcionCall de 06-funciones_php/funciones.php sin ninguna validacion de
// sesion ni de referencias. Reemplaza esas dos llamadas puntuales sin alterar
// la semantica de Visita.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../04-modelo/conectDB.php';
require_once __DIR__ . '/../04-modelo/visitaMaterialesModel.php';

if (!function_exists('leerEntradaVisitaMaterialesController')) {
    function leerEntradaVisitaMaterialesController(): array
    {
        $json = [];
        $raw = file_get_contents('php://input');
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $json = $decoded;
            }
        }

        return array_merge($_GET, $_POST, $json);
    }
}

if (!function_exists('responderVisitaMaterialesJson')) {
    function responderVisitaMaterialesJson(bool $success, string $message, array $data = [], array $errors = [], int $status = 200): void
    {
        http_response_code($status);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$idUsuarioSesion = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
if ($idUsuarioSesion <= 0) {
    responderVisitaMaterialesJson(false, 'No hay sesion de usuario activa.', [], [], 401);
}

$input = leerEntradaVisitaMaterialesController();
$accion = trim((string)($input['accion'] ?? ''));

$db = conectDB();
mysqli_set_charset($db, 'utf8mb4');

try {
    if ($accion === 'agregar_material_visita') {
        $idVisita = filter_var($input['id_visita'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $idMaterial = filter_var($input['id_material'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $cantidadCruda = $input['material_cantidad'] ?? null;

        if ($idVisita === false || $idMaterial === false || $cantidadCruda === null || trim((string)$cantidadCruda) === '') {
            responderVisitaMaterialesJson(false, 'Los datos del material no son validos.', [], [], 422);
        }

        $resultado = agregarMaterialVisitaEnConexion($db, (int)$idVisita, (int)$idMaterial, $cantidadCruda, $idUsuarioSesion);
        responderVisitaMaterialesJson(true, 'Material registrado correctamente.', $resultado);
    }

    if ($accion === 'eliminar_material_visita') {
        $idVisita = filter_var($input['id_visita'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $idMaterialesVisita = filter_var($input['id_materiales_visita'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($idVisita === false || $idMaterialesVisita === false) {
            responderVisitaMaterialesJson(false, 'Los datos de la eliminacion no son validos.', [], [], 422);
        }

        $resultado = eliminarMaterialVisitaEnConexion($db, (int)$idVisita, (int)$idMaterialesVisita, $idUsuarioSesion);
        responderVisitaMaterialesJson(true, 'Material eliminado correctamente.', $resultado);
    }

    responderVisitaMaterialesJson(false, 'La accion solicitada no es valida.', [], ['accion' => 'Accion invalida.'], 422);
} catch (RuntimeException $e) {
    responderVisitaMaterialesJson(false, $e->getMessage(), [], [], $e->getCode() ?: 422);
} finally {
    mysqli_close($db);
}
