<?php
/**
 * check_php.php
 * Verificador ultra-simple que SIEMPRE muestra algo
 * 
 * EJECUTAR PRIMERO: http://localhost/heiyubai/check_php.php
 */

// NO usar nada complicado, solo PHP básico
?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Check PHP</title>
    <style>
        body { font-family: Arial; background: #1a1a1a; color: #fff; padding: 20px; }
        .box { background: #2a2a2a; padding: 20px; border-radius: 10px; max-width: 800px; margin: 20px auto; }
        h1 { color: #4CAF50; }
        .ok { color: #4CAF50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        td { padding: 10px; border-bottom: 1px solid #444; }
        td:first-child { font-weight: bold; width: 200px; }
        pre { background: #1a1a1a; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="box">
        <h1>✓ PHP Funciona Correctamente</h1>
        <p>Si ves este mensaje, PHP está ejecutándose.</p>
    </div>

    <div class="box">
        <h2>Información de PHP</h2>
        <table>
            <tr>
                <td>Versión PHP:</td>
                <td class="ok"><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr>
                <td>Sistema Operativo:</td>
                <td><?php echo PHP_OS; ?></td>
            </tr>
            <tr>
                <td>Servidor:</td>
                <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
            </tr>
            <tr>
                <td>Document Root:</td>
                <td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td>
            </tr>
            <tr>
                <td>Script Path:</td>
                <td><?php echo __DIR__; ?></td>
            </tr>
            <tr>
                <td>Usuario PHP:</td>
                <td><?php echo get_current_user(); ?></td>
            </tr>
            <tr>
                <td>Display Errors:</td>
                <td class="<?php echo ini_get('display_errors') ? 'ok' : 'error'; ?>">
                    <?php echo ini_get('display_errors') ? 'Activado' : 'Desactivado'; ?>
                </td>
            </tr>
            <tr>
                <td>Error Reporting:</td>
                <td><?php echo error_reporting(); ?></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>Extensiones PHP</h2>
        <table>
            <tr>
                <td>PDO:</td>
                <td class="<?php echo extension_loaded('pdo') ? 'ok' : 'error'; ?>">
                    <?php echo extension_loaded('pdo') ? '✓ Instalado' : '✗ NO instalado'; ?>
                </td>
            </tr>
            <tr>
                <td>PDO MySQL:</td>
                <td class="<?php echo extension_loaded('pdo_mysql') ? 'ok' : 'error'; ?>">
                    <?php echo extension_loaded('pdo_mysql') ? '✓ Instalado' : '✗ NO instalado'; ?>
                </td>
            </tr>
            <tr>
                <td>JSON:</td>
                <td class="<?php echo extension_loaded('json') ? 'ok' : 'error'; ?>">
                    <?php echo extension_loaded('json') ? '✓ Instalado' : '✗ NO instalado'; ?>
                </td>
            </tr>
            <tr>
                <td>MBString:</td>
                <td class="<?php echo extension_loaded('mbstring') ? 'ok' : 'warning'; ?>">
                    <?php echo extension_loaded('mbstring') ? '✓ Instalado' : '⚠ NO instalado'; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>Archivos en el Directorio</h2>
        <pre><?php
$files = scandir(__DIR__);
$important = [
    'autoload.php',
    'bootstrap.php',
    'database.php',
    'constants.php',
    'Router.php',
    'routes.php',
    'Session.php',
    'Response.php',
    'Logger.php',
    'PedidoController.php',
    'MesaController.php',
    'test_diagnostico.php',
    'instalador_simple.php'
];

echo "Archivos importantes encontrados:\n\n";

foreach ($important as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $icon = $exists ? '✓' : '✗';
    $color = $exists ? 'ok' : 'error';
    echo "<span class='$color'>$icon $file</span>\n";
}

echo "\n\nCarpetas:\n\n";
$dirs = ['app', 'Core', 'config', 'routes', 'public', 'views', 'storage'];
foreach ($dirs as $dir) {
    $exists = is_dir(__DIR__ . '/' . $dir);
    $icon = $exists ? '✓' : '✗';
    $color = $exists ? 'ok' : 'error';
    echo "<span class='$color'>$icon $dir/</span>\n";
}
        ?></pre>
    </div>

    <div class="box">
        <h2>Test de Escritura</h2>
        <?php
        $testFile = __DIR__ . '/test_write.txt';
        $canWrite = @file_put_contents($testFile, 'test');
        
        if ($canWrite) {
            echo "<p class='ok'>✓ El directorio tiene permisos de escritura</p>";
            @unlink($testFile);
        } else {
            echo "<p class='error'>✗ NO hay permisos de escritura en el directorio</p>";
            echo "<p>Ejecuta: <code>chmod 755 " . __DIR__ . "</code></p>";
        }
        ?>
    </div>

    <div class="box">
        <h2>Siguientes Pasos</h2>
        <ol>
            <li>Si ves errores arriba, corrígelos primero</li>
            <li><a href="test_diagnostico.php" style="color: #4CAF50;">Ejecutar Diagnóstico Completo</a></li>
            <li><a href="instalador_simple.php" style="color: #4CAF50;">Ejecutar Instalador Simple</a></li>
            <li><a href="index.php" style="color: #4CAF50;">Ir al Sistema</a></li>
        </ol>
    </div>

    <div class="box">
        <h2>¿El instalador se queda en blanco?</h2>
        <p>Posibles causas:</p>
        <ul>
            <li><strong>Display Errors OFF:</strong> No se muestran errores</li>
            <li><strong>Error fatal en PHP:</strong> Revisa logs del servidor</li>
            <li><strong>Timeout:</strong> El script tarda mucho</li>
            <li><strong>Archivos faltantes:</strong> Faltan archivos necesarios</li>
        </ul>
        <p>Solución: Usa <strong>instalador_simple.php</strong> que muestra el progreso en tiempo real.</p>
    </div>
</body>
</html>