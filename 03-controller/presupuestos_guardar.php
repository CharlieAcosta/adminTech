<?php
// Evita rutas relativas frágiles tipo "03-controller../..."
$BASE = __DIR__;

// ../03-controller/presupuestos_guardar.php
header('Content-Type: application/json; charset=utf-8');

require_once $BASE . '/../04-modelo/presupuestoGeneradoModel.php';

try {
    // ---- Validaciones básicas
    if (($_POST['via'] ?? '') !== 'ajax') {
        throw new RuntimeException('Acceso inválido');
    }

    $funcion = $_POST['funcion'] ?? '';

    switch ($funcion) {
        case 'guardar_tarea_archivada':
            // --- Rama nueva: guardar tarea como plantilla ---
            $raw = $_POST['data_json'] ?? '';
            if ($raw === '') {
                echo json_encode(['ok' => false, 'msg' => 'Falta data_json'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                echo json_encode(['ok' => false, 'msg' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
    
            require_once $BASE . '/../04-modelo/tareasArchivadasGuardarModel.php';
            try {
                $res = guardarTareaArchivada($data);
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['ok' => false, 'msg' => 'Excepción', 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;
    
        case 'guardarPresupuesto':
            // >>>> de aquí para abajo sigue tu flujo existente de guardar presupuesto <<<<
            break;
    
        default:
            throw new RuntimeException('Función no soportada: ' . $funcion);
    }
    

    // ---- Payload JSON
    $payloadJson = $_POST['payload'] ?? '';
    if ($payloadJson === '') {
        throw new RuntimeException('Payload vacío');
    }

    $payload = json_decode($payloadJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Payload JSON inválido: ' . json_last_error_msg());
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
                // Caso single (menos común, pero válido)
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


    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
