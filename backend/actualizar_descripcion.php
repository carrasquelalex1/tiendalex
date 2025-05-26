<?php
// Incluir el archivo de conexión a la base de datos
require_once '../backend/config/db.php';

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $id_producto = isset($_POST['id_producto']) && is_numeric($_POST['id_producto']) ? $_POST['id_producto'] : 0;
    $descripcion_producto = isset($_POST['descripcion_producto']) ? $_POST['descripcion_producto'] : '';
    $eliminar = isset($_POST['eliminar']) && $_POST['eliminar'] == 1;

    // Validar que el ID del producto sea válido
    if ($id_producto <= 0) {
        header("Location: ../catalogo.php?error=1&message=" . urlencode("ID de producto no válido."));
        exit;
    }

    // Preparar la consulta SQL para actualizar la descripción
    if ($eliminar) {
        // Si se solicitó eliminar la descripción
        $sql = "UPDATE productos_tienda SET descripcion_producto = '' WHERE id_producto = ?";
        $mensaje_exito = "Descripción eliminada correctamente.";
    } else {
        // Actualizar la descripción
        $sql = "UPDATE productos_tienda SET descripcion_producto = ? WHERE id_producto = ?";
        $mensaje_exito = "Descripción actualizada correctamente.";
    }

    // Preparar la sentencia
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        if ($eliminar) {
            // Vincular parámetros para eliminar
            $stmt->bind_param("i", $id_producto);
        } else {
            // Vincular parámetros para actualizar
            $stmt->bind_param("si", $descripcion_producto, $id_producto);
        }

        // Ejecutar la sentencia
        if ($stmt->execute()) {
            // Redirigir a la página de catálogo con un mensaje de éxito
            header("Location: ../catalogo.php?success=2&message=" . urlencode($mensaje_exito));
            exit;
        } else {
            // Redirigir a la página de catálogo con un mensaje de error
            header("Location: ../catalogo.php?error=1&message=" . urlencode("Error al actualizar la descripción: " . $stmt->error));
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
