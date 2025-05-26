<?php
/**
 * Script para configurar roles y crear usuario administrador
 * Este script debe ejecutarse una sola vez para configurar el sistema
 */

// Incluir la conexión a la base de datos
require_once 'config/db.php';

// Función para verificar si ya existen roles
function rolesExisten($conn) {
    $sql = "SELECT COUNT(*) as count FROM rol";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }
    
    return false;
}

// Función para crear roles
function crearRoles($conn) {
    // Definir los roles
    $roles = [
        ['nombre_rol' => 'Administrador'],
        ['nombre_rol' => 'Cliente']
    ];
    
    // Insertar roles
    $stmt = $conn->prepare("INSERT INTO rol (nombre_rol, created_at, updated_at) VALUES (?, NOW(), NOW())");
    $stmt->bind_param("s", $nombre_rol);
    
    foreach ($roles as $rol) {
        $nombre_rol = $rol['nombre_rol'];
        if (!$stmt->execute()) {
            echo "Error al crear rol {$nombre_rol}: " . $stmt->error . "<br>";
            return false;
        }
    }
    
    echo "Roles creados correctamente.<br>";
    return true;
}

// Función para verificar si el usuario administrador ya existe
function usuarioAdminExiste($conn, $nombre_usuario) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuario WHERE nombre_usuario = ?");
    $stmt->bind_param("s", $nombre_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }
    
    return false;
}

// Función para crear usuario administrador
function crearUsuarioAdmin($conn, $nombre_usuario, $contrasena) {
    // Hashear la contraseña
    $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);
    
    // Obtener el código del rol administrador
    $sql = "SELECT codigo FROM rol WHERE nombre_rol = 'Administrador' LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $rol_codigo = $row['codigo'];
        
        // Insertar el usuario administrador
        $stmt = $conn->prepare("INSERT INTO usuario (nombre_usuario, correo_electronico, contrasena, rol_codigo, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $correo = $nombre_usuario . "@admin.com"; // Correo genérico para el admin
        $stmt->bind_param("sssi", $nombre_usuario, $correo, $hashed_password, $rol_codigo);
        
        if ($stmt->execute()) {
            echo "Usuario administrador creado correctamente.<br>";
            return true;
        } else {
            echo "Error al crear usuario administrador: " . $stmt->error . "<br>";
            return false;
        }
    } else {
        echo "Error: No se encontró el rol de Administrador.<br>";
        return false;
    }
}

// Ejecutar la configuración
try {
    // Verificar si ya existen roles
    if (!rolesExisten($conn)) {
        // Crear roles
        if (!crearRoles($conn)) {
            echo "Error al crear roles. Abortando.<br>";
            exit;
        }
    } else {
        echo "Los roles ya existen en la base de datos.<br>";
    }
    
    // Verificar si el usuario administrador ya existe
    $nombre_admin = "alexander";
    if (!usuarioAdminExiste($conn, $nombre_admin)) {
        // Crear usuario administrador
        $contrasena_admin = "12569655";
        if (!crearUsuarioAdmin($conn, $nombre_admin, $contrasena_admin)) {
            echo "Error al crear usuario administrador. Abortando.<br>";
            exit;
        }
    } else {
        echo "El usuario administrador ya existe en la base de datos.<br>";
    }
    
    echo "Configuración completada con éxito.<br>";
    echo "<a href='../index.php'>Volver al inicio</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
