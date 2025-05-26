/**
 * Script para la página de confirmación de pedido
 *
 * Este script maneja las animaciones y notificaciones en la página de confirmación de pedido
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mostrar mensaje de éxito más prominente
    M.toast({html: '<i class="material-icons left">check_circle</i> ¡Pedido completado con éxito!', classes: 'green', displayLength: 6000});

    // Mostrar mensaje de alerta más visible
    Swal.fire({
        title: '¡Pedido Completado!',
        text: 'Tu compra se ha realizado satisfactoriamente. ¡Gracias por tu preferencia!',
        icon: 'success',
        confirmButtonText: 'Continuar',
        confirmButtonColor: '#4CAF50'
    });

    // Crear efecto de confeti
    createConfetti();

    // Mostrar modal de confirmación
    setTimeout(function() {
        mostrarModalConfirmacion();
    }, 1000);

    // Función para crear confeti
    function createConfetti() {
        const colors = ['#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4CAF50', '#8BC34A', '#CDDC39', '#FFEB3B', '#FFC107', '#FF9800', '#FF5722'];

        for (let i = 0; i < 100; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.width = Math.random() * 10 + 5 + 'px';
            confetti.style.height = Math.random() * 10 + 5 + 'px';
            confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
            confetti.style.animationDelay = Math.random() * 5 + 's';

            document.body.appendChild(confetti);

            // Eliminar el confeti después de la animación
            setTimeout(function() {
                confetti.remove();
            }, 8000);
        }
    }

    // Función para mostrar el modal de confirmación
    function mostrarModalConfirmacion() {
        // Verificar si el modal ya existe
        let modal = document.getElementById('modal-confirmacion-pedido');

        // Si no existe, crearlo
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'modal-confirmacion-pedido';
            modal.className = 'modal';

            // Obtener el código de pedido
            const codigoPedido = document.getElementById('codigo-pedido').textContent.trim();

            // Contenido del modal
            modal.innerHTML = `
                <div class="modal-content">
                    <h4 class="center-align"><i class="material-icons medium green-text">check_circle</i></h4>
                    <h4 class="center-align">¡Pedido Completado!</h4>
                    <p class="center-align">Tu pedido con código <strong>${codigoPedido}</strong> ha sido registrado correctamente.</p>
                    <p class="center-align">Ahora puedes realizar el pago para completar tu compra.</p>
                    <div class="center-align" style="margin-top: 20px;">
                        <a href="registrar_pago.php?pedido=${codigoPedido}" class="waves-effect waves-light btn-large blue">
                            <i class="material-icons left">payment</i>Registrar Pago
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cerrar</a>
                </div>
            `;

            // Agregar el modal al DOM
            document.body.appendChild(modal);

            // Inicializar el modal
            const modalInstance = M.Modal.init(modal, {
                dismissible: true,
                opacity: 0.8,
                inDuration: 300,
                outDuration: 200,
                startingTop: '4%',
                endingTop: '10%'
            });

            // Abrir el modal
            modalInstance.open();
        } else {
            // Si ya existe, solo abrirlo
            const modalInstance = M.Modal.getInstance(modal);
            modalInstance.open();
        }

        // Mostrar el icono de pedidos en el encabezado después de 3 segundos
        setTimeout(function() {
            mostrarIconoPedidos();
        }, 3000);
    }

    // Función para mostrar el icono de pedidos en el encabezado
    function mostrarIconoPedidos() {
        // Mostrar el contenedor del icono de pedidos
        const pedidosContainer = document.getElementById('pedidos-icon-container');
        const pedidosContainerMobile = document.getElementById('pedidos-icon-container-mobile');

        if (pedidosContainer) {
            pedidosContainer.style.display = 'block';

            // Animar el icono
            const pedidosIcon = document.getElementById('pedidos-icon');
            if (pedidosIcon) {
                pedidosIcon.classList.add('pulse');

                // Quitar la animación después de 5 segundos
                setTimeout(function() {
                    pedidosIcon.classList.remove('pulse');
                }, 5000);
            }
        }

        if (pedidosContainerMobile) {
            pedidosContainerMobile.style.display = 'block';

            // Animar el icono en móvil
            const pedidosIconMobile = document.getElementById('pedidos-icon-mobile');
            if (pedidosIconMobile) {
                pedidosIconMobile.classList.add('pulse');

                // Quitar la animación después de 5 segundos
                setTimeout(function() {
                    pedidosIconMobile.classList.remove('pulse');
                }, 5000);
            }
        }
    }
});
