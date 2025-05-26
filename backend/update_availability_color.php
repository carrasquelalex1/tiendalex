<?php
/**
 * Script para actualizar el color de disponibilidad y regenerar archivos de respaldo
 */

require_once 'config/db.php';

// Verificar que se reciba el color
if (!isset($_POST['availability_color']) || empty($_POST['availability_color'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Color no proporcionado']);
    exit;
}

$availability_color = $_POST['availability_color'];

// Validar formato hexadecimal
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $availability_color)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de color inválido']);
    exit;
}

try {
    // Actualizar en la base de datos
    $stmt = $conn->prepare("UPDATE configuracion_tema SET valor_configuracion = ? WHERE nombre_configuracion = 'availability_color'");
    $stmt->bind_param("s", $availability_color);
    
    if (!$stmt->execute()) {
        throw new Exception("Error actualizando base de datos: " . $conn->error);
    }
    
    // Regenerar CSS de respaldo
    $css_backup_content = "/* CSS de respaldo para color de disponibilidad - Generado automáticamente */
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

/* Última actualización: " . date('Y-m-d H:i:s') . " */
";

    $css_backup_file = '../css/availability_color_backup.css';
    if (!file_put_contents($css_backup_file, $css_backup_content)) {
        throw new Exception("Error escribiendo archivo CSS de respaldo");
    }
    
    // Regenerar JavaScript de respaldo
    $js_backup_content = "/**
 * JavaScript de respaldo para aplicar color de disponibilidad
 * Generado automáticamente - Última actualización: " . date('Y-m-d H:i:s') . "
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

    $js_backup_file = '../js/availability_color_backup.js';
    if (!file_put_contents($js_backup_file, $js_backup_content)) {
        throw new Exception("Error escribiendo archivo JavaScript de respaldo");
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'color' => $availability_color,
        'message' => 'Color de disponibilidad actualizado correctamente',
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
