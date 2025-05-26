<main>
    <!-- Primera sección con parallax -->
    <div class="parallax-container first-parallax" style="height: 500px;">
        <div class="parallax">
            <img src="backend/images/a.jpg" alt="Imagen Parallax 1">
        </div>
        <div class="container">
            <div class="row">
                <div class="col s12 center-align" style="margin-top: 150px;">
                    <h2 class="white-text text-shadow">Bienvenido a Alexander Carrasquel</h2>
                    <h5 class="white-text text-shadow">Soluciones innovadoras para tu negocio</h5>
                    <a href="#servicios" class="btn-large waves-effect waves-light blue lighten-1">Nuestros Servicios</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de contenido -->
    <div class="section white">
        <div class="container">
            <div class="row">
                <div class="col s12 m4">
                    <div class="center-align">
                        <i class="large material-icons blue-text">flash_on</i>
                        <h5>Velocidad</h5>
                        <p class="light">Soluciones rápidas y eficientes para tu negocio, optimizadas para un rendimiento excepcional.</p>
                    </div>
                </div>
                <div class="col s12 m4">
                    <div class="center-align">
                        <i class="large material-icons blue-text">group</i>
                        <h5>Experiencia de Usuario</h5>
                        <p class="light">Diseños intuitivos y atractivos que mejoran la experiencia de tus clientes.</p>
                    </div>
                </div>
                <div class="col s12 m4">
                    <div class="center-align">
                        <i class="large material-icons blue-text">settings</i>
                        <h5>Fácil Mantenimiento</h5>
                        <p class="light">Sistemas diseñados para ser fácilmente mantenibles y actualizables.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Segunda sección con parallax -->
    <div id="servicios" class="parallax-container" style="height: 400px;">
        <div class="parallax">
            <img src="backend/images/b.jpg" alt="Imagen Parallax 2">
        </div>
        <div class="container">
            <div class="row">
                <div class="col s12 center-align" style="margin-top: 100px;">
                    <h3 class="white-text text-shadow">Nuestros Servicios</h3>
                    <h5 class="white-text text-shadow">Soluciones adaptadas a tus necesidades</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de servicios -->
    <div class="section white">
        <div class="container">
            <div class="row">
                <div class="col s12 m6">
                    <div class="card ilike-blue-container hoverable">
                        <div class="card-content">
                            <span class="card-title">Desarrollo Web</span>
                            <p>Creamos sitios web modernos, responsivos y optimizados para buscadores.</p>
                        </div>
                        <div class="card-action">
                            <a href="#" class="blue-text enlace-dinamico">Servicios</a>
                        </div>
                    </div>
                </div>
                <div class="col s12 m6">
                    <div class="card ilike-blue-container hoverable">
                        <div class="card-content">
                            <span class="card-title">E-commerce</span>
                            <p>Implementamos soluciones de comercio electrónico seguras y eficientes.</p>
                        </div>
                        <div class="card-action">
                            <a href="#" class="blue-text enlace-dinamico">Servicios</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col s12 m6">
                    <div class="card ilike-blue-container hoverable">
                        <div class="card-content">
                            <span class="card-title">Aplicaciones Móviles</span>
                            <p>Desarrollamos aplicaciones nativas y multiplataforma para iOS y Android.</p>
                        </div>
                        <div class="card-action">
                            <a href="#" class="blue-text enlace-dinamico">Servicios</a>
                        </div>
                    </div>
                </div>
                <div class="col s12 m6">
                    <div class="card ilike-blue-container hoverable">
                        <div class="card-content">
                            <span class="card-title">Consultoría IT</span>
                            <p>Asesoramos a empresas en la implementación de soluciones tecnológicas.</p>
                        </div>
                        <div class="card-action">
                            <a href="#" class="blue-text enlace-dinamico">Servicios</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Inicialización del parallax -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var elems = document.querySelectorAll('.parallax');
    var instances = M.Parallax.init(elems);
});
</script>

<!-- Estilos adicionales -->
<style>
.text-shadow {
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}
.parallax-container {
    color: white;
}
.card.hoverable {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card.hoverable:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 15px 0 rgba(0,0,0,0.24), 0 17px 50px 0 rgba(0,0,0,0.19);
}
.btn-large {
    margin-top: 20px;
    border-radius: 30px;
    padding: 0 30px;
}
.large.material-icons {
    font-size: 4rem;
    margin-bottom: 15px;
}
.section {
    padding-top: 3rem;
    padding-bottom: 3rem;
}
</style>
