/**
 * domicilios.js - ARREGLADO
 * Guardado correcto de domiciliario con mejor logging
 */

const DOMICILIOS_API = '../../app/controllers/DomicilioController.php';

let estadoDomicilio = { idPedidoActual: null, modal: null };

document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('myModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        estadoDomicilio.modal = new bootstrap.Modal(modalEl);
    }
});

// ═══════════════════════════════════════════════════════
// ABRIR MODAL
// ═══════════════════════════════════════════════════════

async function abrirModalDomicilio(numero_pedido) {
    console.log('🚀 Abriendo modal para pedido:', numero_pedido);
    estadoDomicilio.idPedidoActual = numero_pedido;

    try {
        const respDom = await fetch(`${DOMICILIOS_API}?action=obtener-domiciliarios`).then(r => r.json());
        console.log('📦 Respuesta domiciliarios:', respDom);
        
        if (respDom.status !== 'success') {
            alert('❌ No hay domiciliarios disponibles');
            return;
        }

        // ✅ El modelo ya filtra solo los disponibles (elimina = 0)
        const domiciliarios = respDom.domiciliarios;
        
        if (domiciliarios.length === 0) {
            alert('❌ No hay domiciliarios disponibles');
            return;
        }

        console.log(`📍 Domiciliarios disponibles: ${domiciliarios.length}`, domiciliarios);

        const respData = await fetch(`${DOMICILIOS_API}?action=obtener-por-pedido&id_pedido=${numero_pedido}`).then(r => r.json());
        const domActual = respData.status === 'success' ? respData.data : null;
        
        console.log('🔍 Domicilio actual:', domActual);

        renderizarModal(domiciliarios, domActual);

        if (estadoDomicilio.modal) {
            estadoDomicilio.modal.show();
        }

    } catch (error) {
        console.error('❌ Error en abrirModalDomicilio:', error);
        alert('Error al abrir modal: ' + error.message);
    }
}

// ═══════════════════════════════════════════════════════
// RENDERIZAR
// ═══════════════════════════════════════════════════════

function renderizarModal(domiciliarios, domActual) {
    const modalContent = document.getElementById('modal-content');
    
    let opciones = '<option value="">-- Seleccione --</option>';
    domiciliarios.forEach(d => {
        const selected = domActual && domActual.id_domi == d.id_e ? 'selected' : '';
        opciones += `<option value="${d.id_e}" ${selected}>${d.repartidor} (${d.celu_reparti || 'Sin teléfono'})</option>`;
    });

    const precio = domActual?.precio || '';

    const html = `
        <div class="card border-info">
            <div class="card-body">
                <h6 class="mb-3">📍 Domiciliarios Disponibles</h6>
                <select id="select-domiciliario" class="form-select">${opciones}</select>
                
                <h6 class="mb-3 mt-4">📞 Teléfono</h6>
                <input type="text" id="input-celular" class="form-control" placeholder="Teléfono" readonly>
                
                <h6 class="mb-3 mt-4">⭐ Calificación</h6>
                <input type="text" id="input-calificacion" class="form-control" placeholder="Calificación" readonly>
                
                <h6 class="mb-3 mt-4">💰 Precio del Domicilio</h6>
                <input type="number" id="input-precio" class="form-control" placeholder="0.00" value="${precio}" min="0" step="0.01">
                
                <div class="alert alert-info small mt-3">✅ Se guarda automáticamente</div>
            </div>
        </div>
    `;

    modalContent.innerHTML = html;

    const select = document.getElementById('select-domiciliario');
    const input = document.getElementById('input-precio');
    
    if (select) {
        select.addEventListener('change', async (e) => {
            console.log('🔄 Select cambió a:', e.target.value);
            actualizarDatosRepartidor(domiciliarios, e.target.value);
            await guardarDomiciliario();
        });
    }
    if (input) {
        input.addEventListener('blur', async (e) => {
            console.log('💰 Precio cambió a:', e.target.value);
            await guardarPrecio(e);
        });
    }
    
    // Mostrar datos del repartidor actual si existe
    if (domActual?.id_domi) {
        console.log('📱 Mostrando datos del repartidor actual');
        actualizarDatosRepartidor(domiciliarios, domActual.id_domi);
    }
}

// 🔥 FUNCIÓN: Actualizar datos del repartidor
function actualizarDatosRepartidor(domiciliarios, id_repartidor) {
    const repartidor = domiciliarios.find(d => d.id_e == id_repartidor);
    
    if (repartidor) {
        document.getElementById('input-celular').value = repartidor.celu_reparti || 'Sin teléfono';
        document.getElementById('input-calificacion').value = repartidor.calificacion || 'Sin calificación';
        console.log(`📱 Repartidor: ${repartidor.repartidor} | Teléfono: ${repartidor.celu_reparti} | Calificación: ${repartidor.calificacion}`);
    } else {
        document.getElementById('input-celular').value = '';
        document.getElementById('input-calificacion').value = '';
    }
}

// ═══════════════════════════════════════════════════════
// GUARDAR DOMICILIARIO
// ═══════════════════════════════════════════════════════

async function guardarDomiciliario() {
    const select = document.getElementById('select-domiciliario');
    const id_domi = select?.value;
    const id_pedido = estadoDomicilio.idPedidoActual;

    if (!id_domi) {
        console.warn('⚠️ No hay domiciliario seleccionado');
        return;
    }

    console.log('💾 Guardando domiciliario:', { id_pedido, id_domi });

    try {
        const resp = await fetch(`${DOMICILIOS_API}?action=actualizar-domiciliario`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_pedido, id_domi: parseInt(id_domi) })
        }).then(r => r.json());

        console.log('📨 Respuesta servidor:', resp);

        if (resp.status === 'success') {
            console.log(`✅ Domiciliario guardado exitosamente: ${id_domi}`);
            
            // 🔥 Guardar en window.turnosData para print_service.js
            if (window.turnosData && window.turnosData[id_pedido]) {
                window.turnosData[id_pedido].id_domi = parseInt(id_domi);
                console.log('💾 ID domiciliario guardado en window.turnosData');
            }
            
            // ✅ ACTUALIZAR BOTÓN Y CACHÉ
            await actualizarBoton(id_pedido);
        } else {
            console.error('❌ Error en respuesta del servidor:', resp.message);
            alert('Error: ' + resp.message);
        }
    } catch (error) {
        console.error('❌ Error en guardarDomiciliario:', error);
        alert('Error al guardar domiciliario: ' + error.message);
    }
}

// ═══════════════════════════════════════════════════════
// GUARDAR PRECIO
// ═══════════════════════════════════════════════════════

async function guardarPrecio(event) {
    const precio = event.target?.value;
    const id_pedido = estadoDomicilio.idPedidoActual;

    if (!precio || precio === '') {
        console.warn('⚠️ Precio vacío');
        return;
    }

    console.log('💾 Guardando precio:', { id_pedido, precio });

    try {
        const resp = await fetch(`${DOMICILIOS_API}?action=actualizar-precio`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_pedido, precio: parseFloat(precio) })
        }).then(r => r.json());

        console.log('📨 Respuesta servidor:', resp);

        if (resp.status === 'success') {
            console.log(`✅ Precio guardado exitosamente: $${precio}`);
            
            // 🔥 Guardar en window.turnosData PARA print_service.js
            if (window.turnosData && window.turnosData[id_pedido]) {
                window.turnosData[id_pedido].costo_domicilio = parseFloat(precio);
                console.log('💾 Costo domicilio guardado en window.turnosData:', parseFloat(precio));
            }
            
            // ✅ ACTUALIZAR BOTÓN Y CACHÉ
            await actualizarBoton(id_pedido);
        } else {
            console.error('❌ Error en respuesta del servidor:', resp.message);
            alert('Error: ' + resp.message);
        }
    } catch (error) {
        console.error('❌ Error en guardarPrecio:', error);
        alert('Error al guardar precio: ' + error.message);
    }
}

// ═══════════════════════════════════════════════════════
// ACTUALIZAR BOTÓN
// ═══════════════════════════════════════════════════════

async function actualizarBoton(id_pedido) {
    console.log('🔄 Actualizando botón para pedido:', id_pedido);
    
    try {
        const resp = await fetch(`${DOMICILIOS_API}?action=obtener-por-pedido&id_pedido=${id_pedido}`).then(r => r.json());
        
        console.log('📨 Datos actualizados desde BD:', resp);

        if (resp.status !== 'success') {
            console.warn('⚠️ No hay domicilio registrado aún');
            return;
        }

        const domicilio = resp.data;
        const id_domi = domicilio?.id_domi;
        const precio = domicilio?.precio;

        console.log('📦 Datos desde BD:', { id_domi, precio });

        // 🔥 INVALIDAR CACHÉ de domicilios
        if (window.domiciliosCache) {
            delete window.domiciliosCache[id_pedido];
            console.log(`🗑️ Caché invalidado para pedido ${id_pedido}`);
        }

        // 🔥 ACTUALIZAR window.turnosData
        if (window.turnosData && window.turnosData[id_pedido]) {
            window.turnosData[id_pedido].id_domi = id_domi;
            window.turnosData[id_pedido].costo_domicilio = parseFloat(precio) || 0;
            console.log('💾 window.turnosData actualizado:', {
                numero_pedido: id_pedido,
                id_domi: id_domi,
                costo_domicilio: parseFloat(precio) || 0
            });
        }

        // ✅ Cambiar botón directamente
        if (typeof window.actualizarBotonDespachador === 'function') {
            console.log('✅ Llamando window.actualizarBotonDespachador()');
            window.actualizarBotonDespachador(id_pedido, id_domi, precio);
        }

        // ✅ Recargar tabla si es posible
        console.log('🔄 Intentando recargar tabla de turnos...');
        if (window.DashboardApp?.controllers?.turnos?.fetch) {
            window.DashboardApp.controllers.turnos.fetch();
            console.log('✅ Tabla recargada con fetch');
        } else if (window.DashboardApp?.controllers?.turnos?.render) {
            window.DashboardApp.controllers.turnos.render();
            console.log('✅ Tabla re-renderizada');
        } else {
            console.warn('⚠️ No se encontró método para recargar tabla');
        }

    } catch (error) {
        console.error('❌ Error en actualizarBoton:', error);
    }
}

window.abrirModalDomicilio = abrirModalDomicilio;
console.log('✅ domicilios.js cargado - ARREGLADO CON LOGGING COMPLETO');