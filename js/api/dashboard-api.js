/**
 * dashboard-api.js
 * API Client para gestión de Dashboard
 *
 * @description
 * Maneja todas las operaciones relacionadas con datos y estadísticas del dashboard.
 * Compatible con el backend PHP api/dashboard.php
 *
 * @methods
 * - getData()           - Obtener datos generales del dashboard
 * - getDetails(category) - Obtener detalles de una categoría específica
 *
 * @author GitHub Copilot
 * @date 2025-10-25
 * @version 1.0.0
 */

import ApiClient from './api-client.js';

/**
 * Cliente API para operaciones de Dashboard
 * @extends ApiClient
 */
export class DashboardApi {
    /**
     * Constructor
     * Inicializa el cliente con el endpoint base de dashboard
     */
    constructor() {
        this.client = new ApiClient();
        this.endpoint = 'dashboard.php';
    }

    /**
     * Obtiene los datos generales del dashboard
     *
     * @returns {Promise<Object>} Objeto con estadísticas y datos del dashboard
     * @throws {Error} Si la petición falla
     *
     * @example
     * const dashboardData = await dashboardApi.getData();
     * console.log(dashboardData); // { totalPersonal: 45, personalPresente: 32, vehiculos: 18, ... }
     */
    async getData() {
        try {
            const result = await this.client.get(this.endpoint);
            if (!result.success) {
                throw new Error(result.error || 'Error al obtener datos del dashboard.');
            }
            return result.data || result;
        } catch (error) {
            console.error('Error al obtener datos del dashboard:', error);
            throw new Error(error.message || 'Error al obtener datos del dashboard.');
        }
    }

    /**
     * Obtiene detalles específicos del dashboard para una categoría
     *
     * @param {string} category - Categoría a obtener detalles (ej: 'personal-trabajando', 'vehiculos-fiscal-adentro', 'visitas-adentro')
     * @returns {Promise<Array>} Array con detalles de la categoría solicitada
     * @throws {Error} Si la petición falla
     *
     * @example
     * const personalDetails = await dashboardApi.getDetails('personal-trabajando');
     * console.log(personalDetails); // [{ id: 1, Nombres: 'Juan', ... }, ...]
     */
    async getDetails(category) {
        try {
            // El backend espera el parámetro 'details', no 'category'
            const result = await this.client.get(this.endpoint, {
                details: category
            });

            if (!result.success) {
                throw new Error(result.error || 'Error al obtener detalles del dashboard.');
            }

            // El backend devuelve directamente el array de datos
            return result.data || [];
        } catch (error) {
            console.error('Error al obtener detalles del dashboard:', error);
            throw new Error(error.message || 'Error al obtener detalles del dashboard.');
        }
    }
}

// Exportar una instancia singleton por defecto
export default new DashboardApi();
