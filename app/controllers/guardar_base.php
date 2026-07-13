<?php
/**
 * guardar_base.php - CORREGIDO
 * UBICACIÓN: heiyubai/controllers/guardar_base.php
 * 
 * Guarda la base en la tabla base
 * Inicializa sesión con configuración correcta
 */

// ═══════════════════════════════════════════════════════
// INICIAR SESIÓN CON CONFIGURACIÓN CORRECTA
// ═══════════════════════════════════════════════════════
if (session_status() === PHP_SESSION_NONE) {
    // Configuración de sesión igual que en la app
    $sessDir = dirname(dirname(__DIR__)) . '/storage/sessions';
    if (!is_dir($sessDir)) {
        @mkdir($sessDir, 0755, true);
    }
    @ini_set('session.save_path', $sessDir);
    
    ini_set('session.gc_maxlifetime', 14400);
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);
    
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
               || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    session_set_cookie_params([
        'lifetime' => 14400,
        'path'     => '/',
        'domain'   => $domain,
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    
    session_name('secure_session_id');
    session_start();
}

header('Content-Type: application/json');

// ═══════════════════════════════════════════════════════
// VERIFICAR SESIÓN
// ═══════════════════════════════════════════════════════
if (!isset($_SESSION['cajero'])) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'No hay sesión',
        'debug' => [
            'session_id' => session_id(),
            'session_data' => $_SESSION ?? [],
        ]
    ]);
    exit;
}

// Obtener datos del formulario
$base = $_POST['base'] ?? null;
$cajero = $_POST['cajero'] ?? $_SESSION['cajero'];

// Validar
if (!$base || $base <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Base inválida']);
    exit;
}

if (!$cajero) {
    echo json_encode(['status' => 'error', 'message' => 'Cajero no identificado']);
    exit;
}

// ═══════════════════════════════════════════════════════
// CONEXIÓN A BD
// ═══════════════════════════════════════════════════════
try {
    // Buscar database.php
    $dbFile = dirname(__DIR__) . '/config/database.php';
    if (!file_exists($dbFile)) {
        $dbFile = dirname(dirname(__DIR__)) . '/config/database.php';
    }
    if (!file_exists($dbFile)) {
        $dbFile = dirname(dirname(dirname(__DIR__))) . '/heiyubai/config/database.php';
    }
    
    if (!file_exists($dbFile)) {
        throw new Exception('database.php no encontrado');
    }
    
    require_once $dbFile;
    $db = Database::getInstance()->getConnection();
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

// ═══════════════════════════════════════════════════════
// INSERTAR EN TABLA BASE
// ═══════════════════════════════════════════════════════
try {
    $sql = "INSERT INTO base (base, fechab, cajero_base) 
            VALUES (:base, CURDATE(), :cajero)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':base', $base, PDO::PARAM_INT);
    $stmt->bindParam(':cajero', $cajero, PDO::PARAM_STR);
    $stmt->execute();
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Base guardada correctamente',
        'data' => [
            'base' => $base,
            'cajero' => $cajero,
            'fecha' => date('Y-m-d')
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error al guardar: ' . $e->getMessage()]);
}