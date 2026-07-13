/**
 * consolidado.js - VERSIÓN FINAL
 * 
 * CAMBIOS:
 * ✅ SIN actualización automática
 * ✅ Tabs para filtrar por tipo de solicitud
 * ✅ No carga productos automáticamente (para evitar errores 500)
 * ✅ Botón para cargar productos bajo demanda
 */

const API_BASE = '/app/controllers';

// Tipos de solicitud disponibles
const TIPOS_SOLICITUD = {
    '': 'Todos',
    '50': 'Domicilios',
    '51': 'Turno',
    '52': 'Mesas',
    '53': 'Recoger'
};

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

// ═════════════════════════════════════════════════════════════
// FUNCIÓN: Cargar datos de turnos por fecha (SIN AUTO-UPDATE)
// ═════════════════════════════════════════════════════════════
function cargarDatosTurnos() {
    const fechaSeleccionada = document.getElementById('fechaSeleccionada').value || 
                              new Date().toISOString().split('T')[0];

    console.log('🔍 Cargando turnos...');
    console.log('   Fecha:', fechaSeleccionada);

    const params = new URLSearchParams({
        fecha: fechaSeleccionada
    });

    const url = `${API_BASE}/obtener_datos_consolidado.php?${params.toString()}`;
    console.log('📡 URL:', url);

    fetch(url)
        .then(response => {
            console.log('📊 Status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status}`);
            }
            
            return response.json();
        })
        .then(data => {
            console.log('✅ Respuesta:', data);

            if (!data.success && data.error) {
                throw new Error(data.error);
            }

            const turnosContainer = document.getElementById('turnos-container');
            turnosContainer.innerHTML = '';

            if (Array.isArray(data.turnos) && data.turnos.length > 0) {
                console.log(`📋 ${data.turnos.length} turnos encontrados`);
                renderizarTurnosConTabs(data.turnos);
            } else {
                turnosContainer.innerHTML = '<div class="alert alert-info">No hay turnos disponibles para esta fecha.</div>';
            }
        })
        .catch(error => {
            console.error('❌ Error:', error);
            
            const turnosContainer = document.getElementById('turnos-container');
            turnosContainer.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Error:</strong> ${error.message}
                </div>
            `;
        });
}

// ═════════════════════════════════════════════════════════════
// FUNCIÓN: Renderizar turnos con TABS por tipo
// ═════════════════════════════════════════════════════════════
function renderizarTurnosConTabs(todosLosTurnos) {
    const turnosContainer = document.getElementById('turnos-container');

    // Agrupar por tipo_solicitud
    const turnosPorTipo = {};
    
    // Inicializar todos los tipos
    Object.keys(TIPOS_SOLICITUD).forEach(tipo => {
        turnosPorTipo[tipo] = [];
    });

    // Asignar turnos a tipos (si no tienen tipo, ir a 'Todos')
    todosLosTurnos.forEach(turno => {
        turnosPorTipo[''].push(turno);
    });

    // HTML de tabs
    let tabsHTML = `
        <ul class="nav nav-tabs mb-3" id="turnosTabs" role="tablist">
    `;

    let tabContentHTML = '<div class="tab-content" id="turnosTabContent">';
    let primeraTab = true;

    // Crear tabs para cada tipo
    Object.keys(TIPOS_SOLICITUD).forEach(tipo => {
        const nombreTipo = TIPOS_SOLICITUD[tipo];
        const turnosDeTipo = turnosPorTipo[tipo];
        const cantidad = turnosDeTipo.length;
        const tabId = `tab-tipo-${tipo || 'todos'}`;
        const isActive = primeraTab ? 'active' : '';

        // Tab header
        tabsHTML += `
            <li class="nav-item" role="presentation">
                <button class="nav-link ${isActive}" id="${tabId}-btn" data-bs-toggle="tab" 
                        data-bs-target="#${tabId}" type="button" role="tab">
                    ${nombreTipo} <span class="badge bg-secondary">${cantidad}</span>
                </button>
            </li>
        `;

        // Tab content
        tabContentHTML += `
            <div class="tab-pane fade ${isActive ? 'show active' : ''}" id="${tabId}" role="tabpanel">
                <div id="contenido-${tabId}" class="turnos-tabla">
                </div>
            </div>
        `;

        primeraTab = false;
    });

    tabsHTML += '</ul>';
    tabContentHTML += '</div>';

    // Renderizar en el DOM
    turnosContainer.innerHTML = tabsHTML + tabContentHTML;

    // Llenar cada tabla
    Object.keys(TIPOS_SOLICITUD).forEach(tipo => {
        const turnosDeTipo = turnosPorTipo[tipo];
        const tabId = `tab-tipo-${tipo || 'todos'}`;
        const contenedorTabla = document.getElementById(`contenido-${tabId}`);

        if (turnosDeTipo.length === 0) {
            contenedorTabla.innerHTML = '<p class="text-muted">No hay turnos de este tipo.</p>';
            return;
        }

        // Crear tabla
        let tablaHTML = `
            <table class="table table-bordered table-hover table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Turno</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Método de Pago</th>
                        <th style="width: 150px;">Acción</th>
                    </tr>
                </thead>
                <tbody>
        `;

        turnosDeTipo.forEach(turno => {
            let claseEstado = 'table-secondary';
            if (turno.estado === 'preparacion') claseEstado = 'table-warning';
            else if (turno.estado === 'espera') claseEstado = 'table-primary';
            else if (turno.estado === 'listo') claseEstado = 'table-success';
            else if (turno.estado === 'entregado') claseEstado = 'table-dark';

            const badgeClase = claseEstado.replace('table-', 'bg-');

            tablaHTML += `
                <tr class="${claseEstado}">
                    <td><strong>${turno.turno}</strong></td>
                    <td>${turno.cliente}</td>
                    <td>
                        <span class="badge ${badgeClase}">
                            ${mapEstadoLabel(turno.estado)}
                        </span>
                    </td>
                    <td>${turno.m_pago || '-'}</td>
                    <td>
                        <form action="/public/index.php?page=caja_tm.php" method="POST" style="display:inline;">
                            <input type="hidden" name="numero_pedido" value="${turno.id_pedidoc}">
                            <button type="submit" class="btn btn-sm btn-info">Caja</button>
                        </form>
                    </td>
                </tr>
            `;
        });

        tablaHTML += `
                </tbody>
            </table>
        `;

        contenedorTabla.innerHTML = tablaHTML;
    });

    console.log('✅ Tabs renderizados');
}

// ═════════════════════════════════════════════════════════════
// FUNCIÓN: Cargar productos bajo demanda (NO automático)
// ═════════════════════════════════════════════════════════════
function cargarProductosPorDemanda(numeroPedido) {
    const params = new URLSearchParams({
        id_pedido: numeroPedido
    });

    const url = `${API_BASE}/obtener_productos_pedido.php?${params.toString()}`;

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log(`📦 Productos del pedido ${numeroPedido}:`, data);

            if (data.success && Array.isArray(data.productos) && data.productos.length > 0) {
                let productosHTML = '<table class="table table-sm"><tbody>';
                
                data.productos.forEach(producto => {
                    const subtotal = producto.cantidad * producto.precio;
                    productosHTML += `
                        <tr>
                            <td><strong>${producto.producto}</strong></td>
                            <td>Cant: ${producto.cantidad}</td>
                            <td>$${producto.precio}</td>
                            <td>$${subtotal}</td>
                        </tr>
                    `;
                });
                
                productosHTML += '</tbody></table>';
                alert(`Productos del pedido ${numeroPedido}:\n\n` + productosHTML);
            } else {
                alert('No hay productos para este pedido.');
            }
        })
        .catch(error => {
            console.error('❌ Error cargando productos:', error);
            alert('Error al cargar productos: ' + error.message);
        });
}

// ═════════════════════════════════════════════════════════════
// FUNCIÓN: Establecer fecha actual
// ═════════════════════════════════════════════════════════════
function setFechaActual() {
    const filtroFecha = document.getElementById('fechaSeleccionada');
    if (filtroFecha) {
        const fechaActual = new Date().toISOString().split('T')[0];
        filtroFecha.value = fechaActual;
        console.log('📅 Fecha:', fechaActual);
    }
}

// ═════════════════════════════════════════════════════════════
// INICIALIZACIÓN
// ═════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ consolidado.js cargado');
    
    setFechaActual();
    cargarDatosTurnos();
    
    // NO hay setInterval (sin actualización automática)
    console.log('⚠️ SIN actualización automática - usa el botón de "Cargar" para actualizar');
});