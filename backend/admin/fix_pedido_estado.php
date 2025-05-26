<?php
/**
 * Script para corregir el estado de un pedido específico
 */

// Incluir la conexión a la base de datos
require_once '../config/db.php';

// Verificar si se recibieron los parámetros necesarios
if (!isset($_POST['pedido_codigo']) || !isset($_POST['accion'])) {
    echo "Error: Parámetros insuficientes.";
    exit;
}

// Obtener los parámetros
$pedido_codigo = $_POST['pedido_codigo'];
$accion = $_POST['accion'];

// Iniciar transacción
$conn->begin_transaction();

try {
    // Registrar la acción
    error_log("fix_pedido_estado.php - Acción: $accion, Pedido: $pedido_codigo");
    
    // Verificar si la tabla envios existe
    $tabla_envios_existe = false;
    $sql_check_table = "SHOW TABLES LIKE 'envios'";
    $result_check = $conn->query($sql_check_table);
    if ($result_check && $result_check->num_rows > 0) {
        $tabla_envios_existe = true;
    }
    
    // Si la tabla no existe, crearla
    if (!$tabla_envios_existe) {
        $sql_create_table = "CREATE TABLE envios (
            id INT(11) NOT NULL AUTO_INCREMENT,
            pedido_codigo VARCHAR(20) NOT NULL,
            usuario_codigo INT(11) NOT NULL,
            estado ENUM('pendiente', 'en_proceso', 'enviado', 'entregado') NOT NULL DEFAULT 'pendiente',
            fecha_envio DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_pedido_codigo (pedido_codigo),
            KEY idx_usuario_codigo (usuario_codigo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        if (!$conn->query($sql_create_table)) {
            throw new Exception("Error al crear la tabla 'envios': " . $conn->error);
        }
        
        $tabla_envios_existe = true;
        echo "<p>Se ha creado la tabla 'envios'.</p>";
    }
    
    // Obtener el ID del usuario
    $usuario_id = null;
    $sql_usuario = "SELECT usuario_id FROM pedidos_finalizados WHERE pedido_codigo = ? LIMIT 1";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("s", $pedido_codigo);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();
    
    if ($result_usuario && $result_usuario->num_rows > 0) {
        $row = $result_usuario->fetch_assoc();
        $usuario_id = $row['usuario_id'];
    } else {
        // Intentar obtener el ID del usuario desde la tabla pagos
        $sql_usuario_pagos = "SELECT usuario_codigo FROM pagos WHERE pedido_codigo = ? LIMIT 1";
        $stmt_usuario_pagos = $conn->prepare($sql_usuario_pagos);
        $stmt_usuario_pagos->bind_param("s", $pedido_codigo);
        $stmt_usuario_pagos->execute();
        $result_usuario_pagos = $stmt_usuario_pagos->get_result();
        
        if ($result_usuario_pagos && $result_usuario_pagos->num_rows > 0) {
            $row = $result_usuario_pagos->fetch_assoc();
            $usuario_id = $row['usuario_codigo'];
        } else {
            throw new Exception("No se pudo obtener el ID del usuario para el pedido $pedido_codigo.");
        }
    }
    
    // Realizar la acción seleccionada
    if ($accion === 'update_envios') {
        // Verificar si existe un registro en la tabla envios
        $sql_verificar = "SELECT id FROM envios WHERE pedido_codigo = ? LIMIT 1";
        $stmt_verificar = $conn->prepare($sql_verificar);
        $stmt_verificar->bind_param("s", $pedido_codigo);
        $stmt_verificar->execute();
        $result_verificar = $stmt_verificar->get_result();
        
        if ($result_verificar->num_rows > 0) {
            // Actualizar el registro existente
            $sql_actualizar = "UPDATE envios SET estado = 'enviado', fecha_envio = NOW(), updated_at = NOW() WHERE pedido_codigo = ?";
            $stmt_actualizar = $conn->prepare($sql_actualizar);
            $stmt_actualizar->bind_param("s", $pedido_codigo);
            
            if (!$stmt_actualizar->execute()) {
                throw new Exception("Error al actualizar el estado en la tabla 'envios': " . $stmt_actualizar->error);
            }
            
            echo "<p>Se ha actualizado el estado en la tabla 'envios' a 'enviado'.</p>";
        } else {
            throw new Exception("No existe un registro en la tabla 'envios' para el pedido $pedido_codigo. Utilice la opción 'Insertar nuevo registro'.");
        }
    } elseif ($accion === 'insert_envios') {
        // Insertar un nuevo registro en la tabla envios
        $sql_insertar = "INSERT INTO envios (pedido_codigo, usuario_codigo, estado, fecha_envio) VALUES (?, ?, 'enviado', NOW())";
        $stmt_insertar = $conn->prepare($sql_insertar);
        $stmt_insertar->bind_param("si", $pedido_codigo, $usuario_id);
        
        if (!$stmt_insertar->execute()) {
            throw new Exception("Error al insertar un nuevo registro en la tabla 'envios': " . $stmt_insertar->error);
        }
        
        echo "<p>Se ha insertado un nuevo registro en la tabla 'envios' con estado 'enviado'.</p>";
    } elseif ($accion === 'update_all') {
        // Actualizar el estado en todas las tablas relevantes
        
        // 1. Actualizar en pedidos_finalizados
        $sql_actualizar_pf = "UPDATE pedidos_finalizados SET estado = 'enviado', updated_at = NOW() WHERE pedido_codigo = ?";
        $stmt_actualizar_pf = $conn->prepare($sql_actualizar_pf);
        $stmt_actualizar_pf->bind_param("s", $pedido_codigo);
        
        if (!$stmt_actualizar_pf->execute()) {
            throw new Exception("Error al actualizar el estado en la tabla 'pedidos_finalizados': " . $stmt_actualizar_pf->error);
        }
        
        echo "<p>Se ha actualizado el estado en la tabla 'pedidos_finalizados' a 'enviado'.</p>";
        
        // 2. Actualizar en pagos
        $sql_actualizar_pagos = "UPDATE pagos SET estado = 'verificado', updated_at = NOW() WHERE pedido_codigo = ?";
        $stmt_actualizar_pagos = $conn->prepare($sql_actualizar_pagos);
        $stmt_actualizar_pagos->bind_param("s", $pedido_codigo);
        
        if (!$stmt_actualizar_pagos->execute()) {
            throw new Exception("Error al actualizar el estado en la tabla 'pagos': " . $stmt_actualizar_pagos->error);
        }
        
        echo "<p>Se ha actualizado el estado en la tabla 'pagos' a 'verificado'.</p>";
        
        // 3. Verificar si existe un registro en la tabla envios
        $sql_verificar = "SELECT id FROM envios WHERE pedido_codigo = ? LIMIT 1";
        $stmt_verificar = $conn->prepare($sql_verificar);
        $stmt_verificar->bind_param("s", $pedido_codigo);
        $stmt_verificar->execute();
        $result_verificar = $stmt_verificar->get_result();
        
        if ($result_verificar->num_rows > 0) {
            // Actualizar el registro existente
            $sql_actualizar_envios = "UPDATE envios SET estado = 'enviado', fecha_envio = NOW(), updated_at = NOW() WHERE pedido_codigo = ?";
            $stmt_actualizar_envios = $conn->prepare($sql_actualizar_envios);
            $stmt_actualizar_envios->bind_param("s", $pedido_codigo);
            
            if (!$stmt_actualizar_envios->execute()) {
                throw new Exception("Error al actualizar el estado en la tabla 'envios': " . $stmt_actualizar_envios->error);
            }
            
            echo "<p>Se ha actualizado el estado en la tabla 'envios' a 'enviado'.</p>";
        } else {
            // Insertar un nuevo registro
            $sql_insertar_envios = "INSERT INTO envios (pedido_codigo, usuario_codigo, estado, fecha_envio) VALUES (?, ?, 'enviado', NOW())";
            $stmt_insertar_envios = $conn->prepare($sql_insertar_envios);
            $stmt_insertar_envios->bind_param("si", $pedido_codigo, $usuario_id);
            
            if (!$stmt_insertar_envios->execute()) {
                throw new Exception("Error al insertar un nuevo registro en la tabla 'envios': " . $stmt_insertar_envios->error);
            }
            
            echo "<p>Se ha insertado un nuevo registro en la tabla 'envios' con estado 'enviado'.</p>";
        }
    } else {
        throw new Exception("Acción no válida: $accion");
    }
    
    // Confirmar transacción
    $conn->commit();
    
    echo "<p style='color: green; font-weight: bold;'>La corrección se ha aplicado correctamente.</p>";
    echo "<p><a href='check_pedido_estado.php?pedido=$pedido_codigo' style='color: blue;'>Verificar el estado actualizado</a></p>";
    echo "<p><a href='/tiendalex2/mis_compras.php' style='color: blue;'>Ir a Mis Compras</a></p>";
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    
    echo "<p style='color: red; font-weight: bold;'>Error: " . $e->getMessage() . "</p>";
    echo "<p><a href='check_pedido_estado.php?pedido=$pedido_codigo' style='color: blue;'>Volver</a></p>";
}
