/**
 * Script global para forzar la aplicación del color de disponibilidad
 * Este script se ejecuta en todas las páginas para asegurar que el color se aplique correctamente
 */

(function() {
    'use strict';
    
    console.log('Iniciando script global para color de disponibilidad...');
    
    // Función para obtener el color de disponibilidad desde múltiples fuentes
    function obtenerColorDisponibilidad() {
        // 1. Prioridad máxima: localStorage (configurado desde admin)
        const forcedColor = localStorage.getItem('forced_availability_color');
        if (forcedColor && forcedColor.match(/^#[0-9A-Fa-f]{6}$/)) {
            console.log('Color obtenido desde localStorage:', forcedColor);
            return forcedColor;
        }
        
        // 2. Variable CSS del tema dinámico
        const computedStyle = getComputedStyle(document.documentElement);
        const cssColor = computedStyle.getPropertyValue('--availability-color').trim();
        if (cssColor && cssColor !== '') {
            console.log('Color obtenido desde CSS:', cssColor);
            return cssColor;
        }
        
        // 3. Fallback: color predeterminado
        console.log('Usando color predeterminado');
        return '#f1c40f';
    }
    
    // Función para aplicar el color de disponibilidad
    function aplicarColorDisponibilidad(forzar = false) {
        const color = obtenerColorDisponibilidad();
        
        if (forzar) {
            console.log('Aplicando color de disponibilidad (forzado):', color);
        }
        
        // Actualizar variable CSS
        document.documentElement.style.setProperty('--availability-color', color);
        
        // Crear o actualizar hoja de estilo global
        let styleElement = document.getElementById('global-availability-style');
        if (!styleElement) {
            styleElement = document.createElement('style');
            styleElement.id = 'global-availability-style';
            styleElement.setAttribute('data-priority', 'high');
            document.head.appendChild(styleElement);
        }
        
        // CSS con alta especificidad para forzar la aplicación
        styleElement.textContent = `
            /* Estilos globales para botones de disponibilidad */
            html body .stock-container .btn:not(.red):not([class*="agotado"]),
            html body .btn.availability-btn:not(.red),
            html body button.availability-btn:not(.red),
            html body [data-is-available="true"]:not(.red),
            html body .producto-item .stock-container button.btn:not(.red),
            html body .card .stock-container .btn:not(.red) {
                background-color: ${color} !important;
                color: white !important;
                border: none !important;
            }
            
            /* Hover effects */
            html body .stock-container .btn:not(.red):hover,
            html body .btn.availability-btn:not(.red):hover,
            html body button.availability-btn:not(.red):hover,
            html body [data-is-available="true"]:not(.red):hover {
                background-color: ${color} !important;
                filter: brightness(1.1) !important;
            }
            
            /* Asegurar que los botones agotados permanezcan rojos */
            html body .btn.red,
            html body .btn[class*="agotado"],
            html body .btn:contains("AGOTADO"),
            html body .btn:contains("agotado") {
                background-color: #f44336 !important;
                color: white !important;
            }
        `;
        
        // Aplicar estilos inline como respaldo
        const availabilityButtons = document.querySelectorAll(`
            .stock-container .btn:not(.red),
            .btn.availability-btn:not(.red),
            button.availability-btn:not(.red),
            [data-is-available="true"]:not(.red)
        `);
        
        availabilityButtons.forEach(button => {
            // Verificar que no sea un botón agotado
            const text = button.textContent.toLowerCase();
            if (!text.includes('agotado') && !button.classList.contains('red')) {
                button.style.setProperty('background-color', color, 'important');
                button.style.setProperty('color', 'white', 'important');
                
                if (forzar) {
                    button.setAttribute('data-availability-color', color);
                }
            }
        });
        
        return availabilityButtons.length;
    }
    
    // Función para manejar cambios en el tema
    function manejarCambioTema() {
        console.log('Detectado cambio en el tema, aplicando color de disponibilidad...');
        setTimeout(() => aplicarColorDisponibilidad(true), 100);
    }
    
    // Función de inicialización
    function inicializar() {
        console.log('Inicializando aplicación global de color de disponibilidad...');
        
        // Aplicar inmediatamente
        const buttonsFound = aplicarColorDisponibilidad(true);
        console.log(`Botones de disponibilidad encontrados: ${buttonsFound}`);
        
        // Aplicar después de un retraso para elementos cargados dinámicamente
        setTimeout(() => aplicarColorDisponibilidad(true), 1000);
        setTimeout(() => aplicarColorDisponibilidad(false), 3000);
        
        // Observador de mutaciones para elementos dinámicos
        const observer = new MutationObserver(function(mutations) {
            let shouldUpdate = false;
            
            mutations.forEach(mutation => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) { // Element node
                            if (node.matches && (
                                node.matches('.btn') ||
                                node.matches('.stock-container') ||
                                node.querySelector('.btn') ||
                                node.querySelector('.stock-container')
                            )) {
                                shouldUpdate = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldUpdate) {
                setTimeout(() => aplicarColorDisponibilidad(false), 100);
            }
        });
        
        // Observar cambios en el DOM
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: false
        });
        
        // Escuchar eventos de storage para cambios de tema
        window.addEventListener('storage', function(e) {
            if (e.key === 'theme_updated' || e.key === 'forced_availability_color') {
                manejarCambioTema();
            }
        });
        
        // Escuchar eventos personalizados
        window.addEventListener('themeUpdated', manejarCambioTema);
        window.addEventListener('availabilityColorChanged', manejarCambioTema);
        
        console.log('Script global de color de disponibilidad inicializado correctamente');
    }
    
    // Exponer funciones globalmente para debugging
    window.availabilityColorManager = {
        aplicar: aplicarColorDisponibilidad,
        obtenerColor: obtenerColorDisponibilidad,
        forzarActualizacion: () => aplicarColorDisponibilidad(true)
    };
    
    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        inicializar();
    }
    
    // También inicializar cuando la ventana esté completamente cargada
    window.addEventListener('load', function() {
        setTimeout(() => aplicarColorDisponibilidad(true), 500);
    });
    
})();
