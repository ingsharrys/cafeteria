<?php
namespace App\Models;

class Gasto {
    private $conn;
    private $table = "gastos";

    public $id;
    public $concepto;
    public $monto;
    public $fecha;
    public $id_usuario;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO {$this->table} (concepto, monto, fecha, id_usuario)
                  VALUES (:concepto, :monto, :fecha, :id_usuario)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':concepto', $this->concepto);
        $stmt->bindParam(':monto', $this->monto);
        $stmt->bindParam(':fecha', $this->fecha);
        $stmt->bindParam(':id_usuario', $this->id_usuario);
        
        try {
            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch (\PDOException $e) {
            error_log("Error creando gasto: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerDelDia($fecha = null) {
        if ($fecha === null) {
            $fecha = date('Y-m-d');
        }
        
        $query = "SELECT * FROM {$this->table} 
                  WHERE DATE(fecha) = :fecha 
                  ORDER BY fecha DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTotalDelDia($fecha = null) {
        if ($fecha === null) {
            $fecha = date('Y-m-d');
        }
        
        $query = "SELECT COALESCE(SUM(monto), 0) as total 
                  FROM {$this->table} 
                  WHERE DATE(fecha) = :fecha";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha', $fecha);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float)($result['total'] ?? 0);
    }
}