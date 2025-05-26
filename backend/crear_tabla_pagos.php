<?php
/**
 * Script para crear la tabla de pagos
 */

// Incluir el archivo de conexión a la base de datos
require_once 'config/db.php';

// Consulta SQL para crear la tabla de pagos
$sql = "CREATE TABLE IF NOT EXISTS pagos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_codigo VARCHAR(255) NOT NULL,
    usuario_codigo BIGINT UNSIGNED NOT NULL,
    metodo_pago ENUM('transferencia', 'pago_movil', 'efectivo') NOT NULL,
    banco VARCHAR(100),
    referencia VARCHAR(100),
    monto DECIMAL(10,2) NOT NULL,
    telefono VARCHAR(20),
    fecha_pago DATE NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'verificado', 'rechazado') DEFAULT 'pendiente',
    comentarios TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (usuario_codigo) REFERENCES usuario(codigo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

// Ejecutar la consulta
if ($conn->query($sql) === TRUE) {
    echo "Tabla 'pagos' creada correctamente o ya existía.";
} else {
    echo "Error al crear la tabla 'pagos': " . $conn->error;
}

// Cerrar la conexión
$conn->close();
?>
