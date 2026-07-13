<?php

declare(strict_types=1);

/**
 * Configuración de WhatsApp Cloud API.
 *
 * Los secretos (access_token, verify_token) se leen de variables de entorno / .env.
 * No escribas tokens reales aquí: quedarían registrados en el historial de git.
 */

return [
    'access_token'    => getenv('WA_ACCESS_TOKEN') ?: '',
    'verify_token'    => getenv('WA_VERIFY_TOKEN') ?: '',
    'phone_number_id' => getenv('WA_PHONE_NUMBER_ID') ?: '',
    'waba_id'         => getenv('WA_WABA_ID') ?: '',

    'business_name'   => getenv('WA_BUSINESS_NAME') ?: 'Heiyubai',
    'menu_base_url'   => getenv('WA_MENU_BASE_URL') ?: 'https://heiyubai.datarie.info/menu/',
    'advisor_phone'   => getenv('WA_ADVISOR_PHONE') ?: '573208624963',
    'advisor_url'     => getenv('WA_ADVISOR_URL') ?: 'https://wa.me/573208624963',

    // Dónde guardar logs
    'log_file'        => __DIR__ . '/../storage/whatsapp_webhook.log',
];
