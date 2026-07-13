/**
 * modal_script.js - Modal de validación de código mesero
 * UBICACIÓN: heiyubai/public/js/modal_script.js
 * 
 * FLUJO:
 * 1. footer.php renderiza <div id="modalContainer"> si NO hay cajero en sesión
 * 2. Este script inyecta el modal Bootstrap 5 y lo muestra inmediatamente
 * 3. Usuario ingresa su código de mesero (cod_mese de tabla meseros)
 * 4. Fetch POST → api.php?route=auth/validar_codigo
 * 5. AuthApiController valida código, guarda cajero/rol en sesión
 * 6. Página recarga → header muestra menú según rol, footer no renderiza modal
 */
document.addEventListener('DOMContentLoaded', function () {
    const modalContainer = document.getElementById('modalContainer');

    // Si no existe el contenedor, el cajero ya está validado
    if (!modalContainer) return;

    // Inyectar HTML del modal
    modalContainer.innerHTML = `
        <div class="modal fade" id="codigoModal" tabindex="-1" aria-labelledby="codigoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <div class="modal-header" style="background: linear-gradient(135deg, #1e293b, #334155); border-radius: 12px 12px 0 0; border: none;">
                        <h5 class="modal-title text-white" id="codigoModalLabel">
                            <i class="fas fa-user-shield me-2"></i>Identificación
                        </h5>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted mb-3" style="font-size: 0.9rem;">Ingresa tu código de colaborador para continuar</p>
                        <form id="codigoForm">
                            <div class="mb-3">
                                <label for="codigoInput" class="form-label fw-semibold">Código</label>
                                <input type="password" 
                                       class="form-control form-control-lg" 
                                       id="codigoInput" 
                                       placeholder="••••" 
                                       autocomplete="off"
                                       required
                                       style="text-align: center; letter-spacing: 0.3em; font-size: 1.5rem;">
                            </div>
                            <div id="errorMensaje" class="alert alert-danger py-2" style="display: none; font-size: 0.85rem;">
                                Código incorrecto, inténtelo de nuevo.
                            </div>
                            <button type="submit" id="btnValidar" class="btn btn-primary w-100 btn-lg">
                                Validar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Inicializar modal Bootstrap 5 (sin poder cerrar)
    const modalEl = document.getElementById('codigoModal');
    const myModal = new bootstrap.Modal(modalEl, {
        backdrop: 'static',
        keyboard: false
    });
    myModal.show();

    // Auto-focus en el input cuando el modal se muestra
    modalEl.addEventListener('shown.bs.modal', function () {
        document.getElementById('codigoInput').focus();
    });

    // Manejar envío del formulario
    const form = document.getElementById('codigoForm');
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const codigo = document.getElementById('codigoInput').value.trim();
        const btnValidar = document.getElementById('btnValidar');
        const errorMsg = document.getElementById('errorMensaje');

        if (!codigo) return;

        // Deshabilitar botón mientras valida
        btnValidar.disabled = true;
        btnValidar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Validando...';
        errorMsg.style.display = 'none';

        // Construir URL de la API
        // modal_script.js se carga desde /public/js/, api.php está en /heiyubai/
        // Usamos ruta relativa desde la página actual (public/index.php)
        const apiUrl = '../api.php?route=auth/validar_codigo';

        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `codigo=${encodeURIComponent(codigo)}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // Éxito: cerrar modal y recargar
                myModal.hide();
                
                // Mostrar mensaje de bienvenida breve
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: data.message || 'Bienvenido',
                        showConfirmButton: false,
                        timer: 1200
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    location.reload();
                }
            } else {
                // Error: mostrar mensaje
                errorMsg.textContent = data.message || 'Código incorrecto, inténtelo de nuevo.';
                errorMsg.style.display = 'block';
                document.getElementById('codigoInput').value = '';
                document.getElementById('codigoInput').focus();

                // Restaurar botón
                btnValidar.disabled = false;
                btnValidar.innerHTML = 'Validar';
            }
        })
        .catch(err => {
            console.error('Error al validar código:', err);
            errorMsg.textContent = 'Error de conexión. Intente nuevamente.';
            errorMsg.style.display = 'block';

            // Restaurar botón
            btnValidar.disabled = false;
            btnValidar.innerHTML = 'Validar';
        });
    });
});