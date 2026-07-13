<?php
namespace App\Models;

/**
 * Modelo Producto
 * Maneja productos del restaurante
 */
class Producto {
    private $conn;
    private $table = "productos";

    public $id_pro;
    public $nombre;
    public $descripcion;
    public $categoria;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtener todos los productos
     */
    public function obtenerTodos() {
        $query = "SELECT * FROM {$this->table} ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener producto por ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT * FROM {$this->table} WHERE id_pro = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener producto con sus precios
     */
    public function obtenerConPrecios($id) {
        $query = "SELECT p.*, pr.precio, pr.tipo_prod 
                  FROM {$this->table} p
                  LEFT JOIN precios pr ON p.id_pro = pr.idproduc
                  WHERE p.id_pro = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener productos por categoría
     */
    public function obtenerPorCategoria($categoria) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE categoria = :categoria 
                  ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':categoria', $categoria);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Crear nuevo producto
     */
    public function crear() {
        $query = "INSERT INTO {$this->table} (nombre, descripcion, categoria)
                  VALUES (:nombre, :descripcion, :categoria)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':categoria', $this->categoria);
        
        try {
            if ($stmt->execute()) {
                $this->id_pro = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch (\PDOException $e) {
            error_log("Error creando producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar producto
     */
    public function actualizar() {
        $query = "UPDATE {$this->table} 
                  SET nombre = :nombre,
                      descripcion = :descripcion,
                      categoria = :categoria
                  WHERE id_pro = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':id', $this->id_pro);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error actualizando producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar producto
     */
    public function eliminar($id) {
        $query = "DELETE FROM {$this->table} WHERE id_pro = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error eliminando producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar productos
     */
    public function buscar($termino) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE nombre LIKE :termino 
                     OR descripcion LIKE :termino
                  ORDER BY nombre
                  LIMIT 50";
        
        $stmt = $this->conn->prepare($query);
        $terminoLike = "%{$termino}%";
        $stmt->bindParam(':termino', $terminoLike);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}