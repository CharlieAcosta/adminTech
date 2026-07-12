CREATE TABLE IF NOT EXISTS pedido_materiales_config_correo (
    id_pedido_materiales_config_correo TINYINT UNSIGNED NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    smtp_host VARCHAR(255) DEFAULT NULL,
    smtp_puerto SMALLINT UNSIGNED DEFAULT NULL,
    smtp_seguridad ENUM('tls','ssl','ninguna') NOT NULL DEFAULT 'ssl',
    smtp_auth TINYINT(1) NOT NULL DEFAULT 1,
    smtp_usuario VARCHAR(255) DEFAULT NULL,
    smtp_password TEXT NULL,
    remitente_email VARCHAR(255) DEFAULT NULL,
    remitente_nombre VARCHAR(150) DEFAULT NULL,
    destinatarios_to TEXT DEFAULT NULL,
    destinatarios_cc TEXT DEFAULT NULL,
    destinatarios_bcc TEXT DEFAULT NULL,
    asunto_base VARCHAR(255) DEFAULT NULL,
    cuerpo_base TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (id_pedido_materiales_config_correo),
    KEY idx_pedido_materiales_config_correo_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    updated_by
)
SELECT
    1,
    1,
    NULL,
    465,
    'ssl',
    1,
    NULL,
    NULL,
    NULL,
    'Pedido de Materiales AdminTech',
    NULL,
    NULL,
    NULL,
    'Pedido de materiales',
    NULL,
    NULL
WHERE NOT EXISTS (
    SELECT 1
    FROM pedido_materiales_config_correo
    WHERE id_pedido_materiales_config_correo = 1
);
