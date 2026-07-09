CREATE TABLE IF NOT EXISTS `pedido_materiales_snapshots` (
  `id_pedido_materiales_snapshot` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_previsita` INT UNSIGNED NOT NULL,
  `pedido_activo` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `pedido_maximo_visible` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `finalizado` TINYINT(1) NOT NULL DEFAULT 0,
  `accion_guardado` ENUM('guardar','realizar') NOT NULL DEFAULT 'guardar',
  `id_usuario_guardado` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pedido_materiales_snapshot`),
  UNIQUE KEY `uk_pedido_materiales_snapshots_previsita` (`id_previsita`),
  KEY `idx_pedido_materiales_snapshots_usuario` (`id_usuario_guardado`),
  KEY `idx_pedido_materiales_snapshots_finalizado` (`finalizado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `pedido_materiales_snapshot_detalles` (
  `id_pedido_materiales_snapshot_detalle` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pedido_materiales_snapshot` INT UNSIGNED NOT NULL,
  `tipo_fila` ENUM('presupuestado','agregado') NOT NULL,
  `id_tarea` INT UNSIGNED DEFAULT NULL,
  `tarea_nro` INT UNSIGNED DEFAULT NULL,
  `tarea_titulo` VARCHAR(255) DEFAULT NULL,
  `id_material` INT UNSIGNED NOT NULL,
  `material_texto` VARCHAR(255) NOT NULL,
  `cantidad_inicial` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `cantidad_solicitada` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `pedido_1` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `pedido_2` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `pedido_3` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `pedido_4` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `pedido_5` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `estado_autorizacion` ENUM('sin_solicitud','pendiente','autorizada','rechazada') NOT NULL DEFAULT 'sin_solicitud',
  `autorizacion_adicional` DECIMAL(15,4) DEFAULT NULL,
  `pedido_autorizacion_previo` DECIMAL(15,4) DEFAULT NULL,
  `orden_visual` INT UNSIGNED NOT NULL DEFAULT 0,
  `id_usuario_guardado` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pedido_materiales_snapshot_detalle`),
  KEY `idx_pedido_materiales_snapshot_detalles_snapshot` (`id_pedido_materiales_snapshot`),
  KEY `idx_pedido_materiales_snapshot_detalles_tipo_orden` (`tipo_fila`, `orden_visual`),
  KEY `idx_pedido_materiales_snapshot_detalles_material` (`id_material`),
  CONSTRAINT `fk_pedido_materiales_snapshot_detalles_snapshot`
    FOREIGN KEY (`id_pedido_materiales_snapshot`)
    REFERENCES `pedido_materiales_snapshots` (`id_pedido_materiales_snapshot`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
