<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/Models/EstadoPedido.php';

use App\Config\Database;
use App\Models\EstadoPedido;

$numero = $_GET['numero'] ?? '3173667467';

echo "🔍 DEBUG - Buscando cliente con número: <strong>$numero</strong><br><br>";

try {
    $database = new Database();
    $db = $database->getConnection();
    $modelo = new EstadoPedido($db);
    
    // 1. Obtener cliente
    echo "1️⃣ Buscando cliente...<br>";
    $cliente = $modelo->obtenerClientePorCelular($numero);
    
    if ($cliente) {
        echo "✅ Cliente encontrado:<br>";
        echo "<pre>";
        print_r($cliente);
        echo "</pre><br>";
        
        $idCliente = $cliente['id'];
        
        // 2. Obtener pedido pendiente
        echo "2️⃣ Buscando pedido pendiente...<br>";
        $pedidoPendiente = $modelo->obtenerPedidoPendiente($idCliente);
        
        if ($pedidoPendiente) {
            echo "✅ Pedido pendiente encontrado:<br>";
            echo "<pre>";
            print_r($pedidoPendiente);
            echo "</pre><br>";
            
            // 3. Obtener detalles del pedido
            $idPedido = $pedidoPendiente['id_pedido'];
            echo "3️⃣ Buscando detalles del pedido #$idPedido...<br>";
            $detalles = $modelo->obtenerDetallesPedido($idPedido);
            
            if ($detalles) {
                echo "✅ Detalles encontrados:<br>";
                echo "<pre>";
                print_r($detalles);
                echo "</pre><br>";
            } else {
                echo "❌ No hay detalles del pedido<br>";
            }
        } else {
            echo "⚠️ No hay pedido pendiente<br>";
        }
        
        // 4. Obtener historial
        echo "4️⃣ Buscando historial...<br>";
        $historial = $modelo->obtenerHistorialPedidos($idCliente);
        
        if ($historial) {
            echo "✅ Historial encontrado (" . count($historial) . " pedidos):<br>";
            echo "<pre>";
            print_r($historial);
            echo "</pre>";
        } else {
            echo "⚠️ Sin historial<br>";
        }
        
    } else {
        echo "❌ Cliente NO encontrado<br>";
        echo "Verifica que el número $numero existe en la BD<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>