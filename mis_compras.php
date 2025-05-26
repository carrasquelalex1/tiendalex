<?php
// Usar rutas relativas al archivo actual
$root_path = __DIR__ . '/';
require_once $root_path . 'autoload.php';
require_once $root_path . 'helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    header("Location: index.php");
    exit;
}

// Incluir conexión a la base de datos
require_once $root_path . 'backend/config/db.php';

// Obtener el ID del usuario
$usuario_id = $_SESSION['usuario_logueado'];

// Registrar para depuración
error_log("mis_compras.php - ID de usuario: " . $usuario_id . ", Nombre de usuario: " . $_SESSION['nombre_usuario']);

// Obtener la tasa de cambio actual para mostrar precios actualizados en Bs. para pedidos pendientes
$sql_tasa = "SELECT valor_tasa FROM tasa ORDER BY id_tasa DESC LIMIT 1";
$result_tasa = $conn->query($sql_tasa);
$tasa_actual = $result_tasa ? floatval($result_tasa->fetch_assoc()['valor_tasa']) : 0;

// Verificar si hay mensajes de devolución de pedido para este usuario
$mensajes_devolucion = [];
$tabla_historial_existe = false;
$sql_check_historial = "SHOW TABLES LIKE 'historial_pagos'";
$result_check_historial = $conn->query($sql_check_historial);
if ($result_check_historial && $result_check_historial->num_rows > 0) {
    $tabla_historial_existe = true;
}

// Verificar si existe la tabla pedidos_finalizados
$tabla_finalizados_existe = false;
$sql_check_finalizados = "SHOW TABLES LIKE 'pedidos_finalizados'";
$result_check_finalizados = $conn->query($sql_check_finalizados);
if ($result_check_finalizados && $result_check_finalizados->num_rows > 0) {
    $tabla_finalizados_existe = true;
}

if ($tabla_historial_existe) {
    // Primero, obtener los pedidos que están en estado pendiente en la tabla pedidos_finalizados
    $pedidos_pendientes = [];
    if ($tabla_finalizados_existe) {
        $sql_pendientes = "SELECT DISTINCT pedido_codigo
                          FROM pedidos_finalizados
                          WHERE usuario_id = ? AND estado = 'pendiente'";
        $stmt_pendientes = $conn->prepare($sql_pendientes);
        $stmt_pendientes->bind_param("i", $usuario_id);
        $stmt_pendientes->execute();
        $result_pendientes = $stmt_pendientes->get_result();

        while ($row = $result_pendientes->fetch_assoc()) {
            $pedidos_pendientes[] = $row['pedido_codigo'];
        }
    }

    // Si hay pedidos pendientes, buscar solo los mensajes de devolución para esos pedidos
    if (!empty($pedidos_pendientes)) {
        $placeholders = str_repeat('?,', count($pedidos_pendientes) - 1) . '?';
        $sql_mensajes = "SELECT hp.pedido_codigo, hp.comentarios_admin, hp.fecha_registro
                        FROM historial_pagos hp
                        INNER JOIN (
                            SELECT pedido_codigo, MAX(fecha_registro) as max_fecha
                            FROM historial_pagos
                            WHERE usuario_codigo = ? AND estado = 'devuelto'
                            AND pedido_codigo IN ($placeholders)
                            GROUP BY pedido_codigo
                        ) as ultimos ON hp.pedido_codigo = ultimos.pedido_codigo AND hp.fecha_registro = ultimos.max_fecha
                        WHERE hp.usuario_codigo = ? AND hp.estado = 'devuelto'
                        ORDER BY hp.fecha_registro DESC";

        // Preparar los parámetros para bind_param
        $tipos = 'i' . str_repeat('s', count($pedidos_pendientes)) . 'i'; // 'i' para usuario_id + 's' para cada pedido + 'i' para usuario_id de nuevo

        // Crear un array de referencias para bind_param
        $params = [$tipos, &$usuario_id];
        foreach ($pedidos_pendientes as &$pedido) {
            $params[] = &$pedido;
        }
        $params[] = &$usuario_id;

        $stmt_mensajes = $conn->prepare($sql_mensajes);
        call_user_func_array([$stmt_mensajes, 'bind_param'], $params);
        $stmt_mensajes->execute();
        $result_mensajes = $stmt_mensajes->get_result();

        while ($row = $result_mensajes->fetch_assoc()) {
            $mensajes_devolucion[] = $row;
        }

        // Registrar para depuración
        error_log("mis_compras.php - Mensajes de devolución encontrados (solo pedidos pendientes): " . count($mensajes_devolucion));
        error_log("mis_compras.php - Pedidos pendientes considerados: " . implode(', ', $pedidos_pendientes));
    } else {
        error_log("mis_compras.php - No se encontraron pedidos pendientes para mostrar mensajes de devolución");
    }
}

// Inicializar contadores de estados
$contador_estados = [
    'pendiente' => 0,
    'pagado' => 0,
    'proceso' => 0,
    'enviado' => 0
];

// Obtener los pedidos pendientes (en carrito)
$sql_pendientes = "SELECT DISTINCT pedido, COUNT(*) as total_productos
                  FROM carrito
                  WHERE usuario_id = ? AND pedido NOT IN (SELECT DISTINCT pedido_codigo FROM pagos WHERE usuario_codigo = ?)
                  GROUP BY pedido";
$stmt_pendientes = $conn->prepare($sql_pendientes);
$stmt_pendientes->bind_param("ii", $usuario_id, $usuario_id);
$stmt_pendientes->execute();
$result_pendientes = $stmt_pendientes->get_result();

// Contar pedidos pendientes
$contador_estados['pendiente'] = $result_pendientes->num_rows;

// Registrar para depuración
error_log("mis_compras.php - Pedidos pendientes (en carrito): " . $contador_estados['pendiente']);

// Obtener los pedidos pendientes de la tabla pedido (pedidos generados pero sin pagos)
$sql_pedidos_pendientes = "SELECT DISTINCT pedido, COUNT(*) as total_productos
                          FROM pedido
                          WHERE usuario_id = ? AND pedido NOT IN (SELECT DISTINCT pedido_codigo FROM pagos WHERE usuario_codigo = ?)
                          GROUP BY pedido";
$stmt_pedidos_pendientes = $conn->prepare($sql_pedidos_pendientes);
$stmt_pedidos_pendientes->bind_param("ii", $usuario_id, $usuario_id);
$stmt_pedidos_pendientes->execute();
$result_pedidos_pendientes = $stmt_pedidos_pendientes->get_result();

// Agregar pedidos pendientes de la tabla pedido al contador
$pedidos_pendientes_tabla_pedido = [];
while ($row = $result_pedidos_pendientes->fetch_assoc()) {
    $pedidos_pendientes_tabla_pedido[] = $row['pedido'];
    $contador_estados['pendiente']++;
}

// Registrar para depuración
error_log("mis_compras.php - Pedidos pendientes (en tabla pedido): " . count($pedidos_pendientes_tabla_pedido));

// Obtener los pedidos pendientes de la tabla pedidos_finalizados (pedidos devueltos)
$pedidos_devueltos = [];

if ($tabla_finalizados_existe) {
    $sql_devueltos = "SELECT DISTINCT pedido_codigo,
                    MAX(updated_at) as fecha_actualizacion,
                    'pendiente' as estado_pago
                    FROM pedidos_finalizados
                    WHERE usuario_id = ? AND estado = 'pendiente'
                    GROUP BY pedido_codigo
                    ORDER BY MAX(updated_at) DESC";

    $stmt_devueltos = $conn->prepare($sql_devueltos);
    $stmt_devueltos->bind_param("i", $usuario_id);
    $stmt_devueltos->execute();
    $result_devueltos = $stmt_devueltos->get_result();

    // Verificar si estos pedidos ya están en la tabla pedido
    // para evitar contarlos dos veces
    while ($row = $result_devueltos->fetch_assoc()) {
        // Verificar si este pedido ya está en la lista de pedidos pendientes
        $pedido_existe = false;
        foreach ($pedidos_pendientes_tabla_pedido as $pedido_existente) {
            if ($pedido_existente === $row['pedido_codigo']) {
                $pedido_existe = true;
                break;
            }
        }

        // Solo agregar al contador si no existe ya
        if (!$pedido_existe) {
            $pedidos_devueltos[] = $row['pedido_codigo'];
            $contador_estados['pendiente']++;

            // Registrar para depuración
            error_log("mis_compras.php - Pedido devuelto encontrado (no duplicado): " . $row['pedido_codigo']);
        } else {
            // Si ya existe, solo lo agregamos a la lista pero no incrementamos el contador
            $pedidos_devueltos[] = $row['pedido_codigo'];

            // Registrar para depuración
            error_log("mis_compras.php - Pedido devuelto encontrado (ya contado): " . $row['pedido_codigo']);
        }
    }
}

// Obtener los pedidos pagados del usuario
$sql_pagados = "SELECT DISTINCT p.pedido_codigo,
                MAX(p.fecha_pago) as fecha_pago,
                MAX(p.fecha_registro) as fecha_registro,
                SUM(p.monto) as total_pagado,
                MAX(p.estado) as estado_pago,
                GROUP_CONCAT(DISTINCT p.metodo_pago) as metodos_pago
                FROM pagos p
                WHERE p.usuario_codigo = ?
                GROUP BY p.pedido_codigo
                ORDER BY MAX(p.fecha_registro) DESC";

$stmt_pagados = $conn->prepare($sql_pagados);
$stmt_pagados->bind_param("i", $usuario_id);
$stmt_pagados->execute();
$result_pagados = $stmt_pagados->get_result();

$compras = [];
while ($row = $result_pagados->fetch_assoc()) {
    // Registrar para depuración
    error_log("mis_compras.php - Procesando pedido pagado: " . $row['pedido_codigo'] . ", Estado: " . $row['estado_pago']);

    // Contar pedidos según su estado
    if ($row['estado_pago'] == 'pendiente') {
        $contador_estados['pagado']++;
        // Este pedido tiene pago informado, no debe estar en pendientes
        $contador_estados['pendiente']--;
        if ($contador_estados['pendiente'] < 0) $contador_estados['pendiente'] = 0;
    } elseif ($row['estado_pago'] == 'verificado') {
        // Verificar si la tabla envios existe
        $tabla_envios_existe = false;
        $sql_check_table = "SHOW TABLES LIKE 'envios'";
        $result_check = $conn->query($sql_check_table);
        if ($result_check && $result_check->num_rows > 0) {
            $tabla_envios_existe = true;
        }

        if ($tabla_envios_existe) {
            // Verificar si está en proceso de envío o ya fue enviado
            $sql_envio = "SELECT estado FROM envios WHERE pedido_codigo = ? LIMIT 1";
            $stmt_envio = $conn->prepare($sql_envio);
            $stmt_envio->bind_param("s", $row['pedido_codigo']);
            $stmt_envio->execute();
            $result_envio = $stmt_envio->get_result();

            if ($result_envio->num_rows > 0) {
                $envio = $result_envio->fetch_assoc();
                if ($envio['estado'] == 'enviado' || $envio['estado'] == 'entregado') {
                    $contador_estados['enviado']++;
                    // Guardar el estado del envío en el array de compras para usarlo más tarde
                    $row['estado_envio'] = $envio['estado'];
                } else {
                    $contador_estados['proceso']++;
                    // Guardar el estado del envío en el array de compras para usarlo más tarde
                    $row['estado_envio'] = $envio['estado'];
                }
            } else {
                $contador_estados['proceso']++;
                // No hay registro en la tabla envios
                $row['estado_envio'] = null;
            }
        } else {
            // Si la tabla no existe, asumimos que está en proceso
            $contador_estados['proceso']++;
        }
    }
    $pedido_codigo = $row['pedido_codigo'];

    // Obtener datos de envío
    $sql_envio = "SELECT * FROM datos_envio WHERE pedido_codigo = ? AND usuario_codigo = ? LIMIT 1";
    $stmt_envio = $conn->prepare($sql_envio);
    $stmt_envio->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_envio->execute();
    $result_envio = $stmt_envio->get_result();

    if ($result_envio->num_rows > 0) {
        $row['datos_envio'] = $result_envio->fetch_assoc();
    } else {
        $row['datos_envio'] = null;
    }

    // Obtener detalles de los pagos
    $sql_detalles_pago = "SELECT * FROM pagos WHERE pedido_codigo = ? AND usuario_codigo = ? ORDER BY parte_pago ASC";
    $stmt_detalles_pago = $conn->prepare($sql_detalles_pago);
    $stmt_detalles_pago->bind_param("si", $pedido_codigo, $usuario_id);
    $stmt_detalles_pago->execute();
    $result_detalles_pago = $stmt_detalles_pago->get_result();

    $row['detalles_pago'] = [];
    while ($pago = $result_detalles_pago->fetch_assoc()) {
        $row['detalles_pago'][] = $pago;
    }

    // Obtener productos del pedido
    // Priorizar productos con estado 'pagado' sobre 'pendiente'
    $sql_productos = "SELECT
                        pf.producto_id,
                        pf.pedido_codigo,
                        pf.usuario_id,
                        pf.precio_dolares,
                        pf.precio_bolivares,
                        pf.cantidad,
                        pf.estado,
                        pf.fecha_pedido,
                        pf.updated_at,
                        pt.nombre_producto,
                        pt.imagen_producto
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
    error_log("mis_compras.php - Consulta de productos para pedido: " . $pedido_codigo . " - Resultados: " . $result_productos->num_rows);

    if ($result_productos->num_rows > 0) {
        $row['productos'] = [];
        while ($producto = $result_productos->fetch_assoc()) {
            // Verificar si tenemos la información del producto
            if (!isset($producto['nombre_producto']) || empty($producto['nombre_producto'])) {
                // Si no tenemos la información, obtenerla de la base de datos
                $sql_producto_info = "SELECT nombre_producto, imagen_producto FROM productos_tienda WHERE id_producto = ? LIMIT 1";
                $stmt_producto_info = $conn->prepare($sql_producto_info);
                $stmt_producto_info->bind_param("i", $producto['producto_id']);
                $stmt_producto_info->execute();
                $result_producto_info = $stmt_producto_info->get_result();

                if ($result_producto_info->num_rows > 0) {
                    $producto_info = $result_producto_info->fetch_assoc();
                    $producto['nombre'] = $producto_info['nombre_producto'];
                    $producto['imagen'] = $producto_info['imagen_producto'];
                } else {
                    $producto['nombre'] = 'Producto no disponible';
                    $producto['imagen'] = 'images/no-image.jpg';
                }
            } else {
                // Si ya tenemos la información, usarla directamente
                $producto['nombre'] = $producto['nombre_producto'];
                $producto['imagen'] = $producto['imagen_producto'] ?: 'images/no-image.jpg';
            }

            // Registrar para depuración
            error_log("mis_compras.php - Producto procesado: " . $producto['nombre'] . ", ID: " . $producto['producto_id']);

            $row['productos'][] = $producto;
        }
    } else {
        // Si no hay productos en pedidos_finalizados, buscar en la tabla pedido
        $sql_productos_pendientes = "SELECT p.*, pt.nombre_producto as nombre, pt.imagen_producto as imagen FROM pedido p
                                    LEFT JOIN productos_tienda pt ON p.producto_id = pt.id_producto
                                    WHERE p.pedido = ? AND p.usuario_id = ?";
        $stmt_productos_pendientes = $conn->prepare($sql_productos_pendientes);
        $stmt_productos_pendientes->bind_param("si", $pedido_codigo, $usuario_id);
        $stmt_productos_pendientes->execute();
        $result_productos_pendientes = $stmt_productos_pendientes->get_result();

        $row['productos'] = [];
        while ($producto = $result_productos_pendientes->fetch_assoc()) {
            $row['productos'][] = $producto;
        }
    }

    $compras[] = $row;
}

// Agregar los pedidos pendientes de la tabla pedido a la lista de compras
if (!empty($pedidos_pendientes_tabla_pedido)) {
    foreach ($pedidos_pendientes_tabla_pedido as $pedido_codigo) {
        // Verificar si este pedido ya está en la lista de compras
        $pedido_existe = false;
        foreach ($compras as $compra) {
            if (isset($compra['pedido_codigo']) && $compra['pedido_codigo'] === $pedido_codigo) {
                $pedido_existe = true;
                break;
            }
        }

        // Solo agregar si no existe ya
        if (!$pedido_existe) {
            // Crear un nuevo registro para este pedido
            $pedido_pendiente = [
                'pedido_codigo' => $pedido_codigo,
                'fecha_registro' => date('Y-m-d H:i:s'), // Fecha actual
                'estado_pago' => 'pendiente',
                'detalles_pago' => [],
                'datos_envio' => null
            ];

            // Obtener productos del pedido desde la tabla pedido
            $sql_productos_pendientes = "SELECT
                                         p.producto_id,
                                         p.pedido as pedido_codigo,
                                         p.usuario_id,
                                         p.precio_dolares,
                                         p.precio_bolivares,
                                         p.cantidad,
                                         'pendiente' as estado,
                                         p.fecha_agregado as fecha_pedido,
                                         pt.nombre_producto as nombre,
                                         pt.imagen_producto as imagen
                                       FROM pedido p
                                       LEFT JOIN productos_tienda pt ON p.producto_id = pt.id_producto
                                       WHERE p.pedido = ? AND p.usuario_id = ?";
            $stmt_productos_pendientes = $conn->prepare($sql_productos_pendientes);
            $stmt_productos_pendientes->bind_param("si", $pedido_codigo, $usuario_id);
            $stmt_productos_pendientes->execute();
            $result_productos_pendientes = $stmt_productos_pendientes->get_result();

            // Registrar para depuración
            error_log("mis_compras.php - Consulta de productos pendientes para pedido: " . $pedido_codigo . " - Resultados: " . $result_productos_pendientes->num_rows);

            $pedido_pendiente['productos'] = [];
            $total_dolares = 0;
            $total_bolivares = 0;

            while ($producto = $result_productos_pendientes->fetch_assoc()) {
                if (!isset($producto['nombre']) || empty($producto['nombre'])) {
                    $producto['nombre'] = 'Producto no disponible';
                }
                if (!isset($producto['imagen']) || empty($producto['imagen'])) {
                    $producto['imagen'] = 'images/no-image.jpg';
                }

                $pedido_pendiente['productos'][] = $producto;
                $total_dolares += $producto['precio_dolares'] * $producto['cantidad'];
                $total_bolivares += $producto['precio_bolivares'] * $producto['cantidad'];
            }

            // Agregar totales
            $pedido_pendiente['total_dolares'] = $total_dolares;
            $pedido_pendiente['total_bolivares'] = $total_bolivares;

            // Registrar para depuración la cantidad de productos
            error_log("mis_compras.php - Pedido pendiente: " . $pedido_codigo . " - Cantidad de productos: " . count($pedido_pendiente['productos']));

            // Agregar a la lista de compras
            $compras[] = $pedido_pendiente;

            // Registrar para depuración
            error_log("mis_compras.php - Pedido pendiente agregado a la lista de compras: " . $pedido_codigo);
        }
    }
}

// Agregar los pedidos devueltos a la lista de compras
if (!empty($pedidos_devueltos)) {
    foreach ($pedidos_devueltos as $pedido_codigo) {
        // Verificar si este pedido ya está en la lista de compras
        $pedido_existe = false;
        foreach ($compras as $compra) {
            if ($compra['pedido_codigo'] === $pedido_codigo) {
                $pedido_existe = true;
                break;
            }
        }

        // Solo agregar si no existe ya
        if (!$pedido_existe) {
            // Crear un nuevo registro para este pedido
            $pedido_devuelto = [
                'pedido_codigo' => $pedido_codigo,
                'fecha_registro' => date('Y-m-d H:i:s'), // Fecha actual
                'estado_pago' => 'pendiente',
                'detalles_pago' => [],
                'datos_envio' => null
            ];

            // Obtener productos del pedido desde pedidos_finalizados
            $sql_productos = "SELECT
                                pf.producto_id,
                                pf.pedido_codigo,
                                pf.usuario_id,
                                pf.precio_dolares,
                                pf.precio_bolivares,
                                pf.cantidad,
                                pf.estado,
                                pt.nombre_producto as nombre,
                                pt.imagen_producto as imagen
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
            error_log("mis_compras.php - Consulta de productos devueltos para pedido: " . $pedido_codigo . " - Resultados: " . $result_productos->num_rows);

            $pedido_devuelto['productos'] = [];
            $total_dolares = 0;
            $total_bolivares = 0;

            while ($producto = $result_productos->fetch_assoc()) {
                if (!isset($producto['nombre']) || empty($producto['nombre'])) {
                    $producto['nombre'] = 'Producto no disponible';
                }
                if (!isset($producto['imagen']) || empty($producto['imagen'])) {
                    $producto['imagen'] = 'images/no-image.jpg';
                }

                $pedido_devuelto['productos'][] = $producto;
                $total_dolares += $producto['precio_dolares'] * $producto['cantidad'];
                $total_bolivares += $producto['precio_bolivares'] * $producto['cantidad'];
            }

            // Si no se encontraron productos en pedidos_finalizados, buscar en pedido
            if (empty($pedido_devuelto['productos'])) {
                $sql_productos_pendientes = "SELECT
                                             p.producto_id,
                                             p.pedido,
                                             p.usuario_id,
                                             p.precio_dolares,
                                             p.precio_bolivares,
                                             MAX(p.cantidad) as cantidad,
                                             pt.nombre_producto as nombre,
                                             pt.imagen_producto as imagen
                                           FROM pedido p
                                           LEFT JOIN productos_tienda pt ON p.producto_id = pt.id_producto
                                           WHERE p.pedido = ? AND p.usuario_id = ?
                                           GROUP BY p.producto_id";
                $stmt_productos_pendientes = $conn->prepare($sql_productos_pendientes);
                $stmt_productos_pendientes->bind_param("si", $pedido_codigo, $usuario_id);
                $stmt_productos_pendientes->execute();
                $result_productos_pendientes = $stmt_productos_pendientes->get_result();

                while ($producto = $result_productos_pendientes->fetch_assoc()) {
                    $pedido_devuelto['productos'][] = $producto;
                    $total_dolares += $producto['precio_dolares'] * $producto['cantidad'];
                    $total_bolivares += $producto['precio_bolivares'] * $producto['cantidad'];
                }
            }

            // Agregar totales
            $pedido_devuelto['total_dolares'] = $total_dolares;
            $pedido_devuelto['total_bolivares'] = $total_bolivares;

            // Registrar para depuración la cantidad de productos
            error_log("mis_compras.php - Pedido devuelto: " . $pedido_codigo . " - Cantidad de productos: " . count($pedido_devuelto['productos']));

            // Registrar IDs de productos para verificar duplicados
            $producto_ids = [];
            $producto_detalles = [];
            foreach ($pedido_devuelto['productos'] as $producto) {
                $producto_ids[] = $producto['producto_id'];
                $producto_detalles[] = "ID: " . $producto['producto_id'] . ", Nombre: " . $producto['nombre'] . ", Estado: " . $producto['estado'];
            }
            error_log("mis_compras.php - Pedido devuelto: " . $pedido_codigo . " - IDs de productos: " . implode(', ', $producto_ids));
            error_log("mis_compras.php - Pedido devuelto: " . $pedido_codigo . " - Detalles de productos: " . implode(' | ', $producto_detalles));

            // Agregar a la lista de compras
            $compras[] = $pedido_devuelto;

            // Registrar para depuración
            error_log("mis_compras.php - Pedido devuelto agregado a la lista de compras: " . $pedido_codigo);
        }
    }
}

// Registrar para depuración el valor final del contador de pedidos pendientes
error_log("mis_compras.php - Contador final de pedidos pendientes: " . $contador_estados['pendiente']);

// Título de la página
$titulo_pagina = "Mis Compras";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Compras - Alexander Carrasquel</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Usamos la clase global .page-title definida in styles.css */

        .back-btn {
            border-radius: 30px;
            padding: 0 20px;
            height: 36px;
            line-height: 36px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.12);
            transition: all 0.3s ease;
            text-transform: none;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .compra-card {
            margin-bottom: 30px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .compra-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            transform: translateY(-5px);
        }

        .compra-header {
            padding: 20px;
            background: linear-gradient(135deg, #f5f5f5 0%, #e3f2fd 100%);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            position: relative;
        }

        .compra-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #1976D2, #64B5F6);
        }

        .compra-codigo {
            font-family: 'Roboto Mono', monospace;
            font-weight: 700;
            color: #0D47A1;
            font-size: 22px;
            display: flex;
            align-items: center;
        }

        .compra-codigo i {
            font-size: 24px;
            margin-right: 10px;
            color: #1976D2;
        }

        .compra-fecha {
            color: #546E7A;
            font-size: 14px;
            margin-top: 5px;
            display: flex;
            align-items: center;
        }

        .compra-fecha i {
            font-size: 16px;
            margin-right: 8px;
            color: #546E7A;
        }

        .compra-estado {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 8px  16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            color: white;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .compra-estado i {
            margin-right: 8px;
            font-size: 16px;
        }

        .estado-pendiente {
            background-color: #FFC107;
        }

        .estado-verificado {
            background-color: #4CAF50;
        }

        .estado-rechazado {
            background-color: #F44336;
        }

        .estado-enviado {
            background-color: #2196F3;
        }

        .estado-entregado {
            background-color: #9C27B0;
        }

        .compra-body {
            padding: 25px;
            background-color: white;
        }

        .compra-section {
            margin-bottom: 30px;
        }

        .compra-section-title {
            font-size: 18px;
            font-weight: 500;
            color: #1976D2;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
        }

        .compra-section-title i {
            margin-right: 10px;
            font-size: 22px;
        }

        .producto-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .producto-imagen {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            margin-right: 15px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .producto-imagen img {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Cambiado a contain para evitar recortes */
            max-width: 100%;
            max-height: 100%;
        }

        .producto-info {
            flex: 1;
        }

        .producto-nombre {
            font-weight: 500;
            color: #263238;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .producto-detalles {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }

        .producto-detalle {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: #546E7A;
        }

        .producto-detalle i {
            font-size: 16px;
            margin-right: 5px;
            color: #1976D2;
        }

        .pago-item {
            background-color: #f5f5f5;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 3px solid #1976D2;
        }

        .pago-metodo {
            font-weight: 500;
            color: #1976D2;
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .pago-metodo i {
            margin-right: 8px;
            font-size: 20px;
        }

        .pago-detalles {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .pago-detalle {
            display: flex;
            flex-direction: column;
        }

        .pago-detalle-label {
            font-size: 12px;
            color: #546E7A;
            margin-bottom: 5px;
        }

        .pago-detalle-valor {
            font-weight: 500;
            color: #263238;
        }

        .envio-info {
            background-color: #f5f5f5;
            border-radius: 8px;
            padding: 20px;
            border-left: 3px solid #4CAF50;
        }

        .envio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .envio-item {
            display: flex;
            flex-direction: column;
        }

        .envio-label {
            font-size: 12px;
            color: #546E7A;
            margin-bottom: 5px;
        }

        .envio-valor {
            font-weight: 500;
            color: #263238;
        }

        .no-compras {
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-top: 30px;
        }

        .no-compras i {
            font-size: 80px;
            color: #bbdefb;
            margin-bottom: 20px;
            display: block;
        }

        .no-compras h5 {
            color: #1976D2;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .no-compras p {
            color: #546E7A;
            margin-bottom: 25px;
            font-size: 16px;
        }

        .no-compras .btn {
            border-radius: 25px;
            padding: 0 25px;
            height: 45px;
            line-height: 45px;
            text-transform: none;
            font-weight: 500;
            letter-spacing: 0.5px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.12);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animated {
            animation: fadeIn 0.5s ease forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }

        /* Estilos para la barra de progreso de estado */
        .estado-progress-container {
            margin: 20px 0;
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .estado-progress {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 0;
            padding: 0;
            list-style: none;
        }

        .estado-progress::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 5%;
            width: 90%;
            height: 4px;
            background-color: #e0e0e0;
            z-index: 1;
        }

        .estado-progress-item {
            position: relative;
            z-index: 2;
            width: 25%;
            text-align: center;
        }

        .estado-progress-step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: #757575;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .estado-progress-step i {
            font-size: 20px;
        }

        .estado-progress-text {
            font-size: 12px;
            font-weight: 500;
            color: #757575;
            margin-top: 5px;
            transition: all 0.3s ease;
        }

        .estado-progress-count {
            position: absolute;
            top: -10px;
            right: 30%;
            background-color: #1976D2;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* Estados activos */
        .estado-progress-item.active .estado-progress-step {
            background-color: #1976D2;
            color: white;
            box-shadow: 0 4px 10px rgba(25, 118, 210, 0.3);
        }

        .estado-progress-item.active .estado-progress-text {
            color: #1976D2;
            font-weight: 600;
        }

        /* Variantes de colores para diferentes estados */
        .estado-progress-item.pending .estado-progress-step {
            background-color: #FFC107;
            color: white;
        }

        .estado-progress-item.pending .estado-progress-text {
            color: #FFC107;
        }

        .estado-progress-item.process .estado-progress-step {
            background-color: #FF9800;
            color: white;
        }

        .estado-progress-item.process .estado-progress-text {
            color: #FF9800;
        }

        .estado-progress-item.completed .estado-progress-step {
            background-color: #4CAF50;
            color: white;
        }

        .estado-progress-item.completed .estado-progress-text {
            color: #4CAF50;
        }

        /* Estado seleccionado */
        .estado-progress-item.selected {
            transform: translateY(-5px);
        }

        .estado-progress-item.selected .estado-progress-step {
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }

        /* Estilos para el icono flotante de notificación */
        .notification-float-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 1000;
        }

        /* Ajuste para evitar superposición con el chat */
        @media (min-width: 601px) {
            .notification-float-container {
                bottom: 30px;
                right: 110px; /* Aumentado para evitar superposición con el chat */
            }
        }

        .notification-float {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #F44336;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
            margin-bottom: 5px;
        }

        .notification-float:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0,0,0,0.3);
        }

        .notification-float i {
            font-size: 28px;
        }

        .notification-float-text {
            background-color: #F44336;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            animation: pulse 2s infinite;
            cursor: pointer;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            }
        }

        /* Estilos para el modal de notificación */
        .notification-modal {
            max-width: 600px;
            border-radius: 12px;
            overflow: hidden;
        }

        .notification-modal .modal-content {
            padding: 0;
        }

        .notification-modal-header {
            background-color: #F44336;
            color: white;
            padding: 20px;
            position: relative;
        }

        .notification-modal-header h4 {
            margin: 0;
            font-size: 22px;
            display: flex;
            align-items: center;
        }

        .notification-modal-header h4 i {
            margin-right: 10px;
            font-size: 28px;
        }

        .notification-modal-body {
            padding: 25px;
            background-color: white;
        }

        .notification-message {
            margin-bottom: 20px;
            font-size: 16px;
            line-height: 1.6;
            color: #333;
        }

        .notification-pedido {
            background-color: #f5f5f5;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 3px solid #F44336;
            display: flex;
            align-items: center;
        }

        .notification-pedido-codigo {
            font-family: 'Roboto Mono', monospace;
            font-weight: 700;
            color: #F44336;
            font-size: 18px;
            margin-right: 15px;
        }

        .notification-pedido-fecha {
            color: #757575;
            font-size: 14px;
        }

        .notification-modal-footer {
            background-color: #f5f5f5;
            padding: 15px;
            text-align: right;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/frontend/includes/header.php'; ?>
<main class="container" data-user-main>
    <h4 class="page-title center-align" style="margin-top: 30px;">Mis Compras</h4>

    <div class="back-btn-container">
        <a href="index.php" class="btn blue lighten-1 waves-effect waves-light back-btn">
            <i class="material-icons left">home</i>Volver al inicio
        </a>
    </div>

    <!-- Barra de progreso de estado -->
    <div class="estado-progress-container animated">
        <ul class="estado-progress">
            <li class="estado-progress-item <?php echo $contador_estados['pendiente'] > 0 ? 'active pending' : ''; ?>" id="estado-pendiente">
                <div class="estado-progress-step">
                    <i class="material-icons">shopping_bag</i>
                </div>
                <div class="estado-progress-text">Pedidos Pendientes</div>
                <?php if ($contador_estados['pendiente'] > 0): ?>
                <div class="estado-progress-count"><?php echo $contador_estados['pendiente']; ?></div>
                <?php endif; ?>
            </li>
            <li class="estado-progress-item <?php echo $contador_estados['pagado'] > 0 ? 'active pending' : ''; ?>" id="estado-pagado">
                <div class="estado-progress-step">
                    <i class="material-icons">credit_score</i>
                </div>
                <div class="estado-progress-text">Pagos Informados</div>
                <?php if ($contador_estados['pagado'] > 0): ?>
                <div class="estado-progress-count"><?php echo $contador_estados['pagado']; ?></div>
                <?php endif; ?>
            </li>
            <li class="estado-progress-item <?php echo $contador_estados['proceso'] > 0 ? 'active process' : ''; ?>" id="estado-proceso">
                <div class="estado-progress-step">
                    <i class="material-icons">local_shipping</i>
                </div>
                <div class="estado-progress-text">En Proceso de Envío</div>
                <?php if ($contador_estados['proceso'] > 0): ?>
                <div class="estado-progress_count"><?php echo $contador_estados['proceso']; ?></div>
                <?php endif; ?>
            </li>
            <li class="estado-progress-item <?php echo $contador_estados['enviado'] > 0 ? 'active completed' : ''; ?>" id="estado-enviado">
                <div class="estado-progress-step">
                    <i class="material-icons">mark_email_read</i>
                </div>
                <div class="estado-progress-text">Pedidos Enviados</div>
                <?php if ($contador_estados['enviado'] > 0): ?>
                <div class="estado-progress-count"><?php echo $contador_estados['enviado']; ?></div>
                <?php endif; ?>
            </li>
        </ul>
    </div>

    <?php if (empty($compras)): ?>
        <div class="no-compras animated">
            <i class="material-icons">shopping_bag</i>
            <h5>No tienes compras realizadas</h5>
            <p>Cuando realices una compra, podrás ver aquí todos los detalles de tus pagos y envíos.</p>
            <a href="catalogo.php" class="btn blue waves-effect waves-light">
                <i class="material-icons left">shopping_cart</i>Ir al catálogo
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($compras as $index => $compra): ?>
                <div class="col s12 animated delay-<?php echo min($index + 1, 3); ?>">
                    <div class="compra-card" data-estado-envio="<?php echo isset($compra['estado_envio']) ? $compra['estado_envio'] : ''; ?>">
                        <div class="compra-header">
                            <div class="compra-codigo">
                                <i class="material-icons">confirmation_number</i>
                                <?php echo $compra['pedido_codigo']; ?>
                            </div>
                            <div class="compra-fecha">
                                <i class="material-icons">calendar_month</i>
                                Fecha: <?php echo date('d/m/Y', strtotime($compra['fecha_registro'])); ?>
                            </div>
                            <?php
                            // Determinar la clase CSS y el texto del estado
                            $estado_clase = 'estado-' . $compra['estado_pago'];
                            $estado_texto = ucfirst($compra['estado_pago']);
                            $estado_icono = $compra['estado_pago'] == 'verificado' ? 'verified' :
                                            ($compra['estado_pago'] == 'rechazado' ? 'cancel' : 'pending_actions');

                            // Si hay estado de envío, usarlo en su lugar
                            if (isset($compra['estado_envio']) && ($compra['estado_envio'] == 'enviado' || $compra['estado_envio'] == 'entregado')) {
                                $estado_clase = 'estado-' . $compra['estado_envio'];
                                $estado_texto = ucfirst($compra['estado_envio']);
                                $estado_icono = $compra['estado_envio'] == 'enviado' ? 'local_shipping' : 'task_alt';
                            }
                            ?>
                            <div class="compra-estado <?php echo $estado_clase; ?>">
                                <i class="material-icons"><?php echo $estado_icono; ?></i>
                                <?php echo $estado_texto; ?>
                            </div>
                        </div>

                        <div class="compra-body">
                            <!-- Sección de productos -->
                            <div class="compra-section">
                                <div class="compra-section-title">
                                    <i class="material-icons">inventory_2</i>
                                    Productos
                                </div>

                                <?php if (!empty($compra['productos'])): ?>
                                    <?php foreach ($compra['productos'] as $producto): ?>
                                        <div class="producto-item">
                                            <div class="producto-imagen">
                                                <img src="<?php echo isset($producto['imagen']) ? $producto['imagen'] : 'images/no-image.jpg'; ?>" alt="<?php echo isset($producto['nombre']) ? $producto['nombre'] : 'Producto'; ?>">
                                            </div>
                                            <div class="producto-info">
                                                <div class="producto-nombre"><?php echo isset($producto['nombre']) ? $producto['nombre'] : 'Producto'; ?></div>
                                                <div class="producto-detalles">
                                                    <div class="producto-detalle">
                                                        <i class="material-icons">shopping_basket</i>
                                                        Cantidad: <?php echo $producto['cantidad']; ?>
                                                    </div>
                                                    <div class="producto-detalle">
                                                        <i class="material-icons">attach_money</i>
                                                        Precio: $<?php echo number_format($producto['precio_dolares'], 2); ?> / Bs. <?php echo number_format($producto['precio_bolivares'], 2); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php
                                    // Al mostrar el total en dólares y bolívares para pedidos pendientes:
                                    $total_dolares = isset($compra['total_dolares']) && $compra['total_dolares'] !== null ? $compra['total_dolares'] : 0.00;
                                    ?>
                                    <div class="pedido-total">
                                        Total: $<?php echo number_format($total_dolares, 2); ?> / Bs. <?php echo number_format($total_dolares * $tasa_actual, 2); ?>
                                    </div>
                                <?php else: ?>
                                    <p class="center-align">No hay información de productos disponible.</p>
                                <?php endif; ?>
                            </div>

                            <!-- Sección de pagos -->
                            <div class="compra-section">
                                <div class="compra-section-title">
                                    <i class="material-icons">payments</i>
                                    Información de Pago
                                </div>

                                <?php if ($compra['estado_pago'] == 'pendiente'): ?>
                                    <!-- Mostrar botón de cancelación para pedidos pendientes -->
                                    <div class="center-align" style="margin: 20px 0;">
                                        <?php if (empty($compra['detalles_pago'])): ?>
                                            <p>No has registrado información de pago para este pedido.</p>
                                            <div class="pago-detalle-valor" style="margin-bottom:10px;">
                                                <strong>Monto a pagar:</strong> $<?php echo number_format($total_dolares, 2); ?> / Bs. <?php echo number_format($total_dolares * $tasa_actual, 2); ?>
                                            </div>
                                            <a href="registrar_pago.php?pedido=<?php echo $compra['pedido_codigo']; ?>" class="btn waves-effect waves-light green">
                                                <i class="material-icons left">payment</i>Registrar Pago
                                            </a>
                                        <?php endif; ?>

                                        <button type="button" class="btn waves-effect waves-light red modal-trigger" data-target="modal-cancelar-<?php echo $compra['pedido_codigo']; ?>" <?php echo !empty($compra['detalles_pago']) ? '' : 'style="margin-left: 10px;"'; ?>>
                                            <i class="material-icons left">cancel</i>Cancelar Pedido
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <!-- Mostrar detalles de pago si existen -->
                                <?php if (!empty($compra['detalles_pago'])): ?>
                                    <!-- Información de Pago -->
                                    <section class="compra-section">
                                        <div class="compra-section-title"><i class="material-icons">account_balance_wallet</i> Información de Pago</div>
                                        <div class="pago-item">
                                            <table style="width:100%; border:none; background:transparent;">
                                                <thead>
                                                    <tr>
                                                        <th style="text-align:left; color:#546E7A; font-size:13px;">Banco</th>
                                                        <th style="text-align:left; color:#546E7A; font-size:13px;">Referencia</th>
                                                        <th style="text-align:left; color:#546E7A; font-size:13px;">Monto</th>
                                                        <th style="text-align:left; color:#546E7A; font-size:13px;">Fecha de Pago</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($compra['detalles_pago'] as $pago): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($pago['banco']); ?></td>
                                                        <td><?php echo htmlspecialchars($pago['referencia']); ?></td>
                                                        <td><?php echo '$' . number_format($pago['monto'], 2, ',', '.'); ?></td>
                                                        <td><?php echo htmlspecialchars($pago['fecha_pago']); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </section>
                                <?php elseif (empty($compra['detalles_pago']) && $compra['estado_pago'] != 'pendiente'): ?>
                                    <p class="center-align">No hay información de pago disponible.</p>
                                <?php endif; ?>
                            </div>

                            <!-- Sección de envío -->
                            <div class="compra-section">
                                <div class="compra-section-title">
                                    <i class="material-icons">local_shipping</i>
                                    Datos de Envío
                                </div>

                                <?php if (!empty($compra['datos_envio'])): ?>
                                    <div class="envio-info">
                                        <div class="envio-grid">
                                            <div class="envio-item">
                                                <div class="envio-label">Dirección</div>
                                                <div class="envio-valor"><?php echo $compra['datos_envio']['direccion']; ?></div>
                                            </div>

                                            <?php if (!empty($compra['datos_envio']['empresa_envio'])): ?>
                                                <div class="envio-item">
                                                    <div class="envio-label">Empresa de Envío</div>
                                                    <div class="envio-valor"><?php echo $compra['datos_envio']['empresa_envio']; ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="envio-item">
                                                <div class="envio-label">Destinatario</div>
                                                <div class="envio-valor"><?php echo $compra['datos_envio']['destinatario_nombre']; ?></div>
                                            </div>

                                            <div class="envio-item">
                                                <div class="envio-label">Teléfono</div>
                                                <div class="envio-valor"><?php echo $compra['datos_envio']['destinatario_telefono']; ?></div>
                                            </div>

                                            <div class="envio-item">
                                                <div class="envio-label">Cédula</div>
                                                <div class="envio-valor"><?php echo $compra['datos_envio']['destinatario_cedula']; ?></div>
                                            </div>

                                            <?php if (!empty($compra['datos_envio']['instrucciones_adicionales'])): ?>
                                                <div class="envio-item" style="grid-column: 1 / -1;">
                                                    <div class="envio-label">Instrucciones Adicionales</div>
                                                    <div class="envio-valor"><?php echo $compra['datos_envio']['instrucciones_adicionales']; ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="center-align">No hay información de envío disponible.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Modales de confirmación para cancelar pedidos -->
<?php if (!empty($compras)): ?>
    <?php foreach ($compras as $compra): ?>
        <?php if ($compra['estado_pago'] == 'pendiente'): ?>
            <div id="modal-cancelar-<?php echo $compra['pedido_codigo']; ?>" class="modal">
                <div class="modal-content">
                    <h4>Confirmar cancelación</h4>
                    <p>¿Estás seguro de que deseas cancelar el pedido <strong><?php echo $compra['pedido_codigo']; ?></strong>?</p>
                    <p>Esta acción no se puede deshacer y los productos volverán a estar disponibles en el catálogo.</p>
                </div>
                <div class="modal-footer">
                    <a href="#!" class="modal-close waves-effect waves-light btn-flat">Cancelar</a>
                    <form action="backend/cancelar_pedido.php" method="POST" style="display: inline;">
                        <input type="hidden" name="codigo_pedido" value="<?php echo $compra['pedido_codigo']; ?>">
                        <button type="submit" class="waves-effect waves-light btn red">Confirmar cancelación</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'frontend/includes/footer.php'; ?>

<?php if (!empty($mensajes_devolucion)): ?>
<!-- Icono flotante de notificación -->
<div class="notification-float-container">
    <div class="notification-float" id="notification-float">
        <i class="material-icons">notifications_active</i>
    </div>
    <div class="notification-float-text">Clic aquí</div>
</div>

<!-- Modal de notificación -->
<div id="notification-modal" class="modal notification-modal">
    <div class="notification-modal-header">
        <h4><i class="material-icons">warning</i> Mensaje Importante</h4>
    </div>
    <div class="notification-modal-body">
        <div class="notification-message">
            <p><strong>Estimado cliente:</strong></p>
            <p>Hemos detectado posibles inconsistencias en los datos de pago proporcionados. Por favor, verifique y vuelva a informar su pago correctamente. Si los datos son incorrectos nuevamente, el pedido será cancelado y los productos serán reintegrados al catálogo.</p>
        </div>

        <h5>Pedidos afectados:</h5>
        <?php foreach ($mensajes_devolucion as $mensaje): ?>
        <div class="notification-pedido">
            <div class="notification-pedido-codigo"><?php echo $mensaje['pedido_codigo']; ?></div>
            <div class="notification-pedido-fecha">Fecha: <?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_registro'])); ?></div>
        </div>
        <?php
        // Mensaje predeterminado que se usa en admin_pedidos.php
        $mensaje_predeterminado = "Estimado cliente, hemos detectado posibles inconsistencias en los datos de pago proporcionados. Por favor, verifique y vuelva a informar su pago correctamente. Si los datos son incorrectos nuevamente, el pedido será cancelado y los productos serán reintegrados al catálogo.";

        // Solo mostrar el mensaje del administrador si no es el mensaje predeterminado
        if (!empty($mensaje['comentarios_admin']) && $mensaje['comentarios_admin'] != $mensaje_predeterminado):
        ?>
        <div class="notification-message">
            <p><strong>Mensaje del administrador:</strong></p>
            <p><?php echo $mensaje['comentarios_admin']; ?></p>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div class="notification-modal-footer">
        <button class="modal-close waves-effect waves-light btn-flat">Cerrar</button>
        <a href="mis_pedidos.php" class="waves-effect waves-light btn blue">Ver mis pedidos</a>
    </div>
</div>
<?php endif; ?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Materialize JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<!-- JavaScript personalizado -->
<script src="js/scripts.js"></script>

<!-- Script para la barra de progreso de estado -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar los modales
        $('.modal').modal({
            dismissible: true,
            opacity: 0.9,
            inDuration: 300,
            outDuration: 200,
            startingTop: '4%',
            endingTop: '10%'
        });

        // Obtener los elementos de la barra de progreso
        const estadoItems = document.querySelectorAll('.estado-progress-item');

        // Agregar eventos de clic a cada elemento
        estadoItems.forEach(item => {
            item.addEventListener('click', function() {
                const estadoId = this.id;
                const estadoClase = estadoId.replace('estado-', '');

                // Filtrar las compras según el estado seleccionado
                const compraCards = document.querySelectorAll('.compra-card');

                // Si no hay compras, no hacer nada
                if (compraCards.length === 0) return;

                // Resaltar el elemento seleccionado
                estadoItems.forEach(el => el.classList.remove('selected'));
                this.classList.add('selected');

                // Mostrar todas las compras si no hay ninguna activa
                if (!this.classList.contains('active')) {
                    compraCards.forEach(card => {
                        card.closest('.col').style.display = 'block';
                    });
                    return;
                }

                // Filtrar según el estado
                compraCards.forEach(card => {
                    const estadoElement = card.querySelector('.compra-estado');
                    const parentCol = card.closest('.col');

                    if (estadoId === 'estado-pendiente') {
                        // Mostrar pedidos pendientes
                        if (estadoElement.classList.contains('estado-pendiente')) {
                            parentCol.style.display = 'block';
                        } else {
                            parentCol.style.display = 'none';
                        }
                    } else if (estadoId === 'estado-pagado') {
                        // Mostrar pagos informados
                        // Verificar si hay detalles de pago
                        const tieneDetallesPago = card.querySelector('.pago-item') !== null;
                        const codigoPedido = card.querySelector('.compra-codigo').textContent.trim();

                        if (tieneDetallesPago) {
                            parentCol.style.display = 'block';
                            // Registrar para depuración
                            console.log("Mostrando pedido con pago informado:", codigoPedido);
                        } else {
                            parentCol.style.display = 'none';
                        }
                    } else if (estadoId === 'estado-proceso') {
                        // Mostrar en proceso de envío
                        // Verificar si el pedido tiene un estado de envío almacenado en un atributo de datos
                        const pedidoCodigo = card.querySelector('.compra-codigo').textContent.trim();
                        const estadoEnvio = card.getAttribute('data-estado-envio');

                        if (estadoElement.classList.contains('estado-verificado') &&
                            (!estadoEnvio || (estadoEnvio !== 'enviado' && estadoEnvio !== 'entregado'))) {
                            parentCol.style.display = 'block';
                        } else {
                            parentCol.style.display = 'none';
                        }
                    } else if (estadoId === 'estado-enviado') {
                        // Mostrar pedidos enviados
                        // Verificar si el pedido tiene un estado de envío almacenado en un atributo de datos
                        const estadoEnvio = card.getAttribute('data-estado-envio');

                        if (estadoEnvio === 'enviado' || estadoEnvio === 'entregado') {
                            parentCol.style.display = 'block';
                        } else {
                            parentCol.style.display = 'none';
                        }
                    }
                });

                // Animar las tarjetas visibles
                const visibleCards = document.querySelectorAll('.col[style="display: block"] .compra-card');
                visibleCards.forEach((card, index) => {
                    card.classList.remove('animated');
                    setTimeout(() => {
                        card.classList.add('animated');
                    }, 10);
                });
            });
        });

        // Agregar efecto hover
        estadoItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                if (this.classList.contains('active')) {
                    this.style.transform = 'translateY(-5px)';
                    this.style.cursor = 'pointer';
                }
            });

            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });
</script>

<?php include 'backend/script/script_enlaces.php'; ?>

<!-- Script para manejar la eliminación de pedidos rechazados -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar clic en el botón de eliminar pedido
    document.querySelectorAll('.btn-eliminar-pedido').forEach(button => {
        button.addEventListener('click', function() {
            const pedidoCodigo = this.getAttribute('data-pedido');

            // Mostrar confirmación
            if (confirm('¿Estás seguro de que deseas eliminar este pedido rechazado? Esta acción no se puede deshacer.')) {
                // Mostrar indicador de carga
                const loading = M.Modal.init(document.createElement('div'), {
                    dismissible: false,
                    opacity: 0.5,
                    inDuration: 300,
                    outDuration: 200,
                    startingTop: '4%',
                    endingTop: '10%',
                    ready: function(modal, trigger) {
                        modal.$el.append('<div class="progress"><div class="indeterminate"></div></div><p class="center-align">Eliminando pedido...</p>');
                    }
                });
                loading.open();

                // Enviar solicitud para eliminar el pedido
                fetch('/tiendalex2/backend/eliminar_pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `pedido_codigo=${encodeURIComponent(pedidoCodigo)}`
                })
                .then(response => response.json())
                .then data => {
                    loading.close();
                    if (data.success) {
                        M.toast({html: 'Pedido eliminado correctamente', classes: 'green'});
                        // Recargar la página después de 1 segundo
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        M.toast({html: 'Error: ' + data.message, classes: 'red'});
                    }
                })
                .catch(error => {
                    loading.close();
                    console.error('Error:', error);
                    M.toast({html: 'Error al procesar la solicitud', classes: 'red'});
                });
            }
        });
    });
});
</script>

<?php if (!empty($mensajes_devolucion)): ?>
<!-- Script para manejar el icono flotante y el modal de notificación -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar el modal de notificación
        var notificationModal = document.getElementById('notification-modal');
        var notificationModalInstance = M.Modal.init(notificationModal, {
            dismissible: true,
            opacity: 0.9,
            inDuration: 300,
            outDuration: 200,
            startingTop: '4%',
            endingTop: '10%'
        });

        // Manejar el clic en el icono flotante
        var notificationFloat = document.getElementById('notification-float');
        var notificationText = document.querySelector('.notification-float-text');

        if (notificationFloat) {
            notificationFloat.addEventListener('click', function() {
                notificationModalInstance.open();
            });
        }

        // Manejar el clic en el texto "Clic aquí"
        if (notificationText) {
            notificationText.addEventListener('click', function() {
                notificationModalInstance.open();
            });
        }

        // Mostrar automáticamente el modal al cargar la página (opcional)
        // setTimeout(function() {
        //     notificationModalInstance.open();
        // }, 1000);

        // Guardar en localStorage que el usuario ha visto la notificación
        notificationModal.addEventListener('modal:close', function() {
            localStorage.setItem('notification_seen_<?php echo md5(json_encode($mensajes_devolucion)); ?>', 'true');
        });

        // Verificar si el usuario ya ha visto esta notificación
        var notificationSeen = localStorage.getItem('notification_seen_<?php echo md5(json_encode($mensajes_devolucion)); ?>');
        if (notificationSeen === 'true') {
            // Si ya la vio, ocultar el icono flotante
            // notificationFloat.style.display = 'none';
            // O mantenerlo visible pero con menos prominencia
            if (notificationFloat) {
                notificationFloat.style.animation = 'none';
                notificationFloat.style.opacity = '0.7';
            }

            // También aplicar el mismo efecto al texto "Clic aquí"
            if (notificationText) {
                notificationText.style.animation = 'none';
                notificationText.style.opacity = '0.7';
            }
        }
    });
</script>
<?php endif; ?>
</body>
</html>
