<?php
namespace App;

class Router {
    private $routes = [];
    private $middlewares = [];
    
    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }
    
    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }
    
    public function put($path, $handler) {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }
    
    public function delete($path, $handler) {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }
    
    public function middleware($middleware) {
        if (!empty($this->routes)) {
            $lastRoute = array_key_last($this->routes);
            $this->routes[$lastRoute]['middlewares'][] = $middleware;
        }
        return $this;
    }
    
    private function addRoute($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => []
        ];
    }
    
    public function run() {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        
        // Remover /heiyubai del path si existe
        $requestUri = preg_replace('#^/heiyubai#', '', $requestUri);
        
        foreach ($this->routes as $route) {
            $pattern = $this->convertToRegex($route['path']);
            
            if ($route['method'] === $requestMethod && preg_match($pattern, $requestUri, $matches)) {
                array_shift($matches);
                
                // Ejecutar middlewares
                foreach ($route['middlewares'] as $middleware) {
                    if (is_callable($middleware)) {
                        $result = call_user_func($middleware);
                        if ($result === false) return;
                    }
                }
                
                return $this->executeHandler($route['handler'], $matches);
            }
        }
        
        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada', 'path' => $requestUri]);
    }
    
    private function convertToRegex($path) {
        $path = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $path . '$#';
    }
    
    private function executeHandler($handler, $params = []) {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }
        
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);
            
            $controllerClass = "App\\Controllers\\$controller";
            
            if (!class_exists($controllerClass)) {
                http_response_code(500);
                echo json_encode(['error' => 'Controller no encontrado', 'controller' => $controllerClass]);
                return;
            }
            
            $controllerInstance = new $controllerClass();
            
            if (!method_exists($controllerInstance, $method)) {
                http_response_code(500);
                echo json_encode(['error' => 'Método no encontrado', 'method' => $method]);
                return;
            }
            
            return call_user_func_array([$controllerInstance, $method], $params);
        }
    }
    
    public function getRoutes() {
        return $this->routes;
    }
}