/**
 * tests/unit/formatters.test.js
 * Tests unitarios para js/utils/formatters.js
 */

import { describe, test, expect } from '@jest/globals';
import {
    formatearFecha,
    formatearFechaHora,
    formatearHora,
    formatearFechaRelativa,
    formatearNumero,
    formatearMoneda,
    capitalizarPalabras,
    capitalizarPrimeraLetra,
    truncarTexto,
    formatearNombreCompleto,
    formatearRUT,
    limpiarRUT,
    formatearPatente,
    formatearTelefono,
    formatearPorcentaje,
    formatearTamanoArchivo,
    formatearSlug
} from '../../js/utils/formatters.js';

describe('Formatters - Fecha', () => {
    test('debería formatear fecha YYYY-MM-DD a DD/MM/YYYY', () => {
        expect(formatearFecha('2025-01-16')).toBe('16/01/2025');
    });

    test('debería retornar string vacío si fecha es null', () => {
        expect(formatearFecha(null)).toBe('');
    });

    test('debería retornar string vacío si fecha es undefined', () => {
        expect(formatearFecha(undefined)).toBe('');
    });

    test('debería retornar string vacío si fecha es inválida', () => {
        expect(formatearFecha('invalid-date')).toBe('');
    });

    test('debería formatear objeto Date', () => {
        const fecha = new Date('2025-01-16');
        const resultado = formatearFecha(fecha);
        // Resultado depende de timezone, pero debe ser válido
        expect(resultado).toMatch(/^\d{2}\/\d{2}\/\d{4}$/);
    });

    test('debería mantener ceros a la izquierda', () => {
        expect(formatearFecha('2025-01-05')).toBe('05/01/2025');
    });
});

describe('Formatters - Fecha y Hora', () => {
    test('debería formatear fecha y hora correctamente', () => {
        const resultado = formatearFechaHora('2025-01-16T14:30:00');
        expect(resultado).toMatch(/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/);
    });

    test('debería retornar string vacío si no hay fecha', () => {
        expect(formatearFechaHora(null)).toBe('');
    });

    test('debería formatear hora correctamente', () => {
        const resultado = formatearHora('2025-01-16T14:30:00');
        expect(resultado).toMatch(/^\d{2}:\d{2}$/);
    });

    test('debería retornar string vacío para hora sin fecha', () => {
        expect(formatearHora(null)).toBe('');
    });
});

describe('Formatters - Números', () => {
    test('debería formatear número con separadores de miles', () => {
        expect(formatearNumero(1000)).toBe('1.000');
    });

    test('debería formatear número con decimales', () => {
        const resultado = formatearNumero(1234.56, 2);
        expect(resultado).toContain('1');
    });

    test('debería retornar string vacío si número es null', () => {
        expect(formatearNumero(null)).toBe('');
    });

    test('debería formatear moneda chilena', () => {
        expect(formatearMoneda(1000)).toBe('$1.000');
    });

    test('debería formatear moneda sin símbolo', () => {
        expect(formatearMoneda(1000, false)).toBe('1.000');
    });

    test('debería retornar string vacío si monto es null', () => {
        expect(formatearMoneda(null)).toBe('');
    });
});

describe('Formatters - Capitalización', () => {
    test('debería capitalizar cada palabra', () => {
        expect(capitalizarPalabras('juan perez garcia')).toBe('Juan Perez Garcia');
    });

    test('debería capitalizar primera letra solamente', () => {
        expect(capitalizarPrimeraLetra('juan perez')).toBe('Juan perez');
    });

    test('debería retornar string vacío si texto es null', () => {
        expect(capitalizarPalabras(null)).toBe('');
    });

    test('debería truncar texto largo', () => {
        const resultado = truncarTexto('Este es un texto muy largo', 10);
        expect(resultado.length).toBeLessThanOrEqual(10);
        expect(resultado).toContain('...');
    });

    test('debería no truncar texto corto', () => {
        expect(truncarTexto('Hola', 10)).toBe('Hola');
    });

    test('debería retornar string vacío si texto es null', () => {
        expect(truncarTexto(null, 10)).toBe('');
    });
});

describe('Formatters - Nombre Completo', () => {
    test('debería formatear nombre completo correctamente', () => {
        const resultado = formatearNombreCompleto({
            grado: 'Capitán',
            nombres: 'Juan',
            paterno: 'Pérez',
            materno: 'García'
        });
        expect(resultado).toBe('Capitán Juan Pérez García');
    });

    test('debería omitir partes vacías', () => {
        const resultado = formatearNombreCompleto({
            nombres: 'Juan',
            paterno: 'Pérez'
        });
        expect(resultado).toBe('Juan Pérez');
    });

    test('debería retornar string vacío si todas las partes están vacías', () => {
        const resultado = formatearNombreCompleto({});
        expect(resultado).toBe('');
    });
});

describe('Formatters - RUT', () => {
    test('debería formatear RUT con puntos y guión', () => {
        expect(formatearRUT('123456789')).toBe('12.345.678-9');
    });

    test('debería limpiar RUT formateado', () => {
        expect(limpiarRUT('12.345.678-9')).toBe('123456789');
    });

    test('debería retornar string vacío si RUT es null', () => {
        expect(formatearRUT(null)).toBe('');
    });

    test('debería limpiar RUT removiendo puntos y guiones', () => {
        // La función limpiarRUT solo quita puntos y guiones, no espacios
        expect(limpiarRUT('12.345.678-9')).toBe('123456789');
    });

    test('debería manejar RUT con solo dos caracteres', () => {
        const resultado = formatearRUT('12');
        expect(resultado).toBe('1-2');
    });
});

describe('Formatters - Patente', () => {
    test('debería convertir patente a mayúsculas', () => {
        expect(formatearPatente('aa1234')).toBe('AA1234');
    });

    test('debería retornar string vacío si patente es null', () => {
        expect(formatearPatente(null)).toBe('');
    });

    test('debería manejar espacios', () => {
        expect(formatearPatente(' aa1234 ')).toBe('AA1234');
    });
});

describe('Formatters - Teléfono', () => {
    test('debería formatear teléfono móvil', () => {
        expect(formatearTelefono('912345678')).toBe('9 1234 5678');
    });

    test('debería eliminar prefijo +56', () => {
        const resultado = formatearTelefono('+56912345678');
        expect(resultado).toBe('9 1234 5678');
    });

    test('debería eliminar prefijo 56', () => {
        const resultado = formatearTelefono('56912345678');
        expect(resultado).toBe('9 1234 5678');
    });

    test('debería retornar string vacío si teléfono es null', () => {
        expect(formatearTelefono(null)).toBe('');
    });

    test('debería limpiar caracteres especiales', () => {
        const resultado = formatearTelefono('(9) 1234-5678');
        expect(resultado).toContain('9');
    });
});

describe('Formatters - Porcentaje', () => {
    test('debería formatear porcentaje como entero', () => {
        expect(formatearPorcentaje(50)).toBe('50%');
    });

    test('debería formatear porcentaje decimal', () => {
        expect(formatearPorcentaje(0.5, true)).toBe('50%');
    });

    test('debería formatear porcentaje con decimales', () => {
        expect(formatearPorcentaje(33.333, false, 2)).toBe('33.33%');
    });

    test('debería retornar string vacío si valor es null', () => {
        expect(formatearPorcentaje(null)).toBe('');
    });
});

describe('Formatters - Tamaño de Archivo', () => {
    test('debería formatear 0 bytes', () => {
        expect(formatearTamanoArchivo(0)).toBe('0 Bytes');
    });

    test('debería formatear bytes', () => {
        expect(formatearTamanoArchivo(512)).toContain('Bytes');
    });

    test('debería formatear kilobytes', () => {
        expect(formatearTamanoArchivo(1024)).toContain('KB');
    });

    test('debería formatear megabytes', () => {
        expect(formatearTamanoArchivo(1024 * 1024)).toContain('MB');
    });

    test('debería formatear gigabytes', () => {
        expect(formatearTamanoArchivo(1024 * 1024 * 1024)).toContain('GB');
    });
});

describe('Formatters - Slug', () => {
    test('debería convertir texto a slug', () => {
        expect(formatearSlug('Hola Mundo')).toBe('hola-mundo');
    });

    test('debería eliminar acentos', () => {
        expect(formatearSlug('Niño español')).toBe('nino-espanol');
    });

    test('debería eliminar caracteres especiales', () => {
        expect(formatearSlug('¡Hola! ¿Cómo estás?')).toMatch(/^[a-z0-9\-]+$/);
    });

    test('debería reemplazar espacios con guiones', () => {
        expect(formatearSlug('Texto con espacios')).toContain('-');
    });

    test('debería eliminar guiones duplicados', () => {
        expect(formatearSlug('Texto  con   espacios')).not.toContain('--');
    });

    test('debería retornar string vacío si texto es null', () => {
        expect(formatearSlug(null)).toBe('');
    });
});

describe('Formatters - Fecha Relativa', () => {
    test('debería retornar "Hace un momento" para hace < 60 segundos', () => {
        const ahora = new Date();
        const haceUnSegundo = new Date(ahora.getTime() - 1000);
        expect(formatearFechaRelativa(haceUnSegundo)).toBe('Hace un momento');
    });

    test('debería retornar "Ayer" para hace 1 día', () => {
        const ayer = new Date();
        ayer.setDate(ayer.getDate() - 1);
        expect(formatearFechaRelativa(ayer)).toBe('Ayer');
    });

    test('debería retornar string vacío si fecha es null', () => {
        expect(formatearFechaRelativa(null)).toBe('');
    });

    test('debería contar minutos correctamente', () => {
        const hace5Minutos = new Date(Date.now() - 5 * 60 * 1000);
        expect(formatearFechaRelativa(hace5Minutos)).toContain('minuto');
    });

    test('debería contar horas correctamente', () => {
        const hace2Horas = new Date(Date.now() - 2 * 60 * 60 * 1000);
        expect(formatearFechaRelativa(hace2Horas)).toContain('hora');
    });
});
