<?php
namespace Core;

/**
 * Router simple para APIs
 */
class Router {
    private static $routes = [];
    private static $prefix = '';

    /**
     * Agregar prefijo de grupo
     */
    public static function group($prefix, $callback) {
        $previousPrefix = self::$prefix;
        self::$prefix = $previousPrefix . $prefix;
        $callback();
        self::$prefix = $previousPrefix;
    }

    /**
     * Registrar ruta GET
     */
    public static function get($path, $handler) {
        self::addRoute('GET', self::$prefix . $path, $handler);
    }

    /**
     * Registrar ruta POST
     */
    public static function post($path, $handler) {
        self::addRoute('POST', self::$prefix . $path, $handler);
    }

    /**
     * Agregar ruta
     */
    private static function addRoute($method, $path, $handler) {
        self::$routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Despachar la petición
     */
    public static function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remover base path si existe
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        
        // Buscar ruta coincidente
        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $pattern = self::convertToRegex($route['path']);
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                return self::callHandler($route['handler'], $matches);
            }
        }
        
        // Ruta no encontrada
        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada', 'uri' => $uri]);
    }

    /**
     * Convertir path a regex
     */
    private static function convertToRegex($path) {
        $path = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $path);
        return '#^' . $path . '$#';
    }

    /**
     * Llamar handler
     */
    private static function callHandler($handler, $params = []) {
        if (is_array($handler)) {
            $class = $handler[0];
            $method = $handler[1];
            
            if (!class_exists($class)) {
                throw new \Exception("Clase no encontrada: $class");
            }
            
            $instance = new $class();
            return call_user_func_array([$instance, $method], $params);
        }
        
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }
        
        throw new \Exception("Handler inválido");
    }

    /**
     * Obtener todas las rutas (debug)
     */
    public static function getRoutes() {
        return self::$routes;
    }
}