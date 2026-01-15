/**
 * access-logs-api.js
 * API Client para gestión de Logs de Acceso
 * 
 * @description
 * Maneja todas las operaciones relacionadas con registros de acceso (entrada/salida).
 * Compatible con los backends PHP:
 * - api/log_access.php (logs manuales y consultas)
 * - api/portico.php (acceso inteligente por pórtico)
 * - api/log_clarified_access.php (accesos con aclaración)
 * 
 * @methods
 * - getByType(targetType)               - Obtener logs de un tipo específico
 * - getAllTypes()                       - Obtener logs de todos los tipos (Promise.all)
 * - logManual(targetId, targetType, puntoAcceso) - Registrar acceso manual
 * - logPortico(id)                      - Registrar acceso por pórtico (inteligente)
 * - logClarified(data)                  - Registrar acceso con aclaración
 * 
 * @author GitHub Copilot
 * @date 2025-10-25
 * @version 1.0.0
 */

import ApiClient from './api-client.js';

/**
 * Cliente API para operaciones de Logs de Acceso
 * @extends ApiClient
 */
export class AccessLogsApi {
    /**
     * Constructor
     * Inicializa el cliente con los endpoints de logs
     */
    constructor() {
        this.client = new ApiClient();
        this.endpoint = 'log_access.php';
        this.porticoEndpoint = 'portico.php';
        this.clarifiedEndpoint = 'log_clarified_access.php';
        
        /**
         * Tipos de target válidos para logs
         * @type {string[]}
         */
        this.validTypes = [
            'personal',
            'vehiculo',
            'visita',
            'personal_comision',
            'empresa_empleado'
        ];
    }

    /**
     * Obtiene los logs de acceso de un tipo específico
     * 
     * @param {string} targetType - Tipo de target: 'personal', 'vehiculo', 'visita', 'personal_comision', 'empresa_empleado'
     * @returns {Promise<Array>} Array de logs del tipo especificado
     * @throws {Error} Si la petición falla o el tipo no es válido
     * 
     * @example
     * const logsPersonal = await accessLogsApi.getByType('personal');
     * console.log(logsPersonal); // [{ id: 1, target_id: 15, action: 'entrada', log_time: '2025-10-25 10:30:00', ... }, ...]
     */
    async getByType(targetType) {
        try {
            // Validar tipo
            if (!this.validTypes.includes(targetType)) {
                throw new Error(`Tipo de target no válido: ${targetType}. Tipos permitidos: ${this.validTypes.join(', ')}`);
            }

            // Añadir timestamp para evitar caché
            const timestamp = new Date().getTime();
            const result = await this.client.get(this.endpoint, {
                target_type: targetType,
                nocache: timestamp
            });
            
            if (!result.success) {
                throw new Error(result.error || `Error al obtener logs de ${targetType}.`);
            }
            
            return result.data || result;
        } catch (error) {
            console.error(`Error al obtener logs de ${targetType}:`, error);
            throw new Error(error.message || `Error al obtener logs de ${targetType}.`);
        }
    }

    /**
     * Obtiene los logs de TODOS los tipos en paralelo usando Promise.all
     * 
     * @returns {Promise<Object>} Objeto con 5 arrays, uno por cada tipo
     * @throws {Error} Si alguna petición falla críticamente
     * 
     * @example
     * const todosLosLogs = await accessLogsApi.getAllTypes();
     * console.log(todosLosLogs);
     * // {
     * //   personal: [...],
     * //   vehiculo: [...],
     * //   visita: [...],
     * //   personal_comision: [...],
     * //   empresa_empleado: [...]
     * // }
     */
    async getAllTypes() {
        try {
            // Ejecutar las 5 peticiones en paralelo
            const [personalLogs, vehiculoLogs, visitaLogs, comisionLogs, empresaLogs] = await Promise.all([
                this.getByType('personal').catch(err => {
                    console.warn('Error al obtener logs de personal:', err);
                    return []; // Retornar array vacío si falla
                }),
                this.getByType('vehiculo').catch(err => {
                    console.warn('Error al obtener logs de vehículo:', err);
                    return [];
                }),
                this.getByType('visita').catch(err => {
                    console.warn('Error al obtener logs de visita:', err);
                    return [];
                }),
                this.getByType('personal_comision').catch(err => {
                    console.warn('Error al obtener logs de personal en comisión:', err);
                    return [];
                }),
                this.getByType('empresa_empleado').catch(err => {
                    console.warn('Error al obtener logs de empresa empleado:', err);
                    return [];
                })
            ]);

            return {
                personal: personalLogs,
                vehiculo: vehiculoLogs,
                visita: visitaLogs,
                personal_comision: comisionLogs,
                empresa_empleado: empresaLogs
            };
        } catch (error) {
            console.error('Error crítico al obtener todos los logs:', error);
            throw new Error(error.message || 'Error al obtener todos los logs.');
        }
    }

    /**
     * Registra un acceso manual desde módulos de control
     * 
     * @param {number|string} targetId - ID del personal/vehículo/visita
     * @param {string} targetType - Tipo: 'personal', 'vehiculo', 'visita', etc.
     * @param {string} [puntoAcceso='desconocido'] - Punto de acceso: 'oficina', 'puerta_principal', etc.
     * @returns {Promise<Object>} Resultado con acción registrada (entrada/salida)
     * @throws {Error} Si la petición falla
     * 
     * @example
     * const resultado = await accessLogsApi.logManual(123, 'personal', 'oficina');
     * console.log(resultado);
     * // {
     * //   success: true,
     * //   action: 'entrada',
     * //   message: 'Acceso registrado correctamente',
     * //   personalName: 'Juan Pérez',
     * //   personalRut: '12345678',
     * //   ...
     * // }
     */
    async logManual(targetId, targetType, puntoAcceso = 'desconocido') {
        try {
            // Validar tipo
            if (!this.validTypes.includes(targetType)) {
                throw new Error(`Tipo de target no válido: ${targetType}`);
            }

            const result = await this.client.post(this.endpoint, {
                target_id: targetId,
                target_type: targetType,
                punto_acceso: puntoAcceso
            });

            // El ApiClient retorna { success, data, error }
            // data contiene lo que PHP devolvió directamente
            if (!result.success) {
                throw new Error(result.error || `Error al registrar acceso para ${targetType}.`);
            }

            if (!result.data) {
                throw new Error(`Error al registrar acceso para ${targetType}: respuesta vacía.`);
            }

            return result.data;
        } catch (error) {
            console.error(`Error al registrar acceso para ${targetType}:`, error);
            throw new Error(error.message || `Error al registrar acceso para ${targetType}.`);
        }
    }

    /**
     * Registra un acceso INTELIGENTE por pórtico
     * 
     * @description
     * Este método detecta automáticamente:
     * - Si es entrada o salida según el último registro
     * - El tipo de entidad (personal, vehículo, visita, etc.)
     * - Si requiere aclaración (funcionario fuera de horario)
     * 
     * @param {number|string} id - ID o RUT de la persona/vehículo
     * @returns {Promise<Object>} Resultado con acción y datos detectados
     * @throws {Error} Si la petición falla
     * 
     * @example
     * const resultado = await accessLogsApi.logPortico("12345678");
     * console.log(resultado);
     * // {
     * //   success: true,
     * //   action: 'entrada',
     * //   type: 'personal',
     * //   name: 'Juan Pérez',
     * //   message: 'Entrada registrada',
     * //   needs_clarification: false
     * // }
     * 
     * // Si requiere aclaración:
     * // {
     * //   action: 'clarification_required',
     * //   needs_clarification: true,
     * //   person_id: 123,
     * //   name: 'Juan Pérez',
     * //   message: 'Funcionario detectado fuera de horario'
     * // }
     */
    async logPortico(id) {
        try {
            // Añadir timestamp para evitar caché
            const timestamp = new Date().getTime();
            const result = await this.client.post(
                `${this.porticoEndpoint}?nocache=${timestamp}`,
                { id: String(id).trim() }
            );

            // El ApiClient retorna { success, data, error }
            // data contiene lo que PHP devolvió directamente
            if (!result.success) {
                throw new Error(result.error || 'Error al registrar acceso por pórtico.');
            }

            if (!result.data) {
                throw new Error('Error al registrar acceso por pórtico: respuesta vacía.');
            }

            // Devolver los datos del PHP directamente
            return result.data;
        } catch (error) {
            console.error('Error al registrar acceso por pórtico:', error);
            throw new Error(error.message || 'Error al registrar acceso por pórtico.');
        }
    }

    /**
     * Registra un acceso con aclaración (cuando requiere confirmación)
     *
     * @param {Object} data - Datos de la aclaración
     * @param {number} data.person_id - ID del personal
     * @param {string} data.reason - Razón de acceso: 'residencia', 'trabajo', 'reunion', 'otros'
     * @param {string} [data.details] - Detalles adicionales cuando reason='otros' (opcional)
     * @returns {Promise<Object>} Resultado del registro con nombre y foto de la persona
     * @throws {Error} Si la petición falla o person_id/reason no válidos
     *
     * @example
     * const resultado = await accessLogsApi.logClarified({
     *     person_id: 123,
     *     reason: 'trabajo',
     *     details: 'Turno de guardia'
     * });
     * console.log(resultado);
     * // {
     * //   message: 'Ingreso para Juan Pérez registrado con motivo: Trabajo',
     * //   name: 'Juan Pérez',
     * //   id: 123,
     * //   type: 'personal',
     * //   action: 'entrada',
     * //   photoUrl: 'ruta/a/foto.jpg'
     * // }
     */
    async logClarified(data) {
        try {
            // Validar campos obligatorios
            if (!data.person_id) {
                throw new Error('El campo "person_id" es obligatorio.');
            }
            if (!data.reason) {
                throw new Error('El campo "reason" es obligatorio.');
            }

            const result = await this.client.post(this.clarifiedEndpoint, data);

            // El ApiClient retorna { success, data, error }
            // data contiene lo que PHP devolvió directamente
            if (!result.success) {
                throw new Error(result.error || 'Error al registrar acceso clarificado.');
            }

            if (!result.data) {
                throw new Error('Error al registrar acceso clarificado: respuesta vacía.');
            }

            return result.data;
        } catch (error) {
            console.error('Error al registrar acceso clarificado:', error);
            throw new Error(error.message || 'Error al registrar acceso clarificado.');
        }
    }

    /**
     * Obtiene todos los logs combinados y ordenados por fecha (helper method)
     * 
     * @returns {Promise<Array>} Array combinado de todos los logs ordenados
     * @throws {Error} Si la petición falla
     * 
     * @example
     * const todosLosLogs = await accessLogsApi.getAllCombined();
     * console.log(todosLosLogs[0]); // Log más reciente
     */
    async getAllCombined() {
        try {
            const allLogs = await this.getAllTypes();
            
            // Combinar todos los arrays
            const combined = [
                ...allLogs.personal,
                ...allLogs.vehiculo,
                ...allLogs.visita,
                ...allLogs.personal_comision,
                ...allLogs.empresa_empleado
            ];
            
            // Ordenar por fecha descendente (más recientes primero)
            combined.sort((a, b) => new Date(b.log_time) - new Date(a.log_time));
            
            return combined;
        } catch (error) {
            console.error('Error al obtener logs combinados:', error);
            throw new Error(error.message || 'Error al obtener logs combinados.');
        }
    }
}

// Exportar una instancia singleton por defecto
export default new AccessLogsApi();
