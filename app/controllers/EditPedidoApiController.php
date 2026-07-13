<?php
/**
 * EditPedidoApiController
 * Maneja: edición de pedidos (tipos producto, anular producto, eliminar pedido, guardar)
 * UBICACIÓN: heiyubai/EditPedidoApiController.php
 */
class EditPedidoApiController {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function handle(string $route, string $method): void {
        switch (true) {
            case ($route === 'edit/tipos_producto' && $method === 'GET'):
                $this->obtenerTiposProducto();
                break;
            case ($route === 'edit/eliminar_producto' && $method === 'POST'):
                $this->eliminarProducto();
                break;
            case ($route === 'edit/eliminar_pedido' && $method === 'POST'):
                $this->eliminarPedido();
                break;
            case ($route === 'edit/guardar' && $method === 'POST'):
                $this->guardarPedido();
                break;
            default:
                http_response_code(404);
                echo json_encode(['success'=>false, 'error'=>"Edit: ruta no encontrada {$method} {$route}"]);
        }
    }

    // ───────────────────────────────────────────────
    // GET /api.php?route=edit/tipos_producto&id_pro=X
    // ───────────────────────────────────────────────
    private function obtenerTiposProducto(): void {
        $idPro = (int)($_GET['id_pro'] ?? 0);
        if (!$idPro) {
            echo json_encode(['status'=>'error', 'message'=>'id_pro requerido']);
            return;
        }

        $st = $this->db->prepare("SELECT tipo_prod, precio FROM precios WHERE idproduc = :id ORDER BY precio");
        $st->execute([':id' => $idPro]);
        $tipos = $st->fetchAll(PDO::FETCH_ASSOC);

        // tcomida
        $stT = $this->db->prepare("SELECT tcomida FROM productos WHERE id_pro = :id LIMIT 1");
        $stT->execute([':id' => $idPro]);
        $tcomida = (int)$stT->fetchColumn();

        $detalles = [];
        switch ($tcomida) {
            case 1:  $detalles = ['amarillo','cafe']; break;
            case 2:  $detalles = ['papa','amarillo','cafe']; break;
            case 10: $detalles = ['Sindetalle']; break;
            default: $detalles = ['Sindetalle']; break;
        }

        echo json_encode([
            'status'=>'success', 'tipos'=>$tipos, 'detalles'=>$detalles, 'tcomida'=>$tcomida
        ], JSON_UNESCAPED_UNICODE);
    }

    // ───────────────────────────────────────────────
    // POST /api.php?route=edit/eliminar_producto
    // Anulación: inserta línea negativa para trazabilidad
    // ───────────────────────────────────────────────
    private function eliminarProducto(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $idPedido = (int)($data['id_pedido'] ?? 0);

        if (!$idPedido) {
            echo json_encode(['status'=>'error', 'message'=>'id_pedido requerido']);
            return;
        }

        $st = $this->db->prepare("
            SELECT numero_pedido, id_pro, cantidad, tipo_solicitud, detalle, tipo_producto, mesa, mesero
            FROM pedidos WHERE id_pedido = :id LIMIT 1
        ");
        $st->execute([':id' => $idPedido]);
        $orig = $st->fetch(PDO::FETCH_ASSOC);

        if (!$orig) {
            echo json_encode(['status'=>'error', 'message'=>'Producto no encontrado']);
            return;
        }

        // Línea negativa
        $stIns = $this->db->prepare("
            INSERT INTO pedidos (numero_pedido, id_pro, cantidad, fecha, tipo_solicitud, detalle, tipo_producto, mesa, mesero)
            VALUES (:np, :id_pro, :cant, NOW(), :ts, :det, :tp, :mesa, :mesero)
        ");
        $stIns->execute([
            ':np'     => $orig['numero_pedido'],
            ':id_pro' => $orig['id_pro'],
            ':cant'   => -abs((int)$orig['cantidad']),
            ':ts'     => $orig['tipo_solicitud'],
            ':det'    => 'ANULADO: ' . ($orig['detalle'] ?? ''),
            ':tp'     => $orig['tipo_producto'],
            ':mesa'   => $orig['mesa'],
            ':mesero' => $orig['mesero']
        ]);

        // Marcar original
        $this->db->prepare("UPDATE pedidos SET detalle = CONCAT('ANULADO: ', COALESCE(detalle,'')) WHERE id_pedido = :id")
            ->execute([':id' => $idPedido]);

        echo json_encode([
            'status'=>'success',
            'message'=>'Producto anulado (línea negativa insertada)',
            'id_pedido_original' => $idPedido,
            'cantidad_restada'   => -abs((int)$orig['cantidad'])
        ], JSON_UNESCAPED_UNICODE);
    }

    // ───────────────────────────────────────────────
    // POST /api.php?route=edit/eliminar_pedido
    // Elimina pedido completo (requiere código seguridad)
    // ───────────────────────────────────────────────
    private function eliminarPedido(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $np  = (int)($data['numero_pedido'] ?? 0);
        $cod = $data['codigo_seguridad'] ?? '';

        if (!$np || !$cod) {
            echo json_encode(['success'=>false, 'message'=>'Pedido y código requeridos']);
            return;
        }

        // Verificar código
        $stSeg = $this->db->prepare("SELECT codigo_seguridad FROM seguridad WHERE codigo_seguridad = :cod");
        $stSeg->execute([':cod' => $cod]);
        if (!$stSeg->fetch()) {
            echo json_encode(['success'=>false, 'message'=>'Código de seguridad incorrecto']);
            return;
        }

        // Tipo solicitud
        $stTipo = $this->db->prepare("SELECT tipo_solicitud FROM turnero WHERE id_pedido = :id LIMIT 1");
        $stTipo->execute([':id' => $np]);
        $tipo = $stTipo->fetch(PDO::FETCH_ASSOC);
        if (!$tipo) {
            echo json_encode(['success'=>false, 'message'=>'Pedido no encontrado en turnero']);
            return;
        }

        // Domicilio
        if ((int)$tipo['tipo_solicitud'] === 50) {
            $this->db->prepare("DELETE FROM domicilios WHERE id_pedido = :id")->execute([':id' => $np]);
        }

        // Comentarios (puede no existir)
        try { $this->db->prepare("DELETE FROM comentarios WHERE id_pedido = :id")->execute([':id' => $np]); }
        catch (PDOException $e) { /* ignorar */ }

        // Liberar mesa
        $this->db->prepare("UPDATE mesas SET id_pedido=NULL, estado='', fecha=NULL WHERE id_pedido=:id")->execute([':id' => $np]);

        // Eliminar pedidos y turnero
        $this->db->prepare("DELETE FROM pedidos WHERE numero_pedido = :id")->execute([':id' => $np]);
        $this->db->prepare("DELETE FROM turnero WHERE id_pedido = :id")->execute([':id' => $np]);

        echo json_encode(['success'=>true, 'message'=>'Pedido eliminado completamente']);
    }

    // ───────────────────────────────────────────────
    // POST /api.php?route=edit/guardar (FormData)
    // Actualiza existentes + inserta nuevos
    // ───────────────────────────────────────────────
    private function guardarPedido(): void {
        $np = (int)($_POST['numero_pedido'] ?? 0);
        if (!$np) {
            echo json_encode(['success'=>false, 'message'=>'numero_pedido requerido']);
            return;
        }

        $errores = [];
        $actualizados = 0;
        $insertados = 0;

        // 1. Actualizar existentes
        if (isset($_POST['productos_existentes']) && is_array($_POST['productos_existentes'])) {
            foreach ($_POST['productos_existentes'] as $idPed => $prod) {
                $idPro   = (int)($prod['id_pro'] ?? 0);
                $cant    = (int)($prod['cantidad'] ?? 0);
                $tipo    = $prod['tipo_producto'] ?? '';
                $detalle = $prod['detalle'] ?? 'Sindetalle';

                if (!$idPro || !$cant || !$tipo) { $errores[] = "Producto #{$idPed}: datos incompletos"; continue; }

                try {
                    $st = $this->db->prepare("UPDATE pedidos SET id_pro=:ip, cantidad=:c, tipo_producto=:t, detalle=:d WHERE id_pedido=:id");
                    $st->execute([':ip'=>$idPro, ':c'=>$cant, ':t'=>$tipo, ':d'=>$detalle, ':id'=>(int)$idPed]);
                    $actualizados++;
                } catch (PDOException $e) {
                    $errores[] = "Error #{$idPed}: ".$e->getMessage();
                }
            }
        }

        // 2. Insertar nuevos
        if (isset($_POST['productos_nuevos']) && is_array($_POST['productos_nuevos'])) {
            $stInfo = $this->db->prepare("SELECT tipo_solicitud, mesa FROM pedidos WHERE numero_pedido=:id LIMIT 1");
            $stInfo->execute([':id'=>$np]);
            $info = $stInfo->fetch(PDO::FETCH_ASSOC);
            $tipoSol = $info['tipo_solicitud'] ?? 51;
            $mesa    = $info['mesa'] ?? null;

            foreach ($_POST['productos_nuevos'] as $prod) {
                $idPro   = (int)($prod['id_pro'] ?? 0);
                $cant    = (int)($prod['cantidad'] ?? 0);
                $tipo    = $prod['tipo_producto'] ?? '';
                $detalle = $prod['detalle'] ?? 'Sindetalle';

                if (!$idPro || !$cant || !$tipo) { $errores[] = "Nuevo producto: datos incompletos"; continue; }

                try {
                    $st = $this->db->prepare("
                        INSERT INTO pedidos (numero_pedido, id_pro, cantidad, fecha, tipo_solicitud, detalle, tipo_producto, mesa)
                        VALUES (:np, :ip, :c, NOW(), :ts, :d, :t, :m)
                    ");
                    $st->execute([':np'=>$np, ':ip'=>$idPro, ':c'=>$cant, ':ts'=>$tipoSol, ':d'=>$detalle, ':t'=>$tipo, ':m'=>$mesa]);
                    $insertados++;
                } catch (PDOException $e) {
                    $errores[] = "Error insertando: ".$e->getMessage();
                }
            }
        }

        echo json_encode([
            'success'      => empty($errores),
            'message'      => "Actualizados: {$actualizados}, Insertados: {$insertados}",
            'actualizados' => $actualizados,
            'insertados'   => $insertados,
            'errores'      => $errores
        ], JSON_UNESCAPED_UNICODE);
    }
}