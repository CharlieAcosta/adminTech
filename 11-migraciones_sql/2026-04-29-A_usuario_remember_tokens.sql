CREATE TABLE IF NOT EXISTS usuario_remember_tokens (
    id_token INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    selector CHAR(24) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    user_agent_hash CHAR(64) DEFAULT NULL,
    ip_creacion VARCHAR(45) DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id_token),
    UNIQUE KEY uq_usuario_remember_tokens_selector (selector),
    KEY idx_usuario_remember_tokens_usuario (id_usuario),
    KEY idx_usuario_remember_tokens_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
