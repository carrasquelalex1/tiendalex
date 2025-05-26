/**
 * Script para corregir el solapamiento de botones de login
 * Este script fuerza la corrección del posicionamiento de los botones
 */

function fixLoginButtons() {
    console.log('Ejecutando corrección de botones de login...');

    // Buscar todos los botones de login
    const loginButtons = document.querySelectorAll('a[href="login.php"]');

    console.log('Botones de login encontrados:', loginButtons.length);

    loginButtons.forEach((button, index) => {
        console.log(`Corrigiendo botón ${index + 1}:`, button);

        // Forzar estilos directamente en el elemento
        button.style.cssText = `
            position: relative !important;
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 8px 12px !important;
            float: none !important;
            clear: both !important;
            z-index: 1000 !important;
            transform: none !important;
            left: auto !important;
            right: auto !important;
            top: auto !important;
            bottom: auto !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            font-size: 0.8rem !important;
        `;

        // Corregir el contenedor padre
        const container = button.closest('.col.s12.center-align');
        if (container) {
            container.style.cssText = `
                position: relative !important;
                display: block !important;
                width: 100% !important;
                padding: 5px 0 !important;
                margin: 0 !important;
                overflow: visible !important;
                min-height: 50px !important;
                box-sizing: border-box !important;
            `;
        }

        // Corregir el contenedor de la fila
        const row = button.closest('.row');
        if (row) {
            row.style.cssText = `
                position: relative !important;
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            `;
        }

        // Corregir el card-action
        const cardAction = button.closest('.card-action');
        if (cardAction) {
            cardAction.style.cssText = `
                padding: 16px !important;
                position: relative !important;
                overflow: visible !important;
                min-height: 60px !important;
            `;
        }
    });

    console.log('Corrección de botones de login completada');
}

// Ejecutar inmediatamente si el DOM ya está listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFix);
} else {
    initFix();
}

function initFix() {
    console.log('Iniciando corrección de botones de login...');

    // Ejecutar inmediatamente
    fixLoginButtons();

    // Ejecutar múltiples veces para asegurar que se aplique
    setTimeout(fixLoginButtons, 100);
    setTimeout(fixLoginButtons, 300);
    setTimeout(fixLoginButtons, 500);
    setTimeout(fixLoginButtons, 1000);
    setTimeout(fixLoginButtons, 2000);
    setTimeout(fixLoginButtons, 3000);

    // Observar cambios en el DOM para aplicar correcciones a elementos nuevos
    const observer = new MutationObserver(function(mutations) {
        let shouldFix = false;

        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.querySelector && node.querySelector('a[href="login.php"]')) {
                            shouldFix = true;
                        }
                    }
                });
            }
        });

        if (shouldFix) {
            setTimeout(fixLoginButtons, 100);
        }
    });

    // Observar el contenedor principal
    const mainContainer = document.querySelector('main') || document.body;
    observer.observe(mainContainer, {
        childList: true,
        subtree: true
    });
});

// También ejecutar cuando la página esté completamente cargada
window.addEventListener('load', function() {
    console.log('Página completamente cargada, ejecutando corrección final...');
    setTimeout(fixLoginButtons, 100);
});

// Función para forzar corrección manual (puede ser llamada desde la consola)
window.fixLoginButtonsManual = fixLoginButtons;
