<?php
/**
 * Inicia el proceso de autenticación con Google
 * 
 * Este script redirige al usuario a la página de inicio de sesión de Google
 */

// Iniciar el buffer de salida para evitar errores de encabezados
ob_start();

// Definir la constante de entorno
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}

// Mostrar errores en desarrollo
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Establecer la zona horaria
date_default_timezone_set('America/Caracas');

// Definir rutas base
define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));


// Incluir el autoloader
require_once BASE_PATH . '/autoload.php';

// Cargar helpers necesarios
loadHelper(['session/session_helper']);

// Verificar funciones requeridas
if (!function_exists('iniciar_sesion_segura')) {
    error_log('Error: Función iniciar_sesion_segura no encontrada');
    die('Error del sistema. Por favor, intente más tarde.');
}

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si la sesión se inició correctamente
if (session_status() !== PHP_SESSION_ACTIVE) {
    error_log('Error: No se pudo iniciar la sesión correctamente');
    die('No se pudo iniciar la sesión. Por favor, intente nuevamente.');
}

// Incluir la configuración de Google
if (!@include(__DIR__ . '/google_config.php')) {
    error_log('Error: No se pudo cargar la configuración de Google');
    die('Error de configuración. Por favor, contacte al administrador.');
}

// Verificar que las variables de configuración estén definidas
if (!isset($google_client_id) || !isset($google_redirect_uri)) {
    error_log('Error: Variables de configuración de Google no definidas');
    error_log('Client ID: ' . ($google_client_id ?? 'No definido'));
    error_log('Redirect URI: ' . ($google_redirect_uri ?? 'No definido'));
    die('Error de configuración de Google. Por favor, contacte al administrador.');
}

// Generar y guardar el estado CSRF
$state = bin2hex(random_bytes(16));
$_SESSION['google_auth_state'] = $state;

// Registrar información de depuración
error_log("GOOGLE LOGIN - Estado CSRF generado: " . $state);

// Obtener la URL de autenticación con el estado
$auth_url = getGoogleAuthUrl($state);

// Registrar la URL de autenticación
error_log("GOOGLE LOGIN - URL de autenticación: " . $auth_url);

// Redirigir a la URL de autenticación de Google
header('Location: ' . $auth_url);

// Registrar la redirección
error_log("GOOGLE LOGIN - Redirigiendo a: " . $auth_url);

exit;
?>
