<?php
namespace App;

use Core\Response;
use Core\Logger;

/**
 * Router - Sistema de enrutamiento profesional
 * Maneja todas las rutas de la aplicación con soporte para:
 * - REST API routes
 * - Middleware
 * - Parámetros de ruta
 * - Validación automática
 */
class Router {
    private $routes = [];
    private $middleware = [];
    private $currentPrefix = '';
    
    /**
     * Registrar ruta GET
     */
    public function get($path, $handler, $middleware = []) {
        $this->addRoute('GET', $path, $handler, $middleware);
        return $this;
    }
    
    /**
     * Registrar ruta POST
     */
    public function post($path, $handler, $middleware = []) {
        $this->addRoute('POST', $path, $handler, $middleware);
        return $this;
    }
    
    /**
     * Registrar ruta PUT
     */
    public function put($path, $handler, $middleware = []) {
        $this->addRoute('PUT', $path, $handler, $middleware);
        return $this;
    }
    
    /**
     * Registrar ruta DELETE
     */
    public function delete($path, $handler, $middleware = []) {
        $this->addRoute('DELETE', $path, $handler, $middleware);
        return $this;
    }
    
    /**
     * Registrar múltiples métodos para una ruta
     */
    public function match($methods, $path, $handler, $middleware = []) {
        foreach ($methods as $method) {
            $this->addRoute($method, $path, $handler, $middleware);
        }
        return $this;
    }
    
    /**
     * Agrupar rutas con prefijo común
     */
    public function group($prefix, callable $callback, $middleware = []) {
        $previousPrefix = $this->currentPrefix;
        $this->currentPrefix .= $prefix;
        
        // Ejecutar callback con el router
        $callback($this);
        
        // Restaurar prefijo anterior
        $this->currentPrefix = $previousPrefix;
        return $this;
    }
    
    /**
     * Agregar middleware global
     */
    public function middleware($middleware) {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }
        return $this;
    }
    
    /**
     * Agregar ruta al registro
     */
    private function addRoute($method, $path, $handler, $middleware = []) {
        $fullPath = $this->currentPrefix . $path;
        
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => $middleware,
            'regex' => $this->pathToRegex($fullPath)
        ];
    }
    
    /**
     * Convertir path a regex para matching
     */
    private function pathToRegex($path) {
        // Convertir {id} a regex
        $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $regex . '$#';
    }
    
    /**
     * Ejecutar el router
     */
    public function run() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            
            // Remover el base path si existe
            $basePath = $this->getBasePath();
            if ($basePath && strpos($path, $basePath) === 0) {
                $path = substr($path, strlen($basePath));
            }
            
            // Si no hay path, usar '/'
            $path = $path ?: '/';
            
            // Buscar ruta coincidente
            foreach ($this->routes as $route) {
                if ($route['method'] !== $method) {
                    continue;
                }
                
                if (preg_match($route['regex'], $path, $matches)) {
                    // Extraer parámetros de la ruta
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    
                    // Ejecutar middleware global
                    foreach ($this->middleware as $mw) {
                        $this->executeMiddleware($mw);
                    }
                    
                    // Ejecutar middleware de la ruta
                    foreach ($route['middleware'] as $mw) {
                        $this->executeMiddleware($mw);
                    }
                    
                    // Ejecutar handler
                    $this->executeHandler($route['handler'], $params);
                    return;
                }
            }
            
            // No se encontró ruta
            Response::notFound('Ruta no encontrada');
            
        } catch (\Exception $e) {
            Logger::error('Error en router', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            Response::serverError('Error interno del servidor');
        }
    }
    
    /**
     * Ejecutar middleware
     */
    private function executeMiddleware($middleware) {
        if (is_callable($middleware)) {
            $middleware();
        } elseif (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            if (method_exists($instance, 'handle')) {
                $instance->handle();
            }
        }
    }
    
    /**
     * Ejecutar handler de la ruta
     */
    private function executeHandler($handler, $params = []) {
        if (is_callable($handler)) {
            // Handler es una función
            $handler($params);
        } elseif (is_string($handler)) {
            // Handler es "Controller@method"
            $this->executeControllerMethod($handler, $params);
        } elseif (is_array($handler) && count($handler) === 2) {
            // Handler es [Controller::class, 'method']
            $this->executeControllerAction($handler[0], $handler[1], $params);
        }
    }
    
    /**
     * Ejecutar método de controller (formato "Controller@method")
     */
    private function executeControllerMethod($handler, $params = []) {
        list($controller, $method) = explode('@', $handler);
        
        // Agregar namespace si no tiene
        if (strpos($controller, '\\') === false) {
            $controller = 'App\\Controllers\\' . $controller;
        }
        
        $this->executeControllerAction($controller, $method, $params);
    }
    
    /**
     * Ejecutar acción de controller
     */
    private function executeControllerAction($controller, $method, $params = []) {
        if (!class_exists($controller)) {
            throw new \Exception("Controller no encontrado: {$controller}");
        }
        
        $instance = new $controller();
        
        if (!method_exists($instance, $method)) {
            throw new \Exception("Método no encontrado: {$controller}::{$method}");
        }
        
        // Inyectar parámetros si el método los requiere
        if (!empty($params)) {
            $instance->$method($params);
        } else {
            $instance->$method();
        }
    }
    
    /**
     * Obtener base path de la aplicación
     */
    private function getBasePath() {
        if (defined('BASE_PATH')) {
            return BASE_PATH;
        }
        
        // Detectar automáticamente
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $scriptDir = dirname($scriptName);
        
        return $scriptDir !== '/' ? $scriptDir : '';
    }
    
    /**
     * Obtener todas las rutas registradas
     */
    public function getRoutes() {
        return $this->routes;
    }
    
    /**
     * Listar rutas (útil para debug)
     */
    public function listRoutes() {
        $output = "Rutas registradas:\n\n";
        
        foreach ($this->routes as $route) {
            $output .= sprintf(
                "%-6s %-40s => %s\n",
                $route['method'],
                $route['path'],
                is_string($route['handler']) ? $route['handler'] : 'Closure'
            );
        }
        
        return $output;
    }
}