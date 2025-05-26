/**
 * Script para forzar la aplicación del color de disponibilidad
 * Este script se ejecuta después de guardar la configuración del tema
 * y aplica directamente el color a los elementos del DOM
 */

// Función para forzar la aplicación del color de disponibilidad
function forzarColorDisponibilidad(color) {
    console.log('Forzando aplicación del color de disponibilidad:', color);

    // Si no se proporciona un color, intentar obtenerlo de las variables CSS
    if (!color) {
        const computedStyle = getComputedStyle(document.documentElement);
        color = computedStyle.getPropertyValue('--availability-color').trim() || '#f1c40f';
    }

    // Asegurarse de que el color sea válido
    if (!color || color === '') {
        color = '#f1c40f'; // Color amarillo por defecto
    }

    // Aplicar el color directamente a todos los botones de disponibilidad
    const availabilityButtons = document.querySelectorAll('.stock-container .btn:not(.red), .producto-item .stock-container button.btn:not(.red), .availability-btn, button.availability-btn');

    console.log('Botones encontrados:', availabilityButtons.length);

    availabilityButtons.forEach(button => {
        if (!button.classList.contains('red') && !button.textContent.includes('Agotado')) {
            button.style.backgroundColor = color + ' !important';
            button.setAttribute('style', `background-color: ${color} !important; color: white !important;`);
            console.log('Color aplicado a botón:', button);
        }
    });

    // Crear una hoja de estilo con alta especificidad
    const styleId = 'forced-availability-styles';
    let styleElement = document.getElementById(styleId);

    if (!styleElement) {
        styleElement = document.createElement('style');
        styleElement.id = styleId;
        document.head.appendChild(styleElement);
    }

    // Usar selectores con alta especificidad
    styleElement.textContent = `
        .stock-container .btn:not(.red),
        .producto-item .stock-container button.btn:not(.red),
        .availability-btn,
        button.availability-btn,
        body .stock-container .btn:not(.red),
        body .producto-item .stock-container button.btn:not(.red),
        body .availability-btn,
        body button.availability-btn,
        html body .stock-container .btn:not(.red),
        html body .producto-item .stock-container button.btn:not(.red),
        html body .availability-btn,
        html body button.availability-btn {
            background-color: ${color} !important;
            color: white !important;
        }
    `;

    // Forzar un reflow para aplicar los estilos
    document.body.offsetHeight;

    console.log('Estilos forzados aplicados');

    // Guardar el color en localStorage para que otras páginas lo apliquen
    localStorage.setItem('forced_availability_color', color);
    localStorage.setItem('forced_availability_timestamp', Date.now());
}

// Ejecutar cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('Verificando si hay un color forzado guardado');

    // Verificar si hay un color guardado en localStorage
    const savedColor = localStorage.getItem('forced_availability_color');
    const timestamp = localStorage.getItem('forced_availability_timestamp');

    // Solo aplicar si el color se guardó en los últimos 5 minutos
    if (savedColor && timestamp && (Date.now() - timestamp < 300000)) {
        console.log('Aplicando color guardado:', savedColor);
        forzarColorDisponibilidad(savedColor);

        // Aplicar el color nuevamente después de un breve retraso para asegurar que se aplique
        setTimeout(function() {
            forzarColorDisponibilidad(savedColor);
        }, 500);

        // Y una vez más después de un retraso más largo para capturar cualquier carga tardía
        setTimeout(function() {
            forzarColorDisponibilidad(savedColor);
        }, 2000);
    }

    // Agregar evento al botón de guardar configuración si estamos en la página de administración del tema
    if (window.location.pathname.includes('admin_tema.php')) {
        const guardarBtn = document.getElementById('guardar_tema_btn');
        if (guardarBtn) {
            console.log('Agregando evento al botón de guardar configuración');
            guardarBtn.addEventListener('click', function() {
                const availabilityColor = document.getElementById('availability_color').value;
                if (availabilityColor) {
                    console.log('Guardando color de disponibilidad:', availabilityColor);
                    localStorage.setItem('forced_availability_color', availabilityColor);
                    localStorage.setItem('forced_availability_timestamp', Date.now());
                }
            });
        }
    }
});

// Escuchar eventos de actualización del tema
window.addEventListener('storage', function(e) {
    if (e.key === 'theme_updated' || e.key === 'forced_availability_color') {
        console.log('Evento de actualización detectado');

        // Esperar un momento para que se carguen los nuevos estilos
        setTimeout(function() {
            const savedColor = localStorage.getItem('forced_availability_color');
            if (savedColor) {
                console.log('Aplicando color guardado desde evento storage:', savedColor);
                forzarColorDisponibilidad(savedColor);

                // Aplicar el color varias veces con diferentes retrasos para asegurar que se aplique
                setTimeout(function() { forzarColorDisponibilidad(savedColor); }, 500);
                setTimeout(function() { forzarColorDisponibilidad(savedColor); }, 1000);
                setTimeout(function() { forzarColorDisponibilidad(savedColor); }, 2000);
            } else {
                forzarColorDisponibilidad();
            }
        }, 100);
    }
});

// Aplicar el color periódicamente en la página de catálogo
if (window.location.pathname.includes('catalogo.php')) {
    console.log('Página de catálogo detectada, configurando aplicación periódica');

    // Aplicar el color cada 3 segundos durante 15 segundos para asegurar que se aplique
    let contador = 0;
    const intervalo = setInterval(function() {
        const savedColor = localStorage.getItem('forced_availability_color');
        if (savedColor) {
            console.log('Aplicación periódica del color:', savedColor);
            forzarColorDisponibilidad(savedColor);
        }

        contador++;
        if (contador >= 5) {
            clearInterval(intervalo);
        }
    }, 3000);
}

// Exponer la función globalmente para que pueda ser llamada desde otros scripts
window.forzarColorDisponibilidad = forzarColorDisponibilidad;
