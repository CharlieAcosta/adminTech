CREATE TABLE IF NOT EXISTS `pedido_materiales_pedido_envios` (
  `id_pedido_materiales_pedido_envio` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pedido_materiales_pedido` INT UNSIGNED NOT NULL,
  `id_pedido_materiales_pedido_documento` INT UNSIGNED DEFAULT NULL,
  `tipo_envio` VARCHAR(30) NOT NULL DEFAULT 'pedido_materiales_mail',
  `estado` VARCHAR(30) NOT NULL DEFAULT 'pendiente',
  `intentos` INT UNSIGNED NOT NULL DEFAULT 0,
  `destinatarios_to` TEXT NOT NULL,
  `destinatarios_cc` TEXT NULL,
  `destinatarios_bcc` TEXT NULL,
  `asunto` VARCHAR(255) NOT NULL,
  `cuerpo` TEXT NULL,
  `ultimo_error` TEXT NULL,
  `fecha_ultimo_intento` DATETIME NULL,
  `fecha_envio` DATETIME NULL,
  `id_usuario_ultimo_intento` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pedido_materiales_pedido_envio`),
  UNIQUE KEY `uq_pm_pedido_envio_tipo` (`id_pedido_materiales_pedido`, `tipo_envio`),
  KEY `idx_pm_pedido_envio_estado` (`estado`),
  KEY `idx_pm_pedido_envio_documento` (`id_pedido_materiales_pedido_documento`),
  CONSTRAINT `fk_pm_pedido_envio_pedido`
    FOREIGN KEY (`id_pedido_materiales_pedido`)
    REFERENCES `pedido_materiales_pedidos` (`id_pedido_materiales_pedido`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_pm_pedido_envio_documento`
    FOREIGN KEY (`id_pedido_materiales_pedido_documento`)
    REFERENCES `pedido_materiales_pedido_documentos` (`id_pedido_materiales_pedido_documento`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
