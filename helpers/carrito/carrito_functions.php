<?php
/**
 * Funciones para el manejo del carrito de compras
 */

/**
 * Genera un código de pedido aleatorio (2 letras + 6 números)
 * @return string Código de pedido generado
 */
function generarCodigoPedido() {
    // Generar 2 letras aleatorias (mayúsculas)
    $letras = '';
    for ($i = 0; $i < 2; $i++) {
        $letras .= chr(rand(65, 90)); // ASCII de A-Z
    }

    // Generar 6 números aleatorios
    $numeros = '';
    for ($i = 0; $i < 6; $i++) {
        $numeros .= rand(0, 9);
    }

    // Combinar letras y números
    return $letras . $numeros;
}

/**
 * Verifica si un código de pedido ya existe en la base de datos
 * @param string $codigo Código de pedido a verificar
 * @return bool True si el código ya existe, False en caso contrario
 */
function codigoPedidoExiste($codigo) {
    global $conn;

    // Verificar en la tabla carrito
    $sql = "SELECT COUNT(*) as count FROM carrito WHERE pedido = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        return true;
    }

    // Verificar en la tabla pedido
    $sql = "SELECT COUNT(*) as count FROM pedido WHERE pedido = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['count'] > 0;
}

/**
 * Genera un código de pedido único
 * @return string Código de pedido único
 */
function generarCodigoPedidoUnico() {
    $codigo = generarCodigoPedido();

    // Verificar si el código ya existe y generar uno nuevo si es necesario
    while (codigoPedidoExiste($codigo)) {
        $codigo = generarCodigoPedido();
    }

    return $codigo;
}

/**
 * Obtiene el código de pedido actual del usuario o genera uno nuevo
 * @param int $usuario_id ID del usuario
 * @return string Código de pedido
 */
function obtenerCodigoPedidoUsuario($usuario_id) {
    global $conn;

    // Verificar si el usuario ya tiene un código de pedido en el carrito
    $sql = "SELECT DISTINCT pedido FROM carrito WHERE usuario_id = ? AND pedido IS NOT NULL LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['pedido'];
    }

    // Si no tiene, generar uno nuevo
    return generarCodigoPedidoUnico();
}

/**
 * Cuenta la cantidad de productos en el carrito del usuario
 * @param int $usuario_id ID del usuario
 * @return int Cantidad de productos en el carrito
 */
function contarProductosCarrito($usuario_id) {
    global $conn;

    $sql = "SELECT SUM(cantidad) as total FROM carrito WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total'] ? $row['total'] : 0;
    }

    return 0;
}

/**
 * Verifica si un producto ya está en el carrito del usuario
 * @param int $usuario_id ID del usuario
 * @param int $producto_id ID del producto
 * @return bool|array False si no está en el carrito, o array con la información si está
 */
function productoEnCarrito($usuario_id, $producto_id) {
    global $conn;

    $sql = "SELECT * FROM carrito WHERE usuario_id = ? AND producto_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $usuario_id, $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return false;
}

/**
 * Agrega un producto al carrito o actualiza la cantidad si ya existe
 * @param int $usuario_id ID del usuario
 * @param int $producto_id ID del producto
 * @param int $cantidad Cantidad a agregar
 * @param string $codigo_pedido Código de pedido
 * @return bool True si se agregó correctamente, False en caso contrario
 */
function agregarProductoCarrito($usuario_id, $producto_id, $cantidad, $codigo_pedido) {
    global $conn;

    // Obtener información del producto
    $sql = "SELECT * FROM productos_tienda WHERE id_producto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return false; // Producto no encontrado
    }

    $producto = $result->fetch_assoc();

    // Verificar si hay suficiente existencia
    if ($producto['existencia_producto'] < $cantidad) {
        return false; // No hay suficiente existencia
    }

    // Verificar si el producto ya está en el carrito
    $producto_carrito = productoEnCarrito($usuario_id, $producto_id);

    if ($producto_carrito) {
        // Actualizar la cantidad
        $nueva_cantidad = $producto_carrito['cantidad'] + $cantidad;

        // Verificar si hay suficiente existencia para la nueva cantidad
        if ($producto['existencia_producto'] < $nueva_cantidad) {
            return false; // No hay suficiente existencia
        }

        $sql = "UPDATE carrito SET cantidad = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $nueva_cantidad, $producto_carrito['id']);
    } else {
        // Agregar nuevo producto al carrito
        $sql = "INSERT INTO carrito (usuario_id, producto_id, cantidad, precio, precio_dolares, precio_bolivares, pedido) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiddds", $usuario_id, $producto_id, $cantidad, $producto['precio_producto'], $producto['precio_producto'], $producto['precio_bolivares'], $codigo_pedido);
    }

    if ($stmt->execute()) {
        // Actualizar la existencia del producto
        $nueva_existencia = $producto['existencia_producto'] - $cantidad;
        $sql = "UPDATE productos_tienda SET existencia_producto = ? WHERE id_producto = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $nueva_existencia, $producto_id);
        return $stmt->execute();
    }

    return false;
}

/**
 * Elimina todos los productos del carrito de un usuario
 * @param int $usuario_id ID del usuario
 * @return bool True si se eliminaron correctamente, False en caso contrario
 */
function limpiarCarrito($usuario_id) {
    global $conn;

    // Registrar información de depuración
    error_log("limpiarCarrito - Iniciando limpieza del carrito para usuario ID: " . $usuario_id);

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Primero, devolver los productos a la existencia
        $sql = "SELECT producto_id, cantidad FROM carrito WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $productos_actualizados = 0;

        while ($row = $result->fetch_assoc()) {
            $sql = "UPDATE productos_tienda SET existencia_producto = existencia_producto + ? WHERE id_producto = ?";
            $stmt_update = $conn->prepare($sql);
            $stmt_update->bind_param("ii", $row['cantidad'], $row['producto_id']);
            $stmt_update->execute();
            $productos_actualizados++;

            error_log("limpiarCarrito - Producto ID: " . $row['producto_id'] . " devuelto a existencia: " . $row['cantidad']);
        }

        error_log("limpiarCarrito - Total de productos actualizados: " . $productos_actualizados);

        // Luego, eliminar los productos del carrito
        $sql = "DELETE FROM carrito WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $resultado = $stmt->execute();

        if ($resultado) {
            $filas_afectadas = $stmt->affected_rows;
            error_log("limpiarCarrito - Productos eliminados del carrito: " . $filas_afectadas);

            // Confirmar transacción
            $conn->commit();
            return true;
        } else {
            error_log("limpiarCarrito - Error al eliminar productos del carrito: " . $stmt->error);
            $conn->rollback();
            return false;
        }
    } catch (Exception $e) {
        // Error en la transacción
        $conn->rollback();
        error_log("Error en limpiarCarrito: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualiza la cantidad de un producto en el carrito
 * @param int $id ID del registro en la tabla carrito
 * @param int $nueva_cantidad Nueva cantidad del producto
 * @param int $usuario_id ID del usuario (para verificación)
 * @return bool|string True si se actualizó correctamente, mensaje de error en caso contrario
 */
function actualizarCantidadCarrito($id, $nueva_cantidad, $usuario_id) {
    global $conn;

    // Registrar información de depuración
    error_log("actualizarCantidadCarrito - Iniciando actualización: id=$id, nueva_cantidad=$nueva_cantidad, usuario_id=$usuario_id");

    // Verificar que el producto pertenezca al usuario
    $sql = "SELECT c.*, p.existencia_producto, p.nombre_producto
            FROM carrito c
            JOIN productos_tienda p ON c.producto_id = p.id_producto
            WHERE c.id = ? AND c.usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        error_log("actualizarCantidadCarrito - El producto no pertenece al usuario: id=$id, usuario_id=$usuario_id");
        return "El producto no pertenece a tu carrito.";
    }

    $producto_carrito = $result->fetch_assoc();
    $cantidad_actual = $producto_carrito['cantidad'];
    $existencia_total = $producto_carrito['existencia_producto'] + $cantidad_actual; // Existencia actual + lo que ya está en el carrito

    error_log("actualizarCantidadCarrito - Información del producto: " . print_r($producto_carrito, true));
    error_log("actualizarCantidadCarrito - Cantidad actual: $cantidad_actual, Existencia total: $existencia_total");

    // Validar la nueva cantidad
    if ($nueva_cantidad <= 0) {
        error_log("actualizarCantidadCarrito - Cantidad no válida: $nueva_cantidad");
        return "La cantidad debe ser mayor a 0.";
    }

    if ($nueva_cantidad > $existencia_total) {
        error_log("actualizarCantidadCarrito - No hay suficiente existencia: $nueva_cantidad > $existencia_total");
        return "No hay suficiente existencia. Disponible: " . $existencia_total;
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Calcular la diferencia de cantidad
        $diferencia = $nueva_cantidad - $cantidad_actual;
        error_log("actualizarCantidadCarrito - Diferencia de cantidad: $diferencia");

        // Actualizar la cantidad en el carrito
        $sql = "UPDATE carrito SET cantidad = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $nueva_cantidad, $id);

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar la cantidad: " . $stmt->error);
        }

        $filas_afectadas = $stmt->affected_rows;
        error_log("actualizarCantidadCarrito - Filas afectadas al actualizar carrito: $filas_afectadas");

        // Actualizar la existencia del producto
        $nueva_existencia = $existencia_total - $nueva_cantidad;
        error_log("actualizarCantidadCarrito - Nueva existencia: $nueva_existencia");

        $sql = "UPDATE productos_tienda SET existencia_producto = ? WHERE id_producto = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $nueva_existencia, $producto_carrito['producto_id']);

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar la existencia: " . $stmt->error);
        }

        $filas_afectadas = $stmt->affected_rows;
        error_log("actualizarCantidadCarrito - Filas afectadas al actualizar existencia: $filas_afectadas");

        // Confirmar transacción
        $conn->commit();
        error_log("actualizarCantidadCarrito - Transacción completada con éxito");
        return true;
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        error_log("Error en actualizarCantidadCarrito: " . $e->getMessage());
        return $e->getMessage();
    }
}

/**
 * Transfiere los productos del carrito a la tabla pedido
 * @param int $usuario_id ID del usuario
 * @return bool True si se transfirieron correctamente, False en caso contrario
 */
function completarCompra($usuario_id) {
    global $conn;

    // Registrar información de depuración
    error_log("completarCompra - Iniciando proceso para usuario ID: " . $usuario_id);

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Obtener productos del carrito
        $sql = "SELECT * FROM carrito WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $num_productos = $result->num_rows;
        error_log("completarCompra - Productos en el carrito: " . $num_productos);

        if ($num_productos === 0) {
            // No hay productos en el carrito
            error_log("completarCompra - No hay productos en el carrito");
            $conn->rollback();
            return false;
        }

        $productos = [];
        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }

        // Registrar información de depuración
        error_log("completarCompra - Productos a transferir: " . print_r($productos, true));

        // Transferir cada producto a la tabla pedido
        foreach ($productos as $producto) {
            // Registrar datos del producto que se está intentando insertar
            error_log("completarCompra - Intentando insertar producto: " . print_r($producto, true));
            
            // Insertar el nuevo pedido
            $sql = "INSERT INTO pedido (usuario_id, producto_id, cantidad, precio, precio_dolares, precio_bolivares, pedido, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            error_log("completarCompra - Consulta SQL: " . $sql);
            
            $stmt_insert = $conn->prepare($sql);
            if ($stmt_insert === false) {
                error_log("completarCompra - Error en prepare: " . $conn->error);
                $conn->rollback();
                return false;
            }
            
            $usuario_id = $producto['usuario_id'];
            $producto_id = $producto['producto_id'];
            $cantidad = $producto['cantidad'];
            $precio = $producto['precio'];
            $precio_dolares = $producto['precio_dolares'];
            $precio_bolivares = $producto['precio_bolivares'];
            $pedido = $producto['pedido'];
            
            $stmt_insert->bind_param("iiiddds", $usuario_id, $producto_id, $cantidad, $precio, $precio_dolares, $precio_bolivares, $pedido);

            if (!$stmt_insert->execute()) {
                error_log("completarCompra - Error al ejecutar la consulta: " . $stmt_insert->error);
                error_log("completarCompra - Código de error: " . $stmt_insert->errno);
                $conn->rollback();
                return false;
            }
            
            error_log("completarCompra - Nuevo pedido insertado para producto ID: " . $producto_id . " con código de pedido: " . $pedido);

            error_log("completarCompra - Producto transferido: ID=" . $producto['producto_id'] . ", Cantidad=" . $producto['cantidad']);
        }

        // Eliminar productos del carrito (sin devolver existencia)
        $sql = "DELETE FROM carrito WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);

        if (!$stmt->execute()) {
            error_log("completarCompra - Error al eliminar del carrito: " . $stmt->error);
            $conn->rollback();
            return false;
        }

        $filas_afectadas = $stmt->affected_rows;
        error_log("completarCompra - Productos eliminados del carrito: " . $filas_afectadas);

        // Confirmar transacción
        $conn->commit();
        error_log("completarCompra - Transacción completada con éxito");
        return true;
    } catch (Exception $e) {
        // Error en la transacción
        $conn->rollback();
        error_log("Error en completarCompra: " . $e->getMessage());
        return false;
    }
}
