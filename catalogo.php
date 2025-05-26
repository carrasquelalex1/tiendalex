<?php
require_once __DIR__ . '/autoload.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - Alexander Carrasquel</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>

<body>

    <?php include 'frontend/includes/header.php'; ?>

    <?php include 'frontend/includes/page_loader.php'; ?>

    <?php include 'frontend/includes/footer.php'; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Materialize JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <!-- JavaScript personalizado -->
    <script src="js/scripts.js"></script>
    <!-- Script para funcionalidades de catálogo -->
    <script src="js/catalogo.js"></script>
    <!-- Script optimizado para corregir los botones de disponibilidad -->
    <script src="js/availability_fix_optimized.js"></script>
    <!-- Script para corregir botones de login solapados -->
    <script src="js/fix_login_buttons.js"></script>
    <!-- Script para aplicar colores del tema a los títulos -->
    <script src="js/fix_title_colors.js"></script>
    <?php include 'backend/script/script_enlaces.php'; ?>

</body>

</html>
