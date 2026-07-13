<?php
namespace App\Controllers;

use App\Models\Caja;
use App\Models\Pedido;
use App\Models\Gasto;
use Core\Session;
use Core\Response;
use Core\Logger;
use Core\Validator;

/**
 * CajaController
 * Maneja todas las operaciones de caja, pagos y consolidados
 */
class CajaController {
    private $cajaModel;
    private $pedidoModel;
    private $gastoModel;
    private $validator;
    
    public function __construct() {
        $db = \Database::getInstance()->getConnection();
        $this->cajaModel = new Caja($db);
        $this->pedidoModel = new Pedido($db);
        $this->gastoModel = new Gasto($db);
        $this->validator = new Validator();
    }
  
    /**
     * API: Registrar pago de un pedido
     */
    public function registrarPago() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar datos requeridos
            $this->validator->required($data['id_pedido'] ?? '', 'id_pedido');
            $this->validator->required($data['costo'] ?? '', 'costo');
            $this->validator->required($data['m_pago'] ?? '', 'm_pago');
            
            if ($this->validator->hasErrors()) {
                Response::jsonError(
                    'Datos incompletos',
                    $this->validator->getErrors(),
                    400
                );
                return;
            }
            
            // Verificar que el pedido no esté ya pagado
            if ($this->pedidoModel->estaPagado($data['id_pedido'])) {
                Response::jsonError('Este pedido ya está pagado', [], 400);
                return;
            }
            
            // Registrar pago
            $this->cajaModel->id_pedidoc = $data['id_pedido'];
            $this->cajaModel->costo = $data['costo'];
            $this->cajaModel->efectivo = $data['efectivo'] ?? $data['costo'];
            $this->cajaModel->m_pago = $data['m_pago'];
            $this->cajaModel->id_cajero = Session::get('usuario')['id_mese'] ?? 0;
            
            $success = $this->cajaModel->registrarPago();
            
            if ($success) {
                Logger::info("Pago registrado", [
                    'pedido' => $data['id_pedido'],
                    'monto' => $data['costo'],
                    'metodo' => $data['m_pago'],
                    'cajero' => $this->cajaModel->id_cajero
                ]);
                
                Response::json([
                    'success' => true,
                    'message' => 'Pago registrado exitosamente',
                    'id_caja' => $this->cajaModel->id_caja
                ]);
            } else {
                Response::jsonError('Error al registrar pago', [], 500);
            }
            
        } catch (\Exception $e) {
            Logger::error('Error registrando pago', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [$e->getMessage()], 500);
        }
    }
    
    /**
     * API: Obtener movimientos de caja del día
     */
    public function obtenerDelDia() {
        try {
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $cajero = $_GET['cajero'] ?? null;
            
            $movimientos = $this->cajaModel->obtenerDelDia($fecha, $cajero);
            
            Response::json([
                'success' => true,
                'movimientos' => $movimientos,
                'fecha' => $fecha,
                'count' => count($movimientos)
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Error obteniendo movimientos de caja', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [], 500);
        }
    }
    
    /**
     * API: Obtener totales por método de pago
     */
    public function obtenerTotales() {
        try {
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $cajero = $_GET['cajero'] ?? null;
            
            $totales = $this->cajaModel->getTotalesPorMetodo($fecha, $cajero);
            
            // Calcular totales finales
            $totalEfectivo = (float)$totales['total_efectivo'] 
                           + (float)$totales['efectivo_mixto'] 
                           + (float)$totales['efectivo_tarjeta_mixto'];
                           
            $totalTarjeta = (float)$totales['total_tarjeta'] 
                          + (float)$totales['tarjeta_mixto'];
                          
            $totalTransferencia = (float)$totales['total_transferencia'] 
                                + (float)$totales['transferencia_mixto'];
            
            Response::json([
                'success' => true,
                'totales' => [
                    'efectivo' => $totalEfectivo,
                    'tarjeta' => $totalTarjeta,
                    'transferencia' => $totalTransferencia,
                    'total_general' => $totalEfectivo + $totalTarjeta + $totalTransferencia
                ],
                'detalles' => $totales,
                'fecha' => $fecha
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Error obteniendo totales', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [], 500);
        }
    }
    
    /**
     * API: Obtener consolidado del día
     */
    public function consolidado() {
        try {
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $cajero = $_GET['cajero'] ?? null;
            
            // Obtener totales de caja
            $totales = $this->cajaModel->getTotalesPorMetodo($fecha, $cajero);
            
            // Calcular totales
            $totalEfectivo = (float)$totales['total_efectivo'] 
                           + (float)$totales['efectivo_mixto'] 
                           + (float)$totales['efectivo_tarjeta_mixto'];
                           
            $totalTarjeta = (float)$totales['total_tarjeta'] 
                          + (float)$totales['tarjeta_mixto'];
                          
            $totalTransferencia = (float)$totales['total_transferencia'] 
                                + (float)$totales['transferencia_mixto'];
            
            $totalIngresos = $totalEfectivo + $totalTarjeta + $totalTransferencia;
            
            // Obtener gastos
            $totalGastos = $this->gastoModel->getTotalDelDia($fecha);
            
            // Calcular balance
            $balance = $totalIngresos - $totalGastos;
            
            Response::json([
                'success' => true,
                'consolidado' => [
                    'ingresos' => [
                        'efectivo' => $totalEfectivo,
                        'tarjeta' => $totalTarjeta,
                        'transferencia' => $totalTransferencia,
                        'total' => $totalIngresos
                    ],
                    'gastos' => $totalGastos,
                    'balance' => $balance,
                    'fecha' => $fecha
                ]
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Error obteniendo consolidado', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [], 500);
        }
    }
    
    /**
     * API: Registrar gasto
     */
    public function registrarGasto() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar datos
            $this->validator->required($data['concepto'] ?? '', 'concepto');
            $this->validator->required($data['monto'] ?? '', 'monto');
            
            if ($this->validator->hasErrors()) {
                Response::jsonError(
                    'Datos incompletos',
                    $this->validator->getErrors(),
                    400
                );
                return;
            }
            
            // Crear gasto
            $this->gastoModel->concepto = $data['concepto'];
            $this->gastoModel->monto = $data['monto'];
            $this->gastoModel->fecha = $data['fecha'] ?? date('Y-m-d');
            $this->gastoModel->id_usuario = Session::get('usuario')['id_mese'] ?? 0;
            
            $success = $this->gastoModel->crear();
            
            if ($success) {
                Logger::info("Gasto registrado", [
                    'concepto' => $data['concepto'],
                    'monto' => $data['monto'],
                    'usuario' => $this->gastoModel->id_usuario
                ]);
                
                Response::json([
                    'success' => true,
                    'message' => 'Gasto registrado exitosamente',
                    'id' => $this->gastoModel->id
                ]);
            } else {
                Response::jsonError('Error al registrar gasto', [], 500);
            }
            
        } catch (\Exception $e) {
            Logger::error('Error registrando gasto', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [], 500);
        }
    }
    
    /**
     * API: Obtener gastos del día
     */
    public function obtenerGastos() {
        try {
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            
            $gastos = $this->gastoModel->obtenerDelDia($fecha);
            $totalGastos = $this->gastoModel->getTotalDelDia($fecha);
            
            Response::json([
                'success' => true,
                'gastos' => $gastos,
                'total' => $totalGastos,
                'fecha' => $fecha
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Error obteniendo gastos', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [], 500);
        }
    }
    
    /**
     * API: Verificar si un pedido está pagado
     */
    public function verificarPago() {
        try {
            $numeroPedido = $_GET['numero_pedido'] ?? null;
            
            if (!$numeroPedido) {
                Response::jsonError('Número de pedido requerido', [], 400);
                return;
            }
            
            $pagado = $this->pedidoModel->estaPagado($numeroPedido);
            
            Response::json([
                'success' => true,
                'pagado' => $pagado
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Error verificando pago', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [], 500);
        }
    }
}