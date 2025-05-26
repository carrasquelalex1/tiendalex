<?php
require_once __DIR__ . '/autoload.php';
/**
 * Página de confirmación de pedido
 */

// Incluir archivos necesarios
require_once __DIR__ . '/backend/config/db.php';
require_once __DIR__ . '/helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Registrar información de depuración
error_log("confirmacion_pedido.php - SESSION al inicio: " . print_r($_SESSION, true));

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    error_log("confirmacion_pedido.php - Usuario no logueado");
    header("Location: index.php?error=1&message=" . urlencode("Debe iniciar sesión para ver la confirmación del pedido."));
    exit;
}

// Verificar si hay un pedido completado
if (!isset($_SESSION['ultimo_pedido_completado'])) {
    header("Location: index.php");
    exit;
}

// Obtener el código del último pedido completado
$codigo_pedido = $_SESSION['ultimo_pedido_completado'];

// Registrar información de depuración
error_log("confirmacion_pedido.php - Código de pedido: " . $codigo_pedido);

// Obtener información del pedido
$sql = "SELECT p.*, pt.nombre_producto, pt.imagen_producto, u.nombre_usuario
        FROM pedido p
        JOIN productos_tienda pt ON p.producto_id = pt.id_producto
        JOIN usuario u ON p.usuario_id = u.codigo
        WHERE p.pedido = ?
        ORDER BY p.id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo_pedido);
$stmt->execute();
$result = $stmt->get_result();

$productos_pedido = [];
$total_dolares = 0;
$total_bolivares = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $productos_pedido[] = $row;
        $total_dolares += $row['precio_dolares'] * $row['cantidad'];
        $total_bolivares += $row['precio_bolivares'] * $row['cantidad'];
    }
}

// Obtener información del usuario
$usuario_id = $_SESSION['usuario_logueado'];
$sql = "SELECT * FROM usuario WHERE codigo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

// Título de la página
$titulo_pagina = "Confirmación de Pedido";

// Configurar el título de la página
$titulo_pagina = "Confirmación de Pedido";

// Incluir el encabezado
include 'frontend/includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Pedido - TiendAlex</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <style>
        .confirmacion-container {
            padding: 40px 20px;
            text-align: center;
        }

        .confirmacion-icon {
            background-color: #e8f5e9;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scale-in 0.5s ease-out;
        }

        .confirmacion-icon i {
            font-size: 60px;
            color: #4CAF50;
        }

        @keyframes scale-in {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .pedido-detalles {
            background-color: #f5f5f5;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            animation: slide-up 0.5s ease-out;
        }

        @keyframes slide-up {
            0% {
                transform: translateY(50px);
                opacity: 0;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .pedido-codigo {
            font-family: 'Roboto Mono', monospace;
            font-size: 24px;
            font-weight: 700;
            color: #0D47A1;
            margin: 20px 0;
            letter-spacing: 1.5px;
            animation: fade-in 1s ease-out;
        }

        @keyframes fade-in {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }

        .acciones-container {
            margin-top: 40px;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .btn-accion {
            min-width: 200px;
            margin: 10px;
            animation: bounce-in 0.8s ease-out;
        }

        @keyframes bounce-in {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        .producto-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .producto-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            margin-right: 15px;
            border-radius: 4px;
        }

        .producto-info {
            flex-grow: 1;
            text-align: left;
        }

        .producto-precio {
            font-weight: bold;
            color: #1565C0;
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f2f2f2;
            animation: confetti-fall 5s linear infinite;
            z-index: 9999;
        }

        @keyframes confetti-fall {
            0% {
                transform: translateY(-100px) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
    <main class="container">
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content confirmacion-container">
                        <div class="confirmacion-icon">
                            <i class="material-icons">check_circle</i>
                        </div>
                        <h3 class="green-text">¡Pedido Completado!</h3>
                        <p class="flow-text">Tu pedido ha sido procesado correctamente.</p>

                        <div class="pedido-codigo">
                            Código de Pedido: <span id="codigo-pedido"><?php echo $codigo_pedido; ?></span>
                        </div>

                        <div class="pedido-detalles">
                            <h5>Resumen de tu pedido</h5>
                            <div class="divider" style="margin: 15px 0;"></div>

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
                                                <div><?php echo $producto['nombre_producto']; ?></div>
                                                <div>Cantidad: <?php echo $producto['cantidad']; ?></div>
                                            </div>
                                            <div class="producto-precio">
                                                <div>$<?php echo number_format($producto['precio_dolares'], 2); ?></div>
                                                <div>Bs. <?php echo number_format($producto['precio_bolivares'], 2); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="divider" style="margin: 15px 0;"></div>
                                <div class="total-container right-align">
                                    <h5>Total: $<?php echo number_format($total_dolares, 2); ?> / Bs. <?php echo number_format($total_bolivares, 2); ?></h5>
                                </div>
                            <?php else: ?>
                                <p>No se encontraron productos en este pedido.</p>
                            <?php endif; ?>
                        </div>

                        <div class="acciones-container">
                            <a href="registrar_pago.php?pedido=<?php echo $codigo_pedido; ?>" class="btn-large waves-effect waves-light green btn-accion">
                                <i class="material-icons left">payment</i>Registrar Pago
                            </a>
                            <a href="mis_compras.php" class="btn-large waves-effect waves-light blue btn-accion">
                                <i class="material-icons left">list</i>Ver Mis Compras
                            </a>
                            <a href="catalogo.php" class="btn-large waves-effect waves-light amber darken-2 btn-accion">
                                <i class="material-icons left">shopping_basket</i>Seguir Comprando
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/scripts.js"></script>
    <script src="js/confirmacion_pedido.js"></script>
    <?php include 'backend/script/script_enlaces.php'; ?>

    <?php
    // Verificar si hay un mensaje de compra completada en la sesión
    if (isset($_SESSION['compra_completada']) && $_SESSION['compra_completada']) {
        // Limpiar la variable de sesión para que no se muestre el mensaje nuevamente
        $_SESSION['compra_completada'] = false;
        echo "<script>
            // Este script se ejecutará cuando la página se cargue
            document.addEventListener('DOMContentLoaded', function() {
                // Mostrar un mensaje de confirmación adicional
                Swal.fire({
                    title: '¡Compra Exitosa!',
                    text: '" . (isset($_SESSION['mensaje_compra']) ? addslashes($_SESSION['mensaje_compra']) : "Tu pedido ha sido procesado correctamente.") . "',
                    icon: 'success',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#4CAF50',
                    timer: 5000,
                    timerProgressBar: true
                });
            });
        </script>";
    }
    ?>

    <?php include 'frontend/includes/footer.php'; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Materialize JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- JavaScript personalizado -->
    <script src="js/scripts.js"></script>
    <script src="js/confirmacion_pedido.js"></script>
    <?php include 'backend/script/script_enlaces.php'; ?>

    <?php
    // Verificar si hay un mensaje de compra completada en la sesión
    if (isset($_SESSION['compra_completada']) && $_SESSION['compra_completada']) {
        // Limpiar la variable de sesión para que no se muestre el mensaje nuevamente
        $_SESSION['compra_completada'] = false;
        echo "<script>
            // Este script se ejecutará cuando la página se cargue
            document.addEventListener('DOMContentLoaded', function() {
                // Mostrar un mensaje de confirmación adicional
                Swal.fire({
                    title: '¡Compra Exitosa!',
                    text: '" . (isset($_SESSION['mensaje_compra']) ? addslashes($_SESSION['mensaje_compra']) : "Tu pedido ha sido procesado correctamente.") . "',
                    icon: 'success',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#4CAF50',
                    timer: 5000,
                    timerProgressBar: true
                });
            });
        </script>";
    }
    ?>
</body>
</html>
