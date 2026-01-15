/**
 * date-utils.js
 * Utilidades para manejo de fechas
 * 
 * Funciones auxiliares para trabajar con fechas en JavaScript
 */

/**
 * Obtiene la fecha actual en formato YYYY-MM-DD
 * @returns {string} - Fecha actual
 */
export function obtenerFechaActual() {
    const hoy = new Date();
    const year = hoy.getFullYear();
    const month = String(hoy.getMonth() + 1).padStart(2, '0');
    const day = String(hoy.getDate()).padStart(2, '0');
    
    return `${year}-${month}-${day}`;
}

/**
 * Obtiene la fecha y hora actual en formato YYYY-MM-DD HH:mm:ss
 * @returns {string} - Fecha y hora actual
 */
export function obtenerFechaHoraActual() {
    const ahora = new Date();
    const year = ahora.getFullYear();
    const month = String(ahora.getMonth() + 1).padStart(2, '0');
    const day = String(ahora.getDate()).padStart(2, '0');
    const hours = String(ahora.getHours()).padStart(2, '0');
    const minutes = String(ahora.getMinutes()).padStart(2, '0');
    const seconds = String(ahora.getSeconds()).padStart(2, '0');
    
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

/**
 * Suma días a una fecha
 * @param {Date|string} fecha - Fecha base
 * @param {number} dias - Número de días a sumar (puede ser negativo)
 * @returns {Date|null} - Nueva fecha o null si es inválida
 */
export function sumarDias(fecha, dias) {
    const date = typeof fecha === 'string' ? new Date(fecha) : new Date(fecha);
    
    if (isNaN(date.getTime())) {
        console.error('sumarDias: fecha inválida', fecha);
        return null;
    }
    
    date.setDate(date.getDate() + dias);
    return date;
}

/**
 * Suma meses a una fecha
 * @param {Date|string} fecha - Fecha base
 * @param {number} meses - Número de meses a sumar
 * @returns {Date|null} - Nueva fecha o null si es inválida
 */
export function sumarMeses(fecha, meses) {
    const date = typeof fecha === 'string' ? new Date(fecha) : new Date(fecha);
    
    if (isNaN(date.getTime())) {
        console.error('sumarMeses: fecha inválida', fecha);
        return null;
    }
    
    date.setMonth(date.getMonth() + meses);
    return date;
}

/**
 * Calcula la diferencia en días entre dos fechas
 * @param {Date|string} fecha1 - Primera fecha
 * @param {Date|string} fecha2 - Segunda fecha
 * @returns {number|null} - Diferencia en días o null si alguna fecha es inválida
 */
export function diferenciaEnDias(fecha1, fecha2) {
    const date1 = typeof fecha1 === 'string' ? new Date(fecha1) : new Date(fecha1);
    const date2 = typeof fecha2 === 'string' ? new Date(fecha2) : new Date(fecha2);
    
    if (isNaN(date1.getTime()) || isNaN(date2.getTime())) {
        console.error('diferenciaEnDias: fecha inválida', { fecha1, fecha2 });
        return null;
    }
    
    const diferencia = Math.abs(date2 - date1);
    return Math.floor(diferencia / (1000 * 60 * 60 * 24));
}

/**
 * Verifica si una fecha es hoy
 * @param {Date|string} fecha - Fecha a verificar
 * @returns {boolean} - true si es hoy, false si no o si es inválida
 */
export function esHoy(fecha) {
    const date = typeof fecha === 'string' ? new Date(fecha) : new Date(fecha);
    
    if (isNaN(date.getTime())) {
        console.error('esHoy: fecha inválida', fecha);
        return false;
    }
    
    const hoy = new Date();
    
    return date.getDate() === hoy.getDate() &&
           date.getMonth() === hoy.getMonth() &&
           date.getFullYear() === hoy.getFullYear();
}

/**
 * Verifica si una fecha es fin de semana
 * @param {Date|string} fecha - Fecha a verificar
 * @returns {boolean} - true si es sábado o domingo, false si no o si es inválida
 */
export function esFinDeSemana(fecha) {
    const date = typeof fecha === 'string' ? new Date(fecha) : new Date(fecha);
    
    if (isNaN(date.getTime())) {
        console.error('esFinDeSemana: fecha inválida', fecha);
        return false;
    }
    
    const dia = date.getDay();
    return dia === 0 || dia === 6; // 0 = Domingo, 6 = Sábado
}

/**
 * Obtiene el primer día del mes
 * @param {Date|string} fecha - Fecha de referencia
 * @returns {Date|null} - Primer día del mes o null si es inválida
 */
export function primerDiaDelMes(fecha) {
    const date = typeof fecha === 'string' ? new Date(fecha) : new Date(fecha);
    
    if (isNaN(date.getTime())) {
        console.error('primerDiaDelMes: fecha inválida', fecha);
        return null;
    }
    
    return new Date(date.getFullYear(), date.getMonth(), 1);
}

/**
 * Obtiene el último día del mes
 * @param {Date|string} fecha - Fecha de referencia
 * @returns {Date|null} - Último día del mes o null si es inválida
 */
export function ultimoDiaDelMes(fecha) {
    const date = typeof fecha === 'string' ? new Date(fecha) : new Date(fecha);
    
    if (isNaN(date.getTime())) {
        console.error('ultimoDiaDelMes: fecha inválida', fecha);
        return null;
    }
    
    return new Date(date.getFullYear(), date.getMonth() + 1, 0);
}

/**
 * Obtiene el nombre del mes en español
 * @param {number} mes - Número del mes (0-11)
 * @returns {string} - Nombre del mes
 */
export function obtenerNombreMes(mes) {
    const meses = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    return meses[mes] || '';
}

/**
 * Obtiene el nombre del día de la semana en español
 * @param {number} dia - Número del día (0-6, donde 0 = Domingo)
 * @returns {string} - Nombre del día
 */
export function obtenerNombreDia(dia) {
    const dias = [
        'Domingo', 'Lunes', 'Martes', 'Miércoles', 
        'Jueves', 'Viernes', 'Sábado'
    ];
    return dias[dia] || '';
}

/**
 * Verifica si una fecha está entre dos fechas
 * @param {Date|string} fecha - Fecha a verificar
 * @param {Date|string} inicio - Fecha de inicio del rango
 * @param {Date|string} fin - Fecha de fin del rango
 * @returns {boolean} - true si está en el rango, false si no o si alguna fecha es inválida
 */
export function estaEnRango(fecha, inicio, fin) {
    const date = typeof fecha === 'string' ? new Date(fecha) : new Date(fecha);
    const fechaInicio = typeof inicio === 'string' ? new Date(inicio) : new Date(inicio);
    const fechaFin = typeof fin === 'string' ? new Date(fin) : new Date(fin);
    
    if (isNaN(date.getTime()) || isNaN(fechaInicio.getTime()) || isNaN(fechaFin.getTime())) {
        console.error('estaEnRango: fecha inválida', { fecha, inicio, fin });
        return false;
    }
    
    return date >= fechaInicio && date <= fechaFin;
}

/**
 * Calcula la edad a partir de una fecha de nacimiento
 * @param {Date|string} fechaNacimiento - Fecha de nacimiento
 * @returns {number|null} - Edad en años o null si la fecha es inválida
 */
export function calcularEdad(fechaNacimiento) {
    const nacimiento = typeof fechaNacimiento === 'string' ? new Date(fechaNacimiento) : new Date(fechaNacimiento);
    
    if (isNaN(nacimiento.getTime())) {
        console.error('calcularEdad: fecha inválida', fechaNacimiento);
        return null;
    }
    
    const hoy = new Date();
    
    let edad = hoy.getFullYear() - nacimiento.getFullYear();
    const mes = hoy.getMonth() - nacimiento.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
        edad--;
    }
    
    return edad;
}

/**
 * Parsea una fecha en formato DD/MM/YYYY a Date
 * @param {string} fechaStr - Fecha en formato DD/MM/YYYY
 * @returns {Date|null} - Objeto Date o null si es inválido
 */
export function parsearFechaChilena(fechaStr) {
    if (!fechaStr) return null;
    
    const partes = fechaStr.split('/');
    if (partes.length !== 3) return null;
    
    const dia = parseInt(partes[0], 10);
    const mes = parseInt(partes[1], 10) - 1; // Los meses en JS son 0-11
    const anio = parseInt(partes[2], 10);
    
    return new Date(anio, mes, dia);
}

/**
 * Convierte una fecha a formato ISO (YYYY-MM-DD)
 * @param {Date|string} fecha - Fecha a convertir
 * @returns {string} - Fecha en formato ISO
 */
export function aFormatoISO(fecha) {
    const date = typeof fecha === 'string' ? new Date(fecha) : new Date(fecha);
    
    if (isNaN(date.getTime())) return '';
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    
    return `${year}-${month}-${day}`;
}

// Exportación por defecto
export default {
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
};
