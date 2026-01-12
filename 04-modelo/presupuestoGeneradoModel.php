<?php
// ../04-modelo/presupuestoGeneradoModel.php

require_once __DIR__ . '/conectDB.php';

/**
 * Guarda un presupuesto + materiales + MO + fotos.
 *
 * @param array $payload             Cabecera + tareas (como lo armás en JS).
 * @param array $archivosPorTarea    [nroTarea => [ [name,type,tmp_name,error,size], ... ]]
 * @param array $eliminadasPorTarea  [nroTarea => ['nombre1.jpg','nombre2.png',...]]
 *
 * @return array ['ok'=>bool, 'id_presupuesto'=>int, 'version'=>int, 'estado'=>string] | ['ok'=>false,'msg'=>...]
 */
function guardarPresupuesto(array $payload, array $archivosPorTarea = [], array $eliminadasPorTarea = []): array
{
    // Config de uploads (ajustá si querés)
    $ALLOWED_EXT = ['jpg','jpeg','png','webp','gif'];
    $MAX_SIZE    = 15 * 1024 * 1024; // 15 MB por archivo

    $db = conectDB();
    mysqli_begin_transaction($db);

    try {
        $id_previsita   = isset($payload['id_previsita']) ? (int)$payload['id_previsita'] : null;
        $id_visita      = isset($payload['id_visita']) ? (int)$payload['id_visita'] : null;
        $id_presupuesto = !empty($payload['id_presupuesto']) ? (int)$payload['id_presupuesto'] : null;
        $estado         = 'BORRADOR'; // unificamos a mayúsculas
        $moneda         = 'ARS';
        $version        = 1;

        if (!$id_previsita) {
            throw new RuntimeException('id_previsita es requerido');
        }

        // === INSERT o UPDATE cabecera (idempotente por (id_previsita, id_visita)) ===

            // Si no vino id_presupuesto, intentamos reutilizar uno existente para esta visita
            $stmt = mysqli_prepare($db, "
                SELECT id_presupuesto
                FROM presupuestos
                WHERE id_previsita = ?
                AND ( ? IS NULL OR id_visita = ? )
                AND UPPER(estado) IN ('BORRADOR','GENERADO')
                ORDER BY updated_at DESC, id_presupuesto DESC
                LIMIT 1
            ");

            // si id_visita es null, el predicado ( ? IS NULL OR id_visita = ? ) se cumple por el primer término
            mysqli_stmt_bind_param($stmt, "iii", $id_previsita, $id_visita, $id_visita);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('Error al buscar presupuesto previo: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
            }
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if ($row && !empty($row['id_presupuesto'])) {
                $id_presupuesto = (int)$row['id_presupuesto'];
            }


            if ($id_presupuesto === null) {
                // No había uno previo: insertamos cabecera nueva
                $stmt = mysqli_prepare($db, "
                    INSERT INTO presupuestos (id_previsita, id_visita, estado, moneda, version, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 1, NOW(), NOW())
                ");
                mysqli_stmt_bind_param($stmt, "iiss", $id_previsita, $id_visita, $estado, $moneda);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException('Error al insertar cabecera: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
                }
                $id_presupuesto = mysqli_insert_id($db);
                mysqli_stmt_close($stmt);
            } else {
                // Reutilizamos el existente: actualizamos cabecera (NO borrar hijos / NO borrar carpeta)
                $stmt = mysqli_prepare($db, "
                    UPDATE presupuestos
                    SET id_previsita = ?, id_visita = ?, estado = ?, updated_at = NOW()
                    WHERE id_presupuesto = ?
                ");
                mysqli_stmt_bind_param($stmt, "iisi", $id_previsita, $id_visita, $estado, $id_presupuesto);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException('Error al actualizar cabecera: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
                }
                mysqli_stmt_close($stmt);
            }
          
        // === Insertar tareas e hijos desde payload ===
        $tareasPayload = $payload['tareas'] ?? [];
        $total_mostrado_cab = 0.0;
        $total_base_cab     = 0.0;
        $impuestos_totales  = 0.0;
        $util_real_total    = 0.0;
        $porc_util_total    = null;

        foreach ($tareasPayload as $t) {
            $nro                = isset($t['nro']) ? (int)$t['nro'] : 0;
            $descripcion        = trim((string)($t['descripcion'] ?? ''));
            $incluir_en_total   = !empty($t['incluir_en_total']) ? 1 : 0;
            $util_mat_pct       = isset($t['utilidad_materiales']) ? (float)$t['utilidad_materiales'] : null;
            $util_mo_pct        = isset($t['utilidad_mano_obra']) ? (float)$t['utilidad_mano_obra'] : null;
            $otros_materiales   = isset($t['otros_materiales']) ? (float)$t['otros_materiales'] : 0.0;
            $otros_mano_obra    = isset($t['otros_mano_obra']) ? (float)$t['otros_mano_obra'] : 0.0;


        // Tarea (UPSERT por nro para mantener id_presu_tarea estable y preservar fotos)
        $id_presu_tarea = obtenerIdPresuTareaPorNro($db, $id_presupuesto, $nro);

        if ($id_presu_tarea) {
            // Update tarea existente
            $stmt = mysqli_prepare($db, "
                UPDATE presupuesto_tareas
                SET descripcion = ?,
                    incluir_en_total = ?,
                    utilidad_materiales_pct = ?,
                    utilidad_mano_obra_pct = ?,
                    otros_materiales_monto = ?,
                    otros_mano_obra_monto = ?,
                    updated_at = NOW()
                WHERE id_presu_tarea = ?
            ");
            mysqli_stmt_bind_param(
                $stmt,
                "siddddi",
                $descripcion,
                $incluir_en_total,
                $util_mat_pct,
                $util_mo_pct,
                $otros_materiales,
                $otros_mano_obra,
                $id_presu_tarea
            );
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('Error al actualizar tarea: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
            }
            mysqli_stmt_close($stmt);

            // Borramos SOLO detalle recalculable (materiales/MO). Fotos NO.
            borrarDetalleRecalculableDeTarea($db, $id_presu_tarea);

        } else {
            // Insert tarea nueva
            $stmt = mysqli_prepare($db, "
                INSERT INTO presupuesto_tareas
                (id_presupuesto, nro, descripcion, incluir_en_total,
                utilidad_materiales_pct, utilidad_mano_obra_pct,
                otros_materiales_monto, otros_mano_obra_monto,
                created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            mysqli_stmt_bind_param(
                $stmt,
                "iisidddd",
                $id_presupuesto, $nro, $descripcion, $incluir_en_total,
                $util_mat_pct, $util_mo_pct, $otros_materiales, $otros_mano_obra
            );
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('Error al insertar tarea: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
            }
            $id_presu_tarea = mysqli_insert_id($db);
            mysqli_stmt_close($stmt);
        }
        

            // === Materiales
            $suma_mat_filas = 0.0;
            $materiales = $t['materiales'] ?? [];
            foreach ($materiales as $m) {
                $id_material       = !empty($m['id_material']) ? (int)$m['id_material'] : null;
                $nombre_material   = trim((string)($m['nombre'] ?? ''));
                $cantidad          = isset($m['cantidad']) ? (float)$m['cantidad'] : 0.0;
                $precio_unitario   = isset($m['precio_unitario']) ? (float)$m['precio_unitario'] : 0.0;
                $porcentaje_extra  = isset($m['porcentaje_extra']) ? (float)$m['porcentaje_extra'] : 0.0;

                $subtotal_fila = ($cantidad * $precio_unitario);
                if ($porcentaje_extra != 0) {
                    $subtotal_fila *= (1 + ($porcentaje_extra / 100.0));
                }
                $suma_mat_filas += $subtotal_fila;

                $stmt = mysqli_prepare($db, "
                    INSERT INTO presupuesto_tarea_material
                    (id_presu_tarea, id_material, nombre_material, unidad_venta, unidad_medida,
                     cantidad, precio_unitario_usado, porcentaje_extra, subtotal_fila, log_alta, log_edicion,
                     created_at, updated_at)
                    VALUES (?, ?, ?, NULL, NULL, ?, ?, ?, ?, NULL, NULL, NOW(), NOW())
                ");
                mysqli_stmt_bind_param(
                    $stmt,
                    "iisdddd",
                    $id_presu_tarea, $id_material, $nombre_material,
                    $cantidad, $precio_unitario, $porcentaje_extra, $subtotal_fila
                );
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException('Error al insertar material: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
                }
                mysqli_stmt_close($stmt);
            }

            // === Mano de Obra
            $suma_mo_filas = 0.0;
            $mano_obra = $t['mano_obra'] ?? [];
            foreach ($mano_obra as $mo) {
                $jornal_id         = !empty($mo['jornal_id']) ? (int)$mo['jornal_id'] : null;
                $nombre_jornal     = trim((string)($mo['nombre'] ?? ''));
                $cantidad          = isset($mo['cantidad']) ? (float)$mo['cantidad'] : 0.0;
                $valor_jornal      = isset($mo['jornal_valor']) ? (float)$mo['jornal_valor'] : 0.0;
                $porcentaje_extra  = isset($mo['porcentaje_extra']) ? (float)$mo['porcentaje_extra'] : 0.0;
                $dias              = isset($mo['dias']) ? (int)$mo['dias'] : 1;
                $observacion       = isset($mo['observacion']) ? trim((string)$mo['observacion']) : null;

                $subtotal_fila = ($cantidad * $valor_jornal);
                if ($porcentaje_extra != 0) {
                    $subtotal_fila *= (1 + ($porcentaje_extra / 100.0));
                }
                $suma_mo_filas += $subtotal_fila;

                $stmt = mysqli_prepare($db, "
                    INSERT INTO presupuesto_tarea_mano_obra
                    (id_presu_tarea, id_jornal, nombre_jornal,
                     cantidad, dias, valor_jornal_usado, porcentaje_extra, observacion, subtotal_fila,
                     updated_at_origen, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NOW(), NOW())
                ");
                mysqli_stmt_bind_param(
                    $stmt,
                    "iisdiddsd",
                    $id_presu_tarea, $jornal_id, $nombre_jornal,
                    $cantidad, $dias, $valor_jornal, $porcentaje_extra, $observacion, $subtotal_fila
                );
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException('Error al insertar mano de obra: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
                }
                mysqli_stmt_close($stmt);
            }

            // === Totales contables por tarea
            $util_mat_contable = ($util_mat_pct !== null) ? ($suma_mat_filas * ($util_mat_pct / 100.0)) : 0.0;
            $util_mo_contable  = ($util_mo_pct  !== null) ? ($suma_mo_filas  * ($util_mo_pct  / 100.0)) : 0.0;

            $total_base_tarea   = ($suma_mat_filas + $suma_mo_filas + $util_mat_contable + $util_mo_contable);
            $total_mostrado_t   = $total_base_tarea + $otros_materiales + $otros_mano_obra;

            $stmt = mysqli_prepare($db, "
                UPDATE presupuesto_tareas
                SET suma_mat_filas = ?, suma_mo_filas = ?,
                    util_mat_contable = ?, util_mo_contable = ?,
                    total_base = ?, total_mostrado = ?,
                    porcentaje_utilidad = NULL,
                    iibb = NULL, ganancia35 = NULL, costo_inversion_3 = NULL, imp_cheque = NULL
                WHERE id_presu_tarea = ?
            ");
            mysqli_stmt_bind_param(
                $stmt,
                "ddddddi",
                $suma_mat_filas, $suma_mo_filas,
                $util_mat_contable, $util_mo_contable,
                $total_base_tarea, $total_mostrado_t,
                $id_presu_tarea
            );
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('Error al actualizar totales de tarea: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
            }
            mysqli_stmt_close($stmt);

            // =======================
            // === FOTOS por tarea ===
            // =======================

            // 1) Eliminar fotos marcadas (si las hay)
            $nombresAEliminar = $eliminadasPorTarea[$nro] ?? [];
            if ($nombresAEliminar) {
                foreach ($nombresAEliminar as $nombre) {
                    $nombre = (string)$nombre;
                    if ($nombre === '') { continue; }

                    // Buscar ruta actual para intentar borrar archivo físico
                    $ruta = null;
                    $q = mysqli_prepare($db, "
                        SELECT ruta_archivo FROM presupuesto_tarea_foto
                        WHERE id_presu_tarea = ? AND nombre_archivo = ?
                        LIMIT 1
                    ");
                    mysqli_stmt_bind_param($q, "is", $id_presu_tarea, $nombre);
                    mysqli_stmt_execute($q);
                    mysqli_stmt_bind_result($q, $ruta);
                    mysqli_stmt_fetch($q);
                    mysqli_stmt_close($q);

                    // Borrar registro
                    $del = mysqli_prepare($db, "
                        DELETE FROM presupuesto_tarea_foto
                        WHERE id_presu_tarea = ? AND nombre_archivo = ?
                    ");
                    mysqli_stmt_bind_param($del, "is", $id_presu_tarea, $nombre);
                    mysqli_stmt_execute($del);
                    mysqli_stmt_close($del);

                    // Intentar borrar físico
                    if ($ruta && is_file(normalizarRutaServidor($ruta))) {
                        @unlink(normalizarRutaServidor($ruta));
                    }
                }
            }

            // 2) Guardar nuevas subidas reales
            $bagArchivos = $archivosPorTarea[$nro] ?? [];
            if ($bagArchivos) {
                $dirBase  = rutaBaseFotosPresupuesto($id_presupuesto);
                $dirTarea = $dirBase . "t{$nro}/";
                asegurarDir($dirTarea);

                // Verificador MIME
                $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;

                foreach ($bagArchivos as $idx => $f) {
                    if (!isset($f['tmp_name']) || (int)$f['error'] !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    if ((int)($f['size'] ?? 0) > $MAX_SIZE) {
                        // descartamos silenciosamente archivos demasiado grandes
                        continue;
                    }

                    $ext   = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
                    if (!in_array($ext, $ALLOWED_EXT, true)) {
                        continue;
                    }

                    // Verificación MIME (best effort)
                    $okMime = true;
                    if ($finfo) {
                        $mime = @finfo_file($finfo, $f['tmp_name']);
                        if (!is_string($mime) || strpos($mime, 'image/') !== 0) {
                            $okMime = false;
                        }
                    }
                    if (!$okMime) {
                        continue;
                    }

                    $base  = pathinfo($f['name'] ?? '', PATHINFO_FILENAME);
                    $slug  = slugify($base ?: "foto");
                    $rand  = bin2hex(random_bytes(3));
                    $fname = "{$slug}_" . date('Ymd_His') . "_{$rand}." . $ext;

                    $destAbs = $dirTarea . $fname; // absoluta en servidor
                    if (!move_uploaded_file($f['tmp_name'], $destAbs)) {
                        // si falla, seguimos con el resto sin abortar toda la transacción
                        continue;
                    }

                    // Ruta “web/relativa” para servir en front
                    $rutaRel = rutaPublicaDesdeAbsoluta($destAbs);

                    // Evitar duplicados por nombre en la misma tarea
                    $del = mysqli_prepare($db, "
                        DELETE FROM presupuesto_tarea_foto
                        WHERE id_presu_tarea = ? AND nombre_archivo = ?
                    ");
                    mysqli_stmt_bind_param($del, "is", $id_presu_tarea, $fname);
                    mysqli_stmt_execute($del);
                    mysqli_stmt_close($del);

                    $stmt = mysqli_prepare($db, "
                        INSERT INTO presupuesto_tarea_foto
                        (id_presu_tarea, nombre_archivo, ruta_archivo, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    mysqli_stmt_bind_param($stmt, "iss", $id_presu_tarea, $fname, $rutaRel);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new RuntimeException('Error al insertar foto: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
                    }
                    mysqli_stmt_close($stmt);
                }

                if ($finfo) { finfo_close($finfo); }
            }

            // 3) Compat: si vinieron nombres “fotos” en payload (sin archivo real), registrar sin ruta
            if (empty($bagArchivos) && !empty($t['fotos']) && is_array($t['fotos'])) {
                $idxFoto = 0;
                foreach ($t['fotos'] as $f) {
                    $idxFoto++;
                    $nombre_archivo = '';
                    if (is_array($f)) {
                        $nombre_archivo = trim((string)($f['nombre'] ?? $f['name'] ?? $f['filename'] ?? ''));
                    } elseif (is_string($f)) {
                        $nombre_archivo = trim($f);
                    }
                    if ($nombre_archivo === '') {
                        $nombre_archivo = sprintf('t%d_img%d_%d.jpg', (int)$nro, (int)$idxFoto, time());
                    }

                    $stmt = mysqli_prepare($db, "
                        INSERT INTO presupuesto_tarea_foto
                        (id_presu_tarea, nombre_archivo, ruta_archivo, created_at)
                        VALUES (?, ?, '', NOW())
                    ");
                    mysqli_stmt_bind_param($stmt, "is", $id_presu_tarea, $nombre_archivo);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new RuntimeException('Error al insertar foto (compat): ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
                    }
                    mysqli_stmt_close($stmt);
                }
            }

            // Acumuladores cabecera
            if ($incluir_en_total) {
                $total_base_cab     += $total_base_tarea;
                $total_mostrado_cab += $total_mostrado_t;
            }
        }

        // === Actualizar cabecera con totales
        $stmt = mysqli_prepare($db, "
            UPDATE presupuestos
            SET total_base = ?, total_mostrado = ?, impuestos_totales = ?, util_real_total = ?, porcentaje_utilidad_total = ?
            WHERE id_presupuesto = ?
        ");
        mysqli_stmt_bind_param(
            $stmt,
            "dddddi",
            $total_base_cab, $total_mostrado_cab, $impuestos_totales, $util_real_total, $porc_util_total, $id_presupuesto
        );
        if (!mysqli_stmt_execute($stmt)) {
            throw new RuntimeException('Error al actualizar cabecera: ' . (mysqli_stmt_error($stmt) ?: mysqli_error($db)));
        }
        mysqli_stmt_close($stmt);

        mysqli_commit($db);

        return [
            'ok'             => true,
            'id_presupuesto' => $id_presupuesto,
            'version'        => $version,
            'estado'         => $estado
        ];
    } catch (Throwable $e) {
        mysqli_rollback($db);
        return ['ok' => false, 'msg' => $e->getMessage()];
    } finally {
        if ($db) { mysqli_close($db); }
    }
}

// === Helper: preparar statement o lanzar con el detalle real de MySQL ===
if (!function_exists('stmt_or_throw')) {
    function stmt_or_throw(mysqli $db, string $sql): mysqli_stmt {
        $stmt = mysqli_prepare($db, $sql);
        if ($stmt === false) {
            throw new RuntimeException('Fallo prepare(): ' . mysqli_error($db) . ' | SQL: ' . $sql);
        }
        return $stmt;
    }
}

// === Helper: bind_param dinámico (IN (...)) con referencias ===
if (!function_exists('bind_params_dynamic')) {
    function bind_params_dynamic(mysqli_stmt $stmt, string $types, array $params): bool {
        // armar array: [$stmt, $types, &p1, &p2, ...] con referencias
        $bind = [$stmt, $types];
        foreach ($params as $k => $v) { $bind[] = &$params[$k]; }
        return call_user_func_array('mysqli_stmt_bind_param', $bind);
    }
}

// === Helper: check si existe tabla (para fotos opcionales) ===
if (!function_exists('tabla_existe')) {
    function tabla_existe(mysqli $db, string $table): bool {
        $sql = "SHOW TABLES LIKE ?";
        $st  = mysqli_prepare($db, $sql);
        if (!$st) return false;
        mysqli_stmt_bind_param($st, "s", $table);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $ok = ($rs && mysqli_fetch_row($rs));
        mysqli_stmt_close($st);
        return (bool)$ok;
    }
}

/**
 * Obtiene el presupuesto más reciente de una previsita.
 *
 * @param int  $id_previsita
 * @param bool $incluirDetalle  Cuando true, agrega tareas + materiales + mano de obra + fotos
 * @return array ['ok'=>true, 'presupuesto'=>array|null, 'tareas'=>array] | ['ok'=>false,'msg'=>string]
 */
function obtenerPresupuestoPorPrevisita(int $id_previsita, bool $incluirDetalle = true): array
{
    $db = conectDB();
    if (!$db) {
        return ['ok' => false, 'msg' => 'No se pudo abrir conexión a la base de datos'];
    }
    mysqli_set_charset($db, 'utf8mb4');

    try {
        $sql = "
            SELECT *
            FROM `presupuestos`
            WHERE `id_previsita` = ?
            ORDER BY `version` DESC, `created_at` DESC, `id_presupuesto` DESC
            LIMIT 1
        ";
        $stmt = stmt_or_throw($db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_previsita);
        mysqli_stmt_execute($stmt);
        $res   = mysqli_stmt_get_result($stmt);
        $presu = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$presu) {
            mysqli_close($db);
            return ['ok' => true, 'presupuesto' => null, 'tareas' => []];
        }

        $tareas = $incluirDetalle ? _obtenerTareasConDetalle($db, (int)$presu['id_presupuesto']) : [];
        mysqli_close($db);

        return ['ok' => true, 'presupuesto' => $presu, 'tareas' => $tareas];

    } catch (Throwable $e) {
        mysqli_close($db);
        return ['ok' => false, 'msg' => $e->getMessage()];
    }
}



/**
 * Obtiene un presupuesto por id_presupuesto.
 *
 * @param int  $id_presupuesto
 * @param bool $incluirDetalle  Cuando true, agrega tareas + materiales + mano de obra + fotos
 * @return array ['ok'=>true, 'presupuesto'=>array|null, 'tareas'=>array] | ['ok'=>false,'msg'=>string]
 */
function obtenerPresupuestoPorId(int $id_presupuesto, bool $incluirDetalle = true): array
{
    $db = conectDB();
    if (!$db) {
        return ['ok' => false, 'msg' => 'No se pudo abrir conexión a la base de datos'];
    }
    mysqli_set_charset($db, 'utf8mb4');

    try {
        $sql = "SELECT * FROM `presupuestos` WHERE `id_presupuesto` = ? LIMIT 1";
        $stmt = stmt_or_throw($db, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_presupuesto);
        mysqli_stmt_execute($stmt);
        $res   = mysqli_stmt_get_result($stmt);
        $presu = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$presu) {
            mysqli_close($db);
            return ['ok' => true, 'presupuesto' => null, 'tareas' => []];
        }

        $tareas = $incluirDetalle ? _obtenerTareasConDetalle($db, (int)$presu['id_presupuesto']) : [];
        mysqli_close($db);

        return ['ok' => true, 'presupuesto' => $presu, 'tareas' => $tareas];

    } catch (Throwable $e) {
        mysqli_close($db);
        return ['ok' => false, 'msg' => $e->getMessage()];
    }
}



/**
 * PRIVADA: trae tareas con sus materiales, mano de obra y fotos (si existen).
 * - Menos roundtrips: materiales/MO/fotos se traen en batch usando IN (...)
 * - Devuelve array de tareas, cada una con keys: materiales[], mano_obra[], fotos[]
 *
 * @param mysqli $db
 * @param int    $id_presupuesto
 * @return array
 */
function _obtenerTareasConDetalle(mysqli $db, int $id_presupuesto): array
{
    // === TAREAS
    $stmtT = stmt_or_throw($db, "
        SELECT *
        FROM `presupuesto_tareas`
        WHERE `id_presupuesto` = ?
        ORDER BY `nro` ASC, `id_presu_tarea` ASC
    ");
    mysqli_stmt_bind_param($stmtT, "i", $id_presupuesto);
    mysqli_stmt_execute($stmtT);
    $resT = mysqli_stmt_get_result($stmtT);

    $tareas = [];
    while ($row = mysqli_fetch_assoc($resT)) {
        $tareas[] = $row;
    }
    mysqli_stmt_close($stmtT);

    if (!$tareas) {
        return [];
    }

    // IDs para IN(...)
    $ids = array_map(static fn($t) => (int)$t['id_presu_tarea'], $tareas);
    if (count($ids) === 0) {
        return $tareas;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types        = str_repeat('i', count($ids));

    // === MATERIALES
    $materialesPorTarea = [];
    $sqlMat = "SELECT * FROM `presupuesto_tarea_material` WHERE `id_presu_tarea` IN ($placeholders) ORDER BY `id_ptm` ASC";
    $stmtM  = stmt_or_throw($db, $sqlMat);
    bind_params_dynamic($stmtM, $types, $ids);
    mysqli_stmt_execute($stmtM);
    $resM = mysqli_stmt_get_result($stmtM);
    while ($row = mysqli_fetch_assoc($resM)) {
        $materialesPorTarea[(int)$row['id_presu_tarea']][] = $row;
    }
    mysqli_stmt_close($stmtM);

    // === MANO DE OBRA
    $moPorTarea = [];
    $sqlMO = "SELECT * FROM `presupuesto_tarea_mano_obra` WHERE `id_presu_tarea` IN ($placeholders) ORDER BY `id_ptmo` ASC";
    $stmtMO = stmt_or_throw($db, $sqlMO);
    bind_params_dynamic($stmtMO, $types, $ids);
    mysqli_stmt_execute($stmtMO);
    $resMO = mysqli_stmt_get_result($stmtMO);
    while ($row = mysqli_fetch_assoc($resMO)) {
        $moPorTarea[(int)$row['id_presu_tarea']][] = $row;
    }
    mysqli_stmt_close($stmtMO);

    // === FOTOS (mismo patrón que materiales/MO: IN(...) sobre los mismos $ids) ===
    $fotosPorTarea = [];
    // Si tu tabla se llama EXACTAMENTE 'presupuesto_tarea_foto', esto va a funcionar.
    // Si usás otro nombre, cambialo aquí.
    $sqlF = "SELECT * 
             FROM `presupuesto_tarea_foto` 
             WHERE `id_presu_tarea` IN ($placeholders)
             ORDER BY `id_presu_tarea` ASC";
    $stmtF = stmt_or_throw($db, $sqlF);
    bind_params_dynamic($stmtF, $types, $ids);
    mysqli_stmt_execute($stmtF);
    $resF = mysqli_stmt_get_result($stmtF);
    while ($row = mysqli_fetch_assoc($resF)) {
        $fotosPorTarea[(int)$row['id_presu_tarea']][] = $row;
    }
    mysqli_stmt_close($stmtF);



    // === MERGE
    foreach ($tareas as &$t) {
        $tid = (int)$t['id_presu_tarea'];
        $t['materiales'] = $materialesPorTarea[$tid] ?? [];
        $t['mano_obra']  = $moPorTarea[$tid] ?? [];
        $t['fotos']      = $fotosPorTarea[$tid] ?? [];
    }
    unset($t);

    return $tareas;
}



/**
 * Borra hijos (tareas + materiales + mano_obra + fotos) de un presupuesto.
 */
function borrarHijosPresupuesto(mysqli $db, int $id_presupuesto): void
{
    // 1) Obtener ids de tareas
    $ids = [];
    $rs = mysqli_query($db, "SELECT id_presu_tarea FROM presupuesto_tareas WHERE id_presupuesto = " . (int)$id_presupuesto);
    if ($rs) {
        while ($row = mysqli_fetch_assoc($rs)) {
            $ids[] = (int)$row['id_presu_tarea'];
        }
        mysqli_free_result($rs);
    }

    if (!$ids) return;

    $in = implode(',', $ids);

    // Borrar primero fotos (si quisieras, podrías también recorrer y unlink físico aquí)
    mysqli_query($db, "DELETE FROM presupuesto_tarea_foto     WHERE id_presu_tarea IN ($in)");
    mysqli_query($db, "DELETE FROM presupuesto_tarea_mano_obra WHERE id_presu_tarea IN ($in)");
    mysqli_query($db, "DELETE FROM presupuesto_tarea_material  WHERE id_presu_tarea IN ($in)");
    mysqli_query($db, "DELETE FROM presupuesto_tareas          WHERE id_presupuesto = " . (int)$id_presupuesto);
}

/**
 * Devuelve id_presu_tarea existente para (id_presupuesto, nro) o null si no existe.
 */
function obtenerIdPresuTareaPorNro(mysqli $db, int $id_presupuesto, int $nro): ?int
{
    $stmt = mysqli_prepare($db, "
        SELECT id_presu_tarea
        FROM presupuesto_tareas
        WHERE id_presupuesto = ? AND nro = ?
        LIMIT 1
    ");
    if (!$stmt) {
        throw new RuntimeException('Error prepare obtenerIdPresuTareaPorNro: ' . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, "ii", $id_presupuesto, $nro);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    return ($row && !empty($row['id_presu_tarea'])) ? (int)$row['id_presu_tarea'] : null;
}

/**
 * Borra SOLO el detalle recalculable de una tarea (materiales y mano de obra).
 * NO borra fotos (para preservarlas entre guardados).
 */
function borrarDetalleRecalculableDeTarea(mysqli $db, int $id_presu_tarea): void
{
    mysqli_query($db, "DELETE FROM presupuesto_tarea_mano_obra WHERE id_presu_tarea = " . (int)$id_presu_tarea);
    mysqli_query($db, "DELETE FROM presupuesto_tarea_material  WHERE id_presu_tarea = " . (int)$id_presu_tarea);
}


/* ============================
 * Helpers de archivos/rutas
 * ============================ */

/** Ruta base absoluta para fotos de un presupuesto. */
function rutaBaseFotosPresupuesto(int $id_presupuesto): string
{
    // __DIR__ = ../04-modelo
    $root = realpath(__DIR__ . '/..');                // raíz del proyecto (un nivel arriba de 04-modelo)
    if ($root === false) { $root = __DIR__ . '/..'; }
    $base = rtrim($root, '/\\') . '/uploads/presupuestos/' . (int)$id_presupuesto . '/';
    return $base;
}

/** Crea un directorio si no existe (recursivo). */
function asegurarDir(string $path): void
{
    if (!is_dir($path)) {
        $old = umask(0);
        @mkdir($path, 0775, true);
        umask($old);
    }
}

/** Slug simple para nombres de archivo (con fallback si iconv no está). */
function slugify(string $txt): string
{
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $txt);
        if ($converted !== false) { $txt = $converted; }
    }
    $txt = preg_replace('~[^\\pL\\d]+~u', '-', $txt);
    $txt = trim($txt, '-');
    $txt = preg_replace('~[^-a-z0-9]+~i', '', $txt);
    $txt = strtolower($txt);
    return $txt ?: 'archivo';
}

/** Convierte ruta absoluta del server a ruta “pública” relativa (ajustá a tu estructura). */
function rutaPublicaDesdeAbsoluta(string $abs): string
{
    $abs = str_replace('\\', '/', $abs);
    $pos = strpos($abs, '/uploads/');
    if ($pos !== false) {
        return ltrim(substr($abs, $pos), '/'); // "uploads/..."
    }
    // Fallback
    return basename($abs);
}

/** Normaliza ruta relativa (tipo "uploads/...") a absoluta en disco. */
function normalizarRutaServidor(string $rutaRel): string
{
    $rutaRel = str_replace('\\', '/', $rutaRel);
    if (preg_match('~^/?uploads/~', $rutaRel)) {
        $root = realpath(__DIR__ . '/..');
        if ($root === false) { $root = __DIR__ . '/..'; }
        return rtrim($root, '/\\') . '/' . ltrim($rutaRel, '/');
    }
    // Si ya parece absoluta, devolver tal cual
    return $rutaRel;
}

/** (Opcional) Eliminar carpeta recursiva. */
function eliminarDirectorioRecursivo(string $dir): void
{
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') continue;
        $p = $dir . '/' . $it;
        if (is_dir($p)) eliminarDirectorioRecursivo($p);
        else @unlink($p);
    }
    @rmdir($dir);
}




