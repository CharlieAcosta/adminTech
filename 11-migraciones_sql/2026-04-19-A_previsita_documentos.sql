CREATE TABLE IF NOT EXISTS previsita_documentos (
    id_documento_previsita INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_previsita INT UNSIGNED NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    nombre_archivo_original VARCHAR(255) DEFAULT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) DEFAULT NULL,
    tamano_bytes BIGINT UNSIGNED DEFAULT NULL,
    id_usuario_alta INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_documento_previsita),
    KEY idx_previsita_documentos_previsita (id_previsita),
    KEY idx_previsita_documentos_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE previsita_documentos
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

INSERT INTO previsita_documentos (
    id_previsita,
    nombre_archivo,
    nombre_archivo_original,
    ruta_archivo,
    mime_type,
    tamano_bytes,
    id_usuario_alta,
    created_at
)
SELECT
    p.id_previsita,
    p.doc_previsita,
    p.doc_previsita,
    CONCAT('09-adjuntos/previsita/', p.doc_previsita),
    'application/pdf',
    NULL,
    p.log_usuario_id,
    COALESCE(p.log_edicion, p.log_alta, NOW())
FROM previsitas AS p
LEFT JOIN previsita_documentos AS pd
    ON pd.id_previsita = p.id_previsita
   AND CONVERT(pd.ruta_archivo USING utf8mb4) = CONVERT(CONCAT('09-adjuntos/previsita/', p.doc_previsita) USING utf8mb4)
WHERE CHAR_LENGTH(TRIM(COALESCE(p.doc_previsita, ''))) > 0
  AND pd.id_documento_previsita IS NULL;
