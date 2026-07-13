<?php
namespace App\Models;

/**
 * Modelo Zona
 * Maneja zonas de domicilios y sus tarifas
 */
class Zona {
    private $conn;
    private $table = "zonas";

    public $id_zona;
    public $nombre_zona;
    public $descripcion;
    public $tarifa_domicilio;
    public $estado;
    public $fecha_creacion;
    public $fecha_actualizacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtener todas las zonas
     */
    public function obtenerTodas() {
        $query = "SELECT * FROM {$this->table} ORDER BY nombre_zona";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener zonas activas
     */
    public function obtenerActivas() {
        $query = "SELECT * FROM {$this->table} 
                  WHERE estado = 'activo' 
                  ORDER BY nombre_zona";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener zona por ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT * FROM {$this->table} WHERE id_zona = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Crear nueva zona
     */
    public function crear() {
        $query = "INSERT INTO {$this->table} 
                  (nombre_zona, descripcion, tarifa_domicilio, estado)
                  VALUES (:nombre, :descripcion, :tarifa, :estado)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':nombre', $this->nombre_zona);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':tarifa', $this->tarifa_domicilio);
        $stmt->bindParam(':estado', $this->estado);
        
        if ($stmt->execute()) {
            $this->id_zona = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     * Actualizar zona
     */
    public function actualizar() {
        $query = "UPDATE {$this->table} 
                  SET nombre_zona = :nombre,
                      descripcion = :descripcion,
                      tarifa_domicilio = :tarifa,
                      estado = :estado
                  WHERE id_zona = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $this->id_zona);
        $stmt->bindParam(':nombre', $this->nombre_zona);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':tarifa', $this->tarifa_domicilio);
        $stmt->bindParam(':estado', $this->estado);
        
        return $stmt->execute();
    }

    /**
     * Eliminar zona
     */
    public function eliminar($id) {
        $query = "DELETE FROM {$this->table} WHERE id_zona = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Verificar si zona existe
     */
    public function existePorNombre($nombre, $excluirId = null) {
        $query = "SELECT COUNT(*) as cnt FROM {$this->table} 
                  WHERE nombre_zona = :nombre";
        
        if ($excluirId) {
            $query .= " AND id_zona != :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        
        if ($excluirId) {
            $stmt->bindParam(':id', $excluirId, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result['cnt'] > 0;
    }

    /**
     * Obtener zona con sus barrios
     */
    public function obtenerConBarrios($id) {
        $zonaQuery = "SELECT * FROM {$this->table} WHERE id_zona = :id LIMIT 1";
        $stmt = $this->conn->prepare($zonaQuery);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $zona = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($zona) {
            // Obtener barrios
            $barriosQuery = "SELECT * FROM barrios WHERE id_zona = :id ORDER BY nombre_barrio";
            $stmt = $this->conn->prepare($barriosQuery);
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            
            $zona['barrios'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        
        return $zona;
    }

    /**
     * Obtener tarifa de una zona
     */
    public function obtenerTarifa($id) {
        $query = "SELECT tarifa_domicilio FROM {$this->table} WHERE id_zona = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ? $result['tarifa_domicilio'] : null;
    }
}