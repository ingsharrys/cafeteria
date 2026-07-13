<?php
// Rutas correctas usando __DIR__
require_once dirname(__DIR__, 2) . '/config/database.php';

// O más explícito:
// require_once dirname(dirname(__DIR__)) . '/config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $conn = $database->getConnection();
    
    $fecha = $_POST["fecha"] ?? date('Y-m-d');
    $concepto = $_POST["concepto"] ?? '';
    $categoria = $_POST["categoria"] ?? 'gastos_varios';
    $monto = $_POST["monto"] ?? 0;
    $cajero = $_POST["cajero"] ?? 'admin';
    $id_mesero = isset($_POST["mesero"]) && !empty($_POST["mesero"]) ? $_POST["mesero"] : null;
    
    try {
        if ($id_mesero) {
            $sql = "INSERT INTO gastos (fecha, concepto, categoria, monto, cajero, id_mesero) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$fecha, $concepto, $categoria, $monto, $cajero, $id_mesero]);
        } else {
            $sql = "INSERT INTO gastos (fecha, concepto, categoria, monto, cajero) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$fecha, $concepto, $categoria, $monto, $cajero]);
        }
        
        // Redirigir a gastos.php (está en public/)
        header("Location: ../../public/index.php?page=gastos.php");
        exit();
        
    } catch (PDOException $e) {
        die("Error al guardar el gasto: " . $e->getMessage());
    }
} else {
    // Si no es POST, redirige también
    header("Location: ../../public/index.php?page=gastos.php");
    exit();
}
?>