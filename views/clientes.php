<!--
    clientes.php - Aprobación de clientes
    Permite al panel aprobar/rechazar clientes para evitar pedidos de broma.
-->
<div class="container mt-5">
    <h3>👥 Clientes</h3>
    <p style="color:#6b7280; font-size:0.9rem;">Aprueba los clientes reales. Solo los aprobados pueden hacer pedidos.</p>

    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin:12px 0;">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary filtro-cliente active" data-estado="pendientes">Pendientes</button>
            <button type="button" class="btn btn-outline-primary filtro-cliente" data-estado="aprobados">Aprobados</button>
            <button type="button" class="btn btn-outline-primary filtro-cliente" data-estado="todos">Todos</button>
        </div>
        <input type="text" id="buscarCliente" class="form-control" placeholder="Buscar por nombre o teléfono" style="max-width:280px;">
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th>Barrio</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="clientes-tbody">
                <tr><td colspan="5" class="text-center">Cargando…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    const API = '../api.php?route=clientes';
    const tbody = document.getElementById('clientes-tbody');
    let estado = 'pendientes';
    let buscar = '';

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function cargar() {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Cargando…</td></tr>';
        const url = API + '&estado=' + encodeURIComponent(estado) + '&buscar=' + encodeURIComponent(buscar);
        fetch(url, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">' + esc(data.error || 'Error') + '</td></tr>';
                    return;
                }
                if (!data.clientes.length) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin clientes</td></tr>';
                    return;
                }
                tbody.innerHTML = data.clientes.map(render).join('');
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error de conexión</td></tr>';
            });
    }

    function render(c) {
        const aprobado = parseInt(c.aprobado, 10) === 1;
        const badge = aprobado
            ? '<span class="badge bg-success">Aprobado</span>'
            : '<span class="badge bg-warning text-dark">Pendiente</span>';
        const accion = aprobado
            ? '<button class="btn btn-sm btn-outline-danger" onclick="cambiarCliente(' + c.id + ',0)">Quitar aprobación</button>'
            : '<button class="btn btn-sm btn-success" onclick="cambiarCliente(' + c.id + ',1)">Aprobar</button>';
        return '<tr>'
            + '<td>' + esc(c.cliente) + '</td>'
            + '<td>' + esc(c.celular) + '</td>'
            + '<td>' + esc(c.barrio) + '</td>'
            + '<td>' + badge + '</td>'
            + '<td>' + accion + '</td>'
            + '</tr>';
    }

    window.cambiarCliente = function (id, val) {
        const ruta = val === 1 ? 'aprobar' : 'rechazar';
        fetch('../api.php?route=clientes/' + ruta, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) { cargar(); }
                else { alert(res.error || 'No se pudo actualizar'); }
            })
            .catch(() => alert('Error de conexión'));
    };

    document.querySelectorAll('.filtro-cliente').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filtro-cliente').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            estado = this.getAttribute('data-estado');
            cargar();
        });
    });

    let t = null;
    document.getElementById('buscarCliente').addEventListener('input', function () {
        buscar = this.value.trim();
        clearTimeout(t);
        t = setTimeout(cargar, 300);
    });

    cargar();
})();
</script>
