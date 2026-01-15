/**
 * api-client.js
 * Cliente HTTP base para todas las llamadas a la API
 * 
 * Proporciona una interfaz unificada para realizar peticiones HTTP
 * con manejo de errores, timeouts y headers consistentes.
 */

export class ApiClient {
    constructor(baseURL = 'api/') {
        this.baseURL = baseURL;
        this.timeout = 30000; // 30 segundos
        this.defaultHeaders = {
            'Content-Type': 'application/json'
        };
    }

    /**
     * Agrega parámetro nocache a una URL para evitar caché del navegador
     * @param {string} url - URL a la que agregar el parámetro
     * @returns {string} - URL con parámetro nocache
     */
    addNoCache(url) {
        const separator = url.includes('?') ? '&' : '?';
        return `${url}${separator}nocache=${Date.now()}`;
    }

    /**
     * Realiza una petición HTTP con timeout y manejo de errores
     * @param {string} url - Endpoint relativo al baseURL
     * @param {Object} options - Opciones de fetch
     * @returns {Promise<Object>} - Objeto con { success, data, error }
     */
    async request(url, options = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);

        try {
            const fullUrl = this.addNoCache(this.baseURL + url);
            
            const response = await fetch(fullUrl, {
                ...options,
                signal: controller.signal,
                headers: {
                    ...this.defaultHeaders,
                    ...options.headers
                }
            });

            clearTimeout(timeoutId);

            // HTTP 204 No Content no tiene body
            if (response.status === 204) {
                return { success: true, data: null, error: null };
            }

            const data = await response.json();

            if (!response.ok) {
                // Si hay un mensaje de error en el response, usarlo
                const errorMessage = data.message || `HTTP ${response.status}: ${response.statusText}`;
                throw new Error(errorMessage);
            }

            return { success: true, data, error: null };

        } catch (error) {
            clearTimeout(timeoutId);
            
            // Manejo específico de errores
            let errorMessage = error.message;
            
            if (error.name === 'AbortError') {
                errorMessage = 'La petición excedió el tiempo de espera';
            } else if (error instanceof TypeError) {
                errorMessage = 'Error de red o servidor no disponible';
            }
            
            console.error('API Request Error:', error);
            return { 
                success: false, 
                data: null, 
                error: errorMessage 
            };
        }
    }

    /**
     * Realiza una petición GET
     * @param {string} endpoint - Endpoint de la API
     * @param {Object} params - Parámetros de query string
     * @returns {Promise<Object>} - Respuesta de la API
     */
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'GET' });
    }

    /**
     * Realiza una petición POST
     * @param {string} endpoint - Endpoint de la API
     * @param {Object} data - Datos a enviar en el body
     * @returns {Promise<Object>} - Respuesta de la API
     */
    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    /**
     * Realiza una petición PUT
     * @param {string} endpoint - Endpoint de la API
     * @param {Object} data - Datos a actualizar
     * @returns {Promise<Object>} - Respuesta de la API
     */
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    /**
     * Realiza una petición DELETE
     * @param {string} endpoint - Endpoint de la API
     * @returns {Promise<Object>} - Respuesta de la API
     */
    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
}

// Exportación por defecto
export default ApiClient;
