<?php
require_once __DIR__ . '/autoload.php';
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_logueado'])) {
    header("Location: login.php");
    exit();
}

// Incluir la conexión a la base de datos
require_once 'backend/config/db.php';

// Obtener información del usuario
$usuario_id = $_SESSION['usuario_logueado'];
$usuario = null;

try {
    $sql = "SELECT * FROM usuario WHERE codigo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
    } else {
        // Si no se encuentra el usuario, redirigir al login
        session_destroy();
        header("Location: login.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Error en perfil.php: " . $e->getMessage());
    $error_message = "Ocurrió un error al cargar la información del perfil.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <!-- CSS de Materialize -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <!-- Íconos de materiales -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- CSS personalizado -->
    <?php include 'frontend/includes/css_includes.php'; ?>
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        main {
            flex: 2 0 auto;
            padding-top: 2rem;
        }

        .profile-card {
            padding: 20px;
            border-radius: 8px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
        }

        .profile-avatar i {
            font-size: 48px;
            color: #9e9e9e;
        }

        .profile-info {
            flex: 1;
        }

        .profile-info h4 {
            margin: 0;
            margin-bottom: 5px;
        }

        .profile-info p {
            margin: 0;
            color: #757575;
        }

        .profile-section {
            margin-top: 30px;
        }

        .profile-section h5 {
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-label {
            color: #757575;
            font-weight: bold;
        }

        .info-value {
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <?php include 'frontend/includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="row">
                <div class="col s12">
                    <div class="card profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="material-icons">person</i>
                            </div>
                            <div class="profile-info">
                                <h4><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></h4>
                                <p><?php echo $usuario['rol_codigo'] == 1 ? 'Administrador' : 'Cliente'; ?></p>
                            </div>
                        </div>

                        <div class="profile-section">
                            <h5>Información Personal</h5>
                            <div class="row">
                                <div class="col s12 m6">
                                    <div class="info-item">
                                        <div class="info-label">Nombre de Usuario</div>
                                        <div class="info-value"><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></div>
                                    </div>
                                </div>
                                <div class="col s12 m6">
                                    <div class="info-item">
                                        <div class="info-label">Correo Electrónico</div>
                                        <div class="info-value"><?php echo htmlspecialchars($usuario['correo_electronico']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="profile-section">
                            <h5>Acciones</h5>
                            <div class="row">
                                <div class="col s12">
                                    <a href="#" class="btn waves-effect waves-light blue">
                                        <i class="material-icons left">edit</i>
                                        Editar Perfil
                                    </a>
                                    <a href="#modal-cambiar-contrasena" class="btn waves-effect waves-light green modal-trigger">
                                        <i class="material-icons left">lock</i>
                                        Cambiar Contraseña
                                    </a>
                                    <a href="logout.php" class="btn waves-effect waves-light red">
                                        <i class="material-icons left">exit_to_app</i>
                                        Cerrar Sesión
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para cambiar contraseña -->
        <div id="modal-cambiar-contrasena" class="modal">
            <div class="modal-content">
                <h4>Cambiar Contraseña</h4>
                <div class="divider"></div>
                <form action="backend/cambiar_contrasena.php" method="POST">
                    <div class="input-field">
                        <i class="material-icons prefix">lock_outline</i>
                        <input id="contrasena_actual" type="password" name="contrasena_actual" class="validate" required>
                        <label for="contrasena_actual">Contraseña Actual</label>
                    </div>
                    <div class="input-field">
                        <i class="material-icons prefix">lock</i>
                        <input id="nueva_contrasena" type="password" name="nueva_contrasena" class="validate" required minlength="6">
                        <label for="nueva_contrasena">Nueva Contraseña</label>
                        <span class="helper-text">Mínimo 6 caracteres</span>
                    </div>
                    <div class="input-field">
                        <i class="material-icons prefix">lock</i>
                        <input id="confirmar_contrasena" type="password" name="confirmar_contrasena" class="validate" required>
                        <label for="confirmar_contrasena">Confirmar Nueva Contraseña</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
                <button type="submit" class="waves-effect waves-green btn-flat">Cambiar</button>
            </div>
        </div>
    </main>

    <?php include 'frontend/includes/footer.php'; ?>

    <!-- Materialize JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar componentes de Materialize
            M.AutoInit();
        });
    </script>
</body>
</html>
