<?php

if (!function_exists('normalizarPerfilFeriados')) {
    function normalizarPerfilFeriados(?string $perfil): string
    {
        return trim((string)$perfil);
    }
}

if (!function_exists('perfilesAdministrativosFeriados')) {
    function perfilesAdministrativosFeriados(): array
    {
        return [
            'Super Administrador',
            'Administrador',
            'Administrativo',
        ];
    }
}

if (!function_exists('perfilPuedeAdministrarFeriados')) {
    /**
     * Primitiva pura. El perfil debe provenir de una fuente confiable del servidor,
     * nunca de GET, POST, JSON o parámetros enviados por el navegador.
     */
    function perfilPuedeAdministrarFeriados(?string $perfil): bool
    {
        return in_array(
            normalizarPerfilFeriados($perfil),
            perfilesAdministrativosFeriados(),
            true
        );
    }
}

if (!function_exists('perfilAutenticadoFeriados')) {
    function perfilAutenticadoFeriados(): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $perfil = $_SESSION['usuario']['perfil'] ?? null;
        if (!is_string($perfil)) {
            return null;
        }

        $perfil = normalizarPerfilFeriados($perfil);

        return $perfil === '' ? null : $perfil;
    }
}

if (!function_exists('usuarioAutenticadoPuedeAdministrarFeriados')) {
    function usuarioAutenticadoPuedeAdministrarFeriados(): bool
    {
        return perfilPuedeAdministrarFeriados(perfilAutenticadoFeriados());
    }
}

if (!function_exists('estadosFeriadoPermitidos')) {
    function estadosFeriadoPermitidos(): array
    {
        return ['enabled', 'disabled'];
    }
}

if (!function_exists('normalizarEstadoFeriado')) {
    function normalizarEstadoFeriado(?string $estado): string
    {
        return strtolower(trim((string)$estado));
    }
}

if (!function_exists('estadoFeriadoValido')) {
    function estadoFeriadoValido(?string $estado): bool
    {
        return in_array(normalizarEstadoFeriado($estado), estadosFeriadoPermitidos(), true);
    }
}

if (!function_exists('normalizarFechaFeriado')) {
    function normalizarFechaFeriado($fecha): string
    {
        return trim((string)$fecha);
    }
}

if (!function_exists('fechaFeriadoValida')) {
    function fechaFeriadoValida($fecha): bool
    {
        $fecha = normalizarFechaFeriado($fecha);
        $fechaParseada = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        $errores = DateTimeImmutable::getLastErrors();

        return $fechaParseada !== false
            && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))
            && $fechaParseada->format('Y-m-d') === $fecha;
    }
}

if (!function_exists('fechaActualAdministracionFeriados')) {
    function fechaActualAdministracionFeriados(?DateTimeImmutable $ahora = null): string
    {
        $zonaHoraria = new DateTimeZone('America/Argentina/Buenos_Aires');
        $ahora = $ahora === null
            ? new DateTimeImmutable('now', $zonaHoraria)
            : $ahora->setTimezone($zonaHoraria);

        return $ahora->format('Y-m-d');
    }
}

if (!function_exists('esFechaHistoricaFeriado')) {
    function esFechaHistoricaFeriado(string $fecha, ?DateTimeImmutable $ahora = null): bool
    {
        if (!fechaFeriadoValida($fecha)) {
            throw new InvalidArgumentException('La fecha del feriado no es válida.');
        }

        return $fecha <= fechaActualAdministracionFeriados($ahora);
    }
}

if (!function_exists('esFechaFuturaFeriado')) {
    function esFechaFuturaFeriado(string $fecha, ?DateTimeImmutable $ahora = null): bool
    {
        if (!fechaFeriadoValida($fecha)) {
            throw new InvalidArgumentException('La fecha del feriado no es válida.');
        }

        return $fecha > fechaActualAdministracionFeriados($ahora);
    }
}

if (!function_exists('normalizarDescripcionFeriado')) {
    function normalizarDescripcionFeriado($descripcion): string
    {
        return trim((string)$descripcion);
    }
}

if (!function_exists('longitudDescripcionFeriado')) {
    function longitudDescripcionFeriado(string $descripcion): int
    {
        return function_exists('mb_strlen') ? mb_strlen($descripcion, 'UTF-8') : strlen($descripcion);
    }
}

if (!function_exists('validarDatosFeriado')) {
    function validarDatosFeriado(array $datos): array
    {
        $fecha = normalizarFechaFeriado($datos['fecha'] ?? '');
        $descripcion = normalizarDescripcionFeriado($datos['descripcion'] ?? '');
        $errores = [];

        if (!fechaFeriadoValida($fecha)) {
            $errores['fecha'] = 'La fecha es obligatoria y debe tener formato YYYY-MM-DD.';
        }

        if ($descripcion === '') {
            $errores['descripcion'] = 'La descripción es obligatoria.';
        } elseif (longitudDescripcionFeriado($descripcion) > 255) {
            $errores['descripcion'] = 'La descripción no puede superar los 255 caracteres.';
        }

        return $errores;
    }
}

if (!function_exists('normalizarDatosFeriado')) {
    function normalizarDatosFeriado(array $datos): array
    {
        return [
            'fecha' => normalizarFechaFeriado($datos['fecha'] ?? ''),
            'descripcion' => normalizarDescripcionFeriado($datos['descripcion'] ?? ''),
        ];
    }
}

if (!function_exists('asegurarDatosFeriadoValidos')) {
    function asegurarDatosFeriadoValidos(array $datos): array
    {
        $datosNormalizados = normalizarDatosFeriado($datos);
        $errores = validarDatosFeriado($datosNormalizados);

        if ($errores !== []) {
            throw new RuntimeException(implode(' ', array_values($errores)), 422);
        }

        return $datosNormalizados;
    }
}

if (!function_exists('asegurarIdFeriadoValido')) {
    function asegurarIdFeriadoValido($idFeriado): int
    {
        if (filter_var($idFeriado, FILTER_VALIDATE_INT) === false || (int)$idFeriado <= 0) {
            throw new RuntimeException('El identificador del feriado no es válido.', 422);
        }

        return (int)$idFeriado;
    }
}

if (!function_exists('asegurarFechaAdministrableFeriado')) {
    function asegurarFechaAdministrableFeriado(string $fecha, ?DateTimeImmutable $ahora = null): void
    {
        if (!esFechaFuturaFeriado($fecha, $ahora)) {
            throw new RuntimeException(
                'La fecha debe ser estrictamente posterior a hoy.',
                409
            );
        }
    }
}

if (!function_exists('validarAltaFeriado')) {
    function validarAltaFeriado(array $datos, ?DateTimeImmutable $ahora = null): array
    {
        if (array_key_exists('estado', $datos)
            && normalizarEstadoFeriado($datos['estado']) !== 'enabled') {
            throw new RuntimeException('Los feriados nuevos deben crearse habilitados.', 422);
        }

        $datos = asegurarDatosFeriadoValidos($datos);
        asegurarFechaAdministrableFeriado($datos['fecha'], $ahora);

        return $datos;
    }
}

if (!function_exists('validarModificacionFeriado')) {
    function validarModificacionFeriado(
        array $actual,
        array $datos,
        ?DateTimeImmutable $ahora = null
    ): array {
        $fechaActual = normalizarFechaFeriado($actual['fecha'] ?? '');
        $descripcionActual = normalizarDescripcionFeriado($actual['descripcion'] ?? '');
        $estadoActual = normalizarEstadoFeriado($actual['estado'] ?? '');

        if (!fechaFeriadoValida($fechaActual) || !estadoFeriadoValido($estadoActual)) {
            throw new RuntimeException('El feriado persistido tiene datos inválidos.', 500);
        }

        $estadoIncluido = array_key_exists('estado', $datos);
        $estadoSolicitado = $estadoIncluido
            ? normalizarEstadoFeriado($datos['estado'])
            : $estadoActual;
        $datos = asegurarDatosFeriadoValidos($datos);

        if ($estadoIncluido) {
            if (!estadoFeriadoValido($estadoSolicitado)) {
                throw new RuntimeException('El estado solicitado no es válido.', 422);
            }
            if ($estadoSolicitado !== $estadoActual) {
                throw new RuntimeException(
                    'El estado debe modificarse mediante la operación específica de cambio de estado.',
                    409
                );
            }
        }

        $cambiaFecha = $datos['fecha'] !== $fechaActual;
        $cambiaDescripcion = $datos['descripcion'] !== $descripcionActual;
        $esHistorico = esFechaHistoricaFeriado($fechaActual, $ahora);

        if ($esHistorico && $cambiaFecha) {
            throw new RuntimeException('La fecha de un feriado histórico no puede modificarse.', 409);
        }

        if (!$esHistorico) {
            asegurarFechaAdministrableFeriado($datos['fecha'], $ahora);
        }

        return [
            'fecha' => $datos['fecha'],
            'descripcion' => $datos['descripcion'],
            'estado' => $estadoActual,
            'es_historico' => $esHistorico,
            'cambia_fecha' => $cambiaFecha,
            'cambia_descripcion' => $cambiaDescripcion,
            'requiere_actualizacion' => $cambiaFecha || $cambiaDescripcion,
        ];
    }
}

if (!function_exists('validarCambioEstadoFeriado')) {
    function validarCambioEstadoFeriado(
        array $actual,
        string $estadoDestino,
        ?DateTimeImmutable $ahora = null
    ): array {
        $fechaActual = normalizarFechaFeriado($actual['fecha'] ?? '');
        $estadoActual = normalizarEstadoFeriado($actual['estado'] ?? '');
        $estadoDestino = normalizarEstadoFeriado($estadoDestino);

        if (!fechaFeriadoValida($fechaActual) || !estadoFeriadoValido($estadoActual)) {
            throw new RuntimeException('El feriado persistido tiene datos inválidos.', 500);
        }

        if (!estadoFeriadoValido($estadoDestino)) {
            throw new RuntimeException('El estado de destino no es válido.', 422);
        }

        if ($estadoActual === $estadoDestino) {
            return [
                'estado_actual' => $estadoActual,
                'estado_destino' => $estadoDestino,
                'requiere_actualizacion' => false,
            ];
        }

        if (!esFechaFuturaFeriado($fechaActual, $ahora)) {
            throw new RuntimeException('El estado de un feriado histórico no puede modificarse.', 409);
        }

        return [
            'estado_actual' => $estadoActual,
            'estado_destino' => $estadoDestino,
            'requiere_actualizacion' => true,
        ];
    }
}

if (!function_exists('normalizarFilaFeriado')) {
    function normalizarFilaFeriado(array $fila): array
    {
        return [
            'id_feriado' => (int)$fila['id_feriado'],
            'fecha' => (string)$fila['fecha'],
            'descripcion' => (string)$fila['descripcion'],
            'estado' => (string)$fila['estado'],
        ];
    }
}

if (!function_exists('prepararConsultaFeriados')) {
    function prepararConsultaFeriados(mysqli $conexion, string $sql): mysqli_stmt
    {
        $consulta = mysqli_prepare($conexion, $sql);

        if ($consulta === false) {
            throw new RuntimeException('No se pudo preparar la consulta de feriados.', 500);
        }

        return $consulta;
    }
}

if (!function_exists('obtenerResultadoConsultaFeriados')) {
    function obtenerResultadoConsultaFeriados(mysqli_stmt $consulta): mysqli_result
    {
        $resultado = mysqli_stmt_get_result($consulta);

        if ($resultado === false) {
            throw new RuntimeException('No se pudo obtener el resultado de la consulta de feriados.', 500);
        }

        return $resultado;
    }
}

if (!function_exists('listarFeriados')) {
    function listarFeriados(mysqli $conexion, ?string $estado = null): array
    {
        $estadoNormalizado = null;

        if ($estado !== null) {
            $estadoNormalizado = normalizarEstadoFeriado($estado);
            if (!estadoFeriadoValido($estadoNormalizado)) {
                throw new RuntimeException('El estado solicitado no es válido.', 422);
            }
            $sql = 'SELECT id_feriado, fecha, descripcion, estado
                    FROM feriados
                    WHERE estado = ?
                    ORDER BY fecha DESC, id_feriado DESC';
        } else {
            $sql = 'SELECT id_feriado, fecha, descripcion, estado
                    FROM feriados
                    ORDER BY fecha DESC, id_feriado DESC';
        }

        $consulta = prepararConsultaFeriados($conexion, $sql);

        if ($estadoNormalizado !== null) {
            mysqli_stmt_bind_param($consulta, 's', $estadoNormalizado);
        }

        if (!mysqli_stmt_execute($consulta)) {
            mysqli_stmt_close($consulta);
            throw new RuntimeException('No se pudo consultar el listado de feriados.', 500);
        }

        $resultado = obtenerResultadoConsultaFeriados($consulta);
        $feriados = [];
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $feriados[] = normalizarFilaFeriado($fila);
        }

        mysqli_free_result($resultado);
        mysqli_stmt_close($consulta);

        return $feriados;
    }
}

if (!function_exists('listarFeriadosHabilitados')) {
    function listarFeriadosHabilitados(mysqli $conexion): array
    {
        return listarFeriados($conexion, 'enabled');
    }
}

if (!function_exists('obtenerFeriadoPorId')) {
    function obtenerFeriadoPorId(mysqli $conexion, $idFeriado, bool $bloquear = false): ?array
    {
        $idFeriado = asegurarIdFeriadoValido($idFeriado);
        $sql = $bloquear
            ? 'SELECT id_feriado, fecha, descripcion, estado
               FROM feriados
               WHERE id_feriado = ?
               FOR UPDATE'
            : 'SELECT id_feriado, fecha, descripcion, estado
               FROM feriados
               WHERE id_feriado = ?';

        $consulta = prepararConsultaFeriados($conexion, $sql);
        mysqli_stmt_bind_param($consulta, 'i', $idFeriado);

        if (!mysqli_stmt_execute($consulta)) {
            mysqli_stmt_close($consulta);
            throw new RuntimeException('No se pudo consultar el feriado.', 500);
        }

        $resultado = obtenerResultadoConsultaFeriados($consulta);
        $fila = mysqli_fetch_assoc($resultado);

        mysqli_free_result($resultado);
        mysqli_stmt_close($consulta);

        return $fila === null ? null : normalizarFilaFeriado($fila);
    }
}

if (!function_exists('obtenerFeriadoPorFecha')) {
    function obtenerFeriadoPorFecha(
        mysqli $conexion,
        string $fecha,
        ?int $idFeriadoExcluido = null,
        bool $bloquear = false
    ): ?array {
        if (!fechaFeriadoValida($fecha)) {
            throw new RuntimeException('La fecha del feriado no es válida.', 422);
        }

        if ($idFeriadoExcluido !== null) {
            $idFeriadoExcluido = asegurarIdFeriadoValido($idFeriadoExcluido);
            $sql = $bloquear
                ? 'SELECT id_feriado, fecha, descripcion, estado
                   FROM feriados
                   WHERE fecha = ? AND id_feriado <> ?
                   FOR UPDATE'
                : 'SELECT id_feriado, fecha, descripcion, estado
                   FROM feriados
                   WHERE fecha = ? AND id_feriado <> ?';
        } else {
            $sql = $bloquear
                ? 'SELECT id_feriado, fecha, descripcion, estado
                   FROM feriados
                   WHERE fecha = ?
                   FOR UPDATE'
                : 'SELECT id_feriado, fecha, descripcion, estado
                   FROM feriados
                   WHERE fecha = ?';
        }

        $consulta = prepararConsultaFeriados($conexion, $sql);
        if ($idFeriadoExcluido === null) {
            mysqli_stmt_bind_param($consulta, 's', $fecha);
        } else {
            mysqli_stmt_bind_param($consulta, 'si', $fecha, $idFeriadoExcluido);
        }

        if (!mysqli_stmt_execute($consulta)) {
            mysqli_stmt_close($consulta);
            throw new RuntimeException('No se pudo consultar la fecha del feriado.', 500);
        }

        $resultado = obtenerResultadoConsultaFeriados($consulta);
        $fila = mysqli_fetch_assoc($resultado);

        mysqli_free_result($resultado);
        mysqli_stmt_close($consulta);

        return $fila === null ? null : normalizarFilaFeriado($fila);
    }
}

if (!function_exists('iniciarTransaccionFeriados')) {
    function iniciarTransaccionFeriados(mysqli $conexion): void
    {
        if (!mysqli_begin_transaction($conexion)) {
            throw new RuntimeException('No se pudo iniciar la transacción de feriados.', 500);
        }
    }
}

if (!function_exists('confirmarTransaccionFeriados')) {
    function confirmarTransaccionFeriados(mysqli $conexion): void
    {
        if (!mysqli_commit($conexion)) {
            throw new RuntimeException('No se pudo confirmar la transacción de feriados.', 500);
        }
    }
}

if (!function_exists('revertirTransaccionFeriados')) {
    function revertirTransaccionFeriados(mysqli $conexion): void
    {
        try {
            mysqli_rollback($conexion);
        } catch (Throwable $errorRollback) {
            // Se preserva la excepción original de la operación.
        }
    }
}

if (!function_exists('normalizarErrorEscrituraFeriado')) {
    function normalizarErrorEscrituraFeriado(
        Throwable $error,
        string $mensajeOperacion
    ): Throwable {
        if (!$error instanceof mysqli_sql_exception) {
            return $error;
        }

        $numeroError = (int)$error->getCode();
        if ($numeroError === 1062) {
            return new RuntimeException('Ya existe un feriado para la fecha indicada.', 409, $error);
        }

        if (in_array($numeroError, [1205, 1213], true)) {
            return new RuntimeException(
                'La operación entró en conflicto con otro cambio concurrente; debe reintentarse.',
                409,
                $error
            );
        }

        return new RuntimeException($mensajeOperacion, 500, $error);
    }
}

if (!function_exists('ejecutarOperacionAutonomaFeriados')) {
    /**
     * Ejecuta una operación como propietaria de una transacción nueva.
     * No debe llamarse si el consumidor ya abrió una transacción en la conexión.
     */
    function ejecutarOperacionAutonomaFeriados(
        mysqli $conexion,
        callable $operacion,
        string $mensajeError
    ): array {
        $transaccionPropia = false;

        try {
            iniciarTransaccionFeriados($conexion);
            $transaccionPropia = true;
            $resultado = $operacion();
            confirmarTransaccionFeriados($conexion);
            $transaccionPropia = false;

            return $resultado;
        } catch (Throwable $error) {
            if ($transaccionPropia) {
                revertirTransaccionFeriados($conexion);
            }

            throw normalizarErrorEscrituraFeriado($error, $mensajeError);
        }
    }
}

if (!function_exists('crearFeriadoEnTransaccion')) {
    /**
     * Requiere una transacción activa propiedad del consumidor.
     * No inicia, confirma ni revierte la transacción recibida.
     */
    function crearFeriadoEnTransaccion(
        mysqli $conexion,
        array $datos,
        ?DateTimeImmutable $ahora = null
    ): array {
        $datos = validarAltaFeriado($datos, $ahora);

        try {
            $existente = obtenerFeriadoPorFecha($conexion, $datos['fecha'], null, true);
            if ($existente !== null) {
                $mensaje = $existente['estado'] === 'disabled'
                    ? 'La fecha ya existe deshabilitada; debe reactivarse el registro existente.'
                    : 'Ya existe un feriado para la fecha indicada.';
                throw new RuntimeException($mensaje, 409);
            }

            $consulta = prepararConsultaFeriados(
                $conexion,
                "INSERT INTO feriados (fecha, descripcion, estado) VALUES (?, ?, 'enabled')"
            );
            mysqli_stmt_bind_param($consulta, 'ss', $datos['fecha'], $datos['descripcion']);

            if (!mysqli_stmt_execute($consulta)) {
                $numeroError = mysqli_stmt_errno($consulta);
                mysqli_stmt_close($consulta);

                if ($numeroError === 1062) {
                    throw new RuntimeException('Ya existe un feriado para la fecha indicada.', 409);
                }

                throw new RuntimeException('No se pudo crear el feriado.', 500);
            }

            $idFeriado = (int)mysqli_insert_id($conexion);
            mysqli_stmt_close($consulta);
            $feriado = obtenerFeriadoPorId($conexion, $idFeriado, false);

            if ($feriado === null) {
                throw new RuntimeException('No se pudo recuperar el feriado creado.', 500);
            }

            return $feriado;
        } catch (Throwable $error) {
            throw normalizarErrorEscrituraFeriado($error, 'No se pudo crear el feriado.');
        }
    }
}

if (!function_exists('crearFeriado')) {
    /**
     * Uso autónomo: esta función es propietaria de su transacción.
     */
    function crearFeriado(
        mysqli $conexion,
        array $datos,
        ?DateTimeImmutable $ahora = null
    ): array {
        return ejecutarOperacionAutonomaFeriados(
            $conexion,
            function () use ($conexion, $datos, $ahora): array {
                return crearFeriadoEnTransaccion($conexion, $datos, $ahora);
            },
            'No se pudo crear el feriado.'
        );
    }
}

if (!function_exists('actualizarFeriadoEnTransaccion')) {
    /**
     * Requiere una transacción activa propiedad del consumidor.
     * No inicia, confirma ni revierte la transacción recibida.
     */
    function actualizarFeriadoEnTransaccion(
        mysqli $conexion,
        $idFeriado,
        array $datos,
        ?DateTimeImmutable $ahora = null
    ): array {
        $idFeriado = asegurarIdFeriadoValido($idFeriado);

        try {
            $actual = obtenerFeriadoPorId($conexion, $idFeriado, true);
            if ($actual === null) {
                throw new RuntimeException('El feriado solicitado no existe.', 404);
            }

            $cambio = validarModificacionFeriado($actual, $datos, $ahora);
            if (!$cambio['requiere_actualizacion']) {
                return $actual;
            }

            if ($cambio['cambia_fecha']) {
                $ocupante = obtenerFeriadoPorFecha($conexion, $cambio['fecha'], $idFeriado, true);
                if ($ocupante !== null) {
                    throw new RuntimeException('Ya existe un feriado para la fecha indicada.', 409);
                }
            }

            if ($cambio['es_historico']) {
                $consulta = prepararConsultaFeriados(
                    $conexion,
                    'UPDATE feriados
                     SET descripcion = ?
                     WHERE id_feriado = ?'
                );
                mysqli_stmt_bind_param($consulta, 'si', $cambio['descripcion'], $idFeriado);
            } else {
                $consulta = prepararConsultaFeriados(
                    $conexion,
                    'UPDATE feriados
                     SET fecha = ?, descripcion = ?
                     WHERE id_feriado = ?'
                );
                mysqli_stmt_bind_param(
                    $consulta,
                    'ssi',
                    $cambio['fecha'],
                    $cambio['descripcion'],
                    $idFeriado
                );
            }

            if (!mysqli_stmt_execute($consulta)) {
                $numeroError = mysqli_stmt_errno($consulta);
                mysqli_stmt_close($consulta);

                if ($numeroError === 1062) {
                    throw new RuntimeException('Ya existe un feriado para la fecha indicada.', 409);
                }

                throw new RuntimeException('No se pudo actualizar el feriado.', 500);
            }

            if (mysqli_stmt_affected_rows($consulta) !== 1) {
                mysqli_stmt_close($consulta);
                throw new RuntimeException('El feriado cambió durante la operación.', 409);
            }

            mysqli_stmt_close($consulta);
            $feriado = obtenerFeriadoPorId($conexion, $idFeriado, false);

            if ($feriado === null) {
                throw new RuntimeException('No se pudo recuperar el feriado actualizado.', 500);
            }

            return $feriado;
        } catch (Throwable $error) {
            throw normalizarErrorEscrituraFeriado($error, 'No se pudo actualizar el feriado.');
        }
    }
}

if (!function_exists('actualizarFeriado')) {
    /**
     * Uso autónomo: esta función es propietaria de su transacción.
     */
    function actualizarFeriado(
        mysqli $conexion,
        $idFeriado,
        array $datos,
        ?DateTimeImmutable $ahora = null
    ): array {
        return ejecutarOperacionAutonomaFeriados(
            $conexion,
            function () use ($conexion, $idFeriado, $datos, $ahora): array {
                return actualizarFeriadoEnTransaccion($conexion, $idFeriado, $datos, $ahora);
            },
            'No se pudo actualizar el feriado.'
        );
    }
}

if (!function_exists('cambiarEstadoFeriadoEnTransaccion')) {
    /**
     * Requiere una transacción activa propiedad del consumidor.
     * No inicia, confirma ni revierte la transacción recibida.
     */
    function cambiarEstadoFeriadoEnTransaccion(
        mysqli $conexion,
        $idFeriado,
        string $estadoDestino,
        ?DateTimeImmutable $ahora = null
    ): array {
        $idFeriado = asegurarIdFeriadoValido($idFeriado);
        $estadoDestino = normalizarEstadoFeriado($estadoDestino);

        if (!estadoFeriadoValido($estadoDestino)) {
            throw new RuntimeException('El estado de destino no es válido.', 422);
        }

        try {
            $actual = obtenerFeriadoPorId($conexion, $idFeriado, true);
            if ($actual === null) {
                throw new RuntimeException('El feriado solicitado no existe.', 404);
            }

            $cambio = validarCambioEstadoFeriado($actual, $estadoDestino, $ahora);
            if (!$cambio['requiere_actualizacion']) {
                return $actual;
            }

            if ($estadoDestino === 'enabled') {
                $ocupante = obtenerFeriadoPorFecha($conexion, $actual['fecha'], $idFeriado, true);
                if ($ocupante !== null) {
                    throw new RuntimeException(
                        'No se puede reactivar: ya existe otro feriado para la fecha indicada.',
                        409
                    );
                }
            }

            $estadoActual = $cambio['estado_actual'];
            $consulta = prepararConsultaFeriados(
                $conexion,
                'UPDATE feriados
                 SET estado = ?
                 WHERE id_feriado = ? AND estado = ?'
            );
            mysqli_stmt_bind_param($consulta, 'sis', $estadoDestino, $idFeriado, $estadoActual);

            if (!mysqli_stmt_execute($consulta)) {
                mysqli_stmt_close($consulta);
                throw new RuntimeException('No se pudo cambiar el estado del feriado.', 500);
            }

            if (mysqli_stmt_affected_rows($consulta) !== 1) {
                mysqli_stmt_close($consulta);
                throw new RuntimeException('El estado del feriado cambió durante la operación.', 409);
            }

            mysqli_stmt_close($consulta);
            $feriado = obtenerFeriadoPorId($conexion, $idFeriado, false);

            if ($feriado === null) {
                throw new RuntimeException('No se pudo recuperar el feriado actualizado.', 500);
            }

            return $feriado;
        } catch (Throwable $error) {
            throw normalizarErrorEscrituraFeriado(
                $error,
                'No se pudo cambiar el estado del feriado.'
            );
        }
    }
}

if (!function_exists('cambiarEstadoFeriado')) {
    /**
     * Uso autónomo: esta función es propietaria de su transacción.
     */
    function cambiarEstadoFeriado(
        mysqli $conexion,
        $idFeriado,
        string $estadoDestino,
        ?DateTimeImmutable $ahora = null
    ): array {
        return ejecutarOperacionAutonomaFeriados(
            $conexion,
            function () use ($conexion, $idFeriado, $estadoDestino, $ahora): array {
                return cambiarEstadoFeriadoEnTransaccion(
                    $conexion,
                    $idFeriado,
                    $estadoDestino,
                    $ahora
                );
            },
            'No se pudo cambiar el estado del feriado.'
        );
    }
}

if (!function_exists('desactivarFeriadoEnTransaccion')) {
    function desactivarFeriadoEnTransaccion(
        mysqli $conexion,
        $idFeriado,
        ?DateTimeImmutable $ahora = null
    ): array {
        return cambiarEstadoFeriadoEnTransaccion($conexion, $idFeriado, 'disabled', $ahora);
    }
}

if (!function_exists('reactivarFeriadoEnTransaccion')) {
    function reactivarFeriadoEnTransaccion(
        mysqli $conexion,
        $idFeriado,
        ?DateTimeImmutable $ahora = null
    ): array {
        return cambiarEstadoFeriadoEnTransaccion($conexion, $idFeriado, 'enabled', $ahora);
    }
}

if (!function_exists('desactivarFeriado')) {
    function desactivarFeriado(
        mysqli $conexion,
        $idFeriado,
        ?DateTimeImmutable $ahora = null
    ): array {
        return cambiarEstadoFeriado($conexion, $idFeriado, 'disabled', $ahora);
    }
}

if (!function_exists('reactivarFeriado')) {
    function reactivarFeriado(
        mysqli $conexion,
        $idFeriado,
        ?DateTimeImmutable $ahora = null
    ): array {
        return cambiarEstadoFeriado($conexion, $idFeriado, 'enabled', $ahora);
    }
}
