<?php
/**
 * app/controllers/excel_cierre_caja.php
 * Genera y descarga Excel con el cierre de caja del cajero
 */

try {
    require_once dirname(__DIR__, 2) . '/config/database.php';
    
    $id_cajero = (int)($_GET['id_cajero'] ?? 0);
    $fecha = (string)($_GET['fecha'] ?? date('Y-m-d'));
    
    if ($id_cajero <= 0) {
        die('❌ ID de cajero inválido');
    }
    
    // Conectar a BD
    $db = new Database();
    $conn = $db->getConnection();
    
    // Obtener datos del cierre
    $stCierre = $conn->prepare("
        SELECT c.*, m.nombre_mese, m.cargo
        FROM cierre_caja c
        LEFT JOIN meseros m ON m.id_mese = c.id_cajero
        WHERE c.id_cajero = ? AND DATE(c.fecha_cierre) = ?
        LIMIT 1
    ");
    $stCierre->execute([$id_cajero, $fecha]);
    $cierre = $stCierre->fetch(PDO::FETCH_ASSOC);
    
    if (!$cierre) {
        die('❌ No hay cierre de caja para este cajero en esta fecha');
    }
    
    // Obtener transacciones del cajero en esa fecha
    $stTransacc = $conn->prepare("
        SELECT 
            t.id_pedido,
            t.turno,
            t.fecha,
            t.tipo_solicitud,
            t.estado,
            c.m_pago,
            c.costo,
            c.efectivo,
            c.banco
        FROM turnero t
        LEFT JOIN caja c ON c.id_pedidoc = t.id_pedido
        WHERE c.id_cajero = ? AND DATE(t.fecha) = ?
        ORDER BY t.fecha ASC
    ");
    $stTransacc->execute([$id_cajero, $fecha]);
    $transacciones = $stTransacc->fetchAll(PDO::FETCH_ASSOC);
    
    // ═══════════════════════════════════════════
    // GENERAR CSV (compatible con Excel)
    // ═══════════════════════════════════════════
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cierre_caja_' . $cierre['nombre_mese'] . '_' . $fecha . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM para Excel (caracteres especiales)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // ENCABEZADO
    fputcsv($output, ['CIERRE DE CAJA'], ';');
    fputcsv($output, [], ';');
    
    // INFORMACIÓN GENERAL
    fputcsv($output, ['Cajero:', $cierre['nombre_mese']], ';');
    fputcsv($output, ['Cargo:', $cierre['cargo']], ';');
    fputcsv($output, ['Fecha Cierre:', $cierre['fecha_cierre']], ';');
    fputcsv($output, ['Fecha Creación:', $cierre['fecha_creacion']], ';');
    fputcsv($output, [], ';');
    
    // RESUMEN FINANCIERO
    fputcsv($output, ['RESUMEN FINANCIERO'], ';');
    fputcsv($output, ['Concepto', 'Monto'], ';');
    fputcsv($output, ['Efectivo', '$' . number_format($cierre['total_efectivo'], 2, ',', '.')], ';');
    fputcsv($output, ['Tarjeta', '$' . number_format($cierre['total_tarjeta'], 2, ',', '.')], ';');
    fputcsv($output, ['Transferencia', '$' . number_format($cierre['total_transferencia'], 2, ',', '.')], ';');
    fputcsv($output, ['Brebe', '$' . number_format($cierre['total_brebe'], 2, ',', '.')], ';');
    fputcsv($output, ['Devoluciones', '$' . number_format($cierre['total_devolucion'], 2, ',', '.')], ';');
    fputcsv($output, ['TOTAL GENERAL', '$' . number_format($cierre['total_general'], 2, ',', '.')], ';');
    fputcsv($output, [], ';');
    
    // COMENTARIOS
    if (!empty($cierre['comentarios'])) {
        fputcsv($output, ['Comentarios:', $cierre['comentarios']], ';');
        fputcsv($output, [], ';');
    }
    
    // DETALLE DE TRANSACCIONES
    if (!empty($transacciones)) {
        fputcsv($output, ['DETALLE DE TRANSACCIONES'], ';');
        fputcsv($output, [
            'Fecha',
            'Pedido',
            'Turno',
            'Tipo',
            'Estado',
            'Método Pago',
            'Monto',
            'Efectivo',
            'Banco'
        ], ';');
        
        $tiposSol = [50 => 'Domicilio', 51 => 'Turno', 52 => 'Mesas', 53 => 'Recoger'];
        $metodosPago = [
            'efectivo' => 'Efectivo',
            'tarjeta' => 'Tarjeta',
            'transferencia' => 'Transferencia',
            'efectivo_transferencia' => 'Efectivo + Transf.',
            'tarjeta_efectivo' => 'Tarjeta + Efectivo',
            'brebe' => 'Brebe',
            'brebe_efectivo' => 'Brebe + Efectivo',
            'devolucion' => 'Devolución'
        ];
        
        foreach ($transacciones as $trans) {
            fputcsv($output, [
                $trans['fecha'] ?? '',
                $trans['id_pedido'] ?? '',
                $trans['turno'] ?? '',
                $tiposSol[$trans['tipo_solicitud']] ?? 'Mesas',
                $trans['estado'] ?? '',
                $metodosPago[$trans['m_pago']] ?? $trans['m_pago'] ?? '',
                '$' . number_format($trans['costo'] ?? 0, 2, ',', '.'),
                '$' . number_format($trans['efectivo'] ?? 0, 2, ',', '.'),
                $trans['banco'] ?? ''
            ], ';');
        }
    }
    
    fputcsv($output, [], ';');
    fputcsv($output, ['Descargado: ' . date('Y-m-d H:i:s')], ';');
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    die('❌ Error: ' . $e->getMessage());
}
?>