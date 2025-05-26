<?php
// Usar rutas relativas al archivo actual
$root_path = __DIR__ . '/';

// Incluir archivos necesarios
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

// Obtener la tasa actual
$tasa_actual = 0;
$sql_tasa = "SELECT valor_tasa FROM tasa WHERE current = 1 ORDER BY id_tasa DESC LIMIT 1";
$result_tasa = $conn->query($sql_tasa);
if ($result_tasa && $result_tasa->num_rows > 0) {
    $tasa_actual = $result_tasa->fetch_assoc()['valor_tasa'];
}

// Primero, verificar si la columna estado_pago existe en la tabla pedido
$check_column = $conn->query("SHOW COLUMNS FROM pedido LIKE 'estado_pago'");
$estado_pago_exists = $check_column->num_rows > 0;

// Construir la consulta SQL dinámicamente
$sql_pendientes = "SELECT 
        p.pedido, 
        MAX(p.created_at) as fecha,
        SUM(p.cantidad * p.precio_dolares) as total_dolares,
        SUM(p.cantidad * p.precio_bolivares) as total_bolivares,
        COUNT(p.id) as total_productos" . 
        ($estado_pago_exists ? ", (SELECT p2.estado_pago FROM pedido p2 WHERE p2.pedido = p.pedido LIMIT 1) as estado_pago" : "") . 
        " FROM pedido p
        WHERE p.usuario_id = ?
        GROUP BY p.pedido
        ORDER BY fecha DESC";

// Agregar un valor predeterminado para estado_pago si no existe
if (!$estado_pago_exists) {
    $GLOBALS['estado_pago_default'] = 'pendiente';
}
$stmt_pendientes = $conn->prepare($sql_pendientes);
$stmt_pendientes->bind_param("i", $usuario_id);
$stmt_pendientes->execute();
$result_pendientes = $stmt_pendientes->get_result();

$pedidos_pendientes = [];
while ($row = $result_pendientes->fetch_assoc()) {
    $pedidos_pendientes[] = array_merge($row, ['tipo' => 'pendiente']);
}

// Verificar si la tabla pedidos_finalizados existe
$tabla_finalizados_existe = $conn->query("SHOW TABLES LIKE 'pedidos_finalizados'")->num_rows > 0;

// Obtener los pedidos finalizados del usuario
$pedidos_finalizados = [];
if ($tabla_finalizados_existe) {
    // Primero, obtener los códigos de pedidos que ya están en el array de pedidos pendientes
    $codigos_existentes = [];
    foreach ($pedidos_pendientes as $pedido_existente) {
        $codigos_existentes[] = $pedido_existente['pedido'];
    }

    // Obtener solo los pedidos finalizados que no están ya en pendientes
    if (!empty($codigos_existentes)) {
        $placeholders = str_repeat('?,', count($codigos_existentes) - 1) . '?';
        $sql_finalizados = "SELECT DISTINCT pedido_codigo as pedido, MAX(created_at) as fecha,
                          SUM(cantidad * precio_dolares) as total_dolares,
                          SUM(cantidad * precio_bolivares) as total_bolivares,
                          COUNT(id) as total_productos
                          FROM pedidos_finalizados
                          WHERE usuario_id = ? AND pedido_codigo NOT IN ($placeholders)
                          GROUP BY pedido_codigo
                          ORDER BY fecha DESC";
        
        $stmt_finalizados = $conn->prepare($sql_finalizados);
        $types = str_repeat('s', count($codigos_existentes));
        $stmt_finalizados->bind_param('i' . $types, $usuario_id, ...$codigos_existentes);
    } else {
        $sql_finalizados = "SELECT DISTINCT pedido_codigo as pedido, MAX(created_at) as fecha,
                          SUM(cantidad * precio_dolares) as total_dolares,
                          SUM(cantidad * precio_bolivares) as total_bolivares,
                          COUNT(id) as total_productos
                          FROM pedidos_finalizados
                          WHERE usuario_id = ?
                          GROUP BY pedido_codigo
                          ORDER BY fecha DESC";
        
        $stmt_finalizados = $conn->prepare($sql_finalizados);
        $stmt_finalizados->bind_param('i', $usuario_id);
    }
    
    $stmt_finalizados->execute();
    $result_finalizados = $stmt_finalizados->get_result();
    
    while ($row = $result_finalizados->fetch_assoc()) {
        $pedidos_finalizados[] = array_merge($row, ['tipo' => 'finalizado']);
    }
}

// Combinar todos los pedidos (primero pendientes, luego finalizados)
$pedidos = array_merge($pedidos_pendientes, $pedidos_finalizados);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Alexander Carrasquel</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .back-btn-container {
            margin-bottom: 20px;
        }
        
        .back-btn i {
            margin-right: 8px;
        }
        
        .pedidos-container {
            margin-top: 20px;
        }
        
        .pedido-card {
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, opacity 0.5s, height 0.5s, padding 0.5s, margin 0.5s;
        }
        
        .pedido-card:hover {
            transform: translateY(-5px);
        }
        
        .pedido-header {
            background: #f5f5f5;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .pedido-body {
            padding: 15px;
        }
        
        .pedido-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .pedido-info div {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .pedido-info i {
            color: #666;
        }
        
        .pedido-total {
            font-weight: bold;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .no-pedidos {
            text-align: center;
            padding: 40px 20px;
        }
        
        .no-pedidos i {
            font-size: 60px;
            color: #9e9e9e;
            margin-bottom: 20px;
        }
        
        .no-pedidos h5 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .no-pedidos p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .pedido-acciones {
            margin-top: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .btn-pedido {
            margin: 5px;
        }
        
        .btn.disabled {
            pointer-events: none;
            opacity: 0.7;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animated {
            animation: fadeIn 0.5s ease-out;
        }
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }
    </style>
</head>
<body>
<?php include $root_path . 'frontend/includes/header.php'; ?>
<main class="container" data-user-main>
    <div class="row">
        <div class="col s12">
            <div class="row" style="margin-top: 80px;">
                <div class="col s12">
                    <div class="back-btn-container">
                        <a href="mis_compras.php" class="btn waves-effect waves-light blue back-btn tooltipped" data-position="bottom" data-tooltip="Volver a Mis Compras">
                            <i class="material-icons left">arrow_back</i> Volver
                        </a>
                    </div>
                    <h4 class="page-title">Mis Pedidos</h4>

            <?php if (count($pedidos) > 0): ?>
                <div class="pedidos-container">
                    <?php $delay = 1; foreach ($pedidos as $pedido): ?>
                        <?php
                        $total_dolares = isset($pedido['total_dolares']) && $pedido['total_dolares'] !== null ? floatval($pedido['total_dolares']) : 0.00;
                        $mostrar_total_bs = false;
                        $estado_pago = $estado_pago_exists ? $pedido['estado_pago'] : $GLOBALS['estado_pago_default'];
                        
                        // Si el pedido está finalizado, forzar el estado de pago a 'pagado' si es necesario
                        if ($pedido['tipo'] === 'finalizado' && $estado_pago !== 'pagado') {
                            $estado_pago = 'pagado';
                        }
                        ?>
                        <div class="pedido-card card animated delay-<?php echo min($delay, 5); ?> <?php echo $pedido['tipo'] === 'finalizado' ? 'pedido-finalizado' : ''; ?>">
                            <div class="pedido-header">
                                <div class="pedido-header-left">
                                    <div class="pedido-codigo">
                                        <i class="material-icons"><?php echo $pedido['tipo'] === 'finalizado' ? 'check_circle' : 'receipt'; ?></i>
                                        <strong>Pedido #<?php echo htmlspecialchars($pedido['pedido']); ?></strong>
                                    </div>
                                    <div class="pedido-fecha">
                                        <i class="material-icons tiny">event</i>
                                        <span><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></span>
                                    </div>
                                </div>
                                <div class="pedido-estado">
                                    <span class="badge <?php echo $pedido['tipo'] === 'finalizado' ? 'green' : 'orange'; ?> lighten-5 <?php echo $pedido['tipo'] === 'finalizado' ? 'green-text' : 'orange-text'; ?> text-darken-1">
                                        <?php echo $pedido['tipo'] === 'finalizado' ? 'Completado' : 'Pendiente'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="pedido-body">
                                <div class="pedido-info">
                                    <div>
                                        <i class="material-icons tiny">shopping_basket</i>
                                        <span><?php echo $pedido['total_productos']; ?> producto<?php echo $pedido['total_productos'] > 1 ? 's' : ''; ?></span>
                                    </div>
                                    <div>
                                        <i class="material-icons tiny">attach_money</i>
                                        <span>Total: $<?php echo number_format($total_dolares, 2, ',', '.'); ?></span>
                                        <?php if ($mostrar_total_bs && isset($pedido['total_bolivares']) && $pedido['total_bolivares'] > 0): ?>
                                            <span class="grey-text">(Bs. <?php echo number_format($pedido['total_bolivares'], 2, ',', '.'); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <i class="material-icons tiny">credit_card</i>
                                        <span>Estado de pago: 
                                            <span class="badge <?php echo $estado_pago === 'pagado' ? 'green' : 'red'; ?> lighten-5 <?php echo $estado_pago === 'pagado' ? 'green-text' : 'red-text'; ?> text-darken-1">
                                                <?php echo ucfirst($estado_pago); ?>
                                            </span>
                                        </span>
                                    </div>
                                </div>
                                <div class="pedido-acciones">
                                    <a href="detalle_pedido.php?pedido=<?php echo $pedido['pedido']; ?>" class="btn waves-effect waves-light blue btn-pedido">
                                        <i class="material-icons left">visibility</i> Ver Detalle
                                    </a>
                                    <?php if ($pedido['tipo'] !== 'finalizado'): ?>
                                        <button type="button" class="btn waves-effect waves-light red btn-pedido btn-cancelar-pedido" data-pedido="<?php echo $pedido['pedido']; ?>">
                                            <i class="material-icons left">cancel</i> Cancelar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php $delay++; endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-pedidos animated">
                    <i class="material-icons">receipt_long</i>
                    <h5>No tienes pedidos</h5>
                    <p>Aún no has realizado ningún pedido. ¿Qué tal si exploras nuestro catálogo?</p>
                    <a href="catalogo.php" class="btn waves-effect waves-light blue">
                        <i class="material-icons left">shopping_cart</i> Ver Catálogo
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include $root_path . 'frontend/includes/footer.php'; ?>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Materialize JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<!-- JavaScript personalizado -->
<script src="js/scripts.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar tooltips
        var elems = document.querySelectorAll('.tooltipped');
        M.Tooltip.init(elems);

        // Manejar clic en botón de cancelar pedido
        var botonesCancelar = document.querySelectorAll('.btn-cancelar-pedido');
        botonesCancelar.forEach(function(boton) {
            boton.addEventListener('click', function() {
                var pedidoId = this.getAttribute('data-pedido');
                if (confirm('¿Estás seguro de que deseas cancelar este pedido?')) {
                    // Aquí iría la lógica para cancelar el pedido
                    console.log('Cancelando pedido:', pedidoId);
                    // Por ahora solo mostramos un mensaje
                    M.toast({html: 'Solicitud de cancelación enviada para el pedido #' + pedidoId});
                }
            });
        });

        // Verificar si hay mensajes de devolución para mostrar
        <?php if (!empty($mensajes_devolucion)): ?>
        // Inicializar el modal de notificaciones
        var notificationModal = document.getElementById('notification-modal');
        var notificationModalInstance = M.Modal.init(notificationModal, {
            dismissible: true,
            opacity: 0.5,
            inDuration: 300,
            outDuration: 200,
            startingTop: '4%',
            endingTop: '10%'
        });

        // Mostrar el modal automáticamente al cargar la página
        notificationModalInstance.open();

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
            notificationFloat.style.animation = 'none';
            notificationFloat.style.opacity = '0.7';

            // También aplicar el mismo efecto al texto "Clic aquí"
            if (notificationText) {
                notificationText.style.animation = 'none';
                notificationText.style.opacity = '0.7';
            }
        }
        <?php endif; ?>
    });
</script>
</body>
</html>
