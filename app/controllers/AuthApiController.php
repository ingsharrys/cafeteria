<?php
/**
 * AuthApiController - Autenticación vía API
 * UBICACIÓN: heiyubai/app/controllers/AuthApiController.php
 * 
 * Rutas:
 *   POST auth/validar_codigo  → Valida código mesero, asigna cajero/rol/permisos en sesión
 *   GET  auth/session_status  → Devuelve estado actual de sesión (debug)
 * 
 * FLUJO:
 * 1. Usuario hace login (AuthController) → solo user_id en sesión
 * 2. Dashboard carga → footer detecta que NO hay cajero → muestra modal
 * 3. Modal envía código → POST auth/validar_codigo
 * 4. Este controller valida contra tabla meseros
 * 5. Si válido: busca rol en tabla roles (por cargo del mesero)
 * 6. Guarda cajero, usuario, rol_nombre, paginas_permitidas en sesión
 * 7. Modal cierra, página recarga, header muestra menú correcto
 */
class AuthApiController
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function handle(string $route, string $method): void
    {
        switch ($route) {
            case 'auth/validar_codigo':
                if ($method === 'POST') {
                    $this->validarCodigo();
                } else {
                    http_response_code(405);
                    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
                }
                break;

            case 'auth/session_status':
                $this->sessionStatus();
                break;

            default:
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => "Ruta auth no encontrada: {$route}"]);
        }
    }

    /**
     * POST auth/validar_codigo
     * Body: codigo=XXXX (x-www-form-urlencoded)
     * 
     * Valida el código del mesero y establece toda la info de sesión:
     * - cajero (nombre del mesero)
     * - usuario (array con cajero, rol, id_mese)
     * - rol_nombre (nombre del rol)
     * - paginas_permitidas (array de páginas)
     */
    private function validarCodigo(): void
    {
        $codigo = trim($_POST['codigo'] ?? '');

        if (empty($codigo)) {
            echo json_encode(['status' => 'error', 'message' => 'Código no proporcionado']);
            return;
        }

        try {
            // 1. Buscar mesero por código
            $stmt = $this->db->prepare("
                SELECT id_mese, nombre_mese, cargo 
                FROM meseros 
                WHERE cod_mese = :codigo 
                LIMIT 1
            ");
            $stmt->execute([':codigo' => $codigo]);
            $mesero = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$mesero) {
                echo json_encode(['status' => 'error', 'message' => 'Código incorrecto']);
                return;
            }

            // 2. Buscar rol en tabla roles (por nombre de cargo del mesero)
            $cargo = $mesero['cargo'] ?? 'default';
            $paginas_permitidas = [];
            $rol_nombre = $cargo;

            $stmtRol = $this->db->prepare("
                SELECT nombre, paginas_permitidas 
                FROM roles 
                WHERE nombre = :cargo 
                LIMIT 1
            ");
            $stmtRol->execute([':cargo' => $cargo]);
            $rol = $stmtRol->fetch(PDO::FETCH_ASSOC);

            if ($rol) {
                $rol_nombre = $rol['nombre'];
                $paginas_permitidas = json_decode($rol['paginas_permitidas'] ?? '[]', true) ?: [];
            }

            // 3. Guardar en sesión (misma sesión que public/index.php)
            $_SESSION['cajero'] = $mesero['nombre_mese'];
            $_SESSION['usuario'] = [
                'cajero'  => $mesero['nombre_mese'],
                'rol'     => $cargo,
                'id_mese' => $mesero['id_mese']
            ];
            $_SESSION['rol_nombre'] = $rol_nombre;
            $_SESSION['paginas_permitidas'] = $paginas_permitidas;

            // 4. Código especial para acceso a registro
            if ($codigo == '4587') {
                $_SESSION['registro_acceso'] = true;
            }

            error_log("[AUTH] Código validado - Cajero: {$mesero['nombre_mese']}, Rol: {$rol_nombre}, Session: " . session_id());

            echo json_encode([
                'status'  => 'success',
                'message' => "Bienvenido(a) {$mesero['nombre_mese']}.",
                'cajero'  => $mesero['nombre_mese'],
                'rol'     => $rol_nombre
            ]);

        } catch (PDOException $e) {
            error_log("[AUTH] Error en validarCodigo: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error de base de datos']);
        }
    }

    /**
     * GET auth/session_status
     * Devuelve el estado actual de la sesión (útil para debug)
     */
    private function sessionStatus(): void
    {
        echo json_encode([
            'status'     => 'success',
            'session_id' => session_id(),
            'user_id'    => $_SESSION['user_id'] ?? null,
            'cajero'     => $_SESSION['cajero'] ?? null,
            'rol_nombre' => $_SESSION['rol_nombre'] ?? null,
            'has_permisos' => !empty($_SESSION['paginas_permitidas']),
        ]);
    }
}