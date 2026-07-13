<?php
/**
 * app/controllers/abonar_credito_cliente.php
 * API para registrar abonos de créditos
 * Recibe: JSON con array de abonos
 * Responde: JSON con status
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once dirname(__DIR__, 2) . '/config/database.php';
    
    // Leer JSON del body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['abonos']) || !is_array($data['abonos'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Formato JSON inválido'
        ]);
        exit;
    }
    
    $abonos = $data['abonos'];
    
    if (empty($abonos)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Lista de abonos vacía'
        ]);
        exit;
    }
    
    // Conectar a BD
    $db = new Database();
    $conn = $db->getConnection();
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    $sql = "INSERT INTO abono_credito (id_credito, m_pagocr, efectivo, fecha_abono)
            VALUES (:id_credito, :m_pagocr, :efectivo, NOW())";
    
    $stmt = $conn->prepare($sql);
    
    foreach ($abonos as $ab) {
        $id_credito = (int)($ab['id_credito'] ?? 0);
        $metodo = (string)($ab['m_pagocr'] ?? '');
        $monto = (float)($ab['efectivo'] ?? 0);
        
        // Validaciones
        if ($id_credito <= 0) {
            throw new Exception('ID crédito inválido: ' . $id_credito);
        }
        
        if (empty($metodo)) {
            throw new Exception('Método de pago requerido');
        }
        
        if ($monto <= 0) {
            throw new Exception('Monto debe ser mayor a 0: ' . $monto);
        }
        
        // Ejecutar insert
        $stmt->bindParam(':id_credito', $id_credito, PDO::PARAM_INT);
        $stmt->bindParam(':m_pagocr', $metodo, PDO::PARAM_STR);
        $stmt->bindParam(':efectivo', $monto, PDO::PARAM_STR);
        $stmt->execute();
    }
    
    // Confirmar transacción
    $conn->commit();
    
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Abonos insertados con éxito'
    ]);
    exit;
    
} catch (Exception $e) {
    // Revertir transacción si falla
    if (isset($conn)) {
        try {
            $conn->rollBack();
        } catch (Exception $rollbackError) {
            // Ignorar error de rollback
        }
    }
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit;
}
?>