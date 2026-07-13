/**
 * storage.service.js
 * Wrapper para localStorage con manejo de errores
 * 
 * UBICACIÓN: public/js/services/storage.service.js
 */

export class StorageService {
    /**
     * Guardar en localStorage
     */
    static set(key, value) {
        try {
            const serialized = JSON.stringify(value);
            localStorage.setItem(key, serialized);
            return true;
        } catch (error) {
            console.error('Error guardando en localStorage:', error);
            return false;
        }
    }

    /**
     * Obtener de localStorage
     */
    static get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            if (item === null) {
                return defaultValue;
            }
            return JSON.parse(item);
        } catch (error) {
            console.error('Error leyendo de localStorage:', error);
            return defaultValue;
        }
    }

    /**
     * Eliminar de localStorage
     */
    static remove(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error('Error eliminando de localStorage:', error);
            return false;
        }
    }

    /**
     * Limpiar todo localStorage
     */
    static clear() {
        try {
            localStorage.clear();
            return true;
        } catch (error) {
            console.error('Error limpiando localStorage:', error);
            return false;
        }
    }

    /**
     * Verificar si existe una clave
     */
    static has(key) {
        return localStorage.getItem(key) !== null;
    }
}

// Hacer disponible globalmente
if (typeof window !== 'undefined') {
    window.StorageService = StorageService;
}