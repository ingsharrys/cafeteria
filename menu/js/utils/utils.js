/**
 * utils.js - Funciones Utilitarias
 * Ubicación: /menu/js/utils/utils.js
 * Funciones helper que se reutilizan en todo el código
 */

const Utils = {
    /**
     * Escapa caracteres especiales en selectores jQuery
     */
    escapeSelector: function(selector) {
        return selector.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\$&");
    },

    /**
     * Parsea un valor a entero de forma segura
     */
    parseInt: function(value) {
        const parsed = parseInt(value);
        return isNaN(parsed) ? 0 : parsed;
    },

    /**
     * Valida si un campo está vacío
     */
    isEmpty: function(value) {
        return !value || value.trim() === '';
    },

    /**
     * Log en consola con estilo
     */
    log: function(message, type = 'info') {
        const styles = {
            success: 'color: #28a745; font-weight: bold;',
            error: 'color: #dc3545; font-weight: bold;',
            warning: 'color: #ffc107; font-weight: bold;',
            info: 'color: #17a2b8; font-weight: bold;'
        };
        console.log(`%c${message}`, styles[type] || styles.info);
    },

    /**
     * Muestra una alerta amigable
     */
    alert: function(message, type = 'error') {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        alert(`${icons[type]} ${message}`);
    }
};