<?php
/**
 * get_barrios.php - API para obtener barrios
 * Ubicación: /menu/api/get_barrios.php
 * Retorna: JSON con array de barrios activos
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Incluir conexión a BD
    require_once __DIR__ . '/../../config/database.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Query para obtener barrios activos
    $query = "
        SELECT 
            id_barrio,
            nombre_barrio,
            id_zona
        FROM barrios
        WHERE estado = 'activo'
        ORDER BY nombre_barrio ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $barrios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $barrios,
        'count' => count($barrios)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>