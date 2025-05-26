<?php
/**
 * Script para vaciar el carrito
 */

// Incluir archivos necesarios
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/../helpers/session/session_helper.php';
require_once __DIR__ . '/../autoload.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Registrar información de depuración
error_log("vaciar_carrito.php - SESSION al inicio: " . print_r($_SESSION, true));

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
    // Obtener el ID del usuario
    $usuario_id = $_SESSION['usuario_logueado'];

    // Limpiar el carrito
    $resultado = Carrito::limpiarCarrito($usuario_id, $conn);
    if ($resultado) {
        // Generar un nuevo código de pedido
        $_SESSION['codigo_pedido'] = Carrito::generarCodigoPedidoUnico($conn);

        // Redirigir a la página de carrito con un mensaje de éxito
        header("Location: ../carrito.php?success=1&message=" . urlencode("Carrito vaciado correctamente."));
        exit;
    } else {
        // Redirigir a la página de carrito con un mensaje de error
        header("Location: ../carrito.php?error=1&message=" . urlencode("Error al vaciar el carrito."));
        exit;
    }
} else {
    // Si no se ha enviado el formulario, redirigir a la página de carrito
    header("Location: ../carrito.php");
    exit;
}
?>
