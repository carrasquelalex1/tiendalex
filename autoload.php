<?php
/**
 * Autoloader mejorado para la aplicación
 * 
 * Este autoloader soporta:
 * - Clases con y sin namespaces
 * - Múltiples directorios base
 * - Convenciones PSR-4
 */

// Registrar el autoloader
spl_autoload_register(function ($className) {
    // Directorios base donde buscar las clases
    $baseDirs = [
        __DIR__ . '/controllers/',
        __DIR__ . '/models/',
        __DIR__ . '/views/',
        __DIR__ . '/helpers/',
        __DIR__ . '/backend/'
    ];

    // Si la clase usa namespaces
    if (strpos($className, '\\') !== false) {
        // Convertir los separadores de namespace a directorios
        $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
        
        // Buscar en los directorios base
        foreach ($baseDirs as $baseDir) {
            $file = $baseDir . $classPath;
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
    } else {
        // Para clases sin namespaces, buscar directamente
        foreach ($baseDirs as $baseDir) {
            $file = $baseDir . $className . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
            
            // Intentar con minúsculas para el nombre del archivo
            $file = $baseDir . strtolower($className) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
    }
    
    // Registrar un error si no se encuentra la clase
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        error_log("No se pudo cargar la clase: $className");
    }
    
    return false;
});

/**
 * Función para cargar archivos de configuración
 * 
 * @param string $configFile Nombre del archivo de configuración (sin extensión)
 * @return array Configuración cargada
 */
function loadConfig($configFile) {
    $configPath = __DIR__ . '/config/' . $configFile . '.php';
    if (file_exists($configPath)) {
        return require $configPath;
    }
    
    error_log("Archivo de configuración no encontrado: $configPath");
    return [];
}

/**
 * Función para cargar helpers
 * 
 * @param string|array $helpers Nombre del helper o array de nombres
 * @return void
 */
function loadHelper($helpers) {
    if (!is_array($helpers)) {
        $helpers = [$helpers];
    }
    
    foreach ($helpers as $helper) {
        $helperPath = __DIR__ . '/helpers/' . $helper . '.php';
        if (file_exists($helperPath)) {
            require_once $helperPath;
        } else {
            error_log("Helper no encontrado: $helperPath");
        }
    }
}
