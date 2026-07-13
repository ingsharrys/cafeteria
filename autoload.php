<?php
/**
 * autoload.php - Autoloader actualizado para PideYApp
 * Compatible con tu estructura existente + nuevos MVC components
 */

spl_autoload_register(function ($class) {
    $base_dir = __DIR__ . '/';
    
    // ============================================
    // MAPEO DE NAMESPACES A DIRECTORIOS
    // ============================================
    $namespace_map = [
    'App\\Controllers\\' => 'app/controllers/',
    'App\\Models\\' => 'app/models/',
    'App\\Middleware\\' => 'app/middleware/',
    'App\\' => 'app/',
    'Core\\' => 'core/',  // ← cambia a minúscula si tu carpeta es core/
    'Config\\' => 'config/',
];
    
    // Buscar el namespace que coincida
    foreach ($namespace_map as $namespace => $directory) {
        if (strpos($class, $namespace) === 0) {
            $relative_class = substr($class, strlen($namespace));
            $file = $base_dir . $directory . str_replace('\\', '/', $relative_class) . '.php';
            
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
    
    // ============================================
    // CLASES LEGACY (sin namespace)
    // ============================================
    $legacy_paths = [
        'config/',
        'Core/',
        'app/models/',
        'app/controllers/',
    ];
    
    foreach ($legacy_paths as $path) {
        $file = $base_dir . $path . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ============================================
// CARGAR ARCHIVOS CRÍTICOS
// ============================================

// 1. Constantes (PRIMERO)
if (file_exists(__DIR__ . '/config/constants.php')) {
    require_once __DIR__ . '/config/constants.php';
}

// 2. Configuración
if (file_exists(__DIR__ . '/config/app.php')) {
    require_once __DIR__ . '/config/app.php';
}

// 3. Database
if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
}

// 4. Core classes (sin namespace)
$coreFiles = [
    'Core/Session.php',
    'Core/Token.php',
    'Core/Response.php',
    'Core/Logger.php',
    'Core/Validator.php',
    'Core/RateLimiter.php',
    'Core/Cache.php'
];

foreach ($coreFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        require_once $fullPath;
    }
}

// ============================================
// LOG DE DEBUG (solo en desarrollo)
// ============================================
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_log('[AUTOLOAD] Autoloader cargado correctamente');
}