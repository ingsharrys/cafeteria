<?php
namespace Core;

class Token {
    public static function generate() {
        Session::start();
        
        $token = bin2hex(random_bytes(32));
        Session::set('csrf_token', $token);
        Session::set('csrf_token_time', time());
        
        return $token;
    }

    public static function validate($token) {
        Session::start();
        
        if (empty($token)) {
            return false;
        }

        $sessionToken = Session::get('csrf_token');
        $tokenTime = Session::get('csrf_token_time');

        if (!$sessionToken) {
            return false;
        }

        // Expiración de 1 hora
        if ($tokenTime && (time() - $tokenTime) > 3600) {
            Session::delete('csrf_token');
            Session::delete('csrf_token_time');
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public static function regenerate() {
        Session::delete('csrf_token');
        Session::delete('csrf_token_time');
        return self::generate();
    }
}