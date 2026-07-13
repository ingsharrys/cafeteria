<?php
namespace App\Controllers;
use Core\Response;
use Core\Logger;

class PedidoController {
    private $conn;
    
    public function __construct() {
        $db = \Database::getInstance();
        $this->conn = $db->getConnection();
    }
    
    public function obtenerDatos() {
        try {
            $numeroPedido = $_GET['numero_pedido'] ?? null;
            if (!$numeroPedido) {
                Response::jsonError('Número de pedido requerido', [], 400);
                return;
            }
            
            $query = "SELECT * FROM pedido WHERE numero_pedido = :numero LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':numero' => $numeroPedido]);
            $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$pedido) {
                Response::jsonError('Pedido no encontrado', [], 404);
                return;
            }
            
            Response::json(['success' => true, 'pedido' => $pedido]);
        } catch (\Exception $e) {
            Logger::error('Error en obtenerDatos: ' . $e->getMessage());
            Response::jsonError('Error del servidor', [], 500);
        }
    }
    
    public function obtenerProductos() {
        try {
            $idPedido = $_GET['id_pedido'] ?? null;
            if (!$idPedido) {
                Response::jsonError('ID requerido', [], 400);
                return;
            }
            
            $query = "SELECT p.*, pr.nombre_producto 
                      FROM pedido p 
                      LEFT JOIN productos pr ON p.id_pro = pr.id_pro 
                      WHERE p.numero_pedido = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id' => $idPedido]);
            $productos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            Response::json(['success' => true, 'productos' => $productos]);
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
            
            $query = "UPDATE pedido SET estado = :estado WHERE numero_pedido = :numero";
            $stmt = $this->conn->prepare($query);
            $success = $stmt->execute([
                ':estado' => $nuevoEstado,
                ':numero' => $numeroPedido
            ]);
            
            if ($success) {
                Response::jsonSuccess('Estado actualizado');
            } else {
                Response::jsonError('Error al actualizar', [], 500);
            }
        } catch (\Exception $e) {
            Logger::error('Error: ' . $e->getMessage());
            Response::jsonError('Error', [], 500);
        }
    }
}