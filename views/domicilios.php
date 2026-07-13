<?php
/**
 * domicilios.php - Domicilios de un domiciliario específico
 * UBICACIÓN: views/domicilios.php
 * ✅ Compatible con public/index.php
 * ✅ Pendientes sin pagar arriba
 * ✅ BS5, sin require_once propios
 * ✅ AGREGADOS: Checkboxes para seleccionar
 */

try {
    $conn = Database::getInstance()->getConnection();
} catch (Exception $e) {
    echo '<div class="alert alert-danger m-4">Error de conexión: ' . $e->getMessage() . '</div>';
    return;
}

date_default_timezone_set('America/Bogota');

// ID del domiciliario
$id_e = isset($_POST['id_e']) ? (int)$_POST['id_e'] : (isset($_GET['id_e']) ? (int)$_GET['id_e'] : 0);

// Fecha filtro
$fecha_actual = isset($_POST['fecha_filtro']) ? $_POST['fecha_filtro'] : date('Y-m-d');

if (!$id_e) {
    echo '<div class="alert alert-warning m-4">No se seleccionó un domiciliario.</div>';
    return;
}

// Nombre del domiciliario
$stNombre = $conn->prepare("SELECT repartidor FROM domiciliarios WHERE id_e = :id");
$stNombre->execute([':id' => $id_e]);
$domiciliario_nombre = $stNombre->fetchColumn() ?: 'Desconocido';

// ═══════════════════════════════════════════════════
// 1. PENDIENTES POR PAGAR (sin filtro de fecha)
// ═══════════════════════════════════════════════════
$stPendientes = $conn->prepare("
    SELECT d.id_pedido, t.turno, p.fecha,
           c.cliente, c.celular, c.direccion,
           d.precio AS costo_domicilio
    FROM domicilios d
    JOIN pedidos p ON d.id_pedido = p.numero_pedido
    LEFT JOIN turnero t ON t.id_pedido = p.numero_pedido
    LEFT JOIN clientes c ON t.id_cliente = c.id
    WHERE d.id_domi = :id_domi
      AND NOT EXISTS (SELECT 1 FROM caja ca WHERE ca.id_pedidoc = d.id_pedido)
    GROUP BY d.id_pedido
    ORDER BY p.fecha DESC
");
$stPendientes->execute([':id_domi' => $id_e]);
$pendientes = $stPendientes->fetchAll(PDO::FETCH_ASSOC);

// Calcular total pendiente
foreach ($pendientes as &$pend) {
    $stTotal = $conn->prepare("
        SELECT SUM(prp.precio * p.cantidad) AS total_prod
        FROM pedidos p
        JOIN precios prp ON p.id_pro = prp.idproduc AND p.tipo_producto = prp.tipo_prod
        WHERE p.numero_pedido = :np
          AND p.cantidad > 0
          AND (p.detalle IS NULL OR p.detalle NOT LIKE 'ANULADO:%')
    ");
    $stTotal->execute([':np' => $pend['id_pedido']]);
    $totalProd = (float)($stTotal->fetchColumn() ?: 0);
    $pend['total_productos'] = $totalProd;
    $pend['total_general'] = $totalProd + (float)($pend['costo_domicilio'] ?? 0);
}
unset($pend);

// ═══════════════════════════════════════════════════
// 2. DOMICILIOS DEL DÍA SELECCIONADO
// ═══════════════════════════════════════════════════
$stDia = $conn->prepare("
    SELECT d.id_pedido, t.turno, p.fecha,
           c.cliente, c.celular, c.direccion,
           d.precio AS costo_domicilio,
           (SELECT COUNT(*) FROM caja ca WHERE ca.id_pedidoc = d.id_pedido) AS pagado
    FROM domicilios d
    JOIN pedidos p ON d.id_pedido = p.numero_pedido
    LEFT JOIN turnero t ON t.id_pedido = p.numero_pedido
    LEFT JOIN clientes c ON t.id_cliente = c.id
    WHERE d.id_domi = :id_domi AND DATE(p.fecha) = :fecha
    GROUP BY d.id_pedido
    ORDER BY t.turno ASC
");
$stDia->execute([':id_domi' => $id_e, ':fecha' => $fecha_actual]);
$domiciliosDia = $stDia->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">

    <!-- Encabezado -->
    <div class="d-flex align-items-center gap-3 mb-3">
        <h3 class="mb-0">Domicilios de <?php echo htmlspecialchars($domiciliario_nombre); ?></h3>
        <a href="index.php?page=domiciliarios.php" class="btn btn-outline-secondary btn-sm">← Volver</a>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- PENDIENTES POR PAGAR (todas las fechas)    -->
    <!-- ═══════════════════════════════════════════ -->
    <?php if (count($pendientes) > 0): ?>
    <div class="card border-danger mb-4">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <strong>⚠️ Pendientes por pagar (<?php echo count($pendientes); ?> pedido<?php echo count($pendientes) > 1 ? 's' : ''; ?>)</strong>
            <?php 
            $totalDeuda = array_sum(array_column($pendientes, 'total_general'));
            ?>
            <span class="badge bg-light text-danger fs-6">
                Total: $<?php echo number_format($totalDeuda, 0, '', ','); ?>
            </span>
        </div>
        <div class="card-body p-0">
            <form action="index.php?page=procesar_caja.php" method="POST" id="formPendientes">
                <input type="hidden" name="id_e" value="<?php echo $id_e; ?>">
                <table class="table table-bordered table-danger mb-0">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><input type="checkbox" id="selectAll"></th>
                            <th>Turno</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Dirección</th>
                            <th>Total</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendientes as $p): ?>
                        <tr>
                            <td><input type="checkbox" name="pedidos[]" value="<?php echo (int)$p['id_pedido']; ?>" class="pedido-check"></td>
                            <td><?php echo htmlspecialchars($p['turno'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars(substr($p['fecha'], 0, 10)); ?></td>
                            <td><?php echo htmlspecialchars($p['cliente'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($p['direccion'] ?? '—'); ?></td>
                            <td><strong>$<?php echo number_format($p['total_general'], 0, '', ','); ?></strong></td>
                            <td>
                                <form action="index.php?page=caja_tm.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="numero_pedido" value="<?php echo (int)$p['id_pedido']; ?>">
                                    <button class="btn btn-info btn-sm">Caja</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="p-2" style="border-top: 1px solid #dee2e6;">
                    <button type="submit" class="btn btn-danger" id="btnEnviarCaja" style="display:none;">
                        Pagar seleccionados
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════ -->
    <!-- FILTRO POR FECHA                           -->
    <!-- ═══════════════════════════════════════════ -->
    <form method="POST" action="index.php?page=domicilios.php" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="id_e" value="<?php echo $id_e; ?>">
        <div class="col-auto">
            <label class="form-label">Fecha:</label>
            <input type="date" class="form-control" name="fecha_filtro" value="<?php echo htmlspecialchars($fecha_actual); ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <!-- ═══════════════════════════════════════════ -->
    <!-- DOMICILIOS DEL DÍA                         -->
    <!-- ═══════════════════════════════════════════ -->
    <h5>Domicilios del <?php echo htmlspecialchars($fecha_actual); ?></h5>
    
    <form action="index.php?page=procesar_caja.php" method="POST" id="formDia">
        <input type="hidden" name="id_e" value="<?php echo $id_e; ?>">
        
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 50px;"><input type="checkbox" id="selectAllDia"></th>
                    <th>Turno</th>
                    <th>Cliente</th>
                    <th>Celular</th>
                    <th>Dirección</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($domiciliosDia) > 0): ?>
                    <?php foreach ($domiciliosDia as $dom): 
                        $pagado = ((int)$dom['pagado'] > 0);
                        $np = (int)$dom['id_pedido'];
                    ?>
                    <tr class="<?php echo $pagado ? '' : 'table-warning'; ?>">
                        <td>
                            <?php if (!$pagado): ?>
                                <input type="checkbox" name="pedidos[]" value="<?php echo $np; ?>" class="pedido-dia-check">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($dom['turno'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($dom['cliente'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($dom['celular'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($dom['direccion'] ?? '—'); ?></td>
                        <td>
                            <?php if ($pagado): ?>
                                <span class="badge bg-success">Pagado</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($pagado): ?>
                                <button class="btn btn-success btn-sm" disabled>Pagado</button>
                            <?php else: ?>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#detModal-<?php echo $np; ?>">
                                    Ver Detalle
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted">No hay domicilios para esta fecha.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if (count($domiciliosDia) > 0): ?>
        <div class="p-2" style="border-top: 1px solid #dee2e6;">
            <button type="submit" class="btn btn-info" id="btnEnviarDia" style="display:none;">
                Pagar seleccionados del día
            </button>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- ═══════════════════════════════════════════ -->
<!-- Modales de detalle (solo no pagados)       -->
<!-- ═══════════════════════════════════════════ -->
<?php foreach ($domiciliosDia as $dom): 
    if ((int)$dom['pagado'] > 0) continue;
    $np = (int)$dom['id_pedido'];
    
    // Obtener detalles del pedido
    $stDet = $conn->prepare("
        SELECT pr.nombre, p.cantidad, prp.precio AS precio_producto, p.detalle, p.tipo_producto
        FROM pedidos p
        JOIN productos pr ON p.id_pro = pr.id_pro
        LEFT JOIN precios prp ON pr.id_pro = prp.idproduc AND prp.tipo_prod = p.tipo_producto
        WHERE p.numero_pedido = :np
          AND p.cantidad > 0
          AND (p.detalle IS NULL OR p.detalle NOT LIKE 'ANULADO:%')
    ");
    $stDet->execute([':np' => $np]);
    $detalles = $stDet->fetchAll(PDO::FETCH_ASSOC);
    
    $totalProd = 0;
    foreach ($detalles as $det) {
        $totalProd += (float)$det['precio_producto'] * (int)$det['cantidad'];
    }
    $costoDom = (float)($dom['costo_domicilio'] ?? 0);
    $totalGen = $totalProd + $costoDom;
?>
<div class="modal fade" id="detModal-<?php echo $np; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle Pedido #<?php echo $np; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($dom['cliente'] ?? '—'); ?></p>
                <p><strong>Celular:</strong> <?php echo htmlspecialchars($dom['celular'] ?? '—'); ?></p>
                <p><strong>Dirección:</strong> <?php echo htmlspecialchars($dom['direccion'] ?? '—'); ?></p>

                <table class="table table-bordered table-sm">
                    <thead><tr><th>Producto</th><th>Cant</th><th>Precio</th><th>Subtotal</th><th>Tipo</th></tr></thead>
                    <tbody>
                        <?php foreach ($detalles as $det): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($det['nombre']); ?></td>
                            <td><?php echo (int)$det['cantidad']; ?></td>
                            <td>$<?php echo number_format($det['precio_producto'], 0, '', ','); ?></td>
                            <td>$<?php echo number_format($det['precio_producto'] * $det['cantidad'], 0, '', ','); ?></td>
                            <td><?php echo htmlspecialchars($det['tipo_producto']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><strong>Total Productos:</strong> $<?php echo number_format($totalProd, 0, '', ','); ?></p>
                <?php if ($costoDom > 0): ?>
                    <p><strong>Costo Domicilio:</strong> $<?php echo number_format($costoDom, 0, '', ','); ?></p>
                <?php endif; ?>
                <p class="fs-5"><strong>Total General:</strong> $<?php echo number_format($totalGen, 0, '', ','); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <form action="index.php?page=caja_tm.php" method="POST" style="display:inline;">
                    <input type="hidden" name="numero_pedido" value="<?php echo $np; ?>">
                    <button class="btn btn-info">Caja</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ═══════════════════════════════════════════════════════
    // PENDIENTES - Select all
    // ═══════════════════════════════════════════════════════
    const selectAll = document.getElementById('selectAll');
    const checks = document.querySelectorAll('.pedido-check');
    const btnEnviar = document.getElementById('btnEnviarCaja');

    if (selectAll && checks.length > 0) {
        selectAll.addEventListener('change', function() {
            checks.forEach(ch => ch.checked = this.checked);
            toggleBtn();
        });
    }

    checks.forEach(ch => ch.addEventListener('change', toggleBtn));

    function toggleBtn() {
        const haySeleccion = [...checks].some(ch => ch.checked);
        if (btnEnviar) btnEnviar.style.display = haySeleccion ? 'inline-block' : 'none';
    }

    // ═══════════════════════════════════════════════════════
    // DOMICILIOS DEL DÍA - Select all
    // ═══════════════════════════════════════════════════════
    const selectAllDia = document.getElementById('selectAllDia');
    const checksDia = document.querySelectorAll('.pedido-dia-check');
    const btnEnviarDia = document.getElementById('btnEnviarDia');

    if (selectAllDia && checksDia.length > 0) {
        selectAllDia.addEventListener('change', function() {
            checksDia.forEach(ch => ch.checked = this.checked);
            toggleBtnDia();
        });
    }

    checksDia.forEach(ch => ch.addEventListener('change', toggleBtnDia));

    function toggleBtnDia() {
        const haySeleccion = [...checksDia].some(ch => ch.checked);
        if (btnEnviarDia) btnEnviarDia.style.display = haySeleccion ? 'inline-block' : 'none';
    }
});
</script>