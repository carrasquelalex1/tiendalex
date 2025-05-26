<?php
// Incluir archivos necesarios
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/../helpers/session/session_helper.php';
require_once __DIR__ . '/../autoload.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $contrasena = $_POST['contrasena'];

    // Registrar intento de inicio de sesión
    error_log("Intento de inicio de sesión para usuario: " . $nombre_usuario);

    try {
        // Preparar la consulta
        $sql = "SELECT * FROM usuario WHERE nombre_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nombre_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verificar la contraseña
            if (password_verify($contrasena, $user['contrasena'])) {
                // Inicializar la sesión
                $_SESSION['usuario_id'] = $user['codigo'];
                $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
                $_SESSION['rol_codigo'] = $user['rol_codigo'];
                
                // Mantener compatibilidad temporal con código que aún usa 'usuario_logueado'
                $_SESSION['usuario_logueado'] = $user['codigo'];

                // Registrar información detallada de la sesión
                error_log("login_process.php - Inicio de sesión exitoso para: " . $nombre_usuario);
                error_log("login_process.php - Datos de usuario: " . print_r($user, true));
                error_log("login_process.php - SESSION después de login: " . print_r($_SESSION, true));

                // Si es un cliente, generar código de pedido
                if ($user['rol_codigo'] != 1) {
                    $_SESSION['codigo_pedido'] = Carrito::generarCodigoPedidoUnico($conn);
                    error_log("login_process.php - Código de pedido generado: " . $_SESSION['codigo_pedido']);
                }

                // Regenerar ID de sesión para mayor seguridad
                regenerar_sesion(true);
                error_log("login_process.php - ID de sesión regenerado");

                // Asegurarse de que la sesión se guarde
                session_write_close();

                // Redirigir basado en el rol
                if ($user['rol_codigo'] == 1) { // Administrador
                    header("Location: ../catalogo.php");
                } else { // Cliente u otros roles
                    header("Location: ../index.php");
                }
                exit();
            } else {
                // Contraseña incorrecta
                error_log("Contraseña incorrecta para: " . $nombre_usuario);
                header("Location: ../login.php?error=1");
                exit();
            }
        } else {
            // Usuario no encontrado
            error_log("Usuario no encontrado: " . $nombre_usuario);
            header("Location: ../login.php?error=1");
            exit();
        }
    } catch (Exception $e) {
        // Error en la base de datos
        error_log("Error en login_process.php: " . $e->getMessage());
        header("Location: ../login.php?error=2");
        exit();
    }
}

// Si se llega aquí, redirigir a la página de login
header("Location: ../login.php");
exit();
?>
