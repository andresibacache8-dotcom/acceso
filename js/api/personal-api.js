/**
 * personal-api.js
 * API Client para gestión de Personal Militar/Civil
 * 
 * @description
 * Maneja todas las operaciones CRUD y búsquedas relacionadas con personal.
 * Compatible con el backend PHP api/personal.php y api/buscar_personal.php
 * 
 * @methods
 * - getAll()              - Obtener todo el personal
 * - getInsideOnly()       - Personal que está "dentro" del recinto
 * - findByRut(rut)        - Buscar por RUT exacto (sin DV)
 * - search(query, tipo)   - Búsqueda avanzada por nombre/RUT + tipo
 * - create(data)          - Crear nuevo registro de personal
 * - update(data)          - Actualizar personal (requiere data.id)
 * - delete(id)            - Eliminar personal por ID
 * 
 * @author GitHub Copilot
 * @date 2025-10-25
 * @version 1.0.0
 */

import ApiClient from './api-client.js';

/**
 * Cliente API para operaciones de Personal
 * @extends ApiClient
 */
export class PersonalApi {
    /**
     * Constructor
     * Inicializa el cliente con el endpoint base de personal
     */
    constructor() {
        this.client = new ApiClient();
        this.endpoint = 'personal.php';
        this.searchEndpoint = 'buscar_personal.php';
    }

    /**
     * Obtiene la lista completa de personal
     * 
     * @returns {Promise<Array>} Array de objetos con datos del personal
     * @throws {Error} Si la petición falla
     * 
     * @example
     * const personal = await personalApi.getAll();
     * console.log(personal); // [{ id: 1, NrRut: "12345678", Nombres: "Juan", ... }, ...]
     */
    async getAll() {
        try {
            const result = await this.client.get(this.endpoint);
            if (!result.success) {
                throw new Error(result.error || 'Error al obtener datos de personal.');
            }
            return result.data || result;
        } catch (error) {
            console.error('Error al obtener datos de personal:', error);
            throw new Error(error.message || 'Error al obtener datos de personal.');
        }
    }

    /**
     * Obtiene solo el personal que está "dentro" del recinto
     * 
     * @returns {Promise<Array>} Array de personal que está adentro
     * @throws {Error} Si la petición falla
     * 
     * @example
     * const personalAdentro = await personalApi.getInsideOnly();
     */
    async getInsideOnly() {
        try {
            const result = await this.client.get(this.endpoint, { status: 'inside' });
            if (!result.success) {
                throw new Error(result.error || 'Error al obtener personal que está dentro.');
            }
            return result.data || result;
        } catch (error) {
            console.error('Error al obtener datos de personal que está dentro:', error);
            throw new Error(error.message || 'Error al obtener datos de personal que está dentro.');
        }
    }

    /**
     * Busca personal por RUT exacto (sin dígito verificador)
     * 
     * @param {string} rut - RUT sin puntos ni guión ni DV (ej: "12345678")
     * @returns {Promise<Object|null>} Objeto con datos del personal o null si no existe
     * @throws {Error} Si la petición falla (excepto 404)
     * 
     * @example
     * const persona = await personalApi.findByRut("12345678");
     * if (persona) {
     *     console.log(persona.Nombres); // "Juan"
     * }
     */
    async findByRut(rut) {
        try {
            const result = await this.client.get(this.endpoint, { rut: rut });
            
            // Si no se encuentra, retornar null sin error
            if (!result || !result.success) {
                return null;
            }
            
            const data = result.data || result;
            
            // Si el resultado es un array vacío, retornar null
            if (Array.isArray(data) && data.length === 0) {
                return null;
            }
            
            // Si es un objeto vacío, retornar null
            if (typeof data === 'object' && !Array.isArray(data) && Object.keys(data).length === 0) {
                return null;
            }
            
            // Si es un array con resultados, retornar el primer elemento
            if (Array.isArray(data)) {
                return data[0];
            }
            
            return data;
        } catch (error) {
            // Si es 404 o "no encontrado", retornar null sin lanzar error
            if (error.message.includes('404') || 
                error.message.toLowerCase().includes('no encontrado')) {
                return null;
            }
            
            console.error('Error al buscar personal por RUT:', error);
            throw new Error(error.message || 'Error al buscar personal por RUT.');
        }
    }

    /**
     * Búsqueda avanzada de personal por nombre o RUT + tipo
     * 
     * @param {string} query - Término de búsqueda (nombre parcial o RUT)
     * @param {string} tipo - Tipo de personal: 'FUNCIONARIO', 'RESIDENTE', 'VISITA', etc.
     * @returns {Promise<Array>} Array de resultados que coinciden
     * @throws {Error} Si la petición falla
     * 
     * @example
     * const resultados = await personalApi.search("Juan", "FUNCIONARIO");
     * console.log(resultados); // [{ NrRut: "12345678", Nombres: "Juan", ... }, ...]
     */
    async search(query, tipo) {
        try {
            const result = await this.client.get(this.searchEndpoint, {
                query: query,
                tipo: tipo
            });
            
            if (!result.success) {
                throw new Error(result.error || 'Error al buscar personal.');
            }
            
            return result.data || result;
        } catch (error) {
            console.error('Error al buscar personal:', error);
            throw new Error(error.message || 'Error al buscar personal.');
        }
    }

    /**
     * Crea un nuevo registro de personal
     * 
     * @param {Object} personalData - Datos del personal a crear
     * @param {string} personalData.NrRut - RUT sin DV (7-8 dígitos)
     * @param {string} personalData.Nombres - Nombres
     * @param {string} personalData.Paterno - Apellido paterno
     * @param {string} personalData.Materno - Apellido materno
     * @param {string} personalData.Grado - Grado militar/rango
     * @param {string} personalData.Cargo - Cargo o función
     * @param {string} personalData.Unidad - Unidad militar
     * @param {string} personalData.Compania - Compañía
     * @param {string} personalData.Departamento - Departamento
     * @param {string} personalData.Tipo - Tipo: 'FUNCIONARIO', 'RESIDENTE', etc.
     * @param {string} personalData.Telefono - Teléfono de contacto
     * @param {string} personalData.Email - Email
     * @param {string} personalData.Direccion - Dirección
     * @param {string} personalData.Foto - URL o base64 de la foto
     * @returns {Promise<Object>} Resultado de la operación
     * @throws {Error} Si la petición falla
     * 
     * @example
     * const resultado = await personalApi.create({
     *     NrRut: "12345678",
     *     Nombres: "Juan Carlos",
     *     Paterno: "González",
     *     Materno: "López",
     *     Grado: "Sargento",
     *     Tipo: "FUNCIONARIO",
     *     // ... más campos
     * });
     */
    async create(personalData) {
        try {
            const result = await this.client.post(this.endpoint, personalData);
            
            if (!result.success) {
                throw new Error(result.error || 'Error al crear personal.');
            }
            
            return result;
        } catch (error) {
            console.error('Error al crear personal:', error);
            throw new Error(error.message || 'Error al crear personal.');
        }
    }

    /**
     * Actualiza un registro de personal existente
     * 
     * @param {Object} personalData - Datos del personal a actualizar
     * @param {number} personalData.id - ID del personal (OBLIGATORIO para UPDATE)
     * @param {string} personalData.NrRut - RUT sin DV
     * @param {string} personalData.Nombres - Nombres
     * @param {string} personalData.Paterno - Apellido paterno
     * @param {string} personalData.Materno - Apellido materno
     * @param {string} personalData.Grado - Grado militar/rango
     * @param {string} personalData.Cargo - Cargo o función
     * @param {string} personalData.Unidad - Unidad militar
     * @param {string} personalData.Compania - Compañía
     * @param {string} personalData.Departamento - Departamento
     * @param {string} personalData.Tipo - Tipo de personal
     * @param {string} personalData.Telefono - Teléfono
     * @param {string} personalData.Email - Email
     * @param {string} personalData.Direccion - Dirección
     * @param {string} personalData.Foto - URL o base64 de la foto
     * @returns {Promise<Object>} Resultado de la operación
     * @throws {Error} Si la petición falla o falta el ID
     * 
     * @example
     * const resultado = await personalApi.update({
     *     id: 123,
     *     NrRut: "12345678",
     *     Nombres: "Juan Carlos",
     *     // ... más campos
     * });
     */
    async update(personalData) {
        try {
            // Validar que exista el ID
            if (!personalData.id) {
                throw new Error('El campo "id" es obligatorio para actualizar personal.');
            }

            const result = await this.client.put(this.endpoint, personalData);
            
            if (!result.success) {
                throw new Error(result.error || 'Error al actualizar personal.');
            }
            
            return result;
        } catch (error) {
            console.error('Error al actualizar personal:', error);
            throw new Error(error.message || 'Error al actualizar personal.');
        }
    }

    /**
     * Elimina un registro de personal
     *
     * @param {number} id - ID del personal a eliminar
     * @returns {Promise<boolean>} true si se eliminó correctamente
     * @throws {Error} Si la petición falla
     *
     * @example
     * await personalApi.delete(123);
     * console.log('Personal eliminado correctamente');
     */
    async delete(id) {
        try {
            // El método delete del ApiClient acepta endpoint + params
            await this.client.delete(`${this.endpoint}?id=${id}`);
            return true;
        } catch (error) {
            console.error('Error al eliminar personal:', error);
            throw new Error(error.message || 'Error al eliminar personal.');
        }
    }

    /**
     * Importa personal masivamente desde archivo Excel/CSV
     *
     * @param {Array<Object>} personalArray - Array de objetos con datos de personal
     * @param {string} personalArray[].Nombres - Nombres (REQUERIDO)
     * @param {string} personalArray[].Paterno - Apellido paterno (REQUERIDO)
     * @param {string} personalArray[].Materno - Apellido materno (opcional)
     * @param {string} personalArray[].NrRut - RUT del personal (REQUERIDO)
     * @param {string} personalArray[].Grado - Grado/Rango (opcional)
     * @param {string} personalArray[].Unidad - Unidad (opcional)
     * @param {number} personalArray[].Estado - Estado activo: 0 o 1 (predeterminado: 1)
     * @param {number} personalArray[].es_residente - Es residente: 0 o 1 (predeterminado: 0)
     *
     * @returns {Promise<Object>} Resultado con estadísticas de la importación
     *   - success: Array de registros importados exitosamente
     *   - errors: Array de errores ocurridos
     *   - total: Total de registros procesados
     *   - processed: Cantidad procesada
     *   - created: Nuevos registros creados
     *   - updated: Registros actualizados
     * @throws {Error} Si la petición falla
     *
     * @example
     * const datos = [
     *     { Nombres: "JUAN", Paterno: "GONZALEZ", Materno: "LOPEZ", NrRut: "12345678-9" },
     *     { Nombres: "MARIA", Paterno: "RODRIGUEZ", NrRut: "87654321-4" }
     * ];
     * const resultado = await personalApi.importMasivo(datos);
     * console.log(resultado); // { success: [...], errors: [...], ... }
     */
    async importMasivo(personalArray) {
        try {
            const payload = {
                personal: personalArray
            };

            // Usar fetch directo para mayor control
            const response = await fetch(`api/${this.endpoint}?action=import`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || `Error ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            return result;
        } catch (error) {
            console.error('Error al importar personal masivamente:', error);
            throw new Error(error.message || 'Error al importar personal masivamente.');
        }
    }
}

// Exportar una instancia singleton por defecto
export default new PersonalApi();
