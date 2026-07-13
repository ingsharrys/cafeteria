<?php
namespace App\Models;

use PDO;
use PDOException;

class EstadoPedido
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtener cliente por celular
     */
    public function obtenerClientePorCelular($celular)
    {
        try {
            $sql = "SELECT id, cliente, celular, direccion, email, cedula, barrio 
                    FROM clientes WHERE celular = ? LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$celular]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerClientePorCelular: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener pedidos pendientes del cliente (TODOS de HOY)
     * Incluye total_productos calculado
     */
    public function obtenerPedidosPendientes($idCliente)
    {
        try {
            $hoy = date('Y-m-d');
            
            $sql = "
                SELECT 
                    t.id_pedido,
                    t.turno,
                    t.estado,
                    t.fecha,
                    t.tipo_solicitud,
                    t.pagado,
                    c.cliente,
                    c.celular,
                    c.direccion,
                    c.barrio,
                    COUNT(p.id_pedido) as cantidad_productos,
                    COALESCE(SUM(COALESCE(prp.precio, 0) * p.cantidad), 0) as total_productos,
                    COALESCE(d.id_domi, 0) as id_domiciliario,
                    COALESCE(dom.repartidor, 'Sin asignar') as nombre_repartidor,
                    COALESCE(dom.celu_reparti, '') as celular_repartidor,
                    COALESCE(d.precio, 0) as precio_domicilio
                FROM turnero t
                LEFT JOIN clientes c ON t.id_cliente = c.id
                LEFT JOIN pedidos p ON t.id_pedido = p.numero_pedido
                LEFT JOIN productos pr ON p.id_pro = pr.id_pro
                LEFT JOIN precios prp ON pr.id_pro = prp.idproduc AND prp.tipo_prod = p.tipo_producto
                LEFT JOIN domicilios d ON t.id_pedido = d.id_pedido
                LEFT JOIN domiciliarios dom ON d.id_domi = dom.id_e
                WHERE t.id_cliente = :id_cliente
                  AND DATE(t.fecha) = :hoy
                  AND (
                      t.estado IN ('preparacion', 'espera')
                      OR (t.estado = 'entregado' AND t.pagado = 0)
                  )
                GROUP BY t.id_pedido
                ORDER BY t.id_pedido DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_cliente' => $idCliente,
                ':hoy' => $hoy
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerPedidosPendientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener UN pedido pendiente del cliente (el primero de HOY)
     * Para compatibilidad con código anterior
     */
    public function obtenerPedidoPendiente($idCliente)
    {
        $pendientes = $this->obtenerPedidosPendientes($idCliente);
        return count($pendientes) > 0 ? $pendientes[0] : null;
    }

    /**
     * Obtener historial de pedidos del cliente (SOLO de otros días)
     */
    public function obtenerHistorialPedidos($idCliente)
    {
        try {
            $hoy = date('Y-m-d');
            
            $sql = "
                SELECT 
                    t.id_pedido,
                    t.turno,
                    t.estado,
                    t.fecha,
                    t.tipo_solicitud,
                    COUNT(p.id_pedido) as cantidad_productos,
                    COALESCE(SUM(COALESCE(prp.precio, 0) * p.cantidad), 0) as total_productos,
                    COALESCE(d.precio, 0) as precio_domicilio,
                    COALESCE(dom.repartidor, 'Sin asignar') as nombre_repartidor,
                    t.pagado as esta_pagado
                FROM turnero t
                LEFT JOIN pedidos p ON t.id_pedido = p.numero_pedido
                LEFT JOIN productos pr ON p.id_pro = pr.id_pro
                LEFT JOIN precios prp ON pr.id_pro = prp.idproduc AND prp.tipo_prod = p.tipo_producto
                LEFT JOIN domicilios d ON t.id_pedido = d.id_pedido
                LEFT JOIN domiciliarios dom ON d.id_domi = dom.id_e
                WHERE t.id_cliente = :id_cliente
                  AND DATE(t.fecha) != :hoy
                GROUP BY t.id_pedido
                ORDER BY t.fecha DESC, t.id_pedido DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_cliente' => $idCliente,
                ':hoy' => $hoy
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerHistorialPedidos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalles de productos de un pedido específico
     */
    public function obtenerDetallesPedido($idPedido)
    {
        try {
            $sql = "
                SELECT 
                    pr.nombre,
                    pr.prefijo,
                    p.cantidad,
                    p.detalle,
                    p.tipo_producto,
                    COALESCE(prp.precio, 0) as precio
                FROM pedidos p
                JOIN productos pr ON p.id_pro = pr.id_pro
                LEFT JOIN precios prp ON pr.id_pro = prp.idproduc AND prp.tipo_prod = p.tipo_producto
                WHERE p.numero_pedido = :id_pedido
                  AND p.cantidad > 0
                  AND (p.detalle IS NULL OR p.detalle NOT LIKE 'ANULADO:%')
                ORDER BY p.id_pedido
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_pedido' => $idPedido]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obtenerDetallesPedido: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener comentarios del pedido
     */
    public function obtenerComentarios($idPedido)
    {
        try {
            $sql = "SELECT comentario FROM comentarios WHERE id_pedido = :id LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $idPedido]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['comentario'] ?? '';
        } catch (PDOException $e) {
            error_log("Error obtenerComentarios: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Obtener estado del pedido con emoji
     */
    public function obtenerEstadoConEmoji($estado)
    {
        $estados = [
            'preparacion'       => ['🔴 Preparación', '#dc3545'],
            'espera'   => ['🟠 Espera',       '#fd7e14'],
            'entregado'   => ['🟢 Entregado',    '#28a745'],
            'cocina'      => ['🟠 Espera',       '#fd7e14'],
        ];

        return $estados[$estado] ?? ['⚪ Desconocido', '#6c757d'];
    }

    /**
     * Obtener tipo de solicitud en texto
     */
    public function obtenerTipoSolicitud($tipo)
    {
        $tipos = [
            50 => '🏠 Domicilio',
            51 => '🏪 Para Recoger',
            53 => '📱 Llamada',
        ];

        return $tipos[$tipo] ?? '❓ Desconocido';
    }
}