<?php
/**
 * login_new.php - Sistema de Login Completo
 * Con rate limiting, logging y validaciones
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\AuthController;
use Core\Logger;

try {
    $controller = new AuthController();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->processLogin();
    } else {
        $controller->showLogin();
    }
    
} catch (Exception $e) {
    // Log del error
    Logger::critical('Error en login_new.php', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    // Mostrar error amigable
    if (APP_DEBUG) {
        echo "<h1>Error en el sistema de login</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Archivo: " . $e->getFile() . " (línea " . $e->getLine() . ")</p>";
    } else {
        echo "<h1>Error temporal</h1>";
        echo "<p>Por favor, intenta nuevamente en unos momentos.</p>";
        
        echo "<h1>Error en el sistema de login</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Archivo: " . $e->getFile() . " (línea " . $e->getLine() . ")</p>";
    }
}