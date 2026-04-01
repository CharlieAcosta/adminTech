<?php
if(isset($_POST['ajax']) && $_POST['ajax']=='on'){
    include '../04-modelo/conectDB.php'; //conecta a la base de datos
    header('Content-Type: application/json; charset=utf-8');
    if( isset($_POST['id_partido']) ){getCallesByPartido($_POST['id_partido'], 'ajax');}
}

// funcion que devuelve la totalidad de calles correspondientes a un partido
function getCallesByPartido($id_partido, $metodo){

        $db = conectDB();
        $query = "
        SELECT cal.*
        FROM calles AS cal
        WHERE cal.id_partido = '".$id_partido."'
        ORDER BY cal.calle
        ;"; // trae las calles por partido seleccionado

        $resultado = $db->query($query);
        while($row = mysqli_fetch_array($resultado, MYSQLI_ASSOC)){$rows[] = $row;}
        if(isset($rows)){
            foreach ($rows as $key => $value) {
                $rows[$key]['calle'] = ucwords(mb_strtolower((string)($value['calle'] ?? '')));
            }
        }
        mysqli_close($db);                           // cierra la base de datos

        if($metodo!="ajax"){
            if(isset($rows)){return $rows;}else{return [];}  // es php devuelve un array()
        }else{
            if(isset($rows)){echo json_encode($rows, JSON_UNESCAPED_UNICODE);}else{echo json_encode([], JSON_UNESCAPED_UNICODE);}// es ajax devuelve un jason
        }
}
// end - funcion que devuelve la totalidad de calles correspondientes a un partido
 
// $fechaActual = new DateTime(date("Y-m-d H:i:s", time()));

?>
