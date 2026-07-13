<?php
/**
 * API para obtener productos de un pedido
 * GET: /menu/api/productos-pedido.php?id_pedido=123
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$idPedido = intval($_GET['id_pedido'] ?? 0);

if (!$idPedido) {
    echo json_encode(['status' => 'error', 'message' => 'ID pedido no válido']);
    exit;
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    $sql = "
        SELECT 
            pr.nombre,
            pr.prefijo,
            p.cantidad,
            p.tipo_producto,
            COALESCE(prp.precio, 0) as precio
        FROM pedidos p
        JOIN productos pr ON p.id_pro = pr.id_pro
        LEFT JOIN precios prp ON pr.id_pro = prp.idproduc AND prp.tipo_prod = p.tipo_producto
        WHERE p.numero_pedido = :id_pedido
          AND p.cantidad > 0
          AND (p.detalle IS NULL OR p.detalle NOT LIKE 'ANULADO:%')
        ORDER BY p.id_pedido
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([':id_pedido' => $idPedido]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status'     => 'success',
        'productos'  => $productos,
        'count'      => count($productos)
    ]);

} catch (Exception $e) {
    error_log("Error en productos-pedido.php: " . $e->getMessage());
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error obteniendo productos'
    ]);
}