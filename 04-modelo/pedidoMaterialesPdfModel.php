<?php

require_once __DIR__ . '/schemaIntrospectionModel.php';
require_once __DIR__ . '/presupuestoDocumentosEmitidosModel.php';

if (!function_exists('pedidoMaterialesPdfTablasMinimasDisponibles')) {
    function pedidoMaterialesPdfTablasMinimasDisponibles(mysqli $db): bool
    {
        return tabla_existe($db, 'pedido_materiales_pedidos')
            && tabla_existe($db, 'pedido_materiales_pedido_detalles')
            && tabla_existe($db, 'pedido_materiales_pedido_documentos');
    }
}

if (!function_exists('obtenerNumeroVisiblePresupuestoPedidoMateriales')) {
    function obtenerNumeroVisiblePresupuestoPedidoMateriales(
        mysqli $db,
        int $idPresupuesto,
        int $idPrevisita
    ): array {
        $resultado = [
            'numero_visible' => '',
            'id_documento_emitido' => null,
            'nombre_archivo' => '',
            'fuente' => '',
        ];

        if (
            $idPresupuesto <= 0
            || $idPrevisita <= 0
            || !function_exists('resolverDocumentoAprobadoVigentePresupuestoEnConexion')
            || !function_exists('extraerNumeroDocumentoEmitidoPresupuesto')
        ) {
            return $resultado;
        }

        $documento = resolverDocumentoAprobadoVigentePresupuestoEnConexion(
            $db,
            $idPresupuesto,
            $idPrevisita
        );
        $nombreArchivo = trim((string)($documento['nombre_archivo'] ?? ''));
        $numeroVisible = $nombreArchivo !== ''
            ? trim(extraerNumeroDocumentoEmitidoPresupuesto($nombreArchivo))
            : '';

        if ($numeroVisible === '') {
            return $resultado;
        }

        return [
            'numero_visible' => $numeroVisible,
            'id_documento_emitido' => (int)($documento['id_documento_emitido'] ?? 0) ?: null,
            'nombre_archivo' => $nombreArchivo,
            'fuente' => 'presupuesto_documentos_emitidos.nombre_archivo',
        ];
    }
}

if (!function_exists('obtenerPedidoMaterialesConfirmadoParaPdf')) {
    function obtenerPedidoMaterialesConfirmadoParaPdf(mysqli $db, int $idPedido): array
    {
        if ($idPedido <= 0) {
            throw new RuntimeException('El pedido confirmado es obligatorio.', 422);
        }

        if (!pedidoMaterialesPdfTablasMinimasDisponibles($db)) {
            throw new RuntimeException(
                'La persistencia PDF de Pedido de Materiales no esta disponible. Debe aplicarse la migracion 2026-07-29-B_pedido_materiales_pedido_documentos.sql.',
                409
            );
        }

        $sqlCabecera = "
            SELECT
                p.id_pedido_materiales_pedido,
                p.id_previsita,
                p.id_presupuesto,
                p.id_orden_compra,
                p.numero_pedido,
                p.estado,
                p.id_usuario_confirmacion,
                p.fecha_confirmacion,
                p.snapshot_hash,
                p.created_at,
                p.updated_at,
                pv.razon_social AS cliente_obra,
                pv.calle_visita,
                pv.altura_visita,
                pv.localidad_visita,
                pv.partido_visita,
                pv.provincia_visita,
                oc.numero_oc,
                oc.fecha_emision AS fecha_emision_oc,
                oc.estado AS estado_oc,
                oc.direccion_entrega,
                oc.direccion_obra_alternativa,
                CONCAT_WS(' ', u.nombres, u.apellidos) AS usuario_confirmacion,
                u.perfil AS perfil_usuario_confirmacion
            FROM pedido_materiales_pedidos p
            LEFT JOIN previsitas pv
              ON pv.id_previsita = p.id_previsita
            LEFT JOIN ordenes_compra oc
              ON oc.id_orden_compra = p.id_orden_compra
            LEFT JOIN usuarios u
              ON u.id_usuario = p.id_usuario_confirmacion
            WHERE p.id_pedido_materiales_pedido = ?
            LIMIT 1
        ";
        $stmtCabecera = mysqli_prepare($db, $sqlCabecera);
        if (!$stmtCabecera) {
            throw new RuntimeException('No se pudo consultar la cabecera del pedido confirmado.', 500);
        }

        mysqli_stmt_bind_param($stmtCabecera, 'i', $idPedido);
        if (!mysqli_stmt_execute($stmtCabecera)) {
            mysqli_stmt_close($stmtCabecera);
            throw new RuntimeException('No se pudo consultar la cabecera del pedido confirmado.', 500);
        }

        $resultCabecera = mysqli_stmt_get_result($stmtCabecera);
        $cabecera = $resultCabecera ? mysqli_fetch_assoc($resultCabecera) : null;
        mysqli_stmt_close($stmtCabecera);

        if (!$cabecera) {
            throw new RuntimeException('No se encontro el pedido de materiales confirmado.', 404);
        }

        $presupuestoVisible = obtenerNumeroVisiblePresupuestoPedidoMateriales(
            $db,
            (int)$cabecera['id_presupuesto'],
            (int)$cabecera['id_previsita']
        );
        $cabecera['numero_presupuesto_visible'] = $presupuestoVisible['numero_visible'];
        $cabecera['id_documento_presupuesto_aprobado'] = $presupuestoVisible['id_documento_emitido'];
        $cabecera['nombre_documento_presupuesto_aprobado'] = $presupuestoVisible['nombre_archivo'];
        $cabecera['fuente_numero_presupuesto_visible'] = $presupuestoVisible['fuente'];

        $sqlDetalles = "
            SELECT
                id_pedido_materiales_pedido_detalle,
                id_pedido_materiales_pedido,
                tipo_fila,
                id_tarea,
                tarea_nro,
                tarea_titulo,
                id_material,
                material_texto,
                cantidad_inicial,
                cantidad_pedido,
                estado_autorizacion,
                orden_visual,
                created_at
            FROM pedido_materiales_pedido_detalles
            WHERE id_pedido_materiales_pedido = ?
              AND cantidad_pedido > 0
            ORDER BY orden_visual ASC, id_pedido_materiales_pedido_detalle ASC
        ";
        $stmtDetalles = mysqli_prepare($db, $sqlDetalles);
        if (!$stmtDetalles) {
            throw new RuntimeException('No se pudo consultar el detalle del pedido confirmado.', 500);
        }

        mysqli_stmt_bind_param($stmtDetalles, 'i', $idPedido);
        if (!mysqli_stmt_execute($stmtDetalles)) {
            mysqli_stmt_close($stmtDetalles);
            throw new RuntimeException('No se pudo consultar el detalle del pedido confirmado.', 500);
        }

        $resultDetalles = mysqli_stmt_get_result($stmtDetalles);
        $detalles = [];
        while ($resultDetalles && ($detalle = mysqli_fetch_assoc($resultDetalles))) {
            $detalles[] = $detalle;
        }
        mysqli_stmt_close($stmtDetalles);

        if (!$detalles) {
            throw new RuntimeException(
                'El pedido confirmado no tiene materiales con cantidad pedida mayor que cero.',
                409
            );
        }

        return [
            'cabecera' => $cabecera,
            'detalles' => $detalles,
        ];
    }
}

if (!function_exists('pedidoMaterialesPdfTextoWinAnsi')) {
    function pedidoMaterialesPdfTextoWinAnsi(string $texto): string
    {
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $texto) ?? '';
        $convertido = function_exists('iconv')
            ? @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $texto)
            : false;
        if ($convertido !== false) {
            $texto = $convertido;
        }

        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\\(', '\\)', ' ', ' '],
            $texto
        );
    }
}

if (!function_exists('pedidoMaterialesPdfLongitud')) {
    function pedidoMaterialesPdfLongitud(string $texto): int
    {
        return function_exists('mb_strlen')
            ? mb_strlen($texto, 'UTF-8')
            : strlen($texto);
    }
}

if (!function_exists('pedidoMaterialesPdfSubstr')) {
    function pedidoMaterialesPdfSubstr(string $texto, int $inicio, int $longitud): string
    {
        return function_exists('mb_substr')
            ? mb_substr($texto, $inicio, $longitud, 'UTF-8')
            : substr($texto, $inicio, $longitud);
    }
}

if (!function_exists('pedidoMaterialesPdfDividirTexto')) {
    function pedidoMaterialesPdfDividirTexto(string $texto, int $maximoCaracteres): array
    {
        $texto = trim((string)(preg_replace('/\s+/u', ' ', $texto) ?? ''));
        if ($texto === '') {
            return ['-'];
        }

        $maximoCaracteres = max(1, $maximoCaracteres);
        $palabras = preg_split('/\s+/u', $texto) ?: [];
        $lineas = [];
        $linea = '';

        foreach ($palabras as $palabra) {
            while (pedidoMaterialesPdfLongitud($palabra) > $maximoCaracteres) {
                if ($linea !== '') {
                    $lineas[] = $linea;
                    $linea = '';
                }
                $lineas[] = pedidoMaterialesPdfSubstr($palabra, 0, $maximoCaracteres);
                $palabra = pedidoMaterialesPdfSubstr(
                    $palabra,
                    $maximoCaracteres,
                    pedidoMaterialesPdfLongitud($palabra)
                );
            }

            $candidata = $linea === '' ? $palabra : ($linea . ' ' . $palabra);
            if (pedidoMaterialesPdfLongitud($candidata) <= $maximoCaracteres) {
                $linea = $candidata;
                continue;
            }

            if ($linea !== '') {
                $lineas[] = $linea;
            }
            $linea = $palabra;
        }

        if ($linea !== '') {
            $lineas[] = $linea;
        }

        return $lineas ?: ['-'];
    }
}

if (!class_exists('PedidoMaterialesPdfDocumento')) {
    class PedidoMaterialesPdfDocumento
    {
        private array $paginas = [];

        public function agregarPagina(): int
        {
            $this->paginas[] = '';

            return count($this->paginas) - 1;
        }

        public function cantidadPaginas(): int
        {
            return count($this->paginas);
        }

        public function texto(
            int $pagina,
            float $x,
            float $y,
            string $texto,
            float $tamano = 9,
            bool $negrita = false,
            array $color = [0.12, 0.12, 0.12]
        ): void {
            $fuente = $negrita ? 'F2' : 'F1';
            $contenido = pedidoMaterialesPdfTextoWinAnsi($texto);
            $this->paginas[$pagina] .= sprintf(
                "%.3F %.3F %.3F rg BT /%s %.2F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
                (float)$color[0],
                (float)$color[1],
                (float)$color[2],
                $fuente,
                $tamano,
                $x,
                $y,
                $contenido
            );
        }

        public function textoDerecha(
            int $pagina,
            float $xDerecha,
            float $y,
            string $texto,
            float $tamano = 9,
            bool $negrita = false,
            array $color = [0.12, 0.12, 0.12]
        ): void {
            $factorAncho = $negrita ? 0.56 : 0.52;
            $anchoEstimado = pedidoMaterialesPdfLongitud($texto) * $tamano * $factorAncho;
            $this->texto(
                $pagina,
                max(36.0, $xDerecha - $anchoEstimado),
                $y,
                $texto,
                $tamano,
                $negrita,
                $color
            );
        }

        public function linea(
            int $pagina,
            float $x1,
            float $y1,
            float $x2,
            float $y2,
            array $color = [0.72, 0.72, 0.72],
            float $ancho = 0.5
        ): void {
            $this->paginas[$pagina] .= sprintf(
                "%.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S\n",
                (float)$color[0],
                (float)$color[1],
                (float)$color[2],
                $ancho,
                $x1,
                $y1,
                $x2,
                $y2
            );
        }

        public function rectangulo(
            int $pagina,
            float $x,
            float $y,
            float $ancho,
            float $alto,
            array $relleno = [1, 1, 1],
            array $borde = [0.72, 0.72, 0.72]
        ): void {
            $this->paginas[$pagina] .= sprintf(
                "%.3F %.3F %.3F rg %.3F %.3F %.3F RG 0.50 w %.2F %.2F %.2F %.2F re B\n",
                (float)$relleno[0],
                (float)$relleno[1],
                (float)$relleno[2],
                (float)$borde[0],
                (float)$borde[1],
                (float)$borde[2],
                $x,
                $y,
                $ancho,
                $alto
            );
        }

        public function generarBinario(): string
        {
            if (!$this->paginas) {
                $this->agregarPagina();
            }

            $objetos = [];
            $objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
            $objetos[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
            $objetos[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

            $referenciasPaginas = [];
            $numeroObjeto = 5;
            foreach ($this->paginas as $contenido) {
                $objetoPagina = $numeroObjeto++;
                $objetoContenido = $numeroObjeto++;
                $referenciasPaginas[] = $objetoPagina . ' 0 R';
                $objetos[$objetoPagina] = sprintf(
                    '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                    $objetoContenido
                );
                $objetos[$objetoContenido] = sprintf(
                    "<< /Length %d >>\nstream\n%sendstream",
                    strlen($contenido),
                    $contenido
                );
            }

            $objetos[2] = sprintf(
                '<< /Type /Pages /Kids [%s] /Count %d >>',
                implode(' ', $referenciasPaginas),
                count($referenciasPaginas)
            );
            ksort($objetos);

            $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
            $offsets = [0 => 0];
            $maximoObjeto = max(array_keys($objetos));

            for ($indice = 1; $indice <= $maximoObjeto; $indice++) {
                $offsets[$indice] = strlen($pdf);
                $pdf .= $indice . " 0 obj\n" . $objetos[$indice] . "\nendobj\n";
            }

            $inicioXref = strlen($pdf);
            $pdf .= "xref\n0 " . ($maximoObjeto + 1) . "\n";
            $pdf .= "0000000000 65535 f \n";
            for ($indice = 1; $indice <= $maximoObjeto; $indice++) {
                $pdf .= sprintf("%010d 00000 n \n", $offsets[$indice]);
            }
            $pdf .= "trailer\n<< /Size " . ($maximoObjeto + 1) . " /Root 1 0 R >>\n";
            $pdf .= "startxref\n" . $inicioXref . "\n%%EOF\n";

            return $pdf;
        }
    }
}

if (!function_exists('pedidoMaterialesPdfFormatearDecimal')) {
    function pedidoMaterialesPdfFormatearDecimal($valor): string
    {
        $numero = (float)$valor;
        $decimales = abs($numero - round($numero)) < 0.00005 ? 0 : 4;

        return number_format($numero, $decimales, ',', '.');
    }
}

if (!function_exists('pedidoMaterialesPdfEtiquetaAutorizacion')) {
    function pedidoMaterialesPdfEtiquetaAutorizacion(string $estado): string
    {
        $etiquetas = [
            'sin_solicitud' => 'Sin solicitud',
            'pendiente' => 'Pendiente',
            'autorizada' => 'Autorizada',
            'rechazada' => 'Rechazada',
        ];

        return $etiquetas[$estado] ?? 'Sin solicitud';
    }
}

if (!function_exists('pedidoMaterialesPdfAgregarEncabezadoTabla')) {
    function pedidoMaterialesPdfAgregarEncabezadoTabla(
        PedidoMaterialesPdfDocumento $pdf,
        int $pagina,
        float $ySuperior,
        array $columnas
    ): float {
        $altura = 22.0;
        $x = 36.0;

        foreach ($columnas as $columna) {
            $pdf->rectangulo(
                $pagina,
                $x,
                $ySuperior - $altura,
                (float)$columna['ancho'],
                $altura,
                [0.88, 0.91, 0.94],
                [0.55, 0.60, 0.65]
            );
            $lineas = pedidoMaterialesPdfDividirTexto((string)$columna['titulo'], (int)$columna['caracteres']);
            foreach (array_slice($lineas, 0, 2) as $indice => $linea) {
                $pdf->texto(
                    $pagina,
                    $x + 3,
                    $ySuperior - 9 - ($indice * 8),
                    $linea,
                    7,
                    true,
                    [0.10, 0.16, 0.22]
                );
            }
            $x += (float)$columna['ancho'];
        }

        return $ySuperior - $altura;
    }
}

if (!function_exists('pedidoMaterialesPdfAgregarCabeceraPagina')) {
    function pedidoMaterialesPdfAgregarCabeceraPagina(
        PedidoMaterialesPdfDocumento $pdf,
        int $pagina,
        array $cabecera,
        bool $continuacion = false
    ): float {
        $numeroPedido = (int)$cabecera['numero_pedido'];
        $clienteObra = trim((string)($cabecera['cliente_obra'] ?? '')) ?: '-';
        $etiquetaPedido = 'Pedido #' . $numeroPedido . ($continuacion ? ' - continuacion' : '');

        $pdf->texto($pagina, 36, 807, 'Pedido de Materiales', 17, true, [0.06, 0.31, 0.55]);
        $pdf->textoDerecha(
            $pagina,
            559,
            807,
            $etiquetaPedido,
            12,
            true,
            [0.06, 0.31, 0.55]
        );
        $pdf->texto(
            $pagina,
            36,
            786,
            'Cliente / Obra: ' . $clienteObra,
            9.5,
            true,
            [0.18, 0.18, 0.18]
        );
        $pdf->linea($pagina, 36, 776, 559, 776, [0.06, 0.31, 0.55], 1.2);

        if ($continuacion) {
            return 758;
        }

        $usuario = trim((string)($cabecera['usuario_confirmacion'] ?? ''));
        if ($usuario === '') {
            $usuarioPedido = 'Usuario ID ' . (int)$cabecera['id_usuario_confirmacion'];
        } else {
            $usuarioPedido = $usuario . ' (ID ' . (int)$cabecera['id_usuario_confirmacion'] . ')';
        }
        $numeroPresupuestoVisible = trim((string)($cabecera['numero_presupuesto_visible'] ?? ''));
        if ($numeroPresupuestoVisible === '') {
            $numeroPresupuestoVisible = 'No disponible';
        }
        $numeroOc = trim((string)($cabecera['numero_oc'] ?? '')) ?: '-';
        $direccion = trim((string)($cabecera['direccion_entrega'] ?? ''));
        if ($direccion === '') {
            $direccion = trim((string)($cabecera['direccion_obra_alternativa'] ?? ''));
        }
        if ($direccion === '') {
            $direccion = trim(implode(' ', array_filter([
                $cabecera['calle_visita'] ?? '',
                $cabecera['altura_visita'] ?? '',
                $cabecera['localidad_visita'] ?? '',
                $cabecera['partido_visita'] ?? '',
                $cabecera['provincia_visita'] ?? '',
            ], static fn($valor): bool => trim((string)$valor) !== '')));
        }
        $direccion = $direccion !== '' ? $direccion : '-';

        $lineas = [
            'Pedido interno: ' . (int)$cabecera['id_pedido_materiales_pedido'],
            'Fecha de confirmacion: ' . (string)$cabecera['fecha_confirmacion'],
            'Previsita: ' . (int)$cabecera['id_previsita'],
            'Presupuesto Nro.: ' . $numeroPresupuestoVisible,
            'Orden de Compra: ' . (int)$cabecera['id_orden_compra']
                . ' / Nro. ' . $numeroOc,
            'Ubicacion / entrega: ' . $direccion,
            'Pedido realizado por: ' . $usuarioPedido,
            'Estado: ' . (string)$cabecera['estado']
                . '    Referencia: ' . substr((string)$cabecera['snapshot_hash'], 0, 16),
        ];

        $y = 758.0;
        foreach ($lineas as $linea) {
            $segmentos = pedidoMaterialesPdfDividirTexto($linea, 100);
            foreach (array_slice($segmentos, 0, 2) as $segmento) {
                $pdf->texto($pagina, 36, $y, $segmento, 8.5);
                $y -= 11;
            }
        }

        return $y - 4;
    }
}

if (!function_exists('crearPdfPedidoMaterialesConfirmado')) {
    function crearPdfPedidoMaterialesConfirmado(array $pedido, string $fechaGeneracion): string
    {
        $cabecera = (array)($pedido['cabecera'] ?? []);
        $detalles = (array)($pedido['detalles'] ?? []);
        if (!$cabecera || !$detalles) {
            throw new RuntimeException('No hay datos suficientes para generar el PDF.', 409);
        }

        $columnas = [
            ['titulo' => '#', 'ancho' => 24, 'caracteres' => 3],
            ['titulo' => 'Tarea', 'ancho' => 76, 'caracteres' => 17],
            ['titulo' => 'Material', 'ancho' => 172, 'caracteres' => 42],
            ['titulo' => 'Tipo', 'ancho' => 68, 'caracteres' => 15],
            ['titulo' => 'Cant. inicial', 'ancho' => 58, 'caracteres' => 12],
            ['titulo' => 'Cant. pedida', 'ancho' => 62, 'caracteres' => 13],
            ['titulo' => 'Autorizacion', 'ancho' => 63, 'caracteres' => 14],
        ];
        $pdf = new PedidoMaterialesPdfDocumento();
        $pagina = $pdf->agregarPagina();
        $y = pedidoMaterialesPdfAgregarCabeceraPagina($pdf, $pagina, $cabecera);
        $y = pedidoMaterialesPdfAgregarEncabezadoTabla($pdf, $pagina, $y, $columnas);

        foreach ($detalles as $indice => $detalle) {
            $tarea = trim((string)($detalle['tarea_nro'] ?? ''));
            $tituloTarea = trim((string)($detalle['tarea_titulo'] ?? ''));
            if ($tituloTarea !== '') {
                $tarea = ($tarea !== '' ? ($tarea . ' - ') : '') . $tituloTarea;
            }

            $valores = [
                (string)($indice + 1),
                $tarea !== '' ? $tarea : '-',
                (string)($detalle['material_texto'] ?? '-'),
                ($detalle['tipo_fila'] ?? '') === 'agregado' ? 'Agregado' : 'Presupuestado',
                pedidoMaterialesPdfFormatearDecimal($detalle['cantidad_inicial'] ?? 0),
                pedidoMaterialesPdfFormatearDecimal($detalle['cantidad_pedido'] ?? 0),
                pedidoMaterialesPdfEtiquetaAutorizacion((string)($detalle['estado_autorizacion'] ?? '')),
            ];

            $lineasCeldas = [];
            $maximoLineas = 1;
            foreach ($columnas as $columnaIndice => $columna) {
                $lineasCeldas[$columnaIndice] = pedidoMaterialesPdfDividirTexto(
                    $valores[$columnaIndice],
                    (int)$columna['caracteres']
                );
                $maximoLineas = max($maximoLineas, count($lineasCeldas[$columnaIndice]));
            }
            $alturaFila = max(20.0, 8.0 + ($maximoLineas * 8.0));

            if (($y - $alturaFila) < 48) {
                $pagina = $pdf->agregarPagina();
                $y = pedidoMaterialesPdfAgregarCabeceraPagina($pdf, $pagina, $cabecera, true);
                $y = pedidoMaterialesPdfAgregarEncabezadoTabla($pdf, $pagina, $y, $columnas);
            }

            $x = 36.0;
            foreach ($columnas as $columnaIndice => $columna) {
                $pdf->rectangulo(
                    $pagina,
                    $x,
                    $y - $alturaFila,
                    (float)$columna['ancho'],
                    $alturaFila,
                    ($indice % 2 === 0) ? [1, 1, 1] : [0.97, 0.98, 0.99]
                );
                foreach ($lineasCeldas[$columnaIndice] as $lineaIndice => $linea) {
                    $pdf->texto(
                        $pagina,
                        $x + 3,
                        $y - 10 - ($lineaIndice * 8),
                        $linea,
                        7
                    );
                }
                $x += (float)$columna['ancho'];
            }
            $y -= $alturaFila;
        }

        $totalPaginas = $pdf->cantidadPaginas();
        for ($indicePagina = 0; $indicePagina < $totalPaginas; $indicePagina++) {
            $pdf->linea($indicePagina, 36, 36, 559, 36, [0.72, 0.72, 0.72], 0.5);
            $pdf->texto(
                $indicePagina,
                36,
                22,
                'Documento generado automaticamente por AdminTech. Generado: ' . $fechaGeneracion,
                7,
                false,
                [0.35, 0.35, 0.35]
            );
            $pdf->texto(
                $indicePagina,
                518,
                22,
                'Pagina ' . ($indicePagina + 1) . '/' . $totalPaginas,
                7,
                false,
                [0.35, 0.35, 0.35]
            );
        }

        return $pdf->generarBinario();
    }
}

if (!function_exists('obtenerRutaPdfPedidoMaterialesConfirmado')) {
    function obtenerRutaPdfPedidoMaterialesConfirmado(array $pedido): array
    {
        $cabecera = (array)($pedido['cabecera'] ?? $pedido);
        $idPedido = (int)($cabecera['id_pedido_materiales_pedido'] ?? 0);
        $idPrevisita = (int)($cabecera['id_previsita'] ?? 0);
        $numeroPedido = (int)($cabecera['numero_pedido'] ?? 0);
        if ($idPedido <= 0 || $idPrevisita <= 0 || $numeroPedido < 1 || $numeroPedido > 5) {
            throw new RuntimeException('No se pudo resolver la ruta segura del PDF.', 422);
        }

        $raizProyecto = realpath(__DIR__ . '/..');
        if ($raizProyecto === false) {
            throw new RuntimeException('No se pudo resolver la raiz del proyecto.', 500);
        }

        $rutaRelativaDirectorio = sprintf(
            'uploads/pedido_materiales/%d/pedidos',
            $idPrevisita
        );
        $nombreArchivo = sprintf(
            'PEDIDO_MATERIALES_PREVISITA_%d_PEDIDO_%d_ID_%d.pdf',
            $idPrevisita,
            $numeroPedido,
            $idPedido
        );

        return [
            'directorio_absoluto' => $raizProyecto . '/' . $rutaRelativaDirectorio,
            'ruta_absoluta' => $raizProyecto . '/' . $rutaRelativaDirectorio . '/' . $nombreArchivo,
            'ruta_relativa' => $rutaRelativaDirectorio . '/' . $nombreArchivo,
            'nombre_archivo' => $nombreArchivo,
        ];
    }
}

if (!function_exists('asegurarDirectorioPdfPedidoMateriales')) {
    function asegurarDirectorioPdfPedidoMateriales(string $directorio): void
    {
        if (is_dir($directorio)) {
            return;
        }

        $umaskAnterior = umask(0002);
        $creado = @mkdir($directorio, 0775, true);
        umask($umaskAnterior);
        if (!$creado && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo crear el directorio para el PDF.', 500);
        }
    }
}

if (!function_exists('obtenerDocumentoPdfPedidoMaterialesConfirmado')) {
    function obtenerDocumentoPdfPedidoMaterialesConfirmado(
        mysqli $db,
        int $idPedido
    ): ?array {
        if ($idPedido <= 0 || !tabla_existe($db, 'pedido_materiales_pedido_documentos')) {
            return null;
        }

        $tipoDocumento = 'pedido_materiales_pdf';
        $stmt = mysqli_prepare(
            $db,
            'SELECT * FROM pedido_materiales_pedido_documentos WHERE id_pedido_materiales_pedido = ? AND tipo_documento = ? LIMIT 1'
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo consultar el documento PDF.', 500);
        }

        mysqli_stmt_bind_param($stmt, 'is', $idPedido, $tipoDocumento);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('No se pudo consultar el documento PDF.', 500);
        }

        $result = mysqli_stmt_get_result($stmt);
        $documento = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $documento ?: null;
    }
}

if (!function_exists('resolverRutaAbsolutaDocumentoPdfPedidoMateriales')) {
    function resolverRutaAbsolutaDocumentoPdfPedidoMateriales(array $documento): string
    {
        $rutaRelativa = str_replace('\\', '/', trim((string)($documento['ruta_archivo'] ?? '')));
        if (
            $rutaRelativa === ''
            || strpos($rutaRelativa, "\0") !== false
            || strpos($rutaRelativa, '..') !== false
            || !preg_match('~^uploads/pedido_materiales/[0-9]+/pedidos/[A-Za-z0-9_.-]+\.pdf$~', $rutaRelativa)
        ) {
            throw new RuntimeException('La ruta registrada para el PDF no es valida.', 409);
        }

        $raizProyecto = realpath(__DIR__ . '/..');
        $basePdf = realpath(($raizProyecto ?: (__DIR__ . '/..')) . '/uploads/pedido_materiales');
        $rutaAbsoluta = realpath(($raizProyecto ?: (__DIR__ . '/..')) . '/' . $rutaRelativa);
        if ($basePdf === false || $rutaAbsoluta === false || !is_file($rutaAbsoluta)) {
            throw new RuntimeException('El archivo PDF registrado no esta disponible.', 404);
        }

        $baseNormalizada = rtrim(str_replace('\\', '/', $basePdf), '/') . '/';
        $rutaNormalizada = str_replace('\\', '/', $rutaAbsoluta);
        if (strpos($rutaNormalizada, $baseNormalizada) !== 0) {
            throw new RuntimeException('El archivo solicitado esta fuera del directorio permitido.', 403);
        }

        return $rutaAbsoluta;
    }
}

if (!function_exists('generarPdfPedidoMaterialesConfirmado')) {
    function generarPdfPedidoMaterialesConfirmado(
        mysqli $db,
        int $idPedido,
        int $idUsuario
    ): array {
        if ($idUsuario <= 0) {
            throw new RuntimeException('No hay un usuario valido para generar el PDF.', 401);
        }

        $pedido = obtenerPedidoMaterialesConfirmadoParaPdf($db, $idPedido);
        $fechaGeneracion = date('Y-m-d H:i:s');
        $binarioPdf = crearPdfPedidoMaterialesConfirmado($pedido, $fechaGeneracion);
        if (substr($binarioPdf, 0, 4) !== '%PDF') {
            throw new RuntimeException('El documento generado no tiene formato PDF valido.', 500);
        }

        $rutas = obtenerRutaPdfPedidoMaterialesConfirmado($pedido);
        asegurarDirectorioPdfPedidoMateriales($rutas['directorio_absoluto']);

        $archivoTemporal = tempnam($rutas['directorio_absoluto'], '.pm_pdf_');
        if ($archivoTemporal === false) {
            throw new RuntimeException('No se pudo preparar el archivo temporal del PDF.', 500);
        }

        $bytesEscritos = file_put_contents($archivoTemporal, $binarioPdf, LOCK_EX);
        if ($bytesEscritos === false || $bytesEscritos <= 0) {
            @unlink($archivoTemporal);
            throw new RuntimeException('No se pudo escribir el PDF generado.', 500);
        }
        @chmod($archivoTemporal, 0640);

        $hashArchivo = hash_file('sha256', $archivoTemporal);
        $tamanoBytes = filesize($archivoTemporal);
        if (!is_string($hashArchivo) || strlen($hashArchivo) !== 64 || $tamanoBytes === false || $tamanoBytes <= 0) {
            @unlink($archivoTemporal);
            throw new RuntimeException('No se pudo validar el PDF generado.', 500);
        }

        $tipoDocumento = 'pedido_materiales_pdf';
        $mimeType = 'application/pdf';
        $destinoExistia = is_file($rutas['ruta_absoluta']);
        $archivoPublicado = false;
        mysqli_begin_transaction($db);

        try {
            $sqlDocumento = "
                INSERT INTO pedido_materiales_pedido_documentos (
                    id_pedido_materiales_pedido,
                    tipo_documento,
                    nombre_archivo,
                    ruta_archivo,
                    mime_type,
                    tamano_bytes,
                    hash_archivo,
                    id_usuario_generacion,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    id_pedido_materiales_pedido_documento = LAST_INSERT_ID(id_pedido_materiales_pedido_documento),
                    nombre_archivo = VALUES(nombre_archivo),
                    ruta_archivo = VALUES(ruta_archivo),
                    mime_type = VALUES(mime_type),
                    tamano_bytes = VALUES(tamano_bytes),
                    hash_archivo = VALUES(hash_archivo),
                    id_usuario_generacion = VALUES(id_usuario_generacion),
                    updated_at = NOW()
            ";
            $stmtDocumento = mysqli_prepare($db, $sqlDocumento);
            if (!$stmtDocumento) {
                throw new RuntimeException('No se pudo preparar el registro del PDF.', 500);
            }
            mysqli_stmt_bind_param(
                $stmtDocumento,
                'issssisi',
                $idPedido,
                $tipoDocumento,
                $rutas['nombre_archivo'],
                $rutas['ruta_relativa'],
                $mimeType,
                $tamanoBytes,
                $hashArchivo,
                $idUsuario
            );
            if (!mysqli_stmt_execute($stmtDocumento)) {
                mysqli_stmt_close($stmtDocumento);
                throw new RuntimeException('No se pudo registrar el PDF generado.', 500);
            }
            $idDocumento = (int)mysqli_insert_id($db);
            mysqli_stmt_close($stmtDocumento);

            if (!rename($archivoTemporal, $rutas['ruta_absoluta'])) {
                throw new RuntimeException('No se pudo publicar el PDF generado.', 500);
            }
            $archivoPublicado = true;
            @chmod($rutas['ruta_absoluta'], 0640);

            mysqli_commit($db);

            return [
                'ok' => true,
                'id_pedido_materiales_pedido_documento' => $idDocumento,
                'id_pedido_materiales_pedido' => $idPedido,
                'ruta_absoluta' => $rutas['ruta_absoluta'],
                'ruta_relativa' => $rutas['ruta_relativa'],
                'nombre_archivo' => $rutas['nombre_archivo'],
                'mime' => $mimeType,
                'tamano' => (int)$tamanoBytes,
                'hash_archivo' => $hashArchivo,
                'fecha_generacion' => $fechaGeneracion,
            ];
        } catch (Throwable $e) {
            mysqli_rollback($db);
            if (is_file($archivoTemporal)) {
                @unlink($archivoTemporal);
            }
            if ($archivoPublicado && !$destinoExistia && is_file($rutas['ruta_absoluta'])) {
                @unlink($rutas['ruta_absoluta']);
            }
            throw $e;
        }
    }
}
