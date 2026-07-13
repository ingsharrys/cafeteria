/**
 * order.service.js - Servicio de Pedidos
 * Ubicación: /menu/js/services/order.service.js
 * Maneja pedidos, localStorage y datos del cliente
 */

const OrderService = {
    /**
     * Guardar datos del cliente en localStorage
     */
    saveCustomerData: function(customerData) {
        localStorage.setItem('customerName', customerData.name || '');
        localStorage.setItem('customerPhone', customerData.phone || '');
        localStorage.setItem('customerAddress', customerData.address || '');
        localStorage.setItem('customerBarrio', customerData.barrio || '');
        localStorage.setItem('customerEmail', customerData.email || 'sincorreo');
        localStorage.setItem('customerId', customerData.id || '0');
        Utils.log('Datos del cliente guardados en localStorage', 'success');
    },

    /**
     * Cargar datos del cliente desde localStorage
     */
    loadCustomerData: function() {
        return {
            name: localStorage.getItem('customerName') || '',
            phone: localStorage.getItem('customerPhone') || '',
            address: localStorage.getItem('customerAddress') || '',
            barrio: localStorage.getItem('customerBarrio') || '',
            email: localStorage.getItem('customerEmail') || '',
            id: localStorage.getItem('customerId') || '0'
        };
    },

    /**
     * Rellenar formulario con datos guardados
     */
    populateFormWithSavedData: function(customerData) {
        if (customerData.name) $('#customerName').val(customerData.name);
        if (customerData.phone) $('#customerPhone').val(customerData.phone);
        if (customerData.address) $('#customerAddress').val(customerData.address);
        if (customerData.barrio) $('#customerBarrio').val(customerData.barrio);
        if (customerData.email && customerData.email !== 'sincorreo') {
            $('#customerEmail').val(customerData.email);
        }
        if (customerData.id && customerData.id !== '0') {
            $('#customerId').val(customerData.id);
        }
        
        // Desmarcar factura electrónica si hay datos guardados
        if (customerData.email || customerData.id) {
            $('#electronicInvoice').prop('checked', false);
        }

        Utils.log('Formulario rellenado con datos guardados', 'info');
    },

    /**
     * Obtener datos del formulario de pedido
     */
    getOrderFormData: function() {
        return {
            tipoSolicitud: $('#tipo_solicitud').val(),
            customerName: $('#customerName').val(),
            customerPhone: $('#customerPhone').val(),
            customerAddress: $('#customerAddress').val(),
            customerBarrio: $('#customerBarrio').val(),
            customerEmail: $('#customerEmail').val() || 'sincorreo',
            customerId: $('#customerId').val() || '0',
            metodoPago: $('#metodoPago').val(),
            comments: $('#comments').val()
        };
    },

    /**
     * Validar datos del formulario
     */
    validateOrderData: function(formData) {
        const errors = [];

        if (Utils.isEmpty(formData.customerName)) {
            errors.push('Nombre requerido');
        }
        if (Utils.isEmpty(formData.customerPhone)) {
            errors.push('Teléfono requerido');
        }
        if (Utils.isEmpty(formData.metodoPago)) {
            errors.push('Método de pago requerido');
        }

        return {
            isValid: errors.length === 0,
            errors: errors
        };
    },

    /**
     * Enviar pedido al servidor
     */
    submitOrder: function(formData, products) {
        return $.post('index.php?route=pedido-store', {
            name: formData.customerName,
            phone: formData.customerPhone,
            address: formData.customerAddress,
            barrio: formData.customerBarrio,
            email: formData.customerEmail,
            id: formData.customerId,
            products: products,
            tipo_solicitud: formData.tipoSolicitud,
            metodo_pago: formData.metodoPago,
            comments: formData.comments
        });
    },

    /**
     * Combinar método de pago con comentarios
     */
    combinePaymentWithComments: function(metodoPago, comments) {
        let combined = `Metodo de pago: [${metodoPago}]`;
        if (!Utils.isEmpty(comments)) {
            combined += ` - ${comments}`;
        }
        return combined;
    }
};