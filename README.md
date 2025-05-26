# TiendaLex2

Una plataforma de tienda en línea moderna con autenticación de usuarios, catálogo de productos y gestión de inventario.

## Descripción

Solución e-commerce completa desarrollada con PHP y MySQL. Incluye autenticación segura (local y Google OAuth), gestión de productos con múltiples imágenes, categorización, y carrito de compras optimizado. Diseño responsive con Materialize CSS para una experiencia de usuario fluida en cualquier dispositivo.

## Características

- 🔐 Sistema de autenticación de usuarios (local y Google OAuth)
- 📱 Diseño responsive con Materialize CSS
- 🛍️ Catálogo de productos con filtros
- 📦 Gestión de inventario
- 🖼️ Soporte para múltiples imágenes por producto
- 💲 Soporte para precios en múltiples monedas

## Funcionalidades detalladas

### Autenticación y usuarios
- Registro de usuarios con validación de datos
- Inicio de sesión local con nombre de usuario y contraseña
- Integración con Google OAuth para inicio de sesión rápido
- Recuperación de contraseña
- Perfiles de usuario personalizables
- Roles diferenciados (administrador, cliente)

### Catálogo y productos
- Visualización de productos en formato grid y lista
- Filtrado por categorías, precios y disponibilidad
- Búsqueda avanzada de productos
- Soporte para múltiples imágenes por producto
- Galería de imágenes con zoom y vista ampliada
- Descripción detallada de productos con formato enriquecido
- Indicadores de disponibilidad en tiempo real

### Carrito de compras
- Añadir productos al carrito desde el catálogo
- Visualización flotante del carrito para acceso rápido
- Contador de productos en el carrito
- Modificación de cantidades en tiempo real
- Cálculo automático de subtotales y totales
- Persistencia del carrito entre sesiones

### Panel de administración
- Gestión completa de productos (añadir, editar, eliminar)
- Control de inventario con alertas de stock bajo
- Gestión de pedidos y estados
- Verificación de usuarios registrados
- Estadísticas de ventas y productos populares
- Chat con clientes para soporte

### Experiencia de usuario
- Diseño responsive adaptable a todos los dispositivos
- Navegación fluida con carga asíncrona (AJAX)
- Notificaciones en tiempo real
- Animaciones y transiciones suaves
- Tema visual personalizable
- Iconos intuitivos y elementos visuales atractivos

### Seguridad
- Protección contra inyección SQL
- Encriptación de contraseñas
- Validación de formularios en cliente y servidor
- Protección CSRF en formularios
- Sesiones seguras con tiempo de expiración
- Logs de actividad para auditoría

## Requisitos

- PHP 7.4 o superior
- MySQL/MariaDB
- Servidor web (Apache/Nginx)
- Credenciales de Google OAuth para la autenticación
- Módulos PHP: mysqli, gd, json, session, curl

## Instalación

1. Clona este repositorio en tu servidor web
2. Importa la estructura de la base de datos desde `database/schema.sql`
3. Configura tus credenciales de Google OAuth en `backend/google_config.php`
4. Asegúrate de que las URLs de redirección en la consola de Google coincidan con tu configuración
5. Configura los permisos de escritura para las carpetas de imágenes y logs
6. Ajusta la configuración de la base de datos en `config/db.php`
7. Accede a la aplicación y crea un usuario administrador

## Configuración de Google OAuth

Consulta el archivo `INSTRUCCIONES_GOOGLE_OAUTH.md` para obtener instrucciones detalladas sobre cómo configurar la autenticación con Google.

## Estructura del proyecto

```
tiendalex2/
├── backend/           # Controladores y lógica de negocio
├── config/            # Archivos de configuración
├── css/               # Estilos CSS
├── database/          # Esquema y scripts de base de datos
├── frontend/          # Vistas y componentes de interfaz
├── img/               # Imágenes estáticas del sitio
├── js/                # Scripts JavaScript
├── logs/              # Registros de errores y actividad
├── uploads/           # Carpeta para imágenes subidas
└── vendor/            # Dependencias de terceros
```

## Tecnologías utilizadas

- PHP 7.4+ (Backend)
- MySQL/MariaDB (Base de datos)
- JavaScript/AJAX (Frontend interactivo)
- Materialize CSS (Framework de diseño)
- Google OAuth API (Autenticación)

## Contribución

Las contribuciones son bienvenidas. Por favor, sigue estos pasos:

1. Haz fork del repositorio
2. Crea una rama para tu característica (`git checkout -b feature/amazing-feature`)
3. Haz commit de tus cambios (`git commit -m 'Add some amazing feature'`)
4. Push a la rama (`git push origin feature/amazing-feature`)
5. Abre un Pull Request

## Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## Autor

Alexander Carrasquel

## Contacto

Para soporte o consultas: alexander.carrasquel@gmail.com
