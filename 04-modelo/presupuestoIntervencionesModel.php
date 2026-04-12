<?php

require_once __DIR__ . '/conectDB.php';
require_once __DIR__ . '/presupuestoGeneradoModel.php';
require_once __DIR__ . '/presupuestoMailConfigModel.php';

if (!function_exists('normalizarAccionIntervencionPresupuesto')) {
    function normalizarAccionIntervencionPresupuesto(string $accion): ?string
    {
        $accion = strtolower(trim($accion));
        $permitidas = [
            'guardar',
            'imprimir',
            'emitir',
            'enviar_mail',
            'simular_envio_mail',
            'fallo_envio_mail',
            'recibido',
            'resolicitado',
            'aprobado',
            'rechazado',
            'cancelado',
        ];

        return in_array($accion, $permitidas, true) ? $accion : null;
    }
}

if (!function_exists('etiquetaAccionIntervencionPresupuesto')) {
    function etiquetaAccionIntervencionPresupuesto(string $accion): string
    {
        $map = [
            'guardar' => 'Guardar',
            'imprimir' => 'Emitir documento',
            'emitir' => 'Emitir documento',
            'enviar_mail' => 'Enviar por mail',
            'simular_envio_mail' => 'Simular envio por mail',
            'fallo_envio_mail' => 'Fallo envio por mail',
            'recibido' => 'Recibido',
            'resolicitado' => 'Resolicitado',
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
            'cancelado' => 'Cancelado',
        ];

        return $map[$accion] ?? ucfirst($accion);
    }
}

if (!function_exists('esAccionComercialPresupuesto')) {
    function esAccionComercialPresupuesto(string $accion): bool
    {
        return in_array(
            normalizarAccionIntervencionPresupuesto($accion),
            ['enviar_mail', 'simular_envio_mail', 'recibido', 'resolicitado', 'aprobado', 'rechazado', 'cancelado'],
            true
        );
    }
}

if (!function_exists('esAccionEstadoComercialPresupuesto')) {
    function esAccionEstadoComercialPresupuesto(string $accion): bool
    {
        return in_array(
            normalizarAccionIntervencionPresupuesto($accion),
            ['recibido', 'resolicitado', 'aprobado', 'rechazado', 'cancelado'],
            true
        );
    }
}

if (!function_exists('estadoDestinoAccionComercialPresupuesto')) {
    function estadoDestinoAccionComercialPresupuesto(string $accion): ?string
    {
        $map = [
            'recibido' => 'Recibido',
            'resolicitado' => 'Resolicitado',
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
            'cancelado' => 'Cancelado',
        ];

        $accion = normalizarAccionIntervencionPresupuesto($accion) ?? '';
        return $map[$accion] ?? null;
    }
}

if (!function_exists('normalizarEstadoPresupuestoIntervencion')) {
    function normalizarEstadoPresupuestoIntervencion(?string $estado): string
    {
        $estado = strtoupper(trim((string)$estado));
        if ($estado === '') {
            return 'PENDIENTE';
        }

        if ($estado === 'IMPRESO') {
            return 'EMITIDO';
        }

        return $estado;
    }
}

if (!function_exists('etiquetaEstadoPresupuestoIntervencion')) {
    function etiquetaEstadoPresupuestoIntervencion(?string $estado): string
    {
        $map = [
            'PENDIENTE' => 'Pendiente',
            'BORRADOR' => 'Borrador',
            'EMITIDO' => 'Emitido',
            'ENVIADO' => 'Enviado',
            'RECIBIDO' => 'Recibido',
            'RESOLICITADO' => 'Resolicitado',
            'APROBADO' => 'Aprobado',
            'RECHAZADO' => 'Rechazado',
            'CANCELADO' => 'Cancelado',
        ];

        $key = normalizarEstadoPresupuestoIntervencion($estado);
        return $map[$key] ?? ucfirst(strtolower($key));
    }
}

if (!function_exists('badgeEstadoPresupuestoIntervencion')) {
    function badgeEstadoPresupuestoIntervencion(?string $estado): string
    {
        $map = [
            'PENDIENTE' => 'badge-danger',
            'BORRADOR' => 'badge-secondary',
            'EMITIDO' => 'badge-info',
            'ENVIADO' => 'badge-primary',
            'RECIBIDO' => 'badge-success',
            'RESOLICITADO' => 'badge-warning',
            'APROBADO' => 'badge-success',
            'RECHAZADO' => 'badge-danger',
            'CANCELADO' => 'badge-dark',
        ];

        $key = normalizarEstadoPresupuestoIntervencion($estado);
        return $map[$key] ?? 'badge-secondary';
    }
}

if (!function_exists('formatearFechaIntervencionPresupuesto')) {
    function formatearFechaIntervencionPresupuesto(?string $fecha): string
    {
        if (!$fecha) {
            return '-';
        }

        $dt = date_create($fecha);
        if (!$dt) {
            return $fecha;
        }

        return $dt->format('d/m/Y H:i:s');
    }
}

if (!function_exists('escIntervencionPresupuesto')) {
    function escIntervencionPresupuesto(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('normalizarAccionHistorialComercialPresupuesto')) {
    function normalizarAccionHistorialComercialPresupuesto(string $accion): ?string
    {
        $accion = strtolower(trim($accion));
        $mapLegacy = [
            'enviar_mail' => 'enviado',
            'simular_envio_mail' => 'enviado',
        ];
        $accion = $mapLegacy[$accion] ?? $accion;

        $permitidas = [
            'enviado',
            'recibido',
            'resolicitado',
            'aprobado',
            'rechazado',
            'cancelado',
            'llamado',
            'mail_contacto',
            'mensaje_contacto',
            'pendiente_respuesta',
            'respondio',
        ];
        return in_array($accion, $permitidas, true) ? $accion : null;
    }
}

if (!function_exists('etiquetaAccionHistorialComercialPresupuesto')) {
    function etiquetaAccionHistorialComercialPresupuesto(string $accion): string
    {
        $map = [
            'enviado' => 'Enviado',
            'recibido' => 'Recibido',
            'resolicitado' => 'Resolicitado',
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
            'cancelado' => 'Cancelado',
            'llamado' => 'Llamado',
            'mail_contacto' => 'Mail de seguimiento',
            'mensaje_contacto' => 'Mensaje enviado',
            'pendiente_respuesta' => 'Pendiente de respuesta',
            'respondio' => 'Respondio',
        ];

        $accion = normalizarAccionHistorialComercialPresupuesto($accion) ?? strtolower(trim($accion));
        return $map[$accion] ?? ucfirst($accion);
    }
}

if (!function_exists('normalizarEstadoComercialPresupuesto')) {
    function normalizarEstadoComercialPresupuesto(?string $estado): string
    {
        $estado = strtoupper(trim((string)$estado));
        return in_array($estado, ['ENVIADO', 'RECIBIDO', 'RESOLICITADO', 'APROBADO', 'RECHAZADO', 'CANCELADO'], true)
            ? $estado
            : '';
    }
}

if (!function_exists('tablaHistorialComercialPresupuestoExiste')) {
    function tablaHistorialComercialPresupuestoExiste(mysqli $db): bool
    {
        return tabla_existe($db, 'presupuesto_historial_comercial');
    }
}

if (!function_exists('columnaComentariosHistorialComercialPresupuestoExiste')) {
    function columnaComentariosHistorialComercialPresupuestoExiste(mysqli $db): bool
    {
        return columna_existe($db, 'presupuesto_historial_comercial', 'comentarios');
    }
}

if (!function_exists('normalizarComentariosHistorialComercialPresupuesto')) {
    function normalizarComentariosHistorialComercialPresupuesto(?string $comentario): ?string
    {
        $comentario = str_replace(["\r\n", "\r"], "\n", (string)$comentario);
        $comentario = trim($comentario);

        return $comentario !== '' ? $comentario : null;
    }
}

if (!function_exists('normalizarListaEmailsHistorialComercialPresupuesto')) {
    function normalizarListaEmailsHistorialComercialPresupuesto($raw): array
    {
        if (is_array($raw)) {
            $items = $raw;
        } else {
            $texto = str_replace(["\r", "\n", ';'], ',', (string)$raw);
            $items = explode(',', $texto);
        }

        $limpios = [];
        foreach ($items as $item) {
            $email = function_exists('normalizarEmailMailPresupuestos')
                ? normalizarEmailMailPresupuestos((string)$item)
                : strtolower(trim((string)$item));

            if ($email === '') {
                continue;
            }

            $limpios[$email] = $email;
        }

        return array_values($limpios);
    }
}

if (!function_exists('obtenerEmailsCopiasOcultasPorDefectoHistorialComercialPresupuesto')) {
    function obtenerEmailsCopiasOcultasPorDefectoHistorialComercialPresupuesto(): array
    {
        if (!function_exists('obtenerCopiasActivasPorDefectoMailPresupuestos')) {
            return [];
        }

        $emails = [];
        foreach (obtenerCopiasActivasPorDefectoMailPresupuestos() as $item) {
            $tipo = strtolower(trim((string)($item['tipo'] ?? 'cco')));
            if ($tipo !== 'cco') {
                continue;
            }

            $email = function_exists('normalizarEmailMailPresupuestos')
                ? normalizarEmailMailPresupuestos((string)($item['email'] ?? ''))
                : strtolower(trim((string)($item['email'] ?? '')));

            if ($email === '') {
                continue;
            }

            $emails[$email] = $email;
        }

        return array_values($emails);
    }
}

if (!function_exists('construirComentarioEnvioFallbackHistorialComercialPresupuesto')) {
    function construirComentarioEnvioFallbackHistorialComercialPresupuesto($paraRaw, $ccoRaw): ?string
    {
        $paraNormalizados = normalizarListaEmailsHistorialComercialPresupuesto($paraRaw);
        $ccoNormalizados = normalizarListaEmailsHistorialComercialPresupuesto($ccoRaw);
        $ccoPorDefecto = obtenerEmailsCopiasOcultasPorDefectoHistorialComercialPresupuesto();
        $ccoAgregadas = array_values(array_diff($ccoNormalizados, $ccoPorDefecto));
        $partes = [];

        if ($paraNormalizados) {
            $partes[] = count($paraNormalizados) > 1
                ? 'Destinatarios: ' . implode(', ', $paraNormalizados)
                : 'Destinatario: ' . $paraNormalizados[0];
        }

        if ($ccoAgregadas) {
            $partes[] = 'Copias ocultas agregadas: ' . implode(', ', $ccoAgregadas);
        }

        return normalizarComentariosHistorialComercialPresupuesto(implode(' | ', $partes));
    }
}

if (!function_exists('obtenerComentarioEnvioFallbackHistorialComercialPresupuestoEnConexion')) {
    function obtenerComentarioEnvioFallbackHistorialComercialPresupuestoEnConexion(mysqli $db, ?int $idEnvio): ?string
    {
        static $cache = [];

        $idEnvio = (int)$idEnvio;
        if ($idEnvio <= 0) {
            return null;
        }

        if (array_key_exists($idEnvio, $cache)) {
            return $cache[$idEnvio];
        }

        if (!tabla_existe($db, 'presupuesto_documentos_emitidos_envios')) {
            $cache[$idEnvio] = null;
            return null;
        }

        $sql = "
            SELECT
                para_email,
                cco
            FROM presupuesto_documentos_emitidos_envios
            WHERE id_envio = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            $cache[$idEnvio] = null;
            return null;
        }

        mysqli_stmt_bind_param($stmt, 'i', $idEnvio);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        $cache[$idEnvio] = $row
            ? construirComentarioEnvioFallbackHistorialComercialPresupuesto(
                (string)($row['para_email'] ?? ''),
                (string)($row['cco'] ?? '')
            )
            : null;

        return $cache[$idEnvio];
    }
}

if (!function_exists('obtenerEstadoComercialActivoDesdePresupuesto')) {
    function obtenerEstadoComercialActivoDesdePresupuesto(array $presupuesto, ?string $modo = null): string
    {
        $modo = $modo ?: obtenerModoActivoCircuitoComercialPresupuestos();
        $columna = columnaEstadoComercialPresupuestoPorModo($modo);
        return normalizarEstadoComercialPresupuesto($presupuesto[$columna] ?? '');
    }
}

if (!function_exists('resolverEstadoVisiblePresupuestoDesdePresupuesto')) {
    function resolverEstadoVisiblePresupuestoDesdePresupuesto(array $presupuesto, ?string $modo = null): string
    {
        $estadoComercial = obtenerEstadoComercialActivoDesdePresupuesto($presupuesto, $modo);
        if ($estadoComercial !== '') {
            return $estadoComercial;
        }

        return normalizarEstadoPresupuestoIntervencion($presupuesto['estado'] ?? '');
    }
}

if (!function_exists('metaAccionesEstadoComercialPresupuesto')) {
    function metaAccionesEstadoComercialPresupuesto(): array
    {
        return [
            'recibido' => [
                'accion' => 'recibido',
                'label' => 'Recibido',
                'icon' => 'fas fa-inbox',
                'button_class' => 'btn-success',
                'confirm_title' => 'Marcar presupuesto como recibido',
                'confirm_text' => 'Se va a registrar un nuevo evento comercial para este presupuesto.',
            ],
            'resolicitado' => [
                'accion' => 'resolicitado',
                'label' => 'Resolicitado',
                'icon' => 'fas fa-redo-alt',
                'button_class' => 'btn-warning',
                'confirm_title' => 'Marcar presupuesto como resolicitado',
                'confirm_text' => 'Se va a registrar un nuevo evento comercial para este presupuesto.',
            ],
            'aprobado' => [
                'accion' => 'aprobado',
                'label' => 'Aprobado',
                'icon' => 'fas fa-check',
                'button_class' => 'btn-success',
                'confirm_title' => 'Aprobar presupuesto',
                'confirm_text' => 'Se va a registrar la aprobacion del presupuesto.',
            ],
            'rechazado' => [
                'accion' => 'rechazado',
                'label' => 'Rechazado',
                'icon' => 'fas fa-times',
                'button_class' => 'btn-danger',
                'confirm_title' => 'Rechazar presupuesto',
                'confirm_text' => 'Se va a registrar el rechazo del presupuesto.',
            ],
            'cancelado' => [
                'accion' => 'cancelado',
                'label' => 'Cancelado',
                'icon' => 'fas fa-ban',
                'button_class' => 'btn-secondary',
                'confirm_title' => 'Cancelar presupuesto',
                'confirm_text' => 'Se va a registrar la cancelacion del presupuesto.',
            ],
        ];
    }
}

if (!function_exists('metaAccionesContactoComercialPresupuesto')) {
    function metaAccionesContactoComercialPresupuesto(): array
    {
        return [
            'llamado' => [
                'accion' => 'llamado',
                'label' => 'Llamado',
                'icon' => 'fas fa-phone',
                'confirm_title' => 'Registrar llamado',
                'confirm_text' => 'Se va a registrar un llamado de seguimiento sobre este presupuesto.',
            ],
            'mail_contacto' => [
                'accion' => 'mail_contacto',
                'label' => 'Mail',
                'icon' => 'fas fa-envelope',
                'confirm_title' => 'Registrar mail de seguimiento',
                'confirm_text' => 'Se va a registrar un mail de seguimiento sobre este presupuesto.',
            ],
            'mensaje_contacto' => [
                'accion' => 'mensaje_contacto',
                'label' => 'Mensaje',
                'icon' => 'fas fa-comment',
                'confirm_title' => 'Registrar mensaje enviado',
                'confirm_text' => 'Se va a registrar un mensaje de seguimiento sobre este presupuesto.',
            ],
            'pendiente_respuesta' => [
                'accion' => 'pendiente_respuesta',
                'label' => 'Pendiente de respuesta',
                'icon' => 'fas fa-hourglass',
                'confirm_title' => 'Marcar pendiente de respuesta',
                'confirm_text' => 'Se va a registrar que el presupuesto quedo pendiente de respuesta.',
            ],
            'respondio' => [
                'accion' => 'respondio',
                'label' => 'Respondio',
                'icon' => 'fas fa-reply',
                'confirm_title' => 'Registrar respuesta del cliente',
                'confirm_text' => 'Se va a registrar que el cliente respondio sobre este presupuesto.',
            ],
        ];
    }
}

if (!function_exists('obtenerAccionesDisponiblesHistorialComercialPresupuesto')) {
    function obtenerAccionesDisponiblesHistorialComercialPresupuesto(?string $estadoInterno, ?string $estadoComercialActivo = null): array
    {
        $estado = normalizarEstadoComercialPresupuesto($estadoComercialActivo);
        $estadoInterno = normalizarEstadoPresupuestoIntervencion($estadoInterno);
        $meta = metaAccionesEstadoComercialPresupuesto();

        $permitidas = [];

        if ($estado === 'ENVIADO') {
            $permitidas = ['recibido', 'resolicitado', 'aprobado', 'rechazado', 'cancelado'];
        } elseif ($estado === 'RECIBIDO') {
            $permitidas = ['resolicitado', 'aprobado', 'rechazado', 'cancelado'];
        } elseif ($estado === 'RESOLICITADO') {
            $permitidas = ['cancelado'];
        } elseif ($estado === '' && $estadoInterno === 'ENVIADO') {
            $permitidas = ['recibido', 'resolicitado', 'aprobado', 'rechazado', 'cancelado'];
        }

        $items = [];
        foreach ($permitidas as $accion) {
            if (isset($meta[$accion])) {
                $items[] = $meta[$accion];
            }
        }

        return $items;
    }
}

if (!function_exists('obtenerAccionesContactoDisponiblesHistorialComercialPresupuesto')) {
    function obtenerAccionesContactoDisponiblesHistorialComercialPresupuesto(?string $estadoInterno, ?string $estadoComercialActivo = null): array
    {
        $estado = normalizarEstadoComercialPresupuesto($estadoComercialActivo);
        $estadoInterno = normalizarEstadoPresupuestoIntervencion($estadoInterno);
        $meta = metaAccionesContactoComercialPresupuesto();

        $circuitoActivo = $estado !== '' || $estadoInterno === 'ENVIADO';
        if (!$circuitoActivo || in_array($estado, ['APROBADO', 'RECHAZADO', 'CANCELADO'], true)) {
            return [];
        }

        return array_values($meta);
    }
}

if (!function_exists('obtenerPresupuestoActualPorPrevisita')) {
    function obtenerPresupuestoActualPorPrevisita(int $idPrevisita, ?int $idPresupuesto = null): ?array
    {
        if ($idPrevisita <= 0) {
            return null;
        }

        $db = conectDB();
        if (!$db) {
            return null;
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            $tieneEstadoComercialSimulacion = columna_existe($db, 'presupuestos', 'estado_comercial_simulacion');
            $tieneEstadoComercialSmtp = columna_existe($db, 'presupuestos', 'estado_comercial_smtp');
            $selectEstadoComercialSimulacion = $tieneEstadoComercialSimulacion
                ? 'estado_comercial_simulacion'
                : "'' AS estado_comercial_simulacion";
            $selectEstadoComercialSmtp = $tieneEstadoComercialSmtp
                ? 'estado_comercial_smtp'
                : "'' AS estado_comercial_smtp";

            if ($idPresupuesto !== null && $idPresupuesto > 0) {
                $sql = "
                    SELECT
                        id_presupuesto,
                        id_previsita,
                        estado,
                        {$selectEstadoComercialSimulacion},
                        {$selectEstadoComercialSmtp},
                        updated_at,
                        created_at
                    FROM presupuestos
                    WHERE id_previsita = ?
                      AND id_presupuesto = ?
                    LIMIT 1
                ";
                $stmt = mysqli_prepare($db, $sql);
                if (!$stmt) {
                    return null;
                }
                mysqli_stmt_bind_param($stmt, 'ii', $idPrevisita, $idPresupuesto);
            } else {
                $sql = "
                    SELECT
                        id_presupuesto,
                        id_previsita,
                        estado,
                        {$selectEstadoComercialSimulacion},
                        {$selectEstadoComercialSmtp},
                        updated_at,
                        created_at
                    FROM presupuestos
                    WHERE id_previsita = ?
                    ORDER BY updated_at DESC, created_at DESC, id_presupuesto DESC
                    LIMIT 1
                ";
                $stmt = mysqli_prepare($db, $sql);
                if (!$stmt) {
                    return null;
                }
                mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
            }

            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (!$row) {
                return null;
            }

            return [
                'id_presupuesto' => (int)($row['id_presupuesto'] ?? 0),
                'id_previsita' => (int)($row['id_previsita'] ?? 0),
                'estado' => (string)($row['estado'] ?? ''),
                'estado_comercial_simulacion' => (string)($row['estado_comercial_simulacion'] ?? ''),
                'estado_comercial_smtp' => (string)($row['estado_comercial_smtp'] ?? ''),
            ];
        } catch (Throwable $e) {
            return null;
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('obtenerIdUltimoDocumentoEmitidoPresupuestoEnConexion')) {
    function obtenerIdUltimoDocumentoEmitidoPresupuestoEnConexion(mysqli $db, int $idPresupuesto): ?int
    {
        if ($idPresupuesto <= 0 || !tabla_existe($db, 'presupuesto_documentos_emitidos')) {
            return null;
        }

        $sql = "
            SELECT id_documento_emitido
            FROM presupuesto_documentos_emitidos
            WHERE id_presupuesto = ?
            ORDER BY created_at DESC, id_documento_emitido DESC
            LIMIT 1
        ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, 'i', $idPresupuesto);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return isset($row['id_documento_emitido']) ? (int)$row['id_documento_emitido'] : null;
    }
}

if (!function_exists('actualizarEstadoComercialPresupuestoEnConexion')) {
    function actualizarEstadoComercialPresupuestoEnConexion(mysqli $db, int $idPresupuesto, string $modo, ?string $estadoDestino): bool
    {
        $columna = columnaEstadoComercialPresupuestoPorModo($modo);
        $estadoDestino = normalizarEstadoComercialPresupuesto($estadoDestino);

        if (!columna_existe($db, 'presupuestos', $columna)) {
            throw new RuntimeException('La columna de estado comercial para el modo activo no existe en la base de datos.');
        }

        $sql = $estadoDestino === ''
            ? "UPDATE presupuestos SET {$columna} = NULL, updated_at = NOW() WHERE id_presupuesto = ?"
            : "UPDATE presupuestos SET {$columna} = ?, updated_at = NOW() WHERE id_presupuesto = ?";

        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            throw new RuntimeException('No se pudo preparar la actualizacion del estado comercial.');
        }

        if ($estadoDestino === '') {
            mysqli_stmt_bind_param($stmt, 'i', $idPresupuesto);
        } else {
            mysqli_stmt_bind_param($stmt, 'si', $estadoDestino, $idPresupuesto);
        }

        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!$ok) {
            throw new RuntimeException('No se pudo actualizar el estado comercial del presupuesto.');
        }

        return true;
    }
}

if (!function_exists('registrarHistorialComercialPresupuestoEnConexion')) {
    function registrarHistorialComercialPresupuestoEnConexion(
        mysqli $db,
        int $idPresupuesto,
        int $idPrevisita,
        ?int $idDocumentoEmitido,
        ?int $idEnvio,
        int $idUsuario,
        string $modoCircuito,
        string $accion,
        string $estadoResultante,
        ?string $comentarios = null
    ): int {
        if (!tablaHistorialComercialPresupuestoExiste($db)) {
            throw new RuntimeException('La tabla de historial comercial no existe en la base de datos.');
        }

        $modoCircuito = normalizarModoEnvioMailPresupuestos($modoCircuito);
        $accion = normalizarAccionHistorialComercialPresupuesto($accion) ?? '';
        $estadoResultante = normalizarEstadoComercialPresupuesto($estadoResultante);
        $comentarios = normalizarComentariosHistorialComercialPresupuesto($comentarios);

        if ($accion === '' || $estadoResultante === '') {
            throw new RuntimeException('No se pudo resolver el evento comercial a registrar.');
        }

        $tieneColumnaComentarios = columnaComentariosHistorialComercialPresupuestoExiste($db);
        $sql = $tieneColumnaComentarios
            ? "
                INSERT INTO presupuesto_historial_comercial
                    (
                        id_presupuesto,
                        id_previsita,
                        id_documento_emitido,
                        id_envio,
                        id_usuario,
                        modo_circuito,
                        accion,
                        estado_resultante,
                        comentarios,
                        created_at
                    )
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            "
            : "
                INSERT INTO presupuesto_historial_comercial
                    (
                        id_presupuesto,
                        id_previsita,
                        id_documento_emitido,
                        id_envio,
                        id_usuario,
                        modo_circuito,
                        accion,
                        estado_resultante,
                        created_at
                    )
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            throw new RuntimeException('No se pudo preparar el registro del historial comercial.');
        }

        if ($tieneColumnaComentarios) {
            $comentariosSql = $comentarios ?? '';
            mysqli_stmt_bind_param(
                $stmt,
                'iiiiissss',
                $idPresupuesto,
                $idPrevisita,
                $idDocumentoEmitido,
                $idEnvio,
                $idUsuario,
                $modoCircuito,
                $accion,
                $estadoResultante,
                $comentariosSql
            );
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                'iiiiisss',
                $idPresupuesto,
                $idPrevisita,
                $idDocumentoEmitido,
                $idEnvio,
                $idUsuario,
                $modoCircuito,
                $accion,
                $estadoResultante
            );
        }
        mysqli_stmt_execute($stmt);
        $idHistorial = mysqli_insert_id($db);
        mysqli_stmt_close($stmt);

        return $idHistorial;
    }
}

if (!function_exists('obtenerHistorialComercialPresupuestoItems')) {
    function obtenerHistorialComercialPresupuestoItems(int $idPrevisita, int $idPresupuesto, ?string $modoCircuito = null): array
    {
        if ($idPrevisita <= 0 || $idPresupuesto <= 0) {
            return [];
        }

        $db = conectDB();
        if (!$db) {
            return [];
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            if (!tablaHistorialComercialPresupuestoExiste($db)) {
                return [];
            }

            $modoCircuito = normalizarModoEnvioMailPresupuestos($modoCircuito ?: obtenerModoActivoCircuitoComercialPresupuestos());
            $selectComentarios = columnaComentariosHistorialComercialPresupuestoExiste($db)
                ? 'h.comentarios,'
                : "'' AS comentarios,";
            $sql = "
                SELECT
                    h.id_historial_comercial,
                    h.id_usuario,
                    h.id_previsita,
                    h.id_presupuesto,
                    h.id_documento_emitido,
                    h.id_envio,
                    h.modo_circuito,
                    h.accion,
                    h.estado_resultante,
                    {$selectComentarios}
                    h.created_at,
                    u.apellidos,
                    u.nombres
                FROM presupuesto_historial_comercial h
                LEFT JOIN usuarios u
                    ON u.id_usuario = h.id_usuario
                WHERE h.id_previsita = ?
                  AND h.id_presupuesto = ?
                  AND h.modo_circuito = ?
                ORDER BY h.created_at DESC, h.id_historial_comercial DESC
            ";
            $stmt = mysqli_prepare($db, $sql);
            if (!$stmt) {
                return [];
            }

            mysqli_stmt_bind_param($stmt, 'iis', $idPrevisita, $idPresupuesto, $modoCircuito);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $rows = [];

            while ($res && ($row = mysqli_fetch_assoc($res))) {
                $apellido = trim((string)($row['apellidos'] ?? ''));
                $nombre = trim((string)($row['nombres'] ?? ''));
                $usuarioNombre = trim($apellido . ' ' . $nombre);
                if ($usuarioNombre === '') {
                    $usuarioNombre = 'Usuario #' . (int)($row['id_usuario'] ?? 0);
                }

                $accion = normalizarAccionHistorialComercialPresupuesto((string)($row['accion'] ?? '')) ?? '';
                if ($accion === '') {
                    continue;
                }

                $comentarios = normalizarComentariosHistorialComercialPresupuesto((string)($row['comentarios'] ?? '')) ?? '';
                if ($comentarios === '' && $accion === 'enviado') {
                    $comentarios = obtenerComentarioEnvioFallbackHistorialComercialPresupuestoEnConexion(
                        $db,
                        isset($row['id_envio']) ? (int)$row['id_envio'] : null
                    ) ?? '';
                }

                $rows[] = [
                    'id_historial_comercial' => (int)($row['id_historial_comercial'] ?? 0),
                    'id_usuario' => (int)($row['id_usuario'] ?? 0),
                    'id_previsita' => (int)($row['id_previsita'] ?? 0),
                    'id_presupuesto' => (int)($row['id_presupuesto'] ?? 0),
                    'id_documento_emitido' => isset($row['id_documento_emitido']) ? (int)$row['id_documento_emitido'] : null,
                    'id_envio' => isset($row['id_envio']) ? (int)$row['id_envio'] : null,
                    'modo_circuito' => (string)($row['modo_circuito'] ?? $modoCircuito),
                    'accion' => $accion,
                    'accion_label' => etiquetaAccionHistorialComercialPresupuesto($accion),
                    'estado_resultante' => normalizarEstadoComercialPresupuesto((string)($row['estado_resultante'] ?? '')),
                    'comentarios' => $comentarios,
                    'created_at' => (string)($row['created_at'] ?? ''),
                    'fecha_texto' => formatearFechaIntervencionPresupuesto((string)($row['created_at'] ?? '')),
                    'usuario_nombre' => $usuarioNombre,
                ];
            }

            mysqli_stmt_close($stmt);
            return $rows;
        } catch (Throwable $e) {
            return [];
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('obtenerIntervencionesPresupuesto')) {
    function obtenerIntervencionesPresupuesto(int $idPrevisita, ?int $idPresupuesto = null, ?array $accionesPermitidas = null): array
    {
        if ($idPrevisita <= 0) {
            return [];
        }

        $db = conectDB();
        if (!$db) {
            return [];
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            if ($idPresupuesto !== null && $idPresupuesto > 0) {
                $sql = "
                    SELECT
                        sg.id_seguimiento,
                        sg.id_usuario,
                        sg.id_previsita,
                        sg.id_presupuesto,
                        sg.accion,
                        sg.created_at,
                        u.apellidos,
                        u.nombres
                    FROM seguimiento_guardados sg
                    LEFT JOIN usuarios u
                        ON u.id_usuario = sg.id_usuario
                    WHERE sg.modulo = 3
                      AND sg.id_previsita = ?
                      AND sg.id_presupuesto = ?
                    ORDER BY sg.created_at DESC, sg.id_seguimiento DESC
                ";

                $stmt = mysqli_prepare($db, $sql);
                if (!$stmt) {
                    return [];
                }

                mysqli_stmt_bind_param($stmt, 'ii', $idPrevisita, $idPresupuesto);
            } else {
                $sql = "
                    SELECT
                        sg.id_seguimiento,
                        sg.id_usuario,
                        sg.id_previsita,
                        sg.id_presupuesto,
                        sg.accion,
                        sg.created_at,
                        u.apellidos,
                        u.nombres
                    FROM seguimiento_guardados sg
                    LEFT JOIN usuarios u
                        ON u.id_usuario = sg.id_usuario
                    WHERE sg.modulo = 3
                      AND sg.id_previsita = ?
                    ORDER BY sg.created_at DESC, sg.id_seguimiento DESC
                ";

                $stmt = mysqli_prepare($db, $sql);
                if (!$stmt) {
                    return [];
                }

                mysqli_stmt_bind_param($stmt, 'i', $idPrevisita);
            }

            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $rows = [];
            $accionesNormalizadas = is_array($accionesPermitidas)
                ? array_values(array_filter(array_map('normalizarAccionIntervencionPresupuesto', $accionesPermitidas)))
                : null;

            while ($res && ($row = mysqli_fetch_assoc($res))) {
                $apellido = trim((string)($row['apellidos'] ?? ''));
                $nombre = trim((string)($row['nombres'] ?? ''));
                $usuarioNombre = trim($apellido . ' ' . $nombre);

                if ($usuarioNombre === '') {
                    $usuarioNombre = 'Usuario #' . (int)($row['id_usuario'] ?? 0);
                }

                $accion = normalizarAccionIntervencionPresupuesto((string)($row['accion'] ?? ''))
                    ?? strtolower(trim((string)($row['accion'] ?? '')));

                if ($accionesNormalizadas !== null && !in_array($accion, $accionesNormalizadas, true)) {
                    continue;
                }

                $rows[] = [
                    'id_seguimiento' => (int)($row['id_seguimiento'] ?? 0),
                    'id_usuario' => (int)($row['id_usuario'] ?? 0),
                    'id_previsita' => (int)($row['id_previsita'] ?? 0),
                    'id_presupuesto' => isset($row['id_presupuesto']) ? (int)$row['id_presupuesto'] : null,
                    'accion' => $accion,
                    'accion_label' => etiquetaAccionIntervencionPresupuesto($accion),
                    'created_at' => (string)($row['created_at'] ?? ''),
                    'fecha_texto' => formatearFechaIntervencionPresupuesto((string)($row['created_at'] ?? '')),
                    'usuario_nombre' => $usuarioNombre,
                ];
            }

            mysqli_stmt_close($stmt);

            return $rows;
        } catch (Throwable $e) {
            return [];
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('obtenerIntervencionesInternasPresupuesto')) {
    function obtenerIntervencionesInternasPresupuesto(int $idPrevisita, ?int $idPresupuesto = null): array
    {
        return obtenerIntervencionesPresupuesto(
            $idPrevisita,
            $idPresupuesto,
            ['guardar', 'imprimir', 'emitir', 'fallo_envio_mail']
        );
    }
}

if (!function_exists('construirResumenIntervencionesPresupuesto')) {
    function construirResumenIntervencionesPresupuesto(array $rows): array
    {
        $headerHtml = '<thead><tr><th>Usuario</th><th>Accion</th><th>Fecha</th></tr></thead>';
        $tablaVacia = '<table class="table table-sm mb-0">'
            . $headerHtml
            . '<tbody><tr><td colspan="3" class="text-center text-muted">Sin otras intervenciones</td></tr></tbody>'
            . '</table>';

        if (empty($rows)) {
            return [
                'ultimo_texto' => 'Sin intervenciones',
                'popover_html' => $tablaVacia,
                'total' => 0,
                'items' => [],
            ];
        }

        $ultimo = $rows[0];
        $ultimoTexto = $ultimo['usuario_nombre']
            . ' | ' . $ultimo['accion_label']
            . ' | ' . $ultimo['fecha_texto'];

        $otros = array_slice($rows, 1);
        $html = '<table class="table table-sm mb-0">' . $headerHtml . '<tbody>';

        if ($otros) {
            foreach ($otros as $item) {
                $html .= '<tr>'
                    . '<td>' . escIntervencionPresupuesto($item['usuario_nombre']) . '</td>'
                    . '<td>' . escIntervencionPresupuesto($item['accion_label']) . '</td>'
                    . '<td>' . escIntervencionPresupuesto($item['fecha_texto']) . '</td>'
                    . '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="3" class="text-center text-muted">Sin otras intervenciones</td></tr>';
        }

        $html .= '</tbody></table>';

        return [
            'ultimo_texto' => $ultimoTexto,
            'popover_html' => $html,
            'total' => count($rows),
            'items' => $rows,
        ];
    }
}

if (!function_exists('obtenerResumenIntervencionesPresupuesto')) {
    function obtenerResumenIntervencionesPresupuesto(int $idPrevisita, ?int $idPresupuesto = null): array
    {
        return construirResumenIntervencionesPresupuesto(
            obtenerIntervencionesInternasPresupuesto($idPrevisita, $idPresupuesto)
        );
    }
}

if (!function_exists('obtenerHistorialComercialPresupuesto')) {
    function obtenerHistorialComercialPresupuesto(int $idPrevisita, ?int $idPresupuesto = null): array
    {
        if ($idPrevisita <= 0) {
            return [
                'ok' => false,
                'msg' => 'Pre-visita invalida.',
                'items' => [],
                'total' => 0,
            ];
        }

        $presupuesto = obtenerPresupuestoActualPorPrevisita($idPrevisita, $idPresupuesto);
        $idPresupuestoActual = (int)($presupuesto['id_presupuesto'] ?? 0);
        $modoCircuito = obtenerModoActivoCircuitoComercialPresupuestos();
        $estadoInterno = $presupuesto ? normalizarEstadoPresupuestoIntervencion($presupuesto['estado'] ?? '') : 'PENDIENTE';
        $estadoComercialActivo = $presupuesto ? obtenerEstadoComercialActivoDesdePresupuesto($presupuesto, $modoCircuito) : '';
        $estadoVisible = $presupuesto ? resolverEstadoVisiblePresupuestoDesdePresupuesto($presupuesto, $modoCircuito) : 'PENDIENTE';
        $items = $idPresupuestoActual > 0
            ? obtenerHistorialComercialPresupuestoItems($idPrevisita, $idPresupuestoActual, $modoCircuito)
            : [];

        if ($estadoComercialActivo === '' && !empty($items)) {
            $ultimoEstadoHistorial = normalizarEstadoComercialPresupuesto((string)($items[0]['estado_resultante'] ?? ''));
            if ($ultimoEstadoHistorial !== '') {
                $estadoComercialActivo = $ultimoEstadoHistorial;
                $estadoVisible = $ultimoEstadoHistorial;
            }
        }

        $accionesDisponibles = obtenerAccionesDisponiblesHistorialComercialPresupuesto($estadoInterno, $estadoComercialActivo);

        return [
            'ok' => true,
            'id_previsita' => $idPrevisita,
            'id_presupuesto' => $idPresupuestoActual > 0 ? $idPresupuestoActual : null,
            'modo_circuito' => $modoCircuito,
            'estado_interno' => $estadoInterno,
            'estado_comercial_activo' => $estadoComercialActivo,
            'estado_actual' => $estadoVisible,
            'estado_actual_label' => etiquetaEstadoPresupuestoIntervencion($estadoVisible),
            'estado_actual_badge_class' => badgeEstadoPresupuestoIntervencion($estadoVisible),
            'acciones_disponibles' => $accionesDisponibles,
            'acciones_contacto_disponibles' => obtenerAccionesContactoDisponiblesHistorialComercialPresupuesto($estadoInterno, $estadoComercialActivo),
            'items' => $items,
            'total' => count($items),
        ];
    }
}

if (!function_exists('insertarAccionIntervencionPresupuestoEnConexion')) {
    function insertarAccionIntervencionPresupuestoEnConexion(mysqli $db, int $idPresupuesto, int $idPrevisita, int $idUsuario, string $accion): int
    {
        $sql = "
            INSERT INTO seguimiento_guardados
                (id_usuario, modulo, id_previsita, id_presupuesto, accion)
            VALUES
                (?, 3, ?, ?, ?)
        ";
        $stmt = mysqli_prepare($db, $sql);
        if (!$stmt) {
            throw new RuntimeException('No se pudo preparar el registro de intervencion.');
        }

        mysqli_stmt_bind_param($stmt, 'iiis', $idUsuario, $idPrevisita, $idPresupuesto, $accion);
        mysqli_stmt_execute($stmt);
        $id = mysqli_insert_id($db);
        mysqli_stmt_close($stmt);

        return $id;
    }
}

if (!function_exists('registrarIntervencionPresupuesto')) {
    function registrarIntervencionPresupuesto(int $idPresupuesto, int $idPrevisita, int $idUsuario, string $accion): array
    {
        $accionNormalizada = normalizarAccionIntervencionPresupuesto($accion);

        if ($idPresupuesto <= 0 || $idPrevisita <= 0 || $idUsuario <= 0) {
            return ['ok' => false, 'msg' => 'Datos incompletos para registrar la intervencion.'];
        }

        if ($accionNormalizada === null) {
            return ['ok' => false, 'msg' => 'Accion de intervencion invalida.'];
        }

        $db = conectDB();
        if (!$db) {
            return ['ok' => false, 'msg' => 'No se pudo abrir conexion a la base de datos.'];
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            insertarAccionIntervencionPresupuestoEnConexion(
                $db,
                $idPresupuesto,
                $idPrevisita,
                $idUsuario,
                $accionNormalizada
            );

            return [
                'ok' => true,
                'intervino' => obtenerResumenIntervencionesPresupuesto($idPrevisita, $idPresupuesto),
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('registrarEstadoComercialPresupuesto')) {
    function registrarEstadoComercialPresupuesto(int $idPrevisita, int $idUsuario, string $accion, ?int $idPresupuesto = null, ?string $comentarios = null): array
    {
        $accionNormalizada = normalizarAccionHistorialComercialPresupuesto($accion);
        $modoCircuito = obtenerModoActivoCircuitoComercialPresupuestos();

        if ($idPrevisita <= 0 || $idUsuario <= 0) {
            return ['ok' => false, 'msg' => 'Faltan datos para actualizar el estado comercial.'];
        }

        if ($accionNormalizada === null || !in_array($accionNormalizada, ['recibido', 'resolicitado', 'aprobado', 'rechazado', 'cancelado'], true)) {
            return ['ok' => false, 'msg' => 'La accion comercial solicitada no es valida.'];
        }

        $historial = obtenerHistorialComercialPresupuesto($idPrevisita, $idPresupuesto);
        $idPresupuestoActual = (int)($historial['id_presupuesto'] ?? 0);

        if ($idPresupuestoActual <= 0) {
            return ['ok' => false, 'msg' => 'No se encontro un presupuesto activo para este seguimiento.'];
        }

        $accionesDisponibles = array_column((array)($historial['acciones_disponibles'] ?? []), 'accion');
        if (!in_array($accionNormalizada, $accionesDisponibles, true)) {
            return ['ok' => false, 'msg' => 'La accion no esta disponible para el estado actual del presupuesto.'];
        }

        $estadoDestino = estadoDestinoAccionComercialPresupuesto($accionNormalizada);
        if ($estadoDestino === null) {
            return ['ok' => false, 'msg' => 'No se pudo resolver el estado destino del presupuesto.'];
        }

        $db = conectDB();
        if (!$db) {
            return ['ok' => false, 'msg' => 'No se pudo abrir conexion a la base de datos.'];
        }

        mysqli_set_charset($db, 'utf8mb4');
        mysqli_begin_transaction($db);

        try {
            actualizarEstadoComercialPresupuestoEnConexion(
                $db,
                $idPresupuestoActual,
                $modoCircuito,
                $estadoDestino
            );

            registrarHistorialComercialPresupuestoEnConexion(
                $db,
                $idPresupuestoActual,
                $idPrevisita,
                obtenerIdUltimoDocumentoEmitidoPresupuestoEnConexion($db, $idPresupuestoActual),
                null,
                $idUsuario,
                $modoCircuito,
                $accionNormalizada,
                $estadoDestino,
                $comentarios
            );

            mysqli_commit($db);
        } catch (Throwable $e) {
            mysqli_rollback($db);
            mysqli_close($db);
            return ['ok' => false, 'msg' => $e->getMessage()];
        }

        mysqli_close($db);

        $historialActualizado = obtenerHistorialComercialPresupuesto($idPrevisita, $idPresupuestoActual);
        $historialActualizado['msg'] = 'Estado comercial actualizado correctamente.';

        return $historialActualizado;
    }
}

if (!function_exists('registrarContactoComercialPresupuesto')) {
    function registrarContactoComercialPresupuesto(int $idPrevisita, int $idUsuario, string $accion, ?int $idPresupuesto = null, ?string $comentarios = null): array
    {
        $accionNormalizada = normalizarAccionHistorialComercialPresupuesto($accion);
        $modoCircuito = obtenerModoActivoCircuitoComercialPresupuestos();
        $accionesPermitidas = ['llamado', 'mail_contacto', 'mensaje_contacto', 'pendiente_respuesta', 'respondio'];

        if ($idPrevisita <= 0 || $idUsuario <= 0) {
            return ['ok' => false, 'msg' => 'Faltan datos para registrar el contacto comercial.'];
        }

        if ($accionNormalizada === null || !in_array($accionNormalizada, $accionesPermitidas, true)) {
            return ['ok' => false, 'msg' => 'La accion de contacto solicitada no es valida.'];
        }

        $historial = obtenerHistorialComercialPresupuesto($idPrevisita, $idPresupuesto);
        $idPresupuestoActual = (int)($historial['id_presupuesto'] ?? 0);
        $estadoComercialActivo = normalizarEstadoComercialPresupuesto((string)($historial['estado_comercial_activo'] ?? ''));
        $estadoInterno = normalizarEstadoPresupuestoIntervencion((string)($historial['estado_interno'] ?? ''));

        if ($idPresupuestoActual <= 0) {
            return ['ok' => false, 'msg' => 'No se encontro un presupuesto activo para este seguimiento.'];
        }

        $accionesDisponibles = array_column((array)($historial['acciones_contacto_disponibles'] ?? []), 'accion');
        if (!in_array($accionNormalizada, $accionesDisponibles, true)) {
            return ['ok' => false, 'msg' => 'La accion de contacto no esta disponible para el estado actual del presupuesto.'];
        }

        $estadoResultante = $estadoComercialActivo !== '' ? $estadoComercialActivo : ($estadoInterno === 'ENVIADO' ? 'ENVIADO' : '');
        if ($estadoResultante === '') {
            return ['ok' => false, 'msg' => 'Todavia no hay un circuito comercial activo para registrar contactos.'];
        }

        $db = conectDB();
        if (!$db) {
            return ['ok' => false, 'msg' => 'No se pudo abrir conexion a la base de datos.'];
        }

        mysqli_set_charset($db, 'utf8mb4');
        mysqli_begin_transaction($db);

        try {
            registrarHistorialComercialPresupuestoEnConexion(
                $db,
                $idPresupuestoActual,
                $idPrevisita,
                obtenerIdUltimoDocumentoEmitidoPresupuestoEnConexion($db, $idPresupuestoActual),
                null,
                $idUsuario,
                $modoCircuito,
                $accionNormalizada,
                $estadoResultante,
                $comentarios
            );

            mysqli_commit($db);
        } catch (Throwable $e) {
            mysqli_rollback($db);
            mysqli_close($db);
            return ['ok' => false, 'msg' => $e->getMessage()];
        }

        mysqli_close($db);

        $historialActualizado = obtenerHistorialComercialPresupuesto($idPrevisita, $idPresupuestoActual);
        $historialActualizado['msg'] = 'Contacto comercial registrado correctamente.';

        return $historialActualizado;
    }
}
