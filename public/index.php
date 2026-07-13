<?php
/**
 * public/index.php - Page Router con control de acceso por roles
 * UBICACIÓN: heiyubai/public/index.php
 *
 * ✅ Bootstrap carga DB, Session, Response
 * ✅ Maneja logout ANTES de verificar autenticación
 * ✅ Verifica autenticación
 * ✅ Verifica permisos por rol antes de cargar la página
 * ✅ Whitelist de páginas permitidas
 * ✅ Ajuste para agregar .php automáticamente a la página solicitada
 */

// Cargar bootstrap (DB, Session, Config, etc.)
require_once __DIR__ . '/../bootstrap.php';

// ─── 0) Manejar LOGOUT ──────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    try {
        $authController = new \App\Controllers\AuthController();
        $authController->logout();
    } catch (\Throwable $e) {
        // Fallback: destruir sesión manualmente
        \Core\Session::destroy();
        header('Location: ' . (defined('LOGIN_URL') ? LOGIN_URL : '../views/auth/login_new.php'));
        exit;
    }
    exit;
}

// ─── 1) Verificar autenticación ────────────────────
if (!\Core\Session::exists('user_id')) {
    header('Location: ' . (defined('LOGIN_URL') ? LOGIN_URL : '../views/auth/login_new.php'));
    exit;
}

// ─── 2) Obtener página solicitada ──────────────────
$page = $_GET['page'] ?? 'llamadas.php';

// Sanitizar: solo alfanuméricos, guion bajo, punto y barra (para subcarpetas)
// Sanitizar: solo alfanuméricos, guion bajo y punto
$page = preg_replace('/[^a-zA-Z0-9_.\\-]/', '', $page);

// Si quedó vacío, usar default
if (empty($page) || !preg_match('/\.php$/', $page)) {
    $page = 'llamadas.php';
}

// Normalizar: agregar .php si no tiene extensión
$pageWithExt = $page;
if (pathinfo($page, PATHINFO_EXTENSION) !== 'php') {
    $pageWithExt .= '.php';
}

// ─── 3) Whitelist global de páginas ────────────────
$allowed = [
    'dashboard.php',
    'whatsapp.php',
    'llamadas.php',
    'gastos.php',
    'consolidado.php',
    'domiciliarios.php',
    'domicilios.php',
    'estadistica.php',
    'meseros.php',
    'productos.php',
    'register.php',
    'reportes.php',
    'edit_pedido.php',
    'caja_tm.php',
    'procesar_caja.php',
    'tarifas.php',
];
if (!in_array($page, $allowed)) {
    http_response_code(403);
    echo '<div style="text-align:center; padding:60px; font-family:sans-serif;">';
    echo '<h2>Página no encontrada</h2>';
    echo '<p>La página solicitada no existe.</p>';
    echo '<a href="index.php?page=llamadas.php">Volver al Dashboard</a>';
    echo '</div>';
    exit;
}

// ─── 4) Verificar permisos por rol ─────────────────
$cajero_validado = \Core\Session::get('cajero', null);
$paginas_permitidas = \Core\Session::get('paginas_permitidas', []);
$rol_nombre = \Core\Session::get('rol_nombre', 'default');

if ($cajero_validado && !empty($paginas_permitidas) && !in_array($page, $paginas_permitidas)) {
    if ($rol_nombre !== 'admin') {
        http_response_code(403);
        echo '<div style="text-align:center; padding:60px; font-family:sans-serif;">';
        echo '<h2 style="color:#ff4757;">Acceso Denegado</h2>';
        echo '<p>No tienes permisos para acceder a esta sección.</p>';
        echo '<p style="color:#6b7280; font-size:0.85rem;">Tu rol: <strong>' . htmlspecialchars($rol_nombre) . '</strong></p>';
        echo '<a href="index.php?page=llamadas.php" style="color:#3b82f6;">Volver al Dashboard</a>';
        echo '</div>';
        exit;
    }
}

// ─── 5) Verificar que el archivo existe ────────────
// Resolver ruta absoluta para evitar problemas con ../ en producción
$projectRoot = realpath(__DIR__ . '/..');
$viewFile = $projectRoot . '/views/' . $pageWithExt;

if (!file_exists($viewFile)) {
    http_response_code(404);
    echo '<div style="text-align:center; padding:60px; font-family:sans-serif;background:#111;color:#fff;">';
    echo '<h2>Error 404 - Vista no encontrada</h2>';
    echo '<p>Página solicitada: <code>' . htmlspecialchars($page) . '</code></p>';
    echo '<p>Ruta buscada: <code>' . htmlspecialchars($viewFile) . '</code></p>';
    echo '<p>¿El archivo existe exactamente en: views/' . htmlspecialchars($pageWithExt) . '?</p>';
    echo '<a href="index.php?page=llamadas.php" style="color:#0f0;">Volver al Dashboard</a>';
    echo '</div>';
    exit;
}

// ─── 6) Cargar header + vista + footer ─────────────
$headerFile = $projectRoot . '/views/inc/header.php';
$footerFile = $projectRoot . '/views/inc/footer.php';

if (file_exists($headerFile)) {
    include $headerFile;
}

echo '<div class="content"><div id="content-area">';
include $viewFile;
echo '</div></div>';

if (file_exists($footerFile)) {
    include $footerFile;
}