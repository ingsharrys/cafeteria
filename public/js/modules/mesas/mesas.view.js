/**
 * mesas.view.js
 * Vista de mesas - maneja el renderizado
 * 
 * UBICACIÓN: public/js/modules/mesas/mesas.view.js
 */

import { DOM } from '../../utils/dom.js';

export class MesasView {
    constructor(containerId = 'mesas-container') {
        // ✅ CORREGIDO: Asegurar que el selector incluya #
        this.container = document.getElementById(containerId);
        
        if (!this.container) {
            console.error('❌ Contenedor de mesas no encontrado:', containerId);
        } else {
            console.log('✅ MesasView: Contenedor encontrado');
        }
    }

    /**
     * Renderizar mesas
     */
    render(mesas, service) {
        if (!this.container) {
            console.error('❌ No se puede renderizar: contenedor no existe');
            return;
        }

        console.log('🎨 Renderizando', mesas.length, 'mesas');
        
        // Limpiar contenedor
        this.container.innerHTML = '';

        // Ordenar por número de mesa
        const mesasOrdenadas = [...mesas].sort((a, b) => 
            a.numero_mesa - b.numero_mesa
        );

        mesasOrdenadas.forEach(mesa => {
            const button = this.createMesaButton(mesa, service);
            this.container.appendChild(button);
        });
        
        console.log('✅ Renderizado completado:', this.container.children.length, 'botones');
    }

    /**
     * Crear botón de mesa
     */
    createMesaButton(mesa, service) {
        const col = document.createElement('div');
        col.className = 'col-md-4 mb-2';

        const color = service.getColorEstado(mesa);
        const label = service.getLabel(mesa);

        const button = document.createElement('button');
        button.className = `btn btn-${color} w-100`;
        button.innerHTML = label;
        
        // Datos del botón
        button.dataset.idPedido = mesa.id_pedido || '';
        button.dataset.estado = mesa.estado || '';
        button.dataset.numeroMesa = mesa.numero_mesa;
        button.dataset.pagado = mesa.pagado || 0;

        col.appendChild(button);
        return col;
    }

    /**
     * Mostrar loading
     */
    showLoading() {
        if (this.container) {
            this.container.innerHTML = `
                <div class="col-12 text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                </div>
            `;
        }
    }

    /**
     * Mostrar error
     */
    showError(message) {
        if (this.container) {
            this.container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">${message}</div>
                </div>
            `;
        }
    }
}