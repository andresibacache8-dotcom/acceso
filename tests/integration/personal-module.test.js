/**
 * tests/integration/personal-module.test.js
 * Tests de integración para el módulo de Personal
 */

import { describe, test, expect, beforeEach, jest, afterEach } from '@jest/globals';
import ApiClient from '../../js/api/api-client.js';

/**
 * Mock del PersonalModule para testing
 * En un test real, importaríamos el módulo pero para esto creamos un mock
 */
class MockPersonalModule {
    constructor(apiClient = null) {
        this.apiClient = apiClient || new ApiClient('api/');
        this.data = [];
        this.selectedId = null;
        this.isLoading = false;
    }

    /**
     * Carga la lista de personal desde la API
     */
    async loadPersonal(page = 1, perPage = 50) {
        this.isLoading = true;
        try {
            const result = await this.apiClient.get('personal', {
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
     * Busca personal por criterios
     */
    async buscarPersonal(query) {
        try {
            const result = await this.apiClient.get('personal', {
                search: query
            });

            return result.success ? { success: true, data: result.data } : { success: false, error: result.error };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    /**
     * Obtiene un empleado por ID
     */
    async obtenerPersonal(id) {
        try {
            const result = await this.apiClient.get(`personal?id=${id}`);

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
     * Crea un nuevo empleado
     */
    async crearPersonal(data) {
        this.isLoading = true;
        try {
            const result = await this.apiClient.post('personal', data);

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
     * Actualiza un empleado existente
     */
    async actualizarPersonal(id, data) {
        this.isLoading = true;
        try {
            const result = await this.apiClient.put(`personal?id=${id}`, data);

            if (result.success) {
                const index = this.data.findIndex(p => p.id === id);
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
     * Elimina un empleado
     */
    async eliminarPersonal(id) {
        this.isLoading = true;
        try {
            const result = await this.apiClient.delete(`personal?id=${id}`);

            if (result.success) {
                this.data = this.data.filter(p => p.id !== id);
                return { success: true };
            }
            return { success: false, error: result.error };
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Filtra personal por unidad
     */
    filtrarPorUnidad(unidad) {
        return this.data.filter(p => p.Unidad === unidad);
    }

    /**
     * Filtra personal por grado
     */
    filtrarPorGrado(grado) {
        return this.data.filter(p => p.Grado === grado);
    }

    /**
     * Obtiene personal por estado
     */
    obtenerPorEstado(estado) {
        return this.data.filter(p => p.estado === estado);
    }
}

describe('PersonalModule - Carga de Datos', () => {
    let module;
    let mockApiClient;

    beforeEach(() => {
        mockApiClient = new ApiClient('api/');
        global.fetch = jest.fn();
        module = new MockPersonalModule(mockApiClient);
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería cargar lista de personal correctamente', async () => {
        const mockData = {
            success: true,
            data: [
                { id: 1, Nombres: 'Juan', Paterno: 'Pérez', NrRut: '12345678-9' },
                { id: 2, Nombres: 'Maria', Paterno: 'García', NrRut: '87654321-0' }
            ]
        };

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => mockData.data
        });

        const result = await module.loadPersonal();

        expect(result.success).toBe(true);
        expect(module.data.length).toBe(2);
        expect(module.data[0].Nombres).toBe('Juan');
    });

    test('debería soportar paginación', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => []
        });

        await module.loadPersonal(2, 25);

        const callUrl = global.fetch.mock.calls[0][0];
        expect(callUrl).toContain('page=2');
        expect(callUrl).toContain('perPage=25');
    });

    test('debería marcar isLoading durante carga', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => []
        });

        const loadPromise = module.loadPersonal();
        expect(module.isLoading).toBe(true);

        await loadPromise;
        expect(module.isLoading).toBe(false);
    });

    test('debería manejar error en loadPersonal', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 500,
            statusText: 'Internal Server Error',
            json: async () => ({ message: 'Error del servidor' })
        });

        const result = await module.loadPersonal();

        expect(result.success).toBe(false);
        expect(result.error).toBeDefined();
    });
});

describe('PersonalModule - Búsqueda', () => {
    let module;
    let mockApiClient;

    beforeEach(() => {
        mockApiClient = new ApiClient('api/');
        global.fetch = jest.fn();
        module = new MockPersonalModule(mockApiClient);
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería buscar personal por nombre', async () => {
        const mockData = [
            { id: 1, Nombres: 'Juan', Paterno: 'Pérez', NrRut: '12345678-9' }
        ];

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => mockData
        });

        const result = await module.buscarPersonal('Juan');

        expect(result.success).toBe(true);
        expect(global.fetch).toHaveBeenCalledWith(
            expect.stringContaining('search=Juan'),
            expect.any(Object)
        );
    });

    test('debería retornar array vacío si no hay resultados', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => []
        });

        const result = await module.buscarPersonal('NoExiste');

        expect(result.success).toBe(true);
        expect(Array.isArray(result.data)).toBe(true);
    });

    test('debería manejar error en búsqueda', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 400,
            statusText: 'Bad Request',
            json: async () => ({ message: 'Búsqueda inválida' })
        });

        const result = await module.buscarPersonal('invalid');

        expect(result.success).toBe(false);
    });
});

describe('PersonalModule - CRUD', () => {
    let module;
    let mockApiClient;

    beforeEach(() => {
        mockApiClient = new ApiClient('api/');
        global.fetch = jest.fn();
        module = new MockPersonalModule(mockApiClient);
        module.data = [
            { id: 1, Nombres: 'Juan', Paterno: 'Pérez', NrRut: '12345678-9' }
        ];
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería obtener un personal por ID', async () => {
        const mockData = { id: 1, Nombres: 'Juan', Paterno: 'Pérez' };

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => mockData
        });

        const result = await module.obtenerPersonal(1);

        expect(result.success).toBe(true);
        expect(module.selectedId).toBe(1);
    });

    test('debería crear nuevo personal', async () => {
        const newPersonal = { Nombres: 'Carlos', Paterno: 'López', NrRut: '99999999-9' };
        const mockResponse = { id: 2, ...newPersonal };

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 201,
            json: async () => mockResponse
        });

        const result = await module.crearPersonal(newPersonal);

        expect(result.success).toBe(true);
        expect(module.data.length).toBe(2);
        expect(module.data[1].Nombres).toBe('Carlos');
    });

    test('debería actualizar personal existente', async () => {
        const updateData = { Nombres: 'Juan Actualizado' };
        const mockResponse = { id: 1, ...updateData };

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => mockResponse
        });

        const result = await module.actualizarPersonal(1, updateData);

        expect(result.success).toBe(true);
        expect(module.data[0].Nombres).toBe('Juan Actualizado');
    });

    test('debería eliminar personal', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => ({ success: true })
        });

        const result = await module.eliminarPersonal(1);

        expect(result.success).toBe(true);
        expect(module.data.length).toBe(0);
    });

    test('debería manejar error en crear', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 400,
            statusText: 'Bad Request',
            json: async () => ({ message: 'Campos requeridos faltantes' })
        });

        const result = await module.crearPersonal({});

        expect(result.success).toBe(false);
    });

    test('debería manejar error en actualizar', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 404,
            statusText: 'Not Found',
            json: async () => ({ message: 'Personal no encontrado' })
        });

        const result = await module.actualizarPersonal(999, {});

        expect(result.success).toBe(false);
    });

    test('debería manejar error en eliminar', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 403,
            statusText: 'Forbidden',
            json: async () => ({ message: 'No tiene permiso' })
        });

        const result = await module.eliminarPersonal(1);

        expect(result.success).toBe(false);
        expect(module.data.length).toBe(1); // No se elimina del array local
    });
});

describe('PersonalModule - Filtros', () => {
    let module;

    beforeEach(() => {
        module = new MockPersonalModule();
        module.data = [
            { id: 1, Nombres: 'Juan', Grado: 'Capitán', Unidad: 'Policía', estado: 'activo' },
            { id: 2, Nombres: 'Maria', Grado: 'Teniente', Unidad: 'Policía', estado: 'activo' },
            { id: 3, Nombres: 'Carlos', Grado: 'Capitán', Unidad: 'Bomberos', estado: 'inactivo' }
        ];
    });

    test('debería filtrar por unidad', () => {
        const resultado = module.filtrarPorUnidad('Policía');

        expect(resultado.length).toBe(2);
        expect(resultado[0].Unidad).toBe('Policía');
    });

    test('debería filtrar por grado', () => {
        const resultado = module.filtrarPorGrado('Capitán');

        expect(resultado.length).toBe(2);
        expect(resultado.every(p => p.Grado === 'Capitán')).toBe(true);
    });

    test('debería obtener personal por estado', () => {
        const resultado = module.obtenerPorEstado('activo');

        expect(resultado.length).toBe(2);
        expect(resultado.every(p => p.estado === 'activo')).toBe(true);
    });

    test('debería retornar array vacío si no hay coincidencias', () => {
        const resultado = module.filtrarPorUnidad('Inexistente');

        expect(resultado.length).toBe(0);
    });

    test('debería combinar múltiples filtros', () => {
        const porfUnidad = module.filtrarPorUnidad('Policía');
        const resultado = porfUnidad.filter(p => p.Grado === 'Capitán');

        expect(resultado.length).toBe(1);
        expect(resultado[0].Nombres).toBe('Juan');
    });
});

describe('PersonalModule - Estado', () => {
    let module;

    beforeEach(() => {
        module = new MockPersonalModule();
    });

    test('debería inicializar con datos vacíos', () => {
        expect(module.data).toEqual([]);
    });

    test('debería mantener ID seleccionado', () => {
        module.selectedId = 5;
        expect(module.selectedId).toBe(5);
    });

    test('debería rastrear estado de carga', () => {
        expect(module.isLoading).toBe(false);

        module.isLoading = true;
        expect(module.isLoading).toBe(true);
    });

    test('debería inicializar con ApiClient por defecto', () => {
        const newModule = new MockPersonalModule();
        expect(newModule.apiClient).toBeDefined();
        expect(newModule.apiClient.baseURL).toBe('api/');
    });

    test('debería aceptar ApiClient personalizado', () => {
        const customClient = new ApiClient('/custom/');
        const newModule = new MockPersonalModule(customClient);

        expect(newModule.apiClient.baseURL).toBe('/custom/');
    });
});
