<?php

declare(strict_types=1);

/**
 * ClienteApiController
 * API del panel para aprobar/gestionar clientes (evitar pedidos de broma).
 *
 * Rutas (api.php?route=...):
 *   GET  clientes                 -> lista de clientes (?estado=pendientes|aprobados|todos, ?buscar=)
 *   POST clientes/aprobar         -> {id}  marca aprobado = 1
 *   POST clientes/rechazar        -> {id}  marca aprobado = 0
 */
class ClienteApiController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->ensureColumn();
    }

    /** Crea la columna `aprobado` en la tabla clientes si no existe. */
    private function ensureColumn(): void
    {
        try {
            $col = $this->db->query("SHOW COLUMNS FROM clientes LIKE 'aprobado'")->fetch();
            if (!$col) {
                $this->db->exec("ALTER TABLE clientes ADD COLUMN aprobado TINYINT(1) NOT NULL DEFAULT 0");
                // Los clientes que ya existían (ya pidieron antes) se aprueban
                // automáticamente; solo los nuevos quedarán pendientes.
                $this->db->exec("UPDATE clientes SET aprobado = 1");
            }
        } catch (Throwable $e) {
            error_log('ClienteApiController.ensureColumn: ' . $e->getMessage());
        }
    }

    public function handle(string $route, string $method): void
    {
        // Solo usuarios autenticados en el panel
        if (empty($_SESSION['cajero'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        if ($method === 'GET') {
            $this->listar();
            return;
        }

        if ($method === 'POST') {
            $data = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
            $id   = (int) ($data['id'] ?? 0);

            if (str_ends_with($route, 'aprobar')) {
                $this->setAprobado($id, 1);
                return;
            }
            if (str_ends_with($route, 'rechazar')) {
                $this->setAprobado($id, 0);
                return;
            }
        }

        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Acción no encontrada']);
    }

    private function listar(): void
    {
        $estado = $_GET['estado'] ?? 'todos';
        $buscar = trim((string) ($_GET['buscar'] ?? ''));

        $where  = [];
        $params = [];

        if ($estado === 'pendientes') {
            $where[] = 'COALESCE(aprobado,0) = 0';
        } elseif ($estado === 'aprobados') {
            $where[] = 'COALESCE(aprobado,0) = 1';
        }

        if ($buscar !== '') {
            $where[] = '(cliente LIKE :b OR celular LIKE :b)';
            $params[':b'] = '%' . $buscar . '%';
        }

        $sql = "SELECT id, cliente, celular, direccion, barrio, cedula, COALESCE(aprobado,0) AS aprobado
                FROM clientes";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY COALESCE(aprobado,0) ASC, id DESC LIMIT 500';

        $st = $this->db->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'clientes' => $rows]);
    }

    private function setAprobado(int $id, int $val): void
    {
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            return;
        }

        $st = $this->db->prepare('UPDATE clientes SET aprobado = :v WHERE id = :id');
        $st->execute([':v' => $val, ':id' => $id]);

        echo json_encode(['success' => true, 'id' => $id, 'aprobado' => $val]);
    }
}
