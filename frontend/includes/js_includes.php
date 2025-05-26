<?php
/**
 * Archivo para incluir los scripts JavaScript comunes
 * Este archivo se incluye en todas las páginas del sitio
 */
?>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Materialize JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<!-- JavaScript personalizado -->
<script src="/tiendalex2/js/scripts.js"></script>
<script src="/tiendalex2/js/floating-buttons.js"></script>
<script src="/tiendalex2/js/theme_updater.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar componentes de Materialize
        M.AutoInit();

        // Configurar enlaces dinámicos
        if (typeof setupDynamicLinks === 'function') {
            setupDynamicLinks();
        }
    });
</script>
