<?php
namespace Core;

/**
 * Rate Limiter para prevenir ataques de fuerza bruta
 */
class RateLimiter
{
    private $maxAttempts;
    private $decayMinutes;

    public function __construct($maxAttempts = MAX_LOGIN_ATTEMPTS, $decayMinutes = LOGIN_LOCKOUT_TIME / 60)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    /**
     * Verificar si hay demasiados intentos
     */
    public function tooManyAttempts($key)
    {
        $attempts = $this->getAttempts($key);
        $expiresAt = Session::get($key . '_expires');

        // Si ya expiró, limpiar
        if ($expiresAt && time() > $expiresAt) {
            $this->clear($key);
            return false;
        }

        return $attempts >= $this->maxAttempts;
    }

    /**
     * Incrementar contador de intentos
     */
    public function hit($key)
    {
        $attempts = $this->getAttempts($key);
        $attempts++;

        Session::set($key . '_attempts', $attempts);

        // Establecer tiempo de expiración solo en el primer intento
        if ($attempts === 1) {
            $expiresAt = time() + ($this->decayMinutes * 60);
            Session::set($key . '_expires', $expiresAt);
        }

        return $attempts;
    }

    /**
     * Obtener número de intentos
     */
    public function getAttempts($key)
    {
        return (int) Session::get($key . '_attempts', 0);
    }

    /**
     * Obtener intentos restantes
     */
    public function attemptsRemaining($key)
    {
        $attempts = $this->getAttempts($key);
        return max(0, $this->maxAttempts - $attempts);
    }

    /**
     * Obtener tiempo restante de bloqueo en segundos
     */
    public function availableIn($key)
    {
        $expiresAt = Session::get($key . '_expires');
        
        if (!$expiresAt) {
            return 0;
        }

        $remaining = $expiresAt - time();
        return max(0, $remaining);
    }

    /**
     * Limpiar intentos
     */
    public function clear($key)
    {
        Session::delete($key . '_attempts');
        Session::delete($key . '_expires');
    }

    /**
     * Resetear después de login exitoso
     */
    public function resetAttempts($key)
    {
        $this->clear($key);
    }

    /**
     * Obtener mensaje de bloqueo
     */
    public function getLockoutMessage($key)
    {
        $seconds = $this->availableIn($key);
        $minutes = ceil($seconds / 60);

        if ($minutes > 1) {
            return "Demasiados intentos fallidos. Intenta nuevamente en {$minutes} minutos.";
        } else {
            return "Demasiados intentos fallidos. Intenta nuevamente en {$seconds} segundos.";
        }
    }

    /**
     * Generar clave única por IP y email
     */
    public static function generateKey($identifier)
    {
        $ip = self::getIpAddress();
        return 'rate_limit_' . md5($identifier . '_' . $ip);
    }

    /**
     * Obtener dirección IP real del usuario
     */
    private static function getIpAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return filter_var(trim($ip), FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }
}