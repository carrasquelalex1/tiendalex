<?php
// Usar rutas relativas al archivo actual
$root_path = __DIR__ . '/../../';
require_once $root_path . 'frontend/includes/enlaces.php';
require_once $root_path . 'frontend/includes/css_includes.php';
// La conexión a la base de datos se incluye en cada archivo que la necesita
require_once $root_path . 'backend/config/db.php';

require_once $root_path . 'helpers/session/session_helper.php';
require_once $root_path . 'autoload.php'; // Asegura acceso a Carrito y helpers

// Iniciar sesión de manera segura (debe ser lo primero)
iniciar_sesion_segura();

// Log de depuración para verificar la sesión
error_log("header.php - SESSION: " . print_r($_SESSION, true));

// Verificar si el usuario está logueado
$usuario_logueado = esta_logueado();
$es_admin = es_admin();
$es_cliente = es_cliente();
$nombre_usuario = obtener_nombre_usuario() ?? '';

// Contar productos en el carrito si el usuario es cliente
$productos_carrito = 0;
if ($es_cliente && isset($_SESSION['usuario_logueado'])) {
    $usuario_id = $_SESSION['usuario_logueado'];
    $productos_carrito = Carrito::contarProductosCarrito($usuario_id, $conn);
    error_log("Usuario ID: $usuario_id, Productos en carrito: $productos_carrito");
}

// Verificar si el usuario tiene pedidos
$tiene_pedidos = false;
$cantidad_pedidos = 0;
if ($es_cliente && isset($_SESSION['usuario_logueado'])) {
    $usuario_id = $_SESSION['usuario_logueado'];
    $sql = "SELECT COUNT(DISTINCT pedido) as total_pedidos FROM pedido WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $cantidad_pedidos = $row['total_pedidos'];
        $tiene_pedidos = ($cantidad_pedidos > 0);
    }
}

// Verificar si el usuario tiene compras realizadas
$tiene_compras = false;
if ($es_cliente && isset($_SESSION['usuario_logueado'])) {
    $usuario_id = $_SESSION['usuario_logueado'];
    $sql = "SELECT COUNT(DISTINCT pedido_codigo) as total_compras FROM pagos WHERE usuario_codigo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $tiene_compras = ($row['total_compras'] > 0);
    }
}

// Verificar si hay mensajes de chat no leídos
$tiene_mensajes_nuevos = false;
$cantidad_mensajes_nuevos = 0;

if ($usuario_logueado && isset($_SESSION['usuario_logueado'])) {
    $usuario_id = $_SESSION['usuario_logueado'];

    // Verificar si la tabla chat_mensajes existe
    $tabla_chat_existe = false;
    $sql_check_table = "SHOW TABLES LIKE 'chat_mensajes'";
    $result_check = $conn->query($sql_check_table);
    if ($result_check && $result_check->num_rows > 0) {
        $tabla_chat_existe = true;
    }

    if ($tabla_chat_existe) {
        if ($es_admin) {
            // Para administradores, verificar todos los chats con mensajes no leídos
            $sql = "SELECT COUNT(*) as total FROM chat_mensajes WHERE enviado_por = 'usuario' AND leido = 0";
            $result = $conn->query($sql);
            if ($row = $result->fetch_assoc()) {
                $cantidad_mensajes_nuevos = $row['total'];
                $tiene_mensajes_nuevos = ($cantidad_mensajes_nuevos > 0);
            }
        } else {
            // Para usuarios normales, verificar sus propios mensajes no leídos
            $sql = "SELECT COUNT(*) as total FROM chat_mensajes WHERE usuario_id = ? AND enviado_por = 'admin' AND leido = 0";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $cantidad_mensajes_nuevos = $row['total'];
                $tiene_mensajes_nuevos = ($cantidad_mensajes_nuevos > 0);
            }
        }
    }
}
?>
<style>
  /* Animación para el carrito */
  @keyframes pulseCart {
    0% {
      transform: scale(1) rotate(0deg);
      color: inherit;
    }
    25% {
      transform: scale(1.2) rotate(5deg);
      color: #ff9800;
    }
    50% {
      transform: scale(0.9) rotate(-5deg);
      color: #ff5722;
    }
    75% {
      transform: scale(1.1) rotate(3deg);
      color: #ff9800;
    }
    100% {
      transform: scale(1) rotate(0deg);
      color: inherit;
    }
  }

  #cart-icon.cart-animation {
    display: inline-block !important;
    animation: pulseCart 1s ease-in-out !important;
    transform-origin: center !important;
    will-change: transform, color;
  }

  /* Animación para la disponibilidad */
  @keyframes pulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); }
    70% { transform: scale(1.05); box-shadow: 0 0 0 8px rgba(76, 175, 80, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
  }

  .pulse {
    animation: pulse 2s infinite;
  }

  .pulse:hover {
    animation: none;
  }

  /* Fondo personalizado para el menú desplegable del usuario */
  #dropdown-user.dropdown-content {
    background: #f8f9fa !important; /* Color claro, puedes cambiarlo por otro que combine con tu tema */
    color: #222 !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 16px rgba(0,0,0,0.12) !important;
    border: 1px solid #e0e0e0 !important;
  }
  #dropdown-user.dropdown-content li > a, #dropdown-user.dropdown-content li > span {
    color: #222 !important;
  }
  #dropdown-user.dropdown-content li.divider {
    background-color: #e0e0e0 !important;
  }

  /* Estilos para el nombre del usuario en el encabezado */
  .user-name-header {
    color: white !important;
    text-decoration: none !important;
    transition: all 0.3s ease;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
  }

  .user-name-header:hover {
    color: rgba(255, 255, 255, 0.8) !important;
    text-decoration: none !important;
  }

  /* Contenedor del icono de usuario */
  .user-account-container {
    display: flex !important;
    align-items: center !important;
    transition: all 0.3s ease;
    padding: 5px 10px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.1);
  }

  .user-account-container:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
  }
</style>

<header class="header">
  <!-- Barra superior con información de contacto -->
  <div class="top-bar">
    <div class="container">
      <div class="row valign-wrapper" style="margin: 0;">
        <div class="col s12 m6 left-align">
          <span><i class="material-icons tiny">phone</i> +58 414-1234567</span>
          <span class="hide-on-small-only">|</span>
          <span><i class="material-icons tiny">email</i> info@alexandercarrasquel.com</span>
        </div>
        <div class="col s12 m6 right-align">
          <?php if ($usuario_logueado): ?>
            <span><i class="material-icons tiny">person</i> <?php echo htmlspecialchars($nombre_usuario); ?></span>
          <?php else: ?>
            <a href="/tiendalex2/login.php" class="login-link">Iniciar Sesión</a>
            <span class="separator">|</span>
            <a href="/tiendalex2/registro.php" class="register-link">Registrarse</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Barra de navegación principal -->
  <div class="navbar-fixed">
    <nav class="nav-extended main-nav">
      <div class="nav-wrapper">
        <div class="container">
          <a href="/tiendalex2/index.php" class="brand-logo">
            <img src="/tiendalex2/frontend/assets/img/logo.png" alt="Alexander Carrasquel" class="logo-img">
          </a>
          <a href="#" data-target="mobile-demo" class="sidenav-trigger"><i class="material-icons">menu</i></a>

          <ul class="right hide-on-med-and-down" style="display: flex; align-items: center;">
            <li><a href="/tiendalex2/index.php" class="nav-link">Inicio</a></li>
            <li><a href="/tiendalex2/servicios.php" class="nav-link">Servicios</a></li>
            <li><a href="/tiendalex2/catalogo.php" class="nav-link">Catálogo</a></li>
            <li><a href="/tiendalex2/acerca.php" class="nav-link">Acerca de</a></li>
            <li><a href="/tiendalex2/contacto.php" class="nav-link">Contacto</a></li>

            <!-- Iconos de acción -->
            <li class="action-icons" style="display: flex; align-items: center; margin-left: 15px;">
              <?php if ($es_cliente && $productos_carrito > 0): ?>
                <a href="/tiendalex2/carrito.php" id="carrito-link" class="icon-link tooltipped" data-position="bottom" data-tooltip="Carrito" style="margin: 0 8px; position: relative;">
                  <i id="cart-icon" class="material-icons" style="font-size: 1.5rem;">shopping_cart</i>
                  <span class="badge new blue" data-badge-caption="" style="right: 8px; top: 8px;"><?php echo $productos_carrito; ?></span>
                </a>
              <?php endif; ?>

              <?php if ($es_cliente): ?>
                <?php if ($tiene_pedidos || $tiene_compras): ?>
                  <a href="/tiendalex2/mis_compras.php" id="mis-compras-link" class="icon-link tooltipped" data-position="bottom" data-tooltip="Historial de Compras" style="margin: 0 8px;">
                    <i class="material-icons" style="font-size: 1.5rem;">list_alt</i>
                    <?php if ($cantidad_pedidos > 0): ?>
                      <span class="badge new white orange-text" data-badge-caption="" style="right: 8px; top: 8px;"><?php echo $cantidad_pedidos; ?></span>
                    <?php endif; ?>
                  </a>
                <?php endif; ?>

                <a href="javascript:void(0)" id="chat-icon" class="icon-link tooltipped" data-position="bottom" data-tooltip="Chat" style="margin: 0 8px;">
                  <i class="material-icons" style="font-size: 1.5rem;">chat</i>
                  <?php if ($tiene_mensajes_nuevos): ?>
                    <span class="badge new white red-text" data-badge-caption="" style="right: 8px; top: 8px;"><?php echo $cantidad_mensajes_nuevos; ?></span>
                  <?php endif; ?>
                </a>
              <?php endif; ?>

              <?php if ($es_admin): ?>
                <a href="/tiendalex2/admin_chat.php" id="admin-chat-icon" class="icon-link tooltipped" data-position="bottom" data-tooltip="Chat Admin" style="margin: 0 8px;">
                  <i class="material-icons" style="font-size: 1.5rem;">forum</i>
                  <?php if ($tiene_mensajes_nuevos): ?>
                    <span class="badge new white red-text" data-badge-caption="" style="right: 8px; top: 8px;"><?php echo $cantidad_mensajes_nuevos; ?></span>
                  <?php endif; ?>
                </a>
              <?php endif; ?>

              <?php if ($usuario_logueado): ?>
                <a class="dropdown-trigger icon-link tooltipped user-account-container" href="#!" data-target="dropdown-user" data-position="bottom" data-tooltip="Mi Cuenta" style="margin: 0 8px;">
                  <i class="material-icons" style="font-size: 1.5rem; margin-right: 5px;">account_circle</i>
                  <span class="hide-on-small-only user-name-header" style="font-size: 0.9rem; font-weight: 500;"><?php echo htmlspecialchars($nombre_usuario); ?></span>
                </a>
              <?php else: ?>
                <a href="/tiendalex2/login.php" class="icon-link tooltipped" data-position="bottom" data-tooltip="Iniciar Sesión" style="margin: 0 8px;">
                  <i class="material-icons" style="font-size: 1.5rem;">login</i>
                  <span class="hide-on-med-and-down">Iniciar Sesión</span>
                </a>
              <?php endif; ?>
            </li>
        </ul>

        <!-- Dropdown Structure -->
        <ul id="dropdown-user" class="dropdown-content">
          <?php if ($es_admin): ?>
            <li><a href="/tiendalex2/catalogo.php"><i class="material-icons left">dashboard</i>Panel Admin</a></li>
            <li><a href="/tiendalex2/admin_pedidos.php"><i class="material-icons left">shopping_cart</i>Gestión de Pedidos</a></li>
            <li><a href="/tiendalex2/admin_chat.php"><i class="material-icons left">forum</i>Chat con Clientes</a></li>
            <li><a href="/tiendalex2/verificar_usuarios.php"><i class="material-icons left">people</i>Verificar Usuarios</a></li>
            <li><a href="/tiendalex2/admin_tema.php"><i class="material-icons left">color_lens</i>Personalizar Tema</a></li>
            <li><a href="/tiendalex2/backend/verificar_tablas.php" target="_blank"><i class="material-icons left">table_chart</i>Verificar Tablas</a></li>
          <li><a href="/tiendalex2/perfil.php"><i class="material-icons left">account_circle</i>Mi Perfil</a></li>
          <li><a href="/tiendalex2/corregir_sesion.php"><i class="material-icons left">build</i>Corregir Sesión</a></li>
          <li><a href="/tiendalex2/corregir_codigo_pedido.php"><i class="material-icons left">shopping_cart</i>Corregir Código Pedido</a></li>
          <li><a href="/tiendalex2/debug_session.php"><i class="material-icons left">bug_report</i>Depurar Sesión</a></li>
          <li class="divider"></li>
          <li><a href="/tiendalex2/logout.php"><i class="material-icons left">exit_to_app</i>Cerrar Sesión</a></li>
          <?php else: ?>
            <li><a href="/tiendalex2/perfil.php"><i class="material-icons left">account_circle</i>Mi Perfil</a></li>
            <li class="divider"></li>
            <li><a href="/tiendalex2/logout.php"><i class="material-icons left">exit_to_app</i>Cerrar Sesión</a></li>
          <?php endif; ?>
        </ul>
        </div>
      </div>
    </nav>
  </div>

  <ul class="sidenav" id="mobile-demo">
    <li><a href="/tiendalex2/index.php"><i class="material-icons left">home</i>Inicio</a></li>
    <li><a href="/tiendalex2/servicios.php"><i class="material-icons left">business</i>Servicios</a></li>
    <li><a href="/tiendalex2/catalogo.php"><i class="material-icons left">view_list</i>Catálogo</a></li>
    <li><a href="/tiendalex2/acerca.php"><i class="material-icons left">info</i>Acerca de</a></li>
    <li><a href="/tiendalex2/contacto.php"><i class="material-icons left">contact_mail</i>Contacto</a></li>

    <li class="divider"></li>

    <?php if ($es_cliente && $productos_carrito > 0): ?>
        <!-- Icono del carrito para clientes (móvil) - solo visible cuando hay productos -->
        <li>
          <a href="/tiendalex2/carrito.php" id="carrito-link-mobile" class="cart-icon-container">
            <i class="material-icons left cart-icon-animated">shopping_cart</i>Carrito
            <span class="badge new blue pulse" data-badge-caption=""><?php echo $productos_carrito; ?></span>
          </a>
        </li>
    <?php endif; ?>

    <?php if ($es_cliente): ?>
        <!-- Icono unificado para pedidos y compras (móvil) -->
        <li id="mis-compras-icon-container-mobile" <?php echo (!$tiene_pedidos && !$tiene_compras) ? 'style="display:none;"' : ''; ?>>
          <a href="/tiendalex2/mis_compras.php" id="mis-compras-link-mobile" class="orders-icon-container">
            <i class="material-icons left orders-icon-animated" id="mis-compras-icon-mobile">shopping_bag</i>Mis Compras
            <?php if ($cantidad_pedidos > 0): ?>
            <span class="badge new amber darken-2 pedidos-badge-mobile" data-badge-caption=""><?php echo $cantidad_pedidos; ?></span>
            <?php endif; ?>
          </a>
        </li>

        <!-- Icono de chat para clientes (móvil) -->
        <li>
          <a href="javascript:void(0)" id="chat-icon-mobile" class="chat-icon-container">
            <i class="material-icons left <?php echo $tiene_mensajes_nuevos ? 'pulse' : ''; ?>">chat</i>Chat
            <?php if ($tiene_mensajes_nuevos): ?>
            <span class="badge new red" data-badge-caption=""><?php echo $cantidad_mensajes_nuevos; ?></span>
            <?php endif; ?>
          </a>
        </li>
    <?php endif; ?>

    <?php if ($es_admin): ?>
        <!-- Icono de chat para administradores (móvil) -->
        <li>
          <a href="/tiendalex2/admin_chat.php" id="admin-chat-icon-mobile" class="chat-icon-container">
            <i class="material-icons left <?php echo $tiene_mensajes_nuevos ? 'pulse' : ''; ?>">forum</i>Chat Admin
            <?php if ($tiene_mensajes_nuevos): ?>
            <span class="badge new red" data-badge-caption=""><?php echo $cantidad_mensajes_nuevos; ?></span>
            <?php endif; ?>
          </a>
        </li>
    <?php endif; ?>

    <?php if ($usuario_logueado): ?>
      <!-- Opciones para usuario logueado -->
      <li>
        <div class="user-view">
          <div class="background blue lighten-4"></div>
          <a href="/tiendalex2/perfil.php"><span class="black-text name"><?php echo htmlspecialchars($nombre_usuario); ?></span></a>
        </div>
      </li>
      <?php if ($es_admin): ?>
        <li><a href="/tiendalex2/catalogo.php"><i class="material-icons left">dashboard</i>Panel Admin</a></li>
        <li><a href="/tiendalex2/admin_pedidos.php"><i class="material-icons left">shopping_cart</i>Gestión de Pedidos</a></li>
        <li><a href="/tiendalex2/admin_chat.php"><i class="material-icons left">forum</i>Chat con Clientes</a></li>
        <li><a href="/tiendalex2/verificar_usuarios.php"><i class="material-icons left">people</i>Verificar Usuarios</a></li>
        <li><a href="/tiendalex2/admin_tema.php"><i class="material-icons left">color_lens</i>Personalizar Tema</a></li>
        <li><a href="/tiendalex2/backend/verificar_tablas.php" target="_blank"><i class="material-icons left">table_chart</i>Verificar Tablas</a></li>
      <?php endif; ?>
      <li><a href="/tiendalex2/perfil.php"><i class="material-icons left">account_circle</i>Mi Perfil</a></li>
      <li><a href="/tiendalex2/corregir_sesion.php"><i class="material-icons left">build</i>Corregir Sesión</a></li>
      <li><a href="/tiendalex2/corregir_codigo_pedido.php"><i class="material-icons left">shopping_cart</i>Corregir Código Pedido</a></li>
      <li><a href="/tiendalex2/debug_session.php"><i class="material-icons left">bug_report</i>Depurar Sesión</a></li>
      <li><a href="/tiendalex2/logout.php"><i class="material-icons left">exit_to_app</i>Cerrar Sesión</a></li>
    <?php else: ?>
      <!-- Enlaces para usuarios no logueados -->
      <li><a href="/tiendalex2/login.php"><i class="material-icons left">login</i>Iniciar Sesión</a></li>
      <li><a href="/tiendalex2/registro.php"><i class="material-icons left">person_add</i>Registrarse</a></li>
    <?php endif; ?>
  </ul>
</header>

<script>
  // Función para animar el carrito
  function animateCart() {
    console.log('Ejecutando animateCart()');
    const cartIcon = document.getElementById('cart-icon');
    console.log('Elemento cart-icon:', cartIcon);

    if (cartIcon) {
      console.log('Aplicando animación al carrito');
      // Remover la clase primero para reiniciar la animación
      cartIcon.classList.remove('cart-animation');
      // Forzar un reflow
      void cartIcon.offsetWidth;
      // Añadir la clase para iniciar la animación
      cartIcon.classList.add('cart-animation');
      console.log('Animación aplicada');
    } else {
      console.error('No se encontró el elemento con ID "cart-icon"');
    }
  }

  // Inicializar todo cuando el documento esté listo
  document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM completamente cargado');

    // Inicializar componentes de Materialize
    M.AutoInit();

    // Inicializar tooltips
    var tooltipElems = document.querySelectorAll('.tooltipped');
    M.Tooltip.init(tooltipElems, {});

    // Inicializar sidenav
    var elemsSidenav = document.querySelectorAll('.sidenav');
    var instancesSidenav = M.Sidenav.init(elemsSidenav, {
      edge: 'left',
      draggable: true,
      preventScrolling: true
    });

    // Inicializar dropdown
    var elemsDropdown = document.querySelectorAll('.dropdown-trigger');
    var instancesDropdown = M.Dropdown.init(elemsDropdown, {
      coverTrigger: false,
      constrainWidth: false,
      alignment: 'right',
      hover: false,
      closeOnClick: true,
      inDuration: 300,
      outDuration: 200,
      container: document.body
    });

    // Forzar la aplicación de estilos
    document.head.insertAdjacentHTML('beforeend', `
      <style id="cart-animation-style">
        @keyframes pulseCart {
          0% { transform: scale(1) rotate(0deg); color: inherit; }
          25% { transform: scale(1.2) rotate(5deg); color: #ff9800; }
          50% { transform: scale(0.9) rotate(-5deg); color: #ff5722; }
          75% { transform: scale(1.1) rotate(3deg); color: #ff9800; }
          100% { transform: scale(1) rotate(0deg); color: inherit; }
        }
        #cart-icon.cart-animation {
          display: inline-block !important;
          animation: pulseCart 1s ease-in-out !important;
          transform-origin: center !important;
          will-change: transform, color;
        }
      </style>
    `);

    // Iniciar la animación después de 1 segundo de cargada la página
    setTimeout(animateCart, 1000);

    // Configurar el intervalo para que se repita cada 10 segundos (10000 ms)
    setInterval(animateCart, 10000);
  });
</script>

<script>
  // Función para actualizar la visibilidad del icono de compras
  window.actualizarIconoCompras = function(tieneCompras) {
    const misComprasIconContainer = document.getElementById('mis-compras-icon-container');
    const misComprasIconContainerMobile = document.getElementById('mis-compras-icon-container-mobile');

    // Verificar si hay pedidos pendientes
    const tienePedidos = document.querySelector('.pedidos-badge') !== null;

    if (misComprasIconContainer) {
      misComprasIconContainer.style.display = (tieneCompras || tienePedidos) ? 'block' : 'none';
    }

    if (misComprasIconContainerMobile) {
      misComprasIconContainerMobile.style.display = (tieneCompras || tienePedidos) ? 'block' : 'none';
    }
  };
</script>

<!-- Script para desactivar animaciones automáticas de tarjetas -->
<script src="/tiendalex2/js/disable_card_hover.js"></script>

<!-- Script para corregir posición de toasts -->
<script src="/tiendalex2/js/fix_toast_position.js"></script>