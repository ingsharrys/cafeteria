/**
 * formatters.js
 * Funciones de formateo de datos
 * 
 * UBICACIÓN: public/js/utils/formatters.js
 */

export const Formatters = {
    /**
     * Formatear moneda colombiana
     */
    currency(amount) {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0
        }).format(amount);
    },

    /**
     * Formatear número
     */
    number(num) {
        return new Intl.NumberFormat('es-CO').format(num);
    },

    /**
     * Formatear fecha
     */
    date(date) {
        return new Intl.DateTimeFormat('es-CO', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        }).format(new Date(date));
    },

    /**
     * Formatear fecha corta
     */
    dateShort(date) {
        return new Intl.DateTimeFormat('es-CO', {
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(date));
    },

    /**
     * Formatear teléfono
     */
    phone(phone) {
        if (!phone) return '';
        const cleaned = phone.toString().replace(/\D/g, '');
        const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/);
        if (match) {
            return `${match[1]} ${match[2]} ${match[3]}`;
        }
        return phone;
    },

    /**
     * Capitalizar primera letra
     */
    capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    },

    /**
     * Truncar texto
     */
    truncate(str, maxLength = 50) {
        if (!str) return '';
        if (str.length <= maxLength) return str;
        return str.substring(0, maxLength) + '...';
    }
};

// Hacer disponible globalmente
if (typeof window !== 'undefined') {
    window.Formatters = Formatters;
}