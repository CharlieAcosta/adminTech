// funcion ajax para traer las calles correspondiente a un partido
var localidades;

$("#partido, .partido").change(function(){
console.log('llegó a partidos');
        $.ajax({
            url: '../04-modelo/localidadesModel.php',
            type: 'POST',
            dataType: 'json',
            //data: $('#inversion').serialize(),
            data: {
               'ajax'		  : 'on',	
               'id_partido' : $(this).val()    
            },
            success: function (data) {
               //console.log('success: '+(data));
            	localidades = Object.values(data);                               
               //optionSelect(objeto, value, leyenda, destino, claseRemove) [reference]
            	optionSelect(localidades, 'id_localidad', 'localidad', "#localidad, .localidad", "localidadesRemove");
            },
            error: function (data) {
            	console.log('error: '+data);
            }
        });   

});




