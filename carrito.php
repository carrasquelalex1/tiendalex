<?php
// Incluir archivos necesarios
require_once __DIR__ . '/backend/config/db.php';
require_once __DIR__ . '/helpers/session/session_helper.php';
require_once __DIR__ . '/autoload.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Registrar información de depuración
error_log("carrito.php - SESSION al inicio: " . print_r($_SESSION, true));

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    header("Location: index.php?error=1&message=" . urlencode("Debe iniciar sesión para acceder a esta página."));
    exit;
}

// Verificar si el usuario es administrador (no puede usar el carrito)
if (es_admin()) {
    header("Location: index.php?error=1&message=" . urlencode("Los administradores no pueden realizar compras."));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - Alexander Carrasquel</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body>
    <?php include 'frontend/includes/header.php'; ?>

    <?php

    // Obtener el ID del usuario
    $usuario_id = $_SESSION['usuario_logueado'];
    $codigo_pedido = isset($_SESSION['codigo_pedido']) ? $_SESSION['codigo_pedido'] : '';
    $productos_carrito = Carrito::obtenerProductosCarrito($usuario_id, $conn);
    // Calcular totales
    $total_dolares = 0;
    $total_bolivares = 0;
    if (!is_array($productos_carrito)) {
        $productos_carrito = [];
    }
    foreach ($productos_carrito as $producto) {
        $total_dolares += $producto['precio_dolares'] * $producto['cantidad'];
        $total_bolivares += $producto['precio_bolivares'] * $producto['cantidad'];
    }
    ?>

    <main>
        <div class="container">
            <div class="row">
                <div class="col s12">
                    <h4 class="page-title">Carrito de Compras</h4>
                    <div class="divider"></div>
                </div>
            </div>

            <?php include 'frontend/includes/messages.php'; ?>

            <?php if (count($productos_carrito) > 0): ?>
                <div class="row">
                    <div class="col s12">
                        <div class="card">
                            <div class="card-content">
                                <div class="codigo-pedido-container">
                                    <div class="codigo-pedido-label">
                                        <i class="material-icons">receipt</i>
                                        <span>Código de Pedido:</span>
                                    </div>
                                    <div class="codigo-pedido-value">
                                        <?php echo $codigo_pedido; ?>
                                    </div>
                                    <div class="codigo-pedido-copy tooltipped" data-position="top" data-tooltip="Copiar código" onclick="copiarCodigoPedido('<?php echo $codigo_pedido; ?>')">
                                        <i class="material-icons">content_copy</i>
                                    </div>
                                </div>

                                <table class="striped responsive-table">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Precio ($)</th>
                                            <th>Precio (Bs)</th>
                                            <th>Cantidad</th>
                                            <th>Subtotal ($)</th>
                                            <th>Subtotal (Bs)</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productos_carrito as $producto): ?>
                                            <tr>
                                                <td>
                                                    <div class="producto-carrito">
                                                        <?php if (!empty($producto['imagen_producto'])): ?>
                                                            <img src="<?php echo $producto['imagen_producto']; ?>" alt="<?php echo $producto['nombre_producto']; ?>" class="circle" width="50">
                                                        <?php else: ?>
                                                            <img src="backend/images/a.jpg" alt="<?php echo $producto['nombre_producto']; ?>" class="circle" width="50">
                                                        <?php endif; ?>
                                                        <span><?php echo $producto['nombre_producto']; ?></span>
                                                    </div>
                                                </td>
                                                <td>$<?php echo number_format($producto['precio_dolares'], 2); ?></td>
                                                <td>Bs. <?php echo number_format($producto['precio_bolivares'], 2); ?></td>
                                                <td>
                                                    <div class="cantidad-container-modern">
                                                        <button class="btn-cantidad-modern decrease" data-action="decrease" data-id="<?php echo $producto['id']; ?>">
                                                            <i class="material-icons">remove</i>
                                                        </button>
                                                        <div class="cantidad-input-wrapper">
                                                            <input type="number" min="1" value="<?php echo $producto['cantidad']; ?>" class="cantidad-input-modern" data-id="<?php echo $producto['id']; ?>" data-original="<?php echo $producto['cantidad']; ?>">
                                                        </div>
                                                        <button class="btn-cantidad-modern increase" data-action="increase" data-id="<?php echo $producto['id']; ?>">
                                                            <i class="material-icons">add</i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="subtotal-dolares">$<?php echo number_format($producto['precio_dolares'] * $producto['cantidad'], 2); ?></td>
                                                <td class="subtotal-bolivares">Bs. <?php echo number_format($producto['precio_bolivares'] * $producto['cantidad'], 2); ?></td>
                                                <td>
                                                    <div class="acciones-container">
                                                        <div class="preloader-wrapper small active update-spinner" data-id="<?php echo $producto['id']; ?>" style="display: none;">
                                                            <div class="spinner-layer spinner-blue-only">
                                                                <div class="circle-clipper left">
                                                                    <div class="circle"></div>
                                                                </div>
                                                                <div class="gap-patch">
                                                                    <div class="circle"></div>
                                                                </div>
                                                                <div class="circle-clipper right">
                                                                    <div class="circle"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <form action="backend/eliminar_carrito.php" method="POST" style="display: inline;">
                                                            <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
                                                            <button type="submit" class="btn-small red waves-effect waves-light tooltipped" data-position="top" data-tooltip="Eliminar producto">
                                                                <i class="material-icons">delete</i>
                                                            </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="4" class="right-align">Total:</th>
                                            <th class="total-dolares">$<?php echo number_format($total_dolares, 2); ?></th>
                                            <th class="total-bolivares">Bs. <?php echo number_format($total_bolivares, 2); ?></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="card-action">
                                <div class="row">
                                    <div class="col s12 m6">
                                        <a href="catalogo.php" class="btn waves-effect waves-light blue">
                                            <i class="material-icons left">arrow_back</i>Seguir Comprando
                                        </a>
                                    </div>
                                    <div class="col s12 m6 right-align">
                                        <form action="backend/vaciar_carrito.php" method="POST" id="form-vaciar-carrito" class="vaciar-carrito-form" style="display: inline-block; margin: 0 5px;">
                                            <button type="submit" class="btn waves-effect waves-light red vaciar-carrito">
                                                <i class="material-icons left">remove_shopping_cart</i>Vaciar Carrito
                                            </button>
                                        </form>
                                        <form action="backend/completar_compra.php" method="POST" id="form-completar-compra" style="display: inline-block; margin: 0 5px;">
                                            <button type="submit" class="btn waves-effect waves-light green completar-compra">
                                                <i class="material-icons left">check</i>Completar Compra
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col s12">
                        <div class="card">
                            <div class="card-content center-align carrito-vacio-container">
                                <div class="carrito-vacio-icon">
                                    <i class="large material-icons">shopping_cart</i>
                                </div>
                                <h4 class="blue-text">Tu carrito está vacío</h4>
                                <p class="flow-text">No tienes productos en tu carrito de compras.</p>
                                <div class="divider" style="margin: 30px 0;"></div>
                                <p>¿Qué tal si exploras nuestro catálogo para encontrar productos que te interesen?</p>
                                <a href="catalogo.php" class="btn-large waves-effect waves-light blue pulse" style="margin-top: 20px;">
                                    <i class="material-icons left">shopping_basket</i>
                                    Explorar Catálogo
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    .carrito-vacio-container {
                        padding: 40px 20px;
                    }

                    .carrito-vacio-icon {
                        background-color: #e3f2fd;
                        width: 100px;
                        height: 100px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto 20px;
                    }

                    .carrito-vacio-icon i {
                        font-size: 50px;
                        color: #1565C0;
                    }
                </style>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'frontend/includes/footer.php'; ?>

    <?php include 'frontend/includes/js_includes.php'; ?>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php include 'backend/script/script_enlaces.php'; ?>

    <!-- JavaScript para el carrito -->
    <script>
        $(document).ready(function() {
            console.log('jQuery cargado y DOM listo');
            console.log('Botones encontrados:', $('.btn-cantidad-modern').length);

            // Inicializar tooltips
            $('.tooltipped').tooltip();

            // Variable para almacenar el temporizador de actualización
            let updateTimers = {};

            // Verificar que los botones existen
            $('.btn-cantidad-modern').each(function(index) {
                console.log('Botón', index, ':', $(this).data('action'), 'ID:', $(this).data('id'));
            });

            // SOLUCIÓN FINAL - Event delegation para botones de cantidad
            const botones = document.querySelectorAll('.btn-cantidad-modern');

            // Event delegation - Método más robusto y limpio
            document.addEventListener('click', function(e) {
                const boton = e.target.closest('.btn-cantidad-modern');
                if (boton) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    // Procesar click en botón de cantidad
                    handleClickSimple(boton);
                }
            });

            // Función para manejar clicks en botones de cantidad
            function handleClickSimple(boton) {
                const action = boton.dataset.action;
                const id = boton.dataset.id;

                const inputElement = document.querySelector(`.cantidad-input-modern[data-id="${id}"]`);
                if (inputElement) {
                    let currentValue = parseInt(inputElement.value);

                    if (action === 'increase') {
                        currentValue++;
                    } else if (action === 'decrease' && currentValue > 1) {
                        currentValue--;
                    }

                    // Actualizar input visualmente
                    inputElement.value = currentValue;

                    // Actualizar en el servidor
                    actualizarCantidadServidor(id, currentValue);
                }
            }

            // Limpiar debugging - ya no necesario
            // Los estilos CSS están funcionando correctamente

            // Función para actualizar cantidad en el servidor
            function actualizarCantidadServidor(id, cantidad) {
                // Mostrar indicador de carga
                const spinner = document.querySelector(`.update-spinner[data-id="${id}"]`);
                if (spinner) {
                    spinner.style.display = 'block';
                }

                // Crear FormData para enviar
                const formData = new FormData();
                formData.append('id', id);
                formData.append('cantidad', cantidad);

                // Enviar petición con fetch
                fetch('backend/actualizar_cantidad_carrito.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {

                    if (data.success) {
                        // Actualizar subtotales en la interfaz
                        const fila = document.querySelector(`input[data-id="${id}"]`).closest('tr');

                        // Actualizar subtotal en dólares
                        const subtotalDolares = fila.querySelector('.subtotal-dolares');
                        if (subtotalDolares && data.producto) {
                            subtotalDolares.textContent = '$' + data.producto.subtotal_dolares;
                            subtotalDolares.style.backgroundColor = '#e3f2fd';
                            setTimeout(() => {
                                subtotalDolares.style.backgroundColor = '';
                            }, 1000);
                        }

                        // Actualizar subtotal en bolívares
                        const subtotalBolivares = fila.querySelector('.subtotal-bolivares');
                        if (subtotalBolivares && data.producto) {
                            subtotalBolivares.textContent = 'Bs. ' + data.producto.subtotal_bolivares;
                            subtotalBolivares.style.backgroundColor = '#e3f2fd';
                            setTimeout(() => {
                                subtotalBolivares.style.backgroundColor = '';
                            }, 1000);
                        }

                        // Actualizar totales
                        if (data.totales) {
                            const totalDolares = document.querySelector('.total-dolares');
                            const totalBolivares = document.querySelector('.total-bolivares');

                            if (totalDolares) totalDolares.textContent = '$' + data.totales.total_dolares;
                            if (totalBolivares) totalBolivares.textContent = 'Bs. ' + data.totales.total_bolivares;

                            // Actualizar badge del carrito en el encabezado
                            const badges = document.querySelectorAll('.badge');
                            badges.forEach(badge => {
                                if (data.totales.total_items) {
                                    badge.textContent = data.totales.total_items;
                                }
                            });
                        }

                        // Mostrar mensaje de éxito
                        if (window.M && window.M.toast) {
                            M.toast({
                                html: '<i class="material-icons left">check_circle</i> Cantidad actualizada',
                                classes: 'green',
                                displayLength: 2000
                            });
                        }

                    } else {
                        // Error del servidor
                        console.error('Error del servidor:', data.message);

                        // Restaurar valor original
                        const inputElement = document.querySelector(`.cantidad-input-modern[data-id="${id}"]`);
                        if (inputElement && data.cantidad_actual) {
                            inputElement.value = data.cantidad_actual;
                        }

                        // Mostrar mensaje de error
                        if (window.M && window.M.toast) {
                            M.toast({
                                html: '<i class="material-icons left">error</i> ' + data.message,
                                classes: 'red',
                                displayLength: 3000
                            });
                        } else {
                            alert('Error: ' + data.message);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error en la petición:', error);

                    // Mostrar mensaje de error
                    if (window.M && window.M.toast) {
                        M.toast({
                            html: '<i class="material-icons left">error</i> Error de conexión',
                            classes: 'red',
                            displayLength: 3000
                        });
                    } else {
                        alert('Error de conexión');
                    }
                })
                .finally(() => {
                    // Ocultar indicador de carga
                    if (spinner) {
                        spinner.style.display = 'none';
                    }
                });
            }

            // Función para actualizar la cantidad (jQuery - COMENTADA)
            function actualizarCantidad(id, cantidad) {
                const inputElement = $(`.cantidad-input-modern[data-id="${id}"], .cantidad-input[data-id="${id}"]`);
                const originalValue = parseInt(inputElement.data('original'));

                // Solo actualizar si la cantidad ha cambiado
                if (cantidad !== originalValue) {
                    // Mostrar spinner de carga
                    $(`.update-spinner[data-id="${id}"]`).show();

                    console.log('Enviando solicitud AJAX para actualizar cantidad:', { id, cantidad });

                    // Enviar solicitud AJAX
                    $.ajax({
                        url: 'backend/actualizar_cantidad_carrito.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            id: id,
                            cantidad: cantidad
                        },
                        success: function(response) {
                            console.log('Respuesta del servidor:', response);

                            if (response.success) {
                                // Verificar si el producto fue eliminado
                                if (response.removed) {
                                    // Eliminar la fila del producto
                                    inputElement.closest('tr').fadeOut(300, function() {
                                        $(this).remove();

                                        // Si no quedan productos, recargar la página
                                        if ($('tbody tr').length === 0) {
                                            location.reload();
                                        }
                                    });

                                    // Mostrar mensaje
                                    M.toast({html: '<i class="material-icons left">info</i> Producto eliminado del carrito', classes: 'blue', displayLength: 2000});
                                } else {
                                    // Actualizar valores en la interfaz
                                    inputElement.data('original', cantidad);
                                    inputElement.removeClass('changed');

                                    // Actualizar subtotales
                                    const subtotalDolaresElement = inputElement.closest('tr').find('.subtotal-dolares');
                                    const subtotalBolivaresElement = inputElement.closest('tr').find('.subtotal-bolivares');

                                    subtotalDolaresElement.text('$' + response.producto.subtotal_dolares);
                                    subtotalBolivaresElement.text('Bs. ' + response.producto.subtotal_bolivares);

                                    // Resaltar los elementos actualizados
                                    subtotalDolaresElement.addClass('highlight');
                                    subtotalBolivaresElement.addClass('highlight');

                                    setTimeout(function() {
                                        subtotalDolaresElement.removeClass('highlight');
                                        subtotalBolivaresElement.removeClass('highlight');
                                    }, 1000);

                                    // Mostrar mensaje de éxito (solo para cambios manuales, no para botones +/-)
                                    if (!inputElement.data('button-click')) {
                                        M.toast({html: '<i class="material-icons left">check_circle</i> ' + response.message, classes: 'green', displayLength: 2000});
                                    }
                                    inputElement.data('button-click', false);
                                }

                                // Actualizar totales
                                $('tfoot .total-dolares').text('$' + response.totales.total_dolares);
                                $('tfoot .total-bolivares').text('Bs. ' + response.totales.total_bolivares);

                                // Actualizar contador del carrito en el encabezado
                                $('.badge').text(response.totales.total_items);
                            } else {
                                // Mostrar mensaje de error
                                M.toast({html: '<i class="material-icons left">error</i> ' + response.message, classes: 'red', displayLength: 3000});

                                // Restaurar valor original
                                inputElement.val(originalValue);
                                inputElement.removeClass('changed');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error en la solicitud AJAX:', status, error);
                            console.log('Respuesta del servidor:', xhr.responseText);

                            // Mostrar mensaje de error
                            M.toast({html: '<i class="material-icons left">error</i> Error de conexión. Inténtalo de nuevo.', classes: 'red', displayLength: 3000});

                            // Restaurar valor original
                            inputElement.val(originalValue);
                            inputElement.removeClass('changed');
                        },
                        complete: function() {
                            // Ocultar spinner de carga
                            $(`.update-spinner[data-id="${id}"]`).hide();
                        }
                    });
                }
            }

            // COMENTADO TEMPORALMENTE PARA EVITAR CONFLICTOS
            /*
            $('.btn-cantidad-modern').on('click', function(e) {
                e.preventDefault();
                console.log('¡CLICK DETECTADO EN BOTÓN!');

                const action = $(this).data('action');
                const id = $(this).data('id');
                const inputElement = $(`.cantidad-input-modern[data-id="${id}"]`);
                let currentValue = parseInt(inputElement.val());

                console.log('Botón clickeado:', action, 'ID:', id, 'Valor actual:', currentValue);
                console.log('Input encontrado:', inputElement.length);

                if (action === 'increase') {
                    currentValue++;
                } else if (action === 'decrease' && currentValue > 1) {
                    currentValue--;
                }

                console.log('Nuevo valor:', currentValue);
                inputElement.val(currentValue);
                inputElement.data('button-click', true);

                // Marcar como cambiado si es diferente al valor original
                const originalValue = parseInt(inputElement.data('original'));
                if (currentValue !== originalValue) {
                    inputElement.addClass('changed');

                    // Cancelar cualquier temporizador existente
                    if (updateTimers[id]) {
                        clearTimeout(updateTimers[id]);
                    }

                    // Configurar un nuevo temporizador (300ms de retraso)
                    updateTimers[id] = setTimeout(function() {
                        actualizarCantidad(id, currentValue);
                    }, 300);
                } else {
                    inputElement.removeClass('changed');
                }
            });
            */

            // Manejar cambio manual en el input (compatibilidad con ambos estilos)
            $('.cantidad-input, .cantidad-input-modern').on('change', function() {
                const id = $(this).data('id');
                const originalValue = parseInt($(this).data('original'));
                let currentValue = parseInt($(this).val());

                // Validar valor mínimo
                if (currentValue < 1 || isNaN(currentValue)) {
                    currentValue = 1;
                    $(this).val(1);
                }

                // Marcar como cambiado si es diferente al valor original
                if (currentValue !== originalValue) {
                    $(this).addClass('changed');

                    // Cancelar cualquier temporizador existente
                    if (updateTimers[id]) {
                        clearTimeout(updateTimers[id]);
                    }

                    // Configurar un nuevo temporizador (500ms de retraso para cambios manuales)
                    updateTimers[id] = setTimeout(function() {
                        actualizarCantidad(id, currentValue);
                    }, 500);
                } else {
                    $(this).removeClass('changed');
                }
            });

            // Animación para los botones de eliminar y vaciar carrito
            $('form[action="backend/eliminar_carrito.php"] button, form[action="backend/vaciar_carrito.php"] button').on('click', function() {
                $(this).addClass('pulse');
            });

            // Animación para el botón de completar compra
            $('form[action="backend/completar_compra.php"] button').on('click', function() {
                $(this).addClass('pulse');
                M.toast({html: '<i class="material-icons left">shopping_cart</i> Procesando tu compra...', classes: 'blue', displayLength: 2000});
            });

            // COMENTADO TEMPORALMENTE - Efecto de onda (ripple) para los botones de cantidad
            /*
            $('.btn-cantidad-modern').on('mousedown', function(e) {
                const button = $(this);
                const diameter = Math.max(button.outerWidth(), button.outerHeight());
                const radius = diameter / 2;

                const x = e.pageX - button.offset().left - radius;
                const y = e.pageY - button.offset().top - radius;

                const ripple = $('<span class="ripple"></span>');
                ripple.css({
                    width: diameter + 'px',
                    height: diameter + 'px',
                    top: y + 'px',
                    left: x + 'px'
                });

                button.append(ripple);

                setTimeout(function() {
                    ripple.remove();
                }, 600);
            });
            */

            // Inicializar tooltips para el botón de copiar código
            $('.codigo-pedido-copy').tooltip();
        });

        // Función para copiar el código de pedido al portapapeles
        function copiarCodigoPedido(codigo) {
            // Crear un elemento de texto temporal
            const tempInput = document.createElement('input');
            tempInput.value = codigo;
            document.body.appendChild(tempInput);

            // Seleccionar y copiar el texto
            tempInput.select();
            document.execCommand('copy');

            // Eliminar el elemento temporal
            document.body.removeChild(tempInput);

            // Mostrar mensaje de éxito
            M.toast({html: '<i class="material-icons left">check_circle</i> Código copiado al portapapeles', classes: 'green', displayLength: 2000});

            // Cambiar el tooltip temporalmente
            const tooltipElement = document.querySelector('.codigo-pedido-copy');
            const instance = M.Tooltip.getInstance(tooltipElement);

            if (instance) {
                const originalText = instance.options.tooltip;
                instance.options.tooltip = '¡Copiado!';
                instance.open();

                setTimeout(function() {
                    instance.options.tooltip = originalText;
                }, 2000);
            }
        }
    </script>

    <style>
        .producto-carrito {
            display: flex;
            align-items: center;
        }

        .producto-carrito img {
            margin-right: 10px;
        }

        /* Estilos antiguos mantenidos por compatibilidad */
        .cantidad-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cantidad-input {
            width: 50px;
            margin: 0 8px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 0 5px;
            height: 36px;
        }

        /* Nuevos estilos modernos para el selector de cantidad */
        .cantidad-container-modern {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
            border-radius: 30px;
            padding: 2px;
            width: 120px;
            height: 40px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .cantidad-container-modern:hover {
            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.2);
            background-color: #e3f2fd;
        }

        .btn-cantidad-modern {
            background: none;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #2196F3;
            transition: all 0.2s ease;
            padding: 0;
            z-index: 2;
        }

        .btn-cantidad-modern:hover {
            background-color: #2196F3;
            color: white;
        }

        .btn-cantidad-modern:active {
            transform: scale(0.9);
        }

        /* Efecto de onda (ripple) */
        .btn-cantidad-modern {
            position: relative;
            overflow: hidden;
        }

        .btn-cantidad-modern .ripple {
            position: absolute;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.7);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple {
            to {
                transform: scale(2.5);
                opacity: 0;
            }
        }

        .btn-cantidad-modern.decrease {
            margin-right: 2px;
        }

        .btn-cantidad-modern.increase {
            margin-left: 2px;
        }

        .cantidad-input-wrapper {
            position: relative;
            flex: 1;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .cantidad-input-modern {
            width: 100%;
            height: 100%;
            border: none;
            text-align: center;
            font-size: 16px;
            font-weight: 500;
            color: #333;
            background: transparent;
            padding: 0;
            margin: 0;
            -moz-appearance: textfield;
        }

        .cantidad-input-modern::-webkit-inner-spin-button,
        .cantidad-input-modern::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .cantidad-input-modern:focus {
            outline: none;
            box-shadow: none;
        }

        .cantidad-input-modern.changed {
            color: #2196F3;
            font-weight: bold;
            animation: pulse-light 1s infinite;
        }

        @keyframes pulse-light {
            0% {
                background-color: transparent;
            }
            50% {
                background-color: rgba(33, 150, 243, 0.1);
            }
            100% {
                background-color: transparent;
            }
        }

        /* Estilos para el código de pedido */
        .codigo-pedido-container {
            display: flex;
            align-items: center;
            background-color: #e3f2fd;
            border-radius: 8px;
            padding: 12px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .codigo-pedido-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(33, 150, 243, 0.1) 0%, rgba(33, 150, 243, 0) 50%);
            z-index: 0;
        }

        .codigo-pedido-label {
            display: flex;
            align-items: center;
            font-size: 16px;
            color: #1565C0;
            margin-right: 15px;
            font-weight: 500;
            z-index: 1;
        }

        .codigo-pedido-label i {
            margin-right: 8px;
            font-size: 24px;
        }

        .codigo-pedido-value {
            font-size: 20px;
            font-weight: 700;
            color: #0D47A1;
            letter-spacing: 1.5px;
            background-color: white;
            padding: 6px 15px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
            position: relative;
            z-index: 1;
            flex-grow: 1;
            text-align: center;
            font-family: 'Roboto Mono', monospace;
            border-left: 4px solid #2196F3;
            animation: highlight-code 2s ease-in-out infinite;
        }

        @keyframes highlight-code {
            0%, 100% {
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
            }
            50% {
                box-shadow: 0 2px 10px rgba(33, 150, 243, 0.4);
            }
        }

        .codigo-pedido-copy {
            margin-left: 15px;
            cursor: pointer;
            background-color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
            transition: all 0.2s ease;
            z-index: 1;
        }

        .codigo-pedido-copy i {
            color: #2196F3;
            font-size: 18px;
        }

        .codigo-pedido-copy:hover {
            background-color: #2196F3;
            transform: scale(1.1);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16);
        }

        .codigo-pedido-copy:hover i {
            color: white;
        }

        .codigo-pedido-copy:active {
            transform: scale(0.95);
        }

        /* Estilos responsivos para el código de pedido */
        @media (max-width: 600px) {
            .codigo-pedido-container {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px;
            }

            .codigo-pedido-label {
                margin-right: 0;
                margin-bottom: 10px;
            }

            .codigo-pedido-value {
                width: 100%;
                margin-bottom: 10px;
            }

            .codigo-pedido-copy {
                margin-left: auto;
                margin-right: auto;
            }
        }

        .acciones-container {
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .btn-actualizar {
            transition: transform 0.3s ease;
        }

        .btn-actualizar.rotate {
            transform: rotate(360deg);
        }

        .cantidad-input.changed {
            background-color: #e3f2fd;
            border-color: #2196F3;
        }

        .subtotal-dolares, .subtotal-bolivares {
            transition: background-color 0.3s ease;
        }

        .highlight {
            background-color: #e3f2fd;
            transition: background-color 0.5s ease;
        }

        #toast-container {
            top: auto !important;
            right: auto !important;
            bottom: 10%;
            left: 50%;
            transform: translateX(-50%);
        }
    </style>
</body>

</html>
