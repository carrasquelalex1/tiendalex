/**
 * Script para mejorar la funcionalidad de la página de configuración del tema
 * Este script asegura que los cambios en el color de disponibilidad se apliquen correctamente
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando mejoras para la página de configuración del tema');

    // Verificar si estamos en la página de configuración del tema
    if (window.location.pathname.includes('admin_tema.php')) {
        // Agregar evento al botón de guardar configuración
        const guardarBtn = document.querySelector('button[name="guardar_tema"]');
        if (guardarBtn) {
            console.log('Detectado botón de guardar configuración, agregando evento');
            
            // Agregar un evento de clic al botón
            guardarBtn.addEventListener('click', function() {
                console.log('Botón de guardar configuración clickeado');
                
                // Obtener el color de disponibilidad seleccionado
                const availabilityColor = document.getElementById('availability_color').value;
                console.log('Color de disponibilidad seleccionado:', availabilityColor);
                
                // Guardar el color en localStorage para asegurar que se aplique
                localStorage.setItem('last_availability_color', availabilityColor);
                
                // Mostrar mensaje informativo
                setTimeout(function() {
                    M.toast({
                        html: '<i class="material-icons left">info</i> Aplicando color de disponibilidad...',
                        classes: 'rounded blue',
                        displayLength: 3000
                    });
                }, 500);
            });
        }
        
        // Mejorar la vista previa para mostrar el color de disponibilidad
        const updatePreviewOriginal = window.updatePreview;
        if (typeof updatePreviewOriginal === 'function') {
            window.updatePreview = function() {
                // Llamar a la función original
                updatePreviewOriginal();
                
                // Actualizar específicamente el botón de disponibilidad en la vista previa
                const availabilityColor = $("#availability_color").spectrum("get").toHexString();
                $("#preview-availability").css("background-color", availabilityColor);
                
                console.log('Vista previa actualizada con color de disponibilidad:', availabilityColor);
            };
        }
    }
});

// Función para aplicar el color de disponibilidad después de guardar
function aplicarColorDespuesDeGuardar() {
    // Verificar si hay un color guardado en localStorage
    const savedColor = localStorage.getItem('last_availability_color');
    if (savedColor) {
        console.log('Aplicando color guardado:', savedColor);
        
        // Crear o actualizar una variable CSS personalizada
        document.documentElement.style.setProperty('--availability-color', savedColor);
        
        // Aplicar el color a todos los botones de disponibilidad
        const availabilityButtons = document.querySelectorAll('.availability-btn, button.availability-btn, .stock-container .btn:not(.red), .producto-item .stock-container button.btn:not(.red)');
        
        availabilityButtons.forEach(button => {
            if (!button.classList.contains('red') && !button.textContent.includes('Agotado')) {
                button.style.backgroundColor = savedColor + ' !important';
            }
        });
        
        // Limpiar el localStorage después de aplicar
        localStorage.removeItem('last_availability_color');
    }
}

// Ejecutar después de que se complete la carga de la página
window.addEventListener('load', function() {
    setTimeout(aplicarColorDespuesDeGuardar, 500);
});
