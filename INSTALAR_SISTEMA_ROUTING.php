<?php
/**
 * INSTALADOR V2 - HeiYuBai
 * Reorganiza archivos de la raíz a estructura MVC
 * 
 * PROBLEMA: Los archivos están en la raíz pero el autoload busca en carpetas
 * SOLUCIÓN: Mover archivos + actualizar autoload
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$ROOT = __DIR__;

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Instalador V2</title>";
echo "<style>
body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: #eee; padding: 20px; max-width: 1000px; margin: 0 auto; }
h1, h2, h3 { color: #00d4ff; }
.box { background: #16213e; padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #00d4ff; }
.ok { color: #00ff88; }
.error { color: #ff4757; }
.warn { color: #ffa502; }
pre { background: #0d1117; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 11px; }
.btn { display: inline-block; padding: 12px 24px; margin: 10px 5px; text-decoration: none; border-radius: 6px; font-weight: bold; }
.btn-primary { background: #00d4ff; color: #000; }
.btn-success { background: #00ff88; color: #000; }
</style></head><body>";

echo "<h1>🚀 Instalador V2 - HeiYuBai</h1>";
echo "<p>Este instalador reorganiza los archivos de la raíz a la estructura MVC correcta.</p>";

// ============================================
// PASO 1: Crear carpetas
// ============================================
echo "<div class='box'><h2>📁 PASO 1: Crear Estructura de Carpetas</h2>";

$folders = [
    'Core',
    'config', 
    'app',
    'app/Controllers',
    'app/Models',
    'app/Views',
    'app/Views/auth',
    'app/Middleware',
    'routes',
    'storage',
    'storage/logs',
    'storage/sessions',
    'storage/cache',
    'public',
    'public/js',
    'public/js/config',
    'public/js/services',
    'public/js/utils',
    'public/js/modules',
    'public/js/modules/mesas',
    'public/js/modules/turnos',
    'public/js/lib',
    'public/css',
    'views',
    'views/inc'
];

foreach ($folders as $folder) {
    $path = $ROOT . '/' . $folder;
    if (!is_dir($path)) {
        if (mkdir($path, 0755, true)) {
            echo "<p><span class='ok'>✓</span> Creada: $folder</p>";
        } else {
            echo "<p><span class='error'>✗</span> Error creando: $folder</p>";
        }
    } else {
        echo "<p>• Ya existe: $folder</p>";
    }
}

// También crear 'core' (minúscula) como enlace simbólico si no existe
if (!is_dir($ROOT . '/core') && is_dir($ROOT . '/Core')) {
    // En Windows no podemos crear symlinks fácilmente, así que simplemente usaremos Core
    echo "<p><span class='warn'>⚠</span> Nota: Se usará 'Core' (mayúscula)</p>";
}

echo "</div>";

// ============================================
// PASO 2: Mover archivos Core
// ============================================
echo "<div class='box'><h2>🔧 PASO 2: Organizar Archivos Core</h2>";

$coreFiles = [
    'Session.php',
    'Token.php', 
    'Response.php',
    'Logger.php',
    'Validator.php',
    'RateLimiter.php',
    'Cache.php'
];

foreach ($coreFiles as $file) {
    $source = $ROOT . '/' . $file;
    $dest = $ROOT . '/Core/' . $file;
    
    // Si existe en raíz pero no en Core, moverlo
    if (file_exists($source) && !file_exists($dest)) {
        // Leer contenido y agregar namespace si no tiene
        $content = file_get_contents($source);
        
        // Verificar si ya tiene namespace Core
        if (strpos($content, 'namespace Core;') === false && strpos($content, 'namespace Core\\') === false) {
            // Agregar namespace después de <?php
            $content = preg_replace('/^<\?php/', "<?php\nnamespace Core;\n", $content);
        }
        
        if (file_put_contents($dest, $content)) {
            echo "<p><span class='ok'>✓</span> Movido y actualizado: $file → Core/$file</p>";
        } else {
            echo "<p><span class='error'>✗</span> Error moviendo: $file</p>";
        }
    } elseif (file_exists($dest)) {
        echo "<p>• Ya existe en Core/: $file</p>";
    } else {
        echo "<p><span class='warn'>⚠</span> No encontrado: $file</p>";
    }
}

echo "</div>";

// ============================================
// PASO 3: Mover archivos Config
// ============================================
echo "<div class='box'><h2>⚙️ PASO 3: Organizar Archivos Config</h2>";

$configFiles = [
    'app.php' => true,      // Tiene namespace Config
    'constants.php' => false, // No tiene namespace
    'database.php' => false   // No tiene namespace
];

foreach ($configFiles as $file => $hasNamespace) {
    $source = $ROOT . '/' . $file;
    $dest = $ROOT . '/config/' . $file;
    
    if (file_exists($source) && !file_exists($dest)) {
        $content = file_get_contents($source);
        
        if (file_put_contents($dest, $content)) {
            echo "<p><span class='ok'>✓</span> Copiado: $file → config/$file</p>";
        } else {
            echo "<p><span class='error'>✗</span> Error copiando: $file</p>";
        }
    } elseif (file_exists($dest)) {
        echo "<p>• Ya existe en config/: $file</p>";
    } else {
        echo "<p><span class='warn'>⚠</span> No encontrado: $file</p>";
    }
}

echo "</div>";

// ============================================
// PASO 4: Mover Controllers
// ============================================
echo "<div class='box'><h2>🎮 PASO 4: Organizar Controllers</h2>";

$controllers = [
    'AuthController.php',
    'PedidoController.php',
    'MesaController.php',
    'TurnoController.php',
    'MeseroController.php',
    'CajaController.php'
];

foreach ($controllers as $file) {
    $source = $ROOT . '/' . $file;
    $dest = $ROOT . '/app/Controllers/' . $file;
    
    if (file_exists($source) && !file_exists($dest)) {
        $content = file_get_contents($source);
        
        // Verificar namespace
        if (strpos($content, 'namespace App\\Controllers;') === false) {
            $content = preg_replace('/^<\?php/', "<?php\nnamespace App\\Controllers;\n", $content);
        }
        
        if (file_put_contents($dest, $content)) {
            echo "<p><span class='ok'>✓</span> Movido: $file → app/Controllers/$file</p>";
        }
    } elseif (file_exists($dest)) {
        echo "<p>• Ya existe en app/Controllers/: $file</p>";
    } else {
        echo "<p><span class='warn'>⚠</span> No encontrado: $file</p>";
    }
}

echo "</div>";

// ============================================
// PASO 5: Crear/Actualizar User Model
// ============================================
echo "<div class='box'><h2>👤 PASO 5: User Model</h2>";

$userModelContent = '<?php
namespace App\Models;

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Crear nuevo usuario
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (username, email, password) 
                  VALUES (:username, :email, :password)";
        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);

        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Autenticar usuario
     */
    public function authenticate($password) {
        $query = "SELECT id, username, email, password FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        
        try {
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($row && password_verify($password, $row[\'password\'])) {
                $this->id = $row[\'id\'];
                $this->username = $row[\'username\'];
                $this->email = $row[\'email\'];
                return true;
            }
        } catch (\PDOException $e) {
            error_log("Error authenticating user: " . $e->getMessage());
        }
        
        return false;
    }

    /**
     * Buscar por email
     */
    public function findByEmail($email) {
        $query = "SELECT id, username, email FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 1";
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
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE email = :email";
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
     * Actualizar último login
     */
    public function updateLastLogin() {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Error updating last login: " . $e->getMessage());
            return false;
        }
    }
}';

$userModelPath = $ROOT . '/app/Models/User.php';
if (!file_exists($userModelPath)) {
    if (file_put_contents($userModelPath, $userModelContent)) {
        echo "<p><span class='ok'>✓</span> Creado: app/Models/User.php</p>";
    }
} else {
    echo "<p>• Ya existe: app/Models/User.php</p>";
}

echo "</div>";

// ============================================
// PASO 6: ACTUALIZAR AUTOLOAD (CRÍTICO)
// ============================================
echo "<div class='box'><h2>📚 PASO 6: Actualizar Autoload (CRÍTICO)</h2>";

$autoloadContent = '<?php
/**
 * Autoloader - HeiYuBai
 * ACTUALIZADO: Usa Core/ (mayúscula) consistentemente
 */

spl_autoload_register(function ($class) {
    $base_dir = __DIR__ . \'/\';
    
    // Mapeo de namespaces a directorios
    $namespace_map = [
        \'App\\\\Controllers\\\\\' => \'app/Controllers/\',
        \'App\\\\Models\\\\\'      => \'app/Models/\',
        \'App\\\\Views\\\\\'       => \'app/Views/\',
        \'App\\\\Middleware\\\\\' => \'app/Middleware/\',
        \'Core\\\\\'             => \'Core/\',  // Mayúscula
        \'Config\\\\\'           => \'config/\',
    ];
    
    foreach ($namespace_map as $namespace => $directory) {
        if (strpos($class, $namespace) === 0) {
            $relative_class = substr($class, strlen($namespace));
            $file = $base_dir . $directory . str_replace(\'\\\\\', \'/\', $relative_class) . \'.php\';
            
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
    
    // Fallback: buscar también en core/ (minúscula) por compatibilidad
    if (strpos($class, \'Core\\\\\') === 0) {
        $relative_class = substr($class, 5); // quitar "Core\\"
        $file = $base_dir . \'core/\' . str_replace(\'\\\\\', \'/\', $relative_class) . \'.php\';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Clases legacy sin namespace
    $legacy_paths = [
        \'config/\',
        \'Core/\',
        \'core/\',
        \'app/Models/\',
        \'app/Controllers/\',
    ];
    
    foreach ($legacy_paths as $path) {
        $file = $base_dir . $path . str_replace(\'\\\\\', \'/\', $class) . \'.php\';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Cargar archivos críticos explícitamente
if (file_exists(__DIR__ . \'/config/app.php\')) {
    require_once __DIR__ . \'/config/app.php\';
}

if (file_exists(__DIR__ . \'/config/database.php\')) {
    require_once __DIR__ . \'/config/database.php\';
}
';

if (file_put_contents($ROOT . '/autoload.php', $autoloadContent)) {
    echo "<p><span class='ok'>✓</span> Autoload actualizado correctamente</p>";
} else {
    echo "<p><span class='error'>✗</span> Error actualizando autoload</p>";
}

echo "</div>";

// ============================================
// PASO 7: Actualizar Bootstrap
// ============================================
echo "<div class='box'><h2>🔄 PASO 7: Actualizar Bootstrap</h2>";

$bootstrapContent = '<?php
/**
 * Bootstrap - HeiYuBai
 * Inicializa la aplicación
 */

error_reporting(E_ALL);
ini_set(\'display_errors\', 1);
ini_set(\'log_errors\', 1);

// Definir ruta raíz
if (!defined(\'ROOT_PATH\')) {
    define(\'ROOT_PATH\', dirname(__FILE__));
}

// Cargar autoloader
require_once ROOT_PATH . \'/autoload.php\';

// Cargar constantes
$constantsFile = ROOT_PATH . \'/config/constants.php\';
if (file_exists($constantsFile)) {
    require_once $constantsFile;
}

// Zona horaria
date_default_timezone_set(\'America/Bogota\');

// Cargar Config si existe
if (class_exists(\'Config\\\\Config\')) {
    Config\\Config::load();
}

// Iniciar sesión
if (class_exists(\'Core\\\\Session\')) {
    Core\\Session::start();
}

// Manejador de errores
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $error = "Error [$errno]: $errstr en $errfile:$errline";
    if (class_exists(\'Core\\\\Logger\')) {
        Core\\Logger::error($error);
    } else {
        error_log($error);
    }
    
    if (defined(\'APP_DEBUG\') && APP_DEBUG) {
        echo "<b>Error:</b> $errstr en <b>$errfile</b> línea <b>$errline</b><br>";
    }
    
    return true;
});

// Manejador de excepciones
set_exception_handler(function($exception) {
    $message = \'Excepción no capturada: \' . $exception->getMessage();
    
    if (class_exists(\'Core\\\\Logger\')) {
        Core\\Logger::critical($message, [
            \'file\' => $exception->getFile(),
            \'line\' => $exception->getLine(),
            \'trace\' => $exception->getTraceAsString()
        ]);
    } else {
        error_log($message);
    }
    
    if (defined(\'APP_DEBUG\') && APP_DEBUG) {
        echo "<h1>Excepción no capturada</h1>";
        echo "<p><b>Mensaje:</b> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><b>Archivo:</b> " . $exception->getFile() . "</p>";
        echo "<p><b>Línea:</b> " . $exception->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    } else {
        echo "Ha ocurrido un error. Por favor, contacta al administrador.";
    }
    
    exit(1);
});

// Shutdown handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error[\'type\'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (class_exists(\'Core\\\\Logger\')) {
            Core\\Logger::critical(\'Error fatal\', $error);
        }
        
        if (!defined(\'APP_DEBUG\') || !APP_DEBUG) {
            header(\'HTTP/1.1 500 Internal Server Error\');
            echo "Ha ocurrido un error fatal.";
        }
    }
});

// Headers de seguridad
if (class_exists(\'Core\\\\Response\')) {
    Core\\Response::setSecurityHeaders();
}
';

if (file_put_contents($ROOT . '/bootstrap.php', $bootstrapContent)) {
    echo "<p><span class='ok'>✓</span> Bootstrap actualizado correctamente</p>";
} else {
    echo "<p><span class='error'>✗</span> Error actualizando bootstrap</p>";
}

echo "</div>";

// ============================================
// PASO 8: Crear Modelos faltantes
// ============================================
echo "<div class='box'><h2>📦 PASO 8: Crear Modelos Faltantes</h2>";

// Mesa Model
$mesaModel = '<?php
namespace App\Models;

class Mesa {
    private $conn;
    private $table = "mesas";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodas() {
        $query = "SELECT m.*, 
                         p.numero_pedido as id_pedido,
                         p.estado,
                         CASE WHEN c.id_caja IS NOT NULL THEN 1 ELSE 0 END as pagado
                  FROM {$this->table} m
                  LEFT JOIN pedido p ON m.numero_mesa = p.mesa 
                       AND DATE(p.fecha) = CURDATE()
                       AND p.estado != \'cancelado\'
                  LEFT JOIN caja c ON p.numero_pedido = c.id_pedidoc
                  ORDER BY m.numero_mesa";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obtenerLibres() {
        $query = "SELECT m.* FROM {$this->table} m
                  WHERE m.numero_mesa NOT IN (
                      SELECT DISTINCT mesa FROM pedido 
                      WHERE DATE(fecha) = CURDATE() 
                      AND estado != \'cancelado\'
                      AND mesa IS NOT NULL
                  )
                  ORDER BY m.numero_mesa";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function liberar($numeroMesa) {
        $query = "UPDATE pedido SET estado = \'cancelado\' 
                  WHERE mesa = :mesa AND DATE(fecha) = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(\':mesa\', $numeroMesa);
        return $stmt->execute();
    }
}';

if (!file_exists($ROOT . '/app/Models/Mesa.php')) {
    file_put_contents($ROOT . '/app/Models/Mesa.php', $mesaModel);
    echo "<p><span class='ok'>✓</span> Creado: app/Models/Mesa.php</p>";
} else {
    echo "<p>• Ya existe: app/Models/Mesa.php</p>";
}

// Turno Model
$turnoModel = '<?php
namespace App\Models;

class Turno {
    private $conn;
    private $table = "turnero";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerPorTipo($tipoSolicitud, $fecha = null) {
        $fecha = $fecha ?: date(\'Y-m-d\');
        
        $query = "SELECT t.*, 
                         c.cliente, c.telefono, c.direccion, c.barrio,
                         CASE WHEN ca.id_caja IS NOT NULL THEN 1 ELSE 0 END as pagado,
                         CASE WHEN d.id_e IS NOT NULL THEN 1 ELSE 0 END as tiene_domiciliario
                  FROM {$this->table} t
                  LEFT JOIN clientes c ON t.id_cliente = c.id_cliente
                  LEFT JOIN caja ca ON t.id_pedido = ca.id_pedidoc
                  LEFT JOIN entrega d ON t.id_pedido = d.id_pede
                  WHERE t.tipo_solicitud = :tipo
                  AND DATE(t.fecha) = :fecha
                  ORDER BY t.turno DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(\':tipo\', $tipoSolicitud);
        $stmt->bindParam(\':fecha\', $fecha);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function actualizarEstado($numeroPedido, $nuevoEstado) {
        $query = "UPDATE {$this->table} SET estado = :estado WHERE id_pedido = :pedido";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(\':estado\', $nuevoEstado);
        $stmt->bindParam(\':pedido\', $numeroPedido);
        return $stmt->execute();
    }

    public function estaPagado($numeroPedido) {
        $query = "SELECT COUNT(*) FROM caja WHERE id_pedidoc = :pedido";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(\':pedido\', $numeroPedido);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function tieneDomiciliario($numeroPedido) {
        $query = "SELECT COUNT(*) FROM entrega WHERE id_pede = :pedido AND id_e IS NOT NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(\':pedido\', $numeroPedido);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}';

if (!file_exists($ROOT . '/app/Models/Turno.php')) {
    file_put_contents($ROOT . '/app/Models/Turno.php', $turnoModel);
    echo "<p><span class='ok'>✓</span> Creado: app/Models/Turno.php</p>";
} else {
    echo "<p>• Ya existe: app/Models/Turno.php</p>";
}

// Pedido Model
$pedidoModel = '<?php
namespace App\Models;

class Pedido {
    private $conn;
    private $table = "pedido";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerConDetalles($numeroPedido) {
        // Obtener info del pedido
        $query = "SELECT p.*, m.nombre_mese as nombre_mesero
                  FROM {$this->table} p
                  LEFT JOIN meseros m ON p.mesero = m.id_mese
                  WHERE p.numero_pedido = :pedido
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(\':pedido\', $numeroPedido);
        $stmt->execute();
        $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$pedido) return null;
        
        // Obtener productos
        $pedido[\'productos\'] = $this->getProductos($numeroPedido);
        $pedido[\'comentarios\'] = $this->getComentarios($numeroPedido);
        
        return $pedido;
    }

    public function getProductos($numeroPedido) {
        $query = "SELECT dp.*, pr.nombre_producto, pr.precio, pr.tipo_prod
                  FROM detalle_pedido dp
                  JOIN productos pr ON dp.id_pro = pr.id_pro
                  WHERE dp.numero_pedido = :pedido";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(\':pedido\', $numeroPedido);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getComentarios($numeroPedido) {
        $query = "SELECT comentario FROM comentarios WHERE numero_pedido = :pedido";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(\':pedido\', $numeroPedido);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getCostoDomicilio($numeroPedido) {
        $query = "SELECT costo_domicilio FROM domicilios WHERE id_pedido = :pedido";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(\':pedido\', $numeroPedido);
        $stmt->execute();
        return $stmt->fetchColumn() ?: 0;
    }

    public function actualizarEstado($numeroPedido, $nuevoEstado) {
        $query = "UPDATE {$this->table} SET estado = :estado WHERE numero_pedido = :pedido";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(\':estado\', $nuevoEstado);
        $stmt->bindParam(\':pedido\', $numeroPedido);
        return $stmt->execute();
    }

    public function estaPagado($numeroPedido) {
        $query = "SELECT COUNT(*) FROM caja WHERE id_pedidoc = :pedido";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(\':pedido\', $numeroPedido);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}';

if (!file_exists($ROOT . '/app/Models/Pedido.php')) {
    file_put_contents($ROOT . '/app/Models/Pedido.php', $pedidoModel);
    echo "<p><span class='ok'>✓</span> Creado: app/Models/Pedido.php</p>";
} else {
    echo "<p>• Ya existe: app/Models/Pedido.php</p>";
}

// Mesero Model
$meseroModel = '<?php
namespace App\Models;

class Mesero {
    private $conn;
    private $table = "meseros";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM {$this->table} ORDER BY nombre_mese";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM {$this->table} WHERE id_mese = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(\':id\', $id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function obtenerPorCodigo($codigo) {
        $query = "SELECT * FROM {$this->table} WHERE cod_mese = :codigo LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(\':codigo\', $codigo);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function validarCodigo($codigo) {
        return $this->obtenerPorCodigo($codigo) !== false;
    }
}';

if (!file_exists($ROOT . '/app/Models/Mesero.php')) {
    file_put_contents($ROOT . '/app/Models/Mesero.php', $meseroModel);
    echo "<p><span class='ok'>✓</span> Creado: app/Models/Mesero.php</p>";
} else {
    echo "<p>• Ya existe: app/Models/Mesero.php</p>";
}

echo "</div>";

// ============================================
// PASO 9: Crear .gitkeep
// ============================================
echo "<div class='box'><h2>📄 PASO 9: Crear .gitkeep</h2>";

$gitkeepDirs = ['storage/logs', 'storage/sessions', 'storage/cache'];
foreach ($gitkeepDirs as $dir) {
    $file = $ROOT . '/' . $dir . '/.gitkeep';
    if (!file_exists($file)) {
        file_put_contents($file, '');
        echo "<p><span class='ok'>✓</span> Creado: $dir/.gitkeep</p>";
    }
}

echo "</div>";

// ============================================
// VERIFICACIÓN FINAL
// ============================================
echo "<div class='box' style='border-color: #00ff88;'>";
echo "<h2>✅ INSTALACIÓN COMPLETADA</h2>";

echo "<h3>Verificación de archivos críticos:</h3>";
echo "<ul>";

$criticalFiles = [
    'autoload.php' => 'Autoloader',
    'bootstrap.php' => 'Bootstrap',
    'config/app.php' => 'Configuración',
    'config/constants.php' => 'Constantes',
    'config/database.php' => 'Base de datos',
    'Core/Session.php' => 'Sesión',
    'Core/Response.php' => 'Response',
    'Core/Logger.php' => 'Logger',
    'app/Controllers/AuthController.php' => 'AuthController',
    'app/Models/User.php' => 'User Model',
    'app/Models/Mesa.php' => 'Mesa Model',
    'app/Models/Turno.php' => 'Turno Model',
];

$allOk = true;
foreach ($criticalFiles as $file => $name) {
    $exists = file_exists($ROOT . '/' . $file);
    if ($exists) {
        echo "<li><span class='ok'>✓</span> $name ($file)</li>";
    } else {
        echo "<li><span class='error'>✗</span> $name ($file) - <strong>FALTA</strong></li>";
        $allOk = false;
    }
}

echo "</ul>";

if ($allOk) {
    echo "<p style='font-size: 18px;'><span class='ok'>✓</span> <strong>Todo listo!</strong></p>";
} else {
    echo "<p style='font-size: 18px;'><span class='error'>✗</span> <strong>Faltan archivos críticos</strong></p>";
}

echo "</div>";

// Enlaces
echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='views/login_new.php' class='btn btn-primary'>🔑 Ir al Login</a>";
echo "<a href='public/' class='btn btn-success'>🏠 Ir al Dashboard</a>";
echo "</div>";

echo "</body></html>";