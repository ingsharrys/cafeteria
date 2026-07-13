<?php
namespace App\Models;

/**
 * Modelo Mesa
 * Maneja toda la lógica de mesas y su asignación
 */
class Mesa {
    private $conn;
    private $table = "mesas";

    public $idm;
    public $numero_mesa;
    public $estado;
    public $id_pedido;
    public $fecha;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtener todas las mesas
     */
    public function obtenerTodas() {
        $query = "SELECT 
                    m.idm,
                    m.numero_mesa,
                    COALESCE(m.estado, '') AS estado,
                    m.id_pedido,
                    m.fecha,
                    CASE
                        WHEN EXISTS (SELECT 1 FROM caja WHERE id_pedidoc = m.id_pedido)
                        THEN 1 ELSE 0
                    END AS pagado
                  FROM {$this->table} m
                  ORDER BY m.numero_mesa";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $mesas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Convertir tipos
        foreach ($mesas as &$mesa) {
            $mesa['pagado'] = (int)$mesa['pagado'];
            $mesa['numero_mesa'] = (int)$mesa['numero_mesa'];
            $mesa['id_pedido'] = $mesa['id_pedido'] ? (int)$mesa['id_pedido'] : null;
        }
        
        return $mesas;
    }

    /**
     * Obtener mesas del día actual
     */
    public function obtenerDelDia() {
        $fechaActual = date('Y-m-d');
        
        $query = "SELECT 
                    m.idm,
                    m.numero_mesa,
                    COALESCE(m.estado, '') AS estado,
                    m.id_pedido,
                    m.fecha,
                    CASE
                        WHEN EXISTS (SELECT 1 FROM caja WHERE id_pedidoc = m.id_pedido)
                        THEN 1 ELSE 0
                    END AS pagado
                  FROM {$this->table} m
                  WHERE (m.id_pedido IS NULL)
                     OR (DATE(m.fecha) = :fecha_actual)
                  ORDER BY m.numero_mesa";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha_actual', $fechaActual, \PDO::PARAM_STR);
        $stmt->execute();
        
        $mesas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Convertir tipos
        foreach ($mesas as &$mesa) {
            $mesa['pagado'] = (int)$mesa['pagado'];
            $mesa['numero_mesa'] = (int)$mesa['numero_mesa'];
            $mesa['id_pedido'] = $mesa['id_pedido'] ? (int)$mesa['id_pedido'] : null;
        }
        
        return $mesas;
    }

    /**
     * Obtener mesas libres
     */
    public function obtenerLibres() {
        $query = "SELECT numero_mesa 
                  FROM {$this->table}
                  WHERE id_pedido IS NULL OR id_pedido = ''
                  ORDER BY numero_mesa";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener mesa por número
     */
    public function obtenerPorNumero($numeroMesa) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE numero_mesa = :numero_mesa 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':numero_mesa', $numeroMesa, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Asignar pedido a mesa
     */
    public function asignarPedido($numeroMesa, $numeroPedido, $estado = 'preparacion') {
        $query = "UPDATE {$this->table} 
                  SET id_pedido = :numero_pedido,
                      estado = :estado,
                      fecha = NOW()
                  WHERE numero_mesa = :numero_mesa";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':numero_pedido', $numeroPedido);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':numero_mesa', $numeroMesa, \PDO::PARAM_INT);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error asignando pedido a mesa: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Liberar mesa
     */
    public function liberar($numeroMesa) {
        $query = "UPDATE {$this->table} 
                  SET id_pedido = NULL,
                      estado = NULL,
                      fecha = NULL
                  WHERE numero_mesa = :numero_mesa";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':numero_mesa', $numeroMesa, \PDO::PARAM_INT);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error liberando mesa: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar estado de mesa
     */
    public function cambiarEstado($numeroMesa, $nuevoEstado) {
        // Validar estado
        $estadosValidos = ['preparacion', 'espera', 'entregado'];
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return false;
        }
        
        $query = "UPDATE {$this->table} 
                  SET estado = :estado 
                  WHERE numero_mesa = :numero_mesa";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado', $nuevoEstado);
        $stmt->bindParam(':numero_mesa', $numeroMesa, \PDO::PARAM_INT);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error cambiando estado de mesa: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar mesa de un pedido
     */
    public function cambiarMesa($numeroPedido, $mesaActual, $mesaNueva) {
        try {
            $this->conn->beginTransaction();
            
            // Liberar mesa actual
            $this->liberar($mesaActual);
            
            // Asignar a nueva mesa
            $this->asignarPedido($mesaNueva, $numeroPedido);
            
            // Actualizar pedido
            $queryPedido = "UPDATE pedidos 
                           SET mesa = (SELECT idm FROM mesas WHERE numero_mesa = :mesa_nueva)
                           WHERE numero_pedido = :numero_pedido";
            $stmt = $this->conn->prepare($queryPedido);
            $stmt->bindParam(':mesa_nueva', $mesaNueva, \PDO::PARAM_INT);
            $stmt->bindParam(':numero_pedido', $numeroPedido, \PDO::PARAM_INT);
            $stmt->execute();
            
            $this->conn->commit();
            return true;
            
        } catch (\PDOException $e) {
            $this->conn->rollBack();
            error_log("Error cambiando mesa: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si mesa está ocupada
     */
    public function estaOcupada($numeroMesa) {
        $query = "SELECT id_pedido FROM {$this->table} 
                  WHERE numero_mesa = :numero_mesa 
                  AND id_pedido IS NOT NULL";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':numero_mesa', $numeroMesa, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Obtener estadísticas de mesas
     */
    public function getEstadisticas() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN id_pedido IS NULL THEN 1 ELSE 0 END) as libres,
                    SUM(CASE WHEN id_pedido IS NOT NULL THEN 1 ELSE 0 END) as ocupadas,
                    SUM(CASE WHEN estado = 'preparacion' THEN 1 ELSE 0 END) as nuevos,
                    SUM(CASE WHEN estado = 'espera' THEN 1 ELSE 0 END) as espera,
                    SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as entregados
                  FROM {$this->table}";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}