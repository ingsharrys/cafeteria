# Revisión de código — Sistema de Cafetería

> Fecha: 2026-07-13 · Rama: `claude/code-review-refactor`
> Alcance aplicado: **limpieza + reestructura de bajo riesgo** (sin tocar la
> lógica de negocio). Este documento resume lo encontrado, lo que se hizo y la
> hoja de ruta recomendada.

---

## 1. Resumen ejecutivo

El proyecto es funcional pero arrastra una fuerte deuda técnica típica de
desarrollo sobre hosting compartido: secretos en el repositorio, decenas de
archivos de respaldo/obsoletos, duplicación de código y estructura inconsistente.

En esta rama se corrigió lo **crítico de seguridad** y se **eliminó el ruido**,
dejando la base lista para mejoras incrementales, **sin alterar comportamiento**.

---

## 2. 🔴 Seguridad (crítico) — corregido en esta rama

| # | Problema | Acción |
|---|----------|--------|
| 1 | Contraseña de BD en texto plano en `config/database.php` | Externalizada a `.env` / variables de entorno |
| 2 | Segunda contraseña de BD (distinta) en `config/app.php` | Eliminada del código |
| 3 | Token de WhatsApp Cloud API en `app/config/whatsapp.php` | Externalizado a `WA_ACCESS_TOKEN` |
| 4 | Segundo token de WhatsApp en `app/controllers/config.php` | Externalizado a env |
| 5 | Llaves privadas y certificados commiteados (`qz-key.pem`, `qz-cert.pem`, `qz-tray.cer`, `certificate.pem`, `digital-certificate.txt`) | Eliminados del repo + ignorados |
| 6 | 160 archivos de sesión PHP vivos (`storage/sessions/sess_*`) | Eliminados del repo + ignorados |
| 7 | Scripts de diagnóstico/instalación expuestos (`diagnostico.php`, `whoami.php`, `test_*.php`, `check_php.php`, `INSTALAR_SISTEMA_ROUTING.php`, `instalador_simple.php`, `send_test.php`) | Eliminados |
| 8 | `bootstrap.php` con `display_errors=1` fijo (fuga de rutas/stack en producción) | Ahora depende de `APP_DEBUG` |

> ### ⚠️ Acción obligatoria de tu parte
> Todas las credenciales anteriores **siguen en el historial de git**. Debes
> **rotarlas** (cambiarlas en el proveedor):
> - 2 contraseñas de base de datos MySQL.
> - 2 access tokens de WhatsApp Cloud API (Meta) → regenéralos en Meta for Developers.
> - Certificados/llaves de QZ Tray si se usan para firmar impresión.
>
> Opcionalmente, purga el historial con `git filter-repo` o BFG Repo-Cleaner.

---

## 3. 🟠 Archivos obsoletos eliminados

**Respaldos con marca de tiempo** (`*.backup.2026...`): 24 archivos en
`app/controllers/` y `app/models/` (Caja, Cliente, Domicilio, Gasto, Mesa,
Mesero, Pedido, Producto, Turno) + `public/index.php.backup.*`.

**Versiones antiguas** (`*.old`, `*_old`, `*_olds`, `.save`):
`api_old.php`, `api_datos_old.php`, `obtener_datos*.old`,
`PedidoModel.php.old`, `validar_codigo.php_old`, `reportes_olds.php`,
`menu/js/script_old.js`, `menu/js/app.js_olds`, `public/js/script*.old`,
`webhooks/bot/chatwp_old.php`, `.gitignore.save`, etc.

**Controladores duplicados muertos en la raíz** (mismo FQCN
`App\Controllers\*` que los de `app/controllers/`; el autoloader siempre
resuelve a `app/`, por lo que eran **inalcanzables**):
`CajaController.php`, `MesaController.php`, `MeseroController.php`,
`PedidoController.php`, `TurnoController.php`, `Router.php`,
`AuthMiddleware.php` (este último contenía por error el cuerpo de `routes.php`).

**Basura de FTP/procesos**: `views/.ftpquota`, `envio_masivo_domingo.lock`.

Total: **229 archivos** eliminados (69 de código/config + 160 sesiones).

---

## 4. 🟡 Deuda técnica pendiente (recomendaciones, no aplicado)

Estos puntos **no** se tocaron para no alterar la lógica; se dejan como hoja de ruta:

1. **Dos sistemas de routing coexistiendo.** Hay un router REST incipiente
   (`routes.php`, `routes/`, `core/Router.php`, `app/Router.php`) y el sistema
   legacy basado en `include` (`api/index.php`, `api.php`). Conviene elegir uno
   y eliminar el otro. Actualmente el entry point real (`public/index.php`) no
   usa el router REST.

2. **Dependencias vendorizadas a mano.** `app/controllers/libs/PhpSpreadsheet`
   (6.3 MB) y `webhooks/bot/PHPMailer` (0.6 MB) están copiadas dentro del repo.
   Se añadió `composer.json` declarándolas: ejecuta `composer install` y luego
   migra los `require` a `vendor/autoload.php` para poder eliminarlas.

3. **Controladores "procedurales".** Decenas de scripts sueltos en
   `app/controllers/` (`get_pedido.php`, `post_pedido.php`, ...) son endpoints
   procedurales, no clases. Migrarlos gradualmente a métodos de controlador.

4. **Acceso a datos con SQL embebido.** Los modelos usan PDO con consultas
   preparadas (bien), pero hay SQL repetido. Considerar un repositorio/base común.

5. **Sin pruebas automatizadas.** No hay tests. Empezar por PHPUnit sobre los
   modelos y helpers (`helpers/TotalCalculator.php` es un buen primer candidato).

6. **`autoload.php` propio vs. Composer.** Una vez con `vendor/`, migrar al
   autoloader de Composer (PSR-4 ya declarado en `composer.json`) y retirar el
   autoloader manual.

7. **Assets binarios en el repo.** `alert-33762.mp3` (~890 KB), imágenes y audios
   en `views/`. Considerar moverlos a `public/assets/` o a almacenamiento externo.

8. **`bootstrap.php`** incluye una ruta URL (`https://...`) dentro de la lista de
   rutas de sistema de archivos (`$possibleRoots`): código muerto inofensivo,
   conviene limpiarlo.

---

## 5. ✅ Buenas prácticas añadidas

- `.env.example` con todas las variables documentadas.
- `.gitignore` reforzado (secretos, sesiones, logs, backups, vendor).
- `composer.json` con autoload PSR-4, dependencias y scripts (`lint`, `fix`, `analyse`).
- `.editorconfig` para estilo consistente.
- `README.md` funcional (el anterior estaba corrupto).
- Documentación de arquitectura (`docs/ARQUITECTURA.md`).
- Manejo de errores dependiente de entorno en `bootstrap.php`.

---

## 6. Cómo verificar

```bash
# La app sigue funcionando igual; el entry point no cambió:
php -l public/index.php
php -l bootstrap.php
php -l config/database.php

# Con Composer disponible:
composer install
composer analyse   # PHPStan nivel 3
composer lint      # php-cs-fixer (dry-run)
```
