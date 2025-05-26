/**
 * Script para diagnosticar problemas con el tema
 * Este script verifica que el tema se esté aplicando correctamente
 */

document.addEventListener('DOMContentLoaded', function() {
    // Solo ejecutar en la página de verificación del tema
    if (!window.location.pathname.includes('verificar_tema.php') && 
        !window.location.pathname.includes('forzar_actualizacion_tema.php')) {
        return;
    }
    
    console.log('Ejecutando diagnóstico del tema...');
    
    // Verificar si el CSS dinámico está cargado
    const dynamicCssLoaded = !!document.querySelector('link[href*="dynamic_theme.php"]');
    console.log('CSS dinámico cargado:', dynamicCssLoaded);
    
    // Obtener los valores de las variables CSS
    const rootStyles = getComputedStyle(document.documentElement);
    const primaryColor = rootStyles.getPropertyValue('--primary-color').trim();
    const secondaryColor = rootStyles.getPropertyValue('--secondary-color').trim();
    const accentColor = rootStyles.getPropertyValue('--accent-color').trim();
    
    console.log('Variables CSS detectadas:');
    console.log('--primary-color:', primaryColor);
    console.log('--secondary-color:', secondaryColor);
    console.log('--accent-color:', accentColor);
    
    // Verificar si los elementos clave están usando las variables CSS
    const header = document.querySelector('header');
    const headerBgColor = header ? getComputedStyle(header).backgroundColor : 'no detectado';
    console.log('Color de fondo del encabezado:', headerBgColor);
    
    const footer = document.querySelector('.page-footer');
    const footerBgColor = footer ? getComputedStyle(footer).backgroundColor : 'no detectado';
    console.log('Color de fondo del pie de página:', footerBgColor);
    
    // Verificar si hay errores en la consola relacionados con el tema
    if (console.error.toString().indexOf('native code') >= 0) {
        const originalError = console.error;
        console.error = function() {
            if (arguments[0] && typeof arguments[0] === 'string' && 
                (arguments[0].includes('theme') || arguments[0].includes('css'))) {
                console.log('Error relacionado con el tema detectado:', arguments[0]);
            }
            return originalError.apply(console, arguments);
        };
    }
    
    // Crear un informe de diagnóstico
    const diagnosticReport = {
        timestamp: new Date().toISOString(),
        dynamicCssLoaded: dynamicCssLoaded,
        cssVariables: {
            primaryColor: primaryColor,
            secondaryColor: secondaryColor,
            accentColor: accentColor
        },
        elementColors: {
            header: headerBgColor,
            footer: footerBgColor
        },
        userAgent: navigator.userAgent,
        screenSize: {
            width: window.innerWidth,
            height: window.innerHeight
        }
    };
    
    // Guardar el informe en localStorage para referencia
    localStorage.setItem('theme_diagnostic_report', JSON.stringify(diagnosticReport));
    
    console.log('Informe de diagnóstico completo:', diagnosticReport);
    
    // Mostrar un mensaje si se detectan problemas
    const problemsDetected = !dynamicCssLoaded || 
                            !primaryColor || 
                            headerBgColor === 'rgba(0, 0, 0, 0)' || 
                            footerBgColor === 'rgba(0, 0, 0, 0)';
    
    if (problemsDetected && typeof M !== 'undefined' && M.toast) {
        M.toast({
            html: '<i class="material-icons left">warning</i> Se han detectado posibles problemas con el tema. Revisa la consola para más detalles.',
            classes: 'rounded orange',
            displayLength: 5000
        });
    }
});
