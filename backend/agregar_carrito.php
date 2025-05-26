<?php
/**
 * Script para agregar productos al carrito
 */

// Incluir archivos necesarios primero para poder usar las funciones
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/../helpers/session/session_helper.php';
require_once __DIR__ . '/../autoload.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Registrar información de depuración
error_log("agregar_carrito.php - SESSION al inicio: " . print_r($_SESSION, true));

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    // Guardar información de depuración
    error_log("Error en agregar_carrito.php: Usuario no logueado. SESSION: " . print_r($_SESSION, true));
    header("Location: ../catalogo.php?error=1&message=" . urlencode("Debe iniciar sesión para agregar productos al carrito."));
    exit;
}

// Obtener el ID del usuario
$usuario_id = $_SESSION['usuario_logueado'];

// Verificar si el usuario es administrador (no puede agregar al carrito)
if (es_admin()) {
    // Guardar información de depuración
    error_log("Error en agregar_carrito.php: Usuario es administrador. rol_codigo: " . $_SESSION['rol_codigo']);
    header("Location: ../catalogo.php?error=1&message=" . urlencode("Los administradores no pueden realizar compras. Inicie sesión como cliente."));
    exit;
}

// Verificar si el usuario es cliente
if (!es_cliente()) {
    error_log("Error en agregar_carrito.php: Usuario no es cliente. rol_codigo: " . (isset($_SESSION['rol_codigo']) ? $_SESSION['rol_codigo'] : 'no definido'));
    header("Location: ../catalogo.php?error=1&message=" . urlencode("Su rol no le permite realizar compras. Por favor, contacte al administrador."));
    exit;
}

// Verificar que el usuario tenga un código de pedido
if (!isset($_SESSION['codigo_pedido'])) {
    $_SESSION['codigo_pedido'] = generarCodigoPedidoUnico();
    error_log("Información en agregar_carrito.php: Se generó un nuevo código de pedido: " . $_SESSION['codigo_pedido']);
}

// Registrar información de depuración
error_log("Información en agregar_carrito.php: Usuario logueado con rol_codigo: " . $_SESSION['rol_codigo'] . ", usuario_id: " . $_SESSION['usuario_logueado'] . ", codigo_pedido: " . $_SESSION['codigo_pedido']);

// Forzar la escritura de la sesión para asegurar que los cambios se guarden
session_write_close();
session_start();

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $producto_id = isset($_POST['producto_id']) && is_numeric($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;
    $cantidad = isset($_POST['cantidad']) && is_numeric($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;
    $codigo_pedido = isset($_POST['codigo_pedido']) ? $_POST['codigo_pedido'] : '';

    // Validar los datos
    if ($producto_id <= 0) {
        header("Location: ../catalogo.php?error=1&message=" . urlencode("ID de producto no válido."));
        exit;
    }

    if ($cantidad <= 0) {
        header("Location: ../catalogo.php?error=1&message=" . urlencode("Cantidad no válida."));
        exit;
    }

    if (empty($codigo_pedido)) {
        // Si no hay código de pedido en el formulario, usar el de la sesión
        if (isset($_SESSION['codigo_pedido'])) {
            $codigo_pedido = $_SESSION['codigo_pedido'];
            error_log("Información en agregar_carrito.php: Usando código de pedido de la sesión: " . $codigo_pedido);
        } else {
            // Si no hay código de pedido en la sesión, generar uno nuevo
            $codigo_pedido = generarCodigoPedidoUnico();
            $_SESSION['codigo_pedido'] = $codigo_pedido;
            error_log("Información en agregar_carrito.php: Se generó un nuevo código de pedido: " . $codigo_pedido);
        }
    } else {
        // Verificar si el código de pedido del formulario coincide con el de la sesión
        if (isset($_SESSION['codigo_pedido']) && $codigo_pedido != $_SESSION['codigo_pedido']) {
            error_log("Información en agregar_carrito.php: El código de pedido del formulario ($codigo_pedido) no coincide con el de la sesión (" . $_SESSION['codigo_pedido'] . "). Se usará el de la sesión.");
            $codigo_pedido = $_SESSION['codigo_pedido'];
        }
    }

    // Obtener el ID del usuario
    $usuario_id = $_SESSION['usuario_logueado'];

    // Agregar el producto al carrito
    $resultado = Carrito::agregarProductoCarrito($usuario_id, $producto_id, $cantidad, $codigo_pedido, $conn);

    if ($resultado) {
        // Verificar si se debe redirigir al carrito o al catálogo
        $redirect_to_cart = isset($_POST['redirect_to_cart']) ? $_POST['redirect_to_cart'] : 'false';

        if ($redirect_to_cart === 'true') {
            // Redirigir al carrito con un mensaje de éxito
            header("Location: ../carrito.php?success=1&message=" . urlencode("Producto agregado al carrito correctamente."));
        } else {
            // Redirigir a la página de catálogo con un mensaje de éxito (comportamiento original)
            header("Location: ../catalogo.php?success=1&message=" . urlencode("Producto agregado al carrito correctamente."));
        }
        exit;
    } else {
        // Redirigir a la página de catálogo con un mensaje de error
        header("Location: ../catalogo.php?error=1&message=" . urlencode("Error al agregar el producto al carrito. Puede que no haya suficiente existencia."));
        exit;
    }
} else {
    // Si no se ha enviado el formulario, redirigir a la página de catálogo
    header("Location: ../catalogo.php");
    exit;
}
?>
