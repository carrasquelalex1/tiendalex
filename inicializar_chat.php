<?php
/**
 * Script para inicializar las tablas del sistema de chat
 */

// Incluir el helper de sesiones
require_once 'backend/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado y es administrador
if (!esta_logueado() || !es_admin()) {
    echo "No tiene permisos para ejecutar este script.";
    exit;
}

// Incluir el script para crear las tablas
require_once 'backend/chat/crear_tablas_chat.php';

// Redirigir a la página de administración
header("Location: admin_chat.php");
exit;
?>
