<?php
/**
 * Página para forzar la actualización del tema en todas las páginas
 * Esta página permite al administrador forzar la actualización del tema en todas las páginas
 */

// Incluir archivos necesarios
require_once 'backend/config/db.php';
require_once 'backend/session_helper.php';
require_once __DIR__ . '/autoload.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario es administrador
$es_admin = isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == 1;

// Mensaje para mostrar al usuario
$mensaje = '';
$tipo_mensaje = '';

// Si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forzar_actualizacion'])) {
    // Verificar si el usuario es administrador
    if ($es_admin) {
        // Incluir el script de actualización de versión del tema
        include_once 'backend/actualizar_version_tema.php';

        // Mostrar mensaje de éxito
        $mensaje = "Se ha forzado la actualización del tema en todas las páginas.";
        $tipo_mensaje = "success";
    } else {
        // Mostrar mensaje de error
        $mensaje = "No tienes permisos para realizar esta acción.";
        $tipo_mensaje = "error";
    }
}

// Título de la página
$titulo_pagina = "Forzar Actualización del Tema";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - Alexander Carrasquel</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <style>
        .action-card {
            margin-top: 30px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .action-card .card-title {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .action-card .card-title i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        .action-button {
            margin-top: 20px;
        }
        .info-section {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            background-color: #f5f5f5;
        }
        .info-section h5 {
            margin-top: 0;
            display: flex;
            align-items: center;
        }
        .info-section h5 i {
            margin-right: 10px;
            color: var(--accent-color);
        }
        .info-section ul {
            margin-left: 20px;
        }
        .info-section li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'frontend/includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="row">
                <div class="col s12">
                    <h1><?php echo $titulo_pagina; ?></h1>

                    <?php if (!empty($mensaje)): ?>
                    <div class="card-panel <?php echo $tipo_mensaje === 'success' ? 'green lighten-4' : 'red lighten-4'; ?>">
                        <span class="<?php echo $tipo_mensaje === 'success' ? 'green-text text-darken-2' : 'red-text text-darken-2'; ?>">
                            <i class="material-icons left"><?php echo $tipo_mensaje === 'success' ? 'check_circle' : 'error'; ?></i>
                            <?php echo $mensaje; ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <div class="card action-card">
                        <div class="card-content">
                            <span class="card-title">
                                <i class="material-icons">refresh</i>
                                Actualización del Tema
                            </span>

                            <p>Esta página permite forzar la actualización del tema en todas las páginas del sitio web.</p>

                            <div class="info-section">
                                <h5><i class="material-icons">info</i>Información</h5>
                                <p>Utiliza esta función cuando:</p>
                                <ul>
                                    <li>Los cambios en el tema no se están aplicando correctamente en todas las páginas.</li>
                                    <li>Quieres asegurarte de que todos los usuarios vean la última versión del tema.</li>
                                    <li>Has realizado cambios manuales en los archivos CSS y quieres que se apliquen inmediatamente.</li>
                                </ul>
                            </div>

                            <?php if ($es_admin): ?>
                            <form method="POST" action="">
                                <div class="center-align action-button">
                                    <button type="submit" name="forzar_actualizacion" class="btn waves-effect waves-light">
                                        <i class="material-icons left">refresh</i>Forzar Actualización del Tema
                                    </button>
                                </div>
                            </form>
                            <?php else: ?>
                            <div class="card-panel red lighten-4">
                                <span class="red-text text-darken-2">
                                    <i class="material-icons left">error</i>
                                    Solo los administradores pueden forzar la actualización del tema.
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card action-card">
                        <div class="card-content">
                            <span class="card-title">
                                <i class="material-icons">build</i>
                                Herramientas Adicionales
                            </span>

                            <div class="collection">
                                <a href="verificar_tema.php" class="collection-item">
                                    <i class="material-icons left">visibility</i>
                                    Verificar Tema
                                    <span class="badge">Comprobar la configuración actual del tema</span>
                                </a>

                                <a href="admin_tema.php" class="collection-item">
                                    <i class="material-icons left">palette</i>
                                    Configuración del Tema
                                    <span class="badge">Personalizar los colores del sitio</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'frontend/includes/footer.php'; ?>

    <?php include 'frontend/includes/js_includes.php'; ?>
    <!-- Script de diagnóstico del tema -->
    <script src="/tiendalex2/js/theme_diagnostics.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar componentes de Materialize
            M.AutoInit();

            // Hacer que el mensaje desaparezca después de 5 segundos
            setTimeout(function() {
                var mensaje = document.querySelector('.card-panel');
                if (mensaje) {
                    mensaje.style.transition = 'opacity 1s';
                    mensaje.style.opacity = '0';
                    setTimeout(function() {
                        mensaje.style.display = 'none';
                    }, 1000);
                }
            }, 5000);
        });
    </script>
</body>
</html>
