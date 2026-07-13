/**
 * ui.manager.js - Manejador de UI
 * Ubicación: /menu/js/managers/ui.manager.js
 * Maneja la visibilidad y estado de elementos en la UI
 */

const UIManager = {
    /**
     * Actualizar visibilidad de botones principales
     */
    updateButtonVisibility: function() {
        const hasPendingOrders = window.pedidosPendientes && window.pedidosPendientes.length > 0;
        let pedidoEstado = hasPendingOrders ? window.pedidosPendientes[window.pedidosPendientes.length - 1].estado : null;

        if (hasPendingOrders && pedidoEstado !== 'entregado') {
            $('#makeOrderButton').hide();
            $('#pedidoExistenteButton').show();
        } else {
            const selectedProductCount = ProductService.getSelectedProductCount();
            if (selectedProductCount > 0) {
                $('#selectProductsButton').hide();
                $('#makeOrderButton').show();
            } else {
                $('#selectProductsButton').show();
                $('#makeOrderButton').hide();
            }
        }
    },

    /**
     * Mostrar modal de pedido
     */
    showOrderModal: function() {
        $('#orderFormModal').modal('show');
    },

    /**
     * Ocultar modal de pedido
     */
    hideOrderModal: function() {
        $('#orderFormModal').modal('hide');
    },

    /**
     * Mostrar modal de pedido enviado
     */
    showOrderSentModal: function(orderNumber, turnoNumber) {
        $('#orderNumber').text(orderNumber);
        $('#turnoNumber').text(turnoNumber);
        $('#orderSentModal').modal('show');
    },

    /**
     * Habilitar botón de envío
     */
    enableSubmitButton: function() {
        const $btn = $('#orderForm').find('button[type="submit"]');
        $btn.prop('disabled', false).text('Enviar pedido');
    },

    /**
     * Deshabilitar botón de envío
     */
    disableSubmitButton: function() {
        const $btn = $('#orderForm').find('button[type="submit"]');
        $btn.prop('disabled', true).text('Enviando...');
    },

    /**
     * Limpiar y resetear la UI después de enviar
     */
    resetAfterSubmit: function() {
        ProductService.resetAllProducts();
        this.hideOrderModal();
        this.enableSubmitButton();
    }
};