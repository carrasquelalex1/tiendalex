<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir el archivo de conexión a la base de datos y funciones necesarias
$root_path = __DIR__ . '/../../';
require_once $root_path . 'backend/config/db.php';
require_once $root_path . 'helpers/carrito/carrito_functions.php';
require_once $root_path . 'helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Registrar información de sesión para depuración
error_log("bodycatalogo.php - SESSION al inicio: " . print_r($_SESSION, true));

// Verificar si el usuario está logueado
$usuario_logueado = esta_logueado();
$usuario_id = obtener_usuario_id();

// Verificar si el usuario es administrador o cliente
// Estas funciones ahora son más robustas y verificarán en la base de datos si es necesario
$es_admin = es_admin();
$es_cliente = es_cliente();
$codigo_pedido = '';

// Registrar información de roles para depuración
error_log("bodycatalogo.php - Usuario ID: " . ($usuario_id ? $usuario_id : 'no logueado') .
          ", rol_codigo: " . (isset($_SESSION['rol_codigo']) ? $_SESSION['rol_codigo'] : 'no definido') .
          ", es_admin: " . ($es_admin ? 'true' : 'false') .
          ", es_cliente: " . ($es_cliente ? 'true' : 'false'));

// Si el usuario está logueado y es cliente, asegurarse de que tenga un código de pedido
if ($usuario_logueado && $es_cliente) {
    if (isset($_SESSION['codigo_pedido'])) {
        $codigo_pedido = $_SESSION['codigo_pedido'];
        error_log("bodycatalogo.php - Código de pedido existente: " . $codigo_pedido);
    } else {
        $codigo_pedido = generarCodigoPedidoUnico();
        $_SESSION['codigo_pedido'] = $codigo_pedido;
        error_log("bodycatalogo.php - Se generó un nuevo código de pedido: " . $codigo_pedido);
    }

    // Verificar si el código de pedido está asociado con el usuario en el carrito
    $sql = "SELECT COUNT(*) as count FROM carrito WHERE usuario_id = ? AND pedido = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $usuario_id, $codigo_pedido);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    error_log("bodycatalogo.php - Verificación de código de pedido: usuario_id=$usuario_id, codigo_pedido=$codigo_pedido, count=" . $row['count']);
}

// Funciones para obtener datos
function obtenerTasaActual() {
    global $conn;

    $sql = "SELECT * FROM tasa WHERE current = 1 ORDER BY id_tasa DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

// Obtener la tasa actual
$tasa_actual = obtenerTasaActual();

// Función para obtener todas las categorías
function obtenerCategorias() {
    global $conn;

    $sql = "SELECT * FROM categorias ORDER BY nombre_categoria ASC";
    $result = $conn->query($sql);

    $categorias = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categorias[] = $row;
        }
    }

    return $categorias;
}

// Obtener todas las categorías
$categorias = obtenerCategorias();

// Configuración de paginación
$productos_por_pagina = 6; // Número de productos por página
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

// Calcular el offset para la consulta SQL
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// Consulta para obtener el número total de productos
$sql_total = "SELECT COUNT(*) as total FROM productos_tienda";
$result_total = $conn->query($sql_total);
$row_total = $result_total->fetch_assoc();
$total_productos = $row_total['total'];

// Calcular el número total de páginas
$total_paginas = ceil($total_productos / $productos_por_pagina);
if ($pagina_actual > $total_paginas && $total_paginas > 0) {
    $pagina_actual = $total_paginas;
    $offset = ($pagina_actual - 1) * $productos_por_pagina;
}

// Consulta para obtener los productos de la página actual
$sql = "SELECT * FROM productos_tienda ORDER BY id_producto DESC LIMIT $offset, $productos_por_pagina";
$result = $conn->query($sql);

// Verificar si hay productos
$productos = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
}
?>

<main class="container catalogo-main">
    <!-- Incluir el componente de mensajes -->
    <?php include_once $root_path . 'frontend/includes/messages.php'; ?>

    <!-- Encabezado con hero section -->
    <div class="hero-section">
        <div class="container">
            <div class="row hero-content">
                <div class="col s12 center-align">
                    <h1 class="hero-title">Catálogo de Productos</h1>
                    <h5 class="hero-subtitle">Descubre nuestra selección de productos de alta calidad</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de filtros y búsqueda -->
    <div class="section white">
        <div class="container">
            <div class="row">
                <div class="filters-section">
                    <div class="row">
                        <div class="col s12 m8">
                            <h4 class="section-title">Nuestros Productos</h4>
                        </div>
                        <div class="col s12 m4">
                            <div class="input-field">
                                <i class="material-icons prefix">search</i>
                                <input id="search" type="text" class="validate">
                                <label for="search">Buscar productos</label>
                            </div>
                        </div>
                    </div>

            <div class="row">
                <div class="col s12 m3">
                    <div class="filtros-panel">
                        <div class="filter-title">Categorías</div>
                        <div class="categorias-container">
                            <p>
                                <label>
                                    <input type="checkbox" class="filled-in categoria-checkbox" value="todas" checked="checked" />
                                    <span>Todas</span>
                                </label>
                            </p>
                            <?php if (count($categorias) > 0): ?>
                                <?php foreach ($categorias as $categoria): ?>
                                    <p>
                                        <label>
                                            <input type="checkbox" class="filled-in categoria-checkbox" value="<?php echo $categoria['id_categoria']; ?>" />
                                            <span class="categoria-nombre" title="<?php echo $categoria['nombre_categoria']; ?>"><?php echo $categoria['nombre_categoria']; ?></span>
                                        </label>
                                    </p>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="note">
                                    <i class="tiny material-icons">info</i>
                                    No hay categorías disponibles.
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="divider" style="margin: 20px 0;"></div>

                        <div class="filter-title">Precio</div>
                        <p class="range-field">
                            <?php
                            // Obtener el precio máximo de los productos
                            $precio_maximo = 1000; // Valor predeterminado
                            foreach ($productos as $producto) {
                                if (!empty($producto['precio_producto']) && $producto['precio_producto'] > $precio_maximo) {
                                    $precio_maximo = ceil($producto['precio_producto']);
                                }
                            }
                            // Redondear a la centena superior
                            $precio_maximo = ceil($precio_maximo / 100) * 100;
                            if ($precio_maximo < 100) $precio_maximo = 100;
                            ?>
                            <input type="range" id="price-range" min="0" max="<?php echo $precio_maximo; ?>" value="<?php echo $precio_maximo; ?>" />
                            <div class="price-labels">
                                <span id="min-price">$0</span>
                                <span id="max-price">$<?php echo number_format($precio_maximo, 0); ?></span>
                            </div>
                            <div class="selected-price">
                                Hasta: <span id="selected-price">$<?php echo number_format($precio_maximo, 0); ?></span>
                            </div>
                        </p>

                        <div class="divider" style="margin: 20px 0;"></div>

                        <div class="filter-title">Disponibilidad</div>
                        <p>
                            <label>
                                <input name="disponibilidad" type="radio" value="todos" class="disponibilidad-radio" checked />
                                <span>Todos</span>
                            </label>
                        </p>
                        <p>
                            <label>
                                <input name="disponibilidad" type="radio" value="disponible" class="disponibilidad-radio" />
                                <span>En stock</span>
                            </label>
                        </p>
                        <p>
                            <label>
                                <input name="disponibilidad" type="radio" value="agotado" class="disponibilidad-radio" />
                                <span>Agotados</span>
                            </label>
                        </p>

                        <!-- Botón para limpiar filtros -->
                        <div class="center-align" style="margin-top: 20px;">
                            <button id="limpiar-filtros" class="btn waves-effect waves-light">
                                <i class="material-icons left">refresh</i>
                                Limpiar filtros
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col s12 m9">
                    <div class="row">
                        <?php if (count($productos) > 0): ?>
                            <?php foreach ($productos as $producto): ?>
                                <div class="col s12 m6 l4 producto-item"
                                     data-categoria="<?php echo !empty($producto['categoria_id']) ? $producto['categoria_id'] : 'sin-categoria'; ?>"
                                     data-precio="<?php echo !empty($producto['precio_producto']) ? $producto['precio_producto'] : '0'; ?>"
                                     data-disponibilidad="<?php echo $producto['existencia_producto'] > 0 ? 'disponible' : 'agotado'; ?>">
                                    <div class="card hoverable">
                                        <div class="card-image">
                                            <?php
                                            // Obtener las imágenes del producto
                                            $imagenes = [];
                                            if (!empty($producto['imagen_producto'])) {
                                                $imagenes[] = $producto['imagen_producto'];
                                            }
                                            if (!empty($producto['imagen_producto2'])) {
                                                $imagenes[] = $producto['imagen_producto2'];
                                            }
                                            if (!empty($producto['imagen_producto3'])) {
                                                $imagenes[] = $producto['imagen_producto3'];
                                            }
                                            if (!empty($producto['imagen_producto4'])) {
                                                $imagenes[] = $producto['imagen_producto4'];
                                            }
                                            if (!empty($producto['imagen_producto5'])) {
                                                $imagenes[] = $producto['imagen_producto5'];
                                            }
                                            ?>

                                            <?php if (count($imagenes) > 0): ?>
                                                <!-- Imagen principal -->
                                                <img src="<?php echo $imagenes[0]; ?>" alt="<?php echo $producto['nombre_producto']; ?>">

                                                <!-- Indicador de número de fotos -->
                                                <?php if (count($imagenes) > 1): ?>
                                                    <a href="#modal-galeria-<?php echo $producto['id_producto']; ?>" class="modal-trigger foto-count-indicator">
                                                        <i class="material-icons tiny">photo</i>
                                                        <span><?php echo count($imagenes); ?></span>
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <img src="backend/images/a.jpg" alt="<?php echo $producto['nombre_producto']; ?>">
                                            <?php endif; ?>

                                            <!-- Botón para editar imágenes (solo admin) -->
                                            <?php if ($es_admin): ?>
                                            <a href="#modal-editar-imagenes-<?php echo $producto['id_producto']; ?>" class="modal-trigger edit-image-icon">
                                                <i class="material-icons">edit</i>
                                            </a>
                                            <?php endif; ?>

                                            <!-- Botón para ampliar imagen -->
                                            <a href="#modal-galeria-<?php echo $producto['id_producto']; ?>" class="modal-trigger fullscreen-image-icon">
                                                <i class="material-icons">fullscreen</i>
                                            </a>
                                        </div>
                                        <div class="card-content">
                                            <h5 class="product-title"><?php echo $producto['nombre_producto']; ?></h5>
                                            <div class="description-container">
                                                <?php if (!empty($producto['descripcion_producto'])): ?>
                                                    <p class="product-description">
                                                        <span class="product-description-text"><?php echo $producto['descripcion_producto']; ?></span>
                                                        <?php if ($es_admin): ?>
                                                        <a href="#modal-editar-descripcion-<?php echo $producto['id_producto']; ?>" class="modal-trigger edit-description-icon">
                                                            <i class="tiny material-icons">edit</i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </p>
                                                <?php else: ?>
                                                    <p class="product-description">
                                                        <span class="product-description-text">Sin descripción disponible.</span>
                                                        <?php if ($es_admin): ?>
                                                        <a href="#modal-editar-descripcion-<?php echo $producto['id_producto']; ?>" class="modal-trigger edit-description-icon">
                                                            <i class="tiny material-icons">add_circle</i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!empty($producto['precio_producto'])): ?>
                                                <div class="price-section">
                                                    <p class="price">
                                                        Precio: $<?php echo number_format($producto['precio_producto'], 2); ?>
                                                        <?php if ($es_admin): ?>
                                                        <a href="#modal-editar-precio-<?php echo $producto['id_producto']; ?>" class="modal-trigger edit-price-icon">
                                                            <i class="tiny material-icons">edit</i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </p>

                                                    <?php if (!empty($producto['precio_bolivares'])): ?>
                                                        <p class="price-bs">
                                                            Precio: Bs. <?php echo number_format($producto['precio_bolivares'], 2); ?>
                                                            <?php if (!empty($producto['tasa'])): ?>
                                                                <span class="tasa-info">(Tasa: <?php echo number_format($producto['tasa'], 2); ?> Bs/$)</span>
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php else: ?>
                                                        <p class="price-bs">Precio en Bs. no disponible</p>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Mostrar categoría y permitir cambiarla -->
                                                <div class="category-container">
                                                    <?php
                                                    $categoria_nombre = "Sin categoría";
                                                    foreach ($categorias as $cat) {
                                                        if ($cat['id_categoria'] == $producto['categoria_id']) {
                                                            $categoria_nombre = $cat['nombre_categoria'];
                                                            break;
                                                        }
                                                    }
                                                    ?>
                                                    <span class="category-label">Categoría:</span>
                                                    <div class="category-value-container">
                                                        <span class="category-value" title="<?php echo $categoria_nombre; ?>">
                                                            <?php echo $categoria_nombre; ?>
                                                        </span>
                                                        <?php if ($es_admin): ?>
                                                        <a href="#modal-asignar-categoria-<?php echo $producto['id_producto']; ?>" class="modal-trigger edit-category-icon">
                                                            <i class="tiny material-icons">edit</i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="price-section">
                                                    <p class="price">
                                                        Precio no disponible
                                                        <?php if ($es_admin): ?>
                                                        <a href="#modal-editar-precio-<?php echo $producto['id_producto']; ?>" class="modal-trigger edit-price-icon">
                                                            <i class="tiny material-icons">add_circle</i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>

                                            <style>
                                            /* Estilos para el botón de disponibilidad */
                                            .card.hovered .availability-btn {
                                                transform: translateY(-2px);
                                                box-shadow: 0 4px 8px rgba(0,0,0,0.25);
                                            }

                                            .availability-btn {
                                                border: 2px solid transparent !important;
                                                position: relative;
                                                overflow: hidden;
                                                transition: all 0.3s ease !important;
                                                border-radius: 20px !important;
                                                display: inline-flex !important;
                                                align-items: center;
                                                justify-content: center;
                                                padding: 0 16px !important;
                                                height: 36px !important;
                                                line-height: 1.5 !important;
                                            }

                                            /* Efecto de borde con gradiente */
                                            .availability-btn:not(.red)::after {
                                                content: '';
                                                position: absolute;
                                                top: -2px;
                                                left: -2px;
                                                right: -2px;
                                                bottom: -2px;
                                                background: linear-gradient(45deg, var(--success-color), var(--accent-color), var(--success-color));
                                                background-size: 200% 200%;
                                                z-index: 0;
                                                border-radius: 20px;
                                                opacity: 0;
                                                transition: opacity 0.3s ease;
                                            }

                                            .card.hovered .availability-btn:not(.red)::after {
                                                opacity: 0.8;
                                                animation: gradientBorder 2s linear infinite;
                                            }

                                            @keyframes gradientBorder {
                                                0% { background-position: 0% 50%; }
                                                50% { background-position: 100% 50%; }
                                                100% { background-position: 0% 50%; }
                                            }

                                            /* Contenido del botón */
                                            .availability-btn > * {
                                                position: relative;
                                                z-index: 1;
                                                display: flex !important;
                                                align-items: center;
                                                gap: 5px;
                                            }

                                            /* Estilo para el texto y la cantidad */
                                            .stock-text, .stock-quantity {
                                                display: inline-block !important;
                                                position: relative;
                                                z-index: 1;
                                            }

                                            /* Deshabilitar la animación de pulso por defecto */
                                            .availability-btn.pulse {
                                                animation: none !important;
                                            }

                                            /* Mostrar animación solo al hacer hover en la tarjeta */
                                            .card.hovered .availability-btn.pulse:not(.red) {
                                                animation: pulse 1.5s infinite !important;
                                            }

                                            @keyframes pulse {
                                                0% { transform: translateY(-2px) scale(1); box-shadow: 0 4px 8px rgba(0,0,0,0.25); }
                                                50% { transform: translateY(-2px) scale(1.02); box-shadow: 0 6px 12px rgba(0,0,0,0.3); }
                                                100% { transform: translateY(-2px) scale(1); box-shadow: 0 4px 8px rgba(0,0,0,0.25); }
                                            }
                                            </style>

                                            <div class="center-align" style="margin: 10px 0;">
                                                <div class="stock-container" style="position: relative; display: inline-flex; align-items: center; gap: 8px;">
                                                    <button class="btn waves-effect waves-light availability-btn <?php echo $producto['existencia_producto'] > 0 ? 'pulse' : 'red'; ?>"
                                                            data-is-available="<?php echo $producto['existencia_producto'] > 0 ? 'true' : 'false'; ?>">
                                                        <div class="button-content">
                                                            <i class="material-icons" style="font-size: 18px;">
                                                                <?php echo $producto['existencia_producto'] > 0 ? 'check_circle' : 'remove_circle'; ?>
                                                            </i>
                                                            <span class="stock-text">
                                                                <?php echo $producto['existencia_producto'] > 0 ? 'DISPONIBLE' : 'AGOTADO'; ?>
                                                            </span>
                                                            <?php if ($producto['existencia_producto'] > 0): ?>
                                                            <span class="stock-quantity">(<?php echo $producto['existencia_producto']; ?>)</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </button>

                                                    <script>
                                                    // Manejar eventos de hover en las tarjetas de productos
                                                    document.addEventListener('DOMContentLoaded', function() {
                                                        const cards = document.querySelectorAll('.card');

                                                        cards.forEach(card => {
                                                            card.addEventListener('mouseenter', function() {
                                                                this.classList.add('hovered');
                                                            });

                                                            card.addEventListener('mouseleave', function() {
                                                                this.classList.remove('hovered');
                                                            });
                                                        });
                                                    });
                                                    </script>

                                                    <?php if ($es_admin): ?>
                                                    <a href="#modal-editar-existencia-<?php echo $producto['id_producto']; ?>"
                                                       class="modal-trigger edit-stock-icon"
                                                       style="color: #757575;
                                                              transition: all 0.3s ease;
                                                              display: inline-flex;
                                                              align-items: center;
                                                              justify-content: center;
                                                              width: 30px;
                                                              height: 30px;
                                                              border-radius: 50%;
                                                              background-color: #f5f5f5;
                                                              text-decoration: none;"
                                                       onmouseover="this.style.backgroundColor='#e0e0e0';"
                                                       onmouseout="this.style.backgroundColor='#f5f5f5';">
                                                        <i class="material-icons" style="font-size: 16px;">edit</i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <style>
                                            @keyframes pulse {
                                                0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); }
                                                70% { transform: scale(1.05); box-shadow: 0 0 0 8px rgba(76, 175, 80, 0); }
                                                100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
                                            }
                                            /* Mantener compatibilidad con estilos dinámicos */
                                            .stock-badge {
                                                cursor: default;
                                            }
                                            .stock-badge.in-stock {
                                                --availability-color: #4CAF50;
                                            }
                                            .stock-badge.out-of-stock {
                                                --danger-color: #f44336;
                                            }
                                            </style>
                                        </div>
                                        <div class="card-action">
                                            <?php if ($es_cliente): ?>
                                            <!-- Formulario para agregar al carrito (solo para clientes) -->
                                            <form action="backend/agregar_carrito.php" method="POST" class="agregar-carrito-form">
                                                <input type="hidden" name="producto_id" value="<?php echo $producto['id_producto']; ?>">
                                                <input type="hidden" name="codigo_pedido" value="<?php echo $codigo_pedido; ?>">
                                                <input type="hidden" name="redirect_to_cart" value="true">

                                                <div class="row" style="margin-bottom: 0;">
                                                    <div class="col s6">
                                                        <?php if ($producto['existencia_producto'] > 0): ?>
                                                            <input type="number" name="cantidad" min="1" max="<?php echo $producto['existencia_producto']; ?>" value="1" class="center-align" required>
                                                        <?php else: ?>
                                                            <input type="number" value="0" class="center-align" disabled>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col s6">
                                                        <button type="submit" class="btn waves-effect waves-light <?php echo $producto['existencia_producto'] <= 0 || !$usuario_logueado ? 'disabled' : ''; ?>">
                                                            <i class="material-icons left">add_shopping_cart</i>AGREGAR
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                            <?php elseif (!$usuario_logueado): ?>
                                            <!-- Mensaje para usuarios no logueados -->
                                            <div class="row" style="margin-bottom: 0; margin-top: 0; padding: 0;">
                                                <div class="col s12 center-align" style="position: relative; display: block; width: 100%; padding: 5px 0; margin: 0; overflow: visible; min-height: 50px; box-sizing: border-box; float: none; clear: both;">
                                                    <a href="login.php" class="btn waves-effect waves-light" style="position: relative !important; display: block !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 8px 12px !important; float: none !important; clear: both !important; z-index: 1000 !important; transform: none !important; left: auto !important; right: auto !important; top: auto !important; bottom: auto !important; box-sizing: border-box !important; overflow: hidden !important; text-overflow: ellipsis !important; white-space: nowrap !important; font-size: 0.8rem !important; text-align: center !important; text-decoration: none !important; line-height: normal !important; height: auto !important; min-height: 36px !important;">
                                                        <i class="material-icons left">login</i>INICIAR SESIÓN PARA COMPRAR
                                                    </a>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                            <div class="row" style="margin-bottom: 0; margin-top: 10px;">
                                                <div class="col s12 right-align">
                                                    <?php if ($es_admin): ?>
                                                    <a href="#modal-eliminar-producto-<?php echo $producto['id_producto']; ?>" class="modal-trigger red-text">
                                                        <i class="tiny material-icons">delete</i> Eliminar producto
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Modal para asignar categoría -->
                                <div id="modal-asignar-categoria-<?php echo $producto['id_producto']; ?>" class="modal">
                                    <div class="modal-content">
                                        <h4>Asignar Categoría</h4>
                                        <div class="divider"></div>
                                        <div class="row">
                                            <form id="form-asignar-categoria-<?php echo $producto['id_producto']; ?>" class="col s12" action="backend/gestionar_categorias.php" method="POST">
                                                <input type="hidden" name="accion" value="asignar_categoria">
                                                <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">

                                                <div class="row">
                                                    <div class="col s12">
                                                        <h5><?php echo $producto['nombre_producto']; ?></h5>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="input-field col s12">
                                                        <i class="material-icons prefix">category</i>
                                                        <select id="categoria_id_<?php echo $producto['id_producto']; ?>" name="categoria_id" class="validate">
                                                            <option value="" <?php echo empty($producto['categoria_id']) ? 'selected' : ''; ?>>Sin categoría</option>
                                                            <?php foreach ($categorias as $cat): ?>
                                                                <option value="<?php echo $cat['id_categoria']; ?>" <?php echo $producto['categoria_id'] == $cat['id_categoria'] ? 'selected' : ''; ?>>
                                                                    <?php echo $cat['nombre_categoria']; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <label for="categoria_id_<?php echo $producto['id_producto']; ?>">Categoría</label>
                                                    </div>
                                                </div>

                                                <?php if (count($categorias) == 0): ?>
                                                <div class="row">
                                                    <div class="col s12">
                                                        <p class="note">
                                                            <i class="tiny material-icons">info</i>
                                                            No hay categorías disponibles. <a href="#modal-categorias" class="modal-trigger">Haga clic aquí</a> para agregar categorías.
                                                        </p>
                                                    </div>
                                                </div>
                                                <?php endif; ?>

                                                <div class="row">
                                                    <div class="col s12 right-align">
                                                        <button class="btn waves-effect waves-light red modal-close" type="button">
                                                            Cancelar
                                                        </button>
                                                        <button class="btn waves-effect waves-light" type="submit">
                                                            Asignar Categoría
                                                            <i class="material-icons right">save</i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal para editar descripción -->
                                <div id="modal-editar-descripcion-<?php echo $producto['id_producto']; ?>" class="modal">
                                    <div class="modal-content">
                                        <h4>Editar Descripción del Producto</h4>
                                        <div class="divider"></div>
                                        <div class="row">
                                            <form id="form-editar-descripcion-<?php echo $producto['id_producto']; ?>" class="col s12" action="backend/actualizar_descripcion.php" method="POST">
                                                <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">

                                                <div class="row">
                                                    <div class="col s12">
                                                        <h5><?php echo $producto['nombre_producto']; ?></h5>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="input-field col s12">
                                                        <i class="material-icons prefix">description</i>
                                                        <textarea id="descripcion_producto_<?php echo $producto['id_producto']; ?>" name="descripcion_producto" class="materialize-textarea"><?php echo $producto['descripcion_producto']; ?></textarea>
                                                        <label for="descripcion_producto_<?php echo $producto['id_producto']; ?>" class="<?php echo !empty($producto['descripcion_producto']) ? 'active' : ''; ?>">Descripción del Producto</label>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col s12 right-align">
                                                        <button class="btn waves-effect waves-light red modal-close" type="button">
                                                            Cancelar
                                                        </button>
                                                        <?php if (!empty($producto['descripcion_producto'])): ?>
                                                        <button class="btn waves-effect waves-light red" type="submit" name="eliminar" value="1">
                                                            Eliminar Descripción
                                                            <i class="material-icons right">delete</i>
                                                        </button>
                                                        <?php endif; ?>
                                                        <button class="btn waves-effect waves-light" type="submit">
                                                            Guardar Descripción
                                                            <i class="material-icons right">save</i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal para gestionar imágenes -->
                                <div id="modal-editar-imagenes-<?php echo $producto['id_producto']; ?>" class="modal">
                                    <div class="modal-content">
                                        <h4>Gestionar Imágenes del Producto</h4>
                                        <div class="divider"></div>
                                        <div class="row">
                                            <form id="form-editar-imagenes-<?php echo $producto['id_producto']; ?>" class="col s12" action="backend/gestionar_imagenes.php" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">
                                                <input type="hidden" name="accion" value="actualizar_imagenes">

                                                <div class="row">
                                                    <div class="col s12">
                                                        <h5><?php echo $producto['nombre_producto']; ?></h5>
                                                    </div>
                                                </div>

                                                <!-- Mostrar imágenes actuales si existen -->
                                                <div class="row">
                                                    <div class="col s12">
                                                        <h6>Imágenes actuales</h6>
                                                        <div class="image-gallery">
                                                            <?php
                                                            // Obtener las imágenes del producto
                                                            $imagenes = [];
                                                            if (!empty($producto['imagen_producto'])) {
                                                                $imagenes[] = $producto['imagen_producto'];
                                                            }
                                                            if (!empty($producto['imagen_producto2'])) {
                                                                $imagenes[] = $producto['imagen_producto2'];
                                                            }
                                                            if (!empty($producto['imagen_producto3'])) {
                                                                $imagenes[] = $producto['imagen_producto3'];
                                                            }
                                                            if (!empty($producto['imagen_producto4'])) {
                                                                $imagenes[] = $producto['imagen_producto4'];
                                                            }
                                                            if (!empty($producto['imagen_producto5'])) {
                                                                $imagenes[] = $producto['imagen_producto5'];
                                                            }

                                                            if (count($imagenes) > 0):
                                                                foreach ($imagenes as $index => $imagen):
                                                            ?>
                                                                <div class="image-item">
                                                                    <img src="<?php echo $imagen; ?>" alt="Imagen <?php echo $index + 1; ?>">
                                                                    <a href="#!" class="remove-image" data-imagen="<?php echo $imagen; ?>">
                                                                        <i class="tiny material-icons">close</i>
                                                                    </a>
                                                                    <input type="hidden" name="imagenes_actuales[]" value="<?php echo $imagen; ?>">
                                                                </div>
                                                            <?php
                                                                endforeach;
                                                            else:
                                                            ?>
                                                                <p class="note">
                                                                    <i class="tiny material-icons">info</i>
                                                                    No hay imágenes para este producto.
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Subir nuevas imágenes -->
                                                <div class="row">
                                                    <div class="col s12">
                                                        <h6>Subir nuevas imágenes (máximo 5 en total)</h6>
                                                        <div class="file-field input-field">
                                                            <div class="btn">
                                                                <span>Seleccionar</span>
                                                                <input type="file" name="nuevas_imagenes[]" multiple accept="image/*" <?php echo count($imagenes) >= 5 ? 'disabled' : ''; ?>>
                                                            </div>
                                                            <div class="file-path-wrapper">
                                                                <input class="file-path validate" type="text" placeholder="Seleccione una o varias imágenes">
                                                            </div>
                                                        </div>
                                                        <p class="note">
                                                            <i class="tiny material-icons">info</i>
                                                            Formatos permitidos: JPG, JPEG, PNG. Tamaño máximo: 2MB por imagen.
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col s12 right-align">
                                                        <button class="btn waves-effect waves-light red modal-close" type="button">
                                                            Cancelar
                                                        </button>
                                                        <button class="btn waves-effect waves-light" type="submit">
                                                            Guardar Cambios
                                                            <i class="material-icons right">save</i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal para editar existencia -->
                                <div id="modal-editar-existencia-<?php echo $producto['id_producto']; ?>" class="modal">
                                    <div class="modal-content">
                                        <h4>Editar Disponibilidad del Producto</h4>
                                        <div class="divider"></div>
                                        <div class="row">
                                            <form id="form-editar-existencia-<?php echo $producto['id_producto']; ?>" class="col s12" action="backend/actualizar_existencia.php" method="POST">
                                                <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">

                                                <div class="row">
                                                    <div class="col s12">
                                                        <h5><?php echo $producto['nombre_producto']; ?></h5>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="input-field col s12">
                                                        <i class="material-icons prefix">inventory</i>
                                                        <input id="existencia_producto_<?php echo $producto['id_producto']; ?>" name="existencia_producto" type="number" min="0" step="1" class="validate" value="<?php echo $producto['existencia_producto']; ?>" required>
                                                        <label for="existencia_producto_<?php echo $producto['id_producto']; ?>">Existencia/Disponibilidad</label>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col s12 right-align">
                                                        <button class="btn waves-effect waves-light red modal-close" type="button">
                                                            Cancelar
                                                        </button>
                                                        <button class="btn waves-effect waves-light" type="submit">
                                                            Actualizar Disponibilidad
                                                            <i class="material-icons right">save</i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal para editar precio -->
                                <div id="modal-editar-precio-<?php echo $producto['id_producto']; ?>" class="modal">
                                    <div class="modal-content">
                                        <h4>Editar Precio del Producto</h4>
                                        <div class="divider"></div>
                                        <div class="row">
                                            <form id="form-editar-precio-<?php echo $producto['id_producto']; ?>" class="col s12" action="backend/actualizar_precio.php" method="POST">
                                                <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">

                                                <div class="row">
                                                    <div class="col s12">
                                                        <h5><?php echo $producto['nombre_producto']; ?></h5>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="input-field col s12">
                                                        <i class="material-icons prefix">attach_money</i>
                                                        <input id="precio_producto_<?php echo $producto['id_producto']; ?>" name="precio_producto" type="number" step="0.01" min="0" class="validate" value="<?php echo $producto['precio_producto']; ?>" required>
                                                        <label for="precio_producto_<?php echo $producto['id_producto']; ?>">Precio en $ (USD)</label>
                                                    </div>
                                                </div>

                                                <?php if ($tasa_actual): ?>
                                                <div class="row">
                                                    <div class="col s12">
                                                        <p class="note">
                                                            <i class="tiny material-icons">info</i>
                                                            El precio en bolívares se calculará automáticamente usando la tasa actual (<?php echo number_format($tasa_actual['valor_tasa'], 2); ?> Bs/$).
                                                        </p>
                                                    </div>
                                                </div>
                                                <?php endif; ?>

                                                <div class="row">
                                                    <div class="col s12 right-align">
                                                        <button class="btn waves-effect waves-light red modal-close" type="button">
                                                            Cancelar
                                                        </button>
                                                        <button class="btn waves-effect waves-light" type="submit">
                                                            Actualizar Precio
                                                            <i class="material-icons right">send</i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal de galería para ver imágenes ampliadas -->
                                <?php
                                // Obtener las imágenes del producto nuevamente
                                $imagenes = [];
                                if (!empty($producto['imagen_producto'])) {
                                    $imagenes[] = $producto['imagen_producto'];
                                }
                                if (!empty($producto['imagen_producto2'])) {
                                    $imagenes[] = $producto['imagen_producto2'];
                                }
                                if (!empty($producto['imagen_producto3'])) {
                                    $imagenes[] = $producto['imagen_producto3'];
                                }
                                if (!empty($producto['imagen_producto4'])) {
                                    $imagenes[] = $producto['imagen_producto4'];
                                }
                                if (!empty($producto['imagen_producto5'])) {
                                    $imagenes[] = $producto['imagen_producto5'];
                                }

                                if (count($imagenes) > 0):
                                ?>
                                <div id="modal-galeria-<?php echo $producto['id_producto']; ?>" class="modal modal-galeria">
                                    <div class="modal-content">
                                        <h5 class="center-align" style="margin-top: 0; font-size: 1.2rem;"><?php echo $producto['nombre_producto']; ?></h5>

                                        <!-- Contenedor de imagen principal -->
                                        <div class="galeria-imagen-principal">
                                            <img src="<?php echo $imagenes[0]; ?>" alt="<?php echo $producto['nombre_producto']; ?>" id="galeria-img-principal-<?php echo $producto['id_producto']; ?>">

                                            <?php if (count($imagenes) > 1): ?>
                                            <div class="galeria-controles">
                                                <a href="#!" class="galeria-prev" data-producto-id="<?php echo $producto['id_producto']; ?>">
                                                    <i class="material-icons">chevron_left</i>
                                                </a>
                                                <a href="#!" class="galeria-next" data-producto-id="<?php echo $producto['id_producto']; ?>">
                                                    <i class="material-icons">chevron_right</i>
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Miniaturas (solo si hay más de una imagen) -->
                                        <?php if (count($imagenes) > 1): ?>
                                        <div class="galeria-miniaturas">
                                            <?php foreach ($imagenes as $index => $imagen): ?>
                                            <div class="galeria-miniatura <?php echo $index === 0 ? 'active' : ''; ?>"
                                                 data-index="<?php echo $index; ?>"
                                                 data-producto-id="<?php echo $producto['id_producto']; ?>"
                                                 data-imagen="<?php echo $imagen; ?>">
                                                <img src="<?php echo $imagen; ?>" alt="Miniatura <?php echo $index + 1; ?>">
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Información del producto -->
                                        <div class="galeria-info-producto">
                                            <?php if (!empty($producto['descripcion_producto'])): ?>
                                                <p class="galeria-descripcion"><?php echo $producto['descripcion_producto']; ?></p>
                                            <?php endif; ?>

                                            <div class="galeria-precios">
                                                <p class="price">Precio: $<?php echo number_format($producto['precio_producto'], 2); ?></p>
                                                <?php if (!empty($producto['precio_bolivares'])): ?>
                                                    <p class="price-bs">Precio: Bs. <?php echo number_format($producto['precio_bolivares'], 2); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer" style="padding: 4px 15px;">
                                        <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cerrar</a>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Modal para eliminar producto -->
                                <div id="modal-eliminar-producto-<?php echo $producto['id_producto']; ?>" class="modal">
                                    <div class="modal-content">
                                        <h4>Eliminar Producto</h4>
                                        <div class="divider"></div>
                                        <p>¿Está seguro de que desea eliminar el producto <strong><?php echo $producto['nombre_producto']; ?></strong>?</p>
                                        <p class="red-text">Esta acción no se puede deshacer. Se eliminarán todas las imágenes asociadas al producto.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <form action="backend/eliminar_producto.php" method="POST">
                                            <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">

                                            <button class="btn waves-effect waves-light grey modal-close" type="button">
                                                Cancelar
                                            </button>
                                            <button class="btn waves-effect waves-light red" type="submit">
                                                Eliminar
                                                <i class="material-icons right">delete</i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col s12">
                                <div class="card-panel blue lighten-4">
                                    <?php if ($es_admin): ?>
                                    <span class="blue-text text-darken-4">No hay productos disponibles. ¡Agrega algunos usando el botón "+" en la esquina inferior derecha!</span>
                                    <?php else: ?>
                                    <span class="blue-text text-darken-4">No hay productos disponibles en este momento.</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Mensaje para cuando no hay productos que coincidan con los filtros -->
                        <div id="no-productos-mensaje" class="col s12" style="display: none;">
                            <div class="card-panel yellow lighten-4">
                                <div class="center-align" style="padding: 20px;">
                                    <i class="large material-icons" style="color: #FF9800; font-size: 4rem;">search_off</i>
                                    <h5 style="color: #FF9800;">No se encontraron productos</h5>
                                    <p style="color: #795548;">No hay productos que coincidan con los filtros seleccionados.</p>
                                    <button id="limpiar-filtros-mensaje" class="btn waves-effect waves-light" style="margin-top: 15px;" onclick="limpiarFiltros(); return false;">
                                        <i class="material-icons left">refresh</i>
                                        Limpiar filtros
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                    <div class="row">
                        <div class="col s12 center-align">
                            <ul class="pagination">
                                <!-- Botón anterior -->
                                <?php if ($pagina_actual > 1): ?>
                                <li class="waves-effect"><a href="catalogo.php?pagina=<?php echo $pagina_actual - 1; ?>"><i class="material-icons">chevron_left</i></a></li>
                                <?php else: ?>
                                <li class="disabled"><a href="#!"><i class="material-icons">chevron_left</i></a></li>
                                <?php endif; ?>

                                <!-- Números de página -->
                                <?php
                                // Determinar el rango de páginas a mostrar
                                $rango = 2; // Mostrar 2 páginas antes y después de la actual
                                $inicio_rango = max(1, $pagina_actual - $rango);
                                $fin_rango = min($total_paginas, $pagina_actual + $rango);

                                // Mostrar primera página si no está en el rango
                                if ($inicio_rango > 1) {
                                    echo '<li class="waves-effect"><a href="catalogo.php?pagina=1">1</a></li>';
                                    if ($inicio_rango > 2) {
                                        echo '<li class="disabled"><a href="#!">...</a></li>';
                                    }
                                }

                                // Mostrar páginas en el rango
                                for ($i = $inicio_rango; $i <= $fin_rango; $i++) {
                                    if ($i == $pagina_actual) {
                                        echo '<li class="active blue"><a href="#!">' . $i . '</a></li>';
                                    } else {
                                        echo '<li class="waves-effect"><a href="catalogo.php?pagina=' . $i . '">' . $i . '</a></li>';
                                    }
                                }

                                // Mostrar última página si no está en el rango
                                if ($fin_rango < $total_paginas) {
                                    if ($fin_rango < $total_paginas - 1) {
                                        echo '<li class="disabled"><a href="#!">...</a></li>';
                                    }
                                    echo '<li class="waves-effect"><a href="catalogo.php?pagina=' . $total_paginas . '">' . $total_paginas . '</a></li>';
                                }
                                ?>

                                <!-- Botón siguiente -->
                                <?php if ($pagina_actual < $total_paginas): ?>
                                <li class="waves-effect"><a href="catalogo.php?pagina=<?php echo $pagina_actual + 1; ?>"><i class="material-icons">chevron_right</i></a></li>
                                <?php else: ?>
                                <li class="disabled"><a href="#!"><i class="material-icons">chevron_right</i></a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón flotante para agregar producto, gestionar tasa y categorías (solo para administradores) -->
    <?php if ($es_admin): ?>
    <div class="fixed-action-btn admin-float">
        <a class="btn-floating btn-large">
            <i class="large material-icons">menu</i>
        </a>
        <ul>
            <li><a class="btn-floating modal-trigger" href="#modal-categorias" title="Gestionar Categorías"><i class="material-icons">category</i></a></li>
            <li><a class="btn-floating modal-trigger" href="#modal-tasa" title="Gestionar Tasa de Cambio"><i class="material-icons">attach_money</i></a></li>
            <li><a class="btn-floating modal-trigger" href="#modal-agregar-producto" title="Agregar Producto"><i class="material-icons">add</i></a></li>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Modal para gestionar categorías -->
    <div id="modal-categorias" class="modal modal-fixed-footer">
        <div class="modal-content">
            <h4>Gestionar Categorías</h4>
            <div class="divider"></div>

            <!-- Formulario para agregar nueva categoría -->
            <div class="row">
                <form id="form-agregar-categoria" class="col s12" action="backend/gestionar_categorias.php" method="POST">
                    <input type="hidden" name="accion" value="agregar">

                    <div class="row">
                        <div class="input-field col s9">
                            <i class="material-icons prefix">label</i>
                            <input id="nombre_categoria" name="nombre_categoria" type="text" class="validate" required>
                            <label for="nombre_categoria">Nombre de la Categoría</label>
                        </div>
                        <div class="input-field col s3">
                            <button class="btn waves-effect waves-light purple" type="submit">
                                Agregar
                                <i class="material-icons right">add</i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="divider"></div>

            <!-- Lista de categorías existentes -->
            <h5>Categorías Existentes</h5>

            <?php if (count($categorias) > 0): ?>
                <table class="striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td><?php echo $categoria['id_categoria']; ?></td>
                                <td><?php echo $categoria['nombre_categoria']; ?></td>
                                <td>
                                    <a href="#modal-editar-categoria-<?php echo $categoria['id_categoria']; ?>" class="btn-small waves-effect waves-light blue edit-category-btn" data-category-id="<?php echo $categoria['id_categoria']; ?>">
                                        <i class="material-icons">edit</i>
                                    </a>
                                    <a href="#modal-eliminar-categoria-<?php echo $categoria['id_categoria']; ?>" class="btn-small waves-effect waves-light red delete-category-btn" data-category-id="<?php echo $categoria['id_categoria']; ?>">
                                        <i class="material-icons">delete</i>
                                    </a>
                                </td>
                            </tr>

                            <!-- Los modales de edición se moverán fuera del bucle -->

                            <!-- Los modales de eliminación se moverán fuera del bucle -->
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="card-panel yellow lighten-4">
                    <span class="orange-text text-darken-4">
                        No hay categorías disponibles. Agregue una nueva categoría utilizando el formulario de arriba.
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cerrar</a>
        </div>
    </div>

    <!-- Modal para gestionar tasa de cambio -->
    <div id="modal-tasa" class="modal">
        <div class="modal-content">
            <h4>Gestionar Tasa de Cambio</h4>
            <div class="divider"></div>

            <?php if ($tasa_actual): ?>
                <div class="card-panel blue lighten-4">
                    <span class="blue-text text-darken-4">
                        Tasa actual: <strong><?php echo number_format($tasa_actual['valor_tasa'], 2); ?> Bs/$</strong>
                        (Actualizada: <?php echo date('d/m/Y H:i', strtotime($tasa_actual['updated_at'])); ?>)
                    </span>
                </div>
            <?php else: ?>
                <div class="card-panel yellow lighten-4">
                    <span class="orange-text text-darken-4">
                        No hay una tasa de cambio configurada. Por favor, establezca una tasa.
                    </span>
                </div>
            <?php endif; ?>

            <div class="row">
                <form id="form-tasa" class="col s12" action="backend/gestionar_tasa.php" method="POST">
                    <input type="hidden" name="accion" value="actualizar_tasa">

                    <div class="row">
                        <div class="input-field col s12">
                            <i class="material-icons prefix">monetization_on</i>
                            <input id="valor_tasa" name="valor_tasa" type="number" step="0.01" min="0.01" class="validate" required>
                            <label for="valor_tasa">Valor de la Tasa (Bs/$)</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col s12">
                            <p>
                                <strong>Nota:</strong> Al actualizar la tasa de cambio, se recalcularán automáticamente los precios en bolívares de todos los productos basados en sus precios en dólares.
                            </p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col s12 right-align">
                            <button class="btn waves-effect waves-light red modal-close" type="button">
                                Cancelar
                            </button>
                            <button class="btn waves-effect waves-light green" type="submit">
                                Actualizar Tasa
                                <i class="material-icons right">send</i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para agregar producto -->
    <div id="modal-agregar-producto" class="modal">
        <div class="modal-content">
            <h4>Agregar Nuevo Producto</h4>
            <div class="divider"></div>
            <div class="row">
                <form id="form-agregar-producto" class="col s12" action="backend/procesar_producto.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="input-field col s12">
                            <i class="material-icons prefix">shopping_bag</i>
                            <input id="nombre_producto" name="nombre_producto" type="text" class="validate" required>
                            <label for="nombre_producto">Nombre del Producto</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field col s12">
                            <i class="material-icons prefix">description</i>
                            <textarea id="descripcion_producto" name="descripcion_producto" class="materialize-textarea"></textarea>
                            <label for="descripcion_producto">Descripción del Producto (opcional)</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field col s6">
                            <i class="material-icons prefix">attach_money</i>
                            <input id="precio_producto" name="precio_producto" type="number" step="0.01" min="0" class="validate">
                            <label for="precio_producto">Precio en $ (opcional)</label>
                        </div>
                        <div class="input-field col s6">
                            <i class="material-icons prefix">monetization_on</i>
                            <input id="precio_bolivares" name="precio_bolivares" type="number" step="0.01" min="0" class="validate">
                            <label for="precio_bolivares">Precio en Bs. (opcional)</label>
                            <?php if ($tasa_actual): ?>
                                <span class="helper-text">
                                    Tasa actual: <?php echo number_format($tasa_actual['valor_tasa'], 2); ?> Bs/$
                                    <a href="#!" id="calcular-precio-bs" class="blue-text">Calcular</a>
                                </span>
                            <?php else: ?>
                                <span class="helper-text">
                                    No hay tasa configurada. <a href="#modal-tasa" class="modal-trigger blue-text">Configurar tasa</a>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field col s12">
                            <i class="material-icons prefix">category</i>
                            <select id="categoria_id" name="categoria_id" class="validate">
                                <option value="">Sin categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id_categoria']; ?>"><?php echo $categoria['nombre_categoria']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="categoria_id">Categoría (opcional)</label>
                            <?php if (count($categorias) == 0): ?>
                                <span class="helper-text">
                                    No hay categorías disponibles. <a href="#modal-categorias" class="modal-trigger purple-text">Agregar categorías</a>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field col s12">
                            <i class="material-icons prefix">inventory</i>
                            <input id="existencia_producto" name="existencia_producto" type="number" min="0" step="1" value="0" class="validate">
                            <label for="existencia_producto">Existencia/Disponibilidad</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col s12">
                            <h6><i class="material-icons prefix">photo_library</i> Imágenes del Producto (máximo 5)</h6>
                            <div class="file-field input-field">
                                <div class="btn orange">
                                    <span>Seleccionar</span>
                                    <input type="file" name="imagenes_producto[]" multiple accept="image/*">
                                </div>
                                <div class="file-path-wrapper">
                                    <input class="file-path validate" type="text" placeholder="Seleccione una o varias imágenes">
                                </div>
                            </div>
                            <p class="note">
                                <i class="tiny material-icons">info</i>
                                Formatos permitidos: JPG, JPEG, PNG. Tamaño máximo: 2MB por imagen.
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col s12 right-align">
                            <button class="btn waves-effect waves-light red modal-close" type="button">
                                Cancelar
                            </button>
                            <button class="btn waves-effect waves-light blue" type="submit">
                                Guardar
                                <i class="material-icons right">send</i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modales de edición de categorías -->
    <?php foreach ($categorias as $categoria): ?>
    <div id="modal-editar-categoria-<?php echo $categoria['id_categoria']; ?>" class="modal">
        <div class="modal-content">
            <h4>Editar Categoría</h4>
            <div class="divider"></div>
            <div class="row">
                <form id="form-editar-categoria-<?php echo $categoria['id_categoria']; ?>" class="col s12" action="backend/gestionar_categorias.php" method="POST">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_categoria" value="<?php echo $categoria['id_categoria']; ?>">

                    <div class="row">
                        <div class="input-field col s12">
                            <i class="material-icons prefix">label</i>
                            <input id="nombre_categoria_<?php echo $categoria['id_categoria']; ?>" name="nombre_categoria" type="text" class="validate" value="<?php echo $categoria['nombre_categoria']; ?>" required>
                            <label for="nombre_categoria_<?php echo $categoria['id_categoria']; ?>">Nombre de la Categoría</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col s12 right-align">
                            <button class="btn waves-effect waves-light red modal-close" type="button">
                                Cancelar
                            </button>
                            <button class="btn waves-effect waves-light blue" type="submit">
                                Actualizar
                                <i class="material-icons right">save</i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Modales de eliminación de categorías -->
    <?php foreach ($categorias as $categoria): ?>
    <div id="modal-eliminar-categoria-<?php echo $categoria['id_categoria']; ?>" class="modal">
        <div class="modal-content">
            <h4>Eliminar Categoría</h4>
            <div class="divider"></div>
            <p>¿Está seguro de que desea eliminar la categoría <strong><?php echo $categoria['nombre_categoria']; ?></strong>?</p>
            <p class="red-text">Esta acción no se puede deshacer. Si la categoría está asignada a algún producto, no se podrá eliminar.</p>
        </div>
        <div class="modal-footer">
            <form action="backend/gestionar_categorias.php" method="POST">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id_categoria" value="<?php echo $categoria['id_categoria']; ?>">

                <button class="btn waves-effect waves-light grey modal-close" type="button">
                    Cancelar
                </button>
                <button class="btn waves-effect waves-light red" type="submit">
                    Eliminar
                    <i class="material-icons right">delete</i>
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</main>

<!-- Inicialización de componentes -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM completamente cargado');

    // Inicializar parallax
    var elemsParallax = document.querySelectorAll('.parallax');
    var instancesParallax = M.Parallax.init(elemsParallax);

    // Inicializar todos los componentes de Materialize
    M.AutoInit();

    // Inicializar carruseles
    var elemsCarousel = document.querySelectorAll('.carousel.carousel-slider');
    var instancesCarousel = M.Carousel.init(elemsCarousel, {
        fullWidth: true,
        indicators: true,
        duration: 200
    });

    // Manejar botones de navegación del carrusel
    document.querySelectorAll('.carousel-prev').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('data-target');
            var carousel = document.getElementById(targetId);
            var instance = M.Carousel.getInstance(carousel);
            if (instance) {
                instance.prev();
            }
        });
    });

    document.querySelectorAll('.carousel-next').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('data-target');
            var carousel = document.getElementById(targetId);
            var instance = M.Carousel.getInstance(carousel);
            if (instance) {
                instance.next();
            }
        });
    });

    // Manejar clic en miniaturas de la galería
    function inicializarGaleria() {
        console.log('Inicializando galería de imágenes...');

        // Manejar clic en miniaturas
        document.querySelectorAll('.galeria-miniatura').forEach(function(miniatura) {
            // Eliminar eventos anteriores para evitar duplicados
            miniatura.removeEventListener('click', manejarClicMiniatura);
            miniatura.addEventListener('click', manejarClicMiniatura);
        });

        // Verificar que los botones de navegación estén funcionando
        document.querySelectorAll('.galeria-prev, .galeria-next').forEach(function(btn) {
            console.log('Botón de navegación encontrado:', btn);
        });

        // Configurar botones de navegación
        configurarBotonesNavegacion();
    }

    // Función para manejar el clic en miniaturas
    function manejarClicMiniatura() {
        console.log('Clic en miniatura');

        // Obtener datos
        var productoId = this.getAttribute('data-producto-id');
        var imagen = this.getAttribute('data-imagen');

        console.log('Producto ID:', productoId);
        console.log('Imagen:', imagen);

        // Actualizar imagen principal
        var imgPrincipal = document.getElementById('galeria-img-principal-' + productoId);
        if (imgPrincipal) {
            console.log('Actualizando imagen principal');
            imgPrincipal.src = imagen;
        } else {
            console.error('No se encontró la imagen principal para el producto', productoId);
        }

        // Actualizar clase activa
        document.querySelectorAll('.galeria-miniatura[data-producto-id="' + productoId + '"]').forEach(function(m) {
            m.classList.remove('active');
        });
        this.classList.add('active');
    }

    // Inicializar galería
    inicializarGaleria();

    // Función para manejar la navegación de la galería
    function configurarBotonesNavegacion() {
        console.log('Configurando botones de navegación de la galería');

        // Manejar botón anterior
        document.querySelectorAll('.galeria-prev').forEach(function(btn) {
            // Eliminar eventos anteriores para evitar duplicados
            btn.removeEventListener('click', navegarAnterior);
            btn.addEventListener('click', navegarAnterior);
        });

        // Manejar botón siguiente
        document.querySelectorAll('.galeria-next').forEach(function(btn) {
            // Eliminar eventos anteriores para evitar duplicados
            btn.removeEventListener('click', navegarSiguiente);
            btn.addEventListener('click', navegarSiguiente);
        });
    }

    // Función para navegar a la imagen anterior
    function navegarAnterior(e) {
        e.preventDefault();
        console.log('Navegando a imagen anterior');

        var productoId = this.getAttribute('data-producto-id');
        console.log('Producto ID:', productoId);

        var miniaturas = document.querySelectorAll('.galeria-miniatura[data-producto-id="' + productoId + '"]');
        var activaActual = document.querySelector('.galeria-miniatura.active[data-producto-id="' + productoId + '"]');

        console.log('Miniaturas encontradas:', miniaturas.length);
        console.log('Miniatura activa:', activaActual ? 'Sí' : 'No');

        if (miniaturas.length > 0 && activaActual) {
            // Encontrar el índice actual
            var indiceActual = -1;
            miniaturas.forEach(function(m, i) {
                if (m === activaActual) {
                    indiceActual = i;
                }
            });

            console.log('Índice actual:', indiceActual);

            // Calcular el índice anterior
            var indiceAnterior = (indiceActual - 1 + miniaturas.length) % miniaturas.length;
            console.log('Índice anterior:', indiceAnterior);

            // Simular clic en la miniatura anterior
            console.log('Haciendo clic en miniatura anterior');
            miniaturas[indiceAnterior].click();
        }
    }

    // Función para navegar a la imagen siguiente
    function navegarSiguiente(e) {
        e.preventDefault();
        console.log('Navegando a imagen siguiente');

        var productoId = this.getAttribute('data-producto-id');
        console.log('Producto ID:', productoId);

        var miniaturas = document.querySelectorAll('.galeria-miniatura[data-producto-id="' + productoId + '"]');
        var activaActual = document.querySelector('.galeria-miniatura.active[data-producto-id="' + productoId + '"]');

        console.log('Miniaturas encontradas:', miniaturas.length);
        console.log('Miniatura activa:', activaActual ? 'Sí' : 'No');

        if (miniaturas.length > 0 && activaActual) {
            // Encontrar el índice actual
            var indiceActual = -1;
            miniaturas.forEach(function(m, i) {
                if (m === activaActual) {
                    indiceActual = i;
                }
            });

            console.log('Índice actual:', indiceActual);

            // Calcular el índice siguiente
            var indiceSiguiente = (indiceActual + 1) % miniaturas.length;
            console.log('Índice siguiente:', indiceSiguiente);

            // Simular clic en la miniatura siguiente
            console.log('Haciendo clic en miniatura siguiente');
            miniaturas[indiceSiguiente].click();
        }
    }

    // Configurar botones de navegación
    configurarBotonesNavegacion();

    // Inicializar modal
    var elemsModal = document.querySelectorAll('.modal');
    var instancesModal = M.Modal.init(elemsModal, {
        dismissible: true,
        opacity: 0.5,
        inDuration: 250,
        outDuration: 200,
        startingTop: '10%',
        endingTop: '10%',
        onOpenStart: function(modal) {
            console.log('Modal abierto:', modal.id);

            // Preparar el modal de galería
            if (modal.classList.contains('modal-galeria')) {
                console.log('Preparando modal de galería');

                // Asegurar que el modal tenga el tamaño correcto
                modal.style.height = 'auto';

                // Asegurar que la primera miniatura esté activa
                var productoId = modal.id.split('-')[2];
                console.log('Producto ID:', productoId);

                var miniaturas = modal.querySelectorAll('.galeria-miniatura');
                console.log('Miniaturas encontradas:', miniaturas.length);

                if (miniaturas.length > 0) {
                    // Activar la primera miniatura
                    miniaturas.forEach(function(m) {
                        m.classList.remove('active');
                    });
                    miniaturas[0].classList.add('active');

                    // Actualizar la imagen principal
                    var imgPrincipal = document.getElementById('galeria-img-principal-' + productoId);
                    if (imgPrincipal) {
                        console.log('Actualizando imagen principal en modal');
                        imgPrincipal.src = miniaturas[0].getAttribute('data-imagen');
                    } else {
                        console.error('No se encontró la imagen principal para el producto', productoId);
                    }

                    // Reinicializar los eventos de la galería
                    inicializarGaleria();
                }
            }
        },
        onOpenEnd: function(modal) {
            // Asegurarse de que los eventos estén correctamente asignados después de que el modal esté completamente abierto
            if (modal.classList.contains('modal-galeria')) {
                console.log('Modal de galería completamente abierto');
                setTimeout(inicializarGaleria, 100);
            }
        }
    });

    // Los botones flotantes se inicializan en floating-buttons.js

    // Inicializar selectores
    var elemsSelect = document.querySelectorAll('select');
    var instancesSelect = M.FormSelect.init(elemsSelect);

    // El manejo de mensajes ahora está en el componente messages.php

    // Ajustar el tamaño de la fuente para categorías con nombres largos
    document.querySelectorAll('.category-value').forEach(function(element) {
        var text = element.textContent.trim();
        var length = text.length;

        // Ajustar el tamaño de la fuente según la longitud del texto
        if (length > 20) {
            // Reducir el tamaño de la fuente para textos largos
            var fontSize = Math.max(0.7, 1 - (length - 20) * 0.02); // Reducir hasta un mínimo de 0.7em
            element.style.fontSize = fontSize + 'em';
        }
    });

    // Ajustar el tamaño de la fuente para descripciones largas
    document.querySelectorAll('.product-description-text').forEach(function(element) {
        var text = element.textContent.trim();
        var length = text.length;

        // Ajustar el tamaño de la fuente según la longitud del texto
        if (length > 50) {
            // Reducir el tamaño de la fuente para textos largos
            var fontSize = Math.max(0.75, 1 - (length - 50) * 0.001); // Reducir gradualmente según la longitud
            element.style.fontSize = fontSize + 'em';

            // Si el texto es muy largo, reducir aún más el interlineado
            if (length > 100) {
                element.style.lineHeight = '1.1';
            }
        }
    });

    // Manejar la eliminación de imágenes
    document.querySelectorAll('.remove-image').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            // Obtener el elemento padre (image-item)
            var imageItem = this.closest('.image-item');

            if (imageItem) {
                // Obtener la ruta de la imagen y el ID del producto
                var rutaImagen = imageItem.querySelector('input[name="imagenes_actuales[]"]').value;
                var idProducto = this.closest('form').querySelector('input[name="id_producto"]').value;

                // Confirmar la eliminación
                if (confirm('¿Está seguro de que desea eliminar esta imagen? Esta acción no se puede deshacer.')) {
                    // Enviar solicitud AJAX para eliminar la imagen
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'backend/eliminar_imagen.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);

                                if (response.success) {
                                    // Eliminar el elemento del DOM
                                    imageItem.remove();

                                    // Reorganizar los índices de las imágenes restantes
                                    var imageInputs = document.querySelectorAll('input[name="imagenes_actuales[]"]');
                                    var fileInput = document.querySelector('input[type="file"][name="nuevas_imagenes[]"]');

                                    // Habilitar el input de archivo si hay menos de 5 imágenes
                                    if (imageInputs.length < 5 && fileInput) {
                                        fileInput.disabled = false;
                                    }

                                    // Mostrar mensaje de confirmación
                                    M.toast({html: response.message, classes: 'green'});
                                } else {
                                    // Mostrar mensaje de error
                                    M.toast({html: response.message, classes: 'red'});
                                }
                            } catch (e) {
                                console.error('Error al procesar la respuesta:', e);
                                M.toast({html: 'Error al procesar la respuesta del servidor.', classes: 'red'});
                            }
                        } else {
                            M.toast({html: 'Error en la solicitud: ' + xhr.status, classes: 'red'});
                        }
                    };

                    xhr.onerror = function() {
                        M.toast({html: 'Error de conexión al servidor.', classes: 'red'});
                    };

                    // Enviar los datos
                    xhr.send('id_producto=' + encodeURIComponent(idProducto) + '&ruta_imagen=' + encodeURIComponent(rutaImagen));
                }
            }
        });
    });

    // Inicializar los inputs de archivo
    document.querySelectorAll('input[type="file"]').forEach(function(input) {
        input.addEventListener('change', function(e) {
            // Verificar si se seleccionaron archivos
            if (this.files.length > 0) {
                // Mostrar mensaje de confirmación
                M.toast({html: 'Imágenes seleccionadas. Recuerda guardar los cambios.', classes: 'blue'});
            }
        });
    });

    // Manejar botones de edición de categoría de manera simplificada
    document.querySelectorAll('.edit-category-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            // Obtener el ID de la categoría
            var categoryId = this.getAttribute('data-category-id');
            var modalId = 'modal-editar-categoria-' + categoryId;

            // Cerrar el modal de categorías primero
            var categoriesModal = M.Modal.getInstance(document.getElementById('modal-categorias'));
            if (categoriesModal) {
                categoriesModal.close();
            }

            // Abrir el modal de edición después de un breve retraso
            setTimeout(function() {
                var editModal = M.Modal.getInstance(document.getElementById(modalId));
                if (editModal) {
                    editModal.open();
                } else {
                    console.error('No se pudo encontrar el modal: ' + modalId);
                }
            }, 300);
        });
    });

    // Manejar botones de eliminación de categoría
    document.querySelectorAll('.delete-category-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            // Obtener el ID de la categoría
            var categoryId = this.getAttribute('data-category-id');
            var modalId = 'modal-eliminar-categoria-' + categoryId;

            // Cerrar el modal de categorías primero
            var categoriesModal = M.Modal.getInstance(document.getElementById('modal-categorias'));
            if (categoriesModal) {
                categoriesModal.close();
            }

            // Abrir el modal de eliminación después de un breve retraso
            setTimeout(function() {
                var deleteModal = M.Modal.getInstance(document.getElementById(modalId));
                if (deleteModal) {
                    deleteModal.open();
                } else {
                    console.error('No se pudo encontrar el modal: ' + modalId);
                }
            }, 300);
        });
    });

    // Calcular precio en bolívares automáticamente
    document.getElementById('calcular-precio-bs')?.addEventListener('click', function(e) {
        e.preventDefault();

        var precioDolares = document.getElementById('precio_producto').value;

        if (!precioDolares || isNaN(precioDolares) || precioDolares <= 0) {
            M.toast({html: 'Por favor, ingrese un precio válido en dólares primero.', classes: 'red'});
            return;
        }

        <?php if ($tasa_actual): ?>
            var tasaCambio = <?php echo $tasa_actual['valor_tasa']; ?>;
            var precioBs = parseFloat(precioDolares) * tasaCambio;
            document.getElementById('precio_bolivares').value = precioBs.toFixed(2);
            // Activar el label para que no se superponga con el valor
            M.updateTextFields();
            M.toast({html: 'Precio en bolívares calculado correctamente.', classes: 'green'});
        <?php else: ?>
            M.toast({html: 'No hay una tasa de cambio configurada.', classes: 'red'});
        <?php endif; ?>
    });
});
</script>

<!-- Estilos adicionales -->
<style>
.text-shadow {
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}
.parallax-container {
    color: white;
}
.card.hoverable {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card.hoverable:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 15px 0 rgba(0,0,0,0.24), 0 17px 50px 0 rgba(0,0,0,0.19);
}
.price-section {
    min-height: 60px; /* Altura fija para la sección de precios */
    display: flex;
    flex-direction: column;
    margin-bottom: 10px;
}
.price {
    font-weight: bold;
    color: #2196F3;
    margin: 10px 0 5px 0;
    display: flex;
    align-items: center;
}
.price-bs {
    font-weight: bold;
    color: #4CAF50;
    margin: 0 0 10px 0;
    font-size: 0.95em;
}
.tasa-info {
    font-size: 0.8em;
    font-weight: normal;
    color: #757575;
    display: block;
    margin-top: 2px;
}
.edit-price-icon {
    margin-left: 5px;
    vertical-align: middle;
}
.edit-price-icon i {
    font-size: 16px;
    color: #2196F3;
}
.note {
    color: #757575;
    font-size: 0.9em;
    font-style: italic;
}
.note i {
    vertical-align: middle;
    margin-right: 5px;
}
.col.s12.m6.l4 {
    display: flex;
    margin-bottom: 20px;
}
.card {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    margin: 0 0.5rem;
}
.card .card-image {
    height: 180px; /* Altura fija para todas las imágenes */
    overflow: hidden;
    position: relative;
    background-color: #f5f5f5; /* Color de fondo para imágenes pequeñas */
}
.card .card-image img {
    width: 100%;
    height: 100%;
    object-fit: contain; /* Mantener proporciones */
    max-height: 100%;
    max-width: 100%;
}
/* Estilos simplificados para el carrusel */
.carousel {
    height: 180px !important;
    overflow: hidden;
}
.carousel .carousel-item {
    width: 100% !important;
    height: 180px !important;
    position: absolute;
    top: 0;
    left: 0;
}
.carousel .carousel-item img {
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 180px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}
.carousel-controls {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 10px;
}
.carousel-prev, .carousel-next {
    pointer-events: auto;
    background-color: rgba(255, 255, 255, 0.7);
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 2px 0 rgba(0,0,0,0.14), 0 3px 1px -2px rgba(0,0,0,0.12), 0 1px 5px 0 rgba(0,0,0,0.2);
    z-index: 5;
}
.carousel-prev i, .carousel-next i {
    color: #333;
    font-size: 20px;
}
.fullscreen-image-icon {
    position: absolute;
    top: 10px;
    left: 10px;
    background-color: rgba(255, 255, 255, 0.8);
    border-radius: 50%;
    padding: 5px;
    box-shadow: 0 2px 2px 0 rgba(0,0,0,0.14), 0 3px 1px -2px rgba(0,0,0,0.12), 0 1px 5px 0 rgba(0,0,0,0.2);
    z-index: 10;
}
.fullscreen-image-icon i {
    font-size: 20px;
    color: #333;
}
.ver-galeria-btn {
    position: absolute;
    bottom: 40px;
    right: 10px;
    background-color: rgba(0, 0, 0, 0.6);
    color: white;
    border-radius: 20px;
    padding: 5px 10px;
    display: flex;
    align-items: center;
    z-index: 10;
    font-size: 12px;
}
.ver-galeria-btn i {
    font-size: 16px;
    margin-right: 5px;
}
.card .card-content {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    padding: 20px;
}
.product-title {
    font-size: 1.1rem;
    font-weight: 500;
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    max-height: 2.6rem;
}

.description-container {
    margin-bottom: 15px;
    height: 60px; /* Altura fija para la descripción */
    position: relative;
    overflow: hidden;
}
.product-description {
    margin: 0;
    display: flex;
    align-items: flex-start;
    max-height: 60px;
    overflow: hidden;
    font-size: 0.95em; /* Tamaño base de la fuente */
    line-height: 1.3;
}
.product-description-text {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3; /* Limitar a 3 líneas */
    -webkit-box-orient: vertical;
}
.edit-description-icon {
    margin-left: 5px;
    flex-shrink: 0;
}
.edit-description-icon i {
    font-size: 14px;
    color: #FF9800;
}
.card-content .center-align {
    margin-top: auto;
}
.category-container {
    margin-top: 5px;
    font-size: 0.9em;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    min-height: 24px; /* Altura mínima para la sección de categoría */
    margin-bottom: 10px;
}
.category-label {
    color: #757575;
    font-weight: bold;
    margin-right: 5px;
}
.category-value-container {
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 0; /* Importante para que el texto se recorte correctamente */
}
.category-value {
    color: #9c27b0;
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: calc(100% - 24px); /* Espacio para el icono */
    flex: 1;
    font-size: 0.9em; /* Tamaño base de la fuente */
    line-height: 1.2;
}
.edit-category-icon {
    margin-left: 5px;
    vertical-align: middle;
}
.edit-category-icon i {
    font-size: 14px;
    color: #9c27b0;
}
.category-title {
    font-weight: bold;
    margin-top: 15px;
    color: #2196F3;
}

.divider {
    margin: 10px 0;
}
.btn {
    margin: 5px 0;
}
.stock-container {
    position: relative;
    display: inline-block;
}
.edit-stock-icon {
    position: absolute;
    top: -10px;
    right: -15px;
    background-color: white;
    border-radius: 50%;
    padding: 2px;
    box-shadow: 0 2px 2px 0 rgba(0,0,0,0.14), 0 3px 1px -2px rgba(0,0,0,0.12), 0 1px 5px 0 rgba(0,0,0,0.2);
}
.edit-stock-icon i {
    font-size: 18px;
    color: #4CAF50;
}
.edit-image-icon {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: rgba(255, 255, 255, 0.8);
    border-radius: 50%;
    padding: 5px;
    box-shadow: 0 2px 2px 0 rgba(0,0,0,0.14), 0 3px 1px -2px rgba(0,0,0,0.12), 0 1px 5px 0 rgba(0,0,0,0.2);
    z-index: 10;
}
.edit-image-icon i {
    font-size: 24px;
    color: #FF9800;
}
.image-preview {
    width: 100%;
    height: 150px;
    object-fit: contain; /* Cambiado de 'cover' a 'contain' */
    margin-bottom: 10px;
    border-radius: 4px;
    background-color: #f5f5f5;
}
.image-gallery {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}
.image-item {
    position: relative;
    width: calc(33.33% - 10px);
    height: 100px;
    background-color: #f5f5f5;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.image-item img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain; /* Cambiado de 'cover' a 'contain' */
    border-radius: 4px;
}
.image-item .remove-image {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: white;
    border-radius: 50%;
    padding: 2px;
    box-shadow: 0 2px 2px 0 rgba(0,0,0,0.14);
}
.image-item .remove-image i {
    font-size: 16px;
    color: #F44336;
}
.modal-galeria {
    width: 85%;
    max-width: 800px;
    height: auto;
    max-height: 85vh; /* Limitar altura al 85% de la altura visible */
    overflow: hidden; /* Evitar scroll en el modal */
    border-radius: 8px;
}
.modal-galeria .modal-content {
    padding: 15px;
    max-height: calc(85vh - 56px); /* Altura máxima menos el footer */
    overflow: hidden;
}
.galeria-imagen-principal {
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #f5f5f5;
    border-radius: 4px;
    overflow: hidden;
    margin: 10px 0;
    height: 40vh; /* Altura relativa a la pantalla */
    min-height: 200px;
    max-height: 350px;
    position: relative;
}
.galeria-imagen-principal img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.galeria-controles {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 10px;
}
.galeria-prev, .galeria-next {
    pointer-events: auto;
    background-color: rgba(255, 255, 255, 0.7);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 2px 0 rgba(0,0,0,0.14), 0 3px 1px -2px rgba(0,0,0,0.12), 0 1px 5px 0 rgba(0,0,0,0.2);
    z-index: 5;
}
.galeria-prev i, .galeria-next i {
    color: #333;
    font-size: 24px;
}
.galeria-info-producto {
    margin: 10px 0;
    padding: 0 10px;
    max-height: 80px;
    overflow: auto;
}
.galeria-descripcion {
    margin-bottom: 10px;
    line-height: 1.4;
    font-size: 0.95rem;
    max-height: 60px;
    overflow: auto;
}
.galeria-precios {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 5px;
}
.galeria-precios p {
    margin: 0;
    font-size: 0.95rem;
}
.galeria-miniaturas {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
    justify-content: flex-start;
    margin: 10px 0 5px;
    padding: 8px;
    background-color: #f5f5f5;
    border-radius: 4px;
    overflow-x: auto;
    white-space: nowrap;
    -webkit-overflow-scrolling: touch; /* Para mejor desplazamiento en iOS */
    scrollbar-width: thin; /* Para Firefox */
}
.galeria-miniaturas::-webkit-scrollbar {
    height: 6px;
}
.galeria-miniaturas::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.2);
    border-radius: 3px;
}
.galeria-miniatura {
    width: 60px;
    height: 60px;
    border-radius: 4px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s ease;
    display: inline-block;
    flex-shrink: 0;
}
.galeria-miniatura:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 4px rgba(0,0,0,0.2);
}
.galeria-miniatura.active {
    border-color: #2196F3;
}
.galeria-miniatura img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Estilos adicionales para el panel de filtros */
.card-content {
    position: relative;
    background-color: white;
}

.card .card-content::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: white;
    z-index: -1;
}

/* Eliminar sombras de los botones en el panel de filtros */
.card .btn.waves-effect.waves-light {
    position: relative;
    z-index: 10;
    box-shadow: none !important;
}

/* Estilos para la tarjeta de filtros */
.filtros-card {
    box-shadow: none !important;
    border: 1px solid #e0e0e0;
    background-color: white !important;
    position: relative;
    overflow: hidden;
}

/* Ocultar cualquier texto que pueda aparecer en la parte inferior del panel de filtros */
.filtros-card::after,
.filtros-card .card-content::after,
.filtros-card .card-action,
.filtros-card .card-reveal,
.filtros-card .card-panel::after {
    display: none !important;
    content: none !important;
    visibility: hidden !important;
}

/* Ocultar específicamente la etiqueta "Filtros" que aparece en la parte inferior */
.card-panel::after,
.card::after,
.card-content::after,
.card-title::after,
.card-title + *::after,
.card-content > *::after,
.card-content > div::after,
.card-content > div > *::after,
.card-content > div > div::after,
.card-content > div > div > *::after,
.card-content > div > div > div::after {
    display: none !important;
    content: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    height: 0 !important;
    width: 0 !important;
    overflow: hidden !important;
    position: absolute !important;
    pointer-events: none !important;
}

/* Agregar un estilo específico para ocultar cualquier texto después del último elemento */
.filtros-card .card-content::after,
.filtros-card .card-content > *:last-child::after,
.filtros-card .card-content > div:last-child::after,
.filtros-card .card-content > div > *:last-child::after {
    content: "" !important;
    display: none !important;
}

/* Estilos específicos para el panel de filtros de Materialize */
.card-content:after {
    content: none !important;
    display: none !important;
}

/* Ocultar cualquier texto que pueda aparecer después del botón de limpiar filtros */
#limpiar-filtros + *,
#limpiar-filtros ~ *,
#limpiar-filtros ~ div,
#limpiar-filtros ~ span,
#limpiar-filtros ~ p,
#limpiar-filtros ~ label,
#limpiar-filtros ~ a,
#limpiar-filtros ~ button,
#limpiar-filtros ~ input,
#limpiar-filtros ~ select,
#limpiar-filtros ~ textarea,
#limpiar-filtros ~ i,
#limpiar-filtros ~ img,
#limpiar-filtros ~ svg,
#limpiar-filtros ~ canvas,
#limpiar-filtros ~ video,
#limpiar-filtros ~ audio,
#limpiar-filtros ~ iframe,
#limpiar-filtros ~ object,
#limpiar-filtros ~ embed,
#limpiar-filtros ~ param,
#limpiar-filtros ~ source,
#limpiar-filtros ~ track,
#limpiar-filtros ~ map,
#limpiar-filtros ~ area,
#limpiar-filtros ~ article,
#limpiar-filtros ~ aside,
#limpiar-filtros ~ details,
#limpiar-filtros ~ figcaption,
#limpiar-filtros ~ figure,
#limpiar-filtros ~ footer,
#limpiar-filtros ~ header,
#limpiar-filtros ~ hgroup,
#limpiar-filtros ~ menu,
#limpiar-filtros ~ nav,
#limpiar-filtros ~ section,
#limpiar-filtros ~ summary,
#limpiar-filtros ~ time,
#limpiar-filtros ~ mark,
#limpiar-filtros ~ ruby,
#limpiar-filtros ~ rt,
#limpiar-filtros ~ rp,
#limpiar-filtros ~ bdi,
#limpiar-filtros ~ bdo,
#limpiar-filtros ~ wbr,
#limpiar-filtros ~ details,
#limpiar-filtros ~ dialog,
#limpiar-filtros ~ summary,
#limpiar-filtros ~ data,
#limpiar-filtros ~ datalist,
#limpiar-filtros ~ output,
#limpiar-filtros ~ progress,
#limpiar-filtros ~ meter,
#limpiar-filtros ~ main {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    height: 0 !important;
    width: 0 !important;
    overflow: hidden !important;
    position: absolute !important;
    pointer-events: none !important;
}

/* Asegurar que no haya imágenes de fondo en la tarjeta */
.filtros-card::before,
.filtros-card::after {
    display: none !important;
    content: none !important;
    background-image: none !important;
    background: none !important;
}

/* Estilos específicos para el contenedor del botón de filtros */
.filtros-btn-container {
    margin-top: 20px;
    position: relative;
    z-index: 10;
    background-color: white;
    padding: 5px;
}

/* Estilos para el botón de filtros */
.filtros-btn {
    width: 100%;
    box-shadow: none !important;
    background-color: #2196F3 !important;
    position: relative;
    z-index: 15;
}

/* Eliminar cualquier imagen o sombra de fondo */
.filtros-btn-container::before,
.filtros-btn-container::after,
.filtros-btn::before,
.filtros-btn::after {
    display: none !important;
    content: none !important;
    background-image: none !important;
    background: none !important;
    box-shadow: none !important;
}

/* Estilos adicionales para eliminar cualquier imagen de fondo */
.card-content,
.card-content > *,
.center-align,
.btn {
    background-image: none !important;
}

/* Forzar un fondo blanco sólido en todo el panel de filtros */
.card-content,
.filtros-btn-container,
.card-content > div,
.card-content > p {
    background-color: white !important;
}

/* Estilos para el indicador de número de fotos */
.foto-count-indicator {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background-color: rgba(0, 0, 0, 0.5);
    color: white;
    border-radius: 10px;
    padding: 2px 5px;
    font-size: 10px;
    display: flex;
    align-items: center;
    z-index: 5;
    text-decoration: none;
}

.foto-count-indicator i {
    font-size: 12px !important;
    margin-right: 2px;
}

.foto-count-indicator span {
    line-height: 1;
}

/* Estilos para los filtros */
.categorias-container {
    max-height: 200px;
    overflow-y: auto;
    padding-right: 5px;
    margin-bottom: 10px;
}

.categorias-container::-webkit-scrollbar {
    width: 5px;
}

.categorias-container::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.2);
    border-radius: 3px;
}

.categoria-nombre {
    font-size: 0.95em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: inline-block;
    max-width: 180px;
}

.price-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 5px;
    font-size: 0.9em;
    color: #757575;
}

.selected-price {
    text-align: center;
    margin-top: 10px;
    font-weight: bold;
    color: #2196F3;
}

#limpiar-filtros {
    color: #757575;
    font-size: 0.9em;
}

/* Estilos para productos filtrados */
.hidden {
    display: none !important;
}

/* Tarjeta de producto compacta */
.producto-item .card {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 0 0 10px 0;
    margin-bottom: 18px;
    min-height: 320px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
}
.producto-item .card-image {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px 0 0 0;
    min-height: 150px;
    background: #fff;
}
.producto-item .card-image img {
    max-width: 120px;
    max-height: 120px;
    object-fit: contain;
    border-radius: 10px;
    margin: 0 auto;
    background: #f8f9fa;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
}
.producto-item .card-content {
    padding: 12px 14px 0 14px;
    text-align: center;
    background: #fff;
}
.producto-item .product-title {
    font-size: 1.13rem;
    font-weight: 600;
    margin: 0 0 6px 0;
    min-height: 38px;
    color: #222;
    letter-spacing: 0.01em;
    text-shadow: 0 1px 0 #fff;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    white-space: normal;
}
.producto-item .product-description {
    font-size: 0.98rem;
    color: #444;
    margin: 0 0 2px 0;
    min-height: 28px;
    line-height: 1.25;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    white-space: normal;
}
.producto-item .price-section {
    min-height: 40px;
    margin-bottom: 4px;
    margin-top: 2px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}
.producto-item .price {
    font-size: 1.18rem;
    font-weight: 700;
    color: #222;
    margin: 0 0 2px 0;
    letter-spacing: 0.01em;
}
.producto-item .price-bs {
    font-size: 1.01rem;
    color: #4CAF50;
    margin-bottom: 2px;
}
.producto-item .category-container, .producto-item .category-label, .producto-item .category-value {
    font-size: 0.93rem;
    color: #666;
    margin-bottom: 2px;
}
.producto-item .acciones-producto {
    margin-top: 8px;
    display: flex;
    justify-content: center;
    gap: 8px;
}
.producto-item .btn, .producto-item .btn-small {
    font-size: 0.97rem;
    padding: 0 12px;
    height: 34px;
    line-height: 34px;
    border-radius: 7px;
}
/* Mejorar contraste de botones de disponibilidad */
.producto-item .availability-btn {
    background: #6c63ff !important;
    color: #fff !important;
    font-weight: 500;
    border-radius: 20px !important;
    box-shadow: 0 2px 8px rgba(108,99,255,0.08);
}
.producto-item .availability-btn.red {
    background: #f44336 !important;
    color: #fff !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar elementos de Materialize
    M.AutoInit();

    // Referencias a elementos del DOM
    const priceRange = document.getElementById('price-range');
    const selectedPrice = document.getElementById('selected-price');
    const categoriaCheckboxes = document.querySelectorAll('.categoria-checkbox');
    const disponibilidadRadios = document.querySelectorAll('.disponibilidad-radio');
    const limpiarFiltrosBtn = document.getElementById('limpiar-filtros');
    const productoItems = document.querySelectorAll('.producto-item');
    const mensajeNoProductos = document.getElementById('no-productos-mensaje');
    const searchInput = document.getElementById('search');
    const limpiarFiltrosMensajeBtn = document.getElementById('limpiar-filtros-mensaje');

    // Función simplificada para aplicar filtros
    function aplicarFiltros() {
        console.log('Aplicando filtros...');

        // Obtener valores de los filtros
        const precioMaximo = priceRange ? parseFloat(priceRange.value) : Infinity;
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';

        // Determinar si "Todas" está seleccionada
        let todasSeleccionada = false;
        const categoriasSeleccionadas = [];

        categoriaCheckboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                if (checkbox.value === 'todas') {
                    todasSeleccionada = true;
                } else {
                    categoriasSeleccionadas.push(checkbox.value);
                }
            }
        });

        // Obtener disponibilidad seleccionada
        let disponibilidadSeleccionada = 'todos';
        disponibilidadRadios.forEach(function(radio) {
            if (radio.checked) {
                disponibilidadSeleccionada = radio.value;
            }
        });

        console.log('Filtros:', {
            precioMaximo,
            todasSeleccionada,
            categoriasSeleccionadas,
            disponibilidadSeleccionada,
            searchTerm
        });

        // Contar productos visibles
        let productosVisibles = 0;

        // Aplicar filtros a cada producto
        productoItems.forEach(function(item) {
            // Obtener datos del producto
            const precio = parseFloat(item.dataset.precio) || 0;
            const categoria = item.dataset.categoria;
            const disponibilidad = item.dataset.disponibilidad;
            const nombreProducto = item.querySelector('.product-title').textContent.toLowerCase();
            const descripcionElement = item.querySelector('.product-description-text');
            const descripcion = descripcionElement ? descripcionElement.textContent.toLowerCase() : '';

            // Verificar si cumple con los filtros
            const cumplePrecio = precio <= precioMaximo;
            const cumpleCategoria = todasSeleccionada || categoriasSeleccionadas.includes(categoria);
            const cumpleDisponibilidad = disponibilidadSeleccionada === 'todos' || disponibilidad === disponibilidadSeleccionada;
            const cumpleBusqueda = searchTerm === '' || nombreProducto.includes(searchTerm) || descripcion.includes(searchTerm);

            // Determinar si el producto debe mostrarse
            const mostrarProducto = cumplePrecio && cumpleCategoria && cumpleDisponibilidad && cumpleBusqueda;

            // Mostrar u ocultar el producto
            if (mostrarProducto) {
                item.style.display = '';
                productosVisibles++;
            } else {
                item.style.display = 'none';
            }
        });

        console.log('Productos visibles:', productosVisibles);

        // Mostrar u ocultar mensaje de no hay productos
        if (mensajeNoProductos) {
            if (productosVisibles === 0) {
                mensajeNoProductos.style.display = '';
            } else {
                mensajeNoProductos.style.display = 'none';
            }
        }
    }

    // Función para limpiar filtros
    function limpiarFiltros() {
        console.log('Limpiando filtros...');

        // Restablecer categorías - seleccionar solo "Todas"
        categoriaCheckboxes.forEach(function(checkbox) {
            checkbox.checked = checkbox.value === 'todas';
        });

        // Restablecer disponibilidad - seleccionar "Todos"
        disponibilidadRadios.forEach(function(radio) {
            radio.checked = radio.value === 'todos';
        });

        // Restablecer precio al máximo
        if (priceRange) {
            priceRange.value = priceRange.max;
            selectedPrice.textContent = '$' + Number(priceRange.max).toLocaleString();
        }

        // Limpiar campo de búsqueda
        if (searchInput) {
            searchInput.value = '';
        }

        // Mostrar todos los productos
        productoItems.forEach(function(item) {
            item.style.display = '';
        });

        // Ocultar mensaje de no hay productos
        if (mensajeNoProductos) {
            mensajeNoProductos.style.display = 'none';
        }
    }

    // Manejar el checkbox "Todas" en categorías
    categoriaCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            if (this.value === 'todas' && this.checked) {
                // Si se selecciona "Todas", desmarcar las demás
                categoriaCheckboxes.forEach(function(cb) {
                    if (cb.value !== 'todas') {
                        cb.checked = false;
                    }
                });
            } else if (this.checked) {
                // Si se selecciona otra categoría, desmarcar "Todas"
                categoriaCheckboxes.forEach(function(cb) {
                    if (cb.value === 'todas') {
                        cb.checked = false;
                    }
                });
            }

            // Si no hay ninguna categoría seleccionada, seleccionar "Todas"
            let algunaSeleccionada = false;
            categoriaCheckboxes.forEach(function(cb) {
                if (cb.checked) {
                    algunaSeleccionada = true;
                }
            });

            if (!algunaSeleccionada) {
                categoriaCheckboxes.forEach(function(cb) {
                    if (cb.value === 'todas') {
                        cb.checked = true;
                    }
                });
            }

            // Aplicar filtros
            aplicarFiltros();
        });
    });

    // Eventos para aplicar filtros
    if (priceRange) {
        priceRange.addEventListener('input', function() {
            selectedPrice.textContent = '$' + Number(this.value).toLocaleString();
            aplicarFiltros();
        });
    }

    disponibilidadRadios.forEach(function(radio) {
        radio.addEventListener('change', aplicarFiltros);
    });

    if (searchInput) {
        searchInput.addEventListener('input', aplicarFiltros);
    }

    if (limpiarFiltrosBtn) {
        limpiarFiltrosBtn.addEventListener('click', limpiarFiltros);
    }

    if (limpiarFiltrosMensajeBtn) {
        limpiarFiltrosMensajeBtn.addEventListener('click', limpiarFiltros);
    }

    // Aplicar filtros al cargar la página
    aplicarFiltros();
});
</script>


