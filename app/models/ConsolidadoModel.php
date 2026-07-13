<?php
/**
 * ConsolidadoModel.php
 * Modelo - Consultas a BD CORRECTAS
 */

namespace App\Models;

use PDO;

class ConsolidadoModel {
    private $db;

    public function __construct($database) {
        $this->db = $database->getConnection();
    }

    /**
     * Obtener turneros por fecha
     */
    public function getTurnerosPorFecha($fecha) {
        $query = "
            SELECT 
                t.id_t,
                t.id_pedido,
                t.turno,
                t.fecha,
                t.tipo_solicitud,
                t.estado,
                t.id_cliente,
                c.cliente,
                c.celular,
                c.barrio
            FROM turnero t
            LEFT JOIN clientes c ON t.id_cliente = c.id
            WHERE DATE(t.fecha) = :fecha
            ORDER BY t.turno ASC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener TODOS los productos de un pedido
     * Relación correcta:
     * - pedidos.id_pro → productos.id_pro (nombre)
     * - pedidos.tipo_producto → precios.tipo_prod (precio exacto)
     */
    public function getProductosPorPedido($numeroPedido) {
        if (empty($numeroPedido)) return array();

        $query = "
            SELECT 
                p.id_pedido,
                p.numero_pedido,
                p.id_pro,
                p.cantidad,
                p.tipo_producto,
                prod.nombre,
                prec.precio
            FROM pedidos p
            INNER JOIN productos prod ON p.id_pro = prod.id_pro
            INNER JOIN precios prec ON p.id_pro = prec.idproduc 
                AND p.tipo_producto = prec.tipo_prod
            WHERE p.numero_pedido = :numeroPedido
            ORDER BY p.id_pedido ASC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':numeroPedido', $numeroPedido, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener información de domicilio por id_pedido
     * Relación:
     * - domicilios.id_pedido → turnero.id_pedido
     * - domicilios.id_domi → domiciliarios.id_e (nombre repartidor)
     */
    public function getDomiciliarioPorPedido($idPedido) {
        if (empty($idPedido)) return null;

        $query = "
            SELECT 
                d.id_pedido,
                d.id_domi,
                d.precio,
                d.califi,
                dom.repartidor,
                dom.celu_reparti,
                dom.calificacion
            FROM domicilios d
            INNER JOIN domiciliarios dom ON d.id_domi = dom.id_e
            WHERE d.id_pedido = :idPedido
            LIMIT 1
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':idPedido', $idPedido, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener comentarios por ID de pedido
     */
    public function getComentariosPorPedidos($ids) {
        if (empty($ids)) return array();

        $idsString = implode(',', array_map('intval', $ids));

        $query = "
            SELECT 
                id_pedido,
                comentario
            FROM comentarios
            WHERE id_pedido IN ($idsString)
        ";

        $stmt = $this->db->query($query);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $comentariosMap = array();
        foreach ($resultados as $com) {
            if (!isset($comentariosMap[$com['id_pedido']])) {
                $comentariosMap[$com['id_pedido']] = array();
            }
            array_push($comentariosMap[$com['id_pedido']], $com['comentario']);
        }

        return $comentariosMap;
    }
}
?>