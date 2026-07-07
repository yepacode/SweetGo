# Sweet Go · Guía de despliegue a producción

Pasos mínimos para subir el sistema a un hosting (Hostinger, DigitalOcean, VPS, etc.).

## 1. Antes de subir

### `.env` para producción
```env
APP_NAME="Sweet Go"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...            # generar con: php artisan key:generate
APP_URL=https://tudominio.com

APP_LOCALE=es
APP_FALLBACK_LOCALE=en

# WhatsApp real del negocio (formato internacional sin '+')
SWEETGO_WHATSAPP=57XXXXXXXXXX

# BD del hosting
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<tu_bd>
DB_USERNAME=<tu_usuario>
DB_PASSWORD=<contraseña_fuerte>

# Sesión / caché
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### Comandos previos
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=RolesSeeder --force
php artisan db:seed --class=ProductosSeeder --force   # opcional: carga los 57 productos base
php artisan db:seed --class=ListasPreciosSeeder --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Limpiar datos de prueba (si probaste con QaSeeder)
```bash
php artisan sweetgo:demo-clean --reset-admin-password="NUEVA_CONTRASEÑA_FUERTE"
```
Este comando borra clientes, cotizaciones, garantías, movimientos, enlaces y bitácora demo; conserva productos y categorías; y elimina los usuarios de prueba, dejando solo `admin@sweetgo.com` con la contraseña nueva.

## 2. En producción

- **HTTPS** ya se fuerza automáticamente cuando `APP_ENV=production` (ver `AppServiceProvider`). Solo necesitas certificado SSL en el hosting.
- **Cookies** se marcan como `secure` + `http_only` + `SameSite=lax`.
- **Confía en el proxy** del hosting (`trustProxies` = `'*'`) para que Laravel detecte el esquema HTTPS detrás del balanceador.
    > ⚠️ **Solo aplícalo si estás detrás de un proxy/CDN real** (Hostinger LiteSpeed, Cloudflare, Nginx, etc.).
    > Si despliegas en un VPS "pelado" sin proxy delante, cambia en `bootstrap/app.php`:
    > ```php
    > $middleware->trustProxies(at: null);
    > ```
    > Confiar en `'*'` sin proxy real permite que un atacante spoofee la IP en la bitácora y el esquema HTTPS.
- **Logo** — reemplaza el logotipo tipográfico en `resources/views/components/brand.blade.php` por el PNG/SVG oficial en `public/img/`.
- **Cambia la contraseña del admin** en el primer login desde "Mi perfil".

## 3. Después de desplegar

1. Entra como `admin@sweetgo.com` y cambia tu contraseña.
2. Crea los usuarios reales del equipo en **Usuarios**.
3. Configura los precios en **Productos → Listas de precios**.
4. Crea el/los enlaces públicos en **Catálogo** y compártelos por WhatsApp.
5. Verifica que el catálogo público muestra los precios y el número de WhatsApp correcto.

## 4. Verificaciones de seguridad

Antes de compartir la URL con el cliente:

- [ ] `APP_DEBUG=false`
- [ ] Contraseña del admin cambiada (no es "password")
- [ ] Cada usuario tiene su propia cuenta y rol
- [ ] `SWEETGO_WHATSAPP` es el número real
- [ ] Certificado SSL activo (candado en el navegador)
- [ ] No hay usuarios extra de prueba
- [ ] Datos demo limpios (si los cargaste)

## Contacto
Sistema desarrollado por MY Tech Solutions.
