-- Script operativo
-- Objetivo:
--   Dejar la pre-visita 107 nuevamente en estado Emitido,
--   conservando el documento emitido y las intervenciones internas,
--   pero limpiando el circuito comercial para repetir pruebas.
--
-- Conserva:
--   - presupuesto_documentos_emitidos
--   - acciones internas en seguimiento_guardados: guardar / emitir
--
-- Elimina:
--   - envios registrados en presupuesto_documentos_emitidos_envios
--   - historial comercial registrado en presupuesto_historial_comercial
--   - acciones comerciales legacy en seguimiento_guardados
--   - estados comerciales aplicados al presupuesto

START TRANSACTION;

DELETE FROM presupuesto_documentos_emitidos_envios
WHERE id_previsita = 107;

DELETE FROM presupuesto_historial_comercial
WHERE id_previsita = 107;

DELETE FROM seguimiento_guardados
WHERE modulo = 3
  AND id_previsita = 107
  AND LOWER(TRIM(accion)) IN (
    'enviar_mail',
    'simular_envio_mail',
    'recibido',
    'resolicitado',
    'aprobado',
    'rechazado',
    'cancelado'
  );

UPDATE presupuestos
SET estado = 'Emitido',
    estado_comercial_simulacion = NULL,
    estado_comercial_smtp = NULL
WHERE id_previsita = 107;

COMMIT;

-- Verificacion sugerida
SELECT id_presupuesto, id_previsita, estado, estado_comercial_simulacion, estado_comercial_smtp
FROM presupuestos
WHERE id_previsita = 107
ORDER BY id_presupuesto DESC;

SELECT id_documento_emitido, id_presupuesto, id_previsita, nombre_archivo
FROM presupuesto_documentos_emitidos
WHERE id_previsita = 107
ORDER BY id_documento_emitido DESC;

SELECT COUNT(*) AS total_envios
FROM presupuesto_documentos_emitidos_envios
WHERE id_previsita = 107;

SELECT COUNT(*) AS total_historial_comercial
FROM presupuesto_historial_comercial
WHERE id_previsita = 107;

SELECT id_seguimiento, accion
FROM seguimiento_guardados
WHERE modulo = 3
  AND id_previsita = 107
ORDER BY id_seguimiento DESC;
