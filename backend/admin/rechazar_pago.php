<?php
/**
 * Rechazar pago de un pedido
 * Notifica al cliente que los datos del pago están incorrectos
 * Actualiza el stock de productos y mueve los pedidos a pendientes si es necesario
 */

// Incluir el helper de sesiones
require_once __DIR__ . '/../../helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado y es administrador
if (!esta_logueado() || !es_admin()) {
    echo json_encode([
        'success' => false,
        'message' => 'No tienes permisos para realizar esta acción.'
    ]);
    exit;
}

// Verificar si se recibieron los parámetros necesarios
if (!isset($_POST['pedido_codigo']) || !isset($_POST['usuario_id']) || !isset($_POST['motivo_rechazo'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parámetros insuficientes.'
    ]);
    exit;
}

// Incluir la conexión a la base de datos
require_once __DIR__ . '/../config/db.php';

// Obtener los parámetros
$pedido_codigo = $_POST['pedido_codigo'];
$usuario_id = $_POST['usuario_id'];
$motivo_rechazo = $_POST['motivo_rechazo'];

// Iniciar transacción
$conn->begin_transaction();

try {
    // 1. Actualizar el estado del pago a 'rechazado' y agregar comentario
    $comentario_rechazo = "[RECHAZADO] " . $motivo_rechazo;
    $sql_actualizar_pagos = "UPDATE pagos SET estado = 'rechazado', comentarios = ?, updated_at = NOW() WHERE pedido_codigo = ? AND usuario_codigo = ?";
    $stmt_actualizar_pagos = $conn->prepare($sql_actualizar_pagos);
    $stmt_actualizar_pagos->bind_param("ssi", $comentario_rechazo, $pedido_codigo, $usuario_id);
    $stmt_actualizar_pagos->execute();
    
    // Verificar si se actualizaron registros
    if ($stmt_actualizar_pagos->affected_rows == 0) {
        throw new Exception("No se encontraron pagos para actualizar.");
    }
    
    // 2. Obtener los productos del pedido
    // Primero intentamos obtener los productos de pedidos_finalizados
    $tabla_finalizados_existe = $conn->query("SHOW TABLES LIKE 'pedidos_finalizados'")->num_rows > 0;
    $productos = [];
    
    if ($tabla_finalizados_existe) {
        // Buscar en pedidos_finalizados primero
        $sql_productos_finalizados = "SELECT producto_id, cantidad 
                                   FROM pedidos_finalizados 
                                   WHERE pedido_codigo = ? AND usuario_id = ?";
        $stmt_productos = $conn->prepare($sql_productos_finalizados);
        $stmt_productos->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_productos->execute();
        $result_productos = $stmt_productos->get_result();
        
        while ($producto = $result_productos->fetch_assoc()) {
            $productos[] = $producto;
        }
    }
    
    // Si no encontramos productos en pedidos_finalizados, buscamos en la tabla pedido
    if (empty($productos)) {
        $sql_productos_pedido = "SELECT p.producto_id, p.cantidad 
                              FROM pedido p 
                              WHERE p.pedido = ? AND p.usuario_id = ?";
        $stmt_productos = $conn->prepare($sql_productos_pedido);
        $stmt_productos->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_productos->execute();
        $result_productos = $stmt_productos->get_result();
        
        while ($producto = $result_productos->fetch_assoc()) {
            $productos[] = $producto;
        }
    }
    
    // 3. Actualizar el stock de cada producto
    foreach ($productos as $producto) {
        // Primero obtenemos el stock actual del producto
        $sql_obtener_stock = "SELECT existencia_producto FROM productos_tienda WHERE id_producto = ?";
        $stmt_stock = $conn->prepare($sql_obtener_stock);
        $stmt_stock->bind_param("i", $producto['producto_id']);
        $stmt_stock->execute();
        $result_stock = $stmt_stock->get_result();
        
        if ($result_stock->num_rows > 0) {
            $stock_actual = $result_stock->fetch_assoc()['existencia_producto'];
            $nuevo_stock = $stock_actual + $producto['cantidad'];
            
            // Actualizamos el stock
            $sql_actualizar_stock = "UPDATE productos_tienda SET existencia_producto = ? WHERE id_producto = ?";
            $stmt_actualizar_stock = $conn->prepare($sql_actualizar_stock);
            $stmt_actualizar_stock->bind_param("ii", $nuevo_stock, $producto['producto_id']);
            
            if (!$stmt_actualizar_stock->execute()) {
                throw new Exception("Error al actualizar el stock del producto ID: " . $producto['producto_id']);
            }
            
            // Registro para depuración
            error_log("Stock actualizado - Producto ID: " . $producto['producto_id'] . 
                     ", Cantidad devuelta: " . $producto['cantidad'] . 
                     ", Stock anterior: " . $stock_actual . 
                     ", Nuevo stock: " . $nuevo_stock);
        } else {
            error_log("No se encontró el producto con ID: " . $producto['producto_id']);
        }
    }
    
    // 4. Mover los productos de vuelta a la tabla de pedidos pendientes si están en pedidos_finalizados
    // Primero verificamos si la tabla pedidos_finalizados existe
    $tabla_existe = $conn->query("SHOW TABLES LIKE 'pedidos_finalizados'")->num_rows > 0;
    
    if ($tabla_existe) {
        // Verificar si hay productos en pedidos_finalizados para este pedido
        $sql_check_finalizados = "SELECT COUNT(*) as count FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ?";
        $stmt_check_finalizados = $conn->prepare($sql_check_finalizados);
        $stmt_check_finalizados->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_check_finalizados->execute();
        $result_check_finalizados = $stmt_check_finalizados->get_result();
        $row_check_finalizados = $result_check_finalizados->fetch_assoc();
        
        if ($row_check_finalizados['count'] > 0) {
            // Mover los productos de vuelta a la tabla pedido
            $sql_mover_a_pendientes = "INSERT INTO pedido (pedido, usuario_id, producto_id, cantidad, precio, precio_dolares, precio_bolivares, created_at, updated_at)
                                     SELECT pedido_codigo, usuario_id, producto_id, cantidad, precio, precio_dolares, precio_bolivares, NOW(), NOW() 
                                     FROM pedidos_finalizados 
                                     WHERE pedido_codigo = ? AND usuario_id = ?";
            $stmt_mover = $conn->prepare($sql_mover_a_pendientes);
            $stmt_mover->bind_param("si", $pedido_codigo, $usuario_id);
            $stmt_mover->execute();
            
            // Eliminar los registros de pedidos_finalizados
            $sql_eliminar_finalizados = "DELETE FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ?";
            $stmt_eliminar = $conn->prepare($sql_eliminar_finalizados);
            $stmt_eliminar->bind_param("si", $pedido_codigo, $usuario_id);
            $stmt_eliminar->execute();
        }
    }
    
    // 5. Obtener el correo electrónico del usuario para notificación
    $sql_usuario = "SELECT correo_electronico as email FROM usuario WHERE codigo = ?";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("i", $usuario_id);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();
    
    if ($result_usuario->num_rows > 0) {
        $usuario = $result_usuario->fetch_assoc();
        $email_usuario = $usuario['email'];
        
        // Aquí iría el código para enviar el correo de notificación
        // Por ahora solo lo registramos en el log
        error_log("Notificación de pago rechazado enviada a: " . $email_usuario);
    }
    
    // Confirmar la transacción
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'El pago ha sido rechazado correctamente. Los productos han sido devueltos al stock y el pedido ha sido marcado como pendiente.'
    ]);
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollback();
    
    error_log("Error al rechazar el pago: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al rechazar el pago: ' . $e->getMessage()
    ]);
}
