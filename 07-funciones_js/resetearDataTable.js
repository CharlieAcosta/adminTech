window.resetearDataTable = function() {
    // Limpiar y destruir la instancia del DataTable anterior
    $('#current_table').DataTable().clear().destroy();
    // Restaurar el contenido original del DataTable
    $('#current_table tbody').html(window.initialTableHTML);
    // Inicializar el DataTable nuevamente con la configuración inicial
    $('#current_table').DataTable({
        "dom": '<"dt-top-container"<l><"dt-center-in-div"B><f>r>t<ip>',
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "pageLength": 100,
        "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
        "language": { "url": "//cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json" },
        "columns": [
            { "width": "5%", "type": "date-dd-mm-yyyy" },
            { "width": "7%" },
            { "width": "8%" },
            { "width": "9%" },
            { "width": "5%" },
            { "width": "3%" },
            { "width": "10%" },
            { "width": "5%" },
            { "width": "5%" },
            { "width": "5%" },
            { "width": "5%" },
            { "width": "5%" },
            { "width": "4%" }
        ],
        "order": [
            [0, 'asc'],
            [2, 'asc']
        ]
    }).buttons().container().appendTo('#current_table_wrapper .col-md-6:eq(0)');

        // Restablecer el color de fondo de los filtros
        ['#fecha_filter', '#agente_filter', '#obra_filter'].forEach(function(selector) {
            const element = $(selector);
            if (element.is('select')) {
                element.next('.select2-container').find('.select2-selection').css('background-color', '');
            } else {
                element.css('background-color', '');
            }
        });

}
