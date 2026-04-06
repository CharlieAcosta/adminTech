<?php

require_once __DIR__ . '/conectDB.php';
require_once __DIR__ . '/presupuestoGeneradoModel.php';
require_once __DIR__ . '/presupuestoDocumentosEmitidosModel.php';
require_once __DIR__ . '/presupuestoIntervencionesModel.php';
require_once __DIR__ . '/presupuestoMailConfigModel.php';

if (!function_exists('cargarComposerAutoloadMailPresupuestos')) {
    function cargarComposerAutoloadMailPresupuestos(): bool
    {
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            return false;
        }

        require_once $autoload;
        return class_exists('\\PHPMailer\\PHPMailer\\PHPMailer');
    }
}

if (!function_exists('normalizarListaEmailsDocumentoEmitido')) {
    function normalizarListaEmailsDocumentoEmitido($raw): array
    {
        if (is_array($raw)) {
            $items = $raw;
        } else {
            $texto = str_replace(["\r", "\n", ';'], ',', (string)$raw);
            $items = explode(',', $texto);
        }

        $limpios = [];
        foreach ($items as $item) {
            $email = normalizarEmailMailPresupuestos($item);
            if ($email === '' || !validarEmailMailPresupuestos($email)) {
                continue;
            }
            $limpios[$email] = $email;
        }

        return array_values($limpios);
    }
}

if (!function_exists('serializarListaEmailsDocumentoEmitido')) {
    function serializarListaEmailsDocumentoEmitido(array $emails): string
    {
        return implode(', ', normalizarListaEmailsDocumentoEmitido($emails));
    }
}

if (!function_exists('obtenerDocumentoEmitidoDetallePresupuesto')) {
    function obtenerDocumentoEmitidoDetallePresupuesto(int $idDocumentoEmitido): ?array
    {
        if ($idDocumentoEmitido <= 0) {
            return null;
        }

        $db = conectDB();
        if (!$db) {
            return null;
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            if (!tabla_existe($db, 'presupuesto_documentos_emitidos')) {
                return null;
            }

            $sql = "
                SELECT
                    d.id_documento_emitido,
                    d.id_presupuesto,
                    d.id_previsita,
                    d.id_usuario,
                    d.version_presupuesto,
                    d.nombre_archivo,
                    d.ruta_archivo,
                    d.created_at,
                    p.estado AS estado_presupuesto,
                    pv.razon_social,
                    pv.cuit,
                    pv.email_contacto_obra,
                    c.email AS cliente_email,
                    c.contacto_pri_email,
                    c.contacto_papro_email,
                    c.email_licitacion,
                    c.email_pagos,
                    c.email_documentacion
                FROM presupuesto_documentos_emitidos d
                INNER JOIN presupuestos p
                    ON p.id_presupuesto = d.id_presupuesto
                INNER JOIN previsitas pv
                    ON pv.id_previsita = d.id_previsita
                LEFT JOIN clientes c
                    ON c.cuit = pv.cuit
                   AND c.estado <> 'Eliminado'
                WHERE d.id_documento_emitido = ?
                LIMIT 1
            ";
            $stmt = stmt_or_throw($db, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $idDocumentoEmitido);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            if (!$row) {
                return null;
            }

            $disponibilidad = resolverDisponibilidadDocumentoEmitidoPresupuesto((string)($row['ruta_archivo'] ?? ''));

            return [
                'id_documento_emitido' => (int)($row['id_documento_emitido'] ?? 0),
                'id_presupuesto' => (int)($row['id_presupuesto'] ?? 0),
                'id_previsita' => (int)($row['id_previsita'] ?? 0),
                'id_usuario' => (int)($row['id_usuario'] ?? 0),
                'version_presupuesto' => isset($row['version_presupuesto']) ? (int)$row['version_presupuesto'] : null,
                'nombre_archivo' => trim((string)($row['nombre_archivo'] ?? '')),
                'ruta_archivo' => $disponibilidad['ruta_archivo'],
                'ruta_absoluta' => $disponibilidad['ruta_absoluta'],
                'archivo_disponible' => $disponibilidad['archivo_disponible'],
                'created_at' => (string)($row['created_at'] ?? ''),
                'fecha_texto' => formatearFechaDocumentoEmitidoPresupuesto((string)($row['created_at'] ?? '')),
                'numero_documento' => extraerNumeroDocumentoEmitidoPresupuesto((string)($row['nombre_archivo'] ?? '')),
                'estado_presupuesto' => trim((string)($row['estado_presupuesto'] ?? '')),
                'razon_social' => trim((string)($row['razon_social'] ?? '')),
                'cuit' => trim((string)($row['cuit'] ?? '')),
                'email_contacto_obra' => trim((string)($row['email_contacto_obra'] ?? '')),
                'cliente_email' => trim((string)($row['cliente_email'] ?? '')),
                'contacto_pri_email' => trim((string)($row['contacto_pri_email'] ?? '')),
                'contacto_papro_email' => trim((string)($row['contacto_papro_email'] ?? '')),
                'email_licitacion' => trim((string)($row['email_licitacion'] ?? '')),
                'email_pagos' => trim((string)($row['email_pagos'] ?? '')),
                'email_documentacion' => trim((string)($row['email_documentacion'] ?? '')),
            ];
        } catch (Throwable $e) {
            return null;
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('obtenerSugerenciasDestinatariosDocumentoEmitidoPresupuesto')) {
    function obtenerSugerenciasDestinatariosDocumentoEmitidoPresupuesto(array $detalle): array
    {
        $candidatos = [
            'Contacto de obra' => $detalle['email_contacto_obra'] ?? '',
            'Contacto principal' => $detalle['contacto_pri_email'] ?? '',
            'Email cliente' => $detalle['cliente_email'] ?? '',
            'Contacto pago a proveedores' => $detalle['contacto_papro_email'] ?? '',
            'Licitaciones' => $detalle['email_licitacion'] ?? '',
            'Pagos' => $detalle['email_pagos'] ?? '',
            'Documentación' => $detalle['email_documentacion'] ?? '',
        ];

        $items = [];
        $vistos = [];

        foreach ($candidatos as $label => $emailRaw) {
            $email = normalizarEmailMailPresupuestos($emailRaw);
            if ($email === '' || !validarEmailMailPresupuestos($email) || isset($vistos[$email])) {
                continue;
            }

            $vistos[$email] = true;
            $items[] = [
                'label' => $label,
                'email' => $email,
            ];
        }

        return $items;
    }
}

if (!function_exists('construirAsuntoDocumentoEmitidoPresupuesto')) {
    function construirAsuntoDocumentoEmitidoPresupuesto(array $detalle): string
    {
        $numero = trim((string)($detalle['numero_documento'] ?? ''));
        $razonSocial = trim((string)($detalle['razon_social'] ?? ''));

        $asunto = 'Presupuesto';
        if ($numero !== '') {
            $asunto .= ' ' . $numero;
        }
        if ($razonSocial !== '') {
            $asunto .= ' | ' . $razonSocial;
        }

        return trim($asunto);
    }
}

if (!function_exists('construirCuerpoDocumentoEmitidoPresupuesto')) {
    function construirCuerpoDocumentoEmitidoPresupuesto(array $detalle): string
    {
        $numero = trim((string)($detalle['numero_documento'] ?? ''));
        $razonSocial = trim((string)($detalle['razon_social'] ?? ''));

        $lineas = [
            'Hola,',
            '',
            'Adjuntamos el presupuesto'
                . ($numero !== '' ? ' ' . $numero : '')
                . ($razonSocial !== '' ? ' correspondiente a ' . $razonSocial : '')
                . '.',
            '',
            'Quedamos a disposición por cualquier consulta.',
            '',
            'Saludos,',
            'Equipo de Presupuestos',
        ];

        return implode("\n", $lineas);
    }
}

if (!function_exists('obtenerContextoEnvioDocumentoEmitidoPresupuesto')) {
    function obtenerContextoEnvioDocumentoEmitidoPresupuesto(int $idDocumentoEmitido): array
    {
        $detalle = obtenerDocumentoEmitidoDetallePresupuesto($idDocumentoEmitido);
        if (!$detalle) {
            return ['ok' => false, 'msg' => 'No se encontró el documento emitido seleccionado.'];
        }

        $config = obtenerConfiguracionMailPresupuestos();
        $sugerencias = obtenerSugerenciasDestinatariosDocumentoEmitidoPresupuesto($detalle);
        $paraDefault = $sugerencias[0]['email'] ?? '';
        $copias = obtenerCopiasActivasPorDefectoMailPresupuestos();

        return [
            'ok' => true,
            'documento' => [
                'id_documento_emitido' => $detalle['id_documento_emitido'],
                'id_presupuesto' => $detalle['id_presupuesto'],
                'id_previsita' => $detalle['id_previsita'],
                'numero_documento' => $detalle['numero_documento'],
                'nombre_archivo' => $detalle['nombre_archivo'],
                'razon_social' => $detalle['razon_social'],
                'fecha_texto' => $detalle['fecha_texto'],
                'archivo_disponible' => $detalle['archivo_disponible'],
            ],
            'config' => [
                'modo_envio' => $config['modo_envio'],
                'modo_envio_label' => describirModoEnvioMailPresupuestos($config['modo_envio']),
                'remitente_email' => $config['remitente_email'],
                'remitente_nombre' => $config['remitente_nombre'],
            ],
            'sugerencias_para' => $sugerencias,
            'para_default' => $paraDefault,
            'copias' => $copias,
            'asunto_default' => construirAsuntoDocumentoEmitidoPresupuesto($detalle),
            'cuerpo_default' => construirCuerpoDocumentoEmitidoPresupuesto($detalle),
        ];
    }
}

if (!function_exists('esUltimoDocumentoEmitidoPresupuesto')) {
    function esUltimoDocumentoEmitidoPresupuesto(int $idDocumentoEmitido, int $idPresupuesto): bool
    {
        if ($idDocumentoEmitido <= 0 || $idPresupuesto <= 0) {
            return false;
        }

        $db = conectDB();
        if (!$db) {
            return false;
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            if (!tabla_existe($db, 'presupuesto_documentos_emitidos')) {
                return false;
            }

            $sql = "
                SELECT id_documento_emitido
                FROM presupuesto_documentos_emitidos
                WHERE id_presupuesto = ?
                ORDER BY created_at DESC, id_documento_emitido DESC
                LIMIT 1
            ";
            $stmt = stmt_or_throw($db, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $idPresupuesto);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            return $row && (int)$row['id_documento_emitido'] === $idDocumentoEmitido;
        } catch (Throwable $e) {
            return false;
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('obtenerEstadoActualPresupuestoMail')) {
    function obtenerEstadoActualPresupuestoMail(int $idPresupuesto): string
    {
        if ($idPresupuesto <= 0) {
            return '';
        }

        $db = conectDB();
        if (!$db) {
            return '';
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            $sql = "SELECT estado FROM presupuestos WHERE id_presupuesto = ? LIMIT 1";
            $stmt = stmt_or_throw($db, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $idPresupuesto);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            return trim((string)($row['estado'] ?? ''));
        } catch (Throwable $e) {
            return '';
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('obtenerEstadoVisiblePresupuestoMail')) {
    function obtenerEstadoVisiblePresupuestoMail(int $idPrevisita, ?int $idPresupuesto = null, ?string $modo = null): string
    {
        $presupuesto = obtenerPresupuestoActualPorPrevisita($idPrevisita, $idPresupuesto);
        if (!$presupuesto) {
            return '';
        }

        return resolverEstadoVisiblePresupuestoDesdePresupuesto(
            $presupuesto,
            $modo ?: obtenerModoActivoCircuitoComercialPresupuestos()
        );
    }
}

if (!function_exists('registrarEnvioDocumentoEmitidoPresupuestoEnConexion')) {
    function registrarEnvioDocumentoEmitidoPresupuestoEnConexion(mysqli $db, array $data): int
    {
        if (!tabla_existe($db, 'presupuesto_documentos_emitidos_envios')) {
            throw new RuntimeException('La tabla de envÃ­os de documentos emitidos no existe en la base de datos.');
        }

        $sql = "
            INSERT INTO presupuesto_documentos_emitidos_envios
                (
                    id_documento_emitido,
                    id_presupuesto,
                    id_previsita,
                    id_usuario,
                    modo_envio,
                    estado_envio,
                    para_email,
                    cc,
                    cco,
                    asunto,
                    cuerpo,
                    remitente_email,
                    remitente_nombre,
                    mensaje_error,
                    respuesta_transporte,
                    created_at
                )
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        $stmt = stmt_or_throw($db, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            'iiiisssssssssss',
            $data['id_documento_emitido'],
            $data['id_presupuesto'],
            $data['id_previsita'],
            $data['id_usuario'],
            $data['modo_envio'],
            $data['estado_envio'],
            $data['para_email'],
            $data['cc'],
            $data['cco'],
            $data['asunto'],
            $data['cuerpo'],
            $data['remitente_email'],
            $data['remitente_nombre'],
            $data['mensaje_error'],
            $data['respuesta_transporte']
        );
        mysqli_stmt_execute($stmt);
        $idEnvio = mysqli_insert_id($db);
        mysqli_stmt_close($stmt);

        return $idEnvio;
    }
}

if (!function_exists('registrarEnvioDocumentoEmitidoPresupuestoDB')) {
    function registrarEnvioDocumentoEmitidoPresupuestoDB(array $data): array
    {
        $db = conectDB();
        if (!$db) {
            return ['ok' => false, 'msg' => 'No se pudo abrir conexión a la base de datos.'];
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            return ['ok' => true, 'id_envio' => registrarEnvioDocumentoEmitidoPresupuestoEnConexion($db, $data)];
            if (!tabla_existe($db, 'presupuesto_documentos_emitidos_envios')) {
                throw new RuntimeException('La tabla de envíos de documentos emitidos no existe en la base de datos.');
            }

            $sql = "
                INSERT INTO presupuesto_documentos_emitidos_envios
                    (
                        id_documento_emitido,
                        id_presupuesto,
                        id_previsita,
                        id_usuario,
                        modo_envio,
                        estado_envio,
                        para_email,
                        cc,
                        cco,
                        asunto,
                        cuerpo,
                        remitente_email,
                        remitente_nombre,
                        mensaje_error,
                        respuesta_transporte,
                        created_at
                    )
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";
            $stmt = stmt_or_throw($db, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                'iiiisssssssssss',
                $data['id_documento_emitido'],
                $data['id_presupuesto'],
                $data['id_previsita'],
                $data['id_usuario'],
                $data['modo_envio'],
                $data['estado_envio'],
                $data['para_email'],
                $data['cc'],
                $data['cco'],
                $data['asunto'],
                $data['cuerpo'],
                $data['remitente_email'],
                $data['remitente_nombre'],
                $data['mensaje_error'],
                $data['respuesta_transporte']
            );
            mysqli_stmt_execute($stmt);
            $idEnvio = mysqli_insert_id($db);
            mysqli_stmt_close($stmt);

            return ['ok' => true, 'id_envio' => $idEnvio];
        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('actualizarEstadoPresupuestoEnviado')) {
    function actualizarEstadoPresupuestoEnviado(int $idPresupuesto): array
    {
        if ($idPresupuesto <= 0) {
            return ['ok' => false, 'msg' => 'Presupuesto inválido para actualizar estado.'];
        }

        $db = conectDB();
        if (!$db) {
            return ['ok' => false, 'msg' => 'No se pudo abrir conexión a la base de datos.'];
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            $sql = "
                UPDATE presupuestos
                SET estado = 'Enviado', updated_at = NOW()
                WHERE id_presupuesto = ?
            ";
            $stmt = stmt_or_throw($db, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $idPresupuesto);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            return ['ok' => true, 'estado' => 'Enviado'];
        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('enviarDocumentoEmitidoPorSmtpPresupuesto')) {
    function enviarDocumentoEmitidoPorSmtpPresupuesto(array $config, array $detalle, array $payload): array
    {
        if (!cargarComposerAutoloadMailPresupuestos()) {
            return [
                'ok' => false,
                'msg' => 'No está disponible la librería SMTP. Ejecutá composer install para habilitar el envío real.',
                'respuesta' => 'PHPMailer no disponible',
            ];
        }

        if (
            !validarEmailMailPresupuestos($config['remitente_email'] ?? '') ||
            trim((string)($config['smtp_host'] ?? '')) === '' ||
            (int)($config['smtp_puerto'] ?? 0) <= 0 ||
            trim((string)($config['smtp_usuario'] ?? '')) === '' ||
            trim((string)($config['smtp_password'] ?? '')) === ''
        ) {
            return [
                'ok' => false,
                'msg' => 'La configuración SMTP está incompleta para realizar el envío real.',
                'respuesta' => 'Configuración SMTP incompleta',
            ];
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = (string)$config['smtp_host'];
            $mail->Port = (int)$config['smtp_puerto'];
            $mail->SMTPAuth = true;
            $mail->Username = (string)$config['smtp_usuario'];
            $mail->Password = (string)$config['smtp_password'];
            $mail->setFrom(
                (string)$config['remitente_email'],
                (string)($config['remitente_nombre'] ?: 'Presupuestos AdminTech')
            );

            $seguridad = normalizarSeguridadSmtpMailPresupuestos($config['smtp_seguridad'] ?? null);
            if ($seguridad === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($seguridad === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPAutoTLS = false;
            }

            foreach ($payload['para_emails'] as $email) {
                $mail->addAddress($email);
            }
            foreach ($payload['cc_emails'] as $email) {
                $mail->addCC($email);
            }
            foreach ($payload['cco_emails'] as $email) {
                $mail->addBCC($email);
            }

            $mail->isHTML(true);
            $mail->Subject = (string)$payload['asunto'];
            $mail->Body = nl2br((string)$payload['cuerpo']);
            $mail->AltBody = (string)$payload['cuerpo'];
            $mail->addAttachment((string)$detalle['ruta_absoluta'], (string)$detalle['nombre_archivo']);
            $mail->send();

            return ['ok' => true, 'respuesta' => 'SMTP OK'];
        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage(), 'respuesta' => $e->getMessage()];
        }
    }
}

if (!function_exists('procesarEnvioDocumentoEmitidoPresupuesto')) {
    function procesarEnvioDocumentoEmitidoPresupuesto(array $input, int $idUsuario): array
    {
        $idDocumentoEmitido = (int)($input['id_documento_emitido'] ?? 0);
        if ($idDocumentoEmitido <= 0 || $idUsuario <= 0) {
            return ['ok' => false, 'msg' => 'Faltan datos para enviar el documento emitido.'];
        }

        $detalle = obtenerDocumentoEmitidoDetallePresupuesto($idDocumentoEmitido);
        if (!$detalle) {
            return ['ok' => false, 'msg' => 'No se encontró el documento emitido seleccionado.'];
        }

        if (empty($detalle['archivo_disponible']) || empty($detalle['ruta_absoluta'])) {
            return ['ok' => false, 'msg' => 'El archivo PDF no está disponible en el servidor.'];
        }

        $config = obtenerConfiguracionMailPresupuestos();
        $modoEnvio = normalizarModoEnvioMailPresupuestos($config['modo_envio'] ?? null);
        $paraEmails = normalizarListaEmailsDocumentoEmitido($input['para_email'] ?? '');
        $ccEmails = normalizarListaEmailsDocumentoEmitido($input['cc_email'] ?? '');

        $ccoEmails = normalizarListaEmailsDocumentoEmitido($input['cco_email'] ?? []);
        $ccoManual = normalizarListaEmailsDocumentoEmitido($input['cco_manual'] ?? '');
        $ccoEmails = array_values(array_unique(array_merge($ccoEmails, $ccoManual)));

        if (!$paraEmails) {
            return ['ok' => false, 'msg' => 'Ingresá al menos un destinatario válido en el campo Para.'];
        }

        $asunto = trim((string)($input['asunto'] ?? ''));
        if ($asunto === '') {
            $asunto = construirAsuntoDocumentoEmitidoPresupuesto($detalle);
        }

        $cuerpo = trim((string)($input['cuerpo'] ?? ''));
        if ($cuerpo === '') {
            $cuerpo = construirCuerpoDocumentoEmitidoPresupuesto($detalle);
        }

        $resultadoTransporte = [];
        $estadoEnvio = 'fallido';
        $mensajeError = '';
        $accionIntervencion = 'fallo_envio_mail';
        $estadoPresupuestoActual = obtenerEstadoActualPresupuestoMail((int)$detalle['id_presupuesto']);
        $estadoActualizado = false;

        if ($modoEnvio === 'simulacion') {
            $resultadoTransporte = [
                'ok' => true,
                'msg' => 'Simulación registrada correctamente.',
                'respuesta' => 'Simulación OK',
            ];
            $estadoEnvio = 'simulado';
            $accionIntervencion = 'simular_envio_mail';
        } else {
            $resultadoTransporte = enviarDocumentoEmitidoPorSmtpPresupuesto($config, $detalle, [
                'para_emails' => $paraEmails,
                'cc_emails' => $ccEmails,
                'cco_emails' => $ccoEmails,
                'asunto' => $asunto,
                'cuerpo' => $cuerpo,
            ]);

            if (!empty($resultadoTransporte['ok'])) {
                $estadoEnvio = 'enviado';
                $accionIntervencion = 'enviar_mail';

                if (esUltimoDocumentoEmitidoPresupuesto((int)$detalle['id_documento_emitido'], (int)$detalle['id_presupuesto'])) {
                    $resultadoEstado = actualizarEstadoPresupuestoEnviado((int)$detalle['id_presupuesto']);
                    if (!empty($resultadoEstado['ok'])) {
                        $estadoPresupuestoActual = 'Enviado';
                        $estadoActualizado = true;
                    }
                } else {
                    $estadoPresupuestoActual = obtenerEstadoActualPresupuestoMail((int)$detalle['id_presupuesto']);
                }
            } else {
                $mensajeError = (string)($resultadoTransporte['msg'] ?? 'No se pudo enviar el documento por SMTP.');
            }
        }

        if ($mensajeError === '') {
            $mensajeError = (string)($resultadoTransporte['msg'] ?? '');
        }

        $registroEnvio = registrarEnvioDocumentoEmitidoPresupuestoDB([
            'id_documento_emitido' => (int)$detalle['id_documento_emitido'],
            'id_presupuesto' => (int)$detalle['id_presupuesto'],
            'id_previsita' => (int)$detalle['id_previsita'],
            'id_usuario' => $idUsuario,
            'modo_envio' => $modoEnvio,
            'estado_envio' => $estadoEnvio,
            'para_email' => serializarListaEmailsDocumentoEmitido($paraEmails),
            'cc' => serializarListaEmailsDocumentoEmitido($ccEmails),
            'cco' => serializarListaEmailsDocumentoEmitido($ccoEmails),
            'asunto' => $asunto,
            'cuerpo' => $cuerpo,
            'remitente_email' => trim((string)($config['remitente_email'] ?? '')),
            'remitente_nombre' => trim((string)($config['remitente_nombre'] ?? '')),
            'mensaje_error' => $estadoEnvio === 'fallido' ? $mensajeError : '',
            'respuesta_transporte' => (string)($resultadoTransporte['respuesta'] ?? ''),
        ]);

        if (empty($registroEnvio['ok'])) {
            return ['ok' => false, 'msg' => $registroEnvio['msg'] ?? 'No se pudo registrar el envío del documento.'];
        }

        $intervencion = registrarIntervencionPresupuesto(
            (int)$detalle['id_presupuesto'],
            (int)$detalle['id_previsita'],
            $idUsuario,
            $accionIntervencion
        );

        $okProceso = $estadoEnvio !== 'fallido';
        $mensaje = $estadoEnvio === 'simulado'
            ? 'Simulación registrada. El correo no se envió realmente.'
            : ($estadoEnvio === 'enviado'
                ? 'El documento fue enviado correctamente.'
                : ($mensajeError !== '' ? $mensajeError : 'No se pudo enviar el documento.'));

        return [
            'ok' => $okProceso,
            'id_envio' => (int)($registroEnvio['id_envio'] ?? 0),
            'estado_envio' => $estadoEnvio,
            'modo_envio' => $modoEnvio,
            'estado_presupuesto_actual' => $estadoPresupuestoActual,
            'estado_presupuesto_actualizado' => $estadoActualizado,
            'msg' => $mensaje,
            'intervino' => $intervencion['intervino'] ?? null,
            'documento' => [
                'id_documento_emitido' => $detalle['id_documento_emitido'],
                'id_presupuesto' => $detalle['id_presupuesto'],
                'id_previsita' => $detalle['id_previsita'],
                'numero_documento' => $detalle['numero_documento'],
                'nombre_archivo' => $detalle['nombre_archivo'],
            ],
        ];
    }
}

if (!function_exists('procesarEnvioDocumentoEmitidoPresupuestoModoActivo')) {
    function procesarEnvioDocumentoEmitidoPresupuestoModoActivo(array $input, int $idUsuario): array
    {
        $idDocumentoEmitido = (int)($input['id_documento_emitido'] ?? 0);
        if ($idDocumentoEmitido <= 0 || $idUsuario <= 0) {
            return ['ok' => false, 'msg' => 'Faltan datos para enviar el documento emitido.'];
        }

        $detalle = obtenerDocumentoEmitidoDetallePresupuesto($idDocumentoEmitido);
        if (!$detalle) {
            return ['ok' => false, 'msg' => 'No se encontro el documento emitido seleccionado.'];
        }

        if (empty($detalle['archivo_disponible']) || empty($detalle['ruta_absoluta'])) {
            return ['ok' => false, 'msg' => 'El archivo PDF no esta disponible en el servidor.'];
        }

        $config = obtenerConfiguracionMailPresupuestos();
        $modoEnvio = normalizarModoEnvioMailPresupuestos($config['modo_envio'] ?? null);
        $paraEmails = normalizarListaEmailsDocumentoEmitido($input['para_email'] ?? '');
        $ccEmails = normalizarListaEmailsDocumentoEmitido($input['cc_email'] ?? '');
        $ccoEmails = normalizarListaEmailsDocumentoEmitido($input['cco_email'] ?? []);
        $ccoManual = normalizarListaEmailsDocumentoEmitido($input['cco_manual'] ?? '');
        $ccoEmails = array_values(array_unique(array_merge($ccoEmails, $ccoManual)));

        if (!$paraEmails) {
            return ['ok' => false, 'msg' => 'Ingresa al menos un destinatario valido en el campo Para.'];
        }

        $asunto = trim((string)($input['asunto'] ?? ''));
        if ($asunto === '') {
            $asunto = construirAsuntoDocumentoEmitidoPresupuesto($detalle);
        }

        $cuerpo = trim((string)($input['cuerpo'] ?? ''));
        if ($cuerpo === '') {
            $cuerpo = construirCuerpoDocumentoEmitidoPresupuesto($detalle);
        }

        $resultadoTransporte = [];
        $estadoEnvio = 'fallido';
        $mensajeError = '';

        if ($modoEnvio === 'simulacion') {
            $resultadoTransporte = [
                'ok' => true,
                'msg' => 'Simulacion registrada correctamente.',
                'respuesta' => 'Simulacion OK',
            ];
            $estadoEnvio = 'simulado';
        } else {
            $resultadoTransporte = enviarDocumentoEmitidoPorSmtpPresupuesto($config, $detalle, [
                'para_emails' => $paraEmails,
                'cc_emails' => $ccEmails,
                'cco_emails' => $ccoEmails,
                'asunto' => $asunto,
                'cuerpo' => $cuerpo,
            ]);

            if (!empty($resultadoTransporte['ok'])) {
                $estadoEnvio = 'enviado';
            } else {
                $mensajeError = (string)($resultadoTransporte['msg'] ?? 'No se pudo enviar el documento por SMTP.');
            }
        }

        if ($mensajeError === '') {
            $mensajeError = (string)($resultadoTransporte['msg'] ?? '');
        }

        $db = conectDB();
        if (!$db) {
            return ['ok' => false, 'msg' => 'No se pudo abrir conexion a la base de datos.'];
        }

        mysqli_set_charset($db, 'utf8mb4');
        mysqli_begin_transaction($db);

        try {
            $presupuestoActual = obtenerPresupuestoActualPorPrevisita(
                (int)$detalle['id_previsita'],
                (int)$detalle['id_presupuesto']
            );
            $estadoComercialActivoAntes = $presupuestoActual
                ? obtenerEstadoComercialActivoDesdePresupuesto($presupuestoActual, $modoEnvio)
                : '';
            $debeImpactarCircuitoComercial = $estadoEnvio !== 'fallido'
                && esUltimoDocumentoEmitidoPresupuesto((int)$detalle['id_documento_emitido'], (int)$detalle['id_presupuesto'])
                && $estadoComercialActivoAntes === '';

            $idEnvio = registrarEnvioDocumentoEmitidoPresupuestoEnConexion($db, [
                'id_documento_emitido' => (int)$detalle['id_documento_emitido'],
                'id_presupuesto' => (int)$detalle['id_presupuesto'],
                'id_previsita' => (int)$detalle['id_previsita'],
                'id_usuario' => $idUsuario,
                'modo_envio' => $modoEnvio,
                'estado_envio' => $estadoEnvio,
                'para_email' => serializarListaEmailsDocumentoEmitido($paraEmails),
                'cc' => serializarListaEmailsDocumentoEmitido($ccEmails),
                'cco' => serializarListaEmailsDocumentoEmitido($ccoEmails),
                'asunto' => $asunto,
                'cuerpo' => $cuerpo,
                'remitente_email' => trim((string)($config['remitente_email'] ?? '')),
                'remitente_nombre' => trim((string)($config['remitente_nombre'] ?? '')),
                'mensaje_error' => $estadoEnvio === 'fallido' ? $mensajeError : '',
                'respuesta_transporte' => (string)($resultadoTransporte['respuesta'] ?? ''),
            ]);

            $estadoActualizado = false;
            if ($debeImpactarCircuitoComercial) {
                actualizarEstadoComercialPresupuestoEnConexion(
                    $db,
                    (int)$detalle['id_presupuesto'],
                    $modoEnvio,
                    'ENVIADO'
                );

                registrarHistorialComercialPresupuestoEnConexion(
                    $db,
                    (int)$detalle['id_presupuesto'],
                    (int)$detalle['id_previsita'],
                    (int)$detalle['id_documento_emitido'],
                    $idEnvio,
                    $idUsuario,
                    $modoEnvio,
                    'enviado',
                    'ENVIADO'
                );

                $estadoActualizado = true;
            }

            mysqli_commit($db);
        } catch (Throwable $e) {
            mysqli_rollback($db);
            mysqli_close($db);
            return ['ok' => false, 'msg' => $e->getMessage()];
        }

        mysqli_close($db);

        $estadoPresupuestoActual = obtenerEstadoVisiblePresupuestoMail(
            (int)$detalle['id_previsita'],
            (int)$detalle['id_presupuesto'],
            $modoEnvio
        );

        $okProceso = $estadoEnvio !== 'fallido';
        $mensaje = $estadoEnvio === 'simulado'
            ? 'Simulacion registrada. El correo no se envio realmente.'
            : ($estadoEnvio === 'enviado'
                ? 'El documento fue enviado correctamente.'
                : ($mensajeError !== '' ? $mensajeError : 'No se pudo enviar el documento.'));

        return [
            'ok' => $okProceso,
            'id_envio' => isset($idEnvio) ? (int)$idEnvio : 0,
            'estado_envio' => $estadoEnvio,
            'modo_envio' => $modoEnvio,
            'estado_presupuesto_actual' => $estadoPresupuestoActual,
            'estado_presupuesto_actualizado' => !empty($estadoActualizado),
            'impacta_historial_comercial' => !empty($estadoActualizado),
            'msg' => $mensaje,
            'documento' => [
                'id_documento_emitido' => $detalle['id_documento_emitido'],
                'id_presupuesto' => $detalle['id_presupuesto'],
                'id_previsita' => $detalle['id_previsita'],
                'numero_documento' => $detalle['numero_documento'],
                'nombre_archivo' => $detalle['nombre_archivo'],
            ],
        ];
    }
}
