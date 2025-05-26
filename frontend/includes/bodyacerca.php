<main>
    <!-- Encabezado con parallax -->
    <div class="parallax-container first-parallax" style="height: 300px;">
        <div class="parallax">
            <img src="backend/images/a.jpg" alt="Acerca de">
        </div>
        <div class="container">
            <div class="row">
                <div class="col s12 center-align" style="margin-top: 100px;">
                    <h2 class="white-text text-shadow">Acerca de Nosotros</h2>
                    <h5 class="white-text text-shadow">Conoce nuestra historia y valores</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de contenido en construcción -->
    <div class="section white">
        <div class="container">
            <div class="row">
                <div class="col s12 center-align">
                    <i class="large material-icons blue-text">engineering</i>
                    <h4>Página en Construcción</h4>
                    <div class="progress blue lighten-4">
                        <div class="indeterminate blue"></div>
                    </div>
                    <p class="flow-text">Estamos trabajando para brindarte más información sobre nuestra empresa.</p>
                    <p>Muy pronto podrás conocer nuestra historia, misión, visión y el equipo que hace posible nuestros servicios.</p>

                    <div class="row" style="margin-top: 40px;">
                        <div class="col s12 m4">
                            <div class="card-panel ilike-blue-container center-align">
                                <i class="medium material-icons blue-text">history</i>
                                <h5>Historia</h5>
                                <p>Próximamente conocerás cómo comenzamos y nuestra trayectoria.</p>
                            </div>
                        </div>
                        <div class="col s12 m4">
                            <div class="card-panel ilike-blue-container center-align">
                                <i class="medium material-icons blue-text">groups</i>
                                <h5>Equipo</h5>
                                <p>Pronto te presentaremos a los profesionales detrás de nuestros servicios.</p>
                            </div>
                        </div>
                        <div class="col s12 m4">
                            <div class="card-panel ilike-blue-container center-align">
                                <i class="medium material-icons blue-text">lightbulb</i>
                                <h5>Valores</h5>
                                <p>Descubrirás los principios que guían nuestro trabajo diario.</p>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 40px;">
                        <a href="#" class="btn-large waves-effect waves-light blue enlace-dinamico">
                            <i class="material-icons left">home</i>Volver al Inicio
                        </a>
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
.card-panel {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 250px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.card-panel:hover {
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
.medium.material-icons {
    font-size: 3rem;
    margin-bottom: 10px;
}
.section {
    padding-top: 3rem;
    padding-bottom: 3rem;
}
</style>
