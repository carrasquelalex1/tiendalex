<?php
/**
 * Página para registrar pago de un pedido
 */

// Incluir archivos necesarios
require_once __DIR__ . '/backend/config/db.php';
require_once __DIR__ . '/helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Registrar información de depuración
error_log("registrar_pago.php - SESSION al inicio: " . print_r($_SESSION, true));
error_log("registrar_pago.php - Usuario logueado: " . (esta_logueado() ? "Sí" : "No"));
error_log("registrar_pago.php - Es admin: " . (es_admin() ? "Sí" : "No"));

// Verificar si el usuario está logueado
if (!esta_logueado()) {
    error_log("registrar_pago.php - Usuario no logueado");
    header("Location: index.php?error=1&message=" . urlencode("Debe iniciar sesión para registrar un pago."));
    exit;
}

// Verificar si se ha proporcionado un código de pedido
if (!isset($_GET['pedido']) || empty($_GET['pedido'])) {
    header("Location: mis_compras.php");
    exit;
}

// Obtener el código del pedido
$codigo_pedido = $_GET['pedido'];

// Registrar información de depuración
error_log("registrar_pago.php - Código de pedido: " . $codigo_pedido);

// Obtener el ID del usuario
$usuario_id = $_SESSION['usuario_logueado'];

// Verificar si el pedido pertenece al usuario
$sql = "SELECT COUNT(*) as count FROM pedido WHERE pedido = ? AND usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $codigo_pedido, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // El pedido no pertenece al usuario
    header("Location: mis_compras.php");
    exit;
}

// Verificar si ya existe un pago registrado para este pedido
$sql = "SELECT COUNT(*) as count FROM pagos WHERE pedido_codigo = ? AND usuario_codigo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $codigo_pedido, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    // Ya existe un pago registrado para este pedido
    header("Location: detalle_pedido.php?pedido=" . $codigo_pedido);
    exit;
}

// Obtener información del pedido
$sql = "SELECT SUM(cantidad * precio_dolares) as total_dolares FROM pedido WHERE pedido = ? AND usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $codigo_pedido, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$pedido = $result->fetch_assoc();

$total_dolares = $pedido['total_dolares'];

// Obtener la tasa de cambio actual
$sql = "SELECT valor_tasa FROM tasa ORDER BY id_tasa DESC LIMIT 1";
$result = $conn->query($sql);
$tasa = $result->fetch_assoc()['valor_tasa'];

// Calcular el monto en bolívares en tiempo real
$total_bolivares = $total_dolares * $tasa;

// Obtener los bancos disponibles
$sql_bancos = "SELECT id, nombre FROM bancos WHERE activo = 1 ORDER BY nombre";
$result_bancos = $conn->query($sql_bancos);
$bancos = [];
while ($row = $result_bancos->fetch_assoc()) {
    $bancos[] = $row;
}

// Obtener información de pago
$sql_info_pago = "SELECT * FROM info_pago WHERE activo = 1 ORDER BY tipo, id";
$result_info_pago = $conn->query($sql_info_pago);
$info_pago = [];
while ($row = $result_info_pago->fetch_assoc()) {
    $info_pago[] = $row;
}

// Verificar si ya existen datos de envío para este pedido
$sql_envio = "SELECT * FROM datos_envio WHERE pedido_codigo = ? AND usuario_codigo = ? LIMIT 1";
$stmt_envio = $conn->prepare($sql_envio);
$stmt_envio->bind_param("si", $codigo_pedido, $usuario_id);
$stmt_envio->execute();
$result_envio = $stmt_envio->get_result();
$datos_envio = $result_envio->num_rows > 0 ? $result_envio->fetch_assoc() : null;

// Verificar si el usuario es administrador
$es_admin = ($_SESSION['rol_codigo'] == 1);

// Título de la página
$titulo_pagina = "Registrar Pago";

// Incluir el encabezado
include 'frontend/includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pago - TiendAlex</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@500&display=swap" rel="stylesheet">
    <?php include 'frontend/includes/css_includes.php'; ?>
    <style>
        .registrar-pago-container {
            padding: 30px 0;
        }

        .pedido-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 35px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
            border-left: 5px solid #1976D2;
        }

        .pedido-info::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(33, 150, 243, 0.1) 0%, rgba(33, 150, 243, 0) 70%);
            border-radius: 50%;
        }

        .pedido-codigo {
            font-family: 'Roboto Mono', monospace;
            font-weight: 700;
            color: #0D47A1;
            font-size: 24px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .pedido-codigo::before {
            content: 'receipt';
            font-family: 'Material Icons';
            margin-right: 10px;
            font-size: 28px;
            color: #1976D2;
        }

        .pedido-total {
            font-weight: 700;
            color: #1565C0;
            font-size: 20px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(25, 118, 210, 0.2);
        }

        .tasa-cambio {
            margin-top: 10px;
            font-size: 14px;
            color: #546E7A;
            display: flex;
            align-items: center;
        }

        .tasa-cambio::before {
            content: 'currency_exchange';
            font-family: 'Material Icons';
            margin-right: 8px;
            font-size: 18px;
            color: #546E7A;
        }

        .form-container {
            background-color: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .form-container:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 500;
            color: #1976D2;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .form-section-title::before {
            content: 'payments';
            font-family: 'Material Icons';
            margin-right: 10px;
            font-size: 24px;
        }

        .input-field label {
            color: #1976D2;
            font-weight: 500;
        }

        .input-field input:focus + label,
        .input-field textarea:focus + label {
            color: #1976D2 !important;
        }

        .input-field input:focus,
        .input-field textarea:focus {
            border-bottom: 2px solid #1976D2 !important;
            box-shadow: 0 1px 0 0 #1976D2 !important;
        }

        .input-field input,
        .input-field textarea {
            font-size: 16px;
        }

        [type="radio"]:checked + span:after {
            background-color: #1976D2;
            border: 2px solid #1976D2;
        }

        [type="radio"] + span {
            font-size: 16px;
            padding-left: 30px;
        }

        .btn-submit {
            width: 100%;
            margin-top: 25px;
            height: 48px;
            line-height: 48px;
            font-size: 16px;
            font-weight: 500;
            text-transform: uppercase;
            border-radius: 24px;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            box-shadow: 0 6px 15px rgba(76, 175, 80, 0.4);
            transform: translateY(-2px);
        }

        .btn-volver {
            height: 48px;
            line-height: 48px;
            font-size: 16px;
            font-weight: 500;
            text-transform: uppercase;
            border-radius: 24px;
            box-shadow: 0 4px 10px rgba(33, 150, 243, 0.3);
            transition: all 0.3s ease;
        }

        .btn-volver:hover {
            box-shadow: 0 6px 15px rgba(33, 150, 243, 0.4);
            transform: translateY(-2px);
        }

        .metodo-pago-container {
            margin-bottom: 35px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .metodo-pago-option {
            margin: 0;
            flex: 1 0 auto;
            min-width: 180px;
        }

        .metodo-pago-option label {
            display: block;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .metodo-pago-option label:hover {
            background-color: #e3f2fd;
        }

        .metodo-pago-option [type="radio"]:checked + span {
            color: #1976D2;
            font-weight: 500;
        }

        .metodo-pago-option [type="radio"]:checked ~ label {
            background-color: #e3f2fd;
            border-color: #1976D2;
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.2);
        }

        .campo-opcional {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .campo-opcional.visible {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            top: 15px;
            right: 10px;
            color: #9e9e9e;
            transition: all 0.2s ease;
        }

        .input-icon input:focus ~ i {
            color: #1976D2;
        }

        .input-with-prefix {
            padding-left: 40px !important;
        }

        .input-prefix {
            position: absolute;
            top: 15px;
            left: 10px;
            color: #9e9e9e;
            font-weight: 500;
        }

        .divider-section {
            margin: 30px 0;
            height: 1px;
            background: linear-gradient(to right, rgba(25, 118, 210, 0), rgba(25, 118, 210, 0.2), rgba(25, 118, 210, 0));
        }

        /* Estilos para pestañas */
        .tabs {
            background-color: transparent;
            margin-bottom: 30px;
        }

        .tabs .tab a {
            color: rgba(25, 118, 210, 0.7);
            font-weight: 500;
        }

        .tabs .tab a:hover, .tabs .tab a.active {
            color: #1976D2;
            background-color: rgba(25, 118, 210, 0.05);
        }

        .tabs .tab a:focus, .tabs .tab a:focus.active {
            background-color: rgba(25, 118, 210, 0.1);
        }

        .tabs .indicator {
            background-color: #1976D2;
            height: 3px;
        }

        .tab-content {
            display: none;
            padding: 20px 0;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        /* Estilos para múltiples métodos de pago */
        .metodo-pago-multiple {
            background-color: #f5f5f5;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            border-left: 3px solid #1976D2;
        }

        .metodo-pago-multiple .remove-metodo {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            color: #F44336;
            transition: all 0.2s ease;
        }

        .metodo-pago-multiple .remove-metodo:hover {
            transform: scale(1.2);
        }

        .add-metodo-btn {
            width: 100%;
            margin: 20px 0;
            border-radius: 8px;
            height: 45px;
            line-height: 45px;
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            box-shadow: 0 4px 10px rgba(33, 150, 243, 0.3);
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .add-metodo-btn:hover {
            background: linear-gradient(135deg, #1E88E5 0%, #1565C0 100%);
            box-shadow: 0 6px 15px rgba(33, 150, 243, 0.4);
            transform: translateY(-2px);
        }

        /* Estilos para información de pago */
        .info-pago-container {
            background-color: #e8f5e9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            position: relative;
        }

        .info-pago-item {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border-left: 3px solid #4CAF50;
        }

        .info-pago-item h6 {
            margin-top: 0;
            color: #2E7D32;
            font-weight: 500;
        }

        .info-pago-item p {
            margin: 5px 0;
        }

        .edit-info-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            cursor: pointer;
            color: #1976D2;
            transition: all 0.2s ease;
        }

        .edit-info-btn:hover {
            transform: scale(1.2);
        }

        /* Estilos para el contenedor de monto */
        .monto-container {
            position: relative;
            margin-bottom: 20px;
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border-left: 3px solid #1976D2;
            transition: all 0.3s ease;
        }

        .monto-container:hover {
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            background-color: #bbdefb;
        }

        .monto-value {
            font-size: 18px;
            font-weight: 500;
            color: #1976D2;
            margin: 5px 0;
        }

        #saldo-restante {
            font-weight: 700;
            color: #F44336; /* Cambiado a rojo */
            margin-left: 5px;
            animation: pulsate 1.5s infinite alternate;
        }

        @keyframes pulsate {
            from { opacity: 1; }
            to { opacity: 0.8; }
        }

        /* Estilos para bancos */
        .banco-select {
            margin-bottom: 20px;
        }

        .dropdown-content li > a, .dropdown-content li > span {
            color: #1976D2;
        }

        .select-wrapper input.select-dropdown:focus {
            border-bottom: 1px solid #1976D2;
        }

        /* Estilos para el datepicker */
        .datepicker-date-display {
            background-color: #1976D2;
        }

        .datepicker-table td.is-selected {
            background-color: #1976D2;
            color: #fff;
        }

        .datepicker-table td.is-today {
            color: #1976D2;
        }

        .datepicker-cancel,
        .datepicker-clear,
        .datepicker-today,
        .datepicker-done {
            color: #1976D2;
        }

        .datepicker-calendar-container {
            padding: 10px;
        }

        .datepicker-table td.is-selected.is-today {
            color: #fff;
        }

        .month-prev:focus, .month-next:focus {
            background-color: rgba(25, 118, 210, 0.1);
        }

        /* Estilo para el campo de fecha */
        .fecha-pago {
            cursor: pointer;
            background-color: #fff !important;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="%231976D2" d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 35px !important;
            transition: all 0.3s ease;
        }

        .fecha-pago:hover, .fecha-pago:focus {
            background-color: #f5f5f5 !important;
        }

        /* Estilos para administrador */
        .admin-actions {
            background-color: #ffebee;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 3px solid #F44336;
        }

        .admin-actions h5 {
            color: #D32F2F;
            margin-top: 0;
        }

        .admin-btn {
            margin: 5px;
        }

        /* Estilos para modales */
        .modal {
            border-radius: 12px;
            max-width: 600px;
        }

        .modal .modal-content {
            padding: 24px;
        }

        .modal .modal-footer {
            padding: 0 24px 24px 24px;
            height: auto;
        }

        .modal-title {
            font-weight: 500;
            color: #1976D2;
            margin-top: 0;
        }

        /* Estilos para chips */
        .chip {
            background-color: #e3f2fd;
            color: #1976D2;
            font-weight: 500;
        }

        .chip .close {
            color: #1976D2;
        }
    </style>
</head>
<body>
    <main class="container">
        <div class="row">
            <div class="col s12">
                <h4 class="page-title center-align">Registrar Pago</h4>

                <div class="registrar-pago-container">
                    <div class="pedido-info">
                        <div class="pedido-codigo"><?php echo $codigo_pedido; ?></div>
                        <div class="pedido-total">
                            Total: $<?php echo number_format($total_dolares, 2); ?> / Bs. <?php echo number_format($total_bolivares, 2); ?>
                        </div>
                        <div class="tasa-cambio">
                            Tasa de cambio actual: Bs. <?php echo number_format($tasa, 2); ?> por $1
                        </div>
                        <!-- Información de depuración -->
                        <div style="display: none;">
                            <p>Usuario ID: <?php echo $usuario_id; ?></p>
                            <p>Sesión activa: <?php echo esta_logueado() ? 'Sí' : 'No'; ?></p>
                        </div>
                    </div>

                    <?php if ($es_admin && count($info_pago) > 0): ?>
                    <div class="info-pago-container">
                        <h5>Información de pago para clientes</h5>
                        <i class="material-icons edit-info-btn" id="edit-info-btn">edit</i>

                        <?php foreach ($info_pago as $info): ?>
                            <div class="info-pago-item">
                                <h6>
                                    <?php
                                    switch ($info['tipo']) {
                                        case 'banco':
                                            echo '<i class="material-icons left">account_balance</i> Transferencia Bancaria';
                                            break;
                                        case 'pago_movil':
                                            echo '<i class="material-icons left">phone_android</i> Pago Móvil';
                                            break;
                                        case 'efectivo':
                                            echo '<i class="material-icons left">local_atm</i> Efectivo';
                                            break;
                                        default:
                                            echo '<i class="material-icons left">payment</i> Otro método';
                                    }
                                    ?>
                                </h6>

                                <?php if (!empty($info['banco_id'])):
                                    // Obtener el nombre del banco
                                    $banco_id = $info['banco_id'];
                                    $banco_nombre = '';
                                    foreach ($bancos as $banco) {
                                        if ($banco['id'] == $banco_id) {
                                            $banco_nombre = $banco['nombre'];
                                            break;
                                        }
                                    }
                                ?>
                                    <p><strong>Banco:</strong> <?php echo $banco_nombre; ?></p>
                                <?php endif; ?>

                                <?php if (!empty($info['numero_cuenta'])): ?>
                                    <p><strong>Cuenta:</strong> <?php echo $info['numero_cuenta']; ?></p>
                                <?php endif; ?>

                                <?php if (!empty($info['titular'])): ?>
                                    <p><strong>Titular:</strong> <?php echo $info['titular']; ?></p>
                                <?php endif; ?>

                                <?php if (!empty($info['cedula_rif'])): ?>
                                    <p><strong>C.I./RIF:</strong> <?php echo $info['cedula_rif']; ?></p>
                                <?php endif; ?>

                                <?php if (!empty($info['telefono'])): ?>
                                    <p><strong>Teléfono:</strong> <?php echo $info['telefono']; ?></p>
                                <?php endif; ?>

                                <?php if (!empty($info['descripcion'])): ?>
                                    <p><?php echo $info['descripcion']; ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="form-container">
                        <form action="/tiendalex2/backend/procesar_pago.php" method="POST" id="form-pago">
                            <input type="hidden" name="pedido_codigo" value="<?php echo $codigo_pedido; ?>">
                            <input type="hidden" name="usuario_codigo" value="<?php echo $usuario_id; ?>">
                            <!-- Agregar información de sesión para depuración -->
                            <input type="hidden" name="session_debug" value="1">

                            <!-- Pestañas de navegación -->
                            <div class="row">
                                <div class="col s12">
                                    <ul class="tabs">
                                        <li class="tab col s4"><a href="#tab-pago" class="active" data-tab="tab-pago">Información de Pago</a></li>
                                        <li class="tab col s4"><a href="#tab-envio" data-tab="tab-envio">Datos de Envío</a></li>
                                        <li class="tab col s4"><a href="#tab-resumen" data-tab="tab-resumen">Resumen</a></li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Pestaña de Información de Pago -->
                            <div id="tab-pago" class="tab-content active">
                                <?php if (!$es_admin && count($info_pago) > 0): ?>
                                <div class="info-pago-container">
                                    <h5>Información de pago</h5>

                                    <?php foreach ($info_pago as $info): ?>
                                        <div class="info-pago-item">
                                            <h6>
                                                <?php
                                                switch ($info['tipo']) {
                                                    case 'banco':
                                                        echo '<i class="material-icons left">account_balance</i> Transferencia Bancaria';
                                                        break;
                                                    case 'pago_movil':
                                                        echo '<i class="material-icons left">phone_android</i> Pago Móvil';
                                                        break;
                                                    case 'efectivo':
                                                        echo '<i class="material-icons left">local_atm</i> Efectivo';
                                                        break;
                                                    default:
                                                        echo '<i class="material-icons left">payment</i> Otro método';
                                                }
                                                ?>
                                            </h6>

                                            <?php if (!empty($info['banco_id'])):
                                                // Obtener el nombre del banco
                                                $banco_id = $info['banco_id'];
                                                $banco_nombre = '';
                                                foreach ($bancos as $banco) {
                                                    if ($banco['id'] == $banco_id) {
                                                        $banco_nombre = $banco['nombre'];
                                                        break;
                                                    }
                                                }
                                            ?>
                                                <p><strong>Banco:</strong> <?php echo $banco_nombre; ?></p>
                                            <?php endif; ?>

                                            <?php if (!empty($info['numero_cuenta'])): ?>
                                                <p><strong>Cuenta:</strong> <?php echo $info['numero_cuenta']; ?></p>
                                            <?php endif; ?>

                                            <?php if (!empty($info['titular'])): ?>
                                                <p><strong>Titular:</strong> <?php echo $info['titular']; ?></p>
                                            <?php endif; ?>

                                            <?php if (!empty($info['cedula_rif'])): ?>
                                                <p><strong>C.I./RIF:</strong> <?php echo $info['cedula_rif']; ?></p>
                                            <?php endif; ?>

                                            <?php if (!empty($info['telefono'])): ?>
                                                <p><strong>Teléfono:</strong> <?php echo $info['telefono']; ?></p>
                                            <?php endif; ?>

                                            <?php if (!empty($info['descripcion'])): ?>
                                                <p><?php echo $info['descripcion']; ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <div class="form-section-title">Métodos de pago</div>

                                <div id="metodos-pago-container">
                                    <!-- Contenedor para métodos de pago múltiples -->
                                    <div class="metodo-pago-multiple" id="metodo-pago-1">
                                        <i class="material-icons remove-metodo" style="display: none;">close</i>

                                        <div class="row">
                                            <div class="col s12">
                                                <div class="metodo-pago-container">
                                                    <div class="metodo-pago-option">
                                                        <label>
                                                            <input name="metodo_pago_1" type="radio" value="transferencia" checked />
                                                            <span>Transferencia bancaria</span>
                                                        </label>
                                                    </div>
                                                    <div class="metodo-pago-option">
                                                        <label>
                                                            <input name="metodo_pago_1" type="radio" value="pago_movil" />
                                                            <span>Pago móvil</span>
                                                        </label>
                                                    </div>
                                                    <div class="metodo-pago-option">
                                                        <label>
                                                            <input name="metodo_pago_1" type="radio" value="efectivo" />
                                                            <span>Efectivo</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col s12">
                                                <div class="monto-container">
                                                    <div class="monto-value">Monto a pagar: Bs. <span id="monto-value-1"><?php echo number_format($total_bolivares, 2); ?></span></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="input-field col s12 m6 campo-banco-1 input-icon">
                                                <select id="banco_1" name="banco_1" class="validate banco-select">
                                                    <option value="" disabled selected>Seleccione un banco</option>
                                                    <?php foreach ($bancos as $banco): ?>
                                                        <option value="<?php echo $banco['nombre']; ?>"><?php echo $banco['nombre']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label>Banco</label>
                                            </div>

                                            <div class="input-field col s12 m6 campo-referencia-1 input-icon">
                                                <input id="referencia_1" name="referencia_1" type="text" class="validate">
                                                <label for="referencia_1">Número de referencia</label>
                                                <i class="material-icons">confirmation_number</i>
                                            </div>

                                            <div class="input-field col s12 m6 input-icon">
                                                <div class="input-prefix">Bs.</div>
                                                <input id="monto_1" name="monto_1" type="number" step="0.01" min="0" class="validate input-with-prefix monto-input" required>
                                                <label for="monto_1">Monto</label>
                                                <i class="material-icons">attach_money</i>
                                            </div>

                                            <div class="input-field col s12 m6 input-icon">
                                                <input id="fecha_pago_1" name="fecha_pago_1" type="text" class="datepicker validate fecha-pago" required>
                                                <label for="fecha_pago_1">Fecha de pago</label>
                                                <i class="material-icons">event</i>
                                            </div>

                                            <div class="input-field col s12 m6 campo-telefono-1 input-icon">
                                                <input id="telefono_1" name="telefono_1" type="text" class="validate">
                                                <label for="telefono_1">Teléfono de contacto</label>
                                                <i class="material-icons">phone</i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col s12">
                                        <button type="button" id="add-metodo-btn" class="btn waves-effect waves-light add-metodo-btn">
                                            <i class="material-icons left">add_circle</i>AGREGAR OTRO MÉTODO DE PAGO <span id="saldo-restante"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="input-field col s12 input-icon">
                                    <textarea id="comentarios" name="comentarios" class="materialize-textarea"></textarea>
                                    <label for="comentarios">Comentarios adicionales</label>
                                    <i class="material-icons">comment</i>
                                </div>

                                <div class="row">
                                    <div class="col s12 center-align">
                                        <button type="button" class="btn waves-effect waves-light blue next-tab" data-next="tab-envio">
                                            <i class="material-icons right">arrow_forward</i>Continuar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Pestaña de Datos de Envío -->
                            <div id="tab-envio" class="tab-content">
                                <div class="form-section-title">Datos de envío</div>

                                <div class="row">
                                    <div class="input-field col s12 input-icon">
                                        <textarea id="direccion" name="direccion" class="materialize-textarea validate" required><?php echo $datos_envio ? $datos_envio['direccion'] : ''; ?></textarea>
                                        <label for="direccion">Dirección de entrega</label>
                                        <i class="material-icons">location_on</i>
                                    </div>

                                    <div class="input-field col s12 m6 input-icon">
                                        <input id="empresa_envio" name="empresa_envio" type="text" value="<?php echo $datos_envio ? $datos_envio['empresa_envio'] : ''; ?>">
                                        <label for="empresa_envio">Empresa de envío (opcional)</label>
                                        <i class="material-icons">local_shipping</i>
                                    </div>

                                    <div class="input-field col s12 m6 input-icon">
                                        <input id="destinatario_nombre" name="destinatario_nombre" type="text" class="validate" required value="<?php echo $datos_envio ? $datos_envio['destinatario_nombre'] : ''; ?>">
                                        <label for="destinatario_nombre">Nombre y apellido del destinatario</label>
                                        <i class="material-icons">person</i>
                                    </div>

                                    <div class="input-field col s12 m6 input-icon">
                                        <input id="destinatario_telefono" name="destinatario_telefono" type="text" class="validate" required value="<?php echo $datos_envio ? $datos_envio['destinatario_telefono'] : ''; ?>">
                                        <label for="destinatario_telefono">Teléfono del destinatario</label>
                                        <i class="material-icons">phone</i>
                                    </div>

                                    <div class="input-field col s12 m6 input-icon">
                                        <input id="destinatario_cedula" name="destinatario_cedula" type="text" class="validate" required value="<?php echo $datos_envio ? $datos_envio['destinatario_cedula'] : ''; ?>">
                                        <label for="destinatario_cedula">Cédula de identidad del destinatario</label>
                                        <i class="material-icons">badge</i>
                                    </div>

                                    <div class="input-field col s12 input-icon">
                                        <textarea id="instrucciones_adicionales" name="instrucciones_adicionales" class="materialize-textarea"><?php echo $datos_envio ? $datos_envio['instrucciones_adicionales'] : ''; ?></textarea>
                                        <label for="instrucciones_adicionales">Instrucciones adicionales para la entrega</label>
                                        <i class="material-icons">info</i>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col s6 center-align">
                                        <button type="button" class="btn waves-effect waves-light blue prev-tab" data-prev="tab-pago">
                                            <i class="material-icons left">arrow_back</i>Anterior
                                        </button>
                                    </div>
                                    <div class="col s6 center-align">
                                        <button type="button" class="btn waves-effect waves-light blue next-tab" data-next="tab-resumen">
                                            <i class="material-icons right">arrow_forward</i>Continuar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Pestaña de Resumen -->
                            <div id="tab-resumen" class="tab-content">
                                <div class="form-section-title">Resumen de la información</div>

                                <div class="row">
                                    <div class="col s12">
                                        <div class="card-panel">
                                            <h5>Información de pago</h5>
                                            <div id="resumen-pagos">
                                                <!-- Se llenará dinámicamente con JavaScript -->
                                            </div>

                                            <h5>Datos de envío</h5>
                                            <div id="resumen-envio">
                                                <!-- Se llenará dinámicamente con JavaScript -->
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col s6 center-align">
                                        <button type="button" class="btn waves-effect waves-light blue prev-tab" data-prev="tab-envio">
                                            <i class="material-icons left">arrow_back</i>Anterior
                                        </button>
                                    </div>
                                    <div class="col s6 center-align">
                                        <button type="submit" class="btn waves-effect waves-light green btn-submit">
                                            <i class="material-icons left">payment</i>Registrar Pago
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal para editar información de pago (solo administrador) -->
                <?php if ($es_admin): ?>
                <div id="modal-edit-info" class="modal">
                    <div class="modal-content">
                        <h4 class="modal-title">Editar información de pago</h4>
                        <p>Modifique la información de pago que verán los clientes.</p>

                        <div id="admin-info-container">
                            <!-- Se llenará dinámicamente con JavaScript -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#!" class="modal-close waves-effect waves-light btn-flat">Cancelar</a>
                        <a href="#!" id="save-info-btn" class="waves-effect waves-light btn blue">Guardar</a>
                    </div>
                </div>

                <!-- Modal para editar bancos (solo administrador) -->
                <div id="modal-edit-bancos" class="modal">
                    <div class="modal-content">
                        <h4 class="modal-title">Administrar bancos</h4>
                        <p>Agregue, edite o elimine los bancos disponibles.</p>

                        <div id="admin-bancos-container">
                            <!-- Se llenará dinámicamente con JavaScript -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#!" class="modal-close waves-effect waves-light btn-flat">Cancelar</a>
                        <a href="#!" id="save-bancos-btn" class="waves-effect waves-light btn blue">Guardar</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal de confirmación de pago exitoso -->
    <div id="modal-pago-exitoso" class="modal">
        <div class="modal-content">
            <div class="center-align">
                <i class="material-icons large green-text">check_circle</i>
                <h4>¡Pago registrado con éxito!</h4>
                <p>Tu pago ha sido registrado correctamente. Hemos enviado un correo electrónico con los detalles de tu pedido.</p>

                <div class="divider"></div>

                <div class="resumen-pago-container">
                    <h5>Resumen del pago</h5>
                    <div id="modal-resumen-pago">
                        <!-- Se llenará dinámicamente con JavaScript -->
                    </div>

                    <h5>Datos de envío</h5>
                    <div id="modal-resumen-envio">
                        <!-- Se llenará dinámicamente con JavaScript -->
                    </div>

                    <div class="pedido-codigo">
                        <h5>Código de pedido</h5>
                        <div class="codigo-container">
                            <span id="modal-codigo-pedido"><?php echo $codigo_pedido; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="mis_compras.php" class="waves-effect waves-light btn green">Ver mis compras</a>
            <a href="index.php" class="waves-effect waves-light btn blue">Volver al inicio</a>
        </div>
    </div>

    <?php include 'frontend/includes/footer.php'; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Materialize JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <!-- JavaScript personalizado -->
    <script src="js/scripts.js"></script>
    <?php include 'backend/script/script_enlaces.php'; ?>

    <script>
        $(document).ready(function() {
            // Inicializar componentes de Materialize
            M.updateTextFields();
            $('select').formSelect();

            // Inicializar las pestañas de Materialize con opciones
            $('.tabs').tabs({
                onShow: function(tab) {
                    // Cuando se muestra una pestaña, actualizar el contenido correspondiente
                    var tabId = $(tab).attr('id');
                    $('.tab-content').removeClass('active');
                    $('#' + tabId).addClass('active');

                    // Si es la pestaña de resumen, actualizar el resumen
                    if (tabId === 'tab-resumen') {
                        actualizarResumen();
                    }

                    // Desplazarse al inicio del formulario
                    $('html, body').animate({
                        scrollTop: $('.form-container').offset().top - 20
                    }, 500);
                }
            });

            // Animación de entrada para elementos del formulario
            $('.pedido-info').addClass('animated fadeInDown');
            $('.form-container').addClass('animated fadeInUp');

            // Variables globales
            var totalBolivares = <?php echo $total_bolivares; ?>;
            var metodoCount = 1;
            var montosPagados = {};
            montosPagados[1] = 0; // Inicializar el primer método de pago

            // Configurar e inicializar el datepicker
            var today = new Date();

            // Configuración del datepicker en español
            var datepickerOptions = {
                format: 'yyyy-mm-dd',
                defaultDate: today,
                setDefaultDate: true,
                maxDate: today, // No permitir fechas futuras
                firstDay: 1, // Lunes como primer día de la semana
                autoClose: true,
                showClearBtn: false,
                i18n: {
                    months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                    monthsShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                    weekdays: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
                    weekdaysShort: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
                    weekdaysAbbrev: ['D', 'L', 'M', 'M', 'J', 'V', 'S'],
                    cancel: 'Cancelar',
                    clear: 'Limpiar',
                    done: 'Aceptar'
                }
            };

            // Inicializar el datepicker
            $('.datepicker').datepicker(datepickerOptions);

            // Función para inicializar datepickers en nuevos métodos de pago
            function initDatepicker(element) {
                element.datepicker(datepickerOptions);
            }

            // Manejar cambios en el método de pago
            $(document).on('change', 'input[type="radio"]', function() {
                var metodo = $(this).val();
                var metodoId = $(this).attr('name').split('_')[2];

                // Ocultar todos los campos opcionales primero
                $('.campo-banco-' + metodoId + ', .campo-referencia-' + metodoId + ', .campo-telefono-' + metodoId).slideUp(200);

                // Mostrar los campos correspondientes según el método seleccionado
                setTimeout(function() {
                    if (metodo !== 'efectivo') {
                        $('.campo-banco-' + metodoId + ', .campo-referencia-' + metodoId).slideDown(300);
                    }

                    if (metodo === 'pago_movil') {
                        $('.campo-telefono-' + metodoId).slideDown(300);
                    }
                }, 200);

                // Efecto visual para la opción seleccionada
                $(this).closest('.metodo-pago-container').find('label').removeClass('selected');
                $(this).closest('label').addClass('selected');

                // Mostrar mensaje de ayuda según el método
                var mensajeAyuda = '';
                switch(metodo) {
                    case 'transferencia':
                        mensajeAyuda = '<i class="material-icons left">info</i> Ingresa los datos de tu transferencia bancaria.';
                        break;
                    case 'pago_movil':
                        mensajeAyuda = '<i class="material-icons left">info</i> Ingresa los datos de tu pago móvil, incluyendo tu número de teléfono.';
                        break;
                    case 'efectivo':
                        mensajeAyuda = '<i class="material-icons left">info</i> Selecciona esta opción si pagarás en efectivo al momento de la entrega.';
                        break;
                }

                M.toast({html: mensajeAyuda, classes: 'blue', displayLength: 3000});
            });

            // Disparar el evento change para establecer el estado inicial
            $('input[name="metodo_pago_1"]:checked').change();

            // Efectos visuales para los campos de entrada
            $('.input-field input, .input-field textarea').focus(function() {
                $(this).closest('.input-icon').find('i').addClass('active');
            }).blur(function() {
                $(this).closest('.input-icon').find('i').removeClass('active');
            });

            // Función para actualizar el resumen del pago
            function actualizarResumen() {
                var resumenPagos = '';
                var totalPagado = 0;

                // Generar el resumen de pagos
                $('.metodo-pago-multiple').each(function() {
                    var metodoId = $(this).attr('id').split('-')[2];
                    var metodo = $('input[name="metodo_pago_' + metodoId + '"]:checked').val();
                    var metodoTexto = '';

                    switch(metodo) {
                        case 'transferencia':
                            metodoTexto = 'Transferencia bancaria';
                            break;
                        case 'pago_movil':
                            metodoTexto = 'Pago móvil';
                            break;
                        case 'efectivo':
                            metodoTexto = 'Efectivo';
                            break;
                        default:
                            metodoTexto = 'Otro método';
                    }

                    var monto = parseFloat($('#monto_' + metodoId).val()) || 0;
                    var fecha = $('#fecha_pago_' + metodoId).val();
                    var banco = $('#banco_' + metodoId).val() || 'N/A';
                    var referencia = $('#referencia_' + metodoId).val() || 'N/A';
                    var telefono = $('#telefono_' + metodoId).val() || 'N/A';

                    totalPagado += monto;

                    resumenPagos += '<div class="resumen-item">';
                    resumenPagos += '<p><strong>Método:</strong> ' + metodoTexto + '</p>';
                    resumenPagos += '<p><strong>Monto:</strong> Bs. ' + monto.toFixed(2) + '</p>';
                    resumenPagos += '<p><strong>Fecha:</strong> ' + fecha + '</p>';

                    if (metodo !== 'efectivo') {
                        resumenPagos += '<p><strong>Banco:</strong> ' + banco + '</p>';
                        resumenPagos += '<p><strong>Referencia:</strong> ' + referencia + '</p>';
                    }

                    if (metodo === 'pago_movil') {
                        resumenPagos += '<p><strong>Teléfono:</strong> ' + telefono + '</p>';
                    }

                    resumenPagos += '</div>';
                });

                // Generar el resumen de envío
                var resumenEnvio = '<div class="resumen-item">';
                resumenEnvio += '<p><strong>Dirección:</strong> ' + $('#direccion').val() + '</p>';

                if ($('#empresa_envio').val()) {
                    resumenEnvio += '<p><strong>Empresa de envío:</strong> ' + $('#empresa_envio').val() + '</p>';
                }

                resumenEnvio += '<p><strong>Destinatario:</strong> ' + $('#destinatario_nombre').val() + '</p>';
                resumenEnvio += '<p><strong>Teléfono:</strong> ' + $('#destinatario_telefono').val() + '</p>';
                resumenEnvio += '<p><strong>Cédula:</strong> ' + $('#destinatario_cedula').val() + '</p>';

                if ($('#instrucciones_adicionales').val()) {
                    resumenEnvio += '<p><strong>Instrucciones adicionales:</strong> ' + $('#instrucciones_adicionales').val() + '</p>';
                }

                resumenEnvio += '</div>';

                // Actualizar el contenido del resumen
                $('#resumen-pagos').html(resumenPagos);
                $('#resumen-envio').html(resumenEnvio);

                // También actualizar el modal de confirmación
                $('#modal-resumen-pago').html(resumenPagos);
                $('#modal-resumen-envio').html(resumenEnvio);
            }

            // Validar el formulario antes de enviar
            $('#form-pago').submit(function(e) {
                e.preventDefault(); // Prevenir el envío normal del formulario

                var isValid = true;
                var totalPagado = 0;

                // Resetear todos los campos a estado normal
                $('.input-field').removeClass('error');

                // Validar cada método de pago
                $('.metodo-pago-multiple').each(function() {
                    var metodoId = $(this).attr('id').split('-')[2];
                    var metodo = $('input[name="metodo_pago_' + metodoId + '"]:checked').val();
                    var monto = $('#monto_' + metodoId).val();
                    var fecha = $('#fecha_pago_' + metodoId).val();

                    totalPagado += parseFloat(monto) || 0;

                    if (!monto || monto <= 0) {
                        $('#monto_' + metodoId).closest('.input-field').addClass('error');
                        M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa un monto válido en todos los métodos de pago.', classes: 'red', displayLength: 3000});
                        isValid = false;
                    }

                    if (!fecha) {
                        $('#fecha_pago_' + metodoId).closest('.input-field').addClass('error');
                        M.toast({html: '<i class="material-icons left">error</i> Por favor, selecciona una fecha de pago en todos los métodos.', classes: 'red', displayLength: 3000});
                        isValid = false;
                    }

                    if (metodo !== 'efectivo') {
                        var banco = $('#banco_' + metodoId).val();
                        var referencia = $('#referencia_' + metodoId).val();

                        if (!banco) {
                            $('#banco_' + metodoId).closest('.input-field').addClass('error');
                            M.toast({html: '<i class="material-icons left">error</i> Por favor, selecciona un banco en todos los métodos necesarios.', classes: 'red', displayLength: 3000});
                            isValid = false;
                        }

                        if (!referencia) {
                            $('#referencia_' + metodoId).closest('.input-field').addClass('error');
                            M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa un número de referencia en todos los métodos necesarios.', classes: 'red', displayLength: 3000});
                            isValid = false;
                        }
                    }

                    if (metodo === 'pago_movil') {
                        var telefono = $('#telefono_' + metodoId).val();

                        if (!telefono) {
                            $('#telefono_' + metodoId).closest('.input-field').addClass('error');
                            M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa un número de teléfono para los pagos móviles.', classes: 'red', displayLength: 3000});
                            isValid = false;
                        }
                    }
                });

                // Verificar que el total pagado sea igual al total del pedido
                if (Math.abs(totalPagado - totalBolivares) > 0.01) {
                    M.toast({html: '<i class="material-icons left">error</i> El monto total pagado debe ser igual al total del pedido.', classes: 'red', displayLength: 3000});
                    isValid = false;
                }

                if (!isValid) {
                    return false;
                }

                // Mostrar animación de carga
                $('.btn-submit').html('<i class="material-icons left">hourglass_empty</i> Procesando...').addClass('disabled');

                // Actualizar el resumen para el modal
                actualizarResumen();

                // Enviar el formulario mediante AJAX
                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    dataType: 'json',
                    xhrFields: {
                        withCredentials: true
                    },
                    success: function(response) {
                        if (response.success) {
                            // Mostrar mensaje de éxito
                            M.toast({html: '<i class="material-icons left">check_circle</i> ' + response.message, classes: 'green', displayLength: 5000});

                            // Actualizar el icono de pedidos en el encabezado
                            actualizarIconoPedidos();

                            // Mostrar el modal de confirmación
                            var modalInstance = M.Modal.getInstance($('#modal-pago-exitoso'));
                            if (modalInstance) {
                                modalInstance.open();
                            } else {
                                var modalOptions = {
                                    dismissible: false,
                                    opacity: 0.9,
                                    inDuration: 300,
                                    outDuration: 200
                                };
                                var modalElem = document.getElementById('modal-pago-exitoso');
                                var modalInstance = M.Modal.init(modalElem, modalOptions);
                                modalInstance.open();
                            }
                        } else {
                            // Mostrar mensaje de error
                            M.toast({html: '<i class="material-icons left">error</i> ' + response.message, classes: 'red', displayLength: 5000});
                            $('.btn-submit').html('<i class="material-icons left">payment</i> Registrar Pago').removeClass('disabled');

                            // Si el error es de sesión, recargar la página después de un breve retraso
                            if (response.message.includes("iniciar sesión")) {
                                setTimeout(function() {
                                    window.location.reload();
                                }, 3000);
                            }
                        }
                    },
                    error: function() {
                        // Mostrar mensaje de error genérico
                        M.toast({html: '<i class="material-icons left">error</i> Ha ocurrido un error al procesar tu pago. Por favor, intenta nuevamente.', classes: 'red', displayLength: 5000});
                        $('.btn-submit').html('<i class="material-icons left">payment</i> Registrar Pago').removeClass('disabled');
                    }
                });

                return false;
            });

            // Añadir efecto de onda (ripple) a los botones
            $('.btn').on('click', function(e) {
                var ripple = $('<span class="ripple"></span>');
                var x = e.pageX - $(this).offset().left;
                var y = e.pageY - $(this).offset().top;

                ripple.css({
                    top: y + 'px',
                    left: x + 'px'
                });

                $(this).append(ripple);

                setTimeout(function() {
                    ripple.remove();
                }, 600);
            });

            // Función para actualizar el icono de pedidos en el encabezado
            function actualizarIconoPedidos() {
                // Versión escritorio
                if ($('#pedidos-icon-container').length > 0) {
                    // Mostrar el contenedor si está oculto
                    $('#pedidos-icon-container').show();

                    // Verificar si ya existe el badge
                    if ($('.pedidos-badge').length === 0) {
                        // Crear el badge
                        $('<span class="badge new amber darken-2 pedidos-badge" data-badge-caption="">1</span>').appendTo('#pedidos-link');
                    } else {
                        // Actualizar el contador de pedidos
                        var contador = parseInt($('.pedidos-badge').text()) || 0;
                        $('.pedidos-badge').text(contador + 1);
                    }

                    // Añadir animación al icono
                    $('#pedidos-icon').addClass('animated pulse');
                    $('.pedidos-badge').addClass('animated pulse');
                }

                // Versión móvil
                if ($('#pedidos-icon-container-mobile').length > 0) {
                    // Mostrar el contenedor si está oculto
                    $('#pedidos-icon-container-mobile').show();

                    // Verificar si ya existe el badge
                    if ($('.pedidos-badge-mobile').length === 0) {
                        // Crear el badge
                        $('<span class="badge new amber darken-2 pedidos-badge-mobile" data-badge-caption="">1</span>').appendTo('#pedidos-link-mobile');
                    } else {
                        // Actualizar el contador de pedidos
                        var contador = parseInt($('.pedidos-badge-mobile').text()) || 0;
                        $('.pedidos-badge-mobile').text(contador + 1);
                    }

                    // Añadir animación al icono
                    $('#pedidos-icon-mobile').addClass('animated pulse');
                    $('.pedidos-badge-mobile').addClass('animated pulse');
                }
            }

            // Añadir clase de error para campos inválidos
            $('<style>.input-field.error input, .input-field.error textarea { border-bottom: 2px solid #F44336 !important; box-shadow: 0 1px 0 0 #F44336 !important; } .input-field.error label { color: #F44336 !important; } .input-field.error .material-icons { color: #F44336 !important; }</style>').appendTo('head');

            // Añadir animaciones
            $('<style>@keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } } @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .animated { animation-duration: 0.5s; animation-fill-mode: both; } .fadeInDown { animation-name: fadeInDown; } .fadeInUp { animation-name: fadeInUp; } .input-icon i.active { color: #1976D2 !important; transform: scale(1.2); }</style>').appendTo('head');

            // Añadir efecto ripple para botones
            $('<style>.btn { position: relative; overflow: hidden; } .ripple { position: absolute; border-radius: 50%; background-color: rgba(255, 255, 255, 0.7); transform: scale(0); animation: ripple 0.6s linear; pointer-events: none; } @keyframes ripple { to { transform: scale(2.5); opacity: 0; } }</style>').appendTo('head');

            // Estilos para el modal de confirmación
            $('<style>.modal { border-radius: 8px; } .modal .modal-content { padding: 24px; } .modal .modal-footer { padding: 16px 24px; } .resumen-pago-container { margin-top: 20px; } .resumen-item { background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 15px; } .resumen-item p { margin: 5px 0; } .codigo-container { background-color: #e3f2fd; padding: 10px; border-radius: 5px; font-size: 24px; font-weight: bold; color: #1976D2; margin: 10px 0; display: inline-block; } .pedidos-badge { position: absolute; top: 0; right: 0; background-color: #F44336; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; line-height: 20px; text-align: center; } .pedidos-link { position: relative; } @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.2); } 100% { transform: scale(1); } } .pulse { animation: pulse 1s infinite; }</style>').appendTo('head');

            // Función para calcular el saldo restante
            function calcularSaldoRestante() {
                var totalPagado = 0;

                // Sumar todos los montos ingresados
                $('.monto-input').each(function() {
                    var monto = parseFloat($(this).val()) || 0;
                    var id = $(this).attr('id').split('_')[1];
                    montosPagados[id] = monto;
                    totalPagado += monto;
                });

                var saldoRestante = totalBolivares - totalPagado;

                // Actualizar el texto del botón de agregar método
                if (saldoRestante > 0) {
                    $('#saldo-restante').html('(SALDO RESTANTE: Bs. ' + saldoRestante.toFixed(2) + ')');
                    $('#add-metodo-btn').show();
                } else {
                    $('#saldo-restante').html('');
                    $('#add-metodo-btn').hide();
                }

                return saldoRestante;
            }

            // Manejar cambios en los montos
            $(document).on('input', '.monto-input', function() {
                calcularSaldoRestante();
            });

            // Agregar otro método de pago
            $('#add-metodo-btn').click(function() {
                var saldoRestante = calcularSaldoRestante();

                if (saldoRestante <= 0) {
                    M.toast({html: '<i class="material-icons left">error</i> No hay saldo restante para agregar otro método de pago.', classes: 'red', displayLength: 3000});
                    return;
                }

                metodoCount++;
                montosPagados[metodoCount] = 0;

                // Clonar el primer método de pago y actualizar IDs
                var nuevoMetodo = $('#metodo-pago-1').clone();
                nuevoMetodo.attr('id', 'metodo-pago-' + metodoCount);

                // Actualizar los IDs y nombres de los elementos dentro del nuevo método
                nuevoMetodo.find('input[type="radio"]').attr('name', 'metodo_pago_' + metodoCount);
                nuevoMetodo.find('#banco_1').attr('id', 'banco_' + metodoCount).attr('name', 'banco_' + metodoCount);
                nuevoMetodo.find('label[for="banco_1"]').attr('for', 'banco_' + metodoCount);
                nuevoMetodo.find('#referencia_1').attr('id', 'referencia_' + metodoCount).attr('name', 'referencia_' + metodoCount);
                nuevoMetodo.find('label[for="referencia_1"]').attr('for', 'referencia_' + metodoCount);
                nuevoMetodo.find('#monto_1').attr('id', 'monto_' + metodoCount).attr('name', 'monto_' + metodoCount).val(saldoRestante.toFixed(2));
                nuevoMetodo.find('label[for="monto_1"]').attr('for', 'monto_' + metodoCount);
                nuevoMetodo.find('#fecha_pago_1').attr('id', 'fecha_pago_' + metodoCount).attr('name', 'fecha_pago_' + metodoCount);
                nuevoMetodo.find('label[for="fecha_pago_1"]').attr('for', 'fecha_pago_' + metodoCount);
                nuevoMetodo.find('#telefono_1').attr('id', 'telefono_' + metodoCount).attr('name', 'telefono_' + metodoCount);
                nuevoMetodo.find('label[for="telefono_1"]').attr('for', 'telefono_' + metodoCount);
                nuevoMetodo.find('#monto-value-1').attr('id', 'monto-value-' + metodoCount).text(saldoRestante.toFixed(2));

                // Mostrar el botón de eliminar
                nuevoMetodo.find('.remove-metodo').show();

                // Añadir el nuevo método al contenedor
                $('#metodos-pago-container').append(nuevoMetodo);

                // Reinicializar los componentes de Materialize
                M.updateTextFields();
                nuevoMetodo.find('select').formSelect();

                // Inicializar el datepicker en el nuevo método
                initDatepicker(nuevoMetodo.find('.datepicker'));

                // Actualizar el contador de métodos
                $('<input>').attr({
                    type: 'hidden',
                    name: 'metodo_count',
                    value: metodoCount
                }).appendTo('#form-pago');

                M.toast({html: '<i class="material-icons left">add_circle</i> Método de pago adicional agregado.', classes: 'blue', displayLength: 3000});
            });

            // Eliminar un método de pago
            $(document).on('click', '.remove-metodo', function() {
                var metodoId = $(this).closest('.metodo-pago-multiple').attr('id').split('-')[2];

                // Eliminar el método de pago
                $(this).closest('.metodo-pago-multiple').remove();

                // Eliminar el monto de este método del total
                delete montosPagados[metodoId];

                // Recalcular el saldo restante
                calcularSaldoRestante();

                M.toast({html: '<i class="material-icons left">remove_circle</i> Método de pago eliminado.', classes: 'blue', displayLength: 3000});
            });

            // Inicializar el contador de métodos
            $('<input>').attr({
                type: 'hidden',
                name: 'metodo_count',
                value: metodoCount
            }).appendTo('#form-pago');

            // Calcular saldo restante inicial
            calcularSaldoRestante();

            // Manejador adicional para los botones de continuar/anterior en la interfaz alternativa
            $(document).on('click', '.btn[id="CONTINUAR"], .btn[id="ANTERIOR"]', function(e) {
                e.preventDefault();

                // Determinar la dirección (siguiente o anterior)
                var isNext = $(this).attr('id') === 'CONTINUAR';

                // Obtener la pestaña actual
                var currentTabId = $('.tab-content.active').attr('id');
                var nextTabId;

                // Determinar la siguiente pestaña
                if (isNext) {
                    if (currentTabId === 'tab-pago') nextTabId = 'tab-envio';
                    else if (currentTabId === 'tab-envio') nextTabId = 'tab-resumen';
                } else {
                    if (currentTabId === 'tab-envio') nextTabId = 'tab-pago';
                    else if (currentTabId === 'tab-resumen') nextTabId = 'tab-envio';
                }

                // Si estamos avanzando, validar los campos
                if (isNext) {
                    var isValid = true;

                    if (currentTabId === 'tab-pago') {
                        // Validación para la pestaña de pago
                        var totalPagado = 0;

                        // Resetear todos los campos a estado normal
                        $('.input-field').removeClass('error');

                        // Validar cada método de pago
                        $('.metodo-pago-multiple').each(function() {
                            var metodoId = $(this).attr('id').split('-')[2];
                            var metodo = $('input[name="metodo_pago_' + metodoId + '"]:checked').val();
                            var monto = $('#monto_' + metodoId).val();
                            var fecha = $('#fecha_pago_' + metodoId).val();

                            totalPagado += parseFloat(monto) || 0;

                            if (!monto || monto <= 0) {
                                $('#monto_' + metodoId).closest('.input-field').addClass('error');
                                M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa un monto válido en todos los métodos de pago.', classes: 'red', displayLength: 3000});
                                isValid = false;
                            }

                            if (!fecha) {
                                $('#fecha_pago_' + metodoId).closest('.input-field').addClass('error');
                                M.toast({html: '<i class="material-icons left">error</i> Por favor, selecciona una fecha de pago en todos los métodos.', classes: 'red', displayLength: 3000});
                                isValid = false;
                            }

                            if (metodo !== 'efectivo') {
                                var banco = $('#banco_' + metodoId).val();
                                var referencia = $('#referencia_' + metodoId).val();

                                if (!banco) {
                                    $('#banco_' + metodoId).closest('.input-field').addClass('error');
                                    M.toast({html: '<i class="material-icons left">error</i> Por favor, selecciona un banco en todos los métodos necesarios.', classes: 'red', displayLength: 3000});
                                    isValid = false;
                                }

                                if (!referencia) {
                                    $('#referencia_' + metodoId).closest('.input-field').addClass('error');
                                    M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa un número de referencia en todos los métodos necesarios.', classes: 'red', displayLength: 3000});
                                    isValid = false;
                                }
                            }

                            if (metodo === 'pago_movil') {
                                var telefono = $('#telefono_' + metodoId).val();

                                if (!telefono) {
                                    $('#telefono_' + metodoId).closest('.input-field').addClass('error');
                                    M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa un número de teléfono para los pagos móviles.', classes: 'red', displayLength: 3000});
                                    isValid = false;
                                }
                            }
                        });

                        // Verificar que el total pagado sea igual al total del pedido
                        if (Math.abs(totalPagado - totalBolivares) > 0.01) {
                            M.toast({html: '<i class="material-icons left">error</i> El monto total pagado debe ser igual al total del pedido.', classes: 'red', displayLength: 3000});
                            isValid = false;
                        }
                    } else if (currentTabId === 'tab-envio') {
                        // Validación para la pestaña de envío
                        if (!$('#direccion').val()) {
                            $('#direccion').closest('.input-field').addClass('error');
                            M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa una dirección de entrega.', classes: 'red', displayLength: 3000});
                            isValid = false;
                        }

                        if (!$('#destinatario_nombre').val()) {
                            $('#destinatario_nombre').closest('.input-field').addClass('error');
                            M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa el nombre del destinatario.', classes: 'red', displayLength: 3000});
                            isValid = false;
                        }

                        if (!$('#destinatario_telefono').val()) {
                            $('#destinatario_telefono').closest('.input-field').addClass('error');
                            M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa el teléfono del destinatario.', classes: 'red', displayLength: 3000});
                            isValid = false;
                        }

                        if (!$('#destinatario_cedula').val()) {
                            $('#destinatario_cedula').closest('.input-field').addClass('error');
                            M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa la cédula del destinatario.', classes: 'red', displayLength: 3000});
                            isValid = false;
                        }
                    }

                    if (!isValid) {
                        return false;
                    }
                }

                // Cambiar a la siguiente pestaña
                if (nextTabId) {
                    // Actualizar el contenido
                    $('.tab-content').removeClass('active');
                    $('#' + nextTabId).addClass('active');

                    // Actualizar las pestañas de Materialize
                    var tabIndex = 0;
                    if (nextTabId === 'tab-envio') tabIndex = 1;
                    if (nextTabId === 'tab-resumen') tabIndex = 2;

                    // Usar la API de Materialize para cambiar la pestaña
                    var tabsInstance = M.Tabs.getInstance($('.tabs'));
                    if (tabsInstance) {
                        tabsInstance.select(nextTabId);
                    } else {
                        // Si no hay instancia, seleccionar manualmente
                        $('.tabs .tab a').removeClass('active');
                        $('.tabs .tab a[href="#' + nextTabId + '"]').addClass('active');
                        $('.tabs .indicator').css({
                            'left': (tabIndex * 33.33) + '%',
                            'right': (100 - (tabIndex + 1) * 33.33) + '%'
                        });
                    }

                    // Si es la pestaña de resumen, actualizar el resumen
                    if (nextTabId === 'tab-resumen') {
                        actualizarResumen();
                    }

                    // Desplazarse al inicio del formulario
                    $('html, body').animate({
                        scrollTop: $('.form-container').offset().top - 20
                    }, 500);
                }
            });

            // Mostrar la primera pestaña por defecto
            $('#tab-pago').addClass('active');

            // Manejar la navegación entre pestañas
            $('.next-tab').click(function() {
                var nextTabId = $(this).data('next');
                var currentTab = $('.tab-content.active');

                // Validar los campos de la pestaña actual antes de continuar
                var isValid = true;

                if (currentTab.attr('id') === 'tab-pago') {
                    // Validar los campos de pago
                    var totalPagado = 0;

                    // Resetear todos los campos a estado normal
                    $('.input-field').removeClass('error');

                    // Validar cada método de pago
                    $('.metodo-pago-multiple').each(function() {
                        var metodoId = $(this).attr('id').split('-')[2];
                        var metodo = $('input[name="metodo_pago_' + metodoId + '"]:checked').val();
                        var monto = $('#monto_' + metodoId).val();
                        var fecha = $('#fecha_pago_' + metodoId).val();

                        totalPagado += parseFloat(monto) || 0;

                        if (!monto || monto <= 0) {
                            $('#monto_' + metodoId).closest('.input-field').addClass('error');
                            M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa un monto válido en todos los métodos de pago.', classes: 'red', displayLength: 3000});
                            isValid = false;
                        }

                        if (!fecha) {
                            $('#fecha_pago_' + metodoId).closest('.input-field').addClass('error');
                            M.toast({html: '<i class="material-icons left">error</i> Por favor, selecciona una fecha de pago en todos los métodos.', classes: 'red', displayLength: 3000});
                            isValid = false;
                        }

                        if (metodo !== 'efectivo') {
                            var banco = $('#banco_' + metodoId).val();
                            var referencia = $('#referencia_' + metodoId).val();

                            if (!banco) {
                                $('#banco_' + metodoId).closest('.input-field').addClass('error');
                                M.toast({html: '<i class="material-icons left">error</i> Por favor, selecciona un banco en todos los métodos necesarios.', classes: 'red', displayLength: 3000});
                                isValid = false;
                            }

                            if (!referencia) {
                                $('#referencia_' + metodoId).closest('.input-field').addClass('error');
                                M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa un número de referencia en todos los métodos necesarios.', classes: 'red', displayLength: 3000});
                                isValid = false;
                            }
                        }

                        if (metodo === 'pago_movil') {
                            var telefono = $('#telefono_' + metodoId).val();

                            if (!telefono) {
                                $('#telefono_' + metodoId).closest('.input-field').addClass('error');
                                M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa un número de teléfono para los pagos móviles.', classes: 'red', displayLength: 3000});
                                isValid = false;
                            }
                        }
                    });

                    // Verificar que el total pagado sea igual al total del pedido
                    if (Math.abs(totalPagado - totalBolivares) > 0.01) {
                        M.toast({html: '<i class="material-icons left">error</i> El monto total pagado debe ser igual al total del pedido.', classes: 'red', displayLength: 3000});
                        isValid = false;
                    }
                } else if (currentTab.attr('id') === 'tab-envio') {
                    // Validar los campos de envío
                    if (!$('#direccion').val()) {
                        $('#direccion').closest('.input-field').addClass('error');
                        M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa una dirección de entrega.', classes: 'red', displayLength: 3000});
                        isValid = false;
                    }

                    if (!$('#destinatario_nombre').val()) {
                        $('#destinatario_nombre').closest('.input-field').addClass('error');
                        M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa el nombre del destinatario.', classes: 'red', displayLength: 3000});
                        isValid = false;
                    }

                    if (!$('#destinatario_telefono').val()) {
                        $('#destinatario_telefono').closest('.input-field').addClass('error');
                        M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa el teléfono del destinatario.', classes: 'red', displayLength: 3000});
                        isValid = false;
                    }

                    if (!$('#destinatario_cedula').val()) {
                        $('#destinatario_cedula').closest('.input-field').addClass('error');
                        M.toast({html: '<i class="material-icons left">error</i> Por favor, ingresa la cédula del destinatario.', classes: 'red', displayLength: 3000});
                        isValid = false;
                    }
                }

                if (!isValid) {
                    return false;
                }

                // Si todo está validado, cambiar a la siguiente pestaña
                $('.tab-content').removeClass('active');
                $('#' + nextTabId).addClass('active');

                // Actualizar también la pestaña activa en el componente de Materialize
                var tabIndex = 0;
                if (nextTabId === 'tab-envio') tabIndex = 1;
                if (nextTabId === 'tab-resumen') tabIndex = 2;

                // Usar la API de Materialize para cambiar la pestaña
                var tabsInstance = M.Tabs.getInstance($('.tabs'));
                if (tabsInstance) {
                    tabsInstance.select(nextTabId);
                } else {
                    // Si no hay instancia, seleccionar manualmente
                    $('.tabs .tab a').removeClass('active');
                    $('.tabs .tab a[href="#' + nextTabId + '"]').addClass('active');
                    $('.tabs .indicator').css({
                        'left': (tabIndex * 33.33) + '%',
                        'right': (100 - (tabIndex + 1) * 33.33) + '%'
                    });
                }

                // Actualizar el resumen si vamos a la pestaña de resumen
                if (nextTabId === 'tab-resumen') {
                    actualizarResumen();
                }

                // Desplazarse al inicio del formulario
                $('html, body').animate({
                    scrollTop: $('.form-container').offset().top - 20
                }, 500);
            });

            // Manejar el botón de anterior
            $('.prev-tab').click(function() {
                var prevTabId = $(this).data('prev');
                $('.tab-content').removeClass('active');
                $('#' + prevTabId).addClass('active');

                // Actualizar también la pestaña activa en el componente de Materialize
                var tabIndex = 0;
                if (prevTabId === 'tab-envio') tabIndex = 1;
                if (prevTabId === 'tab-resumen') tabIndex = 2;

                // Usar la API de Materialize para cambiar la pestaña
                var tabsInstance = M.Tabs.getInstance($('.tabs'));
                if (tabsInstance) {
                    tabsInstance.select(prevTabId);
                } else {
                    // Si no hay instancia, seleccionar manualmente
                    $('.tabs .tab a').removeClass('active');
                    $('.tabs .tab a[href="#' + prevTabId + '"]').addClass('active');
                    $('.tabs .indicator').css({
                        'left': (tabIndex * 33.33) + '%',
                        'right': (100 - (tabIndex + 1) * 33.33) + '%'
                    });
                }

                // Desplazarse al inicio del formulario
                $('html, body').animate({
                    scrollTop: $('.form-container').offset().top - 20
                }, 500);
            });

            // Función para actualizar el resumen
            function actualizarResumen() {
                // Limpiar el resumen
                $('#resumen-pagos').empty();
                $('#resumen-envio').empty();

                // Añadir información de pagos
                var htmlPagos = '<ul class="collection">';

                $('.metodo-pago-multiple').each(function() {
                    var metodoId = $(this).attr('id').split('-')[2];
                    var metodoTexto = $('input[name="metodo_pago_' + metodoId + '"]:checked').next('span').text();
                    var monto = $('#monto_' + metodoId).val();
                    var fecha = $('#fecha_pago_' + metodoId).val();

                    htmlPagos += '<li class="collection-item">';
                    htmlPagos += '<div><strong>Método:</strong> ' + metodoTexto + '</div>';
                    htmlPagos += '<div><strong>Monto:</strong> Bs. ' + parseFloat(monto).toFixed(2) + '</div>';
                    htmlPagos += '<div><strong>Fecha:</strong> ' + fecha + '</div>';

                    var metodo = $('input[name="metodo_pago_' + metodoId + '"]:checked').val();
                    if (metodo !== 'efectivo') {
                        var banco = $('#banco_' + metodoId + ' option:selected').text();
                        var referencia = $('#referencia_' + metodoId).val();

                        htmlPagos += '<div><strong>Banco:</strong> ' + banco + '</div>';
                        htmlPagos += '<div><strong>Referencia:</strong> ' + referencia + '</div>';

                        if (metodo === 'pago_movil') {
                            var telefono = $('#telefono_' + metodoId).val();
                            htmlPagos += '<div><strong>Teléfono:</strong> ' + telefono + '</div>';
                        }
                    }

                    htmlPagos += '</li>';
                });

                htmlPagos += '</ul>';
                $('#resumen-pagos').html(htmlPagos);

                // Añadir información de envío
                var htmlEnvio = '<ul class="collection">';
                htmlEnvio += '<li class="collection-item">';
                htmlEnvio += '<div><strong>Dirección:</strong> ' + $('#direccion').val() + '</div>';

                if ($('#empresa_envio').val()) {
                    htmlEnvio += '<div><strong>Empresa de envío:</strong> ' + $('#empresa_envio').val() + '</div>';
                }

                htmlEnvio += '<div><strong>Destinatario:</strong> ' + $('#destinatario_nombre').val() + '</div>';
                htmlEnvio += '<div><strong>Teléfono:</strong> ' + $('#destinatario_telefono').val() + '</div>';
                htmlEnvio += '<div><strong>Cédula:</strong> ' + $('#destinatario_cedula').val() + '</div>';

                if ($('#instrucciones_adicionales').val()) {
                    htmlEnvio += '<div><strong>Instrucciones adicionales:</strong> ' + $('#instrucciones_adicionales').val() + '</div>';
                }

                htmlEnvio += '</li>';
                htmlEnvio += '</ul>';
                $('#resumen-envio').html(htmlEnvio);
            }
        });
    </script>
</body>
</html>
