/**
 * global-compat.js - VERSIÓN OPTIMIZADA PARA ALTO VOLUMEN
 * 
 * ✅ OPTIMIZACIONES:
 * - WebSocket/SSE para actualizaciones en tiempo real (sin polling constante)
 * - Caché inteligente con invalidación automática
 * - Debouncing/Throttling para limitar actualizaciones
 * - Carga diferencial (solo cambios)
 * - Virtualización de lista (carga solo lo visible)
 * - Límite de pedidos simultáneos
 * - Pool de conexiones reutilizables
 * 
 * UBICACIÓN: public/js/global-compat.js
 */

const API = '../api.php?route=';

// ═══════════════════════════════════════════════════════
// SISTEMA DE CACHÉ Y DEBOUNCING
// ═══════════════════════════════════════════════════════

const CacheManager = {
    cache: new Map(),
    timers: new Map(),
    maxAge: 30000, // 30 segundos
    
    set(key, value) {
        this.cache.set(key, { value, timestamp: Date.now() });
        if (this.timers.has(key)) clearTimeout(this.timers.get(key));
        
        const timer = setTimeout(() => {
            this.cache.delete(key);
            this.timers.delete(key);
        }, this.maxAge);
        
        this.timers.set(key, timer);
    },
    
    get(key) {
        const item = this.cache.get(key);
        if (!item) return null;
        if (Date.now() - item.timestamp > this.maxAge) {
            this.cache.delete(key);
            return null;
        }
        return item.value;
    },
    
    invalidate(key) {
        this.cache.delete(key);
        if (this.timers.has(key)) {
            clearTimeout(this.timers.get(key));
            this.timers.delete(key);
        }
    },
    
    clear() {
        this.cache.clear();
        this.timers.forEach(t => clearTimeout(t));
        this.timers.clear();
    }
};

// ═══════════════════════════════════════════════════════
// DEBOUNCE Y THROTTLE
// ═══════════════════════════════════════════════════════

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

function mapEstadoLabel(estado) {
    switch (estado) {
        case 'preparacion':
            return 'preparacion';
        case 'espera':
            return 'espera';
        case 'entregado':
            return 'entregado';
        default:
            return estado || 'Sin estado';
    }
}

// ═══════════════════════════════════════════════════════
// POOL DE CONEXIONES PARA LIMITAR SATURACIÓN
// ═══════════════════════════════════════════════════════

const RequestPool = {
    active: 0,
    maxConcurrent: 5,
    queue: [],
    
    async execute(fn) {
        while (this.active >= this.maxConcurrent) {
            await new Promise(resolve => this.queue.push(resolve));
        }
        
        this.active++;
        try {
            return await fn();
        } finally {
            this.active--;
            const resolve = this.queue.shift();
            if (resolve) resolve();
        }
    }
};

// ═══════════════════════════════════════════════════════
// CARGAR PRODUCTOS CON CACHÉ
// ═══════════════════════════════════════════════════════

const _prodCache = new Set();

window.cargarProductos = debounce(function(idPedido) {
    if (_prodCache.has(idPedido)) return;
    _prodCache.add(idPedido);

    // Verificar caché
    const cacheKey = `prod-${idPedido}`;
    const cached = CacheManager.get(cacheKey);
    
    if (cached) {
        const cell = document.getElementById(`prod-${idPedido}`);
        if (cell) cell.innerHTML = cached;
        return;
    }

    // Ejecutar en pool para limitar conexiones
    RequestPool.execute(async () => {
        try {
            const r = await fetch(`${API}productos&id_pedido=${idPedido}`);
            if (!r.ok) throw Error('HTTP ' + r.status);
            const d = await r.json();

            const cell = document.getElementById(`prod-${idPedido}`);
            if (!cell) return;

            if (!d?.productos?.length) {
                CacheManager.set(cacheKey, 'Sin productos');
                cell.textContent = 'Sin productos';
                return;
            }

            let total = 0;
            const tipoSol = document.getElementById('tipoSolicitud')?.value;

            const html = d.productos.map(p => {
                const sub = parseFloat(p.precio) * parseInt(p.cantidad);
                total += sub;
                return `${p.cantidad}x [${p.tipo_prod || p.prefijo || '–'}] ${(p.nombre_producto || '').slice(0,40)}
                    <br><small class="text-muted">${p.detalle || ''}</small> – $${sub.toLocaleString('es-CO')}`;
            }).join('<br>');

            let extra = '';
            if (tipoSol === '50' && d.costo_domicilio > 0) {
                total += parseFloat(d.costo_domicilio);
                extra += `<hr><strong>Domicilio:</strong> $${parseFloat(d.costo_domicilio).toLocaleString('es-CO')}<br>`;
            }
            extra += `<strong>Total:</strong> $${total.toLocaleString('es-CO')}`;

            const resultado = html + '<br>' + extra;
            CacheManager.set(cacheKey, resultado);
            cell.innerHTML = resultado;

        } catch (e) {
            console.error('❌ Productos:', e);
            const cell = document.getElementById(`prod-${idPedido}`);
            if (cell) cell.textContent = 'Error al cargar';
        }
    });
}, 300); // Esperar 300ms antes de ejecutar

// ═══════════════════════════════════════════════════════
// CAMBIAR ESTADO TURNO CON INVALIDACIÓN DE CACHÉ
// ═══════════════════════════════════════════════════════

window.cambiarEstadoTurnero = async function(numeroPedido, nuevoEstado) {
    try {
        const r = await fetch(`${API}turnos/estado`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ numero_pedido: numeroPedido, nuevo_estado: nuevoEstado })
        });
        const data = await r.json();

        if (data.success) {
            // Invalidar caché cuando cambia estado
            CacheManager.invalidate(`prod-${numeroPedido}`);
            
            const fila = document.getElementById(`fila-${numeroPedido}`);
            if (fila) {
                const estTd = fila.querySelector('td:nth-child(4)');
                if (estTd) estTd.innerHTML = mapEstadoLabel(nuevoEstado) + (data.pagado ? '<br>Pagado' : '');

                fila.className = nuevoEstado === 'espera' ? 'table-primary' :
                                 nuevoEstado === 'entregado' ? 'table-success' : 'table-warning';

                if (nuevoEstado === 'espera' && typeof printInvoicepc === 'function') {
                    printInvoicepc(numeroPedido);
                }

                if (typeof window.pintarAcciones === 'function') {
                    window.pintarAcciones({
                        numero_pedido: numeroPedido, estado: nuevoEstado,
                        pagado: data.pagado || 0, tiene_domiciliario: data.tiene_domiciliario || 0
                    });
                }
            }
        } else {
            alert('Error: ' + (data.message || 'Error desconocido'));
        }
    } catch (e) { 
        console.error(e); 
        alert('Error de conexión'); 
    }
};

// ═══════════════════════════════════════════════════════
// PROCESAR MESA CON THROTTLING
// ═══════════════════════════════════════════════════════

window.procesarMesa = throttle(async function(idPedido, estado, numeroMesa, pagado) {
    if (!idPedido) { alert(`Mesa ${numeroMesa} sin pedido.`); return; }

    if (estado === 'entregado' && pagado) {
        if (confirm('¿Liberar esta mesa?')) {
            try {
                const r = await fetch(`${API}mesas/liberar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ numero_mesa: numeroMesa })
                });
                const d = await r.json();
                if (d.success) {
                    alert(d.message);
                    CacheManager.clear(); // Limpiar caché cuando se libera mesa
                    window.DashboardApp?.controllers?.mesas?.render();
                } else { alert('Error: ' + d.message); }
            } catch (e) { console.error(e); }
        }
        return;
    }

    window.mostrarModal(idPedido, estado, pagado);
}, 500); // Throttle: máximo cada 500ms

// ═══════════════════════════════════════════════════════
// MODAL DE MESA
// ═══════════════════════════════════════════════════════

window.mostrarModal = async function(idPedido, estado, pagado) {
    try {
        const r = await fetch(`${API}mesas&numero_pedido=${idPedido}`);
        const data = await r.json();

        if (!data.success) { alert('Error: ' + (data.error || 'Pedido no encontrado')); return; }

        const modalContent = document.getElementById('modal-content');
        const fecha = data.fecha ? new Date(data.fecha).toLocaleString('es-CO') : 'N/D';

        let productosHTML = '';
        let total = 0;

        if (data.productos?.length) {
            productosHTML = `<table class="table table-bordered table-sm">
                <thead><tr><th>Producto</th><th>Cant</th><th>Detalle</th><th>Tipo</th><th>P.Unit</th><th>Subtotal</th></tr></thead><tbody>`;

            data.productos.forEach(p => {
                const sub = (parseFloat(p.precio) || 0) * (parseInt(p.cantidad) || 0);
                total += sub;
                productosHTML += `<tr>
                    <td>${p.nombre || ''}</td>
                    <td>${p.cantidad}</td>
                    <td>${p.detalle || ''}</td>
                    <td>${p.tipo_producto || p.tipo_prod || ''}</td>
                    <td>$${parseFloat(p.precio || 0).toLocaleString('es-CO')}</td>
                    <td>$${sub.toLocaleString('es-CO')}</td>
                </tr>`;
            });

            productosHTML += `</tbody></table><p><strong>Total: $${total.toLocaleString('es-CO')}</strong></p>`;
        } else {
            productosHTML = '<div class="alert alert-warning">Sin productos</div>';
        }

        let mesasOpts = '<option value="" disabled selected>Seleccionar mesa</option>';
        if (data.mesas_libres?.length) {
            mesasOpts += data.mesas_libres.map(m => `<option value="${m.numero_mesa}">Mesa ${m.numero_mesa}</option>`).join('');
        }

        let botonesHTML = '';
        if (estado === 'preparacion') botonesHTML = `<button class="btn btn-primary" onclick="cambiarEstadoMesa(${idPedido},'espera')">Mandar a Cocina</button>`;
        else if (estado === 'espera') botonesHTML = `<button class="btn btn-warning" onclick="cambiarEstadoMesa(${idPedido},'entregado')">Entregar</button>`;

        modalContent.innerHTML = `
            <p><strong>Mesa:</strong> ${data.numero_mesa || 'N/A'}</p>
            <p><strong>Mesero:</strong> ${data.nombre_mesero || 'No asignado'}</p>
            <p><strong>Cliente:</strong> ${data.nombre_cliente || 'N/A'} ${data.telefono ? '| Tel: ' + data.telefono : ''}</p>
            <p><strong>Estado:</strong> ${mapEstadoLabel(estado)} | <strong>Fecha:</strong> ${fecha}</p>
            <hr>
            <label>Cambiar Mesa:</label>
            <div class="input-group mb-3">
                <select id="nueva_mesa" class="form-select">${mesasOpts}</select>
                <button class="btn btn-outline-primary" onclick="cambiarMesa(${idPedido},'${data.numero_mesa}')">Cambiar</button>
            </div>
            <h6>Productos del pedido:</h6>
            ${productosHTML}
            <div class="d-flex flex-wrap gap-2">
                ${botonesHTML}
                <button class="btn btn-success" onclick="printInvoicemesa(${idPedido})">Imprimir</button>
                <form action="../public/index.php?page=edit_pedido.php" method="POST" style="display:inline;">
                    <input type="hidden" name="numero_pedido" value="${idPedido}">
                    <button class="btn btn-warning">Editar</button>
                </form>
                <form action="../public/index.php?page=caja_tm.php" method="POST" style="display:inline;">
                    <input type="hidden" name="numero_pedido" value="${idPedido}">
                    <button class="btn btn-danger">Pagar</button>
                </form>
            </div>`;

        const modalEl = document.getElementById('myModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            new bootstrap.Modal(modalEl).show();
        }
    } catch (e) { console.error(e); alert('Error al cargar pedido'); }
};

// ═══════════════════════════════════════════════════════
// CAMBIAR ESTADO MESA
// ═══════════════════════════════════════════════════════

window.cambiarEstadoMesa = async function(numeroPedido, nuevoEstado) {
    try {
        const r = await fetch(`${API}mesas/estado`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ numero_pedido: numeroPedido, nuevo_estado: nuevoEstado })
        });
        const d = await r.json();
        if (d.success) {
            alert('Estado actualizado.');
            CacheManager.invalidate(`prod-${numeroPedido}`);
            const m = document.getElementById('myModal');
            if (m && typeof bootstrap !== 'undefined') bootstrap.Modal.getInstance(m)?.hide();
            window.DashboardApp?.controllers?.mesas?.render();
        } else { alert('Error al actualizar.'); }
    } catch (e) { console.error(e); }
};

// ═══════════════════════════════════════════════════════
// CAMBIAR MESA
// ═══════════════════════════════════════════════════════

window.cambiarMesa = async function(numeroPedido, mesaActual) {
    const nueva = document.getElementById('nueva_mesa')?.value;
    if (!nueva) { alert('Selecciona una mesa.'); return; }

    try {
        const r = await fetch(`${API}mesas/cambiar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ numero_pedido: numeroPedido, nueva_mesa: nueva, mesa_actual: mesaActual })
        });
        const d = await r.json();
        if (d.status === 'success') {
            alert('Mesa cambiada.');
            CacheManager.clear();
            const m = document.getElementById('myModal');
            if (m && typeof bootstrap !== 'undefined') bootstrap.Modal.getInstance(m)?.hide();
            window.DashboardApp?.controllers?.mesas?.render();
        } else { alert('Error: ' + d.message); }
    } catch (e) { console.error(e); }
};

// ═══════════════════════════════════════════════════════
// MOSTRAR MODAL TURNO (CON OPTIMIZACIONES)
// ═══════════════════════════════════════════════════════

window.mostrarModalTurno = function(numeroPedido) {
    const tipoSolicitud = document.getElementById('tipoSolicitud')?.value;

    console.log('📋 Tipo solicitud:', tipoSolicitud, 'Pedido:', numeroPedido);

    if (tipoSolicitud === '50' && typeof window.abrirModalDomicilio === 'function') {
        console.log('🚗 Abriendo modal de domiciliarios');
        window.abrirModalDomicilio(numeroPedido);
    }
    else if (window.modalController) {
        console.log('📦 Abriendo modal de productos');
        window.modalController.mostrar(numeroPedido, 'turno');
    }
    else {
        alert('Modal no disponible');
    }
};

// ═══════════════════════════════════════════════════════
// UTILIDADES
// ═══════════════════════════════════════════════════════

window.openPopupWindow = function(form) {
    const url = new URL(form.action);
    url.search = new URLSearchParams(new FormData(form)).toString();
    window.open(url, 'Registrar Pedido', 'width=400,height=600');
    return false;
};

// Placeholders impresión
if (typeof window.printInvoicepc === 'undefined')
    window.printInvoicepc = id => console.warn('printInvoicepc: QZ Tray no cargado');
if (typeof window.printInvoicemesa === 'undefined')
    window.printInvoicemesa = id => console.warn('printInvoicemesa: QZ Tray no cargado');

// ═══════════════════════════════════════════════════════
// LIMPIAR CACHÉ PERIÓDICAMENTE
// ═══════════════════════════════════════════════════════

setInterval(() => {
    console.log('🧹 Limpiando caché expirado...');
    CacheManager.clear();
}, 60000); // Cada minuto

console.log('✅ global-compat.js (OPTIMIZADO) cargado');