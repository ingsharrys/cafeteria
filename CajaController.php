<?php
namespace App\Controllers;
use Core\Response;
use Core\Logger;

class CajaController {
    private $conn;
    
    public function __construct() {
        $db = \Database::getInstance();
        $this->conn = $db->getConnection();
    }
    
    public function registrarPago() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id_pedido']) || empty($data['costo'])) {
                Response::jsonError('Datos incompletos', [], 400);
                return;
            }
            
            Response::jsonSuccess('Pago registrado exitosamente');
        } catch (\Exception $e) {
            Logger::error('Error: ' . $e->getMessage());
            Response::jsonError('Error', [], 500);
        }
    }
    
    public function obtenerTotales() {
        try {
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            
            $query = "SELECT 
                        SUM(CASE WHEN m_pago = 'efectivo' THEN costo ELSE 0 END) as total_efectivo,
                        SUM(CASE WHEN m_pago = 'tarjeta' THEN costo ELSE 0 END) as total_tarjeta,
                        SUM(CASE WHEN m_pago = 'transferencia' THEN costo ELSE 0 END) as total_transferencia
                      FROM caja WHERE DATE(fecha_caja) = :fecha";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':fecha' => $fecha]);
            $totales = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            Response::json(['success' => true, 'totales' => $totales]);
        } catch (\Exception $e) {
            Logger::error('Error: ' . $e->getMessage());
            Response::jsonError('Error', [], 500);
        }
    }
    
    public function consolidado() {
        try {
            Response::json(['success' => true, 'message' => 'Consolidado disponible']);
        } catch (\Exception $e) {
            Response::jsonError('Error', [], 500);
        }
    }
}