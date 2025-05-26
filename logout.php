<?php
session_start();

// Incluir archivos necesarios
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/backend/config/db.php';
require_once __DIR__ . '/helpers/carrito/carrito_functions.php';

// Verificar si el usuario tiene productos en el carrito y limpiarlos
if (isset($_SESSION['usuario_logueado']) && (!isset($_SESSION['rol_codigo']) || $_SESSION['rol_codigo'] != 1)) {
    $usuario_id = $_SESSION['usuario_logueado'];
    limpiarCarrito($usuario_id);
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la sesión completamente, borrar también la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// Redirigir a la página de inicio
header("Location: index.php");
exit();
?>
