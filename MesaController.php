<?php
namespace App\Controllers;
use Core\Response;
use Core\Logger;

class MesaController {
    private $conn;
    
    public function __construct() {
        $db = \Database::getInstance();
        $this->conn = $db->getConnection();
    }
    
    public function index() {
        try {
            $query = "SELECT m.*, p.estado, p.numero_pedido as id_pedido 
                      FROM mesas m 
                      LEFT JOIN pedido p ON m.numero_mesa = p.mesa AND p.estado != 'entregado'
                      ORDER BY m.numero_mesa";
            $stmt = $this->conn->query($query);
            $mesas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            Response::json(['success' => true, 'mesas' => $mesas]);
        } catch (\Exception $e) {
            Logger::error('Error: ' . $e->getMessage());
            Response::jsonError('Error', [], 500);
        }
    }
    
    public function liberar() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $numeroMesa = $data['numero_mesa'] ?? null;
            
            if (!$numeroMesa) {
                Response::jsonError('Número de mesa requerido', [], 400);
                return;
            }
            
            $query = "UPDATE mesas SET estado = 'libre' WHERE numero_mesa = :numero";
            $stmt = $this->conn->prepare($query);
            $success = $stmt->execute([':numero' => $numeroMesa]);
            
            if ($success) {
                Response::jsonSuccess('Mesa liberada');
            } else {
                Response::jsonError('Error', [], 500);
            }
        } catch (\Exception $e) {
            Logger::error('Error: ' . $e->getMessage());
            Response::jsonError('Error', [], 500);
        }
    }
}