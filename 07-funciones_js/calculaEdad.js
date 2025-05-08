$("#nacimiento, .nacimiento").change(function(){
    calculaEdad(this)
});

function calculaEdad(elementos){
    var fecha = $(elementos).val();
    var hoy = new Date();
    var cumpleanos = new Date(fecha);
    var edad = hoy.getFullYear() - cumpleanos.getFullYear();
    var m = hoy.getMonth() - cumpleanos.getMonth();
    if (m < 0 || (m === 0 && hoy.getDate() < cumpleanos.getDate())) {
        edad--;
    }
    $("#edad").val(edad).html(edad);
    $("#edad").prev().find("i:eq(0)").addClass("text-success");
};