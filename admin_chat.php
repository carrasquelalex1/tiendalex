<?php
require_once __DIR__ . '/autoload.php';
/**
 * Página de administración de chat
 * Permite al administrador gestionar las conversaciones con los clientes
 */

// Incluir el helper de sesiones
require_once 'helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado y es administrador
if (!esta_logueado() || !es_admin()) {
    header("Location: index.php");
    exit;
}

// Incluir archivos necesarios
require_once 'backend/config/db.php';

// Título de la página
$titulo_pagina = "Administración de Chat";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Chat - Alexander Carrasquel</title>
    <!-- Materialize CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Estilos personalizados -->
    <?php include 'frontend/includes/css_includes.php'; ?><link rel="stylesheet" href="css/chat.css">
    <style>
        /* Estilos específicos para la página de administración de chat */
        body {
            background-color: #f5f5f5;
            font-family: 'Roboto', sans-serif;
        }

        .page-title {
            margin: 30px 0;
            color: #333;
            font-weight: 500;
        }

        .admin-chat-container {
            display: flex;
            height: 600px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .chat-list {
            width: 300px;
            border-right: 1px solid #e0e0e0;
            overflow-y: auto;
            background-color: white;
        }

        .admin-chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: white;
        }

        .chat-header {
            background-color: #2c3e50;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h5 {
            margin: 0;
            font-size: 18px;
            font-weight: 500;
        }

        .admin-chat-placeholder {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f5f5f5;
            color: #757575;
            font-style: italic;
        }

        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: #f5f5f5;
        }

        .chat-input {
            padding: 15px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            background-color: white;
        }

        .chat-input input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 30px;
            outline: none;
        }

        .chat-input button {
            margin-left: 10px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #2196F3;
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .chat-list-item {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
        }

        .chat-list-item:hover {
            background-color: #f5f5f5;
        }

        .chat-list-item.active {
            background-color: #E3F2FD;
        }

        .chat-list-item.unread {
            background-color: #BBDEFB;
        }

        .chat-list-item.closed {
            background-color: #F5F5F5;
            opacity: 0.8;
        }

        .chat-user {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .chat-time {
            font-size: 12px;
            color: #9e9e9e;
        }

        .chat-status {
            font-size: 12px;
            margin-bottom: 5px;
        }

        .unread-badge {
            display: inline-block;
            background-color: #F44336;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            text-align: center;
            line-height: 20px;
            margin-left: 5px;
        }

        /* Estilos responsivos */
        @media (max-width: 768px) {
            .admin-chat-container {
                flex-direction: column;
                height: auto;
            }

            .chat-list {
                width: 100%;
                max-height: 300px;
            }
        }

        /* Estilos para los mensajes de chat */
        .chat-message {
            margin-bottom: 15px;
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 18px;
            position: relative;
            clear: both;
            word-wrap: break-word;
        }

        .chat-message.user {
            background-color: #E3F2FD;
            color: #333;
            float: right;
            border-bottom-right-radius: 4px;
        }

        .chat-message.admin {
            background-color: #2c3e50;
            color: white;
            float: left;
            border-bottom-left-radius: 4px;
        }

        .chat-message .message-content {
            margin-bottom: 5px;
        }

        .chat-message .message-time {
            font-size: 11px;
            opacity: 0.7;
            text-align: right;
        }

        /* Estilos para el botón de toggle */
        #btn-toggle-chats {
            background-color: transparent;
            color: #2c3e50;
            border: 1px solid #2c3e50;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #btn-toggle-chats:hover {
            background-color: rgba(44, 62, 80, 0.1);
        }

        /* Estilos para los botones de acción */
        .chat-action-buttons {
            display: flex;
            align-items: center;
        }

        .chat-action-buttons button {
            padding: 5px;
            margin-left: 5px;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .chat-action-buttons button:hover {
            background-color: rgba(0, 0, 0, 0.05);
            transform: scale(1.1);
        }

        .chat-action-buttons .material-icons {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <?php include 'frontend/includes/header.php'; ?>

    <div class="container">
        <h4 class="page-title">Administración de Chat</h4>

        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title">Conversaciones con Clientes</span>
                        <p>Gestione las conversaciones con los clientes desde este panel.</p>

                        <div id="admin-chat-container" class="admin-chat-container">
                            <!-- Lista de chats -->
                            <div id="admin-chat-list" class="chat-list">
                                <!-- Los chats se cargarán dinámicamente -->
                                <div class="center-align grey-text" style="padding: 20px;">
                                    Cargando chats...
                                </div>
                            </div>

                            <!-- Área de chat -->
                            <div class="admin-chat-main">
                                <!-- Encabezado del chat -->
                                <div class="chat-header">
                                    <h5 id="admin-chat-user-info">Seleccione un chat</h5>
                                </div>

                                <!-- Placeholder cuando no hay chat seleccionado -->
                                <div id="admin-chat-placeholder" class="admin-chat-placeholder">
                                    <div class="center-align grey-text">
                                        <i class="material-icons large">chat</i>
                                        <p>Seleccione una conversación para comenzar a chatear</p>
                                    </div>
                                </div>

                                <!-- Mensajes del chat (oculto inicialmente) -->
                                <div style="display: none; flex-direction: column; flex: 1;">
                                    <div id="admin-chat-messages" class="chat-messages"></div>

                                    <!-- Entrada de chat -->
                                    <div class="chat-input">
                                        <input type="text" id="admin-chat-input" placeholder="Escriba un mensaje..." disabled>
                                        <button id="admin-chat-send-btn" disabled>
                                            <i class="material-icons">send</i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'frontend/includes/footer.php'; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Materialize JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <!-- JavaScript personalizado -->
    <script src="js/scripts.js"></script>
    <!-- JavaScript para el chat de administrador -->
    <script src="js/admin_chat.js"></script>

    <script>
        // Inicializar componentes de Materialize
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar sidenav
            var elems = document.querySelectorAll('.sidenav');
            var instances = M.Sidenav.init(elems);

            // Inicializar tooltips
            var tooltips = document.querySelectorAll('.tooltipped');
            var tooltipInstances = M.Tooltip.init(tooltips);
        });
    </script>
</body>
</html>
