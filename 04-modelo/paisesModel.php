<?php
function getAllPaises(){

        $db = conectDB();
        $query = "
        SELECT p.*
        FROM paises AS p
        ;"; // trae todos los paises

        $resultado = $db->query($query);
        while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}

        mysqli_close($db);                           // cierra la base de datos

        return $rows; // devuelve un array();
}
?>