<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico del Tema</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
</head>
<body>
    <?php include 'frontend/includes/header.php'; ?>
    
    <main>
        <div class="container">
            <h1>Diagnóstico del Sistema de Temas</h1>
            
            <div class="row">
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Estado de la Base de Datos</span>
                            
                            <?php
                            require_once 'backend/config/db.php';
                            
                            echo "<h5>Configuración actual del tema:</h5>";
                            $sql = "SELECT * FROM configuracion_tema ORDER BY nombre_configuracion";
                            $result = $conn->query($sql);
                            
                            if ($result && $result->num_rows > 0) {
                                echo "<table class='striped'>";
                                echo "<thead><tr><th>Configuración</th><th>Valor</th><th>Fecha</th></tr></thead>";
                                echo "<tbody>";
                                while ($row = $result->fetch_assoc()) {
                                    $color_preview = '';
                                    if (strpos($row['nombre_configuracion'], 'color') !== false) {
                                        $color_preview = "<span style='display:inline-block;width:20px;height:20px;background-color:{$row['valor_configuracion']};border:1px solid #ccc;margin-right:10px;'></span>";
                                    }
                                    echo "<tr>";
                                    echo "<td>{$row['nombre_configuracion']}</td>";
                                    echo "<td>{$color_preview}{$row['valor_configuracion']}</td>";
                                    echo "<td>{$row['fecha_actualizacion']}</td>";
                                    echo "</tr>";
                                }
                                echo "</tbody></table>";
                            } else {
                                echo "<p class='red-text'>❌ No se encontró configuración del tema</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col s12 m6">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Test de CSS Dinámico</span>
                            <p><strong>URL del CSS:</strong> <a href="css/dynamic_theme.php" target="_blank">css/dynamic_theme.php</a></p>
                            <button class="btn blue" onclick="verificarCSS()">Verificar CSS</button>
                            <div id="css-result" style="margin-top: 10px;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="col s12 m6">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Test de Botones de Disponibilidad</span>
                            <div class="stock-container">
                                <button class="btn">DISPONIBLE (Test 1)</button>
                            </div>
                            <div class="stock-container" style="margin-top: 10px;">
                                <button class="btn availability-btn">DISPONIBLE (Test 2)</button>
                            </div>
                            <div class="stock-container" style="margin-top: 10px;">
                                <button class="btn red">AGOTADO (Test 3)</button>
                            </div>
                            <button class="btn orange" onclick="aplicarColorManual()">Aplicar Color Manual</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Test de Comunicación entre Pestañas</span>
                            <button class="btn green" onclick="simularActualizacion()">Simular Actualización de Tema</button>
                            <button class="btn purple" onclick="verificarEventos()">Verificar Eventos</button>
                            <div id="eventos-result" style="margin-top: 10px;"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col s12">
                    <p>
                        <a href="admin_tema.php" class="btn orange">Ir a Configuración del Tema</a>
                        <a href="catalogo.php" class="btn green">Ver Catálogo</a>
                    </p>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'frontend/includes/footer.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="js/scripts.js"></script>
    
    <script>
        function verificarCSS() {
            const result = document.getElementById('css-result');
            
            // Obtener variable CSS
            const computedStyle = getComputedStyle(document.documentElement);
            const availabilityColor = computedStyle.getPropertyValue('--availability-color').trim();
            const primaryColor = computedStyle.getPropertyValue('--primary-color').trim();
            
            // Verificar si el CSS dinámico está cargado
            const linkElement = document.querySelector('link[href*="dynamic_theme.php"]');
            
            result.innerHTML = `
                <div class="card-panel blue lighten-4">
                    <strong>Variables CSS detectadas:</strong><br>
                    --availability-color: <span style="background-color: ${availabilityColor}; color: white; padding: 2px 8px;">${availabilityColor || 'No definida'}</span><br>
                    --primary-color: <span style="background-color: ${primaryColor}; color: white; padding: 2px 8px;">${primaryColor || 'No definida'}</span><br>
                    <strong>CSS dinámico:</strong> ${linkElement ? '✅ Cargado' : '❌ No encontrado'}<br>
                    <strong>URL:</strong> ${linkElement ? linkElement.href : 'N/A'}
                </div>
            `;
        }
        
        function aplicarColorManual() {
            const computedStyle = getComputedStyle(document.documentElement);
            const availabilityColor = computedStyle.getPropertyValue('--availability-color').trim();
            
            if (availabilityColor) {
                document.querySelectorAll('.stock-container .btn:not(.red)').forEach(btn => {
                    btn.style.setProperty('background-color', availabilityColor, 'important');
                    btn.style.setProperty('color', 'white', 'important');
                });
                M.toast({html: 'Color aplicado manualmente: ' + availabilityColor, classes: 'green'});
            } else {
                M.toast({html: 'No se encontró color de disponibilidad', classes: 'red'});
            }
        }
        
        function simularActualizacion() {
            localStorage.setItem('theme_updated', Date.now());
            window.dispatchEvent(new CustomEvent('themeUpdated', {
                detail: { timestamp: Date.now() }
            }));
            M.toast({html: 'Actualización simulada', classes: 'blue'});
        }
        
        function verificarEventos() {
            const result = document.getElementById('eventos-result');
            
            // Verificar si el script global está cargado
            const hasGlobalUpdater = typeof window.themeUpdater !== 'undefined';
            
            // Verificar localStorage
            const lastUpdate = localStorage.getItem('theme_updated');
            
            result.innerHTML = `
                <div class="card-panel orange lighten-4">
                    <strong>Estado de eventos:</strong><br>
                    Script global: ${hasGlobalUpdater ? '✅ Cargado' : '❌ No encontrado'}<br>
                    Última actualización: ${lastUpdate ? new Date(parseInt(lastUpdate)).toLocaleString() : 'Nunca'}<br>
                    ${hasGlobalUpdater ? '<button class="btn small" onclick="window.themeUpdater.actualizar()">Forzar Actualización</button>' : ''}
                </div>
            `;
        }
        
        // Verificar automáticamente al cargar
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(verificarCSS, 1000);
            setTimeout(verificarEventos, 1000);
        });
        
        // Escuchar eventos de actualización
        window.addEventListener('storage', function(e) {
            if (e.key === 'theme_updated') {
                M.toast({html: 'Detectada actualización de tema via localStorage', classes: 'blue'});
            }
        });
        
        window.addEventListener('themeUpdated', function() {
            M.toast({html: 'Detectada actualización de tema via CustomEvent', classes: 'purple'});
        });
    </script>
</body>
</html>
