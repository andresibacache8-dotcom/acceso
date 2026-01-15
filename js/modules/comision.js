/**
 * comision.js
 * Módulo independiente para gestión de Personal en Comisión
 *
 * @description
 * Maneja la lógica de CRUD para personal en comisión
 * Incluye búsqueda, edición, eliminación y renderizado de tablas
 *
 * @author Refactorización 2025-10-28
 */

import comisionApi from '../api/comision-api.js';
import { showToast } from './ui/notifications.js';

let mainContent;
let comisionData = [];
let comisionModalInstance = null;

/**
 * Inicializa el módulo de comisión
 * Debe llamarse una sola vez con el elemento principal del contenido
 *
 * @param {HTMLElement} contentElement - El elemento contenedor principal (main)
 * @returns {void}
 */
export function initComisionModule(contentElement) {
    mainContent = contentElement;
    setupComisionModal();
    setupEventListeners();
    loadComisionData();
}

/**
 * Configura el modal de comisión
 * @private
 */
function setupComisionModal() {
    const modalEl = document.getElementById('comision-modal');
    if (modalEl && !comisionModalInstance) {
        modalEl.innerHTML = getComisionModalTemplate();
        comisionModalInstance = new bootstrap.Modal(modalEl);
        const form = modalEl.querySelector('#comision-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                handleComisionFormSubmit(e, comisionModalInstance);
            });
        }
    }
}

/**
 * Configura los event listeners del módulo
 * @private
 */
function setupEventListeners() {
    const addComisionBtn = document.getElementById('add-comision-btn');
    const searchComisionInput = document.getElementById('search-comision-tabla');

    if (addComisionBtn) {
        addComisionBtn.addEventListener('click', () => openComisionModal());
    }
    if (searchComisionInput) {
        searchComisionInput.addEventListener('input', handleComisionTableSearch);
    }

    // Event delegation para botones de editar/eliminar
    mainContent.addEventListener('click', (e) => {
        const editComisionBtn = e.target.closest('.edit-comision-btn');
        const deleteComisionBtn = e.target.closest('.delete-comision-btn');

        if (editComisionBtn) {
            openComisionModal(editComisionBtn.dataset.id);
        } else if (deleteComisionBtn) {
            deleteComision(deleteComisionBtn.dataset.id);
        }
    });
}

/**
 * Carga datos de comisión
 * @private
 */
async function loadComisionData() {
    try {
        comisionData = await comisionApi.getAll();
        renderComisionTable(comisionData);
    } catch (error) {
        showToast(error.message, 'error');
    }
}

/**
 * Renderiza la tabla de comisión
 * @private
 */
function renderComisionTable(data) {
    const tableBody = mainContent.querySelector('#comision-table-body');
    if (!tableBody) return;

    tableBody.innerHTML = data.length === 0
        ? '<tr><td colspan="8" class="text-center text-muted p-4">No se encontraron resultados.</td></tr>'
        : data.map(c => `
            <tr>
                <td>${c.nombre_completo}</td>
                <td>${c.rut}</td>
                <td>${c.unidad_origen}</td>
                <td>${c.unidad_poc}</td>
                <td>${c.fecha_inicio}</td>
                <td>${c.fecha_fin}</td>
                <td><span class="badge ${c.estado === 'Activo' ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'}">${c.estado}</span></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary edit-comision-btn" data-id="${c.id}" title="Editar"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger delete-comision-btn" data-id="${c.id}" title="Eliminar"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `).join('');
}

/**
 * Busca en la tabla de comisión
 * @private
 */
function handleComisionTableSearch(e) {
    const query = e.target.value.toLowerCase().trim();
    const filteredData = comisionData.filter(c => {
        const nombre = (c.nombre_completo || '').toLowerCase();
        const rut = (c.rut || '').toLowerCase();
        const unidad = (c.unidad_origen || '').toLowerCase();
        return nombre.includes(query) || rut.includes(query) || unidad.includes(query);
    });
    renderComisionTable(filteredData);
}

/**
 * Abre el modal de comisión
 * @private
 */
function openComisionModal(id = null) {
    if (!comisionModalInstance) return;

    const modalEl = document.getElementById('comision-modal');
    if (!modalEl) {
        console.error('Modal comisión no encontrado');
        return;
    }

    const form = modalEl.querySelector('#comision-form');
    const modalTitle = modalEl.querySelector('#comision-modal-title');

    if (!form || !modalTitle) {
        console.error('Formulario o título del modal de comisión no encontrado');
        return;
    }

    form.reset();
    form.classList.remove('was-validated');
    form.elements.id.value = '';

    if (id) {
        modalTitle.textContent = 'Editar Personal en Comisión';
        const comision = comisionData.find(c => c.id == id);
        if (comision) {
            populateComisionForm(form, comision);
        }
    } else {
        modalTitle.textContent = 'Agregar Personal en Comisión';
    }
    comisionModalInstance.show();
}

/**
 * Rellena el formulario de comisión
 * @private
 */
function populateComisionForm(form, comision) {
    const fields = ['id', 'rut', 'grado', 'nombres', 'paterno', 'materno',
                    'unidad_origen', 'unidad_poc', 'fecha_inicio', 'fecha_fin',
                    'motivo', 'poc_nombre', 'poc_anexo'];

    fields.forEach(field => {
        if (form.elements[field]) {
            form.elements[field].value = comision[field] || '';
        }
    });
}

/**
 * Maneja el envío del formulario de comisión
 * @private
 */
async function handleComisionFormSubmit(e, modal) {
    const form = e.target;
    if (!form.checkValidity()) {
        e.stopPropagation();
        form.classList.add('was-validated');
        return;
    }

    const id = form.elements.id.value;
    const data = {};

    // Campos que NO deben convertirse a mayúsculas (fechas, RUT e IDs)
    const excludeFromUpperCase = ['id', 'rut', 'fecha_inicio', 'fecha_fin'];

    for (const element of form.elements) {
        if (element.name) {
            let value = element.value;

            // Convertir a mayúsculas: nombres, paterno, materno, grado, unidad, poc_nombre, motivo
            // NO convertir: rut, fechas, id
            if (!excludeFromUpperCase.includes(element.name) && value &&
                (element.type === 'text' || element.type === 'textarea')) {
                value = value.toUpperCase();
            }

            data[element.name] = value;
        }
    }

    try {
        if (id) {
            await comisionApi.update(data);
            showToast('Registro actualizado correctamente.', 'success');
        } else {
            await comisionApi.create(data);
            showToast('Personal en comisión agregado correctamente.', 'success');
        }
        modal.hide();
        await loadComisionData();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

/**
 * Elimina un registro de comisión
 * @private
 */
async function deleteComision(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este registro?')) {
        try {
            await comisionApi.delete(id);
            showToast('Registro eliminado correctamente.', 'success');
            await loadComisionData();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }
}
