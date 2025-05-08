// funcion para saber si es un alta, visualización, edición // formatea la vista del formulario
function abm_detect() {
	if($(".v-id").val()==""){
		// es alta	
		$(".v-alta, .v-alta-edit").removeClass("d-none");
   		$(".v-visual, .v-edit").remove();	  		
   		$(".v-requerido-icon").addClass("text-danger");
   		$("i").removeClass("text-success");
	}else{
		// define si es visualización o edición
		if($(".v-id").data("visualiza")=="on"){
			// es visualización	
			$(".v-visual, .v-visual-edit").removeClass("d-none");
   			$(".v-alta, .v-alta-edit").remove();
   			$("input, select, textarea, .icheck-success").addClass("v-visual-style");
			$(".select2bs4, .v-select2").removeClass("select2bs4");
    		calculaEdad("#nacimiento, .nacimiento");
		}else{
			// es edicion	
			$(".v-alta-edit, .v-edit, .v-visual-edit").removeClass("d-none");
   			$(".v-alta, .v-visual").remove();
    		calculaEdad("#nacimiento, .nacimiento");
    		$('option').removeAttr('disabled');
		}	
	}
}





