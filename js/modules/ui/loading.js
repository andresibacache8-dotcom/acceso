/**
 * loading.js
 * Módulo para manejo del spinner de carga
 *
 * @description
 * Centraliza la lógica de mostrar/ocultar el spinner de carga
 * durante operaciones asincrónicas
 *
 * @author Refactorización 2025-10-25
 */

let loadingSpinner;

/**
 * Inicializa el módulo de loading
 * Debe llamarse una sola vez al cargar la página
 *
 * @param {HTMLElement} spinnerElement - El elemento DOM del spinner
 * @returns {void}
 *
 * @example
 * const spinnerEl = document.getElementById('loading-spinner');
 * initLoading(spinnerEl);
 */
export function initLoading(spinnerElement) {
    loadingSpinner = spinnerElement;
}

/**
 * Muestra el spinner de carga
 *
 * @returns {void}
 *
 * @example
 * showLoadingSpinner();
 */
export function showLoadingSpinner() {
    if (loadingSpinner) {
        loadingSpinner.classList.remove('d-none');
    }
}

/**
 * Oculta el spinner de carga
 *
 * @returns {void}
 *
 * @example
 * hideLoadingSpinner();
 */
export function hideLoadingSpinner() {
    if (loadingSpinner) {
        loadingSpinner.classList.add('d-none');
    }
}

/**
 * Ejecuta una función asincrónica mostrando el spinner
 * Oculta el spinner automáticamente al completar
 *
 * @param {Function} asyncFn - Función asincrónica a ejecutar
 * @returns {Promise} Promesa que se resuelve cuando la función completa
 *
 * @example
 * await withLoading(async () => {
 *     const data = await api.fetchData();
 *     return data;
 * });
 */
export async function withLoading(asyncFn) {
    try {
        showLoadingSpinner();
        return await asyncFn();
    } finally {
        hideLoadingSpinner();
    }
}
