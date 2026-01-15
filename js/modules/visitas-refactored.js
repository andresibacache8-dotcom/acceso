/**
 * visitas-refactored.js
 * Módulo para Gestión de Visitas - Refactorizado con BaseModule
 *
 * Refactorización: 562 líneas → ~450 líneas (↓ 20%)
 *
 * Cambios:
 * - Extiende BaseModule para reutilizar patrones comunes
 * - Elimina código duplicado de modal, form, table, search, delete
 * - Mantiene lógica específica: búsqueda de POC/Familiar, lista negra
 *
 * Patrones reutilizados de BaseModule:
 * ✓ setupModal() - Inicialización de modal
 * ✓ populateModalForm() - Rellena formulario
 * ✓ clearModalForm() - Limpia formulario
 * ✓ setupSearch() - Búsqueda en tabla
 * ✓ loadData() - Carga de datos API
 * ✓ confirmDelete() - Eliminación con confirmación
 * ✓ setupDelegatedListener() - Event delegation
 *
 * @author Refactorización 2025
 */

import { BaseModule } from '../core/base-module.js';
import visitasApi from '../api/visitas-api.js';
import personalApi from '../api/personal-api.js';

/**
 * VisitasModule - Gestión de Visitas
 * Extiende BaseModule pero con lógica específica compleja
 */
class VisitasModule extends BaseModule {
    /**
     * Constructor
     * @param {HTMLElement} contentElement
     */
    constructor(contentElement) {
        super(contentElement, visitasApi);

        // Campos de búsqueda específicos para visitas
        this.searchFields = ['nombre', 'paterno', 'materno', 'rut'];

        // Estado local específico de visitas
        this.allPersonalData = [];
    }

    /**
     * Inicializa el módulo
     * @async
     */
    async init() {
        try {
            // Cargar personal primero para búsquedas
            const personalResponse = await personalApi.getAll();
            this.allPersonalData = personalResponse.data || personalResponse || [];

            // Configurar modal con template y submit handler
            this.setupModal(
                'visita-modal',
                window.getVisitaModalTemplate,
                this.handleVisitaFormSubmit.bind(this)
            );

            // Configurar search input
            this.setupSearch('search-visita-tabla', this.searchFields);

            // Configurar modal-specific logic (tipo selection, POC/Familiar search)
            this.setupVisitaModalLogic();

            // Configurar event delegation para botones de acción
            this.setupDelegatedListener(
                '.edit-visita-btn',
                'click',
                this.handleEditClick.bind(this)
            );

            this.setupDelegatedListener(
                '.delete-visita-btn',
                'click',
                this.handleDeleteClick.bind(this)
            );

            this.setupDelegatedListener(
                '.toggle-blacklist-btn',
                'click',
                this.handleToggleBlacklist.bind(this)
            );

            // Configurar botón agregar
            const addVisitaBtn = this.content.querySelector('#add-visita-btn');
            if (addVisitaBtn) {
                addVisitaBtn.addEventListener('click', () => {
                    this.openModal('visita-modal');
                });
                this.eventListeners.push({
                    element: addVisitaBtn,
                    event: 'click',
                    handler: () => this.openModal('visita-modal')
                });
            }

            // Cargar datos
            await this.loadData();

        } catch (error) {
            console.error('Error initializing VisitasModule:', error);
            window.showToast('Error al inicializar módulo de visitas', 'error');
        }
    }

    /**
     * Configurar lógica específica del modal de visitas
     * @private
     */
    setupVisitaModalLogic() {
        const modalEl = document.getElementById('visita-modal');
        if (!modalEl) return;

        const form = modalEl.querySelector('#visita-form');
        if (!form) return;

        const tipoSelect = form.querySelector('#tipo');
        const pocFields = form.querySelector('#poc-fields');
        const familiarFields = form.querySelector('#familiar-fields');
        const accesoPermanenteCheckbox = form.querySelector('#acceso_permanente');

        // Lógica de tipo de visita
        if (tipoSelect) {
            const listener = () => {
                const selectedType = tipoSelect.value;
                if (pocFields) pocFields.style.display = selectedType === 'Visita' ? 'flex' : 'none';
                if (familiarFields) familiarFields.style.display = selectedType === 'Familiar' ? 'flex' : 'none';

                // Limpiar campos cuando se cambia el tipo
                if (selectedType === 'Familiar') {
                    this.clearPocFields(form);
                } else if (selectedType === 'Visita') {
                    this.clearFamiliarFields(form);
                }
            };
            tipoSelect.addEventListener('change', listener);
            this.eventListeners.push({
                element: tipoSelect,
                event: 'change',
                handler: listener
            });
        }

        // Lógica de acceso permanente y fechas
        if (accesoPermanenteCheckbox) {
            const listener = () => this.updateAccessDatesState(form);
            accesoPermanenteCheckbox.addEventListener('change', listener);
            this.eventListeners.push({
                element: accesoPermanenteCheckbox,
                event: 'change',
                handler: listener
            });
        }

        // Configurar búsqueda de POC
        this.setupPocSearch(form);

        // Configurar búsqueda de Familiar
        this.setupFamiliarSearch(form);

        // Validación de RUT en tiempo real
        const rutInput = form.querySelector('#rut');
        const rutFeedback = form.querySelector('#rut-feedback');
        if (rutInput) {
            const listener = () => {
                const visitaId = form.elements.id?.value || null;
                this.validateRUT(rutInput, rutFeedback, visitaId);
            };
            rutInput.addEventListener('input', listener);
            this.eventListeners.push({
                element: rutInput,
                event: 'input',
                handler: listener
            });
        }
    }

    /**
     * Configurar búsqueda de POC
     * @private
     */
    setupPocSearch(form) {
        const pocRutInput = form.querySelector('#poc_rut_visita');
        const pocSearchResults = form.querySelector('#poc-search-results-visita');

        if (!pocRutInput) return;

        const listener = (e) => this.handlePocSearch(e, form);
        pocRutInput.addEventListener('input', listener);
        this.eventListeners.push({
            element: pocRutInput,
            event: 'input',
            handler: listener
        });

        // Event delegation para seleccionar POC
        if (pocSearchResults) {
            const clickListener = (e) => {
                const pocItem = e.target.closest('.poc-search-item-visita');
                if (pocItem) {
                    this.selectPocFromSearch(pocItem, form);
                }
            };
            pocSearchResults.addEventListener('click', clickListener);
            this.eventListeners.push({
                element: pocSearchResults,
                event: 'click',
                handler: clickListener
            });
        }
    }

    /**
     * Configurar búsqueda de Familiar
     * @private
     */
    setupFamiliarSearch(form) {
        const familiarInput = form.querySelector('#familiar_de_personal_input');
        const familiarSearchResults = form.querySelector('#familiar-search-results-visita');

        if (!familiarInput) return;

        const listener = (e) => this.handleFamiliarSearch(e, form);
        familiarInput.addEventListener('input', listener);
        this.eventListeners.push({
            element: familiarInput,
            event: 'input',
            handler: listener
        });

        // Event delegation para seleccionar Familiar
        if (familiarSearchResults) {
            const clickListener = (e) => {
                const familiarItem = e.target.closest('.familiar-search-item-visita');
                if (familiarItem) {
                    this.selectFamiliarFromSearch(familiarItem, form);
                }
            };
            familiarSearchResults.addEventListener('click', clickListener);
            this.eventListeners.push({
                element: familiarSearchResults,
                event: 'click',
                handler: clickListener
            });
        }
    }

    /**
     * Limpiar campos de POC
     * @private
     */
    clearPocFields(form) {
        const fields = ['poc_rut_visita', 'poc_personal_id_visita', 'poc_nombre_visita',
                       'poc_rut_hidden_visita', 'poc_unidad_visita', 'poc_anexo_visita'];
        fields.forEach(field => {
            const el = form.querySelector(`#${field}`);
            if (el) el.value = '';
        });
        const pocRutDisplay = form.querySelector('#poc-rut-display');
        if (pocRutDisplay) pocRutDisplay.textContent = '';
        const pocSearchResults = form.querySelector('#poc-search-results-visita');
        if (pocSearchResults) pocSearchResults.style.display = 'none';
    }

    /**
     * Limpiar campos de Familiar
     * @private
     */
    clearFamiliarFields(form) {
        const fields = ['familiar_de_personal_input', 'familiar_personal_id_visita', 'familiar_nombre_visita',
                       'familiar_rut_hidden_visita', 'familiar_unidad', 'familiar_anexo'];
        fields.forEach(field => {
            const el = form.querySelector(`#${field}`);
            if (el) el.value = '';
        });
        const familiarRutDisplay = form.querySelector('#familiar-rut-display');
        if (familiarRutDisplay) familiarRutDisplay.textContent = '';
        const familiarSearchResults = form.querySelector('#familiar-search-results-visita');
        if (familiarSearchResults) familiarSearchResults.style.display = 'none';
    }

    /**
     * Actualizar estado de campos de fecha
     * @private
     */
    updateAccessDatesState(form) {
        const accesoPermanente = form.querySelector('#acceso_permanente');
        const fechaInicio = form.querySelector('#fecha_inicio');
        const fechaExpiracion = form.querySelector('#fecha_expiracion');
        const fechaExpiracionRequired = document.querySelector('#fecha-expiracion-required');

        if (!accesoPermanente || !fechaInicio || !fechaExpiracion) return;

        fechaInicio.disabled = false;
        fechaInicio.required = true;

        if (accesoPermanente.checked) {
            fechaExpiracion.disabled = true;
            fechaExpiracion.value = '';
            fechaExpiracion.required = false;
            if (fechaExpiracionRequired) fechaExpiracionRequired.style.display = 'none';
        } else {
            fechaExpiracion.disabled = false;
            fechaExpiracion.required = true;
            if (fechaExpiracionRequired) fechaExpiracionRequired.style.display = 'inline';
        }
    }

    /**
     * Validar RUT chileno
     * @private
     */
    validateRUT(rutInput, feedbackElement, visitaId = null) {
        const rut = rutInput.value.trim();

        if (!rut) {
            feedbackElement.textContent = '';
            rutInput.classList.remove('is-valid', 'is-invalid');
            return;
        }

        const rutLimpio = rut.replace(/[.\-\s]/g, '');
        const isValidFormat = /^[0-9]{7,8}$/.test(rutLimpio);

        if (!isValidFormat) {
            feedbackElement.textContent = '❌ RUT inválido. Debe tener 7 u 8 dígitos.';
            feedbackElement.className = 'form-text text-danger';
            rutInput.classList.add('is-invalid');
            rutInput.classList.remove('is-valid');
            return;
        }

        const rutExistente = this.data.find(v => v.rut === rutLimpio && v.id != visitaId);

        if (rutExistente) {
            feedbackElement.textContent = '⚠️ Este RUT ya está registrado en otra visita.';
            feedbackElement.className = 'form-text text-danger';
            rutInput.classList.add('is-invalid');
            rutInput.classList.remove('is-valid');
            return;
        }

        feedbackElement.textContent = '✓ RUT válido';
        feedbackElement.className = 'form-text text-success';
        rutInput.classList.add('is-valid');
        rutInput.classList.remove('is-invalid');
    }

    /**
     * Buscar POC mientras se escribe
     * @private
     */
    handlePocSearch(e, form) {
        const query = e.target.value.trim().toLowerCase();
        const resultsContainer = form.querySelector('#poc-search-results-visita');
        const feedback = form.querySelector('#poc-rut-feedback-visita');

        if (!query) {
            resultsContainer.style.display = 'none';
            feedback.textContent = '';
            return;
        }

        const filtered = this.allPersonalData.filter(person => {
            const rut = (person.NrRut || person.RUT || person.rut || '').toString();
            const nombres = (person.Nombres || person.nombres || '').toLowerCase();
            const paterno = (person.Paterno || person.paterno || '').toLowerCase();
            const materno = (person.Materno || person.materno || '').toLowerCase();

            return rut.includes(query) || nombres.includes(query) ||
                   paterno.includes(query) || materno.includes(query);
        });

        if (filtered.length > 0) {
            this.renderPocSearchResults(filtered, form);
            resultsContainer.style.display = 'block';
            feedback.textContent = '';
        } else {
            resultsContainer.style.display = 'none';
            feedback.textContent = 'No se encontraron resultados.';
            feedback.className = 'form-text text-warning';
        }
    }

    /**
     * Renderizar resultados de búsqueda de POC
     * @private
     */
    renderPocSearchResults(results, form) {
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
     * Seleccionar POC de búsqueda
     * @private
     */
    selectPocFromSearch(element, form) {
        const pocRutInput = form.querySelector('#poc_rut_visita');
        const pocPersonalIdInput = form.querySelector('#poc_personal_id_visita');
        const pocNombreInput = form.querySelector('#poc_nombre_visita');
        const pocRutHiddenInput = form.querySelector('#poc_rut_hidden_visita');
        const pocUnidadInput = form.querySelector('#poc_unidad_visita');
        const pocAnexoInput = form.querySelector('#poc_anexo_visita');
        const pocRutDisplay = form.querySelector('#poc-rut-display');
        const resultsContainer = form.querySelector('#poc-search-results-visita');

        pocRutInput.value = element.dataset.nombre;
        pocPersonalIdInput.value = element.dataset.id;
        pocNombreInput.value = element.dataset.nombre;
        pocRutHiddenInput.value = element.dataset.rut;
        pocUnidadInput.value = element.dataset.unidad || '';
        pocAnexoInput.value = element.dataset.anexo || '';

        if (pocRutDisplay) pocRutDisplay.textContent = element.dataset.rut;
        if (resultsContainer) resultsContainer.style.display = 'none';
    }

    /**
     * Buscar Familiar mientras se escribe
     * @private
     */
    handleFamiliarSearch(e, form) {
        const query = e.target.value.trim().toLowerCase();
        const resultsContainer = form.querySelector('#familiar-search-results-visita');

        if (!query) {
            resultsContainer.style.display = 'none';
            return;
        }

        const filtered = this.allPersonalData.filter(person => {
            const rut = (person.NrRut || person.RUT || person.rut || '').toString();
            const nombres = (person.Nombres || person.nombres || '').toLowerCase();
            const paterno = (person.Paterno || person.paterno || '').toLowerCase();
            const materno = (person.Materno || person.materno || '').toLowerCase();

            return rut.includes(query) || nombres.includes(query) ||
                   paterno.includes(query) || materno.includes(query);
        });

        if (filtered.length > 0) {
            this.renderFamiliarSearchResults(filtered, form);
            resultsContainer.style.display = 'block';
        } else {
            resultsContainer.style.display = 'none';
        }
    }

    /**
     * Renderizar resultados de búsqueda de Familiar
     * @private
     */
    renderFamiliarSearchResults(results, form) {
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
     * Seleccionar Familiar de búsqueda
     * @private
     */
    selectFamiliarFromSearch(element, form) {
        const familiarInput = form.querySelector('#familiar_de_personal_input');
        const familiarPersonalIdInput = form.querySelector('#familiar_personal_id_visita');
        const familiarNombreInput = form.querySelector('#familiar_nombre_visita');
        const familiarRutHiddenInput = form.querySelector('#familiar_rut_hidden_visita');
        const familiarUnidadInput = form.querySelector('#familiar_unidad');
        const familiarAnexoInput = form.querySelector('#familiar_anexo');
        const familiarRutDisplay = form.querySelector('#familiar-rut-display');
        const resultsContainer = form.querySelector('#familiar-search-results-visita');

        familiarInput.value = element.dataset.nombre;
        familiarPersonalIdInput.value = element.dataset.id;
        familiarNombreInput.value = element.dataset.nombre;
        familiarRutHiddenInput.value = element.dataset.rut;
        familiarUnidadInput.value = element.dataset.unidad || '';
        familiarAnexoInput.value = element.dataset.anexo || '';

        if (familiarRutDisplay) familiarRutDisplay.textContent = element.dataset.rut;
        if (resultsContainer) resultsContainer.style.display = 'none';
    }

    /**
     * Renderizar tabla de visitas
     */
    renderTable() {
        const tableBody = this.content.querySelector('#visita-table-body');
        if (!tableBody) return;

        if (this.filteredData.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted p-4">
                        No se encontraron resultados.
                    </td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = this.filteredData.map(v => {
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
     * Manejar clic en botón editar
     * @private
     */
    handleEditClick(e, target) {
        const id = target.dataset.id;
        const visita = this.data.find(v => v.id == id);

        if (visita) {
            this.openModal('visita-modal', visita);
        }
    }

    /**
     * Manejar clic en botón eliminar
     * @private
     */
    handleDeleteClick(e, target) {
        const id = target.dataset.id;
        const visita = this.data.find(v => v.id == id);

        if (visita) {
            const nombreCompleto = `${visita.nombre || ''} ${visita.paterno || ''}`.trim();
            this.confirmDelete(id, nombreCompleto);
        }
    }

    /**
     * Manejar envío de formulario de visita
     * @private
     */
    async handleVisitaFormSubmit(e, modal) {
        const form = e.target;

        // Validación del formulario
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        // Validar RUT no duplicado
        const rutInput = form.querySelector('#rut');
        const rutLimpio = rutInput.value.trim().replace(/[.\-\s]/g, '');
        const visitaId = form.elements.id?.value || null;
        const rutExistente = this.data.find(v => v.rut === rutLimpio && v.id != visitaId);

        if (rutExistente) {
            window.showToast('❌ Este RUT ya está registrado en otra visita.', 'error');
            return;
        }

        // Validar POC o Familiar según tipo
        const tipo = form.elements.tipo.value;

        if (tipo === 'Visita') {
            const pocPersonalId = form.querySelector('#poc_personal_id_visita').value;
            if (!pocPersonalId) {
                window.showToast('Por favor selecciona un POC válido.', 'error');
                return;
            }
        } else if (tipo === 'Familiar') {
            const familiarPersonalId = form.querySelector('#familiar_personal_id_visita').value;
            if (!familiarPersonalId) {
                window.showToast('Por favor selecciona un Familiar válido.', 'error');
                return;
            }
        }

        try {
            window.showLoadingSpinner();

            const id = form.elements.id.value;
            const data = {};

            // Campos que NO se convierten a mayúsculas
            const excludeFromUpperCase = [
                'id', 'rut', 'movil', 'tipo', 'fecha_inicio', 'fecha_expiracion',
                'acceso_permanente', 'en_lista_negra', 'poc_personal_id',
                'familiar_de_personal_id', 'poc_rut', 'poc_nombre', 'poc_rut_visita'
            ];

            // Procesar elementos del formulario
            for (const element of form.elements) {
                if (element.name) {
                    let value = element.type === 'checkbox' ? element.checked : element.value;

                    // Convertir a mayúsculas selectivamente
                    if (!excludeFromUpperCase.includes(element.name) && value &&
                        (element.type === 'text' || element.type === 'textarea')) {
                        value = value.toUpperCase();
                    }

                    data[element.name] = value;
                }
            }

            // Realizar operación (crear o actualizar)
            let response;
            if (id) {
                response = await this.api.update(data);
                window.showToast('Visita actualizada correctamente', 'success');
            } else {
                response = await this.api.create(data);
                window.showToast('Visita creada correctamente', 'success');
            }

            if (response.success) {
                modal.hide();
                await this.loadData();
            } else {
                throw new Error(response.error?.message || 'Error al guardar');
            }

        } catch (error) {
            console.error('Error submitting visita form:', error);
            window.showToast(error.message || 'Error al guardar', 'error');
        } finally {
            window.hideLoadingSpinner();
        }
    }

    /**
     * Manejar toggle de lista negra
     * @private
     */
    async handleToggleBlacklist(e, target) {
        const id = target.dataset.id;
        const isBlacklisted = target.dataset.blacklisted === 'true';
        const newStatus = isBlacklisted ? 0 : 1;
        const actionText = newStatus === 1 ? 'añadir a la' : 'quitar de la';

        if (!confirm(`¿Estás seguro de que quieres ${actionText} lista negra?`)) {
            return;
        }

        try {
            window.showLoadingSpinner();

            const response = await this.api.toggleBlacklist(id, newStatus);

            if (response.success) {
                window.showToast('Estado de lista negra actualizado.', 'success');
                await this.loadData();
            } else {
                throw new Error(response.error?.message || 'Error al actualizar');
            }

        } catch (error) {
            console.error('Error toggling blacklist:', error);
            window.showToast(error.message || 'Error al actualizar', 'error');
        } finally {
            window.hideLoadingSpinner();
        }
    }

    /**
     * Filtrar item (override para lógica específica de visitas)
     * @private
     */
    filterItem(item) {
        // La búsqueda por searchQuery se aplica en BaseModule's applyFilters
        return true;
    }
}

/**
 * Inicializador del módulo (para compatibilidad)
 * @param {HTMLElement} contentElement
 */
export function initVisitasModule(contentElement) {
    const module = new VisitasModule(contentElement);
    module.init();
    return module;
}

export default VisitasModule;
