<?php
if(isset($_POST['ajax']) && $_POST['ajax']=='on'){
    include '../04-modelo/conectDB.php'; //conecta a la tabla de paises
    if( isset($_POST['id_provincia']) ){getPartidosByProvincia($_POST['id_provincia'], 'ajax');}
}

function getPartidosByProvincia($id_provincia, $metodo){
        $db = conectDB();
        $query = "
        SELECT par.*
        FROM partidos AS par
        WHERE par.id_provincia = '".$id_provincia."'
        ORDER BY par.partido
        ;"; // trae los partidos por provincia seleccionada

        $resultado = $db->query($query);
        while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}

        foreach ($rows as $key => $value) {
            $rows[$key]['partido'] = utf8_encode($value['partido']);
        }

        mysqli_close($db);                           // cierra la base de datos

        if($metodo!="ajax"){
            return $rows; // es php devuelve un array()
        }else{
            echo json_encode($rows); // es ajax devuelve un jason
        }
}
 
?>