<?php

if (!function_exists('solicitudAdmintechUsaHttps')) {
    function solicitudAdmintechUsaHttps(?array $servidor = null): bool
    {
        $servidor = $servidor ?? $_SERVER;
        $https = $servidor['HTTPS'] ?? null;

        if (is_string($https) && $https !== '' && strtolower($https) !== 'off') {
            return true;
        }

        return isset($servidor['SERVER_PORT'])
            && (int)$servidor['SERVER_PORT'] === 443;
    }
}

if (!function_exists('opcionesCookieSesionAdmintech')) {
    function opcionesCookieSesionAdmintech(?bool $segura = null): array
    {
        $actuales = session_get_cookie_params();
        $path = is_string($actuales['path'] ?? null) && $actuales['path'] !== ''
            ? $actuales['path']
            : '/';

        return [
            'lifetime' => max(0, (int)($actuales['lifetime'] ?? 0)),
            'path' => $path,
            'domain' => is_string($actuales['domain'] ?? null) ? $actuales['domain'] : '',
            'secure' => $segura ?? solicitudAdmintechUsaHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}

if (!function_exists('configurarSesionAdmintech')) {
    function configurarSesionAdmintech(?array $servidor = null): bool
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return false;
        }

        $segura = solicitudAdmintechUsaHttps($servidor);
        $directivas = [
            'session.use_strict_mode' => '1',
            'session.use_only_cookies' => '1',
            'session.cookie_httponly' => '1',
            'session.cookie_samesite' => 'Lax',
            'session.cookie_secure' => $segura ? '1' : '0',
        ];

        foreach ($directivas as $directiva => $valor) {
            if (ini_set($directiva, $valor) === false) {
                return false;
            }
        }

        return session_set_cookie_params(opcionesCookieSesionAdmintech($segura));
    }
}

if (!function_exists('iniciarSesionAdmintech')) {
    function iniciarSesionAdmintech(?array $servidor = null): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        if (session_status() !== PHP_SESSION_NONE || !configurarSesionAdmintech($servidor)) {
            return false;
        }

        return session_start();
    }
}

if (!function_exists('emitirCookieSesionAdmintech')) {
    function emitirCookieSesionAdmintech(?array $servidor = null): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE
            || session_id() === ''
            || headers_sent()
            || !filter_var(ini_get('session.use_cookies'), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $opciones = opcionesCookieSesionAdmintech(solicitudAdmintechUsaHttps($servidor));
        $opciones['expires'] = $opciones['lifetime'] > 0
            ? time() + $opciones['lifetime']
            : 0;
        unset($opciones['lifetime']);

        $emitida = setcookie(session_name(), session_id(), $opciones);
        if ($emitida) {
            $_COOKIE[session_name()] = session_id();
        }

        return $emitida;
    }
}

if (!function_exists('regenerarIdSesionAdmintech')) {
    function regenerarIdSesionAdmintech(?array $servidor = null): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE || headers_sent()) {
            return false;
        }

        if (!session_regenerate_id(true)) {
            return false;
        }

        return emitirCookieSesionAdmintech($servidor);
    }
}

if (!function_exists('reemplazarIdentidadAutenticadaAdmintech')) {
    function reemplazarIdentidadAutenticadaAdmintech(
        array $usuario,
        ?array $servidor = null
    ): bool {
        if (!regenerarIdSesionAdmintech($servidor)) {
            return false;
        }

        if (function_exists('invalidarTokenCsrfAdmintech')) {
            invalidarTokenCsrfAdmintech();
        } else {
            unset($_SESSION['_admintech_csrf']);
        }

        $_SESSION['usuario'] = $usuario;

        return true;
    }
}

if (!function_exists('eliminarCookieSesionAdmintech')) {
    function eliminarCookieSesionAdmintech(?array $servidor = null): bool
    {
        if (!filter_var(ini_get('session.use_cookies'), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        if (headers_sent()) {
            return false;
        }

        $opciones = opcionesCookieSesionAdmintech(solicitudAdmintechUsaHttps($servidor));
        $opciones['expires'] = time() - 42000;
        unset($opciones['lifetime']);

        $eliminada = setcookie(session_name(), '', $opciones);
        if ($eliminada) {
            unset($_COOKIE[session_name()]);
        }

        return $eliminada;
    }
}

if (!function_exists('destruirSesionAdmintech')) {
    function destruirSesionAdmintech(?array $servidor = null): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return eliminarCookieSesionAdmintech($servidor);
        }

        $_SESSION = [];
        session_unset();
        $cookieEliminada = eliminarCookieSesionAdmintech($servidor);
        $sesionDestruida = session_destroy();

        return $cookieEliminada && $sesionDestruida;
    }
}
