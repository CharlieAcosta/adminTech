-- Continuacion de produccion desde el bloque F del consolidado.
-- Usar solo si el consolidado se corto en el bloque E con:
--   #1060 - Nombre de columna duplicado 'estado_comercial_simulacion'
--
-- Ese error indica que las columnas comerciales ya existen en presupuestos.
-- Este script no vuelve a tocar esas columnas; solo asegura el historial
-- comercial y sus acciones de contacto.

-- F) Historial comercial de presupuestos por modo de circuito.
CREATE TABLE IF NOT EXISTS presupuesto_historial_comercial (
    id_historial_comercial INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_presupuesto INT UNSIGNED NOT NULL,
    id_previsita INT UNSIGNED NOT NULL,
    id_documento_emitido INT UNSIGNED DEFAULT NULL,
    id_envio INT UNSIGNED DEFAULT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    modo_circuito ENUM('simulacion','smtp') NOT NULL,
    accion ENUM('enviado','recibido','resolicitado','aprobado','rechazado','cancelado') NOT NULL,
    estado_resultante ENUM('ENVIADO','RECIBIDO','RESOLICITADO','APROBADO','RECHAZADO','CANCELADO') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_historial_comercial),
    KEY idx_phc_previsita_presupuesto (id_previsita, id_presupuesto),
    KEY idx_phc_modo_fecha (modo_circuito, created_at),
    KEY idx_phc_documento (id_documento_emitido),
    KEY idx_phc_envio (id_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- G) Acciones de contacto comercial dentro del historial.
ALTER TABLE presupuesto_historial_comercial
MODIFY accion ENUM(
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
    'respondio'
) NOT NULL;

-- Verificacion sugerida sin information_schema.
SHOW TABLES LIKE 'presupuesto_historial_comercial';
SHOW COLUMNS FROM presupuestos LIKE 'estado_comercial_simulacion';
SHOW COLUMNS FROM presupuestos LIKE 'estado_comercial_smtp';
SHOW COLUMNS FROM presupuesto_historial_comercial LIKE 'accion';
