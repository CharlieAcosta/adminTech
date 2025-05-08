$(document).on("click",".v-icon-accion, .v-accion-cancelar, .v-accion-eliminar, .v-accion-ver-todos",function(){
    usuariosAcciones($(this));
});


function usuariosAcciones(elemento){
      switch($(elemento).data('accion')) {
      case 'visual': // ver el agente por pantalla
        var id = $(elemento).closest('tr').data('id');
        window.location.href='agente_form.php?acci=v&id='+id;
        break;

      case 'editar': // editar el agente
        var id = $(elemento).closest('tr').data('id');
        window.location.href='agente_form.php?acci=e&id='+id;
        break;

      case 'pdf': // generar pdf del usuario
        var id = $(elemento).closest('tr').data('id');

            $.ajax({
                url: '../04-modelo/usuariosModel.php',
                type: 'POST',
                dataType: 'json',
                //data: $('#inversion').serialize(),
                data: {
                   'id'      : id,
                   'via'     : 'ajax',   
                   'funcion' : 'modGetUsuariosById',      
                },
                success: function (data) {
                    //console.log('success: '+(data));
                    agentePdf = Object.values(data);                               

                    if(agentePdf['0']['doc_afip'] != ""){agentePdf['0']['doc_afip']=': Si'}else{agentePdf['0']['doc_afip']=': No'}
                    if(agentePdf['0']['doc_apto_medico'] != ""){agentePdf['0']['doc_apto_medico']=': Si'}else{agentePdf['0']['doc_apto_medico']=': No'}
                    if(agentePdf['0']['doc_cuenta_banco'] != ""){agentePdf['0']['doc_cuenta_banco']=': Si'}else{agentePdf['0']['doc_cuenta_banco']=': No'}
                    if(agentePdf['0']['doc_dni'] != ""){agentePdf['0']['doc_dni']=': Si'}else{agentePdf['0']['doc_dni']=': No'}
                    if(agentePdf['0']['doc_licencia_conducir'] != ""){agentePdf['0']['doc_licencia_conducir']=': Si'}else{agentePdf['0']['doc_licencia_conducir']=': No'} 
                    if(agentePdf['0']['doc_titulos'] != ""){agentePdf['0']['doc_titulos']=': Si'}else{agentePdf['0']['doc_titulos']=': No'}                        
                    if(agentePdf['0']['doc_vacunas'] != ""){agentePdf['0']['doc_vacunas']=': Si'}else{agentePdf['0']['doc_vacunas']=': No'}
                    if(agentePdf['0']['doc_recibos'] != ""){agentePdf['0']['doc_recibos']=': Si'}else{agentePdf['0']['doc_recibos']=': No'}   
                    if(agentePdf['0']['doc_cuil'] != ""){agentePdf['0']['doc_cuil']=': Si'}else{agentePdf['0']['doc_cuil']=': No'}                                      
                    if(agentePdf['0']['doc_foto'] != ""){agentePdf['0']['doc_foto']=': Si'}else{agentePdf['0']['doc_foto']=': No'} 






var dd = {
    content: [
        {
            style: 'tableExample',
            table: {
                widths: ['85%', '15%'],
                headerRows: 2,
                body: [
                    [{border: [true, true, false, false], text: 'Agente'}, {border: [false, true, true, false], text: 'Legajo', alignment: 'left'}],
                    [{border: [true, false, false, true], text: agentePdf['0']['apellidos']+' '+agentePdf['0']['nombres'], style: 'header'}, {border: [false, false, true, true], text: agentePdf['0']['legajo'], style: 'header', alignment: 'left'}],
                ]
            }
        },

        {
            style: 'tableExample',
            table: {
                widths: ['25%', '75%'],
                headerRows: 1,
                body: [
                    [{text: 'Datos personales', style: 'tableHeader'}, {}],
                    [agentePdf['0']['tipo_documento']+':', agentePdf['0']['nro_documento']],
                    ['CUIL:', agentePdf['0']['cuil']],
                    ['Nacionalidad:', agentePdf['0']['nacionalidad']],
                    ['Fecha de nacimiento:', agentePdf['0']['nacimiento']],
                    ['Edad:', 'en desarrollo'],
                    ['Celular:', agentePdf['0']['estado_civil']],
                    ['Celular:', agentePdf['0']['celular']],

                ]
            },
            layout: 'lightHorizontalLines'
        },
            {
            style: 'tableExample',
            table: {
                widths: ['25%', '75%'],
                headerRows: 1,
                body: [
                    [{text: 'Domicilio', style: 'tableHeader'}, {}],
                    ['Provincia:', agentePdf['0']['provincianom']],
                    ['Partido:', agentePdf['0']['partidonom']],
                    ['Localidad:', agentePdf['0']['localidadnom']],
                    ['Calle:', agentePdf['0']['callenom']],
                    ['Altura:', agentePdf['0']['altura']],
                    ['Piso:', agentePdf['0']['piso']],
                    ['Depto:', agentePdf['0']['depto']],
                    ['CP:', agentePdf['0']['cp']],
                    ['Teléfono:', agentePdf['0']['telefono']],
                ]
            },
            layout: 'lightHorizontalLines'
        },
            {
            style: 'tableExample',
            table: {
                widths: ['25%', '75%'],
                headerRows: 1,
                body: [
                    [{text: 'Datos del sistema', style: 'tableHeader'}, {}],
                    ['Estado:', agentePdf['0']['estado']],
                    ['Ingreso:', agentePdf['0']['ingreso']],
                    ['Egreso:', agentePdf['0']['egreso']],
                    ['Email:', agentePdf['0']['email']],
                    ['Perfil:', agentePdf['0']['perfil']],
                ]
            },
            layout: 'lightHorizontalLines'
        },
            {
            style: 'tableExample',
            table: {
                widths: ['50%', '50%'],
                headerRows: 1,
                body: [
                    [{text: 'Documentación', style: 'tableHeader'}, {}],
                    ['DNI'+agentePdf['0']['doc_dni'], 'Apto médico'+agentePdf['0']['doc_apto_medico']],
                    ['CUIL'+agentePdf['0']['doc_cuil'], 'Título(s)'+agentePdf['0']['doc_titulos']],
                    ['AFIP(Alta/baja)'+agentePdf['0']['doc_afip'], 'Vacunas'+agentePdf['0']['doc_vacunas']],
                    ['Foto 4x4'+agentePdf['0']['doc_foto'], 'Cuenta banco'+agentePdf['0']['doc_cuenta_banco']],
                    ['Recibos'+agentePdf['0']['doc_recibos'], 'Licencia conducir'+agentePdf['0']['doc_licencia_conducir']]
                ]
            },
            layout: 'lightHorizontalLines'
        },
    ],
    styles: {
        header: {
            fontSize: 18,
            bold: true,
            margin: [0, 0, 0, 10]
        },
        subheader: {
            fontSize: 16,
            bold: true,
            margin: [0, 10, 0, 5]
        },
        tableExample: {
            margin: [0, 5, 0, 15]
        },
        tableHeader: {
            bold: true,
            fontSize: 13,
            color: 'black'
        }
    },
    defaultStyle: {
        // alignment: 'justify'
    }
                        
                    }
                    pdfMake.createPdf(dd).open();
                },
                error: function (data) {
                    console.log('error: '+Object.values(data));
                }
            });


        break;

      case 'cancelar':
        Swal.fire({
            icon: 'warning',
            title: 'Los datos ingresados o modificados no seran registrados',
            text: "¿Deseas cancelar de todas maneras?",
            showDenyButton: true,
            confirmButtonText: 'Si',
            denyButtonText: 'No',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                window.location.href='listado_personal.php';
            } else if (result.isDenied) {

            }
        })  
        break;

      case 'eliminar': // editar el agente
        $('#estado').html('<option selected value="Eliminado">Eliminado</option>');
        $.ajax({
            url: '../04-modelo/usuariosGuardarModel.php',
            type: 'POST',
            dataType: 'json',
            data: $('#currentForm').serialize()+"&ajax=on&accion=eliminar",
            // data: {
            //    'ajax'    : 'on', 
            //    'accion' : "alta",
            // },
            success: function (data) {
                //console.log('success: '+(data));

                Swal.fire({
                    icon: 'success',
                    title: 'El agente se elimino correctamente',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                   }).then((result) => {
                    /* Read more about isConfirmed, isDenied below */
                    if (result.isConfirmed) {
                         window.location.href='listado_personal.php';
                    }
                })


            },
            error: function (data) {
                Swal.fire({
                    icon: 'error',
                    title: 'Algó ha salido mal',
                    text: "Intentalo más tarde o comunicate con el administrador",
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                   }).then((result) => {
                    /* Read more about isConfirmed, isDenied below */
                    if (result.isConfirmed) {
                         window.location.href='listado_personal.php';
                    }
                })
            }
        });   
        break;

      case 'vertodos':
        $('.v-accion-ver-todos').html('<i class="fa fa-eye"></i> Activos').data('accion', 'veractivos');
        $.ajax({
            url: '../03-controller/usuariosController.php',
            type: 'POST',
            dataType: 'json',
            //data: $('#currentForm').serialize()+"&ajax=on&accion=eliminar",
              data: {
                    'ajax'    : 'on', 
                    'funcion' : 'poblarDatableAll',
                    'tds'     : new Array('id_usuario', 'estado', 'apellidos', 'nombres', 'tipo_documento', 'nro_documento', 'cuil', 'nacimiento', 'ingreso'), 
              },
            success: function (data) {
               //console.log('success: '+(data));
               $('#current_table').DataTable().destroy();
               $('#current_table tbody').html(data);
                  $(function () {
                    $("#current_table").DataTable({
                      "dom": '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
                      "responsive": true, "lengthChange": true, "autoWidth": false,
                      "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
                      "language": {"url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"},
                      "columns": [{ "width": "3%" }, { "width": "5%" }, null, null, { "width": "10%" }, { "width": "10%" }, { "width": "10%" }, { "width": "11%" }, { "width": "10%" }, { "width": "10%" }]
                    }).buttons().container().appendTo('#current_table_wrapper .col-md-6:eq(0)');
                  });
            },
            error: function (data) {
                Swal.fire({
                    icon: 'error',
                    title: 'Algó ha salido mal',
                    text: "Intentalo más tarde o comunicate con el administrador",
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                   }).then((result) => {
                    /* Read more about isConfirmed, isDenied below */
                    if (result.isConfirmed) {
                         window.location.href='listado_personal.php';
                    }
                })
            }
        });   
        break;

      case 'veractivos':
        $('.v-accion-ver-todos').html('<i class="fa fa-eye"></i> Todos').data('accion', 'vertodos');
        $.ajax({
            url: '../03-controller/usuariosController.php',
            type: 'POST',
            dataType: 'json',
            //data: $('#currentForm').serialize()+"&ajax=on&accion=eliminar",
              data: {
                    'ajax'    : 'on', 
                    'funcion' : 'poblarDatableActivos',
                    'tds'     : new Array('id_usuario', 'estado', 'apellidos', 'nombres', 'tipo_documento', 'nro_documento', 'cuil', 'nacimiento', 'ingreso'), 
              },
            success: function (data) {
               //console.log('success: '+(data));
               $('#current_table').DataTable().destroy();
               $('#current_table tbody').html(data);
                  $(function () {
                    $("#current_table").DataTable({
                      "dom": '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
                      "responsive": true, "lengthChange": true, "autoWidth": false,
                      "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
                      "language": {"url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"},
                      "columns": [{ "width": "3%" }, { "width": "5%" }, null, null, { "width": "10%" }, { "width": "10%" }, { "width": "10%" }, { "width": "11%" }, { "width": "10%" }, { "width": "10%" }]
                    }).buttons().container().appendTo('#current_table_wrapper .col-md-6:eq(0)');
                  });
            },
            error: function (data) {
                Swal.fire({
                    icon: 'error',
                    title: 'Algó ha salido mal',
                    text: "Intentalo más tarde o comunicate con el administrador",
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                   }).then((result) => {
                    /* Read more about isConfirmed, isDenied below */
                    if (result.isConfirmed) {
                         window.location.href='listado_personal.php';
                    }
                })
            }
        });
        break;

      case 'passOnOff':
        $('.v-ver-pass').toggleClass("fa-eye-slash");            
        $('.v-ver-pass').toggleClass("fa-eye");  
        
        if($('.v-ver-pass').data('estado')!='on'){
            $('.v-ver-pass').data('estado','on');
            $("#password").attr('type','text');
        }else{
            $('.v-ver-pass').data('estado','off');
            $("#password").attr('type','password');
        }

        break;

      case 'guardar':
        alert("guardar");
        break;
    }
}




