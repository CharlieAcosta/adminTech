// funcion ajax para traer los partidos correspondiente a las provincias
var partidos;

$("#provincia, .provincia").change(function(){
//console.log('llegó');   
        $.ajax({
            url: '../04-modelo/partidosModel.php',
            type: 'POST',
            dataType: 'json',
            //data: $('#inversion').serialize(), [example]
            data: {
               'ajax'		  : 'on',	
               'id_provincia' : $(this).val()    
            },
            success: function (data) {
            //console.log('success: '+(data));
            	partidos = Object.values(data);                               

                $("#partido, .partido").html('<option value="" disabled selected class="bg-secondary partidosOpt1">Partido</option>');
                if(!$("#partido, .partido").prev().find("i:eq(0)").hasClass('v-requerido-icon-off')){
                    $("#partido, .partido").prev().find("i:eq(0)").removeClass("text-success").addClass("text-danger");
                }
                $("#partido, .partido").select2('val', null); 

                $("#localidad, .localidad").html('<option value="" disabled selected class="bg-secondary">Localidad</option>');
                if(!$("#localidad, .localidad").prev().find("i:eq(0)").hasClass('v-requerido-icon-off')){
                    $("#localidad, .localidad").prev().find("i:eq(0)").removeClass("text-success").addClass("text-danger");
                }            
                $("#localidad, .localidad").select2('val', null);

                $("#calle, .calle").html('<option value="" disabled selected class="bg-secondary">Calle</option>');
                if(!$("#calle, .calle").prev().find("i:eq(0)").hasClass('v-requerido-icon-off')){
                    $("#calle, .calle").prev().find("i:eq(0)").removeClass("text-success").addClass("text-danger");
                }        
                $("#calle, .calle").select2('val', null);

               //optionSelect(objeto, value, leyenda, destino, claseRemove) [reference]
            	optionSelect(partidos, 'id_partido', 'partido', "#partido, .partido", "partidosRemove");
            },
            error: function (data) {
            	console.log('error: '+data);
            }
        });   

});




