<?php
/**
 * test_diagnostico.php
 * Sistema completo de diagnóstico para PideYApp
 * 
 * EJECUTAR: http://localhost/heiyubai/test_diagnostico.php
 */

// Forzar mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Deshabilitar límites de tiempo
set_time_limit(300);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico PideYApp</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .section {
            padding: 30px;
            border-bottom: 1px solid #eee;
        }
        .section:last-child { border-bottom: none; }
        .section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.8em;
            display: flex;
            align-items: center;
        }
        .section h2::before {
            content: "📋";
            margin-right: 10px;
        }
        .test {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s;
        }
        .test:hover {
            transform: translateX(5px);
        }
        .test-name {
            font-weight: 600;
            flex: 1;
        }
        .test-result {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .success .test-result {
            background: #28a745;
            color: white;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .error .test-result {
            background: #dc3545;
            color: white;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .warning .test-result {
            background: #ffc107;
            color: #333;
        }
        .info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
        }
        .info .test-result {
            background: #17a2b8;
            color: white;
        }
        .details {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 0.9em;
            font-family: monospace;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .summary-card {
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            color: white;
        }
        .summary-card.success { background: #28a745; }
        .summary-card.error { background: #dc3545; }
        .summary-card.warning { background: #ffc107; color: #333; }
        .summary-card h3 {
            font-size: 3em;
            margin-bottom: 10px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .actions {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .code {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnóstico PideYApp</h1>
            <p>Sistema completo de detección y solución de problemas</p>
        </div>

<?php

// ============================================
// CONTADORES
// ============================================
$total_tests = 0;
$passed = 0;
$failed = 0;
$warnings = 0;

// ============================================
// FUNCIÓN HELPER
// ============================================
function test($name, $condition, $error_msg = '', $details = '') {
    global $total_tests, $passed, $failed, $warnings;
    $total_tests++;
    
    if ($condition === true) {
        $passed++;
        $class = 'success';
        $result = '✓ OK';
    } elseif ($condition === 'warning') {
        $warnings++;
        $class = 'warning';
        $result = '⚠ AVISO';
    } else {
        $failed++;
        $class = 'error';
        $result = '✗ ERROR';
    }
    
    echo "<div class='test $class'>";
    echo "<span class='test-name'>$name</span>";
    echo "<span class='test-result'>$result</span>";
    echo "</div>";
    
    if ($error_msg && $condition !== true) {
        echo "<div class='details'>$error_msg</div>";
    }
    
    if ($details) {
        echo "<div class='details'>$details</div>";
    }
}

// ============================================
// SECCIÓN 1: PHP Y SERVIDOR
// ============================================
echo "<div class='section'>";
echo "<h2>Entorno PHP y Servidor</h2>";

test(
    "Versión de PHP",
    version_compare(PHP_VERSION, '7.4.0', '>='),
    "PHP " . PHP_VERSION . " - Se recomienda PHP 7.4+",
    "Versión actual: <span class='code'>" . PHP_VERSION . "</span>"
);

test(
    "Display Errors",
    ini_get('display_errors') == 1,
    "Los errores no se están mostrando. Esto puede ocultar problemas.",
    "display_errors = " . ini_get('display_errors')
);

test(
    "Error Reporting",
    error_reporting() == E_ALL,
    "Error reporting no está en E_ALL",
    "error_reporting = " . error_reporting()
);

test(
    "Extensión PDO",
    extension_loaded('pdo'),
    "PDO no está instalado. Necesario para base de datos."
);

test(
    "Extensión PDO MySQL",
    extension_loaded('pdo_mysql'),
    "PDO MySQL no está instalado."
);

test(
    "Extensión JSON",
    extension_loaded('json'),
    "JSON no está instalado. Necesario para APIs."
);

test(
    "Extensión MBString",
    extension_loaded('mbstring'),
    "MBString no está instalado. Necesario para strings multibyte."
);

echo "</div>";

// ============================================
// SECCIÓN 2: ESTRUCTURA DE ARCHIVOS
// ============================================
echo "<div class='section'>";
echo "<h2>Estructura de Archivos</h2>";

$root = __DIR__;

$archivos_criticos = [
    'autoload.php' => 'Autoloader de clases',
    'bootstrap.php' => 'Inicializador del sistema',
    'database.php' => 'Configuración de base de datos',
    'constants.php' => 'Constantes del sistema'
];

foreach ($archivos_criticos as $archivo => $desc) {
    $existe = file_exists($root . '/' . $archivo);
    test(
        "$archivo ($desc)",
        $existe,
        "Archivo no encontrado: $archivo",
        $existe ? "Ubicación: <span class='code'>$root/$archivo</span>" : ""
    );
}

// Archivos Core
$archivos_core = [
    'Session.php',
    'Response.php',
    'Logger.php',
    'Token.php',
    'Validator.php'
];

echo "<h3 style='margin-top: 20px; color: #764ba2;'>Archivos Core (pueden estar en raíz o Core/)</h3>";

foreach ($archivos_core as $archivo) {
    $en_raiz = file_exists($root . '/' . $archivo);
    $en_core = file_exists($root . '/Core/' . $archivo);
    
    $existe = $en_raiz || $en_core;
    $ubicacion = $en_raiz ? 'raíz' : ($en_core ? 'Core/' : 'no encontrado');
    
    test(
        $archivo,
        $existe,
        "Archivo no encontrado: $archivo",
        $existe ? "Ubicación: <span class='code'>$ubicacion</span>" : ""
    );
}

echo "</div>";

// ============================================
// SECCIÓN 3: PERMISOS
// ============================================
echo "<div class='section'>";
echo "<h2>Permisos de Archivos</h2>";

$directorios = [
    '',
    'storage',
    'storage/logs',
    'storage/sessions',
    'storage/cache'
];

foreach ($directorios as $dir) {
    $path = $root . '/' . $dir;
    $path = $dir === '' ? $root : $path;
    
    if (is_dir($path)) {
        $writable = is_writable($path);
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        
        test(
            $dir === '' ? "Directorio raíz" : "Directorio $dir",
            $writable,
            "No tiene permisos de escritura: $path",
            "Permisos actuales: <span class='code'>$perms</span> - " . ($writable ? "Escribible ✓" : "No escribible ✗")
        );
    } else {
        test(
            $dir === '' ? "Directorio raíz" : "Directorio $dir",
            'warning',
            "Directorio no existe: $path",
            "Se creará automáticamente si es necesario"
        );
    }
}

echo "</div>";

// ============================================
// SECCIÓN 4: BASE DE DATOS
// ============================================
echo "<div class='section'>";
echo "<h2>Conexión a Base de Datos</h2>";

try {
    // Intentar cargar configuración
    if (file_exists($root . '/database.php')) {
        require_once $root . '/database.php';
    }
    
    // Intentar conectar
    if (class_exists('Database')) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        test(
            "Conexión a MySQL",
            $conn instanceof PDO,
            "No se pudo conectar a la base de datos"
        );
        
        if ($conn instanceof PDO) {
            // Test de query
            $stmt = $conn->query("SELECT 1 as test");
            $result = $stmt->fetch();
            
            test(
                "Ejecución de queries",
                $result['test'] == 1,
                "No se pueden ejecutar queries"
            );
            
            // Verificar tablas
            $tables = ['users', 'meseros', 'mesas'];
            foreach ($tables as $table) {
                try {
                    $stmt = $conn->query("SELECT COUNT(*) FROM $table");
                    $count = $stmt->fetchColumn();
                    test(
                        "Tabla '$table'",
                        true,
                        "",
                        "Registros: <span class='code'>$count</span>"
                    );
                } catch (Exception $e) {
                    test(
                        "Tabla '$table'",
                        false,
                        "Tabla no existe o error: " . $e->getMessage()
                    );
                }
            }
        }
    } else {
        test(
            "Clase Database",
            false,
            "Clase Database no encontrada. Verifica database.php"
        );
    }
} catch (Exception $e) {
    test(
        "Base de datos",
        false,
        "Error: " . $e->getMessage()
    );
}

echo "</div>";

// ============================================
// SECCIÓN 5: AUTOLOAD Y CLASES
// ============================================
echo "<div class='section'>";
echo "<h2>Sistema de Autoload</h2>";

if (file_exists($root . '/autoload.php')) {
    require_once $root . '/autoload.php';
    
    test("autoload.php cargado", true);
    
    // Test de clases
    $clases_test = [
        'Core\\Session' => 'Core/Session.php',
        'Core\\Response' => 'Core/Response.php',
        'Core\\Logger' => 'Core/Logger.php',
        'Config\\Config' => 'config/app.php'
    ];
    
    foreach ($clases_test as $clase => $archivo) {
        $existe = class_exists($clase);
        test(
            "Clase $clase",
            $existe,
            "Clase no se puede cargar. Verifica $archivo"
        );
    }
} else {
    test(
        "autoload.php",
        false,
        "Archivo autoload.php no encontrado"
    );
}

echo "</div>";

// ============================================
// SECCIÓN 6: ARCHIVOS DEL INSTALADOR
// ============================================
echo "<div class='section'>";
echo "<h2>Archivos del Sistema de Routing</h2>";

$archivos_routing = [
    'Router.php' => 'Motor del routing',
    'routes.php' => 'Definición de rutas',
    'AuthMiddleware.php' => 'Middleware de autenticación',
    'PedidoController.php' => 'Controller de pedidos',
    'MesaController.php' => 'Controller de mesas',
    'TurnoController.php' => 'Controller de turnos',
    'MeseroController.php' => 'Controller de meseros',
    'CajaController.php' => 'Controller de caja'
];

$archivos_faltantes = [];

foreach ($archivos_routing as $archivo => $desc) {
    $existe = file_exists($root . '/' . $archivo);
    
    if (!$existe) {
        $archivos_faltantes[] = $archivo;
    }
    
    test(
        "$archivo",
        $existe ? true : 'warning',
        $existe ? "" : "Archivo no encontrado. Descárgalo de Claude.",
        $desc
    );
}

echo "</div>";

// ============================================
// SECCIÓN 7: RESUMEN
// ============================================
echo "<div class='section'>";
echo "<h2>Resumen del Diagnóstico</h2>";

echo "<div class='summary'>";
echo "<div class='summary-card success'>";
echo "<h3>$passed</h3>";
echo "<p>Tests Pasados</p>";
echo "</div>";

echo "<div class='summary-card error'>";
echo "<h3>$failed</h3>";
echo "<p>Tests Fallados</p>";
echo "</div>";

echo "<div class='summary-card warning'>";
echo "<h3>$warnings</h3>";
echo "<p>Avisos</p>";
echo "</div>";
echo "</div>";

// Estado general
$porcentaje = ($total_tests > 0) ? round(($passed / $total_tests) * 100) : 0;

echo "<div style='margin-top: 30px; padding: 20px; background: ";
if ($porcentaje >= 80) echo "#d4edda";
elseif ($porcentaje >= 50) echo "#fff3cd";
else echo "#f8d7da";
echo "; border-radius: 10px; text-align: center;'>";

echo "<h3 style='font-size: 2em; margin-bottom: 10px;'>Estado: ";
if ($porcentaje >= 80) echo "✅ BUENO";
elseif ($porcentaje >= 50) echo "⚠️ REQUIERE ATENCIÓN";
else echo "❌ CRÍTICO";
echo "</h3>";

echo "<p style='font-size: 1.2em;'>Porcentaje de éxito: <strong>$porcentaje%</strong></p>";
echo "</div>";

echo "</div>";

// ============================================
// SECCIÓN 8: ACCIONES RECOMENDADAS
// ============================================
echo "<div class='actions'>";
echo "<h2 style='color: #667eea; margin-bottom: 20px;'>Acciones Recomendadas</h2>";

if ($failed > 0) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #dc3545;'>";
    echo "<h3 style='color: #dc3545; margin-bottom: 10px;'>⚠️ Problemas Detectados</h3>";
    echo "<p>Se encontraron <strong>$failed errores</strong> que deben corregirse.</p>";
    echo "</div>";
}

if (count($archivos_faltantes) > 0) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #ffc107;'>";
    echo "<h3 style='color: #856404; margin-bottom: 10px;'>📥 Archivos Faltantes</h3>";
    echo "<p>Descarga estos archivos de Claude:</p>";
    echo "<ul style='text-align: left; margin: 10px 0;'>";
    foreach ($archivos_faltantes as $archivo) {
        echo "<li><span class='code'>$archivo</span></li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<a href='test_diagnostico.php' class='btn'>🔄 Ejecutar Diagnóstico Nuevamente</a>";

if ($porcentaje >= 80 && file_exists($root . '/INSTALAR_SISTEMA_ROUTING.php')) {
    echo "<a href='INSTALAR_SISTEMA_ROUTING.php' class='btn' style='background: #28a745;'>✅ Ejecutar Instalador</a>";
}

echo "<a href='index.php' class='btn' style='background: #17a2b8;'>🏠 Ir al Sistema</a>";

echo "</div>";

// ============================================
// INFORMACIÓN ADICIONAL
// ============================================
echo "<div class='section'>";
echo "<h2>Información del Sistema</h2>";
echo "<pre>";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Path: " . __DIR__ . "\n";
echo "PHP User: " . get_current_user() . "\n";
echo "OS: " . PHP_OS . "\n";
echo "</pre>";
echo "</div>";

?>

    </div>
</body>
</html>