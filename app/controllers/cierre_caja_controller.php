<?php
/**
 * app/controllers/cierre_caja_controller.php
 * Guarda el cierre de caja del cajero en la BD
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once dirname(__DIR__, 2) . '/config/database.php';
    
    // Leer JSON del body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'JSON inválido'
        ]);
        exit;
    }
    
    // Validar datos
    $id_cajero = (int)($data['id_cajero'] ?? 0);
    $fecha_cierre = (string)($data['fecha_cierre'] ?? '');
    $total_efectivo = (float)($data['total_efectivo'] ?? 0);
    $total_tarjeta = (float)($data['total_tarjeta'] ?? 0);
    $total_transferencia = (float)($data['total_transferencia'] ?? 0);
    $total_brebe = (float)($data['total_brebe'] ?? 0);
    $total_devolucion = (float)($data['total_devolucion'] ?? 0);
    $total_general = (float)($data['total_general'] ?? 0);
    $comentarios = (string)($data['comentarios'] ?? '');
    
    if ($id_cajero <= 0 || empty($fecha_cierre)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Datos incompletos'
        ]);
        exit;
    }
    
    // Conectar a BD
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verificar si ya existe cierre para este cajero en esta fecha
    $stVerif = $conn->prepare("
        SELECT id FROM cierre_caja 
        WHERE id_cajero = ? AND DATE(fecha_cierre) = ?
        LIMIT 1
    ");
    $stVerif->execute([$id_cajero, $fecha_cierre]);
    $existe = $stVerif->fetch(PDO::FETCH_ASSOC);
    
    if ($existe) {
        http_response_code(409);
        echo json_encode([
            'status' => 'error',
            'message' => 'Ya existe un cierre de caja para este cajero en esta fecha'
        ]);
        exit;
    }
    
    // Insertar cierre de caja
    $sql = "
        INSERT INTO cierre_caja (
            id_cajero, 
            fecha_cierre, 
            total_efectivo, 
            total_tarjeta, 
            total_transferencia, 
            total_brebe, 
            total_devolucion, 
            total_general, 
            comentarios,
            fecha_creacion
        )
        VALUES (
            :id_cajero,
            :fecha_cierre,
            :total_efectivo,
            :total_tarjeta,
            :total_transferencia,
            :total_brebe,
            :total_devolucion,
            :total_general,
            :comentarios,
            NOW()
        )
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':id_cajero' => $id_cajero,
        ':fecha_cierre' => $fecha_cierre,
        ':total_efectivo' => $total_efectivo,
        ':total_tarjeta' => $total_tarjeta,
        ':total_transferencia' => $total_transferencia,
        ':total_brebe' => $total_brebe,
        ':total_devolucion' => $total_devolucion,
        ':total_general' => $total_general,
        ':comentarios' => $comentarios
    ]);
    
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => '✅ Cierre de caja registrado correctamente'
    ]);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit;
}
?>