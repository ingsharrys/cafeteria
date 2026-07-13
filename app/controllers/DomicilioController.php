<?php
/**
 * DomicilioController.php - Controlador
 * Gestiona las operaciones de domicilios
 * 
 * Endpoints:
 * - GET /api/domicilios/obtener-domiciliarios
 * - GET /api/domicilios/obtener-por-pedido?id_pedido=X
 * - POST /api/domicilios/crear
 * - POST /api/domicilios/actualizar-domiciliario
 * - POST /api/domicilios/actualizar-precio
 * - POST /api/domicilios/actualizar
 */

require_once __DIR__ . '/../models/Domiciliario.php';
require_once __DIR__ . '/../models/Domicilio.php';
require_once __DIR__ . '/EnviarMensajeWhatsApp.php';

class DomicilioController {
    private $domiciliario;
    private $domicilio;
    private $db;

    public function __construct($database) {
        $this->db = $database->getConnection();
        $this->domiciliario = new Domiciliario($this->db);
        $this->domicilio = new Domicilio($this->db);
    }

    /**
     * ✅ Obtener lista de domiciliarios disponibles
     * GET: /api/domicilios/obtener-domiciliarios
     */
    public function obtenerDomiciliarios() {
        header('Content-Type: application/json');
        
        $resultado = $this->domiciliario->obtenerJSON();
        echo json_encode($resultado);
    }

    /**
     * ✅ Obtener datos del domicilio por ID de pedido
     * GET: /api/domicilios/obtener-por-pedido?id_pedido=123
     */
    public function obtenerPorPedido() {
        header('Content-Type: application/json');
        
        $id_pedido = isset($_GET['id_pedido']) ? intval($_GET['id_pedido']) : null;

        if (!$id_pedido) {
            echo json_encode([
                'status' => 'error',
                'message' => 'ID del pedido no proporcionado'
            ]);
            return;
        }

        $domicilio = $this->domicilio->obtenerPorIdPedido($id_pedido);

        if ($domicilio) {
            echo json_encode([
                'status' => 'success',
                'data' => $domicilio
            ]);
        } else {
            echo json_encode([
                'status' => 'no_data',
                'message' => 'No hay domicilio registrado para este pedido'
            ]);
        }
    }

    /**
     * ✅ Crear nuevo domicilio (INSERT)
     * POST: /api/domicilios/crear
     * Body: { id_pedido, id_domi (opcional), precio (opcional) }
     */
    public function crear() {
        header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents('php://input'), true);

        // Validaciones
        if (!isset($data['id_pedido'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'ID del pedido es requerido'
            ]);
            return;
        }

        $id_pedido = intval($data['id_pedido']);
        $id_domi = isset($data['id_domi']) ? intval($data['id_domi']) : null;
        $precio = isset($data['precio']) ? $data['precio'] : null;

        // Verificar que al menos uno de los campos se proporciona
        if ($id_domi === null && $precio === null) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Debe proporcionar al menos un domiciliario o un precio'
            ]);
            return;
        }

        // Verificar que el pedido no tenga ya un domicilio
        if ($this->domicilio->existePara($id_pedido)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Este pedido ya tiene un domicilio asignado'
            ]);
            return;
        }

        $resultado = $this->domicilio->crear($id_pedido, $id_domi, $precio);
        echo json_encode($resultado);
    }

    /**
     * ✅ Actualizar domiciliario (id_domi)
     * POST: /api/domicilios/actualizar-domiciliario
     * Body: { id_pedido, id_domi }
     */
    public function actualizarDomiciliario() {
        header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id_pedido']) || !isset($data['id_domi'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'ID del pedido e ID del domiciliario son requeridos'
            ]);
            return;
        }

        $id_pedido = intval($data['id_pedido']);
        $id_domi = intval($data['id_domi']);

        // Verificar que el domiciliario existe
        $domiciliario = $this->domiciliario->obtenerPorId($id_domi);
        if (!$domiciliario) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Domiciliario no encontrado'
            ]);
            return;
        }

        // Si no existe domicilio para este pedido, crear uno
        if (!$this->domicilio->existePara($id_pedido)) {
            $resultado = $this->domicilio->crear($id_pedido, $id_domi, null);
        } else {
            $resultado = $this->domicilio->actualizarDomiciliario($id_pedido, $id_domi);
        }
        
        // 🆕 ENVIAR WHATSAPP AL CLIENTE
        if ($resultado['status'] === 'success') {
            try {
                // 1. Obtener teléfono del cliente
                $sqlWA = "SELECT c.celular 
                          FROM clientes c
                          INNER JOIN turnero t ON c.id = t.id_cliente 
                          WHERE t.id_pedido = :id_pedido LIMIT 1";
                
                $stmtWA = $this->db->prepare($sqlWA);
                $stmtWA->execute([':id_pedido' => $id_pedido]);
                $datosWA = $stmtWA->fetch(PDO::FETCH_ASSOC);
                        if ($datosWA && !empty($datosWA['celular'])) {
                            // 2. Preparar teléfono
                            $numeroBase = preg_replace('/[^\d]/', '', trim($datosWA['celular'] ?? ''));
                            $telefono = $numeroBase;
                        
                            if (strlen($telefono) === 10) {
                                $telefono = '57' . $telefono;
                            }
                        
                            // 3. Definir plantilla
                            $plantilla = 'domiciliarioheiyu';
                        
                            // 4. Parámetro dinámico del botón URL
                            $parametroBoton = $numeroBase;
                        
                            if (!empty($telefono)) {
                                // 5. ENVIAR WHATSAPP
                                $resultadoWA = EnviarMensajeWhatsApp::enviar(
                                    $telefono,
                                    $plantilla,
                                    'es_CO',
                                    $parametroBoton
                                );
                        
                                // 6. LOGUEAR RESULTADO
                                try {
                                    $sqlInsert = "INSERT INTO whatsapp_log 
                                                  (numero_pedido, estado_pedido, celular, plantilla, idioma, estado_mensaje)
                                                  VALUES (:numero_pedido, :estado_pedido, :celular, :plantilla, :idioma, 'pendiente')";
                                    
                                    $stmtInsert = $this->db->prepare($sqlInsert);
                                    $stmtInsert->execute([
                                        ':numero_pedido' => $id_pedido,
                                        ':estado_pedido' => 'domiciliario_asignado',
                                        ':celular' => $telefono,
                                        ':plantilla' => $plantilla,
                                        ':idioma' => 'es_CO'
                                    ]);
                                    
                                    $logId = $this->db->lastInsertId();
                        
                                    if ($resultadoWA['success']) {
                                        $sqlUpdate = "UPDATE whatsapp_log 
                                                      SET estado_mensaje = 'enviado', resultado_exito = 1,
                                                          message_id = :message_id, http_code = :http_code
                                                      WHERE id = :id";
                                        
                                        $stmtUpdate = $this->db->prepare($sqlUpdate);
                                        $stmtUpdate->execute([
                                            ':message_id' => $resultadoWA['message_id'] ?? '',
                                            ':http_code' => $resultadoWA['http_code'] ?? 200,
                                            ':id' => $logId
                                        ]);
                                    } else {
                                        $sqlUpdate = "UPDATE whatsapp_log 
                                                      SET estado_mensaje = 'error', resultado_exito = 0,
                                                          mensaje_error = :mensaje_error, http_code = :http_code
                                                      WHERE id = :id";
                                        
                                        $stmtUpdate = $this->db->prepare($sqlUpdate);
                                        $stmtUpdate->execute([
                                            ':mensaje_error' => $resultadoWA['error'] ?? 'Error',
                                            ':http_code' => $resultadoWA['http_code'] ?? 0,
                                            ':id' => $logId
                                        ]);
                                    }
                                } catch (Throwable $e) {
                                    error_log("Error insertando en whatsapp_log: " . $e->getMessage());
                                }
                            }
                        }
            } catch (Throwable $e) {
                error_log("Error enviando WhatsApp domiciliario: " . $e->getMessage());
            }
        }

        echo json_encode($resultado);
    }

    /**
     * ✅ Actualizar precio del domicilio
     * POST: /api/domicilios/actualizar-precio
     * Body: { id_pedido, precio }
     */
    public function actualizarPrecio() {
        header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id_pedido']) || !isset($data['precio'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'ID del pedido y precio son requeridos'
            ]);
            return;
        }

        $id_pedido = intval($data['id_pedido']);
        $precio = floatval($data['precio']);

        if ($precio < 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'El precio no puede ser negativo'
            ]);
            return;
        }

        // Si no existe domicilio para este pedido, crear uno
        if (!$this->domicilio->existePara($id_pedido)) {
            $resultado = $this->domicilio->crear($id_pedido, null, $precio);
        } else {
            $resultado = $this->domicilio->actualizarPrecio($id_pedido, $precio);
        }

        echo json_encode($resultado);
    }

    /**
     * ✅ Actualizar ambos campos
     * POST: /api/domicilios/actualizar
     * Body: { id_pedido, id_domi (opcional), precio (opcional) }
     */
    public function actualizar() {
        header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id_pedido'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'ID del pedido es requerido'
            ]);
            return;
        }

        $id_pedido = intval($data['id_pedido']);
        $id_domi = isset($data['id_domi']) ? intval($data['id_domi']) : null;
        $precio = isset($data['precio']) ? $data['precio'] : null;

        if ($id_domi === null && $precio === null) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Debe proporcionar al menos un campo a actualizar'
            ]);
            return;
        }

        $resultado = $this->domicilio->actualizar($id_pedido, $id_domi, $precio);
        echo json_encode($resultado);
    }
}

// ═══════════════════════════════════════════════════════
// ENRUTAMIENTO
// ═══════════════════════════════════════════════════════

// Detectar qué acción se solicita
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Cargar Database
require_once __DIR__ . '/../../config/database.php';
$database = Database::getInstance();

// Instanciar controlador
$controller = new DomicilioController($database);

// Enrutar según la acción
switch ($action) {
    case 'obtener-domiciliarios':
        $controller->obtenerDomiciliarios();
        break;
    
    case 'obtener-por-pedido':
        $controller->obtenerPorPedido();
        break;
    
    case 'crear':
        $controller->crear();
        break;
    
    case 'actualizar-domiciliario':
        $controller->actualizarDomiciliario();
        break;
    
    case 'actualizar-precio':
        $controller->actualizarPrecio();
        break;
    
    case 'actualizar':
        $controller->actualizar();
        break;
    
    default:
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Acción no especificada o no válida'
        ]);
        break;
}