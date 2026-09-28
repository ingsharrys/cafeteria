<!--
    horario.php - Configuración del horario de atención
    Permite al panel definir la hora de apertura y cierre del servicio de pedidos.
-->
<div class="container mt-5">
    <h3>🕐 Horario de atención</h3>
    <p style="color:#6b7280; font-size:0.9rem;">Define desde qué hora hasta qué hora se pueden hacer pedidos. Fuera de este horario, el menú muestra "cerrado".</p>

    <div class="card" style="max-width:420px; padding:20px; margin-top:12px;">
        <div class="form-group">
            <label for="horaApertura"><strong>Apertura</strong></label>
            <input type="time" id="horaApertura" class="form-control" required>
        </div>
        <div class="form-group mt-2">
            <label for="horaCierre"><strong>Cierre</strong></label>
            <input type="time" id="horaCierre" class="form-control" required>
        </div>
        <button id="guardarHorario" class="btn btn-success mt-3 w-100">Guardar horario</button>
        <div id="horarioMsg" style="margin-top:10px; font-weight:700; text-align:center;"></div>
    </div>
</div>

<script>
(function () {
    const API = '../api.php?route=horario';
    const inApertura = document.getElementById('horaApertura');
    const inCierre = document.getElementById('horaCierre');
    const msg = document.getElementById('horarioMsg');

    function cargar() {
        fetch(API, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    inApertura.value = data.apertura || '05:00';
                    inCierre.value = data.cierre || '09:30';
                }
            })
            .catch(() => {});
    }

    document.getElementById('guardarHorario').addEventListener('click', function () {
        const apertura = inApertura.value;
        const cierre = inCierre.value;
        if (!apertura || !cierre) {
            msg.textContent = 'Completa ambas horas.';
            msg.style.color = '#b45309';
            return;
        }
        if (cierre <= apertura) {
            msg.textContent = 'La hora de cierre debe ser mayor que la de apertura.';
            msg.style.color = '#b45309';
            return;
        }
        msg.textContent = 'Guardando…';
        msg.style.color = '#6b7280';
        fetch(API, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ apertura: apertura, cierre: cierre })
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    msg.textContent = '✅ Horario guardado (' + res.apertura + ' - ' + res.cierre + ')';
                    msg.style.color = '#16a34a';
                } else {
                    msg.textContent = res.error || 'No se pudo guardar';
                    msg.style.color = '#dc2626';
                }
            })
            .catch(() => {
                msg.textContent = 'Error de conexión';
                msg.style.color = '#dc2626';
            });
    });

    cargar();
})();
</script>
