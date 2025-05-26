<?php
/**
 * Revertir compra y reintegrar productos al catálogo
 */

// Incluir el helper de sesiones
require_once '../session_helper.php';

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
if (!isset($_POST['pedido_codigo']) || !isset($_POST['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parámetros insuficientes.'
    ]);
    exit;
}

// Incluir la conexión a la base de datos
require_once '../config/db.php';

// Obtener los parámetros
$pedido_codigo = $_POST['pedido_codigo'];
$usuario_id = $_POST['usuario_id'];

// Iniciar transacción
$conn->begin_transaction();

try {
    // Obtener los productos del pedido
    $sql_productos = "SELECT p.*, pt.existencia 
                     FROM pedido p 
                     JOIN productos_tienda pt ON p.producto_id = pt.id_producto 
                     WHERE p.pedido = ? AND p.usuario_id = ?";
    $stmt_productos = $conn->prepare($sql_productos);
    $stmt_productos->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_productos->execute();
    $result_productos = $stmt_productos->get_result();
    
    // Verificar si hay productos para revertir
    if ($result_productos->num_rows == 0) {
        // Verificar si hay productos en pagos
        $sql_check_pagos = "SELECT COUNT(*) as count FROM pagos WHERE pedido_codigo = ? AND usuario_codigo = ?";
        $stmt_check_pagos = $conn->prepare($sql_check_pagos);
        $stmt_check_pagos->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_check_pagos->execute();
        $result_check_pagos = $stmt_check_pagos->get_result();
        $row_check_pagos = $result_check_pagos->fetch_assoc();
        
        if ($row_check_pagos['count'] == 0) {
            throw new Exception("No se encontraron productos ni pagos para este pedido.");
        }
        
        // Si hay pagos pero no productos en pedido, verificar en pedidos_finalizados
        $sql_productos_finalizados = "SELECT pf.*, pt.existencia 
                                     FROM pedidos_finalizados pf 
                                     JOIN productos_tienda pt ON pf.producto_id = pt.id_producto 
                                     WHERE pf.pedido_codigo = ? AND pf.usuario_id = ?";
        $stmt_productos_finalizados = $conn->prepare($sql_productos_finalizados);
        $stmt_productos_finalizados->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_productos_finalizados->execute();
        $result_productos_finalizados = $stmt_productos_finalizados->get_result();
        
        if ($result_productos_finalizados->num_rows == 0) {
            throw new Exception("No se encontraron productos para revertir.");
        }
        
        // Reintegrar productos de pedidos_finalizados al catálogo
        while ($producto = $result_productos_finalizados->fetch_assoc()) {
            // Actualizar existencia en productos_tienda
            $nueva_existencia = $producto['existencia'] + $producto['cantidad'];
            $sql_actualizar_existencia = "UPDATE productos_tienda SET existencia = ? WHERE id_producto = ?";
            $stmt_actualizar_existencia = $conn->prepare($sql_actualizar_existencia);
            $stmt_actualizar_existencia->bind_param("ii", $nueva_existencia, $producto['producto_id']);
            $stmt_actualizar_existencia->execute();
        }
        
        // Eliminar registros de pedidos_finalizados
        $sql_eliminar_finalizados = "DELETE FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ?";
        $stmt_eliminar_finalizados = $conn->prepare($sql_eliminar_finalizados);
        $stmt_eliminar_finalizados->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_eliminar_finalizados->execute();
    } else {
        // Reintegrar productos de pedido al catálogo
        while ($producto = $result_productos->fetch_assoc()) {
            // Actualizar existencia en productos_tienda
            $nueva_existencia = $producto['existencia'] + $producto['cantidad'];
            $sql_actualizar_existencia = "UPDATE productos_tienda SET existencia = ? WHERE id_producto = ?";
            $stmt_actualizar_existencia = $conn->prepare($sql_actualizar_existencia);
            $stmt_actualizar_existencia->bind_param("ii", $nueva_existencia, $producto['producto_id']);
            $stmt_actualizar_existencia->execute();
        }
        
        // Eliminar registros de pedido
        $sql_eliminar_pedido = "DELETE FROM pedido WHERE pedido = ? AND usuario_id = ?";
        $stmt_eliminar_pedido = $conn->prepare($sql_eliminar_pedido);
        $stmt_eliminar_pedido->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_eliminar_pedido->execute();
    }
    
    // Eliminar registros de pagos
    $sql_eliminar_pagos = "DELETE FROM pagos WHERE pedido_codigo = ? AND usuario_codigo = ?";
    $stmt_eliminar_pagos = $conn->prepare($sql_eliminar_pagos);
    $stmt_eliminar_pagos->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_eliminar_pagos->execute();
    
    // Confirmar transacción
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Compra revertida correctamente. Los productos han sido reintegrados al catálogo.'
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al revertir la compra: ' . $e->getMessage()
    ]);
}
