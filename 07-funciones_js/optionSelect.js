function optionSelect(objeto, value, leyenda, destino, claseRemove){
	var selectSalida = "";
	$(objeto).each(function(index, element){
       selectSalida += '<option ';
       selectSalida += 'class="'+claseRemove+'"';
       selectSalida += 'value="';
       selectSalida += this[value];
       selectSalida += '">';
       selectSalida += this[leyenda];
       selectSalida += '</option>';

	});
$("."+claseRemove).remove();
$(destino).append(selectSalida);
}