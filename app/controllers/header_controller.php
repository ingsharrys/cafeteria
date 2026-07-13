<?php
/**
 * header_controller.php - CON MÉTODO BREBE
 * UBICACIÓN: heiyubai/app/controllers/header_controller.php
 * 
 * Obtiene:
 * - Gastos desde tabla gastos
 * - Base desde tabla base
 * - Efectivo, Tarjeta, Transferencia, BREVE desde tabla caja
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener nombre del cajero desde sesión
$cajero = $_SESSION['cajero'] ?? null;

// Valores por defecto
$total_gastos = 0;
$total_base = 0;
$total_efectivo = 0;
$total_tarjeta = 0;
$total_transferencia = 0;
$total_brebe = 0;

// Si no hay cajero logueado, salir
if (!$cajero) {
    return;
}

// Conexión a BD
try {
    $dbFile = dirname(__DIR__) . '/../config/database.php';
    if (!file_exists($dbFile)) {
        $dbFile = dirname(dirname(dirname(__DIR__))) . '/config/database.php';
    }
    
    require_once $dbFile;
    $db = Database::getInstance()->getConnection();
    
} catch (Exception $e) {
    return;
}

$fecha_hoy = date('Y-m-d');

// ═══════════════════════════════════════════════════════
// 1️⃣ GASTOS DEL DÍA
// ═══════════════════════════════════════════════════════
try {
    $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM gastos 
            WHERE cajero = :cajero AND DATE(fecha) = :fecha";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':cajero', $cajero, PDO::PARAM_STR);
    $stmt->bindParam(':fecha', $fecha_hoy, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_gastos = (float)($result['total'] ?? 0);
} catch (Exception $e) {
    $total_gastos = 0;
}

// ═══════════════════════════════════════════════════════
// 2️⃣ BASE DEL DÍA (desde tabla base)
// ═══════════════════════════════════════════════════════
try {
    $sql = "SELECT COALESCE(base, 0) as total FROM base 
            WHERE cajero_base = :cajero AND DATE(fechab) = :fecha
            ORDER BY idb DESC LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':cajero', $cajero, PDO::PARAM_STR);
    $stmt->bindParam(':fecha', $fecha_hoy, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_base = (float)($result['total'] ?? 0);
} catch (Exception $e) {
    $total_base = 0;
}

// ═══════════════════════════════════════════════════════
// 3️⃣ CAJA - EFECTIVO, TARJETA, TRANSFERENCIA, BREBE
// ═══════════════════════════════════════════════════════
try {
    $sql = "SELECT 
                COALESCE(SUM(CASE 
                    WHEN m_pago = 'efectivo' THEN costo
                    WHEN m_pago = 'efectivo_transferencia' THEN COALESCE(efectivo, 0)
                    WHEN m_pago = 'tarjeta_efectivo' THEN COALESCE(efectivo, 0)
                    WHEN m_pago = 'brebe_efectivo' THEN COALESCE(efectivo, 0)
                    ELSE 0 
                END), 0) as total_efectivo,
                
                COALESCE(SUM(CASE 
                    WHEN m_pago = 'tarjeta' THEN costo
                    WHEN m_pago = 'tarjeta_efectivo' THEN (costo - COALESCE(efectivo, 0))
                    ELSE 0 
                END), 0) as total_tarjeta,
                
                COALESCE(SUM(CASE 
                    WHEN m_pago = 'transferencia' THEN costo
                    WHEN m_pago = 'efectivo_transferencia' THEN (costo - COALESCE(efectivo, 0))
                    ELSE 0 
                END), 0) as total_transferencia,
                
                COALESCE(SUM(CASE 
                    WHEN m_pago = 'brebe' THEN costo
                    WHEN m_pago = 'brebe_efectivo' THEN (costo - COALESCE(efectivo, 0))
                    ELSE 0 
                END), 0) as total_brebe
            FROM caja
            WHERE cajero = :cajero AND DATE(fecha_caja) = :fecha";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':cajero', $cajero, PDO::PARAM_STR);
    $stmt->bindParam(':fecha', $fecha_hoy, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_efectivo = (float)($result['total_efectivo'] ?? 0);
    $total_tarjeta = (float)($result['total_tarjeta'] ?? 0);
    $total_transferencia = (float)($result['total_transferencia'] ?? 0);
    $total_brebe = (float)($result['total_brebe'] ?? 0);
    
} catch (Exception $e) {
    $total_efectivo = 0;
    $total_tarjeta = 0;
    $total_transferencia = 0;
    $total_brebe = 0;
}