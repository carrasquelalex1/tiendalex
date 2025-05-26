# Estructura de la Base de Datos

Este documento contiene los scripts SQL necesarios para crear todas las tablas utilizadas en el sistema.

## Tablas del Sistema

### Tabla: datos_envio
```sql
CREATE TABLE IF NOT EXISTS datos_envio (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_codigo VARCHAR(255) NOT NULL,
    usuario_codigo BIGINT UNSIGNED NOT NULL,
    direccion TEXT NOT NULL,
    empresa_envio VARCHAR(255),
    destinatario_nombre VARCHAR(255) NOT NULL,
    destinatario_telefono VARCHAR(20) NOT NULL,
    destinatario_cedula VARCHAR(20) NOT NULL,
    instrucciones_adicionales TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX (pedido_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: productos_tienda
```sql
CREATE TABLE IF NOT EXISTS productos_tienda (
    id_producto BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    categoria VARCHAR(100),
    stock INT NOT NULL DEFAULT 0,
    imagen VARCHAR(255),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: pedidos_finalizados
```sql
CREATE TABLE IF NOT EXISTS pedidos_finalizados (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_codigo VARCHAR(255) NOT NULL,
    usuario_codigo BIGINT UNSIGNED NOT NULL,
    producto_id BIGINT UNSIGNED NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'pagado', 'enviado', 'entregado', 'cancelado') NOT NULL DEFAULT 'pendiente',
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos_tienda(id_producto) ON DELETE CASCADE,
    INDEX (pedido_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: pagos
```sql
CREATE TABLE IF NOT EXISTS pagos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_codigo VARCHAR(255) NOT NULL,
    parte_pago INT NOT NULL DEFAULT 1,
    metodo_pago VARCHAR(50) NOT NULL,
    referencia VARCHAR(100) NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    porcentaje DECIMAL(5,2) NULL,
    estado ENUM('pendiente', 'verificado', 'rechazado') NOT NULL DEFAULT 'pendiente',
    fecha_pago TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX (pedido_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: envios
```sql
CREATE TABLE IF NOT EXISTS envios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_codigo VARCHAR(255) NOT NULL,
    numero_guia VARCHAR(100),
    empresa_envio VARCHAR(100) NOT NULL,
    estado ENUM('pendiente', 'en_transito', 'entregado', 'devuelto') NOT NULL DEFAULT 'pendiente',
    fecha_envio TIMESTAMP NULL,
    fecha_entrega TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX (pedido_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: usuario
```sql
CREATE TABLE IF NOT EXISTS usuario (
    codigo BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    rol_id INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: rol
```sql
CREATE TABLE IF NOT EXISTS rol (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: bancos
```sql
CREATE TABLE IF NOT EXISTS bancos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: info_pago
```sql
CREATE TABLE IF NOT EXISTS info_pago (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    banco_id BIGINT UNSIGNED NOT NULL,
    tipo_cuenta ENUM('ahorro', 'corriente') NOT NULL,
    numero_cuenta VARCHAR(50) NOT NULL,
    titular VARCHAR(100) NOT NULL,
    cedula_titular VARCHAR(20) NOT NULL,
    telefono_titular VARCHAR(20),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (banco_id) REFERENCES bancos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: chat_mensajes
```sql
CREATE TABLE IF NOT EXISTS chat_mensajes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    usuario_id INT(11) NOT NULL,
    admin_id INT(11) DEFAULT NULL,
    mensaje TEXT NOT NULL,
    leido TINYINT(1) DEFAULT 0,
    fecha_envio DATETIME NOT NULL,
    enviado_por ENUM('usuario', 'admin') NOT NULL,
    PRIMARY KEY (id),
    KEY usuario_id (usuario_id),
    KEY admin_id (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Tabla: chat_sesiones
```sql
CREATE TABLE IF NOT EXISTS chat_sesiones (
    id INT(11) NOT NULL AUTO_INCREMENT,
    usuario_id INT(11) NOT NULL,
    admin_id INT(11) DEFAULT NULL,
    estado ENUM('activa', 'cerrada') NOT NULL DEFAULT 'activa',
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY usuario_id (usuario_id),
    KEY admin_id (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Tabla: configuracion_tema
```sql
CREATE TABLE IF NOT EXISTS configuracion_tema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_configuracion VARCHAR(50) NOT NULL,
    valor_configuracion VARCHAR(255) NOT NULL,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Datos Iniciales

### Insertar Bancos Predeterminados
```sql
INSERT INTO bancos (nombre, activo, created_at, updated_at) VALUES
('Banco de Venezuela', 1, NOW(), NOW()),
('Banco Provincial (BBVA)', 1, NOW(), NOW()),
('Banesco', 1, NOW(), NOW()),
('Banco Mercantil', 1, NOW(), NOW()),
('Banco Nacional de Crédito (BNC)', 1, NOW(), NOW()),
('Banco Exterior', 1, NOW(), NOW()),
('Banco Occidental de Descuento (BOD)', 1, NOW(), NOW()),
('Banco Venezolano de Crédito', 1, NOW(), NOW()),
('Banco Bicentenario', 1, NOW(), NOW()),
('Banco del Tesoro', 1, NOW(), NOW()),
('Bancaribe', 1, NOW(), NOW()),
('Banco Fondo Común (BFC)', 1, NOW(), NOW()),
('Banco Plaza', 1, NOW(), NOW()),
('Banco Caroní', 1, NOW(), NOW()),
('100% Banco', 1, NOW(), NOW()),
('Bancamiga', 1, NOW(), NOW()),
('Banplus', 1, NOW(), NOW()),
('Banco Activo', 1, NOW(), NOW()),
('Banco Agrícola de Venezuela', 1, NOW(), NOW()),
('Banco de la Fuerza Armada Nacional Bolivariana (BANFANB)', 1, NOW(), NOW());
```

### Insertar Configuración de Tema Predeterminada
```sql
INSERT INTO configuracion_tema (nombre_configuracion, valor_configuracion) VALUES
('primary_color', '#2c3e50'),
('secondary_color', '#34495e'),
('accent_color', '#2980b9'),
('success_color', '#27ae60'),
('danger_color', '#c0392b'),
('warning_color', '#f39c12');
```

## Notas de Implementación

1. Ejecutar primero el script de creación de tablas en el orden presentado para respetar las dependencias de claves foráneas.
2. Después de crear las tablas, ejecutar los scripts de inserción de datos iniciales.
3. Para verificar la correcta creación de las tablas, puede usar el comando `SHOW TABLES;` en MySQL.
4. Para verificar la estructura de una tabla específica, puede usar el comando `DESCRIBE nombre_tabla;` en MySQL.
