<?php
// Incluir el archivo de conexión a la base de datos
require_once 'config/db.php';

// Obtener la tasa actual
$sql_tasa = "SELECT valor_tasa FROM tasa WHERE current = 1 ORDER BY id_tasa DESC LIMIT 1";
$result_tasa = $conn->query($sql_tasa);

if ($result_tasa && $result_tasa->num_rows > 0) {
    $tasa_actual = $result_tasa->fetch_assoc()['valor_tasa'];
    
    // Obtener todos los pedidos pendientes
    $sql_pedidos = "SELECT pf.id, pf.pedido_codigo, pf.precio as precio_original, 
                   pf.precio_bolivares as precio_actual_bs, pf.estado
                   FROM pedidos_finalizados pf
                   WHERE (pf.estado = 'pago_pendiente' OR pf.estado = 'pendiente')
                   AND (pf.precio_bolivares IS NULL OR pf.precio_bolivares = 0 OR 
                        ABS((pf.precio * ?) - IFNULL(pf.precio_bolivares, 0)) > 0.01)";
    
    $stmt = $conn->prepare($sql_pedidos);
    if (!$stmt) {
        die("Error al preparar la consulta: " . $conn->error);
    }
    
    $stmt->bind_param("d", $tasa_actual);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $actualizados = 0;
    $errores = [];
    
    if ($result && $result->num_rows > 0) {
        $update_sql = "UPDATE pedidos_finalizados SET 
                      precio_bolivares = ROUND(precio * ?, 2),
                      precio_dolares = precio,
                      updated_at = NOW() 
                      WHERE id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            die("Error al preparar la actualización: " . $conn->error);
        }
        
        while ($pedido = $result->fetch_assoc()) {
            $nuevo_precio = round($pedido['precio_original'] * $tasa_actual, 2);
            
            error_log(sprintf(
                "Actualizando pedido ID %s (Código: %s, Estado: %s): Precio actual: %s Bs, Nuevo precio: %s Bs (Tasa: %s)",
                $pedido['id'],
                $pedido['pedido_codigo'],
                $pedido['estado'],
                $pedido['precio_actual_bs'],
                $nuevo_precio,
                $tasa_actual
            ));
            
            $update_stmt->bind_param("di", $tasa_actual, $pedido['id']);
            if ($update_stmt->execute()) {
                $actualizados++;
            } else {
                $errores[] = "Error al actualizar pedido ID {$pedido['id']}: " . $update_stmt->error;
            }
        }
        
        $update_stmt->close();
    }
    
    $stmt->close();
    
    // Mostrar resultados
    echo "<h2>Proceso de actualización completado</h2>";
    echo "<p>Tasa de cambio utilizada: " . number_format($tasa_actual, 2) . " Bs/$" . "</p>";
    echo "<p>Pedidos actualizados: " . $actualizados . "</p>";
    
    if (!empty($errores)) {
        echo "<h3>Errores encontrados:</h3>";
        echo "<ul>";
        foreach ($errores as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    }
    
    // Mostrar lista de pedidos actualizados
    if ($actualizados > 0) {
        echo "<h3>Pedidos actualizados:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Código</th><th>Estado</th><th>Precio ($)</th><th>Precio (Bs.)</th></tr>";
        
        $sql_actualizados = "SELECT id, pedido_codigo, estado, precio, precio_bolivares 
                            FROM pedidos_finalizados 
                            WHERE (estado = 'pago_pendiente' OR estado = 'pendiente')
                            ORDER BY updated_at DESC";
        
        $result_actualizados = $conn->query($sql_actualizados);
        
        if ($result_actualizados && $result_actualizados->num_rows > 0) {
            while ($row = $result_actualizados->fetch_assoc()) {
                echo sprintf(
                    "<tr><td>%s</td><td>%s</td><td>%s</td><td>%s $</td><td>%s Bs.</td></tr>",
                    $row['id'],
                    htmlspecialchars($row['pedido_codigo']),
                    htmlspecialchars($row['estado']),
                    number_format($row['precio'], 2),
                    number_format($row['precio_bolivares'], 2, ',', '.')
                );
            }
        }
        echo "</table>";
    }
    
} else {
    echo "<p>No se encontró una tasa de cambio configurada.</p>";
}

$conn->close();
?>

<style>
table {
    border-collapse: collapse;
    width: 100%;
    max-width: 800px;
    margin: 20px 0;
}
th, td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}
th {
    background-color: #f2f2f2;
}
</style>
