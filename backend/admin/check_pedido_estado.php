<?php
/**
 * Script para verificar el estado de un pedido específico en todas las tablas relevantes
 */

// Incluir la conexión a la base de datos
require_once '../config/db.php';

// Verificar si se recibió el código del pedido
$pedido_codigo = isset($_GET['pedido']) ? $_GET['pedido'] : 'CL271777';

echo "<h1>Verificación del estado del pedido: $pedido_codigo</h1>";

// Verificar en la tabla pedidos_finalizados
echo "<h2>Estado en la tabla 'pedidos_finalizados':</h2>";
$sql_pedidos_finalizados = "SELECT * FROM pedidos_finalizados WHERE pedido_codigo = ?";
$stmt_pedidos_finalizados = $conn->prepare($sql_pedidos_finalizados);
$stmt_pedidos_finalizados->bind_param("s", $pedido_codigo);
$stmt_pedidos_finalizados->execute();
$result_pedidos_finalizados = $stmt_pedidos_finalizados->get_result();

if ($result_pedidos_finalizados && $result_pedidos_finalizados->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Pedido Código</th><th>Usuario ID</th><th>Producto ID</th><th>Cantidad</th><th>Precio Dólares</th><th>Precio Bolívares</th><th>Estado</th><th>Created At</th><th>Updated At</th></tr>";
    
    while ($row = $result_pedidos_finalizados->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['pedido_codigo'] . "</td>";
        echo "<td>" . $row['usuario_id'] . "</td>";
        echo "<td>" . $row['producto_id'] . "</td>";
        echo "<td>" . $row['cantidad'] . "</td>";
        echo "<td>" . $row['precio_dolares'] . "</td>";
        echo "<td>" . $row['precio_bolivares'] . "</td>";
        echo "<td><strong>" . $row['estado'] . "</strong></td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "<td>" . $row['updated_at'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "No se encontraron registros en la tabla 'pedidos_finalizados' para el pedido $pedido_codigo.<br>";
}

// Verificar en la tabla pagos
echo "<h2>Estado en la tabla 'pagos':</h2>";
$sql_pagos = "SELECT * FROM pagos WHERE pedido_codigo = ?";
$stmt_pagos = $conn->prepare($sql_pagos);
$stmt_pagos->bind_param("s", $pedido_codigo);
$stmt_pagos->execute();
$result_pagos = $stmt_pagos->get_result();

if ($result_pagos && $result_pagos->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Pedido Código</th><th>Usuario Código</th><th>Método Pago</th><th>Banco</th><th>Referencia</th><th>Monto</th><th>Fecha Pago</th><th>Teléfono</th><th>Estado</th><th>Created At</th><th>Updated At</th></tr>";
    
    while ($row = $result_pagos->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['pedido_codigo'] . "</td>";
        echo "<td>" . $row['usuario_codigo'] . "</td>";
        echo "<td>" . $row['metodo_pago'] . "</td>";
        echo "<td>" . $row['banco'] . "</td>";
        echo "<td>" . $row['referencia'] . "</td>";
        echo "<td>" . $row['monto'] . "</td>";
        echo "<td>" . $row['fecha_pago'] . "</td>";
        echo "<td>" . $row['telefono'] . "</td>";
        echo "<td><strong>" . $row['estado'] . "</strong></td>";
        echo "<td>" . $row['fecha_registro'] . "</td>";
        echo "<td>" . $row['updated_at'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "No se encontraron registros en la tabla 'pagos' para el pedido $pedido_codigo.<br>";
}

// Verificar en la tabla envios
echo "<h2>Estado en la tabla 'envios':</h2>";

// Verificar si la tabla envios existe
$tabla_envios_existe = false;
$sql_check_table = "SHOW TABLES LIKE 'envios'";
$result_check = $conn->query($sql_check_table);
if ($result_check && $result_check->num_rows > 0) {
    $tabla_envios_existe = true;
}

if ($tabla_envios_existe) {
    $sql_envios = "SELECT * FROM envios WHERE pedido_codigo = ?";
    $stmt_envios = $conn->prepare($sql_envios);
    $stmt_envios->bind_param("s", $pedido_codigo);
    $stmt_envios->execute();
    $result_envios = $stmt_envios->get_result();
    
    if ($result_envios && $result_envios->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Pedido Código</th><th>Usuario Código</th><th>Estado</th><th>Fecha Envío</th><th>Created At</th><th>Updated At</th></tr>";
        
        while ($row = $result_envios->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['pedido_codigo'] . "</td>";
            echo "<td>" . $row['usuario_codigo'] . "</td>";
            echo "<td><strong>" . $row['estado'] . "</strong></td>";
            echo "<td>" . $row['fecha_envio'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "<td>" . $row['updated_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "No se encontraron registros en la tabla 'envios' para el pedido $pedido_codigo.<br>";
    }
} else {
    echo "La tabla 'envios' no existe en la base de datos.<br>";
}

// Verificar cómo se determina el estado en mis_compras.php
echo "<h2>Simulación de la lógica de mis_compras.php para este pedido:</h2>";

// Obtener el estado del pago
$estado_pago = "desconocido";
$sql_estado_pago = "SELECT estado FROM pagos WHERE pedido_codigo = ? LIMIT 1";
$stmt_estado_pago = $conn->prepare($sql_estado_pago);
$stmt_estado_pago->bind_param("s", $pedido_codigo);
$stmt_estado_pago->execute();
$result_estado_pago = $stmt_estado_pago->get_result();

if ($result_estado_pago && $result_estado_pago->num_rows > 0) {
    $row = $result_estado_pago->fetch_assoc();
    $estado_pago = $row['estado'];
}

echo "Estado del pago: <strong>$estado_pago</strong><br>";

// Determinar el estado según la lógica de mis_compras.php
$estado_final = "desconocido";

if ($estado_pago == 'pendiente') {
    $estado_final = "pagado (pendiente de verificación)";
} elseif ($estado_pago == 'verificado') {
    if ($tabla_envios_existe) {
        $sql_envio = "SELECT estado FROM envios WHERE pedido_codigo = ? LIMIT 1";
        $stmt_envio = $conn->prepare($sql_envio);
        $stmt_envio->bind_param("s", $pedido_codigo);
        $stmt_envio->execute();
        $result_envio = $stmt_envio->get_result();
        
        if ($result_envio->num_rows > 0) {
            $envio = $result_envio->fetch_assoc();
            if ($envio['estado'] == 'enviado') {
                $estado_final = "enviado";
            } else {
                $estado_final = "en proceso de envío";
            }
        } else {
            $estado_final = "en proceso de envío (no hay registro en envios)";
        }
    } else {
        $estado_final = "en proceso de envío (tabla envios no existe)";
    }
}

echo "Estado final según la lógica de mis_compras.php: <strong>$estado_final</strong><br>";

// Proporcionar opciones para corregir el estado
echo "<h2>Opciones para corregir el estado:</h2>";

echo "<form action='fix_pedido_estado.php' method='post'>";
echo "<input type='hidden' name='pedido_codigo' value='$pedido_codigo'>";
echo "<p>Seleccione la acción a realizar:</p>";

echo "<div style='margin-bottom: 10px;'>";
echo "<input type='radio' id='update_envios' name='accion' value='update_envios' checked>";
echo "<label for='update_envios'>Actualizar estado en la tabla 'envios' a 'enviado'</label>";
echo "</div>";

echo "<div style='margin-bottom: 10px;'>";
echo "<input type='radio' id='insert_envios' name='accion' value='insert_envios'>";
echo "<label for='insert_envios'>Insertar nuevo registro en la tabla 'envios' con estado 'enviado'</label>";
echo "</div>";

echo "<div style='margin-bottom: 10px;'>";
echo "<input type='radio' id='update_all' name='accion' value='update_all'>";
echo "<label for='update_all'>Actualizar estado en todas las tablas relevantes</label>";
echo "</div>";

echo "<button type='submit' style='padding: 10px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;'>Aplicar Corrección</button>";
echo "</form>";
