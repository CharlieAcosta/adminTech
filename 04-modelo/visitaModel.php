<?php

require_once __DIR__ . '/schemaIntrospectionModel.php';

function modGetTareasByVisitaId(int $id_visita, string $callType = 'php') {
    $db = conectDB();
    if (!$db) return false;
    $id_visita = intval($id_visita);
    $ordenMateriales = columna_existe($db, 'visita_tarea_material', 'orden')
        ? 'ORDER BY tm.orden ASC, tm.id ASC'
        : 'ORDER BY tm.id ASC';
    $ordenManoObra = columna_existe($db, 'visita_tarea_mano_obra', 'orden')
        ? 'ORDER BY mo.orden ASC, mo.id ASC'
        : 'ORDER BY mo.id ASC';

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
                 WHERE tm.id_tarea = $tid
                 $ordenMateriales";
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
        WHERE mo.id_tarea = $tid
        $ordenManoObra";
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

if (!function_exists('obtenerPayloadPresupuestoDesdeVisitaPersistidaEnConexion')) {
    function obtenerPayloadPresupuestoDesdeVisitaPersistidaEnConexion(mysqli $db, int $idPrevisita): array
    {
        if ($idPrevisita <= 0) {
            throw new RuntimeException('La pre-visita es invalida.', 400);
        }

        $ordenTareas = 'ORDER BY vt.id_tarea ASC';
        $ordenMateriales = columna_existe($db, 'visita_tarea_material', 'orden')
            ? 'ORDER BY vtm.orden ASC, vtm.id ASC'
            : 'ORDER BY vtm.id ASC';
        $ordenManoObra = columna_existe($db, 'visita_tarea_mano_obra', 'orden')
            ? 'ORDER BY vtmo.orden ASC, vtmo.id ASC'
            : 'ORDER BY vtmo.id ASC';

        $stmt = mysqli_prepare($db, "
            SELECT vt.id_tarea, vt.descripcion
            FROM visita_tarea AS vt
            WHERE vt.id_visita = ?
            {$ordenTareas}
        ");
        if (!$stmt) {
            throw new RuntimeException('No se pudo leer la Visita persistida.');
        }

        mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
        if (!mysqli_stmt_execute($stmt)) {
            $mensaje = mysqli_stmt_error($stmt) ?: mysqli_error($db);
            mysqli_stmt_close($stmt);
            throw new RuntimeException('No se pudo consultar la Visita persistida: ' . $mensaje);
        }

        $res = mysqli_stmt_get_result($stmt);
        $tareasBase = [];
        while ($row = $res ? mysqli_fetch_assoc($res) : null) {
            $tareasBase[] = $row;
        }
        mysqli_stmt_close($stmt);

        if (!$tareasBase) {
            throw new RuntimeException('La Visita no tiene tareas persistidas para generar Presupuesto.', 422);
        }

        $tareas = [];
        foreach ($tareasBase as $indice => $tareaBase) {
            $idTarea = (int)$tareaBase['id_tarea'];
            $descripcion = sanitizarHtmlDetalleTareaPresupuesto((string)($tareaBase['descripcion'] ?? ''));
            if ($descripcion === '') {
                throw new RuntimeException('Todas las tareas de la Visita deben tener descripcion para generar Presupuesto.', 422);
            }

            $materiales = [];
            $stmtMat = mysqli_prepare($db, "
                SELECT
                    vtm.id_material,
                    vtm.cantidad,
                    m.descripcion_corta,
                    m.producto,
                    m.unidad_venta,
                    m.contenido,
                    m.unidad_medida,
                    m.precio_unitario,
                    m.log_alta,
                    m.log_edicion
                FROM visita_tarea_material AS vtm
                INNER JOIN materiales AS m ON m.id_material = vtm.id_material
                WHERE vtm.id_tarea = ?
                {$ordenMateriales}
            ");
            if (!$stmtMat) {
                throw new RuntimeException('No se pudieron leer los materiales de la Visita.');
            }
            mysqli_stmt_bind_param($stmtMat, 'i', $idTarea);
            if (!mysqli_stmt_execute($stmtMat)) {
                $mensaje = mysqli_stmt_error($stmtMat) ?: mysqli_error($db);
                mysqli_stmt_close($stmtMat);
                throw new RuntimeException('No se pudieron consultar los materiales de la Visita: ' . $mensaje);
            }
            $resMat = mysqli_stmt_get_result($stmtMat);
            $ordenMaterial = 1;
            while ($mat = $resMat ? mysqli_fetch_assoc($resMat) : null) {
                $idMaterial = (int)($mat['id_material'] ?? 0);
                $cantidad = (float)($mat['cantidad'] ?? 0);
                if ($idMaterial <= 0 || $cantidad <= 0) {
                    throw new RuntimeException('Todas las tareas de la Visita deben tener materiales validos para generar Presupuesto.', 422);
                }

                $nombreMaterial = trim((string)($mat['descripcion_corta'] ?? ''));
                if ($nombreMaterial === '') {
                    $nombreMaterial = trim((string)($mat['producto'] ?? ''));
                }

                $materiales[] = [
                    'id_material' => $idMaterial,
                    'nombre' => $nombreMaterial,
                    'cantidad' => $cantidad,
                    'precio_unitario' => (string)($mat['precio_unitario'] ?? '0'),
                    'unidad_medida' => (string)($mat['unidad_medida'] ?? ''),
                    'unidad_venta' => (string)($mat['unidad_venta'] ?? ''),
                    'contenido' => (string)($mat['contenido'] ?? ''),
                    'log_alta' => $mat['log_alta'] ?? null,
                    'log_edicion' => $mat['log_edicion'] ?? null,
                    'porcentaje_extra' => 0,
                    'orden' => $ordenMaterial,
                ];
                $ordenMaterial++;
            }
            mysqli_stmt_close($stmtMat);

            if (!$materiales) {
                throw new RuntimeException('Todas las tareas de la Visita deben tener al menos un material para generar Presupuesto.', 422);
            }

            $manoObra = [];
            $stmtMo = mysqli_prepare($db, "
                SELECT
                    vtmo.id_jornal,
                    vtmo.cantidad,
                    vtmo.dias,
                    vtmo.observaciones,
                    tj.jornal_codigo,
                    tj.jornal_descripcion,
                    tj.jornal_valor,
                    COALESCE(tj.updated_at, tj.created_at) AS updated_at_origen
                FROM visita_tarea_mano_obra AS vtmo
                INNER JOIN tipo_jornales AS tj ON tj.jornal_id = vtmo.id_jornal
                WHERE vtmo.id_tarea = ?
                {$ordenManoObra}
            ");
            if (!$stmtMo) {
                throw new RuntimeException('No se pudo leer la mano de obra de la Visita.');
            }
            mysqli_stmt_bind_param($stmtMo, 'i', $idTarea);
            if (!mysqli_stmt_execute($stmtMo)) {
                $mensaje = mysqli_stmt_error($stmtMo) ?: mysqli_error($db);
                mysqli_stmt_close($stmtMo);
                throw new RuntimeException('No se pudo consultar la mano de obra de la Visita: ' . $mensaje);
            }
            $resMo = mysqli_stmt_get_result($stmtMo);
            $ordenMo = 1;
            while ($mo = $resMo ? mysqli_fetch_assoc($resMo) : null) {
                $idJornal = (int)($mo['id_jornal'] ?? 0);
                $cantidad = (float)($mo['cantidad'] ?? 0);
                $dias = (int)($mo['dias'] ?? 1);
                if ($dias <= 0) {
                    $dias = 1;
                }
                if ($idJornal <= 0 || $cantidad <= 0) {
                    throw new RuntimeException('Todas las tareas de la Visita deben tener mano de obra valida para generar Presupuesto.', 422);
                }

                $nombreJornal = trim((string)($mo['jornal_codigo'] ?? ''));
                $descripcionJornal = trim((string)($mo['jornal_descripcion'] ?? ''));
                $nombreJornal = trim($nombreJornal . ($nombreJornal !== '' && $descripcionJornal !== '' ? ' | ' : '') . $descripcionJornal);

                $manoObra[] = [
                    'id_jornal' => $idJornal,
                    'jornal_id' => $idJornal,
                    'nombre' => $nombreJornal,
                    'cantidad' => $cantidad,
                    'dias' => $dias,
                    'jornales' => $cantidad * $dias,
                    'observacion' => trim((string)($mo['observaciones'] ?? '')),
                    'jornal_valor' => (string)($mo['jornal_valor'] ?? '0'),
                    'updated_at' => $mo['updated_at_origen'] ?? null,
                    'porcentaje_extra' => 0,
                    'orden' => $ordenMo,
                ];
                $ordenMo++;
            }
            mysqli_stmt_close($stmtMo);

            if (!$manoObra) {
                throw new RuntimeException('Todas las tareas de la Visita deben tener al menos una mano de obra para generar Presupuesto.', 422);
            }

            $tareas[] = [
                'nro' => $indice + 1,
                'id_tarea' => $idTarea,
                'descripcion' => $descripcion,
                'incluir_en_total' => 1,
                'utilidad_materiales' => 30,
                'utilidad_mano_obra' => 100,
                'otros_materiales' => 0,
                'otros_mano_obra' => 0,
                'materiales' => $materiales,
                'mano_obra' => $manoObra,
            ];
        }

        return [
            'id_previsita' => $idPrevisita,
            'id_visita' => $idPrevisita,
            'tareas' => $tareas,
        ];
    }
}
