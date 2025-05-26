<?php
/**
 * Script para verificar los registros de error de PHP
 */

// Verificar si el usuario está autenticado como administrador
require_once '../session_helper.php';
iniciar_sesion_segura();

if (!esta_logueado() || !es_admin()) {
    echo "No tienes permisos para acceder a esta página.";
    exit;
}

// Función para mostrar los últimos N registros de error
function mostrar_ultimos_registros($archivo, $n = 50) {
    if (file_exists($archivo)) {
        $lineas = file($archivo);
        $total_lineas = count($lineas);
        $inicio = max(0, $total_lineas - $n);
        
        echo "<h3>Últimos $n registros de error (de un total de $total_lineas):</h3>";
        echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
        
        for ($i = $inicio; $i < $total_lineas; $i++) {
            // Resaltar las líneas que contienen "marcar_enviado.php"
            if (strpos($lineas[$i], "marcar_enviado.php") !== false) {
                echo "<span style='color: red; font-weight: bold;'>" . htmlspecialchars($lineas[$i]) . "</span>";
            } else {
                echo htmlspecialchars($lineas[$i]);
            }
        }
        
        echo "</pre>";
    } else {
        echo "<p>El archivo de registro no existe o no es accesible.</p>";
    }
}

// Obtener la ruta del archivo de registro de errores de PHP
$error_log_path = ini_get('error_log');

// Si no se puede obtener la ruta, intentar con ubicaciones comunes
if (empty($error_log_path) || !file_exists($error_log_path)) {
    $posibles_rutas = [
        '/var/log/apache2/error.log',
        '/var/log/httpd/error_log',
        '/var/log/php_errors.log',
        '/var/log/php/php_errors.log',
        '/var/log/php/error.log',
        '/var/log/nginx/error.log',
        '/var/www/logs/error.log',
        '/var/www/html/error.log',
        '/var/www/html/tiendalex2/error.log',
        '/var/www/html/tiendalex2/logs/error.log',
        '/tmp/php_errors.log'
    ];
    
    foreach ($posibles_rutas as $ruta) {
        if (file_exists($ruta)) {
            $error_log_path = $ruta;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Registros de Error</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <style>
        body {
            padding: 20px;
        }
        .container {
            max-width: 1200px;
        }
        h2 {
            color: #1565C0;
            margin-bottom: 20px;
        }
        .card {
            padding: 20px;
            margin-bottom: 30px;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verificación de Registros de Error</h2>
        
        <div class="card">
            <h4>Información del Archivo de Registro</h4>
            <p><strong>Ruta configurada en PHP:</strong> <?php echo ini_get('error_log') ?: 'No configurada'; ?></p>
            <p><strong>Ruta utilizada:</strong> <?php echo $error_log_path ?: 'No se encontró ningún archivo de registro'; ?></p>
            
            <?php if (!empty($error_log_path) && file_exists($error_log_path)): ?>
                <p><strong>Tamaño del archivo:</strong> <?php echo round(filesize($error_log_path) / 1024, 2); ?> KB</p>
                <p><strong>Última modificación:</strong> <?php echo date('Y-m-d H:i:s', filemtime($error_log_path)); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <?php
            if (!empty($error_log_path) && file_exists($error_log_path)) {
                mostrar_ultimos_registros($error_log_path, 100);
            } else {
                echo "<p class='red-text'>No se pudo encontrar o acceder al archivo de registro de errores.</p>";
                
                // Intentar crear un archivo temporal para probar el registro de errores
                $temp_log = '/tmp/test_error_log_' . time() . '.log';
                if (error_log('Test error message', 3, $temp_log)) {
                    echo "<p>Se ha creado un archivo de registro temporal en: $temp_log</p>";
                    
                    // Intentar registrar un error relacionado con marcar_enviado.php
                    error_log('Test message for marcar_enviado.php', 3, $temp_log);
                    
                    // Mostrar el contenido del archivo temporal
                    if (file_exists($temp_log)) {
                        echo "<h4>Contenido del archivo de registro temporal:</h4>";
                        echo "<pre>" . htmlspecialchars(file_get_contents($temp_log)) . "</pre>";
                    }
                } else {
                    echo "<p class='red-text'>No se pudo crear un archivo de registro temporal.</p>";
                }
            }
            ?>
        </div>
        
        <div class="card">
            <h4>Verificar Permisos de Escritura</h4>
            <?php
            $directorios_a_verificar = [
                '/var/log',
                '/var/log/apache2',
                '/var/log/httpd',
                '/var/log/php',
                '/var/www/logs',
                '/var/www/html',
                '/var/www/html/tiendalex2',
                '/var/www/html/tiendalex2/logs',
                '/tmp'
            ];
            
            echo "<ul class='collection'>";
            foreach ($directorios_a_verificar as $dir) {
                if (file_exists($dir)) {
                    $es_escribible = is_writable($dir);
                    $clase = $es_escribible ? 'green-text' : 'red-text';
                    $estado = $es_escribible ? 'Escribible' : 'No escribible';
                    
                    echo "<li class='collection-item'><span class='$clase'>$dir: $estado</span></li>";
                } else {
                    echo "<li class='collection-item grey-text'>$dir: No existe</li>";
                }
            }
            echo "</ul>";
            ?>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
