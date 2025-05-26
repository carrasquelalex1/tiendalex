<?php
// Incluir el archivo de conexión a la base de datos
require_once '../backend/config/db.php';

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $id_producto = isset($_POST['id_producto']) && is_numeric($_POST['id_producto']) ? $_POST['id_producto'] : 0;
    $existencia_producto = isset($_POST['existencia_producto']) && is_numeric($_POST['existencia_producto']) ? intval($_POST['existencia_producto']) : 0;

    // Validar que el ID del producto sea válido
    if ($id_producto <= 0) {
        header("Location: ../catalogo.php?error=1&message=" . urlencode("ID de producto no válido."));
        exit;
    }

    // Preparar la consulta SQL para actualizar la existencia
    $sql = "UPDATE productos_tienda SET existencia_producto = ? WHERE id_producto = ?";
    
    // Preparar la sentencia
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Vincular parámetros
        $stmt->bind_param("ii", $existencia_producto, $id_producto);

        // Ejecutar la sentencia
        if ($stmt->execute()) {
            // Redirigir a la página de catálogo con un mensaje de éxito
            header("Location: ../catalogo.php?success=3&message=" . urlencode("Disponibilidad actualizada correctamente."));
            exit;
        } else {
            // Redirigir a la página de catálogo con un mensaje de error
            header("Location: ../catalogo.php?error=1&message=" . urlencode("Error al actualizar la disponibilidad: " . $stmt->error));
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
