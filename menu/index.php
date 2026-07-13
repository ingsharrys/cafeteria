<?php
require_once __DIR__ . '/app/config/database.php';

// Cargar automáticamente las clases con Composer (si usas autoload). 
// Si no, inclúyelas manualmente.
require_once __DIR__ . '/vendor/autoload.php';

use App\Controllers\PedidoController;
use App\Controllers\EstadoPedidoController;

// Podrías usar un router sencillo basado en la variable 'route' de $_GET
$route = $_GET['route'] ?? 'home';

switch ($route) {
    case 'pedidos':
        $controller = new PedidoController();
        $controller->index();
        break;

    case 'pedido-store':
        $controller = new PedidoController();
        $controller->store(); // <-- Método donde insertas el pedido y devuelves JSON
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

