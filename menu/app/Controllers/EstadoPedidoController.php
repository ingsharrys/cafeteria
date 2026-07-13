<?php
namespace App\Controllers;
use App\Config\Database;
use App\Models\EstadoPedido;

class EstadoPedidoController
{
    private $estadoPedidoModel;

    public function __construct()
    {
        $database = new Database();
        $db = $database->getConnection();
        $this->estadoPedidoModel = new EstadoPedido($db);
    }

    /**
     * Mostrar estado del pedido del cliente
     * GET: ?route=estado-pedido&numero=3001234567
     */
    public function index()
    {
        // Obtener teléfono de la URL
        $celular = $_GET['numero'] ?? null;
        if (!$celular) {
            echo "<h3>Error: Número de teléfono no proporcionado</h3>";
            return;
        }

        // Inicializar variables
        $nombreCliente      = 'Cliente';
        $pedidoPendiente    = null;
        $otrosPedidosActivos = [];
        $otrosPedidosDia    = [];
        $pedidoCancelado    = null;
        $historialPedidos   = [];

        // Obtener datos del cliente
        $cliente = $this->estadoPedidoModel->obtenerClientePorCelular($celular);

        if (!$cliente) {
            $nombreCliente = 'Cliente';
        } else {
            $nombreCliente = $cliente['cliente'];
            $idCliente     = $cliente['id'];

            // ── Pedidos de HOY ──────────────────────────────────────────
            $pedidosPendientes = $this->estadoPedidoModel->obtenerPedidosPendientes($idCliente);

            $pedidosActivos    = [];
            $pedidosCompletados = [];

            foreach ($pedidosPendientes as $p) {
                if ($p['estado'] !== 'entregado' || $p['pagado'] == 0) {
                    $pedidosActivos[] = $p;
                } else {
                    $pedidosCompletados[] = $p;
                }
            }

            // Adjuntar detalle de productos a cada pedido activo
            foreach ($pedidosActivos as &$p) {
                $p['productos'] = $this->estadoPedidoModel->obtenerDetallesPedido($p['id_pedido']);
            }
            unset($p);

            // Adjuntar detalle de productos a cada pedido completado del día
            foreach ($pedidosCompletados as &$p) {
                $p['productos'] = $this->estadoPedidoModel->obtenerDetallesPedido($p['id_pedido']);
            }
            unset($p);

            // El primer activo es el "Estado Actual"
            $pedidoPendiente     = count($pedidosActivos) > 0 ? $pedidosActivos[0] : null;
            $otrosPedidosActivos = array_slice($pedidosActivos, 1);
            $otrosPedidosDia     = $pedidosCompletados;

            // ── Historial (otros días) ───────────────────────────────────
            $historialPedidos = $this->estadoPedidoModel->obtenerHistorialPedidos($idCliente);

            // Adjuntar detalle de productos al historial
            foreach ($historialPedidos as &$p) {
                $p['productos'] = $this->estadoPedidoModel->obtenerDetallesPedido($p['id_pedido']);
            }
            unset($p);

            // ── Detectar pedido cancelado ────────────────────────────────
            $pedidoCancelado = null;
            $allPedidos = array_merge($pedidosActivos, $pedidosCompletados, $historialPedidos);

            foreach ($allPedidos as $pedido) {
                $esActivo = false;
                foreach ($pedidosActivos as $pActivo) {
                    if ($pActivo['id_pedido'] === $pedido['id_pedido']) {
                        $esActivo = true;
                        break;
                    }
                }
                if (!$esActivo && $pedido['estado'] !== 'entregado' && ($pedido['pagado'] ?? 0) == 0) {
                    $pedidoCancelado = $pedido;
                    break;
                }
            }
        }

        // ── Helpers ─────────────────────────────────────────────────────
        $obtenerEstado = function($estado) {
            $estados = [
                'preparacion'     => ['🔴 Preparación', '#dc3545'],
                'espera' => ['🟠 Espera',       '#fd7e14'],
                'entregado' => ['✅ Entregado',    '#28a745'],
                'cocina'    => ['🟠 Espera',       '#fd7e14'],
            ];
            return $estados[$estado] ?? ['⚪ Desconocido', '#6c757d'];
        };

        $obtenerTipo = function($tipo) {
            $tipos = [
                50 => '🏠 Domicilio',
                51 => '🏪 Para Recoger',
                53 => '📱 Llamada',
            ];
            return $tipos[$tipo] ?? '❓ Desconocido';
        };

        // ── Renderizar vista ─────────────────────────────────────────────
        $data = [
            'celular'             => $celular,
            'nombreCliente'       => $nombreCliente,
            'pedidoPendiente'     => $pedidoPendiente,
            'otrosPedidosActivos' => $otrosPedidosActivos,
            'otrosPedidosDia'     => $otrosPedidosDia,
            'pedidoCancelado'     => $pedidoCancelado,
            'historialPedidos'    => $historialPedidos,
            'obtenerEstado'       => $obtenerEstado,
            'obtenerTipo'         => $obtenerTipo,
        ];

        $this->renderView('estado_pedido.view.php', $data);
    }

    /**
     * Manejar subida de imagen de pago asociada a un pedido
     * POST: archivo `pago_img`, campos `id_pedido`, `numero`
     */
    public function uploadPaymentImage()
    {
        if (empty($_FILES['pago_img']) || !isset($_POST['id_pedido'])) {
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/menu/index.php'));
            exit;
        }

        $idPedido = preg_replace('/[^0-9]/', '', $_POST['id_pedido']);
        if (!$idPedido) {
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/menu/index.php'));
            exit;
        }

        $file = $_FILES['pago_img'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/menu/index.php'));
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (strpos($mime, 'image/') !== 0) {
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/menu/index.php'));
            exit;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed)) {
            $ext = 'jpg';
        }

        $uploadsDir = __DIR__ . '/../../../public/img/payments/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $target = $uploadsDir . $idPedido . '.' . $ext;

        // Eliminar posibles duplicados con otras extensiones
        $existing = glob($uploadsDir . $idPedido . '.*');
        foreach ($existing as $e) {
            if (is_file($e)) @unlink($e);
        }

        if (move_uploaded_file($file['tmp_name'], $target)) {
            // éxito
        }

        // Redirigir de vuelta a la vista de estado
        $numero = $_POST['numero'] ?? null;
        if ($numero) {
            header('Location: /cafeteria-pombo/menu/index.php?route=estado-pedido&numero=' . urlencode($numero));
        } else {
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/cafeteria-pombo/menu/index.php'));
        }
        exit;
    }

    private function renderView($viewName, $data = [])
    {
        extract($data);
        $viewPath = __DIR__ . '/../Views/' . $viewName;
        if (!file_exists($viewPath)) {
            die("Error: Vista no encontrada en $viewPath");
        }
        require_once $viewPath;
    }
}