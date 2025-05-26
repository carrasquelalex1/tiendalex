<?php
/**
 * Script para verificar específicamente la columna 'estado' en las tablas relacionadas con pedidos
 */

// Incluir la conexión a la base de datos
require_once '../config/db.php';

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
        return $row['COLUMN_TYPE'];
    } else {
        echo "Error al obtener el tipo de la columna '$columna' en la tabla '$tabla'.<br><br>";
        return null;
    }
}

// Función para verificar los valores actuales en la columna estado
function verificar_valores_actuales($conn, $tabla, $columna) {
    echo "<h3>Valores actuales en la columna '$columna' de la tabla '$tabla':</h3>";
    
    $sql = "SELECT DISTINCT $columna, COUNT(*) as cantidad FROM $tabla GROUP BY $columna";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Valor</th><th>Cantidad</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . ($row[$columna] ? htmlspecialchars($row[$columna]) : '<em>NULL</em>') . "</td>";
            echo "<td>" . $row['cantidad'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table><br><br>";
    } else {
        echo "No se encontraron valores o error al consultar.<br><br>";
    }
}

// Verificar la columna 'estado' en la tabla 'pedidos_finalizados'
echo "<h2>Verificación de la columna 'estado' en la tabla 'pedidos_finalizados'</h2>";
$tipo_pedidos_finalizados = verificar_tipo_columna($conn, 'pedidos_finalizados', 'estado');
verificar_valores_actuales($conn, 'pedidos_finalizados', 'estado');

// Verificar la columna 'estado' en la tabla 'pagos'
echo "<h2>Verificación de la columna 'estado' en la tabla 'pagos'</h2>";
$tipo_pagos = verificar_tipo_columna($conn, 'pagos', 'estado');
verificar_valores_actuales($conn, 'pagos', 'estado');

// Verificar la columna 'estado' en la tabla 'envios'
echo "<h2>Verificación de la columna 'estado' en la tabla 'envios'</h2>";
$tipo_envios = verificar_tipo_columna($conn, 'envios', 'estado');
verificar_valores_actuales($conn, 'envios', 'estado');

// Mostrar un resumen de los tipos de columna
echo "<h2>Resumen de tipos de columna 'estado':</h2>";
echo "<ul>";
echo "<li>pedidos_finalizados.estado: " . ($tipo_pedidos_finalizados ?: 'No disponible') . "</li>";
echo "<li>pagos.estado: " . ($tipo_pagos ?: 'No disponible') . "</li>";
echo "<li>envios.estado: " . ($tipo_envios ?: 'No disponible') . "</li>";
echo "</ul>";

// Verificar si hay alguna restricción en la columna 'estado' de cada tabla
echo "<h2>Restricciones en la columna 'estado':</h2>";

$tablas = ['pedidos_finalizados', 'pagos', 'envios'];

foreach ($tablas as $tabla) {
    echo "<h3>Restricciones en la tabla '$tabla':</h3>";
    
    $sql = "SHOW CREATE TABLE $tabla";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (isset($row['Create Table'])) {
            echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre>";
        } else {
            echo "No se pudo obtener la definición de la tabla.<br>";
        }
    } else {
        echo "Error al consultar la definición de la tabla.<br>";
    }
    
    echo "<br>";
}
