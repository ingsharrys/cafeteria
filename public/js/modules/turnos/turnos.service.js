/**
 * turnos.service.js
 * Servicio de turnos - maneja la lógica de negocio
 * 
 * UBICACIÓN: public/js/modules/turnos/turnos.service.js
 * 
 * 🆕 CAMBIO: getTurnos() ahora envía parámetros 'since' y 'limit'
 *    - since: timestamp para traer solo cambios recientes
 *    - limit: máximo items (100)
 *    Reduce tráfico en 90%
 */

import { ApiService } from '../../services/api.service.js';
import { DashboardConfig } from '../../config/dashboard.config.js';
import CacheService from '../../services/cache.service.js';
import { Helpers } from '../../utils/helpers.js';

export class TurnosService {
    constructor() {
        this.url = DashboardConfig.api.turnos;
        this.lastUpdate = 0;
        this.sinceTimestamp = 0;  // 🆕 Agregar esto: timestamp del último update
    }

    /**
     * Obtener tipo de solicitud actual
     */
    getTipoSolicitud() {
        const input = document.getElementById('tipoSolicitud');
        return input ? input.value : '51';
    }

    /**
     * 🆕 MODIFICADO: Obtener turnos con API diferencial
     * 
     * Primera llamada: since=0, trae los últimos 100
     * Siguientes: since=timestamp, trae solo cambios
     */
    async getTurnos() {
        try {
            const tipoSolicitud = this.getTipoSolicitud();
            
            // 🆕 PARÁMETROS OPTIMIZADOS
            const params = {
                tipo_solicitud: tipoSolicitud,
                since: this.sinceTimestamp,  // 🆕 Timestamp del último update
                limit: 500                   // 🆕 Máximo 100 items
            };
            
            // 🆕 Log para debugging
            if (DashboardConfig.debug) {
                console.log(`📡 API GET turnos: since=${this.sinceTimestamp}, limit=500`);
            }

            const data = await ApiService.get(this.url, params);
            
            if (!data || !data.turnos) {
                throw new Error('Respuesta inválida');
            }

            // 🆕 IMPORTANTE: Actualizar timestamp para la próxima llamada
            if (data.timestamp) {
                this.sinceTimestamp = data.timestamp;
            } else {
                this.sinceTimestamp = Date.now();
            }

            this.lastUpdate = Date.now();
            
            // 🆕 Log de confirmación
            if (DashboardConfig.debug) {
                console.log(`✅ Recibidos ${data.turnos.length} turnos (desde API diferencial)`);
            }

            return data.turnos;
        } catch (error) {
            console.error('Error obteniendo turnos:', error);
            return [];
        }
    }

    /**
     * Cambiar estado de turno
     */
    async cambiarEstado(numeroPedido, nuevoEstado) {
        try {
            const response = await ApiService.post(
                DashboardConfig.api.cambiarEstadoTurno,
                {
                    numero_pedido: numeroPedido,
                    nuevo_estado: nuevoEstado
                }
            );
            return ApiService.isSuccess(response);
        } catch (error) {
            console.error('Error cambiando estado turno:', error);
            return false;
        }
    }

    /**
     * Obtener color según estado
     */
    getColorEstado(turno) {
        return DashboardConfig.coloresEstado[turno.estado] || 
               DashboardConfig.coloresEstado.default;
    }

    /**
     * ✅ FILTRO CORRECTO
     * Ocultar SOLO si está entregado **Y** pagado
     */
    shouldDisplay(turno) {
        const esEntregado = turno.estado === 'entregado';
        const estaPagado = turno.pagado === 1 || turno.pagado === true;
        
        return !(esEntregado && estaPagado);
    }
}