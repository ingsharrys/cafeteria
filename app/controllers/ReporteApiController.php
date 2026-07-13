<?php
/**
 * ReporteApiController
 * Maneja: detalle de pedido (HTML para modal) y productos vendidos (JSON para impresión)
 * UBICACIÓN: heiyubai/ReporteApiController.php
 */
class ReporteApiController {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function handle(string $route, string $method): void {
        switch (true) {
            case ($route === 'reporte/detalle_pedido' && $method === 'GET'):
                $this->detallePedido();
                break;
            case ($route === 'reporte/productos' && $method === 'GET'):
                $this->productosVendidos();
                break;
            default:
                http_response_code(404);
                echo json_encode(['success'=>false, 'error'=>"Reporte: ruta no encontrada {$method} {$route}"]);
        }
    }

    // ───────────────────────────────────────────────
    // GET /api.php?route=reporte/detalle_pedido&id_pedido=X
    // Retorna HTML para el modal de reportes
    // ───────────────────────────────────────────────
    private function detallePedido(): void {
        header('Content-Type: text/html; charset=utf-8');

        $np = $_GET['id_pedido'] ?? '';
        if (empty($np)) { echo '<p>No se proporcionó un ID de pedido válido.</p>'; return; }

        $st = $this->db->prepare("
            SELECT p.numero_pedido, p.tipo_producto, p.detalle, p.cantidad,
                   pr.precio, c.cliente, c.direccion, c.celular,
                   prod.nombre AS producto
            FROM turnero t
            JOIN pedidos p ON t.id_pedido = p.numero_pedido
            LEFT JOIN clientes c ON t.id_cliente = c.id
            JOIN precios pr ON p.id_pro = pr.idproduc AND p.tipo_producto = pr.tipo_prod
            JOIN productos prod ON p.id_pro = prod.id_pro
            WHERE p.numero_pedido = :np
              AND p.cantidad > 0
              AND (p.detalle IS NULL OR p.detalle NOT LIKE 'ANULADO:%')
        ");
        $st->execute([':np' => $np]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) { echo '<p>No se encontraron detalles para este pedido.</p>'; return; }

        $r = $rows[0];
        echo "<h5>Detalle del Pedido</h5>";
        echo "<p><strong>Pedido N°:</strong> ".htmlspecialchars($r['numero_pedido'])."</p>";
        echo "<p><strong>Cliente:</strong> ".htmlspecialchars($r['cliente'] ?? '—')."</p>";
        echo "<p><strong>Dirección:</strong> ".htmlspecialchars($r['direccion'] ?? '—')."</p>";
        echo "<p><strong>Teléfono:</strong> ".htmlspecialchars($r['celular'] ?? '—')."</p>";

        echo '<div class="table-responsive"><table class="table table-bordered"><thead>
                <tr><th>Producto</th><th>Tamaño</th><th>Detalle</th><th>Cantidad</th>
                    <th>Precio</th><th>Subtotal</th></tr></thead><tbody>';

        $total = 0;
        foreach ($rows as $row) {
            $sub = $row['cantidad'] * $row['precio'];
            $total += $sub;
            echo '<tr><td>'.htmlspecialchars($row['producto']).'</td>
                     <td>'.htmlspecialchars($row['tipo_producto']).'</td>
                     <td>'.htmlspecialchars($row['detalle'] ?? '').'</td>
                     <td>'.htmlspecialchars($row['cantidad']).'</td>
                     <td>$'.number_format($row['precio'],0,',','.').'</td>
                     <td>$'.number_format($sub,0,',','.').'</td></tr>';
        }
        echo '</tbody></table></div>';
        echo '<p><strong>Total:</strong> $'.number_format($total,0,',','.').'</p>';

        // Domicilio opcional
        $stD = $this->db->prepare("
            SELECT d.precio, dom.repartidor
            FROM domicilios d
            LEFT JOIN domiciliarios dom ON d.id_domi = dom.id_e
            WHERE d.id_pedido = :np
        ");
        $stD->execute([':np' => $np]);
        if ($dom = $stD->fetch(PDO::FETCH_ASSOC)) {
            echo '<h5>Detalles del Domicilio</h5>';
            echo '<p><strong>Repartidor:</strong> '.htmlspecialchars($dom['repartidor'] ?? '—').'</p>';
            echo '<p><strong>Precio:</strong> $'.number_format($dom['precio'],0,',','.').'</p>';
        }
    }

    // ───────────────────────────────────────────────
    // GET /api.php?route=reporte/productos&fecha=YYYY-MM-DD
    // JSON para impresión térmica
    // ───────────────────────────────────────────────
    private function productosVendidos(): void {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        $st = $this->db->prepare("
            SELECT 
                c.id_pedidoc AS id_pedido,
                DATE_FORMAT(c.fecha_caja, '%Y-%m-%d') AS fecha,
                pr.prefijo AS nombre_producto,
                p.tipo_producto,
                SUM(p.cantidad) AS cantidad_total,
                pre.precio AS precio_unitario,
                (SUM(p.cantidad) * pre.precio) AS total_producto,
                c.costo AS costo_registrado
            FROM caja c
            LEFT JOIN pedidos p ON c.id_pedidoc = p.numero_pedido
            LEFT JOIN productos pr ON p.id_pro = pr.id_pro
            LEFT JOIN precios pre ON pr.id_pro = pre.idproduc AND p.tipo_producto = pre.tipo_prod
            WHERE DATE(c.fecha_caja) = :fecha
              AND p.cantidad > 0
              AND (p.detalle IS NULL OR p.detalle NOT LIKE 'ANULADO:%')
            GROUP BY c.id_pedidoc, pr.prefijo, p.tipo_producto, pre.precio, c.costo
            ORDER BY c.fecha_caja ASC
        ");
        $st->execute([':fecha' => $fecha]);
        $productos = $st->fetchAll(PDO::FETCH_ASSOC);

        if (empty($productos)) {
            echo json_encode(['error' => "No se encontraron productos pagados para: {$fecha}"]);
            return;
        }

        echo json_encode($productos);
    }
}