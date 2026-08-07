<?php

require_once __DIR__ . '/../00-config/configIni.php';
require_once __DIR__ . '/../04-modelo/feriadosModel.php';
require_once __DIR__ . '/../10-clases/Auditoria.php';
require_once __DIR__ . '/../06-funciones_php/csrf.php';
require_once __DIR__ . '/../06-funciones_php/sesionSegura.php';

if (!class_exists('FeriadosControllerException')) {
    class FeriadosControllerException extends RuntimeException
    {
        private $codigoPublico;
        private $estadoHttp;

        public function __construct(
            string $codigoPublico,
            string $mensajePublico,
            int $estadoHttp,
            ?Throwable $anterior = null
        ) {
            parent::__construct($mensajePublico, 0, $anterior);
            $this->codigoPublico = $codigoPublico;
            $this->estadoHttp = $estadoHttp;
        }

        public function codigoPublico(): string
        {
            return $this->codigoPublico;
        }

        public function estadoHttp(): int
        {
            return $this->estadoHttp;
        }
    }
}

if (!function_exists('accionesAdministrativasFeriadosController')) {
    function accionesAdministrativasFeriadosController(): array
    {
        return [
            'listar' => 'GET',
            'obtener' => 'GET',
            'obtener_csrf' => 'GET',
            'crear' => 'POST',
            'actualizar' => 'POST',
            'desactivar' => 'POST',
            'reactivar' => 'POST',
        ];
    }
}

if (!function_exists('accionesEscrituraFeriadosController')) {
    function accionesEscrituraFeriadosController(): array
    {
        return ['crear', 'actualizar', 'desactivar', 'reactivar'];
    }
}

if (!function_exists('respuestaExitosaFeriadosController')) {
    function respuestaExitosaFeriadosController(
        array $data,
        string $mensaje,
        int $estadoHttp = 200
    ): array {
        return [
            'http_status' => $estadoHttp,
            'body' => [
                'ok' => true,
                'data' => $data,
                'message' => $mensaje,
            ],
        ];
    }
}

if (!function_exists('respuestaErrorFeriadosController')) {
    function respuestaErrorFeriadosController(
        string $codigo,
        string $mensaje,
        int $estadoHttp
    ): array {
        return [
            'http_status' => $estadoHttp,
            'body' => [
                'ok' => false,
                'error' => [
                    'code' => $codigo,
                    'message' => $mensaje,
                ],
            ],
        ];
    }
}

if (!function_exists('emitirRespuestaFeriadosController')) {
    function emitirRespuestaFeriadosController(array $respuesta): void
    {
        $estadoHttp = (int)($respuesta['http_status'] ?? 500);
        $body = isset($respuesta['body']) && is_array($respuesta['body'])
            ? $respuesta['body']
            : respuestaErrorFeriadosController(
                'INTERNAL_ERROR',
                'Ocurrió un error interno.',
                500
            )['body'];

        http_response_code($estadoHttp);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            http_response_code(500);
            $json = '{"ok":false,"error":{"code":"INTERNAL_ERROR","message":"Ocurrió un error interno."}}';
        }

        echo $json;
    }
}

if (!function_exists('usuarioFeriadosController')) {
    function usuarioFeriadosController(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE
            || !isset($_SESSION['usuario'])
            || !is_array($_SESSION['usuario'])) {
            throw new FeriadosControllerException(
                'UNAUTHENTICATED',
                'No hay una sesión de usuario activa.',
                401
            );
        }

        $idUsuario = filter_var(
            $_SESSION['usuario']['id_usuario'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $perfil = perfilAutenticadoFeriados();
        $email = is_string($_SESSION['usuario']['email'] ?? null)
            ? trim($_SESSION['usuario']['email'])
            : '';

        if ($idUsuario === false || $perfil === null || $email === '') {
            throw new FeriadosControllerException(
                'UNAUTHENTICATED',
                'No hay una sesión de usuario válida.',
                401
            );
        }

        if (!usuarioAutenticadoPuedeAdministrarFeriados()) {
            throw new FeriadosControllerException(
                'FORBIDDEN',
                'El perfil autenticado no tiene permiso para administrar feriados.',
                403
            );
        }

        return [
            'id_usuario' => (int)$idUsuario,
            'perfil' => $perfil,
            'email' => $email,
        ];
    }
}

if (!function_exists('validarAccionMetodoFeriadosController')) {
    function validarAccionMetodoFeriadosController(string $metodo, $accion): string
    {
        $accion = is_string($accion) ? trim($accion) : '';
        $acciones = accionesAdministrativasFeriadosController();

        if ($accion === '' || !array_key_exists($accion, $acciones)) {
            throw new FeriadosControllerException(
                'INVALID_ACTION',
                'La acción solicitada no es válida.',
                400
            );
        }

        if (strtoupper($metodo) !== $acciones[$accion]) {
            throw new FeriadosControllerException(
                'METHOD_NOT_ALLOWED',
                'El método HTTP no está permitido para esta acción.',
                405
            );
        }

        return $accion;
    }
}

if (!function_exists('leerEntradaFeriadosController')) {
    function leerEntradaFeriadosController(
        string $metodo,
        array $servidor,
        array $query,
        array $post,
        string $cuerpoCrudo
    ): array {
        if (strtoupper($metodo) === 'GET') {
            return $query;
        }

        $contentType = strtolower(trim((string)($servidor['CONTENT_TYPE'] ?? '')));
        if (strpos($contentType, 'application/json') === false) {
            return $post;
        }

        if (trim($cuerpoCrudo) === '') {
            return [];
        }

        $entrada = json_decode($cuerpoCrudo, true);
        if (!is_array($entrada) || preg_match('/^\s*\{/', $cuerpoCrudo) !== 1) {
            throw new FeriadosControllerException(
                'INVALID_DATA',
                'El cuerpo JSON no es válido.',
                400
            );
        }

        return $entrada;
    }
}

if (!function_exists('validarCamposEntradaFeriadosController')) {
    function validarCamposEntradaFeriadosController(array $entrada, array $permitidos): void
    {
        $desconocidos = array_diff(array_keys($entrada), $permitidos);
        if ($desconocidos !== []) {
            throw new FeriadosControllerException(
                'INVALID_DATA',
                'La solicitud contiene campos no permitidos.',
                400
            );
        }
    }
}

if (!function_exists('idFeriadoEntradaController')) {
    function idFeriadoEntradaController($valor): int
    {
        if (!is_int($valor) && !is_string($valor)) {
            throw new FeriadosControllerException(
                'INVALID_DATA',
                'El identificador del feriado no es válido.',
                400
            );
        }

        $valor = trim((string)$valor);
        if (preg_match('/^[1-9][0-9]*$/D', $valor) !== 1) {
            throw new FeriadosControllerException(
                'INVALID_DATA',
                'El identificador del feriado no es válido.',
                400
            );
        }

        $idFeriado = filter_var(
            $valor,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($idFeriado === false) {
            throw new FeriadosControllerException(
                'INVALID_DATA',
                'El identificador del feriado no es válido.',
                400
            );
        }

        return (int)$idFeriado;
    }
}

if (!function_exists('datosFeriadoEntradaController')) {
    function datosFeriadoEntradaController(array $entrada): array
    {
        if (!is_string($entrada['fecha'] ?? null)
            || !is_string($entrada['descripcion'] ?? null)) {
            throw new FeriadosControllerException(
                'INVALID_DATA',
                'La fecha y la descripción son obligatorias.',
                400
            );
        }

        $datos = [
            'fecha' => $entrada['fecha'],
            'descripcion' => $entrada['descripcion'],
        ];
        if (validarDatosFeriado($datos) !== []) {
            throw new FeriadosControllerException(
                'INVALID_DATA',
                'Los datos del feriado no son válidos.',
                400
            );
        }

        return normalizarDatosFeriado($datos);
    }
}

if (!function_exists('abrirConexionFeriadosController')) {
    function abrirConexionFeriadosController(): mysqli
    {
        try {
            $conexion = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
            if (!$conexion || !mysqli_set_charset($conexion, 'utf8mb4')) {
                throw new RuntimeException('No se pudo abrir la conexión.');
            }

            return $conexion;
        } catch (Throwable $error) {
            throw new FeriadosControllerException(
                'DATABASE_ERROR',
                'No se pudo acceder a la base de datos.',
                500,
                $error
            );
        }
    }
}

if (!function_exists('filasConsultaFeriadosController')) {
    function filasConsultaFeriadosController(mysqli $conexion, string $sql): array
    {
        try {
            $consulta = mysqli_prepare($conexion, $sql);
            if (!$consulta || !mysqli_stmt_execute($consulta)) {
                if ($consulta) {
                    mysqli_stmt_close($consulta);
                }
                throw new RuntimeException('Falló la consulta de precondición.');
            }

            $resultado = mysqli_stmt_get_result($consulta);
            if (!$resultado) {
                mysqli_stmt_close($consulta);
                throw new RuntimeException('Falló el resultado de precondición.');
            }

            $filas = [];
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $filas[] = $fila;
            }
            mysqli_free_result($resultado);
            if (!mysqli_stmt_close($consulta)) {
                throw new RuntimeException('Falló el cierre de precondición.');
            }

            return $filas;
        } catch (Throwable $error) {
            throw new AuditoriaException(
                'No se pudo verificar la disponibilidad de la auditoría.',
                0,
                $error
            );
        }
    }
}

if (!function_exists('verificarPrecondicionesAuditoriaFeriados')) {
    function verificarPrecondicionesAuditoriaFeriados(mysqli $conexion): void
    {
        $tablas = filasConsultaFeriadosController(
            $conexion,
            "SELECT TABLE_NAME, ENGINE
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME IN ('auditoria', 'feriados')"
        );
        $motores = [];
        foreach ($tablas as $tabla) {
            $motores[(string)$tabla['TABLE_NAME']] = strtoupper((string)$tabla['ENGINE']);
        }

        if (($motores['auditoria'] ?? '') !== 'INNODB'
            || ($motores['feriados'] ?? '') !== 'INNODB') {
            throw new AuditoriaException(
                'La auditoría y los feriados deben estar disponibles en tablas InnoDB.'
            );
        }

        $columnas = filasConsultaFeriadosController(
            $conexion,
            "SELECT COLUMN_NAME, COLUMN_TYPE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'auditoria'"
        );
        $columnasDisponibles = [];
        $tipoAccion = '';
        foreach ($columnas as $columna) {
            $nombre = (string)$columna['COLUMN_NAME'];
            $columnasDisponibles[$nombre] = true;
            if ($nombre === 'accion_realizada') {
                $tipoAccion = strtolower((string)$columna['COLUMN_TYPE']);
            }
        }

        $requeridas = [
            'id_auditoria',
            'id_usuario',
            'email_usuario',
            'perfil_usuario',
            'accion_realizada',
            'fecha_hora',
            'ip_origen',
            'dispositivo',
            'navegador',
            'modulo_afectado',
            'metodo_acceso',
            'url_acceso',
            'descripcion_cambio',
            'datos_previos',
        ];
        foreach ($requeridas as $columnaRequerida) {
            if (!isset($columnasDisponibles[$columnaRequerida])) {
                throw new AuditoriaException(
                    'La tabla de auditoría no posee todas las columnas requeridas.'
                );
            }
        }

        if (strpos($tipoAccion, "'insert'") === false
            || strpos($tipoAccion, "'update'") === false) {
            throw new AuditoriaException(
                'La tabla de auditoría no admite las acciones requeridas.'
            );
        }
    }
}

if (!function_exists('serializarAuditoriaFeriadosController')) {
    function serializarAuditoriaFeriadosController(array $datos): string
    {
        $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new AuditoriaException('No se pudieron serializar los datos de auditoría.');
        }

        return $json;
    }
}

if (!function_exists('diferenciasFeriadoAuditoriaController')) {
    function diferenciasFeriadoAuditoriaController(array $anterior, array $nuevo): array
    {
        $diferencias = [];
        foreach (['fecha', 'descripcion', 'estado'] as $campo) {
            if (($anterior[$campo] ?? null) !== ($nuevo[$campo] ?? null)) {
                $diferencias[$campo] = [
                    'anterior' => $anterior[$campo] ?? null,
                    'nuevo' => $nuevo[$campo] ?? null,
                ];
            }
        }

        return $diferencias;
    }
}

if (!function_exists('registrarAuditoriaFeriadosController')) {
    function registrarAuditoriaFeriadosController(
        Auditoria $auditoria,
        array $usuario,
        string $operacion,
        ?array $anterior,
        array $nuevo,
        string $url
    ): void {
        if ($operacion === 'alta') {
            $descripcion = serializarAuditoriaFeriadosController([
                'operacion' => 'alta',
                'id_feriado' => (int)$nuevo['id_feriado'],
                'datos_nuevos' => $nuevo,
            ]);
            $resultado = $auditoria->registrarAlta(
                $usuario['id_usuario'],
                $usuario['email'],
                $usuario['perfil'],
                'FERIADOS',
                $url,
                $descripcion
            );
        } else {
            if ($anterior === null) {
                throw new AuditoriaException('Faltan los datos anteriores para la auditoría.');
            }
            $descripcion = serializarAuditoriaFeriadosController([
                'operacion' => $operacion,
                'id_feriado' => (int)$nuevo['id_feriado'],
                'datos_nuevos' => $nuevo,
                'cambios' => diferenciasFeriadoAuditoriaController($anterior, $nuevo),
            ]);
            $datosPrevios = serializarAuditoriaFeriadosController([
                'id_feriado' => (int)$anterior['id_feriado'],
                'datos_anteriores' => $anterior,
            ]);
            $resultado = $auditoria->registrarModificacion(
                $usuario['id_usuario'],
                $usuario['email'],
                $usuario['perfil'],
                'FERIADOS',
                $url,
                $descripcion,
                $datosPrevios
            );
        }

        if ($resultado !== true) {
            throw new AuditoriaException('No se pudo confirmar el registro de auditoría.');
        }
    }
}

if (!function_exists('coordinarEscrituraAuditadaFeriados')) {
    function coordinarEscrituraAuditadaFeriados(
        callable $iniciar,
        callable $operar,
        callable $auditar,
        callable $confirmar,
        callable $revertir
    ): array {
        $transaccionPropia = false;

        try {
            $iniciar();
            $transaccionPropia = true;
            $resultado = $operar();
            $auditar($resultado);
            $confirmar();
            $transaccionPropia = false;

            return $resultado;
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                try {
                    $revertir();
                } catch (Throwable $errorRollback) {
                    error_log('ADMINTECH Feriados: no se pudo revertir la transacción administrativa.');
                }
            }

            throw $error;
        }
    }
}

if (!function_exists('ejecutarEscrituraAuditadaFeriadosController')) {
    function ejecutarEscrituraAuditadaFeriadosController(
        mysqli $conexion,
        callable $operacion,
        callable $registroAuditoria
    ): array {
        verificarPrecondicionesAuditoriaFeriados($conexion);
        $auditoria = new Auditoria($conexion, false);

        return coordinarEscrituraAuditadaFeriados(
            function () use ($conexion): void {
                if (!mysqli_begin_transaction($conexion)) {
                    throw new RuntimeException('No se pudo iniciar la transacción administrativa.');
                }
            },
            function () use ($operacion, $conexion): array {
                return $operacion($conexion);
            },
            function (array $resultado) use ($registroAuditoria, $auditoria): void {
                $registroAuditoria($auditoria, $resultado);
            },
            function () use ($conexion): void {
                if (!mysqli_commit($conexion)) {
                    throw new RuntimeException('No se pudo confirmar la transacción administrativa.');
                }
            },
            function () use ($conexion): void {
                if (!mysqli_rollback($conexion)) {
                    throw new RuntimeException('No se pudo revertir la transacción administrativa.');
                }
            }
        );
    }
}

if (!function_exists('urlAuditoriaFeriadosController')) {
    function urlAuditoriaFeriadosController(array $servidor): string
    {
        $url = preg_replace('/[\x00-\x1F\x7F]/', '', (string)($servidor['REQUEST_URI'] ?? ''));
        $url = is_string($url) ? $url : '';

        return function_exists('mb_substr')
            ? mb_substr($url, 0, 255, 'UTF-8')
            : substr($url, 0, 255);
    }
}

if (!function_exists('contextoEscrituraFeriadosController')) {
    function contextoEscrituraFeriadosController(
        mysqli $conexion,
        string $accion,
        array $entrada
    ): array {
        if ($accion === 'crear') {
            $nuevo = crearFeriadoEnTransaccion(
                $conexion,
                datosFeriadoEntradaController($entrada)
            );

            return [
                'operacion' => 'alta',
                'anterior' => null,
                'feriado' => $nuevo,
                'cambio' => true,
            ];
        }

        $idFeriado = idFeriadoEntradaController($entrada['id_feriado'] ?? null);
        $anterior = obtenerFeriadoPorId($conexion, $idFeriado, true);
        if ($anterior === null) {
            throw new RuntimeException('El feriado solicitado no existe.', 404);
        }

        if ($accion === 'actualizar') {
            $nuevo = actualizarFeriadoEnTransaccion(
                $conexion,
                $idFeriado,
                datosFeriadoEntradaController($entrada)
            );
            $operacion = 'modificacion';
        } elseif ($accion === 'desactivar') {
            $nuevo = desactivarFeriadoEnTransaccion($conexion, $idFeriado);
            $operacion = 'desactivacion';
        } elseif ($accion === 'reactivar') {
            $nuevo = reactivarFeriadoEnTransaccion($conexion, $idFeriado);
            $operacion = 'reactivacion';
        } else {
            throw new FeriadosControllerException(
                'INVALID_ACTION',
                'La acción solicitada no es válida.',
                400
            );
        }

        return [
            'operacion' => $operacion,
            'anterior' => $anterior,
            'feriado' => $nuevo,
            'cambio' => diferenciasFeriadoAuditoriaController($anterior, $nuevo) !== [],
        ];
    }
}

if (!function_exists('mapearErrorFeriadosController')) {
    function mapearErrorFeriadosController(Throwable $error): array
    {
        if ($error instanceof FeriadosControllerException) {
            return respuestaErrorFeriadosController(
                $error->codigoPublico(),
                $error->getMessage(),
                $error->estadoHttp()
            );
        }

        if ($error instanceof AuditoriaException) {
            return respuestaErrorFeriadosController(
                'AUDIT_FAILURE',
                'No se pudo registrar la auditoría de la operación.',
                500
            );
        }

        if ($error instanceof mysqli_sql_exception) {
            return respuestaErrorFeriadosController(
                'DATABASE_ERROR',
                'No se pudo completar la operación en la base de datos.',
                500
            );
        }

        if ($error instanceof RuntimeException) {
            $estado = (int)$error->getCode();
            $mensaje = function_exists('mb_strtolower')
                ? mb_strtolower($error->getMessage(), 'UTF-8')
                : strtolower($error->getMessage());

            if ($estado === 404) {
                return respuestaErrorFeriadosController(
                    'NOT_FOUND',
                    'El feriado solicitado no existe.',
                    404
                );
            }

            if ($estado === 422) {
                $codigo = strpos($mensaje, 'estado') !== false
                    ? 'INVALID_STATE_TRANSITION'
                    : 'INVALID_DATA';
                return respuestaErrorFeriadosController(
                    $codigo,
                    $codigo === 'INVALID_DATA'
                        ? 'Los datos enviados no son válidos.'
                        : 'La transición de estado no es válida.',
                    $codigo === 'INVALID_DATA' ? 400 : 409
                );
            }

            if ($estado === 409) {
                if (strpos($mensaje, 'ya existe') !== false) {
                    return respuestaErrorFeriadosController(
                        'DUPLICATE_DATE',
                        'Ya existe un feriado para la fecha indicada.',
                        409
                    );
                }
                if (strpos($mensaje, 'históric') !== false
                    || strpos($mensaje, 'posterior a hoy') !== false) {
                    return respuestaErrorFeriadosController(
                        'HISTORICAL_CONFLICT',
                        'La operación no está permitida por la regla de fechas históricas.',
                        409
                    );
                }
                if (strpos($mensaje, 'estado') !== false) {
                    return respuestaErrorFeriadosController(
                        'INVALID_STATE_TRANSITION',
                        'La transición de estado no es válida.',
                        409
                    );
                }

                return respuestaErrorFeriadosController(
                    'CONCURRENCY_CONFLICT',
                    'El feriado cambió durante la operación; vuelva a intentarlo.',
                    409
                );
            }

            if ($estado >= 500) {
                return respuestaErrorFeriadosController(
                    'DATABASE_ERROR',
                    'No se pudo completar la operación en la base de datos.',
                    500
                );
            }
        }

        return respuestaErrorFeriadosController(
            'INTERNAL_ERROR',
            'Ocurrió un error interno.',
            500
        );
    }
}

if (!function_exists('procesarSolicitudFeriadosController')) {
    function procesarSolicitudFeriadosController(
        string $metodo,
        array $entrada,
        array $servidor
    ): array {
        $usuario = usuarioFeriadosController();
        $accion = validarAccionMetodoFeriadosController(
            $metodo,
            $entrada['accion'] ?? null
        );

        $camposPorAccion = [
            'listar' => ['accion'],
            'obtener' => ['accion', 'id_feriado'],
            'obtener_csrf' => ['accion'],
            'crear' => ['accion', 'fecha', 'descripcion', 'csrf_token'],
            'actualizar' => ['accion', 'id_feriado', 'fecha', 'descripcion', 'csrf_token'],
            'desactivar' => ['accion', 'id_feriado', 'csrf_token'],
            'reactivar' => ['accion', 'id_feriado', 'csrf_token'],
        ];
        validarCamposEntradaFeriadosController($entrada, $camposPorAccion[$accion]);

        if ($accion === 'obtener_csrf') {
            return respuestaExitosaFeriadosController(
                ['csrf_token' => obtenerOCrearTokenCsrfAdmintech()],
                'Token de seguridad obtenido.'
            );
        }

        if (in_array($accion, accionesEscrituraFeriadosController(), true)) {
            $tokenCsrf = extraerTokenCsrfAdmintech($metodo, $servidor, $entrada);
            if (!validarTokenCsrfAdmintech($tokenCsrf)) {
                throw new FeriadosControllerException(
                    'CSRF_VALIDATION_FAILED',
                    'No se pudo validar la solicitud.',
                    403
                );
            }
        }

        if ($accion === 'obtener'
            || $accion === 'actualizar'
            || $accion === 'desactivar'
            || $accion === 'reactivar') {
            idFeriadoEntradaController($entrada['id_feriado'] ?? null);
        }
        if ($accion === 'crear' || $accion === 'actualizar') {
            $datos = datosFeriadoEntradaController($entrada);
            if ($accion === 'crear') {
                validarAltaFeriado($datos);
            }
        }

        $conexion = abrirConexionFeriadosController();
        try {
            if ($accion === 'listar') {
                return respuestaExitosaFeriadosController(
                    ['feriados' => listarFeriados($conexion)],
                    'Listado administrativo de feriados obtenido.'
                );
            }

            if ($accion === 'obtener') {
                $idFeriado = idFeriadoEntradaController($entrada['id_feriado']);
                $feriado = obtenerFeriadoPorId($conexion, $idFeriado, false);
                if ($feriado === null) {
                    throw new RuntimeException('El feriado solicitado no existe.', 404);
                }

                return respuestaExitosaFeriadosController(
                    ['feriado' => $feriado],
                    'Feriado obtenido.'
                );
            }

            $url = urlAuditoriaFeriadosController($servidor);
            $resultado = ejecutarEscrituraAuditadaFeriadosController(
                $conexion,
                function (mysqli $conexionOperacion) use ($accion, $entrada): array {
                    return contextoEscrituraFeriadosController(
                        $conexionOperacion,
                        $accion,
                        $entrada
                    );
                },
                function (Auditoria $auditoria, array $contexto) use ($usuario, $url): void {
                    if (!$contexto['cambio']) {
                        return;
                    }
                    registrarAuditoriaFeriadosController(
                        $auditoria,
                        $usuario,
                        $contexto['operacion'],
                        $contexto['anterior'],
                        $contexto['feriado'],
                        $url
                    );
                }
            );

            $sinCambios = !$resultado['cambio'];
            $mensajes = [
                'crear' => 'Feriado creado correctamente.',
                'actualizar' => $sinCambios
                    ? 'El feriado ya contenía los datos indicados.'
                    : 'Feriado actualizado correctamente.',
                'desactivar' => $sinCambios
                    ? 'El feriado ya estaba desactivado.'
                    : 'Feriado desactivado correctamente.',
                'reactivar' => $sinCambios
                    ? 'El feriado ya estaba habilitado.'
                    : 'Feriado reactivado correctamente.',
            ];

            return respuestaExitosaFeriadosController(
                [
                    'feriado' => $resultado['feriado'],
                    'changed' => !$sinCambios,
                ],
                $mensajes[$accion],
                $accion === 'crear' ? 201 : 200
            );
        } finally {
            try {
                mysqli_close($conexion);
            } catch (Throwable $errorCierre) {
                error_log('ADMINTECH Feriados: no se pudo cerrar la conexión administrativa.');
            }
        }
    }
}

if (!function_exists('ejecutarEndpointFeriadosController')) {
    function ejecutarEndpointFeriadosController(): void
    {
        try {
            if (session_status() !== PHP_SESSION_ACTIVE && !iniciarSesionAdmintech()) {
                throw new FeriadosControllerException(
                    'INTERNAL_ERROR',
                    'No se pudo iniciar la sesión.',
                    500
                );
            }

            $metodo = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? ''));
            $entrada = leerEntradaFeriadosController(
                $metodo,
                $_SERVER,
                $_GET,
                $_POST,
                (string)file_get_contents('php://input')
            );
            $respuesta = procesarSolicitudFeriadosController(
                $metodo,
                $entrada,
                $_SERVER
            );
        } catch (Throwable $error) {
            $respuesta = mapearErrorFeriadosController($error);
        }

        emitirRespuestaFeriadosController($respuesta);
    }
}

if (!defined('FERIADOS_CONTROLLER_NO_DISPATCH')) {
    ejecutarEndpointFeriadosController();
}
