<?php
// Incluir el archivo de conexión a la base de datos
require_once '../backend/config/db.php';

// Función para procesar y guardar una imagen
function procesarImagen($file, $producto_id, $index) {
    // Verificar si hay un error en la carga
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'Error al cargar la imagen: ' . $file['error']
        ];
    }

    // Verificar el tamaño del archivo (máximo 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return [
            'success' => false,
            'message' => 'La imagen es demasiado grande. El tamaño máximo permitido es 2MB.'
        ];
    }

    // Verificar el tipo de archivo
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($file['type'], $allowed_types)) {
        return [
            'success' => false,
            'message' => 'Tipo de archivo no permitido. Solo se permiten imágenes JPG, JPEG y PNG.'
        ];
    }

    // Crear directorio si no existe
    $upload_dir = '../images/productos/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generar un nombre único para la imagen
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'producto_' . $producto_id . '_' . time() . '_' . $index . '.' . $extension;
    $filepath = $upload_dir . $filename;

    // Mover el archivo al directorio de destino
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Establecer permisos de archivo (0644 o 0666)
        chmod($filepath, 0666);

        // Registrar información de depuración
        error_log("Imagen guardada correctamente: " . $filepath);

        return [
            'success' => true,
            'filepath' => 'images/productos/' . $filename
        ];
    } else {
        // Depurar el error
        $error = error_get_last();
        error_log("Error al guardar la imagen: " . ($error ? $error['message'] : 'Desconocido'));
        error_log("Ruta: " . $filepath);
        error_log("Permisos de directorio: " . substr(sprintf('%o', fileperms($upload_dir)), -4));

        return [
            'success' => false,
            'message' => 'Error al guardar la imagen en el servidor: ' . ($error ? $error['message'] : 'Desconocido')
        ];
    }
}

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $id_producto = isset($_POST['id_producto']) && is_numeric($_POST['id_producto']) ? $_POST['id_producto'] : 0;
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    // Validar que el ID del producto sea válido
    if ($id_producto <= 0) {
        header("Location: ../catalogo.php?error=1&message=" . urlencode("ID de producto no válido."));
        exit;
    }

    // Procesar según la acción
    if ($accion === 'actualizar_imagenes') {
        // Obtener las imágenes actuales
        $imagenes_actuales = isset($_POST['imagenes_actuales']) ? $_POST['imagenes_actuales'] : [];

        // Inicializar array para las rutas de imágenes
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

        // Procesar nuevas imágenes si se han subido
        if (isset($_FILES['nuevas_imagenes']) && !empty($_FILES['nuevas_imagenes']['name'][0])) {
            $total_imagenes = count($imagenes_actuales);
            $nuevas_imagenes = $_FILES['nuevas_imagenes'];

            // Procesar cada nueva imagen
            for ($i = 0; $i < count($nuevas_imagenes['name']); $i++) {
                // Verificar si ya tenemos 5 imágenes
                if ($total_imagenes >= 5) {
                    break;
                }

                // Crear un array con la información de la imagen actual
                $file = [
                    'name' => $nuevas_imagenes['name'][$i],
                    'type' => $nuevas_imagenes['type'][$i],
                    'tmp_name' => $nuevas_imagenes['tmp_name'][$i],
                    'error' => $nuevas_imagenes['error'][$i],
                    'size' => $nuevas_imagenes['size'][$i]
                ];

                // Procesar la imagen
                $resultado = procesarImagen($file, $id_producto, $i);

                if ($resultado['success']) {
                    $total_imagenes++;

                    // Asignar la ruta de la imagen a la variable correspondiente
                    if (empty($imagen_producto)) {
                        $imagen_producto = $resultado['filepath'];
                    } elseif (empty($imagen_producto2)) {
                        $imagen_producto2 = $resultado['filepath'];
                    } elseif (empty($imagen_producto3)) {
                        $imagen_producto3 = $resultado['filepath'];
                    } elseif (empty($imagen_producto4)) {
                        $imagen_producto4 = $resultado['filepath'];
                    } elseif (empty($imagen_producto5)) {
                        $imagen_producto5 = $resultado['filepath'];
                    }
                } else {
                    // Si hay un error, mostrar mensaje y continuar con la siguiente imagen
                    header("Location: ../catalogo.php?error=1&message=" . urlencode($resultado['message']));
                    exit;
                }
            }
        }

        // Obtener las imágenes actuales antes de la actualización
        $sql_select = "SELECT imagen_producto, imagen_producto2, imagen_producto3, imagen_producto4, imagen_producto5
                      FROM productos_tienda
                      WHERE id_producto = ?";

        $stmt_select = $conn->prepare($sql_select);
        $imagenes_anteriores = [];

        if ($stmt_select) {
            $stmt_select->bind_param("i", $id_producto);

            if ($stmt_select->execute()) {
                $result = $stmt_select->get_result();

                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();

                    // Guardar las imágenes anteriores
                    if (!empty($row['imagen_producto'])) $imagenes_anteriores[] = $row['imagen_producto'];
                    if (!empty($row['imagen_producto2'])) $imagenes_anteriores[] = $row['imagen_producto2'];
                    if (!empty($row['imagen_producto3'])) $imagenes_anteriores[] = $row['imagen_producto3'];
                    if (!empty($row['imagen_producto4'])) $imagenes_anteriores[] = $row['imagen_producto4'];
                    if (!empty($row['imagen_producto5'])) $imagenes_anteriores[] = $row['imagen_producto5'];
                }
            }

            $stmt_select->close();
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
                // Crear un array con las imágenes actuales
                $imagenes_actuales = [];
                if (!empty($imagen_producto)) $imagenes_actuales[] = $imagen_producto;
                if (!empty($imagen_producto2)) $imagenes_actuales[] = $imagen_producto2;
                if (!empty($imagen_producto3)) $imagenes_actuales[] = $imagen_producto3;
                if (!empty($imagen_producto4)) $imagenes_actuales[] = $imagen_producto4;
                if (!empty($imagen_producto5)) $imagenes_actuales[] = $imagen_producto5;

                // Encontrar las imágenes que ya no se utilizan
                $imagenes_eliminadas = array_diff($imagenes_anteriores, $imagenes_actuales);

                // Eliminar los archivos físicos de las imágenes que ya no se utilizan
                foreach ($imagenes_eliminadas as $imagen) {
                    $ruta_completa = '../' . $imagen;
                    if (file_exists($ruta_completa)) {
                        unlink($ruta_completa);
                    }
                }

                header("Location: ../catalogo.php?success=1&message=" . urlencode("Imágenes actualizadas correctamente."));
                exit;
            } else {
                header("Location: ../catalogo.php?error=1&message=" . urlencode("Error al actualizar las imágenes: " . $stmt->error));
                exit;
            }

            $stmt->close();
        } else {
            header("Location: ../catalogo.php?error=1&message=" . urlencode("Error en la preparación de la consulta: " . $conn->error));
            exit;
        }
    } else {
        header("Location: ../catalogo.php?error=1&message=" . urlencode("Acción no válida."));
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
