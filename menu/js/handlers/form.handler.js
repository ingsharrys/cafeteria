/**
 * form.handler.js - Manejador de Formulario
 * Ubicación: /menu/js/handlers/form.handler.js
 * Maneja todo lo relacionado con el formulario de pedidos
 */

const FormHandler = {
    /**
     * Mostrar modal del formulario de pedido
     */
    showOrderModal: function() {
        const selectedProducts = ProductService.getSelectedProducts();

        if (selectedProducts.length === 0) {
            Utils.alert('Por favor selecciona al menos un producto', 'warning');
            return;
        }

        // Generar HTML de productos
        let productListHtml = '';
        selectedProducts.forEach(product => {
            productListHtml += this.generateProductFormHtml(product);
        });

        $('#selectedProductsContainer').html(productListHtml);
        UIManager.showOrderModal();
        Utils.log('Modal de pedido mostrado', 'success');
    },

    /**
     * Generar HTML de producto en el modal
     */
    generateProductFormHtml: function(product) {
        const escapedType = Utils.escapeSelector(product.type);
        
        let html = `
            <div class="form-group" style="background: #4b4b4b;padding: 2%;border-radius: 10px;">
                <label style="color:#fff">- ${product.name} - ${product.type} (Cantidad: ${product.quantity})</label>
        `;

        // Opciones si tcomida = 2 (arroz/papa)
        if ([2].includes(product.tcomida)) {
            html += `
                <div class="form-check">
                    <input class="form-check-input option-radio" type="radio" 
                           name="option-${product.id}-${escapedType}" 
                           id="option-${product.id}-${escapedType}-arroz" 
                           value="arroz" required>
                    <label class="form-check-label" for="option-${product.id}-${escapedType}-arroz">Arroz</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input option-radio" type="radio" 
                           name="option-${product.id}-${escapedType}" 
                           id="option-${product.id}-${escapedType}-papa" 
                           value="papa" required>
                    <label class="form-check-label" for="option-${product.id}-${escapedType}-papa">Papa</label>
                </div>
                <div id="suboptions-${product.id}-${escapedType}" class="suboptions" style="display:none;">
                    <div class="form-check">
                        <input class="form-check-input suboption-radio" type="radio" 
                               name="suboption-${product.id}-${escapedType}" 
                               id="suboption-${product.id}-${escapedType}-amarillo" 
                               value="amarillo">
                        <label class="form-check-label" style="color:#fff" for="suboption-${product.id}-${escapedType}-amarillo">Amarillo</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input suboption-radio" type="radio" 
                               name="suboption-${product.id}-${escapedType}" 
                               id="suboption-${product.id}-${escapedType}-cafe" 
                               value="cafe">
                        <label class="form-check-label" style="color:#fff" for="suboption-${product.id}-${escapedType}-cafe">Café</label>
                    </div>
                </div>
            `;
        }

        // Opciones si tcomida = 1
        if ([1].includes(product.tcomida)) {
            html += `
                <div class="form-check">
                    <input class="form-check-input suboption-radio" type="radio" 
                           name="suboption-${product.id}-${escapedType}" 
                           id="suboption-${product.id}-${escapedType}-amarillo" 
                           value="amarillo" required>
                    <label class="form-check-label" style="color:#fff" for="suboption-${product.id}-${escapedType}-amarillo">Amarillo</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input suboption-radio" type="radio" 
                           name="suboption-${product.id}-${escapedType}" 
                           id="suboption-${product.id}-${escapedType}-cafe" 
                           value="cafe" required>
                    <label class="form-check-label" style="color:#fff" for="suboption-${product.id}-${escapedType}-cafe">Café</label>
                </div>
            `;
        }

        html += `</div>`;
        return html;
    },

    /**
     * Manejar envío del formulario
     */
    handleOrderSubmit: function() {
        // Obtener datos
        const formData = OrderService.getOrderFormData();
        const selectedProducts = ProductService.getSelectedProducts();

        // Validar
        const validation = OrderService.validateOrderData(formData);
        if (!validation.isValid) {
            Utils.alert(validation.errors.join('\n'), 'error');
            return;
        }

        // Procesar productos con opciones
        const processedProducts = this.processProductsWithOptions(selectedProducts);

        // Combinar método de pago con comentarios
        formData.comments = OrderService.combinePaymentWithComments(
            formData.metodoPago,
            formData.comments
        );

        // Guardar datos del cliente
        OrderService.saveCustomerData({
            name: formData.customerName,
            phone: formData.customerPhone,
            address: formData.customerAddress,
            barrio: formData.customerBarrio,
            email: formData.customerEmail,
            id: formData.customerId
        });

        // Deshabilitar botón
        UIManager.disableSubmitButton();

        // Enviar al servidor
        OrderService.submitOrder(formData, processedProducts)
            .done((response) => this.handleOrderSuccess(response))
            .fail((jqXHR, textStatus, errorThrown) => this.handleOrderError(textStatus, errorThrown));
    },

    /**
     * Procesar productos con sus opciones seleccionadas
     */
    processProductsWithOptions: function(selectedProducts) {
        return selectedProducts.map(product => {
            const escapedType = Utils.escapeSelector(product.type);
            const productOption = $(`input[name="option-${product.id}-${escapedType}"]:checked`).val() || null;
            const productSubOption = $(`input[name="suboption-${product.id}-${escapedType}"]:checked`).val() || null;

            return {
                ...product,
                option: productOption,
                suboption: productSubOption
            };
        });
    },

    /**
     * Manejar éxito en el envío del pedido
     */
    handleOrderSuccess: function(response) {
        try {
            const res = JSON.parse(response);
            
            if (res.status === 'success') {
                Utils.log('Pedido enviado exitosamente', 'success');
                
                // Reset UI
                UIManager.resetAfterSubmit();
                
                // Mostrar modal de confirmación
                UIManager.showOrderSentModal(res.order_number, res.turno);
                
                // Recargar página después de 2 segundos
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                Utils.alert(res.message || 'Error desconocido', 'error');
                UIManager.enableSubmitButton();
            }
        } catch (e) {
            Utils.alert('Error al procesar respuesta del servidor', 'error');
            UIManager.enableSubmitButton();
        }
    },

    /**
     * Manejar error en el envío del pedido
     */
    handleOrderError: function(textStatus, errorThrown) {
        Utils.log(`Error AJAX: ${textStatus} - ${errorThrown}`, 'error');
        Utils.alert(`Error al enviar pedido: ${textStatus}`, 'error');
        UIManager.enableSubmitButton();
    }
};