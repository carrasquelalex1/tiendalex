<?php
/**
 * Script para actualizar las tablas existentes y crear nuevas tablas
 */

// Incluir el archivo de conexión a la base de datos
require_once 'config/db.php';

// Crear tabla de bancos
$sql_bancos = "CREATE TABLE IF NOT EXISTS bancos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

// Crear tabla de información de pago (cuentas bancarias, etc.)
$sql_info_pago = "CREATE TABLE IF NOT EXISTS info_pago (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('banco', 'pago_movil', 'efectivo', 'otro') NOT NULL,
    banco_id BIGINT UNSIGNED NULL,
    numero_cuenta VARCHAR(30) NULL,
    titular VARCHAR(100) NULL,
    cedula_rif VARCHAR(20) NULL,
    telefono VARCHAR(20) NULL,
    descripcion TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (banco_id) REFERENCES bancos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

// Crear tabla de datos de envío
$sql_datos_envio = "CREATE TABLE IF NOT EXISTS datos_envio (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_codigo VARCHAR(255) NOT NULL,
    usuario_codigo BIGINT UNSIGNED NOT NULL,
    direccion TEXT NOT NULL,
    empresa_envio VARCHAR(100) NULL,
    destinatario_nombre VARCHAR(100) NOT NULL,
    destinatario_telefono VARCHAR(20) NOT NULL,
    destinatario_cedula VARCHAR(20) NOT NULL,
    instrucciones_adicionales TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (usuario_codigo) REFERENCES usuario(codigo) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

// Modificar la tabla pagos para soportar múltiples métodos de pago por pedido
$sql_modificar_pagos = "ALTER TABLE pagos 
    ADD COLUMN parte_pago INT NOT NULL DEFAULT 1 AFTER pedido_codigo,
    ADD COLUMN porcentaje DECIMAL(5,2) NULL AFTER monto;";

// Ejecutar las consultas
$queries = [
    'Crear tabla de bancos' => $sql_bancos,
    'Crear tabla de información de pago' => $sql_info_pago,
    'Crear tabla de datos de envío' => $sql_datos_envio
];

// Verificar si la columna parte_pago ya existe en la tabla pagos
$check_column = "SHOW COLUMNS FROM pagos LIKE 'parte_pago'";
$result = $conn->query($check_column);
if ($result->num_rows == 0) {
    $queries['Modificar tabla pagos'] = $sql_modificar_pagos;
}

// Ejecutar las consultas
foreach ($queries as $description => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo $description . ": Operación completada correctamente.<br>";
    } else {
        echo $description . ": Error - " . $conn->error . "<br>";
    }
}

// Insertar bancos predeterminados si la tabla está vacía
$check_bancos = "SELECT COUNT(*) as count FROM bancos";
$result = $conn->query($check_bancos);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    $bancos_default = [
        'Banco de Venezuela',
        'Banco Provincial (BBVA)',
        'Banesco',
        'Banco Mercantil',
        'Banco Nacional de Crédito (BNC)',
        'Banco Exterior',
        'Banco Occidental de Descuento (BOD)',
        'Banco Venezolano de Crédito',
        'Banco Bicentenario',
        'Banco del Tesoro',
        'Bancaribe',
        'Banco Fondo Común (BFC)',
        'Banco Plaza',
        'Banco Caroní',
        '100% Banco',
        'Bancamiga',
        'Banplus',
        'Banco Activo',
        'Banco Agrícola de Venezuela',
        'Banco de la Fuerza Armada Nacional Bolivariana (BANFANB)'
    ];
    
    $sql_insert_bancos = "INSERT INTO bancos (nombre, activo, created_at, updated_at) VALUES (?, 1, NOW(), NOW())";
    $stmt = $conn->prepare($sql_insert_bancos);
    
    foreach ($bancos_default as $banco) {
        $stmt->bind_param("s", $banco);
        $stmt->execute();
    }
    
    echo "Bancos predeterminados insertados correctamente.<br>";
}

// Insertar información de pago predeterminada si la tabla está vacía
$check_info_pago = "SELECT COUNT(*) as count FROM info_pago";
$result = $conn->query($check_info_pago);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Obtener ID de Banesco
    $sql_get_banesco = "SELECT id FROM bancos WHERE nombre LIKE '%Banesco%' LIMIT 1";
    $result = $conn->query($sql_get_banesco);
    $banesco_id = $result->fetch_assoc()['id'];
    
    // Obtener ID de Banco de Venezuela
    $sql_get_bdv = "SELECT id FROM bancos WHERE nombre LIKE '%Venezuela%' LIMIT 1";
    $result = $conn->query($sql_get_bdv);
    $bdv_id = $result->fetch_assoc()['id'];
    
    // Insertar información de pago predeterminada
    $info_pago_default = [
        [
            'tipo' => 'banco',
            'banco_id' => $banesco_id,
            'numero_cuenta' => '0134-0000-00-0000000000',
            'titular' => 'Alexander Carrasquel',
            'cedula_rif' => 'V-12569655',
            'telefono' => '',
            'descripcion' => 'Cuenta Corriente Banesco'
        ],
        [
            'tipo' => 'banco',
            'banco_id' => $bdv_id,
            'numero_cuenta' => '0102-0000-00-0000000000',
            'titular' => 'Alexander Carrasquel',
            'cedula_rif' => 'V-12569655',
            'telefono' => '',
            'descripcion' => 'Cuenta Corriente Banco de Venezuela'
        ],
        [
            'tipo' => 'pago_movil',
            'banco_id' => $banesco_id,
            'numero_cuenta' => '',
            'titular' => 'Alexander Carrasquel',
            'cedula_rif' => 'V-12569655',
            'telefono' => '0414-1234567',
            'descripcion' => 'Pago Móvil Banesco'
        ],
        [
            'tipo' => 'efectivo',
            'banco_id' => null,
            'numero_cuenta' => '',
            'titular' => '',
            'cedula_rif' => '',
            'telefono' => '',
            'descripcion' => 'Pago en efectivo al momento de la entrega'
        ]
    ];
    
    $sql_insert_info = "INSERT INTO info_pago (tipo, banco_id, numero_cuenta, titular, cedula_rif, telefono, descripcion, activo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
    $stmt = $conn->prepare($sql_insert_info);
    
    foreach ($info_pago_default as $info) {
        $stmt->bind_param("sisssss", $info['tipo'], $info['banco_id'], $info['numero_cuenta'], $info['titular'], $info['cedula_rif'], $info['telefono'], $info['descripcion']);
        $stmt->execute();
    }
    
    echo "Información de pago predeterminada insertada correctamente.<br>";
}

// Cerrar la conexión
$conn->close();

echo "<br>Proceso de actualización de tablas completado.";
?>
