<?php
/**
 * tarifas.php - Módulo de Tarifas de Domicilios
 * UBICACIÓN: public/index.php?page=tarifas.php
 */
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>📍 Tarifas de Domicilios</h2>
            <p class="text-muted">Gestiona zonas, barrios y tarifas</p>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item"><button class="nav-link active" id="zonas-tab" data-bs-toggle="tab" data-bs-target="#zonas-content" type="button" role="tab">🗺️ Zonas</button></li>
        <li class="nav-item"><button class="nav-link" id="barrios-tab" data-bs-toggle="tab" data-bs-target="#barrios-content" type="button" role="tab">🏘️ Barrios</button></li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- ZONAS -->
        <div class="tab-pane fade show active" id="zonas-content" role="tabpanel">
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#modal-zona" onclick="limpiarFormularioZona()">➕ Nueva Zona</button>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr><th>Zona</th><th>Descripción</th><th>Tarifa</th><th>Barrios</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody id="tbody-zonas"><tr><td colspan="6" class="text-center">Cargando...</td></tr></tbody>
                </table>
            </div>
        </div>

        <!-- BARRIOS -->
        <div class="tab-pane fade" id="barrios-content" role="tabpanel">
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#modal-barrio" onclick="limpiarFormularioBarrio()">➕ Nuevo Barrio</button>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr><th>Barrio</th><th>Zona</th><th>Tarifa</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody id="tbody-barrios"><tr><td colspan="5" class="text-center">Cargando...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Zona -->
<div class="modal fade" id="modal-zona" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="titulo-modal-zona">Nueva Zona</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="form-zona">
                    <input type="hidden" id="id_zona_edit">
                    <div class="mb-3"><label class="form-label">Nombre</label><input type="text" id="nombre_zona" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Descripción</label><textarea id="descripcion_zona" class="form-control" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label">Tarifa ($)</label><input type="number" id="tarifa_zona" class="form-control" step="500" required></div>
                    <div class="mb-3"><label class="form-label">Estado</label><select id="estado_zona" class="form-select"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarZona()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Barrio -->
<div class="modal fade" id="modal-barrio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="titulo-modal-barrio">Nuevo Barrio</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="form-barrio">
                    <input type="hidden" id="id_barrio_edit">
                    <div class="mb-3"><label class="form-label">Zona</label><select id="zona_barrio" class="form-select" required><option value="">Seleccionar...</option></select></div>
                    <div class="mb-3"><label class="form-label">Nombre</label><input type="text" id="nombre_barrio" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Descripción</label><textarea id="descripcion_barrio" class="form-control" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label">Estado</label><select id="estado_barrio" class="form-select"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarBarrio()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
const API = '../api.php?route=';

async function cargarZonas() {
    const r = await fetch(`${API}zonas`);
    const data = await r.json();
    const tbody = document.getElementById('tbody-zonas');
    tbody.innerHTML = '';
    
    if(!data.zonas.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay zonas</td></tr>';
        return;
    }
    
    data.zonas.forEach(z => {
        tbody.innerHTML += `<tr id="fila-zona-${z.id_zona}">
            <td><strong>${z.nombre_zona}</strong></td>
            <td>${z.descripcion || '-'}</td>
            <td><strong>$${parseFloat(z.tarifa_domicilio).toLocaleString('es-CO')}</strong></td>
            <td><span class="badge bg-info">0</span></td>
            <td><span class="badge ${z.estado === 'activo' ? 'bg-success' : 'bg-danger'}">${z.estado}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarZona(${z.id_zona})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarZona(${z.id_zona})">Eliminar</button>
            </td>
        </tr>`;
    });
}

async function cargarBarrios() {
    const r = await fetch(`${API}barrios`);
    const data = await r.json();
    const tbody = document.getElementById('tbody-barrios');
    tbody.innerHTML = '';
    
    if(!data.barrios.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay barrios</td></tr>';
        return;
    }
    
    data.barrios.forEach(b => {
        tbody.innerHTML += `<tr id="fila-barrio-${b.id_barrio}">
            <td><strong>${b.nombre_barrio}</strong></td>
            <td>${b.nombre_zona}</td>
            <td>$${parseFloat(b.tarifa_domicilio).toLocaleString('es-CO')}</td>
            <td><span class="badge ${b.estado === 'activo' ? 'bg-success' : 'bg-danger'}">${b.estado}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarBarrio(${b.id_barrio})">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="eliminarBarrio(${b.id_barrio})">Eliminar</button>
            </td>
        </tr>`;
    });
}

async function llenarSelectZonas() {
    const r = await fetch(`${API}zonas`);
    const data = await r.json();
    const select = document.getElementById('zona_barrio');
    select.innerHTML = '<option value="">Seleccionar...</option>';
    data.zonas.forEach(z => {
        select.innerHTML += `<option value="${z.id_zona}">${z.nombre_zona}</option>`;
    });
}

function limpiarFormularioZona() {
    document.getElementById('form-zona').reset();
    document.getElementById('id_zona_edit').value = '';
    document.getElementById('titulo-modal-zona').textContent = 'Nueva Zona';
}

function limpiarFormularioBarrio() {
    document.getElementById('form-barrio').reset();
    document.getElementById('id_barrio_edit').value = '';
    document.getElementById('titulo-modal-barrio').textContent = 'Nuevo Barrio';
}

    async function guardarZona() {
        const id = document.getElementById('id_zona_edit').value;
        const data = {
            nombre_zona: document.getElementById('nombre_zona').value,
            descripcion: document.getElementById('descripcion_zona').value,
            tarifa_domicilio: parseFloat(document.getElementById('tarifa_zona').value),
            estado: document.getElementById('estado_zona').value
        };
        
        const r = await fetch(`${API}${id ? 'zonas/'+id : 'zonas'}`, {
            method: id ? 'PUT' : 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(id ? {...data, id_zona: id} : data)
        });
        
        const resp = await r.json();
        if(resp.success) {
            alert('Guardado');
            bootstrap.Modal.getInstance(document.getElementById('modal-zona')).hide();
            cargarZonas();
            llenarSelectZonas();
        } else alert('Error: ' + resp.message);
    }

async function editarZona(id) {
    try {
        const r = await fetch(`${API}zonas/${id}`);
        const data = await r.json();
        
        if (data.success && data.zona) {
            const zona = data.zona;
            document.getElementById('id_zona_edit').value = zona.id_zona;
            document.getElementById('nombre_zona').value = zona.nombre_zona;
            document.getElementById('descripcion_zona').value = zona.descripcion || '';
            document.getElementById('tarifa_zona').value = zona.tarifa_domicilio;
            document.getElementById('estado_zona').value = zona.estado;
            document.getElementById('titulo-modal-zona').textContent = 'Editar Zona';
            
            new bootstrap.Modal(document.getElementById('modal-zona')).show();
        } else {
            alert('Error cargando zona');
        }
    } catch (e) {
        console.error('Error:', e);
        alert('Error al cargar zona');
    }
}

    

async function eliminarZona(id) {
    if (!confirm('¿Eliminar esta zona?')) return;
    
    try {
        const r = await fetch(`${API}zonas/${id}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_zona: id })
        });
        
        const data = await r.json();
        
        if (data.success) {
            alert('Zona eliminada');
            cargarZonas();
            llenarSelectZonas();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (e) {
        console.error('Error:', e);
        alert('Error al eliminar zona');
    }
}

async function guardarBarrio() {
    const id = document.getElementById('id_barrio_edit').value;
    const data = {
        id_zona: parseInt(document.getElementById('zona_barrio').value),
        nombre_barrio: document.getElementById('nombre_barrio').value,
        descripcion: document.getElementById('descripcion_barrio').value,
        estado: document.getElementById('estado_barrio').value
    };
    
    const r = await fetch(`${API}${id ? 'barrios/'+id : 'barrios'}`, {
        method: id ? 'PUT' : 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(id ? {...data, id_barrio: id} : data)
    });
    
    const resp = await r.json();
    if(resp.success) {
        alert('Guardado');
        bootstrap.Modal.getInstance(document.getElementById('modal-barrio')).hide();
        cargarBarrios();
    } else alert('Error: ' + resp.message);
}

    async function editarBarrio(id) {
        try {
            const r = await fetch(`${API}barrios/${id}`);
            const data = await r.json();
            
            if (data.success && data.barrio) {
                const barrio = data.barrio;
                document.getElementById('id_barrio_edit').value = barrio.id_barrio;
                document.getElementById('zona_barrio').value = barrio.id_zona;
                document.getElementById('nombre_barrio').value = barrio.nombre_barrio;
                document.getElementById('descripcion_barrio').value = barrio.descripcion || '';
                document.getElementById('estado_barrio').value = barrio.estado;
                document.getElementById('titulo-modal-barrio').textContent = 'Editar Barrio';
                
                new bootstrap.Modal(document.getElementById('modal-barrio')).show();
            } else {
                alert('Error cargando barrio');
            }
        } catch (e) {
            console.error('Error:', e);
            alert('Error al cargar barrio');
        }
    }



async function eliminarBarrio(id) {
    if (!confirm('¿Eliminar este barrio?')) return;
    
    try {
        const r = await fetch(`${API}barrios/${id}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_barrio: id })
        });
        
        const data = await r.json();
        
        if (data.success) {
            alert('Barrio eliminado');
            cargarBarrios();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (e) {
        console.error('Error:', e);
        alert('Error al eliminar barrio');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    cargarZonas();
    cargarBarrios();
    llenarSelectZonas();
});
</script>