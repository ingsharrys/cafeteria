<?php
namespace App\Controllers;

use App\Models\Turno;
use App\Models\Pedido;
use Core\Response;
use Core\Logger;

/**
 * TurnoController
 * Maneja todas las operaciones relacionadas con turnos (domicilios/recoger)
 */
class TurnoController {
    private $turnoModel;
    private $pedidoModel;
    
    public function __construct() {
        $db = \Database::getInstance()->getConnection();
        $this->turnoModel = new Turno($db);
        $this->pedidoModel = new Pedido($db);
    }
    
    /**
     * API: Obtener turnos por tipo de solicitud
     * Reemplaza: obtener_datos_turnos.php
     */
    public function index() {
        try {
            $tipoSolicitud = $_GET['tipo_solicitud'] ?? null;
            $since = $_GET['since'] ?? 0;
            
            if (!$tipoSolicitud) {
                Response::jsonError('Tipo de solicitud requerido', [], 400);
                return;
            }
            
            $fecha = date('Y-m-d');
            $turnos = $this->turnoModel->obtenerPorTipo($tipoSolicitud, $fecha);
            
            Response::json([
                'success' => true,
                'turnos' => $turnos,
                'count' => count($turnos)
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Error obteniendo turnos', [
                'error' => $e->getMessage(),
                'tipo_solicitud' => $_GET['tipo_solicitud'] ?? 'none'
            ]);
            Response::jsonError('Error obteniendo turnos', [$e->getMessage()], 500);
        }
    }
    
    /**
     * API: Cambiar estado de turno
     * Reemplaza: actualizar_estado_turnero.php
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
            
            // Actualizar estado en turnero
            $success = $this->turnoModel->actualizarEstado($numeroPedido, $nuevoEstado);
            
            if ($success) {
                // Verificar si está pagado
                $pagado = $this->turnoModel->estaPagado($numeroPedido);
                $tieneDomiciliario = $this->turnoModel->tieneDomiciliario($numeroPedido);
                
                Logger::info("Estado de turno actualizado", [
                    'pedido' => $numeroPedido,
                    'estado' => $nuevoEstado
                ]);
                
                Response::json([
                    'success' => true,
                    'message' => 'Estado actualizado correctamente',
                    'nuevo_estado' => $nuevoEstado,
                    'pagado' => $pagado ? 1 : 0,
                    'tiene_domiciliario' => $tieneDomiciliario ? 1 : 0
                ]);
            } else {
                Response::json([
                    'success' => false,
                    'message' => 'Error al actualizar estado'
                ]);
            }
            
        } catch (\Exception $e) {
            Logger::error('Error cambiando estado turno', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [$e->getMessage()], 500);
        }
    }
    
    /**
     * API: Obtener turnos pendientes (no entregados o no pagados)
     */
    public function pendientes() {
        try {
            $tipoSolicitud = $_GET['tipo_solicitud'] ?? null;
            
            if (!$tipoSolicitud) {
                Response::jsonError('Tipo de solicitud requerido', [], 400);
                return;
            }
            
            $turnos = $this->turnoModel->obtenerPendientes($tipoSolicitud);
            
            Response::json([
                'success' => true,
                'turnos' => $turnos,
                'count' => count($turnos)
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Error obteniendo turnos pendientes', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [$e->getMessage()], 500);
        }
    }
    
    /**
     * API: Crear nuevo turno
     */
    public function crear() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $this->turnoModel->id_pedido = $data['id_pedido'] ?? null;
            $this->turnoModel->id_cliente = $data['id_cliente'] ?? null;
            $this->turnoModel->tipo_solicitud = $data['tipo_solicitud'] ?? null;
            
            if (!$this->turnoModel->id_pedido || !$this->turnoModel->tipo_solicitud) {
                Response::jsonError('Datos incompletos', [], 400);
                return;
            }
            
            $success = $this->turnoModel->crear();
            
            if ($success) {
                Logger::info("Turno creado", [
                    'pedido' => $this->turnoModel->id_pedido,
                    'turno' => $this->turnoModel->turno,
                    'tipo' => $this->turnoModel->tipo_solicitud
                ]);
                
                Response::json([
                    'success' => true,
                    'message' => 'Turno creado exitosamente',
                    'turno' => $this->turnoModel->turno
                ]);
            } else {
                Response::jsonError('Error al crear turno', [], 500);
            }
            
        } catch (\Exception $e) {
            Logger::error('Error creando turno', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [$e->getMessage()], 500);
        }
    }
    
    /**
     * API: Obtener turno por ID de pedido
     */
    public function obtenerPorPedido() {
        try {
            $numeroPedido = $_GET['numero_pedido'] ?? null;
            
            if (!$numeroPedido) {
                Response::jsonError('Número de pedido requerido', [], 400);
                return;
            }
            
            $turno = $this->turnoModel->obtenerPorPedido($numeroPedido);
            
            if ($turno) {
                Response::json([
                    'success' => true,
                    'turno' => $turno
                ]);
            } else {
                Response::jsonError('Turno no encontrado', [], 404);
            }
            
        } catch (\Exception $e) {
            Logger::error('Error obteniendo turno', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [], 500);
        }
    }
    
    /**
     * API: Obtener estadísticas de turnos
     */
    public function estadisticas() {
        try {
            $tipoSolicitud = $_GET['tipo_solicitud'] ?? null;
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            
            if (!$tipoSolicitud) {
                Response::jsonError('Tipo de solicitud requerido', [], 400);
                return;
            }
            
            $stats = $this->turnoModel->getEstadisticas($tipoSolicitud, $fecha);
            
            Response::json([
                'success' => true,
                'estadisticas' => $stats,
                'fecha' => $fecha
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Error obteniendo estadísticas', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [], 500);
        }
    }
    
    /**
     * API: Eliminar turno
     */
    public function eliminar() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $numeroPedido = $data['numero_pedido'] ?? null;
            
            if (!$numeroPedido) {
                Response::jsonError('Número de pedido requerido', [], 400);
                return;
            }
            
            $success = $this->turnoModel->eliminar($numeroPedido);
            
            if ($success) {
                Logger::info("Turno eliminado", [
                    'pedido' => $numeroPedido
                ]);
                Response::jsonSuccess('Turno eliminado exitosamente');
            } else {
                Response::jsonError('Error al eliminar turno', [], 500);
            }
            
        } catch (\Exception $e) {
            Logger::error('Error eliminando turno', [
                'error' => $e->getMessage()
            ]);
            Response::jsonError('Error del servidor', [], 500);
        }
    }
}