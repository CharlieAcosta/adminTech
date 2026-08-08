<?php
include_once '../04-modelo/conectDB.php';

function feriadosEstadoPermitido($estado) {
    return in_array($estado, ['enabled', 'disabled'], true);
}

function feriadosFechaValida($fecha) {
    if (!is_string($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return false;
    }

    $fechaObjeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    $errores = DateTimeImmutable::getLastErrors();

    return $fechaObjeto !== false
        && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))
        && $fechaObjeto->format('Y-m-d') === $fecha;
}

function feriadosDescripcionValida($descripcion) {
    $descripcion = trim((string)$descripcion);
    $longitud = function_exists('mb_strlen') ? mb_strlen($descripcion, 'UTF-8') : strlen($descripcion);

    return $descripcion !== '' && $longitud <= 255;
}

function feriadosNormalizarFila(array $fila) {
    return [
        'id_feriado' => (int)$fila['id_feriado'],
        'fecha' => (string)$fila['fecha'],
        'descripcion' => (string)$fila['descripcion'],
        'estado' => (string)$fila['estado'],
    ];
}

function modGetAllFeriados($filtro = 'todos') {
    $conexion = conectDB();
    $registros = [];

    if ($filtro !== 'todos') {
        $sql = "SELECT id_feriado, fecha, descripcion, estado FROM feriados WHERE estado = ? ORDER BY fecha DESC, id_feriado DESC";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, 's', $filtro);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
    } else {
        $sql = "SELECT id_feriado, fecha, descripcion, estado FROM feriados ORDER BY fecha DESC, id_feriado DESC";
        $resultado = mysqli_query($conexion, $sql);
    }

    if ($resultado) {
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $registros[] = feriadosNormalizarFila($fila);
        }
    }

    mysqli_close($conexion);

    return $registros;
}

function modGetFeriadoById($idFeriado) {
    $idFeriado = filter_var($idFeriado, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idFeriado === false) {
        return null;
    }

    $conexion = conectDB();
    $sql = "SELECT id_feriado, fecha, descripcion, estado FROM feriados WHERE id_feriado = ? LIMIT 1";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $idFeriado);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $fila = $resultado ? mysqli_fetch_assoc($resultado) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $fila ? feriadosNormalizarFila($fila) : null;
}

function modGetFeriadoByFecha($fecha, $idExcluir = null) {
    if (!feriadosFechaValida($fecha)) {
        return null;
    }

    $conexion = conectDB();
    if ($idExcluir !== null && $idExcluir !== '') {
        $idExcluir = (int)$idExcluir;
        $sql = "SELECT id_feriado, fecha, descripcion, estado FROM feriados WHERE fecha = ? AND id_feriado <> ? LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, 'si', $fecha, $idExcluir);
    } else {
        $sql = "SELECT id_feriado, fecha, descripcion, estado FROM feriados WHERE fecha = ? LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, 's', $fecha);
    }

    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $fila = $resultado ? mysqli_fetch_assoc($resultado) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return $fila ? feriadosNormalizarFila($fila) : null;
}

function modGuardarFeriado($idFeriado, $fecha, $descripcion) {
    $idFeriado = trim((string)$idFeriado);
    $fecha = trim((string)$fecha);
    $descripcion = trim((string)$descripcion);
    $esEdicion = $idFeriado !== '';

    if (!feriadosFechaValida($fecha)) {
        return ['ok' => false, 'mensaje' => 'La fecha es obligatoria y debe ser valida.'];
    }

    if (!feriadosDescripcionValida($descripcion)) {
        return ['ok' => false, 'mensaje' => 'La descripcion es obligatoria y no puede superar 255 caracteres.'];
    }

    $idFeriadoInt = null;
    if ($esEdicion) {
        $idFeriadoInt = filter_var($idFeriado, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($idFeriadoInt === false || modGetFeriadoById($idFeriadoInt) === null) {
            return ['ok' => false, 'mensaje' => 'El feriado solicitado no existe.'];
        }
    }

    $duplicado = modGetFeriadoByFecha($fecha, $idFeriadoInt);
    if ($duplicado !== null) {
        if (!$esEdicion && $duplicado['estado'] === 'disabled') {
            return ['ok' => false, 'mensaje' => 'La fecha ya existe como feriado desactivado. Edite o reactive el registro existente.'];
        }

        return ['ok' => false, 'mensaje' => 'Ya existe un feriado para esa fecha.'];
    }

    $conexion = conectDB();
    if ($esEdicion) {
        $sql = "UPDATE feriados SET fecha = ?, descripcion = ? WHERE id_feriado = ?";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, 'ssi', $fecha, $descripcion, $idFeriadoInt);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);

        return [
            'ok' => (bool)$ok,
            'mensaje' => $ok ? 'Feriado actualizado correctamente.' : 'No se pudo actualizar el feriado.',
            'id_feriado' => $idFeriadoInt,
        ];
    }

    $estadoInicial = 'enabled';
    $sql = "INSERT INTO feriados (fecha, descripcion, estado) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 'sss', $fecha, $descripcion, $estadoInicial);
    $ok = mysqli_stmt_execute($stmt);
    $idInsertado = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return [
        'ok' => (bool)$ok,
        'mensaje' => $ok ? 'Feriado creado correctamente.' : 'No se pudo crear el feriado.',
        'id_feriado' => $idInsertado,
    ];
}

function modCambiarEstadoFeriado($idFeriado, $estadoDestino) {
    $idFeriado = filter_var($idFeriado, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idFeriado === false || !feriadosEstadoPermitido($estadoDestino)) {
        return ['ok' => false, 'mensaje' => 'La solicitud de cambio de estado no es valida.'];
    }

    if (modGetFeriadoById($idFeriado) === null) {
        return ['ok' => false, 'mensaje' => 'El feriado solicitado no existe.'];
    }

    $conexion = conectDB();
    $sql = "UPDATE feriados SET estado = ? WHERE id_feriado = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $estadoDestino, $idFeriado);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    return [
        'ok' => (bool)$ok,
        'mensaje' => $ok ? 'Estado actualizado correctamente.' : 'No se pudo actualizar el estado.',
        'id_feriado' => $idFeriado,
        'estado' => $estadoDestino,
    ];
}
?>
