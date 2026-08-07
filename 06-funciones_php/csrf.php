<?php

if (!function_exists('claveSesionCsrfAdmintech')) {
    function claveSesionCsrfAdmintech(): string
    {
        return '_admintech_csrf';
    }
}

if (!function_exists('tokenCsrfAdmintechTieneFormatoValido')) {
    function tokenCsrfAdmintechTieneFormatoValido($token): bool
    {
        return is_string($token)
            && preg_match('/\A[a-f0-9]{64}\z/D', $token) === 1;
    }
}

if (!function_exists('contextoSesionCsrfAdmintech')) {
    function contextoSesionCsrfAdmintech(): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE
            || !isset($_SESSION['usuario'])
            || !is_array($_SESSION['usuario'])) {
            return null;
        }

        $idUsuario = filter_var(
            $_SESSION['usuario']['id_usuario'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $email = is_string($_SESSION['usuario']['email'] ?? null)
            ? trim($_SESSION['usuario']['email'])
            : '';
        $perfil = is_string($_SESSION['usuario']['perfil'] ?? null)
            ? trim($_SESSION['usuario']['perfil'])
            : '';
        $idSesion = session_id();

        if ($idUsuario === false || $email === '' || $perfil === '' || $idSesion === '') {
            return null;
        }

        return hash(
            'sha256',
            (string)$idUsuario . "\0" . $email . "\0" . $perfil . "\0" . $idSesion
        );
    }
}

if (!function_exists('generarTokenCsrfAdmintech')) {
    function generarTokenCsrfAdmintech(): string
    {
        return bin2hex(random_bytes(32));
    }
}

if (!function_exists('invalidarTokenCsrfAdmintech')) {
    function invalidarTokenCsrfAdmintech(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[claveSesionCsrfAdmintech()]);
        }
    }
}

if (!function_exists('obtenerOCrearTokenCsrfAdmintech')) {
    function obtenerOCrearTokenCsrfAdmintech(): string
    {
        $contexto = contextoSesionCsrfAdmintech();
        if ($contexto === null) {
            throw new RuntimeException('No hay una sesión autenticada válida para CSRF.');
        }

        $clave = claveSesionCsrfAdmintech();
        $estado = $_SESSION[$clave] ?? null;
        $token = is_array($estado) ? ($estado['token'] ?? null) : null;
        $contextoGuardado = is_array($estado) ? ($estado['contexto'] ?? null) : null;

        if (!tokenCsrfAdmintechTieneFormatoValido($token)
            || !is_string($contextoGuardado)
            || !hash_equals($contexto, $contextoGuardado)) {
            $token = generarTokenCsrfAdmintech();
            $_SESSION[$clave] = [
                'token' => $token,
                'contexto' => $contexto,
            ];
        }

        return $token;
    }
}

if (!function_exists('rotarTokenCsrfAdmintech')) {
    function rotarTokenCsrfAdmintech(): string
    {
        invalidarTokenCsrfAdmintech();

        return obtenerOCrearTokenCsrfAdmintech();
    }
}

if (!function_exists('extraerTokenCsrfAdmintech')) {
    function extraerTokenCsrfAdmintech(
        string $metodo,
        array $servidor,
        array $cuerpo
    ): ?string {
        if (strtoupper($metodo) !== 'POST') {
            return null;
        }

        if (array_key_exists('HTTP_X_CSRF_TOKEN', $servidor)) {
            return is_string($servidor['HTTP_X_CSRF_TOKEN'])
                ? $servidor['HTTP_X_CSRF_TOKEN']
                : null;
        }

        if (array_key_exists('csrf_token', $cuerpo)) {
            return is_string($cuerpo['csrf_token'])
                ? $cuerpo['csrf_token']
                : null;
        }

        return null;
    }
}

if (!function_exists('validarTokenCsrfAdmintech')) {
    function validarTokenCsrfAdmintech($tokenRecibido): bool
    {
        $contexto = contextoSesionCsrfAdmintech();
        if ($contexto === null || !tokenCsrfAdmintechTieneFormatoValido($tokenRecibido)) {
            return false;
        }

        $estado = $_SESSION[claveSesionCsrfAdmintech()] ?? null;
        if (!is_array($estado)) {
            return false;
        }

        $tokenGuardado = $estado['token'] ?? null;
        $contextoGuardado = $estado['contexto'] ?? null;
        if (!tokenCsrfAdmintechTieneFormatoValido($tokenGuardado)
            || !is_string($contextoGuardado)
            || !hash_equals($contexto, $contextoGuardado)) {
            return false;
        }

        return hash_equals($tokenGuardado, $tokenRecibido);
    }
}
