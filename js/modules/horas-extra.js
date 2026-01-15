/**
 * horas-extra.js
 * Módulo para gestión de horas extra
 *
 * @description
 * Maneja la lógica de creación, visualización y eliminación de registros de horas extra
 * Incluye búsqueda de personal y validación de datos
 *
 * @author Refactorización 2025-10-25
 */

import horasExtraApi from '../api/horas-extra-api.js';
import personalApi from '../api/personal-api.js';
import { validarRUT, limpiarRUT } from '../utils/validators.js';
import { showToast } from './ui/notifications.js';

let mainContent;

/**
 * Inicializa el módulo de horas extra
 * Debe llamarse una sola vez con el elemento principal del contenido
 *
 * @param {HTMLElement} contentElement - El elemento contenedor principal (main)
 * @returns {void}
 */
export function initHorasExtraModule(contentElement) {
    mainContent = contentElement;
    setupHorasExtraForm();
    loadAndRenderHorasExtraHistory();
}

/**
 * Configura los eventos del formulario de horas extra
 * @private
 */
function setupHorasExtraForm() {
    let personalList = [];

    const form = mainContent.querySelector('#horas-extra-form');
    const rutInput = mainContent.querySelector('#he-rut-input');
    const addPersonBtn = mainContent.querySelector('#he-add-person-btn');
    const rutLookupNombre = mainContent.querySelector('#he-rut-lookup-nombre');
    const personalListUl = mainContent.querySelector('#he-personal-list');
    const autorizaInput = mainContent.querySelector('#he-autorizado-por');
    const autorizaDisplay = mainContent.querySelector('#he-nombre-autoriza');
    const motivoSelect = mainContent.querySelector('#he-motivo');
    const motivoOtroContainer = mainContent.querySelector('#he-motivo-otro-container');
    const fechaFinInput = mainContent.querySelector('#he-fecha-fin');

    // Establecer fecha por defecto
    if (fechaFinInput) {
        fechaFinInput.value = new Date().toISOString().split('T')[0];
    }

    // Renderizar lista de personal
    function renderPersonalList() {
        personalListUl.innerHTML = personalList.map((person, index) => `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>${person.nombre} (${person.rut})</span>
                <button type="button" class="btn-close" aria-label="Remove" data-index="${index}"></button>
            </li>
        `).join('');
    }

    // Eventos
    rutInput.addEventListener('keyup', (e) => {
        handleRutLookup(e.target, rutLookupNombre);
    });

    addPersonBtn.addEventListener('click', async () => {
        const rut = rutInput.value.trim();
        if (!rut) return;

        if (personalList.some(p => p.rut === rut)) {
            showToast('Esta persona ya está en la lista.', 'warning');
            return;
        }

        try {
            const persona = await personalApi.findByRut(rut);
            if (persona && persona.Nombres) {
                const nombreCompleto = `${persona.Grado || ''} ${persona.Nombres || ''} ${persona.Paterno || ''} ${persona.Materno || ''}`.trim();
                personalList.push({ rut: persona.NrRut, nombre: nombreCompleto });
                renderPersonalList();
                rutInput.value = '';
                rutLookupNombre.textContent = '';
                rutInput.classList.remove('is-valid', 'is-invalid');
            } else {
                showToast('RUT no encontrado.', 'error');
            }
        } catch (error) {
            showToast(error.message, 'error');
        }
    });

    personalListUl.addEventListener('click', (e) => {
        if (e.target.matches('.btn-close')) {
            const index = parseInt(e.target.dataset.index, 10);
            personalList.splice(index, 1);
            renderPersonalList();
        }
    });

    autorizaInput.addEventListener('blur', (e) => handleRutLookup(e.target, autorizaDisplay));
    motivoSelect.addEventListener('change', () => {
        motivoOtroContainer.style.display = motivoSelect.value === 'OTRO' ? 'block' : 'none';
    });

    form.addEventListener('submit', (e) => {
        handleHorasExtraSubmit(e, personalList, renderPersonalList);
    });
}

/**
 * Busca una persona por RUT o nombre
 * @private
 */
async function handleRutLookup(inputElement, displayElement) {
    const rut = inputElement.value.trim();
    displayElement.textContent = '';
    displayElement.removeAttribute('data-nombre-completo');
    inputElement.classList.remove('is-invalid', 'is-valid');

    if (!rut) return;

    if (!validarRUT(rut)) {
        displayElement.textContent = 'RUT inválido (ingrese solo números, 7-8 dígitos, sin dígito verificador)';
        displayElement.classList.remove('text-success');
        displayElement.classList.add('text-danger');
        inputElement.classList.add('is-invalid');
        return;
    }

    try {
        // Primero intentar buscar por RUT
        const personaByRut = await personalApi.findByRut(rut);
        if (personaByRut && personaByRut.Nombres) {
            const materno = (personaByRut.Materno === 'undefined' || personaByRut.Materno === null) ? '' : personaByRut.Materno;
            const nombreCompleto = `${personaByRut.Grado || ''} ${personaByRut.Nombres || ''} ${personaByRut.Paterno || ''} ${materno}`.trim();
            displayElement.textContent = nombreCompleto;
            displayElement.setAttribute('data-nombre-completo', nombreCompleto);
            displayElement.classList.remove('text-danger');
            displayElement.classList.add('text-success');
            inputElement.classList.add('is-valid');
            return;
        }

        // Si no se encuentra por RUT, intentar buscar como FUNCIONARIO
        const results = await personalApi.search(rut, 'FUNCIONARIO');
        if (results && results.length > 0) {
            const persona = results[0];
            const materno = (persona.Materno === 'undefined' || persona.Materno === null) ? '' : persona.Materno;
            const nombreCompleto = `${persona.Grado || ''} ${persona.Nombres || ''} ${persona.Paterno || ''} ${materno}`.trim();
            displayElement.textContent = nombreCompleto;
            displayElement.setAttribute('data-nombre-completo', nombreCompleto);
            displayElement.classList.remove('text-danger');
            displayElement.classList.add('text-success');
            inputElement.classList.add('is-valid');
            return;
        }

        // Si no se encuentra por ningún método
        displayElement.textContent = 'RUT o nombre no encontrado';
        displayElement.classList.remove('text-success');
        displayElement.classList.add('text-danger');
        inputElement.classList.add('is-invalid');

    } catch (error) {
        showToast(error.message, 'error');
        displayElement.textContent = 'Error al buscar RUT/Nombre';
        displayElement.classList.add('text-danger');
        inputElement.classList.add('is-invalid');
    }
}

/**
 * Carga y renderiza el historial de horas extra
 * @private
 */
async function loadAndRenderHorasExtraHistory() {
    try {
        const historyData = await horasExtraApi.getAll();
        renderHorasExtraTable(historyData);
    } catch (error) {
        showToast(error.message, 'error');
        const logTableBody = mainContent.querySelector('#horas-extra-log-table');
        if (logTableBody) {
            logTableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger p-4">Error al cargar el historial.</td></tr>`;
        }
    }
}

/**
 * Renderiza la tabla de horas extra
 * @private
 */
function renderHorasExtraTable(data) {
    const logTableBody = mainContent.querySelector('#horas-extra-log-table');
    if (!logTableBody) return;

    if (data.length === 0) {
        logTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted p-4">No hay registros de horas extra.</td></tr>';
        return;
    }

    logTableBody.innerHTML = '';
    data.forEach(record => {
        const fechaTermino = new Date(record.fecha_hora_termino);
        const fechaFormateada = fechaTermino.toLocaleDateString('es-CL');
        const horaFormateada = fechaTermino.toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });
        const motivoCompleto = record.motivo_detalle ? (record.motivo + ': ' + record.motivo_detalle) : record.motivo;
        logTableBody.innerHTML += '<tr>' +
            '<td><div>' + record.personal_nombre + '</div>' +
            '<small class="text-muted">RUT: ' + record.personal_rut + '</small></td>' +
            '<td>' + fechaFormateada + '</td>' +
            '<td>' + motivoCompleto + '</td>' +
            '<td>' + horaFormateada + '</td>' +
            '<td>' +
                '<button class="btn btn-sm btn-outline-danger delete-he-btn" data-id="' + record.id + '" title="Eliminar">' +
                    '<i class="bi bi-trash"></i>' +
                '</button>' +
            '</td>' +
        '</tr>';
    });

    bindHorasExtraTableEvents();
}

/**
 * Vincula eventos a la tabla de horas extra
 * @private
 */
function bindHorasExtraTableEvents() {
    mainContent.querySelectorAll('.delete-he-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const id = e.currentTarget.dataset.id;
            handleDeleteHorasExtra(id);
        });
    });
}

/**
 * Maneja la eliminación de un registro de horas extra
 * @private
 */
async function handleDeleteHorasExtra(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este registro de horas extra?')) {
        try {
            await horasExtraApi.delete(id);
            showToast('Registro eliminado correctamente.', 'success');
            loadAndRenderHorasExtraHistory();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }
}

/**
 * Maneja el envío del formulario de horas extra
 * @private
 */
async function handleHorasExtraSubmit(e, personalList, renderPersonalList) {
    const form = e.target;

    if (personalList.length === 0) {
        showToast('Debe agregar al menos una persona a la lista.', 'error');
        return;
    }

    const autorizaRut = form.querySelector('#he-autorizado-por').value;
    const autorizaDisplay = form.querySelector('#he-nombre-autoriza');
    const autorizaNombre = autorizaDisplay.getAttribute('data-nombre-completo');

    if (!autorizaRut.trim() || !autorizaNombre) {
        showToast('Debe ingresar un RUT válido para quien autoriza.', 'error');
        return;
    }

    const fechaFin = form.querySelector('#he-fecha-fin').value;
    const horaFin = form.querySelector('#he-hora-fin').value;
    const motivo = form.querySelector('#he-motivo').value;
    const motivoDetalle = form.querySelector('#he-motivo-otro').value;

    if (!fechaFin || !horaFin || !motivo) {
        showToast('Por favor, complete la fecha, hora y motivo.', 'error');
        return;
    }
    if (motivo === 'OTRO' && !motivoDetalle.trim()) {
        showToast('Por favor, especifique el motivo en el cuadro de texto.', 'error');
        return;
    }

    const data = {
        personal: personalList.map(p => ({ rut: p.rut, nombre: p.nombre })),
        fecha_hora_termino: `${fechaFin} ${horaFin}:00`,
        motivo: motivo,
        motivo_detalle: motivo === 'OTRO' ? motivoDetalle : null,
        autorizado_por_rut: autorizaRut,
        autorizado_por_nombre: autorizaNombre
    };

    try {
        await horasExtraApi.create(data);
        showToast('Registros de horas extra guardados correctamente.', 'success');
        form.reset();
        personalList.length = 0;
        if (renderPersonalList) renderPersonalList();
        autorizaDisplay.textContent = '';
        form.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-valid', 'is-invalid'));
        form.querySelector('#he-motivo-otro-container').style.display = 'none';
        form.querySelector('#he-fecha-fin').value = new Date().toISOString().split('T')[0];

        loadAndRenderHorasExtraHistory();

    } catch (error) {
        showToast(error.message, 'error');
    }
}
