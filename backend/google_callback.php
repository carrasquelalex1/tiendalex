<?php
/**
 * Callback para la autenticación con Google
 * Procesa la respuesta de Google después de que el usuario ha iniciado sesión
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

// Configurar el registro de errores
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/google_callback_errors.log');

// Registrar información de depuración
error_log("GOOGLE CALLBACK - Iniciando callback de Google");

// Incluir el autoloader
require_once BASE_PATH . '/autoload.php';

// Incluir configuración de la base de datos
require_once BASE_PATH . '/config/db.php';

// Incluir funciones del carrito
require_once BASE_PATH . '/helpers/carrito/carrito_functions.php';

error_log("GOOGLE CALLBACK - Configuración de base de datos y funciones del carrito cargadas");

// Verificar que la conexión a la base de datos se haya establecido correctamente
if (!isset($conn) || !($conn instanceof mysqli)) {
    $errorMsg = 'Error: No se pudo establecer la conexión a la base de datos';
    error_log($errorMsg);
    redirectWithError('system_error', $errorMsg);
}

// Cargar helpers necesarios
loadHelper(['session/session_helper', 'carrito/carrito_functions']);

// Verificar funciones requeridas
$required_functions = ['iniciar_sesion_segura', 'generarCodigoPedidoUnico'];
foreach ($required_functions as $function) {
    if (!function_exists($function)) {
        error_log("Error: Función requerida no encontrada: $function");
        redirectWithError('system_error', 'Función del sistema no disponible: ' . $function);
    }
}

// Incluir configuración de Google
if (!@include(__DIR__ . '/google_config.php')) {
    error_log('Error: No se pudo cargar la configuración de Google');
    redirectWithError('google_config_error', 'Error en la configuración de Google.');
}

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si la sesión se inició correctamente
if (session_status() !== PHP_SESSION_ACTIVE) {
    error_log('Error: No se pudo iniciar la sesión correctamente');
    redirectWithError('session_error', 'No se pudo iniciar la sesión.');
}

/**
 * Redirige al usuario a la página de error correspondiente
 * 
 * @param string $errorCode Código de error
 * @param string $message Mensaje de error opcional
 * @return void
 */
function redirectWithError($errorCode, $message = '') {
    $url = BASE_PATH . '/login.php?error=' . urlencode($errorCode);
    if (!empty($message)) {
        $url .= '&message=' . urlencode($message);
    }
    
    // Limpiar cualquier salida antes de redirigir
    if (ob_get_length()) ob_clean();
    
    header('Location: ' . $url);
    exit;
}

// LOGS DE DEPURACIÓN INICIALES
error_log("\n" . str_repeat("=", 80));
error_log("NUEVA SOLICITUD GOOGLE CALLBACK - " . date('Y-m-d H:i:s'));
error_log("GOOGLE CALLBACK - _GET: " . print_r($_GET, true));
error_log("GOOGLE CALLBACK - _SESSION (inicio): " . print_r($_SESSION, true));

// Verificar si hay un error en la respuesta de Google
if (isset($_GET['error'])) {
    $errorMsg = isset($_GET['error_description']) ? $_GET['error_description'] : $_GET['error'];
    error_log("Error en la autenticación de Google: " . $errorMsg);
    redirectWithError('google_auth', $errorMsg);
}

// Verificar si hay un error en la respuesta de Google
if (isset($_GET['error'])) {
    error_log("Error en la autenticación de Google: " . $_GET['error']);
    header('Location: ../login.php?error=google_auth&message=' . urlencode($_GET['error']));
    exit;
}

// Verificar si se recibió el código de autorización y el estado
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    error_log("Falta código o estado en la respuesta de Google");
    header('Location: ../login.php?error=google_missing_params');
    exit;
}

// Verificar que el estado coincida (protección contra CSRF)
if (empty($_SESSION['google_auth_state']) || empty($_GET['state']) || $_GET['state'] !== $_SESSION['google_auth_state']) {
    error_log("Estado no válido en la respuesta de Google. Estado recibido: " . 
             ($_GET['state'] ?? 'no definido') . 
             ", Estado esperado: " . 
             ($_SESSION['google_auth_state'] ?? 'no definido'));
    error_log("Datos de sesión completos: " . print_r($_SESSION, true));
    header('Location: ../login.php?error=google_csrf');
    exit;
}

// Limpiar el estado después de usarlo para evitar reutilización
unset($_SESSION['google_auth_state']);

// Obtener el token de acceso
$token_data = getGoogleAccessToken($_GET['code']);
error_log("GOOGLE CALLBACK - token_data: " . print_r($token_data, true));

if (!$token_data || !isset($token_data['access_token'])) {
    error_log("No se pudo obtener el token de acceso de Google");
    header('Location: ../login.php?error=google_token');
    exit;
}

// Obtener información del usuario
$user_info = getGoogleUserInfo($token_data['access_token']);
error_log("GOOGLE CALLBACK - user_info: " . print_r($user_info, true));

if (!$user_info) {
    error_log("No se pudo obtener la información del usuario de Google");
    header('Location: ../login.php?error=google_user_info');
    exit;
}

// Extraer información del usuario
$email = $user_info['email'] ?? null;
$given_name = $user_info['given_name'] ?? '';
$family_name = $user_info['family_name'] ?? '';
$name = $user_info['name'] ?? '';
$picture = $user_info['picture'] ?? '';

// Verificar que tengamos al menos el correo electrónico
if (empty($email)) {
    error_log("No se pudo obtener el correo electrónico del usuario de Google");
    header('Location: ../login.php?error=google_email');
    exit;
}

// Conectar a la base de datos
require_once __DIR__ . '/../backend/config/db.php';

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    // Verificar si el usuario ya existe
    $stmt = $conn->prepare("SELECT * FROM usuario WHERE correo_electronico = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // El usuario ya existe, iniciar sesión
        $user = $result->fetch_assoc();

        $_SESSION['usuario_logueado'] = $user['codigo'];
        $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
        $_SESSION['rol_codigo'] = $user['rol_codigo'];

        // Si es un cliente, generar código de pedido
        if ($user['rol_codigo'] != 1 && empty($_SESSION['codigo_pedido'])) {
            // Generar código de pedido único usando la función global
            $_SESSION['codigo_pedido'] = generarCodigoPedidoUnico();
        }

        // Log de depuración de sesión
        error_log("GOOGLE CALLBACK - SESSION tras login EXISTENTE: " . print_r($_SESSION, true));

        // Redirigir según el rol
        if ($user['rol_codigo'] == 1) { // Administrador
            header("Location: ../catalogo.php");
        } else { // Cliente u otros roles
            header("Location: ../index.php");
        }
        exit();
    } else {
        // El usuario no existe, crear una nueva cuenta
        // Generar un nombre de usuario único basado en el nombre o correo
        $base_username = $given_name ? strtolower($given_name) : strtolower(explode('@', $email)[0]);
        $username = $base_username;
        $counter = 1;
        
        // Verificar si el nombre de usuario ya existe
        $stmt = $conn->prepare("SELECT codigo FROM usuario WHERE nombre_usuario = ?");
        while (true) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                break;
            }
            
            $username = $base_username . $counter;
            $counter++;
        }
        
        // Crear la contraseña aleatoria
        $password_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        
        // Insertar el nuevo usuario
        $rol_codigo = 2; // Código para cliente
        $stmt = $conn->prepare("INSERT INTO usuario (nombre_usuario, correo_electronico, contrasena, rol_codigo, google_user_id, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("sssis", $username, $email, $password_hash, $rol_codigo, $user_info['sub']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al crear el usuario: " . $stmt->error);
        }
        
        $user_id = $stmt->insert_id;
        
        // Iniciar sesión con el nuevo usuario
        $_SESSION['usuario_logueado'] = $user_id;
        $_SESSION['nombre_usuario'] = $username;
        $_SESSION['rol_codigo'] = $rol_codigo;
        $_SESSION['codigo_pedido'] = generarCodigoPedidoUnico();
        
        // Log de depuración de sesión
        error_log("GOOGLE CALLBACK - SESSION tras NUEVO registro: " . print_r($_SESSION, true));
        
        // Redirigir a la página principal
        header("Location: ../index.php?new_user=1");
        exit();
    }
    
} catch (Exception $e) {
    error_log("Error en google_callback: " . $e->getMessage());
    header('Location: ../login.php?error=google_db&message=' . urlencode($e->getMessage()));
    exit;
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

// Verificar si hay un error en la respuesta de Google
if (isset($_GET['error'])) {
    error_log("Error en la autenticación de Google: " . $_GET['error']);
    header('Location: ../login.php?error=google');
    exit;
}

// Verificar si se recibió el código de autorización y el estado
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    error_log("Falta código o estado en la respuesta de Google");
    header('Location: ../login.php?error=google');
    exit;
}

// Verificar que el estado coincida (protección contra CSRF)
if (!isset($_SESSION['google_auth_state']) || empty($_SESSION['google_auth_state']) || !isset($_GET['state']) || $_GET['state'] !== $_SESSION['google_auth_state']) {
    error_log("Estado no válido en la respuesta de Google. Estado recibido: " . ($_GET['state'] ?? 'no definido') . ", Estado esperado: " . ($_SESSION['google_auth_state'] ?? 'no definido'));
    header('Location: ../login.php?error=google_csrf');
    exit;
}

// Limpiar el estado después de usarlo para evitar reutilización
unset($_SESSION['google_auth_state']);

// Obtener el token de acceso
$token_data = getGoogleAccessToken($_GET['code']);
error_log("GOOGLE CALLBACK - token_data: " . print_r($token_data, true));
if (!$token_data || !isset($token_data['access_token'])) {
    error_log("No se pudo obtener el token de acceso de Google");
    header('Location: ../login.php?error=google');
    exit;
}

// Obtener información del usuario
$user_info = getGoogleUserInfo($token_data['access_token']);
error_log("GOOGLE CALLBACK - user_info: " . print_r($user_info, true));
if (!$user_info || !isset($user_info['sub'])) {
    error_log("No se pudo obtener la información del usuario de Google");
    header('Location: ../login.php?error=google');
    exit;
}

// Extraer la información necesaria
$google_id = $user_info['sub'];
$email = isset($user_info['email']) ? $user_info['email'] : '';
$name = isset($user_info['name']) ? $user_info['name'] : '';
$given_name = isset($user_info['given_name']) ? $user_info['given_name'] : '';

// Verificar si el usuario ya existe en la base de datos
$stmt = $conn->prepare("SELECT * FROM usuario WHERE google_user_id = ?");
$stmt->bind_param("s", $google_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // El usuario ya existe, iniciar sesión
    $user = $result->fetch_assoc();

    $_SESSION['usuario_logueado'] = $user['codigo'];
    $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
    $_SESSION['rol_codigo'] = $user['rol_codigo'];

    // Si es un cliente, generar código de pedido
    if ($user['rol_codigo'] != 1) {
        // Verificar si ya tiene un código de pedido activo
        if (empty($_SESSION['codigo_pedido'])) {
            // Generar código de pedido único usando la función global
            $_SESSION['codigo_pedido'] = generarCodigoPedidoUnico();
        }
    }

    // Log de depuración de sesión
    error_log("GOOGLE CALLBACK - SESSION tras login EXISTENTE: " . print_r($_SESSION, true));

    // Redirigir según el rol
    if ($user['rol_codigo'] == 1) { // Administrador
        header("Location: ../catalogo.php");
    } else { // Cliente u otros roles
        header("Location: ../index.php");
    }
    exit;
} else {
    // El usuario no existe, crear una nueva cuenta

    // Generar un nombre de usuario único basado en el nombre o correo
    $base_username = $given_name ? strtolower($given_name) : strtolower(explode('@', $email)[0]);
    $username = $base_username;
    $counter = 1;

    // Verificar si el nombre de usuario ya existe
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuario WHERE nombre_usuario = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Si el nombre de usuario ya existe, agregar un número al final
    while ($row['count'] > 0) {
        $username = $base_username . $counter;
        $counter++;

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
    }

    // Insertar el nuevo usuario
    $stmt = $conn->prepare("INSERT INTO usuario (nombre_usuario, correo_electronico, google_user_id, rol_codigo, created_at, updated_at) VALUES (?, ?, ?, 2, NOW(), NOW())");
    $stmt->bind_param("sss", $username, $email, $google_id);

    if ($stmt->execute()) {
        // Registro exitoso, iniciar sesión
        $_SESSION['usuario_logueado'] = $conn->insert_id;
        $_SESSION['nombre_usuario'] = $username;
        $_SESSION['rol_codigo'] = 2; // Cliente por defecto
        $_SESSION['codigo_pedido'] = generarCodigoPedidoUnico();

        // Log de depuración de sesión
        error_log("GOOGLE CALLBACK - SESSION tras login NUEVO: " . print_r($_SESSION, true));

        header("Location: ../index.php");
        exit;
    } else {
        // Error al insertar
        error_log("Error al insertar usuario de Google: " . $stmt->error);
        header("Location: ../login.php?error=db");
        exit;
    }
}
?>
