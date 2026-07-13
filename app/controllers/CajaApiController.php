<?php
/**
 * CajaApiController
 * Maneja: pagar, reversar, abonar crédito, pagar lote
 * Incluye: brebe, brebe_efectivo
 * UBICACIÓN: heiyubai/CajaApiController.php
 */
class CajaApiController {
    private $db;

    /** Métodos de pago válidos */
    private const METODOS = [
        'efectivo','transferencia','efectivo_transferencia','tarjeta',
        'credito','cortesia','devolucion','tarjeta_efectivo',
        'brebe','brebe_efectivo'
    ];

    /** Métodos que requieren campo efectivo */
    private const CON_EFECTIVO = ['efectivo','efectivo_transferencia','tarjeta_efectivo','brebe_efectivo'];

    /** Métodos que requieren banco + referencia */
    private const CON_BANCO = ['transferencia','efectivo_transferencia'];

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function handle(string $route, string $method): void {
        switch (true) {
            case ($route === 'caja/pagar' && $method === 'POST'):
                $this->procesarPago();
                break;
            case ($route === 'caja/reversar' && $method === 'POST'):
                $this->reversarPago();
                break;
            case ($route === 'caja/abonar' && $method === 'POST'):
                $this->abonarCredito();
                break;
            case ($route === 'caja/pagar_lote' && $method === 'POST'):
                $this->pagarLote();
                break;
            default:
                http_response_code(404);
                echo json_encode(['success'=>false, 'error'=>"Caja: ruta no encontrada {$method} {$route}"]);
        }
    }

    // ───────────────────────────────────────────────
    // POST /api.php?route=caja/pagar (FormData)
    // ───────────────────────────────────────────────
    private function procesarPago(): void {
        if (!isset($_POST['numero_pedido'])) {
            echo json_encode(['status'=>'error', 'message'=>'Datos incompletos']);
            return;
        }

        $np           = (int)$_POST['numero_pedido'];
        $descuento    = isset($_POST['descuento']) ? (float)$_POST['descuento'] : 0;
        $metodo       = $_POST['m_pago'] ?? '';
        $efectivo     = isset($_POST['pago']) ? (float)$_POST['pago'] : 0;
        $banco        = $_POST['banco'] ?? null;
        $referencia   = $_POST['referencia'] ?? null;
        $detalle      = $_POST['detalle'] ?? null;
        $idmesero     = $_POST['idmeses'] ?? null;
        $totalPagar   = isset($_POST['tpago']) ? (float)$_POST['tpago'] : 0;

        session_start();
        $cajero    = $_SESSION['cajero'] ?? 'Desconocido';
        $fechaHoy  = date('Y-m-d');

        if (!in_array($metodo, self::METODOS)) {
            echo json_encode(['status'=>'error', 'message'=>"Método no reconocido: {$metodo}"]);
            return;
        }

        try {
            $this->db->beginTransaction();

            // Verificar duplicado
            if ($this->yaExisteEnCaja($np)) {
                $this->db->rollBack();
                echo json_encode(['status'=>'error', 'message'=>'El pedido ya existe en caja']);
                return;
            }

            if ($metodo === 'credito') {
                $this->insertarCredito($np, $totalPagar, $descuento, $cajero, $fechaHoy, $idmesero, $efectivo, $metodo);
            } else {
                $this->insertarPagoNormal($np, $totalPagar, $metodo, $descuento, $cajero, $fechaHoy, $idmesero, $efectivo, $banco, $referencia, $detalle);
            }
            
            // ✅ MARCAR COMO PAGADO EN TURNERO
$stMarcado = $this->db->prepare("UPDATE turnero SET pagado = 1 WHERE id_pedido = :id");
$stMarcado->execute([':id' => $np]);

            $this->db->commit();
            echo json_encode(['status'=>'success', 'message'=>'Pago registrado correctamente']);

        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['status'=>'error', 'message'=>'Error: '.$e->getMessage()]);
        }
    }

    // ───────────────────────────────────────────────
    // POST /api.php?route=caja/reversar (JSON)
    // ───────────────────────────────────────────────
    private function reversarPago(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $np  = (int)($data['numero_pedido'] ?? 0);
        $cod = $data['codigo_seguridad'] ?? '';

        if (!$np || !$cod) {
            echo json_encode(['success'=>false, 'message'=>'Datos incompletos']);
            return;
        }

        if (!$this->validarCodigoSeguridad($cod)) {
            echo json_encode(['success'=>false, 'message'=>'Código de seguridad incorrecto']);
            return;
        }

        $st = $this->db->prepare("DELETE FROM caja WHERE id_pedidoc = :id");
        $st->execute([':id' => $np]);
        
        // ✅ ACTUALIZAR turnero.pagado = 0 AL REVERSAR
    if ($st->rowCount() > 0) {
        $stMarcado = $this->db->prepare("UPDATE turnero SET pagado = 0 WHERE id_pedido = :id");
        $stMarcado->execute([':id' => $np]);
    }

        echo json_encode([
            'success' => $st->rowCount() > 0,
            'message' => $st->rowCount() > 0 ? 'Caja reversada correctamente' : 'No se encontró registro en caja'
        ]);
    }

    // ───────────────────────────────────────────────
    // POST /api.php?route=caja/abonar (JSON)
    // ───────────────────────────────────────────────
    private function abonarCredito(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $idCredito = (int)($data['id_credito'] ?? 0);
        $abonos    = $data['abonos'] ?? [];

        if (!$idCredito || empty($abonos)) {
            echo json_encode(['status'=>'error', 'message'=>'Datos de abono incompletos']);
            return;
        }

        try {
            $this->db->beginTransaction();
            $st = $this->db->prepare("INSERT INTO abono_credito (id_credito, m_pagocr, efectivo, fecha_abono) VALUES (:ic, :mp, :ef, NOW())");

            foreach ($abonos as $ab) {
                $met = $ab['m_pagocr'] ?? '';
                $val = (float)($ab['efectivo'] ?? 0);
                if (!$met) throw new Exception('Método de abono no especificado');
                $st->execute([':ic'=>$idCredito, ':mp'=>$met, ':ef'=>$val]);
            }

            $this->db->commit();
            echo json_encode(['status'=>'success', 'message'=>'Abonos guardados correctamente']);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['status'=>'error', 'message'=>'Error: '.$e->getMessage()]);
        }
    }

    // ───────────────────────────────────────────────
    // POST /api.php?route=caja/pagar_lote (JSON)
    // Pago múltiple para domicilios
    // ───────────────────────────────────────────────
    private function pagarLote(): void {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['pedidos']) || !is_array($data['pedidos'])) {
            echo json_encode(['success'=>false, 'message'=>'Array de pedidos requerido']);
            return;
        }

        session_start();
        $cajero    = $_SESSION['cajero'] ?? 'Desconocido';
        $idCajero  = $_SESSION['usuario']['id_mese'] ?? 0;
        $fechaHoy  = date('Y-m-d');

        $exitosos = 0;
        $errores  = [];

        foreach ($data['pedidos'] as $ped) {
            $np       = (int)($ped['id_pedidoc'] ?? 0);
            $costo    = (float)($ped['costo'] ?? 0);
            $mp       = $ped['m_pago'] ?? '';
            $efectivo = (float)($ped['efectivo'] ?? 0);
            $banco    = $ped['banco'] ?? null;
            $ref      = $ped['referencia'] ?? null;

            if (!$np || !$costo || !$mp) {
                $errores[] = "Pedido {$np}: datos incompletos";
                continue;
            }

            if ($this->yaExisteEnCaja($np)) {
                $errores[] = "Pedido {$np}: ya pagado";
                continue;
            }

            try {
                $campos  = 'id_pedidoc, costo, m_pago, cajero, fecha_caja, id_cajero';
                $valores = ':id, :costo, :mpago, :cajero, :fecha, :idcaj';
                $params  = [':id'=>$np, ':costo'=>$costo, ':mpago'=>$mp, ':cajero'=>$cajero, ':fecha'=>$fechaHoy, ':idcaj'=>$idCajero];

                if (in_array($mp, self::CON_EFECTIVO)) {
                    $campos  .= ', efectivo';
                    $valores .= ', :efectivo';
                    $params[':efectivo'] = $efectivo;
                }

                if (in_array($mp, self::CON_BANCO)) {
                    $campos  .= ', banco, referencia';
                    $valores .= ', :banco, :ref';
                    $params[':banco'] = $banco;
                    $params[':ref']   = $ref;
                }

                $this->db->prepare("INSERT INTO caja ({$campos}) VALUES ({$valores})")->execute($params);
                $stMarcado = $this->db->prepare("UPDATE turnero SET pagado = 1 WHERE id_pedido = :id");
$stMarcado->execute([':id' => $np]);
                $exitosos++;
            } catch (PDOException $e) {
                $errores[] = "Pedido {$np}: ".$e->getMessage();
            }
        }

        echo json_encode([
            'success'  => empty($errores),
            'message'  => "Procesados: {$exitosos} pagos.",
            'errores'  => $errores,
            'exitosos' => $exitosos
        ]);
    }

    // ═══════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ═══════════════════════════════════════════════

    private function yaExisteEnCaja(int $np): bool {
        $st = $this->db->prepare("SELECT COUNT(*) FROM caja WHERE id_pedidoc = :id");
        $st->execute([':id' => $np]);
        return $st->fetchColumn() > 0;
    }

    private function validarCodigoSeguridad(string $cod): bool {
        $st = $this->db->prepare("SELECT codigo_seguridad FROM seguridad WHERE codigo_seguridad = :cod");
        $st->execute([':cod' => $cod]);
        return (bool)$st->fetch();
    }

    private function insertarCredito(int $np, float $costo, float $desc, string $cajero, string $fecha, $idMesero, float $efectivo, string $metodo): void {
        $st = $this->db->prepare("
            INSERT INTO caja (id_pedidoc, costo, m_pago, descuento, efectivo, cajero, fecha_caja, id_cajero)
            VALUES (:id, :costo, :mp, :desc, 0, :cajero, :fecha, :idcaj)
        ");
        $st->execute([':id'=>$np, ':costo'=>$costo, ':mp'=>$metodo, ':desc'=>$desc, ':cajero'=>$cajero, ':fecha'=>$fecha, ':idcaj'=>$idMesero]);

        // Buscar cliente
        $stCli = $this->db->prepare("SELECT id_cliente FROM turnero WHERE id_pedido = :id LIMIT 1");
        $stCli->execute([':id' => $np]);
        $row = $stCli->fetch(PDO::FETCH_ASSOC);
        $idCliente = $row ? $row['id_cliente'] : 1;

        // Crédito
        $stCred = $this->db->prepare("INSERT INTO creditos (id_cajero, id_clientecr, m_pedidocr, fecha) VALUES (:caj, :cli, :ped, NOW())");
        $stCred->execute([':caj'=>$cajero, ':cli'=>$idCliente, ':ped'=>$np]);
        $idCredito = $this->db->lastInsertId();

        // Abono inicial
        if ($efectivo > 0) {
            $this->db->prepare("INSERT INTO abono_credito (id_credito, m_pagocr, efectivo, fecha_abono) VALUES (:ic, :mp, :ef, NOW())")
                ->execute([':ic'=>$idCredito, ':mp'=>$metodo, ':ef'=>$efectivo]);
        }
    }

    private function insertarPagoNormal(int $np, float $costo, string $metodo, float $desc, string $cajero, string $fecha, $idMesero, float $efectivo, $banco, $ref, $detalle): void {
        $campos  = 'id_pedidoc, costo, m_pago, descuento, cajero, fecha_caja, id_cajero';
        $valores = ':id, :costo, :mpago, :desc, :cajero, :fecha, :idcaj';
        $params  = [':id'=>$np, ':costo'=>$costo, ':mpago'=>$metodo, ':desc'=>$desc, ':cajero'=>$cajero, ':fecha'=>$fecha, ':idcaj'=>$idMesero];

        // Efectivo
        if (in_array($metodo, self::CON_EFECTIVO)) {
            $campos  .= ', efectivo';
            $valores .= ', :efectivo';
            $params[':efectivo'] = $efectivo;
        }

        // Banco + Referencia
        if (in_array($metodo, self::CON_BANCO)) {
            $campos  .= ', banco, referencia';
            $valores .= ', :banco, :ref';
            $params[':banco'] = $banco;
            $params[':ref']   = $ref;
        }

        // Cortesía/Devolución
        if (in_array($metodo, ['cortesia','devolucion'])) {
            $campos  .= ', referencia';
            $valores .= ', :ref';
            $params[':ref'] = $detalle;
        }

        $this->db->prepare("INSERT INTO caja ({$campos}) VALUES ({$valores})")->execute($params);
    }
}