<?php
require_once(__DIR__ . '/configIni.php'); // Usa las constantes del entorno
date_default_timezone_set('America/Argentina/Buenos_Aires');

$db = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// Manejo de errores explícito (opcional, pero útil)
if (!$db) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}
if (!mysqli_set_charset($db, 'utf8mb4')) {
    die("Error al configurar charset utf8mb4: " . mysqli_error($db));
}
?>
