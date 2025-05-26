<?php
// Incluir el archivo de conexión a la base de datos
require_once '../backend/config/db.php';

// Función para obtener todas las categorías
function obtenerCategorias() {
    global $conn;
    
    $sql = "SELECT * FROM categorias ORDER BY nombre_categoria ASC";
    $result = $conn->query($sql);
    
    $categorias = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categorias[] = $row;
        }
    }
    
    return $categorias;
}

// Función para obtener una categoría por ID
function obtenerCategoriaPorId($id_categoria) {
    global $conn;
    
    $sql = "SELECT * FROM categorias WHERE id_categoria = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id_categoria);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    }
    
    return null;
}

// Función para verificar si una categoría está en uso
function categoriaEnUso($id_categoria) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as total FROM productos_tienda WHERE categoria_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id_categoria);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['total'] > 0;
        }
    }
    
    return false;
}

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';
    
    // Agregar nueva categoría
    if ($accion == 'agregar') {
        $nombre_categoria = isset($_POST['nombre_categoria']) ? trim($_POST['nombre_categoria']) : '';
        
        // Validar que el nombre no esté vacío
        if (empty($nombre_categoria)) {
            header("Location: ../catalogo.php?error=1&message=" . urlencode("El nombre de la categoría no puede estar vacío."));
            exit;
        }
        
        // Verificar si la categoría ya existe
        $sql = "SELECT * FROM categorias WHERE nombre_categoria = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $nombre_categoria);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                header("Location: ../catalogo.php?error=1&message=" . urlencode("La categoría '$nombre_categoria' ya existe."));
                exit;
            }
            
            $stmt->close();
        }
        
        // Insertar la nueva categoría
        $sql = "INSERT INTO categorias (nombre_categoria, created_at, updated_at) VALUES (?, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $nombre_categoria);
            
            if ($stmt->execute()) {
                header("Location: ../catalogo.php?success=4&message=" . urlencode("Categoría '$nombre_categoria' agregada correctamente."));
                exit;
            } else {
                header("Location: ../catalogo.php?error=1&message=" . urlencode("Error al agregar la categoría: " . $stmt->error));
                exit;
            }
            
            $stmt->close();
        } else {
            header("Location: ../catalogo.php?error=1&message=" . urlencode("Error en la preparación de la consulta: " . $conn->error));
            exit;
        }
    }
    
    // Editar categoría existente
    else if ($accion == 'editar') {
        $id_categoria = isset($_POST['id_categoria']) && is_numeric($_POST['id_categoria']) ? $_POST['id_categoria'] : 0;
        $nombre_categoria = isset($_POST['nombre_categoria']) ? trim($_POST['nombre_categoria']) : '';
        
        // Validar que el ID y el nombre no estén vacíos
        if ($id_categoria <= 0 || empty($nombre_categoria)) {
            header("Location: ../catalogo.php?error=1&message=" . urlencode("ID de categoría no válido o nombre vacío."));
            exit;
        }
        
        // Verificar si la categoría ya existe con ese nombre (excluyendo la actual)
        $sql = "SELECT * FROM categorias WHERE nombre_categoria = ? AND id_categoria != ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("si", $nombre_categoria, $id_categoria);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                header("Location: ../catalogo.php?error=1&message=" . urlencode("Ya existe otra categoría con el nombre '$nombre_categoria'."));
                exit;
            }
            
            $stmt->close();
        }
        
        // Actualizar la categoría
        $sql = "UPDATE categorias SET nombre_categoria = ?, updated_at = NOW() WHERE id_categoria = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("si", $nombre_categoria, $id_categoria);
            
            if ($stmt->execute()) {
                header("Location: ../catalogo.php?success=4&message=" . urlencode("Categoría actualizada correctamente a '$nombre_categoria'."));
                exit;
            } else {
                header("Location: ../catalogo.php?error=1&message=" . urlencode("Error al actualizar la categoría: " . $stmt->error));
                exit;
            }
            
            $stmt->close();
        } else {
            header("Location: ../catalogo.php?error=1&message=" . urlencode("Error en la preparación de la consulta: " . $conn->error));
            exit;
        }
    }
    
    // Eliminar categoría
    else if ($accion == 'eliminar') {
        $id_categoria = isset($_POST['id_categoria']) && is_numeric($_POST['id_categoria']) ? $_POST['id_categoria'] : 0;
        
        // Validar que el ID no esté vacío
        if ($id_categoria <= 0) {
            header("Location: ../catalogo.php?error=1&message=" . urlencode("ID de categoría no válido."));
            exit;
        }
        
        // Verificar si la categoría está en uso
        if (categoriaEnUso($id_categoria)) {
            header("Location: ../catalogo.php?error=1&message=" . urlencode("No se puede eliminar la categoría porque está asignada a uno o más productos."));
            exit;
        }
        
        // Eliminar la categoría
        $sql = "DELETE FROM categorias WHERE id_categoria = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $id_categoria);
            
            if ($stmt->execute()) {
                header("Location: ../catalogo.php?success=4&message=" . urlencode("Categoría eliminada correctamente."));
                exit;
            } else {
                header("Location: ../catalogo.php?error=1&message=" . urlencode("Error al eliminar la categoría: " . $stmt->error));
                exit;
            }
            
            $stmt->close();
        } else {
            header("Location: ../catalogo.php?error=1&message=" . urlencode("Error en la preparación de la consulta: " . $conn->error));
            exit;
        }
    }
    
    // Asignar categoría a producto
    else if ($accion == 'asignar_categoria') {
        $id_producto = isset($_POST['id_producto']) && is_numeric($_POST['id_producto']) ? $_POST['id_producto'] : 0;
        $categoria_id = isset($_POST['categoria_id']) && is_numeric($_POST['categoria_id']) ? $_POST['categoria_id'] : null;
        
        // Validar que el ID del producto sea válido
        if ($id_producto <= 0) {
            header("Location: ../catalogo.php?error=1&message=" . urlencode("ID de producto no válido."));
            exit;
        }
        
        // Actualizar la categoría del producto
        $sql = "UPDATE productos_tienda SET categoria_id = ?, updated_at = NOW() WHERE id_producto = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ii", $categoria_id, $id_producto);
            
            if ($stmt->execute()) {
                header("Location: ../catalogo.php?success=4&message=" . urlencode("Categoría asignada correctamente al producto."));
                exit;
            } else {
                header("Location: ../catalogo.php?error=1&message=" . urlencode("Error al asignar la categoría: " . $stmt->error));
                exit;
            }
            
            $stmt->close();
        } else {
            header("Location: ../catalogo.php?error=1&message=" . urlencode("Error en la preparación de la consulta: " . $conn->error));
            exit;
        }
    }
    
    // Acción no reconocida
    else {
        header("Location: ../catalogo.php?error=1&message=" . urlencode("Acción no reconocida."));
        exit;
    }
} else {
    // Si no se ha enviado el formulario, redirigir a la página de catálogo
    header("Location: ../catalogo.php");
    exit;
}
?>
