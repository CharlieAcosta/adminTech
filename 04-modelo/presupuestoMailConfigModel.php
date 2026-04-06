<?php

require_once __DIR__ . '/conectDB.php';
require_once __DIR__ . '/presupuestoGeneradoModel.php';

if (!function_exists('defaultsConfiguracionMailPresupuestos')) {
    function defaultsConfiguracionMailPresupuestos(): array
    {
        return [
            'id_configuracion' => 1,
            'modo_envio' => 'simulacion',
            'remitente_email' => '',
            'remitente_nombre' => 'Presupuestos AdminTech',
            'smtp_host' => '',
            'smtp_puerto' => 587,
            'smtp_seguridad' => 'tls',
            'smtp_usuario' => '',
            'smtp_password' => '',
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
        return in_array($seguridad, ['tls', 'ssl', 'ninguna'], true) ? $seguridad : 'tls';
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

if (!function_exists('obtenerConfiguracionMailPresupuestos')) {
    function obtenerConfiguracionMailPresupuestos(): array
    {
        $defaults = defaultsConfiguracionMailPresupuestos();
        $db = conectDB();
        if (!$db) {
            return $defaults;
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
            if (!tabla_existe($db, 'configuracion_mail_presupuestos')) {
                return $defaults;
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

            if (!$row) {
                return $defaults;
            }

            return array_merge($defaults, [
                'id_configuracion' => (int)($row['id_configuracion'] ?? 1),
                'modo_envio' => normalizarModoEnvioMailPresupuestos($row['modo_envio'] ?? null),
                'remitente_email' => trim((string)($row['remitente_email'] ?? '')),
                'remitente_nombre' => trim((string)($row['remitente_nombre'] ?? '')),
                'smtp_host' => trim((string)($row['smtp_host'] ?? '')),
                'smtp_puerto' => isset($row['smtp_puerto']) ? (int)$row['smtp_puerto'] : 587,
                'smtp_seguridad' => normalizarSeguridadSmtpMailPresupuestos($row['smtp_seguridad'] ?? null),
                'smtp_usuario' => trim((string)($row['smtp_usuario'] ?? '')),
                'smtp_password' => trim((string)($row['smtp_password'] ?? '')),
                'created_at' => (string)($row['created_at'] ?? ''),
                'updated_at' => (string)($row['updated_at'] ?? ''),
                'updated_by' => isset($row['updated_by']) ? (int)$row['updated_by'] : null,
            ]);
        } catch (Throwable $e) {
            return $defaults;
        } finally {
            mysqli_close($db);
        }
    }
}

if (!function_exists('guardarConfiguracionMailPresupuestos')) {
    function guardarConfiguracionMailPresupuestos(array $data, int $idUsuario): array
    {
        if ($idUsuario <= 0) {
            return ['ok' => false, 'msg' => 'Usuario inválido para guardar la configuración.'];
        }

        $modoEnvio = normalizarModoEnvioMailPresupuestos($data['modo_envio'] ?? null);
        $remitenteEmail = trim((string)($data['remitente_email'] ?? ''));
        $remitenteNombre = trim((string)($data['remitente_nombre'] ?? ''));
        $smtpHost = trim((string)($data['smtp_host'] ?? ''));
        $smtpPuerto = (int)($data['smtp_puerto'] ?? 0);
        $smtpSeguridad = normalizarSeguridadSmtpMailPresupuestos($data['smtp_seguridad'] ?? null);
        $smtpUsuario = trim((string)($data['smtp_usuario'] ?? ''));
        $smtpPassword = trim((string)($data['smtp_password'] ?? ''));

        if ($remitenteEmail !== '' && !validarEmailMailPresupuestos($remitenteEmail)) {
            return ['ok' => false, 'msg' => 'El email remitente no tiene un formato válido.'];
        }

        if ($remitenteNombre === '') {
            $remitenteNombre = 'Presupuestos AdminTech';
        }

        if ($smtpPuerto <= 0) {
            $smtpPuerto = 587;
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
                $smtpPassword,
                $idUsuario
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            return [
                'ok' => true,
                'msg' => 'La configuración de mail quedó guardada.',
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
            $tipo = 'cco';
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
