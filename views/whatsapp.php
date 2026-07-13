<!-- 
    whatsapp.php - VISTA DE DOMICILIOS (VERSIÓN LIMPIA DE CERO)
    Modal para seleccionar domiciliario y precio
-->
<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <!-- Formulario para registrar nuevo pedido a domicilio -->
            <form action="../menu/" method="get" onsubmit="openPopupWindow(this); return false;">
                <input type="hidden" name="route" value="pedidos">
                <input type="hidden" name="pedido" value="wp">
                
                <div class="form-group">
                    <label for="orderNumberInput">Número del Celular:</label>
                    <input type="number" class="form-control" id="orderNumberInput" name="numero" 
                           placeholder="Ingrese el número del celular" required>
                </div>
                <button type="submit" class="btn btn-primary">Registrar pedido Domicilio</button>
            </form>
            
            <!-- Tabla de domicilios (este contenido se genera desde JavaScript/app.js) -->
            <h3 class="mt-5">🏠 Pedidos a Domicilio</h3>
            <div id="turnos-container" class="row"></div>
        </div>
    </div>
</div>

<!-- Campo oculto para tipo de solicitud -->
<input type="hidden" id="tipoSolicitud" value="50">

<!-- ═══════════════════════════════════════════════════════
     MODAL PARA DOMICILIARIOS
     ═══════════════════════════════════════════════════════ -->
<div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">🚗 Asignar Domiciliario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="modal-content">
                <!-- Contenido dinámico desde domicilios.js -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Actualizar</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════════════════════ -->

<!-- Librerías externas -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
<script type="text/javascript" src="https://heiyubai.datarie.info/qz-tray.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>

<!-- Scripts del proyecto -->
<script src="../public/js/domicilios.js?caches=458333"></script>
<script src="../public/js/global-compat.js?cache=99897"></script>
<script src="/js/modal-domiciliarios-handler.js"></script>
<script type="module" src="../public/js/app.js?dt=hjj77"></script>



