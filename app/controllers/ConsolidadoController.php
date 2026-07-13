<?php
/**
 * ConsolidadoController.php
 * Controlador - Procesa datos con domiciliarios
 */

namespace App\Controllers;

use App\Models\ConsolidadoModel;

class ConsolidadoController {
    private $model;

    private $tiposMap = array(
        50 => 'domicilios',
        51 => 'turno',
        52 => 'mesas',
        53 => 'recoger'
    );

    public function __construct($database) {
        $this->model = new ConsolidadoModel($database);
    }

    /**
     * Obtener todos los turnos de una fecha específica agrupados por tipo
     */
    public function obtenerTurnosPorTipo($fecha) {
        // Validar que la fecha sea válida
        if (!$this->esValido($fecha)) {
            $fecha = date('Y-m-d');
        }

        // Obtener TODOS los turneros de la fecha
        $turneros = $this->model->getTurnerosPorFecha($fecha);

        if (empty($turneros)) {
            return array(
                'success' => true,
                'total' => 0,
                'fecha' => $fecha,
                'turnosPorTipo' => array(
                    'todos' => array(),
                    'domicilios' => array(),
                    'turno' => array(),
                    'mesas' => array(),
                    'recoger' => array()
                )
            );
        }

        // Obtener comentarios para todos los id_pedido
        $idsPedidos = array_unique(array_map(function($t) { 
            return intval($t['id_pedido']); 
        }, $turneros));
        $comentariosMap = $this->model->getComentariosPorPedidos($idsPedidos);

        // Agrupar por tipo de solicitud
        $turnosPorTipo = array(
            'todos' => array(),
            'domicilios' => array(),
            'turno' => array(),
            'mesas' => array(),
            'recoger' => array()
        );

        // Procesar cada turno
        foreach ($turneros as $turno) {
            $tipo = isset($this->tiposMap[$turno['tipo_solicitud']]) 
                ? $this->tiposMap[$turno['tipo_solicitud']] 
                : 'recoger';

            $idPedido = intval($turno['id_pedido']);
            $numeroPedido = intval($turno['id_pedido']);

            // Obtener TODOS los productos de este pedido
            $productosDelPedido = $this->model->getProductosPorPedido($numeroPedido);

            // Calcular total de productos
            $totalProductos = 0;
            $productosFormateados = array();

            foreach ($productosDelPedido as $p) {
                $cantidad = intval($p['cantidad']) ?: 0;
                $precio = intval($p['precio']) ?: 0;
                $subtotal = $cantidad * $precio;
                $totalProductos += $subtotal;

                array_push($productosFormateados, array(
                    'nombre' => $p['nombre'],
                    'tipo' => $p['tipo_producto'],
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal
                ));
            }

            // Obtener información de domiciliario (si es tipo domicilio = 50)
            $domiciliario = null;
            $costoDomicilio = 0;
            
            if ($turno['tipo_solicitud'] == 50) {
                $domiciliario = $this->model->getDomiciliarioPorPedido($idPedido);
                if ($domiciliario) {
                    $costoDomicilio = intval($domiciliario['precio']) ?: 0;
                }
            }

            // Calcular total general (productos + domicilio)
            $totalGeneral = $totalProductos + $costoDomicilio;

            // Obtener comentarios
            $comentarios = isset($comentariosMap[$idPedido]) ? $comentariosMap[$idPedido] : array();

            // Crear registro del turno
            $turnoData = array(
                'id_t' => $turno['id_t'],
                'id_pedidoc' => $idPedido,
                'turno' => $turno['turno'],
                'cliente' => $turno['cliente'],
                'celular' => $turno['celular'],
                'barrio' => $turno['barrio'],
                'estado' => $turno['estado'],
                'fecha' => $turno['fecha'],
                'tipo_solicitud' => $turno['tipo_solicitud'],
                'productos' => $productosFormateados,
                'totalProductos' => $totalProductos,
                'domiciliario' => $domiciliario,
                'costoDomicilio' => $costoDomicilio,
                'totalGeneral' => $totalGeneral,
                'comentarios' => $comentarios
            );

            // Agregar a "todos"
            array_push($turnosPorTipo['todos'], $turnoData);

            // Agregar al tipo específico
            array_push($turnosPorTipo[$tipo], $turnoData);
        }

        return array(
            'success' => true,
            'total' => count($turneros),
            'fecha' => $fecha,
            'turnosPorTipo' => $turnosPorTipo
        );
    }

    /**
     * Validar que la fecha sea válida (YYYY-MM-DD)
     */
    private function esValido($fecha) {
        if (empty($fecha)) return false;
        
        $d = explode('-', $fecha);
        if (count($d) !== 3) return false;
        
        return checkdate($d[1], $d[2], $d[0]);
    }
}
?>