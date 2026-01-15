/**
 * formatters.js
 * Funciones de formateo de datos reutilizables
 * 
 * Este módulo contiene funciones para formatear datos de diferentes tipos:
 * - Fechas y horas
 * - Números y moneda
 * - Textos y nombres
 * - RUT y patentes
 */

/**
 * Formatea una fecha en formato chileno (DD/MM/YYYY)
 * @param {string|Date} fecha - Fecha a formatear
 * @returns {string} - Fecha formateada
 */
export function formatearFecha(fecha) {
    if (!fecha) return '';
    
    let date;
    
    // Si es string en formato ISO (YYYY-MM-DD), parsear manualmente para evitar problemas de zona horaria
    if (typeof fecha === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
        const [year, month, day] = fecha.split('-').map(Number);
        date = new Date(year, month - 1, day); // mes es 0-indexed
    } else {
        date = typeof fecha === 'string' ? new Date(fecha) : fecha;
    }
    
    if (isNaN(date.getTime())) return '';
    
    const dia = String(date.getDate()).padStart(2, '0');
    const mes = String(date.getMonth() + 1).padStart(2, '0');
    const anio = date.getFullYear();
    
    return `${dia}/${mes}/${anio}`;
}

/**
 * Formatea una fecha y hora en formato chileno
 * @param {string|Date} fechaHora - Fecha y hora a formatear
 * @returns {string} - Fecha y hora formateada (DD/MM/YYYY HH:mm)
 */
export function formatearFechaHora(fechaHora) {
    if (!fechaHora) return '';
    
    const date = typeof fechaHora === 'string' ? new Date(fechaHora) : fechaHora;
    
    if (isNaN(date.getTime())) return '';
    
    const dia = String(date.getDate()).padStart(2, '0');
    const mes = String(date.getMonth() + 1).padStart(2, '0');
    const anio = date.getFullYear();
    const hora = String(date.getHours()).padStart(2, '0');
    const minutos = String(date.getMinutes()).padStart(2, '0');
    
    return `${dia}/${mes}/${anio} ${hora}:${minutos}`;
}

/**
 * Formatea solo la hora
 * @param {string|Date} fechaHora - Fecha y hora
 * @returns {string} - Hora formateada (HH:mm)
 */
export function formatearHora(fechaHora) {
    if (!fechaHora) return '';
    
    const date = typeof fechaHora === 'string' ? new Date(fechaHora) : fechaHora;
    
    if (isNaN(date.getTime())) return '';
    
    const hora = String(date.getHours()).padStart(2, '0');
    const minutos = String(date.getMinutes()).padStart(2, '0');
    
    return `${hora}:${minutos}`;
}

/**
 * Obtiene una fecha relativa (ej: "Hace 2 horas", "Ayer", "Hace 3 días")
 * @param {string|Date} fecha - Fecha a comparar con ahora
 * @returns {string} - Texto relativo
 */
export function formatearFechaRelativa(fecha) {
    if (!fecha) return '';
    
    const date = typeof fecha === 'string' ? new Date(fecha) : fecha;
    const ahora = new Date();
    const diferencia = ahora - date;
    
    const segundos = Math.floor(diferencia / 1000);
    const minutos = Math.floor(segundos / 60);
    const horas = Math.floor(minutos / 60);
    const dias = Math.floor(horas / 24);
    
    if (segundos < 60) return 'Hace un momento';
    if (minutos < 60) return `Hace ${minutos} minuto${minutos > 1 ? 's' : ''}`;
    if (horas < 24) return `Hace ${horas} hora${horas > 1 ? 's' : ''}`;
    if (dias === 1) return 'Ayer';
    if (dias < 7) return `Hace ${dias} días`;
    if (dias < 30) return `Hace ${Math.floor(dias / 7)} semana${Math.floor(dias / 7) > 1 ? 's' : ''}`;
    if (dias < 365) return `Hace ${Math.floor(dias / 30)} mes${Math.floor(dias / 30) > 1 ? 'es' : ''}`;
    return `Hace ${Math.floor(dias / 365)} año${Math.floor(dias / 365) > 1 ? 's' : ''}`;
}

/**
 * Formatea un número con separadores de miles
 * @param {number} numero - Número a formatear
 * @param {number} decimales - Número de decimales (default: 0)
 * @returns {string} - Número formateado
 */
export function formatearNumero(numero, decimales = 0) {
    if (numero === null || numero === undefined) return '';
    
    return Number(numero).toLocaleString('es-CL', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales
    });
}

/**
 * Formatea un número como moneda chilena
 * @param {number} monto - Monto a formatear
 * @param {boolean} mostrarSimbolo - Si debe mostrar el símbolo $ (default: true)
 * @returns {string} - Monto formateado
 */
export function formatearMoneda(monto, mostrarSimbolo = true) {
    if (monto === null || monto === undefined) return '';
    
    const formateado = Number(monto).toLocaleString('es-CL', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
    
    return mostrarSimbolo ? `$${formateado}` : formateado;
}

/**
 * Capitaliza la primera letra de cada palabra
 * @param {string} texto - Texto a formatear
 * @returns {string} - Texto capitalizado
 */
export function capitalizarPalabras(texto) {
    if (!texto) return '';
    
    return texto
        .toLowerCase()
        .split(' ')
        .map(palabra => palabra.charAt(0).toUpperCase() + palabra.slice(1))
        .join(' ');
}

/**
 * Capitaliza solo la primera letra del texto
 * @param {string} texto - Texto a formatear
 * @returns {string} - Texto con primera letra capitalizada
 */
export function capitalizarPrimeraLetra(texto) {
    if (!texto) return '';
    return texto.charAt(0).toUpperCase() + texto.slice(1).toLowerCase();
}

/**
 * Trunca un texto a una longitud máxima
 * @param {string} texto - Texto a truncar
 * @param {number} maxLength - Longitud máxima
 * @param {string} sufijo - Sufijo a agregar (default: '...')
 * @returns {string} - Texto truncado
 */
export function truncarTexto(texto, maxLength, sufijo = '...') {
    if (!texto) return '';
    if (texto.length <= maxLength) return texto;
    
    return texto.substring(0, maxLength - sufijo.length) + sufijo;
}

/**
 * Formatea un nombre completo uniendo partes no vacías
 * @param {Object} partes - Objeto con las partes del nombre
 * @param {string} partes.grado - Grado militar/profesional
 * @param {string} partes.nombres - Nombres
 * @param {string} partes.paterno - Apellido paterno
 * @param {string} partes.materno - Apellido materno
 * @returns {string} - Nombre completo formateado
 */
export function formatearNombreCompleto({ grado = '', nombres = '', paterno = '', materno = '' }) {
    const partes = [grado, nombres, paterno, materno]
        .filter(parte => parte && parte.trim() !== '' && parte !== 'undefined')
        .map(parte => parte.trim());
    
    return partes.join(' ');
}

/**
 * Formatea un RUT chileno con puntos y guión
 * @param {string} rut - RUT a formatear
 * @returns {string} - RUT formateado (12.345.678-9)
 */
export function formatearRUT(rut) {
    if (!rut) return '';
    
    // Eliminar cualquier formato previo
    rut = String(rut).replace(/\./g, '').replace(/-/g, '').trim();
    
    if (rut.length < 2) return rut;
    
    // Separar cuerpo y dígito verificador
    const cuerpo = rut.slice(0, -1);
    const dv = rut.slice(-1);
    
    // Formatear el cuerpo con puntos
    const cuerpoFormateado = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    
    return `${cuerpoFormateado}-${dv}`;
}

/**
 * Limpia un RUT de puntos y guiones
 * @param {string} rut - RUT a limpiar
 * @returns {string} - RUT limpio (solo números y K)
 */
export function limpiarRUT(rut) {
    if (!rut) return '';
    return String(rut).replace(/\./g, '').replace(/-/g, '').trim();
}

/**
 * Formatea una patente chilena en mayúsculas
 * @param {string} patente - Patente a formatear
 * @returns {string} - Patente en mayúsculas
 */
export function formatearPatente(patente) {
    if (!patente) return '';
    return String(patente).toUpperCase().trim();
}

/**
 * Formatea un número de teléfono chileno
 * @param {string} telefono - Teléfono a formatear
 * @returns {string} - Teléfono formateado
 */
export function formatearTelefono(telefono) {
    if (!telefono) return '';
    
    // Eliminar cualquier formato previo
    telefono = String(telefono).replace(/[\s\-\(\)]/g, '');
    
    // Si empieza con +56, quitarlo para formatear
    if (telefono.startsWith('+56')) {
        telefono = telefono.substring(3);
    } else if (telefono.startsWith('56')) {
        telefono = telefono.substring(2);
    }
    
    // Formatear según longitud
    if (telefono.length === 9) {
        // Móvil o fijo: 9 1234 5678
        return `${telefono.substring(0, 1)} ${telefono.substring(1, 5)} ${telefono.substring(5)}`;
    }
    
    return telefono;
}

/**
 * Formatea un porcentaje
 * @param {number} valor - Valor a formatear (0-100 o 0-1)
 * @param {boolean} esDecimal - Si el valor es decimal (0-1)
 * @param {number} decimales - Número de decimales
 * @returns {string} - Porcentaje formateado
 */
export function formatearPorcentaje(valor, esDecimal = false, decimales = 0) {
    if (valor === null || valor === undefined) return '';
    
    const porcentaje = esDecimal ? valor * 100 : valor;
    
    return `${porcentaje.toFixed(decimales)}%`;
}

/**
 * Formatea un tamaño de archivo
 * @param {number} bytes - Tamaño en bytes
 * @returns {string} - Tamaño formateado (ej: "1.5 MB")
 */
export function formatearTamanoArchivo(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const decimales = 2;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(decimales)) + ' ' + sizes[i];
}

/**
 * Convierte un texto a formato slug (URL-friendly)
 * @param {string} texto - Texto a convertir
 * @returns {string} - Texto en formato slug
 */
export function formatearSlug(texto) {
    if (!texto) return '';
    
    return texto
        .toLowerCase()
        .trim()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Eliminar acentos
        .replace(/[^a-z0-9\s-]/g, '') // Eliminar caracteres especiales
        .replace(/\s+/g, '-') // Reemplazar espacios con guiones
        .replace(/-+/g, '-'); // Eliminar guiones duplicados
}

// Exportación por defecto
export default {
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
};
