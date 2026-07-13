<?php
/**
 * bootstrap.php - Inicializa la aplicación con estructura MVC
 * Versión ajustada para Hostinger + detección automática de raíz
 */

// ─── ACTIVAR ERRORES TEMPORAL (borrar cuando funcione) ─────────────
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ─── LOG INICIAL ──────────────────────────────────────────────────────
$logFile = __DIR__ . '/debug_bootstrap.log';
function logBoot($msg) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}
logBoot("bootstrap.php iniciado");

// ─── 1) DETECTAR RAÍZ DEL PROYECTO AUTOMÁTICAMENTE ────────────────────
$possibleRoots = [
    __DIR__,                                                     // la carpeta actual del proyecto
    realpath(__DIR__ . '/..'),                                    // sube 1 nivel (lo usual)
    realpath(__DIR__ . '/../..'),                                 // sube 2 niveles (si hay subcarpeta extra)
    realpath($_SERVER['DOCUMENT_ROOT'] ?? ''),                    // raíz del servidor web
    realpath(($_SERVER['DOCUMENT_ROOT'] ?? '') . '/'),
    'https://cafeteria.sharrys.com/',                           // ruta absoluta de tu proyecto
];

$rootPath = null;
foreach ($possibleRoots as $path) {
    if ($path && file_exists($path . '/autoload.php')) {
        $rootPath = $path;
        break;
    }
}

if (!$rootPath) {
    logBoot("ERROR: No se encontró autoload.php en ninguna ruta");
    die('No se pudo encontrar autoload.php.<br>Rutas probadas:<br>' . implode('<br>', array_map('htmlspecialchars', $possibleRoots)) . '<br><br>Confirma que autoload.php exista en la carpeta del proyecto y que Apache pueda leerla.');
}

define('ROOT_PATH', $rootPath);
logBoot("ROOT_PATH detectado correctamente: " . ROOT_PATH);

// ─── 2) CARGAR AUTOLOADER ─────────────────────────────────────────────
$autoloadPath = ROOT_PATH . '/autoload.php';
logBoot("Cargando autoload: $autoloadPath");

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    logBoot("autoload.php cargado OK");
} else {
    logBoot("ERROR: autoload.php NO encontrado en $autoloadPath");
    die('autoload.php no encontrado en <code>' . htmlspecialchars($autoloadPath) . '</code>');
}

// ─── 3) CARGAR CONSTANTES ─────────────────────────────────────────────
$constantsPath = ROOT_PATH . '/config/constants.php';
$fallbackPath = ROOT_PATH . '/constants.php';

logBoot("Buscando constantes...");
if (file_exists($constantsPath)) {
    require_once $constantsPath;
    logBoot("config/constants.php cargado");
} elseif (file_exists($fallbackPath)) {
    require_once $fallbackPath;
    logBoot("constants.php cargado (fallback)");
} else {
    logBoot("No se encontró constants.php ni config/constants.php");
}

// ─── 4) ZONA HORARIA ──────────────────────────────────────────────────
date_default_timezone_set('America/Bogota');
logBoot("Zona horaria establecida");

// ─── 5) CARGAR CONFIGURACIÓN ──────────────────────────────────────────
logBoot("Cargando Config");
if (class_exists('Config\\Config')) {
    Config\Config::load();
    logBoot("Config cargada OK");
} else {
    logBoot("ERROR: Clase Config\\Config no existe");
}

// ─── 6) INICIAR SESIÓN ────────────────────────────────────────────────
logBoot("Iniciando sesión");
if (class_exists('Core\\Session')) {
    Core\Session::start();
    logBoot("Sesión iniciada");
} else {
    logBoot("WARNING: Core\\Session no existe → sesión no iniciada");
}

// ─── 7) MANEJO DE ERRORES Y EXCEPCIONES ──────────────────────────────
logBoot("Registrando handlers");
// (tu código de handlers sigue igual aquí: set_error_handler, set_exception_handler, register_shutdown_function)

// ─── 8) HEADERS DE SEGURIDAD ─────────────────────────────────────────
logBoot("Configurando headers de seguridad");
if (class_exists('Core\\Response')) {
    Core\Response::setSecurityHeaders();
}

// ─── 9) LOG FINAL ─────────────────────────────────────────────────────
logBoot("bootstrap.php completado OK");
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_log('[BOOTSTRAP] Sistema inicializado');
}