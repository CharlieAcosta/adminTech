<?php

function modGetTareasByVisitaId(int $id_visita, string $callType = 'php') {
    $db = conectDB();
    if (!$db) return false;
    $id_visita = intval($id_visita);

    // 1) Traigo todas las tareas
    $sqlT = "SELECT id_tarea, id_visita, descripcion
             FROM visita_tarea
             WHERE id_visita = $id_visita
             ORDER BY id_tarea";
    $resT = mysqli_query($db, $sqlT);
    if (!$resT) return false;

    $tareas = [];
    while ($rowT = mysqli_fetch_assoc($resT)) {
        $tarea = [
            'id_tarea'    => (int)$rowT['id_tarea'],
            'id_visita'   => (int)$rowT['id_visita'],
            'descripcion' => $rowT['descripcion'],
            'materiales'  => [],
            'mano_obra'   => [],
            'fotos'       => [],
        ];

        $tid = $tarea['id_tarea'];

        // 2) Materiales de la tarea
        $sqlM = "SELECT tm.id_material, tm.cantidad,
                        m.descripcion_corta, m.unidad_venta, m.contenido, m.unidad_medida
                 FROM visita_tarea_material tm
                 JOIN materiales m ON m.id_material = tm.id_material
                 WHERE tm.id_tarea = $tid";
        $resM = mysqli_query($db, $sqlM);
        if ($resM) {
            while ($rowM = mysqli_fetch_assoc($resM)) {
                $tarea['materiales'][] = [
                    'id_material'       => (int)$rowM['id_material'],
                    'cantidad'          => $rowM['cantidad'],
                    'descripcion_corta' => $rowM['descripcion_corta'],
                    'unidad_venta'      => $rowM['unidad_venta'],
                    'contenido'         => $rowM['contenido'],
                    'unidad_medida'     => $rowM['unidad_medida'],
                ];
            }
        }

        // 3) Mano de obra de la tarea
        $sqlJ = "SELECT mo.id_jornal, mo.cantidad, mo.dias, mo.observaciones,
        j.jornal_codigo, j.jornal_descripcion
        FROM visita_tarea_mano_obra mo
        JOIN tipo_jornales j ON j.jornal_id = mo.id_jornal
        WHERE mo.id_tarea = $tid";
        $resJ = mysqli_query($db, $sqlJ);
        if ($resJ) {
            while ($rowJ = mysqli_fetch_assoc($resJ)) {
                $tarea['mano_obra'][] = [
                    'id_jornal'          => (int)$rowJ['id_jornal'],
                    'cantidad'           => $rowJ['cantidad'],
                    'dias'               => (int)$rowJ['dias'],                   
                    'observaciones'      => $rowJ['observaciones'],
                    'jornal_codigo'      => $rowJ['jornal_codigo'],
                    'jornal_descripcion' => $rowJ['jornal_descripcion'],
                ];
            }
        }

        // 4) Fotos de la tarea
        $sqlF = "
          SELECT 
            f.id            AS id_foto,
            f.nombre_archivo,
            f.ruta_archivo
          FROM visita_tarea_foto f
          WHERE f.id_tarea = $tid
          ORDER BY f.id
        ";
        $resF = mysqli_query($db, $sqlF);
        if ($resF) {
            while ($rowF = mysqli_fetch_assoc($resF)) {
                $tarea['fotos'][] = [
                    'id_foto'        => (int)$rowF['id_foto'],
                    'nombre_archivo' => $rowF['nombre_archivo'],
                    'ruta_archivo'   => $rowF['ruta_archivo'],
                ];
            }
        }

        $tareas[] = $tarea;
    }

    return $callType === 'json'
        ? json_encode($tareas, JSON_UNESCAPED_UNICODE)
        : $tareas;
}
