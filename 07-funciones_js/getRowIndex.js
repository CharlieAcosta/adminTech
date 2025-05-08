function getRowIndex(event) {
    // Verifica si el evento fue disparado dentro de un DataTable
    let tableRow = event.target.closest('tr');

    if (tableRow) {
        // Obtenemos el DataTable asociado al elemento
        let dataTable = $(tableRow).closest('table').DataTable();
        
        // Si el DataTable existe
        if (dataTable) {
            // Obtenemos el número de fila (índice de la fila en el DataTable)
            let rowIndex = dataTable.row(tableRow).index();

            // Retornamos el índice de la fila
            return rowIndex;
        }
    }
    
    // Si no se encuentra el row index
    return null;
}