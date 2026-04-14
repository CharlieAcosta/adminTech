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
