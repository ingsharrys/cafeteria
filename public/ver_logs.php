<?php
/**
 * ver_logs.php
 * UBICACIÓN: heiyubai/public/ver_logs.php
 * 
 * Accede en: https://heiyubai.datarie.info/public/ver_logs.php
 * 
 * Muestra los últimos logs de app.log para ver qué pasa en header_controller
 */

// Ruta del archivo de log
$logFile = dirname(dirname(__DIR__)) . '/app.log';
$logFile2 = dirname(__DIR__) . '/app.log';

// Buscar dónde existe el archivo
if (!file_exists($logFile)) {
    $logFile = $logFile2;
}

if (!file_exists($logFile)) {
    die('❌ Archivo app.log no encontrado en: <br>' . $logFile . '<br>' . $logFile2);
}

// Leer últimas 100 líneas del log
$lines = file($logFile);
$lastLines = array_slice($lines, -100);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Ver Logs - Header Controller</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #0f0; padding: 20px; }
        pre { background: #000; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .debug { color: #0f0; }
        .error { color: #f00; }
        .success { color: #0f0; }
        .info { color: #0ff; }
        button { padding: 10px 20px; background: #0f0; color: #000; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>📋 Logs de header_controller.php</h1>
    <button onclick="location.reload()">🔄 Refrescar</button>
    <button onclick="limpiarLogs()">🗑️ Limpiar Logs</button>
    
    <h2>Últimos 100 registros:</h2>
    <pre>
<?php
foreach ($lastLines as $line) {
    $line = trim($line);
    
    // Colorear según el tipo
    if (strpos($line, '❌') !== false || strpos($line, 'ERROR') !== false) {
        echo "<span class='error'>" . htmlspecialchars($line) . "</span>\n";
    } elseif (strpos($line, '✅') !== false || strpos($line, 'exitosa') !== false) {
        echo "<span class='success'>" . htmlspecialchars($line) . "</span>\n";
    } elseif (strpos($line, '🔍') !== false || strpos($line, 'DEBUG') !== false) {
        echo "<span class='debug'>" . htmlspecialchars($line) . "</span>\n";
    } elseif (strpos($line, '═') !== false) {
        echo "<span class='info'>" . htmlspecialchars($line) . "</span>\n";
    } else {
        echo htmlspecialchars($line) . "\n";
    }
}
?>
    </pre>

    <h2>📊 Resumen Rápido:</h2>
    <pre>
<?php
// Buscar último resumen
$searchStart = false;
foreach (array_reverse($lastLines) as $line) {
    if (strpos($line, 'RESULTADO FINAL:') !== false) {
        $searchStart = true;
    }
    if ($searchStart) {
        echo htmlspecialchars($line) . "\n";
        if (strpos($line, '═') !== false && $searchStart) {
            break;
        }
    }
}
?>
    </pre>

    <script>
        function limpiarLogs() {
            if (confirm('¿Limpiar los logs?')) {
                fetch('limpiar_logs.php')
                    .then(r => r.text())
                    .then(data => {
                        alert(data);
                        location.reload();
                    });
            }
        }
    </script>
</body>
</html>