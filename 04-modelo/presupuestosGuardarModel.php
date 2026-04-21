<?php
session_start();

include_once '../06-funciones_php/funciones.php';
include_once '../06-funciones_php/cleanInput.php';
include_once '../04-modelo/presupuestoComercialLockModel.php';
include_once '../04-modelo/previsitaWorkflowModel.php';
include_once '../04-modelo/previsitaDocumentosModel.php';

if (isset($_POST['ajax']) && $_POST['ajax'] == 'on') {
    include_once '../04-modelo/conectDB.php';
    if (isset($_POST['accion']) && $_POST['accion'] == 'alta') {
        addPrevisita('ajax');
    } else {
        savePrevisita('ajax');
    }
}

function addPrevisita($metodo)
{
    $_POST = cleanInput($_POST);

    $campos = '';
    $valores = '';
    $errorFile = '';
    $resultado = false;
    $idPrevisita = 0;
    $documentosPrevisita = [];
    $idUsuario = (int)($_SESSION['usuario']['id_usuario'] ?? 0);

    foreach ($_POST as $key => $value) {
        if ($key === 'ajax' || $key === 'accion' || $key === 'edad' || $key === 'previsita_documentos_eliminados' || $value === '') {
            continue;
        }

        if ($key == 'razon_social') {
            $value = strtoupper($value);
        }
        if ($key == 'contacto_obra') {
            $value = ucwords(strtolower($value));
        }

        $campos .= $key . ', ';
        $valores .= "'" . $value . "', ";
    }

    $campos = rtrim($campos, ', ');
    $valores = rtrim($valores, ', ');
    $campos .= ', log_usuario_id, log_accion';
    $valores .= ", {$idUsuario}, '" . ($_POST['accion'] ?? 'alta') . "'";

    $db = conectDB();
    $db->set_charset('utf8mb4');

    $archivosNuevos = normalizarArchivosSubidosPrevisita($_FILES['doc_previsita'] ?? []);
    $documentosEliminados = decodificarDocumentosPrevisitaEliminados($_POST['previsita_documentos_eliminados'] ?? '');

    mysqli_begin_transaction($db);

    try {
        $query = "
            INSERT INTO previsitas ({$campos})
            VALUES ({$valores});
        ";
        $resultado = $db->query($query);

        if ($resultado !== true) {
            throw new RuntimeException('No se pudo guardar la pre-visita.');
        }

        $idPrevisita = (int)mysqli_insert_id($db);
        $documentosPrevisita = sincronizarDocumentosPrevisitaEnConexion(
            $db,
            $idPrevisita,
            $idUsuario,
            $archivosNuevos,
            $documentosEliminados
        );

        mysqli_commit($db);
    } catch (Throwable $e) {
        @mysqli_rollback($db);
        $resultado = false;
        $errorFile = $e->getMessage();
    }

    mysqli_close($db);

    if ($metodo != 'ajax') {
        return [
            'id_previsita' => $idPrevisita,
            'documentos_previsita' => $documentosPrevisita,
        ];
    }

    echo json_encode([
        'resultado' => $resultado,
        'error_file' => '',
        'msg' => $resultado ? '' : ($errorFile !== '' ? $errorFile : 'No se pudo guardar la pre-visita.'),
        'id_previsita' => $idPrevisita,
        'documentos_previsita' => $documentosPrevisita,
    ], JSON_UNESCAPED_UNICODE);
}

function savePrevisita($metodo)
{
    $errorFile = '';
    $_POST = cleanInput($_POST);

    $idPrevisita = isset($_POST['id_previsita']) ? (int)$_POST['id_previsita'] : 0;
    $soloDocumentos = isset($_POST['solo_documentos']) && (string)$_POST['solo_documentos'] === '1';
    $idUsuarioSesion = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
    $archivosNuevos = normalizarArchivosSubidosPrevisita($_FILES['doc_previsita'] ?? []);
    $documentosEliminados = decodificarDocumentosPrevisitaEliminados($_POST['previsita_documentos_eliminados'] ?? '');
    $bloqueoWorkflowPrevisita = obtenerBloqueoWorkflowPrevisitaPorId($idPrevisita);
    if (!empty($bloqueoWorkflowPrevisita['bloqueado']) && !$soloDocumentos) {
        $respuesta = [
            'resultado' => false,
            'error_file' => '',
            'msg' => $bloqueoWorkflowPrevisita['mensaje'] ?: mensajeBloqueoWorkflowPrevisita($bloqueoWorkflowPrevisita['estado'] ?? ''),
        ];

        if ($metodo != 'ajax') {
            return false;
        }

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        return;
    }

    $bloqueoEdicion = obtenerBloqueoEdicionComercialPresupuestoPorPrevisita($idPrevisita);
    if (!empty($bloqueoEdicion['bloqueado']) && !$soloDocumentos) {
        $respuesta = [
            'resultado' => false,
            'error_file' => '',
            'msg' => $bloqueoEdicion['mensaje'] ?: mensajeBloqueoEdicionComercialPresupuesto($bloqueoEdicion['estado'] ?? ''),
        ];

        if ($metodo != 'ajax') {
            return false;
        }

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        return;
    }

    if ($soloDocumentos) {
        if ($idPrevisita <= 0) {
            $respuesta = [
                'resultado' => false,
                'error_file' => '',
                'msg' => 'No se pudo identificar la pre-visita para actualizar documentos.',
            ];

            if ($metodo != 'ajax') {
                return false;
            }

            echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
            return;
        }

        $db = conectDB();
        $db->set_charset('utf8mb4');

        $resultado = false;
        $documentosPrevisita = [];
        $legacyData = [];

        if (columna_existe($db, 'previsitas', 'doc_previsita')) {
            $sqlLegacy = 'SELECT doc_previsita FROM previsitas WHERE id_previsita = ? LIMIT 1';
            $stmtLegacy = stmt_or_throw($db, $sqlLegacy);
            mysqli_stmt_bind_param($stmtLegacy, 'i', $idPrevisita);
            mysqli_stmt_execute($stmtLegacy);
            $resultLegacy = mysqli_stmt_get_result($stmtLegacy);
            $legacyData = $resultLegacy ? (mysqli_fetch_assoc($resultLegacy) ?: []) : [];
            mysqli_stmt_close($stmtLegacy);
        }

        mysqli_begin_transaction($db);

        try {
            $documentosPrevisita = sincronizarDocumentosPrevisitaEnConexion(
                $db,
                $idPrevisita,
                $idUsuarioSesion,
                $archivosNuevos,
                $documentosEliminados,
                $legacyData
            );

            mysqli_commit($db);
            $resultado = true;
        } catch (Throwable $e) {
            @mysqli_rollback($db);
            $resultado = false;
            $errorFile = $e->getMessage();
        }

        mysqli_close($db);

        if ($metodo != 'ajax') {
            return $resultado;
        }

        echo json_encode([
            'resultado' => $resultado,
            'error_file' => '',
            'msg' => $resultado ? '' : ($errorFile !== '' ? $errorFile : 'No se pudieron actualizar los documentos de la pre-visita.'),
            'id_previsita' => $idPrevisita,
            'documentos_previsita' => $documentosPrevisita,
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $camposValues = '';

    if (isset($_POST['provincia_visita'])) {
        if (!isset($_POST['partido_visita'])) {
            $_POST['partido_visita'] = null;
        }
        if (!isset($_POST['localidad_visita'])) {
            $_POST['localidad_visita'] = null;
        }
        if (!isset($_POST['calle_visita'])) {
            $_POST['calle_visita'] = null;
        }
    }

    $checks = ['induccion_visita', 'chaleco_visita', 'casco_visita', 'escalera_visita', 'arnes_visita', 'soga_visita', 'gafas_visita'];
    foreach ($checks as $check) {
        if (!array_key_exists($check, $_POST)) {
            $_POST[$check] = 'n';
        }
    }

    foreach ($_POST as $key => $value) {
        if ($key === 'ajax' || $key === 'accion' || $key === 'id_previsita' || $key === 'previsita_documentos_eliminados') {
            continue;
        }

        if ($key == 'razon_social') {
            $value = strToMayus($value);
        }
        if ($key == 'contacto_obra') {
            $value = strMayusMinus($value);
        }
        if ($key == 'email_contacto_obra') {
            $value = strToMinus($value);
        }

        $camposValues .= $key . " = '" . $value . "', ";
    }

    $camposValues = rtrim($camposValues, ', ');
    $camposValues .= ", log_usuario_id = '" . (int)($_SESSION['usuario']['id_usuario'] ?? 0) . "'";
    if (($_POST['accion'] ?? '') == 'eliminar') {
        $camposValues .= ", log_accion = 'eliminar'";
    }

    $db = conectDB();
    $db->set_charset('utf8mb4');

    $resultado = false;
    $documentosPrevisita = [];

    $legacyData = [];
    if (columna_existe($db, 'previsitas', 'doc_previsita')) {
        $sqlLegacy = 'SELECT doc_previsita FROM previsitas WHERE id_previsita = ? LIMIT 1';
        $stmtLegacy = stmt_or_throw($db, $sqlLegacy);
        mysqli_stmt_bind_param($stmtLegacy, 'i', $idPrevisita);
        mysqli_stmt_execute($stmtLegacy);
        $resultLegacy = mysqli_stmt_get_result($stmtLegacy);
        $legacyData = $resultLegacy ? (mysqli_fetch_assoc($resultLegacy) ?: []) : [];
        mysqli_stmt_close($stmtLegacy);
    }

    mysqli_begin_transaction($db);

    try {
        $query = "UPDATE previsitas AS p SET {$camposValues} WHERE p.id_previsita = {$idPrevisita};";
        $resultado = $db->query($query);
        if ($resultado !== true) {
            throw new RuntimeException('No se pudo guardar la pre-visita.');
        }

        $documentosPrevisita = sincronizarDocumentosPrevisitaEnConexion(
            $db,
            $idPrevisita,
            $idUsuarioSesion,
            $archivosNuevos,
            $documentosEliminados,
            $legacyData
        );

        mysqli_commit($db);
    } catch (Throwable $e) {
        @mysqli_rollback($db);
        $resultado = false;
        $errorFile = $e->getMessage();
    }

    mysqli_close($db);

    if ($metodo != 'ajax') {
        return $resultado;
    }

    echo json_encode([
        'resultado' => $resultado,
        'error_file' => '',
        'msg' => $resultado ? '' : ($errorFile !== '' ? $errorFile : 'No se pudo guardar la pre-visita.'),
        'id_previsita' => $idPrevisita,
        'documentos_previsita' => $documentosPrevisita,
    ], JSON_UNESCAPED_UNICODE);
}
