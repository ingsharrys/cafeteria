<style>
    .btn.btn-secondary.btn-block { width: 100% !important; }
</style>

<div class="container mt-5">
    <div class="row">
        <!-- Columna para Mesas -->
        <div class="col-md-12">
            <h3>Mesas</h3>
            <div id="mesas-container" class="row"></div>
        </div>

        <!-- Columna para Turnos -->
        <div class="col-md-12">
            <br>
            <form action="../menu/" method="get" onsubmit="openPopupWindow(this); return false;">
                <input type="hidden" name="route" value="pedidos">
                <input type="hidden" name="pedido" value="qr">
                
                <div class="form-group">
                    <label for="orderNumberInput">Número del celular:</label>
                    <input type="number" class="form-control" id="orderNumberInput" name="numero" placeholder="Ingrese el número del celular" required>
                </div>
                <button type="submit" class="btn btn-primary">Registrar pedido Turno</button>
            </form>
            <h3>Turnos</h3>
            <div id="turnos-container" class="row"></div>
        </div>
    </div>
</div>

<!-- Campo oculto para almacenar tipoSolicitud -->
<input type="hidden" id="tipoSolicitud" value="51">

<!-- Modal para detalles de pedidos -->
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

<!-- Librerías externas (impresión) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
<script type="text/javascript" src="https://heiyubai.datarie.info/qz-tray.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>

<!-- ══════════════════════════════════════════════════════
     SISTEMA DE SCRIPTS
     
     1. global-compat.js → Funciones globales (procesarMesa, 
        cambiarEstadoTurnero, printInvoicepc, etc.)
     
     2. app.js (module) → Sistema modular que:
        - Detecta la página (dashboard)
        - Inicializa MesasController y TurnosController
        - Hace fetch() a la API
        - Renderiza datos en el DOM
     
     ⚠️ NO cargar script.js aquí, duplicaría las llamadas
     ══════════════════════════════════════════════════════ -->
<script src="../public/js/global-compat.js?cache=ef44354"></script>
<script type="module" src="../public/js/app.js?cachef=43546"></script>