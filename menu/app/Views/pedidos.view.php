<?php
/**
 * @var int    $tipo_solicitud
 * @var string $celular
 * @var array  $pedidosPendientes
 * @var string $nombreCliente
 * @var string $direccionCliente
 * @var string $emailCliente
 * @var string $cedulaCliente
 * @var string $barrioCliente
 * @var array  $productosOrganizados
 * @var int    $dia_semana
 * @var string $pedido
 */
$baseUrl = dirname($_SERVER['PHP_SELF'] ?? '/');
$baseUrl = rtrim($baseUrl, '/');
if ($baseUrl === '.' || $baseUrl === '\\' || $baseUrl === '/') {
    $baseUrl = '';
}
$pedidosPendientesJSON = $pedidosPendientesJSON ?? '[]';
$logoUrl = 'https://cafeteria.sharrys.com/public/img/logo-pideyapp.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/css/style_prueba.css?cache=egrhu">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta name=viewport content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>

<header class="d-flex justify-content-center py-3">
    <img src="<?php echo htmlspecialchars($logoUrl); ?>"
         style="width: 140px;height:56px"
         alt="Logotipo"
         class="header-logo">
</header>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- BOTÓN ESTADO DE TU PEDIDO (solo si hay pedidos activos) -->
<!-- ═══════════════════════════════════════════════════════ -->
<?php
$tienePedidosActivos = false;
foreach ($pedidosPendientes as $p) {
    if ($p['estado'] !== 'entregado') {
        $tienePedidosActivos = true;
        break;
    }
}
?>
<?php if ($tienePedidosActivos): ?>
<div class="container mt-3 mb-2 text-center">
    <a href="<?= $baseUrl ?>/index.php?route=estado-pedido&numero=<?php echo urlencode($celular); ?>"
       class="btn btn-block"
       style="background: linear-gradient(135deg, #10b981, #059669); color: white; font-weight: 700; font-size: 14px; padding: 14px 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(16,185,129,0.35); display: flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none; max-width: 480px; margin: 0 auto;">
        <i class="fas fa-receipt" style="font-size:18px;"></i>
        Ver estado de tu pedido
        <i class="fas fa-chevron-right" style="font-size:12px; opacity:0.7;"></i>
    </a>
</div>
<?php endif; ?>

<div class="container mb-4">
    <input type="text" id="productSearch" class="form-control" placeholder="Buscar productos...">
    <!-- Carrusel / Nav -->
    <div id="filterCarousel" class="carousel slide" data-ride="carousel">
        <div class="carousel-inner d-flex flex-nowrap" id="scrollContainer">
            <?php include 'nav.php' ?>
        </div>
    </div>
</div>

<?php if ($pedido !== 'wp' && $pedido !== 'fb'): ?>
    <a href="https://wa.me/573174742056" class="whatsapp-button">
        <i class="fab fa-whatsapp"></i>
    </a>
<?php endif; ?>

<div class="container" style="z-index: 1; position: relative">
    <div class="row" id="product-list" style="z-index: 1; position: relative">
        <?php foreach ($productosOrganizados as $producto): ?>
            <?php
            if ($producto['id_pro'] == 51 && ($dia_semana != 6 && $dia_semana != 7)) {
                continue;
            }
            ?>

            <div class="col-sm-6 col-md-6 mb-4 product-card"
                 data-category="<?php echo $producto['cat']; ?>"
                 data-tcomida="<?php echo $producto['tcomida']; ?>"
                 data-prefix="<?php echo $producto['prefijo']; ?>"
                 style="z-index: 1; position: relative">

                <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white h-100">
                    <div class="row g-0 align-items-stretch h-100">
                        <!-- Imagen -->
                        <div class="col-4 p-0">
                            <img
                                src="<?php echo '../menu/src/' . htmlspecialchars($producto['img']); ?>"
                                class="img-fluid h-100 w-100 object-fit-cover"
                                style="object-fit: cover; cursor: pointer;"
                                alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                data-toggle="modal"
                                data-target="#descriptionModal"
                                data-image="<?php echo '../menu/src/' . htmlspecialchars($producto['img']); ?>"
                                data-description="<?php echo htmlspecialchars($producto['descript']); ?>"
                            >
                        </div>

                        <!-- Información -->
                        <div class="col-8 p-3 d-flex flex-column justify-content-center bg-white">
                            <h6 class="fw-bold text-dark mb-3 product-name">
                                <?php echo htmlspecialchars($producto['nombre']); ?>
                            </h6>

                            <?php foreach ($producto['precios'] as $precio): ?>
                                <?php if ($precio['tipo_prod'] && $precio['precio_tipo']): ?>
                                    <div class="d-flex align-items-center justify-content-between mb-2 p-2 rounded bg-light">
                                        <div>
                                            <div class="text-dark fw-semibold" style="font-size: 0.9rem">
                                                <?php echo htmlspecialchars($precio['tipo_prod']); ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.85rem">
                                                $<?php echo number_format($precio['precio_tipo'], 0, '', ','); ?>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button" class="btn btn-outline-secondary btn-sm btn-minus">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number"
                                                   class="form-control form-control-sm quantity-input text-center"
                                                   style="width: 50px; background: #f9f9f9;"
                                                   data-id="<?php echo $producto['id_pro']; ?>"
                                                   data-product-name="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                                   data-prefix="<?php echo $producto['prefijo']; ?>"
                                                   data-product-type="<?php echo htmlspecialchars($precio['tipo_prod']); ?>"
                                                   data-price="<?php echo $precio['precio_tipo']; ?>"
                                                   value="0" min="0" readonly>
                                            <button type="button" class="btn btn-outline-secondary btn-sm btn-plus">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    </div><!-- row -->
</div><!-- container -->

<!-- Modal Formulario -->
<?php include 'partials/orderFormModal.php'; ?>

<!-- Modal Pedido Enviado -->
<?php include 'partials/orderSentModal.php'; ?>

<!-- Botones flotantes -->
<button id="selectProductsButton" class="btn btn-secondary floating-button"
        style="background-color: #500;font-size:10pt">
    Seleccionar productos
</button>
<button id="makeOrderButton" class="btn btn-primary floating-button" style="display:none;">
    Hacer pedido
</button>
<button id="pedidoExistenteButton" class="btn btn-primary floating-button" style="display:none;">
    Ya tienes un pedido
</button>

<!-- Modal descripción del producto -->
<?php include 'partials/descriptionModal.php'; ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lazysizes/5.2.2/lazysizes.min.js" async></script>

<script>
  window.pedidosPendientes = <?= $pedidosPendientesJSON ?>;
</script>
<script>const pedidosPendientes = <?= $pedidosPendientesJSON ?>;</script>

<script src="<?= $baseUrl ?>/js/script.js?cache=fhgf345"></script>

<script>
  $('#descriptionModal').on('show.bs.modal', function (event) {
    const trigger = $(event.relatedTarget);
    const description = trigger.data('description') || 'No hay descripción disponible.';
    $(this).find('#product-description').text(description);
  });
</script>

<!-- SELECT2 - BARRIOS DROPDOWN -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectBarrio = document.getElementById('customerBarrio');
    if (!selectBarrio) return;

    // Guardar barrio inicial seleccionado del cliente
    const barrioInicial = selectBarrio.value;

    // Cargar barrios desde API
    fetch('/menu/api/api_barrios_simple.php')
        .then(response => {
            if (!response.ok) throw new Error('Error en servidor');
            return response.json();
        })
        .then(data => {
            if (data.status === 'success' && data.data && data.data.length > 0) {
                // Limpiar opciones excepto la primera (placeholder)
                while (selectBarrio.options.length > 1) {
                    selectBarrio.remove(1);
                }
                
                // Agregar barrios cargados desde API
                data.data.forEach(barrio => {
                    const option = document.createElement('option');
                    option.value = barrio.nombre_barrio;
                    option.textContent = barrio.nombre_barrio;
                    
                    // Si coincide con el barrio del cliente, marcar como seleccionado
                    if (barrio.nombre_barrio === barrioInicial) {
                        option.selected = true;
                    }
                    
                    selectBarrio.appendChild(option);
                });

                // Si el cliente tenía un barrio pero no está en la lista, agregarlo como "Otro"
                if (barrioInicial && selectBarrio.value !== barrioInicial) {
                    const optionOtro = document.createElement('option');
                    optionOtro.value = barrioInicial;
                    optionOtro.textContent = barrioInicial + ' (Otro)';
                    optionOtro.selected = true;
                    selectBarrio.appendChild(optionOtro);
                }
            }
        })
        .catch(error => {
            console.error('❌ Error cargando barrios:', error);
        })
        .finally(() => {
            // Inicializar Select2 DESPUÉS de cargar los datos
            $(selectBarrio).select2({
                language: 'es',
                placeholder: '-- Selecciona barrio --',
                allowClear: false,
                width: '100%',
                dropdownAutoWidth: true,
                minimumInputLength: 0
            });

            // Establecer el valor inicial en Select2
            if (barrioInicial) {
                $(selectBarrio).val(barrioInicial).trigger('change');
            }
        });
});
</script>

<style>
    .select2-container--default .select2-selection--single {
        height: 38px !important;
        display: flex;
        align-items: center;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 12px;
    }
    .select2-dropdown {
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    .select2-results__option { 
        padding: 10px; 
    }
    .select2-container--default .select2-selection--single .select2-selection__clear {
        display: none;
    }
</style>

</body>
</html>