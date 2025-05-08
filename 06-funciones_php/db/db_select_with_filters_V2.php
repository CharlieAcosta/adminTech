<?php
/**
 * Realiza una consulta a la base de datos utilizando consultas preparadas con filtros especificados,
 * permite ordenar los resultados y seleccionar columnas específicas.
 *
 * @param string $table Nombre de la tabla en la base de datos desde la cual se seleccionan los datos.
 * @param array $columns Array de nombres de columnas para las condiciones WHERE.
 * @param array $comparisons Array de operadores de comparación correspondientes a cada columna (por ejemplo, '=', '>', '<', 'LIKE').
 * @param array $values Array de valores para comparar con cada columna.
 * @param array $orderBy Array de arrays que especifican las columnas y el orden para la cláusula ORDER BY (por ejemplo, [['columna1', 'ASC'], ['columna2', 'DESC']]).
 * @param string $callType Tipo de llamada: 'ajax' para llamadas AJAX que devuelven datos en formato JSON, vacío para llamadas normales que devuelven un array PHP.
 * @param array $selectColumns (Opcional) Array de nombres de columnas que se desean obtener. Si se deja vacío, se seleccionarán todas las columnas.
 *
 * @return array|false Devuelve un array de resultados en formato asociativo si la llamada no es AJAX, o imprime JSON en caso de llamada AJAX. Devuelve false en caso de error.
 *
 * @example
 * // Ejemplo de uso en PHP:
 * $resultados = db_select_with_filters_V2(
 *     'usuarios',                        // Nombre de la tabla
 *     ['edad', 'estado'],                // Columnas para condiciones WHERE
 *     ['>', '='],                        // Operadores de comparación
 *     [25, 'activo'],                    // Valores para comparar
 *     [['edad', 'ASC']],                 // Ordenar por 'edad' ascendente
 *     '',                                // Tipo de llamada (vacío para uso interno en PHP)
 *     ['nombre', 'edad', 'estado']       // Columnas a seleccionar
 * );
 *
 * if ($resultados !== false) {
 *     foreach ($resultados as $fila) {
 *         echo 'Nombre: ' . $fila['nombre'] . ', Edad: ' . $fila['edad'] . ', Estado: ' . $fila['estado'] . '<br>';
 *     }
 * } else {
 *     echo 'Error en la consulta.';
 * }
 */
function db_select_with_filters_V2($table, $columns = [], $comparisons = [], $values = [], $orderBy = [], $callType = '', $selectColumns = []) {
    // Verificar que los arrays $columns, $comparisons y $values tengan la misma cantidad de elementos
    if (count($columns) !== count($comparisons) || count($columns) !== count($values)) {
        $error_message = "Error: Los arrays 'columns', 'comparisons' y 'values' deben tener la misma cantidad de elementos.";
        if ($callType === 'ajax') {
            echo json_encode(['error' => $error_message]);
        } else {
            echo $error_message . PHP_EOL;
        }
        return false;
    }

    // Conectar a la base de datos
    $db = conectaDB();

    // Establecer el conjunto de caracteres a utf8mb4
    $db->set_charset('utf8mb4');

    // Construir la lista de columnas a seleccionar
    $selectClause = '*'; // Por defecto selecciona todas las columnas
    if (!empty($selectColumns)) {
        $escapedColumns = array_map(function($col) use ($db) {
            return "`" . $db->real_escape_string($col) . "`";
        }, $selectColumns);
        $selectClause = implode(', ', $escapedColumns);
    }

    // Construir la consulta SQL básica con placeholders
    $sql = "SELECT $selectClause FROM `" . $db->real_escape_string($table) . "`";
    $conditions = [];
    $param_types = '';  // Cadena para los tipos de parámetros de bind_param
    $param_values = [];  // Array para almacenar los valores de los parámetros

    // Añadir las condiciones a la consulta si hay elementos en los arrays
    if (!empty($columns)) {
        $sql .= " WHERE ";
        for ($i = 0; $i < count($columns); $i++) {
            $column = $db->real_escape_string($columns[$i]);
            $comparison = $comparisons[$i];
            $conditions[] = "`$column` $comparison ?";

            // Determinar el tipo de parámetro y agregar el valor
            if (is_int($values[$i])) {
                $param_types .= 'i';
                $param_values[] = $values[$i];
            } elseif (is_float($values[$i])) {
                $param_types .= 'd';
                $param_values[] = $values[$i];
            } else {
                $param_types .= 's';
                $param_values[] = $values[$i];
            }
        }
        $sql .= implode(' AND ', $conditions);
    }

    // Añadir la cláusula ORDER BY si hay elementos en $orderBy
    if (!empty($orderBy)) {
        $orderConditions = [];
        foreach ($orderBy as $order) {
            $orderColumn = $db->real_escape_string($order[0]);
            $orderDirection = strtoupper($order[1]) === 'DESC' ? 'DESC' : 'ASC';
            $orderConditions[] = "`$orderColumn` $orderDirection";
        }
        $sql .= " ORDER BY " . implode(', ', $orderConditions);
    }

    // Preparar la consulta
    $stmt = $db->prepare($sql);

    // Verificar si la consulta se preparó correctamente
    if (!$stmt) {
        $error_message = "Error en la preparación de la consulta: " . $db->error;
        if ($callType === 'ajax') {
            echo json_encode(['error' => $error_message]);
        } else {
            echo $error_message . PHP_EOL;
        }
        $db->close();
        return false;
    }

    // Vincular los parámetros si es necesario
    if (!empty($columns)) {
        $stmt->bind_param($param_types, ...$param_values);
    }

    // Ejecutar la consulta
    if (!$stmt->execute()) {
        $error_message = "Error en la ejecución de la consulta: " . $stmt->error;
        if ($callType === 'ajax') {
            echo json_encode(['error' => $error_message]);
        } else {
            echo $error_message . PHP_EOL;
        }
        $stmt->close();
        $db->close();
        return false;
    }

    // Obtener los resultados
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    // Cerrar la conexión y la sentencia
    $stmt->close();
    $db->close();

    // Devolver los datos en el formato correcto
    if ($callType === 'ajax') {
        header('Content-Type: application/json');
        echo json_encode($data);
    } else {
        return $data;
    }
}