/**
 * Script para manejar la funcionalidad del chat para administradores
 */
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del chat
    const chatList = document.getElementById('admin-chat-list');
    const chatMessages = document.getElementById('admin-chat-messages');
    const chatInput = document.getElementById('admin-chat-input');
    const chatSendBtn = document.getElementById('admin-chat-send-btn');
    const chatUserInfo = document.getElementById('admin-chat-user-info');
    const chatPlaceholder = document.getElementById('admin-chat-placeholder');
    const chatContainer = document.getElementById('admin-chat-container');
    const chatFloat = document.getElementById('admin-chat-float');
    const chatBadge = document.getElementById('admin-chat-badge');

    // Variables para el chat
    let usuarioSeleccionado = null;
    let ultimoMensajeId = 0;
    let intervaloVerificacion = null;
    let intervaloActualizacion = null;
    let chatAbierto = false;

    // Variable para controlar si se muestran los chats cerrados
    let mostrarChatsCerrados = false;

    // Función para cargar la lista de chats activos
    function cargarChatsActivos() {
        const url = mostrarChatsCerrados ?
            'backend/chat/obtener_chats_activos.php?mostrar_cerrados=true' :
            'backend/chat/obtener_chats_activos.php';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    actualizarListaChats(data.chats);
                }
            })
            .catch(error => {
                console.error('Error al cargar chats activos:', error);
            });
    }

    // Función para alternar la visualización de chats cerrados
    function toggleChatsCerrados() {
        mostrarChatsCerrados = !mostrarChatsCerrados;

        // Actualizar el texto del botón
        const btnToggle = document.getElementById('btn-toggle-chats');
        if (btnToggle) {
            btnToggle.textContent = mostrarChatsCerrados ?
                'Ocultar chats resueltos' :
                'Mostrar chats resueltos';
        }

        // Recargar la lista de chats
        cargarChatsActivos();
    }

    // Función para cerrar un chat
    function cerrarChat(usuarioId) {
        if (!confirm('¿Está seguro de que desea marcar este chat como resuelto?')) {
            return;
        }

        const formData = new FormData();
        formData.append('usuario_id', usuarioId);

        fetch('backend/chat/cerrar_chat.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                M.toast({html: data.message, classes: 'green'});

                // Si el chat cerrado es el que está seleccionado actualmente, recargar los mensajes
                if (usuarioSeleccionado == usuarioId) {
                    cargarMensajes();
                }

                // Recargar la lista de chats
                cargarChatsActivos();
            } else {
                M.toast({html: data.message, classes: 'red'});
            }
        })
        .catch(error => {
            console.error('Error al cerrar el chat:', error);
            M.toast({html: 'Error al cerrar el chat', classes: 'red'});
        });
    }

    // Función para eliminar definitivamente un chat
    function eliminarChat(usuarioId) {
        if (!confirm('¿Está seguro de que desea eliminar definitivamente este chat? Esta acción no se puede deshacer.')) {
            return;
        }

        const formData = new FormData();
        formData.append('usuario_id', usuarioId);

        fetch('backend/chat/eliminar_chat.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                M.toast({html: data.message, classes: 'green'});

                // Si el chat eliminado es el que está seleccionado actualmente, limpiar la selección
                if (usuarioSeleccionado == usuarioId) {
                    usuarioSeleccionado = null;

                    // Mostrar el placeholder
                    if (chatPlaceholder) {
                        chatPlaceholder.style.display = 'flex';
                    }

                    // Ocultar el área de mensajes
                    if (chatMessages && chatMessages.parentElement) {
                        chatMessages.parentElement.style.display = 'none';
                    }

                    // Actualizar el título
                    if (chatUserInfo) {
                        chatUserInfo.textContent = 'Seleccione un chat';
                    }
                }

                // Recargar la lista de chats
                cargarChatsActivos();
            } else {
                M.toast({html: data.message, classes: 'red'});
            }
        })
        .catch(error => {
            console.error('Error al eliminar el chat:', error);
            M.toast({html: 'Error al eliminar el chat', classes: 'red'});
        });
    }

    // Función para actualizar la lista de chats
    function actualizarListaChats(chats) {
        if (!chatList) return;

        // Limpiar la lista
        chatList.innerHTML = '';

        // Agregar botón para mostrar/ocultar chats cerrados
        const toggleButton = document.createElement('button');
        toggleButton.id = 'btn-toggle-chats';
        toggleButton.className = 'btn-flat waves-effect waves-light blue-text';
        toggleButton.style.width = '100%';
        toggleButton.style.margin = '10px 0';
        toggleButton.textContent = mostrarChatsCerrados ? 'Ocultar chats resueltos' : 'Mostrar chats resueltos';
        toggleButton.addEventListener('click', toggleChatsCerrados);
        chatList.appendChild(toggleButton);

        if (chats.length === 0) {
            const emptyMessage = document.createElement('div');
            emptyMessage.className = 'center-align grey-text';
            emptyMessage.style.padding = '20px';
            emptyMessage.textContent = mostrarChatsCerrados ? 'No hay chats' : 'No hay chats activos';
            chatList.appendChild(emptyMessage);
            return;
        }

        // Agregar cada chat a la lista
        chats.forEach(chat => {
            const chatItem = document.createElement('div');
            chatItem.className = 'chat-list-item';
            chatItem.setAttribute('data-usuario-id', chat.usuario_id);
            chatItem.setAttribute('data-estado', chat.estado);

            // Aplicar clases según el estado
            if (chat.estado === 'cerrada') {
                chatItem.classList.add('closed');
            } else if (chat.mensajes_no_leidos > 0) {
                chatItem.classList.add('unread');
            }

            if (usuarioSeleccionado && chat.usuario_id == usuarioSeleccionado) {
                chatItem.classList.add('active');
            }

            // Contenedor para la información del chat
            const chatInfo = document.createElement('div');
            chatInfo.className = 'chat-info';
            chatInfo.style.flex = '1';

            // Nombre de usuario y badge de mensajes no leídos
            const chatUser = document.createElement('div');
            chatUser.className = 'chat-user';
            chatUser.textContent = chat.nombre_usuario;

            if (chat.mensajes_no_leidos > 0) {
                const unreadBadge = document.createElement('span');
                unreadBadge.className = 'unread-badge';
                unreadBadge.textContent = chat.mensajes_no_leidos;
                chatUser.appendChild(unreadBadge);
            }

            // Estado del chat
            const chatStatus = document.createElement('div');
            chatStatus.className = 'chat-status';
            chatStatus.textContent = chat.estado === 'activa' ? 'Activo' : 'Resuelto';
            chatStatus.style.fontSize = '12px';
            chatStatus.style.color = chat.estado === 'activa' ? '#4CAF50' : '#9E9E9E';

            // Fecha del último mensaje
            const chatTime = document.createElement('div');
            chatTime.className = 'chat-time';
            chatTime.textContent = chat.fecha_formateada;

            // Agregar elementos al contenedor de información
            chatInfo.appendChild(chatUser);
            chatInfo.appendChild(chatStatus);
            chatInfo.appendChild(chatTime);

            // Agregar el contenedor de información al item del chat
            chatItem.appendChild(chatInfo);

            // Contenedor para los botones de acción
            const actionButtons = document.createElement('div');
            actionButtons.className = 'chat-action-buttons';

            // Si el chat está activo, agregar botón para cerrarlo
            if (chat.estado === 'activa') {
                const closeButton = document.createElement('button');
                closeButton.className = 'btn-flat waves-effect waves-light red-text';
                closeButton.innerHTML = '<i class="material-icons">check_circle</i>';
                closeButton.title = 'Marcar como resuelto';
                closeButton.style.marginLeft = '10px';

                // Evento para cerrar el chat
                closeButton.addEventListener('click', function(e) {
                    e.stopPropagation(); // Evitar que se seleccione el chat
                    cerrarChat(chat.usuario_id);
                });

                actionButtons.appendChild(closeButton);
            }
            // Si el chat está cerrado, agregar botón para eliminarlo
            else if (chat.estado === 'cerrada') {
                const deleteButton = document.createElement('button');
                deleteButton.className = 'btn-flat waves-effect waves-light red-text';
                deleteButton.innerHTML = '<i class="material-icons">delete_forever</i>';
                deleteButton.title = 'Eliminar definitivamente';
                deleteButton.style.marginLeft = '10px';

                // Evento para eliminar el chat
                deleteButton.addEventListener('click', function(e) {
                    e.stopPropagation(); // Evitar que se seleccione el chat
                    eliminarChat(chat.usuario_id);
                });

                actionButtons.appendChild(deleteButton);
            }

            chatItem.appendChild(actionButtons);

            // Evento para seleccionar el chat
            chatItem.addEventListener('click', function() {
                seleccionarChat(chat.usuario_id, chat.nombre_usuario);
            });

            chatList.appendChild(chatItem);
        });
    }

    // Función para seleccionar un chat
    function seleccionarChat(usuarioId, nombreUsuario) {
        // Actualizar usuario seleccionado
        usuarioSeleccionado = usuarioId;
        ultimoMensajeId = 0;

        // Actualizar UI
        document.querySelectorAll('.chat-list-item').forEach(item => {
            item.classList.remove('active');
        });

        const chatItem = document.querySelector(`.chat-list-item[data-usuario-id="${usuarioId}"]`);
        if (chatItem) {
            chatItem.classList.add('active');
            chatItem.classList.remove('unread');
        }

        // Mostrar información del usuario
        if (chatUserInfo) {
            chatUserInfo.textContent = nombreUsuario;
        }

        // Mostrar el contenedor de chat y ocultar el placeholder
        if (chatPlaceholder) {
            chatPlaceholder.style.display = 'none';
        }

        if (chatMessages && chatInput && chatSendBtn) {
            chatMessages.parentElement.style.display = 'flex';
            chatInput.disabled = false;
            chatSendBtn.disabled = false;
        }

        // Limpiar mensajes anteriores
        if (chatMessages) {
            chatMessages.innerHTML = '';
        }

        // Cargar mensajes
        cargarMensajes();

        // Iniciar intervalo de actualización
        if (intervaloActualizacion !== null) {
            clearInterval(intervaloActualizacion);
        }
        intervaloActualizacion = setInterval(cargarMensajes, 5000);
    }

    // Función para cargar mensajes
    function cargarMensajes() {
        if (!usuarioSeleccionado) return;

        fetch(`backend/chat/obtener_mensajes.php?usuario_id=${usuarioSeleccionado}&ultimo_mensaje_id=${ultimoMensajeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Agregar mensajes nuevos
                    if (data.mensajes.length > 0) {
                        data.mensajes.forEach(mensaje => {
                            agregarMensaje(mensaje);
                            ultimoMensajeId = Math.max(ultimoMensajeId, mensaje.id);
                        });

                        // Desplazar al final
                        if (chatMessages) {
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error al cargar mensajes:', error);
            });
    }

    // Función para agregar un mensaje al chat
    function agregarMensaje(mensaje) {
        if (!chatMessages) return;

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
        if (!chatInput || !usuarioSeleccionado) return;

        const mensaje = chatInput.value.trim();

        if (mensaje === '') {
            return;
        }

        // Deshabilitar el botón mientras se envía
        if (chatSendBtn) {
            chatSendBtn.disabled = true;
        }

        // Crear FormData para enviar
        const formData = new FormData();
        formData.append('mensaje', mensaje);
        formData.append('usuario_id', usuarioSeleccionado);

        fetch('backend/chat/enviar_mensaje.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Limpiar el campo de entrada
                chatInput.value = '';

                // Cargar mensajes para ver el mensaje enviado
                cargarMensajes();
            } else {
                console.error('Error al enviar mensaje:', data.message);
                M.toast({html: data.message, classes: 'red'});
            }

            // Habilitar el botón nuevamente
            if (chatSendBtn) {
                chatSendBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error al enviar mensaje:', error);
            M.toast({html: 'Error al enviar el mensaje', classes: 'red'});
            if (chatSendBtn) {
                chatSendBtn.disabled = false;
            }
        });
    }

    // Función para verificar mensajes nuevos
    function verificarMensajesNuevos() {
        fetch('backend/chat/verificar_mensajes_nuevos.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.tiene_mensajes_nuevos) {
                    // Mostrar indicador de mensajes nuevos
                    if (chatFloat && chatBadge) {
                        chatFloat.classList.add('new-message');
                        chatBadge.style.display = 'flex';
                        chatBadge.textContent = data.cantidad_mensajes;
                    }

                    // Actualizar la lista de chats
                    cargarChatsActivos();

                    // Si hay un chat seleccionado, actualizar los mensajes
                    if (usuarioSeleccionado) {
                        cargarMensajes();
                    }
                }
            })
            .catch(error => {
                console.error('Error al verificar mensajes nuevos:', error);
            });
    }

    // Inicializar
    if (chatContainer) {
        // Cargar chats activos
        cargarChatsActivos();

        // Iniciar verificación de mensajes nuevos
        intervaloVerificacion = setInterval(verificarMensajesNuevos, 10000);
        verificarMensajesNuevos(); // Verificar inmediatamente al cargar
    }

    // Event listeners
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

    // Evento para el icono flotante (si existe)
    if (chatFloat) {
        chatFloat.addEventListener('click', function() {
            window.location.href = 'admin_chat.php';
        });
    }
});
