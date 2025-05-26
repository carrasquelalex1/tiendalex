<?php
// Incluir el archivo de conexión a la base de datos
require_once '../backend/config/db.php';

// Función para obtener la tasa actual
function obtenerTasaActual() {
    global $conn;

    $sql = "SELECT * FROM tasa WHERE current = 1 ORDER BY id_tasa DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

// Función para actualizar los precios en bolívares y el valor de la tasa en todos los productos
function actualizarPreciosBolivares($tasa) {
    global $conn;
    $success = true;

    // Iniciar transacción para asegurar la consistencia de los datos
    $conn->begin_transaction();
    
    // Registrar la ejecución de la función con la tasa actual
    error_log("actualizarPreciosBolivares - Iniciando con tasa: " . $tasa);

    try {
        // 1. Actualizar todos los productos: precio en bolívares y valor de la tasa
        $sql = "UPDATE productos_tienda SET precio_bolivares = precio_producto * ?, tasa = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("dd", $tasa, $tasa);
            $stmt->execute();
            $stmt->close();
        } else {
            throw new Exception("Error al preparar la actualización de productos: " . $conn->error);
        }

        // 2. Actualizar los precios en bolívares de TODOS los pedidos pendientes de pago
        // sin importar el precio actual, ya que la tasa ha cambiado
        $sql_pedidos = "SELECT 
            pf.id, 
            pf.pedido_codigo, 
            pf.producto_id, 
            pf.cantidad, 
            pf.precio as precio_original, 
            pf.precio_dolares, 
            pf.precio_bolivares as precio_actual_bs,
            pf.estado
            FROM pedidos_finalizados pf
            WHERE pf.estado IN ('pago_pendiente', 'pendiente')";
        
        error_log("ACTUALIZANDO PRECIOS - Obteniendo pedidos pendientes...");
        $result_pedidos = $conn->query($sql_pedidos);
        
        if ($result_pedidos === false) {
            throw new Exception("Error en la consulta de pedidos pendientes: " . $conn->error);
        }
        
        $total_pedidos = $result_pedidos->num_rows;
        error_log("ACTUALIZANDO PRECIOS - Total de pedidos pendientes: " . $total_pedidos);
        
        if ($total_pedidos > 0) {
            $update_sql = "UPDATE pedidos_finalizados SET 
                         precio = ?, 
                         precio_dolares = ?, 
                         precio_bolivares = ?,
                         updated_at = NOW() 
                         WHERE id = ?";
            
            $update_stmt = $conn->prepare($update_sql);
            if (!$update_stmt) {
                throw new Exception("Error al preparar la actualización de pedidos: " . $conn->error);
            }
            
            while ($pedido = $result_pedidos->fetch_assoc()) {
                // Calcular el nuevo precio en bolívares usando el precio original del pedido
                $precio_base = $pedido['precio_original'];
                $nuevo_precio_bolivares = round($precio_base * $tasa, 2);
                
                // Registrar los detalles de la actualización
                error_log(sprintf(
                    "ACTUALIZANDO PEDIDO - ID: %s, Código: %s, Estado: %s, " .
                    "Precio base: %s $, Tasa: %s, Precio actual: %s Bs, Nuevo precio: %s Bs",
                    $pedido['id'],
                    $pedido['pedido_codigo'],
                    $pedido['estado'],
                    number_format($precio_base, 2, '.', ''),
                    $tasa,
                    number_format($pedido['precio_actual_bs'] ?? 0, 2, '.', ''),
                    number_format($nuevo_precio_bolivares, 2, '.', '')
                ));
                
                // Actualizar el pedido con los nuevos precios
                $update_stmt->bind_param("dddi", 
                    $precio_base,               // precio (en dólares)
                    $precio_base,               // precio_dolares
                    $nuevo_precio_bolivares,     // precio_bolivares (nuevo cálculo)
                    $pedido['id']               // id del pedido
                );
                
                if (!$update_stmt->execute()) {
                    $error_msg = sprintf(
                        "Error al actualizar el pedido ID %s (Código: %s): %s",
                        $pedido['id'],
                        $pedido['pedido_codigo'],
                        $update_stmt->error
                    );
                    error_log($error_msg);
                    throw new Exception($error_msg);
                }
            }
            
            $update_stmt->close();
        }
        
        // Si todo salió bien, confirmar la transacción
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        // Si hay algún error, revertir la transacción
        $conn->rollback();
        error_log("Error en actualizarPreciosBolivares: " . $e->getMessage());
        return false;
    }
}

// Verificar si se ha enviado el formulario para actualizar la tasa
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'actualizar_tasa') {
    $valor_tasa = isset($_POST['valor_tasa']) && is_numeric($_POST['valor_tasa']) ? $_POST['valor_tasa'] : 0;

    // Validar que la tasa sea mayor que cero
    if ($valor_tasa <= 0) {
        header("Location: ../catalogo.php?error=1&message=" . urlencode("El valor de la tasa debe ser mayor que cero."));
        exit;
    }

    // Desactivar todas las tasas actuales
    $sql = "UPDATE tasa SET current = 0";
    $conn->query($sql);

    // Insertar la nueva tasa
    $sql = "INSERT INTO tasa (valor_tasa, current, created_at, updated_at) VALUES (?, 1, NOW(), NOW())";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("d", $valor_tasa);

        if ($stmt->execute()) {
            // Actualizar los precios en bolívares de todos los productos
            if (actualizarPreciosBolivares($valor_tasa)) {
                header("Location: ../catalogo.php?success=2&message=" . urlencode("Tasa actualizada correctamente a " . number_format($valor_tasa, 2) . " Bs/$. Precios en bolívares actualizados."));
            } else {
                header("Location: ../catalogo.php?success=2&message=" . urlencode("Tasa actualizada correctamente a " . number_format($valor_tasa, 2) . " Bs/$. Error al actualizar precios en bolívares."));
            }
            exit;
        } else {
            header("Location: ../catalogo.php?error=1&message=" . urlencode("Error al guardar la tasa: " . $stmt->error));
            exit;
        }

        $stmt->close();
    } else {
        header("Location: ../catalogo.php?error=1&message=" . urlencode("Error en la preparación de la consulta: " . $conn->error));
        exit;
    }
} else if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'calcular_precio') {
    // Obtener la tasa actual
    $tasa_actual = obtenerTasaActual();

    if (!$tasa_actual) {
        echo json_encode(['error' => 'No hay una tasa de cambio configurada.']);
        exit;
    }

    $precio_dolares = isset($_POST['precio_dolares']) && is_numeric($_POST['precio_dolares']) ? $_POST['precio_dolares'] : 0;
    $precio_bolivares = $precio_dolares * $tasa_actual['valor_tasa'];

    echo json_encode(['precio_bolivares' => $precio_bolivares]);
    exit;
} else {
    // Redirigir a la página de catálogo
    header("Location: ../catalogo.php");
    exit;
}
?>
