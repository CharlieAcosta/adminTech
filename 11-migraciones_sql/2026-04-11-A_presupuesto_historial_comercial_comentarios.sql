-- Ejecutar una sola vez en ambientes donde presupuesto_historial_comercial
-- todavia no tenga la columna comentarios.

ALTER TABLE presupuesto_historial_comercial
ADD COLUMN comentarios TEXT NULL AFTER estado_resultante;

SHOW COLUMNS FROM presupuesto_historial_comercial LIKE 'comentarios';
