<?php
/**
 * api.php - Router delgado de la API REST
 * UBICACIÓN: heiyubai/api.php
 * 
 * ✅ ~130 líneas — carga SOLO el controller necesario por request
 * ✅ Sesión IDÉNTICA a Core\Session (mismo save_path, cookie_params, session_name)
 * ✅ Cada controller: ~150-300 líneas (dominio específico)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
date_default_timezone_set('America/Bogota');


$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// ─── Sesión (DEBE coincidir exactamente con Core\Session::start()) ────
// Si no coinciden save_path y cookie_params, api.php y public/index.php
// usan sesiones DIFERENTES y el modal de validación nunca persiste.
if (session_status() === PHP_SESSION_NONE) {

    // 1) save_path: igual que Core\Session → STORAGE_PATH/sessions
    //    STORAGE_PATH = ROOT_PATH/storage, ROOT_PATH = __DIR__ (heiyubai/)
    $sessDir = __DIR__ . '/storage/sessions';
    if (!is_dir($sessDir)) {
        @mkdir($sessDir, 0755, true);
    }
    @ini_set('session.save_path', $sessDir);

    // 2) gc settings
    ini_set('session.gc_maxlifetime', 14400);
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);

    // 3) Cookie params: igual que Core\Session
    $domain  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
               || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 14400,
        'path'     => '/',
        'domain'   => $domain,
        'secure'   => $isHttps,
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);

    // 4) Mismo nombre de sesión
    session_name('secure_session_id');

    // 5) Iniciar
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$route  = $_GET['route'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// ─── Conexión BD (lazy singleton) ─────────────────
$dbFile = null;
foreach ([__DIR__.'/config/database.php', __DIR__.'/database.php', dirname(__DIR__).'/config/database.php'] as $p) {
    if (file_exists($p)) { $dbFile = $p; break; }
}
if (!$dbFile) { http_response_code(500); echo json_encode(['success'=>false,'error'=>'database.php no encontrado']); exit; }

require_once $dbFile;

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'error'=>'BD: '.$e->getMessage()]);
    exit;
}

// ─── Dispatch: solo carga el controller necesario ─
$prefix = explode('/', $route)[0];

try {
    
    
    
    
    
    
    switch ($prefix) {
        
         case 'test-file-check':
            $files = [
                'ZonaController' => __DIR__ . '/app/controllers/ZonaController.php',
                'BarrioController' => __DIR__ . '/app/controllers/BarrioController.php',
                'Zona' => __DIR__ . '/app/models/Zona.php',
                'Barrio' => __DIR__ . '/app/models/Barrio.php',
            ];
            
            $result = [];
            foreach ($files as $name => $path) {
                $result[$name] = [
                    'exists' => file_exists($path),
                    'path' => $path
                ];
            }
            
            echo json_encode(['success' => true, 'files' => $result]);
            break;

        case 'auth':
            require_once __DIR__ . '/app/controllers/AuthApiController.php';
            (new AuthApiController($db))->handle($route, $method);
            break;

        case 'mesas':
        case 'turnos':
        case 'productos':
            require_once __DIR__ . '/app/controllers/DashboardApiController.php';
            (new DashboardApiController($db))->handle($route, $method);
            break;

        case 'edit':
            require_once __DIR__ . '/app/controllers/EditPedidoApiController.php';
            (new EditPedidoApiController($db))->handle($route, $method);
            break;

        case 'caja':
            require_once __DIR__ . '/app/controllers/CajaApiController.php';
            (new CajaApiController($db))->handle($route, $method);
            break;

        case 'catalogo':
            require_once __DIR__ . '/app/controllers/CatalogoApiController.php';
            (new CatalogoApiController($db))->handle($route, $method);
            break;
            
         case 'meseros':
            require_once __DIR__ . '/app/controllers/MeseroApiController.php';
            (new MeseroApiController($db))->handle($route, $method);
            break;    

        case 'domiciliarios':
            require_once __DIR__ . '/app/controllers/DomiciliarioApiController.php';
            (new DomiciliarioApiController($db))->handle($route, $method);
            break;

        case 'reporte':
            require_once __DIR__ . '/app/controllers/ReporteApiController.php';
            (new ReporteApiController($db))->handle($route, $method);
            break;
            
        case 'zonas':
            require_once __DIR__ . '/app/controllers/ZonaController.php';
            $c = new \App\Controllers\ZonaController($db);
            $c->handle($route, $method);
            break;

        case 'barrios':
            require_once __DIR__ . '/app/controllers/BarrioController.php';
            $c = new \App\Controllers\BarrioController($db);
            $c->handle($route, $method);
            break;    
            
        

        case 'debug':
            if ($route === 'debug/tables') {
                echo json_encode(['success'=>true, 'tables'=>$db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN)]);
            } elseif ($route === 'debug/test') {
                echo json_encode([
                    'success'    => true, 
                    'message'    => 'API OK', 
                    'php'        => PHP_VERSION, 
                    'session_id' => session_id(),
                    'save_path'  => ini_get('session.save_path'),
                    'cajero'     => $_SESSION['cajero'] ?? null,
                ]);
            } else {
                echo json_encode(['success'=>false, 'error'=>'Debug route no encontrada']);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['success'=>false, 'error'=>"Ruta no encontrada: {$method} {$route}"]);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("API Error [{$route}]: " . $e->getMessage());
    echo json_encode(['success'=>false, 'error'=>'Error interno: '.$e->getMessage()]);
}