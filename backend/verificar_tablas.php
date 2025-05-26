<?php
/**
 * Script para verificar y crear las tablas necesarias para el sistema
 */

// Incluir el archivo de conexión a la base de datos
require_once 'config/db.php';

// Definir las tablas necesarias y sus estructuras
$tablas = [
    'datos_envio' => "CREATE TABLE IF NOT EXISTS datos_envio (
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
        INDEX (pedido_codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'pagos' => "CREATE TABLE IF NOT EXISTS pagos (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        pedido_codigo VARCHAR(255) NOT NULL,
        parte_pago INT NOT NULL DEFAULT 1,
        usuario_codigo BIGINT UNSIGNED NOT NULL,
        metodo_pago ENUM('transferencia', 'pago_movil', 'efectivo') NOT NULL,
        banco VARCHAR(100),
        referencia VARCHAR(100),
        monto DECIMAL(10,2) NOT NULL,
        porcentaje DECIMAL(5,2),
        telefono VARCHAR(20),
        fecha_pago DATE NOT NULL,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        estado ENUM('pendiente', 'verificado', 'rechazado') DEFAULT 'pendiente',
        comentarios TEXT,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        INDEX (pedido_codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    'pedidos_finalizados' => "CREATE TABLE IF NOT EXISTS pedidos_finalizados (
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
        INDEX (pedido_codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
];

// Verificar y crear cada tabla
$tablas_creadas = [];
$tablas_existentes = [];
$errores = [];

foreach ($tablas as $nombre_tabla => $sql_crear) {
    // Verificar si la tabla existe
    $sql_check = "SHOW TABLES LIKE '$nombre_tabla'";
    $result = $conn->query($sql_check);

    if ($result && $result->num_rows > 0) {
        $tablas_existentes[] = $nombre_tabla;
    } else {
        // Crear la tabla
        if ($conn->query($sql_crear) === TRUE) {
            $tablas_creadas[] = $nombre_tabla;
        } else {
            $errores[] = "Error al crear la tabla '$nombre_tabla': " . $conn->error;
        }
    }
}

// Mostrar resultados
echo "<h1>Verificación de tablas</h1>";

if (!empty($tablas_existentes)) {
    echo "<h2>Tablas existentes:</h2>";
    echo "<ul>";
    foreach ($tablas_existentes as $tabla) {
        echo "<li>$tabla</li>";
    }
    echo "</ul>";
}

if (!empty($tablas_creadas)) {
    echo "<h2>Tablas creadas:</h2>";
    echo "<ul>";
    foreach ($tablas_creadas as $tabla) {
        echo "<li>$tabla</li>";
    }
    echo "</ul>";
}

if (!empty($errores)) {
    echo "<h2>Errores:</h2>";
    echo "<ul>";
    foreach ($errores as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}

// Cerrar la conexión
$conn->close();
?>
