/**
 * tests/setup.js
 * Setup global para Jest - Mocks y configuración
 */

import 'whatwg-fetch';
import { jest } from '@jest/globals';

// Mock de localStorage
global.localStorage = {
    getItem: jest.fn(),
    setItem: jest.fn(),
    removeItem: jest.fn(),
    clear: jest.fn(),
    key: jest.fn(),
    length: 0
};

// Mock de sessionStorage
global.sessionStorage = {
    getItem: jest.fn(),
    setItem: jest.fn(),
    removeItem: jest.fn(),
    clear: jest.fn(),
    key: jest.fn(),
    length: 0
};

// Mock de console (opcional - uncomment si necesitas)
// global.console = {
//     ...console,
//     error: jest.fn(),
//     warn: jest.fn(),
//     log: jest.fn(),
//     debug: jest.fn()
// };

// Mock de window.alert
global.alert = jest.fn();

// Mock de window.confirm
global.confirm = jest.fn(() => true);

// Configurar timezone para tests de fechas
process.env.TZ = 'UTC';
