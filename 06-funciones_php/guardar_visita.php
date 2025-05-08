<?php
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

function eliminarFotosDeTarea($db, $tareaId) {
    // Obtener rutas de imágenes a eliminar
    $rutas = [];
    $sql = "SELECT ruta_archivo FROM visita_tarea_foto WHERE id_tarea = ?";
    $stmt = mysqli_prepare($db, $sql);
file_put_contents('../log/log_fotos.txt', "➡️ Insertando tarea: visita=$id_visita, desc=$descripcion\n", FILE_APPEND);
    mysqli_stmt_bind_param($stmt, 'i', $tareaId);
    mysqli_stmt_execute($stmt);
if (mysqli_stmt_affected_rows($stmt) > 0) {
    file_put_contents('../log/log_fotos.txt', "✅ Tarea insertada con ID: " . $tareaId . "\n", FILE_APPEND);
} else {
    file_put_contents('../log/log_fotos.txt', "❌ No se insertó la tarea. Error: " . mysqli_error($db) . "\n", FILE_APPEND);
}    
    mysqli_stmt_bind_result($stmt, $rutaArchivo);
    while (mysqli_stmt_fetch($stmt)) {
        if (!empty($rutaArchivo)) {
            $rutas[] = $rutaArchivo;
        }
    }
    mysqli_stmt_close($stmt);

    // Eliminar archivos físicos
    foreach ($rutas as $ruta) {
        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }

    // Eliminar registros de la base
    $sqlDelete = "DELETE FROM visita_tarea_foto WHERE id_tarea = ?";
    $stmtDelete = mysqli_prepare($db, $sqlDelete);
    mysqli_stmt_bind_param($stmtDelete, 'i', $tareaId);
    mysqli_stmt_execute($stmtDelete);
    mysqli_stmt_close($stmtDelete);
}



$id_visita = isset($_POST['id_visita']) ? intval($_POST['id_visita']) : null;

if (!$id_visita) {
    echo json_encode(['status' => false, 'mensaje' => 'ID de visita no recibido.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tareas = $_POST['tareas'] ?? [];

    // 🔄 Eliminar tareas anteriores de esta visita
    $stmt = $db->prepare("SELECT id_tarea FROM visita_tarea WHERE id_visita = ?");
    if (!$stmt) {
        ob_end_clean();
        echo json_encode([
            'status' => false,
            'mensaje' => 'Error en prepare SELECT tareas: ' . $db->error
        ]);
        exit;
    }
    $stmt->bind_param("i", $id_visita);
    $stmt->execute();
    $result = $stmt->get_result();
    $tareasPrevias = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($tareasPrevias as $tarea) {
        $tareaId = $tarea['id_tarea'];

        file_put_contents('../log/log_fotos.txt', "🗑️ Eliminando tarea previa ID $tareaId y todas sus fotos asociadas\n", FILE_APPEND);

        // Eliminar fotos físicas y registros
        eliminarFotosDeTarea($db, $tareaId);

        // Eliminar materiales y mano de obra
        $db->query("DELETE FROM visita_tarea_material WHERE id_tarea = $tareaId");
        $db->query("DELETE FROM visita_tarea_mano_obra WHERE id_tarea = $tareaId");
    }

    // Finalmente eliminar tareas de esta visita
    $stmt = $db->prepare("DELETE FROM visita_tarea WHERE id_visita = ?");
    $stmt->bind_param("i", $id_visita);
    $stmt->execute();
    $stmt->close();

    $carpetaFotos = '../uploads/visitas/' . date('Ymd');
    if (!is_dir($carpetaFotos)) {
        mkdir($carpetaFotos, 0777, true);
    }

    $idsTareas = [];

    foreach ($tareas as $i => $tarea) {
        $idTareaExistente = isset($tarea['id_tarea']) ? intval($tarea['id_tarea']) : null;
        $descripcion = trim($tarea['descripcion'] ?? '');

            // INSERT nueva tarea
file_put_contents('../log/log_fotos.txt', "➡️ Insertando tarea: visita={$id_visita}, desc={$descripcion}\n", FILE_APPEND);
            $sql = "INSERT INTO visita_tarea (id_visita, descripcion) VALUES (?, ?)";
            $stmt = mysqli_prepare($db, $sql);
            if (!$stmt) {
                ob_end_clean();
                echo json_encode(['status' => false, 'mensaje' => 'Error SQL (insert tarea): ' . mysqli_error($db)]);
                exit;
            }
            mysqli_stmt_bind_param($stmt, 'is', $id_visita, $descripcion);
            mysqli_stmt_execute($stmt);
            $tareaId = mysqli_insert_id($db);
            mysqli_stmt_close($stmt);


        $idsTareas[$i] = $tareaId;

        // Insertar materiales
        if (!empty($tarea['materiales'])) {
            foreach ($tarea['materiales'] as $mat) {
                $id = (int)$mat['id'];
                $cantidad = (int)$mat['cantidad'];
                $sql = "INSERT INTO visita_tarea_material (id_tarea, id_material, cantidad) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($db, $sql);
                if (!$stmt) {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'mensaje' => 'Error SQL (insert material): ' . mysqli_error($db)]);
                    exit;
                }                
                mysqli_stmt_bind_param($stmt, 'iii', $tareaId, $id, $cantidad);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }

        // Insertar mano de obra
        if (!empty($tarea['mano_obra'])) {
            foreach ($tarea['mano_obra'] as $mo) {
                $id = (int)$mo['id'];
                $cantidad = (int)$mo['cantidad'];
                $obs = trim((string)($mo['observacion'] ?? ''));
                $sql = "INSERT INTO visita_tarea_mano_obra (id_tarea, id_jornal, cantidad, observaciones) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($db, $sql);
                if (!$stmt) {
                    ob_end_clean();
                    echo json_encode(['status' => false, 'mensaje' => 'Error SQL (insert mano de obra): ' . mysqli_error($db)]);
                    exit;
                }
                mysqli_stmt_bind_param($stmt, 'iiis', $tareaId, $id, $cantidad, $obs);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);



            }
        }

    }

    $response = ['status' => true, 'mensaje' => 'Visita guardada correctamente', 'ids_tareas' => $idsTareas];
    // 🔁 Eliminar fotos individuales luego de tener IDs correctos
    foreach ($tareas as $i => $tarea) {
        if (!empty($tarea['fotos_eliminadas'])) {
            foreach ($tarea['fotos_eliminadas'] as $nombreArchivo) {
                $tareaId = $idsTareas[$i] ?? null;
                if (!$tareaId) continue;

                // Obtener ruta del archivo
                $stmt = $db->prepare("SELECT ruta_archivo FROM visita_tarea_foto WHERE id_tarea = ? AND nombre_archivo = ?");
                $stmt->bind_param('is', $tareaId, $nombreArchivo);
                $stmt->execute();
                $stmt->bind_result($ruta);
                if ($stmt->fetch() && file_exists($ruta)) {
                    unlink($ruta);
                    file_put_contents('../log/log_fotos.txt', "🧹 Eliminada post-inserción: $nombreArchivo (ID: $tareaId)\n", FILE_APPEND);
                }
                $stmt->close();

                // Eliminar de la base
                $stmt = $db->prepare("DELETE FROM visita_tarea_foto WHERE id_tarea = ? AND nombre_archivo = ?");
                $stmt->bind_param('is', $tareaId, $nombreArchivo);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Eliminar fotos individuales si fueron marcadas para borrar
    if (!empty($tarea['fotos_eliminadas'])) {
        foreach ($tarea['fotos_eliminadas'] as $nombreArchivo) {
            // Obtener ruta de archivo a partir del nombre
            $stmt = $db->prepare("SELECT ruta_archivo FROM visita_tarea_foto WHERE id_tarea = ? AND nombre_archivo = ?");
            $stmt->bind_param('is', $tareaId, $nombreArchivo);
            $stmt->execute();
            $stmt->bind_result($ruta);
            if ($stmt->fetch() && file_exists($ruta)) {
                unlink($ruta); // Eliminar archivo físico
            }
            $stmt->close();
                // Eliminar registro de base
            $stmt = $db->prepare("DELETE FROM visita_tarea_foto WHERE id_tarea = ? AND nombre_archivo = ?");
            $stmt->bind_param('is', $tareaId, $nombreArchivo);
            $stmt->execute();
            $stmt->close();
                file_put_contents('../log/log_fotos.txt', "🗑️ Foto eliminada manualmente: $nombreArchivo\n", FILE_APPEND);
        }
    }

}

// Procesar fotos (fuera del foreach de tareas)
foreach ($_FILES as $clave => $archivo) {
    if (preg_match('/^foto_tarea_(\d+)_(\d+)$/', $clave, $matches)) {
        $tareaIndex = (int)$matches[1];
        $fotoIndex = (int)$matches[2];

        $tareaId = $idsTareas[$tareaIndex] ?? null;
        if (!$tareaId) {
            file_put_contents('../log/log_fotos.txt', "⚠️ No se encontró tarea para índice $tareaIndex\n", FILE_APPEND);
            continue;
        }

        file_put_contents('../log/log_fotos.txt', "📎 Asociando a tarea ID: $tareaId (índice $tareaIndex)\n", FILE_APPEND);

        if (!is_uploaded_file($archivo['tmp_name'])) {
            file_put_contents('../log/log_fotos.txt', "Archivo inválido en $clave\n", FILE_APPEND);
            continue;
        }

        $nombreOriginal = $archivo['name'];
        $nombreGuardado = uniqid('foto_') . '_' . basename($nombreOriginal);
        $rutaDestino = $carpetaFotos . '/' . $nombreGuardado;

        if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            $sql = "INSERT INTO visita_tarea_foto (id_tarea, nombre_archivo, ruta_archivo) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($db, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'iss', $tareaId, $nombreOriginal, $rutaDestino);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                file_put_contents('../log/log_fotos.txt', "✔️ Guardada: $nombreGuardado\n", FILE_APPEND);
            } else {
                file_put_contents('../log/log_fotos.txt', "❌ Error prepare SQL foto: " . mysqli_error($db) . "\n", FILE_APPEND);
            }
        } else {
            file_put_contents('../log/log_fotos.txt', "❌ Error al mover archivo: $nombreOriginal\n", FILE_APPEND);
        }
    }
}


ob_end_clean();
echo json_encode($response);
exit;
