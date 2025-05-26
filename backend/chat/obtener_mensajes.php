<?php
/**
 * Script para obtener los mensajes de chat
 */

// Incluir el helper de sesiones
require_once '../session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    echo json_encode([
        'success' => false,
        'message' => 'Debe iniciar sesión para ver mensajes.'
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

// Obtener el ID del usuario actual
$usuario_id = $_SESSION['usuario_logueado'];
$es_admin = es_admin();

// Si es un administrador y se proporciona un usuario_id, usar ese ID
if ($es_admin && isset($_GET['usuario_id'])) {
    $chat_usuario_id = $_GET['usuario_id'];
} else {
    $chat_usuario_id = $usuario_id;
}

// Obtener el último mensaje visto (opcional)
$ultimo_mensaje_id = isset($_GET['ultimo_mensaje_id']) ? (int)$_GET['ultimo_mensaje_id'] : 0;

try {
    // Verificar si las tablas de chat existen
    $tabla_chat_existe = false;
    $sql_check_table = "SHOW TABLES LIKE 'chat_mensajes'";
    $result_check = $conn->query($sql_check_table);
    if ($result_check && $result_check->num_rows > 0) {
        $tabla_chat_existe = true;
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Las tablas de chat no existen. Por favor, ejecute el script de creación de tablas.'
        ]);
        exit;
    }

    // Consulta para obtener los mensajes
    if ($es_admin) {
        // Para administradores, obtener mensajes del usuario específico
        $sql = "SELECT cm.id, cm.usuario_id, cm.admin_id, cm.mensaje, cm.leido,
                       cm.fecha_envio, cm.enviado_por, u.nombre_usuario as nombre_usuario
                FROM chat_mensajes cm
                JOIN usuario u ON cm.usuario_id = u.codigo
                WHERE cm.usuario_id = ? AND cm.id > ?
                ORDER BY cm.fecha_envio ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $chat_usuario_id, $ultimo_mensaje_id);
    } else {
        // Para usuarios normales, obtener sus propios mensajes
        $sql = "SELECT cm.id, cm.usuario_id, cm.admin_id, cm.mensaje, cm.leido,
                       cm.fecha_envio, cm.enviado_por,
                       CASE
                           WHEN cm.enviado_por = 'admin' THEN a.nombre_usuario
                           ELSE u.nombre_usuario
                       END as nombre_usuario
                FROM chat_mensajes cm
                JOIN usuario u ON cm.usuario_id = u.codigo
                LEFT JOIN usuario a ON cm.admin_id = a.codigo
                WHERE cm.usuario_id = ? AND cm.id > ?
                ORDER BY cm.fecha_envio ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $usuario_id, $ultimo_mensaje_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $mensajes = [];
    while ($row = $result->fetch_assoc()) {
        // Formatear la fecha para mostrarla en formato legible
        $fecha = new DateTime($row['fecha_envio']);
        $row['fecha_formateada'] = $fecha->format('d/m/Y H:i');

        $mensajes[] = $row;

        // Si el usuario actual es el destinatario, marcar como leído
        if (($es_admin && $row['enviado_por'] === 'usuario') ||
            (!$es_admin && $row['enviado_por'] === 'admin' && !$row['leido'])) {
            $sql_marcar = "UPDATE chat_mensajes SET leido = 1 WHERE id = ?";
            $stmt_marcar = $conn->prepare($sql_marcar);
            $stmt_marcar->bind_param("i", $row['id']);
            $stmt_marcar->execute();
        }
    }

    // Obtener información de la sesión de chat
    $sql_sesion = "SELECT id, estado, fecha_ultimo_mensaje FROM chat_sesiones WHERE usuario_id = ?";
    $stmt_sesion = $conn->prepare($sql_sesion);
    $stmt_sesion->bind_param("i", $chat_usuario_id);
    $stmt_sesion->execute();
    $sesion = $stmt_sesion->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'mensajes' => $mensajes,
        'sesion' => $sesion,
        'es_admin' => $es_admin
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los mensajes: ' . $e->getMessage()
    ]);
}
?>
