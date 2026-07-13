<?php
/**
 * verificar_estructura.php
 * Verifica que todas las carpetas y archivos necesarios existan
 * Sube a la raíz: /verificar_estructura.php
 * Accede: http://localhost/heiyubai/verificar_estructura.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Verificación de Estructura del Proyecto</h1>";
echo "<hr>";

$rootPath = __DIR__;

echo "<h2>1. Carpetas principales</h2>";

$carpetas = [
    'controllers',
    'config',
    'core',
    'app',
    'app/Controllers',
    'app/Models',
    'views',
    'views/inc',
    'public',
    'storage',
    'storage/logs',
    'storage/sessions',
];

foreach ($carpetas as $carpeta) {
    $fullPath = $rootPath . '/' . $carpeta;
    if (is_dir($fullPath)) {
        $permisos = substr(sprintf('%o', fileperms($fullPath)), -4);
        echo "✅ <code>/$carpeta</code> - Existe (Permisos: $permisos)<br>";
    } else {
        echo "❌ <code>/$carpeta</code> - <strong style='color: red;'>NO EXISTE</strong><br>";
    }
}

echo "<hr>";
echo "<h2>2. Archivos críticos</h2>";

$archivos = [
    'bootstrap.php',
    'autoload.php',
    'config/app.php',
    'config/database.php',
    'config/constants.php',
    'core/Session.php',
    'core/Token.php',
    'public/index.php',
    'views/login_new.php',
    'controllers/validar_codigo.php',  // ← EL ARCHIVO QUE DA 404
];

foreach ($archivos as $archivo) {
    $fullPath = $rootPath . '/' . $archivo;
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        $permisos = substr(sprintf('%o', fileperms($fullPath)), -4);
        echo "✅ <code>/$archivo</code> - Existe ($size bytes, Permisos: $permisos)<br>";
    } else {
        echo "❌ <code>/$archivo</code> - <strong style='color: red;'>NO EXISTE</strong><br>";
    }
}

echo "<hr>";
echo "<h2>3. Crear carpetas faltantes</h2>";

$carpetasCrear = ['controllers', 'storage/logs', 'storage/sessions'];

foreach ($carpetasCrear as $carpeta) {
    $fullPath = $rootPath . '/' . $carpeta;
    if (!is_dir($fullPath)) {
        if (@mkdir($fullPath, 0755, true)) {
            echo "✅ Carpeta <code>/$carpeta</code> creada correctamente<br>";
        } else {
            echo "❌ No se pudo crear <code>/$carpeta</code><br>";
        }
    }
}

echo "<hr>";
echo "<h2>4. Contenido de /controllers/</h2>";

$controllersPath = $rootPath . '/controllers';
if (is_dir($controllersPath)) {
    $files = scandir($controllersPath);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "<li><code>$file</code></li>";
        }
    }
    echo "</ul>";
    
    if (count($files) <= 2) {
        echo "<div style='background: #ffcccc; padding: 10px; margin: 10px 0;'>";
        echo "⚠️ La carpeta /controllers/ está vacía o solo tiene . y ..<br>";
        echo "<strong>ACCIÓN:</strong> Debes subir el archivo <code>validar_codigo.php</code> a esta carpeta.";
        echo "</div>";
    }
} else {
    echo "❌ La carpeta /controllers/ NO EXISTE<br>";
    echo "<div style='background: #ffcccc; padding: 10px; margin: 10px 0;'>";
    echo "<strong>ACCIÓN:</strong> Crea la carpeta <code>/controllers/</code> y sube <code>validar_codigo.php</code> ahí.";
    echo "</div>";
}

echo "<hr>";
echo "<h2>5. URL que intenta el modal</h2>";
echo "<code>POST http://localhost/heiyubai/controllers/validar_codigo.php</code><br>";
echo "Ruta física que debería existir: <code>" . $rootPath . "/controllers/validar_codigo.php</code><br>";

$validarPath = $rootPath . '/controllers/validar_codigo.php';
if (file_exists($validarPath)) {
    echo "<div style='background: #ccffcc; padding: 10px; margin: 10px 0;'>";
    echo "✅ El archivo <strong>SÍ EXISTE</strong> en la ubicación correcta";
    echo "</div>";
} else {
    echo "<div style='background: #ffcccc; padding: 10px; margin: 10px 0;'>";
    echo "❌ El archivo <strong>NO EXISTE</strong> en la ubicación esperada<br>";
    echo "<strong>SOLUCIÓN:</strong> Sube el archivo <code>validar_codigo.php</code> a la carpeta <code>/controllers/</code>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>✅ CONCLUSIÓN</h2>";

if (!is_dir($rootPath . '/controllers')) {
    echo "<div style='background: #ffffcc; padding: 15px; border-left: 4px solid orange;'>";
    echo "<h3>⚠️ PROBLEMA ENCONTRADO</h3>";
    echo "<p>La carpeta <code>/controllers/</code> NO EXISTE en tu proyecto.</p>";
    echo "<h4>SOLUCIÓN:</h4>";
    echo "<ol>";
    echo "<li>Crea la carpeta <code>/controllers/</code> en la raíz de tu proyecto</li>";
    echo "<li>Sube el archivo <code>validar_codigo.php</code> dentro de esa carpeta</li>";
    echo "<li>Verifica que la ruta sea: <code>/controllers/validar_codigo.php</code></li>";
    echo "</ol>";
    echo "</div>";
} elseif (!file_exists($validarPath)) {
    echo "<div style='background: #ffffcc; padding: 15px; border-left: 4px solid orange;'>";
    echo "<h3>⚠️ PROBLEMA ENCONTRADO</h3>";
    echo "<p>La carpeta <code>/controllers/</code> existe pero el archivo <code>validar_codigo.php</code> NO está dentro.</p>";
    echo "<h4>SOLUCIÓN:</h4>";
    echo "<ol>";
    echo "<li>Sube el archivo <code>validar_codigo.php</code> a <code>/controllers/</code></li>";
    echo "<li>Verifica que el nombre sea exactamente <code>validar_codigo.php</code> (sin espacios ni mayúsculas)</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #ccffcc; padding: 15px; border-left: 4px solid green;'>";
    echo "<h3>✅ TODO CORRECTO</h3>";
    echo "<p>La estructura está bien. Si aún da error 404, puede ser un problema de permisos o caché.</p>";
    echo "<h4>SOLUCIÓN:</h4>";
    echo "<ol>";
    echo "<li>Limpia la caché del navegador (Ctrl+Shift+Del)</li>";
    echo "<li>Recarga la página con Ctrl+F5</li>";
    echo "<li>Verifica que Apache/XAMPP esté corriendo</li>";
    echo "</ol>";
    echo "</div>";
}
?>