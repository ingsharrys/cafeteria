<?php
namespace App\Models;

/**
 * Modelo Cliente
 * Maneja información de clientes
 */
class Cliente {
    private $conn;
    private $table = "clientes";

    public $id;
    public $cliente;
    public $celular;
    public $direccion;
    public $barrio;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtener cliente por celular
     */
    public function obtenerPorCelular($celular) {
        $query = "SELECT * FROM {$this->table} WHERE celular = :celular LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':celular', $celular);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Crear nuevo cliente
     */
    public function crear() {
        $query = "INSERT INTO {$this->table} (cliente, celular, direccion, barrio)
                  VALUES (:cliente, :celular, :direccion, :barrio)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':cliente', $this->cliente);
        $stmt->bindParam(':celular', $this->celular);
        $stmt->bindParam(':direccion', $this->direccion);
        $stmt->bindParam(':barrio', $this->barrio);
        
        try {
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch (\PDOException $e) {
            error_log("Error creando cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar cliente
     */
    public function actualizar() {
        $query = "UPDATE {$this->table} 
                  SET cliente = :cliente,
                      direccion = :direccion,
                      barrio = :barrio
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':cliente', $this->cliente);
        $stmt->bindParam(':direccion', $this->direccion);
        $stmt->bindParam(':barrio', $this->barrio);
        $stmt->bindParam(':id', $this->id);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error actualizando cliente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todos los clientes
     */
    public function obtenerTodos() {
        $query = "SELECT * FROM {$this->table} ORDER BY cliente";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Buscar clientes por nombre o teléfono
     */
    public function buscar($termino) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE cliente LIKE :termino 
                     OR celular LIKE :termino
                  ORDER BY cliente
                  LIMIT 20";
        
        $stmt = $this->conn->prepare($query);
        $terminoLike = "%{$termino}%";
        $stmt->bindParam(':termino', $terminoLike);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}