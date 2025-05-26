<?php
/**
 * Script para procesar el pago de un pedido
 */

// Incluir el helper de sesiones
require_once __DIR__ . '/../helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Registrar información de depuración
error_log("procesar_pago.php - SESSION después de iniciar sesión: " . print_r($_SESSION, true));

// Configurar la respuesta como JSON
header('Content-Type: application/json');

// Función para devolver respuesta JSON
function responderJSON($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_logueado']) || !isset($_SESSION['rol_codigo'])) {
    // Registrar información de depuración
    error_log("procesar_pago.php - Usuario no logueado. SESSION: " . print_r($_SESSION, true));

    // Intentar recuperar la sesión
    session_write_close();
    session_start();

    error_log("procesar_pago.php - Intento de recuperar sesión. SESSION después: " . print_r($_SESSION, true));

    // Verificar nuevamente
    if (!isset($_SESSION['usuario_logueado']) || !isset($_SESSION['rol_codigo'])) {
        responderJSON(false, "Debes iniciar sesión para realizar esta acción. Por favor, actualiza la página e intenta nuevamente.");
    }
}

// Registrar información de depuración
error_log("procesar_pago.php - Usuario logueado. ID: " . $_SESSION['usuario_logueado'] . ", Rol: " . $_SESSION['rol_codigo']);

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    responderJSON(false, "Método de solicitud no válido.");
}

// Incluir la conexión a la base de datos
require_once __DIR__ . '/../config/db.php';

// Obtener datos del formulario
$pedido_codigo = isset($_POST['pedido_codigo']) ? $_POST['pedido_codigo'] : '';
$usuario_codigo = isset($_POST['usuario_codigo']) ? $_POST['usuario_codigo'] : '';
$comentarios = isset($_POST['comentarios']) ? $_POST['comentarios'] : '';

// Verificar información de depuración
if (isset($_POST['session_debug'])) {
    error_log("procesar_pago.php - Datos del formulario recibidos:");
    error_log("procesar_pago.php - pedido_codigo: " . $pedido_codigo);
    error_log("procesar_pago.php - usuario_codigo: " . $usuario_codigo);
    error_log("procesar_pago.php - SESSION: " . print_r($_SESSION, true));
}

// Obtener datos de envío
$direccion = isset($_POST['direccion']) ? $_POST['direccion'] : '';
$empresa_envio = isset($_POST['empresa_envio']) ? $_POST['empresa_envio'] : '';
$destinatario_nombre = isset($_POST['destinatario_nombre']) ? $_POST['destinatario_nombre'] : '';
$destinatario_telefono = isset($_POST['destinatario_telefono']) ? $_POST['destinatario_telefono'] : '';
$destinatario_cedula = isset($_POST['destinatario_cedula']) ? $_POST['destinatario_cedula'] : '';
$instrucciones_adicionales = isset($_POST['instrucciones_adicionales']) ? $_POST['instrucciones_adicionales'] : '';

// Obtener el número de métodos de pago
$metodo_count = isset($_POST['metodo_count']) ? intval($_POST['metodo_count']) : 1;

// Validar datos básicos
if (empty($pedido_codigo) || empty($usuario_codigo) || empty($direccion) || empty($destinatario_nombre) || empty($destinatario_telefono) || empty($destinatario_cedula)) {
    responderJSON(false, "Por favor, completa todos los campos obligatorios.");
}

// Verificar si el usuario actual es el mismo que se envía en el formulario
if ($_SESSION['usuario_logueado'] != $usuario_codigo) {
    error_log("procesar_pago.php - Usuario en sesión (" . $_SESSION['usuario_logueado'] . ") no coincide con usuario en formulario (" . $usuario_codigo . ")");

    // Actualizar el usuario_codigo con el valor de la sesión
    $usuario_codigo = $_SESSION['usuario_logueado'];
    error_log("procesar_pago.php - Actualizando usuario_codigo a: " . $usuario_codigo);
}

// Verificar si el pedido pertenece al usuario
$sql = "SELECT COUNT(*) as count FROM pedido WHERE pedido = ? AND usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $pedido_codigo, $usuario_codigo);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    responderJSON(false, "El pedido no existe o no te pertenece.");
}

// Verificar si ya existe un pago registrado para este pedido
$sql = "SELECT COUNT(*) as count FROM pagos WHERE pedido_codigo = ? AND usuario_codigo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $pedido_codigo, $usuario_codigo);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Verificar si hay registros en pedidos_finalizados con estado 'pendiente'
$sql_check_pendiente = "SELECT COUNT(*) as count FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ? AND estado = 'pendiente'";
$stmt_check_pendiente = $conn->prepare($sql_check_pendiente);
$stmt_check_pendiente->bind_param("si", $pedido_codigo, $usuario_codigo);
$stmt_check_pendiente->execute();
$result_check_pendiente = $stmt_check_pendiente->get_result();
$row_check_pendiente = $result_check_pendiente->fetch_assoc();

// Solo bloquear si hay pagos registrados y no hay pedidos devueltos pendientes
if ($row['count'] > 0 && $row_check_pendiente['count'] == 0) {
    responderJSON(false, "Ya existe un pago registrado para este pedido.");
}

// Si hay pagos registrados pero también hay pedidos devueltos, eliminar los pagos anteriores
if ($row['count'] > 0 && $row_check_pendiente['count'] > 0) {
    $sql_delete_pagos = "DELETE FROM pagos WHERE pedido_codigo = ? AND usuario_codigo = ?";
    $stmt_delete_pagos = $conn->prepare($sql_delete_pagos);
    $stmt_delete_pagos->bind_param("si", $pedido_codigo, $usuario_codigo);
    $stmt_delete_pagos->execute();

    error_log("procesar_pago.php - Eliminados pagos anteriores para el pedido devuelto: " . $pedido_codigo);
}

// Verificar si las tablas necesarias existen
$tablas_necesarias = ['datos_envio', 'pagos', 'pedidos_finalizados'];
$tablas_faltantes = [];

foreach ($tablas_necesarias as $tabla) {
    $sql_check = "SHOW TABLES LIKE '$tabla'";
    $result_check = $conn->query($sql_check);
    if (!$result_check || $result_check->num_rows == 0) {
        $tablas_faltantes[] = $tabla;
    }
}

// Si faltan tablas, crearlas
if (!empty($tablas_faltantes)) {
    foreach ($tablas_faltantes as $tabla) {
        switch ($tabla) {
            case 'datos_envio':
                $sql_create = "CREATE TABLE IF NOT EXISTS datos_envio (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    pedido_codigo VARCHAR(255) NOT NULL,
                    usuario_codigo BIGINT UNSIGNED NOT NULL,
                    direccion TEXT NOT NULL,
                    empresa_envio VARCHAR(255),
                    destinatario_nombre VARCHAR(255) NOT NULL,
                    destinatario_telefono VARCHAR(20) NOT NULL,
                    destinatario_cedula VARCHAR(20) NOT NULL,
                    instrucciones_adicionales TEXT,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    INDEX (pedido_codigo)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                break;
            case 'pagos':
                $sql_create = "CREATE TABLE IF NOT EXISTS pagos (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    pedido_codigo VARCHAR(255) NOT NULL,
                    parte_pago INT NOT NULL DEFAULT 1,
                    usuario_codigo BIGINT UNSIGNED NOT NULL,
                    metodo_pago ENUM('transferencia', 'pago_movil', 'efectivo') NOT NULL,
                    banco VARCHAR(100),
                    referencia VARCHAR(100),
                    monto DECIMAL(10,2) NOT NULL,
                    porcentaje DECIMAL(5,2),
                    telefono VARCHAR(20),
                    fecha_pago DATE NOT NULL,
                    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    estado ENUM('pendiente', 'verificado', 'rechazado') DEFAULT 'pendiente',
                    comentarios TEXT,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    INDEX (pedido_codigo)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                break;
            case 'pedidos_finalizados':
                $sql_create = "CREATE TABLE IF NOT EXISTS pedidos_finalizados (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    pedido_codigo VARCHAR(255) NOT NULL,
                    usuario_id BIGINT UNSIGNED NOT NULL,
                    producto_id BIGINT UNSIGNED NOT NULL,
                    cantidad INT NOT NULL,
                    precio DECIMAL(10,2) NOT NULL,
                    precio_dolares DECIMAL(10,2) NOT NULL,
                    precio_bolivares DECIMAL(10,2) NOT NULL,
                    estado ENUM('pendiente', 'pago_pendiente', 'pagado', 'enviado', 'entregado', 'cancelado') NOT NULL DEFAULT 'pagado',
                    fecha_pedido TIMESTAMP NULL,
                    fecha_pago TIMESTAMP NULL,
                    fecha_finalizacion TIMESTAMP NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    INDEX (pedido_codigo)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                break;
        }

        $conn->query($sql_create);
    }
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // Verificar si la tabla datos_envio existe
    $sql_check_envio = "SHOW TABLES LIKE 'datos_envio'";
    $result_check_envio = $conn->query($sql_check_envio);

    if ($result_check_envio && $result_check_envio->num_rows > 0) {
        // Verificar si ya existen datos de envío para este pedido
        $sql_check_datos_envio = "SELECT id FROM datos_envio WHERE pedido_codigo = ? AND usuario_codigo = ? LIMIT 1";
        $stmt_check_datos_envio = $conn->prepare($sql_check_datos_envio);
        $stmt_check_datos_envio->bind_param("si", $pedido_codigo, $usuario_codigo);
        $stmt_check_datos_envio->execute();
        $result_check_datos_envio = $stmt_check_datos_envio->get_result();

        if ($result_check_datos_envio->num_rows > 0) {
            // Actualizar los datos de envío existentes
            $sql_envio = "UPDATE datos_envio
                          SET direccion = ?,
                              empresa_envio = ?,
                              destinatario_nombre = ?,
                              destinatario_telefono = ?,
                              destinatario_cedula = ?,
                              instrucciones_adicionales = ?,
                              updated_at = NOW()
                          WHERE pedido_codigo = ? AND usuario_codigo = ?";
            $stmt_envio = $conn->prepare($sql_envio);
            $stmt_envio->bind_param("sssssssi", $direccion, $empresa_envio, $destinatario_nombre, $destinatario_telefono, $destinatario_cedula, $instrucciones_adicionales, $pedido_codigo, $usuario_codigo);

            if (!$stmt_envio->execute()) {
                throw new Exception("Error al actualizar los datos de envío: " . $stmt_envio->error);
            }

            error_log("procesar_pago.php - Datos de envío actualizados para el pedido: " . $pedido_codigo);
        } else {
            // Insertar nuevos datos de envío
            $sql_envio = "INSERT INTO datos_envio (pedido_codigo, usuario_codigo, direccion, empresa_envio, destinatario_nombre, destinatario_telefono, destinatario_cedula, instrucciones_adicionales, created_at, updated_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt_envio = $conn->prepare($sql_envio);
            $stmt_envio->bind_param("sissssss", $pedido_codigo, $usuario_codigo, $direccion, $empresa_envio, $destinatario_nombre, $destinatario_telefono, $destinatario_cedula, $instrucciones_adicionales);

            if (!$stmt_envio->execute()) {
                throw new Exception("Error al guardar los datos de envío: " . $stmt_envio->error);
            }

            error_log("procesar_pago.php - Nuevos datos de envío insertados para el pedido: " . $pedido_codigo);
        }
    } else {
        // Si la tabla no existe, continuar sin guardar los datos de envío
        error_log("La tabla datos_envio no existe. No se guardarán los datos de envío.");
    }

    // Verificar si la tabla pagos existe
    $sql_check_pagos = "SHOW TABLES LIKE 'pagos'";
    $result_check_pagos = $conn->query($sql_check_pagos);

    if ($result_check_pagos && $result_check_pagos->num_rows > 0) {
        // Procesar cada método de pago
        for ($i = 1; $i <= $metodo_count; $i++) {
            $metodo_pago = isset($_POST['metodo_pago_' . $i]) ? $_POST['metodo_pago_' . $i] : '';
            $banco = isset($_POST['banco_' . $i]) ? $_POST['banco_' . $i] : '';
            $referencia = isset($_POST['referencia_' . $i]) ? $_POST['referencia_' . $i] : '';
            $monto = isset($_POST['monto_' . $i]) ? floatval($_POST['monto_' . $i]) : 0;
            $telefono = isset($_POST['telefono_' . $i]) ? $_POST['telefono_' . $i] : '';
            $fecha_pago = isset($_POST['fecha_pago_' . $i]) ? $_POST['fecha_pago_' . $i] : '';

            // Validar datos del método de pago
            if (empty($metodo_pago) || $monto <= 0 || empty($fecha_pago)) {
                throw new Exception("Por favor, completa todos los campos obligatorios del método de pago " . $i);
            }

            // Insertar el pago en la base de datos
            $sql = "INSERT INTO pagos (pedido_codigo, parte_pago, usuario_codigo, metodo_pago, banco, referencia, monto, porcentaje, telefono, fecha_pago, comentarios, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siisssdsss", $pedido_codigo, $i, $usuario_codigo, $metodo_pago, $banco, $referencia, $monto, $telefono, $fecha_pago, $comentarios);

            if (!$stmt->execute()) {
                throw new Exception("Error al registrar el pago " . $i . ": " . $stmt->error);
            }
        }
    } else {
        // Si la tabla no existe, lanzar una excepción
        throw new Exception("No se puede procesar el pago porque la tabla 'pagos' no existe. Por favor, contacta al administrador.");
    }

    // Obtener los datos del pedido
    $sql_pedido = "SELECT * FROM pedido WHERE pedido = ?";
    $stmt_pedido = $conn->prepare($sql_pedido);
    $stmt_pedido->bind_param("s", $pedido_codigo);
    $stmt_pedido->execute();
    $result_pedido = $stmt_pedido->get_result();

    if ($result_pedido->num_rows == 0) {
        throw new Exception("No se encontró el pedido especificado.");
    }

    // Verificar si la tabla pedidos_finalizados existe
    $sql_check_finalizados = "SHOW TABLES LIKE 'pedidos_finalizados'";
    $result_check_finalizados = $conn->query($sql_check_finalizados);
    $tabla_existe = ($result_check_finalizados && $result_check_finalizados->num_rows > 0);

    // Guardar los datos del pedido para procesarlos
    $pedidos_data = [];
    while ($pedido = $result_pedido->fetch_assoc()) {
        $pedidos_data[] = $pedido;
    }

    // Si la tabla existe, mover los datos del pedido a la tabla de pedidos finalizados
    if ($tabla_existe) {
        try {
            // Verificar si ya existen registros en pedidos_finalizados para este pedido
            $sql_check_finalizados = "SELECT COUNT(*) as count FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ? AND estado = 'pendiente'";
            $stmt_check_finalizados = $conn->prepare($sql_check_finalizados);
            $stmt_check_finalizados->bind_param("si", $pedido_codigo, $usuario_codigo);
            $stmt_check_finalizados->execute();
            $result_check_finalizados = $stmt_check_finalizados->get_result();
            $row_check_finalizados = $result_check_finalizados->fetch_assoc();

            if ($row_check_finalizados['count'] > 0) {
                // Si ya existen registros en estado 'pendiente', actualizarlos a 'pagado'
                $sql_update_finalizados = "UPDATE pedidos_finalizados
                                          SET estado = 'pagado',
                                              fecha_pago = NOW(),
                                              updated_at = NOW()
                                          WHERE pedido_codigo = ? AND usuario_id = ? AND estado = 'pendiente'";
                $stmt_update_finalizados = $conn->prepare($sql_update_finalizados);
                $stmt_update_finalizados->bind_param("si", $pedido_codigo, $usuario_codigo);

                if (!$stmt_update_finalizados->execute()) {
                    throw new Exception("Error al actualizar los pedidos finalizados: " . $stmt_update_finalizados->error);
                }

                error_log("procesar_pago.php - Actualizados " . $stmt_update_finalizados->affected_rows . " registros en pedidos_finalizados para el pedido: " . $pedido_codigo);

                // Eliminar los datos del pedido de la tabla original si existen
                $sql_check_pedido = "SELECT COUNT(*) as count FROM pedido WHERE pedido = ? AND usuario_id = ?";
                $stmt_check_pedido = $conn->prepare($sql_check_pedido);
                $stmt_check_pedido->bind_param("si", $pedido_codigo, $usuario_codigo);
                $stmt_check_pedido->execute();
                $result_check_pedido = $stmt_check_pedido->get_result();
                $row_check_pedido = $result_check_pedido->fetch_assoc();

                if ($row_check_pedido['count'] > 0) {
                    $sql_delete = "DELETE FROM pedido WHERE pedido = ? AND usuario_id = ?";
                    $stmt_delete = $conn->prepare($sql_delete);
                    $stmt_delete->bind_param("si", $pedido_codigo, $usuario_codigo);

                    if (!$stmt_delete->execute()) {
                        throw new Exception("Error al eliminar el pedido original: " . $stmt_delete->error);
                    }

                    error_log("procesar_pago.php - Eliminados " . $stmt_delete->affected_rows . " registros de la tabla pedido para el pedido: " . $pedido_codigo);
                }
            } else {
                // Si no existen registros, insertar nuevos
                foreach ($pedidos_data as $pedido) {
                    $sql_insert = "INSERT INTO pedidos_finalizados (
                        pedido_codigo,
                        usuario_id,
                        producto_id,
                        cantidad,
                        precio,
                        precio_dolares,
                        precio_bolivares,
                        estado,
                        fecha_pedido,
                        fecha_pago,
                        fecha_finalizacion,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pagado', ?, NOW(), NULL, NOW(), NOW())";

                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param(
                        "siiiidds",
                        $pedido['pedido'],
                        $pedido['usuario_id'],
                        $pedido['producto_id'],
                        $pedido['cantidad'],
                        $pedido['precio'],
                        $pedido['precio_dolares'],
                        $pedido['precio_bolivares'],
                        $pedido['created_at']
                    );

                    if (!$stmt_insert->execute()) {
                        throw new Exception("Error al mover el pedido a la tabla de pedidos finalizados: " . $stmt_insert->error);
                    }

                    error_log("procesar_pago.php - Insertado producto ID " . $pedido['producto_id'] . " en pedidos_finalizados para el pedido: " . $pedido_codigo);
                }

                // Eliminar los datos del pedido de la tabla original
                $sql_delete = "DELETE FROM pedido WHERE pedido = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("s", $pedido_codigo);

                if (!$stmt_delete->execute()) {
                    throw new Exception("Error al eliminar el pedido original: " . $stmt_delete->error);
                }

                error_log("procesar_pago.php - Eliminados " . $stmt_delete->affected_rows . " registros de la tabla pedido para el pedido: " . $pedido_codigo);
            }
        } catch (Exception $e) {
            // Si hay un error al mover los datos, actualizar el estado del pedido a 'pagado'
            error_log("Error al mover datos a pedidos_finalizados: " . $e->getMessage());

            // Actualizar el estado del pedido a 'pagado'
            $sql_update = "UPDATE pedido SET estado = 'pagado' WHERE pedido = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("s", $pedido_codigo);

            if (!$stmt_update->execute()) {
                throw new Exception("Error al actualizar el estado del pedido: " . $stmt_update->error);
            }
        }
    } else {
        // Si la tabla no existe, simplemente actualizar el estado del pedido a 'pagado'
        $sql_update = "UPDATE pedido SET estado = 'pagado' WHERE pedido = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("s", $pedido_codigo);

        if (!$stmt_update->execute()) {
            throw new Exception("Error al actualizar el estado del pedido: " . $stmt_update->error);
        }
    }

    // Confirmar transacción
    $conn->commit();

    // Devolver respuesta exitosa
    responderJSON(true, "Pago registrado correctamente. Pronto verificaremos tu pago.", [
        'pedido_codigo' => $pedido_codigo
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();

    // Devolver respuesta de error
    responderJSON(false, "Error al registrar el pago: " . $e->getMessage());
}
?>
