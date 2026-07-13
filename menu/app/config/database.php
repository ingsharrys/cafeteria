<?php
namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct()
    {
        // La app del menú vive en una subcarpeta y no comparte el bootstrap
        // principal, así que carga el .env por su cuenta.
        $env = $this->loadEnv();

        $this->host     = getenv('DB_HOST') ?: ($env['DB_HOST'] ?? 'localhost');
        $this->db_name  = getenv('DB_NAME') ?: ($env['DB_NAME'] ?? '');
        $this->username = getenv('DB_USER') ?: ($env['DB_USER'] ?? '');

        $envPass = getenv('DB_PASS');
        $this->password = ($envPass !== false && $envPass !== '')
            ? $envPass
            : ($env['DB_PASS'] ?? '');
    }

    /**
     * Lee el archivo .env (ubicado en la raíz del sitio) y devuelve sus
     * variables como array.
     */
    private function loadEnv(): array
    {
        $candidates = [
            __DIR__ . '/../../../.env', // raíz del sitio (…/cafeteria.sharrys.com/.env)
            __DIR__ . '/../../.env',    // …/menu/.env (por si se coloca aquí)
        ];

        $vars = [];
        foreach ($candidates as $envFile) {
            if (!is_file($envFile)) {
                continue;
            }
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                    continue;
                }
                [$key, $value] = explode('=', $line, 2);
                $vars[trim($key)] = trim(trim($value), "\"'");
            }
            break; // usar el primer .env encontrado
        }

        return $vars;
    }

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
