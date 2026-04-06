SET @db_name := DATABASE();

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = @db_name
              AND TABLE_NAME = 'presupuesto_historial_comercial'
        ),
        "ALTER TABLE presupuesto_historial_comercial MODIFY accion ENUM('enviado','recibido','resolicitado','aprobado','rechazado','cancelado','llamado','mail_contacto','mensaje_contacto','pendiente_respuesta','respondio') NOT NULL",
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
