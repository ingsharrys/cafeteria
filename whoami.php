<?php

echo '<pre>';

$config = require __DIR__ . '/app/config/whatsapp.php';

echo "CONFIG phone_number_id: " . ($config['phone_number_id'] ?? 'NO') . PHP_EOL;
echo "CONFIG waba_id: " . ($config['waba_id'] ?? 'NO') . PHP_EOL;

echo '</pre>';