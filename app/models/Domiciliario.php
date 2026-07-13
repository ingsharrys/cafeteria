<?php
/**
 * Domiciliario.php - Modelo
 * Gestiona datos de la tabla 'domiciliarios'
 * 
 * Campos:
 * - id_e: int(11) - ID del domiciliario
 * - repartidor: varchar(200) - Nombre del domiciliario
 * - celu_reparti: varchar(200) - Teléfono
 * - calificacion: varchar(100) - Calificación
 * - elimina: int(11) - 0=activo, 1=eliminado
 */

class Domiciliario {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Obtener todos los domiciliarios activos
     */
    public function obtenerTodos() {
        try {
            $sql = "SELECT id_e, repartidor, celu_reparti, calificacion 
                    FROM domiciliarios 
                    WHERE elimina = 1 
                    ORDER BY repartidor ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('❌ Error obtenerTodos domiciliarios: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener un domiciliario por ID
     */
    public function obtenerPorId($id_e) {
        try {
            $sql = "SELECT id_e, repartidor, celu_reparti, calificacion 
                    FROM domiciliarios 
                    WHERE id_e = ? AND elimina = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_e]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('❌ Error obtenerPorId domiciliario: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener domiciliarios como JSON (para AJAX)
     */
    public function obtenerJSON() {
        $domiciliarios = $this->obtenerTodos();
        
        if (empty($domiciliarios)) {
            return [
                'status' => 'error',
                'message' => 'No hay domiciliarios disponibles',
                'domiciliarios' => []
            ];
        }

        return [
            'status' => 'success',
            'domiciliarios' => $domiciliarios
        ];
    }
}