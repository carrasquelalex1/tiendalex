<?php
/**
 * Script para verificar si hay mensajes nuevos
 */

// Incluir el helper de sesiones
require_once '../../helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    echo json_encode([
        'success' => false,
        'message' => 'Debe iniciar sesión para verificar mensajes.'
    ]);
    exit;
}

// Incluir la conexión a la base de datos
if (file_exists('../config/db.php')) {
    require_once '../config/db.php';
} else if (file_exists('backend/config/db.php')) {
    require_once 'backend/config/db.php';
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo encontrar el archivo de conexión a la base de datos.'
    ]);
    exit;
}

// Verificar si las tablas de chat existen
$tabla_chat_existe = false;
$sql_check_table = "SHOW TABLES LIKE 'chat_mensajes'";
$result_check = $conn->query($sql_check_table);
if ($result_check && $result_check->num_rows > 0) {
    $tabla_chat_existe = true;
} else {
    echo json_encode([
        'success' => true,
        'tiene_mensajes_nuevos' => false,
        'cantidad_mensajes' => 0
    ]);
    exit;
}

// Obtener el ID del usuario actual
$usuario_id = $_SESSION['usuario_logueado'];
$es_admin = es_admin();

try {
    if ($es_admin) {
        // Para administradores, verificar todos los chats con mensajes no leídos
        $sql = "SELECT COUNT(*) as total,
                       COUNT(DISTINCT usuario_id) as usuarios_con_mensajes
                FROM chat_mensajes
                WHERE enviado_por = 'usuario' AND leido = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $tiene_mensajes_nuevos = $row['total'] > 0;
        $cantidad_mensajes = $row['total'];
        $usuarios_con_mensajes = $row['usuarios_con_mensajes'];

        // Obtener lista de usuarios con mensajes no leídos
        $sql_usuarios = "SELECT DISTINCT cm.usuario_id, u.nombre_usuario,
                          COUNT(*) as cantidad_mensajes
                         FROM chat_mensajes cm
                         JOIN usuario u ON cm.usuario_id = u.codigo
                         WHERE cm.enviado_por = 'usuario' AND cm.leido = 0
                         GROUP BY cm.usuario_id, u.nombre_usuario
                         ORDER BY MAX(cm.fecha_envio) DESC";
        $result_usuarios = $conn->query($sql_usuarios);

        $usuarios = [];
        while ($usuario = $result_usuarios->fetch_assoc()) {
            $usuarios[] = $usuario;
        }

        echo json_encode([
            'success' => true,
            'tiene_mensajes_nuevos' => $tiene_mensajes_nuevos,
            'cantidad_mensajes' => $cantidad_mensajes,
            'usuarios_con_mensajes' => $usuarios_con_mensajes,
            'usuarios' => $usuarios
        ]);
    } else {
        // Para usuarios normales, verificar sus propios mensajes no leídos
        $sql = "SELECT COUNT(*) as total FROM chat_mensajes
                WHERE usuario_id = ? AND enviado_por = 'admin' AND leido = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $tiene_mensajes_nuevos = $row['total'] > 0;
        $cantidad_mensajes = $row['total'];

        echo json_encode([
            'success' => true,
            'tiene_mensajes_nuevos' => $tiene_mensajes_nuevos,
            'cantidad_mensajes' => $cantidad_mensajes
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar mensajes nuevos: ' . $e->getMessage()
    ]);
}
?>
