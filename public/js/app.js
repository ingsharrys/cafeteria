/**
 * app.js
 * Entry point principal de la aplicación
 * Importa y coordina todos los módulos
 * 
 * UBICACIÓN: public/js/app.js
 */

// ============================================
// IMPORTS
// ============================================

// Config
import { DashboardConfig } from './config/dashboard.config.js';

// Services
import { ApiService } from './services/api.service.js';
import CacheService from './services/cache.service.js';
import { StorageService } from './services/storage.service.js';

// Utils
import { Helpers } from './utils/helpers.js';
import { Formatters } from './utils/formatters.js';
import { DOM } from './utils/dom.js';

// Controllers
import { MesasController } from './modules/mesas/mesas.controller.js';
import { TurnosController } from './modules/turnos/turnos.controller.js';

// Services adicionales
import ProductosService from './modules/productos/productos.service.js';
import ModalController from './modules/modal/modal.controller.js';
import PrintService from './lib/print.service.js';

// ============================================
// CLASE PRINCIPAL
// ============================================

export class DashboardApp {
    constructor() {
        this.initialized = false;
        this.controllers = {};
    }

    /**
     * Inicializar aplicación
     */
    async init() {
        if (this.initialized) {
            console.warn('App ya inicializada');
            return;
        }

        try {
            console.log('🚀 Inicializando DashboardApp...');

            // Verificar dependencias
            this.checkDependencies();

            // Determinar tipo de página
            const pageType = this.detectPageType();
            console.log(`📄 Tipo de página: ${pageType}`);

            // Inicializar modal
            ModalController.init();

            // Inicializar según tipo de página
            switch (pageType) {
                case 'mesas':
                    await this.initMesas();
                    break;
                    
                case 'turnos':
                    await this.initTurnos();
                    break;
                    
                case 'dashboard':
                    await this.initDashboard();
                    break;
                    
                default:
                    console.log('Página no requiere inicialización especial');
            }

            this.initialized = true;
            console.log('✅ DashboardApp inicializado correctamente');

        } catch (error) {
            console.error('❌ Error inicializando app:', error);
            this.showError('Error al inicializar la aplicación');
        }
    }

    /**
     * Detectar tipo de página
     */
    detectPageType() {
        const tipoSolicitud = DOM.$('#tipoSolicitud');
        
        if (!tipoSolicitud) {
            return 'unknown';
        }

        const tipo = tipoSolicitud.value;
        
        // Mesa = 51
        if (tipo === '51') {
            const mesasContainer = DOM.$('#mesas-container');
            if (mesasContainer) {
                return 'dashboard'; // Tiene mesas y turnos
            }
            return 'turnos';
        }

        // Domicilio = 50, Recoger = 53
        return 'turnos';
    }

    /**
     * Inicializar solo mesas
     */
    async initMesas() {
        console.log('Inicializando módulo de mesas...');
        
        this.controllers.mesas = new MesasController();
        await this.controllers.mesas.init();
    }

    /**
     * Inicializar solo turnos
     */
    async initTurnos() {
        console.log('Inicializando módulo de turnos...');
        
        this.controllers.turnos = new TurnosController();
        await this.controllers.turnos.init();
    }

    /**
     * Inicializar dashboard completo (mesas + turnos)
     */
    async initDashboard() {
        console.log('Inicializando dashboard completo...');
        
        // Mesas
        this.controllers.mesas = new MesasController();
        await this.controllers.mesas.init();

        // Turnos
        this.controllers.turnos = new TurnosController();
        await this.controllers.turnos.init();
    }

    /**
     * Verificar dependencias
     */
    checkDependencies() {
        const required = ['DashboardConfig', 'ApiService', 'bootstrap'];
        const missing = [];

        if (typeof DashboardConfig === 'undefined') missing.push('DashboardConfig');
        if (typeof ApiService === 'undefined') missing.push('ApiService');
        if (typeof bootstrap === 'undefined') missing.push('Bootstrap');

        if (missing.length > 0) {
            throw new Error(`Dependencias faltantes: ${missing.join(', ')}`);
        }

        // Advertencias opcionales
        if (typeof qz === 'undefined') {
            console.warn('⚠️ QZ Tray no disponible - impresión deshabilitada');
        }

        if (typeof jQuery === 'undefined') {
            console.warn('⚠️ jQuery no disponible - algunas funciones legacy pueden fallar');
        }
    }

    /**
     * Mostrar error
     */
    showError(message) {
        alert(message);
    }

    /**
     * Destruir aplicación
     */
    destroy() {
        console.log('Destruyendo aplicación...');

        // Destruir controllers
        Object.values(this.controllers).forEach(controller => {
            if (controller && typeof controller.destroy === 'function') {
                controller.destroy();
            }
        });

        this.controllers = {};
        this.initialized = false;

        console.log('✅ Aplicación destruida');
    }

    /**
     * Obtener controller
     */
    getController(name) {
        return this.controllers[name] || null;
    }

    /**
     * Log de debug
     */
    debug(...args) {
        if (DashboardConfig.debug) {
            console.log('[DEBUG]', ...args);
        }
    }
}

// ============================================
// INICIALIZACIÓN AUTOMÁTICA
// ============================================

// Crear instancia global
const app = new DashboardApp();

// Hacer disponible globalmente
if (typeof window !== 'undefined') {
    window.DashboardApp = app;
    
    // Exponer servicios útiles
    window.ApiService = ApiService;
    window.CacheService = CacheService;
    window.StorageService = StorageService;
    window.Helpers = Helpers;
    window.Formatters = Formatters;
    window.DOM = DOM;
}

// Inicializar cuando DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        app.init();
    });
} else {
    app.init();
}

// Export para módulos
export default app;

// ============================================
// LOG FINAL
// ============================================

console.log('✅ app.js cargado');