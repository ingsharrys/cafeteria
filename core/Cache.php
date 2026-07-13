<?php
namespace Core;

/**
 * File-Based Cache (SIN REDIS)
 * Funciona en cualquier hosting sin necesidad de Redis
 */
class Cache
{
    private static $cacheDir = null;
    private static $enabled = true;

    /**
     * Inicializar directorio de cache
     */
    private static function init()
    {
        if (self::$cacheDir !== null) {
            return;
        }

        // Usar storage/cache si existe, sino usar sys_get_temp_dir()
        if (defined('STORAGE_PATH')) {
            self::$cacheDir = STORAGE_PATH . '/cache';
        } else {
            self::$cacheDir = sys_get_temp_dir() . '/pideyapp_cache';
        }

        // Crear directorio si no existe
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0755, true);
        }

        // Verificar que sea escribible
        if (!is_writable(self::$cacheDir)) {
            error_log("⚠️ Cache directory not writable: " . self::$cacheDir);
            self::$enabled = false;
        }
    }

    /**
     * Generar nombre de archivo para cache
     */
    private static function getCacheFile($key)
    {
        $hash = md5($key);
        return self::$cacheDir . '/' . $hash . '.cache';
    }

    /**
     * Obtener valor del cache
     */
    public static function get(string $key)
    {
        self::init();
        
        if (!self::$enabled) {
            return null;
        }

        $file = self::getCacheFile($key);

        if (!file_exists($file)) {
            return null;
        }

        $data = @file_get_contents($file);
        if ($data === false) {
            return null;
        }

        $cached = unserialize($data);

        // Verificar expiración
        if (isset($cached['expires']) && time() > $cached['expires']) {
            @unlink($file);
            return null;
        }

        return $cached['value'] ?? null;
    }

    /**
     * Guardar valor en cache
     */
    public static function set(string $key, $value, int $ttl = 3600): bool
    {
        self::init();
        
        if (!self::$enabled) {
            return false;
        }

        $file = self::getCacheFile($key);
        
        $data = [
            'value' => $value,
            'expires' => ($ttl > 0) ? time() + $ttl : 0,
            'created' => time()
        ];

        $serialized = serialize($data);
        return @file_put_contents($file, $serialized, LOCK_EX) !== false;
    }

    /**
     * Eliminar valor del cache
     */
    public static function delete(string $key): bool
    {
        self::init();
        
        if (!self::$enabled) {
            return false;
        }

        $file = self::getCacheFile($key);
        
        if (file_exists($file)) {
            return @unlink($file);
        }
        
        return true;
    }

    /**
     * Verificar si existe una clave
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    /**
     * Limpiar todo el cache
     */
    public static function flush(): bool
    {
        self::init();
        
        if (!self::$enabled) {
            return false;
        }

        $files = glob(self::$cacheDir . '/*.cache');
        
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            @unlink($file);
        }
        
        return true;
    }

    /**
     * Remember pattern (lo más útil)
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        $cached = self::get($key);
        
        if ($cached !== null) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("💾 Cache HIT: {$key}");
            }
            return $cached;
        }

        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("🔍 Cache MISS: {$key}");
        }
        
        $value = $callback();
        self::set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * Limpiar cache expirado (ejecutar periódicamente)
     */
    public static function cleanExpired()
    {
        self::init();
        
        if (!self::$enabled) {
            return 0;
        }

        $files = glob(self::$cacheDir . '/*.cache');
        $cleaned = 0;

        foreach ($files as $file) {
            $data = @file_get_contents($file);
            if ($data === false) continue;

            $cached = unserialize($data);
            
            if (isset($cached['expires']) && $cached['expires'] > 0 && time() > $cached['expires']) {
                @unlink($file);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Obtener estadísticas
     */
    public static function stats(): array
    {
        self::init();
        
        if (!self::$enabled) {
            return [
                'available' => false,
                'error' => 'Cache directory not writable'
            ];
        }

        $files = glob(self::$cacheDir . '/*.cache');
        $totalSize = 0;
        $expired = 0;
        $active = 0;

        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $data = @file_get_contents($file);
            if ($data === false) continue;

            $cached = unserialize($data);
            
            if (isset($cached['expires']) && $cached['expires'] > 0 && time() > $cached['expires']) {
                $expired++;
            } else {
                $active++;
            }
        }

        return [
            'available' => true,
            'type' => 'file-based',
            'total_keys' => count($files),
            'active_keys' => $active,
            'expired_keys' => $expired,
            'total_size' => round($totalSize / 1024, 2) . ' KB',
            'cache_dir' => self::$cacheDir
        ];
    }

    /**
     * Verificar si cache está disponible
     */
    public static function isAvailable(): bool
    {
        self::init();
        return self::$enabled;
    }
}