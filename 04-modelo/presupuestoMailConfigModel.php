<?php

require_once __DIR__ . '/conectDB.php';
require_once __DIR__ . '/presupuestoGeneradoModel.php';

if (!function_exists('resolverModoEnvioDefaultMailPresupuestos')) {
    function resolverModoEnvioDefaultMailPresupuestos(): string
    {
        if (function_exists('admintechEsEntornoProduccion')) {
            return admintechEsEntornoProduccion() ? 'smtp' : 'simulacion';
        }

        $appEnv = strtolower(trim((string)(getenv('APP_ENV') ?: 'production')));
        return in_array(
            $appEnv,
            ['development', 'dev', 'local', 'test', 'testing', 'qa', 'staging', 'preproduction', 'preproduccion'],
            true
        )
            ? 'simulacion'
            : 'smtp';
    }
}

if (!function_exists('textoAyudaHostSmtpDonWebMailPresupuestos')) {
    function textoAyudaHostSmtpDonWebMailPresupuestos(): string
    {
        return 'En DonWeb/Ferozo el host SMTP suele tener formato c######.ferozo.com o l######.ferozo.com. Usar el servidor exacto informado por DonWeb en los datos de configuración de la cuenta de correo. No reemplazarlo por el dominio propio.';
    }
}

if (!function_exists('placeholderPasswordSmtpMailPresupuestos')) {
    function placeholderPasswordSmtpMailPresupuestos(): string
    {
        return '********';
    }
}

if (!function_exists('defaultsConfiguracionMailPresupuestos')) {
    function defaultsConfiguracionMailPresupuestos(): array
    {
        return [
            'id_configuracion' => 1,
            'modo_envio' => resolverModoEnvioDefaultMailPresupuestos(),
            'remitente_email' => '',
            'remitente_nombre' => 'Presupuestos AdminTech',
            'smtp_host' => '',
            'smtp_puerto' => 465,
            'smtp_seguridad' => 'ssl',
            'smtp_usuario' => '',
            'smtp_password' => '',
            'smtp_password_configurada' => false,
            'smtp_password_placeholder' => '',
            'smtp_password_requiere_migracion' => false,
            'config_guardada' => false,
            'config_personalizada' => false,
            'created_at' => '',
            'updated_at' => '',
            'updated_by' => null,
        ];
    }
}

if (!function_exists('normalizarModoEnvioMailPresupuestos')) {
    function normalizarModoEnvioMailPresupuestos(?string $modo): string
    {
        $modo = strtolower(trim((string)$modo));
        return in_array($modo, ['simulacion', 'smtp'], true) ? $modo : 'simulacion';
    }
}

if (!function_exists('normalizarSeguridadSmtpMailPresupuestos')) {
    function normalizarSeguridadSmtpMailPresupuestos(?string $seguridad): string
    {
        $seguridad = strtolower(trim((string)$seguridad));

        if (in_array($seguridad, ['none', 'ninguna', 'sin', 'no'], true)) {
            return 'ninguna';
        }

        return in_array($seguridad, ['tls', 'ssl'], true) ? $seguridad : 'ssl';
    }
}

if (!function_exists('describirModoEnvioMailPresupuestos')) {
    function describirModoEnvioMailPresupuestos(?string $modo): string
    {
        return normalizarModoEnvioMailPresupuestos($modo) === 'smtp'
            ? 'Envío real por SMTP'
            : 'Simulación';
    }
}

if (!function_exists('obtenerModoActivoCircuitoComercialPresupuestos')) {
    function obtenerModoActivoCircuitoComercialPresupuestos(): string
    {
        $config = obtenerConfiguracionMailPresupuestos();
        return normalizarModoEnvioMailPresupuestos($config['modo_envio'] ?? null);
    }
}

if (!function_exists('columnaEstadoComercialPresupuestoPorModo')) {
    function columnaEstadoComercialPresupuestoPorModo(?string $modo): string
    {
        return normalizarModoEnvioMailPresupuestos($modo) === 'smtp'
            ? 'estado_comercial_smtp'
            : 'estado_comercial_simulacion';
    }
}

if (!function_exists('validarEmailMailPresupuestos')) {
    function validarEmailMailPresupuestos(?string $email): bool
    {
        $email = trim((string)$email);
        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('normalizarEmailMailPresupuestos')) {
    function normalizarEmailMailPresupuestos(?string $email): string
    {
        return strtolower(trim((string)$email));
    }
}

if (!function_exists('normalizarHostSmtpMailPresupuestos')) {
    function normalizarHostSmtpMailPresupuestos(?string $host): string
    {
        return trim((string)$host);
    }
}

if (!function_exists('runtimeCifradoMailPresupuestosDisponible')) {
    function runtimeCifradoMailPresupuestosDisponible(): bool
    {
        return function_exists('openssl_encrypt')
            && function_exists('openssl_decrypt')
            && function_exists('hash_hmac')
            && function_exists('hash_equals')
            && function_exists('random_bytes');
    }
}

if (!function_exists('obtenerClaveSecretaMailPresupuestos')) {
    function obtenerClaveSecretaMailPresupuestos(): string
    {
        $clave = normalizarClaveSecretaMailPresupuestos(getenv('MAIL_PRESUPUESTOS_SECRET') ?: '');
        if ($clave !== '') {
            return $clave;
        }

        $clave = normalizarClaveSecretaMailPresupuestos(getenv('ADMINTECH_MAIL_SECRET') ?: '');
        if ($clave !== '') {
            return $clave;
        }

        return obtenerClaveSecretaArchivoExternoMailPresupuestos();
    }
}

if (!function_exists('normalizarClaveSecretaMailPresupuestos')) {
    function normalizarClaveSecretaMailPresupuestos(?string $clave): string
    {
        $clave = trim((string)$clave);
        return strlen($clave) >= 32 ? $clave : '';
    }
}

if (!function_exists('rutaArchivoSecretoExternoMailPresupuestos')) {
    function rutaArchivoSecretoExternoMailPresupuestos(): string
    {
        return dirname(dirname(__DIR__)) . '/admintech_secrets/mail_secret.php';
    }
}

if (!function_exists('obtenerClaveSecretaArchivoExternoMailPresupuestos')) {
    function obtenerClaveSecretaArchivoExternoMailPresupuestos(): string
    {
        $archivoSecreto = rutaArchivoSecretoExternoMailPresupuestos();
        if (!is_file($archivoSecreto)) {
            return '';
        }

        $secretos = @include $archivoSecreto;
        if (!is_array($secretos)) {
            return '';
        }

        return normalizarClaveSecretaMailPresupuestos($secretos['ADMINTECH_MAIL_SECRET'] ?? '');
    }
}

if (!function_exists('hayClaveSecretaMailPresupuestos')) {
    function hayClaveSecretaMailPresupuestos(): bool
    {
        return obtenerClaveSecretaMailPresupuestos() !== '';
    }
}

if (!function_exists('prefijoPasswordSmtpCifradaMailPresupuestos')) {
    function prefijoPasswordSmtpCifradaMailPresupuestos(): string
    {
        return 'encv1:';
    }
}

if (!function_exists('smtpPasswordEstaCifradaMailPresupuestos')) {
    function smtpPasswordEstaCifradaMailPresupuestos(?string $valor): bool
    {
        return strpos((string)$valor, prefijoPasswordSmtpCifradaMailPresupuestos()) === 0;
    }
}

if (!function_exists('codificarBase64UrlMailPresupuestos')) {
    function codificarBase64UrlMailPresupuestos(string $valor): string
    {
        return rtrim(strtr(base64_encode($valor), '+/', '-_'), '=');
    }
}

if (!function_exists('decodificarBase64UrlMailPresupuestos')) {
    function decodificarBase64UrlMailPresupuestos(string $valor): string
    {
        $padding = strlen($valor) % 4;
        if ($padding > 0) {
            $valor .= str_repeat('=', 4 - $padding);
        }

        $decodificado = base64_decode(strtr($valor, '-_', '+/'), true);
        return $decodificado === false ? '' : $decodificado;
    }
}

if (!function_exists('cifrarPasswordSmtpMailPresupuestos')) {
    function cifrarPasswordSmtpMailPresupuestos(string $passwordPlano): array
    {
        if ($passwordPlano === '') {
            return ['ok' => true, 'value' => ''];
        }

        if (!runtimeCifradoMailPresupuestosDisponible()) {
            return ['ok' => false, 'msg' => 'El servidor no tiene OpenSSL disponible para proteger la contraseña SMTP.'];
        }

        $secret = obtenerClaveSecretaMailPresupuestos();
        if ($secret === '') {
            return ['ok' => false, 'msg' => 'Antes de guardar la contrasena SMTP configura MAIL_PRESUPUESTOS_SECRET o ADMINTECH_MAIL_SECRET por variable de entorno, o el archivo externo /admintech_secrets/mail_secret.php fuera de public_html. El secreto debe tener al menos 32 caracteres.'];
        }

        $key = hash('sha256', $secret, true);
        $cipher = 'aes-256-cbc';
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = random_bytes($ivLength);
        $ciphertext = openssl_encrypt($passwordPlano, $cipher, $key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            return ['ok' => false, 'msg' => 'No se pudo proteger la contraseña SMTP antes de guardarla.'];
        }

        $mac = hash_hmac('sha256', $iv . $ciphertext, $key, true);
        $payload = prefijoPasswordSmtpCifradaMailPresupuestos() . codificarBase64UrlMailPresupuestos($iv . $mac . $ciphertext);

        if (strlen($payload) > 255) {
            return ['ok' => false, 'msg' => 'La contraseña SMTP es demasiado larga para ser almacenada de forma protegida en la estructura actual.'];
        }

        return ['ok' => true, 'value' => $payload];
    }
}

if (!function_exists('descifrarPasswordSmtpMailPresupuestos')) {
    function descifrarPasswordSmtpMailPresupuestos(string $passwordGuardada): array
    {
        if ($passwordGuardada === '') {
            return ['ok' => true, 'value' => ''];
        }

        if (!smtpPasswordEstaCifradaMailPresupuestos($passwordGuardada)) {
            return ['ok' => true, 'value' => $passwordGuardada, 'legacy' => true];
        }

        if (!runtimeCifradoMailPresupuestosDisponible()) {
            return ['ok' => false, 'msg' => 'El servidor no tiene OpenSSL disponible para leer la contraseña SMTP protegida.'];
        }

        $secret = obtenerClaveSecretaMailPresupuestos();
        if ($secret === '') {
            return ['ok' => false, 'msg' => 'Falta configurar una clave valida para usar la contrasena SMTP guardada: variable de entorno MAIL_PRESUPUESTOS_SECRET/ADMINTECH_MAIL_SECRET o archivo externo /admintech_secrets/mail_secret.php fuera de public_html. Si la clave cambio, las contrasenas SMTP cifradas anteriormente pueden quedar ilegibles.'];
        }

        $payload = substr($passwordGuardada, strlen(prefijoPasswordSmtpCifradaMailPresupuestos()));
        $binario = decodificarBase64UrlMailPresupuestos($payload);
        if ($binario === '') {
            return ['ok' => false, 'msg' => 'La contraseña SMTP guardada no pudo decodificarse correctamente.'];
        }

        $cipher = 'aes-256-cbc';
        $key = hash('sha256', $secret, true);
        $ivLength = openssl_cipher_iv_length($cipher);
        $macLength = 32;

        if (strlen($binario) <= ($ivLength + $macLength)) {
            return ['ok' => false, 'msg' => 'La contraseña SMTP guardada tiene un formato inválido.'];
        }

        $iv = substr($binario, 0, $ivLength);
        $mac = substr($binario, $ivLength, $macLength);
        $ciphertext = substr($binario, $ivLength + $macLength);
        $macEsperada = hash_hmac('sha256', $iv . $ciphertext, $key, true);

        if (!hash_equals($macEsperada, $mac)) {
            return ['ok' => false, 'msg' => 'La contrasena SMTP guardada no pudo verificarse correctamente. Si la clave secreta cambio, las contrasenas SMTP cifradas anteriormente pueden quedar ilegibles.'];
        }

        $passwordPlano = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        if ($passwordPlano === false) {
            return ['ok' => false, 'msg' => 'La contrasena SMTP guardada no pudo descifrarse correctamente. Si la clave secreta cambio, las contrasenas SMTP cifradas anteriormente pueden quedar ilegibles.'];
        }

        return ['ok' => true, 'value' => $passwordPlano, 'legacy' => false];
    }
}

if (!function_exists('obtenerEstadoTransporteSmtpMailPresupuestos')) {
    function obtenerEstadoTransporteSmtpMailPresupuestos(): array
    {
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        $estado = [
            'autoload_path' => $autoload,
            'composer_autoload' => is_file($autoload),
            'phpmailer_disponible' => false,
            'openssl_habilitado' => extension_loaded('openssl'),
            'mbstring_habilitado' => extension_loaded('mbstring'),
            'json_habilitado' => extension_loaded('json'),
            'curl_habilitado' => extension_loaded('curl'),
        ];

        if ($estado['composer_autoload']) {
            require_once $autoload;
            $estado['phpmailer_disponible'] = class_exists('\\PHPMailer\\PHPMailer\\PHPMailer');
        }

        $estado['disponible'] = $estado['composer_autoload']
            && $estado['phpmailer_disponible']
            && $estado['openssl_habilitado']
            && $estado['mbstring_habilitado']
            && $estado['json_habilitado'];

        return $estado;
    }
}

if (!function_exists('smtpTransportMailPresupuestosDisponible')) {
    function smtpTransportMailPresupuestosDisponible(): bool
    {
        $estado = obtenerEstadoTransporteSmtpMailPresupuestos();
        return !empty($estado['disponible']);
    }
}

if (!function_exists('mensajeDisponibilidadTransporteSmtpMailPresupuestos')) {
    function mensajeDisponibilidadTransporteSmtpMailPresupuestos(): string
    {
        $estado = obtenerEstadoTransporteSmtpMailPresupuestos();

        if (!empty($estado['disponible'])) {
            return 'PHPMailer está disponible y el transporte SMTP real puede usarse en este ambiente.';
        }

        if (empty($estado['composer_autoload'])) {
            return 'PHPMailer no está disponible todavía. Ejecutá composer require phpmailer/phpmailer y luego composer install en la raíz del proyecto para habilitar el envío real.';
        }

        if (empty($estado['phpmailer_disponible'])) {
            return 'Existe vendor/autoload.php, pero PHPMailer no está instalado. Ejecutá composer require phpmailer/phpmailer en la raíz del proyecto.';
        }

        if (empty($estado['openssl_habilitado'])) {
            return 'OpenSSL no está habilitado en PHP; no es posible abrir conexiones SMTP seguras con SSL/TLS.';
        }

        if (empty($estado['mbstring_habilitado'])) {
            return 'mbstring no está habilitado en PHP; completá esa extensión antes de habilitar el envío SMTP real.';
        }

        return 'El transporte SMTP real no está disponible todavía en este ambiente.';
    }
}

if (!function_exists('advertenciasRecomendacionSmtpDonWebMailPresupuestos')) {
    function advertenciasRecomendacionSmtpDonWebMailPresupuestos(array $config): array
    {
        $warnings = [];
        $host = normalizarHostSmtpMailPresupuestos($config['smtp_host'] ?? '');
        $port = (int)($config['smtp_puerto'] ?? 0);
        $security = normalizarSeguridadSmtpMailPresupuestos($config['smtp_seguridad'] ?? null);

        if ($host !== '' && stripos($host, '.ferozo.com') === false) {
            $warnings[] = textoAyudaHostSmtpDonWebMailPresupuestos();
        }

        if ($security === 'ssl' && $port > 0 && $port !== 465) {
            $warnings[] = 'Para DonWeb/Ferozo la combinación recomendada es puerto 465 con SSL.';
        }

        if ($port === 465 && $security !== 'ssl') {
            $warnings[] = 'Si usás puerto 465 en DonWeb/Ferozo, la seguridad recomendada es SSL.';
        }

        if ($security === 'tls' && $port > 0 && $port !== 587) {
            $warnings[] = 'Si usás TLS/STARTTLS, el puerto habitual es 587.';
        }

        if ($security === 'ninguna') {
            $warnings[] = 'El modo sin cifrado solo debería usarse si DonWeb/Ferozo lo informa explícitamente en los Datos de Configuración.';
        }

        return array_values(array_unique($warnings));
    }
}

if (!function_exists('validarConfiguracionSmtpMailPresupuestos')) {
    function validarConfiguracionSmtpMailPresupuestos(array $config, array $options = []): array
    {
        $modo = normalizarModoEnvioMailPresupuestos($config['modo_envio'] ?? null);
        $errores = [];
        $advertencias = advertenciasRecomendacionSmtpDonWebMailPresupuestos($config);
        $exigirPassword = !empty($options['exigir_password']);
        $password = trim((string)($config['smtp_password'] ?? ''));
        $passwordConfigurada = !empty($config['smtp_password_configurada']) || $password !== '';

        if ($modo === 'simulacion') {
            return ['ok' => true, 'errores' => [], 'advertencias' => $advertencias];
        }

        $remitenteEmail = normalizarEmailMailPresupuestos($config['remitente_email'] ?? '');
        $smtpHost = normalizarHostSmtpMailPresupuestos($config['smtp_host'] ?? '');
        $smtpPuerto = (int)($config['smtp_puerto'] ?? 0);
        $smtpSeguridad = normalizarSeguridadSmtpMailPresupuestos($config['smtp_seguridad'] ?? null);
        $smtpUsuario = normalizarEmailMailPresupuestos($config['smtp_usuario'] ?? '');

        if (!validarEmailMailPresupuestos($remitenteEmail)) {
            $errores[] = 'El email remitente es obligatorio y debe tener un formato válido para usar SMTP real.';
        }

        if ($smtpHost === '') {
            $errores[] = 'El host SMTP es obligatorio para usar SMTP real.';
        }

        if ($smtpPuerto <= 0) {
            $errores[] = 'El puerto SMTP es obligatorio y debe ser numérico.';
        }

        if (!in_array($smtpSeguridad, ['ssl', 'tls', 'ninguna'], true)) {
            $errores[] = 'La seguridad SMTP debe ser SSL, TLS o Ninguna.';
        }

        if (!validarEmailMailPresupuestos($smtpUsuario)) {
            $errores[] = 'El usuario SMTP es obligatorio y debe ser una cuenta de correo completa.';
        }

        if ($remitenteEmail !== '' && $smtpUsuario !== '' && $remitenteEmail !== $smtpUsuario) {
            $errores[] = 'En DonWeb/Ferozo el email remitente debe coincidir con la cuenta autenticada del usuario SMTP.';
        }

        if (!empty($config['smtp_password_error'])) {
            $errores[] = (string)$config['smtp_password_error'];
        } elseif ($exigirPassword && !$passwordConfigurada) {
            $errores[] = 'La contraseña SMTP es obligatoria cuando el modo activo es SMTP real y todavía no hay una guardada.';
        }

        return [
            'ok' => !$errores,
            'errores' => array_values(array_unique($errores)),
            'advertencias' => $advertencias,
        ];
    }
}

if (!function_exists('configuracionMailPresupuestosFuePersonalizada')) {
    function configuracionMailPresupuestosFuePersonalizada(?array $row): bool
    {
        if (!$row) {
            return false;
        }

        if (!empty($row['updated_by'])) {
            return true;
        }

        $comparables = [
            trim((string)($row['remitente_email'] ?? '')),
            trim((string)($row['smtp_host'] ?? '')),
            trim((string)($row['smtp_usuario'] ?? '')),
            trim((string)($row['smtp_password'] ?? '')),
        ];

        foreach ($comparables as $valor) {
            if ($valor !== '') {
                return true;
            }
        }

        $nombre = trim((string)($row['remitente_nombre'] ?? ''));
        if ($nombre !== '' && $nombre !== 'Presupuestos AdminTech') {
            return true;
        }

        return false;
    }
}

if (!function_exists('filaConfiguracionMailPresupuestosDesdeDB')) {
    function filaConfiguracionMailPresupuestosDesdeDB(): ?array
    {
        $db = conectDB();
        if (!$db) {
            return null;
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            if (!tabla_existe($db, 'configuracion_mail_presupuestos')) {
                return null;
            }

            $sql = "
                SELECT
                    id_configuracion,
                    modo_envio,
                    remitente_email,
                    remitente_nombre,
                    smtp_host,
                    smtp_puerto,
                    smtp_seguridad,
                    smtp_usuario,
                    smtp_password,
                    created_at,
                    updated_at,
                    updated_by
                FROM configuracion_mail_presupuestos
                WHERE id_configuracion = 1
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

if (!function_exists('normalizarConfiguracionMailPresupuestosDesdeFila')) {
    function normalizarConfiguracionMailPresupuestosDesdeFila(?array $row, bool $incluirSecretos = false): array
    {
        $defaults = defaultsConfiguracionMailPresupuestos();
        if (!$row) {
            return $defaults;
        }

        $configPersonalizada = configuracionMailPresupuestosFuePersonalizada($row);
        $modoResuelto = $configPersonalizada
            ? normalizarModoEnvioMailPresupuestos($row['modo_envio'] ?? null)
            : resolverModoEnvioDefaultMailPresupuestos();
        $smtpPuertoResuelto = $configPersonalizada
            ? (isset($row['smtp_puerto']) ? (int)$row['smtp_puerto'] : 465)
            : 465;
        $smtpSeguridadResuelta = $configPersonalizada
            ? normalizarSeguridadSmtpMailPresupuestos($row['smtp_seguridad'] ?? null)
            : 'ssl';
        $passwordGuardada = trim((string)($row['smtp_password'] ?? ''));
        $passwordPlano = '';
        $passwordError = '';

        if ($incluirSecretos && $passwordGuardada !== '') {
            $resultadoPassword = descifrarPasswordSmtpMailPresupuestos($passwordGuardada);
            if (!empty($resultadoPassword['ok'])) {
                $passwordPlano = (string)($resultadoPassword['value'] ?? '');
            } else {
                $passwordError = (string)($resultadoPassword['msg'] ?? 'No se pudo leer la contraseña SMTP guardada.');
            }
        }

        return array_merge($defaults, [
            'id_configuracion' => (int)($row['id_configuracion'] ?? 1),
            'modo_envio' => $modoResuelto,
            'remitente_email' => trim((string)($row['remitente_email'] ?? '')),
            'remitente_nombre' => trim((string)($row['remitente_nombre'] ?? '')),
            'smtp_host' => normalizarHostSmtpMailPresupuestos($row['smtp_host'] ?? ''),
            'smtp_puerto' => $smtpPuertoResuelto > 0 ? $smtpPuertoResuelto : 465,
            'smtp_seguridad' => $smtpSeguridadResuelta,
            'smtp_usuario' => trim((string)($row['smtp_usuario'] ?? '')),
            'smtp_password' => $incluirSecretos ? $passwordPlano : '',
            'smtp_password_error' => $passwordError,
            'smtp_password_configurada' => $passwordGuardada !== '',
            'smtp_password_placeholder' => $passwordGuardada !== '' ? placeholderPasswordSmtpMailPresupuestos() : '',
            'smtp_password_requiere_migracion' => $passwordGuardada !== '' && !smtpPasswordEstaCifradaMailPresupuestos($passwordGuardada),
            'config_guardada' => true,
            'config_personalizada' => $configPersonalizada,
            'created_at' => (string)($row['created_at'] ?? ''),
            'updated_at' => (string)($row['updated_at'] ?? ''),
            'updated_by' => isset($row['updated_by']) ? (int)$row['updated_by'] : null,
        ]);
    }
}

if (!function_exists('obtenerConfiguracionMailPresupuestos')) {
    function obtenerConfiguracionMailPresupuestos(bool $incluirSecretos = false): array
    {
        return normalizarConfiguracionMailPresupuestosDesdeFila(
            filaConfiguracionMailPresupuestosDesdeDB(),
            $incluirSecretos
        );
    }
}

if (!function_exists('guardarConfiguracionMailPresupuestos')) {
    function guardarConfiguracionMailPresupuestos(array $data, int $idUsuario): array
    {
        if ($idUsuario <= 0) {
            return ['ok' => false, 'msg' => 'Usuario inválido para guardar la configuración.'];
        }

        $filaActual = filaConfiguracionMailPresupuestosDesdeDB();
        $configActual = normalizarConfiguracionMailPresupuestosDesdeFila($filaActual, true);
        $passwordStorageActual = trim((string)($filaActual['smtp_password'] ?? ''));
        $passwordNueva = trim((string)($data['smtp_password'] ?? ''));

        $modoEnvio = normalizarModoEnvioMailPresupuestos($data['modo_envio'] ?? null);
        $remitenteEmail = normalizarEmailMailPresupuestos($data['remitente_email'] ?? '');
        $remitenteNombre = trim((string)($data['remitente_nombre'] ?? ''));
        $smtpHost = normalizarHostSmtpMailPresupuestos($data['smtp_host'] ?? '');
        $smtpPuerto = (int)($data['smtp_puerto'] ?? 0);
        $smtpSeguridad = normalizarSeguridadSmtpMailPresupuestos($data['smtp_seguridad'] ?? null);
        $smtpUsuario = normalizarEmailMailPresupuestos($data['smtp_usuario'] ?? '');

        if ($remitenteNombre === '') {
            $remitenteNombre = 'Presupuestos AdminTech';
        }

        if ($smtpPuerto <= 0) {
            $smtpPuerto = 465;
        }

        $passwordResueltaParaValidar = $passwordNueva !== ''
            ? $passwordNueva
            : trim((string)($configActual['smtp_password'] ?? ''));
        $passwordConfigurada = $passwordNueva !== '' || $passwordStorageActual !== '';

        $validacion = validarConfiguracionSmtpMailPresupuestos([
            'modo_envio' => $modoEnvio,
            'remitente_email' => $remitenteEmail,
            'remitente_nombre' => $remitenteNombre,
            'smtp_host' => $smtpHost,
            'smtp_puerto' => $smtpPuerto,
            'smtp_seguridad' => $smtpSeguridad,
            'smtp_usuario' => $smtpUsuario,
            'smtp_password' => $passwordResueltaParaValidar,
            'smtp_password_configurada' => $passwordConfigurada,
            'smtp_password_error' => trim((string)($configActual['smtp_password_error'] ?? '')),
        ], [
            'exigir_password' => $modoEnvio === 'smtp',
        ]);

        if (!$validacion['ok']) {
            return ['ok' => false, 'msg' => implode(' ', $validacion['errores'])];
        }

        $smtpPasswordStorage = $passwordStorageActual;
        if ($passwordNueva !== '') {
            $resultadoCifrado = cifrarPasswordSmtpMailPresupuestos($passwordNueva);
            if (empty($resultadoCifrado['ok'])) {
                return ['ok' => false, 'msg' => $resultadoCifrado['msg'] ?? 'No se pudo proteger la contraseña SMTP.'];
            }

            $smtpPasswordStorage = (string)($resultadoCifrado['value'] ?? '');
        } elseif (
            $passwordStorageActual !== ''
            && trim((string)($configActual['smtp_password'] ?? '')) !== ''
            && !smtpPasswordEstaCifradaMailPresupuestos($passwordStorageActual)
            && runtimeCifradoMailPresupuestosDisponible()
            && hayClaveSecretaMailPresupuestos()
        ) {
            $resultadoMigracion = cifrarPasswordSmtpMailPresupuestos((string)$configActual['smtp_password']);
            if (!empty($resultadoMigracion['ok'])) {
                $smtpPasswordStorage = (string)($resultadoMigracion['value'] ?? $passwordStorageActual);
            }
        }

        $db = conectDB();
        if (!$db) {
            return ['ok' => false, 'msg' => 'No se pudo abrir conexión a la base de datos.'];
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            if (!tabla_existe($db, 'configuracion_mail_presupuestos')) {
                throw new RuntimeException('La tabla de configuración de mail no existe en la base de datos.');
            }

            $sql = "
                INSERT INTO configuracion_mail_presupuestos
                    (id_configuracion, modo_envio, remitente_email, remitente_nombre, smtp_host, smtp_puerto, smtp_seguridad, smtp_usuario, smtp_password, updated_by, created_at, updated_at)
                VALUES
                    (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    modo_envio = VALUES(modo_envio),
                    remitente_email = VALUES(remitente_email),
                    remitente_nombre = VALUES(remitente_nombre),
                    smtp_host = VALUES(smtp_host),
                    smtp_puerto = VALUES(smtp_puerto),
                    smtp_seguridad = VALUES(smtp_seguridad),
                    smtp_usuario = VALUES(smtp_usuario),
                    smtp_password = VALUES(smtp_password),
                    updated_by = VALUES(updated_by),
                    updated_at = NOW()
            ";
            $stmt = stmt_or_throw($db, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                'ssssisssi',
                $modoEnvio,
                $remitenteEmail,
                $remitenteNombre,
                $smtpHost,
                $smtpPuerto,
                $smtpSeguridad,
                $smtpUsuario,
                $smtpPasswordStorage,
                $idUsuario
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $mensaje = 'La configuración de mail quedó guardada.';
            if (!empty($validacion['advertencias'])) {
                $mensaje .= ' ' . implode(' ', $validacion['advertencias']);
            }

            return [
                'ok' => true,
                'msg' => $mensaje,
                'config' => obtenerConfiguracionMailPresupuestos(),
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('listarCopiasConfiguracionMailPresupuestos')) {
    function listarCopiasConfiguracionMailPresupuestos(bool $soloActivas = false): array
    {
        $db = conectDB();
        if (!$db) {
            return [];
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            if (!tabla_existe($db, 'configuracion_mail_presupuestos_copias')) {
                return [];
            }

            $sql = "
                SELECT
                    id_copia,
                    etiqueta,
                    email,
                    tipo,
                    activo,
                    activo_por_defecto,
                    orden,
                    created_at,
                    updated_at,
                    updated_by
                FROM configuracion_mail_presupuestos_copias
            ";

            if ($soloActivas) {
                $sql .= " WHERE activo = 1";
            }

            $sql .= " ORDER BY orden ASC, etiqueta ASC, id_copia ASC";

            $stmt = stmt_or_throw($db, $sql);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $rows = [];

            while ($res && ($row = mysqli_fetch_assoc($res))) {
                $rows[] = [
                    'id_copia' => (int)($row['id_copia'] ?? 0),
                    'etiqueta' => trim((string)($row['etiqueta'] ?? '')),
                    'email' => trim((string)($row['email'] ?? '')),
                    'tipo' => strtolower(trim((string)($row['tipo'] ?? 'cco'))),
                    'activo' => !empty($row['activo']),
                    'activo_por_defecto' => !empty($row['activo_por_defecto']),
                    'orden' => (int)($row['orden'] ?? 0),
                    'created_at' => (string)($row['created_at'] ?? ''),
                    'updated_at' => (string)($row['updated_at'] ?? ''),
                    'updated_by' => isset($row['updated_by']) ? (int)$row['updated_by'] : null,
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

if (!function_exists('existeEmailDuplicadoEnCopiasMailPresupuestos')) {
    function existeEmailDuplicadoEnCopiasMailPresupuestos(mysqli $db, int $idCopia, string $email): bool
    {
        $sql = "
            SELECT id_copia
            FROM configuracion_mail_presupuestos_copias
            WHERE LOWER(TRIM(email)) = ?
              AND id_copia <> ?
            LIMIT 1
        ";
        $stmt = stmt_or_throw($db, $sql);
        mysqli_stmt_bind_param($stmt, 'si', $email, $idCopia);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return !empty($row);
    }
}

if (!function_exists('guardarCopiaConfiguracionMailPresupuestos')) {
    function guardarCopiaConfiguracionMailPresupuestos(array $data, int $idUsuario): array
    {
        if ($idUsuario <= 0) {
            return ['ok' => false, 'msg' => 'Usuario inválido para guardar la copia.'];
        }

        $idCopia = (int)($data['id_copia'] ?? 0);
        $etiqueta = trim((string)($data['etiqueta'] ?? ''));
        $email = normalizarEmailMailPresupuestos($data['email'] ?? '');
        $tipo = strtolower(trim((string)($data['tipo'] ?? 'cco')));
        $activo = !empty($data['activo']) ? 1 : 0;
        $activoPorDefecto = !empty($data['activo_por_defecto']) ? 1 : 0;
        $orden = max(0, (int)($data['orden'] ?? 10));

        if ($etiqueta === '') {
            return ['ok' => false, 'msg' => 'La etiqueta de la copia es obligatoria.'];
        }

        if (!validarEmailMailPresupuestos($email)) {
            return ['ok' => false, 'msg' => 'El email de la copia no tiene un formato válido.'];
        }

        if (!in_array($tipo, ['cc', 'cco'], true)) {
            return ['ok' => false, 'msg' => 'El tipo de copia debe ser CC o CCO.'];
        }

        $db = conectDB();
        if (!$db) {
            return ['ok' => false, 'msg' => 'No se pudo abrir conexión a la base de datos.'];
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            if (!tabla_existe($db, 'configuracion_mail_presupuestos_copias')) {
                throw new RuntimeException('La tabla de copias internas no existe en la base de datos.');
            }

            if (existeEmailDuplicadoEnCopiasMailPresupuestos($db, $idCopia, $email)) {
                return ['ok' => false, 'msg' => 'Ese email ya está configurado en otra copia interna.'];
            }

            if ($idCopia > 0) {
                $sql = "
                    UPDATE configuracion_mail_presupuestos_copias
                    SET
                        etiqueta = ?,
                        email = ?,
                        tipo = ?,
                        activo = ?,
                        activo_por_defecto = ?,
                        orden = ?,
                        updated_by = ?,
                        updated_at = NOW()
                    WHERE id_copia = ?
                ";
                $stmt = stmt_or_throw($db, $sql);
                mysqli_stmt_bind_param(
                    $stmt,
                    'sssiiiii',
                    $etiqueta,
                    $email,
                    $tipo,
                    $activo,
                    $activoPorDefecto,
                    $orden,
                    $idUsuario,
                    $idCopia
                );
            } else {
                $sql = "
                    INSERT INTO configuracion_mail_presupuestos_copias
                        (etiqueta, email, tipo, activo, activo_por_defecto, orden, created_at, updated_at, updated_by)
                    VALUES
                        (?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
                ";
                $stmt = stmt_or_throw($db, $sql);
                mysqli_stmt_bind_param(
                    $stmt,
                    'sssiiii',
                    $etiqueta,
                    $email,
                    $tipo,
                    $activo,
                    $activoPorDefecto,
                    $orden,
                    $idUsuario
                );
            }

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            return ['ok' => true, 'msg' => 'La copia quedó guardada.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('eliminarCopiaConfiguracionMailPresupuestos')) {
    function eliminarCopiaConfiguracionMailPresupuestos(int $idCopia): array
    {
        if ($idCopia <= 0) {
            return ['ok' => false, 'msg' => 'La copia a eliminar es inválida.'];
        }

        $db = conectDB();
        if (!$db) {
            return ['ok' => false, 'msg' => 'No se pudo abrir conexión a la base de datos.'];
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            if (!tabla_existe($db, 'configuracion_mail_presupuestos_copias')) {
                throw new RuntimeException('La tabla de copias internas no existe en la base de datos.');
            }

            $sql = "DELETE FROM configuracion_mail_presupuestos_copias WHERE id_copia = ? LIMIT 1";
            $stmt = stmt_or_throw($db, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $idCopia);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            return ['ok' => true, 'msg' => 'La copia fue eliminada.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('obtenerCopiasActivasPorDefectoMailPresupuestos')) {
    function obtenerCopiasActivasPorDefectoMailPresupuestos(): array
    {
        return array_values(
            array_filter(
                listarCopiasConfiguracionMailPresupuestos(true),
                static fn(array $item): bool => !empty($item['activo_por_defecto'])
            )
        );
    }
}
