/**
 * Script específico para la página de catálogo
 * Maneja las funcionalidades de imágenes, filtros y otras interacciones
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando funcionalidades de catálogo...');

    // Inicializar todos los modales
    var modals = document.querySelectorAll('.modal');
    if (modals.length > 0) {
        M.Modal.init(modals, {
            dismissible: true,
            opacity: 0.8,
            inDuration: 300,
            outDuration: 200
        });
    }

    // Inicializar selects
    var selects = document.querySelectorAll('select');
    if (selects.length > 0) {
        M.FormSelect.init(selects);
    }

    // Inicializar parallax
    var parallax = document.querySelectorAll('.parallax');
    if (parallax.length > 0) {
        M.Parallax.init(parallax);
    }

    // Inicializar tooltips
    var tooltips = document.querySelectorAll('.tooltipped');
    if (tooltips.length > 0) {
        M.Tooltip.init(tooltips);
    }

    // Inicializar carruseles
    var carousels = document.querySelectorAll('.carousel');
    if (carousels.length > 0) {
        M.Carousel.init(carousels, {
            fullWidth: true,
            indicators: true
        });
    }

    // Manejar la eliminación de imágenes
    setupImageRemoval();

    // Manejar la previsualización de imágenes
    setupImagePreview();

    // Manejar los filtros
    setupFilters();

    // Manejar la búsqueda
    setupSearch();
});

/**
 * Configura la funcionalidad para eliminar imágenes
 */
function setupImageRemoval() {
    const removeButtons = document.querySelectorAll('.remove-image');

    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const imagePath = this.getAttribute('data-imagen');
            const imageItem = this.closest('.image-item');

            if (confirm('¿Estás seguro de que deseas eliminar esta imagen?')) {
                // Crear un formulario oculto para enviar la solicitud
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'backend/gestionar_imagenes.php';
                form.style.display = 'none';

                const accionInput = document.createElement('input');
                accionInput.type = 'hidden';
                accionInput.name = 'accion';
                accionInput.value = 'eliminar_imagen';

                const imagenInput = document.createElement('input');
                imagenInput.type = 'hidden';
                imagenInput.name = 'imagen_path';
                imagenInput.value = imagePath;

                const idProductoInput = document.createElement('input');
                idProductoInput.type = 'hidden';
                idProductoInput.name = 'id_producto';
                idProductoInput.value = imageItem.closest('form').querySelector('input[name="id_producto"]').value;

                form.appendChild(accionInput);
                form.appendChild(imagenInput);
                form.appendChild(idProductoInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    });
}

/**
 * Configura la previsualización de imágenes al subirlas
 */
function setupImagePreview() {
    const imageInputs = document.querySelectorAll('.image-upload');

    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const previewContainer = this.closest('.file-field').nextElementSibling;

            if (previewContainer && previewContainer.classList.contains('image-preview')) {
                previewContainer.innerHTML = '';

                if (this.files && this.files.length > 0) {
                    for (let i = 0; i < this.files.length; i++) {
                        if (i >= 5) break; // Máximo 5 imágenes

                        const file = this.files[i];
                        if (!file.type.match('image.*')) continue;

                        const reader = new FileReader();
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';

                        reader.onload = function(e) {
                            previewItem.innerHTML = `
                                <img src="${e.target.result}" alt="Vista previa">
                                <span class="file-name">${file.name}</span>
                            `;
                        };

                        reader.readAsDataURL(file);
                        previewContainer.appendChild(previewItem);
                    }
                }
            }
        });
    });
}

/**
 * Configura los filtros de productos
 */
function setupFilters() {
    // Filtro por categoría
    const categoriaCheckboxes = document.querySelectorAll('.categoria-checkbox');

    categoriaCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.value === 'todas' && this.checked) {
                // Si se selecciona "Todas", desmarcar las demás
                categoriaCheckboxes.forEach(cb => {
                    if (cb.value !== 'todas') {
                        cb.checked = false;
                    }
                });
            } else if (this.checked) {
                // Si se selecciona una categoría específica, desmarcar "Todas"
                const todasCheckbox = document.querySelector('.categoria-checkbox[value="todas"]');
                if (todasCheckbox) {
                    todasCheckbox.checked = false;
                }
            }

            // Si no hay ninguna categoría seleccionada, seleccionar "Todas"
            const anyChecked = Array.from(categoriaCheckboxes).some(cb => cb.checked);
            if (!anyChecked) {
                const todasCheckbox = document.querySelector('.categoria-checkbox[value="todas"]');
                if (todasCheckbox) {
                    todasCheckbox.checked = true;
                }
            }

            aplicarFiltros();
        });
    });

    // Filtro por precio
    const priceRange = document.getElementById('price-range');
    if (priceRange) {
        priceRange.addEventListener('input', function() {
            const selectedPrice = document.getElementById('selected-price');
            if (selectedPrice) {
                selectedPrice.textContent = '$' + Number(this.value).toLocaleString();
            }
            aplicarFiltros();
        });
    }

    // Filtro por disponibilidad
    const disponibilidadRadios = document.querySelectorAll('.disponibilidad-radio');
    disponibilidadRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            aplicarFiltros();
        });
    });

    // Botón para limpiar filtros
    const limpiarFiltrosBtn = document.getElementById('limpiar-filtros');
    if (limpiarFiltrosBtn) {
        limpiarFiltrosBtn.addEventListener('click', function() {
            // Restablecer categorías
            const todasCheckbox = document.querySelector('.categoria-checkbox[value="todas"]');
            if (todasCheckbox) {
                todasCheckbox.checked = true;
            }
            categoriaCheckboxes.forEach(cb => {
                if (cb.value !== 'todas') {
                    cb.checked = false;
                }
            });

            // Restablecer precio
            if (priceRange) {
                priceRange.value = priceRange.max;
                const selectedPrice = document.getElementById('selected-price');
                if (selectedPrice) {
                    selectedPrice.textContent = '$' + Number(priceRange.max).toLocaleString();
                }
            }

            // Restablecer disponibilidad
            const todosRadio = document.querySelector('.disponibilidad-radio[value="todos"]');
            if (todosRadio) {
                todosRadio.checked = true;
            }

            // Limpiar búsqueda
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.value = '';
            }

            aplicarFiltros();
        });
    }
}

/**
 * Configura la búsqueda de productos
 */
function setupSearch() {
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            aplicarFiltros();
        });
    }
}

/**
 * Aplica todos los filtros a los productos
 */
function aplicarFiltros() {
    const productos = document.querySelectorAll('.producto-item');

    // Obtener valores de filtros
    const categoriasSeleccionadas = Array.from(document.querySelectorAll('.categoria-checkbox:checked')).map(cb => cb.value);
    const precioMaximo = document.getElementById('price-range')?.value || Infinity;
    const disponibilidad = document.querySelector('.disponibilidad-radio:checked')?.value || 'todos';
    const busqueda = document.getElementById('search')?.value.toLowerCase() || '';

    let productosVisibles = 0;

    productos.forEach(producto => {
        let mostrar = true;

        // Filtrar por categoría
        if (!categoriasSeleccionadas.includes('todas')) {
            const categoriaProducto = producto.getAttribute('data-categoria');
            if (!categoriasSeleccionadas.includes(categoriaProducto)) {
                mostrar = false;
            }
        }

        // Filtrar por precio
        const precioProducto = parseFloat(producto.getAttribute('data-precio') || 0);
        if (precioProducto > precioMaximo) {
            mostrar = false;
        }

        // Filtrar por disponibilidad
        if (disponibilidad !== 'todos') {
            const disponibilidadProducto = producto.getAttribute('data-disponibilidad');
            if (disponibilidadProducto !== disponibilidad) {
                mostrar = false;
            }
        }

        // Filtrar por búsqueda
        if (busqueda) {
            const nombreProducto = producto.querySelector('.product-title')?.textContent.toLowerCase() || '';
            const descripcionProducto = producto.querySelector('.product-description-text')?.textContent.toLowerCase() || '';
            if (!nombreProducto.includes(busqueda) && !descripcionProducto.includes(busqueda)) {
                mostrar = false;
            }
        }

        // Mostrar u ocultar el producto
        producto.style.display = mostrar ? '' : 'none';

        if (mostrar) {
            productosVisibles++;
        }
    });

    // Mostrar mensaje si no hay productos visibles
    const noProductosMensaje = document.getElementById('no-productos-mensaje');
    if (noProductosMensaje) {
        noProductosMensaje.style.display = productosVisibles === 0 ? 'block' : 'none';
    }

    // Ocultar paginación si se están aplicando filtros
    const paginacion = document.querySelector('.pagination');
    if (paginacion && (busqueda || !categoriasSeleccionadas.includes('todas') || disponibilidad !== 'todos')) {
        paginacion.closest('.row').style.display = 'none';
    } else if (paginacion) {
        paginacion.closest('.row').style.display = '';
    }
}
