<?php
/**
 * gastos.php
 * Sistema completo de gestión de gastos, vales y créditos
 * Ubicación: views/gastos.php
 */

// Rutas correctas para views/
require_once dirname(__DIR__) . '/config/database.php';

// La sesión ya está iniciada en public/index.php, no iniciar de nuevo
// Alertas de sesión
$successMessage = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$errorMessage = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$warningMessage = isset($_SESSION['warning']) ? $_SESSION['warning'] : '';

unset($_SESSION['success'], $_SESSION['error'], $_SESSION['warning']);

$database = new Database();
$conn = $database->getConnection();

// =====================================================
// 1) GASTOS DEL DÍA
// =====================================================
$fecha_seleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$gastosDelDia = [];

try {
    $sql = "
        SELECT id, fecha, concepto, categoria, monto, cajero
        FROM gastos
        WHERE DATE(fecha) = ?
        ORDER BY fecha DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$fecha_seleccionada]);
    $gastosDelDia = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errorMessage = "Error al cargar gastos: " . $e->getMessage();
}

// =====================================================
// 2) VALES - AGRUPADOS POR CAJERO
// =====================================================
function obtenerValesPorMesCajero($conn, $cajero) {
    $sql = "
        SELECT 
            DATE_FORMAT(g.fecha, '%Y-%m') as mes,
            DATE_FORMAT(g.fecha, '%B %Y') as mes_texto,
            COUNT(g.id) as cantidad,
            SUM(CAST(g.monto AS DECIMAL(10,2))) as total
        FROM gastos g
        WHERE g.cajero = :cajero 
              AND g.categoria = 'vales'
              AND g.estado = 0
        GROUP BY DATE_FORMAT(g.fecha, '%Y-%m')
        ORDER BY g.fecha DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':cajero', $cajero, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerValesDetalleMesCajero($conn, $cajero, $mes) {
    $sql = "
        SELECT 
            g.id, 
            g.fecha, 
            g.concepto, 
            g.monto, 
            m.nombre_mese,
            m.cargo
        FROM gastos g
        LEFT JOIN meseros m ON g.id_mesero = m.id_mese
        WHERE g.cajero = :cajero 
              AND g.categoria = 'vales'
              AND g.estado = 0
              AND DATE_FORMAT(g.fecha, '%Y-%m') = :mes
        ORDER BY g.fecha DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':cajero', $cajero, PDO::PARAM_STR);
    $stmt->bindParam(':mes', $mes, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener cajeros con vales pendientes
$sql = "
    SELECT 
        g.cajero,
        COUNT(g.id) as cantidad_vales,
        SUM(CAST(g.monto AS DECIMAL(10,2))) as total_monto
    FROM gastos g
    WHERE g.categoria = 'vales' AND g.estado = 0
    GROUP BY g.cajero
    ORDER BY total_monto DESC, g.cajero ASC
";
$stmtCajeros = $conn->prepare($sql);
$stmtCajeros->execute();
$cajerosValesTodo = $stmtCajeros->fetchAll(PDO::FETCH_ASSOC);

// =====================================================
// 3) CRÉDITOS PENDIENTES
// =====================================================
$sql = "
    SELECT 
        cr.idcr,
        cr.fecha,
        cr.id_clientecr,
        cr.m_pedidocr,
        c.cliente,
        SUM(CAST(ac.m_pagocr AS DECIMAL(10,2))) as total_pagado,
        (CAST(cr.m_pedidocr AS DECIMAL(10,2)) - COALESCE(SUM(CAST(ac.m_pagocr AS DECIMAL(10,2))), 0)) as saldo
    FROM creditos cr
    LEFT JOIN clientes c ON cr.id_clientecr = c.id
    LEFT JOIN abono_credito ac ON cr.idcr = ac.id_credito
    GROUP BY cr.idcr, cr.fecha, cr.id_clientecr, cr.m_pedidocr, c.cliente
    HAVING saldo > 0
    ORDER BY cr.fecha DESC
";
$stmtCreditos = $conn->prepare($sql);
$stmtCreditos->execute();
$creditosPendientes = $stmtCreditos->fetchAll(PDO::FETCH_ASSOC);

// Obtener meseros para select en formulario de vales
$sql = "SELECT id_mese, nombre_mese, cargo, phon_mese FROM meseros ORDER BY nombre_mese ASC";
$stmtMeseros = $conn->prepare($sql);
$stmtMeseros->execute();
$meseros = $stmtMeseros->fetchAll(PDO::FETCH_ASSOC);

// =====================================================
// 4) FUNCIÓN: Obtener abonos de un crédito
// =====================================================
function obtenerAbonosCredito($conn, $id_credito) {
    $sql = "
        SELECT 
            id,
            id_credito,
            m_pagocr,
            efectivo,
            fecha_abono
        FROM abono_credito
        WHERE id_credito = ?
        ORDER BY fecha_abono DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_credito]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gastos</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f5f5f5; padding: 20px 0; }
        .card { border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .alert { margin-bottom: 20px; }
        .search-box { margin: 15px 0; }
        .search-box input { border-radius: 20px; padding: 10px 15px; }
        .table-sm th { font-weight: bold; }
        .badge { padding: 8px 12px; }
        .modal-header { border-bottom: 2px solid; }
        .card-header { font-weight: bold; }
        @media (max-width: 768px) {
            .table { font-size: 13px; }
            .btn { padding: 5px 10px; font-size: 12px; }
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <!-- ALERTAS -->
    <?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>✅ Éxito:</strong> <?php echo htmlspecialchars($successMessage); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>❌ Error:</strong> <?php echo htmlspecialchars($errorMessage); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <?php if ($warningMessage): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>⚠️ Advertencia:</strong> <?php echo htmlspecialchars($warningMessage); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <!-- TABS -->
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="gastosTab-link" data-toggle="tab" href="#gastosTab" role="tab">
                        📊 Gastos del Día
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="valesTab-link" data-toggle="tab" href="#valesTab" role="tab">
                        💳 Vales Pendientes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="creditosTab-link" data-toggle="tab" href="#creditosTab" role="tab">
                        💰 Créditos Pendientes
                    </a>
                </li>
            </ul>

            <div class="tab-content">

                <!-- TAB 1: GASTOS DEL DÍA -->
                <div class="tab-pane fade show active" id="gastosTab" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            Gastos del Día
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Seleccionar Fecha:</label>
                                <input type="date" id="fecha-gastos" class="form-control" value="<?php echo $fecha_seleccionada; ?>">
                            </div>

                            <?php if (!empty($gastosDelDia)): ?>
                            <table class="table table-sm table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Concepto</th>
                                        <th>Categoría</th>
                                        <th>Monto</th>
                                        <th>Cajero</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total = 0;
                                    foreach ($gastosDelDia as $gasto): 
                                        $total += floatval($gasto['monto']);
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($gasto['fecha']); ?></td>
                                        <td><?php echo htmlspecialchars($gasto['concepto']); ?></td>
                                        <td><span class="badge badge-secondary"><?php echo htmlspecialchars($gasto['categoria']); ?></span></td>
                                        <td><strong>$<?php echo number_format($gasto['monto'], 2, ',', '.'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($gasto['cajero']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-dark">
                                        <td colspan="3" class="text-right"><strong>TOTAL:</strong></td>
                                        <td colspan="2"><strong>$<?php echo number_format($total, 2, ',', '.'); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="alert alert-info">No hay gastos registrados para esta fecha</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: VALES PENDIENTES -->
                <div class="tab-pane fade" id="valesTab" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            Vales Pendientes por Cajero
                        </div>
                        <div class="card-body">
                            <div class="search-box">
                                <input type="text" id="busqueda-vales" class="form-control" 
                                       placeholder="🔍 Buscar cajero en tiempo real...">
                            </div>

                            <?php if (!empty($cajerosValesTodo)): ?>
                            <table class="table table-bordered table-hover table-vales">
                                <thead class="table-dark">
                                    <tr>
                                        <th>👤 Cajero (Quién Hizo el Vale)</th>
                                        <th>📊 # Vales</th>
                                        <th>💰 Total</th>
                                        <th style="width: 80px; text-align: center;">⚙️</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cajerosValesTodo as $cajero): ?>
                                    <tr style="cursor: pointer;" data-toggle="modal" 
                                        data-target="#valesModal-<?php echo md5($cajero['cajero']); ?>">
                                        <td><strong>👤 <?php echo htmlspecialchars($cajero['cajero'] ?: '—'); ?></strong></td>
                                        <td><span class="badge badge-info"><?php echo $cajero['cantidad_vales']; ?></span></td>
                                        <td><strong>$<?php echo number_format($cajero['total_monto'], 2, ',', '.'); ?></strong></td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-success">Ver</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="alert alert-info">No hay vales pendientes</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: CRÉDITOS PENDIENTES -->
                <div class="tab-pane fade" id="creditosTab" role="tabpanel">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            Créditos Pendientes
                        </div>
                        <div class="card-body">
                            <div class="search-box">
                                <input type="text" id="busqueda-creditos" class="form-control" 
                                       placeholder="🔍 Buscar cliente en tiempo real...">
                            </div>

                            <?php if (!empty($creditosPendientes)): ?>
                            <table class="table table-bordered table-hover table-creditos">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Pedido #</th>
                                        <th>Total</th>
                                        <th>Pagado</th>
                                        <th>Saldo</th>
                                        <th style="width: 100px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($creditosPendientes as $credito): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($credito['idcr']); ?></td>
                                        <td><?php echo htmlspecialchars($credito['cliente'] ?: '—'); ?></td>
                                        <td>#<?php echo htmlspecialchars($credito['m_pedidocr']); ?></td>
                                        <td>$<?php echo number_format($credito['m_pedidocr'], 2, ',', '.'); ?></td>
                                        <td>$<?php echo number_format($credito['total_pagado'] ?: 0, 2, ',', '.'); ?></td>
                                        <td><strong class="text-danger">$<?php echo number_format($credito['saldo'], 2, ',', '.'); ?></strong></td>
                                        <td>
                                            <button class="btn btn-xs btn-primary btn-sm" data-toggle="modal"
                                                    data-target="#abonosModal-<?php echo $credito['idcr']; ?>">
                                                💳
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="alert alert-success">✅ No hay créditos pendientes</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- PANEL DERECHO: FORMULARIO INGRESAR GASTO -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    📝 Ingresar Nuevo Gasto
                </div>
                <div class="card-body">
                    <form id="gastos-form">
                        <div class="form-group">
                            <label>Fecha:</label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Concepto:</label>
                            <textarea name="concepto" class="form-control" rows="2" required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Categoría:</label>
                            <select name="categoria" id="categoria" class="form-control" required>
                                <option value="">-- Selecciona --</option>
                                <option value="vales">💳 Vales</option>
                                <option value="servicios">🔧 Servicios</option>
                                <option value="suministros">📦 Suministros</option>
                                <option value="mantenimiento">🛠️ Mantenimiento</option>
                                <option value="gastos_varios">📌 Gastos Varios</option>
                            </select>
                        </div>

                        <!-- Select de Meseros (solo si es Vales) -->
                        <div class="form-group" id="mesero-group" style="display: none;">
                            <label>Para Quién (Colaborador):</label>
                            <select name="mesero" class="form-control">
                                <option value="">-- Opcional --</option>
                                <?php foreach ($meseros as $mesero): ?>
                                <option value="<?php echo $mesero['id_mese']; ?>">
                                    <?php echo htmlspecialchars($mesero['nombre_mese']); ?> 
                                    (<?php echo htmlspecialchars($mesero['cargo']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Monto:</label>
                            <input type="number" name="monto" class="form-control" step="0.01" min="0" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            ✅ Ingresar Gasto
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ========================================== -->
<!-- MODALES PARA VALES -->
<!-- ========================================== -->
<?php foreach ($cajerosValesTodo as $cajeroData): ?>
<?php
    $cajeroProcesado = $cajeroData['cajero'];
    $valesPorMesCajero = obtenerValesPorMesCajero($conn, $cajeroProcesado);
    $cajerHashId = md5($cajeroProcesado);
?>

<div class="modal fade" id="valesModal-<?php echo $cajerHashId; ?>" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">📋 Vales Creados por <?php echo htmlspecialchars($cajeroProcesado); ?></h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-light mb-3">
          <strong>👤 Cajero:</strong> <?php echo htmlspecialchars($cajeroProcesado); ?><br>
          <strong>💰 Total Pendiente:</strong> <span class="text-danger h5">$<?php echo number_format($cajeroData['total_monto'], 2, ',', '.'); ?></span>
        </div>

        <?php if (!empty($valesPorMesCajero)): ?>
          <?php foreach ($valesPorMesCajero as $mesData): ?>
            <?php $valesDetalleCajero = obtenerValesDetalleMesCajero($conn, $cajeroProcesado, $mesData['mes']); ?>
            <div class="card mb-3 border-primary">
              <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                  <strong>📅 <?php echo htmlspecialchars($mesData['mes_texto']); ?></strong>
                  <span class="badge badge-light ml-2"><?php echo $mesData['cantidad']; ?> vales</span>
                </div>
                <div><strong>$<?php echo number_format($mesData['total'], 2, ',', '.'); ?></strong></div>
              </div>
              <div class="card-body">
                <table class="table table-sm table-bordered mb-3">
                  <thead class="table-light">
                    <tr>
                      <th>Fecha</th>
                      <th>Para Quién</th>
                      <th>Concepto</th>
                      <th>Monto</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($valesDetalleCajero as $vale): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($vale['fecha']); ?></td>
                      <td>
                        <strong><?php echo htmlspecialchars($vale['nombre_mese'] ?: '—'); ?></strong>
                        <?php if ($vale['cargo']): ?>
                          <br><small class="text-muted"><?php echo htmlspecialchars($vale['cargo']); ?></small>
                        <?php endif; ?>
                      </td>
                      <td><?php echo htmlspecialchars($vale['concepto']); ?></td>
                      <td><strong>$<?php echo number_format($vale['monto'], 2, ',', '.'); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>

                <button type="button" class="btn btn-success btn-sm" 
                        data-toggle="modal" 
                        data-target="#saldarMesModal-<?php echo $cajerHashId; ?>-<?php echo str_replace('-', '_', $mesData['mes']); ?>"
                        data-dismiss="modal">
                  ✅ Saldar <?php echo htmlspecialchars($mesData['mes_texto']); ?>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="alert alert-warning">⚠️ No hay vales pendientes</p>
        <?php endif; ?>

        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- MODALES PARA SALDAR -->
<?php foreach ($valesPorMesCajero as $mesData): ?>
<?php $mesKey = str_replace('-', '_', $mesData['mes']); ?>
<div class="modal fade" id="saldarMesModal-<?php echo $cajerHashId; ?>-<?php echo $mesKey; ?>" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">✅ Saldar - <?php echo htmlspecialchars($mesData['mes_texto']); ?></h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning">
          <strong>⚠️ ¿Marcar como PAGADOS <?php echo $mesData['cantidad']; ?> vales de <?php echo htmlspecialchars($cajeroProcesado); ?>?</strong>
        </div>
        
        <div class="card bg-light p-3 mb-3">
          <p><strong>👤 Cajero:</strong> <?php echo htmlspecialchars($cajeroProcesado); ?></p>
          <p><strong>📅 Período:</strong> <?php echo htmlspecialchars($mesData['mes_texto']); ?></p>
          <p><strong>📊 Vales:</strong> <?php echo $mesData['cantidad']; ?></p>
          <p><strong>💰 Total:</strong> <span class="text-danger h5">$<?php echo number_format($mesData['total'], 2, ',', '.'); ?></span></p>
        </div>

        <form action="../app/controllers/gastos_controller.php" method="POST" class="mt-3">
          <input type="hidden" name="action" value="saldar_vales">
          <input type="hidden" name="cajero" value="<?php echo htmlspecialchars($cajeroProcesado); ?>">
          <input type="hidden" name="mes_vales" value="<?php echo $mesData['mes']; ?>">
          
          <div class="form-group">
            <label>Fecha de Pago:</label>
            <input type="date" name="fecha_pago" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
          </div>

          <div class="form-group">
            <label>Método de Pago:</label>
            <select name="metodo_pago" class="form-control" required>
              <option value="">-- Selecciona --</option>
              <option value="efectivo">💰 Efectivo</option>
              <option value="transferencia">💳 Transferencia</option>
              <option value="tarjeta">🏧 Tarjeta</option>
              <option value="cheque">📄 Cheque</option>
            </select>
          </div>

          <div class="form-group">
            <label>Referencia (Opcional):</label>
            <input type="text" name="referencia" class="form-control" placeholder="Cheque #, referencia...">
          </div>

          <button type="submit" class="btn btn-success btn-block">✅ Confirmar Pago</button>
          <button type="button" class="btn btn-secondary btn-block mt-2" data-dismiss="modal">Cancelar</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?php endforeach; ?>

<!-- ========================================== -->
<!-- MODALES PARA CRÉDITOS -->
<!-- ========================================== -->
<?php foreach ($creditosPendientes as $credito): ?>
<?php
    $abonosRegistrados = obtenerAbonosCredito($conn, $credito['idcr']);
    $sumaAbonos = 0;
    foreach ($abonosRegistrados as $abono) {
        $sumaAbonos += floatval($abono['efectivo']);
    }
    $saldoRestante = floatval($credito['m_pedidocr']) - $sumaAbonos;
?>
<div class="modal fade" id="abonosModal-<?php echo $credito['idcr']; ?>" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">💳 Crédito #<?php echo $credito['idcr']; ?> - <?php echo htmlspecialchars($credito['cliente']); ?></h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        
        <!-- RESUMEN DEL CRÉDITO -->
        <div class="card bg-light mb-3">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <p><strong>📋 ID Crédito:</strong> #<?php echo $credito['idcr']; ?></p>
                <p><strong>👤 Cliente:</strong> <?php echo htmlspecialchars($credito['cliente']); ?></p>
                <p><strong>🛒 Pedido:</strong> #<?php echo htmlspecialchars($credito['m_pedidocr']); ?></p>
              </div>
              <div class="col-md-6">
                <p><strong>💰 Total Crédito:</strong> <span class="h5">$<?php echo number_format($credito['m_pedidocr'], 2, ',', '.'); ?></span></p>
                <p><strong>✅ Total Abonado:</strong> <span class="h5 text-success">$<?php echo number_format($sumaAbonos, 2, ',', '.'); ?></span></p>
                <p><strong>📊 Saldo Pendiente:</strong> <span class="h5 text-danger">$<?php echo number_format($saldoRestante, 2, ',', '.'); ?></span></p>
              </div>
            </div>
          </div>
        </div>

        <!-- HISTORIAL DE ABONOS -->
        <h6 class="mb-3">📜 Historial de Abonos</h6>
        
        <?php if (!empty($abonosRegistrados)): ?>
          <table class="table table-sm table-bordered">
            <thead class="table-dark">
              <tr>
                <th>Fecha</th>
                <th>Método</th>
                <th>Monto</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($abonosRegistrados as $abono): ?>
              <tr>
                <td><?php echo htmlspecialchars($abono['fecha_abono']); ?></td>
                <td>
                  <?php 
                    $metodos = [
                      'efectivo' => '💰 Efectivo',
                      'transferencia' => '💳 Transferencia',
                      'tarjeta' => '🏧 Tarjeta',
                      'cheque' => '📄 Cheque'
                    ];
                    echo $metodos[$abono['m_pagocr']] ?? htmlspecialchars($abono['m_pagocr']);
                  ?>
                </td>
                <td><strong>$<?php echo number_format($abono['efectivo'], 2, ',', '.'); ?></strong></td>
              </tr>
              <?php endforeach; ?>
              <tr class="table-dark">
                <td colspan="2" class="text-right"><strong>TOTAL ABONADO:</strong></td>
                <td><strong>$<?php echo number_format($sumaAbonos, 2, ',', '.'); ?></strong></td>
              </tr>
            </tbody>
          </table>
        <?php else: ?>
          <div class="alert alert-info">
            ℹ️ No hay abonos registrados aún
          </div>
        <?php endif; ?>

        <hr>

        <!-- FORMULARIO PARA NUEVO ABONO -->
        <h6 class="mb-3">➕ Registrar Nuevo Abono</h6>
        <form class="form-abono-credito" data-id-credito="<?php echo $credito['idcr']; ?>">
          <div class="row">
            <div class="col-md-8">
              <div class="form-group">
                <label>Monto a Abonar (Máximo: <strong>$<?php echo number_format($saldoRestante, 2, ',', '.'); ?></strong>):</label>
                <input type="number" class="form-control monto-abono" step="0.01" min="0" max="<?php echo $saldoRestante; ?>" placeholder="Ingresa el monto" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Método:</label>
                <select class="form-control metodo-abono" required>
                  <option value="">-- Selecciona --</option>
                  <option value="efectivo">💰 Efectivo</option>
                  <option value="transferencia">💳 Transferencia</option>
                  <option value="tarjeta">🏧 Tarjeta</option>
                  <option value="cheque">📄 Cheque</option>
                </select>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-warning btn-block">💳 Registrar Abono</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<!-- ========================================== -->
<!-- SCRIPTS -->
<!-- ========================================== -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // ============================================
    // 1️⃣ BÚSQUEDA DINÁMICA DE VALES
    // ============================================
    const searchVales = document.querySelector('#busqueda-vales');
    if (searchVales) {
        searchVales.addEventListener('keyup', function(e) {
            const query = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.table-vales tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // ============================================
    // 2️⃣ BÚSQUEDA DINÁMICA DE CRÉDITOS
    // ============================================
    const searchCreditos = document.querySelector('#busqueda-creditos');
    if (searchCreditos) {
        searchCreditos.addEventListener('keyup', function(e) {
            const query = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.table-creditos tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // ============================================
    // 3️⃣ CAMBIAR FECHA DE GASTOS
    // ============================================
    const fechaGastos = document.querySelector('#fecha-gastos');
    if (fechaGastos) {
        fechaGastos.addEventListener('change', function() {
            window.location.href = '?page=gastos.php&fecha=' + this.value;
        });
    }

    // ============================================
    // 4️⃣ MOSTRAR/OCULTAR SELECT MESERO
    // ============================================
    const categoria = document.querySelector('#categoria');
    const meseroGroup = document.querySelector('#mesero-group');
    
    if (categoria) {
        categoria.addEventListener('change', function() {
            if (this.value === 'vales') {
                meseroGroup.style.display = 'block';
            } else {
                meseroGroup.style.display = 'none';
            }
        });
    }

    // ============================================
    // 5️⃣ GUARDAR GASTO CON AJAX
    // ============================================
    const gastosForm = document.querySelector('#gastos-form');
    if (gastosForm) {
        gastosForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('../app/controllers/gastos_controller.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                
                if (response.ok) {
                    alert('✅ Gasto guardado correctamente');
                    gastosForm.reset();
                    // Recargar tabla
                    setTimeout(() => {
                        window.location.href = '?page=gastos.php';
                    }, 1000);
                } else {
                    alert('❌ Error: ' + (text || 'Error desconocido'));
                }
            } catch (error) {
                alert('❌ Error de conexión: ' + error.message);
            }
        });
    }

    // ============================================
    // 6️⃣ REGISTRAR ABONO DE CRÉDITO (JSON)
    // ============================================
    const formasAbono = document.querySelectorAll('.form-abono-credito');
    formasAbono.forEach(forma => {
        forma.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const idCredito = parseInt(this.getAttribute('data-id-credito'));
            const montoAbono = parseFloat(this.querySelector('.monto-abono').value);
            const metodoAbono = this.querySelector('.metodo-abono').value;
            
            if (!idCredito || !montoAbono || !metodoAbono) {
                alert('❌ Completa todos los campos');
                return;
            }
            
            // Preparar datos en formato JSON que espera abonar_credito_cliente.php
            const datos = {
                abonos: [
                    {
                        id_credito: idCredito,
                        m_pagocr: metodoAbono,    // efectivo, transferencia, etc
                        efectivo: montoAbono       // monto del abono
                    }
                ]
            };
            
            try {
                const response = await fetch('../app/controllers/abonar_credito_cliente.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datos)
                });
                
                const resultado = await response.json();
                
                if (resultado.status === 'success') {
                    alert('✅ ' + resultado.message);
                    // Cerrar modal y recargar
                    $(this).closest('.modal').modal('hide');
                    setTimeout(() => {
                        window.location.href = '?page=gastos.php';
                    }, 500);
                } else {
                    alert('❌ Error: ' + resultado.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Error de conexión: ' + error.message);
            }
        });
    });

});
</script>

</body>
</html>