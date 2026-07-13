<?php
/**
 * enviar_masivo_domingo.php
 *
 * Ejecutar por cron cada minuto.
 *
 * Hace lo siguiente:
 * - Lee SOLO WHATSAPP_TOKEN desde el .env de la raíz.
 * - Usa fijo el Phone Number ID: 1010195975520716.
 * - Toma máximo 3 clientes por ejecución.
 * - Solo toma celulares válidos: exactamente 10 dígitos y que empiecen por 3.
 * - Solo toma clientes con envios_servicio_atencion = 0.
 * - Envía la plantilla servicio_de_atencion.
 * - Usa el campo cliente como parámetro {{1}}.
 * - Al enviar correctamente, aumenta envios_servicio_atencion y guarda la fecha.
 * - Solo permite envíos entre 08:00 a.m. y 10:00 p.m. hora Colombia.
 * - Usa lock para evitar doble ejecución.
 * - Guarda logs para revisar cada intento.
 */

// ======================================================
// ZONA HORARIA
// ======================================================
date_default_timezone_set('America/Bogota');

// ======================================================
// FUNCIÓN PARA LEER EL .env DE LA RAÍZ
// ======================================================
function cargarEnv(string $rutaEnv): array
{
    if (!file_exists($rutaEnv)) {
        throw new Exception("No se encontró el archivo .env en: {$rutaEnv}");
    }

    $variables = [];
    $lineas = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lineas as $linea) {
        $linea = trim($linea);

        // Ignorar comentarios
        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }

        // Solo líneas con =
        if (!str_contains($linea, '=')) {
            continue;
        }

        [$clave, $valor] = explode('=', $linea, 2);

        $clave = trim($clave);
        $valor = trim($valor);

        // Quitar comillas si las tiene
        $valor = trim($valor, "\"'");

        $variables[$clave] = $valor;
    }

    return $variables;
}

// ======================================================
// CARGAR TOKEN DESDE EL .env
// ======================================================
try {
    $env = cargarEnv(__DIR__ . '/.env');
} catch (Exception $e) {
    header('Content-Type: text/plain; charset=utf-8');
    die('ERROR: ' . $e->getMessage());
}

// Solo el token viene del .env
$accessToken = $env['WHATSAPP_TOKEN'] ?? null;

// Phone Number ID fijo, el mismo que te funciona en Postman
$phoneNumberId = '1010195975520716';

// Validar que exista el token
if (!$accessToken) {
    header('Content-Type: text/plain; charset=utf-8');
    die('ERROR: No se encontró WHATSAPP_TOKEN en el .env');
}

// ======================================================
// CONFIGURACIÓN DE BASE DE DATOS
// ======================================================
$dbHost = 'localhost';
$dbName = 'u936058592_restaurant';
$dbUser = 'u936058592_heiyu';
$dbPass = 'u;J7yx*F';

// ======================================================
// CONFIGURACIÓN DEL ENVÍO
// ======================================================
$templateName     = 'servicio_de_atencion';
$templateLanguage = 'es_CO';

// Máximo 3 por ejecución del cron
$limitePorMinuto = 3;

// Horario permitido en Colombia
$horaInicio = '06:00';
$horaFin    = '10:00';

// Esta campaña solo se permite hoy.
// Si vuelves a usarla otro día, cambia la fecha.
// Si no quieres limitarla por fecha, usa: $fechaPermitidaCampana = null;
$fechaPermitidaCampana = '2026-05-10';

// ======================================================
// LOGS Y LOCK
// ======================================================
$logDir   = __DIR__ . '/logs_masivo_domingo';
$logFile  = $logDir . '/envios_' . date('Y-m-d') . '.log';
$lockFile = __DIR__ . '/envio_masivo_domingo.lock';

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function escribirLog(string $mensaje): void
{
    global $logFile;

    $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje . PHP_EOL;
    file_put_contents($logFile, $linea, FILE_APPEND);
}

function responder(string $mensaje): void
{
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensaje . PHP_EOL;
}

function prepararNombre(?string $nombre): string
{
    $nombre = trim((string) $nombre);

    if ($nombre === '') {
        return 'cliente';
    }

    // Quitar espacios dobles
    $nombre = preg_replace('/\s+/', ' ', $nombre);

    return $nombre;
}

function enviarPlantillaWhatsApp(
    string $phoneNumberId,
    string $accessToken,
    string $numeroDestino,
    string $templateName,
    string $templateLanguage,
    string $nombreCliente
): array {
    // Endpoint igual al que te funciona en Postman:
    // https://graph.facebook.com/v25.0/1010195975520716/messages
    $url = "https://graph.facebook.com/v25.0/{$phoneNumberId}/messages";

    // Payload igual al probado en Postman, pero dinámico
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $numeroDestino,
        'type' => 'template',
        'template' => [
            'name' => $templateName,
            'language' => [
                'code' => $templateLanguage
            ],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $nombreCliente
                        ]
                    ]
                ]
            ]
        ]
    ];

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);

    $rawResponse = curl_exec($ch);
    $curlError   = curl_error($ch);
    $httpCode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($rawResponse === false) {
        return [
            'ok' => false,
            'http_code' => $httpCode,
            'response' => 'Error cURL: ' . $curlError,
            'message_id' => null,
        ];
    }

    $decoded = json_decode($rawResponse, true);

    $messageId = $decoded['messages'][0]['id'] ?? null;

    $ok = $httpCode >= 200
        && $httpCode < 300
        && !empty($messageId);

    return [
        'ok' => $ok,
        'http_code' => $httpCode,
        'response' => $decoded ?: $rawResponse,
        'message_id' => $messageId,
    ];
}

// ======================================================
// LOCK PARA EVITAR DOBLE EJECUCIÓN
// ======================================================
$lockHandle = fopen($lockFile, 'c');

if (!$lockHandle) {
    $mensaje = 'ERROR: No se pudo crear o abrir el archivo lock.';
    escribirLog($mensaje);
    responder($mensaje);
    exit;
}

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    $mensaje = 'INFO: Ya hay otra ejecución en curso. Se omite esta ejecución.';
    escribirLog($mensaje);
    responder($mensaje);
    exit;
}

// ======================================================
// VALIDACIONES DE FECHA Y HORA
// ======================================================
$fechaActual = date('Y-m-d');
$horaActual  = date('H:i');

if ($fechaPermitidaCampana !== null && $fechaActual !== $fechaPermitidaCampana) {
    $mensaje = "INFO: Hoy es {$fechaActual}. La campaña solo está permitida para {$fechaPermitidaCampana}. No se envía nada.";
    escribirLog($mensaje);
    responder($mensaje);

    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    exit;
}

if ($horaActual < $horaInicio || $horaActual > $horaFin) {
    $mensaje = "INFO: Fuera de horario permitido. Hora actual: {$horaActual}. Rango permitido: {$horaInicio} - {$horaFin}.";
    escribirLog($mensaje);
    responder($mensaje);

    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    exit;
}

// ======================================================
// CONEXIÓN A BASE DE DATOS
// ======================================================
try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    $mensaje = 'ERROR DE CONEXIÓN BD: ' . $e->getMessage();
    escribirLog($mensaje);
    responder($mensaje);

    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    exit;
}

// ======================================================
// BUSCAR MÁXIMO 3 CLIENTES PENDIENTES
// ======================================================
try {
    $sql = "
        SELECT 
            id,
            cliente,
            celular,
            envios_servicio_atencion,
            ultimo_envio_servicio_atencion
        FROM clientes
        WHERE celular REGEXP '^3[0-9]{9}$'
          AND envios_servicio_atencion = 0
        ORDER BY id ASC
        LIMIT :limite
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limite', $limitePorMinuto, PDO::PARAM_INT);
    $stmt->execute();

    $clientes = $stmt->fetchAll();

    if (empty($clientes)) {
        $mensaje = 'INFO: No hay clientes pendientes por enviar.';
        escribirLog($mensaje);
        responder($mensaje);

        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        exit;
    }
} catch (PDOException $e) {
    $mensaje = 'ERROR consultando clientes: ' . $e->getMessage();
    escribirLog($mensaje);
    responder($mensaje);

    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    exit;
}

// ======================================================
// ENVIAR MENSAJES
// ======================================================
$enviados = 0;
$errores  = 0;

foreach ($clientes as $cliente) {
    $clienteId = (int) $cliente['id'];
    $nombre    = prepararNombre($cliente['cliente']);
    $celular   = trim($cliente['celular']);

    // Para Colombia: 57 + celular de 10 dígitos
    $numeroDestino = '57' . $celular;

    escribirLog("INTENTO: cliente_id={$clienteId}, celular={$celular}, destino={$numeroDestino}, nombre={$nombre}");

    $resultado = enviarPlantillaWhatsApp(
        $phoneNumberId,
        $accessToken,
        $numeroDestino,
        $templateName,
        $templateLanguage,
        $nombre
    );

    if ($resultado['ok']) {
        try {
            $update = $pdo->prepare("
                UPDATE clientes
                SET 
                    envios_servicio_atencion = envios_servicio_atencion + 1,
                    ultimo_envio_servicio_atencion = NOW()
                WHERE id = :id
            ");

            $update->execute([
                ':id' => $clienteId
            ]);

            $enviados++;

            $messageId = $resultado['message_id'] ?? 'sin_message_id';
            escribirLog("OK: enviado a cliente_id={$clienteId}, celular={$celular}, message_id={$messageId}");
        } catch (PDOException $e) {
            $errores++;
            escribirLog("ERROR actualizando cliente_id={$clienteId}: " . $e->getMessage());
        }
    } else {
        $errores++;

        $respuestaMeta = json_encode($resultado['response'], JSON_UNESCAPED_UNICODE);

        escribirLog(
            "ERROR envío cliente_id={$clienteId}, celular={$celular}, http_code={$resultado['http_code']}, respuesta={$respuestaMeta}"
        );
    }
}

// ======================================================
// RESPUESTA FINAL
// ======================================================
$resumen = "FINALIZADO: enviados={$enviados}, errores={$errores}, procesados=" . count($clientes);

escribirLog($resumen);
responder($resumen);

flock($lockHandle, LOCK_UN);
fclose($lockHandle);