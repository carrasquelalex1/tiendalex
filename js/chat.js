/**
 * Script para manejar la funcionalidad del chat
 */
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del chat
    const chatFloat = document.getElementById('chat-float');
    const chatWindow = document.getElementById('chat-window');
    const chatMessages = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');
    const chatSendBtn = document.getElementById('chat-send-btn');
    const chatBadge = document.getElementById('chat-badge');
    const closeChat = document.getElementById('close-chat');

    // Variables para el chat
    let ultimoMensajeId = 0;
    let chatAbierto = false;
    let intervaloVerificacion = null;
    let intervaloActualizacion = null;
    let chatActivo = false; // Variable para controlar si hay un chat activo

    // Función para abrir la ventana de chat
    function abrirChat() {
        chatWindow.classList.add('active');
        chatFloat.classList.remove('new-message');
        chatBadge.style.display = 'none';
        chatAbierto = true;

        // Cargar mensajes
        cargarMensajes();

        // Iniciar intervalo de actualización
        if (intervaloActualizacion === null) {
            intervaloActualizacion = setInterval(cargarMensajes, 5000);
        }

        // Enfocar el campo de entrada
        setTimeout(() => {
            chatInput.focus();
        }, 300);
    }

    // Función para cerrar la ventana de chat
    function cerrarChat() {
        chatWindow.classList.remove('active');
        chatAbierto = false;

        // Detener intervalo de actualización
        if (intervaloActualizacion !== null) {
            clearInterval(intervaloActualizacion);
            intervaloActualizacion = null;
        }
    }

    // Función para cargar mensajes
    function cargarMensajes() {
        fetch(`backend/chat/obtener_mensajes.php?ultimo_mensaje_id=${ultimoMensajeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Verificar si hay mensajes en el chat
                    const mensajesAnteriores = document.querySelectorAll('.chat-message').length;

                    // Agregar mensajes nuevos
                    if (data.mensajes.length > 0) {
                        data.mensajes.forEach(mensaje => {
                            agregarMensaje(mensaje);
                            ultimoMensajeId = Math.max(ultimoMensajeId, mensaje.id);
                        });

                        // Desplazar al final
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }

                    // Verificar si hay mensajes después de cargar los nuevos
                    const mensajesActuales = document.querySelectorAll('.chat-message').length;

                    // Actualizar el estado del chat activo
                    const chatActivoAnterior = chatActivo;
                    chatActivo = mensajesActuales > 0;

                    // Si el estado del chat activo ha cambiado, actualizar la interfaz
                    if (chatActivoAnterior !== chatActivo) {
                        actualizarIndicadorChatActivo();
                    }

                    // Verificar si el chat está cerrado
                    if (data.sesion && data.sesion.estado === 'cerrada') {
                        // Mostrar mensaje de chat cerrado
                        mostrarMensajeChatCerrado();
                    } else {
                        // Habilitar el campo de entrada
                        if (chatInput) chatInput.disabled = false;
                        if (chatSendBtn) chatSendBtn.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error al cargar mensajes:', error);
            });
    }

    // Función para actualizar el indicador de chat activo
    function actualizarIndicadorChatActivo() {
        // Actualizar el icono flotante
        if (chatFloat) {
            if (chatActivo) {
                chatFloat.classList.add('active-chat');
            } else {
                chatFloat.classList.remove('active-chat');
            }
        }

        // Actualizar los iconos del encabezado
        const chatIcon = document.getElementById('chat-icon');
        const chatIconMobile = document.getElementById('chat-icon-mobile');

        if (chatIcon) {
            if (chatActivo) {
                chatIcon.parentElement.classList.add('active-chat');
            } else {
                chatIcon.parentElement.classList.remove('active-chat');
            }
        }

        if (chatIconMobile) {
            if (chatActivo) {
                chatIconMobile.parentElement.classList.add('active-chat');
            } else {
                chatIconMobile.parentElement.classList.remove('active-chat');
            }
        }
    }

    // Función para mostrar mensaje de chat cerrado
    function mostrarMensajeChatCerrado() {
        // Deshabilitar el campo de entrada
        if (chatInput) chatInput.disabled = true;
        if (chatSendBtn) chatSendBtn.disabled = true;

        // Mostrar mensaje informativo
        const chatCerradoDiv = document.createElement('div');
        chatCerradoDiv.className = 'chat-closed-message';
        chatCerradoDiv.innerHTML = `
            <div class="chat-closed-icon">
                <i class="material-icons">check_circle</i>
            </div>
            <p>Este chat ha sido marcado como resuelto por el administrador.</p>
            <p>Si necesita ayuda adicional, puede iniciar una nueva conversación.</p>
            <button class="btn waves-effect waves-light" id="btn-nuevo-chat">
                Iniciar nueva conversación
            </button>
        `;

        // Verificar si ya existe el mensaje
        const mensajeExistente = document.querySelector('.chat-closed-message');
        if (!mensajeExistente) {
            chatMessages.appendChild(chatCerradoDiv);

            // Agregar evento al botón
            const btnNuevoChat = document.getElementById('btn-nuevo-chat');
            if (btnNuevoChat) {
                btnNuevoChat.addEventListener('click', iniciarNuevoChat);
            }
        }
    }

    // Función para iniciar un nuevo chat
    function iniciarNuevoChat() {
        // Enviar mensaje al servidor para crear una nueva sesión
        const formData = new FormData();
        formData.append('mensaje', 'Hola, necesito ayuda adicional.');

        fetch('backend/chat/enviar_mensaje.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Limpiar mensajes anteriores
                chatMessages.innerHTML = '';

                // Habilitar el campo de entrada
                if (chatInput) chatInput.disabled = false;
                if (chatSendBtn) chatSendBtn.disabled = false;

                // Cargar mensajes nuevos
                ultimoMensajeId = 0;

                // Actualizar el estado del chat activo
                chatActivo = true;
                actualizarIndicadorChatActivo();

                cargarMensajes();
            } else {
                console.error('Error al iniciar nuevo chat:', data.message);
                M.toast({html: data.message, classes: 'red'});
            }
        })
        .catch(error => {
            console.error('Error al iniciar nuevo chat:', error);
            M.toast({html: 'Error al iniciar nuevo chat', classes: 'red'});
        });
    }

    // Función para agregar un mensaje al chat
    function agregarMensaje(mensaje) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${mensaje.enviado_por === 'usuario' ? 'user' : 'admin'}`;
        messageDiv.setAttribute('data-id', mensaje.id);

        const messageContent = document.createElement('div');
        messageContent.className = 'message-content';
        messageContent.textContent = mensaje.mensaje;

        const messageTime = document.createElement('div');
        messageTime.className = 'message-time';
        messageTime.textContent = mensaje.fecha_formateada;

        messageDiv.appendChild(messageContent);
        messageDiv.appendChild(messageTime);

        // Verificar si el mensaje ya existe
        const mensajeExistente = document.querySelector(`.chat-message[data-id="${mensaje.id}"]`);
        if (!mensajeExistente) {
            chatMessages.appendChild(messageDiv);
        }
    }

    // Función para enviar un mensaje
    function enviarMensaje() {
        const mensaje = chatInput.value.trim();

        if (mensaje === '') {
            return;
        }

        // Deshabilitar el botón mientras se envía
        chatSendBtn.disabled = true;

        // Crear FormData para enviar
        const formData = new FormData();
        formData.append('mensaje', mensaje);

        fetch('backend/chat/enviar_mensaje.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Limpiar el campo de entrada
                chatInput.value = '';

                // Actualizar el estado del chat activo
                chatActivo = true;
                actualizarIndicadorChatActivo();

                // Cargar mensajes para ver el mensaje enviado
                cargarMensajes();
            } else {
                console.error('Error al enviar mensaje:', data.message);
                M.toast({html: data.message, classes: 'red'});
            }

            // Habilitar el botón nuevamente
            chatSendBtn.disabled = false;
        })
        .catch(error => {
            console.error('Error al enviar mensaje:', error);
            M.toast({html: 'Error al enviar el mensaje', classes: 'red'});
            chatSendBtn.disabled = false;
        });
    }

    // Función para verificar mensajes nuevos
    function verificarMensajesNuevos() {
        fetch('backend/chat/verificar_mensajes_nuevos.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.tiene_mensajes_nuevos) {
                    // Mostrar indicador de mensajes nuevos
                    if (!chatAbierto) {
                        chatFloat.classList.add('new-message');
                        chatBadge.style.display = 'flex';
                        chatBadge.textContent = data.cantidad_mensajes;
                    } else {
                        // Si el chat está abierto, cargar los mensajes nuevos
                        cargarMensajes();
                    }
                }
            })
            .catch(error => {
                console.error('Error al verificar mensajes nuevos:', error);
            });
    }

    // Iniciar verificación de mensajes nuevos
    intervaloVerificacion = setInterval(verificarMensajesNuevos, 10000);
    verificarMensajesNuevos(); // Verificar inmediatamente al cargar

    // Cargar mensajes al inicio para verificar si hay un chat activo
    cargarMensajes();

    // Event listeners
    if (chatFloat) {
        chatFloat.addEventListener('click', abrirChat);
    }

    if (closeChat) {
        closeChat.addEventListener('click', cerrarChat);
    }

    if (chatSendBtn) {
        chatSendBtn.addEventListener('click', enviarMensaje);
    }

    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                enviarMensaje();
            }
        });
    }

    // Agregar eventos a los iconos del encabezado
    const chatIcon = document.getElementById('chat-icon');
    if (chatIcon) {
        chatIcon.addEventListener('click', abrirChat);
    }

    const chatIconMobile = document.getElementById('chat-icon-mobile');
    if (chatIconMobile) {
        chatIconMobile.addEventListener('click', abrirChat);
    }
});
