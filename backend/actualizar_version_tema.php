<?php
/**
 * Script para actualizar la versión del tema
 * Este script actualiza el archivo de versión del tema para forzar la recarga del CSS en todas las páginas
 */

// Habilitar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si se está ejecutando desde la línea de comandos o desde el navegador
$is_cli = (php_sapi_name() === 'cli');

// Función para actualizar la versión del tema
function actualizar_version_tema() {
    // Ruta al archivo de versión del tema
    $version_file = __DIR__ . '/../css/theme_version.txt';
    $version = time();

    // Registrar la acción
    error_log("Actualizando versión del tema: $version_file con versión $version");

    // Asegurarse de que el directorio existe
    if (!is_dir(dirname($version_file))) {
        if (!mkdir(dirname($version_file), 0777, true)) {
            return "Error al crear el directorio: " . dirname($version_file);
        }
    }

    // Verificar permisos de escritura
    if (!is_writable(dirname($version_file))) {
        // Intentar cambiar los permisos
        if (!chmod(dirname($version_file), 0777)) {
            return "Error: El directorio no tiene permisos de escritura: " . dirname($version_file);
        }
    }

    // Guardar la versión en el archivo
    $result = file_put_contents($version_file, $version);
    if ($result === false) {
        // Intentar con otro método
        $fp = fopen($version_file, 'w');
        if (!$fp) {
            return "Error al abrir el archivo para escritura: $version_file";
        }

        $result = fwrite($fp, $version);
        fclose($fp);

        if ($result === false) {
            return "Error al escribir en el archivo: $version_file";
        }
    }

    // Asegurarse de que el archivo tenga los permisos correctos
    chmod($version_file, 0666);

    // Limpiar la caché de opcode si está disponible
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }

    return "Archivo de versión del tema actualizado: $version_file con versión $version";
}

// Actualizar la versión del tema
$resultado = actualizar_version_tema();

// Mostrar el resultado solo si se llama directamente, no cuando se incluye
if (!defined('INCLUIDO_DESDE_ADMIN')) {
    if ($is_cli) {
        echo $resultado . PHP_EOL;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => $resultado, 'version' => time()]);
    }
}
?>
