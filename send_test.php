<?php

$config = require __DIR__ . '/app/config/whatsapp.php';
require __DIR__ . '/app/services/WhatsAppCloudApiService.php';

$service = new WhatsAppCloudApiService($config);

// Reemplaza por tu número en formato internacional. Ej: 573001234567
$to = '573001234567';

$result = $service->sendText($to, 'Hola bro, prueba desde WhatsApp Cloud API');

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);