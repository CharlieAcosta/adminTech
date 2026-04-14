ALTER TABLE presupuestos
MODIFY estado ENUM(
    'Borrador',
    'Enviado',
    'Recibido',
    'Resolicitado',
    'Aprobado',
    'Rechazado',
    'Cancelado',
    'Impreso',
    'Emitido'
)
COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Borrador';
