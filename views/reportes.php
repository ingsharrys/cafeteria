<?php
/**
 * reportes.php - Reporte de Turnos/Ventas
 * UBICACIÓN: views/reportes.php
 * ✅ Incluye: Cajeros, Admins, Cerrar Caja, Excel
 */

try {
    $conn = Database::getInstance()->getConnection();
} catch (Exception $e) {
    echo '<div class="alert alert-danger m-4">Error de conexión: ' . $e->getMessage() . '</div>';
    return;
}

date_default_timezone_set('America/Bogota');

// ═══════════════════════════════════════════════════
// PARÁMETROS DE FILTRO
// ═══════════════════════════════════════════════════
$fechaActual = (!empty($_POST['fecha_seleccionada'])) ? $_POST['fecha_seleccionada'] : date('Y-m-d');
$cajeroSeleccionado = (!empty($_POST['cajero'])) ? $_POST['cajero'] : 'consolidado';
$bancoSeleccionado = $_POST['banco'] ?? '';
$tipoSolicitudSeleccionado = $_POST['tipo_solicitud'] ?? '';
$metodoPagoSeleccionado = $_POST['metodo_pago'] ?? '';

// ═══════════════════════════════════════════════════
// OBTENER CAJEROS Y ADMINS
// ═══════════════════════════════════════════════════
$cajerosDb = [];
$cajerInfo = null;
try {
    $stCaj = $conn->prepare("SELECT id_mese, nombre_mese, cargo FROM meseros WHERE cargo IN ('cajero', 'admin') ORDER BY nombre_mese ASC");
    $stCaj->execute();
    $cajerosDb = $stCaj->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener info del cajero seleccionado
    if ($cajeroSeleccionado !== 'consolidado') {
        $stInfo = $conn->prepare("SELECT id_mese, nombre_mese, cargo FROM meseros WHERE id_mese = ?");
        $stInfo->execute([$cajeroSeleccionado]);
        $cajerInfo = $stInfo->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { /* silenciar */ }

// ═══════════════════════════════════════════════════
// VERIFICAR SI YA EXISTE CIERRE DE CAJA
// ═══════════════════════════════════════════════════
$cierreCajaExiste = false;
$datosCierre = null;
if ($cajeroSeleccionado !== 'consolidado') {
    try {
        $stCierre = $conn->prepare("
            SELECT id, id_cajero, fecha_cierre, total_efectivo, total_tarjeta, 
                   total_transferencia, total_brebe, total_devolucion, total_general
            FROM cierre_caja
            WHERE id_cajero = :id_cajero AND DATE(fecha_cierre) = :fecha
            LIMIT 1
        ");
        $stCierre->execute([
            ':id_cajero' => $cajeroSeleccionado,
            ':fecha' => $fechaActual
        ]);
        $datosCierre = $stCierre->fetch(PDO::FETCH_ASSOC);
        $cierreCajaExiste = !empty($datosCierre);
    } catch (Exception $e) { /* silenciar */ }
}

// ═══════════════════════════════════════════════════
// MÉTODOS DE PAGO
// ═══════════════════════════════════════════════════
$metodosPago = [
    'consolidado',
    'efectivo',
    'transferencia',
    'tarjeta',
    'efectivo_transferencia',
    'tarjeta_efectivo',
    'devolucion'
];

$nombresMetodos = [
    'consolidado'              => 'Todos',
    'efectivo'                 => 'Efectivo',
    'transferencia'            => 'Transferencia',
    'tarjeta'                  => 'Tarjeta',
    'efectivo_transferencia'   => 'Efectivo + Transf.',
    'tarjeta_efectivo'         => 'Tarjeta + Efectivo',
    'devolucion'               => 'Devolución'
];

// ═══════════════════════════════════════════════════
// FUNCIÓN PARA CONSULTAR TURNOS POR MÉTODO
// ═══════════════════════════════════════════════════
function filtrarTurnos($conn, $metodoPago, $fecha, $cajero, $banco, $tipoSolicitud, $metFiltroPago) {
    $sql = "
        SELECT t.id_pedido, t.turno, t.fecha, t.tipo_solicitud, t.estado, t.id_cliente,
               c.m_pago, m.nombre_mese AS cajero, c.banco, c.costo, c.efectivo
        FROM turnero t
        LEFT JOIN caja c ON c.id_pedidoc = t.id_pedido
        LEFT JOIN meseros m ON m.id_mese = c.id_cajero
        WHERE DATE(t.fecha) = :fecha
    ";
    $params = [':fecha' => $fecha];

    if ($metodoPago !== 'consolidado') {
        $sql .= " AND c.m_pago = :metodo_pago";
        $params[':metodo_pago'] = $metodoPago;
    }

    if ($cajero !== 'consolidado') {
        $sql .= " AND c.id_cajero = :cajero";
        $params[':cajero'] = $cajero;
    }

    if (!empty($banco)) {
        $sql .= " AND c.banco = :banco";
        $params[':banco'] = $banco;
    }

    if (!empty($tipoSolicitud)) {
        $sql .= " AND t.tipo_solicitud = :tipo_sol";
        $params[':tipo_sol'] = $tipoSolicitud;
    }

    if (!empty($metFiltroPago)) {
        $sql .= " AND c.m_pago = :met_filtro";
        $params[':met_filtro'] = $metFiltroPago;
    }

    $sql .= " ORDER BY t.fecha ASC";

    $st = $conn->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

// ═══════════════════════════════════════════════════
// OBTENER DATOS POR CADA TAB
// ═══════════════════════════════════════════════════
$turnosPorMetodo = [];
foreach ($metodosPago as $metodo) {
    $turnosPorMetodo[$metodo] = filtrarTurnos(
        $conn, $metodo, $fechaActual, $cajeroSeleccionado, 
        $bancoSeleccionado, $tipoSolicitudSeleccionado, $metodoPagoSeleccionado
    );
}

// ═══════════════════════════════════════════════════
// CALCULAR TOTALES
// ═══════════════════════════════════════════════════
$totales = [
    'totalRecibidoEfectivo'      => 0,
    'totalEfectivo'              => 0,
    'total_efectivo_abonos'      => 0,
    'total_nomina'               => 0,
    'totalRecibidoTarjeta'       => 0,
    'totalTarjeta'               => 0,
    'total_tarjeta_abonos'       => 0,
    'totalRecibidoTransferencia' => 0,
    'totalTransferencia'         => 0,
    'total_transferencia_abonos' => 0,
    'totalRecibidoBrebe'         => 0,
    'totalBrebe'                 => 0,
    'totalDevolucion'            => 0,
];

$procesados = [];
foreach ($turnosPorMetodo['consolidado'] as $reg) {
    $idPed = $reg['id_pedido'] ?? null;
    if (empty($idPed) || isset($procesados[$idPed])) continue;
    $procesados[$idPed] = true;

    $costo    = (float)($reg['costo'] ?? 0);
    $efectivo = (float)($reg['efectivo'] ?? 0);
    $mp       = $reg['m_pago'] ?? '';

    switch ($mp) {
        case 'efectivo':
            $totales['totalEfectivo'] += $costo;
            $totales['totalRecibidoEfectivo'] += $costo;
            break;
        case 'efectivo_transferencia':
            $totales['totalRecibidoEfectivo'] += $efectivo;
            $totales['totalRecibidoTransferencia'] += ($costo - $efectivo);
            break;
        case 'tarjeta_efectivo':
            $totales['totalRecibidoEfectivo'] += $efectivo;
            $totales['totalRecibidoTarjeta'] += ($costo - $efectivo);
            break;
        case 'tarjeta':
            $totales['totalTarjeta'] += $costo;
            $totales['totalRecibidoTarjeta'] += $costo;
            break;
        case 'transferencia':
            $totales['totalTransferencia'] += $costo;
            $totales['totalRecibidoTransferencia'] += $costo;
            break;
        case 'brebe':
            $totales['totalBrebe'] += $costo;
            $totales['totalRecibidoBrebe'] += $costo;
            break;
        case 'brebe_efectivo':
            $totales['totalRecibidoEfectivo'] += $efectivo;
            $totales['totalRecibidoBrebe'] += ($costo - $efectivo);
            break;
        case 'devolucion':
            $totales['totalDevolucion'] += $costo;
            break;
    }
}

// Total general
$totalGeneral = $totales['totalRecibidoEfectivo'] + $totales['totalRecibidoTarjeta'] 
              + $totales['totalRecibidoTransferencia'] + $totales['totalRecibidoBrebe']
              - $totales['totalDevolucion'];

// Tipos de solicitud
$tiposSol = [50 => 'Domicilio', 51 => 'Turno', 52 => 'Mesas', 53 => 'Recoger'];
?>

<div class="container-fluid mt-4">
    <h3 class="mb-3">Reporte de Ventas — <?php echo $fechaActual; ?></h3>

    <!-- ═══════════════════════════════════════════ -->
    <!-- FORMULARIO DE FILTROS                      -->
    <!-- ═══════════════════════════════════════════ -->
    <form method="POST" action="index.php?page=reportes.php" class="card p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Fecha <?php echo $cajeroValidado ?></label>
                <input type="date" name="fecha_seleccionada" class="form-control" id="fecha_seleccionada"
                       value="<?php echo htmlspecialchars($fechaActual); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Cajero/Admin</label>
                <select name="cajero" class="form-select" id="select_cajero">
                    <option value="consolidado" <?php echo $cajeroSeleccionado=='consolidado'?'selected':''; ?>>Todos</option>
                    <?php foreach ($cajerosDb as $caj): ?>
                        <option value="<?php echo htmlspecialchars($caj['id_mese']); ?>" <?php echo $cajeroSeleccionado==$caj['id_mese']?'selected':''; ?>>
                            👤 <?php echo htmlspecialchars($caj['nombre_mese']); ?> 
                            <span class="badge badge-secondary"><?php echo htmlspecialchars($caj['cargo']); ?></span>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Banco</label>
                <select name="banco" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach (['Nequi','Bancolombia','Davivienda','Daviplata','BBVA'] as $b): ?>
                        <option value="<?php echo $b; ?>" <?php echo $bancoSeleccionado==$b?'selected':''; ?>><?php echo $b; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Tipo Solicitud</label>
                <select name="tipo_solicitud" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($tiposSol as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo $tipoSolicitudSeleccionado==$val?'selected':''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Método Pago</label>
                <select name="metodo_pago" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($nombresMetodos as $k => $v): 
                        if ($k === 'consolidado') continue; ?>
                        <option value="<?php echo $k; ?>" <?php echo $metodoPagoSeleccionado==$k?'selected':''; ?>><?php echo $v; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="imprimirDatos()">🖨️ Imprimir Datos</button>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="imprimirResumen()">🖨️ Imprimir Resumen</button>
                
                <!-- BOTÓN CERRAR CAJA -->
                <?php if ($cajeroSeleccionado !== 'consolidado' && $cajerInfo): ?>
                    <button type="button" class="btn <?php echo $cierreCajaExiste ? 'btn-secondary' : 'btn-danger'; ?>" 
                            onclick="abrirModalCierreCaja()" 
                            <?php echo $cierreCajaExiste ? 'disabled' : ''; ?>>
                        🔐 Cerrar Caja
                    </button>
                    <?php if ($cierreCajaExiste): ?>
                        <small class="text-muted ms-2">✅ Caja ya cerrada para este cajero</small>
                        <button type="button" class="btn btn-outline-success btn-sm ms-2" onclick="descargarExcelCierre()">
                            📊 Descargar Excel Cierre
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- ═══════════════════════════════════════════ -->
    <!-- RESUMEN DE VENTAS                          -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <small class="text-muted">Efectivo</small>
                    <h4 class="text-success mb-0" id="h4_efectivo">$<?php echo number_format($totales['totalRecibidoEfectivo'], 0, '', ','); ?></h4>
                    <small>Venta directa: $<?php echo number_format($totales['totalEfectivo'], 0, '', ','); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <small class="text-muted">Tarjetas</small>
                    <h4 class="text-primary mb-0" id="h4_tarjeta">$<?php echo number_format($totales['totalRecibidoTarjeta'], 0, '', ','); ?></h4>
                    <small>Venta directa: $<?php echo number_format($totales['totalTarjeta'], 0, '', ','); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <small class="text-muted">Transferencias</small>
                    <h4 class="text-info mb-0" id="h4_transferencia">$<?php echo number_format($totales['totalRecibidoTransferencia'], 0, '', ','); ?></h4>
                    <small>Venta directa: $<?php echo number_format($totales['totalTransferencia'], 0, '', ','); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <small class="text-muted">Brebe</small>
                    <h4 class="text-warning mb-0" id="h4_brebe">$<?php echo number_format($totales['totalRecibidoBrebe'], 0, '', ','); ?></h4>
                    <small>Venta directa: $<?php echo number_format($totales['totalBrebe'], 0, '', ','); ?></small>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <small class="text-muted">Devoluciones</small>
                    <h4 class="text-danger mb-0" id="h4_devolucion">$<?php echo number_format($totales['totalDevolucion'], 0, '', ','); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card bg-dark text-white">
                <div class="card-body text-center">
                    <small>Total General</small>
                    <h3 class="mb-0">$<?php echo number_format($totalGeneral, 0, '', ','); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- TABS POR MÉTODO DE PAGO                    -->
    <!-- ═══════════════════════════════════════════ -->
    <ul class="nav nav-tabs" id="tabMetodos" role="tablist">
        <?php foreach ($metodosPago as $i => $metodo): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($i === 0) ? 'active' : ''; ?>" 
                        id="tab-<?php echo $metodo; ?>"
                        data-bs-toggle="tab" data-bs-target="#panel-<?php echo $metodo; ?>"
                        type="button" role="tab">
                    <?php echo $nombresMetodos[$metodo]; ?>
                    <span class="badge bg-secondary"><?php echo count($turnosPorMetodo[$metodo]); ?></span>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="tab-content" id="tabContenido">
        <?php foreach ($metodosPago as $i => $metodo): ?>
        <div class="tab-pane fade <?php echo ($i === 0) ? 'show active' : ''; ?>"
             id="panel-<?php echo $metodo; ?>" role="tabpanel">

            <div class="table-responsive" style="max-height:500px; overflow-y:auto;">
                <table class="table table-bordered table-striped table-sm mt-2">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>Pedido</th>
                            <th>Turno</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Método</th>
                            <th>Cajero</th>
                            <th>Costo</th>
                            <th>Efectivo</th>
                            <th>Banco</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    $totalTab = 0;
                    foreach ($turnosPorMetodo[$metodo] as $reg):
                        $totalTab += (float)($reg['costo'] ?? 0);
                        $tSol = $reg['tipo_solicitud'] ?? '';
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reg['fecha'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($reg['id_pedido'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($reg['turno'] ?? ''); ?></td>
                            <td><?php echo $tiposSol[$tSol] ?? 'Mesas'; ?></td>
                            <td><?php echo htmlspecialchars($reg['estado'] ?? ''); ?></td>
                            <td><?php echo $nombresMetodos[$reg['m_pago'] ?? ''] ?? ucfirst($reg['m_pago'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($reg['cajero'] ?? ''); ?></td>
                            <td>$<?php echo number_format($reg['costo'] ?? 0, 0, '', ','); ?></td>
                            <td>$<?php echo number_format($reg['efectivo'] ?? 0, 0, '', ','); ?></td>
                            <td><?php echo htmlspecialchars($reg['banco'] ?? ''); ?></td>
                            <td>
                                <button class="btn btn-info btn-sm btn-detalle" 
                                        data-pedido="<?php echo htmlspecialchars($reg['id_pedido'] ?? ''); ?>"
                                        data-bs-toggle="modal" data-bs-target="#detalleModal">
                                    Ver
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <td colspan="7" class="text-end"><strong>Total Tab:</strong></td>
                            <td colspan="4"><strong>$<?php echo number_format($totalTab, 0, '', ','); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════ -->
<!-- MODAL CERRAR CAJA                          -->
<!-- ═══════════════════════════════════════════ -->
<div class="modal fade" id="modalCierreCaja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">🔐 Cerrar Caja del Día</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <strong>⚠️ Confirmación:</strong> Estás a punto de cerrar la caja de <strong id="nombre_cajero_cierre">-</strong> para <strong id="fecha_cierre">-</strong>
                </div>

                <div class="card bg-light p-3 mb-3">
                    <h6>📊 Resumen del Cierre</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Efectivo:</strong></td>
                            <td id="cierre_efectivo">$0</td>
                        </tr>
                        <tr>
                            <td><strong>Tarjeta:</strong></td>
                            <td id="cierre_tarjeta">$0</td>
                        </tr>
                        <tr>
                            <td><strong>Transferencia:</strong></td>
                            <td id="cierre_transferencia">$0</td>
                        </tr>
                        <tr>
                            <td><strong>Brebe:</strong></td>
                            <td id="cierre_brebe">$0</td>
                        </tr>
                        <tr>
                            <td><strong>Devoluciones:</strong></td>
                            <td id="cierre_devolucion">$0</td>
                        </tr>
                        <tr class="table-dark">
                            <td><strong>TOTAL GENERAL:</strong></td>
                            <td id="cierre_total">$0</td>
                        </tr>
                    </table>
                </div>

                <form id="formCierreCaja">
                    <input type="hidden" id="hidden_cajero" value="">
                    <input type="hidden" id="hidden_fecha" value="">

                    <div class="form-group mb-3">
                        <label>Comentarios (Opcional):</label>
                        <textarea class="form-control" id="comentarios_cierre" rows="3" placeholder="Ej: Billetes faltantes, diferencias, etc."></textarea>
                    </div>

                    <button type="button" class="btn btn-danger w-100" onclick="confirmarCierreCaja()">
                        🔐 Confirmar Cierre de Caja
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════ -->
<!-- Modal Detalle Pedido                       -->
<!-- ═══════════════════════════════════════════ -->
<div class="modal fade" id="detalleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detallePedidoContent">
                Cargando...
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // ─── Cargar detalle en modal ─────────────────────
    const detalleModal = document.getElementById('detalleModal');
    if (detalleModal) {
        detalleModal.addEventListener('show.bs.modal', function(e) {
            const pedido = e.relatedTarget?.dataset?.pedido;
            const content = document.getElementById('detallePedidoContent');
            if (!pedido) { content.innerHTML = '<p>Sin ID de pedido.</p>'; return; }

            content.innerHTML = '<p>Cargando...</p>';

            fetch('../api.php?route=reporte/detalle_pedido&id_pedido=' + pedido)
            .then(r => r.text())
            .then(html => { content.innerHTML = html; })
            .catch(err => { content.innerHTML = '<p>Error al cargar: ' + err.message + '</p>'; });
        });
    }
});

// ─── FUNCIÓN: Abrir Modal Cierre Caja ────────
function abrirModalCierreCaja() {
    const cajeroSelect = document.getElementById('select_cajero');
    const cajeroId = cajeroSelect.value;
    const cajeroNombre = cajeroSelect.options[cajeroSelect.selectedIndex].text;
    const fecha = document.getElementById('fecha_seleccionada').value;

    // Llenar datos en el modal
    document.getElementById('nombre_cajero_cierre').textContent = cajeroNombre;
    document.getElementById('fecha_cierre').textContent = fecha;
    document.getElementById('hidden_cajero').value = cajeroId;
    document.getElementById('hidden_fecha').value = fecha;

    // Llenar datos del resumen
    document.getElementById('cierre_efectivo').textContent = document.getElementById('h4_efectivo').textContent;
    document.getElementById('cierre_tarjeta').textContent = document.getElementById('h4_tarjeta').textContent;
    document.getElementById('cierre_transferencia').textContent = document.getElementById('h4_transferencia').textContent;
    document.getElementById('cierre_brebe').textContent = document.getElementById('h4_brebe').textContent;
    document.getElementById('cierre_devolucion').textContent = document.getElementById('h4_devolucion').textContent;

    // Calcular total
    const efectivo = parseFloat(document.getElementById('h4_efectivo').textContent.replace(/[^\d]/g, '')) || 0;
    const tarjeta = parseFloat(document.getElementById('h4_tarjeta').textContent.replace(/[^\d]/g, '')) || 0;
    const transf = parseFloat(document.getElementById('h4_transferencia').textContent.replace(/[^\d]/g, '')) || 0;
    const brebe = parseFloat(document.getElementById('h4_brebe').textContent.replace(/[^\d]/g, '')) || 0;
    const devol = parseFloat(document.getElementById('h4_devolucion').textContent.replace(/[^\d]/g, '')) || 0;
    const total = efectivo + tarjeta + transf + brebe - devol;

    document.getElementById('cierre_total').textContent = '$' + total.toLocaleString('es-CO', {maximumFractionDigits: 0});

    // Abrir modal
    new bootstrap.Modal(document.getElementById('modalCierreCaja')).show();
}

// ─── FUNCIÓN: Confirmar Cierre de Caja ──────
async function confirmarCierreCaja() {
    const cajeroId = document.getElementById('hidden_cajero').value;
    const fecha = document.getElementById('hidden_fecha').value;
    const comentarios = document.getElementById('comentarios_cierre').value;

    const efectivo = parseFloat(document.getElementById('h4_efectivo').textContent.replace(/[^\d]/g, '')) || 0;
    const tarjeta = parseFloat(document.getElementById('h4_tarjeta').textContent.replace(/[^\d]/g, '')) || 0;
    const transf = parseFloat(document.getElementById('h4_transferencia').textContent.replace(/[^\d]/g, '')) || 0;
    const brebe = parseFloat(document.getElementById('h4_brebe').textContent.replace(/[^\d]/g, '')) || 0;
    const devol = parseFloat(document.getElementById('h4_devolucion').textContent.replace(/[^\d]/g, '')) || 0;
    const total = efectivo + tarjeta + transf + brebe - devol;

    const datos = {
        id_cajero: cajeroId,
        fecha_cierre: fecha,
        total_efectivo: efectivo,
        total_tarjeta: tarjeta,
        total_transferencia: transf,
        total_brebe: brebe,
        total_devolucion: devol,
        total_general: total,
        comentarios: comentarios
    };

    try {
        const response = await fetch('../app/controllers/cierre_caja_controller.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });

        const resultado = await response.json();

        if (resultado.status === 'success') {
            alert('✅ ' + resultado.message);
            bootstrap.Modal.getInstance(document.getElementById('modalCierreCaja')).hide();
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('❌ Error: ' + resultado.message);
        }
    } catch (error) {
        alert('❌ Error de conexión: ' + error.message);
    }
}

// ─── FUNCIÓN: Descargar Excel Cierre ─────────
function descargarExcelCierre() {
    const cajeroId = document.getElementById('select_cajero').value;
    const fecha = document.getElementById('fecha_seleccionada').value;
    window.location.href = '../app/controllers/excel_cierre_caja.php?id_cajero=' + cajeroId + '&fecha=' + fecha;
}

// ─── Imprimir Datos ──────────────────────────
async function imprimirDatos() {
    const fecha = document.getElementById('fecha_seleccionada').value;

    if (typeof qz === 'undefined') {
        alert('QZ Tray no disponible. Verifica que esté abierto.');
        return;
    }

    try {
        const response = await fetch('../api.php?route=reporte/productos&fecha=' + fecha);
        const pedidos = await response.json();

        if (pedidos.error) { alert(pedidos.error); return; }

        let agrupados = {};
        let totalFinal = 0;

        pedidos.forEach(p => {
            const key = `${p.nombre_producto} - ${p.tipo_producto}`;
            const cant = Number(p.cantidad_total) || 0;
            const precio = Number(p.precio_unitario) || 0;
            const totalP = cant * precio;

            if (agrupados[key]) {
                agrupados[key].cantidad += cant;
                agrupados[key].totalProducto += totalP;
            } else {
                agrupados[key] = { cantidad: cant, precio, totalProducto: totalP };
            }
            totalFinal += totalP;
        });

        let ticket = "\x1B\x40";
        ticket += "\x1B\x61\x01\x1B\x21\x20REPORTE DE VENTAS\x1B\x21\x00\n";
        ticket += "------------------------------------------\n";
        ticket += "Producto (Tipo)          Cantidad   Total\n";
        ticket += "------------------------------------------\n";

        for (let key in agrupados) {
            const { cantidad, totalProducto } = agrupados[key];
            const [prod, tipo] = key.split(' - ');
            ticket += `${prod.padEnd(15)} ${(tipo||'').padEnd(10)} ${cantidad.toString().padEnd(5)} $${totalProducto.toLocaleString('es-CO')}\n`;
        }

        ticket += "------------------------------------------\n";
        ticket += `TOTAL GENERAL: $${totalFinal.toLocaleString('es-CO')}\n`;
        ticket += "==========================================\n";
        ticket += "\n\n\n\x1D\x56\x00\x1B\x70\x00\x19\xFA";

        await ensureConnection();
        const printer = await qz.printers.getDefault();
        if (!printer) return;
        await qz.print(qz.configs.create(printer), [{ type:'raw', format:'plain', data: ticket }]);
    } catch (err) {
        console.error('Error:', err);
    }
}

// ─── Imprimir Resumen ────────────────────────
async function imprimirResumen() {
    if (typeof qz === 'undefined') {
        alert('QZ Tray no disponible.');
        return;
    }

    const fecha = document.getElementById('fecha_seleccionada').value;
    const efectivo = document.getElementById('h4_efectivo')?.textContent || '$0';
    const tarjeta  = document.getElementById('h4_tarjeta')?.textContent || '$0';
    const transf   = document.getElementById('h4_transferencia')?.textContent || '$0';
    const brebe    = document.getElementById('h4_brebe')?.textContent || '$0';
    const devol    = document.getElementById('h4_devolucion')?.textContent || '$0';

    let ticket = "\x1B\x40";
    ticket += "\x1B\x61\x01\x1B\x21\x20RESUMEN DE VENTAS\x1B\x21\x00\n";
    ticket += `Fecha: ${fecha}\n`;
    ticket += "------------------------------------------\n";
    ticket += `Total Efectivo:       ${efectivo}\n`;
    ticket += `Total Tarjetas:       ${tarjeta}\n`;
    ticket += `Total Transferencias: ${transf}\n`;
    ticket += `Total Brebe:          ${brebe}\n`;
    ticket += `Total Devoluciones:   ${devol}\n`;
    ticket += "==========================================\n";
    ticket += "\n\n\n\x1D\x56\x00\x1B\x70\x00\x19\xFA";

    try {
        await ensureConnection();
        const printer = await qz.printers.getDefault();
        if (!printer) return;
        await qz.print(qz.configs.create(printer), [{ type:'raw', format:'plain', data: ticket }]);
    } catch (err) {
        console.error('Error:', err);
    }
}

function ensureConnection() {
    return qz.websocket.connect({ host:'localhost', secure:false }).then(() => {
        console.log('Conectado a QZ Tray.');
    }).catch(err => {
        console.error('Error QZ:', err);
        alert('No se pudo conectar a QZ Tray.');
    });
}
</script>