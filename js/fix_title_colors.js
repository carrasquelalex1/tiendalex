/**
 * Script para forzar la aplicación de colores del tema a los títulos
 * Este script asegura que los títulos usen los colores dinámicos del tema
 */

function applyTitleColors() {
    console.log('Aplicando colores del tema a los títulos...');
    
    // Obtener los colores del tema desde las variables CSS
    const rootStyles = getComputedStyle(document.documentElement);
    const primaryColor = rootStyles.getPropertyValue('--primary-color').trim();
    const accentColor = rootStyles.getPropertyValue('--accent-color').trim();
    
    console.log('Colores detectados:');
    console.log('Primary color:', primaryColor);
    console.log('Accent color:', accentColor);
    
    // Si no se detectan colores, usar valores predeterminados
    const finalPrimaryColor = primaryColor || '#2c3e50';
    const finalAccentColor = accentColor || '#2980b9';
    
    // Aplicar color primario a los títulos principales
    const mainTitles = document.querySelectorAll('.hero-title, h1.hero-title, .page-title, .section-title');
    mainTitles.forEach(title => {
        title.style.color = finalPrimaryColor + ' !important';
        console.log('Color aplicado a título principal:', title);
    });
    
    // Aplicar color de acento a los subtítulos
    const subtitles = document.querySelectorAll('.hero-subtitle, h5.hero-subtitle');
    subtitles.forEach(subtitle => {
        subtitle.style.color = finalAccentColor + ' !important';
        console.log('Color aplicado a subtítulo:', subtitle);
    });
    
    // Aplicar color primario a las líneas decorativas
    const decorativeLines = document.querySelectorAll('.section-title::after');
    decorativeLines.forEach(line => {
        line.style.backgroundColor = finalPrimaryColor + ' !important';
    });
    
    // Forzar la aplicación usando CSS inline para máxima prioridad
    const style = document.createElement('style');
    style.id = 'force-title-colors';
    style.innerHTML = `
        .hero-title,
        h1.hero-title,
        .page-title,
        .section-title {
            color: ${finalPrimaryColor} !important;
        }
        
        .hero-subtitle,
        h5.hero-subtitle {
            color: ${finalAccentColor} !important;
        }
        
        .section-title::after {
            background-color: ${finalPrimaryColor} !important;
        }
        
        /* Especificidad máxima para el catálogo */
        main.catalogo-main .hero-title,
        main.catalogo-main h1.hero-title {
            color: ${finalPrimaryColor} !important;
        }
        
        main.catalogo-main .hero-subtitle,
        main.catalogo-main h5.hero-subtitle {
            color: ${finalAccentColor} !important;
        }
    `;
    
    // Remover estilo anterior si existe
    const existingStyle = document.getElementById('force-title-colors');
    if (existingStyle) {
        existingStyle.remove();
    }
    
    // Agregar el nuevo estilo al head
    document.head.appendChild(style);
    
    console.log('Estilos de título aplicados correctamente');
}

// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, aplicando colores de título...');
    
    // Ejecutar inmediatamente
    applyTitleColors();
    
    // Ejecutar después de un breve retraso para asegurar que las variables CSS estén cargadas
    setTimeout(applyTitleColors, 500);
    setTimeout(applyTitleColors, 1000);
    setTimeout(applyTitleColors, 2000);
});

// Ejecutar cuando la página esté completamente cargada
window.addEventListener('load', function() {
    console.log('Página completamente cargada, aplicando colores de título...');
    setTimeout(applyTitleColors, 100);
});

// Escuchar cambios en el tema
window.addEventListener('storage', function(event) {
    if (event.key === 'theme_updated') {
        console.log('Tema actualizado, reaplicando colores de título...');
        setTimeout(applyTitleColors, 500);
    }
});

// Observar cambios en el DOM para aplicar colores a elementos nuevos
const observer = new MutationObserver(function(mutations) {
    let shouldApply = false;
    
    mutations.forEach(function(mutation) {
        if (mutation.type === 'childList') {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    if (node.classList && (
                        node.classList.contains('hero-title') ||
                        node.classList.contains('hero-subtitle') ||
                        node.classList.contains('section-title') ||
                        node.classList.contains('page-title')
                    )) {
                        shouldApply = true;
                    }
                }
            });
        }
    });
    
    if (shouldApply) {
        setTimeout(applyTitleColors, 100);
    }
});

// Observar el contenedor principal
const mainContainer = document.querySelector('main') || document.body;
observer.observe(mainContainer, {
    childList: true,
    subtree: true
});

// Función para aplicación manual (puede ser llamada desde la consola)
window.applyTitleColorsManual = applyTitleColors;
