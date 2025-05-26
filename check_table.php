<?php
require_once __DIR__ . '/autoload.php';

// Incluir el archivo de conexión a la base de datos
require_once 'backend/config/db.php';

// Consulta para obtener la estructura de la tabla
$sql = "DESCRIBE productos_tienda";
$result = $conn->query($sql);

if ($result) {
    echo "<h2>Estructura de la tabla productos_tienda:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
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
    echo "Error al consultar la estructura de la tabla: " . $conn->error;
}

// Cerrar la conexión
$conn->close();
?>
