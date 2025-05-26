<?php
/**
 * Script final para solucionar definitivamente el problema del color de disponibilidad
 */

require_once 'backend/config/db.php';

echo "<h1>Solucionando el problema del color de disponibilidad</h1>";

// Paso 1: Verificar y corregir la base de datos
echo "<h2>Paso 1: Verificando base de datos</h2>";

// Verificar si existe la tabla
$check_table = "SHOW TABLES LIKE 'configuracion_tema'";
$result = $conn->query($check_table);

if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ Tabla no existe. Creándola...</p>";

    $create_table = "CREATE TABLE configuracion_tema (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_configuracion VARCHAR(50) NOT NULL UNIQUE,
        valor_configuracion VARCHAR(255) NOT NULL,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    if ($conn->query($create_table)) {
        echo "<p style='color: green;'>✅ Tabla creada</p>";
    } else {
        die("Error creando tabla: " . $conn->error);
    }
}

// Verificar si existe el registro de availability_color
$check_availability = "SELECT * FROM configuracion_tema WHERE nombre_configuracion = 'availability_color'";
$result = $conn->query($check_availability);

if ($result->num_rows == 0) {
    echo "<p style='color: orange;'>⚠️ Campo availability_color no existe. Agregándolo...</p>";

    $insert_availability = "INSERT INTO configuracion_tema (nombre_configuracion, valor_configuracion) VALUES ('availability_color', '#f1c40f')";
    if ($conn->query($insert_availability)) {
        echo "<p style='color: green;'>✅ Campo availability_color agregado con valor #f1c40f</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
    }
} else {
    $row = $result->fetch_assoc();
    echo "<p style='color: green;'>✅ Campo availability_color existe: " . $row['valor_configuracion'] . "</p>";
}

// Paso 2: Verificar el CSS dinámico
echo "<h2>Paso 2: Verificando CSS dinámico</h2>";

$css_file = 'css/dynamic_theme.php';
if (file_exists($css_file)) {
    echo "<p style='color: green;'>✅ Archivo CSS dinámico existe</p>";

    // Probar el CSS dinámico
    ob_start();
    include $css_file;
    $css_content = ob_get_clean();

    if (strpos($css_content, '--availability-color') !== false) {
        echo "<p style='color: green;'>✅ Variable --availability-color encontrada en CSS</p>";
    } else {
        echo "<p style='color: red;'>❌ Variable --availability-color NO encontrada en CSS</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Archivo CSS dinámico no existe</p>";
}

// Paso 3: Crear un CSS de respaldo específico
echo "<h2>Paso 3: Creando CSS de respaldo</h2>";

$availability_color_query = "SELECT valor_configuracion FROM configuracion_tema WHERE nombre_configuracion = 'availability_color'";
$result = $conn->query($availability_color_query);
$availability_color = '#f1c40f'; // Default

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $availability_color = $row['valor_configuracion'];
}

$css_backup_content = "/* CSS de respaldo para color de disponibilidad */
:root {
    --availability-color: {$availability_color} !important;
}

/* Selectores específicos para botones de disponibilidad */
.stock-container .btn:not(.red):not([class*='agotado']),
.btn.availability-btn:not(.red),
button.availability-btn:not(.red),
[data-is-available='true']:not(.red),
.producto-item .stock-container button.btn:not(.red),
.card .stock-container .btn:not(.red) {
    background-color: {$availability_color} !important;
    color: white !important;
}

/* Hover effects */
.stock-container .btn:not(.red):hover,
.btn.availability-btn:not(.red):hover,
button.availability-btn:not(.red):hover,
[data-is-available='true']:not(.red):hover {
    background-color: {$availability_color} !important;
    filter: brightness(1.1) !important;
}

/* Asegurar que los botones agotados permanezcan rojos */
.btn.red,
.btn[class*='agotado'] {
    background-color: #f44336 !important;
    color: white !important;
}
";

$css_backup_file = 'css/availability_color_backup.css';
if (file_put_contents($css_backup_file, $css_backup_content)) {
    echo "<p style='color: green;'>✅ CSS de respaldo creado: {$css_backup_file}</p>";
} else {
    echo "<p style='color: red;'>❌ Error creando CSS de respaldo</p>";
}

// Paso 4: Crear JavaScript de respaldo
echo "<h2>Paso 4: Creando JavaScript de respaldo</h2>";

$js_backup_content = "/**
 * JavaScript de respaldo para aplicar color de disponibilidad
 */
(function() {
    'use strict';

    const AVAILABILITY_COLOR = '{$availability_color}';

    function applyAvailabilityColor() {
        console.log('Aplicando color de disponibilidad de respaldo:', AVAILABILITY_COLOR);

        // Actualizar variable CSS
        document.documentElement.style.setProperty('--availability-color', AVAILABILITY_COLOR);

        // Aplicar a botones específicos
        const selectors = [
            '.stock-container .btn:not(.red)',
            '.btn.availability-btn:not(.red)',
            'button.availability-btn:not(.red)',
            '[data-is-available=\"true\"]:not(.red)',
            '.producto-item .stock-container button.btn:not(.red)',
            '.card .stock-container .btn:not(.red)'
        ];

        selectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(btn => {
                const text = btn.textContent.toLowerCase();
                if (!text.includes('agotado') && !btn.classList.contains('red')) {
                    btn.style.setProperty('background-color', AVAILABILITY_COLOR, 'important');
                    btn.style.setProperty('color', 'white', 'important');
                }
            });
        });
    }

    // Aplicar inmediatamente
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyAvailabilityColor);
    } else {
        applyAvailabilityColor();
    }

    // Aplicar después de cargar
    window.addEventListener('load', function() {
        setTimeout(applyAvailabilityColor, 500);
        setTimeout(applyAvailabilityColor, 2000);
    });

    // Observar cambios
    const observer = new MutationObserver(function(mutations) {
        let shouldApply = false;
        mutations.forEach(mutation => {
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && (
                        node.matches && node.matches('.btn') ||
                        node.querySelector && node.querySelector('.btn')
                    )) {
                        shouldApply = true;
                    }
                });
            }
        });

        if (shouldApply) {
            setTimeout(applyAvailabilityColor, 100);
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Exponer globalmente
    window.forceAvailabilityColor = applyAvailabilityColor;

})();";

$js_backup_file = 'js/availability_color_backup.js';
if (file_put_contents($js_backup_file, $js_backup_content)) {
    echo "<p style='color: green;'>✅ JavaScript de respaldo creado: {$js_backup_file}</p>";
} else {
    echo "<p style='color: red;'>❌ Error creando JavaScript de respaldo</p>";
}

// Paso 5: Actualizar css_includes.php
echo "<h2>Paso 5: Actualizando includes</h2>";

$css_includes_file = 'frontend/includes/css_includes.php';
$css_includes_content = file_get_contents($css_includes_file);

// Verificar si ya incluye el CSS de respaldo
if (strpos($css_includes_content, 'availability_color_backup.css') === false) {
    // Agregar antes del final
    $new_line = "<!-- CSS de respaldo para color de disponibilidad -->\n<link rel=\"stylesheet\" href=\"/tiendalex2/css/availability_color_backup.css?v=" . time() . "\">\n<!-- JavaScript de respaldo para color de disponibilidad -->\n<script src=\"/tiendalex2/js/availability_color_backup.js?v=" . time() . "\"></script>\n";

    // Buscar donde insertar (antes del final del archivo)
    $insert_position = strrpos($css_includes_content, "\n");
    if ($insert_position !== false) {
        $css_includes_content = substr_replace($css_includes_content, $new_line, $insert_position, 0);
    } else {
        $css_includes_content .= $new_line;
    }

    if (file_put_contents($css_includes_file, $css_includes_content)) {
        echo "<p style='color: green;'>✅ Archivos de respaldo agregados a css_includes.php</p>";
    } else {
        echo "<p style='color: red;'>❌ Error actualizando css_includes.php</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ️ Los archivos de respaldo ya están incluidos</p>";
}

echo "<h2>Paso 6: Resumen y pruebas</h2>";

// Mostrar el color actual
$final_color_query = "SELECT valor_configuracion FROM configuracion_tema WHERE nombre_configuracion = 'availability_color'";
$result = $conn->query($final_color_query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $final_color = $row['valor_configuracion'];
    echo "<div style='padding: 20px; background: #f0f0f0; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>Color de disponibilidad configurado:</h3>";
    echo "<div style='display: inline-block; width: 50px; height: 50px; background-color: {$final_color}; border: 1px solid #ccc; border-radius: 4px; margin-right: 10px; vertical-align: middle;'></div>";
    echo "<strong>{$final_color}</strong>";
    echo "</div>";
}

echo "<h3>Archivos creados/actualizados:</h3>";
echo "<ul>";
echo "<li>✅ css/availability_color_backup.css</li>";
echo "<li>✅ js/availability_color_backup.js</li>";
echo "<li>✅ frontend/includes/css_includes.php (actualizado)</li>";
echo "</ul>";

echo "<h3>Próximos pasos:</h3>";
echo "<ol>";
echo "<li>Ve a <a href='admin_tema.php'>Configuración del Tema</a> para cambiar el color si es necesario</li>";
echo "<li>Visita el <a href='catalogo.php'>Catálogo</a> para verificar que el color se aplica</li>";
echo "<li>Usa <a href='test_availability_fix.php'>la página de prueba</a> para verificar la funcionalidad</li>";
echo "</ol>";

$conn->close();
echo "<p style='color: green; font-weight: bold;'>🎉 Proceso completado exitosamente!</p>";
?>
