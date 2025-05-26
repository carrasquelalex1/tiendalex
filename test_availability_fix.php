<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Color de Disponibilidad</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
</head>
<body>
    <?php include 'frontend/includes/header.php'; ?>
    
    <main>
        <div class="container">
            <h1>Test del Color de Disponibilidad</h1>
            
            <div class="row">
                <div class="col s12 m6">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Producto de Prueba 1</span>
                            <p>Este es un producto de prueba para verificar el color de disponibilidad.</p>
                            
                            <div class="stock-container">
                                <button class="btn">DISPONIBLE (15)</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col s12 m6">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Producto de Prueba 2</span>
                            <p>Otro producto de prueba con botón de disponibilidad.</p>
                            
                            <div class="stock-container">
                                <button class="btn availability-btn">DISPONIBLE (8)</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col s12 m6">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Producto Agotado</span>
                            <p>Este producto debería mostrar el botón rojo.</p>
                            
                            <div class="stock-container">
                                <button class="btn red">AGOTADO</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col s12 m6">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Producto con Data Attribute</span>
                            <p>Producto usando data-is-available.</p>
                            
                            <div class="stock-container">
                                <button class="btn" data-is-available="true">DISPONIBLE (12)</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Controles de Prueba</span>
                            
                            <p>
                                <button class="btn blue" onclick="verificarColor()">Verificar Color Actual</button>
                                <button class="btn orange" onclick="forzarActualizacion()">Forzar Actualización</button>
                                <button class="btn green" onclick="aplicarColorManual()">Aplicar Color Manual</button>
                            </p>
                            
                            <div id="resultado" style="margin-top: 20px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'frontend/includes/footer.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="js/scripts.js"></script>
    
    <script>
        function verificarColor() {
            const resultado = document.getElementById('resultado');
            
            // Obtener color desde CSS
            const computedStyle = getComputedStyle(document.documentElement);
            const cssColor = computedStyle.getPropertyValue('--availability-color').trim();
            
            // Obtener color desde localStorage
            const savedColor = localStorage.getItem('forced_availability_color');
            
            // Verificar botones
            const buttons = document.querySelectorAll('.stock-container .btn:not(.red)');
            let buttonColors = [];
            buttons.forEach((btn, index) => {
                const bgColor = getComputedStyle(btn).backgroundColor;
                buttonColors.push(`Botón ${index + 1}: ${bgColor}`);
            });
            
            resultado.innerHTML = `
                <div class="card-panel blue lighten-4">
                    <h6>Información del Color de Disponibilidad:</h6>
                    <p><strong>Variable CSS:</strong> ${cssColor || 'No definida'}</p>
                    <p><strong>LocalStorage:</strong> ${savedColor || 'No definido'}</p>
                    <p><strong>Colores de botones:</strong></p>
                    <ul>
                        ${buttonColors.map(color => `<li>${color}</li>`).join('')}
                    </ul>
                </div>
            `;
        }
        
        function forzarActualizacion() {
            if (window.availabilityColorManager) {
                const applied = window.availabilityColorManager.forzarActualizacion();
                M.toast({html: 'Actualización forzada aplicada', classes: 'rounded green'});
            } else {
                M.toast({html: 'Manager no disponible', classes: 'rounded red'});
            }
        }
        
        function aplicarColorManual() {
            const color = '#e74c3c'; // Color rojo para prueba
            
            // Aplicar usando el manager global si está disponible
            if (window.availabilityColorManager) {
                localStorage.setItem('forced_availability_color', color);
                window.availabilityColorManager.forzarActualizacion();
                M.toast({html: `Color manual aplicado: ${color}`, classes: 'rounded orange'});
            } else {
                // Fallback manual
                const buttons = document.querySelectorAll('.stock-container .btn:not(.red)');
                buttons.forEach(btn => {
                    btn.style.backgroundColor = color;
                    btn.style.color = 'white';
                });
                M.toast({html: 'Color aplicado manualmente (fallback)', classes: 'rounded blue'});
            }
        }
        
        // Verificar automáticamente al cargar
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(verificarColor, 2000);
        });
    </script>
</body>
</html>
