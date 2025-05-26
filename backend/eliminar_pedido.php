<?php
/**
 * Maneja la eliminación de pedidos rechazados
 */

// Incluir el helper de sesiones
require_once __DIR__ . '/../helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    echo json_encode([
        'success' => false,
        'message' => 'No has iniciado sesión.',
        'session' => $_SESSION // Para depuración
    ]);
    exit;
}

// Verificar si se recibió el código del pedido
if (!isset($_POST['pedido_codigo']) || empty($_POST['pedido_codigo'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No se especificó el pedido a eliminar.'
    ]);
    exit;
}

$pedido_codigo = $_POST['pedido_codigo'];
$usuario_id = $_SESSION['usuario_id'];

// Incluir la conexión a la base de datos
require_once __DIR__ . '/../config/db.php';

// Iniciar transacción
$conn->begin_transaction();

try {
    // 1. Verificar que el pedido existe y pertenece al usuario
    $sql_verificar = "SELECT p.estado FROM pagos p 
                    WHERE p.pedido_codigo = ? AND p.usuario_codigo = ?
                    ORDER BY p.id DESC LIMIT 1";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_verificar->execute();
    $result_verificar = $stmt_verificar->get_result();
    
    if ($result_verificar->num_rows === 0) {
        throw new Exception("No se encontró el pedido o no tienes permiso para eliminarlo.");
    }
    
    $pago = $result_verificar->fetch_assoc();
    if ($pago['estado'] !== 'rechazado') {
        throw new Exception("Solo se pueden eliminar pedidos con estado 'rechazado'.");
    }
    
    // 2. Eliminar registros relacionados en orden
    
    // a. Eliminar de pedidos_finalizados si existe
    $sql_eliminar_finalizados = "DELETE FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ?";
    $stmt_eliminar_finalizados = $conn->prepare($sql_eliminar_finalizados);
    $stmt_eliminar_finalizados->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_eliminar_finalizados->execute();
    
    // b. Eliminar de la tabla pedido
    $sql_eliminar_pedido = "DELETE FROM pedido WHERE pedido = ? AND usuario_id = ?";
    $stmt_eliminar_pedido = $conn->prepare($sql_eliminar_pedido);
    $stmt_eliminar_pedido->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_eliminar_pedido->execute();
    
    // c. Eliminar de la tabla pagos
    $sql_eliminar_pagos = "DELETE FROM pagos WHERE pedido_codigo = ? AND usuario_codigo = ?";
    $stmt_eliminar_pagos = $conn->prepare($sql_eliminar_pagos);
    $stmt_eliminar_pagos->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_eliminar_pagos->execute();
    
    // Confirmar la transacción
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pedido eliminado correctamente.'
    ]);
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollback();
    
    error_log("Error al eliminar el pedido: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar el pedido: ' . $e->getMessage()
    ]);
}
