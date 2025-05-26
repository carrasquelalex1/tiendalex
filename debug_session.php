<?php
// Script para depurar la sesión y los roles

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivos necesarios
require_once 'backend/config/db.php';
require_once 'backend/session_helper.php';
require_once __DIR__ . '/autoload.php';

// Función para mostrar información de depuración
function debug_info($title, $data) {
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9;'>";
    echo "<h3 style='margin-top: 0;'>{$title}</h3>";
    echo "<pre style='margin: 0;'>";
    print_r($data);
    echo "</pre>";
    echo "</div>";
}

// Verificar si el usuario está logueado
$usuario_logueado = esta_logueado();
$es_admin = es_admin();
$es_cliente = es_cliente();

// Obtener información del usuario actual
function get_current_user_info() {
    global $conn;
    
    if (!isset($_SESSION['usuario_logueado'])) {
        return null;
    }
    
    $user_id = $_SESSION['usuario_logueado'];
    $stmt = $conn->prepare("SELECT * FROM usuario WHERE codigo = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Obtener información de roles
function get_roles_info() {
    global $conn;
    
    $result = $conn->query("SELECT * FROM rol");
    $roles = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
    }
    
    return $roles;
}

// Obtener información del usuario actual
$user_info = get_current_user_info();

// Obtener información de roles
$roles_info = get_roles_info();

// Forzar la actualización de la sesión si se solicita
if (isset($_GET['update_role']) && $usuario_logueado && $user_info) {
    $_SESSION['rol_codigo'] = $user_info['rol_codigo'];
    header("Location: debug_session.php?updated=1");
    exit;
}

// Forzar el cierre de sesión si se solicita
if (isset($_GET['logout'])) {
    cerrar_sesion();
    header("Location: debug_session.php?logged_out=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depuración de Sesión</title>
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
            <h2>Depuración de Sesión y Roles</h2>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="card-panel green lighten-4">
                    <span class="green-text text-darken-4">La sesión ha sido actualizada correctamente.</span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['logged_out'])): ?>
                <div class="card-panel blue lighten-4">
                    <span class="blue-text text-darken-4">Se ha cerrado la sesión correctamente.</span>
                </div>
            <?php endif; ?>
            
            <h4>Estado de la Sesión</h4>
            <?php debug_info("Variables de Sesión", $_SESSION); ?>
            
            <h4>Funciones de Verificación</h4>
            <ul class="collection">
                <li class="collection-item">esta_logueado(): <?php echo $usuario_logueado ? 'true' : 'false'; ?></li>
                <li class="collection-item">es_admin(): <?php echo $es_admin ? 'true' : 'false'; ?></li>
                <li class="collection-item">es_cliente(): <?php echo $es_cliente ? 'true' : 'false'; ?></li>
            </ul>
            
            <?php if ($roles_info): ?>
                <h4>Roles Disponibles</h4>
                <?php debug_info("Roles en la Base de Datos", $roles_info); ?>
            <?php endif; ?>
            
            <?php if ($user_info): ?>
                <h4>Información del Usuario Actual</h4>
                <?php debug_info("Datos del Usuario", $user_info); ?>
                
                <?php if ($user_info['rol_codigo'] != $_SESSION['rol_codigo']): ?>
                    <div class="card-panel red lighten-4">
                        <span class="red-text text-darken-4">
                            <strong>¡ADVERTENCIA!</strong> El rol en la sesión (<?php echo $_SESSION['rol_codigo']; ?>) 
                            no coincide con el rol en la base de datos (<?php echo $user_info['rol_codigo']; ?>).
                        </span>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="card-panel yellow lighten-4">
                    <span class="orange-text text-darken-4">No hay usuario logueado o no se pudo obtener la información.</span>
                </div>
            <?php endif; ?>
            
            <div class="action-buttons">
                <?php if ($usuario_logueado): ?>
                    <a href="debug_session.php?update_role=1" class="waves-effect waves-light btn orange">
                        <i class="material-icons left">refresh</i>Actualizar Rol en Sesión
                    </a>
                    <a href="debug_session.php?logout=1" class="waves-effect waves-light btn red">
                        <i class="material-icons left">exit_to_app</i>Cerrar Sesión
                    </a>
                <?php else: ?>
                    <a href="login.php" class="waves-effect waves-light btn blue">
                        <i class="material-icons left">login</i>Iniciar Sesión
                    </a>
                <?php endif; ?>
                <a href="index.php" class="waves-effect waves-light btn grey">
                    <i class="material-icons left">arrow_back</i>Volver al Inicio
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
