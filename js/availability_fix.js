/**
 * Script para forzar la actualización de los botones de disponibilidad
 * Este script se ejecuta después de cargar la página para asegurar que los botones
 * de disponibilidad muestren el color correcto
 */

// Función para aplicar el color de disponibilidad a todos los botones
function aplicarColorDisponibilidad(forceRefresh = false) {
    console.log('Aplicando corrección de disponibilidad...');

    // Obtener el color de disponibilidad desde las variables CSS
    const computedStyle = getComputedStyle(document.documentElement);
    let availabilityColor = computedStyle.getPropertyValue('--availability-color').trim() || '#f1c40f';

    // Si el color está vacío, usar el valor predeterminado
    if (!availabilityColor || availabilityColor === '') {
        availabilityColor = '#f1c40f';
    }

    console.log('Color de disponibilidad detectado:', availabilityColor);

    // Aplicar el color a todos los botones de disponibilidad con alta especificidad
    const availabilityButtons = document.querySelectorAll('.availability-btn, button.availability-btn, .stock-container .btn, .stock-container button.btn, [class*="availability-btn"], .producto-item .stock-container button.btn:not(.red)');

    availabilityButtons.forEach(button => {
        // Verificar si es un botón de disponibilidad (no agotado)
        if (!button.classList.contains('red') && !button.textContent.includes('Agotado')) {
            // Aplicar el color con !important para mayor prioridad
            button.setAttribute('style', `background-color: ${availabilityColor} !important; color: white !important;`);
            console.log('Aplicando color a botón:', button);
        }
    });

    // Forzar un reflow para aplicar los estilos
    document.querySelectorAll('.stock-container').forEach(container => {
        container.style.display = container.style.display;
    });

    // Crear o actualizar una hoja de estilo dinámica para mayor especificidad
    let styleSheet = document.getElementById('dynamic-availability-styles');
    if (!styleSheet) {
        styleSheet = document.createElement('style');
        styleSheet.id = 'dynamic-availability-styles';
        document.head.appendChild(styleSheet);
    }

    // Usar selectores muy específicos para aumentar la prioridad
    styleSheet.textContent = `
        .stock-container .btn:not(.red),
        button.availability-btn,
        .btn.availability-btn,
        .producto-item .stock-container button.btn:not(.red),
        [class*="availability-btn"]:not(.red),
        .availability-btn,
        button.btn.availability-btn,
        button.availability-btn,
        .stock-container .btn.availability-btn,
        .stock-container button.btn:not(.red),
        .producto-item .stock-container button.btn:not(.red),
        body .stock-container .btn:not(.red),
        body button.availability-btn,
        body .btn.availability-btn,
        body .producto-item .stock-container button.btn:not(.red),
        body [class*="availability-btn"]:not(.red) {
            background-color: ${availabilityColor} !important;
            color: white !important;
        }
    `;

    // Si se solicita una actualización forzada, recargar el CSS dinámico
    if (forceRefresh) {
        const linkElement = document.querySelector('link[href*="dynamic_theme.php"]');
        if (linkElement) {
            const href = linkElement.getAttribute('href').split('?')[0];
            linkElement.setAttribute('href', href + '?v=' + new Date().getTime());
        }
    }

    console.log('Corrección de disponibilidad aplicada');
}

// Aplicar el color al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    aplicarColorDisponibilidad();

    // Verificar si estamos en la página de configuración del tema
    if (window.location.pathname.includes('admin_tema.php')) {
        // Agregar evento al botón de guardar configuración
        const guardarBtn = document.querySelector('button[name="guardar_tema"]');
        if (guardarBtn) {
            console.log('Detectado botón de guardar configuración, agregando evento');
            guardarBtn.addEventListener('click', function() {
                console.log('Botón de guardar configuración clickeado');
                // Esperar a que se procese el formulario y se actualice el tema
                setTimeout(function() {
                    aplicarColorDisponibilidad(true);
                }, 1000);
            });
        }
    }
});

// Escuchar cambios en el tema
window.addEventListener('storage', function(e) {
    if (e.key === 'theme_updated') {
        console.log('Tema actualizado, aplicando corrección de disponibilidad...');

        // Esperar un momento para que se carguen los nuevos estilos
        setTimeout(function() {
            // Usar la función centralizada para aplicar los estilos
            aplicarColorDisponibilidad(true);
        }, 100);
    }
});
