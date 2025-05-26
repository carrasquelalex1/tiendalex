<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Color de Disponibilidad</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <style>
        .diagnostico-container {
            padding: 20px;
            margin: 20px 0;
        }
        .test-button {
            margin: 10px;
            padding: 10px 20px;
        }
        .color-box {
            width: 50px;
            height: 50px;
            display: inline-block;
            margin: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .info-box {
            background: #f5f5f5;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #2196F3;
        }
        .error-box {
            background: #ffebee;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #f44336;
        }
        .success-box {
            background: #e8f5e8;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #4caf50;
        }
    </style>
</head>
<body>
    <?php include 'frontend/includes/header.php'; ?>
    
    <main>
        <div class="container">
            <h1>Diagnóstico del Color de Disponibilidad</h1>
            
            <div class="diagnostico-container">
                <h3>1. Verificación de Base de Datos</h3>
                <div id="db-check">
                    <?php
                    require_once 'backend/config/db.php';
                    
                    $sql = "SELECT valor_configuracion FROM configuracion_tema WHERE nombre_configuracion = 'availability_color'";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $availability_color = $row['valor_configuracion'];
                        echo "<div class='success-box'>";
                        echo "✅ Color de disponibilidad encontrado en BD: <strong>$availability_color</strong>";
                        echo "<div class='color-box' style='background-color: $availability_color;'></div>";
                        echo "</div>";
                    } else {
                        echo "<div class='error-box'>";
                        echo "❌ Color de disponibilidad NO encontrado en la base de datos";
                        echo "</div>";
                    }
                    ?>
                </div>
                
                <h3>2. Verificación de CSS Dinámico</h3>
                <div id="css-check">
                    <button class="btn test-button" onclick="verificarCSS()">Verificar CSS</button>
                    <div id="css-result"></div>
                </div>
                
                <h3>3. Botones de Prueba</h3>
                <div class="info-box">
                    <p>Estos botones deberían tener el color de disponibilidad configurado:</p>
                    
                    <!-- Botón normal de disponibilidad -->
                    <div class="stock-container">
                        <button class="btn">DISPONIBLE (10)</button>
                    </div>
                    
                    <!-- Botón con clase específica -->
                    <button class="btn availability-btn">DISPONIBLE (5)</button>
                    
                    <!-- Botón con atributo data -->
                    <button class="btn" data-is-available="true">DISPONIBLE (8)</button>
                    
                    <!-- Botón agotado (debería ser rojo) -->
                    <button class="btn red">AGOTADO</button>
                </div>
                
                <h3>4. Aplicar Color Manualmente</h3>
                <div>
                    <input type="color" id="color-picker" value="#f1c40f">
                    <button class="btn" onclick="aplicarColorManual()">Aplicar Color</button>
                    <button class="btn" onclick="resetearColor()">Resetear</button>
                </div>
                
                <h3>5. Información del Sistema</h3>
                <div id="system-info">
                    <button class="btn test-button" onclick="mostrarInfo()">Mostrar Información</button>
                    <div id="info-result"></div>
                </div>
                
                <h3>6. Forzar Actualización</h3>
                <div>
                    <button class="btn orange" onclick="forzarActualizacion()">Forzar Actualización del Tema</button>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'frontend/includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/availability_fix_optimized.js"></script>
    
    <script>
        function verificarCSS() {
            const result = document.getElementById('css-result');
            const computedStyle = getComputedStyle(document.documentElement);
            const availabilityColor = computedStyle.getPropertyValue('--availability-color').trim();
            
            result.innerHTML = `
                <div class="info-box">
                    <strong>Variable CSS --availability-color:</strong> ${availabilityColor || 'No definida'}<br>
                    ${availabilityColor ? `<div class="color-box" style="background-color: ${availabilityColor};"></div>` : ''}
                </div>
            `;
        }
        
        function aplicarColorManual() {
            const color = document.getElementById('color-picker').value;
            
            // Aplicar a todos los botones de disponibilidad
            const buttons = document.querySelectorAll('.stock-container .btn:not(.red), .availability-btn, [data-is-available="true"]');
            buttons.forEach(btn => {
                btn.style.backgroundColor = color;
                btn.style.color = 'white';
            });
            
            // Actualizar variable CSS
            document.documentElement.style.setProperty('--availability-color', color);
            
            // Guardar en localStorage
            localStorage.setItem('forced_availability_color', color);
            
            M.toast({html: `Color aplicado: ${color}`, classes: 'rounded green'});
        }
        
        function resetearColor() {
            // Limpiar estilos inline
            const buttons = document.querySelectorAll('.stock-container .btn:not(.red), .availability-btn, [data-is-available="true"]');
            buttons.forEach(btn => {
                btn.style.backgroundColor = '';
                btn.style.color = '';
            });
            
            // Limpiar localStorage
            localStorage.removeItem('forced_availability_color');
            
            // Recargar CSS dinámico
            const link = document.querySelector('link[href*="dynamic_theme.php"]');
            if (link) {
                const href = link.getAttribute('href').split('?')[0];
                link.setAttribute('href', href + '?v=' + new Date().getTime());
            }
            
            M.toast({html: 'Color reseteado', classes: 'rounded blue'});
        }
        
        function mostrarInfo() {
            const result = document.getElementById('info-result');
            const savedColor = localStorage.getItem('forced_availability_color');
            const timestamp = localStorage.getItem('forced_availability_timestamp');
            
            result.innerHTML = `
                <div class="info-box">
                    <strong>LocalStorage:</strong><br>
                    - forced_availability_color: ${savedColor || 'No definido'}<br>
                    - forced_availability_timestamp: ${timestamp || 'No definido'}<br>
                    <br>
                    <strong>URL actual:</strong> ${window.location.href}<br>
                    <strong>User Agent:</strong> ${navigator.userAgent}
                </div>
            `;
        }
        
        function forzarActualizacion() {
            // Actualizar timestamp del tema
            localStorage.setItem('theme_updated', Date.now());
            
            // Recargar CSS dinámico
            const link = document.querySelector('link[href*="dynamic_theme.php"]');
            if (link) {
                const href = link.getAttribute('href').split('?')[0];
                link.setAttribute('href', href + '?v=' + new Date().getTime());
            }
            
            // Forzar aplicación del color
            setTimeout(() => {
                if (typeof aplicarColorDisponibilidad === 'function') {
                    aplicarColorDisponibilidad(true);
                }
            }, 500);
            
            M.toast({html: 'Tema actualizado forzosamente', classes: 'rounded orange'});
        }
        
        // Verificar CSS automáticamente al cargar
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(verificarCSS, 1000);
        });
    </script>
</body>
</html>
