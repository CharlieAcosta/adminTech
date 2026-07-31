<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../04-modelo/conectDB.php';
require_once __DIR__ . '/../04-modelo/schemaIntrospectionModel.php';
require_once __DIR__ . '/../04-modelo/ordenCompraWorkflowModel.php';
require_once __DIR__ . '/../04-modelo/pedidoMaterialesSnapshotModel.php';
require_once __DIR__ . '/../04-modelo/pedidoMaterialesAutorizacionesModel.php';
require_once __DIR__ . '/../04-modelo/pedidoMaterialesPedidosModel.php';
require_once __DIR__ . '/../04-modelo/pedidoMaterialesPdfModel.php';
require_once __DIR__ . '/../04-modelo/pedidoMaterialesEnviosModel.php';

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

if (!function_exists('validarIdPedidoMaterialesController')) {
    function validarIdPedidoMaterialesController($valor): int
    {
        $idPedido = filter_var(
            $valor,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($idPedido === false) {
            responderPedidoMaterialesJson(
                false,
                'El pedido confirmado informado no es valido.',
                [],
                ['id_pedido_materiales_pedido' => 'Debe ser un entero mayor que cero.'],
                422
            );
        }

        return (int)$idPedido;
    }
}

if (!function_exists('descargarPdfPedidoMaterialesController')) {
    function descargarPdfPedidoMaterialesController(mysqli $db, int $idPedido): void
    {
        if (!pedidoMaterialesPdfTablasMinimasDisponibles($db)) {
            responderPedidoMaterialesJson(
                false,
                'La persistencia PDF de Pedido de Materiales no esta disponible. Debe aplicarse la migracion 2026-07-29-B_pedido_materiales_pedido_documentos.sql.',
                [],
                [],
                409
            );
        }

        $documento = obtenerDocumentoPdfPedidoMaterialesConfirmado($db, $idPedido);
        if (!$documento) {
            responderPedidoMaterialesJson(
                false,
                'El pedido confirmado todavia no tiene un PDF generado.',
                [],
                [],
                404
            );
        }

        $rutaAbsoluta = resolverRutaAbsolutaDocumentoPdfPedidoMateriales($documento);
        $nombreArchivo = basename((string)($documento['nombre_archivo'] ?? 'pedido_materiales.pdf'));
        if (!preg_match('/^[A-Za-z0-9_.-]+\.pdf$/', $nombreArchivo)) {
            $nombreArchivo = 'pedido_materiales_' . $idPedido . '.pdf';
        }

        $archivo = fopen($rutaAbsoluta, 'rb');
        if ($archivo === false) {
            responderPedidoMaterialesJson(false, 'No se pudo abrir el PDF solicitado.', [], [], 404);
        }
        $firma = (string)fread($archivo, 4);
        fclose($archivo);
        if ($firma !== '%PDF') {
            responderPedidoMaterialesJson(false, 'El archivo registrado no es un PDF valido.', [], [], 409);
        }

        $tamano = filesize($rutaAbsoluta);
        if ($tamano === false || $tamano <= 0) {
            responderPedidoMaterialesJson(false, 'El archivo PDF esta vacio.', [], [], 409);
        }

        header_remove('Content-Type');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Content-Length: ' . (int)$tamano);
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        readfile($rutaAbsoluta);
        exit;
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
    if ($accion === 'resolver_autorizacion_pedido_materiales') {
        if (!puedeAutorizarPedidoMateriales($usuario['perfil'])) {
            responderPedidoMaterialesJson(
                false,
                'No tenes permisos para autorizar pedidos de materiales.',
                [],
                [],
                403
            );
        }

        $idPrevisita = filter_var(
            $input['id_previsita'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $numeroPedido = filter_var(
            $input['numero_pedido'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 5]]
        );
        $idMaterial = filter_var(
            $input['id_material'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $ordenVisual = filter_var(
            $input['orden_visual'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $tipoFila = trim((string)($input['tipo_fila'] ?? ''));
        $decision = trim((string)($input['decision'] ?? ''));
        $tareaNroEntrada = $input['tarea_nro'] ?? null;
        $tareaNro = null;
        if ($tareaNroEntrada !== null && $tareaNroEntrada !== '') {
            $tareaNroValidada = filter_var(
                $tareaNroEntrada,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            if ($tareaNroValidada === false) {
                responderPedidoMaterialesJson(false, 'La tarea informada no es valida.', [], [], 422);
            }
            $tareaNro = (int)$tareaNroValidada;
        }

        if ($idPrevisita === false
            || $numeroPedido === false
            || $idMaterial === false
            || $ordenVisual === false
            || !in_array($tipoFila, ['presupuestado', 'agregado'], true)
            || !in_array($decision, ['autorizada', 'rechazada'], true)) {
            responderPedidoMaterialesJson(
                false,
                'Los datos de la autorizacion no son validos.',
                [],
                [],
                422
            );
        }

        validarPrevisitaPedidoMaterialesController($db, (int)$idPrevisita);
        $resultadoAutorizacion = resolverAutorizacionPedidoMaterialesEnConexion(
            $db,
            (int)$idPrevisita,
            (int)$numeroPedido,
            $tipoFila,
            (int)$idMaterial,
            $tareaNro,
            (int)$ordenVisual,
            $decision,
            isset($input['motivo']) ? (string)$input['motivo'] : null,
            (int)$usuario['id_usuario']
        );
        $dataAutorizacion = [
            'id_pedido_materiales_autorizacion' => (int)$resultadoAutorizacion['id_pedido_materiales_autorizacion'],
            'estado_autorizacion' => (string)$resultadoAutorizacion['estado_autorizacion'],
            'id_usuario_autorizacion' => (int)$resultadoAutorizacion['id_usuario_autorizacion'],
            'fecha_autorizacion' => (string)$resultadoAutorizacion['fecha_autorizacion'],
            'motivo_autorizacion' => $resultadoAutorizacion['motivo_autorizacion'],
        ];

        responderPedidoMaterialesJson(
            true,
            $decision === 'autorizada' ? 'Autorizacion aprobada.' : 'Autorizacion rechazada.',
            $dataAutorizacion,
            [],
            200,
            $dataAutorizacion
        );
    }

    if ($accion === 'listar_pdfs_pedido_materiales') {
        $idPrevisita = filter_var(
            $input['id_previsita'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($idPrevisita === false) {
            responderPedidoMaterialesJson(
                false,
                'El ID de Pedido de Materiales informado no es valido.',
                [],
                ['id_previsita' => 'Debe ser un entero mayor que cero.'],
                422
            );
        }

        $idPrevisita = (int)$idPrevisita;
        validarPrevisitaPedidoMaterialesController($db, $idPrevisita);
        $documentos = listarDocumentosPdfPedidoMaterialesPorPrevisita(
            $db,
            $idPrevisita
        );
        $dataListado = [
            'id_previsita' => $idPrevisita,
            'documentos' => $documentos,
        ];

        responderPedidoMaterialesJson(
            true,
            'Listado de PDFs de Pedido de Materiales obtenido correctamente.',
            $dataListado,
            [],
            200,
            ['documentos' => $documentos]
        );
    }

    if ($accion === 'generar_pdf_pedido') {
        if (!pedidoMaterialesPdfTablasMinimasDisponibles($db)) {
            responderPedidoMaterialesJson(
                false,
                'La persistencia PDF de Pedido de Materiales no esta disponible. Debe aplicarse la migracion 2026-07-29-B_pedido_materiales_pedido_documentos.sql.',
                [],
                [],
                409
            );
        }

        $idPedido = validarIdPedidoMaterialesController(
            $input['id_pedido_materiales_pedido'] ?? null
        );
        $resultadoPdf = generarPdfPedidoMaterialesConfirmado(
            $db,
            $idPedido,
            (int)$usuario['id_usuario']
        );
        $urlDescarga = '../03-controller/pedidoMaterialesController.php'
            . '?accion=descargar_pdf_pedido'
            . '&id_pedido_materiales_pedido=' . $idPedido;

        $dataPdf = [
            'id_pedido_materiales_pedido_documento' => (int)$resultadoPdf['id_pedido_materiales_pedido_documento'],
            'id_pedido_materiales_pedido' => (int)$resultadoPdf['id_pedido_materiales_pedido'],
            'nombre_archivo' => (string)$resultadoPdf['nombre_archivo'],
            'mime' => (string)$resultadoPdf['mime'],
            'tamano' => (int)$resultadoPdf['tamano'],
            'hash_archivo' => (string)$resultadoPdf['hash_archivo'],
            'fecha_generacion' => (string)$resultadoPdf['fecha_generacion'],
            'url_descarga' => $urlDescarga,
        ];

        responderPedidoMaterialesJson(
            true,
            'PDF generado correctamente.',
            $dataPdf,
            [],
            200,
            $dataPdf
        );
    }

    if ($accion === 'descargar_pdf_pedido') {
        $idPedido = validarIdPedidoMaterialesController(
            $input['id_pedido_materiales_pedido'] ?? null
        );
        descargarPdfPedidoMaterialesController($db, $idPedido);
    }

    if ($accion === 'enviar_correo_pedido') {
        if (!pedidoMaterialesEnviosTablasMinimasDisponibles($db)) {
            responderPedidoMaterialesJson(
                false,
                'La persistencia de envios de Pedido de Materiales no esta disponible. Debe aplicarse la migracion 2026-07-29-C_pedido_materiales_pedido_envios.sql.',
                [],
                [],
                409
            );
        }

        $idPedido = validarIdPedidoMaterialesController(
            $input['id_pedido_materiales_pedido'] ?? null
        );
        $resultadoEnvio = enviarCorreoPedidoMaterialesConfirmado(
            $db,
            $idPedido,
            (int)$usuario['id_usuario']
        );
        $dataEnvio = [
            'id_pedido_materiales_pedido_envio' => (int)$resultadoEnvio['id_pedido_materiales_pedido_envio'],
            'id_pedido_materiales_pedido_documento' => isset($resultadoEnvio['id_pedido_materiales_pedido_documento'])
                ? (int)$resultadoEnvio['id_pedido_materiales_pedido_documento']
                : null,
            'id_pedido_materiales_pedido' => (int)$resultadoEnvio['id_pedido_materiales_pedido'],
            'estado' => (string)$resultadoEnvio['estado'],
            'ya_enviado' => !empty($resultadoEnvio['ya_enviado']),
            'simulado' => !empty($resultadoEnvio['simulado']),
            'ya_simulado' => !empty($resultadoEnvio['ya_simulado']),
        ];

        responderPedidoMaterialesJson(
            true,
            (string)$resultadoEnvio['mensaje'],
            $dataEnvio,
            [],
            200,
            $dataEnvio
        );
    }

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
    $status = in_array((int)$e->getCode(), [400, 403, 404, 409, 422], true)
        ? (int)$e->getCode()
        : 500;
    responderPedidoMaterialesJson(false, $e->getMessage(), [], [], $status);
} finally {
    mysqli_close($db);
}
