<?php
/**
 * views/meseros.php
 * UBICACIÓN: heiyubai/views/meseros.php
 * 
 * ✅ Cargado vía public/index.php (bootstrap ya ejecutado)
 * ✅ Usa Database::getInstance() en vez de require helpers/Session
 * ✅ Bootstrap 5 modals (data-bs-toggle, data-bs-target, data-bs-dismiss)
 * ✅ Fetch a api.php en vez de ../controllers/
 * ✅ CORREGIDO: URLs con meseros/* en lugar de catalogo/mesero/*
 */

// Bootstrap ya inició sesión y cargó DB vía public/index.php
$db   = Database::getInstance()->getConnection();

// Paginación
$page            = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$productsPerPage = 10;
$offset          = ($page - 1) * $productsPerPage;

$totalStmt    = $db->query("SELECT COUNT(*) FROM meseros");
$totalProducts = $totalStmt->fetchColumn();
$totalPages   = ceil($totalProducts / $productsPerPage);

$stmt = $db->prepare("SELECT * FROM meseros LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$meseros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener roles disponibles para el select
$rolesDisponibles = [];
try {
    $stmtRoles = $db->query("SELECT nombre FROM roles ORDER BY nombre");
    $rolesDisponibles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Si no existe tabla roles, usar lista hardcoded
    $rolesDisponibles = ['admin', 'cajero', 'domi', 'turno', 'mesero', 'super', 'subadmin'];
}

// URL base para API
$apiBase = BASE_URL . '/api.php?route=';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Colaboradores</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#agregarMeseroModal">
            <i class="fas fa-plus me-1"></i> Agregar
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Cédula</th>
                    <th>Código</th>
                    <th>Cargo</th>
                    <th style="width:120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($meseros as $mesero): ?>
                <tr>
                    <td><?= htmlspecialchars($mesero['id_mese'] ?? '') ?></td>
                    <td>
                        <a href="index.php?page=repor_mese.php&idmeser=<?= htmlspecialchars($mesero['id_mese'] ?? '') ?>" 
                           class="text-decoration-none fw-semibold">
                            <?= htmlspecialchars($mesero['nombre_mese'] ?? '') ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($mesero['phon_mese'] ?? '') ?></td>
                    <td><?= htmlspecialchars($mesero['cedula_mese'] ?? '') ?></td>
                    <td><code><?= htmlspecialchars($mesero['cod_mese'] ?? '') ?></code></td>
                    <td>
                        <span class="badge bg-secondary"><?= htmlspecialchars($mesero['cargo'] ?? '') ?></span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-warning btn-edit" 
                                data-id="<?= $mesero['id_mese'] ?>"
                                data-bs-toggle="modal" 
                                data-bs-target="#editarMeseroModal"
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-delete" 
                                data-id="<?= $mesero['id_mese'] ?>"
                                title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination justify-content-center">
            <?php 
            $baseUrl   = 'index.php?page=meseros.php';
            $startPage = max(1, $page - 5);
            $endPage   = min($totalPages, $page + 4);
            ?>

            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>&pagina=<?= $page - 1 ?>">Anterior</a>
            </li>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                <a class="page-link" href="<?= $baseUrl ?>&pagina=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>&pagina=<?= $page + 1 ?>">Siguiente</a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- ═══ Modal Agregar Mesero ═══ -->
<div class="modal fade" id="agregarMeseroModal" tabindex="-1" aria-labelledby="agregarMeseroModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agregarMeseroModalLabel">Agregar Colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="agregar-mesero-form">
                    <div class="mb-3">
                        <label for="nombre_mese" class="form-label">Nombre:</label>
                        <input type="text" class="form-control" id="nombre_mese" name="nombre_mese" required>
                    </div>
                    <div class="mb-3">
                        <label for="phon_mese" class="form-label">Teléfono:</label>
                        <input type="text" class="form-control" id="phon_mese" name="phon_mese" required>
                    </div>
                    <div class="mb-3">
                        <label for="cedula_mese" class="form-label">Cédula:</label>
                        <input type="text" class="form-control" id="cedula_mese" name="cedula_mese" required>
                    </div>
                    <div class="mb-3">
                        <label for="cargo_mese" class="form-label">Cargo:</label>
                        <select class="form-select" id="cargo_mese" name="cargo_mese" required>
                            <?php foreach ($rolesDisponibles as $rol): ?>
                            <option value="<?= htmlspecialchars($rol) ?>"><?= htmlspecialchars(ucfirst($rol)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cod_mese" class="form-label">Código:</label>
                        <input type="number" class="form-control" id="cod_mese" name="cod_mese" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Agregar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ═══ Modal Editar Mesero ═══ -->
<div class="modal fade" id="editarMeseroModal" tabindex="-1" aria-labelledby="editarMeseroModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarMeseroModalLabel">Editar Colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editar-mesero-form">
                    <input type="hidden" id="edit-id_mese" name="id_mese">
                    <div class="mb-3">
                        <label for="edit-nombre_mese" class="form-label">Nombre:</label>
                        <input type="text" class="form-control" id="edit-nombre_mese" name="nombre_mese" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-phon_mese" class="form-label">Teléfono:</label>
                        <input type="text" class="form-control" id="edit-phon_mese" name="phon_mese" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-cedula_mese" class="form-label">Cédula:</label>
                        <input type="text" class="form-control" id="edit-cedula_mese" name="cedula_mese" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-cargo_mese" class="form-label">Cargo:</label>
                        <select class="form-select" id="edit-cargo_mese" name="cargo_mese" required>
                            <?php foreach ($rolesDisponibles as $rol): ?>
                            <option value="<?= htmlspecialchars($rol) ?>"><?= htmlspecialchars(ucfirst($rol)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit-cod_mese" class="form-label">Código:</label>
                        <input type="number" class="form-control" id="edit-cod_mese" name="cod_mese" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const API = '<?= $apiBase ?>';

    // ═══ AGREGAR ═══
    document.getElementById('agregar-mesero-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch(API + 'meseros/agregar', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success || data.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Colaborador agregado', timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || data.error || 'Error desconocido' });
            }
        })
        .catch(err => {
            console.error('Error:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
        });
    });

    // ═══ CARGAR DATOS PARA EDITAR ═══
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;

            fetch(API + 'meseros/obtener&id_mese=' + id)
            .then(r => r.json())
            .then(data => {
                if (data.success || data.status === 'success') {
                    const m = data.mesero || data.data;
                    document.getElementById('edit-id_mese').value      = m.id_mese;
                    document.getElementById('edit-nombre_mese').value  = m.nombre_mese;
                    document.getElementById('edit-phon_mese').value    = m.phon_mese;
                    document.getElementById('edit-cedula_mese').value  = m.cedula_mese;
                    document.getElementById('edit-cargo_mese').value   = m.cargo;
                    document.getElementById('edit-cod_mese').value     = m.cod_mese;
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo obtener datos' });
                }
            })
            .catch(err => {
                console.error('Error:', err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
            });
        });
    });

    // ═══ GUARDAR EDICIÓN ═══
    document.getElementById('editar-mesero-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch(API + 'meseros/editar', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success || data.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Colaborador actualizado', timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || data.error || 'Error desconocido' });
            }
        })
        .catch(err => {
            console.error('Error:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
        });
    });

    // ═══ ELIMINAR ═══
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;

            Swal.fire({
                title: '¿Eliminar colaborador?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    fetch(API + 'meseros/eliminar&id_mese=' + id, { method: 'DELETE' })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success || data.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1500, showConfirmButton: false })
                                .then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Error desconocido' });
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
                    });
                }
            });
        });
    });
});
</script>