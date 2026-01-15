/**
 * portico-api.js
 * API Client para gestión del Pórtico de Acceso
 *
 * @description
 * Maneja todas las operaciones de registro de acceso a través del pórtico.
 * Compatible con el backend PHP api/portico.php
 *
 * @methods
 * - logAccess(id)  - Registrar acceso (entrada/salida) en el pórtico
 *
 * @author GitHub Copilot
 * @date 2025-10-25
 * @version 1.0.0
 */

import ApiClient from './api-client.js';

/**
 * Cliente API para operaciones del Pórtico
 * @extends ApiClient
 */
export class PorricoApi {
    /**
     * Constructor
     * Inicializa el cliente con el endpoint base del pórtico
     */
    constructor() {
        this.client = new ApiClient();
        this.endpoint = 'portico.php';
    }

    /**
     * Registra un acceso (entrada o salida) en el pórtico
     *
     * Busca la entidad (personal, vehículo, visita, empleado de empresa, personal en comisión)
     * por su ID y registra automáticamente si es entrada o salida según el último registro.
     *
     * @param {string|number} id - ID a escanear (RUT personal, patente vehículo, RUT visita, etc.)
     * @returns {Promise<Object>} Objeto con los datos del acceso registrado
     * @throws {Error} Si la petición falla o el ID no es encontrado
     *
     * @example
     * try {
     *     const result = await porticoApi.logAccess("12345678");
     *     console.log(result);
     *     // {
     *     //   id: 5,
     *     //   type: "personal",
     *     //   action: "entrada",
     *     //   name: "Sargento Juan González López",
     *     //   photoUrl: "...",
     *     //   message: "Acceso 'entrada' para Sargento Juan González López registrado correctamente."
     *     // }
     * } catch (error) {
     *     console.error("Error al registrar acceso:", error.message);
     * }
     */
    async logAccess(id) {
        try {
            // Agregar timestamp para evitar caché
            const timestamp = new Date().getTime();

            const result = await this.client.post(
                `${this.endpoint}?nocache=${timestamp}`,
                { id: String(id).trim() }
            );

            if (!result.success) {
                throw new Error(result.error || result.message || 'Error al registrar acceso en pórtico.');
            }

            // El servidor puede devolver directamente el objeto sin el wrapper success
            return result.data || result;
        } catch (error) {
            console.error('Error al registrar acceso en pórtico:', error);
            throw new Error(error.message || 'Error al registrar acceso en pórtico.');
        }
    }
}

// Exportar una instancia singleton por defecto
export default new PorricoApi();
