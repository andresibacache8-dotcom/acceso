/**
 * comision-api.js
 * API Client para gestión de Comisiones
 *
 * @description
 * Maneja todas las operaciones CRUD relacionadas con comisiones de personal.
 * Compatible con el backend PHP api/comision.php
 *
 * @methods
 * - getAll()      - Obtener todas las comisiones
 * - create(data)  - Crear nueva comisión
 * - update(data)  - Actualizar comisión existente
 * - delete(id)    - Eliminar comisión por ID
 *
 * @author GitHub Copilot
 * @date 2025-10-25
 * @version 1.0.0
 */

import ApiClient from './api-client.js';

/**
 * Cliente API para operaciones de Comisiones
 * @extends ApiClient
 */
export class ComisionApi {
    /**
     * Constructor
     * Inicializa el cliente con el endpoint base de comisiones
     */
    constructor() {
        this.client = new ApiClient();
        this.endpoint = 'comision.php';
    }

    /**
     * Obtiene la lista completa de comisiones
     *
     * @returns {Promise<Array>} Array de objetos con datos de comisiones
     * @throws {Error} Si la petición falla
     *
     * @example
     * const comisiones = await comisionApi.getAll();
     * console.log(comisiones); // [{ id: 1, personal_id: 5, descripcion: "Reunión", fecha: "2025-10-25", ... }, ...]
     */
    async getAll() {
        try {
            const result = await this.client.get(this.endpoint);
            if (!result.success) {
                throw new Error(result.error || 'Error al obtener datos de comisiones.');
            }
            return result.data || result;
        } catch (error) {
            console.error('Error al obtener datos de comisiones:', error);
            throw new Error(error.message || 'Error al obtener datos de comisiones.');
        }
    }

    /**
     * Crea una nueva comisión
     *
     * @param {Object} comisionData - Datos de la comisión a crear
     * @param {number} comisionData.personal_id - ID del personal asignado
     * @param {string} comisionData.descripcion - Descripción de la comisión
     * @param {string} comisionData.fecha_inicio - Fecha de inicio (YYYY-MM-DD)
     * @param {string} comisionData.fecha_termino - Fecha de término (YYYY-MM-DD)
     * @param {string} comisionData.lugar - Lugar de la comisión
     * @param {string} comisionData.estado - Estado: 'pendiente', 'activa', 'completada'
     * @param {string} comisionData.observaciones - Observaciones adicionales
     * @returns {Promise<Object>} Resultado de la operación
     * @throws {Error} Si la petición falla
     *
     * @example
     * const resultado = await comisionApi.create({
     *     personal_id: 5,
     *     descripcion: "Reunión directiva",
     *     fecha_inicio: "2025-10-25",
     *     fecha_termino: "2025-10-25",
     *     lugar: "Sala de conferencias",
     *     estado: "activa",
     *     observaciones: "Reunión importante"
     * });
     */
    async create(comisionData) {
        try {
            const result = await this.client.post(this.endpoint, comisionData);

            if (!result.success) {
                throw new Error(result.error || 'Error al crear comisión.');
            }

            return result;
        } catch (error) {
            console.error('Error al crear comisión:', error);
            throw new Error(error.message || 'Error al crear comisión.');
        }
    }

    /**
     * Actualiza una comisión existente
     *
     * @param {Object} comisionData - Datos de la comisión a actualizar
     * @param {number} comisionData.id - ID de la comisión (OBLIGATORIO para UPDATE)
     * @param {number} comisionData.personal_id - ID del personal asignado
     * @param {string} comisionData.descripcion - Descripción de la comisión
     * @param {string} comisionData.fecha_inicio - Fecha de inicio
     * @param {string} comisionData.fecha_termino - Fecha de término
     * @param {string} comisionData.lugar - Lugar de la comisión
     * @param {string} comisionData.estado - Estado de la comisión
     * @param {string} comisionData.observaciones - Observaciones
     * @returns {Promise<Object>} Resultado de la operación
     * @throws {Error} Si la petición falla o falta el ID
     *
     * @example
     * const resultado = await comisionApi.update({
     *     id: 123,
     *     personal_id: 5,
     *     descripcion: "Reunión directiva actualizada",
     *     estado: "completada",
     *     // ... más campos
     * });
     */
    async update(comisionData) {
        try {
            // Validar que exista el ID
            if (!comisionData.id) {
                throw new Error('El campo "id" es obligatorio para actualizar comisión.');
            }

            const result = await this.client.put(this.endpoint, comisionData);

            if (!result.success) {
                throw new Error(result.error || 'Error al actualizar comisión.');
            }

            return result;
        } catch (error) {
            console.error('Error al actualizar comisión:', error);
            throw new Error(error.message || 'Error al actualizar comisión.');
        }
    }

    /**
     * Elimina una comisión
     *
     * @param {number} id - ID de la comisión a eliminar
     * @returns {Promise<boolean>} true si se eliminó correctamente
     * @throws {Error} Si la petición falla
     *
     * @example
     * await comisionApi.delete(123);
     * console.log('Comisión eliminada correctamente');
     */
    async delete(id) {
        try {
            // El método delete del ApiClient acepta endpoint + params
            await this.client.delete(`${this.endpoint}?id=${id}`);
            return true;
        } catch (error) {
            console.error('Error al eliminar comisión:', error);
            throw new Error(error.message || 'Error al eliminar comisión.');
        }
    }
}

// Exportar una instancia singleton por defecto
export default new ComisionApi();
