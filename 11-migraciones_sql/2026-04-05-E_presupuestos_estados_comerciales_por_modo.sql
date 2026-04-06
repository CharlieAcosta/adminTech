SET @db_name := DATABASE();

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @db_name
              AND TABLE_NAME = 'presupuestos'
              AND COLUMN_NAME = 'estado_comercial_simulacion'
        ),
        'SELECT 1',
        "ALTER TABLE presupuestos ADD COLUMN estado_comercial_simulacion ENUM('ENVIADO','RECIBIDO','RESOLICITADO','APROBADO','RECHAZADO','CANCELADO') NULL AFTER estado"
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @db_name
              AND TABLE_NAME = 'presupuestos'
              AND COLUMN_NAME = 'estado_comercial_smtp'
        ),
        'SELECT 1',
        "ALTER TABLE presupuestos ADD COLUMN estado_comercial_smtp ENUM('ENVIADO','RECIBIDO','RESOLICITADO','APROBADO','RECHAZADO','CANCELADO') NULL AFTER estado_comercial_simulacion"
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
