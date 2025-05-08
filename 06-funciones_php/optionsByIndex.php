<?php
// optionsByIndex($array = a la matriz, $indice = al subindice, $leyenda = leyenda del primer option) [REFERENCE]
function optionsByIndex($array, $indice, $leyenda){
	$options = "";
	
	foreach ($array as $key => $value) {	 
		 $options .= '<option value="'.$value[$indice].'">'.$value[$indice].'</option>';
	}

	return $options;
}
?>