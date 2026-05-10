<?php
// =========================================================================
// pdfExtractorModel.php
// Extracción de texto y patrones desde PDF de OC usando smalot/pdfparser.
// Solo lectura — no modifica BD ni archivos.
// =========================================================================

if (!class_exists('Smalot\PdfParser\Parser', false)) {
    $_autoloadPdfExt = realpath(__DIR__ . '/../vendor/autoload.php');
    if ($_autoloadPdfExt !== false && file_exists($_autoloadPdfExt)) {
        require_once $_autoloadPdfExt;
    }
    unset($_autoloadPdfExt);
}

// ---------------------------------------------------------------------------
// Extracción de texto
// ---------------------------------------------------------------------------

if (!function_exists('extraerTextoPdfOrdenCompra')) {
    function extraerTextoPdfOrdenCompra(string $ruta): array
    {
        $resultado = ['texto' => '', 'paginas' => 0, 'advertencias' => []];

        if ($ruta === '' || !is_file($ruta) || !is_readable($ruta)) {
            $resultado['advertencias'][] = 'El archivo PDF no está disponible en el servidor.';
            return $resultado;
        }

        if (!class_exists('Smalot\PdfParser\Parser')) {
            $resultado['advertencias'][] = 'El analizador de PDF no está disponible. Completá los datos manualmente.';
            return $resultado;
        }

        try {
            if (class_exists('Smalot\PdfParser\Config')) {
                $cfg = new \Smalot\PdfParser\Config();
                $cfg->setRetainImageContent(false);
                $parser = new \Smalot\PdfParser\Parser([], $cfg);
            } else {
                $parser = new \Smalot\PdfParser\Parser();
            }

            $pdf      = $parser->parseFile($ruta);
            $texto    = $pdf->getText();
            $detalles = $pdf->getDetails();

            $resultado['texto']   = is_string($texto) ? $texto : '';
            $resultado['paginas'] = isset($detalles['Pages']) ? (int) $detalles['Pages'] : 0;
        } catch (\Throwable $e) {
            $resultado['advertencias'][] = 'No se pudo procesar el PDF. Puede estar dañado o protegido.';
            return $resultado;
        }

        if (trim($resultado['texto']) === '') {
            $resultado['advertencias'][] =
                'El PDF no contiene texto extraíble. Puede ser un documento escaneado o basado en imágenes. Completá los datos manualmente.';
        }

        return $resultado;
    }
}

// ---------------------------------------------------------------------------
// Helpers internos de normalización (no wrapped — siempre son nuevas)
// ---------------------------------------------------------------------------

function _ocPdfNormalizarFecha(string $d, string $m, string $a): ?string
{
    $dia = (int) $d;
    $mes = (int) $m;
    $ano = (int) $a;
    if ($ano < 100) {
        $ano += 2000;
    }
    // Ambigüedad dd/mm vs mm/dd: asume dd/mm/yyyy (Argentina)
    if ($dia > 12 && $mes <= 12) {
        // OK: día > 12 confirma que el primer bloque es día
    } elseif ($mes > 12 && $dia <= 12) {
        [$dia, $mes] = [$mes, $dia];
    }
    // Si ambos <= 12 → dd/mm por convención argentina
    if ($mes < 1 || $mes > 12 || $dia < 1 || $dia > 31 || $ano < 2000 || $ano > 2099) {
        return null;
    }
    return sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
}

function _ocPdfNormalizarFechaTexto(string $dStr, string $mesNombre, string $aStr): ?string
{
    static $meses = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
        'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
        'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10,
        'noviembre' => 11, 'diciembre' => 12,
    ];
    $mesNombre = mb_strtolower(trim($mesNombre));
    if (!isset($meses[$mesNombre])) {
        return null;
    }
    return _ocPdfNormalizarFecha($dStr, (string) $meses[$mesNombre], $aStr);
}

function _ocPdfNormalizarMonto(string $raw): ?float
{
    $limpio = preg_replace('/[^\d.,]/', '', trim($raw));
    if ($limpio === '') {
        return null;
    }
    // Detectar separador decimal: si la última coma/punto viene seguida de 1-2 dígitos → decimal
    if (preg_match('/,(\d{1,2})$/', $limpio)) {
        $limpio = str_replace('.', '', $limpio);
        $limpio = str_replace(',', '.', $limpio);
    } elseif (preg_match('/\.(\d{1,2})$/', $limpio)) {
        $limpio = str_replace(',', '', $limpio);
    } else {
        $limpio = preg_replace('/[.,]/', '', $limpio);
    }
    $valor = (float) $limpio;
    return $valor > 0 ? $valor : null;
}

// ---------------------------------------------------------------------------
// Detección de patrones en el texto extraído
// ---------------------------------------------------------------------------

if (!function_exists('buscarPatronesOrdenCompraEnTexto')) {
    function buscarPatronesOrdenCompraEnTexto(string $texto): array
    {
        // Normalizar saltos de línea y espacios múltiples
        $textoLineas = $texto;
        $textoNorm   = preg_replace('/[^\S\n]+/', ' ', $texto);
        $textoPlano  = preg_replace('/\s+/', ' ', $texto);

        $campos = [];

        // --- numero_oc ---
        // Campo crítico: contexto fuerte obligatorio + el valor debe contener al menos un dígito.
        // Nunca se sugiere un fragmento de texto sin número (e.g. "ulares").
        $patronesNumOc = [
            // "Orden de Compra N°/Nro/# VALOR" o "Orden de Compra: VALOR"
            '/Orden\s+de\s+Compra\s+(?:N[°º]|Nro\.?|#|No\.?)\s*[:\-]?\s*([A-Z0-9][\w.\-\/]{2,20})/i',
            '/Orden\s+de\s+Compra\s*[:\-]\s*([A-Z0-9][\w.\-\/]{2,20})/i',
            // "N° OC" / "Nro OC" / "Nro. OC"
            '/\bN(?:ro\.?|[°º])\s*O\.?C\.?\s*[:\-]?\s*([A-Z0-9][\w.\-\/]{2,20})/i',
            // "OC N°" / "OC Nro" / "OC #"  (qualificador explícito obligatorio)
            '/\bO\.?C\.?\s+(?:N[°º]|Nro\.?|#|No\.?)\s*[:\-]?\s*([A-Z0-9][\w.\-\/]{2,20})/i',
            // "OC:" / "OC -"  (separador explícito obligatorio)
            '/\bO\.?C\.?\s*[:\-]\s*([A-Z0-9][\w.\-\/]{2,20})/i',
            // "Purchase Order" / "PO #" / "PO:"
            '/Purchase\s+Order\s+(?:N[°º]|Nro\.?|#|No\.?)?\s*[:\-]?\s*([A-Z0-9][\w.\-\/]{2,20})/i',
            '/\bPO\s*[:\-#]\s*([A-Z0-9][\w.\-\/]{2,20})/i',
        ];
        foreach ($patronesNumOc as $pat) {
            if (preg_match($pat, $textoPlano, $m)) {
                $val = trim($m[1]);
                // Mínimo 3 chars y al menos un dígito — sin esto no es un número de OC
                if (strlen($val) >= 3 && preg_match('/\d/', $val)) {
                    $campos['numero_oc'] = ['valor' => $val, 'fuente' => 'pdf', 'confianza' => 0.75];
                    break;
                }
            }
        }

        // --- fecha_emision ---
        // dd/mm/yyyy o dd-mm-yyyy
        if (preg_match('/\b(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})\b/', $textoPlano, $m)) {
            $fecha = _ocPdfNormalizarFecha($m[1], $m[2], $m[3]);
            if ($fecha !== null) {
                $campos['fecha_emision'] = ['valor' => $fecha, 'fuente' => 'pdf', 'confianza' => 0.80];
            }
        }
        // dd de mes de yyyy (texto)
        if (!isset($campos['fecha_emision'])) {
            $pat = '/(\d{1,2})\s+de\s+([a-záéíóúü]+)\s+de\s+(\d{4})/iu';
            if (preg_match($pat, $textoPlano, $m)) {
                $fecha = _ocPdfNormalizarFechaTexto($m[1], $m[2], $m[3]);
                if ($fecha !== null) {
                    $campos['fecha_emision'] = ['valor' => $fecha, 'fuente' => 'pdf', 'confianza' => 0.85];
                }
            }
        }

        // --- proveedor ---
        $patronesProv = [
            '/(?:Proveedor|PROVEEDOR)\s*[:\-]\s*([^\n\r]{5,80})/i',
            '/(?:A:\s*|Señores?:\s*|Sres\.?:\s*)([^\n\r]{5,80})/i',
        ];
        foreach ($patronesProv as $pat) {
            if (preg_match($pat, $textoLineas, $m)) {
                $val = trim($m[1]);
                if (strlen($val) >= 5) {
                    $campos['proveedor'] = ['valor' => $val, 'fuente' => 'pdf', 'confianza' => 0.60];
                    break;
                }
            }
        }

        // --- moneda ---
        if (preg_match('/\b(USD|U\.?S\.?D\.?|d[oó]lar(?:es)?|DOLARES|US\s*\$)\b/i', $textoPlano)) {
            $campos['moneda'] = ['valor' => 'USD', 'fuente' => 'pdf', 'confianza' => 0.90];
        } elseif (preg_match('/\b(EUR|euros?)\b/i', $textoPlano)) {
            $campos['moneda'] = ['valor' => 'EUR', 'fuente' => 'pdf', 'confianza' => 0.90];
        }
        // ARS es el default — no se sugiere para no interferir con el campo ya inicializado

        // --- monto_neto ---
        $patNeto = '/(?:SUBTOTAL|Subtotal|Monto\s+Neto|NETO|Importe\s+Neto)\s*[:\$]?\s*([\d.,]+(?:\s*[\d.,]+)?)/i';
        if (preg_match($patNeto, $textoPlano, $m)) {
            $val = _ocPdfNormalizarMonto(trim($m[1]));
            if ($val !== null) {
                $campos['monto_neto'] = ['valor' => (string) $val, 'fuente' => 'pdf', 'confianza' => 0.70];
            }
        }

        // --- total ---
        $patTotal = '/(?:TOTAL\s+(?:GENERAL|FINAL|OC|PEDIDO|COMPRA)?|Total\s+(?:General|Final|OC)?)\s*[:\$]?\s*([\d.,]+)/i';
        if (preg_match($patTotal, $textoPlano, $m)) {
            $val = _ocPdfNormalizarMonto(trim($m[1]));
            if ($val !== null) {
                $campos['total'] = ['valor' => (string) $val, 'fuente' => 'pdf', 'confianza' => 0.70];
            }
        }

        // --- condicion_pago ---
        $patCond = '/(?:Condici[oó]n\s+de\s+pago|Forma\s+de\s+pago|CONDICI[OÓ]N\s+DE\s+PAGO|PLAZO\s+DE\s+PAGO)\s*[:\-]\s*([^\n\r]{4,80})/i';
        if (preg_match($patCond, $textoLineas, $m)) {
            $val = trim($m[1]);
            if (strlen($val) >= 4) {
                $campos['condicion_pago'] = ['valor' => $val, 'fuente' => 'pdf', 'confianza' => 0.70];
            }
        }

        // --- direccion_entrega ---
        $patDir = '/(?:Lugar\s+de\s+entrega|Entrega\s+en|Direcci[oó]n\s+de\s+entrega|PUNTO\s+DE\s+ENTREGA|ENTREGA)\s*[:\-]\s*([^\n\r]{8,120})/i';
        if (preg_match($patDir, $textoLineas, $m)) {
            $val = trim($m[1]);
            if (strlen($val) >= 8) {
                $campos['direccion_entrega'] = ['valor' => $val, 'fuente' => 'pdf', 'confianza' => 0.65];
            }
        }

        // --- email_facturacion ---
        $patEmail = '/\b([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})\b/';
        if (preg_match_all($patEmail, $textoPlano, $ms)) {
            // El primer email encontrado suele ser el de facturación/contacto
            $campos['email_facturacion'] = ['valor' => $ms[1][0], 'fuente' => 'pdf', 'confianza' => 0.60];
            // Si hay un segundo email, podría ser contacto_sitio_email
            if (isset($ms[1][1]) && $ms[1][1] !== $ms[1][0]) {
                $campos['contacto_sitio_email'] = ['valor' => $ms[1][1], 'fuente' => 'pdf', 'confianza' => 0.55];
            }
        }

        // --- contacto_sitio_telefono ---
        $patTel = '/(?:Tel[eé]fono|Tel\.?|Cel(?:ular)?|Phone)\s*[:\-.]?\s*([+\d][\d\s\(\)\-\.]{6,20})/i';
        if (preg_match($patTel, $textoPlano, $m)) {
            $val = trim(preg_replace('/\s{2,}/', ' ', $m[1]));
            if (strlen($val) >= 6) {
                $campos['contacto_sitio_telefono'] = ['valor' => $val, 'fuente' => 'pdf', 'confianza' => 0.60];
            }
        }

        // --- observaciones_comerciales ---
        $patObs = '/(?:Observaciones?|OBSERVACIONES?|Notas?|NOTAS?)\s*[:\-]\s*([^\n\r]{10,200})/i';
        if (preg_match($patObs, $textoLineas, $m)) {
            $val = trim($m[1]);
            if (strlen($val) >= 10) {
                $campos['observaciones_comerciales'] = ['valor' => $val, 'fuente' => 'pdf', 'confianza' => 0.55];
            }
        }

        return $campos;
    }
}

// ---------------------------------------------------------------------------
// Datos del circuito (BD) para complementar sugerencias
// ---------------------------------------------------------------------------

if (!function_exists('obtenerDatosCircuitoParaOcAnalisis')) {
    function obtenerDatosCircuitoParaOcAnalisis(mysqli $db, int $idPresupuesto, int $idPrevisita): array
    {
        $datos = [
            'cliente_snapshot'      => null,
            'direccion_entrega'     => null,
            'sucursal_planta_sede'  => null,
        ];

        // cliente_snapshot desde presupuesto (función ya existente en ordenCompraModel.php)
        if (function_exists('obtenerPresupuestoBasicoParaOrdenCompra')) {
            $presupuesto = obtenerPresupuestoBasicoParaOrdenCompra($db, $idPresupuesto);
            if ($presupuesto && !empty($presupuesto['cliente_snapshot'])) {
                $datos['cliente_snapshot'] = (string) $presupuesto['cliente_snapshot'];
            }
        }

        // Dirección desde previsitas
        if (
            $idPrevisita > 0
            && function_exists('tabla_existe')
            && tabla_existe($db, 'previsitas')
        ) {
            $tieneCol = function_exists('columna_existe');

            $selCalle = ($tieneCol && columna_existe($db, 'previsitas', 'calle_visita'))
                ? 'calle_visita'    : "'' AS calle_visita";
            $selLoc   = ($tieneCol && columna_existe($db, 'previsitas', 'localidad_visita'))
                ? 'localidad_visita' : "'' AS localidad_visita";
            $selProv  = ($tieneCol && columna_existe($db, 'previsitas', 'provincia_visita'))
                ? 'provincia_visita' : "'' AS provincia_visita";

            $sql  = "SELECT $selCalle, $selLoc, $selProv FROM previsitas WHERE id_previsita = ? LIMIT 1";
            $stmt = mysqli_prepare($db, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = $res ? mysqli_fetch_assoc($res) : null;
                mysqli_stmt_close($stmt);

                if ($row) {
                    $partes = array_filter(array_map('trim', [
                        $row['calle_visita']    ?? '',
                        $row['localidad_visita'] ?? '',
                        $row['provincia_visita'] ?? '',
                    ]));
                    if ($partes) {
                        $datos['direccion_entrega'] = implode(', ', $partes);
                    }
                    $loc = trim($row['localidad_visita'] ?? '');
                    if ($loc !== '') {
                        $datos['sucursal_planta_sede'] = $loc;
                    }
                }
            }
        }

        return $datos;
    }
}

// ---------------------------------------------------------------------------
// Construcción del payload final de sugerencias
// ---------------------------------------------------------------------------

if (!function_exists('construirPayloadSugerenciasOc')) {
    function construirPayloadSugerenciasOc(array $desdePdf, array $desdeCircuito): array
    {
        $camposSugeridos   = [];
        $noDetectados      = [];
        $advertencias      = [];

        // Campos del circuito (confianza 1.0, fuente autorizada)
        $mapeoCircuito = [
            'cliente_snapshot'     => 'cliente_snapshot',
            'direccion_entrega'    => 'direccion_entrega',
            'sucursal_planta_sede' => 'sucursal_planta_sede',
        ];
        foreach ($mapeoCircuito as $campo => $clave) {
            if (!empty($desdeCircuito[$clave])) {
                $camposSugeridos[$campo] = [
                    'valor'     => (string) $desdeCircuito[$clave],
                    'fuente'    => 'circuito',
                    'confianza' => 1.0,
                ];
            }
        }

        // Campos desde PDF (solo si confianza >= 0.50)
        // PDF puede complementar o sobreescribir los campos de circuito (salvo cliente_snapshot)
        foreach ($desdePdf as $campo => $sugerencia) {
            if (empty($sugerencia['valor']) || (float)($sugerencia['confianza'] ?? 0) < 0.50) {
                $noDetectados[] = $campo;
                continue;
            }
            // cliente_snapshot viene del circuito con confianza 1.0 — el PDF no lo pisa
            if ($campo === 'cliente_snapshot' && isset($camposSugeridos['cliente_snapshot'])) {
                continue;
            }
            // Para campos de dirección: solo usar PDF si el circuito no lo tiene
            if (in_array($campo, ['direccion_entrega', 'sucursal_planta_sede'], true)
                && isset($camposSugeridos[$campo])
            ) {
                continue;
            }
            $camposSugeridos[$campo] = $sugerencia;
        }

        // Campos objetivo que no tienen sugerencia
        $camposObjetivo = [
            'numero_oc', 'fecha_emision', 'proveedor', 'moneda',
            'monto_neto', 'total', 'condicion_pago',
            'direccion_entrega', 'sucursal_planta_sede',
            'contacto_sitio', 'contacto_sitio_email', 'contacto_sitio_telefono',
            'email_facturacion', 'cliente_snapshot', 'observaciones_comerciales',
        ];
        foreach ($camposObjetivo as $campo) {
            if (!isset($camposSugeridos[$campo]) && !in_array($campo, $noDetectados, true)) {
                $noDetectados[] = $campo;
            }
        }

        if (!empty($noDetectados)) {
            $advertencias[] = 'No se detectaron datos para: ' . implode(', ', $noDetectados) . '. Completá esos campos manualmente.';
        }

        return [
            'campos_sugeridos'   => $camposSugeridos,
            'campos_no_detectados' => $noDetectados,
            'advertencias'       => $advertencias,
        ];
    }
}
