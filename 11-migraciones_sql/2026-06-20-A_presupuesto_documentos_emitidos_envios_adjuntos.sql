CREATE TABLE IF NOT EXISTS presupuesto_documentos_emitidos_envios_adjuntos (
    id_envio_adjunto INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_envio INT UNSIGNED NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_envio_adjunto),
    KEY idx_pdeea_envio (id_envio),
    KEY idx_pdeea_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
