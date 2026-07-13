/**
 * helpers.js
 * Funciones auxiliares reutilizables
 * 
 * UBICACIÓN: public/js/utils/helpers.js
 */

export const Helpers = {
    /**
     * Pad número con ceros
     */
    pad2(n) {
        return n.toString().padStart(2, '0');
    },

    /**
     * Calcular tiempo transcurrido
     */
    ago(date) {
        const seconds = Math.floor((Date.now() - date.getTime()) / 1000);
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        return `${this.pad2(hours)}:${this.pad2(minutes)}:${this.pad2(secs)}`;
    },

    /**
     * Generar hash simple
     */
    hash(arr, keys) {
        if (!Array.isArray(arr)) return '';
        return arr.map(obj => 
            keys.map(k => obj[k]).join('-')
        ).join('|');
    },

    /**
     * Debounce
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle
     */
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Esperar X milisegundos
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    },

    /**
     * Escapar HTML
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
};

// Hacer disponible globalmente
if (typeof window !== 'undefined') {
    window.Helpers = Helpers;
}