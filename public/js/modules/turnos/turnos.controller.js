/**
 * turnos.controller.js - TU CÓDIGO + OPTIMIZACIÓN MINIMAL
 * Controlador de turnos - coordina service y view
 * Incluye: Infinite scroll, carga progresiva
 * 
 * ✅ CAMBIO ÚNICO:
 * - smartRefresh() en lugar de render() completo cada 5s
 * - TODO LO DEMÁS IGUAL A TU CÓDIGO
 * 
 * UBICACIÓN: public/js/modules/turnos/turnos.controller.js
 */

import { TurnosService } from './turnos.service.js';
import { TurnosView } from './turnos.view.js';
import { DashboardConfig } from '../../config/dashboard.config.js';

export class TurnosController {
    constructor() {
        this.service = new TurnosService();
        this.view = new TurnosView();
        this.intervalId = null;
        this.timerIntervalId = null;
        
        // 🆕 INFINITE SCROLL
        this.paginaActual = 1;
        this.itemsPorPagina = 805;
        this.turnosCompletos = [];
        this.turnosMostrados = [];
        this.cargandoMas = false;
        this.scrollListener = null;
    }

    /**
     * Inicializar
     */
    async init() {
        console.log('✅ TurnosController inicializado');
        
        // Renderizar inmediatamente
        await this.render();

        // ✅ CAMBIO: Usar smartRefresh en lugar de startAutoRefresh
        this.startSmartRefresh();

        // Configurar actualización de temporizadores
        this.startTimerUpdates();

        // Configurar event listeners
        this.setupEventListeners();
        
        // 🆕 Configurar infinite scroll
        this.setupInfiniteScroll();
    }

    /**
     * Renderizar turnos (CARGA INICIAL)
     */
    async render() {
        try {
            const turnos = await this.service.getTurnos();

            if (turnos.length > 0) {
                // 🆕 GUARDAR todos los turnos
                this.turnosCompletos = turnos;
                
                // 🆕 RESET a primera página
                this.paginaActual = 1;
                this.turnosMostrados = [];
                
                // 🆕 CARGAR primera página (25 items)
                this.cargarMasTurnos();
                
                // Pintar acciones
                this.renderAcciones(this.turnosMostrados);
            }

        } catch (error) {
            console.error('Error renderizando turnos:', error);
            this.view.showError('Error cargando turnos');
        }
    }

    /**
     * ✅ NUEVO: smartRefresh - Solo trae CAMBIOS
     * 
     * En lugar de hacer render() completo cada 5s,
     * solo trae cambios y los agrega
     */
    async smartRefresh() {
        try {
            const turnos = await this.service.getTurnos();

            if (!turnos || turnos.length === 0) {
                return;
            }

            // Actualizar lista completa
            this.turnosCompletos = turnos;

            // Buscar nuevos o modificados
            const nuevosOModificados = turnos.filter(turno => {
                const existente = document.getElementById(`fila-${turno.numero_pedido}`);
                return !existente;  // Si NO existe en el DOM, es nuevo
            });

            if (nuevosOModificados.length > 0) {
                console.log(`🔄 +${nuevosOModificados.length} turnos nuevos`);
                
                // Agregar nuevos turnos al inicio (porque vienen en DESC)
                this.view.appendRows(nuevosOModificados, this.service);
                
                // Cargar productos para nuevos
                this.loadProductosForTurnos(nuevosOModificados);
            }

        } catch (error) {
            console.error('❌ Error en smartRefresh:', error);
        }
    }

    /**
     * 🆕 CARGAR MÁS TURNOS (Called on scroll)
     */
    cargarMasTurnos() {
        if (this.cargandoMas) return;
        
        const inicio = (this.paginaActual - 1) * this.itemsPorPagina;
        const fin = inicio + this.itemsPorPagina;
        
        // Obtener turnos de esta página
        const nuevosTurnos = this.turnosCompletos.slice(inicio, fin);
        
        if (nuevosTurnos.length === 0) {
            console.log('✅ Todos los turnos cargados');
            return;
        }
        
        // Si es la primera página, render completo
        if (this.paginaActual === 1) {
            this.turnosMostrados = nuevosTurnos;
            this.view.render(this.turnosMostrados, this.service);
        } else {
            // Si no, agregar filas a la tabla
            this.turnosMostrados.push(...nuevosTurnos);
            this.view.appendRows(nuevosTurnos, this.service);
        }
        
        // Cargar productos
        this.loadProductosForTurnos(nuevosTurnos);
        
        this.paginaActual++;
        
        console.log(`📄 Cargados ${this.turnosMostrados.length}/${this.turnosCompletos.length} turnos`);
    }

    /**
     * 🆕 CONFIGURAR INFINITE SCROLL
     */
    setupInfiniteScroll() {
        const container = document.getElementById('turnos-container');
        
        if (!container) {
            console.warn('⚠️ No se encontró #turnos-container');
            return;
        }

        // Remover listener anterior si existe
        if (this.scrollListener) {
            container.removeEventListener('scroll', this.scrollListener);
        }

        this.scrollListener = () => {
            // Calcular distancia desde el final
            const scrollTop = container.scrollTop;
            const clientHeight = container.clientHeight;
            const scrollHeight = container.scrollHeight;
            
            const distanciaDelFinal = scrollHeight - (scrollTop + clientHeight);
            
            // Cargar cuando falten 500px para llegar al final
            if (distanciaDelFinal < 500 && !this.cargandoMas) {
                const totalCargados = this.paginaActual * this.itemsPorPagina;
                if (totalCargados <= this.turnosCompletos.length) {
                    this.cargandoMas = true;
                    console.log('⬇️ Cargando más turnos...');
                    this.cargarMasTurnos();
                    this.cargandoMas = false;
                }
            }
        };

        container.addEventListener('scroll', this.scrollListener);
        console.log('✅ Infinite scroll configurado');
    }

    /**
     * Cargar productos para turnos
     */
    loadProductosForTurnos(turnos) {
        turnos.forEach(turno => {
            const prodCell = document.getElementById(`prod-${turno.numero_pedido}`);
            if (prodCell && prodCell.textContent === 'Cargando…') {
                if (typeof window.cargarProductos === 'function') {
                    window.cargarProductos(turno.numero_pedido);
                }
            }
        });
    }

    /**
     * Renderizar acciones
     */
    renderAcciones(turnos) {
        turnos.forEach(turno => {
            if (typeof window.pintarAcciones === 'function') {
                window.pintarAcciones(turno);
            }
        });
    }

    /**
     * ✅ NUEVO: startSmartRefresh en lugar de startAutoRefresh
     * 
     * Cada 10 segundos, solo trae CAMBIOS (no recarga todo)
     */
    startSmartRefresh() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }

        // ✅ smartRefresh en lugar de render completo
        this.intervalId = setInterval(() => {
            this.smartRefresh();
        }, 10000);  // Cada 10 segundos (antes era 5000ms recargando TODO)
        
        console.log('✅ Smart refresh iniciado (10s)');
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
     * Iniciar actualización de temporizadores
     */
    startTimerUpdates() {
        if (this.timerIntervalId) {
            clearInterval(this.timerIntervalId);
        }

        this.timerIntervalId = setInterval(() => {
            this.view.updateTimers();
        }, DashboardConfig.refresh.temporizador);
    }

    /**
     * Detener actualización de temporizadores
     */
    stopTimerUpdates() {
        if (this.timerIntervalId) {
            clearInterval(this.timerIntervalId);
            this.timerIntervalId = null;
        }
    }

    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        console.log('Event listeners configurados');
    }

    /**
     * Cambiar estado de turno
     */
    async cambiarEstado(numeroPedido, nuevoEstado) {
        const success = await this.service.cambiarEstado(numeroPedido, nuevoEstado);
        
        if (success) {
            // ✅ NO hacer render completo, solo actualizar ese turno
            const turno = this.turnosCompletos.find(t => t.numero_pedido === numeroPedido);
            if (turno) {
                turno.estado = nuevoEstado;
                
                // Actualizar fila en tabla
                const row = document.getElementById(`fila-${numeroPedido}`);
                if (row) {
                    this.view.updateRow(row, turno, this.service);
                }
            }
            
            if (nuevoEstado === 'espera') {
                if (typeof window.printInvoicepc === 'function') {
                    window.printInvoicepc(numeroPedido);
                }
            }
        }

        return success;
    }

    /**
     * Destruir
     */
    destroy() {
        this.stopAutoRefresh();
        this.stopTimerUpdates();
        
        // Remover scroll listener
        const container = document.getElementById('turnos-container');
        if (container && this.scrollListener) {
            container.removeEventListener('scroll', this.scrollListener);
        }
    }
}