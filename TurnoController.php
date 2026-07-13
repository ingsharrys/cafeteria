<?php
namespace App\Controllers;
use Core\Response;
use Core\Logger;

class TurnoController {
    private $conn;
    
    public function __construct() {
        $db = \Database::getInstance();
        $this->conn = $db->getConnection();
    }
    
    public function index() {
        try {
            $tipoSolicitud = $_GET['tipo_solicitud'] ?? '51';
            $fecha = date('Y-m-d');
            
            $query = "SELECT t.*, c.cliente, c.telefono, c.direccion 
                      FROM turnero t 
                      LEFT JOIN clientes c ON t.id_cliente = c.id_cliente 
                      WHERE t.tipo_solicitud = :tipo AND DATE(t.fecha) = :fecha
                      ORDER BY t.turno DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':tipo' => $tipoSolicitud, ':fecha' => $fecha]);
            $turnos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            Response::json(['success' => true, 'turnos' => $turnos]);
        } catch (\Exception $e) {
            Logger::error('Error: ' . $e->getMessage());
            Response::jsonError('Error', [], 500);
        }
    }
    
    public function cambiarEstado() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $numeroPedido = $data['numero_pedido'] ?? null;
            $nuevoEstado = $data['nuevo_estado'] ?? null;
            
            if (!$numeroPedido || !$nuevoEstado) {
                Response::jsonError('Datos incompletos', [], 400);
                return;
            }
            
            $query = "UPDATE turnero SET estado = :estado WHERE id_pedido = :pedido";
            $stmt = $this->conn->prepare($query);
            $success = $stmt->execute([
                ':estado' => $nuevoEstado,
                ':pedido' => $numeroPedido
            ]);
            
            if ($success) {
                Response::json(['success' => true, 'message' => 'Estado actualizado']);
            } else {
                Response::jsonError('Error', [], 500);
            }
        } catch (\Exception $e) {
            Logger::error('Error: ' . $e->getMessage());
            Response::jsonError('Error', [], 500);
        }
    }
}