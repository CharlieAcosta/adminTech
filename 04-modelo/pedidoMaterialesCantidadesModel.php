<?php

// P109/P111: integridad de cantidades de Pedido de materiales y clasificacion
// entero/decimal por unidad_venta. Este archivo es la UNICA fuente de verdad de
// la clasificacion (el frontend la consume via data-attributes renderizados por
// PHP, nunca la reimplementa).
//
// P111 — cambio de arquitectura respecto de P109: el snapshot
// (pedido_materiales_snapshot_detalles, InnoDB) pasa a ser la UNICA fuente de
// verdad de las cantidades activas de Pedido. Ya NO se inserta un delta en
// materiales_visita (MyISAM) por cada edicion: esa doble escritura duplicaba
// informacion del snapshot, no podia protegerse con una transaccion real (no
// hay atomicidad entre MyISAM e InnoDB) y nunca se aplicaba de vuelta cuando
// una autorizacion de excedente se aprobaba (P110, Problema B). La
// coordinacion de escrituras concurrentes se resuelve con SELECT...FOR UPDATE
// real sobre las dos tablas InnoDB del snapshot (cabecera y detalle), no con
// GET_LOCK.

require_once __DIR__ . '/schemaIntrospectionModel.php';
require_once __DIR__ . '/pedidoMaterialesSnapshotModel.php';
require_once __DIR__ . '/pedidoMaterialesAutorizacionesModel.php';

if (!function_exists('unidadesVentaPedidoMaterialesQueAdmitenDecimales')) {
    function unidadesVentaPedidoMaterialesQueAdmitenDecimales(): array
    {
        // Regla funcional aprobada por Charlie (P109/P111). No inferida ni inventada.
        return ['Bolsa', 'Unidad', 'Kilogramo', 'Metro', 'Metro²', 'Hora', 'Día'];
    }
}

if (!function_exists('precisionMaximaDecimalesPedidoMateriales')) {
    function precisionMaximaDecimalesPedidoMateriales(): int
    {
        return 2;
    }
}

if (!function_exists('unidadVentaPedidoMaterialesAdmiteDecimales')) {
    function unidadVentaPedidoMaterialesAdmiteDecimales(?string $unidadVenta): bool
    {
        $unidadVenta = trim((string)$unidadVenta);
        if ($unidadVenta === '') {
            // Unidad desconocida/vacia: se trata como indivisible (mas restrictivo,
            // nunca se asume que admite fracciones sin saberlo con certeza).
            return false;
        }

        return in_array($unidadVenta, unidadesVentaPedidoMaterialesQueAdmitenDecimales(), true);
    }
}

if (!function_exists('obtenerUnidadVentaMaterialPedidoEnConexion')) {
    function obtenerUnidadVentaMaterialPedidoEnConexion(
        mysqli $db,
        int $idPrevisita,
        string $tipoFila,
        int $idMaterial,
        ?int $tareaNro
    ): ?string {
        if ($idPrevisita <= 0 || $idMaterial <= 0) {
            return null;
        }

        if ($tipoFila === 'presupuestado') {
            // Unidad CONGELADA al momento de presupuestar: preserva la unidad
            // registrada en la operacion historica, aunque el catalogo vigente
            // haya cambiado despues.
            $sql = "
                SELECT ptm.unidad_venta
                FROM presupuesto_tarea_material ptm
                INNER JOIN presupuesto_tareas pt ON pt.id_presu_tarea = ptm.id_presu_tarea
                INNER JOIN presupuestos p ON p.id_presupuesto = pt.id_presupuesto
                WHERE p.id_previsita = ?
                  AND ptm.id_material = ?
                  AND (? IS NULL OR pt.nro = ?)
                ORDER BY p.version DESC, p.id_presupuesto DESC, ptm.id_ptm DESC
                LIMIT 1
            ";
            $stmt = mysqli_prepare($db, $sql);
            if (!$stmt) {
                return null;
            }
            mysqli_stmt_bind_param($stmt, 'iiii', $idPrevisita, $idMaterial, $tareaNro, $tareaNro);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if ($row && trim((string)($row['unidad_venta'] ?? '')) !== '') {
                return trim((string)$row['unidad_venta']);
            }

            // presupuesto_tarea_material.unidad_venta queda NULL siempre que el
            // presupuesto se genero/edito con el codigo actual (verificado en
            // P110: guardarPresupuestoEnConexion() escribe NULL en esa columna
            // de forma incondicional, tanto en generacion automatica como en
            // guardado manual). No es un caso de "unidad congelada distinta de
            // la vigente": no existe ninguna unidad congelada real hoy. Se cae,
            // a continuacion, al mismo resguardo que usan los materiales
            // agregados (unidad VIGENTE del catalogo), tal como quedo
            // explicitamente autorizado en P111 punto 2. No se modifica en esta
            // tarea la generacion de presupuestos para completar esa columna.
        }

        // 'agregado', o 'presupuestado' sin unidad congelada: unidad VIGENTE del catalogo.
        $sql = "SELECT unidad_venta FROM materiales WHERE id_material = ? LIMIT 1";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return null;
        }
        mysqli_stmt_bind_param($stmt, 'i', $idMaterial);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            return null;
        }

        $unidad = trim((string)($row['unidad_venta'] ?? ''));
        return $unidad !== '' ? $unidad : null;
    }
}

if (!function_exists('normalizarTextoCantidadMovimientoPedidoMateriales')) {
    // Normaliza el separador decimal (',' o '.') SIN redondear ni truncar.
    // Devuelve null si el texto no representa un numero valido.
    function normalizarTextoCantidadMovimientoPedidoMateriales($valorCrudo): ?array
    {
        $texto = trim((string)$valorCrudo);
        if ($texto === '') {
            return null;
        }

        // Un unico separador admitido a la vez: coma decimal o punto decimal.
        // No se intenta adivinar miles agrupados (evita ambiguedad silenciosa).
        if (strpos($texto, ',') !== false && strpos($texto, '.') !== false) {
            return null;
        }

        $textoNormalizado = str_replace(',', '.', $texto);

        if (!preg_match('/^-?\d+(\.\d+)?$/', $textoNormalizado)) {
            return null;
        }

        $partes = explode('.', $textoNormalizado);
        $decimales = isset($partes[1]) ? strlen($partes[1]) : 0;

        return [
            'valor' => (float)$textoNormalizado,
            'decimales' => $decimales,
            'texto_normalizado' => $textoNormalizado,
        ];
    }
}

if (!function_exists('validarCantidadMovimientoPedidoMateriales')) {
    // Valida tipo + precision segun si la unidad admite decimales.
    // Devuelve ['ok'=>true,'valor'=>float] o ['ok'=>false,'error'=>string].
    // NUNCA redondea ni trunca el valor recibido: lo acepta tal cual o lo rechaza.
    function validarCantidadMovimientoPedidoMateriales($valorCrudo, bool $admiteDecimales): array
    {
        $parseado = normalizarTextoCantidadMovimientoPedidoMateriales($valorCrudo);
        if ($parseado === null) {
            return ['ok' => false, 'error' => 'La cantidad ingresada no es un numero valido.'];
        }

        if ($parseado['valor'] < 0) {
            return ['ok' => false, 'error' => 'La cantidad ingresada no puede ser negativa.'];
        }

        if (!$admiteDecimales && $parseado['decimales'] > 0) {
            return ['ok' => false, 'error' => 'Esta unidad de venta exige cantidades enteras.'];
        }

        if ($admiteDecimales && $parseado['decimales'] > precisionMaximaDecimalesPedidoMateriales()) {
            return [
                'ok' => false,
                'error' => 'La cantidad admite hasta ' . precisionMaximaDecimalesPedidoMateriales() . ' decimales.',
            ];
        }

        return ['ok' => true, 'valor' => round($parseado['valor'], precisionMaximaDecimalesPedidoMateriales())];
    }
}

if (!function_exists('localizarFilaSnapshotPedidoMateriales')) {
    function localizarFilaSnapshotPedidoMateriales(
        array $snapshot,
        string $tipoFila,
        int $idMaterial,
        ?int $tareaNro,
        int $ordenVisual
    ): ?array {
        $clave = $tipoFila === 'agregado' ? 'materiales_agregados' : 'materiales_presupuestados';
        foreach ((array)($snapshot[$clave] ?? []) as $fila) {
            $filaTareaNro = isset($fila['tarea_nro']) ? (int)$fila['tarea_nro'] : null;
            $coincideTarea = $tipoFila === 'agregado'
                ? true
                : ($filaTareaNro === $tareaNro);
            if (
                (int)($fila['id_material'] ?? 0) === $idMaterial
                && (int)($fila['orden_visual'] ?? 0) === $ordenVisual
                && $coincideTarea
            ) {
                return $fila;
            }
        }

        return null;
    }
}

if (!function_exists('obtenerCantidadInicialMaterialPresupuestadoEnConexion')) {
    // Cantidad presupuestada real (fuente: presupuesto_tarea_material.cantidad,
    // ultima version del presupuesto), usada UNICAMENTE para inicializar en
    // servidor una fila de snapshot que todavia no existe (bootstrap de la
    // primera edicion de una previsita sin snapshot previo). Nunca se confia en
    // una cantidad_inicial declarada por el cliente para crear la fila.
    function obtenerCantidadInicialMaterialPresupuestadoEnConexion(
        mysqli $db,
        int $idPrevisita,
        int $idMaterial,
        ?int $tareaNro
    ): ?array {
        $sql = "
            SELECT ptm.cantidad, ptm.nombre_material, pt.descripcion AS tarea_titulo, pt.id_presu_tarea AS id_tarea
            FROM presupuesto_tarea_material ptm
            INNER JOIN presupuesto_tareas pt ON pt.id_presu_tarea = ptm.id_presu_tarea
            INNER JOIN presupuestos p ON p.id_presupuesto = pt.id_presupuesto
            WHERE p.id_previsita = ?
              AND ptm.id_material = ?
              AND (? IS NULL OR pt.nro = ?)
            ORDER BY p.version DESC, p.id_presupuesto DESC, ptm.id_ptm DESC
            LIMIT 1
        ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return null;
        }
        mysqli_stmt_bind_param($stmt, 'iiii', $idPrevisita, $idMaterial, $tareaNro, $tareaNro);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            return null;
        }

        return [
            'cantidad_inicial' => round((float)($row['cantidad'] ?? 0), 4),
            'material_texto' => trim((string)($row['nombre_material'] ?? '')),
            'tarea_titulo' => trim((string)($row['tarea_titulo'] ?? '')),
            'id_tarea' => (int)($row['id_tarea'] ?? 0),
        ];
    }
}

if (!function_exists('obtenerNombreMaterialCatalogoEnConexion')) {
    function obtenerNombreMaterialCatalogoEnConexion(mysqli $db, int $idMaterial): ?string
    {
        $stmt = mysqli_prepare($db, 'SELECT producto FROM materiales WHERE id_material = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        mysqli_stmt_bind_param($stmt, 'i', $idMaterial);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            return null;
        }

        $nombre = trim((string)($row['producto'] ?? ''));
        return $nombre !== '' ? $nombre : null;
    }
}

if (!function_exists('obtenerOCrearSnapshotHeaderParaEdicionEnConexion')) {
    // Bloquea (FOR UPDATE, InnoDB real) la cabecera del snapshot de la
    // previsita. Si todavia no existe ninguna (previsita que nunca guardo un
    // snapshot de Pedido), la crea fresca en pedido_activo=1 dentro de la MISMA
    // transaccion — es el "comportamiento de inicializacion compatible" pedido
    // en P111 punto 3: no se reconstruye nada desde materiales_visita (registro
    // legacy), se arranca limpio.
    function obtenerOCrearSnapshotHeaderParaEdicionEnConexion(mysqli $db, int $idPrevisita, int $idUsuario): array
    {
        $sql = 'SELECT * FROM pedido_materiales_snapshots WHERE id_previsita = ? LIMIT 1 FOR UPDATE';
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            throw new RuntimeException('No se pudo bloquear la cabecera del snapshot.', 500);
        }
        mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if ($row) {
            return $row;
        }

        $stmtInsert = mysqli_prepare(
            $db,
            "INSERT INTO pedido_materiales_snapshots
                (id_previsita, pedido_activo, pedido_maximo_visible, finalizado, accion_guardado, id_usuario_guardado, created_at, updated_at)
             VALUES (?, 1, 1, 0, 'guardar', ?, NOW(), NOW())"
        );
        if (!$stmtInsert) {
            throw new RuntimeException('No se pudo inicializar el snapshot de Pedido de materiales.', 500);
        }
        mysqli_stmt_bind_param($stmtInsert, 'ii', $idPrevisita, $idUsuario);
        if (!mysqli_stmt_execute($stmtInsert)) {
            mysqli_stmt_close($stmtInsert);
            throw new RuntimeException('No se pudo inicializar el snapshot de Pedido de materiales.', 500);
        }
        mysqli_stmt_close($stmtInsert);

        // Re-lectura FOR UPDATE de la fila recien creada: sigue dentro de la
        // misma transaccion, por lo que el lock se mantiene.
        $stmt2 = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt2, 'i', $idPrevisita);
        mysqli_stmt_execute($stmt2);
        $res2 = mysqli_stmt_get_result($stmt2);
        $row2 = $res2 ? mysqli_fetch_assoc($res2) : null;
        mysqli_stmt_close($stmt2);

        if (!$row2) {
            throw new RuntimeException('No se pudo confirmar la inicializacion del snapshot.', 500);
        }

        return $row2;
    }
}

if (!function_exists('obtenerOCrearFilaSnapshotDetalleEnConexion')) {
    // Bloquea (FOR UPDATE) la fila de detalle exacta. Si no existe, la crea:
    // - 'presupuestado': cantidad_inicial se resuelve SIEMPRE en servidor desde
    //   presupuesto_tarea_material (nunca se acepta la que declare el cliente).
    // - 'agregado': cantidad_inicial es 0 (no tiene presupuesto de referencia),
    //   material_texto se resuelve del catalogo real.
    function obtenerOCrearFilaSnapshotDetalleEnConexion(
        mysqli $db,
        int $idSnapshot,
        int $idPrevisita,
        string $tipoFila,
        int $idMaterial,
        ?int $tareaNro,
        int $ordenVisual,
        int $idUsuario
    ): array {
        $tareaNroConsulta = $tareaNro ?? 0;
        $sql = "
            SELECT * FROM pedido_materiales_snapshot_detalles
            WHERE id_pedido_materiales_snapshot = ?
              AND tipo_fila = ?
              AND id_material = ?
              AND COALESCE(tarea_nro, 0) = ?
              AND orden_visual = ?
            LIMIT 1
            FOR UPDATE
        ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            throw new RuntimeException('No se pudo bloquear la fila del snapshot.', 500);
        }
        mysqli_stmt_bind_param($stmt, 'isiii', $idSnapshot, $tipoFila, $idMaterial, $tareaNroConsulta, $ordenVisual);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if ($row) {
            return $row;
        }

        if ($tipoFila === 'presupuestado') {
            $referencia = obtenerCantidadInicialMaterialPresupuestadoEnConexion($db, $idPrevisita, $idMaterial, $tareaNro);
            if ($referencia === null) {
                throw new RuntimeException('El material informado no pertenece al presupuesto de esta previsita.', 404);
            }
            $cantidadInicial = $referencia['cantidad_inicial'];
            $materialTexto = $referencia['material_texto'] !== '' ? $referencia['material_texto'] : ('Material #' . $idMaterial);
            $tareaTitulo = $referencia['tarea_titulo'];
            $idTarea = $referencia['id_tarea'] > 0 ? $referencia['id_tarea'] : null;
        } else {
            $cantidadInicial = 0.0;
            $nombreCatalogo = obtenerNombreMaterialCatalogoEnConexion($db, $idMaterial);
            if ($nombreCatalogo === null) {
                throw new RuntimeException('El material informado no existe en el catalogo.', 404);
            }
            $materialTexto = $nombreCatalogo;
            $tareaTitulo = null;
            $idTarea = null;
        }

        $stmtInsert = mysqli_prepare(
            $db,
            "INSERT INTO pedido_materiales_snapshot_detalles
                (id_pedido_materiales_snapshot, tipo_fila, id_tarea, tarea_nro, tarea_titulo, id_material,
                 material_texto, cantidad_inicial, cantidad_solicitada, pedido_1, pedido_2, pedido_3, pedido_4, pedido_5,
                 estado_autorizacion, orden_visual, id_usuario_guardado, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 'sin_solicitud', ?, ?, NOW(), NOW())"
        );
        if (!$stmtInsert) {
            throw new RuntimeException('No se pudo crear la fila del snapshot.', 500);
        }
        mysqli_stmt_bind_param(
            $stmtInsert,
            'isiisisdii',
            $idSnapshot,
            $tipoFila,
            $idTarea,
            $tareaNro,
            $tareaTitulo,
            $idMaterial,
            $materialTexto,
            $cantidadInicial,
            $ordenVisual,
            $idUsuario
        );
        if (!$stmtInsert) {
            throw new RuntimeException('No se pudo crear la fila del snapshot.', 500);
        }
        if (!mysqli_stmt_execute($stmtInsert)) {
            mysqli_stmt_close($stmtInsert);
            throw new RuntimeException('No se pudo crear la fila del snapshot.', 500);
        }
        mysqli_stmt_close($stmtInsert);

        $stmt2 = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt2, 'isiii', $idSnapshot, $tipoFila, $idMaterial, $tareaNroConsulta, $ordenVisual);
        mysqli_stmt_execute($stmt2);
        $res2 = mysqli_stmt_get_result($stmt2);
        $row2 = $res2 ? mysqli_fetch_assoc($res2) : null;
        mysqli_stmt_close($stmt2);

        if (!$row2) {
            throw new RuntimeException('No se pudo confirmar la creacion de la fila del snapshot.', 500);
        }

        return $row2;
    }
}

if (!function_exists('registrarMovimientoCantidadPedidoMaterialesEnConexion')) {
    /**
     * P111: persiste la cantidad ABSOLUTA deseada para el ciclo activo
     * DIRECTAMENTE en pedido_materiales_snapshot_detalles (la fuente de verdad
     * de Pedido), dentro de una transaccion InnoDB real con FOR UPDATE sobre la
     * cabecera y el detalle del snapshot. Ya NO escribe en materiales_visita:
     * esa escritura duplicaba el snapshot y no podia coordinarse de forma
     * transaccional real entre MyISAM e InnoDB (P110, P111 punto 3).
     *
     * Operacion idempotente: reenviar la misma cantidad absoluta no produce
     * ningun efecto adicional (se detecta "sin cambios" y no se escribe nada).
     */
    function registrarMovimientoCantidadPedidoMaterialesEnConexion(
        mysqli $db,
        int $idPrevisita,
        string $tipoFila,
        int $idMaterial,
        ?int $tareaNro,
        int $ordenVisual,
        int $numeroPedido,
        $cantidadDeseadaCruda,
        int $idUsuario
    ): array {
        if ($idPrevisita <= 0 || $idMaterial <= 0 || $ordenVisual <= 0 || $idUsuario <= 0) {
            throw new RuntimeException('Los datos del movimiento no son validos.', 422);
        }
        if (!in_array($tipoFila, ['presupuestado', 'agregado'], true)) {
            throw new RuntimeException('El tipo de fila no es valido.', 422);
        }
        if ($numeroPedido < 1 || $numeroPedido > 5) {
            throw new RuntimeException('El numero de pedido no es valido.', 422);
        }

        // Existencia real del material (no se asume por el solo hecho de venir de la UI).
        $stmtMaterial = mysqli_prepare($db, 'SELECT id_material FROM materiales WHERE id_material = ? LIMIT 1');
        if (!$stmtMaterial) {
            throw new RuntimeException('No se pudo validar el material.', 500);
        }
        mysqli_stmt_bind_param($stmtMaterial, 'i', $idMaterial);
        mysqli_stmt_execute($stmtMaterial);
        $existeMaterial = mysqli_stmt_get_result($stmtMaterial);
        $materialRow = $existeMaterial ? mysqli_fetch_assoc($existeMaterial) : null;
        mysqli_stmt_close($stmtMaterial);
        if (!$materialRow) {
            throw new RuntimeException('El material informado no existe.', 404);
        }

        // Habilitacion real por OC (mismo criterio que la UI, verificado en servidor).
        $ordenCompra = obtenerOrdenCompraHabilitantePedidoMaterialesEnConexion($db, $idPrevisita);
        if (!$ordenCompra) {
            throw new RuntimeException('Pedido de materiales no esta habilitado: no hay una Orden de compra cargada u observada para este presupuesto.', 409);
        }

        // Unidad real (congelada o vigente segun tipo_fila) y clasificacion.
        $unidadVenta = obtenerUnidadVentaMaterialPedidoEnConexion($db, $idPrevisita, $tipoFila, $idMaterial, $tareaNro);
        if ($unidadVenta === null) {
            throw new RuntimeException('No se pudo determinar la unidad de venta del material para validar la cantidad.', 422);
        }
        $admiteDecimales = unidadVentaPedidoMaterialesAdmiteDecimales($unidadVenta);

        $validacionCantidad = validarCantidadMovimientoPedidoMateriales($cantidadDeseadaCruda, $admiteDecimales);
        if (!$validacionCantidad['ok']) {
            throw new RuntimeException($validacionCantidad['error'], 422);
        }
        $cantidadDeseada = $validacionCantidad['valor'];

        mysqli_begin_transaction($db);
        try {
            $header = obtenerOCrearSnapshotHeaderParaEdicionEnConexion($db, $idPrevisita, $idUsuario);
            if ((int)$header['pedido_activo'] !== $numeroPedido) {
                throw new RuntimeException('El pedido indicado ya no es el pedido activo.', 409);
            }
            if (!empty($header['finalizado'])) {
                throw new RuntimeException('El flujo de Pedido de materiales ya esta finalizado.', 409);
            }

            $idSnapshot = (int)$header['id_pedido_materiales_snapshot'];
            $detalleDb = obtenerOCrearFilaSnapshotDetalleEnConexion(
                $db,
                $idSnapshot,
                $idPrevisita,
                $tipoFila,
                $idMaterial,
                $tareaNro,
                $ordenVisual,
                $idUsuario
            );

            $estadoAutorizacionFila = trim((string)($detalleDb['estado_autorizacion'] ?? 'sin_solicitud'));
            if ($estadoAutorizacionFila !== 'sin_solicitud') {
                throw new RuntimeException('Esta fila tiene una autorizacion pendiente o ya decidida en el pedido activo; no puede editarse directamente.', 409);
            }

            $columnaPedido = 'pedido_' . $numeroPedido;
            $cantidadActualPedidoActivo = round((float)($detalleDb[$columnaPedido] ?? 0), 4);

            if (abs($cantidadDeseada - $cantidadActualPedidoActivo) < 0.005) {
                // Idempotencia: la misma cantidad absoluta ya esta persistida.
                // No se escribe nada (evita duplicar efectos ante un reenvio).
                mysqli_commit($db);
                return [
                    'ok' => true,
                    'sin_cambios' => true,
                    'cantidad_confirmada' => $cantidadActualPedidoActivo,
                    'cantidad_solicitada' => round((float)($detalleDb['cantidad_solicitada'] ?? 0), 2),
                    'unidad_venta' => $unidadVenta,
                    'admite_decimales' => $admiteDecimales,
                ];
            }

            // Re-evaluacion REAL de excedente con la misma funcion ya usada y
            // verificada por la correccion de autorizacion por ciclo (P104-R1).
            $filaParaContexto = filaSnapshotAutorizacionPedidoMaterialesDesdeDb($detalleDb);
            $filaParaContexto['pedidos'][$numeroPedido] = $cantidadDeseada;
            $contexto = obtenerContextoFilaAutorizacionPedidoMateriales($filaParaContexto, $numeroPedido);
            if ($contexto['requiere_autorizacion']) {
                throw new RuntimeException('La cantidad solicitada excede el limite disponible y requiere autorizacion. Utilice el circuito de autorizacion de excedentes.', 409);
            }

            $nuevaCantidadSolicitada = 0.0;
            for ($n = 1; $n <= 5; $n++) {
                $valorCiclo = $n === $numeroPedido ? $cantidadDeseada : round((float)($detalleDb['pedido_' . $n] ?? 0), 4);
                $nuevaCantidadSolicitada += $valorCiclo;
            }

            $stmtUpdate = mysqli_prepare(
                $db,
                "UPDATE pedido_materiales_snapshot_detalles
                 SET {$columnaPedido} = ?, cantidad_solicitada = ?, id_usuario_guardado = ?, updated_at = NOW()
                 WHERE id_pedido_materiales_snapshot_detalle = ?"
            );
            if (!$stmtUpdate) {
                throw new RuntimeException('No se pudo registrar la cantidad.', 500);
            }
            $idDetalle = (int)$detalleDb['id_pedido_materiales_snapshot_detalle'];
            mysqli_stmt_bind_param($stmtUpdate, 'ddii', $cantidadDeseada, $nuevaCantidadSolicitada, $idUsuario, $idDetalle);
            $ejecutoOk = mysqli_stmt_execute($stmtUpdate);
            $filasAfectadas = $ejecutoOk ? mysqli_stmt_affected_rows($stmtUpdate) : 0;
            mysqli_stmt_close($stmtUpdate);

            // Verificacion estricta: bajo FOR UPDATE, con un valor realmente
            // distinto (ya se descarto "sin cambios" arriba), MySQL SIEMPRE
            // reporta 1 fila afectada; 0 solo puede significar que la fila ya
            // no existe (fue eliminada por otra transaccion) — no se informa
            // exito en ese caso.
            if (!$ejecutoOk || $filasAfectadas !== 1) {
                throw new RuntimeException('La cantidad no pudo verificarse como persistida correctamente.', 500);
            }

            mysqli_commit($db);

            return [
                'ok' => true,
                'sin_cambios' => false,
                'cantidad_confirmada' => $cantidadDeseada,
                'cantidad_solicitada' => round($nuevaCantidadSolicitada, 2),
                'unidad_venta' => $unidadVenta,
                'admite_decimales' => $admiteDecimales,
            ];
        } catch (Throwable $e) {
            mysqli_rollback($db);
            throw $e;
        }
    }
}

if (!function_exists('eliminarMaterialAgregadoPedidoMaterialesEnConexion')) {
    /**
     * P111 punto 6: reemplaza guardarMaterialPedidoAdicional()/simpleInsertInDB_v2
     * para "quitar material" de Materiales agregados. Idempotente (eliminar dos
     * veces la misma fila no falla, la segunda vez informa 'ya_eliminado').
     *
     * No asume que "quitar del pedido activo" equivale a insertar un delta
     * negativo sobre el acumulado historico: si la fila tiene cantidad real en
     * algun ciclo ANTERIOR al activo (ya congelado/confirmado), esos valores no
     * se tocan — solo se pone en 0 el ciclo activo. Solo se hace DELETE fisico
     * de la fila cuando no existe ningun valor historico que preservar.
     *
     * No modifica pedido_materiales_autorizaciones: el registro formal de una
     * autorizacion ya decidida (evidencia de auditoria) nunca se borra ni se
     * altera desde aqui.
     */
    function eliminarMaterialAgregadoPedidoMaterialesEnConexion(
        mysqli $db,
        int $idPrevisita,
        int $idMaterial,
        int $ordenVisual,
        int $numeroPedido,
        int $idUsuario
    ): array {
        if ($idPrevisita <= 0 || $idMaterial <= 0 || $ordenVisual <= 0 || $idUsuario <= 0) {
            throw new RuntimeException('Los datos de la eliminacion no son validos.', 422);
        }
        if ($numeroPedido < 1 || $numeroPedido > 5) {
            throw new RuntimeException('El numero de pedido no es valido.', 422);
        }

        mysqli_begin_transaction($db);
        try {
            $stmtHeader = mysqli_prepare(
                $db,
                'SELECT * FROM pedido_materiales_snapshots WHERE id_previsita = ? LIMIT 1 FOR UPDATE'
            );
            if (!$stmtHeader) {
                throw new RuntimeException('No se pudo bloquear la cabecera del snapshot.', 500);
            }
            mysqli_stmt_bind_param($stmtHeader, 'i', $idPrevisita);
            mysqli_stmt_execute($stmtHeader);
            $resHeader = mysqli_stmt_get_result($stmtHeader);
            $header = $resHeader ? mysqli_fetch_assoc($resHeader) : null;
            mysqli_stmt_close($stmtHeader);

            if (!$header) {
                throw new RuntimeException('No existe un snapshot de Pedido de materiales para esta previsita.', 404);
            }
            if ((int)$header['pedido_activo'] !== $numeroPedido) {
                throw new RuntimeException('El pedido indicado ya no es el pedido activo.', 409);
            }
            if (!empty($header['finalizado'])) {
                throw new RuntimeException('El flujo de Pedido de materiales ya esta finalizado.', 409);
            }

            $idSnapshot = (int)$header['id_pedido_materiales_snapshot'];
            $stmtDetalle = mysqli_prepare(
                $db,
                "SELECT * FROM pedido_materiales_snapshot_detalles
                 WHERE id_pedido_materiales_snapshot = ? AND tipo_fila = 'agregado' AND id_material = ? AND orden_visual = ?
                 LIMIT 1 FOR UPDATE"
            );
            if (!$stmtDetalle) {
                throw new RuntimeException('No se pudo bloquear la fila del snapshot.', 500);
            }
            mysqli_stmt_bind_param($stmtDetalle, 'iii', $idSnapshot, $idMaterial, $ordenVisual);
            mysqli_stmt_execute($stmtDetalle);
            $resDetalle = mysqli_stmt_get_result($stmtDetalle);
            $detalleDb = $resDetalle ? mysqli_fetch_assoc($resDetalle) : null;
            mysqli_stmt_close($stmtDetalle);

            if (!$detalleDb) {
                // Idempotente: ya no existe (eliminada antes, o reintento tras exito previo).
                mysqli_commit($db);
                return ['ok' => true, 'ya_eliminado' => true];
            }

            $tieneHistoria = false;
            for ($n = 1; $n < $numeroPedido; $n++) {
                if (round((float)($detalleDb['pedido_' . $n] ?? 0), 4) > 0.00005) {
                    $tieneHistoria = true;
                    break;
                }
            }

            $idDetalle = (int)$detalleDb['id_pedido_materiales_snapshot_detalle'];

            if (!$tieneHistoria) {
                $stmtDelete = mysqli_prepare(
                    $db,
                    'DELETE FROM pedido_materiales_snapshot_detalles WHERE id_pedido_materiales_snapshot_detalle = ?'
                );
                if (!$stmtDelete) {
                    throw new RuntimeException('No se pudo eliminar el material agregado.', 500);
                }
                mysqli_stmt_bind_param($stmtDelete, 'i', $idDetalle);
                $ejecutoOk = mysqli_stmt_execute($stmtDelete);
                $filasAfectadas = $ejecutoOk ? mysqli_stmt_affected_rows($stmtDelete) : 0;
                mysqli_stmt_close($stmtDelete);

                if (!$ejecutoOk || $filasAfectadas !== 1) {
                    throw new RuntimeException('El material agregado no pudo eliminarse correctamente.', 500);
                }

                mysqli_commit($db);
                return ['ok' => true, 'ya_eliminado' => false, 'eliminacion_fisica' => true];
            }

            // Tiene cantidad en un ciclo historico ya congelado: se preserva esa
            // historia y solo se vacia el ciclo activo (nunca se tocan pedidos
            // anteriores).
            $columnaPedido = 'pedido_' . $numeroPedido;
            $nuevaCantidadSolicitada = 0.0;
            for ($n = 1; $n <= 5; $n++) {
                $valorCiclo = $n === $numeroPedido ? 0.0 : round((float)($detalleDb['pedido_' . $n] ?? 0), 4);
                $nuevaCantidadSolicitada += $valorCiclo;
            }

            $stmtVaciar = mysqli_prepare(
                $db,
                "UPDATE pedido_materiales_snapshot_detalles
                 SET {$columnaPedido} = 0, cantidad_solicitada = ?, estado_autorizacion = 'sin_solicitud', id_usuario_guardado = ?, updated_at = NOW()
                 WHERE id_pedido_materiales_snapshot_detalle = ?"
            );
            if (!$stmtVaciar) {
                throw new RuntimeException('No se pudo actualizar el material agregado.', 500);
            }
            mysqli_stmt_bind_param($stmtVaciar, 'dii', $nuevaCantidadSolicitada, $idUsuario, $idDetalle);
            $ejecutoOk = mysqli_stmt_execute($stmtVaciar);
            $filasAfectadas = $ejecutoOk ? mysqli_stmt_affected_rows($stmtVaciar) : 0;
            mysqli_stmt_close($stmtVaciar);

            if (!$ejecutoOk || $filasAfectadas !== 1) {
                throw new RuntimeException('El material agregado no pudo actualizarse correctamente.', 500);
            }

            mysqli_commit($db);
            return [
                'ok' => true,
                'ya_eliminado' => false,
                'eliminacion_fisica' => false,
                'cantidad_solicitada' => round($nuevaCantidadSolicitada, 2),
            ];
        } catch (Throwable $e) {
            mysqli_rollback($db);
            throw $e;
        }
    }
}
