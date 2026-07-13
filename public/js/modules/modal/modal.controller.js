/**
 * modal.controller.js
 * Controlador del modal de pedidos
 * 
 * UBICACIÓN: public/js/modules/modal/modal.controller.js
 */

import { ApiService } from '../../services/api.service.js';
import { DashboardConfig } from '../../config/dashboard.config.js';
import { Formatters } from '../../utils/formatters.js';

export class ModalController {
    constructor() {
        this.modalElement = null;
        this.modal = null;
    }

    /**
     * Inicializar modal
     */
    init() {
        this.modalElement = document.getElementById('myModal');
        
        if (this.modalElement && typeof bootstrap !== 'undefined') {
            this.modal = new bootstrap.Modal(this.modalElement);
            console.log('✅ ModalController inicializado');
        }
    }

    /**
     * Mostrar modal con datos de pedido
     */
    async mostrar(numeroPedido, tipo = 'mesa') {
        if (!this.modal) {
            console.error('Modal no inicializado');
            return;
        }

        try {
            // Cargar datos
            const data = await this.cargarDatos(numeroPedido, tipo);

            // Renderizar contenido
            this.renderContenido(data, tipo);

            // Mostrar modal
            this.modal.show();

        } catch (error) {
            console.error('Error mostrando modal:', error);
            alert('Error al cargar los datos del pedido');
        }
    }

    /**
     * Cargar datos del pedido
     */
    async cargarDatos(numeroPedido, tipo) {
        const url = tipo === 'turno' 
            ? DashboardConfig.api.productos
            : DashboardConfig.api.mesas;

        const params = tipo === 'turno'
            ? { id_pedido: numeroPedido }
            : { numero_pedido: numeroPedido };

        return await ApiService.get(url, params);
    }

    /**
     * Renderizar contenido del modal
     */
    renderContenido(data, tipo) {
        const modalContent = document.getElementById('modal-content');
        if (!modalContent) return;

        if (tipo === 'mesa') {
            this.renderMesa(modalContent, data);
        } else {
            this.renderTurno(modalContent, data);
        }
    }

    /**
     * Renderizar modal de mesa
     */
    renderMesa(container, data) {
    const { 
        numero_mesa, nombre_mesero, nombre_cliente, telefono, 
        estado, fecha, productos, pagado, numero_pedido, mesas_libres 
    } = data;

    let total = 0;
    const productosHTML = productos && productos.length > 0
        ? productos.map(p => {
            const subtotal = (parseFloat(p.precio) || 0) * (parseInt(p.cantidad) || 0);
            total += subtotal;

            return `
                <tr>
                    <td>${p.nombre || ''}</td>
                    <td>${p.cantidad}</td>
                    <td>${p.detalle || ''}</td>
                    <td>${p.tipo_producto || p.tipo_prod || ''}</td>
                    <td>$${parseFloat(p.precio || 0).toLocaleString('es-CO')}</td>
                    <td>$${subtotal.toLocaleString('es-CO')}</td>
                </tr>
            `;
        }).join('')
        : '<tr><td colspan="6" class="text-center">Sin productos</td></tr>';

    // ═══════════════════════════════════════════════════════
    // BOTONES DE ESTADO (Cocina/Entregar)
    // ═══════════════════════════════════════════════════════
    let botonesEstado = '';
    if (estado === 'preparacion') {
        botonesEstado = `
            <button class="btn btn-primary mb-2 me-2" 
                    onclick="cambiarEstadoMesa(${numero_pedido},'espera')">
                Mandar a Cocina
            </button>`;
    } else if (estado === 'espera') {
        botonesEstado = `
            <button class="btn btn-warning mb-2 me-2" 
                    onclick="cambiarEstadoMesa(${numero_pedido},'entregado')">
                Entregar
            </button>`;
    }

    // ═══════════════════════════════════════════════════════
    // MESAS LIBRES (para cambiar de mesa)
    // ═══════════════════════════════════════════════════════
    let mesasOpts = '<option value="" disabled selected>Seleccionar mesa</option>';
    if (mesas_libres && mesas_libres.length > 0) {
        mesasOpts += mesas_libres.map(m => 
            `<option value="${m.numero_mesa}">Mesa ${m.numero_mesa}</option>`
        ).join('');
    }

    // ═══════════════════════════════════════════════════════
    // HTML DEL MODAL CON TODOS LOS BOTONES
    // ═══════════════════════════════════════════════════════
    container.innerHTML = `
        <p><strong>Mesa:</strong> ${numero_mesa || 'No asignada'}</p>
        <p><strong>Mesero:</strong> ${nombre_mesero || 'No asignado'}</p>
        <p><strong>Cliente:</strong> ${nombre_cliente || 'N/A'} ${telefono ? '| Tel: ' + telefono : ''}</p>
        <p><strong>Estado:</strong> ${this.mapEstadoLabel(estado)} | <strong>Fecha:</strong> ${fecha ? new Date(fecha).toLocaleString('es-CO') : 'N/D'}</p>
        <hr>
        <div class="mb-3">
            <label for="nueva_mesa" class="form-label"><strong>Cambiar Mesa:</strong></label>
            <div class="input-group">
                <select id="nueva_mesa" class="form-select">${mesasOpts}</select>
                <button class="btn btn-outline-primary" type="button" onclick="cambiarMesa(${numero_pedido},'${numero_mesa}')">Cambiar</button>
            </div>
        </div>
        <h6>Productos del pedido:</h6>
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cant</th>
                    <th>Detalle</th>
                    <th>Tipo</th>
                    <th>P.Unit</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                ${productosHTML}
            </tbody>
        </table>
        <p><strong>Total: $${total.toLocaleString('es-CO')}</strong></p>
        <hr>
        <div class="d-flex flex-wrap gap-2">
            ${botonesEstado}
            <button class="btn btn-success mb-2 me-2" onclick="printInvoicemesa(${numero_pedido})">Imprimir</button>
            <form action="../public/index.php?page=edit_pedido.php" method="POST" style="display:inline;">
                <input type="hidden" name="numero_pedido" value="${numero_pedido}">
                <button class="btn btn-warning mb-2 me-2">Editar</button>
            </form>
            <form action="../public/index.php?page=caja_tm.php" method="POST" style="display:inline;">
                <input type="hidden" name="numero_pedido" value="${numero_pedido}">
                <button class="btn btn-danger mb-2 me-2">Pagar</button>
            </form>
        </div>
    `;
}

    mapEstadoLabel(estado) {
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

    /**
     * Renderizar modal de turno
     */
    renderTurno(container, data) {
        const { cliente, productos, costo_domicilio, comentario } = data;

        let total = 0;
        const productosHTML = productos.map(p => {
            const subtotal = parseFloat(p.precio) * parseInt(p.cantidad);
            total += subtotal;

            return `
                <tr>
                    <td>${p.cantidad}</td>
                    <td>${p.tipo_prod || '—'}</td>
                    <td>${p.nombre_producto}</td>
                    <td>${p.detalle || ''}</td>
                    <td>${Formatters.currency(subtotal)}</td>
                </tr>
            `;
        }).join('');

        // Costo domicilio si existe
        let costoDomHTML = '';
        if (costo_domicilio) {
            const costoDom = parseFloat(costo_domicilio);
            total += costoDom;
            costoDomHTML = `
                <tr>
                    <td colspan="4"><strong>Costo Domicilio</strong></td>
                    <td><strong>${Formatters.currency(costoDom)}</strong></td>
                </tr>
            `;
        }

        container.innerHTML = `
            <p><strong>Cliente:</strong> ${cliente?.cliente || 'Sin cliente'}</p>
            
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Cant.</th>
                        <th>Tipo</th>
                        <th>Producto</th>
                        <th>Detalle</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${productosHTML}
                    ${costoDomHTML}
                    <tr>
                        <td colspan="4"><strong>TOTAL</strong></td>
                        <td><strong>${Formatters.currency(total)}</strong></td>
                    </tr>
                </tbody>
            </table>
            
            <p><strong>Comentario:</strong> ${comentario || 'Sin comentarios'}</p>
        `;
    }

    /**
     * Ocultar modal
     */
    ocultar() {
        if (this.modal) {
            this.modal.hide();
        }
    }
}

// Instancia singleton
const modalInstance = new ModalController();

// Hacer disponible globalmente
if (typeof window !== 'undefined') {
    window.modalController = modalInstance;
    
    // Funciones de compatibilidad
    window.mostrarModal = (numeroPedido, estado, pagado) => {
        modalInstance.mostrar(numeroPedido, 'mesa');
    };
    
    window.mostrarModalTurno = (numeroPedido) => {
        modalInstance.mostrar(numeroPedido, 'turno');
    };
}

export default modalInstance;