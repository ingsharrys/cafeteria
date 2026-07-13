<?php
namespace Core;

/**
 * Clase para manejar respuestas HTTP de forma unificada
 */
class Response
{
    /**
     * Redirigir a una URL
     */
    public static function redirect($url, $statusCode = 302)
    {
        header("Location: {$url}", true, $statusCode);
        exit();
    }

    /**
     * Redirigir al login
     */
    public static function redirectToLogin()
    {
        self::redirect(LOGIN_URL);
    }

    /**
     * Redirigir al dashboard
     */
    public static function redirectToDashboard()
    {
        self::redirect(DASHBOARD_URL);
    }

    /**
     * Redirigir con mensaje de error
     */
    public static function redirectWithError($url, $message, $oldInput = [])
    {
        Session::set('error_message', $message);
        
        if (!empty($oldInput)) {
            Session::set('old_input', $oldInput);
        }
        
        self::redirect($url);
    }

    /**
     * Redirigir con mensaje de éxito
     */
    public static function redirectWithSuccess($url, $message)
    {
        Session::set('success_message', $message);
        self::redirect($url);
    }

    /**
     * Respuesta JSON
     */
    public static function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * Respuesta JSON de éxito
     */
    public static function jsonSuccess($message = 'Success', $data = [], $statusCode = 200)
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Respuesta JSON de error
     */
    public static function jsonError($message = 'Error', $errors = [], $statusCode = 400)
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Error 404
     */
    public static function notFound($message = 'Página no encontrada')
    {
        http_response_code(404);
        die($message);
    }

    /**
     * Error 403
     */
    public static function forbidden($message = 'Acceso denegado')
    {
        http_response_code(403);
        die($message);
    }

    /**
     * Error 500
     */
    public static function serverError($message = 'Error interno del servidor')
    {
        http_response_code(500);
        Logger::error('Server Error', ['message' => $message]);
        die($message);
    }

    /**
     * ✅ CORREGIDO: CSP con TODOS los dominios que tu app necesita
     * 
     * Antes faltaban:
     *   - cdnjs.cloudflare.com (crypto-js)
     *   - heiyubai.datarie.info (qz-tray.js)
     *   - kit.fontawesome.com + ka-f.fontawesome.com (iconos)
     *   - stackpath.bootstrapcdn.com (si usas Bootstrap CDN)
     *   - cdn.jsdelivr.net en connect-src (para source maps)
     */
    public static function setSecurityHeaders()
    {
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // ✅ CSP actualizado con todos los dominios necesarios
        $csp = implode(' ', [
            "default-src 'self';",
            
            // Scripts: todos los CDN y servicios que usas
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'"
                . " https://www.google.com"
                . " https://www.gstatic.com"
                . " https://cdn.jsdelivr.net"
                . " https://code.jquery.com"
                . " https://cdnjs.cloudflare.com"          // ← crypto-js
                . " https://heiyubai.datarie.info"          // ← qz-tray
                . " https://kit.fontawesome.com"            // ← fontawesome
                . " https://ka-f.fontawesome.com"           // ← fontawesome runtime
                . " https://stackpath.bootstrapcdn.com;",   // ← bootstrap legacy
            
            // Estilos
            "style-src 'self' 'unsafe-inline'"
                . " https://cdn.jsdelivr.net"
                . " https://fonts.googleapis.com"
                . " https://cdnjs.cloudflare.com"           // ← fontawesome CSS
                . " https://ka-f.fontawesome.com;",
            
            // Fuentes
            "font-src 'self'"
                . " https://fonts.gstatic.com"
                . " https://ka-f.fontawesome.com;",         // ← fontawesome fonts
            
            // Imágenes
            "img-src 'self' data: https:;",
            
            // Conexiones AJAX/fetch/WebSocket
            "connect-src 'self'"
                . " https://cdn.jsdelivr.net"               // ← source maps
                . " wss://localhost:*"                       // ← QZ Tray WebSocket
                . " ws://localhost:*;",                      // ← QZ Tray WebSocket
            
            // Frames
            "frame-src 'self' https://www.google.com;",
        ]);
        
        header("Content-Security-Policy: {$csp}");
        
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }

    /**
     * Obtener input antiguo del formulario
     */
    public static function old($key, $default = '')
    {
        $oldInput = Session::get('old_input', []);
        $value = $oldInput[$key] ?? $default;
        
        if (isset($oldInput[$key])) {
            unset($oldInput[$key]);
            Session::set('old_input', $oldInput);
        }
        
        return $value;
    }

    /**
     * Obtener y limpiar mensaje de error
     */
    public static function getError()
    {
        $error = Session::get('error_message');
        Session::delete('error_message');
        return $error;
    }

    /**
     * Obtener y limpiar mensaje de éxito
     */
    public static function getSuccess()
    {
        $success = Session::get('success_message');
        Session::delete('success_message');
        return $success;
    }
}