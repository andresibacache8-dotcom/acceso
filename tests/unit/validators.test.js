/**
 * tests/unit/validators.test.js
 * Tests unitarios para js/utils/validators.js
 */

import { describe, test, expect } from '@jest/globals';
import {
    validarRUT,
    limpiarRUT,
    validarPatenteChilena,
    obtenerInfoPatente,
    validarEmail,
    validarFechaNoFutura,
    validarRangoFechas,
    validarTelefonoChileno,
    validarNoVacio,
    validarRango,
    validarPassword
} from '../../js/utils/validators.js';

describe('Validators - RUT', () => {
    test('debería validar RUT correcto sin formato', () => {
        expect(validarRUT('12345678')).toBe(true);
    });

    test('debería validar RUT con 7 dígitos', () => {
        expect(validarRUT('1234567')).toBe(true);
    });

    test('debería rechazar RUT vacío', () => {
        expect(validarRUT('')).toBe(false);
    });

    test('debería rechazar RUT con letras', () => {
        expect(validarRUT('1234567A')).toBe(false);
    });

    test('debería rechazar RUT con menos de 7 dígitos', () => {
        expect(validarRUT('123456')).toBe(false);
    });

    test('debería rechazar RUT con más de 8 dígitos', () => {
        expect(validarRUT('123456789')).toBe(false);
    });

    test('debería rechazar RUT con null', () => {
        expect(validarRUT(null)).toBe(false);
    });

    test('debería rechazar RUT con undefined', () => {
        expect(validarRUT(undefined)).toBe(false);
    });

    test('debería limpiar RUT removiendo puntos y dígito verificador', () => {
        expect(limpiarRUT('12.345.678-9')).toBe('123456789');
    });

    test('debería limpiar RUT removiendo espacios', () => {
        expect(limpiarRUT('12 345 678')).toBe('12345678');
    });

    test('debería retornar string vacío si RUT es null', () => {
        expect(limpiarRUT(null)).toBe('');
    });
});

describe('Validators - Patente Chilena', () => {
    test('debería validar patente antiguo formato AA1234', () => {
        expect(validarPatenteChilena('AA1234')).toBe(true);
    });

    test('debería validar patente antiguo minúsculas', () => {
        expect(validarPatenteChilena('aa1234')).toBe(true);
    });

    test('debería validar patente nuevo formato BCDF12', () => {
        expect(validarPatenteChilena('BCDF12')).toBe(true);
    });

    test('debería validar patente nuevo minúsculas', () => {
        expect(validarPatenteChilena('bcdf12')).toBe(true);
    });

    test('debería validar patente moto nuevo BCD12', () => {
        expect(validarPatenteChilena('BCD12')).toBe(true);
    });

    test('debería validar patente moto antiguo AB123', () => {
        expect(validarPatenteChilena('AB123')).toBe(true);
    });

    test('debería validar patente remolque ABC123', () => {
        expect(validarPatenteChilena('ABC123')).toBe(true);
    });

    test('debería rechazar patente inválida', () => {
        expect(validarPatenteChilena('ABC')).toBe(false);
    });

    test('debería rechazar patente con caracteres especiales', () => {
        expect(validarPatenteChilena('AA-1234')).toBe(false);
    });

    test('obtenerInfoPatente retorna info correcta para auto antiguo', () => {
        const info = obtenerInfoPatente('AA1234');
        expect(info.tipo).toBe('Auto antiguo');
        expect(info.valida).toBe(true);
    });

    test('obtenerInfoPatente retorna info correcta para auto nuevo', () => {
        const info = obtenerInfoPatente('BCDF12');
        expect(info.tipo).toBe('Auto nuevo');
        expect(info.valida).toBe(true);
    });

    test('obtenerInfoPatente retorna tipo desconocido para patente inválida', () => {
        const info = obtenerInfoPatente('INVALID');
        expect(info.valida).toBe(false);
        expect(info.tipo).toBe('Desconocido');
    });
});

describe('Validators - Email', () => {
    test('debería validar email correcto', () => {
        expect(validarEmail('test@example.com')).toBe(true);
    });

    test('debería validar email con múltiples puntos', () => {
        expect(validarEmail('juan.perez@empresa.co.cl')).toBe(true);
    });

    test('debería rechazar email sin @', () => {
        expect(validarEmail('testexample.com')).toBe(false);
    });

    test('debería rechazar email sin dominio', () => {
        expect(validarEmail('test@')).toBe(false);
    });

    test('debería rechazar email con espacios', () => {
        expect(validarEmail('test @example.com')).toBe(false);
    });

    test('debería rechazar email vacío', () => {
        expect(validarEmail('')).toBe(false);
    });
});

describe('Validators - Fechas', () => {
    test('debería validar fecha de hoy como no futura', () => {
        const hoy = new Date().toISOString().split('T')[0];
        expect(validarFechaNoFutura(hoy)).toBe(true);
    });

    test('debería validar fecha pasada como no futura', () => {
        expect(validarFechaNoFutura('2020-01-01')).toBe(true);
    });

    test('debería rechazar fecha futura', () => {
        expect(validarFechaNoFutura('2099-12-31')).toBe(false);
    });

    test('debería rechazar fecha vacía', () => {
        expect(validarFechaNoFutura('')).toBe(false);
    });

    test('debería validar rango de fechas correcto', () => {
        expect(validarRangoFechas('2025-01-01', '2025-01-31')).toBe(true);
    });

    test('debería validar fechas iguales como rango válido', () => {
        expect(validarRangoFechas('2025-01-15', '2025-01-15')).toBe(true);
    });

    test('debería rechazar rango con fecha fin anterior a inicio', () => {
        expect(validarRangoFechas('2025-01-31', '2025-01-01')).toBe(false);
    });

    test('debería rechazar rango con fechas vacías', () => {
        expect(validarRangoFechas('', '2025-01-01')).toBe(false);
    });
});

describe('Validators - Teléfono Chileno', () => {
    test('debería validar móvil formato +56912345678', () => {
        expect(validarTelefonoChileno('+56912345678')).toBe(true);
    });

    test('debería validar móvil formato 912345678', () => {
        expect(validarTelefonoChileno('912345678')).toBe(true);
    });

    test('debería validar teléfono fijo formato 221234567', () => {
        expect(validarTelefonoChileno('221234567')).toBe(true);
    });

    test('debería validar teléfono fijo +56221234567', () => {
        expect(validarTelefonoChileno('+56221234567')).toBe(true);
    });

    test('debería validar móvil con formato 56912345678', () => {
        expect(validarTelefonoChileno('56912345678')).toBe(true);
    });

    test('debería rechazar teléfono con letras', () => {
        expect(validarTelefonoChileno('9123456A8')).toBe(false);
    });

    test('debería tolerar espacios y guiones', () => {
        expect(validarTelefonoChileno('9 1234-5678')).toBe(true);
    });

    test('debería rechazar teléfono inválido', () => {
        expect(validarTelefonoChileno('123')).toBe(false);
    });
});

describe('Validators - String', () => {
    test('debería validar string no vacío', () => {
        expect(validarNoVacio('test')).toBe(true);
    });

    test('debería rechazar string vacío', () => {
        expect(!validarNoVacio('')).toBe(true); // Falsy value
    });

    test('debería rechazar string con solo espacios', () => {
        expect(!validarNoVacio('   ')).toBe(true); // Falsy value
    });

    test('debería rechazar null', () => {
        expect(!validarNoVacio(null)).toBe(true); // Falsy value
    });

    test('debería rechazar undefined', () => {
        expect(!validarNoVacio(undefined)).toBe(true); // Falsy value
    });
});

describe('Validators - Rango Numérico', () => {
    test('debería validar número dentro del rango', () => {
        expect(validarRango(5, 1, 10)).toBe(true);
    });

    test('debería validar número en límite inferior', () => {
        expect(validarRango(1, 1, 10)).toBe(true);
    });

    test('debería validar número en límite superior', () => {
        expect(validarRango(10, 1, 10)).toBe(true);
    });

    test('debería rechazar número fuera del rango', () => {
        expect(validarRango(11, 1, 10)).toBe(false);
    });

    test('debería convertir string a número', () => {
        expect(validarRango('5', 1, 10)).toBe(true);
    });

    test('debería rechazar NaN', () => {
        expect(validarRango('abc', 1, 10)).toBe(false);
    });
});

describe('Validators - Contraseña', () => {
    test('debería validar contraseña correcta', () => {
        const result = validarPassword('Password123');
        expect(result.valida).toBe(true);
    });

    test('debería rechazar contraseña corta', () => {
        const result = validarPassword('Pass1');
        expect(result.valida).toBe(false);
        expect(result.mensaje).toContain('8 caracteres');
    });

    test('debería rechazar contraseña sin minúsculas', () => {
        const result = validarPassword('PASSWORD123');
        expect(result.valida).toBe(false);
        expect(result.mensaje).toContain('minúscula');
    });

    test('debería rechazar contraseña sin mayúsculas', () => {
        const result = validarPassword('password123');
        expect(result.valida).toBe(false);
        expect(result.mensaje).toContain('mayúscula');
    });

    test('debería rechazar contraseña sin números', () => {
        const result = validarPassword('PasswordAbc');
        expect(result.valida).toBe(false);
        expect(result.mensaje).toContain('número');
    });

    test('debería validar contraseña larga', () => {
        const result = validarPassword('MyP@ssw0rd123');
        expect(result.valida).toBe(true);
    });
});
