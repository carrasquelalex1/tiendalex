<?php
require_once __DIR__ . '/autoload.php';
/**
 * Script para actualizar automáticamente todos los archivos PHP para usar el nuevo archivo CSS unificado
 * Este script busca todos los archivos PHP en el directorio raíz y actualiza las referencias a los archivos CSS
 */

// Directorio raíz
$root_dir = __DIR__;

// Obtener todos los archivos PHP en el directorio raíz
$files = glob($root_dir . '/*.php');

// Patrones a buscar y reemplazar
$patterns = [
    // Patrón 1: CSS de Materialize + Íconos + CSS personalizado (styles.css, real-estate.css, custom.css)
    [
        'search' => '/<link rel="stylesheet" href="https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/materialize\/1\.0\.0\/css\/materialize\.min\.css">\s*<link href="https:\/\/fonts\.googleapis\.com\/icon\?family=Material\+Icons" rel="stylesheet">\s*<link rel="stylesheet" href="css\/styles\.css">\s*<link rel="stylesheet" href="css\/real-estate\.css">\s*<link rel="stylesheet" href="css\/custom\.css">/s',
        'replace' => '<?php include \'frontend/includes/css_includes.php\'; ?>'
    ],
    // Patrón 2: CSS de Materialize + Íconos + CSS personalizado (styles.css, custom.css)
    [
        'search' => '/<link rel="stylesheet" href="https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/materialize\/1\.0\.0\/css\/materialize\.min\.css">\s*<link href="https:\/\/fonts\.googleapis\.com\/icon\?family=Material\+Icons" rel="stylesheet">\s*<link rel="stylesheet" href="css\/styles\.css">\s*<link rel="stylesheet" href="css\/custom\.css">/s',
        'replace' => '<?php include \'frontend/includes/css_includes.php\'; ?>'
    ],
    // Patrón 3: CSS de Materialize + Íconos + CSS personalizado (styles.css)
    [
        'search' => '/<link rel="stylesheet" href="https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/materialize\/1\.0\.0\/css\/materialize\.min\.css">\s*<link href="https:\/\/fonts\.googleapis\.com\/icon\?family=Material\+Icons" rel="stylesheet">\s*<link rel="stylesheet" href="css\/styles\.css">/s',
        'replace' => '<?php include \'frontend/includes/css_includes.php\'; ?>'
    ],
    // Patrón 4: CSS personalizado (styles.css, real-estate.css, custom.css)
    [
        'search' => '/<link rel="stylesheet" href="css\/styles\.css">\s*<link rel="stylesheet" href="css\/real-estate\.css">\s*<link rel="stylesheet" href="css\/custom\.css">/s',
        'replace' => '<?php include \'frontend/includes/css_includes.php\'; ?>'
    ],
    // Patrón 5: CSS personalizado (styles.css, custom.css)
    [
        'search' => '/<link rel="stylesheet" href="css\/styles\.css">\s*<link rel="stylesheet" href="css\/custom\.css">/s',
        'replace' => '<?php include \'frontend/includes/css_includes.php\'; ?>'
    ],
    // Patrón 6: CSS personalizado (styles.css, real-estate.css, chat.css, custom.css)
    [
        'search' => '/<link rel="stylesheet" href="css\/styles\.css">\s*<link rel="stylesheet" href="css\/real-estate\.css">\s*<link rel="stylesheet" href="css\/chat\.css">\s*<link rel="stylesheet" href="css\/custom\.css">/s',
        'replace' => '<?php include \'frontend/includes/css_includes.php\'; ?><link rel="stylesheet" href="css/chat.css">'
    ],
    // Patrón 7: CSS personalizado (styles.css, chat.css)
    [
        'search' => '/<link rel="stylesheet" href="css\/styles\.css">\s*<link rel="stylesheet" href="css\/chat\.css">/s',
        'replace' => '<?php include \'frontend/includes/css_includes.php\'; ?><link rel="stylesheet" href="css/chat.css">'
    ]
];

// Contador de archivos actualizados
$updated_files = 0;

// Procesar cada archivo
foreach ($files as $file) {
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
