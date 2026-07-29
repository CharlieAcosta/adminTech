CREATE TABLE IF NOT EXISTS `pedido_materiales_pedidos` (
  `id_pedido_materiales_pedido` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_previsita` INT UNSIGNED NOT NULL,
  `id_presupuesto` INT UNSIGNED NOT NULL,
  `id_orden_compra` INT UNSIGNED NOT NULL,
  `numero_pedido` TINYINT UNSIGNED NOT NULL,
  `estado` VARCHAR(30) NOT NULL DEFAULT 'confirmado',
  `id_usuario_confirmacion` INT UNSIGNED NOT NULL,
  `fecha_confirmacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `snapshot_hash` CHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pedido_materiales_pedido`),
  UNIQUE KEY `uq_pm_pedido_previsita_numero` (`id_previsita`, `numero_pedido`),
  KEY `idx_pm_pedido_presupuesto` (`id_presupuesto`),
  KEY `idx_pm_pedido_orden_compra` (`id_orden_compra`),
  KEY `idx_pm_pedido_usuario` (`id_usuario_confirmacion`),
  KEY `idx_pm_pedido_fecha` (`fecha_confirmacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pedido_materiales_pedido_detalles` (
  `id_pedido_materiales_pedido_detalle` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pedido_materiales_pedido` INT UNSIGNED NOT NULL,
  `tipo_fila` ENUM('presupuestado','agregado') NOT NULL,
  `id_tarea` INT UNSIGNED DEFAULT NULL,
  `tarea_nro` INT UNSIGNED DEFAULT NULL,
  `tarea_titulo` VARCHAR(255) DEFAULT NULL,
  `id_material` INT UNSIGNED NOT NULL,
  `material_texto` VARCHAR(500) NOT NULL,
  `cantidad_inicial` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `cantidad_pedido` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `estado_autorizacion` ENUM('sin_solicitud','pendiente','autorizada','rechazada') NOT NULL DEFAULT 'sin_solicitud',
  `orden_visual` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pedido_materiales_pedido_detalle`),
  KEY `idx_pm_pedido_detalles_pedido` (`id_pedido_materiales_pedido`),
  KEY `idx_pm_pedido_detalles_tipo_orden` (`tipo_fila`, `orden_visual`),
  KEY `idx_pm_pedido_detalles_material` (`id_material`),
  CONSTRAINT `fk_pm_pedido_detalles_pedido`
    FOREIGN KEY (`id_pedido_materiales_pedido`)
    REFERENCES `pedido_materiales_pedidos` (`id_pedido_materiales_pedido`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
