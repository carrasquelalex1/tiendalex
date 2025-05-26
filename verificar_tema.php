<?php
require_once __DIR__ . '/autoload.php';
/**
 * Página para verificar la configuración del tema
 * Esta página muestra la configuración actual del tema y permite probar los colores
 */

// Incluir archivos necesarios
require_once 'backend/config/db.php';
require_once 'backend/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Obtener la configuración actual del tema
$configuracion_tema = [];
$sql = "SELECT * FROM configuracion_tema";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $configuracion_tema[$row['nombre_configuracion']] = $row['valor_configuracion'];
    }
}

// Título de la página
$titulo_pagina = "Verificación del Tema";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - Alexander Carrasquel</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .color-box {
            width: 50px;
            height: 50px;
            display: inline-block;
            margin-right: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }
        .color-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
            background-color: #f5f5f5;
        }
        .color-details {
            flex: 1;
        }
        .element-preview {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .section-title {
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
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
                    <p class="flow-text">Esta página muestra la configuración actual del tema y permite verificar que los colores se apliquen correctamente.</p>

                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Configuración Actual</span>

                            <div class="row">
                                <div class="col s12 m6">
                                    <h5 class="section-title">Colores Configurados</h5>

                                    <div class="color-info">
                                        <div class="color-box" style="background-color: <?php echo $configuracion_tema['primary_color'] ?? '#2c3e50'; ?>"></div>
                                        <div class="color-details">
                                            <strong>Color Principal:</strong> <?php echo $configuracion_tema['primary_color'] ?? '#2c3e50'; ?>
                                        </div>
                                    </div>

                                    <div class="color-info">
                                        <div class="color-box" style="background-color: <?php echo $configuracion_tema['secondary_color'] ?? '#34495e'; ?>"></div>
                                        <div class="color-details">
                                            <strong>Color Secundario:</strong> <?php echo $configuracion_tema['secondary_color'] ?? '#34495e'; ?>
                                        </div>
                                    </div>

                                    <div class="color-info">
                                        <div class="color-box" style="background-color: <?php echo $configuracion_tema['accent_color'] ?? '#2980b9'; ?>"></div>
                                        <div class="color-details">
                                            <strong>Color de Acento:</strong> <?php echo $configuracion_tema['accent_color'] ?? '#2980b9'; ?>
                                        </div>
                                    </div>

                                    <div class="color-info">
                                        <div class="color-box" style="background-color: <?php echo $configuracion_tema['success_color'] ?? '#27ae60'; ?>"></div>
                                        <div class="color-details">
                                            <strong>Color de Éxito:</strong> <?php echo $configuracion_tema['success_color'] ?? '#27ae60'; ?>
                                        </div>
                                    </div>

                                    <div class="color-info">
                                        <div class="color-box" style="background-color: <?php echo $configuracion_tema['danger_color'] ?? '#c0392b'; ?>"></div>
                                        <div class="color-details">
                                            <strong>Color de Peligro:</strong> <?php echo $configuracion_tema['danger_color'] ?? '#c0392b'; ?>
                                        </div>
                                    </div>

                                    <div class="color-info">
                                        <div class="color-box" style="background-color: <?php echo $configuracion_tema['warning_color'] ?? '#f39c12'; ?>"></div>
                                        <div class="color-details">
                                            <strong>Color de Advertencia:</strong> <?php echo $configuracion_tema['warning_color'] ?? '#f39c12'; ?>
                                        </div>
                                    </div>

                                    <div class="color-info">
                                        <div class="color-box" style="background-color: <?php echo $configuracion_tema['availability_color'] ?? '#4CAF50'; ?>"></div>
                                        <div class="color-details">
                                            <strong>Color de Disponibilidad:</strong> <?php echo $configuracion_tema['availability_color'] ?? '#4CAF50'; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col s12 m6">
                                    <h5 class="section-title">Elementos de Muestra</h5>

                                    <div class="element-preview">
                                        <h6>Botones</h6>
                                        <button class="btn waves-effect waves-light">
                                            <i class="material-icons left">check</i>Principal
                                        </button>

                                        <button class="btn waves-effect waves-light blue">
                                            <i class="material-icons left">star</i>Acento
                                        </button>

                                        <button class="btn waves-effect waves-light green">
                                            <i class="material-icons left">check_circle</i>Éxito
                                        </button>

                                        <button class="btn waves-effect waves-light red">
                                            <i class="material-icons left">error</i>Peligro
                                        </button>

                                        <button class="btn waves-effect waves-light orange">
                                            <i class="material-icons left">warning</i>Advertencia
                                        </button>
                                    </div>

                                    <div class="element-preview">
                                        <h6>Botones Flotantes</h6>
                                        <div style="position: relative; height: 70px; border: 1px dashed #ddd; border-radius: 8px; padding: 10px;">
                                            <div style="position: absolute; right: 20px; bottom: 10px;">
                                                <a class="btn-floating">
                                                    <i class="material-icons">add</i>
                                                </a>
                                                <a class="btn-floating blue" style="margin-left: 10px;">
                                                    <i class="material-icons">shopping_cart</i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="element-preview">
                                        <h6>Badges</h6>
                                        <span class="badge new blue" data-badge-caption="">5</span>
                                        <span class="badge new green" data-badge-caption="">3</span>
                                        <span class="badge new red" data-badge-caption="">1</span>
                                        <span class="badge new orange" data-badge-caption="">2</span>
                                    </div>

                                    <div class="element-preview">
                                        <h6>Botón de Disponibilidad</h6>
                                        <div class="stock-container">
                                            <button class="btn waves-effect waves-light availability-btn" id="preview-availability">
                                                <i class="material-icons left">inventory_2</i>DISPONIBLE (18)
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col s12">
                                    <h5 class="section-title">Información Técnica</h5>

                                    <p>
                                        <strong>Archivo CSS dinámico:</strong>
                                        <a href="/tiendalex2/css/dynamic_theme.php" target="_blank">/tiendalex2/css/dynamic_theme.php</a>
                                    </p>

                                    <p>
                                        <strong>Página de administración:</strong>
                                        <a href="/tiendalex2/admin_tema.php">Configuración del Tema</a>
                                        <?php if ($es_admin): ?>
                                            <span class="green-text text-darken-2">(Tienes acceso)</span>
                                        <?php else: ?>
                                            <span class="red-text text-darken-2">(Solo administradores)</span>
                                        <?php endif; ?>
                                    </p>

                                    <p>
                                        <strong>Herramientas adicionales:</strong>
                                        <a href="/tiendalex2/forzar_actualizacion_tema.php">Forzar Actualización del Tema</a>
                                        <?php if ($es_admin): ?>
                                            <span class="green-text text-darken-2">(Tienes acceso)</span>
                                        <?php else: ?>
                                            <span class="red-text text-darken-2">(Solo administradores)</span>
                                        <?php endif; ?>
                                    </p>

                                    <div class="divider" style="margin: 20px 0;"></div>

                                    <p>
                                        <strong>Nota:</strong> Si los colores no se están aplicando correctamente, puedes forzar la actualización del tema.
                                    </p>

                                    <div class="center-align" style="margin-top: 20px;">
                                        <button id="forzar-actualizacion" class="btn waves-effect waves-light">
                                            <i class="material-icons left">refresh</i>Forzar Actualización del Tema
                                        </button>
                                    </div>

                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            // Botón para forzar la actualización del tema
                                            document.getElementById('forzar-actualizacion').addEventListener('click', function() {
                                                // Mostrar indicador de carga
                                                this.innerHTML = '<i class="material-icons left">hourglass_empty</i>Actualizando...';
                                                this.disabled = true;

                                                // Llamar al script de actualización
                                                fetch('/tiendalex2/backend/actualizar_version_tema.php?' + new Date().getTime())
                                                    .then(response => response.json())
                                                    .then(data => {
                                                        console.log('Respuesta del servidor:', data);

                                                        // Forzar la recarga del CSS dinámico
                                                        const linkElement = document.querySelector('link[href*="dynamic_theme.php"]');
                                                        if (linkElement) {
                                                            const href = linkElement.getAttribute('href').split('?')[0];
                                                            linkElement.setAttribute('href', href + '?v=' + new Date().getTime());
                                                        }

                                                        // Notificar a otras pestañas
                                                        localStorage.setItem('theme_updated', new Date().getTime());

                                                        // Mostrar mensaje de éxito
                                                        M.toast({
                                                            html: '<i class="material-icons left">check_circle</i> Tema actualizado correctamente',
                                                            classes: 'rounded green',
                                                            displayLength: 3000
                                                        });

                                                        // Restaurar el botón
                                                        setTimeout(() => {
                                                            this.innerHTML = '<i class="material-icons left">refresh</i>Forzar Actualización del Tema';
                                                            this.disabled = false;
                                                        }, 1000);
                                                    })
                                                    .catch(error => {
                                                        console.error('Error al actualizar el tema:', error);

                                                        // Mostrar mensaje de error
                                                        M.toast({
                                                            html: '<i class="material-icons left">error</i> Error al actualizar el tema',
                                                            classes: 'rounded red',
                                                            displayLength: 3000
                                                        });

                                                        // Restaurar el botón
                                                        this.innerHTML = '<i class="material-icons left">refresh</i>Forzar Actualización del Tema';
                                                        this.disabled = false;
                                                    });
                                            });
                                        });
                                    </script>
                                </div>
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
            // Obtener el color de disponibilidad desde las variables CSS
            const computedStyle = getComputedStyle(document.documentElement);
            const availabilityColor = computedStyle.getPropertyValue('--availability-color').trim();

            // Aplicar el color al botón de disponibilidad
            if (availabilityColor) {
                document.getElementById('preview-availability').style.backgroundColor = availabilityColor;
            }

            // Escuchar cambios en el tema
            window.addEventListener('storage', function(e) {
                if (e.key === 'theme_updated') {
                    // Recargar la página para mostrar los cambios
                    location.reload();
                }
            });
        });
    </script>
</body>
</html>
