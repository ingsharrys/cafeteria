<?php
/**
 * Archivo de constantes globales
 * Define todas las rutas y configuraciones centralizadas
 */

// Definir ruta raíz solo si no está definida
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Rutas base
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('VIEWS_PATH', ROOT_PATH . '/views');

// URLs (cambiar según tu entorno)
define('BASE_URL', getenv('APP_URL') ?: 'https://cafeteria.sharrys.com/');
define('PUBLIC_URL', BASE_URL . '/public');
define('ASSETS_URL', PUBLIC_URL . '/assets');

// Rutas de aplicación
define('LOGIN_URL', BASE_URL . '/views/login_new.php');
define('DASHBOARD_URL', BASE_URL . '/public/');
define('LOGOUT_URL', BASE_URL . '/public/?action=logout');

// Configuración de sesión
define('SESSION_LIFETIME', 14400); // 4 horas
define('SESSION_NAME', 'secure_session_id');

// Configuración de seguridad
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hora
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos

// Configuración de passwords
define('MIN_PASSWORD_LENGTH', 8);
define('REQUIRE_PASSWORD_UPPERCASE', true);
define('REQUIRE_PASSWORD_LOWERCASE', true);
define('REQUIRE_PASSWORD_NUMBER', true);
define('REQUIRE_PASSWORD_SPECIAL', false);

// Configuración de base de datos
define('DB_CHARSET', 'utf8mb4');
define('DB_TIMEZONE', '-05:00');

// Configuración de logs
define('LOG_PATH', STORAGE_PATH . '/logs');
define('LOG_FILE', LOG_PATH . '/app.log');
define('ERROR_LOG_FILE', LOG_PATH . '/error.log');
define('SECURITY_LOG_FILE', LOG_PATH . '/security.log');

// Entorno
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN));

// Cache
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600);