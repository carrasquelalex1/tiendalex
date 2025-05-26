<?php
/**
 * Endpoint para eliminar definitivamente un chat marcado como resuelto
 */

// Incluir archivos necesarios
require_once '../session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado y es administrador
if (!esta_logueado() || !es_admin()) {
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para realizar esta acción'
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
        'success' => false,
        'message' => 'Las tablas de chat no existen. Por favor, ejecute el script de creación de tablas.'
    ]);
    exit;
}

// Verificar si se recibió el ID del usuario
if (!isset($_POST['usuario_id']) || empty($_POST['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de usuario no proporcionado'
    ]);
    exit;
}

$usuario_id = (int)$_POST['usuario_id'];

// Iniciar transacción
$conn->begin_transaction();

try {
    // Verificar si existe una sesión de chat para este usuario
    $sql_check_sesion = "SELECT estado FROM chat_sesiones WHERE usuario_id = ?";
    $stmt_check_sesion = $conn->prepare($sql_check_sesion);
    $stmt_check_sesion->bind_param("i", $usuario_id);
    $stmt_check_sesion->execute();
    $result_sesion = $stmt_check_sesion->get_result();

    if ($result_sesion->num_rows === 0) {
        // No existe una sesión de chat para este usuario
        echo json_encode([
            'success' => false,
            'message' => 'No existe una sesión de chat para este usuario.'
        ]);
        exit;
    }

    $conversacion = $result_sesion->fetch_assoc();

    if ($conversacion['estado'] !== 'cerrada') {
        echo json_encode([
            'success' => false,
            'message' => 'Solo se pueden eliminar conversaciones marcadas como resueltas'
        ]);
        exit;
    }

    // Eliminar los mensajes de la conversación
    $sql_eliminar_mensajes = "DELETE FROM chat_mensajes WHERE usuario_id = ?";
    $stmt_eliminar_mensajes = $conn->prepare($sql_eliminar_mensajes);
    $stmt_eliminar_mensajes->bind_param("i", $usuario_id);
    $stmt_eliminar_mensajes->execute();

    // Eliminar la sesión de chat
    $sql_eliminar_sesion = "DELETE FROM chat_sesiones WHERE usuario_id = ?";
    $stmt_eliminar_sesion = $conn->prepare($sql_eliminar_sesion);
    $stmt_eliminar_sesion->bind_param("i", $usuario_id);
    $stmt_eliminar_sesion->execute();

    // Confirmar transacción
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Conversación eliminada correctamente'
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar la conversación: ' . $e->getMessage()
    ]);
}
