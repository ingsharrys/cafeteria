<?php
namespace App\Controllers;

require_once __DIR__ . '/../models/Barrio.php';
require_once __DIR__ . '/../models/Zona.php';

use App\Models\Barrio;
use App\Models\Zona;

class BarrioController {
    private $barrioModel;
    private $zonaModel;
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        $this->barrioModel = new Barrio($db);
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
        $barrios = $this->barrioModel->obtenerTodos();
        echo json_encode(['success' => true, 'barrios' => $barrios]);
    }
    
    private function obtenerPorId($id) {
        $barrio = $this->barrioModel->obtenerPorId($id);
        if ($barrio) {
            echo json_encode(['success' => true, 'barrio' => $barrio]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Barrio no encontrado']);
        }
    }
    
    private function crear() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id_zona']) || !isset($data['nombre_barrio'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
        
        if ($this->barrioModel->existePorNombre($data['nombre_barrio'], $data['id_zona'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El barrio ya existe en esta zona']);
            return;
        }
        
        $this->barrioModel->id_zona = (int)$data['id_zona'];
        $this->barrioModel->nombre_barrio = $data['nombre_barrio'];
        $this->barrioModel->descripcion = $data['descripcion'] ?? '';
        $this->barrioModel->estado = $data['estado'] ?? 'activo';
        
        if ($this->barrioModel->crear()) {
            echo json_encode([
                'success' => true,
                'message' => 'Barrio creado exitosamente',
                'id_barrio' => $this->barrioModel->id_barrio
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al crear barrio']);
        }
    }
    
    private function actualizar($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['nombre_barrio']) || !isset($data['id_zona'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
        
        if ($this->barrioModel->existePorNombre($data['nombre_barrio'], $data['id_zona'], $id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El barrio ya existe en esta zona']);
            return;
        }
        
        $this->barrioModel->id_barrio = $id;
        $this->barrioModel->id_zona = (int)$data['id_zona'];
        $this->barrioModel->nombre_barrio = $data['nombre_barrio'];
        $this->barrioModel->descripcion = $data['descripcion'] ?? '';
        $this->barrioModel->estado = $data['estado'] ?? 'activo';
        
        if ($this->barrioModel->actualizar()) {
            echo json_encode(['success' => true, 'message' => 'Barrio actualizado exitosamente']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar barrio']);
        }
    }
    
    private function eliminar($id) {
        if ($this->barrioModel->eliminar($id)) {
            echo json_encode(['success' => true, 'message' => 'Barrio eliminado exitosamente']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar barrio']);
        }
    }
}