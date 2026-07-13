<?php
namespace App\Models;

/**
 * Modelo Mesero
 * Maneja colaboradores del restaurante
 */
class Mesero {
    private $conn;
    private $table = "meseros";

    public $id_mese;
    public $nombre_mese;
    public $phon_mese;
    public $cedula_mese;
    public $cargo;
    public $cod_mese;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtener todos los meseros
     */
    public function obtenerTodos() {
        $query = "SELECT * FROM {$this->table} ORDER BY nombre_mese";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener mesero por ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT * FROM {$this->table} WHERE id_mese = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener mesero por código
     */
    public function obtenerPorCodigo($codigo) {
        $query = "SELECT * FROM {$this->table} WHERE cod_mese = :codigo LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':codigo', $codigo, \PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Validar código de mesero
     */
    public function validarCodigo($codigo) {
        $mesero = $this->obtenerPorCodigo($codigo);
        return $mesero !== false;
    }

    /**
     * Obtener meseros por cargo
     */
    public function obtenerPorCargo($cargo) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE cargo = :cargo 
                  ORDER BY nombre_mese";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cargo', $cargo);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Crear nuevo mesero
     */
    public function crear() {
        $query = "INSERT INTO {$this->table} 
                  (nombre_mese, phon_mese, cedula_mese, cargo, cod_mese)
                  VALUES 
                  (:nombre, :telefono, :cedula, :cargo, :codigo)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar
        $this->nombre_mese = htmlspecialchars(strip_tags($this->nombre_mese));
        $this->phon_mese = htmlspecialchars(strip_tags($this->phon_mese));
        $this->cedula_mese = htmlspecialchars(strip_tags($this->cedula_mese));
        $this->cargo = htmlspecialchars(strip_tags($this->cargo));
        
        $stmt->bindParam(':nombre', $this->nombre_mese);
        $stmt->bindParam(':telefono', $this->phon_mese);
        $stmt->bindParam(':cedula', $this->cedula_mese);
        $stmt->bindParam(':cargo', $this->cargo);
        $stmt->bindParam(':codigo', $this->cod_mese);
        
        try {
            if ($stmt->execute()) {
                $this->id_mese = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch (\PDOException $e) {
            error_log("Error creando mesero: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar mesero
     */
    public function actualizar() {
        $query = "UPDATE {$this->table} 
                  SET nombre_mese = :nombre,
                      phon_mese = :telefono,
                      cedula_mese = :cedula,
                      cargo = :cargo,
                      cod_mese = :codigo
                  WHERE id_mese = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar
        $this->nombre_mese = htmlspecialchars(strip_tags($this->nombre_mese));
        $this->phon_mese = htmlspecialchars(strip_tags($this->phon_mese));
        $this->cedula_mese = htmlspecialchars(strip_tags($this->cedula_mese));
        $this->cargo = htmlspecialchars(strip_tags($this->cargo));
        
        $stmt->bindParam(':nombre', $this->nombre_mese);
        $stmt->bindParam(':telefono', $this->phon_mese);
        $stmt->bindParam(':cedula', $this->cedula_mese);
        $stmt->bindParam(':cargo', $this->cargo);
        $stmt->bindParam(':codigo', $this->cod_mese);
        $stmt->bindParam(':id', $this->id_mese, \PDO::PARAM_INT);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error actualizando mesero: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar mesero
     */
    public function eliminar($id) {
        $query = "DELETE FROM {$this->table} WHERE id_mese = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error eliminando mesero: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si código ya existe
     */
    public function codigoExiste($codigo, $excluirId = null) {
        $query = "SELECT COUNT(*) FROM {$this->table} 
                  WHERE cod_mese = :codigo";
        
        if ($excluirId) {
            $query .= " AND id_mese != :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':codigo', $codigo);
        
        if ($excluirId) {
            $stmt->bindParam(':id', $excluirId, \PDO::PARAM_INT);
        }
        
        try {
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Error verificando código: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si cédula ya existe
     */
    public function cedulaExiste($cedula, $excluirId = null) {
        $query = "SELECT COUNT(*) FROM {$this->table} 
                  WHERE cedula_mese = :cedula";
        
        if ($excluirId) {
            $query .= " AND id_mese != :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cedula', $cedula);
        
        if ($excluirId) {
            $stmt->bindParam(':id', $excluirId, \PDO::PARAM_INT);
        }
        
        try {
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Error verificando cédula: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de meseros
     */
    public function getEstadisticas() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN cargo = 'mesero' THEN 1 ELSE 0 END) as meseros,
                    SUM(CASE WHEN cargo = 'cajero' THEN 1 ELSE 0 END) as cajeros,
                    SUM(CASE WHEN cargo = 'admin' THEN 1 ELSE 0 END) as admins,
                    SUM(CASE WHEN cargo = 'cocina' THEN 1 ELSE 0 END) as cocina
                  FROM {$this->table}";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}