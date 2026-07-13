<?php
/**
 * domiciliarios.php - Gestión de Domiciliarios
 * UBICACIÓN: views/domiciliarios.php
 * ✅ Badge "Debe" con conteo de pedidos sin pagar
 * ✅ Compatible con public/index.php, BS5, API routes
 */

try {
    $conn = Database::getInstance()->getConnection();
} catch (Exception $e) {
    echo '<div class="alert alert-danger m-4">Error de conexión: ' . $e->getMessage() . '</div>';
    return;
}

// Paginación
$page = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$productsPerPage = 10;
$offset = ($page - 1) * $productsPerPage;

// Modo: activos o eliminados
$verEliminados = (isset($_GET['ver']) && $_GET['ver'] === 'eliminados');

if ($verEliminados) {
    $totalQuery = "SELECT COUNT(*) FROM domiciliarios WHERE elimina = 0";
    $dataQuery  = "SELECT * FROM domiciliarios WHERE elimina = 0 LIMIT :limit OFFSET :offset";
    $btnText = "Ver Activos";
    $btnLink = "index.php?page=domiciliarios.php";
} else {
    $totalQuery = "SELECT COUNT(*) FROM domiciliarios WHERE elimina = 1";
    $dataQuery  = "SELECT * FROM domiciliarios WHERE elimina = 1 LIMIT :limit OFFSET :offset";
    $btnText = "Ver Eliminados";
    $btnLink = "index.php?page=domiciliarios.php&ver=eliminados";
}

$totalStmt = $conn->prepare($totalQuery);
$totalStmt->execute();
$totalProducts = $totalStmt->fetchColumn();
$totalPages = ceil($totalProducts / $productsPerPage);

$stmt = $conn->prepare($dataQuery);
$stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$domiciliarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Consultar deuda (pedidos sin pagar) por cada domiciliario activo
$deudas = [];
if (!$verEliminados && count($domiciliarios) > 0) {
    // Pedidos de hoy asignados a domiciliarios que NO están en caja
    date_default_timezone_set('America/Bogota');
    
    $stDeuda = $conn->prepare("
        SELECT d.id_domi, COUNT(*) AS pendientes
        FROM domicilios d
        JOIN pedidos p ON d.id_pedido = p.numero_pedido
        WHERE d.id_domi IN (" . implode(',', array_map(function($d){ return (int)$d['id_e']; }, $domiciliarios)) . ")
          AND NOT EXISTS (SELECT 1 FROM caja c WHERE c.id_pedidoc = d.id_pedido)
        GROUP BY d.id_domi
    ");
    $stDeuda->execute();
    foreach ($stDeuda->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $deudas[(int)$row['id_domi']] = (int)$row['pendientes'];
    }
}
?>

<div class="container mt-4">
    <div class="d-flex align-items-center gap-3 mb-3">
        <h3 class="mb-0">Domiciliarios</h3>
        <?php if (!$verEliminados): ?>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#agregarDomiciliarioModal">
                + Agregar
            </button>
        <?php endif; ?>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.location.href='<?php echo $btnLink; ?>'">
            <?php echo $btnText; ?>
        </button>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>N°</th>
                <th>Repartidor</th>
                <th>Celular</th>
                <th>Calificación</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($domiciliarios) > 0): ?>
                <?php foreach ($domiciliarios as $d): 
                    $id = (int)$d['id_e'];
                    $pendientes = $deudas[$id] ?? 0;
                ?>
                <tr data-id="<?php echo $id; ?>">
                    <td><?php echo $id; ?></td>
                    <td>
                        <form action="index.php?page=domicilios.php" method="POST" style="display:inline;">
                            <input type="hidden" name="id_e" value="<?php echo $id; ?>">
                            <button type="submit" class="btn btn-link p-0">
                                <?php echo htmlspecialchars($d['repartidor']); ?>
                            </button>
                        </form>
                    </td>
                    <td><?php echo htmlspecialchars($d['celu_reparti']); ?></td>
                    <td><?php echo htmlspecialchars($d['calificacion']); ?></td>
                    <td>
                        <?php if ($pendientes > 0): ?>
                            <span class="badge bg-danger" style="font-size:0.85rem;">
                                Debe <?php echo $pendientes; ?> pedido<?php echo $pendientes > 1 ? 's' : ''; ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success">Al día</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$verEliminados): ?>
                            <button class="btn btn-warning btn-sm btn-edit" 
                                    data-id="<?php echo $id; ?>"
                                    data-bs-toggle="modal" data-bs-target="#editarDomiciliarioModal"
                                    title="Editar">✏️</button>
                            <button class="btn btn-danger btn-sm btn-delete" 
                                    data-id="<?php echo $id; ?>"
                                    title="Eliminar">🗑️</button>
                        <?php else: ?>
                            <button class="btn btn-success btn-sm btn-restore" 
                                    data-id="<?php echo $id; ?>"
                                    title="Restaurar">🔄 Restaurar</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center text-muted">
                    <?php echo $verEliminados ? 'No hay domiciliarios eliminados.' : 'No hay domiciliarios registrados.'; ?>
                </td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination">
            <?php 
            $baseUrl = 'index.php?page=domiciliarios.php';
            if ($verEliminados) $baseUrl .= '&ver=eliminados';
            $startPage = max(1, $page - 5);
            $endPage = min($totalPages, $page + 4);
            ?>
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="<?php echo $baseUrl; ?>&pagina=<?php echo $page - 1; ?>">Anterior</a></li>
            <?php endif; ?>
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo $baseUrl; ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item"><a class="page-link" href="<?php echo $baseUrl; ?>&pagina=<?php echo $page + 1; ?>">Siguiente</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Modal Agregar (BS5) -->
<div class="modal fade" id="agregarDomiciliarioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Domiciliario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="agregar-domiciliario-form">
                    <div class="mb-2"><label class="form-label">Repartidor</label>
                        <input type="text" class="form-control" name="repartidor" required></div>
                    <div class="mb-2"><label class="form-label">Celular</label>
                        <input type="text" class="form-control" name="celu_reparti" required></div>
                    <div class="mb-2"><label class="form-label">Calificación</label>
                        <input type="text" class="form-control" name="calificacion"></div>
                    <button type="submit" class="btn btn-success w-100">Agregar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar (BS5) -->
<div class="modal fade" id="editarDomiciliarioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Domiciliario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editar-domiciliario-form">
                    <input type="hidden" id="edit-id_e" name="id_e">
                    <div class="mb-2"><label class="form-label">Repartidor</label>
                        <input type="text" class="form-control" id="edit-repartidor" name="repartidor" required></div>
                    <div class="mb-2"><label class="form-label">Celular</label>
                        <input type="text" class="form-control" id="edit-celu_reparti" name="celu_reparti" required></div>
                    <div class="mb-2"><label class="form-label">Calificación</label>
                        <input type="text" class="form-control" id="edit-calificacion" name="calificacion"></div>
                    <button type="submit" class="btn btn-primary w-100">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const API_DOM = '../api.php?route=domiciliarios';

document.addEventListener('DOMContentLoaded', function() {
    // Agregar
    document.getElementById('agregar-domiciliario-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch(API_DOM + '/agregar', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(data => { if (data.status === 'success') { alert('✅ Agregado.'); location.reload(); } else { alert('❌ ' + data.message); } })
        .catch(err => alert('Error: ' + err.message));
    });

    // Editar - cargar datos
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            fetch(API_DOM + '/obtener&id=' + this.dataset.id)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    const d = data.domiciliarios;
                    document.getElementById('edit-id_e').value = d.id_e;
                    document.getElementById('edit-repartidor').value = d.repartidor;
                    document.getElementById('edit-celu_reparti').value = d.celu_reparti;
                    document.getElementById('edit-calificacion').value = d.calificacion || '';
                } else { alert('❌ ' + data.message); }
            }).catch(err => alert('Error: ' + err.message));
        });
    });

    // Editar - guardar
    document.getElementById('editar-domiciliario-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch(API_DOM + '/editar', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(data => { if (data.status === 'success') { alert('✅ Actualizado.'); location.reload(); } else { alert('❌ ' + data.message); } })
        .catch(err => alert('Error: ' + err.message));
    });

    // Eliminar
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('¿Eliminar este domiciliario?')) return;
            fetch(API_DOM + '/eliminar', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({id_e: this.dataset.id}) })
            .then(r => r.json())
            .then(data => { if (data.status === 'success') { alert('✅ Eliminado.'); this.closest('tr')?.remove(); } else { alert('❌ ' + data.message); } })
            .catch(err => alert('Error: ' + err.message));
        });
    });

    // Restaurar
    document.querySelectorAll('.btn-restore').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('¿Restaurar?')) return;
            fetch(API_DOM + '/restaurar', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({id_e: this.dataset.id}) })
            .then(r => r.json())
            .then(data => { if (data.status === 'success') { alert('✅ Restaurado.'); this.closest('tr')?.remove(); } else { alert('❌ ' + data.message); } })
            .catch(err => alert('Error: ' + err.message));
        });
    });
});
</script>