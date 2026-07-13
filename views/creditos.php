<!-- 
    dashboard.php - Vista Principal
    Muestra: Mesas (51) + Para Llevar (52)
-->

<style>
    .btn btn-secondary btn-block{width:100% !important;}
</style>

<div class="container mt-5">
    <div class="row">
        <!-- Columna para Mesas -->
        <div class="col-md-12">
            <h3>🪑 Mesas</h3>
            <div id="mesas-container" class="row"></div>
        </div>

        <!-- Columna para Turnos Para Llevar -->
        <div class="col-md-12">
            <br>
            <form action="../menu/" method="get" onsubmit="openPopupWindow(this); return false;">
                <input type="hidden" name="route" value="pedidos">
                <input type="hidden" name="pedido" value="qr">
                
                <div class="form-group">
                    <label for="orderNumberInput">Número del celular:</label>
                    <input type="number" class="form-control" id="orderNumberInput" name="numero" 
                           placeholder="Ingrese el número del celular" required>
                </div>
                <button type="submit" class="btn btn-primary">Registrar pedido Para Llevar</button>
            </form>
            
            <h3>🥡 Para Llevar</h3>
            <div id="turnos-container" class="row"></div>
        </div>
    </div>
</div>

<!-- ✅ Campo oculto para MÚLTIPLES tipos de solicitud -->
<input type="hidden" id="tipoSolicitud" value="51,52">

<!-- Librerías externas -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
<script type="text/javascript" src="https://heiyubai.datarie.info/qz-tray.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>

<!-- ✅ MÓDULOS NUEVOS (en orden) -->
<script src="../public/js/dashboard/dashboard-config.js"></script>
<script src="../public/js/dashboard/dashboard-utils.js"></script>
<script src="../public/js/dashboard/pedido-manager.js"></script>
<script src="../public/js/dashboard/mesa-manager.js"></script>
<script src="../public/js/dashboard/turno-manager.js"></script>
<script src="../public/js/dashboard/modal-builder.js"></script>
<script src="../public/js/dashboard/dashboard-app.js"></script>

<!-- ⚠️ CÓDIGO LEGACY (mantener para compatibilidad) -->
<script src="../public/js/script.js?cache=jkklll"></script>

<!-- Modal COMPARTIDO para Mesas y Turnos -->
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