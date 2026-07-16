<?php
require_once __DIR__ . '/app/config/database.php';

// Cargar automáticamente las clases con Composer (si usas autoload).
// Si no, inclúyelas manualmente.
require_once __DIR__ . '/vendor/autoload.php';

// Control de acceso al menú (enlace firmado enviado por WhatsApp / aprobación)
require_once __DIR__ . '/app/helpers/menu_access.php';

use App\Controllers\PedidoController;
use App\Controllers\EstadoPedidoController;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Conexión BD del menú.
 */
function menu_db(): ?PDO
{
    static $db = null;
    if ($db === null) {
        try {
            $database = new \App\Config\Database();
            $db = $database->getConnection();
        } catch (\Throwable $e) {
            $db = null;
        }
    }
    return $db;
}

/**
 * ¿El cliente con ese número está aprobado?
 */
function menu_numero_aprobado(string $numero): bool
{
    $numero = preg_replace('/\D+/', '', $numero);
    if ($numero === '') {
        return false;
    }
    $db = menu_db();
    if (!$db) {
        return false;
    }
    try {
        $st = $db->prepare("SELECT COALESCE(aprobado, 0) AS aprobado FROM clientes WHERE celular = ? LIMIT 1");
        $st->execute([$numero]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row && (int) $row['aprobado'] === 1;
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * Determina si el visitante puede acceder al menú / hacer pedidos:
 * 1) Token válido en la URL (?t=...) enviado por WhatsApp o por el panel.
 * 2) Sesión de acceso vigente.
 * 3) El número (?numero=) corresponde a un cliente ya APROBADO.
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

    $numero = preg_replace('/\D+/', '', (string) ($_GET['numero'] ?? ''));
    if ($numero !== '' && menu_numero_aprobado($numero)) {
        $_SESSION['menu_acceso'] = ['exp' => time() + 3600, 'admin' => false, 'n' => $numero];
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
                'message' => 'Acceso no autorizado. Solicita el acceso al menú.'
            ]);
            break;
        }
        $controller = new PedidoController();
        $controller->store(); // <-- Método donde insertas el pedido y devuelves JSON
        break;

    // 🆕 Registro rápido: al llenar el formulario el cliente queda habilitado
    //     al instante (aprobado) y entra directo a hacer su pedido.
    case 'solicitar-acceso':
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $numero = preg_replace('/\D+/', '', (string) ($_POST['numero'] ?? ''));
        $ok = false;
        $mensaje = '';

        if ($nombre === '' || strlen($numero) < 7) {
            $mensaje = 'Escribe tu nombre y un número de teléfono válido.';
        } else {
            $db = menu_db();
            if ($db) {
                try {
                    // Asegurar que exista la columna 'aprobado'
                    $col = $db->query("SHOW COLUMNS FROM clientes LIKE 'aprobado'")->fetch();
                    if (!$col) {
                        $db->exec("ALTER TABLE clientes ADD COLUMN aprobado TINYINT(1) NOT NULL DEFAULT 0");
                        $db->exec("UPDATE clientes SET aprobado = 1");
                    }

                    $clienteModel = new \App\Models\Cliente($db);
                    $existe = $clienteModel->getClienteByCelular($numero);
                    if ($existe) {
                        $db->prepare("UPDATE clientes SET cliente = :n, aprobado = 1 WHERE id = :id")
                           ->execute([':n' => $nombre, ':id' => $existe['id']]);
                    } else {
                        $clienteModel->createCliente([
                            'name'    => $nombre,
                            'phone'   => $numero,
                            'email'   => 'sincorreo',
                            'address' => '',
                            'cedula'  => '0',
                            'barrio'  => '',
                        ]);
                        $db->prepare("UPDATE clientes SET aprobado = 1 WHERE celular = :c")
                           ->execute([':c' => $numero]);
                    }
                    $ok = true;
                } catch (\Throwable $e) {
                    $mensaje = 'No se pudo registrar. Intenta de nuevo.';
                }
            } else {
                $mensaje = 'Error de conexión. Intenta más tarde.';
            }
        }

        if ($ok) {
            // Habilitar la sesión de acceso y llevarlo directo al menú
            $_SESSION['menu_acceso'] = ['exp' => time() + 3600, 'admin' => false, 'n' => $numero];
            header('Location: index.php?route=pedidos&pedido=call&numero=' . urlencode($numero));
            exit;
        }

        require __DIR__ . '/app/Views/acceso_solicitado.php';
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
        $db = menu_db();
        $cli = $db ? (new \App\Models\Cliente($db))->getClienteByCelular($numero) : null;
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
