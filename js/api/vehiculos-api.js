/**
 * vehiculos-api.js
 * API Client para gestión de Vehículos
 * 
 * @description
 * Maneja todas las operaciones CRUD y consultas relacionadas con vehículos.
 * Compatible con el backend PHP api/vehiculos.php y api/vehiculo_historial.php
 * 
 * @methods
 * - getAll()                  - Obtener todos los vehículos
 * - getHistorial(vehiculoId)  - Obtener historial de cambios de un vehículo
 * - create(data)              - Crear nuevo vehículo
 * - update(data)              - Actualizar vehículo (requiere data.id)
 * - deleteVehiculo(id)        - Eliminar vehículo por ID
 * 
 * @author GitHub Copilot
 * @date 2025-10-25
 * @version 1.0.0
 */

import ApiClient from './api-client.js';

/**
 * Cliente API para operaciones de Vehículos
 * @extends ApiClient
 */
export class VehiculosApi {
    /**
     * Constructor
     * Inicializa el cliente con el endpoint base de vehículos
     */
    constructor() {
        this.client = new ApiClient();
        this.endpoint = 'vehiculos.php';
        this.historialEndpoint = 'vehiculo_historial.php';
    }

    /**
     * Obtiene la lista completa de vehículos
     * 
     * @returns {Promise<Array>} Array de objetos con datos de vehículos
     * @throws {Error} Si la petición falla
     * 
     * @example
     * const vehiculos = await vehiculosApi.getAll();
     * console.log(vehiculos); // [{ id: 1, patente: "AA1234", marca: "Toyota", ... }, ...]
     */
    async getAll() {
        try {
            const result = await this.client.get(this.endpoint);
            if (!result.success) {
                throw new Error(result.error || 'Error al obtener datos de vehículos.');
            }
            return result.data || result;
        } catch (error) {
            console.error('Error al obtener datos de vehículos:', error);
            throw new Error(error.message || 'Error al obtener datos de vehículos.');
        }
    }

    /**
     * Obtiene el historial completo de cambios de un vehículo
     * 
     * @param {number} vehiculoId - ID del vehículo
     * @returns {Promise<Object>} Objeto con historial de cambios
     * @throws {Error} Si la petición falla
     * 
     * @example
     * const historial = await vehiculosApi.getHistorial(123);
     * console.log(historial.historial); // [{ fecha: "2025-01-15", campo: "propietario", ... }, ...]
     */
    async getHistorial(vehiculoId) {
        try {
            const result = await this.client.get(this.historialEndpoint, { 
                vehiculo_id: vehiculoId 
            });
            
            if (!result.success) {
                throw new Error(result.error || 'Error al obtener historial del vehículo.');
            }
            
            return result.data || result;
        } catch (error) {
            console.error('Error al obtener historial del vehículo:', error);
            throw new Error(error.message || 'Error al obtener historial del vehículo.');
        }
    }

    /**
     * Crea un nuevo registro de vehículo
     * 
     * @param {Object} vehiculoData - Datos del vehículo a crear
     * @param {string} vehiculoData.patente - Patente del vehículo (formato chileno)
     * @param {string} vehiculoData.marca - Marca del vehículo
     * @param {string} vehiculoData.modelo - Modelo del vehículo
     * @param {string} vehiculoData.tipo - Tipo: 'particular', 'camioneta', 'bus', etc.
     * @param {string} vehiculoData.tipo_vehiculo - Tipo vehículo: 'LIVIANO', 'PESADO'
     * @param {number} vehiculoData.asociado_id - ID del propietario
     * @param {string} vehiculoData.asociado_tipo - Tipo: 'personal', 'visita', 'empresa_empleado'
     * @param {string} vehiculoData.seguro_vencimiento - Fecha vencimiento seguro (YYYY-MM-DD)
     * @param {string} vehiculoData.revision_tecnica_vencimiento - Fecha vencimiento revisión técnica
     * @param {string} vehiculoData.permiso_circulacion_vencimiento - Fecha vencimiento permiso circulación
     * @param {boolean} vehiculoData.acceso_permanente - true o false
     * @param {string|null} vehiculoData.fecha_expiracion - Fecha expiración (solo si acceso_permanente=false)
     * @param {string} vehiculoData.observaciones - Observaciones adicionales
     * @returns {Promise<Object>} Resultado de la operación
     * @throws {Error} Si la petición falla
     * 
     * @example
     * const resultado = await vehiculosApi.create({
     *     patente: "AA1234",
     *     marca: "Toyota",
     *     modelo: "Corolla",
     *     color: "Blanco",
     *     tipo: "particular",
     *     tipo_vehiculo: "LIVIANO",
     *     asociado_id: 15,
     *     asociado_tipo: "personal",
     *     seguro_vencimiento: "2025-12-31",
     *     revision_tecnica_vencimiento: "2025-12-31",
     *     permiso_circulacion_vencimiento: "2025-12-31",
     *     acceso_permanente: "1",
     *     fecha_expiracion: null,
     *     observaciones: ""
     * });
     */
    async create(vehiculoData) {
        try {
            const result = await this.client.post(this.endpoint, vehiculoData);
            
            if (!result.success) {
                throw new Error(result.error || 'Error al crear vehículo.');
            }
            
            return result.data || result;
        } catch (error) {
            console.error('Error al crear vehículo:', error);
            throw new Error(error.message || 'Error al crear vehículo.');
        }
    }

    /**
     * Actualiza un registro de vehículo existente
     * 
     * @param {Object} vehiculoData - Datos del vehículo a actualizar
     * @param {number} vehiculoData.id - ID del vehículo (OBLIGATORIO para UPDATE)
     * @param {string} vehiculoData.patente - Patente del vehículo
     * @param {string} vehiculoData.marca - Marca del vehículo
     * @param {string} vehiculoData.modelo - Modelo del vehículo
     * @param {string} vehiculoData.color - Color del vehículo
     * @param {string} vehiculoData.tipo - Tipo de vehículo
     * @param {string} vehiculoData.tipo_vehiculo - Tipo vehículo: 'LIVIANO', 'PESADO'
     * @param {number} vehiculoData.asociado_id - ID del propietario
     * @param {string} vehiculoData.asociado_tipo - Tipo de propietario
     * @param {string} vehiculoData.seguro_vencimiento - Fecha vencimiento seguro
     * @param {string} vehiculoData.revision_tecnica_vencimiento - Fecha vencimiento revisión técnica
     * @param {string} vehiculoData.permiso_circulacion_vencimiento - Fecha vencimiento permiso circulación
     * @param {string} vehiculoData.acceso_permanente - '0' o '1'
     * @param {string|null} vehiculoData.fecha_expiracion - Fecha expiración
     * @param {string} vehiculoData.observaciones - Observaciones
     * @returns {Promise<Object>} Resultado de la operación
     * @throws {Error} Si la petición falla o falta el ID
     * 
     * @example
     * const resultado = await vehiculosApi.update({
     *     id: 123,
     *     patente: "AA1234",
     *     marca: "Toyota",
     *     modelo: "Corolla 2024",
     *     color: "Blanco",
     *     // ... más campos actualizados
     * });
     */
    async update(vehiculoData) {
        try {
            // Validar que exista el ID
            if (!vehiculoData.id) {
                throw new Error('El campo "id" es obligatorio para actualizar vehículo.');
            }

            const result = await this.client.put(this.endpoint, vehiculoData);
            
            if (!result.success) {
                throw new Error(result.error || 'Error al actualizar vehículo.');
            }
            
            return result.data || result;
        } catch (error) {
            console.error('Error al actualizar vehículo:', error);
            throw new Error(error.message || 'Error al actualizar vehículo.');
        }
    }

    /**
     * Elimina un registro de vehículo
     * 
     * @param {number} id - ID del vehículo a eliminar
     * @returns {Promise<boolean>} true si se eliminó correctamente
     * @throws {Error} Si la petición falla
     * 
     * @example
     * await vehiculosApi.deleteVehiculo(123);
     * console.log('Vehículo eliminado correctamente');
     */
    async deleteVehiculo(id) {
        try {
            // El método delete del ApiClient acepta endpoint con query params
            const result = await this.client.delete(`${this.endpoint}?id=${id}`);
            
            // HTTP 204 retorna success: true, data: null
            if (result.success) {
                return { success: true };
            }
            
            throw new Error('Error al eliminar vehículo.');
        } catch (error) {
            console.error('Error al eliminar vehículo:', error);
            throw new Error(error.message || 'Error al eliminar vehículo.');
        }
    }
}

// Exportar una instancia singleton por defecto
export default new VehiculosApi();
