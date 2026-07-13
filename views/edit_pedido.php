<?php
/**
 * edit_pedido.php - ARREGLADO PARA FK DE PRECIOS
 * Función mejorada para manejar tipos de producto correctamente
 */

try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    echo '<div class="alert alert-danger m-4">Error de conexión: ' . $e->getMessage() . '</div>';
    return;
}

$numero_pedido = isset($_POST['numero_pedido']) ? (int)$_POST['numero_pedido'] 
               : (isset($_GET['numero_pedido']) ? (int)$_GET['numero_pedido'] : null);

if (!$numero_pedido) {
    echo '<div class="alert alert-warning m-4">Número de pedido no proporcionado. <a href="index.php?page=llamadas.php">Volver</a></div>';
    return;
}

$stmtProductos = $db->query("SELECT id_pro, nombre, tcomida FROM productos ORDER BY nombre");
$productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

$stmtCab = $db->prepare("
    SELECT t.turno, t.estado, t.fecha, t.tipo_solicitud,
           COALESCE(c.cliente, '') AS cliente
    FROM turnero t
    LEFT JOIN clientes c ON t.id_cliente = c.id
    WHERE t.id_pedido = :np LIMIT 1
");
$stmtCab->execute([':np' => $numero_pedido]);
$cabecera = $stmtCab->fetch(PDO::FETCH_ASSOC);

$queryPedido = "SELECT p.id_pedido, p.id_pro, p.cantidad, p.detalle, p.tipo_producto,
                       pr.nombre AS nombre_producto, pr.tcomida,
                       COALESCE(prp.precio, 0) AS precio
                FROM pedidos p
                JOIN productos pr ON p.id_pro = pr.id_pro
                LEFT JOIN precios prp ON pr.id_pro = prp.idproduc AND prp.tipo_prod = p.tipo_producto
                WHERE p.numero_pedido = :numero_pedido
                ORDER BY p.id_pedido ASC";
$stmtPedido = $db->prepare($queryPedido);
$stmtPedido->execute([':numero_pedido' => $numero_pedido]);
$todosProductos = $stmtPedido->fetchAll(PDO::FETCH_ASSOC);

if (empty($todosProductos)) {
    echo '<div class="alert alert-warning m-4">No se encontraron productos en el pedido #' . $numero_pedido . '. <a href="index.php?page=llamadas.php">Volver</a></div>';
    return;
}

function obtenerTiposProductoEdit($db, $id_pro) {
    $stmt = $db->prepare("SELECT DISTINCT tipo_prod FROM precios WHERE idproduc = :id_pro ORDER BY tipo_prod");
    $stmt->execute([':id_pro' => $id_pro]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function obtenerDetallesPermitidosEdit($tcomida) {
    switch ((int)$tcomida) {
        case 1:  return ['amarillo', 'cafe'];
        case 2:  return ['papa', 'amarillo', 'cafe'];
        case 10: return ['Sindetalle'];
        default: return ['Sindetalle'];
    }
}

$items = [];       
$totalPedido = 0;
$countActivos = 0; 

foreach ($todosProductos as $p) {
    $cant = (int)$p['cantidad'];
    $esNegativo = ($cant < 0);
    $esAnuladoOriginal = (!$esNegativo && strpos($p['detalle'] ?? '', 'ANULADO:') === 0);
    
    $subtotal = (float)$p['precio'] * $cant;
    $totalPedido += $subtotal;

    if ($esNegativo) {
        $vinculado = false;
        for ($i = count($items) - 1; $i >= 0; $i--) {
            if ($items[$i]['id_pro'] == $p['id_pro'] && $items[$i]['es_anulado_original']) {
                $items[$i]['sub_anulado'] = $p;
                $items[$i]['sub_anulado']['subtotal'] = $subtotal;
                $vinculado = true;
                break;
            }
        }
        if (!$vinculado) {
            $p['tipo'] = 'negativo_suelto';
            $p['subtotal'] = $subtotal;
            $items[] = $p;
        }
    } else {
        $p['tipo'] = $esAnuladoOriginal ? 'anulado_original' : 'activo';
        $p['es_anulado_original'] = $esAnuladoOriginal;
        $p['sub_anulado'] = null;
        $p['subtotal'] = $subtotal;
        if (!$esAnuladoOriginal) $countActivos++;
        $items[] = $p;
    }
}
$indexActivo = 0;
?>

<div class="container-fluid" style="max-width:960px;">

    <!-- Header -->
    <div class="edit-header">
        <h3>
            Editar Pedido <span class="pedido-num">#<?php echo $numero_pedido; ?></span>
        </h3>
        <div style="display:flex; gap:8px; align-items:center;">
            <?php if ($cabecera): ?>
                <span class="badge-estado badge-<?php echo $cabecera['estado']; ?>">
                    <?php echo ucfirst($cabecera['estado']); ?>
                </span>
            <?php endif; ?>
            <a href="index.php?page=llamadas.php" class="btn btn-outline-secondary btn-sm">&larr; Volver</a>
        </div>
    </div>

    <?php if ($cabecera): ?>
    <div style="display:flex; gap:24px; margin-bottom:20px; font-size:0.85rem; color:var(--slate-500); flex-wrap:wrap;">
        <span><strong>Turno:</strong> <?php echo $cabecera['turno']; ?></span>
        <span><strong>Cliente:</strong> <?php echo htmlspecialchars($cabecera['cliente'] ?: 'N/A'); ?></span>
        <span><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($cabecera['fecha'])); ?></span>
        <span id="total-display"><strong>Total:</strong> $<?php echo number_format($totalPedido, 0, ',', '.'); ?></span>
    </div>
    <?php endif; ?>

    <form id="form-editar-pedido" method="POST">
        <input type="hidden" name="numero_pedido" value="<?php echo $numero_pedido; ?>">

        <div id="productos-container">
            <?php foreach ($items as $item): ?>

                <?php if ($item['tipo'] === 'activo'): ?>
                <div class="producto-card" 
                     data-tcomida="<?php echo (int)$item['tcomida']; ?>" 
                     data-idpedido="<?php echo $item['id_pedido']; ?>">
                    
                    <span class="producto-index"><?php echo $indexActivo++; ?></span>

                    <input type="hidden" 
                           name="productos_existentes[<?php echo $item['id_pedido']; ?>][id_pedido]"
                           value="<?php echo $item['id_pedido']; ?>">

                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Producto</label>
                            <select name="productos_existentes[<?php echo $item['id_pedido']; ?>][id_pro]"
                                class="form-select producto-select" required
                                onchange="actualizarProductoEdit(this)">
                                <option value="">Seleccionar</option>
                                <?php foreach ($productos as $prod): ?>
                                    <option value="<?php echo $prod['id_pro']; ?>" 
                                        data-tcomida="<?php echo $prod['tcomida']; ?>"
                                        <?php echo ($prod['id_pro'] == $item['id_pro']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prod['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Cant</label>
                            <input type="number"
                                name="productos_existentes[<?php echo $item['id_pedido']; ?>][cantidad]"
                                class="form-control text-center" value="<?php echo $item['cantidad']; ?>" min="1" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tipo</label>
                            <select name="productos_existentes[<?php echo $item['id_pedido']; ?>][tipo_producto]"
                                class="form-select tipo-producto-select" required>
                                <?php
                                $tiposPermitidos = obtenerTiposProductoEdit($db, $item['id_pro']);
                                foreach ($tiposPermitidos as $tipo) {
                                    $sel = ($tipo == $item['tipo_producto']) ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($tipo) . "\" {$sel}>" . htmlspecialchars($tipo) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Detalle</label>
                            <select name="productos_existentes[<?php echo $item['id_pedido']; ?>][detalle]"
                                class="form-select detalle-select" required>
                                <?php
                                $detalles = obtenerDetallesPermitidosEdit($item['tcomida']);
                                $detalleActual = !empty($item['detalle']) ? $item['detalle'] : 'Sindetalle';
                                foreach ($detalles as $det) {
                                    $sel = ($det == $detalleActual) ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($det) . "\" {$sel}>" . htmlspecialchars($det) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Precio</label>
                            <div class="form-control-plaintext text-end" style="font-weight:600; font-size:0.85rem;">
                                $<?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm w-100"
                                data-id-pedido="<?php echo $item['id_pedido']; ?>"
                                onclick="anularProducto(this)"
                                <?php echo ($countActivos <= 1) ? 'disabled title="No puedes anular el único producto activo"' : ''; ?>>
                                ✕ Anular
                            </button>
                        </div>
                    </div>
                </div>

                <?php elseif ($item['tipo'] === 'anulado_original'): ?>
                <div class="producto-card" style="opacity:0.55; border-color:var(--coral); background:var(--slate-50); pointer-events:none;">
                    <span class="producto-index" style="background:var(--coral);">ANULADO</span>
                    <div class="row g-2 align-items-center" style="font-size:0.85rem;">
                        <div class="col-md-4">
                            <span style="text-decoration:line-through; color:var(--slate-500);">
                                <?php echo htmlspecialchars($item['nombre_producto']); ?>
                            </span>
                        </div>
                        <div class="col-md-1 text-center" style="text-decoration:line-through;">
                            <?php echo $item['cantidad']; ?>
                        </div>
                        <div class="col-md-2" style="color:var(--slate-400);">
                            <?php echo htmlspecialchars($item['tipo_producto']); ?>
                        </div>
                        <div class="col-md-2" style="color:var(--slate-400);">
                            <?php echo htmlspecialchars(str_replace('ANULADO: ', '', $item['detalle'])); ?>
                        </div>
                        <div class="col-md-1 text-end" style="text-decoration:line-through; color:var(--slate-400);">
                            $<?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                        </div>
                        <div class="col-md-2"></div>
                    </div>

                    <?php if ($item['sub_anulado']): ?>
                    <div style="margin-top:8px; padding:8px 12px; background:var(--coral-glow); border-radius:var(--radius-sm); border-left:3px solid var(--coral); display:flex; justify-content:space-between; align-items:center; font-size:0.82rem;">
                        <span style="color:var(--coral-dark); font-weight:600;">
                            ↳ Anulado: <?php echo $item['sub_anulado']['cantidad']; ?> × <?php echo htmlspecialchars($item['nombre_producto']); ?>
                        </span>
                        <span style="color:var(--coral-dark); font-weight:700;">
                            −$<?php echo number_format(abs($item['sub_anulado']['subtotal']), 0, ',', '.'); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php elseif ($item['tipo'] === 'negativo_suelto'): ?>
                <div class="producto-card" style="opacity:0.5; border-color:var(--coral); background:var(--coral-glow); pointer-events:none;">
                    <div style="padding:6px 12px; border-left:3px solid var(--coral); font-size:0.82rem; display:flex; justify-content:space-between;">
                        <span style="color:var(--coral-dark); font-weight:600;">
                            ↳ Anulado: <?php echo $item['cantidad']; ?> × <?php echo htmlspecialchars($item['nombre_producto']); ?>
                        </span>
                        <span style="color:var(--coral-dark); font-weight:700;">
                            −$<?php echo number_format(abs($item['subtotal']), 0, ',', '.'); ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>

            <?php endforeach; ?>
        </div>

        <div class="edit-actions">
            <button type="button" id="btn-agregar-producto" class="btn btn-info">+ Agregar Producto</button>
            <button type="submit" class="btn btn-success">Guardar Cambios</button>
            <div style="flex:1;"></div>
            <button type="button" class="btn btn-danger" onclick="eliminarPedidoEdit(<?php echo $numero_pedido; ?>)">Eliminar Pedido</button>
        </div>
    </form>
</div>

<div class="modal fade" id="modalEliminarPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eliminar Pedido #<?php echo $numero_pedido; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom:12px;">Esta acción es <strong>irreversible</strong>. Ingresa el código de seguridad:</p>
                <input type="password" id="codigoSeguridadEdit" class="form-control form-control-lg text-center" 
                       placeholder="••••" maxlength="10" autocomplete="off">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarEliminarPedidoBtn">Eliminar Pedido</button>
            </div>
        </div>
    </div>
</div>

<script>
const API_EDIT = '../api.php?route=';

function anularProducto(button) {
    const card = button.closest('.producto-card');
    const idPedido = card.dataset.idpedido;
    const nombre = card.querySelector('.producto-select')?.selectedOptions[0]?.text || 'producto';

    if (!confirm(`¿Anular "${nombre}" del pedido?\n\nSe creará un registro negativo para trazabilidad.`)) return;

    button.disabled = true;
    button.textContent = 'Anulando…';

    fetch(`${API_EDIT}edit/eliminar_producto`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_pedido: idPedido })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
            button.disabled = false;
            button.textContent = '✕ Anular';
        }
    })
    .catch(e => {
        console.error(e);
        alert('Error de conexión');
        button.disabled = false;
        button.textContent = '✕ Anular';
    });
}

function eliminarPedidoEdit(numero_pedido) {
    const modal = new bootstrap.Modal(document.getElementById('modalEliminarPedido'));
    modal.show();
    document.getElementById('modalEliminarPedido').addEventListener('shown.bs.modal', () => {
        document.getElementById('codigoSeguridadEdit').focus();
    }, { once: true });

    document.getElementById('confirmarEliminarPedidoBtn').onclick = function() {
        const codigo = document.getElementById('codigoSeguridadEdit').value;
        if (!codigo) { alert('El código de seguridad es obligatorio.'); return; }
        this.disabled = true;
        this.textContent = 'Eliminando…';

        fetch(`${API_EDIT}edit/eliminar_pedido`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ numero_pedido, codigo_seguridad: codigo })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Pedido eliminado correctamente.');
                window.location.href = 'index.php?page=llamadas.php';
            } else {
                alert('Error: ' + data.message);
                this.disabled = false;
                this.textContent = 'Eliminar Pedido';
            }
        })
        .catch(e => { console.error(e); alert('Error de conexión'); this.disabled = false; this.textContent = 'Eliminar Pedido'; });
    };
}

// 🔥 FUNCIÓN MEJORADA - Maneja tanto strings como objetos
function actualizarProductoEdit(selectElement) {
    const productoId = selectElement.value;
    const card = selectElement.closest('.producto-card');
    if (!productoId) return;
    card.dataset.tcomida = selectElement.options[selectElement.selectedIndex].dataset.tcomida || '10';

    console.log(`🔄 Cargando tipos para producto ${productoId}...`);

    fetch(`${API_EDIT}edit/tipos_producto&id_pro=${productoId}`)
        .then(r => r.json())
        .then(data => {
            console.log('📦 Respuesta del API:', data);
            
            if (data.status === 'success' && data.tipos && data.tipos.length > 0) {
                // 🔥 FIX: Manejar tanto strings como objetos
                const tiposHTML = data.tipos.map(t => {
                    let tipo = t;
                    let valor = t;
                    
                    // Si es un objeto, extraer el valor correcto
                    if (typeof t === 'object' && t !== null) {
                        tipo = t.tipo_prod || t.tipo || t.value || t[0] || '';
                        valor = tipo;
                    }
                    
                    console.log(`  ✅ Tipo procesado: "${tipo}" (type: ${typeof t})`);
                    
                    return `<option value="${valor}">${tipo}</option>`;
                }).join('');
                
                card.querySelector('.tipo-producto-select').innerHTML = tiposHTML;
                console.log(`✅ ${data.tipos.length} tipos cargados correctamente`);
            } else {
                console.error('❌ Error: No hay tipos disponibles');
                card.querySelector('.tipo-producto-select').innerHTML = 
                    '<option value="">Sin tipos disponibles</option>';
            }
            
            // Cargar detalles
            const ds = card.querySelector('.detalle-select');
            if (data.detalles && data.detalles.length > 0) {
                ds.innerHTML = data.detalles.map(d => `<option value="${d}">${d}</option>`).join('');
            } else {
                ds.innerHTML = '<option value="Sindetalle">Sindetalle</option>';
            }
        })
        .catch(e => {
            console.error('❌ Error cargando tipos:', e);
            card.querySelector('.tipo-producto-select').innerHTML = 
                '<option value="">Error cargando tipos</option>';
        });
}

document.getElementById('btn-agregar-producto').addEventListener('click', function() {
    const productosNuevos = document.querySelectorAll('[name^="productos_nuevos["]');
    
    if (productosNuevos.length > 0 && productosNuevos.length < 6) {
        const ultimoIdx = Math.max(
            ...Array.from(productosNuevos)
                .map(input => {
                    const match = input.name.match(/productos_nuevos\[(\d+)\]/);
                    return match ? parseInt(match[1]) : -1;
                })
        );
        
        const ultimoIdPro = document.querySelector(`[name="productos_nuevos[${ultimoIdx}][id_pro]"]`)?.value;
        
        if (!ultimoIdPro) {
            alert('⚠️ Completa el último producto antes de agregar otro');
            return;
        }
    }
    
    if (productosNuevos.length >= 5) {
        alert('⚠️ Máximo 5 productos nuevos por operación. Guarda primero.');
        return;
    }
    
    const idx = parseInt(document.querySelector('[name^="productos_nuevos["]')?.name.match(/\d+/) || 0) + productosNuevos.length + 1;
    const selectProductos = document.querySelector('.producto-select');
    const productosHTML = selectProductos ? selectProductos.innerHTML : '';
    
    const html = `
    <div class="producto-card" data-tcomida="10" style="border-color:var(--sky); border-style:dashed;">
        <span class="producto-index" style="background:var(--sky);">NUEVO</span>
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Producto</label>
                <select name="productos_nuevos[${idx}][id_pro]" class="form-select producto-select" required
                    onchange="actualizarProductoEdit(this)">
                    <option value="">-- Seleccionar --</option>
                    ${productosHTML}
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">Cant</label>
                <input type="number" name="productos_nuevos[${idx}][cantidad]" class="form-control text-center" value="1" min="1" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Tipo</label>
                <select name="productos_nuevos[${idx}][tipo_producto]" class="form-select tipo-producto-select" required>
                    <option value="">-- Seleccionar --</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Detalle</label>
                <select name="productos_nuevos[${idx}][detalle]" class="form-select detalle-select" required>
                    <option value="Sindetalle">Sindetalle</option>
                </select>
            </div>
            <div class="col-md-1"></div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-secondary btn-sm w-100" 
                        onclick="this.closest('.producto-card').remove()" title="Quitar este producto">✕ Quitar</button>
            </div>
        </div>
    </div>`;
    
    document.getElementById('productos-container').insertAdjacentHTML('beforeend', html);
    const newCard = document.getElementById('productos-container').lastElementChild;
    newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
    newCard.querySelector('.producto-select').focus();
    console.log(`✅ Nuevo producto agregado (índice: ${idx})`);
});

document.getElementById('form-editar-pedido').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const btn = this.querySelector('[type="submit"]');
    const form = this;
    const productosNuevos = [];
    const nuevosInputs = form.querySelectorAll('[name^="productos_nuevos["]');
    
    if (nuevosInputs.length > 0) {
        console.log('📦 Validando productos nuevos...');
        
        const indices = new Set();
        nuevosInputs.forEach(input => {
            const match = input.name.match(/productos_nuevos\[(\d+)\]/);
            if (match) indices.add(match[1]);
        });
        
        let tieneErrores = false;
        indices.forEach(idx => {
            const idPro = form.querySelector(`[name="productos_nuevos[${idx}][id_pro]"]`)?.value;
            const cantidad = form.querySelector(`[name="productos_nuevos[${idx}][cantidad]"]`)?.value;
            const tipo = form.querySelector(`[name="productos_nuevos[${idx}][tipo_producto]"]`)?.value;
            
            console.log(`  [Producto ${idx}] id_pro=${idPro}, cantidad=${cantidad}, tipo=${tipo}`);
            
            // 🔥 VALIDAR que tipo no sea "object" o inválido
            if (typeof tipo !== 'string' || tipo.includes('object') || tipo === '') {
                alert(`❌ Producto ${idx}: El tipo no se cargó correctamente.\nIntenta seleccionar otro tipo.`);
                tieneErrores = true;
                return;
            }
            
            if (!idPro) {
                alert(`❌ Producto ${idx}: Debes seleccionar un producto`);
                tieneErrores = true;
                return;
            }
            if (!cantidad || cantidad < 1) {
                alert(`❌ Producto ${idx}: La cantidad debe ser >= 1`);
                tieneErrores = true;
                return;
            }
            if (!tipo) {
                alert(`❌ Producto ${idx}: Debes seleccionar un tipo`);
                tieneErrores = true;
                return;
            }
            
            productosNuevos.push({ idx, idPro, cantidad, tipo });
        });
        
        if (tieneErrores) return;
        if (productosNuevos.length > 0) {
            console.log(`✅ ${productosNuevos.length} producto(s) nuevo(s) validado(s)`);
        }
    }
    
    btn.disabled = true;
    btn.textContent = 'Guardando…';
    const formData = new FormData(form);
    
    console.log('📤 Enviando datos al servidor...');

    fetch(`../api.php?route=edit/guardar`, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        console.log('📦 Respuesta del servidor COMPLETA:', JSON.stringify(data, null, 2));
        
        if (data.success) {
            console.log('✅ Pedido guardado exitosamente');
            alert('✅ Pedido actualizado correctamente.');
            window.location.href = 'index.php?page=llamadas.php';
        } else {
            let mensajeError = data.message || 'Error desconocido';
            
            if (data.errores && data.errores.length > 0) {
                console.error('🔴 ERRORES DEL SERVIDOR:');
                mensajeError += '\n\n🔴 ERRORES ESPECÍFICOS:';
                data.errores.forEach((err, i) => {
                    console.error(`  [${i}]`, err);
                    mensajeError += `\n${i + 1}. ${err}`;
                });
            }
            
            alert('❌ Error:\n' + mensajeError);
            btn.disabled = false;
            btn.textContent = 'Guardar Cambios';
        }
    })
    .catch(e => {
        console.error('❌ Error de conexión:', e);
        alert('❌ Error de conexión con el servidor');
        btn.disabled = false;
        btn.textContent = 'Guardar Cambios';
    });
});

console.log('✅ edit_pedido.php cargado - VERSIÓN ARREGLADA CON FK FIX');
</script>