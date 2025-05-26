<?php
require_once __DIR__ . '/autoload.php';

// Incluir el helper de sesiones
require_once __DIR__ . '/helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario ya está logueado
if (esta_logueado()) {
    // Redirigir según el rol
    if (es_admin()) {
        header("Location: catalogo.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

// Verificar si hay un mensaje de error o éxito
$error_message = '';
$success_message = '';

if (isset($_GET['error'])) {
    $error_message = 'Credenciales incorrectas. Por favor, inténtalo de nuevo.';
}

if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        $success_message = '¡Registro exitoso! Ahora puedes iniciar sesión con tus credenciales.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión - Alexander Carrasquel</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <style>
        .login-card {
            padding: 20px;
            border-radius: 8px;
            margin-top: 2rem;
        }

        .error-message {
            color: #f44336;
            margin-bottom: 15px;
            text-align: center;
        }

        .success-message {
            color: #4CAF50;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <?php include 'frontend/includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="row">
                <div class="col s12 m6 offset-m3">
                    <div class="card login-card ilike-blue-container">
                        <div class="card-content">
                            <h2 class="card-title center-align">Inicio de Sesión</h2>

                            <?php if (!empty($success_message)): ?>
                                <div class="success-message">
                                    <i class="material-icons tiny">check_circle</i>
                                    <?php echo $success_message; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($error_message)): ?>
                                <div class="error-message">
                                    <i class="material-icons tiny">error</i>
                                    <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>

                            <form action="backend/login_process.php" method="POST">
                                <div class="input-field">
                                    <i class="material-icons prefix">account_circle</i>
                                    <input id="nombre_usuario" type="text" name="nombre_usuario" class="validate" required>
                                    <label for="nombre_usuario">Nombre de Usuario</label>
                                </div>
                                <div class="input-field">
                                    <i class="material-icons prefix">lock</i>
                                    <input id="contrasena" type="password" name="contrasena" class="validate" required>
                                    <label for="contrasena">Contraseña</label>
                                </div>
                                <div class="center-align">
                                    <button type="submit" class="waves-effect waves-light btn blue darken-2">
                                        <i class="material-icons left">login</i>
                                        Ingresar
                                    </button>
                                </div>
                            </form>

                            <div class="divider" style="margin: 20px 0;"></div>

                            <div class="center-align">
                                <p>O inicia sesión con:</p>
                                <a href="backend/google_login.php" class="waves-effect waves-light btn red">
                                    <i class="material-icons left">login</i>
                                    Google
                                </a>
                            </div>

                            <div class="center-align" style="margin-top: 20px;">
                                <p>¿No tienes una cuenta? <a href="registro.php" class="blue-text text-darken-2">Regístrate aquí</a></p>
                                <a href="index.php" class="grey-text">Volver a la página de inicio</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'frontend/includes/footer.php'; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Materialize JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <!-- JavaScript personalizado -->
    <script src="js/scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar componentes de Materialize
            M.AutoInit();
        });
    </script>
</body>
</html>
