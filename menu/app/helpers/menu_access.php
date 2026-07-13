<?php
/**
 * menu_access.php
 * Control de acceso al menú mediante enlace firmado.
 *
 * El enlace se envía por WhatsApp con un token HMAC que incluye el número del
 * cliente y una expiración. Nadie puede falsificarlo sin la clave secreta, así
 * que se evita que entren al menú por la URL directa (pedidos de broma).
 *
 * La clave se toma de MENU_ACCESS_SECRET (.env). Si no existe, usa como
 * respaldo el WA_VERIFY_TOKEN (que también es secreto y está en .env).
 */

if (!function_exists('menu_access_secret')) {

    function menu_access_secret(): string
    {
        // 1) Variable de entorno del servidor
        $s = getenv('MENU_ACCESS_SECRET');
        if ($s !== false && $s !== '') {
            return (string) $s;
        }

        // 2) Leer del .env de la raíz del sitio (ambas apps incluyen este mismo
        //    archivo, así que el secreto resultante es idéntico).
        static $env = null;
        if ($env === null) {
            $env = [];
            $envFile = __DIR__ . '/../../../.env';
            if (is_file($envFile)) {
                foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                        continue;
                    }
                    [$k, $v] = explode('=', $line, 2);
                    $env[trim($k)] = trim(trim($v), "\"'");
                }
            }
        }

        if (!empty($env['MENU_ACCESS_SECRET'])) {
            return $env['MENU_ACCESS_SECRET'];
        }
        if (!empty($env['WA_VERIFY_TOKEN'])) {
            return $env['WA_VERIFY_TOKEN'];
        }

        $wa = getenv('WA_VERIFY_TOKEN');
        if ($wa !== false && $wa !== '') {
            return (string) $wa;
        }

        return 'cambia-esta-clave-en-el-env';
    }

    /**
     * Lee una variable del entorno o del .env de la raíz del sitio.
     */
    function menu_env(string $key, $default = null)
    {
        $g = getenv($key);
        if ($g !== false && $g !== '') {
            return $g;
        }

        static $env = null;
        if ($env === null) {
            $env = [];
            $envFile = __DIR__ . '/../../../.env';
            if (is_file($envFile)) {
                foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                        continue;
                    }
                    [$k, $v] = explode('=', $line, 2);
                    $env[trim($k)] = trim(trim($v), "\"'");
                }
            }
        }

        return $env[$key] ?? $default;
    }

    /**
     * ¿Se exige que el cliente esté aprobado para poder pedir?
     * Controlado por MENU_REQUIRE_APPROVAL en .env (por defecto: sí).
     */
    function menu_require_approval(): bool
    {
        $v = strtolower((string) menu_env('MENU_REQUIRE_APPROVAL', '1'));
        return !in_array($v, ['0', 'false', 'no', 'off'], true);
    }

    function menu_access_b64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    function menu_access_b64url_decode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Genera un token firmado.
     *
     * @param string $numero Teléfono del cliente ('*' para acceso de administrador)
     * @param int    $ttl    Segundos de validez
     * @param bool   $admin  Marca el token como de administrador
     */
    function menu_access_generate(string $numero, int $ttl = 3600, bool $admin = false): string
    {
        $payload = [
            'n'   => $numero,
            'exp' => time() + $ttl,
            'a'   => $admin ? 1 : 0,
        ];
        $body = menu_access_b64url_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $sig  = menu_access_b64url_encode(hash_hmac('sha256', $body, menu_access_secret(), true));

        return $body . '.' . $sig;
    }

    /**
     * Valida un token. Devuelve el payload (n, exp, a) o null si es inválido/expirado.
     */
    function menu_access_validate(?string $token): ?array
    {
        if (!$token || strpos($token, '.') === false) {
            return null;
        }

        [$body, $sig] = explode('.', $token, 2);

        $expected = menu_access_b64url_encode(hash_hmac('sha256', $body, menu_access_secret(), true));
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $payload = json_decode(menu_access_b64url_decode($body), true);
        if (!is_array($payload) || empty($payload['exp']) || time() > (int) $payload['exp']) {
            return null;
        }

        return $payload;
    }
}
