<?php
/**
 * Script para depurar el procesamiento de pagos
 */

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configurar la salida como HTML
header('Content-Type: text/html; charset=utf-8');

// Incluir archivos necesarios
require_once 'config/db.php';

// Habilitar la visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Función para mostrar mensajes
function mostrarMensaje($tipo, $mensaje) {
    $color = ($tipo == 'error') ? 'red' : 'green';
    echo "<div style='background-color: $color; color: white; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>" . ucfirst($tipo) . ":</strong> " . $mensaje;
    echo "</div>";
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_logueado']) || !isset($_SESSION['rol_codigo'])) {
    mostrarMensaje('error', 'Debes iniciar sesión para realizar esta acción.');
    exit;
}

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // Mostrar formulario de prueba
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Depuración de Procesamiento de Pagos</title>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css'>
        <link href='https://fonts.googleapis.com/icon?family=Material+Icons' rel='stylesheet'>
        <style>
            body { padding: 20px; }
            .container { max-width: 800px; }
            .card { padding: 20px; }
            .btn { margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h3>Depuración de Procesamiento de Pagos</h3>
            <div class='card'>
                <form method='POST' action='debug_procesar_pago.php'>
                    <div class='row'>
                        <div class='input-field col s12'>
                            <input id='pedido_codigo' name='pedido_codigo' type='text' class='validate' required>
                            <label for='pedido_codigo'>Código de Pedido</label>
                        </div>
                        <div class='input-field col s12'>
                            <input id='usuario_codigo' name='usuario_codigo' type='text' class='validate' value='" . $_SESSION['usuario_logueado'] . "' required>
                            <label for='usuario_codigo'>Código de Usuario</label>
                        </div>
                        <div class='input-field col s12'>
                            <textarea id='comentarios' name='comentarios' class='materialize-textarea'></textarea>
                            <label for='comentarios'>Comentarios</label>
                        </div>
                        
                        <div class='col s12'>
                            <h5>Datos de Envío</h5>
                        </div>
                        
                        <div class='input-field col s12'>
                            <textarea id='direccion' name='direccion' class='materialize-textarea' required></textarea>
                            <label for='direccion'>Dirección</label>
                        </div>
                        <div class='input-field col s12'>
                            <input id='empresa_envio' name='empresa_envio' type='text' class='validate'>
                            <label for='empresa_envio'>Empresa de Envío</label>
                        </div>
                        <div class='input-field col s12'>
                            <input id='destinatario_nombre' name='destinatario_nombre' type='text' class='validate' required>
                            <label for='destinatario_nombre'>Nombre del Destinatario</label>
                        </div>
                        <div class='input-field col s12'>
                            <input id='destinatario_telefono' name='destinatario_telefono' type='text' class='validate' required>
                            <label for='destinatario_telefono'>Teléfono del Destinatario</label>
                        </div>
                        <div class='input-field col s12'>
                            <input id='destinatario_cedula' name='destinatario_cedula' type='text' class='validate' required>
                            <label for='destinatario_cedula'>Cédula del Destinatario</label>
                        </div>
                        <div class='input-field col s12'>
                            <textarea id='instrucciones_adicionales' name='instrucciones_adicionales' class='materialize-textarea'></textarea>
                            <label for='instrucciones_adicionales'>Instrucciones Adicionales</label>
                        </div>
                        
                        <div class='col s12'>
                            <h5>Método de Pago</h5>
                        </div>
                        
                        <div class='input-field col s12'>
                            <select id='metodo_pago_1' name='metodo_pago_1' class='validate' required>
                                <option value='' disabled selected>Seleccione un método</option>
                                <option value='transferencia'>Transferencia bancaria</option>
                                <option value='pago_movil'>Pago móvil</option>
                                <option value='efectivo'>Efectivo</option>
                            </select>
                            <label for='metodo_pago_1'>Método de Pago</label>
                        </div>
                        <div class='input-field col s12'>
                            <input id='banco_1' name='banco_1' type='text' class='validate'>
                            <label for='banco_1'>Banco</label>
                        </div>
                        <div class='input-field col s12'>
                            <input id='referencia_1' name='referencia_1' type='text' class='validate'>
                            <label for='referencia_1'>Referencia</label>
                        </div>
                        <div class='input-field col s12'>
                            <input id='monto_1' name='monto_1' type='number' step='0.01' class='validate' required>
                            <label for='monto_1'>Monto</label>
                        </div>
                        <div class='input-field col s12'>
                            <input id='telefono_1' name='telefono_1' type='text' class='validate'>
                            <label for='telefono_1'>Teléfono</label>
                        </div>
                        <div class='input-field col s12'>
                            <input id='fecha_pago_1' name='fecha_pago_1' type='date' class='validate' required>
                            <label for='fecha_pago_1'>Fecha de Pago</label>
                        </div>
                        
                        <input type='hidden' name='metodo_count' value='1'>
                        
                        <div class='col s12'>
                            <button type='submit' class='btn waves-effect waves-light'>
                                <i class='material-icons left'>payment</i>Procesar Pago
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var elems = document.querySelectorAll('select');
                var instances = M.FormSelect.init(elems);
                
                M.updateTextFields();
            });
        </script>
    </body>
    </html>";
    exit;
}

// Obtener datos del formulario
$pedido_codigo = isset($_POST['pedido_codigo']) ? $_POST['pedido_codigo'] : '';
$usuario_codigo = isset($_POST['usuario_codigo']) ? $_POST['usuario_codigo'] : '';
$comentarios = isset($_POST['comentarios']) ? $_POST['comentarios'] : '';

// Obtener datos de envío
$direccion = isset($_POST['direccion']) ? $_POST['direccion'] : '';
$empresa_envio = isset($_POST['empresa_envio']) ? $_POST['empresa_envio'] : '';
$destinatario_nombre = isset($_POST['destinatario_nombre']) ? $_POST['destinatario_nombre'] : '';
$destinatario_telefono = isset($_POST['destinatario_telefono']) ? $_POST['destinatario_telefono'] : '';
$destinatario_cedula = isset($_POST['destinatario_cedula']) ? $_POST['destinatario_cedula'] : '';
$instrucciones_adicionales = isset($_POST['instrucciones_adicionales']) ? $_POST['instrucciones_adicionales'] : '';

// Obtener el número de métodos de pago
$metodo_count = isset($_POST['metodo_count']) ? intval($_POST['metodo_count']) : 1;

// Mostrar los datos recibidos
echo "<h2>Datos recibidos:</h2>";
echo "<pre>";
echo "Pedido: $pedido_codigo\n";
echo "Usuario: $usuario_codigo\n";
echo "Comentarios: $comentarios\n";
echo "Dirección: $direccion\n";
echo "Empresa de envío: $empresa_envio\n";
echo "Destinatario: $destinatario_nombre\n";
echo "Teléfono: $destinatario_telefono\n";
echo "Cédula: $destinatario_cedula\n";
echo "Instrucciones: $instrucciones_adicionales\n";
echo "Métodos de pago: $metodo_count\n";

for ($i = 1; $i <= $metodo_count; $i++) {
    $metodo_pago = isset($_POST['metodo_pago_' . $i]) ? $_POST['metodo_pago_' . $i] : '';
    $banco = isset($_POST['banco_' . $i]) ? $_POST['banco_' . $i] : '';
    $referencia = isset($_POST['referencia_' . $i]) ? $_POST['referencia_' . $i] : '';
    $monto = isset($_POST['monto_' . $i]) ? floatval($_POST['monto_' . $i]) : 0;
    $telefono = isset($_POST['telefono_' . $i]) ? $_POST['telefono_' . $i] : '';
    $fecha_pago = isset($_POST['fecha_pago_' . $i]) ? $_POST['fecha_pago_' . $i] : '';
    
    echo "\nMétodo de pago $i:\n";
    echo "  Tipo: $metodo_pago\n";
    echo "  Banco: $banco\n";
    echo "  Referencia: $referencia\n";
    echo "  Monto: $monto\n";
    echo "  Teléfono: $telefono\n";
    echo "  Fecha: $fecha_pago\n";
}
echo "</pre>";

// Verificar si el pedido pertenece al usuario
$sql = "SELECT COUNT(*) as count FROM pedido WHERE pedido = ? AND usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $pedido_codigo, $usuario_codigo);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    mostrarMensaje('error', 'El pedido no existe o no te pertenece.');
    exit;
}

// Verificar si ya existe un pago registrado para este pedido
$sql = "SELECT COUNT(*) as count FROM pagos WHERE pedido_codigo = ? AND usuario_codigo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $pedido_codigo, $usuario_codigo);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    mostrarMensaje('error', 'Ya existe un pago registrado para este pedido.');
    exit;
}

// Mostrar un botón para continuar con el procesamiento real
echo "<div style='margin-top: 20px;'>";
echo "<form action='procesar_pago.php' method='POST'>";
foreach ($_POST as $key => $value) {
    echo "<input type='hidden' name='" . htmlspecialchars($key) . "' value='" . htmlspecialchars($value) . "'>";
}
echo "<button type='submit' class='btn waves-effect waves-light green'>Continuar con el procesamiento real</button>";
echo "</form>";
echo "</div>";

// Mostrar un botón para volver al formulario
echo "<div style='margin-top: 10px;'>";
echo "<a href='debug_procesar_pago.php' class='btn waves-effect waves-light blue'>Volver al formulario</a>";
echo "</div>";
?>
