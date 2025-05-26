<?php
/**
 * Confirmar pago de un pedido
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
    // Actualizar el estado de los pagos a 'verificado'
    $sql_actualizar_pagos = "UPDATE pagos SET estado = 'verificado', fecha_pago = NOW() WHERE pedido_codigo = ? AND usuario_codigo = ?";
    $stmt_actualizar_pagos = $conn->prepare($sql_actualizar_pagos);
    $stmt_actualizar_pagos->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_actualizar_pagos->execute();

    // Verificar si se actualizaron registros
    if ($stmt_actualizar_pagos->affected_rows == 0) {
        throw new Exception("No se encontraron pagos para actualizar.");
    }

    // Verificar si existen registros en pedidos_finalizados
    $sql_verificar = "SELECT COUNT(*) as count FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ?";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_verificar->execute();
    $result_verificar = $stmt_verificar->get_result();
    $row_verificar = $result_verificar->fetch_assoc();

    if ($row_verificar['count'] > 0) {
        // Si ya existen registros en pedidos_finalizados, actualizar su estado
        $sql_actualizar = "UPDATE pedidos_finalizados SET estado = 'pagado', updated_at = NOW() WHERE pedido_codigo = ? AND usuario_id = ?";
        $stmt_actualizar = $conn->prepare($sql_actualizar);
        $stmt_actualizar->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_actualizar->execute();

        // Registrar para depuración
        error_log("confirmar_pago.php - Actualizados " . $stmt_actualizar->affected_rows . " registros en pedidos_finalizados para el pedido " . $pedido_codigo);
    } else {
        // Si no existen en pedidos_finalizados, moverlos desde pedido
        // Obtener productos del pedido
        $sql_productos = "SELECT * FROM pedido WHERE pedido = ? AND usuario_id = ?";
        $stmt_productos = $conn->prepare($sql_productos);
        $stmt_productos->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_productos->execute();
        $result_productos = $stmt_productos->get_result();

        // Insertar cada producto en pedidos_finalizados
        while ($producto = $result_productos->fetch_assoc()) {
            $sql_insertar = "INSERT INTO pedidos_finalizados (
                pedido_codigo,
                usuario_id,
                producto_id,
                cantidad,
                precio,
                precio_dolares,
                precio_bolivares,
                estado,
                fecha_pedido,
                fecha_pago,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pagado', NOW(), NOW(), NOW(), NOW())";

            $stmt_insertar = $conn->prepare($sql_insertar);
            $stmt_insertar->bind_param(
                "siidddd",
                $pedido_codigo,
                $usuario_id,
                $producto['producto_id'],
                $producto['cantidad'],
                $producto['precio'],
                $producto['precio_dolares'],
                $producto['precio_bolivares']
            );
            $stmt_insertar->execute();

            // Registrar para depuración
            error_log("confirmar_pago.php - Insertado producto ID " . $producto['producto_id'] . " en pedidos_finalizados para el pedido " . $pedido_codigo);
        }

        // Eliminar productos del pedido original
        $sql_eliminar = "DELETE FROM pedido WHERE pedido = ? AND usuario_id = ?";
        $stmt_eliminar = $conn->prepare($sql_eliminar);
        $stmt_eliminar->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_eliminar->execute();

        // Registrar para depuración
        error_log("confirmar_pago.php - Eliminados " . $stmt_eliminar->affected_rows . " registros de pedido para el pedido " . $pedido_codigo);
    }

    // Confirmar transacción
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pago confirmado correctamente. El pedido está ahora en proceso de empaque.'
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => 'Error al confirmar el pago: ' . $e->getMessage()
    ]);
}
