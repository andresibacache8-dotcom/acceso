/**
 * reportes-api.js
 * API para gestionar la obtención de reportes
 */

const reportesApi = {
    /**
     * Obtiene los datos de un reporte según los filtros
     * @param {Object} filters - Objeto con los filtros del reporte
     * @returns {Promise<Object>} Datos del reporte
     */
    async getReport(filters) {
        try {
            // Construir query string con los filtros
            const params = new URLSearchParams(filters);

            const response = await fetch(`./api/reportes.php?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            // Verificar si hay error en la respuesta
            if (data.error) {
                throw new Error(data.error || 'Error desconocido');
            }

            return data;
        } catch (error) {
            console.error('Error al obtener reporte:', error);
            throw error;
        }
    }
};

export default reportesApi;
