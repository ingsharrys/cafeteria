<?php
namespace App\Controllers;

// ✅ CARGAR el modelo antes de usarlo
require_once __DIR__ . '/../models/Zona.php';

use App\Models\Zona;

class ZonaController {
    private $zonaModel;
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        $this->zonaModel = new Zona($db);
    }
    
    public function handle($route, $method) {
    try {
        $parts = explode('/', $route);
        $action = isset($parts[1]) ? $parts[1] : null;
        $id = isset($parts[2]) ? $parts[2] : null;
        
        // Si action es numérico, es un ID
        if (is_numeric($action)) {
            $id = $action;
        }
        
        // Procesar según el método y si hay ID
        if ($id && is_numeric($id)) {
            if ($method === 'GET') {
                $this->obtenerPorId($id);
            } elseif ($method === 'PUT') {
                $this->actualizar($id);
            } elseif ($method === 'DELETE') {
                $this->eliminar($id);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            }
        } elseif ($action === 'activas' && $method === 'GET') {
            $this->obtenerActivas();
        } elseif ($method === 'GET') {
            $this->index();
        } elseif ($method === 'POST') {
            $this->crear();
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Ruta no encontrada']);
        }
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

    
    private function index() {
        $zonas = $this->zonaModel->obtenerTodas();
        echo json_encode(['success' => true, 'zonas' => $zonas]);
    }
    
    private function obtenerActivas() {
        $zonas = $this->zonaModel->obtenerActivas();
        echo json_encode(['success' => true, 'zonas' => $zonas]);
    }
    
    private function obtenerPorId($id) {
        $zona = $this->zonaModel->obtenerConBarrios($id);
        if ($zona) {
            echo json_encode(['success' => true, 'zona' => $zona]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Zona no encontrada']);
        }
    }
    
    private function crear() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['nombre_zona']) || !isset($data['tarifa_domicilio'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
        
        if ($this->zonaModel->existePorNombre($data['nombre_zona'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'La zona ya existe']);
            return;
        }
        
        $this->zonaModel->nombre_zona = $data['nombre_zona'];
        $this->zonaModel->descripcion = $data['descripcion'] ?? '';
        $this->zonaModel->tarifa_domicilio = (float)$data['tarifa_domicilio'];
        $this->zonaModel->estado = $data['estado'] ?? 'activo';
        
        if ($this->zonaModel->crear()) {
            echo json_encode([
                'success' => true,
                'message' => 'Zona creada exitosamente',
                'id_zona' => $this->zonaModel->id_zona
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al crear zona']);
        }
    }
    
    private function actualizar($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['nombre_zona']) || !isset($data['tarifa_domicilio'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
        
        if ($this->zonaModel->existePorNombre($data['nombre_zona'], $id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'La zona ya existe']);
            return;
        }
        
        $this->zonaModel->id_zona = $id;
        $this->zonaModel->nombre_zona = $data['nombre_zona'];
        $this->zonaModel->descripcion = $data['descripcion'] ?? '';
        $this->zonaModel->tarifa_domicilio = (float)$data['tarifa_domicilio'];
        $this->zonaModel->estado = $data['estado'] ?? 'activo';
        
        if ($this->zonaModel->actualizar()) {
            echo json_encode(['success' => true, 'message' => 'Zona actualizada exitosamente']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar zona']);
        }
    }
    
    private function eliminar($id) {
        if ($this->zonaModel->eliminar($id)) {
            echo json_encode(['success' => true, 'message' => 'Zona eliminada exitosamente']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar zona']);
        }
    }
}