<?php
namespace App\Models;

/**
 * Modelo Barrio
 * Maneja barrios dentro de zonas
 */
class Barrio {
    private $conn;
    private $table = "barrios";

    public $id_barrio;
    public $id_zona;
    public $nombre_barrio;
    public $descripcion;
    public $estado;
    public $fecha_creacion;
    public $fecha_actualizacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtener todos los barrios
     */
    public function obtenerTodos() {
        $query = "SELECT b.*, z.nombre_zona, z.tarifa_domicilio
                  FROM {$this->table} b
                  JOIN zonas z ON b.id_zona = z.id_zona
                  ORDER BY z.nombre_zona, b.nombre_barrio";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener barrios por zona
     */
    public function obtenerPorZona($id_zona) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE id_zona = :id_zona 
                  ORDER BY nombre_barrio";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_zona', $id_zona, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener barrios activos por zona
     */
    public function obtenerActivosPorZona($id_zona) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE id_zona = :id_zona AND estado = 'activo'
                  ORDER BY nombre_barrio";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_zona', $id_zona, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener barrio por ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT b.*, z.nombre_zona, z.tarifa_domicilio
                  FROM {$this->table} b
                  JOIN zonas z ON b.id_zona = z.id_zona
                  WHERE b.id_barrio = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Crear nuevo barrio
     */
    public function crear() {
        $query = "INSERT INTO {$this->table} 
                  (id_zona, nombre_barrio, descripcion, estado)
                  VALUES (:id_zona, :nombre, :descripcion, :estado)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id_zona', $this->id_zona);
        $stmt->bindParam(':nombre', $this->nombre_barrio);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':estado', $this->estado);
        
        if ($stmt->execute()) {
            $this->id_barrio = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     * Actualizar barrio
     */
    public function actualizar() {
        $query = "UPDATE {$this->table} 
                  SET id_zona = :id_zona,
                      nombre_barrio = :nombre,
                      descripcion = :descripcion,
                      estado = :estado
                  WHERE id_barrio = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $this->id_barrio);
        $stmt->bindParam(':id_zona', $this->id_zona);
        $stmt->bindParam(':nombre', $this->nombre_barrio);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':estado', $this->estado);
        
        return $stmt->execute();
    }

    /**
     * Eliminar barrio
     */
    public function eliminar($id) {
        $query = "DELETE FROM {$this->table} WHERE id_barrio = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Verificar si barrio existe
     */
    public function existePorNombre($nombre, $id_zona, $excluirId = null) {
        $query = "SELECT COUNT(*) as cnt FROM {$this->table} 
                  WHERE nombre_barrio = :nombre AND id_zona = :id_zona";
        
        if ($excluirId) {
            $query .= " AND id_barrio != :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':id_zona', $id_zona, \PDO::PARAM_INT);
        
        if ($excluirId) {
            $stmt->bindParam(':id', $excluirId, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result['cnt'] > 0;
    }

    /**
     * Obtener tarifa del barrio (por su zona)
     */
    public function obtenerTarifa($id) {
        $query = "SELECT z.tarifa_domicilio FROM {$this->table} b
                  JOIN zonas z ON b.id_zona = z.id_zona
                  WHERE b.id_barrio = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ? $result['tarifa_domicilio'] : null;
    }

    /**
     * Contar barrios por zona
     */
    public function contarPorZona($id_zona) {
        $query = "SELECT COUNT(*) as cnt FROM {$this->table} 
                  WHERE id_zona = :id_zona";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_zona', $id_zona, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result['cnt'];
    }
}