<?php
/**
 * Componente de chat flotante
 * Se incluye en todas las páginas para usuarios logueados
 */

// Verificar si el usuario está logueado
if (!isset($usuario_logueado) || !$usuario_logueado) {
    return;
}

// Verificar si las tablas de chat existen
$tabla_chat_existe = false;
$sql_check_table = "SHOW TABLES LIKE 'chat_mensajes'";
$result_check = $conn->query($sql_check_table);
if ($result_check && $result_check->num_rows > 0) {
    $tabla_chat_existe = true;
} else {
    // Si las tablas no existen, no mostrar el chat
    return;
}

// Incluir los estilos de chat si no se han incluido
if (!isset($chat_styles_included)) {
    echo '<link rel="stylesheet" href="css/chat.css">';
    $chat_styles_included = true;
}
?>

<?php if ($es_cliente): ?>
<!-- Icono flotante de chat para clientes -->
<div id="chat-float" class="fixed-action-btn chat-float">
    <a class="btn-floating btn-large">
        <i class="material-icons">chat</i>
        <span id="chat-badge" class="chat-badge" style="display: none;">0</span>
    </a>
</div>

<!-- Ventana de chat -->
<div id="chat-window" class="chat-window">
    <div class="chat-header">
        <h5>Chat con Soporte</h5>
        <i id="close-chat" class="material-icons close-chat">close</i>
    </div>
    <div id="chat-messages" class="chat-messages">
        <!-- Los mensajes se cargarán dinámicamente -->
    </div>
    <div class="chat-input">
        <input type="text" id="chat-input" placeholder="Escriba un mensaje...">
        <button id="chat-send-btn">
            <i class="material-icons">send</i>
        </button>
    </div>
</div>

<!-- Script de chat para clientes -->
<script src="js/chat.js"></script>
<?php endif; ?>

<?php if ($es_admin && strpos($_SERVER['PHP_SELF'], 'admin_chat.php') === false): ?>
<!-- Icono flotante de chat para administradores (solo en páginas que no son admin_chat.php) -->
<div id="admin-chat-float" class="fixed-action-btn chat-float">
    <a class="btn-floating btn-large">
        <i class="material-icons">forum</i>
        <span id="admin-chat-badge" class="chat-badge" style="display: none;">0</span>
    </a>
</div>

<!-- Script de chat para administradores -->
<script src="js/admin_chat.js"></script>
<?php endif; ?>
