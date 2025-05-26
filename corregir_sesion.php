<?php
/**
 * Script para corregir la sesión del usuario
 * 
 * Este script verifica y corrige el rol del usuario en la sesión
 * comparándolo con el rol almacenado en la base de datos.
 */

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivos necesarios
require_once 'backend/config/db.php';
require_once 'backend/session_helper.php';
require_once __DIR__ . '/autoload.php';

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    header("Location: login.php?error=1&message=" . urlencode("Debe iniciar sesión para acceder a esta página."));
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
    $mensaje = "Se ha corregido el rol en la sesión. Ahora puede continuar navegando.";
    $tipo_mensaje = "success";
    
    // Registrar la corrección
    error_log("corregir_sesion.php - Se ha corregido el rol del usuario ID: $usuario_id. Rol anterior: $rol_sesion, Nuevo rol: $rol_bd");
} else {
    $mensaje = "El rol en la sesión ya es correcto. No se requiere ninguna acción.";
    $tipo_mensaje = "info";
}

// Si es un cliente, asegurarse de que tenga un código de pedido
if ($rol_bd != 1 && !isset($_SESSION['codigo_pedido'])) {
    $_SESSION['codigo_pedido'] = generarCodigoPedidoUnico();
    $mensaje .= " Se ha generado un nuevo código de pedido.";
}

// Regenerar el ID de sesión para mayor seguridad
regenerar_sesion(true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrección de Sesión</title>
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
    <div class="container">
        <div class="card">
            <h2>Corrección de Sesión</h2>
            
            <?php if ($tipo_mensaje == "success"): ?>
                <div class="card-panel green lighten-4">
                    <span class="green-text text-darken-4"><?php echo $mensaje; ?></span>
                </div>
            <?php elseif ($tipo_mensaje == "info"): ?>
                <div class="card-panel blue lighten-4">
                    <span class="blue-text text-darken-4"><?php echo $mensaje; ?></span>
                </div>
            <?php endif; ?>
            
            <h4>Información de la Sesión</h4>
            <ul class="collection">
                <li class="collection-item">Usuario: <?php echo $usuario['nombre_usuario']; ?></li>
                <li class="collection-item">ID: <?php echo $usuario_id; ?></li>
                <li class="collection-item">Rol en la base de datos: <?php echo $rol_bd; ?> (<?php echo $rol_bd == 1 ? 'Administrador' : 'Cliente'; ?>)</li>
                <li class="collection-item">Rol en la sesión: <?php echo $_SESSION['rol_codigo']; ?> (<?php echo $_SESSION['rol_codigo'] == 1 ? 'Administrador' : 'Cliente'; ?>)</li>
                <?php if (isset($_SESSION['codigo_pedido'])): ?>
                    <li class="collection-item">Código de pedido: <?php echo $_SESSION['codigo_pedido']; ?></li>
                <?php endif; ?>
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
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        // Redirigir automáticamente después de 5 segundos
        setTimeout(function() {
            window.location.href = 'catalogo.php';
        }, 5000);
    </script>
</body>
</html>
