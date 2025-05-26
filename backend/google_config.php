<?php
/**
 * Configuración para la autenticación con Google
 *
 * Este archivo contiene las credenciales y configuraciones necesarias para la autenticación con Google OAuth
 */

// Definir el protocolo (http o https)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url = "$protocol://$host";

// Credenciales de Google OAuth
// Nota: En un entorno de producción, estas credenciales deberían estar en variables de entorno o en un archivo seguro
$google_client_id = 'el que le asignes';
$google_client_secret = 'el que le asignes';
$google_redirect_uri = $base_url . '/tiendalex2/backend/google_callback.php';

// Ámbitos (scopes) requeridos para la autenticación
// Estos ámbitos deben estar habilitados en la consola de desarrolladores de Google
$google_scopes = [
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/userinfo.profile',
    'openid'
];

// URL base para la autenticación de Google
$google_auth_url = 'https://accounts.google.com/o/oauth2/v2/auth';

// URL para obtener el token de acceso
$google_token_url = 'https://oauth2.googleapis.com/token';

// URL para obtener información del usuario
$google_userinfo_url = 'https://www.googleapis.com/oauth2/v3/userinfo';

// Definir la constante de entorno
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}

// Mostrar errores en desarrollo
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Registrar la URL de redirección para depuración
    error_log("Google Redirect URI: " . $google_redirect_uri);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Establecer la zona horaria
date_default_timezone_set('America/Caracas');

// Verificar si las credenciales están configuradas
if (empty($google_client_id) || empty($google_client_secret)) {
    error_log('Error: Las credenciales de Google no están configuradas correctamente');
    if (ENVIRONMENT === 'development') {
        die('Error: Las credenciales de Google no están configuradas correctamente');
    } else {
        die('Error de configuración. Por favor, contacte al administrador.');
    }
}

/**
 * Genera la URL de autenticación de Google
 *
 * @param string $state Estado CSRF (opcional, se generará uno si no se proporciona)
 * @return string URL para redirigir al usuario a la página de inicio de sesión de Google
 */
function getGoogleAuthUrl($state = null) {
    global $google_client_id, $google_redirect_uri, $google_scopes, $google_auth_url;
    
    // Registrar información de depuración
    error_log("GOOGLE CONFIG - Generando URL de autenticación");
    error_log("GOOGLE CONFIG - Client ID: " . $google_client_id);
    error_log("GOOGLE CONFIG - Redirect URI: " . $google_redirect_uri);
    error_log("GOOGLE CONFIG - Scopes: " . print_r($google_scopes, true));
    
    // Si no se proporciona un estado, generar uno nuevo
    if ($state === null) {
        $state = bin2hex(random_bytes(16));
        error_log("GOOGLE CONFIG - Estado generado: " . $state);
    } else {
        error_log("GOOGLE CONFIG - Estado proporcionado: " . $state);
    }
    
    // Construir la URL de autorización
    $params = [
        'client_id' => $google_client_id,
        'redirect_uri' => $google_redirect_uri,
        'response_type' => 'code',
        'scope' => implode(' ', $google_scopes),
        'state' => $state,
        'access_type' => 'offline',
        'prompt' => 'consent' // Forzar la obtención de un token de actualización
    ];
    
    $authUrl = $google_auth_url . '?' . http_build_query($params);
    error_log("GOOGLE CONFIG - URL de autenticación generada: " . $authUrl);
    
    return $authUrl;
}

/**
 * Intercambia el código de autorización por un token de acceso
 *
 * @param string $code Código de autorización recibido de Google
 * @return array|null Token de acceso y otra información, o null si hay un error
 */
function getGoogleAccessToken($code) {
    global $google_token_url, $google_client_id, $google_client_secret, $google_redirect_uri;

    $params = [
        'client_id' => $google_client_id,
        'client_secret' => $google_client_secret,
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $google_redirect_uri
    ];

    $ch = curl_init($google_token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Error al obtener token de Google: " . $error);
        return null;
    }

    return json_decode($response, true);
}

/**
 * Obtiene la información del usuario de Google
 *
 * @param string $access_token Token de acceso obtenido de Google
 * @return array|null Información del usuario, o null si hay un error
 */
function getGoogleUserInfo($access_token) {
    global $google_userinfo_url;

    $ch = curl_init($google_userinfo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Error al obtener información de usuario de Google: " . $error);
        return null;
    }

    return json_decode($response, true);
}
?>
