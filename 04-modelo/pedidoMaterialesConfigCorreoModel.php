<?php

require_once __DIR__ . '/conectDB.php';
require_once __DIR__ . '/presupuestoMailConfigModel.php';

if (!function_exists('defaultsConfiguracionCorreoPedidoMateriales')) {
    function defaultsConfiguracionCorreoPedidoMateriales(): array
    {
        return [
            'id_pedido_materiales_config_correo' => 1,
            'activo' => true,
            'smtp_host' => '',
            'smtp_puerto' => 465,
            'smtp_seguridad' => 'ssl',
            'smtp_auth' => true,
            'smtp_usuario' => '',
            'smtp_password' => '',
            'smtp_password_error' => '',
            'smtp_password_configurada' => false,
            'smtp_password_placeholder' => '',
            'remitente_email' => '',
            'remitente_nombre' => 'Pedido de Materiales AdminTech',
            'destinatarios_to' => '',
            'destinatarios_cc' => '',
            'destinatarios_bcc' => '',
            'asunto_base' => 'Pedido de materiales',
            'cuerpo_base' => '',
            'config_guardada' => false,
            'created_at' => '',
            'updated_at' => '',
            'updated_by' => null,
        ];
    }
}

if (!function_exists('placeholderPasswordSmtpCorreoPedidoMateriales')) {
    function placeholderPasswordSmtpCorreoPedidoMateriales(): string
    {
        return '********';
    }
}

if (!function_exists('normalizarSeguridadSmtpCorreoPedidoMateriales')) {
    function normalizarSeguridadSmtpCorreoPedidoMateriales(?string $seguridad): string
    {
        return normalizarSeguridadSmtpMailPresupuestos($seguridad);
    }
}

if (!function_exists('normalizarEmailCorreoPedidoMateriales')) {
    function normalizarEmailCorreoPedidoMateriales(?string $email): string
    {
        return normalizarEmailMailPresupuestos($email);
    }
}

if (!function_exists('validarEmailCorreoPedidoMateriales')) {
    function validarEmailCorreoPedidoMateriales(?string $email): bool
    {
        return validarEmailMailPresupuestos($email);
    }
}

if (!function_exists('normalizarListaDestinatariosCorreoPedidoMateriales')) {
    function normalizarListaDestinatariosCorreoPedidoMateriales(?string $valor): array
    {
        $texto = str_replace(["\r\n", "\r"], "\n", trim((string)$valor));
        if ($texto === '') {
            return [];
        }

        $partes = preg_split('/[\n,;]+/', $texto) ?: [];
        $emails = [];

        foreach ($partes as $parte) {
            $email = normalizarEmailCorreoPedidoMateriales($parte);
            if ($email !== '') {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }
}

if (!function_exists('serializarListaDestinatariosCorreoPedidoMateriales')) {
    function serializarListaDestinatariosCorreoPedidoMateriales(array $emails): string
    {
        return implode("\n", array_values(array_unique(array_filter(array_map(
            static fn($item): string => normalizarEmailCorreoPedidoMateriales((string)$item),
            $emails
        )))));
    }
}

if (!function_exists('prefijoPasswordSmtpCorreoPedidoMateriales')) {
    function prefijoPasswordSmtpCorreoPedidoMateriales(): string
    {
        return 'encv1:';
    }
}

if (!function_exists('smtpPasswordEstaCifradaCorreoPedidoMateriales')) {
    function smtpPasswordEstaCifradaCorreoPedidoMateriales(?string $valor): bool
    {
        return strpos((string)$valor, prefijoPasswordSmtpCorreoPedidoMateriales()) === 0;
    }
}

if (!function_exists('cifrarPasswordSmtpCorreoPedidoMateriales')) {
    function cifrarPasswordSmtpCorreoPedidoMateriales(string $passwordPlano): array
    {
        if ($passwordPlano === '') {
            return ['ok' => true, 'value' => ''];
        }

        if (!runtimeCifradoMailPresupuestosDisponible()) {
            return ['ok' => false, 'msg' => 'El servidor no tiene OpenSSL disponible para proteger la contrasena SMTP.'];
        }

        $secret = obtenerClaveSecretaMailPresupuestos();
        if ($secret === '') {
            return ['ok' => false, 'msg' => 'Antes de guardar la contrasena SMTP configurá MAIL_PRESUPUESTOS_SECRET o ADMINTECH_MAIL_SECRET por variable de entorno, o el archivo externo /admintech_secrets/mail_secret.php fuera de public_html. El secreto debe tener al menos 32 caracteres.'];
        }

        $key = hash('sha256', $secret, true);
        $cipher = 'aes-256-cbc';
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = random_bytes($ivLength);
        $ciphertext = openssl_encrypt($passwordPlano, $cipher, $key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            return ['ok' => false, 'msg' => 'No se pudo proteger la contrasena SMTP antes de guardarla.'];
        }

        $mac = hash_hmac('sha256', $iv . $ciphertext, $key, true);
        $payload = prefijoPasswordSmtpCorreoPedidoMateriales() . codificarBase64UrlMailPresupuestos($iv . $mac . $ciphertext);

        return ['ok' => true, 'value' => $payload];
    }
}

if (!function_exists('descifrarPasswordSmtpCorreoPedidoMateriales')) {
    function descifrarPasswordSmtpCorreoPedidoMateriales(string $passwordGuardada): array
    {
        if ($passwordGuardada === '') {
            return ['ok' => true, 'value' => ''];
        }

        if (!smtpPasswordEstaCifradaCorreoPedidoMateriales($passwordGuardada)) {
            return ['ok' => true, 'value' => $passwordGuardada, 'legacy' => true];
        }

        if (!runtimeCifradoMailPresupuestosDisponible()) {
            return ['ok' => false, 'msg' => 'El servidor no tiene OpenSSL disponible para leer la contrasena SMTP protegida.'];
        }

        $secret = obtenerClaveSecretaMailPresupuestos();
        if ($secret === '') {
            return ['ok' => false, 'msg' => 'Falta configurar una clave valida para usar la contrasena SMTP guardada: MAIL_PRESUPUESTOS_SECRET / ADMINTECH_MAIL_SECRET o /admintech_secrets/mail_secret.php. Si la clave cambio, las contrasenas cifradas pueden quedar ilegibles.'];
        }

        $payload = substr($passwordGuardada, strlen(prefijoPasswordSmtpCorreoPedidoMateriales()));
        $binario = decodificarBase64UrlMailPresupuestos($payload);
        if ($binario === '') {
            return ['ok' => false, 'msg' => 'La contrasena SMTP guardada no pudo decodificarse correctamente.'];
        }

        $cipher = 'aes-256-cbc';
        $key = hash('sha256', $secret, true);
        $ivLength = openssl_cipher_iv_length($cipher);
        $macLength = 32;

        if (strlen($binario) <= ($ivLength + $macLength)) {
            return ['ok' => false, 'msg' => 'La contrasena SMTP guardada tiene un formato invalido.'];
        }

        $iv = substr($binario, 0, $ivLength);
        $mac = substr($binario, $ivLength, $macLength);
        $ciphertext = substr($binario, $ivLength + $macLength);
        $macEsperada = hash_hmac('sha256', $iv . $ciphertext, $key, true);

        if (!hash_equals($macEsperada, $mac)) {
            return ['ok' => false, 'msg' => 'La contrasena SMTP guardada no pudo verificarse correctamente. Si la clave secreta cambio, las contrasenas cifradas pueden quedar ilegibles.'];
        }

        $passwordPlano = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        if ($passwordPlano === false) {
            return ['ok' => false, 'msg' => 'La contrasena SMTP guardada no pudo descifrarse correctamente.'];
        }

        return ['ok' => true, 'value' => $passwordPlano, 'legacy' => false];
    }
}

if (!function_exists('filaConfiguracionCorreoPedidoMaterialesDesdeDB')) {
    function filaConfiguracionCorreoPedidoMaterialesDesdeDB(): ?array
    {
        $db = conectDB();
        if (!$db) {
            return null;
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            if (!tabla_existe($db, 'pedido_materiales_config_correo')) {
                return null;
            }

            $sql = "
                SELECT
                    id_pedido_materiales_config_correo,
                    activo,
                    smtp_host,
                    smtp_puerto,
                    smtp_seguridad,
                    smtp_auth,
                    smtp_usuario,
                    smtp_password,
                    remitente_email,
                    remitente_nombre,
                    destinatarios_to,
                    destinatarios_cc,
                    destinatarios_bcc,
                    asunto_base,
                    cuerpo_base,
                    created_at,
                    updated_at,
                    updated_by
                FROM pedido_materiales_config_correo
                WHERE id_pedido_materiales_config_correo = 1
                LIMIT 1
            ";
            $stmt = stmt_or_throw($db, $sql);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            mysqli_stmt_close($stmt);

            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('normalizarConfiguracionCorreoPedidoMaterialesDesdeFila')) {
    function normalizarConfiguracionCorreoPedidoMaterialesDesdeFila(?array $row, bool $incluirSecretos = false): array
    {
        $defaults = defaultsConfiguracionCorreoPedidoMateriales();
        if (!$row) {
            return $defaults;
        }

        $passwordGuardada = trim((string)($row['smtp_password'] ?? ''));
        $passwordPlano = '';
        $passwordError = '';

        if ($incluirSecretos && $passwordGuardada !== '') {
            $resultadoPassword = descifrarPasswordSmtpCorreoPedidoMateriales($passwordGuardada);
            if (!empty($resultadoPassword['ok'])) {
                $passwordPlano = (string)($resultadoPassword['value'] ?? '');
            } else {
                $passwordError = (string)($resultadoPassword['msg'] ?? 'No se pudo leer la contrasena SMTP guardada.');
            }
        }

        return array_merge($defaults, [
            'id_pedido_materiales_config_correo' => (int)($row['id_pedido_materiales_config_correo'] ?? 1),
            'activo' => !empty($row['activo']),
            'smtp_host' => trim((string)($row['smtp_host'] ?? '')),
            'smtp_puerto' => (isset($row['smtp_puerto']) && ctype_digit((string)$row['smtp_puerto']) && (int)$row['smtp_puerto'] >= 1 && (int)$row['smtp_puerto'] <= 65535)
                ? (int)$row['smtp_puerto']
                : 465,
            'smtp_seguridad' => normalizarSeguridadSmtpCorreoPedidoMateriales($row['smtp_seguridad'] ?? null),
            'smtp_auth' => !isset($row['smtp_auth']) || !empty($row['smtp_auth']),
            'smtp_usuario' => trim((string)($row['smtp_usuario'] ?? '')),
            'smtp_password' => $incluirSecretos ? $passwordPlano : '',
            'smtp_password_error' => $passwordError,
            'smtp_password_configurada' => $passwordGuardada !== '',
            'smtp_password_placeholder' => $passwordGuardada !== '' ? placeholderPasswordSmtpCorreoPedidoMateriales() : '',
            'remitente_email' => trim((string)($row['remitente_email'] ?? '')),
            'remitente_nombre' => trim((string)($row['remitente_nombre'] ?? '')),
            'destinatarios_to' => trim((string)($row['destinatarios_to'] ?? '')),
            'destinatarios_cc' => trim((string)($row['destinatarios_cc'] ?? '')),
            'destinatarios_bcc' => trim((string)($row['destinatarios_bcc'] ?? '')),
            'asunto_base' => trim((string)($row['asunto_base'] ?? '')),
            'cuerpo_base' => trim((string)($row['cuerpo_base'] ?? '')),
            'config_guardada' => true,
            'created_at' => (string)($row['created_at'] ?? ''),
            'updated_at' => (string)($row['updated_at'] ?? ''),
            'updated_by' => isset($row['updated_by']) ? (int)$row['updated_by'] : null,
        ]);
    }
}

if (!function_exists('obtenerConfiguracionCorreoPedidoMateriales')) {
    function obtenerConfiguracionCorreoPedidoMateriales(bool $incluirSecretos = false): array
    {
        return normalizarConfiguracionCorreoPedidoMaterialesDesdeFila(
            filaConfiguracionCorreoPedidoMaterialesDesdeDB(),
            $incluirSecretos
        );
    }
}

if (!function_exists('obtenerConfiguracionCorreoPedidoMaterialesActiva')) {
    function obtenerConfiguracionCorreoPedidoMaterialesActiva(bool $incluirSecretos = false): ?array
    {
        $config = obtenerConfiguracionCorreoPedidoMateriales($incluirSecretos);
        return !empty($config['config_guardada']) && !empty($config['activo']) ? $config : null;
    }
}

if (!function_exists('validarConfiguracionCorreoPedidoMateriales')) {
    function validarConfiguracionCorreoPedidoMateriales(array $config, array $options = []): array
    {
        $errores = [];
        $exigirPassword = !empty($options['exigir_password']);
        $smtpAuth = !isset($config['smtp_auth']) || !empty($config['smtp_auth']);
        $smtpHost = trim((string)($config['smtp_host'] ?? ''));
        $smtpPuertoRaw = trim((string)($config['smtp_puerto'] ?? ''));
        $smtpPuerto = 0;
        $smtpSeguridad = normalizarSeguridadSmtpCorreoPedidoMateriales($config['smtp_seguridad'] ?? null);
        $smtpUsuario = normalizarEmailCorreoPedidoMateriales($config['smtp_usuario'] ?? '');
        $remitenteEmail = normalizarEmailCorreoPedidoMateriales($config['remitente_email'] ?? '');
        $destinatariosTo = normalizarListaDestinatariosCorreoPedidoMateriales($config['destinatarios_to'] ?? '');
        $destinatariosCc = normalizarListaDestinatariosCorreoPedidoMateriales($config['destinatarios_cc'] ?? '');
        $destinatariosBcc = normalizarListaDestinatariosCorreoPedidoMateriales($config['destinatarios_bcc'] ?? '');
        $asuntoBase = trim((string)($config['asunto_base'] ?? ''));
        $password = trim((string)($config['smtp_password'] ?? ''));
        $passwordConfigurada = !empty($config['smtp_password_configurada']) || $password !== '';

        if ($smtpHost === '') {
            $errores[] = 'El host SMTP es obligatorio.';
        }

        if ($smtpPuertoRaw === '' || !ctype_digit($smtpPuertoRaw)) {
            $errores[] = 'El puerto SMTP debe ser un numero entero entre 1 y 65535.';
        } else {
            $smtpPuerto = (int)$smtpPuertoRaw;

            if ($smtpPuerto < 1 || $smtpPuerto > 65535) {
                $errores[] = 'El puerto SMTP debe ser un numero entero entre 1 y 65535.';
            }
        }

        if (!in_array($smtpSeguridad, ['ssl', 'tls', 'ninguna'], true)) {
            $errores[] = 'La seguridad SMTP debe ser SSL, TLS o Ninguna.';
        }

        if (!validarEmailCorreoPedidoMateriales($remitenteEmail)) {
            $errores[] = 'El email remitente es obligatorio y debe tener un formato valido.';
        }

        if ($smtpAuth && !validarEmailCorreoPedidoMateriales($smtpUsuario)) {
            $errores[] = 'El usuario SMTP es obligatorio y debe ser una cuenta de correo valida cuando la autenticacion esta activa.';
        }

        if (!empty($config['smtp_password_error'])) {
            $errores[] = (string)$config['smtp_password_error'];
        } elseif ($smtpAuth && $exigirPassword && !$passwordConfigurada) {
            $errores[] = 'La contrasena SMTP es obligatoria mientras la autenticacion SMTP este activa y no exista una guardada.';
        }

        if (!$destinatariosTo) {
            $errores[] = 'Debe informar al menos un destinatario principal en Para.';
        }

        foreach ([
            'Para' => $destinatariosTo,
            'CC' => $destinatariosCc,
            'CCO' => $destinatariosBcc,
        ] as $etiqueta => $lista) {
            foreach ($lista as $email) {
                if (!validarEmailCorreoPedidoMateriales($email)) {
                    $errores[] = 'Uno de los destinatarios de ' . $etiqueta . ' no tiene un formato de email valido.';
                    break;
                }
            }
        }

        if ($asuntoBase === '') {
            $errores[] = 'El asunto base es obligatorio.';
        }

        return [
            'ok' => !$errores,
            'errores' => array_values(array_unique($errores)),
            'destinatarios_to' => $destinatariosTo,
            'destinatarios_cc' => $destinatariosCc,
            'destinatarios_bcc' => $destinatariosBcc,
        ];
    }
}

if (!function_exists('guardarConfiguracionCorreoPedidoMateriales')) {
    function guardarConfiguracionCorreoPedidoMateriales(array $data, int $idUsuario): array
    {
        if ($idUsuario <= 0) {
            return ['ok' => false, 'msg' => 'Usuario invalido para guardar la configuracion.'];
        }

        $filaActual = filaConfiguracionCorreoPedidoMaterialesDesdeDB();
        $configActual = normalizarConfiguracionCorreoPedidoMaterialesDesdeFila($filaActual, true);
        $passwordStorageActual = trim((string)($filaActual['smtp_password'] ?? ''));
        $passwordNueva = trim((string)($data['smtp_password'] ?? ''));

        $activo = !empty($data['activo']) ? 1 : 0;
        $smtpHost = trim((string)($data['smtp_host'] ?? ''));
        $smtpPuerto = trim((string)($data['smtp_puerto'] ?? ''));
        $smtpSeguridad = normalizarSeguridadSmtpCorreoPedidoMateriales($data['smtp_seguridad'] ?? null);
        $smtpAuth = !empty($data['smtp_auth']) ? 1 : 0;
        $smtpUsuario = normalizarEmailCorreoPedidoMateriales($data['smtp_usuario'] ?? '');
        $remitenteEmail = normalizarEmailCorreoPedidoMateriales($data['remitente_email'] ?? '');
        $remitenteNombre = trim((string)($data['remitente_nombre'] ?? ''));
        $asuntoBase = trim((string)($data['asunto_base'] ?? ''));
        $cuerpoBase = trim((string)($data['cuerpo_base'] ?? ''));

        if ($remitenteNombre === '') {
            $remitenteNombre = 'Pedido de Materiales AdminTech';
        }

        $passwordResueltaParaValidar = $passwordNueva !== ''
            ? $passwordNueva
            : trim((string)($configActual['smtp_password'] ?? ''));
        $passwordConfigurada = $passwordNueva !== '' || $passwordStorageActual !== '';

        $validacion = validarConfiguracionCorreoPedidoMateriales([
            'activo' => $activo,
            'smtp_host' => $smtpHost,
            'smtp_puerto' => $smtpPuerto,
            'smtp_seguridad' => $smtpSeguridad,
            'smtp_auth' => $smtpAuth,
            'smtp_usuario' => $smtpUsuario,
            'smtp_password' => $passwordResueltaParaValidar,
            'smtp_password_configurada' => $passwordConfigurada,
            'smtp_password_error' => trim((string)($configActual['smtp_password_error'] ?? '')),
            'remitente_email' => $remitenteEmail,
            'remitente_nombre' => $remitenteNombre,
            'destinatarios_to' => $data['destinatarios_to'] ?? '',
            'destinatarios_cc' => $data['destinatarios_cc'] ?? '',
            'destinatarios_bcc' => $data['destinatarios_bcc'] ?? '',
            'asunto_base' => $asuntoBase,
            'cuerpo_base' => $cuerpoBase,
        ], [
            'exigir_password' => $smtpAuth,
        ]);

        if (!$validacion['ok']) {
            return ['ok' => false, 'msg' => implode(' ', $validacion['errores'])];
        }

        $smtpPasswordStorage = $passwordStorageActual;
        if ($smtpAuth) {
            if ($passwordNueva !== '') {
                $resultadoCifrado = cifrarPasswordSmtpCorreoPedidoMateriales($passwordNueva);
                if (empty($resultadoCifrado['ok'])) {
                    return ['ok' => false, 'msg' => $resultadoCifrado['msg'] ?? 'No se pudo proteger la contrasena SMTP.'];
                }

                $smtpPasswordStorage = (string)($resultadoCifrado['value'] ?? '');
            } elseif (
                $passwordStorageActual !== ''
                && trim((string)($configActual['smtp_password'] ?? '')) !== ''
                && !smtpPasswordEstaCifradaCorreoPedidoMateriales($passwordStorageActual)
                && runtimeCifradoMailPresupuestosDisponible()
                && hayClaveSecretaMailPresupuestos()
            ) {
                $resultadoMigracion = cifrarPasswordSmtpCorreoPedidoMateriales((string)$configActual['smtp_password']);
                if (!empty($resultadoMigracion['ok'])) {
                    $smtpPasswordStorage = (string)($resultadoMigracion['value'] ?? $passwordStorageActual);
                }
            }
        } else {
            $smtpUsuario = '';
            $smtpPasswordStorage = '';
        }

        $db = conectDB();
        if (!$db) {
            return ['ok' => false, 'msg' => 'No se pudo abrir conexion a la base de datos.'];
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            if (!tabla_existe($db, 'pedido_materiales_config_correo')) {
                throw new RuntimeException('La tabla de configuracion de correo de Pedido de Materiales no existe en la base de datos.');
            }

            $destinatariosTo = serializarListaDestinatariosCorreoPedidoMateriales($validacion['destinatarios_to']);
            $destinatariosCc = serializarListaDestinatariosCorreoPedidoMateriales($validacion['destinatarios_cc']);
            $destinatariosBcc = serializarListaDestinatariosCorreoPedidoMateriales($validacion['destinatarios_bcc']);

            $sql = "
                INSERT INTO pedido_materiales_config_correo (
                    id_pedido_materiales_config_correo,
                    activo,
                    smtp_host,
                    smtp_puerto,
                    smtp_seguridad,
                    smtp_auth,
                    smtp_usuario,
                    smtp_password,
                    remitente_email,
                    remitente_nombre,
                    destinatarios_to,
                    destinatarios_cc,
                    destinatarios_bcc,
                    asunto_base,
                    cuerpo_base,
                    created_at,
                    updated_at,
                    updated_by
                ) VALUES (
                    1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?
                )
                ON DUPLICATE KEY UPDATE
                    activo = VALUES(activo),
                    smtp_host = VALUES(smtp_host),
                    smtp_puerto = VALUES(smtp_puerto),
                    smtp_seguridad = VALUES(smtp_seguridad),
                    smtp_auth = VALUES(smtp_auth),
                    smtp_usuario = VALUES(smtp_usuario),
                    smtp_password = VALUES(smtp_password),
                    remitente_email = VALUES(remitente_email),
                    remitente_nombre = VALUES(remitente_nombre),
                    destinatarios_to = VALUES(destinatarios_to),
                    destinatarios_cc = VALUES(destinatarios_cc),
                    destinatarios_bcc = VALUES(destinatarios_bcc),
                    asunto_base = VALUES(asunto_base),
                    cuerpo_base = VALUES(cuerpo_base),
                    updated_by = VALUES(updated_by),
                    updated_at = NOW()
            ";
            $stmt = stmt_or_throw($db, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                'isisisssssssssi',
                $activo,
                $smtpHost,
                $smtpPuerto,
                $smtpSeguridad,
                $smtpAuth,
                $smtpUsuario,
                $smtpPasswordStorage,
                $remitenteEmail,
                $remitenteNombre,
                $destinatariosTo,
                $destinatariosCc,
                $destinatariosBcc,
                $asuntoBase,
                $cuerpoBase,
                $idUsuario
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            return [
                'ok' => true,
                'msg' => 'La configuracion de correo de Pedido de Materiales quedo guardada.',
                'config' => obtenerConfiguracionCorreoPedidoMateriales(),
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        } finally {
            mysqli_close($db);
        }
    }
}
