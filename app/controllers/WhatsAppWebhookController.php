<?php

declare(strict_types=1);

class WhatsAppWebhookController
{
    private array $config;
    private string $logFile;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->logFile = __DIR__ . '/../../storage/logs/whatsapp_webhook.log';
    }

    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'GET') {
            $this->verifyWebhook();
            return;
        }

        if ($method === 'POST') {
            $this->receiveWebhook();
            return;
        }

        http_response_code(405);
        echo 'Method Not Allowed';
    }

    private function verifyWebhook(): void
    {
        $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
        $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
        $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

        $this->writeLog([
            'type' => 'verify',
            'mode' => $mode,
            'token_received' => $token,
            'token_expected' => $this->config['verify_token'] ?? '',
        ]);

        if ($mode === 'subscribe' && $token === ($this->config['verify_token'] ?? '')) {
            http_response_code(200);
            echo $challenge;
            return;
        }

        http_response_code(403);
        echo 'Forbidden';
    }

    private function receiveWebhook(): void
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            $this->writeLog([
                'type' => 'invalid_json',
                'raw' => $raw,
            ]);

            http_response_code(200);
            echo 'INVALID_JSON';
            return;
        }

        $value = $data['entry'][0]['changes'][0]['value'] ?? [];

        $receivedPhoneNumberId = $value['metadata']['phone_number_id'] ?? '';
        $receivedDisplayPhone = $value['metadata']['display_phone_number'] ?? '';

        $this->writeLog([
            'received_at' => date('Y-m-d H:i:s'),
            'value_keys' => array_keys($value),
            'metadata' => $value['metadata'] ?? null,
            'body' => $data,
        ]);

        $this->writeLog([
            'type' => 'debug_phone_source',
            'expected_phone_number_id' => $this->config['phone_number_id'] ?? '',
            'received_phone_number_id' => $receivedPhoneNumberId,
            'received_display_phone_number' => $receivedDisplayPhone,
            'has_messages' => isset($value['messages']),
            'has_statuses' => isset($value['statuses']),
        ]);

        // Ignorar eventos de otro número distinto al configurado
        if (
            $receivedPhoneNumberId !== '' &&
            $receivedPhoneNumberId !== ($this->config['phone_number_id'] ?? '')
        ) {
            $this->writeLog([
                'type' => 'wrong_phone_number_event',
                'reason' => 'Evento recibido de otro número de WhatsApp',
                'expected_phone_number_id' => $this->config['phone_number_id'] ?? '',
                'received_phone_number_id' => $receivedPhoneNumberId,
                'received_display_phone_number' => $receivedDisplayPhone,
                'body' => $data,
            ]);

            http_response_code(200);
            echo 'IGNORED_WRONG_PHONE';
            return;
        }

        $message = $value['messages'][0] ?? null;

        if (!$message) {
            http_response_code(200);
            echo 'EVENT_RECEIVED';
            return;
        }

        $from = $message['from'] ?? '';
        $type = $message['type'] ?? '';

        if ($from === '') {
            http_response_code(200);
            echo 'EVENT_RECEIVED';
            return;
        }

        // Si escribe cualquier texto, mandar botones
        if ($type === 'text') {
            $text = trim((string)($message['text']['body'] ?? ''));

            $this->writeLog([
                'type' => 'incoming_text',
                'from' => $from,
                'text' => $text,
            ]);

            $this->sendGreetingButtons($from);

            http_response_code(200);
            echo 'EVENT_RECEIVED';
            return;
        }

        // Si pulsa un botón, responder según la opción
        if ($type === 'interactive') {
            $buttonId = $message['interactive']['button_reply']['id'] ?? '';

            $this->writeLog([
                'type' => 'button_reply',
                'from' => $from,
                'button_id' => $buttonId,
            ]);

            if ($buttonId !== '') {
                $this->handleButtonReply($from, $buttonId);
            }

            http_response_code(200);
            echo 'EVENT_RECEIVED';
            return;
        }

        http_response_code(200);
        echo 'EVENT_RECEIVED';
    }

    private function handleButtonReply(string $to, string $buttonId): void
    {
        switch ($buttonId) {
            case 'domicilio':
                $url = $this->buildMenuUrl('wp', $to);
                $this->sendCtaUrlMessage(
                    $to,
                    "Perfecto 🍜\n\nHaz tu pedido a domicilio tocando el botón de abajo.",
                    'Haz tu pedido aquí',
                    $url
                );
                break;

            case 'recoger':
                $url = $this->buildMenuUrl('call', $to);
                $this->sendCtaUrlMessage(
                    $to,
                    "¡Claro! 🍱\n\nHaz tu pedido para recoger tocando el botón de abajo.",
                    'Haz tu pedido aquí',
                    $url
                );
                break;

            case 'asesor':
                $this->sendCtaUrlMessage(
                    $to,
                    "Te conecto con un asesor 👨‍💼\n\nToca el botón de abajo para escribirnos.",
                    'Hablar asesor',
                    (string)($this->config['advisor_url'] ?? '')
                );
                break;

            default:
                $this->writeLog([
                    'type' => 'unknown_button_reply',
                    'from' => $to,
                    'button_id' => $buttonId,
                ]);
                break;
        }
    }

    private function buildMenuUrl(string $pedidoType, string $customerPhone): string
    {
        $numero = $this->normalizePhone($customerPhone);

        $params = http_build_query([
            'route'  => 'pedidos',
            'pedido' => $pedidoType,
            'numero' => $numero,
        ]);

        return rtrim((string)($this->config['menu_base_url'] ?? ''), '/') . '/?' . $params;
    }

    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/\D+/', '', $phone) ?? '';

        if ($clean === '') {
            return '';
        }

        // Si viene 57XXXXXXXXXX, dejarlo como local colombiano de 10 dígitos
        if (str_starts_with($clean, '57') && strlen($clean) === 12) {
            return substr($clean, 2);
        }

        return $clean;
    }

    private function sendGreetingButtons(string $to): void
    {
        $url = 'https://graph.facebook.com/v23.0/' . ($this->config['phone_number_id'] ?? '') . '/messages';

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => [
                    'text' => "¡Hola! 👋 Bienvenido a *" . ($this->config['business_name'] ?? 'Heiyubai') . "* 🍜\n\n¿Quieres hacer un pedido?",
                ],
                'action' => [
                    'buttons' => [
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => 'domicilio',
                                'title' => 'Domicilio',
                            ],
                        ],
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => 'recoger',
                                'title' => 'Recoger',
                            ],
                        ],
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => 'asesor',
                                'title' => 'Hablar asesor',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->sendRequest($url, $payload);
    }

    private function sendTextMessage(string $to, string $message): void
    {
        $url = 'https://graph.facebook.com/v23.0/' . ($this->config['phone_number_id'] ?? '') . '/messages';

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => true,
                'body' => $message,
            ],
        ];

        $this->sendRequest($url, $payload);
    }

    private function sendCtaUrlMessage(string $to, string $bodyText, string $buttonText, string $targetUrl): void
    {
        $url = 'https://graph.facebook.com/v23.0/' . ($this->config['phone_number_id'] ?? '') . '/messages';

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'cta_url',
                'body' => [
                    'text' => $bodyText,
                ],
                'action' => [
                    'name' => 'cta_url',
                    'parameters' => [
                        'display_text' => $buttonText,
                        'url' => $targetUrl,
                    ],
                ],
            ],
        ];

        $this->sendRequest($url, $payload);
    }

    private function sendRequest(string $url, array $payload): void
    {
        $accessToken = $this->config['access_token'] ?? '';

        if ($accessToken === '' || ($this->config['phone_number_id'] ?? '') === '') {
            $this->writeLog([
                'type' => 'send_request_error',
                'error' => 'Falta access_token o phone_number_id en config',
                'request_payload' => $payload,
            ]);
            return;
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $this->writeLog([
            'type' => 'send_request',
            'sent_at' => date('Y-m-d H:i:s'),
            'request_payload' => $payload,
            'response_status' => $httpCode,
            'response_body' => $response,
            'curl_error' => $error,
        ]);
    }

    private function writeLog(array $content): void
    {
        $dir = dirname($this->logFile);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $line = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        file_put_contents($this->logFile, $line, FILE_APPEND);
    }
}