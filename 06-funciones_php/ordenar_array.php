<?php
// function ordenar_array(la matriz, el subindice, "asc" o "desc") [REFERENCE]
function ordenar_array($array, $indice, $orden)
{
    usort($array, function ($a, $b) use ($indice, $orden) {
        if ($orden === 'asc') {
            return strcmp($a[$indice], $b[$indice]);
        } else {
            return strcmp($b[$indice], $a[$indice]);
        }
    });
    return $array;
}
?>