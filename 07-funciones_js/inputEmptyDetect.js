// esta funcion se utiliza para detectar campos vacios o llenos en consecuencia colorear los iconos que acompañan el campo
  
$("input, select, textarea").change(function(){
    inputEmptyDetect(this);
});


function inputEmptyDetect(elementos){
  $(elementos).each(function(index, element){
    // Comprobar si el valor del elemento no está vacío
    if($(element).val() != ""){
      // Si el elemento tiene la clase "v-input-requerido"
      if($(element).hasClass("v-input-requerido")){
        // Encontrar el primer elemento con la clase "v-requerido-icon" en el elemento hermano anterior
        $(element).prev().find(".v-requerido-icon:eq(0)").removeClass("text-danger").addClass("text-success");
      }else{
        // Encontrar el primer elemento <i> en el elemento hermano anterior
        $(element).prev().find("i:eq(0)").removeClass("text-danger").addClass("text-success");

      }
    }else{
      // Si el elemento tiene la clase "v-input-requerido"
      if($(element).hasClass("v-input-requerido")){
        // Encontrar el primer elemento con la clase "v-requerido-icon" en el elemento hermano anterior
        $(element).prev().find(".v-requerido-icon:eq(0)").removeClass("text-success").addClass("text-danger");
      }else{
        // Encontrar el primer elemento <i> en el elemento hermano anterior
        $(element).prev().find("i:eq(0)").removeClass("text-success");
      }
    }
  });
}
