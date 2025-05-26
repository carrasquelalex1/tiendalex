<?php
/**
 * Script para crear la tabla de datos de envío
 */

// Incluir el archivo de conexión a la base de datos
require_once 'config/db.php';

// Consulta SQL para crear la tabla de datos de envío
$sql = "CREATE TABLE IF NOT EXISTS datos_envio (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_codigo VARCHAR(255) NOT NULL,
    usuario_codigo BIGINT UNSIGNED NOT NULL,
    direccion TEXT NOT NULL,
    empresa_envio VARCHAR(255),
    destinatario_nombre VARCHAR(255) NOT NULL,
    destinatario_telefono VARCHAR(20) NOT NULL,
    destinatario_cedula VARCHAR(20) NOT NULL,
    instrucciones_adicionales TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (usuario_codigo) REFERENCES usuario(codigo) ON DELETE CASCADE,
    INDEX (pedido_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

// Ejecutar la consulta
if ($conn->query($sql) === TRUE) {
    echo "Tabla 'datos_envio' creada correctamente o ya existía.\n";
    
    // Verificar si la tabla existe
    $sql_check = "SHOW TABLES LIKE 'datos_envio'";
    $result = $conn->query($sql_check);
    if ($result->num_rows > 0) {
        echo "Confirmado: La tabla 'datos_envio' existe en la base de datos.\n";
    } else {
        echo "Error: La tabla 'datos_envio' no se pudo crear correctamente.\n";
    }
} else {
    echo "Error al crear la tabla 'datos_envio': " . $conn->error . "\n";
}

// Cerrar la conexión
$conn->close();
?>
