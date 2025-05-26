<?php
require_once __DIR__ . '/autoload.php';
/**
 * Script para actualizar automáticamente todos los archivos PHP para usar el nuevo archivo JS unificado
 * Este script busca todos los archivos PHP en el directorio raíz y actualiza las referencias a los archivos JS
 */

// Directorio raíz
$root_dir = __DIR__;

// Obtener todos los archivos PHP en el directorio raíz
$files = glob($root_dir . '/*.php');

// Patrones a buscar y reemplazar
$patterns = [
    // Patrón 1: jQuery + Materialize JS + scripts.js
    [
        'search' => '/<script src="https:\/\/code\.jquery\.com\/jquery-3\.6\.0\.min\.js"><\/script>\s*<script src="https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/materialize\/1\.0\.0\/js\/materialize\.min\.js"><\/script>\s*<script src="js\/scripts\.js"><\/script>/s',
        'replace' => '<?php include \'frontend/includes/js_includes.php\'; ?>'
    ],
    // Patrón 2: jQuery + Materialize JS
    [
        'search' => '/<script src="https:\/\/code\.jquery\.com\/jquery-3\.6\.0\.min\.js"><\/script>\s*<script src="https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/materialize\/1\.0\.0\/js\/materialize\.min\.js"><\/script>/s',
        'replace' => '<?php include \'frontend/includes/js_includes.php\'; ?>'
    ],
    // Patrón 3: Inicialización de Materialize con M.AutoInit()
    [
        'search' => '/<script>\s*document\.addEventListener\(\'DOMContentLoaded\', function\(\) {\s*M\.AutoInit\(\);\s*}\);\s*<\/script>/s',
        'replace' => '<!-- Inicialización de Materialize incluida en js_includes.php -->'
    ]
];

// Contador de archivos actualizados
$updated_files = 0;

// Procesar cada archivo
foreach ($files as $file) {
    // Excluir los archivos de actualización
    if (basename($file) == 'actualizar_css.php' || basename($file) == 'actualizar_js.php') {
        continue;
    }
    
    // Leer el contenido del archivo
    $content = file_get_contents($file);
    $original_content = $content;
    
    // Aplicar cada patrón
    foreach ($patterns as $pattern) {
        $content = preg_replace($pattern['search'], $pattern['replace'], $content);
    }
    
    // Si el contenido ha cambiado, guardar el archivo
    if ($content !== $original_content) {
        file_put_contents($file, $content);
        $updated_files++;
        echo "Actualizado: " . basename($file) . "<br>";
    }
}

echo "<h2>Proceso completado</h2>";
echo "<p>Se actualizaron $updated_files archivos.</p>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
