<?php
if(isset($_POST['ajax']) && $_POST['ajax']=='on'){
    include '../04-modelo/conectDB.php'; //conecta a la tabla de paises
    if( isset($_POST['id_partido']) ){getLocalidadesByPartido($_POST['id_partido'], 'ajax');}
}

// funcion que devuelve la totalidad de localidades correspondientes a un partido
function getLocalidadesByPartido($id_partido, $metodo){

        $db = conectDB();
        $query = "
        SELECT loc.*
        FROM localidades AS loc
        WHERE loc.id_partido = '".$id_partido."'
        ORDER BY loc.localidad
        ;"; // trae los partidos por provincia seleccionada

        $resultado = $db->query($query);
        while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}

        foreach ($rows as $key => $value) {
            $rows[$key]['localidad'] = utf8_encode(ucwords(mb_strtolower($value['localidad'])));
        }

        mysqli_close($db);                           // cierra la base de datos

        if($metodo!="ajax"){
            return $rows; // es php devuelve un array()
        }else{
            echo json_encode($rows); // es ajax devuelve un jason
        }
}
// end - funcion que devuelve la totalidad de calles correspondientes a un partido
 
// $fechaActual = new DateTime(date("Y-m-d H:i:s", time()));

?>