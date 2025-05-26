/**
 * Script para aplicar directamente el color de disponibilidad a los botones en el catálogo
 * Este script se ejecuta después de cargar la página y aplica el color directamente a los elementos
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Aplicando corrección directa a botones de disponibilidad en catálogo');
    
    // Función para aplicar el color directamente a los botones
    function aplicarColorDirecto() {
        // Obtener el color de disponibilidad desde localStorage o variables CSS
        let availabilityColor = localStorage.getItem('forced_availability_color');
        
        if (!availabilityColor) {
            const computedStyle = getComputedStyle(document.documentElement);
            availabilityColor = computedStyle.getPropertyValue('--availability-color').trim() || '#f1c40f';
        }
        
        // Si el color está vacío, usar el valor predeterminado
        if (!availabilityColor || availabilityColor === '') {
            availabilityColor = '#f1c40f';
        }
        
        console.log('Color de disponibilidad para aplicación directa:', availabilityColor);
        
        // Seleccionar todos los botones de disponibilidad
        const availabilityButtons = document.querySelectorAll('.stock-container .btn[data-is-available="true"], .availability-btn, button.availability-btn');
        
        console.log('Botones encontrados para aplicación directa:', availabilityButtons.length);
        
        // Aplicar el color directamente a cada botón
        availabilityButtons.forEach(button => {
            button.style.backgroundColor = availabilityColor + ' !important';
            button.setAttribute('style', `background-color: ${availabilityColor} !important; color: white !important;`);
            console.log('Color aplicado directamente a botón:', button);
        });
    }
    
    // Aplicar el color inmediatamente
    aplicarColorDirecto();
    
    // Aplicar el color después de un breve retraso
    setTimeout(aplicarColorDirecto, 500);
    
    // Aplicar el color después de un retraso más largo
    setTimeout(aplicarColorDirecto, 2000);
    
    // Configurar un intervalo para aplicar el color periódicamente
    setInterval(aplicarColorDirecto, 5000);
    
    // Observar cambios en el DOM para aplicar el color a nuevos elementos
    const observer = new MutationObserver(function(mutations) {
        aplicarColorDirecto();
    });
    
    // Observar cambios en todo el documento
    observer.observe(document.body, { 
        childList: true, 
        subtree: true,
        attributes: true,
        attributeFilter: ['style', 'class']
    });
});
