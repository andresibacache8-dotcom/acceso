/**
 * horas-extra-api.js
 * API Client para gestión de Horas Extra
 *
 * @description
 * Maneja todas las operaciones CRUD relacionadas con horas extra del personal.
 * Compatible con el backend PHP api/horas_extra.php
 *
 * @methods
 * - getAll()      - Obtener todas las horas extra
 * - create(data)  - Crear nuevo registro de horas extra
 * - delete(id)    - Eliminar horas extra por ID
 *
 * @author GitHub Copilot
 * @date 2025-10-25
 * @version 1.0.0
 */

import ApiClient from './api-client.js';

/**
 * Cliente API para operaciones de Horas Extra
 * @extends ApiClient
 */
export class HorasExtraApi {
    /**
     * Constructor
     * Inicializa el cliente con el endpoint base de horas extra
     */
    constructor() {
        this.client = new ApiClient();
        this.endpoint = 'horas_extra.php';
    }

    /**
     * Obtiene la lista completa de horas extra
     *
     * @returns {Promise<Array>} Array de objetos con datos de horas extra
     * @throws {Error} Si la petición falla
     *
     * @example
     * const horasExtra = await horasExtraApi.getAll();
     * console.log(horasExtra); // [{ id: 1, personal_id: 5, horas: 2, fecha: "2025-10-25", ... }, ...]
     */
    async getAll() {
        try {
            const result = await this.client.get(this.endpoint);
            if (!result.success) {
                throw new Error(result.error || 'Error al obtener datos de horas extra.');
            }
            return result.data || result;
        } catch (error) {
            console.error('Error al obtener datos de horas extra:', error);
            throw new Error(error.message || 'Error al obtener datos de horas extra.');
        }
    }

    /**
     * Crea uno o múltiples registros de horas extra
     *
     * @param {Object} horasData - Datos de las horas extra a crear
     * @param {Array} horasData.personal - Array de objetos con rut y nombre del personal
     * @param {string} horasData.personal[].rut - RUT del personal (ej: "12345678-9")
     * @param {string} horasData.personal[].nombre - Nombre completo del personal
     * @param {string} horasData.fecha_hora_termino - Fecha y hora de término (YYYY-MM-DD HH:MM:SS)
     * @param {string} horasData.motivo - Motivo de las horas extra (ej: "Evento especial", "Emergencia")
     * @param {string} [horasData.motivo_detalle] - Detalles adicionales del motivo (opcional)
     * @param {string} horasData.autorizado_por_rut - RUT de quien autoriza las horas extra
     * @param {string} horasData.autorizado_por_nombre - Nombre de quien autoriza las horas extra
     * @returns {Promise<Object>} Objeto con información de la creación { message, count, success }
     * @throws {Error} Si la petición falla
     *
     * @example
     * const resultado = await horasExtraApi.create({
     *     personal: [
     *         { rut: "12345678-9", nombre: "Juan Pérez" },
     *         { rut: "98765432-1", nombre: "María García" }
     *     ],
     *     fecha_hora_termino: "2025-10-25 22:30:00",
     *     motivo: "Evento especial",
     *     motivo_detalle: "Montaje de infraestructura",
     *     autorizado_por_rut: "11111111-1",
     *     autorizado_por_nombre: "Carlos López"
     * });
     */
    async create(horasData) {
        try {
            const result = await this.client.post(this.endpoint, horasData);

            if (!result.success) {
                throw new Error(result.error || 'Error al crear horas extra.');
            }

            // Retornar datos extraídos (consistente con getAll y delete)
            return result.data || result;
        } catch (error) {
            console.error('Error al crear horas extra:', error);
            throw new Error(error.message || 'Error al crear horas extra.');
        }
    }

    /**
     * Elimina un registro de horas extra
     *
     * @param {number} id - ID de las horas extra a eliminar
     * @returns {Promise<boolean>} true si se eliminó correctamente
     * @throws {Error} Si la petición falla
     *
     * @example
     * await horasExtraApi.delete(123);
     * console.log('Horas extra eliminadas correctamente');
     */
    async delete(id) {
        try {
            // El método delete del ApiClient acepta endpoint + params
            await this.client.delete(`${this.endpoint}?id=${id}`);
            return true;
        } catch (error) {
            console.error('Error al eliminar horas extra:', error);
            throw new Error(error.message || 'Error al eliminar horas extra.');
        }
    }
}

// Exportar una instancia singleton por defecto
export default new HorasExtraApi();
