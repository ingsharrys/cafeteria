/**
 * main.js - Archivo Principal
 * Ubicación: /menu/js/main.js
 * Orquesta la inicialización de todos los módulos
 */

$(document).ready(function() {
    
    // ═══════════════════════════════════════════════════════════════
    // 1. CARGAR DATOS DEL CLIENTE DESDE LOCALSTORAGE
    // ═══════════════════════════════════════════════════════════════
    const savedCustomerData = OrderService.loadCustomerData();
    OrderService.populateFormWithSavedData(savedCustomerData);
    
    // ═══════════════════════════════════════════════════════════════
    // 2. INICIALIZAR MÓDULOS ESPECIALIZADOS
    // ═══════════════════════════════════════════════════════════════
    // Inicializar Select2 para barrios (si Select2 está disponible)
    if (typeof BarrioModule !== 'undefined') {
        BarrioModule.init();
    }
    
    // ═══════════════════════════════════════════════════════════════
    // 3. INICIALIZAR MANEJADOR DE EVENTOS
    // ═══════════════════════════════════════════════════════════════
    EventManager.init();
    
    // ═══════════════════════════════════════════════════════════════
    // 4. ACTUALIZAR VISIBILIDAD DE BOTONES
    // ═══════════════════════════════════════════════════════════════
    UIManager.updateButtonVisibility();
    
    // ═══════════════════════════════════════════════════════════════
    // 5. LOG DE INICIALIZACIÓN
    // ═══════════════════════════════════════════════════════════════
    Utils.log('=== APLICACIÓN INICIALIZADA ===', 'success');
    Utils.log('✓ Utils cargado', 'success');
    Utils.log('✓ ProductService cargado', 'success');
    Utils.log('✓ OrderService cargado', 'success');
    Utils.log('✓ EventManager cargado', 'success');
    Utils.log('✓ UIManager cargado', 'success');
    Utils.log('✓ FormHandler cargado', 'success');
    if (typeof BarrioModule !== 'undefined') {
        Utils.log('✓ BarrioModule cargado', 'success');
    }
    Utils.log('=================================', 'success');
    
});