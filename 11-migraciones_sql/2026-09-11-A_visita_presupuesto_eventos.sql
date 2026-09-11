CREATE TABLE IF NOT EXISTS visita_presupuesto_eventos (
    id_evento INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_previsita INT NOT NULL,
    tipo_evento VARCHAR(64) NOT NULL,
    id_presupuesto INT UNSIGNED NOT NULL,
    id_usuario INT DEFAULT NULL,
    origen ENUM('LEGACY','GENERACION') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_evento),
    UNIQUE KEY uq_vpe_previsita_tipo (id_previsita, tipo_evento),
    KEY idx_vpe_presupuesto (id_presupuesto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill conservador: todo seguimiento con al menos un presupuesto existente
-- ya cruzo historicamente el punto de corte. No modifica datos preexistentes.
-- El presupuesto vinculado respeta el criterio vigente de la aplicacion:
-- version DESC, created_at DESC, id_presupuesto DESC.
INSERT INTO visita_presupuesto_eventos (
    id_previsita,
    tipo_evento,
    id_presupuesto,
    id_usuario,
    origen,
    created_at
)
SELECT DISTINCT
    p.id_previsita,
    'VISITA_CONGELADA_POR_GENERACION_PRESUPUESTO',
    (
        SELECT vigente.id_presupuesto
        FROM presupuestos AS vigente
        WHERE vigente.id_previsita = p.id_previsita
        ORDER BY vigente.version DESC, vigente.created_at DESC, vigente.id_presupuesto DESC
        LIMIT 1
    ),
    NULL,
    'LEGACY',
    CURRENT_TIMESTAMP
FROM presupuestos AS p
WHERE p.id_previsita > 0
ON DUPLICATE KEY UPDATE id_evento = id_evento;
