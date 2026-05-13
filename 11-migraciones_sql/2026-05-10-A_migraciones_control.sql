CREATE TABLE IF NOT EXISTS migraciones (
    id_migracion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    archivo VARCHAR(255) NOT NULL UNIQUE,
    estado ENUM('OK','ERROR') DEFAULT 'OK',
    ejecutada_por_id INT,
    ejecutada_por_email VARCHAR(255),
    fecha_ejecucion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    error_mensaje TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
