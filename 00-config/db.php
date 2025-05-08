<?php
// remoto (comentar en trabajo local)
// date_default_timezone_set('America/Argentina/Buenos_Aires');    
// //$db = mysqli_connect("server", "user", "pass", "base");
// $db = mysqli_connect("localhost", "ug000507_techos", "biGAnabi15", "ug000507_techos");

// local (comentar para producción)
date_default_timezone_set('America/Argentina/Buenos_Aires');    
$db = mysqli_connect("localhost", "root", "", "techos");
?>