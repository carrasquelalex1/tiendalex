/**
 * Script para actualizar dinámicamente los colores del tema
 * Este script se encarga de aplicar los colores seleccionados en tiempo real
 * y verificar periódicamente si hay cambios en el tema
 */

document.addEventListener('DOMContentLoaded', function() {
    // Verificar si estamos en la página de administración de temas
    const isThemeAdmin = document.getElementById('primary_color') !== null;

    // Si estamos en la página de administración, no necesitamos cargar los colores
    if (isThemeAdmin) {
        return;
    }

    // Variable para almacenar la última versión del tema
    let lastThemeVersion = '';

    // Función para verificar si hay una nueva versión del tema
    function checkThemeVersion() {
        fetch('/tiendalex2/css/theme_version.txt?' + new Date().getTime())
            .then(response => {
                if (response.ok) {
                    return response.text();
                }
                throw new Error('No se pudo cargar la versión del tema');
            })
            .then(version => {
                // Si la versión ha cambiado, recargar el tema
                if (version !== lastThemeVersion) {
                    console.log('Nueva versión del tema detectada:', version);
                    lastThemeVersion = version;
                    loadThemeColors(version);
                }
            })
            .catch(error => {
                console.error('Error al verificar la versión del tema:', error);
                // Si hay un error, intentar cargar el tema de todos modos
                loadThemeColors(new Date().getTime());
            });
    }

    // Función para cargar los colores desde el servidor
    function loadThemeColors(version) {
        // Usar la versión proporcionada o generar una nueva
        const themeVersion = version || new Date().getTime();

        // Forzar la recarga del CSS dinámico añadiendo un parámetro de versión
        const linkElement = document.querySelector('link[href*="dynamic_theme.php"]');

        if (linkElement) {
            // Eliminar el enlace existente para forzar una recarga completa
            linkElement.remove();
        }

        // Crear un nuevo enlace para el CSS dinámico
        const newLink = document.createElement('link');
        newLink.rel = 'stylesheet';
        newLink.id = 'dynamic-theme-css';
        newLink.href = `/tiendalex2/css/dynamic_theme.php?v=${themeVersion}`;

        // Asegurarse de que se cargue al final para tener prioridad
        document.head.appendChild(newLink);
        console.log('Tema actualizado con versión:', themeVersion);

        // Actualizar también los estilos inline si existen
        document.querySelectorAll('[style*="--primary-color"]').forEach(element => {
            // Forzar un reflow para aplicar los nuevos estilos
            element.style.display = element.style.display;
        });

        // Forzar un reflow en elementos clave
        const elementsToRefresh = [
            'header', 'nav', '.page-footer', '.btn', '.btn-floating',
            '.fixed-action-btn', '.card', '.badge'
        ];

        elementsToRefresh.forEach(selector => {
            document.querySelectorAll(selector).forEach(element => {
                // Forzar un reflow
                void element.offsetWidth;

                // Aplicar el estilo directamente si es necesario
                if (selector === 'header' || selector === 'nav' || selector === '.page-footer') {
                    element.style.backgroundColor = `var(--primary-color)`;
                }

                // Añadir y quitar una clase para forzar la actualización
                element.classList.add('theme-refreshed');
                setTimeout(() => element.classList.remove('theme-refreshed'), 50);
            });
        });
    }

    // Verificar la versión del tema al iniciar
    checkThemeVersion();

    // Verificar periódicamente si hay cambios en el tema (cada 30 segundos)
    setInterval(checkThemeVersion, 30000);

    // También verificar cuando la ventana recupera el foco
    window.addEventListener('focus', checkThemeVersion);

    // Escuchar cambios en localStorage para actualización inmediata entre pestañas
    window.addEventListener('storage', function(event) {
        if (event.key === 'theme_updated') {
            console.log('Tema actualizado en otra pestaña, recargando...');
            loadThemeColors(event.newValue);

            // Mostrar notificación
            if (typeof M !== 'undefined' && M.toast) {
                M.toast({
                    html: '<i class="material-icons left">palette</i> Tema actualizado',
                    classes: 'rounded',
                    displayLength: 3000
                });
            }
        }
    });
});
