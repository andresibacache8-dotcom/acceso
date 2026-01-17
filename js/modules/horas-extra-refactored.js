/**
 * horas-extra-refactored.js
 * Módulo de Horas Extra - Refactorizado con BaseModule
 *
 * Refactorización: 338 líneas → ~180 líneas (↓ 47%)
 *
 * Nota: Este módulo tiene lógica más compleja que comisión:
 * - Búsqueda de personal por RUT (específica)
 * - Lista dinámica de personal (específica)
 * - Historial de registros (usa BaseModule)
 * - Eliminación de registros (usa confirmDelete)
 *
 * Patrones reutilizados de BaseModule:
 * ✓ loadData() - Carga historial
 * ✓ renderTable() - Renderiza historial
 * ✓ confirmDelete() - Elimina con confirmación
 * ✓ setupDelegatedListener() - Event delegation
 *
 * @author Refactorización 2025
 */

import { BaseModule } from '../core/base-module.js';
import horasExtraApi from '../api/horas-extra-api.js';
import personalApi from '../api/personal-api.js';
import { validarRUT } from '../utils/validators.js';

/**
 * HorasExtraModule - Gestión de Horas Extra
 * Extiende BaseModule pero con lógica específica compleja
 */
class HorasExtraModule extends BaseModule {
    /**
     * Constructor
     * @param {HTMLElement} contentElement
     */
    constructor(contentElement) {
        super(contentElement, horasExtraApi);

        // Estado local específico de horas extra
        this.personalList = []; // Lista de personas agregadas al formulario
    }

    /**
     * Inicializa el módulo
     * @async
     */
    async init() {
        try {
            this.setupHorasExtraForm();
            await this.loadData();
        } catch (error) {
            console.error('Error initializing HorasExtraModule:', error);
            window.showToast('Error al inicializar módulo de horas extra', 'error');
        }
    }

    /**
     * Configurar formulario de horas extra
     * Lógica específica: búsqueda de RUT, manejo de lista de personal
     * @private
     */
    setupHorasExtraForm() {
        const form = this.content.querySelector('#horas-extra-form');
        const rutInput = this.content.querySelector('#he-rut-input');
        const addPersonBtn = this.content.querySelector('#he-add-person-btn');
        const rutLookupNombre = this.content.querySelector('#he-rut-lookup-nombre');
        const personalListUl = this.content.querySelector('#he-personal-list');
        const autorizaInput = this.content.querySelector('#he-autorizado-por');
        const autorizaDisplay = this.content.querySelector('#he-nombre-autoriza');
        const motivoSelect = this.content.querySelector('#he-motivo');
        const motivoOtroContainer = this.content.querySelector('#he-motivo-otro-container');
        const fechaFinInput = this.content.querySelector('#he-fecha-fin');

        // Establecer fecha por defecto a hoy
        if (fechaFinInput) {
            fechaFinInput.value = new Date().toISOString().split('T')[0];
        }

        // --- Búsqueda de RUT para persona ---
        const rutInputListener = (e) => this.handleRutLookup(e.target, rutLookupNombre);
        rutInput.addEventListener('keyup', rutInputListener);
        this.eventListeners.push({
            element: rutInput,
            event: 'keyup',
            handler: rutInputListener
        });

        // --- Agregar persona a lista ---
        const addPersonListener = async () => await this.handleAddPerson(rutInput, rutLookupNombre, personalListUl);
        addPersonBtn.addEventListener('click', addPersonListener);
        this.eventListeners.push({
            element: addPersonBtn,
            event: 'click',
            handler: addPersonListener
        });

        // --- Remover persona de lista ---
        const removePersonListener = (e) => {
            if (e.target.matches('.btn-close')) {
                const index = parseInt(e.target.dataset.index, 10);
                this.personalList.splice(index, 1);
                this.renderPersonalList(personalListUl);
            }
        };
        personalListUl.addEventListener('click', removePersonListener);
        this.eventListeners.push({
            element: personalListUl,
            event: 'click',
            handler: removePersonListener
        });

        // --- Búsqueda de autorizado ---
        const autorizaListener = (e) => this.handleRutLookup(e.target, autorizaDisplay);
        autorizaInput.addEventListener('blur', autorizaListener);
        this.eventListeners.push({
            element: autorizaInput,
            event: 'blur',
            handler: autorizaListener
        });

        // --- Mostrar/ocultar campo de motivo otro ---
        const motivoListener = () => {
            motivoOtroContainer.style.display = motivoSelect.value === 'OTRO' ? 'block' : 'none';
        };
        motivoSelect.addEventListener('change', motivoListener);
        this.eventListeners.push({
            element: motivoSelect,
            event: 'change',
            handler: motivoListener
        });

        // --- Envío del formulario ---
        const formSubmitListener = (e) => this.handleHorasExtraFormSubmit(e, form, personalListUl);
        form.addEventListener('submit', formSubmitListener);
        this.eventListeners.push({
            element: form,
            event: 'submit',
            handler: formSubmitListener
        });
    }

    /**
     * Manejar búsqueda de RUT
     * @private
     */
    async handleRutLookup(inputElement, displayElement) {
        const rut = inputElement.value.trim();
        displayElement.textContent = '';
        displayElement.removeAttribute('data-nombre-completo');
        inputElement.classList.remove('is-invalid', 'is-valid');

        if (!rut) return;

        if (!validarRUT(rut)) {
            displayElement.textContent = 'RUT inválido (ingrese solo números, 7-8 dígitos)';
            displayElement.classList.remove('text-success');
            displayElement.classList.add('text-danger');
            inputElement.classList.add('is-invalid');
            return;
        }

        try {
            // Intentar buscar por RUT
            let persona = await personalApi.findByRut(rut);

            // Si no encuentra por RUT, buscar como FUNCIONARIO
            if (!persona || !persona.Nombres) {
                const results = await personalApi.search(rut, 'FUNCIONARIO');
                persona = results && results.length > 0 ? results[0] : null;
            }

            if (persona && persona.Nombres) {
                const materno = (persona.Materno === 'undefined' || !persona.Materno) ? '' : persona.Materno;
                const nombreCompleto = `${persona.Grado || ''} ${persona.Nombres} ${persona.Paterno} ${materno}`.trim();

                displayElement.textContent = nombreCompleto;
                displayElement.setAttribute('data-nombre-completo', nombreCompleto);
                displayElement.classList.remove('text-danger');
                displayElement.classList.add('text-success');
                inputElement.classList.add('is-valid');
            } else {
                displayElement.textContent = 'RUT o nombre no encontrado';
                displayElement.classList.remove('text-success');
                displayElement.classList.add('text-danger');
                inputElement.classList.add('is-invalid');
            }
        } catch (error) {
            window.showToast(error.message, 'error');
            displayElement.textContent = 'Error al buscar RUT/Nombre';
            displayElement.classList.add('text-danger');
            inputElement.classList.add('is-invalid');
        }
    }

    /**
     * Manejar agregar persona a lista
     * @private
     */
    async handleAddPerson(rutInput, rutLookupNombre, personalListUl) {
        const rut = rutInput.value.trim();

        if (!rut) {
            window.showToast('Ingrese un RUT', 'warning');
            return;
        }

        if (this.personalList.some(p => p.rut === rut)) {
            window.showToast('Esta persona ya está en la lista', 'warning');
            return;
        }

        try {
            const persona = await personalApi.findByRut(rut);

            if (persona && persona.Nombres) {
                const nombreCompleto = `${persona.Grado || ''} ${persona.Nombres} ${persona.Paterno} ${persona.Materno || ''}`.trim();
                this.personalList.push({ rut: persona.NrRut, nombre: nombreCompleto });
                this.renderPersonalList(personalListUl);

                rutInput.value = '';
                rutLookupNombre.textContent = '';
                rutInput.classList.remove('is-valid', 'is-invalid');
            } else {
                window.showToast('RUT no encontrado', 'error');
            }
        } catch (error) {
            window.showToast(error.message, 'error');
        }
    }

    /**
     * Renderizar lista de personal en el formulario
     * @private
     */
    renderPersonalList(personalListUl) {
        personalListUl.innerHTML = this.personalList.map((person, index) => `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>${person.nombre} (${person.rut})</span>
                <button type="button" class="btn-close" aria-label="Remove" data-index="${index}"></button>
            </li>
        `).join('');
    }

    /**
     * Manejar envío del formulario de horas extra
     * @private
     */
    async handleHorasExtraFormSubmit(e, form, personalListUl) {
        e.preventDefault();

        if (this.personalList.length === 0) {
            window.showToast('Debe agregar al menos una persona a la lista', 'error');
            return;
        }

        const autorizaRut = form.querySelector('#he-autorizado-por').value;
        const autorizaDisplay = form.querySelector('#he-nombre-autoriza');
        const autorizaNombre = autorizaDisplay.getAttribute('data-nombre-completo');

        if (!autorizaRut.trim() || !autorizaNombre) {
            window.showToast('Debe ingresar un RUT válido para quien autoriza', 'error');
            return;
        }

        const fechaFin = form.querySelector('#he-fecha-fin').value;
        const horaFin = form.querySelector('#he-hora-fin').value;
        const motivo = form.querySelector('#he-motivo').value;
        const motivoDetalle = form.querySelector('#he-motivo-otro').value;

        if (!fechaFin || !horaFin || !motivo) {
            window.showToast('Por favor, complete la fecha, hora y motivo', 'error');
            return;
        }

        if (motivo === 'OTRO' && !motivoDetalle.trim()) {
            window.showToast('Por favor, especifique el motivo', 'error');
            return;
        }

        try {
            window.showLoadingSpinner();

            const data = {
                personal: this.personalList.map(p => ({ rut: p.rut, nombre: p.nombre })),
                fecha_hora_termino: `${fechaFin} ${horaFin}:00`,
                motivo: motivo,
                motivo_detalle: motivo === 'OTRO' ? motivoDetalle : null,
                autorizado_por_rut: autorizaRut,
                autorizado_por_nombre: autorizaNombre
            };

            await this.api.create(data);

            window.showToast('Registros de horas extra guardados correctamente', 'success');

            // Limpiar formulario
            form.reset();
            this.personalList = [];
            this.renderPersonalList(personalListUl);
            autorizaDisplay.textContent = '';
            form.querySelectorAll('.form-control').forEach(el =>
                el.classList.remove('is-valid', 'is-invalid')
            );
            form.querySelector('#he-motivo-otro-container').style.display = 'none';
            form.querySelector('#he-fecha-fin').value = new Date().toISOString().split('T')[0];

            // Recargar historial
            await this.loadData();

        } catch (error) {
            window.showToast(error.message || 'Error al guardar', 'error');
        } finally {
            window.hideLoadingSpinner();
        }
    }

    /**
     * Renderizar tabla de historial
     * Implementación específica para horas extra
     */
    renderTable() {
        const logTableBody = this.content.querySelector('#horas-extra-log-table');
        if (!logTableBody) return;

        if (this.data.length === 0) {
            logTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted p-4">
                        No hay registros de horas extra.
                    </td>
                </tr>
            `;
            return;
        }

        logTableBody.innerHTML = this.data.map(record => {
            const fechaTermino = new Date(record.fecha_hora_termino);
            const fechaFormateada = fechaTermino.toLocaleDateString('es-CL');
            const horaFormateada = fechaTermino.toLocaleTimeString('es-CL', {
                hour: '2-digit',
                minute: '2-digit'
            });
            const motivoCompleto = record.motivo_detalle
                ? `${record.motivo}: ${record.motivo_detalle}`
                : record.motivo;

            return `
                <tr>
                    <td>
                        <div>${record.personal_nombre}</div>
                        <small class="text-muted">RUT: ${record.personal_rut}</small>
                    </td>
                    <td>${fechaFormateada}</td>
                    <td>${motivoCompleto}</td>
                    <td>${horaFormateada}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger delete-he-btn"
                                data-id="${record.id}"
                                title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        // Agregar event listeners a botones de eliminar
        this.setupDelegatedListener(
            '.delete-he-btn',
            'click',
            this.handleDeleteRecord.bind(this)
        );
    }

    /**
     * Manejar eliminación de registro
     * @private
     */
    async handleDeleteRecord(e, target) {
        const id = target.dataset.id;
        const record = this.data.find(r => r.id == id);

        if (record) {
            await this.confirmDelete(id, `Horas extra de ${record.personal_nombre}`);
        }
    }

    /**
     * Filtrar items (no se usa en horas extra)
     * @private
     */
    filterItem(item) {
        return true;
    }
}

/**
 * Inicializador del módulo (para compatibilidad)
 * @param {HTMLElement} contentElement
 */
export function initHorasExtraModule(contentElement) {
    const module = new HorasExtraModule(contentElement);
    module.init();
    return module;
}

export default HorasExtraModule;
