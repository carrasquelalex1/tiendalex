<?php
/**
 * Maneja la cancelación de pedidos por parte del usuario
 * Solo permite cancelar pedidos con estado 'pendiente' o 'pendiente de pago'
 * Devuelve los productos al inventario
 */

// Incluir el helper de sesiones
require_once __DIR__ . '/../helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    echo json_encode([
        'success' => false,
        'message' => 'Debes iniciar sesión para realizar esta acción.'
    ]);
    exit;
}

// Verificar si se recibió el código del pedido
if (!isset($_POST['pedido_codigo'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No se especificó el pedido a cancelar.'
    ]);
    exit;
}

// Incluir la conexión a la base de datos
require_once __DIR__ . '/../config/db.php';

// Obtener los parámetros
$pedido_codigo = $_POST['pedido_codigo'];
$usuario_id = $_SESSION['usuario_id'];

// Iniciar transacción
$conn->begin_transaction();

try {
    // 1. Verificar que el pedido existe y pertenece al usuario
    $sql_verificar = "SELECT p.estado, COUNT(*) as total_productos 
                    FROM pedido p 
                    WHERE p.pedido = ? AND p.usuario_id = ?
                    GROUP BY p.pedido, p.estado";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_verificar->execute();
    $result_verificar = $stmt_verificar->get_result();
    
    if ($result_verificar->num_rows === 0) {
        throw new Exception("No se encontró el pedido o no tienes permiso para cancelarlo.");
    }
    
    $pedido = $result_verificar->fetch_assoc();
    
    // 2. Verificar que el pedido esté en un estado cancelable
    $estados_permitidos = ['pendiente', 'pendiente de pago'];
    if (!in_array(strtolower($pedido['estado']), $estados_permitidos)) {
        throw new Exception("Solo se pueden cancelar pedidos que estén pendientes de pago.");
    }
    
    // 3. Obtener los productos del pedido
    $sql_productos = "SELECT producto_id, cantidad 
                     FROM pedido 
                     WHERE pedido = ? AND usuario_id = ?";
    $stmt_productos = $conn->prepare($sql_productos);
    $stmt_productos->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_productos->execute();
    $result_productos = $stmt_productos->get_result();
    
    // 4. Actualizar el stock de cada producto
    while ($producto = $result_productos->fetch_assoc()) {
        // Obtener el stock actual
        $sql_obtener_stock = "SELECT existencia_producto FROM productos_tienda WHERE id_producto = ?";
        $stmt_stock = $conn->prepare($sql_obtener_stock);
        $stmt_stock->bind_param("i", $producto['producto_id']);
        $stmt_stock->execute();
        $result_stock = $stmt_stock->get_result();
        
        if ($result_stock->num_rows > 0) {
            $stock_actual = $result_stock->fetch_assoc()['existencia_producto'];
            $nuevo_stock = $stock_actual + $producto['cantidad'];
            
            // Actualizar el stock
            $sql_actualizar_stock = "UPDATE productos_tienda SET existencia_producto = ? WHERE id_producto = ?";
            $stmt_actualizar_stock = $conn->prepare($sql_actualizar_stock);
            $stmt_actualizar_stock->bind_param("ii", $nuevo_stock, $producto['producto_id']);
            
            if (!$stmt_actualizar_stock->execute()) {
                throw new Exception("Error al actualizar el stock del producto ID: " . $producto['producto_id']);
            }
            
            // Registro para depuración
            error_log("Stock actualizado por cancelación de pedido - Producto ID: " . $producto['producto_id'] . 
                     ", Cantidad devuelta: " . $producto['cantidad'] . 
                     ", Stock anterior: " . $stock_actual . 
                     ", Nuevo stock: " . $nuevo_stock);
        }
    }
    
    // 5. Actualizar el estado del pedido a 'cancelado'
    $sql_actualizar_pedido = "UPDATE pedido SET estado = 'cancelado', updated_at = NOW() 
                             WHERE pedido = ? AND usuario_id = ?";
    $stmt_actualizar = $conn->prepare($sql_actualizar_pedido);
    $stmt_actualizar->bind_param("si", $pedido_codigo, $usuario_id);
    
    if (!$stmt_actualizar->execute()) {
        throw new Exception("Error al actualizar el estado del pedido.");
    }
    
    // 6. Actualizar también en la tabla de pagos si existe
    $tabla_pagos_existe = $conn->query("SHOW TABLES LIKE 'pagos'")->num_rows > 0;
    if ($tabla_pagos_existe) {
        $sql_actualizar_pago = "UPDATE pagos SET estado = 'cancelado', 
                              comentarios = CONCAT(IFNULL(comentarios, ''), '\nCancelado por el usuario el ', NOW())
                              WHERE pedido_codigo = ? AND usuario_codigo = ?";
        $stmt_pago = $conn->prepare($sql_actualizar_pago);
        $stmt_pago->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_pago->execute();
    }
    
    // Confirmar la transacción
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'El pedido ha sido cancelado correctamente. Los productos han sido devueltos al inventario.'
    ]);
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollback();
    
    error_log("Error al cancelar el pedido: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cancelar el pedido: ' . $e->getMessage()
    ]);
}
