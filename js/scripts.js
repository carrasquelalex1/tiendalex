/**
 * Scripts personalizados para tiendalex2
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todos los componentes de Materialize
    M.AutoInit();

    // Manejar enlaces dinámicos
    setupDynamicLinks();

    // Inicializar mensajes de notificación
    setupNotifications();
});

/**
 * Configura los enlaces dinámicos para cargar contenido sin recargar la página
 */
function setupDynamicLinks() {
    // Obtener todos los enlaces dinámicos
    const dynamicLinks = document.querySelectorAll('.enlace-dinamico');

    // Agregar evento de clic a cada enlace
    dynamicLinks.forEach(link => {
        // Evitar duplicar event listeners
        if (link.getAttribute('data-listener-added')) {
            return;
        }

        link.setAttribute('data-listener-added', 'true');

        link.addEventListener('click', function(e) {
            e.preventDefault();

            // Obtener la página a cargar
            const page = this.getAttribute('data-page');

            // Si es login.php o registro.php, navegar directamente
            if (page === 'login.php' || page === 'registro.php') {
                console.log('Navegando a:', page);
                window.location.href = page;
                return;
            }

            // Para otras páginas, usar AJAX para cargar el contenido
            loadPage(page);
        });
    });
}

/**
 * Carga una página mediante AJAX
 * @param {string} page - La página a cargar
 */
function loadPage(page) {
    // Mostrar indicador de carga
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'progress';
    loadingIndicator.innerHTML = '<div class="indeterminate"></div>';
    document.querySelector('main').prepend(loadingIndicator);

    // Realizar la solicitud AJAX
    fetch(page)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al cargar la página');
            }
            return response.text();
        })
        .then(html => {
            // Extraer el contenido principal
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const mainContent = doc.querySelector('main').innerHTML;

            // Actualizar el contenido principal
            document.querySelector('main').innerHTML = mainContent;

            // Actualizar el título de la página
            const title = doc.querySelector('title').textContent;
            document.title = title;

            // Actualizar la URL sin recargar la página
            history.pushState({page: page}, title, page);

            // Reinicializar los componentes de Materialize
            reinitializeMaterialize();
        })
        .catch(error => {
            console.error('Error:', error);
            M.toast({html: 'Error al cargar la página', classes: 'red'});
        })
        .finally(() => {
            // Eliminar el indicador de carga
            if (loadingIndicator) {
                loadingIndicator.remove();
            }
        });
}

/**
 * Reinicializa los componentes de Materialize después de cargar contenido dinámico
 */
function reinitializeMaterialize() {
    // Inicializar componentes específicos que puedan estar en el contenido cargado
    const selects = document.querySelectorAll('select');
    if (selects.length > 0) {
        M.FormSelect.init(selects);
    }

    const modals = document.querySelectorAll('.modal');
    if (modals.length > 0) {
        M.Modal.init(modals);
    }

    const tooltips = document.querySelectorAll('.tooltipped');
    if (tooltips.length > 0) {
        M.Tooltip.init(tooltips);
    }

    const parallax = document.querySelectorAll('.parallax');
    if (parallax.length > 0) {
        M.Parallax.init(parallax);
    }

    // Volver a configurar los enlaces dinámicos
    setupDynamicLinks();
}

/**
 * Configura las notificaciones para que se puedan cerrar
 */
function setupNotifications() {
    // Buscar botones de cierre de notificaciones
    const closeButtons = document.querySelectorAll('.close-message');

    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Encontrar el contenedor padre de la notificación
            const notification = this.closest('.message-container');

            // Animar la desaparición
            notification.style.opacity = '0';

            // Eliminar después de la animación
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    });

    // Cerrar automáticamente las notificaciones después de 5 segundos
    const notifications = document.querySelectorAll('.message-container');

    notifications.forEach(notification => {
        setTimeout(() => {
            if (notification) {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }, 5000);
    });
}
