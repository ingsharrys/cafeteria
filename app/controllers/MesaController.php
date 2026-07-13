<?php
// app/controllers/MesasController.php

class MesasController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Punto de entrada principal para manejar todas las rutas que empiecen con 'mesas'
     */
    public function handle($route, $method)
    {
        // Limpiamos la ruta para comparaciones más fáciles
        $route = trim($route, '/');

        if ($route === 'mesas' && $method === 'GET') {
            $this->obtenerMesas();
        }
        elseif (preg_match('#^mesas(\?numero_pedido=\d+)?$#', $route) && $method === 'GET') {
            // GET mesas → todas las mesas
            // GET mesas?numero_pedido=123 → detalle de un pedido/mesa
            if (isset($_GET['numero_pedido']) && is_numeric($_GET['numero_pedido'])) {
                $this->obtenerDetallePedido((int)$_GET['numero_pedido']);
            } else {
                $this->obtenerMesas();
            }
        }
        elseif ($route === 'mesas/estado' && $method === 'POST') {
            $this->cambiarEstadoMesa();
        }
        elseif ($route === 'mesas/liberar' && $method === 'POST') {
            $this->liberarMesa();
        }
        elseif ($route === 'mesas/cambiar' && $method === 'POST') {
            $this->cambiarMesa();
        }
        else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error'   => "Acción no encontrada en mesas: {$method} {$route}"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // ────────────────────────────────────────────────
    // GET /api/mesas
    // ────────────────────────────────────────────────
    private function obtenerMesas()
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    m.numero_mesa, 
                    m.id_pedido, 
                    m.estado, 
                    m.fecha,
                    CASE WHEN EXISTS (
                        SELECT 1 FROM caja c WHERE c.id_pedidoc = m.id_pedido
                    ) THEN 1 ELSE 0 END AS pagado
                FROM mesas m
                ORDER BY CAST(m.numero_mesa AS UNSIGNED) ASC
            ");

            $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Aseguramos tipos correctos
            foreach ($mesas as &$mesa) {
                $mesa['id_pedido'] = $mesa['id_pedido'] ? (int)$mesa['id_pedido'] : null;
                $mesa['pagado']    = (int)$mesa['pagado'];
            }

            echo json_encode([
                'success' => true,
                'mesas'   => $mesas
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->errorResponse('Error al obtener mesas: ' . $e->getMessage());
        }
    }

    // ────────────────────────────────────────────────
    // GET /api/mesas?numero_pedido=XXXX
    // ────────────────────────────────────────────────
    private function obtenerDetallePedido($numeroPedido)
    {
        try {
            // 1. Cabecera desde turnero
            $stmt = $this->db->prepare("
                SELECT 
                    t.id_pedido AS numero_pedido,
                    t.turno,
                    t.estado,
                    t.fecha,
                    t.tipo_solicitud,
                    t.id_cliente
                FROM turnero t 
                WHERE t.id_pedido = :id 
                LIMIT 1
            ");
            $stmt->execute([':id' => $numeroPedido]);
            $cabecera = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cabecera) {
                return $this->errorResponse('Pedido no encontrado en turnero');
            }

            // 2. Mesa asignada
            $stMesa = $this->db->prepare("
                SELECT numero_mesa 
                FROM mesas 
                WHERE id_pedido = :id 
                LIMIT 1
            ");
            $stMesa->execute([':id' => $numeroPedido]);
            $numeroMesa = $stMesa->fetchColumn() ?: 'No asignada';

            // 3. Mesero (de la primera línea)
            $stMes = $this->db->prepare("
                SELECT me.nombre_mese 
                FROM pedidos p
                JOIN meseros me ON p.mesero = me.id_mese
                WHERE p.numero_pedido = :id 
                LIMIT 1
            ");
            $stMes->execute([':id' => $numeroPedido]);
            $nombreMesero = $stMes->fetchColumn() ?: 'No asignado';

            // 4. Cliente
            $nombreCliente = 'Cliente de Mesa';
            $telefonoCliente = '';
            if ($cabecera['id_cliente']) {
                $stCli = $this->db->prepare("
                    SELECT cliente, celular 
                    FROM clientes 
                    WHERE id = :id 
                    LIMIT 1
                ");
                $stCli->execute([':id' => $cabecera['id_cliente']]);
                $cli = $stCli->fetch(PDO::FETCH_ASSOC);
                if ($cli) {
                    $nombreCliente   = $cli['cliente']   ?? $nombreCliente;
                    $telefonoCliente = $cli['celular']   ?? '';
                }
            }

            // 5. Productos (excluyendo anulados)
            $stProd = $this->db->prepare("
                SELECT 
                    pr.nombre AS nombre,
                    pr.prefijo AS tipo_producto,
                    p.cantidad,
                    p.detalle,
                    p.tipo_producto AS tipo_prod,
                    COALESCE(prp.precio, 0) AS precio
                FROM pedidos p
                JOIN productos pr ON p.id_pro = pr.id_pro
                LEFT JOIN precios prp ON pr.id_pro = prp.idproduc 
                                      AND prp.tipo_prod = p.tipo_producto
                WHERE p.numero_pedido = :id
                  AND p.cantidad > 0
                  AND (p.detalle IS NULL OR p.detalle NOT LIKE 'ANULADO:%')
            ");
            $stProd->execute([':id' => $numeroPedido]);
            $productos = $stProd->fetchAll(PDO::FETCH_ASSOC);

            foreach ($productos as &$pr) {
                $pr['precio']   = (float)$pr['precio'];
                $pr['cantidad'] = (int)$pr['cantidad'];
            }

            // 6. Estado de pago
            $stPago = $this->db->prepare("
                SELECT COUNT(*) 
                FROM caja 
                WHERE id_pedidoc = :id
            ");
            $stPago->execute([':id' => $numeroPedido]);
            $estaPagado = ($stPago->fetchColumn() > 0) ? 1 : 0;

            // 7. Mesas libres (para cambio de mesa)
            $mesasLibres = $this->db->query("
                SELECT numero_mesa 
                FROM mesas 
                WHERE id_pedido IS NULL OR id_pedido = 0
                ORDER BY CAST(numero_mesa AS UNSIGNED)
            ")->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode([
                'success'        => true,
                'numero_pedido'  => (int)$cabecera['numero_pedido'],
                'numero_mesa'    => $numeroMesa,
                'nombre_mesero'  => $nombreMesero,
                'nombre_cliente' => $nombreCliente,
                'telefono'       => $telefonoCliente,
                'estado'         => $cabecera['estado'] ?? '',
                'pagado'         => $estaPagado,
                'fecha'          => $cabecera['fecha'],
                'productos'      => $productos,
                'comentarios'    => [], // puedes agregar después si existe tabla comentarios
                'mesas_libres'   => $mesasLibres
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->errorResponse('Error al obtener detalle del pedido: ' . $e->getMessage());
        }
    }

    // ────────────────────────────────────────────────
    // POST /api/mesas/estado
    // ────────────────────────────────────────────────
    private function cambiarEstadoMesa()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($data['numero_pedido'] ?? 0);
        $est  = trim($data['nuevo_estado'] ?? '');

        if (!$id || !$est) {
            return $this->errorResponse('Datos incompletos', 400);
        }

        try {
            $this->db->prepare("UPDATE turnero SET estado = :est WHERE id_pedido = :id")
                     ->execute([':est' => $est, ':id' => $id]);

            $this->db->prepare("UPDATE mesas SET estado = :est WHERE id_pedido = :id")
                     ->execute([':est' => $est, ':id' => $id]);

            echo json_encode([
                'success' => true,
                'message' => 'Estado actualizado correctamente'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->errorResponse('Error al cambiar estado: ' . $e->getMessage());
        }
    }

    // ────────────────────────────────────────────────
    // POST /api/mesas/liberar
    // ────────────────────────────────────────────────
    private function liberarMesa()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $mesa = $data['numero_mesa'] ?? null;

        if (!$mesa) {
            return $this->errorResponse('Número de mesa requerido', 400);
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE mesas 
                SET id_pedido = NULL, 
                    estado = '', 
                    fecha = NULL 
                WHERE numero_mesa = :m
            ");
            $stmt->execute([':m' => $mesa]);

            echo json_encode([
                'success' => true,
                'message' => 'Mesa liberada correctamente'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->errorResponse('Error al liberar mesa: ' . $e->getMessage());
        }
    }

    // ────────────────────────────────────────────────
    // POST /api/mesas/cambiar
    // ────────────────────────────────────────────────
    private function cambiarMesa()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $id     = (int)($data['numero_pedido'] ?? 0);
        $nueva  = $data['nueva_mesa'] ?? null;
        $actual = $data['mesa_actual'] ?? null;

        if (!$id || !$nueva) {
            return $this->errorResponse('Datos incompletos (pedido y nueva mesa requeridos)', 400);
        }

        try {
            $this->db->beginTransaction();

            // Obtener estado actual
            $est = $this->db->prepare("SELECT estado FROM mesas WHERE id_pedido = :id LIMIT 1");
            $est->execute([':id' => $id]);
            $estado = $est->fetchColumn() ?: '';

            // Liberar mesa actual si se indica
            if ($actual) {
                $this->db->prepare("
                    UPDATE mesas 
                    SET id_pedido = NULL, estado = '', fecha = NULL 
                    WHERE numero_mesa = :m
                ")->execute([':m' => $actual]);
            }

            // Asignar nueva mesa
            $this->db->prepare("
                UPDATE mesas 
                SET id_pedido = :id, estado = :est, fecha = NOW() 
                WHERE numero_mesa = :m
            ")->execute([':id' => $id, ':est' => $estado, ':m' => $nueva]);

            // Actualizar mesa en líneas de pedido
            $this->db->prepare("
                UPDATE pedidos 
                SET mesa = :m 
                WHERE numero_pedido = :id
            ")->execute([':m' => $nueva, ':id' => $id]);

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Mesa cambiada correctamente'
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->errorResponse('Error al cambiar mesa: ' . $e->getMessage());
        }
    }

    // ────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────
    private function errorResponse($message, $code = 500)
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error'   => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}