/**
 * visitas-api.js
 * API Client para gestión de Visitas
 * 
 * @description
 * Maneja todas las operaciones CRUD y consultas relacionadas con visitas.
 * Compatible con el backend PHP api/visitas.php
 * 
 * @methods
 * - getAll()                      - Obtener todas las visitas
 * - create(data)                  - Crear nueva visita
 * - update(data)                  - Actualizar visita (requiere data.id)
 * - deleteVisita(id)              - Eliminar visita por ID
 * - toggleBlacklist(id, status)   - Agregar/quitar de lista negra
 * 
 * @author GitHub Copilot
 * @date 2025-10-25
 * @version 1.0.0
 */

import ApiClient from './api-client.js';

/**
 * Cliente API para operaciones de Visitas
 * @extends ApiClient
 */
export class VisitasApi {
    /**
     * Constructor
     * Inicializa el cliente con el endpoint base de visitas
     */
    constructor() {
        this.client = new ApiClient();
        this.endpoint = 'visitas.php';
    }

    /**
     * Obtiene la lista completa de visitas
     * 
     * @returns {Promise<Array>} Array de objetos con datos de visitas
     * @throws {Error} Si la petición falla
     * 
     * @example
     * const visitas = await visitasApi.getAll();
     * console.log(visitas); // [{ id: 1, rut: "12345678-9", nombre: "Juan", ... }, ...]
     */
    async getAll() {
        try {
            const result = await this.client.get(this.endpoint);
            if (!result.success) {
                throw new Error(result.error || 'Error al obtener datos de visitas.');
            }
            return result.data || result;
        } catch (error) {
            console.error('Error al obtener datos de visitas:', error);
            throw new Error(error.message || 'Error al obtener datos de visitas.');
        }
    }

    /**
     * Crea un nuevo registro de visita
     * 
     * @param {Object} visitaData - Datos de la visita a crear
     * @param {string} visitaData.rut - RUT de la visita (con o sin DV)
     * @param {string} visitaData.nombre - Nombre de la visita
     * @param {string} visitaData.paterno - Apellido paterno
     * @param {string} visitaData.materno - Apellido materno
     * @param {string} visitaData.movil - Teléfono móvil
     * @param {string} visitaData.tipo - Tipo: 'Visita', 'Familiar', etc.
     * @param {string} visitaData.fecha_inicio - Fecha inicio autorización (YYYY-MM-DD)
     * @param {string} visitaData.fecha_expiracion - Fecha fin autorización (YYYY-MM-DD)
     * @param {boolean|number} visitaData.acceso_permanente - Acceso permanente (true/false o 1/0)
     * @param {boolean|number} visitaData.en_lista_negra - En lista negra (true/false o 1/0)
     * @param {number} [visitaData.poc_personal_id] - ID del personal de contacto (POC)
     * @param {string} [visitaData.poc_unidad] - Unidad del POC
     * @param {string} [visitaData.poc_anexo] - Anexo del POC
     * @param {number} [visitaData.familiar_de_personal_id] - ID del familiar
     * @param {string} [visitaData.familiar_unidad] - Unidad del familiar
     * @param {string} [visitaData.familiar_anexo] - Anexo del familiar
     * @returns {Promise<Object>} Resultado de la operación
     * @throws {Error} Si la petición falla
     * 
     * @example
     * const resultado = await visitasApi.create({
     *     rut: "12345678-9",
     *     nombre: "Juan",
     *     paterno: "Pérez",
     *     materno: "González",
     *     movil: "+56912345678",
     *     tipo: "Visita",
     *     fecha_inicio: "2025-01-01",
     *     fecha_expiracion: "2025-12-31",
     *     acceso_permanente: 0,
     *     en_lista_negra: 0,
     *     poc_personal_id: 15,
     *     poc_unidad: "Unidad 1",
     *     poc_anexo: "1234"
     * });
     */
    async create(visitaData) {
        try {
            const result = await this.client.post(this.endpoint, visitaData);
            
            if (!result.success) {
                throw new Error(result.error || 'Error al crear visita.');
            }
            
            return result.data || result;
        } catch (error) {
            console.error('Error al crear visita:', error);
            throw new Error(error.message || 'Error al crear visita.');
        }
    }

    /**
     * Actualiza un registro de visita existente
     * 
     * @param {Object} visitaData - Datos de la visita a actualizar
     * @param {number} visitaData.id - ID de la visita (OBLIGATORIO para UPDATE)
     * @param {string} visitaData.rut - RUT de la visita
     * @param {string} visitaData.nombre - Nombre
     * @param {string} visitaData.paterno - Apellido paterno
     * @param {string} visitaData.materno - Apellido materno
     * @param {string} visitaData.movil - Teléfono móvil
     * @param {string} visitaData.tipo - Tipo de visita
     * @param {string} visitaData.fecha_inicio - Fecha inicio
     * @param {string} visitaData.fecha_expiracion - Fecha fin
     * @param {boolean|number} visitaData.acceso_permanente - Acceso permanente
     * @param {boolean|number} visitaData.en_lista_negra - En lista negra
     * @param {number} [visitaData.poc_personal_id] - ID del POC
     * @param {string} [visitaData.poc_unidad] - Unidad del POC
     * @param {string} [visitaData.poc_anexo] - Anexo del POC
     * @param {number} [visitaData.familiar_de_personal_id] - ID del familiar
     * @param {string} [visitaData.familiar_unidad] - Unidad del familiar
     * @param {string} [visitaData.familiar_anexo] - Anexo del familiar
     * @returns {Promise<Object>} Resultado de la operación
     * @throws {Error} Si la petición falla o falta el ID
     * 
     * @example
     * const resultado = await visitasApi.update({
     *     id: 123,
     *     rut: "12345678-9",
     *     nombre: "Juan Carlos",
     *     paterno: "Pérez",
     *     // ... más campos actualizados
     * });
     */
    async update(visitaData) {
        try {
            // Validar que exista el ID
            if (!visitaData.id) {
                throw new Error('El campo "id" es obligatorio para actualizar visita.');
            }

            const result = await this.client.put(this.endpoint, visitaData);
            
            if (!result.success) {
                throw new Error(result.error || 'Error al actualizar visita.');
            }
            
            return result.data || result;
        } catch (error) {
            console.error('Error al actualizar visita:', error);
            throw new Error(error.message || 'Error al actualizar visita.');
        }
    }

    /**
     * Elimina un registro de visita
     * 
     * @param {number} id - ID de la visita a eliminar
     * @returns {Promise<boolean>} true si se eliminó correctamente
     * @throws {Error} Si la petición falla
     * 
     * @example
     * await visitasApi.deleteVisita(123);
     * console.log('Visita eliminada correctamente');
     */
    async deleteVisita(id) {
        try {
            // El método delete del ApiClient acepta endpoint con query params
            const result = await this.client.delete(`${this.endpoint}?id=${id}`);
            
            // HTTP 204 retorna success: true, data: null
            if (result.success) {
                return { success: true };
            }
            
            throw new Error('Error al eliminar visita.');
        } catch (error) {
            console.error('Error al eliminar visita:', error);
            throw new Error(error.message || 'Error al eliminar visita.');
        }
    }

    /**
     * Agrega o quita una visita de la lista negra
     * 
     * @param {number} id - ID de la visita
     * @param {number|boolean} enListaNegra - 1/true para agregar, 0/false para quitar
     * @returns {Promise<Object>} Resultado de la operación
     * @throws {Error} Si la petición falla
     * 
     * @example
     * // Agregar a lista negra
     * await visitasApi.toggleBlacklist(123, 1);
     * 
     * // Quitar de lista negra
     * await visitasApi.toggleBlacklist(123, 0);
     */
    async toggleBlacklist(id, enListaNegra) {
        try {
            const result = await this.client.put(
                `${this.endpoint}?action=toggle_blacklist&id=${id}`,
                { en_lista_negra: enListaNegra }
            );
            
            if (!result.success) {
                throw new Error(result.error || 'Error al actualizar estado de lista negra.');
            }
            
            return result.data || result;
        } catch (error) {
            console.error('Error al actualizar estado de lista negra:', error);
            throw new Error(error.message || 'Error al actualizar estado de lista negra.');
        }
    }
}

// Exportar una instancia singleton por defecto
export default new VisitasApi();
