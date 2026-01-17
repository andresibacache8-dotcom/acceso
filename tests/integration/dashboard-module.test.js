/**
 * tests/integration/dashboard-module.test.js
 * Tests de integración para el módulo de Dashboard
 */

import { describe, test, expect, beforeEach, jest, afterEach } from '@jest/globals';
import ApiClient from '../../js/api/api-client.js';

/**
 * Mock del DashboardModule para testing
 */
class MockDashboardModule {
    constructor(apiClient = null) {
        this.apiClient = apiClient || new ApiClient('api/');
        this.counters = {};
        this.isLoading = false;
    }

    /**
     * Carga los contadores del dashboard
     */
    async loadCounters() {
        this.isLoading = true;
        try {
            const result = await this.apiClient.get('dashboard');

            if (result.success) {
                this.counters = result.data;
                return { success: true, data: result.data };
            } else {
                return { success: false, error: result.error };
            }
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Obtiene datos detallados de una categoría
     */
    async obtenerDetallesPorCategoria(categoria) {
        try {
            const result = await this.apiClient.get('dashboard', {
                details: categoria
            });

            return result.success ? { success: true, data: result.data } : { success: false, error: result.error };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    /**
     * Obtiene contador específico
     */
    getCounter(key) {
        return this.counters[key] || 0;
    }

    /**
     * Obtiene todos los contadores
     */
    getAllCounters() {
        return this.counters;
    }

    /**
     * Actualiza un contador
     */
    updateCounter(key, value) {
        this.counters[key] = value;
    }

    /**
     * Limpia los contadores
     */
    clearCounters() {
        this.counters = {};
    }

    /**
     * Calcula total de personal en sistema
     */
    getTotalPersonal() {
        return (this.counters.personal_trabajando || 0) +
               (this.counters.personal_fuera || 0);
    }

    /**
     * Calcula total de vehículos
     */
    getTotalVehiculos() {
        return (this.counters.vehiculos_adentro || 0) +
               (this.counters.vehiculos_afuera || 0);
    }

    /**
     * Verifica si hay alertas
     */
    hasAlerts() {
        return (this.counters.visitantes_sin_registro || 0) > 0 ||
               (this.counters.vehiculos_no_autorizados || 0) > 0;
    }
}

describe('DashboardModule - Carga de Contadores', () => {
    let module;
    let mockApiClient;

    beforeEach(() => {
        mockApiClient = new ApiClient('api/');
        global.fetch = jest.fn();
        module = new MockDashboardModule(mockApiClient);
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería cargar contadores del dashboard', async () => {
        const mockCounters = {
            personal_trabajando: 120,
            personal_fuera: 30,
            vehiculos_adentro: 45,
            vehiculos_afuera: 15
        };

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => mockCounters
        });

        const result = await module.loadCounters();

        expect(result.success).toBe(true);
        expect(module.counters).toEqual(mockCounters);
    });

    test('debería marcar isLoading durante carga', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => ({})
        });

        const loadPromise = module.loadCounters();
        expect(module.isLoading).toBe(true);

        await loadPromise;
        expect(module.isLoading).toBe(false);
    });

    test('debería manejar error en carga de contadores', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 500,
            statusText: 'Internal Server Error',
            json: async () => ({ message: 'Error del servidor' })
        });

        const result = await module.loadCounters();

        expect(result.success).toBe(false);
        expect(result.error).toBeDefined();
    });
});

describe('DashboardModule - Detalles por Categoría', () => {
    let module;
    let mockApiClient;

    beforeEach(() => {
        mockApiClient = new ApiClient('api/');
        global.fetch = jest.fn();
        module = new MockDashboardModule(mockApiClient);
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    test('debería obtener detalles de personal trabajando', async () => {
        const mockData = [
            { id: 1, Nombres: 'Juan', Unidad: 'Policía', Grado: 'Capitán' },
            { id: 2, Nombres: 'Maria', Unidad: 'Bomberos', Grado: 'Teniente' }
        ];

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => mockData
        });

        const result = await module.obtenerDetallesPorCategoria('personal_trabajando');

        expect(result.success).toBe(true);
        expect(global.fetch).toHaveBeenCalledWith(
            expect.stringContaining('details=personal_trabajando'),
            expect.any(Object)
        );
    });

    test('debería obtener detalles de vehículos', async () => {
        const mockData = [
            { id: 1, placa: 'AA1234', tipo: 'Auto', propietario: 'Juan' },
            { id: 2, placa: 'BB5678', tipo: 'Moto', propietario: 'Maria' }
        ];

        global.fetch.mockResolvedValueOnce({
            ok: true,
            status: 200,
            json: async () => mockData
        });

        const result = await module.obtenerDetallesPorCategoria('vehiculos');

        expect(result.success).toBe(true);
        expect(result.data.length).toBe(2);
    });

    test('debería manejar categoría inválida', async () => {
        global.fetch.mockResolvedValueOnce({
            ok: false,
            status: 400,
            statusText: 'Bad Request',
            json: async () => ({ message: 'Categoría no válida' })
        });

        const result = await module.obtenerDetallesPorCategoria('categoria_invalida');

        expect(result.success).toBe(false);
    });
});

describe('DashboardModule - Acceso a Contadores', () => {
    let module;

    beforeEach(() => {
        module = new MockDashboardModule();
        module.counters = {
            personal_trabajando: 120,
            personal_fuera: 30,
            vehiculos_adentro: 45,
            vehiculos_afuera: 15,
            visitantes_registrados: 85
        };
    });

    test('debería obtener contador específico', () => {
        expect(module.getCounter('personal_trabajando')).toBe(120);
    });

    test('debería retornar 0 para contador no existente', () => {
        expect(module.getCounter('contador_inexistente')).toBe(0);
    });

    test('debería obtener todos los contadores', () => {
        const all = module.getAllCounters();

        expect(all.personal_trabajando).toBe(120);
        expect(all.vehiculos_adentro).toBe(45);
    });

    test('debería actualizar contador', () => {
        module.updateCounter('personal_trabajando', 150);

        expect(module.getCounter('personal_trabajando')).toBe(150);
    });

    test('debería limpiar contadores', () => {
        module.clearCounters();

        expect(module.getAllCounters()).toEqual({});
        expect(module.getCounter('personal_trabajando')).toBe(0);
    });
});

describe('DashboardModule - Cálculos de Totales', () => {
    let module;

    beforeEach(() => {
        module = new MockDashboardModule();
        module.counters = {
            personal_trabajando: 120,
            personal_fuera: 30,
            vehiculos_adentro: 45,
            vehiculos_afuera: 15
        };
    });

    test('debería calcular total de personal', () => {
        expect(module.getTotalPersonal()).toBe(150); // 120 + 30
    });

    test('debería calcular total de vehículos', () => {
        expect(module.getTotalVehiculos()).toBe(60); // 45 + 15
    });

    test('debería retornar 0 si no hay contadores de personal', () => {
        module.clearCounters();

        expect(module.getTotalPersonal()).toBe(0);
    });

    test('debería retornar 0 si no hay contadores de vehículos', () => {
        module.clearCounters();

        expect(module.getTotalVehiculos()).toBe(0);
    });
});

describe('DashboardModule - Detección de Alertas', () => {
    let module;

    beforeEach(() => {
        module = new MockDashboardModule();
    });

    test('debería detectar alerta de visitantes sin registro', () => {
        module.counters = { visitantes_sin_registro: 5 };

        expect(module.hasAlerts()).toBe(true);
    });

    test('debería detectar alerta de vehículos no autorizados', () => {
        module.counters = { vehiculos_no_autorizados: 2 };

        expect(module.hasAlerts()).toBe(true);
    });

    test('debería detectar alerta si hay múltiples problemas', () => {
        module.counters = {
            visitantes_sin_registro: 3,
            vehiculos_no_autorizados: 1
        };

        expect(module.hasAlerts()).toBe(true);
    });

    test('debería no detectar alerta si todo está bien', () => {
        module.counters = {
            visitantes_sin_registro: 0,
            vehiculos_no_autorizados: 0
        };

        expect(module.hasAlerts()).toBe(false);
    });

    test('debería no detectar alerta si contadores no existen', () => {
        module.clearCounters();

        expect(module.hasAlerts()).toBe(false);
    });
});

describe('DashboardModule - Estado', () => {
    let module;

    beforeEach(() => {
        module = new MockDashboardModule();
    });

    test('debería inicializar con contadores vacíos', () => {
        expect(module.counters).toEqual({});
    });

    test('debería inicializar sin loading', () => {
        expect(module.isLoading).toBe(false);
    });

    test('debería aceptar ApiClient personalizado', () => {
        const customClient = new ApiClient('/custom/');
        const newModule = new MockDashboardModule(customClient);

        expect(newModule.apiClient.baseURL).toBe('/custom/');
    });

    test('debería mantener estado de contadores entre llamadas', () => {
        module.updateCounter('test', 42);

        expect(module.getCounter('test')).toBe(42);

        module.updateCounter('test', 100);

        expect(module.getCounter('test')).toBe(100);
    });
});
