CREATE TABLE IF NOT EXISTS `pedido_materiales_pedido_documentos` (
  `id_pedido_materiales_pedido_documento` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pedido_materiales_pedido` INT UNSIGNED NOT NULL,
  `tipo_documento` VARCHAR(30) NOT NULL DEFAULT 'pedido_materiales_pdf',
  `nombre_archivo` VARCHAR(255) NOT NULL,
  `ruta_archivo` VARCHAR(500) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL DEFAULT 'application/pdf',
  `tamano_bytes` BIGINT UNSIGNED NOT NULL,
  `hash_archivo` CHAR(64) NOT NULL,
  `id_usuario_generacion` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pedido_materiales_pedido_documento`),
  UNIQUE KEY `uq_pm_pedido_documento_actual` (`id_pedido_materiales_pedido`, `tipo_documento`),
  KEY `idx_pm_pedido_documento_hash` (`hash_archivo`),
  KEY `idx_pm_pedido_documento_usuario` (`id_usuario_generacion`),
  CONSTRAINT `fk_pm_pedido_documento_pedido`
    FOREIGN KEY (`id_pedido_materiales_pedido`)
    REFERENCES `pedido_materiales_pedidos` (`id_pedido_materiales_pedido`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
