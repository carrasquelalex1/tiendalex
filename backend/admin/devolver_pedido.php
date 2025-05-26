<?php
/**
 * Devolver pedido a estado pendiente
 * Da una segunda oportunidad al cliente para verificar sus datos de pago
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
if (!isset($_POST['pedido_codigo']) || !isset($_POST['usuario_id']) || !isset($_POST['mensaje_cliente'])) {
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
$mensaje_cliente = $_POST['mensaje_cliente'];

// Iniciar transacción
$conn->begin_transaction();

try {
    // Verificar si el pedido existe en la tabla pagos
    $sql_verificar = "SELECT COUNT(*) as count FROM pagos WHERE pedido_codigo = ? AND usuario_codigo = ?";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_verificar->execute();
    $result_verificar = $stmt_verificar->get_result();
    $row_verificar = $result_verificar->fetch_assoc();

    if ($row_verificar['count'] == 0) {
        throw new Exception("No se encontró el pedido con pago informado.");
    }

    // Verificar si la tabla historial_pagos existe
    $sql_check_table = "SHOW TABLES LIKE 'historial_pagos'";
    $result_check = $conn->query($sql_check_table);
    if (!$result_check || $result_check->num_rows == 0) {
        // Crear la tabla historial_pagos si no existe
        $sql_create = "CREATE TABLE IF NOT EXISTS historial_pagos (
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

        if (!$conn->query($sql_create)) {
            throw new Exception("Error al crear la tabla historial_pagos: " . $conn->error);
        }
    }

    // Obtener los datos del pago para guardarlos como historial
    $sql_pago = "SELECT * FROM pagos WHERE pedido_codigo = ? AND usuario_codigo = ? LIMIT 1";
    $stmt_pago = $conn->prepare($sql_pago);
    $stmt_pago->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_pago->execute();
    $result_pago = $stmt_pago->get_result();
    $pago = $result_pago->fetch_assoc();

    // Guardar el historial de pago rechazado
    try {
        // Convertir el tipo de datos si es necesario
        $metodo_pago = (string)$pago['metodo_pago'];
        $banco = $pago['banco'] ? (string)$pago['banco'] : null;
        $referencia = $pago['referencia'] ? (string)$pago['referencia'] : null;
        $monto = (float)$pago['monto'];
        $telefono = $pago['telefono'] ? (string)$pago['telefono'] : null;
        $comentarios = $pago['comentarios'] ? (string)$pago['comentarios'] : null;

        $sql_historial = "INSERT INTO historial_pagos (
            pedido_codigo,
            usuario_codigo,
            metodo_pago,
            banco,
            referencia,
            monto,
            fecha_pago,
            telefono,
            comentarios,
            estado,
            comentarios_admin,
            fecha_registro
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'devuelto', ?, NOW())";

        $stmt_historial = $conn->prepare($sql_historial);

        if (!$stmt_historial) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }

        $stmt_historial->bind_param(
            "sisssdssss",
            $pedido_codigo,
            $usuario_id,
            $metodo_pago,
            $banco,
            $referencia,
            $monto,
            $pago['fecha_pago'],
            $telefono,
            $comentarios,
            $mensaje_cliente
        );

        if (!$stmt_historial->execute()) {
            throw new Exception("Error al guardar el historial de pago: " . $stmt_historial->error);
        }
    } catch (Exception $e) {
        // Registrar el error pero continuar con el proceso
        error_log("Error al guardar historial de pago: " . $e->getMessage());
        // No lanzamos la excepción para que el proceso continúe
    }

    // Eliminar el registro de pago
    $sql_eliminar = "DELETE FROM pagos WHERE pedido_codigo = ? AND usuario_codigo = ?";
    $stmt_eliminar = $conn->prepare($sql_eliminar);
    $stmt_eliminar->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_eliminar->execute();

    // Verificar si se eliminaron registros
    if ($stmt_eliminar->affected_rows == 0) {
        throw new Exception("No se pudo eliminar el pago informado.");
    }

    // Verificar si el pedido está en la tabla pedidos_finalizados
    $sql_check_finalizados = "SELECT COUNT(*) as count FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ?";
    $stmt_check_finalizados = $conn->prepare($sql_check_finalizados);
    $stmt_check_finalizados->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_check_finalizados->execute();
    $result_check_finalizados = $stmt_check_finalizados->get_result();
    $row_check_finalizados = $result_check_finalizados->fetch_assoc();

    if ($row_check_finalizados['count'] > 0) {
        // Opción 1: Actualizar el estado del pedido en pedidos_finalizados
        $sql_update_finalizados = "UPDATE pedidos_finalizados SET estado = 'pendiente', updated_at = NOW() WHERE pedido_codigo = ? AND usuario_id = ?";
        $stmt_update_finalizados = $conn->prepare($sql_update_finalizados);
        $stmt_update_finalizados->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_update_finalizados->execute();

        if ($stmt_update_finalizados->affected_rows == 0) {
            error_log("Advertencia: No se actualizó ningún registro en pedidos_finalizados para el pedido " . $pedido_codigo);
        }

        // Opción 2: Eliminar el pedido de pedidos_finalizados y moverlo a pedido
        // Esto evita duplicados entre las tablas
        /*
        // Primero obtenemos los datos del pedido
        $sql_get_finalizados = "SELECT * FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ?";
        $stmt_get_finalizados = $conn->prepare($sql_get_finalizados);
        $stmt_get_finalizados->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_get_finalizados->execute();
        $result_get_finalizados = $stmt_get_finalizados->get_result();

        // Luego eliminamos el pedido de pedidos_finalizados
        $sql_delete_finalizados = "DELETE FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ?";
        $stmt_delete_finalizados = $conn->prepare($sql_delete_finalizados);
        $stmt_delete_finalizados->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_delete_finalizados->execute();

        if ($stmt_delete_finalizados->affected_rows == 0) {
            error_log("Advertencia: No se eliminó ningún registro en pedidos_finalizados para el pedido " . $pedido_codigo);
        }
        */
    }

    // Verificar si el pedido existe en la tabla pedido
    $sql_check_pedido = "SELECT COUNT(*) as count FROM pedido WHERE pedido = ?";
    $stmt_check_pedido = $conn->prepare($sql_check_pedido);
    $stmt_check_pedido->bind_param("s", $pedido_codigo);
    $stmt_check_pedido->execute();
    $result_check_pedido = $stmt_check_pedido->get_result();
    $row_check_pedido = $result_check_pedido->fetch_assoc();

    // Si el pedido no existe en la tabla pedido, pero sí en pedidos_finalizados,
    // necesitamos mover los registros de pedidos_finalizados a pedido
    if ($row_check_pedido['count'] == 0 && $row_check_finalizados['count'] > 0) {
        $sql_get_finalizados = "SELECT * FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ?";
        $stmt_get_finalizados = $conn->prepare($sql_get_finalizados);
        $stmt_get_finalizados->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_get_finalizados->execute();
        $result_get_finalizados = $stmt_get_finalizados->get_result();

        while ($producto = $result_get_finalizados->fetch_assoc()) {
            // Verificar si este producto ya existe en la tabla pedido
            $sql_check_producto = "SELECT COUNT(*) as count FROM pedido
                                  WHERE pedido = ? AND usuario_id = ? AND producto_id = ?";
            $stmt_check_producto = $conn->prepare($sql_check_producto);
            $stmt_check_producto->bind_param("sii", $pedido_codigo, $usuario_id, $producto['producto_id']);
            $stmt_check_producto->execute();
            $result_check_producto = $stmt_check_producto->get_result();
            $row_check_producto = $result_check_producto->fetch_assoc();

            // Solo insertar si el producto no existe ya en la tabla pedido
            if ($row_check_producto['count'] == 0) {
                // Insertar en la tabla pedido
                $sql_insert_pedido = "INSERT INTO pedido (
                    usuario_id,
                    producto_id,
                    cantidad,
                    precio,
                    precio_dolares,
                    precio_bolivares,
                    pedido,
                    fecha_agregado,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())";

                $stmt_insert_pedido = $conn->prepare($sql_insert_pedido);
                $stmt_insert_pedido->bind_param(
                    "iiiddds",
                    $producto['usuario_id'],
                    $producto['producto_id'],
                    $producto['cantidad'],
                    $producto['precio'],
                    $producto['precio_dolares'],
                    $producto['precio_bolivares'],
                    $pedido_codigo
                );

                if (!$stmt_insert_pedido->execute()) {
                    error_log("Error al insertar producto en tabla pedido: " . $stmt_insert_pedido->error);
                } else {
                    error_log("Producto ID " . $producto['producto_id'] . " insertado correctamente en tabla pedido para el pedido " . $pedido_codigo);
                }
            } else {
                error_log("Producto ID " . $producto['producto_id'] . " ya existe en tabla pedido para el pedido " . $pedido_codigo);
            }
        }
    } else if ($row_check_pedido['count'] == 0 && $row_check_finalizados['count'] == 0) {
        error_log("Advertencia: El pedido " . $pedido_codigo . " no existe en la tabla pedido ni en pedidos_finalizados");
    } else {
        error_log("El pedido " . $pedido_codigo . " ya existe en la tabla pedido con " . $row_check_pedido['count'] . " productos");
    }

    // Confirmar transacción
    $conn->commit();

    // Registrar en el log para depuración
    error_log("Pedido devuelto correctamente: " . $pedido_codigo . " - Usuario: " . $usuario_id);

    // Registrar información sobre las tablas
    $sql_check_pedido = "SELECT COUNT(*) as count FROM pedido WHERE pedido = ?";
    $stmt_check_pedido = $conn->prepare($sql_check_pedido);
    $stmt_check_pedido->bind_param("s", $pedido_codigo);
    $stmt_check_pedido->execute();
    $result_check_pedido = $stmt_check_pedido->get_result();
    $row_check_pedido = $result_check_pedido->fetch_assoc();

    $sql_check_finalizados = "SELECT COUNT(*) as count FROM pedidos_finalizados WHERE pedido_codigo = ?";
    $stmt_check_finalizados = $conn->prepare($sql_check_finalizados);
    $stmt_check_finalizados->bind_param("s", $pedido_codigo);
    $stmt_check_finalizados->execute();
    $result_check_finalizados = $stmt_check_finalizados->get_result();
    $row_check_finalizados = $result_check_finalizados->fetch_assoc();

    $sql_check_pagos = "SELECT COUNT(*) as count FROM pagos WHERE pedido_codigo = ?";
    $stmt_check_pagos = $conn->prepare($sql_check_pagos);
    $stmt_check_pagos->bind_param("s", $pedido_codigo);
    $stmt_check_pagos->execute();
    $result_check_pagos = $stmt_check_pagos->get_result();
    $row_check_pagos = $result_check_pagos->fetch_assoc();

    error_log("Estado final del pedido " . $pedido_codigo . ": " .
              "En tabla pedido: " . $row_check_pedido['count'] . ", " .
              "En tabla pedidos_finalizados: " . $row_check_finalizados['count'] . ", " .
              "En tabla pagos: " . $row_check_pagos['count']);

    echo json_encode([
        'success' => true,
        'message' => 'El pedido ha sido devuelto a pendientes. Se ha notificado al cliente para que verifique sus datos de pago.'
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();

    // Registrar el error en el log
    error_log("Error al devolver pedido: " . $pedido_codigo . " - Usuario: " . $usuario_id . " - Error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Error al devolver el pedido: ' . $e->getMessage()
    ]);
}
