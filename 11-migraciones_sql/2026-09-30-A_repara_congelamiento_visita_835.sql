-- Reparacion puntual e idempotente del caso productivo confirmado:
-- Presupuesto 326 pertenece a la Pre-visita 835, pero fue creado sin el
-- evento irreversible de congelamiento. No modifica el Presupuesto ni
-- actua si la relacion exacta entre ambos identificadores no existe.
INSERT INTO visita_presupuesto_eventos (
    id_previsita,
    tipo_evento,
    id_presupuesto,
    id_usuario,
    origen,
    created_at
)
SELECT
    p.id_previsita,
    'VISITA_CONGELADA_POR_GENERACION_PRESUPUESTO',
    p.id_presupuesto,
    NULL,
    'GENERACION',
    CURRENT_TIMESTAMP
FROM presupuestos AS p
WHERE p.id_presupuesto = 326
  AND p.id_previsita = 835
ON DUPLICATE KEY UPDATE id_evento = id_evento;
