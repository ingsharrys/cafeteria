<?php
namespace App\Controllers;
use Core\Response;
use Core\Session;
use Core\Logger;

class MeseroController {
    private $conn;
    
    public function __construct() {
        $db = \Database::getInstance();
        $this->conn = $db->getConnection();
    }
    
    public function validarCodigo() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Response::jsonError('Método no permitido', [], 405);
                return;
            }
            
            $codigo = trim($_POST['codigo'] ?? '');
            if (empty($codigo)) {
                Response::jsonError('Código no proporcionado', [], 400);
                return;
            }
            
            $query = "SELECT id_mese, nombre_mese, cargo FROM meseros WHERE cod_mese = :codigo LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':codigo' => $codigo]);
            
            if ($stmt->rowCount() > 0) {
                $mesero = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                Session::set('usuario', [
                    'cajero' => $mesero['nombre_mese'],
                    'rol' => $mesero['cargo'] ?? 'usuario',
                    'id_mese' => $mesero['id_mese']
                ]);
                Session::set('cajero', $mesero['nombre_mese']);
                
                if ($codigo == '4587') {
                    Session::set('registro_acceso', true);
                }
                
                Logger::info("Colaborador validado: {$mesero['nombre_mese']}");
                
                Response::json([
                    'status' => 'success',
                    'message' => "Bienvenido(a) {$mesero['nombre_mese']}."
                ]);
            } else {
                Response::json(['status' => 'error', 'message' => 'Código incorrecto']);
            }
        } catch (\Exception $e) {
            Logger::error('Error: ' . $e->getMessage());
            Response::jsonError('Error', [], 500);
        }
    }
    
    public function index() {
        try {
            $query = "SELECT * FROM meseros ORDER BY nombre_mese";
            $stmt = $this->conn->query($query);
            $meseros = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            Response::json(['success' => true, 'meseros' => $meseros]);
        } catch (\Exception $e) {
            Logger::error('Error: ' . $e->getMessage());
            Response::jsonError('Error', [], 500);
        }
    }
}