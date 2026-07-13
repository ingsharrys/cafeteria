/**
 * product.service.js - Servicio de Productos
 * Ubicación: /menu/js/services/product.service.js
 * Maneja toda la lógica de productos (cantidad, selección, cálculos)
 */

const ProductService = {
    /**
     * Obtener todos los productos seleccionados
     */
    getSelectedProducts: function() {
        const selectedProducts = [];

        $('.quantity-input').each(function() {
            const quantity = Utils.parseInt($(this).val());
            
            if (quantity > 0) {
                const productId = $(this).data('id');
                const productName = $(this).data('product-name');
                const productPrice = $(this).data('price');
                const productType = $(this).data('product-type');
                const productCard = $(this).closest('.product-card');
                const productPrefix = productCard.data('prefix');
                let tcomida = productCard.data('tcomida');

                if (!tcomida) {
                    tcomida = $(this).data('tcomida') || "Desconocido";
                }

                selectedProducts.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    type: productType,
                    quantity: quantity,
                    tcomida: tcomida,
                    prefix: productPrefix
                });
            }
        });

        return selectedProducts;
    },

    /**
     * Contar productos seleccionados
     */
    getSelectedProductCount: function() {
        return this.getSelectedProducts().length;
    },

    /**
     * Aumentar cantidad
     */
    increaseQuantity: function($input) {
        let currentVal = Utils.parseInt($input.val());
        $input.val(currentVal + 1);
        this.updateProductCard($input);
    },

    /**
     * Disminuir cantidad
     */
    decreaseQuantity: function($input) {
        let currentVal = Utils.parseInt($input.val());
        if (currentVal > 0) {
            $input.val(currentVal - 1);
        }
        this.updateProductCard($input);
    },

    /**
     * Actualizar estado visual de la tarjeta del producto
     */
    updateProductCard: function($input) {
        const $card = $input.closest('.product-card');
        const $checkbox = $card.find('.product-checkbox');
        const $productSelected = $card.find('.product-selected');
        const quantity = Utils.parseInt($input.val());

        if (quantity > 0) {
            $checkbox.prop('checked', true);
            $productSelected.addClass('show');
        } else {
            $checkbox.prop('checked', false);
            $productSelected.removeClass('show');
        }
    },

    /**
     * Resetear todos los productos
     */
    resetAllProducts: function() {
        $('.quantity-input').val(0);
        $('.product-checkbox').prop('checked', false);
        $('.product-selected').removeClass('show');
        Utils.log('Productos reseteados', 'info');
    },

    /**
     * Filtrar productos por categoría
     */
    filterByCategory: function(category) {
        if (category === 'all') {
            $('.product-card').show();
        } else {
            $('.product-card').hide();
            $(`.product-card[data-category="${category}"]`).show();
        }
        Utils.log(`Filtro: ${category}`, 'info');
    },

    /**
     * Buscar productos
     */
    searchProducts: function(searchText) {
        searchText = searchText.toLowerCase();
        
        if (Utils.isEmpty(searchText)) {
            $('.product-card').show();
            return;
        }

        $('.product-card').hide();
        $('.product-card').filter(function() {
            let productName = $(this).find('.card-title label').text().toLowerCase();
            return productName.includes(searchText);
        }).show();

        Utils.log(`Búsqueda: "${searchText}"`, 'info');
    }
};