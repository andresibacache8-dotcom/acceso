/**
 * empresas-api.js
 * API Client para gestión de Empresas y sus Empleados
 *
 * @description
 * Maneja todas las operaciones CRUD relacionadas con empresas y empleados de empresas.
 * Compatible con el backend PHP api/empresas.php y api/empresa_empleados.php
 *
 * @methods
 * - getAll()                  - Obtener todas las empresas
 * - create(data)              - Crear nueva empresa
 * - update(data)              - Actualizar empresa existente
 * - delete(id)                - Eliminar empresa por ID
 * - getEmpleados(empresaId)   - Obtener empleados de una empresa
 * - createEmpleado(data)      - Crear nuevo empleado de empresa
 * - updateEmpleado(data)      - Actualizar empleado de empresa
 * - deleteEmpleado(id)        - Eliminar empleado de empresa
 *
 * @author GitHub Copilot
 * @date 2025-10-25
 * @version 1.0.0
 */

import ApiClient from './api-client.js';

/**
 * Cliente API para operaciones de Empresas y Empleados
 * @extends ApiClient
 */
export class EmpresasApi {
    /**
     * Constructor
     * Inicializa el cliente con los endpoints base de empresas y empleados
     */
    constructor() {
        this.client = new ApiClient();
        this.endpoint = 'empresas.php';
        this.empleadosEndpoint = 'empresa_empleados.php';
    }

    // ========== OPERACIONES DE EMPRESAS ==========

    /**
     * Obtiene la lista completa de empresas
     *
     * @returns {Promise<Array>} Array de objetos con datos de empresas
     * @throws {Error} Si la petición falla
     *
     * @example
     * const empresas = await empresasApi.getAll();
     * console.log(empresas); // [{ id: 1, nombre: "Empresa A", rut: "76123456-5", ... }, ...]
     */
    async getAll() {
        try {
            const result = await this.client.get(this.endpoint);
            if (!result.success) {
                throw new Error(result.error || 'Error al obtener datos de empresas.');
            }
            return result.data || result;
        } catch (error) {
            console.error('Error al obtener datos de empresas:', error);
            throw new Error(error.message || 'Error al obtener datos de empresas.');
        }
    }

    /**
     * Crea una nueva empresa
     *
     * @param {Object} empresaData - Datos de la empresa a crear
     * @param {string} empresaData.nombre - Nombre de la empresa
     * @param {string} empresaData.unidad_poc - Unidad o departamento del POC (contacto principal)
     * @param {string} empresaData.poc_rut - RUT del contacto principal de la empresa
     * @param {string} empresaData.poc_nombre - Nombre del contacto principal
     * @param {string} empresaData.poc_anexo - Anexo telefónico del contacto principal (opcional)
     * @returns {Promise<Object>} Objeto con datos de la empresa creada
     * @throws {Error} Si la petición falla
     *
     * @example
     * const resultado = await empresasApi.create({
     *     nombre: "Empresa A Ltda.",
     *     unidad_poc: "Administración",
     *     poc_rut: "12345678-9",
     *     poc_nombre: "Juan González",
     *     poc_anexo: "123"
     * });
     */
    async create(empresaData) {
        try {
            const result = await this.client.post(this.endpoint, empresaData);

            if (!result.success) {
                throw new Error(result.error || 'Error al crear empresa.');
            }

            // Retornar datos extraídos (consistente con getAll y delete)
            return result.data || result;
        } catch (error) {
            console.error('Error al crear empresa:', error);
            throw new Error(error.message || 'Error al crear empresa.');
        }
    }

    /**
     * Actualiza una empresa existente
     *
     * @param {Object} empresaData - Datos de la empresa a actualizar
     * @param {number} empresaData.id - ID de la empresa (OBLIGATORIO para UPDATE)
     * @param {string} empresaData.nombre - Nombre de la empresa
     * @param {string} empresaData.unidad_poc - Unidad o departamento del POC
     * @param {string} empresaData.poc_rut - RUT del contacto principal
     * @param {string} empresaData.poc_nombre - Nombre del contacto principal
     * @param {string} empresaData.poc_anexo - Anexo telefónico del contacto principal
     * @returns {Promise<Object>} Objeto con datos de la empresa actualizada
     * @throws {Error} Si la petición falla o falta el ID
     *
     * @example
     * const resultado = await empresasApi.update({
     *     id: 1,
     *     nombre: "Empresa A Ltda. Actualizada",
     *     unidad_poc: "Administración",
     *     poc_rut: "12345678-9",
     *     poc_nombre: "Juan González",
     *     poc_anexo: "123"
     * });
     */
    async update(empresaData) {
        try {
            // Validar que exista el ID
            if (!empresaData.id) {
                throw new Error('El campo "id" es obligatorio para actualizar empresa.');
            }

            const result = await this.client.put(this.endpoint, empresaData);

            if (!result.success) {
                throw new Error(result.error || 'Error al actualizar empresa.');
            }

            // Retornar datos extraídos (consistente con otros métodos)
            return result.data || result;
        } catch (error) {
            console.error('Error al actualizar empresa:', error);
            throw new Error(error.message || 'Error al actualizar empresa.');
        }
    }

    /**
     * Elimina una empresa
     *
     * @param {number} id - ID de la empresa a eliminar
     * @returns {Promise<boolean>} true si se eliminó correctamente
     * @throws {Error} Si la petición falla
     *
     * @example
     * await empresasApi.delete(1);
     * console.log('Empresa eliminada correctamente');
     */
    async delete(id) {
        try {
            // El método delete del ApiClient acepta endpoint + params
            await this.client.delete(`${this.endpoint}?id=${id}`);
            return true;
        } catch (error) {
            console.error('Error al eliminar empresa:', error);
            throw new Error(error.message || 'Error al eliminar empresa.');
        }
    }

    // ========== OPERACIONES DE EMPLEADOS DE EMPRESA ==========

    /**
     * Obtiene la lista de empleados de una empresa
     *
     * @param {number} empresaId - ID de la empresa
     * @returns {Promise<Array>} Array de objetos con datos de empleados
     * @throws {Error} Si la petición falla
     *
     * @example
     * const empleados = await empresasApi.getEmpleados(1);
     * console.log(empleados); // [{ id: 1, nombre: "Juan", rut: "12345678-9", ... }, ...]
     */
    async getEmpleados(empresaId) {
        try {
            const result = await this.client.get(this.empleadosEndpoint, {
                empresa_id: empresaId
            });

            if (!result.success) {
                throw new Error(result.error || 'Error al obtener empleados de empresa.');
            }

            return result.data || result;
        } catch (error) {
            console.error('Error al obtener empleados de empresa:', error);
            throw new Error(error.message || 'Error al obtener empleados de empresa.');
        }
    }

    /**
     * Crea un nuevo empleado de empresa
     *
     * @param {Object} empleadoData - Datos del empleado a crear
     * @param {number} empleadoData.empresa_id - ID de la empresa
     * @param {string} empleadoData.nombre - Nombre completo
     * @param {string} empleadoData.paterno - Apellido paterno
     * @param {string} empleadoData.materno - Apellido materno (opcional)
     * @param {string} empleadoData.rut - RUT del empleado
     * @param {Date} empleadoData.fecha_expiracion - Fecha de expiración de acceso (opcional)
     * @param {boolean} empleadoData.acceso_permanente - Si el acceso es permanente (default: false)
     * @returns {Promise<Object>} Objeto con datos del empleado creado
     * @throws {Error} Si la petición falla
     *
     * @example
     * const resultado = await empresasApi.createEmpleado({
     *     empresa_id: 1,
     *     nombre: "Juan",
     *     paterno: "González",
     *     materno: "López",
     *     rut: "12345678-9",
     *     acceso_permanente: true
     * });
     */
    async createEmpleado(empleadoData) {
        try {
            const result = await this.client.post(this.empleadosEndpoint, empleadoData);

            if (!result.success) {
                throw new Error(result.error || 'Error al crear empleado de empresa.');
            }

            return result.data || result;
        } catch (error) {
            console.error('Error al crear empleado de empresa:', error);
            throw new Error(error.message || 'Error al crear empleado de empresa.');
        }
    }

    /**
     * Actualiza un empleado de empresa
     *
     * @param {Object} empleadoData - Datos del empleado a actualizar
     * @param {number} empleadoData.id - ID del empleado (OBLIGATORIO para UPDATE)
     * @param {number} empleadoData.empresa_id - ID de la empresa
     * @param {string} empleadoData.nombre - Nombre completo
     * @param {string} empleadoData.paterno - Apellido paterno
     * @param {string} empleadoData.materno - Apellido materno (opcional)
     * @param {string} empleadoData.rut - RUT del empleado
     * @param {Date} empleadoData.fecha_expiracion - Fecha de expiración de acceso (opcional)
     * @param {boolean} empleadoData.acceso_permanente - Si el acceso es permanente
     * @returns {Promise<Object>} Objeto con datos del empleado actualizado
     * @throws {Error} Si la petición falla o falta el ID
     *
     * @example
     * const resultado = await empresasApi.updateEmpleado({
     *     id: 5,
     *     empresa_id: 1,
     *     nombre: "Juan Carlos",
     *     paterno: "González",
     *     materno: "López",
     *     rut: "12345678-9",
     *     acceso_permanente: true
     * });
     */
    async updateEmpleado(empleadoData) {
        try {
            // Validar que exista el ID
            if (!empleadoData.id) {
                throw new Error('El campo "id" es obligatorio para actualizar empleado.');
            }

            const result = await this.client.put(this.empleadosEndpoint, empleadoData);

            if (!result.success) {
                throw new Error(result.error || 'Error al actualizar empleado de empresa.');
            }

            return result.data || result;
        } catch (error) {
            console.error('Error al actualizar empleado de empresa:', error);
            throw new Error(error.message || 'Error al actualizar empleado de empresa.');
        }
    }

    /**
     * Elimina un empleado de empresa
     *
     * @param {number} id - ID del empleado a eliminar
     * @returns {Promise<boolean>} true si se eliminó correctamente
     * @throws {Error} Si la petición falla
     *
     * @example
     * await empresasApi.deleteEmpleado(5);
     * console.log('Empleado eliminado correctamente');
     */
    async deleteEmpleado(id) {
        try {
            // El método delete del ApiClient acepta endpoint + params
            await this.client.delete(`${this.empleadosEndpoint}?id=${id}`);
            return true;
        } catch (error) {
            console.error('Error al eliminar empleado de empresa:', error);
            throw new Error(error.message || 'Error al eliminar empleado de empresa.');
        }
    }
}

// Exportar una instancia singleton por defecto
export default new EmpresasApi();
