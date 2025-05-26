<?php
require_once __DIR__ . '/autoload.php';

// Script para probar la visualización de mensajes
// Redirige a catalogo.php con un mensaje de éxito o error según el parámetro

$type = isset($_GET['type']) ? $_GET['type'] : 'success';
$message = isset($_GET['message']) ? $_GET['message'] : 'Este es un mensaje de prueba';

if ($type === 'success') {
    header("Location: catalogo.php?success=2&message=" . urlencode($message));
} else {
    header("Location: catalogo.php?error=1&message=" . urlencode($message));
}
exit;
?>
