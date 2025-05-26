<?php
/**
 * Obtener detalles del pedido para el administrador
 */

// Incluir el helper de sesiones
require_once __DIR__ . '/../../helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado y es administrador
if (!esta_logueado() || !es_admin()) {
    echo '<p class="red-text">No tienes permisos para acceder a esta información.</p>';
    exit;
}

// Verificar si se recibieron los parámetros necesarios
if (!isset($_POST['pedido_codigo']) || !isset($_POST['usuario_id'])) {
    echo '<p class="red-text">Parámetros insuficientes.</p>';
    exit;
}

// Incluir la conexión a la base de datos
require_once __DIR__ . '/../config/db.php';

// Obtener los parámetros
$pedido_codigo = $_POST['pedido_codigo'];
$usuario_id = $_POST['usuario_id'];
$estado = isset($_POST['estado']) ? $_POST['estado'] : 'pendiente';

// Obtener información del usuario
$sql_usuario = "SELECT * FROM usuario WHERE codigo = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$usuario = $result_usuario->fetch_assoc();

// Verificar si el pedido está en la tabla pedido
$sql_check_pedido = "SELECT COUNT(*) as count FROM pedido WHERE pedido = ? AND usuario_id = ?";
$stmt_check_pedido = $conn->prepare($sql_check_pedido);
$stmt_check_pedido->bind_param("si", $pedido_codigo, $usuario_id);
$stmt_check_pedido->execute();
$result_check_pedido = $stmt_check_pedido->get_result();
$row_check_pedido = $result_check_pedido->fetch_assoc();

// Verificar si el pedido está en la tabla pedidos_finalizados
$sql_check_finalizados = "SELECT COUNT(*) as count FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ?";
$stmt_check_finalizados = $conn->prepare($sql_check_finalizados);
$stmt_check_finalizados->bind_param("si", $pedido_codigo, $usuario_id);
$stmt_check_finalizados->execute();
$result_check_finalizados = $stmt_check_finalizados->get_result();
$row_check_finalizados = $result_check_finalizados->fetch_assoc();

// Obtener la tasa de cambio actual para mostrar precios actualizados en Bs. para pedidos pendientes
$sql_tasa = "SELECT valor_tasa FROM tasa ORDER BY id_tasa DESC LIMIT 1";
$result_tasa = $conn->query($sql_tasa);
$tasa_actual = $result_tasa ? floatval($result_tasa->fetch_assoc()['valor_tasa']) : 0;

// Calcular totales
$total_dolares = 0;
$total_bolivares = 0;
$productos = [];

// Verificar si el pedido está en la tabla pedidos_finalizados con estado 'pendiente'
// Esto significa que es un pedido devuelto
$sql_check_devuelto = "SELECT COUNT(*) as count FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ? AND estado = 'pendiente'";
$stmt_check_devuelto = $conn->prepare($sql_check_devuelto);
$stmt_check_devuelto->bind_param("si", $pedido_codigo, $usuario_id);
$stmt_check_devuelto->execute();
$result_check_devuelto = $stmt_check_devuelto->get_result();
$row_check_devuelto = $result_check_devuelto->fetch_assoc();
$es_pedido_devuelto = ($row_check_devuelto['count'] > 0);

// Registrar para depuración
error_log("obtener_detalles_pedido.php - Pedido: $pedido_codigo, Usuario: $usuario_id, Es devuelto: " . ($es_pedido_devuelto ? 'Sí' : 'No'));

// Si es un pedido devuelto, obtener productos solo de pedidos_finalizados
if ($es_pedido_devuelto) {
    $sql_productos_finalizados = "SELECT
                                    pf.id,
                                    pf.producto_id,
                                    pf.pedido_codigo,
                                    pf.usuario_id,
                                    pf.precio_dolares,
                                    pf.precio_bolivares,
                                    pf.cantidad,
                                    'pendiente' as estado,
                                    pf.fecha_pedido,
                                    pt.nombre_producto,
                                    pt.imagen_producto,
                                    pt.descripcion_producto
                                 FROM pedidos_finalizados pf
                                 LEFT JOIN productos_tienda pt ON pf.producto_id = pt.id_producto
                                 WHERE pf.pedido_codigo = ?
                                   AND pf.usuario_id = ?
                                   AND pf.id IN (
                                       SELECT MAX(id)
                                       FROM pedidos_finalizados
                                       WHERE pedido_codigo = ?
                                         AND usuario_id = ?
                                       GROUP BY producto_id
                                   )";
    $stmt_productos_finalizados = $conn->prepare($sql_productos_finalizados);
    $stmt_productos_finalizados->bind_param("sisi", $pedido_codigo, $usuario_id, $pedido_codigo, $usuario_id);
    $stmt_productos_finalizados->execute();
    $result_productos_finalizados = $stmt_productos_finalizados->get_result();

    // Registrar para depuración
    error_log("obtener_detalles_pedido.php - Consulta de productos devueltos para pedido: " . $pedido_codigo . " - Resultados: " . $result_productos_finalizados->num_rows);

    while ($producto = $result_productos_finalizados->fetch_assoc()) {
        // Registrar información de depuración sobre las imágenes
        error_log("obtener_detalles_pedido.php (devuelto) - Producto ID: " . $producto['producto_id'] . ", Imagen: " . $producto['imagen_producto']);

        // Renombrar campos para que coincidan con la estructura de la tabla pedido
        if (!isset($producto['pedido'])) {
            $producto['pedido'] = $producto['pedido_codigo'];
        }

        // Recalcular precio en Bs. si el pedido está pendiente
        if ($tasa_actual > 0) {
            $producto['precio_bolivares'] = $producto['precio_dolares'] * $tasa_actual;
        }

        $productos[] = $producto;
        $total_dolares += $producto['precio_dolares'] * $producto['cantidad'];
        $total_bolivares += $producto['precio_bolivares'] * $producto['cantidad'];
    }
}
// Si no es un pedido devuelto, verificar en la tabla pedido primero
else if ($row_check_pedido['count'] > 0) {
    $sql_productos = "SELECT
                        p.producto_id,
                        p.pedido,
                        p.usuario_id,
                        p.precio_dolares,
                        p.precio_bolivares,
                        MAX(p.cantidad) as cantidad,
                        p.created_at,
                        pt.nombre_producto,
                        pt.imagen_producto
                     FROM pedido p
                     LEFT JOIN productos_tienda pt ON p.producto_id = pt.id_producto
                     WHERE p.pedido = ? AND p.usuario_id = ?
                     GROUP BY p.producto_id, p.pedido, p.usuario_id, p.precio_dolares,
                              p.precio_bolivares, p.created_at, pt.nombre_producto, pt.imagen_producto";
    $stmt_productos = $conn->prepare($sql_productos);
    $stmt_productos->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_productos->execute();
    $result_productos = $stmt_productos->get_result();

    while ($producto = $result_productos->fetch_assoc()) {
        // Registrar información de depuración sobre las imágenes
        error_log("obtener_detalles_pedido.php (pedido) - Producto ID: " . $producto['producto_id'] . ", Imagen: " . $producto['imagen_producto']);

        // Recalcular precio en Bs. si el pedido está pendiente
        if ($tasa_actual > 0) {
            $producto['precio_bolivares'] = $producto['precio_dolares'] * $tasa_actual;
        }

        $productos[] = $producto;
        $total_dolares += $producto['precio_dolares'] * $producto['cantidad'];
        $total_bolivares += $producto['precio_bolivares'] * $producto['cantidad'];
    }
}
// Si no está en la tabla pedido, buscar en pedidos_finalizados (no devueltos)
else if ($row_check_finalizados['count'] > 0 && !$es_pedido_devuelto) {
    $sql_productos_finalizados = "SELECT
                                    pf.producto_id,
                                    pf.pedido_codigo,
                                    pf.usuario_id,
                                    pf.precio_dolares,
                                    pf.precio_bolivares,
                                    MAX(pf.cantidad) as cantidad,
                                    pf.estado,
                                    pf.fecha_pedido,
                                    pt.nombre_producto,
                                    pt.imagen_producto
                                 FROM pedidos_finalizados pf
                                 LEFT JOIN productos_tienda pt ON pf.producto_id = pt.id_producto
                                 WHERE pf.pedido_codigo = ? AND pf.usuario_id = ? AND (pf.estado != 'pendiente' OR pf.estado IS NULL)
                                 GROUP BY pf.producto_id, pf.pedido_codigo, pf.usuario_id, pf.precio_dolares,
                                          pf.precio_bolivares, pf.estado, pf.fecha_pedido, pt.nombre_producto, pt.imagen_producto";
    $stmt_productos_finalizados = $conn->prepare($sql_productos_finalizados);
    $stmt_productos_finalizados->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_productos_finalizados->execute();
    $result_productos_finalizados = $stmt_productos_finalizados->get_result();

    while ($producto = $result_productos_finalizados->fetch_assoc()) {
        // Registrar información de depuración sobre las imágenes
        error_log("obtener_detalles_pedido.php (finalizados) - Producto ID: " . $producto['producto_id'] . ", Imagen: " . $producto['imagen_producto']);

        // Renombrar campos para que coincidan con la estructura de la tabla pedido
        if (!isset($producto['pedido'])) {
            $producto['pedido'] = $producto['pedido_codigo'];
        }

        // Recalcular precio en Bs. si el pedido está pendiente
        if ($tasa_actual > 0) {
            $producto['precio_bolivares'] = $producto['precio_dolares'] * $tasa_actual;
        }

        $productos[] = $producto;
        $total_dolares += $producto['precio_dolares'] * $producto['cantidad'];
        $total_bolivares += $producto['precio_bolivares'] * $producto['cantidad'];
    }
}

// Generar HTML con los detalles
?>

<div class="row">
    <div class="col s12">
        <div class="card-panel">
            <h5>Información del Cliente</h5>
            <div class="divider"></div>
            <div class="row">
                <div class="col s12 m6">
                    <p><strong>Usuario:</strong> <?php echo $usuario['nombre_usuario']; ?></p>
                    <p><strong>Correo:</strong> <?php echo $usuario['correo_electronico']; ?></p>
                </div>
                <div class="col s12 m6">
                    <p><strong>Fecha del pedido:</strong> <?php echo isset($productos[0]['fecha']) ? date('d/m/Y H:i', strtotime($productos[0]['fecha'])) : 'No disponible'; ?></p>
                    <p><strong>Estado:</strong> <span class="chip <?php echo $estado == 'pendiente' ? 'orange' : 'green'; ?> white-text"><?php echo ucfirst($estado); ?></span></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col s12">
        <div class="card-panel">
            <h5>Productos del Pedido</h5>
            <div class="divider"></div>

            <?php if (empty($productos)): ?>
                <p class="center-align">No hay productos en este pedido.</p>
            <?php else: ?>
                <table class="striped responsive-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td>
                                    <div class="valign-wrapper">
                                        <img src="<?php echo $producto['imagen_producto'] ?: 'images/no-image.jpg'; ?>" alt="<?php echo $producto['nombre_producto']; ?>" class="circle" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                                        <?php echo $producto['nombre_producto']; ?>
                                    </div>
                                </td>
                                <td>
                                    $<?php echo number_format($producto['precio_dolares'], 2); ?><br>
                                    <small>Bs. <?php echo number_format($producto['precio_bolivares'], 2); ?></small>
                                </td>
                                <td><?php echo $producto['cantidad']; ?></td>
                                <td>
                                    $<?php echo number_format($producto['precio_dolares'] * $producto['cantidad'], 2); ?><br>
                                    <small>Bs. <?php echo number_format($producto['precio_bolivares'] * $producto['cantidad'], 2); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="right-align">Total:</th>
                            <th>
                                $<?php echo number_format($total_dolares, 2); ?><br>
                                <small>Bs. <?php echo number_format($total_bolivares, 2); ?></small>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
