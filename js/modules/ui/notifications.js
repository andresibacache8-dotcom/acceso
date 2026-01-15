/**
 * notifications.js
 * Módulo para manejo de notificaciones tipo Toast
 *
 * @description
 * Centraliza la lógica de mostrar notificaciones visuales (toasts)
 * con diferentes tipos: success, error, warning, info
 *
 * @author Refactorización 2025-10-25
 */

let bsToast;
let toastEl;

/**
 * Inicializa el componente de notificaciones
 * Debe llamarse una sola vez al cargar la página
 *
 * @param {HTMLElement} toastElement - El elemento DOM del toast
 * @returns {void}
 *
 * @example
 * const toastEl = document.getElementById('toast');
 * initNotifications(toastEl);
 */
export function initNotifications(toastElement) {
    toastEl = toastElement;
    bsToast = new bootstrap.Toast(toastEl);
}

/**
 * Muestra una notificación tipo Toast
 *
 * @param {string} message - Mensaje a mostrar en el cuerpo del toast
 * @param {string} [type='info'] - Tipo de notificación: 'success', 'error', 'warning', 'info'
 * @param {string} [title='Notificación'] - Título del toast
 * @returns {void}
 *
 * @example
 * showToast('Operación completada', 'success', 'Éxito');
 * showToast('Algo salió mal', 'error', 'Error');
 * showToast('Advertencia importante', 'warning', 'Cuidado');
 * showToast('Información general', 'info', 'Info');
 */
export function showToast(message, type = 'info', title = 'Notificación') {
    if (!toastEl || !bsToast) {
        console.warn('Toast no inicializado. Llamar initNotifications() primero.');
        console.log(`[${type.toUpperCase()}] ${title}: ${message}`);
        return;
    }

    const toastHeader = toastEl.querySelector('.toast-header');
    const toastBody = toastEl.querySelector('.toast-body');
    const toastTitle = toastEl.querySelector('.me-auto');
    const toastIcon = toastEl.querySelector('.toast-icon');

    // Limpiar clases de tipo anteriores
    toastIcon.className = 'toast-icon'; // Resetear clases
    toastHeader.className = 'toast-header'; // Resetear clases

    // Establecer clases y contenido según el tipo
    switch (type) {
        case 'success':
            toastIcon.classList.add('text-success');
            toastIcon.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
            toastHeader.classList.add('text-bg-success');
            break;
        case 'error':
            toastIcon.classList.add('text-danger');
            toastIcon.innerHTML = '<i class="bi bi-x-circle-fill"></i>';
            toastHeader.classList.add('text-bg-danger');
            break;
        case 'warning':
            toastIcon.classList.add('text-warning');
            toastIcon.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>';
            toastHeader.classList.add('text-bg-warning');
            break;
        case 'info':
        default:
            toastIcon.classList.add('text-info');
            toastIcon.innerHTML = '<i class="bi bi-info-circle-fill"></i>';
            toastHeader.classList.add('text-bg-info');
            break;
    }

    toastTitle.textContent = title;
    toastBody.textContent = message;
    bsToast.show();
}

/**
 * Alias para showToast - mantiene compatibilidad
 * @deprecated Usar showToast en su lugar
 */
export function toast(message, type, title) {
    return showToast(message, type, title);
}
