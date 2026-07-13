<?php
/**
 * CatalogoApiController
 * Maneja: CRUD de productos del catálogo (con precios)
 * UBICACIÓN: heiyubai/CatalogoApiController.php
 */
class CatalogoApiController {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function handle(string $route, string $method): void {
        switch (true) {
            case ($route === 'catalogo/obtener' && $method === 'GET'):
                $this->obtener();
                break;
            case ($route === 'catalogo/agregar' && $method === 'POST'):
                $this->agregar();
                break;
            case ($route === 'catalogo/editar' && $method === 'POST'):
                $this->editar();
                break;
            case ($route === 'catalogo/eliminar' && $method === 'POST'):
                $this->eliminar();
                break;
            default:
                http_response_code(404);
                echo json_encode(['success'=>false, 'error'=>"Catalogo: ruta no encontrada {$method} {$route}"]);
        }
    }

    // ───────────────────────────────────────────────
    // GET /api.php?route=catalogo/obtener&id=X
    // ───────────────────────────────────────────────
    private function obtener(): void {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            echo json_encode(['status'=>'error', 'message'=>'ID de producto no proporcionado.']);
            return;
        }

        $st = $this->db->prepare("
            SELECT p.id_pro, p.nombre, p.prefijo, p.cat, p.descript, p.img, p.tcomida,
                   GROUP_CONCAT(pr.tipo_prod) AS tipos_producto,
                   GROUP_CONCAT(pr.precio) AS precios
            FROM productos p
            LEFT JOIN precios pr ON p.id_pro = pr.idproduc
            WHERE p.id_pro = :id
            GROUP BY p.id_pro
        ");
        $st->execute([':id' => $id]);
        $prod = $st->fetch(PDO::FETCH_ASSOC);

        if (!$prod) {
            echo json_encode(['status'=>'error', 'message'=>'Producto no encontrado.']);
            return;
        }

        $tipos   = !empty($prod['tipos_producto']) ? explode(',', $prod['tipos_producto']) : [];
        $precios = !empty($prod['precios']) ? explode(',', $prod['precios']) : [];
        $count   = min(count($tipos), count($precios));

        $prod['tipos_precios'] = [];
        for ($i = 0; $i < $count; $i++) {
            $prod['tipos_precios'][] = ['tipo_prod'=>$tipos[$i], 'precio'=>$precios[$i]];
        }
        $prod['tipos_producto'] = $tipos;
        $prod['precios']        = $precios;

        echo json_encode(['status'=>'success', 'producto'=>$prod]);
    }

    // ───────────────────────────────────────────────
    // POST /api.php?route=catalogo/agregar (FormData)
    // ───────────────────────────────────────────────
    private function agregar(): void {
        $nombre  = $_POST['nombre'] ?? null;
        $prefijo = $_POST['prefijo'] ?? null;
        $cat     = $_POST['cat'] ?? null;
        $descript= $_POST['descript'] ?? '';
        $tcomida = $_POST['tcomida'] ?? null;
        $tipos   = $_POST['tipo_producto'] ?? [];
        $precios = $_POST['precio_producto'] ?? [];
        $img     = $_FILES['img'] ?? null;

        if (!$nombre || !$prefijo || !$cat) {
            echo json_encode(['status'=>'error', 'message'=>'Nombre, prefijo y categoría son obligatorios.']);
            return;
        }

        $imgName = $this->procesarImagen($img);

        try {
            // Duplicado
            $stChk = $this->db->prepare("SELECT COUNT(*) FROM productos WHERE nombre = :n");
            $stChk->execute([':n' => $nombre]);
            if ($stChk->fetchColumn() > 0) {
                echo json_encode(['status'=>'error', 'message'=>'El producto ya existe.']);
                return;
            }

            $this->db->beginTransaction();

            $st = $this->db->prepare("INSERT INTO productos (nombre, prefijo, descript, cat, tcomida, img) VALUES (:n,:p,:d,:c,:t,:i)");
            $st->execute([':n'=>$nombre, ':p'=>$prefijo, ':d'=>$descript, ':c'=>$cat, ':t'=>$tcomida, ':i'=>$imgName]);
            $idProd = $this->db->lastInsertId();

            $this->sincronizarPrecios($idProd, $tipos, $precios);

            $this->db->commit();
            echo json_encode(['status'=>'success', 'message'=>'Producto agregado exitosamente.', 'id'=>$idProd]);
        } catch (PDOException $e) {
            $this->db->rollBack();
            echo json_encode(['status'=>'error', 'message'=>'Error: '.$e->getMessage()]);
        }
    }

    // ───────────────────────────────────────────────
    // POST /api.php?route=catalogo/editar (FormData)
    // ───────────────────────────────────────────────
    private function editar(): void {
        $id      = $_POST['id_pro'] ?? null;
        $nombre  = $_POST['nombre'] ?? '';
        $prefijo = $_POST['prefijo'] ?? '';
        $cat     = $_POST['cat'] ?? '';
        $descript= $_POST['descript'] ?? '';
        $tcomida = $_POST['tcomida'] ?? null;
        $tipos   = $_POST['tipos'] ?? [];
        $precios = $_POST['precios'] ?? [];
        $img     = $_FILES['img'] ?? null;

        if (!$id) {
            echo json_encode(['status'=>'error', 'message'=>'ID de producto requerido.']);
            return;
        }

        try {
            $imgName = null;
            if ($img && $img['error'] === UPLOAD_ERR_OK) {
                $imgName = $this->procesarImagen($img);
                $this->eliminarImagenAnterior($id);
            }

            $query = "UPDATE productos SET nombre=:n, prefijo=:p, cat=:c, descript=:d, tcomida=:t";
            $params = [':n'=>$nombre, ':p'=>$prefijo, ':c'=>$cat, ':d'=>$descript, ':t'=>$tcomida, ':id'=>$id];
            if ($imgName) { $query .= ", img=:i"; $params[':i'] = $imgName; }
            $query .= " WHERE id_pro=:id";

            $this->db->prepare($query)->execute($params);

            // Sincronizar variantes de precio
            $stExist = $this->db->prepare("SELECT tipo_prod FROM precios WHERE idproduc = :id");
            $stExist->execute([':id' => $id]);
            $existentes = $stExist->fetchAll(PDO::FETCH_COLUMN);
            $nuevos     = array_map('strval', $tipos);

            // Eliminar los que ya no están
            $borrar = array_diff($existentes, $nuevos);
            if (!empty($borrar)) {
                $stDel = $this->db->prepare("DELETE FROM precios WHERE idproduc = :id AND tipo_prod = :t");
                foreach ($borrar as $v) { $stDel->execute([':id'=>$id, ':t'=>$v]); }
            }

            // Upsert
            foreach ($tipos as $i => $tipo) {
                if (empty($tipo) || !isset($precios[$i])) continue;
                $precio = (int)$precios[$i];

                if (in_array($tipo, $existentes)) {
                    $this->db->prepare("UPDATE precios SET precio=:p WHERE idproduc=:id AND tipo_prod=:t")
                        ->execute([':p'=>$precio, ':id'=>$id, ':t'=>$tipo]);
                } else {
                    $this->db->prepare("INSERT INTO precios (idproduc, tipo_prod, precio) VALUES (:id, :t, :p)")
                        ->execute([':id'=>$id, ':t'=>$tipo, ':p'=>$precio]);
                }
            }

            echo json_encode(['status'=>'success', 'message'=>'Producto editado con éxito.']);
        } catch (PDOException $e) {
            echo json_encode(['status'=>'error', 'message'=>'Error: '.$e->getMessage()]);
        }
    }

    // ───────────────────────────────────────────────
    // POST /api.php?route=catalogo/eliminar (JSON)
    // ───────────────────────────────────────────────
    private function eliminar(): void {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($data['id_pro'] ?? 0);

        if (!$id) {
            echo json_encode(['status'=>'error', 'message'=>'ID de producto inválido.']);
            return;
        }

        try {
            $this->db->beginTransaction();
            $this->eliminarImagenAnterior($id);
            $this->db->prepare("DELETE FROM precios WHERE idproduc = :id")->execute([':id'=>$id]);
            $this->db->prepare("DELETE FROM productos WHERE id_pro = :id")->execute([':id'=>$id]);
            $this->db->commit();
            echo json_encode(['status'=>'success', 'message'=>'Producto eliminado.']);
        } catch (PDOException $e) {
            $this->db->rollBack();
            echo json_encode(['status'=>'error', 'message'=>'Error: '.$e->getMessage()]);
        }
    }

    // ═══════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════

    private function procesarImagen($img): string {
        if (!$img || $img['error'] !== UPLOAD_ERR_OK) return 'sin-imagen.jpg';

        $filename = preg_replace("/[^a-zA-Z0-9._-]/", "", pathinfo($img['name'], PATHINFO_FILENAME));
        $ext      = pathinfo($img['name'], PATHINFO_EXTENSION);
        $imgName  = 'producto-'.$filename.'-'.uniqid().'.'.$ext;
        $imgDir   = dirname(__DIR__) . '/path/to/productos/';
        if (!is_dir($imgDir)) @mkdir($imgDir, 0755, true);
        move_uploaded_file($img['tmp_name'], $imgDir.$imgName);
        return $imgName;
    }

    private function eliminarImagenAnterior(int $id): void {
        $st = $this->db->prepare("SELECT img FROM productos WHERE id_pro = :id");
        $st->execute([':id' => $id]);
        $oldImg = $st->fetchColumn();
        if ($oldImg && $oldImg !== 'sin-imagen.jpg') {
            $path = dirname(__DIR__).'/path/to/productos/'.$oldImg;
            if (file_exists($path)) @unlink($path);
        }
    }

    private function sincronizarPrecios(int $idProd, array $tipos, array $precios): void {
        $st = $this->db->prepare("INSERT INTO precios (idproduc, tipo_prod, precio) VALUES (:id, :t, :p)");
        foreach ($tipos as $i => $tipo) {
            if (empty($tipo) || !isset($precios[$i])) continue;
            $st->execute([':id'=>$idProd, ':t'=>$tipo, ':p'=>(int)$precios[$i]]);
        }
    }
}