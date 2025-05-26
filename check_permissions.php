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

echo "<h2>Verificación de permisos para el usuario '$username' en la base de datos '$dbname'</h2>";

// Verificar si podemos crear una tabla (prueba de permisos)
$test_table = "test_permissions_" . time();
$sql = "CREATE TABLE $test_table (id INT)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✓ El usuario tiene permisos para crear tablas.</p>";
    
    // Eliminar la tabla de prueba
    $sql = "DROP TABLE $test_table";
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>✓ El usuario tiene permisos para eliminar tablas.</p>";
    } else {
        echo "<p style='color:red;'>✗ El usuario no tiene permisos para eliminar tablas: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:red;'>✗ El usuario no tiene permisos para crear tablas: " . $conn->error . "</p>";
}

// Verificar si podemos insertar datos en la tabla productos_tienda
$sql = "SHOW TABLES LIKE 'productos_tienda'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<p style='color:green;'>✓ La tabla 'productos_tienda' existe.</p>";
    
    // Intentar insertar un registro de prueba
    $test_name = "Producto de prueba " . time();
    $sql = "INSERT INTO productos_tienda (nombre_producto) VALUES ('$test_name')";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>✓ El usuario tiene permisos para insertar datos en la tabla 'productos_tienda'.</p>";
        $last_id = $conn->insert_id;
        
        // Eliminar el registro de prueba
        $sql = "DELETE FROM productos_tienda WHERE id_producto = $last_id";
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color:green;'>✓ El usuario tiene permisos para eliminar datos de la tabla 'productos_tienda'.</p>";
        } else {
            echo "<p style='color:red;'>✗ El usuario no tiene permisos para eliminar datos de la tabla 'productos_tienda': " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:red;'>✗ El usuario no tiene permisos para insertar datos en la tabla 'productos_tienda': " . $conn->error . "</p>";
    }
    
    // Mostrar la estructura de la tabla
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
        echo "<p style='color:red;'>✗ Error al consultar la estructura de la tabla: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:red;'>✗ La tabla 'productos_tienda' no existe.</p>";
}

// Cerrar la conexión
$conn->close();
?>
