<?php
// —————————————————————————————
//  guardar_visita.php
// —————————————————————————————

file_put_contents('../log/log_fotos.txt', print_r($_FILES, true)); // log inicial
ob_start();
file_put_contents('../log/log_fotos.txt', "--- NUEVO INGRESO ---\n", FILE_APPEND);
file_put_contents('../log/log_fotos.txt', "🟠 _POST:\n" . print_r($_POST, true), FILE_APPEND);
file_put_contents('../log/log_fotos.txt', "🔵 _FILES:\n" . print_r($_FILES, true), FILE_APPEND);

include_once '../04-modelo/conectDB.php';
$db = conectDB();
if (!$db) {
    ob_end_clean();
    echo json_encode(['status' => false, 'mensaje' => 'Fallo al conectar con la base de datos.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$response = ['status' => false, 'mensaje' => 'No se pudo procesar la solicitud.'];

/**
 * Elimina todas las fotos (DB + físicas) de una tarea dada.
 */
function eliminarFotosDeTarea($db, $tareaId) {
    // 1) Recoger rutas
    $rutas = [];
    $sql = "SELECT ruta_archivo FROM visita_tarea_foto WHERE id_tarea = ?";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $tareaId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $rutaArchivo);
    while (mysqli_stmt_fetch($stmt)) {
        if ($rutaArchivo) {
            $rutas[] = $rutaArchivo;
        }
    }
    mysqli_stmt_close($stmt);

    // 2) Borrar físicas
    foreach ($rutas as $r) {
        if (file_exists($r)) {
            unlink($r);
        }
    }
    // 3) Borrar registros
    $del = "DELETE FROM visita_tarea_foto WHERE id_tarea = ?";
    $stmt2 = mysqli_prepare($db, $del);
    mysqli_stmt_bind_param($stmt2, 'i', $tareaId);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
}

// Acepta tanto 'id_visita' como 'id_previsita'
if (isset($_POST['id_visita'])) {
    $id_visita = intval($_POST['id_visita']);
} elseif (isset($_POST['id_previsita'])) {
    $id_visita = intval($_POST['id_previsita']);
} else {
    echo json_encode(['status' => false, 'mensaje' => 'ID de visita no recibido.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tareas = $_POST['tareas'] ?? [];

    // ——————————————————————————————
    //  1) Borrar selectivamente tareas quitadas
    // ——————————————————————————————
    // IDs que vienen del formulario
    $idsForm = array_filter(array_map(function($t){
        return isset($t['id_tarea']) ? intval($t['id_tarea']) : null;
    }, $tareas));

    // IDs que hay en BD para esta visita
    $stmt = $db->prepare("SELECT id_tarea FROM visita_tarea WHERE id_visita = ?");
    $stmt->bind_param('i', $id_visita);
    $stmt->execute();
    $res = $stmt->get_result();
    $idsBD = array_column($res->fetch_all(MYSQLI_ASSOC), 'id_tarea');
    $stmt->close();

    // Calcular diferencias → borro solo las quitadas
    $idsABorrar = array_diff($idsBD, $idsForm);
    foreach ($idsABorrar as $tareaId) {
        eliminarFotosDeTarea($db, $tareaId);
        $db->query("DELETE FROM visita_tarea_material   WHERE id_tarea = $tareaId");
        $db->query("DELETE FROM visita_tarea_mano_obra WHERE id_tarea = $tareaId");
        $db->query("DELETE FROM visita_tarea           WHERE id_tarea = $tareaId");
    }

    // ——————————————————————————————
    //  2) Preparar carpeta de fotos
    // ——————————————————————————————
    $carpetaFotos = '../uploads/visitas/' . date('Ymd');
    if (!is_dir($carpetaFotos)) {
        mkdir($carpetaFotos, 0777, true);
    }

    // ——————————————————————————————
    //  3) Crear o actualizar cada tarea + sus materiales y mano de obra
    // ——————————————————————————————
    $idsTareas = [];
    foreach ($tareas as $i => $tarea) {
        $idExist = isset($tarea['id_tarea']) ? intval($tarea['id_tarea']) : null;
        $desc    = trim($tarea['descripcion'] ?? '');

        if ($idExist) {
            // Actualizar descripción
            $upd = $db->prepare("UPDATE visita_tarea SET descripcion = ? WHERE id_tarea = ?");
            $upd->bind_param('si', $desc, $idExist);
            $upd->execute();
            $upd->close();
            $tareaId = $idExist;
        } else {
            // Insertar nueva
            $ins = $db->prepare("INSERT INTO visita_tarea (id_visita, descripcion) VALUES (?, ?)");
            $ins->bind_param('is', $id_visita, $desc);
            $ins->execute();
            $tareaId = $db->insert_id;
            $ins->close();
        }

        $idsTareas[$i] = $tareaId;

        // Materiales → limpiar e insertar
        $db->query("DELETE FROM visita_tarea_material WHERE id_tarea = $tareaId");
        if (!empty($tarea['materiales'])) {
            foreach ($tarea['materiales'] as $j => $mat) {
                $mId = (int)$mat['id'];
                $cant= (int)$mat['cantidad'];
                $stm = $db->prepare(
                    "INSERT INTO visita_tarea_material (id_tarea,id_material,cantidad) VALUES (?,?,?)"
                );
                $stm->bind_param('iii', $tareaId, $mId, $cant);
                $stm->execute();
                $stm->close();
            }
        }

        // Mano de obra → limpiar e insertar
        $db->query("DELETE FROM visita_tarea_mano_obra WHERE id_tarea = $tareaId");
        if (!empty($tarea['mano_obra'])) {
            foreach ($tarea['mano_obra'] as $j => $mo) {
                $moId = (int)$mo['id'];
                $cant = (int)$mo['cantidad'];
                $obs  = trim((string)($mo['observacion'] ?? ''));
                $dias = isset($mo['dias']) ? (int)$mo['dias'] : 1;
                $stm = $db->prepare(
                    "INSERT INTO visita_tarea_mano_obra (id_tarea, id_jornal, cantidad, dias, observaciones) VALUES (?,?,?,?,?)"
                );
                $stm->bind_param('iiiis', $tareaId, $moId, $cant, $dias, $obs);               
                $stm->execute();
                $stm->close();
            }
        }
    }

    $response = ['status' => true, 'mensaje' => 'Visita guardada correctamente', 'ids_tareas' => $idsTareas];

    // ——————————————————————————————
    //  4) Eliminar fotos marcadas para borrar (post-tareas)
    // ——————————————————————————————
    foreach ($tareas as $i => $tarea) {
        if (!empty($tarea['fotos_eliminadas'])) {
            $tId = $idsTareas[$i] ?? null;
            if (!$tId) continue;
            foreach ($tarea['fotos_eliminadas'] as $nombre) {
                // Logging antes de borrar
                file_put_contents(
                    '../log/log_fotos.txt',
                    "🗑️ [DEBUG BORRADO] tarea={$tId} nombre_archivo={$nombre}\n",
                    FILE_APPEND
                );

                // Obtener ruta
                $stm = $db->prepare(
                    "SELECT ruta_archivo FROM visita_tarea_foto WHERE id_tarea = ? AND nombre_archivo = ?"
                );
                $stm->bind_param('is', $tId, $nombre);
                $stm->execute();
                $stm->bind_result($ruta);
                if ($stm->fetch()) {
                    if (file_exists($ruta)) {
                        unlink($ruta);
                        file_put_contents(
                            '../log/log_fotos.txt',
                            "   ✔️ Archivo físico borrado: {$ruta}\n",
                            FILE_APPEND
                        );
                    } else {
                        file_put_contents(
                            '../log/log_fotos.txt',
                            "   ⚠️ No existe el archivo físico: {$ruta}\n",
                            FILE_APPEND
                        );
                    }
                }
                $stm->close();

                // Borrar registro DB
                $stm2 = $db->prepare(
                    "DELETE FROM visita_tarea_foto WHERE id_tarea = ? AND nombre_archivo = ?"
                );
                $stm2->bind_param('is', $tId, $nombre);
                $stm2->execute();
                $stm2->close();

                file_put_contents(
                    '../log/log_fotos.txt',
                    "   ✔️ Registro DB borrado para archivo {$nombre}\n",
                    FILE_APPEND
                );
            }
        }
    }

    // ——————————————————————————————
    //  5) Procesar $_FILES → insertar fotos nuevas
    // ——————————————————————————————
    foreach ($_FILES as $key => $file) {
        if (preg_match('/^foto_tarea_(\d+)_(\d+)$/', $key, $m)) {
            $tareaIndex = (int)$m[1];
            $tareaId    = $idsTareas[$tareaIndex] ?? null;
            if (!$tareaId) continue;

            if (!is_uploaded_file($file['tmp_name'])) continue;

            $orig = $file['name'];
            $new  = uniqid('foto_') . '_' . basename($orig);
            $dst  = "$carpetaFotos/$new";

            if (move_uploaded_file($file['tmp_name'], $dst)) {
                $stm = $db->prepare(
                    "INSERT INTO visita_tarea_foto (id_tarea,nombre_archivo,ruta_archivo) VALUES (?,?,?)"
                );
                $stm->bind_param('iss', $tareaId, $orig, $dst);
                $stm->execute();
                $stm->close();
                file_put_contents('../log/log_fotos.txt', "✔️ Saved $new\n", FILE_APPEND);
            } else {
                file_put_contents('../log/log_fotos.txt', "❌ Move failed $orig\n", FILE_APPEND);
            }
        }
    }

} // fin POST

ob_end_clean();
echo json_encode($response);
exit;
