/**
 * mesas.controller.js - CORREGIDO
 * Controlador de mesas - coordina service y view
 * 
 * UBICACIÓN: public/js/modules/mesas/mesas.controller.js
 */

import { MesasService } from './mesas.service.js';
import { MesasView } from './mesas.view.js';
import { DashboardConfig } from '../../config/dashboard.config.js';

export class MesasController {
    constructor() {
        this.service = new MesasService();
        this.view = new MesasView();
        this.intervalId = null;
    }

    /**
     * Inicializar
     */
    async init() {
        console.log('✅ MesasController inicializado');
        
        // Renderizar inmediatamente
        await this.render();

        // Configurar actualización periódica
        this.startAutoRefresh();

        // Configurar event listeners
        this.setupEventListeners();
    }

    /**
     * Renderizar mesas
     */
    async render() {
        try {
            const mesas = await this.service.getMesas();

            // Solo renderizar si hay cambios
            if (this.service.hasChanges(mesas)) {
                this.view.render(mesas, this.service);
            }

        } catch (error) {
            console.error('Error renderizando mesas:', error);
            this.view.showError('Error cargando mesas');
        }
    }

    /**
     * Iniciar auto-refresh
     */
    startAutoRefresh() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }

        this.intervalId = setInterval(() => {
            this.render();
        }, DashboardConfig.refresh.mesas);
    }

    /**
     * Detener auto-refresh
     */
    stopAutoRefresh() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Delegación de eventos en el contenedor
        const container = this.view.container;
        if (!container) return;

        container.addEventListener('click', async (e) => {
            const button = e.target.closest('button');
            if (!button) return;

            // Extraer datos del botón (ojo con camelCase vs snake_case)
            const idPedido = parseInt(button.dataset.idPedido || button.dataset.id_pedido) || null;
            const estado = button.dataset.estado || '';
            const numeroMesa = parseInt(button.dataset.numeroMesa || button.dataset.numero_mesa);
            const pagado = parseInt(button.dataset.pagado || 0) === 1;
            
            await this.handleMesaClick(idPedido, estado, numeroMesa, pagado);
        });
    }

    /**
     * Manejar click en mesa
     * 
     * CORREGIDO: Usa window.procesarMesa de global-compat.js
     * que tiene todos los botones del modal (editar, pagar, cambiar estado)
     */
    async handleMesaClick(idPedido, estado, numeroMesa, pagado) {
        console.log('🖱️ Click mesa:', { idPedido, estado, numeroMesa, pagado });

        // Si no tiene pedido
        if (!idPedido) {
            alert(`La mesa ${numeroMesa} no tiene pedido asignado.`);
            return;
        }

        // ✅ USAR procesarMesa DE global-compat.js
        // Esta función tiene TODO: botones de cambiar estado, editar, pagar
        if (typeof window.procesarMesa === 'function') {
            try {
                await window.procesarMesa(idPedido, estado, numeroMesa, pagado);
            } catch (error) {
                console.error('Error en procesarMesa:', error);
                alert('Error al cargar el modal');
            }
        } else {
            console.error('❌ procesarMesa no encontrada. Verifica que global-compat.js esté cargado');
            alert('Error: función procesarMesa no disponible');
        }
    }

    /**
     * Destruir
     */
    destroy() {
        this.stopAutoRefresh();
    }
}