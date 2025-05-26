<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Final - Color de Disponibilidad</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <style>
        .test-section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .color-display {
            width: 40px;
            height: 40px;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
            vertical-align: middle;
        }
        .status-ok { color: green; font-weight: bold; }
        .status-error { color: red; font-weight: bold; }
        .status-warning { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'frontend/includes/header.php'; ?>
    
    <main>
        <div class="container">
            <h1>Prueba Final del Color de Disponibilidad</h1>
            
            <div class="test-section">
                <h3>1. Estado de la Base de Datos</h3>
                <div id="db-status">
                    <?php
                    require_once 'backend/config/db.php';
                    
                    $sql = "SELECT valor_configuracion FROM configuracion_tema WHERE nombre_configuracion = 'availability_color'";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $db_color = $row['valor_configuracion'];
                        echo "<span class='status-ok'>✅ Color en BD:</span> ";
                        echo "<div class='color-display' style='background-color: {$db_color};'></div>";
                        echo "<strong>{$db_color}</strong>";
                    } else {
                        echo "<span class='status-error'>❌ Color no encontrado en la base de datos</span>";
                        $db_color = '#f1c40f';
                    }
                    ?>
                </div>
            </div>
            
            <div class="test-section">
                <h3>2. Archivos de Respaldo</h3>
                <div id="backup-status">
                    <?php
                    $css_backup = 'css/availability_color_backup.css';
                    $js_backup = 'js/availability_color_backup.js';
                    
                    echo "<p>";
                    if (file_exists($css_backup)) {
                        echo "<span class='status-ok'>✅ CSS de respaldo existe</span><br>";
                        $css_content = file_get_contents($css_backup);
                        if (strpos($css_content, $db_color) !== false) {
                            echo "<span class='status-ok'>✅ CSS contiene el color correcto</span>";
                        } else {
                            echo "<span class='status-warning'>⚠️ CSS no contiene el color actual</span>";
                        }
                    } else {
                        echo "<span class='status-error'>❌ CSS de respaldo no existe</span>";
                    }
                    echo "</p>";
                    
                    echo "<p>";
                    if (file_exists($js_backup)) {
                        echo "<span class='status-ok'>✅ JavaScript de respaldo existe</span><br>";
                        $js_content = file_get_contents($js_backup);
                        if (strpos($js_content, $db_color) !== false) {
                            echo "<span class='status-ok'>✅ JavaScript contiene el color correcto</span>";
                        } else {
                            echo "<span class='status-warning'>⚠️ JavaScript no contiene el color actual</span>";
                        }
                    } else {
                        echo "<span class='status-error'>❌ JavaScript de respaldo no existe</span>";
                    }
                    echo "</p>";
                    ?>
                </div>
            </div>
            
            <div class="test-section">
                <h3>3. Botones de Prueba</h3>
                <p>Estos botones deberían mostrar el color de disponibilidad configurado:</p>
                
                <div class="row">
                    <div class="col s12 m6">
                        <div class="card">
                            <div class="card-content">
                                <span class="card-title">Producto 1</span>
                                <div class="stock-container">
                                    <button class="btn">DISPONIBLE (10)</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col s12 m6">
                        <div class="card">
                            <div class="card-content">
                                <span class="card-title">Producto 2</span>
                                <div class="stock-container">
                                    <button class="btn availability-btn">DISPONIBLE (5)</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col s12 m6">
                        <div class="card">
                            <div class="card-content">
                                <span class="card-title">Producto 3</span>
                                <div class="stock-container">
                                    <button class="btn" data-is-available="true">DISPONIBLE (8)</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col s12 m6">
                        <div class="card">
                            <div class="card-content">
                                <span class="card-title">Producto Agotado</span>
                                <div class="stock-container">
                                    <button class="btn red">AGOTADO</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="test-section">
                <h3>4. Controles de Diagnóstico</h3>
                <p>
                    <button class="btn blue" onclick="verificarEstado()">Verificar Estado</button>
                    <button class="btn orange" onclick="forzarActualizacion()">Forzar Actualización</button>
                    <button class="btn green" onclick="regenerarArchivos()">Regenerar Archivos</button>
                </p>
                
                <div id="diagnostico-resultado" style="margin-top: 20px;"></div>
            </div>
            
            <div class="test-section">
                <h3>5. Enlaces de Navegación</h3>
                <p>
                    <a href="admin_tema.php" class="btn">Configuración del Tema</a>
                    <a href="catalogo.php" class="btn">Ver Catálogo</a>
                    <a href="diagnostico_availability.php" class="btn">Diagnóstico Completo</a>
                </p>
            </div>
        </div>
    </main>
    
    <?php include 'frontend/includes/footer.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="js/scripts.js"></script>
    
    <script>
        function verificarEstado() {
            const resultado = document.getElementById('diagnostico-resultado');
            
            // Obtener color desde CSS
            const computedStyle = getComputedStyle(document.documentElement);
            const cssColor = computedStyle.getPropertyValue('--availability-color').trim();
            
            // Obtener color desde localStorage
            const savedColor = localStorage.getItem('forced_availability_color');
            
            // Verificar botones
            const buttons = document.querySelectorAll('.stock-container .btn:not(.red)');
            let buttonInfo = [];
            buttons.forEach((btn, index) => {
                const bgColor = getComputedStyle(btn).backgroundColor;
                const computedColor = getComputedStyle(btn).getPropertyValue('background-color');
                buttonInfo.push({
                    index: index + 1,
                    text: btn.textContent.trim(),
                    backgroundColor: bgColor,
                    computedColor: computedColor
                });
            });
            
            resultado.innerHTML = `
                <div class="card-panel blue lighten-4">
                    <h6>Estado del Sistema:</h6>
                    <p><strong>Variable CSS --availability-color:</strong> ${cssColor || 'No definida'}</p>
                    <p><strong>LocalStorage forced_availability_color:</strong> ${savedColor || 'No definido'}</p>
                    <p><strong>Botones encontrados:</strong> ${buttons.length}</p>
                    <div style="margin-top: 15px;">
                        <h6>Detalles de botones:</h6>
                        ${buttonInfo.map(btn => `
                            <div style="margin: 5px 0; padding: 5px; background: #f5f5f5; border-radius: 3px;">
                                <strong>Botón ${btn.index}:</strong> "${btn.text}"<br>
                                <small>Color: ${btn.backgroundColor}</small>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        
        function forzarActualizacion() {
            // Usar el manager global si está disponible
            if (window.forceAvailabilityColor) {
                window.forceAvailabilityColor();
                M.toast({html: 'Actualización forzada aplicada', classes: 'rounded green'});
            } else if (window.availabilityColorManager) {
                window.availabilityColorManager.forzarActualizacion();
                M.toast({html: 'Manager global aplicado', classes: 'rounded green'});
            } else {
                M.toast({html: 'No hay managers disponibles', classes: 'rounded red'});
            }
            
            // Verificar estado después de la actualización
            setTimeout(verificarEstado, 1000);
        }
        
        function regenerarArchivos() {
            const dbColor = '<?php echo $db_color; ?>';
            
            fetch('backend/update_availability_color.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'availability_color=' + encodeURIComponent(dbColor)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    M.toast({html: 'Archivos regenerados correctamente', classes: 'rounded green'});
                    setTimeout(() => location.reload(), 2000);
                } else {
                    M.toast({html: 'Error: ' + data.error, classes: 'rounded red'});
                }
            })
            .catch(error => {
                M.toast({html: 'Error en la petición: ' + error, classes: 'rounded red'});
            });
        }
        
        // Verificar estado automáticamente al cargar
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(verificarEstado, 2000);
        });
    </script>
</body>
</html>
