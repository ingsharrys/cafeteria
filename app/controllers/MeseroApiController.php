<?php
/**
 * MeseroApiController
 * UBICACIÓN: heiyubai/app/controllers/MeseroApiController.php
 * 
 * Maneja CRUD de meseros/colaboradores
 * Rutas:
 *   GET  /api.php?route=meseros/obtener&id_mese=X
 *   POST /api.php?route=meseros/agregar
 *   POST /api.php?route=meseros/editar
 *   DELETE /api.php?route=meseros/eliminar&id_mese=X
 */

class MeseroApiController {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function handle(string $route, string $method): void {
        switch (true) {
            case ($route === 'meseros/obtener' && $method === 'GET'):
                $this->obtener();
                break;
            case ($route === 'meseros/agregar' && $method === 'POST'):
                $this->agregar();
                break;
            case ($route === 'meseros/editar' && $method === 'POST'):
                $this->editar();
                break;
            case ($route === 'meseros/eliminar' && $method === 'DELETE'):
                $this->eliminar();
                break;
            default:
                http_response_code(404);
                echo json_encode(['success'=>false, 'error'=>"Meseros: ruta no encontrada {$method} {$route}"]);
        }
    }

    /**
     * GET /api.php?route=meseros/obtener&id_mese=X
     */
    private function obtener(): void {
        $id = filter_input(INPUT_GET, 'id_mese', FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success'=>false, 'message'=>'ID de mesero no proporcionado.']);
            return;
        }

        $st = $this->db->prepare("
            SELECT id_mese, nombre_mese, phon_mese, cedula_mese, cod_mese, cargo
            FROM meseros
            WHERE id_mese = :id
            LIMIT 1
        ");
        $st->execute([':id' => $id]);
        $mesero = $st->fetch(PDO::FETCH_ASSOC);

        if (!$mesero) {
            http_response_code(404);
            echo json_encode(['success'=>false, 'message'=>'Mesero no encontrado.']);
            return;
        }

        echo json_encode(['success'=>true, 'mesero'=>$mesero]);
    }

    /**
     * POST /api.php?route=meseros/agregar
     * Body: FormData con nombre_mese, phon_mese, cedula_mese, cargo, cod_mese
     */
    private function agregar(): void {
        $nombre  = $_POST['nombre_mese'] ?? null;
        $telefono= $_POST['phon_mese'] ?? null;
        $cedula  = $_POST['cedula_mese'] ?? null;
        $cargo   = $_POST['cargo_mese'] ?? null;
        $codigo  = $_POST['cod_mese'] ?? null;

        // Validación
        if (!$nombre || !$telefono || !$cedula || !$cargo || !$codigo) {
            http_response_code(400);
            echo json_encode(['success'=>false, 'message'=>'Todos los campos son obligatorios.']);
            return;
        }

        try {
            // Verificar duplicado por cédula
            $stCheck = $this->db->prepare("SELECT COUNT(*) FROM meseros WHERE cedula_mese = :ced");
            $stCheck->execute([':ced' => $cedula]);
            if ($stCheck->fetchColumn() > 0) {
                http_response_code(409);
                echo json_encode(['success'=>false, 'message'=>'Ya existe un mesero con esa cédula.']);
                return;
            }

            // Insertar
            $st = $this->db->prepare("
                INSERT INTO meseros (nombre_mese, phon_mese, cedula_mese, cargo, cod_mese)
                VALUES (:nombre, :telefono, :cedula, :cargo, :codigo)
            ");
            $st->execute([
                ':nombre'   => $nombre,
                ':telefono' => $telefono,
                ':cedula'   => $cedula,
                ':cargo'    => $cargo,
                ':codigo'   => $codigo
            ]);

            $idMesero = $this->db->lastInsertId();

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Colaborador agregado exitosamente.',
                'id' => $idMesero
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success'=>false, 'message'=>'Error: '.$e->getMessage()]);
        }
    }

    /**
     * POST /api.php?route=meseros/editar
     * Body: FormData con id_mese, nombre_mese, phon_mese, cedula_mese, cargo, cod_mese
     */
    private function editar(): void {
        $id      = $_POST['id_mese'] ?? null;
        $nombre  = $_POST['nombre_mese'] ?? null;
        $telefono= $_POST['phon_mese'] ?? null;
        $cedula  = $_POST['cedula_mese'] ?? null;
        $cargo   = $_POST['cargo_mese'] ?? null;
        $codigo  = $_POST['cod_mese'] ?? null;

        // Validación
        if (!$id || !$nombre || !$telefono || !$cedula || !$cargo || !$codigo) {
            http_response_code(400);
            echo json_encode(['success'=>false, 'message'=>'Todos los campos son obligatorios.']);
            return;
        }

        try {
            // Verificar que el mesero existe
            $stCheck = $this->db->prepare("SELECT COUNT(*) FROM meseros WHERE id_mese = :id");
            $stCheck->execute([':id' => $id]);
            if ($stCheck->fetchColumn() === 0) {
                http_response_code(404);
                echo json_encode(['success'=>false, 'message'=>'Mesero no encontrado.']);
                return;
            }

            // Verificar cédula duplicada (diferente mesero)
            $stCed = $this->db->prepare("
                SELECT COUNT(*) FROM meseros 
                WHERE cedula_mese = :ced AND id_mese != :id
            ");
            $stCed->execute([':ced' => $cedula, ':id' => $id]);
            if ($stCed->fetchColumn() > 0) {
                http_response_code(409);
                echo json_encode(['success'=>false, 'message'=>'Esa cédula ya está registrada en otro colaborador.']);
                return;
            }

            // Actualizar
            $st = $this->db->prepare("
                UPDATE meseros 
                SET nombre_mese = :nombre, 
                    phon_mese = :telefono, 
                    cedula_mese = :cedula, 
                    cargo = :cargo, 
                    cod_mese = :codigo
                WHERE id_mese = :id
            ");
            $st->execute([
                ':nombre'   => $nombre,
                ':telefono' => $telefono,
                ':cedula'   => $cedula,
                ':cargo'    => $cargo,
                ':codigo'   => $codigo,
                ':id'       => $id
            ]);

            http_response_code(200);
            echo json_encode(['success'=>true, 'message'=>'Colaborador actualizado exitosamente.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success'=>false, 'message'=>'Error: '.$e->getMessage()]);
        }
    }

    /**
     * DELETE /api.php?route=meseros/eliminar&id_mese=X
     */
    private function eliminar(): void {
        $id = filter_input(INPUT_GET, 'id_mese', FILTER_VALIDATE_INT);
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success'=>false, 'message'=>'ID de mesero inválido.']);
            return;
        }

        try {
            // Verificar que existe
            $stCheck = $this->db->prepare("SELECT COUNT(*) FROM meseros WHERE id_mese = :id");
            $stCheck->execute([':id' => $id]);
            if ($stCheck->fetchColumn() === 0) {
                http_response_code(404);
                echo json_encode(['success'=>false, 'message'=>'Mesero no encontrado.']);
                return;
            }

            // Eliminar
            $st = $this->db->prepare("DELETE FROM meseros WHERE id_mese = :id");
            $st->execute([':id' => $id]);

            http_response_code(200);
            echo json_encode(['success'=>true, 'message'=>'Colaborador eliminado exitosamente.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success'=>false, 'message'=>'Error: '.$e->getMessage()]);
        }
    }
}