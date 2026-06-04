<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../04-modelo/ordenCompraWorkflowModel.php';

if (!function_exists('leerEntradaOrdenCompraController')) {
    function leerEntradaOrdenCompraController(): array
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

if (!function_exists('responderOrdenCompraJson')) {
    function responderOrdenCompraJson(bool $success, string $message, array $data = [], array $errors = [], int $status = 200): void
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

if (!function_exists('responderOrdenCompraConfirmacionClienteJson')) {
    function responderOrdenCompraConfirmacionClienteJson(array $clienteSugerido): void
    {
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'requires_cliente_confirmation' => true,
            'message' => 'El cliente no existe en el maestro de Clientes.',
            'data' => [
                'cliente_sugerido' => $clienteSugerido,
            ],
            'errors' => [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('usuarioOrdenCompraController')) {
    function usuarioOrdenCompraController(): array
    {
        return [
            'id_usuario' => (int)($_SESSION['usuario']['id_usuario'] ?? 0),
            'perfil' => trim((string)($_SESSION['usuario']['perfil'] ?? '')),
            'email' => trim((string)($_SESSION['usuario']['email'] ?? '')),
        ];
    }
}

if (!function_exists('perfilPuedeConsultarOrdenCompraController')) {
    function perfilPuedeConsultarOrdenCompraController(?string $perfil): bool
    {
        return perfilPuedeEditarOrdenCompra($perfil)
            || perfilSoloPuedeVerOrdenCompra($perfil)
            || perfilPuedeVerSeguimientoCompletoOrdenCompra($perfil)
            || perfilPuedeAccederSoloOrdenCompra($perfil);
    }
}

if (!function_exists('validarSesionOrdenCompraController')) {
    function validarSesionOrdenCompraController(): array
    {
        $usuario = usuarioOrdenCompraController();
        if ($usuario['id_usuario'] <= 0 || $usuario['perfil'] === '') {
            responderOrdenCompraJson(false, 'No hay sesion de usuario activa.', [], [], 401);
        }

        if (!perfilPuedeConsultarOrdenCompraController($usuario['perfil'])) {
            responderOrdenCompraJson(false, 'El perfil no tiene permiso para acceder a Ordenes de compra.', [], [], 403);
        }

        return $usuario;
    }
}

if (!function_exists('abrirConexionOrdenCompraController')) {
    function abrirConexionOrdenCompraController(): mysqli
    {
        $db = conectDB();
        if (!$db) {
            responderOrdenCompraJson(false, 'No se pudo conectar a la base de datos.', [], [], 500);
        }

        mysqli_set_charset($db, 'utf8mb4');

        return $db;
    }
}

if (!function_exists('validarPresupuestoOrdenCompraController')) {
    function validarPresupuestoOrdenCompraController(mysqli $db, array $input, bool $requierePrevisita = false): array
    {
        $idPresupuesto = isset($input['id_presupuesto']) ? (int)$input['id_presupuesto'] : 0;
        $idPrevisita = isset($input['id_previsita']) ? (int)$input['id_previsita'] : 0;

        if ($idPresupuesto <= 0) {
            responderOrdenCompraJson(false, 'El presupuesto es obligatorio.', [], ['id_presupuesto' => 'Dato obligatorio.'], 422);
        }

        if ($requierePrevisita && $idPrevisita <= 0) {
            responderOrdenCompraJson(false, 'La previsita es obligatoria.', [], ['id_previsita' => 'Dato obligatorio.'], 422);
        }

        $presupuesto = obtenerPresupuestoBasicoParaOrdenCompra($db, $idPresupuesto);
        if (!$presupuesto) {
            responderOrdenCompraJson(false, 'No se encontro el presupuesto informado.', [], [], 404);
        }

        if ($idPrevisita > 0 && (int)$presupuesto['id_previsita'] !== $idPrevisita) {
            responderOrdenCompraJson(false, 'La previsita no corresponde al presupuesto informado.', [], ['id_previsita' => 'No coincide con el presupuesto.'], 422);
        }

        return $presupuesto;
    }
}

if (!function_exists('payloadOrdenCompraController')) {
    function payloadOrdenCompraController(mysqli $db, array $presupuesto, string $perfil): array
    {
        $ordenCompra = obtenerOrdenCompraActivaPorPresupuestoEnConexion($db, (int)$presupuesto['id_presupuesto']);
        if ($ordenCompra) {
            $ordenCompra['intervino_resumen'] = construirResumenIntervencionesOrdenCompraEnConexion($db, $ordenCompra);
        }

        $estadoCalculado = resolverEstadoOrdenCompraCalculado(
            (string)($presupuesto['estado_comercial_activo'] ?? ''),
            $ordenCompra
        );

        $mensaje = '';
        if ($estadoCalculado['estado'] === 'pendiente') {
            $mensaje = 'OC pendiente de carga administrativa.';
        } elseif ($estadoCalculado['estado'] === 'no_habilitada') {
            $mensaje = 'La OC solo puede cargarse cuando el presupuesto esta Aprobado.';
        }

        return [
            'id_presupuesto' => (int)$presupuesto['id_presupuesto'],
            'id_previsita' => (int)$presupuesto['id_previsita'],
            'estado_comercial' => (string)($presupuesto['estado_comercial_activo'] ?? ''),
            'estado_calculado' => $estadoCalculado['estado'],
            'label_estado' => $estadoCalculado['estado_label'],
            'badge_class' => $estadoCalculado['badge_class'],
            'puede_editar' => perfilPuedeEditarOrdenCompra($perfil)
                && !empty($estadoCalculado['habilitada']),
            'orden_compra' => $ordenCompra,
            'mensaje' => $mensaje,
        ];
    }
}

if (!function_exists('validarAccesoLecturaOrdenCompraController')) {
    function validarAccesoLecturaOrdenCompraController(string $perfil, array $estado): void
    {
        if (!perfilPuedeAccederSeguimientoOrdenCompra($perfil, [
            'habilitada' => $estado['estado_calculado'] !== 'no_habilitada',
        ])) {
            responderOrdenCompraJson(false, 'El perfil no tiene acceso a esta Orden de compra.', [], [], 403);
        }
    }
}

if (!function_exists('asegurarTablaOrdenCompraDisponibleController')) {
    function asegurarTablaOrdenCompraDisponibleController(mysqli $db): void
    {
        if (!ordenCompraTablaTieneColumnasMinimas($db)) {
            responderOrdenCompraJson(false, 'La tabla ordenes_compra no esta disponible. Debe aplicarse la migracion del Paso 2.', [], [], 409);
        }
    }
}

if (!function_exists('prepararDatosOrdenCompraController')) {
    function prepararDatosOrdenCompraController(array $input, array $presupuesto, int $idUsuario, ?array $base = null): array
    {
        $datosBase = $base ?: [];
        $datos = normalizarDatosOrdenCompra(array_merge($datosBase, $input));
        $datos['id_presupuesto'] = (int)$presupuesto['id_presupuesto'];
        $datos['id_previsita'] = (int)$presupuesto['id_previsita'];
        $datos['cliente_snapshot'] = $datos['cliente_snapshot'] ?: ($presupuesto['cliente_snapshot'] ?? null);
        $datos['id_usuario_modificacion'] = $idUsuario;

        if ($base === null) {
            $datos['estado'] = 'cargada';
            $datos['id_usuario_alta'] = $idUsuario;
        }

        return $datos;
    }
}

if (!function_exists('archivoPdfOrdenCompraController')) {
    function archivoPdfOrdenCompraController(): ?array
    {
        if (!isset($_FILES['pdf_oc']) || !is_array($_FILES['pdf_oc'])) {
            return null;
        }

        $archivo = $_FILES['pdf_oc'];
        if ((int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $archivo;
    }
}

if (!function_exists('adjuntarPdfOrdenCompraController')) {
    function adjuntarPdfOrdenCompraController(array $datos, ?array $archivoPdf): array
    {
        if ($archivoPdf === null) {
            return $datos;
        }

        $pdfGuardado = guardarArchivoPdfOrdenCompra(
            (int)$datos['id_presupuesto'],
            $archivoPdf,
            (string)($datos['numero_oc'] ?? '')
        );

        $datos['pdf_nombre_archivo'] = $pdfGuardado['pdf_nombre_archivo'];
        $datos['pdf_ruta_archivo'] = $pdfGuardado['pdf_ruta_archivo'];
        $datos['pdf_mime_type'] = $pdfGuardado['pdf_mime_type'];
        $datos['pdf_tamano_bytes'] = $pdfGuardado['pdf_tamano_bytes'];
        $datos['_pdf_ruta_absoluta_nueva'] = $pdfGuardado['ruta_absoluta'];

        return $datos;
    }
}

if (!function_exists('flagOrdenCompraController')) {
    function flagOrdenCompraController(array $input, string $nombre): bool
    {
        $valor = $input[$nombre] ?? null;
        return $valor === true || $valor === 1 || $valor === '1' || $valor === 'true' || $valor === 'on';
    }
}

if (!function_exists('evaluarAltaClienteDesdeOrdenCompraController')) {
    function evaluarAltaClienteDesdeOrdenCompraController(mysqli $db, array $input, array $presupuesto, array $datosOrdenCompra, array $usuario): array
    {
        $datosCliente = prepararDatosClienteDesdeOrdenCompra(
            $presupuesto,
            array_merge($datosOrdenCompra, $input),
            (int)$usuario['id_usuario']
        );
        $erroresCliente = validarDatosClienteDesdeOrdenCompra($datosCliente);

        if (!empty($erroresCliente)) {
            return [
                'accion' => 'omitida',
                'motivo' => 'datos_insuficientes',
                'errores' => $erroresCliente,
                'cliente_sugerido' => [
                    'cuit' => (string)($presupuesto['cuit'] ?? ''),
                    'razon_social' => (string)($presupuesto['razon_social'] ?? ''),
                ],
            ];
        }

        $clienteExistente = obtenerClientePorCuitNormalizadoEnConexion($db, $datosCliente['cuit']);
        if ($clienteExistente) {
            return [
                'accion' => 'existente',
                'cliente' => $clienteExistente,
            ];
        }

        if (flagOrdenCompraController($input, 'guardar_sin_alta_cliente')) {
            return [
                'accion' => 'omitida',
                'motivo' => 'rechazada_por_usuario',
                'cliente_sugerido' => [
                    'cuit' => $datosCliente['cuit'],
                    'razon_social' => $datosCliente['razon_social'],
                ],
            ];
        }

        if (!flagOrdenCompraController($input, 'confirmar_alta_cliente')) {
            responderOrdenCompraConfirmacionClienteJson([
                'cuit' => $datosCliente['cuit'],
                'razon_social' => $datosCliente['razon_social'],
            ]);
        }

        return [
            'accion' => 'crear',
            'datos_cliente' => $datosCliente,
        ];
    }
}

if (!function_exists('responderPdfOrdenCompraController')) {
    function responderPdfOrdenCompraController(array $ordenCompra): void
    {
        if (!ordenCompraTienePdf($ordenCompra)) {
            responderOrdenCompraJson(false, 'La OC no tiene PDF cargado.', [], [], 404);
        }

        $rutaAbsoluta = rutaAbsolutaOrdenCompraPdfDesdeRelativa((string)$ordenCompra['pdf_ruta_archivo']);
        if (
            $rutaAbsoluta === ''
            || !file_exists($rutaAbsoluta)
            || !rutaOrdenCompraPdfDentroDeBase($rutaAbsoluta, (int)$ordenCompra['id_presupuesto'])
        ) {
            responderOrdenCompraJson(false, 'El PDF de la OC no esta disponible en el servidor.', [], [], 404);
        }

        $nombre = trim((string)($ordenCompra['pdf_nombre_archivo'] ?? 'orden-compra.pdf'));
        if ($nombre === '') {
            $nombre = 'orden-compra.pdf';
        }
        if (!preg_match('/\.pdf$/i', $nombre)) {
            $nombre .= '.pdf';
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . str_replace('"', '', $nombre) . '"');
        header('Content-Length: ' . filesize($rutaAbsoluta));
        header('X-Content-Type-Options: nosniff');
        readfile($rutaAbsoluta);
        exit;
    }
}

$input = leerEntradaOrdenCompraController();
$accion = (string)($input['accion'] ?? $input['funcion'] ?? '');
$usuario = validarSesionOrdenCompraController();
$db = abrirConexionOrdenCompraController();

try {
    switch ($accion) {
        case 'obtener_orden_compra':
            $presupuesto = validarPresupuestoOrdenCompraController($db, $input, false);
            $data = payloadOrdenCompraController($db, $presupuesto, $usuario['perfil']);
            validarAccesoLecturaOrdenCompraController($usuario['perfil'], $data);

            responderOrdenCompraJson(true, 'Orden de compra obtenida.', $data);
            break;

        case 'descargar_pdf_orden_compra':
            $idOrdenCompra = isset($input['id_orden_compra']) ? (int)$input['id_orden_compra'] : 0;
            if ($idOrdenCompra <= 0) {
                responderOrdenCompraJson(false, 'La Orden de compra es obligatoria.', [], ['id_orden_compra' => 'Dato obligatorio.'], 422);
            }

            $ordenCompra = obtenerOrdenCompraPorIdEnConexion($db, $idOrdenCompra);
            if (!$ordenCompra) {
                responderOrdenCompraJson(false, 'No se encontro la Orden de compra.', [], [], 404);
            }

            $presupuesto = obtenerPresupuestoBasicoParaOrdenCompra($db, (int)$ordenCompra['id_presupuesto']);
            if (!$presupuesto) {
                responderOrdenCompraJson(false, 'No se encontro el presupuesto asociado a la OC.', [], [], 404);
            }

            $data = payloadOrdenCompraController($db, $presupuesto, $usuario['perfil']);
            validarAccesoLecturaOrdenCompraController($usuario['perfil'], $data);
            responderPdfOrdenCompraController($ordenCompra);
            break;

        case 'guardar_orden_compra':
            asegurarTablaOrdenCompraDisponibleController($db);
            if (!perfilPuedeEditarOrdenCompra($usuario['perfil'])) {
                responderOrdenCompraJson(false, 'El perfil no tiene permiso para crear Ordenes de compra.', [], [], 403);
            }

            $presupuesto = validarPresupuestoOrdenCompraController($db, $input, true);
            if (!estadoComercialHabilitaOrdenCompra($presupuesto['estado_comercial_activo'] ?? '')) {
                responderOrdenCompraJson(false, 'La OC solo puede cargarse cuando el presupuesto esta Aprobado.', [], [], 422);
            }

            if (existeOrdenCompraActivaPorPresupuestoEnConexion($db, (int)$presupuesto['id_presupuesto'])) {
                responderOrdenCompraJson(false, 'Ya existe una OC activa para este presupuesto. Debe actualizarse la existente.', [], [], 409);
            }

            $datos = prepararDatosOrdenCompraController($input, $presupuesto, $usuario['id_usuario']);
            $errores = validarDatosOrdenCompra($datos, true, ordenCompraTablaTieneColumnasAdicionales($db));
            if (!empty($errores)) {
                responderOrdenCompraJson(false, 'Hay datos de OC invalidos.', [], $errores, 422);
            }

            $archivoPdf = archivoPdfOrdenCompraController();
            if ($archivoPdf === null) {
                responderOrdenCompraJson(false, 'El PDF de la OC es obligatorio.', [], ['pdf_oc' => 'Debe adjuntar el PDF respaldatorio de la OC.'], 422);
            }

            try {
                validarArchivoPdfOrdenCompra($archivoPdf);
            } catch (RuntimeException $e) {
                responderOrdenCompraJson(false, 'El PDF de la OC no es valido.', [], ['pdf_oc' => $e->getMessage()], 422);
            }

            $altaCliente = evaluarAltaClienteDesdeOrdenCompraController($db, $input, $presupuesto, $datos, $usuario);

            try {
                $datos = adjuntarPdfOrdenCompraController($datos, $archivoPdf);
            } catch (RuntimeException $e) {
                responderOrdenCompraJson(false, 'El PDF de la OC no es valido.', [], ['pdf_oc' => $e->getMessage()], 422);
            }

            mysqli_begin_transaction($db);
            $clienteAltaPayload = $altaCliente;
            if (($altaCliente['accion'] ?? '') === 'crear') {
                $resultadoAltaCliente = crearClienteDesdeOrdenCompraEnConexion($db, $altaCliente['datos_cliente']);
                if (empty($resultadoAltaCliente['creado']) && empty($resultadoAltaCliente['existente'])) {
                    mysqli_rollback($db);
                    if (!empty($datos['_pdf_ruta_absoluta_nueva']) && file_exists($datos['_pdf_ruta_absoluta_nueva'])) {
                        @unlink($datos['_pdf_ruta_absoluta_nueva']);
                    }
                    responderOrdenCompraJson(false, $resultadoAltaCliente['error'] ?? 'No se pudo crear el cliente desde la OC.', [], ['cliente' => 'No se pudo crear el cliente automaticamente.'], 500);
                }

                $clienteAltaPayload = [
                    'accion' => !empty($resultadoAltaCliente['creado']) ? 'creado' : 'existente',
                    'cliente' => $resultadoAltaCliente['cliente'] ?? null,
                ];
            }

            $idOrdenCompra = crearOrdenCompraEnConexion($db, $datos);
            if (!$idOrdenCompra) {
                mysqli_rollback($db);
                if (!empty($datos['_pdf_ruta_absoluta_nueva']) && file_exists($datos['_pdf_ruta_absoluta_nueva'])) {
                    @unlink($datos['_pdf_ruta_absoluta_nueva']);
                }
                responderOrdenCompraJson(false, 'No se pudo guardar la Orden de compra.', [], [], 500);
            }

            mysqli_commit($db);
            $ordenCompra = obtenerOrdenCompraPorIdEnConexion($db, (int)$idOrdenCompra);
            responderOrdenCompraJson(true, 'Orden de compra creada.', [
                'id_orden_compra' => (int)$idOrdenCompra,
                'estado' => $ordenCompra['estado'] ?? 'cargada',
                'numero_oc' => $ordenCompra['numero_oc'] ?? $datos['numero_oc'],
                'orden_compra' => $ordenCompra,
                'cliente_alta' => $clienteAltaPayload,
            ]);
            break;

        case 'actualizar_orden_compra':
            asegurarTablaOrdenCompraDisponibleController($db);
            if (!perfilPuedeEditarOrdenCompra($usuario['perfil'])) {
                responderOrdenCompraJson(false, 'El perfil no tiene permiso para actualizar Ordenes de compra.', [], [], 403);
            }

            $idOrdenCompra = isset($input['id_orden_compra']) ? (int)$input['id_orden_compra'] : 0;
            if ($idOrdenCompra <= 0) {
                responderOrdenCompraJson(false, 'La Orden de compra es obligatoria.', [], ['id_orden_compra' => 'Dato obligatorio.'], 422);
            }

            $ordenCompraActual = obtenerOrdenCompraPorIdEnConexion($db, $idOrdenCompra);
            if (!$ordenCompraActual) {
                responderOrdenCompraJson(false, 'No se encontro la Orden de compra.', [], [], 404);
            }

            if (!esEstadoOrdenCompraActiva($ordenCompraActual['estado'] ?? '')) {
                responderOrdenCompraJson(false, 'Solo se puede actualizar una OC activa.', [], [], 409);
            }

            $input['id_presupuesto'] = $input['id_presupuesto'] ?? $ordenCompraActual['id_presupuesto'];
            $input['id_previsita'] = $input['id_previsita'] ?? $ordenCompraActual['id_previsita'];
            $presupuesto = validarPresupuestoOrdenCompraController($db, $input, true);
            if ((int)$ordenCompraActual['id_presupuesto'] !== (int)$presupuesto['id_presupuesto']) {
                responderOrdenCompraJson(false, 'La OC no corresponde al presupuesto informado.', [], [], 422);
            }

            $datos = prepararDatosOrdenCompraController($input, $presupuesto, $usuario['id_usuario'], $ordenCompraActual);
            $datos['estado'] = $ordenCompraActual['estado'];
            $errores = validarDatosOrdenCompra($datos, false, ordenCompraTablaTieneColumnasAdicionales($db));
            if (!empty($errores)) {
                responderOrdenCompraJson(false, 'Hay datos de OC invalidos.', [], $errores, 422);
            }

            $archivoPdf = archivoPdfOrdenCompraController();
            if ($archivoPdf === null && !ordenCompraTienePdf($ordenCompraActual)) {
                responderOrdenCompraJson(false, 'El PDF de la OC es obligatorio.', [], ['pdf_oc' => 'Debe adjuntar el PDF respaldatorio de la OC.'], 422);
            }

            try {
                $datos = adjuntarPdfOrdenCompraController($datos, $archivoPdf);
            } catch (RuntimeException $e) {
                responderOrdenCompraJson(false, 'El PDF de la OC no es valido.', [], ['pdf_oc' => $e->getMessage()], 422);
            }

            if (!actualizarOrdenCompraEnConexion($db, $idOrdenCompra, $datos)) {
                if (!empty($datos['_pdf_ruta_absoluta_nueva']) && file_exists($datos['_pdf_ruta_absoluta_nueva'])) {
                    @unlink($datos['_pdf_ruta_absoluta_nueva']);
                }
                responderOrdenCompraJson(false, 'No se pudo actualizar la Orden de compra.', [], [], 500);
            }

            if (!empty($datos['_pdf_ruta_absoluta_nueva'])) {
                eliminarArchivoPdfOrdenCompraSiCorresponde($ordenCompraActual);
            }

            responderOrdenCompraJson(true, 'Orden de compra actualizada.', [
                'orden_compra' => obtenerOrdenCompraPorIdEnConexion($db, $idOrdenCompra),
            ]);
            break;

        case 'cambiar_estado_orden_compra':
            asegurarTablaOrdenCompraDisponibleController($db);
            if (!perfilPuedeEditarOrdenCompra($usuario['perfil'])) {
                responderOrdenCompraJson(false, 'El perfil no tiene permiso para cambiar el estado de la OC.', [], [], 403);
            }

            $idOrdenCompra = isset($input['id_orden_compra']) ? (int)$input['id_orden_compra'] : 0;
            $estado = strtolower(trim((string)($input['estado'] ?? '')));
            if ($idOrdenCompra <= 0) {
                responderOrdenCompraJson(false, 'La Orden de compra es obligatoria.', [], ['id_orden_compra' => 'Dato obligatorio.'], 422);
            }
            if (!validarEstadoOrdenCompraPersistible($estado)) {
                responderOrdenCompraJson(false, 'El estado solicitado no es valido para una OC.', [], ['estado' => 'Solo se permite cargada, observada o anulada.'], 422);
            }

            $ordenCompraActual = obtenerOrdenCompraPorIdEnConexion($db, $idOrdenCompra);
            if (!$ordenCompraActual) {
                responderOrdenCompraJson(false, 'No se encontro la Orden de compra.', [], [], 404);
            }

            if (esEstadoOrdenCompraActiva($estado) && existeOtraOrdenCompraActivaPorPresupuestoEnConexion($db, (int)$ordenCompraActual['id_presupuesto'], $idOrdenCompra)) {
                responderOrdenCompraJson(false, 'Ya existe otra OC activa para este presupuesto.', [], [], 409);
            }

            if (!cambiarEstadoOrdenCompraEnConexion($db, $idOrdenCompra, $estado, $usuario['id_usuario'])) {
                responderOrdenCompraJson(false, 'No se pudo cambiar el estado de la OC.', [], [], 500);
            }

            $presupuesto = obtenerPresupuestoBasicoParaOrdenCompra($db, (int)$ordenCompraActual['id_presupuesto']);
            $payload = $presupuesto
                ? payloadOrdenCompraController($db, $presupuesto, $usuario['perfil'])
                : ['estado' => $estado];

            responderOrdenCompraJson(true, 'Estado de OC actualizado.', $payload);
            break;

        default:
            responderOrdenCompraJson(false, 'La accion solicitada no es valida.', [], ['accion' => 'Accion no reconocida.'], 400);
            break;
    }
} catch (Throwable $e) {
    responderOrdenCompraJson(false, 'Ocurrio un error al procesar la Orden de compra.', [], [], 500);
} finally {
    if ($db instanceof mysqli) {
        mysqli_close($db);
    }
}
