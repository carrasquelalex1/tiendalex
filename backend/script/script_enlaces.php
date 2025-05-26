<?php
// Incluir el archivo de enlaces
include_once __DIR__ . '/../../frontend/includes/enlaces.php';

// Obtener los enlaces como JSON para JavaScript
$enlaces_json = obtener_enlaces_json();
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // console.log('Inicializando enlaces dinámicos...'); // Opcional para depuración

    const enlaces = <?php echo $enlaces_json; ?>;
    // console.log('Enlaces disponibles:', enlaces); // Opcional para depuración

    // Función para manejar la navegación
    function navegarA(claveEnlace) {
        // console.log('Intentando navegar a la clave:', claveEnlace); // Opcional
        if (enlaces && enlaces[claveEnlace]) {
            // console.log('URL destino:', enlaces[claveEnlace]); // Opcional
            window.location.href = enlaces[claveEnlace];
            return true;
        }
        console.warn('Clave de enlace no encontrada en la lista:', claveEnlace);
        return false;
    }

    // Mapeo de palabras clave (o partes del texto) a las claves de enlace deseadas
    // Las claves del objeto son las claves que se usarán en `enlaces`
    // Los valores son arrays de subcadenas que identifican esa clave
    const mapeoTextosAClaves = {
        'Inicio': ['home', 'Inicio'],
        'Servicios': ['business', 'Servicios'],
        'Catálogo': ['view_list', 'Catálogo'],
        'Tienda': ['shopping_cart', 'Tienda'],
        'Acerca': ['info', 'Acerca'],
        'Contacto': ['contact_mail', 'Contacto'],
        'Login': ['login', 'Login', 'Iniciar Sesión'],
        'Registro': ['person_add', 'Registro', 'Registrarse']
    };

    function obtenerClaveDesdeTexto(textoElemento) {
        for (const [clave, palabrasClave] of Object.entries(mapeoTextosAClaves)) {
            if (palabrasClave.some(palabra => textoElemento.includes(palabra))) {
                return clave;
            }
        }
        return textoElemento; // Fallback: usar el texto original si no hay mapeo
    }

    document.querySelectorAll('.enlace-dinamico').forEach(function(enlace) {
        enlace.addEventListener('click', function(e) {
            e.preventDefault();

            let claveEnlace;

            if (this.classList.contains('brand-logo')) {
                claveEnlace = 'Inicio';
            } else {
                const textoDelElemento = this.textContent.trim();
                claveEnlace = obtenerClaveDesdeTexto(textoDelElemento);
            }

            // console.log('Clave de enlace identificada:', claveEnlace); // Opcional
            navegarA(claveEnlace);
        });
    });

    // Exponer la función de navegación globalmente (si es realmente necesario)
    window.navegarA = navegarA;
});
</script>