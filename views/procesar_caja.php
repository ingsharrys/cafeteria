<?php
/**
 * procesar_caja.php - Pago múltiple de domicilios
 * UBICACIÓN: views/procesar_caja.php
 * ✅ Compatible con public/index.php
 * ✅ BS5, pago por lote via API
 */

try {
    $conn = Database::getInstance()->getConnection();
} catch (Exception $e) {
    echo '<div class="alert alert-danger m-4">Error de conexión: ' . $e->getMessage() . '</div>';
    return;
}

date_default_timezone_set('America/Bogota');

// Obtener pedidos seleccionados
$pedidosSeleccionados = $_POST['pedidos'] ?? [];
$id_e = $_POST['id_e'] ?? 0;

if (empty($pedidosSeleccionados)) {
    echo '<div class="alert alert-warning m-4">No se seleccionaron pedidos. <a href="index.php?page=domiciliarios.php">Volver</a></div>';
    return;
}

// Preparar datos de cada pedido
$pedidosData = [];
$sumatoria_total = 0;
$all_paid = true;

foreach ($pedidosSeleccionados as $numero_pedido) {
    $np = (int)$numero_pedido;
    
    // Obtener detalles
    $stDet = $conn->prepare("
        SELECT pr.prefijo, pr.nombre AS nombre_producto, p.cantidad, p.tipo_solicitud,
               prp.precio AS precio_producto, p.detalle, p.tipo_producto
        FROM pedidos p
        JOIN productos pr ON p.id_pro = pr.id_pro
        LEFT JOIN precios prp ON p.id_pro = prp.idproduc AND p.tipo_producto = prp.tipo_prod
        WHERE p.numero_pedido = :np
          AND p.cantidad > 0
          AND (p.detalle IS NULL OR p.detalle NOT LIKE 'ANULADO:%')
    ");
    $stDet->execute([':np' => $np]);
    $detalles = $stDet->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$detalles) continue;

    // Cliente desde turnero
    $stCli = $conn->prepare("
        SELECT c.cliente, c.celular, c.direccion
        FROM turnero t
        LEFT JOIN clientes c ON t.id_cliente = c.id
        WHERE t.id_pedido = :np LIMIT 1
    ");
    $stCli->execute([':np' => $np]);
    $cli = $stCli->fetch(PDO::FETCH_ASSOC) ?: ['cliente'=>'—','celular'=>'—','direccion'=>'—'];

    // Costo domicilio
    $stDom = $conn->prepare("SELECT precio FROM domicilios WHERE id_pedido = :np LIMIT 1");
    $stDom->execute([':np' => $np]);
    $costoDom = (float)($stDom->fetchColumn() ?: 0);

    $tipoSol = $detalles[0]['tipo_solicitud'] ?? '';

    // Total productos
    $totalProd = array_reduce($detalles, function($c, $d) {
        return $c + ((float)$d['precio_producto'] * (int)$d['cantidad']);
    }, 0);

    $totalPagar = $totalProd + ($tipoSol == 50 ? $costoDom : 0);
    $sumatoria_total += $totalPagar;

    // Verificar si ya pagado
    $stPago = $conn->prepare("SELECT COUNT(*) FROM caja WHERE id_pedidoc = :np");
    $stPago->execute([':np' => $np]);
    $yaPagado = ($stPago->fetchColumn() > 0);
    if (!$yaPagado) $all_paid = false;

    $pedidosData[] = [
        'numero_pedido' => $np,
        'detalles'      => $detalles,
        'cliente'       => $cli,
        'costo_dom'     => $costoDom,
        'tipo_sol'      => $tipoSol,
        'total_prod'    => $totalProd,
        'total_pagar'   => $totalPagar,
        'ya_pagado'     => $yaPagado,
    ];
}
?>

<div class="container mt-4">
    <div class="d-flex align-items-center gap-3 mb-3">
        <h3 class="mb-0">Procesar Pagos (<?php echo count($pedidosData); ?> pedidos)</h3>
        <a href="index.php?page=domiciliarios.php" class="btn btn-outline-secondary btn-sm">← Volver</a>
    </div>

    <?php foreach ($pedidosData as $ped): ?>
    <div class="card mb-3 <?php echo $ped['ya_pagado'] ? 'border-success' : ''; ?>">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Pedido #<?php echo $ped['numero_pedido']; ?></strong>
            <?php if ($ped['ya_pagado']): ?>
                <span class="badge bg-success">Ya Pagado</span>
            <?php else: ?>
                <span class="badge bg-warning text-dark">Pendiente</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <!-- Detalles del pedido -->
            <table class="table table-bordered table-sm">
                <thead><tr><th>Producto</th><th>Cant</th><th>Precio</th><th>Subtotal</th><th>Tipo</th></tr></thead>
                <tbody>
                    <?php foreach ($ped['detalles'] as $det): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($det['nombre_producto']); ?></td>
                        <td><?php echo (int)$det['cantidad']; ?></td>
                        <td>$<?php echo number_format($det['precio_producto'], 0, '', ','); ?></td>
                        <td>$<?php echo number_format($det['precio_producto'] * $det['cantidad'], 0, '', ','); ?></td>
                        <td><?php echo htmlspecialchars($det['tipo_producto']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="row">
                <div class="col-md-6">
                    <p><strong>Total Productos:</strong> $<?php echo number_format($ped['total_prod'], 0, '', ','); ?></p>
                    <?php if ($ped['tipo_sol'] == 50 && $ped['costo_dom'] > 0): ?>
                        <p><strong>Domicilio:</strong> $<?php echo number_format($ped['costo_dom'], 0, '', ','); ?></p>
                    <?php endif; ?>
                    <p class="fs-5"><strong>Total a Pagar:</strong> $<?php echo number_format($ped['total_pagar'], 0, '', ','); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($ped['cliente']['cliente'] ?? '—'); ?></p>
                    <p><strong>Celular:</strong> <?php echo htmlspecialchars($ped['cliente']['celular'] ?? '—'); ?></p>
                    <p><strong>Dirección:</strong> <?php echo htmlspecialchars($ped['cliente']['direccion'] ?? '—'); ?></p>
                </div>
            </div>

            <?php if (!$ped['ya_pagado']): ?>
            <!-- Formulario de pago -->
            <div class="pago-form" data-pedido="<?php echo $ped['numero_pedido']; ?>" data-total="<?php echo $ped['total_pagar']; ?>">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Método de Pago</label>
                        <select class="form-select sel-mpago" data-pedido="<?php echo $ped['numero_pedido']; ?>">
                            <option value="">Seleccionar</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="efectivo_transferencia">Efectivo + Transferencia</option>
                            <option value="tarjeta_efectivo">Tarjeta + Efectivo</option>
                            <option value="devolucion">Devolución</option>
                            <option value="Cortesia">Cortesía</option>
                        </select>
                    </div>
                    <div class="col-md-2 div-efectivo" style="display:none;">
                        <label class="form-label">Efectivo</label>
                        <input type="number" class="form-control inp-efectivo" data-pedido="<?php echo $ped['numero_pedido']; ?>" value="<?php echo $ped['total_pagar']; ?>">
                    </div>
                    <div class="col-md-2 div-banco" style="display:none;">
                        <label class="form-label">Banco</label>
                        <select class="form-select sel-banco">
                            <option value="Nequi">Nequi</option>
                            <option value="Bancolombia">Bancolombia</option>
                            <option value="Davivienda">Davivienda</option>
                            <option value="Daviplata">Daviplata</option>
                            <option value="BBVA">BBVA</option>
                        </select>
                    </div>
                    <div class="col-md-2 div-banco" style="display:none;">
                        <label class="form-label">Referencia</label>
                        <input type="text" class="form-control inp-referencia">
                    </div>
                    <div class="col-md-3">
                        <p class="mb-0 resultado-cambio" id="res-<?php echo $ped['numero_pedido']; ?>"></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Resumen -->
    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Total General: $<?php echo number_format($sumatoria_total, 0, '', ','); ?></h5>
                <small>Efectivo ingresado: <span id="sumEfectivo">$0</span></small>
            </div>
            <?php if (!$all_paid): ?>
                <button class="btn btn-primary btn-lg" id="btnProcesar">Procesar Todos los Pagos</button>
            <?php else: ?>
                <span class="badge bg-success fs-5">Todos pagados</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fmt = new Intl.NumberFormat('es-CO', { style:'currency', currency:'COP', minimumFractionDigits:0, maximumFractionDigits:0 });

    // Toggle campos según método de pago
    document.querySelectorAll('.sel-mpago').forEach(sel => {
        sel.addEventListener('change', function() {
            const form = this.closest('.pago-form');
            const mp = this.value;
            const showEfectivo = ['efectivo','efectivo_transferencia','tarjeta_efectivo','brebe_efectivo'].includes(mp);
            const showBanco = ['transferencia','efectivo_transferencia'].includes(mp);

            form.querySelector('.div-efectivo').style.display = showEfectivo ? '' : 'none';
            form.querySelectorAll('.div-banco').forEach(d => d.style.display = showBanco ? '' : 'none');

            if (!showEfectivo) {
                const inp = form.querySelector('.inp-efectivo');
                if (inp) inp.value = '';
            }
            calcularCambio(form);
            recalcSumatoria();
        });
    });

    // Calcular cambio
    document.querySelectorAll('.inp-efectivo').forEach(inp => {
        inp.addEventListener('input', function() {
            calcularCambio(this.closest('.pago-form'));
            recalcSumatoria();
        });
    });

    function calcularCambio(form) {
        const total = parseFloat(form.dataset.total) || 0;
        const pago = parseFloat(form.querySelector('.inp-efectivo')?.value) || 0;
        const mp = form.querySelector('.sel-mpago')?.value;
        const pedido = form.dataset.pedido;
        const res = document.getElementById('res-' + pedido);
        if (!res) return;

        if (mp === 'efectivo' || mp === 'brebe_efectivo' || mp === 'tarjeta_efectivo') {
            if (pago < total) res.textContent = 'Restante: ' + fmt.format(total - pago);
            else if (pago === total) res.textContent = 'Pago exacto ✓';
            else res.textContent = 'Cambio: ' + fmt.format(pago - total);
        } else {
            res.textContent = '';
        }
    }

    function recalcSumatoria() {
        let sum = 0;
        document.querySelectorAll('.inp-efectivo').forEach(inp => {
            sum += parseFloat(inp.value) || 0;
        });
        document.getElementById('sumEfectivo').textContent = fmt.format(sum);
    }

    // Procesar pagos
    document.getElementById('btnProcesar')?.addEventListener('click', function() {
        const pedidos = [];

        document.querySelectorAll('.pago-form').forEach(form => {
            const mp = form.querySelector('.sel-mpago')?.value;
            if (!mp) return; // Skip if no method selected

            pedidos.push({
                id_pedidoc: form.dataset.pedido,
                costo:      form.dataset.total,
                m_pago:     mp,
                efectivo:   parseFloat(form.querySelector('.inp-efectivo')?.value) || null,
                banco:      form.querySelector('.sel-banco')?.value || null,
                referencia: form.querySelector('.inp-referencia')?.value || null
            });
        });

        if (pedidos.length === 0) {
            alert('Selecciona un método de pago para al menos un pedido.');
            return;
        }

        if (!confirm('¿Procesar ' + pedidos.length + ' pago(s)?')) return;

        this.disabled = true;
        this.textContent = 'Procesando...';

        fetch('../api.php?route=caja/pagar_lote', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pedidos: pedidos })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                // Redirigir a domiciliarios
                window.location.href = 'index.php?page=domiciliarios.php';
            } else {
                alert('❌ ' + (data.message || 'Error procesando pagos.'));
                if (data.errores) console.error(data.errores);
                this.disabled = false;
                this.textContent = 'Procesar Todos los Pagos';
            }
        })
        .catch(err => {
            alert('Error: ' + err.message);
            this.disabled = false;
            this.textContent = 'Procesar Todos los Pagos';
        });
    });
});
</script>