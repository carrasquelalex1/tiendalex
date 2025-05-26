<?php
/**
 * Script para obtener los chats activos (para administradores)
 */

// Incluir el helper de sesiones
require_once '../session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado y es administrador
if (!esta_logueado() || !es_admin()) {
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para acceder a esta función.'
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
$sql_check_table = "SHOW TABLES LIKE 'chat_sesiones'";
$result_check = $conn->query($sql_check_table);
if ($result_check && $result_check->num_rows > 0) {
    $tabla_chat_existe = true;
} else {
    echo json_encode([
        'success' => true,
        'chats' => []
    ]);
    exit;
}

try {
    // Verificar si se debe mostrar solo chats activos o todos
    $mostrar_cerrados = isset($_GET['mostrar_cerrados']) && $_GET['mostrar_cerrados'] == 'true';

    // Consulta para obtener los chats con mensajes no leídos primero
    $sql = "SELECT cs.id, cs.usuario_id, cs.fecha_ultimo_mensaje, cs.estado,
                   u.nombre_usuario,
                   (SELECT COUNT(*) FROM chat_mensajes
                    WHERE usuario_id = cs.usuario_id AND enviado_por = 'usuario' AND leido = 0) as mensajes_no_leidos
            FROM chat_sesiones cs
            JOIN usuario u ON cs.usuario_id = u.codigo";

    // Filtrar por estado si es necesario
    if (!$mostrar_cerrados) {
        $sql .= " WHERE cs.estado = 'activa'";
    }

    // Ordenar por mensajes no leídos y fecha
    $sql .= " ORDER BY cs.estado = 'activa' DESC, mensajes_no_leidos DESC, cs.fecha_ultimo_mensaje DESC";

    $result = $conn->query($sql);

    $chats = [];
    while ($row = $result->fetch_assoc()) {
        // Formatear la fecha para mostrarla en formato legible
        if ($row['fecha_ultimo_mensaje']) {
            $fecha = new DateTime($row['fecha_ultimo_mensaje']);
            $row['fecha_formateada'] = $fecha->format('d/m/Y H:i');
        } else {
            $row['fecha_formateada'] = 'Sin mensajes';
        }

        $chats[] = $row;
    }

    echo json_encode([
        'success' => true,
        'chats' => $chats
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los chats activos: ' . $e->getMessage()
    ]);
}
?>
