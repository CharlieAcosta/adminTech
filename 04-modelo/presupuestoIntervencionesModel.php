<?php

require_once __DIR__ . '/conectDB.php';

if (!function_exists('normalizarAccionIntervencionPresupuesto')) {
    function normalizarAccionIntervencionPresupuesto(string $accion): ?string
    {
        $accion = strtolower(trim($accion));
        $permitidas = ['guardar', 'imprimir', 'emitir', 'enviar_mail'];

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
        ];

        return $map[$accion] ?? ucfirst($accion);
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

if (!function_exists('obtenerIntervencionesPresupuesto')) {
    function obtenerIntervencionesPresupuesto(int $idPrevisita, ?int $idPresupuesto = null): array
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

            while ($res && ($row = mysqli_fetch_assoc($res))) {
                $apellido = trim((string)($row['apellidos'] ?? ''));
                $nombre = trim((string)($row['nombres'] ?? ''));
                $usuarioNombre = trim($apellido . ' ' . $nombre);

                if ($usuarioNombre === '') {
                    $usuarioNombre = 'Usuario #' . (int)($row['id_usuario'] ?? 0);
                }

                $accion = normalizarAccionIntervencionPresupuesto((string)($row['accion'] ?? '')) ?? (string)($row['accion'] ?? '');

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

if (!function_exists('construirResumenIntervencionesPresupuesto')) {
    function construirResumenIntervencionesPresupuesto(array $rows): array
    {
        $headerHtml = '<thead><tr><th>Usuario</th><th>Accion</th><th>Fecha</th></tr></thead>';
        $tablaVacia = '<table class="table table-sm mb-0">'
            . '<thead><tr><th>Usuario</th><th>Acción</th><th>Fecha</th></tr></thead>'
            . '<tbody><tr><td colspan="3" class="text-center text-muted">Sin otras intervenciones</td></tr></tbody>'
            . '</table>';
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
        $html = '<table class="table table-sm mb-0">'
            . '<thead><tr><th>Usuario</th><th>Acción</th><th>Fecha</th></tr></thead>'
            . '<tbody>';
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
            obtenerIntervencionesPresupuesto($idPrevisita, $idPresupuesto)
        );
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

        if ($idPresupuesto <= 0 || $idPrevisita <= 0 || $idUsuario <= 0) {
            return ['ok' => false, 'msg' => 'Datos incompletos para registrar la intervención.'];
        }

        if ($accionNormalizada === null) {
            return ['ok' => false, 'msg' => 'Acción de intervención inválida.'];
        }

        $db = conectDB();
        if (!$db) {
            return ['ok' => false, 'msg' => 'No se pudo abrir conexión a la base de datos.'];
        }

        mysqli_set_charset($db, 'utf8mb4');

        try {
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

            if (!$stmt) {
                throw new RuntimeException('No se pudo preparar el registro de intervención.');
            }

            mysqli_stmt_bind_param($stmt, 'iiis', $idUsuario, $idPrevisita, $idPresupuesto, $accionNormalizada);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

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
