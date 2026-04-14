-- Consolidado de produccion para circuito comercial de presupuestos.
-- Ejecutar solo si en produccion ya fue aplicado:
--   2026-04-04-A_presupuesto_documentos_emitidos.sql
--
-- Este archivo reemplaza la ejecucion individual de:
--   2026-04-05-A_configuracion_mail_presupuestos.sql
--   2026-04-05-B_configuracion_mail_presupuestos_copias.sql
--   2026-04-05-C_presupuesto_documentos_emitidos_envios.sql
--   2026-04-05-D_presupuestos_estados_recibido_resolicitado.sql
--   2026-04-05-E_presupuestos_estados_comerciales_por_modo.sql
--   2026-04-05-F_presupuesto_historial_comercial.sql
--   2026-04-05-G_presupuesto_historial_comercial_acciones_contacto.sql
--
-- Nota DonWeb/phpMyAdmin: no usa information_schema porque algunos usuarios
-- de hosting compartido no tienen permisos para consultarlo. Ejecutar una sola
-- vez por ambiente.

-- A) Configuracion global de mail para presupuestos.
CREATE TABLE IF NOT EXISTS configuracion_mail_presupuestos (
    id_configuracion TINYINT UNSIGNED NOT NULL DEFAULT 1,
    modo_envio ENUM('simulacion','smtp') NOT NULL DEFAULT 'simulacion',
    remitente_email VARCHAR(255) DEFAULT NULL,
    remitente_nombre VARCHAR(150) DEFAULT NULL,
    smtp_host VARCHAR(255) DEFAULT NULL,
    smtp_puerto SMALLINT UNSIGNED DEFAULT NULL,
    smtp_seguridad ENUM('tls','ssl','ninguna') NOT NULL DEFAULT 'tls',
    smtp_usuario VARCHAR(255) DEFAULT NULL,
    smtp_password VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (id_configuracion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO configuracion_mail_presupuestos (
    id_configuracion,
    modo_envio,
    remitente_email,
    remitente_nombre,
    smtp_host,
    smtp_puerto,
    smtp_seguridad,
    smtp_usuario,
    smtp_password,
    updated_by
)
SELECT
    1,
    'simulacion',
    NULL,
    'Presupuestos AdminTech',
    NULL,
    587,
    'tls',
    NULL,
    NULL,
    NULL
WHERE NOT EXISTS (
    SELECT 1
    FROM configuracion_mail_presupuestos
    WHERE id_configuracion = 1
);

-- B) Copias internas configurables para mail de presupuestos.
CREATE TABLE IF NOT EXISTS configuracion_mail_presupuestos_copias (
    id_copia INT UNSIGNED NOT NULL AUTO_INCREMENT,
    etiqueta VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    tipo ENUM('cc','cco') NOT NULL DEFAULT 'cco',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    activo_por_defecto TINYINT(1) NOT NULL DEFAULT 1,
    orden INT UNSIGNED NOT NULL DEFAULT 10,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (id_copia),
    KEY idx_cmpc_activo_orden (activo, orden, etiqueta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- C) Registro de envios de documentos emitidos.
CREATE TABLE IF NOT EXISTS presupuesto_documentos_emitidos_envios (
    id_envio INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_documento_emitido INT UNSIGNED NOT NULL,
    id_presupuesto INT UNSIGNED NOT NULL,
    id_previsita INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    modo_envio ENUM('simulacion','smtp') NOT NULL,
    estado_envio ENUM('simulado','enviado','fallido') NOT NULL,
    para_email TEXT NOT NULL,
    cc TEXT DEFAULT NULL,
    cco TEXT DEFAULT NULL,
    asunto VARCHAR(255) NOT NULL,
    cuerpo MEDIUMTEXT DEFAULT NULL,
    remitente_email VARCHAR(255) DEFAULT NULL,
    remitente_nombre VARCHAR(150) DEFAULT NULL,
    mensaje_error TEXT DEFAULT NULL,
    respuesta_transporte TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_envio),
    KEY idx_pdee_documento (id_documento_emitido),
    KEY idx_pdee_presupuesto (id_presupuesto),
    KEY idx_pdee_previsita (id_previsita),
    KEY idx_pdee_estado_fecha (estado_envio, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- D) Estados principales permitidos en presupuestos.
ALTER TABLE presupuestos
MODIFY estado ENUM(
    'Borrador',
    'Enviado',
    'Recibido',
    'Resolicitado',
    'Aprobado',
    'Rechazado',
    'Cancelado',
    'Impreso',
    'Emitido'
)
COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Borrador';

-- E) Estados comerciales separados por modo de circuito.
ALTER TABLE presupuestos
ADD COLUMN estado_comercial_simulacion ENUM('ENVIADO','RECIBIDO','RESOLICITADO','APROBADO','RECHAZADO','CANCELADO') NULL AFTER estado;

ALTER TABLE presupuestos
ADD COLUMN estado_comercial_smtp ENUM('ENVIADO','RECIBIDO','RESOLICITADO','APROBADO','RECHAZADO','CANCELADO') NULL AFTER estado_comercial_simulacion;

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

-- Verificacion sugerida.
SHOW TABLES LIKE 'configuracion_mail_presupuestos';
SHOW TABLES LIKE 'configuracion_mail_presupuestos_copias';
SHOW TABLES LIKE 'presupuesto_documentos_emitidos_envios';
SHOW TABLES LIKE 'presupuesto_historial_comercial';

SHOW COLUMNS FROM presupuestos LIKE 'estado';
SHOW COLUMNS FROM presupuestos LIKE 'estado_comercial_simulacion';
SHOW COLUMNS FROM presupuestos LIKE 'estado_comercial_smtp';
