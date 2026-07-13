<?php
declare(strict_types=1);

/**
 * EnviarMensajeWhatsApp.php
 * Envía mensajes por WhatsApp - Método estático
 */

class EnviarMensajeWhatsApp
{
    public static function enviar(
        string $telefono,
        string $plantilla,
        string $idioma = 'es_CO',
        ?string $parametroBoton = null
    ): array {
        $config = require __DIR__ . '/../config/whatsapp.php';

        $token = (string)($config['access_token'] ?? '');
        $phoneId = (string)($config['phone_number_id'] ?? '');
        $apiVersion = 'v23.0';
        $apiUrl = 'https://graph.facebook.com';

        if ($token === '' || $phoneId === '') {
            return [
                'success' => false,
                'error' => 'Falta access_token o phone_number_id en app/config/whatsapp.php'
            ];
        }

        $url = "{$apiUrl}/{$apiVersion}/{$phoneId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $telefono,
            'type' => 'template',
            'template' => [
                'name' => $plantilla,
                'language' => [
                    'code' => $idioma
                ]
            ]
        ];

        // Botón dinámico URL: {{1}}
        if (!empty($parametroBoton)) {
            $payload['template']['components'] = [
                [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => '0',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $parametroBoton
                        ]
                    ]
                ]
            ];
        }

        try {
            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    "Authorization: Bearer {$token}"
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            curl_close($ch);

            if ($error) {
                return [
                    'success' => false,
                    'error' => $error,
                    'http_code' => $httpCode,
                    'payload' => $payload
                ];
            }

            $data = json_decode((string)$response, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'message_id' => $data['messages'][0]['id'] ?? 'unknown',
                    'http_code' => $httpCode,
                    'response' => $data,
                    'payload' => $payload
                ];
            }

            return [
                'success' => false,
                'error' => $data['error']['message'] ?? 'Error desconocido',
                'http_code' => $httpCode,
                'response' => $data,
                'payload' => $payload
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}