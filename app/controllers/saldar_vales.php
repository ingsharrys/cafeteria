<?php
/**
 * controllers/saldar_vales.php
 * Controlador para marcar vales como pagados (saldados) por mes y cajero
 */

require_once dirname(__DIR__) . '/config/database.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Método no permitido']));
}

// Obtener datos
$cajero = isset($_POST['cajero']) ? $_POST['cajero'] : null;
$mes_vales = isset($_POST['mes_vales']) ? $_POST['mes_vales'] : null;
$fecha_pago = isset($_POST['fecha_pago']) ? $_POST['fecha_pago'] : date('Y-m-d');
$metodo_pago = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : 'efectivo';
$referencia = isset($_POST['referencia']) ? $_POST['referencia'] : '';

// Validar datos
if (!$cajero || !$mes_vales) {
    $_SESSION['error'] = 'Datos incompletos';
    header('Location: ../gastos.php');
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // 🔍 Obtener los vales a saldar para este mes específico y cajero
    $queryVales = "
        SELECT id, monto 
        FROM gastos 
        WHERE cajero = :cajero 
              AND categoria = 'vales' 
              AND estado = 0
              AND DATE_FORMAT(fecha, '%Y-%m') = :mes_vales
    ";
    $stmtVales = $conn->prepare($queryVales);
    $stmtVales->bindParam(':cajero', $cajero, PDO::PARAM_STR);
    $stmtVales->bindParam(':mes_vales', $mes_vales, PDO::PARAM_STR);
    $stmtVales->execute();
    $vales = $stmtVales->fetchAll(PDO::FETCH_ASSOC);

    if (empty($vales)) {
        $_SESSION['error'] = 'No hay vales para saldar en este período';
        header('Location: ../gastos.php');
        exit;
    }

    // 🔄 Actualizar estado de vales a 1 (pagados) SOLO del mes especificado y cajero
    $queryUpdate = "
        UPDATE gastos 
        SET estado = 1,
            observaciones = CONCAT(
                'Saldado el ', :fecha_pago, 
                ' | Método: ', :metodo_pago,
                IF(:referencia != '', CONCAT(' | ', :referencia), '')
            )
        WHERE cajero = :cajero 
              AND categoria = 'vales' 
              AND estado = 0
              AND DATE_FORMAT(fecha, '%Y-%m') = :mes_vales
    ";
    $stmtUpdate = $conn->prepare($queryUpdate);
    $stmtUpdate->bindParam(':cajero', $cajero, PDO::PARAM_STR);
    $stmtUpdate->bindParam(':mes_vales', $mes_vales, PDO::PARAM_STR);
    $stmtUpdate->bindParam(':fecha_pago', $fecha_pago, PDO::PARAM_STR);
    $stmtUpdate->bindParam(':metodo_pago', $metodo_pago, PDO::PARAM_STR);
    $stmtUpdate->bindParam(':referencia', $referencia, PDO::PARAM_STR);
    $stmtUpdate->execute();

    $rowsAffected = $stmtUpdate->rowCount();

    // ✅ Redirigir con mensaje de éxito
    if ($rowsAffected > 0) {
        $_SESSION['success'] = "✅ Se saldaron " . $rowsAffected . " vales correctamente";
    } else {
        $_SESSION['warning'] = "⚠️ No se realizaron cambios";
    }

    header('Location: ../gastos.php');
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = 'Error al saldar vales: ' . $e->getMessage();
    header('Location: ../gastos.php');
    exit;
}