<?php
/**
 * Helper para verificar horario de atención
 * Horario: 6:00 AM - 9:30 AM
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
    
    // Horario: 6:00 AM (360 minutos) a 9:30 AM (570 minutos)
    $horaApertura = 6 * 60;            // 360 minutos (6:00 AM)
    $horaCierre   = (9 * 60) + 30;     // 570 minutos (9:30 AM)
    
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
            'mensaje' => "Cerrado. Atendemos de 6:00 AM a 9:30 AM (Hora actual: $horaActual)"
        ];
    }
}
?>