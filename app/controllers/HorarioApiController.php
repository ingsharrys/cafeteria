<?php

declare(strict_types=1);

/**
 * HorarioApiController
 * Permite al panel definir la hora de apertura y cierre del servicio.
 *
 * Rutas (api.php?route=...):
 *   GET  horario   -> {apertura:"HH:MM", cierre:"HH:MM"}
 *   POST horario   -> {apertura, cierre}  guarda el horario
 *
 * El horario se guarda en la tabla menu_config (clave/valor), que el menú lee
 * para saber si está abierto.
 */
class HorarioApiController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        try {
            $this->db->exec(
                "CREATE TABLE IF NOT EXISTS menu_config (
                    clave VARCHAR(50) PRIMARY KEY,
                    valor VARCHAR(100) NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
            $this->db->exec(
                "INSERT IGNORE INTO menu_config (clave, valor)
                 VALUES ('hora_apertura', '05:00'), ('hora_cierre', '09:30')"
            );
        } catch (Throwable $e) {
            error_log('HorarioApiController.ensureTable: ' . $e->getMessage());
        }
    }

    public function handle(string $route, string $method): void
    {
        if (empty($_SESSION['cajero'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        if ($method === 'GET') {
            echo json_encode(['success' => true] + $this->actual());
            return;
        }

        if ($method === 'POST') {
            $this->guardar();
            return;
        }

        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Acción no encontrada']);
    }

    private function actual(): array
    {
        try {
            $rows = $this->db
                ->query("SELECT clave, valor FROM menu_config WHERE clave IN ('hora_apertura','hora_cierre')")
                ->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Throwable $e) {
            $rows = [];
        }
        return [
            'apertura' => $rows['hora_apertura'] ?? '05:00',
            'cierre'   => $rows['hora_cierre']   ?? '09:30',
        ];
    }

    private function guardar(): void
    {
        $data = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;

        $apertura = $this->normalizarHora($data['apertura'] ?? '');
        $cierre   = $this->normalizarHora($data['cierre'] ?? '');

        if ($apertura === null || $cierre === null) {
            echo json_encode(['success' => false, 'error' => 'Hora inválida. Usa el formato HH:MM.']);
            return;
        }

        $st = $this->db->prepare(
            "INSERT INTO menu_config (clave, valor) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE valor = :v2"
        );
        $st->execute([':k' => 'hora_apertura', ':v' => $apertura, ':v2' => $apertura]);
        $st->execute([':k' => 'hora_cierre',   ':v' => $cierre,   ':v2' => $cierre]);

        echo json_encode(['success' => true, 'apertura' => $apertura, 'cierre' => $cierre]);
    }

    private function normalizarHora($valor): ?string
    {
        if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', trim((string) $valor), $m)) {
            return null;
        }
        return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
    }
}
