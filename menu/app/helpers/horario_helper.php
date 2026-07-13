<?php
/**
 * Helper para verificar horario de atención
 * Horario: 9:00 AM - 10:00 PM (21:00)
 * Zona horaria: América/Bogotá (Colombia UTC-5)
 */

/**
 * Verifica si el restaurante está abierto
 * @return bool true si está abierto, false si está cerrado
 */
function isOpen() {
    // Configurar zona horaria de Colombia
    date_default_timezone_set('America/Bogota');
    
    // Obtener hora actual en Colombia
    $horaActual = (int)date('H');
    $minutoActual = (int)date('i');
    $horarioEnMinutos = ($horaActual * 60) + $minutoActual;
    
    // Horario: 9:00 AM (540 minutos) a 10:00 PM (1320 minutos)
    $horaApertura = 9 * 60;      // 540 minutos (9:00 AM)
    $horaCierre = 23 * 60;       // 1320 minutos (10:00 PM)
    
    // Verificar si está dentro del horario
    return ($horarioEnMinutos >= $horaApertura && $horarioEnMinutos < $horaCierre);
}

/**
 * Obtiene la hora actual en Colombia formateada
 * @return string Hora en formato "HH:MM"
 */
function getCurrentTimeInColombia() {
    date_default_timezone_set('America/Bogota');
    return date('H:i');
}

/**
 * Obtiene el estado del restaurante como texto
 * @return array ['estado' => 'abierto|cerrado', 'mensaje' => 'string']
 */
function getStatusMessage() {
    $abierto = isOpen();
    $horaActual = getCurrentTimeInColombia();
    
    if ($abierto) {
        return [
            'estado' => 'abierto',
            'mensaje' => "¡Bienvenido! Estamos abiertos ($horaActual)"
        ];
    } else {
        return [
            'estado' => 'cerrado',
            'mensaje' => "Cerrado. Abrimos a las 9:00 AM (Hora actual: $horaActual)"
        ];
    }
}
?>