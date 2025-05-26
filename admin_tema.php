<?php
/**
 * Página para configurar el tema del sitio
 * Permite al administrador cambiar los colores del sitio
 */

// Incluir archivos necesarios
require_once 'backend/config/db.php';
require_once('helpers/session/session_helper.php');

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado y es administrador
if (!esta_logueado() || !es_admin()) {
    header("Location: login.php");
    exit;
}

// Obtener la configuración actual del tema
$configuracion_tema = [];
$sql = "SELECT * FROM configuracion_tema";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $configuracion_tema[$row['nombre_configuracion']] = $row['valor_configuracion'];
    }
}

// Procesar el formulario si se envía
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_tema'])) {
    // Validar y procesar los colores usando métodos modernos de sanitización
    $colores = [
        'primary_color' => htmlspecialchars(trim($_POST['primary_color'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'secondary_color' => htmlspecialchars(trim($_POST['secondary_color'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'accent_color' => htmlspecialchars(trim($_POST['accent_color'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'success_color' => htmlspecialchars(trim($_POST['success_color'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'danger_color' => htmlspecialchars(trim($_POST['danger_color'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'warning_color' => htmlspecialchars(trim($_POST['warning_color'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'availability_color' => htmlspecialchars(trim($_POST['availability_color'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'body_bg_color' => htmlspecialchars(trim($_POST['body_bg_color'] ?? ''), ENT_QUOTES, 'UTF-8')
    ];

    // --- NUEVO: Verificar si existe body_bg_color, si no, insertarlo ---
    $checkBodyBg = $conn->prepare("SELECT COUNT(*) as count FROM configuracion_tema WHERE nombre_configuracion = 'body_bg_color'");
    $checkBodyBg->execute();
    $resultCheck = $checkBodyBg->get_result();
    $rowCheck = $resultCheck->fetch_assoc();
    if ($rowCheck['count'] == 0) {
        $defaultBg = $colores['body_bg_color'] ?: '#f5f6fa';
        $insertBodyBg = $conn->prepare("INSERT INTO configuracion_tema (nombre_configuracion, valor_configuracion, fecha_actualizacion) VALUES ('body_bg_color', ?, NOW())");
        $insertBodyBg->bind_param("s", $defaultBg);
        $insertBodyBg->execute();
    }
    // --- FIN NUEVO ---

    // Validar que los colores tengan el formato correcto (hexadecimal)
    $color_valido = true;
    foreach ($colores as $nombre => $valor) {
        if (!empty($valor) && !preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $valor)) {
            $color_valido = false;
            $mensaje = "El color $nombre no tiene un formato hexadecimal válido.";
            $tipo_mensaje = "error";
            break;
        }
    }

    // Actualizar cada color en la base de datos si todos son válidos
    $exito = true;
    if ($color_valido) {
        $stmt = $conn->prepare("UPDATE configuracion_tema SET valor_configuracion = ? WHERE nombre_configuracion = ?");

        foreach ($colores as $nombre => $valor) {
            if (!empty($valor)) {
                $stmt->bind_param("ss", $valor, $nombre);
                if (!$stmt->execute()) {
                    $exito = false;
                    $mensaje = "Error al guardar el color $nombre: " . $conn->error;
                    $tipo_mensaje = "error";
                    break;
                }
                // Actualizar el array de configuración
                $configuracion_tema[$nombre] = $valor;
            }
        }

        if ($exito) {
            // Definir constante para evitar que el script envíe encabezados
            define('INCLUIDO_DESDE_ADMIN', true);

            // Utilizar el script de actualización de versión del tema
            require_once __DIR__ . '/backend/actualizar_version_tema.php';

            $mensaje = "La configuración del tema se ha guardado correctamente.";
            $tipo_mensaje = "success";
        }
    }
}

// Título de la página
$titulo_pagina = "Configuración del Tema";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - Alexander Carrasquel</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Incluir Spectrum Color Picker -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.1/spectrum.min.css">
    <!-- Estilos adicionales para Spectrum -->
    <style>
        .sp-container {
            z-index: 9999 !important;
        }
        .sp-replacer {
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            padding: 5px !important;
            cursor: pointer !important;
        }
        .sp-replacer:hover {
            border-color: #aaa !important;
        }
        .sp-preview {
            width: 25px !important;
            height: 25px !important;
            border-radius: 3px !important;
        }
        .sp-dd {
            display: none !important;
        }
    </style>
    <style>
        body { background: var(--body-bg-color) !important; }
        .color-preview {
            width: 30px;
            height: 30px;
            display: inline-block;
            border-radius: 50%;
            margin-right: 10px;
            vertical-align: middle;
            border: 1px solid #ddd;
        }
        .color-container {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .color-container:hover {
            background-color: #f5f5f5;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .color-label {
            flex: 1;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        .color-label i {
            margin-right: 8px;
        }
        .color-input {
            flex: 2;
        }
        .theme-preview {
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .theme-preview:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transform: translateY(-2px);
        }
        .preview-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
            display: flex;
            align-items: center;
        }
        .preview-header i {
            margin-right: 10px;
        }
        .preview-content {
            padding: 20px;
            background-color: white;
        }
        .preview-footer {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            border-radius: 0 0 8px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .preview-button {
            margin: 5px;
            transition: transform 0.2s ease;
        }
        .preview-button:hover {
            transform: scale(1.05);
        }
        .sp-replacer {
            border-radius: 4px;
            border: 1px solid #ddd;
            padding: 8px;
            transition: all 0.3s ease;
        }
        .sp-replacer:hover {
            border-color: #aaa;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .sp-preview {
            width: 30px;
            height: 30px;
            border-radius: 4px;
        }
        .card {
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 8px 17px 2px rgba(0,0,0,0.14),
                        0 3px 14px 2px rgba(0,0,0,0.12),
                        0 5px 5px -3px rgba(0,0,0,0.2);
        }
        .card .card-content {
            padding: 24px;
        }
        .card .card-title {
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .card .card-title i {
            margin-right: 10px;
        }
        .btn {
            border-radius: 30px;
            text-transform: none;
            font-weight: 500;
            padding: 0 25px;
            height: 42px;
            line-height: 42px;
            box-shadow: 0 3px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        .btn:hover {
            box-shadow: 0 5px 11px rgba(0,0,0,0.3);
            transform: translateY(-2px);
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
                    <p class="flow-text">Personaliza los colores del sitio web.</p>

                    <?php if (!empty($mensaje)): ?>
                    <div class="card-panel <?php echo $tipo_mensaje === 'success' ? 'green lighten-4' : 'red lighten-4'; ?>">
                        <span class="<?php echo $tipo_mensaje === 'success' ? 'green-text text-darken-2' : 'red-text text-darken-2'; ?>">
                            <i class="material-icons left"><?php echo $tipo_mensaje === 'success' ? 'check_circle' : 'error'; ?></i>
                            <?php echo $mensaje; ?>
                            <?php if ($tipo_mensaje === 'success'): ?>
                            <span id="reload-message" style="display: none;"> Actualizando tema en todas las páginas...</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <script>
                        // Hacer que el mensaje desaparezca después de 8 segundos
                        setTimeout(function() {
                            var mensaje = document.querySelector('.card-panel');
                            if (mensaje) {
                                mensaje.style.transition = 'opacity 1s';
                                mensaje.style.opacity = '0';
                                setTimeout(function() {
                                    mensaje.style.display = 'none';
                                }, 1000);
                            }
                        }, 8000);

                        <?php if ($tipo_mensaje === 'success'): ?>
                        // Mostrar mensaje de actualización
                        document.getElementById('reload-message').style.display = 'inline';

                        // Forzar la recarga del tema en todas las páginas abiertas
                        // Esto se hace enviando un mensaje a través de localStorage
                        localStorage.setItem('theme_updated', '<?php echo time(); ?>');

                        // Disparar evento personalizado para la misma pestaña
                        window.dispatchEvent(new CustomEvent('themeUpdated', {
                            detail: { timestamp: '<?php echo time(); ?>' }
                        }));

                        // Actualizar la vista previa del color de disponibilidad
                        const availabilityColor = document.getElementById('availability_color').value;
                        if (availabilityColor) {
                            // Actualizar la vista previa inmediatamente
                            const previewBtn = document.getElementById('preview-availability');
                            if (previewBtn) {
                                previewBtn.style.backgroundColor = availabilityColor;
                            }
                        }

                        // Recargar el tema en esta página también
                        setTimeout(function() {
                            // Recargar el CSS dinámico
                            var linkElement = document.querySelector('link[href*="dynamic_theme.php"]');
                            if (linkElement) {
                                var href = linkElement.getAttribute('href').split('?')[0];
                                linkElement.setAttribute('href', href + '?v=' + new Date().getTime());
                            }

                            // Actualizar la vista previa
                            updatePreview();

                            // Actualizar directamente la variable CSS
                            const availabilityColor = document.getElementById('availability_color').value;
                            if (availabilityColor) {
                                document.documentElement.style.setProperty('--availability-color', availabilityColor);
                                console.log('Actualizando variable CSS de disponibilidad:', availabilityColor);
                            }

                            // Mostrar mensaje de éxito
                            M.toast({
                                html: '<i class="material-icons left">check_circle</i> Tema actualizado correctamente',
                                classes: 'rounded green',
                                displayLength: 3000
                            });
                        }, 1000);
                        <?php endif; ?>
                    </script>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col s12 m6">
                            <div class="card">
                                <div class="card-content">
                                    <span class="card-title">Selección de Colores</span>

                                    <form method="POST" action="">
                                        <div class="color-container">
                                            <div class="color-label">
                                                <i class="material-icons">format_color_fill</i>
                                                Color Principal:
                                                <span class="tooltipped" data-position="top" data-tooltip="Color del encabezado, pie de página y elementos principales">
                                                    <i class="material-icons tiny">info_outline</i>
                                                </span>
                                            </div>
                                            <div class="color-input">
                                                <input type="text" name="primary_color" id="primary_color" class="color-picker" value="<?php echo $configuracion_tema['primary_color'] ?? '#2c3e50'; ?>">
                                            </div>
                                        </div>

                                        <div class="color-container">
                                            <div class="color-label">
                                                <i class="material-icons">tonality</i>
                                                Color Secundario:
                                                <span class="tooltipped" data-position="top" data-tooltip="Color complementario para elementos secundarios">
                                                    <i class="material-icons tiny">info_outline</i>
                                                </span>
                                            </div>
                                            <div class="color-input">
                                                <input type="text" name="secondary_color" id="secondary_color" class="color-picker" value="<?php echo $configuracion_tema['secondary_color'] ?? '#34495e'; ?>">
                                            </div>
                                        </div>

                                        <div class="color-container">
                                            <div class="color-label">
                                                <i class="material-icons">palette</i>
                                                Color de Acento:
                                                <span class="tooltipped" data-position="top" data-tooltip="Color para destacar elementos interactivos como botones y enlaces">
                                                    <i class="material-icons tiny">info_outline</i>
                                                </span>
                                            </div>
                                            <div class="color-input">
                                                <input type="text" name="accent_color" id="accent_color" class="color-picker" value="<?php echo $configuracion_tema['accent_color'] ?? '#2980b9'; ?>">
                                            </div>
                                        </div>

                                        <div class="color-container">
                                            <div class="color-label">
                                                <i class="material-icons">check_circle</i>
                                                Color de Éxito:
                                                <span class="tooltipped" data-position="top" data-tooltip="Color para mensajes y acciones exitosas">
                                                    <i class="material-icons tiny">info_outline</i>
                                                </span>
                                            </div>
                                            <div class="color-input">
                                                <input type="text" name="success_color" id="success_color" class="color-picker" value="<?php echo $configuracion_tema['success_color'] ?? '#27ae60'; ?>">
                                            </div>
                                        </div>

                                        <div class="color-container">
                                            <div class="color-label">
                                                <i class="material-icons">error</i>
                                                Color de Peligro:
                                                <span class="tooltipped" data-position="top" data-tooltip="Color para errores y acciones destructivas">
                                                    <i class="material-icons tiny">info_outline</i>
                                                </span>
                                            </div>
                                            <div class="color-input">
                                                <input type="text" name="danger_color" id="danger_color" class="color-picker" value="<?php echo $configuracion_tema['danger_color'] ?? '#c0392b'; ?>">
                                            </div>
                                        </div>

                                        <div class="color-container">
                                            <div class="color-label">
                                                <i class="material-icons">warning</i>
                                                Color de Advertencia:
                                                <span class="tooltipped" data-position="top" data-tooltip="Color para alertas y advertencias">
                                                    <i class="material-icons tiny">info_outline</i>
                                                </span>
                                            </div>
                                            <div class="color-input">
                                                <input type="text" name="warning_color" id="warning_color" class="color-picker" value="<?php echo $configuracion_tema['warning_color'] ?? '#f39c12'; ?>">
                                            </div>
                                        </div>

                                        <div class="color-container">
                                            <div class="color-label">
                                                <i class="material-icons">inventory_2</i>
                                                Color de Disponibilidad:
                                                <span class="tooltipped" data-position="top" data-tooltip="Color para el botón de disponibilidad de productos">
                                                    <i class="material-icons tiny">info_outline</i>
                                                </span>
                                            </div>
                                            <div class="color-input">
                                                <input type="text" name="availability_color" id="availability_color" class="color-picker" value="<?php echo $configuracion_tema['availability_color'] ?? '#4CAF50'; ?>">
                                            </div>
                                        </div>

                                        <div class="color-container">
                                            <div class="color-label">
                                                <i class="material-icons">format_paint</i>
                                                Color de Fondo del Body:
                                                <span class="tooltipped" data-position="top" data-tooltip="Color de fondo del cuerpo de la página">
                                                    <i class="material-icons tiny">info_outline</i>
                                                </span>
                                            </div>
                                            <div class="color-input">
                                                <input type="text" name="body_bg_color" id="body_bg_color" class="color-picker" value="<?php echo $configuracion_tema['body_bg_color'] ?? '#f5f6fa'; ?>">
                                            </div>
                                        </div>

                                        <div class="divider" style="margin: 30px 0;"></div>

                                        <div class="center-align" style="margin-top: 20px;">
                                            <button type="submit" name="guardar_tema" id="guardar_tema_btn" class="btn waves-effect waves-light">
                                                <i class="material-icons left">save</i>Guardar Configuración
                                            </button>
                                            <button type="button" id="reset-colors" class="btn waves-effect waves-light grey lighten-1" style="margin-left: 10px;">
                                                <i class="material-icons left">refresh</i>Restaurar Predeterminados
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col s12 m6">
                            <div class="card">
                                <div class="card-content">
                                    <span class="card-title">Vista Previa</span>

                                    <div class="theme-preview">
                                        <div class="preview-header" id="preview-header">
                                            <i class="material-icons">web</i>
                                            <h5>Encabezado del sitio</h5>
                                        </div>
                                        <div class="preview-content">
                                            <h5>Vista previa de elementos</h5>
                                            <p>Así se verán los elementos con los colores seleccionados.</p>

                                            <div class="row">
                                                <div class="col s12">
                                                    <h6>Botones</h6>
                                                    <button class="btn waves-effect waves-light preview-button" id="preview-primary">
                                                        <i class="material-icons left">check</i>Principal
                                                    </button>

                                                    <button class="btn waves-effect waves-light preview-button" id="preview-accent">
                                                        <i class="material-icons left">star</i>Acento
                                                    </button>

                                                    <button class="btn waves-effect waves-light green preview-button" id="preview-success">
                                                        <i class="material-icons left">check_circle</i>Éxito
                                                    </button>

                                                    <button class="btn waves-effect waves-light red preview-button" id="preview-danger">
                                                        <i class="material-icons left">error</i>Peligro
                                                    </button>

                                                    <button class="btn waves-effect waves-light orange preview-button" id="preview-warning">
                                                        <i class="material-icons left">warning</i>Advertencia
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="row" style="margin-top: 20px;">
                                                <div class="col s12">
                                                    <h6>Botones flotantes</h6>
                                                    <div style="position: relative; height: 70px; border: 1px dashed #ddd; border-radius: 8px; padding: 10px;">
                                                        <div style="position: absolute; right: 20px; bottom: 10px;">
                                                            <a class="btn-floating" id="preview-float-primary">
                                                                <i class="material-icons">add</i>
                                                            </a>
                                                            <a class="btn-floating" id="preview-float-accent" style="margin-left: 10px;">
                                                                <i class="material-icons">shopping_cart</i>
                                                            </a>
                                                            <a class="btn-floating" id="preview-float-chat" style="margin-left: 10px;">
                                                                <i class="material-icons">chat</i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row" style="margin-top: 20px;">
                                                <div class="col s12">
                                                    <h6>Badges</h6>
                                                    <span class="badge new blue" id="preview-badge-accent" data-badge-caption="">5</span>
                                                    <span class="badge new green" id="preview-badge-success" data-badge-caption="">3</span>
                                                    <span class="badge new red" id="preview-badge-danger" data-badge-caption="">1</span>
                                                    <span class="badge new orange" id="preview-badge-warning" data-badge-caption="">2</span>
                                                </div>
                                            </div>

                                            <div class="row" style="margin-top: 20px;">
                                                <div class="col s12">
                                                    <h6>Disponibilidad de Productos</h6>
                                                    <button class="btn waves-effect waves-light" id="preview-availability">
                                                        <i class="material-icons left">inventory_2</i>DISPONIBLE (18)
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="row" style="margin-top: 20px;">
                                                <div class="col s12">
                                                    <h6>Mensajes</h6>
                                                    <div class="card-panel green lighten-4" id="preview-message-success">
                                                        <span class="green-text text-darken-2">
                                                            <i class="material-icons left">check_circle</i>
                                                            Mensaje de éxito
                                                        </span>
                                                    </div>
                                                    <div class="card-panel red lighten-4" id="preview-message-error">
                                                        <span class="red-text text-darken-2">
                                                            <i class="material-icons left">error</i>
                                                            Mensaje de error
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="preview-footer" id="preview-footer">
                                            <div>
                                                <i class="material-icons">copyright</i>
                                                <span>Pie de página</span>
                                            </div>
                                            <div>
                                                <a href="#!" class="white-text">Enlace 1</a>
                                                <a href="#!" class="white-text" style="margin-left: 10px;">Enlace 2</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
    <!-- Spectrum Color Picker -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.1/spectrum.min.js"></script>
    <!-- JavaScript personalizado -->
    <script src="js/scripts.js"></script>
    <script src="js/availability_fix_optimized.js"></script>
    <script src="js/color_picker_diagnostics.js"></script>
    <?php include 'backend/script/script_enlaces.php'; ?>

    <script>
        $(document).ready(function() {
            console.log('Inicializando página de administración de tema...');

            // Inicializar tooltips
            $('.tooltipped').tooltip();

            // Valores predeterminados
            const defaultColors = {
                primary_color: '#2c3e50',
                secondary_color: '#34495e',
                accent_color: '#2980b9',
                success_color: '#27ae60',
                danger_color: '#c0392b',
                warning_color: '#f39c12',
                availability_color: '#f1c40f'
            };

            // Función para inicializar color pickers
            function initColorPickers() {
                console.log('Inicializando color pickers...');

                // Verificar que Spectrum esté disponible
                if (typeof $.fn.spectrum === 'undefined') {
                    console.error('Spectrum no está disponible');
                    return false;
                }

                // Inicializar cada color picker individualmente
                $('.color-picker').each(function() {
                    const $this = $(this);
                    const id = $this.attr('id');
                    console.log('Inicializando color picker:', id);

                    try {
                        $this.spectrum({
                            color: $this.val(),
                            showInput: true,
                            className: "full-spectrum",
                            showInitial: true,
                            showPalette: true,
                            showSelectionPalette: true,
                            maxSelectionSize: 10,
                            preferredFormat: "hex",
                            localStorageKey: "spectrum.demo",
                            palette: [
                                ["#2c3e50", "#34495e", "#2980b9", "#27ae60", "#c0392b", "#f39c12"],
                                ["#1abc9c", "#3498db", "#9b59b6", "#e74c3c", "#f1c40f", "#ecf0f1"],
                                ["#16a085", "#2980b9", "#8e44ad", "#c0392b", "#f39c12", "#bdc3c7"],
                                ["#3498db", "#2ecc71", "#e74c3c", "#f1c40f", "#9b59b6", "#ecf0f1"]
                            ],
                            change: function(color) {
                                console.log('Color cambiado en', id, ':', color.toHexString());
                                updatePreview();
                            },
                            move: function(color) {
                                updatePreview();
                            }
                        });
                        console.log('Color picker inicializado:', id);
                    } catch (error) {
                        console.error('Error al inicializar color picker', id, ':', error);
                    }
                });

                return true;
            }

            // Botón para restaurar colores predeterminados
            $("#reset-colors").click(function() {
                console.log('Restaurando colores predeterminados...');
                // Restaurar cada color a su valor predeterminado
                for (const [key, value] of Object.entries(defaultColors)) {
                    $("#" + key).spectrum("set", value);
                }

                // Actualizar vista previa
                updatePreview();

                // Mostrar mensaje
                M.toast({
                    html: '<i class="material-icons left">refresh</i> Colores restaurados a valores predeterminados',
                    classes: 'rounded',
                    displayLength: 3000
                });
            });

            // Inicializar color pickers con reintento
            function tryInitColorPickers(attempts = 0) {
                if (attempts > 5) {
                    console.error('No se pudieron inicializar los color pickers después de 5 intentos');
                    return;
                }

                if (typeof $.fn.spectrum !== 'undefined') {
                    if (initColorPickers()) {
                        console.log('Color pickers inicializados correctamente');
                        setTimeout(updatePreview, 500);
                    } else {
                        console.log('Reintentando inicialización en 500ms...');
                        setTimeout(() => tryInitColorPickers(attempts + 1), 500);
                    }
                } else {
                    console.log('Spectrum no disponible, reintentando en 200ms...');
                    setTimeout(() => tryInitColorPickers(attempts + 1), 200);
                }
            }

            // Iniciar el proceso
            tryInitColorPickers();
        });

        // Función para actualizar la vista previa
        function updatePreview() {
            try {
                console.log('Actualizando vista previa...');

                // Obtener colores de manera segura
                function getColor(id, defaultColor) {
                    const $element = $("#" + id);
                    if ($element.length === 0) return defaultColor;

                    // Intentar obtener el color de Spectrum primero
                    try {
                        if ($element.hasClass('sp-input') && typeof $element.spectrum === 'function') {
                            return $element.spectrum("get").toHexString();
                        }
                    } catch (e) {
                        console.log('Error obteniendo color de Spectrum para', id, ':', e);
                    }

                    // Fallback al valor del input
                    return $element.val() || defaultColor;
                }

                var primaryColor = getColor('primary_color', '#2c3e50');
                var secondaryColor = getColor('secondary_color', '#34495e');
                var accentColor = getColor('accent_color', '#2980b9');
                var successColor = getColor('success_color', '#27ae60');
                var dangerColor = getColor('danger_color', '#c0392b');
                var warningColor = getColor('warning_color', '#f39c12');
                var availabilityColor = getColor('availability_color', '#f1c40f');
                var bodyBgColor = getColor('body_bg_color', '#f5f6fa');

                // Actualizar encabezado y pie de página
                $("#preview-header, #preview-footer").css("background-color", primaryColor);

                // Actualizar botones normales
                $("#preview-primary").css("background-color", primaryColor);
                $("#preview-accent").css("background-color", accentColor);
                $("#preview-success").css("background-color", successColor);
                $("#preview-danger").css("background-color", dangerColor);
                $("#preview-warning").css("background-color", warningColor);

                // Actualizar botones flotantes
                $("#preview-float-primary").css("background-color", primaryColor);
                $("#preview-float-accent").css("background-color", accentColor);
                $("#preview-float-chat").css("background-color", primaryColor);

                // Actualizar badges
                $("#preview-badge-accent").css("background-color", accentColor);
                $("#preview-badge-success").css("background-color", successColor);
                $("#preview-badge-danger").css("background-color", dangerColor);
                $("#preview-badge-warning").css("background-color", warningColor);

                // Actualizar botón de disponibilidad
                $("#preview-availability").css("background-color", availabilityColor);

                // Actualizar mensajes
                $("#preview-message-success .green-text").css("color", successColor);
                $("#preview-message-error .red-text").css("color", dangerColor);

                // Crear CSS dinámico
                var dynamicCSS = `
                    :root {
                        --primary-color: ${primaryColor};
                        --secondary-color: ${secondaryColor};
                        --accent-color: ${accentColor};
                        --success-color: ${successColor};
                        --danger-color: ${dangerColor};
                        --warning-color: ${warningColor};
                        --availability-color: ${availabilityColor};
                        --body-bg-color: ${bodyBgColor};
                    }
                    body { background: var(--body-bg-color) !important; }
                `;

                // Aplicar CSS dinámico
                var styleElement = document.getElementById("dynamic-colors");
                if (!styleElement) {
                    styleElement = document.createElement("style");
                    styleElement.id = "dynamic-colors";
                    document.head.appendChild(styleElement);
                }
                styleElement.textContent = dynamicCSS;

                console.log('Vista previa actualizada correctamente');
            } catch (error) {
                console.error('Error al actualizar vista previa:', error);
            }
        }

        // Añadir efectos visuales a los botones de la vista previa cuando esté listo
        $(document).ready(function() {
            $(".preview-button").hover(
                function() {
                    $(this).css("transform", "scale(1.05)");
                    $(this).css("box-shadow", "0 4px 10px rgba(0,0,0,0.3)");
                },
                function() {
                    $(this).css("transform", "scale(1)");
                    $(this).css("box-shadow", "0 2px 5px rgba(0,0,0,0.2)");
                }
            );
        });
    </script>
</body>
</html>
