<?php
/**
 * Script para enviar un mensaje de chat
 */

// Incluir el helper de sesiones
require_once '../session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    echo json_encode([
        'success' => false,
        'message' => 'Debe iniciar sesión para enviar mensajes.'
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

// Verificar si se recibieron los parámetros necesarios
if (!isset($_POST['mensaje'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parámetros insuficientes.'
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
        'success' => false,
        'message' => 'Las tablas de chat no existen. Por favor, ejecute el script de creación de tablas.'
    ]);
    exit;
}

// Obtener los parámetros
$mensaje = trim($_POST['mensaje']);
$usuario_id = $_SESSION['usuario_logueado'];
$enviado_por = es_admin() ? 'admin' : 'usuario';

// Validar el mensaje
if (empty($mensaje)) {
    echo json_encode([
        'success' => false,
        'message' => 'El mensaje no puede estar vacío.'
    ]);
    exit;
}

// Si es un administrador, necesitamos el ID del usuario al que se envía el mensaje
if ($enviado_por === 'admin' && !isset($_POST['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Debe especificar el usuario destinatario.'
    ]);
    exit;
}

// Si es un administrador, usar el usuario_id proporcionado
if ($enviado_por === 'admin' && isset($_POST['usuario_id'])) {
    $destinatario_id = $_POST['usuario_id'];
    $admin_id = $usuario_id;
    $usuario_id = $destinatario_id;
} else {
    $admin_id = null; // Se asignará automáticamente al administrador activo
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // Verificar si existe una sesión de chat para este usuario
    $sql_check_sesion = "SELECT id, estado FROM chat_sesiones WHERE usuario_id = ?";
    $stmt_check_sesion = $conn->prepare($sql_check_sesion);
    $stmt_check_sesion->bind_param("i", $usuario_id);
    $stmt_check_sesion->execute();
    $result_sesion = $stmt_check_sesion->get_result();

    if ($result_sesion->num_rows === 0) {
        // Crear una nueva sesión de chat
        $sql_nueva_sesion = "INSERT INTO chat_sesiones (usuario_id, estado) VALUES (?, 'activa')";
        $stmt_nueva_sesion = $conn->prepare($sql_nueva_sesion);
        $stmt_nueva_sesion->bind_param("i", $usuario_id);
        $stmt_nueva_sesion->execute();
    } else {
        // Verificar si la sesión está cerrada
        $sesion = $result_sesion->fetch_assoc();
        if ($sesion['estado'] === 'cerrada') {
            // Reactivar la sesión
            $sql_reactivar = "UPDATE chat_sesiones SET estado = 'activa' WHERE usuario_id = ?";
            $stmt_reactivar = $conn->prepare($sql_reactivar);
            $stmt_reactivar->bind_param("i", $usuario_id);
            $stmt_reactivar->execute();

            // Si es un cliente, enviar un mensaje de sistema
            if ($enviado_por === 'usuario') {
                $mensaje_sistema = "El cliente ha iniciado una nueva conversación.";
                $sql_mensaje_sistema = "INSERT INTO chat_mensajes (usuario_id, mensaje, fecha_envio, enviado_por, leido)
                                       VALUES (?, ?, NOW(), 'admin', 0)";
                $stmt_mensaje_sistema = $conn->prepare($sql_mensaje_sistema);
                $stmt_mensaje_sistema->bind_param("is", $usuario_id, $mensaje_sistema);
                $stmt_mensaje_sistema->execute();
            }
        }
    }

    // Insertar el mensaje
    $sql_insertar = "INSERT INTO chat_mensajes (usuario_id, admin_id, mensaje, fecha_envio, enviado_por)
                     VALUES (?, ?, ?, NOW(), ?)";
    $stmt_insertar = $conn->prepare($sql_insertar);
    $stmt_insertar->bind_param("iiss", $usuario_id, $admin_id, $mensaje, $enviado_por);
    $stmt_insertar->execute();

    // Obtener el ID del mensaje insertado
    $mensaje_id = $conn->insert_id;

    // Actualizar la sesión de chat con el último mensaje
    $sql_actualizar = "UPDATE chat_sesiones SET ultimo_mensaje_id = ?, fecha_ultimo_mensaje = NOW(), estado = 'activa'
                       WHERE usuario_id = ?";
    $stmt_actualizar = $conn->prepare($sql_actualizar);
    $stmt_actualizar->bind_param("ii", $mensaje_id, $usuario_id);
    $stmt_actualizar->execute();

    // Confirmar transacción
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Mensaje enviado correctamente.',
        'mensaje_id' => $mensaje_id,
        'fecha' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => 'Error al enviar el mensaje: ' . $e->getMessage()
    ]);
}
?>
