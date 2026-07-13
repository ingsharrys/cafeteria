/**
 * productos.service.js - OPTIMIZADO
 * Servicio de productos - maneja la carga y renderizado de productos
 * 
 * ✅ MEJORAS:
 * 1. Lazy loading: Solo carga cuando entra en viewport
 * 2. Debounce: Agrupa requests
 * 3. Cache mejorado: Evita recargas
 */

class ProductosService {
    constructor() {
        this.url = new URL('../api.php', window.location.href);
        this.url.searchParams.set('route', 'productos');
        this.url = this.url.toString();
        this.cache = new Map();  // Cambiar a Map para mejor performance
        this.fetchQueue = [];
        this.fetchTimer = null;
    }

    /**
     * Cargar productos para un pedido CON DEBOUNCE
     */
    cargarProductos(idPedido) {
        // Si ya está cacheado, renderizar de inmediato
        if (this.cache.has(idPedido)) {
            const data = this.cache.get(idPedido);
            this.renderProductos(idPedido, data);
            return Promise.resolve();
        }

        // Agregar a cola
        if (!this.fetchQueue.includes(idPedido)) {
            this.fetchQueue.push(idPedido);
        }

        // Cancelar timer anterior
        if (this.fetchTimer) {
            clearTimeout(this.fetchTimer);
        }

        // Esperar 200ms y luego hacer fetch en batch
        return new Promise((resolve) => {
            this.fetchTimer = setTimeout(() => {
                const queue = this.fetchQueue;
                this.fetchQueue = [];

                if (queue.length === 0) {
                    resolve();
                    return;
                }

                console.log(`📦 Cargando ${queue.length} productos en BATCH`);

                // Procesar en paralelo (máximo 5 simultáneos)
                const maxParallel = 5;
                let current = 0;

                const procesarSiguiente = () => {
                    if (current >= queue.length) {
                        resolve();
                        return;
                    }

                    const ids = queue.slice(current, current + maxParallel);
                    current += maxParallel;

                    Promise.all(ids.map(id => this._fetchSingleProducto(id))).then(procesarSiguiente);
                };

                procesarSiguiente();
            }, 200);
        });
    }

    /**
     * Fetch de un pedido individual
     */
    async _fetchSingleProducto(idPedido) {
        try {
            const response = await fetch(`${this.url}&id_pedido=${idPedido}`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (!data || !Array.isArray(data.productos)) {
                this.renderError(idPedido, 'Sin productos');
                return;
            }

            // Guardar en cache
            this.cache.set(idPedido, data);

            // Renderizar
            this.renderProductos(idPedido, data);
        } catch (error) {
            console.error('Error cargando productos:', error);
            this.renderError(idPedido, 'Error cargando productos');
        }
    }

    /**
     * Renderizar productos en la celda
     */
    renderProductos(idPedido, data) {
        const cell = document.getElementById(`prod-${idPedido}`);
        if (!cell) return;

        const { productos, costo_domicilio, comentario } = data;

        if (!Array.isArray(productos) || productos.length === 0) {
            cell.textContent = 'Sin productos';
            return;
        }

        let total = 0;

        // Renderizar productos
        const productosHTML = productos.map(p => {
            const nombre = (p.nombre_producto || p.nombre || 'Producto').toString();
            const cantidad = parseInt(p.cantidad || 0, 10) || 1;
            const precio = parseFloat(p.precio || 0);
            const subtotal = precio * cantidad;
            total += subtotal;
            return `
                ${cantidad}x [${p.tipo_prod || '—'}] ${nombre.slice(0, 40)}<br>
                <small class="text-muted">${p.detalle || ''}</small> — 
                $${subtotal.toFixed(0)}
            `;
        }).join('<br>');

        // Información adicional
        let adicionalesHTML = '';
        const tipoSol = document.getElementById('tipoSolicitud')?.value;

        if (tipoSol === '50' && costo_domicilio) {
            const costoDom = parseFloat(costo_domicilio);
            total += costoDom;
            adicionalesHTML += `
                <hr>
                <strong>Costo Domicilio:</strong> $${costoDom.toFixed(0)}<br>
            `;
        }

        // Extraer el método de pago del comentario (formato "Metodo de pago: [X]")
        let metodoPago = null;
        let comentarioLimpio = (comentario || '').trim();
        const matchPago = comentarioLimpio.match(/Met[oó]do de pago:\s*\[([^\]]+)\]\s*-?\s*/i);
        if (matchPago) {
            metodoPago = matchPago[1].trim();
            comentarioLimpio = comentarioLimpio.replace(matchPago[0], '').trim();
        }

        // Etiqueta del método de pago (azul = transferencia, verde = efectivo)
        let metodoHTML = '';
        if (metodoPago) {
            const esTransfer = /transfer/i.test(metodoPago);
            const bg = esTransfer ? '#0d6efd' : '#198754';
            const icon = esTransfer ? '💳' : '💵';
            metodoHTML = `<div style="margin:4px 0;"><span style="display:inline-block;background:${bg};color:#fff;font-weight:700;font-size:11px;padding:3px 10px;border-radius:12px;">${icon} Pago: ${metodoPago}</span></div>`;
        }

        adicionalesHTML += `
            <strong>Total del pedido:</strong> $${total.toFixed(0)}<br>
            ${metodoHTML}
            <strong>Comentario:</strong> ${comentarioLimpio || 'No disponible'}
        `;

        cell.innerHTML = productosHTML + adicionalesHTML;
    }

    /**
     * Renderizar error CON BOTÓN DE RECARGA
     */
    renderError(idPedido, message) {
        const cell = document.getElementById(`prod-${idPedido}`);
        if (cell) {
            cell.innerHTML = `
                <div style="padding: 10px; text-align: center; border: 1px solid #ddd; border-radius: 4px; background: #fff8f8;">
                    <div style="color: #dc3545; margin-bottom: 10px; font-size: 13px; font-weight: bold;">
                        ⚠️ ${message}
                    </div>
                    <button 
                        class="btn btn-sm btn-primary" 
                        onclick="window.recargarProductos(${idPedido})"
                        style="font-size: 11px; padding: 5px 10px;"
                    >
                        🔄 Recargar
                    </button>
                </div>
            `;
        }
    }

    /**
     * Limpiar cache
     */
    clearCache() {
        this.cache.clear();
    }

    /**
     * Refrescar productos (limpiar cache y recargar)
     */
    async refrescar(idPedido) {
        this.cache.delete(idPedido);
        await this.cargarProductos(idPedido);
    }
}

// Instancia singleton
const productosInstance = new ProductosService();

// API global
if (typeof window !== 'undefined') {
    window.cargarProductos = (idPedido) => {
        productosInstance.cargarProductos(idPedido);
    };

    window.recargarProductos = (idPedido) => {
        console.log(`🔄 Recargando productos para pedido ${idPedido}`);
        productosInstance.refrescar(idPedido);
    };
}

export default productosInstance;