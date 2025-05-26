<?php
/**
 * Script para crear la tabla de configuración de temas
 * Esta tabla almacenará los colores personalizados para el sitio
 */

// Habilitar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Buscando archivo de conexión...<br>";

// Incluir archivo de conexión
$db_path1 = __DIR__ . '/config/db.php';
$db_path2 = __DIR__ . '/../config/db.php';

echo "Ruta 1: $db_path1 - Existe: " . (file_exists($db_path1) ? 'Sí' : 'No') . "<br>";
echo "Ruta 2: $db_path2 - Existe: " . (file_exists($db_path2) ? 'Sí' : 'No') . "<br>";

if (file_exists($db_path1)) {
    echo "Usando ruta 1<br>";
    require_once $db_path1;
} else if (file_exists($db_path2)) {
    echo "Usando ruta 2<br>";
    require_once $db_path2;
} else {
    die("No se pudo encontrar el archivo de conexión a la base de datos.");
}

// Crear la tabla si no existe
$sql = "CREATE TABLE IF NOT EXISTS configuracion_tema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_configuracion VARCHAR(50) NOT NULL,
    valor_configuracion VARCHAR(255) NOT NULL,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabla configuracion_tema creada correctamente.<br>";

    // Verificar si ya existen registros
    $check = "SELECT COUNT(*) as count FROM configuracion_tema";
    $result = $conn->query($check);
    $row = $result->fetch_assoc();

    // Si no hay registros, insertar valores predeterminados
    if ($row['count'] == 0) {
        $valores_predeterminados = [
            ['primary_color', '#2c3e50'],
            ['secondary_color', '#34495e'],
            ['accent_color', '#2980b9'],
            ['success_color', '#27ae60'],
            ['danger_color', '#c0392b'],
            ['warning_color', '#f39c12']
        ];

        $insert_sql = "INSERT INTO configuracion_tema (nombre_configuracion, valor_configuracion) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_sql);

        foreach ($valores_predeterminados as $valor) {
            $stmt->bind_param("ss", $valor[0], $valor[1]);
            $stmt->execute();
        }

        echo "Valores predeterminados insertados correctamente.<br>";
    } else {
        echo "Los valores ya existen en la tabla.<br>";
    }
} else {
    echo "Error al crear la tabla: " . $conn->error . "<br>";
}

$conn->close();

echo "Proceso completado.";
?>
