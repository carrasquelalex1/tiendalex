<?php
/**
 * Archivo para incluir los estilos CSS unificados
 * Este archivo se incluye en todas las páginas del sitio
 */

// Obtener la versión del tema para cache busting
$version_file = __DIR__ . '/../../css/theme_version.txt';
$theme_version = file_exists($version_file) ? trim(file_get_contents($version_file)) : time();
?>
<!-- Materialize CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
<!-- Material Icons -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@500&display=swap" rel="stylesheet">
<!-- Estilos unificados (base) -->
<link rel="stylesheet" href="/tiendalex2/css/tiendalex.css?v=<?php echo $theme_version; ?>">
<!-- Correcciones para el encabezado y los iconos flotantes -->
<link rel="stylesheet" href="/tiendalex2/css/header_fixes.css?v=<?php echo $theme_version; ?>">
<!-- Estilos para la actualización del tema -->
<link rel="stylesheet" href="/tiendalex2/css/theme_refresh.css?v=<?php echo $theme_version; ?>">
<!-- Tema dinámico (debe ser el último para tener prioridad) -->
<link rel="stylesheet" href="/tiendalex2/css/dynamic_theme.php?v=<?php echo $theme_version; ?>" id="dynamic-theme-css">
<!-- Estilos personalizados para forzar ajustes visuales de usuario -->
<link rel="stylesheet" href="/tiendalex2/css/custom.css?v=<?php echo time(); ?>">
<!-- Script para actualización global del tema -->
<script src="/tiendalex2/js/theme_global_updater.js?v=<?php echo $theme_version; ?>"></script>
