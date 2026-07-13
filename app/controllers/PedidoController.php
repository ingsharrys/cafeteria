<?php
namespace App\Controllers;

use App\Models\Pedido;
use App\Models\Mesa;
use Core\Response;
use Core\Logger;

/**
 * PedidoController
 * Maneja todas las operaciones relacionadas con pedidos
 */
class PedidoController {
    private $pedidoModel;
    private $mesaModel;
    
    public function __construct() {
        $db = \Database::getInstance()->getConnection();
        $this->pedidoModel = new Pedido($db);
        $this->mesaModel = new Mesa($db);
    }
    
    /**
     * API: Obtener datos completos de un pedido
     * Reemplaza: obtener_datos.php?numero_pedido=X
     */
    public function obtenerDatos() {
        try {
            $numeroPedido = $_GET['numero_pedido'] ?? null;
            
            if (!$numeroPedido) {
                Response::jsonError('Número de pedido requerido', [], 400);
                return;
            }
            
            $pedido = $this->pedidoModel->obtenerConDetalles($numeroPedido);
            
            if (!$pedido) {
                Response::jsonError('Pedido no encontrado', [], 404);
                return;
            }
            
            // Agregar mesas libres
            $pedido['mesas_libres'] = $this->mesaModel->obtenerLibres();
            $pedido['success'] = true;
            
            Response::json($pedido);
            
        } catch (\Exception $e) {
            Logger::error('Error en obtenerDatos', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            Response::jsonError('Error del servidor', [$e->getMessage()], 500);
        }
    }
    
    /**
     * API: Obtener solo productos de un pedido
     * Reemplaza: obtener_datos_pedido.php
     */
    public function obtenerProductos() {
        try {
            $idPedido = $_GET['id_pedido'] ?? null;
            
            if (!$idPedido) {
                Response::jsonError('ID de pedido requerido', [], 400);
                return;
            }
            
            $productos = $this->pedidoModel->getProductos($idPedido);
            $costoDomicilio = $this->pedidoModel->getCostoDomicilio($idPedido);
            $comentarios = $this->pedidoModel->getComentarios($idPedido);
            
            $comentario = 'Sin comentarios';
            if (!empty($comentarios)) {
                $comentario = is_array($comentarios) ? $comentarios[0] : $comentarios;
            }
            
            Response::json([
                'success' => true,
                'productos' => $productos,
                'costo_domicilio' => $costoDomicilio,
                'comentario' => $comentario,
                'count' => count($productos)
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Error en obtenerProductos', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error obteniendo productos', [$e->getMessage()], 500);
        }
    }
    
    /**
     * API: Cambiar estado de pedido
     */
    public function cambiarEstado() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $numeroPedido = $data['numero_pedido'] ?? null;
            $nuevoEstado = $data['nuevo_estado'] ?? null;
            
            if (!$numeroPedido || !$nuevoEstado) {
                Response::jsonError('Datos incompletos', [], 400);
                return;
            }
            
            $success = $this->pedidoModel->actualizarEstado($numeroPedido, $nuevoEstado);
            
            if ($success) {
                Logger::info("Estado de pedido actualizado", [
                    'pedido' => $numeroPedido,
                    'estado' => $nuevoEstado
                ]);
                
                Response::json([
                    'success' => true,
                    'message' => 'Estado actualizado correctamente',
                    'nuevo_estado' => $nuevoEstado
                ]);
            } else {
                Response::jsonError('Error al actualizar estado', [], 500);
            }
            
        } catch (\Exception $e) {
            Logger::error('Error en cambiarEstado', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [$e->getMessage()], 500);
        }
    }
    
    /**
     * API: Crear nuevo pedido
     */
    public function crear() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $this->pedidoModel->numero_pedido = $data['numero_pedido'] ?? null;
            $this->pedidoModel->id_pro = $data['id_pro'] ?? null;
            $this->pedidoModel->cantidad = $data['cantidad'] ?? 1;
            $this->pedidoModel->detalle = $data['detalle'] ?? '';
            $this->pedidoModel->tipo_producto = $data['tipo_producto'] ?? null;
            $this->pedidoModel->mesa = $data['mesa'] ?? null;
            $this->pedidoModel->mesero = $data['mesero'] ?? null;
            
            $success = $this->pedidoModel->crear();
            
            if ($success) {
                Logger::info("Pedido creado", [
                    'numero_pedido' => $this->pedidoModel->numero_pedido
                ]);
                Response::jsonSuccess('Pedido creado exitosamente');
            } else {
                Response::jsonError('Error al crear pedido', [], 500);
            }
            
        } catch (\Exception $e) {
            Logger::error('Error creando pedido', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [$e->getMessage()], 500);
        }
    }
    
    /**
     * API: Obtener total de un pedido
     */
    public function obtenerTotal() {
        try {
            $numeroPedido = $_GET['numero_pedido'] ?? null;
            
            if (!$numeroPedido) {
                Response::jsonError('Número de pedido requerido', [], 400);
                return;
            }
            
            $total = $this->pedidoModel->getTotal($numeroPedido);
            
            Response::json([
                'success' => true,
                'total' => $total
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Error obteniendo total', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [], 500);
        }
    }
}