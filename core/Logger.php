<?php
namespace Core;

/**
 * Sistema de logging estructurado
 */
class Logger
{
    const EMERGENCY = 'EMERGENCY';
    const ALERT = 'ALERT';
    const CRITICAL = 'CRITICAL';
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const NOTICE = 'NOTICE';
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';
    const SECURITY = 'SECURITY';

    /**
     * Log de emergencia
     */
    public static function emergency($message, $context = [])
    {
        self::log(self::EMERGENCY, $message, $context);
    }

    /**
     * Log de alerta
     */
    public static function alert($message, $context = [])
    {
        self::log(self::ALERT, $message, $context);
    }

    /**
     * Log crítico
     */
    public static function critical($message, $context = [])
    {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Log de error
     */
    public static function error($message, $context = [])
    {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Log de advertencia
     */
    public static function warning($message, $context = [])
    {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Log de aviso
     */
    public static function notice($message, $context = [])
    {
        self::log(self::NOTICE, $message, $context);
    }

    /**
     * Log informativo
     */
    public static function info($message, $context = [])
    {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Log de debug
     */
    public static function debug($message, $context = [])
    {
        if (APP_DEBUG) {
            self::log(self::DEBUG, $message, $context);
        }
    }

    /**
     * Log de seguridad
     */
    public static function security($message, $context = [])
    {
        self::log(self::SECURITY, $message, $context, SECURITY_LOG_FILE);
    }

    /**
     * Método principal de logging
     */
    private static function log($level, $message, $context = [], $logFile = null)
    {
        $logFile = $logFile ?: LOG_FILE;

        // Crear directorio si no existe
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        // Formato del log
        $timestamp = date('Y-m-d H:i:s');
        $ip = self::getIpAddress();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $userId = Session::get('user_id', 'guest');
        
        // Construir mensaje
        $logMessage = sprintf(
            "[%s] [%s] [IP: %s] [User: %s] %s",
            $timestamp,
            $level,
            $ip,
            $userId,
            $message
        );

        // Agregar contexto si existe
        if (!empty($context)) {
            $logMessage .= ' | Context: ' . json_encode($context);
        }

        // Agregar user agent en logs de seguridad
        if ($level === self::SECURITY) {
            $logMessage .= ' | UA: ' . substr($userAgent, 0, 100);
        }

        $logMessage .= PHP_EOL;

        // Escribir en archivo
        @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);

        // Si es error crítico, también enviar a error_log de PHP
        if (in_array($level, [self::EMERGENCY, self::ALERT, self::CRITICAL, self::ERROR])) {
            error_log($message);
        }
    }

    /**
     * Log de login exitoso
     */
    public static function logSuccessfulLogin($email)
    {
        self::security("Login exitoso", [
            'email' => $email,
            'action' => 'successful_login'
        ]);
    }

    /**
     * Log de login fallido
     */
    public static function logFailedLogin($email, $reason = 'invalid_credentials')
    {
        self::security("Intento de login fallido", [
            'email' => $email,
            'reason' => $reason,
            'action' => 'failed_login'
        ]);
    }

    /**
     * Log de logout
     */
    public static function logLogout($email)
    {
        self::security("Logout", [
            'email' => $email,
            'action' => 'logout'
        ]);
    }

    /**
     * Log de cuenta bloqueada por rate limiting
     */
    public static function logAccountLocked($email)
    {
        self::security("Cuenta bloqueada por múltiples intentos fallidos", [
            'email' => $email,
            'action' => 'account_locked'
        ]);
    }

    /**
     * Log de actividad sospechosa
     */
    public static function logSuspiciousActivity($description, $context = [])
    {
        self::security("Actividad sospechosa detectada: {$description}", $context);
    }

    /**
     * Obtener dirección IP
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

    /**
     * Limpiar logs antiguos (más de 30 días)
     */
    public static function cleanOldLogs($days = 30)
    {
        $logFiles = [LOG_FILE, ERROR_LOG_FILE, SECURITY_LOG_FILE];
        
        foreach ($logFiles as $logFile) {
            if (file_exists($logFile)) {
                $lines = file($logFile);
                $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
                
                $newLines = array_filter($lines, function($line) use ($cutoffDate) {
                    preg_match('/\[(\d{4}-\d{2}-\d{2})/', $line, $matches);
                    return isset($matches[1]) && $matches[1] >= $cutoffDate;
                });
                
                file_put_contents($logFile, implode('', $newLines));
            }
        }
    }
}