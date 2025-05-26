<?php
/**
 * Script para verificar y agregar el campo availability_color a la tabla configuracion_tema
 */

// Incluir archivo de conexión
require_once 'backend/config/db.php';

echo "<h2>Verificación del campo availability_color</h2>";

// Verificar si la tabla existe
$sql_check_table = "SHOW TABLES LIKE 'configuracion_tema'";
$result_table = $conn->query($sql_check_table);

if ($result_table->num_rows == 0) {
    echo "<p style='color: red;'>❌ La tabla configuracion_tema no existe. Creándola...</p>";
    
    // Crear la tabla
    $sql_create = "CREATE TABLE configuracion_tema (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_configuracion VARCHAR(50) NOT NULL,
        valor_configuracion VARCHAR(255) NOT NULL,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql_create) === TRUE) {
        echo "<p style='color: green;'>✅ Tabla configuracion_tema creada correctamente.</p>";
    } else {
        echo "<p style='color: red;'>❌ Error al crear la tabla: " . $conn->error . "</p>";
        exit;
    }
} else {
    echo "<p style='color: green;'>✅ La tabla configuracion_tema existe.</p>";
}

// Verificar registros existentes
echo "<h3>Registros actuales en la tabla:</h3>";
$sql_select = "SELECT nombre_configuracion, valor_configuracion FROM configuracion_tema ORDER BY nombre_configuracion";
$result = $conn->query($sql_select);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Nombre Configuración</th><th>Valor</th></tr>";
    
    $availability_exists = false;
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['nombre_configuracion']) . "</td>";
        echo "<td>" . htmlspecialchars($row['valor_configuracion']) . "</td>";
        echo "</tr>";
        
        if ($row['nombre_configuracion'] === 'availability_color') {
            $availability_exists = true;
        }
    }
    echo "</table>";
    
    // Verificar si existe availability_color
    if ($availability_exists) {
        echo "<p style='color: green;'>✅ El campo availability_color ya existe en la base de datos.</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ El campo availability_color NO existe. Agregándolo...</p>";
        
        // Agregar el campo availability_color
        $sql_insert = "INSERT INTO configuracion_tema (nombre_configuracion, valor_configuracion) VALUES ('availability_color', '#f1c40f')";
        
        if ($conn->query($sql_insert) === TRUE) {
            echo "<p style='color: green;'>✅ Campo availability_color agregado correctamente con valor predeterminado #f1c40f</p>";
        } else {
            echo "<p style='color: red;'>❌ Error al agregar availability_color: " . $conn->error . "</p>";
        }
    }
} else {
    echo "<p style='color: orange;'>⚠️ No hay registros en la tabla. Agregando valores predeterminados...</p>";
    
    // Agregar valores predeterminados
    $valores_predeterminados = [
        ['primary_color', '#2c3e50'],
        ['secondary_color', '#34495e'],
        ['accent_color', '#2980b9'],
        ['success_color', '#27ae60'],
        ['danger_color', '#c0392b'],
        ['warning_color', '#f39c12'],
        ['availability_color', '#f1c40f'],
        ['body_bg_color', '#f5f6fa']
    ];
    
    $sql_insert = "INSERT INTO configuracion_tema (nombre_configuracion, valor_configuracion) VALUES (?, ?)";
    $stmt = $conn->prepare($sql_insert);
    
    foreach ($valores_predeterminados as $valor) {
        $stmt->bind_param("ss", $valor[0], $valor[1]);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Agregado: " . $valor[0] . " = " . $valor[1] . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Error al agregar " . $valor[0] . ": " . $stmt->error . "</p>";
        }
    }
}

// Verificar registros finales
echo "<h3>Registros finales en la tabla:</h3>";
$result_final = $conn->query($sql_select);

if ($result_final && $result_final->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Nombre Configuración</th><th>Valor</th></tr>";
    
    while ($row = $result_final->fetch_assoc()) {
        $color = $row['nombre_configuracion'];
        $valor = $row['valor_configuracion'];
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($color) . "</td>";
        echo "<td>";
        echo "<span style='background-color: " . htmlspecialchars($valor) . "; color: white; padding: 2px 8px; border-radius: 3px;'>";
        echo htmlspecialchars($valor);
        echo "</span>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No se pudieron obtener los registros finales.</p>";
}

$conn->close();

echo "<h3>Proceso completado</h3>";
echo "<p><a href='admin_tema.php'>← Volver a Configuración del Tema</a></p>";
?>
