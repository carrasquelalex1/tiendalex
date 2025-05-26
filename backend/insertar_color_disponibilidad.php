<?php
/**
 * Script para insertar el color de disponibilidad en la tabla de configuración del tema
 */

// Incluir archivo de conexión
require_once __DIR__ . '/config/db.php';

// Verificar si ya existe el color de disponibilidad
$sql = "SELECT * FROM configuracion_tema WHERE nombre_configuracion = 'availability_color'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "El color de disponibilidad ya existe en la tabla de configuración.";
} else {
    // Insertar el color de disponibilidad (amarillo)
    $sql = "INSERT INTO configuracion_tema (nombre_configuracion, valor_configuracion) VALUES ('availability_color', '#f1c40f')";
    if ($conn->query($sql) === TRUE) {
        echo "Color de disponibilidad insertado correctamente.";
    } else {
        echo "Error al insertar el color de disponibilidad: " . $conn->error;
    }
}

// Cerrar la conexión
$conn->close();
?>
