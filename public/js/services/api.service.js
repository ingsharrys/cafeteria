/**
 * api.service.js
 * Servicio centralizado para todas las llamadas HTTP
 * 
 * UBICACIÓN: public/js/services/api.service.js
 */

export class ApiService {
    /**
     * Realizar petición GET
     */
    static async get(url, params = {}) {
        try {
            const queryString = new URLSearchParams(params).toString();
            
            // ✅ FIX: Si la URL ya tiene "?", usar "&" para los params adicionales
            let fullUrl = url;
            if (queryString) {
                const separator = url.includes('?') ? '&' : '?';
                fullUrl = `${url}${separator}${queryString}`;
            }

            const response = await fetch(fullUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            return data;

        } catch (error) {
            console.error('❌ Error en GET:', url, error);
            throw error;
        }
    }

    /**
     * Realizar petición POST
     */
    static async post(url, data = {}) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            return result;

        } catch (error) {
            console.error('❌ Error en POST:', url, error);
            throw error;
        }
    }

    /**
     * Realizar petición POST con FormData
     */
    static async postForm(url, formData) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            return result;

        } catch (error) {
            console.error('❌ Error en POST Form:', url, error);
            throw error;
        }
    }

    /**
     * Verificar si respuesta es exitosa
     */
    static isSuccess(response) {
        return response && response.success === true;
    }

    /**
     * Obtener mensaje de error
     */
    static getErrorMessage(response) {
        if (response && response.message) {
            return response.message;
        }
        if (response && response.error) {
            return response.error;
        }
        return 'Error desconocido';
    }
}

// Hacer disponible globalmente
if (typeof window !== 'undefined') {
    window.ApiService = ApiService;
}