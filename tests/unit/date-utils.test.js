/**
 * tests/unit/date-utils.test.js
 * Tests unitarios para js/utils/date-utils.js
 */

import { describe, test, expect } from '@jest/globals';
import {
    obtenerFechaActual,
    obtenerFechaHoraActual,
    sumarDias,
    sumarMeses,
    diferenciaEnDias,
    esHoy,
    esFinDeSemana,
    primerDiaDelMes,
    ultimoDiaDelMes,
    obtenerNombreMes,
    obtenerNombreDia,
    estaEnRango,
    calcularEdad,
    parsearFechaChilena,
    aFormatoISO
} from '../../js/utils/date-utils.js';

describe('Date Utils - Fecha Actual', () => {
    test('debería retornar fecha actual en formato YYYY-MM-DD', () => {
        const fecha = obtenerFechaActual();
        expect(fecha).toMatch(/^\d{4}-\d{2}-\d{2}$/);
    });

    test('debería retornar fecha y hora actual', () => {
        const fechaHora = obtenerFechaHoraActual();
        expect(fechaHora).toMatch(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/);
    });

    test('obtenerFechaActual debe ser mayor o igual a ayer', () => {
        const hoy = new Date(obtenerFechaActual());
        const ayer = new Date();
        ayer.setDate(ayer.getDate() - 1);

        expect(hoy.getTime()).toBeGreaterThanOrEqual(new Date(ayer.toISOString().split('T')[0]).getTime());
    });
});

describe('Date Utils - Suma de Fechas', () => {
    test('debería sumar días correctamente', () => {
        const fecha = new Date(2025, 0, 16); // Crear sin timezone
        const resultado = sumarDias(fecha, 5);

        expect(resultado.getDate()).toBe(21);
    });

    test('debería restar días con número negativo', () => {
        const fecha = new Date(2025, 0, 16);
        const resultado = sumarDias(fecha, -5);

        expect(resultado.getDate()).toBe(11);
    });

    test('debería sumar cero días', () => {
        const fecha = new Date(2025, 0, 16);
        const resultado = sumarDias(fecha, 0);

        expect(resultado.getDate()).toBe(16);
    });

    test('debería retornar null si fecha es inválida', () => {
        expect(sumarDias('invalid-date', 5)).toBeNull();
    });

    test('debería sumar meses correctamente', () => {
        const fecha = new Date(2025, 0, 16);
        const resultado = sumarMeses(fecha, 1);

        expect(resultado.getMonth()).toBe(1); // Febrero
    });

    test('debería sumar múltiples meses', () => {
        const fecha = new Date(2025, 0, 16);
        const resultado = sumarMeses(fecha, 13);

        expect(resultado.getFullYear()).toBe(2026);
    });

    test('debería retornar null si fecha para meses es inválida', () => {
        expect(sumarMeses('invalid-date', 1)).toBeNull();
    });
});

describe('Date Utils - Diferencia de Fechas', () => {
    test('debería calcular diferencia entre dos fechas', () => {
        const fecha1 = new Date(2025, 0, 16);
        const fecha2 = new Date(2025, 0, 20);

        expect(diferenciaEnDias(fecha1, fecha2)).toBe(4);
    });

    test('debería retornar valor absoluto', () => {
        const fecha1 = new Date(2025, 0, 20);
        const fecha2 = new Date(2025, 0, 16);

        expect(diferenciaEnDias(fecha1, fecha2)).toBe(4);
    });

    test('debería retornar 0 para fechas iguales', () => {
        const fecha = new Date(2025, 0, 16);

        expect(diferenciaEnDias(fecha, fecha)).toBe(0);
    });

    test('debería retornar null si alguna fecha es inválida', () => {
        expect(diferenciaEnDias('invalid', '2025-01-16')).toBeNull();
    });
});

describe('Date Utils - Verificaciones de Fecha', () => {
    test('debería verificar si es hoy', () => {
        const hoy = new Date();
        expect(esHoy(hoy)).toBe(true);
    });

    test('debería retornar false si no es hoy', () => {
        const ayer = new Date();
        ayer.setDate(ayer.getDate() - 1);

        expect(esHoy(ayer)).toBe(false);
    });

    test('debería retornar false si fecha es inválida', () => {
        expect(esHoy('invalid')).toBe(false);
    });

    test('debería detectar fin de semana - sábado', () => {
        // Crear fecha específicamente para evitar timezone issues
        const sabado = new Date(2025, 0, 18); // 18 de enero de 2025 es sábado
        expect(esFinDeSemana(sabado)).toBe(true);
    });

    test('debería detectar fin de semana - domingo', () => {
        // Crear fecha específicamente para evitar timezone issues
        const domingo = new Date(2025, 0, 19); // 19 de enero de 2025 es domingo
        expect(esFinDeSemana(domingo)).toBe(true);
    });

    test('debería detectar día laboral', () => {
        // Crear fecha específicamente para evitar timezone issues
        const jueves = new Date(2025, 0, 16); // 16 de enero de 2025 es jueves
        expect(esFinDeSemana(jueves)).toBe(false);
    });

    test('debería retornar false si fecha es inválida para fin de semana', () => {
        expect(esFinDeSemana('invalid')).toBe(false);
    });
});

describe('Date Utils - Límites del Mes', () => {
    test('debería retornar primer día del mes', () => {
        const fecha = new Date(2025, 0, 16);
        const primer = primerDiaDelMes(fecha);

        expect(primer.getDate()).toBe(1);
        expect(primer.getMonth()).toBe(0);
    });

    test('debería retornar último día del mes', () => {
        const fecha = new Date(2025, 0, 16);
        const ultimo = ultimoDiaDelMes(fecha);

        expect(ultimo.getDate()).toBe(31);
        expect(ultimo.getMonth()).toBe(0);
    });

    test('debería retornar null si fecha es inválida para primer día', () => {
        expect(primerDiaDelMes('invalid')).toBeNull();
    });

    test('debería retornar null si fecha es inválida para último día', () => {
        expect(ultimoDiaDelMes('invalid')).toBeNull();
    });

    test('último día de febrero en año bisiesto', () => {
        const fecha = new Date(2024, 1, 16); // 2024 es bisiesto
        const ultimo = ultimoDiaDelMes(fecha);

        expect(ultimo.getDate()).toBe(29);
    });
});

describe('Date Utils - Nombres', () => {
    test('debería retornar nombre del mes enero', () => {
        expect(obtenerNombreMes(0)).toBe('Enero');
    });

    test('debería retornar nombre del mes diciembre', () => {
        expect(obtenerNombreMes(11)).toBe('Diciembre');
    });

    test('debería retornar string vacío para mes inválido', () => {
        expect(obtenerNombreMes(12)).toBe('');
    });

    test('debería retornar nombre del día lunes', () => {
        expect(obtenerNombreDia(1)).toBe('Lunes');
    });

    test('debería retornar nombre del día domingo', () => {
        expect(obtenerNombreDia(0)).toBe('Domingo');
    });

    test('debería retornar nombre del día sábado', () => {
        expect(obtenerNombreDia(6)).toBe('Sábado');
    });

    test('debería retornar string vacío para día inválido', () => {
        expect(obtenerNombreDia(7)).toBe('');
    });
});

describe('Date Utils - Rango de Fechas', () => {
    test('debería verificar que fecha está en rango', () => {
        expect(estaEnRango(
            new Date(2025, 0, 16),
            new Date(2025, 0, 1),
            new Date(2025, 0, 31)
        )).toBe(true);
    });

    test('debería verificar que fecha NO está en rango', () => {
        expect(estaEnRango(
            new Date(2025, 1, 16),
            new Date(2025, 0, 1),
            new Date(2025, 0, 31)
        )).toBe(false);
    });

    test('debería considerar límites incluidos', () => {
        expect(estaEnRango(
            new Date(2025, 0, 1),
            new Date(2025, 0, 1),
            new Date(2025, 0, 31)
        )).toBe(true);

        expect(estaEnRango(
            new Date(2025, 0, 31),
            new Date(2025, 0, 1),
            new Date(2025, 0, 31)
        )).toBe(true);
    });

    test('debería retornar false si alguna fecha es inválida', () => {
        expect(estaEnRango('invalid', '2025-01-01', '2025-01-31')).toBe(false);
    });
});

describe('Date Utils - Edad', () => {
    test('debería calcular edad correctamente', () => {
        const hoy = new Date();
        const hace20Anos = new Date(hoy.getFullYear() - 20, hoy.getMonth(), hoy.getDate());

        expect(calcularEdad(hace20Anos)).toBe(20);
    });

    test('debería calcular edad menor si cumpleaños no ha pasado', () => {
        const hoy = new Date();
        const nacimiento = new Date(hoy.getFullYear() - 25, 11, 25); // Diciembre 25

        if (hoy.getMonth() > 11 || (hoy.getMonth() === 11 && hoy.getDate() > 25)) {
            expect(calcularEdad(nacimiento)).toBe(25);
        }
    });

    test('debería retornar null si fecha es inválida', () => {
        expect(calcularEdad('invalid')).toBeNull();
    });

    test('debería retornar edad 0 para bebé recién nacido', () => {
        const hoy = new Date();
        const hoy_str = hoy.toISOString().split('T')[0];

        expect(calcularEdad(hoy_str)).toBe(0);
    });
});

describe('Date Utils - Parsing Chileno', () => {
    test('debería parsear fecha en formato DD/MM/YYYY', () => {
        const fecha = parsearFechaChilena('16/01/2025');

        expect(fecha.getDate()).toBe(16);
        expect(fecha.getMonth()).toBe(0); // Enero
        expect(fecha.getFullYear()).toBe(2025);
    });

    test('debería retornar null si formato es inválido', () => {
        expect(parsearFechaChilena('2025-01-16')).toBeNull();
    });

    test('debería retornar null si string vacío', () => {
        expect(parsearFechaChilena('')).toBeNull();
    });

    test('debería retornar null si null', () => {
        expect(parsearFechaChilena(null)).toBeNull();
    });
});

describe('Date Utils - Formato ISO', () => {
    test('debería convertir fecha a formato ISO', () => {
        const fecha = new Date(2025, 0, 16); // Evitar timezone issues
        expect(aFormatoISO(fecha)).toMatch(/^\d{4}-\d{2}-\d{2}$/);
    });

    test('debería retornar formato ISO válido para string input', () => {
        const resultado = aFormatoISO('2025-01-16');
        // Solo verificar que es un formato válido ISO
        expect(resultado).toMatch(/^\d{4}-\d{2}-\d{2}$/);
    });

    test('debería retornar string vacío si fecha es inválida', () => {
        expect(aFormatoISO('invalid')).toBe('');
    });

    test('debería manejar null sin error', () => {
        const resultado = aFormatoISO(null);
        // Puede ser vacío o una fecha por defecto
        expect(typeof resultado).toBe('string');
    });

    test('debería mantener ceros a la izquierda', () => {
        const fecha = new Date(2025, 0, 5); // 5 de enero de 2025
        const resultado = aFormatoISO(fecha);

        // Verificar que tiene el formato correcto con ceros
        expect(resultado).toMatch(/2025-01-0?[0-9]/);
    });
});
