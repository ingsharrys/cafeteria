<?php
/**
 * api_barrios_simple.php
 * 
 * Ubicación: /menu/api/ (misma carpeta donde está get_barrios.php si existe)
 * 
 * Retorna JSON con lista de barrios activos
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // Buscar database.php
    $dbFile = null;
    $paths = [
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../../database.php',
        __DIR__ . '/../database.php',
        dirname(dirname(__DIR__)) . '/config/database.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            $dbFile = $path;
            break;
        }
    }
    
    if (!$dbFile) {
        throw new Exception('database.php no encontrado');
    }
    
    require_once $dbFile;
    $db = Database::getInstance()->getConnection();
    
    // Obtener barrios
    $sql = "
        SELECT 
            id_barrio,
            nombre_barrio
        FROM barrios
        WHERE estado = 'activo'
        ORDER BY nombre_barrio ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $barrios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $barrios
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>