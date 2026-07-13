<?php
// Token de acceso de administrador para abrir el menú (el cajero registra
// pedidos sin necesidad del enlace de WhatsApp). Solo se genera aquí porque
// esta vista únicamente se muestra a usuarios autenticados en el panel.
require_once __DIR__ . '/../menu/app/helpers/menu_access.php';
$adminMenuToken = menu_access_generate('*', 8 * 3600, true);
?>
<!--
    llamadas.php - Vista Recoger
    Muestra: Pedidos para Recoger en Restaurante (53)
-->

<div class="container mt-5">
    <div class="row">
        <!-- Columna para Turnos Recoger -->
        <div class="col-md-12">
            <form action="../menu/" method="get" onsubmit="openPopupWindow(this); return false;">
                <input type="hidden" name="route" value="pedidos">
                <input type="hidden" name="pedido" value="call">
                <input type="hidden" name="t" value="<?php echo htmlspecialchars($adminMenuToken ?? ''); ?>">
                
                <div class="form-group">
                    <label for="orderNumberInput">Número del celular:</label>
                    <input type="number" class="form-control" id="orderNumberInput" name="numero" 
                           placeholder="Ingrese el número del celular" required>
                </div>
                <button type="submit" class="btn btn-primary">Registrar pedido Recoger</button>
            </form>
            
            <h3>📞 Pedidos para Recoger</h3>
            <div id="turnos-container" class="row"></div>
        </div>
    </div>
</div>

<!-- ✅ Campo oculto para tipo Recoger -->
<input type="hidden" id="tipoSolicitud" value="53">

<!-- Modal para detalles -->
<div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="myModalLabel">Detalles del Pedido</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="modal-content">
        <!-- Contenido dinámico -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Librerías externas -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
<script type="text/javascript" src="https://heiyubai.datarie.info/qz-tray.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>

<!-- ✅ SCRIPTS MODULARES NECESARIOS -->
<script src="../public/js/global-compat.js?cache=<?php echo(rand(10,100)); ?>"></script>
<script type="module" src="../public/js/app.js?cache=<?php echo(rand(10,100)); ?>"></script>