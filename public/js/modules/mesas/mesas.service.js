/**
 * mesas.service.js
 * Servicio de mesas - maneja la lógica de negocio
 * 
 * UBICACIÓN: public/js/modules/mesas/mesas.service.js
 */

import { ApiService } from '../../services/api.service.js';
import { DashboardConfig } from '../../config/dashboard.config.js';
import CacheService from '../../services/cache.service.js';
import { Helpers } from '../../utils/helpers.js';

export class MesasService {
    constructor() {
        this.url = DashboardConfig.api.mesas;
        this.lastHash = '';
    }

    /**
     * Obtener mesas
     */
    async getMesas() {
        try {
            const data = await ApiService.get(this.url);
            
            if (!data || !data.mesas) {
                throw new Error('Respuesta inválida del servidor');
            }

            return data.mesas;

        } catch (error) {
            console.error('Error obteniendo mesas:', error);
            return [];
        }
    }

    /**
     * Obtener mesas con cache
     */
    async getMesasWithCache() {
        return await CacheService.remember(
            'mesas',
            DashboardConfig.cache.lifetime,
            () => this.getMesas()
        );
    }

    /**
     * Verificar si hay cambios
     */
    hasChanges(mesas) {
        const hash = Helpers.hash(mesas, ['numero_mesa', 'estado', 'pagado']);
        const changed = hash !== this.lastHash;
        if (changed) {
            this.lastHash = hash;
        }
        return changed;
    }

    /**
     * Cambiar estado de mesa
     */
    async cambiarEstado(numeroPedido, nuevoEstado) {
        try {
            const response = await ApiService.post(
                DashboardConfig.api.cambiarEstadoMesa,
                {
                    numero_pedido: numeroPedido,
                    nuevo_estado: nuevoEstado
                }
            );

            return ApiService.isSuccess(response);

        } catch (error) {
            console.error('Error cambiando estado:', error);
            return false;
        }
    }

    /**
     * Liberar mesa
     */
    async liberarMesa(numeroMesa) {
        try {
            const response = await ApiService.post(
                DashboardConfig.api.liberarMesa,
                { numero_mesa: numeroMesa }
            );

            return ApiService.isSuccess(response);

        } catch (error) {
            console.error('Error liberando mesa:', error);
            return false;
        }
    }

    /**
     * Cambiar mesa
     */
    async cambiarMesa(numeroPedido, nuevaMesa, mesaActual) {
        try {
            const response = await ApiService.post(
                DashboardConfig.api.cambiarMesa,
                {
                    numero_pedido: numeroPedido,
                    nueva_mesa: nuevaMesa,
                    mesa_actual: mesaActual
                }
            );

            return ApiService.isSuccess(response);

        } catch (error) {
            console.error('Error cambiando mesa:', error);
            return false;
        }
    }

    /**
     * Obtener color según estado
     */
    getColorEstado(mesa) {
        if (!mesa.id_pedido && !mesa.estado) {
            return 'secondary';
        }

        if (mesa.estado === 'entregado' && mesa.pagado) {
            return 'secondary';
        }

        return DashboardConfig.coloresEstado[mesa.estado] || 
               DashboardConfig.coloresEstado.default;
    }

    mapEstadoLabel(estado) {
        switch (estado) {
            case 'preparacion':
                return 'preparacion';
            case 'espera':
                return 'espera';
            case 'entregado':
                return 'entregado';
            default:
                return estado || 'Sin estado';
        }
    }

    /**
     * Obtener label de mesa
     */
    getLabel(mesa) {
        if (!mesa.id_pedido && !mesa.estado) {
            return `Mesa ${mesa.numero_mesa}`;
        }

        const estado = this.mapEstadoLabel(mesa.estado || '');
        const pagado = mesa.pagado ? 'Pagado' : 'Por pagar';
        
        return `Mesa ${mesa.numero_mesa}<br>${estado}<br>${pagado}`;
    }
}