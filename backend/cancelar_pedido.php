<?php
/**
 * Script para cancelar un pedido y devolver los productos al catálogo
 */

// Incluir archivos necesarios
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/../helpers/carrito/carrito_functions.php';
require_once __DIR__ . '/../helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Registrar información de depuración
error_log("cancelar_pedido.php - SESSION al inicio: " . print_r($_SESSION, true));

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    error_log("cancelar_pedido.php - Usuario no logueado");
    header("Location: ../index.php?error=1&message=" . urlencode("Debe iniciar sesión para cancelar un pedido."));
    exit;
}

// Verificar si el usuario es administrador
if (es_admin()) {
    error_log("cancelar_pedido.php - Usuario es administrador");
    header("Location: ../index.php?error=1&message=" . urlencode("Los administradores no pueden cancelar pedidos."));
    exit;
}

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener el ID del usuario
    $usuario_id = $_SESSION['usuario_logueado'];

    // Obtener el código del pedido
    $codigo_pedido = isset($_POST['codigo_pedido']) ? trim($_POST['codigo_pedido']) : '';

    error_log("cancelar_pedido.php - Código de pedido recibido: " . $codigo_pedido);

    if (empty($codigo_pedido)) {
        error_log("cancelar_pedido.php - Código de pedido vacío");
        header("Location: ../mis_pedidos.php?error=1&message=" . urlencode("Código de pedido no válido."));
        exit;
    }

    // Verificar que el pedido pertenezca al usuario (en tabla pedido o pedidos_finalizados)
    $pedido_encontrado = false;
    $productos_pedido = [];

    // Verificar en la tabla pedido
    $sql = "SELECT * FROM pedido WHERE pedido = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $codigo_pedido, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $pedido_encontrado = true;
        // Guardar los productos del pedido
        while ($row = $result->fetch_assoc()) {
            $productos_pedido[] = $row;
        }
    }

    // Si no se encontró en la tabla pedido, verificar en pedidos_finalizados
    if (!$pedido_encontrado) {
        // Verificar si la tabla pedidos_finalizados existe
        $tabla_existe = false;
        $sql_check_table = "SHOW TABLES LIKE 'pedidos_finalizados'";
        $result_check = $conn->query($sql_check_table);
        if ($result_check && $result_check->num_rows > 0) {
            $tabla_existe = true;
        }

        if ($tabla_existe) {
            $sql_finalizados = "SELECT * FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ? AND estado = 'pendiente'";
            $stmt_finalizados = $conn->prepare($sql_finalizados);
            $stmt_finalizados->bind_param("si", $codigo_pedido, $usuario_id);
            $stmt_finalizados->execute();
            $result_finalizados = $stmt_finalizados->get_result();

            if ($result_finalizados->num_rows > 0) {
                $pedido_encontrado = true;
                // Guardar los productos del pedido
                while ($row = $result_finalizados->fetch_assoc()) {
                    $productos_pedido[] = $row;
                }
            }
        }
    }

    if (!$pedido_encontrado) {
        header("Location: ../mis_pedidos.php?error=1&message=" . urlencode("El pedido no pertenece a tu cuenta o no está en estado pendiente."));
        exit;
    }

    // Verificar que el pedido no tenga pagos registrados
    $sql_pago = "SELECT * FROM pagos WHERE pedido_codigo = ? AND usuario_codigo = ? LIMIT 1";
    $stmt_pago = $conn->prepare($sql_pago);
    $stmt_pago->bind_param("si", $codigo_pedido, $usuario_id);
    $stmt_pago->execute();
    $result_pago = $stmt_pago->get_result();

    if ($result_pago->num_rows > 0) {
        header("Location: ../mis_pedidos.php?error=1&message=" . urlencode("No se puede cancelar un pedido con pago registrado."));
        exit;
    }

    // Iniciar transacción
    $conn->begin_transaction();
    error_log("cancelar_pedido.php - Iniciando transacción");

    try {
        // Ya tenemos los productos del pedido en $productos_pedido
        error_log("cancelar_pedido.php - Productos encontrados: " . count($productos_pedido));

        // Devolver la existencia de cada producto
        foreach ($productos_pedido as $producto) {
            error_log("cancelar_pedido.php - Devolviendo existencia del producto ID: " . $producto['producto_id'] . ", Cantidad: " . $producto['cantidad']);

            $sql = "UPDATE productos_tienda SET existencia_producto = existencia_producto + ? WHERE id_producto = ?";
            $stmt_update = $conn->prepare($sql);
            $stmt_update->bind_param("ii", $producto['cantidad'], $producto['producto_id']);

            if (!$stmt_update->execute()) {
                $error_msg = "Error al actualizar la existencia del producto: " . $stmt_update->error;
                error_log("cancelar_pedido.php - " . $error_msg);
                throw new Exception($error_msg);
            }

            $filas_afectadas = $stmt_update->affected_rows;
            error_log("cancelar_pedido.php - Filas afectadas al actualizar existencia: " . $filas_afectadas);

            if ($filas_afectadas === 0) {
                error_log("cancelar_pedido.php - Advertencia: No se actualizó la existencia del producto ID: " . $producto['producto_id']);
            }
        }

        // Eliminar el pedido de la tabla pedido
        $sql = "DELETE FROM pedido WHERE pedido = ? AND usuario_id = ?";
        $stmt_delete = $conn->prepare($sql);
        $stmt_delete->bind_param("si", $codigo_pedido, $usuario_id);

        error_log("cancelar_pedido.php - Eliminando pedido de la tabla pedido: " . $codigo_pedido);
        $stmt_delete->execute();
        $filas_afectadas_pedido = $stmt_delete->affected_rows;
        error_log("cancelar_pedido.php - Filas afectadas al eliminar de tabla pedido: " . $filas_afectadas_pedido);

        // Eliminar el pedido de la tabla pedidos_finalizados si existe
        $filas_afectadas_finalizados = 0;
        if ($tabla_existe) {
            $sql_delete_finalizados = "DELETE FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ? AND estado = 'pendiente'";
            $stmt_delete_finalizados = $conn->prepare($sql_delete_finalizados);
            $stmt_delete_finalizados->bind_param("si", $codigo_pedido, $usuario_id);

            error_log("cancelar_pedido.php - Eliminando pedido de la tabla pedidos_finalizados: " . $codigo_pedido);
            $stmt_delete_finalizados->execute();
            $filas_afectadas_finalizados = $stmt_delete_finalizados->affected_rows;
            error_log("cancelar_pedido.php - Filas afectadas al eliminar de tabla pedidos_finalizados: " . $filas_afectadas_finalizados);
        }

        // Verificar si se eliminó el pedido de alguna tabla
        $total_filas_afectadas = $filas_afectadas_pedido + $filas_afectadas_finalizados;
        error_log("cancelar_pedido.php - Total filas afectadas: " . $total_filas_afectadas);

        if ($total_filas_afectadas === 0) {
            $error_msg = "No se encontró el pedido para eliminar";
            error_log("cancelar_pedido.php - " . $error_msg);
            throw new Exception($error_msg);
        }

        // Confirmar transacción
        $conn->commit();
        error_log("cancelar_pedido.php - Transacción completada con éxito");

        // Redirigir a la página de mis pedidos con un mensaje de éxito
        header("Location: ../mis_pedidos.php?success=1&message=" . urlencode("Pedido cancelado correctamente. Los productos han sido devueltos al catálogo."));
        exit;
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        error_log("cancelar_pedido.php - Error en la transacción: " . $e->getMessage());

        // Redirigir a la página de mis pedidos con un mensaje de error
        header("Location: ../mis_pedidos.php?error=1&message=" . urlencode("Error al cancelar el pedido: " . $e->getMessage()));
        exit;
    }
} else {
    // Si no se ha enviado el formulario, redirigir a la página de mis pedidos
    error_log("cancelar_pedido.php - Método no permitido: " . $_SERVER["REQUEST_METHOD"]);
    header("Location: ../mis_pedidos.php?error=1&message=" . urlencode("Método no permitido."));
    exit;
}
?>
