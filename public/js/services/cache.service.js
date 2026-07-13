/**
 * cache.service.js
 * Servicio simple de cache en memoria
 * 
 * UBICACIÓN: public/js/services/cache.service.js
 */

export class CacheService {
    constructor() {
        this.cache = new Map();
        this.timestamps = new Map();
    }

    /**
     * Obtener del cache
     */
    get(key) {
        if (!this.has(key)) {
            return null;
        }
        return this.cache.get(key);
    }

    /**
     * Guardar en cache
     */
    set(key, value, ttl = 30) {
        this.cache.set(key, value);
        this.timestamps.set(key, {
            created: Date.now(),
            ttl: ttl * 1000 // convertir a ms
        });
    }

    /**
     * Verificar si existe y no ha expirado
     */
    has(key) {
        if (!this.cache.has(key)) {
            return false;
        }

        const timestamp = this.timestamps.get(key);
        if (!timestamp) {
            return false;
        }

        const age = Date.now() - timestamp.created;
        if (age > timestamp.ttl) {
            // Expirado, eliminar
            this.delete(key);
            return false;
        }

        return true;
    }

    /**
     * Eliminar del cache
     */
    delete(key) {
        this.cache.delete(key);
        this.timestamps.delete(key);
    }

    /**
     * Limpiar todo el cache
     */
    clear() {
        this.cache.clear();
        this.timestamps.clear();
    }

    /**
     * Obtener tamaño del cache
     */
    size() {
        return this.cache.size;
    }

    /**
     * Patrón remember: obtener o ejecutar callback
     */
    async remember(key, ttl, callback) {
        if (this.has(key)) {
            return this.get(key);
        }

        const value = await callback();
        this.set(key, value, ttl);
        return value;
    }
}

// Instancia singleton
const cacheInstance = new CacheService();

// Hacer disponible globalmente
if (typeof window !== 'undefined') {
    window.CacheService = cacheInstance;
}

export default cacheInstance;