<?php
// Incluir el archivo de conexión a la base de datos
require_once 'config/db.php';

// Obtener la tasa actual
$sql_tasa = "SELECT valor_tasa FROM tasa WHERE current = 1 ORDER BY id_tasa DESC LIMIT 1";
$result_tasa = $conn->query($sql_tasa);

if ($result_tasa && $result_tasa->num_rows > 0) {
    $tasa_actual = $result_tasa->fetch_assoc()['valor_tasa'];
    
    echo "<h2>Actualizando precios con tasa: " . number_format($tasa_actual, 2, ',', '.') . " Bs/$" . "</h2>";
    
    // Actualizar directamente todos los pedidos pendientes
    $update_sql = "UPDATE pedidos_finalizados 
                  SET precio_bolivares = ROUND(precio * ?, 2),
                      precio_dolares = precio,
                      updated_at = NOW()
                  WHERE estado IN ('pago_pendiente', 'pendiente')";
    
    $stmt = $conn->prepare($update_sql);
    if (!$stmt) {
        die("Error al preparar la actualización: " . $conn->error);
    }
    
    $stmt->bind_param("d", $tasa_actual);
    
    if ($stmt->execute()) {
        $filas_afectadas = $stmt->affected_rows;
        echo "<p>Se actualizaron correctamente $filas_afectadas pedidos pendientes.</p>";
        
        // Mostrar los pedidos actualizados
        $sql_pedidos = "SELECT id, pedido_codigo, estado, 
                       FORMAT(precio, 2) as precio_usd, 
                       FORMAT(precio_bolivares, 2, 'de_DE') as precio_bs
                       FROM pedidos_finalizados 
                       WHERE estado IN ('pago_pendiente', 'pendiente')
                       ORDER BY updated_at DESC";
        
        $result = $conn->query($sql_pedidos);
        
        if ($result && $result->num_rows > 0) {
            echo "<h3>Pedidos actualizados:</h3>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Código</th><th>Estado</th><th>Precio (USD)</th><th>Precio (Bs)</th></tr>";
            
            while ($pedido = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $pedido['id'] . "</td>";
                echo "<td>" . htmlspecialchars($pedido['pedido_codigo']) . "</td>";
                echo "<td>" . htmlspecialchars($pedido['estado']) . "</td>";
                echo "<td style='text-align: right;'>" . $pedido['precio_usd'] . " $" . "</td>";
                echo "<td style='text-align: right;'>" . $pedido['precio_bs'] . " Bs" . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    } else {
        echo "<p>Error al ejecutar la actualización: " . $stmt->error . "</p>";
    }
    
    $stmt->close();
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
    border: 1px solid #ddd;
}
th {
    background-color: #f2f2f2;
}
tr:nth-child(even) {
    background-color: #f9f9f9;
}
</style>
