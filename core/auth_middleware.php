<?php
require_once __DIR__ . '/Session.php';

Session::start();

// Si el usuario no está autenticado, redirigir al login
if (!Session::get('user_id')) {
    header("Location: /heiyubai.datarie.info/views/login_new.php");
    exit();
}

// Verificar expiración de sesión (opcional)
if (Session::exists('LAST_ACTIVITY')) {
    $inactive = time() - Session::get('LAST_ACTIVITY');
    $sessionLifetime = 14400; // 4 horas
    
    if ($inactive > $sessionLifetime) {
        Session::destroy();
        header("Location: /heiyubai.datarie.info/views/login_new.php?timeout=1");
        exit();
    }
}

Session::set('LAST_ACTIVITY', time());
?>