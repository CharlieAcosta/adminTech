<?php

require_once __DIR__ . '/schemaIntrospectionModel.php';
require_once __DIR__ . '/pedidoMaterialesConfigCorreoModel.php';
require_once __DIR__ . '/pedidoMaterialesPdfModel.php';

if (!function_exists('pedidoMaterialesEnviosTablasMinimasDisponibles')) {
    function pedidoMaterialesEnviosTablasMinimasDisponibles(mysqli $db): bool
    {
        return pedidoMaterialesPdfTablasMinimasDisponibles($db)
            && tabla_existe($db, 'pedido_materiales_pedido_envios');
    }
}

if (!function_exists('pedidoMaterialesMailSimulacionActiva')) {
    function pedidoMaterialesMailSimulacionActiva(): bool
    {
        $valor = strtolower(trim((string)getenv(
            'ADMINTECH_PEDIDO_MATERIALES_MAIL_SIMULACION'
        )));
        if (!in_array($valor, ['1', 'true', 'yes', 'on', 'si'], true)) {
            return false;
        }

        if (function_exists('admintechEsEntornoNoProductivo')) {
            return admintechEsEntornoNoProductivo();
        }

        $appEnv = strtolower(trim((string)(getenv('APP_ENV') ?: 'production')));
        return in_array(
            $appEnv,
            [
                'development',
                'dev',
                'local',
                'test',
                'testing',
                'qa',
                'staging',
                'preproduction',
                'preproduccion',
            ],
            true
        );
    }
}

if (!function_exists('normalizarDestinatariosEnvioPedidoMateriales')) {
    function normalizarDestinatariosEnvioPedidoMateriales(array $config): array
    {
        $to = normalizarListaDestinatariosCorreoPedidoMateriales(
            (string)($config['destinatarios_to'] ?? '')
        );
        $cc = array_values(array_diff(
            normalizarListaDestinatariosCorreoPedidoMateriales(
                (string)($config['destinatarios_cc'] ?? '')
            ),
            $to
        ));
        $bcc = array_values(array_diff(
            normalizarListaDestinatariosCorreoPedidoMateriales(
                (string)($config['destinatarios_bcc'] ?? '')
            ),
            $to,
            $cc
        ));

        return [
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
        ];
    }
}

if (!function_exists('serializarDestinatariosEnvioPedidoMateriales')) {
    function serializarDestinatariosEnvioPedidoMateriales(array $destinatarios): string
    {
        return implode(', ', array_values(array_unique(array_filter(array_map(
            static fn($email): string => normalizarEmailCorreoPedidoMateriales((string)$email),
            $destinatarios
        )))));
    }
}

if (!function_exists('sanitizarAsuntoEnvioPedidoMateriales')) {
    function sanitizarAsuntoEnvioPedidoMateriales(?string $asunto): string
    {
        $asunto = preg_replace('/[\r\n\t]+/', ' ', (string)$asunto);
        $asunto = trim((string)$asunto);

        if (function_exists('mb_substr')) {
            return mb_substr($asunto, 0, 255, 'UTF-8');
        }

        return substr($asunto, 0, 255);
    }
}

if (!function_exists('sanitizarCuerpoEnvioPedidoMateriales')) {
    function sanitizarCuerpoEnvioPedidoMateriales(?string $cuerpo): string
    {
        $cuerpo = str_replace(["\r\n", "\r"], "\n", (string)$cuerpo);
        $cuerpo = preg_replace("/\n{3,}/", "\n\n", $cuerpo);

        return trim((string)$cuerpo);
    }
}

if (!function_exists('reemplazarVariablesCorreoPedidoMateriales')) {
    function reemplazarVariablesCorreoPedidoMateriales(string $plantilla, array $cabecera): string
    {
        return strtr($plantilla, [
            '{numero_pedido}' => (string)(int)($cabecera['numero_pedido'] ?? 0),
            '{id_previsita}' => (string)(int)($cabecera['id_previsita'] ?? 0),
            '{id_presupuesto}' => (string)(int)($cabecera['id_presupuesto'] ?? 0),
            '{id_orden_compra}' => (string)(int)($cabecera['id_orden_compra'] ?? 0),
            '{fecha_confirmacion}' => trim((string)($cabecera['fecha_confirmacion'] ?? '')),
        ]);
    }
}

if (!function_exists('construirAsuntoCorreoPedidoMateriales')) {
    function construirAsuntoCorreoPedidoMateriales(array $pedido, array $config): string
    {
        $cabecera = (array)($pedido['cabecera'] ?? []);
        $plantilla = trim((string)($config['asunto_base'] ?? ''));
        if ($plantilla === '') {
            $plantilla = 'Pedido de materiales #{numero_pedido} - Previsita {id_previsita}';
        }

        return sanitizarAsuntoEnvioPedidoMateriales(
            reemplazarVariablesCorreoPedidoMateriales($plantilla, $cabecera)
        );
    }
}

if (!function_exists('construirCuerpoCorreoPedidoMateriales')) {
    function construirCuerpoCorreoPedidoMateriales(array $pedido, array $config): string
    {
        $cabecera = (array)($pedido['cabecera'] ?? []);
        $plantilla = trim((string)($config['cuerpo_base'] ?? ''));
        if ($plantilla === '') {
            $plantilla = implode("\n", [
                'Se adjunta el PDF correspondiente al Pedido de Materiales #{numero_pedido}.',
                '',
                'Previsita: {id_previsita}',
                'Presupuesto: {id_presupuesto}',
                'Orden de Compra: {id_orden_compra}',
                'Fecha de confirmacion: {fecha_confirmacion}',
                '',
                'Este mensaje fue generado automaticamente por AdminTech.',
            ]);
        }

        return sanitizarCuerpoEnvioPedidoMateriales(
            reemplazarVariablesCorreoPedidoMateriales($plantilla, $cabecera)
        );
    }
}

if (!function_exists('obtenerEstadoEnvioPedidoMateriales')) {
    function obtenerEstadoEnvioPedidoMateriales(mysqli $db, int $idPedido): ?array
    {
        if ($idPedido <= 0 || !tabla_existe($db, 'pedido_materiales_pedido_envios')) {
            return null;
        }

        $tipoEnvio = 'pedido_materiales_mail';
        $stmt = mysqli_prepare(
            $db,
            'SELECT * FROM pedido_materiales_pedido_envios
             WHERE id_pedido_materiales_pedido = ? AND tipo_envio = ?
             LIMIT 1'
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo consultar el estado del envio.', 500);
        }

        mysqli_stmt_bind_param($stmt, 'is', $idPedido, $tipoEnvio);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('No se pudo consultar el estado del envio.', 500);
        }

        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $row ?: null;
    }
}

if (!function_exists('reclamarIntentoEnvioPedidoMateriales')) {
    function reclamarIntentoEnvioPedidoMateriales(
        mysqli $db,
        int $idPedido,
        int $idUsuario,
        array $destinatarios,
        string $asunto,
        string $cuerpo
    ): array {
        if (!pedidoMaterialesEnviosTablasMinimasDisponibles($db)) {
            throw new RuntimeException(
                'La persistencia de envios de Pedido de Materiales no esta disponible. Debe aplicarse la migracion 2026-07-29-C_pedido_materiales_pedido_envios.sql.',
                409
            );
        }

        $tipoEnvio = 'pedido_materiales_mail';
        $to = serializarDestinatariosEnvioPedidoMateriales((array)($destinatarios['to'] ?? []));
        $cc = serializarDestinatariosEnvioPedidoMateriales((array)($destinatarios['cc'] ?? []));
        $bcc = serializarDestinatariosEnvioPedidoMateriales((array)($destinatarios['bcc'] ?? []));

        mysqli_begin_transaction($db);
        try {
            $sqlCrear = "
                INSERT INTO pedido_materiales_pedido_envios (
                    id_pedido_materiales_pedido,
                    tipo_envio,
                    estado,
                    intentos,
                    destinatarios_to,
                    destinatarios_cc,
                    destinatarios_bcc,
                    asunto,
                    cuerpo,
                    id_usuario_ultimo_intento,
                    created_at,
                    updated_at
                ) VALUES (?, ?, 'pendiente', 0, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    id_pedido_materiales_pedido_envio = id_pedido_materiales_pedido_envio
            ";
            $stmtCrear = mysqli_prepare($db, $sqlCrear);
            if (!$stmtCrear) {
                throw new RuntimeException('No se pudo preparar el registro del envio.', 500);
            }
            mysqli_stmt_bind_param(
                $stmtCrear,
                'issssssi',
                $idPedido,
                $tipoEnvio,
                $to,
                $cc,
                $bcc,
                $asunto,
                $cuerpo,
                $idUsuario
            );
            if (!mysqli_stmt_execute($stmtCrear)) {
                mysqli_stmt_close($stmtCrear);
                throw new RuntimeException('No se pudo crear el registro del envio.', 500);
            }
            mysqli_stmt_close($stmtCrear);

            $stmtEstado = mysqli_prepare(
                $db,
                'SELECT * FROM pedido_materiales_pedido_envios
                 WHERE id_pedido_materiales_pedido = ? AND tipo_envio = ?
                 LIMIT 1 FOR UPDATE'
            );
            if (!$stmtEstado) {
                throw new RuntimeException('No se pudo bloquear el registro del envio.', 500);
            }
            mysqli_stmt_bind_param($stmtEstado, 'is', $idPedido, $tipoEnvio);
            if (!mysqli_stmt_execute($stmtEstado)) {
                mysqli_stmt_close($stmtEstado);
                throw new RuntimeException('No se pudo bloquear el registro del envio.', 500);
            }
            $resultEstado = mysqli_stmt_get_result($stmtEstado);
            $envio = $resultEstado ? mysqli_fetch_assoc($resultEstado) : null;
            mysqli_stmt_close($stmtEstado);

            if (!$envio) {
                throw new RuntimeException('No se pudo recuperar el registro del envio.', 500);
            }

            if (($envio['estado'] ?? '') === 'enviado') {
                mysqli_commit($db);

                return [
                    'reclamado' => false,
                    'ya_enviado' => true,
                    'envio' => $envio,
                ];
            }

            if (($envio['estado'] ?? '') === 'simulado') {
                mysqli_commit($db);

                return [
                    'reclamado' => false,
                    'ya_enviado' => false,
                    'ya_simulado' => true,
                    'envio' => $envio,
                ];
            }

            if (($envio['estado'] ?? '') === 'procesando') {
                mysqli_commit($db);

                return [
                    'reclamado' => false,
                    'ya_enviado' => false,
                    'en_curso' => true,
                    'envio' => $envio,
                ];
            }

            $idEnvio = (int)$envio['id_pedido_materiales_pedido_envio'];
            $stmtActualizar = mysqli_prepare(
                $db,
                "UPDATE pedido_materiales_pedido_envios
                 SET estado = 'procesando',
                     intentos = intentos + 1,
                     destinatarios_to = ?,
                     destinatarios_cc = ?,
                     destinatarios_bcc = ?,
                     asunto = ?,
                     cuerpo = ?,
                     ultimo_error = NULL,
                     fecha_ultimo_intento = NOW(),
                     id_usuario_ultimo_intento = ?,
                     updated_at = NOW()
                 WHERE id_pedido_materiales_pedido_envio = ?"
            );
            if (!$stmtActualizar) {
                throw new RuntimeException('No se pudo preparar el intento de envio.', 500);
            }
            mysqli_stmt_bind_param(
                $stmtActualizar,
                'sssssii',
                $to,
                $cc,
                $bcc,
                $asunto,
                $cuerpo,
                $idUsuario,
                $idEnvio
            );
            if (!mysqli_stmt_execute($stmtActualizar)) {
                mysqli_stmt_close($stmtActualizar);
                throw new RuntimeException('No se pudo registrar el intento de envio.', 500);
            }
            mysqli_stmt_close($stmtActualizar);
            mysqli_commit($db);

            return [
                'reclamado' => true,
                'ya_enviado' => false,
                'id_pedido_materiales_pedido_envio' => $idEnvio,
            ];
        } catch (Throwable $e) {
            mysqli_rollback($db);
            throw $e;
        }
    }
}

if (!function_exists('vincularDocumentoEnvioPedidoMateriales')) {
    function vincularDocumentoEnvioPedidoMateriales(
        mysqli $db,
        int $idEnvio,
        int $idDocumento
    ): void {
        $stmt = mysqli_prepare(
            $db,
            "UPDATE pedido_materiales_pedido_envios
             SET id_pedido_materiales_pedido_documento = ?, updated_at = NOW()
             WHERE id_pedido_materiales_pedido_envio = ? AND estado = 'procesando'"
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo vincular el PDF con el envio.', 500);
        }
        mysqli_stmt_bind_param($stmt, 'ii', $idDocumento, $idEnvio);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('No se pudo vincular el PDF con el envio.', 500);
        }
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('marcarEnvioPedidoMaterialesEnviado')) {
    function marcarEnvioPedidoMaterialesEnviado(mysqli $db, int $idEnvio): void
    {
        $stmt = mysqli_prepare(
            $db,
            "UPDATE pedido_materiales_pedido_envios
             SET estado = 'enviado',
                 ultimo_error = NULL,
                 fecha_envio = NOW(),
                 updated_at = NOW()
             WHERE id_pedido_materiales_pedido_envio = ? AND estado = 'procesando'"
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo actualizar el envio confirmado.', 500);
        }
        mysqli_stmt_bind_param($stmt, 'i', $idEnvio);
        if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) !== 1) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('No se pudo confirmar el estado final del envio.', 500);
        }
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('marcarEnvioPedidoMaterialesSimulado')) {
    function marcarEnvioPedidoMaterialesSimulado(mysqli $db, int $idEnvio): void
    {
        $stmt = mysqli_prepare(
            $db,
            "UPDATE pedido_materiales_pedido_envios
             SET estado = 'simulado',
                 ultimo_error = NULL,
                 fecha_envio = NULL,
                 updated_at = NOW()
             WHERE id_pedido_materiales_pedido_envio = ? AND estado = 'procesando'"
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo actualizar el envio simulado.', 500);
        }
        mysqli_stmt_bind_param($stmt, 'i', $idEnvio);
        if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) !== 1) {
            mysqli_stmt_close($stmt);
            throw new RuntimeException('No se pudo confirmar el estado simulado del envio.', 500);
        }
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('marcarEnvioPedidoMaterialesError')) {
    function marcarEnvioPedidoMaterialesError(
        mysqli $db,
        int $idEnvio,
        string $mensaje
    ): void {
        $stmt = mysqli_prepare(
            $db,
            "UPDATE pedido_materiales_pedido_envios
             SET estado = 'error',
                 ultimo_error = ?,
                 updated_at = NOW()
             WHERE id_pedido_materiales_pedido_envio = ? AND estado = 'procesando'"
        );
        if (!$stmt) {
            return;
        }
        mysqli_stmt_bind_param($stmt, 'si', $mensaje, $idEnvio);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('sanitizarErrorEnvioPedidoMateriales')) {
    function sanitizarErrorEnvioPedidoMateriales(string $mensaje, array $config = []): string
    {
        $mensaje = trim($mensaje);
        if ($mensaje === '') {
            $mensaje = 'No se pudo enviar el correo por SMTP.';
        }

        $sensibles = array_filter([
            trim((string)($config['smtp_host'] ?? '')),
            trim((string)($config['smtp_usuario'] ?? '')),
            trim((string)($config['smtp_password'] ?? '')),
            trim((string)($config['remitente_email'] ?? '')),
        ]);
        foreach ($sensibles as $sensible) {
            $mensaje = str_replace($sensible, '[dato oculto]', $mensaje);
        }

        $mensaje = preg_replace(
            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu',
            '[email oculto]',
            $mensaje
        );
        $mensaje = preg_replace(
            '/\b(?:c|l)\d{5,}\.ferozo\.com\b/iu',
            '[host smtp oculto]',
            $mensaje
        );
        $mensaje = preg_replace(
            '/\bsmtp\.[A-Z0-9.-]+\.[A-Z]{2,}\b/iu',
            '[host smtp oculto]',
            $mensaje
        );

        if (function_exists('mb_substr')) {
            $mensaje = mb_substr($mensaje, 0, 500, 'UTF-8');
        } else {
            $mensaje = substr($mensaje, 0, 500);
        }

        return trim($mensaje) !== '' ? trim($mensaje) : 'No se pudo enviar el correo por SMTP.';
    }
}

if (!function_exists('obtenerPdfParaEnvioPedidoMateriales')) {
    function obtenerPdfParaEnvioPedidoMateriales(
        mysqli $db,
        int $idPedido,
        int $idUsuario
    ): array {
        $documento = obtenerDocumentoPdfPedidoMaterialesConfirmado($db, $idPedido);
        if ($documento) {
            try {
                $rutaAbsoluta = resolverRutaAbsolutaDocumentoPdfPedidoMateriales($documento);
                $tamano = filesize($rutaAbsoluta);
                $archivo = fopen($rutaAbsoluta, 'rb');
                $firma = $archivo !== false ? (string)fread($archivo, 4) : '';
                if ($archivo !== false) {
                    fclose($archivo);
                }

                if (
                    ($documento['mime_type'] ?? '') === 'application/pdf'
                    && $tamano !== false
                    && $tamano > 0
                    && $firma === '%PDF'
                ) {
                    return [
                        'id_pedido_materiales_pedido_documento' => (int)$documento['id_pedido_materiales_pedido_documento'],
                        'id_pedido_materiales_pedido' => $idPedido,
                        'ruta_absoluta' => $rutaAbsoluta,
                        'ruta_relativa' => (string)$documento['ruta_archivo'],
                        'nombre_archivo' => (string)$documento['nombre_archivo'],
                        'mime' => 'application/pdf',
                        'tamano' => (int)$tamano,
                        'hash_archivo' => (string)$documento['hash_archivo'],
                        'reutilizado' => true,
                    ];
                }
            } catch (Throwable $e) {
                // El registro o archivo no es reutilizable: se regenera desde el pedido congelado.
            }
        }

        $pdf = generarPdfPedidoMaterialesConfirmado($db, $idPedido, $idUsuario);
        $pdf['reutilizado'] = false;

        return $pdf;
    }
}

if (!function_exists('validarAdjuntoPdfEnvioPedidoMateriales')) {
    function validarAdjuntoPdfEnvioPedidoMateriales(array $pdf): void
    {
        $ruta = trim((string)($pdf['ruta_absoluta'] ?? ''));
        $nombre = basename((string)($pdf['nombre_archivo'] ?? 'pedido_materiales.pdf'));
        if ($ruta === '' || !is_file($ruta) || !is_readable($ruta)) {
            throw new RuntimeException('El PDF del pedido no esta disponible para adjuntar.', 409);
        }
        if (($pdf['mime'] ?? '') !== 'application/pdf') {
            throw new RuntimeException('El documento del pedido no tiene MIME PDF valido.', 409);
        }
        if (!preg_match('/^[A-Za-z0-9_.-]+\.pdf$/', $nombre)) {
            throw new RuntimeException('El nombre del PDF del pedido no es valido.', 409);
        }

        $tamano = filesize($ruta);
        $archivo = fopen($ruta, 'rb');
        $firma = $archivo !== false ? (string)fread($archivo, 4) : '';
        if ($archivo !== false) {
            fclose($archivo);
        }
        if ($tamano === false || $tamano <= 0 || $firma !== '%PDF') {
            throw new RuntimeException('El archivo adjunto no es un PDF valido.', 409);
        }
    }
}

if (!function_exists('enviarSmtpPedidoMateriales')) {
    function enviarSmtpPedidoMateriales(
        array $config,
        array $destinatarios,
        string $asunto,
        string $cuerpo,
        array $pdf
    ): void {
        if (!smtpTransportMailPresupuestosDisponible()) {
            throw new RuntimeException(mensajeDisponibilidadTransporteSmtpMailPresupuestos(), 409);
        }

        validarAdjuntoPdfEnvioPedidoMateriales($pdf);

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->SMTPDebug = 0;
            $mail->Host = (string)$config['smtp_host'];
            $mail->Port = (int)$config['smtp_puerto'];
            $mail->SMTPAuth = !empty($config['smtp_auth']);
            $mail->SMTPAutoTLS = false;

            if ($mail->SMTPAuth) {
                $mail->Username = (string)$config['smtp_usuario'];
                $mail->Password = (string)$config['smtp_password'];
            }

            $seguridad = normalizarSeguridadSmtpCorreoPedidoMateriales(
                $config['smtp_seguridad'] ?? null
            );
            if ($seguridad === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($seguridad === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = '';
            }

            $nombreRemitente = trim((string)($config['remitente_nombre'] ?? ''));
            if ($nombreRemitente === '') {
                $nombreRemitente = 'Pedido de Materiales AdminTech';
            }
            $mail->setFrom((string)$config['remitente_email'], $nombreRemitente);
            $mail->addReplyTo((string)$config['remitente_email'], $nombreRemitente);

            foreach ((array)($destinatarios['to'] ?? []) as $email) {
                $mail->addAddress($email);
            }
            foreach ((array)($destinatarios['cc'] ?? []) as $email) {
                $mail->addCC($email);
            }
            foreach ((array)($destinatarios['bcc'] ?? []) as $email) {
                $mail->addBCC($email);
            }

            $mail->isHTML(false);
            $mail->Subject = $asunto;
            $mail->Body = $cuerpo;
            $mail->AltBody = $cuerpo;
            $mail->addAttachment(
                (string)$pdf['ruta_absoluta'],
                (string)$pdf['nombre_archivo'],
                'base64',
                'application/pdf'
            );
            $mail->send();
        } catch (Throwable $e) {
            throw new RuntimeException(
                sanitizarErrorEnvioPedidoMateriales($e->getMessage(), $config),
                409
            );
        }
    }
}

if (!function_exists('enviarCorreoPedidoMaterialesConfirmado')) {
    function enviarCorreoPedidoMaterialesConfirmado(
        mysqli $db,
        int $idPedido,
        int $idUsuario
    ): array {
        if ($idPedido <= 0 || $idUsuario <= 0) {
            throw new RuntimeException('Faltan datos para enviar el correo del pedido.', 422);
        }
        if (!pedidoMaterialesEnviosTablasMinimasDisponibles($db)) {
            throw new RuntimeException(
                'La persistencia de envios de Pedido de Materiales no esta disponible. Debe aplicarse la migracion 2026-07-29-C_pedido_materiales_pedido_envios.sql.',
                409
            );
        }

        $modoSimulacion = pedidoMaterialesMailSimulacionActiva();
        $pedido = obtenerPedidoMaterialesConfirmadoParaPdf($db, $idPedido);
        $config = obtenerConfiguracionCorreoPedidoMaterialesActiva(true);
        $configPlantilla = $config ?: defaultsConfiguracionCorreoPedidoMateriales();
        $destinatarios = normalizarDestinatariosEnvioPedidoMateriales($configPlantilla);
        $asunto = construirAsuntoCorreoPedidoMateriales($pedido, $configPlantilla);
        $cuerpo = construirCuerpoCorreoPedidoMateriales($pedido, $configPlantilla);

        $reclamo = reclamarIntentoEnvioPedidoMateriales(
            $db,
            $idPedido,
            $idUsuario,
            $destinatarios,
            $asunto,
            $cuerpo
        );

        if (!empty($reclamo['ya_enviado'])) {
            $envioExistente = (array)($reclamo['envio'] ?? []);

            return [
                'ok' => true,
                'ya_enviado' => true,
                'id_pedido_materiales_pedido_envio' => (int)($envioExistente['id_pedido_materiales_pedido_envio'] ?? 0),
                'id_pedido_materiales_pedido_documento' => isset($envioExistente['id_pedido_materiales_pedido_documento'])
                    ? (int)$envioExistente['id_pedido_materiales_pedido_documento']
                    : null,
                'id_pedido_materiales_pedido' => $idPedido,
                'estado' => 'enviado',
                'simulado' => false,
                'ya_simulado' => false,
                'mensaje' => 'El correo ya habia sido enviado para este pedido.',
            ];
        }

        if (!empty($reclamo['ya_simulado'])) {
            $envioExistente = (array)($reclamo['envio'] ?? []);

            return [
                'ok' => true,
                'ya_enviado' => false,
                'id_pedido_materiales_pedido_envio' => (int)($envioExistente['id_pedido_materiales_pedido_envio'] ?? 0),
                'id_pedido_materiales_pedido_documento' => isset($envioExistente['id_pedido_materiales_pedido_documento'])
                    ? (int)$envioExistente['id_pedido_materiales_pedido_documento']
                    : null,
                'id_pedido_materiales_pedido' => $idPedido,
                'estado' => 'simulado',
                'simulado' => true,
                'ya_simulado' => true,
                'mensaje' => 'El correo ya habia sido simulado para este pedido.',
            ];
        }

        if (!empty($reclamo['en_curso'])) {
            throw new RuntimeException('El envio del correo ya esta en curso.', 409);
        }

        $idEnvio = (int)($reclamo['id_pedido_materiales_pedido_envio'] ?? 0);
        try {
            $pdf = obtenerPdfParaEnvioPedidoMateriales($db, $idPedido, $idUsuario);
            vincularDocumentoEnvioPedidoMateriales(
                $db,
                $idEnvio,
                (int)$pdf['id_pedido_materiales_pedido_documento']
            );

            if ($modoSimulacion) {
                validarAdjuntoPdfEnvioPedidoMateriales($pdf);
                marcarEnvioPedidoMaterialesSimulado($db, $idEnvio);

                return [
                    'ok' => true,
                    'ya_enviado' => false,
                    'id_pedido_materiales_pedido_envio' => $idEnvio,
                    'id_pedido_materiales_pedido_documento' => (int)$pdf['id_pedido_materiales_pedido_documento'],
                    'id_pedido_materiales_pedido' => $idPedido,
                    'estado' => 'simulado',
                    'simulado' => true,
                    'ya_simulado' => false,
                    'pdf_reutilizado' => !empty($pdf['reutilizado']),
                    'mensaje' => 'Correo simulado correctamente.',
                ];
            }

            if (!$config) {
                throw new RuntimeException(
                    'No existe una configuracion activa de correo para Pedido de Materiales.',
                    409
                );
            }

            $validacionConfig = validarConfiguracionCorreoPedidoMateriales(
                $config,
                ['exigir_password' => true]
            );
            if (empty($validacionConfig['ok'])) {
                throw new RuntimeException(
                    implode(' ', (array)($validacionConfig['errores'] ?? [])),
                    409
                );
            }

            enviarSmtpPedidoMateriales($config, $destinatarios, $asunto, $cuerpo, $pdf);
            marcarEnvioPedidoMaterialesEnviado($db, $idEnvio);

            return [
                'ok' => true,
                'ya_enviado' => false,
                'id_pedido_materiales_pedido_envio' => $idEnvio,
                'id_pedido_materiales_pedido_documento' => (int)$pdf['id_pedido_materiales_pedido_documento'],
                'id_pedido_materiales_pedido' => $idPedido,
                'estado' => 'enviado',
                'simulado' => false,
                'ya_simulado' => false,
                'pdf_reutilizado' => !empty($pdf['reutilizado']),
                'mensaje' => 'Correo enviado correctamente.',
            ];
        } catch (Throwable $e) {
            $mensaje = sanitizarErrorEnvioPedidoMateriales($e->getMessage(), $config ?: []);
            marcarEnvioPedidoMaterialesError($db, $idEnvio, $mensaje);
            $codigo = in_array((int)$e->getCode(), [400, 404, 409, 422], true)
                ? (int)$e->getCode()
                : 500;
            throw new RuntimeException($mensaje, $codigo);
        }
    }
}
