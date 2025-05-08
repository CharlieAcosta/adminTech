$(document).on("click",".v-icon-accion, .v-accion-cancelar, .v-accion-eliminar, .v-accion-ver-todos",function(){
    obrasAcciones($(this));
    //alert('materialesAcciones.js'); [DEBUG PERMANENTE]
});


function obrasAcciones(elemento){
      switch($(elemento).data('accion')) {
      case 'visual': // ver obra por pantalla
        var id = $(elemento).closest('tr').data('id');
        window.location.href='obras_form.php?acci=v&id='+id;
        break;

      case 'editar': // editar obra
        var id = $(elemento).closest('tr').data('id');
        window.location.href='obras_form.php?acci=e&id='+id;
        break;

      case 'pdf': // generar pdf del usuario
        var id = $(elemento).closest('tr').data('id');

            $.ajax({
                url: '../04-modelo/presupuestosModel.php',
                type: 'POST',
                dataType: 'json',
                //data: $('#inversion').serialize(), [example]
                data: {
                   'id'      : id,
                   'via'     : 'ajax',   
                   'funcion' : 'modGetClientesById',      
                },
                success: function (data) {
//console.log('success: '+(data));
                    agentePdf = Object.values(data);                               

var dd = {
    content: [
        {
            style: 'tableExample',
            table: {
                widths: ['85%', '15%'],
                headerRows: 2,
                body: [
                    [{border: [true, true, false, false], text: 'Razón Social'}, {border: [false, true, true, false], text: 'Nro. cliente', alignment: 'center'}],
                    [{border: [true, false, false, true], text: agentePdf['0']['razon_social'], style: 'header'}, {border: [false, false, true, true], text: agentePdf['0']['id_cliente'], style: 'header', alignment: 'center'}],
                ]
            }
        },

        {
            style: 'tableExample',
            table: {
                widths: ['35%', '65%'],
                headerRows: 1,
                body: [
                    [{text: 'Datos de la empresa', style: 'tableHeader'}, {}],
                    ['CUIT:', agentePdf['0']['cuit']],
                    ['Email:', agentePdf['0']['email']],
                    ['Estado:', agentePdf['0']['estado']],
                ]
            },
            layout: 'lightHorizontalLines'
        },
        {
            style: 'tableExample',
            table: {
                widths: ['35%', '65%'],
                headerRows: 1,
                body: [
                    [{text: 'Domicilio fiscal', style: 'tableHeader'}, {}],
                    ['Provincia:', agentePdf['0']['provincianom']],
                    ['Partido:', agentePdf['0']['partidonom']],
                    ['Localidad:', agentePdf['0']['localidadnom']],
                    ['Calle:', agentePdf['0']['callenom']],
                    ['Altura:', agentePdf['0']['dirfis_altura']],
                    ['Piso:', agentePdf['0']['dirfis_piso']],
                    ['Depto:', agentePdf['0']['dirfis_depto']],
                    ['CP:', agentePdf['0']['dirfis_cp']],
                    ['Teléfono:', agentePdf['0']['telefono']],
                ]
            },
            layout: 'lightHorizontalLines'
        },
        {
            style: 'tableExample',
            table: {
                widths: ['35%', '65%'],
                headerRows: 1,
                body: [
                    [{text: 'Contacto principal', style: 'tableHeader'}, {}],
                    ['Apellido y Nombre(s):', agentePdf['0']['contacto_pri']],
                    ['Celular:', agentePdf['0']['contacto_pri_celular']],
                    ['Email:', agentePdf['0']['contacto_pri_email']],
                ]
            },
            layout: 'lightHorizontalLines'
        },
        {
            style: 'tableExample',
            table: {
                widths: ['35%', '65%'],
                headerRows: 1,
                body: [
                    [{text: 'Contacto pago a proveedores', style: 'tableHeader'}, {}],
                    ['Apellido y Nombre(s):', agentePdf['0']['contacto_papro']],
                    ['Celular:', agentePdf['0']['contacto_papro_celular']],
                    ['Email:', agentePdf['0']['contacto_papro_email']],
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
                    [{text: 'Plataformas', style: 'tableHeader'}, {}],
                    ['Plataforma de licitaciones:', agentePdf['0']['plat_licitacion']],
                    ['Plataforma de pagos:', agentePdf['0']['plat_pagos']],
                    ['Plataforma de documentación:', agentePdf['0']['plat_documentacion']]
                ]
            },
            layout: 'lightHorizontalLines'
        }
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
                window.location.href='obras_listado.php';
            } else if (result.isDenied) {

            }
        })  
       break;

      case 'delete': // editar el cliente
        var id = $(elemento).closest('tr').data('id');
        const functionsArray = [
            () => dtableRowDelete("current_table", id),
            () => simpleUpdateInDB('../06-funciones_php/funciones.php', 'obras', {obra_log_accion: 'delete'}, {columna: 'obra_id', condicion: '=', valorCompara: id})
        ];

        sAlertDialog(
           'question',                 
           '¿QUIERES ELIMINAR ESTA OBRA?',  
           '', 
           ' SI ',            
           'success',              
           ' NO ',             
           'danger',                 
            () => someFunctions(functionsArray, 1000),  
           undefined,   
           undefined,              
           undefined,              
           undefined,              
           undefined               
        );       

      break;

      case 'vertodos':
        $('.v-accion-ver-todos i').removeClass("fa-eye").addClass("fa-eye-slash");
        $('.v-accion-ver-todos').removeClass("btn-success").addClass("btn-warning");
        elemento.removeClass("btn-warning").addClass('btn-success').find('i').removeClass("fa-eye-slash").addClass('fa-eye');

        $.ajax({
            url: '../03-controller/presupuestosController.php',
            type: 'POST',
            dataType: 'json',
            //data: $('#currentForm').serialize()+"&ajax=on&accion=eliminar",
              data: {
                    'ajax'    : 'on', 
                    'funcion' : 'poblarDatableAll',
                    'tds'     : new Array('id_cliente', 'log_alta','estado', 'razon_social', 'cuit', 'telefono', 'email', 'contacto_pri', 'contacto_pri_celular', 'contacto_pri_email'), 
                    'filtro'  : elemento.data('filtro')
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
                      "columns": [{ "width": "1%" }, { "width": "6%" }, { "width": "5%" }, null, { "width": "8%" }, { "width": "10%" }, { "width": "10%" }, { "width": "10%" }, { "width": "11%" }, { "width": "10%" }, { "width": "6%" }]
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
                         window.location.href='obras_personal.php';
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




