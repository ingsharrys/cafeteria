<?php
/**
 * DomiciliarioApiController
 * Maneja: CRUD domiciliarios (repartidores)
 * UBICACIÓN: heiyubai/DomiciliarioApiController.php
 */
class DomiciliarioApiController {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function handle(string $route, string $method): void {
        switch (true) {
            case ($route === 'domiciliarios/obtener' && $method === 'GET'):
                $this->obtener();
                break;
            case ($route === 'domiciliarios/agregar' && $method === 'POST'):
                $this->agregar();
                break;
            case ($route === 'domiciliarios/editar' && $method === 'POST'):
                $this->editar();
                break;
            case ($route === 'domiciliarios/eliminar' && $method === 'POST'):
                $this->eliminar();
                break;
            case ($route === 'domiciliarios/restaurar' && $method === 'POST'):
                $this->restaurar();
                break;
            default:
                http_response_code(404);
                echo json_encode(['success'=>false, 'error'=>"Domiciliarios: ruta no encontrada {$method} {$route}"]);
        }
    }

    private function obtener(): void {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if ($id) {
            $st = $this->db->prepare("SELECT id_e, repartidor, celu_reparti, calificacion FROM domiciliarios WHERE id_e = :id");
            $st->execute([':id' => $id]);
            $dom = $st->fetch(PDO::FETCH_ASSOC);
            echo json_encode($dom ? ['status'=>'success','domiciliarios'=>$dom] : ['status'=>'error','message'=>'No encontrado.']);
        } else {
            $doms = $this->db->query("SELECT * FROM domiciliarios WHERE elimina = 1")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status'=>'success', 'domiciliarios'=>$doms]);
        }
    }

    private function agregar(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $rep  = $data['repartidor'] ?? '';
        $cel  = $data['celu_reparti'] ?? '';
        $cal  = $data['calificacion'] ?? '';

        if (!$rep) { echo json_encode(['status'=>'error','message'=>'Nombre obligatorio.']); return; }

        try {
            $this->db->prepare("INSERT INTO domiciliarios (repartidor, celu_reparti, calificacion) VALUES (:r,:c,:cal)")
                ->execute([':r'=>$rep, ':c'=>$cel, ':cal'=>$cal]);
            echo json_encode(['status'=>'success', 'message'=>'Domiciliario agregado.']);
        } catch (PDOException $e) {
            echo json_encode(['status'=>'error', 'message'=>'Error: '.$e->getMessage()]);
        }
    }

    private function editar(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id   = (int)($data['id_e'] ?? 0);
        $rep  = $data['repartidor'] ?? '';
        $cel  = $data['celu_reparti'] ?? '';
        $cal  = $data['calificacion'] ?? '';

        if (!$id) { echo json_encode(['status'=>'error','message'=>'ID requerido.']); return; }

        try {
            $this->db->prepare("UPDATE domiciliarios SET repartidor=:r, celu_reparti=:c, calificacion=:cal WHERE id_e=:id")
                ->execute([':r'=>$rep, ':c'=>$cel, ':cal'=>$cal, ':id'=>$id]);
            echo json_encode(['status'=>'success', 'message'=>'Domiciliario actualizado.']);
        } catch (PDOException $e) {
            echo json_encode(['status'=>'error', 'message'=>'Error: '.$e->getMessage()]);
        }
    }

    private function eliminar(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id   = (int)($data['id_e'] ?? 0);
        if (!$id) { echo json_encode(['status'=>'error','message'=>'ID requerido.']); return; }

        try {
            $this->db->prepare("UPDATE domiciliarios SET elimina = 0 WHERE id_e = :id")->execute([':id'=>$id]);
            echo json_encode(['status'=>'success', 'message'=>'Domiciliario desactivado.']);
        } catch (PDOException $e) {
            echo json_encode(['status'=>'error', 'message'=>'Error: '.$e->getMessage()]);
        }
    }

    private function restaurar(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id   = (int)($data['id_e'] ?? 0);
        if (!$id) { echo json_encode(['status'=>'error','message'=>'ID requerido.']); return; }

        try {
            $this->db->prepare("UPDATE domiciliarios SET elimina = 1 WHERE id_e = :id")->execute([':id'=>$id]);
            echo json_encode(['status'=>'success', 'message'=>'Domiciliario restaurado.']);
        } catch (PDOException $e) {
            echo json_encode(['status'=>'error', 'message'=>'Error: '.$e->getMessage()]);
        }
    }
}