<?php
require_once __DIR__ . '/autoload.php';
/**
 * Script para corregir el código de pedido
 * 
 * Este script verifica y corrige el código de pedido para usuarios clientes
 */

// Incluir archivos necesarios
require_once 'backend/config/db.php';
require_once 'backend/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    header("Location: login.php?error=1&message=" . urlencode("Debe iniciar sesión para acceder a esta página."));
    exit;
}

// Verificar si el usuario es cliente
if (!es_cliente()) {
    header("Location: index.php?error=1&message=" . urlencode("Esta página es solo para clientes."));
    exit;
}

// Obtener información del usuario desde la base de datos
$usuario_id = $_SESSION['usuario_logueado'];
$sql = "SELECT * FROM usuario WHERE codigo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // El usuario no existe en la base de datos
    cerrar_sesion();
    header("Location: login.php?error=1&message=" . urlencode("Usuario no encontrado. Por favor, inicie sesión nuevamente."));
    exit;
}

$usuario = $result->fetch_assoc();

// Verificar si el rol en la sesión coincide con el rol en la base de datos
$rol_sesion = isset($_SESSION['rol_codigo']) ? $_SESSION['rol_codigo'] : null;
$rol_bd = $usuario['rol_codigo'];

$mensaje = "";
$tipo_mensaje = "";

if ($rol_sesion != $rol_bd) {
    // Corregir el rol en la sesión
    $_SESSION['rol_codigo'] = $rol_bd;
    $mensaje = "Se ha corregido el rol en la sesión. ";
    $tipo_mensaje = "success";
    
    // Registrar la corrección
    error_log("corregir_codigo_pedido.php - Se ha corregido el rol del usuario ID: $usuario_id. Rol anterior: $rol_sesion, Nuevo rol: $rol_bd");
}

// Verificar si el usuario tiene un código de pedido
$codigo_pedido_actual = isset($_SESSION['codigo_pedido']) ? $_SESSION['codigo_pedido'] : null;

if (!$codigo_pedido_actual) {
    // Generar un nuevo código de pedido
    $codigo_pedido_nuevo = generarCodigoPedidoUnico();
    $_SESSION['codigo_pedido'] = $codigo_pedido_nuevo;
    
    $mensaje .= "Se ha generado un nuevo código de pedido.";
    $tipo_mensaje = "success";
    
    // Registrar la generación del código
    error_log("corregir_codigo_pedido.php - Se ha generado un nuevo código de pedido para el usuario ID: $usuario_id. Código: $codigo_pedido_nuevo");
} else {
    // Verificar si el código de pedido es válido
    if (!codigoPedidoExiste($codigo_pedido_actual)) {
        $mensaje .= "El código de pedido actual es válido.";
        $tipo_mensaje = $tipo_mensaje ?: "info";
    } else {
        // Verificar si el código pertenece al usuario actual
        $sql = "SELECT COUNT(*) as count FROM carrito WHERE pedido = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $codigo_pedido_actual, $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $mensaje .= "El código de pedido actual es válido y pertenece a su carrito.";
            $tipo_mensaje = $tipo_mensaje ?: "info";
        } else {
            // Generar un nuevo código de pedido
            $codigo_pedido_nuevo = generarCodigoPedidoUnico();
            $_SESSION['codigo_pedido'] = $codigo_pedido_nuevo;
            
            $mensaje .= "Se ha generado un nuevo código de pedido porque el anterior no pertenecía a su carrito.";
            $tipo_mensaje = "warning";
            
            // Registrar la generación del código
            error_log("corregir_codigo_pedido.php - Se ha generado un nuevo código de pedido para el usuario ID: $usuario_id. Código anterior: $codigo_pedido_actual, Nuevo código: $codigo_pedido_nuevo");
        }
    }
}

// Regenerar el ID de sesión para mayor seguridad
regenerar_sesion(true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrección de Código de Pedido</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .container { margin-top: 30px; }
        .card { padding: 20px; }
        .action-buttons { margin-top: 20px; }
        .action-buttons a { margin-right: 10px; }
    </style>
</head>
<body>
    <?php include 'frontend/includes/header.php'; ?>
    
    <div class="container">
        <div class="card">
            <h2>Corrección de Código de Pedido</h2>
            
            <?php if ($tipo_mensaje == "success"): ?>
                <div class="card-panel green lighten-4">
                    <span class="green-text text-darken-4"><?php echo $mensaje; ?></span>
                </div>
            <?php elseif ($tipo_mensaje == "info"): ?>
                <div class="card-panel blue lighten-4">
                    <span class="blue-text text-darken-4"><?php echo $mensaje; ?></span>
                </div>
            <?php elseif ($tipo_mensaje == "warning"): ?>
                <div class="card-panel orange lighten-4">
                    <span class="orange-text text-darken-4"><?php echo $mensaje; ?></span>
                </div>
            <?php endif; ?>
            
            <h4>Información de la Sesión</h4>
            <ul class="collection">
                <li class="collection-item">Usuario: <?php echo $usuario['nombre_usuario']; ?></li>
                <li class="collection-item">ID: <?php echo $usuario_id; ?></li>
                <li class="collection-item">Rol: <?php echo $_SESSION['rol_codigo'] == 1 ? 'Administrador' : 'Cliente'; ?></li>
                <li class="collection-item">Código de pedido: <?php echo $_SESSION['codigo_pedido']; ?></li>
            </ul>
            
            <div class="action-buttons">
                <a href="catalogo.php" class="waves-effect waves-light btn blue">
                    <i class="material-icons left">view_list</i>Ir al Catálogo
                </a>
                <a href="index.php" class="waves-effect waves-light btn grey">
                    <i class="material-icons left">home</i>Ir al Inicio
                </a>
                <a href="debug_session.php" class="waves-effect waves-light btn orange">
                    <i class="material-icons left">bug_report</i>Depurar Sesión
                </a>
            </div>
        </div>
    </div>
    
    <?php include 'frontend/includes/footer.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        // Redirigir automáticamente después de 5 segundos
        setTimeout(function() {
            window.location.href = 'catalogo.php';
        }, 5000);
    </script>
</body>
</html>
