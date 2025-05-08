// funcion ajax para traer las calles correspondiente a un partido
var localidades;
var calles;

$("#partido, .partido").change(function(){
//console.log('Llegó localidades');
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
                $("#localidad, .localidad").html('<option value="" disabled selected class="bg-secondary">Localidad</option>');
                if(!$("#localidad, .localidad").prev().find("i:eq(0)").hasClass('v-requerido-icon-off')){
                    $("#localidad, .localidad").prev().find("i:eq(0)").removeClass("text-success").addClass("text-danger");
                }


                $("#calle, .calle").html('<option value="" disabled selected class="bg-secondary">Calle</option>');
                if(!$("#calle, .calle").prev().find("i:eq(0)").hasClass('v-requerido-icon-off')){
                    $("#calle, .calle").prev().find("i:eq(0)").removeClass("text-success").addClass("text-danger");
                }
            	
               //optionSelect(objeto, value, leyenda, destino, claseRemove) [reference]
                optionSelect(localidades, 'id_localidad', 'localidad', "#localidad, .localidad", "localidadesRemove");
            },
            error: function (data) {
            	console.log('error: '+data);
            }
        });   

        $.ajax({
            url: '../04-modelo/callesModel.php',
            type: 'POST',
            dataType: 'json',
            //data: $('#inversion').serialize(),
            data: {
               'ajax'         : 'on',   
               'id_partido' : $(this).val()    
            },
            success: function (data) {
                //console.log('success: '+(data));
                calles = Object.values(data);                               
                //optionSelect(objeto, value, leyenda, destino, claseRemove) [reference]
                optionSelect(calles, 'id_calle', 'calle', "#calle, .calle", "callesRemove");
            },
            error: function (data) {
                console.log('error: '+data);
            }
        });  

});




