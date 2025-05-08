<?php
// filename: conectDB.php
function conectDB(){
    include '../00-config/db.php'; //conecta a la base de datos
    if (!$db) {
        echo "Error: No se pudo conectar a MySQL." . PHP_EOL;
        echo "error de depuración: " . mysqli_connect_errno() . PHP_EOL;
        echo "error de depuración: " . mysqli_connect_error() . PHP_EOL;
        exit;
    } 
    return $db;
}
?>