<?php
namespace App\Models;

class Caja {
    private $conn;
    private $table = "caja";

    public $id_caja;
    public $id_pedidoc;
    public $costo;
    public $efectivo;
    public $m_pago;
    public $fecha_caja;
    public $id_cajero;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function registrarPago() {
        $query = "INSERT INTO {$this->table} 
                  (id_pedidoc, costo, efectivo, m_pago, fecha_caja, id_cajero)
                  VALUES 
                  (:id_pedido, :costo, :efectivo, :m_pago, NOW(), :id_cajero)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_pedido', $this->id_pedidoc);
        $stmt->bindParam(':costo', $this->costo);
        $stmt->bindParam(':efectivo', $this->efectivo);
        $stmt->bindParam(':m_pago', $this->m_pago);
        $stmt->bindParam(':id_cajero', $this->id_cajero);
        
        try {
            if ($stmt->execute()) {
                $this->id_caja = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch (\PDOException $e) {
            error_log("Error registrando pago: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerDelDia($fecha = null, $cajero = null) {
        if ($fecha === null) {
            $fecha = date('Y-m-d');
        }
        
        $query = "SELECT * FROM {$this->table} 
                  WHERE DATE(fecha_caja) = :fecha";
        
        if ($cajero) {
            $query .= " AND id_cajero = :cajero";
        }
        
        $query .= " ORDER BY fecha_caja DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha', $fecha);
        
        if ($cajero) {
            $stmt->bindParam(':cajero', $cajero, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTotalesPorMetodo($fecha = null, $cajero = null) {
        if ($fecha === null) {
            $fecha = date('Y-m-d');
        }
        
        $query = "SELECT 
                    SUM(CASE WHEN m_pago = 'efectivo' THEN costo ELSE 0 END) as total_efectivo,
                    SUM(CASE WHEN m_pago = 'tarjeta' THEN costo ELSE 0 END) as total_tarjeta,
                    SUM(CASE WHEN m_pago = 'transferencia' THEN costo ELSE 0 END) as total_transferencia,
                    SUM(CASE WHEN m_pago = 'efectivo_transferencia' THEN efectivo ELSE 0 END) as efectivo_mixto,
                    SUM(CASE WHEN m_pago = 'efectivo_transferencia' THEN (costo - efectivo) ELSE 0 END) as transferencia_mixto,
                    SUM(CASE WHEN m_pago = 'tarjeta_efectivo' THEN efectivo ELSE 0 END) as efectivo_tarjeta_mixto,
                    SUM(CASE WHEN m_pago = 'tarjeta_efectivo' THEN (costo - efectivo) ELSE 0 END) as tarjeta_mixto
                  FROM {$this->table}
                  WHERE DATE(fecha_caja) = :fecha";
        
        if ($cajero) {
            $query .= " AND id_cajero = :cajero";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha', $fecha);
        
        if ($cajero) {
            $stmt->bindParam(':cajero', $cajero, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}