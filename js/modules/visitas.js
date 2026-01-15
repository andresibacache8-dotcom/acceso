/**
 * visitas.js
 * Módulo para gestión de visitas
 *
 * @description
 * Maneja la lógica de CRUD para visitas, incluyendo lista negra
 * Soporta dos tipos: Visita (con POC) y Familiar (de personal)
 *
 * @author Refactorización 2025-10-25
 */

import visitasApi from '../api/visitas-api.js';
import personalApi from '../api/personal-api.js';
import { showToast } from './ui/notifications.js';

let mainContent;
let visitasData = [];
let visitaModalInstance = null;
let allPersonalData = [];

/**
 * Inicializa el módulo de visitas
 *
 * @param {HTMLElement} contentElement - El elemento contenedor principal
 * @returns {void}
 */
export function initVisitasModule(contentElement) {
    mainContent = contentElement;
    setupVisitaModal();
    setupEventListeners();
    loadVisitasData();
}

/**
 * Configura el modal de visitas
 * @private
 */
function setupVisitaModal() {
    const modalEl = document.getElementById('visita-modal');
    if (modalEl && !visitaModalInstance) {
        visitaModalInstance = new bootstrap.Modal(modalEl);
        const form = modalEl.querySelector('#visita-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                handleVisitaFormSubmit(e, visitaModalInstance);
            });

            setupModalLogic(form);
        }
    }
}

/**
 * Configura la lógica específica del modal de visitas
 * @private
 */
async function setupModalLogic(form) {
    const tipoSelect = form.querySelector('#tipo');
    const pocFields = form.querySelector('#poc-fields');
    const familiarFields = form.querySelector('#familiar-fields');
    const accesoPermanenteCheckbox = form.querySelector('#acceso_permanente');
    const fechaInicioInput = form.querySelector('#fecha_inicio');
    const fechaExpiracionInput = form.querySelector('#fecha_expiracion');

    // Lógica de tipo de visita
    tipoSelect.addEventListener('change', () => {
        const selectedType = tipoSelect.value;
        pocFields.style.display = selectedType === 'Visita' ? 'flex' : 'none';
        familiarFields.style.display = selectedType === 'Familiar' ? 'flex' : 'none';

        // Limpiar campos de POC cuando se cambia a Familiar
        if (selectedType === 'Familiar') {
            form.querySelector('#poc_rut_visita').value = '';
            form.querySelector('#poc_personal_id_visita').value = '';
            form.querySelector('#poc_nombre_visita').value = '';
            form.querySelector('#poc_rut_hidden_visita').value = '';
            form.querySelector('#poc_unidad_visita').value = '';
            form.querySelector('#poc_anexo_visita').value = '';
            const pocRutDisplay = form.querySelector('#poc-rut-display');
            if (pocRutDisplay) {
                pocRutDisplay.textContent = '';
            }
            const pocSearchResultsVisita = form.querySelector('#poc-search-results-visita');
            if (pocSearchResultsVisita) {
                pocSearchResultsVisita.style.display = 'none';
            }
        }
        // Limpiar campos de Familiar cuando se cambia a Visita
        if (selectedType === 'Visita') {
            form.querySelector('#familiar_de_personal_input').value = '';
            form.querySelector('#familiar_personal_id_visita').value = '';
            form.querySelector('#familiar_nombre_visita').value = '';
            form.querySelector('#familiar_rut_hidden_visita').value = '';
            form.querySelector('#familiar_unidad').value = '';
            form.querySelector('#familiar_anexo').value = '';
            const familiarRutDisplay = form.querySelector('#familiar-rut-display');
            if (familiarRutDisplay) {
                familiarRutDisplay.textContent = '';
            }
            const familiarSearchResultsVisita = form.querySelector('#familiar-search-results-visita');
            if (familiarSearchResultsVisita) {
                familiarSearchResultsVisita.style.display = 'none';
            }
        }
    });

    // Lógica de acceso permanente y fechas
    if (accesoPermanenteCheckbox && fechaInicioInput && fechaExpiracionInput) {
        accesoPermanenteCheckbox.addEventListener('change', () => {
            updateAccessDatesState(form);
        });
    }

    // Cargar personal para autocomplete y búsqueda
    const personalList = await personalApi.getAll();
    allPersonalData = personalList; // Guardar para búsqueda dinámica
    const personalOptions = form.querySelector('#personal-options');
    const personalOptionsFamiliar = form.querySelector('#personal-options-familiar');

    const optionsHtml = personalList.map(p =>
        `<option value="${(p.Grado || '')} ${p.Nombres} ${p.Paterno}" data-id="${p.id}" data-unidad="${p.Unidad}" data-anexo="${p.anexo}"></option>`
    ).join('');

    // Configurar opciones de autocomplete si existen
    if (personalOptions) {
        personalOptions.innerHTML = optionsHtml;
    }
    if (personalOptionsFamiliar) {
        personalOptionsFamiliar.innerHTML = optionsHtml;
    }

    // Configurar búsqueda dinámica de POC para visitas
    const pocRutInput = form.querySelector('#poc_rut_visita');
    const pocSearchResultsVisita = form.querySelector('#poc-search-results-visita');
    if (pocRutInput) {
        pocRutInput.addEventListener('input', handlePocSearchVisita);
        // Event delegation para seleccionar POC
        if (pocSearchResultsVisita) {
            pocSearchResultsVisita.addEventListener('click', (e) => {
                const pocItem = e.target.closest('.poc-search-item-visita');
                if (pocItem) {
                    selectPocFromSearchVisita(pocItem);
                }
            });
        }
        // Cerrar resultados cuando se hace click fuera
        document.addEventListener('click', (e) => {
            if (pocSearchResultsVisita && !pocRutInput.contains(e.target) && !pocSearchResultsVisita.contains(e.target)) {
                pocSearchResultsVisita.style.display = 'none';
            }
        });
    }

    // Autocomplete para POC
    const pocInput = form.querySelector('#poc_personal_input');
    if (pocInput && personalOptions) {
        pocInput.addEventListener('input', () => {
            const selectedOption = Array.from(personalOptions.options).find(opt => opt.value === pocInput.value);
            if (selectedOption) {
                form.querySelector('#poc_personal_id').value = selectedOption.dataset.id;
                form.querySelector('#poc_unidad').value = selectedOption.dataset.unidad || '';
                form.querySelector('#poc_anexo').value = selectedOption.dataset.anexo || '';
            }
        });
    }

    // Configurar búsqueda dinámica de Familiar para visitas
    const familiarInput = form.querySelector('#familiar_de_personal_input');
    const familiarSearchResultsVisita = form.querySelector('#familiar-search-results-visita');
    if (familiarInput) {
        familiarInput.addEventListener('input', handleFamiliarSearchVisita);
        // Event delegation para seleccionar Familiar
        if (familiarSearchResultsVisita) {
            familiarSearchResultsVisita.addEventListener('click', (e) => {
                const familiarItem = e.target.closest('.familiar-search-item-visita');
                if (familiarItem) {
                    selectFamiliarFromSearchVisita(familiarItem);
                }
            });
        }
        // Cerrar resultados cuando se hace click fuera
        document.addEventListener('click', (e) => {
            if (familiarSearchResultsVisita && !familiarInput.contains(e.target) && !familiarSearchResultsVisita.contains(e.target)) {
                familiarSearchResultsVisita.style.display = 'none';
            }
        });
    }

    // Validación de RUT en tiempo real
    const rutInput = form.querySelector('#rut');
    const rutFeedback = form.querySelector('#rut-feedback');

    if (rutInput) {
        rutInput.addEventListener('input', () => {
            const visitaId = form.elements.id?.value || null;
            validateRUT(rutInput, rutFeedback, visitaId);
        });

        // Validar al abrir el modal si ya tiene valor
        if (rutInput.value) {
            const visitaId = form.elements.id?.value || null;
            validateRUT(rutInput, rutFeedback, visitaId);
        }
    }
}

/**
 * Configura los event listeners del módulo
 * @private
 */
function setupEventListeners() {
    const addVisitaBtn = document.getElementById('add-visita-btn');
    const searchVisitaInput = document.getElementById('search-visita-tabla');
    const moduleContainer = document.getElementById('visitas-module-container');

    if (addVisitaBtn) {
        addVisitaBtn.addEventListener('click', () => openVisitaModal());
    }
    if (searchVisitaInput) {
        searchVisitaInput.addEventListener('input', handleVisitasTableSearch);
    }

    // Event delegation para tabla
    if (moduleContainer) {
        moduleContainer.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.edit-visita-btn');
            const deleteBtn = e.target.closest('.delete-visita-btn');
            const blacklistBtn = e.target.closest('.toggle-blacklist-btn');

            if (editBtn) {
                openVisitaModal(editBtn.dataset.id);
            } else if (deleteBtn) {
                deleteVisita(deleteBtn.dataset.id);
            } else if (blacklistBtn) {
                handleToggleBlacklist(blacklistBtn.dataset.id, blacklistBtn.dataset.blacklisted);
            }
        });
    }
}

/**
 * Carga datos de visitas
 * @private
 */
async function loadVisitasData() {
    try {
        visitasData = await visitasApi.getAll();
        renderVisitasTable(visitasData);
    } catch (error) {
        showToast(error.message, 'error');
    }
}

/**
 * Renderiza la tabla de visitas
 * @private
 */
function renderVisitasTable(data) {
    const tableBody = mainContent.querySelector('#visita-table-body');
    if (!tableBody) return;

    tableBody.innerHTML = data.length === 0
        ? '<tr><td colspan="7" class="text-center text-muted p-4">No se encontraron resultados.</td></tr>'
        : data.map(v => {
            const nombreCompleto = `${v.nombre || ''} ${v.paterno || ''} ${v.materno || ''}`.trim();
            let detalleTipo = '-';
            if (v.tipo === 'Familiar' && v.familiar_nombre) {
                detalleTipo = `Familiar de: ${v.familiar_nombre}`;
            } else if (v.tipo === 'Visita' && v.poc_nombre) {
                detalleTipo = `POC: ${v.poc_nombre}`;
            }

            return `
                <tr class="${v.en_lista_negra == 1 ? 'table-danger' : ''}">
                    <td>${nombreCompleto}</td>
                    <td>${v.rut || 'N/A'}</td>
                    <td><span class="badge bg-info-subtle text-info-emphasis">${v.tipo}</span></td>
                    <td>${detalleTipo}</td>
                    <td><span class="badge ${v.status === 'autorizado' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis'}">${v.status}</span></td>
                    <td>${v.acceso_permanente == 1 ? 'Permanente' : (v.fecha_expiracion || 'Sin fecha')}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary edit-visita-btn" data-id="${v.id}" title="Editar"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger delete-visita-btn" data-id="${v.id}" title="Eliminar"><i class="bi bi-trash"></i></button>
                        <button class="btn btn-sm ${v.en_lista_negra == 1 ? 'btn-outline-success' : 'btn-outline-dark'} toggle-blacklist-btn" data-id="${v.id}" data-blacklisted="${v.en_lista_negra}" title="${v.en_lista_negra == 1 ? 'Quitar de lista negra' : 'Añadir a lista negra'}">
                            <i class="bi ${v.en_lista_negra == 1 ? 'bi-unlock' : 'bi-lock'}"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
}

/**
 * Actualiza el estado de los campos de fecha según acceso permanente
 * @private
 */
function updateAccessDatesState(form) {
    const accesoPermanenteCheckbox = form.querySelector('#acceso_permanente');
    const fechaInicioInput = form.querySelector('#fecha_inicio');
    const fechaExpiracionInput = form.querySelector('#fecha_expiracion');
    const fechaExpiracionRequiredSpan = document.querySelector('#fecha-expiracion-required');

    if (!accesoPermanenteCheckbox || !fechaInicioInput || !fechaExpiracionInput) {
        return;
    }

    // Fecha de inicio SIEMPRE está habilitada, es obligatoria y SE GUARDA
    fechaInicioInput.disabled = false;
    fechaInicioInput.required = true;

    if (accesoPermanenteCheckbox.checked) {
        // Si acceso permanente está marcado: solo deshabilitar fecha de expiración (pero NO limpiar fecha inicio)
        fechaExpiracionInput.disabled = true;
        fechaExpiracionInput.value = '';
        fechaExpiracionInput.required = false;
        // Ocultar el asterisco de obligatorio
        if (fechaExpiracionRequiredSpan) {
            fechaExpiracionRequiredSpan.style.display = 'none';
        }
    } else {
        // Si acceso permanente NO está marcado: habilitar y hacer obligatoria fecha de expiración
        fechaExpiracionInput.disabled = false;
        fechaExpiracionInput.required = true;
        // Mostrar el asterisco de obligatorio
        if (fechaExpiracionRequiredSpan) {
            fechaExpiracionRequiredSpan.style.display = 'inline';
        }
    }
}

/**
 * Busca en la tabla de visitas
 * @private
 */
function handleVisitasTableSearch(e) {
    const query = e.target.value.toLowerCase().trim();
    const filteredVisitas = visitasData.filter(v => {
        const nombreCompleto = `${v.nombre || ''} ${v.paterno || ''} ${v.materno || ''}`.toLowerCase();
        return nombreCompleto.includes(query) || (v.rut || '').toLowerCase().includes(query);
    });
    renderVisitasTable(filteredVisitas);
}

/**
 * Abre el modal de visita
 * @private
 */
function openVisitaModal(id = null) {
    if (!visitaModalInstance) return;

    const modalEl = document.getElementById('visita-modal');
    const form = modalEl.querySelector('#visita-form');
    const modalTitle = modalEl.querySelector('#visita-modal-title');

    form.reset();
    form.classList.remove('was-validated');
    const pocRutDisplay = form.querySelector('#poc-rut-display');
    if (pocRutDisplay) {
        pocRutDisplay.textContent = '';
    }
    const pocPersonalIdInput = form.querySelector('#poc_personal_id_visita');
    if (pocPersonalIdInput) {
        pocPersonalIdInput.value = '';
    }
    const familiarRutDisplay = form.querySelector('#familiar-rut-display');
    if (familiarRutDisplay) {
        familiarRutDisplay.textContent = '';
    }
    const familiarPersonalIdInput = form.querySelector('#familiar_personal_id_visita');
    if (familiarPersonalIdInput) {
        familiarPersonalIdInput.value = '';
    }

    if (id) {
        modalTitle.textContent = 'Editar Visita';
        const visita = visitasData.find(v => v.id == id);
        if (visita) {
            populateVisitaForm(form, visita);
        }
    } else {
        modalTitle.textContent = 'Agregar Visita';
    }

    // Actualizar estado de campos de fecha según acceso_permanente
    updateAccessDatesState(form);

    // Disparar eventos para actualizar lógica
    form.querySelector('#tipo').dispatchEvent(new Event('change'));
    form.querySelector('#acceso_permanente').dispatchEvent(new Event('change'));

    visitaModalInstance.show();
}

/**
 * Rellena el formulario de visita
 * @private
 */
function populateVisitaForm(form, visita) {
    const fields = ['id', 'nombre', 'paterno', 'materno', 'rut', 'movil', 'tipo',
        'fecha_inicio', 'fecha_expiracion', 'acceso_permanente', 'en_lista_negra'];

    fields.forEach(field => {
        if (form.elements[field]) {
            if (form.elements[field].type === 'checkbox') {
                form.elements[field].checked = visita[field] == 1 || !!visita[field];
            } else {
                form.elements[field].value = visita[field] || '';
            }
        }
    });

    // Campos condicionales
    if (visita.tipo === 'Visita') {
        const pocPersonalId = visita.poc_personal_id || '';
        const pocRut = visita.poc_rut || '';
        const pocNombre = visita.poc_nombre || '';
        form.querySelector('#poc_rut_visita').value = pocNombre;
        form.querySelector('#poc_personal_id_visita').value = pocPersonalId;
        form.querySelector('#poc_nombre_visita').value = pocNombre;
        form.querySelector('#poc_rut_hidden_visita').value = pocRut;
        const pocRutDisplay = form.querySelector('#poc-rut-display');
        if (pocRutDisplay) {
            pocRutDisplay.textContent = pocRut;
        }
        form.querySelector('#poc_unidad_visita').value = visita.poc_unidad || '';
        form.querySelector('#poc_anexo_visita').value = visita.poc_anexo || '';
    } else if (visita.tipo === 'Familiar') {
        const familiarPersonalId = visita.familiar_de_personal_id || '';
        const familiarNombre = visita.familiar_nombre || '';
        const familiarRut = visita.familiar_rut || '';
        form.querySelector('#familiar_de_personal_input').value = familiarNombre;
        form.querySelector('#familiar_personal_id_visita').value = familiarPersonalId;
        form.querySelector('#familiar_nombre_visita').value = familiarNombre;
        form.querySelector('#familiar_rut_hidden_visita').value = familiarRut;
        const familiarRutDisplay = form.querySelector('#familiar-rut-display');
        if (familiarRutDisplay) {
            familiarRutDisplay.textContent = familiarRut;
        }
        form.querySelector('#familiar_unidad').value = visita.familiar_unidad || '';
        form.querySelector('#familiar_anexo').value = visita.familiar_anexo || '';
    }
}

/**
 * Maneja el envío del formulario de visita
 * @private
 */
async function handleVisitaFormSubmit(e, modal) {
    const form = e.target;

    if (!form.checkValidity()) {
        e.stopPropagation();
        form.classList.add('was-validated');
        return;
    }

    // Validar que el RUT no esté duplicado
    const rutInput = form.querySelector('#rut');
    const rutLimpio = rutInput.value.trim().replace(/[.\-\s]/g, '');
    const visitaId = form.elements.id?.value || null;
    const rutExistente = visitasData.find(v => v.rut === rutLimpio && v.id != visitaId);

    if (rutExistente) {
        showToast('❌ Este RUT ya está registrado en otra visita. No se puede guardar.', 'error');
        return;
    }

    // Validar que si el tipo es Visita, el POC debe estar seleccionado
    // O si el tipo es Familiar, el Familiar debe estar seleccionado
    const tipo = form.elements.tipo.value;

    if (tipo === 'Visita') {
        const pocPersonalId = form.querySelector('#poc_personal_id_visita').value;

        if (!pocPersonalId) {
            showToast('Por favor selecciona un POC válido de la lista de búsqueda.', 'error');
            return;
        }
    } else if (tipo === 'Familiar') {
        const familiarPersonalId = form.querySelector('#familiar_personal_id_visita').value;

        if (!familiarPersonalId) {
            showToast('Por favor selecciona un Familiar válido de la lista de búsqueda.', 'error');
            return;
        }
    }

    const id = form.elements.id.value;
    const data = {};

    // Campos que NO deben convertirse a mayúsculas
    const excludeFromUpperCase = ['id', 'rut', 'movil', 'tipo', 'fecha_inicio', 'fecha_expiracion',
                                  'acceso_permanente', 'en_lista_negra', 'poc_personal_id',
                                  'familiar_de_personal_id', 'poc_rut', 'poc_nombre', 'poc_rut_visita'];

    for (const element of form.elements) {
        if (element.name) {
            let value = element.type === 'checkbox' ? element.checked : element.value;

            // Convertir a mayúsculas solo si es un campo de texto y no está excluido
            if (!excludeFromUpperCase.includes(element.name) && value &&
                (element.type === 'text' || element.type === 'textarea')) {
                value = value.toUpperCase();
            }

            data[element.name] = value;
        }
    }

    try {
        if (id) {
            await visitasApi.update(data);
            showToast('Visita actualizada correctamente.', 'success');
        } else {
            await visitasApi.create(data);
            showToast('Visita creada correctamente.', 'success');
        }
        modal.hide();
        await loadVisitasData();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

/**
 * Elimina una visita
 * @private
 */
async function deleteVisita(id) {
    if (confirm('¿Estás seguro de que quieres eliminar esta visita?')) {
        try {
            await visitasApi.deleteVisita(id);
            showToast('Visita eliminada correctamente.', 'success');
            await loadVisitasData();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }
}

/**
 * Alterna el estado de lista negra de una visita
 * @private
 */
async function handleToggleBlacklist(id, isBlacklisted) {
    const newStatus = isBlacklisted === 'true' ? 0 : 1;
    const actionText = newStatus === 1 ? 'añadir a la' : 'quitar de la';
    if (confirm(`¿Estás seguro de que quieres ${actionText} lista negra a esta visita?`)) {
        try {
            await visitasApi.toggleBlacklist(id, newStatus);
            showToast('Estado de lista negra actualizado.', 'success');
            await loadVisitasData();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }
}

/**
 * Valida un RUT chileno sin dígito verificador (7 u 8 dígitos)
 * @private
 */
function validateRUT(rutInput, feedbackElement, visitaId = null) {
    const rut = rutInput.value.trim();

    if (!rut) {
        feedbackElement.textContent = '';
        rutInput.classList.remove('is-valid', 'is-invalid');
        return;
    }

    // Limpiar el RUT de puntos, guiones y espacios
    const rutLimpio = rut.replace(/[.\-\s]/g, '');

    // Validar formato: solo números, 7 u 8 dígitos (sin dígito verificador)
    const isValidFormat = /^[0-9]{7,8}$/.test(rutLimpio);

    if (!isValidFormat) {
        feedbackElement.textContent = '❌ RUT inválido. Debe tener 7 u 8 dígitos (sin dígito verificador).';
        feedbackElement.className = 'form-text text-danger';
        rutInput.classList.add('is-invalid');
        rutInput.classList.remove('is-valid');
        return;
    }

    // Verificar si el RUT ya existe en visitasData (pero permitir si es la misma visita que estamos editando)
    const rutExistente = visitasData.find(v => v.rut === rutLimpio && v.id != visitaId);

    if (rutExistente) {
        feedbackElement.textContent = '⚠️ Este RUT ya está registrado en otra visita.';
        feedbackElement.className = 'form-text text-danger';
        rutInput.classList.add('is-invalid');
        rutInput.classList.remove('is-valid');
        return;
    }

    // RUT válido y no duplicado
    feedbackElement.textContent = '✓ RUT válido';
    feedbackElement.className = 'form-text text-success';
    rutInput.classList.add('is-valid');
    rutInput.classList.remove('is-invalid');
}

/**
 * Calcula el dígito verificador de un RUT chileno
 * @private
 */
function calcularDV(rut) {
    let suma = 0;
    let multiplicador = 2;

    for (let i = rut.length - 1; i >= 0; i--) {
        suma += parseInt(rut.charAt(i)) * multiplicador;
        multiplicador++;
        if (multiplicador > 7) {
            multiplicador = 2;
        }
    }

    const dv = 11 - (suma % 11);

    if (dv === 11) {
        return '0';
    } else if (dv === 10) {
        return 'K';
    } else {
        return dv.toString();
    }
}

/**
 * Maneja la búsqueda de POC mientras se escribe (filtrado client-side)
 * @private
 */
function handlePocSearchVisita(e) {
    const query = e.target.value.trim().toLowerCase();
    const form = document.getElementById('visita-form');
    const resultsContainer = form.querySelector('#poc-search-results-visita');
    const feedback = form.querySelector('#poc-rut-feedback-visita');

    if (!query || query.length < 1) {
        resultsContainer.style.display = 'none';
        feedback.textContent = '';
        return;
    }

    // Filtrar personal client-side
    const filtered = allPersonalData.filter(person => {
        const rut = (person.NrRut || person.RUT || person.rut || '').toString();
        const nombres = (person.Nombres || person.nombres || '').toLowerCase();
        const paterno = (person.Paterno || person.paterno || '').toLowerCase();
        const materno = (person.Materno || person.materno || '').toLowerCase();

        // Buscar en cualquiera de estos campos
        return (
            rut.includes(query) ||
            nombres.includes(query) ||
            paterno.includes(query) ||
            materno.includes(query)
        );
    });

    if (filtered.length > 0) {
        renderPocSearchResultsVisita(filtered, form);
        resultsContainer.style.display = 'block';
        feedback.textContent = '';
    } else {
        resultsContainer.style.display = 'none';
        feedback.textContent = 'No se encontraron resultados.';
        feedback.className = 'form-text text-warning';
    }
}

/**
 * Renderiza los resultados de búsqueda de POC
 * @private
 */
function renderPocSearchResultsVisita(results, form) {
    const container = form.querySelector('#poc-search-results-visita');
    container.innerHTML = results.map(person => {
        const grado = person.Grado || person.grado || '';
        const nombres = person.Nombres || person.nombres || '';
        const paterno = person.Paterno || person.paterno || '';
        const materno = person.Materno || person.materno || '';
        const rut = person.NrRut || person.RUT || person.rut || '';
        const anexo = person.anexo || person.Anexo || '';

        const nombreCompleto = `${grado} ${nombres} ${paterno} ${materno}`.trim();

        return `
            <button type="button" class="list-group-item list-group-item-action poc-search-item-visita"
                    data-id="${person.id}" data-rut="${rut}" data-nombre="${nombreCompleto}"
                    data-unidad="${person.Unidad || ''}" data-anexo="${anexo}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>${nombreCompleto}</strong>
                        <br>
                        <small class="text-muted">RUT: ${rut}</small>
                    </div>
                    ${anexo ? `<small class="text-muted">Anexo: ${anexo}</small>` : ''}
                </div>
            </button>
        `;
    }).join('');
}

/**
 * Selecciona un POC de la lista de búsqueda
 * @private
 */
function selectPocFromSearchVisita(element) {
    const form = document.getElementById('visita-form');
    const pocRutInput = form.querySelector('#poc_rut_visita');
    const pocPersonalIdInput = form.querySelector('#poc_personal_id_visita');
    const pocNombreInput = form.querySelector('#poc_nombre_visita');
    const pocRutHiddenInput = form.querySelector('#poc_rut_hidden_visita');
    const pocUnidadInput = form.querySelector('#poc_unidad_visita');
    const pocAnexoInput = form.querySelector('#poc_anexo_visita');
    const pocRutDisplay = form.querySelector('#poc-rut-display');
    const resultsContainer = form.querySelector('#poc-search-results-visita');

    const id = element.dataset.id;
    const rut = element.dataset.rut;
    const nombre = element.dataset.nombre;
    const unidad = element.dataset.unidad;
    const anexo = element.dataset.anexo;

    pocRutInput.value = nombre;
    pocPersonalIdInput.value = id;
    pocNombreInput.value = nombre;
    pocRutHiddenInput.value = rut;
    pocUnidadInput.value = unidad || '';
    pocAnexoInput.value = anexo || '';

    if (pocRutDisplay) {
        pocRutDisplay.textContent = rut;
    }
    if (resultsContainer) {
        resultsContainer.style.display = 'none';
    }
}

/**
 * Maneja la búsqueda de Familiar mientras se escribe
 * @private
 */
function handleFamiliarSearchVisita(e) {
    const query = e.target.value.trim().toLowerCase();
    const form = document.getElementById('visita-form');
    const resultsContainer = form.querySelector('#familiar-search-results-visita');

    if (!query || query.length < 1) {
        resultsContainer.style.display = 'none';
        return;
    }

    // Filtrar personal client-side
    const filtered = allPersonalData.filter(person => {
        const rut = (person.NrRut || person.RUT || person.rut || '').toString();
        const nombres = (person.Nombres || person.nombres || '').toLowerCase();
        const paterno = (person.Paterno || person.paterno || '').toLowerCase();
        const materno = (person.Materno || person.materno || '').toLowerCase();

        // Buscar en cualquiera de estos campos
        return (
            rut.includes(query) ||
            nombres.includes(query) ||
            paterno.includes(query) ||
            materno.includes(query)
        );
    });

    if (filtered.length > 0) {
        renderFamiliarSearchResultsVisita(filtered, form);
        resultsContainer.style.display = 'block';
    } else {
        resultsContainer.style.display = 'none';
    }
}

/**
 * Renderiza los resultados de búsqueda de Familiar
 * @private
 */
function renderFamiliarSearchResultsVisita(results, form) {
    const container = form.querySelector('#familiar-search-results-visita');
    container.innerHTML = results.map(person => {
        const grado = person.Grado || person.grado || '';
        const nombres = person.Nombres || person.nombres || '';
        const paterno = person.Paterno || person.paterno || '';
        const materno = person.Materno || person.materno || '';
        const rut = person.NrRut || person.RUT || person.rut || '';
        const anexo = person.anexo || person.Anexo || '';

        const nombreCompleto = `${grado} ${nombres} ${paterno} ${materno}`.trim();

        return `
            <button type="button" class="list-group-item list-group-item-action familiar-search-item-visita"
                    data-id="${person.id}" data-rut="${rut}" data-nombre="${nombreCompleto}"
                    data-unidad="${person.Unidad || ''}" data-anexo="${anexo}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>${nombreCompleto}</strong>
                        <br>
                        <small class="text-muted">RUT: ${rut}</small>
                    </div>
                    ${anexo ? `<small class="text-muted">Anexo: ${anexo}</small>` : ''}
                </div>
            </button>
        `;
    }).join('');
}

/**
 * Selecciona un Familiar de la lista de búsqueda
 * @private
 */
function selectFamiliarFromSearchVisita(element) {
    const form = document.getElementById('visita-form');
    const familiarInput = form.querySelector('#familiar_de_personal_input');
    const familiarPersonalIdInput = form.querySelector('#familiar_personal_id_visita');
    const familiarNombreInput = form.querySelector('#familiar_nombre_visita');
    const familiarRutHiddenInput = form.querySelector('#familiar_rut_hidden_visita');
    const familiarUnidadInput = form.querySelector('#familiar_unidad');
    const familiarAnexoInput = form.querySelector('#familiar_anexo');
    const familiarRutDisplay = form.querySelector('#familiar-rut-display');
    const resultsContainer = form.querySelector('#familiar-search-results-visita');

    const id = element.dataset.id;
    const rut = element.dataset.rut;
    const nombre = element.dataset.nombre;
    const unidad = element.dataset.unidad;
    const anexo = element.dataset.anexo;

    familiarInput.value = nombre;
    familiarPersonalIdInput.value = id;
    familiarNombreInput.value = nombre;
    familiarRutHiddenInput.value = rut;
    familiarUnidadInput.value = unidad || '';
    familiarAnexoInput.value = anexo || '';

    if (familiarRutDisplay) {
        familiarRutDisplay.textContent = rut;
    }
    if (resultsContainer) {
        resultsContainer.style.display = 'none';
    }
}
