<?php

class WhatsAppCloudApiService
{
    private string $token;
    private string $phoneNumberId;
    private string $graphVersion;

    public function __construct(array $config)
    {
        $this->token = $config['token'] ?? '';
        $this->phoneNumberId = $config['phone_number_id'] ?? '';
        $this->graphVersion = $config['graph_version'] ?? 'v24.0';
    }

    public function sendText(string $to, string $message): array
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ]);
    }

    public function sendReplyButtons(string $to, string $body, array $buttons): array
    {
        $mappedButtons = [];

        foreach ($buttons as $button) {
            $mappedButtons[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => $button['id'],
                    'title' => $button['title'],
                ],
            ];
        }

        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => [
                    'text' => $body,
                ],
                'action' => [
                    'buttons' => $mappedButtons,
                ],
            ],
        ]);
    }

    private function send(array $payload): array
    {
        if (empty($this->token) || empty($this->phoneNumberId)) {
            return [
                'success' => false,
                'error' => 'Falta token o phone_number_id en la configuración'
            ];
        }

        $url = "https://graph.facebook.com/{$this->graphVersion}/{$this->phoneNumberId}/messages";

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'http_code' => $httpCode,
                'error' => $curlError,
            ];
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'http_code' => $httpCode,
                'data' => $decoded,
            ];
        }

        return [
            'success' => false,
            'http_code' => $httpCode,
            'error' => $decoded['error']['message'] ?? 'Error desconocido',
            'response' => $decoded,
        ];
    }
}