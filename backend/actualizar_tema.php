<?php
/**
 * Script para actualizar la versión del tema
 * Este script puede ser llamado directamente desde la línea de comandos o mediante una solicitud AJAX
 */

// Habilitar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el script de actualización de versión del tema
require_once __DIR__ . '/actualizar_version_tema.php';

// El resultado ya ha sido mostrado por el script incluido
?>
