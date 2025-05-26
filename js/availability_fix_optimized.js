/**
 * Script optimizado para corregir los botones de disponibilidad
 * Versión unificada y optimizada para mejorar el rendimiento
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Iniciando corrección optimizada para botones de disponibilidad...');
    
    // Función para obtener el color de disponibilidad
    function obtenerColorDisponibilidad() {
        // Primero intentar obtener desde localStorage (mayor prioridad)
        const savedColor = localStorage.getItem('forced_availability_color');
        if (savedColor) {
            return savedColor;
        }
        
        // Si no hay color guardado, obtener desde variables CSS
        const computedStyle = getComputedStyle(document.documentElement);
        let availabilityColor = computedStyle.getPropertyValue('--availability-color').trim();
        
        // Si el color está vacío, usar el valor predeterminado
        if (!availabilityColor || availabilityColor === '') {
            return '#f1c40f'; // Color amarillo por defecto
        }
        
        return availabilityColor;
    }
    
    // Función optimizada para aplicar el color a los botones de disponibilidad
    function aplicarColorDisponibilidad(forzar = false) {
        const availabilityColor = obtenerColorDisponibilidad();
        
        // Solo registrar en consola si es forzado para reducir la salida
        if (forzar) {
            console.log('Aplicando color de disponibilidad:', availabilityColor);
        }
        
        // Seleccionar botones con un solo selector optimizado
        const availabilityButtons = document.querySelectorAll('.stock-container .btn:not(.red), [data-is-available="true"]');
        
        // Aplicar el color solo a los botones que lo necesitan
        availabilityButtons.forEach(button => {
            if (!button.classList.contains('red') && !button.textContent.includes('Agotado')) {
                button.style.backgroundColor = availabilityColor;
                // Solo usar setAttribute si es forzado para reducir operaciones DOM
                if (forzar) {
                    button.setAttribute('style', `background-color: ${availabilityColor} !important; color: white !important;`);
                }
            }
        });
        
        // Actualizar o crear la hoja de estilo dinámica
        let styleSheet = document.getElementById('availability-styles');
        if (!styleSheet) {
            styleSheet = document.createElement('style');
            styleSheet.id = 'availability-styles';
            document.head.appendChild(styleSheet);
        }
        
        // Usar menos selectores pero más específicos
        styleSheet.textContent = `
            html body .stock-container .btn:not(.red),
            html body [data-is-available="true"]:not(.red) {
                background-color: ${availabilityColor} !important;
                color: white !important;
            }
        `;
    }
    
    // Aplicar el color inmediatamente
    aplicarColorDisponibilidad(true);
    
    // Aplicar una vez más después de un breve retraso para capturar elementos cargados dinámicamente
    setTimeout(() => aplicarColorDisponibilidad(true), 1000);
    
    // Observador de mutaciones optimizado con throttling
    let pendingMutations = false;
    const observer = new MutationObserver(function() {
        if (!pendingMutations) {
            pendingMutations = true;
            setTimeout(() => {
                aplicarColorDisponibilidad();
                pendingMutations = false;
            }, 200);
        }
    });
    
    // Observar solo el contenedor principal con opciones limitadas
    const mainContainer = document.querySelector('main') || document.body;
    observer.observe(mainContainer, { 
        childList: true, 
        subtree: true,
        attributes: false
    });
    
    // Escuchar cambios en el tema con throttling
    let pendingStorageEvent = false;
    window.addEventListener('storage', function(e) {
        if ((e.key === 'theme_updated' || e.key === 'forced_availability_color') && !pendingStorageEvent) {
            pendingStorageEvent = true;
            setTimeout(() => {
                console.log('Tema actualizado, aplicando corrección...');
                aplicarColorDisponibilidad(true);
                pendingStorageEvent = false;
            }, 200);
        }
    });
    
    // Si estamos en la página de administración del tema, agregar evento al botón de guardar
    if (window.location.pathname.includes('admin_tema.php')) {
        const guardarBtn = document.getElementById('guardar_tema_btn');
        if (guardarBtn) {
            guardarBtn.addEventListener('click', function() {
                const availabilityColorInput = document.getElementById('availability_color');
                if (availabilityColorInput) {
                    const color = availabilityColorInput.value;
                    localStorage.setItem('forced_availability_color', color);
                    localStorage.setItem('forced_availability_timestamp', Date.now());
                    console.log('Color de disponibilidad guardado:', color);
                }
            });
        }
    }
});
