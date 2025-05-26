<?php
require_once __DIR__ . '/autoload.php';
/**
 * Página de detalle de pedido
 */

// Incluir archivos necesarios
require_once 'backend/config/db.php';
require_once 'helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Registrar información de depuración
error_log("detalle_pedido.php - SESSION al inicio: " . print_r($_SESSION, true));

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    error_log("detalle_pedido.php - Usuario no logueado");
    header("Location: index.php?error=1&message=" . urlencode("Debe iniciar sesión para ver los detalles del pedido."));
    exit;
}

// Verificar si se ha proporcionado un código de pedido
if (!isset($_GET['pedido']) || empty($_GET['pedido'])) {
    header("Location: mis_compras.php");
    exit;
}

// Obtener el código del pedido
$codigo_pedido = $_GET['pedido'];

// Registrar información de depuración
error_log("detalle_pedido.php - Código de pedido: " . $codigo_pedido);

// Obtener el ID del usuario
$usuario_id = $_SESSION['usuario_logueado'];

// Verificar si es un pedido finalizado
$es_pedido_finalizado = isset($_GET['tipo']) && $_GET['tipo'] === 'finalizado';

// Verificar si la tabla pedidos_finalizados existe
$tabla_existe = false;
if ($es_pedido_finalizado) {
    $sql_check_table = "SHOW TABLES LIKE 'pedidos_finalizados'";
    $result_check = $conn->query($sql_check_table);
    if ($result_check && $result_check->num_rows > 0) {
        $tabla_existe = true;
    } else {
        // Si la tabla no existe, redirigir a mis compras
        header("Location: mis_compras.php");
        exit;
    }
}

// Verificar si el pedido está en la tabla pedidos_finalizados con estado 'pendiente'
// Esto significa que es un pedido devuelto
$es_pedido_devuelto = false;
if ($tabla_existe) {
    $sql_check_devuelto = "SELECT COUNT(*) as count FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ? AND estado = 'pendiente'";
    $stmt_check_devuelto = $conn->prepare($sql_check_devuelto);
    $stmt_check_devuelto->bind_param("si", $codigo_pedido, $usuario_id);
    $stmt_check_devuelto->execute();
    $result_check_devuelto = $stmt_check_devuelto->get_result();
    $row_check_devuelto = $result_check_devuelto->fetch_assoc();
    $es_pedido_devuelto = ($row_check_devuelto['count'] > 0);

    // Registrar para depuración
    error_log("detalle_pedido.php - Pedido: $codigo_pedido, Usuario: $usuario_id, Es devuelto: " . ($es_pedido_devuelto ? 'Sí' : 'No'));
}

// Si es un pedido devuelto, obtener productos de pedidos_finalizados con estado 'pendiente'
if ($es_pedido_devuelto) {
    // Obtener información del pedido devuelto
    // Usamos DISTINCT y filtramos para evitar duplicados
    $sql = "SELECT DISTINCT
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
            JOIN productos_tienda pt ON pf.producto_id = pt.id_producto
            WHERE pf.pedido_codigo = ?
              AND pf.usuario_id = ?
              AND pf.estado = 'pendiente'
              AND pf.producto_id NOT IN (
                  SELECT producto_id
                  FROM pedidos_finalizados
                  WHERE pedido_codigo = ?
                    AND usuario_id = ?
                    AND estado != 'pendiente'
              )
            ORDER BY pf.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisi", $codigo_pedido, $usuario_id, $codigo_pedido, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Si no hay resultados, redirigir a mis compras
    if ($result->num_rows == 0) {
        header("Location: mis_compras.php");
        exit;
    }
}
// Si es un pedido finalizado (no devuelto), obtener productos de pedidos_finalizados
else if ($es_pedido_finalizado && $tabla_existe) {
    // Verificar si el pedido finalizado pertenece al usuario
    $sql = "SELECT COUNT(*) as count FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ? AND (estado != 'pendiente' OR estado IS NULL)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $codigo_pedido, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] == 0) {
        // El pedido no pertenece al usuario
        header("Location: mis_compras.php");
        exit;
    }

    // Obtener información del pedido finalizado
    // Usamos GROUP BY producto_id para evitar duplicados
    $sql = "SELECT
                pf.producto_id,
                pf.pedido_codigo,
                pf.usuario_id,
                pf.precio_dolares,
                pf.precio_bolivares,
                MAX(pf.cantidad) as cantidad,
                pf.estado,
                pf.fecha_pedido,
                pt.nombre_producto,
                pt.imagen_producto,
                pt.descripcion_producto
            FROM pedidos_finalizados pf
            JOIN productos_tienda pt ON pf.producto_id = pt.id_producto
            WHERE pf.pedido_codigo = ? AND pf.usuario_id = ? AND (pf.estado != 'pendiente' OR pf.estado IS NULL)
            GROUP BY pf.producto_id
            ORDER BY MAX(pf.id) DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $codigo_pedido, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Verificar si el pedido pendiente pertenece al usuario
    $sql = "SELECT COUNT(*) as count FROM pedido WHERE pedido = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $codigo_pedido, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] == 0) {
        // El pedido no pertenece al usuario
        header("Location: mis_compras.php");
        exit;
    }

    // Obtener información del pedido pendiente
    // Usamos GROUP BY producto_id para evitar duplicados
    $sql = "SELECT
                p.producto_id,
                p.pedido,
                p.usuario_id,
                p.precio_dolares,
                p.precio_bolivares,
                MAX(p.cantidad) as cantidad,
                p.created_at,
                pt.nombre_producto,
                pt.imagen_producto,
                pt.descripcion_producto
            FROM pedido p
            JOIN productos_tienda pt ON p.producto_id = pt.id_producto
            WHERE p.pedido = ? AND p.usuario_id = ?
            GROUP BY p.producto_id
            ORDER BY MAX(p.id) DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $codigo_pedido, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$productos_pedido = [];
$total_dolares = 0;
$total_bolivares = 0;
$fecha_pedido = '';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Registrar información de depuración sobre las imágenes
        error_log("detalle_pedido.php - Producto ID: " . $row['producto_id'] . ", Imagen: " . $row['imagen_producto']);

        $productos_pedido[] = $row;
        $total_dolares += $row['precio_dolares'] * $row['cantidad'];
        $total_bolivares += $row['precio_bolivares'] * $row['cantidad'];
        $fecha_pedido = $es_pedido_finalizado ? $row['fecha_pedido'] : $row['created_at'];
    }
}

// Verificar si hay pagos registrados para este pedido
$sql_pago = "SELECT * FROM pagos WHERE pedido_codigo = ? AND usuario_codigo = ? LIMIT 1";
$stmt_pago = $conn->prepare($sql_pago);
$stmt_pago->bind_param("si", $codigo_pedido, $usuario_id);
$stmt_pago->execute();
$result_pago = $stmt_pago->get_result();

// Un pedido devuelto siempre se considera como "pago no registrado" para permitir registrar el pago nuevamente
$pago_registrado = ($result_pago->num_rows > 0 && !$es_pedido_devuelto) || ($es_pedido_finalizado && !$es_pedido_devuelto);
$pago = $pago_registrado && !$es_pedido_finalizado ? $result_pago->fetch_assoc() : null;

// Título de la página
$titulo_pagina = "Detalle de Pedido";

// Incluir el encabezado
include 'frontend/includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Pedido - TiendAlex</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@500&display=swap" rel="stylesheet">
    <?php include 'frontend/includes/css_includes.php'; ?>
    <style>
        /* Estilos para el título de la página */
        .page-title {
            margin-top: 20px;
            margin-bottom: 30px;
            color: #1565C0;
            font-weight: 500;
            position: relative;
            z-index: 5;
        }

        /* Estilos para el botón de volver */
        .back-btn {
            border-radius: 30px !important;
            padding: 0 20px !important;
            height: 36px !important;
            line-height: 36px !important;
            box-shadow: 0 3px 8px rgba(0,0,0,0.12) !important;
            transition: all 0.3s ease !important;
            text-transform: none !important;
            font-weight: 500 !important;
        }

        .back-btn:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.15) !important;
            transform: translateY(-2px);
        }

        .back-btn i {
            margin-right: 5px !important;
        }

        .detalle-pedido-container {
            padding: 20px 0;
        }

        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #f5f5f5 0%, #e3f2fd 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            flex-wrap: wrap;
            gap: 20px;
        }

        .pedido-header-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .pedido-codigo {
            display: flex;
            align-items: center;
            font-family: 'Roboto Mono', monospace;
            font-weight: 700;
            color: #0D47A1;
            font-size: 24px;
        }

        .pedido-codigo i {
            font-size: 28px;
            margin-right: 10px;
            color: #1976D2;
        }

        .pedido-fecha {
            display: flex;
            align-items: center;
            color: #546E7A;
            font-size: 16px;
        }

        .pedido-fecha i {
            font-size: 20px;
            margin-right: 10px;
            color: #546E7A;
        }

        .pedido-estado {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .pedido-estado i {
            margin-right: 8px;
            font-size: 18px;
        }

        .section-title {
            color: #1565C0;
            font-weight: 500;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e3f2fd;
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

        /* Estilos para pedidos finalizados */
        .finalizado-badge {
            background-color: #4CAF50;
            color: white;
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 10px;
            margin-left: 10px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            vertical-align: middle;
        }

        .btn-factura {
            background-color: #FF9800;
        }

        .btn-factura:hover {
            background-color: #F57C00;
        }

        .productos-lista {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
        }

        .producto-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 12px;
            background-color: white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-left: 4px solid #1976D2;
        }

        .producto-item:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transform: translateY(-3px);
        }

        .producto-item img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            margin-right: 25px;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .producto-item:hover img {
            transform: scale(1.05);
        }

        .producto-info {
            flex-grow: 1;
            padding-right: 20px;
        }

        .producto-nombre {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 8px;
            color: #263238;
        }

        .producto-descripcion {
            color: #607D8B;
            margin-bottom: 10px;
            max-height: 44px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.4;
        }

        .producto-precio {
            font-weight: 700;
            color: #1565C0;
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .producto-precio::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            background-color: #1565C0;
            border-radius: 50%;
            margin-right: 8px;
        }

        .producto-cantidad {
            background-color: #e3f2fd;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            color: #1565C0;
            display: inline-flex;
            align-items: center;
            margin-top: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .producto-cantidad::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #1565C0;
            border-radius: 50%;
            margin-right: 8px;
            opacity: 0.5;
        }

        .producto-subtotal {
            min-width: 150px;
            text-align: right;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .pedido-total {
            text-align: right;
            font-weight: 700;
            color: white;
            font-size: 20px;
            margin-top: 30px;
            padding: 20px 25px;
            background: linear-gradient(135deg, #1976D2 0%, #0D47A1 100%);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .pedido-total::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            z-index: 1;
        }

        .pedido-acciones {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .pedido-acciones .btn {
            border-radius: 30px;
            padding: 0 25px;
            height: 42px;
            line-height: 42px;
            text-transform: none;
            font-weight: 500;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .pedido-acciones .btn:hover {
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
            transform: translateY(-3px);
        }

        .pedido-acciones .btn i {
            margin-right: 8px;
        }

        .pago-info {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            padding: 25px;
            border-radius: 12px;
            margin-top: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }

        .pago-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            z-index: 1;
        }

        .pago-info h5 {
            color: #2E7D32;
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .pago-info h5::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 24px;
            background-color: #2E7D32;
            margin-right: 12px;
            border-radius: 4px;
        }

        .pago-detalle {
            margin-top: 15px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .pago-detalle p {
            margin: 8px 0;
            padding: 10px 15px;
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .pago-label {
            font-weight: 700;
            color: #2E7D32;
            display: block;
            margin-bottom: 5px;
        }

        .comentarios {
            grid-column: 1 / -1;
        }

        /* Ajustes para pantallas pequeñas */
        @media (max-width: 600px) {
            .pedido-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .pedido-header-estado {
                margin-top: 15px;
            }

            .producto-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .producto-item img {
                margin-bottom: 15px;
                margin-right: 0;
                width: 100%;
                height: auto;
                max-height: 200px;
            }

            .producto-info {
                width: 100%;
                padding-right: 0;
                margin-bottom: 15px;
            }

            .producto-subtotal {
                width: 100%;
                text-align: left;
            }

            .pedido-acciones {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <main class="container" style="padding-top: 0;">
        <div class="row">
            <div class="col s12">
                <div style="position: relative; height: 50px; margin-top: 80px; margin-bottom: 20px;">
                    <a href="mis_compras.php" class="btn waves-effect waves-light blue lighten-1 back-btn tooltipped" data-position="bottom" data-tooltip="Volver a mis compras" style="position: absolute; left: 0; top: 0;">
                        <i class="material-icons left">arrow_back</i> Volver
                    </a>
                </div>

                <h4 class="center-align page-title">Detalle del Pedido</h4>

                <div class="detalle-pedido-container">
                    <div class="pedido-header">
                        <div class="pedido-header-info">
                            <div class="pedido-codigo">
                                <i class="material-icons"><?php echo $es_pedido_finalizado ? 'check_circle' : 'receipt'; ?></i>
                                <span><?php echo $codigo_pedido; ?></span>
                                <?php if ($es_pedido_finalizado): ?>
                                    <span class="finalizado-badge">Finalizado</span>
                                <?php endif; ?>
                            </div>
                            <div class="pedido-fecha">
                                <i class="material-icons">event</i>
                                <span><?php echo date('d/m/Y H:i', strtotime($fecha_pedido)); ?></span>
                            </div>
                        </div>

                        <div class="pedido-header-estado">
                            <?php if ($es_pedido_finalizado): ?>
                                <div class="pedido-estado estado-verificado">
                                    <i class="material-icons">check_circle</i>
                                    <span>Pago verificado</span>
                                </div>
                            <?php elseif ($pago_registrado): ?>
                                <div class="pedido-estado estado-<?php echo $pago['estado']; ?>">
                                    <i class="material-icons">
                                        <?php
                                            switch ($pago['estado']) {
                                                case 'pendiente':
                                                    echo 'hourglass_empty';
                                                    break;
                                                case 'verificado':
                                                    echo 'check_circle';
                                                    break;
                                                case 'rechazado':
                                                    echo 'cancel';
                                                    break;
                                            }
                                        ?>
                                    </i>
                                    <span>
                                        <?php
                                            switch ($pago['estado']) {
                                                case 'pendiente':
                                                    echo 'Pago en revisión';
                                                    break;
                                                case 'verificado':
                                                    echo 'Pago verificado';
                                                    break;
                                                case 'rechazado':
                                                    echo 'Pago rechazado';
                                                    break;
                                            }
                                        ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="pedido-estado estado-pendiente">
                                    <i class="material-icons">schedule</i>
                                    <span>Pago pendiente</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h5 class="section-title">Productos</h5>

                    <?php if (count($productos_pedido) > 0): ?>
                        <div class="productos-lista">
                            <?php foreach ($productos_pedido as $producto): ?>
                                <div class="producto-item">
                                    <?php if (!empty($producto['imagen_producto'])): ?>
                                        <img src="<?php echo $producto['imagen_producto']; ?>" alt="<?php echo $producto['nombre_producto']; ?>">
                                    <?php else: ?>
                                        <img src="backend/images/a.jpg" alt="<?php echo $producto['nombre_producto']; ?>">
                                    <?php endif; ?>

                                    <div class="producto-info">
                                        <div class="producto-nombre"><?php echo $producto['nombre_producto']; ?></div>
                                        <?php if (!empty($producto['descripcion_producto'])): ?>
                                            <div class="producto-descripcion"><?php echo $producto['descripcion_producto']; ?></div>
                                        <?php endif; ?>
                                        <div class="producto-precio">
                                            $<?php echo number_format($producto['precio_dolares'], 2); ?> / Bs. <?php echo number_format($producto['precio_bolivares'], 2); ?>
                                        </div>
                                        <div class="producto-cantidad">
                                            Cantidad: <?php echo $producto['cantidad']; ?>
                                        </div>
                                    </div>

                                    <div class="producto-subtotal">
                                        <div>Subtotal:</div>
                                        <div class="producto-precio">
                                            $<?php echo number_format($producto['precio_dolares'] * $producto['cantidad'], 2); ?>
                                        </div>
                                        <div class="producto-precio">
                                            Bs. <?php echo number_format($producto['precio_bolivares'] * $producto['cantidad'], 2); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="pedido-total">
                            Total: $<?php echo number_format($total_dolares, 2); ?> / Bs. <?php echo number_format($total_bolivares, 2); ?>
                        </div>

                        <?php if ($pago_registrado || $es_pedido_finalizado): ?>
                            <div class="pago-info">
                                <h5>Información de Pago</h5>
                                <div class="pago-detalle">
                                    <?php if ($es_pedido_finalizado): ?>
                                        <!-- Información de pago para pedidos finalizados -->
                                        <?php
                                        // Obtener información de pago para pedidos finalizados
                                        $sql_pago_finalizado = "SELECT * FROM pagos WHERE pedido_codigo = ? LIMIT 1";
                                        $stmt_pago_finalizado = $conn->prepare($sql_pago_finalizado);
                                        $stmt_pago_finalizado->bind_param("s", $codigo_pedido);
                                        $stmt_pago_finalizado->execute();
                                        $result_pago_finalizado = $stmt_pago_finalizado->get_result();

                                        if ($result_pago_finalizado->num_rows > 0) {
                                            $pago_finalizado = $result_pago_finalizado->fetch_assoc();
                                        ?>
                                            <p>
                                                <span class="pago-label">Método de pago</span>
                                                <?php
                                                    switch ($pago_finalizado['metodo_pago']) {
                                                        case 'transferencia':
                                                            echo 'Transferencia bancaria';
                                                            break;
                                                        case 'pago_movil':
                                                            echo 'Pago móvil';
                                                            break;
                                                        case 'efectivo':
                                                            echo 'Efectivo';
                                                            break;
                                                        default:
                                                            echo $pago_finalizado['metodo_pago'];
                                                    }
                                                ?>
                                            </p>
                                            <?php if (!empty($pago_finalizado['banco'])): ?>
                                                <p>
                                                    <span class="pago-label">Banco</span>
                                                    <?php echo $pago_finalizado['banco']; ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if (!empty($pago_finalizado['referencia'])): ?>
                                                <p>
                                                    <span class="pago-label">Referencia</span>
                                                    <?php echo $pago_finalizado['referencia']; ?>
                                                </p>
                                            <?php endif; ?>
                                            <p>
                                                <span class="pago-label">Monto</span>
                                                Bs. <?php echo number_format($pago_finalizado['monto'], 2); ?>
                                            </p>
                                            <p>
                                                <span class="pago-label">Fecha de pago</span>
                                                <?php echo date('d/m/Y', strtotime($pago_finalizado['fecha_pago'])); ?>
                                            </p>
                                            <?php if (!empty($pago_finalizado['telefono'])): ?>
                                                <p>
                                                    <span class="pago-label">Teléfono</span>
                                                    <?php echo $pago_finalizado['telefono']; ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if (!empty($pago_finalizado['comentarios'])): ?>
                                                <p class="comentarios">
                                                    <span class="pago-label">Comentarios</span>
                                                    <?php echo $pago_finalizado['comentarios']; ?>
                                                </p>
                                            <?php endif; ?>
                                        <?php } else { ?>
                                            <p>
                                                <span class="pago-label">Estado</span>
                                                Pago verificado
                                            </p>
                                            <p>
                                                <span class="pago-label">Fecha de finalización</span>
                                                <?php
                                                    // Obtener la fecha de finalización del primer producto
                                                    if (count($productos_pedido) > 0 && isset($productos_pedido[0]['fecha_finalizacion'])) {
                                                        echo date('d/m/Y', strtotime($productos_pedido[0]['fecha_finalizacion']));
                                                    } else {
                                                        echo 'No disponible';
                                                    }
                                                ?>
                                            </p>
                                        <?php } ?>

                                        <!-- Obtener información de envío -->
                                        <?php
                                        $sql_envio = "SELECT * FROM datos_envio WHERE pedido_codigo = ? LIMIT 1";
                                        $stmt_envio = $conn->prepare($sql_envio);
                                        $stmt_envio->bind_param("s", $codigo_pedido);
                                        $stmt_envio->execute();
                                        $result_envio = $stmt_envio->get_result();

                                        if ($result_envio->num_rows > 0) {
                                            $datos_envio = $result_envio->fetch_assoc();
                                        ?>
                                            <p class="comentarios">
                                                <span class="pago-label">Dirección de envío</span>
                                                <?php echo $datos_envio['direccion']; ?>
                                            </p>
                                            <?php if (!empty($datos_envio['empresa_envio'])): ?>
                                                <p>
                                                    <span class="pago-label">Empresa de envío</span>
                                                    <?php echo $datos_envio['empresa_envio']; ?>
                                                </p>
                                            <?php endif; ?>
                                            <p>
                                                <span class="pago-label">Destinatario</span>
                                                <?php echo $datos_envio['destinatario_nombre']; ?>
                                            </p>
                                            <p>
                                                <span class="pago-label">Teléfono del destinatario</span>
                                                <?php echo $datos_envio['destinatario_telefono']; ?>
                                            </p>
                                            <p>
                                                <span class="pago-label">Cédula del destinatario</span>
                                                <?php echo $datos_envio['destinatario_cedula']; ?>
                                            </p>
                                            <?php if (!empty($datos_envio['instrucciones_adicionales'])): ?>
                                                <p class="comentarios">
                                                    <span class="pago-label">Instrucciones adicionales</span>
                                                    <?php echo $datos_envio['instrucciones_adicionales']; ?>
                                                </p>
                                            <?php endif; ?>
                                        <?php } ?>
                                    <?php elseif ($pago_registrado): ?>
                                        <!-- Información de pago para pedidos pendientes -->
                                        <p>
                                            <span class="pago-label">Método de pago</span>
                                            <?php
                                                switch ($pago['metodo_pago']) {
                                                    case 'transferencia':
                                                        echo 'Transferencia bancaria';
                                                        break;
                                                    case 'pago_movil':
                                                        echo 'Pago móvil';
                                                        break;
                                                    case 'efectivo':
                                                        echo 'Efectivo';
                                                        break;
                                                }
                                            ?>
                                        </p>
                                        <?php if (!empty($pago['banco'])): ?>
                                            <p>
                                                <span class="pago-label">Banco</span>
                                                <?php echo $pago['banco']; ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if (!empty($pago['referencia'])): ?>
                                            <p>
                                                <span class="pago-label">Referencia</span>
                                                <?php echo $pago['referencia']; ?>
                                            </p>
                                        <?php endif; ?>
                                        <p>
                                            <span class="pago-label">Monto</span>
                                            Bs. <?php echo number_format($pago['monto'], 2); ?>
                                        </p>
                                        <p>
                                            <span class="pago-label">Fecha de pago</span>
                                            <?php echo date('d/m/Y', strtotime($pago['fecha_pago'])); ?>
                                        </p>
                                        <?php if (!empty($pago['telefono'])): ?>
                                            <p>
                                                <span class="pago-label">Teléfono</span>
                                                <?php echo $pago['telefono']; ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if (!empty($pago['comentarios'])): ?>
                                            <p class="comentarios">
                                                <span class="pago-label">Comentarios</span>
                                                <?php echo $pago['comentarios']; ?>
                                            </p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="pedido-acciones">
                            <a href="mis_compras.php" class="btn waves-effect waves-light blue">
                                <i class="material-icons left">arrow_back</i>Volver a Mis Compras
                            </a>
                            <?php if ($es_pedido_finalizado && !$es_pedido_devuelto): ?>
                                <a href="#" class="btn waves-effect waves-light green btn-factura">
                                    <i class="material-icons left">receipt_long</i>Ver Factura
                                </a>
                            <?php elseif (!$pago_registrado || $es_pedido_devuelto): ?>
                                <a href="registrar_pago.php?pedido=<?php echo $codigo_pedido; ?>" class="btn waves-effect waves-light green">
                                    <i class="material-icons left">payment</i>Registrar Pago
                                </a>
                                <?php if (!$es_pedido_devuelto): ?>
                                    <button type="button" class="btn waves-effect waves-light red modal-trigger" data-target="modal-cancelar-<?php echo $codigo_pedido; ?>" style="margin-left: 10px;">
                                        <i class="material-icons left">cancel</i>Cancelar Pedido
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p>No se encontraron productos en este pedido.</p>
                        <a href="mis_compras.php" class="btn waves-effect waves-light blue">
                            <i class="material-icons left">arrow_back</i>Volver a Mis Compras
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de confirmación para cancelar pedido -->
    <?php if (!$es_pedido_finalizado && !$pago_registrado): ?>
        <div id="modal-cancelar-<?php echo $codigo_pedido; ?>" class="modal">
            <div class="modal-content">
                <h4>Confirmar cancelación</h4>
                <p>¿Estás seguro de que deseas cancelar el pedido <strong><?php echo $codigo_pedido; ?></strong>?</p>
                <p>Esta acción no se puede deshacer y los productos volverán a estar disponibles en el catálogo.</p>
            </div>
            <div class="modal-footer">
                <a href="#!" class="modal-close waves-effect waves-light btn-flat">Cancelar</a>
                <form action="backend/cancelar_pedido.php" method="POST" style="display: inline;">
                    <input type="hidden" name="codigo_pedido" value="<?php echo $codigo_pedido; ?>">
                    <button type="submit" class="waves-effect waves-light btn red">Confirmar cancelación</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php include 'frontend/includes/footer.php'; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Materialize JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <!-- JavaScript personalizado -->
    <script src="js/scripts.js"></script>
    <?php include 'backend/script/script_enlaces.php'; ?>

    <script>
        $(document).ready(function() {
            // Asegurarse de que la página se cargue desde arriba
            window.scrollTo(0, 0);

            // Inicializar tooltips
            $('.tooltipped').tooltip();

            // Inicializar modales
            $('.modal').modal({
                dismissible: true,
                opacity: 0.9,
                inDuration: 300,
                outDuration: 200,
                startingTop: '4%',
                endingTop: '10%'
            });

            // Añadir animaciones a los elementos
            $('.back-btn').css({
                'opacity': '0',
                'transform': 'translateX(-20px)'
            }).animate({
                'opacity': '1',
                'transform': 'translateX(0)'
            }, 300);

            $('.page-title').css({
                'opacity': '0',
                'transform': 'translateY(-20px)'
            }).animate({
                'opacity': '1',
                'transform': 'translateY(0)'
            }, 400);

            $('.pedido-header').css({
                'opacity': '0',
                'transform': 'translateY(20px)'
            }).animate({
                'opacity': '1',
                'transform': 'translateY(0)'
            }, 500);

            // Animar los productos con un retraso secuencial
            $('.producto-item').each(function(index) {
                $(this).css({
                    'opacity': '0',
                    'transform': 'translateX(20px)'
                }).delay(300 + (index * 100)).animate({
                    'opacity': '1',
                    'transform': 'translateX(0)'
                }, 400);
            });

            // Animar el total
            $('.pedido-total').css({
                'opacity': '0',
                'transform': 'scale(0.95)'
            }).delay(600).animate({
                'opacity': '1',
                'transform': 'scale(1)'
            }, 400);

            // Animar la información de pago si existe
            $('.pago-info').css({
                'opacity': '0',
                'transform': 'translateY(20px)'
            }).delay(700).animate({
                'opacity': '1',
                'transform': 'translateY(0)'
            }, 500);

            // Animar los botones de acción
            $('.pedido-acciones .btn').each(function(index) {
                $(this).css({
                    'opacity': '0',
                    'transform': 'translateY(20px)'
                }).delay(800 + (index * 100)).animate({
                    'opacity': '1',
                    'transform': 'translateY(0)'
                }, 400);
            });
        });
    </script>
</body>
</html>
