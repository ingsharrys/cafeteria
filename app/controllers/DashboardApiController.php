<?php
/**
 * DashboardApiController
 * Maneja: mesas, turnos, productos (dashboard principal)
 * UBICACIÓN: heiyubai/app/controllers/DashboardApiController.php
 *
 * 🆕 CAMBIO: obtenerTurnos() ahora soporta parámetros 'since' y 'limit'
 *    para API diferencial (trae solo cambios recientes)
 *
 * Versión ajustada para mantener compatibilidad 100% con el frontend antiguo
 * (igual que en el viejo api.php monolítico)
 */
class DashboardApiController {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function handle(string $route, string $method): void {
        // ✅ Cargar controlador de WhatsApp al INICIO
        require_once __DIR__ . '/EnviarMensajeWhatsApp.php';
        
        switch (true) {
            case ($route === 'mesas' && $method === 'GET'):
                if (!empty($_GET['numero_pedido'])) {
                    $this->obtenerDetallePedido((int)$_GET['numero_pedido']);
                } else {
                    $this->obtenerMesas();
                }
                break;

            case ($route === 'turnos' && $method === 'GET'):
                $this->obtenerTurnos();
                break;

            case ($route === 'productos' && $method === 'GET'):
                $this->obtenerProductos();
                break;

            case ($route === 'mesas/estado' && $method === 'POST'):
                $this->cambiarEstadoMesa();
                break;

            case ($route === 'turnos/estado' && $method === 'POST'):
                $this->cambiarEstadoTurno();
                break;

            case ($route === 'mesas/liberar' && $method === 'POST'):
                $this->liberarMesa();
                break;

            case ($route === 'mesas/cambiar' && $method === 'POST'):
                $this->cambiarMesa();
                break;

            default:
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => "Dashboard: ruta no encontrada {$method} {$route}"]);
                exit;
        }
    }

    // ───────────────────────────────────────────────
    // GET /api.php?route=mesas
    // ───────────────────────────────────────────────
    private function obtenerMesas(): void {
        $stmt = $this->db->query("
            SELECT m.numero_mesa, m.id_pedido, m.estado, m.fecha
            FROM mesas m
            ORDER BY CAST(m.numero_mesa AS UNSIGNED) ASC
        ");
        $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mesas as &$mesa) {
            $mesa['numero_mesa'] = $mesa['numero_mesa'];
            $mesa['id_pedido']   = $mesa['id_pedido'] ? (int)$mesa['id_pedido'] : null;
            $mesa['pagado']      = 0;

            if ($mesa['id_pedido']) {
                $st = $this->db->prepare("SELECT pagado FROM turnero WHERE id_pedido = :id LIMIT 1");
    $st->execute([':id' => $mesa['id_pedido']]);
    $mesa['pagado'] = (int)($st->fetchColumn() ?? 0);
            }
        }

        echo json_encode(['success' => true, 'mesas' => $mesas], JSON_UNESCAPED_UNICODE);
    }

    // ───────────────────────────────────────────────
    // GET /api.php?route=mesas&numero_pedido=X  (modal)
    // ───────────────────────────────────────────────
    private function obtenerDetallePedido(int $numeroPedido): void {
        // 1. Cabecera del pedido (turnero)
        $stmt = $this->db->prepare("
            SELECT t.id_pedido AS numero_pedido, t.turno, t.estado, t.fecha, t.tipo_solicitud, t.id_cliente
            FROM turnero t WHERE t.id_pedido = :id LIMIT 1
        ");
        $stmt->execute([':id' => $numeroPedido]);
        $cabecera = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cabecera) {
            echo json_encode(['success' => false, 'error' => 'Pedido no encontrado en turnero']);
            return;
        }

        // 2. Mesa asignada
        $stMesa = $this->db->prepare("SELECT numero_mesa FROM mesas WHERE id_pedido = :id LIMIT 1");
        $stMesa->execute([':id' => $numeroPedido]);
        $numeroMesa = $stMesa->fetchColumn() ?: 'No asignada';

        // 3. Mesero (de la primera línea de pedidos)
        $nombreMesero = 'No asignado';
        $stMes = $this->db->prepare("
            SELECT me.nombre_mese FROM pedidos p
            JOIN meseros me ON p.mesero = me.id_mese
            WHERE p.numero_pedido = :id LIMIT 1
        ");
        $stMes->execute([':id' => $numeroPedido]);
        $mes = $stMes->fetchColumn();
        if ($mes) $nombreMesero = $mes;

        // 4. Cliente
        $nombreCliente = 'Cliente de Mesa';
        $telefonoCliente = '';
        if ($cabecera['id_cliente']) {
            $stCli = $this->db->prepare("SELECT cliente, celular FROM clientes WHERE id = :id LIMIT 1");
            $stCli->execute([':id' => $cabecera['id_cliente']]);
            $cli = $stCli->fetch(PDO::FETCH_ASSOC);
            if ($cli) {
                $nombreCliente = $cli['cliente'] ?? $nombreCliente;
                $telefonoCliente = $cli['celular'] ?? '';
            }
        }

        // 5. Productos con precio (JOIN pedidos + productos + precios)
        // ✅ Excluir anulados del modal
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
            LEFT JOIN precios prp ON pr.id_pro = prp.idproduc AND prp.tipo_prod = p.tipo_producto
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

        // 6. Verificar pago en caja
$stPago = $this->db->prepare("SELECT pagado FROM turnero WHERE id_pedido = :id LIMIT 1");
$stPago->execute([':id' => $numeroPedido]);
$estaPagado = (int)($stPago->fetchColumn() ?? 0);

        // 7. Mesas libres
        $mesasLibres = $this->db->query("
            SELECT numero_mesa FROM mesas 
            WHERE id_pedido IS NULL OR id_pedido = 0
            ORDER BY CAST(numero_mesa AS UNSIGNED)
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 8. Obtener comentarios
        $comentarioTexto = '';
        try {
            $stCom = $this->db->prepare("SELECT comentario FROM comentarios WHERE id_pedido = :id LIMIT 1");
            $stCom->execute([':id' => $numeroPedido]);
            $com = $stCom->fetchColumn();
            if ($com) $comentarioTexto = (string)$com;
        } catch (PDOException $e) { /* tabla comentarios puede no existir */ }

        echo json_encode([
            'success'       => true,
            'numero_pedido' => (int)$cabecera['numero_pedido'],
            'numero_mesa'   => $numeroMesa,
            'nombre_mesero' => $nombreMesero,
            'nombre_cliente'=> $nombreCliente,
            'telefono'      => $telefonoCliente,
            'estado'        => $cabecera['estado'] ?? '',
            'pagado'        => $estaPagado,
            'fecha'         => $cabecera['fecha'],
            'productos'     => $productos,
            'comentario'    => $comentarioTexto,
            'comentarios'   => [],
            'mesas_libres'  => $mesasLibres
        ], JSON_UNESCAPED_UNICODE);
    }

    // ───────────────────────────────────────────────
    // GET /api.php?route=turnos&tipo_solicitud=51&since=X&limit=100
    // 🆕 CON SOPORTE PARA TRAER SOLO CAMBIOS RECIENTES (API DIFERENCIAL)
    // ───────────────────────────────────────────────
    private function obtenerTurnos(): void {
        $hoy  = date('Y-m-d');
        $tipo = $_GET['tipo_solicitud'] ?? '51';
        
        // 🆕 PARÁMETROS PARA API DIFERENCIAL
        $since = intval($_GET['since'] ?? 0);  // Timestamp en milisegundos
        $limit = intval($_GET['limit'] ?? 100); // Máximo items (default 100)
        
        // Convertir timestamp de ms a segundos
        $sinceSeconds = floor($since / 1000);

        // 🆕 LÓGICA: Si 'since' > 0, traer solo cambios recientes
        if ($since > 0) {
            // Traer SOLO turnos modificados DESPUÉS de 'since'
            $sql = "
    SELECT t.id_pedido AS numero_pedido, t.turno, t.estado, t.fecha, t.tipo_solicitud, t.id_cliente,
           t.pagado,
           UNIX_TIMESTAMP(t.fecha) * 1000 as timestamp
    FROM turnero t
    WHERE DATE(t.fecha) = :hoy 
      AND t.tipo_solicitud = :tipo
      AND UNIX_TIMESTAMP(t.fecha) > :since
                ORDER BY t.turno DESC
                LIMIT :limit
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':hoy', $hoy, PDO::PARAM_STR);
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindValue(':since', $sinceSeconds, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        } else {
            // Primera carga: traer últimos X turnos
           $sql = "
    SELECT t.id_pedido AS numero_pedido, t.turno, t.estado, t.fecha, t.tipo_solicitud, t.id_cliente,
           t.pagado,
           UNIX_TIMESTAMP(t.fecha) * 1000 as timestamp
    FROM turnero t
    WHERE DATE(t.fecha) = :hoy AND t.tipo_solicitud = :tipo
                ORDER BY t.turno DESC
                LIMIT :limit
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':hoy', $hoy, PDO::PARAM_STR);
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($turnos as &$t) {
            $t['numero_pedido'] = (int)$t['numero_pedido'];
            $t['turno']         = (int)$t['turno'];
            $t['pagado'] = (int)$t['pagado'];
            $t['tiene_domiciliario'] = 0;
            $t['cliente']       = 'Cliente #' . ($t['id_cliente'] ?? '?');
            $t['telefono']      = '';
            $t['direccion']     = '';
            $t['barrio']        = '';

            if ($t['id_cliente']) {
                try {
                    $stCli = $this->db->prepare("SELECT cliente, celular, direccion, barrio FROM clientes WHERE id = :id LIMIT 1");
                    $stCli->execute([':id' => $t['id_cliente']]);
                    $cli = $stCli->fetch(PDO::FETCH_ASSOC);
                    if ($cli) {
                        $t['cliente']   = $cli['cliente'] ?? $t['cliente'];
                        $t['telefono']  = $cli['celular'] ?? '';
                        $t['direccion'] = $cli['direccion'] ?? '';
                        $t['barrio']    = $cli['barrio'] ?? '';
                    }
                } catch (PDOException $e) { /* tabla clientes diferente */ }
            }
        }

        // 🆕 Enviar timestamp actual para el próximo 'since'
        echo json_encode([
            'success' => true, 
            'turnos' => $turnos, 
            'count' => count($turnos),
            'timestamp' => time() * 1000  // Timestamp actual en ms
        ], JSON_UNESCAPED_UNICODE);
    }

    // ───────────────────────────────────────────────
    // GET /api.php?route=productos&id_pedido=X
    // ───────────────────────────────────────────────
    private function obtenerProductos(): void {
        $id = (int)($_GET['id_pedido'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'id_pedido requerido']);
            return;
        }

        $stmt = $this->db->prepare("
            SELECT 
                pr.nombre AS nombre_producto,
                pr.prefijo,
                p.cantidad,
                p.detalle,
                p.tipo_producto AS tipo_prod,
                COALESCE(prp.precio, 0) AS precio
            FROM pedidos p
            JOIN productos pr ON p.id_pro = pr.id_pro
            LEFT JOIN precios prp ON pr.id_pro = prp.idproduc AND prp.tipo_prod = p.tipo_producto
            WHERE p.numero_pedido = :id
              AND p.cantidad > 0
              AND (p.detalle IS NULL OR p.detalle NOT LIKE 'ANULADO:%')
        ");
        $stmt->execute([':id' => $id]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($productos as &$p) {
            $p['precio']   = (float)$p['precio'];
            $p['cantidad'] = (int)$p['cantidad'];
        }

        // Costo domicilio (si aplica)
        $costoDom = 0;
        try {
            $stDom = $this->db->prepare("SELECT precio FROM domicilios WHERE id_pedido = :id LIMIT 1");
            $stDom->execute([':id' => $id]);
            $dom = $stDom->fetchColumn();
            if ($dom) $costoDom = (float)$dom;
        } catch (PDOException $e) { /* tabla domicilios puede no existir */ }

        // 🎯 OBTENER COMENTARIO DE LA TABLA comentarios
        $comentarioTexto = '';
        try {
            $stCom = $this->db->prepare("SELECT comentario FROM comentarios WHERE id_pedido = :id LIMIT 1");
            $stCom->execute([':id' => $id]);
            $com = $stCom->fetchColumn();
            if ($com) $comentarioTexto = (string)$com;
        } catch (PDOException $e) { /* tabla comentarios puede no existir */ }

        echo json_encode([
            'success'         => true,
            'productos'       => $productos,
            'costo_domicilio' => $costoDom,
            'comentario'      => $comentarioTexto,
            'count'           => count($productos)
        ], JSON_UNESCAPED_UNICODE);
    }

    // ───────────────────────────────────────────────
    // POST /api.php?route=mesas/estado
    // ───────────────────────────────────────────────
    private function cambiarEstadoMesa(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['numero_pedido'] ?? 0);
        $est  = $data['nuevo_estado'] ?? '';

        if (!$id || !$est) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }

        $this->db->prepare("UPDATE turnero SET estado = :est WHERE id_pedido = :id")->execute([':est' => $est, ':id' => $id]);
        $this->db->prepare("UPDATE mesas SET estado = :est WHERE id_pedido = :id")->execute([':est' => $est, ':id' => $id]);

        echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
    }

    // ───────────────────────────────────────────────
    // POST /api.php?route=turnos/estado
    // ───────────────────────────────────────────────
  private function cambiarEstadoTurno(): void
{
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($data['numero_pedido'] ?? 0);
    $est  = trim((string)($data['nuevo_estado'] ?? ''));

    if (!$id || !$est) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }

    // 1. Actualizar estado del pedido
    $this->db->prepare("UPDATE turnero SET estado = :est WHERE id_pedido = :id")
             ->execute([':est' => $est, ':id' => $id]);

    // 2. Consultar si está pagado
    $stPag = $this->db->prepare("SELECT pagado FROM turnero WHERE id_pedido = :id LIMIT 1");
    $stPag->execute([':id' => $id]);
    $pagado = (int)($stPag->fetchColumn() ?? 0);

    // 3. Enviar WhatsApp solo si es domicilio
    try {
        $sqlWA = "SELECT t.tipo_solicitud, c.celular
                  FROM turnero t
                  INNER JOIN clientes c ON t.id_cliente = c.id
                  WHERE t.id_pedido = :id
                  LIMIT 1";
        $stmtWA = $this->db->prepare($sqlWA);
        $stmtWA->execute([':id' => $id]);
        $datosWA = $stmtWA->fetch(PDO::FETCH_ASSOC);

        // Solo domicilios
        if ($datosWA && (int)($datosWA['tipo_solicitud'] ?? 0) === 50) {
            $numeroBase = preg_replace('/[^\d]/', '', trim((string)($datosWA['celular'] ?? '')));

            if (!empty($numeroBase)) {
                $telefono = $numeroBase;

                if (strlen($telefono) === 10) {
                    $telefono = '57' . $telefono;
                }

                $estadoNormalizado = mb_strtolower(trim($est));
                $estadoNormalizado = str_replace([' ', '-'], '_', $estadoNormalizado);

                $plantilla = null;

                // Cocina
                if (in_array($estadoNormalizado, [
                    'espera',
                    'cocina'
                ], true)) {
                    $plantilla = 'estadoheuiyu';
                }
                // Entrega / listo para entregar
                elseif (in_array($estadoNormalizado, [
                    'empacando',
                    'empaque',
                    'salio_cocina',
                    'entregar',
                    'entregado',
                    'listo_entregar',
                    'listo_para_entrega'
                ], true)) {
                    $plantilla = 'entregadoheiyu';
                }
                // Domiciliario / en camino
                elseif (in_array($estadoNormalizado, [
                    'domiciliario',
                    'en_camino',
                    'asignar_domiciliario',
                    'asignado_domiciliario',
                    'asignar_domi',
                    'repartidor'
                ], true)) {
                    $plantilla = 'domiciliarioheiyu';
                }

                error_log('WhatsApp estado original: ' . $est);
                error_log('WhatsApp estado normalizado: ' . $estadoNormalizado);
                error_log('WhatsApp plantilla elegida: ' . ($plantilla ?? 'NINGUNA'));

                if ($plantilla) {
                    $parametroBoton = $numeroBase;

                    try {
                        $sqlInsert = "INSERT INTO whatsapp_log
                                      (numero_pedido, estado_pedido, celular, plantilla, idioma, estado_mensaje)
                                      VALUES (:numero_pedido, :estado_pedido, :celular, :plantilla, :idioma, 'pendiente')";

                        $stmtInsert = $this->db->prepare($sqlInsert);
                        $stmtInsert->execute([
                            ':numero_pedido' => $id,
                            ':estado_pedido' => $est,
                            ':celular' => $telefono,
                            ':plantilla' => $plantilla,
                            ':idioma' => 'es_CO'
                        ]);

                        $logId = $this->db->lastInsertId();

                        $resultado = EnviarMensajeWhatsApp::enviar(
                            $telefono,
                            $plantilla,
                            'es_CO',
                            $parametroBoton
                        );

                        if (!empty($resultado['success'])) {
                            $sqlUpdate = "UPDATE whatsapp_log
                                          SET estado_mensaje = 'enviado',
                                              resultado_exito = 1,
                                              message_id = :message_id,
                                              http_code = :http_code
                                          WHERE id = :id";

                            $stmtUpdate = $this->db->prepare($sqlUpdate);
                            $stmtUpdate->execute([
                                ':message_id' => $resultado['message_id'] ?? '',
                                ':http_code' => $resultado['http_code'] ?? 200,
                                ':id' => $logId
                            ]);
                        } else {
                            $sqlUpdate = "UPDATE whatsapp_log
                                          SET estado_mensaje = 'error',
                                              resultado_exito = 0,
                                              mensaje_error = :mensaje_error,
                                              http_code = :http_code
                                          WHERE id = :id";

                            $stmtUpdate = $this->db->prepare($sqlUpdate);
                            $stmtUpdate->execute([
                                ':mensaje_error' => $resultado['error'] ?? 'Error',
                                ':http_code' => $resultado['http_code'] ?? 0,
                                ':id' => $logId
                            ]);
                        }
                    } catch (Throwable $e) {
                        error_log("WhatsApp error: " . $e->getMessage());
                    }
                }
            }
        }
    } catch (Throwable $e) {
        error_log("WhatsApp check error: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado',
        'nuevo_estado' => $est,
        'pagado' => $pagado,
        'tiene_domiciliario' => 0
    ], JSON_UNESCAPED_UNICODE);
}

    // ───────────────────────────────────────────────
    // POST /api.php?route=mesas/liberar
    // ───────────────────────────────────────────────
    private function liberarMesa(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $mesa = (int)($data['numero_mesa'] ?? 0);
        if (!$mesa) {
            echo json_encode(['success' => false, 'message' => 'Mesa requerida']);
            return;
        }

        $this->db->prepare("UPDATE mesas SET id_pedido = NULL, estado = '', fecha = NULL WHERE numero_mesa = :m")
                 ->execute([':m' => $mesa]);

        echo json_encode(['success' => true, 'message' => 'Mesa liberada']);
    }

    // ───────────────────────────────────────────────
    // POST /api.php?route=mesas/cambiar
    // ───────────────────────────────────────────────
    private function cambiarMesa(): void {
        $data   = json_decode(file_get_contents('php://input'), true);
        $id     = (int)($data['numero_pedido'] ?? 0);
        $nueva  = (int)($data['nueva_mesa'] ?? 0);
        $actual = (int)($data['mesa_actual'] ?? 0);

        if (!$id || !$nueva) {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
            return;
        }

        $est = $this->db->prepare("SELECT estado FROM mesas WHERE id_pedido = :id LIMIT 1");
        $est->execute([':id' => $id]);
        $estado = $est->fetchColumn() ?: '';

        if ($actual) {
            $this->db->prepare("UPDATE mesas SET id_pedido = NULL, estado = '', fecha = NULL WHERE numero_mesa = :m")
                     ->execute([':m' => $actual]);
        }

        $this->db->prepare("UPDATE mesas SET id_pedido = :id, estado = :est, fecha = NOW() WHERE numero_mesa = :m")
                 ->execute([':id' => $id, ':est' => $estado, ':m' => $nueva]);

        $this->db->prepare("UPDATE pedidos SET mesa = :m WHERE numero_pedido = :id")
                 ->execute([':m' => $nueva, ':id' => $id]);

        echo json_encode(['status' => 'success', 'message' => 'Mesa cambiada']);
    }
}
?>