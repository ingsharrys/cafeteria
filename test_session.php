<?php
/**
 * debug_api.php
 * Debug para encontrar el error HTTP 500
 * 
 * Abre: https://heiyubai.datarie.info/debug_api.php
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>🔍 Debug API Error</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .box { background: #252526; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007acc; }
        .ok { border-left-color: #28a745; }
        .error { border-left-color: #dc3545; }
        h1 { color: #4ec9b0; }
        pre { background: #1e1e1e; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>

<h1>🔍 Debug API - Encontrar Error HTTP 500</h1>

<?php

// 1. Cargar Database
echo '<div class="box ok"><h2>1️⃣ Cargar Database.php</h2>';
try {
    require_once __DIR__ . '/config/database.php';
    echo '✅ Database.php cargado<br>';
    $db = Database::getInstance()->getConnection();
    echo '✅ Conexión BD OK<br>';
} catch (Throwable $e) {
    echo '<div class="box error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    die();
}
echo '</div>';

// 2. Cargar DashboardApiController
echo '<div class="box ok"><h2>2️⃣ Cargar DashboardApiController</h2>';
try {
    require_once __DIR__ . '/DashboardApiController.php';
    echo '✅ DashboardApiController.php cargado<br>';
} catch (Throwable $e) {
    echo '<div class="box error">❌ Error cargando DashboardApiController:<br>';
    echo htmlspecialchars($e->getMessage()) . '<br>';
    echo 'File: ' . htmlspecialchars($e->getFile()) . '<br>';
    echo 'Line: ' . $e->getLine() . '</div>';
    die();
}
echo '</div>';

// 3. Simular llamada a API
echo '<div class="box ok"><h2>3️⃣ Simular Llamada API</h2>';
try {
    // Simular POST a /api.php?route=turnos/estado
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_GET['route'] = 'turnos/estado';
    
    // Crear instancia del controller
    $controller = new DashboardApiController($db);
    
    echo '✅ Controller instanciado<br>';
    echo '✅ Llamada API lista para procesar<br>';
    
} catch (Throwable $e) {
    echo '<div class="box error">❌ Error:<br>';
    echo htmlspecialchars($e->getMessage()) . '<br>';
    echo 'File: ' . htmlspecialchars($e->getFile()) . '<br>';
    echo 'Line: ' . $e->getLine() . '<br>';
    echo 'Trace: <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
    die();
}
echo '</div>';

// 4. Ver error_log
echo '<div class="box ok"><h2>4️⃣ Revisar Error Log</h2>';
echo '<p>Abre en navegador: <code>https://heiyubai.datarie.info/ver_error_log.php</code></p>';
echo '<p>Y busca líneas con "sendWhatsAppAndLog" o "WhatsAppService"</p>';
echo '</div>';

// 5. Resumen
echo '<div class="box ok"><h2>✅ RESUMEN</h2>';
echo '✅ Database.php cargado<br>';
echo '✅ Conexión BD OK<br>';
echo '✅ DashboardApiController cargado<br>';
echo '✅ Controller instanciado<br>';
echo '<br>';
echo '<strong>Si ves TODO VERDE arriba:</strong><br>';
echo '→ El error está en el método cambiarEstadoTurno()<br>';
echo '→ Abre ver_error_log.php y busca el error exacto<br>';
echo '</div>';

?>

</body>
</html>