<?php
// Clase para el manejo del carrito de compras
class Carrito {
    public static function generarCodigoPedido() {
        $letras = '';
        for ($i = 0; $i < 2; $i++) {
            $letras .= chr(rand(65, 90));
        }
        $numeros = '';
        for ($i = 0; $i < 6; $i++) {
            $numeros .= rand(0, 9);
        }
        return $letras . $numeros;
    }

    public static function codigoPedidoExiste($codigo, $conn) {
        $sql = "SELECT COUNT(*) as count FROM carrito WHERE pedido = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row['count'] > 0) {
            return true;
        }
        $sql = "SELECT COUNT(*) as count FROM pedido WHERE pedido = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    public static function generarCodigoPedidoUnico($conn) {
        $codigo = self::generarCodigoPedido();
        while (self::codigoPedidoExiste($codigo, $conn)) {
            $codigo = self::generarCodigoPedido();
        }
        return $codigo;
    }

    public static function obtenerCodigoPedidoUsuario($usuario_id, $conn) {
        $sql = "SELECT DISTINCT pedido FROM carrito WHERE usuario_id = ? AND pedido IS NOT NULL LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['pedido'];
        }
        return self::generarCodigoPedidoUnico($conn);
    }

    public static function contarProductosCarrito($usuario_id, $conn) {
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

    public static function productoEnCarrito($usuario_id, $producto_id, $conn) {
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

    public static function agregarProductoCarrito($usuario_id, $producto_id, $cantidad, $codigo_pedido, $conn) {
        $sql = "SELECT * FROM productos_tienda WHERE id_producto = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            return false;
        }
        $producto = $result->fetch_assoc();
        if ($producto['existencia_producto'] < $cantidad) {
            return false;
        }
        $producto_carrito = self::productoEnCarrito($usuario_id, $producto_id, $conn);
        if ($producto_carrito) {
            $nueva_cantidad = $producto_carrito['cantidad'] + $cantidad;
            if ($producto['existencia_producto'] < $nueva_cantidad) {
                return false;
            }
            $sql = "UPDATE carrito SET cantidad = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $nueva_cantidad, $producto_carrito['id']);
        } else {
            $sql = "INSERT INTO carrito (usuario_id, producto_id, cantidad, precio, precio_dolares, precio_bolivares, pedido) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiiddds", $usuario_id, $producto_id, $cantidad, $producto['precio_producto'], $producto['precio_producto'], $producto['precio_bolivares'], $codigo_pedido);
        }
        if ($stmt->execute()) {
            $nueva_existencia = $producto['existencia_producto'] - $cantidad;
            $sql = "UPDATE productos_tienda SET existencia_producto = ? WHERE id_producto = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $nueva_existencia, $producto_id);
            return $stmt->execute();
        }
        return false;
    }

    public static function limpiarCarrito($usuario_id, $conn) {
        error_log("limpiarCarrito - Iniciando limpieza del carrito para usuario ID: " . $usuario_id);
        $conn->begin_transaction();
        try {
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
            $sql = "DELETE FROM carrito WHERE usuario_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
            $resultado = $stmt->execute();
            if ($resultado) {
                $filas_afectadas = $stmt->affected_rows;
                error_log("limpiarCarrito - Productos eliminados del carrito: " . $filas_afectadas);
                $conn->commit();
                return true;
            } else {
                error_log("limpiarCarrito - Error al eliminar productos del carrito: " . $stmt->error);
                $conn->rollback();
                return false;
            }
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error en limpiarCarrito: " . $e->getMessage());
            return false;
        }
    }

    public static function actualizarCantidadCarrito($id, $nueva_cantidad, $usuario_id, $conn) {
        error_log("actualizarCantidadCarrito - Iniciando actualización: id=$id, nueva_cantidad=$nueva_cantidad, usuario_id=$usuario_id");
        $sql = "SELECT c.*, p.existencia_producto, p.nombre_producto FROM carrito c JOIN productos_tienda p ON c.producto_id = p.id_producto WHERE c.id = ? AND c.usuario_id = ?";
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
        $existencia_total = $producto_carrito['existencia_producto'] + $cantidad_actual;
        error_log("actualizarCantidadCarrito - Información del producto: " . print_r($producto_carrito, true));
        error_log("actualizarCantidadCarrito - Cantidad actual: $cantidad_actual, Existencia total: $existencia_total");
        if ($nueva_cantidad <= 0) {
            error_log("actualizarCantidadCarrito - Cantidad no válida: $nueva_cantidad");
            return "La cantidad debe ser mayor a 0.";
        }
        if ($nueva_cantidad > $existencia_total) {
            error_log("actualizarCantidadCarrito - No hay suficiente existencia: $nueva_cantidad > $existencia_total");
            return "No hay suficiente existencia. Disponible: " . $existencia_total;
        }
        $conn->begin_transaction();
        try {
            $diferencia = $nueva_cantidad - $cantidad_actual;
            error_log("actualizarCantidadCarrito - Diferencia de cantidad: $diferencia");
            $sql = "UPDATE carrito SET cantidad = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $nueva_cantidad, $id);
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar la cantidad: " . $stmt->error);
            }
            $filas_afectadas = $stmt->affected_rows;
            error_log("actualizarCantidadCarrito - Filas afectadas al actualizar carrito: $filas_afectadas");
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
            $conn->commit();
            error_log("actualizarCantidadCarrito - Transacción completada con éxito");
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error en actualizarCantidadCarrito: " . $e->getMessage());
            return $e->getMessage();
        }
    }

    public static function completarCompra($usuario_id, $conn) {
        error_log("completarCompra - Iniciando proceso para usuario ID: " . $usuario_id);
        $conn->begin_transaction();
        try {
            $sql = "SELECT * FROM carrito WHERE usuario_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $num_productos = $result->num_rows;
            error_log("completarCompra - Productos en el carrito: " . $num_productos);
            if ($num_productos === 0) {
                error_log("completarCompra - No hay productos en el carrito");
                $conn->rollback();
                return false;
            }
            $productos = [];
            while ($row = $result->fetch_assoc()) {
                $productos[] = $row;
            }
            error_log("completarCompra - Productos a transferir: " . print_r($productos, true));
            foreach ($productos as $producto) {
                $sql = "INSERT INTO pedido (usuario_id, producto_id, cantidad, precio, precio_dolares, precio_bolivares, pedido, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt_insert = $conn->prepare($sql);
                $stmt_insert->bind_param("iiiddds", $producto['usuario_id'], $producto['producto_id'], $producto['cantidad'], $producto['precio'], $producto['precio_dolares'], $producto['precio_bolivares'], $producto['pedido']);
                if (!$stmt_insert->execute()) {
                    error_log("completarCompra - Error al insertar en tabla pedido: " . $stmt_insert->error);
                    $conn->rollback();
                    return false;
                }
                error_log("completarCompra - Producto transferido: ID=" . $producto['producto_id'] . ", Cantidad=" . $producto['cantidad']);
            }
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
            $conn->commit();
            error_log("completarCompra - Transacción completada con éxito");
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error en completarCompra: " . $e->getMessage());
            return false;
        }
    }

    // Obtener los productos en el carrito
    public static function obtenerProductosCarrito($usuario_id, $conn) {
        $sql = "SELECT c.*, p.nombre_producto, p.imagen_producto, p.imagen_producto2, p.imagen_producto3, p.imagen_producto4, p.imagen_producto5
                FROM carrito c
                JOIN productos_tienda p ON c.producto_id = p.id_producto
                WHERE c.usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $productos = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $productos[] = $row;
            }
        }
        return $productos;
    }
}
