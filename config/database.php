<?php
/**
 * database.php - VERSIÓN SIMPLE SIN DEPENDENCIAS
 * Ubicación: /config/database.php
 * 
 * Compatible con tus controllers
 */
date_default_timezone_set('America/Bogota');
class Database
{
    private $host = 'localhost';
    private $db_name = '';
    private $username = '';
    private $password = '';
    
    /** @var PDO|null Instancia única de PDO */
    private static $pdo = null;
    
    /** @var Database|null Instancia única de Database */
    private static $instance = null;

    /**
     * Constructor - Cargar configuración
     */
    public function __construct() {
        // Si ya existe instancia, reutilizarla
        if (self::$instance !== null) {
            return self::$instance;
        }
        
        // ✅ Cargar desde .env si existe
        $this->loadEnvConfig();
        
        self::$instance = $this;
    }
    
    /**
     * Cargar configuración desde .env
     */
    private function loadEnvConfig() {
        // 1) Variables de entorno del servidor (Hostinger/cPanel, docker, etc.)
        $this->host     = getenv('DB_HOST') ?: $this->host;
        $this->db_name  = getenv('DB_NAME') ?: $this->db_name;
        $this->username = getenv('DB_USER') ?: $this->username;
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : $this->password;

        // 2) Archivo .env (fuera del control de versiones)
        $envFile = __DIR__ . '/../.env';

        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parsear KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, '"\''); // Quitar comillas
                
                // Asignar valores
                switch ($key) {
                    case 'DB_HOST':
                        $this->host = $value;
                        break;
                    case 'DB_NAME':
                        $this->db_name = $value;
                        break;
                    case 'DB_USER':
                        $this->username = $value;
                        break;
                    case 'DB_PASS':
                        $this->password = $value;
                        break;
                }
            }
        }
    }

    /**
     * Obtener instancia única (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtener conexión PDO
     */
    public function getConnection() {
        // Si ya existe conexión, reutilizarla
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        // Crear nueva conexión
        $dsn = "mysql:host={$this->host};port=3306;dbname={$this->db_name};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT         => true,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            self::$pdo = new PDO($dsn, $this->username, $this->password, $options);
            
            // Configurar timezone
            self::$pdo->exec("SET time_zone='-05:00'");
            
        } catch (PDOException $e) {
            // Log error
            error_log('❌ Database connection error: ' . $e->getMessage());
            
            // En desarrollo, mostrar error
            if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') {
                throw new PDOException('Error de conexión: ' . $e->getMessage());
            }
            
            // En producción, mensaje genérico
            throw new Exception($e->getMessage());
        }

        return self::$pdo;
    }

    /**
     * Cerrar conexión
     */
    public static function close() {
        self::$pdo = null;
    }

    /**
     * Verificar si hay conexión activa
     */
    public static function isConnected() {
        return self::$pdo instanceof PDO;
    }
}