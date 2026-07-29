<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../04-modelo/conectDB.php';
require_once __DIR__ . '/../04-modelo/schemaIntrospectionModel.php';
require_once __DIR__ . '/../04-modelo/ordenCompraWorkflowModel.php';
require_once __DIR__ . '/../04-modelo/pedidoMaterialesSnapshotModel.php';
require_once __DIR__ . '/../04-modelo/pedidoMaterialesPedidosModel.php';

if (!function_exists('leerEntradaPedidoMaterialesController')) {
    function leerEntradaPedidoMaterialesController(): array
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

if (!function_exists('responderPedidoMaterialesJson')) {
    function responderPedidoMaterialesJson(
        bool $success,
        string $message,
        array $data = [],
        array $errors = [],
        int $status = 200,
        array $extraTopLevel = []
    ): void
    {
        http_response_code($status);
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ], $extraTopLevel), JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('usuarioPedidoMaterialesController')) {
    function usuarioPedidoMaterialesController(): array
    {
        return [
            'id_usuario' => (int)($_SESSION['usuario']['id_usuario'] ?? 0),
            'perfil' => trim((string)($_SESSION['usuario']['perfil'] ?? '')),
            'email' => trim((string)($_SESSION['usuario']['email'] ?? '')),
        ];
    }
}

if (!function_exists('validarSesionPedidoMaterialesController')) {
    function validarSesionPedidoMaterialesController(): array
    {
        $usuario = usuarioPedidoMaterialesController();
        if ($usuario['id_usuario'] <= 0 || $usuario['perfil'] === '') {
            responderPedidoMaterialesJson(false, 'No hay sesion de usuario activa.', [], [], 401);
        }

        if (!perfilPuedeVerSeguimientoCompletoOrdenCompra($usuario['perfil'])) {
            responderPedidoMaterialesJson(false, 'El perfil no tiene permiso para gestionar Pedido de Materiales.', [], [], 403);
        }

        return $usuario;
    }
}

if (!function_exists('abrirConexionPedidoMaterialesController')) {
    function abrirConexionPedidoMaterialesController(): mysqli
    {
        $db = conectDB();
        if (!$db) {
            responderPedidoMaterialesJson(false, 'No se pudo conectar a la base de datos.', [], [], 500);
        }

        mysqli_set_charset($db, 'utf8mb4');

        return $db;
    }
}

if (!function_exists('validarPrevisitaPedidoMaterialesController')) {
    function validarPrevisitaPedidoMaterialesController(mysqli $db, int $idPrevisita): void
    {
        if ($idPrevisita <= 0) {
            responderPedidoMaterialesJson(false, 'La previsita es obligatoria.', [], ['id_previsita' => 'Dato obligatorio.'], 422);
        }

        $stmt = mysqli_prepare($db, 'SELECT id_previsita FROM previsitas WHERE id_previsita = ? LIMIT 1');
        if (!$stmt) {
            responderPedidoMaterialesJson(false, 'No se pudo validar la previsita.', [], [], 500);
        }

        mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            responderPedidoMaterialesJson(false, 'No se encontro la previsita informada.', [], ['id_previsita' => 'No existe.'], 404);
        }
    }
}

if (!function_exists('validarFilaSnapshotPedidoMaterialesController')) {
    function validarFilaSnapshotPedidoMaterialesController(array $fila, string $tipoEsperado, int $indice): array
    {
        $errores = [];
        $tipoFila = ($fila['tipo_fila'] ?? '') === 'agregado' ? 'agregado' : 'presupuestado';
        if ($tipoFila !== $tipoEsperado) {
            $errores[] = 'tipo_fila invalido';
        }

        $idMaterial = (int)($fila['id_material'] ?? 0);
        if ($idMaterial <= 0) {
            $errores[] = 'id_material obligatorio';
        }

        $materialTexto = trim((string)($fila['material_texto'] ?? ''));
        if ($materialTexto === '') {
            $errores[] = 'material_texto obligatorio';
        }

        $pedidos = (array)($fila['pedidos'] ?? []);
        $pedidosNormalizados = [];
        for ($numeroPedido = 1; $numeroPedido <= 5; $numeroPedido += 1) {
            $valor = $pedidos[$numeroPedido] ?? $pedidos[(string)$numeroPedido] ?? 0;
            $valorNormalizado = normalizarDecimalPedidoMaterialesSnapshotEntrada($valor);
            if ($valorNormalizado < 0) {
                $errores[] = 'pedido_' . $numeroPedido . ' invalido';
            }
            $pedidosNormalizados[$numeroPedido] = $valorNormalizado;
        }

        $estadoAutorizacion = trim((string)($fila['estado_autorizacion'] ?? 'sin_solicitud'));
        if (!in_array($estadoAutorizacion, pedidoMaterialesSnapshotEstadosPermitidos(), true)) {
            $errores[] = 'estado_autorizacion invalido';
        }

        return [
            'errores' => $errores,
            'fila' => [
                'tipo_fila' => $tipoEsperado,
                'id_tarea' => isset($fila['id_tarea']) && (int)$fila['id_tarea'] > 0 ? (int)$fila['id_tarea'] : null,
                'tarea_nro' => isset($fila['tarea_nro']) && (int)$fila['tarea_nro'] > 0 ? (int)$fila['tarea_nro'] : null,
                'tarea_titulo' => trim((string)($fila['tarea_titulo'] ?? '')),
                'id_material' => $idMaterial,
                'material_texto' => $materialTexto,
                'cantidad_inicial' => normalizarDecimalPedidoMaterialesSnapshotEntrada($fila['cantidad_inicial'] ?? 0),
                'cantidad_solicitada' => normalizarDecimalPedidoMaterialesSnapshotEntrada($fila['cantidad_solicitada'] ?? 0),
                'pedidos' => $pedidosNormalizados,
                'estado_autorizacion' => $estadoAutorizacion,
                'autorizacion_adicional' => array_key_exists('autorizacion_adicional', $fila)
                    ? normalizarDecimalPedidoMaterialesSnapshotEntrada($fila['autorizacion_adicional'])
                    : null,
                'pedido_autorizacion_previo' => array_key_exists('pedido_autorizacion_previo', $fila)
                    ? normalizarDecimalPedidoMaterialesSnapshotEntrada($fila['pedido_autorizacion_previo'])
                    : null,
                'orden_visual' => $indice + 1,
            ],
        ];
    }
}

if (!function_exists('validarSnapshotPedidoMaterialesController')) {
    function validarSnapshotPedidoMaterialesController(array $snapshot): array
    {
        $idPrevisita = (int)($snapshot['id_previsita'] ?? 0);
        $pedidoActivo = normalizarNumeroPedidoMaterialesSnapshot($snapshot['pedido_activo'] ?? 1);
        $pedidoMaximoVisible = normalizarNumeroPedidoMaterialesSnapshot($snapshot['pedido_maximo_visible'] ?? $pedidoActivo, $pedidoActivo);
        $accionGuardado = (($snapshot['accion_guardado'] ?? '') === 'realizar') ? 'realizar' : 'guardar';
        $finalizado = !empty($snapshot['finalizado']);

        $errores = [];
        if ($idPrevisita <= 0) {
            $errores['id_previsita'] = 'Dato obligatorio.';
        }
        if ($pedidoMaximoVisible < $pedidoActivo) {
            $errores['pedido_maximo_visible'] = 'Debe ser mayor o igual al pedido activo.';
        }

        $materialesPresupuestados = [];
        foreach ((array)($snapshot['materiales_presupuestados'] ?? []) as $indice => $fila) {
            $validacionFila = validarFilaSnapshotPedidoMaterialesController($fila, 'presupuestado', $indice);
            if (!empty($validacionFila['errores'])) {
                $errores['materiales_presupuestados_' . $indice] = implode(', ', $validacionFila['errores']);
            }
            $materialesPresupuestados[] = $validacionFila['fila'];
        }

        $materialesAgregados = [];
        foreach ((array)($snapshot['materiales_agregados'] ?? []) as $indice => $fila) {
            $validacionFila = validarFilaSnapshotPedidoMaterialesController($fila, 'agregado', $indice);
            if (!empty($validacionFila['errores'])) {
                $errores['materiales_agregados_' . $indice] = implode(', ', $validacionFila['errores']);
            }
            $materialesAgregados[] = $validacionFila['fila'];
        }

        return [
            'errores' => $errores,
            'snapshot' => [
                'id_previsita' => $idPrevisita,
                'pedido_activo' => $pedidoActivo,
                'pedido_maximo_visible' => $pedidoMaximoVisible,
                'finalizado' => $finalizado,
                'accion_guardado' => $accionGuardado,
                'materiales_presupuestados' => $materialesPresupuestados,
                'materiales_agregados' => $materialesAgregados,
            ],
        ];
    }
}

if (!function_exists('validarConfirmacionPedidoMaterialesController')) {
    function validarConfirmacionPedidoMaterialesController(
        array $snapshot,
        int $numeroPedido
    ): array {
        $errores = [];
        $pedidoActivo = (int)($snapshot['pedido_activo'] ?? 0);
        $pedidoMaximoVisible = (int)($snapshot['pedido_maximo_visible'] ?? 0);

        if ($numeroPedido < 1 || $numeroPedido > 5) {
            $errores['numero_pedido'] = 'Debe ser un numero entre 1 y 5.';
        } elseif ($pedidoActivo !== $numeroPedido) {
            $errores['numero_pedido'] = 'Debe coincidir con el pedido activo del snapshot.';
        }

        if ($pedidoMaximoVisible < $numeroPedido) {
            $errores['pedido_maximo_visible'] = 'El pedido a confirmar debe estar visible.';
        }

        if (!empty($snapshot['finalizado'])) {
            $errores['finalizado'] = 'El flujo de Pedido de Materiales ya esta finalizado.';
        }

        $tieneCantidadPedido = false;
        $tieneAutorizacionPendiente = false;
        $filas = array_merge(
            (array)($snapshot['materiales_presupuestados'] ?? []),
            (array)($snapshot['materiales_agregados'] ?? [])
        );

        foreach ($filas as $fila) {
            if (($fila['estado_autorizacion'] ?? 'sin_solicitud') === 'pendiente') {
                $tieneAutorizacionPendiente = true;
            }

            $pedidos = (array)($fila['pedidos'] ?? []);
            $cantidadPedido = normalizarDecimalPedidoMaterialesSnapshotEntrada(
                $pedidos[$numeroPedido] ?? $pedidos[(string)$numeroPedido] ?? 0
            );
            if ($cantidadPedido > 0) {
                $tieneCantidadPedido = true;
            }
        }

        if ($tieneAutorizacionPendiente) {
            $errores['autorizaciones'] = 'No se puede confirmar mientras existan autorizaciones pendientes.';
        }

        if (!$tieneCantidadPedido) {
            $errores['cantidades'] = 'El pedido debe contener al menos una cantidad mayor que cero.';
        }

        return $errores;
    }
}

$usuario = validarSesionPedidoMaterialesController();
$input = leerEntradaPedidoMaterialesController();
$accion = trim((string)($input['accion'] ?? 'guardar_snapshot'));
$db = abrirConexionPedidoMaterialesController();

if (!pedidoMaterialesSnapshotTablasMinimasDisponibles($db)) {
    responderPedidoMaterialesJson(false, 'Las tablas de snapshot de Pedido de Materiales no estan disponibles. Debe aplicarse la migracion 2026-07-07-A_pedido_materiales_snapshots.sql.', [], [], 409);
}

try {
    if ($accion === 'obtener_snapshot') {
        $idPrevisita = (int)($input['id_previsita'] ?? 0);
        validarPrevisitaPedidoMaterialesController($db, $idPrevisita);
        $snapshot = obtenerPedidoMaterialesSnapshotPorPrevisitaEnConexion($db, $idPrevisita);

        responderPedidoMaterialesJson(
            true,
            $snapshot ? 'Snapshot recuperado correctamente.' : 'No existe snapshot guardado para la previsita.',
            ['snapshot' => $snapshot]
        );
    }

    if ($accion === 'confirmar_pedido') {
        if (!pedidoMaterialesPedidosTablasMinimasDisponibles($db)) {
            responderPedidoMaterialesJson(
                false,
                'Las tablas de pedidos confirmados no estan disponibles. Debe aplicarse la migracion 2026-07-29-A_pedido_materiales_pedidos_confirmados.sql.',
                [],
                [],
                409
            );
        }

        $snapshotInput = isset($input['snapshot']) && is_array($input['snapshot'])
            ? $input['snapshot']
            : [];
        $validacion = validarSnapshotPedidoMaterialesController($snapshotInput);
        if (!empty($validacion['errores'])) {
            responderPedidoMaterialesJson(
                false,
                'El snapshot contiene datos invalidos.',
                [],
                $validacion['errores'],
                422
            );
        }

        $numeroPedidoValidado = filter_var(
            $input['numero_pedido'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 5]]
        );
        if ($numeroPedidoValidado === false) {
            responderPedidoMaterialesJson(
                false,
                'El numero de pedido debe estar entre 1 y 5.',
                [],
                ['numero_pedido' => 'Valor invalido.'],
                422
            );
        }
        $numeroPedido = (int)$numeroPedidoValidado;

        $erroresConfirmacion = validarConfirmacionPedidoMaterialesController(
            $validacion['snapshot'],
            $numeroPedido
        );
        if ($erroresConfirmacion) {
            responderPedidoMaterialesJson(
                false,
                'El pedido no cumple las condiciones para confirmarse.',
                [],
                $erroresConfirmacion,
                422
            );
        }

        validarPrevisitaPedidoMaterialesController(
            $db,
            (int)$validacion['snapshot']['id_previsita']
        );
        $resultadoConfirmacion = confirmarPedidoMaterialesEnConexion(
            $db,
            $validacion['snapshot'],
            $numeroPedido,
            (int)$usuario['id_usuario']
        );
        $mensajeConfirmacion = !empty($resultadoConfirmacion['ya_existia'])
            ? 'El pedido ya estaba confirmado y se recupero de forma idempotente.'
            : 'Pedido confirmado correctamente.';

        responderPedidoMaterialesJson(
            true,
            $mensajeConfirmacion,
            $resultadoConfirmacion,
            [],
            200,
            $resultadoConfirmacion
        );
    }

    if ($accion !== 'guardar_snapshot') {
        responderPedidoMaterialesJson(false, 'La accion solicitada no es valida.', [], ['accion' => 'Accion invalida.'], 422);
    }

    $snapshotInput = isset($input['snapshot']) && is_array($input['snapshot']) ? $input['snapshot'] : $input;
    $validacion = validarSnapshotPedidoMaterialesController($snapshotInput);
    if (!empty($validacion['errores'])) {
        responderPedidoMaterialesJson(false, 'El snapshot contiene datos invalidos.', [], $validacion['errores'], 422);
    }

    validarPrevisitaPedidoMaterialesController($db, (int)$validacion['snapshot']['id_previsita']);
    $resultado = guardarPedidoMaterialesSnapshotEnConexion(
        $db,
        $validacion['snapshot'],
        (int)$usuario['id_usuario']
    );

    responderPedidoMaterialesJson(true, 'Snapshot guardado correctamente.', ['snapshot' => $resultado]);
} catch (Throwable $e) {
    $status = in_array((int)$e->getCode(), [400, 404, 409, 422], true)
        ? (int)$e->getCode()
        : 500;
    responderPedidoMaterialesJson(false, $e->getMessage(), [], [], $status);
} finally {
    mysqli_close($db);
}
