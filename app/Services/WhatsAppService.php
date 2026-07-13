<?php

class WhatsAppService {
    private $token;
    private $phoneId;
    private $apiVersion = 'v22.0';
    private $apiUrl = 'https://graph.facebook.com';
    private $enabled = true;
    private $db;

    public function __construct() {
        // Credenciales desde variables de entorno / .env (nunca hardcodeadas)
        $this->token   = getenv('WA_ACCESS_TOKEN') ?: '';
        $this->phoneId = getenv('WA_PHONE_NUMBER_ID') ?: '846122748583953';

        // CARGAR BD EN EL CONSTRUCTOR
        try {
            require_once __DIR__ . '/../../config/database.php';
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("❌ Error cargar BD: " . $e->getMessage());
            $this->db = null;
        }
    }

    public function isEnabled(): bool {
        return $this->enabled && !empty($this->token) && !empty($this->phoneId);
    }

    /**
     * MÉTODO PRINCIPAL
     * INSERT → ENVIAR → UPDATE
     */
    public function sendWhatsAppAndLog(int $numeroPedido, string $estadoPedido, string $celular, 
                                       string $plantilla, string $idioma): void {
        
        if (!$this->db) {
            error_log("❌ BD no disponible");
            return;
        }

        error_log("📱 sendWhatsAppAndLog: pedido={$numeroPedido}");

        // 1️⃣ INSERT
        $logId = null;
        try {
            $sql = "INSERT INTO whatsapp_log 
                    (numero_pedido, estado_pedido, celular, plantilla, idioma, estado_mensaje)
                    VALUES (:numero_pedido, :estado_pedido, :celular, :plantilla, :idioma, 'pendiente')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':numero_pedido' => $numeroPedido,
                ':estado_pedido' => $estadoPedido,
                ':celular' => $celular,
                ':plantilla' => $plantilla,
                ':idioma' => $idioma
            ]);
            
            $logId = $this->db->lastInsertId();
            error_log("   ✅ INSERT: log_id={$logId}");
            
        } catch (Throwable $e) {
            error_log("   ❌ INSERT ERROR: " . $e->getMessage());
            return;
        }

        // 2️⃣ ENVIAR
        $resultado = $this->sendCustomMessage($celular, $plantilla, $idioma);

        // 3️⃣ UPDATE
        try {
            if ($resultado['success']) {
                $sql = "UPDATE whatsapp_log 
                        SET estado_mensaje = 'enviado', resultado_exito = 1,
                            message_id = :message_id, http_code = :http_code
                        WHERE id = :id";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':message_id' => $resultado['message_id'] ?? '',
                    ':http_code' => $resultado['http_code'] ?? 200,
                    ':id' => $logId
                ]);
                
                error_log("   ✅ UPDATE: enviado");
            } else {
                $sql = "UPDATE whatsapp_log 
                        SET estado_mensaje = 'error', resultado_exito = 0,
                            mensaje_error = :mensaje_error, http_code = :http_code
                        WHERE id = :id";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':mensaje_error' => $resultado['error'] ?? 'Error',
                    ':http_code' => $resultado['http_code'] ?? 0,
                    ':id' => $logId
                ]);
                
                error_log("   ✅ UPDATE: error");
            }
        } catch (Throwable $e) {
            error_log("   ❌ UPDATE ERROR: " . $e->getMessage());
        }
    }

    /**
     * Enviar por WhatsApp
     */
    public function sendCustomMessage(string $phone, string $templateName = 'hello_world', 
                                      string $languageCode = 'es_CO'): array {
        
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'WhatsApp no configurado'];
        }

        $url = "{$this->apiUrl}/{$this->apiVersion}/{$this->phoneId}/messages";
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode]
            ]
        ];

        return $this->makeRequest($url, $payload);
    }

    /**
     * Petición HTTP
     */
    private function makeRequest(string $url, array $payload): array {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    "Authorization: Bearer {$this->token}"
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['success' => false, 'error' => $error, 'http_code' => $httpCode];
            }

            $data = json_decode($response, true);

            if ($httpCode === 200) {
                error_log("   ✅ Mensaje enviado");
                return [
                    'success' => true,
                    'message_id' => $data['messages'][0]['id'] ?? 'unknown',
                    'http_code' => $httpCode
                ];
            } else {
                $errorMsg = $data['error']['message'] ?? 'Error desconocido';
                error_log("   ❌ HTTP {$httpCode}");
                return [
                    'success' => false,
                    'error' => $errorMsg,
                    'http_code' => $httpCode
                ];
            }
        } catch (Throwable $e) {
            error_log("   ❌ EXCEPCIÓN: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

?>