<?php
/**
 * Script para crear las tablas necesarias para el sistema de chat
 */

// Incluir la conexión a la base de datos
if (file_exists('../config/db.php')) {
    require_once '../config/db.php';
} else if (file_exists('backend/config/db.php')) {
    require_once 'backend/config/db.php';
} else {
    die("No se pudo encontrar el archivo de conexión a la base de datos.");
}

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

// Verificar si el usuario administrador existe
$sql_check_admin = "SELECT codigo FROM usuario WHERE nombre_usuario = 'alexander' LIMIT 1";
$result_admin = $conn->query($sql_check_admin);

if ($result_admin->num_rows > 0) {
    $admin = $result_admin->fetch_assoc();
    echo "Usuario administrador encontrado con ID: " . $admin['codigo'] . "<br>";
} else {
    echo "ADVERTENCIA: No se encontró el usuario administrador 'alexander'<br>";
}

echo "Configuración de tablas para el chat completada.";
?>
