<?php
/**
 * Archivo de ayuda para manejar sesiones
 * 
 * Este archivo proporciona funciones para iniciar y gestionar sesiones
 * de manera segura y consistente en toda la aplicación.
 */

/**
 * Inicia una sesión de manera segura
 *
 * Esta función configura opciones de sesión para evitar problemas comunes
 * como permisos de directorio y problemas de seguridad.
 *
 * @return bool Retorna true si la sesión se inició correctamente
 */
function iniciar_sesion_segura() {
    if (session_status() == PHP_SESSION_NONE) {
        // Configurar directorio temporal para guardar sesiones
        ini_set('session.save_path', sys_get_temp_dir());

        // Configurar cookies para sesiones
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);

        // Configurar tiempo de vida de la sesión (2 horas)
        ini_set('session.gc_maxlifetime', 7200);

        // Configurar seguridad de cookies
        $cookie_params = session_get_cookie_params();
        session_set_cookie_params(
            $cookie_params["lifetime"],
            $cookie_params["path"],
            $cookie_params["domain"],
            isset($_SERVER['HTTPS']), // Secure
            true // HttpOnly
        );

        // Iniciar sesión
        return session_start();
    }

    return true;
}

/**
 * Regenera el ID de sesión para prevenir ataques de fijación de sesión
 *
 * @param bool $delete_old_session Si es true, elimina los datos de la sesión anterior
 * @return bool Retorna true si el ID de sesión se regeneró correctamente
 */
function regenerar_sesion($delete_old_session = false) {
    return session_regenerate_id($delete_old_session);
}

/**
 * Verifica si el usuario está logueado
 *
 * @return bool Retorna true si el usuario está logueado
 */
function esta_logueado() {
    // Verificar si alguna de las claves de sesión está definida
    return isset($_SESSION['usuario_id']) || isset($_SESSION['usuario_logueado']);
}

/**
 * Obtiene el ID del usuario logueado
 *
 * @return int|null Retorna el ID del usuario o null si no está logueado
 */
function obtener_usuario_id() {
    if (isset($_SESSION['usuario_id'])) {
        return $_SESSION['usuario_id'];
    } elseif (isset($_SESSION['usuario_logueado'])) {
        // Mantener compatibilidad con código que usa 'usuario_logueado'
        $_SESSION['usuario_id'] = $_SESSION['usuario_logueado'];
        return $_SESSION['usuario_logueado'];
    }
    return null;
}

/**
 * Verifica si el usuario es administrador
 *
 * @return bool Retorna true si el usuario es administrador
 */
function es_admin() {
    global $conn;

    // Verificar si el usuario está logueado
    if (!esta_logueado()) {
        return false;
    }

    // Verificar si el rol está definido en la sesión
    if (isset($_SESSION['rol_codigo'])) {
        return $_SESSION['rol_codigo'] == 1;
    }

    // Si el rol no está definido en la sesión, intentar obtenerlo de la base de datos
    $usuario_id = obtener_usuario_id();
    if (!$usuario_id) {
        return false;
    }
    
    $sql = "SELECT rol_codigo FROM usuario WHERE codigo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Actualizar la sesión con el rol correcto
        $_SESSION['rol_codigo'] = $user['rol_codigo'];
        return $user['rol_codigo'] == 1;
    }

    // Si no se puede obtener el rol, asumir que no es administrador
    return false;
}

/**
 * Verifica si el usuario es cliente
 *
 * @return bool Retorna true si el usuario es cliente
 */
function es_cliente() {
    global $conn;

    // Verificar si el usuario está logueado
    if (!esta_logueado()) {
        return false;
    }

    // Verificar si el rol está definido en la sesión
    if (isset($_SESSION['rol_codigo'])) {
        return $_SESSION['rol_codigo'] != 1;
    }

    // Si el rol no está definido en la sesión, intentar obtenerlo de la base de datos
    $usuario_id = obtener_usuario_id();
    if (!$usuario_id) {
        return false;
    }
    
    $sql = "SELECT rol_codigo FROM usuario WHERE codigo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Actualizar la sesión con el rol correcto
        $_SESSION['rol_codigo'] = $user['rol_codigo'];
        return $user['rol_codigo'] != 1;
    }

    // Si no se puede obtener el rol, asumir que no es cliente
    return false;
}

/**
 * Obtiene el nombre del usuario logueado
 *
 * @return string|null Retorna el nombre del usuario o null si no está logueado
 */
function obtener_nombre_usuario() {
    return esta_logueado() ? ($_SESSION['nombre_usuario'] ?? null) : null;
}

/**
 * Cierra la sesión actual
 *
 * @return bool Retorna true si la sesión se cerró correctamente
 */
function cerrar_sesion() {
    // Limpiar todas las variables de sesión
    $_SESSION = array();

    // Si se desea destruir la cookie de sesión, descomentar las siguientes líneas
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finalmente, destruir la sesión
    return session_destroy();
}
