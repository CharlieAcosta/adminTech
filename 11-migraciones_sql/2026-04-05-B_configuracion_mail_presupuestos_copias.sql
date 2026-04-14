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
