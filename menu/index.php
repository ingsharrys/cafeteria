<?php
require_once __DIR__ . '/app/config/database.php';

// Cargar automáticamente las clases con Composer (si usas autoload).
// Si no, inclúyelas manualmente.
require_once __DIR__ . '/vendor/autoload.php';

// Control de acceso al menú (enlace firmado enviado por WhatsApp)
require_once __DIR__ . '/app/helpers/menu_access.php';

use App\Controllers\PedidoController;
use App\Controllers\EstadoPedidoController;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Determina si el visitante puede acceder al menú / hacer pedidos.
 * - Si llega un token válido en la URL (?t=...), abre la sesión de acceso.
 * - Si ya tiene sesión de acceso vigente, la respeta.
 */
function menu_acceso_permitido(): bool
{
    $token = $_GET['t'] ?? '';
    if ($token !== '') {
        $payload = menu_access_validate($token);
        if ($payload) {
            $_SESSION['menu_acceso'] = [
                'exp'   => (int) $payload['exp'],
                'admin' => !empty($payload['a']),
                'n'     => $payload['n'] ?? '',
            ];
            return true;
        }
    }

    if (!empty($_SESSION['menu_acceso']) && time() <= (int) ($_SESSION['menu_acceso']['exp'] ?? 0)) {
        return true;
    }

    return false;
}

// Podrías usar un router sencillo basado en la variable 'route' de $_GET
$route = $_GET['route'] ?? 'home';

switch ($route) {
    case 'pedidos':
        if (!menu_acceso_permitido()) {
            http_response_code(403);
            require __DIR__ . '/app/Views/acceso_restringido.php';
            break;
        }
        $controller = new PedidoController();
        $controller->index();
        break;

    case 'pedido-store':
        if (!menu_acceso_permitido()) {
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Acceso no autorizado. Solicita el enlace del menú por WhatsApp.'
            ]);
            break;
        }
        $controller = new PedidoController();
        $controller->store(); // <-- Método donde insertas el pedido y devuelves JSON
        break;


    // 🆕 Datos del cliente por teléfono (autocompletar el formulario de pedido)
    case 'cliente-info':
        header('Content-Type: application/json');
        if (!menu_acceso_permitido()) {
            echo json_encode(['found' => false, 'error' => 'no_autorizado']);
            break;
        }
        $numero = preg_replace('/\D+/', '', (string) ($_GET['numero'] ?? ''));
        if ($numero === '') {
            echo json_encode(['found' => false]);
            break;
        }
        try {
            $database = new \App\Config\Database();
            $db = $database->getConnection();
            $cli = (new \App\Models\Cliente($db))->getClienteByCelular($numero);
        } catch (\Throwable $e) {
            $cli = null;
        }
        if ($cli) {
            echo json_encode([
                'found'     => true,
                'nombre'    => $cli['cliente']   ?? '',
                'direccion' => $cli['direccion'] ?? '',
                'barrio'    => $cli['barrio']    ?? '',
                'aprobado'  => array_key_exists('aprobado', $cli) ? (int) $cli['aprobado'] : 1,
            ]);
        } else {
            echo json_encode(['found' => false]);
        }
        break;

    // 🆕 NUEVA RUTA PARA VER ESTADO DEL PEDIDO
    case 'estado-pedido':
        $controller = new EstadoPedidoController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->uploadPaymentImage();
        } else {
            $controller->index();
        }
        break;


    default:
        echo "<h1>Bienvenido a mi aplicación</h1>";
        break;
}
