/**
 * Script para actualizar globalmente el tema en todas las páginas
 * Este script escucha cambios en localStorage y actualiza el CSS dinámico
 */

(function() {
    'use strict';
    
    console.log('Iniciando actualizador global del tema...');
    
    // Función para recargar el CSS dinámico
    function recargarCSSDinamico() {
        const linkElement = document.querySelector('link[href*="dynamic_theme.php"]');
        if (linkElement) {
            const href = linkElement.getAttribute('href').split('?')[0];
            const newHref = href + '?v=' + new Date().getTime();
            linkElement.setAttribute('href', newHref);
            console.log('CSS dinámico recargado:', newHref);
            return true;
        }
        return false;
    }
    
    // Función para aplicar el color de disponibilidad inmediatamente
    function aplicarColorDisponibilidad() {
        // Obtener el color desde la variable CSS
        const computedStyle = getComputedStyle(document.documentElement);
        const availabilityColor = computedStyle.getPropertyValue('--availability-color').trim();
        
        if (availabilityColor) {
            console.log('Aplicando color de disponibilidad:', availabilityColor);
            
            // Aplicar a todos los botones de disponibilidad
            const selectors = [
                '.stock-container .btn:not(.red)',
                '.btn.availability-btn:not(.red)',
                'button.availability-btn:not(.red)',
                '[data-is-available="true"]:not(.red)',
                '.producto-item .stock-container button.btn:not(.red)',
                '.card .stock-container .btn:not(.red)'
            ];
            
            selectors.forEach(selector => {
                document.querySelectorAll(selector).forEach(btn => {
                    const text = btn.textContent.toLowerCase();
                    if (!text.includes('agotado') && !btn.classList.contains('red')) {
                        btn.style.setProperty('background-color', availabilityColor, 'important');
                        btn.style.setProperty('color', 'white', 'important');
                    }
                });
            });
        }
    }
    
    // Función para manejar la actualización del tema
    function manejarActualizacionTema() {
        console.log('Detectada actualización del tema, recargando...');
        
        // Recargar CSS dinámico
        if (recargarCSSDinamico()) {
            // Esperar un poco para que se cargue el nuevo CSS y luego aplicar el color
            setTimeout(() => {
                aplicarColorDisponibilidad();
            }, 500);
            
            // Aplicar una vez más después de un retraso mayor
            setTimeout(() => {
                aplicarColorDisponibilidad();
            }, 1500);
        }
    }
    
    // Escuchar cambios en localStorage
    window.addEventListener('storage', function(e) {
        if (e.key === 'theme_updated') {
            manejarActualizacionTema();
        }
    });
    
    // También escuchar el evento personalizado para la misma pestaña
    window.addEventListener('themeUpdated', function() {
        manejarActualizacionTema();
    });
    
    // Aplicar el color al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(aplicarColorDisponibilidad, 1000);
    });
    
    // Aplicar después de que la ventana esté completamente cargada
    window.addEventListener('load', function() {
        setTimeout(aplicarColorDisponibilidad, 1000);
    });
    
    // Observador de mutaciones para elementos dinámicos
    const observer = new MutationObserver(function(mutations) {
        let shouldApply = false;
        
        mutations.forEach(mutation => {
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && (
                        (node.matches && node.matches('.btn')) ||
                        (node.querySelector && node.querySelector('.btn')) ||
                        (node.matches && node.matches('.stock-container')) ||
                        (node.querySelector && node.querySelector('.stock-container'))
                    )) {
                        shouldApply = true;
                    }
                });
            }
        });
        
        if (shouldApply) {
            setTimeout(aplicarColorDisponibilidad, 100);
        }
    });
    
    // Observar cambios en el DOM
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: false
    });
    
    // Exponer funciones globalmente para debugging
    window.themeUpdater = {
        recargarCSS: recargarCSSDinamico,
        aplicarColor: aplicarColorDisponibilidad,
        actualizar: manejarActualizacionTema
    };
    
    console.log('Actualizador global del tema inicializado correctamente');
    
})();
