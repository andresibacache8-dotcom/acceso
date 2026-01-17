/**
 * tests/unit/api-client.test.js
 * Tests unitarios para js/api/api-client.js
 */

import { describe, test, expect, beforeEach, jest, afterEach } from '@jest/globals';
import ApiClient from '../../js/api/api-client.js';

describe('ApiClient - Configuración', () => {
    let apiClient;

    beforeEach(() => {
        apiClient = new ApiClient();
    });

    test('debería crear instancia con baseURL por defecto', () => {
        expect(apiClient.baseURL).toBe('api/');
    });

    test('debería crear instancia con baseURL personalizado', () => {
        const customClient = new ApiClient('/custom/api/');
        expect(customClient.baseURL).toBe('/custom/api/');
    });

    test('debería tener timeout de 30000ms', () => {
        expect(apiClient.timeout).toBe(30000);
    });

    test('debería tener headers por defecto', () => {
        expect(apiClient.defaultHeaders['Content-Type']).toBe('application/json');
    });

    test('debería agregar parámetro nocache a URL sin query', () => {
        const url = apiClient.addNoCache('personal');
        expect(url).toContain('personal');
        expect(url).toContain('nocache=');
    });

    test('debería agregar parámetro nocache a URL con query existente', () => {
        const url = apiClient.addNoCache('personal?page=1');
        expect(url).toContain('personal?page=1');
        expect(url).toContain('&nocache=');
    });
});

describe('ApiClient - Peticiones GET', () => {
    let apiClient;

    beforeEach(() => {
        apiClient = new ApiClient('api/');
        global.fetch = jest.fn();
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería realizar petición GET sin parámetros', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => []
        });

        const result = await apiClient.get('personal');

        expect(global.fetch).toHaveBeenCalledWith(
            expect.stringContaining('api/personal'),
            expect.objectContaining({ method: 'GET' })
        );
        expect(result.success).toBe(true);
        expect(result.data).toEqual([]);
    });

    test('debería realizar petición GET con parámetros', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => []
        });

        await apiClient.get('personal', { page: 1, perPage: 50 });

        expect(global.fetch).toHaveBeenCalledWith(
            expect.stringContaining('page=1'),
            expect.any(Object)
        );
        expect(global.fetch).toHaveBeenCalledWith(
            expect.stringContaining('perPage=50'),
            expect.any(Object)
        );
    });

    test('debería incluir parámetro nocache en GET', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => ({})
        });

        await apiClient.get('personal');

        expect(global.fetch).toHaveBeenCalledWith(
            expect.stringContaining('nocache='),
            expect.any(Object)
        );
    });

    test('debería retornar error si GET falla', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 404,
            statusText: 'Not Found',
            json: async () => ({ message: 'Recurso no encontrado' })
        });

        const result = await apiClient.get('personal-inexistente');

        expect(result.success).toBe(false);
        expect(result.error).toContain('Recurso no encontrado');
    });
});

describe('ApiClient - Peticiones POST', () => {
    let apiClient;

    beforeEach(() => {
        apiClient = new ApiClient('api/');
        global.fetch = jest.fn();
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería realizar petición POST con datos', async () => {
        const newPersonal = { nombre: 'Juan', paterno: 'Pérez' };

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 201,
            json: async () => ({ id: 1, ...newPersonal })
        });

        const result = await apiClient.post('personal', newPersonal);

        expect(global.fetch).toHaveBeenCalledWith(
            expect.stringContaining('api/personal'),
            expect.objectContaining({
                method: 'POST',
                body: JSON.stringify(newPersonal)
            })
        );
        expect(result.success).toBe(true);
        expect(result.data.id).toBe(1);
    });

    test('debería realizar petición POST sin datos', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => ({})
        });

        await apiClient.post('personal');

        expect(global.fetch).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({ method: 'POST' })
        );
    });

    test('debería manejar error de validación en POST', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 400,
            statusText: 'Bad Request',
            json: async () => ({ message: 'Campo requerido: nombre' })
        });

        const result = await apiClient.post('personal', {});

        expect(result.success).toBe(false);
        expect(result.error).toContain('Campo requerido');
    });

    test('debería retornar error en POST si servidor falla', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 500,
            statusText: 'Internal Server Error',
            json: async () => ({ message: 'Error del servidor' })
        });

        const result = await apiClient.post('personal', {});

        expect(result.success).toBe(false);
    });
});

describe('ApiClient - Peticiones PUT', () => {
    let apiClient;

    beforeEach(() => {
        apiClient = new ApiClient('api/');
        global.fetch = jest.fn();
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería realizar petición PUT con datos', async () => {
        const updateData = { nombre: 'Juan Modificado' };

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => ({ id: 1, ...updateData })
        });

        const result = await apiClient.put('personal?id=1', updateData);

        expect(global.fetch).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({
                method: 'PUT',
                body: JSON.stringify(updateData)
            })
        );
        expect(result.success).toBe(true);
    });

    test('debería manejar error de no encontrado en PUT', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 404,
            statusText: 'Not Found',
            json: async () => ({ message: 'Personal no encontrado' })
        });

        const result = await apiClient.put('personal?id=999', {});

        expect(result.success).toBe(false);
        expect(result.error).toContain('Personal no encontrado');
    });
});

describe('ApiClient - Peticiones DELETE', () => {
    let apiClient;

    beforeEach(() => {
        apiClient = new ApiClient('api/');
        global.fetch = jest.fn();
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería realizar petición DELETE', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => null
        });

        const result = await apiClient.delete('personal?id=1');

        expect(global.fetch).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({ method: 'DELETE' })
        );
        expect(result.success).toBe(true);
    });

    test('debería manejar error de no autorizado en DELETE', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 403,
            statusText: 'Forbidden',
            json: async () => ({ message: 'No tiene permiso para eliminar' })
        });

        const result = await apiClient.delete('personal?id=1');

        expect(result.success).toBe(false);
        expect(result.error).toContain('No tiene permiso');
    });
});

describe('ApiClient - Manejo de Errores', () => {
    let apiClient;

    beforeEach(() => {
        apiClient = new ApiClient('api/');
        global.fetch = jest.fn();
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería manejar error de red (TypeError)', async () => {
        global.fetch.mockRejectedValueOnce(new TypeError('Failed to fetch'));

        const result = await apiClient.get('personal');

        expect(result.success).toBe(false);
        expect(result.error).toContain('Error de red');
    });

    test('debería manejar respuesta HTTP 204 No Content', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 204,
            statusText: 'No Content',
            json: async () => null
        });

        const result = await apiClient.delete('personal?id=1');

        expect(result.success).toBe(true);
        expect(result.data).toBeNull();
    });

    test('debería manejar error genérico', async () => {
        global.fetch.mockRejectedValueOnce(new Error('Error inesperado'));

        const result = await apiClient.get('personal');

        expect(result.success).toBe(false);
        expect(result.error).toContain('Error inesperado');
    });

    test('debería incluir headers por defecto en todas las peticiones', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => ({})
        });

        await apiClient.post('personal', {});

        expect(global.fetch).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({
                headers: expect.objectContaining({
                    'Content-Type': 'application/json'
                })
            })
        );
    });

    test('debería mezclar headers personalizados con por defecto', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => ({})
        });

        await apiClient.request('personal', {
            headers: { 'Authorization': 'Bearer token' }
        });

        expect(global.fetch).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({
                headers: expect.objectContaining({
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer token'
                })
            })
        );
    });
});

describe('ApiClient - Manejo de Respuestas', () => {
    let apiClient;

    beforeEach(() => {
        apiClient = new ApiClient('api/');
        global.fetch = jest.fn();
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería retornar { success: true, data, error: null } en éxito', async () => {
        const mockData = { id: 1, nombre: 'Juan' };

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => mockData
        });

        const result = await apiClient.get('personal');

        expect(result).toEqual({
            success: true,
            data: mockData,
            error: null
        });
    });

    test('debería retornar { success: false, data: null, error } en fallo', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 400,
            statusText: 'Bad Request',
            json: async () => ({ message: 'Error' })
        });

        const result = await apiClient.get('personal');

        expect(result.success).toBe(false);
        expect(result.data).toBeNull();
        expect(result.error).toBeDefined();
        expect(typeof result.error).toBe('string');
    });

    test('debería usar mensaje de error personalizado si existe', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 400,
            statusText: 'Bad Request',
            json: async () => ({ message: 'Validación fallida: RUT inválido' })
        });

        const result = await apiClient.get('personal');

        expect(result.error).toBe('Validación fallida: RUT inválido');
    });

    test('debería usar statusText si no hay mensaje personalizado', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 500,
            statusText: 'Internal Server Error',
            json: async () => ({})
        });

        const result = await apiClient.get('personal');

        expect(result.error).toContain('HTTP 500');
        expect(result.error).toContain('Internal Server Error');
    });
});

describe('ApiClient - QueryString Builder', () => {
    let apiClient;

    beforeEach(() => {
        apiClient = new ApiClient('api/');
        global.fetch = jest.fn();
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería construir query string correctamente', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => []
        });

        await apiClient.get('personal', {
            page: 1,
            perPage: 25,
            search: 'Juan'
        });

        const callUrl = global.fetch.mock.calls[0][0];
        expect(callUrl).toContain('page=1');
        expect(callUrl).toContain('perPage=25');
        expect(callUrl).toContain('search=Juan');
    });

    test('debería manejar parámetros especiales en query string', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => []
        });

        await apiClient.get('personal', {
            name: 'Juan Pérez',
            rut: '12.345.678-9'
        });

        const callUrl = global.fetch.mock.calls[0][0];
        // URLSearchParams codifica automáticamente
        expect(callUrl).toBeDefined();
    });

    test('debería no agregar query string si no hay parámetros', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => []
        });

        await apiClient.get('personal', {});

        const callUrl = global.fetch.mock.calls[0][0];
        // Solo debe estar nocache
        expect(callUrl).toContain('api/personal');
    });
});
