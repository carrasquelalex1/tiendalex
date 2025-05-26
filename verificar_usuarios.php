<?php
require_once __DIR__ . '/autoload.php';
/**
 * Script para verificar la estructura de la tabla de usuarios
 *
 * Este script muestra información detallada sobre la tabla de usuarios
 * y los roles asignados a cada usuario.
 */

// Incluir archivos necesarios
require_once 'backend/config/db.php';
require_once 'backend/session_helper.php';

// Iniciar sesión de manera segura
iniciar_sesion_segura();

// Verificar si el usuario es administrador
if (!es_admin()) {
    header("Location: index.php?error=1&message=" . urlencode("Acceso denegado. Solo los administradores pueden acceder a esta página."));
    exit;
}

// Función para obtener todos los usuarios
function obtener_usuarios() {
    global $conn;

    $sql = "SELECT u.*, r.nombre_rol
            FROM usuario u
            LEFT JOIN rol r ON u.rol_codigo = r.codigo
            ORDER BY u.codigo";
    $result = $conn->query($sql);

    $usuarios = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }

    return $usuarios;
}

// Función para obtener todos los roles
function obtener_roles() {
    global $conn;

    $sql = "SELECT * FROM rol ORDER BY codigo";
    $result = $conn->query($sql);

    $roles = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
    }

    return $roles;
}

// Función para obtener la estructura de la tabla
function obtener_estructura_tabla($tabla) {
    global $conn;

    $sql = "DESCRIBE $tabla";
    $result = $conn->query($sql);

    $estructura = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $estructura[] = $row;
        }
    }

    return $estructura;
}

// Obtener datos
$usuarios = obtener_usuarios();
$roles = obtener_roles();
$estructura_usuario = obtener_estructura_tabla('usuario');
$estructura_rol = obtener_estructura_tabla('rol');

// Verificar si se solicita actualizar el rol de un usuario
if (isset($_POST['actualizar_rol'])) {
    $usuario_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
    $nuevo_rol = isset($_POST['nuevo_rol']) ? intval($_POST['nuevo_rol']) : 0;

    if ($usuario_id > 0 && $nuevo_rol > 0) {
        $sql = "UPDATE usuario SET rol_codigo = ? WHERE codigo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $nuevo_rol, $usuario_id);

        if ($stmt->execute()) {
            header("Location: verificar_usuarios.php?success=1&message=" . urlencode("Rol actualizado correctamente."));
            exit;
        } else {
            header("Location: verificar_usuarios.php?error=1&message=" . urlencode("Error al actualizar el rol: " . $stmt->error));
            exit;
        }
    } else {
        header("Location: verificar_usuarios.php?error=1&message=" . urlencode("Datos inválidos para actualizar el rol."));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Usuarios</title>
    <?php include 'frontend/includes/css_includes.php'; ?>
    <style>
        /* Usamos los estilos generales definidos en tiendalex.css */
        .container.main-container {
            margin-top: 30px;
            padding-top: 30px;
        }
        .table-container {
            overflow-x: auto;
        }
        .action-buttons {
            margin-top: 20px;
        }
        .action-buttons a {
            margin-right: 10px;
        }
        /* Estilos específicos para esta página */
        h2, h4 {
            margin-top: 20px;
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        /* Ajuste para el encabezado */
        body {
            padding-top: var(--header-height);
        }
    </style>
</head>
<body class="verificar-usuarios">
    <?php include 'frontend/includes/header.php'; ?>

    <div class="container main-container">
        <div class="card">
            <h2>Verificación de Usuarios y Roles</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="card-panel green lighten-4">
                    <span class="green-text text-darken-4"><?php echo urldecode($_GET['message']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="card-panel red lighten-4">
                    <span class="red-text text-darken-4"><?php echo urldecode($_GET['message']); ?></span>
                </div>
            <?php endif; ?>

            <h4>Estructura de la Tabla Usuario</h4>
            <div class="table-container">
                <table class="striped">
                    <thead>
                        <tr>
                            <th>Campo</th>
                            <th>Tipo</th>
                            <th>Nulo</th>
                            <th>Clave</th>
                            <th>Predeterminado</th>
                            <th>Extra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estructura_usuario as $campo): ?>
                            <tr>
                                <td><?php echo $campo['Field']; ?></td>
                                <td><?php echo $campo['Type']; ?></td>
                                <td><?php echo $campo['Null']; ?></td>
                                <td><?php echo $campo['Key']; ?></td>
                                <td><?php echo $campo['Default']; ?></td>
                                <td><?php echo $campo['Extra']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h4>Estructura de la Tabla Rol</h4>
            <div class="table-container">
                <table class="striped">
                    <thead>
                        <tr>
                            <th>Campo</th>
                            <th>Tipo</th>
                            <th>Nulo</th>
                            <th>Clave</th>
                            <th>Predeterminado</th>
                            <th>Extra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estructura_rol as $campo): ?>
                            <tr>
                                <td><?php echo $campo['Field']; ?></td>
                                <td><?php echo $campo['Type']; ?></td>
                                <td><?php echo $campo['Null']; ?></td>
                                <td><?php echo $campo['Key']; ?></td>
                                <td><?php echo $campo['Default']; ?></td>
                                <td><?php echo $campo['Extra']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h4>Roles Disponibles</h4>
            <div class="table-container">
                <table class="striped">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $rol): ?>
                            <tr>
                                <td><?php echo $rol['codigo']; ?></td>
                                <td><?php echo $rol['nombre_rol']; ?></td>
                                <td><?php echo $rol['descripcion'] ?? ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h4>Usuarios Registrados</h4>
            <div class="table-container">
                <table class="striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre de Usuario</th>
                            <th>Correo</th>
                            <th>Rol Actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo $usuario['codigo']; ?></td>
                                <td><?php echo $usuario['nombre_usuario']; ?></td>
                                <td><?php echo $usuario['correo_electronico'] ?? ''; ?></td>
                                <td><?php echo $usuario['nombre_rol'] ?? 'Sin rol'; ?> (<?php echo $usuario['rol_codigo']; ?>)</td>
                                <td>
                                    <a href="#modal-editar-rol-<?php echo $usuario['codigo']; ?>" class="btn-small blue modal-trigger">
                                        <i class="material-icons">edit</i>
                                    </a>

                                    <!-- Modal para editar rol -->
                                    <div id="modal-editar-rol-<?php echo $usuario['codigo']; ?>" class="modal">
                                        <div class="modal-content">
                                            <h4>Editar Rol de Usuario</h4>
                                            <p>Usuario: <strong><?php echo $usuario['nombre_usuario']; ?></strong></p>
                                            <p>Rol actual: <strong><?php echo $usuario['nombre_rol'] ?? 'Sin rol'; ?> (<?php echo $usuario['rol_codigo']; ?>)</strong></p>

                                            <form action="verificar_usuarios.php" method="POST">
                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['codigo']; ?>">

                                                <div class="input-field">
                                                    <select name="nuevo_rol" required>
                                                        <option value="" disabled>Seleccione un rol</option>
                                                        <?php foreach ($roles as $rol): ?>
                                                            <option value="<?php echo $rol['codigo']; ?>" <?php echo $usuario['rol_codigo'] == $rol['codigo'] ? 'selected' : ''; ?>>
                                                                <?php echo $rol['nombre_rol']; ?> (<?php echo $rol['codigo']; ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <label>Nuevo Rol</label>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="modal-close waves-effect waves-light btn-flat">Cancelar</button>
                                                    <button type="submit" name="actualizar_rol" class="waves-effect waves-light btn blue">Actualizar Rol</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="action-buttons">
                <a href="index.php" class="waves-effect waves-light btn grey">
                    <i class="material-icons left">arrow_back</i>Volver al Inicio
                </a>
                <a href="debug_session.php" class="waves-effect waves-light btn orange">
                    <i class="material-icons left">bug_report</i>Depurar Sesión
                </a>
            </div>
        </div>
    </div>

    <?php include 'frontend/includes/footer.php'; ?>

    <?php include 'frontend/includes/js_includes.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar modales y selects específicos de esta página
            var elems = document.querySelectorAll('.modal');
            var instances = M.Modal.init(elems);

            var elems = document.querySelectorAll('select');
            var instances = M.FormSelect.init(elems);
        });
    </script>
</body>
</html>
