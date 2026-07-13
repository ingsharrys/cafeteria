<?php
/**
 * Domicilio.php - Modelo
 * Gestiona datos de la tabla 'domicilios'
 * 
 * Campos:
 * - id_d: int(11) - ID auto-increment
 * - id_pedido: int(11) - ID del pedido (FK)
 * - id_domi: int(11) - ID del domiciliario (FK)
 * - precio: varchar(20) - Precio del domicilio
 * - califi: int(11) - Calificación (nullable)
 */

class Domicilio {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Obtener domicilio por ID del pedido
     */
    public function obtenerPorIdPedido($id_pedido) {
        try {
            $sql = "SELECT d.*, dom.repartidor, dom.celu_reparti 
                    FROM domicilios d
                    LEFT JOIN domiciliarios dom ON d.id_domi = dom.id_e
                    WHERE d.id_pedido = ?
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_pedido]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('❌ Error obtenerPorIdPedido: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear nuevo domicilio (INSERT)
     * @param int $id_pedido
     * @param int|null $id_domi - Puede ser null si se crea solo con precio
     * @param string|null $precio - Puede ser null si se crea solo con domiciliario
     */
    public function crear($id_pedido, $id_domi = null, $precio = null) {
        try {
            $sql = "INSERT INTO domicilios (id_pedido, id_domi, precio) 
                    VALUES (?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$id_pedido, $id_domi, $precio]);
            
            if ($result) {
                return [
                    'status' => 'success',
                    'message' => 'Domicilio creado correctamente',
                    'id_d' => $this->db->lastInsertId()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Error al crear el domicilio'
            ];
        } catch (PDOException $e) {
            error_log('❌ Error crear domicilio: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar domiciliario (id_domi)
     */
    public function actualizarDomiciliario($id_pedido, $id_domi) {
        try {
            $sql = "UPDATE domicilios 
                    SET id_domi = ? 
                    WHERE id_pedido = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$id_domi, $id_pedido]);
            
            if ($result) {
                return [
                    'status' => 'success',
                    'message' => 'Domiciliario asignado correctamente'
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Error al asignar domiciliario'
            ];
        } catch (PDOException $e) {
            error_log('❌ Error actualizarDomiciliario: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos'
            ];
        }
    }

    /**
     * Actualizar precio del domicilio
     */
    public function actualizarPrecio($id_pedido, $precio) {
        try {
            $sql = "UPDATE domicilios 
                    SET precio = ? 
                    WHERE id_pedido = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$precio, $id_pedido]);
            
            if ($result) {
                return [
                    'status' => 'success',
                    'message' => 'Precio actualizado correctamente'
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Error al actualizar precio'
            ];
        } catch (PDOException $e) {
            error_log('❌ Error actualizarPrecio: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos'
            ];
        }
    }

    /**
     * Actualizar ambos campos (domiciliario y precio)
     */
    public function actualizar($id_pedido, $id_domi = null, $precio = null) {
        try {
            $updates = [];
            $params = [];

            if ($id_domi !== null) {
                $updates[] = "id_domi = ?";
                $params[] = $id_domi;
            }

            if ($precio !== null) {
                $updates[] = "precio = ?";
                $params[] = $precio;
            }

            if (empty($updates)) {
                return [
                    'status' => 'error',
                    'message' => 'No hay campos para actualizar'
                ];
            }

            $params[] = $id_pedido;
            $sql = "UPDATE domicilios SET " . implode(", ", $updates) . " WHERE id_pedido = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                return [
                    'status' => 'success',
                    'message' => 'Datos actualizados correctamente'
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Error al actualizar'
            ];
        } catch (PDOException $e) {
            error_log('❌ Error actualizar: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos'
            ];
        }
    }

    /**
     * Verificar si existe un domicilio para un pedido
     */
    public function existePara($id_pedido) {
        try {
            $sql = "SELECT id_d FROM domicilios WHERE id_pedido = ? LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_pedido]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            error_log('❌ Error existePara: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar domicilio
     */
    public function eliminar($id_pedido) {
        try {
            $sql = "DELETE FROM domicilios WHERE id_pedido = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$id_pedido]);
            
            if ($result) {
                return [
                    'status' => 'success',
                    'message' => 'Domicilio eliminado correctamente'
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Error al eliminar'
            ];
        } catch (PDOException $e) {
            error_log('❌ Error eliminar: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Error en la base de datos'
            ];
        }
    }
}