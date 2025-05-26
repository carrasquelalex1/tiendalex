<footer class="page-footer footer-dark">
    <div class="footer-content">
        <div class="container">
            <div class="row">
                <div class="col s12">
                    <div class="footer-links">
                        <a href="#" class="enlace-dinamico" data-page="index.php">Inicio</a>
                        <a href="#" class="enlace-dinamico" data-page="servicios.php">Servicios</a>
                        <a href="#" class="enlace-dinamico" data-page="catalogo.php">Catálogo</a>
                        <a href="#" class="enlace-dinamico" data-page="acerca.php">Acerca de</a>
                        <a href="#" class="enlace-dinamico" data-page="contacto.php">Contacto</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-copyright">
        <div class="container">
            © 2025 Alexander Carrasquel - Todos los derechos reservados
            <a class="right" href="#!">Términos y Condiciones</a>
        </div>
    </div>
</footer>

<?php
// Incluir el componente de carrito flotante si el usuario es cliente
if (isset($es_cliente) && $es_cliente) {
    include 'frontend/includes/cart_float.php';
}

// Incluir el componente de chat si el usuario está logueado
if (isset($usuario_logueado) && $usuario_logueado) {
    include 'frontend/includes/chat_component.php';
}
?>

<style>
body, html { background: var(--body-bg-color) !important; background-color: var(--body-bg-color) !important; }
</style>