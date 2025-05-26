/**
 * Script para inicializar y configurar correctamente los botones flotantes
 * Este script se encarga de asegurar que los botones flotantes no choquen entre sí
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todos los botones flotantes
    var elemsFloatingBtn = document.querySelectorAll('.fixed-action-btn');

    if (elemsFloatingBtn.length > 0) {
        console.log('Inicializando botones flotantes:', elemsFloatingBtn.length);

        // Verificar si hay botones que necesitan clases específicas
        elemsFloatingBtn.forEach(function(btn) {
            // Verificar si ya tiene una clase específica
            if (btn.id === 'chat-float' || btn.id === 'admin-chat-float') {
                btn.classList.add('chat-float');
                console.log('Botón de chat detectado:', btn.id);
            } else if (btn.id === 'cart-float') {
                btn.classList.add('cart-float');
                console.log('Botón de carrito detectado');
            } else if (!btn.classList.contains('admin-float') &&
                      !btn.classList.contains('cart-float') &&
                      !btn.classList.contains('chat-float') &&
                      !btn.classList.contains('user-float')) {
                // Si no tiene ninguna clase específica, asignar admin-float por defecto
                btn.classList.add('admin-float');
                console.log('Asignada clase admin-float por defecto');
            }
        });

        // Inicializar con configuración específica después de asignar clases
        var instancesFloatingBtn = M.FloatingActionButton.init(elemsFloatingBtn, {
            direction: 'top',
            hoverEnabled: false,
            toolbarEnabled: false
        });

        // Verificar posiciones después de inicializar
        console.log('Botones flotantes inicializados correctamente');
    }
});
