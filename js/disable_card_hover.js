/**
 * Script para desactivar las animaciones automáticas de las tarjetas
 * y permitir solo animaciones específicas en los botones
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Desactivando animaciones automáticas de tarjetas...');
    
    // Función para remover la clase hovered y desactivar eventos
    function desactivarHoverTarjetas() {
        const cards = document.querySelectorAll('.card');
        
        cards.forEach(card => {
            // Remover la clase hovered si existe
            card.classList.remove('hovered');
            
            // Clonar el elemento para remover todos los event listeners
            const newCard = card.cloneNode(true);
            card.parentNode.replaceChild(newCard, card);
            
            // Asegurar que no se agregue la clase hovered
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (newCard.classList.contains('hovered')) {
                            newCard.classList.remove('hovered');
                        }
                    }
                });
            });
            
            observer.observe(newCard, {
                attributes: true,
                attributeFilter: ['class']
            });
        });
    }
    
    // Función para desactivar animaciones de botones de disponibilidad
    function desactivarAnimacionesBotones() {
        const availabilityButtons = document.querySelectorAll('.availability-btn, .btn.availability-btn');
        
        availabilityButtons.forEach(button => {
            // Remover clases de animación
            button.classList.remove('pulse');
            
            // Desactivar animaciones CSS
            button.style.animation = 'none';
            button.style.transform = 'none';
            
            // Observar cambios en las clases
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (button.classList.contains('pulse')) {
                            button.classList.remove('pulse');
                            button.style.animation = 'none';
                        }
                    }
                });
            });
            
            observer.observe(button, {
                attributes: true,
                attributeFilter: ['class']
            });
        });
    }
    
    // Ejecutar inmediatamente
    desactivarHoverTarjetas();
    desactivarAnimacionesBotones();
    
    // Ejecutar después de un breve retraso para elementos cargados dinámicamente
    setTimeout(() => {
        desactivarHoverTarjetas();
        desactivarAnimacionesBotones();
    }, 500);
    
    // Ejecutar periódicamente para asegurar que se mantenga desactivado
    setInterval(() => {
        desactivarAnimacionesBotones();
    }, 2000);
    
    console.log('Animaciones automáticas desactivadas');
});

// Sobrescribir cualquier función que pueda agregar la clase hovered
window.addEventListener('load', function() {
    // Desactivar cualquier script que agregue hover
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.onmouseenter = null;
        card.onmouseleave = null;
        card.addEventListener('mouseenter', function(e) {
            e.stopPropagation();
            this.classList.remove('hovered');
        }, true);
        card.addEventListener('mouseleave', function(e) {
            e.stopPropagation();
            this.classList.remove('hovered');
        }, true);
    });
});
