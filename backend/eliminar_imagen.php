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
 * Función para obtener las rutas de imágenes actuales de un producto
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
 * Función para actualizar las imágenes de un producto
 * @param int $id_producto ID del producto
 * @param array $imagenes_actuales Array con las rutas de las imágenes actuales
 * @return bool True si se actualizó correctamente, False en caso contrario
 */
function actualizarImagenesProducto($id_producto, $imagenes_actuales) {
    global $conn;
    
    // Inicializar variables para las imágenes
    $imagen_producto = '';
    $imagen_producto2 = '';
    $imagen_producto3 = '';
    $imagen_producto4 = '';
    $imagen_producto5 = '';
    
    // Asignar las imágenes actuales a las variables correspondientes
    if (count($imagenes_actuales) > 0) {
        $imagen_producto = $imagenes_actuales[0];
    }
    if (count($imagenes_actuales) > 1) {
        $imagen_producto2 = $imagenes_actuales[1];
    }
    if (count($imagenes_actuales) > 2) {
        $imagen_producto3 = $imagenes_actuales[2];
    }
    if (count($imagenes_actuales) > 3) {
        $imagen_producto4 = $imagenes_actuales[3];
    }
    if (count($imagenes_actuales) > 4) {
        $imagen_producto5 = $imagenes_actuales[4];
    }
    
    // Actualizar las imágenes en la base de datos
    $sql = "UPDATE productos_tienda SET
            imagen_producto = ?,
            imagen_producto2 = ?,
            imagen_producto3 = ?,
            imagen_producto4 = ?,
            imagen_producto5 = ?
            WHERE id_producto = ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("sssssi", $imagen_producto, $imagen_producto2, $imagen_producto3, $imagen_producto4, $imagen_producto5, $id_producto);
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }
        
        $stmt->close();
    }
    
    return false;
}

// Verificar si se ha enviado el formulario mediante AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $id_producto = isset($_POST['id_producto']) && is_numeric($_POST['id_producto']) ? $_POST['id_producto'] : 0;
    $ruta_imagen = isset($_POST['ruta_imagen']) ? $_POST['ruta_imagen'] : '';
    
    // Validar que el ID del producto y la ruta de la imagen sean válidos
    if ($id_producto <= 0 || empty($ruta_imagen)) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos inválidos. Se requiere ID de producto y ruta de imagen.'
        ]);
        exit;
    }
    
    // Obtener las imágenes actuales del producto
    $imagenes_actuales = obtenerImagenesProducto($id_producto);
    
    // Verificar si la imagen a eliminar existe en las imágenes actuales
    if (!in_array($ruta_imagen, $imagenes_actuales)) {
        echo json_encode([
            'success' => false,
            'message' => 'La imagen no pertenece al producto especificado.'
        ]);
        exit;
    }
    
    // Eliminar la imagen del array de imágenes actuales
    $imagenes_actuales = array_diff($imagenes_actuales, [$ruta_imagen]);
    // Reindexar el array para evitar índices no consecutivos
    $imagenes_actuales = array_values($imagenes_actuales);
    
    // Eliminar el archivo físico
    $resultado_eliminacion = eliminarArchivoFisico($ruta_imagen);
    
    // Actualizar las imágenes en la base de datos
    $resultado_actualizacion = actualizarImagenesProducto($id_producto, $imagenes_actuales);
    
    // Devolver respuesta
    if ($resultado_eliminacion && $resultado_actualizacion) {
        echo json_encode([
            'success' => true,
            'message' => 'Imagen eliminada correctamente.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar la imagen. ' . 
                        ($resultado_eliminacion ? '' : 'No se pudo eliminar el archivo físico. ') . 
                        ($resultado_actualizacion ? '' : 'No se pudo actualizar la base de datos.')
        ]);
    }
    
    exit;
} else {
    // Si no se ha enviado el formulario mediante AJAX, redirigir a la página de catálogo
    header("Location: ../catalogo.php");
    exit;
}
?>
