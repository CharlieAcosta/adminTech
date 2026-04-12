<?php
// Evita rutas relativas fr�giles tipo "03-controller../..."
$BASE = __DIR__;

// ../03-controller/presupuestos_guardar.php
header('Content-Type: application/json; charset=utf-8');

require_once $BASE . '/../04-modelo/presupuestoGeneradoModel.php';
require_once $BASE . '/../04-modelo/presupuestoDocumentosEmitidosModel.php';
require_once $BASE . '/../04-modelo/presupuestoDocumentosEmitidosEnviosModel.php';
require_once $BASE . '/../04-modelo/presupuestoIntervencionesModel.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('obtenerIdUsuarioSolicitudPresupuesto')) {
    function obtenerIdUsuarioSolicitudPresupuesto(): int
    {
        $idSesion = isset($_SESSION['usuario']['id_usuario']) ? (int)$_SESSION['usuario']['id_usuario'] : 0;
        if ($idSesion > 0) {
            return $idSesion;
        }

        $idPost = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;
        if ($idPost > 0) {
            return $idPost;
        }

        return 0;
    }
}

if (!function_exists('obtenerPerfilUsuarioSolicitudPresupuesto')) {
    function obtenerPerfilUsuarioSolicitudPresupuesto(): string
    {
        return trim((string)($_SESSION['usuario']['perfil'] ?? ''));
    }
}

if (!function_exists('enriquecerRespuestaHistorialComercialPresupuestoParaUsuario')) {
    function enriquecerRespuestaHistorialComercialPresupuestoParaUsuario(array $respuesta, ?int $idUsuario = null, ?string $perfil = null): array
    {
        $idUsuario = $idUsuario ?? obtenerIdUsuarioSolicitudPresupuesto();
        $perfil = $perfil !== null ? $perfil : obtenerPerfilUsuarioSolicitudPresupuesto();

        return agregarAccionReaperturaDisponibleHistorialComercialPresupuesto(
            $respuesta,
            (int)$idUsuario,
            $perfil
        );
    }
}

try {
    // ---- Validaciones b�sicas
    if (($_POST['via'] ?? '') !== 'ajax') {
        throw new RuntimeException('Acceso inv�lido');
    }

    $funcion = $_POST['funcion'] ?? '';

    switch ($funcion) {
        case 'listar_tareas_archivadas':
            // === NUEVO: listado de plantillas ===
            require_once $BASE . '/../04-modelo/tareasArchivadasListarModel.php';
            $q    = isset($_POST['q']) ? (string)$_POST['q'] : '';
            $page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
            $per  = isset($_POST['per_page']) ? max(1, min(100, (int)$_POST['per_page'])) : 20;

            try {
                $out = listarTareasArchivadas($q, $page, $per);
                echo json_encode($out, JSON_UNESCAPED_UNICODE);
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;

        case 'guardar_tarea_archivada':
            // --- Rama nueva: guardar tarea como plantilla ---
            $raw = $_POST['data_json'] ?? '';
            if ($raw === '') {
                echo json_encode(['ok' => false, 'msg' => 'Falta data_json'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                echo json_encode(['ok' => false, 'msg' => 'JSON inv�lido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
    
            require_once $BASE . '/../04-modelo/tareasArchivadasGuardarModel.php';
            try {
                $res = guardarTareaArchivada($data);
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['ok' => false, 'msg' => 'Excepci�n', 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;

            case 'obtener_tarea_archivada':
                require_once $BASE . '/../04-modelo/tareasArchivadasObtenerModel.php';
                $id = isset($_POST['id_arch_tarea']) ? (int)$_POST['id_arch_tarea'] : 0;
                if ($id <= 0) {
                    echo json_encode(['ok' => false, 'msg' => 'id_arch_tarea inv�lido'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                try {
                    $out = obtenerTareaArchivada($id);
                    echo json_encode($out, JSON_UNESCAPED_UNICODE);
                } catch (Throwable $e) {
                    http_response_code(500);
                    echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                }
                exit;
                       
            case 'guardarPresupuesto':
                // >>>> de aqu� para abajo sigue tu flujo existente de guardar presupuesto <<<<
                break;
        
            case 'registrarIntervencionPresupuesto':
                $idPresupuesto = isset($_POST['id_presupuesto']) ? (int)$_POST['id_presupuesto'] : 0;
                $idPrevisita = isset($_POST['id_previsita']) ? (int)$_POST['id_previsita'] : 0;
                $accionIntervencion = isset($_POST['accion_intervencion']) ? (string)$_POST['accion_intervencion'] : '';
                $idUsuario = obtenerIdUsuarioSolicitudPresupuesto();

                if ($idUsuario <= 0) {
                    echo json_encode(['ok' => false, 'msg' => 'No hay sesi�n de usuario activa.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                if ($idUsuario <= 0) {
                    echo json_encode(['ok' => false, 'msg' => 'No hay sesi�n de usuario activa.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                echo json_encode(
                    registrarIntervencionPresupuesto($idPresupuesto, $idPrevisita, $idUsuario, $accionIntervencion),
                    JSON_UNESCAPED_UNICODE
                );
                exit;

            case 'emitirDocumentoPresupuesto':
                $idPresupuesto = isset($_POST['id_presupuesto']) ? (int)$_POST['id_presupuesto'] : 0;
                $idPrevisita = isset($_POST['id_previsita']) ? (int)$_POST['id_previsita'] : 0;
                $nombreArchivo = isset($_POST['nombre_archivo']) ? trim((string)$_POST['nombre_archivo']) : '';
                $idUsuario = obtenerIdUsuarioSolicitudPresupuesto();
                $archivoPdf = $_FILES['documento_pdf'] ?? null;

                if ($idUsuario <= 0) {
                    echo json_encode(['ok' => false, 'msg' => 'No hay sesi�n de usuario activa.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                if ($idPresupuesto <= 0 || $idPrevisita <= 0) {
                    echo json_encode(['ok' => false, 'msg' => 'Faltan datos para emitir el documento.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                if (!is_array($archivoPdf)) {
                    echo json_encode(['ok' => false, 'msg' => 'No se recibi� el archivo PDF emitido.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $resultadoEmision = emitirDocumentoPresupuesto(
                    $idPresupuesto,
                    $idPrevisita,
                    $idUsuario,
                    $archivoPdf,
                    $nombreArchivo
                );

                if (!empty($resultadoEmision['ok'])) {
                    $registroIntervencion = registrarIntervencionPresupuesto(
                        $idPresupuesto,
                        $idPrevisita,
                        $idUsuario,
                        'emitir'
                    );

                    if (!empty($registroIntervencion['ok']) && isset($registroIntervencion['intervino'])) {
                        $resultadoEmision['intervino'] = $registroIntervencion['intervino'];
                    }

                    $rutaArchivo = ltrim((string)($resultadoEmision['ruta_archivo'] ?? ''), '/');
                    $resultadoEmision['url_publica'] = $rutaArchivo !== ''
                        ? ('../' . $rutaArchivo)
                        : '';
                }

                echo json_encode($resultadoEmision, JSON_UNESCAPED_UNICODE);
                exit;

            case 'listarDocumentosEmitidosPresupuesto':
                $idPrevisita = isset($_POST['id_previsita']) ? (int)$_POST['id_previsita'] : 0;
                $idPresupuesto = isset($_POST['id_presupuesto']) ? (int)$_POST['id_presupuesto'] : 0;

                if ($idPrevisita <= 0) {
                    echo json_encode(['ok' => false, 'msg' => 'Pre-visita inv�lida.', 'items' => []], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $documentos = listarDocumentosEmitidosPresupuesto(
                    $idPrevisita,
                    $idPresupuesto > 0 ? $idPresupuesto : null
                );

                foreach ($documentos as &$documento) {
                    $rutaArchivo = ltrim((string)($documento['ruta_archivo'] ?? ''), '/');
                    $archivoDisponible = !empty($documento['archivo_disponible']);
                    $documento['url_publica'] = ($archivoDisponible && $rutaArchivo !== '')
                        ? ('../' . $rutaArchivo)
                        : '';
                }
                unset($documento);

                echo json_encode([
                    'ok' => true,
                    'items' => $documentos,
                    'total' => count($documentos),
                ], JSON_UNESCAPED_UNICODE);
                exit;

            case 'obtenerContextoEnvioDocumentoEmitidoPresupuesto':
                $idDocumentoEmitido = isset($_POST['id_documento_emitido']) ? (int)$_POST['id_documento_emitido'] : 0;
                echo json_encode(
                    obtenerContextoEnvioDocumentoEmitidoPresupuesto($idDocumentoEmitido),
                    JSON_UNESCAPED_UNICODE
                );
                exit;

            case 'enviarDocumentoEmitidoPresupuesto':
                $idUsuario = obtenerIdUsuarioSolicitudPresupuesto();
                if ($idUsuario <= 0) {
                    echo json_encode(['ok' => false, 'msg' => 'No hay sesión de usuario activa.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                echo json_encode(
                    procesarEnvioDocumentoEmitidoPresupuestoModoActivo($_POST, $idUsuario),
                    JSON_UNESCAPED_UNICODE
                );
                exit;

            case 'obtenerHistorialComercialPresupuesto':
                $idPrevisita = isset($_POST['id_previsita']) ? (int)$_POST['id_previsita'] : 0;
                $idPresupuesto = isset($_POST['id_presupuesto']) ? (int)$_POST['id_presupuesto'] : 0;

                echo json_encode(
                    enriquecerRespuestaHistorialComercialPresupuestoParaUsuario(
                        obtenerHistorialComercialPresupuesto(
                            $idPrevisita,
                            $idPresupuesto > 0 ? $idPresupuesto : null
                        )
                    ),
                    JSON_UNESCAPED_UNICODE
                );
                exit;

            case 'registrarEstadoComercialPresupuesto':
                $idUsuario = obtenerIdUsuarioSolicitudPresupuesto();
                if ($idUsuario <= 0) {
                    echo json_encode(['ok' => false, 'msg' => 'No hay sesion de usuario activa.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $idPrevisita = isset($_POST['id_previsita']) ? (int)$_POST['id_previsita'] : 0;
                $idPresupuesto = isset($_POST['id_presupuesto']) ? (int)$_POST['id_presupuesto'] : 0;
                $accionComercial = isset($_POST['accion_comercial']) ? (string)$_POST['accion_comercial'] : '';
                $comentarios = isset($_POST['comentarios']) ? (string)$_POST['comentarios'] : '';

                echo json_encode(
                    enriquecerRespuestaHistorialComercialPresupuestoParaUsuario(
                        registrarEstadoComercialPresupuesto(
                            $idPrevisita,
                            $idUsuario,
                            $accionComercial,
                            $idPresupuesto > 0 ? $idPresupuesto : null,
                            $comentarios
                        ),
                        $idUsuario,
                        obtenerPerfilUsuarioSolicitudPresupuesto()
                    ),
                    JSON_UNESCAPED_UNICODE
                );
                exit;

            case 'registrarContactoComercialPresupuesto':
                $idUsuario = obtenerIdUsuarioSolicitudPresupuesto();
                if ($idUsuario <= 0) {
                    echo json_encode(['ok' => false, 'msg' => 'No hay sesion de usuario activa.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $idPrevisita = isset($_POST['id_previsita']) ? (int)$_POST['id_previsita'] : 0;
                $idPresupuesto = isset($_POST['id_presupuesto']) ? (int)$_POST['id_presupuesto'] : 0;
                $accionContacto = isset($_POST['accion_comercial']) ? (string)$_POST['accion_comercial'] : '';
                $comentarios = isset($_POST['comentarios']) ? (string)$_POST['comentarios'] : '';

                echo json_encode(
                    enriquecerRespuestaHistorialComercialPresupuestoParaUsuario(
                        registrarContactoComercialPresupuesto(
                            $idPrevisita,
                            $idUsuario,
                            $accionContacto,
                            $idPresupuesto > 0 ? $idPresupuesto : null,
                            $comentarios
                        ),
                        $idUsuario,
                        obtenerPerfilUsuarioSolicitudPresupuesto()
                    ),
                    JSON_UNESCAPED_UNICODE
                );
                exit;

            case 'registrarReaperturaComercialPresupuesto':
                $idUsuario = obtenerIdUsuarioSolicitudPresupuesto();
                if ($idUsuario <= 0) {
                    echo json_encode(['ok' => false, 'msg' => 'No hay sesion de usuario activa.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $idPrevisita = isset($_POST['id_previsita']) ? (int)$_POST['id_previsita'] : 0;
                $idPresupuesto = isset($_POST['id_presupuesto']) ? (int)$_POST['id_presupuesto'] : 0;
                $comentarios = isset($_POST['comentarios']) ? (string)$_POST['comentarios'] : '';

                echo json_encode(
                    enriquecerRespuestaHistorialComercialPresupuestoParaUsuario(
                        registrarReaperturaComercialPresupuesto(
                            $idPrevisita,
                            $idUsuario,
                            $idPresupuesto > 0 ? $idPresupuesto : null,
                            $comentarios
                        ),
                        $idUsuario,
                        obtenerPerfilUsuarioSolicitudPresupuesto()
                    ),
                    JSON_UNESCAPED_UNICODE
                );
                exit;

            case 'listarIntervencionesPresupuesto':
                $idPrevisita = isset($_POST['id_previsita']) ? (int)$_POST['id_previsita'] : 0;
                $idPresupuesto = isset($_POST['id_presupuesto']) ? (int)$_POST['id_presupuesto'] : 0;

                if ($idPrevisita <= 0) {
                    echo json_encode(['ok' => false, 'msg' => 'Pre-visita inválida.', 'items' => []], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $items = obtenerIntervencionesPresupuesto(
                    $idPrevisita,
                    $idPresupuesto > 0 ? $idPresupuesto : null
                );

                echo json_encode([
                    'ok' => true,
                    'items' => $items,
                    'total' => count($items),
                ], JSON_UNESCAPED_UNICODE);
                exit;

            default:
                throw new RuntimeException('Funcion no soportada: ' . $funcion);
    }
    

    // ---- Payload JSON
    $payloadJson = $_POST['payload'] ?? '';
    if ($payloadJson === '') {
        throw new RuntimeException('Payload vac�o');
    }

    $payload = json_decode($payloadJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Payload JSON inv�lido: ' . json_last_error_msg());
    }

    // --------------------------------------------------------------------
    // 1) Normalizar archivos por tarea: $_FILES['fotos_tarea_{N}'][] ...
    // --------------------------------------------------------------------
    // Resultado esperado:
    // $archivosPorTarea = [
    //   <nroTarea:int> => [
    //      [ 'name'=>string, 'type'=>string, 'tmp_name'=>string, 'error'=>int, 'size'=>int ],
    //      ...
    //   ],
    //   ...
    // ];
    $archivosPorTarea = [];

    foreach ($_FILES as $key => $fileBag) {
        // Matchea fotos_tarea_12  (con o sin [] lo maneja PHP internamente)
        if (preg_match('/^fotos_tarea_(\d+)$/', $key, $m)) {
            $nro = (int)$m[1];

            // Puede venir agrupado en arrays paralelos (name[], type[], tmp_name[]...)
            if (isset($fileBag['name']) && is_array($fileBag['name'])) {
                $count = count($fileBag['name']);
                for ($i = 0; $i < $count; $i++) {
                    if (
                        !isset($fileBag['tmp_name'][$i]) ||
                        !isset($fileBag['error'][$i]) ||
                        $fileBag['error'][$i] !== UPLOAD_ERR_OK
                    ) {
                        continue;
                    }
                    $archivosPorTarea[$nro][] = [
                        'name'     => (string)$fileBag['name'][$i],
                        'type'     => (string)($fileBag['type'][$i] ?? ''),
                        'tmp_name' => (string)$fileBag['tmp_name'][$i],
                        'error'    => (int)$fileBag['error'][$i],
                        'size'     => (int)($fileBag['size'][$i] ?? 0),
                    ];
                }
            } else {
                // Caso single (menos com�n, pero v�lido)
                if (($fileBag['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $archivosPorTarea[$nro][] = [
                        'name'     => (string)($fileBag['name'] ?? ''),
                        'type'     => (string)($fileBag['type'] ?? ''),
                        'tmp_name' => (string)($fileBag['tmp_name'] ?? ''),
                        'error'    => (int)$fileBag['error'],
                        'size'     => (int)($fileBag['size'] ?? 0),
                    ];
                }
            }
        }
    }

    // --------------------------------------------------------------------
    // 2) Normalizar eliminadas por tarea: fotos_eliminadas_tarea_{N}[]
    // --------------------------------------------------------------------
    // Resultado esperado:
    // $eliminadasPorTarea = [
    //   <nroTarea:int> => ['nombre1.jpg','nombre2.png', ...],
    //   ...
    // ];
    $eliminadasPorTarea = [];

    foreach ($_POST as $key => $val) {
        if (preg_match('/^fotos_eliminadas_tarea_(\d+)$/', $key, $m) && is_array($val)) {
            $nro = (int)$m[1];
            $eliminadasPorTarea[$nro] = array_values(
                array_filter(
                    array_map('strval', $val),
                    static fn($s) => $s !== ''
                )
            );
        }
    }


// 2) Mapear archivos por tarea (acepta nombres con o sin [])
$archivosPorTarea = [];

foreach ($_FILES as $key => $fileBag) {
    // Matchea: fotos_tarea_12  o  fotos_tarea_12[]
    if (preg_match('/^fotos_tarea_(\d+)(?:\[\])?$/', $key, $m)) {
        $nro = (int)$m[1];

        // Normalizar a arrays paralelos
        $names = isset($fileBag['name']) ? (array)$fileBag['name'] : [];
        $types = isset($fileBag['type']) ? (array)$fileBag['type'] : [];
        $tmps  = isset($fileBag['tmp_name']) ? (array)$fileBag['tmp_name'] : [];
        $errs  = isset($fileBag['error']) ? (array)$fileBag['error'] : [];
        $sizes = isset($fileBag['size']) ? (array)$fileBag['size'] : [];

        $cnt = max(count($names), count($tmps), count($errs));
        for ($i = 0; $i < $cnt; $i++) {
            $err = $errs[$i] ?? UPLOAD_ERR_NO_FILE;
            if ($err !== UPLOAD_ERR_OK) continue;

            $archivosPorTarea[$nro][] = [
                'name'     => (string)($names[$i] ?? ''),
                'type'     => (string)($types[$i] ?? ''),
                'tmp_name' => (string)($tmps[$i] ?? ''),
                'error'    => (int)$err,
                'size'     => (int)($sizes[$i] ?? 0),
            ];
        }
    }
}

// 2.b) Mapear eliminadas por tarea (POST plano)
$eliminadasPorTarea = [];
foreach ($_POST as $key => $val) {
    // Acepta fotos_eliminadas_tarea_12  o  fotos_eliminadas_tarea_12[]
    if (preg_match('/^fotos_eliminadas_tarea_(\d+)(?:\[\])?$/', $key, $m)) {
        $nro = (int)$m[1];
        $arr = is_array($val) ? $val : [$val];
        $eliminadasPorTarea[$nro] = array_values(
            array_filter(
                array_map('strval', $arr),
                static fn($s) => $s !== ''
            )
        );
    }
}

// 3) Llamada al modelo
$resultado = guardarPresupuesto($payload, $archivosPorTarea, $eliminadasPorTarea);

    if (!empty($resultado['ok'])) {
        $idPresupuestoGuardado = isset($resultado['id_presupuesto']) ? (int)$resultado['id_presupuesto'] : 0;
        $idPrevisitaGuardada = isset($payload['id_previsita']) ? (int)$payload['id_previsita'] : 0;
        $idUsuario = obtenerIdUsuarioSolicitudPresupuesto();

        if ($idPresupuestoGuardado > 0 && $idPrevisitaGuardada > 0 && $idUsuario > 0) {
            $registroIntervencion = registrarIntervencionPresupuesto(
                $idPresupuestoGuardado,
                $idPrevisitaGuardada,
                $idUsuario,
                'guardar'
            );

            if (!empty($registroIntervencion['ok']) && isset($registroIntervencion['intervino'])) {
                $resultado['intervino'] = $registroIntervencion['intervino'];
            }
        }
    }

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
