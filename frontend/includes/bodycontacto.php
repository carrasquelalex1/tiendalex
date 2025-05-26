<main>
    <!-- Encabezado con parallax -->
    <div class="parallax-container first-parallax" style="height: 300px;">
        <div class="parallax">
            <img src="backend/images/b.jpg" alt="Contacto">
        </div>
        <div class="container">
            <div class="row">
                <div class="col s12 center-align" style="margin-top: 100px;">
                    <h2 class="white-text text-shadow">Contáctanos</h2>
                    <h5 class="white-text text-shadow">Estamos aquí para ayudarte</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de contenido en construcción -->
    <div class="section white">
        <div class="container">
            <div class="row">
                <div class="col s12 center-align">
                    <i class="large material-icons blue-text">construction</i>
                    <h4>Formulario de Contacto en Construcción</h4>
                    <div class="progress blue lighten-4">
                        <div class="indeterminate blue"></div>
                    </div>
                    <p class="flow-text">Estamos trabajando para implementar nuestro formulario de contacto.</p>
                    <p>Mientras tanto, puedes contactarnos a través de los siguientes medios:</p>
                </div>
            </div>

            <div class="row" style="margin-top: 40px;">
                <div class="col s12 m4">
                    <div class="card-panel ilike-blue-container hoverable">
                        <div class="center-align">
                            <i class="medium material-icons blue-text">email</i>
                            <h5>Correo Electrónico</h5>
                            <p>info@alexandercarrasquel.com</p>
                            <p>soporte@alexandercarrasquel.com</p>
                        </div>
                    </div>
                </div>
                <div class="col s12 m4">
                    <div class="card-panel ilike-blue-container hoverable">
                        <div class="center-align">
                            <i class="medium material-icons blue-text">phone</i>
                            <h5>Teléfono</h5>
                            <p>+58 123 456 7890</p>
                            <p>Lunes a Viernes: 9am - 6pm</p>
                        </div>
                    </div>
                </div>
                <div class="col s12 m4">
                    <div class="card-panel ilike-blue-container hoverable">
                        <div class="center-align">
                            <i class="medium material-icons blue-text">location_on</i>
                            <h5>Dirección</h5>
                            <p>Av. Principal, Edificio Central</p>
                            <p>Caracas, Venezuela</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mapa simulado -->
            <div class="row">
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title center-align">Nuestra Ubicación</span>
                            <div class="grey lighten-3" style="height: 300px; display: flex; justify-content: center; align-items: center;">
                                <div class="center-align">
                                    <i class="large material-icons grey-text">map</i>
                                    <p>Mapa en construcción</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Redes sociales -->
            <div class="row">
                <div class="col s12 center-align">
                    <h5>Síguenos en Redes Sociales</h5>
                    <div style="margin-top: 20px;">
                        <a href="#!" class="btn-floating btn-large blue darken-1"><i class="material-icons">facebook</i></a>
                        <a href="#!" class="btn-floating btn-large blue lighten-1"><i class="material-icons">twitter</i></a>
                        <a href="#!" class="btn-floating btn-large red"><i class="material-icons">youtube_activity</i></a>
                        <a href="#!" class="btn-floating btn-large pink"><i class="material-icons">photo_camera</i></a>
                        <a href="#!" class="btn-floating btn-large blue darken-4"><i class="material-icons">business</i></a>
                    </div>
                </div>
            </div>

            <div class="row" style="margin-top: 40px;">
                <div class="col s12 center-align">
                    <a href="#" class="btn-large waves-effect waves-light blue enlace-dinamico">
                        <i class="material-icons left">home</i>Volver al Inicio
                    </a>
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
    height: 200px;
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
.btn-floating {
    margin: 0 10px;
}
</style>
