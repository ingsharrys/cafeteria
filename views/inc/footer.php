<?php
/**
 * views/inc/footer.php
 * UBICACIÓN: heiyubai/views/inc/footer.php
 * 
 * ✅ Carga jQuery, Bootstrap 5 JS, SweetAlert2
 * ✅ Si NO hay cajero en sesión → renderiza modalContainer + carga modal_script.js
 * ✅ Si hay cajero → no muestra nada (modal no aparece)
 */

// Detectar si ya hay un cajero validado en sesión
$cajeroValidado = false;
if (isset($_SESSION['cajero']) && !empty($_SESSION['cajero'])) {
    $cajeroValidado = true;
} elseif (isset($_SESSION['usuario']['cajero']) && !empty($_SESSION['usuario']['cajero'])) {
    $cajeroValidado = true;
}

// BASE_URL para construir rutas de JS
$baseUrl = defined('BASE_URL') ? BASE_URL : '..';
?>

<!-- jQuery COMPLETO -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<!-- ✅ Bootstrap 5 JS Bundle (incluye Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlerts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.26/dist/sweetalert2.all.min.js"></script>

<?php if (!$cajeroValidado): ?>
<!-- ═══ MODAL DE VALIDACIÓN DE USUARIO ═══ -->
<!-- Solo se muestra si NO hay cajero en sesión -->
<div id="modalContainer"></div>
<script src="<?php echo $baseUrl; ?>/public/js/modal_script.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>

</body>
</html>