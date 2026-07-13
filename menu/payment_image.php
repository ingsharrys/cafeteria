<?php
// Devuelve JSON con la URL de la imagen de pago asociada a un pedido (si existe)
$numero = $_GET['numero_pedido'] ?? $_GET['numero'] ?? null;
header('Content-Type: application/json');
if (!$numero) {
    echo json_encode(['status'=>'error','message'=>'numero_pedido requerido']);
    exit;
}

// __DIR__ es menu/, las imágenes se guardan en la carpeta public/ del sitio
$storageDir = __DIR__ . '/../public/img/payments/';
$webDir = '/public/img/payments/';

$found = glob($storageDir . $numero . '.*');
if ($found && count($found) > 0) {
    $file = $found[0];
    $url = $webDir . basename($file);
    echo json_encode(['status'=>'ok','url'=>$url]);
    exit;
}

echo json_encode(['status'=>'not_found']);
