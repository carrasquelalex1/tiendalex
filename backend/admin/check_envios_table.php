<?php
/**
 * Script para verificar la estructura de las tablas relacionadas con pedidos
 */

// Incluir la conexión a la base de datos
require_once '../config/db.php';

// Función para mostrar la estructura de una tabla
function mostrar_estructura_tabla($conn, $tabla) {
    echo "<h3>Estructura de la tabla '$tabla':</h3>";

    // Obtener la estructura de la tabla
    $sql_describe = "DESCRIBE $tabla";
    $result_describe = $conn->query($sql_describe);

    if ($result_describe && $result_describe->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";

        while ($row = $result_describe->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "Error al obtener la estructura de la tabla '$tabla'.<br>";
    }
}

// Función para verificar el tipo de una columna
function verificar_tipo_columna($conn, $tabla, $columna) {
    echo "<h3>Tipo de la columna '$columna' en la tabla '$tabla':</h3>";

    $sql_column_type = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE TABLE_SCHEMA = 'tiendalex2'
                        AND TABLE_NAME = '$tabla'
                        AND COLUMN_NAME = '$columna'";

    $result_column_type = $conn->query($sql_column_type);

    if ($result_column_type && $result_column_type->num_rows > 0) {
        $row = $result_column_type->fetch_assoc();
        echo $row['COLUMN_TYPE'] . "<br><br>";
    } else {
        echo "Error al obtener el tipo de la columna '$columna' en la tabla '$tabla'.<br><br>";
    }
}

// Verificar y mostrar información de la tabla 'envios'
$sql_check_table = "SHOW TABLES LIKE 'envios'";
$result_check = $conn->query($sql_check_table);
if ($result_check && $result_check->num_rows > 0) {
    echo "La tabla 'envios' existe.<br>";
    mostrar_estructura_tabla($conn, 'envios');
    verificar_tipo_columna($conn, 'envios', 'estado');
} else {
    echo "La tabla 'envios' no existe.<br><br>";
}

// Verificar y mostrar información de la tabla 'pedidos_finalizados'
$sql_check_table = "SHOW TABLES LIKE 'pedidos_finalizados'";
$result_check = $conn->query($sql_check_table);
if ($result_check && $result_check->num_rows > 0) {
    echo "La tabla 'pedidos_finalizados' existe.<br>";
    mostrar_estructura_tabla($conn, 'pedidos_finalizados');
    verificar_tipo_columna($conn, 'pedidos_finalizados', 'estado');
} else {
    echo "La tabla 'pedidos_finalizados' no existe.<br><br>";
}

// Verificar y mostrar información de la tabla 'pagos'
$sql_check_table = "SHOW TABLES LIKE 'pagos'";
$result_check = $conn->query($sql_check_table);
if ($result_check && $result_check->num_rows > 0) {
    echo "La tabla 'pagos' existe.<br>";
    mostrar_estructura_tabla($conn, 'pagos');
    verificar_tipo_columna($conn, 'pagos', 'estado');
} else {
    echo "La tabla 'pagos' no existe.<br><br>";
}
