<?php
require_once __DIR__ . '/autoload.php';
/**
 * Script para corregir la sesión del usuario Gabriel
 * 
 * Este script verifica y corrige la sesión del usuario Gabriel
 * para solucionar el problema de agregar productos al carrito
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

// Obtener información del usuario desde la base de datos
$usuario_id = $_SESSION['usuario_logueado'];
$nombre_usuario = isset($_SESSION['nombre_usuario']) ? $_SESSION['nombre_usuario'] : '';

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

// Verificar si el usuario es Gabriel
if ($usuario['nombre_usuario'] !== 'gabriel') {
    header("Location: index.php?error=1&message=" . urlencode("Esta página es solo para el usuario Gabriel."));
    exit;
}

// Inicializar variables para mensajes
$mensajes = [];
$tipo_mensaje = "success";

// Verificar y corregir el rol en la sesión
if (!isset($_SESSION['rol_codigo']) || $_SESSION['rol_codigo'] != $usuario['rol_codigo']) {
    $_SESSION['rol_codigo'] = $usuario['rol_codigo'];
    $mensajes[] = "Se ha corregido el rol en la sesión.";
    error_log("corregir_sesion_gabriel.php - Se ha corregido el rol del usuario Gabriel. Nuevo rol: " . $_SESSION['rol_codigo']);
}

// Verificar y corregir el código de pedido
if (!isset($_SESSION['codigo_pedido'])) {
    $_SESSION['codigo_pedido'] = generarCodigoPedidoUnico();
    $mensajes[] = "Se ha generado un nuevo código de pedido.";
    error_log("corregir_sesion_gabriel.php - Se ha generado un nuevo código de pedido para Gabriel: " . $_SESSION['codigo_pedido']);
} else {
    // Verificar si el código de pedido es válido
    $codigo_pedido = $_SESSION['codigo_pedido'];
    
    // Verificar si el código pertenece al usuario actual
    $sql = "SELECT COUNT(*) as count FROM carrito WHERE pedido = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $codigo_pedido, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $mensajes[] = "El código de pedido actual es válido y pertenece a su carrito.";
    } else {
        // Generar un nuevo código de pedido
        $_SESSION['codigo_pedido'] = generarCodigoPedidoUnico();
        $mensajes[] = "Se ha generado un nuevo código de pedido porque el anterior no pertenecía a su carrito.";
        error_log("corregir_sesion_gabriel.php - Se ha generado un nuevo código de pedido para Gabriel. Código anterior: $codigo_pedido, Nuevo código: " . $_SESSION['codigo_pedido']);
    }
}

// Regenerar el ID de sesión para mayor seguridad
regenerar_sesion(true);

// Forzar la escritura de la sesión
session_write_close();
session_start();

// Verificar si la sesión se guardó correctamente
if (isset($_SESSION['usuario_logueado']) && isset($_SESSION['rol_codigo']) && isset($_SESSION['codigo_pedido'])) {
    $mensajes[] = "La sesión se ha guardado correctamente.";
} else {
    $mensajes[] = "Error al guardar la sesión. Por favor, inténtelo de nuevo.";
    $tipo_mensaje = "error";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrección de Sesión - Gabriel</title>
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
            <h2>Corrección de Sesión para Gabriel</h2>
            
            <?php if ($tipo_mensaje == "success"): ?>
                <div class="card-panel green lighten-4">
                    <span class="green-text text-darken-4">
                        <?php foreach ($mensajes as $mensaje): ?>
                            <p><i class="material-icons tiny">check_circle</i> <?php echo $mensaje; ?></p>
                        <?php endforeach; ?>
                    </span>
                </div>
            <?php else: ?>
                <div class="card-panel red lighten-4">
                    <span class="red-text text-darken-4">
                        <?php foreach ($mensajes as $mensaje): ?>
                            <p><i class="material-icons tiny">error</i> <?php echo $mensaje; ?></p>
                        <?php endforeach; ?>
                    </span>
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
