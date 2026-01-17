/**
 * tests/integration/vehiculos-module.test.js
 * Tests de integración para el módulo de Vehículos
 */

import { describe, test, expect, beforeEach, jest, afterEach } from '@jest/globals';
import ApiClient from '../../js/api/api-client.js';

/**
 * Mock del VehiculosModule para testing
 */
class MockVehiculosModule {
    constructor(apiClient = null) {
        this.apiClient = apiClient || new ApiClient('api/');
        this.data = [];
        this.selectedId = null;
        this.isLoading = false;
    }

    /**
     * Carga la lista de vehículos desde la API
     */
    async loadVehiculos(page = 1, perPage = 50) {
        this.isLoading = true;
        try {
            const result = await this.apiClient.get('vehiculos', {
                page,
                perPage
            });

            if (result.success) {
                this.data = result.data;
                return { success: true, data: result.data };
            } else {
                return { success: false, error: result.error };
            }
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Busca vehículos por patente
     */
    async buscarPorPatente(patente) {
        try {
            const result = await this.apiClient.get('vehiculos', {
                search: patente
            });

            return result.success ? { success: true, data: result.data } : { success: false, error: result.error };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    /**
     * Obtiene un vehículo por ID
     */
    async obtenerVehiculo(id) {
        try {
            const result = await this.apiClient.get(`vehiculos?id=${id}`);

            if (result.success) {
                this.selectedId = id;
                return { success: true, data: result.data };
            }
            return { success: false, error: result.error };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    /**
     * Crea un nuevo vehículo
     */
    async crearVehiculo(data) {
        this.isLoading = true;
        try {
            const result = await this.apiClient.post('vehiculos', data);

            if (result.success) {
                this.data.push(result.data);
                return { success: true, data: result.data };
            }
            return { success: false, error: result.error };
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Actualiza un vehículo
     */
    async actualizarVehiculo(id, data) {
        this.isLoading = true;
        try {
            const result = await this.apiClient.put(`vehiculos?id=${id}`, data);

            if (result.success) {
                const index = this.data.findIndex(v => v.id === id);
                if (index !== -1) {
                    this.data[index] = result.data;
                }
                return { success: true, data: result.data };
            }
            return { success: false, error: result.error };
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Elimina un vehículo
     */
    async eliminarVehiculo(id) {
        this.isLoading = true;
        try {
            const result = await this.apiClient.delete(`vehiculos?id=${id}`);

            if (result.success) {
                this.data = this.data.filter(v => v.id !== id);
                return { success: true };
            }
            return { success: false, error: result.error };
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Obtiene el historial de un vehículo
     */
    async obtenerHistorial(vehiculoId) {
        try {
            const result = await this.apiClient.get(`vehiculo_historial?id=${vehiculoId}`);

            return result.success ? { success: true, data: result.data } : { success: false, error: result.error };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    /**
     * Filtra vehículos por tipo
     */
    filtrarPorTipo(tipo) {
        return this.data.filter(v => v.tipo === tipo);
    }

    /**
     * Filtra vehículos por estado
     */
    filtrarPorEstado(estado) {
        return this.data.filter(v => v.estado === estado);
    }

    /**
     * Filtra vehículos por propietario
     */
    filtrarPorPropietario(propietario) {
        return this.data.filter(v => v.propietario === propietario);
    }

    /**
     * Obtiene vehículos por rango de fecha
     */
    obtenerPorFechaIngreso(fechaInicio, fechaFin) {
        return this.data.filter(v => {
            const fecha = new Date(v.fecha_ingreso);
            return fecha >= new Date(fechaInicio) && fecha <= new Date(fechaFin);
        });
    }
}

describe('VehiculosModule - Carga de Datos', () => {
    let module;
    let mockApiClient;

    beforeEach(() => {
        mockApiClient = new ApiClient('api/');
        global.fetch = jest.fn();
        module = new MockVehiculosModule(mockApiClient);
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería cargar lista de vehículos', async () => {
        const mockData = [
            { id: 1, placa: 'AA1234', tipo: 'Auto', propietario: 'Juan' },
            { id: 2, placa: 'BB5678', tipo: 'Moto', propietario: 'Maria' }
        ];

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => mockData
        });

        const result = await module.loadVehiculos();

        expect(result.success).toBe(true);
        expect(module.data.length).toBe(2);
    });

    test('debería soportar paginación en carga de vehículos', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => []
        });

        await module.loadVehiculos(3, 20);

        const callUrl = global.fetch.mock.calls[0][0];
        expect(callUrl).toContain('page=3');
        expect(callUrl).toContain('perPage=20');
    });
});

describe('VehiculosModule - Búsqueda', () => {
    let module;
    let mockApiClient;

    beforeEach(() => {
        mockApiClient = new ApiClient('api/');
        global.fetch = jest.fn();
        module = new MockVehiculosModule(mockApiClient);
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería buscar vehículo por patente', async () => {
        const mockData = [
            { id: 1, placa: 'AA1234', tipo: 'Auto', propietario: 'Juan' }
        ];

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => mockData
        });

        const result = await module.buscarPorPatente('AA1234');

        expect(result.success).toBe(true);
        expect(global.fetch).toHaveBeenCalledWith(
            expect.stringContaining('search=AA1234'),
            expect.any(Object)
        );
    });

    test('debería manejar búsqueda sin resultados', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => []
        });

        const result = await module.buscarPorPatente('NOEXISTE');

        expect(result.success).toBe(true);
        expect(result.data.length).toBe(0);
    });
});

describe('VehiculosModule - CRUD', () => {
    let module;
    let mockApiClient;

    beforeEach(() => {
        mockApiClient = new ApiClient('api/');
        global.fetch = jest.fn();
        module = new MockVehiculosModule(mockApiClient);
        module.data = [
            { id: 1, placa: 'AA1234', tipo: 'Auto', propietario: 'Juan', estado: 'activo' }
        ];
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería obtener vehículo por ID', async () => {
        const mockData = { id: 1, placa: 'AA1234', tipo: 'Auto' };

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => mockData
        });

        const result = await module.obtenerVehiculo(1);

        expect(result.success).toBe(true);
        expect(module.selectedId).toBe(1);
    });

    test('debería crear nuevo vehículo', async () => {
        const newVehiculo = { placa: 'BB5678', tipo: 'Moto', propietario: 'Maria' };
        const mockResponse = { id: 2, ...newVehiculo };

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 201,
            json: async () => mockResponse
        });

        const result = await module.crearVehiculo(newVehiculo);

        expect(result.success).toBe(true);
        expect(module.data.length).toBe(2);
    });

    test('debería actualizar vehículo', async () => {
        const updateData = { estado: 'inactivo' };
        const mockResponse = { id: 1, placa: 'AA1234', ...updateData };

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => mockResponse
        });

        const result = await module.actualizarVehiculo(1, updateData);

        expect(result.success).toBe(true);
        expect(module.data[0].estado).toBe('inactivo');
    });

    test('debería eliminar vehículo', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => ({ success: true })
        });

        const result = await module.eliminarVehiculo(1);

        expect(result.success).toBe(true);
        expect(module.data.length).toBe(0);
    });
});

describe('VehiculosModule - Historial', () => {
    let module;
    let mockApiClient;

    beforeEach(() => {
        mockApiClient = new ApiClient('api/');
        global.fetch = jest.fn();
        module = new MockVehiculosModule(mockApiClient);
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería obtener historial de vehículo', async () => {
        const mockHistorial = [
            { id: 1, tipo_cambio: 'entrada', fecha: '2025-01-16 08:00' },
            { id: 2, tipo_cambio: 'salida', fecha: '2025-01-16 17:00' }
        ];

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => mockHistorial
        });

        const result = await module.obtenerHistorial(1);

        expect(result.success).toBe(true);
        expect(result.data.length).toBe(2);
    });

    test('debería manejar error en historial', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 404,
            statusText: 'Not Found',
            json: async () => ({ message: 'Vehículo no encontrado' })
        });

        const result = await module.obtenerHistorial(999);

        expect(result.success).toBe(false);
    });
});

describe('VehiculosModule - Filtros', () => {
    let module;

    beforeEach(() => {
        module = new MockVehiculosModule();
        module.data = [
            { id: 1, placa: 'AA1234', tipo: 'Auto', propietario: 'Juan', estado: 'activo', fecha_ingreso: '2024-01-15' },
            { id: 2, placa: 'BB5678', tipo: 'Moto', propietario: 'Maria', estado: 'activo', fecha_ingreso: '2023-06-20' },
            { id: 3, placa: 'CC9999', tipo: 'Auto', propietario: 'Carlos', estado: 'inactivo', fecha_ingreso: '2024-11-10' }
        ];
    });

    test('debería filtrar vehículos por tipo', () => {
        const resultado = module.filtrarPorTipo('Auto');

        expect(resultado.length).toBe(2);
        expect(resultado.every(v => v.tipo === 'Auto')).toBe(true);
    });

    test('debería filtrar vehículos por estado', () => {
        const resultado = module.filtrarPorEstado('activo');

        expect(resultado.length).toBe(2);
        expect(resultado.every(v => v.estado === 'activo')).toBe(true);
    });

    test('debería filtrar vehículos por propietario', () => {
        const resultado = module.filtrarPorPropietario('Juan');

        expect(resultado.length).toBe(1);
        expect(resultado[0].propietario).toBe('Juan');
    });

    test('debería obtener vehículos por rango de fecha', () => {
        const resultado = module.obtenerPorFechaIngreso('2024-01-01', '2024-12-31');

        expect(resultado.length).toBe(2);
        expect(resultado.some(v => v.id === 1)).toBe(true);
    });

    test('debería retornar array vacío si no hay coincidencias en filtros', () => {
        const resultado = module.filtrarPorPropietario('NoExiste');

        expect(resultado.length).toBe(0);
        expect(Array.isArray(resultado)).toBe(true);
    });
});

describe('VehiculosModule - Estado', () => {
    let module;

    beforeEach(() => {
        module = new MockVehiculosModule();
    });

    test('debería inicializar con datos vacíos', () => {
        expect(module.data).toEqual([]);
    });

    test('debería mantener ID seleccionado', () => {
        module.selectedId = 3;
        expect(module.selectedId).toBe(3);
    });

    test('debería rastrear estado de carga', () => {
        expect(module.isLoading).toBe(false);
    });

    test('debería aceptar ApiClient personalizado', () => {
        const customClient = new ApiClient('/custom/api/');
        const newModule = new MockVehiculosModule(customClient);

        expect(newModule.apiClient.baseURL).toBe('/custom/api/');
    });
});
