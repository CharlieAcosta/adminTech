-- Ejecutar una sola vez en ambientes donde presupuesto_historial_comercial
-- todavia no tenga contemplada la accion reabierto en el ENUM accion.

ALTER TABLE presupuesto_historial_comercial
MODIFY accion ENUM(
    'enviado',
    'recibido',
    'resolicitado',
    'aprobado',
    'rechazado',
    'cancelado',
    'llamado',
    'mail_contacto',
    'mensaje_contacto',
    'pendiente_respuesta',
    'respondio',
    'reabierto'
) NOT NULL;

SHOW COLUMNS FROM presupuesto_historial_comercial LIKE 'accion';
