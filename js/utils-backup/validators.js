/**
 * validators.js
 * Funciones de validación reutilizables
 * 
 * Este módulo contiene todas las validaciones que se usan en la aplicación:
 * - Validación de RUT chileno
 * - Validación de patentes chilenas
 * - Validación de emails
 * - Validación de fechas
 */

/**
 * Valida el formato de un RUT chileno (solo números sin DV)
 * @param {string} rut - RUT a validar (solo números, sin puntos ni DV)
 * @returns {boolean} - true si el RUT es válido
 */
export function validarRUT(rut) {
    if (!rut) return false;
    
    // Limpiar espacios
    rut = rut.trim();
    
    // Debe ser solo números, entre 7 y 8 dígitos
    return /^[0-9]{7,8}$/.test(rut);
}

/**
 * Limpia un RUT eliminando caracteres no numéricos
 * @param {string} rut - RUT a limpiar
 * @returns {string} - RUT solo con números
 */
export function limpiarRUT(rut) {
    if (!rut) return '';
    
    // Eliminar todo excepto números
    return rut.replace(/\D/g, '');
}

/**
 * Valida el formato de una patente chilena
 * Soporta todos los formatos oficiales de Chile
 * @param {string} patente - Patente a validar
 * @returns {boolean} - true si la patente es válida
 */
export function validarPatenteChilena(patente) {
    // Convertir a mayúsculas
    patente = patente.toUpperCase().trim();
    
    // Formato antiguo: dos letras y cuatro dígitos (AA1234)
    const formatoAntiguo = /^[A-Z]{2}[0-9]{4}$/;
    
    // Formato nuevo: cuatro letras y dos dígitos (BCDF12)
    // No se usan vocales (A, E, I, O, U) para evitar palabras ofensivas
    const formatoNuevo = /^[B-DF-HJ-NP-TV-Z]{4}[0-9]{2}$/;
    
    // Formato de motos nuevo: tres letras y dos números (BCD12)
    const formatoMotoNuevo = /^[B-DF-HJ-NP-TV-Z]{3}[0-9]{2}$/;

    // Formato de motos antiguo: dos letras y tres números (AB123)
    const formatoMotoAntiguo = /^[A-Z]{2}[0-9]{3}$/;

    // Formato de remolques: tres letras y tres números (ABC123)
    const formatoRemolque = /^[A-Z]{3}[0-9]{3}$/;
    
    return formatoAntiguo.test(patente) || 
           formatoNuevo.test(patente) || 
           formatoMotoNuevo.test(patente) ||
           formatoMotoAntiguo.test(patente) ||
           formatoRemolque.test(patente);
}

/**
 * Obtiene información sobre el formato de una patente
 * @param {string} patente - Patente a analizar
 * @returns {Object} - Información del formato { tipo, valida, mensaje }
 */
export function obtenerInfoPatente(patente) {
    patente = patente.toUpperCase().trim();
    
    const formatoAntiguo = /^[A-Z]{2}[0-9]{4}$/;
    const formatoNuevo = /^[B-DF-HJ-NP-TV-Z]{4}[0-9]{2}$/;
    const formatoMotoNuevo = /^[B-DF-HJ-NP-TV-Z]{3}[0-9]{2}$/;
    const formatoMotoAntiguo = /^[A-Z]{2}[0-9]{3}$/;
    const formatoRemolque = /^[A-Z]{3}[0-9]{3}$/;
    
    if (formatoAntiguo.test(patente)) {
        return { tipo: 'Auto antiguo', valida: true, ejemplo: 'AA1234' };
    }
    if (formatoNuevo.test(patente)) {
        return { tipo: 'Auto nuevo', valida: true, ejemplo: 'BCDF12' };
    }
    if (formatoMotoNuevo.test(patente)) {
        return { tipo: 'Moto nueva', valida: true, ejemplo: 'BCD12' };
    }
    if (formatoMotoAntiguo.test(patente)) {
        return { tipo: 'Moto antigua', valida: true, ejemplo: 'AB123' };
    }
    if (formatoRemolque.test(patente)) {
        return { tipo: 'Remolque', valida: true, ejemplo: 'ABC123' };
    }
    
    return { 
        tipo: 'Desconocido', 
        valida: false, 
        mensaje: 'Formatos válidos: AA1234, BCDF12, BCD12, AB123, ABC123' 
    };
}

/**
 * Valida un email
 * @param {string} email - Email a validar
 * @returns {boolean} - true si el email es válido
 */
export function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Valida que una fecha no sea mayor a hoy
 * @param {string} fecha - Fecha en formato YYYY-MM-DD
 * @returns {boolean} - true si la fecha es válida (no futura)
 */
export function validarFechaNoFutura(fecha) {
    if (!fecha) return false;
    
    const fechaInput = new Date(fecha);
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    
    return fechaInput <= hoy;
}

/**
 * Valida que una fecha de fin sea mayor o igual a la fecha de inicio
 * @param {string} fechaInicio - Fecha inicio en formato YYYY-MM-DD
 * @param {string} fechaFin - Fecha fin en formato YYYY-MM-DD
 * @returns {boolean} - true si las fechas son válidas
 */
export function validarRangoFechas(fechaInicio, fechaFin) {
    if (!fechaInicio || !fechaFin) return false;
    
    const inicio = new Date(fechaInicio);
    const fin = new Date(fechaFin);
    
    return fin >= inicio;
}

/**
 * Valida un número de teléfono chileno
 * Acepta formatos: +56912345678, 912345678, 221234567
 * @param {string} telefono - Teléfono a validar
 * @returns {boolean} - true si el teléfono es válido
 */
export function validarTelefonoChileno(telefono) {
    // Eliminar espacios y caracteres especiales
    telefono = telefono.replace(/[\s\-\(\)]/g, '');
    
    // Móvil: +56912345678 o 912345678 (9 dígitos, empieza con 9)
    const regexMovil = /^(\+?56)?9[0-9]{8}$/;
    
    // Fijo: +56221234567 o 221234567 (9 dígitos, empieza con 2-7)
    const regexFijo = /^(\+?56)?[2-7][0-9]{8}$/;
    
    return regexMovil.test(telefono) || regexFijo.test(telefono);
}

/**
 * Valida que un string no esté vacío
 * @param {string} valor - Valor a validar
 * @returns {boolean} - true si no está vacío
 */
export function validarNoVacio(valor) {
    return valor && valor.trim().length > 0;
}

/**
 * Valida que un número esté dentro de un rango
 * @param {number} valor - Valor a validar
 * @param {number} min - Valor mínimo
 * @param {number} max - Valor máximo
 * @returns {boolean} - true si está en el rango
 */
export function validarRango(valor, min, max) {
    const num = parseFloat(valor);
    return !isNaN(num) && num >= min && num <= max;
}

/**
 * Valida una contraseña
 * Requisitos: mínimo 8 caracteres, al menos una mayúscula, una minúscula y un número
 * @param {string} password - Contraseña a validar
 * @returns {Object} - { valida: boolean, mensaje: string }
 */
export function validarPassword(password) {
    if (password.length < 8) {
        return { valida: false, mensaje: 'La contraseña debe tener al menos 8 caracteres' };
    }
    
    if (!/[a-z]/.test(password)) {
        return { valida: false, mensaje: 'La contraseña debe contener al menos una minúscula' };
    }
    
    if (!/[A-Z]/.test(password)) {
        return { valida: false, mensaje: 'La contraseña debe contener al menos una mayúscula' };
    }
    
    if (!/[0-9]/.test(password)) {
        return { valida: false, mensaje: 'La contraseña debe contener al menos un número' };
    }
    
    return { valida: true, mensaje: 'Contraseña válida' };
}

// Exportación por defecto de un objeto con todas las funciones
export default {
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
};
