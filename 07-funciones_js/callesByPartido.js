// funcion ajax para traer las calles correspondiente a un partido
var calles;

$("#partido, .partido").change(function(){

        $.ajax({
            url: '../04-modelo/callesModel.php',
            type: 'POST',
            dataType: 'json',
            //data: $('#inversion').serialize(),
            data: {
               'ajax'		  : 'on',	
               'id_partido' : $(this).val()    
            },
            success: function (data) {
            	//console.log('success: '+(data));
            	calles = Object.values(data);                               
               //optionSelect(objeto, value, leyenda, destino, claseRemove) [reference]
            	optionSelect(calles, 'id_calle', 'calle', "#calle", "callesRemove");
            },
            error: function (data) {
            	console.log('error: '+data);
            }
        });   

});




