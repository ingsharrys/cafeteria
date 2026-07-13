<?php

declare(strict_types=1);

$config = require __DIR__ . '/app/config/whatsapp.php';
require __DIR__ . '/app/controllers/WhatsAppWebhookController.php';

$controller = new WhatsAppWebhookController($config);
$controller->handle();