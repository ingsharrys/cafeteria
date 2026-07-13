<?php
namespace App\Middleware;

use Core\Session;
use Core\Response;

class AuthMiddleware {
    public static function handle() {
        Session::start();
        
        if (!Session::exists('user_id')) {
            Response::json([
                'success' => false,
                'message' => 'No autenticado'
            ], 401);
            return false;
        }
        
        return true;
    }
}