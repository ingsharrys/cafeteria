# Arquitectura

Aplicación PHP con una arquitectura MVC ligera propia (sin framework),
pensada para hosting compartido (Apache + `mod_rewrite`).

## Flujo de una petición

```
Navegador
   │
   ▼
public/.htaccess ──(rewrite)──► public/index.php     (front controller de vistas)
   │
   ├─ require bootstrap.php
   │     ├─ carga errores según APP_DEBUG
   │     ├─ require autoload.php  (PSR-4 + clases de core/)
   │     ├─ require config/constants.php, config/app.php, config/database.php
   │     ├─ Config\Config::load()   (lee .env)
   │     ├─ Core\Session::start()
   │     └─ Core\Response::setSecurityHeaders()
   │
   ├─ Autenticación (Core\Session::exists('user_id'))
   ├─ Whitelist + permisos por rol
   └─ include views/inc/header.php + views/<page>.php + views/inc/footer.php
```

Las llamadas AJAX del frontend pegan a endpoints en `app/controllers/*.php`
(scripts procedurales) y a `api/index.php` (dispatcher por `action`).

## Capas

| Capa            | Ubicación            | Responsabilidad                                  |
|-----------------|----------------------|--------------------------------------------------|
| Front controller| `public/index.php`   | Auth, permisos y render de vistas                |
| Vistas          | `views/`             | HTML + PHP de cada pantalla                       |
| Controladores   | `app/controllers/`   | Endpoints (clases `*Controller` y scripts sueltos)|
| Modelos         | `app/models/`        | Acceso a datos con PDO (consultas preparadas)     |
| Servicios       | `app/Services/`      | Integraciones (WhatsApp Cloud API)                |
| Middleware      | `app/middleware/`    | `AuthMiddleware`                                   |
| Núcleo          | `core/`              | `Session`, `Response`, `Router`, `Logger`, `Validator`, `Token`, `Cache`, `RateLimiter` |
| Configuración   | `config/`            | `database.php`, `app.php`, `constants.php`        |

## Convenciones

- **Namespaces**: `App\Controllers`, `App\Models`, `App\Middleware`,
  `App\Services`, `Core`, `Config`. El mapeo PSR-4 está en `composer.json` y
  replicado en `autoload.php`.
- **Configuración**: nunca hardcodear secretos; leer de `getenv()` / `.env`.
- **Base de datos**: `Database::getInstance()->getConnection()` devuelve un PDO
  singleton con `ERRMODE_EXCEPTION` y prepared statements.
- **Respuestas JSON**: usar `Core\Response` (`jsonSuccess`, `jsonError`).
- **Errores**: en producción (`APP_DEBUG=false`) no se muestran, solo se registran.

## Sub-aplicaciones

- `menu/` — menú público para clientes. Es **autocontenida**: tiene su propio
  `composer.json`, `vendor/` y configuración. No comparte el autoloader raíz.
- `webhooks/` — webhook del bot de WhatsApp (usa PHPMailer vendorizado).

## Deuda conocida

Ver [`CODE_REVIEW.md`](CODE_REVIEW.md), sección 4, para la hoja de ruta de
consolidación (unificar routing, migrar a Composer, añadir tests, etc.).
