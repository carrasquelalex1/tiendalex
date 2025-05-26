<?php
/**
 * Script para verificar y corregir la estructura de la tabla envios
 */

// Incluir la conexión a la base de datos
require_once '../config/db.php';

// Verificar si la tabla envios existe
$sql_check_table = "SHOW TABLES LIKE 'envios'";
$result_check = $conn->query($sql_check_table);
$tabla_envios_existe = ($result_check && $result_check->num_rows > 0);

if ($tabla_envios_existe) {
    echo "La tabla 'envios' existe.<br>";
    
    // Verificar la estructura de la columna 'estado'
    $sql_column_type = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_SCHEMA = 'tiendalex2' 
                        AND TABLE_NAME = 'envios' 
                        AND COLUMN_NAME = 'estado'";
    
    $result_column_type = $conn->query($sql_column_type);
    
    if ($result_column_type && $result_column_type->num_rows > 0) {
        $row = $result_column_type->fetch_assoc();
        $column_type = $row['COLUMN_TYPE'];
        
        echo "Tipo actual de la columna 'estado': " . $column_type . "<br>";
        
        // Verificar si es necesario modificar la columna
        $modificar_columna = false;
        
        // Si es un ENUM, verificar si 'enviado' está entre los valores permitidos
        if (strpos($column_type, 'enum') === 0) {
            preg_match_all("/'(.*?)'/", $column_type, $matches);
            $valores_permitidos = $matches[1];
            
            echo "Valores permitidos actualmente: " . implode(", ", $valores_permitidos) . "<br>";
            
            if (!in_array('enviado', $valores_permitidos)) {
                $modificar_columna = true;
                echo "El valor 'enviado' no está entre los valores permitidos. Se modificará la columna.<br>";
            } else {
                echo "El valor 'enviado' ya está entre los valores permitidos. No es necesario modificar la columna.<br>";
            }
        } else {
            // Si no es un ENUM, modificar para convertirlo en uno
            $modificar_columna = true;
            echo "La columna 'estado' no es un ENUM. Se modificará la columna.<br>";
        }
        
        if ($modificar_columna) {
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // Modificar la columna para incluir 'enviado' entre los valores permitidos
                $sql_alter = "ALTER TABLE envios MODIFY COLUMN estado ENUM('pendiente', 'en_proceso', 'enviado', 'entregado') NOT NULL DEFAULT 'pendiente'";
                
                if ($conn->query($sql_alter)) {
                    echo "La columna 'estado' ha sido modificada correctamente.<br>";
                    
                    // Confirmar transacción
                    $conn->commit();
                } else {
                    throw new Exception("Error al modificar la columna: " . $conn->error);
                }
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $conn->rollback();
                echo "Error: " . $e->getMessage() . "<br>";
            }
        }
    } else {
        echo "Error al obtener información sobre la columna 'estado'.<br>";
    }
} else {
    echo "La tabla 'envios' no existe. Se creará la tabla.<br>";
    
    // Crear la tabla envios
    $sql_create_table = "CREATE TABLE envios (
        id INT(11) NOT NULL AUTO_INCREMENT,
        pedido_codigo VARCHAR(20) NOT NULL,
        usuario_codigo INT(11) NOT NULL,
        estado ENUM('pendiente', 'en_proceso', 'enviado', 'entregado') NOT NULL DEFAULT 'pendiente',
        fecha_envio DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_pedido_codigo (pedido_codigo),
        KEY idx_usuario_codigo (usuario_codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql_create_table)) {
        echo "La tabla 'envios' ha sido creada correctamente.<br>";
    } else {
        echo "Error al crear la tabla 'envios': " . $conn->error . "<br>";
    }
}

// Verificar la estructura de la tabla pedidos_finalizados
echo "<h2>Verificando la tabla pedidos_finalizados</h2>";

$sql_check_table = "SHOW TABLES LIKE 'pedidos_finalizados'";
$result_check = $conn->query($sql_check_table);
$tabla_pedidos_finalizados_existe = ($result_check && $result_check->num_rows > 0);

if ($tabla_pedidos_finalizados_existe) {
    echo "La tabla 'pedidos_finalizados' existe.<br>";
    
    // Verificar la estructura de la columna 'estado'
    $sql_column_type = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_SCHEMA = 'tiendalex2' 
                        AND TABLE_NAME = 'pedidos_finalizados' 
                        AND COLUMN_NAME = 'estado'";
    
    $result_column_type = $conn->query($sql_column_type);
    
    if ($result_column_type && $result_column_type->num_rows > 0) {
        $row = $result_column_type->fetch_assoc();
        $column_type = $row['COLUMN_TYPE'];
        
        echo "Tipo actual de la columna 'estado': " . $column_type . "<br>";
        
        // Verificar si es necesario modificar la columna
        $modificar_columna = false;
        
        // Si es un ENUM, verificar si 'enviado' está entre los valores permitidos
        if (strpos($column_type, 'enum') === 0) {
            preg_match_all("/'(.*?)'/", $column_type, $matches);
            $valores_permitidos = $matches[1];
            
            echo "Valores permitidos actualmente: " . implode(", ", $valores_permitidos) . "<br>";
            
            if (!in_array('enviado', $valores_permitidos)) {
                $modificar_columna = true;
                echo "El valor 'enviado' no está entre los valores permitidos. Se modificará la columna.<br>";
            } else {
                echo "El valor 'enviado' ya está entre los valores permitidos. No es necesario modificar la columna.<br>";
            }
        } else {
            // Si no es un ENUM, modificar para convertirlo en uno
            $modificar_columna = true;
            echo "La columna 'estado' no es un ENUM. Se modificará la columna.<br>";
        }
        
        if ($modificar_columna) {
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // Modificar la columna para incluir 'enviado' entre los valores permitidos
                $sql_alter = "ALTER TABLE pedidos_finalizados MODIFY COLUMN estado ENUM('pendiente', 'verificado', 'enviado', 'entregado') NOT NULL DEFAULT 'pendiente'";
                
                if ($conn->query($sql_alter)) {
                    echo "La columna 'estado' ha sido modificada correctamente.<br>";
                    
                    // Confirmar transacción
                    $conn->commit();
                } else {
                    throw new Exception("Error al modificar la columna: " . $conn->error);
                }
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $conn->rollback();
                echo "Error: " . $e->getMessage() . "<br>";
            }
        }
    } else {
        echo "Error al obtener información sobre la columna 'estado'.<br>";
    }
} else {
    echo "La tabla 'pedidos_finalizados' no existe.<br>";
}

// Verificar la estructura de la tabla pagos
echo "<h2>Verificando la tabla pagos</h2>";

$sql_check_table = "SHOW TABLES LIKE 'pagos'";
$result_check = $conn->query($sql_check_table);
$tabla_pagos_existe = ($result_check && $result_check->num_rows > 0);

if ($tabla_pagos_existe) {
    echo "La tabla 'pagos' existe.<br>";
    
    // Verificar la estructura de la columna 'estado'
    $sql_column_type = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_SCHEMA = 'tiendalex2' 
                        AND TABLE_NAME = 'pagos' 
                        AND COLUMN_NAME = 'estado'";
    
    $result_column_type = $conn->query($sql_column_type);
    
    if ($result_column_type && $result_column_type->num_rows > 0) {
        $row = $result_column_type->fetch_assoc();
        $column_type = $row['COLUMN_TYPE'];
        
        echo "Tipo actual de la columna 'estado': " . $column_type . "<br>";
        
        // Verificar si es necesario modificar la columna
        $modificar_columna = false;
        
        // Si es un ENUM, verificar si 'enviado' está entre los valores permitidos
        if (strpos($column_type, 'enum') === 0) {
            preg_match_all("/'(.*?)'/", $column_type, $matches);
            $valores_permitidos = $matches[1];
            
            echo "Valores permitidos actualmente: " . implode(", ", $valores_permitidos) . "<br>";
            
            if (!in_array('enviado', $valores_permitidos)) {
                $modificar_columna = true;
                echo "El valor 'enviado' no está entre los valores permitidos. Se modificará la columna.<br>";
            } else {
                echo "El valor 'enviado' ya está entre los valores permitidos. No es necesario modificar la columna.<br>";
            }
        } else {
            // Si no es un ENUM, modificar para convertirlo en uno
            $modificar_columna = true;
            echo "La columna 'estado' no es un ENUM. Se modificará la columna.<br>";
        }
        
        if ($modificar_columna) {
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // Modificar la columna para incluir 'enviado' entre los valores permitidos
                $sql_alter = "ALTER TABLE pagos MODIFY COLUMN estado ENUM('pendiente', 'verificado', 'enviado', 'entregado') NOT NULL DEFAULT 'pendiente'";
                
                if ($conn->query($sql_alter)) {
                    echo "La columna 'estado' ha sido modificada correctamente.<br>";
                    
                    // Confirmar transacción
                    $conn->commit();
                } else {
                    throw new Exception("Error al modificar la columna: " . $conn->error);
                }
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $conn->rollback();
                echo "Error: " . $e->getMessage() . "<br>";
            }
        }
    } else {
        echo "Error al obtener información sobre la columna 'estado'.<br>";
    }
} else {
    echo "La tabla 'pagos' no existe.<br>";
}
