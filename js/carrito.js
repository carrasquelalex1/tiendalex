/**
 * Script para la página de carrito
 * 
 * Este script maneja las interacciones y notificaciones en la página de carrito
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes de Materialize
    M.AutoInit();
    
    // Obtener referencias a los botones
    const btnCompletarCompra = document.querySelector('.completar-compra');
    const btnVaciarCarrito = document.querySelector('.vaciar-carrito');
    const btnSeguirComprando = document.querySelector('.seguir-comprando');
    
    // Agregar evento al botón "Completar Compra"
    if (btnCompletarCompra) {
        btnCompletarCompra.addEventListener('click', function(e) {
            // Prevenir el comportamiento predeterminado
            e.preventDefault();
            
            // Mostrar un mensaje de confirmación
            Swal.fire({
                title: '¿Completar compra?',
                text: '¿Estás seguro de que deseas completar la compra?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4CAF50',
                cancelButtonColor: '#F44336',
                confirmButtonText: 'Sí, completar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar mensaje de procesamiento
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Estamos procesando tu compra',
                        icon: 'info',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        allowEnterKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Enviar el formulario
                    document.getElementById('form-completar-compra').submit();
                }
            });
        });
    }
    
    // Agregar evento al formulario de vaciar carrito
    document.addEventListener('click', function(e) {
        // Verificar si se hizo clic en el botón de vaciar carrito o en sus hijos
        if (e.target.closest('.vaciar-carrito') || e.target.closest('.vaciar-carrito *')) {
            e.preventDefault();

            // Obtener el formulario más cercano
            const form = e.target.closest('form');

            // Mostrar un mensaje de confirmación
            Swal.fire({
                title: '¿Vaciar carrito?',
                text: '¿Estás seguro de que deseas vaciar el carrito? Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#F44336',
                cancelButtonColor: '#9e9e9e',
                confirmButtonText: 'Sí, vaciar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar mensaje de procesamiento
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Vaciando el carrito',
                        icon: 'info',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        allowEnterKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Enviar el formulario original
                    form.submit();
                }
            });
        }
    });
    
    // Agregar evento a los botones de actualizar cantidad
    const btnsMenos = document.querySelectorAll('.btn-menos');
    const btnsMas = document.querySelectorAll('.btn-mas');
    
    btnsMenos.forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.nextElementSibling;
            const id = input.getAttribute('data-id');
            let cantidad = parseInt(input.value);
            
            if (cantidad > 1) {
                cantidad--;
                input.value = cantidad;
                actualizarCantidad(id, cantidad);
            }
        });
    });
    
    btnsMas.forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const id = input.getAttribute('data-id');
            let cantidad = parseInt(input.value);
            
            cantidad++;
            input.value = cantidad;
            actualizarCantidad(id, cantidad);
        });
    });
    
    // Función para actualizar la cantidad
    function actualizarCantidad(id, cantidad) {
        // Mostrar indicador de carga
        M.toast({html: '<i class="material-icons left">hourglass_empty</i> Actualizando cantidad...', classes: 'blue', displayLength: 2000});
        
        // Enviar solicitud AJAX
        fetch('backend/actualizar_cantidad.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}&cantidad=${cantidad}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar subtotales y total
                document.getElementById(`subtotal-dolares-${id}`).textContent = `$${data.subtotal_dolares}`;
                document.getElementById(`subtotal-bolivares-${id}`).textContent = `Bs. ${data.subtotal_bolivares}`;
                document.getElementById('total-dolares').textContent = `$${data.total_dolares}`;
                document.getElementById('total-bolivares').textContent = `Bs. ${data.total_bolivares}`;
                
                // Mostrar mensaje de éxito
                M.toast({html: '<i class="material-icons left">check_circle</i> Cantidad actualizada', classes: 'green', displayLength: 2000});
            } else {
                // Mostrar mensaje de error
                M.toast({html: `<i class="material-icons left">error</i> ${data.message}`, classes: 'red', displayLength: 3000});
                
                // Restaurar la cantidad anterior
                document.querySelector(`input[data-id="${id}"]`).value = data.cantidad_actual;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            M.toast({html: '<i class="material-icons left">error</i> Error al actualizar la cantidad', classes: 'red', displayLength: 3000});
        });
    }
    
    // Verificar si hay mensajes de éxito o error en la URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        M.toast({html: '<i class="material-icons left">check_circle</i> ' + decodeURIComponent(urlParams.get('message')), classes: 'green', displayLength: 4000});
    } else if (urlParams.has('error')) {
        M.toast({html: '<i class="material-icons left">error</i> ' + decodeURIComponent(urlParams.get('message')), classes: 'red', displayLength: 4000});
    }
});
