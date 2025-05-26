# Base de Datos TiendaAlex2

Este archivo contiene los códigos SQL para crear todas las tablas de la base de datos `tiendalex2`.

## Información de Conexión

- **Servidor:** localhost
- **Usuario:** alex
- **Base de datos:** tiendalex2

## Tablas de la Base de Datos

### Tabla: `bancos`

```sql
CREATE TABLE `bancos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: `carrito`

```sql
CREATE TABLE `carrito` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` bigint unsigned NOT NULL,
  `producto_id` bigint unsigned NOT NULL,
  `cantidad` int DEFAULT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `precio` decimal(10,2) NOT NULL,
  `precio_dolares` decimal(10,2) NOT NULL,
  `precio_bolivares` decimal(10,2) NOT NULL,
  `pedido` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `carrito_usuario_id_index` (`usuario_id`),
  KEY `carrito_producto_id_index` (`producto_id`),
  CONSTRAINT `carrito_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos_tienda` (`id_producto`),
  CONSTRAINT `carrito_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: `categorias`

```sql
CREATE TABLE `categorias` (
  `id_categoria` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `categorias_nombre_categoria_unique` (`nombre_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: `chat_mensajes`

```sql
CREATE TABLE `chat_mensajes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `admin_id` int DEFAULT NULL,
  `mensaje` text COLLATE utf8mb4_general_ci NOT NULL,
  `leido` tinyint(1) DEFAULT '0',
  `fecha_envio` datetime NOT NULL,
  `enviado_por` enum('usuario','admin') COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Tabla: `chat_sesiones`

```sql
CREATE TABLE `chat_sesiones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `ultimo_mensaje_id` int DEFAULT NULL,
  `fecha_ultimo_mensaje` datetime DEFAULT NULL,
  `estado` enum('activa','cerrada') COLLATE utf8mb4_general_ci DEFAULT 'activa',
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  KEY `ultimo_mensaje_id` (`ultimo_mensaje_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Tabla: `configuracion_tema`

```sql
CREATE TABLE `configuracion_tema` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_configuracion` varchar(50) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `valor_configuracion` varchar(255) COLLATE utf8mb4_spanish2_ci NOT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
```

### Tabla: `datos_envio`

```sql
CREATE TABLE `datos_envio` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pedido_codigo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_codigo` bigint unsigned NOT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `empresa_envio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destinatario_nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destinatario_telefono` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destinatario_cedula` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instrucciones_adicionales` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_codigo` (`usuario_codigo`),
  CONSTRAINT `datos_envio_ibfk_1` FOREIGN KEY (`usuario_codigo`) REFERENCES `usuario` (`codigo`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: `envios`

```sql
CREATE TABLE `envios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_codigo` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_codigo` int NOT NULL,
  `estado` enum('pendiente','en_proceso','enviado','entregado') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
  `fecha_envio` datetime DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `numero_seguimiento` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `empresa_envio` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pedido_codigo` (`pedido_codigo`),
  KEY `usuario_codigo` (`usuario_codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Tabla: `historial_pagos`

```sql
CREATE TABLE `historial_pagos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_codigo` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `usuario_codigo` int NOT NULL,
  `metodo_pago` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `banco` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `referencia` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comentarios` text COLLATE utf8mb4_general_ci,
  `estado` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
  `comentarios_admin` text COLLATE utf8mb4_general_ci,
  `fecha_registro` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pedido_codigo` (`pedido_codigo`),
  KEY `idx_usuario_codigo` (`usuario_codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Tabla: `info_pago`

```sql
CREATE TABLE `info_pago` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tipo` enum('banco','pago_movil','efectivo','otro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `banco_id` bigint unsigned DEFAULT NULL,
  `numero_cuenta` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `titular` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cedula_rif` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `banco_id` (`banco_id`),
  CONSTRAINT `info_pago_ibfk_1` FOREIGN KEY (`banco_id`) REFERENCES `bancos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: `pagos`

```sql
CREATE TABLE `pagos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pedido_codigo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parte_pago` int NOT NULL DEFAULT '1',
  `usuario_codigo` bigint unsigned NOT NULL,
  `metodo_pago` enum('transferencia','pago_movil','efectivo') COLLATE utf8mb4_unicode_ci NOT NULL,
  `banco` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `porcentaje` decimal(5,2) DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_pago` date NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('pendiente','verificado','enviado','entregado','rechazado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `comentarios` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_codigo` (`usuario_codigo`),
  CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`usuario_codigo`) REFERENCES `usuario` (`codigo`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: `pedido`

```sql
CREATE TABLE `pedido` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` bigint unsigned NOT NULL,
  `producto_id` bigint unsigned NOT NULL,
  `cantidad` int NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `precio_dolares` decimal(10,2) NOT NULL,
  `precio_bolivares` decimal(10,2) NOT NULL,
  `pedido` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pedido_producto_id_foreign` (`producto_id`),
  KEY `pedido_usuario_id_foreign` (`usuario_id`),
  CONSTRAINT `pedido_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos_tienda` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pedido_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`codigo`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: `pedidos_finalizados`

```sql
CREATE TABLE `pedidos_finalizados` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pedido_codigo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` bigint unsigned NOT NULL,
  `producto_id` bigint unsigned NOT NULL,
  `cantidad` int NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `precio_dolares` decimal(10,2) NOT NULL,
  `precio_bolivares` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','pago_pendiente','pagado','enviado','entregado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pagado',
  `fecha_pedido` timestamp NULL DEFAULT NULL,
  `fecha_pago` timestamp NULL DEFAULT NULL,
  `fecha_finalizacion` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `producto_id` (`producto_id`),
  KEY `pedido_codigo` (`pedido_codigo`),
  CONSTRAINT `pedidos_finalizados_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`codigo`) ON DELETE CASCADE,
  CONSTRAINT `pedidos_finalizados_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos_tienda` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: `productos_tienda`

```sql
CREATE TABLE `productos_tienda` (
  `id_producto` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_producto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion_producto` text COLLATE utf8mb4_unicode_ci,
  `precio_producto` decimal(10,2) NOT NULL,
  `tasa` decimal(10,3) NOT NULL DEFAULT '0.000',
  `existencia_producto` int NOT NULL DEFAULT '0',
  `imagen_producto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagen_producto2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagen_producto3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagen_producto4` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagen_producto5` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categoria_id` bigint unsigned DEFAULT NULL,
  `precio_bolivares` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_producto`),
  KEY `productos_tienda_categoria_id_foreign` (`categoria_id`),
  CONSTRAINT `productos_tienda_categoria_id_foreign` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: `rol`

```sql
CREATE TABLE `rol` (
  `codigo` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: `rol_archivo`

```sql
CREATE TABLE `rol_archivo` (
  `codigo_rol` bigint unsigned NOT NULL,
  `archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tiene_acceso` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`codigo_rol`,`archivo`),
  CONSTRAINT `rol_archivo_codigo_rol_foreign` FOREIGN KEY (`codigo_rol`) REFERENCES `rol` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: `sessions`

```sql
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: `tasa`

```sql
CREATE TABLE `tasa` (
  `id_tasa` bigint unsigned NOT NULL AUTO_INCREMENT,
  `valor_tasa` decimal(10,3) NOT NULL,
  `current` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_tasa`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabla: `usuario`

```sql
CREATE TABLE `usuario` (
  `codigo` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_usuario` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo_electronico` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contrasena` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_user_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol_codigo` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`codigo`),
  UNIQUE KEY `usuario_google_user_id_unique` (`google_user_id`),
  KEY `usuario_rol_codigo_foreign` (`rol_codigo`),
  CONSTRAINT `usuario_rol_codigo_foreign` FOREIGN KEY (`rol_codigo`) REFERENCES `rol` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Instrucciones de Instalación

1. Crear la base de datos:
```sql
CREATE DATABASE tiendalex2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Usar la base de datos:
```sql
USE tiendalex2;
```

3. Ejecutar los comandos CREATE TABLE de cada tabla en el orden mostrado arriba.

## Orden Recomendado de Creación de Tablas

Para evitar errores de claves foráneas, se recomienda crear las tablas en el siguiente orden:

1. `rol` - Tabla de roles de usuario
2. `usuario` - Tabla de usuarios (depende de rol)
3. `categorias` - Tabla de categorías de productos
4. `productos_tienda` - Tabla de productos (depende de categorias)
5. `bancos` - Tabla de bancos
6. `info_pago` - Información de pago (depende de bancos)
7. `carrito` - Carrito de compras (depende de usuario y productos_tienda)
8. `pedido` - Pedidos (depende de usuario y productos_tienda)
9. `pedidos_finalizados` - Pedidos finalizados (depende de usuario y productos_tienda)
10. `datos_envio` - Datos de envío (depende de usuario)
11. `pagos` - Pagos (depende de usuario)
12. `envios` - Envíos
13. `historial_pagos` - Historial de pagos
14. `tasa` - Tasas de cambio
15. `configuracion_tema` - Configuración del tema
16. `chat_sesiones` - Sesiones de chat
17. `chat_mensajes` - Mensajes de chat
18. `rol_archivo` - Permisos de archivos por rol (depende de rol)
19. `sessions` - Sesiones de usuario
20. `migrations` - Control de migraciones

## Datos Iniciales Recomendados

### Roles
```sql
INSERT INTO rol (codigo, nombre_rol) VALUES
(1, 'administrador'),
(2, 'cliente');
```

### Usuario Administrador
```sql
INSERT INTO usuario (codigo, nombre_usuario, correo_electronico, contrasena, rol_codigo) VALUES
(1, 'alexander', 'admin@tiendalex2.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);
```

### Tasa de Cambio Inicial
```sql
INSERT INTO tasa (valor_tasa, current) VALUES
(36.500, 1);
```

### Categorías de Ejemplo
```sql
INSERT INTO categorias (nombre_categoria) VALUES
('Electrónicos'),
('Ropa'),
('Hogar'),
('Deportes');
```

### Configuración de Tema Inicial
```sql
INSERT INTO configuracion_tema (nombre_configuracion, valor_configuracion) VALUES
('color_encabezado', '#2c3e50'),
('color_footer', '#2c3e50'),
('color_botones', '#3498db'),
('color_disponibilidad', '#f39c12');
```

## Descripción de las Tablas Principales

### Sistema de Usuarios y Roles
- **usuario**: Almacena información de usuarios del sistema
- **rol**: Define los roles disponibles (administrador, cliente)
- **rol_archivo**: Controla permisos de acceso a archivos por rol

### Sistema de Productos
- **productos_tienda**: Catálogo de productos con precios y existencias
- **categorias**: Categorías para organizar productos
- **tasa**: Tasas de cambio para conversión de monedas

### Sistema de Compras
- **carrito**: Productos agregados al carrito (temporal)
- **pedido**: Pedidos en proceso
- **pedidos_finalizados**: Pedidos completados
- **pagos**: Información de pagos realizados
- **historial_pagos**: Historial completo de pagos
- **datos_envio**: Información de envío de pedidos
- **envios**: Control de envíos y entregas

### Sistema de Pagos
- **bancos**: Lista de bancos disponibles
- **info_pago**: Información de cuentas bancarias para recibir pagos

### Sistema de Chat
- **chat_sesiones**: Sesiones de chat entre usuarios y administradores
- **chat_mensajes**: Mensajes del sistema de chat

### Sistema de Configuración
- **configuracion_tema**: Configuración de colores y tema del sitio
- **sessions**: Manejo de sesiones de usuario
- **migrations**: Control de versiones de base de datos

## Notas Importantes

1. **Codificación**: Todas las tablas usan `utf8mb4` para soporte completo de Unicode
2. **Claves Foráneas**: Las relaciones están definidas con restricciones de integridad referencial
3. **Timestamps**: Muchas tablas incluyen campos `created_at` y `updated_at` para auditoría
4. **Seguridad**: Las contraseñas deben estar encriptadas usando `password_hash()` de PHP
5. **Precios**: El sistema maneja precios en dólares y bolívares con conversión automática

