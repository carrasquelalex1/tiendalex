<?php
/**
 * CSS dinámico basado en la configuración del tema
 * Este archivo genera CSS con variables personalizadas según los colores seleccionados
 */

// Establecer el tipo de contenido como CSS
header('Content-Type: text/css');

// Deshabilitar el caché para asegurar que siempre se cargue la versión más reciente
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Verificar si existe un archivo de versión para el tema
$version_file = __DIR__ . '/theme_version.txt';
$theme_version = file_exists($version_file) ? file_get_contents($version_file) : time();

// Si no se puede leer el archivo, crear uno nuevo
if (!$theme_version) {
    $theme_version = time();
    @file_put_contents($version_file, $theme_version);
}

// Función para ajustar el brillo de un color hexadecimal
function adjustBrightness($hex, $steps) {
    // Eliminar el # si existe
    $hex = ltrim($hex, '#');

    // Convertir a RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // Ajustar el brillo
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));

    // Convertir de nuevo a hexadecimal
    return '#' . sprintf('%02x', $r) . sprintf('%02x', $g) . sprintf('%02x', $b);
}

// Añadir la versión como comentario para depuración
echo "/* Theme version: {$theme_version} - Generated: " . date('Y-m-d H:i:s') . " */\n";

// Forzar que el navegador no almacene en caché este archivo
echo "/* No-cache: " . uniqid() . " */\n";

// Incluir archivo de conexión
if (file_exists(__DIR__ . '/../backend/config/db.php')) {
    require_once __DIR__ . '/../backend/config/db.php';
} else {
    // Si no se puede cargar la conexión, usar colores predeterminados
    echo ':root {
        --primary-color: #2c3e50;
        --secondary-color: #34495e;
        --accent-color: #2980b9;
        --success-color: #27ae60;
        --danger-color: #c0392b;
        --warning-color: #f39c12;
        --light-color: #f5f5f5;
        --dark-color: #263238;
        --text-color: #333;
        --text-light: #757575;
        --border-color: #e0e0e0;
        --shadow-color: rgba(0, 0, 0, 0.1);
        --header-height: 64px;
    }';
    exit;
}

// Obtener la configuración del tema desde la base de datos
$configuracion_tema = [];
$sql = "SELECT * FROM configuracion_tema";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $configuracion_tema[$row['nombre_configuracion']] = $row['valor_configuracion'];
    }
}

// Valores predeterminados si no hay configuración
$primary_color = isset($configuracion_tema['primary_color']) && preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $configuracion_tema['primary_color'])
    ? $configuracion_tema['primary_color'] : '#2c3e50';
$secondary_color = isset($configuracion_tema['secondary_color']) && preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $configuracion_tema['secondary_color'])
    ? $configuracion_tema['secondary_color'] : '#34495e';
$accent_color = isset($configuracion_tema['accent_color']) && preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $configuracion_tema['accent_color'])
    ? $configuracion_tema['accent_color'] : '#2980b9';
$success_color = isset($configuracion_tema['success_color']) && preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $configuracion_tema['success_color'])
    ? $configuracion_tema['success_color'] : '#27ae60';
$danger_color = isset($configuracion_tema['danger_color']) && preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $configuracion_tema['danger_color'])
    ? $configuracion_tema['danger_color'] : '#c0392b';
$warning_color = isset($configuracion_tema['warning_color']) && preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $configuracion_tema['warning_color'])
    ? $configuracion_tema['warning_color'] : '#f39c12';
$availability_color = isset($configuracion_tema['availability_color']) && preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $configuracion_tema['availability_color'])
    ? $configuracion_tema['availability_color'] : '#f1c40f';

// Generar CSS con variables
?>
:root {
    --primary-color: <?php echo $primary_color; ?> !important;
    --secondary-color: <?php echo $secondary_color; ?> !important;
    --accent-color: <?php echo $accent_color; ?> !important;
    --accent-hover: <?php echo adjustBrightness($accent_color, 20); ?> !important;
    --success-color: <?php echo $success_color; ?> !important;
    --success-hover: <?php echo adjustBrightness($success_color, 20); ?> !important;
    --danger-color: <?php echo $danger_color; ?> !important;
    --danger-hover: <?php echo adjustBrightness($danger_color, 20); ?> !important;
    --warning-color: <?php echo $warning_color; ?> !important;
    --availability-color: <?php echo $availability_color; ?> !important;
    --availability-hover: <?php echo adjustBrightness($availability_color, 20); ?> !important;
    --light-color: #f5f5f5;
    --dark-color: #263238;
    --text-color: #333;
    --text-light: #757575;
    --border-color: #e0e0e0;
    --shadow-color: rgba(0, 0, 0, 0.1);
    --header-height: 64px;
}

/* Estilos específicos para el encabezado */
header, nav, .page-footer {
    background-color: var(--primary-color) !important;
}

/* Estilos para botones */
.btn {
    background-color: var(--primary-color);
}

.btn:hover {
    background-color: var(--secondary-color);
}

.btn.green, .btn.success {
    background-color: var(--success-color);
}

.btn.green:hover, .btn.success:hover {
    background-color: var(--success-hover);
}

.btn.red, .btn.danger {
    background-color: var(--danger-color);
}

.btn.red:hover, .btn.danger:hover {
    background-color: var(--danger-hover);
}

.btn.blue, .btn.accent {
    background-color: var(--accent-color);
}

.btn.blue:hover, .btn.accent:hover {
    background-color: var(--accent-hover);
}

.btn.orange, .btn.warning {
    background-color: var(--warning-color);
}

/* Estilos para botón de disponibilidad */
.stock-container .btn.availability-btn,
.btn.availability-btn,
button.availability-btn,
button.btn.availability-btn,
.stock-container .btn:not(.red),
.producto-item .stock-container button.btn:not(.red) {
    background-color: var(--availability-color, #f1c40f) !important;
    color: white !important;
}

.stock-container .btn.availability-btn:hover,
.btn.availability-btn:hover,
button.availability-btn:hover,
button.btn.availability-btn:hover,
.stock-container .btn:not(.red):hover,
.producto-item .stock-container button.btn:not(.red):hover {
    background-color: var(--availability-hover, var(--availability-color, #f1c40f)) !important;
    filter: brightness(1.1);
}

/* Estilos para el botón de disponibilidad en la vista previa */
#preview-availability {
    background-color: var(--availability-color, #f1c40f) !important;
}

/* Estilos para botones de disponibilidad en el catálogo */
.producto-item .stock-container button.btn:not(.red) {
    background-color: var(--availability-color, #f1c40f) !important;
    width: 100%;
    text-transform: uppercase;
    font-weight: 500;
    letter-spacing: 0.5px;
}

/* Forzar la aplicación del color en todos los contextos */
[class*="availability-btn"]:not(.red) {
    background-color: var(--availability-color, #f1c40f) !important;
}

/* Estilos para botones flotantes */
.btn-floating {
    background-color: var(--primary-color);
}

.btn-floating:hover {
    background-color: var(--secondary-color);
}

.fixed-action-btn.chat-float .btn-floating {
    background-color: var(--primary-color);
}

.fixed-action-btn.chat-float .btn-floating:hover {
    background-color: var(--secondary-color);
}

.fixed-action-btn.cart-float .btn-floating {
    background-color: var(--accent-color);
}

.fixed-action-btn.cart-float .btn-floating:hover {
    background-color: var(--accent-hover);
}

/* Estilos para enlaces */
.page-footer a {
    color: white !important;
}

/* Estilos para badges */
.badge.new.blue {
    background-color: var(--accent-color) !important;
}

.badge.new.green {
    background-color: var(--success-color) !important;
}

.badge.new.red {
    background-color: var(--danger-color) !important;
}

.badge.new.orange {
    background-color: var(--warning-color) !important;
}

/* Estilos para checkboxes y radio buttons */
[type="checkbox"].filled-in:checked+span:not(.lever):after {
    border: 2px solid var(--accent-color);
    background-color: var(--accent-color);
}

[type="radio"]:checked+span:after,
[type="radio"].with-gap:checked+span:after {
    background-color: var(--accent-color);
}

[type="radio"]:checked+span:after,
[type="radio"].with-gap:checked+span:before,
[type="radio"].with-gap:checked+span:after {
    border: 2px solid var(--accent-color);
}

/* Estilos para tabs */
.tabs .tab a {
    color: var(--primary-color);
}

.tabs .tab a:hover, .tabs .tab a.active {
    color: var(--accent-color);
}

.tabs .indicator {
    background-color: var(--accent-color);
}

/* Estilos para dropdown */
.dropdown-content li > a, .dropdown-content li > span {
    color: var(--accent-color);
}

/* Estilos para paginación */
.pagination li.active {
    background-color: var(--primary-color);
}

/* Estilos para colecciones */
.collection .collection-item.active {
    background-color: var(--primary-color);
    color: white;
}

.collection a.collection-item:hover {
    background-color: rgba(0, 0, 0, 0.05);
    color: var(--accent-color);
}

/* Estilos para sidenav */
.sidenav .user-view {
    background-color: var(--primary-color) !important;
}

.sidenav .user-view .name,
.sidenav .user-view .email {
    color: white !important;
}

/* Estilos para preloader */
.preloader-wrapper .spinner-layer {
    border-color: var(--accent-color);
}

/* Estilos para títulos de página que deben usar el color del encabezado */
.hero-title,
.page-title,
h1.hero-title,
.section-title {
    color: var(--primary-color) !important;
}

/* Subtítulos que deben usar el color de acento */
.hero-subtitle {
    color: var(--accent-color) !important;
}

/* Títulos de sección que deben usar el color primario */
.section-title::after {
    background-color: var(--primary-color) !important;
}

/* Estilos para switches */
.switch label input[type=checkbox]:checked + .lever {
    background-color: var(--accent-color);
}

.switch label input[type=checkbox]:checked + .lever:after {
    background-color: var(--accent-color);
}

/* Estilos para inputs */
input:not([type]):focus:not([readonly]),
input[type="text"]:focus:not([readonly]),
input[type="password"]:focus:not([readonly]),
input[type="email"]:focus:not([readonly]),
input[type="url"]:focus:not([readonly]),
input[type="time"]:focus:not([readonly]),
input[type="date"]:focus:not([readonly]),
input[type="datetime"]:focus:not([readonly]),
input[type="datetime-local"]:focus:not([readonly]),
input[type="tel"]:focus:not([readonly]),
input[type="number"]:focus:not([readonly]),
input[type="search"]:focus:not([readonly]),
textarea.materialize-textarea:focus:not([readonly]) {
    border-bottom: 1px solid var(--accent-color);
    box-shadow: 0 1px 0 0 var(--accent-color);
}

input:not([type]):focus:not([readonly]) + label,
input[type="text"]:focus:not([readonly]) + label,
input[type="password"]:focus:not([readonly]) + label,
input[type="email"]:focus:not([readonly]) + label,
input[type="url"]:focus:not([readonly]) + label,
input[type="time"]:focus:not([readonly]) + label,
input[type="date"]:focus:not([readonly]) + label,
input[type="datetime"]:focus:not([readonly]) + label,
input[type="datetime-local"]:focus:not([readonly]) + label,
input[type="tel"]:focus:not([readonly]) + label,
input[type="number"]:focus:not([readonly]) + label,
input[type="search"]:focus:not([readonly]) + label,
textarea.materialize-textarea:focus:not([readonly]) + label {
    color: var(--accent-color);
}
