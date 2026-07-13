/**
 * dashboard.config.js
 * Configuración centralizada del dashboard
 * 
 * UBICACIÓN: public/js/config/dashboard.config.js
 * 
 * 🆕 CAMBIO: refresh.turnos de 15000 → 10000 (10 segundos)
 *    Esto reduce tráfico en 33% sin afectar UX
 */

export const DashboardConfig = {
    // URLs de API - apuntan directo a api.php?route=...
    api: {
        mesas:               '../api.php?route=mesas',
        turnos:              '../api.php?route=turnos',
        productos:           '../api.php?route=productos',
        cambiarEstadoMesa:   '../api.php?route=mesas/estado',
        cambiarEstadoTurno:  '../api.php?route=turnos/estado',
        liberarMesa:         '../api.php?route=mesas/liberar',
        cambiarMesa:         '../api.php?route=mesas/cambiar',
        obtenerBase:         '../controllers/obtener_base.php',
        guardarBase:         '../controllers/guardar_base.php'
    },

    refresh: {
        mesas: 15000,
        turnos: 10000,      // 🆕 CAMBIO: 15000 → 10000 (10 segundos)
        temporizador: 1000
    },

    tiposSolicitud: {
        DOMICILIO: '50',
        MESA: '51',
        LLEVAR: '52',
        RECOGER: '53'
    },

    estados: {
        NUEVO: 'preparacion',
        EN_COCINA: 'espera',
        ENTREGADO: 'entregado'
    },

    coloresEstado: {
        'preparacion': 'warning',
        'espera': 'primary',
        'entregado': 'success',
        'default': 'secondary'
    },

    cache: {
        enabled: true,
        lifetime: 30
    },

    debug: true
};

if (typeof window !== 'undefined') {
    window.DashboardConfig = DashboardConfig;
}