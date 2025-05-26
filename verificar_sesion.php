<?php
// Script para verificar el estado de la sesión y los roles

// Incluir archivos necesarios
require_once 'backend/session_helper.php';
require_once 'backend/config/db.php';
require_once __DIR__ . '/autoload.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Función para mostrar información de depuración
function debug_info($title, $data) {
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9;'>";
    echo "<h3 style='margin-top: 0;'>{$title}</h3>";
    echo "<pre style='margin: 0;'>";
    print_r($data);
    echo "</pre>";
    echo "</div>";
}

// Verificar la estructura de la tabla usuario
function get_user_table_structure() {
    global $conn;
    $result = $conn->query("DESCRIBE usuario");
    $structure = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $structure[] = $row;
        }
    }
    
    return $structure;
}

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

// Verificar si la tabla rol existe
function table_exists($table_name) {
    global $conn;
    
    $result = $conn->query("SHOW TABLES LIKE '{$table_name}'");
    return $result->num_rows > 0;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Sesión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .container { margin-top: 30px; }
        .card { padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>Información de Sesión y Roles</h2>
            
            <h4>Estado de la Sesión</h4>
            <?php debug_info("Variables de Sesión", $_SESSION); ?>
            
            <h4>Funciones de Verificación</h4>
            <ul class="collection">
                <li class="collection-item">esta_logueado(): <?php echo esta_logueado() ? 'true' : 'false'; ?></li>
                <li class="collection-item">es_admin(): <?php echo es_admin() ? 'true' : 'false'; ?></li>
                <li class="collection-item">es_cliente(): <?php echo es_cliente() ? 'true' : 'false'; ?></li>
                <li class="collection-item">obtener_usuario_id(): <?php echo obtener_usuario_id() ?: 'null'; ?></li>
                <li class="collection-item">obtener_nombre_usuario(): <?php echo obtener_nombre_usuario() ?: 'null'; ?></li>
            </ul>
            
            <?php if (table_exists('usuario')): ?>
                <h4>Estructura de la Tabla Usuario</h4>
                <?php debug_info("Campos de la Tabla", get_user_table_structure()); ?>
            <?php endif; ?>
            
            <?php if (table_exists('rol')): ?>
                <h4>Información de Roles</h4>
                <?php debug_info("Roles Disponibles", get_roles_info()); ?>
            <?php endif; ?>
            
            <h4>Información del Usuario Actual</h4>
            <?php 
            $user_info = get_current_user_info();
            if ($user_info) {
                debug_info("Datos del Usuario", $user_info);
            } else {
                echo "<p>No hay usuario logueado o no se pudo obtener la información.</p>";
            }
            ?>
            
            <div class="center-align" style="margin-top: 20px;">
                <a href="index.php" class="waves-effect waves-light btn">
                    <i class="material-icons left">arrow_back</i>Volver al Inicio
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
