<?php
/**
 * Script para crear la tabla historial_pagos si no existe
 */

// Incluir la conexión a la base de datos
require_once 'config/db.php';

// Verificar si la tabla ya existe
$sql_check = "SHOW TABLES LIKE 'historial_pagos'";
$result = $conn->query($sql_check);

if ($result->num_rows == 0) {
    // La tabla no existe, crearla
    $sql_create = "CREATE TABLE historial_pagos (
        id INT(11) NOT NULL AUTO_INCREMENT,
        pedido_codigo VARCHAR(20) NOT NULL,
        usuario_codigo INT(11) NOT NULL,
        metodo_pago VARCHAR(50) NOT NULL,
        banco VARCHAR(100) DEFAULT NULL,
        referencia VARCHAR(100) DEFAULT NULL,
        monto DECIMAL(10,2) NOT NULL,
        fecha_pago DATE NOT NULL,
        telefono VARCHAR(20) DEFAULT NULL,
        comentarios TEXT DEFAULT NULL,
        estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
        comentarios_admin TEXT DEFAULT NULL,
        fecha_registro DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_pedido_codigo (pedido_codigo),
        KEY idx_usuario_codigo (usuario_codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql_create) === TRUE) {
        echo "Tabla historial_pagos creada correctamente.";
    } else {
        echo "Error al crear la tabla historial_pagos: " . $conn->error;
    }
} else {
    echo "La tabla historial_pagos ya existe.";
}

// Cerrar la conexión
$conn->close();
