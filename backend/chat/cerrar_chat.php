<?php
/**
 * Script para cerrar un chat (marcar como resuelto)
 */

// Incluir el helper de sesiones
require_once '../session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado y es administrador
if (!esta_logueado() || !es_admin()) {
    echo json_encode([
        'success' => false,
        'message' => 'No tiene permisos para realizar esta acción.'
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

// Verificar si se recibieron los parámetros necesarios
if (!isset($_POST['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parámetros insuficientes.'
    ]);
    exit;
}

// Obtener los parámetros
$usuario_id = (int)$_POST['usuario_id'];

// Iniciar transacción
$conn->begin_transaction();

try {
    // Verificar si existe una sesión de chat para este usuario
    $sql_check_sesion = "SELECT id FROM chat_sesiones WHERE usuario_id = ?";
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
    
    // Actualizar el estado de la sesión de chat a 'cerrada'
    $sql_actualizar = "UPDATE chat_sesiones SET estado = 'cerrada' WHERE usuario_id = ?";
    $stmt_actualizar = $conn->prepare($sql_actualizar);
    $stmt_actualizar->bind_param("i", $usuario_id);
    $stmt_actualizar->execute();
    
    // Verificar si se actualizó la sesión
    if ($stmt_actualizar->affected_rows === 0) {
        throw new Exception("No se pudo actualizar el estado de la sesión de chat.");
    }
    
    // Enviar un mensaje de sistema indicando que el chat ha sido cerrado
    $mensaje = "El administrador ha marcado este chat como resuelto. Si necesita ayuda adicional, puede iniciar una nueva conversación.";
    $fecha_envio = date('Y-m-d H:i:s');
    $enviado_por = 'admin';
    
    $sql_insertar = "INSERT INTO chat_mensajes (usuario_id, admin_id, mensaje, fecha_envio, enviado_por, leido) 
                     VALUES (?, ?, ?, ?, ?, 0)";
    $stmt_insertar = $conn->prepare($sql_insertar);
    $admin_id = $_SESSION['usuario_logueado'];
    $stmt_insertar->bind_param("iisss", $usuario_id, $admin_id, $mensaje, $fecha_envio, $enviado_por);
    $stmt_insertar->execute();
    
    // Confirmar transacción
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Chat marcado como resuelto correctamente.'
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al cerrar el chat: ' . $e->getMessage()
    ]);
}
?>
