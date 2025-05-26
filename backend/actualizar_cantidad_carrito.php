<?php
/**
 * Script para actualizar la cantidad de un producto en el carrito
 */

// Incluir archivos necesarios
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/../helpers/session/session_helper.php';
require_once __DIR__ . '/../autoload.php';

// Iniciar sesión de manera segura (debe ser lo primero)
iniciar_sesion_segura();

// Registrar información de depuración
error_log("actualizar_cantidad_carrito.php - SESSION al inicio: " . print_r($_SESSION, true));
error_log("actualizar_cantidad_carrito.php - POST data: " . print_r($_POST, true));

// Verificar si el usuario está logueado y la variable de sesión existe
if (!esta_logueado() || !isset($_SESSION['usuario_logueado']) || !is_numeric($_SESSION['usuario_logueado'])) {
    $response = [
        'success' => false,
        'message' => 'Debe iniciar sesión para actualizar el carrito.'
    ];
    echo json_encode($response);
    exit;
}

// Verificar si el usuario es administrador (no puede usar el carrito)
if (es_admin()) {
    $response = [
        'success' => false,
        'message' => 'Los administradores no pueden realizar compras.'
    ];
    echo json_encode($response);
    exit;
}

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $id = isset($_POST['id']) && is_numeric($_POST['id']) ? intval($_POST['id']) : 0;
    $cantidad = isset($_POST['cantidad']) && is_numeric($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;

    // Validar los datos
    if ($id <= 0) {
        $response = [
            'success' => false,
            'message' => 'ID de producto no válido.'
        ];
        echo json_encode($response);
        exit;
    }

    if ($cantidad <= 0) {
        $response = [
            'success' => false,
            'message' => 'Cantidad no válida.'
        ];
        echo json_encode($response);
        exit;
    }

    // Obtener el ID del usuario
    $usuario_id = intval($_SESSION['usuario_logueado']);

    // Registrar información de depuración
    error_log("actualizar_cantidad_carrito.php - Actualizando cantidad: id=$id, cantidad=$cantidad, usuario_id=$usuario_id");

    // Actualizar la cantidad
    $resultado = Carrito::actualizarCantidadCarrito($id, $cantidad, $usuario_id, $conn);

    if ($resultado === true) {
        // Obtener el nuevo total
        $sql = "SELECT
                    SUM(c.cantidad * c.precio_dolares) as total_dolares,
                    SUM(c.cantidad * c.precio_bolivares) as total_bolivares,
                    COUNT(c.id) as num_productos,
                    SUM(c.cantidad) as total_items
                FROM carrito c
                WHERE c.usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $totales = $result->fetch_assoc();

        // Registrar información de depuración
        error_log("actualizar_cantidad_carrito.php - Totales: " . print_r($totales, true));

        // Obtener el subtotal del producto actualizado
        $sql = "SELECT
                    (cantidad * precio_dolares) as subtotal_dolares,
                    (cantidad * precio_bolivares) as subtotal_bolivares,
                    cantidad
                FROM carrito
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $producto = $result->fetch_assoc();

            // Registrar información de depuración
            error_log("actualizar_cantidad_carrito.php - Producto actualizado: " . print_r($producto, true));

            $response = [
                'success' => true,
                'message' => 'Cantidad actualizada correctamente.',
                'totales' => [
                    'total_dolares' => number_format($totales['total_dolares'], 2),
                    'total_bolivares' => number_format($totales['total_bolivares'], 2),
                    'num_productos' => $totales['num_productos'],
                    'total_items' => $totales['total_items']
                ],
                'producto' => [
                    'subtotal_dolares' => number_format($producto['subtotal_dolares'], 2),
                    'subtotal_bolivares' => number_format($producto['subtotal_bolivares'], 2),
                    'cantidad' => $producto['cantidad']
                ]
            ];
        } else {
            // El producto ya no existe en el carrito (podría haber sido eliminado)
            error_log("actualizar_cantidad_carrito.php - Producto no encontrado en el carrito después de actualizar");

            $response = [
                'success' => true,
                'message' => 'Producto eliminado del carrito.',
                'totales' => [
                    'total_dolares' => number_format($totales['total_dolares'], 2),
                    'total_bolivares' => number_format($totales['total_bolivares'], 2),
                    'num_productos' => $totales['num_productos'],
                    'total_items' => $totales['total_items']
                ],
                'producto' => [
                    'subtotal_dolares' => '0.00',
                    'subtotal_bolivares' => '0.00',
                    'cantidad' => 0
                ],
                'removed' => true
            ];
        }

        echo json_encode($response);
        exit;
    } else {
        // Error al actualizar
        $response = [
            'success' => false,
            'message' => is_string($resultado) ? $resultado : 'Error al actualizar la cantidad.'
        ];
        echo json_encode($response);
        exit;
    }
} else {
    // Si no se ha enviado el formulario, devolver error
    $response = [
        'success' => false,
        'message' => 'Método no permitido.'
    ];
    echo json_encode($response);
    exit;
}
?>
