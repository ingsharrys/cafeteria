<?php
/**
 * DIAGNÓSTICO COMPLETO
 * Sube este archivo a la RAÍZ de tu proyecto
 * Accede: http://localhost/heiyubai/diagnostico.php
 */

// Mostrar TODOS los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Diagnóstico del Sistema</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        code { background: #eee; padding: 2px 5px; border-radius: 3px; }
        pre { background: #333; color: #0f0; padding: 10px; overflow: auto; }
    </style>
</head>
<body>";

echo "<h1>🔍 Diagnóstico Completo del Sistema</h1>";
echo "<hr>";

// ====================================
// TEST 1: Información de PHP
// ====================================
echo "<div class='section'>";
echo "<h2>1. Información de PHP</h2>";
echo "Versión de PHP: <strong>" . phpversion() . "</strong><br>";
echo "Sistema Operativo: <strong>" . PHP_OS . "</strong><br>";
echo "Directorio actual: <strong>" . __DIR__ . "</strong><br>";
echo "</div>";

// ====================================
// TEST 2: Estructura de carpetas
// ====================================
echo "<div class='section'>";
echo "<h2>2. Verificando estructura de carpetas</h2>";

$folders = [
    'app' => __DIR__ . '/app',
    'app/Controllers' => __DIR__ . '/app/Controllers',
    'app/Models' => __DIR__ . '/app/Models',
    'app/Views' => __DIR__ . '/app/Views',
    'config' => __DIR__ . '/config',
    'core' => __DIR__ . '/core',
    'public' => __DIR__ . '/public',
    'views' => __DIR__ . '/views',
    'storage' => __DIR__ . '/storage',
    'storage/logs' => __DIR__ . '/storage/logs',
    'storage/sessions' => __DIR__ . '/storage/sessions',
];

foreach ($folders as $name => $path) {
    $exists = is_dir($path);
    $writable = $exists ? is_writable($path) : false;
    
    echo $exists 
        ? "<span class='ok'>✓</span> /$name " 
        : "<span class='error'>✗</span> /$name <strong>NO EXISTE</strong> ";
    
    if ($exists) {
        echo $writable 
            ? "<span class='ok'>(escribible)</span>" 
            : "<span class='warning'>(NO escribible)</span>";
    }
    echo "<br>";
}
echo "</div>";

// ====================================
// TEST 3: Archivos críticos
// ====================================
echo "<div class='section'>";
echo "<h2>3. Verificando archivos críticos</h2>";

$files = [
    '.env' => __DIR__ . '/.env',
    'bootstrap.php' => __DIR__ . '/bootstrap.php',
    'autoload.php' => __DIR__ . '/autoload.php',
    'config/constants.php' => __DIR__ . '/config/constants.php',
    'config/app.php' => __DIR__ . '/config/app.php',
    'config/database.php' => __DIR__ . '/config/database.php',
    'core/Session.php' => __DIR__ . '/core/Session.php',
    'core/Token.php' => __DIR__ . '/core/Token.php',
    'core/Validator.php' => __DIR__ . '/core/Validator.php',
    'core/Logger.php' => __DIR__ . '/core/Logger.php',
    'core/Response.php' => __DIR__ . '/core/Response.php',
    'core/RateLimiter.php' => __DIR__ . '/core/RateLimiter.php',
    'app/Controllers/AuthController.php' => __DIR__ . '/app/Controllers/AuthController.php',
    'app/Models/User.php' => __DIR__ . '/app/Models/User.php',
    'views/login_new.php' => __DIR__ . '/views/login_new.php',
    'public/index.php' => __DIR__ . '/public/index.php',
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "<span class='ok'>✓</span> /$name ";
        
        // Verificar sintaxis PHP
        $content = file_get_contents($path);
        
        // Verificar namespace
        if (strpos($content, 'namespace') !== false) {
            preg_match('/namespace\s+([^;]+);/', $content, $matches);
            if (isset($matches[1])) {
                echo "<small>(namespace: <code>{$matches[1]}</code>)</small>";
            }
        }
        
        // Verificar errores de sintaxis
        $result = exec("php -l " . escapeshellarg($path) . " 2>&1", $output, $return);
        if ($return !== 0) {
            echo " <span class='error'>ERROR DE SINTAXIS</span>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
        }
        
        echo "<br>";
    } else {
        echo "<span class='error'>✗</span> /$name <strong>NO EXISTE</strong><br>";
    }
}
echo "</div>";

// ====================================
// TEST 4: Intentar cargar bootstrap
// ====================================
echo "<div class='section'>";
echo "<h2>4. Intentando cargar bootstrap.php</h2>";

if (file_exists(__DIR__ . '/bootstrap.php')) {
    echo "Archivo bootstrap.php encontrado. Intentando cargar...<br><br>";
    
    try {
        ob_start();
        require_once __DIR__ . '/bootstrap.php';
        $output = ob_get_clean();
        
        echo "<span class='ok'>✓ Bootstrap cargado correctamente</span><br>";
        
        if (!empty($output)) {
            echo "<strong>Output generado:</strong><br>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
        }
        
    } catch (Error $e) {
        ob_end_clean();
        echo "<span class='error'>✗ ERROR al cargar bootstrap:</span><br>";
        echo "<strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
        echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } catch (Exception $e) {
        ob_end_clean();
        echo "<span class='error'>✗ EXCEPCIÓN al cargar bootstrap:</span><br>";
        echo "<strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
        echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
} else {
    echo "<span class='error'>✗ bootstrap.php NO EXISTE</span><br>";
}
echo "</div>";

// ====================================
// TEST 5: Verificar clases
// ====================================
echo "<div class='section'>";
echo "<h2>5. Verificando clases disponibles</h2>";

$classes = [
    'Config\\Config',
    'Core\\Session',
    'Core\\Token',
    'Core\\Validator',
    'Core\\RateLimiter',
    'Core\\Logger',
    'Core\\Response',
    'App\\Controllers\\AuthController',
    'App\\Models\\User',
    'Database', // Sin namespace
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "<span class='ok'>✓</span> Clase <code>$class</code> disponible<br>";
    } else {
        echo "<span class='error'>✗</span> Clase <code>$class</code> NO encontrada<br>";
    }
}
echo "</div>";

// ====================================
// TEST 6: Verificar conexión a BD
// ====================================
echo "<div class='section'>";
echo "<h2>6. Verificando conexión a base de datos</h2>";

try {
    if (class_exists('Database')) {
        $database = new Database();
        $db = $database->getConnection();
        echo "<span class='ok'>✓ Conexión a base de datos exitosa</span><br>";
        
        // Verificar tabla users
        $query = "SHOW TABLES LIKE 'users'";
        $stmt = $db->query($query);
        if ($stmt->rowCount() > 0) {
            echo "<span class='ok'>✓ Tabla 'users' existe</span><br>";
            
            // Verificar estructura
            $query = "DESCRIBE users";
            $stmt = $db->query($query);
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "Columnas de la tabla users:<br>";
            echo "<ul>";
            foreach ($columns as $col) {
                echo "<li><code>{$col['Field']}</code> - {$col['Type']}</li>";
            }
            echo "</ul>";
            
        } else {
            echo "<span class='error'>✗ Tabla 'users' NO existe</span><br>";
        }
        
    } else {
        echo "<span class='error'>✗ Clase Database no está disponible</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Error de conexión:</span> " . htmlspecialchars($e->getMessage()) . "<br>";
}
echo "</div>";

// ====================================
// TEST 7: Verificar .env
// ====================================
echo "<div class='section'>";
echo "<h2>7. Verificando archivo .env</h2>";

if (file_exists(__DIR__ . '/.env')) {
    echo "<span class='ok'>✓ Archivo .env existe</span><br><br>";
    
    $envContent = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $envContent);
    
    echo "<strong>Variables en .env:</strong><br>";
    echo "<ul>";
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Ocultar valores sensibles
            if (in_array($key, ['DB_PASS', 'RECAPTCHA_SECRET_KEY'])) {
                $value = str_repeat('*', strlen($value));
            }
            
            echo "<li><code>$key</code> = " . htmlspecialchars($value) . "</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<span class='error'>✗ Archivo .env NO existe</span><br>";
}
echo "</div>";

// ====================================
// TEST 8: Simular carga de login_new.php
// ====================================
echo "<div class='section'>";
echo "<h2>8. Simulando carga de login_new.php</h2>";

if (file_exists(__DIR__ . '/views/login_new.php')) {
    echo "Intentando cargar /views/login_new.php...<br><br>";
    
    try {
        ob_start();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        require __DIR__ . '/views/login_new.php';
        $output = ob_get_clean();
        
        echo "<span class='ok'>✓ login_new.php cargado sin errores</span><br>";
        echo "<small>Longitud del output: " . strlen($output) . " bytes</small><br>";
        
    } catch (Error $e) {
        ob_end_clean();
        echo "<span class='error'>✗ ERROR al cargar login_new.php:</span><br>";
        echo "<strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
        echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } catch (Exception $e) {
        ob_end_clean();
        echo "<span class='error'>✗ EXCEPCIÓN al cargar login_new.php:</span><br>";
        echo "<strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "<strong>Archivo:</strong> " . $e->getFile() . "<br>";
        echo "<strong>Línea:</strong> " . $e->getLine() . "<br>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
} else {
    echo "<span class='error'>✗ /views/login_new.php NO existe</span><br>";
}
echo "</div>";

// ====================================
// TEST 9: Logs de PHP
// ====================================
echo "<div class='section'>";
echo "<h2>9. Últimas líneas del error_log de PHP</h2>";

$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    echo "Ubicación: <code>$errorLog</code><br><br>";
    $lines = file($errorLog);
    $lastLines = array_slice($lines, -20);
    
    echo "<pre>" . htmlspecialchars(implode("", $lastLines)) . "</pre>";
} else {
    echo "No se encontró archivo error_log<br>";
    echo "Buscando en ubicaciones comunes...<br>";
    
    $commonLogs = [
        'C:/xampp/apache/logs/error.log',
        'C:/xampp/php/logs/php_error_log',
        __DIR__ . '/error_log',
        __DIR__ . '/storage/logs/error.log',
    ];
    
    foreach ($commonLogs as $log) {
        if (file_exists($log)) {
            echo "<br>Encontrado: <code>$log</code><br>";
            $lines = file($log);
            $lastLines = array_slice($lines, -10);
            echo "<pre>" . htmlspecialchars(implode("", $lastLines)) . "</pre>";
            break;
        }
    }
}
echo "</div>";

// ====================================
// RESUMEN FINAL
// ====================================
echo "<div class='section'>";
echo "<h2>✅ Resumen</h2>";
echo "<p>Revisa los resultados arriba. Los elementos en <span class='error'>ROJO</span> son los que necesitas corregir.</p>";
echo "<p><strong>Copia TODA esta página</strong> y compártela para ayudarte mejor.</p>";
echo "</div>";

echo "</body></html>";
