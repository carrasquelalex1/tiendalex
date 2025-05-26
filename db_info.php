<?php
require_once __DIR__ . '/autoload.php';

// Información de conexión
$servername = "localhost";
$username = "alex";
$password = "12569655";
$dbname = "tiendalex2";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "Conexión exitosa a la base de datos $dbname<br>";

// Obtener información sobre la tabla productos_tienda
$sql = "SHOW TABLES";
$result = $conn->query($sql);

if ($result) {
    echo "<h3>Tablas en la base de datos:</h3>";
    echo "<ul>";
    while ($row = $result->fetch_row()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "Error al consultar las tablas: " . $conn->error;
}

// Intentar obtener la estructura de la tabla productos_tienda
$sql = "DESCRIBE productos_tienda";
$result = $conn->query($sql);

if ($result) {
    echo "<h3>Estructura de la tabla productos_tienda:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . (isset($row['Default']) ? $row['Default'] : 'NULL') . "</td>";
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
