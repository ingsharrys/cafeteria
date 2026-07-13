<?php
namespace App\Models;

use PDO;

class Cliente
{
    private $conn;
    private $table_name = "clientes";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getClienteByCelular($celular)
    {
        $query = "SELECT * FROM {$this->table_name} WHERE celular = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$celular]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCliente($idCliente, array $data)
    {
        // Actualiza si existe
        $sql = "
          UPDATE {$this->table_name}
          SET cliente   = :nombre,
              email     = :email,
              direccion = :direccion,
              barrio    = :barrio,
              cedula    = :cedula
          WHERE id      = :idCliente
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':nombre'    => $data['name'],
            ':email'     => 'pruebaemail',
            ':direccion' => $data['address'],
            ':barrio'    => $data['barrio'],
            ':cedula'    => $data['cedula'],
            ':idCliente' => $idCliente
        ]);
    }

    public function createCliente(array $data)
    {
        $sql = "
            INSERT INTO {$this->table_name} 
            (cliente, celular, email, direccion, cedula, barrio)
            VALUES
            (:nombre, :celular, :email, :direccion, :cedula, :barrio)
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':nombre'    => $data['name'],
            ':celular'   => $data['phone'],
            ':email'     => $data['email'],
            ':direccion' => $data['address'],
            ':cedula'    => $data['cedula'],
            ':barrio'    => $data['barrio']
        ]);
        return $this->conn->lastInsertId();
    }
    
     /**
     * 🆕 INSERTAR COSTO DE DOMICILIO
     * Agregamos esta función al final
     */
    public function insertCostoDomicilioCliente($nombreBarrio, $idPedido)
    {
        if (empty($nombreBarrio) || empty($idPedido)) {
            return false;
        }

        try {
            // Buscar tarifa: barrios JOIN zonas
            $sql = "
                SELECT z.tarifa_domicilio
                FROM barrios b
                JOIN zonas z ON b.id_zona = z.id_zona
                WHERE b.nombre_barrio = :barrio
                  AND b.estado = 'activo'
                LIMIT 1
            ";
           
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':barrio' => $nombreBarrio]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si encontró tarifa, insertar en domicilios
            if ($result && !empty($result['tarifa_domicilio'])) {
                $this->conn->prepare("
                    INSERT INTO domicilios (id_pedido, precio)
                    VALUES (:id_pedido, :precio)
                ")->execute([
                    ':id_pedido' => $idPedido,
                    ':precio'    => (int)$result['tarifa_domicilio']
                ]);
                
                return true;
            }
            
            return false;
            
        } catch (\PDOException $e) {
            error_log("Error en insertCostoDomicilioCliente: " . $e->getMessage());
            return false;
        }
    }
}
