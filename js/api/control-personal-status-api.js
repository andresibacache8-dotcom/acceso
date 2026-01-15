/**
 * control-personal-status-api.js
 * API para gestionar el estado de Control de Unidades en el servidor
 */

const controlPersonalStatusApi = {
    /**
     * Obtiene el estado actual de Control de Unidades desde el servidor
     * @returns {Promise<boolean>} true si está habilitado, false si está deshabilitado
     */
    async getStatus() {
        try {
            const response = await fetch('./api/control-personal-status.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include' // Incluir cookies de sesión
            });

            if (response.ok) {
                const data = await response.json();
                return data.enabled || false;
            }

            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        } catch (error) {
            console.error('Error al obtener estado de Control de Unidades:', error);
            throw error;
        }
    },

    /**
     * Actualiza el estado de Control de Unidades en el servidor
     * @param {boolean} enabled - true para habilitar, false para deshabilitar
     * @returns {Promise<boolean>} El nuevo estado
     */
    async setStatus(enabled) {
        try {
            const response = await fetch('./api/control-personal-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ enabled }),
                credentials: 'include' // Incluir cookies de sesión
            });

            if (response.ok) {
                const data = await response.json();
                return data.enabled || false;
            }

            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        } catch (error) {
            console.error('Error al actualizar estado de Control de Unidades:', error);
            throw error;
        }
    }
};

export default controlPersonalStatusApi;
