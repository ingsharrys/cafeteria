 // Saber si hay pedidos pendientes

$(document).ready(function() {

    function escapeSelector(selector) {
        return selector.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\$&");
    }

    // Funci車n para actualizar el estado de los botones
    function updateButtons() {
        const hasPendingOrders = pedidosPendientes.length > 0; 
        let pedidoEstado = hasPendingOrders ? pedidosPendientes[pedidosPendientes.length - 1].estado : null;
        if (hasPendingOrders && pedidoEstado !== 'entregado') {
            $('#makeOrderButton').hide(); 
            $('#pedidoExistenteButton').show();
        } else {
            // L車gica existente para manejar la visibilidad de botones seg迆n los productos seleccionados
            const selectedProducts = $('.quantity-input').filter(function () {
                return $(this).val() > 0;
            });
            if (selectedProducts.length > 0) {
                $('#selectProductsButton').hide();
                $('#makeOrderButton').show();
            } else {
                $('#selectProductsButton').show();
                $('#makeOrderButton').hide();
            }
        }
    }

    // Funci車n para aumentar la cantidad
    function aumentarCantidad($input, $checkbox, $productSelected) {
        $input.val(parseInt($input.val()) + 1);
        if ($input.val() > 0) {
            $checkbox.prop('checked', true);
            $productSelected.addClass('show');
        }
        updateButtons();
    }

    // Cargar datos desde localStorage
    // 🆕 OBTENER TELÉFONO ACTUAL DE LA URL
const urlParams = new URLSearchParams(window.location.search);
const numeroActual = urlParams.get('numero') || '';

// Cargar datos desde localStorage SOLO si el teléfono coincide
const telefonoEnLocalStorage = localStorage.getItem('customerPhone') || '';

let customerName = '';
let customerPhone = '';
let customerAddress = '';
let customerBarrio = '';
let customerEmail = '';
let customerId = '';

// ✅ SOLO usar localStorage si el teléfono es el mismo
if (telefonoEnLocalStorage === numeroActual) {
    customerName = localStorage.getItem('customerName') || '';
    customerPhone = localStorage.getItem('customerPhone') || '';
    customerAddress = localStorage.getItem('customerAddress') || '';
    customerBarrio = localStorage.getItem('customerBarrio') || '';
    customerEmail = localStorage.getItem('customerEmail') || '';
    customerId = localStorage.getItem('customerId') || '';
} else {
    // 🆕 Si cambió el teléfono, limpiar localStorage
    localStorage.clear();
}

// Rellenar formulario
if (customerName) $('#customerName').val(customerName);
if (customerPhone) $('#customerPhone').val(customerPhone);
if (customerAddress) $('#customerAddress').val(customerAddress);
if (customerBarrio) $('#customerBarrio').val(customerBarrio);
if (customerEmail && customerEmail !== 'sincorreo') $('#customerEmail').val(customerEmail);
if (customerId && customerId !== '0') $('#customerId').val(customerId);
    if (customerEmail || customerId) {
        $('#electronicInvoice').prop('checked', false);
    }

    // Mostrar/ocultar campos de factura electr車nica
    $('#electronicInvoice').change(function() {
        if ($(this).is(':checked')) {
            $('#invoiceDetails').show();
        } else {
            $('#invoiceDetails').hide();
        }
    });

    // Bot車n -
    $(document).on('click', '.btn-minus', function() {
        var $input = $(this).siblings('.quantity-input');
        var value = parseInt($input.val());
        if (value > 0) {
            $input.val(value - 1);
        }
        var $checkbox = $(this).closest('.card-body').find('.product-checkbox');
        var $productSelected = $(this).closest('.card').find('.product-selected');
        if ($input.val() == 0) {
            $checkbox.prop('checked', false);
            $productSelected.removeClass('show');
        }
        updateButtons();
    });

    // Bot車n +
    $(document).on('click', '.btn-plus', function() {
        var $input = $(this).siblings('.quantity-input');
        var $checkbox = $(this).closest('.card-body').find('.product-checkbox');
        var $productSelected = $(this).closest('.card').find('.product-selected');
        aumentarCantidad($input, $checkbox, $productSelected);
    });

    // Aumentar cantidad al hacer clic en la imagen
    $(document).on('click', '.product-image', function() {
        var $cardBody = $(this).closest('.card-body');
        var $input = $cardBody.find('.quantity-input');
        var $checkbox = $cardBody.find('.product-checkbox');
        var $productSelected = $(this).closest('.card').find('.product-selected');
        aumentarCantidad($input, $checkbox, $productSelected);
    });

    // Inicializar estado de botones
    updateButtons();

    // Al hacer clic en 'Hacer pedido'
    // Al hacer clic en 'Hacer pedido'
$('#makeOrderButton').click(function() {
    const selectedProducts = [];

    $('.quantity-input').each(function() {
        const quantity = parseInt($(this).val());
        if (quantity > 0) {
            const productId = $(this).data('id');
            const productName = $(this).data('product-name');
            const productPrice = $(this).data('price');
            const productType = $(this).data('product-type');
            const productCard = $(this).closest('.product-card');
            const productPrefix = productCard.data('prefix');

            // ✅ Capturar correctamente el valor de tcomida
            let tcomida = $(this).data('tcomida');  // 🔥 Captura el data-tcomida directamente

            // 🔍 Si `tcomida` no se encuentra en el input, intenta obtenerlo desde `product-card`
            if (!tcomida) {
                tcomida = productCard.data('tcomida') || "Desconocido";
            }

            console.log(`Producto: ${productName}, Tipo Comida: ${tcomida}`); // 🔍 Depuración

            selectedProducts.push({
                id: productId,
                name: productName,
                price: productPrice,
                type: productType,
                quantity: quantity,
                tcomida: tcomida, // ✅ Ahora se toma correctamente como un número o texto
                prefix: productPrefix
            });
        }
    });

    console.log("Productos seleccionados:", selectedProducts); // 🔍 Revisar en consola



 

        let productListHtml = '';
        selectedProducts.forEach(product => {
            // << USAMOS TEMPLATE STRINGS >>
            
            productListHtml += `
                <div class="form-group" style="background: #4b4b4b;padding: 2%;border-radius: 10px;">
                    <label style="color:#fff">- ${product.name} - ${product.type} (Cantidad: ${product.quantity})</label>
                    ${[2].includes(product.tcomida) ? `
                            <div class="form-check">
                                <input class="form-check-input option-radio" type="radio" name="option-${product.id}-${product.type}" id="option-${product.id}-${product.type}-arroz" value="arroz" required>
                                <label class="form-check-label" for="option-${product.id}-${product.type}-arroz">Arroz</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input option-radio" type="radio" name="option-${product.id}-${product.type}" id="option-${product.id}-${product.type}-papa" value="papa" required>
                                <label class="form-check-label" for="option-${product.id}-${product.type}-papa">Papa</label>
                            </div>
                            <div id="suboptions-${product.id}-${product.type}" class="suboptions" style="display:none;">
                                <div class="form-check">
                                    <input class="form-check-input suboption-radio" type="radio" name="suboption-${product.id}-${product.type}" id="suboption-${product.id}-${product.type}-amarillo" value="amarillo">
                                    <label class="form-check-label" style="color:#fff"  for="suboption-${product.id}-${product.type}-amarillo">Amarillo</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input suboption-radio" type="radio" name="suboption-${product.id}-${product.type}" id="suboption-${product.id}-${product.type}-cafe" value="cafe">
                                    <label class="form-check-label" style="color:#fff" for="suboption-${product.id}-${product.type}-cafe">Café</label>
                                </div>
                            </div>
                        ` : ''
                    }
                    ${
                        [1].includes(product.tcomida) ? `
                            <div class="form-check">
                                <input class="form-check-input suboption-radio" type="radio" name="suboption-${product.id}-${product.type}" id="suboption-${product.id}-${product.type}-amarillo" value="amarillo" required>
                                <label class="form-check-label" style="color:#fff"  for="suboption-${product.id}-${product.type}-amarillo">Amarillo</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input suboption-radio" type="radio" name="suboption-${product.id}-${product.type}" id="suboption-${product.id}-${product.type}-cafe" value="cafe" required>
                                <label class="form-check-label" style="color:#fff" for="suboption-${product.id}-${product.type}-cafe">Café</label>
                            </div>
                        ` : ''
                    }
                </div>
            `;
        });

        $('#selectedProductsContainer').html(productListHtml);

        // Mostrar el modal
        $('#orderFormModal').modal('show');

        // Manejo de suboptions
        $('input[type=radio][name^="option-"]').change(function() {
            const [_, id, type] = $(this).attr('name').split('-');
            if (this.value === 'arroz') {
                $(`#suboptions-${id}-${type}`).show();
                $(`#suboptions-${id}-${type} .suboption-radio`).attr('required', true);
            } else {
                $(`#suboptions-${id}-${type}`).hide();
                $(`#suboptions-${id}-${type} .suboption-radio`).removeAttr('required');
                $(`#suboptions-${id}-${type} .suboption-radio`).prop('checked', false);
            }
        });
    });

    // Submit del formulario de pedido
    $('#orderForm').submit(function(e) {
        e.preventDefault();
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true).text('Enviando...');

        // Obt谷n todos los datos
        const tipoSolicitud = $('#tipo_solicitud').val();
        const customerName = $('#customerName').val();
        const customerPhone = $('#customerPhone').val();
        const customerAddress = $('#customerAddress').val();
        const customerBarrio = $('#customerBarrio').val();
        const customerEmail = $('#customerEmail').val() || 'sincorreo';
        const customerId = $('#customerId').val() || '0';
        const metodoPago = $('#metodoPago').val();

        // Comprobante de transferencia (si aplica)
        const evidenceInput = document.getElementById('paymentEvidence');
        const paymentEvidence = (evidenceInput && evidenceInput.files.length > 0)
            ? evidenceInput.files[0]
            : null;

        // Si el método es Transferencia, la imagen del comprobante es obligatoria
        if (metodoPago === 'Transferencia' && !paymentEvidence) {
            alert('Debes adjuntar la imagen del comprobante de la transferencia');
            submitButton.prop('disabled', false).text('Enviar pedido');
            return;
        }
        if (paymentEvidence && !paymentEvidence.type.startsWith('image/')) {
            alert('El comprobante debe ser una imagen (jpg, png, etc.)');
            submitButton.prop('disabled', false).text('Enviar pedido');
            return;
        }

        // Registrar el método de pago dentro de los comentarios (visible en caja)
        let comments = $('#comments').val();
        comments = 'Metodo de pago: [' + metodoPago + ']' + (comments ? ' - ' + comments : '');

        // Productos seleccionados
        const selectedProducts = [];
        $('.quantity-input').each(function() {
            const quantity = parseInt($(this).val());
            if (quantity > 0) {
                const productId = $(this).data('id');
                const productName = $(this).data('product-name');
                const productPrice = $(this).data('price');
                const productType = $(this).data('product-type');
                const escapedProductType = escapeSelector(productType);
                const productCard = $(this).closest('.product-card');
                const productPrefix = productCard.data('prefix');

                // <<< CORREGIDO >>>
                const productOption = $(`input[name="option-${productId}-${escapedProductType}"]:checked`).val() || null;
                const productSubOption = $(`input[name="suboption-${productId}-${escapedProductType}"]:checked`).val() || null;

                selectedProducts.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    type: productType,
                    quantity: quantity,
                    prefix: productPrefix,
                    option: productOption,
                    suboption: productSubOption
                });
            }
        });

        // Petici車n AJAX
        // Se usa FormData para poder adjuntar la imagen del comprobante
        const fd = new FormData();
        fd.append('name', customerName);
        fd.append('phone', customerPhone);
        fd.append('address', customerAddress);
        fd.append('barrio', customerBarrio);
        fd.append('email', customerEmail);
        fd.append('id', customerId);
        fd.append('tipo_solicitud', tipoSolicitud);
        fd.append('metodo_pago', metodoPago);
        fd.append('comments', comments);
        fd.append('products', JSON.stringify(selectedProducts));
        if (paymentEvidence) {
            fd.append('payment_evidence', paymentEvidence);
        }

        $.ajax({
            url: 'index.php?route=pedido-store',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false
        }).done(function(response) {
            console.log("Respuesta del servidor:", response);
            const res = JSON.parse(response);
            if (res.status === 'success') {
                // Limpiar inputs
                $('.quantity-input').val(0);
                $('.product-checkbox').prop('checked', false);
                $('.product-selected').removeClass('show');

                // Cerrar modal
                $('#orderFormModal').modal('hide');

                // Mostrar modal de "Pedido enviado"
                $('#orderSentModal').modal('show');
                $('#orderNumber').text(res.order_number);
                $('#turnoNumber').text(res.turno);

                // Recargar la p芍gina tras 2 seg
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                console.error(res.message);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
            submitButton.prop('disabled', false).text('Enviar pedido');
        });
    });

    // Ver detalles del producto
    $('.btn-details').click(function() {
        const description = $(this).data('description');
        const image = $(this).data('image');
        $('#product-description').text(description);
        $('#product-image-modal').attr('src', image);
        $('#descriptionModal').modal('show');
    });

    // Filtrar categor赤a
    $(document).on('click', '.filter-btn', function() {
        let category = $(this).data('category');
        console.log("Category clicked: " + category);
        if (category === 'all') {
            $('.product-card').show();
        } else {
            $('.product-card').hide();
            $(`.product-card[data-category="${category}"]`).show();
        }
    });

    $('#filterCarousel .carousel-item .filter-btn').click(function() {
        let category = $(this).data('category');
        if (category === 'all') {
            $('.product-card').show();
        } else {
            $('.product-card').hide();
            $(`.product-card[data-category="${category}"]`).show();
        }
    });

    // B迆squeda
    $('#productSearch').on('keyup', function() {
        let searchText = $(this).val().toLowerCase();
        if (searchText === '') {
            $('.product-card').show();
        } else {
            $('.product-card').hide();
            $('.product-card').filter(function() {
                let productName = $(this).find('.product-name').text().toLowerCase();
                return productName.includes(searchText);
            }).show();
        }
    });

    // Animaci車n en el bot車n + (opcional)
    let $button = $('.btn-plus');
    $button.addClass('grow-shrink-animation');
    setTimeout(function() {
        $button.removeClass('grow-shrink-animation');
    }, 10000); 
});