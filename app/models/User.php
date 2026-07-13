<?php
namespace App\Models;

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password;
    
    // Nuevos campos de rol
    public $rol_id;
    public $rol_nombre;
    public $paginas_permitidas = [];
    public $id_mese;
    public $nombre_mese;
    public $cargo;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Crear nuevo usuario
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (username, email, password, rol_id, id_mese) 
                  VALUES (:username, :email, :password, :rol_id, :id_mese)";
        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":rol_id", $this->rol_id);
        $stmt->bindParam(":id_mese", $this->id_mese);

        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Autenticar usuario + cargar rol y mesero
     */
    public function authenticate($password) {
        $query = "
            SELECT u.id, u.username, u.email, u.password, u.rol_id, u.id_mese, u.activo
            FROM {$this->table_name} u
            WHERE u.email = :email 
            LIMIT 1
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        
        try {
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$row) return false;
            
            // Verificar contraseña
            if (!password_verify($password, $row['password'])) return false;
            
            // Verificar usuario activo
            if (isset($row['activo']) && $row['activo'] == 0) return false;

            // Guardar datos del usuario
            $this->id       = $row['id'];
            $this->username = $row['username'];
            $this->email    = $row['email'];
            
            // Datos de rol
            $this->rol_id           = $row['rol_id'];
            $this->rol_nombre       = 'default';
            $this->paginas_permitidas = [];
            
            // Datos de mesero/empleado
            $this->id_mese     = $row['id_mese'];
            $this->nombre_mese = $row['username'];
            $this->cargo       = 'default';
            
            return true;
            
        } catch (\PDOException $e) {
            error_log("Error authenticating user: " . $e->getMessage());
        }
        
        return false;
    }

    /**
     * Buscar por email
     */
    public function findByEmail($email) {
        $query = "SELECT u.id, u.username, u.email, u.rol_id, u.id_mese
                  FROM {$this->table_name} u
                  WHERE u.email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        
        try {
            $stmt->execute();
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error finding user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si el email ya existe
     */
    public function emailExists($email) {
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        
        try {
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Error checking email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener páginas permitidas por rol
     */
    public function getPaginasPermitidas() {
        return $this->paginas_permitidas;
    }

    /**
     * Verificar si tiene acceso a una página
     */
    public function canAccess($pagina) {
        // Admin tiene acceso a todo
        if ($this->rol_nombre === 'admin') return true;
        return in_array($pagina, $this->paginas_permitidas);
    }

    /**
     * Actualizar último login
     */
    public function updateLastLogin() {
        $query = "UPDATE {$this->table_name} SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error updating last login: " . $e->getMessage());
            return false;
        }
    }
}