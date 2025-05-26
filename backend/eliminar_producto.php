<?php
// Incluir el archivo de conexión a la base de datos
require_once '../backend/config/db.php';

/**
 * Función para eliminar un archivo físico
 * @param string $filepath Ruta del archivo a eliminar
 * @return bool True si se eliminó correctamente, False en caso contrario
 */
function eliminarArchivoFisico($filepath) {
    // Asegurarse de que la ruta sea relativa a la raíz del proyecto
    if (strpos($filepath, 'images/') === 0) {
        $filepath = '../' . $filepath;
    }
    
    // Verificar si el archivo existe
    if (file_exists($filepath)) {
        // Intentar eliminar el archivo
        if (unlink($filepath)) {
            return true;
        } else {
            // Registrar el error
            error_log("Error al eliminar el archivo: " . $filepath);
            return false;
        }
    } else {
        // El archivo no existe
        error_log("El archivo no existe: " . $filepath);
        return false;
    }
}

/**
 * Función para obtener las rutas de imágenes de un producto
 * @param int $id_producto ID del producto
 * @return array Array con las rutas de las imágenes
 */
function obtenerImagenesProducto($id_producto) {
    global $conn;
    
    $sql = "SELECT imagen_producto, imagen_producto2, imagen_producto3, imagen_producto4, imagen_producto5 
            FROM productos_tienda 
            WHERE id_producto = ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Filtrar solo las imágenes que existen
            $imagenes = [];
            if (!empty($row['imagen_producto'])) $imagenes[] = $row['imagen_producto'];
            if (!empty($row['imagen_producto2'])) $imagenes[] = $row['imagen_producto2'];
            if (!empty($row['imagen_producto3'])) $imagenes[] = $row['imagen_producto3'];
            if (!empty($row['imagen_producto4'])) $imagenes[] = $row['imagen_producto4'];
            if (!empty($row['imagen_producto5'])) $imagenes[] = $row['imagen_producto5'];
            
            return $imagenes;
        }
        
        $stmt->close();
    }
    
    return [];
}

/**
 * Función para eliminar un producto y sus imágenes
 * @param int $id_producto ID del producto a eliminar
 * @return bool True si se eliminó correctamente, False en caso contrario
 */
function eliminarProducto($id_producto) {
    global $conn;
    
    // Obtener las imágenes del producto
    $imagenes = obtenerImagenesProducto($id_producto);
    
    // Eliminar el producto de la base de datos
    $sql = "DELETE FROM productos_tienda WHERE id_producto = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id_producto);
        $resultado = $stmt->execute();
        $stmt->close();
        
        if ($resultado) {
            // Eliminar los archivos físicos de las imágenes
            foreach ($imagenes as $imagen) {
                eliminarArchivoFisico($imagen);
            }
            
            return true;
        }
    }
    
    return false;
}

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $id_producto = isset($_POST['id_producto']) && is_numeric($_POST['id_producto']) ? $_POST['id_producto'] : 0;
    
    // Validar que el ID del producto sea válido
    if ($id_producto <= 0) {
        header("Location: ../catalogo.php?error=1&message=" . urlencode("ID de producto no válido."));
        exit;
    }
    
    // Eliminar el producto y sus imágenes
    if (eliminarProducto($id_producto)) {
        header("Location: ../catalogo.php?success=1&message=" . urlencode("Producto eliminado correctamente."));
    } else {
        header("Location: ../catalogo.php?error=1&message=" . urlencode("Error al eliminar el producto."));
    }
    
    exit;
} else {
    // Si no se ha enviado el formulario, redirigir a la página de catálogo
    header("Location: ../catalogo.php");
    exit;
}
?>
