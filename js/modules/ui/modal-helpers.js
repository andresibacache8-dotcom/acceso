/**
 * modal-helpers.js
 * Módulo con funciones de ayuda para manejo de modales
 *
 * @description
 * Proporciona funciones reutilizables para abrir, cerrar y limpiar modales
 * Utiliza Bootstrap Modal por debajo
 *
 * @author Refactorización 2025-10-25
 */

/**
 * Abre un modal por su ID
 *
 * @param {string} modalId - ID del modal a abrir (sin #)
 * @returns {void}
 *
 * @example
 * openModal('empresaModal');
 */
export function openModal(modalId) {
    const modalElement = document.getElementById(modalId);
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } else {
        console.warn(`Modal con ID "${modalId}" no encontrado`);
    }
}

/**
 * Cierra un modal por su ID
 *
 * @param {string} modalId - ID del modal a cerrar (sin #)
 * @returns {void}
 *
 * @example
 * closeModal('empresaModal');
 */
export function closeModal(modalId) {
    const modalElement = document.getElementById(modalId);
    if (modalElement) {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
    } else {
        console.warn(`Modal con ID "${modalId}" no encontrado`);
    }
}

/**
 * Limpia todos los campos de un formulario dentro de un modal
 *
 * @param {string} modalId - ID del modal (sin #)
 * @param {string} [formSelector='form'] - Selector del formulario dentro del modal
 * @returns {void}
 *
 * @example
 * clearModalForm('empresaModal');
 * clearModalForm('empleadoModal', '#empleadoForm');
 */
export function clearModalForm(modalId, formSelector = 'form') {
    const modalElement = document.getElementById(modalId);
    if (modalElement) {
        const form = modalElement.querySelector(formSelector);
        if (form) {
            form.reset();
            // Limpiar mensajes de validación si existen
            form.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
        }
    } else {
        console.warn(`Modal con ID "${modalId}" no encontrado`);
    }
}

/**
 * Abre un modal y limpia su formulario
 * Combinación de openModal + clearModalForm
 *
 * @param {string} modalId - ID del modal (sin #)
 * @param {string} [formSelector='form'] - Selector del formulario
 * @returns {void}
 *
 * @example
 * openAndClearModal('empresaModal');
 */
export function openAndClearModal(modalId, formSelector = 'form') {
    clearModalForm(modalId, formSelector);
    openModal(modalId);
}

/**
 * Cierra un modal y limpia su formulario
 * Combinación de closeModal + clearModalForm
 *
 * @param {string} modalId - ID del modal (sin #)
 * @param {string} [formSelector='form'] - Selector del formulario
 * @returns {void}
 *
 * @example
 * closeAndClearModal('empresaModal');
 */
export function closeAndClearModal(modalId, formSelector = 'form') {
    closeModal(modalId);
    clearModalForm(modalId, formSelector);
}

/**
 * Obtiene el elemento del modal
 *
 * @param {string} modalId - ID del modal (sin #)
 * @returns {HTMLElement|null} El elemento del modal o null si no existe
 *
 * @example
 * const modalElement = getModal('empresaModal');
 */
export function getModal(modalId) {
    return document.getElementById(modalId);
}

/**
 * Verifica si un modal está abierto
 *
 * @param {string} modalId - ID del modal (sin #)
 * @returns {boolean} true si el modal está abierto, false en caso contrario
 *
 * @example
 * if (isModalOpen('empresaModal')) { ... }
 */
export function isModalOpen(modalId) {
    const modalElement = document.getElementById(modalId);
    if (modalElement) {
        return modalElement.classList.contains('show');
    }
    return false;
}
