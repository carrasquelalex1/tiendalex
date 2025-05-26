<?php
/**
 * Obtener detalles del pago para el administrador
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

// Obtener información del usuario
$sql_usuario = "SELECT * FROM usuario WHERE codigo = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$usuario = $result_usuario->fetch_assoc();

// Obtener detalles de los pagos
$sql_pagos = "SELECT * FROM pagos WHERE pedido_codigo = ? AND usuario_codigo = ? ORDER BY parte_pago ASC";
$stmt_pagos = $conn->prepare($sql_pagos);
$stmt_pagos->bind_param("si", $pedido_codigo, $usuario_id);
$stmt_pagos->execute();
$result_pagos = $stmt_pagos->get_result();

// Obtener datos de envío
$sql_envio = "SELECT * FROM datos_envio WHERE pedido_codigo = ? AND usuario_codigo = ? LIMIT 1";
$stmt_envio = $conn->prepare($sql_envio);
$stmt_envio->bind_param("si", $pedido_codigo, $usuario_id);
$stmt_envio->execute();
$result_envio = $stmt_envio->get_result();
$datos_envio = $result_envio->num_rows > 0 ? $result_envio->fetch_assoc() : null;

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
error_log("obtener_detalles_pago.php - Pedido: $pedido_codigo, Usuario: $usuario_id, Es devuelto: " . ($es_pedido_devuelto ? 'Sí' : 'No'));

// Calcular totales
$total_dolares = 0;
$total_bolivares = 0;
$productos = [];

// Si es un pedido devuelto, obtener productos solo de pedidos_finalizados con estado 'pendiente'
if ($es_pedido_devuelto) {
    $sql_productos = "SELECT
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
    $stmt_productos = $conn->prepare($sql_productos);
    $stmt_productos->bind_param("sisi", $pedido_codigo, $usuario_id, $pedido_codigo, $usuario_id);
    $stmt_productos->execute();
    $result_productos = $stmt_productos->get_result();

    // Registrar para depuración
    error_log("obtener_detalles_pago.php - Consulta de productos devueltos para pedido: " . $pedido_codigo . " - Resultados: " . $result_productos->num_rows);
}
// Verificar si el pedido está en la tabla pedido
else {
    $sql_check_pedido = "SELECT COUNT(*) as count FROM pedido WHERE pedido = ? AND usuario_id = ?";
    $stmt_check_pedido = $conn->prepare($sql_check_pedido);
    $stmt_check_pedido->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_check_pedido->execute();
    $result_check_pedido = $stmt_check_pedido->get_result();
    $row_check_pedido = $result_check_pedido->fetch_assoc();

    if ($row_check_pedido['count'] > 0) {
        // Obtener productos de la tabla pedido
        $sql_productos = "SELECT p.*, pt.nombre_producto, pt.imagen_producto, pt.descripcion_producto
                         FROM pedido p
                         LEFT JOIN productos_tienda pt ON p.producto_id = pt.id_producto
                         WHERE p.pedido = ? AND p.usuario_id = ?";
        $stmt_productos = $conn->prepare($sql_productos);
        $stmt_productos->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_productos->execute();
        $result_productos = $stmt_productos->get_result();
    } else {
        // Obtener productos de la tabla pedidos_finalizados (no devueltos)
        $sql_productos = "SELECT pf.*, pt.nombre_producto, pt.imagen_producto, pt.descripcion_producto
                         FROM pedidos_finalizados pf
                         LEFT JOIN productos_tienda pt ON pf.producto_id = pt.id_producto
                         WHERE pf.pedido_codigo = ?
                           AND pf.usuario_id = ?
                           AND (pf.estado != 'pendiente' OR pf.estado IS NULL)
                           AND pf.id IN (
                               SELECT MAX(id)
                               FROM pedidos_finalizados
                               WHERE pedido_codigo = ?
                                 AND usuario_id = ?
                               GROUP BY producto_id
                           )";
        $stmt_productos = $conn->prepare($sql_productos);
        $stmt_productos->bind_param("sisi", $pedido_codigo, $usuario_id, $pedido_codigo, $usuario_id);
        $stmt_productos->execute();
        $result_productos = $stmt_productos->get_result();
    }
}

// Procesar los productos obtenidos
while ($producto = $result_productos->fetch_assoc()) {
    // Registrar información de depuración sobre las imágenes
    error_log("obtener_detalles_pago.php - Producto ID: " . $producto['producto_id'] . ", Imagen: " . ($producto['imagen_producto'] ?? 'No disponible'));

    // Obtener información adicional del producto si no está disponible
    if (!isset($producto['nombre_producto']) || empty($producto['nombre_producto'])) {
        $sql_producto_info = "SELECT nombre_producto, imagen_producto, descripcion_producto FROM productos_tienda WHERE id_producto = ? LIMIT 1";
        $stmt_producto_info = $conn->prepare($sql_producto_info);
        $stmt_producto_info->bind_param("i", $producto['producto_id']);
        $stmt_producto_info->execute();
        $result_producto_info = $stmt_producto_info->get_result();

        if ($result_producto_info->num_rows > 0) {
            $producto_info = $result_producto_info->fetch_assoc();
            $producto['nombre_producto'] = $producto_info['nombre_producto'];
            $producto['imagen_producto'] = $producto_info['imagen_producto'];
            $producto['descripcion_producto'] = $producto_info['descripcion_producto'];
        } else {
            $producto['nombre_producto'] = 'Producto no disponible';
            $producto['imagen_producto'] = 'images/no-image.jpg';
            $producto['descripcion_producto'] = 'Sin descripción';
        }
    }

    $productos[] = $producto;
    $total_dolares += isset($producto['precio_dolares']) ? $producto['precio_dolares'] * $producto['cantidad'] : 0;
    $total_bolivares += isset($producto['precio_bolivares']) ? $producto['precio_bolivares'] * $producto['cantidad'] : 0;

    // Registrar para depuración
    error_log("obtener_detalles_pago.php - Producto agregado: " . $producto['nombre_producto'] . ", Precio: $" . $producto['precio_dolares']);
}

// Calcular total pagado
$total_pagado = 0;
$pagos = [];
while ($pago = $result_pagos->fetch_assoc()) {
    $pagos[] = $pago;
    $total_pagado += $pago['monto'];
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
                    <p><strong>Fecha del pedido:</strong> <?php echo isset($pagos[0]['fecha_registro']) ? date('d/m/Y H:i', strtotime($pagos[0]['fecha_registro'])) : 'No disponible'; ?></p>
                    <p><strong>Estado:</strong>
                        <?php if (!empty($pagos)): ?>
                            <span class="chip <?php echo $pagos[0]['estado'] == 'pendiente' ? 'orange' : 'green'; ?> white-text"><?php echo ucfirst($pagos[0]['estado']); ?></span>
                        <?php else: ?>
                            <span class="chip grey white-text">No disponible</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col s12">
        <div class="card-panel">
            <h5>Detalles del Pago</h5>
            <div class="divider"></div>

            <?php if (empty($pagos)): ?>
                <p class="center-align">No hay información de pago disponible.</p>
            <?php else: ?>
                <table class="striped responsive-table">
                    <thead>
                        <tr>
                            <th>Método</th>
                            <th>Banco</th>
                            <th>Referencia</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $pago): ?>
                            <tr>
                                <td>
                                    <?php
                                    echo $pago['metodo_pago'] == 'transferencia' ? 'Transferencia Bancaria' :
                                        ($pago['metodo_pago'] == 'pago_movil' ? 'Pago Móvil' : 'Efectivo');
                                    ?>
                                </td>
                                <td><?php echo $pago['banco'] ?: 'N/A'; ?></td>
                                <td><?php echo $pago['referencia'] ?: 'N/A'; ?></td>
                                <td>Bs. <?php echo number_format($pago['monto'], 2); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pago['fecha_registro'])); ?></td>
                                <td>
                                    <span class="chip <?php echo $pago['estado'] == 'pendiente' ? 'orange' : 'green'; ?> white-text">
                                        <?php echo ucfirst($pago['estado']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="right-align">Total Pagado:</th>
                            <th colspan="3">Bs. <?php echo number_format($total_pagado, 2); ?></th>
                        </tr>
                    </tfoot>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($datos_envio): ?>
<div class="row">
    <div class="col s12">
        <div class="card-panel">
            <h5>Datos de Envío</h5>
            <div class="divider"></div>

            <div class="row">
                <div class="col s12 m6">
                    <p><strong>Destinatario:</strong> <?php echo isset($datos_envio['destinatario_nombre']) ? $datos_envio['destinatario_nombre'] : 'No especificado'; ?></p>
                    <p><strong>Teléfono:</strong> <?php echo isset($datos_envio['destinatario_telefono']) ? $datos_envio['destinatario_telefono'] : 'No especificado'; ?></p>
                    <p><strong>Dirección:</strong> <?php echo isset($datos_envio['direccion']) ? $datos_envio['direccion'] : 'No especificada'; ?></p>
                </div>
                <div class="col s12 m6">
                    <p><strong>Cédula:</strong> <?php echo isset($datos_envio['destinatario_cedula']) ? $datos_envio['destinatario_cedula'] : 'No especificada'; ?></p>
                    <p><strong>Empresa de Envío:</strong> <?php echo isset($datos_envio['empresa_envio']) ? $datos_envio['empresa_envio'] : 'No especificada'; ?></p>
                </div>
            </div>

            <?php if (!empty($datos_envio['instrucciones_adicionales'])): ?>
                <div class="row">
                    <div class="col s12">
                        <p><strong>Instrucciones Adicionales:</strong></p>
                        <p><?php echo $datos_envio['instrucciones_adicionales']; ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col s12">
        <div class="card-panel">
            <h5>Productos del Pedido</h5>
            <div class="divider"></div>

            <?php if (empty($productos)): ?>
                <p class="center-align">No hay productos disponibles para este pedido.</p>
            <?php else: ?>
                <div class="productos-container">
                    <?php foreach ($productos as $producto): ?>
                        <div class="producto-item card-panel">
                            <div class="row valign-wrapper">
                                <div class="col s12 m3 center-align">
                                    <?php
                                    // Obtener la ruta de la imagen del producto
                                    $ruta_imagen = !empty($producto['imagen_producto']) ? $producto['imagen_producto'] : '';

                                    // Imagen por defecto en base64 (un cuadrado gris simple)
                                    $imagen_default = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAMAAAD04JH5AAAAMFBMVEXMzMyZmZnFxcWqqqqcnJzJycm+vr6xsbGjo6O7u7uwsLCtra23t7fCwsKnp6egoKALTvf3AAABF0lEQVR4nO3X23KDIBRF0QgCXkDt//90qG3TpJOAyKXTWW+fPJIDKgAAAAAAAAAAAAAAAAAAAP6b5Vu5M6Vy52z1i0rnLJf8+0WSG5Jz/v0qyTl/8XqQ5Jw/ew1Jcs7vXkOSnPPnryFJzvnL15Ak5/zNa0iSc/72NSQp5e+9hiSl/MPXkKSUf/wakpTyT19DklL++WtIUso/vIYkpfzTa0hSyj+/hiSl/OtrSFLKv76GJKX822tIUsp/vIYkpfzna0hSyn++hiSl/PdrSFLK/3sNSUr5w2tIUsr/vIYkpfzxNSQp5X9fQ5JS/vwakpTyl9eQpJS/voYkpfztNSQp5e+vIUkp//gakpTyz68hSSn/+hqSBAAAAAAAAAAAAAAAAP7XF1TsCyOI5GRZAAAAAElFTkSuQmCC';

                                    // Depurar la ruta de la imagen
                                    error_log("Ruta de imagen: " . $ruta_imagen);
                                    ?>
                                    <div style="width: 100%; height: 120px; display: flex; justify-content: center; align-items: center; background-color: #f5f5f5; border-radius: 4px; overflow: hidden;">
                                        <?php if (!empty($ruta_imagen)): ?>
                                            <img src="<?php echo htmlspecialchars($ruta_imagen); ?>"
                                                 alt="<?php echo htmlspecialchars($producto['nombre_producto'] ?? 'Producto'); ?>"
                                                 class="responsive-img producto-imagen"
                                                 style="max-height: 100%; max-width: 100%; object-fit: contain;"
                                                 onerror="this.onerror=null; this.src='<?php echo $imagen_default; ?>'">
                                        <?php else: ?>
                                            <img src="<?php echo $imagen_default; ?>"
                                                 alt="Imagen no disponible"
                                                 class="responsive-img producto-imagen"
                                                 style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col s12 m9">
                                    <div class="producto-detalles">
                                        <h6 class="producto-nombre"><?php echo htmlspecialchars($producto['nombre_producto'] ?? 'Producto sin nombre'); ?></h6>

                                        <p class="producto-descripcion">
                                            <?php echo !empty($producto['descripcion_producto']) ? htmlspecialchars($producto['descripcion_producto']) : 'Sin descripción'; ?>
                                        </p>

                                        <div class="row">
                                            <div class="col s12 m4">
                                                <p><strong>Cantidad:</strong> <?php echo $producto['cantidad']; ?></p>
                                            </div>
                                            <div class="col s12 m4">
                                                <p><strong>Precio unitario:</strong> $<?php echo number_format($producto['precio_dolares'] ?? 0, 2); ?></p>
                                            </div>
                                            <div class="col s12 m4">
                                                <p><strong>Subtotal:</strong> $<?php echo number_format(($producto['precio_dolares'] ?? 0) * $producto['cantidad'], 2); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="row">
                    <div class="col s12">
                        <div class="card-panel blue lighten-5">
                            <div class="row">
                                <div class="col s12 m6">
                                    <h6><strong>Resumen del Pedido</strong></h6>
                                    <p><strong>Total en USD:</strong> $<?php echo number_format($total_dolares, 2); ?></p>
                                    <p><strong>Total en Bs:</strong> Bs. <?php echo number_format($total_bolivares, 2); ?></p>
                                </div>
                                <div class="col s12 m6">
                                    <h6><strong>Estado del Pago</strong></h6>
                                    <p><strong>Total Pagado:</strong> Bs. <?php echo number_format($total_pagado, 2); ?></p>
                                    <?php
                                    $saldo_pendiente = $total_bolivares - $total_pagado;
                                    if ($saldo_pendiente > 0):
                                    ?>
                                        <p><strong>Saldo Pendiente:</strong> <span class="red-text">Bs. <?php echo number_format($saldo_pendiente, 2); ?></span></p>
                                    <?php else: ?>
                                        <p><strong>Estado:</strong> <span class="green-text">Pago Completado</span></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>