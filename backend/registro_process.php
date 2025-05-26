<?php
// Incluir archivos necesarios
require_once '../backend/config/db.php';
require_once '../backend/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y limpiar datos del formulario
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $correo_electronico = trim($_POST['correo_electronico']);
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];

    // Validaciones básicas
    if (empty($nombre_usuario) || empty($correo_electronico) || empty($contrasena)) {
        header("Location: ../registro.php?error=empty");
        exit();
    }

    // Validar formato de correo electrónico
    if (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../registro.php?error=email_format");
        exit();
    }

    // Validar longitud de contraseña
    if (strlen($contrasena) < 6) {
        header("Location: ../registro.php?error=password");
        exit();
    }

    // Validar que las contraseñas coincidan
    if ($contrasena !== $confirmar_contrasena) {
        header("Location: ../registro.php?error=password_match");
        exit();
    }

    try {
        // Verificar si el nombre de usuario ya existe
        $check_username = "SELECT COUNT(*) FROM usuario WHERE nombre_usuario = ?";
        $stmt_username = $conn->prepare($check_username);
        $stmt_username->bind_param("s", $nombre_usuario);
        $stmt_username->execute();
        $stmt_username->bind_result($count_username);
        $stmt_username->fetch();
        $stmt_username->close();

        if ($count_username > 0) {
            header("Location: ../registro.php?error=username");
            exit();
        }

        // Verificar si el correo electrónico ya existe
        $check_email = "SELECT COUNT(*) FROM usuario WHERE correo_electronico = ?";
        $stmt_email = $conn->prepare($check_email);
        $stmt_email->bind_param("s", $correo_electronico);
        $stmt_email->execute();
        $stmt_email->bind_result($count_email);
        $stmt_email->fetch();
        $stmt_email->close();

        if ($count_email > 0) {
            header("Location: ../registro.php?error=email");
            exit();
        }

        // Hashear la contraseña
        $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);

        // Insertar el nuevo usuario
        $insert_user = "INSERT INTO usuario (nombre_usuario, correo_electronico, contrasena, rol_codigo, created_at, updated_at) VALUES (?, ?, ?, 2, NOW(), NOW())";
        $stmt_insert = $conn->prepare($insert_user);
        $stmt_insert->bind_param("sss", $nombre_usuario, $correo_electronico, $hashed_password);

        if ($stmt_insert->execute()) {
            // Registro exitoso
            header("Location: ../login.php?success=1");
            exit();
        } else {
            // Error al insertar
            error_log("Error al insertar usuario: " . $stmt_insert->error);
            header("Location: ../registro.php?error=db");
            exit();
        }
    } catch (Exception $e) {
        // Error en la base de datos
        error_log("Error en registro_process.php: " . $e->getMessage());
        header("Location: ../registro.php?error=db");
        exit();
    }
}

// Si se llega aquí, redirigir a la página de registro
header("Location: ../registro.php");
exit();
?>
