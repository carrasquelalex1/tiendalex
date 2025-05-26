<?php
// Enhanced db.php

$servername = "localhost";
$username = "alex";
$password = "12569655";
$dbname = "tiendalex2";

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set custom error log location - with permission check
$logFile = '/var/www/html/tiendalex2/logs/php_errors.log';
$logDir = dirname($logFile);

// Solo intentar crear el directorio y configurar el log si tenemos permisos
if (is_writable(dirname($logDir))) {
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    // Verificar si el directorio se creó correctamente o ya existe y es escribible
    if (is_dir($logDir) && is_writable($logDir)) {
        ini_set('error_log', $logFile);
    }
}

// Database connection with improved error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    // Registrar el error si es posible, pero no fallar si no se puede
    @error_log('[' . date('Y-m-d H:i:s') . '] Database Error: ' . $e->getMessage());

    // En desarrollo, mostrar el error específico
    if (ini_get('display_errors')) {
        die('Error de base de datos: ' . $e->getMessage());
    } else {
        // En producción, mostrar un mensaje genérico
        die('Ha ocurrido un error en la base de datos. Por favor, inténtelo más tarde.');
    }
}
?>