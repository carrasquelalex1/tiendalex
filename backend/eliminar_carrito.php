<?php
/**
 * Script para eliminar un producto del carrito
 */

// Incluir archivos necesarios
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/../helpers/session/session_helper.php';
require_once __DIR__ . '/../autoload.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Registrar información de depuración
error_log("eliminar_carrito.php - SESSION al inicio: " . print_r($_SESSION, true));

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    header("Location: ../index.php?error=1&message=" . urlencode("Debe iniciar sesión para acceder a esta página."));
    exit;
}

// Verificar si el usuario es administrador (no puede usar el carrito)
if (es_admin()) {
    header("Location: ../index.php?error=1&message=" . urlencode("Los administradores no pueden realizar compras."));
    exit;
}

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $id = isset($_POST['id']) && is_numeric($_POST['id']) ? intval($_POST['id']) : 0;

    // Registrar información de depuración
    error_log("eliminar_carrito.php - Eliminando producto ID: " . $id);

    // Validar los datos
    if ($id <= 0) {
        error_log("eliminar_carrito.php - ID de producto no válido: " . $id);
        header("Location: ../carrito.php?error=1&message=" . urlencode("ID de producto no válido."));
        exit;
    }

    // Obtener el ID del usuario
    $usuario_id = $_SESSION['usuario_logueado'];

    // Verificar que el producto pertenezca al usuario
    $sql = "SELECT * FROM carrito WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        error_log("eliminar_carrito.php - El producto no pertenece al usuario: usuario_id=" . $usuario_id . ", producto_id=" . $id);
        header("Location: ../carrito.php?error=1&message=" . urlencode("El producto no pertenece a tu carrito."));
        exit;
    }

    // Obtener la información del producto en el carrito
    $producto_carrito = $result->fetch_assoc();
    $producto_id = $producto_carrito['producto_id'];
    $cantidad = $producto_carrito['cantidad'];

    error_log("eliminar_carrito.php - Información del producto: producto_id=" . $producto_id . ", cantidad=" . $cantidad);

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Eliminar el producto del carrito
        $sql = "DELETE FROM carrito WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception("Error al eliminar el producto del carrito: " . $stmt->error);
        }

        $filas_afectadas = $stmt->affected_rows;
        error_log("eliminar_carrito.php - Filas afectadas al eliminar: " . $filas_afectadas);

        // Devolver la cantidad a la existencia del producto
        $sql = "UPDATE productos_tienda SET existencia_producto = existencia_producto + ? WHERE id_producto = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $cantidad, $producto_id);

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar la existencia del producto: " . $stmt->error);
        }

        $filas_actualizadas = $stmt->affected_rows;
        error_log("eliminar_carrito.php - Filas afectadas al actualizar existencia: " . $filas_actualizadas);

        // Confirmar transacción
        $conn->commit();

        // Redirigir a la página de carrito con un mensaje de éxito
        header("Location: ../carrito.php?success=1&message=" . urlencode("Producto eliminado del carrito correctamente."));
        exit;
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();

        error_log("eliminar_carrito.php - Error: " . $e->getMessage());

        // Redirigir a la página de carrito con un mensaje de error
        header("Location: ../carrito.php?error=1&message=" . urlencode("Error al eliminar el producto del carrito: " . $e->getMessage()));
        exit;
    }
} else {
    // Si no se ha enviado el formulario, redirigir a la página de carrito
    header("Location: ../carrito.php");
    exit;
}
?>
