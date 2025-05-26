<?php
// enlaces.php

// Definición centralizada de enlaces
$lista_enlaces = [
    "Inicio" => "/tiendalex2/index.php",
    "Servicios" => "/tiendalex2/servicios.php",
    "Catálogo" => "/tiendalex2/catalogo.php",
    "Tienda" => "/tiendalex2/tienda.php",  // Mantenemos este enlace por compatibilidad
    "Acerca" => "/tiendalex2/acerca.php",
    "Contacto" => "/tiendalex2/contacto.php",
    "Login" => "/tiendalex2/login.php",
    "Registro" => "/tiendalex2/registro.php",
];

// Función para obtener enlaces en formato JSON para JavaScript
function obtener_enlaces_json() {
    global $lista_enlaces;
    return json_encode($lista_enlaces);
}

?>