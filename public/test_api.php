<?php
/**
 * test_api.php - Test de endpoints del API
 * UBICACIÓN: /public/test_api.php
 * 
 * Acceder: http://localhost/heiyubai/public/test_api.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar bootstrap
require_once __DIR__ . '/../bootstrap.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test API - HeiYuBai</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow: auto; }
    </style>
</head>
<body>
<div class='container mt-4'>
    <h1>🧪 Test API - HeiYuBai MVC</h1>
    <hr>";

// Base URL del API
$baseUrl = 'http://localhost/heiyubai/public/api.php';

// Tests a ejecutar
$tests = [
    [
        'name' => 'Conexión a Base de Datos',
        'type' => 'db',
    ],
    [
        'name' => 'GET Mesas',
        'url' => $baseUrl . '?route=mesas',
        'method' => 'GET',
        'expected' => 'mesas'
    ],
    [
        'name' => 'GET Turnos (tipo 51 - Mesa)',
        'url' => $baseUrl . '?route=turnos&tipo_solicitud=51',
        'method' => 'GET',
        'expected' => 'turnos'
    ],
    [
        'name' => 'GET Turnos (tipo 50 - Domicilio)',
        'url' => $baseUrl . '?route=turnos&tipo_solicitud=50',
        'method' => 'GET',
        'expected' => 'turnos'
    ],
    [
        'name' => 'GET Turnos (tipo 53 - Recoger)',
        'url' => $baseUrl . '?route=turnos&tipo_solicitud=53',
        'method' => 'GET',
        'expected' => 'turnos'
    ],
    [
        'name' => 'GET Repartidores',
        'url' => $baseUrl . '?route=domicilios/repartidores',
        'method' => 'GET',
        'expected' => 'domiciliarios'
    ],
    [
        'name' => 'GET Base Caja',
        'url' => $baseUrl . '?route=caja/base',
        'method' => 'GET',
        'expected' => 'base'
    ],
    [
        'name' => 'Verificar archivo api.php existe',
        'type' => 'file',
        'path' => __DIR__ . '/api.php'
    ],
    [
        'name' => 'Verificar dashboard.config.js existe',
        'type' => 'file',
        'path' => __DIR__ . '/js/config/dashboard.config.js'
    ],
];

$passed = 0;
$failed = 0;
$warnings = 0;

echo "<table class='table table-striped'>
<thead><tr><th>Test</th><th>Resultado</th><th>Detalles</th></tr></thead>
<tbody>";

foreach ($tests as $test) {
    $result = '';
    $details = '';
    $status = 'error';
    
    try {
        // Test de base de datos
        if (isset($test['type']) && $test['type'] === 'db') {
            $db = Database::getInstance()->getConnection();
            if ($db) {
                $status = 'success';
                $result = '✅ Conectado';
                $details = 'PDO conectado correctamente';
            }
        }
        // Test de archivo
        elseif (isset($test['type']) && $test['type'] === 'file') {
            if (file_exists($test['path'])) {
                $status = 'success';
                $result = '✅ Existe';
                $details = basename($test['path']);
            } else {
                $status = 'error';
                $result = '❌ No existe';
                $details = $test['path'];
            }
        }
        // Test de API
        else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $test['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            if ($test['method'] === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['data'] ?? []));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                $status = 'error';
                $result = "❌ Error cURL";
                $details = $error;
            } elseif ($httpCode === 404) {
                $status = 'error';
                $result = "❌ 404 Not Found";
                $details = "El archivo api.php no existe o la ruta es incorrecta";
            } elseif ($httpCode >= 200 && $httpCode < 300) {
                $json = json_decode($response, true);
                
                if (isset($json[$test['expected']]) || isset($json['success'])) {
                    $status = 'success';
                    $result = "✅ HTTP $httpCode";
                    $count = is_array($json[$test['expected']] ?? null) ? count($json[$test['expected']]) : 'N/A';
                    $details = "Encontrados: $count registros";
                } else {
                    $status = 'warning';
                    $result = "⚠️ HTTP $httpCode";
                    $details = "Respuesta: " . substr($response, 0, 100);
                }
            } else {
                $status = 'error';
                $result = "❌ HTTP $httpCode";
                $details = substr($response, 0, 200);
            }
        }
    } catch (Exception $e) {
        $status = 'error';
        $result = '❌ Excepción';
        $details = $e->getMessage();
    }
    
    if ($status === 'success') $passed++;
    elseif ($status === 'warning') $warnings++;
    else $failed++;
    
    echo "<tr>
        <td>{$test['name']}</td>
        <td class='$status'>$result</td>
        <td><small>$details</small></td>
    </tr>";
}

echo "</tbody></table>";

// Resumen
echo "<div class='alert " . ($failed === 0 ? 'alert-success' : 'alert-warning') . "'>
    <strong>Resumen:</strong> 
    <span class='success'>✅ $passed pasaron</span> | 
    <span class='warning'>⚠️ $warnings advertencias</span> | 
    <span class='error'>❌ $failed fallaron</span>
</div>";

// Información de debug
echo "<h3>📋 Información de Debug</h3>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "Script Path: " . __FILE__ . "\n";
echo "API Path: " . __DIR__ . "/api.php\n";
echo "API Exists: " . (file_exists(__DIR__ . '/api.php') ? 'SÍ' : 'NO') . "\n";
echo "</pre>";

// Verificar estructura de archivos
echo "<h3>📁 Estructura de Archivos Requerida</h3>";
$archivos = [
    '/public/api.php' => 'Router API principal',
    '/public/js/config/dashboard.config.js' => 'Configuración JS',
    '/bootstrap.php' => 'Bootstrap de la aplicación',
    '/config/database.php' => 'Conexión a BD',
    '/core/Session.php' => 'Manejo de sesiones',
];

echo "<table class='table table-sm'>
<thead><tr><th>Archivo</th><th>Estado</th><th>Descripción</th></tr></thead>
<tbody>";

foreach ($archivos as $archivo => $desc) {
    $fullPath = dirname(__DIR__) . $archivo;
    $existe = file_exists($fullPath);
    $icon = $existe ? '✅' : '❌';
    $class = $existe ? 'success' : 'error';
    echo "<tr>
        <td><code>$archivo</code></td>
        <td class='$class'>$icon</td>
        <td>$desc</td>
    </tr>";
}

echo "</tbody></table>";

// Test manual de endpoint
echo "<h3>🔧 Test Manual de Endpoints</h3>";
echo "<div class='row'>";

$endpoints = [
    'Mesas' => 'api.php?route=mesas',
    'Turnos Mesa' => 'api.php?route=turnos&tipo_solicitud=51',
    'Turnos Domicilio' => 'api.php?route=turnos&tipo_solicitud=50',
    'Turnos Recoger' => 'api.php?route=turnos&tipo_solicitud=53',
    'Repartidores' => 'api.php?route=domicilios/repartidores',
    'Base Caja' => 'api.php?route=caja/base',
];

foreach ($endpoints as $name => $url) {
    echo "<div class='col-md-4 mb-2'>
        <a href='$url' target='_blank' class='btn btn-outline-primary btn-sm w-100'>
            $name
        </a>
    </div>";
}

echo "</div>";

echo "</div>
</body>
</html>";