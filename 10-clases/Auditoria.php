<?php
/**
 * Clase Auditoria
 *
 * Esta clase permite gestionar la auditoría de accesos y acciones de los usuarios dentro del sistema.
 * Proporciona métodos para registrar eventos como accesos, modificaciones, inserciones y eliminaciones,
 * así como la capacidad de auditar la navegación dentro del sistema.
 */

if (!class_exists('AuditoriaException')) {
    class AuditoriaException extends RuntimeException
    {
    }
}

class Auditoria {

    private $conexion;

    /**
     * Constructor
     *
     * Recibe la conexión a la base de datos y verifica si la tabla de auditoría existe. Si no existe, la crea.
     *
     * @param mysqli $conexion Conexión a la base de datos MySQL
     * @param bool $verificarEsquema Mantiene por defecto el comportamiento legacy.
     *                               Debe ser false si el esquema ya fue validado
     *                               antes de una transacción externa.
     */
    public function __construct($conexion, $verificarEsquema = true) {
        if (!$conexion instanceof mysqli) {
            throw new InvalidArgumentException('La conexión de auditoría no es válida.');
        }

        $this->conexion = $conexion;
        if ($verificarEsquema) {
            $this->crearTablaSiNoExiste();
        }
    }

    /**
     * Crear tabla de auditoría si no existe y agregar columna faltante si es necesario.
     */
    private function crearTablaSiNoExiste() {
        $nombreTabla = 'auditoria';
        $verificarTabla = "SHOW TABLES LIKE '$nombreTabla'";
        $resultado = $this->conexion->query($verificarTabla);

        if ($resultado->num_rows == 0) {
            // Crear la tabla si no existe
            $crearTablaSQL = "CREATE TABLE $nombreTabla (
                id_auditoria INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                id_usuario INT(11),
                email_usuario VARCHAR(255),
                perfil_usuario VARCHAR(255),
                accion_realizada ENUM('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'NAVIGATE', 'LOGIN_FAILED') NOT NULL,
                fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_origen VARCHAR(45),
                dispositivo VARCHAR(100),
                navegador VARCHAR(255),
                modulo_afectado VARCHAR(100),
                metodo_acceso VARCHAR(50),
                url_acceso VARCHAR(255),
                descripcion_cambio TEXT,
                datos_previos TEXT  -- Columna añadida para almacenar el valor anterior en modificaciones
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

            $this->conexion->query($crearTablaSQL);
        } else {
            // Verificar si la columna 'datos_previos' ya existe en la tabla
            $verificarColumna = "SHOW COLUMNS FROM $nombreTabla LIKE 'datos_previos'";
            $resultadoColumna = $this->conexion->query($verificarColumna);

            if ($resultadoColumna->num_rows == 0) {
                // Agregar la columna 'datos_previos' si no existe
                $agregarColumnaSQL = "ALTER TABLE $nombreTabla ADD COLUMN datos_previos TEXT";
                $this->conexion->query($agregarColumnaSQL);
            }
        }
    }


    /**
     * Registrar acceso o acción del usuario
     */
    public function registrarAcceso($usuario, $accion, $modulo = null, $url = null, $id_usuario = null, $perfil = null) {
        $ipOrigen = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $dispositivoCompleto = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        $dispositivo = $this->limitarTexto($dispositivoCompleto, 100);
        $navegador = $this->obtenerNavegador($dispositivoCompleto);
        $metodoAcceso = 'WEB';  

        $sql = "INSERT INTO auditoria (id_usuario, email_usuario, perfil_usuario, accion_realizada, fecha_hora, ip_origen, dispositivo, navegador, modulo_afectado, metodo_acceso, url_acceso)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?)";

        $usuario = $this->limitarTexto((string)$usuario, 255);
        $perfil = $perfil === null ? null : $this->limitarTexto((string)$perfil, 255);
        $modulo = $modulo === null ? null : $this->limitarTexto((string)$modulo, 100);
        $url = $url === null ? null : $this->limitarTexto((string)$url, 255);

        $stmt = null;
        try {
            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) {
                throw new AuditoriaException('No se pudo preparar el registro de auditoría.');
            }
            if (!$stmt->bind_param('isssssssss', $id_usuario, $usuario, $perfil, $accion, $ipOrigen, $dispositivo, $navegador, $modulo, $metodoAcceso, $url)) {
                $stmt->close();
                $stmt = null;
                throw new AuditoriaException('No se pudieron vincular los datos de auditoría.');
            }
            if (!$stmt->execute() || $stmt->affected_rows !== 1) {
                $stmt->close();
                $stmt = null;
                throw new AuditoriaException('No se pudo registrar la auditoría.');
            }
            if (!$stmt->close()) {
                $stmt = null;
                throw new AuditoriaException('No se pudo finalizar el registro de auditoría.');
            }
            $stmt = null;

            return true;
        } catch (AuditoriaException $error) {
            $this->cerrarStatementSinInterrumpir($stmt);
            error_log('ADMINTECH Auditoria: no se pudo registrar el acceso.');
            return false;
        } catch (Throwable $error) {
            $this->cerrarStatementSinInterrumpir($stmt);
            error_log('ADMINTECH Auditoria: no se pudo registrar el acceso.');
            return false;
        }
    }

    /**
     * Detectar el navegador utilizado por el usuario
     */
    private function obtenerNavegador($userAgent) {
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) return 'Internet Explorer';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        return 'Desconocido';
    }

    private function limitarTexto($texto, $longitud) {
        $texto = (string)$texto;
        return function_exists('mb_substr')
            ? mb_substr($texto, 0, (int)$longitud, 'UTF-8')
            : substr($texto, 0, (int)$longitud);
    }

    private function cerrarStatementSinInterrumpir($stmt) {
        if (!$stmt instanceof mysqli_stmt) {
            return;
        }

        try {
            $stmt->close();
        } catch (Throwable $error) {
            // Se preserva el error original de auditoría.
        }
    }

    /**
     * Método para manejar las solicitudes AJAX de auditoría.
     * @param array $request
     */
    public function auditoriaAjax($request) {
        // Definir las fechas por defecto si no se proporcionan
        $fechaInicio = isset($request['fecha_inicio']) ? $request['fecha_inicio'] : date('Y-m-d', strtotime('-7 days'));
        $fechaFin = isset($request['fecha_fin']) ? $request['fecha_fin'] : date('Y-m-d');
        $agente = isset($request['agente']) ? $request['agente'] : null;

        // Consulta principal basada en las fechas
        $query = "SELECT * FROM auditoria WHERE fecha_hora BETWEEN ? AND ?";
        $params = [$fechaInicio, $fechaFin];

        // Agregar condición de agente si se seleccionó uno
        if ($agente) {
            $query .= " AND id_usuario = ?";
            $params[] = $agente;
        }

        // Preparar la consulta
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];

        // Procesar los resultados y ajustarlos al formato de la tabla
        while ($fila = $result->fetch_assoc()) {
            $data[] = [
                'id_auditoria' => $fila['id_auditoria'], // ID de la auditoría (PK)
                'id_usuario' => $fila['id_usuario'], // ID del usuario que realizó la acción
                'email_usuario' => $fila['email_usuario'], // Email del usuario
                'perfil_usuario' => $fila['perfil_usuario'], // Perfil del usuario
                'accion_realizada' => $fila['accion_realizada'], // Acción realizada (LOGIN, LOGOUT, etc.)
                'fecha_hora' => $fila['fecha_hora'], // Fecha y hora de la acción
                'ip_origen' => $fila['ip_origen'], // IP desde la cual se realizó la acción
                'dispositivo' => $fila['dispositivo'], // Dispositivo utilizado (navegador)
                'navegador' => $fila['navegador'], // Navegador utilizado
                'modulo_afectado' => $fila['modulo_afectado'], // Módulo afectado por la acción
                'metodo_acceso' => $fila['metodo_acceso'], // Método de acceso (WEB, APP, etc.)
                'url_acceso' => $fila['url_acceso'], // URL accedida
                'descripcion_cambio' => $fila['descripcion_cambio'] // Descripción del cambio (si aplica)
            ];
        }

        // Retornar la respuesta en formato JSON para DataTables
        echo json_encode([
            'data' => $data
        ]);

        $stmt->close();
    }

    /**
     * Registrar una acción de alta en la auditoría.
     *
     * Este método registra un nuevo ingreso en el sistema (INSERT), indicando que se ha realizado una acción de alta.
     *
     * @param int $id_usuario ID del usuario que realiza la acción
     * @param string $usuario Email del usuario que realiza la acción
     * @param string $perfil Perfil del usuario
     * @param string $modulo Módulo en el que se realizó la acción
     * @param string $url URL de acceso relacionada con la acción
     * @param string $descripcionCambio Descripción del cambio realizado
     */
    public function registrarAlta($id_usuario, $usuario, $perfil, $modulo, $url, $descripcionCambio) {
        return $this->registrarAccion($id_usuario, $usuario, $perfil, 'INSERT', $modulo, $url, $descripcionCambio);
    }

    /**
     * Registrar una acción de modificación en la auditoría.
     *
     * Este método registra una modificación en el sistema (UPDATE), almacenando tanto el dato actual como el anterior.
     *
     * @param int $id_usuario ID del usuario que realiza la acción
     * @param string $usuario Email del usuario que realiza la acción
     * @param string $perfil Perfil del usuario
     * @param string $modulo Módulo en el que se realizó la acción
     * @param string $url URL de acceso relacionada con la acción
     * @param string $descripcionCambio Descripción del cambio realizado
     * @param string $datosPrevios Valor anterior al cambio (dato previo)
     */
    public function registrarModificacion($id_usuario, $usuario, $perfil, $modulo, $url, $descripcionCambio, $datosPrevios) {
        return $this->registrarAccion($id_usuario, $usuario, $perfil, 'UPDATE', $modulo, $url, $descripcionCambio, $datosPrevios);
    }

    /**
     * Registrar una acción de borrado en la auditoría.
     *
     * Este método registra una acción de borrado en el sistema (DELETE), indicando que se ha eliminado un registro.
     *
     * @param int $id_usuario ID del usuario que realiza la acción
     * @param string $usuario Email del usuario que realiza la acción
     * @param string $perfil Perfil del usuario
     * @param string $modulo Módulo en el que se realizó la acción
     * @param string $url URL de acceso relacionada con la acción
     * @param string $descripcionCambio Descripción del cambio realizado
     */
    public function registrarBorrado($id_usuario, $usuario, $perfil, $modulo, $url, $descripcionCambio) {
        $this->registrarAccion($id_usuario, $usuario, $perfil, 'DELETE', $modulo, $url, $descripcionCambio);
    }

    /**
     * Método de soporte para registrar acciones en la auditoría.
     *
     * Este método general permite registrar cualquier acción (INSERT, UPDATE, DELETE) y gestiona los detalles
     * de la inserción en la base de datos, incluyendo los datos anteriores cuando se trata de una modificación.
     *
     * @param int $id_usuario ID del usuario que realiza la acción
     * @param string $usuario Email del usuario que realiza la acción
     * @param string $perfil Perfil del usuario
     * @param string $accion Tipo de acción realizada ('INSERT', 'UPDATE', 'DELETE')
     * @param string $modulo Módulo en el que se realizó la acción
     * @param string $url URL de acceso relacionada con la acción
     * @param string $descripcionCambio Descripción del cambio realizado
     * @param string|null $datosPrevios Valor anterior al cambio (solo se usa para modificaciones)
     */
    private function registrarAccion($id_usuario, $usuario, $perfil, $accion, $modulo, $url, $descripcionCambio, $datosPrevios = null) {
        $ipOrigen = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $dispositivoCompleto = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        $dispositivo = $this->limitarTexto($dispositivoCompleto, 100);
        $navegador = $this->obtenerNavegador($dispositivoCompleto); // Determinar el navegador
        $metodoAcceso = 'WEB';                           // Método de acceso (WEB en este caso)

        // Consulta SQL para insertar el registro en la tabla de auditoría
        $sql = "INSERT INTO auditoria (id_usuario, email_usuario, perfil_usuario, accion_realizada, fecha_hora, ip_origen, dispositivo, navegador, modulo_afectado, metodo_acceso, url_acceso, descripcion_cambio, datos_previos)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?, ?)";

        $usuario = $this->limitarTexto((string)$usuario, 255);
        $perfil = $this->limitarTexto((string)$perfil, 255);
        $modulo = $this->limitarTexto((string)$modulo, 100);
        $url = $this->limitarTexto((string)$url, 255);

        $stmt = null;
        try {
            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) {
                throw new AuditoriaException('No se pudo preparar el registro de auditoría.');
            }
            if (!$stmt->bind_param(
                'isssssssssss',
                $id_usuario,
                $usuario,
                $perfil,
                $accion,
                $ipOrigen,
                $dispositivo,
                $navegador,
                $modulo,
                $metodoAcceso,
                $url,
                $descripcionCambio,
                $datosPrevios
            )) {
                $stmt->close();
                $stmt = null;
                throw new AuditoriaException('No se pudieron vincular los datos de auditoría.');
            }
            if (!$stmt->execute() || $stmt->affected_rows !== 1) {
                $stmt->close();
                $stmt = null;
                throw new AuditoriaException('No se pudo registrar la auditoría.');
            }
            if (!$stmt->close()) {
                $stmt = null;
                throw new AuditoriaException('No se pudo finalizar el registro de auditoría.');
            }
            $stmt = null;

            return true;
        } catch (AuditoriaException $error) {
            $this->cerrarStatementSinInterrumpir($stmt);
            error_log('ADMINTECH Auditoria: no se pudo registrar la acción.');
            return false;
        } catch (Throwable $error) {
            $this->cerrarStatementSinInterrumpir($stmt);
            error_log('ADMINTECH Auditoria: no se pudo registrar la acción.');
            return false;
        }
    }

    /**
     * Registrar una visualización en la auditoría.
     *
     * Este método registra una acción de visualización en el sistema (VIEW), indicando que se ha accedido a un módulo o
     * recurso sin realizar modificaciones.
     *
     * @param int $id_usuario ID del usuario que realiza la acción
     * @param string $usuario Email del usuario que realiza la acción
     * @param string $perfil Perfil del usuario
     * @param string $modulo Módulo o recurso visualizado
     * @param string $url URL de acceso relacionada con la visualización
     * @param string $descripcion Descripción breve de la visualización realizada
     */
    public function registrarVisualizacion($id_usuario, $usuario, $perfil, $modulo, $url, $descripcion) {
        $this->registrarAccion($id_usuario, $usuario, $perfil, 'VIEW', $modulo, $url, $descripcion);
    }


}
?>
