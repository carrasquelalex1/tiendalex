/**
 * Script para corregir la posición de los toasts de Materialize
 * Fuerza que aparezcan por encima del encabezado o en posición visible
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Iniciando corrección de posición de toasts...');
    
    // Sobrescribir la función toast de Materialize para forzar posición
    const originalToast = M.toast;
    
    M.toast = function(options) {
        // Llamar a la función original
        const toastInstance = originalToast.call(this, options);
        
        // Esperar un momento para que el toast se cree
        setTimeout(() => {
            // Buscar todos los toasts activos
            const toasts = document.querySelectorAll('.toast');
            
            toasts.forEach(toast => {
                // Forzar estilos específicos
                toast.style.position = 'fixed';
                toast.style.zIndex = '15000';
                toast.style.top = '20px'; // Muy arriba, por encima del encabezado
                toast.style.right = '20px';
                toast.style.left = 'auto';
                toast.style.maxWidth = '350px';
                toast.style.minWidth = '250px';
                toast.style.boxShadow = '0 8px 24px rgba(0,0,0,0.3)';
                toast.style.borderRadius = '8px';
                toast.style.padding = '16px 20px';
                toast.style.fontSize = '14px';
                toast.style.fontWeight = '500';
                toast.style.backdropFilter = 'blur(10px)';
                
                console.log('Toast posicionado:', toast);
            });
        }, 50);
        
        return toastInstance;
    };
    
    // Función para reposicionar toasts existentes
    function repositionExistingToasts() {
        const toasts = document.querySelectorAll('.toast');
        
        toasts.forEach(toast => {
            toast.style.position = 'fixed';
            toast.style.zIndex = '15000';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.left = 'auto';
            toast.style.maxWidth = '350px';
            toast.style.minWidth = '250px';
            toast.style.boxShadow = '0 8px 24px rgba(0,0,0,0.3)';
            toast.style.borderRadius = '8px';
            toast.style.padding = '16px 20px';
            toast.style.fontSize = '14px';
            toast.style.fontWeight = '500';
            toast.style.backdropFilter = 'blur(10px)';
        });
    }
    
    // Observar cambios en el DOM para capturar nuevos toasts
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Verificar si es un toast o contiene toasts
                        if (node.classList && node.classList.contains('toast')) {
                            setTimeout(() => repositionExistingToasts(), 10);
                        } else if (node.querySelector && node.querySelector('.toast')) {
                            setTimeout(() => repositionExistingToasts(), 10);
                        }
                    }
                });
            }
        });
    });
    
    // Observar el body para capturar toasts dinámicos
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Reposicionar toasts existentes cada segundo (como respaldo)
    setInterval(repositionExistingToasts, 1000);
    
    // Función para crear toast personalizado si Materialize falla
    window.showCustomToast = function(message, className = 'green', duration = 4000) {
        // Crear elemento toast personalizado
        const toast = document.createElement('div');
        toast.className = `toast ${className}`;
        toast.innerHTML = message;
        
        // Aplicar estilos directamente
        toast.style.position = 'fixed';
        toast.style.zIndex = '15000';
        toast.style.top = '20px';
        toast.style.right = '20px';
        toast.style.maxWidth = '350px';
        toast.style.minWidth = '250px';
        toast.style.padding = '16px 20px';
        toast.style.borderRadius = '8px';
        toast.style.fontSize = '14px';
        toast.style.fontWeight = '500';
        toast.style.color = 'white';
        toast.style.boxShadow = '0 8px 24px rgba(0,0,0,0.3)';
        toast.style.backdropFilter = 'blur(10px)';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'transform 0.3s ease-out';
        
        // Color de fondo según la clase
        if (className.includes('green')) {
            toast.style.backgroundColor = '#4CAF50';
        } else if (className.includes('red')) {
            toast.style.backgroundColor = '#f44336';
        } else if (className.includes('blue')) {
            toast.style.backgroundColor = '#2196F3';
        }
        
        // Agregar al DOM
        document.body.appendChild(toast);
        
        // Animar entrada
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 10);
        
        // Remover después del tiempo especificado
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, duration);
        
        console.log('Toast personalizado creado:', toast);
        return toast;
    };
    
    console.log('Corrección de toasts inicializada');
});
