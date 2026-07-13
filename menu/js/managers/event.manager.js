/**
 * event.manager.js - Manejador de Eventos
 * Ubicación: /menu/js/managers/event.manager.js
 * Delega todos los eventos con $(document).on()
 */

const EventManager = {
    /**
     * Inicializar todos los eventos
     */
    init: function() {
        this.setupProductQuantityEvents();
        this.setupFormEvents();
        this.setupModalEvents();
        this.setupFilterAndSearchEvents();
        Utils.log('EventManager inicializado', 'success');
    },

    /**
     * Eventos de cantidad de productos (+ y -)
     */
    setupProductQuantityEvents: function() {
        // Botón MINUS (-)
        $(document).on('click', '.btn-minus', function() {
            const $input = $(this).siblings('.quantity-input');
            ProductService.decreaseQuantity($input);
            UIManager.updateButtonVisibility();
            Utils.log(`Botón - presionado, cantidad: ${$input.val()}`, 'info');
        });

        // Botón PLUS (+)
        $(document).on('click', '.btn-plus', function() {
            const $input = $(this).siblings('.quantity-input');
            ProductService.increaseQuantity($input);
            UIManager.updateButtonVisibility();
            Utils.log(`Botón + presionado, cantidad: ${$input.val()}`, 'info');
        });

        // Click en imagen del producto
        $(document).on('click', '.product-image', function() {
            const $card = $(this).closest('.product-card');
            const $input = $card.find('.quantity-input');
            ProductService.increaseQuantity($input);
            UIManager.updateButtonVisibility();
        });
    },

    /**
     * Eventos del formulario
     */
    setupFormEvents: function() {
        // Toggle factura electrónica
        $(document).on('change', '#electronicInvoice', function() {
            if ($(this).is(':checked')) {
                $('#invoiceDetails').show();
            } else {
                $('#invoiceDetails').hide();
            }
        });

        // Cambio en radio buttons de opciones
        $(document).on('change', 'input[type=radio][name^="option-"]', function() {
            const parts = $(this).attr('name').split('-');
            const id = parts[1];
            const type = parts.slice(2).join('-');
            
            if (this.value === 'arroz') {
                $(`#suboptions-${id}-${type}`).show();
                $(`#suboptions-${id}-${type} .suboption-radio`).attr('required', true);
            } else {
                $(`#suboptions-${id}-${type}`).hide();
                $(`#suboptions-${id}-${type} .suboption-radio`).removeAttr('required').prop('checked', false);
            }
        });

        // Envío del formulario
        $(document).on('submit', '#orderForm', function(e) {
            e.preventDefault();
            FormHandler.handleOrderSubmit();
        });
    },

    /**
     * Eventos de modales
     */
    setupModalEvents: function() {
        // Botón "Hacer pedido"
        $(document).on('click', '#makeOrderButton', function() {
            FormHandler.showOrderModal();
        });

        // Botón de detalles del producto
        $(document).on('click', '.btn-details', function() {
            const description = $(this).data('description');
            const image = $(this).data('image');
            $('#product-description').text(description);
            $('#product-image-modal').attr('src', image);
            $('#descriptionModal').modal('show');
        });
    },

    /**
     * Eventos de filtro y búsqueda
     */
    setupFilterAndSearchEvents: function() {
        // Filtro de categorías
        $(document).on('click', '.filter-btn', function() {
            const category = $(this).data('category');
            ProductService.filterByCategory(category);
        });

        // Búsqueda de productos
        $(document).on('keyup', '#productSearch', function() {
            const searchText = $(this).val();
            ProductService.searchProducts(searchText);
        });
    }
};