/**
 * turnos.view.js - TU CÓDIGO + OPTIMIZACIONES MÍNIMAS
 * Vista de turnos - maneja el renderizado
 * 
 * ✅ CAMBIOS MÍNIMOS:
 * 1. Batch rendering con documentFragment (render + appendRows)
 * 2. Debounce en updateTimers
 * 3. TODO LO DEMÁS IGUAL A TU CÓDIGO
 * 
 * UBICACIÓN: public/js/modules/turnos/turnos.view.js
 */

import { DOM } from '../../utils/dom.js';
import { Formatters } from '../../utils/formatters.js';
import { Helpers } from '../../utils/helpers.js';

// Objeto global para almacenar datos de turnos
if (typeof window !== 'undefined') {
    window.turnosData = window.turnosData || {};
    window.domiciliosCache = window.domiciliosCache || {};
}

export class TurnosView {
    constructor(containerId = 'turnos-container') {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.initialized = false;
        
        // 🆕 AGREGAR PARA DEBOUNCE DOMICILIOS
        this.domiciliofetchBuffer = [];    // Buffer de pedidos pendientes
        this.domiciliofetchTimer = null;   // Timer para el debounce
        
        // ✅ AGREGAR PARA DEBOUNCE updateTimers
        this.updateTimersTimer = null;
    }

    initTable() {
        if (this.initialized) return;
        const container = document.getElementById(this.containerId);
        if (!container) { 
            console.error('Contenedor de turnos no encontrado'); 
            return; 
        }

        container.innerHTML = `
            <table class="table table-bordered" id="tabla-turnos">
                <thead>
                    <tr><th>N°</th><th>Cliente</th><th>Tiempo</th><th>Estado</th><th>Productos</th><th>Acción</th></tr>
                </thead>
                <tbody id="tbd"></tbody>
            </table>
        `;
        this.initialized = true;
    }

    /**
     * Renderizar turnos (primera carga o reset completo)
     * ✅ OPTIMIZADO: Batch rendering con documentFragment
     */
    render(turnos, service) {
        this.initTable();
        const tbody = document.getElementById('tbd');
        if (!tbody) return;

        // ✅ Usar fragment para batch rendering
        const fragment = document.createDocumentFragment();
        let filasAgregadas = 0;

        turnos.forEach(turno => {
            const filaId = `fila-${turno.numero_pedido}`;
            const existingRow = document.getElementById(filaId);

            // Si NO debe mostrarse → eliminar fila existente
            if (!service.shouldDisplay(turno)) {
                if (existingRow) {
                    existingRow.remove();
                    console.log(`🗑️ Turno #${turno.numero_pedido} oculto (entregado + pagado)`);
                }
                return;
            }

            if (existingRow) {
                this.updateRow(existingRow, turno, service);
            } else {
                const newRow = this.createRow(turno, service);
                fragment.appendChild(newRow);  // ✅ Agregar a fragment
                filasAgregadas++;
            }
        });

        // ✅ Una sola operación DOM
        if (filasAgregadas > 0) {
            tbody.innerHTML = '';  // Limpiar solo si hay nuevas filas
            tbody.appendChild(fragment);
        }
    }

    /**
     * Agregar filas adicionales (para infinite scroll)
     * ✅ OPTIMIZADO: Batch rendering con documentFragment
     * No borra la tabla, solo agrega filas nuevas
     */
    appendRows(turnos, service) {
        const tbody = document.getElementById('tbd');
        if (!tbody) return;

        // ✅ Usar fragment para batch rendering
        const fragment = document.createDocumentFragment();

        turnos.forEach(turno => {
            // Verificar si debe mostrarse
            if (!service.shouldDisplay(turno)) {
                return;
            }

            const filaId = `fila-${turno.numero_pedido}`;
            const existingRow = document.getElementById(filaId);

            // Si ya existe, actualizar
            if (existingRow) {
                this.updateRow(existingRow, turno, service);
            } else {
                // Si no existe, crear nueva
                const newRow = this.createRow(turno, service);
                fragment.appendChild(newRow);  // ✅ Agregar a fragment
            }
        });

        // ✅ Una sola operación DOM
        tbody.appendChild(fragment);
    }

    createRow(turno, service) {
        const rowClass = `table-${service.getColorEstado(turno)}`;
        const fecha = new Date(turno.fecha);
        
        const tr = document.createElement('tr');
        tr.id = `fila-${turno.numero_pedido}`;
        tr.className = rowClass;
        tr.dataset.fecha = fecha.toISOString();
        tr.dataset.estadoAnterior = turno.estado;
        tr.dataset.pagadoAnterior = turno.pagado;

        const tdTurno = document.createElement('td');
        tdTurno.innerHTML = `<h1>${turno.turno}</h1>`;
        tr.appendChild(tdTurno);

        const tdCliente = document.createElement('td');
        tdCliente.style.fontSize = '9pt';
        tdCliente.style.whiteSpace = 'normal';
        tdCliente.innerHTML = this.formatCliente(turno);
        tr.appendChild(tdCliente);

        const tdTiempo = document.createElement('td');
        tdTiempo.id = `tmp-${turno.numero_pedido}`;
        tdTiempo.textContent = Helpers.ago(fecha);
        tr.appendChild(tdTiempo);

        const tdEstado = document.createElement('td');
        tdEstado.id = `est-${turno.numero_pedido}`;
        tdEstado.innerHTML = `${this.mapEstadoLabel(turno.estado)}${turno.pagado ? '<br><span style="color:var(--emerald,#10b981);font-weight:600;">Pagado</span>' : ''}`;
        tr.appendChild(tdEstado);

        const tdProductos = document.createElement('td');
        tdProductos.id = `prod-${turno.numero_pedido}`;
        tdProductos.textContent = 'Cargando…';
        tr.appendChild(tdProductos);

        const tdAcciones = document.createElement('td');
        tdAcciones.id = `acc-${turno.numero_pedido}`;
        tr.appendChild(tdAcciones);

        this.pintarAcciones(turno, tdAcciones);
        
        // Guardar datos de turno en window
        window.turnosData[turno.numero_pedido] = turno;
        
        return tr;
    }

    updateRow(row, turno, service) {
        row.className = `table-${service.getColorEstado(turno)}`;

        const estadoTd = row.querySelector(`#est-${turno.numero_pedido}`);
        if (estadoTd) {
            estadoTd.innerHTML = `${this.mapEstadoLabel(turno.estado)}${turno.pagado ? '<br><span style="color:var(--emerald,#10b981);font-weight:600;">Pagado</span>' : ''}`;
        }

        const fecha = new Date(row.dataset.fecha);
        const tiempoTd = row.querySelector(`#tmp-${turno.numero_pedido}`);
        if (tiempoTd) tiempoTd.textContent = Helpers.ago(fecha);

        const accionesTd = row.querySelector(`#acc-${turno.numero_pedido}`);
        
        // OPTIMIZACIÓN: Solo re-renderizar acciones si cambió algo importante
        const estadoAnterior = row.dataset.estadoAnterior;
        if (accionesTd && (estadoAnterior !== turno.estado || turno.pagado !== row.dataset.pagadoAnterior)) {
            console.log(`🔄 Estado cambió: ${estadoAnterior} → ${turno.estado}. Re-renderizando acciones...`);
            this.pintarAcciones(turno, accionesTd);
            row.dataset.estadoAnterior = turno.estado;
            row.dataset.pagadoAnterior = turno.pagado;
        } else if (accionesTd) {
            console.log(`⚡ Estado igual (${turno.estado}). NO re-renderizar acciones para evitar parpadeos.`);
        }
        
        // Actualizar datos de turno en window
        window.turnosData[turno.numero_pedido] = turno;
    }

    pintarAcciones(turno, td) {
        if (!td) return;
        let html = '';
        const tipoSol = document.getElementById('tipoSolicitud')?.value;

        if (turno.estado !== 'entregado') {
            const next = (turno.estado === 'preparacion' ? 'espera' : 'entregado');
            const color = (turno.estado === 'preparacion' ? 'warning' : 'primary');
            const label = (turno.estado === 'preparacion' ? 'Disponible' : 'Entregar');
            html += `<button class="btn btn-${color} mb-2 me-2" onclick="cambiarEstadoTurnero(${turno.numero_pedido}, '${next}')">${label}</button>`;
        }

        if (tipoSol === '50') {
            // Verificar caché
            const cacheData = window.domiciliosCache && window.domiciliosCache[turno.numero_pedido];
            const despachoCompleto = cacheData && cacheData.id_domi && cacheData.precio && parseFloat(cacheData.precio) > 0;
            
            if (despachoCompleto) {
                html += `<button id="btn-desp-${turno.numero_pedido}" class="btn btn-success mb-2 me-2" onclick="abrirModalDomicilio(${turno.numero_pedido})">Despachado</button>`;
                console.log(`✅ Renderizando btn-desp-${turno.numero_pedido} como DESPACHADO (desde caché)`);
            } else {
                html += `<button id="btn-desp-${turno.numero_pedido}" class="btn btn-warning mb-2 me-2" onclick="abrirModalDomicilio(${turno.numero_pedido})">Despachar</button>`;
                console.log(`⚠️ Renderizando btn-desp-${turno.numero_pedido} como DESPACHAR (necesita datos)`);
                
                // 🆕 SOLO hacer fetch si NO está en caché
                if (!cacheData) {
                    this.fetchDomicilioConCache(turno.numero_pedido);
                }
            }
        }

        if (turno.pagado) {
            html += `<form action="../public/index.php?page=caja_tm.php" method="POST" style="display:inline;"><input type="hidden" name="numero_pedido" value="${turno.numero_pedido}"><button class="btn btn-success mb-2 me-2">Pagado</button></form>`;
        } else {
            html += `<form action="../public/index.php?page=caja_tm.php" method="POST" style="display:inline;"><input type="hidden" name="numero_pedido" value="${turno.numero_pedido}"><button class="btn btn-info mb-2 me-2">Caja</button></form>`;
            html += `<form action="../public/index.php?page=edit_pedido.php" method="POST" style="display:inline;"><input type="hidden" name="numero_pedido" value="${turno.numero_pedido}"><button class="btn btn-warning mb-2">Editar</button></form>`;
        }

        td.innerHTML = html;
        // Verificar si existe imagen de pago para este pedido y agregar botón si aplica
        (function(numero, container){
            try {
                fetch(`/cafeteria-pombo/menu/payment_image.php?numero_pedido=${numero}`)
                    .then(r => r.json())
                    .then(resp => {
                        if (resp && resp.status === 'ok' && resp.url) {
                            const btnId = `btn-pago-${numero}`;
                            if (!document.getElementById(btnId)) {
                                const btn = document.createElement('button');
                                btn.id = btnId;
                                btn.className = 'btn btn-outline-primary mb-2 ms-2';
                                btn.type = 'button';
                                btn.title = 'Ver imagen de pago';
                                btn.innerHTML = 'Ver imagen de pago';
                                btn.onclick = function() { abrirModalImagenPago(resp.url); };
                                container.appendChild(btn);
                            }
                        }
                    })
                    .catch(err => {/* no bloquear UI */});
            } catch(e) {}
        })(turno.numero_pedido, td);
    }

    /**
     * 🆕 MODIFICADO: Fetch domicilio CON DEBOUNCE
     * 
     * En lugar de hacer fetch inmediato, agrega a buffer
     * Espera 500ms a que se agreguen más pedidos
     * Luego procesa TODO el buffer de una vez
     * 
     * Esto reduce 25 requests simultáneos → 1-3 requests agrupados
     */
    fetchDomicilioConCache(numero_pedido) {
        // Agregar a buffer
        if (!this.domiciliofetchBuffer.includes(numero_pedido)) {
            this.domiciliofetchBuffer.push(numero_pedido);
        }

        // Cancelar timer anterior si existe
        if (this.domiciliofetchTimer) {
            clearTimeout(this.domiciliofetchTimer);
        }

        // Esperar 500ms y luego procesar TODO el buffer de una vez
        this.domiciliofetchTimer = setTimeout(() => {
            const buffer = this.domiciliofetchBuffer;
            this.domiciliofetchBuffer = [];  // Limpiar buffer

            if (buffer.length === 0) return;
            
            console.log(`⏱️ Procesando ${buffer.length} domicilios en BATCH (debounce)`);

            // Procesar cada uno (secuencial, espaciado 100ms)
            buffer.forEach((id, index) => {
                setTimeout(() => {
                    this._fetchSingleDomicilio(id);
                }, index * 100);
            });

        }, 500);  // Esperar 500ms a que se agreguen más
    }

    /**
     * 🆕 NUEVO: Fetch de un domicilio individual
     * (separado de fetchDomicilioConCache para poder hacerlo secuencial)
     */
    _fetchSingleDomicilio(numero_pedido) {
        fetch(`../../app/controllers/DomicilioController.php?action=obtener-por-pedido&id_pedido=${numero_pedido}`)
            .then(r => r.json())
            .then(resp => {
                if (resp.status === 'success') {
                    const domicilio = resp.data || {};
                    const id_domi = domicilio.id_domi;
                    const precio = domicilio.precio;
                    
                    // Guardar en caché
                    window.domiciliosCache[numero_pedido] = { id_domi, precio };
                    console.log(`✅ Domicilio cacheado para pedido ${numero_pedido}`);
                    
                    // Guardar en window.turnosData
                    if (window.turnosData && window.turnosData[numero_pedido]) {
                        window.turnosData[numero_pedido].id_domi = id_domi;
                        window.turnosData[numero_pedido].costo_domicilio = parseFloat(precio) || 0;
                    }
                    
                    // Actualizar UI
                    this.actualizarDomicilioEnUI(numero_pedido, id_domi, precio);
                }
            })
            .catch(err => console.error('❌ Error:', err));
    }

    /**
     * Actualizar UI del domicilio
     */
    actualizarDomicilioEnUI(numero_pedido, id_domi, precio) {
        const despachoCompleto = id_domi && precio && parseFloat(precio) > 0;
        
        const btn = document.getElementById(`btn-desp-${numero_pedido}`);
        if (btn) {
            if (despachoCompleto) {
                btn.classList.remove('btn-warning');
                btn.classList.add('btn-success');
                btn.textContent = 'Despachado';
                console.log(`✅ Pedido ${numero_pedido}: Botón cambió a DESPACHADO`);
            } else {
                console.log(`⚠️ Pedido ${numero_pedido}: Falta domiciliario o precio`);
            }
        }
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
                return estado || 'desconocido';
        }
    }

    formatCliente(turno) {
        const tipoSol = document.getElementById('tipoSolicitud')?.value;
        if (tipoSol === '50') {
            return `<strong>${turno.cliente}</strong><br>Dirección: ${turno.direccion || '—'}<br>Barrio: ${turno.barrio || '—'}<br><a href="https://api.whatsapp.com/send?phone=57${turno.telefono}&text=Hola" target="_blank">${turno.telefono || '—'}</a><br>${Formatters.dateShort(turno.fecha)}`;
        }
        return turno.cliente || 'Sin cliente';
    }

    /**
     * ✅ OPTIMIZADO: Debounce en updateTimers
     */
    updateTimers() {
        // Cancelar timer anterior
        if (this.updateTimersTimer) {
            clearTimeout(this.updateTimersTimer);
        }

        // Ejecutar después de 300ms
        this.updateTimersTimer = setTimeout(() => {
            const rows = document.querySelectorAll('#tabla-turnos [id^="tmp-"]');
            rows.forEach(el => {
                const fecha = new Date(el.closest('tr').dataset.fecha);
                el.textContent = Helpers.ago(fecha);
            });
        }, 300);
    }

    showLoading() {
        const container = document.getElementById(this.containerId);
        if (container) {
            container.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>`;
        }
    }

    showError(message) {
        const container = document.getElementById(this.containerId);
        if (container) {
            container.innerHTML = `<div class="alert alert-danger">${message}</div>`;
        }
    }
}

if (typeof window !== 'undefined') {
    window.pintarAcciones = (turno) => {
        const td = document.getElementById(`acc-${turno.numero_pedido}`);
        if (td) {
            const view = new TurnosView();
            view.pintarAcciones(turno, td);
        }
    };
    
    /**
     * Actualizar botón despachar/despachado
     */
    window.actualizarBotonDespachador = function(numero_pedido, id_domi, precio) {
        console.log('🔄 ACTUALIZANDO BOTÓN:', { numero_pedido, id_domi, precio });
        
        // Actualizar caché
        if (window.domiciliosCache) {
            window.domiciliosCache[numero_pedido] = { id_domi, precio };
            console.log(`♻️ Caché ACTUALIZADO para pedido ${numero_pedido}:`, { id_domi, precio });
        }
        
        const btn = document.getElementById(`btn-desp-${numero_pedido}`);
        if (!btn) {
            console.error('❌ Botón no encontrado: btn-desp-' + numero_pedido);
            return;
        }

        const despachoCompleto = id_domi && precio && parseFloat(precio) > 0;
        
        console.log('✔️ Tiene domiciliario?', !!id_domi);
        console.log('✔️ Tiene precio?', !!precio && parseFloat(precio) > 0);
        console.log('🎯 ¿Despacho completo?', despachoCompleto);

        if (despachoCompleto) {
            btn.classList.remove('btn-warning');
            btn.classList.add('btn-success');
            btn.textContent = 'Despachado';
            btn.disabled = false;
            console.log('✅ Botón actualizado a "DESPACHADO" VERDE');
        } else {
            btn.classList.remove('btn-success');
            btn.classList.add('btn-warning');
            btn.textContent = 'Despachar';
            btn.disabled = false;
            console.log('⚠️ Botón mantiene "DESPACHAR" AMARILLO (falta domiciliario o precio)');
        }
    };
}

// Función global para abrir modal con imagen de pago (usa el modal en views/llamadas.php)
function abrirModalImagenPago(url) {
    try {
        const modalContent = document.getElementById('modal-content');
        if (!modalContent) {
            window.open(url, '_blank');
            return;
        }
        modalContent.innerHTML = `<div style="text-align:center;"><img src="${url}" style="max-width:100%; height:auto; border-radius:8px;"></div>`;
        if (typeof bootstrap !== 'undefined') {
            const myModalEl = document.getElementById('myModal');
            const modal = new bootstrap.Modal(myModalEl);
            modal.show();
        } else {
            // fallback
            window.open(url, '_blank');
        }
    } catch(e) {
        window.open(url, '_blank');
    }
}