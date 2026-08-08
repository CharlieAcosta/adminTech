<?php
include_once '../06-funciones_php/funciones.php';
include_once '../04-modelo/feriadosModel.php';

if (isset($_POST['ajax']) && $_POST['ajax'] === 'on') {
    header('Content-Type: application/json; charset=utf-8');

    $funcion = $_POST['funcion'] ?? '';
    if ($funcion === 'guardarFeriado') {
        echo json_encode(
            modGuardarFeriado(
                $_POST['id_feriado'] ?? '',
                $_POST['fecha'] ?? '',
                $_POST['descripcion'] ?? ''
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    if ($funcion === 'cambiarEstadoFeriado') {
        echo json_encode(
            modCambiarEstadoFeriado(
                $_POST['id_feriado'] ?? '',
                $_POST['estado'] ?? ''
            ),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    if ($funcion === 'poblarDatableAll') {
        echo json_encode(poblarDatableAll([], 'php', $_POST['filtro'] ?? 'todos'), JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['ok' => false, 'mensaje' => 'Accion no reconocida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

function feriadosTextoEstado($estado) {
    return $estado === 'enabled' ? 'Habilitado' : 'Deshabilitado';
}

function feriadosBadgeEstado($estado) {
    $texto = feriadosTextoEstado($estado);
    $clase = $estado === 'enabled' ? 'badge-success' : 'badge-secondary';
    $icono = $estado === 'enabled' ? 'fa-check-circle' : 'fa-ban';

    return '<span class="badge ' . $clase . '"><i class="fas ' . $icono . ' mr-1" aria-hidden="true"></i>'
        . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</span>';
}

function feriadosFormatoFecha($fecha) {
    $fechaObjeto = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$fecha);
    $errores = DateTimeImmutable::getLastErrors();
    if ($fechaObjeto === false || ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))) {
        return '-';
    }

    return $fechaObjeto->format('d/m/Y');
}

function poblarDatableAll($tds, $via, $filtro = 'todos') {
    $allRegistros = modGetAllFeriados($filtro);
    $filas = '';

    foreach ($allRegistros as $feriado) {
        $id = (int)$feriado['id_feriado'];
        $estado = (string)$feriado['estado'];
        $fecha = (string)$feriado['fecha'];
        $descripcion = (string)$feriado['descripcion'];
        $claseFila = $estado === 'disabled' ? ' class="text-muted"' : '';

        $filas .= '<tr' . $claseFila . ' data-id="' . $id . '" data-fecha="' . htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') . '" data-estado="' . htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') . '">';
        $filas .= '<td>' . $id . '</td>';
        $filas .= '<td data-order="' . htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(feriadosFormatoFecha($fecha), ENT_QUOTES, 'UTF-8') . '</td>';
        $filas .= '<td>' . htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') . '</td>';
        $filas .= '<td class="text-center" data-search="' . htmlspecialchars(feriadosTextoEstado($estado), ENT_QUOTES, 'UTF-8') . '">' . feriadosBadgeEstado($estado) . '</td>';
        $filas .= '<td class="text-center">';
        $filas .= '<i class="v-icon-accion p-1 fas fa-solid fa-eye" data-accion="visual" title="Visualizar"></i>';
        $filas .= '<i class="v-icon-accion p-1 fas fa-edit" data-accion="editar" title="Editar"></i>';
        if ($estado === 'enabled') {
            $filas .= '<i class="v-icon-accion text-warning p-1 fas fa-toggle-off" data-accion="desactivar" title="Desactivar"></i>';
        } else {
            $filas .= '<i class="v-icon-accion text-success p-1 fas fa-toggle-on" data-accion="reactivar" title="Reactivar"></i>';
        }
        $filas .= '</td>';
        $filas .= '</tr>';
    }

    return $filas;
}
?>
