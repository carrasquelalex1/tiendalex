<?php
/**
 * Página de prueba para el sistema de chat
 */

// Incluir el helper de sesiones
require_once 'backend/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    header("Location: login.php");
    exit;
}

// Incluir archivos necesarios
require_once 'backend/config/db.php';

// Título de la página
$titulo_pagina = "Prueba de Chat";

// Incluir el encabezado
include 'frontend/includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Chat</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <link rel="stylesheet" href="css/real-estate.css">
    <link rel="stylesheet" href="css/chat.css">
    <link rel="stylesheet" href="css/custom.css">
</head>
<body>
    <div class="container">
        <h4 class="page-title">Prueba de Chat</h4>

        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Sistema de Chat</span>
                        <p>Esta es una página de prueba para el sistema de chat. Utiliza el icono flotante para iniciar una conversación.</p>

                        <?php if ($es_cliente): ?>
                        <div class="section">
                            <h5>Instrucciones para Clientes</h5>
                            <p>Haz clic en el icono de chat flotante para iniciar una conversación con el soporte.</p>
                        </div>
                        <?php endif; ?>

                        <?php if ($es_admin): ?>
                        <div class="section">
                            <h5>Instrucciones para Administradores</h5>
                            <p>Puedes acceder al panel de administración de chat desde <a href="admin_chat.php">aquí</a>.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($es_cliente): ?>
    <!-- Icono flotante de chat para clientes -->
    <div id="chat-float" class="chat-float">
        <i class="material-icons">chat</i>
        <span id="chat-badge" class="chat-badge" style="display: none;">0</span>
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
    <?php endif; ?>

    <?php if ($es_admin): ?>
    <!-- Icono flotante de chat para administradores -->
    <div id="admin-chat-float" class="chat-float">
        <i class="material-icons">forum</i>
        <span id="admin-chat-badge" class="chat-badge" style="display: none;">0</span>
    </div>
    <?php endif; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Materialize JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <!-- JavaScript personalizado -->
    <script src="js/scripts.js"></script>

    <?php if ($es_cliente): ?>
    <!-- Script de chat para clientes -->
    <script src="js/chat.js"></script>
    <?php endif; ?>

    <?php if ($es_admin): ?>
    <!-- Script de chat para administradores -->
    <script src="js/admin_chat.js"></script>
    <?php endif; ?>

    <?php include 'backend/script/script_enlaces.php'; ?>
</body>
</html>
<?php
require_once __DIR__ . '/autoload.php';
