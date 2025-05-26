# Instrucciones para configurar Google OAuth

Para completar la configuración de la autenticación con Google en tu proyecto, sigue estos pasos:

## 1. Obtener el Client Secret

El Client ID ya está configurado en `backend/google_config.php`:
```
$google_client_id = '2203602660-g0ri5e1vppevcu4s3uc8cnq0cehlnrvn.apps.googleusercontent.com';
```

Necesitas obtener el Client Secret correspondiente a este Client ID desde la consola de desarrolladores de Google y reemplazar el placeholder en el archivo `backend/google_config.php`:
```
$google_client_secret = 'GOCSPX-YOUR_CLIENT_SECRET'; // Reemplaza esto con el Client Secret real
```

## 2. Configurar la URL de redirección

Asegúrate de que la URL de redirección configurada en la consola de desarrolladores de Google coincida exactamente con la URL configurada en `backend/google_config.php`:
```
$google_redirect_uri = 'http://localhost/tiendalex2/backend/google_callback.php';
```

Para configurar la URL de redirección en la consola de Google:
1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Selecciona tu proyecto
3. Ve a "APIs y servicios" > "Credenciales"
4. Edita el cliente OAuth 2.0 que estás utilizando
5. En la sección "URIs de redirección autorizados", asegúrate de que esté incluida la URL:
   `http://localhost/tiendalex2/backend/google_callback.php`
6. Guarda los cambios

## 3. Verificar los ámbitos (scopes)

Asegúrate de que los siguientes ámbitos estén habilitados en la consola de desarrolladores de Google:
- `https://www.googleapis.com/auth/userinfo.email`
- `https://www.googleapis.com/auth/userinfo.profile`
- `openid`

Para verificar y configurar los ámbitos:
1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Selecciona tu proyecto
3. Ve a "APIs y servicios" > "Pantalla de consentimiento de OAuth"
4. En la sección "Permisos", asegúrate de que los ámbitos mencionados estén incluidos
5. Si necesitas añadir alguno, haz clic en "Añadir o eliminar ámbitos" y selecciona los ámbitos necesarios
6. Guarda los cambios

## 4. Probar la autenticación

Una vez completada la configuración:
1. Accede a la página de inicio de sesión: `http://localhost/tiendalex2/login.php`
2. Haz clic en el botón "Google"
3. Deberías ser redirigido a la página de inicio de sesión de Google
4. Después de iniciar sesión, deberías ser redirigido de vuelta a tu aplicación

## Solución de problemas

Si encuentras algún error durante la autenticación:

1. **Error de redirección**: Verifica que la URL de redirección configurada en la consola de Google coincida exactamente con la URL en tu código.

2. **Error de ámbitos**: Asegúrate de que todos los ámbitos necesarios estén habilitados en la pantalla de consentimiento de OAuth.

3. **Error de credenciales**: Verifica que el Client ID y el Client Secret sean correctos y correspondan al mismo proyecto en la consola de Google.

4. **Errores en el servidor**: Revisa los logs de error de PHP para obtener más información sobre posibles problemas.

## Notas adicionales

- En un entorno de producción, deberías almacenar las credenciales de Google OAuth en variables de entorno o en un archivo de configuración seguro, no directamente en el código.
- Si cambias el dominio o la ruta de tu aplicación, deberás actualizar la URL de redirección tanto en tu código como en la consola de Google.
