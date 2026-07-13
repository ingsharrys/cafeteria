<?php

/**
 * Configuración de WhatsApp Cloud API (webhooks/bot).
 *
 * Los valores sensibles se leen de variables de entorno / .env.
 * Nunca escribas tokens reales en este archivo: quedan en el historial de git.
 */

return [
    // Token privado de Meta / WhatsApp Cloud API
    'access_token'    => getenv('WA_ACCESS_TOKEN') ?: '',

    // Verify token del webhook
    'verify_token'    => getenv('WA_VERIFY_TOKEN') ?: '',

    // Phone Number ID de WhatsApp Cloud API
    'phone_number_id' => getenv('WA_PHONE_NUMBER_ID') ?: '',

    // WABA ID
    'waba_id'         => getenv('WA_WABA_ID') ?: '',

    // Datos públicos del negocio
    'business_name'   => getenv('WA_BUSINESS_NAME') ?: 'colegio_pombo',
    'menu_base_url'   => getenv('WA_MENU_BASE_URL') ?: 'https://cafeteria.sharrys.com/menu/',
    'advisor_phone'   => getenv('WA_ADVISOR_PHONE') ?: '573173667467',
    'asesor_url'      => getenv('WA_ADVISOR_URL') ?: 'https://wa.me/573173667467',
];
