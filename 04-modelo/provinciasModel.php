<?php
function getAllProvincias(){

        $db = conectDB();
        $query = "
        SELECT pr.*
        FROM provincias AS pr
        ORDER BY pr.provincia
        ;"; // trae todas las provincias

        $resultado = $db->query($query);
        while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}

        mysqli_close($db);                           // cierra la base de datos

        return $rows; // devuelve un array();
}
 
// $fechaActual = new DateTime(date("Y-m-d H:i:s", time()));
// echo json_encode($res);



?>