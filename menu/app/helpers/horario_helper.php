<?php
/**
 * Helper para verificar horario de atención.
 * El horario se define desde el panel (tabla menu_config) y aquí solo se lee.
 * Si no hay configuración, usa por defecto 5:00 AM - 9:30 AM.
 * Zona horaria: América/Bogotá (Colombia UTC-5)
 */

/**
 * Lee el horario configurado desde la BD (clave 'hora_apertura' / 'hora_cierre').
 * @return array ['apertura' => 'HH:MM', 'cierre' => 'HH:MM']
 */
function horario_config(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $defaults = ['apertura' => '05:00', 'cierre' => '09:30'];

    try {
        $conn = (new \App\Config\Database())->getConnection();
        $stmt = $conn->query(
            "SELECT clave, valor FROM menu_config WHERE clave IN ('hora_apertura','hora_cierre')"
        );
        $rows = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        $cache = [
            'apertura' => !empty($rows['hora_apertura']) ? $rows['hora_apertura'] : $defaults['apertura'],
            'cierre'   => !empty($rows['hora_cierre'])   ? $rows['hora_cierre']   : $defaults['cierre'],
        ];
    } catch (\Throwable $e) {
        // Si la tabla aún no existe, se usa el horario por defecto.
        $cache = $defaults;
    }

    return $cache;
}

/**
 * Convierte "HH:MM" a minutos desde medianoche.
 */
function horario_a_minutos(string $hhmm): int
{
    $p = explode(':', trim($hhmm));
    return ((int) ($p[0] ?? 0)) * 60 + (int) ($p[1] ?? 0);
}

/**
 * Convierte "HH:MM" (24h) a "H:MM AM/PM".
 */
function horario_formato_12h(string $hhmm): string
{
    $min = horario_a_minutos($hhmm);
    $h = intdiv($min, 60);
    $m = $min % 60;
    $ampm = $h >= 12 ? 'PM' : 'AM';
    $h12 = $h % 12;
    if ($h12 === 0) {
        $h12 = 12;
    }
    return sprintf('%d:%02d %s', $h12, $m, $ampm);
}

/**
 * Verifica si el restaurante está abierto según el horario configurado.
 * @return bool
 */
function isOpen(): bool
{
    date_default_timezone_set('America/Bogota');
    $ahora = ((int) date('H') * 60) + (int) date('i');

    $cfg = horario_config();
    $apertura = horario_a_minutos($cfg['apertura']);
    $cierre   = horario_a_minutos($cfg['cierre']);

    return ($ahora >= $apertura && $ahora < $cierre);
}

/**
 * Hora actual en Colombia "HH:MM".
 */
function getCurrentTimeInColombia(): string
{
    date_default_timezone_set('America/Bogota');
    return date('H:i');
}

/**
 * Estado del restaurante como texto.
 * @return array ['estado' => 'abierto|cerrado', 'mensaje' => 'string']
 */
function getStatusMessage(): array
{
    $abierto = isOpen();
    $horaActual = getCurrentTimeInColombia();
    $cfg = horario_config();

    if ($abierto) {
        return [
            'estado'  => 'abierto',
            'mensaje' => "¡Bienvenido! Estamos abiertos ($horaActual)"
        ];
    }

    $rango = horario_formato_12h($cfg['apertura']) . ' a ' . horario_formato_12h($cfg['cierre']);
    return [
        'estado'  => 'cerrado',
        'mensaje' => "Cerrado. Atendemos de $rango (Hora actual: $horaActual)"
    ];
}
