<?php
/**
 * Script para limpiar registros duplicados en las tablas pedidos_finalizados y envios
 * Este script debe ejecutarse para corregir los datos existentes
 */

// Incluir el helper de sesiones
require_once '../session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado y es administrador
if (!esta_logueado() || !es_admin()) {
    echo json_encode([
        'success' => false,
        'message' => 'No tienes permisos para realizar esta acción.'
    ]);
    exit;
}

// Incluir la conexión a la base de datos
require_once '../config/db.php';

// Iniciar transacción
$conn->begin_transaction();

try {
    echo "<h1>Limpieza de registros duplicados</h1>";

    // PARTE 1: Limpiar duplicados en pedidos_finalizados
    echo "<h2>Limpieza de duplicados en pedidos_finalizados</h2>";

    // Paso 1: Identificar pedidos con registros duplicados
    $sql_duplicados = "
        SELECT pedido_codigo, usuario_id, producto_id, COUNT(*) as total
        FROM pedidos_finalizados
        GROUP BY pedido_codigo, usuario_id, producto_id
        HAVING COUNT(*) > 1
    ";

    $result_duplicados = $conn->query($sql_duplicados);

    if (!$result_duplicados) {
        throw new Exception("Error al buscar duplicados en pedidos_finalizados: " . $conn->error);
    }

    $total_duplicados = $result_duplicados->num_rows;
    $pedidos_procesados = [];

    if ($total_duplicados > 0) {
        echo "<p>Se encontraron {$total_duplicados} grupos de registros duplicados en pedidos_finalizados.</p>";

        // Paso 2: Para cada grupo de duplicados, mantener solo el registro más reciente
        while ($duplicado = $result_duplicados->fetch_assoc()) {
            $pedido_codigo = $duplicado['pedido_codigo'];
            $usuario_id = $duplicado['usuario_id'];
            $producto_id = $duplicado['producto_id'];

            // Obtener todos los registros duplicados
            $sql_registros = "
                SELECT id, estado, updated_at
                FROM pedidos_finalizados
                WHERE pedido_codigo = ? AND usuario_id = ? AND producto_id = ?
                ORDER BY updated_at DESC, id DESC
            ";

            $stmt_registros = $conn->prepare($sql_registros);
            $stmt_registros->bind_param("sii", $pedido_codigo, $usuario_id, $producto_id);
            $stmt_registros->execute();
            $result_registros = $stmt_registros->get_result();

            // Mantener el primer registro (el más reciente) y eliminar los demás
            $primer_registro = true;
            $ids_a_eliminar = [];

            while ($registro = $result_registros->fetch_assoc()) {
                if ($primer_registro) {
                    $primer_registro = false;
                    echo "<p>Manteniendo registro ID {$registro['id']} (estado: {$registro['estado']}) para pedido {$pedido_codigo}, producto {$producto_id}</p>";
                } else {
                    $ids_a_eliminar[] = $registro['id'];
                }
            }

            // Eliminar los registros duplicados
            if (!empty($ids_a_eliminar)) {
                $ids_string = implode(',', $ids_a_eliminar);
                $sql_eliminar = "DELETE FROM pedidos_finalizados WHERE id IN ({$ids_string})";

                if (!$conn->query($sql_eliminar)) {
                    throw new Exception("Error al eliminar duplicados en pedidos_finalizados: " . $conn->error);
                }

                echo "<p>Eliminados " . count($ids_a_eliminar) . " registros duplicados para pedido {$pedido_codigo}, producto {$producto_id}</p>";
            }

            // Registrar el pedido como procesado
            if (!in_array($pedido_codigo, $pedidos_procesados)) {
                $pedidos_procesados[] = $pedido_codigo;
            }
        }

        echo "<p>Total de pedidos procesados en pedidos_finalizados: " . count($pedidos_procesados) . "</p>";
    } else {
        echo "<p>No se encontraron registros duplicados en pedidos_finalizados.</p>";
    }

    // PARTE 2: Limpiar duplicados en la tabla envios
    echo "<h2>Limpieza de duplicados en envios</h2>";

    // Verificar si la tabla envios existe
    $tabla_envios_existe = false;
    $sql_check_table = "SHOW TABLES LIKE 'envios'";
    $result_check = $conn->query($sql_check_table);
    if ($result_check && $result_check->num_rows > 0) {
        $tabla_envios_existe = true;
    }

    if ($tabla_envios_existe) {
        // Paso 1: Identificar pedidos con registros duplicados en envios
        $sql_duplicados_envios = "
            SELECT pedido_codigo, usuario_codigo, COUNT(*) as total
            FROM envios
            GROUP BY pedido_codigo, usuario_codigo
            HAVING COUNT(*) > 1
        ";

        $result_duplicados_envios = $conn->query($sql_duplicados_envios);

        if (!$result_duplicados_envios) {
            throw new Exception("Error al buscar duplicados en envios: " . $conn->error);
        }

        $total_duplicados_envios = $result_duplicados_envios->num_rows;
        $envios_procesados = [];

        if ($total_duplicados_envios > 0) {
            echo "<p>Se encontraron {$total_duplicados_envios} grupos de registros duplicados en envios.</p>";

            // Paso 2: Para cada grupo de duplicados, mantener solo el registro más reciente
            while ($duplicado = $result_duplicados_envios->fetch_assoc()) {
                $pedido_codigo = $duplicado['pedido_codigo'];
                $usuario_codigo = $duplicado['usuario_codigo'];

                // Obtener todos los registros duplicados
                $sql_registros = "
                    SELECT id, estado, updated_at
                    FROM envios
                    WHERE pedido_codigo = ? AND usuario_codigo = ?
                    ORDER BY updated_at DESC, id DESC
                ";

                $stmt_registros = $conn->prepare($sql_registros);
                $stmt_registros->bind_param("si", $pedido_codigo, $usuario_codigo);
                $stmt_registros->execute();
                $result_registros = $stmt_registros->get_result();

                // Mantener el primer registro (el más reciente) y eliminar los demás
                $primer_registro = true;
                $ids_a_eliminar = [];

                while ($registro = $result_registros->fetch_assoc()) {
                    if ($primer_registro) {
                        $primer_registro = false;
                        echo "<p>Manteniendo registro ID {$registro['id']} (estado: {$registro['estado']}) para pedido {$pedido_codigo}</p>";
                    } else {
                        $ids_a_eliminar[] = $registro['id'];
                    }
                }

                // Eliminar los registros duplicados
                if (!empty($ids_a_eliminar)) {
                    $ids_string = implode(',', $ids_a_eliminar);
                    $sql_eliminar = "DELETE FROM envios WHERE id IN ({$ids_string})";

                    if (!$conn->query($sql_eliminar)) {
                        throw new Exception("Error al eliminar duplicados en envios: " . $conn->error);
                    }

                    echo "<p>Eliminados " . count($ids_a_eliminar) . " registros duplicados para pedido {$pedido_codigo}</p>";
                }

                // Registrar el pedido como procesado
                if (!in_array($pedido_codigo, $envios_procesados)) {
                    $envios_procesados[] = $pedido_codigo;
                }
            }

            echo "<p>Total de pedidos procesados en envios: " . count($envios_procesados) . "</p>";
        } else {
            echo "<p>No se encontraron registros duplicados en envios.</p>";
        }
    } else {
        echo "<p>La tabla envios no existe en la base de datos.</p>";
    }

    // PARTE 3: Verificar consistencia entre pedidos_finalizados y envios
    echo "<h2>Verificación de consistencia entre tablas</h2>";

    if ($tabla_envios_existe) {
        try {
            // Obtener información sobre las colaciones de las tablas
            $sql_colacion_pf = "SELECT TABLE_COLLATION FROM information_schema.TABLES
                               WHERE TABLE_SCHEMA = 'tiendalex2' AND TABLE_NAME = 'pedidos_finalizados'";
            $result_colacion_pf = $conn->query($sql_colacion_pf);
            $colacion_pf = $result_colacion_pf->fetch_assoc()['TABLE_COLLATION'];

            $sql_colacion_envios = "SELECT TABLE_COLLATION FROM information_schema.TABLES
                                  WHERE TABLE_SCHEMA = 'tiendalex2' AND TABLE_NAME = 'envios'";
            $result_colacion_envios = $conn->query($sql_colacion_envios);
            $colacion_envios = $result_colacion_envios->fetch_assoc()['TABLE_COLLATION'];

            echo "<p>Colación de pedidos_finalizados: {$colacion_pf}</p>";
            echo "<p>Colación de envios: {$colacion_envios}</p>";

            // Usar consultas preparadas para evitar problemas de colación
            // Primero obtenemos todos los pedidos marcados como enviados en pedidos_finalizados
            $sql_pedidos_enviados = "
                SELECT DISTINCT pedido_codigo, usuario_id
                FROM pedidos_finalizados
                WHERE estado = 'enviado'
            ";

            $result_pedidos_enviados = $conn->query($sql_pedidos_enviados);

            if (!$result_pedidos_enviados) {
                throw new Exception("Error al obtener pedidos enviados: " . $conn->error);
            }

            $total_pedidos = $result_pedidos_enviados->num_rows;
            $inconsistencias = 0;

            if ($total_pedidos > 0) {
                echo "<p>Verificando {$total_pedidos} pedidos marcados como enviados en pedidos_finalizados...</p>";

                while ($pedido = $result_pedidos_enviados->fetch_assoc()) {
                    $pedido_codigo = $pedido['pedido_codigo'];
                    $usuario_id = $pedido['usuario_id'];

                    // Verificar si existe en envios con estado 'enviado'
                    $sql_verificar_envio = "SELECT id FROM envios
                                          WHERE pedido_codigo = ?
                                          AND usuario_codigo = ?
                                          AND estado = 'enviado'
                                          LIMIT 1";

                    $stmt_verificar_envio = $conn->prepare($sql_verificar_envio);
                    $stmt_verificar_envio->bind_param("si", $pedido_codigo, $usuario_id);
                    $stmt_verificar_envio->execute();
                    $result_verificar_envio = $stmt_verificar_envio->get_result();

                    if ($result_verificar_envio->num_rows == 0) {
                        // No existe en envios con estado 'enviado', verificar si existe con otro estado
                        $inconsistencias++;

                        $sql_verificar_otro_estado = "SELECT id, estado FROM envios
                                                    WHERE pedido_codigo = ?
                                                    AND usuario_codigo = ?
                                                    LIMIT 1";

                        $stmt_verificar_otro_estado = $conn->prepare($sql_verificar_otro_estado);
                        $stmt_verificar_otro_estado->bind_param("si", $pedido_codigo, $usuario_id);
                        $stmt_verificar_otro_estado->execute();
                        $result_verificar_otro_estado = $stmt_verificar_otro_estado->get_result();

                        if ($result_verificar_otro_estado->num_rows > 0) {
                            // Existe con otro estado, actualizar
                            $registro = $result_verificar_otro_estado->fetch_assoc();
                            echo "<p>Actualizando registro en envios para pedido {$pedido_codigo} (estado actual: {$registro['estado']} -> enviado)</p>";

                            $sql_actualizar = "UPDATE envios SET estado = 'enviado', fecha_envio = NOW(), updated_at = NOW() WHERE id = ?";
                            $stmt_actualizar = $conn->prepare($sql_actualizar);
                            $stmt_actualizar->bind_param("i", $registro['id']);

                            if (!$stmt_actualizar->execute()) {
                                throw new Exception("Error al actualizar registro en envios: " . $stmt_actualizar->error);
                            }
                        } else {
                            // No existe, crear nuevo registro
                            echo "<p>Creando registro en envios para pedido {$pedido_codigo}</p>";

                            $sql_insertar = "INSERT INTO envios (pedido_codigo, usuario_codigo, estado, fecha_envio) VALUES (?, ?, 'enviado', NOW())";
                            $stmt_insertar = $conn->prepare($sql_insertar);
                            $stmt_insertar->bind_param("si", $pedido_codigo, $usuario_id);

                            if (!$stmt_insertar->execute()) {
                                throw new Exception("Error al insertar registro en envios: " . $stmt_insertar->error);
                            }
                        }
                    }
                }

                if ($inconsistencias > 0) {
                    echo "<p>Se encontraron y corrigieron {$inconsistencias} inconsistencias entre pedidos_finalizados y envios.</p>";
                } else {
                    echo "<p>No se encontraron inconsistencias entre pedidos_finalizados y envios.</p>";
                }
            } else {
                echo "<p>No hay pedidos marcados como enviados en pedidos_finalizados.</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error durante la verificación de consistencia: " . $e->getMessage() . "</p>";
            // Continuamos con el resto del script a pesar del error
        }
    }

    // Confirmar transacción
    $conn->commit();

    echo "<p style='color: green; font-weight: bold;'>Limpieza completada con éxito.</p>";
    echo "<p><a href='/tiendalex2/admin_pedidos.php' class='btn blue'>Volver a Administración de Pedidos</a></p>";

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();

    echo "<p style='color: red; font-weight: bold;'>Error: " . $e->getMessage() . "</p>";
    echo "<p><a href='/tiendalex2/admin_pedidos.php' class='btn blue'>Volver a Administración de Pedidos</a></p>";
}
?>
