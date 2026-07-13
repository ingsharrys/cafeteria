<!-- Modal del formulario para crear/editar pedido -->
<div class="modal fade" id="orderFormModal" tabindex="-1" role="dialog" aria-labelledby="orderFormModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content" style="background: #000000de; border: 2px solid #5d520673;">
      <div class="modal-header">
        <h5 class="modal-title" style="color:white" id="orderFormModalLabel">Detalles del pedido</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true" style="color: white;">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="orderForm">
          <!-- input hidden para tipo_solicitud -->
          <input type="hidden" id="tipo_solicitud" name="tipo_solicitud" value="<?php echo $tipo_solicitud ?? ''; ?>">

          <div class="form-group">
            <label for="customerName" style="color:#fff">Nombre</label>
            <input type="text" value="<?php echo htmlspecialchars($nombreCliente ?? ''); ?>" class="form-control" id="customerName" required>
          </div>

          <div class="form-group">
            <label for="customerPhone" style="color:#fff">Teléfono</label>
            <input type="tel" class="form-control" value="<?php echo htmlspecialchars($celular ?? ''); ?>" id="customerPhone" required>
          </div>

          <?php if (($tipo_solicitud ?? null) == 50): ?>
            <!-- Mostrar estos campos solo si NO es tipo_solicitud = 51 -->
            <div class="form-group">
              <label for="customerAddress" style="color:#fff">Dirección</label>
              <input type="text" value="<?php echo htmlspecialchars($direccionCliente ?? ''); ?>" class="form-control" id="customerAddress" required>
            </div>
            
            <!-- 🆕 SELECT2 PARA BARRIOS -->
            <div class="form-group">
              <label for="customerBarrio" style="color:#fff">
                Barrio 
                <small style="color:#aaa;">(Si no encuentra el barrio seleccione otro, y escriba el barrio en comentarios)</small>
              </label>
              <select id="customerBarrio" name="customerBarrio" style="width: 100%;" required class="form-control">
                <option value="">-- Selecciona barrio --</option>
                <?php if (!empty($barrioCliente)): ?>
                  <option value="<?php echo htmlspecialchars($barrioCliente); ?>" selected>
                    <?php echo htmlspecialchars($barrioCliente); ?>
                  </option>
                <?php endif; ?>
              </select>
            </div>
          <?php endif; ?>
          
          <!-- ═══════════════════════════════════════════ -->
          <!-- ✅ MÉTODO DE PAGO                  -->
          <!-- ═══════════════════════════════════════════ -->
          <div class="form-group">
            <label for="metodoPago" style="color:#fff"><strong>Método de Pago</strong></label>
            <select class="form-control" id="metodoPago" required>
              
              <!-- DOMICILIOS (tipo_solicitud = 50): Solo Efectivo y Transferencia -->
              <?php if (($tipo_solicitud ?? null) == 50): ?>
                <option value="Transferencia" selected>💳 Transferencia</option>
              
              <!-- OTROS TIPOS: Todos excepto Crédito -->
              <?php else: ?>
                <option value="Transferencia" selected>💳 Transferencia</option>
              <?php endif; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="comments" style="color:#fff">Comentarios del pedido</label>
            <textarea class="form-control" id="comments" name="comments" rows="3" placeholder="Ej: Sin picante, instrucciones especiales, etc."></textarea>
          </div>

          <!-- Aquí se inyectan los productos seleccionados (ver script.js) -->
          <div id="selectedProductsContainer"></div>

          <button type="submit" class="btn btn-primary" style="width: 100%;">✓ Enviar pedido</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  // Mostrar/ocultar detalles de factura electrónica
  document.addEventListener('DOMContentLoaded', function() {
    const electronicInvoiceCheckbox = document.getElementById('electronicInvoice');
    const invoiceDetails = document.getElementById('invoiceDetails');

    if (electronicInvoiceCheckbox) {
      electronicInvoiceCheckbox.addEventListener('change', function() {
        if (this.checked) {
          invoiceDetails.style.display = 'block';
        } else {
          invoiceDetails.style.display = 'none';
        }
      });
    }
  });
</script>