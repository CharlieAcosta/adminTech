$(document).on("click", ".v-icon-accion, .v-accion-cancelar, .v-btn-accion", function () {
  jornalesAcciones($(this));
});

function jornalesAcciones(elemento) {
  switch ($(elemento).data("accion")) {
    case "visual":
      const idVer = $(elemento).closest("tr").data("id");
      window.location.href = "jornales_form.php?acci=v&id=" + idVer;
      break;

    case "editar":
      const idEditar = $(elemento).closest("tr").data("id");
      window.location.href = "jornales_form.php?acci=e&id=" + idEditar;
      break;

    case "delete":
      const idEliminar = $(elemento).closest("tr").data("id");
      const funciones = [
        () => dtableRowDelete("current_table", idEliminar),
        () => simpleUpdateInDB(
          "../06-funciones_php/funciones.php",
          "tipo_jornales",
          {jornal_estado: "eliminado" },
          [{ columna: "jornal_id", condicion: "=", valorCompara: idEliminar }]
        )
      ];

      sAlertDialog(
        "warning",
        "¿Deseas eliminar el resgistro?",
        "",
        "SÍ",
        "success",
        "NO",
        "secondary",
        () => someFunctions(funciones, 1000)
      );
      break;

    case "cancelar":
      Swal.fire({
        icon: "warning",
        title: "Los datos ingresados o modificados no serán registrados",
        text: "¿Deseas cancelar de todas maneras?",
        showDenyButton: true,
        confirmButtonText: "Sí",
        denyButtonText: "No",
        allowOutsideClick: false,
        allowEscapeKey: false
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = "jornales_listado.php";
        }
      });
      break;

    case "guardar":
        if ($('#currentForm').valid())
        {
            const idJornal = $(".v-id").val();
            const esEdicion = idJornal !== "";
            const accion = esEdicion ? "edicion" : "alta";
            const leyenda = esEdicion
              ? "¿Deseas continuar editando este jornal?"
              : "¿Deseas ingresar otro jornal?";
          
            const datosForm = {
              jornal_log_usuario_id: $("#jornal_log_usuario_id").val(),
              jornal_log_accion: $("#jornal_log_accion").val(),
              jornal_id: $("#jornal_id").val(),
              jornal_descripcion: $("#jornal_descripcion").val(),
              jornal_codigo: $("#jornal_codigo").val(),
              jornal_valor: $("#jornal_valor").val(),
              jornal_estado: $("#jornal_estado").val()
            };
                 
            if (esEdicion) {
              simpleUpdateInDB(
              '../06-funciones_php/funciones.php', //urlDestino = 'url del destino que ejecuta' - string
              'tipo_jornales',                     //tabla = 'nombre_de_la_tabla_de_update' - string
              {jornal_descripcion: $("#jornal_descripcion").val(),
              jornal_codigo: $("#jornal_codigo").val(),
              jornal_valor: $("#jornal_valor").val(),
              jornal_estado: $("#jornal_estado").val()
              }, //arraySet = {nombreDelcampo1: 'ValorDelCampo1', nombreDelcampo2: 'ValorDelCampo2'} - jason
              [{columna: "jornal_id", condicion: "=", valorCompara: idJornal}], //arrayWhere = {columna: 'id', condicion: '=', valorCompara: '1'} - jason
              undefined //callType = 'ajax' por default - string
              ).then((response) => { 
                const alertParams = [
                false, // icono
                '<span class="text-white">EDICIÓN EXITOSA</span>', // título
                '<span class="text-white">La información ha sido registrada correctamente</span>', // html
                3700,  // duración
                true,  // barra de progreso
                false, // pie
                false, // clic fuera para cerrar
                true, // permitir cerrar con Escape
                '#28a745' // color de fondo personalizado danger: #dc3545, success: #28a745
              ];
              sAlertAutoCloseV2(alertParams); 
            })
            .catch((error) => {
                const alertParams = [
                false, // icono
                '<span class="text-white">HA OCURRIDO UN ERROR</span>', // título
                '<span class="text-white">intentelo más tarde o comunicate con el administrador</span>', // html
                3700,  // duración
                true,  // barra de progreso
                false, // pie
                false, // clic fuera para cerrar
                true, // permitir cerrar con Escape
                '#dc3545' // color de fondo personalizado danger: #dc3545, success: #28a745
                ];
                sAlertAutoCloseV2(alertParams);                
            });

            } else {
              simpleInsertInDB(
                '../06-funciones_php/funciones.php', // urlDestino = 'url del destino que ejecuta' - string
                'tipo_jornales',  // NOMBRE DE LA TABLA
                ['jornal_descripcion', 'jornal_codigo', 'jornal_valor', 'jornal_estado'], // COLUMNAS
                [$("#jornal_descripcion").val(), $("#jornal_codigo").val(), $("#jornal_valor").val(), $("#jornal_estado").val()] // VALORES
              ).then((response) => { 
                  const alertParams = [
                  false, // icono
                  '<span class="text-white">ALTA EXITOSA</span>', // título
                  '<span class="text-white">La información ha sido registrada correctamente</span>', // html
                  3700,  // duración
                  true,  // barra de progreso
                  false, // pie
                  false, // clic fuera para cerrar
                  true, // permitir cerrar con Escape
                  '#28a745' // color de fondo personalizado danger: #dc3545, success: #28a745
                ];
                sAlertAutoCloseV2(alertParams); 
              })
              .catch((error) => {  
                  const alertParams = [
                  false, // icono
                  '<span class="text-white">HA OCURRIDO UN ERROR</span>', // título
                  '<span class="text-white">intentelo más tarde o comunicate con el administrador</span>', // html
                  3700,  // duración
                  true,  // barra de progreso
                  false, // pie
                  false, // clic fuera para cerrar
                  true, // permitir cerrar con Escape
                  '#dc3545' // color de fondo personalizado danger: #dc3545, success: #28a745
                  ];
                  sAlertAutoCloseV2(alertParams);                
              });
            }
        } 
        else 
        {
          const alertParams = [
            false, // icono
            '<span class="text-white">NO SE PUEDE GUARDAR</span>', // título
            '<span class="text-white">Hay campos sin completar</span>', // html
            3700,  // duración
            true,  // barra de progreso
            false, // pie
            false, // clic fuera para cerrar
            true, // permitir cerrar con Escape
            '#dc3545' // color de fondo personalizado danger: #dc3545, success: #28a745
          ];
          sAlertAutoCloseV2(alertParams);      
        }  
    break;     
    
    case "volver":
      window.location.href = "panel.php";
    break    
  }
}

