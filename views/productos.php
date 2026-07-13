<?php
/**
 * productos.php - Gestión de Productos
 * UBICACIÓN: views/productos.php
 * 
 * ✅ Se incluye desde public/index.php (bootstrap ya cargó DB, Session)
 * ✅ No hace require_once propios
 * ✅ Bootstrap 5
 */

try {
    $conn = Database::getInstance()->getConnection();
} catch (Exception $e) {
    echo '<div class="alert alert-danger m-4">Error de conexión: ' . $e->getMessage() . '</div>';
    return;
}

// Búsqueda
$busqueda = '';
if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
    $busqueda = trim($_GET['busqueda']);
}

// Paginación
$page = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$productsPerPage = 10;
$offset = ($page - 1) * $productsPerPage;

// Total de productos
$totalQuery = "SELECT COUNT(*) FROM productos p";
if ($busqueda) {
    $totalQuery .= " WHERE p.nombre LIKE :busqueda";
}
$totalStmt = $conn->prepare($totalQuery);
if ($busqueda) {
    $totalStmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
}
$totalStmt->execute();
$totalProducts = $totalStmt->fetchColumn();
$totalPages = ceil($totalProducts / $productsPerPage);

// Consulta paginada
$query = "
    SELECT 
        p.id_pro, p.nombre, p.prefijo, p.cat, p.descript, p.img, p.tcomida,
        GROUP_CONCAT(pr.tipo_prod SEPARATOR ', ') AS tipos_producto,
        GROUP_CONCAT(pr.precio SEPARATOR ', ') AS precios
    FROM productos p 
    LEFT JOIN precios pr ON p.id_pro = pr.idproduc
";

if ($busqueda) {
    $query .= " WHERE p.nombre LIKE :busqueda"; 
}

$query .= " GROUP BY p.id_pro ORDER BY p.cat ASC LIMIT :limit OFFSET :offset"; 

$stmt = $conn->prepare($query);
if ($busqueda) {
    $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $productsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    
    <div class="row align-items-center mb-3">
        <div class="col-sm-12 col-md-3">
            <h3>Productos</h3>
        </div>
        <div class="col-sm-12 col-md-5">
            <form method="GET" action="index.php" class="d-flex gap-2">
                <input type="hidden" name="page" value="productos.php">
                <input type="text" name="busqueda" class="form-control" 
                       placeholder="Buscar producto..." 
                       value="<?php echo htmlspecialchars($busqueda); ?>">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </form>
        </div>
        <div class="col-sm-12 col-md-4 text-end">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#agregarProductoModal">
                + Agregar Producto
            </button>
        </div>
    </div>

    <table class="table table-bordered" id="tabla_productos">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Prefijo</th>
                <th>Precio</th>
                <th>Tipo</th>
                <th>Cat</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($productos) > 0): ?>
                <?php foreach ($productos as $producto): ?>
                <tr>
                    <td><?php echo (int)$producto['id_pro']; ?></td>
                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($producto['prefijo']); ?></td>
                    <td><?php echo htmlspecialchars($producto['precios'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($producto['tipos_producto'] ?? 'N/A'); ?></td>
                    <td><?php echo (int)$producto['cat']; ?></td>
                    <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?php echo htmlspecialchars($producto['descript']); ?>
                    </td>
                    <td>
                        <button class="btn btn-warning btn-sm edit-product-btn" 
                                data-id="<?php echo (int)$producto['id_pro']; ?>"
                                data-bs-toggle="modal" data-bs-target="#editarProductoModal"
                                title="Editar">
                            ✏️
                        </button>
                        <form class="form-eliminar-producto d-inline" method="post">
                            <input type="hidden" name="id_pro" value="<?php echo (int)$producto['id_pro']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center text-muted">No se encontraron productos.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination">
            <?php 
            $baseUrl = 'index.php?page=productos.php';
            if (!empty($busqueda)) {
                $baseUrl .= '&busqueda=' . urlencode($busqueda);
            }
            $startPage = max(1, $page - 5);
            $endPage = min($totalPages, $page + 4);
            ?>

            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo $baseUrl; ?>&pagina=<?php echo $page - 1; ?>">Anterior</a>
                </li>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo $baseUrl; ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo $baseUrl; ?>&pagina=<?php echo $page + 1; ?>">Siguiente</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════ -->
<!-- Modal Agregar Producto (BS5) -->
<!-- ═══════════════════════════════════════════ -->
<div class="modal fade" id="agregarProductoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="agregar-producto-form" method="post" enctype="multipart/form-data">
                    <div class="mb-2">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Prefijo</label>
                        <input type="text" class="form-control" name="prefijo" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label">Categoría</label>
                            <input type="number" class="form-control" name="cat" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo comida</label>
                            <input type="number" class="form-control" name="tcomida">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descript" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Imagen</label>
                        <input type="file" class="form-control" name="img">
                    </div>

                    <hr>
                    <label class="form-label">Variantes (tipo + precio)</label>
                    <div id="variantes-container">
                        <div class="variante-group row g-2 mb-2">
                            <div class="col-md-5">
                                <input type="text" class="form-control" name="tipo_producto[]" placeholder="Tipo" required>
                            </div>
                            <div class="col-md-5">
                                <input type="number" class="form-control" name="precio_producto[]" placeholder="Precio" required>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-variante" disabled>✕</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-variante-btn">+ Variante</button>

                    <button type="submit" class="btn btn-success w-100">Agregar Producto</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════ -->
<!-- Modal Editar Producto (BS5) -->
<!-- ═══════════════════════════════════════════ -->
<div class="modal fade" id="editarProductoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editar-producto-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" id="edit-id_pro" name="id_pro">
                    <div class="mb-2">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="edit-nombre" name="nombre" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Prefijo</label>
                        <input type="text" class="form-control" id="edit-prefijo" name="prefijo" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label">Categoría</label>
                            <input type="text" class="form-control" id="edit-cat" name="cat" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo comida</label>
                            <input type="number" class="form-control" id="edit-tcomida" name="tcomida">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" id="edit-descript" name="descript" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Imagen</label>
                        <input type="file" class="form-control" name="img">
                    </div>

                    <hr>
                    <label class="form-label">Variantes</label>
                    <div id="tipo-precio-container"></div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="agregar-tipo-precio">+ Variante</button>

                    <button type="submit" class="btn btn-primary w-100">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════ -->
<!-- Scripts -->
<!-- ═══════════════════════════════════════════ -->
<script>
// ─── Variantes: agregar/eliminar ────────────
function toggleEliminarBoton() {
    ['#variantes-container', '#tipo-precio-container'].forEach(bloqueId => {
        const groups = document.querySelectorAll(`${bloqueId} .variante-group`);
        groups.forEach(group => {
            const btn = group.querySelector('.btn-remove-variante');
            if (btn) btn.disabled = (groups.length <= 1);
        });
    });
}

document.getElementById('add-variante-btn')?.addEventListener('click', function() {
    const container = document.getElementById('variantes-container');
    const newRow = container.querySelector('.variante-group').cloneNode(true);
    newRow.querySelectorAll('input').forEach(inp => inp.value = '');
    container.appendChild(newRow);
    toggleEliminarBoton();
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-remove-variante')) {
        e.target.closest('.variante-group')?.remove();
        toggleEliminarBoton();
    }
});

toggleEliminarBoton();

// ─── Agregar producto ───────────────────────
document.getElementById('agregar-producto-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('../api.php?route=catalogo/agregar', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error al procesar la solicitud.');
    });
});

// ─── Cargar datos para editar ───────────────
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.edit-product-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');

            fetch('../api.php?route=catalogo/obtener&id=' + id)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    const p = data.producto;
                    document.getElementById('edit-id_pro').value   = p.id_pro;
                    document.getElementById('edit-nombre').value   = p.nombre;
                    document.getElementById('edit-prefijo').value  = p.prefijo;
                    document.getElementById('edit-cat').value      = p.cat;
                    document.getElementById('edit-descript').value = p.descript;
                    document.getElementById('edit-tcomida').value  = p.tcomida || '';

                    const container = document.getElementById('tipo-precio-container');
                    container.innerHTML = '';

                    (p.tipos_precios || []).forEach(tp => {
                        agregarTipoPrecio(tp.tipo_prod, tp.precio);
                    });

                    toggleEliminarBoton();
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(err => alert('Error: ' + err.message));
        });
    });

    function agregarTipoPrecio(tipo = '', precio = '') {
        const container = document.getElementById('tipo-precio-container');
        const div = document.createElement('div');
        div.className = 'variante-group row g-2 mb-2';
        div.innerHTML = `
            <div class="col-md-5">
                <input type="text" class="form-control" name="tipos[]" value="${tipo}" placeholder="Tipo">
            </div>
            <div class="col-md-5">
                <input type="number" class="form-control" name="precios[]" value="${precio}" placeholder="Precio">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-variante">✕</button>
            </div>
        `;
        container.appendChild(div);
    }

    // Botón agregar variante en edición
    document.getElementById('agregar-tipo-precio')?.addEventListener('click', function() {
        agregarTipoPrecio();
        toggleEliminarBoton();
    });

    // Guardar edición
    let isSubmitting = false;
    document.getElementById('editar-producto-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        if (isSubmitting) return;
        isSubmitting = true;

        const btn = this.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;

        fetch('../api.php?route=catalogo/editar', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(err => alert('Error: ' + err.message))
        .finally(() => {
            isSubmitting = false;
            if (btn) btn.disabled = false;
        });
    });
});

// ─── Eliminar producto ──────────────────────
document.querySelectorAll('.form-eliminar-producto').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!confirm('¿Seguro que deseas eliminar este producto?')) return;

        fetch('../api.php?route=catalogo/eliminar', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                alert('✅ ' + data.message);
                this.closest('tr')?.remove();
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(err => alert('Error: ' + err.message));
    });
});
</script>