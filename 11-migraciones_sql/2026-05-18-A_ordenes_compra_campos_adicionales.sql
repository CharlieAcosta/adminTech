-- Agrega campos administrativos/comerciales adicionales al circuito OC.
-- No incluye CTA CTE, FC, NC, pagos ni retenciones.

DROP PROCEDURE IF EXISTS admintech_add_column_if_missing;

DELIMITER //

CREATE PROCEDURE admintech_add_column_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_column_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
          AND column_name = p_column_name
    ) THEN
        SET @sql_admintech_add_column = CONCAT(
            'ALTER TABLE `',
            p_table_name,
            '` ADD COLUMN ',
            p_column_definition
        );
        PREPARE stmt_admintech_add_column FROM @sql_admintech_add_column;
        EXECUTE stmt_admintech_add_column;
        DEALLOCATE PREPARE stmt_admintech_add_column;
    END IF;
END//

DELIMITER ;

CALL admintech_add_column_if_missing(
    'ordenes_compra',
    'proveedor_nombre_fantasia',
    '`proveedor_nombre_fantasia` VARCHAR(150) NULL AFTER `proveedor`'
);

CALL admintech_add_column_if_missing(
    'ordenes_compra',
    'proveedor_direccion_fiscal',
    '`proveedor_direccion_fiscal` TEXT NULL AFTER `proveedor_nombre_fantasia`'
);

CALL admintech_add_column_if_missing(
    'ordenes_compra',
    'direccion_obra_alternativa',
    '`direccion_obra_alternativa` TEXT NULL AFTER `direccion_entrega`'
);

CALL admintech_add_column_if_missing(
    'ordenes_compra',
    'contacto_compras',
    '`contacto_compras` VARCHAR(255) NULL AFTER `area_facturacion`'
);

CALL admintech_add_column_if_missing(
    'ordenes_compra',
    'contacto_compras_email',
    '`contacto_compras_email` VARCHAR(255) NULL AFTER `contacto_compras`'
);

CALL admintech_add_column_if_missing(
    'ordenes_compra',
    'contacto_compras_telefono',
    '`contacto_compras_telefono` VARCHAR(100) NULL AFTER `contacto_compras_email`'
);

CALL admintech_add_column_if_missing(
    'ordenes_compra',
    'contacto_obra_mantenimiento',
    '`contacto_obra_mantenimiento` VARCHAR(255) NULL AFTER `contacto_sitio_telefono`'
);

CALL admintech_add_column_if_missing(
    'ordenes_compra',
    'contacto_obra_mantenimiento_email',
    '`contacto_obra_mantenimiento_email` VARCHAR(255) NULL AFTER `contacto_obra_mantenimiento`'
);

CALL admintech_add_column_if_missing(
    'ordenes_compra',
    'contacto_obra_mantenimiento_telefono',
    '`contacto_obra_mantenimiento_telefono` VARCHAR(100) NULL AFTER `contacto_obra_mantenimiento_email`'
);

CALL admintech_add_column_if_missing(
    'ordenes_compra',
    'portal_facturacion_url',
    '`portal_facturacion_url` VARCHAR(255) NULL AFTER `instrucciones_facturacion`'
);

CALL admintech_add_column_if_missing(
    'ordenes_compra',
    'requiere_caucion',
    '`requiere_caucion` TINYINT(1) NOT NULL DEFAULT 0 AFTER `observaciones_seguridad`'
);

CALL admintech_add_column_if_missing(
    'ordenes_compra',
    'requiere_poliza_rc',
    '`requiere_poliza_rc` TINYINT(1) NOT NULL DEFAULT 0 AFTER `requiere_caucion`'
);

CALL admintech_add_column_if_missing(
    'ordenes_compra',
    'poliza_rc_detalle',
    '`poliza_rc_detalle` TEXT NULL AFTER `requiere_poliza_rc`'
);

CALL admintech_add_column_if_missing(
    'ordenes_compra',
    'portal_ingreso_obra_url',
    '`portal_ingreso_obra_url` VARCHAR(255) NULL AFTER `poliza_rc_detalle`'
);

DROP PROCEDURE IF EXISTS admintech_add_column_if_missing;
