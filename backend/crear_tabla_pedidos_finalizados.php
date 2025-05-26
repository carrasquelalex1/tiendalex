<?php
/**
 * Script para crear la tabla de pedidos finalizados
 */

// Incluir el archivo de conexión a la base de datos
require_once 'config/db.php';

// Consulta SQL para crear la tabla de pedidos finalizados
$sql = "CREATE TABLE IF NOT EXISTS pedidos_finalizados (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_codigo VARCHAR(255) NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    producto_id BIGINT UNSIGNED NOT NULL,
    cantidad INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    precio_dolares DECIMAL(10,2) NOT NULL,
    precio_bolivares DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'pago_pendiente', 'pagado', 'enviado', 'entregado', 'cancelado') NOT NULL DEFAULT 'pagado',
    fecha_pedido TIMESTAMP NULL,
    fecha_pago TIMESTAMP NULL,
    fecha_finalizacion TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuario(codigo) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos_tienda(id_producto) ON DELETE CASCADE,
    INDEX (pedido_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

// Ejecutar la consulta
if ($conn->query($sql) === TRUE) {
    echo "Tabla 'pedidos_finalizados' creada correctamente o ya existía.\n";

    // Verificar si la tabla existe
    $sql_check = "SHOW TABLES LIKE 'pedidos_finalizados'";
    $result = $conn->query($sql_check);
    if ($result->num_rows > 0) {
        echo "Confirmado: La tabla 'pedidos_finalizados' existe en la base de datos.\n";
    } else {
        echo "Error: La tabla 'pedidos_finalizados' no se pudo crear correctamente.\n";
    }
} else {
    echo "Error al crear la tabla 'pedidos_finalizados': " . $conn->error . "\n";
}

// Cerrar la conexión
$conn->close();
?>
