<?php
//  filename: configIni.php path: 00-config/configIni.php
// BASE DE DATOS
if( isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] == 'development' )
{
	define('DB_SERVER', 'localhost');
	define('DB_USER', 'root');
	define('DB_PASS', '');
	define('DB_NAME', 'techos'); 
	define('COLUMN_TYPE', 'COLUMN_TYPE');

} 
else
{ 
	define('DB_SERVER', 'localhost');
	define('DB_USER', 'c1401312_techos');
	define('DB_PASS', 'Eco853Techos');
	define('DB_NAME', 'c1401312_techos');
	define('COLUMN_TYPE', 'column_type');
}




// SESION
define('URL_LOGIN', '../01-views/login.php');
define('SESION_TIME', 7200); // Establece duración máxima de la sesión en segundos (3600 = 1 hora)

define('BASE_PATH', __DIR__ . '/../'); // Esto sube un nivel desde `00-config` hacia la raíz
?>