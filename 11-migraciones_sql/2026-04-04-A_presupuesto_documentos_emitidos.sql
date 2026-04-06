CREATE TABLE IF NOT EXISTS presupuesto_documentos_emitidos (
    id_documento_emitido INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_presupuesto INT UNSIGNED NOT NULL,
    id_previsita INT UNSIGNED NOT NULL,
    id_usuario INT UNSIGNED NOT NULL,
    version_presupuesto INT UNSIGNED NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) DEFAULT 'application/pdf',
    tamano_bytes BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_documento_emitido),
    KEY idx_pde_previsita (id_previsita),
    KEY idx_pde_presupuesto (id_presupuesto),
    KEY idx_pde_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
