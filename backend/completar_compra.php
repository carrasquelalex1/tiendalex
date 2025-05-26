<?php
/**
 * Script para completar la compra
 */

// Incluir archivos necesarios
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/../helpers/session/session_helper.php';
require_once __DIR__ . '/../helpers/carrito/carrito_functions.php';
require_once __DIR__ . '/../autoload.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Registrar información de depuración
error_log("completar_compra.php - SESSION al inicio: " . print_r($_SESSION, true));

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    error_log("completar_compra.php - Usuario no logueado");
    header("Location: ../index.php?error=1&message=" . urlencode("Debe iniciar sesión para completar la compra."));
    exit;
}

// Verificar si el usuario es administrador (no puede usar el carrito)
if (es_admin()) {
    error_log("completar_compra.php - Usuario es administrador");
    header("Location: ../index.php?error=1&message=" . urlencode("Los administradores no pueden realizar compras."));
    exit;
}

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener el ID del usuario
    $usuario_id = $_SESSION['usuario_logueado'];

    // Verificar si hay productos en el carrito
    $sql = "SELECT COUNT(*) as count FROM carrito WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] == 0) {
        error_log("completar_compra.php - No hay productos en el carrito para el usuario ID: " . $usuario_id);
        header("Location: ../carrito.php?error=1&message=" . urlencode("No hay productos en el carrito."));
        exit;
    }

    // Verificar si hay un código de pedido
    if (!isset($_SESSION['codigo_pedido']) || empty($_SESSION['codigo_pedido'])) {
        error_log("completar_compra.php - No hay código de pedido en la sesión");
        $_SESSION['codigo_pedido'] = generarCodigoPedidoUnico();
        error_log("completar_compra.php - Se generó un nuevo código de pedido: " . $_SESSION['codigo_pedido']);
    }

    // Obtener el código de pedido actual
    $codigo_pedido_actual = $_SESSION['codigo_pedido'];
    error_log("completar_compra.php - Código de pedido actual: " . $codigo_pedido_actual);

    // Completar la compra
    error_log("completar_compra.php - Iniciando completarCompra para usuario ID: " . $usuario_id);
    $resultado = completarCompra($usuario_id);

    if ($resultado) {
        error_log("completar_compra.php - Compra completada con éxito");

        // Guardar el código del pedido completado en una variable de sesión
        $_SESSION['ultimo_pedido_completado'] = $codigo_pedido_actual;
        error_log("completar_compra.php - Código de pedido completado: " . $codigo_pedido_actual);

        // Generar un nuevo código de pedido para futuras compras
        $_SESSION['codigo_pedido'] = generarCodigoPedidoUnico();
        error_log("completar_compra.php - Nuevo código de pedido generado: " . $_SESSION['codigo_pedido']);

        // Agregar mensaje de éxito a la sesión
        $_SESSION['compra_completada'] = true;
        $_SESSION['mensaje_compra'] = "¡Pedido completado con éxito! Tu código de pedido es: " . $codigo_pedido_actual;
        error_log("completar_compra.php - Mensaje de éxito agregado a la sesión");

        // Forzar la escritura de la sesión
        session_write_close();
        session_start();

        // Redirigir a la página de confirmación de pedido
        header("Location: ../confirmacion_pedido.php");
        exit;
    } else {
        error_log("completar_compra.php - Error al completar la compra");
        // Redirigir a la página de carrito con un mensaje de error
        header("Location: ../carrito.php?error=1&message=" . urlencode("Error al completar la compra. Por favor, inténtalo de nuevo."));
        exit;
    }
} else {
    error_log("completar_compra.php - Método no permitido: " . $_SERVER["REQUEST_METHOD"]);
    // Si no se ha enviado el formulario, redirigir a la página de carrito
    header("Location: ../carrito.php");
    exit;
}
?>
