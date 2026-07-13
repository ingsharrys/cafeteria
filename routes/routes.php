<?php
/**
 * Definición de rutas API
 */

use Core\Router;
use App\Controllers\PedidoController;
use App\Controllers\MesaController;
use App\Controllers\TurnoController;
use App\Controllers\MeseroController;
use App\Controllers\CajaController;
use App\Controllers\MasivoDomingoController;

// Rutas de Pedidos
Router::group('/api/pedidos', function() {
    Router::get('/datos', [PedidoController::class, 'obtenerDatos']);
    Router::get('/productos', [PedidoController::class, 'obtenerProductos']);
    Router::post('/estado', [PedidoController::class, 'cambiarEstado']);
    Router::post('/crear', [PedidoController::class, 'crear']);
    Router::get('/total', [PedidoController::class, 'obtenerTotal']);
});

// Rutas de Mesas
Router::group('/api/mesas', function() {
    Router::get('/', [MesaController::class, 'index']);
    Router::get('/libres', [MesaController::class, 'libres']);
    Router::post('/liberar', [MesaController::class, 'liberar']);
    Router::post('/cambiar', [MesaController::class, 'cambiar']);
    Router::post('/estado', [MesaController::class, 'cambiarEstado']);
});

// Rutas de Turnos
Router::group('/api/turnos', function() {
    Router::get('/', [TurnoController::class, 'index']);
    Router::get('/pendientes', [TurnoController::class, 'pendientes']);
    Router::post('/estado', [TurnoController::class, 'cambiarEstado']);
    Router::post('/crear', [TurnoController::class, 'crear']);
    Router::get('/estadisticas', [TurnoController::class, 'estadisticas']);
});

// Rutas de Meseros
Router::group('/api/meseros', function() {
    Router::get('/', [MeseroController::class, 'index']);
    Router::post('/validar', [MeseroController::class, 'validarCodigo']);
    Router::get('/cargo', [MeseroController::class, 'obtenerPorCargo']);
    Router::post('/crear', [MeseroController::class, 'crear']);
    Router::post('/actualizar', [MeseroController::class, 'actualizar']);
    Router::post('/eliminar', [MeseroController::class, 'eliminar']);
});

// Rutas de Caja
Router::group('/api/caja', function() {
    Router::post('/pagar', [CajaController::class, 'registrarPago']);
    Router::get('/movimientos', [CajaController::class, 'obtenerDelDia']);
    Router::get('/totales', [CajaController::class, 'obtenerTotales']);
    Router::get('/consolidado', [CajaController::class, 'consolidado']);
    Router::post('/gasto', [CajaController::class, 'registrarGasto']);
    Router::get('/gastos', [CajaController::class, 'obtenerGastos']);
});
