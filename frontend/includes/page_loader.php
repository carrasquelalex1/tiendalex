<?php
/**
 * Cargador de páginas dinámico
 *
 * Este archivo determina qué contenido cargar basado en la URL actual.
 * Utiliza un array de mapeo para relacionar nombres de archivo con sus correspondientes archivos de cuerpo.
 */

// Determinar qué página mostrar basado en la URL actual
$current_page = basename($_SERVER['PHP_SELF']);
$base_path = 'frontend/includes/';

// Mapeo de nombres de archivo a archivos de cuerpo
$page_bodies = [
    'index.php'     => 'bodyprincipal.php',
    'tienda.php'    => 'bodytienda.php',
    'servicios.php' => 'bodytienda.php',
    'catalogo.php'  => 'bodycatalogo.php',
    'acerca.php'    => 'bodyacerca.php',
    'contacto.php'  => 'bodycontacto.php',
];

// Determinar qué archivo incluir
if (isset($page_bodies[$current_page])) {
    $body_to_include = $page_bodies[$current_page];
} else {
    // Página por defecto si no coincide con ninguna conocida
    $body_to_include = 'bodyprincipal.php';
}

// Incluir el archivo
$file_path = $base_path . $body_to_include;
if (file_exists($file_path)) {
    // Si la página no es el catálogo (que ya incluye los mensajes), incluir el componente de mensajes
    if ($body_to_include !== 'bodycatalogo.php') {
        include_once 'frontend/includes/messages.php';
    }

    include $file_path;
} else {
    // Manejar el caso de que el archivo mapeado no exista
    include_once 'frontend/includes/messages.php';
    echo "<div class='container'><p class='red-text'>Error: El archivo de contenido '$body_to_include' no fue encontrado.</p></div>";
    // Intentar incluir el archivo por defecto
    if (file_exists($base_path . 'bodyprincipal.php')) {
        include $base_path . 'bodyprincipal.php';
    }
}
?>
