/**
 * caja_tm.js - Lógica de caja/pago
 * UBICACIÓN: public/js/caja_tm.js
 * 
 * ✅ Rutas via api.php (no ../controllers/)
 * ✅ Bootstrap 5 modals
 * ✅ Sin dependencia de jQuery
 */

const API_CAJA = '../api.php?route=';

document.addEventListener('DOMContentLoaded', function() {

    // ─── Continuar (modal confirmación) ─────────────
    const continueButton = document.getElementById('continueButton');
    if (continueButton) {
        continueButton.addEventListener('click', redireccionarPorTipoSolicitud);
    }

    // ─── Formulario de pago ─────────────────────────
    const formPago = document.getElementById('form-pago');
    if (formPago) {
        formPago.addEventListener('submit', function(e) {
            e.preventDefault();
            handlePaymentAndPrint();
        });
    }

    // ─── Agregar fila de abono ──────────────────────
    const btnAgregarAbono = document.getElementById('btn-agregar-abono');
    if (btnAgregarAbono) {
        btnAgregarAbono.addEventListener('click', function() {
            const contenedor = document.getElementById('abonos-container');
            if (!contenedor) return;
            const rows = contenedor.querySelectorAll('.abono-row');
            const nuevaRow = rows[rows.length - 1].cloneNode(true);
            nuevaRow.querySelectorAll('input[name="efectivo[]"]').forEach(inp => inp.value = '');
            contenedor.appendChild(nuevaRow);
        });
    }

    // ─── Guardar abonos ─────────────────────────────
    const btnGuardarAbonos = document.getElementById('btn-guardar-abonos');
    if (btnGuardarAbonos) {
        btnGuardarAbonos.addEventListener('click', guardarAbonos);
    }

});


// ═══════════════════════════════════════════════════════
// REDIRECCIÓN POR TIPO SOLICITUD
// ═══════════════════════════════════════════════════════
function redireccionarPorTipoSolicitud() {
    const el = document.getElementById('tipo_solicitud');
    const tipo = el ? el.value : '';
    let url;

    switch (tipo) {
        case '50':  url = 'index.php?page=whatsapp.php'; break;
        case '53':  url = 'index.php?page=llamadas.php'; break;
        default:    url = 'index.php?page=llamadas.php';
    }
    window.location.href = url;
}


// ═══════════════════════════════════════════════════════
// TOGGLE INPUTS SEGÚN MÉTODO DE PAGO
// ═══════════════════════════════════════════════════════
function toggleEfectivoInput() {
    const metodo = document.getElementById('m_pago').value;
    const efectivoInput       = document.getElementById('efectivoInput');
    const transferenciaInputs = document.getElementById('transferenciaInputs');
    const especialesInputs    = document.getElementById('especialesInputs');

    if (!efectivoInput || !transferenciaInputs || !especialesInputs) return;

    // Reset
    ['pago','banco','referencia','detalle'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    efectivoInput.style.display       = 'none';
    transferenciaInputs.style.display = 'none';
    especialesInputs.style.display    = 'none';

    // Mostrar según método
    if (['efectivo','efectivo_transferencia','tarjeta_efectivo','brebe_efectivo','credito'].includes(metodo)) {
        efectivoInput.style.display = 'block';
        const pagoInput = document.getElementById('pago');
        if (pagoInput) pagoInput.value = document.getElementById('total')?.value || '';
    }

    if (['transferencia','efectivo_transferencia'].includes(metodo)) {
        transferenciaInputs.style.display = 'block';
    }

    if (['cortesia','devolucion','credito'].includes(metodo)) {
        especialesInputs.style.display = 'block';
    }
}


// ═══════════════════════════════════════════════════════
// PROCESAR PAGO + IMPRIMIR
// ═══════════════════════════════════════════════════════
function handlePaymentAndPrint() {
    const form = document.getElementById('form-pago');
    if (!form) return;

    const submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Procesando…';
    }

    const formData = new FormData(form);

    fetch(`${API_CAJA}caja/pagar`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            console.log('✅ Pago procesado');

            // Imprimir ticket
            const numeroPedido = formData.get('numero_pedido');
            const metodoPago   = formData.get('m_pago');
            imprimirTicket(numeroPedido, metodoPago);

            // Mostrar modal confirmación (Bootstrap 5)
            const modalEl = document.getElementById('confirmationModal');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
                modal.show();
            }
        } else {
            alert('Error: ' + data.message);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Procesar Pago';
            }
        }
    })
    .catch(e => {
        console.error('Error:', e);
        alert('Error al procesar el pago');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Procesar Pago';
        }
    });
}


// ═══════════════════════════════════════════════════════
// REVERSAR CAJA
// ═══════════════════════════════════════════════════════
function reversarCaja(numeroPedido) {
    const codigo = prompt('Ingresa el código de seguridad para reversar el pago:');
    if (!codigo || !codigo.trim()) {
        alert('El código de seguridad es obligatorio.');
        return;
    }

    fetch(`${API_CAJA}caja/reversar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ numero_pedido: numeroPedido, codigo_seguridad: codigo })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Pago reversado correctamente.');
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(e => {
        console.error('Error:', e);
        alert('Error de conexión');
    });
}


// ═══════════════════════════════════════════════════════
// GUARDAR ABONOS DE CRÉDITO
// ═══════════════════════════════════════════════════════
function guardarAbonos() {
    const idCredito = document.getElementById('id_credito_hidden')?.value;
    if (!idCredito) {
        alert('No se encontró el id del crédito.');
        return;
    }

    const contenedor = document.getElementById('abonos-container');
    if (!contenedor) return;

    const rows = contenedor.querySelectorAll('.abono-row');
    let abonosData = [];

    rows.forEach(row => {
        const metodo = row.querySelector('select[name="m_pagocr[]"]')?.value || '';
        const valor  = row.querySelector('input[name="efectivo[]"]')?.value || 0;
        abonosData.push({ m_pagocr: metodo, efectivo: valor });
    });

    fetch(`${API_CAJA}caja/abonar`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_credito: idCredito, abonos: abonosData })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Abonos guardados exitosamente.');
            // Cerrar modal Bootstrap 5
            const modalEl = document.getElementById('modal-abonar');
            if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(e => {
        console.error('Error:', e);
        alert('Error al guardar abonos');
    });
}


// ═══════════════════════════════════════════════════════
// CALCULAR CAMBIO
// ═══════════════════════════════════════════════════════
function calcularCambio() {
    const totalEl     = document.getElementById('total');
    const pagoEl      = document.getElementById('pago');
    const resultadoEl = document.getElementById('resultado');
    if (!totalEl || !pagoEl || !resultadoEl) return;

    const total  = parseFloat(totalEl.value) || 0;
    const pago   = parseFloat(pagoEl.value || '0');
    const metodo = document.getElementById('m_pago')?.value || '';

    const fmt = new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP',
        minimumFractionDigits: 0, maximumFractionDigits: 0
    });

    if (['efectivo','tarjeta_efectivo','brebe_efectivo','credito'].includes(metodo)) {
        if (pago < total) {
            const label = metodo === 'credito' ? 'Restante crédito' 
                        : metodo === 'tarjeta_efectivo' ? 'Restante tarjeta'
                        : metodo === 'brebe_efectivo' ? 'Restante Brebe'
                        : 'Falta por pagar';
            resultadoEl.textContent = `${label}: ${fmt.format(total - pago)}`;
            resultadoEl.style.color = 'var(--coral, #ff4757)';
        } else if (pago === total) {
            resultadoEl.textContent = 'Pagado completo en efectivo.';
            resultadoEl.style.color = 'var(--emerald, #10b981)';
        } else {
            resultadoEl.textContent = `Cambio: ${fmt.format(pago - total)}`;
            resultadoEl.style.color = 'var(--sky, #3b82f6)';
        }
    } else if (metodo === 'efectivo_transferencia') {
        if (pago < total) {
            resultadoEl.textContent = `Restante transferencia: ${fmt.format(total - pago)}`;
            resultadoEl.style.color = 'var(--amber, #f59e0b)';
        } else {
            resultadoEl.textContent = `Cambio: ${fmt.format(pago - total)}`;
            resultadoEl.style.color = 'var(--sky, #3b82f6)';
        }
    } else if (metodo === 'transferencia') {
        resultadoEl.textContent = 'Pagado por transferencia.';
        resultadoEl.style.color = 'var(--emerald, #10b981)';
    } else {
        resultadoEl.textContent = '';
    }
}


// ═══════════════════════════════════════════════════════
// IMPRIMIR TICKET (QZ Tray) - incluye anulados
// ═══════════════════════════════════════════════════════
function imprimirTicket(numeroPedido, metodoPago) {
    console.log("🖨️ Iniciando impresión...");

    const filas  = document.querySelectorAll('#tabla-productos tbody tr');
    const cajero = document.getElementById('nombre_cajero')?.value || 'N/A';
    const fechaHoraImp = new Date().toLocaleString('es-CO');

    let cliente = 'Sin nombre';
    let celular = 'Sin celular';
    document.querySelectorAll('span, p').forEach(el => {
        const text = el.textContent;
        if (text.includes('Cliente:')) cliente = text.replace(/.*Cliente:\s*/, '').trim();
        if (text.includes('Celular:')) celular = text.replace(/.*Celular:\s*/, '').trim();
    });

    let ticket = [];
    ticket.push("\x1B\x40");           // Reset
    ticket.push("\x1B\x61\x01");       // Centrar
    ticket.push("\x1B\x21\x30");       // Grande
    ticket.push("Restaurante HEIYUBAI\n");
    ticket.push("\x1B\x21\x00");       // Normal
    ticket.push("--------------------------------\n");
    ticket.push(`Pedido N°: ${numeroPedido}\n`);
    ticket.push(`Fecha/Hora: ${fechaHoraImp}\n`);
    ticket.push(`Cajero: ${cajero}\n`);
    ticket.push(`Pago: ${metodoPago || '—'}\n`);
    ticket.push(`Cliente: ${cliente}\n`);
    ticket.push(`Celular: ${celular}\n`);
    ticket.push("--------------------------------\n");
    ticket.push("Prefijo    Cant   Tipo       Precio  Total\n");
    ticket.push("------------------------------------------\n");

    filas.forEach(tr => {
        const esAnulado = tr.dataset.anulado === '1';
        const prefijo   = (tr.children[0]?.innerText || 'N/A').replace('↳','').trim().substring(0, 10);
        const cantidad  = (tr.children[2]?.innerText || '0').trim();
        const tipoProd  = (tr.children[6]?.innerText || 'N/A').trim().substring(0, 8);
        const precio    = (tr.children[3]?.innerText || '0').replace(/[$,.]/g, '');
        const subtotal  = (tr.children[4]?.innerText || '0').replace(/[$,.−]/g, '');
        const detalle   = (tr.children[5]?.innerText || '').trim().substring(0, 20);

        if (esAnulado && parseInt(cantidad) < 0) {
            // Línea de anulación en ticket
            ticket.push("  >> ANULADO: " + prefijo.trim() + " x" + cantidad + "  -$" + subtotal + "\n");
        } else if (esAnulado) {
            // Original anulado (tachado)
            ticket.push("  [X] " + prefijo.trim().padEnd(8) + " " + cantidad.padStart(3) + " (anulado)\n");
        } else {
            // Producto normal
            ticket.push(
                prefijo.padEnd(10) + ' ' + cantidad.padStart(3) + ' ' +
                tipoProd.padEnd(10) + ' $' + precio.padStart(6) + ' $' + subtotal.padStart(6) + '\n'
            );
            if (detalle) ticket.push('   Detalle: ' + detalle + '\n');
        }
    });

    const totalEl = document.getElementById('total_a_pagar_con_descuento');
    const totalTxt = totalEl ? totalEl.innerText.replace(/[$,.]/g, '') : '0';

    ticket.push("--------------------------------\n");
    ticket.push("\x1B\x21\x20TOTAL PAGADO: $" + totalTxt + "\x1B\x21\x00\n");
    ticket.push("================================\n");
    ticket.push("\x1B\x61\x01\x1B\x21\x20¡Gracias por su compra!\x1B\x21\x00\n");
    ticket.push("\n\n\n\n");
    ticket.push("\x1D\x56\x00");        // Corte
    ticket.push("\x1B\x70\x00\x19\xFA"); // Cajón

    console.log("✅ Contenido ticket:\n", ticket.join(''));

    // QZ Tray
    if (typeof qz !== 'undefined') {
        qz.websocket.connect().then(() => {
            return qz.printers.find("POS-80");
        }).then(printer => {
            let config = qz.configs.create(printer, { encoding: 'ISO-8859-1' });
            return qz.print(config, [{ type: 'raw', format: 'plain', data: ticket.join('') }]);
        }).then(() => {
            console.log("✅ Ticket impreso.");
            return qz.websocket.disconnect();
        }).catch(err => {
            console.error("❌ Error QZ Tray:", err);
        });
    } else {
        console.warn("⚠️ QZ Tray no disponible, ticket en consola.");
    }
}

console.log('✅ caja_tm.js cargado');