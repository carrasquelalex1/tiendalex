<?php
require_once __DIR__ . '/autoload.php';
/**
 * Página de administración de pedidos
 * Permite al administrador visualizar y gestionar los pedidos en diferentes estados
 */

// Incluir el helper de sesiones
require_once __DIR__ . '/helpers/session/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario está logueado y es administrador
if (!esta_logueado() || !es_admin()) {
    header("Location: index.php");
    exit;
}

// Incluir archivos necesarios
require_once __DIR__ . '/backend/config/db.php';

// Título de la página
$titulo_pagina = "Administración de Pedidos";

// Incluir el encabezado
include 'frontend/includes/header.php';

// Función para obtener los pedidos pendientes (en tabla pedido)
function obtenerPedidosPendientes($conn) {
    // Enfoque alternativo para evitar duplicados
    // Primero obtenemos todos los pedidos de ambas tablas
    $sql_pedidos = "
        -- Obtener pedidos de la tabla pedido
        SELECT
            p.pedido as pedido_codigo,
            u.nombre_usuario,
            u.codigo as usuario_id,
            p.fecha_agregado as fecha
        FROM pedido p
        JOIN usuario u ON p.usuario_id = u.codigo
        WHERE p.pedido NOT IN (SELECT DISTINCT pedido_codigo FROM pagos)

        UNION

        -- Obtener pedidos de la tabla pedidos_finalizados
        SELECT
            pf.pedido_codigo,
            u.nombre_usuario,
            u.codigo as usuario_id,
            pf.updated_at as fecha
        FROM pedidos_finalizados pf
        JOIN usuario u ON pf.usuario_id = u.codigo
        WHERE pf.estado = 'pendiente'
        AND pf.pedido_codigo NOT IN (SELECT DISTINCT pedido_codigo FROM pagos)
    ";

    $result = $conn->query($sql_pedidos);
    $pedidos_temp = [];

    // Agrupar por pedido_codigo para eliminar duplicados
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $codigo = $row['pedido_codigo'];

            // Si el pedido ya existe en el array, actualizar solo si la fecha es más reciente
            if (isset($pedidos_temp[$codigo])) {
                if (strtotime($row['fecha']) > strtotime($pedidos_temp[$codigo]['fecha'])) {
                    $pedidos_temp[$codigo] = $row;
                }
            } else {
                // Si no existe, agregarlo
                $pedidos_temp[$codigo] = $row;
            }
        }
    }

    // Convertir el array asociativo a un array indexado y ordenar por fecha
    $pedidos = array_values($pedidos_temp);

    // Ordenar por fecha (más reciente primero)
    usort($pedidos, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });

    return $pedidos;
}

// Función para obtener los pedidos con pago informado (pendientes de verificación)
function obtenerPedidosInformados($conn) {
    $sql = "SELECT DISTINCT p.pedido_codigo, u.nombre_usuario, u.codigo as usuario_id,
            MAX(p.fecha_registro) as fecha, p.estado
            FROM pagos p
            JOIN usuario u ON p.usuario_codigo = u.codigo
            WHERE p.estado = 'pendiente'
            GROUP BY p.pedido_codigo, u.nombre_usuario, u.codigo, p.estado
            ORDER BY fecha DESC";

    $result = $conn->query($sql);
    $pedidos = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pedidos[] = $row;
        }
    }

    return $pedidos;
}

// Función para obtener los pedidos verificados (en proceso de empaque/envío)
function obtenerPedidosVerificados($conn) {
    // Verificar si la tabla envios existe
    $tabla_envios_existe = false;
    $sql_check_table = "SHOW TABLES LIKE 'envios'";
    $result_check = $conn->query($sql_check_table);
    if ($result_check && $result_check->num_rows > 0) {
        $tabla_envios_existe = true;
    }

    if ($tabla_envios_existe) {
        // Consulta que tiene en cuenta la tabla envios
        // Usamos COLLATE para asegurar que todas las comparaciones usen la misma colación
        $sql = "SELECT DISTINCT p.pedido_codigo COLLATE utf8mb4_general_ci as pedido_codigo,
                u.nombre_usuario,
                u.codigo as usuario_id,
                MAX(p.fecha_pago) as fecha,
                p.estado
                FROM pagos p
                JOIN usuario u ON p.usuario_codigo = u.codigo
                WHERE p.estado = 'verificado'
                AND p.pedido_codigo NOT IN (
                    SELECT DISTINCT pedido_codigo COLLATE utf8mb4_general_ci FROM pedidos_finalizados WHERE estado = 'enviado'
                )
                AND (p.pedido_codigo NOT IN (
                    SELECT DISTINCT pedido_codigo COLLATE utf8mb4_general_ci FROM envios WHERE estado = 'enviado'
                ) OR p.pedido_codigo IN (
                    SELECT DISTINCT pedido_codigo COLLATE utf8mb4_general_ci FROM envios WHERE estado = 'pendiente' OR estado = 'en_proceso'
                ))
                GROUP BY p.pedido_codigo, u.nombre_usuario, u.codigo, p.estado
                ORDER BY fecha DESC";
    } else {
        // Consulta original si la tabla envios no existe
        $sql = "SELECT DISTINCT p.pedido_codigo, u.nombre_usuario, u.codigo as usuario_id,
                MAX(p.fecha_pago) as fecha, p.estado
                FROM pagos p
                JOIN usuario u ON p.usuario_codigo = u.codigo
                WHERE p.estado = 'verificado'
                AND p.pedido_codigo NOT IN (
                    SELECT DISTINCT pedido_codigo FROM pedidos_finalizados WHERE estado = 'enviado'
                )
                GROUP BY p.pedido_codigo, u.nombre_usuario, u.codigo, p.estado
                ORDER BY fecha DESC";
    }

    $result = $conn->query($sql);
    $pedidos = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pedidos[] = $row;
        }
    }

    return $pedidos;
}

// Función para obtener los pedidos enviados
function obtenerPedidosEnviados($conn) {
    // Verificar si la tabla envios existe
    $tabla_envios_existe = false;
    $sql_check_table = "SHOW TABLES LIKE 'envios'";
    $result_check = $conn->query($sql_check_table);
    if ($result_check && $result_check->num_rows > 0) {
        $tabla_envios_existe = true;
    }

    if ($tabla_envios_existe) {
        // Enfoque alternativo para evitar problemas de colación
        // En lugar de usar UNION, obtendremos los resultados por separado y los combinaremos en PHP

        // Obtener pedidos enviados de pedidos_finalizados
        $sql_pf = "SELECT DISTINCT
                    pf.pedido_codigo as pedido_codigo,
                    u.nombre_usuario as nombre_usuario,
                    u.codigo as usuario_id,
                    MAX(pf.updated_at) as fecha,
                    pf.estado as estado
                  FROM pedidos_finalizados pf
                  JOIN usuario u ON pf.usuario_id = u.codigo
                  WHERE pf.estado = 'enviado'
                  GROUP BY pf.pedido_codigo, u.nombre_usuario, u.codigo, pf.estado";

        $result_pf = $conn->query($sql_pf);

        // Obtener pedidos enviados de envios
        $sql_envios = "SELECT DISTINCT
                        e.pedido_codigo as pedido_codigo,
                        u.nombre_usuario as nombre_usuario,
                        u.codigo as usuario_id,
                        MAX(e.updated_at) as fecha,
                        e.estado as estado
                      FROM envios e
                      JOIN usuario u ON e.usuario_codigo = u.codigo
                      WHERE e.estado = 'enviado'
                      GROUP BY e.pedido_codigo, u.nombre_usuario, u.codigo, e.estado";

        $result_envios = $conn->query($sql_envios);

        // Inicializar array para almacenar todos los resultados
        $pedidos_temp = [];

        // Procesar resultados de pedidos_finalizados
        if ($result_pf && $result_pf->num_rows > 0) {
            while ($row = $result_pf->fetch_assoc()) {
                $codigo = $row['pedido_codigo'];
                $pedidos_temp[$codigo] = $row;
            }
        }

        // Procesar resultados de envios
        if ($result_envios && $result_envios->num_rows > 0) {
            while ($row = $result_envios->fetch_assoc()) {
                $codigo = $row['pedido_codigo'];

                // Si el pedido ya existe en el array, actualizar solo si la fecha es más reciente
                if (isset($pedidos_temp[$codigo])) {
                    if (strtotime($row['fecha']) > strtotime($pedidos_temp[$codigo]['fecha'])) {
                        $pedidos_temp[$codigo] = $row;
                    }
                } else {
                    // Si no existe, agregarlo
                    $pedidos_temp[$codigo] = $row;
                }
            }
        }

        // Convertir el array asociativo a un array indexado
        $pedidos = array_values($pedidos_temp);

        // Ordenar por fecha (más reciente primero)
        usort($pedidos, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });

        return $pedidos;
    } else {
        // Consulta original si la tabla envios no existe
        $sql = "SELECT DISTINCT pf.pedido_codigo, u.nombre_usuario, u.codigo as usuario_id,
                MAX(pf.updated_at) as fecha, pf.estado
                FROM pedidos_finalizados pf
                JOIN usuario u ON pf.usuario_id = u.codigo
                WHERE pf.estado = 'enviado'
                GROUP BY pf.pedido_codigo, u.nombre_usuario, u.codigo, pf.estado
                ORDER BY fecha DESC";
    }

    $result = $conn->query($sql);
    $pedidos_temp = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $codigo = $row['pedido_codigo'];

            // Si el pedido ya existe en el array, actualizar solo si la fecha es más reciente
            if (isset($pedidos_temp[$codigo])) {
                if (strtotime($row['fecha']) > strtotime($pedidos_temp[$codigo]['fecha'])) {
                    $pedidos_temp[$codigo] = $row;
                }
            } else {
                // Si no existe, agregarlo
                $pedidos_temp[$codigo] = $row;
            }
        }
    }

    // Convertir el array asociativo a un array indexado y ordenar por fecha
    $pedidos = array_values($pedidos_temp);

    // Ordenar por fecha (más reciente primero)
    usort($pedidos, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });

    return $pedidos;
}

// Obtener los pedidos en diferentes estados
$pedidosPendientes = obtenerPedidosPendientes($conn);
$pedidosInformados = obtenerPedidosInformados($conn);
$pedidosVerificados = obtenerPedidosVerificados($conn);
$pedidosEnviados = obtenerPedidosEnviados($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Pedidos - TiendAlex</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <style>
        .page-title {
            margin-top: 20px;
            margin-bottom: 30px;
            color: #1565C0;
            font-weight: 500;
        }

        .admin-section {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            height: 100%;
        }

        .section-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
        }

        .pedido-card {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .pedido-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .pedido-codigo {
            font-weight: 500;
            color: #1976D2;
        }

        .pedido-cliente {
            font-weight: 500;
        }

        .pedido-fecha {
            font-size: 12px;
            color: #757575;
        }

        .pedido-actions {
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
        }

        .pedido-actions a {
            margin-left: 10px;
        }

        .pendientes-section {
            background-color: #fff8e1;
        }

        .informados-section {
            background-color: #e1f5fe;
        }

        .verificados-section {
            background-color: #e8f5e9;
        }

        .enviados-section {
            background-color: #fff3e0;
        }

        .empty-message {
            text-align: center;
            padding: 20px;
            color: #757575;
            font-style: italic;
        }

        .btn-small {
            padding: 0 12px;
            height: 32px;
            line-height: 32px;
            font-weight: 500;
            text-transform: none;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.16);
        }

        .btn-small i.left {
            margin-right: 6px;
        }

        .chip {
            font-weight: 500;
            height: 28px;
            line-height: 28px;
            padding: 0 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .chip i.tiny {
            margin-right: 5px;
            font-size: 16px;
            line-height: 28px;
            vertical-align: middle;
        }

        /* Estilos para la visualización de productos */
        .producto-item {
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .producto-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .producto-imagen {
            max-height: 120px;
            width: auto;
            max-width: 100%;
            border-radius: 4px;
            object-fit: contain;
            border: 1px solid #e0e0e0;
            padding: 5px;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .producto-nombre {
            font-weight: 600;
            color: #1565C0;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .producto-descripcion {
            font-size: 0.9rem;
            color: #616161;
            margin-bottom: 15px;
            line-height: 1.4;
            max-height: 60px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        /* Estilos para el modal grande */
        .modal.modal-large {
            width: 90%;
            max-width: 1200px;
            max-height: 90%;
            height: 90%;
        }

        .modal.modal-large .modal-content {
            padding: 24px;
            overflow-y: auto;
        }

        @media only screen and (max-width: 992px) {
            .modal.modal-large {
                width: 95%;
                height: 95%;
            }
        }

        .badge-count {
            display: inline-block;
            min-width: 24px;
            height: 24px;
            line-height: 24px;
            border-radius: 12px;
            text-align: center;
            background-color: #1976D2;
            color: white;
            font-size: 12px;
            margin-left: 8px;
            padding: 0 6px;
        }
    </style>
</head>
<body>
    <main class="container" style="margin-top: 100px; padding-top: 0;">
        <h4 class="page-title center-align" style="margin-top: 0; margin-bottom: 40px; font-weight: bold; color: #1565C0; padding-top: 20px;">Administración de Pedidos</h4>

        <div class="row" style="margin-bottom: 20px;">
            <div class="col s12 right-align">
                <a href="backend/admin/limpiar_duplicados.php" target="_blank" class="waves-effect waves-light btn blue darken-1">
                    <i class="material-icons left">cleaning_services</i>
                    Limpiar Duplicados
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Sección 1: Pedidos Pendientes -->
            <div class="col s12 m3">
                <div class="admin-section pendientes-section">
                    <div class="section-title">
                        <i class="material-icons">shopping_cart</i>
                        Pedidos Pendientes
                        <span class="badge-count"><?php echo count($pedidosPendientes); ?></span>
                    </div>

                    <?php if (empty($pedidosPendientes)): ?>
                        <div class="empty-message">No hay pedidos pendientes</div>
                    <?php else: ?>
                        <?php foreach ($pedidosPendientes as $pedido): ?>
                            <div class="pedido-card white">
                                <div class="pedido-header">
                                    <div class="pedido-codigo"><?php echo $pedido['pedido_codigo']; ?></div>
                                    <div class="pedido-fecha">
                                        <?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?>
                                    </div>
                                </div>
                                <div class="pedido-cliente">
                                    <i class="material-icons tiny">person</i>
                                    <?php echo $pedido['nombre_usuario']; ?>
                                </div>
                                <div class="pedido-actions" style="text-align: right;">
                                    <a href="javascript:void(0)" onclick="verDetallesPedido('<?php echo $pedido['pedido_codigo']; ?>', <?php echo $pedido['usuario_id']; ?>, 'pendiente')" class="btn-small blue waves-effect waves-light">
                                        <i class="material-icons left tiny">visibility</i>
                                        Ver detalles
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sección 2: Pedidos con Pago Informado -->
            <div class="col s12 m3">
                <div class="admin-section informados-section">
                    <div class="section-title">
                        <i class="material-icons">payment</i>
                        Pagos Informados
                        <span class="badge-count"><?php echo count($pedidosInformados); ?></span>
                    </div>

                    <?php if (empty($pedidosInformados)): ?>
                        <div class="empty-message">No hay pagos informados pendientes</div>
                    <?php else: ?>
                        <?php foreach ($pedidosInformados as $pedido): ?>
                            <div class="pedido-card white">
                                <div class="pedido-header">
                                    <div class="pedido-codigo"><?php echo $pedido['pedido_codigo']; ?></div>
                                    <div class="pedido-fecha">
                                        <?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?>
                                    </div>
                                </div>
                                <div class="pedido-cliente">
                                    <i class="material-icons tiny">person</i>
                                    <?php echo $pedido['nombre_usuario']; ?>
                                </div>
                                <div class="pedido-actions">
                                    <a href="javascript:void(0)" onclick="verDetallesPago('<?php echo $pedido['pedido_codigo']; ?>', <?php echo $pedido['usuario_id']; ?>)" class="btn-small blue waves-effect waves-light">
                                        <i class="material-icons left tiny">visibility</i>
                                        Ver pago
                                    </a>
                                </div>
                                <div style="margin-top: 10px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 5px;">
                                    <a href="javascript:void(0)" onclick="rechazarPago('<?php echo $pedido['pedido_codigo']; ?>', <?php echo $pedido['usuario_id']; ?>)" class="btn-small red waves-effect waves-light">
                                        <i class="material-icons left tiny">cancel</i>
                                        Rechazar
                                    </a>
                                    <a href="javascript:void(0)" onclick="devolverPedido('<?php echo $pedido['pedido_codigo']; ?>', <?php echo $pedido['usuario_id']; ?>)" class="btn-small amber darken-2 waves-effect waves-light">
                                        <i class="material-icons left tiny">undo</i>
                                        Devolver
                                    </a>
                                    <a href="javascript:void(0)" onclick="revertirCompra('<?php echo $pedido['pedido_codigo']; ?>', <?php echo $pedido['usuario_id']; ?>)" class="btn-small orange waves-effect waves-light">
                                        <i class="material-icons left tiny">replay</i>
                                        Revertir
                                    </a>
                                    <a href="javascript:void(0)" onclick="confirmarPago('<?php echo $pedido['pedido_codigo']; ?>', <?php echo $pedido['usuario_id']; ?>)" class="btn-small green waves-effect waves-light">
                                        <i class="material-icons left tiny">check</i>
                                        Confirmar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sección 3: Pedidos Verificados -->
            <div class="col s12 m3">
                <div class="admin-section verificados-section">
                    <div class="section-title">
                        <i class="material-icons">local_shipping</i>
                        En Proceso de Envío
                        <span class="badge-count"><?php echo count($pedidosVerificados); ?></span>
                    </div>

                    <?php if (empty($pedidosVerificados)): ?>
                        <div class="empty-message">No hay pedidos en proceso de envío</div>
                    <?php else: ?>
                        <?php foreach ($pedidosVerificados as $pedido): ?>
                            <div class="pedido-card white">
                                <div class="pedido-header">
                                    <div class="pedido-codigo"><?php echo $pedido['pedido_codigo']; ?></div>
                                    <div class="pedido-fecha">
                                        <?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?>
                                    </div>
                                </div>
                                <div class="pedido-cliente">
                                    <i class="material-icons tiny">person</i>
                                    <?php echo $pedido['nombre_usuario']; ?>
                                </div>
                                <div class="pedido-actions">
                                    <a href="javascript:void(0)" onclick="verDetallesPago('<?php echo $pedido['pedido_codigo']; ?>', <?php echo $pedido['usuario_id']; ?>)" class="btn-small blue waves-effect waves-light">
                                        <i class="material-icons left tiny">visibility</i>
                                        Ver detalles
                                    </a>
                                </div>
                                <div style="margin-top: 10px; text-align: right;">
                                    <a href="javascript:void(0)" onclick="marcarEnviado('<?php echo $pedido['pedido_codigo']; ?>', <?php echo $pedido['usuario_id']; ?>)" class="btn-small orange waves-effect waves-light">
                                        <i class="material-icons left tiny">local_shipping</i>
                                        Marcar como enviado
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sección 4: Pedidos Enviados -->
            <div class="col s12 m3">
                <div class="admin-section enviados-section">
                    <div class="section-title">
                        <i class="material-icons">check_circle</i>
                        Pedidos Enviados
                        <span class="badge-count"><?php echo count($pedidosEnviados); ?></span>
                    </div>

                    <?php if (empty($pedidosEnviados)): ?>
                        <div class="empty-message">No hay pedidos enviados</div>
                    <?php else: ?>
                        <?php foreach ($pedidosEnviados as $pedido): ?>
                            <div class="pedido-card white">
                                <div class="pedido-header">
                                    <div class="pedido-codigo"><?php echo $pedido['pedido_codigo']; ?></div>
                                    <div class="pedido-fecha">
                                        <?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?>
                                    </div>
                                </div>
                                <div class="pedido-cliente">
                                    <i class="material-icons tiny">person</i>
                                    <?php echo $pedido['nombre_usuario']; ?>
                                </div>
                                <div class="pedido-actions">
                                    <a href="javascript:void(0)" onclick="verDetallesPago('<?php echo $pedido['pedido_codigo']; ?>', <?php echo $pedido['usuario_id']; ?>)" class="btn-small blue waves-effect waves-light">
                                        <i class="material-icons left tiny">visibility</i>
                                        Ver detalles
                                    </a>
                                </div>
                                <div style="margin-top: 10px; text-align: right;">
                                    <span class="chip green white-text" style="margin-right: 0;">
                                        <i class="material-icons tiny">check_circle</i>
                                        Enviado
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal para ver detalles del pedido -->
    <div id="modalDetallesPedido" class="modal modal-fixed-footer">
        <div class="modal-content">
            <h4>Detalles del Pedido <span id="pedidoCodigoDetalle"></span></h4>
            <div id="detallesPedidoContent" class="center-align">
                <div class="preloader-wrapper big active">
                    <div class="spinner-layer spinner-blue-only">
                        <div class="circle-clipper left">
                            <div class="circle"></div>
                        </div>
                        <div class="gap-patch">
                            <div class="circle"></div>
                        </div>
                        <div class="circle-clipper right">
                            <div class="circle"></div>
                        </div>
                    </div>
                </div>
                <p>Cargando detalles...</p>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cerrar</a>
        </div>
    </div>

    <!-- Modal para ver detalles del pago -->
    <div id="modalDetallesPago" class="modal modal-fixed-footer modal-large">
        <div class="modal-content">
            <h4 class="blue-text">Detalles del Pedido <span id="pagoCodigoDetalle" class="chip blue white-text"></span></h4>
            <div id="detallesPagoContent" class="center-align">
                <div class="preloader-wrapper big active">
                    <div class="spinner-layer spinner-blue-only">
                        <div class="circle-clipper left">
                            <div class="circle"></div>
                        </div>
                        <div class="gap-patch">
                            <div class="circle"></div>
                        </div>
                        <div class="circle-clipper right">
                            <div class="circle"></div>
                        </div>
                    </div>
                </div>
                <p>Cargando detalles...</p>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-light btn-small blue">Cerrar</a>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div id="modalConfirmacion" class="modal">
        <div class="modal-content">
            <h4>Confirmar Acción</h4>
            <p id="mensajeConfirmacion">¿Estás seguro de que deseas realizar esta acción?</p>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
            <a href="#!" id="btnConfirmarAccion" class="waves-effect waves-green btn-flat">Confirmar</a>
        </div>
    </div>

    <!-- Modal para rechazar pago -->
    <div id="modalRechazarPago" class="modal">
        <div class="modal-content">
            <h4>Rechazar Pago</h4>
            <p>Por favor, indique el motivo por el cual se rechaza el pago:</p>
            <div class="input-field">
                <textarea id="motivoRechazo" class="materialize-textarea"></textarea>
                <label for="motivoRechazo">Motivo del rechazo</label>
            </div>
            <p class="red-text">Esta acción notificará al cliente que los datos del pago están incorrectos.</p>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
            <a href="#!" id="btnConfirmarRechazo" class="waves-effect waves-light btn-small red">Rechazar Pago</a>
        </div>
    </div>

    <!-- Modal para devolver pedido a pendientes -->
    <div id="modalDevolverPedido" class="modal">
        <div class="modal-content">
            <h4>Devolver Pedido a Pendientes</h4>
            <p>Esta acción devolverá el pedido a la sección de "Pedidos Pendientes", dando al cliente una segunda oportunidad para verificar sus datos de pago.</p>
            <div class="input-field">
                <textarea id="mensajeCliente" class="materialize-textarea"></textarea>
                <label for="mensajeCliente">Mensaje para el cliente</label>
            </div>
            <p class="amber-text text-darken-2">
                <i class="material-icons tiny">warning</i>
                <strong>Importante:</strong> Indique al cliente que si los datos de pago son incorrectos nuevamente,
                el pedido será cancelado y los productos serán reintegrados al catálogo.
            </p>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancelar</a>
            <a href="#!" id="btnConfirmarDevolucion" class="waves-effect waves-light btn-small amber darken-2">Devolver a Pendientes</a>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Materialize JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <!-- JavaScript personalizado -->
    <script src="js/scripts.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar modales
            var modals = document.querySelectorAll('.modal');
            M.Modal.init(modals, {
                dismissible: true,
                opacity: 0.5,
                inDuration: 300,
                outDuration: 200,
                startingTop: '4%',
                endingTop: '5%'
            });

            // Inicializar tooltips
            var tooltips = document.querySelectorAll('.tooltipped');
            M.Tooltip.init(tooltips);
        });

        // Ver detalles del pedido
        function verDetallesPedido(pedidoCodigo, usuarioId, estado) {
            // Mostrar el modal
            var modal = M.Modal.getInstance(document.getElementById('modalDetallesPedido'));
            modal.open();

            // Actualizar el código del pedido en el modal
            document.getElementById('pedidoCodigoDetalle').textContent = pedidoCodigo;

            // Cargar los detalles del pedido mediante AJAX
            $.ajax({
                url: 'backend/admin/obtener_detalles_pedido.php',
                type: 'POST',
                data: {
                    pedido_codigo: pedidoCodigo,
                    usuario_id: usuarioId,
                    estado: estado
                },
                success: function(response) {
                    document.getElementById('detallesPedidoContent').innerHTML = response;
                },
                error: function() {
                    document.getElementById('detallesPedidoContent').innerHTML = '<p class="red-text">Error al cargar los detalles del pedido.</p>';
                }
            });
        }

        // Ver detalles del pago
        function verDetallesPago(pedidoCodigo, usuarioId) {
            // Mostrar el modal
            var modal = M.Modal.getInstance(document.getElementById('modalDetallesPago'));
            modal.open();

            // Actualizar el código del pedido en el modal
            document.getElementById('pagoCodigoDetalle').textContent = pedidoCodigo;

            // Cargar los detalles del pago mediante AJAX
            $.ajax({
                url: 'backend/admin/obtener_detalles_pago.php',
                type: 'POST',
                data: {
                    pedido_codigo: pedidoCodigo,
                    usuario_id: usuarioId
                },
                success: function(response) {
                    document.getElementById('detallesPagoContent').innerHTML = response;
                },
                error: function() {
                    document.getElementById('detallesPagoContent').innerHTML = '<p class="red-text">Error al cargar los detalles del pago.</p>';
                }
            });
        }

        // Confirmar pago
        function confirmarPago(pedidoCodigo, usuarioId) {
            // Mostrar el modal de confirmación
            var modal = M.Modal.getInstance(document.getElementById('modalConfirmacion'));
            document.getElementById('mensajeConfirmacion').textContent = '¿Estás seguro de que deseas confirmar el pago del pedido ' + pedidoCodigo + '?';

            // Configurar el botón de confirmación
            document.getElementById('btnConfirmarAccion').onclick = function() {
                // Realizar la acción mediante AJAX
                $.ajax({
                    url: 'backend/admin/confirmar_pago.php',
                    type: 'POST',
                    data: {
                        pedido_codigo: pedidoCodigo,
                        usuario_id: usuarioId
                    },
                    success: function(response) {
                        try {
                            var result = JSON.parse(response);
                            if (result.success) {
                                M.toast({html: result.message, classes: 'green'});
                                // Recargar la página después de un breve retraso
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                M.toast({html: result.message, classes: 'red'});
                            }
                        } catch (e) {
                            M.toast({html: 'Error al procesar la respuesta', classes: 'red'});
                        }
                    },
                    error: function() {
                        M.toast({html: 'Error al confirmar el pago', classes: 'red'});
                    }
                });

                // Cerrar el modal
                modal.close();
            };

            modal.open();
        }

        // Marcar como enviado
        function marcarEnviado(pedidoCodigo, usuarioId) {
            // Mostrar el modal de confirmación
            var modal = M.Modal.getInstance(document.getElementById('modalConfirmacion'));
            document.getElementById('mensajeConfirmacion').textContent = '¿Estás seguro de que deseas marcar como enviado el pedido ' + pedidoCodigo + '?';

            // Configurar el botón de confirmación
            document.getElementById('btnConfirmarAccion').onclick = function() {
                // Realizar la acción mediante AJAX
                $.ajax({
                    url: 'backend/admin/marcar_enviado.php',
                    type: 'POST',
                    data: {
                        pedido_codigo: pedidoCodigo,
                        usuario_id: usuarioId
                    },
                    success: function(response) {
                        try {
                            var result = JSON.parse(response);
                            if (result.success) {
                                M.toast({html: result.message, classes: 'green'});
                                // Recargar la página después de un breve retraso
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                M.toast({html: result.message, classes: 'red'});
                            }
                        } catch (e) {
                            M.toast({html: 'Error al procesar la respuesta', classes: 'red'});
                        }
                    },
                    error: function() {
                        M.toast({html: 'Error al marcar como enviado', classes: 'red'});
                    }
                });

                // Cerrar el modal
                modal.close();
            };

            modal.open();
        }

        // Rechazar pago
        function rechazarPago(pedidoCodigo, usuarioId) {
            // Variables globales para usar en el callback
            window.pedidoCodigoActual = pedidoCodigo;
            window.usuarioIdActual = usuarioId;

            // Mostrar el modal de rechazo
            var modal = M.Modal.getInstance(document.getElementById('modalRechazarPago'));

            // Limpiar el campo de motivo
            document.getElementById('motivoRechazo').value = '';
            M.textareaAutoResize(document.getElementById('motivoRechazo'));

            // Configurar el botón de confirmación
            document.getElementById('btnConfirmarRechazo').onclick = function() {
                var motivoRechazo = document.getElementById('motivoRechazo').value.trim();

                if (motivoRechazo === '') {
                    M.toast({html: 'Por favor, indique el motivo del rechazo', classes: 'red'});
                    return;
                }

                // Realizar la acción mediante AJAX
                $.ajax({
                    url: '/tiendalex2/backend/admin/rechazar_pago.php',
                    type: 'POST',
                    data: {
                        pedido_codigo: window.pedidoCodigoActual,
                        usuario_id: window.usuarioIdActual,
                        motivo_rechazo: motivoRechazo
                    },
                    success: function(response) {
                        try {
                            var result = JSON.parse(response);
                            if (result.success) {
                                M.toast({html: result.message, classes: 'green'});
                                // Recargar la página después de un breve retraso
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                M.toast({html: result.message, classes: 'red'});
                            }
                        } catch (e) {
                            M.toast({html: 'Error al procesar la respuesta', classes: 'red'});
                        }
                    },
                    error: function() {
                        M.toast({html: 'Error al rechazar el pago', classes: 'red'});
                    }
                });

                // Cerrar el modal
                modal.close();
            };

            modal.open();
        }

        // Devolver pedido a pendientes
        function devolverPedido(pedidoCodigo, usuarioId) {
            // Variables globales para usar en el callback
            window.pedidoCodigoActual = pedidoCodigo;
            window.usuarioIdActual = usuarioId;

            // Mostrar el modal de devolución
            var modal = M.Modal.getInstance(document.getElementById('modalDevolverPedido'));

            // Establecer un mensaje predeterminado
            var mensajePredeterminado = "Estimado cliente, hemos detectado posibles inconsistencias en los datos de pago proporcionados. " +
                "Por favor, verifique y vuelva a informar su pago correctamente. " +
                "Si los datos son incorrectos nuevamente, el pedido será cancelado y los productos serán reintegrados al catálogo.";

            document.getElementById('mensajeCliente').value = mensajePredeterminado;
            M.textareaAutoResize(document.getElementById('mensajeCliente'));
            M.updateTextFields(); // Actualizar los campos para que Materialize reconozca el contenido

            // Configurar el botón de confirmación
            document.getElementById('btnConfirmarDevolucion').onclick = function() {
                var mensajeCliente = document.getElementById('mensajeCliente').value.trim();

                if (mensajeCliente === '') {
                    M.toast({html: 'Por favor, indique un mensaje para el cliente', classes: 'red'});
                    return;
                }

                // Mostrar mensaje de depuración
                console.log('Enviando solicitud para devolver pedido:', {
                    pedido_codigo: window.pedidoCodigoActual,
                    usuario_id: window.usuarioIdActual,
                    mensaje_cliente: mensajeCliente
                });

                // Mostrar indicador de carga
                M.toast({html: '<i class="material-icons left">hourglass_empty</i> Procesando solicitud...', classes: 'blue', displayLength: 3000});

                // Realizar la acción mediante AJAX
                $.ajax({
                    url: 'backend/admin/devolver_pedido.php',
                    type: 'POST',
                    data: {
                        pedido_codigo: window.pedidoCodigoActual,
                        usuario_id: window.usuarioIdActual,
                        mensaje_cliente: mensajeCliente
                    },
                    success: function(response) {
                        console.log('Respuesta recibida:', response);
                        try {
                            var result = JSON.parse(response);
                            console.log('Respuesta parseada:', result);
                            if (result.success) {
                                M.toast({html: result.message, classes: 'green'});
                                // Recargar la página después de un breve retraso
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                M.toast({html: result.message, classes: 'red'});
                            }
                        } catch (e) {
                            console.error('Error al parsear la respuesta:', e);
                            console.log('Respuesta original:', response);
                            M.toast({html: 'Error al procesar la respuesta', classes: 'red'});
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error en la solicitud AJAX:', {xhr: xhr, status: status, error: error});
                        M.toast({html: 'Error al devolver el pedido: ' + error, classes: 'red'});
                    }
                });

                // Cerrar el modal
                modal.close();
            };

            modal.open();
        }

        // Revertir compra
        function revertirCompra(pedidoCodigo, usuarioId) {
            // Mostrar el modal de confirmación
            var modal = M.Modal.getInstance(document.getElementById('modalConfirmacion'));
            document.getElementById('mensajeConfirmacion').innerHTML =
                '¿Estás seguro de que deseas revertir la compra <strong>' + pedidoCodigo + '</strong>?<br><br>' +
                '<span class="red-text">Esta acción eliminará el pedido y reintegrará los productos al catálogo.</span>';

            // Configurar el botón de confirmación
            document.getElementById('btnConfirmarAccion').onclick = function() {
                // Realizar la acción mediante AJAX
                $.ajax({
                    url: 'backend/admin/revertir_compra.php',
                    type: 'POST',
                    data: {
                        pedido_codigo: pedidoCodigo,
                        usuario_id: usuarioId
                    },
                    success: function(response) {
                        try {
                            var result = JSON.parse(response);
                            if (result.success) {
                                M.toast({html: result.message, classes: 'green'});
                                // Recargar la página después de un breve retraso
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                M.toast({html: result.message, classes: 'red'});
                            }
                        } catch (e) {
                            M.toast({html: 'Error al procesar la respuesta', classes: 'red'});
                        }
                    },
                    error: function() {
                        M.toast({html: 'Error al revertir la compra', classes: 'red'});
                    }
                });

                // Cerrar el modal
                modal.close();
            };

            modal.open();
        }
    </script>
</body>
</html>
