<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Simple - Color de Disponibilidad</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
</head>
<body>
    <?php include 'frontend/includes/header.php'; ?>
    
    <main>
        <div class="container">
            <h1>Test Simple del Color de Disponibilidad</h1>
            
            <div class="row">
                <div class="col s12">
                    <h3>Estado de la Base de Datos</h3>
                    <?php
                    require_once 'backend/config/db.php';
                    
                    $sql = "SELECT valor_configuracion FROM configuracion_tema WHERE nombre_configuracion = 'availability_color'";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $availability_color = $row['valor_configuracion'];
                        echo "<p><strong>Color en BD:</strong> <span style='background-color: {$availability_color}; color: white; padding: 5px 10px; border-radius: 3px;'>{$availability_color}</span></p>";
                    } else {
                        echo "<p style='color: red;'>❌ Color no encontrado en la base de datos</p>";
                        $availability_color = '#f1c40f';
                    }
                    ?>
                </div>
            </div>
            
            <div class="row">
                <div class="col s12 m6">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Producto de Prueba 1</span>
                            <p>Botón con clase stock-container</p>
                            <div class="stock-container">
                                <button class="btn">DISPONIBLE (10)</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col s12 m6">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Producto de Prueba 2</span>
                            <p>Botón con clase availability-btn</p>
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
                            <span class="card-title">Producto Agotado</span>
                            <p>Este debe ser rojo</p>
                            <div class="stock-container">
                                <button class="btn red">AGOTADO</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col s12 m6">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title">Información CSS</span>
                            <p id="css-info">Cargando...</p>
                            <button class="btn blue" onclick="verificarCSS()">Verificar CSS</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col s12">
                    <p>
                        <a href="admin_tema.php" class="btn orange">Ir a Configuración del Tema</a>
                        <a href="catalogo.php" class="btn green">Ver Catálogo Real</a>
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
            const info = document.getElementById('css-info');
            
            // Obtener variable CSS
            const computedStyle = getComputedStyle(document.documentElement);
            const cssColor = computedStyle.getPropertyValue('--availability-color').trim();
            
            // Verificar botones
            const buttons = document.querySelectorAll('.stock-container .btn:not(.red)');
            let buttonColors = [];
            
            buttons.forEach((btn, index) => {
                const bgColor = getComputedStyle(btn).backgroundColor;
                buttonColors.push(`Botón ${index + 1}: ${bgColor}`);
            });
            
            info.innerHTML = `
                <strong>Variable CSS:</strong> ${cssColor || 'No definida'}<br>
                <strong>Botones encontrados:</strong> ${buttons.length}<br>
                <strong>Colores aplicados:</strong><br>
                ${buttonColors.map(color => `• ${color}`).join('<br>')}
            `;
        }
        
        // Verificar automáticamente al cargar
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(verificarCSS, 1000);
        });
    </script>
</body>
</html>
