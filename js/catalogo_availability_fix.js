/**
 * Script específico para corregir los botones de disponibilidad en el catálogo
 * Este script se ejecuta después de cargar la página de catálogo para asegurar que los botones
 * de disponibilidad muestren el color correcto
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Aplicando corrección específica para botones de disponibilidad en catálogo...');

    // Función para aplicar el color a los botones de disponibilidad
    function aplicarColorDisponibilidadCatalogo() {
        // Obtener el color de disponibilidad desde las variables CSS
        const computedStyle = getComputedStyle(document.documentElement);
        let availabilityColor = computedStyle.getPropertyValue('--availability-color').trim() || '#f1c40f';
        
        // Si el color está vacío, usar el valor predeterminado
        if (!availabilityColor || availabilityColor === '') {
            availabilityColor = '#f1c40f';
        }

        console.log('Color de disponibilidad para catálogo:', availabilityColor);

        // Seleccionar específicamente los botones de disponibilidad en el catálogo
        const availabilityButtons = document.querySelectorAll('.producto-item .stock-container button.btn:not(.red)');

        availabilityButtons.forEach(button => {
            // Verificar si es un botón de disponibilidad (no agotado)
            if (!button.classList.contains('red') && !button.textContent.includes('Agotado')) {
                // Aplicar el color con !important para mayor prioridad
                button.setAttribute('style', `background-color: ${availabilityColor} !important; color: white !important;`);
                console.log('Aplicando color a botón de catálogo:', button);
            }
        });

        // Crear o actualizar una hoja de estilo dinámica específica para el catálogo
        let styleSheet = document.getElementById('catalogo-availability-styles');
        if (!styleSheet) {
            styleSheet = document.createElement('style');
            styleSheet.id = 'catalogo-availability-styles';
            document.head.appendChild(styleSheet);
        }

        // Usar selectores muy específicos para aumentar la prioridad
        styleSheet.textContent = `
            .producto-item .stock-container button.btn:not(.red),
            .producto-item .stock-container .btn:not(.red),
            body .producto-item .stock-container button.btn:not(.red),
            body .producto-item .stock-container .btn:not(.red),
            html body .producto-item .stock-container button.btn:not(.red),
            html body .producto-item .stock-container .btn:not(.red) {
                background-color: ${availabilityColor} !important;
                color: white !important;
            }
        `;
    }

    // Aplicar el color inmediatamente
    aplicarColorDisponibilidadCatalogo();

    // Aplicar el color cada vez que se cargue un nuevo producto o se actualice la página
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                aplicarColorDisponibilidadCatalogo();
            }
        });
    });

    // Observar cambios en el contenedor de productos
    const productosContainer = document.querySelector('.row');
    if (productosContainer) {
        observer.observe(productosContainer, { childList: true, subtree: true });
    }

    // Escuchar cambios en el tema
    window.addEventListener('storage', function(e) {
        if (e.key === 'theme_updated') {
            console.log('Tema actualizado, aplicando corrección específica para catálogo...');
            setTimeout(aplicarColorDisponibilidadCatalogo, 100);
        }
    });
});
