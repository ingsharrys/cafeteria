<?php
/**
 * caja_tm.php - Caja / Pago de Pedido
 * UBICACIÓN: views/caja_tm.php
 * 
 * ✅ Muestra productos anulados (tachados, rojo) para trazabilidad
 * ✅ Total neto = activos - anulados
 * ✅ Impresión incluye anulados
 */

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    echo '<div class="alert alert-danger m-4">Error de conexión: ' . $e->getMessage() . '</div>';
    return;
}

$cajeros = \Core\Session::get('cajero') ?? \Core\Session::get('username') ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['numero_pedido'])) {
    echo '<div class="alert alert-warning m-4">Datos incompletos. <a href="index.php?page=llamadas.php">Volver</a></div>';
    return;
}

$numero_pedido = (int)$_POST['numero_pedido'];
if (!$numero_pedido) {
    echo '<div class="alert alert-danger m-4">Número de pedido inválido. <a href="index.php?page=llamadas.php">Volver</a></div>';
    return;
}

// ─── Verificar turno o mesa ────────────────────────
$stVer = $db->prepare("SELECT COUNT(*) FROM turnero WHERE id_pedido = ?");
$stVer->execute([$numero_pedido]);
$es_turno = $stVer->fetchColumn() > 0;

// ─── Obtener TODOS los productos (incluidos anulados) ──
if ($es_turno) {
    $query_pedido = "
        SELECT pr.prefijo, pr.nombre AS nombre_producto, p.cantidad, p.tipo_solicitud,
               prp.precio AS precio_producto, p.detalle, p.tipo_producto,
               COALESCE(c.cliente, 'Cliente de Mesa') AS nombre_cliente,
               COALESCE(c.celular, 'Sin celular') AS celular_cliente,
               p.mesero
        FROM pedidos p
        JOIN productos pr ON p.id_pro = pr.id_pro
        JOIN precios prp ON pr.id_pro = prp.idproduc AND prp.tipo_prod = p.tipo_producto
        JOIN turnero t ON p.numero_pedido = t.id_pedido
        LEFT JOIN clientes c ON t.id_cliente = c.id
        WHERE p.numero_pedido = ?
        ORDER BY p.id_pedido ASC
    ";
} else {
    $query_pedido = "
        SELECT pr.prefijo, pr.nombre AS nombre_producto, p.cantidad, p.tipo_solicitud,
               prp.precio AS precio_producto, p.detalle, p.tipo_producto,
               'Cliente de Mesa' AS nombre_cliente, 'Sin celular' AS celular_cliente,
               p.mesero
        FROM pedidos p
        JOIN productos pr ON p.id_pro = pr.id_pro
        JOIN precios prp ON pr.id_pro = prp.idproduc AND prp.tipo_prod = p.tipo_producto
        WHERE p.numero_pedido = ?
        ORDER BY p.id_pedido ASC
    ";
}

$stmt_pedido = $db->prepare($query_pedido);
$stmt_pedido->execute([$numero_pedido]);
$todos_productos = $stmt_pedido->fetchAll(PDO::FETCH_ASSOC);

if (empty($todos_productos)) {
    echo '<div class="alert alert-warning m-4">No se encontraron productos en el pedido #' . $numero_pedido . '. <a href="index.php?page=llamadas.php">Volver</a></div>';
    return;
}

$nombre_cliente  = $todos_productos[0]['nombre_cliente'] ?? 'Cliente de Mesa';
$celular_cliente = $todos_productos[0]['celular_cliente'] ?? 'Sin celular';
$tipo_solicitud  = $todos_productos[0]['tipo_solicitud'] ?? null;
$idsmese         = $_SESSION['usuario']['id_mese'] ?? '';

// ─── Domicilio ─────────────────────────────────────
$costo_domicilio = 0;
if ($tipo_solicitud == 50) {
    try {
        $stDom = $db->prepare("SELECT precio FROM domicilios WHERE id_pedido = :id LIMIT 1");
        $stDom->execute([':id' => $numero_pedido]);
        $dom = $stDom->fetchColumn();
        if ($dom) $costo_domicilio = (float)$dom;
    } catch (PDOException $e) {}
}

// ─── Clasificar y calcular total neto ──────────────
$total_productos = 0;
foreach ($todos_productos as &$d) {
    $cant = (int)$d['cantidad'];
    $precio = (float)$d['precio_producto'];
    $d['subtotal'] = $cant * $precio;
    $d['es_anulado'] = ($cant < 0) || (strpos($d['detalle'] ?? '', 'ANULADO:') === 0);
    $total_productos += $d['subtotal']; // Negativos restan automáticamente
}
unset($d);

$total_a_pagar = $total_productos;

// ─── Pago existente ────────────────────────────────
$stCheck = $db->prepare("SELECT COUNT(*) AS cnt, costo, m_pago FROM caja WHERE id_pedidoc = :id");
$stCheck->execute([':id' => $numero_pedido]);
$pagoData = $stCheck->fetch(PDO::FETCH_ASSOC);

$pago_existente = ($pagoData['cnt'] ?? 0) > 0;
$monto_pagado   = $pagoData['costo'] ?? 0;
$metodo_pagado  = $pagoData['m_pago'] ?? '';

// ─── Créditos y abonos ────────────────────────────
$abonos = [];
$abonosTotal = 0;
$idCredito = null;

if ($metodo_pagado === 'credito') {
    $stCr = $db->prepare("SELECT idcr FROM creditos WHERE m_pedidocr = :np LIMIT 1");
    $stCr->execute([':np' => $numero_pedido]);
    $rowCr = $stCr->fetch(PDO::FETCH_ASSOC);
    if ($rowCr) {
        $idCredito = $rowCr['idcr'];
        $stAb = $db->prepare("SELECT id, m_pagocr, efectivo, fecha_abono FROM abono_credito WHERE id_credito = :ic ORDER BY fecha_abono DESC");
        $stAb->execute([':ic' => $idCredito]);
        $abonos = $stAb->fetchAll(PDO::FETCH_ASSOC);
        foreach ($abonos as $ab) $abonosTotal += (float)$ab['efectivo'];
    }
}

// Sumar domicilio
if ($tipo_solicitud == 50 && $costo_domicilio > 0) {
    $total_a_pagar += $costo_domicilio;
}
?>

<!-- ═══════════════════════════════════════════════════ -->
<!-- CAJA VIEW -->
<!-- ═══════════════════════════════════════════════════ -->

<div class="container-fluid" style="max-width:800px;">

    <!-- Header -->
    <div class="edit-header">
        <h3>
            Caja <span class="pedido-num">#<?php echo $numero_pedido; ?></span>
        </h3>
        <a href="index.php?page=llamadas.php" class="btn btn-outline-secondary btn-sm">&larr; Volver</a>
    </div>

    <!-- Info del cliente -->
    <div class="card mb-3">
        <div class="card-body" style="display:flex; gap:24px; flex-wrap:wrap; font-size:0.85rem;">
            <span><strong>Cliente:</strong> <?php echo htmlspecialchars($nombre_cliente); ?></span>
            <span><strong>Celular:</strong> <?php echo htmlspecialchars($celular_cliente); ?></span>
            <span><strong>Cajero:</strong> <?php echo htmlspecialchars($cajeros); ?></span>
        </div>
    </div>

    <!-- Tabla de productos (con anulados) -->
    <table class="table table-bordered" id="tabla-productos">
        <thead>
            <tr>
                <th>Prefijo</th>
                <th>Producto</th>
                <th>Cant</th>
                <th>Precio</th>
                <th>Subtotal</th>
                <th>Detalle</th>
                <th>Tipo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($todos_productos as $detalle): ?>
            <?php 
                $esAnulado = $detalle['es_anulado'];
                $esNegativo = ((int)$detalle['cantidad'] < 0);
                $rowClass = '';
                $rowStyle = '';
                
                if ($esNegativo) {
                    // Línea negativa → sub-item rojo
                    $rowClass = 'table-danger';
                    $rowStyle = 'font-size:0.82rem;';
                } elseif ($esAnulado) {
                    // Original anulado → tachado gris
                    $rowStyle = 'text-decoration:line-through; opacity:0.5;';
                }
            ?>
            <tr class="<?php echo $rowClass; ?>" 
                style="<?php echo $rowStyle; ?>"
                data-anulado="<?php echo $esAnulado ? '1' : '0'; ?>">
                <td>
                    <?php if ($esNegativo): ?>
                        <span style="color:var(--coral,#ff4757); font-weight:600;">↳</span>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($detalle['prefijo']); ?>
                </td>
                <td>
                    <?php echo htmlspecialchars($detalle['nombre_producto']); ?>
                    <?php if ($esNegativo): ?>
                        <small style="color:var(--coral,#ff4757); font-weight:600;">(ANULADO)</small>
                    <?php endif; ?>
                </td>
                <td><?php echo (int)$detalle['cantidad']; ?></td>
                <td>$<?php echo number_format($detalle['precio_producto'], 0, ',', '.'); ?></td>
                <td style="<?php echo $esNegativo ? 'color:var(--coral,#ff4757); font-weight:700;' : ''; ?>">
                    <?php if ($esNegativo): ?>
                        −$<?php echo number_format(abs($detalle['subtotal']), 0, ',', '.'); ?>
                    <?php else: ?>
                        $<?php echo number_format($detalle['subtotal'], 0, ',', '.'); ?>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars(str_replace('ANULADO: ', '', $detalle['detalle'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars($detalle['tipo_producto']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totales -->
    <div class="card mb-3">
        <div class="card-body">
            <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                <span>Total productos (neto):</span>
                <strong>$<?php echo number_format($total_productos, 0, ',', '.'); ?></strong>
            </div>
            <?php if ($tipo_solicitud == 50 && $costo_domicilio > 0): ?>
            <div style="display:flex; justify-content:space-between; font-size:0.9rem; margin-top:4px;">
                <span>Domicilio:</span>
                <strong>$<?php echo number_format($costo_domicilio, 0, ',', '.'); ?></strong>
            </div>
            <?php endif; ?>

            <!-- Descuento -->
            <div id="descuentoInput" style="display:none; margin-top:8px;">
                <div style="display:flex; gap:8px; align-items:center;">
                    <label class="form-label mb-0" style="white-space:nowrap;">Descuento:</label>
                    <input type="number" id="descuento" class="form-control form-control-sm" style="max-width:140px;" 
                           value="0" min="0" step="100" oninput="calcularTotalConDescuento()">
                </div>
            </div>

            <hr style="margin:8px 0;">
            <div style="display:flex; justify-content:space-between; font-size:1.1rem;">
                <strong>Total a Pagar:</strong>
                <strong id="total_a_pagar_con_descuento" style="color:var(--coral,#ff4757);">
                    $<?php echo number_format($total_a_pagar, 0, ',', '.'); ?>
                </strong>
            </div>
        </div>
    </div>

    <!-- ═══ PAGO ═══ -->
    <h5 style="margin-top:20px;">Pago</h5>

    <?php if ($pago_existente): ?>
        <div class="card mb-3" style="border-color:var(--emerald,#10b981);">
            <div class="card-body">
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <span class="badge-estado badge-pagado" style="font-size:0.85rem; padding:6px 14px;">
                        PAGADO — <?php echo htmlspecialchars($metodo_pagado); ?>
                    </span>
                    <a href="index.php?page=llamadas.php" class="btn btn-outline-secondary btn-sm">Volver</a>
                    <button type="button" class="btn btn-danger btn-sm" onclick="reversarCaja(<?php echo (int)$numero_pedido; ?>)">
                        Reversar Caja
                    </button>
                </div>

                <?php if ($metodo_pagado === 'credito'): ?>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Abonos Registrados</h6>
                        <button class="btn btn-info btn-sm"
                            data-bs-toggle="modal" data-bs-target="#modal-abonar"
                            onclick="document.getElementById('id_credito_hidden').value='<?php echo (int)$idCredito; ?>';">
                            + Abonar
                        </button>
                    </div>
                    <?php if (!empty($abonos)): ?>
                        <table class="table table-bordered" style="font-size:0.85rem;">
                            <thead><tr><th>Fecha</th><th>Método</th><th>Valor</th></tr></thead>
                            <tbody>
                                <?php foreach ($abonos as $ab): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ab['fecha_abono']); ?></td>
                                    <td><?php echo htmlspecialchars($ab['m_pagocr']); ?></td>
                                    <td>$<?php echo number_format($ab['efectivo'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                            <span>Total Abonado:</span>
                            <strong style="color:var(--emerald,#10b981);">$<?php echo number_format($abonosTotal, 0, ',', '.'); ?></strong>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                            <span>Saldo Pendiente:</span>
                            <strong style="color:var(--coral,#ff4757);">$<?php echo number_format(($monto_pagado - $abonosTotal), 0, ',', '.'); ?></strong>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No hay abonos registrados todavía.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <!-- Formulario de pago -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="form-pago">
                    <input type="hidden" name="numero_pedido" value="<?php echo (int)$numero_pedido; ?>">
                    <input type="hidden" name="tpago" id="total" value="<?php echo $total_a_pagar; ?>">
                    <input type="hidden" name="idmeses" value="<?php echo htmlspecialchars($idsmese); ?>">
                    <input type="hidden" id="nombre_cajero" value="<?php echo htmlspecialchars($cajeros); ?>">

                    <div class="mb-3">
                        <label class="form-label">Método de Pago</label>
                        <select name="m_pago" id="m_pago" class="form-select" onchange="toggleEfectivoInput()" required>
                            <option value="transferencia" selected>Transferencia</option>
                        </select>
                    </div>

                    <!-- Transferencia -->
                    <div id="transferenciaInputs" class="mb-3">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Banco</label>
                                <select name="banco" id="banco" class="form-select">
                                    <option value="Nequi" selected>Nequi</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100" style="padding:12px;">
                        Procesar Pago
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Confirmación -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pago Procesado</h5>
            </div>
            <div class="modal-body">El pago ha sido procesado. Haz clic en "Continuar" para finalizar.</div>
            <div class="modal-footer">
                <input type="hidden" id="tipo_solicitud" value="<?php echo htmlspecialchars($tipo_solicitud ?? ''); ?>">
                <button type="button" class="btn btn-success" id="continueButton">Continuar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Abonar -->
<div class="modal fade" id="modal-abonar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Abonos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="id_credito_hidden" value="">
                <div id="abonos-container">
                    <div class="abono-row mb-2">
                        <div class="row g-2">
                            <div class="col-md-7">
                                <label class="form-label">Método</label>
                                <select class="form-select" name="m_pagocr[]">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="efectivo_transferencia">Efectivo + Transferencia</option>
                                    <option value="tarjeta_efectivo">Tarjeta + Efectivo</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Valor</label>
                                <input type="number" class="form-control" name="efectivo[]" step="100">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="btn-agregar-abono">+ Agregar Abono</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btn-guardar-abonos">Guardar Abonos</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts impresión -->
<script type="text/javascript" src="https://heiyubai.datarie.info/qz-tray.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsrsasign@10.5.0/lib/jsrsasign-all-min.js"></script>

<script>
    const CAJA_DATA = {
        totalProductos: <?php echo $total_productos; ?>,
        costoDomicilio: <?php echo $costo_domicilio; ?>,
        totalAPagar: <?php echo $total_a_pagar; ?>,
        numeroPedido: <?php echo $numero_pedido; ?>
    };
</script>
<script src="../public/js/caja_tm.js?cache=<?php echo time(); ?>"></script>

<script>
document.getElementById('mostrar-descuento-btn')?.addEventListener('click', function() {
    const d = document.getElementById('descuentoInput');
    d.style.display = d.style.display === 'none' ? 'block' : 'none';
});

function calcularTotalConDescuento() {
    const descuento = parseFloat(document.getElementById('descuento').value) || 0;
    let total = CAJA_DATA.totalProductos + CAJA_DATA.costoDomicilio - descuento;
    if (total < 0) total = 0;
    
    document.getElementById('total_a_pagar_con_descuento').textContent = 
        '$' + total.toLocaleString('es-CO', { minimumFractionDigits: 0 });
    document.getElementById('total').value = total;
    
    const pagoInput = document.getElementById('pago');
    if (pagoInput) pagoInput.value = total;
}
</script>