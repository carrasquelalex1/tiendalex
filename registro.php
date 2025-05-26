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

// Verificar si hay mensajes
$mensaje = '';
$tipo_mensaje = '';

if (isset($_GET['success'])) {
    $mensaje = 'Usuario registrado correctamente. Ahora puedes iniciar sesión.';
    $tipo_mensaje = 'success';
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'username':
            $mensaje = 'El nombre de usuario ya está en uso. Por favor, elige otro.';
            break;
        case 'email':
            $mensaje = 'El correo electrónico ya está registrado.';
            break;
        case 'password':
            $mensaje = 'La contraseña debe tener al menos 6 caracteres.';
            break;
        case 'password_match':
            $mensaje = 'Las contraseñas no coinciden. Por favor, verifica.';
            break;
        case 'empty':
            $mensaje = 'Todos los campos son obligatorios.';
            break;
        case 'email_format':
            $mensaje = 'El formato del correo electrónico no es válido.';
            break;
        case 'db':
            $mensaje = 'Error en la base de datos. Por favor, inténtalo más tarde.';
            break;
        default:
            $mensaje = 'Ocurrió un error durante el registro. Por favor, inténtalo de nuevo.';
    }
    $tipo_mensaje = 'error';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - Alexander Carrasquel</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <style>
        .registro-card {
            padding: 20px;
            border-radius: 8px;
            margin-top: 2rem;
        }

        .mensaje {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }

        .mensaje i {
            margin-right: 8px;
        }

        .mensaje.success {
            background-color: #c8e6c9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .mensaje.error {
            background-color: #ffcdd2;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
    </style>
</head>

<body>
    <?php include 'frontend/includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="row">
                <div class="col s12 m8 offset-m2">
                    <div class="card registro-card ilike-blue-container">
                        <div class="card-content">
                            <h2 class="card-title center-align">Registro de Usuario</h2>

                            <?php if (!empty($mensaje)): ?>
                                <div class="mensaje <?php echo $tipo_mensaje; ?>">
                                    <i class="material-icons tiny"><?php echo $tipo_mensaje === 'success' ? 'check_circle' : 'error'; ?></i>
                                    <?php echo $mensaje; ?>
                                </div>
                            <?php endif; ?>

                            <form action="backend/registro_process.php" method="POST" id="registro-form">
                                <div class="row">
                                    <div class="input-field col s12">
                                        <i class="material-icons prefix">account_circle</i>
                                        <input id="nombre_usuario" type="text" name="nombre_usuario" class="validate" required>
                                        <label for="nombre_usuario">Nombre de Usuario</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-field col s12">
                                        <i class="material-icons prefix">email</i>
                                        <input id="correo_electronico" type="email" name="correo_electronico" class="validate" required>
                                        <label for="correo_electronico">Correo Electrónico</label>
                                        <span class="helper-text" data-error="Correo inválido" data-success="Correcto"></span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="input-field col s12 m6">
                                        <i class="material-icons prefix">lock</i>
                                        <input id="contrasena" type="password" name="contrasena" class="validate" required minlength="6">
                                        <label for="contrasena">Contraseña</label>
                                        <span class="helper-text">Mínimo 6 caracteres</span>
                                    </div>
                                    <div class="input-field col s12 m6">
                                        <i class="material-icons prefix">lock_outline</i>
                                        <input id="confirmar_contrasena" type="password" name="confirmar_contrasena" class="validate" required>
                                        <label for="confirmar_contrasena">Confirmar Contraseña</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col s12 center-align">
                                        <button type="submit" class="waves-effect waves-light btn blue darken-2">
                                            <i class="material-icons left">person_add</i>
                                            Registrarme
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div class="divider" style="margin: 20px 0;"></div>

                            <div class="center-align">
                                <p>O regístrate con:</p>
                                <a href="backend/google_login.php" class="waves-effect waves-light btn red">
                                    <i class="material-icons left">login</i>
                                    Google
                                </a>
                            </div>

                            <div class="center-align" style="margin-top: 20px;">
                                <p>¿Ya tienes una cuenta? <a href="login.php" class="blue-text text-darken-2">Inicia sesión aquí</a></p>
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

            // Validación de contraseñas
            const form = document.getElementById('registro-form');
            const password = document.getElementById('contrasena');
            const confirmPassword = document.getElementById('confirmar_contrasena');

            form.addEventListener('submit', function(event) {
                if (password.value !== confirmPassword.value) {
                    event.preventDefault();
                    M.toast({html: 'Las contraseñas no coinciden', classes: 'red'});
                    confirmPassword.setCustomValidity('Las contraseñas no coinciden');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            });

            confirmPassword.addEventListener('input', function() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Las contraseñas no coinciden');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            });
        });
    </script>
</body>
</html>
