<?php
/**
 * routes.php - Definición de rutas de la aplicación
 * Este archivo contiene todas las rutas organizadas por recursos
 */

use App\Router;

// Crear instancia del router
$router = new Router();

// ============================================
// RUTAS PÚBLICAS (sin autenticación)
// ============================================

// Health check
$router->get('/health', function() {
    \Core\Response::json([
        'status' => 'ok',
        'timestamp' => time(),
        'version' => '2.0.0'
    ]);
});

// ============================================
// GRUPO: API (requiere autenticación)
// ============================================

$router->group('/api', function($router) {
    
    // ----------------------------------------
    // PEDIDOS
    // ----------------------------------------
    $router->get('/pedidos', 'PedidoController@obtenerDatos');
    $router->get('/pedidos/{id}', 'PedidoController@obtenerDatos');
    $router->get('/pedidos/{id}/productos', 'PedidoController@obtenerProductos');
    $router->get('/pedidos/{id}/total', 'PedidoController@obtenerTotal');
    $router->post('/pedidos', 'PedidoController@crear');
    $router->post('/pedidos/{id}/estado', 'PedidoController@cambiarEstado');
    
    // ----------------------------------------
    // MESAS
    // ----------------------------------------
    $router->get('/mesas', 'MesaController@index');
    $router->get('/mesas/todas', 'MesaController@todas');
    $router->get('/mesas/estadisticas', 'MesaController@estadisticas');
    $router->post('/mesas/liberar', 'MesaController@liberar');
    $router->post('/mesas/cambiar', 'MesaController@cambiarMesa');
    $router->post('/mesas/asignar', 'MesaController@asignarPedido');
    $router->post('/mesas/{id}/estado', 'MesaController@cambiarEstado');
    
    // ----------------------------------------
    // TURNOS (Domicilios y Recoger)
    // ----------------------------------------
    $router->get('/turnos', 'TurnoController@index');
    $router->get('/turnos/pendientes', 'TurnoController@pendientes');
    $router->get('/turnos/{id}', 'TurnoController@obtenerPorPedido');
    $router->get('/turnos/estadisticas', 'TurnoController@estadisticas');
    $router->post('/turnos', 'TurnoController@crear');
    $router->post('/turnos/{id}/estado', 'TurnoController@cambiarEstado');
    $router->delete('/turnos/{id}', 'TurnoController@eliminar');
    
    // ----------------------------------------
    // MESEROS / COLABORADORES
    // ----------------------------------------
    $router->get('/meseros', 'MeseroController@index');
    $router->get('/meseros/{id}', 'MeseroController@obtenerPorId');
    $router->get('/meseros/cargo/{cargo}', 'MeseroController@obtenerPorCargo');
    $router->get('/meseros/estadisticas', 'MeseroController@estadisticas');
    $router->post('/meseros/validar', 'MeseroController@validarCodigo');
    $router->post('/meseros', 'MeseroController@crear');
    $router->put('/meseros/{id}', 'MeseroController@actualizar');
    $router->delete('/meseros/{id}', 'MeseroController@eliminar');
    
    // ----------------------------------------
    // CAJA Y PAGOS
    // ----------------------------------------
    $router->get('/caja', 'CajaController@obtenerDelDia');
    $router->get('/caja/totales', 'CajaController@obtenerTotales');
    $router->get('/caja/consolidado', 'CajaController@consolidado');
    $router->post('/caja/pago', 'CajaController@registrarPago');
    $router->get('/caja/verificar/{id}', 'CajaController@verificarPago');
    
    // ----------------------------------------
    // GASTOS
    // ----------------------------------------
    $router->get('/gastos', 'CajaController@obtenerGastos');
    $router->post('/gastos', 'CajaController@registrarGasto');
    
}, ['auth']); // Middleware de autenticación para todo el grupo

// ============================================
// RUTAS DE COMPATIBILIDAD (deprecadas)
// ============================================

// Mantener URLs antiguas para transición suave
$router->get('/obtener_datos.php', 'PedidoController@obtenerDatos');
$router->get('/obtener_datos_pedido.php', 'PedidoController@obtenerProductos');
$router->get('/obtener_datos_turnos.php', 'TurnoController@index');
$router->post('/actualizar_estado_turnero.php', 'TurnoController@cambiarEstado');
$router->post('/validar_codigo.php', 'MeseroController@validarCodigo');
$router->post('/liberar_mesa.php', 'MesaController@liberar');
$router->post('/cambiar_mesa.php', 'MesaController@cambiarMesa');

// ============================================
// RUTAS DE DEBUG (solo en desarrollo)
// ============================================

if (defined('APP_DEBUG') && APP_DEBUG) {
    $router->get('/debug/routes', function() use ($router) {
        header('Content-Type: text/plain');
        echo $router->listRoutes();
    });
    
    $router->get('/debug/session', function() {
        \Core\Response::json([
            'session_id' => session_id(),
            'session_data' => $_SESSION ?? [],
            'user_id' => \Core\Session::get('user_id'),
            'cajero' => \Core\Session::get('cajero')
        ]);
    });
}

// Retornar router configurado
return $router;