<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/Session.php';  // ✅ Agregar __DIR__

Session::start();

// Si el usuario no está autenticado, redirigir al login
if (!Session::get('user_id')) {
    header("Location: /heiyubai.datarie.info/views/login_new.php");  // ✅ Usar la nueva ruta
    exit();
}
?>