<?php
namespace Config;

/**
 * Clase de configuración mejorada
 * Carga variables desde .env y proporciona acceso centralizado
 */
class Config
{
    private static $config = null;
    private static $envLoaded = false;

    /**
     * Cargar variables de entorno desde .env
     */
    private static function loadEnv()
    {
        if (self::$envLoaded) {
            return;
        }

        $envFile = dirname(__DIR__) . '/.env';
        
        if (!file_exists($envFile)) {
            // Si no existe .env, usar valores por defecto
            self::$envLoaded = true;
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parsear línea KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remover comillas si existen
                $value = trim($value, '"\'');
                
                // Establecer como variable de entorno
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    putenv("{$key}={$value}");
                }
            }
        }

        self::$envLoaded = true;
    }

    /**
     * Cargar toda la configuración
     */
    public static function load()
    {
        if (self::$config !== null) {
            return self::$config;
        }

        // Cargar variables de entorno primero
        self::loadEnv();

        // Construir array de configuración
        self::$config = [
            // Aplicación
            'APP_ENV' => self::env('APP_ENV', 'production'),
            'APP_DEBUG' => self::env('APP_DEBUG', false, 'bool'),
            'APP_URL' => self::env('APP_URL', 'https://cafeteria.sharrys.com/'),
            
            // Base de datos
            'DB_HOST' => self::env('DB_HOST', 'localhost'),
            'DB_NAME' => self::env('DB_NAME', 'sharrys_cafeteriapombodb'),
            'DB_USER' => self::env('DB_USER', 'sharrys_cafeteriapombouser'),
            'DB_PASS' => self::env('DB_PASS', ''),
            'DB_CHARSET' => 'utf8mb4',
            'DB_TIMEZONE' => '-05:00',
            
            // reCAPTCHA
            'RECAPTCHA_ENABLED' => self::env('RECAPTCHA_ENABLED', false, 'bool'),
            'RECAPTCHA_SITE_KEY' => self::env('RECAPTCHA_SITE_KEY', ''),
            'RECAPTCHA_SECRET_KEY' => self::env('RECAPTCHA_SECRET_KEY', ''),
            'RECAPTCHA_MIN_SCORE' => self::env('RECAPTCHA_MIN_SCORE', 0.5, 'float'),
            
            // Sesión
            'SESSION_LIFETIME' => self::env('SESSION_LIFETIME', 14400, 'int'),
            'SESSION_DOMAIN' => self::env('SESSION_DOMAIN', ''),
            
            // Seguridad
            'MAX_LOGIN_ATTEMPTS' => 5,
            'LOGIN_LOCKOUT_TIME' => 900, // 15 minutos
            'MIN_PASSWORD_LENGTH' => 8,
            
            // Email (para futuras implementaciones)
            'MAIL_HOST' => self::env('MAIL_HOST', ''),
            'MAIL_PORT' => self::env('MAIL_PORT', 587, 'int'),
            'MAIL_USERNAME' => self::env('MAIL_USERNAME', ''),
            'MAIL_PASSWORD' => self::env('MAIL_PASSWORD', ''),
            'MAIL_ENCRYPTION' => self::env('MAIL_ENCRYPTION', 'tls'),
            'MAIL_FROM_ADDRESS' => self::env('MAIL_FROM_ADDRESS', 'noreply@pideyapp.com'),
            'MAIL_FROM_NAME' => self::env('MAIL_FROM_NAME', 'PideYAPP'),
        ];

        return self::$config;
    }

    /**
     * Obtener valor de configuración
     */
    public static function get($key, $default = null)
    {
        if (self::$config === null) {
            self::load();
        }

        // Permitir notación de punto: database.host
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $value = self::$config;
            
            foreach ($keys as $k) {
                if (!isset($value[$k])) {
                    return $default;
                }
                $value = $value[$k];
            }
            
            return $value;
        }

        return self::$config[$key] ?? $default;
    }

    /**
     * Obtener variable de entorno con tipo
     */
    private static function env($key, $default = null, $type = 'string')
    {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }

        // Convertir según tipo
        switch ($type) {
            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            
            case 'int':
            case 'integer':
                return (int) $value;
            
            case 'float':
            case 'double':
                return (float) $value;
            
            case 'array':
                return explode(',', $value);
            
            default:
                return $value;
        }
    }

    /**
     * Verificar si está en modo debug
     */
    public static function isDebug()
    {
        return self::get('APP_DEBUG', false);
    }

    /**
     * Verificar si está en producción
     */
    public static function isProduction()
    {
        return self::get('APP_ENV') === 'production';
    }

    /**
     * Obtener toda la configuración (útil para debug)
     */
    public static function all()
    {
        if (self::$config === null) {
            self::load();
        }

        // Ocultar información sensible
        $config = self::$config;
        $sensitiveKeys = ['DB_PASS', 'RECAPTCHA_SECRET_KEY', 'MAIL_PASSWORD'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($config[$key])) {
                $config[$key] = '********';
            }
        }

        return $config;
    }
}