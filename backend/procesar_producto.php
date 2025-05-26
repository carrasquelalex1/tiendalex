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
    $nombre_producto = isset($_POST['nombre_producto']) ? $_POST['nombre_producto'] : '';
    $descripcion_producto = isset($_POST['descripcion_producto']) ? $_POST['descripcion_producto'] : '';
    $precio_producto = isset($_POST['precio_producto']) && is_numeric($_POST['precio_producto']) ? $_POST['precio_producto'] : 0.00;
    $precio_bolivares = isset($_POST['precio_bolivares']) && is_numeric($_POST['precio_bolivares']) ? $_POST['precio_bolivares'] : 0.00;
    $categoria_id = isset($_POST['categoria_id']) && is_numeric($_POST['categoria_id']) ? $_POST['categoria_id'] : null;
    $existencia_producto = isset($_POST['existencia_producto']) && is_numeric($_POST['existencia_producto']) ? intval($_POST['existencia_producto']) : 0;

    // Obtener la tasa actual
    $tasa_actual = obtenerTasaActual();
    $valor_tasa = $tasa_actual ? $tasa_actual['valor_tasa'] : 0.00;

    // Si no se proporcionó un precio en bolívares pero sí en dólares y hay una tasa, calcularlo
    if ($precio_bolivares == 0 && $precio_producto > 0 && $valor_tasa > 0) {
        $precio_bolivares = $precio_producto * $valor_tasa;
    }

    // Validar que el nombre no esté vacío
    if (empty($nombre_producto)) {
        echo "Error: El nombre del producto no puede estar vacío.";
        exit;
    }

    // Procesar imágenes si se han subido
    $imagen_producto = '';
    $imagen_producto2 = '';
    $imagen_producto3 = '';
    $imagen_producto4 = '';
    $imagen_producto5 = '';

    if (isset($_FILES['imagenes_producto']) && !empty($_FILES['imagenes_producto']['name'][0])) {
        // Incluir la función para procesar imágenes
        require_once 'gestionar_imagenes.php';

        $imagenes = $_FILES['imagenes_producto'];
        $total_imagenes = 0;

        // Crear directorio si no existe
        $upload_dir = '../images/productos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Procesar cada imagen
        for ($i = 0; $i < count($imagenes['name']) && $total_imagenes < 5; $i++) {
            // Verificar que el archivo existe y no está vacío
            if (empty($imagenes['name'][$i]) || $imagenes['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            // Crear un array con la información de la imagen actual
            $file = [
                'name' => $imagenes['name'][$i],
                'type' => $imagenes['type'][$i],
                'tmp_name' => $imagenes['tmp_name'][$i],
                'error' => $imagenes['error'][$i],
                'size' => $imagenes['size'][$i]
            ];

            // Procesar la imagen (usaremos un ID temporal ya que aún no tenemos el ID del producto)
            $resultado = procesarImagen($file, time() . '_temp', $i);

            if ($resultado['success']) {
                $total_imagenes++;

                // Asignar la ruta de la imagen a la variable correspondiente
                if ($total_imagenes == 1) {
                    $imagen_producto = $resultado['filepath'];
                } elseif ($total_imagenes == 2) {
                    $imagen_producto2 = $resultado['filepath'];
                } elseif ($total_imagenes == 3) {
                    $imagen_producto3 = $resultado['filepath'];
                } elseif ($total_imagenes == 4) {
                    $imagen_producto4 = $resultado['filepath'];
                } elseif ($total_imagenes == 5) {
                    $imagen_producto5 = $resultado['filepath'];
                }
            } else {
                // Si hay un error, mostrar mensaje y continuar con la siguiente imagen
                error_log("Error al procesar imagen: " . $resultado['message']);
            }
        }
    }

    // Preparar la consulta SQL para insertar el producto
    $sql = "INSERT INTO productos_tienda (nombre_producto, descripcion_producto, precio_producto, precio_bolivares, tasa, categoria_id, existencia_producto, imagen_producto, imagen_producto2, imagen_producto3, imagen_producto4, imagen_producto5) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Preparar la sentencia
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Vincular parámetros
        $stmt->bind_param("ssdddiiissss", $nombre_producto, $descripcion_producto, $precio_producto, $precio_bolivares, $valor_tasa, $categoria_id, $existencia_producto, $imagen_producto, $imagen_producto2, $imagen_producto3, $imagen_producto4, $imagen_producto5);

        // Ejecutar la sentencia
        if ($stmt->execute()) {
            // Redirigir a la página de catálogo con un mensaje de éxito
            header("Location: ../catalogo.php?success=1");
            exit;
        } else {
            // Redirigir a la página de catálogo con un mensaje de error
            header("Location: ../catalogo.php?error=1&message=" . urlencode("Error al guardar el producto: " . $stmt->error));
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
