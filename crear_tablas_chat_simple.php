<?php
/**
 * Script simple para crear las tablas de chat
 */

// Conexión directa a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tiendalex2";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "Conexión exitosa a la base de datos.<br>";

// Crear tabla de mensajes de chat
$sql_mensajes = "CREATE TABLE IF NOT EXISTS chat_mensajes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    usuario_id INT(11) NOT NULL,
    admin_id INT(11) DEFAULT NULL,
    mensaje TEXT NOT NULL,
    leido TINYINT(1) DEFAULT 0,
    fecha_envio DATETIME NOT NULL,
    enviado_por ENUM('usuario', 'admin') NOT NULL,
    PRIMARY KEY (id),
    KEY usuario_id (usuario_id),
    KEY admin_id (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

// Crear tabla de sesiones de chat
$sql_sesiones = "CREATE TABLE IF NOT EXISTS chat_sesiones (
    id INT(11) NOT NULL AUTO_INCREMENT,
    usuario_id INT(11) NOT NULL,
    ultimo_mensaje_id INT(11) DEFAULT NULL,
    fecha_ultimo_mensaje DATETIME DEFAULT NULL,
    estado ENUM('activa', 'cerrada') DEFAULT 'activa',
    PRIMARY KEY (id),
    UNIQUE KEY usuario_id (usuario_id),
    KEY ultimo_mensaje_id (ultimo_mensaje_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

// Ejecutar las consultas
if ($conn->query($sql_mensajes) === TRUE) {
    echo "Tabla chat_mensajes creada correctamente<br>";
} else {
    echo "Error al crear la tabla chat_mensajes: " . $conn->error . "<br>";
}

if ($conn->query($sql_sesiones) === TRUE) {
    echo "Tabla chat_sesiones creada correctamente<br>";
} else {
    echo "Error al crear la tabla chat_sesiones: " . $conn->error . "<br>";
}

// Verificar si las tablas se crearon correctamente
$result = $conn->query("SHOW TABLES LIKE 'chat_mensajes'");
if ($result->num_rows > 0) {
    echo "La tabla chat_mensajes existe en la base de datos.<br>";
} else {
    echo "La tabla chat_mensajes NO existe en la base de datos.<br>";
}

$result = $conn->query("SHOW TABLES LIKE 'chat_sesiones'");
if ($result->num_rows > 0) {
    echo "La tabla chat_sesiones existe en la base de datos.<br>";
} else {
    echo "La tabla chat_sesiones NO existe en la base de datos.<br>";
}

// Cerrar conexión
$conn->close();

echo "<br>Proceso completado.";
?>
