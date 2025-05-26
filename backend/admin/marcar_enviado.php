<?php
/**
 * Marcar un pedido como enviado
 */

// Incluir el helper de sesiones
require_once '../session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado y es administrador
if (!esta_logueado() || !es_admin()) {
    echo json_encode([
        'success' => false,
        'message' => 'No tienes permisos para realizar esta acción.'
    ]);
    exit;
}

// Verificar si se recibieron los parámetros necesarios
if (!isset($_POST['pedido_codigo']) || !isset($_POST['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parámetros insuficientes.'
    ]);
    exit;
}

// Incluir la conexión a la base de datos
require_once '../config/db.php';

// Obtener los parámetros
$pedido_codigo = $_POST['pedido_codigo'];
$usuario_id = $_POST['usuario_id'];

// Iniciar transacción
$conn->begin_transaction();

try {
    // Registrar los parámetros recibidos para depuración
    error_log("marcar_enviado.php - Parámetros recibidos: pedido_codigo=$pedido_codigo, usuario_id=$usuario_id");
    // Verificar los valores permitidos para la columna 'estado' en pedidos_finalizados
    $sql_column_type_pf = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE TABLE_SCHEMA = 'tiendalex2'
                        AND TABLE_NAME = 'pedidos_finalizados'
                        AND COLUMN_NAME = 'estado'";

    $result_column_type_pf = $conn->query($sql_column_type_pf);
    $estado_valor_pf = 'enviado'; // Valor predeterminado

    if ($result_column_type_pf && $result_column_type_pf->num_rows > 0) {
        $row = $result_column_type_pf->fetch_assoc();
        $column_type = $row['COLUMN_TYPE'];

        // Si es un ENUM, extraer los valores permitidos
        if (strpos($column_type, 'enum') === 0) {
            preg_match_all("/'(.*?)'/", $column_type, $matches);
            $valores_permitidos = $matches[1];

            // Verificar si 'enviado' está entre los valores permitidos
            if (in_array('enviado', $valores_permitidos)) {
                $estado_valor_pf = 'enviado';
            } elseif (in_array('entregado', $valores_permitidos)) {
                $estado_valor_pf = 'entregado';
            } else {
                // Usar el primer valor del ENUM como fallback
                $estado_valor_pf = $valores_permitidos[0];
            }
        }
    }

    // Verificar si ya existe un registro en pedidos_finalizados con estado 'enviado'
    $sql_verificar_enviado = "SELECT COUNT(*) as total FROM pedidos_finalizados WHERE pedido_codigo = ? AND usuario_id = ? AND estado = 'enviado'";
    $stmt_verificar_enviado = $conn->prepare($sql_verificar_enviado);
    if ($stmt_verificar_enviado === false) {
        throw new Exception("Error al preparar la consulta para verificar pedidos_finalizados: " . $conn->error);
    }
    $stmt_verificar_enviado->bind_param("si", $pedido_codigo, $usuario_id);
    if (!$stmt_verificar_enviado->execute()) {
        throw new Exception("Error al ejecutar la consulta para verificar pedidos_finalizados: " . $stmt_verificar_enviado->error);
    }
    $result_verificar_enviado = $stmt_verificar_enviado->get_result();
    $row_verificar_enviado = $result_verificar_enviado->fetch_assoc();
    $ya_esta_enviado = ($row_verificar_enviado['total'] > 0);

    // Solo actualizar si no está ya marcado como enviado
    if (!$ya_esta_enviado) {
        // Actualizar el estado de los productos en pedidos_finalizados - usar valor directo para evitar problemas
        error_log("marcar_enviado.php - Actualizando pedidos_finalizados con estado='enviado'");
        $sql_actualizar = "UPDATE pedidos_finalizados SET estado = 'enviado', updated_at = NOW() WHERE pedido_codigo = ? AND usuario_id = ?";
        $stmt_actualizar = $conn->prepare($sql_actualizar);
        if ($stmt_actualizar === false) {
            throw new Exception("Error al preparar la consulta para pedidos_finalizados: " . $conn->error);
        }
        $stmt_actualizar->bind_param("si", $pedido_codigo, $usuario_id);
        if (!$stmt_actualizar->execute()) {
            throw new Exception("Error al ejecutar la consulta para pedidos_finalizados: " . $stmt_actualizar->error);
        }
        error_log("marcar_enviado.php - Resultado actualización pedidos_finalizados: " . ($stmt_actualizar->affected_rows > 0 ? "OK" : "Sin cambios"));
    } else {
        error_log("marcar_enviado.php - El pedido ya está marcado como enviado en pedidos_finalizados, no se actualiza");
        // Crear una variable para simular el resultado de la actualización
        $stmt_actualizar = new stdClass();
        $stmt_actualizar->affected_rows = 0;
    }

    // Verificar los valores permitidos para la columna 'estado' en pagos
    $sql_column_type_pagos = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                           WHERE TABLE_SCHEMA = 'tiendalex2'
                           AND TABLE_NAME = 'pagos'
                           AND COLUMN_NAME = 'estado'";

    $result_column_type_pagos = $conn->query($sql_column_type_pagos);
    $estado_valor_pagos = 'enviado'; // Valor predeterminado

    if ($result_column_type_pagos && $result_column_type_pagos->num_rows > 0) {
        $row = $result_column_type_pagos->fetch_assoc();
        $column_type = $row['COLUMN_TYPE'];

        // Si es un ENUM, extraer los valores permitidos
        if (strpos($column_type, 'enum') === 0) {
            preg_match_all("/'(.*?)'/", $column_type, $matches);
            $valores_permitidos = $matches[1];

            // Verificar si 'enviado' está entre los valores permitidos
            if (in_array('enviado', $valores_permitidos)) {
                $estado_valor_pagos = 'enviado';
            } elseif (in_array('entregado', $valores_permitidos)) {
                $estado_valor_pagos = 'entregado';
            } else {
                // Usar el primer valor del ENUM como fallback
                $estado_valor_pagos = $valores_permitidos[0];
            }
        }
    }

    // Actualizar el estado de los pagos - usar 'verificado' en lugar de 'enviado' para evitar problemas
    error_log("marcar_enviado.php - Actualizando pagos con estado='verificado'");
    $sql_actualizar_pagos = "UPDATE pagos SET estado = 'verificado', updated_at = NOW() WHERE pedido_codigo = ? AND usuario_codigo = ?";
    $stmt_actualizar_pagos = $conn->prepare($sql_actualizar_pagos);
    if ($stmt_actualizar_pagos === false) {
        throw new Exception("Error al preparar la consulta para pagos: " . $conn->error);
    }
    $stmt_actualizar_pagos->bind_param("si", $pedido_codigo, $usuario_id);
    if (!$stmt_actualizar_pagos->execute()) {
        throw new Exception("Error al ejecutar la consulta para pagos: " . $stmt_actualizar_pagos->error);
    }
    error_log("marcar_enviado.php - Resultado actualización pagos: " . ($stmt_actualizar_pagos->affected_rows > 0 ? "OK" : "Sin cambios"));

    // Verificar si la tabla envios existe
    $tabla_envios_existe = false;
    $sql_check_table = "SHOW TABLES LIKE 'envios'";
    $result_check = $conn->query($sql_check_table);
    if ($result_check && $result_check->num_rows > 0) {
        $tabla_envios_existe = true;
    }

    if ($tabla_envios_existe) {
        // Verificar si ya existe un registro en la tabla envios con estado 'enviado'
        // Convertir el pedido_codigo a una variable temporal para evitar problemas de colación
        $temp_pedido_codigo = $pedido_codigo;

        $sql_verificar_envio_enviado = "SELECT id FROM envios WHERE pedido_codigo = ? AND usuario_codigo = ? AND estado = 'enviado' LIMIT 1";
        $stmt_verificar_envio_enviado = $conn->prepare($sql_verificar_envio_enviado);
        $stmt_verificar_envio_enviado->bind_param("si", $temp_pedido_codigo, $usuario_id);
        $stmt_verificar_envio_enviado->execute();
        $result_verificar_envio_enviado = $stmt_verificar_envio_enviado->get_result();
        $ya_existe_envio_enviado = ($result_verificar_envio_enviado->num_rows > 0);

        // Si ya existe un registro con estado 'enviado', no hacemos nada
        if ($ya_existe_envio_enviado) {
            error_log("marcar_enviado.php - Ya existe un registro en envios con estado 'enviado' para este pedido, no se modifica");
        } else {
            // Verificar si existe un registro en la tabla envios (con cualquier estado)
            // Usar la misma variable temporal para evitar problemas de colación
            $sql_verificar_envio = "SELECT id, estado FROM envios WHERE pedido_codigo = ? AND usuario_codigo = ? LIMIT 1";
            $stmt_verificar_envio = $conn->prepare($sql_verificar_envio);
            $stmt_verificar_envio->bind_param("si", $temp_pedido_codigo, $usuario_id);
            $stmt_verificar_envio->execute();
            $result_verificar_envio = $stmt_verificar_envio->get_result();

            // Verificar los valores permitidos para la columna 'estado'
            $sql_column_type = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                                WHERE TABLE_SCHEMA = 'tiendalex2'
                                AND TABLE_NAME = 'envios'
                                AND COLUMN_NAME = 'estado'";

            $result_column_type = $conn->query($sql_column_type);
            $estado_valor = 'enviado'; // Valor predeterminado

            if ($result_column_type && $result_column_type->num_rows > 0) {
                $row = $result_column_type->fetch_assoc();
                $column_type = $row['COLUMN_TYPE'];

                // Si es un ENUM, extraer los valores permitidos
                if (strpos($column_type, 'enum') === 0) {
                    preg_match_all("/'(.*?)'/", $column_type, $matches);
                    $valores_permitidos = $matches[1];

                    // Verificar si 'enviado' está entre los valores permitidos
                    if (in_array('enviado', $valores_permitidos)) {
                        $estado_valor = 'enviado';
                    } elseif (in_array('entregado', $valores_permitidos)) {
                        $estado_valor = 'entregado';
                    } else {
                        // Usar el primer valor del ENUM como fallback
                        $estado_valor = $valores_permitidos[0];
                    }
                }
            }

            error_log("marcar_enviado.php - Verificando envios con estado='$estado_valor'");
            if ($result_verificar_envio->num_rows > 0) {
                // Actualizar el registro existente - usar valor directo para evitar problemas
                $row_envio = $result_verificar_envio->fetch_assoc();
                error_log("marcar_enviado.php - Actualizando registro existente en envios (estado actual: {$row_envio['estado']})");
                $sql_actualizar_envio = "UPDATE envios SET estado = 'enviado', fecha_envio = NOW(), updated_at = NOW() WHERE pedido_codigo = ? AND usuario_codigo = ?";
                $stmt_actualizar_envio = $conn->prepare($sql_actualizar_envio);
                if ($stmt_actualizar_envio === false) {
                    throw new Exception("Error al preparar la consulta para actualizar envios: " . $conn->error);
                }
                $stmt_actualizar_envio->bind_param("si", $temp_pedido_codigo, $usuario_id);
                if (!$stmt_actualizar_envio->execute()) {
                    throw new Exception("Error al ejecutar la consulta para actualizar envios: " . $stmt_actualizar_envio->error);
                }
                error_log("marcar_enviado.php - Resultado actualización envios: " . ($stmt_actualizar_envio->affected_rows > 0 ? "OK" : "Sin cambios"));
            } else {
                // Insertar un nuevo registro en la tabla envios - usar valor directo para evitar problemas
                error_log("marcar_enviado.php - Insertando nuevo registro en envios");
                $sql_insertar_envio = "INSERT INTO envios (pedido_codigo, usuario_codigo, estado, fecha_envio) VALUES (?, ?, 'enviado', NOW())";
                $stmt_insertar_envio = $conn->prepare($sql_insertar_envio);
                if ($stmt_insertar_envio === false) {
                    throw new Exception("Error al preparar la consulta para insertar en envios: " . $conn->error);
                }
                $stmt_insertar_envio->bind_param("si", $temp_pedido_codigo, $usuario_id);
                if (!$stmt_insertar_envio->execute()) {
                    throw new Exception("Error al ejecutar la consulta para insertar en envios: " . $stmt_insertar_envio->error);
                }
                error_log("marcar_enviado.php - Resultado inserción envios: " . ($stmt_insertar_envio->affected_rows > 0 ? "OK" : "Sin cambios"));
            }
        }
    }

    // Verificar si se actualizaron registros
    if ($stmt_actualizar->affected_rows == 0 && $stmt_actualizar_pagos->affected_rows == 0) {
        throw new Exception("No se encontraron registros para actualizar.");
    }

    // Confirmar transacción
    $conn->commit();

    // Mensaje de éxito simplificado
    echo json_encode([
        'success' => true,
        'message' => 'Pedido marcado como enviado correctamente.'
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'message' => 'Error al marcar el pedido como enviado: ' . $e->getMessage()
    ]);
}
