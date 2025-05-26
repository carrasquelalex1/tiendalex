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

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $id_producto = isset($_POST['id_producto']) && is_numeric($_POST['id_producto']) ? $_POST['id_producto'] : 0;
    $precio_producto = isset($_POST['precio_producto']) && is_numeric($_POST['precio_producto']) ? $_POST['precio_producto'] : 0.00;
    
    // Validar que el ID del producto sea válido
    if ($id_producto <= 0) {
        header("Location: ../catalogo.php?error=1&message=" . urlencode("ID de producto no válido."));
        exit;
    }
    
    // Obtener la tasa actual
    $tasa_actual = obtenerTasaActual();
    $valor_tasa = $tasa_actual ? $tasa_actual['valor_tasa'] : 0.00;
    
    // Calcular el precio en bolívares
    $precio_bolivares = $precio_producto * $valor_tasa;
    
    // Preparar la consulta SQL para actualizar el producto
    $sql = "UPDATE productos_tienda SET precio_producto = ?, precio_bolivares = ?, tasa = ? WHERE id_producto = ?";
    
    // Preparar la sentencia
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Vincular parámetros
        $stmt->bind_param("dddi", $precio_producto, $precio_bolivares, $valor_tasa, $id_producto);
        
        // Ejecutar la sentencia
        if ($stmt->execute()) {
            // Redirigir a la página de catálogo con un mensaje de éxito
            header("Location: ../catalogo.php?success=3&message=" . urlencode("Precio del producto actualizado correctamente."));
            exit;
        } else {
            // Redirigir a la página de catálogo con un mensaje de error
            header("Location: ../catalogo.php?error=1&message=" . urlencode("Error al actualizar el precio del producto: " . $stmt->error));
            exit;
        }
        
        // Cerrar la sentencia
        $stmt->close();
    } else {
        // Redirigir a la página de catálogo con un mensaje de error
        header("Location: ../catalogo.php?error=1&message=" . urlencode("Error en la preparación de la consulta: " . $conn->error));
        exit;
    }
    
    // Cerrar la conexión
    $conn->close();
} else {
    // Si no se ha enviado el formulario, redirigir a la página de catálogo
    header("Location: ../catalogo.php");
    exit;
}
?>
