<?php
/**
 * Componente de mensajes reutilizable
 *
 * Este archivo contiene el código para mostrar mensajes de éxito y error
 * de manera consistente en toda la aplicación.
 */

// Verificar si hay mensajes
$success_message = '';
$error_message = '';

// Registrar información de depuración
error_log("messages.php - GET params: " . print_r($_GET, true));

if (isset($_GET['success'])) {
    if ($_GET['success'] == 1 && isset($_GET['message'])) {
        $success_message = $_GET['message'];
    } else if ($_GET['success'] == 1) {
        $success_message = 'Operación realizada correctamente.';
    } else if (isset($_GET['message'])) {
        $success_message = $_GET['message'];
    }
}

if (isset($_GET['error'])) {
    if (isset($_GET['message'])) {
        $error_message = $_GET['message'];
    } else {
        $error_message = 'Ha ocurrido un error al procesar la solicitud.';
    }
}
?>

<!-- Mensajes de éxito y error con mejor visibilidad -->
<?php if (!empty($success_message)): ?>
<div class="message-container" id="success-message">
    <div class="container">
        <div class="row">
            <div class="col s12">
                <div class="card-panel green lighten-4">
                    <span class="green-text text-darken-4"><i class="material-icons left">check_circle</i><?php echo $success_message; ?></span>
                    <button class="btn-flat waves-effect waves-light right close-message"><i class="material-icons">close</i></button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="message-container" id="error-message">
    <div class="container">
        <div class="row">
            <div class="col s12">
                <div class="card-panel red lighten-4">
                    <span class="red-text text-darken-4"><i class="material-icons left">error</i><?php echo $error_message; ?></span>
                    <button class="btn-flat waves-effect waves-light right close-message"><i class="material-icons">close</i></button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Script para manejar los mensajes -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar mensajes de éxito y error
    document.querySelectorAll('.close-message').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var messageContainer = this.closest('.message-container');
            if (messageContainer) {
                // Animación de desvanecimiento
                messageContainer.style.opacity = '0';
                messageContainer.style.transform = 'translateY(-20px)';

                // Eliminar el elemento después de la animación
                setTimeout(function() {
                    messageContainer.style.display = 'none';
                }, 300);
            }
        });
    });

    // Mostrar mensajes si existen
    var successMessage = document.getElementById('success-message');
    var errorMessage = document.getElementById('error-message');

    if (successMessage || errorMessage) {
        // Asegurar que los mensajes sean visibles
        if (successMessage) successMessage.style.display = 'block';
        if (errorMessage) errorMessage.style.display = 'block';

        // Ocultar mensajes automáticamente después de 6 segundos
        setTimeout(function() {
            document.querySelectorAll('#success-message, #error-message').forEach(function(el) {
                if (el) {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-20px)';

                    setTimeout(function() {
                        el.style.display = 'none';
                    }, 300);
                }
            });
        }, 6000);
    }
});
</script>
